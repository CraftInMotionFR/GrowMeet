<?php

class CourseManager extends Manager {
    // Catalogue complet - GET /courses
    public function findAll(): array {
        $stmt = $this->pdo->prepare('
            SELECT c.id_course,
                ct.id_course_type,
                ct.name AS title,
                ct.description AS desc,
                ct.min_age,
                ct.max_age,
                c.date,
                c.start_time,
                c.end_time,
                c.status,
                u.first_name AS coach_first,
                u.last_name AS coach_last,
                (c.max_participants - COUNT(b.id_dog)) AS places
            FROM course c
            JOIN course_type ct ON ct.id_course_type = c.id_course_type
            JOIN users u ON u.id_user = c.id_user
            LEFT JOIN booking b ON b.id_course = c.id_course
            AND b.status NOT IN (\'cancelled\', \'waiting\')
            WHERE c.date >= CURDATE()
            GROUP BY c.id_course
            ORDER BY c.date ASC, c.start_time ASC
        ');
        $stmt->execute();

        return array_map([$this, 'formatCourse'], $stmt->fetchAll());
    }

    // Détail d'un cours - GET /courses/{id}
    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('
            SELECT c.id_course,
                ct.id_course_type,
                ct.name AS title,
                ct.description AS desc,
                ct.min_age,
                ct.max_age,
                c.date,
                c.start_time,
                c.end_time,
                c.status,
                c.min_participants,
                c.max_participants,
                u.first_name AS coach_first,
                u.last_name AS coach_last,
                (c.max_participants - COUNT(b.id_dog)) AS places
            FROM course c
            JOIN course_type ct ON ct.id_course_type = c.id_course_type
            JOIN users u ON u.id_user = c.id_user
            LEFT JOIN booking b ON b.id_course = c.id_course
            AND b.status NOT IN (\'cancelled\', \'waiting\')
            WHERE c.id_course = :id
            GROUP BY c.id_course
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->formatCourse($row) : null;
    }

    // Toutes les séances, sans filtre de date - GET /admin/sessions
    public function findAllForAdmin(): array {
        $stmt = $this->pdo->prepare('
            SELECT c.id_course, c.id_user AS coach_id, ct.name AS title,
                c.date, c.start_time, c.end_time, c.status,
                c.max_participants,
                u.first_name AS coach_first, u.last_name AS coach_last,
                (c.max_participants - COUNT(b.id_dog)) AS places
            FROM course c
            JOIN course_type ct ON ct.id_course_type = c.id_course_type
            JOIN users u ON u.id_user = c.id_user
            LEFT JOIN booking b ON b.id_course = c.id_course
            AND b.status NOT IN (\'cancelled\', \'waiting\')
            GROUP BY c.id_course
            ORDER BY c.date DESC, c.start_time DESC
        ');
        $stmt->execute();

        return array_map(fn($r) => [
            'id' => $r['id_course'],
            'coach_id' => (int) $r['coach_id'],
            'title' => $r['title'],
            'schedule' => $this->formatSchedule($r['date'], $r['start_time'], $r['end_time']),
            'coach' => $r['coach_first'] . ' ' . $r['coach_last'],
            'status' => $r['status'],
            'places' => max(0, (int) $r['places']) . '/' . (int) $r['max_participants'],
        ], $stmt->fetchAll());
    }

    // Données brutes d'une séance pour le formulaire d'édition admin
    public function findRawById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM course WHERE id_course = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // $data = ['id_course_type', 'id_user', 'date', 'start_time', 'end_time', 'min_participants', 'max_participants', 'status']
    public function create(array $data): int {
        $name = (new CourseTypeManager())->findById((int) $data['id_course_type'])['name'] ?? 'Séance';

        $stmt = $this->pdo->prepare('
            INSERT INTO course (name, date, start_time, end_time, min_participants, max_participants, status, id_user, id_course_type)
            VALUES (:name, :date, :start_time, :end_time, :min_participants, :max_participants, :status, :id_user, :id_course_type)
        ');
        $stmt->execute([
            'name' => $name,
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'min_participants' => $data['min_participants'],
            'max_participants' => $data['max_participants'],
            'status' => $data['status'],
            'id_user' => $data['id_user'],
            'id_course_type' => $data['id_course_type'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $stmt = $this->pdo->prepare('
            UPDATE course
            SET date = :date, start_time = :start_time, end_time = :end_time,
                min_participants = :min_participants, max_participants = :max_participants,
                status = :status, id_user = :id_user, id_course_type = :id_course_type
            WHERE id_course = :id
        ');
        $stmt->execute([
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'min_participants' => $data['min_participants'],
            'max_participants' => $data['max_participants'],
            'status' => $data['status'],
            'id_user' => $data['id_user'],
            'id_course_type' => $data['id_course_type'],
            'id' => $id,
        ]);
    }

    // Supprime la séance ; les bookings liés sont supprimés en cascade (ON DELETE CASCADE)
    public function delete(int $id): void {
        $stmt = $this->pdo->prepare('DELETE FROM course WHERE id_course = :id');
        $stmt->execute(['id' => $id]);
    }

    // Cours suggérés (dashboard) - déjà existant
    public function findSuggestedForUser(int $userId): array {
        $stmt = $this->pdo->prepare('
            SELECT c.id_course, ct.name AS title,
                c.date, c.start_time, c.end_time,
                (c.max_participants - COUNT(b.id_dog)) AS places
            FROM course c
            JOIN course_type ct ON ct.id_course_type = c.id_course_type
            LEFT JOIN booking b ON b.id_course = c.id_course
            AND b.status NOT IN (\'cancelled\', \'waiting\')
            WHERE c.status = \'open\'
              AND c.date >= CURDATE()
              AND c.id_course NOT IN (
                SELECT b2.id_course
                FROM booking b2
                JOIN dog d ON d.id_dog = b2.id_dog
                WHERE d.id_user = :id
              )
            GROUP BY c.id_course
            HAVING places > 0
            ORDER BY c.date ASC
            LIMIT 3
        ');
        $stmt->execute(['id' => $userId]);

        return array_map(fn($r) => [
            'id' => $r['id_course'],
            'title' => $r['title'],
            'schedule' => $this->formatSchedule($r['date'], $r['start_time'], $r['end_time']),
            'places' => (int) $r['places'],
        ], $stmt->fetchAll());
    }

    // Filtres d'âge de la page catalogue (voir courses/index.php), un cours "Tous âges" doit apparaître dans chacun d'eux, en plus de sa catégorie propre
    private const AGE_FILTER_SLUGS = ['chiot', '6-12', '1-2', '2+'];

    // Une image différente par type de cours (pas encore de colonne image sur course/course_type)
    private const IMAGE_BY_TYPE = [
        1 => 'Dog_gallery_2.png',  // École du chiot
        2 => 'Dog_gallery_8.jpg',  // Éducation 6-12 mois
        3 => 'Dog_gallery_7.jpg',  // Éducation 1-2 ans
        4 => 'Dog_gallery_10.jpg',  // Éducation +2 ans
        5 => 'Dog_gallery_6.jpg',  // Socialisation
        6 => 'Dog_gallery_5.jpg',  // Parcours sportifs
        7 => 'Dog_gallery_9.jpg',  // Dressage avancé
    ];

    // Objectifs affichés sur la fiche cours, par type de cours (pas encore de colonne en base)
    private const OBJECTIVES_BY_TYPE = [
        1 => ['Socialisation avec d’autres chiots', 'Apprentissage des ordres de base (assis, couché, pas bouger)', 'Marche en laisse sans tirer', 'Rappel au pied', 'Gestion des morsures et mordillements'],
        2 => ['Renforcement des ordres de base', 'Marche en laisse en environnement varié', 'Rappel fiable', 'Gestion de la période d’adolescence', 'Concentration en présence de distractions'],
        3 => ['Consolidation des acquis', 'Obéissance avancée', 'Travail à distance', 'Positions (assis, debout, couché) sur ordre', 'Contrôle en présence de distractions'],
        4 => ['Perfectionnement de l’obéissance', 'Rappel en toute circonstance', 'Travail sans laisse', 'Maîtrise des ordres à distance', 'Calme et concentration'],
        5 => ['Rencontres canines encadrées', 'Confiance en situation nouvelle', 'Gestion de la réactivité', 'Lecture du langage canin', 'Bonnes manières en groupe'],
        6 => ['Agilité et coordination', 'Franchissement d’obstacles', 'Confiance et complicité maître-chien', 'Dépense physique encadrée', 'Écoute sous stimulation'],
        7 => ['Enchaînements d’ordres complexes', 'Travail à distance et hors laisse', 'Précision et rapidité d’exécution', 'Renforcement de la complicité', 'Préparation à la compétition'],
    ];

    private const DEFAULT_OBJECTIVES = ['Renforcement des acquis', 'Complicité maître-chien', 'Progression encadrée par un coach'];

    // Lieu unique pour l'instant (pas de colonne en base)
    private const DEFAULT_LOCATION = 'Terrain A - Parc du club';

    private function formatCourse(array $r): array {
        $places = max(0, (int) $r['places']);
        $age = $this->formatAgeRange($r['min_age'], $r['max_age'] ?? null);

        $categories = [$this->slugify($r['title'])];
        if ($age === 'Tous âges') {
            $categories = array_unique(array_merge($categories, self::AGE_FILTER_SLUGS));
        }

        $d = new DateTime($r['date']);
        $days = ['Mon'=>'Lundi','Tue'=>'Mardi','Wed'=>'Mercredi','Thu'=>'Jeudi',
                 'Fri'=>'Vendredi','Sat'=>'Samedi','Sun'=>'Dimanche'];
        $duration = (new DateTime($r['start_time']))->diff(new DateTime($r['end_time']));
        $durationStr = $duration->h > 0 ? "{$duration->h}h" . ($duration->i > 0 ? $duration->i : '') : "{$duration->i}min";

        return [
            'id' => $r['id_course'],
            'title' => $r['title'],
            'category' => implode(' ', $categories),
            'desc' => $r['desc'] ?? '',
            'age' => $age,
            'schedule' => $this->formatSchedule($r['date'], $r['start_time'], $r['end_time']),
            'day' => ($days[$d->format('D')] ?? $d->format('D')) . ' ' . $d->format('j') . ' ' . $this->monthName($d->format('n')),
            'time_range' => substr($r['start_time'], 0, 5) . ' - ' . substr($r['end_time'], 0, 5),
            'duration' => $durationStr,
            'coach' => $r['coach_first'] . ' ' . $r['coach_last'],
            'places' => $places,
            'max_participants' => isset($r['max_participants']) ? (int) $r['max_participants'] : null,
            'status' => $r['status'],
            'objectives' => self::OBJECTIVES_BY_TYPE[$r['id_course_type']] ?? self::DEFAULT_OBJECTIVES,
            'location' => self::DEFAULT_LOCATION,
            'min_birth' => $r['min_age'] ?? null,
            'max_birth' => $r['max_age'] ?? null,
            'image' => self::IMAGE_BY_TYPE[$r['id_course_type']] ?? 'Dog_gallery_1.png',
        ];
    }

    // Au-delà de ce nombre de mois, min_age est considéré comme une valeur sentinelle ("pas de limite d'âge maximum") plutôt qu'une borne réelle
    private const NO_UPPER_LIMIT_MONTHS = 96;

    private function formatAgeRange(?string $minAge, ?string $maxAge): string {
        $now = new DateTime();

        // min_age = date de naissance la plus ancienne acceptée -> borne d'âge MAX
        $upperMonths = $minAge ? $this->monthsSince($minAge, $now) : null;
        // max_age = date de naissance la plus récente acceptée -> borne d'âge MIN
        $lowerMonths = $maxAge ? $this->monthsSince($maxAge, $now) : 0;

        $noUpperLimit = $upperMonths === null || $upperMonths >= self::NO_UPPER_LIMIT_MONTHS;

        if ($noUpperLimit && $lowerMonths <= 0) {
            return 'Tous âges';
        }

        if ($noUpperLimit) {
            return '+' . $this->formatMonths($lowerMonths);
        }

        return $this->formatMonths($lowerMonths) . ' - ' . $this->formatMonths($upperMonths);
    }

    // Un chien est éligible si sa date de naissance est comprise entre min_birth (plus ancienne acceptée) et max_birth (plus récente acceptée)
    public function isDogEligible(array $course, ?string $birthDate): bool {
        if (!$birthDate) {
            return true;
        }

        $minBirth = $course['min_birth'] ?? null;
        $maxBirth = $course['max_birth'] ?? null;

        // Au-delà du seuil, min_birth est une valeur sentinelle "pas de limite d'âge maximum"
        $hasUpperLimit = $minBirth && $this->monthsSince($minBirth, new DateTime()) < self::NO_UPPER_LIMIT_MONTHS;

        if ($hasUpperLimit && $birthDate < $minBirth) {
            return false;
        }

        return !$maxBirth || $birthDate <= $maxBirth;
    }

    private function monthName(string $n): string {
        return ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'][(int) $n - 1];
    }

    private function monthsSince(string $date, DateTime $now): int {
        $diff = (new DateTime($date))->diff($now);
        return $diff->y * 12 + $diff->m;
    }

    private function formatMonths(int $months): string {
        if ($months < 12) {
            return $months . ' mois';
        }

        $years = intdiv($months, 12);
        return $years . ' an' . ($years > 1 ? 's' : '');
    }

    private function formatSchedule(string $date, string $start, string $end): string {
        $d = new DateTime($date);
        $days = ['Mon'=>'Lundi','Tue'=>'Mardi','Wed'=>'Mercredi','Thu'=>'Jeudi',
                 'Fri'=>'Vendredi','Sat'=>'Samedi','Sun'=>'Dimanche'];
        $day = $days[$d->format('D')] ?? $d->format('D');

        $duration = (new DateTime($start))->diff(new DateTime($end));
        $h = $duration->h;
        $m = $duration->i;
        $durationStr = $h > 0 ? "{$h}h" . ($m > 0 ? $m : '') : "{$m}min";

        $months = ['Jan'=>'janv.','Feb'=>'févr.','Mar'=>'mars','Apr'=>'avr.','May'=>'mai','Jun'=>'juin',
                   'Jul'=>'juil.','Aug'=>'août','Sep'=>'sept.','Oct'=>'oct.','Nov'=>'nov.','Dec'=>'déc.'];
        $month = $months[$d->format('M')] ?? $d->format('M');

        return $day . ' ' . $d->format('j') . ' ' . $month . ' ' . substr($start, 0, 5) . ' • ' . $durationStr;
    }

    private function slugify(string $title): string {
        $map = [
            'École du chiot' => 'chiot',
            'Éducation 6-12 mois' => '6-12',
            'Éducation 1-2 ans' => '1-2',
            'Éducation +2 ans' => '2+',
            'Socialisation' => 'socialisation',
            'Parcours sportifs' => 'sport',
            'Dressage' => 'dressage',
        ];

        foreach ($map as $keyword => $slug) {
            if (stripos($title, $keyword) !== false) return $slug;
        }

        // Fallback : nettoie le titre en slug
        return strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
    }
}
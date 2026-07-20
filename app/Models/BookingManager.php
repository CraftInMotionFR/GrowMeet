<?php

class BookingManager extends Manager {
    // Réservations à venir ou récentes pour le chien d'un membre
    public function findByUser(int $userId): array {
        $stmt = $this->pdo->prepare('
            SELECT c.id_course, ct.name AS title,
                   c.date, c.start_time, c.end_time,
                   b.status
            FROM booking b
            JOIN dog d ON d.id_dog = b.id_dog
            JOIN course c ON c.id_course = b.id_course
            JOIN course_type ct ON ct.id_course_type = c.id_course_type
            WHERE d.id_user = :id AND b.status != \'cancelled\'
            ORDER BY c.date ASC, c.start_time ASC
            LIMIT 10
        ');
        $stmt->execute(['id' => $userId]);
        $rows = $stmt->fetchAll();

        return array_map(fn($r) => [
            'id_course' => (int) $r['id_course'],
            'title' => $r['title'],
            'date' => $this->formatDate($r['date'], $r['start_time']),
            'status' => $r['status'],
        ], $rows);
    }

    // Prochain cours à venir (date >= aujourd'hui)
    public function findNextCourse(int $userId): ?array {
        $stmt = $this->pdo->prepare('
            SELECT c.id_course, ct.name AS title,
                   c.date, c.start_time, c.end_time,
                   u.first_name AS coach_first, u.last_name AS coach_last,
                   b.status
            FROM booking b
            JOIN dog d ON d.id_dog = b.id_dog
            JOIN course c ON c.id_course = b.id_course
            JOIN course_type ct ON ct.id_course_type = c.id_course_type
            JOIN users u ON u.id_user = c.id_user
            WHERE d.id_user = :id
              AND c.date >= CURDATE()
              AND b.status IN (\'confirmed\', \'pending\')
            ORDER BY c.date ASC, c.start_time ASC
            LIMIT 1
        ');
        $stmt->execute(['id' => $userId]);
        $r = $stmt->fetch();

        if (!$r) return null;

        $date = new DateTime($r['date']);

        return [
            'id_course' => (int) $r['id_course'],
            'title' => $r['title'],
            'day' => $this->translateDay($date->format('D')),
            'date_num' => $date->format('d'),
            'month' => $this->translateMonth($date->format('M')),
            'time' => substr($r['start_time'], 0, 5) . ' - ' . substr($r['end_time'], 0, 5),
            'coach' => $r['coach_first'] . ' ' . $r['coach_last'],
            'status' => $r['status'],
        ];
    }

    // Prochaines séances à venir (confirmées ou en attente), avec le nom du chien concerné
    public function findUpcoming(int $userId, int $limit = 2): array {
        $stmt = $this->pdo->prepare('
            SELECT c.id_course, ct.name AS title,
                   c.date, c.start_time, c.end_time,
                   u.first_name AS coach_first, u.last_name AS coach_last,
                   d.name AS dog_name,
                   b.status
            FROM booking b
            JOIN dog d ON d.id_dog = b.id_dog
            JOIN course c ON c.id_course = b.id_course
            JOIN course_type ct ON ct.id_course_type = c.id_course_type
            JOIN users u ON u.id_user = c.id_user
            WHERE d.id_user = :id
              AND c.date >= CURDATE()
              AND b.status IN (\'confirmed\', \'pending\')
            ORDER BY c.date ASC, c.start_time ASC
            LIMIT ' . (int) $limit . '
        ');
        $stmt->execute(['id' => $userId]);
        $rows = $stmt->fetchAll();

        return array_map(function ($r) {
            $d = new DateTime($r['date']);

            return [
                'id_course' => (int) $r['id_course'],
                'title' => $r['title'],
                'dog' => $r['dog_name'],
                'day_label' => $this->translateDay($d->format('D')) . ' ' . $d->format('d') . ' ' . strtolower($this->translateMonth($d->format('M'))),
                'time_range' => substr($r['start_time'], 0, 5) . ' - ' . substr($r['end_time'], 0, 5),
                'coach' => $r['coach_first'] . ' ' . $r['coach_last'],
                'status' => $r['status'],
            ];
        }, $rows);
    }

    // Stats globales du membre
    public function getStats(int $userId): array {
        $stmt = $this->pdo->prepare('
            SELECT
                COUNT(b.id_course) AS bookings_count,
                SUM(b.status = \'confirmed\') AS sessions_done,
                COUNT(DISTINCT CASE WHEN b.status IN (\'confirmed\', \'pending\') AND c.date >= CURDATE()
                                    THEN c.id_course END) AS upcoming_sessions,
                SUM(
                    CASE WHEN b.status = \'confirmed\'
                    THEN TIME_TO_SEC(TIMEDIFF(c.end_time, c.start_time)) / 3600
                    ELSE 0 END
                ) AS training_hours
            FROM booking b
            JOIN dog d ON d.id_dog = b.id_dog
            JOIN course c ON c.id_course = b.id_course
            WHERE d.id_user = :id
        ');
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        return [
            'bookings_count' => (int) ($row['bookings_count'] ?? 0),
            'sessions_done' => (int) ($row['sessions_done'] ?? 0),
            'upcoming_sessions' => (int) ($row['upcoming_sessions'] ?? 0),
            'training_hours' => (int) ($row['training_hours'] ?? 0),
            'badges_count' => 0, // TODO: table badges
        ];
    }

    private function formatDate(string $date, string $time): string {
        $d = new DateTime($date);
        $days = ['Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mer','Thu'=>'Jeu','Fri'=>'Ven','Sat'=>'Sam','Sun'=>'Dim'];
        $months = ['Jan'=>'jan','Feb'=>'fév','Mar'=>'mar','Apr'=>'avr','May'=>'mai','Jun'=>'juin',
                   'Jul'=>'juil','Aug'=>'août','Sep'=>'sep','Oct'=>'oct','Nov'=>'nov','Dec'=>'déc'];

        $day = $days[$d->format('D')] ?? $d->format('D');
        $month = $months[$d->format('M')] ?? $d->format('M');

        return $day . ' ' . $d->format('d') . ' ' . $month . ' • ' . substr($time, 0, 5);
    }

    private function translateDay(string $en): string {
        return ['Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mer','Thu'=>'Jeu',
                'Fri'=>'Ven','Sat'=>'Sam','Sun'=>'Dim'][$en] ?? $en;
    }

    private function translateMonth(string $en): string {
        return ['Jan'=>'Jan','Feb'=>'Fév','Mar'=>'Mar','Apr'=>'Avr','May'=>'Mai','Jun'=>'Juin',
                'Jul'=>'Juil','Aug'=>'Août','Sep'=>'Sep','Oct'=>'Oct','Nov'=>'Nov','Dec'=>'Déc'][$en] ?? $en;
    }

    // Vérifie si ce chien est déjà inscrit à ce cours
    public function isAlreadyBooked(int $dogId, int $courseId): bool {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*)
            FROM booking
            WHERE id_dog = :dog_id
              AND id_course = :course_id
              AND status != \'cancelled\'
        ');
        $stmt->execute(['dog_id' => $dogId, 'course_id' => $courseId]);
        return (bool) $stmt->fetchColumn();
    }

    // Inscrit un chien du membre à un cours, retourne le statut créé (pending/confirmed ou waiting)
    public function create(int $userId, int $dogId, int $courseId, bool $waiting = false): string {
        // Le chien doit appartenir au membre
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM dog WHERE id_dog = :dog AND id_user = :user');
        $stmt->execute(['dog' => $dogId, 'user' => $userId]);

        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('Chien introuvable pour ce compte.');
        }

        $status = $waiting ? 'waiting' : 'pending';

        $stmt = $this->pdo->prepare('
            INSERT INTO booking (id_dog, id_course, status)
            VALUES (:id_dog, :id_course, :status)
            ON DUPLICATE KEY UPDATE status = VALUES(status), booking_date = NOW()
        ');
        $stmt->execute([
            'id_dog' => $dogId,
            'id_course' => $courseId,
            'status' => $status,
        ]);

        $this->confirmIfMinReached($courseId);

        // Statut final : la réservation a pu être confirmée si le minimum de participants est atteint
        $stmt = $this->pdo->prepare('SELECT status FROM booking WHERE id_dog = :id_dog AND id_course = :id_course');
        $stmt->execute(['id_dog' => $dogId, 'id_course' => $courseId]);

        return $stmt->fetchColumn() ?: $status;
    }

    // Passe toutes les inscriptions "pending" du cours à "confirmed" dès que le minimum de participants est atteint
    private function confirmIfMinReached(int $courseId): void {
        // Comptage séparé : MySQL interdit une sous-requête sur la table en cours de mise à jour
        $stmt = $this->pdo->prepare('
            SELECT c.min_participants,
                   (SELECT COUNT(*) FROM booking b
                    WHERE b.id_course = c.id_course AND b.status IN (\'pending\', \'confirmed\')) AS active
            FROM course c
            WHERE c.id_course = :course_id
        ');
        $stmt->execute(['course_id' => $courseId]);
        $row = $stmt->fetch();

        if (!$row || (int) $row['active'] < (int) $row['min_participants']) {
            return;
        }

        $stmt = $this->pdo->prepare('
            UPDATE booking SET status = \'confirmed\'
            WHERE id_course = :course_id AND status = \'pending\'
        ');
        $stmt->execute(['course_id' => $courseId]);
    }

    // Inscriptions actives (non annulées) d'un cours, avec chien et propriétaire, pour la gestion côté coach
    public function findByCourse(int $courseId): array {
        $stmt = $this->pdo->prepare('
            SELECT b.id_dog, b.status, d.name AS dog_name,
                   u.first_name, u.last_name
            FROM booking b
            JOIN dog d ON d.id_dog = b.id_dog
            JOIN users u ON u.id_user = d.id_user
            WHERE b.id_course = :course_id AND b.status != \'cancelled\'
            ORDER BY b.booking_date ASC
        ');
        $stmt->execute(['course_id' => $courseId]);

        return $stmt->fetchAll();
    }

    // Annule toutes les inscriptions actives d'un chien (libère les places et fait monter la liste d'attente)
    public function cancelAllForDog(int $dogId): void {
        $stmt = $this->pdo->prepare('SELECT id_course FROM booking WHERE id_dog = :dog AND status != \'cancelled\'');
        $stmt->execute(['dog' => $dogId]);

        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $courseId) {
            $this->cancelByDog($dogId, (int) $courseId);
        }
    }

    // Annulation par le staff de l'inscription d'un chien donné
    public function cancelByDog(int $dogId, int $courseId): bool {
        $stmt = $this->pdo->prepare('
            SELECT status FROM booking
            WHERE id_dog = :dog AND id_course = :course AND status != \'cancelled\'
        ');
        $stmt->execute(['dog' => $dogId, 'course' => $courseId]);
        $status = $stmt->fetchColumn();

        if ($status === false) {
            return false;
        }

        $stmt = $this->pdo->prepare('
            UPDATE booking SET status = \'cancelled\'
            WHERE id_dog = :dog AND id_course = :course
        ');
        $stmt->execute(['dog' => $dogId, 'course' => $courseId]);

        if ($status !== 'waiting') {
            $this->promoteNextWaiting($courseId);
        }

        return true;
    }

    // Désinscrit le chien du membre d'un cours. Retourne false s'il n'y avait rien à annuler.
    public function cancel(int $userId, int $courseId, ?int $dogId = null): bool {
        // Un chien précis : on vérifie qu'il appartient bien au membre avant d'annuler
        if ($dogId !== null) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM dog WHERE id_dog = :dog AND id_user = :user');
            $stmt->execute(['dog' => $dogId, 'user' => $userId]);

            return $stmt->fetchColumn() ? $this->cancelByDog($dogId, $courseId) : false;
        }

        $stmt = $this->pdo->prepare('
            SELECT b.status
            FROM booking b
            JOIN dog d ON d.id_dog = b.id_dog
            WHERE d.id_user = :user_id AND b.id_course = :course_id AND b.status != \'cancelled\'
            LIMIT 1
        ');
        $stmt->execute(['user_id' => $userId, 'course_id' => $courseId]);
        $status = $stmt->fetchColumn();

        if ($status === false) {
            return false;
        }

        $stmt = $this->pdo->prepare('
            UPDATE booking b
            JOIN dog d ON d.id_dog = b.id_dog
            SET b.status = \'cancelled\'
            WHERE d.id_user = :user_id AND b.id_course = :course_id
        ');
        $stmt->execute(['user_id' => $userId, 'course_id' => $courseId]);

        // La place ne se libère vraiment que si le booking annulé occupait une place réelle (pas juste en liste d'attente)
        if ($status !== 'waiting') {
            $this->promoteNextWaiting($courseId);
        }

        return true;
    }

    // Fait passer le premier chien en liste d'attente sur la place qui vient de se libérer
    private function promoteNextWaiting(int $courseId): void {
        $stmt = $this->pdo->prepare('
            SELECT id_dog FROM booking
            WHERE id_course = :course_id AND status = \'waiting\'
            ORDER BY booking_date ASC
            LIMIT 1
        ');
        $stmt->execute(['course_id' => $courseId]);
        $dogId = $stmt->fetchColumn();

        if ($dogId) {
            $stmt = $this->pdo->prepare('
                UPDATE booking SET status = \'pending\'
                WHERE id_dog = :id_dog AND id_course = :course_id
            ');
            $stmt->execute(['id_dog' => $dogId, 'course_id' => $courseId]);
            $this->confirmIfMinReached($courseId);
        }
    }
}
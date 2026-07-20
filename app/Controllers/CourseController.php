<?php

class CourseController extends Controller {
    // Catalogue (GET /courses)
    public function index(): void {
        $this->requireDog();

        $courses = (new CourseManager())->findAll();

        $this->render('courses/index', [
            'page_title' => 'Cours',
            'current_page' => 'courses',
            'courses' => $courses,
            'dogs' => $this->memberDogs(),
            'flash' => $this->flashFromStatus($_GET['status'] ?? null),
        ]);
    }

    // Détail d'un cours (GET /courses/{id})
    public function show(string $id): void {
        $this->requireDog();

        $course = (new CourseManager())->findById((int) $id);

        if (!$course) {
            $this->notFound(
                "Le cours que vous recherchez n'existe pas ou n'est plus disponible.",
                "Cours non trouvé",
                '/courses',
                'Retour au catalogue'
            );
        }

        $courseManager = new CourseManager();
        $eligibleDogs = [];
        $ineligibleDogs = [];
        foreach ($this->memberDogs() as $dog) {
            if ($courseManager->isDogEligible($course, $dog['birth_date'])) {
                $eligibleDogs[] = $dog;
            } else {
                $ineligibleDogs[] = $dog;
            }
        }

        // Chiens du membre déjà inscrits à ce cours
        $bookedIds = array_map('intval', array_column((new BookingManager())->findByCourse((int) $id), 'id_dog'));
        $bookedDogs = array_values(array_filter($eligibleDogs, fn($d) => in_array((int) $d['id_dog'], $bookedIds, true)));
        $bookableDogs = array_values(array_filter($eligibleDogs, fn($d) => !in_array((int) $d['id_dog'], $bookedIds, true)));

        $this->render('courses/show', [
            'page_title' => $course['title'],
            'current_page' => 'courses',
            'course' => $course,
            'dogs' => $bookableDogs,
            'eligibleDogs' => $eligibleDogs,
            'bookedDogs' => $bookedDogs,
            'ineligibleDogs' => $ineligibleDogs,
            'isMember' => ($_SESSION['user']['role'] ?? null) === 'member',
            'flash' => $this->flashFromStatus($_GET['status'] ?? null),
        ]);
    }

    // Chiens du membre connecté (vide pour un coach ou un admin)
    private function memberDogs(): array {
        if (($_SESSION['user']['role'] ?? null) !== 'member') {
            return [];
        }

        return (new DogManager())->findAllByUser($_SESSION['user']['id']);
    }

    // Inscription à un cours (POST /courses/{id}/join)
    public function join(string $id): void {
        $this->requireDog();

        $courseId = (int) $id;
        $userId = $_SESSION['user']['id'];

        $course = (new CourseManager())->findById($courseId);

        if (!$course) {
            $this->notFound();
        }

        $bookingManager = new BookingManager();

        // Chien choisi ; à défaut (un seul chien), le premier du membre
        $dogId = (int) ($_POST['dog_id'] ?? 0);
        if ($dogId === 0) {
            $dogId = (int) ((new DogManager())->findByUser($userId)['id_dog'] ?? 0);
        }

        // Contrôle d'âge côté serveur : le chien doit correspondre à la tranche du cours
        $dog = $dogId ? (new DogManager())->findById($dogId, $userId) : null;
        if ($dog && !(new CourseManager())->isDogEligible($course, $dog['birth_date'])) {
            $this->redirect("/courses/{$courseId}?status=dog-not-eligible");
        }

        if ($dogId && $bookingManager->isAlreadyBooked($dogId, $courseId)) {
            $this->redirect('/courses?status=already-booked');
        }

        try {
            $status = $bookingManager->create($userId, $dogId, $courseId, $course['places'] <= 0);
        } catch (RuntimeException $e) {
            // PDOException étend RuntimeException : on ne masque pas une erreur SQL en "pas de chien"
            if ($e instanceof PDOException) {
                throw $e;
            }
            $this->redirect('/courses?status=no-dog');
        }

        if ($status === 'waiting') {
            $this->redirect('/courses?status=waiting');
        }

        $this->redirect($status === 'confirmed' ? '/dashboard?status=booked' : '/dashboard?status=booked-pending');
    }

    // Désinscription d'un cours (POST /courses/{id}/cancel)
    public function cancel(string $id): void {
        $courseId = (int) $id;
        $userId = $_SESSION['user']['id'];

        $dogId = isset($_POST['dog_id']) ? (int) $_POST['dog_id'] : null;
        $cancelled = (new BookingManager())->cancel($userId, $courseId, $dogId);

        // Depuis la fiche d'un cours (dog_id fourni), on y revient ; sinon tableau de bord
        if ($dogId !== null) {
            $this->redirect("/courses/{$courseId}" . ($cancelled ? '?status=booking-cancelled' : ''));
        }

        $this->redirect($cancelled ? '/dashboard?status=booking-cancelled' : '/dashboard');
    }

    // Liste des séances (GET /admin/sessions) - accessible aux admins et aux coachs
    public function adminIndex(): void {
        $this->requireStaff();

        $this->render('admin/sessions/index', [
            'page_title' => 'Séances de cours',
            'current_page' => 'admin-sessions',
            'sessions' => (new CourseManager())->findAllForAdmin(),
            'flash' => $this->flashFromStatus($_GET['status'] ?? null),
        ]);
    }

    // Formulaire de création (GET /admin/sessions/create)
    public function adminCreate(): void {
        $this->requireStaff();

        $this->render('admin/sessions/create', array_merge([
            'page_title' => 'Nouvelle séance',
            'current_page' => 'admin-sessions',
            'errors' => [],
            'courseTypes' => (new CourseTypeManager())->findAll(),
        ], $this->coachFieldData()));
    }

    // Traitement de la création (POST /admin/sessions)
    public function adminStore(): void {
        $this->requireStaff();

        // Un coach ne peut créer une séance que pour lui-même, quoi qu'il envoie dans le formulaire
        if ($this->isCoach()) {
            $_POST['id_user'] = $_SESSION['user']['id'];
        }

        [$data, $errors] = $this->validateSession($_POST);

        if (!empty($errors)) {
            $this->render('admin/sessions/create', array_merge([
                'page_title' => 'Nouvelle séance',
                'current_page' => 'admin-sessions',
                'errors' => $errors,
                'old' => $_POST,
                'courseTypes' => (new CourseTypeManager())->findAll(),
            ], $this->coachFieldData()));
            return;
        }

        (new CourseManager())->create($data);

        $this->redirect('/admin/sessions?status=session-created');
    }

    // Formulaire d'édition (GET /admin/sessions/{id}/edit)
    public function adminEdit(string $id): void {
        $this->requireStaff();

        $session = (new CourseManager())->findRawById((int) $id);

        if (!$session) {
            $this->notFound();
        }
        $this->requireOwnSession($session);

        $this->render('admin/sessions/edit', array_merge([
            'page_title' => 'Modifier la séance',
            'current_page' => 'admin-sessions',
            'errors' => [],
            'session' => $session,
            'old' => $session,
            'courseTypes' => (new CourseTypeManager())->findAll(),
        ], $this->coachFieldData()));
    }

    // Traitement de la modification (POST /admin/sessions/{id}/update)
    public function adminUpdate(string $id): void {
        $this->requireStaff();

        $courseManager = new CourseManager();
        $session = $courseManager->findRawById((int) $id);

        if (!$session) {
            $this->notFound();
        }
        $this->requireOwnSession($session);

        // Un coach ne peut pas réassigner sa séance à quelqu'un d'autre
        if ($this->isCoach()) {
            $_POST['id_user'] = $_SESSION['user']['id'];
        }

        [$data, $errors] = $this->validateSession($_POST);

        if (!empty($errors)) {
            $this->render('admin/sessions/edit', array_merge([
                'page_title' => 'Modifier la séance',
                'current_page' => 'admin-sessions',
                'errors' => $errors,
                'session' => $session,
                'old' => $_POST,
                'courseTypes' => (new CourseTypeManager())->findAll(),
            ], $this->coachFieldData()));
            return;
        }

        $courseManager->update((int) $id, $data);

        $this->redirect('/admin/sessions?status=session-updated');
    }

    // Suppression (POST /admin/sessions/{id}/delete)
    public function adminDelete(string $id): void {
        $this->requireStaff();

        $courseManager = new CourseManager();
        $session = $courseManager->findRawById((int) $id);

        if (!$session) {
            $this->notFound();
        }
        $this->requireOwnSession($session);

        $courseManager->delete((int) $id);

        $this->redirect('/admin/sessions?status=session-deleted');
    }

    // Inscrits d'une séance (GET /admin/sessions/{id}/bookings)
    public function adminBookings(string $id): void {
        $this->requireStaff();

        $session = (new CourseManager())->findById((int) $id);
        $raw = (new CourseManager())->findRawById((int) $id);

        if (!$session || !$raw) {
            $this->notFound();
        }
        $this->requireOwnSession($raw);

        $this->render('admin/sessions/bookings', [
            'page_title' => 'Inscrits',
            'current_page' => 'admin-sessions',
            'session' => $session,
            'bookings' => (new BookingManager())->findByCourse((int) $id),
            'flash' => $this->flashFromStatus($_GET['status'] ?? null),
        ]);
    }

    // Annulation d'une inscription par le staff (POST /admin/sessions/{id}/bookings/{dogId}/cancel)
    public function adminCancelBooking(string $id, string $dogId): void {
        $this->requireStaff();

        $raw = (new CourseManager())->findRawById((int) $id);

        if (!$raw) {
            $this->notFound();
        }
        $this->requireOwnSession($raw);

        $cancelled = (new BookingManager())->cancelByDog((int) $dogId, (int) $id);

        $this->redirect("/admin/sessions/{$id}/bookings" . ($cancelled ? '?status=booking-cancelled-staff' : ''));
    }

    private function isCoach(): bool {
        return ($_SESSION['user']['role'] ?? null) === 'coach';
    }

    // Un coach ne peut agir que sur les séances qui lui sont assignées ; un admin peut tout gérer
    private function requireOwnSession(array $session): void {
        if ($this->isCoach() && (int) $session['id_user'] !== (int) $_SESSION['user']['id']) {
            $this->redirect('/admin/sessions?status=not-your-session');
        }
    }

    // Données du formulaire coach : select complet pour un admin, coach imposé (lui-même) sinon
    private function coachFieldData(): array {
        if ($this->isCoach()) {
            return ['isCoach' => true];
        }

        return ['isCoach' => false, 'coaches' => (new CoachManager())->findAll()];
    }

    private function validateSession(array $input): array {
        $errors = [];
        $idCourseType = $input['id_course_type'] ?? '';
        $idUser = $input['id_user'] ?? '';
        $date = $input['date'] ?? '';
        $startTime = $input['start_time'] ?? '';
        $endTime = $input['end_time'] ?? '';
        $minParticipants = $input['min_participants'] ?? '';
        $maxParticipants = $input['max_participants'] ?? '';
        $status = $input['status'] ?? 'open';

        if (empty($idCourseType)) {
            $errors[] = 'Le type de cours est obligatoire.';
        }
        if (empty($idUser)) {
            $errors[] = 'Le coach est obligatoire.';
        }
        if (empty($date)) {
            $errors[] = 'La date est obligatoire.';
        }
        if (empty($startTime) || empty($endTime)) {
            $errors[] = "Les horaires de début et de fin sont obligatoires.";
        } elseif ($startTime >= $endTime) {
            $errors[] = "L'heure de fin doit être après l'heure de début.";
        }
        if (!is_numeric($minParticipants) || !is_numeric($maxParticipants) || (int) $minParticipants > (int) $maxParticipants) {
            $errors[] = 'Le nombre de places minimum doit être inférieur ou égal au maximum.';
        }
        if (!in_array($status, ['open', 'full', 'cancelled', 'completed'], true)) {
            $errors[] = 'Statut invalide.';
        }

        if (!empty($errors)) {
            return [[], $errors];
        }

        return [[
            'id_course_type' => (int) $idCourseType,
            'id_user' => (int) $idUser,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'min_participants' => (int) $minParticipants,
            'max_participants' => (int) $maxParticipants,
            'status' => $status,
        ], []];
    }
}
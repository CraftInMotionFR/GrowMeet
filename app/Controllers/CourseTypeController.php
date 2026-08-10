<?php

class CourseTypeController extends Controller {
    private const NO_UPPER_LIMIT_MONTHS = 240;

    // Liste des types de cours (GET /admin/course-types)
    public function index(): void {
        $manager = new CourseTypeManager();
        $courseTypes = array_map(fn($ct) => $ct + [
            'age_label' => $manager->formatAgeRange($ct['min_age'], $ct['max_age']),
        ], $manager->findAll());

        $this->render('admin/course-types/index', [
            'page_title' => 'Types de cours',
            'current_page' => 'admin-course-types',
            'courseTypes' => $courseTypes,
            'flash' => $this->flashFromStatus($_GET['status'] ?? null),
        ]);
    }

    // Formulaire de création (GET /admin/course-types/create)
    public function create(): void {
        $this->render('admin/course-types/create', [
            'page_title' => 'Nouveau type de cours',
            'current_page' => 'admin-course-types',
            'errors' => [],
        ]);
    }

    // Traitement de la création (POST /admin/course-types)
    public function store(): void {
        [$data, $errors] = $this->validate($_POST);

        if (!empty($errors)) {
            $this->render('admin/course-types/create', [
                'page_title' => 'Nouveau type de cours',
                'current_page' => 'admin-course-types',
                'errors' => $errors,
                'old' => $_POST,
            ]);
            return;
        }

        (new CourseTypeManager())->create($data, $_SESSION['user']['id']);

        $this->redirect('/admin/course-types?status=course-type-created');
    }

    // Formulaire d'édition (GET /admin/course-types/{id}/edit)
    public function edit(string $id): void {
        $courseType = (new CourseTypeManager())->findById((int) $id);

        if (!$courseType) {
            $this->notFound();
        }

        $this->render('admin/course-types/edit', [
            'page_title' => 'Modifier le type de cours',
            'current_page' => 'admin-course-types',
            'errors' => [],
            'courseType' => $courseType,
            'old' => [
                'name' => $courseType['name'],
                'description' => $courseType['description'],
                'age_min_months' => $courseType['max_age'] ? $this->monthsSince($courseType['max_age']) : '',
                'age_max_months' => $this->monthsSince($courseType['min_age']) >= self::NO_UPPER_LIMIT_MONTHS ? '' : $this->monthsSince($courseType['min_age']),
            ],
        ]);
    }

    private function monthsSince(string $date): int {
        $diff = (new DateTime($date))->diff(new DateTime());
        return $diff->y * 12 + $diff->m;
    }

    // Traitement de la modification (POST /admin/course-types/{id}/update)
    public function update(string $id): void {
        $courseTypeManager = new CourseTypeManager();
        $courseType = $courseTypeManager->findById((int) $id);

        if (!$courseType) {
            $this->notFound();
        }

        [$data, $errors] = $this->validate($_POST);

        if (!empty($errors)) {
            $this->render('admin/course-types/edit', [
                'page_title' => 'Modifier le type de cours',
                'current_page' => 'admin-course-types',
                'errors' => $errors,
                'courseType' => $courseType,
                'old' => $_POST,
            ]);
            return;
        }

        $courseTypeManager->update((int) $id, $data);

        $this->redirect('/admin/course-types?status=course-type-updated');
    }

    // Suppression (POST /admin/course-types/{id}/delete)
    public function delete(string $id): void {
        try {
            (new CourseTypeManager())->delete((int) $id);
            $this->redirect('/admin/course-types?status=course-type-deleted');
        } catch (PDOException $e) {
            $this->redirect('/admin/course-types?status=type-in-use');
        }
    }

    // Valide le formulaire et convertit les âges en mois vers les dates de naissance seuils attendues en base
    private function validate(array $input): array {
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $ageMinMonths = $input['age_min_months'] ?? '';
        $ageMaxMonths = $input['age_max_months'] ?? '';
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Le nom du type de cours est obligatoire.';
        }
        if ($ageMinMonths !== '' && !is_numeric($ageMinMonths)) {
            $errors[] = "L'âge minimum doit être un nombre de mois.";
        }
        if ($ageMaxMonths !== '' && !is_numeric($ageMaxMonths)) {
            $errors[] = "L'âge maximum doit être un nombre de mois.";
        }
        if ($ageMinMonths !== '' && $ageMaxMonths !== '' && (float) $ageMinMonths >= (float) $ageMaxMonths) {
            $errors[] = "L'âge minimum doit être inférieur à l'âge maximum.";
        }

        if (!empty($errors)) {
            return [[], $errors];
        }

        // max_age (colonne) = date de naissance la plus récente acceptée = borne d'âge MIN
        $maxAge = $ageMinMonths !== '' ? date('Y-m-d', strtotime('-' . (int) $ageMinMonths . ' months')) : null;
        // min_age (colonne) = date de naissance la plus ancienne acceptée = borne d'âge MAX
        $minAgeMonths = $ageMaxMonths !== '' ? (int) $ageMaxMonths : self::NO_UPPER_LIMIT_MONTHS;
        $minAge = date('Y-m-d', strtotime('-' . $minAgeMonths . ' months'));

        return [[
            'name' => $name,
            'description' => $description,
            'min_age' => $minAge,
            'max_age' => $maxAge,
        ], []];
    }
}

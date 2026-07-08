<?php

class DogController extends Controller {
    // Liste des chiens en vue carte (GET /dogs)
    public function index(): void {
        $this->requireMember();

        $this->render('dogs/index', [
            'page_title' => 'Mes chiens',
            'current_page' => 'dogs',
            'dogs' => (new DogManager())->findAllByUser($_SESSION['user']['id']),
            'flash' => $this->flashFromStatus($_GET['status'] ?? null),
        ]);
    }

    // Formulaire d'ajout d'un chien (GET /dogs/create)
    public function create(): void {
        $this->requireMember();

        $this->render('dogs/create', [
            'page_title' => 'Ajouter mon chien',
            'current_page' => 'dogs',
            'errors' => [],
            'breeds' => (new DogManager())->findAllBreeds(),
        ]);
    }

    // Traitement de l'ajout d'un chien (POST /dogs)
    public function store(): void {
        $this->requireMember();

        $userId = $_SESSION['user']['id'];
        $input = $this->readInput();
        $errors = $this->validate($input);

        if (!empty($errors)) {
            $this->render('dogs/create', [
                'page_title' => 'Ajouter mon chien',
                'current_page' => 'dogs',
                'errors' => $errors,
                'old' => $input,
                'breeds' => (new DogManager())->findAllBreeds(),
            ]);
            return;
        }

        $input['image'] = $this->storeDogPhoto($_FILES['dog_photo'] ?? null);

        (new DogManager())->createDog($userId, $input);

        $this->redirect('/dogs?status=dog-added');
    }

    // Formulaire de modification d'un chien (GET /dogs/{id}/edit)
    public function edit(string $id): void {
        $this->requireMember();

        $dog = $this->findOwnDog((int) $id);

        $this->render('dogs/create', [
            'page_title' => 'Modifier ' . $dog['name'],
            'current_page' => 'dogs',
            'errors' => [],
            'dog' => $dog,
            'old' => [
                'dog_name' => $dog['name'],
                'dog_breed' => $dog['breed'] ?? '',
                'gender' => $dog['gender'],
                'birth_date' => $dog['birth_date'] ?? '',
            ],
            'breeds' => (new DogManager())->findAllBreeds(),
        ]);
    }

    // Traitement de la modification (POST /dogs/{id}/update)
    public function update(string $id): void {
        $this->requireMember();

        $dog = $this->findOwnDog((int) $id);
        $input = $this->readInput();
        $errors = $this->validate($input);

        if (!empty($errors)) {
            $this->render('dogs/create', [
                'page_title' => 'Modifier ' . $dog['name'],
                'current_page' => 'dogs',
                'errors' => $errors,
                'dog' => $dog,
                'old' => $input,
                'breeds' => (new DogManager())->findAllBreeds(),
            ]);
            return;
        }

        // Sans nouvelle photo valide, on garde l'ancienne
        $newImage = $this->storeDogPhoto($_FILES['dog_photo'] ?? null);
        $input['image'] = $newImage ?? $dog['image'];

        (new DogManager())->updateDog((int) $id, $_SESSION['user']['id'], $input);

        if ($newImage !== null) {
            $this->deleteDogPhoto($dog['image']);
        }

        $this->redirect('/dogs?status=dog-updated');
    }

    // Suppression d'un chien (POST /dogs/{id}/delete)
    public function delete(string $id): void {
        $this->requireMember();

        $dog = $this->findOwnDog((int) $id);

        (new DogManager())->deleteDog((int) $id, $_SESSION['user']['id']);
        $this->deleteDogPhoto($dog['image']);

        $this->redirect('/dogs?status=dog-deleted');
    }

    private function requireMember(): void {
        if (($_SESSION['user']['role'] ?? null) !== 'member') {
            $this->redirect('/dashboard?status=members-only');
        }
    }

    // Chien appartenant au membre connecté, 404 sinon
    private function findOwnDog(int $dogId): array {
        $dog = (new DogManager())->findById($dogId, $_SESSION['user']['id']);

        if (!$dog) {
            $this->notFound();
        }

        return $dog;
    }

    private function readInput(): array {
        return [
            'dog_name' => trim($_POST['dog_name'] ?? ''),
            'dog_breed' => trim($_POST['dog_breed'] ?? ''),
            'gender' => $_POST['gender'] ?? '',
            'birth_date' => $_POST['birth_date'] ?? '',
        ];
    }

    private function validate(array $input): array {
        $errors = [];

        if (empty($input['dog_name'])) {
            $errors[] = 'Le nom du chien est obligatoire.';
        }
        if (!in_array($input['gender'], ['male', 'female', 'unknown'], true)) {
            $errors[] = 'Veuillez indiquer le sexe du chien.';
        }
        if (empty($input['birth_date'])) {
            $errors[] = 'La date de naissance est obligatoire.';
        } elseif ($input['birth_date'] > date('Y-m-d')) {
            $errors[] = 'La date de naissance ne peut pas être dans le futur.';
        }

        return $errors;
    }

    // Enregistre la photo envoyée dans public/images/dogs et retourne son nom de fichier (ou null)
    private function storeDogPhoto(?array $file): ?string {
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        $mime = mime_content_type($file['tmp_name']);

        if (!isset($allowed[$mime]) || $file['size'] > 5 * 1024 * 1024) {
            return null;
        }

        $dir = __DIR__ . '/../../public/images/dogs';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'dog_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
        move_uploaded_file($file['tmp_name'], $dir . '/' . $filename);

        return 'dogs/' . $filename;
    }

    // Supprime une photo uploadée (uniquement dans public/images/dogs, jamais les images du site)
    private function deleteDogPhoto(?string $image): void {
        if (!$image || !preg_match('#^dogs/dog_[a-f0-9]+\.(jpg|png)$#', $image)) {
            return;
        }

        $path = __DIR__ . '/../../public/images/' . $image;
        if (is_file($path)) {
            unlink($path);
        }
    }
}

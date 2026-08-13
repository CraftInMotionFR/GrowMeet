<?php

class CoachController extends Controller {
    // Liste des coachs (GET /admin/coaches)
    public function index(): void {
        $this->requireAdmin();

        $this->render('admin/coaches/index', [
            'page_title' => 'Coachs',
            'current_page' => 'admin-coaches',
            'coaches' => (new CoachManager())->findAll(),
            'flash' => $this->flashFromStatus($_GET['status'] ?? null),
        ]);
    }

    // Formulaire de création (GET /admin/coaches/create)
    public function create(): void {
        $this->requireAdmin();

        $this->render('admin/coaches/create', [
            'page_title' => 'Nouveau coach',
            'current_page' => 'admin-coaches',
            'errors' => [],
        ]);
    }

    // Traitement de la création (POST /admin/coaches)
    public function store(): void {
        $this->requireAdmin();

        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $errors = [];

        if (empty($firstname) || empty($lastname)) {
            $errors[] = 'Le prénom et le nom sont obligatoires.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresse e-mail invalide.';
        }
        if ($passwordError = $this->validatePasswordStrength($password)) {
            $errors[] = $passwordError;
        }

        if (empty($errors)) {
            $userManager = new UserManager();

            if ($userManager->findByEmail($email)) {
                $errors[] = 'Cette adresse e-mail est déjà utilisée.';
            } else {
                $userManager->createCoach([
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                $this->redirect('/admin/coaches?status=coach-created');
            }
        }

        $this->render('admin/coaches/create', [
            'page_title' => 'Nouveau coach',
            'current_page' => 'admin-coaches',
            'errors' => $errors,
            'old' => compact('firstname', 'lastname', 'email'),
        ]);
    }

    // Suppression (POST /admin/coaches/{id}/delete)
    public function delete(string $id): void {
        $this->requireAdmin();

        try {
            (new CoachManager())->delete((int) $id);
            $this->redirect('/admin/coaches?status=coach-deleted');
        } catch (PDOException $e) {
            $this->redirect('/admin/coaches?status=coach-in-use');
        }
    }
}

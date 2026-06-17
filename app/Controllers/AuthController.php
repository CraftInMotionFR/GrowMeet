<?php

class AuthController extends Controller {
    // Formulaire de connexion (GET /login)
    public function loginForm(): void {
        // Redirige vers son espace si déjà connecté
        if (isset($_SESSION['user'])) {
            $this->redirect($this->homeUrl());
        }

        $this->render('auth/login', [
            'page_title' => 'Connexion',
            'errors' => [],
        ]);
    }

    // Traitement connexion (POST /login)
    public function login(): void {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $errors = [];

        if (empty($email) || empty($password)) {
            $errors[] = 'Veuillez remplir tous les champs.';
        }

        if (empty($errors)) {
            try {
                $user = (new UserManager())->authenticate($email, $password);

                if ($user) {
                    // Détermination du rôle
                    $role = 'member';
                    $stmt = $this->pdo ?? null; // on passe par le Manager
                    // On vérifie le rôle via un second manager si besoin, mais pour l'instant on stocke member par défaut
                    $role = (new UserManager())->getRole($user['id_user']);

                    $_SESSION['user'] = [
                        'id' => $user['id_user'],
                        'firstname' => $user['first_name'],
                        'lastname' => $user['last_name'],
                        'email' => $user['email'],
                        'role' => $role,
                    ];

                    $this->redirect($this->homeUrl());
                }

                $errors[] = 'Email ou mot de passe incorrect.';

            } catch (PDOException $e) {
                $errors[] = 'Une erreur est survenue, veuillez réessayer.';
            }
        }

        $this->render('auth/login', [
            'page_title' => 'Connexion',
            'errors' => $errors,
            'old_email' => $email,
        ]);
    }

    // Formulaire d'inscription (GET /register)
    public function registerForm(): void {
        if (isset($_SESSION['user'])) {
            $this->redirect($this->homeUrl());
        }

        $this->render('auth/register', [
            'page_title' => 'Inscription',
            'errors' => [],
        ]);
    }

    // Traitement inscription - compte uniquement, le chien est ajouté ensuite via /dogs/create (POST /register)
    public function register(): void {
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';
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
        if ($password !== $confirm) {
            $errors[] = 'Les mots de passe ne correspondent pas.';
        }
        if (empty($_POST['terms'])) {
            $errors[] = 'Vous devez accepter les conditions d\'utilisation.';
        }

        if (empty($errors)) {
            try {
                $userManager = new UserManager();

                if ($userManager->findByEmail($email)) {
                    $errors[] = 'Cette adresse e-mail est déjà utilisée.';
                } else {
                    $userId = $userManager->createMember([
                        'firstname' => $firstname,
                        'lastname' => $lastname,
                        'email' => $email,
                        'phone' => $phone,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                    ]);

                    $_SESSION['user'] = [
                        'id' => $userId,
                        'firstname' => $firstname,
                        'lastname' => $lastname,
                        'email' => $email,
                        'role' => 'member',
                    ];

                    $this->redirect('/dogs/create');
                }
            } catch (PDOException $e) {
                $errors[] = 'Une erreur est survenue, veuillez réessayer.';
            }
        }

        $this->render('auth/register', [
            'page_title' => 'Inscription',
            'errors' => $errors,
            'old' => compact('firstname', 'lastname', 'email', 'phone'),
        ]);
    }

    // Déconnexion (GET /logout)
    public function logout(): void {
        session_destroy();
        $this->redirect('/');
    }
}

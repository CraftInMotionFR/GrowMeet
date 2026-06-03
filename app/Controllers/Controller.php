<?php

abstract class Controller {
    protected View $view;

    private const FLASH_MESSAGES = [
        'booked' => ['type' => 'success', 'text' => "Inscription confirmée ! Vous êtes inscrit(e) au cours."],
        'booked-pending' => ['type' => 'warning', 'text' => "Inscription enregistrée ! Elle sera confirmée dès que le nombre minimum de participants sera atteint."],
        'waiting' => ['type' => 'warning', 'text' => "Ce cours est complet. Vous avez été ajouté(e) à la liste d'attente."],
        'already-booked' => ['type' => 'warning', 'text' => "Vous êtes déjà inscrit(e) à ce cours."],
        'dog-not-eligible' => ['type' => 'error', 'text' => "Ce chien ne correspond pas à la tranche d'âge de ce cours."],
        'no-dog' => ['type' => 'error', 'text' => "Vous devez avoir un chien enregistré pour vous inscrire à un cours."],
        'dog-added' => ['type' => 'success', 'text' => "Votre chien a été ajouté avec succès."],
        'dog-updated' => ['type' => 'success', 'text' => "Les informations de votre chien ont été mises à jour."],
        'dog-deleted' => ['type' => 'success', 'text' => "Le chien a été supprimé, ainsi que ses inscriptions."],
        'booking-cancelled' => ['type' => 'success', 'text' => "Votre inscription a été annulée."],
        'booking-cancelled-staff' => ['type' => 'success', 'text' => "L'inscription a été annulée."],
        'members-only' => ['type' => 'error', 'text' => "Cette fonctionnalité est réservée aux membres."],
        'admins-only' => ['type' => 'error', 'text' => "Cette fonctionnalité est réservée aux administrateurs."],
        'staff-only' => ['type' => 'error', 'text' => "Cette fonctionnalité est réservée aux administrateurs et aux coachs."],
        'not-your-session' => ['type' => 'error', 'text' => "Vous ne pouvez gérer que vos propres séances."],
        'course-type-created' => ['type' => 'success', 'text' => "Type de cours créé avec succès."],
        'course-type-updated' => ['type' => 'success', 'text' => "Type de cours modifié avec succès."],
        'course-type-deleted' => ['type' => 'success', 'text' => "Type de cours supprimé avec succès."],
        'type-in-use' => ['type' => 'error', 'text' => "Impossible de supprimer ce type de cours : des séances y sont encore rattachées."],
        'coach-created' => ['type' => 'success', 'text' => "Coach créé avec succès."],
        'coach-deleted' => ['type' => 'success', 'text' => "Coach supprimé avec succès."],
        'coach-in-use' => ['type' => 'error', 'text' => "Impossible de supprimer ce coach : des séances lui sont encore assignées."],
        'session-created' => ['type' => 'success', 'text' => "Séance créée avec succès."],
        'session-updated' => ['type' => 'success', 'text' => "Séance modifiée avec succès."],
        'session-deleted' => ['type' => 'success', 'text' => "Séance supprimée avec succès."],
    ];

    public function __construct() {
        $this->view = new View();
    }

    protected function flashFromStatus(?string $status): ?array {
        return self::FLASH_MESSAGES[$status] ?? null;
    }

    protected function render(string $template, array $data = []): void {
        $this->view->render($template, $data);
    }

    protected function notFound(
        string $message = "Cette page n'existe pas ou a été déplacée.",
        string $title = "Page non trouvée",
        string $backUrl = '/',
        string $backLabel = "Retour à l'accueil"
    ): void {
        http_response_code(404);
        $this->render('errors/404', compact('message', 'title', 'backUrl', 'backLabel'));
        exit;
    }

    protected function redirect(string $url): void {
        header("Location:$url");
        exit;
    }

    // Force un membre sans chien enregistré à passer par /dogs/create avant d'accéder au dashboard/cours
    protected function requireDog(): void {
        if (($_SESSION['user']['role'] ?? null) !== 'member') {
            return;
        }

        if (!(new DogManager())->findByUser($_SESSION['user']['id'])) {
            $this->redirect('/dogs/create');
        }
    }

    // Bloque l'accès aux actions réservées aux administrateurs
    protected function requireAdmin(): void {
        if (($_SESSION['user']['role'] ?? null) !== 'administrator') {
            $this->redirect($this->homeUrl() . '?status=admins-only');
        }
    }

    // Bloque l'accès aux actions réservées aux administrateurs et aux coachs (gestion des séances)
    protected function requireStaff(): void {
        if (!in_array($_SESSION['user']['role'] ?? null, ['administrator', 'coach'], true)) {
            $this->redirect($this->homeUrl() . '?status=staff-only');
        }
    }

    // Vérifie la robustesse d'un mot de passe (mêmes critères que la checklist JS côté client)
    protected function validatePasswordStrength(string $password): ?string {
        if (strlen($password) < 8) {
            return 'Le mot de passe doit contenir au moins 8 caractères.';
        }
        if (!preg_match('/[#@!?.$%&*]+/', $password)) {
            return 'Le mot de passe doit contenir au moins un caractère spécial parmi #@!?.$%&*.';
        }
        if (!preg_match('/[A-Z]+/', $password)) {
            return 'Le mot de passe doit contenir au moins une majuscule.';
        }
        if (!preg_match('/[0-9]+/', $password)) {
            return 'Le mot de passe doit contenir au moins un chiffre.';
        }
        return null;
    }

    // Page d'accueil de l'espace connecté selon le rôle : les admins/coachs n'ont pas de dashboard "chiens"
    protected function homeUrl(): string {
        return match ($_SESSION['user']['role'] ?? null) {
            'administrator' => '/admin/course-types',
            'coach' => '/admin/sessions',
            default => '/dashboard',
        };
    }
}
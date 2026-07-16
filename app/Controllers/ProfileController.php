<?php

class ProfileController extends Controller {
    private const ROLE_LABELS = [
        'member' => 'Membre',
        'coach' => 'Coach',
        'administrator' => 'Administrateur',
    ];

    // Affiche le profil du compte connecté (GET /profile)
    public function index(): void {
        $role = $_SESSION['user']['role'] ?? 'member';

        $this->render('profile/index', [
            'page_title' => 'Mon profil',
            'current_page' => 'profile',
            'user' => $_SESSION['user'],
            'role_label' => self::ROLE_LABELS[$role] ?? $role,
        ]);
    }
}

<?php

class HomeController extends Controller {
    public function index(): void {
        // Un utilisateur connecté n'a rien à faire sur la vitrine publique : direction son espace
        if (isset($_SESSION['user'])) {
            $this->redirect($this->homeUrl());
        }

        $this->render('home/index', [
            'page_title' => 'Accueil',
        ]);
    }
}

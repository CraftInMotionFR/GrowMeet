<?php

class DashboardController extends Controller {
    public function index(): void {
        // Les admins/coachs n'ont pas de dashboard "chiens" : direction leur propre espace
        if (in_array($_SESSION['user']['role'] ?? null, ['administrator', 'coach'], true)) {
            $this->redirect($this->homeUrl());
        }

        $this->requireDog();

        $userId = $_SESSION['user']['id'];

        $dogManager = new DogManager();
        $bookingManager = new BookingManager();

        $dogs = $dogManager->findAllByUser($userId);
        $stats = $bookingManager->getStats($userId);
        $upcoming = $bookingManager->findUpcoming($userId);

        $this->render('dashboard/index', [
            'page_title' => 'Mon espace',
            'current_page' => 'dashboard',
            'user' => $_SESSION['user'],
            'stats' => $stats,
            'dogs' => $dogs,
            'upcoming' => $upcoming,
            'flash' => $this->flashFromStatus($_GET['status'] ?? null),
        ]);
    }
}

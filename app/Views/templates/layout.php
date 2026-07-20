<?php

$isLoggedIn = isset($_SESSION['user']);
$dashboardPages = ['dashboard', 'courses', 'dogs', 'profile', 'admin-course-types', 'admin-coaches', 'admin-sessions'];
$isAdmin = ($_SESSION['user']['role'] ?? null) === 'administrator';
$isCoach = ($_SESSION['user']['role'] ?? null) === 'coach';
$isStaff = $isAdmin || $isCoach;
$isDashboardPage = $isLoggedIn && isset($current_page) && in_array($current_page, $dashboardPages);
$isCoursePage = isset($current_page) && in_array($current_page, $dashboardPages);
// Page d'accueil de l'espace connecté selon le rôle : les admins/coachs n'ont pas de dashboard "chiens"
$homeUrl = $isAdmin ? '/admin/course-types' : ($isCoach ? '/admin/sessions' : '/dashboard');

// Fichier js à charger selon la page en cours
$jsFiles = [
    'courses' => ['script.js', 'courses.js'],
    'dashboard' => ['script.js'],
    'login' => ['script.js'],
    'register' => ['script.js'],
];

$pageJs = $jsFiles[$current_page ?? ''] ?? ['script.js'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/css/style.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.boxicons.com/3.0.8/fonts/basic/boxicons.min.css" rel="stylesheet">
    <link href="https://cdn.boxicons.com/3.0.8/fonts/brands/boxicons-brands.min.css" rel="stylesheet">
    <?php foreach ($pageJs as $jsFile): ?>
        <script src="/js/<?= htmlspecialchars($jsFile) ?>" defer></script>
    <?php endforeach; ?>
    <title><?= htmlspecialchars($page_title ?? 'GrowMeet') ?> | GrowMeet</title>
</head>
<body>
<?php if ($isDashboardPage): ?>
    <!-- Header dashboard -->
    <header class="header-dashboard">
        <div class="nav-dashboard container">
            <div class="logo">
                <a href="<?= htmlspecialchars($homeUrl) ?>">
                    <img src="/images/GrowMeet_logo_50x50.png" alt="GrowMeet">
                    <span>GrowMeet</span>
                </a>
            </div>
            <nav class="dashboard-nav">
                <?php if (!$isStaff): ?>
                <a href="/dashboard" class="nav-link <?= ($current_page === 'dashboard') ? 'nav-link-active' : '' ?>">
                    <i class="bx bx-home bx-remove-padding"></i>
                    <span>Accueil</span>
                </a>
                <a href="/courses" class="nav-link <?= ($current_page === 'courses') ? 'nav-link-active' : '' ?>">
                    <i class="bx bx-book-open bx-remove-padding"></i>
                    <span>Cours</span>
                </a>
                <a class="nav-link nav-link-disabled" aria-disabled="true" title="Bientôt disponible">
                    <i class="bx bx-calendar bx-remove-padding"></i>
                    <span>Calendrier</span>
                </a>
                <a href="/dogs" class="nav-link <?= ($current_page === 'dogs') ? 'nav-link-active' : '' ?>">
                    <i class="bx bx-dog bx-remove-padding"></i>
                    <span>Chiens</span>
                </a>
                <?php endif; ?>
                <?php if ($isAdmin): ?>
                <a href="/admin/course-types" class="nav-link <?= ($current_page === 'admin-course-types') ? 'nav-link-active' : '' ?>">
                    <i class="bx bx-categories bx-remove-padding"></i>
                    <span>Types de cours</span>
                </a>
                <a href="/admin/coaches" class="nav-link <?= ($current_page === 'admin-coaches') ? 'nav-link-active' : '' ?>">
                    <i class="bx bx-education bx-remove-padding"></i>
                    <span>Coachs</span>
                </a>
                <?php endif; ?>
                <?php if ($isAdmin || $isCoach): ?>
                <a href="/admin/sessions" class="nav-link <?= ($current_page === 'admin-sessions') ? 'nav-link-active' : '' ?>">
                    <i class="bx bx-calendar-event bx-remove-padding"></i>
                    <span>Séances</span>
                </a>
                <?php endif; ?>
            </nav>
            <div class="nav-actions">
                <a href="/profile" class="nav-profile <?= ($current_page === 'profile') ? 'nav-link-active' : '' ?>">
                    <i class="bx bx-user-circle bx-remove-padding"></i>
                    <span><?= htmlspecialchars($_SESSION['user']['firstname'] ?? 'Mon profil') ?></span>
                </a>
            </div>
        </div>
    </header>
<?php else: ?>
    <!-- Header public -->
    <header>
        <div class="nav container">
            <div class="logo">
                <a href="/">
                    <img src="/images/GrowMeet_logo_50x50.png" alt="GrowMeet">
                    <span>GrowMeet</span>
                </a>
            </div>
            <div>
                <?php if ($isLoggedIn): ?>
                    <a href="<?= htmlspecialchars($homeUrl) ?>"><button class="cto-green">Mon espace</button></a>
                <?php else: ?>
                    <a href="/login"><button class="cto-green">Se connecter</button></a>
                    <a href="/register"><button class="cto-orange">S'inscrire</button></a>
                <?php endif; ?>
            </div>
        </div>
    </header>
<?php endif; ?>
<main <?= $isCoursePage ? 'class="main-dashboard"' : '' ?>>
    <?php if (!empty($flash)): ?>
        <div class="container">
            <div class="flash-message flash-message-<?= htmlspecialchars($flash['type']) ?>">
                <?= htmlspecialchars($flash['text']) ?>
            </div>
        </div>
    <?php endif; ?>
    <?= $content ?>
</main>
<footer>
    <div class="container">
        <div class="logo">
            <img src="/images/GrowMeet_logo_40x40.png" alt="GrowMeet">
            <span>GrowMeet</span>
        </div>
        <div>
            <p>© <?= date('Y') ?> GrowMeet. Tous droits réservés.</p>
        </div>
    </div>
</footer>
</body>
</html>

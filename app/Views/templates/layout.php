<?php

$isLoggedIn = isset($_SESSION['user']);
$dashboardPages = ['dashboard', 'courses', 'dogs', 'profile', 'admin-course-types', 'admin-coaches', 'admin-sessions'];
$isDashboardPage = isset($current_page) && in_array($current_page, $dashboardPages);

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
                    <a href="/dashboard"><button class="cto-green">Mon espace</button></a>
                <?php else: ?>
                    <a href="/login"><button class="cto-green">Se connecter</button></a>
                    <a href="/register"><button class="cto-orange">S'inscrire</button></a>
                <?php endif; ?>
            </div>
        </div>
    </header>
<main <?= $isDashboardPage ? 'class="main-dashboard"' : '' ?>>
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

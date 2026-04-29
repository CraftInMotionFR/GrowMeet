<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>GrowMeet</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <a href="index.php"><strong>GrowMeet</strong></a>
    <a href="cours.php">Cours</a>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="dashboard.php">Mon espace</a>
        <a href="deconnexion.php">Déconnexion</a>
    <?php else: ?>
        <a href="connexion.php">Connexion</a>
        <a href="inscription.php">Inscription</a>
    <?php endif; ?>
</header>
<main>

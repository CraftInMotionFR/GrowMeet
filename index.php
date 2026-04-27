<?php
session_start();

// Connexion à la base de données
$pdo = new PDO('mysql:host=localhost;dbname=growmeet;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>GrowMeet</title>
</head>
<body>
    <h1>GrowMeet</h1>
    <p>Bienvenue sur GrowMeet, l'application de gestion de cours d'éducation canine.</p>
</body>
</html>

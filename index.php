<?php
session_start();

// Connexion à la base de données
$pdo = new PDO('mysql:host=localhost;dbname=growmeet;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

include 'includes/header.php';
?>

<h1>Bienvenue sur GrowMeet</h1>
<p>Réservez des cours d'éducation canine pour votre chien près de chez vous.</p>

<?php
// Nombre de cours disponibles
$nb = $pdo->query('SELECT COUNT(*) FROM course')->fetchColumn();
?>
<p><?= $nb ?> cours disponibles pour l'instant.</p>

<?php if (!isset($_SESSION['user_id'])): ?>
    <p><a href="inscription.php">Créer un compte</a> ou <a href="connexion.php">se connecter</a></p>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

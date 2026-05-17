<?php
session_start();

// Il faut être connecté pour voir cette page
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

// Connexion à la base de données
$pdo = new PDO('mysql:host=localhost;dbname=growmeet;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Liste des chiens de l'utilisateur
$req = $pdo->prepare('SELECT dog.*, breed.name AS race FROM dog LEFT JOIN breed ON dog.id_breed = breed.id_breed WHERE dog.id_user = ?');
$req->execute([$_SESSION['user_id']]);
$chiens = $req->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<h1>Bonjour <?= $_SESSION['prenom'] ?></h1>

<h2>Mes chiens</h2>

<?php if (count($chiens) === 0): ?>
    <p>Vous n'avez pas encore ajouté de chien.</p>
<?php else: ?>
    <table>
        <tr><th>Nom</th><th>Race</th><th>Date de naissance</th></tr>
        <?php foreach ($chiens as $chien): ?>
            <tr>
                <td><?= $chien['name'] ?></td>
                <td><?= $chien['race'] ?></td>
                <td><?= $chien['birth_date'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<p><a href="ajouter_chien.php">Ajouter un chien</a></p>

<?php include 'includes/footer.php'; ?>

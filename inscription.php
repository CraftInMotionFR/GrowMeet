<?php
session_start();

// Connexion à la base de données
$pdo = new PDO('mysql:host=localhost;dbname=growmeet;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $mdp = $_POST['mot_de_passe'];

    // Vérifie si l'email existe déjà
    $req = $pdo->prepare('SELECT id_user FROM users WHERE email = ?');
    $req->execute([$email]);

    if ($req->fetch()) {
        $erreur = 'Cet email est déjà utilisé.';
    } else {
        $hash = password_hash($mdp, PASSWORD_DEFAULT);
        $req = $pdo->prepare('INSERT INTO users (nom, prenom, email, mot_de_passe) VALUES (?, ?, ?, ?)');
        $req->execute([$nom, $prenom, $email, $hash]);
        header('Location: connexion.php');
        exit;
    }
}

include 'includes/header.php';
?>

<h1>Inscription</h1>

<?php if ($erreur): ?>
    <p class="erreur"><?= $erreur ?></p>
<?php endif; ?>

<form method="post">
    <label>Nom</label>
    <input type="text" name="nom">
    <label>Prénom</label>
    <input type="text" name="prenom">
    <label>Email</label>
    <input type="email" name="email">
    <label>Mot de passe</label>
    <input type="password" name="mot_de_passe">
    <button type="submit">S'inscrire</button>
</form>

<?php include 'includes/footer.php'; ?>

<?php
session_start();

// Connexion à la base de données
$pdo = new PDO('mysql:host=localhost;dbname=growmeet;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $mdp = $_POST['mot_de_passe'];

    $req = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $req->execute([$email]);
    $user = $req->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($mdp, $user['mot_de_passe'])) {
        // On garde l'id et le prénom en session
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['prenom'] = $user['prenom'];
        header('Location: dashboard.php');
        exit;
    } else {
        $erreur = 'Email ou mot de passe incorrect.';
    }
}

include 'includes/header.php';
?>

<h1>Connexion</h1>

<?php if ($erreur): ?>
    <p class="erreur"><?= $erreur ?></p>
<?php endif; ?>

<form method="post">
    <label>Email</label>
    <input type="email" name="email">
    <label>Mot de passe</label>
    <input type="password" name="mot_de_passe">
    <button type="submit">Se connecter</button>
</form>

<?php include 'includes/footer.php'; ?>

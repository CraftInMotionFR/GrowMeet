<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

// Connexion à la base de données
$pdo = new PDO('mysql:host=localhost;dbname=growmeet;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $race = $_POST['race'];
    $naissance = $_POST['naissance'];

    if (empty($nom)) {
        $erreur = 'Le nom du chien est obligatoire.';
    } else {
        $req = $pdo->prepare('INSERT INTO dog (name, birth_date, id_user, id_breed) VALUES (?, ?, ?, ?)');
        $req->execute([$nom, $naissance ?: null, $_SESSION['user_id'], $race ?: null]);
        header('Location: dashboard.php');
        exit;
    }
}

$races = $pdo->query('SELECT * FROM breed ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<h1>Ajouter un chien</h1>

<?php if ($erreur): ?>
    <p class="erreur"><?= $erreur ?></p>
<?php endif; ?>

<form method="post">
    <label>Nom</label>
    <input type="text" name="nom">
    <label>Race</label>
    <select name="race">
        <option value="">-- Choisir --</option>
        <?php foreach ($races as $r): ?>
            <option value="<?= $r['id_breed'] ?>"><?= $r['name'] ?></option>
        <?php endforeach; ?>
    </select>
    <label>Date de naissance</label>
    <input type="date" name="naissance">
    <button type="submit">Ajouter</button>
</form>

<?php include 'includes/footer.php'; ?>

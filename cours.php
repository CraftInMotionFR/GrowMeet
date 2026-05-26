<?php
session_start();

// Connexion à la base de données
$pdo = new PDO('mysql:host=localhost;dbname=growmeet;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Cours à venir, du plus proche au plus lointain
$cours = $pdo->query('SELECT * FROM course WHERE date >= CURDATE() ORDER BY date, start_time')->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<h1>Nos cours</h1>

<?php if (count($cours) === 0): ?>
    <p>Aucun cours prévu pour le moment.</p>
<?php else: ?>
    <table>
        <tr><th>Cours</th><th>Date</th><th>Horaire</th><th>Places</th></tr>
        <?php foreach ($cours as $c): ?>
            <tr>
                <td><?= $c['name'] ?></td>
                <td><?= date('d/m/Y', strtotime($c['date'])) ?></td>
                <td><?= substr($c['start_time'], 0, 5) ?> - <?= substr($c['end_time'], 0, 5) ?></td>
                <td><?= $c['max_participants'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

<?php

$config = require __DIR__ . '/../config/database.php';
$dbName = getenv('GROWMEET_DB') ?: 'growmeet_test';

if (!preg_match('/^[a-z0-9_]+$/i', $dbName) || $dbName === 'growmeet') {
    fwrite(STDERR, "Nom de base refusé : « {$dbName} » (la base de développement ne doit jamais être réinitialisée).\n");
    exit(1);
}

$sql = file_get_contents(__DIR__ . '/../database/migrations/growmeet.sql');
$sql = preg_replace('/CREATE DATABASE IF NOT EXISTS growmeet\b/', "CREATE DATABASE `{$dbName}`", $sql, 1, $n1);
$sql = preg_replace('/USE growmeet;/', "USE `{$dbName}`;", $sql, 1, $n2);

if ($n1 !== 1 || $n2 !== 1) {
    fwrite(STDERR, "Impossible d'adapter le nom de la base dans la migration.\n");
    exit(1);
}

$pdo = new PDO("mysql:host={$config['host']};charset={$config['charset']}", $config['username'], $config['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
$pdo->exec($sql);

echo "Base « {$dbName} » recréée.\n";

<?php

// Les identifiants viennent du fichier .env (voir .env.example)
require_once __DIR__ . '/../app/Core/Env.php';
Env::load(__DIR__ . '/../.env');

return [
    'host' => Env::get('DB_HOST', 'localhost'),
    // GROWMEET_DB permet de pointer vers une autre base (tests e2e : growmeet_test)
    'dbname' => Env::get('GROWMEET_DB', Env::get('DB_NAME', 'growmeet')),
    'username' => Env::get('DB_USER', 'root'),
    'password' => Env::get('DB_PASS', ''),
    'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
];
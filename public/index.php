<?php

// 1. Autoloader : charge automatiquement toutes les classes de app/
require_once __DIR__ . '/../app/Core/Autoloader.php';
$autoloader = new Autoloader(__DIR__ . '/../app');
$autoloader->register();

// 2. Démarrage de la session
session_start();

// 3. Routeur
require_once __DIR__ . '/../app/Core/Router.php';
$router = new Router();

// Déclaration de toutes les routes (source unique de vérité)
require __DIR__ . '/../config/routes.php';

// Lancement
$method = $_SERVER['REQUEST_METHOD'];
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$router->dispatch($method, $url);

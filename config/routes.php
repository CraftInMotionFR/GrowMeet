<?php

// Routes publiques (ni auth ni guest)
$router->get('/', 'HomeController', 'index');
$router->get('/courses', 'CourseController', 'index');

// Routes guest - redirige vers /dashboard si déjà connecté
$router->get('/login', 'AuthController', 'loginForm')->middleware('guest');
$router->post('/login', 'AuthController', 'login')->middleware('guest');
$router->get('/register', 'AuthController', 'registerForm')->middleware('guest');
$router->post('/register', 'AuthController', 'register')->middleware('guest');

// Routes protégées - redirige vers /login si non connecté
$router->get('/logout', 'AuthController', 'logout')->middleware('auth');
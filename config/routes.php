<?php

// Routes publiques (ni auth ni guest)
$router->get('/', 'HomeController', 'index');
$router->get('/courses', 'CourseController', 'index');
$router->get('/courses/{id}', 'CourseController', 'show');

// Routes guest - redirige vers /dashboard si déjà connecté
$router->get('/login', 'AuthController', 'loginForm')->middleware('guest');
$router->post('/login', 'AuthController', 'login')->middleware('guest');
$router->get('/register', 'AuthController', 'registerForm')->middleware('guest');
$router->post('/register', 'AuthController', 'register')->middleware('guest');

// Routes protégées - redirige vers /login si non connecté
$router->get('/dashboard', 'DashboardController', 'index')->middleware('auth');
$router->get('/profile', 'ProfileController', 'index')->middleware('auth');
$router->get('/logout', 'AuthController', 'logout')->middleware('auth');
$router->post('/courses/{id}/join', 'CourseController', 'join')->middleware('auth');
$router->post('/courses/{id}/cancel', 'CourseController', 'cancel')->middleware('auth');
$router->get('/dogs', 'DogController', 'index')->middleware('auth');
$router->get('/dogs/create', 'DogController', 'create')->middleware('auth');
$router->post('/dogs', 'DogController', 'store')->middleware('auth');
$router->get('/dogs/{id}/edit', 'DogController', 'edit')->middleware('auth');
$router->post('/dogs/{id}/update', 'DogController', 'update')->middleware('auth');
$router->post('/dogs/{id}/delete', 'DogController', 'delete')->middleware('auth');

// Routes admin - restriction de rôle vérifiée dans chaque contrôleur
$router->get('/admin/course-types', 'CourseTypeController', 'index')->middleware('auth');
$router->get('/admin/course-types/create', 'CourseTypeController', 'create')->middleware('auth');
$router->post('/admin/course-types', 'CourseTypeController', 'store')->middleware('auth');
$router->get('/admin/course-types/{id}/edit', 'CourseTypeController', 'edit')->middleware('auth');
$router->post('/admin/course-types/{id}/update', 'CourseTypeController', 'update')->middleware('auth');
$router->post('/admin/course-types/{id}/delete', 'CourseTypeController', 'delete')->middleware('auth');

$router->get('/admin/coaches', 'CoachController', 'index')->middleware('auth');
$router->get('/admin/coaches/create', 'CoachController', 'create')->middleware('auth');
$router->post('/admin/coaches', 'CoachController', 'store')->middleware('auth');
$router->post('/admin/coaches/{id}/delete', 'CoachController', 'delete')->middleware('auth');

$router->get('/admin/sessions', 'CourseController', 'adminIndex')->middleware('auth');
$router->get('/admin/sessions/create', 'CourseController', 'adminCreate')->middleware('auth');
$router->post('/admin/sessions', 'CourseController', 'adminStore')->middleware('auth');
$router->get('/admin/sessions/{id}/edit', 'CourseController', 'adminEdit')->middleware('auth');
$router->post('/admin/sessions/{id}/update', 'CourseController', 'adminUpdate')->middleware('auth');
$router->post('/admin/sessions/{id}/delete', 'CourseController', 'adminDelete')->middleware('auth');
$router->get('/admin/sessions/{id}/bookings', 'CourseController', 'adminBookings')->middleware('auth');
$router->post('/admin/sessions/{id}/bookings/{dogId}/cancel', 'CourseController', 'adminCancelBooking')->middleware('auth');
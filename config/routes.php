<?php

// Routes publiques (ni auth ni guest)
$router->get('/', 'HomeController', 'index');
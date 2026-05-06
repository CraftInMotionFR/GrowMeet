<?php
session_start();

// On vide la session puis retour à l'accueil
session_destroy();
header('Location: index.php');
exit;

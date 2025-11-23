<?php
session_start();

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire le cookie de session si existant
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Détruire la session
session_destroy();

// Redirection vers la page de connexion avec message
header('Location: login.php?logout=1');
exit();
?>
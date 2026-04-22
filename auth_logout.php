<?php
// Démarrer la session si elle ne l'est pas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Détruire tous les données de session
$_SESSION = [];
session_destroy();

// Redirection vers la page d'accueil (ou login)
header('Location: index.php?logout=1');
exit;
?>

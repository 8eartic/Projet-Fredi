<?php
// auth_logout.php - déconnecte l'utilisateur et détruit sa session.
// Cette page est utilisée pour fermer proprement la session côté serveur.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vider la session et supprimer ses données.
$_SESSION = [];
session_destroy();

// Rediriger vers la page d'accueil après déconnexion.
header('Location: index.php?logout=1');
exit;
?>

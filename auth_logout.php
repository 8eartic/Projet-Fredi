<?php
// Si aucune session n'est déjà ouverte
// on en démarre une, car on ne peut pas détruire quelque chose qui n'existe pas.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vider la session et supprimer ses données.
$_SESSION = [];
session_destroy();

// Rediriger vers la page d'accueil après déconnexion, avec le paramètre logout=1 dans l'URL.
header('Location: index.php?logout=1');
exit;
?>

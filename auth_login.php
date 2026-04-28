<?php
// Démarre ou restaure la session PHP afin de pouvoir stocker
// des informations de l'utilisateur après la connexion.
session_start();

// Charge la connexion à la base de données définie dans db.php.
// Cela fournit l'objet $db utilisé pour exécuter les requêtes préparées.
require 'db.php';

// Vérifie que l'utilisateur a bien envoyé un email et un mot de passe.
// Si un champ manque, on revient à l'accueil avec un code d'erreur.
if (empty($_POST['email']) || empty($_POST['password'])) {
    header('Location: index.php?error=4');
    exit;
}

// Trim enlève les espaces superflus autour de l'email.
// On laisse le mot de passe tel quel pour ne pas altérer la saisie.
$email = trim($_POST['email']);
$password = $_POST['password'];

// Requête préparée pour éviter les injections SQL.
// On sélectionne uniquement les colonnes nécessaires à l'authentification.
$stmt = $db->prepare("SELECT id, first_name, last_name, role, password_hash FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Si aucun utilisateur n'existe pour cet email, on redirige avec erreur.
if (!$user) {
    header('Location: index.php?error=3');
    exit;
}

// Vérifie le mot de passe hashé.
// password_verify compare le mot de passe envoyé par l'utilisateur
// avec le hash stocké en base, sans jamais stocker le mot de passe brut.
if (!password_verify($password, $user['password_hash'])) {
    header('Location: index.php?error=2');
    exit;
}

// Sécurité : on change l'ID de session après l'authentification
// pour réduire le risque de session fixation.
session_regenerate_id(true);

// Stocke les données essentielles de l'utilisateur dans la session.
// La clé 'utilisateur' est utilisée partout dans le projet pour vérifier
// l'authentification et le rôle.
$_SESSION['utilisateur'] = [
    'id' => $user['id'],
    'nom' => $user['last_name'],
    'prenom' => $user['first_name'],
    'role' => $user['role']
];

// Redirige selon le rôle de l'utilisateur.
// Le trésorier va vers son tableau de bord, les adhérents vers le formulaire.
if ($user['role'] === 'tresorier') {
    header('Location: tresorier_dashboard.php');
} else {
    header('Location: Formulaire_remboursement.php');
}
exit;

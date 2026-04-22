<?php
session_start();
require 'db.php';

if (empty($_POST['email']) || empty($_POST['password'])) {
    header('Location: index.php?error=4');
    exit;
}

$email = trim($_POST['email']);
$password = $_POST['password'];

// Adapter la requête à la structure de la base `fredi.sql` (table `users`)
$stmt = $db->prepare("SELECT id, first_name, last_name, role, password_hash FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: index.php?error=3');
    exit;
}

if (!password_verify($password, $user['password_hash'])) {
    header('Location: index.php?error=2');
    exit;
}

// Prévenir fixation de session
session_regenerate_id(true);

// Normaliser les clés de session pour le reste de l'application
$_SESSION['utilisateur'] = [
    'id' => $user['id'],
    'nom' => $user['last_name'],
    'prenom' => $user['first_name'],
    'role' => $user['role']
];

// Redirection basée sur le rôle
if ($user['role'] === 'tresorier') {
    header('Location: tresorier_dashboard.php');
} else {
    header('Location: Formulaire_remboursement.php');
}
exit;

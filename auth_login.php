<?php
// Démarre la session PHP. Sans cette ligne, $_SESSION ne fonctionne pas.
session_start();

// Importe le fichier qui contient la connexion à la base de données. 
require 'db.php';

// Si l'utilisateur a soumis le formulaire sans remplir l'email ou le mot de passe
// on le renvoie immédiatement à la page d'accueil avec le code d'erreur 4.     ON NE LE VOIS PAS SUR LA PAGE
if (empty($_POST['email']) || empty($_POST['password'])) {
    header('Location: index.php?error=4');
    exit;
}

// On récupère ce que l'utilisateur a tapé.
// trim() supprime les espaces accidentels autour de l'email
$email = trim($_POST['email']);
$password = $_POST['password'];

// On cherche dans la table users si quelqu'un possède cet email.
// Le ? est remplacé de façon sécurisée par l'email, ce qui protège contre les injections SQL.
// Le résultat est stocké dans $user sous forme de tableau
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

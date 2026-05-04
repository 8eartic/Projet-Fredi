<?php
// auth_register.php - traite le formulaire d'inscription d'un nouvel adhérent.
// Ce script vérifie les données, protège le mot de passe puis crée l'utilisateur.

session_start();
require 'db.php';

/**
 * Vérifie l'existence d'une colonne dans la table users.
 * Utile pour faire fonctionner l'application sur des bases de données
 * qui peuvent avoir été installées avec un schéma ancien ou incomplet.
 */
function columnExists($db, $column) {
    $stmt = $db->prepare("SHOW COLUMNS FROM users LIKE ?");
    $stmt->execute([$column]);
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Envoie un email de confirmation d'inscription.
 * Ce n'est pas une vraie validation par email, mais cela simule
 * une communication de bienvenue pour l'utilisateur.
 */
function sendConfirmationEmail($email, $first_name) {
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $subject = "Bienvenue sur FREDI - confirmation d'inscription";
    $message = "Bonjour $first_name,\r\n\r\n"
        . "Merci pour votre inscription sur FREDI. Votre compte a bien été créé et vous pouvez dès maintenant accéder au formulaire de remboursement.\r\n\r\n"
        . "Voici vos informations de connexion :\r\n"
        . "- Email : $email\r\n"
        . "- Accès : https://$host/Projet-Fredi/login.php\r\n\r\n"
        . "Si vous n'avez pas créé ce compte, veuillez nous contacter.\r\n\r\n"
        . "Cordialement,\r\n"
        . "L'équipe FREDI";

    $headers = "From: no-reply@$host\r\n"
               . "Reply-To: no-reply@$host\r\n"
               . "Content-Type: text/plain; charset=UTF-8\r\n";

    return mail($email, $subject, $message, $headers);
}

// Validation basique des champs requis du formulaire d'inscription.
// Si un champ est manquant, on redirige vers le formulaire avec une erreur.
if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['license_number']) || empty($_POST['league_name']) || empty($_POST['email']) || empty($_POST['password'])) {
    header('Location: register.php?error=' . urlencode('Tous les champs sont requis.'));
    exit;
}

$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);
$license_number = trim($_POST['license_number']);
$league_name = trim($_POST['league_name']);
$email = trim($_POST['email']);
$password = $_POST['password'];

// Vérifie que l'email est au bon format.
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: register.php?error=' . urlencode('Email invalide.'));
    exit;
}

// Exigences de sécurité pour le mot de passe :
// - au moins 12 caractères
// - au moins une majuscule
// - au moins une minuscule
// - au moins un chiffre
// - au moins un caractère spécial
$passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#\$%\^&\*\(\)_\+\-=\[\]\{\};:\"\\|,<\.>\/?]).{12,}$/';
if (!preg_match($passwordPattern, $password)) {
    header('Location: register.php?error=' . urlencode('Le mot de passe doit contenir au moins 12 caractères, avec des lettres majuscules et minuscules, un chiffre et un caractère spécial.'));
    exit;
}

// Vérifie qu'aucun compte n'existe déjà avec l'email donné.
$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    header('Location: register.php?error=' . urlencode('Cet email est déjà utilisé.'));
    exit;
}

// Hash du mot de passe avant stockage. Le hashage protège les mots de passe
// en cas de fuite de la base de données.
$password_hash = password_hash($password, PASSWORD_DEFAULT);

$fields = ['email', 'password_hash', 'first_name', 'last_name', 'role', 'created_at'];
$placeholders = ['?', '?', '?', '?', '?', 'NOW()'];
$values = [$email, $password_hash, $first_name, $last_name, 'adherent'];

// Si la base contient les colonnes liées à la ligue et la licence,
// on les ajoute à l'insertion.
if (columnExists($db, 'license_number') && columnExists($db, 'league_name')) {
    $fields[] = 'license_number';
    $fields[] = 'league_name';
    $placeholders[] = '?';
    $placeholders[] = '?';
    $values[] = $license_number;
    $values[] = $league_name;
}

// Construction dynamique de la requête INSERT pour rester compatible
// avec plusieurs versions du schéma de base de données.
$sql = sprintf(
    'INSERT INTO users (%s) VALUES (%s)',
    implode(', ', $fields),
    implode(', ', $placeholders)
);
$stmt = $db->prepare($sql);
$stmt->execute($values);

$userId = $db->lastInsertId();

// Envoi d'un email de confirmation de création de compte.
if (!sendConfirmationEmail($email, $first_name)) {
    error_log('Échec de l\'envoi de l\'email de confirmation pour ' . $email);
}

// Création de la session utilisateur une fois l'inscription réussie.
$_SESSION['utilisateur'] = [
    'id' => $userId,
    'nom' => $last_name,
    'prenom' => $first_name,
    'role' => 'adherent'
];

// Redirection vers la page adhérent après inscription.
header('Location: Formulaire_remboursement.php');
exit;

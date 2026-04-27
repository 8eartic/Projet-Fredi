<?php
session_start();
require 'db.php';

function columnExists($db, $column) {
    $stmt = $db->prepare("SHOW COLUMNS FROM users LIKE ?");
    $stmt->execute([$column]);
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function sendConfirmationEmail($email, $first_name) {
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $subject = "Bienvenue sur FREDI - confirmation d'inscription";
    $message = "Bonjour $first_name,\r\n\r\n" .
        "Merci pour votre inscription sur FREDI. Votre compte a bien été créé et vous pouvez dès maintenant accéder au formulaire de remboursement.\r\n\r\n" .
        "Voici vos informations de connexion :\r\n" .
        "- Email : $email\r\n" .
        "- Accès : https://$host/Projet-Fredi/login.php\r\n\r\n" .
        "Si vous n'avez pas créé ce compte, veuillez nous contacter.\r\n\r\n" .
        "Cordialement,\r\n" .
        "L'équipe FREDI";

    $headers = "From: no-reply@$host\r\n" .
               "Reply-To: no-reply@$host\r\n" .
               "Content-Type: text/plain; charset=UTF-8\r\n";

    return mail($email, $subject, $message, $headers);
}

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

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: register.php?error=' . urlencode('Email invalide.'));
    exit;
}

if (strlen($password) < 6) {
    header('Location: register.php?error=' . urlencode('Le mot de passe doit contenir au moins 6 caractères.'));
    exit;
}

$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    header('Location: register.php?error=' . urlencode('Cet email est déjà utilisé.'));
    exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$fields = ['email', 'password_hash', 'first_name', 'last_name', 'role', 'created_at'];
$placeholders = ['?', '?', '?', '?', '?', 'NOW()'];
$values = [$email, $password_hash, $first_name, $last_name, 'adherent'];

if (columnExists($db, 'license_number') && columnExists($db, 'league_name')) {
    $fields[] = 'license_number';
    $fields[] = 'league_name';
    $placeholders[] = '?';
    $placeholders[] = '?';
    $values[] = $license_number;
    $values[] = $league_name;
}

$sql = sprintf(
    'INSERT INTO users (%s) VALUES (%s)',
    implode(', ', $fields),
    implode(', ', $placeholders)
);
$stmt = $db->prepare($sql);
$stmt->execute($values);

$userId = $db->lastInsertId();

// Envoi automatique d'un email de confirmation
if (!sendConfirmationEmail($email, $first_name)) {
    error_log('Échec de l\'envoi de l\'email de confirmation pour ' . $email);
}

$_SESSION['utilisateur'] = [
    'id' => $userId,
    'nom' => $last_name,
    'prenom' => $first_name,
    'role' => 'adherent'
];

header('Location: Formulaire_remboursement.php');
exit;

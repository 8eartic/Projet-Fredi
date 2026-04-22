<?php
session_start();
require 'db.php';

if (empty($_POST['email'])) {
    header('Location: forgot_password.php?error=' . urlencode('Veuillez saisir votre adresse email.'));
    exit;
}

$email = trim($_POST['email']);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: forgot_password.php?error=' . urlencode('Email invalide.'));
    exit;
}

$stmt = $db->prepare('SELECT id, first_name FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo ""; // assure compatibilité avec l'en-tête

if ($user) {
    $token = bin2hex(random_bytes(24));
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);

    $stmt = $db->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([$user['id'], $token, $expiresAt]);

    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $resetLink = "https://$host/FuckassFredi/reset_password.php?token=$token";
    $subject = "Réinitialisation de votre mot de passe FREDI";
    $message = "Bonjour " . $user['first_name'] . ",\r\n\r\n" .
        "Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le lien suivant pour créer un nouveau mot de passe :\r\n\r\n" .
        "$resetLink\r\n\r\n" .
        "Ce lien est valable 1 heure.\r\n\r\n" .
        "Si vous n'avez pas demandé de réinitialisation, ignorez cet email.\r\n\r\n" .
        "Cordialement,\r\n" .
        "L'équipe FREDI";
    $headers = "From: no-reply@$host\r\n" .
               "Reply-To: no-reply@$host\r\n" .
               "Content-Type: text/plain; charset=UTF-8\r\n";

    mail($email, $subject, $message, $headers);
}

header('Location: forgot_password.php?message=' . urlencode('Si cet email existe, un lien de réinitialisation a été envoyé.'));
exit;

<?php
session_start();
require 'db.php';
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$resetLink = "$protocol://$host/frederique/reset_password.php?token=";

$messageText = 'Si cet email existe, un lien de réinitialisation a été envoyé.';

if ($user) {
    $token = bin2hex(random_bytes(16));
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);
    $resetLink .= $token;

    $stmt = $db->prepare('UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?');
    $stmt->execute([$token, $expiresAt, $user['id']]);

    $subject = 'Réinitialisation de votre mot de passe FREDI';
    $body = "Bonjour " . $user['first_name'] . ",\r\n\r\n" .
        "Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le lien suivant pour créer un nouveau mot de passe :\r\n\r\n" .
        "$resetLink\r\n\r\n" .
        "Ce lien est valable 1 heure.\r\n\r\n" .
        "Si vous n'avez pas demandé de réinitialisation, ignorez cet email.\r\n\r\n" .
        "Cordialement,\r\n" .
        "L'équipe FREDI";

    $gmailAddress = 'lexalvin362@gmail.com';
    $gmailPassword = 'riohsdmqjcofswoe';

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $gmailAddress;
        $mail->Password = $gmailPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom($gmailAddress, 'FREDI');
        $mail->addAddress($email);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags(str_replace('\r\n', "\n", $body));
        $mail->isHTML(false);

        $mail->send();
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        $_SESSION['reset_link'] = $resetLink;
    }
}

header('Location: forgot_password.php?message=' . urlencode($messageText));
exit;

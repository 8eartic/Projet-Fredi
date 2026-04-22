<?php
session_start();
require 'db.php';

if (empty($_POST['token']) || empty($_POST['password']) || empty($_POST['password_confirm'])) {
    header('Location: reset_password.php?token=' . urlencode($_POST['token'] ?? '') . '&error=' . urlencode('Tous les champs sont requis.'));
    exit;
}

$token = $_POST['token'];
$password = $_POST['password'];
$password_confirm = $_POST['password_confirm'];

if ($password !== $password_confirm) {
    header('Location: reset_password.php?token=' . urlencode($token) . '&error=' . urlencode('Les mots de passe ne correspondent pas.'));
    exit;
}

if (strlen($password) < 6) {
    header('Location: reset_password.php?token=' . urlencode($token) . '&error=' . urlencode('Le mot de passe doit contenir au moins 6 caractères.'));
    exit;
}

$stmt = $db->prepare('SELECT pr.user_id FROM password_resets pr WHERE pr.token = ? AND pr.expires_at >= NOW()');
$stmt->execute([$token]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reset) {
    header('Location: login.php?error=' . urlencode('Lien de réinitialisation invalide ou expiré.'));
    exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
$stmt->execute([$password_hash, $reset['user_id']]);

$stmt = $db->prepare('DELETE FROM password_resets WHERE user_id = ?');
$stmt->execute([$reset['user_id']]);

header('Location: login.php?message=' . urlencode('Votre mot de passe a été mis à jour. Connectez-vous maintenant.'));
exit;

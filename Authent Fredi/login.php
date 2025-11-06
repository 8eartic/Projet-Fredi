<?php
require 'config.php';
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$email = strtolower(trim($data['email']));
$password = $data['password'];

$stmt = $pdo->prepare("SELECT id, name, email, password_hash FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
    echo json_encode(['status' => 'error', 'message' => 'Email ou mot de passe incorrect.']);
    exit;
}

// Création de la session
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];

echo json_encode(['status' => 'success', 'message' => 'Connexion réussie.']);
?>

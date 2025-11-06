<?php
require 'config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$name = trim($data['name']);
$email = strtolower(trim($data['email']));
$password = $data['password'];

if (!$name || !$email || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'Champs manquants.']);
    exit;
}

// Vérifier si l'email existe déjà
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Cet email est déjà utilisé.']);
    exit;
}

// Hachage sécurisé du mot de passe
$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
$stmt->execute([$name, $email, $hash]);

echo json_encode(['status' => 'success', 'message' => 'Inscription réussie.']);
?>

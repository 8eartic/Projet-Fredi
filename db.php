<?php
require_once __DIR__ . '/helpers.php';
$host = 'localhost';
$dbname = 'fredi';
$user = 'root';
$pass = '';

try {
    $db = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
} catch (PDOException $e) {
    error_log('DB connection error: ' . $e->getMessage());
    abort('Erreur DB : connexion impossible.', 500);
}

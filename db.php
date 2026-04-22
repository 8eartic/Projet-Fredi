<?php
require_once __DIR__ . '/helpers.php';
$host = 'sql211.infinityfree.com';
$dbname = 'if0_41723856_fredi';
$user = 'if0_41723856';
$pass = 'rffoTlbcLNbeycr';

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

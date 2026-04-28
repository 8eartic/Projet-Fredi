<?php
// db.php - connexion centralisée à la base de données MySQL/MariaDB.
// Ce fichier est inclus dans toutes les pages qui doivent échanger
// des données avec la base.

require_once __DIR__ . '/helpers.php';

// Paramètres de connexion. Sur InfinityFree, il faudra modifier
// ces valeurs pour correspondre à votre base distante.
$host = 'localhost';
$dbname = 'fredi';
$user = 'root';
$pass = '';

try {
    // PDO est utilisé pour sa capacité à gérer les requêtes préparées,
    // la gestion des erreurs et les encodages plus proprement que mysqli.
    $db = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
} catch (PDOException $e) {
    // Si la connexion échoue, on log l'erreur côté serveur
    // et on renvoie un message utilisateur générique.
    error_log('DB connection error: ' . $e->getMessage());
    abort('Erreur DB : connexion impossible.', 500);
}

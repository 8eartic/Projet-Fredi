<?php
$host = 'localhost';
$user = 'root';       // ton utilisateur MySQL (souvent 'root' en local)
$pass = '';           // mot de passe MySQL (souvent vide en local)
$dbname = 'sportclub';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>

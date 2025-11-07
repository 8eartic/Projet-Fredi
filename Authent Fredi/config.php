<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'association_sportive';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Erreur de connexion à la base de données."]));
}
?>

<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "mon_site";

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Erreur connexion base : " . $e->getMessage());
}
?>

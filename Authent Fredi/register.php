<?php
require "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = trim($_POST["nom"]);
    $prenom = trim($_POST["prenom"]);
    $adresse = trim($_POST["adresse"]);
    $tel_portable = trim($_POST["tel_portable"]);
    $tel_fixe = trim($_POST["tel_fixe"]);
    $email = strtolower(trim($_POST["email"]));
    $mdp = $_POST["mdp"];

    // Vérifie que tous les champs sont remplis
    if (!$nom || !$prenom || !$adresse || !$email || !$mdp) {
        die("Veuillez remplir tous les champs.");
    }

    // Vérifie si l'email existe déjà
    $check = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->rowCount() > 0) {
        die("Cet email est déjà utilisé.");
    }

    // Hash du mot de passe
    $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT);

    // Insertion
    $stmt = $db->prepare("INSERT INTO users (nom, prenom, adresse, tel_portable, tel_fixe, email, mdp)
                          VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nom, $prenom, $adresse, $tel_portable, $tel_fixe, $email, $mdp_hash]);

    // Redirige vers ton site après inscription réussie
    header("Location: suite.html");
    exit;
}
?>

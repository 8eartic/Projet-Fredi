<?php
session_start();
require "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Lecture du JSON envoyé par fetch()
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    // Vérifie que les données ont bien été reçues
    if (!$data) {
        echo json_encode(["status" => "error", "message" => "Données invalides."]);
        exit;
    }

    $email = strtolower(trim($data["email"] ?? ""));
    $mdp = $data["password"] ?? "";

    if (empty($email) || empty($mdp)) {
        echo json_encode(["status" => "error", "message" => "Veuillez remplir tous les champs."]);
        exit;
    }

    // Vérifie si l'utilisateur existe
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($mdp, $user["mdp"])) {
        $_SESSION["user"] = [
            "id" => $user["id"],
            "nom" => $user["nom"],
            "prenom" => $user["prenom"],
            "email" => $user["email"]
        ];

        echo json_encode(["status" => "success", "message" => "Connexion réussie."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Identifiants incorrects."]);
    }
}
?>

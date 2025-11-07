<?php
// login.php
session_start();
header('Content-Type: application/json');

require 'config.php';

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Erreur de connexion à la base de données."]);
    exit;
}

// Récupération des données JSON envoyées
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["status" => "error", "message" => "Aucune donnée reçue."]);
    exit;
}

$email = trim(strtolower($data["email"] ?? ""));
$mdp = $data["password"] ?? "";

if ($email === "" || $mdp === "") {
    echo json_encode(["status" => "error", "message" => "Veuillez entrer un email et un mot de passe."]);
    exit;
}

// Recherche de l'utilisateur
$stmt = $conn->prepare("SELECT id, nom, prenom, mdp FROM membres WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($mdp, $user["mdp"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["user_name"] = $user["prenom"];
        echo json_encode([
            "status" => "success",
            "message" => "Connexion réussie ! Bienvenue " . htmlspecialchars($user["prenom"]) . "."
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Mot de passe incorrect."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Aucun compte trouvé pour cet email."]);
}

$stmt->close();
$conn->close();
?>

<?php
// register.php
header('Content-Type: application/json');

$conn = new mysqli('localhost', 'root', '', 'association_sportive');
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Erreur de connexion à la base de données."]);
    exit;
}

// Récupère les données JSON envoyées
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["status" => "error", "message" => "Aucune donnée reçue."]);
    exit;
}

$nom = trim($data["nom"] ?? "");
$prenom = trim($data["prenom"] ?? "");
$adresse = trim($data["adresse"] ?? "");
$tel = trim($data["tel"] ?? "");
$mobile = trim($data["mobile"] ?? "");
$email = trim(strtolower($data["email"] ?? ""));
$mdp = $data["mdp"] ?? "";

// Vérification de base
if ($nom === "" || $prenom === "" || $adresse === "" || $email === "" || $mdp === "") {
    echo json_encode(["status" => "error", "message" => "Veuillez remplir tous les champs obligatoires."]);
    exit;
}

// Vérifie si l'email existe déjà
$stmt = $conn->prepare("SELECT id FROM membres WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Cet email est déjà enregistré."]);
    exit;
}
$stmt->close();

// Enregistre dans la base
$hash = password_hash($mdp, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO membres (nom, prenom, adresse, tel, mobile, email, mdp) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $nom, $prenom, $adresse, $tel, $mobile, $email, $hash);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Inscription réussie ! Vous pouvez vous connecter."]);
} else {
    echo json_encode(["status" => "error", "message" => "Erreur lors de l'inscription."]);
}

$stmt->close();
$conn->close();
?>

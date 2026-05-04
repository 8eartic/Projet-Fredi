<?php
/* ===============================
   SETUP BDD - Créer la table documents_remboursement
================================ */
require_once __DIR__ . '/helpers.php';  // Charge le fichier helpers.php (qui contient la fonction abort() utilisée plus bas)
$host = "localhost";
$dbname = "fredi";
$user = "root";
$pass = "";

try {
    $pdo = new PDO(  // Ouvre la connexion à la base de données avec gestion des erreurs activée.
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Créer la table
    $sql = "CREATE TABLE IF NOT EXISTS documents_remboursement (
        id_document INT PRIMARY KEY AUTO_INCREMENT,
        id_remboursement INT NOT NULL,
        nom_fichier VARCHAR(255) NOT NULL,
        chemin_fichier VARCHAR(500) NOT NULL,
        type_fichier VARCHAR(50),
        taille_fichier INT,
        date_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        --  si la demande de remboursement liée est supprimée, le document l'est aussi automatiquement
        FOREIGN KEY (id_remboursement) REFERENCES remboursement(id_remboursement) ON DELETE CASCADE
    )";

    $pdo->exec($sql);   // Exécute la requête SQL de création de la table.
    echo "✅ Table 'documents_remboursement' créée avec succès !<br>";

    // Créer le dossier de stockage
    $uploadDir = "uploads/documents/";  // Définit le chemin du dossier
    if (!is_dir($uploadDir)) {      // Vérifie si ce dossier n'existe pas encore.
        mkdir($uploadDir, 0755, true);  // Crée le dossier avec les permissions 0755 (lisible par tous, modifiable seulement par le propriétaire).
         // Le true permet de créer les dossiers parents si nécessaire. 
        echo "✅ Dossier 'uploads/documents/' créé avec succès !<br>";
    } else {
        echo "ℹ️ Le dossier 'uploads/documents/' existe déjà.<br>";
    }

    echo "<p style='color: green; font-weight: bold;'>Setup terminé ! Vous pouvez maintenant utiliser le formulaire de remboursement.</p>";

} catch (PDOException $e) {     // Si une erreur survient, on arrête proprement avec un message d'erreur.
    abort("❌ Erreur : " . $e->getMessage(), 500);
}
?>

<?php
/* ===============================
   SETUP BDD - Restructuration des tables documents
================================ */
$host = "localhost";
$dbname = "fredi";
$user = "root";
$pass = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // 1. Ajouter id_mission et id_remboursement à la table remboursement si nécessaire
    $sql = "ALTER TABLE remboursement ADD COLUMN IF NOT EXISTS id_mission INT;";
    $pdo->exec($sql);
    echo "✅ Colonne 'id_mission' vérifiée dans remboursement.<br>";

    // 1bis. Ajouter les champs adresse et téléphone à la table users si nécessaire
    $sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL;";
    $pdo->exec($sql);
    echo "✅ Colonne 'address' vérifiée dans users.<br>";

    $sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(30) DEFAULT NULL;";
    $pdo->exec($sql);
    echo "✅ Colonne 'phone' vérifiée dans users.<br>";

    // 2. mettre à jour l'enum statut pour correspondre à la nouvelle logique
    $sql = "ALTER TABLE remboursement MODIFY statut ENUM('EN_ATTENTE','ACCEPTEE','REFUSEE','PAYEE') DEFAULT 'EN_ATTENTE';";
    try {
        $pdo->exec($sql);
        echo "✅ Enum 'statut' mise à jour.<br>";
    } catch (Exception $e) {
        echo "ℹ️ Impossible de modifier l'enum 'statut' (peut déjà être à jour).<br>";
    }

    // 2. Créer les tables pour chaque catégorie de dépense
    $categories = [
        'documents_repas_france',
        'documents_repas_etranger',
        'documents_transport',
        'documents_hebergement',
        'documents_parking',
        'documents_carburant',
        'documents_autres_frais'
    ];

    foreach ($categories as $table) {
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id_document INT PRIMARY KEY AUTO_INCREMENT,
            id_remboursement INT NOT NULL,
            id_mission INT,
            nom_fichier VARCHAR(255) NOT NULL,
            chemin_fichier VARCHAR(500) NOT NULL,
            type_fichier VARCHAR(50),
            taille_fichier INT,
            montant DECIMAL(10,2) DEFAULT 0,
            date_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_remboursement) REFERENCES remboursement(id_remboursement) ON DELETE CASCADE,
            FOREIGN KEY (id_mission) REFERENCES mission(id_mission) ON DELETE SET NULL
        ) ENGINE=InnoDB;";
        
        $pdo->exec($sql);
        echo "✅ Table '$table' créée/vérifiée.<br>";
    }

    // 3. Renommer l'ancienne table si elle existe
    $sql = "ALTER TABLE documents_remboursement RENAME TO documents_remboursement_old;";
    try {
        $pdo->exec($sql);
        echo "✅ Ancienne table renommée en 'documents_remboursement_old'.<br>";
    } catch (Exception $e) {
        echo "ℹ️ Table documents_remboursement_old déjà existante ou pas de migration nécessaire.<br>";
    }

    // 4. Créer les indices pour optimiser les recherches
    foreach ($categories as $table) {
        $sql = "ALTER TABLE $table ADD INDEX idx_remboursement (id_remboursement);";
        try {
            $pdo->exec($sql);
        } catch (Exception $e) {
            // L'index existe peut-être déjà
        }

        $sql = "ALTER TABLE $table ADD INDEX idx_mission (id_mission);";
        try {
            $pdo->exec($sql);
        } catch (Exception $e) {
            // L'index existe peut-être déjà
        }
    }

    echo "<p style='color: green; font-weight: bold;'>✅ Structure BDD restructurée avec succès!</p>";
    echo "<p style='color: #0066cc;'><strong>Tables créées :</strong><br>";
    foreach ($categories as $cat) {
        echo "• $cat<br>";
    }
    echo "</p>";
    echo "<p style='color: #666;'><strong>Chaque table contient :</strong><br>
    • id_document (clé primaire)<br>
    • id_remboursement (lié à la demande)<br>
    • id_mission (lié à la mission)<br>
    • nom_fichier, chemin_fichier, type_fichier, taille_fichier<br>
    • montant (montant dépensé pour ce justificatif)<br>
    • date_upload (timestamp)
    </p>";

} catch (PDOException $e) {
    require_once __DIR__ . '/helpers.php';
    abort("❌ Erreur : " . $e->getMessage(), 500);
}
?>

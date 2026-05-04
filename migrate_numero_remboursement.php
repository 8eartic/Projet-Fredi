<?php
/**
 * Migration : Ajouter la colonne numero_remboursement
 * Ce script ajoute une colonne numero_remboursement à la table remboursement
 * pour que chaque compte ait son propre compteur d'ID.
 */

require_once __DIR__ . '/db.php';

try {
    // 1. Vérifier si la colonne existe déjà
    $stmt = $db->query("SHOW COLUMNS FROM remboursement LIKE 'numero_remboursement'");
    $columnExists = $stmt->rowCount() > 0;

    if ($columnExists) {
        echo "✅ La colonne numero_remboursement existe déjà.<br>";
    } else {
        // 2. Ajouter la colonne
        echo "⏳ Ajout de la colonne numero_remboursement...<br>";
        $db->exec("ALTER TABLE remboursement ADD COLUMN numero_remboursement INT DEFAULT NULL AFTER id_remboursement");
        echo "✅ Colonne ajoutée.<br>";

        // 3. Ajouter l'index UNIQUE
        echo "⏳ Ajout de l'index UNIQUE...<br>";
        $db->exec("ALTER TABLE remboursement ADD UNIQUE KEY unique_user_numero (id_utilisateur, numero_remboursement)");
        echo "✅ Index ajouté.<br>";

        // 4. Remplir les numéros existants par utilisateur
        echo "⏳ Remplissage des numéros existants...<br>";
        $users = $db->query("SELECT DISTINCT id_utilisateur FROM remboursement ORDER BY id_utilisateur")->fetchAll(PDO::FETCH_COLUMN);
        
        $count = 0;
        foreach ($users as $user_id) {
            $remboursements = $db->prepare("SELECT id_remboursement FROM remboursement WHERE id_utilisateur = ? ORDER BY id_remboursement ASC");
            $remboursements->execute([$user_id]);
            $remboursements = $remboursements->fetchAll(PDO::FETCH_COLUMN);
            
            $numero = 1;
            foreach ($remboursements as $id) {
                $update = $db->prepare("UPDATE remboursement SET numero_remboursement = ? WHERE id_remboursement = ?");
                $update->execute([$numero, $id]);
                $numero++;
                $count++;
            }
        }
        
        echo "✅ $count remboursements ont reçu un numéro de compte.<br>";
        echo "✅ Migration terminée avec succès !<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?>

<?php
/* ===============================
   SETUP BDD - Mettre à jour la table documents_remboursement
================================ */
require_once __DIR__ . '/helpers.php';
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

    // On travaille désormais sur la table `documents` (unifiée)
    $sql = "ALTER TABLE documents ADD COLUMN IF NOT EXISTS categorie VARCHAR(50) DEFAULT 'autres_frais';";
    $pdo->exec($sql);
    echo "✅ Colonne 'categorie' vérifiée sur documents.<br>";

    $sql = "ALTER TABLE documents ADD COLUMN IF NOT EXISTS montant DECIMAL(10,2) DEFAULT 0;";
    $pdo->exec($sql);
    echo "✅ Colonne 'montant' vérifiée sur documents.<br>";

    echo "<p style='color: green; font-weight: bold;'>Structure BDD mise à jour ! Vous pouvez maintenant utiliser le formulaire amélioré.</p>";

} catch (PDOException $e) {
    abort("❌ Erreur : " . $e->getMessage(), 500);
}
?>

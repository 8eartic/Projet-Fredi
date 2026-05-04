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
    $pdo = new PDO(             //Crée une connexion à la base de données MySQL via PDO (une interface PHP standard pour parler aux bases de données)
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]   // Dit à PDO de lancer une exception (une erreur attrapable) 
        // si quelque chose tourne mal, plutôt que de rater silencieusement.
    );

    // Prépare et exécute une requête SQL qui ajoute la colonne categorie à la table documents
    $sql = "ALTER TABLE documents ADD COLUMN IF NOT EXISTS categorie VARCHAR(50) DEFAULT 'autres_frais';";
    $pdo->exec($sql);
    echo "✅ Colonne 'categorie' vérifiée sur documents.<br>";

    // Prépare et exécute une requête SQL qui ajoute la colonne montant à la table documents
    $sql = "ALTER TABLE documents ADD COLUMN IF NOT EXISTS montant DECIMAL(10,2) DEFAULT 0;";
    $pdo->exec($sql);
    echo "✅ Colonne 'montant' vérifiée sur documents.<br>";

    // Affiche un message final en vert en HTML pour confirmer que tout est bon.
    echo "<p style='color: green; font-weight: bold;'>Structure BDD mise à jour ! Vous pouvez maintenant utiliser le formulaire amélioré.</p>";


//Si une erreur survient pendant la connexion ou l'exécution des requêtes,
// ce bloc intercepte l'erreur et appelle abort() pour afficher un message d'erreur propre avec le code HTTP 500.
} catch (PDOException $e) {
    abort("❌ Erreur : " . $e->getMessage(), 500);
}
?>

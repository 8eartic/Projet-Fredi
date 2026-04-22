<?php
header('Content-Type: application/json');

// utiliser la connexion communes
require_once __DIR__ . '/db.php';
$pdo = $db; // nom historique

// en cas d'erreur de connexion, db.php gère déjà l'abandon

if (!isset($_GET['id_remboursement'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

$id_remboursement = (int)$_GET['id_remboursement'];

// Initialiser les catégories
$documents = [];
$categories = ['repas_france', 'repas_etranger', 'transport', 'hebergement', 'parking', 'carburant', 'autres_frais'];
foreach ($categories as $cat) {
    $documents[$cat] = [];
}

// Récupérer tous les documents
$stmt = $pdo->prepare("SELECT * FROM documents WHERE id_remboursement = ? ORDER BY categorie, date_upload DESC");
$stmt->execute([$id_remboursement]);

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $doc) {
    if (isset($documents[$doc['categorie']])) {
        $documents[$doc['categorie']][] = $doc;
    }
}

echo json_encode($documents);
?>


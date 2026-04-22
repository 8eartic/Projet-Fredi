<?php
/* ===============================
   API - RÉCUPÉRATION DES DOCUMENTS D'UNE DEMANDE
================================ */
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';
$pdo = $db;

// db.php s'occupe de l'erreur si la connexion échoue

if (!isset($_GET['id_remboursement'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID remboursement manquant']);
    exit;
}

$id_remboursement = (int)$_GET['id_remboursement'];

// Récupérer tous les documents pour cette demande
$stmt = $pdo->prepare("SELECT * FROM documents WHERE id_remboursement = ? ORDER BY categorie, date_upload DESC");
$stmt->execute([$id_remboursement]);
$all_docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Grouper par catégorie
$documents = [
    'repas_france' => [],
    'repas_etranger' => [],
    'transport' => [],
    'hebergement' => [],
    'parking' => [],
    'carburant' => [],
    'autres_frais' => []
];

foreach ($all_docs as $doc) {
    if (isset($documents[$doc['categorie']])) {
        $documents[$doc['categorie']][] = $doc;
    }
}

// Calculer les totaux par catégorie
$totals = [];
foreach ($documents as $categorie => $docs) {
    $total = 0;
    foreach ($docs as $doc) {
        $total += (float)$doc['montant'];
    }
    $totals[$categorie] = $total;
}

echo json_encode([
    'documents' => $documents,
    'totals' => $totals,
    'grand_total' => array_sum($totals)
]);
?>

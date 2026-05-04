<?php
/* ===============================
   API DOCUMENTS - GESTION CENTRALISÉE
   Récupération des documents de remboursement
================================ */
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';
$pdo = $db;

if (!isset($_GET['id_remboursement'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID remboursement manquant']);
    exit;
}

$id_remboursement = (int)$_GET['id_remboursement'];

// Fonction helper pour les catégories
function getDocumentCategories(): array {
    return ['transport', 'hebergement', 'parking', 'carburant', 'autres_frais'];
}

// Fonction principale de récupération
function fetchDocumentsByRemboursement(PDO $pdo, int $id_remboursement): array {
    $documents = [];
    foreach (getDocumentCategories() as $cat) {
        $documents[$cat] = [];
    }

    $stmt = $pdo->prepare("SELECT * FROM documents WHERE id_remboursement = ? ORDER BY categorie, date_upload DESC");
    $stmt->execute([$id_remboursement]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $doc) {
        if (isset($documents[$doc['categorie']])) {
            $documents[$doc['categorie']][] = $doc;
        }
    }

    $totals = [];
    foreach ($documents as $categorie => $docs) {
        $total = 0;
        foreach ($docs as $doc) {
            $total += (float) $doc['montant'];
        }
        $totals[$categorie] = $total;
    }

    return [
        'documents' => $documents,
        'totals' => $totals,
        'grand_total' => array_sum($totals),
    ];
}

// Récupération des données
$result = fetchDocumentsByRemboursement($pdo, $id_remboursement);

// Selon le paramètre 'format', retourner les données appropriées
$format = $_GET['format'] ?? 'full'; // 'full' ou 'documents_only'

if ($format === 'documents_only') {
    echo json_encode($result['documents']);
} else {
    echo json_encode($result);
}
?>

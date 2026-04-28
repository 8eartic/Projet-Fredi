<?php
// tresorier_reporting.php - écran de synthèse comptable pour le trésorier.
// Il affiche les montants validés, les rapports par catégorie et les données
// par période, afin de préparer la comptabilité de fin d'année.

session_start();

if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'tresorier') {
    // Seul le trésorier a accès à cette page.
    header('Location: auth_login.php');
    exit;
}

require 'db.php';

// On récupère ensuite les filtres éventuels et on prépare les requêtes.
// Ces requêtes utilisent YEAR/MONTH sur la date de demande afin de fournir
// des statistiques temporelles et par ligue.

$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? null;
$league = $_GET['league'] ?? null;
$export_format = $_POST['export_format'] ?? null;

// ======================================
// RÉCUPÉRER LES DONNÉES DE RAPPORT
// ======================================

$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? null;
$league = $_GET['league'] ?? null;
$export_format = $_POST['export_format'] ?? null;

try {
    // -- Statistiques globales
    $stats_query = "SELECT 
        COUNT(DISTINCT r.id_remboursement) as total_reports,
        SUM(CASE WHEN r.validation_status = 'valide' THEN 1 ELSE 0 END) as validated_reports,
        SUM(CASE WHEN r.validation_status = 'rejete' THEN 1 ELSE 0 END) as rejected_reports,
        SUM(CASE WHEN r.validation_status = 'soumis' THEN 1 ELSE 0 END) as pending_reports,
        SUM(d.montant) as total_amount,
        SUM(CASE WHEN r.validation_status = 'valide' THEN d.montant ELSE 0 END) as validated_amount,
        SUM(CASE WHEN r.validation_status = 'soumis' THEN d.montant ELSE 0 END) as pending_amount
    FROM remboursement r
    LEFT JOIN documents d ON r.id_remboursement = d.id_remboursement
    WHERE YEAR(r.date_demande) = :year";
    
    $params = [':year' => $year];
    
    if ($month) {
        $stats_query .= " AND MONTH(r.date_demande) = :month";
        $params[':month'] = $month;
    }
    
    $stmt = $db->prepare($stats_query);
    $stmt->execute($params);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // -- Détail par catégorie
    $category_query = "SELECT 
        d.categorie,
        COUNT(DISTINCT r.id_remboursement) as report_count,
        SUM(d.montant) as category_total,
        SUM(CASE WHEN r.validation_status = 'valide' THEN d.montant ELSE 0 END) as validated_total
    FROM remboursement r
    LEFT JOIN documents d ON r.id_remboursement = d.id_remboursement
    WHERE YEAR(r.date_demande) = :year
    AND d.categorie IS NOT NULL";
    
    $params_cat = [':year' => $year];
    
    if ($month) {
        $category_query .= " AND MONTH(r.date_demande) = :month";
        $params_cat[':month'] = $month;
    }
    
    $category_query .= " GROUP BY d.categorie ORDER BY category_total DESC";
    
    $stmt_cat = $db->prepare($category_query);
    $stmt_cat->execute($params_cat);
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
    
    // -- Détail par ligue
    $league_query = "SELECT 
        YEAR(r.date_demande) as year,
        MONTH(r.date_demande) as month,
        COUNT(DISTINCT r.id_remboursement) as report_count,
        SUM(d.montant) as league_total,
        SUM(CASE WHEN r.validation_status = 'valide' THEN d.montant ELSE 0 END) as validated_total
    FROM remboursement r
    LEFT JOIN documents d ON r.id_remboursement = d.id_remboursement
    WHERE YEAR(r.date_demande) = :year";
    
    $params_league = [':year' => $year];
    
    if ($month) {
        $league_query .= " AND MONTH(r.date_demande) = :month";
        $params_league[':month'] = $month;
    }
    
    $league_query .= " GROUP BY YEAR(r.date_demande), MONTH(r.date_demande) ORDER BY league_total DESC";
    
    $stmt_league = $db->prepare($league_query);
    $stmt_league->execute($params_league);
    $leagues = $stmt_league->fetchAll(PDO::FETCH_ASSOC);
    
    // -- Détail par mois (pour graphique)
    $monthly_query = "SELECT 
        DATE_FORMAT(r.date_demande, '%m') as month_num,
        DATE_FORMAT(r.date_demande, '%B') as month_name,
        COUNT(DISTINCT r.id_remboursement) as report_count,
        SUM(d.montant) as month_total,
        SUM(CASE WHEN r.validation_status = 'valide' THEN d.montant ELSE 0 END) as validated_total
    FROM remboursement r
    LEFT JOIN documents d ON r.id_remboursement = d.id_remboursement
    WHERE YEAR(r.date_demande) = :year";
    
    $params_month = [':year' => $year];
    
    $monthly_query .= " GROUP BY MONTH(r.date_demande) ORDER BY month_num";
    
    $stmt_month = $db->prepare($monthly_query);
    $stmt_month->execute($params_month);
    $monthly = $stmt_month->fetchAll(PDO::FETCH_ASSOC);
    
    // -- Liste détaillée des bordereaux avec qui les a créés
    $detail_reports_query = "SELECT 
        r.id_remboursement,
        r.date_demande,
        r.statut,
        r.total,
        u.prenom,
        u.nom,
        u.email
    FROM remboursement r
    LEFT JOIN users u ON r.id_utilisateur = u.id
    WHERE YEAR(r.date_demande) = :year";
    
    $params_detail = [':year' => $year];
    
    if ($month) {
        $detail_reports_query .= " AND MONTH(r.date_demande) = :month";
        $params_detail[':month'] = $month;
    }
    
    $detail_reports_query .= " ORDER BY r.date_demande DESC";
    
    $stmt_detail = $db->prepare($detail_reports_query);
    $stmt_detail->execute($params_detail);
    $detail_reports = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);
    
    // -- Lister les ligues pour le filtre
    $leagues_list_query = "SELECT COUNT(DISTINCT id_remboursement) as report_count FROM remboursement WHERE id_remboursement IS NOT NULL ORDER BY report_count DESC";
    $stmt_leagues_list = $db->prepare($leagues_list_query);
    $stmt_leagues_list->execute();
    $leagues_available = [];
    
    $league = null;
    
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur lors du chargement des rapports: " . $e->getMessage();
}

// ======================================
// EXPORT CSV
// ======================================

if ($export_format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rapports_tresorier_' . $year . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes CSV avec BOM UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM pour Excel
    
    // Section 1: Statistiques globales
    fputcsv($output, ['STATISTIQUES GLOBALES', $year], ';');
    fputcsv($output, ['Rapports totaux', $stats['total_reports'] ?? 0], ';');
    fputcsv($output, ['Rapports validés', $stats['validated_reports'] ?? 0], ';');
    fputcsv($output, ['Rapports rejetés', $stats['rejected_reports'] ?? 0], ';');
    fputcsv($output, ['Rapports en attente', $stats['pending_reports'] ?? 0], ';');
    fputcsv($output, ['Montant total (€)', number_format($stats['total_amount'] ?? 0, 2, ',', ' ')], ';');
    fputcsv($output, ['Montant validé (€)', number_format($stats['validated_amount'] ?? 0, 2, ',', ' ')], ';');
    fputcsv($output, ['Montant en attente (€)', number_format($stats['pending_amount'] ?? 0, 2, ',', ' ')], ';');
    fputcsv($output, [''], ';');
    
    // Section 2: Détail par catégorie
    fputcsv($output, ['DÉTAIL PAR CATÉGORIE'], ';');
    fputcsv($output, ['Catégorie', 'Nombre de rapports', 'Montant total (€)', 'Montant validé (€)'], ';');
    
    foreach ($categories as $cat) {
        fputcsv($output, [
            $cat['categorie'],
            $cat['report_count'],
            number_format($cat['category_total'], 2, ',', ' '),
            number_format($cat['validated_total'], 2, ',', ' ')
        ], ';');
    }
    
    fputcsv($output, [''], ';');
    
    // Section 3: Détail par ligue
    fputcsv($output, ['DÉTAIL PAR LIGUE'], ';');
    fputcsv($output, ['Ligue', 'Nombre de rapports', 'Montant total (€)', 'Montant validé (€)'], ';');
    
    foreach ($leagues as $l) {
        fputcsv($output, [
            $l['league_name'],
            $l['report_count'],
            number_format($l['league_total'], 2, ',', ' '),
            number_format($l['validated_total'], 2, ',', ' ')
        ], ';');
    }
    
    fclose($output);
    exit;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports Comptables - FREDI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 28px;
        }
        
        .user-info {
            text-align: right;
            font-size: 14px;
        }
        
        .user-info strong {
            display: block;
            color: #2c3e50;
        }
        
        .user-info .logout {
            color: #e74c3c;
            text-decoration: none;
            margin-top: 5px;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 13px;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 8px 10px;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .filter-actions button {
            padding: 8px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
        }
        
        .filter-actions button:hover {
            background: #2980b9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
        }
        
        .stat-card.total {
            border-left-color: #e74c3c;
        }
        
        .stat-card.validated {
            border-left-color: #27ae60;
        }
        
        .stat-card.pending {
            border-left-color: #f39c12;
        }
        
        .stat-label {
            font-size: 12px;
            color: #95a5a6;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .table-title {
            background: #34495e;
            color: white;
            padding: 15px 20px;
            font-size: 16px;
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #ecf0f1;
        }
        
        th {
            padding: 12px 15px;
            text-align: left;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #bdc3c7;
            font-size: 13px;
        }
        
        td {
            padding: 10px 15px;
            border-bottom: 1px solid #ecf0f1;
            font-size: 13px;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .amount {
            text-align: right;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .export-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .export-section h3 {
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .export-button {
            display: inline-block;
            padding: 10px 20px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
        }
        
        .export-button:hover {
            background: #229954;
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
        }
        
        .message.error {
            background: #fadbd8;
            color: #c0392b;
            display: block;
        }
        
        .message.success {
            background: #d5f4e6;
            color: #27ae60;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- ENTÊTE -->
        <div class="header">
            <h1>📊 Rapports Comptables</h1>
            <div class="user-info">
                <strong><?= htmlspecialchars($_SESSION['utilisateur']['prenom'] . ' ' . $_SESSION['utilisateur']['nom']) ?></strong>
                <small>Trésorier</small>
                <a href="auth_logout.php" class="logout">Déconnexion</a>
            </div>
        </div>
        
        
        
        <!-- STATISTIQUES -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Rapports Totaux</div>
                <div class="stat-value"><?= $stats['total_reports'] ?? 0 ?></div>
            </div>
            
            <div class="stat-card validated">
                <div class="stat-label">✅ Validés</div>
                <div class="stat-value"><?= $stats['validated_reports'] ?? 0 ?></div>
            </div>
            
            <div class="stat-card pending">
                <div class="stat-label">⏳ En Attente</div>
                <div class="stat-value"><?= $stats['pending_reports'] ?? 0 ?></div>
            </div>
            
            <div class="stat-card" style="border-left-color: #c0392b;">
                <div class="stat-label">❌ Rejetés</div>
                <div class="stat-value"><?= $stats['rejected_reports'] ?? 0 ?></div>
            </div>
            
            <div class="stat-card total" style="grid-column: span 2;">
                <div class="stat-label">💰 Montant Total</div>
                <div class="stat-value"><?= number_format($stats['total_amount'] ?? 0, 2) ?> €</div>
            </div>
        </div>
        
        <!-- DÉTAIL PAR CATÉGORIE -->
        <div class="table-container">
            <div class="table-title">📂 Détail par Catégorie</div>
            <table>
                <thead>
                    <tr>
                        <th>Catégorie</th>
                        <th>Rapports</th>
                        <th class="amount">Montant Total (€)</th>
                        <th class="amount">Montant Validé (€)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $cat['categorie']))) ?></td>
                            <td><?= $cat['report_count'] ?></td>
                            <td class="amount"><?= number_format($cat['category_total'] ?? 0, 2) ?></td>
                            <td class="amount"><?= number_format($cat['validated_total'] ?? 0, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        
        <!-- DÉTAIL PAR MOIS -->
        <?php if (!empty($monthly)): ?>
            <div class="table-container">
                <div class="table-title">📅 Détail par Mois</div>
                <table>
                    <thead>
                        <tr>
                            <th>Mois</th>
                            <th>Rapports</th>
                            <th class="amount">Montant Total (€)</th>
                            <th class="amount">Montant Validé (€)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthly as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['month_name']) ?></td>
                                <td><?= $m['report_count'] ?></td>
                                <td class="amount"><?= number_format($m['month_total'] ?? 0, 2) ?></td>
                                <td class="amount"><?= number_format($m['validated_total'] ?? 0, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- DÉTAIL DES BORDEREAUX INDIVIDUELS -->
        <?php if (!empty($detail_reports)): ?>
            <div class="table-container">
                <div class="table-title">📋 Détail des Bordereaux - Créé par</div>
                <table>
                    <thead>
                        <tr>
                            <th>ID Bordereau</th>
                            <th>Date</th>
                            <th>Créé par (Nom)</th>
                            <th>Email</th>
                            <th>Montant (€)</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detail_reports as $report): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($report['id_remboursement']) ?></td>
                                <td><?= (new DateTime($report['date_demande']))->format('d/m/Y H:i') ?></td>
                                <td><?= htmlspecialchars($report['prenom'] . ' ' . $report['nom']) ?></td>
                                <td><?= htmlspecialchars($report['email'] ?? '-') ?></td>
                                <td class="amount"><?= number_format($report['total'] ?? 0, 2) ?></td>
                                <td>
                                    <?php
                                        $statutColor = [
                                            'EN_ATTENTE' => '#ff9800',
                                            'ACCEPTEE' => '#4caf50',
                                            'REFUSEE' => '#f44336',
                                            'PAYEE' => '#2196f3'
                                        ];
                                        $color = $statutColor[$report['statut']] ?? '#999';
                                    ?>
                                    <span style="padding: 4px 8px; background: <?= $color ?>15; color: <?= $color ?>; border-radius: 4px; font-weight: 600; font-size: 12px;">
                                        <?= htmlspecialchars($report['statut']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- EXPORT -->
        <div class="export-section">
            <h3>📥 Exporter les Rapports</h3>
            <form method="POST">
                <input type="hidden" name="year" value="<?= $year ?>">
                <input type="hidden" name="month" value="<?= $month ?? '' ?>">
                <input type="hidden" name="league" value="<?= $league ?? '' ?>">
                <input type="hidden" name="export_format" value="csv">
                <button type="submit" class="export-button">📊 Télécharger en CSV (Excel)</button>
            </form>
        </div>
    </div>
</body>
</html>

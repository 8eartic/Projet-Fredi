<?php
/* ===============================
   TRESORIER DASHBOARD
   Tableau de bord trésorier
   - Liste des bordereaux
   - Statut de validation
   - Actions rapides
================================ */

session_start();
require_once __DIR__ . '/db.php';

// Vérifier authentification
if (empty($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'tresorier') {
    header('Location: index.php?error=auth');
    exit;
}

$tresorier_id = (int) $_SESSION['utilisateur']['id'];
$tresorier_name = $_SESSION['utilisateur']['prenom'] . ' ' . $_SESSION['utilisateur']['nom'];
$league_id = $_SESSION['utilisateur']['league_id'] ?? null;

// ======================================
// STATISTIQUES GLOBALES
// ======================================

$stats = [
    'total_reports' => 0,
    'submitted' => 0,
    'validated' => 0,
    'rejected' => 0,
    'total_amount' => 0.00
];

try {
    // Total reports for this league/club
    $query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN validation_status = 'soumis' THEN 1 ELSE 0 END) as submitted,
        SUM(CASE WHEN validation_status = 'valide' THEN 1 ELSE 0 END) as validated,
        SUM(CASE WHEN validation_status = 'rejete' THEN 1 ELSE 0 END) as rejected,
        SUM(total) as total_amount
    FROM remboursement r
    JOIN users u ON r.id_utilisateur = u.id
    WHERE DATE_FORMAT(r.date_demande, '%Y') = YEAR(NOW())";
    
    if ($league_id) {
        $query .= " AND u.league_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$league_id]);
    } else {
        $stmt = $db->prepare($query);
        $stmt->execute();
    }
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $stats['total_reports'] = (int) $row['total'];
        $stats['submitted'] = (int) ($row['submitted'] ?? 0);
        $stats['validated'] = (int) ($row['validated'] ?? 0);
        $stats['rejected'] = (int) ($row['rejected'] ?? 0);
        $stats['total_amount'] = (float) ($row['total_amount'] ?? 0);
    }
} catch (Exception $e) {
    error_log('Dashboard stats error: ' . $e->getMessage());
}

// ======================================
// LISTE DES BORDEREAUX A TRAITER
// ======================================

$reports = [];
try {
    $query = "SELECT 
        r.id_remboursement,
        u.id,
        u.first_name,
        u.last_name,
        u.email,
        u.license_number,
        u.league_name,
        r.total,
        r.validation_status,
        r.date_demande,
        r.submitted_date,
        COUNT(d.id_document) as docs_count
    FROM remboursement r
    JOIN users u ON r.id_utilisateur = u.id
    LEFT JOIN documents d ON r.id_remboursement = d.id_remboursement
    WHERE r.validation_status IN ('soumis', 'en_revision')
    AND YEAR(r.date_demande) = YEAR(NOW())";
    
    if ($league_id) {
        $query .= " AND u.league_id = ?";
    }
    
    $query .= " GROUP BY r.id_remboursement
    ORDER BY r.submitted_date ASC, r.date_demande DESC";
    
    if ($league_id) {
        $stmt = $db->prepare($query);
        $stmt->execute([$league_id]);
    } else {
        $stmt = $db->prepare($query);
        $stmt->execute();
    }
    
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Reports fetch error: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FREDI - Tableau de Bord Trésorier</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        .navbar {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .navbar h1 {
            font-size: 24px;
            color: #2c3e50;
        }
        
        .user-info {
            font-size: 14px;
            color: #666;
        }
        
        .user-info strong {
            color: #2c3e50;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .stat-card.warning {
            border-left-color: #f39c12;
        }
        
        .stat-card.success {
            border-left-color: #27ae60;
        }
        
        .stat-card.danger {
            border-left-color: #e74c3c;
        }
        
        .stat-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-value.currency::after {
            content: ' €';
            font-size: 20px;
        }
        
        /* Reports Table */
        .reports-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .section-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .section-header h2 {
            font-size: 18px;
            color: #2c3e50;
        }
        
        .section-content {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8f9fa;
            border-bottom: 2px solid #ddd;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #666;
            font-size: 13px;
            text-transform: uppercase;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-soumis {
            background: #ecf0f1;
            color: #34495e;
        }
        
        .status-en_revision {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-valide {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rejete {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background: #d68910;
        }
        
        .empty-state {
            padding: 40px;
            text-align: center;
            color: #999;
        }
        
        .empty-state p {
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        
        .logout-btn:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🏆 FREDI - Tableau de Bord Trésorier</h1>
        <div style="display: flex; align-items: center; gap: 20px;">
            <div class="user-info">
                Connecté en tant que: <strong><?= htmlspecialchars($tresorier_name) ?></strong>
            </div>
            <a href="tresorier_reporting.php" style="color: white; text-decoration: none; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 4px; font-weight: bold;">📊 Rapports</a>
            <form method="POST" action="auth_logout.php" style="display: inline;">
                <button type="submit" class="logout-btn">Déconnexion</button>
            </form>
        </div>
    </div>
    
    <div class="container">
        <!-- STATISTIQUES -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Bordereaux</div>
                <div class="stat-value"><?= $stats['total_reports'] ?></div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-label">À Traiter</div>
                <div class="stat-value"><?= $stats['submitted'] + $stats['rejected'] ?></div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-label">Validés</div>
                <div class="stat-value"><?= $stats['validated'] ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Total des Montants</div>
                <div class="stat-value currency"><?= number_format($stats['total_amount'], 2) ?></div>
            </div>
        </div>
        
        <!-- LISTE DES BORDEREAUX -->
        <div class="reports-section">
            <div class="section-header">
                <h2>📋 Bordereaux à Valider</h2>
            </div>
            
            <div class="section-content">
                <?php if (empty($reports)): ?>
                    <div class="empty-state">
                        <p>✅ Aucun bordereau à traiter pour le moment</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Adhérent</th>
                                <th>N° Licence</th>
                                <th>Ligue</th>
                                <th>Montant Total</th>
                                <th>Documents</th>
                                <th>Statut</th>
                                <th>Soumis le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($report['first_name'] . ' ' . $report['last_name']) ?></strong><br>
                                        <small style="color: #999;"><?= htmlspecialchars($report['email']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($report['license_number'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($report['league_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <strong><?= number_format($report['total'], 2) ?> €</strong>
                                    </td>
                                    <td>
                                        <span style="background: #ecf0f1; padding: 4px 8px; border-radius: 3px;">
                                            <?= $report['docs_count'] ?> doc(s)
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= str_replace('_', '', $report['validation_status']) ?>">
                                            <?= ucfirst(str_replace('_', ' ', $report['validation_status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $report['submitted_date'] ? date('d/m/Y H:i', strtotime($report['submitted_date'])) : 'N/A' ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="tresorier_validate.php?id=<?= $report['id_remboursement'] ?>" class="btn btn-primary">
                                                ✓ Valider
                                            </a>
                                            <a href="tresorier_detail.php?id=<?= $report['id_remboursement'] ?>" class="btn btn-warning">
                                                📄 Détails
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

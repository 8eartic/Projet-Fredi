<?php
// ======================================
// MES CERFAS - PAGE ADHÉRENT
// ======================================

session_start();

// Vérifier l'authentification
if (!isset($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'adherent') {
    header('Location: auth_login.php');
    exit;
}

require 'db.php';
require 'header.php';

$user_id = $_SESSION['utilisateur']['id'];
$error = '';
$success = '';

try {
    // Récupérer les CERFAs de l'adhérent
    // Les CERFAs sont générés sur les remboursements validés avec des dons
    $stmt = $db->prepare("
        SELECT 
            cr.id_cerfa,
            cr.cerfa_number,
            cr.issued_date,
            cr.total_amount,
            cr.status,
            r.id_remboursement,
            u.first_name,
            u.last_name,
            u.league_name
        FROM cerfa_receipts cr
        JOIN remboursement r ON cr.id_remboursement = r.id_remboursement
        JOIN users u ON r.id_utilisateur = u.id
        WHERE r.id_utilisateur = ?
        ORDER BY cr.issued_date DESC
    ");
    $stmt->execute([$user_id]);
    $cerfas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les informations utilisateur
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Erreur lors du chargement des CERFAs: " . $e->getMessage();
}

// Traiter les actions (télécharger, imprimer)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
    $cerfa_id = (int) ($_GET['cerfa_id'] ?? 0);
    
    if ($action === 'download' && $cerfa_id > 0) {
        try {
            $stmt = $db->prepare("
                SELECT cr.*, r.id_remboursement
                FROM cerfa_receipts cr
                JOIN remboursement r ON cr.id_remboursement = r.id_remboursement
                WHERE cr.id_cerfa = ? AND r.id_utilisateur = ?
            ");
            $stmt->execute([$cerfa_id, $user_id]);
            $cerfa = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cerfa) {
                // Rediriger vers le générateur de CERFA
                header('Location: cerfa_generator.php?id=' . $cerfa['id_remboursement'] . '&cerfa_id=' . $cerfa_id . '&download=1');
                exit;
            }
        } catch (Exception $e) {
            $error = "Erreur lors du téléchargement: " . $e->getMessage();
        }
    }
    
    if ($action === 'view' && $cerfa_id > 0) {
        try {
            $stmt = $db->prepare("
                SELECT cr.*, r.id_remboursement
                FROM cerfa_receipts cr
                JOIN remboursement r ON cr.id_remboursement = r.id_remboursement
                WHERE cr.id_cerfa = ? AND r.id_utilisateur = ?
            ");
            $stmt->execute([$cerfa_id, $user_id]);
            $cerfa = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cerfa) {
                // Rediriger vers le générateur de CERFA pour affichage
                header('Location: cerfa_generator.php?id=' . $cerfa['id_remboursement'] . '&cerfa_id=' . $cerfa_id);
                exit;
            }
        } catch (Exception $e) {
            $error = "Erreur lors de l'affichage: " . $e->getMessage();
        }
    }
}

// Fonction pour convertir montant en lettres
function montantEnLettres($montant) {
    $unite = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf'];
    $dix = ['dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
    $dizaine = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante-dix', 'quatre-vingt', 'quatre-vingt-dix'];
    
    $montant = (int) $montant;
    $cents = (int) ($montant / 100);
    $reste = $montant % 100;
    
    $result = '';
    
    if ($cents > 0) {
        if ($cents === 1) {
            $result .= 'cent';
        } else {
            $result .= $unite[$cents] . ' cents';
        }
    }
    
    if ($reste > 0) {
        if ($cents > 0) $result .= ' ';
        
        $dix_num = (int) ($reste / 10);
        $unite_num = $reste % 10;
        
        if ($dix_num === 1) {
            $result .= $dix[$unite_num];
        } else {
            $result .= $dizaine[$dix_num];
            if ($unite_num > 0) {
                $result .= '-' . $unite[$unite_num];
            }
        }
    }
    
    return ucfirst($result) . ' euros';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes CERFAs</title>
    <style>
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #007bff;
        }
        
        .page-header h1 {
            margin: 0;
            color: #333;
            font-size: 28px;
        }
        
        .page-header .icon {
            font-size: 32px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #999;
            margin: 0;
        }
        
        .cerfas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .cerfa-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .cerfa-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-color: #007bff;
        }
        
        .cerfa-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .cerfa-number {
            font-weight: bold;
            font-size: 14px;
            color: #007bff;
        }
        
        .cerfa-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .cerfa-status.draft {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .cerfa-status.issued {
            background-color: #d4edda;
            color: #155724;
        }
        
        .cerfa-status.archived {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .cerfa-card-body {
            margin: 15px 0;
        }
        
        .cerfa-info {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 14px;
        }
        
        .cerfa-info-label {
            color: #666;
        }
        
        .cerfa-info-value {
            font-weight: bold;
            color: #333;
        }
        
        .cerfa-amount {
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
            margin: 15px 0;
        }
        
        .cerfa-date {
            font-size: 13px;
            color: #999;
        }
        
        .cerfa-card-footer {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0056b3;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .info-box h4 {
            margin-top: 0;
            color: #004085;
        }
        
        .info-box p {
            margin: 5px 0;
            color: #004085;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="page-header">
        <span class="icon">📋</span>
        <h1>Mes CERFAs</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <strong>Erreur :</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>Succès :</strong> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <div class="info-box">
        <h4>ℹ️ Qu'est-ce qu'un CERFA ?</h4>
        <p>
            Un CERFA 11580-02 est un reçu officiel de don qui vous permet de bénéficier d'une réduction d'impôt 
            de <strong>66%</strong> sur le montant donné à l'association (dans la limite de 20% de vos revenus).
        </p>
        <p>
            Conservez ces documents pour votre déclaration fiscale.
        </p>
    </div>
    
    <?php if (empty($cerfas)): ?>
        <div class="empty-state">
            <h3>Aucun CERFA pour le moment</h3>
            <p>Vous n'avez pas encore de reçu disponible.</p>
            <p>Les CERFAs seront générés une fois que le trésorier valide vos remboursements avec dons.</p>
        </div>
    <?php else: ?>
        <div class="cerfas-grid">
            <?php foreach ($cerfas as $cerfa): ?>
                <div class="cerfa-card">
                    <div class="cerfa-card-header">
                        <div class="cerfa-number"><?= htmlspecialchars($cerfa['cerfa_number']) ?></div>
                        <span class="cerfa-status <?= strtolower($cerfa['status']) ?>">
                            <?php
                            $status_labels = [
                                'draft' => 'Brouillon',
                                'issued' => 'Émis',
                                'archived' => 'Archivé'
                            ];
                            echo $status_labels[strtolower($cerfa['status'])] ?? ucfirst($cerfa['status']);
                            ?>
                        </span>
                    </div>
                    
                    <div class="cerfa-card-body">
                        <div class="cerfa-info">
                            <span class="cerfa-info-label">Montant :</span>
                            <span class="cerfa-info-value"><?= number_format($cerfa['total_amount'], 2, ',', ' ') ?> €</span>
                        </div>
                        <div class="cerfa-date">
                            Émis le <?= date('d/m/Y', strtotime($cerfa['issued_date'])) ?>
                        </div>
                        <div class="cerfa-date" style="margin-top: 5px;">
                            Réduction fiscale estimée : <strong><?= number_format($cerfa['total_amount'] * 0.66, 2, ',', ' ') ?> €</strong>
                        </div>
                    </div>
                    
                    <div class="cerfa-card-footer">
                        <a href="mes_cerfas.php?action=view&cerfa_id=<?= $cerfa['id_cerfa'] ?>" class="btn btn-primary">
                            👁️ Voir
                        </a>
                        <a href="mes_cerfas.php?action=download&cerfa_id=<?= $cerfa['id_cerfa'] ?>" class="btn btn-secondary">
                            ⬇️ Télécharger
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center;">
        <a href="Formulaire_remboursement.php" style="color: #007bff; text-decoration: none;">
            ← Retour aux remboursements
        </a>
    </div>
</div>

</body>
</html>

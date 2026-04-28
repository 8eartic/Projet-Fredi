<?php
/* ===============================
   PAGE DE VALIDATION DES BORDEREAUX
   Affiche les détails + permet validation ligne par ligne
================================ */

session_start();
require_once __DIR__ . '/db.php';

// Cette page est réservée aux trésoriers.
// Elle permet de corriger, valider et rejeter les notes de frais.
if (empty($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'tresorier') {
    header('Location: index.php?error=auth');
    exit;
}

$tresorier_id = (int) $_SESSION['utilisateur']['id'];
$report_id = (int) ($_GET['id'] ?? 0);

if ($report_id <= 0) {
    header('Location: tresorier_dashboard.php');
    exit;
}

// ======================================
// CHARGER BORDEREAU + DETAILS
// ======================================

$report = null;
$lines = [];
$documents = [];

try {
    // Récupérer le bordereau principal
    $stmt = $db->prepare("
        SELECT r.*, u.first_name, u.last_name, u.email, u.license_number, u.league_name
        FROM remboursement r
        JOIN users u ON r.id_utilisateur = u.id
        WHERE r.id_remboursement = ?
    ");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        header('Location: tresorier_dashboard.php?error=not_found');
        exit;
    }
    
    // Récupérer les documents associés
    $stmt = $db->prepare("
        SELECT * FROM documents
        WHERE id_remboursement = ?
        ORDER BY date_upload DESC
    ");
    $stmt->execute([$report_id]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log('Report load error: ' . $e->getMessage());
    $error = "Erreur lors du chargement du bordereau";
}

// ======================================
// TRAITER LES VALIDATIONS (POST)
// ======================================

$validation_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'validate_line') {
            // Valider ou corriger une ligne de justificatif.
            // Le trésorier peut modifier le montant et changer le statut.
            $doc_id = (int) $_POST['doc_id'];
            $new_amount = (float) $_POST['amount'];
            $status = $_POST['status'] ?? 'accepte';
            
            $stmt = $db->prepare("
                UPDATE documents 
                SET montant = ?, validation_status = ?
                WHERE id_document = ? AND id_remboursement = ?
            ");
            $stmt->execute([$new_amount, $status, $doc_id, $report_id]);
            
            $validation_message = "✅ Ligne mise à jour";
            
        } elseif ($action === 'validate_report') {
            // Validation finale du bordereau.
            // On recalcule le total avec les lignes acceptées seulement.
            $notes = $_POST['notes'] ?? '';
            
            $stmt = $db->prepare("
                SELECT SUM(montant) as total FROM documents 
                WHERE id_remboursement = ? AND validation_status = 'accepte'
            ");
            $stmt->execute([$report_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $new_total = (float) ($result['total'] ?? 0);
            
            $stmt = $db->prepare("
                UPDATE remboursement 
                SET validation_status = 'valide', 
                    validated_date = NOW(),
                    validated_by = ?,
                    validation_notes = ?,
                    total = ?
                WHERE id_remboursement = ?
            ");
            $stmt->execute([$tresorier_id, $notes, $new_total, $report_id]);
            
            // Historique d'audit pour suivre qui a validé et quel montant a été retenu.
            $stmt = $db->prepare("
                INSERT INTO validation_history 
                (id_remboursement, id_utilisateur, action, montant_initial, montant_final, notes)
                VALUES (?, ?, 'VALIDATED', ?, ?, ?)
            ");
            $stmt->execute([$report_id, $tresorier_id, $report['total'], $new_total, $notes]);
            
            $validation_message = "✅ Bordereau validé avec succès!";
            
            header('Location: tresorier_detail.php?id=' . $report_id . '&success=1');
            exit;
            
        } elseif ($action === 'reject_report') {
            // Rejet du bordereau entier avec commentaire.
            $notes = $_POST['notes'] ?? 'Bordereau rejeté';
            
            $stmt = $db->prepare("
                UPDATE remboursement 
                SET validation_status = 'rejete',
                    validation_notes = ?
                WHERE id_remboursement = ?
            ");
            $stmt->execute([$notes, $report_id]);
            
            $validation_message = "⚠️ Bordereau rejeté";
            
            header('Location: tresorier_dashboard.php?success=1');
            exit;
        }
    } catch (Exception $e) {
        $validation_message = "❌ Erreur: " . $e->getMessage();
    }
}

// Reload des données après changement
if ($validation_message) {
    $stmt = $db->prepare("SELECT * FROM remboursement WHERE id_remboursement = ?");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT * FROM documents WHERE id_remboursement = ? ORDER BY date_upload DESC");
    $stmt->execute([$report_id]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FREDI - Validation Bordereau</title>
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
        }
        
        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h2 {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .adherer-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
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
        
        .documents-section {
            padding: 20px;
        }
        
        .doc-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
        }
        
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .doc-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .doc-form {
            display: grid;
            grid-template-columns: 1fr 150px 150px;
            gap: 10px;
            align-items: end;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 4px;
        }
        
        input, select {
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 4px rgba(52, 152, 219, 0.2);
        }
        
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
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
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .actions {
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .message {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🔍 Validation du Bordereau</h1>
    </div>
    
    <div class="container">
        <a href="tresorier_dashboard.php" class="back-link">← Retour au tableau de bord</a>
        
        <?php if ($validation_message): ?>
            <div class="message <?= strpos($validation_message, '✅') === 0 ? 'success' : 'error' ?>">
                <?= htmlspecialchars($validation_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($report): ?>
            <!-- INFORMATIONS ADHÉRENT -->
            <div class="card">
                <div class="card-header">
                    <h2>👤 Informations de l'Adhérent</h2>
                    <span class="status-badge status-<?= str_replace('_', '', $report['validation_status']) ?>">
                        <?= ucfirst(str_replace('_', ' ', $report['validation_status'])) ?>
                    </span>
                </div>
                <div class="adherer-info">
                    <div class="info-item">
                        <div class="info-label">Nom Complet</div>
                        <div class="info-value"><?= htmlspecialchars($report['first_name'] . ' ' . $report['last_name']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($report['email']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">N° Licence</div>
                        <div class="info-value"><?= htmlspecialchars($report['license_number'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Ligue</div>
                        <div class="info-value"><?= htmlspecialchars($report['league_name'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Montant Total Déclaré</div>
                        <div class="info-value" style="color: #27ae60; font-size: 20px;">
                            <?= number_format($report['total'], 2) ?> €
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Soumis le</div>
                        <div class="info-value">
                            <?= $report['submitted_date'] ? date('d/m/Y H:i', strtotime($report['submitted_date'])) : 'N/A' ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- DOCUMENTS À VALIDER -->
            <div class="card">
                <div class="card-header">
                    <h2>📄 Pièces Justificatives (<?= count($documents) ?>)</h2>
                </div>
                
                <div class="documents-section">
                    <?php if (empty($documents)): ?>
                        <p style="color: #999;">Aucun document attaché</p>
                    <?php else: ?>
                        <form method="POST">
                            <?php foreach ($documents as $doc): ?>
                                <div class="doc-item">
                                    <div class="doc-header">
                                        <span class="doc-name">📎 <?= htmlspecialchars($doc['nom_fichier']) ?></span>
                                        <small style="color: #999;">
                                            <?= round($doc['taille_fichier'] / 1024, 1) ?> KB
                                        </small>
                                    </div>
                                    
                                    <div class="doc-form">
                                        <div class="form-group">
                                            <label class="form-label">Montant</label>
                                            <input type="number" name="amount" step="0.01" 
                                                   value="<?= $doc['montant'] ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Catégorie</label>
                                            <select name="category">
                                                <option value="transport" <?= $doc['categorie'] === 'transport' ? 'selected' : '' ?>>Transport</option>
                                                <option value="hebergement" <?= $doc['categorie'] === 'hebergement' ? 'selected' : '' ?>>Hébergement</option>
                                                <option value="repas" <?= $doc['categorie'] === 'repas' ? 'selected' : '' ?>>Repas</option>
                                                <option value="autres" <?= $doc['categorie'] === 'autres' ? 'selected' : '' ?>>Autres</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Statut</label>
                                            <select name="status">
                                                <option value="accepte" <?= $doc['validation_status'] === 'accepte' ? 'selected' : '' ?>>Accepté</option>
                                                <option value="rejete" <?= $doc['validation_status'] === 'rejete' ? 'selected' : '' ?>>Rejeté</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="action" value="validate_line" class="btn btn-primary">
                                            ✓ Mettre à jour
                                        </button>
                                    </div>
                                    <input type="hidden" name="doc_id" value="<?= $doc['id_document'] ?>">
                                </div>
                            <?php endforeach; ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ACTIONS FINALES -->
            <?php if ($report['validation_status'] !== 'valide'): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>✅ Validation Finale du Bordereau</h2>
                    </div>
                    <form method="POST" style="padding: 20px;">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label class="form-label">Notes de Validation (optionnel)</label>
                            <textarea name="notes" style="width: 100%; padding: 10px; border: 1px solid #ddd; 
                                                         border-radius: 4px; min-height: 80px; resize: vertical;"></textarea>
                        </div>
                        
                        <div class="actions">
                            <button type="submit" name="action" value="validate_report" class="btn btn-success">
                                ✅ Valider le Bordereau Complet
                            </button>
                            <button type="submit" name="action" value="reject_report" class="btn btn-danger">
                                ❌ Rejeter le Bordereau
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="card">
                <div style="padding: 40px; text-align: center; color: #999;">
                    <p>Bordereau non trouvé</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

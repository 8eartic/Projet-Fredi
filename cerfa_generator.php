<?php
/* ===============================
   GENERATEUR CERFA PDF
   Document Officiel CERFA 11580-02
   Reçu pour don à assocation
================================ */

session_start();
require_once __DIR__ . '/db.php';

// cerfa_generator.php génère une page imprimable au format CERFA.
// Il n'est accessible qu'aux trésoriers pour éviter toute fuite de documents fiscaux.
if (empty($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'tresorier') {
    header('Location: index.php?error=auth');
    exit;
}

// Identifiants nécessaires pour récupérer le bordereau validé.
$tresorier_id = (int) $_SESSION['utilisateur']['id'];
$report_id = (int) ($_GET['id'] ?? 0);
$copy_type = $_GET['copy'] ?? 'original'; // original ou copy

if ($report_id <= 0) {
    die('Bordereau invalide');
}

// ======================================
// CHARGER LES DONNÉES
// ======================================

try {
    $stmt = $db->prepare("
        SELECT r.*, u.first_name, u.last_name, u.email,
               u.license_number, u.league_name, u.club_id
        FROM remboursement r
        JOIN users u ON r.id_utilisateur = u.id
        WHERE r.id_remboursement = ? AND r.validation_status = 'valide'
    ");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        die('Bordereau non validé ou introuvable');
    }
    
    // Récupérer les documents validés
    $stmt = $db->prepare("
        SELECT * FROM documents
        WHERE id_remboursement = ? AND validation_status = 'accepte'
        ORDER BY categorie, date_upload
    ");
    $stmt->execute([$report_id]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Générer le numéro CERFA unique
    $year = date('Y');
    $cerfa_number = 'CERFA-' . $year . '-' . str_pad($report_id, 5, '0', STR_PAD_LEFT);
    
} catch (Exception $e) {
    die('Erreur: ' . $e->getMessage());
}

// ======================================
// GÉNÉRER LE HTML PRINT-FRIENDLY
// ======================================
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CERFA - <?= $cerfa_number ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #f0f0f0;
            padding: 20px;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        @media print {
            .container {
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .header p {
            font-size: 12px;
            color: #666;
        }
        
        .cerfa-number {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #e74c3c;
        }
        
        .cerfa-number strong {
            font-size: 14px;
        }
        
        .copy-type {
            font-size: 18px;
            font-weight: bold;
            color: #e74c3c;
            text-transform: uppercase;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 13px;
            font-weight: bold;
            background: #e3e3e3;
            padding: 8px 12px;
            margin-bottom: 10px;
            text-transform: uppercase;
            border-left: 4px solid #3498db;
        }
        
        .section-content {
            padding: 10px;
            border: 1px solid #ddd;
            line-height: 1.6;
        }
        
        .info-row {
            margin-bottom: 8px;
            display: grid;
            grid-template-columns: 150px 1fr;
        }
        
        .info-label {
            font-weight: bold;
            color: #2c3e50;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        thead {
            background: #34495e;
            color: white;
        }
        
        th {
            padding: 10px;
            text-align: left;
            font-size: 12px;
            font-weight: bold;
        }
        
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }
        
        tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .amount-col {
            text-align: right;
        }
        
        .total-row {
            background: #ecf0f1;
            font-weight: bold;
        }
        
        .total-row td {
            border-top: 2px solid #34495e;
            border-bottom: 2px solid #34495e;
            padding: 12px 10px;
        }
        
        .legal-notice {
            font-size: 10px;
            line-height: 1.5;
            color: #666;
            margin-bottom: 20px;
            padding: 10px;
            background: #fffacd;
            border-left: 4px solid #f39c12;
        }
        
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            padding-top: 40px;
        }
        
        .signature-block {
            text-align: center;
            font-size: 12px;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
        }
        
        .print-button {
            display: none;
        }
        
        .no-print .print-button {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .no-print .print-button:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print" style="text-align: center; margin-bottom: 20px;">
            <button class="print-button" onclick="window.print();">🖨️ Imprimer / Sauvegarder en PDF</button>
        </div>
        
        <div class="header">
            <h1>💼 REÇU POUR DON</h1>
            <p>Article 200 du Code général des impôts (Loi n°2008-1425 du 17 décembre 2008)</p>
            <p style="font-size: 10px; margin-top: 8px;">Document officiel CERFA n°11580-02</p>
        </div>
        
        <div class="cerfa-number">
            <strong>N° CERFA: <?= htmlspecialchars($cerfa_number) ?></strong>
            <span class="copy-type"><?= ucfirst($copy_type) ?></span>
        </div>
        
        <!-- ORGANISME BÉNÉFICIAIRE -->
        <div class="section">
            <div class="section-title">1. Organisme Bénéficiaire</div>
            <div class="section-content">
                <div class="info-row">
                    <div class="info-label">Nom:</div>
                    <div>Maison des Ligues de Lorraine (M2L)</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Adresse:</div>
                    <div>FREDI - Frais de Déplacement et Remise d'Impôt<br>Lorraine, France</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div>contact@m2l-lorraine.fr</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Type:</div>
                    <div>Association Sportive (domaine d'intérêt général)</div>
                </div>
            </div>
        </div>
        
        <!-- DONATEUR -->
        <div class="section">
            <div class="section-title">2. Donateur (Adhérent de Club)</div>
            <div class="section-content">
                <div class="info-row">
                    <div class="info-label">Nom Complet:</div>
                    <div><?= htmlspecialchars($report['first_name'] . ' ' . $report['last_name']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div><?= htmlspecialchars($report['email']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">N° Licence:</div>
                    <div><?= htmlspecialchars($report['license_number'] ?? 'N/A') ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Ligue/Club:</div>
                    <div><?= htmlspecialchars($report['league_name'] ?? 'N/A') ?></div>
                </div>
            </div>
        </div>
        
        <!-- DÉTAIL DES FRAIS -->
        <div class="section">
            <div class="section-title">3. Détail des Frais Déclarés (Dons)</div>
            <table>
                <thead>
                    <tr>
                        <th>Catégorie</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th class="amount-col">Montant (€)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $doc['categorie']))) ?></td>
                            <td><?= date('d/m/Y', strtotime($doc['date_upload'])) ?></td>
                            <td><?= htmlspecialchars(substr($doc['nom_fichier'], 0, 40)) ?></td>
                            <td class="amount-col"><strong><?= number_format($doc['montant'], 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right;">TOTAL DES DONS:</td>
                        <td class="amount-col"><strong><?= number_format($report['total'], 2) ?> €</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- MENTIONS LÉGALES -->
        <div class="legal-notice">
            <strong>⚖️ DÉCLARATION RELATIVE À LA NATURE DU DON:</strong><br>
            Le donateur certifie que:<br>
            • Ce don est effectué <strong>sans contrepartie directe</strong> (aucun avantage particulier en retour)<br>
            • Ce don s'inscrit dans le cadre d'une <strong>renonciation aux frais de déplacement</strong><br>
            • Cet enregistrement constitue la <strong>preuve du don déductible</strong> de l'impôt sur le revenu<br>
            • Le montant peut être déduit à hauteur de <strong>66% du don</strong> (Article 200, al. 1 du CGI)<br>
            • Le bénéficiaire s'engage à utiliser ce don à des fins d'<strong>intérêt général et non lucratif</strong><br>
            <br>
            <em>Accord du centre des impôts | Exemple CERFA officiel sur demande</em>
        </div>
        
        <!-- SIGNATURES -->
        <div class="signatures">
            <div class="signature-block">
                <div>Signature du Trésorier:</div>
                <div style="font-size: 10px; margin-top: 5px;">
                    <?= htmlspecialchars($_SESSION['utilisateur']['prenom'] . ' ' . $_SESSION['utilisateur']['nom']) ?>
                </div>
                <div class="signature-line"></div>
                <div style="font-size: 10px; margin-top: 5px;">Date: <?= date('d/m/Y') ?></div>
            </div>
            <div class="signature-block">
                <div>Signature de l'Adhérent:</div>
                <div style="font-size: 10px; margin-top: 5px;">
                    <?= htmlspecialchars($report['first_name'] . ' ' . $report['last_name']) ?>
                </div>
                <div class="signature-line"></div>
                <div style="font-size: 10px; margin-top: 5px;">Date: <?= date('d/m/Y') ?></div>
            </div>
        </div>
        
        <!-- PIED DE PAGE -->
        <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #ecf0f1; font-size: 9px; color: #999; text-align: center;">
            <p>Document généré automatiquement par FREDI | Maison des Ligues de Lorraine (M2L)</p>
            <p>Imprimé le <?= date('d/m/Y à H:i') ?> | Numéro unique: <?= $cerfa_number ?></p>
        </div>
    </div>
</body>
</html>
<?php
exit;

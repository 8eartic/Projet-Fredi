<?php
/* ===============================
   GENERATEUR CERFA PDF
   Document Officiel CERFA 11580-02
   Reçu pour don à assocation
================================ */

session_start();
require_once __DIR__ . '/db.php';

// Vérifie que l'utilisateur est bien connecté et qu'il a le rôle tresorier. Si ce n'est pas le cas :
if (empty($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'tresorier') {
    header('Location: index.php?error=auth');   // Redirige vers la page d'accueil avec une erreur et arrête le script — personne d'autre ne peut accéder à ce document.
    exit;
}

// Identifiants nécessaires pour récupérer le bordereau validé.
$tresorier_id = (int) $_SESSION['utilisateur']['id'];   // Récupère l'ID du trésorier connecté et le force en entier (sécurité).
$report_id = (int) ($_GET['id'] ?? 0);  // Récupère l'ID du bordereau passé dans l'URL (?id=5 par exemple). Si absent, vaut 0.
$copy_type = $_GET['copy'] ?? 'original'; // original ou copy

if ($report_id <= 0) {
    die('Bordereau invalide');
}

// ======================================
// CHARGER LES DONNÉES
// ======================================

try {       // Récupère les infos du remboursement et les infos de l'utilisateur associé (nom, email, licence...)
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
    <link rel="stylesheet" href="cerfa_generator.css">
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
            <strong>N° CERFA: <?= htmlspecialchars($cerfa_number) ?></strong>   <!-- Affiche le numéro CERFA généré en PHP, sécurisé contre les injections. -->
            <span class="copy-type"><?= ucfirst($copy_type) ?></span>   <!-- Affiche Original ou Copy avec une majuscule au début. -->
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
                    <!-- Affiche le prénom et nom de l'adhérent -->
                    <div><?= htmlspecialchars($report['first_name'] . ' ' . $report['last_name']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div><?= htmlspecialchars($report['email']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">N° Licence:</div>
                    <!-- Affiche le numéro de licence, ou N/A s'il est absent -->
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
                <thead>   <!-- En-tête du tableau avec 4 colonnes : Catégorie, Date, Description, Montant -->
                    <tr>
                        <th>Catégorie</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th class="amount-col">Montant (€)</th>
                    </tr>
                </thead>
                <tbody>     <!-- Corps du tableau, rempli dynamiquement par la boucle PHP -->
                    <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $doc['categorie']))) ?></td>
                            <td><?= date('d/m/Y', strtotime($doc['date_upload'])) ?></td>   <!-- Convertit la date en format lisible jour/mois/année -->
                            <td><?= htmlspecialchars(substr($doc['nom_fichier'], 0, 40)) ?></td>    <!-- Coupe le nom du fichier à 40 caractères maximum pour ne pas déborder du tableau -->
                            <td class="amount-col"><strong><?= number_format($doc['montant'], 2) ?></strong></td>   <!-- Coupe le nom du fichier à 40 caractères maximum pour ne pas déborder du tableau -->
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
                    <!-- Affiche le nom du trésorier connecté depuis la session -->
                    <?= htmlspecialchars($_SESSION['utilisateur']['prenom'] . ' ' . $_SESSION['utilisateur']['nom']) ?>
                </div>
                <div class="signature-line"></div>
                <div style="font-size: 10px; margin-top: 5px;">Date: <?= date('d/m/Y') ?></div>
            </div>
            <div class="signature-block">
                <div>Signature de l'Adhérent:</div>
                <div style="font-size: 10px; margin-top: 5px;">
                    <!-- Affiche le nom de l'adhérent depuis la base -->
                    <?= htmlspecialchars($report['first_name'] . ' ' . $report['last_name']) ?>
                </div>
                <div class="signature-line"></div>
                <div style="font-size: 10px; margin-top: 5px;">Date: <?= date('d/m/Y') ?></div>
            </div>
        </div>
        
        <!-- PIED DE PAGE -->
        <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #ecf0f1; font-size: 9px; color: #999; text-align: center;">
            <p>Document généré automatiquement par FREDI | Maison des Ligues de Lorraine (M2L)</p>
            <!-- Affiche la date et l'heure exacte de génération du document -->
            <!-- Rappelle le numéro CERFA en bas de page pour référence -->
            <p>Imprimé le <?= date('d/m/Y à H:i') ?> | Numéro unique: <?= $cerfa_number ?></p>
        </div>
    </div>
</body>
</html>
<?php
exit;

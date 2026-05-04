<?php require_once __DIR__ . '/Formulaire_remboursement_php.php'; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<!-- Le titre de l'onglet change selon si on est en mode édition ou création.
C'est un opérateur ternaire : si $isEdit est vrai → premier texte, sinon → deuxième. -->
<title><?php echo $isEdit ? 'Modifier la demande de remboursement' : 'Fiche de remboursement'; ?></title>
<link rel="stylesheet" href="CSS_FRRB.css">
<script src="script_FRRB.js" defer></script>    <!-- defer signifie que le script s'exécute après que tout le HTML est chargé -->
</head>

<body>

<header>
    <div style="display: flex; align-items: center; gap: 14px;">
        <a href="index.php" class="logo">
            <span class="logo-mark">F</span>
            <span>FREDI</span>
        </a>
        <h1><?php echo $isEdit ? 'Modifier la demande de remboursement' : 'Fiche de remboursement'; ?></h1>
    </div>
    <div style="display:flex; gap:10px; align-items:center;">
        <a href="auth_logout.php" class="logout-btn">🚪 Déconnexion</a>
    </div>
</header>

<?php if ($message): ?>
    <div class="message"><?= $message ?></div>
<?php endif; ?>

<?php
    // Définit un tableau avec les 5 catégories de dépenses, chacune avec un label lisible et un emoji.
    // Cela évite de répéter du code HTML pour chaque catégorie.
    $categorySections = [
        'transport' => ['label' => 'Transport', 'icon' => '🚗'],
        'hebergement' => ['label' => 'Hébergement', 'icon' => '🏨'],
        'parking' => ['label' => 'Parking', 'icon' => '🅿️'],
        'carburant' => ['label' => 'Carburant', 'icon' => '⛽'],
        'autres_frais' => ['label' => 'Autres frais', 'icon' => '💰'],
    ];
?>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <?php foreach ($categorySections as $categorie => $meta): ?>       <!-- Boucle qui génère une section HTML pour chaque catégorie automatiquement -->
    <div class="remboursement-section">
        <h3><?= $meta['icon'] ?> <?= htmlspecialchars($meta['label']) ?></h3>   <!-- Affiche le titre de la section avec l'emoji et le nom de la catégorie -->

        <?php if ($isEdit && !empty($existingDocuments[$categorie])): ?>
        <div class="existing-docs">
            <strong>✅ Documents existants :</strong>
            <ul>
                <?php foreach ($existingDocuments[$categorie] as $doc): ?>
                <li>
                    <a href="<?= htmlspecialchars($doc['chemin_fichier']) ?>" target="_blank" rel="noopener noreferrer">📄 <?= htmlspecialchars($doc['nom_fichier']) ?></a>
                    (<?= number_format($doc['montant'], 2) ?> €)
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div id="<?= $categorie ?>_container">
            <div class="justificatif-row">
                <input type="file" name="<?= $categorie ?>[]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                <div style="display:flex;align-items:center;gap:4px;grid-column:2;">
                    <input type="hidden" name="<?= $categorie ?>_don[]" value="0">
                    <input type="number" step="0.01" name="<?= $categorie ?>_montant[]" placeholder="Montant (€)" style="flex:1;">
                    <label class="switch">
                        <input type="checkbox" onchange="toggleDonationHidden(this)">
                        <span class="slider"></span>
                    </label>
                    <span style="font-size:13px;color:#333;">Don</span>
                </div>
            </div>
        </div>
        <button type="button" onclick="addJustificatif('<?= $categorie ?>')">➕ Ajouter pièce justificative</button>
        <p>Total <?= htmlspecialchars($meta['label']) ?>: <strong id="total_<?= $categorie ?>">0.00</strong> €</p>
    </div>
    <?php endforeach; ?>

    <div class="total">
        Total : <span id="total">0.00</span> €
    </div>

    <input type="hidden" name="total" id="total_input">

    <button type="submit" name="show_history" value="1">📜 Voir historique</button>
    <button type="submit" class="btn-apple">
        <?= $isEdit ? '✏️ Modifier la demande' : '✓ Envoyer la demande' ?>
    </button>
</form>

<?php if (!empty($history)): ?>
<div style="max-width: 700px; margin: 40px auto 0;">
<h2 style="text-align: center; color: #1a1a1a; margin-bottom: 24px;">📊 Historique des demandes</h2>
<table style="width: 100%; background: white; border-collapse: collapse; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
    <tr style="background: #1565c0; color: white; font-weight: 600;">
        <th style="padding: 14px; border: 1px solid #e0e0e0; text-align: center;">ID</th>
        <th style="padding: 14px; border: 1px solid #e0e0e0; text-align: center;">Date</th>
        <th style="padding: 14px; border: 1px solid #e0e0e0; text-align: center;">Total (€)</th>
        <th style="padding: 14px; border: 1px solid #e0e0e0; text-align: center;">Statut</th>
        <th style="padding: 14px; border: 1px solid #e0e0e0; text-align: center;">Action</th>
    </tr>
    <?php foreach ($history as $h): ?>
    <tr style="border-bottom: 1px solid #f0f0f0;">
        <td style="padding: 14px; border: 1px solid #e0e0e0; text-align: center; color: #666;"><?= $h['numero_remboursement'] ?></td>
        <td style="padding: 14px; border: 1px solid #e0e0e0; text-align: center; color: #666;"><?= date('d/m/Y', strtotime($h['date_demande'])) ?></td>
        <td style="padding: 14px; border: 1px solid #e0e0e0; text-align: center; font-weight: 600; color: #1565c0;"><?= number_format($h['total'], 2) ?> €</td>
        <td style="padding: 14px; border: 1px solid #e0e0e0; text-align: center;">
            <?php
            $statutColor = [
                'EN_ATTENTE' => '#ff9800',
                'VALIDE' => '#4caf50',
                'REFUSE' => '#f44336',
                'ACCEPTEE' => '#4caf50',
                'REFUSEE' => '#f44336',
                'PAYEE' => '#2196f3'
            ];
            $statutLabel = [
                'EN_ATTENTE' => 'En attente',
                'VALIDE' => 'Validé',
                'REFUSE' => 'Refusé',
                'ACCEPTEE' => 'Validé',
                'REFUSEE' => 'Refusé',
                'PAYEE' => 'Payé'
            ];
            $color = $statutColor[$h['statut']] ?? '#999';
            $label = $statutLabel[$h['statut']] ?? ($h['statut'] ?: 'Inconnu');
            ?>
            <span style="padding: 6px 12px; background: <?= $color ?>15; color: <?= $color ?>; border-radius: 6px; font-weight: 600; font-size: 12px;"><?= htmlspecialchars($label) ?></span>
        </td>
        <td style="padding: 14px; border: 1px solid #e0e0e0; text-align: center;">
            <?php
            $dateDemande = new DateTime($h['date_demande']);
            $now = new DateTime();
            $interval = $now->diff($dateDemande);
            $hours = $interval->h + ($interval->days * 24);
            if ($hours <= 72 && $h['statut'] === 'EN_ATTENTE') {
                echo '<a href="?edit=' . $h['id_remboursement'] . '" style="color: #1565c0; text-decoration: none; font-weight: 600; padding: 6px 12px; border-radius: 6px; border: 1px solid #1565c0; transition: all 0.3s; display: inline-block; margin-right: 6px;">✏️ Modifier</a>';
                echo '<a href="?delete=' . $h['id_remboursement'] . '" onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer cette demande ?\')" style="color: #f44336; text-decoration: none; font-weight: 600; padding: 6px 12px; border-radius: 6px; border: 1px solid #f44336; transition: all 0.3s; display: inline-block;">🗑️ Supprimer</a>';
            } else {
                echo '<span style="color: #999;">—</span>';
            }
            ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>
<?php endif; ?>



</body>
</html>


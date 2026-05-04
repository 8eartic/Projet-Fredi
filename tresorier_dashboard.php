<?php
// tresorier_dashboard.php - page principale du trésorier.
// Elle regroupe les statistiques, les bordereaux en attente et les actions de validation.
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['utilisateur']) || $_SESSION['utilisateur']['role'] !== 'tresorier') {
    // Si l'utilisateur n'est pas connecté ou n'est pas trésorier,
    // on le redirige vers la page d'accueil.
    header('Location: index.php?error=auth');
    exit;
}

$tresorier_id   = (int) $_SESSION['utilisateur']['id'];
$tresorier_name = $_SESSION['utilisateur']['prenom'] . ' ' . $_SESSION['utilisateur']['nom'];
$league_id      = $_SESSION['utilisateur']['league_id'] ?? null;

// ======================================
// TRAITEMENT VALIDATION / REFUS (POST)
// ======================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id_remboursement'])) {
    $id          = (int) $_POST['id_remboursement'];
    $numero      = (int) ($_POST['numero_remboursement'] ?? 0);
    $action      = $_POST['action'];
    $commentaire = trim($_POST['commentaire'] ?? '');

    // L'action du trésorier peut être de valider ou refuser un bordereau.
    if (in_array($action, ['valider', 'refuser'])) {
        $nouveau_statut = ($action === 'valider') ? 'ACCEPTEE' : 'REFUSEE';
        try {
            $update = $db->prepare("UPDATE remboursement SET statut = ? WHERE id_remboursement = ?");
            $update->execute([$nouveau_statut, $id]);
            $num_display = $numero > 0 ? "#$numero" : "#$id";
            $_SESSION['success'] = ($action === 'valider')
                ? "✅ Bordereau $num_display accepté avec succès."
                : "❌ Bordereau $num_display refusé.";
        } catch (Exception $e) {
            // Si la mise à jour échoue, on conserve l'erreur dans la session
            // pour l'afficher dans l'interface utilisateur.
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
        }
    }
    header('Location: tresorier_dashboard.php');
    exit;
}

// ======================================
// STATISTIQUES GLOBALES
// ======================================
// On récupère les chiffres clés des bordereaux pour le trésorier.
$stats = ['total'=>0, 'en_attente'=>0, 'valide'=>0, 'refuse'=>0, 'total_amount'=>0.00];
try {
    $s = $db->query("SELECT
        COUNT(*) as total,
        SUM(CASE WHEN UPPER(statut) IN ('EN_ATTENTE','ATTENTE') THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN UPPER(statut) IN ('VALIDE','ACCEPTEE','ACCEPTE') THEN 1 ELSE 0 END) as valide,
        SUM(CASE WHEN UPPER(statut) IN ('REFUSE','REFUSEE') THEN 1 ELSE 0 END) as refuse,
        SUM(total) as total_amount
    FROM remboursement");
    $row = $s->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $stats['total']        = (int)   $row['total'];
        $stats['en_attente']   = (int)  ($row['en_attente']   ?? 0);
        $stats['valide']       = (int)  ($row['valide']       ?? 0);
        $stats['refuse']       = (int)  ($row['refuse']       ?? 0);
        $stats['total_amount'] = (float)($row['total_amount'] ?? 0);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
}

// ======================================
// LISTE DES BORDEREAUX EN ATTENTE
// ======================================
$reports = [];
try {
    $s = $db->query("SELECT
        r.id_remboursement,
        r.numero_remboursement,
        r.id_utilisateur,
        r.transport, r.hebergement, r.parking, r.carburant, r.autres_frais,
        r.total, r.statut,
        u.first_name, u.last_name, u.email,
        COUNT(d.id_document) as doc_count,
        (
            SELECT chemin_fichier
            FROM documents
            WHERE id_remboursement = r.id_remboursement
            ORDER BY date_upload DESC
            LIMIT 1
        ) as latest_doc_path
    FROM remboursement r
    LEFT JOIN documents d ON r.id_remboursement = d.id_remboursement
    JOIN users u ON r.id_utilisateur = u.id
    WHERE r.statut = 'EN_ATTENTE'
    GROUP BY r.id_remboursement, r.numero_remboursement, r.id_utilisateur, r.transport, r.hebergement, r.parking, r.carburant, r.autres_frais, r.total, r.statut, u.first_name, u.last_name, u.email
    ORDER BY r.id_remboursement ASC");
    $reports = $s->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { error_log($e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FREDI - Tableau de Bord Trésorier</title>
<link rel="stylesheet" href="tresorier_dashboard.css">
</head>
<body>

<div class="navbar">
    <h1>🏆 FREDI - Tableau de Bord Trésorier</h1>
    <div style="display:flex;align-items:center;gap:16px">
        <span style="font-size:14px;color:#666">Connecté : <strong style="color:#2c3e50"><?php echo htmlspecialchars($tresorier_name); ?></strong></span>
        <form method="POST" action="auth_logout.php" style="display:inline">
            <button type="submit" class="logout-btn">Déconnexion</button>
        </form>
    </div>
</div>

<div class="container">

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Bordereaux</div>
            <div class="stat-value"><?php echo $stats['total']; ?></div>
        </div>
        <div class="stat-card warning">
            <div class="stat-label">⏳ En Attente</div>
            <div class="stat-value"><?php echo $stats['en_attente']; ?></div>
        </div>
        <div class="stat-card success">
            <div class="stat-label">✅ Validés</div>
            <div class="stat-value"><?php echo $stats['valide']; ?></div>
        </div>
        <div class="stat-card danger">
            <div class="stat-label">❌ Refusés</div>
            <div class="stat-value"><?php echo $stats['refuse']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">💰 Montant Total</div>
            <div class="stat-value" style="font-size:22px"><?php echo number_format($stats['total_amount'], 2); ?> €</div>
        </div>
    </div>

    <!-- TABLEAU DES BORDEREAUX EN ATTENTE -->
    <div class="reports-section">
        <div class="section-header">
            <h2>📋 Bordereaux à Traiter (EN_ATTENTE)</h2>
        </div>
        <div class="section-content">
            <?php if (empty($reports)): ?>
                <div class="empty-state">✅ Aucun bordereau en attente pour le moment</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Adhérent</th>
                            <th>Transport</th>
                            <th>Hébergement</th>
                            <th>Parking</th>
                            <th>Carburant</th>
                            <th>Autres</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $r): ?>
                        <tr>
                            <td><strong>#<?php echo $r['numero_remboursement']; ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($r['first_name'].' '.$r['last_name']); ?></strong><br>
                                <small style="color:#999"><?php echo htmlspecialchars($r['email']); ?></small>
                            </td>
                            <td><?php echo number_format($r['transport'],    2); ?> €</td>
                            <td><?php echo number_format($r['hebergement'],  2); ?> €</td>
                            <td><?php echo number_format($r['parking'],      2); ?> €</td>
                            <td><?php echo number_format($r['carburant'],    2); ?> €</td>
                            <td><?php echo number_format($r['autres_frais'], 2); ?> €</td>
                            <td><strong><?php echo number_format($r['total'], 2); ?> €</strong></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-success"
                                        onclick="ouvrirModal('valider',
                                            <?php echo $r['id_remboursement']; ?>,
                                            <?php echo $r['numero_remboursement']; ?>,
                                            '<?php echo htmlspecialchars(addslashes($r['first_name'].' '.$r['last_name'])); ?>',
                                            '<?php echo number_format($r['total'],2); ?>')">
                                        ✅ Valider
                                    </button>
                                    <button class="btn btn-danger"
                                        onclick="ouvrirModal('refuser',
                                            <?php echo $r['id_remboursement']; ?>,
                                            <?php echo $r['numero_remboursement']; ?>,
                                            '<?php echo htmlspecialchars(addslashes($r['first_name'].' '.$r['last_name'])); ?>',
                                            '<?php echo number_format($r['total'],2); ?>')">
                                        ❌ Refuser
                                    </button>
                                    <?php if (!empty($r['latest_doc_path'])): ?>
                                        <a class="btn btn-info" href="<?php echo htmlspecialchars($r['latest_doc_path']); ?>" target="_blank" rel="noopener noreferrer">
                                            📄 Voir document
                                        </a>
                                    <?php endif; ?>
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

<!-- MODAL CONFIRMATION -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal" id="modalBox">
        <div class="modal-icon" id="modalIcon"></div>
        <h3 id="modalTitle"></h3>
        <p class="modal-sub" id="modalSub"></p>
        <div class="modal-info-box">
            <strong id="modalNom"></strong>
            <span id="modalMontant" style="color:#27ae60;font-weight:bold"></span>
        </div>
        <form method="POST">
            <input type="hidden" name="id_remboursement" id="modalId">
            <input type="hidden" name="numero_remboursement" id="modalNumero">
            <input type="hidden" name="action"           id="modalAction">
            <label>Commentaire <span class="modal-optional">(optionnel)</span></label>
            <textarea name="commentaire" placeholder="Ex : pièces justificatives manquantes..."></textarea>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="fermerModal()">Annuler</button>
                <button type="submit" class="confirm-btn" id="modalConfirmBtn">Confirmer</button>
            </div>
        </form>
    </div>
</div>

<script>
function ouvrirModal(action, id, numero, nom, montant) {
    const isVal = action === 'valider';
    document.getElementById('modalIcon').textContent       = isVal ? '✅' : '❌';
    document.getElementById('modalTitle').textContent      = isVal ? 'Valider ce bordereau ?' : 'Refuser ce bordereau ?';
    document.getElementById('modalSub').textContent        = isVal
        ? 'Le bordereau sera marqué comme ACCEPTÉ.'
        : 'Le bordereau sera marqué comme REFUSÉ.';
    document.getElementById('modalNom').textContent        = '👤 ' + nom;
    document.getElementById('modalMontant').textContent    = '💰 ' + montant + ' €';
    document.getElementById('modalId').value               = id;
    document.getElementById('modalNumero').value           = numero;
    document.getElementById('modalAction').value           = action;
    document.getElementById('modalConfirmBtn').textContent = isVal ? '✅ Valider' : '❌ Refuser';
    document.getElementById('modalBox').className          = 'modal mode-' + action;
    document.getElementById('modalOverlay').classList.add('active');
}
function fermerModal() {
    document.getElementById('modalOverlay').classList.remove('active');
}
document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) fermerModal();
});
</script>
</body>
</html>
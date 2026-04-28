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
    $action      = $_POST['action'];
    $commentaire = trim($_POST['commentaire'] ?? '');
 
    // L'action du trésorier peut être de valider ou refuser un bordereau.
    if (in_array($action, ['valider', 'refuser'])) {
        $nouveau_statut = ($action === 'valider') ? 'VALIDE' : 'REFUSE';
        try {
            $update = $db->prepare("UPDATE remboursement SET statut = ? WHERE id_remboursement = ?");
            $update->execute([$nouveau_statut, $id]);
            $_SESSION['success'] = ($action === 'valider')
                ? "✅ Bordereau #$id validé avec succès."
                : "❌ Bordereau #$id refusé.";
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
        r.id_utilisateur,
        r.transport, r.hebergement, r.parking, r.carburant, r.autres_frais,
        r.total, r.statut,
        u.first_name, u.last_name, u.email
    FROM remboursement r
    JOIN users u ON r.id_utilisateur = u.id
    WHERE r.statut = 'EN_ATTENTE'
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
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fa;color:#333}
 
.navbar{background:white;border-bottom:1px solid #eee;padding:15px 30px;display:flex;
    justify-content:space-between;align-items:center;box-shadow:0 2px 4px rgba(0,0,0,.05)}
.navbar h1{font-size:22px;color:#2c3e50}
 
.container{max-width:1200px;margin:30px auto;padding:0 20px}
 
.alert{padding:14px 20px;border-radius:6px;margin-bottom:20px;font-weight:500;font-size:14px}
.alert-success{background:#d4edda;color:#155724;border-left:4px solid #27ae60}
.alert-error  {background:#f8d7da;color:#721c24;border-left:4px solid #e74c3c}
 
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px;margin-bottom:30px}
.stat-card{background:white;padding:20px;border-radius:8px;border-left:4px solid #3498db;
    box-shadow:0 2px 4px rgba(0,0,0,.05)}
.stat-card.warning{border-left-color:#f39c12}
.stat-card.success{border-left-color:#27ae60}
.stat-card.danger {border-left-color:#e74c3c}
.stat-label{font-size:11px;color:#999;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px}
.stat-value{font-size:30px;font-weight:bold;color:#2c3e50}
 
.reports-section{background:white;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.05);overflow:hidden}
.section-header{background:#f8f9fa;padding:20px;border-bottom:1px solid #eee}
.section-header h2{font-size:18px;color:#2c3e50}
.section-content{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead{background:#f8f9fa;border-bottom:2px solid #ddd}
th{padding:14px 15px;text-align:left;font-weight:600;color:#666;font-size:12px;text-transform:uppercase}
td{padding:13px 15px;border-bottom:1px solid #eee;font-size:13px}
tbody tr:hover{background:#fafafa}
 
.btn{display:inline-block;padding:8px 16px;border-radius:4px;text-decoration:none;
    font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .2s ease}
.btn-success{background:#27ae60;color:white} .btn-success:hover{background:#229954}
.btn-danger {background:#e74c3c;color:white} .btn-danger:hover {background:#c0392b}
.btn-secondary{background:#95a5a6;color:white}.btn-secondary:hover{background:#7f8c8d}
.action-buttons{display:flex;gap:8px}
 
.logout-btn{background:#e74c3c;color:white;padding:8px 16px;border:none;border-radius:4px;cursor:pointer;font-size:13px}
.logout-btn:hover{background:#c0392b}
 
.empty-state{padding:50px;text-align:center;color:#999;font-size:16px}
 
/* === MODAL === */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);
    z-index:1000;align-items:center;justify-content:center}
.modal-overlay.active{display:flex}
.modal{background:white;border-radius:12px;padding:32px;width:100%;max-width:460px;
    box-shadow:0 20px 60px rgba(0,0,0,.25);animation:popIn .2s ease}
@keyframes popIn{
    from{transform:scale(.92) translateY(-10px);opacity:0}
    to  {transform:scale(1)   translateY(0);opacity:1}
}
.modal-icon{font-size:36px;margin-bottom:12px}
.modal h3{font-size:20px;color:#2c3e50;margin-bottom:6px}
.modal-sub{color:#888;font-size:14px;margin-bottom:18px}
.modal-info-box{background:#f8f9fa;border-radius:8px;padding:14px 16px;
    margin-bottom:18px;font-size:13px;border-left:3px solid #bdc3c7}
.modal-info-box strong{display:block;color:#2c3e50;font-size:15px;margin-bottom:4px}
.modal label{display:block;font-size:13px;font-weight:600;color:#555;margin-bottom:6px}
.modal-optional{font-size:11px;color:#aaa;font-weight:400}
.modal textarea{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:6px;
    font-size:13px;resize:vertical;min-height:80px;font-family:inherit}
.modal textarea:focus{outline:none;border-color:#3498db}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:20px}
.confirm-btn{color:white;padding:9px 22px;border-radius:5px;border:none;cursor:pointer;font-weight:600;font-size:14px}
.mode-valider .confirm-btn{background:#27ae60} .mode-valider .confirm-btn:hover{background:#229954}
.mode-refuser .confirm-btn{background:#e74c3c} .mode-refuser .confirm-btn:hover{background:#c0392b}
</style>
</head>
<body>
 
<div class="navbar">
    <h1>🏆 FREDI - Tableau de Bord Trésorier</h1>
    <div style="display:flex;align-items:center;gap:16px">
        <span style="font-size:14px;color:#666">Connecté : <strong style="color:#2c3e50"><?= htmlspecialchars($tresorier_name) ?></strong></span>
        <a href="tresorier_reporting.php" style="color:white;text-decoration:none;padding:8px 14px;background:#3498db;border-radius:4px;font-weight:bold;font-size:13px">📊 Rapports</a>
        <form method="POST" action="auth_logout.php" style="display:inline">
            <button type="submit" class="logout-btn">Déconnexion</button>
        </form>
    </div>
</div>
 
<div class="container">
 
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
 
    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Bordereaux</div>
            <div class="stat-value"><?= $stats['total'] ?></div>
        </div>
        <div class="stat-card warning">
            <div class="stat-label">⏳ En Attente</div>
            <div class="stat-value"><?= $stats['en_attente'] ?></div>
        </div>
        <div class="stat-card success">
            <div class="stat-label">✅ Validés</div>
            <div class="stat-value"><?= $stats['valide'] ?></div>
        </div>
        <div class="stat-card danger">
            <div class="stat-label">❌ Refusés</div>
            <div class="stat-value"><?= $stats['refuse'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">💰 Montant Total</div>
            <div class="stat-value" style="font-size:22px"><?= number_format($stats['total_amount'], 2) ?> €</div>
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
                            <td><strong>#<?= $r['id_remboursement'] ?></strong></td>
                            <td>
                                <strong><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></strong><br>
                                <small style="color:#999"><?= htmlspecialchars($r['email']) ?></small>
                            </td>
                            <td><?= number_format($r['transport'],    2) ?> €</td>
                            <td><?= number_format($r['hebergement'],  2) ?> €</td>
                            <td><?= number_format($r['parking'],      2) ?> €</td>
                            <td><?= number_format($r['carburant'],    2) ?> €</td>
                            <td><?= number_format($r['autres_frais'], 2) ?> €</td>
                            <td><strong><?= number_format($r['total'], 2) ?> €</strong></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-success"
                                        onclick="ouvrirModal('valider',
                                            <?= $r['id_remboursement'] ?>,
                                            '<?= htmlspecialchars(addslashes($r['first_name'].' '.$r['last_name'])) ?>',
                                            '<?= number_format($r['total'],2) ?>')">
                                        ✅ Valider
                                    </button>
                                    <button class="btn btn-danger"
                                        onclick="ouvrirModal('refuser',
                                            <?= $r['id_remboursement'] ?>,
                                            '<?= htmlspecialchars(addslashes($r['first_name'].' '.$r['last_name'])) ?>',
                                            '<?= number_format($r['total'],2) ?>')">
                                        ❌ Refuser
                                    </button>
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
function ouvrirModal(action, id, nom, montant) {
    const isVal = action === 'valider';
    document.getElementById('modalIcon').textContent       = isVal ? '✅' : '❌';
    document.getElementById('modalTitle').textContent      = isVal ? 'Valider ce bordereau ?' : 'Refuser ce bordereau ?';
    document.getElementById('modalSub').textContent        = isVal
        ? 'Le bordereau sera marqué comme VALIDÉ.'
        : 'Le bordereau sera marqué comme REFUSÉ.';
    document.getElementById('modalNom').textContent        = '👤 ' + nom;
    document.getElementById('modalMontant').textContent    = '💰 ' + montant + ' €';
    document.getElementById('modalId').value               = id;
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
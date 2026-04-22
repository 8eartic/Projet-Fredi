<?php
session_start();
require_once __DIR__ . '/db.php';
// header will be included after <body> to maintain valid HTML

// Vérifier authentification basique
if (empty($_SESSION['utilisateur']) || empty($_SESSION['utilisateur']['id'])) {
    header('Location: index.php?error=auth');
    exit;
}

$userId = (int) $_SESSION['utilisateur']['id'];
$role = $_SESSION['utilisateur']['role'] ?? 'adherent';

// Créer une nouvelle note de frais
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_report') {
    $title = trim($_POST['title'] ?? '');
    if ($title !== '') {
        $stmt = $db->prepare('INSERT INTO expense_reports (user_id, title, status) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $title, 'brouillon']);
    }
    header('Location: gestion_notes_frais.php');
    exit;
}

// Changement de statut ou export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['submit_report','update_status','export_csv'])) {
    $rid = (int) ($_POST['report_id'] ?? 0);
    // vérifier accès
    $rchk = $db->prepare('SELECT user_id, status FROM expense_reports WHERE id = ?');
    $rchk->execute([$rid]);
    $rep = $rchk->fetch(PDO::FETCH_ASSOC);
    if ($rep) {
        if ($_POST['action'] === 'submit_report' && $rep['user_id'] == $userId) {
            // uniquement l'utilisateur peut soumettre
            $upd = $db->prepare('UPDATE expense_reports SET status = ? WHERE id = ?');
            $upd->execute(['soumis', $rid]);
        } elseif ($_POST['action'] === 'update_status' && ($role === 'tresorier' || $role === 'admin')) {
            $new = $_POST['new_status'] ?? $rep['status'];
            if (in_array($new, ['brouillon','soumis','valide','rejete'])) {
                $upd = $db->prepare('UPDATE expense_reports SET status = ? WHERE id = ?');
                $upd->execute([$new, $rid]);
            }
        } elseif ($_POST['action'] === 'export_csv') {
            // générer CSV des lignes
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename=note_'.$rid.'.csv');
            $out = fopen('php://output','w');
            fputcsv($out,['Date','Description','Catégorie','Montant']);
            $linesQ = $db->prepare('SELECT date,description,category,amount FROM expense_lines WHERE report_id=?');
            $linesQ->execute([$rid]);
            while ($row = $linesQ->fetch(PDO::FETCH_NUM)) {
                fputcsv($out,$row);
            }
            exit;
        }
    }
    header('Location: gestion_notes_frais.php?report_id='.$rid);
    exit;
}

// Ajouter une ligne de frais
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_line') {
    $report_id = (int) ($_POST['report_id'] ?? 0);
    $date = $_POST['date'] ?? null;
    $description = trim($_POST['description'] ?? '');
    $amount = (float) ($_POST['amount'] ?? 0);
    $category = trim($_POST['category'] ?? '');

    // vérifier que le report appartient à l'utilisateur
    $chk = $db->prepare('SELECT id FROM expense_reports WHERE id = ? AND user_id = ?');
    $chk->execute([$report_id, $userId]);
    if ($chk->fetch()) {
        $ins = $db->prepare('INSERT INTO expense_lines (report_id, date, description, amount, category) VALUES (?, ?, ?, ?, ?)');
        $ins->execute([$report_id, $date ?: null, $description, $amount, $category]);
    }

    header('Location: gestion_notes_frais.php?report_id=' . $report_id);
    exit;
}

// Récupérer les notes de frais (toutes si rôle administratif)
if ($role === 'adherent') {
    $reportsStmt = $db->prepare('SELECT * FROM expense_reports WHERE user_id = ? ORDER BY created_at DESC');
    $reportsStmt->execute([$userId]);
} else {
    $reportsStmt = $db->query('SELECT * FROM expense_reports ORDER BY created_at DESC');
}
$reports = $reportsStmt->fetchAll(PDO::FETCH_ASSOC);

$currentReport = null;
$lines = [];
$totalAmount = 0;
if (!empty($_GET['report_id'])) {
    $rid = (int) $_GET['report_id'];
    // si admin ou tresorier récupère sans condition, sinon vérifier ownership
    if ($role === 'adherent') {
        $rchk = $db->prepare('SELECT * FROM expense_reports WHERE id = ? AND user_id = ?');
        $rchk->execute([$rid, $userId]);
    } else {
        $rchk = $db->prepare('SELECT * FROM expense_reports WHERE id = ?');
        $rchk->execute([$rid]);
    }
    $currentReport = $rchk->fetch(PDO::FETCH_ASSOC);
    if ($currentReport) {
        $linesStmt = $db->prepare('SELECT * FROM expense_lines WHERE report_id = ? ORDER BY date DESC');
        $linesStmt->execute([$rid]);
        $lines = $linesStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($lines as $l) {
            $totalAmount += (float)$l['amount'];
        }
    }
}

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Gestion des notes de frais</title>
    <link rel="stylesheet" href="style.css">
    <!-- petits ajustements locaux -->
    <style>
        .notes-card{background:#fff;padding:16px;border-radius:6px;box-shadow:0 1px 6px rgba(0,0,0,.06);max-width:1100px;margin:auto}
        table{width:100%;border-collapse:collapse}
        th,td{padding:8px;border-bottom:1px solid #eee;text-align:left}
        form.row{display:flex;gap:8px;flex-wrap:wrap}
        form.row input, form.row select{padding:6px;border:1px solid #ddd;border-radius:4px}
    </style>
</head>
<body class="page-center">
<?php include __DIR__ . '/header.php'; ?>
<div class="content notes-card">
    <h1>Gestion des notes de frais</h1>
    <p>Connecté en tant que <strong><?php echo htmlspecialchars($_SESSION['utilisateur']['prenom'] . ' ' . $_SESSION['utilisateur']['nom']); ?></strong></p>

    <h2>Mes notes de frais</h2>
    <table>
        <thead><tr><th>ID</th><th>Titre</th><th>Statut</th><th>Créé le</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($reports as $r): ?>
            <tr<?php if (isset($currentReport) && $currentReport['id']==$r['id']) echo ' style="background:#e0f7fa"'; ?>>
                <td><?php echo $r['id']; ?></td>
                <td><?php echo htmlspecialchars($r['title']); ?></td>
                <td><?php echo htmlspecialchars($r['status']); ?></td>
                <td><?php echo $r['created_at']; ?></td>
                <td>
                    <a href="gestion_notes_frais.php?report_id=<?php echo $r['id']; ?>">Voir</a>
                    <?php if ($role==='adherent' && $r['user_id']==$userId && $r['status']==='brouillon'): ?>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="action" value="submit_report">
                            <input type="hidden" name="report_id" value="<?php echo $r['id']; ?>">
                            <button class="btn-small" type="submit">Soumettre</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Créer une nouvelle note de frais</h3>
    <form method="post">
        <input type="hidden" name="action" value="create_report">
        <div class="row">
            <input name="title" placeholder="Titre (ex: Mission X - Mars)" required>
            <button type="submit">Créer</button>
        </div>
    </form>

    <?php if ($currentReport): ?>
        <hr>
        <h2>Note #<?php echo $currentReport['id'] . ' - ' . htmlspecialchars($currentReport['title']); ?></h2>
        <p>Statut: <?php echo htmlspecialchars($currentReport['status']); ?> — Créée: <?php echo $currentReport['created_at']; ?></p>
        <?php if ($totalAmount>0): ?><p><strong>Total:</strong> <?php echo number_format($totalAmount,2,'.',' '); ?> €</p><?php endif; ?>
        <p>
            <form method="post" style="display:inline">
                <input type="hidden" name="action" value="export_csv">
                <input type="hidden" name="report_id" value="<?php echo $currentReport['id']; ?>">
                <button class="btn-small" type="submit">Télécharger CSV</button>
            </form>
        </p>
        <?php if ($role==='adherent' && $currentReport['user_id']==$userId && $currentReport['status']==='brouillon'): ?>
            <form method="post" style="display:inline">
                <input type="hidden" name="action" value="submit_report">
                <input type="hidden" name="report_id" value="<?php echo $currentReport['id']; ?>">
                <button class="btn-small">Soumettre cette note</button>
            </form>
        <?php endif; ?>
        <?php if (($role==='tresorier' || $role==='admin')): ?>
            <form method="post" style="display:inline">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="report_id" value="<?php echo $currentReport['id']; ?>">
                <select name="new_status">
                    <?php foreach (['brouillon','soumis','valide','rejete'] as $st): ?>
                        <option value="<?php echo $st; ?>"<?php if ($currentReport['status']===$st) echo ' selected'; ?>><?php echo $st; ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn-small">Mettre à jour</button>
            </form>
        <?php endif; ?>

        <h3>Lignes</h3>
        <?php if (count($lines) === 0): ?>
            <p>Aucune ligne pour cette note.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Date</th><th>Description</th><th>Catégorie</th><th>Montant</th></tr></thead>
                <tbody>
                <?php foreach ($lines as $l): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($l['date']); ?></td>
                        <td><?php echo htmlspecialchars($l['description']); ?></td>
                        <td><?php echo htmlspecialchars($l['category']); ?></td>
                        <td><?php echo number_format($l['amount'], 2, '.', ' '); ?> €</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h3>Ajouter une ligne</h3>
        <form method="post">
            <input type="hidden" name="action" value="add_line">
            <input type="hidden" name="report_id" value="<?php echo $currentReport['id']; ?>">
            <div class="row">
                <input type="date" name="date" required>
                <input name="description" placeholder="Description" required>
                <input name="amount" type="number" step="0.01" placeholder="Montant" required>
                <input name="category" placeholder="Catégorie (ex: Transport)">
                <button type="submit">Ajouter</button>
            </div>
        </form>
    <?php endif; ?>

</div> <!-- /.notes-card -->
</body>
</html>

<?php
// header.php - barre de navigation commune
// suppose que la session est déjà démarrée
?>
<header style="background:#fff;border-bottom:1px solid #eee;padding:12px 18px;margin-bottom:18px;position:sticky;top:0;z-index:50">
    <div style="max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:12px">
        <?php $current = basename($_SERVER['PHP_SELF']); ?>
        <div style="display:flex;align-items:center;gap:12px">
            <a href="index.php" style="text-decoration:none;color:#1565c0;font-weight:700;font-size:18px;display:flex;align-items:center;gap:10px">
                <span style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:10px;background:#ff6b35;color:#fff;font-size:18px;font-weight:800;">F</span>
                FREDI
            </a>
            <nav style="display:flex;gap:8px;align-items:center">
                <a class="nav-link<?php echo $current==='gestion_notes_frais.php'?' active':''; ?>" href="gestion_notes_frais.php">Notes de frais</a>
                <a class="nav-link<?php echo $current==='Gestion des document.php'?' active':''; ?>" href="Gestion des document.php">Documents</a>
                <a class="nav-link<?php echo $current==='create_mission.php'?' active':''; ?>" href="create_mission.php">Missions</a>
            </nav>
        </div>

        <div style="display:flex;align-items:center;gap:12px">
            <?php if (!empty($_SESSION['utilisateur'])): ?>
                <div style="font-size:14px;color:#333">Connecté: <strong><?php echo htmlspecialchars($_SESSION['utilisateur']['prenom'] . ' ' . $_SESSION['utilisateur']['nom']); ?></strong></div>
                <a class="btn-secondary" href="auth_logout.php">Se déconnecter</a>
            <?php else: ?>
                <a class="btn-secondary" href="index.php">Se connecter</a>
            <?php endif; ?>
        </div>
    </div>
</header>

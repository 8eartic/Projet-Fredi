<?php
// index.php est la page d'accueil et le point d'entrée du site.
// Si l'utilisateur est déjà authentifié, on le redirige vers sa page
// principale en fonction de son rôle.
session_start();

if (!empty($_SESSION['utilisateur'])) {
    $role = $_SESSION['utilisateur']['role'] ?? 'adherent';

    if ($role === 'tresorier') {
        // Trésorier : accès direct au tableau de bord dédié.
        header('Location: tresorier_dashboard.php');
        exit;
    }

    // Adhérent ou autre rôle : accès au formulaire de remboursement.
    header('Location: Formulaire_remboursement.php');
    exit;
}

// Messages d'erreur gérés par le système en cas d'échec de connexion.
$error_messages = [
    1 => 'Erreur inconnue.',
    2 => 'Mot de passe incorrect.',
    3 => 'Utilisateur non trouvé.',
    4 => 'Email et mot de passe requis.',
];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FREDI - Maison des Ligues</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <?php if (isset($_GET['error']) && isset($error_messages[$_GET['error']])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; text-align: center; border: 1px solid #f5c6cb;">
            <?php echo htmlspecialchars($error_messages[$_GET['error']]); ?>
        </div>
    <?php endif; ?>

    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="logo">
                <span class="logo-mark">F</span>
                FREDI
            </a>
            <div class="nav-buttons">
                <a href="login.php" class="btn-login">Se connecter</a>
                <a href="register.php" class="btn-login" style="background:#ff6b35;">S'inscrire</a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <h1>FREDI</h1>
        <p>Maison des Ligues</p>
        <p style="font-size: 16px; opacity: 0.9;">Simplifiez vos demandes de remboursement et gérez vos documents en un seul endroit</p>
        <div class="hero-buttons">
            <a href="login.php" class="btn-primary">Commencer</a>
        </div>
    </section>

    <section class="features">
        <h2>Pourquoi FREDI ?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📋</div>
                <h3>Notes de Frais</h3>
                <p>Créez et gérez facilement vos notes de frais en quelques clics</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💰</div>
                <h3>Remboursements</h3>
                <p>Suivez en temps réel l'état de vos remboursements</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📂</div>
                <h3>Documents</h3>
                <p>Organisez et conservez tous vos documents importants</p>
            </div>
        </div>
    </section>

    <footer class="footer">
        <p>&copy; 2026 FREDI - Maison des Ligues de Lorraine - CROSL. Tous droits réservés.</p>
    </footer>
</body>
</html>
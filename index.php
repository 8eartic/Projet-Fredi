<?php
session_start();
if (isset($_SESSION['utilisateur'])) {
    header('Location: Formulaire_remboursement.php');
    exit;
}

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
    <title>FREDI - Gestionnaire de Missions Campus</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f8f9fa;
        }

        .navbar {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 12px 20px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: 700;
            color: #1565c0;
            text-decoration: none;
        }

        .logo-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #ff6b35;
            color: #fff;
            font-size: 18px;
            font-weight: 800;
        }

        .nav-buttons {
            display: flex;
            gap: 12px;
        }

        .btn-login {
            background: #1565c0;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.3s ease;
        }

        .btn-login:hover {
            background: #0d47a1;
        }

        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 20px;
            text-align: center;
            min-height: 500px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .hero h1 {
            font-size: 52px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .hero p {
            font-size: 22px;
            margin-bottom: 40px;
            opacity: 0.95;
            max-width: 600px;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: white;
            color: #667eea;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .features {
            max-width: 1200px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .features h2 {
            text-align: center;
            font-size: 36px;
            color: #333;
            margin-bottom: 50px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .feature-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .feature-card h3 {
            font-size: 20px;
            color: #333;
            margin-bottom: 10px;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
            font-size: 14px;
        }

        .footer {
            background: white;
            border-top: 1px solid #eee;
            padding: 30px 20px;
            text-align: center;
            color: #666;
            margin-top: 60px;
        }
    </style>
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
        <p>Gestionnaire de Missions Campus</p>
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
        <p>&copy; 2026 FREDI - Gestionnaire de Missions. Tous droits réservés</p>
    </footer>
</body>
</html>
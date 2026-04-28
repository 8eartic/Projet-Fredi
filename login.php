<!doctype html>
<html lang="fr">

<link rel="stylesheet" href="style.css">

<head>
<meta charset="utf-8">
<title>Connexion - FREDI</title>
</head>
<body class="login-page">

<div class="login-container">
  <div class="login-box">
    <h1>Connexion</h1>
    <p class="subtitle">Gestionnaire de Missions Campus</p>

<?php

$cas_enabled = defined('USE_CAS') && USE_CAS && (!defined('CAS_FAKE') || !CAS_FAKE);
?>

    <!-- Local login form (always available for development) -->
    <form method="POST" action="auth_login.php">
      <div class="form-group">
        <input type="email" name="email" placeholder="Email" required>
      </div>

      <div class="form-group">
        <input type="password" name="password" placeholder="Mot de passe" required>
      </div>

      <button type="submit" class="btn-apple">Se connecter</button>
    </form>

    <!-- CAS button (kept for SSO) -->
    <div style="margin-top:12px; text-align:center;">
      <a href="cas_auth.php" class="btn-apple" style="background:#2d9cdb; display:inline-block; padding:10px 18px; color:white; text-decoration:none; border-radius:6px;">Se connecter via CAS</a>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] !== 'cas_required'): ?>
      <p class="message error"><?php echo htmlspecialchars($_GET['error'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <?php endif; ?>

    <div style="margin-top:20px; text-align:center;">
      <p style="font-size:14px; color:#666; margin:0;">Pas encore de compte ?</p>
      <a href="register.php" style="font-size:14px; color:#1565c0; text-decoration:none;">Créer un compte</a>
    </div>

    <div style="margin-top:10px; text-align:center;">
      <a href="forgot_password.php" style="font-size:14px; color:#1565c0; text-decoration:none;">Mot de passe oublié ?</a>
    </div>

    <div style="margin-top:18px; text-align:center; border-top:1px solid #eee; padding-top:15px;">
      <p style="font-size:14px; color:#666; margin:0;">Pas encore inscrit ?</p>
      <a href="index.php" style="font-size:14px; color:#1565c0; text-decoration:none;">Retour à l'accueil</a>
    </div>
  </div>
</div>

</body>
</html>

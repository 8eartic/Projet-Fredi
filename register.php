<!doctype html>
<html lang="fr">

<link rel="stylesheet" href="style.css">

<head>
<meta charset="utf-8">
<title>Inscription - FREDI</title>
</head>
<body class="login-page">

<div class="login-container">
  <div class="login-box">
    <h1>Créer un compte</h1>
    <p class="subtitle">Remplissez ce formulaire pour accéder au service de remboursement.</p>

    <form method="POST" action="auth_register.php">
      <div class="form-group">
        <input type="text" name="first_name" placeholder="Prénom" required>
      </div>
      <div class="form-group">
        <input type="text" name="last_name" placeholder="Nom" required>
      </div>
      <div class="form-group">
        <input type="text" name="license_number" placeholder="N° de licence" required>
      </div>
      <div class="form-group">
        <input type="text" name="league_name" placeholder="Ligue d'affiliation" required>
      </div>
      <div class="form-group">
        <input type="email" name="email" placeholder="Email" required>
      </div>
      <div class="form-group">
        <input type="password" name="password" placeholder="Mot de passe" required>
      </div>
      <button type="submit" class="btn-apple">S'inscrire</button>
    </form>

    <?php if (isset($_GET['error'])): ?>
      <p class="message error"><?php echo htmlspecialchars($_GET['error'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <?php endif; ?>

    <div style="margin-top:20px; text-align:center; border-top:1px solid #eee; padding-top:15px;">
      <p style="font-size:14px; color:#666; margin:0;">Vous avez déjà un compte ?</p>
      <a href="login.php" style="font-size:14px; color:#1565c0; text-decoration:none;">Se connecter</a>
    </div>
  </div>
</div>

</body>
</html>

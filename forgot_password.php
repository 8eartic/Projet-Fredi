<!doctype html>
<html lang="fr">

<link rel="stylesheet" href="style.css">

<head>
<meta charset="utf-8">
<title>Mot de passe oublié - FREDI</title>
</head>
<body class="login-page">

<div class="login-container">
  <div class="login-box">
    <h1>Mot de passe oublié</h1>
    <p class="subtitle">Entrez votre adresse email pour recevoir un lien de réinitialisation.</p>

    <form method="POST" action="auth_forgot_password.php">
      <div class="form-group">
        <input type="email" name="email" placeholder="Email" required>
      </div>
      <button type="submit" class="btn-apple">Envoyer le lien</button>
    </form>

    <?php if (isset($_GET['message'])): ?>
      <p class="message success"><?php echo htmlspecialchars($_GET['message'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <p class="message error"><?php echo htmlspecialchars($_GET['error'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <?php endif; ?>

    <div style="margin-top:20px; text-align:center; border-top:1px solid #eee; padding-top:15px;">
      <a href="login.php" style="font-size:14px; color:#1565c0; text-decoration:none;">Retour à la connexion</a>
    </div>
  </div>
</div>

</body>
</html>

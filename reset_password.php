<?php
require 'db.php';

if (empty($_GET['token'])) {
    header('Location: login.php');
    exit;
}

$token = $_GET['token'];
$stmt = $db->prepare('SELECT id, reset_expiry FROM users WHERE reset_token = ?');
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || strtotime($user['reset_expiry']) < time()) {
    header('Location: login.php?error=' . urlencode('Lien de réinitialisation invalide ou expiré.'));
    exit;
}
?>
<!doctype html>
<html lang="fr">

<link rel="stylesheet" href="style.css">

<head>
<meta charset="utf-8">
<title>Réinitialiser le mot de passe - FREDI</title>
</head>
<body class="login-page">

<div class="login-container">
  <div class="login-box">
    <h1>Réinitialiser le mot de passe</h1>
    <p class="subtitle">Entrez un nouveau mot de passe pour votre compte.</p>

    <form method="POST" action="auth_reset_password.php">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?>">
      <div class="form-group">
        <input type="password" name="password" placeholder="Nouveau mot de passe" required>
      </div>
      <div class="form-group">
        <input type="password" name="password_confirm" placeholder="Confirmer le mot de passe" required>
      </div>
      <button type="submit" class="btn-apple">Mettre à jour</button>
    </form>

    <?php if (isset($_GET['error'])): ?>
      <p class="message error"><?php echo htmlspecialchars($_GET['error'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <?php endif; ?>
    <?php if (isset($_GET['message'])): ?>
      <p class="message success"><?php echo htmlspecialchars($_GET['message'], ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <?php endif; ?>

    <div style="margin-top:20px; text-align:center; border-top:1px solid #eee; padding-top:15px;">
      <a href="login.php" style="font-size:14px; color:#1565c0; text-decoration:none;">Retour à la connexion</a>
    </div>
  </div>
</div>

</body>
</html>

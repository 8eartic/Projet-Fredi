<?php
session_start();
$message = $_GET['message'] ?? null;  // Récupère un message de succès passé dans l'URL (ex: ?message=Email envoyé). Si absent, vaut null
$error = $_GET['error'] ?? null;  // Même chose mais pour un message d'erreur
?>
<!doctype html>
<html lang="fr">


<head>
<meta charset="utf-8">
<title>Mot de passe oublié - FREDI</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="login-page">

<div class="login-container">
  <div class="login-box">
    <h1>Mot de passe oublié</h1>
    <p class="subtitle">Entrez votre adresse email pour recevoir un lien de réinitialisation.</p>

    <!-- Formulaire qui envoie les données en POST 
    vers le fichier auth_forgot_password.php qui traitera la demande. -->
    <form method="POST" action="auth_forgot_password.php">
      <div class="form-group">
        <input type="email" name="email" placeholder="Email" required>  <!-- Le required empêche de soumettre le formulaire si le champ est vide -->
      </div>
      <button type="submit" class="btn-apple">Envoyer le lien</button>
    </form>

    <?php if ($message): ?>
      <p class="message success"><?php echo htmlspecialchars($message, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['reset_link'])): ?>
      <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-left: 4px solid #1565c0; border-radius: 4px;">
        <p style="color: #1565c0; font-weight: 600; margin: 0 0 10px 0;">Link de reinitialisation :</p>
        <p style="margin: 0; word-break: break-all; font-size: 13px;">
          <!-- Affiche le lien cliquable de réinitialisation, sécurisé avec htmlspecialchars -->
          <a href="<?php echo htmlspecialchars($_SESSION['reset_link']); ?>" style="color: #1565c0; text-decoration: underline;">
            <?php echo htmlspecialchars($_SESSION['reset_link']); ?>
          </a>
        </p>
        <!-- Supprime le lien de la session après l'avoir affiché — il ne sera visible qu'une seule fois -->
        <?php unset($_SESSION['reset_link']); ?>
      </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
      <p class="message error"><?php echo htmlspecialchars($error, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <?php endif; ?>

    <div style="margin-top:20px; text-align:center; border-top:1px solid #eee; padding-top:15px;">
      <a href="login.php" style="font-size:14px; color:#1565c0; text-decoration:none;">Retour à la connexion</a>
    </div>
  </div>
</div>

</body>
</html>

<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Tableau de bord</title>
</head>
<body>
    <h1>Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name']); ?> !</h1>
    <p>Vous êtes connecté.</p>
    <a href="logout.php">Se déconnecter</a>
</body>
</html>

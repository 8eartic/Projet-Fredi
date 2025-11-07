<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="card">
    <div class="panel">
        <h2>Bienvenue, <?php echo htmlspecialchars($_SESSION["user_name"]); ?> !</h2>
        <p>Vous êtes connecté.</p>
        <a href="logout.php" onclick="fetch('logout.php').then(()=>window.location='index.html')">Se déconnecter</a>
    </div>
</div>
</body>
</html>

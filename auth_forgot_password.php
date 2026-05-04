<?php
session_start();    // Démarre la session PHP pour pouvoir utiliser $_SESSION plus tard dans le script.
require 'db.php';   // Inclut le fichier de connexion à la base de données, qui fournit la variable $db (connexion PDO).
require __DIR__ . '/vendor/autoload.php';   // Charge toutes les bibliothèques installées via Composer, notamment PHPMailer.

// Déclare les classes PHPMailer à utiliser pour éviter d'écrire le chemin complet à chaque fois.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Si le champ email du formulaire est vide, on redirige vers la page avec un message d'erreur. 
// urlencode() encode le message pour qu'il passe proprement dans l'URL (je vois pas ça sur le site)
if (empty($_POST['email'])) {
    header('Location: forgot_password.php?error=' . urlencode('Veuillez saisir votre adresse email.'));
    exit;
}

$email = trim($_POST['email']);     // Trim enlève les espaces superflus autour de l'email, pour éviter les erreurs de saisie.

// Vérifie que l'email a un format valide (ex: nom@domaine.com). Si ce n'est pas le cas, on redirige avec une erreur.
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: forgot_password.php?error=' . urlencode('Email invalide.'));
    exit;
}

// On prépare une requête SQL pour chercher l'utilisateur dont l'email correspond.
// Le ? est un paramètre sécurisé qui évite les injections SQL.
// Le résultat est stocké dans $user sous forme de tableau associatif (ex: $user['id'], $user['first_name'])
$stmt = $db->prepare('SELECT id, first_name FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Construit la base du lien de réinitialisation dynamiquement.
// Si le site tourne en HTTPS, le lien commence par https://, sinon http://. Le lien pointe vers reset_password.php avec un paramètre token à compléter.
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$resetLink = "$protocol://$host/Projet-Fredi/reset_password.php?token=";

// Message générique affiché à l'utilisateur, qu'il soit trouvé ou non en base. C'est une bonne pratique de sécurité : on ne révèle pas si un email existe ou non dans le système.
$messageText = 'Si cet email existe, un lien de réinitialisation a été envoyé.';

if ($user) {    // On entre dans ce bloc uniquement si un utilisateur avec cet email a été trouvé.
    $token = bin2hex(random_bytes(16));   // Génère un token de 32 caractères (16 octets convertis en hexadécimal) pour sécuriser le lien de réinitialisation.    
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);    // Calcule la date d'expiration du token : maintenant + 3600 secondes = 1 heure.
    $resetLink .= $token;   //Complète le lien en y ajoutant le token

    // Sauvegarde le token et sa date d'expiration dans la base de données pour l'utilisateur concerné.
    // Ainsi, quand il cliquera sur le lien, on pourra vérifier que le token est valide et non expiré.
    $stmt = $db->prepare('UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?');   
    $stmt->execute([$token, $expiresAt, $user['id']]);

    // Construit le sujet et le corps de l'email avec le prénom de l'utilisateur et le lien de réinitialisation.
    // \r\n est un retour à la ligne compatible avec les standards des emails.
    $subject = 'Reinitialisation de votre mot de passe FREDI';
    $body = "Bonjour " . $user['first_name'] . ",\r\n\r\n" .
        "Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le lien suivant pour créer un nouveau mot de passe :\r\n\r\n" .
        "$resetLink\r\n\r\n" .
        "Ce lien est valable 1 heure.\r\n\r\n" .
        "Si vous n'avez pas demandé de réinitialisation, ignorez cet email.\r\n\r\n" .
        "Cordialement,\r\n" .
        "L'équipe FREDI";

    // Les identifiants du compte Gmail utilisé pour envoyer l'email. ⚠️ Attention mauvaise pratique.
    $gmailAddress = 'lexalvin362@gmail.com';
    $gmailPassword = 'riohsdmqjcofswoe';

    try {
        $mail = new PHPMailer(true);    //Crée une instance de PHPMailer. Le true active les exceptions en cas d'erreur.
        // Configure la connexion SMTP via Gmail : protocole sécurisé SSL sur le port 465 avec authentification.
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $gmailAddress;
        $mail->Password = $gmailPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        //Définit l'expéditeur, le destinataire, le sujet et le corps de l'email. AltBody est une version alternative en texte brut.
        // isHTML(false) indique que l'email est en texte simple, pas en HTML.
        $mail->setFrom($gmailAddress, 'FREDI');
        $mail->addAddress($email);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags(str_replace('\r\n', "\n", $body));
        $mail->isHTML(false);

        $mail->send();
    
    // Si l'envoi échoue, l'erreur est enregistrée dans les logs serveur.
    // Le lien est aussi sauvegardé en session comme fallback (probablement pour le déboguer en développement).
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        $_SESSION['reset_link'] = $resetLink;
    }
}

// Redirige toujours vers la page forgot_password.php avec le message générique, que l'utilisateur existe ou non.
header('Location: forgot_password.php?message=' . urlencode($messageText));
exit;

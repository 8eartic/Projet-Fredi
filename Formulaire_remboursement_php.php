<?php
/* ===============================
   Formulaire_remboursement.php
   Page principale pour les adhérents.
   Elle gère l'affichage et la modification des notes de frais.
================================ */

session_start();

if (empty($_SESSION['csrf_token'])) {   // Vérifie si un token CSRF existe déjà en session. Si non, on en crée un.
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));    // Génère un token aléatoire de 16 octets converti en texte hexadécimal. C'est une clé de sécurité unique qui protège le formulaire contre les soumissions frauduleuses depuis d'autres sites.
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(16)); // Alternative de secours si random_bytes échoue — génère le token d'une autre façon.
    }
}

// Si l'utilisateur n'est pas connecté, redirige vers l'accueil.
if (!isset($_SESSION['utilisateur'])) {
    header("Location: index.php");
    exit;
}

// Identifiant de l'utilisateur connecté.
$id_user = (int) $_SESSION['utilisateur']['id'];
$role = $_SESSION['utilisateur']['role'];
$nom_utilisateur = $_SESSION['utilisateur']['prenom'] . " " . $_SESSION['utilisateur']['nom'];


/* ===============================
   CONNEXION BDD
================================ */
// On réutilise la connexion centralisée du projet depuis db.php.
require_once __DIR__ . '/db.php';
$pdo = $db;

/* ===============================
   Variables de gestion du formulaire
================================ */
$message = "";      // Initialise les variables utilisées plus bas : message affiché à l'utilisateur,
$isEdit = false;    // si on est en mode édition, les données de la demande à modifier, et les documents déjà uploadés.
$editData = null;
$existingDocuments = [];

if (isset($_GET['edit'])) {     // Si l'URL contient ?edit=5, on entre en mode modification.
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM remboursement WHERE id_remboursement = ?");       // Récupère la demande correspondante dans la base.
    $stmt->execute([$id]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editData) {
        $dateDemande = new DateTime($editData['date_demande']);
        $now = new DateTime();
        $interval = $now->diff($dateDemande);
        $hours = $interval->h + ($interval->days * 24);
        if ($hours > 72) {
            $message = "❌ La demande ne peut plus être modifiée (délai de 72h dépassé).";
            $editData = null;
        } else {
            $isEdit = true;
            $existingDocuments = getExistingDocuments($id, $pdo);
        }
    } else {
        $message = "❌ Demande introuvable.";
    }
}

/* ===============================
   SUPPRESSION D'UNE DEMANDE
================================ */
if (isset($_GET['delete'])) {       // Si l'URL contient ?delete=5, on entre dans le bloc de suppression.
    $id = (int)$_GET['delete'];

    // Vérifie que l'utilisateur tente de supprimer sa propre demande.
    $stmt = $pdo->prepare("SELECT * FROM remboursement WHERE id_remboursement = ? AND id_utilisateur = ?");
    $stmt->execute([$id, $id_user]);
    $deleteData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($deleteData) {
        // Calcul d'un délai de suppression de 72 heures.
        // Cette règle empêche les modifications trop tardives après la soumission.
        $dateDemande = new DateTime($deleteData['date_demande']);
        $now = new DateTime();
        $interval = $now->diff($dateDemande);
        $hours = $interval->h + ($interval->days * 24);
        
        if ($hours > 72) {
            $message = "❌ La demande ne peut plus être supprimée (délai de 72h dépassé).";
        } elseif ($deleteData['statut'] !== 'EN_ATTENTE') {
            // On ne supprime pas les demandes déjà validées ou traitées.
            $message = "❌ Seules les demandes en attente peuvent être supprimées.";
        } else {
            // Supprime les fichiers stockés sur le serveur.
            $stmt = $pdo->prepare("SELECT chemin_fichier FROM documents WHERE id_remboursement = ?");
            $stmt->execute([$id]);
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($files as $file) {
                if (file_exists($file['chemin_fichier'])) {
                    unlink($file['chemin_fichier']);
                }
            }
            
            // Supprime les entrées de justificatifs associées.
            $stmt = $pdo->prepare("DELETE FROM documents WHERE id_remboursement = ?");
            $stmt->execute([$id]);
            
            // Supprime enfin le bordereau lui-même.
            $stmt = $pdo->prepare("DELETE FROM remboursement WHERE id_remboursement = ?");
            $stmt->execute([$id]);
            
            $message = "✅ Demande de remboursement supprimée avec succès.";
        }
    } else {
        $message = "❌ Demande introuvable ou vous n'avez pas les droits d'accès.";
    }
}

/* ===============================
   RÉCUPÉRATION DES DOCUMENTS EXISTANTS
================================ */
function getExistingDocuments($id_remboursement, $pdo) {
    // Charge tous les justificatifs classés par catégorie.
    // Cette fonction est utilisée pour afficher les documents déjà téléversés
    // lors de la modification d'un bordereau.
    $documents = [];
    $categories = ['transport','hebergement','parking','carburant','autres_frais'];
    foreach ($categories as $cat) {
        $stmt = $pdo->prepare("SELECT * FROM documents WHERE id_remboursement = ? AND categorie = ?");
        $stmt->execute([$id_remboursement, $cat]);
        $documents[$cat] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    return $documents;
}



/* ===============================
   GESTION DES FICHIERS UPLOADÉS - Structure unifiée
================================ */
function uploadDocuments($files, $id_remboursement, $pdo, $userFullName = "") {
    $uploadDir = "uploads/documents/";
    if (!is_dir($uploadDir)) {      // Crée le dossier d'upload s'il n'existe pas
        mkdir($uploadDir, 0755, true);
    }

    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    $maxFileSize = 30 * 1024 * 1024; // 30 Mo

    // Catégories de dépenses
    $categories = [
        'transport',
        'hebergement',
        'parking',
        'carburant',
        'autres_frais'
    ];

    // Parcourir les catégories de dépenses
    foreach ($categories as $categorie) {
        // Vérifier que la catégorie existe dans $_FILES
        if (!isset($files[$categorie]) || !is_array($files[$categorie]['name'])) {
            continue;
        }

        $file_names = $files[$categorie]['name'];
        $file_errors = $files[$categorie]['error'];
        $file_sizes = $files[$categorie]['size'];
        $file_tmp_names = $files[$categorie]['tmp_name'];
        
        // Récupérer les montants depuis $_POST - les champs sont nommés {categorie}_montant[]
        $montants = isset($_POST[$categorie . '_montant']) ? $_POST[$categorie . '_montant'] : [];
        $donations = isset($_POST[$categorie . '_don']) ? $_POST[$categorie . '_don'] : [];

        // Traiter chaque fichier
        foreach ($file_names as $key => $fileName) {
            // Ignorer les fichiers vides
            if (empty($fileName) || $file_errors[$key] !== UPLOAD_ERR_OK) {
                continue;
            }

            $fileSize = $file_sizes[$key];
            if ($fileSize > $maxFileSize) {
                error_log("Fichier trop volumineux: $fileName");
                continue;
            }

            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedExtensions)) {
                error_log("Extension non autorisée: $fileExtension");
                continue;
            }

            // Récupérer le montant associé à ce fichier
            $montant = isset($montants[$key]) ? floatval($montants[$key]) : 0;
            $is_don = isset($donations[$key]) ? (int) $donations[$key] : 0;

            // Créer un nom de fichier avec initiales et ID demande
            // Format: INITIALES_ID_REMB.ext
            // Exemple: AB_12345.pdf pour Anne Bouvier demande 12345
            $parts = explode('_', str_replace(' ', '_', trim($userFullName)));
            $initials = '';
            foreach ($parts as $part) {
                if (!empty($part)) {
                    $initials .= strtoupper($part[0]);
                }
            }
            if (empty($initials)) {
                $initials = 'USR';
            }
            // Générer un nom unique pour éviter les conflits d'écrasement
            $unique = uniqid('', true);
            $newFileName = "{$initials}_{$id_remboursement}_{$unique}." . $fileExtension;
            $filePath = $uploadDir . $newFileName;

            // Déplacer le fichier uploadé
            if (move_uploaded_file($file_tmp_names[$key], $filePath)) {
                try {
                    // Enregistrer dans la table UNIFIÉE documents avec la catégorie
                    $sql = "INSERT INTO documents (id_remboursement, categorie, nom_fichier, chemin_fichier, type_fichier, taille_fichier, montant, is_don)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $id_remboursement,
                        $categorie,
                        $fileName,
                        $filePath,
                        $fileExtension,
                        $fileSize,
                        $montant,
                        $is_don
                    ]);
                    error_log("✅ Document inséré: $fileName ({$montant}€) dans $categorie - Fichier: $newFileName");
                } catch (PDOException $e) {
                    error_log("❌ Erreur insertion BD: " . $e->getMessage());
                }
            } else {
                error_log("❌ Erreur déplacement fichier: $fileName");
            }
        }
    }
}

/* ===============================
   TRAITEMENT POST - HISTORIQUE
================================ */
$history = [];
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['show_history'])) {
    $hist_user = !empty($_POST['id_utilisateur']) ? (int)$_POST['id_utilisateur'] : $id_user;
    $stmt = $pdo->prepare("SELECT id_remboursement, numero_remboursement, date_demande, total, statut FROM remboursement WHERE id_utilisateur = ? ORDER BY date_demande DESC");
    $stmt->execute([$hist_user]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ===============================
   TRAITEMENT POST - FORMULAIRE REMBOURSEMENT
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['show_history'])) {  //Si le formulaire est soumis avec le bouton historique, on charge les anciennes demandes.
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {       // Vérifie que le token CSRF envoyé correspond à celui en session. hash_equals compare de façon sécurisée pour éviter les attaques timing.
        $message = "❌ Requête invalide (CSRF).";
    } else {

    $categories = ['transport','hebergement','parking','carburant','autres_frais'];
    $totals = [];
    foreach ($categories as $cat) {
        $totals[$cat] = 0;
        if (isset($_POST[$cat.'_montant'])) {
            foreach ($_POST[$cat.'_montant'] as $m) {
                $totals[$cat] += floatval($m);
            }
        }
    }
    $total_general = array_sum($totals);

    if ($isEdit) {  // Met à jour la demande existante avec les nouveaux montants.
        $sql = "UPDATE remboursement SET
            id_utilisateur = :id_utilisateur,
            transport = :transport,
            hebergement = :hebergement,
            parking = :parking,
            carburant = :carburant,
            autres_frais = :autres_frais,
            total = :total
            WHERE id_remboursement = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_utilisateur' => $id_user,
            ':transport' => $totals['transport'],
            ':hebergement' => $totals['hebergement'],
            ':parking' => $totals['parking'],
            ':carburant' => $totals['carburant'],
            ':autres_frais' => $totals['autres_frais'],
            ':total' => $total_general,
            ':id' => $editData['id_remboursement']
        ]);

uploadDocuments($_FILES, $editData['id_remboursement'], $pdo, $nom_utilisateur);    // Upload les nouveaux fichiers éventuellement ajoutés.
        $message = "✅ Demande modifiée.";

    } else {
        $stmt = $pdo->prepare("SELECT MAX(numero_remboursement) as max_num FROM remboursement WHERE id_utilisateur = ?");
        $stmt->execute([$id_user]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $next_numero = ($result['max_num'] ?? 0) + 1;

        $sql = "INSERT INTO remboursement (
            numero_remboursement, id_utilisateur,
            transport, hebergement, parking, carburant, autres_frais, total
        ) VALUES (
            :numero_remboursement, :id_utilisateur,
            :transport, :hebergement, :parking, :carburant, :autres_frais, :total
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':numero_remboursement' => $next_numero,
            ':id_utilisateur' => $id_user,
            ':transport' => $totals['transport'],
            ':hebergement' => $totals['hebergement'],
            ':parking' => $totals['parking'],
            ':carburant' => $totals['carburant'],
            ':autres_frais' => $totals['autres_frais'],
            ':total' => $total_general
        ]);

        $lastInsertId = $pdo->lastInsertId();
        uploadDocuments($_FILES, $lastInsertId, $pdo, $nom_utilisateur);
            $message = "✅ Demande de remboursement #$next_numero enregistrée.";
    }
}
}
?>

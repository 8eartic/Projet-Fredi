<?php
/* ===============================
   SESSION & RÔLE
================================ */
session_start();
// Générer token CSRF si nécessaire
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(16));
    }
}
$id_user = (int) $_SESSION['utilisateur']['id'];
// id_mission may be passed by form but default null

if (!isset($_SESSION['utilisateur'])) {
    header("Location: index.php");
    exit;
}
$role = $_SESSION['utilisateur']['role'];
$nom_utilisateur = $_SESSION['utilisateur']['prenom'] . " " . $_SESSION['utilisateur']['nom'];
// Déterminer le lobby selon le rôle pour les boutons Accueil
$lobby = ($role === 'SUPERVISEUR') ? 'Lobby_superviseur.php' : (($role === 'GESTIONNAIRE') ? 'Lobby_gestionnaire.php' : 'Lobby_missionaire.php');

/* ===============================
   CONNEXION BDD (PHP)
================================ */
// réutiliser la connexion commune, identique à db.php
require_once __DIR__ . '/db.php';
$pdo = $db;


/* ===============================
   TRAITEMENT FORMULAIRE
================================ */
$message = "";
$isEdit = false;
$editData = null;
$existingDocuments = [];

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM remboursement WHERE id_remboursement = ?");
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
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT * FROM remboursement WHERE id_remboursement = ? AND id_utilisateur = ?");
    $stmt->execute([$id, $id_user]);
    $deleteData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($deleteData) {
        $dateDemande = new DateTime($deleteData['date_demande']);
        $now = new DateTime();
        $interval = $now->diff($dateDemande);
        $hours = $interval->h + ($interval->days * 24);
        
        if ($hours > 72) {
            $message = "❌ La demande ne peut plus être supprimée (délai de 72h dépassé).";
        } elseif ($deleteData['statut'] !== 'EN_ATTENTE') {
            $message = "❌ Seules les demandes en attente peuvent être supprimées.";
        } else {
            // Supprimer les fichiers associés
            $stmt = $pdo->prepare("SELECT chemin_fichier FROM documents WHERE id_remboursement = ?");
            $stmt->execute([$id]);
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($files as $file) {
                if (file_exists($file['chemin_fichier'])) {
                    unlink($file['chemin_fichier']);
                }
            }
            
            // Supprimer les documents de la base de données
            $stmt = $pdo->prepare("DELETE FROM documents WHERE id_remboursement = ?");
            $stmt->execute([$id]);
            
            // Supprimer la demande de remboursement
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
function uploadDocuments($files, $id_remboursement, $pdo, $id_mission = null, $userFullName = "") {
    $uploadDir = "uploads/documents/";
    if (!is_dir($uploadDir)) {
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
                    $sql = "INSERT INTO documents (id_remboursement, id_mission, categorie, nom_fichier, chemin_fichier, type_fichier, taille_fichier, montant, is_don)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $id_remboursement,
                        $id_mission,
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
    $stmt = $pdo->prepare("SELECT id_remboursement, date_demande, total, statut FROM remboursement WHERE id_utilisateur = ? ORDER BY date_demande DESC");
    $stmt->execute([$hist_user]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ===============================
   TRAITEMENT POST - FORMULAIRE REMBOURSEMENT
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['show_history'])) {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
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

    if ($isEdit) {
        $sql = "UPDATE remboursement SET
            id_utilisateur = :id_utilisateur,
            id_mission = :id_mission,
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
            ':id_mission' => !empty($_POST['id_mission']) ? $_POST['id_mission'] : null,
            ':transport' => $totals['transport'],
            ':hebergement' => $totals['hebergement'],
            ':parking' => $totals['parking'],
            ':carburant' => $totals['carburant'],
            ':autres_frais' => $totals['autres_frais'],
            ':total' => $total_general,
            ':id' => $editData['id_remboursement']
        ]);

        $missionId = !empty($_POST['id_mission']) ? $_POST['id_mission'] : null;
        uploadDocuments($_FILES, $editData['id_remboursement'], $pdo, $missionId, $nom_utilisateur);
        $message = "✅ Demande modifiée.";

    } else {
        $sql = "INSERT INTO remboursement (
            id_utilisateur, id_mission,
            transport, hebergement, parking, carburant, autres_frais, total
        ) VALUES (
            :id_utilisateur, :id_mission,
            :transport, :hebergement, :parking, :carburant, :autres_frais, :total
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_utilisateur' => $id_user,
            ':id_mission' => !empty($_POST['id_mission']) ? $_POST['id_mission'] : null,
            ':transport' => $totals['transport'],
            ':hebergement' => $totals['hebergement'],
            ':parking' => $totals['parking'],
            ':carburant' => $totals['carburant'],
            ':autres_frais' => $totals['autres_frais'],
            ':total' => $total_general
        ]);

        $lastInsertId = $pdo->lastInsertId();
        $missionId = !empty($_POST['id_mission']) ? $_POST['id_mission'] : null;
        uploadDocuments($_FILES, $lastInsertId, $pdo, $missionId, $nom_utilisateur);
        $message = "✅ Demande de remboursement enregistrée.";
    }
}
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title><?php echo $isEdit ? 'Modifier la demande de remboursement' : 'Fiche de remboursement'; ?></title>

<!-- ===============================
     CSS (INTÉGRÉ)
================================ -->
<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    background: #f8f9fa;
    padding: 30px 20px;
    color: #333;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}

.header h1 {
    margin: 0;
    flex: 1;
    text-align: center;
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
}

.nav-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: center;
}

.nav-buttons a {
    padding: 10px 16px;
    background: #f5f5f5;
    color: #333;
    text-decoration: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
}

.nav-buttons a:hover {
    background: #ececec;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    color: #000;
}

form {
    background: white;
    padding: 35px;
    max-width: 700px;
    margin: auto;
    border-radius: 12px;
    box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
}

h1 {
    text-align: center;
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 28px;
}

label {
    display: block;
    margin-top: 16px;
    margin-bottom: 6px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

input,
select {
    width: 100%;
    padding: 12px 16px;
    margin-bottom: 18px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #fafafa;
}

input:focus,
select:focus {
    outline: none;
    border-color: #1565c0;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.1);
}

button {
    margin-top: 12px;
    margin-bottom: 12px;
    padding: 12px 20px;
    background: #1565c0;
    color: white;
    border: none;
    cursor: pointer;
    border-radius: 8px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
    display: block;
    margin-left: auto;
    margin-right: auto;
}

button:hover {
    background: #1052a3;
    box-shadow: 0 4px 12px rgba(21, 101, 192, 0.25);
    transform: translateY(-1px);
}

button:active {
    transform: translateY(0);
}

.total {
    font-weight: 700;
    margin-top: 28px;
    padding-top: 20px;
    border-top: 2px solid #f0f0f0;
    font-size: 18px;
    color: #1565c0;
    text-align: center;
}

.message {
    text-align: center;
    margin-bottom: 20px;
    padding: 14px 16px;
    border-radius: 8px;
    color: #2e7d32;
    background: #efe;
    border: 1px solid #cfc;
    border-left: 4px solid #3c3;
    font-weight: 500;
}

.justificatif-row {
    display: grid;
    grid-template-columns: 1fr auto auto;
    gap: 24px;
    margin-bottom: 12px;
    align-items: center;
}

.justificatif-row input[type="file"] {
    grid-column: 1;
    padding: 10px 12px !important;
    cursor: pointer;
}

.justificatif-row input[type="number"] {
    grid-column: 2;
    width: 140px;
    padding: 10px 12px !important;
}

.justificatif-row button[type="button"] {
    grid-column: 3;
    width: auto;
    padding: 10px 12px;
    background: #d32f2f;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    margin-top: 0 !important;
    transition: all 0.3s ease;
}

.justificatif-row button[type="button"]:hover {
    background: #b71c1c;
    box-shadow: 0 2px 8px rgba(211, 47, 47, 0.25);
}

.existing-docs {
    background: #e8f5e9;
    padding: 14px;
    border-left: 4px solid #28a745;
    margin-bottom: 16px;
    border-radius: 6px;
    font-size: 13px;
}

.existing-docs strong {
    display: block;
    margin-bottom: 8px;
    color: #2e7d32;
    font-weight: 600;
}

.existing-docs ul {
    margin: 8px 0;
    padding-left: 20px;
    list-style-type: disc;
}

.existing-docs li {
    margin: 6px 0;
    font-size: 13px;
    color: #555;
}

.existing-docs a {
    color: #1565c0;
    text-decoration: none;
    font-weight: 500;
}

.existing-docs a:hover {
    text-decoration: underline;
}

.switch {
    position: relative;
    display: inline-block;
    width: 46px;
    height: 26px;
    
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.3s;
    border-radius: 34px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.switch input:checked + .slider {
    background-color: #1565c0;
}

.switch input:checked + .slider:before {
    transform: translateX(20px);
}

/* Sections de remboursement */
.remboursement-section {
    background: #f9f9f9;
    padding: 20px;
    margin-top: 20px;
    border-radius: 8px;
    border-left: 4px solid #1565c0;
}

.remboursement-section h3 {
    color: #1565c0;
    font-size: 16px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 0;
    margin-bottom: 16px;
}

.remboursement-section p {
    color: #666;
    font-size: 14px;
    margin-top: 12px;
    margin-bottom: 0;
}

.remboursement-section strong {
    color: #1565c0;
    font-weight: 700;
}

header {
    background: #004E89;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 8px rgba(0, 78, 137, 0.15);
    margin-bottom: 30px;
    border-radius: 0;
    max-width: none;
}

header .logo {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: white;
    font-weight: 700;
    font-size: 18px;
}

header .logo-mark {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: #ff6b35;
    color: white;
    font-size: 18px;
    font-weight: 800;
}

header h1 {
    color: white;
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    flex: 1;
}

.logout-btn {
    padding: 10px 16px;
    background: #FF6B35;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    display: inline-block;
}

.logout-btn:hover {
    background: #e55a24;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 107, 53, 0.25);
}
</style>
</head>

<body>

<header>
    <div style="display: flex; align-items: center; gap: 14px;">
        <a href="index.php" class="logo">
            <span class="logo-mark">F</span>
            <span>FREDI</span>
        </a>
        <h1><?php echo $isEdit ? 'Modifier la demande de remboursement' : 'Fiche de remboursement'; ?></h1>
    </div>
    <div style="display:flex; gap:10px; align-items:center;">
        <a href="auth_logout.php" class="logout-btn">🚪 Déconnexion</a>
    </div>
</header>

<?php if ($role === 'SUPERVISEUR'): ?>
<div class="header">
    <div class="nav-buttons">
        <a href="Formulaire_remboursement_superviseur.php">← Retour à la gestion</a>
        <a href="create_mission.php">➕ Nouvelle mission</a>
    </div>
</div>
<?php endif; ?>


<?php if ($message): ?>
    <div class="message"><?= $message ?></div>
<?php endif; ?>


<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">


    <!-- TRANSPORT -->
    <div class="remboursement-section">
        <h3>🚗 Transport</h3>
        
        <?php if ($isEdit && !empty($existingDocuments['transport'])): ?>
        <div class="existing-docs">
            <strong>✅ Documents existants :</strong>
            <ul>
                <?php foreach ($existingDocuments['transport'] as $doc): ?>
                <li>
                    <a href="<?= htmlspecialchars($doc['chemin_fichier']) ?>" target="_blank" rel="noopener noreferrer">📄 <?= htmlspecialchars($doc['nom_fichier']) ?></a>
                    (<?= number_format($doc['montant'], 2) ?> €)
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div id="transport_container">
            <div class="justificatif-row">
                <input type="file" name="transport[]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                <div style="display:flex;align-items:center;gap:4px;grid-column:2;">
                    <input type="hidden" name="transport_don[]" value="0">
                    <input type="number" step="0.01" name="transport_montant[]" placeholder="Montant (€)" style="flex:1;">
                    <label class="switch">
                        <input type="checkbox" onchange="toggleDonationHidden(this)">
                        <span class="slider"></span>
                    </label>
                    <span style="font-size:13px;color:#333;">Don</span>
                </div>
            </div>
        </div>
        <button type="button" onclick="addJustificatif('transport')">➕ Ajouter pièce justificative</button>
        <p>Total Transport: <strong id="total_transport">0.00</strong> €</p>
    </div>

    <!-- HÉBERGEMENT -->
    <div class="remboursement-section">
        <h3>🏨 Hébergement</h3>
        
        <?php if ($isEdit && !empty($existingDocuments['hebergement'])): ?>
        <div class="existing-docs">
            <strong>✅ Documents existants :</strong>
            <ul>
                <?php foreach ($existingDocuments['hebergement'] as $doc): ?>
                <li>
                    <a href="<?= htmlspecialchars($doc['chemin_fichier']) ?>" target="_blank" rel="noopener noreferrer">📄 <?= htmlspecialchars($doc['nom_fichier']) ?></a>
                    (<?= number_format($doc['montant'], 2) ?> €)
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div id="hebergement_container">
            <div class="justificatif-row">
                <input type="file" name="hebergement[]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                <div style="display:flex;align-items:center;gap:4px;grid-column:2;">
                    <input type="hidden" name="hebergement_don[]" value="0">
                    <input type="number" step="0.01" name="hebergement_montant[]" placeholder="Montant (€)" style="flex:1;">
                    <label class="switch">
                        <input type="checkbox" onchange="toggleDonationHidden(this)">
                        <span class="slider"></span>
                    </label>
                    <span style="font-size:13px;color:#333;">Don</span>
                </div>
            </div>
        </div>
        <button type="button" onclick="addJustificatif('hebergement')">➕ Ajouter pièce justificative</button>
        <p>Total Hébergement: <strong id="total_hebergement">0.00</strong> €</p>
    </div>

    <!-- PARKING -->
    <div class="remboursement-section">
        <h3>🅿️ Parking</h3>        
        <?php if ($isEdit && !empty($existingDocuments['parking'])): ?>
        <div class="existing-docs">
            <strong>✅ Documents existants :</strong>
            <ul>
                <?php foreach ($existingDocuments['parking'] as $doc): ?>
                <li>
                    <a href="<?= htmlspecialchars($doc['chemin_fichier']) ?>" target="_blank" rel="noopener noreferrer">📄 <?= htmlspecialchars($doc['nom_fichier']) ?></a>
                    (<?= number_format($doc['montant'], 2) ?> €)
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <div id="parking_container">
            <div class="justificatif-row">
                <input type="file" name="parking[]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                <div style="display:flex;align-items:center;gap:4px;grid-column:2;">
                    <input type="hidden" name="parking_don[]" value="0">
                    <input type="number" step="0.01" name="parking_montant[]" placeholder="Montant (€)" style="flex:1;">
                    <label class="switch">
                        <input type="checkbox" onchange="toggleDonationHidden(this)">
                        <span class="slider"></span>
                    </label>
                    <span style="font-size:13px;color:#333;">Don</span>
                </div>
            </div>
        </div>
        <button type="button" onclick="addJustificatif('parking')">➕ Ajouter pièce justificative</button>
        <p>Total Parking: <strong id="total_parking">0.00</strong> €</p>
    </div>

    <!-- CARBURANT -->
    <div class="remboursement-section">
        <h3>⛽ Carburant</h3>
        
        <?php if ($isEdit && !empty($existingDocuments['carburant'])): ?>
        <div class="existing-docs">
            <strong>✅ Documents existants :</strong>
            <ul>
                <?php foreach ($existingDocuments['carburant'] as $doc): ?>
                <li>
                    <a href="<?= htmlspecialchars($doc['chemin_fichier']) ?>" target="_blank" rel="noopener noreferrer">📄 <?= htmlspecialchars($doc['nom_fichier']) ?></a>
                    (<?= number_format($doc['montant'], 2) ?> €)
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div id="carburant_container">
            <div class="justificatif-row">
                <input type="file" name="carburant[]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                <div style="display:flex;align-items:center;gap:4px;grid-column:2;">
                    <input type="hidden" name="carburant_don[]" value="0">
                    <input type="number" step="0.01" name="carburant_montant[]" placeholder="Montant (€)" style="flex:1;">
                    <label class="switch">
                        <input type="checkbox" onchange="toggleDonationHidden(this)">
                        <span class="slider"></span>
                    </label>
                    <span style="font-size:13px;color:#333;">Don</span>
                </div>
            </div>
        </div>
        <button type="button" onclick="addJustificatif('carburant')">➕ Ajouter pièce justificative</button>
        <p>Total Carburant: <strong id="total_carburant">0.00</strong> €</p>
    </div>

    <!-- AUTRES FRAIS -->
    <div class="remboursement-section">
        <h3>💰 Autres frais</h3>
        
        <?php if ($isEdit && !empty($existingDocuments['autres_frais'])): ?>
        <div class="existing-docs">
            <strong>✅ Documents existants :</strong>
            <ul>
                <?php foreach ($existingDocuments['autres_frais'] as $doc): ?>
                <li>
                    <a href="<?= htmlspecialchars($doc['chemin_fichier']) ?>" target="_blank" rel="noopener noreferrer">📄 <?= htmlspecialchars($doc['nom_fichier']) ?></a>
                    (<?= number_format($doc['montant'], 2) ?> €)
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div id="autres_frais_container">
            <div class="justificatif-row">
                <input type="file" name="autres_frais[]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                <div style="display:flex;align-items:center;gap:4px;grid-column:2;">
                    <input type="hidden" name="autres_frais_don[]" value="0">
                    <input type="number" step="0.01" name="autres_frais_montant[]" placeholder="Montant (€)" style="flex:1;">
                    <label class="switch">
                        <input type="checkbox" onchange="toggleDonationHidden(this)">
                        <span class="slider"></span>
                    </label>
                    <span style="font-size:13px;color:#333;">Don</span>
                </div>
            </div>
        </div>
        <button type="button" onclick="addJustificatif('autres_frais')">➕ Ajouter pièce justificative</button>
        <p>Total Autres frais: <strong id="total_autres_frais">0.00</strong> €</p>
    </div>

    <div class="total">
        Total : <span id="total">0.00</span> €
    </div>

    <input type="hidden" name="total" id="total_input">

    <button type="submit" name="show_history" value="1">📜 Voir historique</button>
    <button type="submit" class="btn-apple">
        <?= $isEdit ? '✏️ Modifier la demande' : '✓ Envoyer la demande' ?>
    </button>
</form>

<?php if (!empty($history)): ?>
<div style="max-width: 700px; margin: 40px auto 0;">
<h2 style="text-align: center; color: #1a1a1a; margin-bottom: 24px;">📊 Historique des demandes</h2>
<table style="width: 100%; background: white; border-collapse: collapse; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
    <tr style="background: #1565c0; color: white; font-weight: 600;">
        <th style="padding: 14px; border: 1px solid #e0e0e0; text-align: center;">ID</th>
        <th style="padding: 14px; border: 1px solid #e0e0e0; text-align: center;">Date</th>
        <th style="padding: 14px; border: 1px solid #e0e0e0; text-align: center;">Total (€)</th>
        <th style="padding: 14px; border: 1px solid #e0e0e0; text-align: center;">Statut</th>
        <th style="padding: 14px; border: 1px solid #e0e0e0; text-align: center;">Action</th>
    </tr>
    <?php foreach ($history as $h): ?>
    <tr style="border-bottom: 1px solid #f0f0f0;">
        <td style="padding: 14px; border: 1px solid #e0e0e0; text-align: center; color: #666;"><?= $h['id_remboursement'] ?></td>
        <td style="padding: 14px; border: 1px solid #e0e0e0; text-align: center; color: #666;"><?= date('d/m/Y', strtotime($h['date_demande'])) ?></td>
        <td style="padding: 14px; border: 1px solid #e0e0e0; text-align: center; font-weight: 600; color: #1565c0;"><?= number_format($h['total'], 2) ?> €</td>
        <td style="padding: 14px; border: 1px solid #e0e0e0; text-align: center;">
            <?php
            $statutColor = [
                'EN_ATTENTE' => '#ff9800',
                'VALIDE' => '#4caf50',
                'REFUSE' => '#f44336',
                'ACCEPTEE' => '#4caf50',
                'REFUSEE' => '#f44336',
                'PAYEE' => '#2196f3'
            ];
            $statutLabel = [
                'EN_ATTENTE' => 'En attente',
                'VALIDE' => 'Validé',
                'REFUSE' => 'Refusé',
                'ACCEPTEE' => 'Validé',
                'REFUSEE' => 'Refusé',
                'PAYEE' => 'Payé'
            ];
            $color = $statutColor[$h['statut']] ?? '#999';
            $label = $statutLabel[$h['statut']] ?? ($h['statut'] ?: 'Inconnu');
            ?>
            <span style="padding: 6px 12px; background: <?= $color ?>15; color: <?= $color ?>; border-radius: 6px; font-weight: 600; font-size: 12px;"><?= htmlspecialchars($label) ?></span>
        </td>
        <td style="padding: 14px; border: 1px solid #e0e0e0; text-align: center;">
            <?php
            $dateDemande = new DateTime($h['date_demande']);
            $now = new DateTime();
            $interval = $now->diff($dateDemande);
            $hours = $interval->h + ($interval->days * 24);
            if ($hours <= 72 && $h['statut'] === 'EN_ATTENTE') {
                echo '<a href="?edit=' . $h['id_remboursement'] . '" style="color: #1565c0; text-decoration: none; font-weight: 600; padding: 6px 12px; border-radius: 6px; border: 1px solid #1565c0; transition: all 0.3s; display: inline-block; margin-right: 6px;">✏️ Modifier</a>';
                echo '<a href="?delete=' . $h['id_remboursement'] . '" onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer cette demande ?\')" style="color: #f44336; text-decoration: none; font-weight: 600; padding: 6px 12px; border-radius: 6px; border: 1px solid #f44336; transition: all 0.3s; display: inline-block;">🗑️ Supprimer</a>';
            } else {
                echo '<span style="color: #999;">—</span>';
            }
            ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>
<?php endif; ?>

<!-- =========================
        JS (INTÉGRÉ)
========================= -->
<script>
const categories = ['transport', 'hebergement', 'parking', 'carburant', 'autres_frais'];

function addJustificatif(categorie) {
    const container = document.getElementById(categorie + '_container');
    const row = document.createElement('div');
    row.className = 'justificatif-row';
    row.innerHTML = `
        <input type="file" name="${categorie}[]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="margin-bottom: 8px;">
        <div style="display:flex;align-items:center;gap:4px;grid-column:2;">
            <input type="hidden" name="${categorie}_don[]" value="0">
            <input type="number" step="0.01" name="${categorie}_montant[]" placeholder="Montant (€)" style="flex:1;" onchange="calculTotals()">
            <label class="switch">
                <input type="checkbox" onchange="toggleDonationHidden(this)">
                <span class="slider"></span>
            </label>
            <span style="font-size:13px;color:#333;">Don</span>
        </div>
        <button type="button" onclick="this.parentElement.remove(); calculTotals();" style="background: red; color: white; padding: 6px 10px; margin-bottom: 8px; cursor: pointer; border: none; border-radius: 4px;">✕</button>
    `;
    container.appendChild(row);
}

function toggleDonationHidden(checkbox) {
    const row = checkbox.closest('.justificatif-row');
    if (!row) return;
    const hidden = row.querySelector('input[type="hidden"][name$="_don[]"]');
    if (hidden) {
        hidden.value = checkbox.checked ? '1' : '0';
    }
}

function calculTotals() {
    let grandTotal = 0;

    categories.forEach(categorie => {
        const montants = document.querySelectorAll(`input[name="${categorie}_montant[]"]`);
        let total = 0;
        montants.forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        
        // Ajouter les montants des documents existants (affichés dans .existing-docs)
        const existingDocs = document.querySelectorAll(`#${categorie}_container .existing-docs li`);
        existingDocs.forEach(li => {
            const match = li.textContent.match(/\(([\d.,]+)\s*€\)/);
            if (match) {
                total += parseFloat(match[1].replace(',', '.')) || 0;
            }
        });
        
        document.getElementById('total_' + categorie).textContent = total.toFixed(2);
        grandTotal += total;
    });

    document.getElementById('total').textContent = grandTotal.toFixed(2);
    document.getElementById('total_input').value = grandTotal.toFixed(2);
}

// Initialiser les écouteurs
document.querySelectorAll('input[type="number"]').forEach(input => {
    if (input.name.includes('_montant')) {
        input.addEventListener('change', calculTotals);
        input.addEventListener('input', calculTotals);
    }
});

// Calcul initial
calculTotals();
</script>

</body>
</html>


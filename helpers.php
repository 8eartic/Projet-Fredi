<?php
// helpers.php - fonctions utilitaires partagées dans tout le projet.
// Ce fichier contient des helpers légers qui facilitent la gestion
// des erreurs et l'inclusion conditionnelle de fichiers.

if (!function_exists('abort')) {    // Vérifie que la fonction abort n'existe pas déjà avant de la créer
    function abort(string $message = 'Accès refusé', int $code = 403)   // Déclare la fonction avec deux paramètres : le message d'erreur (par défaut "Accès refusé") et le code HTTP (par défaut 403 = interdit).
    {
        // Sécuriser le message pour éviter les attaques XSS dans la page d'erreur.
        $safe = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';    // Récupère ce que le navigateur/client accepte comme type de réponse (ex: JSON ou HTML)

        // Détecte si la requête vient d'une appel AJAX
        // (une requête faite en arrière-plan par JavaScript, pas par un navigateur classique).
        $xhr = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        if ($xhr || strpos($accept, 'application/json') !== false) {        // Si la requête est AJAX ou que le client attend du JSON, on répond en JSON.
            http_response_code($code);  //envoie le code d'erreur HTTP
            header('Content-Type: application/json; charset=utf-8');        // dit que la réponse est du JSON
            echo json_encode(['error' => $message]);    //  envoie le message d'erreur au format JSON
            exit;
        }

        http_response_code($code);      // envoie le code HTTP
        // affiche une page HTML d'erreur mise en forme (carte blanche, message rouge)
        echo "<!doctype html><html lang=\"fr\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>Erreur</title>"
            . "<style>body{font-family:Segoe UI, Tahoma, Geneva, Verdana, sans-serif;background:#f5f5f5;padding:24px} .card{max-width:720px;margin:36px auto;background:#fff;padding:18px;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.06)} .error{color:#721c24;background:#f8d7da;padding:12px;border-radius:6px;border-left:4px solid #f5c6cb}</style></head><body>"
            . "<div class=\"card\"><h1>Erreur</h1><div class=\"error\">{$safe}</div></div></body></html>";
        exit;
    }
}

if (!function_exists('safe_require_once')) {    // Même principe : on crée la fonction seulement si elle n'existe pas déjà.
    function safe_require_once(string $path)    // Déclare une fonction qui prend en paramètre le chemin d'un fichier à inclure.
    {
        if (file_exists($path)) {   // Si le fichier existe, on l'inclut normalement.
            require_once $path;
        } else {
            error_log("safe_require_once: fichier introuvable: {$path}");   // Si le fichier n'existe pas, au lieu de planter, on écrit juste un message dans les logs du serveur et on continue.
        }
    }
}

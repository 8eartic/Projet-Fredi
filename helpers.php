<?php
// helpers.php - fonctions utilitaires légères et non invasives

if (!function_exists('abort')) {
    /**
     * Abandonne l'exécution avec un message et un code HTTP.
     * Si la requête attend du JSON (X-Requested-With ou Accept header), renvoie JSON.
     */
    function abort(string $message = 'Accès refusé', int $code = 403)
    {
        // Sécuriser le message pour l'affichage
        $safe = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Si la requête préfère le JSON, répondre JSON
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        if ($xhr || strpos($accept, 'application/json') !== false) {
            http_response_code($code);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $message]);
            exit;
        }

        http_response_code($code);
        // Réponse HTML simple, stylée légèrement pour rester cohérente
        echo "<!doctype html><html lang=\"fr\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>Erreur</title>"
            . "<style>body{font-family:Segoe UI, Tahoma, Geneva, Verdana, sans-serif;background:#f5f5f5;padding:24px} .card{max-width:720px;margin:36px auto;background:#fff;padding:18px;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.06)} .error{color:#721c24;background:#f8d7da;padding:12px;border-radius:6px;border-left:4px solid #f5c6cb}</style></head><body>"
            . "<div class=\"card\"><h1>Erreur</h1><div class=\"error\">{$safe}</div></div></body></html>";
        exit;
    }
}

if (!function_exists('safe_require_once')) {
    /**
     * Require_once mais silencieux si le fichier est manquant (logguer).
     */
    function safe_require_once(string $path)
    {
        if (file_exists($path)) {
            require_once $path;
        } else {
            error_log("safe_require_once: fichier introuvable: {$path}");
        }
    }
}

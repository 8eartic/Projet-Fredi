<?php
// helpers.php - fonctions utilitaires partagées dans tout le projet.
// Ce fichier contient des helpers légers qui facilitent la gestion
// des erreurs et l'inclusion conditionnelle de fichiers.

if (!function_exists('abort')) {
    /**
     * Arrête l'exécution et renvoie une erreur.
     *
     * Ce helper vérifie si la requête attend du JSON (API/AJAX)
     * et renvoie la réponse appropriée. Sinon, affiche une page HTML.
     *
     * @param string $message Message d'erreur à afficher
     * @param int $code Code HTTP renvoyé
     */
    function abort(string $message = 'Accès refusé', int $code = 403)
    {
        // Sécuriser le message pour éviter les injections XSS dans la page d'erreur.
        $safe = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($xhr || strpos($accept, 'application/json') !== false) {
            http_response_code($code);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $message]);
            exit;
        }

        http_response_code($code);
        echo "<!doctype html><html lang=\"fr\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>Erreur</title>"
            . "<style>body{font-family:Segoe UI, Tahoma, Geneva, Verdana, sans-serif;background:#f5f5f5;padding:24px} .card{max-width:720px;margin:36px auto;background:#fff;padding:18px;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.06)} .error{color:#721c24;background:#f8d7da;padding:12px;border-radius:6px;border-left:4px solid #f5c6cb}</style></head><body>"
            . "<div class=\"card\"><h1>Erreur</h1><div class=\"error\">{$safe}</div></div></body></html>";
        exit;
    }
}

if (!function_exists('safe_require_once')) {
    /**
     * Inclut un fichier seulement s'il existe.
     *
     * Utile pour charger des modules optionnels sans générer d'erreur fatale.
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

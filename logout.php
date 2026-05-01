<?php

declare(strict_types=1);

session_start();

/* SVUOTA SESSIONE */
$_SESSION = [];

/* ELIMINA COOKIE DI SESSIONE */
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

/* DISTRUGGE SESSIONE */
session_destroy();

/* HEADER ANTI CACHE */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

/* REDIRECT */
header('Location: /app.php');
exit;

// by LaEmiX
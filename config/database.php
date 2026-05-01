<?php

declare(strict_types=1);

$host = 'xxx';
$db   = 'xxx';
$user = 'xxx';
$pass = 'xxx';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Errore connessione database.');
}

return $pdo;

// by LaEmiX

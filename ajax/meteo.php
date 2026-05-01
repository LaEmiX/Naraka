<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

function e_json(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if (!isset($_SESSION['user'])) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'error' => 'Utente non autenticato.',
    ]);

    exit;
}

$pdo = require __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../includes/meteo/meteo.php';

try {
    $ambiente = ottieniMeteoMondo($pdo);

    echo json_encode([
        'success' => true,
        'data' => [
            'data_ora' => (string) $ambiente['data_ora'],
            'data' => (string) $ambiente['data'],
            'ora' => (string) $ambiente['ora'],
            'fascia' => (string) $ambiente['fascia'],
            'temperatura' => (int) $ambiente['temperatura'],
            'luna' => (string) $ambiente['luna'],
            'descrizione' => (string) $ambiente['descrizione'],
            'condizione' => (string) $ambiente['condizione'],
            'intensita' => $ambiente['intensita'] !== null ? (string) $ambiente['intensita'] : null,
            'vento' => (string) $ambiente['vento'],
            'umidita' => (string) $ambiente['umidita'],
            'modificatore' => $ambiente['modificatore'] !== null ? (string) $ambiente['modificatore'] : null,
        ],
    ], JSON_UNESCAPED_UNICODE);

    exit;
} catch (Throwable $exception) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'Errore durante il caricamento del meteo.',
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

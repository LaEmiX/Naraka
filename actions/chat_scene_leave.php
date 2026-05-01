<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

function json_response(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user'])) {
    json_response(['success' => false, 'error' => 'Sessione non valida.']);
}

$pdo = require __DIR__ . '/../config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user = $_SESSION['user'];

$rawInput = file_get_contents('php://input');
$data = json_decode((string) $rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

$idChat = (int) ($data['id_chat'] ?? 0);

$stmt = $pdo->prepare("
    SELECT id_land
    FROM lands
    WHERE slug = 'city'
    LIMIT 1
");
$stmt->execute();
$land = $stmt->fetch();

if (!$land) {
    json_response(['success' => false, 'error' => 'Land non trovata.']);
}

$stmt = $pdo->prepare("
    SELECT
        id_character,
        active_chat_id,
        active_chat_session_id
    FROM characters
    WHERE id_user = :id_user
    AND id_land = :id_land
    LIMIT 1
");
$stmt->execute([
    'id_user' => (int) $user['id_user'],
    'id_land' => (int) $land['id_land'],
]);

$character = $stmt->fetch();

if (!$character) {
    json_response(['success' => false, 'error' => 'Personaggio non trovato.']);
}

$idCharacter = (int) $character['id_character'];
$activeChatId = (int) ($character['active_chat_id'] ?? 0);
$activeSessionId = (int) ($character['active_chat_session_id'] ?? 0);

if ($idChat > 0 && $activeChatId > 0 && $activeChatId !== $idChat) {
    json_response([
        'success' => false,
        'error' => 'Non puoi uscire da una chat diversa da quella attiva.',
    ]);
}

$pdo->beginTransaction();

try {
    if ($activeSessionId > 0) {
        $stmt = $pdo->prepare("
            UPDATE city_chat_sessions
            SET ended_at = NOW(),
                last_seen_at = NOW(),
                is_active = 0
            WHERE id_session = :id_session
            AND id_character = :id_character
            LIMIT 1
        ");
        $stmt->execute([
            'id_session' => $activeSessionId,
            'id_character' => $idCharacter,
        ]);
    }

    $stmt = $pdo->prepare("
        UPDATE city_chat_sessions
        SET ended_at = COALESCE(ended_at, NOW()),
            last_seen_at = NOW(),
            is_active = 0
        WHERE id_character = :id_character
        AND is_active = 1
    ");
    $stmt->execute([
        'id_character' => $idCharacter,
    ]);

    $stmt = $pdo->prepare("
        UPDATE characters
        SET active_chat_id = NULL,
            active_chat_session_id = NULL
        WHERE id_character = :id_character
        LIMIT 1
    ");
    $stmt->execute([
        'id_character' => $idCharacter,
    ]);

    $pdo->commit();

    json_response(['success' => true]);
} catch (Throwable $exception) {
    $pdo->rollBack();

    json_response([
        'success' => false,
        'error' => 'Errore durante l’uscita di scena.',
    ]);
}

// by LaEmiX
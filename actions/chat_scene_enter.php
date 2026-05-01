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

require __DIR__ . '/../config/permissions.php';

$pdo = require __DIR__ . '/../config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user = $_SESSION['user'];

$rawInput = file_get_contents('php://input');
$data = json_decode((string) $rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

$idChat = (int) ($data['id_chat'] ?? 0);

if ($idChat <= 0) {
    json_response(['success' => false, 'error' => 'Chat non valida.']);
}

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

$isStaff = hasRole($user, 'master')
    || hasRole($user, 'gestore')
    || hasRole($user, 'admin');

$stmt = $pdo->prepare("
    SELECT
        id_chat,
        is_active,
        is_private,
        owner_character_id
    FROM city_chats
    WHERE id_chat = :id_chat
    LIMIT 1
");
$stmt->execute([
    'id_chat' => $idChat,
]);

$chat = $stmt->fetch();

if (!$chat || (int) $chat['is_active'] !== 1) {
    json_response(['success' => false, 'error' => 'Chat non disponibile.']);
}

$isPrivateChat = (int) $chat['is_private'] === 1;
$isOwnerChat = (int) ($chat['owner_character_id'] ?? 0) === $idCharacter;
$isInvitedChat = false;

if ($isPrivateChat && !$isStaff && !$isOwnerChat) {
    $stmt = $pdo->prepare("
        SELECT id_invite
        FROM city_chat_invites
        WHERE id_chat = :id_chat
        AND id_character = :id_character
        AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute([
        'id_chat' => $idChat,
        'id_character' => $idCharacter,
    ]);

    $isInvitedChat = (bool) $stmt->fetch();

    if (!$isInvitedChat) {
        json_response(['success' => false, 'error' => 'Questa chat privata è accessibile solo su invito.']);
    }
}

if ($activeChatId > 0 && $activeChatId !== $idChat) {
    json_response([
        'success' => false,
        'error' => 'Il personaggio è già in scena in un’altra chat.'
    ]);
}

$pdo->beginTransaction();

try {
    if ($activeChatId === $idChat && $activeSessionId > 0) {
        $stmt = $pdo->prepare("
            UPDATE city_chat_sessions
            SET last_seen_at = NOW(),
                is_active = 1
            WHERE id_session = :id_session
            AND id_character = :id_character
            LIMIT 1
        ");
        $stmt->execute([
            'id_session' => $activeSessionId,
            'id_character' => $idCharacter,
        ]);

        $pdo->commit();

        json_response([
            'success' => true,
            'id_session' => $activeSessionId,
            'already_active' => true,
        ]);
    }

    $stmt = $pdo->prepare("
        INSERT INTO city_chat_sessions
        (
            id_user,
            id_character,
            id_chat,
            started_at,
            last_seen_at,
            is_active
        )
        VALUES
        (
            :id_user,
            :id_character,
            :id_chat,
            NOW(),
            NOW(),
            1
        )
    ");
    $stmt->execute([
        'id_user' => (int) $user['id_user'],
        'id_character' => $idCharacter,
        'id_chat' => $idChat,
    ]);

    $idSession = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        UPDATE characters
        SET active_chat_id = :active_chat_id,
            active_chat_session_id = :active_chat_session_id
        WHERE id_character = :id_character
        LIMIT 1
    ");
    $stmt->execute([
        'active_chat_id' => $idChat,
        'active_chat_session_id' => $idSession,
        'id_character' => $idCharacter,
    ]);

    $pdo->commit();

    json_response([
        'success' => true,
        'id_session' => $idSession,
        'already_active' => false,
    ]);
} catch (Throwable $exception) {
    $pdo->rollBack();

    json_response([
        'success' => false,
        'error' => 'Errore durante l’ingresso in scena.',
    ]);
}

// by LaEmiX
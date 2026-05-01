<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=UTF-8');

function json_response(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
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
$messageType = (string) ($data['message_type'] ?? 'azione');
$content = trim((string) ($data['content'] ?? ''));
$context = trim((string) ($data['context'] ?? ''));
$targetName = trim((string) ($data['target'] ?? ''));

$allowedTypes = ['azione', 'master', 'sussurro', 'offgame'];

if ($idChat <= 0) {
    json_response(['success' => false, 'error' => 'Chat non valida.']);
}

if (!in_array($messageType, $allowedTypes, true)) {
    json_response(['success' => false, 'error' => 'Tipo messaggio non valido.']);
}

if ($content === '') {
    json_response(['success' => false, 'error' => 'Il messaggio è vuoto.']);
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
        name,
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

$isStaff = hasRole($user, 'master')
    || hasRole($user, 'gestore')
    || hasRole($user, 'admin');

$activeChatId = (int) ($character['active_chat_id'] ?? 0);
$activeSessionId = (int) ($character['active_chat_session_id'] ?? 0);

if (!$isStaff && ($activeChatId !== $idChat || $activeSessionId <= 0)) {
    json_response([
        'success' => false,
        'error' => 'Devi entrare in scena per scrivere in questa chat.',
    ]);
}

$stmt = $pdo->prepare("
    SELECT
        id_chat,
        id_district,
        name,
        is_private,
        owner_character_id,
        min_action_chars,
        max_action_chars,
        is_active
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

if ((int) $chat['is_private'] === 1) {
    $canEnterPrivate = $isStaff || (int) ($chat['owner_character_id'] ?? 0) === $idCharacter;

    if (!$canEnterPrivate) {
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

        $canEnterPrivate = (bool) $stmt->fetch();
    }

    if (!$canEnterPrivate) {
        json_response(['success' => false, 'error' => 'Non hai accesso a questa chat privata.']);
    }
}

if ($messageType === 'master' && !hasRole($user, 'master')) {
    json_response(['success' => false, 'error' => 'Non puoi inviare messaggi master.']);
}

if ($messageType === 'azione') {
    $length = mb_strlen($content);

    if ($length < (int) $chat['min_action_chars']) {
        json_response(['success' => false, 'error' => 'Il messaggio Azione è troppo corto.']);
    }

    if ($length > (int) $chat['max_action_chars']) {
        json_response(['success' => false, 'error' => 'Il messaggio Azione è troppo lungo.']);
    }
} else {
    $context = '';
}

$targetCharacterId = null;

if ($messageType === 'sussurro') {
    if ($targetName === '') {
        json_response(['success' => false, 'error' => 'Devi indicare un destinatario.']);
    }

    $stmt = $pdo->prepare("
        SELECT id_character
        FROM characters
        WHERE name = :name
        AND id_land = :id_land
        LIMIT 1
    ");
    $stmt->execute([
        'name' => $targetName,
        'id_land' => (int) $land['id_land'],
    ]);

    $target = $stmt->fetch();

    if (!$target) {
        json_response(['success' => false, 'error' => 'Destinatario non trovato.']);
    }

    $targetCharacterId = (int) $target['id_character'];
}

$pdo->beginTransaction();

try {
    if (!$isStaff && $activeSessionId > 0) {
        $stmt = $pdo->prepare("
            UPDATE city_chat_sessions
            SET last_seen_at = NOW()
            WHERE id_session = :id_session
            AND id_character = :id_character
            AND id_chat = :id_chat
            AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([
            'id_session' => $activeSessionId,
            'id_character' => $idCharacter,
            'id_chat' => $idChat,
        ]);
    }

    $stmt = $pdo->prepare("
        UPDATE city_chat_messages
        SET is_editable = 0
        WHERE id_chat = :id_chat
        AND id_character = :id_character
        AND is_editable = 1
    ");
    $stmt->execute([
        'id_chat' => $idChat,
        'id_character' => $idCharacter,
    ]);

    $stmt = $pdo->prepare("
        INSERT INTO city_chat_messages
        (
            id_chat,
            id_character,
            message_type,
            target_character_id,
            context,
            content,
            payload,
            is_editable
        )
        VALUES
        (
            :id_chat,
            :id_character,
            :message_type,
            :target_character_id,
            :context,
            :content,
            NULL,
            1
        )
    ");

    $stmt->execute([
        'id_chat' => $idChat,
        'id_character' => $idCharacter,
        'message_type' => $messageType,
        'target_character_id' => $targetCharacterId,
        'context' => $context !== '' ? $context : null,
        'content' => $content,
    ]);

    $idMessage = (int) $pdo->lastInsertId();

    $pdo->commit();

    json_response([
        'success' => true,
        'id_message' => $idMessage,
    ]);
} catch (Throwable $exception) {
    $pdo->rollBack();

    json_response([
        'success' => false,
        'error' => 'Errore durante l’invio.',
    ]);
}

// by LaEmiX
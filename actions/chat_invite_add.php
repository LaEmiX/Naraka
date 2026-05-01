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
    json_response(['success' => false, 'message' => 'Sessione non valida.']);
}

require __DIR__ . '/../config/permissions.php';

$pdo = require __DIR__ . '/../config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user = $_SESSION['user'];

$data = json_decode((string) file_get_contents('php://input'), true);

if (!is_array($data)) {
    $data = [];
}

$idChat = (int) ($data['id_chat'] ?? 0);
$characterName = trim((string) ($data['character_name'] ?? ''));

if ($idChat <= 0 || $characterName === '') {
    json_response(['success' => false, 'message' => 'Dati non validi.']);
}

/* LAND */
$stmt = $pdo->prepare("
    SELECT id_land
    FROM lands
    WHERE slug = 'city'
    LIMIT 1
");
$stmt->execute();

$land = $stmt->fetch();

if (!$land) {
    json_response(['success' => false, 'message' => 'Land non trovata.']);
}

$idLand = (int) $land['id_land'];

/* CHAT */
$stmt = $pdo->prepare("
    SELECT id_chat, is_private, owner_character_id
    FROM city_chats
    WHERE id_chat = :id_chat
    LIMIT 1
");
$stmt->execute(['id_chat' => $idChat]);

$chat = $stmt->fetch();

if (!$chat || (int) $chat['is_private'] !== 1) {
    json_response(['success' => false, 'message' => 'Chat privata non valida.']);
}

/* PERSONAGGIO CORRENTE */
$stmt = $pdo->prepare("
    SELECT id_character
    FROM characters
    WHERE id_user = :id_user
    AND id_land = :id_land
    LIMIT 1
");
$stmt->execute([
    'id_user' => (int) $user['id_user'],
    'id_land' => $idLand,
]);

$currentCharacter = $stmt->fetch();
$idCurrentCharacter = $currentCharacter ? (int) $currentCharacter['id_character'] : 0;

$isStaff = hasRole($user, 'master')
    || hasRole($user, 'gestore')
    || hasRole($user, 'admin');

$isOwner = (int) $chat['owner_character_id'] === $idCurrentCharacter;

if (!$isStaff && !$isOwner) {
    json_response(['success' => false, 'message' => 'Permesso negato.']);
}

/* TARGET */
$stmt = $pdo->prepare("
    SELECT id_character, name
    FROM characters
    WHERE name = :name
    AND id_land = :id_land
    LIMIT 1
");
$stmt->execute([
    'name' => $characterName,
    'id_land' => $idLand,
]);

$target = $stmt->fetch();

if (!$target) {
    json_response(['success' => false, 'message' => 'Personaggio non trovato.']);
}

$idTarget = (int) $target['id_character'];

/* NO OWNER */
if ($idTarget === (int) $chat['owner_character_id']) {
    json_response(['success' => false, 'message' => 'Il proprietario ha già accesso.']);
}

/* CONTROLLO ESISTENTE */
$stmt = $pdo->prepare("
    SELECT id_invite
    FROM city_chat_invites
    WHERE id_chat = :id_chat
    AND id_character = :id_character
    LIMIT 1
");
$stmt->execute([
    'id_chat' => $idChat,
    'id_character' => $idTarget,
]);

$existing = $stmt->fetch();

if ($existing) {
    /* RIATTIVA */
    $stmt = $pdo->prepare("
        UPDATE city_chat_invites
        SET is_active = 1,
            revoked_at = NULL,
            invited_by_character_id = :by
        WHERE id_invite = :id_invite
        LIMIT 1
    ");
    $stmt->execute([
        'by' => $idCurrentCharacter,
        'id_invite' => (int) $existing['id_invite'],
    ]);
} else {
    /* INSERT */
    $stmt = $pdo->prepare("
        INSERT INTO city_chat_invites
        (
            id_chat,
            id_character,
            invited_by_character_id,
            is_active,
            created_at,
            revoked_at
        )
        VALUES
        (
            :id_chat,
            :id_character,
            :by,
            1,
            NOW(),
            NULL
        )
    ");
    $stmt->execute([
        'id_chat' => $idChat,
        'id_character' => $idTarget,
        'by' => $idCurrentCharacter,
    ]);
}

json_response([
    'success' => true,
    'message' => 'Invito aggiunto.',
    'character' => [
        'id_character' => $idTarget,
        'name' => (string) $target['name'],
    ],
]);

// by LaEmiX
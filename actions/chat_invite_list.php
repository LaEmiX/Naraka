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
    json_response([
        'success' => false,
        'message' => 'Sessione non valida.',
    ]);
}

require __DIR__ . '/../config/permissions.php';

$pdo = require __DIR__ . '/../config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user = $_SESSION['user'];

$input = json_decode((string) file_get_contents('php://input'), true);

if (!is_array($input)) {
    $input = $_GET;
}

$idChat = (int) ($input['id_chat'] ?? 0);

if ($idChat <= 0) {
    json_response([
        'success' => false,
        'message' => 'Chat non valida.',
    ]);
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
    json_response([
        'success' => false,
        'message' => 'Land non trovata.',
    ]);
}

$idLand = (int) $land['id_land'];

/* CHAT */
$stmt = $pdo->prepare("
    SELECT id_chat, is_private, owner_character_id
    FROM city_chats
    WHERE id_chat = :id_chat
    LIMIT 1
");
$stmt->execute([
    'id_chat' => $idChat,
]);

$chat = $stmt->fetch();

if (!$chat || (int) $chat['is_private'] !== 1) {
    json_response([
        'success' => false,
        'message' => 'Chat privata non valida.',
    ]);
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
$idCharacter = $currentCharacter ? (int) $currentCharacter['id_character'] : 0;

$isStaff = hasRole($user, 'master')
    || hasRole($user, 'gestore')
    || hasRole($user, 'admin');

$isOwner = (int) $chat['owner_character_id'] === $idCharacter;

if (!$isStaff && !$isOwner) {
    json_response([
        'success' => false,
        'message' => 'Permesso negato.',
    ]);
}

/* LISTA INVITATI */
$stmt = $pdo->prepare("
    SELECT
        i.id_character,
        c.name AS character_name
    FROM city_chat_invites i
    JOIN characters c ON c.id_character = i.id_character
    WHERE i.id_chat = :id_chat
    AND i.is_active = 1
    AND c.id_land = :id_land
    ORDER BY c.name ASC
");
$stmt->execute([
    'id_chat' => $idChat,
    'id_land' => $idLand,
]);

json_response([
    'success' => true,
    'invites' => $stmt->fetchAll(),
]);

// by LaEmiX
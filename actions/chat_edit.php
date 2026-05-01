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

$idMessage = (int) ($data['id_message'] ?? 0);
$content = trim((string) ($data['content'] ?? ''));
$context = trim((string) ($data['context'] ?? ''));

if ($idMessage <= 0) {
    json_response(['success' => false, 'error' => 'Messaggio non valido.']);
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
    SELECT id_character
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

$stmt = $pdo->prepare("
    SELECT
        m.id_message,
        m.id_chat,
        m.id_character,
        m.message_type,
        m.is_editable,
        ch.min_action_chars,
        ch.max_action_chars
    FROM city_chat_messages m
    JOIN city_chats ch ON ch.id_chat = m.id_chat
    WHERE m.id_message = :id_message
    LIMIT 1
");
$stmt->execute([
    'id_message' => $idMessage,
]);

$message = $stmt->fetch();

if (!$message) {
    json_response(['success' => false, 'error' => 'Messaggio non trovato.']);
}

if ((int) $message['id_character'] !== $idCharacter) {
    json_response(['success' => false, 'error' => 'Puoi modificare solo i tuoi messaggi.']);
}

if ((int) $message['is_editable'] !== 1) {
    json_response(['success' => false, 'error' => 'Questo messaggio non è più modificabile.']);
}

if ((string) $message['message_type'] === 'azione') {
    $length = mb_strlen($content);

    if ($length < (int) $message['min_action_chars']) {
        json_response(['success' => false, 'error' => 'Il messaggio Azione è troppo corto.']);
    }

    if ($length > (int) $message['max_action_chars']) {
        json_response(['success' => false, 'error' => 'Il messaggio Azione è troppo lungo.']);
    }
} else {
    $context = '';
}

$stmt = $pdo->prepare("
    UPDATE city_chat_messages
    SET content = :content,
        context = :context,
        updated_at = CURRENT_TIMESTAMP
    WHERE id_message = :id_message
    AND id_character = :id_character
    AND is_editable = 1
    LIMIT 1
");

$stmt->execute([
    'content' => $content,
    'context' => $context !== '' ? $context : null,
    'id_message' => $idMessage,
    'id_character' => $idCharacter,
]);

json_response(['success' => true]);

// by LaEmiX

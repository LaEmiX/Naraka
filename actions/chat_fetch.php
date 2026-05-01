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

function format_chat_text(string $text): string
{
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    $text = preg_replace('/\[(.*?)\]/', '&lt;$1&gt;', $text);
    $text = preg_replace('/\&lt;(.*?)\&gt;/', '<span class="chat-action-text">&lt;$1&gt;</span>', $text);

    return nl2br((string) $text);
}

function render_message_html(array $msg, array $user, int $idCharacter): string
{
    $type = (string) $msg['message_type'];
    $time = date('H:i', strtotime((string) $msg['created_at']));
    $author = (string) $msg['character_name'];
    $targetName = (string) ($msg['target_name'] ?? '');
    $context = (string) ($msg['context'] ?? '');
    $content = format_chat_text((string) $msg['content']);

    ob_start();
    ?>

    <?php if ($type === 'azione') { ?>

        <span class="chat-time">[<?php echo e($time); ?>]</span>
        <span class="chat-author"><?php echo e($author); ?></span>

        <?php if ($context !== '') { ?>
            <span class="chat-tag">[<?php echo e($context); ?>]</span>
        <?php } ?>

        <span class="chat-text"><?php echo $content; ?></span>

    <?php } elseif ($type === 'master') { ?>

        <span class="chat-text"><?php echo $content; ?></span>

    <?php } elseif ($type === 'sussurro') { ?>

        <?php if ((int) $msg['id_character'] === $idCharacter) { ?>
            <span class="chat-text">Sussurri a <?php echo e($targetName); ?>: <?php echo $content; ?></span>
        <?php } else { ?>
            <span class="chat-author"><?php echo e($author); ?></span>
            <span class="chat-text">ti sussurra: <?php echo $content; ?></span>
        <?php } ?>

    <?php } elseif ($type === 'offgame') { ?>

        <span class="chat-author"><?php echo e($author); ?></span>
        <span class="chat-off-label">[OFF]</span>
        <span class="chat-text"><?php echo $content; ?></span>

    <?php } ?>

    <?php if ((int) $msg['id_character'] === $idCharacter && (int) $msg['is_editable'] === 1) { ?>
        <button
            type="button"
            class="chat-edit-btn"
            data-edit-message="<?php echo (int) $msg['id_message']; ?>"
        >
            Modifica
        </button>
    <?php } ?>

    <?php
    return trim((string) ob_get_clean());
}

if (!isset($_SESSION['user'])) {
    json_response([]);
}

require __DIR__ . '/../config/permissions.php';

$pdo = require __DIR__ . '/../config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user = $_SESSION['user'];

$idChat = (int) ($_GET['id_chat'] ?? 0);
$lastId = (int) ($_GET['last_id'] ?? 0);

if ($idChat <= 0) {
    json_response([]);
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
    json_response([]);
}

$stmt = $pdo->prepare("
    SELECT id_character, name
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
    json_response([]);
}

$idCharacter = (int) $character['id_character'];

$stmt = $pdo->prepare("
    SELECT id_chat, is_private, owner_character_id, is_active
    FROM city_chats
    WHERE id_chat = :id_chat
    LIMIT 1
");
$stmt->execute([
    'id_chat' => $idChat,
]);

$chat = $stmt->fetch();

if (!$chat || (int) $chat['is_active'] !== 1) {
    json_response([]);
}

if ((int) $chat['is_private'] === 1) {
    $canEnterPrivate = hasRole($user, 'master')
        || hasRole($user, 'gestore')
        || hasRole($user, 'admin')
        || (int) ($chat['owner_character_id'] ?? 0) === $idCharacter;

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
        json_response([]);
    }
}

$stmt = $pdo->prepare("
    SELECT
        m.id_message,
        m.id_chat,
        m.id_character,
        m.message_type,
        m.target_character_id,
        m.context,
        m.content,
        m.created_at,
        m.is_editable,
        c.name AS character_name,
        target.name AS target_name
    FROM city_chat_messages m
    JOIN characters c ON c.id_character = m.id_character
    LEFT JOIN characters target ON target.id_character = m.target_character_id
    WHERE m.id_chat = :id_chat
    AND m.id_message > :last_id
    AND m.created_at >= (NOW() - INTERVAL 3 HOUR)
    ORDER BY m.id_message ASC
    LIMIT 50
");

$stmt->execute([
    'id_chat' => $idChat,
    'last_id' => $lastId,
]);

$messages = $stmt->fetchAll();
$output = [];

foreach ($messages as $msg) {
    $type = (string) $msg['message_type'];

    if ($type === 'sussurro') {
        $isSender = (int) $msg['id_character'] === $idCharacter;
        $isTarget = (int) $msg['target_character_id'] === $idCharacter;

        if (!$isSender && !$isTarget) {
            continue;
        }
    }

    $output[] = [
        'id_message' => (int) $msg['id_message'],
        'message_type' => $type,
        'is_editable' => (int) $msg['is_editable'],
        'content' => (string) $msg['content'],
        'context' => (string) ($msg['context'] ?? ''),
        'target_name' => (string) ($msg['target_name'] ?? ''),
        'created_at' => (string) $msg['created_at'],
        'html' => render_message_html($msg, $user, $idCharacter),
    ];
}

json_response($output);

// by LaEmiX
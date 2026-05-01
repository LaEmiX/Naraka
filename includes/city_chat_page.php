<?php

declare(strict_types=1);

if (!isset($pdo, $user, $character, $district, $chat)) {
    return;
}

require_once __DIR__ . '/../config/permissions.php';
require_once __DIR__ . '/chat_helpers.php';

$idChat = (int) $chat['id_chat'];
$idCharacter = (int) $character['id_character'];

$isStaff = hasRole($user, 'master')
    || hasRole($user, 'gestore')
    || hasRole($user, 'admin');

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
        echo '<div class="chat-scene-warning">Questa chat privata è accessibile solo su invito.</div>';
        return;
    }
}

$stmt = $pdo->prepare("
    SELECT active_chat_id, active_chat_session_id
    FROM characters
    WHERE id_character = :id_character
    LIMIT 1
");
$stmt->execute([
    'id_character' => $idCharacter,
]);

$sceneCharacter = $stmt->fetch();

$activeChatId = (int) ($sceneCharacter['active_chat_id'] ?? 0);
$activeChatSessionId = (int) ($sceneCharacter['active_chat_session_id'] ?? 0);

if (!$isStaff && $activeChatId > 0 && $activeChatSessionId > 0 && $activeChatId !== $idChat) {
    echo '<div class="chat-scene-warning">Il personaggio è già in scena in un’altra chat. Devi uscire di scena prima di cambiare chat.</div>';
    return;
}

$isInSceneHere = $activeChatId === $idChat && $activeChatSessionId > 0;
$isLockedElsewhere = !$isStaff && $activeChatId > 0 && $activeChatId !== $idChat;
$canOpenWrite = $isStaff || $isInSceneHere;

$stmt = $pdo->prepare("
    SELECT
        m.id_message,
        m.id_chat,
        m.id_character,
        m.message_type,
        m.target_character_id,
        m.context,
        m.content,
        m.payload,
        m.created_at,
        m.is_editable,
        c.name AS character_name,
        target.name AS target_name
    FROM city_chat_messages m
    JOIN characters c ON c.id_character = m.id_character
    LEFT JOIN characters target ON target.id_character = m.target_character_id
    WHERE m.id_chat = :id_chat
    AND m.created_at >= (NOW() - INTERVAL 3 HOUR)
    ORDER BY m.created_at ASC, m.id_message ASC
    LIMIT 100
");

$stmt->execute([
    'id_chat' => $idChat,
]);

$messages = $stmt->fetchAll();

?>

<link rel="stylesheet" href="/themes/city_chat.css?v=chat-03">

<div class="chat-container">

    <?php if ($isLockedElsewhere) { ?>
        <div class="chat-scene-warning">
            Il personaggio è già in scena in un’altra chat. Devi uscire di scena prima di cambiare chat.
        </div>
    <?php } ?>

    <div id="chat-messages" class="chat-messages">

        <?php foreach ($messages as $msg) { ?>

            <?php
                $type = (string) $msg['message_type'];

                if ($type === 'sussurro') {
                    $isSender = (int) $msg['id_character'] === $idCharacter;
                    $isTarget = (int) $msg['target_character_id'] === $idCharacter;

                    if (!$isSender && !$isTarget) {
                        continue;
                    }
                }

                $time = date('H:i', strtotime((string) $msg['created_at']));
                $author = (string) $msg['character_name'];
                $targetName = (string) ($msg['target_name'] ?? '');
                $context = (string) ($msg['context'] ?? '');
                $rawContent = (string) $msg['content'];
                $content = format_chat_text($rawContent);
                $canEdit = (int) $msg['id_character'] === $idCharacter && (int) $msg['is_editable'] === 1;
            ?>

            <div
                class="chat-message chat-message-<?php echo e($type); ?>"
                data-id="<?php echo (int) $msg['id_message']; ?>"
                data-type="<?php echo e($type); ?>"
                data-content="<?php echo e($rawContent); ?>"
                data-context="<?php echo e($context); ?>"
                data-target-name="<?php echo e($targetName); ?>"
                data-created-at="<?php echo e((string) $msg['created_at']); ?>"
            >

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

                <?php if ($canEdit) { ?>
                    <button
                        type="button"
                        class="chat-edit-btn"
                        data-edit-message="<?php echo (int) $msg['id_message']; ?>"
                    >
                        Modifica
                    </button>
                <?php } ?>

            </div>

        <?php } ?>

    </div>

    <div class="chat-actions-bar">

        <?php if (!$isStaff) { ?>
            <button
                class="chat-btn"
                type="button"
                id="btn-scene-enter"
                <?php echo ($isInSceneHere || $isLockedElsewhere) ? 'disabled' : ''; ?>
            >
                Entra in scena
            </button>

            <button
                class="chat-btn chat-btn-danger"
                type="button"
                id="btn-scene-leave"
                <?php echo !$isInSceneHere ? 'disabled' : ''; ?>
            >
                Esci di scena
            </button>
        <?php } ?>

        <button
            class="chat-btn"
            type="button"
            id="btn-write"
            <?php echo (!$canOpenWrite || $isLockedElsewhere) ? 'disabled' : ''; ?>
        >
            Scrivi
        </button>

        <button class="chat-btn" type="button" id="btn-skill">Usa abilità</button>
        <button class="chat-btn" type="button" id="btn-item">Usa oggetto</button>

        <?php if ((int) $chat['allow_dice'] === 1) { ?>
            <button class="chat-btn" type="button" id="btn-dice">Dado</button>
        <?php } ?>

        <?php if ((int) $chat['allow_minigames'] === 1) { ?>
            <button class="chat-btn" type="button" id="btn-minigame">Minigiochi</button>
        <?php } ?>

        <?php if ((int) $chat['allow_combat'] === 1) { ?>
            <button class="chat-btn chat-btn-danger" type="button" id="btn-attack">Attacca</button>
        <?php } ?>

        <?php if ((int) $chat['allow_search'] === 1) { ?>
            <button class="chat-btn" type="button" id="btn-search">Ricerca</button>
        <?php } ?>

        <input
            type="text"
            id="chat-target"
            class="chat-target-input"
            placeholder="Target..."
        >

        <?php if ((int) $chat['is_private'] === 1 && ($isStaff || (int) $chat['owner_character_id'] === $idCharacter)) { ?>
            <button class="chat-btn" type="button" id="btn-invites">Inviti</button>
        <?php } ?>

        <div class="chat-spacer"></div>

        <button class="chat-btn" type="button" id="btn-info">Info</button>
        <button class="chat-btn" type="button" id="btn-plus">+</button>
        <button class="chat-btn" type="button" id="btn-minus">-</button>

    </div>

</div>

<div id="chat-write-modal" class="chat-modal">
    <div class="chat-modal-content" id="chat-write-content">

        <div class="chat-modal-drag-handle" id="chat-write-drag-handle"></div>

        <button type="button" class="chat-modal-close" id="chat-write-close" aria-label="Chiudi">×</button>
        <button type="button" class="chat-modal-minimize" id="chat-write-minimize" aria-label="Riduci">–</button>

        <div id="chat-type-row">
            <label class="chat-modal-label" for="chat-type">Tipo messaggio</label>
            <select id="chat-type" class="chat-modal-field">
                <option value="azione">Azione</option>

                <?php if (hasRole($user, 'master') || hasRole($user, 'gestore') || hasRole($user, 'admin')) { ?>
                    <option value="master">Master</option>
                <?php } ?>

                <option value="sussurro">Sussurro</option>
                <option value="offgame">Offgame</option>
            </select>
        </div>

        <div id="chat-tag-row">
            <label class="chat-modal-label" for="chat-tag-input">Tag</label>
            <input
                type="text"
                id="chat-tag-input"
                class="chat-modal-field"
                placeholder="Es. Ingresso, Bancone, Strada..."
            >
        </div>

        <div id="chat-whisper-row" style="display:none;">
            <label class="chat-modal-label" for="chat-whisper-target">Destinatario sussurro</label>
            <input
                type="text"
                id="chat-whisper-target"
                class="chat-modal-field"
                placeholder="Nome personaggio"
            >
        </div>

        <label class="chat-modal-label" for="chat-text">Testo</label>
        <textarea
            id="chat-text"
            class="chat-modal-textarea"
            data-min-action="<?php echo (int) $chat['min_action_chars']; ?>"
            data-max-action="<?php echo (int) $chat['max_action_chars']; ?>"
        ></textarea>

        <div id="chat-counter" class="chat-counter"></div>

        <button type="button" class="chat-btn" id="chat-send" disabled>Invia</button>

    </div>
</div>

<button type="button" id="chat-write-restore" class="chat-write-restore">
    SCRIVI
</button>

<div id="chat-edit-modal" class="chat-modal">
    <div class="chat-modal-content">

        <button type="button" class="chat-modal-close" id="chat-edit-close" aria-label="Chiudi">×</button>

        <input type="hidden" id="chat-edit-id" value="">
        <input type="hidden" id="chat-edit-type" value="">

        <div id="chat-edit-tag-row">
            <label class="chat-modal-label" for="chat-edit-tag-input">Tag</label>
            <input
                type="text"
                id="chat-edit-tag-input"
                class="chat-modal-field"
                placeholder="Es. Ingresso, Bancone, Strada..."
            >
        </div>

        <label class="chat-modal-label" for="chat-edit-text">Modifica messaggio</label>
        <textarea
            id="chat-edit-text"
            class="chat-modal-textarea"
            data-min-action="<?php echo (int) $chat['min_action_chars']; ?>"
            data-max-action="<?php echo (int) $chat['max_action_chars']; ?>"
        ></textarea>

        <div id="chat-edit-counter" class="chat-counter"></div>

        <button type="button" class="chat-btn" id="chat-edit-save">Salva modifica</button>

    </div>
</div>

<div id="chat-info-modal" class="chat-modal">
    <div class="chat-modal-content">

        <button type="button" class="chat-modal-close" id="chat-info-close" aria-label="Chiudi">×</button>

        <div id="chat-info-body">
            <?php echo render_chat_bbcode((string) ($chat['description'] ?? '')); ?>
        </div>

    </div>
</div>

<div id="chat-invites-modal" class="chat-modal">
    <div class="chat-modal-content">

        <button type="button" class="chat-modal-close" id="chat-invites-close" aria-label="Chiudi">×</button>

        <label class="chat-modal-label" for="chat-invite-name">Invita personaggio</label>
        <input
            type="text"
            id="chat-invite-name"
            class="chat-modal-field"
            placeholder="Nome personaggio"
        >

        <button type="button" class="chat-btn" id="chat-invite-add">Invita</button>

        <div id="chat-invite-feedback" class="chat-counter"></div>

        <div id="chat-invites-list"></div>

    </div>
</div>

<script>
window.NarakaChatConfig = {
    idChat: <?php echo $idChat; ?>,
    idCharacter: <?php echo $idCharacter; ?>,
    isStaff: <?php echo $isStaff ? 'true' : 'false'; ?>,
    isInSceneHere: <?php echo $isInSceneHere ? 'true' : 'false'; ?>,
    isLockedElsewhere: <?php echo $isLockedElsewhere ? 'true' : 'false'; ?>
};
</script>

<script src="/themes/js/chat_helpers.js?v=01"></script>
<script src="/themes/js/chat_state.js?v=01"></script>
<script src="/themes/js/chat_dom.js?v=01"></script>
<script src="/themes/js/chat_modal_drag.js?v=01"></script>
<script src="/themes/js/chat_messages.js?v=01"></script>
<script src="/themes/js/chat_scene.js?v=01"></script>
<script src="/themes/js/chat_write.js?v=01"></script>
<script src="/themes/js/chat_edit.js?v=01"></script>
<script src="/themes/js/chat_invites.js?v=01"></script>
<script src="/themes/js/chat_info.js?v=01"></script>
<script src="/themes/js/chat_bootstrap.js?v=01"></script>

<?php
// by LaEmiX
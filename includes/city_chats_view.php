<?php

declare(strict_types=1);

if (!isset($pdo)) {
    return;
}

if (!isset($district) || !is_array($district)) {
    return;
}

if (!isset($character) || !is_array($character)) {
    return;
}

if (!isset($user) || !is_array($user)) {
    return;
}

if (!function_exists('hasRole')) {
    require __DIR__ . '/../config/permissions.php';
}

if (!function_exists('render_bbcode')) {
    function render_bbcode(string $value): string
    {
        $text = e($value);

        $text = preg_replace('/\[b\](.*?)\[\/b\]/is', '<strong>$1</strong>', $text);
        $text = preg_replace('/\[u\](.*?)\[\/u\]/is', '<u>$1</u>', $text);
        $text = preg_replace('/\[i\](.*?)\[\/i\]/is', '<em>$1</em>', $text);
        $text = preg_replace('/\[center\](.*?)\[\/center\]/is', '<div style="text-align:center;">$1</div>', $text);
        $text = preg_replace('/\[left\](.*?)\[\/left\]/is', '<div style="text-align:left;">$1</div>', $text);
        $text = preg_replace('/\[right\](.*?)\[\/right\]/is', '<div style="text-align:right;">$1</div>', $text);
        $text = preg_replace('/\[justify\](.*?)\[\/justify\]/is', '<div style="text-align:justify;">$1</div>', $text);
        $text = preg_replace('/\[color=([#a-zA-Z0-9]+)\](.*?)\[\/color\]/is', '<span style="color:$1;">$2</span>', $text);

        return nl2br((string) $text);
    }
}

$idDistrict = (int) $district['id_district'];
$districtSlug = (string) $district['slug'];
$idCharacter = (int) $character['id_character'];

$isStaff = hasRole($user, 'master')
    || hasRole($user, 'gestore')
    || hasRole($user, 'admin');

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
$hasActiveScene = !$isStaff && $activeChatId > 0 && $activeChatSessionId > 0;

$stmt = $pdo->prepare("
    SELECT
        id_chat,
        id_district,
        name,
        slug,
        description,
        image_path,
        is_private,
        owner_character_id,
        allow_search,
        allow_dice,
        allow_combat,
        allow_minigames,
        min_action_chars,
        max_action_chars,
        is_active,
        sort_order
    FROM city_chats
    WHERE id_district = :id_district
    AND is_active = 1
    ORDER BY sort_order ASC, name ASC
");

$stmt->execute([
    'id_district' => $idDistrict,
]);

$visibleChats = $stmt->fetchAll();

?>

<div class="districts-grid">

    <?php foreach ($visibleChats as $chat) { ?>

        <?php
            $chatId = (int) $chat['id_chat'];

            $isPrivateChat = (int) $chat['is_private'] === 1;
            $ownerCharacterId = isset($chat['owner_character_id']) ? (int) $chat['owner_character_id'] : 0;
            $isOwnerChat = $ownerCharacterId === $idCharacter;

            $isInvitedChat = false;

            if ($isPrivateChat && !$isStaff && !$isOwnerChat) {
                $stmtInvite = $pdo->prepare("
                    SELECT id_invite
                    FROM city_chat_invites
                    WHERE id_chat = :id_chat
                    AND id_character = :id_character
                    AND is_active = 1
                    LIMIT 1
                ");
                $stmtInvite->execute([
                    'id_chat' => $chatId,
                    'id_character' => $idCharacter,
                ]);

                $isInvitedChat = (bool) $stmtInvite->fetch();
            }

            $canEnterPrivateChat = !$isPrivateChat || $isStaff || $isOwnerChat || $isInvitedChat;
            $isLockedChat = ($hasActiveScene && $activeChatId !== $chatId) || !$canEnterPrivateChat;

            $lockLabel = ($hasActiveScene && $activeChatId !== $chatId) ? 'In scena altrove' : '';
            $lockTitle = ($hasActiveScene && $activeChatId !== $chatId)
                ? 'Il personaggio è già in scena in un’altra chat.'
                : 'Questa chat privata è accessibile solo su invito.';
        ?>

        <div class="district-card <?php echo $isLockedChat ? 'district-card-locked' : ''; ?>">

            <?php if ($isLockedChat) { ?>

                <div
                    class="district-image-wrap district-image-wrap-locked"
                    aria-disabled="true"
                    title="<?php echo e($lockTitle); ?>"
                >
                    <img
                        src="<?php echo e((string) ($chat['image_path'] ?: '/themes/images/default_chat.jpg')); ?>"
                        class="district-image"
                        alt="<?php echo e((string) $chat['name']); ?>"
                    >
                </div>

            <?php } else { ?>

                <a href="/index.php?district=<?php echo e($districtSlug); ?>&chat=<?php echo e((string) $chat['slug']); ?>" class="district-image-wrap">
                    <img
                        src="<?php echo e((string) ($chat['image_path'] ?: '/themes/images/default_chat.jpg')); ?>"
                        class="district-image"
                        alt="<?php echo e((string) $chat['name']); ?>"
                    >
                </a>

            <?php } ?>

            <div class="district-meta">
                <span class="district-name"><?php echo e((string) $chat['name']); ?></span>

                <?php if ($lockLabel !== '') { ?>
                    <span class="district-lock-label"><?php echo e($lockLabel); ?></span>
                <?php } ?>

                <button
                    type="button"
                    class="district-info-btn"
                    data-description="<?php echo e(render_bbcode((string) $chat['description'])); ?>"
                    aria-label="Descrizione chat"
                >
                    <svg viewBox="0 0 24 24" class="district-info-icon" aria-hidden="true" focusable="false">
                        <circle cx="12" cy="12" r="9"></circle>
                        <line x1="12" y1="11" x2="12" y2="16"></line>
                        <circle cx="12" cy="7.6" r="1.1"></circle>
                    </svg>
                </button>
            </div>

        </div>

    <?php } ?>

</div>

<div id="district-modal" class="district-modal">
    <div class="district-modal-content">
        <button type="button" class="district-modal-close" aria-label="Chiudi">×</button>
        <div id="district-modal-body"></div>
    </div>
</div>

<script>
document.querySelectorAll('.district-info-btn').forEach(function (button) {
    button.addEventListener('click', function () {
        const modal = document.getElementById('district-modal');
        const body = document.getElementById('district-modal-body');

        body.innerHTML = this.getAttribute('data-description') || '';
        modal.style.display = 'flex';
    });
});

document.querySelector('.district-modal-close').addEventListener('click', function () {
    document.getElementById('district-modal').style.display = 'none';
});

document.getElementById('district-modal').addEventListener('click', function (event) {
    if (event.target === this) {
        this.style.display = 'none';
    }
});
</script>

<?php
// by LaEmiX
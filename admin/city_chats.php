<?php

declare(strict_types=1);

session_start();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function make_slug(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim((string) $slug, '-');

    return $slug !== '' ? $slug : 'chat';
}

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

if (!isset($_SESSION['user'])) {
    header('Location: /app.php');
    exit;
}

require __DIR__ . '/../config/permissions.php';

$user = $_SESSION['user'];

if (!hasRole($user, 'admin')) {
    header('Location: /index.php');
    exit;
}

$pdo = require __DIR__ . '/../config/database.php';

$errors = [];
$success = '';
$editingChat = null;

$uploadDir = __DIR__ . '/../themes/images/city_chats';
$uploadWebPath = '/themes/images/city_chats';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$stmt = $pdo->query("
    SELECT id_district, name
    FROM city_districts
    ORDER BY sort_order ASC, name ASC
");

$districts = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT c.id_character, c.name
    FROM characters c
    JOIN lands l ON l.id_land = c.id_land
    WHERE l.slug = 'city'
    ORDER BY c.name ASC
");

$characters = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save') {
        $idChat = (int) ($_POST['id_chat'] ?? 0);
        $idDistrict = (int) ($_POST['id_district'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);

        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isPrivate = isset($_POST['is_private']) ? 1 : 0;

        $ownerCharacterId = isset($_POST['owner_character_id']) && $_POST['owner_character_id'] !== ''
            ? (int) $_POST['owner_character_id']
            : null;

        $allowSearch = isset($_POST['allow_search']) ? 1 : 0;
        $allowDice = isset($_POST['allow_dice']) ? 1 : 0;
        $allowCombat = isset($_POST['allow_combat']) ? 1 : 0;
        $allowMinigames = isset($_POST['allow_minigames']) ? 1 : 0;

        $minActionChars = (int) ($_POST['min_action_chars'] ?? 200);
        $maxActionChars = (int) ($_POST['max_action_chars'] ?? 4000);

        $imagePath = trim((string) ($_POST['current_image_path'] ?? ''));

        if ($idDistrict <= 0) {
            $errors[] = 'Il quartiere è obbligatorio.';
        }

        if ($name === '') {
            $errors[] = 'Il nome della chat è obbligatorio.';
        }

        if ($isPrivate === 1 && $ownerCharacterId === null) {
            $errors[] = 'Per una chat privata devi selezionare un proprietario.';
        }

        if ($minActionChars < 0) {
            $errors[] = 'Il minimo caratteri non può essere negativo.';
        }

        if ($maxActionChars <= 0) {
            $errors[] = 'Il massimo caratteri deve essere maggiore di zero.';
        }

        if ($maxActionChars < $minActionChars) {
            $errors[] = 'Il massimo caratteri non può essere inferiore al minimo.';
        }

        $slug = make_slug($name);

        if (!$errors) {
            $stmt = $pdo->prepare("
                SELECT id_chat
                FROM city_chats
                WHERE slug = :slug
                AND id_chat <> :id_chat
                LIMIT 1
            ");

            $stmt->execute([
                'slug' => $slug,
                'id_chat' => $idChat,
            ]);

            if ($stmt->fetch()) {
                $slug = $slug . '-' . time();
            }
        }

        if (!$errors && isset($_FILES['image']) && is_array($_FILES['image']) && (int) $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ((int) $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Errore durante il caricamento immagine.';
            } else {
                $tmpName = (string) $_FILES['image']['tmp_name'];
                $originalName = (string) $_FILES['image']['name'];

                $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                if (!in_array($extension, $allowedExtensions, true)) {
                    $errors[] = 'Formato immagine non valido.';
                }

                if (!$errors) {
                    $fileName = 'chat_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
                    $destination = $uploadDir . '/' . $fileName;

                    if (!move_uploaded_file($tmpName, $destination)) {
                        $errors[] = 'Impossibile salvare l’immagine.';
                    } else {
                        $imagePath = $uploadWebPath . '/' . $fileName;
                    }
                }
            }
        }

        if (!$errors) {
            if ($idChat > 0) {
                $stmt = $pdo->prepare("
                    UPDATE city_chats
                    SET id_district = :id_district,
                        name = :name,
                        slug = :slug,
                        description = :description,
                        image_path = :image_path,
                        is_private = :is_private,
                        owner_character_id = :owner_character_id,
                        allow_search = :allow_search,
                        allow_dice = :allow_dice,
                        allow_combat = :allow_combat,
                        allow_minigames = :allow_minigames,
                        min_action_chars = :min_action_chars,
                        max_action_chars = :max_action_chars,
                        is_active = :is_active,
                        sort_order = :sort_order
                    WHERE id_chat = :id_chat
                    LIMIT 1
                ");

                $stmt->execute([
                    'id_district' => $idDistrict,
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'image_path' => $imagePath !== '' ? $imagePath : null,
                    'is_private' => $isPrivate,
                    'owner_character_id' => $ownerCharacterId,
                    'allow_search' => $allowSearch,
                    'allow_dice' => $allowDice,
                    'allow_combat' => $allowCombat,
                    'allow_minigames' => $allowMinigames,
                    'min_action_chars' => $minActionChars,
                    'max_action_chars' => $maxActionChars,
                    'is_active' => $isActive,
                    'sort_order' => $sortOrder,
                    'id_chat' => $idChat,
                ]);

                $success = 'Chat aggiornata.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO city_chats
                    (
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
                    )
                    VALUES
                    (
                        :id_district,
                        :name,
                        :slug,
                        :description,
                        :image_path,
                        :is_private,
                        :owner_character_id,
                        :allow_search,
                        :allow_dice,
                        :allow_combat,
                        :allow_minigames,
                        :min_action_chars,
                        :max_action_chars,
                        :is_active,
                        :sort_order
                    )
                ");

                $stmt->execute([
                    'id_district' => $idDistrict,
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'image_path' => $imagePath !== '' ? $imagePath : null,
                    'is_private' => $isPrivate,
                    'owner_character_id' => $ownerCharacterId,
                    'allow_search' => $allowSearch,
                    'allow_dice' => $allowDice,
                    'allow_combat' => $allowCombat,
                    'allow_minigames' => $allowMinigames,
                    'min_action_chars' => $minActionChars,
                    'max_action_chars' => $maxActionChars,
                    'is_active' => $isActive,
                    'sort_order' => $sortOrder,
                ]);

                $success = 'Chat creata.';
            }
        }
    }

    if ($action === 'toggle') {
        $idChat = (int) ($_POST['id_chat'] ?? 0);

        if ($idChat > 0) {
            $stmt = $pdo->prepare("
                UPDATE city_chats
                SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END
                WHERE id_chat = :id_chat
                LIMIT 1
            ");

            $stmt->execute([
                'id_chat' => $idChat,
            ]);

            $success = 'Stato chat aggiornato.';
        }
    }

    if ($action === 'delete') {
        $idChat = (int) ($_POST['id_chat'] ?? 0);

        if ($idChat > 0) {
            $stmt = $pdo->prepare("
                DELETE FROM city_chats
                WHERE id_chat = :id_chat
                LIMIT 1
            ");

            $stmt->execute([
                'id_chat' => $idChat,
            ]);

            $success = 'Chat eliminata.';
        }
    }
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if ($editId > 0) {
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
        WHERE id_chat = :id_chat
        LIMIT 1
    ");

    $stmt->execute([
        'id_chat' => $editId,
    ]);

    $editingChat = $stmt->fetch() ?: null;
}

$stmt = $pdo->query("
    SELECT
        ch.id_chat,
        ch.id_district,
        ch.name,
        ch.slug,
        ch.description,
        ch.image_path,
        ch.is_private,
        ch.owner_character_id,
        ch.allow_search,
        ch.allow_dice,
        ch.allow_combat,
        ch.allow_minigames,
        ch.min_action_chars,
        ch.max_action_chars,
        ch.is_active,
        ch.sort_order,
        d.name AS district_name,
        c.name AS owner_name
    FROM city_chats ch
    JOIN city_districts d ON d.id_district = ch.id_district
    LEFT JOIN characters c ON c.id_character = ch.owner_character_id
    ORDER BY d.sort_order ASC, d.name ASC, ch.sort_order ASC, ch.name ASC
");

$chats = $stmt->fetchAll();

$formId = $editingChat ? (int) $editingChat['id_chat'] : 0;
$formDistrictId = $editingChat ? (int) $editingChat['id_district'] : 0;
$formName = $editingChat ? (string) $editingChat['name'] : '';
$formDescription = $editingChat ? (string) $editingChat['description'] : '';
$formImagePath = $editingChat ? (string) ($editingChat['image_path'] ?? '') : '';
$formIsPrivate = $editingChat ? (int) $editingChat['is_private'] : 0;
$formOwnerCharacterId = $editingChat ? (int) ($editingChat['owner_character_id'] ?? 0) : 0;
$formAllowSearch = $editingChat ? (int) $editingChat['allow_search'] : 0;
$formAllowDice = $editingChat ? (int) $editingChat['allow_dice'] : 1;
$formAllowCombat = $editingChat ? (int) $editingChat['allow_combat'] : 0;
$formAllowMinigames = $editingChat ? (int) $editingChat['allow_minigames'] : 0;
$formMinActionChars = $editingChat ? (int) $editingChat['min_action_chars'] : 200;
$formMaxActionChars = $editingChat ? (int) $editingChat['max_action_chars'] : 4000;
$formSortOrder = $editingChat ? (int) $editingChat['sort_order'] : 0;
$formIsActive = $editingChat ? (int) $editingChat['is_active'] : 1;

?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestione Chat City - Naraka</title>
<link rel="stylesheet" href="/themes/auth.css">
<link rel="stylesheet" href="/themes/city.css?v=admin-city-chats-01">
</head>
<body>

<div class="game-shell">

    <header class="game-topbar">

        <div class="game-brand">
            <span class="game-brand-main">NARAKA</span>
            <span class="game-brand-sub">Gestione Chat City</span>
        </div>

        <nav class="game-nav" aria-label="Menu principale">
            <a class="game-nav-link" href="/admin/city_districts.php">Quartieri</a>
            <a class="game-nav-link" href="/index.php">Torna alla land</a>
            <a class="game-nav-link game-nav-link-danger" href="/logout.php">Logout</a>
        </nav>

    </header>

    <main class="game-layout">

        <section class="game-main-area">

            <div class="game-map-panel">

                <div class="game-section-heading">
                    <h1>Chat City</h1>
                </div>

                <?php if ($errors) { ?>
                    <div class="game-message game-message-system">
                        <span class="game-message-author">Errore</span>
                        <?php foreach ($errors as $error) { ?>
                            <p><?php echo e($error); ?></p>
                        <?php } ?>
                    </div>
                <?php } ?>

                <?php if ($success !== '') { ?>
                    <div class="game-message game-message-system">
                        <span class="game-message-author">Sistema</span>
                        <p><?php echo e($success); ?></p>
                    </div>
                <?php } ?>

                <form class="game-chat-form" method="post" action="/admin/city_chats.php" enctype="multipart/form-data">

                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id_chat" value="<?php echo $formId; ?>">
                    <input type="hidden" name="current_image_path" value="<?php echo e($formImagePath); ?>">

                    <label class="game-chat-label" for="id_district">Quartiere</label>
                    <select id="id_district" name="id_district" class="game-chat-textarea" required>
                        <option value="">Seleziona quartiere</option>
                        <?php foreach ($districts as $district) { ?>
                            <option
                                value="<?php echo (int) $district['id_district']; ?>"
                                <?php echo (int) $district['id_district'] === $formDistrictId ? 'selected' : ''; ?>
                            >
                                <?php echo e((string) $district['name']); ?>
                            </option>
                        <?php } ?>
                    </select>

                    <label class="game-chat-label" for="name">Nome chat</label>
                    <input
                        id="name"
                        name="name"
                        class="game-chat-textarea"
                        type="text"
                        value="<?php echo e($formName); ?>"
                        required
                    >

                    <label class="game-chat-label" for="description">Descrizione BBCode</label>
                    <textarea
                        id="description"
                        name="description"
                        class="game-chat-textarea"
                        rows="6"
                    ><?php echo e($formDescription); ?></textarea>

                    <label class="game-chat-label" for="image">Immagine chat</label>
                    <input
                        id="image"
                        name="image"
                        class="game-chat-textarea"
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp,.gif"
                    >

                    <?php if ($formImagePath !== '') { ?>
                        <div class="game-message">
                            <span class="game-message-author">Immagine attuale</span>
                            <img src="<?php echo e($formImagePath); ?>" alt="" style="max-width:100%; height:auto;">
                        </div>
                    <?php } ?>

                    <label class="game-chat-label" for="sort_order">Ordine</label>
                    <input
                        id="sort_order"
                        name="sort_order"
                        class="game-chat-textarea"
                        type="number"
                        value="<?php echo $formSortOrder; ?>"
                    >

                    <label class="game-chat-label" for="min_action_chars">Minimo caratteri Azione</label>
                    <input
                        id="min_action_chars"
                        name="min_action_chars"
                        class="game-chat-textarea"
                        type="number"
                        min="0"
                        value="<?php echo $formMinActionChars; ?>"
                    >

                    <label class="game-chat-label" for="max_action_chars">Massimo caratteri Azione</label>
                    <input
                        id="max_action_chars"
                        name="max_action_chars"
                        class="game-chat-textarea"
                        type="number"
                        min="1"
                        value="<?php echo $formMaxActionChars; ?>"
                    >

                    <label class="game-chat-label">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            <?php echo $formIsActive === 1 ? 'checked' : ''; ?>
                        >
                        Attiva
                    </label>

                    <label class="game-chat-label">
                        <input
                            type="checkbox"
                            name="is_private"
                            value="1"
                            <?php echo $formIsPrivate === 1 ? 'checked' : ''; ?>
                        >
                        Privata
                    </label>

                    <label class="game-chat-label" for="owner_character_id">Proprietario chat privata</label>
                    <select id="owner_character_id" name="owner_character_id" class="game-chat-textarea">
                        <option value="">Nessun proprietario</option>
                        <?php foreach ($characters as $character) { ?>
                            <option
                                value="<?php echo (int) $character['id_character']; ?>"
                                <?php echo (int) $character['id_character'] === $formOwnerCharacterId ? 'selected' : ''; ?>
                            >
                                <?php echo e((string) $character['name']); ?>
                            </option>
                        <?php } ?>
                    </select>

                    <label class="game-chat-label">
                        <input
                            type="checkbox"
                            name="allow_search"
                            value="1"
                            <?php echo $formAllowSearch === 1 ? 'checked' : ''; ?>
                        >
                        Ricerca
                    </label>

                    <label class="game-chat-label">
                        <input
                            type="checkbox"
                            name="allow_dice"
                            value="1"
                            <?php echo $formAllowDice === 1 ? 'checked' : ''; ?>
                        >
                        Dadi
                    </label>

                    <label class="game-chat-label">
                        <input
                            type="checkbox"
                            name="allow_combat"
                            value="1"
                            <?php echo $formAllowCombat === 1 ? 'checked' : ''; ?>
                        >
                        Combattimento
                    </label>

                    <label class="game-chat-label">
                        <input
                            type="checkbox"
                            name="allow_minigames"
                            value="1"
                            <?php echo $formAllowMinigames === 1 ? 'checked' : ''; ?>
                        >
                        Minigiochi
                    </label>

                    <button class="game-chat-submit" type="submit">
                        <?php echo $editingChat ? 'Salva modifiche' : 'Crea chat'; ?>
                    </button>

                </form>

                <?php if ($editingChat) { ?>
                    <div class="auth-links">
                        <a href="/admin/city_chats.php">Annulla modifica</a>
                    </div>
                <?php } ?>

                <div class="game-chat-box" aria-label="Elenco chat">

                    <?php if (!$chats) { ?>
                        <div class="game-message">
                            <span class="game-message-author">City</span>
                            <p>Nessuna chat creata.</p>
                        </div>
                    <?php } ?>

                    <?php foreach ($chats as $chat) { ?>
                        <?php
                            $modules = [];

                            if ((int) $chat['allow_search'] === 1) {
                                $modules[] = 'Ricerca';
                            }

                            if ((int) $chat['allow_dice'] === 1) {
                                $modules[] = 'Dadi';
                            }

                            if ((int) $chat['allow_combat'] === 1) {
                                $modules[] = 'Combattimento';
                            }

                            if ((int) $chat['allow_minigames'] === 1) {
                                $modules[] = 'Minigiochi';
                            }

                            $moduleLabel = $modules ? implode(', ', $modules) : 'Nessuno';
                        ?>

                        <div class="game-message">
                            <span class="game-message-author"><?php echo e((string) $chat['name']); ?></span>

                            <p>Quartiere: <?php echo e((string) $chat['district_name']); ?></p>
                            <p>Stato: <?php echo (int) $chat['is_active'] === 1 ? 'Attiva' : 'Nascosta'; ?></p>
                            <p>Privacy: <?php echo (int) $chat['is_private'] === 1 ? 'Privata' : 'Pubblica'; ?></p>

                            <?php if ((int) $chat['is_private'] === 1 && !empty($chat['owner_name'])) { ?>
                                <p>Owner: <?php echo e((string) $chat['owner_name']); ?></p>
                            <?php } ?>

                            <p>Moduli: <?php echo e($moduleLabel); ?></p>
                            <p>Azione: min <?php echo (int) $chat['min_action_chars']; ?> / max <?php echo (int) $chat['max_action_chars']; ?></p>

                            <div class="game-nav">
                                <a
                                    class="game-nav-link"
                                    href="/admin/city_chats.php?edit=<?php echo (int) $chat['id_chat']; ?>"
                                >
                                    Modifica
                                </a>

                                <?php if ((int) $chat['allow_search'] === 1) { ?>
                                    <a
                                        class="game-nav-link"
                                        href="/admin/city_chat_drops.php?id_chat=<?php echo (int) $chat['id_chat']; ?>"
                                    >
                                        Gestisci drop
                                    </a>
                                <?php } ?>

                                <form method="post" action="/admin/city_chats.php">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id_chat" value="<?php echo (int) $chat['id_chat']; ?>">
                                    <button class="game-nav-link" type="submit">
                                        <?php echo (int) $chat['is_active'] === 1 ? 'Nascondi' : 'Attiva'; ?>
                                    </button>
                                </form>

                                <form method="post" action="/admin/city_chats.php">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id_chat" value="<?php echo (int) $chat['id_chat']; ?>">
                                    <button class="game-nav-link game-nav-link-danger" type="submit">
                                        Elimina
                                    </button>
                                </form>
                            </div>
                        </div>

                    <?php } ?>

                </div>

            </div>

        </section>

    </main>

</div>

</body>
</html>

<?php
// by LaEmiX
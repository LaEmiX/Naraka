<?php

declare(strict_types=1);

session_start();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if (!isset($_SESSION['user'])) {
    header('Location: /app.php');
    exit;
}

require __DIR__ . '/config/permissions.php';

$pdo = require __DIR__ . '/config/database.php';

$user = $_SESSION['user'];

$currentLand = (string) ($_SESSION['current_land'] ?? 'city');

$stmt = $pdo->prepare("
    SELECT id_land, slug, name
    FROM lands
    WHERE slug = :slug
    LIMIT 1
");

$stmt->execute([
    'slug' => $currentLand,
]);

$land = $stmt->fetch();

if (!$land) {
    $_SESSION['current_land'] = 'city';
    header('Location: /index.php');
    exit;
}

$idLand = (int) $land['id_land'];
$landSlug = (string) $land['slug'];
$landCssFile = $landSlug === 'echoes' ? 'echoes.css' : 'city.css';

/**
 * PERSONAGGIO
 */
$stmt = $pdo->prepare("
    SELECT id_character, name
    FROM characters
    WHERE id_user = :id_user
    AND id_land = :id_land
    LIMIT 1
");

$stmt->execute([
    'id_user' => (int) $user['id_user'],
    'id_land' => $idLand,
]);

$character = $stmt->fetch();

if (!$character) {
    $_SESSION['current_land'] = 'city';
    header('Location: /index.php');
    exit;
}

/**
 * SWITCH LAND
 */
if ($landSlug === 'city') {
    $switchLandSlug = 'echoes';
    $switchLandLabel = 'Vai a Echoes';
} else {
    $switchLandSlug = 'city';
    $switchLandLabel = 'Torna a City';
}

/**
 * DISTRICT
 */
$requestedDistrict = isset($_GET['district']) ? (string) $_GET['district'] : '';

$district = null;

if ($requestedDistrict !== '' && $landSlug === 'city') {
    $stmt = $pdo->prepare("
        SELECT id_district, name, slug
        FROM city_districts
        WHERE slug = :slug
        AND is_active = 1
        LIMIT 1
    ");

    $stmt->execute([
        'slug' => $requestedDistrict,
    ]);

    $district = $stmt->fetch();
}

/**
 * CHAT / PAGE
 */
$requestedChat = isset($_GET['chat']) ? (string) $_GET['chat'] : '';
$requestedPage = isset($_GET['page']) ? (string) $_GET['page'] : '';

$chat = null;

if ($requestedChat !== '' && $district && $landSlug === 'city') {
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
        WHERE slug = :slug
        AND id_district = :id_district
        AND is_active = 1
        LIMIT 1
    ");

    $stmt->execute([
        'slug' => $requestedChat,
        'id_district' => (int) $district['id_district'],
    ]);

    $chat = $stmt->fetch() ?: null;
}

/**
 * TITLE DINAMICO
 */
$pageTitle = 'REDWOOD GROVE';

if ($requestedPage === 'my_roles') {
    $pageTitle = 'Le mie role';
} elseif ($requestedPage === 'admin_roles') {
    $pageTitle = 'Gestione role';
} elseif ($district) {
    $pageTitle = (string) $district['name'];
}

if ($chat) {
    $pageTitle = (string) $chat['name'];
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Naraka</title>
<link rel="stylesheet" href="/themes/auth.css">
<link rel="stylesheet" href="/themes/<?php echo e($landCssFile); ?>?v=land-02">
<link rel="stylesheet" href="/themes/topbar_menu.css?v=01">
<link rel="stylesheet" href="/themes/meteo.css?v=01">
<?php if ($landSlug === 'city') { ?>
<link rel="stylesheet" href="/themes/city_districts.css?v=city-districts-01">
<?php } ?>
</head>
<body>

<div class="game-shell">

    <header class="game-topbar">

        <div class="game-brand">
            <span class="game-brand-main">SYNAPSE SYSTEMS</span>
            <span class="game-brand-sub"><?php echo e((string) $land['name']); ?></span>
        </div>

        <?php require __DIR__ . '/includes/topbar_menu.php'; ?>

        <nav class="game-nav" aria-label="Menu account">
            <a class="game-nav-link" href="/switch_land.php?land=<?php echo e($switchLandSlug); ?>">
                <?php echo e($switchLandLabel); ?>
            </a>
            <a class="game-nav-link game-nav-link-danger" href="/logout.php">Logout</a>
        </nav>

    </header>

    <main class="game-layout">

        <section class="game-main-area">

            <?php if ($requestedPage === 'my_roles') { ?>

                <div class="game-map-panel">

                    <div class="game-section-heading">
                        <h1>Le mie role</h1>
                    </div>

                    <?php require __DIR__ . '/includes/my_roles_view.php'; ?>

                </div>

            <?php } elseif ($requestedPage === 'admin_roles') { ?>

                <div class="game-map-panel">

                    <div class="game-section-heading">
                        <h1>Gestione role</h1>
                    </div>

                    <?php require __DIR__ . '/includes/admin_roles_view.php'; ?>

                </div>

            <?php } elseif ($landSlug === 'city') { ?>

                <div class="game-map-panel">

                    <div class="game-section-heading">
                        <h1><?php echo e($pageTitle); ?></h1>
                    </div>

                    <?php if (!$district) { ?>

                        <?php require __DIR__ . '/includes/city_districts_view.php'; ?>

                    <?php } elseif (!$chat) { ?>

                        <?php require __DIR__ . '/includes/city_chats_view.php'; ?>

                    <?php } else { ?>

                        <?php require __DIR__ . '/includes/city_chat_page.php'; ?>

                    <?php } ?>

                </div>

            <?php } else { ?>

                <div class="game-map-panel">

                    <div class="game-section-heading">
                        <h1>Echoes</h1>
                    </div>

                    <div class="game-empty">
                        Sistema Echoes in preparazione.
                    </div>

                </div>

            <?php } ?>

        </section>

        <aside class="game-sidebar">

            <section class="game-side-card">
                <h2>Personaggio</h2>

                <div class="game-side-row">
                    <span>Nome</span>
                    <strong><?php echo e((string) $character['name']); ?></strong>
                </div>

                <div class="game-side-row">
                    <span>Land</span>
                    <strong><?php echo e((string) $land['name']); ?></strong>
                </div>
            </section>

            <section class="game-side-card">
                <h2>Presenti</h2>

                <div class="game-present-placeholder">
                    Nessun sistema presenti collegato.
                </div>
            </section>

        </aside>

    </main>

</div>

</body>
</html>

<?php
// by LaEmiX
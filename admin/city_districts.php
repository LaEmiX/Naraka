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

    return $slug !== '' ? $slug : 'quartiere';
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
$editingDistrict = null;

$uploadDir = __DIR__ . '/../themes/images/city_districts';
$uploadWebPath = '/themes/images/city_districts';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$stmt = $pdo->query("SHOW COLUMNS FROM city_districts LIKE 'image_path'");
$hasImageColumn = (bool) $stmt->fetch();

if (!$hasImageColumn) {
    $pdo->exec("
        ALTER TABLE city_districts
        ADD COLUMN image_path VARCHAR(255) NULL AFTER description
    ");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save') {
        $idDistrict = (int) ($_POST['id_district'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $imagePath = trim((string) ($_POST['current_image_path'] ?? ''));

        if ($name === '') {
            $errors[] = 'Il nome del quartiere è obbligatorio.';
        }

        $slug = make_slug($name);

        if (!$errors) {
            $stmt = $pdo->prepare("
                SELECT id_district
                FROM city_districts
                WHERE slug = :slug
                AND id_district <> :id_district
                LIMIT 1
            ");

            $stmt->execute([
                'slug' => $slug,
                'id_district' => $idDistrict,
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
                    $fileName = 'district_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
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
            if ($idDistrict > 0) {
                $stmt = $pdo->prepare("
                    UPDATE city_districts
                    SET name = :name,
                        slug = :slug,
                        description = :description,
                        image_path = :image_path,
                        is_active = :is_active,
                        sort_order = :sort_order
                    WHERE id_district = :id_district
                    LIMIT 1
                ");

                $stmt->execute([
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'image_path' => $imagePath !== '' ? $imagePath : null,
                    'is_active' => $isActive,
                    'sort_order' => $sortOrder,
                    'id_district' => $idDistrict,
                ]);

                $success = 'Quartiere aggiornato.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO city_districts
                    (name, slug, description, image_path, is_active, sort_order)
                    VALUES
                    (:name, :slug, :description, :image_path, :is_active, :sort_order)
                ");

                $stmt->execute([
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'image_path' => $imagePath !== '' ? $imagePath : null,
                    'is_active' => $isActive,
                    'sort_order' => $sortOrder,
                ]);

                $success = 'Quartiere creato.';
            }
        }
    }

    if ($action === 'toggle') {
        $idDistrict = (int) ($_POST['id_district'] ?? 0);

        if ($idDistrict > 0) {
            $stmt = $pdo->prepare("
                UPDATE city_districts
                SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END
                WHERE id_district = :id_district
                LIMIT 1
            ");

            $stmt->execute([
                'id_district' => $idDistrict,
            ]);

            $success = 'Stato quartiere aggiornato.';
        }
    }

    if ($action === 'delete') {
        $idDistrict = (int) ($_POST['id_district'] ?? 0);

        if ($idDistrict > 0) {
            $stmt = $pdo->prepare("
                DELETE FROM city_districts
                WHERE id_district = :id_district
                LIMIT 1
            ");

            $stmt->execute([
                'id_district' => $idDistrict,
            ]);

            $success = 'Quartiere eliminato.';
        }
    }
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

if ($editId > 0) {
    $stmt = $pdo->prepare("
        SELECT id_district, name, slug, description, image_path, is_active, sort_order
        FROM city_districts
        WHERE id_district = :id_district
        LIMIT 1
    ");

    $stmt->execute([
        'id_district' => $editId,
    ]);

    $editingDistrict = $stmt->fetch() ?: null;
}

$stmt = $pdo->query("
    SELECT id_district, name, slug, description, image_path, is_active, sort_order
    FROM city_districts
    ORDER BY sort_order ASC, name ASC
");

$districts = $stmt->fetchAll();

$formId = $editingDistrict ? (int) $editingDistrict['id_district'] : 0;
$formName = $editingDistrict ? (string) $editingDistrict['name'] : '';
$formDescription = $editingDistrict ? (string) $editingDistrict['description'] : '';
$formImagePath = $editingDistrict ? (string) ($editingDistrict['image_path'] ?? '') : '';
$formSortOrder = $editingDistrict ? (int) $editingDistrict['sort_order'] : 0;
$formIsActive = $editingDistrict ? (int) $editingDistrict['is_active'] : 1;

?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestione Quartieri - Naraka</title>
<link rel="stylesheet" href="/themes/auth.css">
<link rel="stylesheet" href="/themes/city.css?v=admin-city-districts-04">
</head>
<body>

<div class="game-shell">

    <header class="game-topbar">

        <div class="game-brand">
            <span class="game-brand-main">NARAKA</span>
            <span class="game-brand-sub">Gestione City</span>
        </div>

        <nav class="game-nav" aria-label="Menu principale">
            <a class="game-nav-link" href="/index.php">Torna alla land</a>
            <a class="game-nav-link game-nav-link-danger" href="/logout.php">Logout</a>
        </nav>

    </header>

    <main class="game-layout">

        <section class="game-main-area">

            <div class="game-map-panel">

                <div class="game-section-heading">
                    <h1>Quartieri</h1>
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

                <form class="game-chat-form" method="post" action="/admin/city_districts.php" enctype="multipart/form-data">

                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id_district" value="<?php echo $formId; ?>">
                    <input type="hidden" name="current_image_path" value="<?php echo e($formImagePath); ?>">

                    <label class="game-chat-label" for="name">Nome quartiere</label>
                    <input id="name" name="name" class="game-chat-textarea" type="text" value="<?php echo e($formName); ?>" required>

                    <label class="game-chat-label" for="description">Descrizione BBCode</label>
                    <textarea id="description" name="description" class="game-chat-textarea" rows="6"><?php echo e($formDescription); ?></textarea>

                    <label class="game-chat-label" for="image">Immagine quartiere</label>
                    <input id="image" name="image" class="game-chat-textarea" type="file">

                    <?php if ($formImagePath !== '') { ?>
                        <div class="game-message">
                            <span class="game-message-author">Immagine attuale</span>
                            <img src="<?php echo e($formImagePath); ?>" style="max-width:100%;">
                        </div>
                    <?php } ?>

                    <label class="game-chat-label" for="sort_order">Ordine</label>
                    <input id="sort_order" name="sort_order" class="game-chat-textarea" type="number" value="<?php echo $formSortOrder; ?>">

                    <label class="game-chat-label">
                        <input type="checkbox" name="is_active" value="1" <?php echo $formIsActive === 1 ? 'checked' : ''; ?>>
                        Attivo
                    </label>

                    <button class="game-chat-submit" type="submit">
                        <?php echo $editingDistrict ? 'Salva modifiche' : 'Crea quartiere'; ?>
                    </button>

                </form>

                <?php if ($editingDistrict) { ?>
                    <div class="auth-links">
                        <a href="/admin/city_districts.php">Annulla modifica</a>
                    </div>
                <?php } ?>

                <div class="game-chat-box">

                    <?php if (!$districts) { ?>
                        <div class="game-message">
                            <span class="game-message-author">City</span>
                            <p>Nessun quartiere creato.</p>
                        </div>
                    <?php } ?>

                    <?php foreach ($districts as $district) { ?>
                        <div class="game-message">
                            <span class="game-message-author"><?php echo e((string) $district['name']); ?></span>

                            <div class="game-nav">
                                <a
                                    class="game-nav-link"
                                    href="/admin/city_districts.php?edit=<?php echo (int) $district['id_district']; ?>"
                                >
                                    Modifica
                                </a>

                                <form method="post" action="/admin/city_districts.php">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id_district" value="<?php echo (int) $district['id_district']; ?>">
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

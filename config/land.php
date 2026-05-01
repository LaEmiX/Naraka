<?php

declare(strict_types=1);

if (!isset($_SESSION['current_land'])) {
    $_SESSION['current_land'] = 'city';
}

$landSlug = $_SESSION['current_land'];

$stmt = $pdo->prepare("
    SELECT id_land, name, slug, theme_folder, is_active
    FROM lands
    WHERE slug = :slug
    AND is_active = 1
    LIMIT 1
");

$stmt->execute([
    'slug' => $landSlug
]);

$currentLand = $stmt->fetch();

if (!$currentLand) {
    $_SESSION['current_land'] = 'city';

    $stmt = $pdo->prepare("
        SELECT id_land, name, slug, theme_folder, is_active
        FROM lands
        WHERE slug = 'city'
        AND is_active = 1
        LIMIT 1
    ");

    $stmt->execute();
    $currentLand = $stmt->fetch();
}

if (!$currentLand) {
    die('Errore: nessuna land attiva trovata.');
}

return $currentLand;

// by LaEmiX
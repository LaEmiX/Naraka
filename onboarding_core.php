<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/config/auth.php';
$pdo = require __DIR__ . '/config/database.php';

$user = $_SESSION['user'] ?? null;

if (!$user) {
    header('Location: /app.php');
    exit;
}

if (!isset($_SESSION['onboarding']) || !is_array($_SESSION['onboarding'])) {
    $_SESSION['onboarding'] = [];
}

function naraka_validate_name(string $name): string
{
    if ($name === '') {
        return 'Nome obbligatorio';
    }

    if (strlen($name) < 3) {
        return 'Minimo 3 caratteri';
    }

    return '';
}

function naraka_get_land(PDO $pdo, string $slug): array
{
    $stmt = $pdo->prepare("SELECT id_land, slug FROM lands WHERE slug = :slug LIMIT 1");
    $stmt->execute(['slug' => $slug]);
    $land = $stmt->fetch();

    if (!$land) {
        die('Land non trovata');
    }

    return $land;
}

function naraka_character_name_exists(PDO $pdo, string $name, int $idLand): bool
{
    $stmt = $pdo->prepare("
        SELECT id_character
        FROM characters
        WHERE LOWER(name) = LOWER(:name)
        AND id_land = :land
        LIMIT 1
    ");

    $stmt->execute([
        'name' => $name,
        'land' => $idLand,
    ]);

    return (bool) $stmt->fetch();
}
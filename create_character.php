<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/config/auth.php';

$pdo = require __DIR__ . '/config/database.php';
$currentLand = require __DIR__ . '/config/land.php';

$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /characters.php');
    exit;
}

$name = trim((string) ($_POST['name'] ?? ''));

if ($name === '' || strlen($name) < 3) {
    die('Nome non valido.');
}

$slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));

try {
    $stmt = $pdo->prepare("
        INSERT INTO characters (id_user, id_land, name, slug)
        VALUES (:id_user, :id_land, :name, :slug)
    ");

    $stmt->execute([
        'id_user' => $user['id_user'],
        'id_land' => $currentLand['id_land'],
        'name' => $name,
        'slug' => $slug,
    ]);

    $idCharacter = (int) $pdo->lastInsertId();

    $_SESSION['character'] = [
        'id_character' => $idCharacter,
        'name' => $name,
    ];

    header('Location: /game.php');
    exit;

} catch (PDOException $e) {
    die('Errore creazione personaggio.');
}
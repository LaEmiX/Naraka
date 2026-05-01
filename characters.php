<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/config/auth.php';

$pdo = require __DIR__ . '/config/database.php';
$currentLand = require __DIR__ . '/config/land.php';

$user = $_SESSION['user'];

$stmt = $pdo->prepare("
    SELECT *
    FROM characters
    WHERE id_user = :id_user
    AND id_land = :id_land
    LIMIT 1
");

$stmt->execute([
    'id_user' => $user['id_user'],
    'id_land' => $currentLand['id_land'],
]);

$character = $stmt->fetch();

if ($character) {
    $_SESSION['character'] = [
        'id_character' => (int) $character['id_character'],
        'name' => (string) $character['name'],
    ];

    header('Location: /game.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea Personaggio - Naraka</title>
</head>
<body>

<h1>Crea il tuo personaggio</h1>

<form method="post" action="/create_character.php">
    <p>
        <label for="name">Nome personaggio</label><br>
        <input type="text" id="name" name="name" required>
    </p>

    <button type="submit">Crea</button>
</form>

<p><a href="/index.php">Torna alla home</a></p>

</body>
</html>
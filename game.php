<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/config/auth.php';

if (!isset($_SESSION['character'])) {
    header('Location: /characters.php');
    exit;
}

$character = $_SESSION['character'];

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gioco - Naraka</title>
</head>
<body>

<h1>Sei dentro Naraka</h1>

<p>Personaggio: <?php echo htmlspecialchars($character['name'], ENT_QUOTES, 'UTF-8'); ?></p>

<a href="/index.php">Home</a>

</body>
</html>
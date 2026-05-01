<?php

declare(strict_types=1);

session_start();

$allowedLands = ['city', 'echoes'];

$requestedLand = $_GET['land'] ?? '';

if (!in_array($requestedLand, $allowedLands, true)) {
    header('Location: /index.php');
    exit;
}

$_SESSION['current_land'] = $requestedLand;

header('Location: /index.php');
exit;

// by LaEmiX
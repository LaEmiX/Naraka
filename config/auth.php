<?php

declare(strict_types=1);

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}

// by LaEmiX
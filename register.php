<?php

declare(strict_types=1);

session_start();

$pdo = require __DIR__ . '/config/database.php';

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($username === '') {
        $errors[] = 'Il nome utente è obbligatorio.';
    }

    if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $errors[] = 'Il nome utente deve contenere solo lettere, numeri e underscore (3–50 caratteri).';
    }

    if ($email === '') {
        $errors[] = 'L’email è obbligatoria.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L’email non è valida.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'La password deve contenere almeno 8 caratteri.';
    }

    if ($password !== $passwordConfirm) {
        $errors[] = 'Le password non coincidono.';
    }

    if (!$errors) {

        $stmt = $pdo->prepare("
            SELECT id_user
            FROM users
            WHERE username = :username
            OR email = :email
            LIMIT 1
        ");

        $stmt->execute([
            'username' => $username,
            'email' => $email,
        ]);

        if ($stmt->fetch()) {
            $errors[] = 'Nome utente o email già registrati.';
        }
    }

    if (!$errors) {

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash)
            VALUES (:username, :email, :password_hash)
        ");

        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        $idUser = (int) $pdo->lastInsertId();

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id_user' => $idUser,
            'username' => $username,
            'email' => $email,
            'role' => 'user',
        ];

        $_SESSION['current_land'] = 'city';
        $_SESSION['onboarding'] = [];

        header('Location: /onboarding_city.php');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrazione - Naraka</title>
<link rel="stylesheet" href="/themes/auth.css">
</head>
<body>

<div class="auth-page">

    <!-- LOGO -->
    <div class="auth-logo-wrap">
        <img src="/themes/images/titolo.png" alt="Naraka" class="auth-logo">
    </div>

    <div class="auth-panel auth-login-panel">

        <!-- TESTO -->
        <p class="auth-subtitle auth-subtitle-loading">
            Creazione account<span class="auth-loading-dots"></span>
        </p>

        <?php if ($errors) { ?>
            <div class="auth-errors">
                <ul>
                    <?php foreach ($errors as $error) { ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>

        <form method="post" class="auth-login-form">

            <div class="auth-field">
                <label>Nome utente</label>
                <input type="text" name="username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="auth-field">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="auth-field">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <div class="auth-field">
                <label>Conferma password</label>
                <input type="password" name="password_confirm" required>
            </div>

            <button class="auth-button">Registrati</button>

        </form>

        <div class="auth-links">
            <a href="/login.php">Hai già un account? Accedi</a>
        </div>

    </div>

</div>

</body>
</html>

<?php
// by LaEmiX
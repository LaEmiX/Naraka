<?php

declare(strict_types=1);

session_start();

$pdo = require __DIR__ . '/config/database.php';

$errors = [];
$login = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $login = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($login === '') {
            $errors[] = 'Inserisci username o email.';
        }

        if ($password === '') {
            $errors[] = 'Inserisci la password.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare("
                SELECT id_user, username, email, password_hash, role, is_active
                FROM users
                WHERE username = :login_username
                OR email = :login_email
                LIMIT 1
            ");

            $stmt->execute([
                'login_username' => $login,
                'login_email' => $login,
            ]);

            $user = $stmt->fetch();

            if (!$user) {
                $errors[] = 'Credenziali non valide.';
            } elseif (!password_verify($password, (string) $user['password_hash'])) {
                $errors[] = 'Credenziali non valide.';
            } elseif ((int) $user['is_active'] !== 1) {
                $errors[] = 'Account disattivato.';
            } else {
                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id_user' => (int) $user['id_user'],
                    'username' => (string) $user['username'],
                    'email' => (string) $user['email'],
                    'role' => (string) $user['role'],
                ];

                $_SESSION['current_land'] = 'city';

                $stmt = $pdo->prepare("
                    SELECT l.slug
                    FROM characters c
                    JOIN lands l ON l.id_land = c.id_land
                    WHERE c.id_user = :id_user
                ");

                $stmt->execute([
                    'id_user' => (int) $user['id_user'],
                ]);

                $lands = $stmt->fetchAll(PDO::FETCH_COLUMN);

                $hasCity = in_array('city', $lands, true);
                $hasEcho = in_array('echoes', $lands, true);

                if ($hasCity && $hasEcho) {
                    header('Location: /index.php');
                    exit;
                }

                $_SESSION['onboarding'] = [];

                if (!$hasCity) {
                    header('Location: /onboarding_city.php');
                    exit;
                }

                header('Location: /onboarding_echoes.php');
                exit;
            }
        }
    }
} catch (Throwable $e) {
    $errors[] = 'Errore interno durante il login.';
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Naraka</title>
    <link rel="stylesheet" href="/themes/auth.css">
</head>
<body>

<div class="auth-page">

    <div class="auth-logo-wrap">
        <img src="/themes/images/titolo.png" alt="Naraka" class="auth-logo">
    </div>

    <div class="auth-panel auth-login-panel">

        <p class="auth-subtitle auth-subtitle-loading">
            Accesso al sistema<span class="auth-loading-dots"></span>
        </p>

        <?php if ($errors) { ?>
            <div class="auth-errors">
                <ul>
                    <?php foreach ($errors as $error) { ?>
                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>

        <form method="post" action="/login.php" class="auth-login-form" target="_self">
            <div class="auth-field">
                <label for="login">Username o email</label>
                <input type="text" id="login" name="login" value="<?php echo htmlspecialchars($login, ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="auth-field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="auth-button">Accedi</button>
        </form>

        <div class="auth-links">
            <a href="/register.php" target="_self">Non hai un account? Registrati</a>
        </div>

    </div>

</div>

</body>
</html>

<?php
// by LaEmiX
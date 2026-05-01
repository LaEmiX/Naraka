<?php

declare(strict_types=1);

require __DIR__ . '/onboarding_core.php';

/* ================= GUARDIA ================= */

if (!isset($_SESSION['onboarding']['city'])) {
    header('Location: /onboarding_city.php');
    exit;
}

$fieldError = '';
$echoValue = $_SESSION['onboarding']['echoes'] ?? '';
$cityName = $_SESSION['onboarding']['city'];

$echoLand = naraka_get_land($pdo, 'echoes');
$idEcho = (int)$echoLand['id_land'];

/* ================= POST ================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $fieldError = naraka_validate_name($name);

    if (!$fieldError && naraka_character_name_exists($pdo, $name, $idEcho)) {
        $fieldError = 'Nome già usato';
    }

    if (!$fieldError) {
        $_SESSION['onboarding']['echoes'] = $name;

        /* ================= REDIRECT CORRETTO ================= */
        header('Location: /onboarding_stats_city.php');
        exit;
    }

    $echoValue = $name;
}

/* ================= TESTO ================= */

$text = "Ehi {$cityName}, è un piacere conoscerti! Dimmi, come si chiamerà il tuo eroe nel mondo di Naraka?";

?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Naraka</title>
<link rel="stylesheet" href="/themes/auth.css">
</head>
<body>

<div class="auth-page">

    <div class="lene-wrap">
        <img src="/themes/images/lene2.png" class="lene-image">
    </div>

    <div class="auth-panel">

        <p class="lene-text" id="leneText"></p>

        <?php if ($fieldError): ?>
            <div class="auth-errors">
                <ul>
                    <li><?= htmlspecialchars($fieldError) ?></li>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="auth-field">
                <label>Inserisci il nome del tuo personaggio nell'Echo System</label>
                <input type="text" name="name" value="<?= htmlspecialchars($echoValue) ?>" required>
            </div>

            <button class="auth-button">Continua</button>
        </form>

        <div class="auth-links">
            <a href="/onboarding_city.php">Cambia nome PG</a>
        </div>

    </div>

</div>

<script>
const text = <?= json_encode($text) ?>;
let i = 0;
const target = document.getElementById("leneText");

function type() {
    target.textContent = text.substring(0, i);
    i++;
    if (i <= text.length) {
        setTimeout(type, 50);
    } else {
        setTimeout(() => {
            i = 0;
            type();
        }, 1500);
    }
}
type();
</script>

</body>
</html>

<?php
// by LaEmiX
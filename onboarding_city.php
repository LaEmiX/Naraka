<?php

require __DIR__ . '/onboarding_core.php';

$fieldError = '';
$cityValue = $_SESSION['onboarding']['city'] ?? '';

$cityLand = naraka_get_land($pdo, 'city');
$idCity = (int)$cityLand['id_land'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $fieldError = naraka_validate_name($name);

    if (!$fieldError && naraka_character_name_exists($pdo, $name, $idCity)) {
        $fieldError = 'Nome già usato';
    }

    if (!$fieldError) {
        $_SESSION['onboarding']['city'] = $name;
        header('Location: /onboarding_echoes.php');
        exit;
    }

    $cityValue = $name;
}

$text = "Ciao! Benvenuto in Naraka. Io sono Lene, tu come ti chiami?";
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
        <img src="/themes/images/lene1.png" class="lene-image">
    </div>

    <div class="auth-panel">

        <p class="lene-text" id="leneText"></p>

        <form method="post">
            <div class="auth-field">
                <label>Inserisci il nome del tuo personaggio</label>
                <input type="text" name="name" value="<?= htmlspecialchars($cityValue) ?>" required>

                <?php if ($fieldError): ?>
                    <p class="auth-field-error"><?= $fieldError ?></p>
                <?php endif; ?>
            </div>

            <button class="auth-button">Continua</button>
        </form>

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
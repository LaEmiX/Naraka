<?php

declare(strict_types=1);

require __DIR__ . '/onboarding_core.php';

if (!isset($_SESSION['onboarding']['city'])) {
    header('Location: /onboarding_city.php');
    exit;
}

$fieldError = '';

$stats = $_SESSION['onboarding']['stats_city'] ?? [
    'influenza' => 1,
    'intelletto' => 1,
    'percezione' => 1,
    'prontezza' => 1,
    'vigore' => 1,
    'volonta' => 1,
];

$totalPoints = array_sum($stats);
$remaining = 18 - $totalPoints;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $newStats = [];

    foreach ($stats as $key => $value) {
        $val = (int)($_POST[$key] ?? 1);
        $val = max(1, min(5, $val));
        $newStats[$key] = $val;
    }

    $sum = array_sum($newStats);
    $maxCount = count(array_filter($newStats, fn($v) => $v === 5));

    if ($sum > 18) {
        $fieldError = 'Hai superato i punti disponibili.';
    } elseif ($sum < 18) {
        $fieldError = 'Devi assegnare tutti i punti.';
    } elseif ($maxCount > 1) {
        $fieldError = 'Puoi avere solo una statistica a 5.';
    } else {
        $_SESSION['onboarding']['stats_city'] = $newStats;
        header('Location: /onboarding_stats_echoes.php');
        exit;
    }

    $stats = $newStats;
    $remaining = 18 - $sum;
}

$cityName = $_SESSION['onboarding']['city'];
$text = "{$cityName}, raccontami qualcosa di te! Quali sono i tuoi punti di forza e le tue debolezze?";

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
        <img src="/themes/images/lene3.png" class="lene-image">
    </div>

    <div class="auth-panel">

        <p class="lene-text" id="leneText"></p>

        <?php if ($fieldError): ?>
            <div class="auth-errors">
                <ul><li><?= htmlspecialchars($fieldError) ?></li></ul>
            </div>
        <?php endif; ?>

        <form method="post" class="auth-login-form">

            <div class="auth-stats-counter">
                Punti rimanenti: <strong id="remaining"><?= $remaining ?></strong>
            </div>

            <?php foreach ($stats as $key => $value): ?>
                <div class="auth-stat-row">
                    <span><?= ucfirst($key) ?></span>

                    <div class="auth-stat-controls">
                        <button type="button" class="auth-stat-btn" onclick="changeStat('<?= $key ?>', -1)">-</button>
                        <span id="<?= $key ?>_val" class="auth-stat-value"><?= $value ?></span>
                        <button type="button" class="auth-stat-btn" onclick="changeStat('<?= $key ?>', 1)">+</button>
                    </div>

                    <input type="hidden" name="<?= $key ?>" id="<?= $key ?>" value="<?= $value ?>">
                </div>
            <?php endforeach; ?>

            <button class="auth-button">Continua</button>

        </form>

        <div class="auth-links">
            <a href="/onboarding_city.php">Cambia nome PG</a>
            <a href="/onboarding_echoes.php">Cambia nome PG Echo System</a>
        </div>

    </div>

</div>

<script>
const maxPoints = 18;

function getStats() {
    const stats = {};
    document.querySelectorAll('input[type="hidden"]').forEach(el => {
        stats[el.id] = parseInt(el.value);
    });
    return stats;
}

function updateRemaining() {
    const stats = getStats();
    const total = Object.values(stats).reduce((a,b)=>a+b,0);
    document.getElementById('remaining').textContent = maxPoints - total;
}

function changeStat(stat, delta) {
    const input = document.getElementById(stat);
    const display = document.getElementById(stat + '_val');

    let value = parseInt(input.value);

    const stats = getStats();
    const total = Object.values(stats).reduce((a,b)=>a+b,0);
    const countFive = Object.values(stats).filter(v => v === 5).length;

    if (delta < 0 && value > 1) {
        value--;
    }

    if (delta > 0) {
        if (value >= 5) return;
        if (total >= maxPoints) return;
        if (value === 4 && countFive >= 1) return;
        value++;
    }

    input.value = value;
    display.textContent = value;

    updateRemaining();
}

updateRemaining();

/* typing */
const text = <?= json_encode($text) ?>;
let i = 0;
const target = document.getElementById("leneText");

function type() {
    target.textContent = text.substring(0, i);
    i++;
    if (i <= text.length) {
        setTimeout(type, 50);
    } else {
        setTimeout(() => { i = 0; type(); }, 1500);
    }
}
type();
</script>

</body>
</html>

<?php
// by LaEmiX
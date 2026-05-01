<?php

declare(strict_types=1);

require __DIR__ . '/onboarding_core.php';

if (
    !isset($_SESSION['onboarding']['city']) ||
    !isset($_SESSION['onboarding']['echoes']) ||
    !isset($_SESSION['onboarding']['stats_city']) ||
    !isset($_SESSION['onboarding']['stats_echoes'])
) {
    header('Location: /onboarding_city.php');
    exit;
}

$user = $_SESSION['user'];

$cityName = (string) $_SESSION['onboarding']['city'];
$echoName = (string) $_SESSION['onboarding']['echoes'];

$statsCity = (array) $_SESSION['onboarding']['stats_city'];
$statsEcho = (array) $_SESSION['onboarding']['stats_echoes'];

$fieldError = '';

function naraka_slug_confirm(string $value): string
{
    $slug = strtolower(trim($value));
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim((string) $slug, '-');

    if ($slug === '') {
        $slug = 'personaggio';
    }

    return $slug;
}

$cityLand = naraka_get_land($pdo, 'city');
$echoLand = naraka_get_land($pdo, 'echoes');

$idCity = (int) $cityLand['id_land'];
$idEcho = (int) $echoLand['id_land'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO characters (id_user, id_land, name, slug)
            VALUES (:id_user, :id_land, :name, :slug)
        ");

        $stmt->execute([
            'id_user' => (int) $user['id_user'],
            'id_land' => $idCity,
            'name' => $cityName,
            'slug' => naraka_slug_confirm($cityName),
        ]);

        $idCharCity = (int) $pdo->lastInsertId();

        $stmt->execute([
            'id_user' => (int) $user['id_user'],
            'id_land' => $idEcho,
            'name' => $echoName,
            'slug' => naraka_slug_confirm($echoName),
        ]);

        $idCharEcho = (int) $pdo->lastInsertId();

        $stmt = $pdo->query("
            SELECT id_stat, slug
            FROM stats
        ");

        $allStats = $stmt->fetchAll();

        $map = [];

        foreach ($allStats as $stat) {
            $map[(string) $stat['slug']] = (int) $stat['id_stat'];
        }

        $stmt = $pdo->prepare("
            INSERT INTO character_stats (id_character, id_stat, value)
            VALUES (:id_character, :id_stat, :value)
        ");

        foreach ($statsCity as $slug => $value) {
            if (!isset($map[$slug])) {
                throw new RuntimeException('Stat City non trovata: ' . $slug);
            }

            $stmt->execute([
                'id_character' => $idCharCity,
                'id_stat' => $map[$slug],
                'value' => (int) $value,
            ]);
        }

        foreach ($statsEcho as $slug => $value) {
            if (!isset($map[$slug])) {
                throw new RuntimeException('Stat Echoes non trovata: ' . $slug);
            }

            $stmt->execute([
                'id_character' => $idCharEcho,
                'id_stat' => $map[$slug],
                'value' => (int) $value,
            ]);
        }

        $pdo->commit();

        unset($_SESSION['onboarding']);

        $_SESSION['current_land'] = 'city';

        header('Location: /index.php');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $fieldError = 'Errore durante la creazione del personaggio: ' . $e->getMessage();
    }
}

$text = "Mi raccomando, controlla che sia tutto ok prima di lanciarti in game. Grazie di aver scelto l'Echo System. Ti aspetto in NARAKA!";

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
        <img src="/themes/images/lene5.png" class="lene-image" alt="Lene">
    </div>

    <div class="auth-panel">

        <p class="lene-text" id="leneText"></p>

        <?php if ($fieldError !== '') { ?>
            <div class="auth-errors">
                <ul>
                    <li><?php echo htmlspecialchars($fieldError, ENT_QUOTES, 'UTF-8'); ?></li>
                </ul>
            </div>
        <?php } ?>

        <div class="auth-summary">

            <div class="auth-summary-card">
                <h2><?php echo htmlspecialchars($cityName, ENT_QUOTES, 'UTF-8'); ?></h2>
                <ul>
                    <?php foreach ($statsCity as $key => $value) { ?>
                        <li><?php echo htmlspecialchars(ucfirst((string) $key), ENT_QUOTES, 'UTF-8'); ?>: <?php echo (int) $value; ?></li>
                    <?php } ?>
                </ul>
            </div>

            <div class="auth-summary-card">
                <h2><?php echo htmlspecialchars($echoName, ENT_QUOTES, 'UTF-8'); ?></h2>
                <ul>
                    <?php foreach ($statsEcho as $key => $value) { ?>
                        <li><?php echo htmlspecialchars(ucfirst((string) $key), ENT_QUOTES, 'UTF-8'); ?>: <?php echo (int) $value; ?></li>
                    <?php } ?>
                </ul>
            </div>

        </div>

        <form method="post">
            <button class="auth-button">Entra in Naraka</button>
        </form>

        <div class="auth-links">
            <a href="/onboarding_stats_echoes.php">Indietro</a>
        </div>

    </div>

</div>

<script>
const text = <?php echo json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
let i = 0;
const target = document.getElementById("leneText");

function type() {
    target.textContent = text.substring(0, i);
    i++;

    if (i <= text.length) {
        setTimeout(type, 40);
    } else {
        setTimeout(() => {
            i = 0;
            type();
        }, 2000);
    }
}

type();
</script>

</body>
</html>

<?php
// by LaEmiX
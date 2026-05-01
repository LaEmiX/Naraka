<?php

declare(strict_types=1);

require_once __DIR__ . '/meteo/meteo.php';
$ambiente = ottieniMeteoMondo($pdo);

$condizioneVisibile = ucfirst(str_replace('_', ' ', (string) $ambiente['condizione']));

$topbarMenuItems = [
    [
        'key' => 'reload',
        'label' => 'RELOAD',
        'href' => '#',
        'onclick' => 'window.location.reload(); return false;',
    ],
    [
        'key' => 'mappa',
        'label' => 'MAPPA',
        'href' => '/index.php',
        'onclick' => '',
    ],
    [
        'key' => 'scheda',
        'label' => 'SCHEDA',
        'href' => '/scheda.php',
        'onclick' => '',
    ],
    [
        'key' => 'regolamento',
        'label' => 'REGOLAMENTO',
        'href' => '/regolamento.php',
        'onclick' => '',
    ],
    [
        'key' => 'lore',
        'label' => 'LORE',
        'href' => '/lore.php',
        'onclick' => '',
    ],
    [
        'key' => 'servizi',
        'label' => 'SERVIZI',
        'href' => '/servizi.php',
        'onclick' => '',
    ],
];

$isMaster = hasRole($user, 'master');
$isGestore = hasRole($user, 'gestore');
$isAdmin = hasRole($user, 'admin');

if ($isMaster || $isGestore || $isAdmin) {
    $topbarMenuItems[] = [
        'key' => 'master',
        'label' => 'MASTER',
        'href' => '/master.php',
        'onclick' => '',
    ];
}

if ($isGestore || $isAdmin) {
    $topbarMenuItems[] = [
        'key' => 'gestione',
        'label' => 'GESTIONE',
        'href' => '/gestione.php',
        'onclick' => '',
    ];
}

?>

<div class="topbar-center-wrap">

    <div
        class="meteo-box"
        data-meteo-root
        data-meteo-fascia="<?php echo e((string) $ambiente['fascia']); ?>"
        data-meteo-condizione="<?php echo e((string) $ambiente['condizione']); ?>"
    >

        <div class="meteo-trigger" data-meteo-clock>
            <?php echo e((string) $ambiente['data_ora']); ?>
        </div>

        <div class="meteo-dropdown">

            <div class="meteo-temp" data-meteo-temp>
                <?php if ($ambiente['fascia'] === 'notte') { ?>
                    <?php echo e((string) $ambiente['temperatura']); ?>°C · <?php echo e($condizioneVisibile); ?> · <?php echo e((string) $ambiente['luna']); ?>
                <?php } else { ?>
                    <?php echo e((string) $ambiente['temperatura']); ?>°C · <?php echo e($condizioneVisibile); ?>
                <?php } ?>
            </div>

            <div class="meteo-desc" data-meteo-desc>
                <?php echo e((string) $ambiente['descrizione']); ?>
            </div>

        </div>

    </div>

    <div class="topbar-menu-wrap">

        <button type="button" class="topbar-menu-trigger" aria-label="Apri menu principale">
            MENU
        </button>

        <nav class="topbar-menu" aria-label="Menu principale">

            <?php foreach ($topbarMenuItems as $item) { ?>

                <?php
                    $imagePath = '/themes/images/menu/' . $item['key'] . '.png';
                    $imageFile = __DIR__ . '/../themes/images/menu/' . $item['key'] . '.png';
                    $hasImage = file_exists($imageFile);
                ?>

                <a
                    href="<?php echo e($item['href']); ?>"
                    class="topbar-menu-link"
                    <?php if ($item['onclick'] !== '') { ?>
                        onclick="<?php echo e($item['onclick']); ?>"
                    <?php } ?>
                >
                    <?php if ($hasImage) { ?>
                        <img
                            src="<?php echo e($imagePath); ?>"
                            class="topbar-menu-img"
                            alt="<?php echo e($item['label']); ?>"
                        >
                    <?php } else { ?>
                        <span class="topbar-menu-text"><?php echo e($item['label']); ?></span>
                    <?php } ?>
                </a>

            <?php } ?>

        </nav>

    </div>

</div>

<script src="/themes/js/meteo.js?v=02" defer></script>

<?php
// by LaEmiX
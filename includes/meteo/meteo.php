<?php

declare(strict_types=1);

require_once __DIR__ . '/tempo.php';
require_once __DIR__ . '/luna.php';
require_once __DIR__ . '/generatore.php';
require_once __DIR__ . '/descrizioni.php';
require_once __DIR__ . '/modificatori.php';

function ottieniMeteoMondo(PDO $pdo): array
{
    $adesso = meteoDataOraItaliana();

    $data = $adesso->format('Y-m-d');
    $ora = (int) $adesso->format('G');
    $fascia = meteoFasciaOraria($ora);

    $meteo = meteoOttieniOGeneraGiorno($pdo, $data, $adesso);

    $temperatura = meteoTemperaturaAttuale(
        (int) $meteo['temp_min'],
        (int) $meteo['temp_max'],
        $ora
    );

    $luna = meteoFaseLunare($data);

    $chiaveDescrizione = $data . $fascia . (string) $meteo['condizione'] . (string) $temperatura;

    $descrizioneBase = meteoDescrizione($meteo, $fascia, $temperatura);

    $descrizioneModificatore = meteoDescrizioneModificatore(
        $meteo['modificatore'] ?? null,
        $temperatura,
        $chiaveDescrizione
    );

    $descrizioneFinale = $descrizioneModificatore !== null
        ? $descrizioneModificatore
        : $descrizioneBase;

    return [
        'data' => $data,
        'ora' => $adesso->format('H:i'),
        'data_ora' => meteoDataOraVisibile($adesso),

        'fascia' => $fascia,
        'temperatura' => $temperatura,
        'luna' => $luna,

        'condizione' => $meteo['condizione'],
        'intensita' => $meteo['intensita'],
        'vento' => $meteo['vento'],
        'umidita' => $meteo['umidita'],
        'modificatore' => $meteo['modificatore'],

        'descrizione' => $descrizioneFinale,
    ];
}
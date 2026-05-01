<?php

declare(strict_types=1);

function meteoModificatoreCompatibile(array $frase, int $temperatura): bool
{
    if ($frase['min_temp'] !== null && $temperatura < $frase['min_temp']) {
        return false;
    }

    if ($frase['max_temp'] !== null && $temperatura > $frase['max_temp']) {
        return false;
    }

    return true;
}

function meteoScegliDescrizioneModificatore(array $frasi, int $temperatura, string $chiave): ?string
{
    $compatibili = array_values(array_filter(
        $frasi,
        static fn (array $frase): bool => meteoModificatoreCompatibile($frase, $temperatura)
    ));

    if ($compatibili === []) {
        return null;
    }

    $indice = abs(crc32($chiave)) % count($compatibili);

    return $compatibili[$indice]['testo'];
}

function meteoDescrizioneModificatore(?string $modificatore, int $temperatura, string $chiave): ?string
{
    if ($modificatore === null || $modificatore === '') {
        return null;
    }

    $descrizioni = [
        'caldo_torrido' => [
            [
                'testo' => 'Caldo pesante sulla valle, superfici roventi e aria quasi ferma.',
                'min_temp' => 33,
                'max_temp' => null,
            ],
            [
                'testo' => 'Il caldo è intenso, con sole cocente e aria immobile tra le case.',
                'min_temp' => 33,
                'max_temp' => null,
            ],
        ],

        'afa_umida' => [
            [
                'testo' => 'Umidità alta e caldo torrido rendono l’aria afosa.',
                'min_temp' => 30,
                'max_temp' => null,
            ],
            [
                'testo' => 'L’aria è calda e umida, con una pesantezza che resta addosso.',
                'min_temp' => 27,
                'max_temp' => null,
            ],
        ],

        'gelata' => [
            [
                'testo' => 'Freddo intenso sulla valle, superfici ghiacciate e aria tagliente.',
                'min_temp' => null,
                'max_temp' => 0,
            ],
            [
                'testo' => 'Una patina di gelo resta sulle superfici esposte.',
                'min_temp' => null,
                'max_temp' => 0,
            ],
        ],

        'schiarite' => [
            [
                'testo' => 'A tratti le nubi si aprono, lasciando passare una luce fioca.',
                'min_temp' => null,
                'max_temp' => null,
            ],
            [
                'testo' => 'Brevi schiarite rompono il cielo chiuso senza cambiare davvero il tempo.',
                'min_temp' => null,
                'max_temp' => null,
            ],
        ],

        'raffiche' => [
            [
                'testo' => 'Raffiche improvvise attraversano la valle e sferzano l’aria.',
                'min_temp' => null,
                'max_temp' => null,
            ],
            [
                'testo' => 'Il vento arriva in forti raffiche, soprattutto tra le case e lungo le strade esposte.',
                'min_temp' => null,
                'max_temp' => null,
            ],
        ],
    ];

    if (!isset($descrizioni[$modificatore])) {
        return null;
    }

    return meteoScegliDescrizioneModificatore(
        $descrizioni[$modificatore],
        $temperatura,
        $chiave . $modificatore
    );
}
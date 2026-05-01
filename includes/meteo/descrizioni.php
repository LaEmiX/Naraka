<?php

declare(strict_types=1);

function meteoFraseCompatibile(array $frase, int $temperatura): bool
{
    if ($frase['min_temp'] !== null && $temperatura < $frase['min_temp']) {
        return false;
    }

    if ($frase['max_temp'] !== null && $temperatura > $frase['max_temp']) {
        return false;
    }

    return true;
}

function meteoScegliDescrizione(array $frasi, int $temperatura, string $chiave): string
{
    $compatibili = array_values(array_filter(
        $frasi,
        static fn (array $frase): bool => meteoFraseCompatibile($frase, $temperatura)
    ));

    if ($compatibili === []) {
        $compatibili = $frasi;
    }

    $indice = abs(crc32($chiave)) % count($compatibili);

    return $compatibili[$indice]['testo'];
}

function meteoDescrizione(array $meteo, string $fascia, int $temperatura): string
{
    $condizione = (string) $meteo['condizione'];

    $descrizioni = [
        'sereno' => [
            'mattina' => [
                [
                    'testo' => 'Cielo limpido sopra la valle, luce chiara sui rilievi e aria fresca.',
                    'min_temp' => null,
                    'max_temp' => 18,
                ],
                [
                    'testo' => 'La giornata si apre senza nubi, aria ferma e temperature miti.',
                    'min_temp' => 15,
                    'max_temp' => 24,
                ],
            ],
            'pomeriggio' => [
                [
                    'testo' => 'Cielo pulito, luce piena sulla città e aria ferma.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
                [
                    'testo' => 'Il pomeriggio è sereno, aria ferma e visibilità ampia sui rilievi.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'sera' => [
                [
                    'testo' => 'Il cielo resta limpido mentre le temperature si abbassano lentamente.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
                [
                    'testo' => 'Sera serena, aria fresca e rilievi ancora ben visibili.',
                    'min_temp' => null,
                    'max_temp' => 18,
                ],
            ],
            'notte' => [
                [
                    'testo' => 'Notte limpida, aria fredda e cielo libero sopra la valle.',
                    'min_temp' => null,
                    'max_temp' => 10,
                ],
                [
                    'testo' => 'Cielo sereno anche nella notte, aria ferma e temperature in calo.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
        ],

        'velato' => [
            'mattina' => [
                [
                    'testo' => 'Cielo velato al mattino, luce morbida e aria fresca.',
                    'min_temp' => null,
                    'max_temp' => 18,
                ],
                [
                    'testo' => 'Una velatura sottile copre il sole senza chiudere il cielo.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'pomeriggio' => [
                [
                    'testo' => 'Cielo chiaro ma velato, con luce meno netta sui rilievi.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
                [
                    'testo' => 'Il sole attraversa un leggero velo di nubi, immergendo la città in una luce pallida.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'sera' => [
                [
                    'testo' => 'Velature alte accompagnano il calare del giorno, aria fresca.',
                    'min_temp' => null,
                    'max_temp' => 18,
                ],
                [
                    'testo' => 'Il cielo si vela verso sera, mentre la luce perde intensità.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'notte' => [
                [
                    'testo' => 'Cielo velato nella notte, luna meno nitida e aria fredda.',
                    'min_temp' => null,
                    'max_temp' => 10,
                ],
                [
                    'testo' => 'La notte resta chiara a tratti, con la luna filtrata da nubi alte.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
        ],

        'nuvoloso' => [
            'mattina' => [
                [
                    'testo' => 'Nubi sparse sui rilievi, luce irregolare e aria fresca.',
                    'min_temp' => null,
                    'max_temp' => 18,
                ],
                [
                    'testo' => 'Il mattino è nuvoloso, aria umida e temperature miti.',
                    'min_temp' => 15,
                    'max_temp' => 24,
                ],
            ],
            'pomeriggio' => [
                [
                    'testo' => 'Nubi compatte attraversano la valle, alternando luce e ombra.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
                [
                    'testo' => 'Il cielo è nuvoloso, ma la visibilità resta buona sui rilievi.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'sera' => [
                [
                    'testo' => 'Le nubi aumentano verso sera, aria più fredda tra le case.',
                    'min_temp' => null,
                    'max_temp' => 14,
                ],
                [
                    'testo' => 'Sera nuvolosa, luce bassa e temperature in calo.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'notte' => [
                [
                    'testo' => 'Notte nuvolosa, luna spesso coperta e aria fredda.',
                    'min_temp' => null,
                    'max_temp' => 10,
                ],
                [
                    'testo' => 'Nubi dense sopra la valle, con poca luce e temperature basse.',
                    'min_temp' => null,
                    'max_temp' => 8,
                ],
            ],
        ],

        'coperto' => [
            'mattina' => [
                [
                    'testo' => 'Cielo chiuso fin dal mattino, luce debole e aria fredda.',
                    'min_temp' => null,
                    'max_temp' => 10,
                ],
                [
                    'testo' => 'Una copertura compatta grava sulla valle, con temperature basse.',
                    'min_temp' => null,
                    'max_temp' => 8,
                ],
            ],
            'pomeriggio' => [
                [
                    'testo' => 'Cielo coperto, luce spenta e rilievi meno definiti.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
                [
                    'testo' => 'Il pomeriggio resta grigio, con aria fredda.',
                    'min_temp' => null,
                    'max_temp' => 10,
                ],
            ],
            'sera' => [
                [
                    'testo' => 'Cielo ancora chiuso verso sera, aria umida e fredda.',
                    'min_temp' => null,
                    'max_temp' => 10,
                ],
                [
                    'testo' => 'La sera scende sotto nubi compatte e temperature in calo.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'notte' => [
                [
                    'testo' => 'Notte scura, luna coperta e aria fredda.',
                    'min_temp' => null,
                    'max_temp' => 10,
                ],
                [
                    'testo' => 'Cielo chiuso anche nel buio, con umidità alta.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
        ],

        'foschia' => [
            'mattina' => [
                [
                    'testo' => 'Foschia leggera al mattino, contorni più morbidi e aria umida.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
                [
                    'testo' => 'La valle si sveglia sotto una foschia sottile, con visibilità appena ridotta.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'pomeriggio' => [
                [
                    'testo' => 'Il pomeriggio resta velato dalla foschia, con visibilità ridotta sulle lunghe distanze.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
                [
                    'testo' => 'Foschia sospesa sulla valle, luce opaca e aria umida.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'sera' => [
                [
                    'testo' => 'La foschia aumenta verso sera, sfumando case e rilievi.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
                [
                    'testo' => 'Sera fosca, aria umida e temperature in calo.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'notte' => [
                [
                    'testo' => 'Foschia notturna sulla valle, luna coperta e aria fredda.',
                    'min_temp' => null,
                    'max_temp' => 10,
                ],
                [
                    'testo' => 'La notte è velata dalla foschia, con luci più deboli e contorni incerti.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
        ],

        'nebbia' => [
            'mattina' => [
                [
                    'testo' => 'Nebbia bassa tra le case, visibilità ridotta e aria fredda.',
                    'min_temp' => null,
                    'max_temp' => 10,
                ],
                [
                    'testo' => 'La nebbia resta nei punti bassi della valle, coprendo strade e rilievi.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'pomeriggio' => [
                [
                    'testo' => 'Nebbia ancora presente nelle zone basse, aria umida e fredda.',
                    'min_temp' => null,
                    'max_temp' => 10,
                ],
                [
                    'testo' => 'La visibilità resta scarsa, soprattutto verso i rilievi e le strade fuori città.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'sera' => [
                [
                    'testo' => 'La nebbia torna a salire con la sera, coprendo lentamente la valle.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
                [
                    'testo' => 'Nebbia più fitta verso sera, aria umida e temperature in calo.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'notte' => [
                [
                    'testo' => 'Nebbia fitta nella notte, luna quasi invisibile e suoni attenuati.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
                [
                    'testo' => 'La valle resta immersa nella nebbia, con visibilità molto ridotta.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
        ],

        'pioggia' => [
            'mattina' => [
                [
                    'testo' => 'Pioggia fine al mattino, cielo chiuso e aria fredda.',
                    'min_temp' => null,
                    'max_temp' => 10,
                ],
                [
                    'testo' => 'Piove con regolarità, lasciando strade lucide e umidità diffusa.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'pomeriggio' => [
                [
                    'testo' => 'Pioggia continua sulla valle, con cielo basso e rilievi sfumati.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
                [
                    'testo' => 'Il pomeriggio resta uggioso, con gocce leggere ma insistenti.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'sera' => [
                [
                    'testo' => 'Pioggia sottile verso sera, aria fredda e strade scure.',
                    'min_temp' => null,
                    'max_temp' => 10,
                ],
                [
                    'testo' => 'La pioggia continua mentre cala la luce, con umidità alta tra le case.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'notte' => [
                [
                    'testo' => 'Pioggia nel buio, cielo chiuso e luna nascosta.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
                [
                    'testo' => 'La notte resta umida, con pioggia costante e aria fredda.',
                    'min_temp' => null,
                    'max_temp' => 10,
                ],
            ],
        ],

        'temporale' => [
            'mattina' => [
                [
                    'testo' => 'Temporale sui rilievi, pioggia forte e vento irregolare.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
                [
                    'testo' => 'Rovesci intensi al mattino, aria instabile e nubi basse.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'pomeriggio' => [
                [
                    'testo' => 'Temporale sulla valle, pioggia fitta e raffiche improvvise.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
                [
                    'testo' => 'Il cielo si chiude in fretta, con rovesci forti e vento teso.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'sera' => [
                [
                    'testo' => 'Temporale verso sera, pioggia battente e raffiche tra le case.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
                [
                    'testo' => 'La sera è agitata da rovesci forti e vento irregolare.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
            'notte' => [
                [
                    'testo' => 'Temporale nella notte, pioggia serrata e luna completamente nascosta.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
                [
                    'testo' => 'Raffiche e pioggia forte attraversano la valle.',
                    'min_temp' => null,
                    'max_temp' => null,
                ],
            ],
        ],

        'neve' => [
            'mattina' => [
                [
                    'testo' => 'Nevicata leggera al mattino, tetti imbiancati e aria gelida.',
                    'min_temp' => null,
                    'max_temp' => 2,
                ],
                [
                    'testo' => 'Fiocchi sottili scendono sulla valle, con temperature molto basse.',
                    'min_temp' => null,
                    'max_temp' => 3,
                ],
            ],
            'pomeriggio' => [
                [
                    'testo' => 'La neve continua sui rilievi e sulle strade.',
                    'min_temp' => null,
                    'max_temp' => 3,
                ],
                [
                    'testo' => 'Fiocchi leggeri ma costanti coprono lentamente la città.',
                    'min_temp' => null,
                    'max_temp' => 2,
                ],
            ],
            'sera' => [
                [
                    'testo' => 'Neve verso sera, aria gelida e rumori più ovattati.',
                    'min_temp' => null,
                    'max_temp' => 2,
                ],
                [
                    'testo' => 'La sera scende con fiocchi radi e temperature sotto lo zero.',
                    'min_temp' => null,
                    'max_temp' => 0,
                ],
            ],
            'notte' => [
                [
                    'testo' => 'Neve nella notte, luna nascosta e città quasi silenziosa.',
                    'min_temp' => null,
                    'max_temp' => 2,
                ],
                [
                    'testo' => 'Il buio si riempie di fiocchi lenti, con aria immobile e gelida.',
                    'min_temp' => null,
                    'max_temp' => 2,
                ],
            ],
        ],
    ];

    $frasi = $descrizioni[$condizione][$fascia] ?? [[
        'testo' => 'Il tempo resta variabile sulla valle.',
        'min_temp' => null,
        'max_temp' => null,
    ]];

    return meteoScegliDescrizione(
        $frasi,
        $temperatura,
        date('Y-m-d') . $fascia . $condizione . (string) $temperatura
    );
}
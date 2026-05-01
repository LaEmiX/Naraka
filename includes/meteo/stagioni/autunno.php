<?php

declare(strict_types=1);

return [

    'condizione_iniziale' => 'nuvoloso',

    'condizioni_ammesse' => [
        'sereno',
        'velato',
        'nuvoloso',
        'coperto',
        'foschia',
        'nebbia',
        'pioggia',
        'temporale',
    ],

    'temperature_mensili' => [
        9  => [12, 30],
        10 => [8, 24],
        11 => [4, 17],
    ],

    'transizioni' => [
        'sereno'    => ['sereno', 'velato', 'foschia', 'nuvoloso'],
        'velato'    => ['sereno', 'velato', 'foschia', 'nuvoloso'],
        'nuvoloso'  => ['velato', 'nuvoloso', 'coperto', 'pioggia', 'nebbia'],
        'coperto'   => ['nuvoloso', 'coperto', 'pioggia', 'nebbia'],
        'foschia'   => ['foschia', 'nebbia', 'velato', 'nuvoloso'],
        'nebbia'    => ['nebbia', 'foschia', 'coperto', 'pioggia'],
        'pioggia'   => ['pioggia', 'coperto', 'nebbia', 'nuvoloso'],
        'temporale' => ['pioggia', 'coperto', 'nuvoloso'],
        'neve'      => ['coperto', 'nuvoloso'],
    ],

];

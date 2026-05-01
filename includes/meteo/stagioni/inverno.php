<?php

declare(strict_types=1);

return [

    'condizione_iniziale' => 'coperto',

    'condizioni_ammesse' => [
        'sereno',
        'velato',
        'nuvoloso',
        'coperto',
        'foschia',
        'nebbia',
        'pioggia',
        'temporale',
        'neve',
    ],

    'temperature_mensili' => [
        12 => [-2, 11],
        1  => [-3, 10],
        2  => [-2, 12],
    ],

    'transizioni' => [
        'sereno'   => ['sereno', 'velato', 'nuvoloso', 'foschia'],
        'velato'   => ['sereno', 'velato', 'nuvoloso', 'foschia'],
        'nuvoloso' => ['nuvoloso', 'coperto', 'pioggia', 'nebbia'],
        'coperto'  => ['coperto', 'pioggia', 'nebbia', 'neve'],
        'foschia'  => ['foschia', 'nebbia', 'nuvoloso', 'velato'],
        'nebbia'   => ['nebbia', 'foschia', 'coperto', 'pioggia'],
        'pioggia'  => ['pioggia', 'coperto', 'nebbia', 'neve'],
        'temporale'=> ['pioggia', 'coperto', 'nuvoloso'],
        'neve'     => ['neve', 'coperto', 'nebbia'],
    ],

];
<?php

declare(strict_types=1);

return [

    'condizione_iniziale' => 'velato',

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
        3 => [3, 16],
        4 => [6, 20],
        5 => [9, 24],
    ],

    'transizioni' => [
        'sereno'    => ['sereno', 'velato', 'nuvoloso', 'foschia'],
        'velato'    => ['sereno', 'velato', 'nuvoloso'],
        'nuvoloso'  => ['velato', 'nuvoloso', 'coperto', 'pioggia'],
        'coperto'   => ['nuvoloso', 'coperto', 'pioggia', 'temporale'],
        'foschia'   => ['foschia', 'velato', 'sereno', 'nuvoloso'],
        'nebbia'    => ['foschia', 'nuvoloso', 'coperto'],
        'pioggia'   => ['pioggia', 'coperto', 'nuvoloso', 'velato'],
        'temporale' => ['pioggia', 'coperto', 'nuvoloso'],
        'neve'      => ['coperto', 'nuvoloso'],
    ],

];
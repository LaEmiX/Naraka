<?php

declare(strict_types=1);

return [

    'condizione_iniziale' => 'sereno',

    'condizioni_ammesse' => [
        'sereno',
        'velato',
        'nuvoloso',
        'foschia',
        'temporale',
    ],

    'temperature_mensili' => [
        6 => [12, 29],
        7 => [15, 35],
        8 => [15, 35],
    ],

    'transizioni' => [
        'sereno'    => ['sereno', 'sereno', 'velato', 'foschia'],
        'velato'    => ['sereno', 'sereno', 'velato', 'nuvoloso'],
        'nuvoloso'  => ['velato', 'nuvoloso', 'temporale'],
        'coperto'   => ['nuvoloso', 'velato', 'sereno'],
        'foschia'   => ['foschia', 'sereno', 'velato'],
        'nebbia'    => ['foschia', 'sereno'],
        'pioggia'   => ['nuvoloso', 'velato', 'sereno'],
        'temporale' => ['nuvoloso', 'velato', 'sereno'],
        'neve'      => ['sereno'],
    ],

];
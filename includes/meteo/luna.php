<?php

declare(strict_types=1);

function meteoFaseLunare(string $data): string
{
    $lunaNuovaRiferimento = new DateTimeImmutable('2000-01-06');
    $dataCorrente = new DateTimeImmutable($data);

    $giorni = (int) $lunaNuovaRiferimento->diff($dataCorrente)->format('%r%a');
    $ciclo = 29.53058867;

    $eta = fmod($giorni, $ciclo);

    if ($eta < 0) {
        $eta += $ciclo;
    }

    return match (true) {
        $eta < 1.85  => 'luna nuova',
        $eta < 5.54  => 'luna crescente',
        $eta < 9.23  => 'primo quarto',
        $eta < 12.92 => 'luna gibbosa crescente',
        $eta < 16.61 => 'luna piena',
        $eta < 20.30 => 'luna gibbosa calante',
        $eta < 23.99 => 'ultimo quarto',
        default      => 'luna calante',
    };
}
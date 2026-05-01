<?php

declare(strict_types=1);

function meteoDataOraItaliana(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone('Europe/Rome'));
}

function meteoFasciaOraria(int $ora): string
{
    return match (true) {
        $ora < 6  => 'notte',
        $ora < 11 => 'mattina',
        $ora < 17 => 'pomeriggio',
        $ora < 21 => 'sera',
        default   => 'notte',
    };
}

function meteoStagioneDaMese(int $mese): string
{
    return match (true) {
        $mese === 12 || $mese <= 2 => 'inverno',
        $mese >= 3 && $mese <= 5  => 'primavera',
        $mese >= 6 && $mese <= 8  => 'estate',
        default                   => 'autunno',
    };
}

function meteoTemperaturaAttuale(int $min, int $max, int $ora): int
{
    $fattore = match (true) {
        $ora < 6  => 0.10,
        $ora < 9  => 0.25,
        $ora < 12 => 0.50,
        $ora < 16 => 0.90,
        $ora < 19 => 0.70,
        $ora < 22 => 0.40,
        default   => 0.20,
    };

    return (int) round($min + (($max - $min) * $fattore));
}

function meteoDataOraVisibile(DateTimeImmutable $data): string
{
    return $data->format('d/m/Y — H:i');
}
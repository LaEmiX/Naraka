<?php

declare(strict_types=1);

require_once __DIR__ . '/tempo.php';

function meteoCaricaStagione(string $stagione): array
{
    $file = __DIR__ . '/stagioni/' . $stagione . '.php';

    if (!is_file($file)) {
        throw new RuntimeException('File stagione non trovato');
    }

    return require $file;
}

function meteoOttieniOGeneraGiorno(PDO $pdo, string $data, DateTimeImmutable $adesso): array
{
    $stmt = $pdo->prepare("SELECT * FROM meteo_mondo WHERE data_meteo = :data LIMIT 1");
    $stmt->execute(['data' => $data]);

    $meteo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($meteo) {
        return $meteo;
    }

    return meteoGeneraGiorno($pdo, $data, $adesso);
}

function meteoGeneraGiorno(PDO $pdo, string $data, DateTimeImmutable $adesso): array
{
    $mese = (int) $adesso->format('n');
    $stagione = meteoStagioneDaMese($mese);

    $regole = meteoCaricaStagione($stagione);

    // giorno precedente
    $dataPrecedente = (new DateTimeImmutable($data))->modify('-1 day')->format('Y-m-d');

    $stmt = $pdo->prepare("SELECT condizione FROM meteo_mondo WHERE data_meteo = :data LIMIT 1");
    $stmt->execute(['data' => $dataPrecedente]);

    $precedente = $stmt->fetchColumn();

    $condizioneBase = $precedente ?: $regole['condizione_iniziale'];

    $possibili = $regole['transizioni'][$condizioneBase]
        ?? $regole['transizioni'][$regole['condizione_iniziale']];

    $condizione = $possibili[array_rand($possibili)];

    // temperatura coerente con stagione
    [$minBase, $maxBase] = $regole['temperature_mensili'][$mese];

    $tempMin = random_int($minBase, $minBase + 3);
    $tempMax = random_int($maxBase - 3, $maxBase);

    if ($tempMax <= $tempMin) {
        $tempMax = $tempMin + random_int(4, 8);
    }

    // blocchi logici fondamentali

    if ($condizione === 'neve' && $tempMax > 2) {
        $condizione = 'coperto';
    }

    if ($condizione === 'temporale' && !in_array('temporale', $regole['condizioni_ammesse'], true)) {
        $condizione = 'pioggia';
    }

    if (!in_array($condizione, $regole['condizioni_ammesse'], true)) {
        $condizione = $regole['condizione_iniziale'];
    }

    // intensità
    $intensita = match ($condizione) {
        'pioggia', 'neve' => ['debole', 'moderata'][array_rand(['debole', 'moderata'])],
        'temporale' => ['moderata', 'forte'][array_rand(['moderata', 'forte'])],
        default => null,
    };

    // vento
    $vento = match ($condizione) {
        'sereno', 'velato' => ['calmo', 'leggero'][array_rand(['calmo', 'leggero'])],
        'nuvoloso', 'coperto', 'foschia', 'nebbia' => ['leggero', 'moderato'][array_rand(['leggero', 'moderato'])],
        'pioggia', 'neve' => ['leggero', 'moderato'][array_rand(['leggero', 'moderato'])],
        'temporale' => ['moderato', 'forte'][array_rand(['moderato', 'forte'])],
        default => 'leggero',
    };

    // umidità
    $umidita = match ($condizione) {
        'nebbia', 'pioggia', 'temporale', 'neve' => ['alta', 'molto alta'][array_rand(['alta', 'molto alta'])],
        'coperto', 'foschia' => ['media', 'alta'][array_rand(['media', 'alta'])],
        default => ['bassa', 'media'][array_rand(['bassa', 'media'])],
    };

    // modificatori coerenti
    $modificatore = null;

    if ($tempMax >= 33) {
        $modificatore = 'caldo_torrido';
    } elseif ($tempMin <= 0) {
        $modificatore = 'gelata';
    } elseif ($vento === 'forte') {
        $modificatore = 'raffiche';
    } elseif ($umidita === 'molto alta' && $tempMax >= 27 && !in_array($condizione, ['pioggia', 'temporale', 'neve'], true)) {
        $modificatore = 'afa_umida';
    } elseif (in_array($condizione, ['coperto', 'pioggia'], true) && random_int(1, 100) <= 20) {
        $modificatore = 'schiarite';
    }

    // salvataggio
    $stmt = $pdo->prepare("
        INSERT INTO meteo_mondo
        (data_meteo, condizione, intensita, temp_min, temp_max, vento, umidita, modificatore)
        VALUES
        (:data, :condizione, :intensita, :temp_min, :temp_max, :vento, :umidita, :modificatore)
    ");

    $stmt->execute([
        'data' => $data,
        'condizione' => $condizione,
        'intensita' => $intensita,
        'temp_min' => $tempMin,
        'temp_max' => $tempMax,
        'vento' => $vento,
        'umidita' => $umidita,
        'modificatore' => $modificatore,
    ]);

    return [
        'data_meteo' => $data,
        'condizione' => $condizione,
        'intensita' => $intensita,
        'temp_min' => $tempMin,
        'temp_max' => $tempMax,
        'vento' => $vento,
        'umidita' => $umidita,
        'modificatore' => $modificatore,
    ];
}

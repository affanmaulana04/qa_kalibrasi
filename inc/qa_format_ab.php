<?php

function qaFormatABNumber($value): ?float {
    if (!is_scalar($value) || is_bool($value)) {
        return null;
    }
    $text = str_replace(',', '.', trim((string)$value));
    if (!preg_match('/^[+-]?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)$/D', $text)) {
        return null;
    }
    $number = (float)$text;
    return is_finite($number) && abs($number) <= 1000000 ? $number : null;
}

function qaFormatABStandards($input): ?array {
    if (!is_array($input) || count($input) < 1 || count($input) > 20
        || array_keys($input) !== range(0, count($input) - 1)) {
        return null;
    }
    $masters = [];
    foreach ($input as $row) {
        if (!is_array($row)) {
            return null;
        }
        foreach (['skala', 'standar', 'toleransi'] as $field) {
            if (!isset($row[$field]) || !is_scalar($row[$field])) {
                return null;
            }
        }
        $scale = qaFormatABNumber($row['skala'] ?? null);
        $standard = qaFormatABNumber($row['standar'] ?? null);
        $tolerance = qaFormatABNumber($row['toleransi'] ?? null);
        if ($scale === null || $standard === null || $tolerance === null
            || $scale < 0 || $standard < 0 || $tolerance < 0) {
            return null;
        }
        $masters[] = [
            'skala' => trim((string)$row['skala']),
            'standar' => trim((string)$row['standar']),
            'toleransi' => trim((string)$row['toleransi'])
        ];
    }
    return $masters;
}

function qaFormatABActual($raw, array $masters): ?array {
    $input = json_decode((string)$raw, true);
    if (qaFormatABStandards($masters) === null || !is_array($input)
        || count($input) !== count($masters)
        || array_keys($input) !== range(0, count($masters) - 1)) {
        return null;
    }
    $actual = [];
    foreach ($input as $i => $row) {
        if (!is_array($row)) {
            return null;
        }
        if ((isset($row['hasil_searah']) && !is_scalar($row['hasil_searah']))
            || (isset($row['hasil_berlawanan']) && !is_scalar($row['hasil_berlawanan']))) {
            return null;
        }
        $clockwise = trim((string)($row['hasil_searah'] ?? ''));
        $counterclockwise = trim((string)($row['hasil_berlawanan'] ?? ''));
        if ($clockwise === '' && $counterclockwise === '') {
            return null;
        }
        $forward = $clockwise !== '' ? qaFormatABNumber($clockwise) : null;
        $reverse = $counterclockwise !== '' ? qaFormatABNumber($counterclockwise) : null;
        if (($clockwise !== '' && $forward === null)
            || ($counterclockwise !== '' && $reverse === null)) {
            return null;
        }
        $master = $masters[$i];
        $standard = qaFormatABNumber($master['standar']);
        $tolerance = qaFormatABNumber($master['toleransi']);
        $minimum = $standard - $tolerance;
        $maximum = $standard + $tolerance;
        $good = true;
        foreach ([$forward, $reverse] as $reading) {
            if ($reading !== null && ($reading < $minimum - 1e-9 || $reading > $maximum + 1e-9)) {
                $good = false;
            }
        }
        $actual[] = $master + [
            'range_min' => number_format($minimum, 2, '.', ''),
            'range_max' => number_format($maximum, 2, '.', ''),
            'hasil_searah' => $clockwise,
            'hasil_berlawanan' => $counterclockwise,
            'status' => $good ? 'OK' : 'NOT GOOD'
        ];
    }
    return $actual;
}

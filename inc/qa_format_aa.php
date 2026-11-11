<?php
function qaFormatAANumber($value): ?float {
    if (!is_scalar($value)) {
        return null;
    }
    $text = str_replace(',', '.', trim((string)$value));
    return preg_match('/^[+-]?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)$/D', $text) ? (float)$text : null;
}

function qaFormatAATolerance($value): ?float {
    $text = trim((string)$value);
    $text = preg_replace('/^\s*(?:±|\+\/-)\s*/u', '', $text);
    $text = preg_replace('/\s*pH\s*$/i', '', $text);
    $number = qaFormatAANumber($text);
    return $number !== null && $number >= 0 && $number <= 14 ? $number : null;
}

function qaFormatAAStandards($input): ?array {
    if (!is_array($input) || count($input) !== 3 || array_keys($input) !== [0, 1, 2]) {
        return null;
    }
    $output = [];
    foreach ($input as $row) {
        if (!is_array($row)) {
            return null;
        }
        $liquid = qaFormatAANumber($row['cairan_std'] ?? null);
        $standard = qaFormatAANumber($row['standar'] ?? null);
        if ($liquid === null || $standard === null || $liquid < 0 || $liquid > 14
            || $standard < 0 || $standard > 14) {
            return null;
        }
        $output[] = [
            'cairan_std' => trim((string)$row['cairan_std']),
            'standar' => trim((string)$row['standar'])
        ];
    }
    return $output;
}

function qaFormatAAActual($raw, array $standards, $tolerance): ?array {
    $tol = qaFormatAATolerance($tolerance);
    $input = json_decode((string)$raw, true);
    if ($tol === null || count($standards) !== 3 || !is_array($input)
        || count($input) !== 9 || array_keys($input) !== range(0, 8)) {
        return null;
    }
    $output = [];
    foreach ($input as $index => $row) {
        if (!is_array($row)) {
            return null;
        }
        $actual = qaFormatAANumber($row['hasil'] ?? null);
        if ($actual === null || $actual < 0 || $actual > 14) {
            return null;
        }
        $master = $standards[intdiv($index, 3)];
        $correction = $actual - qaFormatAANumber($master['standar']);
        $output[] = [
            'cairan_std' => $master['cairan_std'],
            'standar' => $master['standar'],
            'hasil' => trim((string)$row['hasil']),
            'koreksi' => number_format($correction, 2, '.', ''),
            'status' => abs($correction) <= $tol + 0.000000001 ? 'OK' : 'NOT GOOD'
        ];
    }
    return $output;
}

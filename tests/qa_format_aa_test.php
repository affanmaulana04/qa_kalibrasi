<?php
if (PHP_SAPI !== 'cli') {
    exit;
}
require_once __DIR__ . '/../inc/qa_format_aa.php';
require_once __DIR__ . '/../inc/qa_migrasi_excel.php';

function assertAA($condition, $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$aliases = qaMigAliases();
assertAA($aliases[qaMigNameKey('Stop Watch')] === 'U', 'Stop Watch harus Format U.');
assertAA($aliases[qaMigNameKey('Stopwatch')] === 'U', 'Stopwatch harus Format U.');
assertAA($aliases[qaMigNameKey('pH meter')] === 'AA', 'pH meter harus Format AA.');
$master = qaFormatAAStandards([
    ['cairan_std' => '4.00', 'standar' => '4.00'],
    ['cairan_std' => '7.00', 'standar' => '7.00'],
    ['cairan_std' => '10.00', 'standar' => '10.00']
]);
assertAA($master !== null, 'Tiga standar pH valid.');
$input = [];
foreach ([4.0, 4.0, 4.0, 7.1, 7.1, 7.1, 10.1, 10.1, 10.1] as $value) {
    $input[] = ['hasil' => (string)$value, 'koreksi' => '999', 'standar' => '999', 'status' => 'OK'];
}
$rows = qaFormatAAActual(json_encode($input), $master, '± 0.5 pH');
assertAA(count($rows) === 9 && $rows[3]['koreksi'] === '0.10' && $rows[3]['status'] === 'OK', 'Koreksi dua desimal dan keputusan dihitung server.');
$input[3]['hasil'] = '7.01';
$rows = qaFormatAAActual(json_encode($input), $master, '0.5');
assertAA($rows[3]['koreksi'] === '0.01', 'Koreksi kecil tampil sebagai 0.01.');
$input[3]['hasil'] = '8';
$rows = qaFormatAAActual(json_encode($input), $master, '0.5');
assertAA($rows[3]['status'] === 'NOT GOOD', 'Hasil di luar toleransi ditolak.');
assertAA(qaFormatAAActual(json_encode(array_slice($input, 0, 8)), $master, '0.5') === null, 'Sembilan hasil wajib lengkap.');
assertAA(qaFormatAAActual(json_encode([['hasil' => '1; DROP TABLE']]), $master, '0.5') === null, 'Nilai tidak valid ditolak.');
assertAA(qaFormatAAStandards([['cairan_std' => '4', 'standar' => '4']]) === null, 'Tiga titik master wajib lengkap.');
echo "Format AA dan alias migrasi OK\n";

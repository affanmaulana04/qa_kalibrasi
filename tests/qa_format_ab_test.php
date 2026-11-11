<?php

require_once __DIR__ . '/../inc/qa_format_ab.php';
require_once __DIR__ . '/../inc/qa_migrasi_excel.php';

function checkAB(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

checkAB((qaMigAliases()['electric driver'] ?? null) === 'AB', 'Nama alat Electric Driver harus memakai AB.');

$masters = qaFormatABStandards([
    ['skala' => '1', 'standar' => '2.0', 'toleransi' => '0.2'],
    ['skala' => '3', 'standar' => '2.9', 'toleransi' => '0.29']
]);
checkAB($masters !== null, 'Standar AB harus diterima.');
$valid = qaFormatABActual(json_encode([
    ['hasil_searah' => '1.80', 'hasil_berlawanan' => '2.20', 'standar' => '999', 'status' => 'OK'],
    ['hasil_searah' => '2.90', 'hasil_berlawanan' => '']
]), $masters);
checkAB($valid !== null && $valid[0]['range_min'] === '1.80' && $valid[0]['range_max'] === '2.20', 'Range harus ±0.2.');
checkAB($valid[0]['status'] === 'OK' && $valid[1]['status'] === 'OK', 'Batas inklusif, satu arah boleh kosong.');
checkAB($valid[0]['standar'] === '2.0', 'Standar dari browser harus diabaikan.');

$bad = qaFormatABActual(json_encode([
    ['hasil_searah' => '2.21', 'hasil_berlawanan' => ''],
    ['hasil_searah' => '2.90', 'hasil_berlawanan' => '2.60']
]), $masters);
checkAB($bad !== null && $bad[0]['status'] === 'NOT GOOD' && $bad[1]['status'] === 'NOT GOOD', 'Kedua arah harus dipastikan berada dalam range.');
checkAB(qaFormatABActual('[{"hasil_searah":"","hasil_berlawanan":""},{"hasil_searah":"2.9"}]', $masters) === null, 'Hasil kosong harus ditolak.');
checkAB(qaFormatABStandards([['skala' => '1', 'standar' => '2.0', 'toleransi' => '-0.2']]) === null, 'Toleransi negatif harus ditolak.');
checkAB(qaFormatABActual('[{"hasil_searah":"2abc"},{"hasil_searah":"2.9"}]', $masters) === null, 'Hasil bukan angka harus ditolak.');

echo "AB helper dan alias migrasi: OK\n";

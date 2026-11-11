<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Jalankan dari CLI.');
}
require_once __DIR__ . '/../inc/inc_koneksi.php';
require_once __DIR__ . '/../inc/qa_jadwal.php';
try {
    qaSinkronJadwalMendatang($konek);
    echo "Sinkronisasi jadwal mendatang selesai.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Sinkronisasi gagal. Periksa migrasi SQL dan kalender.\n");
    exit(1);
}

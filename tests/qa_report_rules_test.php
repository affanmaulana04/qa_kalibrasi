<?php
require_once __DIR__ . '/../inc/qa_report_rules.php';
function check($condition, $message) {
    if (!$condition)
        throw new RuntimeException($message);
}
check(qaSectionKey(" machining\xc2\xa0  2 ") === 'MACHINING 2', 'Normalisasi seksi');
foreach (["MACHINING\xe2\x80\xaf2", "MACHINING\xe2\x80\x8a2", "MACHINING \xe2\x80\x8c2", "MACHINING \xe2\x81\xa02", "MACHINING\xa02"] as $variant) {
    check(qaSectionKey($variant) === 'MACHINING 2', 'Seksi dari Excel dengan spasi/karakter tak terlihat harus digabung');
}
check(qaMasterResultLabel('OK', 'kalibrasi', 'verifikasi') === 'V', 'Hasil verifikasi harus V');
check(qaMasterResultLabel('OK', 'verifikasi', 'kalibrasi') === 'O', 'Hasil kalibrasi harus O');
check(qaMasterResultLabel('NOT GOOD', 'kalibrasi', 'verifikasi') === 'NOT GOOD', 'Non-OK tidak boleh menjadi V');
check(qaMasterResultLabel('OK', 'periksa', '') === 'OK', 'Jenis lain tidak otomatis O');
$base = ['nama_part' => 'Thread plug gauge', 'status' => 'OK', 'petugas' => 'QA'];
$a = $base + ['no_part' => 'ALAT-1', 'seksi' => 'MACHINING 2', 'plan_date' => '2026-11-05'];
$b = $base + ['no_part' => 'ALAT-2', 'seksi' => ' machining  2 ', 'plan_date' => '2026-11-05'];
$ha = $a + ['actual_date' => '2026-11-05'];
$hb = $b + ['actual_date' => '2026-11-06'];
$groups = qaMonthlyCounts([$a, $b, $a], [$ha, $hb, $ha], '2026-11', 30);
check(count($groups) === 1, 'Seksi sama harus satu kelompok');
$g = reset($groups);
check($g['R'][5] === 2 && $g['R'][6] === 0, 'R tanggal 5 harus 2');
check($g['H'][5] === 1 && $g['H'][6] === 1, 'H tanggal 5/6 harus 1/1');
check($g['totalR'] === 2 && $g['totalH'] === 2, 'Tidak boleh hitung duplikat');
check(count(qaMonthlyCounts([$a, $b], [], '2026-11', 30)) === 1, 'R muncul meskipun belum ada H');
$internal = qaMonthlyCounts([$a, $b], [$hb], '2026-11', 30, 'ALL', 'ALL', true);
$gi = reset($internal);
check($gi['R'][5] === 1 && $gi['H'][6] === 1 && $gi['H'][5] === 0, 'Internal: R tanggal 5 berkurang dan H pindah tanggal 6');
$external = qaMonthlyCounts([$a, $b], [], '2026-11', 30);
$ge = reset($external);
check($ge['R'][5] === 2 && $ge['totalH'] === 0, 'External: jadwal asli tetap dua R tanpa H');
$later = $b;
$later['actual_date'] = '2026-12-01';
$across = qaMonthlyCounts([$a, $b], [$later], '2026-11', 30, 'ALL', 'ALL', true);
$ga = reset($across);
check($ga['R'][5] === 1 && $ga['totalH'] === 0,'H bulan berikutnya tetap menghapus R alat bersangkutan di November');
echo "PASS: seksi, O/V, R internal berpindah ke H aktual, external tetap R.\n";

<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['login'])) {
    header('location: ../../../logout.php');
    exit();
}
require_once __DIR__ . '/../../../inc/inc_koneksi.php';
$isMonthlyReport = isset($monthlyReportOptions);
$isMonthlyPrint = $isMonthlyReport && $monthlyReportOptions['cetak'];
$nama_petugas = isset($_SESSION['nama_lengkap']) && $_SESSION['nama_lengkap'] !== ''
    ? $_SESSION['nama_lengkap'] : ($_SESSION['userid'] ?? '');
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function esc($db, $v) { return mysqli_real_escape_string($db, (string)$v); }
function finalStatusFromTable($table) {
    return [
        'qa_part' => 'OK',
        'qa_alat_ng' => 'NOT GOOD',
        'qa_alat_rusak' => 'RUSAK',
        'qa_alat_discontinue' => 'DISCONTINUE',
        'qa_alat_ditarik' => 'DITARIK TECHNICAL',
    ][$table] ?? '';
}
function originalNoPartFromArchive($no) {
    return preg_replace('/_(?:NOTGOOD|RUSAK|DISCONTINUE|DITARIK)_\d{10,14}$/i','',$no);
}
/* Semua tabel arsip QA dibuat seperti qa_part jika belum tersedia.
   Tidak memakai INFORMATION_SCHEMA/PREPARE agar aman untuk phpMyAdmin lokal. */
if (!$isMonthlyReport) {
foreach (['qa_alat_ng','qa_alat_rusak','qa_alat_discontinue','qa_alat_ditarik'] as $archiveTable) {
    @mysqli_query($konek, "CREATE TABLE IF NOT EXISTS `$archiveTable` LIKE `qa_part`");
}
@mysqli_query($konek, "CREATE TABLE IF NOT EXISTS qa_laporan_transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenis_transaksi VARCHAR(40) NOT NULL,
    periode_key VARCHAR(30) NOT NULL,
    periode_label VARCHAR(120) NOT NULL,
    status_validasi VARCHAR(40) NOT NULL DEFAULT 'menunggu_pemeriksa',
    created_by VARCHAR(100) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    payload_json LONGTEXT NULL,
    UNIQUE KEY uq_jenis_periode (jenis_transaksi, periode_key),
    INDEX idx_status (status_validasi),
    INDEX idx_periode (periode_key)
)");
@mysqli_query($konek, "CREATE TABLE IF NOT EXISTS qa_laporan_validasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    laporan_id INT NOT NULL,
    tahap ENUM('pemeriksa','penyetuju') NOT NULL,
    keputusan ENUM('setuju','tolak') NOT NULL,
    nama_validator VARCHAR(100) NOT NULL,
    keterangan VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_laporan (laporan_id)
)");
}
// ------------------------ FILTER ------------------------
$filter_seksi  = $_GET['seksi'] ?? 'ALL';
$filter_status = $_GET['status'] ?? 'ALL';
$filter_waktu  = $_GET['waktu'] ?? 'daftar';
if (!in_array($filter_waktu, ['daftar','masalah','harian','bulanan','tahunan'], true)) $filter_waktu = 'daftar';
$today = date('Y-m-d');
$val_harian  = $_GET['tgl_harian'] ?? $today;
$val_bulanan = $_GET['tgl_bulanan'] ?? date('Y-m');
$val_tahunan = $_GET['tgl_tahunan'] ?? date('Y');
if ($isMonthlyReport) {
    $filter_seksi = $monthlyReportOptions['seksi'];
    $filter_status = 'ALL';
    $filter_waktu = 'bulanan';
    $val_harian = $today;
    $val_tahunan = (string)$monthlyReportOptions['tahun'];
    $val_bulanan = sprintf('%04d-%02d', $monthlyReportOptions['tahun'], $monthlyReportOptions['bulan']);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $val_harian)) $val_harian = $today;
if (!preg_match('/^\d{4}-\d{2}$/', $val_bulanan)) $val_bulanan = date('Y-m');
if (!preg_match('/^\d{4}$/', $val_tahunan)) $val_tahunan = date('Y');
$selectedYear = (int)substr($val_bulanan, 0, 4);
$selectedMonth = (int)substr($val_bulanan, 5, 2);
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
/* Hari libur Indonesia yang ditandai sebagai tanggal merah/cuti bersama.
 * Sabtu dan Minggu otomatis ditandai untuk semua tahun.
 * Daftar 2026 mengikuti SKB 3 Menteri Tahun 2026.
 */
$holidayDates = [
    '2026-01-01' => 'Tahun Baru 2026',
    '2026-01-16' => 'Isra Mikraj Nabi Muhammad SAW',
    '2026-02-16' => 'Cuti Bersama Tahun Baru Imlek',
    '2026-02-17' => 'Tahun Baru Imlek 2577 Kongzili',
    '2026-03-18' => 'Cuti Bersama Hari Suci Nyepi',
    '2026-03-19' => 'Hari Suci Nyepi',
    '2026-03-20' => 'Cuti Bersama Idul Fitri',
    '2026-03-21' => 'Idul Fitri 1447 H',
    '2026-03-22' => 'Idul Fitri 1447 H',
    '2026-03-23' => 'Cuti Bersama Idul Fitri',
    '2026-03-24' => 'Cuti Bersama Idul Fitri',
    '2026-04-03' => 'Wafat Yesus Kristus',
    '2026-04-05' => 'Kebangkitan Yesus Kristus (Paskah)',
    '2026-05-01' => 'Hari Buruh Internasional',
    '2026-05-14' => 'Kenaikan Yesus Kristus',
    '2026-05-15' => 'Cuti Bersama Kenaikan Yesus Kristus',
    '2026-05-27' => 'Idul Adha 1447 H',
    '2026-05-28' => 'Cuti Bersama Idul Adha',
    '2026-05-31' => 'Hari Raya Waisak 2570 BE',
    '2026-06-01' => 'Hari Lahir Pancasila',
    '2026-06-16' => 'Tahun Baru Islam 1448 H',
    '2026-08-17' => 'Proklamasi Kemerdekaan RI',
    '2026-08-25' => 'Maulid Nabi Muhammad SAW',
    '2026-12-24' => 'Cuti Bersama Natal',
    '2026-12-25' => 'Hari Raya Natal',
    '2027-01-01' => 'Tahun Baru 2027',
    '2027-01-05' => 'Isra Mikraj Nabi Muhammad SAW',
    '2027-02-05' => 'Cuti Bersama Tahun Baru Imlek',
    '2027-02-06' => 'Tahun Baru Imlek 2578 Kongzili',
    '2027-03-08' => 'Hari Suci Nyepi',
    '2027-03-09' => 'Cuti Bersama Idul Fitri',
    '2027-03-10' => 'Idul Fitri 1448 H',
    '2027-03-11' => 'Idul Fitri 1448 H',
    '2027-03-12' => 'Cuti Bersama Idul Fitri',
    '2027-03-15' => 'Cuti Bersama Idul Fitri',
    '2027-03-25' => 'Cuti Bersama Wafat Yesus Kristus',
    '2027-03-26' => 'Wafat Yesus Kristus',
    '2027-03-28' => 'Kebangkitan Yesus Kristus (Paskah)',
    '2027-05-01' => 'Hari Buruh Internasional',
    '2027-05-06' => 'Kenaikan Yesus Kristus',
    '2027-05-17' => 'Idul Adha 1448 H',
    '2027-05-18' => 'Cuti Bersama Idul Adha',
    '2027-05-19' => 'Cuti Bersama Hari Raya Waisak',
    '2027-05-20' => 'Hari Raya Waisak 2571 BE',
    '2027-06-01' => 'Hari Lahir Pancasila',
    '2027-06-06' => 'Tahun Baru Islam 1449 H',
    '2027-08-15' => 'Maulid Nabi Muhammad SAW',
    '2027-08-17' => 'Proklamasi Kemerdekaan RI',
    '2027-12-24' => 'Cuti Bersama Natal',
    '2027-12-25' => 'Hari Raya Natal',
    '2027-12-26' => 'Isra Mikraj Nabi Muhammad SAW'
];
// CUSTOM TANGGAL MERAH PERUSAHAAN
// Tambahkan tanggal yang diatur Admin tanpa mengubah daftar hari libur bawaan di atas.
// Data custom dengan tanggal yang sama akan memakai keterangan dari qa_tanggal_merah.
$customHolidayStart = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);
$customHolidayEnd = date('Y-m-t', strtotime($customHolidayStart));
$qCustomHoliday = false;
// Custom holidays are optional; report viewing must not create database tables.
$customHolidayTable = mysqli_query($konek, "SHOW TABLES LIKE 'qa\\_tanggal\\_merah'");
if ($customHolidayTable && mysqli_num_rows($customHolidayTable) > 0) {
$qCustomHoliday = mysqli_query(
    $konek,
    "SELECT tanggal, keterangan
     FROM qa_tanggal_merah
     WHERE tanggal BETWEEN '$customHolidayStart' AND '$customHolidayEnd'
     ORDER BY tanggal ASC"
);
}
if ($qCustomHoliday) {
    while ($customHoliday = mysqli_fetch_assoc($qCustomHoliday)) {
        $customDate = substr((string)($customHoliday['tanggal'] ?? ''), 0, 10);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $customDate)) {
            $customLabel = trim((string)($customHoliday['keterangan'] ?? ''));
            $holidayDates[$customDate] = $customLabel !== '' ? $customLabel : 'Tanggal Merah Perusahaan';
        }
    }
}
function holidayClassForDate($date, $holidayDates) {
    $ts = strtotime($date);
    $isWeekend = $ts !== false && (int)date('N', $ts) >= 6;
    return ($isWeekend || isset($holidayDates[$date])) ? 'holiday-col' : '';
}
function holidayTitleForDate($date, $holidayDates) {
    if (isset($holidayDates[$date])) return $holidayDates[$date];
    $ts = strtotime($date);
    if ($ts !== false) {
        $n = (int)date('N', $ts);
        if ($n === 6) return 'Sabtu';
        if ($n === 7) return 'Minggu';
    }
    return '';
}
$statusOptions = ['ALL'=>'Semua Kondisi','OK'=>'OK (Aktif)','NOT GOOD'=>'NOT GOOD','RUSAK'=>'RUSAK','DISCONTINUE'=>'DISCONTINUE','DITARIK TECHNICAL'=>'DITARIK TECHNICAL','MENUNGGU VALIDASI'=>'Menunggu Validasi','DITOLAK VALIDASI'=>'Ditolak Validasi'];
$q_seksi = mysqli_query($konek, "SELECT nama_seksi FROM qa_seksi_master ORDER BY nama_seksi ASC");
$seksiOptions = [];
if ($q_seksi) while ($s = mysqli_fetch_assoc($q_seksi)) $seksiOptions[] = $s['nama_seksi'];
/* ------------------------ MASTER ALAT ------------------------
 * Satu sumber daftar alat: qa_part + empat tabel arsip.
 * Petugas diambil dari user yang melakukan registrasi awal
 * (riwayat nomor QA-REG-...), karena qa_part tidak menyimpan created_by.
 */
$masterRows = [];
$tables = ['qa_part','qa_alat_ng','qa_alat_rusak','qa_alat_discontinue','qa_alat_ditarik'];
foreach ($tables as $t) {
    $status = finalStatusFromTable($t);
    $sql = "SELECT p.*,
            (SELECT q0.created_by FROM qa_pengecekan q0
             WHERE q0.no_part = p.no_part AND q0.nomor_pengecekan LIKE 'QA-REG-%'
             ORDER BY q0.id ASC LIMIT 1) AS petugas_daftar
            FROM `$t` p ORDER BY p.seksi_pemilik ASC, p.nama_part ASC, p.no_part ASC";
    $q = mysqli_query($konek, $sql);
    if (!$q) continue;
    while ($r = mysqli_fetch_assoc($q)) {
        // Untuk arsip, created_by registrasi awal masih berada di histori dengan nomor asli.
        if (empty($r['petugas_daftar']) && $t !== 'qa_part') {
            $orig = originalNoPartFromArchive((string)$r['no_part']);
            $origEsc = esc($konek,$orig);
            $qReg = mysqli_query($konek, "SELECT created_by FROM qa_pengecekan WHERE no_part='$origEsc' AND nomor_pengecekan LIKE 'QA-REG-%' ORDER BY id ASC LIMIT 1");
            if ($qReg && ($rr=mysqli_fetch_assoc($qReg))) $r['petugas_daftar']=$rr['created_by'];
        }
        $r['_table'] = $t;
        $r['_final_status'] = $status;
        $masterRows[] = $r;
    }
}
/* Latest validation/result per nomor alat. */
$latestByNo = [];
$approvedByNo = [];
$qLatest = mysqli_query($konek, "SELECT q.* FROM qa_pengecekan q
    INNER JOIN (SELECT no_part, MAX(id) max_id FROM qa_pengecekan GROUP BY no_part) z
        ON z.no_part=q.no_part AND z.max_id=q.id");
if ($qLatest) {
    while ($q = mysqli_fetch_assoc($qLatest)) $latestByNo[$q['no_part']] = $q;
}
$qApproved = mysqli_query($konek, "SELECT q.* FROM qa_pengecekan q
    INNER JOIN (SELECT no_part, MAX(id) max_id FROM qa_pengecekan WHERE status_validasi='final_disetujui' GROUP BY no_part) z
        ON z.no_part=q.no_part AND z.max_id=q.id");
if ($qApproved) {
    while ($q = mysqli_fetch_assoc($qApproved)) $approvedByNo[$q['no_part']] = $q;
}
/* Registry = sumber kebenaran status nomor alat. Ini penting untuk legacy
 * seperti nomor yang masih ada di qa_part tetapi sudah final RUSAK/NOT GOOD. */
$registryByNo = [];
$qRegistry = mysqli_query($konek, "SELECT no_part,status_alat,alasan_status,hasil_terakhir FROM qa_alat_registry");
if ($qRegistry) {
    while ($r = mysqli_fetch_assoc($qRegistry)) $registryByNo[strtoupper(trim((string)$r['no_part']))] = $r;
}
function displayMasterStatus($master, $latestByNo, $registryByNo = []) {
    $no = strtoupper(trim((string)($master['no_part'] ?? '')));
    $final = strtoupper(trim((string)($master['_final_status'] ?? '')));
    // MASTER NON-OK V2 - BARIS DARI TABEL ARSIP ADALAH SUMBER STATUS FINAL
    // Jangan ditimpa menjadi OK hanya karena registry legacy masih AKTIF.
    if (($master['_table'] ?? 'qa_part') !== 'qa_part' && in_array($final, ['NOT GOOD','RUSAK','DISCONTINUE','DITARIK TECHNICAL'], true)) return $final;
    $latest = $latestByNo[$master['no_part']] ?? ($latestByNo[$no] ?? null);
    $registry = $registryByNo[$no] ?? null;
    if ($latest) {
        $sv = strtolower((string)($latest['status_validasi'] ?? ''));
        if ($sv === 'menunggu_pemeriksa' || $sv === 'menunggu_penyetuju') return 'MENUNGGU VALIDASI';
        if ($sv === 'ditolak_pemeriksa' || $sv === 'ditolak_penyetuju') return 'DITOLAK VALIDASI';
    }
    if ($registry) {
        $rs = strtoupper(trim((string)($registry['status_alat'] ?? '')));
        if ($rs === 'BLOKIR_PERMANEN') return strtoupper((string)($registry['alasan_status'] ?: ($registry['hasil_terakhir'] ?: 'RUSAK')));
        if ($rs === 'NONAKTIF_SEMENTARA') return strtoupper((string)($registry['alasan_status'] ?: ($registry['hasil_terakhir'] ?: 'NONAKTIF')));
        if ($rs === 'AKTIF' && $latest && strtolower((string)($latest['status_validasi'] ?? '')) === 'final_disetujui') return 'OK';
    }
    return $final !== '' ? $final : 'OK';
}
function statusMatches($rowStatus, $filterStatus) {
    return $filterStatus === 'ALL' || strtoupper($rowStatus) === strtoupper($filterStatus);
}
function isNegativeMasterStatus($status) {
    $s = strtoupper(trim((string)$status));
    return in_array($s, ['RUSAK','DISCONTINUE','DITARIK TECHNICAL','NOT GOOD'], true);
}
function masterColValue($row, $keys, $fallback='') {
    foreach ((array)$keys as $k) {
        if (isset($row[$k]) && trim((string)$row[$k]) !== '') return $row[$k];
    }
    return $fallback;
}
// MASTER NON-OK V2 - DEDUPE NOMOR ALAT, PRIORITASKAN DATA ARSIP NON-OK
// Jika data legacy masih tersisa di qa_part sekaligus sudah ada di tabel arsip, tampilkan arsip saja.
$dedupMaster = [];
foreach ($masterRows as $r) {
    $key = strtoupper(trim((string)($r['no_part'] ?? '')));
    if ($key === '') continue;
    $isArchive = (($r['_table'] ?? 'qa_part') !== 'qa_part');
    if (!isset($dedupMaster[$key]) || $isArchive) $dedupMaster[$key] = $r;
}
$masterRows = array_values($dedupMaster);
$filteredMasters = [];
foreach ($masterRows as $r) {
    $r['_display_status'] = displayMasterStatus($r, $latestByNo, $registryByNo);
    if ($filter_seksi !== 'ALL' && strtoupper((string)$r['seksi_pemilik']) !== strtoupper((string)$filter_seksi)) continue;
    if (!statusMatches($r['_display_status'], $filter_status)) continue;
    $filteredMasters[] = $r;
}
/* ------------------------ REPORT EVENT SOURCES ------------------------
 * Rencana (R): current active schedule + historical schedule date from QA checks.
 * Aktual (H): only after BOTH validators approve (final_disetujui).
 * This avoids filling H when an operator has only submitted the result.
 */
function buildPlanEvents($konek, $masterByNo, $latestByNo) {
    $events = [];
    foreach ($masterByNo as $no => $m) {
        if (($m['_table'] ?? '') === 'qa_part' && !empty($m['jadwal_kalibrasi'])) {
            $d = substr((string)$m['jadwal_kalibrasi'],0,10);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                $events[$no.'|'.$d] = [
                    'no_part'=>$no, 'nama_part'=>$m['nama_part'], 'seksi'=>$m['seksi_pemilik'],
                    'spesifikasi'=>$m['spesifikasi'] ?? ($m['ukuran'] ?? ''), 'resolusi'=>$m['resolusi'] ?? '',
                    'periode'=>$m['periode_kalibrasi'] ?? '', 'plan_date'=>$d, 'status'=>$m['_final_status'],
                    'petugas'=>$m['petugas_daftar'] ?? '-'
                ];
            }
        }
    }
    $q = mysqli_query($konek, "SELECT q.* FROM qa_pengecekan q WHERE q.tanggal_jadwal_saat_pengecekan IS NOT NULL AND q.tanggal_jadwal_saat_pengecekan <> '' ORDER BY q.id ASC");
    if ($q) {
        while ($x = mysqli_fetch_assoc($q)) {
            $d = substr((string)$x['tanggal_jadwal_saat_pengecekan'],0,10);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) continue;
            $no = $x['no_part'] ?? '';
            if (!isset($masterByNo[$no])) continue;
            $m = $masterByNo[$no];
            $events[$no.'|'.$d] = [
                'no_part'=>$no, 'nama_part'=>$m['nama_part'], 'seksi'=>$m['seksi_pemilik'],
                'spesifikasi'=>$m['spesifikasi'] ?? ($m['ukuran'] ?? ''), 'resolusi'=>$m['resolusi'] ?? '',
                'periode'=>$m['periode_kalibrasi'] ?? '', 'plan_date'=>$d, 'status'=>$m['_final_status'],
                'petugas'=>$m['petugas_daftar'] ?? '-'
            ];
        }
    }
    return array_values($events);
}
$masterByNo = [];
foreach ($masterRows as $r) $masterByNo[(string)$r['no_part']] = $r;
$planEvents = buildPlanEvents($konek, $masterByNo, $latestByNo);
$actualEvents = [];
$qActual = mysqli_query($konek, "SELECT q.* FROM qa_pengecekan q WHERE COALESCE(q.jenis_transaksi,'update_hasil_kalibrasi') <> 'registrasi_alat' AND (q.nomor_pengecekan IS NULL OR q.nomor_pengecekan NOT LIKE 'QA-REG-%') ORDER BY q.tanggal_pengecekan ASC, q.id ASC");
if ($qActual) {
    while ($q = mysqli_fetch_assoc($qActual)) {
        $no = (string)($q['no_part'] ?? '');
        if (!isset($masterByNo[$no])) continue;
        $m = $masterByNo[$no];
        $actualEvents[] = [
            'no_part'=>$no,
            'nama_part'=>$m['nama_part'], 'seksi'=>$m['seksi_pemilik'],
            'spesifikasi'=>$m['spesifikasi'] ?? ($m['ukuran'] ?? ''), 'resolusi'=>$m['resolusi'] ?? '',
            'periode'=>$m['periode_kalibrasi'] ?? '',
            'plan_date'=>substr((string)$q['tanggal_jadwal_saat_pengecekan'],0,10),
            'actual_date'=>substr((string)$q['tanggal_pengecekan'],0,10),
            'status'=>$m['_final_status'],
            'petugas'=>$m['petugas_daftar'] ?? '-',
            'hasil'=>$m['_final_status'],
            'next_date'=>$q['periode_berikutnya'] ?? ($m['jadwal_kalibrasi'] ?? '')
        ];
    }
}
$reportRows = [];
$reportMeta = null;
if ($filter_waktu === 'harian') {
    $daily = [];
    foreach ($planEvents as $e) {
        if ($e['plan_date'] !== $val_harian) continue;
        if ($filter_seksi !== 'ALL' && strtoupper($e['seksi']) !== strtoupper($filter_seksi)) continue;
        if (!statusMatches(displayMasterStatus($masterByNo[$e['no_part']] ?? ['_final_status'=>$e['status'],'no_part'=>$e['no_part']], $latestByNo, $registryByNo), $filter_status)) continue;
        $daily[$e['no_part']] = [
            'no_part'=>$e['no_part'], 'nama_part'=>$e['nama_part'], 'spesifikasi'=>$e['spesifikasi'], 'resolusi'=>$e['resolusi'],
            'seksi'=>$e['seksi'], 'periode'=>$e['periode'], 'rencana'=>$e['plan_date'], 'aktual'=>'', 'hasil'=>'', 'next'=>'', 'petugas'=>$e['petugas'], 'status'=>$e['status']
        ];
    }
    foreach ($actualEvents as $e) {
        if ($e['actual_date'] !== $val_harian) continue;
        if ($filter_seksi !== 'ALL' && strtoupper($e['seksi']) !== strtoupper($filter_seksi)) continue;
        if (!statusMatches($e['status'], $filter_status)) continue;
        if (!isset($daily[$e['no_part']])) {
            $daily[$e['no_part']] = [
                'no_part'=>$e['no_part'], 'nama_part'=>$e['nama_part'], 'spesifikasi'=>$e['spesifikasi'], 'resolusi'=>$e['resolusi'],
                'seksi'=>$e['seksi'], 'periode'=>$e['periode'], 'rencana'=>$e['plan_date'], 'aktual'=>'', 'hasil'=>'', 'next'=>'', 'petugas'=>$e['petugas'], 'status'=>$e['status']
            ];
        }
        $daily[$e['no_part']]['aktual']=$e['actual_date'];
        $daily[$e['no_part']]['hasil']=$e['hasil'];
        $daily[$e['no_part']]['next']=$e['next_date'];
    }
    $reportRows = array_values($daily);
    usort($reportRows, fn($a,$b)=>strtoupper($a['seksi'].$a['nama_part'].$a['no_part']) <=> strtoupper($b['seksi'].$b['nama_part'].$b['no_part']));
    $reportMeta = ['jenis'=>'laporan_harian','periode_key'=>$val_harian,'periode_label'=>'Harian '.$val_harian];
} elseif ($filter_waktu === 'bulanan') {
    /*
     * HASIL MEETING - JADWAL BULANAN
     * R dan H hanya muncul setelah Update Hasil Kalibrasi dilakukan.
     * Tanggal R selalu mengikuti tanggal aktual kalibrasi, sehingga R dan H tidak pernah beda tanggal.
     * Registrasi alat tidak membentuk Rencana pada jadwal bulanan.
     */
    $groups = [];
    $actualSeen = [];
    $latestCalibrationNextByNo = [];

    foreach ($actualEvents as $e) {
        $no = (string)($e['no_part'] ?? '');
        $actualDate = substr((string)($e['actual_date'] ?? ''), 0, 10);
        $nextDate = substr((string)($e['next_date'] ?? ''), 0, 10);

        if ($no !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $nextDate)) {
            // actualEvents sudah berurutan berdasarkan tanggal/id; nilai terakhir menjadi jadwal berikut terbaru.
            $latestCalibrationNextByNo[$no] = $nextDate;
        }

        if (substr($actualDate, 0, 7) !== $val_bulanan) continue;
        if ($filter_seksi !== 'ALL' && strtoupper($e['seksi']) !== strtoupper($filter_seksi)) continue;
        if (!statusMatches($e['status'], $filter_status)) continue;

        // Satu nomor alat dihitung satu kali per tanggal walaupun ada penyimpanan/update ulang di hari yang sama.
        $actualKey = strtoupper($no).'|'.$actualDate;
        if (isset($actualSeen[$actualKey])) continue;
        $actualSeen[$actualKey] = true;

        $gk = strtoupper(trim($e['seksi'])).'|'.strtoupper(trim($e['nama_part']));
        if (!isset($groups[$gk])) {
            $groups[$gk] = [
                'seksi'=>$e['seksi'],
                'nama_part'=>$e['nama_part'],
                'petugas'=>[],
                'R'=>array_fill(1,$daysInMonth,0),
                'H'=>array_fill(1,$daysInMonth,0)
            ];
        }

        $d = (int)substr($actualDate, 8, 2);
        if ($d >= 1 && $d <= $daysInMonth) {
            $groups[$gk]['R'][$d]++;
            $groups[$gk]['H'][$d]++;
        }
        if (($e['petugas'] ?? '-') !== '-') $groups[$gk]['petugas'][] = $e['petugas'];
    }

    foreach ($groups as &$g) {
        $g['totalR'] = array_sum($g['R']);
        $g['totalH'] = array_sum($g['H']);
        $g['petugas'] = implode(', ', array_values(array_unique(array_filter($g['petugas']))));
    }
    unset($g);
    uasort($groups, fn($a,$b)=>strtoupper($a['seksi'].$a['nama_part']) <=> strtoupper($b['seksi'].$b['nama_part']));
    $reportRows = $groups;

    /*
     * Informasi rencana kalibrasi masa depan hanya ditampilkan untuk tahun depan.
     * Data diringkas per seksi saja, tanpa nama alat dan tanpa menentukan tanggal kerja.
     * Hanya alat yang sudah pernah Update Hasil Kalibrasi yang masuk perhitungan.
     */
    $showNextYearDueInfo = $selectedYear === ((int)date('Y') + 1);
    $dueGroups = [];

    if ($showNextYearDueInfo) {
        foreach ($masterByNo as $no => $m) {
            if (($m['_table'] ?? '') !== 'qa_part') continue;
            if (!isset($latestCalibrationNextByNo[$no])) continue;

            $nextDate = $latestCalibrationNextByNo[$no];
            if (substr($nextDate, 0, 7) !== $val_bulanan) continue;
            if ($filter_seksi !== 'ALL' && strtoupper((string)$m['seksi_pemilik']) !== strtoupper($filter_seksi)) continue;

            $display = displayMasterStatus($m, $latestByNo, $registryByNo);
            if (!statusMatches($display, $filter_status)) continue;

            $gk = strtoupper(trim((string)$m['seksi_pemilik']));
            if (!isset($dueGroups[$gk])) {
                $dueGroups[$gk] = [
                    'seksi'=>(string)$m['seksi_pemilik'],
                    'jumlah'=>0
                ];
            }
            $dueGroups[$gk]['jumlah']++;
        }
    }

    uasort($dueGroups, fn($a,$b)=>strtoupper($a['seksi']) <=> strtoupper($b['seksi']));
    $monthlyDueRows = array_values($dueGroups);
    $monthlyDueTotal = array_sum(array_column($monthlyDueRows, 'jumlah'));

    $reportMeta=['jenis'=>'laporan_bulanan','periode_key'=>$val_bulanan,'periode_label'=>'Bulanan '.date('F Y',strtotime($val_bulanan.'-01'))];
} elseif ($filter_waktu === 'tahunan') {
    $year=(int)$val_tahunan;
    $groups=[]; $rSeen=[];
    foreach ($planEvents as $e) {
        if ((int)substr($e['plan_date'],0,4)!==$year) continue;
        if ($filter_seksi !== 'ALL' && strtoupper($e['seksi']) !== strtoupper($filter_seksi)) continue;
        $display=displayMasterStatus($masterByNo[$e['no_part']] ?? ['_final_status'=>$e['status'],'no_part'=>$e['no_part']], $latestByNo, $registryByNo);
        if (!statusMatches($display,$filter_status)) continue;
        $gk=strtoupper(trim($e['seksi'])).'|'.strtoupper(trim($e['nama_part']));
        $key=$gk.'|'.$e['no_part'].'|'.$e['plan_date']; if(isset($rSeen[$key])) continue; $rSeen[$key]=true;
        if(!isset($groups[$gk])) $groups[$gk]=['seksi'=>$e['seksi'],'nama_part'=>$e['nama_part'],'R'=>array_fill(1,12,0),'H'=>array_fill(1,12,0),'petugas'=>[]];
        $m=(int)substr($e['plan_date'],5,2); if($m>=1&&$m<=12)$groups[$gk]['R'][$m]++;
        if(($e['petugas']??'-')!=='-')$groups[$gk]['petugas'][]=$e['petugas'];
    }
    foreach($actualEvents as $e){
        if((int)substr($e['actual_date'],0,4)!==$year)continue;
        if($filter_seksi!=='ALL'&&strtoupper($e['seksi'])!==strtoupper($filter_seksi))continue;
        if(!statusMatches($e['status'],$filter_status))continue;
        $gk=strtoupper(trim($e['seksi'])).'|'.strtoupper(trim($e['nama_part']));
        if(!isset($groups[$gk]))$groups[$gk]=['seksi'=>$e['seksi'],'nama_part'=>$e['nama_part'],'R'=>array_fill(1,12,0),'H'=>array_fill(1,12,0),'petugas'=>[]];
        $m=(int)substr($e['actual_date'],5,2);if($m>=1&&$m<=12)$groups[$gk]['H'][$m]++;
        if(($e['petugas']??'-')!=='-')$groups[$gk]['petugas'][]=$e['petugas'];
    }
    foreach($groups as &$g){$g['B']=array_fill(1,12,0);for($m=1;$m<=12;$m++)$g['B'][$m]=max(0,(int)$g['R'][$m]-(int)$g['H'][$m]);$g['totalR']=array_sum($g['R']);$g['totalH']=array_sum($g['H']);$g['totalB']=array_sum($g['B']);$g['petugas']=implode(', ',array_values(array_unique(array_filter($g['petugas']))));}
    unset($g);
    uasort($groups,fn($a,$b)=>strtoupper($a['seksi'].$a['nama_part'])<=>strtoupper($b['seksi'].$b['nama_part']));
    $reportRows=$groups;
    $reportMeta=['jenis'=>'laporan_tahunan','periode_key'=>(string)$year,'periode_label'=>'Tahunan '.$year];
}
// Normalisasi data untuk daftar default.
$masterRows = array_values(array_filter($filteredMasters, function($r){ return trim((string)($r['no_part'] ?? '')) !== ''; }));
usort($masterRows, function($a,$b){return strtoupper(($a['seksi_pemilik']??'').($a['nama_part']??'').($a['no_part']??''))<=>strtoupper(($b['seksi_pemilik']??'').($b['nama_part']??'').($b['no_part']??''));});
/* Daftar khusus alat bermasalah/non-OK. Status workflow ditolak/menunggu tidak ikut. */
$problemRows = [];
foreach ($masterRows as $r) {
    $st = strtoupper(trim((string)($r['_display_status'] ?? '')));
    if ($st === '' || $st === 'OK' || $st === 'REGIST' || $st === 'MENUNGGU VALIDASI' || $st === 'DITOLAK VALIDASI' || strpos($st,'DITOLAK') === 0) continue;
    $lookupNo = (string)($r['no_part'] ?? '');
    if (($r['_table'] ?? '') !== 'qa_part') $lookupNo = originalNoPartFromArchive($lookupNo);
    $last = $approvedByNo[$lookupNo] ?? ($approvedByNo[strtoupper($lookupNo)] ?? null);
    $r['_last_calibration'] = $last ? substr((string)($last['tanggal_pengecekan'] ?? ''),0,10) : '';
    $problemRows[] = $r;
}
usort($problemRows, function($a,$b){return strtoupper(($a['seksi_pemilik']??'').($a['nama_part']??'').($a['no_part']??''))<=>strtoupper(($b['seksi_pemilik']??'').($b['nama_part']??'').($b['no_part']??''));});
// MASTER NON-OK V2 - TABEL ARSIP TAMPIL + TOTAL SELURUH ALAT NON-OK / BERMASALAH
$totalAlatOff = count($problemRows);
if ($isMonthlyReport) {
    // Include sections present in legacy equipment data as well as the section master.
    foreach ($masterByNo as $master) {
        $section = trim((string)($master['seksi_pemilik'] ?? ''));
        if ($section !== '' && !in_array($section, $seksiOptions, true)) $seksiOptions[] = $section;
    }
    natcasesort($seksiOptions);
    if ($filter_seksi !== 'ALL' && !in_array($filter_seksi, $seksiOptions, true)) {
        $monthlyReportOptions['errors'][] = 'Seksi tidak ditemukan. Pilih seksi yang tersedia.';
    }
    if ($monthlyReportOptions['errors']) {
        http_response_code(400);
        $reportRows = [];
    }
    $monthNames = [1=>'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $monthlyTitle = $monthNames[$selectedMonth].' '.$selectedYear;
    $monthlyDisplayFormat = isset($_GET['format']) && in_array((string)$_GET['format'], ['1','2','3'], true) ? (string)$_GET['format'] : '1';
    $monthlyPlanRows = [];
    if (!$monthlyReportOptions['errors']) {
        foreach ($planEvents as $event) {
            if (substr($event['plan_date'], 0, 7) !== $val_bulanan) continue;
            if ($filter_seksi !== 'ALL' && strcasecmp($event['seksi'], $filter_seksi) !== 0) continue;
            $monthlyPlanRows[] = $event;
        }
    }
    usort($monthlyPlanRows, function($a, $b) {
        return strcasecmp($a['seksi'], $b['seksi'])
            ?: strcmp($a['plan_date'], $b['plan_date'])
            ?: strcasecmp($a['nama_part'], $b['nama_part'])
            ?: strnatcasecmp($a['no_part'], $b['no_part']);
    });
    if ($isMonthlyPrint) {
        require __DIR__ . '/../jadwal_bulanan/cetak_jadwal_bulanan_format1.php';
        return;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>
            <?=$isMonthlyReport ? 'Laporan Jadwal Bulanan' : 'Master Data'?> | QA System
        </title>
        <link rel="icon" type="image/png" href="/qa_kalibrasi/images/favicon-toto-qa.png?v=1">
        <link href="../../../css/bootstrap.min.css" rel="stylesheet">
        <link href="../../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet">
        <style>
            body{padding-top:80px;background:#fff}.filter-box{background:#fff;border:1px solid #ddd;border-radius:4px;padding:20px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
            .table-wrap{overflow:auto}.table-jadwal{width:100%;border-collapse:collapse;border:1px solid #aaa;font-family:Arial,sans-serif;font-size:12px}.table-jadwal th{border:1px solid #aaa;padding:7px;text-align:center;vertical-align:middle;background:#e2e6ea;color:#000;font-weight:bold;white-space:nowrap}.table-jadwal td{border:1px solid #aaa;padding:6px;vertical-align:middle;text-align:center;color:#000}.text-left{text-align:left!important}.tr-seksi{background:#d6d8db;font-weight:bold;border-top:2px solid #555}.r-row{background:#fff9e6}.h-row{background:#eef8ee}.b-row{background:#fdecec}.negative-row td{background:#f4cccc!important;color:#990000!important;font-weight:600}.holiday-col,.table-jadwal th.holiday-col,.table-jadwal td.holiday-col{background:rgba(220,53,69,.50)!important;color:#000!important}.mini-badge{font-size:10px;padding:3px 6px;border-radius:3px;display:inline-block}.btn-validate-report{margin-left:8px}.report-status{font-size:12px;font-weight:bold}.sticky-first{position:sticky;left:0;background:#fff;z-index:2}.sticky-second{position:sticky;left:42px;background:#fff;z-index:2}@media print{.no-print{display:none!important}.print-area{display:block!important}.table-jadwal{font-size:9px}.table-jadwal th,.table-jadwal td{padding:3px}}
            .print-area{display:none}
        </style>
        <?php if ($isMonthlyReport): ?>
        <style>
            body{background:#f4f6f9}
            .monthly-controls h3{margin-top:0;font-weight:700;color:#1e3f66}
            .monthly-filters,.monthly-print-controls{display:flex;flex-wrap:wrap;align-items:flex-end;gap:12px}
            .monthly-filters .form-group{margin:0;min-width:150px}
            .monthly-filters .form-group:first-child{min-width:240px}
            .monthly-filters .btn,.monthly-print-controls .btn{height:34px}
            .monthly-print-controls{border-top:1px solid #e5e7eb;margin-top:18px;padding-top:16px}
            #monthlyPrintHelp{margin:10px 0 0}
            #monthlyFilterNotice{margin-top:10px}
            .monthly-sheet{background:#fff;border:1px solid #ddd;border-radius:4px;padding:20px;margin-bottom:25px}
            .monthly-heading{text-align:center;margin-bottom:18px}
            .monthly-heading h2{font-size:20px;font-weight:700;margin:8px 0}
            .monthly-sheet .table-jadwal{min-width:1200px}
            .monthly-sheet [hidden]{display:none!important}
            .monthly-format1 .section-table{width:100%;border-collapse:collapse;table-layout:fixed;margin-bottom:18px;font-size:14px}
            .monthly-format1 .section-table th,.monthly-format1 .section-table td{border:1px solid #000;padding:10px 8px;vertical-align:middle;overflow-wrap:anywhere}
            .monthly-format1 .section-table th{text-align:center;font-weight:normal;height:48px}
            .monthly-format1 .section-table .section-label{text-align:left;height:auto;padding:8px}
            .monthly-format1 .number,.monthly-format1 .planned-date{text-align:center}
            .monthly-format1 .planned-date{font-weight:bold;-webkit-print-color-adjust:exact;print-color-adjust:exact}
            .monthly-format1 .planned-date.status-overdue{background:#f8d7da;color:#842029}
            .monthly-format1 .planned-date.status-today{background:#cfe2ff;color:#084298}
            .monthly-format1 .planned-date.status-scheduled{background:#d1e7dd;color:#0f5132}
            .monthly-format1 .notice{padding:12px;border:1px solid #bbb}
            .monthly-format1 .error{color:#a94442}
            @media(max-width:600px){
                .monthly-report-page>.container-fluid{padding:0 12px!important}
                .monthly-filters .form-group,.monthly-filters .form-group:first-child{width:100%;min-width:0}
                .monthly-print-controls>div{width:100%}
                .monthly-sheet{padding:12px}
            }
            @page{size:A4 portrait;margin:0}
            @media print{
                html,body{margin:0!important;padding:0!important;background:#fff;color:#000}
                .no-print{display:none!important}
                .monthly-report-page>.container-fluid{padding:0!important}
                .monthly-sheet{padding:12mm;margin:0;border:0;border-radius:0;box-sizing:border-box}
                .monthly-heading{margin-bottom:4mm;break-after:avoid}
                .monthly-heading h2{font-size:12pt}
                .monthly-heading p{margin:0 0 2mm}
                .monthly-sheet .table-wrap{overflow:visible}
                .monthly-sheet .table-jadwal{width:100%;min-width:0;table-layout:fixed;font-size:7pt}
                .monthly-sheet .table-jadwal th,.monthly-sheet .table-jadwal td{padding:1mm .4mm;border-color:#555;white-space:normal;overflow-wrap:anywhere;font-size:7pt!important}
                .monthly-sheet thead{display:table-header-group}
                .monthly-sheet .table-jadwal thead tr:nth-child(2) th{font-size:6pt!important;white-space:nowrap}
                .monthly-sheet tr{break-inside:avoid;page-break-inside:avoid}
                .monthly-sheet .r-row,.monthly-sheet .tr-seksi{break-after:avoid;page-break-after:avoid}
                .monthly-sheet .table-jadwal thead tr:first-child th:nth-child(1){width:3%}
                .monthly-sheet .table-jadwal thead tr:first-child th:nth-child(2){width:14%}
                .monthly-sheet .table-jadwal thead tr:first-child th:nth-child(3){width:7%}
                .monthly-sheet .table-jadwal thead tr:first-child th:nth-child(4){width:3%}
                .monthly-sheet .table-jadwal thead tr:first-child th:nth-last-child(2){width:5%}
                .monthly-sheet .table-jadwal thead tr:first-child th:last-child{width:10%}
                .monthly-sheet .r-row{background:#fff9e6!important}
                .monthly-sheet .h-row{background:#eef8ee!important}
                .monthly-sheet .tr-seksi{background:#d6d8db!important}
                .monthly-sheet{-webkit-print-color-adjust:exact;print-color-adjust:exact}
            }
        </style>
        <?php endif; ?>
    </head>
    <body>
        <div class="<?=$isMonthlyReport ? 'monthly-report-page' : 'no-print'?>">
            <?php if (!$isMonthlyPrint): ?>
            <nav class="navbar navbar-default navbar-fixed-top no-print">
                <div class="container-fluid">
                    <div class="navbar-header">
                        <a class="navbar-brand" href="../../../media.php">
                            <img src="../../../images/toto.png" style="height:25px;margin-top:-3px">
                        </a>
                    </div>
                    <ul class="nav navbar-nav navbar-left">
                        <li>
                            <a href="<?=$isMonthlyReport ? '../../../media.php' : '../kalibrasi/cek_alat.php'?>">
                                <i class="fa fa-arrow-left">
                                </i>
                                <?=$isMonthlyReport ? 'Menu QA' : 'Kembali ke Cek Alat'?>
                            </a>
                        </li>
                    </ul>
                    <ul class="nav navbar-nav navbar-right">
                        <li>
                            <a href="#">
                                <i class="fa fa-user">
                                </i>
                                <?=h($nama_petugas)?>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            <?php endif; ?>
            <div class="container-fluid" style="padding:0 25px">
                <?php if ($isMonthlyReport): ?>
                <div class="filter-box monthly-controls no-print">
                    <h3>Laporan Jadwal Bulanan</h3>
                    <p class="text-muted">Pilih seksi dan periode untuk menampilkan rencana kalibrasi.</p>
                    <form method="get" id="formFilter" class="monthly-filters">
                        <div class="form-group">
                            <label for="monthlySection">Seksi</label>
                            <select class="form-control" id="monthlySection" name="seksi">
                                <option value="ALL">Semua Seksi</option>
                                <?php foreach ($seksiOptions as $section): ?>
                                <option value="<?=h($section)?>" <?=$filter_seksi===$section?'selected':''?>><?=h($section)?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="monthlyMonth">Bulan</label>
                            <select class="form-control" id="monthlyMonth" name="bulan">
                                <?php foreach ($monthNames as $number=>$monthName): ?>
                                <option value="<?=$number?>" <?=$selectedMonth===$number?'selected':''?>><?=h($monthName)?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="monthlyYear">Tahun</label>
                            <input type="number" class="form-control" id="monthlyYear" name="tahun" min="1900" max="9999" step="1" value="<?=$selectedYear?>" required>
                        </div>
                        <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Tampilkan</button>
                    </form>
                    <div class="monthly-print-controls">
                        <div>
                            <label for="monthlyPrintFormat">Format tampilan &amp; cetak</label>
                            <select class="form-control" id="monthlyPrintFormat" name="format" form="formFilter" aria-describedby="monthlyPrintHelp">
                                <option value="1" <?=$monthlyDisplayFormat==='1'?'selected':''?>>Format 1 — Daftar Rencana Kalibrasi</option>
                                <option value="2" <?=$monthlyDisplayFormat==='2'?'selected':''?>>Format 2 — Jadwal Kalibrasi Internal</option>
                                <option value="3" <?=$monthlyDisplayFormat==='3'?'selected':''?>>Format 3 — Jadwal Kalibrasi External</option>
                            </select>
                        </div>
                        <button class="btn btn-primary" type="button" id="monthlyPrintButton"><i class="fa fa-eye"></i> Tampilkan</button>
                        <?php if ($monthlyDisplayFormat === '1'): ?>
                        <button class="btn btn-success" type="button" id="monthlyPrintNowButton" onclick="qaPrintMonthlyFormat1()" <?=!$monthlyPlanRows?'disabled':''?>> <i class="fa fa-print"></i> Print</button>
                        <?php endif; ?>
                    </div>
                    <p id="monthlyPrintHelp" class="text-muted">Pilih format lalu klik Tampilkan. Setelah tampilan laporan muncul, gunakan tombol Print. Format Internal menampilkan R + H; Format External menampilkan R dan baris H kosong.</p>
                    <?php if (!$monthlyPlanRows && !$monthlyReportOptions['errors']): ?>
                    <p class="text-muted">Tidak ada rencana kalibrasi untuk Format 1 pada seksi dan periode ini.</p>
                    <?php endif; ?>
                    <p id="monthlyFilterNotice" class="text-warning" hidden>Filter atau format berubah. Klik Tampilkan untuk memuat tampilan terbaru.</p>
                    <?php if ($monthlyReportOptions['errors']): ?>
                    <div class="alert alert-danger" role="alert"><?=h(implode(' ', $monthlyReportOptions['errors']))?></div>
                    <?php endif; ?>
                </div>
                <section class="monthly-sheet" aria-label="Jadwal kalibrasi bulanan">
                    <header class="monthly-heading">
                        <div>PT. SURYA TOTO INDONESIA Tbk.</div>
                        <h2>LAPORAN JADWAL KALIBRASI BULANAN</h2>
                        <p><?=h($monthlyTitle)?> &middot; Seksi: <?=h($filter_seksi==='ALL'?'Semua Seksi':$filter_seksi)?></p>
                    </header>
                <?php else: ?>
                <div class="filter-box">
                    <h4 style="margin-top:0;font-weight:bold">
                        <i class="fa fa-filter">
                        </i>
                        Filter Master Data
                    </h4>
                    <form method="GET" class="form-inline" id="formFilter">
                        <div class="form-group" style="margin-right:12px">
                            <label style="margin-right:5px">
                                Seksi:
                            </label>
                            <select class="form-control" name="seksi">
                                <option value="ALL">
                                    Semua Seksi
                                </option>
                                <?php foreach($seksiOptions as $s):?>
                                    <option value="<?=h($s)?>" <?=$filter_seksi===$s?'selected':''?>>
                                        <?=h($s)?>
                                    </option>
                                <?php endforeach;?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-right:12px">
                            <label style="margin-right:5px">
                                Status:
                            </label>
                            <select class="form-control" name="status">
                                <?php foreach($statusOptions as $k=>$v):?>
                                    <option value="<?=h($k)?>" <?=$filter_status===$k?'selected':''?>>
                                        <?=h($v)?>
                                    </option>
                                <?php endforeach;?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-right:12px">
                            <label style="margin-right:5px">
                                Tampilan:
                            </label>
                            <select class="form-control" name="waktu" id="filter_waktu">
                                <option value="daftar" <?=$filter_waktu==='daftar'?'selected':''?>>
                                    Daftar No. Alat Ukur
                                </option>
                                <option value="masalah" <?=$filter_waktu==='masalah'?'selected':''?>>
                                    Alat Non-OK / Bermasalah
                                </option>
                                <option value="harian" <?=$filter_waktu==='harian'?'selected':''?>>
                                    Jadwal Kalibrasi Harian
                                </option>
                                <option value="bulanan" <?=$filter_waktu==='bulanan'?'selected':''?>>
                                    Jadwal Kalibrasi Bulanan
                                </option>
                                <option value="tahunan" <?=$filter_waktu==='tahunan'?'selected':''?>>
                                    Jadwal Kalibrasi Tahunan
                                </option>
                            </select>
                        </div>
                        <div class="form-group view-date" data-view="harian" style="margin-right:12px;display:none">
                            <input type="date" class="form-control" name="tgl_harian" value="<?=h($val_harian)?>">
                        </div>
                        <div class="form-group view-date" data-view="bulanan" style="margin-right:12px;display:none">
                            <input type="month" class="form-control" name="tgl_bulanan" value="<?=h($val_bulanan)?>">
                        </div>
                        <div class="form-group view-date" data-view="tahunan" style="margin-right:12px;display:none">
                            <select class="form-control" name="tgl_tahunan">
                                <?php for($y=(int)date('Y')-5;$y<=(int)date('Y')+2;$y++):?>
                                    <option value="<?=$y?>" <?=$val_tahunan==(string)$y?'selected':''?>>
                                        <?=$y?>
                                    </option>
                                <?php endfor;?>
                            </select>
                        </div>
                        <button class="btn btn-primary" type="submit">
                            <i class="fa fa-search">
                            </i>  Tampilkan
                        </button>
                        <a class="btn btn-danger" href="?waktu=masalah&seksi=<?=urlencode($filter_seksi)?>">
                            <i class="fa fa-exclamation-triangle">
                            </i>  Alat Non-OK
                            <span style="margin-left:5px;background:#fff;color:#c9302c;">
                            </span>
                        </a>
                    </form>
                </div>
                <?php endif; ?>
                <?php if($filter_waktu==='daftar'): ?>
                    <div class="table-wrap">
                        <table class="table-jadwal">
                            <thead>
                                <tr>
                                    <th>NO
                                    </th>
                                    <th>NAMA ALAT
                                    </th>
                                    <th>SPESIFIKASI
                                    </th>
                                    <th>NO ALAT
                                    </th>
                                    <th>RES /KLS
                                    </th>
                                    <th>NO ASSET
                                    </th>
                                    <th>FUNGSI
                                    </th>
                                    <th>STATUS
                                    </th>
                                    <th>KALIBRATOR
                                    </th>
                                    <th>KETERANGAN
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $lastSection='';$no=0;foreach($masterRows as $r):$no++;$sec=(string)($r['seksi_pemilik']??'');if($sec!==$lastSection){$lastSection=$sec;echo '<tr class="tr-seksi"><td colspan="10" class="text-left">SEKSI: '.h(strtoupper($sec)).'</td></tr>';}$negative=isNegativeMasterStatus($r['_display_status']??'');$noAsset=masterColValue($r,['no_asset','no_aset'],'');$fungsi=masterColValue($r,['fungsi'],'');$kalibrator=masterColValue($r,['kalibrator','petugas_daftar'],'-');$ket=masterColValue($r,['keterangan'],''); ?>
                                <tr class="<?=$negative?'negative-row':''?>">
                                    <td>
                                        <?=$no?>
                                    </td>
                                    <td class="text-left">
                                        <?=h($r['nama_part'])?>
                                    </td>
                                    <td class="text-left">
                                        <?=h($r['spesifikasi']??($r['ukuran']??''))?>
                                    </td>
                                    <td class="text-left">
                                        <b>
                                            <?=h($r['no_part'])?>
                                        </b>
                                    </td>
                                    <td>
                                        <?=h(($r['resolusi']??'').(($r['kls']??'')?' / '.$r['kls']:''))?>
                                    </td>
                                    <td>
                                        <?=h($noAsset)?>
                                    </td>
                                    <td class="text-left">
                                        <?=h($fungsi)?>
                                    </td>
                                    <td>
                                        <span class="label <?=$negative?'label-danger':'label-default'?>">
                                            <?=h($r['_display_status'])?>
                                        </span>
                                    </td>
                                    <td>
                                        <?=h($kalibrator)?>
                                    </td>
                                    <td class="text-left">
                                        <?=h($ket)?>
                                    </td>
                                </tr>
                            <?php endforeach;if($no===0):?>
                            <tr>
                                <td colspan="10" class="text-center">
                                    Tidak ada data.
                                </td>
                            </tr>
                        <?php endif;?>
                    </tbody>
                </table>
            </div>
        <?php elseif($filter_waktu==='masalah'): ?>
            <div class="filter-box">
                <b>
                    <i class="fa fa-exclamation-triangle">
                    </i> DAFTAR ALAT NON-OK / BERMASALAH &nbsp;
                    <span class="label label-danger" style="font-size:12px">
                        TOTAL:
                        <?= (int)$totalAlatOff ?>
                        ALAT
                    </span>
                </b>
                <div style="font-size:12px;color:#666;margin-top:4px">
                    Menampilkan kondisi alat selain OK. Status workflow yang ditolak validasi tidak dimasukkan.
                </div>
            </div>
            <div class="table-wrap">
                <table class="table-jadwal">
                    <thead>
                        <tr>
                            <th>
                                NO
                            </th>
                            <th>
                                NAMA ALAT
                            </th>
                            <th>
                                SPESIFIKASI
                            </th>
                            <th>
                                NO ALAT
                            </th>
                            <th>
                                RES / KLS
                            </th>
                            <th>
                                NO ASSET
                            </th>
                            <th>
                                FUNGSI
                            </th>
                            <th>
                                KETERANGAN
                            </th>
                            <th>
                                TANGGAL KALIBRASI TERAKHIR
                            </th>
                            <th>
                                KALIBRATOR
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $lastSection='';$no=0;foreach($problemRows as $r):$no++;$sec=(string)($r['seksi_pemilik']??'');if($sec!==$lastSection){$lastSection=$sec;echo '<tr class="tr-seksi"><td colspan="10" class="text-left">SEKSI: '.h(strtoupper($sec)).'</td></tr>';}$noAsset=masterColValue($r,['no_asset','no_aset'],'');$fungsi=masterColValue($r,['fungsi'],'');$kalibrator=masterColValue($r,['kalibrator','petugas_daftar'],'-');$tgl=$r['_last_calibration']??'';$tglFmt=($tgl&&strtotime($tgl)!==false)?date('d-m-Y',strtotime($tgl)):'-'; ?>
                        <tr class="negative-row">
                            <td>
                                <?=$no?>
                            </td>
                            <td class="text-left">
                                <?=h($r['nama_part'])?>
                            </td>
                            <td class="text-left">
                                <?=h($r['spesifikasi']??($r['ukuran']??''))?>
                            </td>
                            <td class="text-left">
                                <b>
                                    <?=h($r['no_part'])?>
                                </b>
                            </td>
                            <td>
                                <?=h(($r['resolusi']??'').(($r['kls']??'')?' / '.$r['kls']:''))?>
                            </td>
                            <td>
                                <?=h($noAsset)?>
                            </td>
                            <td class="text-left">
                                <?=h($fungsi)?>
                            </td>
                            <td>
                                <span class="label label-danger">
                                    <?=h($r['_display_status'])?>
                                </span>
                            </td>
                            <td>
                                <?=h($tglFmt)?>
                            </td>
                            <td>
                                <?=h($kalibrator)?>
                            </td>
                        </tr>
                    <?php endforeach;if($no===0):?>
                    <tr>
                        <td colspan="10" class="text-center">
                            Tidak ada alat bermasalah / Non-OK.
                        </td>
                    </tr>
                <?php endif;?>
            </tbody>
        </table>
    </div>
<?php elseif($filter_waktu==='harian'): ?>
    <div class="filter-box">
        <b>
            JADWAL KALIBRASI HARIAN
            <?=h($val_harian)?>
        </b>
    </div>
    <div class="table-wrap">
        <table class="table-jadwal">
            <thead>
                <tr>
                    <th>
                        NO
                    </th>
                    <th>
                        NAMA ALAT
                    </th>
                    <th>
                        SPESIFIKASI
                    </th>
                    <th>
                        NO. ALAT
                    </th>
                    <th>
                        RESOLUSI
                    </th>
                    <th>
                        PERIODE KALIBRASI
                    </th>
                    <th>
                        RENCANA KALIBRASI
                    </th>
                    <th>
                        AKTUAL KALIBRASI
                    </th>
                    <th>
                        HASIL
                    </th>
                    <th>
                        KALIBRASI BERIKUTNYA
                    </th>
                    <th>
                        PETUGAS
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php $last='';$n=0;foreach($reportRows as $r):$n++;if($r['seksi']!==$last){$last=$r['seksi'];echo '<tr class="tr-seksi"><td colspan="11" class="text-left">SEKSI: '.h(strtoupper($last)).'</td></tr>';}$actualBadge=$r['aktual']!==''?$r['aktual']:'-';$hasil=$r['hasil']!==''?$r['hasil']:'-';$dailyStatus=$r['status']??($hasil!=='-'?$hasil:'');$negative=isNegativeMasterStatus($dailyStatus);?>
                <tr class="<?=$negative?'negative-row':''?>">
                    <td>
                        <?=$n?>
                    </td>
                    <td class="text-left">
                        <?=h($r['nama_part'])?>
                    </td>
                    <td class="text-left">
                        <?=h($r['spesifikasi'])?>
                    </td>
                    <td>
                        <b>
                            <?=h($r['no_part'])?>
                        </b>
                    </td>
                    <td>
                        <?=h($r['resolusi'])?>
                    </td>
                    <td>
                        <?=h($r['periode'])?>
                    </td>
                    <td>
                        <?=h($r['rencana'])?>
                    </td>
                    <td>
                        <?=h($actualBadge)?>
                    </td>
                    <td>
                        <?=h($hasil)?>
                    </td>
                    <td>
                        <?=h($r['next'])?>
                    </td>
                    <td>
                        <?=h($r['petugas'])?>
                    </td>
                </tr>
            <?php endforeach;if($n===0):?>
            <tr>
                <td colspan="11" class="text-center">
                    Tidak ada data jadwal untuk tanggal ini.
                </td>
            </tr>
        <?php endif;?>
    </tbody>
</table>
</div>
<?php elseif($filter_waktu==='bulanan'): ?>
    <?php if ($isMonthlyReport): ?>
    <div id="monthlyFormat1" class="monthly-format1">
        <?php require __DIR__ . '/../jadwal_bulanan/jadwal_bulanan_format1_tabel.php'; ?>
    </div>
    <?php else: ?>
    <div class="filter-box">
        <b>
            JADWAL KALIBRASI BULANAN
            <?=h(strtoupper(date('F Y',strtotime($val_bulanan.'-01'))))?>
        </b>
    </div>
    <div class="table-wrap">
        <table class="table-jadwal">
            <thead>
                <tr>
                    <th rowspan="2">
                        NO
                    </th>
                    <th rowspan="2">
                        NAMA ALAT
                    </th>
                    <th rowspan="2">
                        SEKSI
                    </th>
                    <th rowspan="2">
                        JENIS
                    </th>
                    <?php for($d=1;$d<=$daysInMonth;$d++):$monthDate=sprintf('%04d-%02d-%02d',$selectedYear,$selectedMonth,$d);$offClass=holidayClassForDate($monthDate,$holidayDates);$offTitle=holidayTitleForDate($monthDate,$holidayDates);?>
                    <th class="<?=h($offClass)?>" title="<?=h($offTitle)?>">
                        <?=$d?>
                    </th>
                <?php endfor;?>
                <th rowspan="2">
                    TOTAL
                </th>
                <th rowspan="2">
                    PETUGAS
                </th>
            </tr>
            <tr>
                <?php for($d=1;$d<=$daysInMonth;$d++):$monthDate=sprintf('%04d-%02d-%02d',$selectedYear,$selectedMonth,$d);$offClass=holidayClassForDate($monthDate,$holidayDates);$offTitle=holidayTitleForDate($monthDate,$holidayDates);?>
                <th class="<?=h($offClass)?>" style="font-size:10px" title="<?=h($offTitle)?>">
                    TGL
                </th>
            <?php endfor;?>
        </tr>
    </thead>
    <tbody>
        <?php $n=0;$last='';foreach($reportRows as $g):$n++;if($g['seksi']!==$last){$last=$g['seksi'];echo '<tr class="tr-seksi"><td colspan="'.($daysInMonth+6).'" class="text-left">SEKSI: '.h(strtoupper($last)).'</td></tr>';}$r=$g['R'];$hh=$g['H'];?>
        <tr class="r-row">
            <td rowspan="2">
                <?=$n?>
            </td>
            <td rowspan="2" class="text-left">
                <?=h($g['nama_part'])?>
            </td>
            <td rowspan="2">
                <?=h($g['seksi'])?>
            </td>
            <td>
                <b>
                    R
                </b>
            </td>
            <?php for($d=1;$d<=$daysInMonth;$d++):$monthDate=sprintf('%04d-%02d-%02d',$selectedYear,$selectedMonth,$d);$offClass=holidayClassForDate($monthDate,$holidayDates);?>
            <td class="<?=h($offClass)?>">
                <?=((int)($r[$d]??0))?:''?>
            </td>
        <?php endfor;?>
        <td>
            <b>
                <?=$g['totalR']?>
            </b>
        </td>
        <td rowspan="2">
            <?=h($g['petugas']?:'-')?>
        </td>
    </tr>
    <tr class="h-row">
        <td>
            <b>
                H
            </b>
        </td>
        <?php for($d=1;$d<=$daysInMonth;$d++):$monthDate=sprintf('%04d-%02d-%02d',$selectedYear,$selectedMonth,$d);$offClass=holidayClassForDate($monthDate,$holidayDates);?>
        <td class="<?=h($offClass)?>">
            <?=((int)($hh[$d]??0))?:''?>
        </td>
    <?php endfor;?>
    <td>
        <b>
            <?=$g['totalH']?>
        </b>
    </td>
</tr>
<?php endforeach;if($n===0):?>
<tr>
    <td colspan="<?=($daysInMonth+6)?>" class="text-center">
        Tidak ada data jadwal bulanan.
    </td>
</tr>
<?php endif;?>
</tbody>
</table>
</div>
<div style="margin-top:8px;font-size:12px;color:#666">
    <span style="display:inline-block;width:14px;height:14px;background:rgba(220,53,69,.50);vertical-align:-2px;margin-right:4px">
    </span>
    = Sabtu/Minggu dan tanggal merah/cuti bersama.
    <b>
        R
    </b>
    = Rencana yang mengikuti tanggal aktual Update Hasil Kalibrasi.
    <b>
        H
    </b>
    = Hasil/Aktual kalibrasi pada tanggal yang sama.
</div>
<?php if (!empty($showNextYearDueInfo)): ?>
<div class="filter-box" style="margin-top:18px">
    <b>INFORMASI RENCANA KALIBRASI BULAN <?=h(strtoupper(date('F Y',strtotime($val_bulanan.'-01'))))?></b>
    <div style="font-size:12px;color:#666;margin:5px 0 12px">
        Informasi tahun depan hanya menunjukkan jumlah alat per seksi yang jatuh tempo pada bulan tersebut. Tanggal pelaksanaan ditentukan secara fleksibel oleh QA.
    </div>
    <div class="table-wrap">
        <table class="table-jadwal" style="max-width:650px">
            <thead>
                <tr><th style="width:55px">NO</th><th>SEKSI</th><th style="width:150px">JUMLAH ALAT</th></tr>
            </thead>
            <tbody>
                <?php $dueNo=0; foreach(($monthlyDueRows ?? []) as $due): $dueNo++; ?>
                <tr>
                    <td><?=$dueNo?></td>
                    <td class="text-left"><?=h($due['seksi'])?></td>
                    <td><b><?=(int)$due['jumlah']?></b></td>
                </tr>
                <?php endforeach; if($dueNo===0): ?>
                <tr><td colspan="3" class="text-center">Belum ada alat hasil kalibrasi sebelumnya yang jatuh tempo pada bulan ini.</td></tr>
                <?php else: ?>
                <tr style="font-weight:bold;background:#f5f5f5"><td colspan="2" class="text-right">TOTAL ALAT BULAN INI</td><td><?=(int)($monthlyDueTotal ?? 0)?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>
<?php elseif($filter_waktu==='tahunan'): ?>
    <div class="filter-box">
        <b>
            JADWAL KALIBRASI TAHUNAN
            <?=h($val_tahunan)?>
        </b>
    </div>
    <div class="table-wrap">
        <table class="table-jadwal">
            <thead>
                <tr>
                    <th rowspan="2">
                        NO
                    </th>
                    <th rowspan="2">
                        NAMA ALAT
                    </th>
                    <th rowspan="2">
                        SEKSI
                    </th>
                    <th rowspan="2">
                        JENIS
                    </th>
                    <?php for($m=1;$m<=12;$m++):?>
                        <th>
                            <?=date('M',mktime(0,0,0,$m,1))?>
                        </th>
                    <?php endfor;?>
                    <th rowspan="2">
                        TOTAL
                    </th>
                    <th rowspan="2">
                        PETUGAS
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php $n=0;$last='';foreach($reportRows as $g):$n++;if($g['seksi']!==$last){$last=$g['seksi'];echo '<tr class="tr-seksi"><td colspan="17" class="text-left">SEKSI: '.h(strtoupper($last)).'</td></tr>';}$codes=['R'=>$g['R'],'H'=>$g['H'],'B'=>$g['B']];$totals=['R'=>$g['totalR'],'H'=>$g['totalH'],'B'=>$g['totalB']];foreach($codes as $code=>$arr):?>
                <tr class="<?=strtolower($code)?>-row">
                    <td>
                        <?=($code==='R'?$n:'')?>
                    </td>
                    <td>
                        <?=($code==='R'?h($g['nama_part']):'')?>
                    </td>
                    <td>
                        <?=($code==='R'?h($g['seksi']):'')?>
                    </td>
                    <td>
                        <b>
                            <?=$code?>
                        </b>
                    </td>
                    <?php for($m=1;$m<=12;$m++):?>
                        <td>
                            <?=((int)($arr[$m]??0))?:''?>
                        </td>
                    <?php endfor;?>
                    <td>
                        <b>
                            <?=$totals[$code]?>
                        </b>
                    </td>
                    <td>
                        <?=($code==='R'?h($g['petugas']?:'-'):'')?>
                    </td>
                </tr>
            <?php endforeach;endforeach;if($n===0):?>
            <tr>
                <td colspan="17" class="text-center">
                    Tidak ada data jadwal tahunan.
                </td>
            </tr>
        <?php endif;?>
    </tbody>
</table>
</div>
<div style="margin-top:8px;font-size:12px;color:#666">
    <b>
        R
    </b>
    = Rencana.
    <b>
        H
    </b>
    = Aktual hasil Update Hasil Kalibrasi.
    <b>
        B
    </b>
    = Beda/terlewat = max(R-H, 0) pada bulan tersebut.
</div>
<?php endif; ?>
<?php if ($isMonthlyReport): ?></section><?php endif; ?>
</div>
</div>
<script src="../../../js/jquery.js">
</script>
<?php if ($isMonthlyReport && !$isMonthlyPrint): ?>
<script>
(function(){
    var form=document.getElementById('formFilter');
    var displayButton=document.getElementById('monthlyPrintButton');
    var initialValues=$(form).find('[name]').serialize();
    var formatSelect=document.getElementById('monthlyPrintFormat');
    var initialFormat=<?=json_encode($monthlyDisplayFormat)?>;
    var printNowButton=document.getElementById('monthlyPrintNowButton');
    var hasData={
        '1':<?=!!$monthlyPlanRows?'true':'false'?>,
        '2':<?=!!$reportRows?'true':'false'?>,
        '3':<?=!!$reportRows?'true':'false'?>
    };
    function updateDisplayButton(){
        var changed=$(form).find('[name]').serialize()!==initialValues || formatSelect.value!==initialFormat;
        displayButton.disabled=!hasData[formatSelect.value];
        if(printNowButton) printNowButton.style.display=changed?'none':'inline-block';
        document.getElementById('monthlyFilterNotice').hidden=!changed;
    }
    $(form).on('input change',updateDisplayButton);
    $(formatSelect).on('change',updateDisplayButton);
    displayButton.addEventListener('click',function(){
        var format=formatSelect.value;
        var seksi=document.getElementById('monthlySection').value;
        var bulan=document.getElementById('monthlyMonth').value;
        var tahun=document.getElementById('monthlyYear').value;
        if(format==='1'){
            window.location.href='../jadwal_bulanan/laporan_jadwal_bulanan.php?seksi='+encodeURIComponent(seksi)+'&bulan='+encodeURIComponent(bulan)+'&tahun='+encodeURIComponent(tahun)+'&format=1';
            return;
        }
        var monthValue=String(tahun)+'-'+String(bulan).padStart(2,'0');
        var mode=format==='2'?'internal':'external';
        window.location.href='../jadwal_bulanan/cetak_jadwal_bulanan.php?bulan='+encodeURIComponent(monthValue)+'&seksi='+encodeURIComponent(seksi)+'&status=ALL&mode='+encodeURIComponent(mode);
    });
    updateDisplayButton();
})();
function qaPrintMonthlyFormat1(){
    var oldTitle=document.title;
    document.title='';
    var restore=function(){ document.title=oldTitle; window.removeEventListener('afterprint',restore); };
    window.addEventListener('afterprint',restore);
    window.print();
    setTimeout(function(){ if(document.title==='') document.title=oldTitle; },1000);
}
</script>
<?php endif; ?>
<script src="../../../js/bootstrap.min.js">
</script>
<script>
    (function(){
    function toggleDate(){var v=$('#filter_waktu').val();$('.view-date').hide();$('.view-date[data-view="'+v+'"]').show();}
    toggleDate(); $('#filter_waktu').on('change',function(){toggleDate();});
    })();
</script>
</body>
</html>

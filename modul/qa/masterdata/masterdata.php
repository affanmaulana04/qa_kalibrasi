<?php
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();
if (empty($_SESSION['login'])) {
    header('location: ../../../logout.php');
    exit();
}
require_once __DIR__ . '/../../../inc/inc_koneksi.php';
require_once __DIR__ . '/../../../inc/qa_report_rules.php';
require_once __DIR__ . '/../../../inc/qa_seksi_akses.php';
require_once __DIR__ . '/../../../inc/qa_jadwal.php';
// Report format permissions are validated again here (not just on menu/dropdown).
if (isset($monthlyReportOptions)) {
    qaRbacRequireAny($konek, ['jadwal_bulanan', 'jadwal_bulanan_internal', 'jadwal_bulanan_external']);
    $requestedMonthlyFormat = (string)($monthlyReportOptions['format'] ?? '1');
    $requiredMonthlyPermission = qaRbacMonthlyPermission($requestedMonthlyFormat);
    if (!$requiredMonthlyPermission) {
        http_response_code(400);
        exit('Format laporan tidak valid.');
    }
    qaRbacRequire($konek, $requiredMonthlyPermission);
} else {
    qaRbacRequire($konek, 'masterdata');
}
$isMonthlyReport = isset($monthlyReportOptions);
$isMonthlyPrint = $isMonthlyReport && $monthlyReportOptions['cetak'];
$nama_petugas = isset($_SESSION['nama_lengkap']) && $_SESSION['nama_lengkap'] !== ''
    ? $_SESSION['nama_lengkap'] : ($_SESSION['userid'] ?? '');
function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function formatMasterDailyDate($value) {
    $date = substr(trim((string)$value), 0, 10);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts) ||
        !checkdate((int)$parts[2], (int)$parts[3], (int)$parts[1]))
        return '-';
    return $parts[3] . '-' . $parts[2] . '-' . $parts[1];
}
function esc($db, $v) {
    return mysqli_real_escape_string($db, (string)$v);
}
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
    return preg_replace('/_(?:NOTGOOD|RUSAK|DISCONTINUE|DITARIK)_\d{10,14}$/i', '', $no);
}
function qaMasterVisualRusak($row) {
    if (strtolower(trim((string)($row['jenis_transaksi'] ?? ''))) === 'pemeriksaan_visual' ||
        strtoupper(trim((string)($row['status_kalibrasi'] ?? ''))) === 'RUSAK VISUAL')
        return true;
    $snapshot = json_decode((string)($row['hasil_json'] ?? ''), true);
    return is_array($snapshot) && !empty($snapshot['visual_rusak']);
}
if (!$isMonthlyReport) {
    foreach (['qa_alat_ng', 'qa_alat_rusak', 'qa_alat_discontinue', 'qa_alat_ditarik'] as $archiveTable) {
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
$filter_seksi = $_GET['seksi'] ?? 'ALL';
$filter_seksi = $filter_seksi === 'ALL'?'ALL':qaSectionKey($filter_seksi);
$filter_status = $_GET['status'] ?? 'ALL';
$filter_waktu = $_GET['waktu'] ?? 'daftar';
if (!in_array($filter_waktu, ['daftar', 'masalah', 'harian', 'bulanan', 'tahunan'], true))
    $filter_waktu = 'daftar';
$today = date('Y-m-d');
$val_harian = $_GET['tgl_harian'] ?? $today;
$val_bulanan = $_GET['tgl_bulanan'] ?? date('Y-m');
$val_tahunan = $_GET['tgl_tahunan'] ?? date('Y');
$val_triwulan = (int)($_GET['triwulan'] ?? 0);
if ($isMonthlyReport) {
    $filter_seksi = $monthlyReportOptions['seksi'];
    $filter_status = 'ALL';
    $filter_waktu = 'bulanan';
    $val_harian = $today;
    $val_tahunan = (string)$monthlyReportOptions['tahun'];
    $val_bulanan = sprintf('%04d-%02d', $monthlyReportOptions['tahun'], $monthlyReportOptions['bulan']);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $val_harian))
    $val_harian = $today;
if (!preg_match('/^\d{4}-\d{2}$/', $val_bulanan))
    $val_bulanan = date('Y-m');
if (!preg_match('/^\d{4}$/', $val_tahunan))
    $val_tahunan = date('Y');
if ($val_triwulan < 1 || $val_triwulan > 4)
    $val_triwulan = $val_tahunan === date('Y') ? (int)ceil((int)date('n') / 3) : 1;
$selectedYear = (int)substr($val_bulanan, 0, 4);
$selectedMonth = (int)substr($val_bulanan, 5, 2);
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
$holidayDates = []; // Sumber tanggal merah: qa_tanggal_merah, dikelola Admin.
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
    if (isset($holidayDates[$date]))
        return $holidayDates[$date];
    $ts = strtotime($date);
    if ($ts !== false) {
        $n = (int)date('N', $ts);
        if ($n === 6)
            return 'Sabtu';
        if ($n === 7)
            return 'Minggu';
    }
    return '';
}
$statusOptions = ['ALL' => 'Semua Kondisi', 'OK' => 'OK (Aktif)', 'NOT GOOD' => 'NOT GOOD', 'RUSAK' => 'RUSAK', 'DISCONTINUE' => 'DISCONTINUE', 'DITARIK TECHNICAL' => 'DITARIK TECHNICAL', 'MENUNGGU VALIDASI' => 'Menunggu Validasi', 'DITOLAK VALIDASI' => 'Ditolak Validasi'];
$q_seksi = mysqli_query($konek, "SELECT nama_seksi FROM qa_seksi_master ORDER BY nama_seksi ASC");
$seksiOptions = [];
if ($q_seksi)
    while ($s = mysqli_fetch_assoc($q_seksi))
        if (qaSeksiBoleh($konek, $s['nama_seksi']))
            $seksiOptions[] = qaSectionKey($s['nama_seksi']);
$seksiOptions = array_values(array_unique($seksiOptions));
$masterRows = [];
$metaTable = mysqli_query($konek, "SHOW TABLES LIKE 'qa_migrasi_alat_meta'");
$metaColumn = $metaTable && mysqli_num_rows($metaTable) > 0
    ? mysqli_query($konek, "SHOW COLUMNS FROM qa_migrasi_alat_meta LIKE 'petugas'") : false;
$petugasMetaSql = $metaColumn && mysqli_num_rows($metaColumn) > 0
    ? "NULLIF((SELECT mm.petugas FROM qa_migrasi_alat_meta mm WHERE mm.no_part=p.no_part LIMIT 1), '')," : 'NULL,';
$tables = ['qa_part', 'qa_alat_ng', 'qa_alat_rusak', 'qa_alat_discontinue', 'qa_alat_ditarik'];
foreach ($tables as $t) {
    $status = finalStatusFromTable($t);
    $sql = "SELECT p.*,
            COALESCE($petugasMetaSql
            (SELECT q0.created_by FROM qa_pengecekan q0
             WHERE q0.no_part = p.no_part AND q0.nomor_pengecekan LIKE 'QA-REG-%'
             ORDER BY q0.id ASC LIMIT 1)) AS petugas_daftar
            FROM `$t` p ORDER BY p.seksi_pemilik ASC, p.nama_part ASC, p.no_part ASC";
    $q = mysqli_query($konek, $sql);
    if (!$q)
        continue;
    while ($r = mysqli_fetch_assoc($q)) {
        // Untuk arsip, created_by registrasi awal masih berada di histori dengan nomor asli.
        if (empty($r['petugas_daftar']) && $t !== 'qa_part') {
            $orig = originalNoPartFromArchive((string)$r['no_part']);
            $origEsc = esc($konek, $orig);
            $qReg = mysqli_query($konek, "SELECT created_by FROM qa_pengecekan WHERE no_part='$origEsc' AND nomor_pengecekan LIKE 'QA-REG-%' ORDER BY id ASC LIMIT 1");
            if ($qReg && ($rr = mysqli_fetch_assoc($qReg)))
                $r['petugas_daftar'] = $rr['created_by'];
        }
        $r['seksi_pemilik'] = qaSectionKey($r['seksi_pemilik'] ?? '');
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
    while ($q = mysqli_fetch_assoc($qLatest))
        $latestByNo[$q['no_part']] = $q;
}
$qApproved = mysqli_query($konek, "SELECT q.* FROM qa_pengecekan q
    INNER JOIN (SELECT no_part, MAX(id) max_id FROM qa_pengecekan WHERE status_validasi='final_disetujui' GROUP BY no_part) z
        ON z.no_part=q.no_part AND z.max_id=q.id");
if ($qApproved) {
    while ($q = mysqli_fetch_assoc($qApproved)) {
        $approvedByNo[$q['no_part']] = $q;
        $approvedByNo[strtoupper(trim((string)$q['no_part']))] = $q;
    }
}
$registryByNo = [];
$qRegistry = mysqli_query($konek, "SELECT no_part,status_alat,alasan_status,hasil_terakhir FROM qa_alat_registry");
if ($qRegistry) {
    while ($r = mysqli_fetch_assoc($qRegistry))
        $registryByNo[strtoupper(trim((string)$r['no_part']))] = $r;
}
function displayMasterStatus($master, $latestByNo, $registryByNo = []) {
    $no = strtoupper(trim((string)($master['no_part'] ?? '')));
    $final = strtoupper(trim((string)($master['_final_status'] ?? '')));
    // MASTER NON-OK V2 - BARIS DARI TABEL ARSIP ADALAH SUMBER STATUS FINAL
    // Jangan ditimpa menjadi OK hanya karena registry legacy masih AKTIF.
    if (($master['_table'] ?? 'qa_part') !== 'qa_part' && in_array($final, ['NOT GOOD', 'RUSAK', 'DISCONTINUE', 'DITARIK TECHNICAL'], true))
        return $final;
    $latest = $latestByNo[$master['no_part']] ?? ($latestByNo[$no] ?? null);
    $registry = $registryByNo[$no] ?? null;
    if ($latest) {
        $sv = strtolower((string)($latest['status_validasi'] ?? ''));
        if ($sv === 'menunggu_pemeriksa' || $sv === 'menunggu_penyetuju')
            return 'MENUNGGU VALIDASI';
        if ($sv === 'ditolak_pemeriksa' || $sv === 'ditolak_penyetuju')
            return 'DITOLAK VALIDASI';
        if ($sv === 'final_disetujui' && qaMasterVisualRusak($latest))
            return 'RUSAK';
    }
    if ($registry) {
        $rs = strtoupper(trim((string)($registry['status_alat'] ?? '')));
        if ($rs === 'BLOKIR_PERMANEN') {
            $reason = strtoupper((string)($registry['alasan_status'] ?: ($registry['hasil_terakhir'] ?: 'RUSAK')));
            return $reason === 'RUSAK VISUAL' ? 'RUSAK' : $reason;
        }
        if ($rs === 'NONAKTIF_SEMENTARA')
            return strtoupper((string)($registry['alasan_status'] ?: ($registry['hasil_terakhir'] ?: 'NONAKTIF')));
        if ($rs === 'AKTIF' && $latest && strtolower((string)($latest['status_validasi'] ?? '')) === 'final_disetujui')
            return 'OK';
    }
    return $final !== '' ? $final : 'OK';
}
function statusMatches($rowStatus, $filterStatus) {
    $status = strtoupper(trim((string)$rowStatus));
    if ($status === 'RUSAK VISUAL')
        $status = 'RUSAK';
    return $filterStatus === 'ALL' || $status === strtoupper($filterStatus);
}
function isNegativeMasterStatus($status) {
    $s = strtoupper(trim((string)$status));
    return in_array($s, ['RUSAK', 'RUSAK VISUAL', 'DISCONTINUE', 'DITARIK TECHNICAL', 'NOT GOOD', 'NG'], true);
}
function masterColValue($row, $keys, $fallback = '') {
    foreach ((array)$keys as $k) {
        if (isset($row[$k]) && trim((string)$row[$k]) !== '')
            return $row[$k];
    }
    return $fallback;
}
// MASTER NON-OK V2 - DEDUPE NOMOR ALAT, PRIORITASKAN DATA ARSIP NON-OK
// Jika data legacy masih tersisa di qa_part sekaligus sudah ada di tabel arsip, tampilkan arsip saja.
$dedupMaster = [];
$fungsiAktifByNo = [];
foreach ($masterRows as $r) {
    $key = strtoupper(trim((string)($r['no_part'] ?? '')));
    if ($key === '')
        continue;
    if (($r['_table'] ?? 'qa_part') === 'qa_part' && trim((string)($r['fungsi'] ?? '')) !== '') {
        $fungsiAktifByNo[$key] = $r['fungsi'];
    }
    $isArchive = (($r['_table'] ?? 'qa_part') !== 'qa_part');
    if (!isset($dedupMaster[$key]) || $isArchive)
        $dedupMaster[$key] = $r;
}
$masterRows = array_values($dedupMaster);
$filteredMasters = [];
foreach ($masterRows as $r) {
    $r['_display_status'] = displayMasterStatus($r, $latestByNo, $registryByNo);
    if (!qaSeksiBoleh($konek, $r['seksi_pemilik'] ?? ''))
        continue;
    if ($filter_seksi !== 'ALL' && strtoupper((string)$r['seksi_pemilik']) !== strtoupper((string)$filter_seksi))
        continue;
    if (!statusMatches($r['_display_status'], $filter_status))
        continue;
    $filteredMasters[] = $r;
}
function qaAnnualPelaksana($hasilJson) {
    $snapshot = json_decode((string)$hasilJson, true);
    $value = is_array($snapshot) ? strtoupper(trim((string)($snapshot['pelaksana_kalibrasi'] ?? ''))) : '';
    return $value === 'EXTERNAL' ? 'EXTERNAL' : 'INTERNAL';
}
function buildPlanEvents($konek, $masterByNo, $latestByNo, $approvedByNo, $inactiveCutoffs) {
    $events = [];
    foreach ($masterByNo as $no => $m) {
        if (($m['_table'] ?? '') === 'qa_part' && !empty($m['jadwal_kalibrasi'])) {
            $d = qaJadwalEfektif($konek, substr((string)$m['jadwal_kalibrasi'], 0, 10));
            if (isset($inactiveCutoffs[$no]) && $d > $inactiveCutoffs[$no])
                continue;
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                $events[$no . '|' . $d] = [
                    'no_part' => $no, 'nama_part' => $m['nama_part'], 'seksi' => $m['seksi_pemilik'],
                    'spesifikasi' => $m['spesifikasi'] ?? ($m['ukuran'] ?? ''), 'resolusi' => $m['resolusi'] ?? '',
                    'periode' => $m['periode_kalibrasi'] ?? '', 'plan_date' => $d, 'status' => $m['_final_status'],
                    'petugas' => $m['petugas_daftar'] ?? '-',
                    'pelaksana' => qaAnnualPelaksana($approvedByNo[$no]['hasil_json'] ?? '')
                ];
            }
        }
    }
    $q = mysqli_query($konek, "SELECT q.* FROM qa_pengecekan q WHERE COALESCE(q.jenis_transaksi,'update_hasil_kalibrasi') <> 'registrasi_alat' AND q.tanggal_jadwal_saat_pengecekan IS NOT NULL AND q.tanggal_jadwal_saat_pengecekan <> '' ORDER BY q.id ASC");
    if ($q) {
        while ($x = mysqli_fetch_assoc($q)) {
            $visualRusak = qaMasterVisualRusak($x);
            if ($visualRusak && $x['status_validasi'] !== 'final_disetujui')
                continue;
            $d = substr((string)$x['tanggal_jadwal_saat_pengecekan'], 0, 10);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d))
                continue;
            $no = $x['no_part'] ?? '';
            if (!isset($masterByNo[$no]))
                continue;
            if (isset($inactiveCutoffs[$no]) && $d > $inactiveCutoffs[$no])
                continue;
            $m = $masterByNo[$no];
            if (!qaSeksiBoleh($konek, $m['seksi_pemilik'] ?? ''))
                continue;
            $events[$no . '|' . $d] = [
                'no_part' => $no, 'nama_part' => $m['nama_part'], 'seksi' => $m['seksi_pemilik'],
                'spesifikasi' => $m['spesifikasi'] ?? ($m['ukuran'] ?? ''), 'resolusi' => $m['resolusi'] ?? '',
                'periode' => $m['periode_kalibrasi'] ?? '', 'plan_date' => $d,
                'status' => $visualRusak ? 'RUSAK' : $m['_final_status'],
                'petugas' => $m['petugas_daftar'] ?? '-',
                'pelaksana' => $x['status_validasi'] === 'final_disetujui'
                    ? qaAnnualPelaksana($x['hasil_json'] ?? '') : 'INTERNAL'
            ];
        }
    }
    return array_values($events);
}
$masterByNo = [];
foreach ($masterRows as $r) {
    $masterByNo[(string)$r['no_part']] = $r;
    if (($r['_table'] ?? 'qa_part') !== 'qa_part') {
        $originalNo = originalNoPartFromArchive((string)$r['no_part']);
        if ($originalNo !== (string)$r['no_part'])
            $masterByNo[$originalNo] = $r;
    }
}
$masterByNo = array_filter($masterByNo, function ($m) use ($konek) {
    return qaSeksiBoleh($konek, $m['seksi_pemilik'] ?? '');
});
$inactiveCutoffs = [];
foreach ($masterByNo as $no => $m) {
    if (!isNegativeMasterStatus(displayMasterStatus($m, $latestByNo, $registryByNo)))
        continue;
    $approved = $approvedByNo[$no] ?? null;
    $approvedStatus = strtoupper(trim((string)($approved['status_kalibrasi'] ?? '')));
    $approvedDate = substr((string)($approved['tanggal_pengecekan'] ?? ''), 0, 10);
    $inactiveCutoffs[$no] = isNegativeMasterStatus($approvedStatus) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $approvedDate)
        ? $approvedDate : $today;
}
$planEvents = buildPlanEvents($konek, $masterByNo, $latestByNo, $approvedByNo, $inactiveCutoffs);
$actualEvents = [];
$monthlyActualEvents = [];
$qActual = mysqli_query($konek, "SELECT q.* FROM qa_pengecekan q WHERE COALESCE(q.jenis_transaksi,'update_hasil_kalibrasi') <> 'registrasi_alat' AND q.status_validasi IN ('menunggu_pemeriksa','menunggu_penyetuju','final_disetujui') AND (q.nomor_pengecekan IS NULL OR q.nomor_pengecekan NOT LIKE 'QA-REG-%') ORDER BY q.tanggal_pengecekan ASC, q.id ASC");
if ($qActual) {
    while ($q = mysqli_fetch_assoc($qActual)) {
        $visualRusak = qaMasterVisualRusak($q);
        if ($visualRusak && $q['status_validasi'] !== 'final_disetujui')
            continue;
        $no = (string)($q['no_part'] ?? '');
        if (!isset($masterByNo[$no]))
            continue;
        $m = $masterByNo[$no];
        $event = [
            'no_part' => $no,
            'nama_part' => $m['nama_part'], 'seksi' => $m['seksi_pemilik'],
            'spesifikasi' => $m['spesifikasi'] ?? ($m['ukuran'] ?? ''), 'resolusi' => $m['resolusi'] ?? '',
            'periode' => $m['periode_kalibrasi'] ?? '',
            'plan_date' => substr((string)$q['tanggal_jadwal_saat_pengecekan'], 0, 10),
            'actual_date' => substr((string)$q['tanggal_pengecekan'], 0, 10),
            'status' => $visualRusak ? 'RUSAK' : $m['_final_status'],
            'petugas' => trim((string)($q['created_by'] ?? '')) !== ''
                ? $q['created_by'] : ($q['operator'] ?? '-'),
            'hasil' => $visualRusak ? 'RUSAK' : $m['_final_status'],
            'pelaksana' => qaAnnualPelaksana($q['hasil_json'] ?? ''),
            'next_date' => isNegativeMasterStatus($visualRusak ? 'RUSAK' : $m['_final_status'])
                ? '-' : ($q['periode_berikutnya'] ?? ($m['jadwal_kalibrasi'] ?? ''))
        ];
        $monthlyActualEvents[] = $event;
        if ($q['status_validasi'] === 'final_disetujui')
            $actualEvents[] = $event;
    }
}
$reportRows = [];
$reportMeta = null;
if ($filter_waktu === 'harian') {
    $daily = [];
    foreach ($planEvents as $e) {
        if ($e['plan_date'] !== $val_harian)
            continue;
        if ($filter_seksi !== 'ALL' && strtoupper($e['seksi']) !== strtoupper($filter_seksi))
            continue;
        if (!statusMatches(displayMasterStatus($masterByNo[$e['no_part']] ?? ['_final_status' => $e['status'], 'no_part' => $e['no_part']], $latestByNo, $registryByNo), $filter_status))
            continue;
        $daily[$e['no_part']] = [
            'no_part' => $e['no_part'], 'nama_part' => $e['nama_part'], 'spesifikasi' => $e['spesifikasi'], 'resolusi' => $e['resolusi'],
            'seksi' => $e['seksi'], 'periode' => $e['periode'], 'rencana' => $e['plan_date'], 'aktual' => '', 'hasil' => '', 'next' => '', 'petugas' => '-', 'status' => $e['status']
        ];
    }
    foreach ($actualEvents as $e) {
        if ($e['actual_date'] !== $val_harian)
            continue;
        if ($filter_seksi !== 'ALL' && strtoupper($e['seksi']) !== strtoupper($filter_seksi))
            continue;
        if (!statusMatches($e['status'], $filter_status))
            continue;
        if (!isset($daily[$e['no_part']])) {
            $daily[$e['no_part']] = [
                'no_part' => $e['no_part'], 'nama_part' => $e['nama_part'], 'spesifikasi' => $e['spesifikasi'], 'resolusi' => $e['resolusi'],
                'seksi' => $e['seksi'], 'periode' => $e['periode'], 'rencana' => $e['plan_date'], 'aktual' => '', 'hasil' => '', 'next' => '', 'petugas' => '-', 'status' => $e['status']
            ];
        }
        $daily[$e['no_part']]['aktual'] = $e['actual_date'];
        $daily[$e['no_part']]['hasil'] = $e['hasil'];
        $daily[$e['no_part']]['next'] = $e['next_date'];
        $daily[$e['no_part']]['petugas'] = trim((string)$e['petugas']) !== '' ? $e['petugas'] : '-';
    }
    $reportRows = array_values($daily);
    usort($reportRows, fn ($a, $b) => strtoupper($a['seksi'] . $a['nama_part'] . $a['no_part']) <=> strtoupper($b['seksi'] . $b['nama_part'] . $b['no_part']));
    $reportMeta = ['jenis' => 'laporan_harian', 'periode_key' => $val_harian, 'periode_label' => 'Harian ' . formatMasterDailyDate($val_harian)];
} elseif ($filter_waktu === 'bulanan') {
    $reportRows = qaMonthlyCounts($planEvents, $monthlyActualEvents, $val_bulanan, $daysInMonth, $filter_seksi, $filter_status, true);
    $reportMeta = ['jenis' => 'laporan_bulanan', 'periode_key' => $val_bulanan, 'periode_label' => 'Bulanan ' . date('F Y', strtotime($val_bulanan . '-01'))];
} elseif ($filter_waktu === 'tahunan') {
    $year = (int)$val_tahunan;
    $annualMonths = range(1, 12);
    $annualSections = $filter_seksi === 'ALL' ? $seksiOptions : [$filter_seksi];
    $annualTypeLabels = [
        'INTERNAL' => 'INTERNAL',
        'EXTERNAL' => 'EKSTERNAL'
    ];
    $groups = array_fill_keys(array_keys($annualTypeLabels), []);
    $seen = ['R' => [], 'H' => []];
    foreach (['R' => $planEvents, 'H' => $actualEvents] as $code => $events) {
        foreach ($events as $e) {
            $date = $code === 'R' ? $e['plan_date'] : $e['actual_date'];
            if ((int)substr($date, 0, 4) !== $year)
                continue;
            $section = qaSectionKey($e['seksi']);
            if (!in_array($section, $annualSections, true))
                continue;
            $display = $code === 'R'
                ? displayMasterStatus($masterByNo[$e['no_part']] ?? ['_final_status' => $e['status'], 'no_part' => $e['no_part']], $latestByNo, $registryByNo)
                : $e['status'];
            if (!statusMatches($display, $filter_status))
                continue;
            $month = (int)substr($date, 5, 2);
            if ($month < 1 || $month > 12)
                continue;
            $key = strtoupper(trim($e['nama_part']));
            $type = $e['pelaksana'] ?? 'INTERNAL';
            if (!isset($groups[$type]))
                $type = 'INTERNAL';
            $unique = $e['no_part'] . '|' . $date;
            if (isset($seen[$code][$unique]))
                continue;
            $seen[$code][$unique] = true;
            if (!isset($groups[$type][$key])) {
                $groups[$type][$key] = ['nama_part' => $e['nama_part'], 'R' => [], 'H' => []];
            }
            $groups[$type][$key][$code][$month][$section] = (int)($groups[$type][$key][$code][$month][$section] ?? 0) + 1;
        }
    }
    $annualTotalsByType = [];
    $reportRows = [];
    foreach ($groups as $type => &$typeGroups) {
        ksort($typeGroups, SORT_NATURAL | SORT_FLAG_CASE);
        $annualTotalsByType[$type] = ['R' => [], 'H' => [], 'B' => []];
        foreach ($typeGroups as &$g) {
            $g['B'] = [];
            foreach (range(1, 12) as $month) {
                foreach ($annualSections as $section) {
                    $r = (int)($g['R'][$month][$section] ?? 0);
                    $h = (int)($g['H'][$month][$section] ?? 0);
                    $g['B'][$month][$section] = max(0, $r - $h);
                    foreach (['R' => $r, 'H' => $h, 'B' => $g['B'][$month][$section]] as $code => $count) {
                        $annualTotalsByType[$type][$code][$month][$section] = (int)($annualTotalsByType[$type][$code][$month][$section] ?? 0) + $count;
                    }
                }
            }
        }
        unset($g);
        $reportRows[$type] = array_values(array_filter($typeGroups, function ($group) use ($annualMonths, $annualSections) {
            foreach ($annualMonths as $month) {
                foreach ($annualSections as $section) {
                    if (!empty($group['R'][$month][$section]) || !empty($group['H'][$month][$section]))
                        return true;
                }
            }
            return false;
        }));
    }
    unset($typeGroups);
    $reportMeta = ['jenis' => 'laporan_tahunan', 'periode_key' => (string)$year, 'periode_label' => 'Tahunan ' . $year];
}
// Normalisasi data untuk daftar default.
$masterRows = array_values(array_filter($filteredMasters, function ($r) {
    return trim((string)($r['no_part'] ?? '')) !== '';
}));
usort($masterRows, function ($a, $b) {
    return [qaSectionKey($a['seksi_pemilik'] ?? ''), strtoupper($a['nama_part'] ?? ''), strtoupper($a['no_part'] ?? '')] <=> [qaSectionKey($b['seksi_pemilik'] ?? ''), strtoupper($b['nama_part'] ?? ''), strtoupper($b['no_part'] ?? '')];
});
/* Daftar khusus alat bermasalah/non-OK. Status workflow ditolak/menunggu tidak ikut. */
$problemRows = [];
foreach ($masterRows as $r) {
    $st = strtoupper(trim((string)($r['_display_status'] ?? '')));
    if ($st === '' || $st === 'OK' || $st === 'REGIST' || $st === 'MENUNGGU VALIDASI' || $st === 'DITOLAK VALIDASI' || strpos($st, 'DITOLAK') === 0)
        continue;
    $lookupNo = (string)($r['no_part'] ?? '');
    if (($r['_table'] ?? '') !== 'qa_part')
        $lookupNo = originalNoPartFromArchive($lookupNo);
    $last = $approvedByNo[$lookupNo] ?? ($approvedByNo[strtoupper($lookupNo)] ?? null);
    $r['_last_calibration'] = $last ? substr((string)($last['tanggal_pengecekan'] ?? ''), 0, 10) : '';
    $problemRows[] = $r;
}
usort($problemRows, function ($a, $b) {
    return [qaSectionKey($a['seksi_pemilik'] ?? ''), strtoupper($a['nama_part'] ?? ''), strtoupper($a['no_part'] ?? '')] <=> [qaSectionKey($b['seksi_pemilik'] ?? ''), strtoupper($b['nama_part'] ?? ''), strtoupper($b['no_part'] ?? '')];
});
// MASTER NON-OK V2 - TABEL ARSIP TAMPIL + TOTAL SELURUH ALAT NON-OK / BERMASALAH
$totalAlatOff = count($problemRows);
if ($isMonthlyReport) {
    // Include sections present in legacy equipment data as well as the section master.
    foreach ($masterByNo as $master) {
        $section = trim((string)($master['seksi_pemilik'] ?? ''));
        if ($section !== '' && qaSeksiBoleh($konek, $section) && !in_array($section, $seksiOptions, true))
            $seksiOptions[] = $section;
    }
    natcasesort($seksiOptions);
    if ($filter_seksi !== 'ALL' && (!qaSeksiBoleh($konek, $filter_seksi) || !in_array($filter_seksi, $seksiOptions, true))) {
        $monthlyReportOptions['errors'][] = 'Seksi tidak ditemukan. Pilih seksi yang tersedia.';
    }
    if ($monthlyReportOptions['errors']) {
        http_response_code(400);
        $reportRows = [];
    }
    $monthNames = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $monthlyTitle = $monthNames[$selectedMonth] . ' ' . $selectedYear;
    $monthlyDisplayFormat = (string)$monthlyReportOptions['format'];
    $monthlyPlanRows = [];
    if (!$monthlyReportOptions['errors']) {
        foreach ($planEvents as $event) {
            if (substr($event['plan_date'], 0, 7) !== $val_bulanan)
                continue;
            if (!qaSeksiBoleh($konek, $event['seksi']))
                continue;
            if ($filter_seksi !== 'ALL' && strcasecmp($event['seksi'], $filter_seksi) !== 0)
                continue;
            $monthlyPlanRows[] = $event;
        }
    }
    usort($monthlyPlanRows, function ($a, $b) {
        return strcasecmp($a['seksi'], $b['seksi'])
            ?: strcmp($a['plan_date'], $b['plan_date'])
            ?: strcasecmp($a['nama_part'], $b['nama_part'])
            ?: strnatcasecmp($a['no_part'], $b['no_part']);
    });
    if ($isMonthlyPrint) {
        if ($monthlyDisplayFormat !== '1') {
            http_response_code(400);
            exit('Gunakan tampilan Jadwal Bulanan untuk mencetak Format Internal/External.');
        }
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
            <?= $isMonthlyReport ? 'Laporan Jadwal Bulanan' : 'Master Data'?> | QA System
        </title>
        <link rel="icon" type="image/png" href="/qa_kalibrasi/images/favicon-toto-qa.png?v=1">
        <link href="../../../css/bootstrap.min.css" rel="stylesheet">
        <link href="../../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet">
        <style>
            body {
                padding-top: 80px;
                background: #fff
            }
            .filter-box {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 1px 3px rgba(0,0,0,.05)
            }
            .table-wrap {
                overflow: auto
            }
            .table-jadwal {
                width: 100%;
                border-collapse: collapse;
                border: 1px solid #aaa;
                font-family: Arial,sans-serif;
                font-size: 12px
            }
            .table-jadwal th {
                border: 1px solid #aaa;
                padding: 7px;
                text-align: center;
                vertical-align: middle;
                background: #e2e6ea;
                color: #000;
                font-weight: bold;
                white-space: nowrap
            }
            .table-jadwal td {
                border: 1px solid #aaa;
                padding: 6px;
                vertical-align: middle;
                text-align: center;
                color: #000
            }
            .text-left {
                text-align: left!important
            }
            .tr-seksi {
                background: #d6d8db;
                font-weight: bold;
                border-top: 2px solid #555
            }
            .r-row {
                background: #fff9e6
            }
            .annual-matrix { width: 100%; table-layout: auto; }
            .annual-matrix th, .annual-matrix td { padding: 5px 8px; font-size: 11px; white-space: nowrap; }
            .annual-matrix .annual-section { min-width: 44px; white-space: nowrap; }
            .annual-month-heading { margin-top: 20px; margin-bottom: 0; font-weight: bold; }
            .annual-month-heading { scroll-margin-top: 85px; }
            .annual-month-shortcuts { display: flex; flex-wrap: wrap; gap: 6px; margin: 12px 0; }
            .annual-month-shortcuts a { display: inline-block; padding: 5px 10px; border: 1px solid #337ab7; border-radius: 4px; color: #337ab7; background: #fff; text-decoration: none; }
            .annual-month-shortcuts a:hover, .annual-month-shortcuts a:focus { color: #fff; background: #337ab7; }
            .annual-matrix .r-row, .annual-matrix .h-row, .annual-matrix .b-row { background: #fff; }
            .annual-matrix .annual-month-total { background: #edf2f6; font-weight: bold; }
            .annual-matrix .annual-group-title th { background: #dbe5ec; text-align: left; padding: 8px; }
            .annual-matrix .annual-group-total th { background: #f3f6f8; }
            .h-row {
                background: #eef8ee
            }
            .b-row {
                background: #fdecec
            }
            .negative-row td {
                background: #f4cccc!important;
                color: #990000!important;
                font-weight: 600
            }
            .holiday-col,.table-jadwal th.holiday-col,.table-jadwal td.holiday-col {
                background: rgba(220,53,69,.50)!important;
                color: #000!important
            }
            .mini-badge {
                font-size: 10px;
                padding: 3px 6px;
                border-radius: 3px;
                display: inline-block
            }
            .btn-validate-report {
                margin-left: 8px
            }
            .report-status {
                font-size: 12px;
                font-weight: bold
            }
            .sticky-first {
                position: sticky;
                left: 0;
                background: #fff;
                z-index: 2
            }
            .sticky-second {
                position: sticky;
                left: 42px;
                background: #fff;
                z-index: 2
            }
            @media print {
                .no-print {
                    display: none!important
                }
                .print-area {
                    display: block!important
                }
                .table-jadwal {
                    font-size: 9px
                }
                .table-jadwal th,.table-jadwal td {
                    padding: 3px
                }
            }
            .print-area {
                display: none
            }
        </style>
        <?php if ($isMonthlyReport): ?>
        <style>
            body {
                background: #f4f6f9
            }
            .monthly-controls h3 {
                margin-top: 0;
                font-weight: 700;
                color: #1e3f66
            }
            .monthly-filters {
                display: flex;
                flex-wrap: wrap;
                align-items: flex-end;
                gap: 12px
            }
            .monthly-filters .form-group {
                margin: 0;
                min-width: 150px
            }
            .monthly-filters .form-group:first-child {
                min-width: 240px
            }
            .monthly-filters .monthly-format-select {
                min-width: 295px
            }
            .monthly-filters .btn {
                height: 34px
            }
            #monthlyPrintHelp {
                margin: 10px 0 0
            }
            #monthlyFilterNotice {
                margin-top: 10px
            }
            .monthly-sheet {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 25px
            }
            .monthly-heading {
                text-align: center;
                margin-bottom: 18px
            }
            .monthly-heading h2 {
                font-size: 20px;
                font-weight: 700;
                margin: 8px 0
            }
            .monthly-sheet .table-jadwal {
                min-width: 1200px
            }
            .monthly-sheet [hidden] {
                display: none!important
            }
            .monthly-preview-frame {
                width: 100%;
                height: 1040px;
                border: 1px solid #ddd;
                background: #fff;
                display: block
            }
            .monthly-format1 .section-table {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
                margin-bottom: 18px;
                font-size: 14px
            }
            .monthly-format1 .section-table th,.monthly-format1 .section-table td {
                border: 1px solid #000;
                padding: 10px 8px;
                vertical-align: middle;
                overflow-wrap: anywhere
            }
            .monthly-format1 .section-table th {
                text-align: center;
                font-weight: normal;
                height: 48px
            }
            .monthly-format1 .section-table .section-label {
                text-align: left;
                height: auto;
                padding: 8px
            }
            .monthly-format1 .number,.monthly-format1 .planned-date {
                text-align: center
            }
            .monthly-format1 .planned-date {
                font-weight: bold;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact
            }
            .monthly-format1 .planned-date.status-overdue {
                background: #f8d7da;
                color: #842029
            }
            .monthly-format1 .planned-date.status-today {
                background: #cfe2ff;
                color: #084298
            }
            .monthly-format1 .planned-date.status-scheduled {
                background: #d1e7dd;
                color: #0f5132
            }
            .monthly-format1 .notice {
                padding: 12px;
                border: 1px solid #bbb
            }
            .monthly-format1 .error {
                color: #a94442
            }
            @media(max-width:600px) {
                .monthly-report-page>.container-fluid {
                    padding: 0 12px!important
                }
                .monthly-filters .form-group,.monthly-filters .form-group:first-child,.monthly-filters .monthly-format-select {
                    width: 100%;
                    min-width: 0
                }
                .monthly-sheet {
                    padding: 12px
                }
            }
            @page {
                size: A4 portrait;
                margin: 0
            }
            @media print {
                html,body {
                    margin: 0!important;
                    padding: 0!important;
                    background: #fff;
                    color: #000
                }
                .no-print {
                    display: none!important
                }
                .monthly-report-page>.container-fluid {
                    padding: 0!important
                }
                .monthly-sheet {
                    padding: 12mm;
                    margin: 0;
                    border: 0;
                    border-radius: 0;
                    box-sizing: border-box
                }
                .monthly-heading {
                    margin-bottom: 4mm;
                    break-after: avoid
                }
                .monthly-heading h2 {
                    font-size: 12pt
                }
                .monthly-heading p {
                    margin: 0 0 2mm
                }
                .monthly-sheet .table-wrap {
                    overflow: visible
                }
                .monthly-sheet .table-jadwal {
                    width: 100%;
                    min-width: 0;
                    table-layout: fixed;
                    font-size: 7pt
                }
                .monthly-sheet .table-jadwal th,.monthly-sheet .table-jadwal td {
                    padding: 1mm .4mm;
                    border-color: #555;
                    white-space: normal;
                    overflow-wrap: anywhere;
                    font-size: 7pt!important
                }
                .monthly-sheet thead {
                    display: table-header-group
                }
                .monthly-sheet .table-jadwal thead tr:nth-child(2) th {
                    font-size: 6pt!important;
                    white-space: nowrap
                }
                .monthly-sheet tr {
                    break-inside: avoid;
                    page-break-inside: avoid
                }
                .monthly-sheet .r-row,.monthly-sheet .tr-seksi {
                    break-after: avoid;
                    page-break-after: avoid
                }
                .monthly-sheet .table-jadwal thead tr:first-child th:nth-child(1) {
                    width: 3%
                }
                .monthly-sheet .table-jadwal thead tr:first-child th:nth-child(2) {
                    width: 14%
                }
                .monthly-sheet .table-jadwal thead tr:first-child th:nth-child(3) {
                    width: 7%
                }
                .monthly-sheet .table-jadwal thead tr:first-child th:nth-child(4) {
                    width: 3%
                }
                .monthly-sheet .table-jadwal thead tr:first-child th:nth-last-child(2) {
                    width: 5%
                }
                .monthly-sheet .table-jadwal thead tr:first-child th:last-child {
                    width: 10%
                }
                .monthly-sheet .r-row {
                    background: #fff9e6!important
                }
                .monthly-sheet .h-row {
                    background: #eef8ee!important
                }
                .monthly-sheet .tr-seksi {
                    background: #d6d8db!important
                }
                .monthly-sheet {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact
                }
            }
        </style>
        <?php endif; ?>
    </head>
    <body>
<?php $calendarYear = (int)$selectedYear;
$calendarCheck = mysqli_query($konek, "SELECT COUNT(*) AS jumlah FROM qa_tanggal_merah WHERE tanggal BETWEEN '$calendarYear-01-01' AND '$calendarYear-12-31'");
$calendarRow = $calendarCheck?mysqli_fetch_assoc($calendarCheck):null;
if (!$calendarRow || !(int)$calendarRow['jumlah']): ?>
<div class="alert alert-warning" style="margin:20px">Tanggal merah tahun <?= h($calendarYear)?> belum diisi. Jadwal sementara hanya menghindari Sabtu/Minggu; lengkapi Custom Tanggal Merah.</div>
<?php endif; ?>
        <div class="<?= $isMonthlyReport ? 'monthly-report-page' : 'no-print'?>">
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
                            <a href="<?= $isMonthlyReport ? '../../../media.php' : '../kalibrasi/cek_alat.php'?>">
                                <i class="fa fa-arrow-left">
                                </i>
                                <?= $isMonthlyReport ? 'Menu QA' : 'Kembali ke Cek Alat'?>
                            </a>
                        </li>
                    </ul>
                    <ul class="nav navbar-nav navbar-right">
                        <li>
                            <a href="#">
                                <i class="fa fa-user">
                                </i>
                                <?= h($nama_petugas)?>
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
                                <option value="<?= h($section)?>" <?= $filter_seksi === $section?'selected':''?>><?= h($section)?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="monthlyMonth">Bulan</label>
                            <select class="form-control" id="monthlyMonth" name="bulan">
                                <?php foreach ($monthNames as $number => $monthName): ?>
                                <option value="<?= $number?>" <?= $selectedMonth === $number?'selected':''?>><?= h($monthName)?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="monthlyYear">Tahun</label>
                            <input type="number" class="form-control" id="monthlyYear" name="tahun" min="1900" max="9999" step="1" value="<?= $selectedYear?>" required>
                        </div>
                        <div class="form-group monthly-format-select">
                            <label for="monthlyPrintFormat">Format tampilan &amp; cetak</label>
                            <select class="form-control" id="monthlyPrintFormat" name="format">
                                <?php if (qaRbacCan($konek, qaRbacMonthlyPermission('1'))): ?>
                                <option value="1" <?= $monthlyDisplayFormat === '1'?'selected':''?>>Format 1 — Daftar Rencana Kalibrasi</option>
                                <?php endif; ?>
                                <?php if (qaRbacCan($konek, qaRbacMonthlyPermission('2'))): ?>
                                <option value="2" <?= $monthlyDisplayFormat === '2'?'selected':''?>>Format 2 — Laporan Bulanan Internal QA</option>
                                <?php endif; ?>
                                <?php if (qaRbacCan($konek, qaRbacMonthlyPermission('3'))): ?>
                                <option value="3" <?= $monthlyDisplayFormat === '3'?'selected':''?>>Format 3 — Laporan Bulanan Seksi</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <button class="btn btn-primary" type="submit" id="monthlyPrintButton"><i class="fa fa-eye"></i> Tampilkan</button>
                        <button class="btn btn-success" type="button" id="monthlyPrintNowButton" onclick="qaPrintMonthlySelected()"><i class="fa fa-print"></i> Print</button>
                    </form>
                    <?php if ($monthlyDisplayFormat === '1' && !$monthlyPlanRows && !$monthlyReportOptions['errors']): ?>
                    <p class="text-muted">Tidak ada rencana kalibrasi untuk Format 1 pada seksi dan periode ini.</p>
                    <?php endif; ?>
                    <p id="monthlyFilterNotice" class="text-warning" hidden>Filter atau format berubah. Klik Tampilkan untuk memuat tampilan terbaru.</p>
                    <?php if ($monthlyReportOptions['errors']): ?>
                    <div class="alert alert-danger" role="alert"><?= h(implode(' ', $monthlyReportOptions['errors']))?></div>
                    <?php endif; ?>
                </div>
                <section class="monthly-sheet" aria-label="Jadwal kalibrasi bulanan">
                    <?php if ($monthlyDisplayFormat === '1'): ?>
                    <header class="monthly-heading">
                        <div>PT. SURYA TOTO INDONESIA Tbk.</div>
                        <h2><?= $monthlyDisplayFormat === '2' ? 'LAPORAN BULANAN INTERNAL QA' : ($monthlyDisplayFormat === '3' ? 'LAPORAN BULANAN SEKSI' : 'DAFTAR RENCANA KALIBRASI BULANAN') ?></h2>
                        <p><?= h($monthlyTitle)?> &middot; Seksi: <?= h($filter_seksi === 'ALL'?'Semua Seksi':$filter_seksi)?></p>
                    </header>
                    <?php endif; ?>
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
                                <?php foreach ($seksiOptions as $s):?>
                                    <option value="<?= h($s)?>" <?= $filter_seksi === $s?'selected':''?>>
                                        <?= h($s)?>
                                    </option>
                                <?php endforeach;?>
                            </select>
                        </div>
                            <div class="form-group" style="margin-right:12px">
                            <label style="margin-right:5px">
                                Status:
                            </label>
                            <select class="form-control" name="status">
                                <?php foreach ($statusOptions as $k => $v):?>
                                    <option value="<?= h($k)?>" <?= $filter_status === $k?'selected':''?>>
                                        <?= h($v)?>
                                    </option>
                                <?php endforeach;?>
                            </select>
                        </div>
                            <div class="form-group" style="margin-right:12px">
                            <label style="margin-right:5px">
                                Tampilan:
                            </label>
                            <select class="form-control" name="waktu" id="filter_waktu">
                                <option value="daftar" <?= $filter_waktu === 'daftar'?'selected':''?>>
                                    Daftar No. Alat Ukur
                                </option>
                                <option value="masalah" <?= $filter_waktu === 'masalah'?'selected':''?>>
                                    Alat Non-OK / Bermasalah
                                </option>
                                <option value="harian" <?= $filter_waktu === 'harian'?'selected':''?>>
                                    Jadwal Kalibrasi Harian
                                </option>
                                <option value="bulanan" <?= $filter_waktu === 'bulanan'?'selected':''?>>
                                    Jadwal Kalibrasi Bulanan
                                </option>
                                <option value="tahunan" <?= $filter_waktu === 'tahunan'?'selected':''?>>
                                    Jadwal Kalibrasi Tahunan
                                </option>
                            </select>
                        </div>
                            <div class="form-group view-date" data-view="harian" style="margin-right:12px;display:none">
                            <input type="date" class="form-control" name="tgl_harian" value="<?= h($val_harian)?>">
                        </div>
                            <div class="form-group view-date" data-view="bulanan" style="margin-right:12px;display:none">
                            <input type="month" class="form-control" name="tgl_bulanan" value="<?= h($val_bulanan)?>">
                        </div>
                            <div class="form-group view-date" data-view="tahunan" style="margin-right:12px;display:none">
                            <select class="form-control" name="tgl_tahunan">
                                <?php for ($y = (int)date('Y') - 5;$y <= (int)date('Y') + 2;$y++):?>
                                    <option value="<?= $y?>" <?= $val_tahunan == (string)$y?'selected':''?>>
                                        <?= $y?>
                                    </option>
                                <?php endfor;?>
                            </select>
                        </div>
                        <button class="btn btn-primary" type="submit">
                            <i class="fa fa-search">
                            </i>  Tampilkan
                        </button>
                        <a class="btn btn-danger" href="?waktu=masalah&seksi=<?= urlencode($filter_seksi)?>">
                            <i class="fa fa-exclamation-triangle">
                            </i>  Alat Non-OK
                            <span style="margin-left:5px;background:#fff;color:#c9302c;">
                            </span>
                        </a>
                    </form>
                    </div>
                <?php endif; ?>
                <?php if ($filter_waktu === 'daftar'): ?>
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
                                <?php
                                $lastSection = '';
                    $no = 0;
                    foreach ($masterRows as $r):
                        // Saat satu seksi dipilih, seluruh baris yang lolos filter
                        // wajib berada di satu grup meski data migrasi punya varian byte.
                        $sec = $filter_seksi !== 'ALL' ? $filter_seksi : qaSectionKey($r['seksi_pemilik'] ?? '');
                        if ($sec !== $lastSection) {
                            $lastSection = $sec;
                            $no = 0; // Nomor dimulai kembali setiap seksi seperti versi terbaru.
                            echo '<tr class="tr-seksi"><td colspan="10" class="text-left">SEKSI: ' . h(strtoupper($sec)) . '</td></tr>';
                        }
                        $no++;
                        $negative = isNegativeMasterStatus($r['_display_status'] ?? '');
                        $noAsset = masterColValue($r, ['no_asset', 'no_aset', 'no_fa'], '~');
                        $fungsi = masterColValue($r, ['fungsi'], '');
                        if ($fungsi === '' && ($r['_table'] ?? 'qa_part') !== 'qa_part') {
                            $fungsi = $fungsiAktifByNo[strtoupper(originalNoPartFromArchive((string)($r['no_part'] ?? '')))] ?? '';
                        }
                        // Kalibrator adalah alat referensi (mis. Micrometer), bukan petugas.
                        $kalibrator = masterColValue($r, ['kalibrator'], '');
                        $res = trim((string)($r['resolusi'] ?? ''));
                        $kls = trim((string)($r['kls'] ?? ''));
                        $resKls = trim($res . ($kls !== ''?' / ' . $kls:''));
                        if ($resKls === '')
                            $resKls = '~';
                        $hasilKondisi = strtoupper(trim((string)($r['_display_status'] ?? '')));
                        $noJenis = (string)($r['no_part'] ?? '');
                        if (($r['_table'] ?? 'qa_part') !== 'qa_part') {
                            $noJenis = originalNoPartFromArchive($noJenis);
                        }
                        $jenisTerakhir = $approvedByNo[$noJenis] ?? ($approvedByNo[strtoupper(trim($noJenis))] ?? null);
                        $statusTampil = qaMasterResultLabel('OK', $r['jenis_laporan'] ?? '', $jenisTerakhir['jenis_laporan'] ?? '');
                        if ($statusTampil !== 'V') {
                            $statusTampil = 'O';
                        }
                        $ket = $hasilKondisi === 'OK' ? '' : $hasilKondisi;
                        ?>
                                <tr class="<?= $negative?'negative-row':''?>">
                                    <td>
                                        <?= $no?>
                                    </td>
                                    <td class="text-left">
                                        <?= h($r['nama_part'])?>
                                    </td>
                                    <td class="text-left">
                                        <?= h($r['spesifikasi'] ?? ($r['ukuran'] ?? ''))?>
                                    </td>
                                    <td class="text-left">
                                        <b>
                                            <?= h($r['no_part'])?>
                                        </b>
                                    </td>
                                    <td>
                                        <?= h($resKls)?>
                                    </td>
                                    <td>
                                        <?= h($noAsset)?>
                                    </td>
                                    <td class="text-left">
                                        <?= h($fungsi)?>
                                    </td>
                                    <td>
                                        <span class="label label-default">
                                            <?= h($statusTampil)?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= h($kalibrator)?>
                                    </td>
                                    <td class="text-center">
                                        <?= h($ket)?>
                                    </td>
                                </tr>
                            <?php endforeach;
                    if ($no === 0):?>
                            <tr>
                                    <td colspan="10" class="text-center">
                                    Tidak ada data.
                                </td>
                                </tr>
                        <?php endif;?>
                    </tbody>
                        </table>
                    </div>
        <?php elseif ($filter_waktu === 'masalah'): ?>
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
                                STATUS
                            </th>
                                    <th>
                                KALIBRATOR
                            </th>
                                    <th>
                                KETERANGAN
                            </th>
                                </tr>
                            </thead>
                            <tbody>
                        <?php $lastSection = '';
            $no = 0;
            foreach ($problemRows as $r):$no++;
                $sec = (string)($r['seksi_pemilik'] ?? '');
                if ($sec !== $lastSection) {
                    $lastSection = $sec;
                    echo '<tr class="tr-seksi"><td colspan="10" class="text-left">SEKSI: ' . h(strtoupper($sec)) . '</td></tr>';
                }
                $noAsset = masterColValue($r, ['no_asset', 'no_aset', 'no_fa'], '~');
                $fungsi = masterColValue($r, ['fungsi'], '');
                if ($fungsi === '' && ($r['_table'] ?? 'qa_part') !== 'qa_part') {
                    $fungsi = $fungsiAktifByNo[strtoupper(originalNoPartFromArchive((string)($r['no_part'] ?? '')))] ?? '';
                }
                $kalibrator = masterColValue($r, ['kalibrator'], '');
                $res = trim((string)($r['resolusi'] ?? ''));
                $kls = trim((string)($r['kls'] ?? ''));
                $resKls = trim($res . ($kls !== '' ? ' / ' . $kls : ''));
                if ($resKls === '') {
                    $resKls = '~';
                }
                $noJenis = (string)($r['no_part'] ?? '');
                if (($r['_table'] ?? 'qa_part') !== 'qa_part') {
                    $noJenis = originalNoPartFromArchive($noJenis);
                }
                $jenisTerakhir = $approvedByNo[$noJenis] ?? ($approvedByNo[strtoupper(trim($noJenis))] ?? null);
                $statusTampil = qaMasterResultLabel('OK', $r['jenis_laporan'] ?? '', $jenisTerakhir['jenis_laporan'] ?? '');
                if ($statusTampil !== 'V') {
                    $statusTampil = 'O';
                }
                $ket = strtoupper(trim((string)($r['_display_status'] ?? ''))); ?>
                        <tr class="negative-row">
                                    <td>
                                <?= $no?>
                            </td>
                                    <td class="text-left">
                                <?= h($r['nama_part'])?>
                            </td>
                                    <td class="text-left">
                                <?= h($r['spesifikasi'] ?? ($r['ukuran'] ?? ''))?>
                            </td>
                                    <td class="text-left">
                                <b>
                                    <?= h($r['no_part'])?>
                                </b>
                            </td>
                                    <td>
                                <?= h($resKls)?>
                            </td>
                                    <td>
                                <?= h($noAsset)?>
                            </td>
                                    <td class="text-left">
                                <?= h($fungsi)?>
                            </td>
                                    <td>
                                <span class="label label-default">
                                    <?= h($statusTampil)?>
                                </span>
                            </td>
                                    <td>
                                <?= h($kalibrator)?>
                            </td>
                                    <td>
                                <?= h($ket)?>
                            </td>
                                </tr>
                    <?php endforeach;
            if ($no === 0):?>
                    <tr>
                                    <td colspan="10" class="text-center">
                            Tidak ada alat bermasalah / Non-OK.
                        </td>
                                </tr>
                <?php endif;?>
            </tbody>
                        </table>
                    </div>
<?php elseif ($filter_waktu === 'harian'): ?>
    <div class="filter-box">
        <b>
            JADWAL KALIBRASI HARIAN
            <?= h(formatMasterDailyDate($val_harian))?>
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
                <?php $last = '';
    $n = 0;
    foreach ($reportRows as $r):$n++;
        if ($r['seksi'] !== $last) {
            $last = $r['seksi'];
            echo '<tr class="tr-seksi"><td colspan="11" class="text-left">SEKSI: ' . h(strtoupper($last)) . '</td></tr>';
        }
        $actualBadge = formatMasterDailyDate($r['aktual']);
        $hasil = $r['hasil'] !== ''?$r['hasil']:'-';
        $dailyStatus = $r['status'] ?? ($hasil !== '-'?$hasil:'');
        $negative = isNegativeMasterStatus($dailyStatus);?>
                <tr class="<?= $negative?'negative-row':''?>">
                                    <td>
                        <?= $n?>
                    </td>
                                    <td class="text-left">
                        <?= h($r['nama_part'])?>
                    </td>
                                    <td class="text-left">
                        <?= h($r['spesifikasi'])?>
                    </td>
                                    <td>
                        <b>
                            <?= h($r['no_part'])?>
                        </b>
                    </td>
                                    <td>
                        <?= h($r['resolusi'])?>
                    </td>
                                    <td>
                        <?= h($r['periode'])?>
                    </td>
                                    <td>
                        <?= h(formatMasterDailyDate($r['rencana']))?>
                    </td>
                                    <td>
                        <?= h($actualBadge)?>
                    </td>
                                    <td>
                        <?= h($hasil)?>
                    </td>
                                    <td>
                        <?= h(isNegativeMasterStatus($dailyStatus) ? '-' : formatMasterDailyDate($r['next']))?>
                    </td>
                                    <td>
                        <?= h($r['petugas'])?>
                    </td>
                                </tr>
            <?php endforeach;
    if ($n === 0):?>
            <tr>
                                    <td colspan="11" class="text-center">
                    Tidak ada data jadwal untuk tanggal ini.
                </td>
                                </tr>
        <?php endif;?>
    </tbody>
                        </table>
                    </div>
<?php elseif ($filter_waktu === 'bulanan'): ?>
    <?php if ($isMonthlyReport): ?>
    <?php if ($monthlyDisplayFormat === '1'): ?>
    <div id="monthlyFormat1" class="monthly-format1">
        <?php require __DIR__ . '/../jadwal_bulanan/jadwal_bulanan_format1_tabel.php'; ?>
    </div>
    <?php else: ?>
    <?php
       $monthlyFrameParams = [
           'bulan' => $val_bulanan,
           'seksi' => $filter_seksi,
           'status' => 'ALL',
           'mode' => $monthlyDisplayFormat === '2' ? 'internal' : 'external',
           'embed' => '1'
       ];
        ?>
    <iframe class="monthly-preview-frame" id="monthlyPreviewFrame" title="Pratinjau <?= $monthlyDisplayFormat === '2' ? 'Laporan Bulanan Internal QA' : 'Laporan Bulanan Seksi' ?>" src="../jadwal_bulanan/cetak_jadwal_bulanan.php?<?= h(http_build_query($monthlyFrameParams))?>"></iframe>
    <?php endif; ?>
    <?php else: ?>
    <div class="filter-box">
        <b>
            JADWAL KALIBRASI BULANAN
            <?= h(strtoupper(date('F Y', strtotime($val_bulanan . '-01'))))?>
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
                    <?php for ($d = 1;$d <= $daysInMonth;$d++):$monthDate = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $d);
                        $offClass = holidayClassForDate($monthDate, $holidayDates);
                        $offTitle = holidayTitleForDate($monthDate, $holidayDates);?>
                    <th class="<?= h($offClass)?>" title="<?= h($offTitle)?>">
                        <?= $d?>
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
                <?php for ($d = 1;$d <= $daysInMonth;$d++):$monthDate = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $d);
                    $offClass = holidayClassForDate($monthDate, $holidayDates);
                    $offTitle = holidayTitleForDate($monthDate, $holidayDates);?>
                <th class="<?= h($offClass)?>" style="font-size:10px" title="<?= h($offTitle)?>">
                    TGL
                </th>
            <?php endfor;?>
        </tr>
                            </thead>
                            <tbody>
        <?php $n = 0;
        $last = '';
        foreach ($reportRows as $g):$n++;
            if ($g['seksi'] !== $last) {
                $last = $g['seksi'];
                echo '<tr class="tr-seksi"><td colspan="' . ($daysInMonth + 6) . '" class="text-left">SEKSI: ' . h(strtoupper($last)) . '</td></tr>';
            }
            $r = $g['R'];
            $hh = $g['H'];?>
        <tr class="r-row">
                                    <td rowspan="2">
                <?= $n?>
            </td>
                                    <td rowspan="2" class="text-left">
                <?= h($g['nama_part'])?>
            </td>
                                    <td rowspan="2">
                <?= h($g['seksi'])?>
            </td>
                                    <td>
                <b>
                    R
                </b>
            </td>
            <?php for ($d = 1;$d <= $daysInMonth;$d++):$monthDate = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $d);
                $offClass = holidayClassForDate($monthDate, $holidayDates);?>
            <td class="<?= h($offClass)?>">
                <?= ((int)($r[$d] ?? 0))?:''?>
            </td>
        <?php endfor;?>
        <td>
            <b>
                <?= $g['totalR']?>
            </b>
        </td>
                                    <td rowspan="2">
            <?= h($g['petugas']?:'-') ?>
        </td>
                                </tr>
                                <tr class="h-row">
                                    <td>
            <b>
                H
            </b>
        </td>
        <?php for ($d = 1;$d <= $daysInMonth;$d++):$monthDate = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $d);
            $offClass = holidayClassForDate($monthDate, $holidayDates);?>
        <td class="<?= h($offClass)?>">
            <?= ((int)($hh[$d] ?? 0))?:''?>
        </td>
    <?php endfor;?>
    <td>
        <b>
            <?= $g['totalH']?>
        </b>
    </td>
                                </tr>
<?php endforeach;
        if ($n === 0):?>
<tr>
                                    <td colspan="<?= ($daysInMonth + 6)?>" class="text-center">
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
    = Rencana sesuai periode kalibrasi, digeser ke hari kerja bila jatuh pada hari libur.
    <b>
        H
    </b>
    = Hasil/Aktual pada tanggal pemeriksaan.
</div>
<?php endif; ?>
<?php elseif ($filter_waktu === 'tahunan'): ?>
    <div class="filter-box"><b>JADWAL KALIBRASI TAHUNAN <?= h($val_tahunan) ?></b></div>
    <?php
        $annualMonthNames = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        $annualSectionLabels = [];
        $usedLabels = [];
        foreach ($annualSections as $section) {
            $words = preg_split('/\s+/', strtoupper(trim((string)$section)));
            $lastWord = end($words);
            if (ctype_digit((string)$lastWord) && count($words) > 1) {
                $label = substr($words[0], 0, 1) . substr((string)$lastWord, 0, 2);
            } elseif (count($words) > 1) {
                $label = substr(implode('', array_map(function ($word) { return substr($word, 0, 1); }, $words)), 0, 3);
            } else {
                $label = substr($words[0] ?? '', 0, 3);
            }
            $label = $label ?: 'S';
            for ($suffix = 1; isset($usedLabels[$label]); $suffix++) {
                $code = strtoupper(base_convert($suffix, 10, 36));
                $label = substr($words[0] ?? 'S', 0, max(0, 3 - strlen($code))) . $code;
            }
            $usedLabels[$label] = true;
            $annualSectionLabels[$section] = $label;
        }
    ?>
    <nav class="annual-month-shortcuts no-print" aria-label="Loncat ke bulan jadwal tahunan">
        <?php foreach ($annualMonthNames as $month => $monthName): ?>
            <a href="#annual-month-<?= (int)$month ?>" data-annual-month="<?= (int)$month ?>"><?= h($monthName) ?></a>
        <?php endforeach; ?>
    </nav>
    <?php foreach ($annualMonthNames as $month => $monthName):
        $monthSections = [];
        foreach ($annualSections as $section) {
            foreach ($annualTypeLabels as $type => $label) {
                foreach ($reportRows[$type] as $group) {
                    if (!empty($group['R'][$month][$section]) || !empty($group['H'][$month][$section])) {
                        $monthSections[] = $section;
                        break 2;
                    }
                }
            }
        }
        $monthRows = [];
        foreach ($annualTypeLabels as $type => $label) {
            $monthRows[$type] = array_values(array_filter($reportRows[$type], function ($group) use ($month, $monthSections) {
                foreach ($monthSections as $section) {
                    if (!empty($group['R'][$month][$section]) || !empty($group['H'][$month][$section])) {
                        return true;
                    }
                }
                return false;
            }));
        }
    ?>
    <div class="annual-month-heading" id="annual-month-<?= (int)$month ?>"><?= h(strtoupper($monthName)) ?> <?= h($val_tahunan) ?></div>
    <div class="table-wrap" style="margin-top:8px">
        <table class="table-jadwal annual-matrix">
            <thead>
                <tr>
                    <th>NO</th>
                    <th style="min-width:130px">NAMA ALAT</th>
                    <th>R/H/B</th>
                    <?php foreach ($monthSections as $section): ?>
                        <th class="annual-section" title="<?= h($section) ?>"><?= h($annualSectionLabels[$section]) ?></th>
                    <?php endforeach; ?>
                    <th class="annual-month-total">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($annualTypeLabels as $type => $label): ?>
                    <tr class="annual-group-title"><th colspan="<?= 4 + count($monthSections) ?>"><?= h($label) ?></th></tr>
                    <?php foreach ($monthRows[$type] as $index => $group): ?>
                        <?php foreach (['R', 'H', 'B'] as $code): ?>
                            <tr class="<?= strtolower($code) ?>-row">
                                <?php if ($code === 'R'): ?>
                                    <td rowspan="3"><?= $index + 1 ?></td>
                                    <td rowspan="3" class="text-left"><?= h($group['nama_part']) ?></td>
                                <?php endif; ?>
                                <td><b><?= $code ?></b></td>
                                <?php $monthTotal = 0; foreach ($monthSections as $section): ?>
                                    <?php $count = (int)($group[$code][$month][$section] ?? 0); $monthTotal += $count; ?>
                                    <td><?= $count ?: '' ?></td>
                                <?php endforeach; ?>
                                <td class="annual-month-total"><b><?= $monthTotal ?></b></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <tr class="annual-group-total">
                        <th colspan="3" class="text-left">TOTAL <?= h($label) ?></th>
                        <?php $monthTotal = 0; foreach ($monthSections as $section): ?>
                            <?php $count = (int)($annualTotalsByType[$type]['R'][$month][$section] ?? 0); $monthTotal += $count; ?>
                            <th><?= $count ?: '' ?></th>
                        <?php endforeach; ?>
                        <th><?= $monthTotal ?></th>
                    </tr>
                <?php endforeach; ?>
                <?php if (!array_filter($monthRows)): ?>
                    <tr><td colspan="<?= 4 + count($monthSections) ?>">Tidak ada data jadwal pada bulan ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
    <div style="margin-top:8px;font-size:12px;color:#666"><b>R</b> = Rencana, <b>H</b> = hasil aktual, <b>B</b> = selisih R-H (minimum 0). Baris TOTAL menghitung R per bulan. Arahkan kursor ke singkatan seksi untuk nama lengkap.</div>
<?php endif; ?>
<?php if ($isMonthlyReport): ?></section><?php endif; ?>
</div>
        </div>
        <script src="../../../js/jquery.js">
</script>
<?php if ($isMonthlyReport && !$isMonthlyPrint): ?>
<script>
            (function() {
                var form = document.getElementById('formFilter');
                var displayButton = document.getElementById('monthlyPrintButton');
                var initialValues = $(form).find('[name]').serialize();
                var formatSelect = document.getElementById('monthlyPrintFormat');
                var initialFormat = <?= json_encode($monthlyDisplayFormat)?>;
                var printNowButton = document.getElementById('monthlyPrintNowButton');
                var hasData = {
                    '1': true,
                    '2': true,
                    '3': true
                };

                function updateDisplayButton() {
                    var changed = $(form).find('[name]').serialize() !== initialValues || formatSelect.value !== initialFormat;
                    displayButton.disabled = !hasData[formatSelect.value];
                    if (printNowButton) printNowButton.style.display = changed ? 'none' : 'inline-block';
                    document.getElementById('monthlyFilterNotice').hidden = !changed;
                }
                $(form).on('input change', updateDisplayButton);
                $(formatSelect).on('change', updateDisplayButton);
                updateDisplayButton();
            })();

            function qaPrintMonthlySelected() {
                var frame = document.getElementById('monthlyPreviewFrame');
                if (frame) {
                    if (frame.contentWindow && typeof frame.contentWindow.qaPrintMonthlyReport === 'function') {
                        frame.contentWindow.qaPrintMonthlyReport();
                    } else if (frame.contentWindow) {
                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                    }
                    return;
                }
                qaPrintMonthlyFormat1();
            }

            function qaPrintMonthlyFormat1() {
                var oldTitle = document.title;
                document.title = '';
                var restore = function() {
                    document.title = oldTitle;
                    window.removeEventListener('afterprint', restore);
                };
                window.addEventListener('afterprint', restore);
                window.print();
                setTimeout(function() {
                    if (document.title === '') document.title = oldTitle;
                }, 1000);
            }
        </script>
<?php endif; ?>
<script src="../../../js/bootstrap.min.js">
</script>
        <script>
            (function() {
                $('.annual-month-shortcuts a').on('click', function(event) {
                    var target = document.getElementById('annual-month-' + this.getAttribute('data-annual-month'));
                    if (!target) return;
                    event.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
                function toggleDate() {
                    var v = $('#filter_waktu').val();
                    $('.view-date').hide();
                    $('.view-date[data-view="' + v + '"]').show();
                }
                toggleDate();
                $('#filter_waktu').on('change', function() {
                    toggleDate();
                });
            })();
        </script>
    </body>
</html>

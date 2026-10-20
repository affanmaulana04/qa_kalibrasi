<?php
session_start();
if (empty($_SESSION['login'])) {
    header('location: ../../../logout.php');
    exit();
}
require_once __DIR__ . '/../../../inc/inc_koneksi.php';
require_once __DIR__ . '/../../../inc/qa_rbac.php';
// Protected independently of the report page/iframe. No cross-format access via URL.
$requestedMode = strtolower(trim((string)($_GET['mode'] ?? 'both')));
if (!in_array($requestedMode,['internal','external','both'],true)) {
    http_response_code(400); exit('Format laporan tidak valid.');
}
if ($requestedMode === 'internal' || $requestedMode === 'both') {
    qaRbacRequire($konek,'jadwal_bulanan_internal');
}
if ($requestedMode === 'external' || $requestedMode === 'both') {
    qaRbacRequire($konek,'jadwal_bulanan_external');
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function originalNoPartFromArchive($no) {
    return preg_replace('/_(?:NOTGOOD|RUSAK|DISCONTINUE|DITARIK)_\d{10,14}$/i', '', (string)$no);
}

$bulan = $_GET['bulan'] ?? date('Y-m');
$seksi = $_GET['seksi'] ?? 'ALL';
$status = $_GET['status'] ?? 'ALL';
$autoprint = isset($_GET['autoprint']) && $_GET['autoprint'] === '1';
$embedded = isset($_GET['embed']) && $_GET['embed'] === '1';
$mode = strtolower(trim((string)($_GET['mode'] ?? 'both')));
if (!in_array($mode, ['internal','external','both'], true)) $mode = 'both';

if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) $bulan = date('Y-m');
$year = (int)substr($bulan, 0, 4);
$month = (int)substr($bulan, 5, 2);
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$monthStart = sprintf('%04d-%02d-01', $year, $month);
$monthEnd = date('Y-m-t', strtotime($monthStart));

$monthNames = [
    1=>'JANUARI', 2=>'FEBRUARI', 3=>'MARET', 4=>'APRIL', 5=>'MEI', 6=>'JUNI',
    7=>'JULI', 8=>'AGUSTUS', 9=>'SEPTEMBER', 10=>'OKTOBER', 11=>'NOVEMBER', 12=>'DESEMBER'
];
$periodeLabel = ($monthNames[$month] ?? strtoupper(date('F', mktime(0,0,0,$month,1)))) . ' ' . $year;
$namaPetugas = trim((string)($_SESSION['nama_lengkap'] ?? ($_SESSION['userid'] ?? '')));

$seksiOptions = [];
$qSeksi = mysqli_query($konek, "SELECT nama_seksi FROM qa_seksi_master ORDER BY nama_seksi ASC");
if ($qSeksi) {
    while ($r = mysqli_fetch_assoc($qSeksi)) $seksiOptions[] = $r['nama_seksi'];
}

/* Tanggal merah perusahaan + weekend. */
$holidayDates = [];
$qHoliday = @mysqli_query(
    $konek,
    "SELECT tanggal, keterangan FROM qa_tanggal_merah
     WHERE tanggal BETWEEN '$monthStart' AND '$monthEnd'
     ORDER BY tanggal ASC"
);
if ($qHoliday) {
    while ($r = mysqli_fetch_assoc($qHoliday)) {
        $d = substr((string)($r['tanggal'] ?? ''), 0, 10);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
            $holidayDates[$d] = trim((string)($r['keterangan'] ?? '')) ?: 'Tanggal Merah Perusahaan';
        }
    }
}

function holidayClassForDate($date, $holidayDates) {
    $ts = strtotime($date);
    $weekend = $ts !== false && (int)date('N', $ts) >= 6;
    return ($weekend || isset($holidayDates[$date])) ? 'holiday-col' : '';
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

/* Lookup master sebagai fallback bila transaksi lama belum punya snapshot JSON header. */
$masterByNo = [];
foreach (['qa_part','qa_alat_ng','qa_alat_rusak','qa_alat_discontinue','qa_alat_ditarik'] as $table) {
    $q = @mysqli_query($konek, "SELECT no_part,nama_part,seksi_pemilik FROM `$table`");
    if (!$q) continue;
    while ($r = mysqli_fetch_assoc($q)) {
        $no = (string)($r['no_part'] ?? '');
        if ($no === '') continue;
        $masterByNo[$no] = $r;
        if ($table !== 'qa_part') {
            $orig = originalNoPartFromArchive($no);
            if ($orig !== '') $masterByNo[$orig] = $r;
        }
    }
}

/*
 * Report bulan hasil meeting:
 * - registrasi tidak dihitung.
 * - setiap alat yang sudah Update Hasil Kalibrasi memberi R dan H pada tanggal aktual yang sama.
 * - satu alat dihitung sekali per tanggal walaupun disimpan/update ulang pada hari yang sama.
 */
$events = [];
$qActual = mysqli_query(
    $konek,
    "SELECT q.* FROM qa_pengecekan q
     WHERE q.tanggal_pengecekan BETWEEN '$monthStart' AND '$monthEnd'
       AND COALESCE(q.jenis_transaksi,'update_hasil_kalibrasi') <> 'registrasi_alat'
       AND (q.nomor_pengecekan IS NULL OR q.nomor_pengecekan NOT LIKE 'QA-REG-%')
     ORDER BY q.tanggal_pengecekan ASC, q.id ASC"
);
if ($qActual) {
    while ($q = mysqli_fetch_assoc($qActual)) {
        $no = trim((string)($q['no_part'] ?? ''));
        $actualDate = substr((string)($q['tanggal_pengecekan'] ?? ''), 0, 10);
        if ($no === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $actualDate)) continue;

        $nama = '';
        $sec = '';
        $snapshot = json_decode((string)($q['hasil_json'] ?? ''), true);
        if (is_array($snapshot) && isset($snapshot['header']) && is_array($snapshot['header'])) {
            $nama = trim((string)($snapshot['header']['nama_part'] ?? ''));
            $sec = trim((string)($snapshot['header']['seksi_pemilik'] ?? ''));
        }
        if (($nama === '' || $sec === '') && isset($masterByNo[$no])) {
            if ($nama === '') $nama = trim((string)($masterByNo[$no]['nama_part'] ?? ''));
            if ($sec === '') $sec = trim((string)($masterByNo[$no]['seksi_pemilik'] ?? ''));
        }
        if ($nama === '') $nama = $no;
        if ($sec === '') $sec = '-';

        if ($seksi !== 'ALL' && strtoupper($sec) !== strtoupper($seksi)) continue;
        $hasil = strtoupper(trim((string)($q['status_kalibrasi'] ?? '')));
        if ($status !== 'ALL' && $hasil !== strtoupper($status)) continue;

        $events[strtoupper($no).'|'.$actualDate] = [
            'no_part'=>$no,
            'nama_part'=>$nama,
            'seksi'=>$sec,
            'actual_date'=>$actualDate,
            'petugas'=>trim((string)($q['created_by'] ?? $q['operator'] ?? ''))
        ];
    }
}

$groups = [];
$isNextYearPlanning = $year === ((int)date('Y') + 1);
if ($isNextYearPlanning) {
    // Ambil tanggal jatuh tempo terakhir per nomor alat dari transaksi Update Hasil.
    // Tidak menghitung registrasi atau membuat rencana di tanggal tertentu.
    $latestDueByNo = [];
    $qDue = mysqli_query($konek, "SELECT q.no_part, q.periode_berikutnya, q.status_kalibrasi
        FROM qa_pengecekan q
        WHERE COALESCE(q.jenis_transaksi,'update_hasil_kalibrasi') <> 'registrasi_alat'
          AND (q.nomor_pengecekan IS NULL OR q.nomor_pengecekan NOT LIKE 'QA-REG-%')
        ORDER BY q.tanggal_pengecekan ASC, q.id ASC");
    if ($qDue) {
        while ($q = mysqli_fetch_assoc($qDue)) {
            $no = trim((string)($q['no_part'] ?? ''));
            $due = substr((string)($q['periode_berikutnya'] ?? ''), 0, 10);
            if ($no !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $due)) {
                $latestDueByNo[$no] = [
                    'tanggal'=>$due,
                    'status'=>strtoupper(trim((string)($q['status_kalibrasi'] ?? '')))
                ];
            }
        }
    }

    $qActive = mysqli_query($konek, "SELECT no_part, nama_part, seksi_pemilik FROM qa_part");
    if ($qActive) {
        while ($m = mysqli_fetch_assoc($qActive)) {
            $no = trim((string)($m['no_part'] ?? ''));
            if (!isset($latestDueByNo[$no])) continue;
            if (substr($latestDueByNo[$no]['tanggal'], 0, 7) !== $bulan) continue;
            $sec = trim((string)($m['seksi_pemilik'] ?? ''));
            $nama = trim((string)($m['nama_part'] ?? ''));
            if ($seksi !== 'ALL' && strtoupper($sec) !== strtoupper($seksi)) continue;
            if ($status !== 'ALL' && $latestDueByNo[$no]['status'] !== strtoupper($status)) continue;
            $gk = strtoupper($sec).'|'.strtoupper($nama);
            if (!isset($groups[$gk])) {
                $groups[$gk] = [
                    'seksi'=>$sec, 'nama_part'=>$nama,
                    'R'=>array_fill(1,$daysInMonth,0),
                    'H'=>array_fill(1,$daysInMonth,0),
                    'petugas'=>[], 'jumlah_rencana'=>0,
                    'future_plan'=>true
                ];
            }
            $groups[$gk]['jumlah_rencana']++;
        }
    }
} else {
foreach ($events as $e) {
    $gk = strtoupper($e['seksi']).'|'.strtoupper($e['nama_part']);
    if (!isset($groups[$gk])) {
        $groups[$gk] = [
            'seksi'=>$e['seksi'],
            'nama_part'=>$e['nama_part'],
            'R'=>array_fill(1,$daysInMonth,0),
            'H'=>array_fill(1,$daysInMonth,0),
            'petugas'=>[]
        ];
    }
    $d = (int)substr($e['actual_date'], 8, 2);
    if ($d >= 1 && $d <= $daysInMonth) {
        $groups[$gk]['R'][$d]++;
        $groups[$gk]['H'][$d]++;
    }
    if ($e['petugas'] !== '') $groups[$gk]['petugas'][] = $e['petugas'];
}
}
foreach ($groups as &$g) {
    $g['totalR'] = $isNextYearPlanning ? (int)($g['jumlah_rencana'] ?? 0) : array_sum($g['R']);
    $g['totalH'] = $isNextYearPlanning ? '' : array_sum($g['H']);
    $g['petugas'] = $isNextYearPlanning ? '' : implode(', ', array_values(array_unique(array_filter($g['petugas']))));
}
unset($g);
uasort($groups, function($a, $b) {
    return strtoupper($a['seksi'].$a['nama_part']) <=> strtoupper($b['seksi'].$b['nama_part']);
});
$reportRows = array_values($groups);

function renderReport($mode, $reportRows, $daysInMonth, $year, $month, $holidayDates, $periodeLabel, $seksi, $namaPetugas, $pageBreak = false) {
    $internal = $mode === 'internal';
    $titleMode = $internal ? 'INTERNAL' : 'EXTERNAL';
    $division = $seksi === 'ALL' ? 'SEMUA SEKSI' : strtoupper($seksi);
    $colspan = $daysInMonth + 6;
    ?>
    <section class="report-sheet <?= $pageBreak ? 'page-break' : '' ?>">
        <table class="schedule-table" style="--days:<?=$daysInMonth?>">
            <colgroup>
                <col class="col-no"><col class="col-name"><col class="col-section"><col class="col-type">
                <?php for ($colDay=1; $colDay<=$daysInMonth; $colDay++): ?><col class="col-day"><?php endfor; ?>
                <col class="col-total"><col class="col-officer">
            </colgroup>
            <thead>
                <tr class="report-banner-row">
                    <th colspan="<?=$colspan?>" class="report-heading-cell">
                        <div class="company-title">PT. SURYA TOTO INDONESIA Tbk.</div>
                        <div class="report-head">
                            <div class="head-spacer"></div>
                            <div class="head-title">
                                <div>JADWAL KALIBRASI BULANAN - <?=$titleMode?></div>
                                <div>DIVISI <?=h($division)?> <?=h($periodeLabel)?></div>
                            </div>
                            <div class="approval-wrap">
                                <div class="print-date">Tanggal, <?=h(date('d-m-Y'))?></div>
                                <table class="approval-table">
                                    <tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr>
                                    <tr><td><?=h($namaPetugas ?: '-')?></td><td>&nbsp;</td><td>&nbsp;</td></tr>
                                    <tr><td>PETUGAS</td><td>SUPERVISOR</td><td>MANAGER</td></tr>
                                </table>
                            </div>
                        </div>
                    </th>
                </tr>
                <tr>
                    <th rowspan="2" class="col-no">NO</th>
                    <th rowspan="2" class="col-name">NAMA ALAT</th>
                    <th rowspan="2" class="col-section">SEKSI</th>
                    <th rowspan="2" class="col-type">JENIS</th>
                    <th colspan="<?=$daysInMonth?>">TANGGAL</th>
                    <th rowspan="2" class="col-total">TOTAL</th>
                    <th rowspan="2" class="col-officer">PETUGAS</th>
                </tr>
                <tr>
                    <?php for ($d=1; $d<=$daysInMonth; $d++):
                        $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
                        $hc = holidayClassForDate($date, $holidayDates);
                        $ht = holidayTitleForDate($date, $holidayDates);
                    ?>
                    <th class="day <?=h($hc)?>" title="<?=h($ht)?>"><?=$d?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <?php if (!$reportRows): ?>
            <tbody>
                <tr><td colspan="<?=$colspan?>" class="empty-row"><?= $year === ((int)date('Y') + 1) ? 'Tidak ada alat yang jatuh tempo pada bulan ini.' : 'Tidak ada data Update Hasil Kalibrasi pada periode ini.' ?></td></tr>
            </tbody>
            <?php else: ?>
                <?php $no=0; $lastSection=null; foreach ($reportRows as $g): $no++; ?>
                <tbody class="tool-group">
                        <?php if ($lastSection !== $g['seksi']): $lastSection=$g['seksi']; ?>
                        <tr class="section-row"><td colspan="<?=$colspan?>">SEKSI: <?=h(strtoupper($lastSection))?></td></tr>
                        <?php endif; ?>

                        <?php if ($internal): ?>
                        <tr>
                            <td rowspan="2"><?=$no?></td>
                            <td rowspan="2" class="text-left tool-name"><?=h($g['nama_part'])?></td>
                            <td rowspan="2" class="tool-section"><?=h($g['seksi'])?></td>
                            <td><b>R</b></td>
                            <?php for ($d=1; $d<=$daysInMonth; $d++):
                                $date=sprintf('%04d-%02d-%02d',$year,$month,$d);
                                $hc=holidayClassForDate($date,$holidayDates);
                                $v=(int)($g['R'][$d]??0);
                            ?><td class="<?=h($hc)?>"><?=$v ?: '&nbsp;'?></td><?php endfor; ?>
                            <td><b><?=$g['totalR']?></b></td>
                            <td rowspan="2"><?= !empty($g['future_plan']) ? '&nbsp;' : h($g['petugas'] ?: '-') ?></td>
                        </tr>
                        <tr>
                            <td><b>H</b></td>
                            <?php for ($d=1; $d<=$daysInMonth; $d++):
                                $date=sprintf('%04d-%02d-%02d',$year,$month,$d);
                                $hc=holidayClassForDate($date,$holidayDates);
                                $v=(int)($g['H'][$d]??0);
                            ?><td class="<?=h($hc)?>"><?=$v ?: '&nbsp;'?></td><?php endfor; ?>
                            <td><b><?=$g['totalH']?></b></td>
                        </tr>
                        <?php else: ?>
                        <tr>
                            <td rowspan="2"><?=$no?></td>
                            <td rowspan="2" class="text-left tool-name"><?=h($g['nama_part'])?></td>
                            <td rowspan="2" class="tool-section"><?=h($g['seksi'])?></td>
                            <td><b>R</b></td>
                            <?php for ($d=1; $d<=$daysInMonth; $d++):
                                $date=sprintf('%04d-%02d-%02d',$year,$month,$d);
                                $hc=holidayClassForDate($date,$holidayDates);
                                $v=(int)($g['R'][$d]??0);
                            ?><td class="<?=h($hc)?>"><?=$v ?: '&nbsp;'?></td><?php endfor; ?>
                            <td><b><?=$g['totalR']?></b></td>
                            <td rowspan="2"><?= !empty($g['future_plan']) ? '&nbsp;' : h($g['petugas'] ?: '-') ?></td>
                        </tr>
                        <tr>
                            <td><b>H</b></td>
                            <?php for ($d=1; $d<=$daysInMonth; $d++):
                                $date=sprintf('%04d-%02d-%02d',$year,$month,$d);
                                $hc=holidayClassForDate($date,$holidayDates);
                            ?><td class="<?=h($hc)?>">&nbsp;</td><?php endfor; ?>
                            <td>&nbsp;</td>
                        </tr>
                        <?php endif; ?>
                </tbody>
                <?php endforeach; ?>
            <?php endif; ?>
            <tfoot>
                <tr>
                    <td colspan="<?=$colspan?>" class="report-footer-cell">
                        <div class="legend">
                            <b>Keterangan :</b>
                            <span><b>R</b> : Rencana</span>
                            <span><b>H</b> : Hasil</span>
                            <span class="holiday-chip"></span><span>: Sabtu/Minggu/Tanggal Merah</span>
                        </div>
                        <div class="doc-code">STIS-CMFG4-10/R3</div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </section>
    <?php
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Jadwal Bulanan | QA System</title>
    <link rel="icon" type="image/png" href="/qa_kalibrasi/images/favicon-toto-qa.png?v=1">
    <link href="../../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        /* Komposisi surat mengacu pada jadwal bulanan pabrik: kolom alat/seksi
           diperlebar, semua tanggal tetap satu baris pada A4 landscape. */
        * { box-sizing:border-box; }
        body { background:#e9ecf1; font-family:Arial,sans-serif; color:#000; padding:20px; }
        .toolbar { background:#fff; border:1px solid #ddd; border-radius:5px; padding:15px; margin-bottom:18px; }
        .preview-wrap { overflow:auto; }
        .report-sheet { background:#fff; width:1400px; margin:0 auto 24px; padding:12px 12px 18px;
                        box-shadow:0 1px 6px rgba(0,0,0,.18); position:relative; }
        .schedule-table { width:100%; border-collapse:collapse; table-layout:fixed; font-size:9px; }
        .schedule-table .col-no { width:2.5%; }
        .schedule-table .col-name { width:19%; }
        .schedule-table .col-section { width:10.5%; }
        .schedule-table .col-type { width:2.5%; }
        .schedule-table .col-day { width:calc(52% / var(--days)); }
        .schedule-table .col-total { width:5%; }
        .schedule-table .col-officer { width:8.5%; }
        .schedule-table > thead > tr > th,
        .schedule-table > tbody > tr > td { border:1px solid #000; text-align:center;
                                             padding:2px 1px; height:19px; vertical-align:middle; }
        .schedule-table > thead > tr > th { font-weight:normal; }
        .schedule-table .text-left { text-align:left; padding-left:5px; }
        .schedule-table .tool-name { font-size:10px; line-height:1.15; overflow-wrap:break-word; }
        .schedule-table .tool-section { font-size:9.5px; line-height:1.15; overflow-wrap:break-word; }
        .section-row td { text-align:left!important; font-weight:bold; background:#e1e1e1; height:17px; }
        .holiday-col { background:#bdbdbd!important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        .company-title { font-size:11px; font-weight:bold; text-align:center;
                         border-bottom:2px solid #000; padding:5px 0 4px; }
        .report-head { display:grid; grid-template-columns:1fr 2fr 1fr;
                       align-items:start; min-height:115px; border-bottom:1px solid #000; }
        .head-title { text-align:center; font-size:17px; line-height:1.4; padding-top:26px; font-weight:normal; }
        .approval-wrap { padding:5px 0 0 8px; }
        .print-date { font-size:10px; text-align:left; margin-bottom:4px; font-weight:bold; }
        .approval-table { width:100%; border-collapse:collapse; font-size:9px; }
        .approval-table th,.approval-table td { border:1px solid #000; text-align:center;
                                              padding:2px; height:28px; font-weight:normal; }
        .approval-table th { height:auto; }
        .schedule-table > thead > tr > th.report-heading-cell {
            border:0!important; padding:0!important; height:auto!important; overflow:visible!important;
            vertical-align:top!important; text-align:initial!important; font-weight:normal;
        }
        .schedule-table > tfoot > tr > td.report-footer-cell {
            border:0!important; padding:0!important; height:auto!important;
            overflow:visible!important; text-align:left!important;
        }
        .schedule-table > tbody.tool-group { break-inside:avoid; page-break-inside:avoid; }
        .schedule-table > tbody > tr { break-inside:avoid; page-break-inside:avoid; }
        .legend { border:1px solid #000; border-top:0; padding:6px; font-size:10px;
                  display:flex; flex-wrap:wrap; gap:22px; align-items:center; }
        .holiday-chip { display:inline-block; width:13px; height:13px; background:#bdbdbd;
                        border:1px solid #999; margin-right:-18px; }
        .doc-code { font-size:9px; text-align:right; margin-top:3px; }
        .empty-row { height:50px!important; }
        .page-break { page-break-before:always; }
        @media print {
            /* Margin 0 mencegah URL/judul browser ikut tercetak; padding di sheet menjadi margin konten. */
            @page { size:A4 landscape; margin:0; }
            html,body { margin:0!important; padding:0!important; background:#fff!important; }
            .no-print { display:none!important; }
            .preview-wrap { overflow:visible; }
            .report-sheet { width:auto; min-height:0; margin:0; padding:0 5mm 5mm;
                            box-shadow:none; page-break-after:always; }
            .report-sheet:last-child { page-break-after:auto; }
            .schedule-table { font-size:6.5pt; }
            .schedule-table > thead { display:table-header-group!important; }
            .schedule-table > tbody { display:table-row-group!important; }
            .schedule-table > tfoot { display:table-footer-group!important; }
            .schedule-table > thead > tr > th.report-heading-cell { padding-top:5mm!important; }
            .schedule-table > thead > tr > th:not(.report-heading-cell),
            .schedule-table > tbody > tr > td { font-size:6.1pt; padding:1px 1px; height:13px; line-height:1.15; }
            .schedule-table .tool-name { font-size:7.1pt!important; font-weight:600; }
            .schedule-table .tool-section { font-size:6.8pt!important; }
            .schedule-table .text-left { padding-left:3px; }
            .section-row td { height:13px!important; font-size:6.4pt!important; }
            .company-title { font-size:8pt; padding:3px 0 4px; }
            .report-head { min-height:78px; grid-template-columns:1fr 2fr 1fr; }
            .head-title { font-size:11pt; padding-top:19px; }
            .approval-wrap { padding-top:4px; }
            .print-date { font-size:6pt; margin-bottom:2px; }
            .approval-table { font-size:5.5pt; }
            .approval-table th,.approval-table td { padding:1px; height:20px; }
            .legend { font-size:6pt; padding:4px; gap:15px; }
            .holiday-chip { width:9px; height:9px; margin-right:-12px; }
            .doc-code { font-size:6pt; }
            .schedule-table,.schedule-table td,.schedule-table th {
                -webkit-print-color-adjust:exact; print-color-adjust:exact;
            }
        }
    </style>
</head>
<body>
<div class="toolbar no-print" <?= $embedded ? 'style="display:none"' : '' ?>>
    <form method="GET" class="form-inline" style="margin:0">
        <input type="hidden" name="mode" value="<?=h($mode)?>">
        <a href="../../../media.php" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali ke Menu Utama</a>
        <div class="form-group" style="margin-left:10px"><label>Bulan:&nbsp;</label><input type="month" class="form-control" name="bulan" value="<?=h($bulan)?>"></div>
        <div class="form-group" style="margin-left:8px"><label>Seksi:&nbsp;</label><select class="form-control" name="seksi"><option value="ALL">Semua Seksi</option><?php foreach($seksiOptions as $s): ?><option value="<?=h($s)?>" <?=$seksi===$s?'selected':''?>><?=h($s)?></option><?php endforeach; ?></select></div>
        <div class="form-group" style="margin-left:8px"><label>Status:&nbsp;</label><select class="form-control" name="status"><?php foreach(['ALL'=>'Semua','OK'=>'OK','NOT GOOD'=>'NOT GOOD','RUSAK'=>'RUSAK','DISCONTINUE'=>'DISCONTINUE','DITARIK TECHNICAL'=>'DITARIK TECHNICAL'] as $k=>$v): ?><option value="<?=h($k)?>" <?=$status===$k?'selected':''?>><?=h($v)?></option><?php endforeach; ?></select></div>
        <button class="btn btn-primary" type="submit" style="margin-left:8px"><i class="fa fa-search"></i> Tampilkan</button>
        <button class="btn btn-success" type="button" onclick="qaPrintMonthlyReport()" style="margin-left:8px"><i class="fa fa-print"></i> <?=$mode==='both'?'Cetak 2 Laporan':'Cetak Laporan'?></button>
    </form>
    <div style="font-size:12px;color:#666;margin-top:9px">
        <?php if ($mode === 'internal'): ?>
            Format <b>Internal</b>: R + H berisi data pada tanggal aktual kalibrasi.
        <?php elseif ($mode === 'external'): ?>
            Format <b>External</b>: R berisi data, baris H tetap tampil tetapi kosong.
        <?php else: ?>
            Satu kali print menghasilkan 2 halaman: <b>Internal</b> (R + H berisi data) dan <b>External</b> (R berisi data, baris H tetap tampil tetapi kosong).
        <?php endif; ?>
    </div>
</div>
<div class="preview-wrap">
    <?php if ($mode === 'internal'): ?>
        <?php renderReport('internal',$reportRows,$daysInMonth,$year,$month,$holidayDates,$periodeLabel,$seksi,$namaPetugas,false); ?>
    <?php elseif ($mode === 'external'): ?>
        <?php renderReport('external',$reportRows,$daysInMonth,$year,$month,$holidayDates,$periodeLabel,$seksi,$namaPetugas,false); ?>
    <?php else: ?>
        <?php renderReport('internal',$reportRows,$daysInMonth,$year,$month,$holidayDates,$periodeLabel,$seksi,$namaPetugas,false); ?>
        <?php renderReport('external',$reportRows,$daysInMonth,$year,$month,$holidayDates,$periodeLabel,$seksi,$namaPetugas,true); ?>
    <?php endif; ?>
</div>
<script src="../../../js/jquery.js"></script>
<script src="../../../js/bootstrap.min.js"></script>
<script>
function qaPrintMonthlyReport(){
    var oldTitle=document.title;
    document.title='';
    var restore=function(){ document.title=oldTitle; window.removeEventListener('afterprint',restore); };
    window.addEventListener('afterprint',restore);
    window.print();
    setTimeout(function(){ if(document.title==='') document.title=oldTitle; },1000);
}
</script>
<?php if ($autoprint): ?>
<script>window.addEventListener('load',function(){setTimeout(function(){qaPrintMonthlyReport();},250);});</script>
<?php endif; ?>
</body>
</html>

<?php
session_start();
if (empty($_SESSION['login'])) {
    header('location: ../../logout.php');
    exit();
}
include "../../inc/inc_koneksi.php";

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
foreach ($groups as &$g) {
    $g['totalR'] = array_sum($g['R']);
    $g['totalH'] = array_sum($g['H']);
    $g['petugas'] = implode(', ', array_values(array_unique(array_filter($g['petugas']))));
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

        <table class="schedule-table">
            <thead>
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
            <tbody>
                <?php if (!$reportRows): ?>
                    <tr><td colspan="<?=$colspan?>" class="empty-row">Tidak ada data Update Hasil Kalibrasi pada periode ini.</td></tr>
                <?php else: ?>
                    <?php $no=0; $lastSection=null; foreach ($reportRows as $g): $no++; ?>
                        <?php if ($lastSection !== $g['seksi']): $lastSection=$g['seksi']; ?>
                        <tr class="section-row"><td colspan="<?=$colspan?>">SEKSI: <?=h(strtoupper($lastSection))?></td></tr>
                        <?php endif; ?>

                        <?php if ($internal): ?>
                        <tr>
                            <td rowspan="2"><?=$no?></td>
                            <td rowspan="2" class="text-left"><?=h($g['nama_part'])?></td>
                            <td rowspan="2"><?=h($g['seksi'])?></td>
                            <td><b>R</b></td>
                            <?php for ($d=1; $d<=$daysInMonth; $d++):
                                $date=sprintf('%04d-%02d-%02d',$year,$month,$d);
                                $hc=holidayClassForDate($date,$holidayDates);
                                $v=(int)($g['R'][$d]??0);
                            ?><td class="<?=h($hc)?>"><?=$v ?: ''?></td><?php endfor; ?>
                            <td><b><?=$g['totalR']?></b></td>
                            <td rowspan="2"><?=h($g['petugas'] ?: '-')?></td>
                        </tr>
                        <tr>
                            <td><b>H</b></td>
                            <?php for ($d=1; $d<=$daysInMonth; $d++):
                                $date=sprintf('%04d-%02d-%02d',$year,$month,$d);
                                $hc=holidayClassForDate($date,$holidayDates);
                                $v=(int)($g['H'][$d]??0);
                            ?><td class="<?=h($hc)?>"><?=$v ?: ''?></td><?php endfor; ?>
                            <td><b><?=$g['totalH']?></b></td>
                        </tr>
                        <?php else: ?>
                        <tr>
                            <td rowspan="2"><?=$no?></td>
                            <td rowspan="2" class="text-left"><?=h($g['nama_part'])?></td>
                            <td rowspan="2"><?=h($g['seksi'])?></td>
                            <td><b>R</b></td>
                            <?php for ($d=1; $d<=$daysInMonth; $d++):
                                $date=sprintf('%04d-%02d-%02d',$year,$month,$d);
                                $hc=holidayClassForDate($date,$holidayDates);
                                $v=(int)($g['R'][$d]??0);
                            ?><td class="<?=h($hc)?>"><?=$v ?: ''?></td><?php endfor; ?>
                            <td><b><?=$g['totalR']?></b></td>
                            <td rowspan="2"><?=h($g['petugas'] ?: '-')?></td>
                        </tr>
                        <tr>
                            <td><b>H</b></td>
                            <?php for ($d=1; $d<=$daysInMonth; $d++):
                                $date=sprintf('%04d-%02d-%02d',$year,$month,$d);
                                $hc=holidayClassForDate($date,$holidayDates);
                            ?><td class="<?=h($hc)?>"></td><?php endfor; ?>
                            <td></td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="legend">
            <b>Keterangan :</b>
            <span><b>R</b> : Rencana</span>
            <span><b>H</b> : Hasil</span>
            <span class="holiday-chip"></span><span>: Sabtu/Minggu/Tanggal Merah</span>
        </div>
        <div class="doc-code">STIS-CMFG4-10/R3</div>
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
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        body{background:#eef1f5;font-family:Arial,sans-serif;color:#000;padding:20px}.toolbar{background:#fff;border:1px solid #ddd;border-radius:5px;padding:15px;margin-bottom:18px}.preview-wrap{overflow:auto}.report-sheet{background:#fff;width:1500px;min-height:820px;margin:0 auto 24px;padding:10px 12px;box-shadow:0 1px 6px rgba(0,0,0,.18);position:relative}.company-title{text-align:center;font-size:11px;font-weight:bold;border-bottom:1px solid #000;padding:4px 0}.report-head{display:grid;grid-template-columns:1fr 2fr 1fr;align-items:start;min-height:118px}.head-title{text-align:center;font-size:18px;line-height:1.55;padding-top:27px}.approval-wrap{padding-top:5px}.print-date{font-size:10px;margin-bottom:3px}.approval-table{width:100%;border-collapse:collapse;font-size:9px}.approval-table th,.approval-table td{border:1px solid #000;text-align:center;padding:3px;height:30px}.approval-table th{height:auto;font-weight:normal}.schedule-table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:9px}.schedule-table th,.schedule-table td{border:1px solid #000;text-align:center;padding:2px;height:20px;overflow:hidden}.schedule-table th{font-weight:normal}.schedule-table .text-left{text-align:left;padding-left:4px}.col-no{width:28px}.col-name{width:155px}.col-section{width:82px}.col-type{width:34px}.col-total{width:55px}.col-officer{width:95px}.day{width:28px}.section-row td{text-align:left;font-weight:bold;background:#f0f0f0;height:18px}.holiday-col{background:rgba(220,53,69,.50)!important}.legend{border:1px solid #000;border-top:0;padding:7px 6px;font-size:10px;display:flex;gap:28px;align-items:center}.holiday-chip{display:inline-block;width:14px;height:14px;background:rgba(220,53,69,.50);border:1px solid #999;margin-right:-24px}.doc-code{text-align:right;font-size:9px;margin-top:4px}.empty-row{height:50px!important}.page-break{page-break-before:always}
        @media print{
            @page{size:A4 landscape;margin:0}
            html,body{margin:0!important;padding:0!important;background:#fff}.no-print{display:none!important}.preview-wrap{overflow:visible}.report-sheet{width:auto;min-height:0;margin:0;padding:6mm;box-sizing:border-box;box-shadow:none;page-break-after:always}.report-sheet:last-child{page-break-after:auto}.company-title{font-size:8px}.report-head{min-height:72px;grid-template-columns:1fr 2fr 1fr}.head-title{font-size:13px;padding-top:15px}.print-date{font-size:7px}.approval-table{font-size:6px}.approval-table th,.approval-table td{height:18px;padding:1px}.schedule-table{font-size:6.2px}.schedule-table th,.schedule-table td{padding:1px;height:13px}.col-no{width:18px}.col-name{width:100px}.col-section{width:55px}.col-type{width:22px}.col-total{width:38px}.col-officer{width:62px}.day{width:auto}.section-row td{height:12px}.legend{font-size:7px;padding:4px;gap:18px}.doc-code{font-size:6px}.holiday-chip{width:9px;height:9px;margin-right:-14px}
        }
    </style>
</head>
<body>
<div class="toolbar no-print">
    <form method="GET" class="form-inline" style="margin:0">
        <input type="hidden" name="mode" value="<?=h($mode)?>">
        <a href="../../media.php" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali ke Menu Utama</a>
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
<script src="../../js/jquery.js"></script>
<script src="../../js/bootstrap.min.js"></script>
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

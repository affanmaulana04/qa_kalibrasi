<?php
session_start();
if (empty($_SESSION['login'])) {
    header('location: ../../logout.php');
    exit();
}

$id_pemakai = $_SESSION['userid'] ?? '';
include '../../inc/inc_koneksi.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatTanggalTitik($value) {
    if (!$value) return '-';
    $time = strtotime($value);
    return $time ? date('d.m.Y', $time) : '-';
}

// LOGIKA 3 WARNA SAJA: Terlewat (Merah), Hari Ini (Kuning), Terjadwal (Hijau)
function statusJadwal($jadwal) {
    if (!$jadwal) return ['label' => 'Terjadwal', 'class' => 'status-scheduled'];
    
    $tgl_sekarang = date('Y-m-d');
    $selisih = (int)((strtotime($jadwal) - strtotime($tgl_sekarang)) / 86400);
    
    if ($selisih < 0) {
        return ['label' => 'Jadwal Terlewat', 'class' => 'status-overdue'];
    } elseif ($selisih === 0) {
        return ['label' => 'Jadwal Hari Ini', 'class' => 'status-today'];
    } else {
        return ['label' => 'Terjadwal', 'class' => 'status-scheduled'];
    }
}

// QUERY BARU: Langsung ambil teks seksi_pemilik, nggak perlu JOIN tabel HRD lama
$sql = "SELECT id, no_part AS nomor_alat, nama_part AS nama_alat,
               seksi_pemilik AS kode_pemilik,
               jadwal_kalibrasi
        FROM qa_part
        WHERE status = 'aktif'
        ORDER BY seksi_pemilik ASC, jadwal_kalibrasi ASC, no_part ASC";

$result = $konek->query($sql);
$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) $rows[] = $row;
}

// Target 23 alat per lembar A4 landscape.
$perPage = 23;
$pages = $rows ? array_chunk($rows, $perPage) : [[]];
$totalPages = count($pages);
$todayLabel = date('d.m.Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar Kalibrasi Alat</title>
<link rel="shortcut icon" href="../../images/hrd.ico">
<link href="../../css/bootstrap.min.css" rel="stylesheet">
<link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet">
<style>
body{padding-top:80px;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;color:#222}
.card-report{background:#fff;border-radius:10px;box-shadow:0 4px 15px rgba(0,0,0,.05);padding:25px;margin-top:20px}
.screen-title{font-weight:700;margin:0 0 20px}
.screen-table th{text-align:center;background:#f3f4f6}.screen-table td{vertical-align:middle!important}
.print-area{display:none}

/* WARNA UNTUK LAYAR MONITOR (3 WARNA) */
.status-scheduled{background:#d1e7dd!important; color:#0f5132!important; font-weight:bold;} /* Hijau */
.status-today{background:#fff3cd!important; color:#664d03!important; font-weight:bold;} /* Kuning */
.status-overdue{background:#f8d7da!important; color:#842029!important; font-weight:bold;} /* Merah */

@media print{
    @page{size:A4 landscape;margin:8mm}
    *{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}
    html,body{margin:0!important;padding:0!important;background:#fff!important}
    .no-print{display:none!important}
    .print-area{display:block!important}
    .print-page{width:100%;height:194mm;box-sizing:border-box;position:relative;page-break-after:always;break-after:page;overflow:hidden}
    .print-page:last-child{page-break-after:auto;break-after:auto}
    .print-top{position:relative;height:19mm}
    .email-date{position:absolute;left:0;top:-1mm;font-size:10pt;font-weight:600}
    .report-title{text-align:center;font-size:19pt;font-weight:700;line-height:1.15;margin:0;padding-top:0}
    .page-number{text-align:right;font-size:10pt;font-weight:700;margin-top:1mm}
    .print-table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:8.7pt}
    .print-table th,.print-table td{border:1px solid #6d7378;padding:2.4px 4px;vertical-align:middle}
    .print-table th{background:#e7eaed;text-align:center;font-weight:700;height:7mm}
    .print-table td:nth-child(1){width:5%;text-align:center}
    .print-table td:nth-child(2){width:17%}
    .print-table td:nth-child(3){width:15%}
    .print-table td:nth-child(4){width:27%}
    .print-table td:nth-child(5){width:17%;text-align:center}
    .print-table td:nth-child(6){width:19%;text-align:center;font-weight:700}
    .section-row td{font-weight:700;padding:2.2px 7px;background:#e9ecef!important}
    
    /* WARNA UNTUK KERTAS PRINT (3 WARNA) */
    .status-scheduled{background:#d1e7dd!important; color:#000!important;}
    .status-today{background:#fff3cd!important; color:#000!important;}
    .status-overdue{background:#f8d7da!important; color:#000!important;}
    
    .print-footer{position:absolute;left:0;right:0;bottom:0;height:32mm;display:flex;gap:4mm;box-sizing:border-box}
    .legend-box,.note-box{border:1px solid #6d7378;box-sizing:border-box;padding:3mm}
    .legend-box{width:30%}.note-box{width:70%}
    .footer-title{font-size:10.5pt;font-weight:700;margin-bottom:2mm}
    .legend-item{display:flex;align-items:center;font-size:8.2pt;line-height:1.25;margin-bottom:1.1mm}
    
    /* KOTAK KECIL LEGENDA BAWAH */
    .legend-color{display:inline-block;width:6mm;height:4mm;border:1px solid #777;margin-right:2mm;flex:none}
    
    .note-line{border-bottom:1px dotted #555;height:5mm;margin-top:0.5mm}
}
</style>
</head>
<body>
<nav class="navbar navbar-default navbar-fixed-top no-print">
    <div class="container">
        <div class="navbar-header"><a class="navbar-brand" href="../../media.php"><img class="img-responsive" src="../../images/toto.png" alt="Logo"></a></div>
        <div class="collapse navbar-collapse">
            <ul class="nav navbar-nav navbar-left"><li><a href="../../media.php"><i class="fa fa-arrow-left"></i> Kembali ke Menu Utama</a></li></ul>
            <ul class="nav navbar-nav navbar-right"><li><a href="#"><span style="color:#1469EA"><i class="fa fa-user"></i> <?php echo e($id_pemakai); ?></span></a></li><li><a href="../../logout.php">Logout</a></li></ul>
        </div>
    </div>
</nav>

<div class="container-fluid no-print">
    <div class="card-report">
        <div class="clearfix">
            <h3 class="screen-title pull-left">Daftar Kalibrasi Alat</h3>
            <button type="button" class="btn btn-primary pull-right" onclick="window.print()"><i class="fa fa-print"></i> Cetak Tabel</button>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered screen-table">
                <thead><tr><th>No</th><th>Seksi Pemilik</th><th>No. Alat</th><th>Nama Alat</th><th>Tanggal Jadwal</th><th>Status</th></tr></thead>
                <tbody>
                <?php if ($rows): $no=1; $last=''; foreach ($rows as $row): $owner=$row['kode_pemilik']; ?>
                    <?php if ($owner !== $last): ?><tr><td colspan="6" style="background:#e9ecef; text-align:left;"><strong>SEKSI: <?php echo e($owner); ?></strong></td></tr><?php $last=$owner; endif; ?>
                    <?php $st=statusJadwal($row['jadwal_kalibrasi']); ?>
                    
                    <tr class="<?php echo e($st['class']); ?>">
                        <td class="text-center" style="background:#fff; color:#333;"><?php echo $no++; ?></td>
                        <td style="background:#fff; color:#333;"><?php echo e($owner); ?></td>
                        <td style="background:#fff; color:#333;"><strong><?php echo e($row['nomor_alat']); ?></strong></td>
                        <td style="background:#fff; color:#333;"><?php echo e($row['nama_alat']); ?></td>
                        <td class="text-center" style="background:#fff; color:#333;"><?php echo formatTanggalTitik($row['jadwal_kalibrasi']); ?></td>
                        <td class="text-center"><?php echo e($st['label']); ?></td>
                    </tr>
                <?php endforeach; else: ?><tr><td colspan="6" class="text-center text-muted">Belum ada data alat QA.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="print-area">
<?php foreach ($pages as $pageIndex => $pageRows): ?>
    <section class="print-page">
        <div class="print-top">
            <div class="email-date">Email: <?php echo e($todayLabel); ?></div>
            <div class="report-title">DAFTAR KALIBRASI ALAT</div>
            <div class="page-number">Halaman <?php echo $totalPages > 1 ? ($pageIndex + 1) . '/' . $totalPages : ($pageIndex + 1); ?></div>
        </div>

        <table class="print-table">
            <thead>
                <tr>
                    <th>No</th><th>Seksi Pemilik</th><th>No. Alat</th><th>Nama Alat</th><th>Tanggal Jadwal</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php $lastOwner=''; foreach ($pageRows as $localIndex => $row):
                $ownerCode=strtoupper(trim((string)$row['kode_pemilik']));
                $st=statusJadwal($row['jadwal_kalibrasi']);
                $globalNo=($pageIndex*$perPage)+$localIndex+1;
                
                if ($ownerCode !== $lastOwner): ?>
                    <tr class="section-row"><td colspan="6">SEKSI PEMILIK: <?php echo e($row['kode_pemilik']); ?></td></tr>
                <?php $lastOwner=$ownerCode; endif; ?>
                
                <tr>
                    <td><?php echo $globalNo; ?></td>
                    <td><?php echo e($row['kode_pemilik']); ?></td>
                    <td><strong><?php echo e($row['nomor_alat']); ?></strong></td>
                    <td><?php echo e($row['nama_alat']); ?></td>
                    <td class="text-center"><?php echo formatTanggalTitik($row['jadwal_kalibrasi']); ?></td>
                    <td class="<?php echo e($st['class']); ?>"><?php echo e($st['label']); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$pageRows): ?><tr><td colspan="6" style="text-align:center;padding:12mm">Belum ada data alat QA.</td></tr><?php endif; ?>
            </tbody>
        </table>

        <!-- LEGENDA BAWAH 3 WARNA -->
        <div class="print-footer">
            <div class="legend-box">
                <div class="footer-title">Keterangan Warna:</div>
                <div class="legend-item"><span class="legend-color status-scheduled"></span><strong>Terjadwal</strong></div>
                <div class="legend-item"><span class="legend-color status-today"></span><strong>Jadwal Hari Ini</strong></div>
                <div class="legend-item"><span class="legend-color status-overdue"></span><strong>Jadwal Terlewat</strong></div>
            </div>
            <div class="note-box">
                <div class="footer-title">Keterangan:</div>
                <div class="note-line"></div><div class="note-line"></div><div class="note-line"></div>
                <div class="note-line"></div><div class="note-line"></div><div class="note-line"></div>
            </div>
        </div>
    </section>
<?php endforeach; ?>
</div>
<?php if (isset($_GET['print']) && $_GET['print'] === '1'): ?>
<script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 300); });</script>
<?php endif; ?>
<script src="../../js/jquery.js"></script>
<script src="../../js/bootstrap.min.js"></script>
</body>
</html>
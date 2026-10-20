<?php
session_start();
if ($_SESSION['login'] == 0) { header('location: ../../../logout.php'); exit(); }
require_once __DIR__ . '/../../../inc/inc_koneksi.php';
require_once __DIR__ . '/../../../inc/qa_rbac.php';
qaRbacRequire($konek,'jadwal_harian');

// Tombol Kembali mengikuti asal navigasi. URL langsung/menu kembali ke Media,
// sedangkan akses dari Master Data (atau ?from=masterdata) kembali ke Master Data.
$referrerPath = parse_url((string)($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH);
$fromMasterdata = strtolower(trim((string)($_GET['from'] ?? ''))) === 'masterdata'
    || (is_string($referrerPath) && preg_match('~/modul/qa/masterdata/masterdata\.php$~i', $referrerPath));
$backUrl = $fromMasterdata ? '../masterdata/masterdata.php' : '../../../media.php';
$backLabel = $fromMasterdata ? 'Kembali ke Master Data' : 'Kembali ke Menu Utama';

$filter_seksi = isset($_GET['seksi']) ? mysqli_real_escape_string($konek, $_GET['seksi']) : 'ALL';
$filter_tgl = isset($_GET['tgl']) ? mysqli_real_escape_string($konek, $_GET['tgl']) : date('Y-m-d');

$where = "status = 'aktif' AND jadwal_kalibrasi <= '$filter_tgl'";
if($filter_seksi !== 'ALL') { $where .= " AND seksi_pemilik = '$filter_seksi'"; }

$query = mysqli_query($konek, "SELECT * FROM qa_part WHERE $where ORDER BY seksi_pemilik ASC, jadwal_kalibrasi ASC");

$data_per_seksi = [];
while($r = mysqli_fetch_assoc($query)) {
    $seksi = $r['seksi_pemilik'];
    if(!isset($data_per_seksi[$seksi])) { $data_per_seksi[$seksi] = []; }
    $data_per_seksi[$seksi][] = $r;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cetak Jadwal Harian</title>
    <link rel="icon" type="image/png" href="../../../images/LogoTOTO.png?v=20261013">
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; margin: 0; padding: 20px; background: #525659; }
        
        .no-print { text-align: center; margin-bottom: 20px; }
        .btn-action { display: inline-block; width: 200px; padding: 12px; background: #fff; color: #333; text-align: center; text-decoration: none; font-weight: bold; border-radius: 5px; cursor: pointer; border:none; margin-top:10px; }
        .btn-action:hover { background: #ddd; }

        .a4-landscape { width: 297mm; min-height: 210mm; background: #fff; margin: 0 auto 20px auto; padding: 10mm; box-shadow: 0 4px 10px rgba(0,0,0,0.5); position: relative; page-break-after: always; }
        .a4-landscape:last-child { page-break-after: auto; margin-bottom: 0; }
        
        .table-excel { width: 100%; border-collapse: collapse; border: 2px solid #000; }
        .table-excel th, .table-excel td { border: 1px solid #000; padding: 4px; page-break-inside: avoid; text-align: center; vertical-align: middle; }
        .table-excel tbody tr, .table-excel tbody td { height: 20px; }
        
        .text-bold { font-weight: bold; }
        .text-left { text-align: left !important; padding-left: 5px !important; }
        
        .header-title { font-size: 20px; font-weight: bold; text-align: center; margin: 15px 0;}
        .pt-title { font-weight: bold; text-align: center; font-size: 13px; margin-top:-5px;}
        
        .sign-box { border: 1px solid #000; width: 300px; text-align: center; float: right; margin-bottom: 10px;}
        .sign-box th { border: 1px solid #000; padding: 2px;}
        .sign-box td { border: 1px solid #000; height: 50px;}

        @media print { 
            @page { size: A4 landscape; margin: 0; } 
            body { background: #fff; padding: 0; margin: 0; } 
            .a4-landscape { box-shadow: none; margin: 0; padding: 10mm; border: none; } 
            .no-print { display: none !important; }
            /* Sel kosong dan sel berisi memakai tinggi baris dan line-height yang sama. */
            .table-excel tbody tr, .table-excel tbody td { height: 4mm; }
            .table-excel tbody td { padding: .2mm 1mm; line-height: 3mm; } 
        }
    </style>
</head>
<body>

    <div class="no-print">
        <a href="<?=htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8')?>" style="color:#fff; font-weight:bold; text-decoration:none;">&larr; <?=htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8')?></a><br>
        <button class="btn-action" onclick="window.print()">Cetak Jadwal Harian</button>
    </div>

    <?php 
    if(count($data_per_seksi) == 0) {
        echo "<div class='a4-landscape text-center' style='padding-top:100px;'><h3>Tidak ada jadwal kalibrasi sampai tanggal ".date('d-m-Y', strtotime($filter_tgl))."</h3></div>";
    }

    $bulan = array (1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
    $tgl_indo = date('d') . ' ' . $bulan[(int)date('m')] . ' ' . date('Y');

    foreach($data_per_seksi as $seksi => $items) {
        $chunks = array_chunk($items, 30); // Max 30 baris per halaman biar fit
        $total_hal = count($chunks);

        foreach($chunks as $idx => $chunk) {
            $hal_ke = $idx + 1;
            ?>
            <div class="a4-landscape">
                <div class="pt-title">PT. SURYA TOTO INDONESIA Tbk.</div>
                
                <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 5px;">
                    <div style="width: 70%;">
                        <div class="header-title">JADUAL KALIBRASI HARIAN</div>
                    </div>
                    <div style="width: 30%;">
                        <div style="margin-bottom: 5px;">Tanggal , <?php echo $tgl_indo; ?></div>
                        <table class="sign-box">
                            <tr><th width="33%">DIBUAT</th><th width="33%">DIPERIKSA</th><th width="33%">DISETUJUI</th></tr>
                            <tr><td></td><td></td><td></td></tr>
                            <tr><th>PETUGAS</th><th>SUPERVISOR</th><th>MANAGER</th></tr>
                        </table>
                        <div style="clear:both; text-align:right; font-weight:bold;">HAL : <?php echo $hal_ke; ?> / <?php echo $total_hal; ?></div>
                    </div>
                </div>

                <div style="font-weight:bold; margin-bottom: 5px; font-size:12px;">SEKSI : <?php echo strtoupper($seksi); ?></div>
                
                <table class="table-excel">
                    <thead>
                        <tr>
                            <th width="3%">NO</th>
                            <th width="15%">NAMA ALAT</th>
                            <th width="20%">SPESIFIKASI</th>
                            <th width="10%">NO. ALAT</th>
                            <th width="8%">RESOLUSI</th>
                            <th width="8%">PERIODE<br>KALIBRASI</th>
                            <th width="9%">RENCANA<br>KALIBRASI</th>
                            <th width="9%">AKTUAL<br>KALIBRASI</th>
                            <th width="6%">HASIL</th>
                            <th width="12%">KALIBRASI BERIKUTNYA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = ($idx * 30) + 1;
                        foreach($chunk as $row) {
                            $periode_txt = $row['periode_kalibrasi'] == 365 ? '1 Tahun' : ($row['periode_kalibrasi'] == 180 ? '6 Bulan' : $row['periode_kalibrasi'].' Hari');
                            $tgl_ren = date('d - m - Y', strtotime($row['jadwal_kalibrasi']));
                            echo "<tr>
                                <td>{$no}</td>
                                <td class='text-left'>{$row['nama_part']}</td>
                                <td class='text-left'>{$row['spesifikasi']}</td>
                                <td class='text-left'>{$row['no_part']}</td>
                                <td>~</td>
                                <td>{$periode_txt}</td>
                                <td>{$tgl_ren}</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>";
                            $no++;
                        }
                        
                        // Fill sisa baris kosong jika kurang dari 30
                        $sisa = 30 - count($chunk);
                        for($i=0; $i<$sisa; $i++) {
                            echo "<tr>" . str_repeat("<td>&nbsp;</td>", 10) . "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <div style="text-align: right; font-size: 10px; margin-top: 5px; font-weight: bold;">STIS-CMFG4-04/R3</div>
            </div>
            <?php
        }
    }
    ?>

</body>
</html>

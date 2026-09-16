<?php
session_start();
if ($_SESSION['login'] == 0) {
    header('location: ../../logout.php');
    exit();
}
include "../../inc/inc_koneksi.php"; 

$id_pemakai = $_SESSION['userid'];
$tanggal_cetak = date('d.m.Y');
$tanggal_hari_ini = date('Y-m-d');
$sql = "SELECT no_part, nama_part, seksi_pemilik, jadwal_kalibrasi 
        FROM qa_part 
        WHERE status = 'aktif' 
        ORDER BY seksi_pemilik ASC, jadwal_kalibrasi ASC";

$query = mysqli_query($konek, $sql);
$rows_per_page = 25; 
$pages = [];
$current_page = [];
$row_count = 0;
$current_seksi = "";
$no = 1;

while ($row = mysqli_fetch_assoc($query)) {
    if ($current_seksi != $row['seksi_pemilik']) {
        $current_seksi = $row['seksi_pemilik'];

        if ($row_count >= $rows_per_page - 2) { 
            $pages[] = $current_page;
            $current_page = [];
            $row_count = 0;
        }
        $current_page[] = ['type' => 'seksi', 'text' => strtoupper($current_seksi)];
        $row_count++;
    }

    if ($row_count >= $rows_per_page) {
        $pages[] = $current_page;
        $current_page = [];
        $row_count = 0;
        $current_page[] = ['type' => 'seksi', 'text' => strtoupper($current_seksi) . " (Lanjutan)"];
        $row_count++;
    }

    $row['no'] = $no++;
    $current_page[] = ['type' => 'data', 'data' => $row];
    $row_count++;
}
if (!empty($current_page)) {
    $pages[] = $current_page;
}

$total_pages = count($pages);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Kalibrasi Alat | HRD Online</title>
    <link rel="shortcut icon" href="../../images/hrd.ico">
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #525659; margin: 0; padding: 20px 0; font-family: Arial, sans-serif; }
        
        .top-navbar { background-color: #333; color: white; padding: 15px 30px; position: fixed; top: 0; left: 0; right: 0; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.5); display: flex; justify-content: space-between; align-items: center;}
        .top-navbar a { color: white; text-decoration: none; font-weight: bold; }
        .top-navbar .btn-print { background-color: #fff; color: #333; font-weight: bold; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
        .top-navbar .btn-print:hover { background-color: #ddd; }

        .a4-paper {
            width: 210mm;
            min-height: 297mm;
            padding: 15mm;
            margin: 60px auto 20px auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            box-sizing: border-box;
            position: relative;
        }

        .report-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px;}
        .report-title { font-weight: bold; font-size: 22px; text-align: center; flex-grow: 1; margin: 0; }
        .date-info, .page-info { font-size: 12px; font-weight: bold; width: 150px; }
        .page-info { text-align: right; }
        
        .table-report { border-collapse: collapse; width: 100%; font-size: 11px; }
        .table-report th, .table-report td { border: 1px solid #777; padding: 6px; vertical-align: middle; text-align: center; }
        .table-report th { background-color: #e2e6ea; font-weight: bold; }
        .table-report td.text-left { text-align: left; padding-left: 10px;}

        .row-seksi { background-color: #d9e1f2; font-weight: bold; text-align: left; }
    
        .bg-terlewat { background-color: #ffb3b3 !important; font-weight: bold; color: #842029; } 
        .bg-hari-ini { background-color: #b3ffb3 !important; font-weight: bold; color: #664d03; } 
        .bg-terjadwal { background-color: #b3d9ff !important; font-weight: bold; color: #0f5132; } 
        
        .keterangan-wrapper { display: flex; gap: 20px; margin-top: 20px; }
        .keterangan-box { border: 1px solid #777; padding: 10px; font-size: 11px; flex: 1;}
        .keterangan-title { font-weight: bold; margin-bottom: 10px; }
        .color-box { display: inline-block; width: 15px; height: 12px; border: 1px solid #777; vertical-align: middle; margin-right: 8px;}

        @media print {
            @page { size: A4 portrait; margin: 0; } 
            
            body { background-color: white; margin: 0; padding: 0; }
            .top-navbar { display: none !important; }
            
            .a4-paper { 
                margin: 0; padding: 15mm; box-shadow: none; border: none; 
                width: 210mm; min-height: 297mm; 
                page-break-after: always;
            }

            .bg-terlewat, .bg-hari-ini, .bg-terjadwal, .row-seksi td, .table-report th, .color-box {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .bg-terlewat, .bg-hari-ini, .bg-terjadwal { color: #000 !important; }
        }
    </style>
</head>
<body>

    <div class="top-navbar">
        <a href="cek_alat.php"><i class="fa fa-arrow-left"></i> Kembali ke Cek Alat</a>
        <button class="btn-print" onclick="window.print()"><i class="fa fa-print"></i> Print Document</button>
    </div>

    <?php foreach ($pages as $index => $page_data) { 
        $current_page_number = $index + 1;
    ?>
    <div class="a4-paper">
        
        <div class="report-header">
            <div class="date-info">Email: <?php echo $tanggal_cetak; ?></div>
            <h1 class="report-title">DAFTAR KALIBRASI ALAT</h1>
            <div class="page-info">Halaman <?php echo $current_page_number . " / " . $total_pages; ?></div>
        </div>

        <table class="table-report">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">Seksi Pemilik</th>
                    <th width="15%">No. Alat</th>
                    <th width="30%">Nama Alat</th>
                    <th width="15%">Tanggal Jadwal</th>
                    <th width="20%">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($page_data as $item) {
                    
                    if ($item['type'] == 'seksi') {
                        echo "<tr class='row-seksi'><td colspan='6' class='text-left'>SEKSI PEMILIK: " . $item['text'] . "</td></tr>";
                    } 
                    else {
                        $row = $item['data'];
                        $tgl_jadwal = $row['jadwal_kalibrasi'];
                        $format_tgl_jadwal = date('d.m.Y', strtotime($tgl_jadwal));
                        
                        $datetime_hari_ini = new DateTime($tanggal_hari_ini);
                        $datetime_jadwal = new DateTime($tgl_jadwal);
                        $interval = $datetime_hari_ini->diff($datetime_jadwal);
                        
                        $selisih_hari = (int)$interval->format('%R%a');

                        $status_text = "";
                        $bg_class = "";

                        // LOGIKA BARU: CUKUP 3 KONDISI
                        if ($selisih_hari < 0) {
                            $status_text = "Jadwal Terlewat";
                            $bg_class = "bg-terlewat";
                        } elseif ($selisih_hari == 0) {
                            $status_text = "Jadwal Hari Ini";
                            $bg_class = "bg-hari-ini";
                        } else {
                            $status_text = "Terjadwal";
                            $bg_class = "bg-terjadwal";
                        }

                        echo "<tr>";
                        echo "<td>" . $row['no'] . "</td>";
                        echo "<td>" . $row['seksi_pemilik'] . "</td>";
                        echo "<td>" . $row['no_part'] . "</td>";
                        echo "<td class='text-left'>" . $row['nama_part'] . "</td>";
                        echo "<td>" . $format_tgl_jadwal . "</td>";
                        echo "<td class='" . $bg_class . "'>" . $status_text . "</td>";
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>

        <?php if ($current_page_number == $total_pages) { ?>
        <div class="keterangan-wrapper">
            <div class="keterangan-box">
                <div class="keterangan-title">Keterangan Warna:</div>
                <div style="margin-bottom: 5px;"><span class="color-box bg-terjadwal"></span> Terjadwal</div>
                <div style="margin-bottom: 5px;"><span class="color-box bg-hari-ini"></span> Jadwal Hari Ini</div>
                <div><span class="color-box bg-terlewat"></span> Jadwal Terlewat</div>
            </div>
            <div class="keterangan-box" style="flex: 2;">
                <div class="keterangan-title">Keterangan:</div>
                <div style="border-bottom: 1px dotted #777; height: 15px;"></div>
                <div style="border-bottom: 1px dotted #777; height: 15px;"></div>
                <div style="border-bottom: 1px dotted #777; height: 15px;"></div>
                <div style="border-bottom: 1px dotted #777; height: 15px;"></div>
            </div>
        </div>
        <?php } ?>

    </div>
    <?php } ?>

</body>
</html>
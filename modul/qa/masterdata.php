<?php
session_start();
if ($_SESSION['login'] == 0) { header('location: ../../logout.php'); exit(); }
include "../../inc/inc_koneksi.php";

$nama_petugas = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : $_SESSION['userid'];

mysqli_query($konek, "CREATE TABLE IF NOT EXISTS qa_alat_rusak LIKE qa_part");

// Query Master Seksi untuk Dropdown Filter
$q_seksi = mysqli_query($konek, "SELECT * FROM qa_seksi_master ORDER BY nama_seksi ASC");

// Tangkap Filter
$filter_seksi = isset($_GET['seksi']) ? mysqli_real_escape_string($konek, $_GET['seksi']) : 'ALL';
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'aktif';
$filter_waktu = isset($_GET['waktu']) ? $_GET['waktu'] : 'semua';

$val_harian = isset($_GET['tgl_harian']) ? $_GET['tgl_harian'] : date('Y-m-d');
$val_mingguan = isset($_GET['tgl_mingguan']) ? $_GET['tgl_mingguan'] : date('Y-\WW');
$val_bulanan = isset($_GET['tgl_bulanan']) ? $_GET['tgl_bulanan'] : date('Y-m');
$val_tahunan = isset($_GET['tgl_tahunan']) ? $_GET['tgl_tahunan'] : date('Y');

$table = ($filter_status == 'rusak') ? 'qa_alat_rusak' : 'qa_part';
$date_col = ($filter_status == 'rusak') ? 'DATE(updated_at)' : 'jadwal_kalibrasi';

$where = "1=1";
if ($filter_seksi !== 'ALL') {
    $where .= " AND seksi_pemilik = '$filter_seksi'";
}

if ($filter_waktu == 'harian') {
    $where .= " AND $date_col = '$val_harian'";
} elseif ($filter_waktu == 'mingguan') {
    $y = substr($val_mingguan, 0, 4);
    $w = substr($val_mingguan, 6, 2);
    $where .= " AND YEAR($date_col) = '$y' AND WEEKOFYEAR($date_col) = '$w'";
} elseif ($filter_waktu == 'bulanan') {
    $y = substr($val_bulanan, 0, 4);
    $m = substr($val_bulanan, 5, 2);
    $where .= " AND YEAR($date_col) = '$y' AND MONTH($date_col) = '$m'";
} elseif ($filter_waktu == 'tahunan') {
    $where .= " AND YEAR($date_col) = '$val_tahunan'";
}

$query = mysqli_query($konek, "SELECT * FROM $table WHERE $where ORDER BY seksi_pemilik ASC, $date_col ASC");
$data_grouped = [];
while($r = mysqli_fetch_assoc($query)) {
    $data_grouped[$r['seksi_pemilik']][] = $r;
}

// Persiapan Array Unified untuk Cetak (Biar tidak pisah halaman per seksi mubazir)
$print_rows = [];
$no_urut_print = 1;
foreach($data_grouped as $seksi => $items) {
    // Masukkan Header Seksi
    $print_rows[] = ['type' => 'header', 'text' => 'SEKSI PEMILIK: ' . strtoupper($seksi)];
    // Masukkan Data
    foreach($items as $r) {
        $print_rows[] = ['type' => 'data', 'row' => $r, 'no' => $no_urut_print];
        $no_urut_print++;
    }
}

// Pagination Kertas Print (Max 20 Baris per kertas)
$batas_baris = 20;
if(count($print_rows) == 0) {
    $chunks = [ [] ]; // Minimal 1 kertas kosong kalau ga ada data
    $total_hal = 1;
} else {
    $chunks = array_chunk($print_rows, $batas_baris);
    $total_hal = count($chunks);
}

// Persiapan Tanggal Cetak Indonesia
$bulan_indo = array (1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
$tgl_sekarang = date('d') . ' ' . $bulan_indo[(int)date('m')] . ' ' . date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Master Data | QA System</title>
    <link rel="icon" type="image/png" href="../../images/LogoTOTO.png?v=<?= time(); ?>"/>
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        body { padding-top: 80px; background-color: #ffffff; }
        
        .filter-box { background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 20px; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .card-plain { background: #fff; border: 1px solid #ccc; margin-bottom: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        
        .table-jadwal { width: 100%; border-collapse: collapse; border: 1px solid #aaa; font-family: Arial, sans-serif; font-size: 13px;}
        .table-jadwal th { border: 1px solid #aaa !important; padding: 12px; text-align: center; vertical-align: middle !important; background-color: #e2e6ea; color: #000; font-weight: bold; }
        .table-jadwal td { border: 1px solid #aaa !important; padding: 10px; vertical-align: middle !important; text-align: center; color: #000;}
        
        .tr-seksi { background-color: #d6d8db; font-weight: bold; border-top: 2px solid #555 !important; border-bottom: 2px solid #555 !important;}
        .text-left { text-align: left !important; }

        /* CSS HACK UNTUK UBAH TAMPILAN DATE KE DD/MM/YYYY */
        .custom-date { position: relative; color: transparent !important; }
        .custom-date::before { position: absolute; top: 6px; left: 12px; content: attr(data-date); color: #555; }
        .custom-date:focus { color: #555 !important; }
        .custom-date:focus::before { display: none !important; }

        /* Link No Alat */
        .link-history { color: #0056b3; cursor: pointer; text-decoration: underline; font-weight: bold; }
        .link-history:hover { color: #ff9800; }

        /* KEBUTUHAN CETAK NATIVE SATU TAB - FIXED OFFSIDE & PUMPING SHEET */
        #print-area { display: none; }
        @media print {
            @page { size: A4 landscape; margin: 8mm 10mm; } 
            body { padding: 0 !important; background-color: #fff !important; margin: 0 !important; }
            .no-print { display: none !important; }
            #print-area { display: block !important; }
            
            .a4-landscape { width: 100%; padding: 0; page-break-after: always; position: relative; font-family: Arial, sans-serif; color: #000; box-sizing: border-box; }
            .a4-landscape:last-child { page-break-after: auto; }
            
            table.print-table { width: 100%; border-collapse: collapse; border: 2px solid #000; font-size: 11px; margin-bottom: 5px; table-layout: fixed;}
            table.print-table th, table.print-table td { border: 1px solid #000 !important; padding: 3px; text-align: center; vertical-align: middle; color: #000 !important; overflow: hidden; word-wrap: break-word;}
            table.print-table th { font-weight: bold; background-color: #fff; }
            table.print-table td.text-left { text-align: left !important; padding-left: 5px !important; }
            
            .ttd-box { border-collapse: collapse; border: 2px solid #000; width: 260px; text-align: center; font-size: 10px; float: right; }
            .ttd-box th, .ttd-box td { border: 1px solid #000 !important; padding: 2px; color: #000 !important; }
            .ttd-box td.ttd-space { height: 40px; }
        }
    </style>
</head>
<body>
    
    <!-- SEMUA UI WEBSITE DIBUNGKUS DALAM NO-PRINT -->
    <div class="no-print">
        <nav class="navbar navbar-default navbar-fixed-top">
            <div class="container-fluid">
                <div class="navbar-header"><a class="navbar-brand" href="../../media.php"><img class="img-responsive" src="../../images/toto.png" style="height: 25px; margin-top: -3px;"></a></div>
                <ul class="nav navbar-nav navbar-left"><li><a href="cek_alat.php"><i class="fa fa-arrow-left"></i> Kembali ke Cek Alat</a></li></ul>
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="#"><span style='color: #1469EA; font-weight: bold;'><i class="fa fa-user"></i> <?php echo $nama_petugas; ?></span></a></li>
                </ul>
            </div>
        </nav>
        
        <div class="container-fluid" style="padding: 0 30px;">
            
            <div class="filter-box">
                <h4 style="margin-top:0; font-weight:bold; margin-bottom: 20px; color:#333;"><i class="fa fa-filter"></i> Filter Master Data</h4>
                <form class="form-inline" method="GET" action="" id="formFilter">
                    
                    <div class="form-group" style="margin-right: 15px;">
                        <label style="margin-right: 5px; color:#555;">Seksi:</label>
                        <select class="form-control" name="seksi" style="border-radius:0;">
                            <option value="ALL">Semua Seksi</option>
                            <?php 
                            mysqli_data_seek($q_seksi, 0);
                            while($rs = mysqli_fetch_assoc($q_seksi)) {
                                $sel = ($filter_seksi == $rs['nama_seksi']) ? 'selected' : '';
                                echo "<option value='{$rs['nama_seksi']}' $sel>{$rs['nama_seksi']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-right: 15px;">
                        <label style="margin-right: 5px; color:#555;">Status:</label>
                        <select class="form-control" name="status" style="border-radius:0;">
                            <option value="aktif" <?php echo $filter_status == 'aktif' ? 'selected' : ''; ?>>Barang OK (Aktif)</option>
                            <option value="rusak" <?php echo $filter_status == 'rusak' ? 'selected' : ''; ?>>Barang BURUK (Rusak)</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-right: 15px;">
                        <label style="margin-right: 5px; color:#555;">Filter Waktu:</label>
                        <select class="form-control" name="waktu" id="filter_waktu" style="border-radius:0;">
                            <option value="harian" <?php echo $filter_waktu == 'harian' ? 'selected' : ''; ?>>Harian</option>
                            <option value="mingguan" <?php echo $filter_waktu == 'mingguan' ? 'selected' : ''; ?>>Mingguan</option>
                            <option value="bulanan" <?php echo $filter_waktu == 'bulanan' ? 'selected' : ''; ?>>Bulanan</option>
                            <option value="tahunan" <?php echo $filter_waktu == 'tahunan' ? 'selected' : ''; ?>>Tahunan</option>
                            <option value="semua" <?php echo $filter_waktu == 'semua' ? 'selected' : ''; ?>>Semua Waktu</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-right: 15px;">
                        <div class="input-group wrapper-waktu" id="wrapper_harian" style="display:none;">
                            <input type="text" class="form-control" id="text_harian" placeholder="DD/MM/YYYY" maxlength="10" style="border-radius:0; width: 120px;" value="<?php echo date('d/m/Y', strtotime($val_harian)); ?>">
                            <div class="input-group-addon" style="position: relative; overflow: hidden; cursor: pointer; padding:0; width: 40px; background-color:#e9ecef;">
                                <i class="fa fa-calendar" style="line-height: 32px; color:#333;"></i>
                                <input type="date" class="input-waktu" name="tgl_harian" id="input_harian" value="<?php echo $val_harian; ?>" style="position: absolute; right: 0; top: 0; bottom: 0; opacity: 0; cursor: pointer; height:100%; width:100%;">
                            </div>
                        </div>

                        <div class="wrapper-waktu" id="wrapper_mingguan" style="display:none;">
                            <input type="week" class="form-control input-waktu" name="tgl_mingguan" id="input_mingguan" value="<?php echo $val_mingguan; ?>" style="border-radius:0;">
                        </div>
                        
                        <div class="wrapper-waktu" id="wrapper_bulanan" style="display:none;">
                            <input type="month" class="form-control input-waktu" name="tgl_bulanan" id="input_bulanan" value="<?php echo $val_bulanan; ?>" style="border-radius:0;">
                        </div>
                        
                        <div class="wrapper-waktu" id="wrapper_tahunan" style="display:none;">
                            <select class="form-control input-waktu" name="tgl_tahunan" id="input_tahunan" style="border-radius:0;">
                                <?php 
                                $thn_skr = date('Y');
                                for($i = $thn_skr - 5; $i <= $thn_skr + 5; $i++) {
                                    $sel = ($i == $val_tahunan) ? 'selected' : '';
                                    echo "<option value='$i' $sel>$i</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="font-weight:bold; border-radius:0;"><i class="fa fa-search"></i> Tampilkan</button>
                    <!-- TOMBOL CETAK NATIVE STAY 1 TAB -->
                    <button type="button" class="btn btn-default" style="font-weight:bold; border-radius:0; margin-left:10px; border: 1px solid #999;" onclick="window.print()"><i class="fa fa-print"></i> Cetak Data</button>
                </form>
            </div>

            <?php if(count($data_grouped) > 0): ?>
                <div class="card-plain table-responsive">
                    <table class="table-jadwal">
                        <thead>
                            <tr>
                                <th width="3%">NO</th>
                                <th width="15%">NAMA ALAT</th>
                                <th width="20%">SPESIFIKASI</th>
                                <th width="12%">NO. ALAT</th>
                                <th width="6%">RESOLUSI</th>
                                <th width="8%">PERIODE<br>KALIBRASI</th>
                                <th width="9%"><?php echo $filter_status == 'aktif' ? 'RENCANA<br>KALIBRASI' : 'TGL DIVONIS<br>RUSAK'; ?></th>
                                <th width="9%">AKTUAL<br>KALIBRASI</th>
                                <th width="6%">HASIL</th>
                                <th width="12%">KALIBRASI<br>BERIKUTNYA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach($data_grouped as $seksi => $items): 
                            ?>
                                <tr class="tr-seksi">
                                    <td colspan="10" class="text-left" style="padding-left:15px !important;">SEKSI PEMILIK: <?php echo strtoupper($seksi); ?></td>
                                </tr>
                                <?php 
                                foreach($items as $r) {
                                    $periode_txt = $r['periode_kalibrasi'] == 365 ? '1 Tahun' : ($r['periode_kalibrasi'] == 180 ? '6 Bulan' : $r['periode_kalibrasi'].' Hari');
                                    // Tanggal di UI
                                    $tgl_display = ($filter_status == 'aktif') ? date('d - m - Y', strtotime($r['jadwal_kalibrasi'])) : date('d - m - Y', strtotime($r['updated_at']));
                                    $res = !empty($r['resolusi']) ? $r['resolusi'] : '~';

                                    echo "<tr>
                                        <td>{$no}</td>
                                        <td class='text-left' style='padding-left:15px !important;'>{$r['nama_part']}</td>
                                        <td class='text-left'>{$r['spesifikasi']}</td>
                                        <td class='text-left'><a class='link-history' data-no='{$r['no_part']}' title='Klik untuk melihat riwayat alat ini'>{$r['no_part']}</a></td>
                                        <td>{$res}</td>
                                        <td>{$periode_txt}</td>
                                        <td>{$tgl_display}</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>";
                                    $no++;
                                }
                                ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="card-plain text-center" style="padding: 50px;">
                    <h4 class="text-muted"><i class="fa fa-inbox fa-3x" style="color:#ddd; margin-bottom:15px; display:block;"></i> Tidak ada data alat yang ditemukan pada filter ini.</h4>
                </div>
            <?php endif; ?>

        </div>
    </div> <!-- END NO PRINT UI -->

    <!-- WADAH KHUSUS PRINT NATIVE - FIX OFFSIDE DENGAN 20 BARIS / HALAMAN -->
    <div id="print-area">
        <?php 
        foreach($chunks as $idx => $chunk) {
            $hal_ke = $idx + 1;
            ?>
            <div class="a4-landscape">
                
                <div style="font-weight:bold; text-align:center; font-size:13px; margin-bottom:0px;">PT SURYA TOTO INDONESIA Tbk.</div>
                
                <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                    <div style="flex:1; text-align:center; padding-top:25px;">
                        <h2 style="margin:0; font-weight:bold; letter-spacing:1px; font-size:24px;">JADWAL KALIBRASI HARIAN</h2>
                    </div>
                    <div style="width:260px;">
                        <div style="text-align:left; font-size:12px; font-weight:bold; margin-bottom:2px;">Tanggal , <?php echo $tgl_sekarang; ?></div>
                        <table class="ttd-box">
                            <tr><th width="33%">DIBUAT</th><th width="33%">DIPERIKSA</th><th width="33%">DISETUJUI</th></tr>
                            <tr><td class="ttd-space"></td><td class="ttd-space"></td><td class="ttd-space"></td></tr>
                            <tr><td>Petugas</td><td>SUPERVISOR</td><td>MANAGER</td></tr>
                        </table>
                        <div style="text-align:right; font-size:12px; font-weight:bold; margin-top:2px;">HAL : <?php echo $hal_ke; ?> / <?php echo $total_hal; ?></div>
                    </div>
                </div>

                <div style="font-weight:bold; font-size:13px; margin-bottom:4px; text-transform:uppercase;">
                    SEKSI : <?php echo ($filter_seksi == 'ALL') ? 'SEMUA SEKSI' : strtoupper($filter_seksi); ?>
                </div>
                
                <table class="print-table">
                    <colgroup>
                        <col style="width: 3%;">
                        <col style="width: 15%;">
                        <col style="width: 20%;">
                        <col style="width: 12%;">
                        <col style="width: 6%;">
                        <col style="width: 8%;">
                        <col style="width: 9%;">
                        <col style="width: 9%;">
                        <col style="width: 6%;">
                        <col style="width: 12%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>NAMA ALAT</th>
                            <th>SPESIFIKASI</th>
                            <th>NO. ALAT</th>
                            <th>RESOLUSI</th>
                            <th>PERIODE<br>KALIBRASI</th>
                            <th><?php echo $filter_status == 'aktif' ? 'RENCANA<br>KALIBRASI' : 'TGL DIVONIS<br>RUSAK'; ?></th>
                            <th>AKTUAL<br>KALIBRASI</th>
                            <th>HASIL</th>
                            <th>KALIBRASI<br>BERIKUTNYA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $baris_terisi = 0;
                        foreach($chunk as $item) {
                            if($item['type'] == 'header') {
                                echo "<tr style='height: 25px; background-color:#f0f0f0; font-weight:bold;'><td colspan='10' class='text-left' style='padding-left:15px !important;'>{$item['text']}</td></tr>";
                            } else {
                                $row = $item['row'];
                                $n = $item['no'];
                                $periode_txt = $row['periode_kalibrasi'] == 365 ? '1 Tahun' : ($row['periode_kalibrasi'] == 180 ? '6 Bulan' : $row['periode_kalibrasi'].' Hari');
                                // Tgl Print pakai format dd - mm - yyyy
                                $tgl_ren = ($filter_status == 'aktif') ? date('d - m - Y', strtotime($row['jadwal_kalibrasi'])) : date('d - m - Y', strtotime($row['updated_at']));
                                $res_print = !empty($row['resolusi']) ? $row['resolusi'] : '~';

                                echo "<tr style='height: 25px;'>
                                    <td>{$n}</td>
                                    <td class='text-left'>{$row['nama_part']}</td>
                                    <td class='text-left'>{$row['spesifikasi']}</td>
                                    <td class='text-left'>{$row['no_part']}</td>
                                    <td>{$res_print}</td>
                                    <td>{$periode_txt}</td>
                                    <td>{$tgl_ren}</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                </tr>";
                            }
                            $baris_terisi++;
                        }
                        
                        // JIKA DATA KURANG DARI 1 HALAMAN (20 BARIS), TAMBAH BARIS KOSONG BIAR TETAP FULL 1 HALAMAN
                        $sisa = $batas_baris - $baris_terisi;
                        for($i=0; $i<$sisa; $i++) {
                            echo "<tr style='height: 25px;'><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <div style="text-align: right; font-size: 11px; margin-top: 3px; font-weight: bold;">STIS-CMFG4-04/R3</div>
            </div>
            <?php
        }
        ?>
    </div>

    <!-- MODAL POPUP HISTORY KALIBRASI -->
    <div class="modal fade no-print" id="modalHistory" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" style="border-radius: 8px;">
                <div class="modal-header" style="background-color: #1E3F66; color: #fff; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff; opacity: 1;"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel"><i class="fa fa-history"></i> Riwayat Kalibrasi Alat</h4>
                </div>
                <div class="modal-body">
                    <div class="row" style="margin-bottom: 15px;">
                        <div class="col-md-6">
                            <table style="width: 100%; font-size: 13px;">
                                <tr><td width="30%"><b>No Alat</b></td><td width="5%">:</td><td id="det_no_alat">-</td></tr>
                                <tr><td><b>Nama Alat</b></td><td>:</td><td id="det_nama_alat">-</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table style="width: 100%; font-size: 13px;">
                                <tr><td width="30%"><b>Seksi</b></td><td width="5%">:</td><td id="det_seksi">-</td></tr>
                                <tr><td><b>Spesifikasi</b></td><td>:</td><td id="det_spek">-</td></tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" style="font-size: 13px;">
                            <thead style="background-color: #f4f6f9;">
                                <tr>
                                    <th width="5%" class="text-center">NO</th>
                                    <th width="20%" class="text-center">TGL KALIBRASI</th>
                                    <th width="15%" class="text-center">HASIL</th>
                                    <th width="25%" class="text-center">PETUGAS</th>
                                    <th width="35%" class="text-center">KETERANGAN</th>
                                </tr>
                            </thead>
                            <tbody id="body_history">
                                <!-- Data AJAX Masuk Sini -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal" style="font-weight:bold;">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../../js/jquery.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function(){
            
            // LOGIKA DYNAMIC FILTER WAKTU
            $('#filter_waktu').change(function(){
                $('.wrapper-waktu').hide(); 
                $('.input-waktu').prop('disabled', true); 
                var val = $(this).val();
                if(val !== 'semua') {
                    $('#wrapper_' + val).show(); 
                    $('#input_' + val).prop('disabled', false); 
                }
            }).trigger('change');

            // JS HACK UNTUK UBAH TAMPILAN DATE KE DD/MM/YYYY
            $('#input_harian').on('change', function() {
                if(this.value) {
                    var p = this.value.split('-');
                    $('#text_harian').val(p[2] + '/' + p[1] + '/' + p[0]);
                } else {
                    $('#text_harian').val('');
                }
            });

            $('#text_harian').on('input', function(e) {
                var val = $(this).val().replace(/[^0-9]/g, '');
                var newVal = '';
                if (val.length > 0) newVal += val.slice(0, 2);
                if (val.length > 2) newVal += '/' + val.slice(2, 4);
                if (val.length > 4) newVal += '/' + val.slice(4, 8);
                $(this).val(newVal);
                
                if (newVal.length === 10) {
                    var p = newVal.split('/');
                    if (p.length === 3) {
                        $('#input_harian').val(p[2] + '-' + p[1] + '-' + p[0]);
                    }
                }
            });

            // LOGIKA KLIK NO ALAT -> TAMPILKAN MODAL HISTORY
            $('.link-history').click(function() {
                var noAlat = $(this).data('no');
                $('#det_no_alat').html('<i class="fa fa-spinner fa-spin"></i> Loading...');
                $('#det_nama_alat, #det_seksi, #det_spek').text('-');
                $('#body_history').html('<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i> Mengambil data riwayat...</td></tr>');
                
                $('#modalHistory').modal('show');

                $.ajax({
                    url: 'proses_qa.php?action=get_bbrau', 
                    type: 'POST', 
                    dataType: 'json', 
                    data: { no_part: noAlat },
                    success: function(res) {
                        if(res.status === 'success') {
                            var alat = res.alat;
                            var history = res.history;

                            $('#det_no_alat').html('<strong>' + alat.no_part + '</strong>');
                            $('#det_nama_alat').text(alat.nama_part);
                            $('#det_seksi').text(alat.seksi_pemilik);
                            $('#det_spek').text(alat.spesifikasi);

                            var tbody = '';
                            if(history.length > 0) {
                                var no = 1;
                                history.forEach(function(h) {
                                    var p = h.tanggal_pengecekan.split('-');
                                    var tglIndo = p[2] + '/' + p[1] + '/' + p[0];
                                    
                                    var statusLabel = '';
                                    if(h.status_kalibrasi === 'Good' || h.status_kalibrasi === 'OK') {
                                        statusLabel = '<span class="label label-success">OK</span>';
                                    } else if (h.status_kalibrasi === 'Bad' || h.status_kalibrasi === 'BURUK') {
                                        statusLabel = '<span class="label label-danger">BURUK</span>';
                                    } else {
                                        statusLabel = '<span class="label label-default">'+h.status_kalibrasi+'</span>';
                                    }

                                    tbody += '<tr>';
                                    tbody += '<td class="text-center">' + no + '</td>';
                                    tbody += '<td class="text-center"><strong>' + tglIndo + '</strong></td>';
                                    tbody += '<td class="text-center">' + statusLabel + '</td>';
                                    tbody += '<td class="text-center">' + (h.created_by || '-') + '</td>';
                                    tbody += '<td>' + (h.keterangan || '-') + '</td>';
                                    tbody += '</tr>';
                                    no++;
                                });
                            } else {
                                tbody = '<tr><td colspan="5" class="text-center text-muted">Tidak ada riwayat pengecekan untuk alat ini.</td></tr>';
                            }
                            $('#body_history').html(tbody);
                        } else {
                            $('#body_history').html('<tr><td colspan="5" class="text-center text-danger">Gagal mengambil data. ' + res.message + '</td></tr>');
                        }
                    }, 
                    error: function() { 
                        $('#body_history').html('<tr><td colspan="5" class="text-center text-danger">Koneksi Error! Gagal mengambil data.</td></tr>'); 
                    }
                });
            });

        });
    </script>
</body>
</html>
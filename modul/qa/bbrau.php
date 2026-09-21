<?php
session_start();
if ($_SESSION['login'] == 0) { header('location: ../../logout.php'); exit(); }
include "../../inc/inc_koneksi.php";

$nama_petugas = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : $_SESSION['userid'];

mysqli_query($konek, "CREATE TABLE IF NOT EXISTS qa_alat_rusak LIKE qa_part");

// Database BBRAU = Semua Alat (Aktif digabung Rusak)
$q_bbrau = mysqli_query($konek, "
    SELECT no_part, nama_part, spesifikasi, seksi_pemilik, type, status FROM qa_part 
    UNION 
    SELECT no_part, nama_part, spesifikasi, seksi_pemilik, type, status FROM qa_alat_rusak 
    ORDER BY no_part ASC
");

// Database Karantina = Alat Rusak Saja
$q_rusak = mysqli_query($konek, "SELECT * FROM qa_alat_rusak ORDER BY updated_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>BBRAU & Karantina | QA System</title>
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        body { padding-top: 80px; background-color: #f4f6f9; }
        .card-custom { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-top: 5px solid #1E3F66; margin-bottom: 30px;}
        .card-rusak { border-top: 5px solid #d9534f; }
        .table th { background-color: #f8f9fa; color: #333; text-align: center; border-bottom: 2px solid #ddd !important; }
        .table td { text-align: center; vertical-align: middle !important; }
    </style>
</head>
<body>
    <nav class="navbar navbar-default navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header"><a class="navbar-brand" href="../../media.php"><img class="img-responsive" src="../../images/toto.png" style="height: 25px; margin-top: -3px;"></a></div>
            <ul class="nav navbar-nav navbar-left"><li><a href="cek_alat.php"><i class="fa fa-arrow-left"></i> Kembali ke Cek Alat</a></li></ul>
            <ul class="nav navbar-nav navbar-right">
                <li><a href="masterdata.php" style="padding-top: 10px; padding-bottom: 10px;"><button class="btn btn-warning btn-sm" style="font-weight: bold; color: #333;"><i class="fa fa-calendar"></i> Master Jadwal</button></a></li>
                <li><a href="#"><span style='color: #1469EA; font-weight: bold;'><i class="fa fa-user"></i> <?php echo $nama_petugas; ?></span></a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container-fluid" style="padding: 0 30px;">
        
        <!-- TABEL BBRAU (SEMUA ALAT) -->
        <div class="card-custom">
            <h3 style="margin-top:0; font-weight:bold; color:#1E3F66;"><i class="fa fa-book"></i> Database BBRAU (Buku Besar)</h3>
            <p class="text-muted">Kumpulan rekam jejak seluruh alat ukur yang pernah atau masih beroperasi.</p>
            <hr>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="5%">NO</th>
                            <th width="15%">NO ALAT</th>
                            <th width="25%">NAMA ALAT</th>
                            <th width="20%">SPESIFIKASI</th>
                            <th width="10%">TYPE</th>
                            <th width="10%">STATUS</th>
                            <th width="15%">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if(mysqli_num_rows($q_bbrau) > 0) {
                            while($r = mysqli_fetch_assoc($q_bbrau)) {
                                $lbl = ($r['status'] == 'aktif') ? "<span class='label label-success'>AKTIF</span>" : "<span class='label label-danger'>RUSAK</span>";
                                $noTxt = ($r['status'] == 'aktif') ? $r['no_part'] : "<span class='text-danger'>{$r['no_part']}</span>";
                                echo "<tr>
                                    <td>{$no}</td>
                                    <td><strong>{$noTxt}</strong></td>
                                    <td>{$r['nama_part']}</td>
                                    <td>{$r['spesifikasi']}</td>
                                    <td>{$r['type']}</td>
                                    <td>{$lbl}</td>
                                    <td>
                                        <!-- Tombol print dipause sesuai request, tinggal diuncomment nanti -->
                                        <!-- <button class='btn btn-xs btn-primary btn-print-bbrau' data-no='{$r['no_part']}'><i class='fa fa-print'></i> Cetak BBRAU</button> -->
                                        <span class='text-muted' style='font-size:11px;'>(Fitur Cetak ditahan)</span>
                                    </td>
                                </tr>";
                                $no++;
                            }
                        } else { echo "<tr><td colspan='7' class='text-center'>Belum ada data.</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TABEL ALAT RUSAK -->
        <div class="card-custom card-rusak">
            <h3 style="margin-top:0; font-weight:bold; color:#d9534f;"><i class="fa fa-archive"></i> Database Alat Rusak (Karantina)</h3>
            <p class="text-muted">Daftar alat ukur yang telah divonis <b>BURUK / RUSAK</b> dan dinonaktifkan dari jadwal kalibrasi harian.</p>
            <hr>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="5%">NO</th>
                            <th width="15%">NO ALAT RUSAK</th>
                            <th width="20%">NAMA ALAT</th>
                            <th width="15%">SPESIFIKASI</th>
                            <th width="15%">SEKSI PEMILIK</th>
                            <th width="10%">TGL AFKIR</th>
                            <th width="20%">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if($q_rusak && mysqli_num_rows($q_rusak) > 0) {
                            while($r = mysqli_fetch_assoc($q_rusak)) {
                                $tgl_rusak = !empty($r['updated_at']) ? date('d-m-Y', strtotime($r['updated_at'])) : '-';
                                echo "<tr>
                                    <td>{$no}</td>
                                    <td><strong class='text-danger'>{$r['no_part']}</strong></td>
                                    <td>{$r['nama_part']}</td>
                                    <td>{$r['spesifikasi']}</td>
                                    <td>{$r['seksi_pemilik']}</td>
                                    <td>{$tgl_rusak}</td>
                                    <td>
                                        <!-- Tombol print dipause sesuai request -->
                                        <span class='text-muted' style='font-size:11px;'></span>
                                    </td>
                                </tr>";
                                $no++;
                            }
                        } else { echo "<tr><td colspan='7' class='text-center'>Belum ada alat yang divonis rusak.</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>
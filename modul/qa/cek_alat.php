<?php
session_start();
if ($_SESSION['login'] == 0) {
    header('location: ../../logout.php');
    exit();
}
include "../../inc/inc_koneksi.php"; 

$id_pemakai = $_SESSION['userid'];
$nama_petugas = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : $id_pemakai; 

$status_admin = isset($_SESSION['admin']) ? $_SESSION['admin'] : 'N';
$is_admin = ($status_admin == 'Y' || $status_admin == '1' || strtolower($status_admin) == 'admin' || strtolower($_SESSION['role'] ?? '') == 'admin' || strtolower($_SESSION['level'] ?? '') == 'admin');

// --- AUTO FIX DATABASE (Tanpa perlu buka phpMyAdmin) ---
$cek_tabel = mysqli_query($konek, "SHOW TABLES LIKE 'qa_part'");
if(mysqli_num_rows($cek_tabel) > 0) {
    $cek_format = mysqli_query($konek, "SHOW COLUMNS FROM qa_part LIKE 'format_laporan'");
    if(mysqli_num_rows($cek_format) == 0) {
        mysqli_query($konek, "ALTER TABLE qa_part ADD format_laporan VARCHAR(20) DEFAULT NULL");
    }
    $cek_json = mysqli_query($konek, "SHOW COLUMNS FROM qa_part LIKE 'titik_standar_json'");
    if(mysqli_num_rows($cek_json) == 0) {
        mysqli_query($konek, "ALTER TABLE qa_part ADD titik_standar_json LONGTEXT DEFAULT NULL");
    }
}
// --------------------------------------------------------

mysqli_query($konek, "CREATE TABLE IF NOT EXISTS qa_kls (id INT AUTO_INCREMENT PRIMARY KEY, nama_kelas VARCHAR(50) NOT NULL UNIQUE)");
mysqli_query($konek, "INSERT IGNORE INTO qa_kls (nama_kelas) VALUES ('KLS 1'), ('KLS 2'), ('KLS 3')");

mysqli_query($konek, "CREATE TABLE IF NOT EXISTS qa_seksi_master (id INT AUTO_INCREMENT PRIMARY KEY, nama_seksi VARCHAR(100) NOT NULL UNIQUE)");
mysqli_query($konek, "INSERT IGNORE INTO qa_seksi_master (nama_seksi) VALUES ('WH'), ('GA'), ('INJ'), ('QC'), ('MO'), ('TECH'), ('QA'), ('Maintenance')");

$q_kls = mysqli_query($konek, "SELECT * FROM qa_kls ORDER BY nama_kelas ASC");
$q_seksi = mysqli_query($konek, "SELECT * FROM qa_seksi_master ORDER BY nama_seksi ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengecekan Alat Kalibrasi | QA System</title>
    <link rel="shortcut icon" href="../../images/hrd.ico">
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="../../css/bootstrap-dialog.css" rel="stylesheet">
    <style>
        body { padding-top: 80px; background-color: #f4f6f9; }
        .card-validasi { background-color: #ffffff; border-radius: 12px; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); padding: 40px 30px; margin-top: 2vh; border-top: 5px solid #1E3F66; }
        .input-group-lg > .form-control, .input-group-lg > .input-group-addon { height: 55px; font-size: 18px; }
        .btn-validasi { background-color: #1E3F66; color: white; font-weight: bold; height: 55px; font-size: 18px; }
        .btn-validasi:hover { background-color: #0d1e33; color: white; }
        .hidden-form-section { display: none; margin-top: 20px; text-align: left; border-top: 1px dashed #ccc; padding-top: 25px; }
        .table-custom th { background-color: #f8f9fa; color: #333; text-align: center; border-bottom: 2px solid #ddd !important; font-size: 13px;}
        .table-custom td { vertical-align: middle !important; text-align: center; font-size: 13px; white-space: nowrap; }
        .control-label { color: #555; font-weight: bold; text-align: left !important; }
        .section-title { font-weight:bold; border-bottom:2px solid #eee; padding-bottom:10px; margin-bottom: 15px; margin-top: 0;}
        .text-uppercase-input { text-transform: uppercase; }
        .btn-simpan-inline { background-color: #ff9800; color: white; border: none; font-weight: bold; }
        .foto-alat-container { border: 2px dashed #ccc; border-radius: 10px; display: flex; align-items: center; justify-content: center; background-color: #fcfcfc; overflow: hidden; padding: 5px; height: 100%; min-height: 280px; aspect-ratio: 16/9;}
        .foto-alat-container img { width: 100%; height: 100%; object-fit: contain; }
        .flex-header-row { display: flex; align-items: stretch; flex-wrap: wrap; }
        .flex-col-center { display: flex; flex-direction: column; justify-content: center; }

        #form-registrasi .form-control, #form-update-kalibrasi .form-control { height: 42px; font-size: 14px; }
        #form-registrasi select.form-control, #form-update-kalibrasi select.form-control { height: 42px !important; }
        #form-registrasi .input-group-addon, #form-update-kalibrasi .input-group-addon { background-color: #e9ecef; }
        #form-registrasi .input-group-btn .btn, #form-update-kalibrasi .input-group-btn .btn { height: 42px !important; }
        
        .table-entry th { background-color: #1E3F66; color: white; text-align: center; }
        .table-entry td { text-align: center; vertical-align: middle !important; }
        .inp-hasil-kalibrasi { text-align: center; font-weight: bold; }
        .inp-koreksi { text-align: center; background-color: #f4f6f9 !important; font-weight:bold; }

        /* =========================================================
            FORM A - LAPORAN PEMERIKSAAN GAUGE
            ========================================================= */
        #form-form-a { display:none; margin-top:20px; text-align:left; border-top:1px dashed #ccc; padding-top:25px; }
        .form-a-card { border:1px solid #d7dce1; border-radius:8px; background:#fff; margin-bottom:18px; }
        .form-a-card-title { background:#1E3F66; color:#fff; font-weight:bold; padding:12px 15px; border-radius:7px 7px 0 0; }
        .form-a-body { padding:18px 20px; }
        .form-a-readonly { background:#eee !important; font-weight:bold; }
        .form-a-subtitle { font-weight:bold; color:#1E3F66; border-bottom:2px solid #eee; padding-bottom:8px; margin:0 0 15px; }
        .form-a-go { border-left:5px solid #198754; }
        .form-a-notgo { border-left:5px solid #dc3545; }
        .form-a-go .form-a-card-title { background:#198754; }
        .form-a-notgo .form-a-card-title { background:#dc3545; }
        .form-a-measure-table { width:100%; border-collapse:collapse; table-layout:fixed; }
        .form-a-measure-table th, .form-a-measure-table td { border:1px solid #bbb; padding:7px; text-align:center; vertical-align:middle; }
        .form-a-measure-table th { background:#f1f3f5; font-weight:bold; }
        .form-a-measure-table .label-col { width:20%; text-align:left; font-weight:bold; background:#fafafa; }
        .form-a-measure-table .std-col { width:20%; }
        .form-a-measure-table input { text-align:center; }
        .form-a-required-note { font-size:12px; color:#777; margin-top:5px; }
        #reg-form-a-fields { text-align:left; }
        #reg-form-a-fields .form-a-card { border:1px solid #d7dce1; border-radius:8px; background:#fff; margin-bottom:18px; }
        #reg-form-a-fields .form-a-card-title { background:#1E3F66; color:#fff; font-weight:bold; padding:12px 15px; border-radius:7px 7px 0 0; }
        #reg-form-a-fields .form-a-body { padding:18px 20px; }
        #reg-form-a-fields .form-a-subtitle { font-weight:bold; color:#1E3F66; border-bottom:2px solid #eee; padding-bottom:8px; margin:0 0 15px; }
        #reg-form-a-fields .form-a-go { border-left:5px solid #198754; }
        #reg-form-a-fields .form-a-notgo { border-left:5px solid #dc3545; }
        #reg-form-a-fields .form-a-go .form-a-card-title { background:#198754; }
        #reg-form-a-fields .form-a-notgo .form-a-card-title { background:#dc3545; }
        .form-a-summary { background:#f8f9fa; border:1px solid #ddd; padding:12px 15px; border-radius:6px; }
    /* Form B: susunan kolom mengikuti contoh laporan. */
        #tabel_form_b { background:#ffff00; color:#000; }
        #tabel_form_b th, #tabel_form_b td { background:#ffff00; border:1px solid #222; vertical-align:middle; text-align:center; }
        #tabel_form_b input[readonly] { background:transparent; border:0; box-shadow:none; text-align:center; }
        #tabel_form_b .b-tests input { margin:3px 0; min-width:80px; }
        #tabel_form_b .b-status { min-width:100px; }
        </style>
</head>
<body oncontextmenu="return false;">

    <nav class="navbar navbar-default navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header">
                <a class="navbar-brand" href="../../media.php"><img class="img-responsive" src="../../images/toto.png" style="height: 25px; margin-top: -3px;"></a>
            </div>
            <div class="collapse navbar-collapse">
                <ul class="nav navbar-nav navbar-left"><li><a href="../../media.php"><i class="fa fa-arrow-left"></i> Kembali ke Menu Utama</a></li></ul>
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="daftar_kalibrasi.php" style="padding-top: 10px; padding-bottom: 10px;"><button class="btn btn-warning btn-sm" style="font-weight: bold; color: #333;"><i class="fa fa-list-alt"></i> Tabel Jadwal Kalibrasi</button></a></li>
                    <li><a href="#"><span style='color: #1469EA; font-weight: bold;'><i class="fa fa-fw fa-user"></i> <?php echo $nama_petugas; ?></span></a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid" style="padding: 0 30px;">
        <div class="row">
            <div class="col-md-12"> 
                <div class="card-validasi">
                    
                    <div class="row flex-header-row">
                        <div class="col-md-6 flex-col-center" style="border-right: 1px solid #ddd; padding-right: 30px; text-align: center;">
                            <h3 style="font-weight: bold; color:#333; margin-top:0;">Check Nomor Alat</h3>
                            <p class="text-muted" style="margin-bottom: 30px;">Ketik Nomor Alat untuk mengecek status atau mendaftar baru.</p>

                            <form id="formCekAlat" onsubmit="return false;">
                                <div class="form-group" id="group-input">
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-addon"><i class="fa fa-barcode"></i></span>
                                        <input type="text" class="form-control text-center text-uppercase-input" id="nomor_alat" placeholder="Ketik No. Alat" autocomplete="off">
                                    </div>
                                    <span id="error-msg" class="text-danger" style="display:none; text-align:left; margin-top:8px;"><i class="fa fa-exclamation-circle"></i> Nomor alat wajib diisi!</span>
                                </div>
                                <div class="form-group" style="margin-top: 20px; margin-bottom: 0;">
                                    <button type="button" id="btn-cek" class="btn btn-validasi btn-block">Cek Alat</button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="col-md-6 flex-col-center" style="padding-left: 30px; text-align: center;">
                            <h4 style="font-weight: bold; color:#555; margin-top:0; margin-bottom: 15px;">Visual Alat</h4>
                            <div class="foto-alat-container">
                                <img src="../../images/no-image.png" onerror="this.src='https://via.placeholder.com/300x200?text=Tidak+Ada+Gambar'" id="preview_gambar_alat">
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- FORM A - LAPORAN PEMERIKSAAN GAUGE         -->
                    <!-- ========================================== -->
                    <div id="form-form-a">
                        <div class="form-a-card">
                            <div class="form-a-card-title">
                                <i class="fa fa-file-text-o"></i> LAPORAN PEMERIKSAAN GAUGE
                            </div>
                            <div class="form-a-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h4 class="form-a-subtitle">Data Alat</h4>
                                        <div class="form-group">
                                            <label>No. Alat</label>
                                            <input type="text" id="fa_no_alat" class="form-control form-a-readonly" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>Nama Alat</label>
                                            <input type="text" id="fa_nama_alat" class="form-control form-a-readonly" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>Spesifikasi</label>
                                            <textarea id="fa_spesifikasi" class="form-control form-a-readonly" rows="2" readonly></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label>Seksi</label>
                                                    <input type="text" id="fa_seksi" class="form-control form-a-readonly" readonly>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label>Format Kalibrasi</label>
                                                    <input type="text" id="fa_format" class="form-control form-a-readonly" value="FORM A" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Jadwal Kalibrasi</label>
                                            <input type="text" id="fa_jadwal" class="form-control form-a-readonly" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <h4 class="form-a-subtitle">Data Pemeriksaan</h4>
                                        <div class="form-group">
                                            <label>No. Laporan <span class="text-danger">*</span></label>
                                            <input type="text" id="fa_no_laporan" class="form-control" placeholder="Contoh: 02/Lap/Kalibrasi/VIII/2026">
                                        </div>
                                        <div class="form-group">
                                            <label>Tanggal Periksa <span class="text-danger">*</span></label>
                                            <input type="date" id="fa_tanggal_periksa" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label>Metode</label>
                                            <input type="text" id="fa_metode" class="form-control" placeholder="Contoh: STIS-SOPF-731015">
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label>Suhu</label>
                                                    <div class="input-group">
                                                        <input type="number" step="any" id="fa_suhu" class="form-control" placeholder="21">
                                                        <span class="input-group-addon">°C</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label>Humidity</label>
                                                    <div class="input-group">
                                                        <input type="number" step="any" id="fa_humidity" class="form-control" placeholder="55">
                                                        <span class="input-group-addon">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Petugas</label>
                                            <input type="text" id="fa_petugas" class="form-control form-a-readonly" value="<?php echo $nama_petugas; ?>" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-a-card">
                            <div class="form-a-card-title">
                                <i class="fa fa-wrench"></i> DATA STANDAR UMUM
                            </div>
                            <div class="form-a-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Traceability</label>
                                            <input type="text" id="fa_traceability" class="form-control" placeholder="Contoh: Gauge Block Kl 0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-a-card form-a-go">
                            <div class="form-a-card-title">
                                <i class="fa fa-check"></i> GO
                            </div>
                            <div class="form-a-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>No. Alat Standar GO <span class="text-danger">*</span></label>
                                            <input type="text" id="fa_go_std_no_alat" class="form-control" placeholder="Contoh: 02.6 A19..">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Spesifikasi Alat Standar GO <span class="text-danger">*</span></label>
                                            <input type="text" id="fa_go_std_spesifikasi" class="form-control" placeholder="Contoh: 0~25 (0.001)">
                                        </div>
                                    </div>
                                </div>
                                <table class="form-a-measure-table">
                                    <thead>
                                        <tr>
                                            <th rowspan="2" class="label-col">Parameter</th>
                                            <th colspan="3">STD UKURAN</th>
                                            <th colspan="3">HASIL UKUR - OUT Ø</th>
                                            <th colspan="3">HASIL UKUR - PITCH Ø</th>
                                        </tr>
                                        <tr>
                                            <th>Nilai</th>
                                            <th>Toleransi</th>
                                            <th>Range</th>
                                            <th>DEP</th>
                                            <th>TEN</th>
                                            <th>BEL</th>
                                            <th>DEP</th>
                                            <th>TEN</th>
                                            <th>BEL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="label-col">OUT Ø</td>
                                            <td><input type="number" step="any" id="fa_go_std_out" class="form-control"></td>
                                            <td><input type="text" id="fa_go_tol_out" class="form-control" placeholder="±0.011"></td>
                                            <td><input type="text" id="fa_go_range_out" class="form-control" placeholder="Contoh: 23.778 ~ 23.800"></td>
                                            <td><input type="number" step="any" id="fa_go_out_dep" class="form-control"></td>
                                            <td><input type="number" step="any" id="fa_go_out_ten" class="form-control"></td>
                                            <td><input type="number" step="any" id="fa_go_out_bel" class="form-control"></td>
                                            <td><input type="number" step="any" id="fa_go_pitch_dep" class="form-control"></td>
                                            <td><input type="number" step="any" id="fa_go_pitch_ten" class="form-control"></td>
                                            <td><input type="number" step="any" id="fa_go_pitch_bel" class="form-control"></td>
                                        </tr>
                                        <tr>
                                            <td class="label-col">PITCH Ø</td>
                                            <td><input type="number" step="any" id="fa_go_std_pitch" class="form-control"></td>
                                            <td><input type="text" id="fa_go_tol_pitch" class="form-control" placeholder="±0.005"></td>
                                            <td><input type="text" id="fa_go_range_pitch" class="form-control" placeholder="Contoh: 22.810 ~ 22.820"></td>
                                            <td colspan="3" class="text-muted">Mengikuti hasil OUT Ø di atas</td>
                                            <td colspan="3" class="text-muted">Mengikuti hasil PITCH Ø di atas</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="form-a-card form-a-notgo">
                            <div class="form-a-card-title">
                                <i class="fa fa-times"></i> NOT GO
                            </div>
                            <div class="form-a-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>No. Alat Standar NOT GO <span class="text-danger">*</span></label>
                                            <input type="text" id="fa_notgo_std_no_alat" class="form-control" placeholder="Contoh: 99.4 A10011.1">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Spesifikasi Alat Standar NOT GO <span class="text-danger">*</span></label>
                                            <input type="text" id="fa_notgo_std_spesifikasi" class="form-control" placeholder="Contoh: ø 1.3855">
                                        </div>
                                    </div>
                                </div>
                                <table class="form-a-measure-table">
                                    <thead>
                                        <tr>
                                            <th rowspan="2" class="label-col">Parameter</th>
                                            <th colspan="3">STD UKURAN</th>
                                            <th colspan="3">HASIL UKUR - OUT Ø</th>
                                            <th colspan="3">HASIL UKUR - PITCH Ø</th>
                                        </tr>
                                        <tr>
                                            <th>Nilai</th>
                                            <th>Toleransi</th>
                                            <th>Range</th>
                                            <th>DEP</th>
                                            <th>TEN</th>
                                            <th>BEL</th>
                                            <th>DEP</th>
                                            <th>TEN</th>
                                            <th>BEL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="label-col">OUT Ø</td>
                                            <td><input type="number" step="any" id="fa_notgo_std_out" class="form-control"></td>
                                            <td><input type="text" id="fa_notgo_tol_out" class="form-control" placeholder="±0.011"></td>
                                            <td><input type="text" id="fa_notgo_range_out" class="form-control" placeholder="Contoh: 23.778 ~ 23.800"></td>
                                            <td><input type="number" step="any" id="fa_notgo_out_dep" class="form-control"></td>
                                            <td><input type="number" step="any" id="fa_notgo_out_ten" class="form-control"></td>
                                            <td><input type="number" step="any" id="fa_notgo_out_bel" class="form-control"></td>
                                            <td><input type="number" step="any" id="fa_notgo_pitch_dep" class="form-control"></td>
                                            <td><input type="number" step="any" id="fa_notgo_pitch_ten" class="form-control"></td>
                                            <td><input type="number" step="any" id="fa_notgo_pitch_bel" class="form-control"></td>
                                        </tr>
                                        <tr>
                                            <td class="label-col">PITCH Ø</td>
                                            <td><input type="number" step="any" id="fa_notgo_std_pitch" class="form-control"></td>
                                            <td><input type="text" id="fa_notgo_tol_pitch" class="form-control" placeholder="±0.005"></td>
                                            <td><input type="text" id="fa_notgo_range_pitch" class="form-control" placeholder="Contoh: 22.810 ~ 22.820"></td>
                                            <td colspan="3" class="text-muted">Mengikuti hasil OUT Ø di atas</td>
                                            <td colspan="3" class="text-muted">Mengikuti hasil PITCH Ø di atas</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="form-a-card">
                            <div class="form-a-card-title">
                                <i class="fa fa-info-circle"></i> HASIL AKHIR
                            </div>
                            <div class="form-a-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Kode Varian</label>
                                            <input type="text" id="fa_kode_varian" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Warna</label>
                                            <input type="text" id="fa_warna" class="form-control" placeholder="Contoh: HIJAU">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>KEP</label>
                                            <select id="fa_kep" class="form-control">
                                                <option value="">-- Pilih --</option>
                                                <option value="OK">OK</option>
                                                <option value="NG">NG</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Periksa Berikut</label>
                                            <input type="date" id="fa_periksa_berikut" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Keterangan</label>
                                            <textarea id="fa_keterangan" class="form-control" rows="2" placeholder="Catatan opsional..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row" style="margin-top:20px; border-top:1px solid #ddd; padding-top:15px;">
                            <div class="col-sm-6">
                                <button type="button" id="btn-batal-fa" class="btn btn-default btn-block" style="height:50px;font-weight:bold;">Tutup</button>
                            </div>
                            <div class="col-sm-6">
                                <button type="button" id="btn-simpan-fa" class="btn btn-primary btn-block" style="height:50px;font-weight:bold;font-size:16px;">
                                    Simpan Form A <i class="fa fa-save"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- 1. FORM UPDATE KALIBRASI (STANDARD / B)    -->
                    <!-- ========================================== -->
                    <div id="form-update-kalibrasi" class="hidden-form-section">
                        <h4 class="section-title text-success"><i class="fa fa-list-alt"></i> Detail & Riwayat Kalibrasi (Top 5)</h4>
                        <div class="table-responsive" style="margin-bottom: 30px;">
                            <table class="table table-bordered table-striped table-hover table-custom" id="tabel-gabungan">
                                <thead>
                                    <tr><th width="3%">NO</th><th>NO ALAT</th><th>NAMA ALAT</th><th>SPESIFIKASI</th><th>SEKSI PEMILIK</th><th>LOKASI</th><th>TYPE</th><th>KLS</th><th style="background-color:#e2e6ea;">STATUS</th><th style="background-color:#e2e6ea;">TGL QA</th></tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <h4 class="section-title text-info"><i class="fa fa-edit"></i> Input Update Hasil Kalibrasi</h4>
                        
                        <div class="form-horizontal" style="padding: 0 15px;">
                            <div class="form-group">
                                <label class="col-sm-2 control-label"><span id="label-qa-no-alat">No. Alat</span> <span style="float:right;">:</span></label>
                                <div class="col-sm-4"><input type="text" class="form-control text-uppercase-input" id="qa_nomor_alat" disabled style="background-color: #eee; font-weight:bold;"></div>
                                <label class="col-sm-2 control-label">Nama Alat <span style="float:right;">:</span></label>
                                <div class="col-sm-4"><input type="text" class="form-control" id="qa_nama_alat" disabled style="background-color: #eee;"></div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Metode Uji <span style="float:right;">:</span></label>
                                <div class="col-sm-4"><input type="text" id="qa_metode" class="form-control" disabled style="background-color: #eee;"></div>
                                <label class="col-sm-2 control-label"><span id="label_qa_standar_uji">Standar Uji</span> <span style="float:right;">:</span></label>
                                <div class="col-sm-4"><input type="text" id="qa_standar_uji" class="form-control" disabled style="background-color: #eee;"></div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Traceability <span style="float:right;">:</span></label>
                                <div class="col-sm-4"><input type="text" id="qa_trace" class="form-control" disabled style="background-color: #eee;"></div>
                                <label class="col-sm-2 control-label">Standar Tol (±) <span style="float:right;">:</span></label>
                                <div class="col-sm-4">
                                    <div class="input-group">
                                        <input type="text" id="qa_toleransi" class="form-control" disabled style="background-color: #eee; font-weight:bold;">
                                        <span class="input-group-addon qa_satuan_label"></span>
                                    </div>
                                    <input type="hidden" id="qa_toleransi_value">
                                    <input type="hidden" id="qa_resolusi_value">
                                </div>
                            </div>

                            <!-- TABEL INPUT HASIL KALIBRASI -->
                            <div class="form-group" style="margin-top: 25px;">
                                <div class="col-sm-12">
                                    <!-- TABEL KUSTOM / STANDAR -->
                                    <table class="table table-bordered table-entry" id="tabel_input_kalibrasi">
                                        <thead></thead>
                                        <tbody></tbody>
                                    </table>

                                    <!-- TABEL FORM B (AIR DRIVER - 3x UJI) -->
                                    <div id="wrapper_form_b" style="display:none;">
                                        <h5 class="text-primary fw-bold mb-2"><i class="fa fa-tools"></i> Pengujian Torsi Air Driver (Form B)</h5>
                                        <div class="row" style="margin-bottom:15px;">
                                            <div class="col-md-6">
                                                <label>No. Laporan</label>
                                                <input type="text" id="qa_no_laporan_b" class="form-control" placeholder="Contoh: 06/Lap/Periksa/VIII/2026">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Tanggal Pemeriksaan</label>
                                                <input type="date" id="qa_tanggal_b" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                        <table class="table table-bordered table-striped text-center" id="tabel_form_b">
                                            <thead><tr>
                                                <th>Tekanan Udara</th><th colspan="2">Posisi Handle Pengatur Torsi</th>
                                                <th colspan="2">Standar Torsi Pengunci Ulir (kgf.cm)</th>
                                                <th>Hasil (kgf.cm)</th><th>Pertimbangan Kelulusan</th>
                                            </tr></thead>
                                            <tbody id="tbody_form_b"></tbody>
                                        </table>
                                        <p class="help-block">Tiga nilai standar berasal dari registrasi. Isi Hasil kalibrasi secara terpisah (kgf.cm); keputusan dibandingkan dengan rentang Min–Max.</p><div class="row"><div class="col-sm-6"><label>Diperiksa (Foreman)</label><input id="qa_foreman_b" class="form-control" placeholder="Nama pemeriksa"></div><div class="col-sm-6"><label>Disetujui (Supervisor)</label><input id="qa_supervisor_b" class="form-control" placeholder="Nama penyetuju"></div></div><p class="help-block">Nama pada laporan; bukan tanda persetujuan elektronik.</p><button type="button" id="btn-complete-b" class="btn btn-warning" style="display:none">Simpan tiga standar alat lama</button><p id="note-complete-b" class="help-block" style="display:none">Alat lama belum mempunyai tiga nilai standar. Lengkapi sekali dan simpan sebelum mengisi hasil kalibrasi.</p><div id="qa_history_b"></div>
                                        <button type="button" class="btn btn-sm btn-info" id="btn_tambah_baris_b" style="font-weight:bold;">
                                            <i class="fa fa-plus"></i> Tambah Baris Uji
                                        </button>
                                    </div>

                                    <!-- FORM C (PEMERIKSAAN TEMPERATUR) -->
                                    <div id="wrapper_form_c" style="display:none;">
                                        <h5 class="text-primary" style="font-weight:bold; margin-bottom:15px;">
                                            <i class="fa fa-thermometer-half"></i> Pemeriksaan Temperatur (Form C)
                                        </h5>

                                        <div class="row" style="margin-bottom:15px;">
                                            <div class="col-md-6">
                                                <label>No. Laporan <span class="text-danger">*</span></label>
                                                <input type="text" id="qa_no_laporan_c" class="form-control" placeholder="Contoh: 04/Lap/Kalibrasi/VII/2026">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Tanggal Periksa <span class="text-danger">*</span></label>
                                                <input type="date" id="qa_tanggal_c" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered table-entry" id="tabel_form_c">
                                                <thead>
                                                    <tr>
                                                        <th width="6%">NO</th>
                                                        <th width="17%">SETTING (SV)<br><small>(°C)</small></th>
                                                        <th width="17%">ACTUAL (PV)<br><small>(°C)</small></th>
                                                        <th width="17%">ALAT STD<br><small>(°C)</small></th>
                                                        <th width="17%">SELISIH<br><small>(°C)</small></th>
                                                        <th width="26%">PERTIMBANGAN KELULUSAN</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbody_form_c"></tbody>
                                            </table>
                                        </div>

                                        <p class="help-block">
                                            <b>Setting (SV)</b> dan <b>Alat STD</b> berasal dari registrasi dan tidak dapat diubah saat QA.
                                            Petugas QA hanya mengisi <b>Actual (PV)</b>. Selisih dihitung otomatis dari <b>Actual (PV) - Alat STD</b>, lalu dibandingkan dengan Standar Toleransi.
                                        </p>

                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label>Diperiksa (Foreman)</label>
                                                <input type="text" id="qa_foreman_c" class="form-control" placeholder="Nama pemeriksa">
                                            </div>
                                            <div class="col-sm-6">
                                                <label>Disetujui (Supervisor)</label>
                                                <input type="text" id="qa_supervisor_c" class="form-control" placeholder="Nama penyetuju">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- FORM D (PEMERIKSAAN MULTIMETER) -->
                                    <div id="wrapper_form_d" style="display:none;">
                                        <h5 class="text-primary" style="font-weight:bold; margin-bottom:15px;">
                                            <i class="fa fa-bolt"></i> Pemeriksaan Multimeter (Form D)
                                        </h5>

                                        <div class="row" style="margin-bottom:15px;">
                                            <div class="col-md-6">
                                                <label>No. Laporan <span class="text-danger">*</span></label>
                                                <input type="text" id="qa_no_laporan_d" class="form-control" placeholder="Contoh: 03/Lap/Periksa/VIII/2026">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Tanggal Periksa <span class="text-danger">*</span></label>
                                                <input type="date" id="qa_tanggal_d" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered table-entry" id="tabel_form_d">
                                                <thead>
                                                    <tr>
                                                        <th width="6%">NO</th>
                                                        <th width="12%">MODE</th>
                                                        <th width="18%">SETTING</th>
                                                        <th width="18%">STANDAR</th>
                                                        <th width="18%">HASIL</th>
                                                        <th width="18%">KOREKSI</th>
                                                        <th width="10%">UNIT</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbody_form_d"></tbody>
                                            </table>
                                        </div>

                                        <p class="help-block">
                                            <b>Mode, Setting, Standar, dan Unit</b> berasal dari registrasi.
                                            Petugas QA hanya mengisi <b>Hasil</b>. Koreksi dihitung otomatis dengan rumus <b>Standar - Hasil</b>.
                                        </p>

                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label>Diperiksa (Foreman)</label>
                                                <input type="text" id="qa_foreman_d" class="form-control" placeholder="Nama pemeriksa">
                                            </div>
                                            <div class="col-sm-6">
                                                <label>Disetujui (Supervisor)</label>
                                                <input type="text" id="qa_supervisor_d" class="form-control" placeholder="Nama penyetuju">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- FORM H (PEMERIKSAAN THREAD PLUG GAUGE) -->
                                    <div id="wrapper_form_h" style="display:none;">
                                        <h5 class="text-primary" style="font-weight:bold; margin-bottom:15px;">
                                            <i class="fa fa-sliders"></i> Pemeriksaan Thread Plug Gauge (Form H)
                                        </h5>

                                        <div class="row" style="margin-bottom:15px;">
                                            <div class="col-md-6">
                                                <label>No. Laporan <span class="text-danger">*</span></label>
                                                <input type="text" id="qa_no_laporan_h" class="form-control" placeholder="Contoh: 07/Lap/Periksa/VI/2026">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Tanggal Periksa <span class="text-danger">*</span></label>
                                                <input type="date" id="qa_tanggal_h" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered table-entry" id="tabel_form_h">
                                                <thead>
                                                    <tr>
                                                        <th rowspan="2" style="vertical-align:middle;">POSISI UKUR</th>
                                                        <th colspan="2">ALAT STANDAR</th>
                                                        <th colspan="2">STD UKURAN</th>
                                                        <th colspan="3">HASIL OUT Ø</th>
                                                        <th colspan="3">HASIL PITCH Ø</th>
                                                    </tr>
                                                    <tr>
                                                        <th>SPESIFIKASI</th>
                                                        <th>NO. ALAT</th>
                                                        <th>OUT Ø</th>
                                                        <th>PITCH Ø</th>
                                                        <th>DEPAN</th>
                                                        <th>TENGAH</th>
                                                        <th>BELAKANG</th>
                                                        <th>DEPAN</th>
                                                        <th>TENGAH</th>
                                                        <th>BELAKANG</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbody_form_h"></tbody>
                                            </table>
                                        </div>

                                        <p class="help-block">
                                            Data <b>Posisi Ukur, Alat Standar, STD OUT/PITCH, toleransi, dan range</b> berasal dari registrasi.
                                            Petugas QA hanya mengisi hasil ukur yang berlaku. Kolom yang tidak mempunyai standar boleh kosong.
                                        </p>

                                        <div class="row" style="margin-bottom:15px;">
                                            <div class="col-sm-6">
                                                <label>Kode Warna <span class="text-danger">*</span></label>
                                                <input type="text" id="qa_h_kode_warna" class="form-control text-uppercase-input" placeholder="Contoh: HIJAU">
                                            </div>
                                            <div class="col-sm-6">
                                                <label>KEP / Putusan <span class="text-danger">*</span></label>
                                                <select id="qa_h_putusan" class="form-control" style="font-weight:bold;">
                                                    <option value="">-- Pilih --</option>
                                                    <option value="OK">OK</option>
                                                    <option value="BAD">BAD</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label>Diperiksa (Foreman)</label>
                                                <input type="text" id="qa_foreman_h" class="form-control" placeholder="Nama pemeriksa">
                                            </div>
                                            <div class="col-sm-6">
                                                <label>Disetujui (Supervisor)</label>
                                                <input type="text" id="qa_supervisor_h" class="form-control" placeholder="Nama penyetuju">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- FORM I (PEMERIKSAAN GAUGE) -->
                                    <div id="wrapper_form_i" style="display:none;">
                                        <h5 class="text-primary" style="font-weight:bold; margin-bottom:15px;">
                                            <i class="fa fa-check-square-o"></i> Pemeriksaan Gauge (Form I)
                                        </h5>

                                        <div class="row" style="margin-bottom:15px;">
                                            <div class="col-md-6">
                                                <label>No. Laporan <span class="text-danger">*</span></label>
                                                <input type="text" id="qa_no_laporan_i" class="form-control" placeholder="Contoh: 08/Lap/Periksa/VI/2026">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Tanggal Periksa <span class="text-danger">*</span></label>
                                                <input type="date" id="qa_tanggal_i" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>

                                        <div class="row" style="margin-bottom:15px;">
                                            <div class="col-md-6">
                                                <label>Spesifikasi Alat Standar</label>
                                                <input type="text" id="qa_i_std_spesifikasi" class="form-control" readonly style="background:#eee;">
                                            </div>
                                            <div class="col-md-6">
                                                <label>No. Alat Standar</label>
                                                <input type="text" id="qa_i_std_no" class="form-control" readonly style="background:#eee;">
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered table-entry" id="tabel_form_i">
                                                <thead>
                                                    <tr>
                                                        <th width="25%">LOKASI PERIKSA</th>
                                                        <th width="35%">HASIL PERIKSA</th>
                                                        <th width="20%">KODE WARNA</th>
                                                        <th width="20%">PUTUSAN</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><input type="text" class="form-control text-center" value="ATAS" readonly style="background:#eee;"></td>
                                                        <td><input type="text" id="qa_i_hasil_atas" class="form-control text-center" placeholder="Contoh: 4/12 PU"></td>
                                                        <td rowspan="2" style="vertical-align:middle;">
                                                            <input type="text" id="qa_i_kode_warna" class="form-control text-center text-uppercase-input" value="HIJAU" placeholder="HIJAU">
                                                        </td>
                                                        <td rowspan="2" style="vertical-align:middle;">
                                                            <select id="qa_i_putusan" class="form-control" style="font-weight:bold;">
                                                                <option value="">-- Pilih --</option>
                                                                <option value="OK">OK</option>
                                                                <option value="NG">NG</option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><input type="text" class="form-control text-center" value="BAWAH" readonly style="background:#eee;"></td>
                                                        <td><input type="text" id="qa_i_hasil_bawah" class="form-control text-center" placeholder="Contoh: 4/12 PU"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <p class="help-block">
                                            Data <b>Spesifikasi Alat Standar</b> dan <b>No. Alat Standar</b> berasal dari registrasi.
                                            Petugas QA mengisi hasil pemeriksaan posisi <b>ATAS</b> dan <b>BAWAH</b>, Kode Warna, dan Putusan.
                                        </p>

                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label>Diperiksa (Foreman)</label>
                                                <input type="text" id="qa_foreman_i" class="form-control" placeholder="Nama pemeriksa">
                                            </div>
                                            <div class="col-sm-6">
                                                <label>Disetujui (Supervisor)</label>
                                                <input type="text" id="qa_supervisor_i" class="form-control" placeholder="Nama penyetuju">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- FORM J (VERIFIKASI RADIUS GAUGE) -->
                                    <div id="wrapper_form_j" style="display:none;">
                                        <h5 class="text-primary" style="font-weight:bold; margin-bottom:15px;">
                                            <i class="fa fa-check-circle-o"></i> Verifikasi Radius Gauge (Form J)
                                        </h5>

                                        <div class="row" style="margin-bottom:15px;">
                                            <div class="col-md-6">
                                                <label>No. Laporan <span class="text-danger">*</span></label>
                                                <input type="text" id="qa_no_laporan_j" class="form-control" placeholder="Contoh: 01/Lap/Verifikasi/VIII/2026">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Tanggal Verifikasi <span class="text-danger">*</span></label>
                                                <input type="date" id="qa_tanggal_j" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered table-entry" id="tabel_form_j">
                                                <thead>
                                                    <tr>
                                                        <th width="6%">NO</th>
                                                        <th width="19%">NO. ALAT</th>
                                                        <th width="22%">SPESIFIKASI</th>
                                                        <th width="25%">SPESIFIKASI ALAT VERIFIKASI</th>
                                                        <th width="13%">HASIL VERIFIKASI</th>
                                                        <th width="15%">TGL. VERIFIKASI BERIKUT</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td style="vertical-align:middle;">1</td>
                                                        <td><input type="text" id="qa_j_no_alat" class="form-control text-center" readonly style="background:#eee;"></td>
                                                        <td><input type="text" id="qa_j_spesifikasi" class="form-control text-center" readonly style="background:#eee;"></td>
                                                        <td><input type="text" id="qa_j_alat_verifikasi" class="form-control text-center" readonly style="background:#eee;"></td>
                                                        <td>
                                                            <select id="qa_j_hasil" class="form-control" style="font-weight:bold;">
                                                                <option value="">-- Pilih --</option>
                                                                <option value="O">O - OKE</option>
                                                                <option value="X">X - TIDAK OKE/BAD</option>
                                                            </select>
                                                        </td>
                                                        <td><input type="text" id="qa_j_tgl_berikut" class="form-control text-center" readonly style="background:#eee;"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <p class="help-block">
                                            <b>No. Alat, Spesifikasi, dan Alat Verifikasi</b> berasal dari registrasi.
                                            Petugas QA hanya menentukan <b>Hasil Verifikasi</b>: <b>O = OKE</b> atau <b>X = TIDAK OKE/BAD</b>.
                                        </p>

                                        <input type="hidden" id="qa_j_periode">

                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label>Diperiksa (Foreman)</label>
                                                <input type="text" id="qa_foreman_j" class="form-control" placeholder="Nama pemeriksa">
                                            </div>
                                            <div class="col-sm-6">
                                                <label>Disetujui (Supervisor)</label>
                                                <input type="text" id="qa_supervisor_j" class="form-control" placeholder="Nama penyetuju">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- FORM K (PEMERIKSAAN VOLTAGE ULTRASONIC) -->
                                    <div id="wrapper_form_k" style="display:none;">
                                        <h5 class="text-primary" style="font-weight:bold; margin-bottom:15px;"><i class="fa fa-bolt"></i> Pemeriksaan Voltage Ultrasonic (Form K)</h5>
                                        <div class="row" style="margin-bottom:15px;">
                                            <div class="col-md-6"><label>No. Laporan <span class="text-danger">*</span></label><input type="text" id="qa_no_laporan_k" class="form-control" placeholder="Contoh: 02/Lap/Periksa/VIII/2026"></div>
                                            <div class="col-md-6"><label>Tanggal Periksa <span class="text-danger">*</span></label><input type="date" id="qa_tanggal_k" class="form-control" value="<?php echo date('Y-m-d'); ?>"></div>
                                        </div>
                                        <div class="table-responsive"><table class="table table-bordered table-entry" id="tabel_form_k"><thead><tr><th width="7%">NO</th><th width="23%">NILAI YANG DITENTUKAN</th><th width="23%">NILAI PENGUKURAN</th><th width="19%">SELISIH</th><th width="28%">PERTIMBANGAN KELULUSAN</th></tr></thead><tbody id="tbody_form_k"></tbody></table></div>
                                        <p class="help-block"><b>Nilai yang Ditentukan</b> berasal dari registrasi. Petugas mengisi <b>Nilai Pengukuran</b>. Selisih = Nilai Ditentukan - Nilai Pengukuran, lalu dibandingkan dengan toleransi persen.</p>
                                        <div class="row"><div class="col-sm-6"><label>Diperiksa (Foreman)</label><input type="text" id="qa_foreman_k" class="form-control" placeholder="Nama pemeriksa"></div><div class="col-sm-6"><label>Disetujui (Supervisor)</label><input type="text" id="qa_supervisor_k" class="form-control" placeholder="Nama penyetuju"></div></div>
                                    </div>

                                    <!-- FORM L (KALIBRASI PRESSURE GAUGE) -->
                                    <div id="wrapper_form_l" style="display:none;">
                                        <h5 class="text-primary" style="font-weight:bold; margin-bottom:15px;">
                                            <i class="fa fa-tachometer"></i> Kalibrasi Pressure Gauge (Form L)
                                        </h5>

                                        <div class="row" style="margin-bottom:15px;">
                                            <div class="col-md-6">
                                                <label>No. Laporan <span class="text-danger">*</span></label>
                                                <input type="text" id="qa_no_laporan_l" class="form-control" placeholder="Contoh: 06/Lap/Kalibrasi/IX/2026">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Tanggal Kalibrasi <span class="text-danger">*</span></label>
                                                <input type="date" id="qa_tanggal_l" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>

                                        <div class="row" style="margin-bottom:15px;">
                                            <div class="col-md-6">
                                                <label>No. Alat Standar</label>
                                                <input type="text" id="qa_l_std_no" class="form-control" readonly style="background:#eee;">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Satuan Kalibrasi</label>
                                                <input type="text" class="form-control" value="MPa" readonly style="background:#eee;">
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered table-entry" id="tabel_form_l">
                                                <thead>
                                                    <tr>
                                                        <th width="16%">UJI / SKALA</th>
                                                        <th width="28%">APPLIED VALUE<br><small>(MPa)</small></th>
                                                        <th width="28%">INDICATED VALUE<br><small>(MPa)</small></th>
                                                        <th width="28%">CORRECTION<br><small>(MPa)</small></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbody_form_l"></tbody>
                                            </table>
                                        </div>

                                        <p class="help-block">
                                            Uji/Skala dan Applied Value berasal dari registrasi.
                                            Petugas hanya mengisi Indicated Value.
                                            Correction = Applied Value - Indicated Value.
                                        </p>

                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label>Diperiksa (Foreman)</label>
                                                <input type="text" id="qa_foreman_l" class="form-control" placeholder="Nama pemeriksa">
                                            </div>
                                            <div class="col-sm-6">
                                                <label>Disetujui (Supervisor)</label>
                                                <input type="text" id="qa_supervisor_l" class="form-control" placeholder="Nama penyetuju">
                                            </div>
                                        </div>
                                    </div>
                                    <!-- FORM M (KALIBRASI PROFILE PROJECTOR) -->
                                    <div id="wrapper_form_m" style="display:none;">
                                        <h5 class="text-primary" style="font-weight:bold; margin-bottom:15px;">
                                            <i class="fa fa-crosshairs"></i> Kalibrasi Profile Projector (Form M)
                                        </h5>

                                        <div class="row" style="margin-bottom:15px;">
                                            <div class="col-md-6">
                                                <label>No. Laporan <span class="text-danger">*</span></label>
                                                <input type="text" id="qa_no_laporan_m" class="form-control" placeholder="Contoh: 01/Lap/Kalibrasi/VII/2026">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Tanggal Kalibrasi <span class="text-danger">*</span></label>
                                                <input type="date" id="qa_tanggal_m" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>

                                        <div class="row" style="margin-bottom:15px;">
                                            <div class="col-md-6">
                                                <label>Standar Uji Sudut</label>
                                                <input type="text" id="qa_m_standar_sudut" class="form-control" readonly style="background:#eee;">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Toleransi Sudut (')</label>
                                                <input type="text" id="qa_m_tol_sudut" class="form-control" readonly style="background:#eee;">
                                            </div>
                                        </div>

                                        <h5 style="font-weight:bold;">AXIS ACCURACY</h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-entry">
                                                <thead>
                                                    <tr>
                                                        <th rowspan="2" width="9%">STD<br>(mm)</th>
                                                        <th colspan="4">X AXIS</th>
                                                        <th colspan="4">Y AXIS</th>
                                                    </tr>
                                                    <tr>
                                                        <th>Left<br>Hasil</th><th>Left<br>Koreksi</th>
                                                        <th>Right<br>Hasil</th><th>Right<br>Koreksi</th>
                                                        <th>Front<br>Hasil</th><th>Front<br>Koreksi</th>
                                                        <th>Back<br>Hasil</th><th>Back<br>Koreksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbody_form_m_axis"></tbody>
                                            </table>
                                        </div>

                                        <h5 style="font-weight:bold; margin-top:20px;">ANGLE ACCURACY</h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-entry">
                                                <thead>
                                                    <tr>
                                                        <th width="12%">No</th>
                                                        <th width="20%">STD (°)</th>
                                                        <th width="15%">Hasil °</th>
                                                        <th width="15%">Hasil '</th>
                                                        <th width="15%">Hasil "</th>
                                                        <th width="23%">Koreksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbody_form_m_angle"></tbody>
                                            </table>
                                        </div>

                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label>Diperiksa (Foreman)</label>
                                                <input type="text" id="qa_foreman_m" class="form-control" placeholder="Nama pemeriksa">
                                            </div>
                                            <div class="col-sm-6">
                                                <label>Disetujui (Supervisor)</label>
                                                <input type="text" id="qa_supervisor_m" class="form-control" placeholder="Nama penyetuju">
                                            </div>
                                        </div>


                                    <!-- FORM O (PEMERIKSAAN SNAP GAUGE) -->
                                    <div id="wrapper_form_o" style="display:none;">
                                        <h5 class="text-primary" style="font-weight:bold; margin-bottom:15px;">
                                            <i class="fa fa-object-group"></i> Pemeriksaan Snap Gauge (Form O)
                                        </h5>

                                        <div class="row" style="margin-bottom:15px;">
                                            <div class="col-md-6">
                                                <label>No. Laporan <span class="text-danger">*</span></label>
                                                <input type="text" id="qa_no_laporan_o" class="form-control" placeholder="Contoh: 12/Lap/Periksa/IV/2026">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Tanggal Periksa <span class="text-danger">*</span></label>
                                                <input type="date" id="qa_tanggal_o" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>

                                        <div class="row" style="margin-bottom:15px;">
                                            <div class="col-md-6">
                                                <label>Standar Uji / No. Alat</label>
                                                <input type="text" id="qa_o_standar_uji" class="form-control" readonly style="background:#eee;">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Traceability</label>
                                                <input type="text" id="qa_o_traceability" class="form-control" readonly style="background:#eee;">
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered table-entry" id="tabel_form_o">
                                                <thead>
                                                    <tr>
                                                        <th rowspan="4" width="4%">NO</th>
                                                        <th rowspan="4" width="11%">NO ALAT</th>
                                                        <th rowspan="4" width="10%">SPESIFIKASI</th>
                                                        <th colspan="8">HASIL PERIKSA</th>
                                                        <th rowspan="4" width="6%">KODE WARNA</th>
                                                        <th rowspan="4" width="6%">KEPUTUSAN</th>
                                                        <th rowspan="4" width="10%">TANGGAL PERIKSA BERIKUTNYA</th>
                                                        <th rowspan="4" width="11%">NO GAMBAR</th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="5">STD UKURAN</th>
                                                        <th colspan="2">STANDAR HASIL</th>
                                                    </tr>
                                                    <tr>
                                                        <th rowspan="2" width="7%">POSISI UKUR</th>
                                                        <th rowspan="2" width="7%">ALAT</th>
                                                        <th rowspan="2" width="10%">TOLERANSI</th>
                                                        <th rowspan="2" width="7%">MIN (mm)</th>
                                                        <th rowspan="2" width="7%">MAX (mm)</th>
                                                        <th rowspan="2" width="7%">MIN<br><small>(MASUK)</small></th>
                                                        <th rowspan="2" width="7%">MAX<br><small>(TIDAK MASUK)</small></th>
                                                    </tr>
                                                    <tr></tr>
                                                </thead>
                                                <tbody id="tbody_form_o"></tbody>
                                            </table>
                                        </div>

                                        <p class="help-block">
                                            Nilai <b>No Alat, Spesifikasi, STD Ukuran, Toleransi, Min/Max, dan No Gambar</b> berasal dari registrasi.
                                            Petugas QA hanya memilih hasil <b>OK / NG</b> pada kolom <b>Standar Hasil</b>.
                                            Keputusan otomatis <b>OK</b> jika semua hasil tiap item bernilai OK.
                                        </p>

                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label>Diperiksa (Foreman)</label>
                                                <input type="text" id="qa_foreman_o" class="form-control" placeholder="Nama pemeriksa">
                                            </div>
                                            <div class="col-sm-6">
                                                <label>Disetujui (Supervisor)</label>
                                                <input type="text" id="qa_supervisor_o" class="form-control" placeholder="Nama penyetuju">
                                            </div>
                                        </div>
                                    </div>                                    </div>


                                    <!-- FORM P (PEMERIKSAAN SK GAUGE) -->
                                    <div id="wrapper_form_p" style="display:none;">
                                        <h5 class="text-primary" style="font-weight:bold; margin-bottom:15px;">
                                            <i class="fa fa-cogs"></i> Pemeriksaan SK Gauge (Form P)
                                        </h5>

                                        <div class="row" style="margin-bottom:15px;">
                                            <div class="col-md-6">
                                                <label>No. Laporan <span class="text-danger">*</span></label>
                                                <input type="text" id="qa_no_laporan_p" class="form-control" placeholder="Contoh: 39/Lap/Periksa/VI/2026">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Tanggal Periksa <span class="text-danger">*</span></label>
                                                <input type="date" id="qa_tanggal_p" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered table-entry" id="tabel_form_p">
                                                <thead>
                                                    <tr>
                                                        <th rowspan="2" width="4%">NO</th>
                                                        <th rowspan="2" width="10%">NO ALAT</th>
                                                        <th rowspan="2" width="12%">TYPE DAN UKURAN</th>
                                                        <th colspan="2">ALAT YANG DIPAKAI</th>
                                                        <th colspan="4">HASIL PERIKSA</th>
                                                        <th rowspan="2" width="6%">KEP</th>
                                                        <th rowspan="2" width="10%">TANGGAL PERIKSA BERIKUTNYA</th>
                                                        <th rowspan="2" width="9%">NO GAMBAR</th>
                                                    </tr>
                                                    <tr>
                                                        <th width="10%">NAMA ALAT</th>
                                                        <th width="9%">NO ALAT</th>
                                                        <th width="12%">STD UKURAN</th>
                                                        <th width="12%">RANGE</th>
                                                        <th width="8%">AKTUAL 1</th>
                                                        <th width="8%">AKTUAL 2</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbody_form_p"></tbody>
                                            </table>
                                        </div>

                                        <p class="help-block">
                                            Petugas QA hanya mengisi <b>Aktual 1</b> dan <b>Aktual 2</b> sesuai kebutuhan titik ukur. Keputusan item otomatis <b>OK</b> jika seluruh aktual masih berada di dalam range MIN–MAX.
                                        </p>

                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label>Diperiksa (Foreman)</label>
                                                <input type="text" id="qa_foreman_p" class="form-control" placeholder="Nama pemeriksa">
                                            </div>
                                            <div class="col-sm-6">
                                                <label>Disetujui (Supervisor)</label>
                                                <input type="text" id="qa_supervisor_p" class="form-control" placeholder="Nama penyetuju">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- FORM Q (SERATION / PLUG GAUGE) -->
                                    <div id="wrapper_form_q" style="display:none;">
                                        <h5 class="text-primary" style="font-weight:bold; margin-bottom:15px;">
                                            <i class="fa fa-wrench"></i> <span id="qa_q_title">Pemeriksaan Gauge (Form Q)</span>
                                        </h5>

                                        <div class="row" style="margin-bottom:15px;">
                                            <div class="col-md-6">
                                                <label>No. Laporan <span class="text-danger">*</span></label>
                                                <input type="text" id="qa_no_laporan_q" class="form-control" placeholder="Contoh: 01/Lap/Periksa/VII/2026">
                                            </div>
                                            <div class="col-md-6">
                                                <label>Tanggal Periksa <span class="text-danger">*</span></label>
                                                <input type="date" id="qa_tanggal_q" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>

                                        <div id="qa_q_seration_box" style="display:none;">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-entry">
                                                    <thead>
                                                        <tr>
                                                            <th rowspan="2" width="7%">POSISI</th>
                                                            <th colspan="2">ALAT STANDAR YANG DIPAKAI</th>
                                                            <th rowspan="2" width="22%">STANDAR UKURAN</th>
                                                            <th colspan="3">HASIL UKUR (CROSS)</th>
                                                        </tr>
                                                        <tr>
                                                            <th width="17%">SPESIFIKASI</th><th width="14%">NO. ALAT</th>
                                                            <th width="10%">I</th><th width="10%">II</th><th width="10%">III</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tbody_form_q_seration"></tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div id="qa_q_plug_box" style="display:none;">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-entry">
                                                    <thead>
                                                        <tr>
                                                            <th rowspan="2" width="8%">POSISI UKUR</th>
                                                            <th rowspan="2" width="16%">ALAT STANDAR</th>
                                                            <th rowspan="2" width="22%">STD UKURAN</th>
                                                            <th colspan="2">DEPAN</th>
                                                            <th colspan="2">BELAKANG</th>
                                                        </tr>
                                                        <tr><th>1</th><th>2</th><th>1</th><th>2</th></tr>
                                                    </thead>
                                                    <tbody id="tbody_form_q_plug"></tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="row" style="margin-top:10px; margin-bottom:15px;">
                                            <div class="col-md-3"><label>Kode Warna</label><input type="text" id="qa_q_kode" class="form-control" readonly style="background:#eee;font-weight:bold;"></div>
                                            <div class="col-md-3"><label>Keputusan</label><input type="text" id="qa_q_keputusan" class="form-control" readonly style="background:#eee;font-weight:bold;"></div>
                                            <div class="col-md-3"><label>Tanggal Periksa Berikutnya</label><input type="text" id="qa_q_next" class="form-control" readonly style="background:#eee;"></div>
                                            <div class="col-md-3" id="qa_q_gambar_group"><label>No. Gambar</label><input type="text" id="qa_q_no_gambar" class="form-control" readonly style="background:#eee;"></div>
                                        </div>

                                        <div class="row">
                                            <div class="col-sm-6"><label>Diperiksa (Foreman)</label><input type="text" id="qa_foreman_q" class="form-control" placeholder="Nama pemeriksa"></div>
                                            <div class="col-sm-6"><label>Disetujui (Supervisor)</label><input type="text" id="qa_supervisor_q" class="form-control" placeholder="Nama penyetuju"></div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="row" style="margin-top: 20px;">
                                <div class="col-md-3 div-ruangan">
                                    <div class="form-group" style="margin-left:0; margin-right:0;">
                                        <label>Suhu Ruangan</label>
                                        <input type="text" id="qa_suhu" class="form-control auto-simbol" data-satuan="°C" placeholder="Cth: 21 atau Ambient">
                                    </div>
                                </div>
                                <div class="col-md-3 div-ruangan">
                                    <div class="form-group" style="margin-left:0; margin-right:0;">
                                        <label>Kelembapan</label>
                                        <input type="text" id="qa_kelembapan" class="form-control auto-simbol" data-satuan="%" placeholder="Cth: 55 atau Ambient">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group" style="margin-left:0; margin-right:0;">
                                        <label>Ketidakpastian (U)</label>
                                        <div class="input-group">
                                            <input type="text" id="qa_ketidakpastian" class="form-control" placeholder="0.05">
                                            <span class="input-group-addon qa_satuan_label"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group" style="margin-left:0; margin-right:0;">
                                        <label>Confidence Lvl</label>
                                        <input type="text" id="qa_confidence" class="form-control auto-simbol" data-satuan="%" value="95 %">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group" style="margin-left:0; margin-right:0;">
                                        <label>Keterangan Tambahan</label>
                                        <textarea id="qa_keterangan" class="form-control" rows="2" placeholder="Catatan opsional..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row" style="margin-top: 15px;">
                                <div class="col-md-6">
                                    <div class="form-group" style="margin-left:0; margin-right:0;">
                                        <label>Petugas QA</label>
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                            <input type="text" class="form-control" id="qa_petugas" value="<?php echo $nama_petugas; ?>" disabled style="background-color: #eee;">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group" style="margin-left:0; margin-right:0;">
                                        <label>Keputusan (Status Final) <span class="text-danger">*</span></label>
                                        <select id="qa_status_final" class="form-control" style="font-weight:bold; font-size:16px;">
                                            <option value="">-- Pilih Status --</option>
                                            <option value="Good">OK</option>
                                            <option value="Bad">BURUK</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" id="qa_hasil_json">
                            
                            <div class="form-group" style="margin-top: 25px; border-top:1px solid #ddd; padding-top:15px;">
                                <div class="col-sm-12 text-right">
                                    <button type="button" id="btn-batal-qa" class="btn btn-default" style="padding: 8px 20px; margin-right: 10px; font-weight:bold;">Tutup</button>
                                    <button type="button" id="btn-simpan-qa" class="btn btn-primary" style="padding: 8px 30px; font-weight:bold;">Submit Hasil QA</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- 2. FORM REGISTRASI ALAT                    -->
                    <!-- ========================================== -->
                    <div id="form-registrasi" class="hidden-form-section">
                        <div id="section-pilih-format" class="text-center" style="margin-bottom: 20px; padding: 25px; border: 2px dashed #ff9800; background-color: #fffaf3; border-radius: 10px;">
                            <h4 style="color: #e65100; font-weight: bold;"><i class="fa fa-warning"></i> Alat Belum Terdaftar</h4>
                            <p style="font-size: 15px; margin-bottom: 20px;">Silakan pilih jenis format kalibrasi untuk memulai registrasi alat baru.</p>
                            <div class="row">
                                <div class="col-md-4 col-md-offset-4">
                                    <select id="reg_pilih_format" class="form-control input-lg" style="border: 2px solid #ff9800; font-weight: bold; text-align: center; font-size:16px;">
                                        <option value="">-- Pilih Format Kalibrasi --</option>
                                        <option value="A">FORM A - Pemeriksaan Gauge</option>
                                        <option value="B">FORM B - Pemeriksaan Air Driver</option>
                                        <option value="C">FORM C - Pemeriksaan Temperatur</option>
                                        <option value="D">FORM D - Pemeriksaan Multimeter</option>
                                        <option value="H">FORM H - Pemeriksaan Thread Plug Gauge</option>
                                        <option value="I">FORM I - Pemeriksaan Gauge</option>
                                        <option value="J">FORM J - Verifikasi Radius Gauge</option>
                                        <option value="K">FORM K - Pemeriksaan Voltage Ultrasonic</option>
                                        <option value="L">FORM L - Kalibrasi Pressure Gauge</option>
                                        <option value="M">FORM M - Kalibrasi Profile Projector</option>
                                        <option value="O">FORM O - Pemeriksaan Snap Gauge</option>
                                        <option value="P">FORM P - Pemeriksaan SK Gauge</option>
                                        <option value="Q">FORM Q - Pemeriksaan Seration / Plug Gauge</option>
                                        <option value="E">FORM E - Thermometer / T1 & T2</option>
                                        <option value="F">FORM F - Timbangan / 3 Section</option>
                                        <option value="G">FORM G - Air Torque / 2 Kolom Hasil</option>
                                        <option value="N">FORM N - Toleransi Dinamis</option>
                                        <option value="R">FORM R - Range Standar & 3 Titik Hasil</option>
                                        <option value="KUSTOM">Format Kustom (Dinamis)</option>
                                    </select>
                                    <button type="button" id="btn-lanjut-format" class="btn btn-warning btn-block" style="margin-top:15px; font-weight:bold; font-size: 16px; height: 50px;">
                                        Lanjut Registrasi <i class="fa fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div id="section-form-registrasi" style="display:none;">
                            <div class="row" style="padding: 0 30px;">
                                <!-- KOLOM KIRI -->
                                <div class="col-md-6" style="padding-right: 25px;">
                                    <div class="form-group">
                                        <label><span id="label-reg-no-alat">No. Alat</span> <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text-uppercase-input" id="reg_nomor_alat" readonly style="background-color: #eee;">
                                    </div>
                                    <div class="form-group">
                                        <label>Nama Alat <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="reg_nama_alat" placeholder="Contoh: Dial Caliper gage">
                                    </div>
                                    <div class="form-group">
                                        <label>Seksi Pemilik <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <select class="form-control" id="reg_seksi">
                                                <option value="">-- Pilih Seksi --</option>
                                                <?php 
                                                mysqli_data_seek($q_seksi, 0);
                                                while($r_seksi = mysqli_fetch_assoc($q_seksi)) { 
                                                    echo '<option value="'.$r_seksi['nama_seksi'].'">'.$r_seksi['nama_seksi'].'</option>';
                                                } 
                                                ?>
                                                <?php if($is_admin) { ?><option value="tambah">+ Tambah Seksi Baru...</option><?php } ?>
                                            </select>
                                            <?php if($is_admin) { ?>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-danger" type="button" id="btn_delete_reg_seksi" title="Hapus Seksi Terpilih"><i class="fa fa-trash"></i></button>
                                                </span>
                                            <?php } ?>
                                        </div>
                                        <?php if($is_admin) { ?>
                                            <div class="input-group" id="group_seksi_custom" style="display: none; margin-top: 10px;">
                                                <input type="text" class="form-control text-uppercase-input" id="reg_seksi_custom" placeholder="Ketik Seksi Baru">
                                                <span class="input-group-btn"><button class="btn btn-simpan-inline" type="button" id="btn_save_seksi"><i class="fa fa-save"></i> Simpan</button></span>
                                            </div>
                                        <?php } ?>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Periode Kalibrasi <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="reg_periode" placeholder="180">
                                                    <span class="input-group-addon">Hari</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Tgl Input Manual</label>
                                                <input type="date" class="form-control" id="reg_tgl_manual" title="Gunakan untuk backdate">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Jadwal Kalibrasi Pertama <small class="text-muted">(otomatis)</small></label>
                                                <input type="text" class="form-control" id="reg_jadwal_preview" readonly style="background:#eee; font-weight:bold;" placeholder="Isi Periode Kalibrasi dahulu">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Upload foto umum: tampil untuk FORM A/B/C/D/Kustom -->
                                    <div class="form-group" id="group-reg-foto-alat" style="margin-top:15px;">
                                        <label><i class="fa fa-upload"></i> Upload Foto Alat</label>
                                        <input type="file"
                                               class="form-control"
                                               id="reg_foto_alat"
                                               accept="image/jpeg, image/png, image/jpg">
                                    </div>
                                    
                                    <div class="form-group reg-general-only" id="group-reg-kls">
                                        <label>Kelas (KLS) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <select class="form-control" id="reg_kls">
                                                <option value="">-- Pilih Kelas --</option>
                                                <?php 
                                                mysqli_data_seek($q_kls, 0);
                                                while($r_kls = mysqli_fetch_assoc($q_kls)) { echo '<option value="'.$r_kls['nama_kelas'].'">'.$r_kls['nama_kelas'].'</option>'; } 
                                                ?>
                                                <?php if($is_admin) { ?><option value="tambah">+ Tambah Kelas Baru...</option><?php } ?>
                                            </select>
                                            <?php if($is_admin) { ?>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-danger" type="button" id="btn_delete_reg_kls" title="Hapus Kelas Terpilih"><i class="fa fa-trash"></i></button>
                                                </span>
                                            <?php } ?>
                                        </div>
                                        <?php if($is_admin) { ?>
                                            <div class="input-group" id="group_kls_custom" style="display: none; margin-top: 10px;">
                                                <input type="text" class="form-control text-uppercase-input" id="reg_kls_custom" placeholder="Ketik Kelas Baru">
                                                <span class="input-group-btn"><button class="btn btn-simpan-inline" type="button" id="btn_save_kls"><i class="fa fa-save"></i> Simpan</button></span>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    
                                    <div class="form-group reg-general-only" id="group-reg-lokasi">
                                        <label>Lokasi</label>
                                        <input type="text" class="form-control" id="reg_lokasi" placeholder="Contoh: Lab GA">
                                    </div>
                                </div>

                                <!-- KOLOM KANAN UMUM -->
                                <div id="reg-kolom-kanan-umum" class="col-md-6 reg-general-only" style="padding-left: 25px;">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><span id="label-reg-spesifikasi">Spesifikasi</span> <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="reg_spesifikasi" placeholder="Cth: 5 ~ 15 mm">
                                            </div>
                                        </div>
                                        <div class="col-md-6" id="group-reg-resolusi">
                                            <div class="form-group">
                                                <label>Resolusi</label>
                                                <input type="text" class="form-control" id="reg_resolusi" placeholder="Cth: 0.01 mm">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6" id="group_reg_toleransi_global">
                                            <div class="form-group">
                                                <label>Toleransi (±) <span class="text-danger">*</span> <small class="text-muted" id="label-info-tol"></small></label>
                                                <input type="number" step="any" class="form-control" id="reg_toleransi" placeholder="Cth: 0.06">
                                            </div>
                                        </div>
                                        <div class="col-md-6" id="group-reg-satuan">
                                            <div class="form-group">
                                                <label>Satuan (Unit) <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="reg_satuan" placeholder="Cth: mm / kgf.cm">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group" id="group-reg-metode">
                                        <label>Metode Kalibrasi</label>
                                        <input type="text" class="form-control" id="reg_metode" placeholder="Cth: STIS-SOPF-731010">
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6" id="group-reg-standar-uji">
                                            <div class="form-group">

                                                <!-- FORMAT UMUM -->
                                                <div id="standar-uji-umum">
                                                    <label>Standar Uji</label>
                                                    <input type="text"
                                                        class="form-control"
                                                        id="reg_standar_uji"
                                                        placeholder="Cth: Torsi Meter">
                                                </div>

                                                <!-- KHUSUS FORM B -->
                                                <div id="standar-uji-form-b" style="display:none;">
                                                    <label>
                                                        Alat Standar Uji
                                                        <span class="text-danger">*</span>
                                                    </label>

                                                    <input type="text"
                                                        class="form-control"
                                                        id="reg_b_standar_nama"
                                                        placeholder="Nama Alat Standar">

                                                    <input type="text"
                                                        class="form-control text-uppercase-input"
                                                        id="reg_b_standar_no"
                                                        placeholder="No. Alat Standar"
                                                        style="margin-top:8px;">
                                                </div>

                                            </div>
                                        </div>

                                        <div class="col-md-6" id="group-reg-traceability">
                                            <div class="form-group">
                                                <label>Traceability</label>
                                                <input type="text" class="form-control" id="reg_trace" placeholder="Cth: Torsi Standar">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- KHUSUS FORM L -->
                                    <div class="form-group" id="group-reg-l-std-no" style="display:none;">
                                        <label>No. Alat Standar <span class="text-danger">*</span></label>
                                        <input type="text"
                                               class="form-control text-uppercase-input"
                                               id="reg_l_std_no"
                                               placeholder="Contoh: 11.9 7350013.1">
                                    </div>

                                    <div class="form-group" id="group-reg-type">
                                        <label>Type</label>
                                        <input type="text" class="form-control text-uppercase-input" id="reg_type" placeholder="Cth: TBI">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- REGISTRASI KHUSUS FORM A -->
                            <div id="reg-form-a-fields" style="display:none; padding:0 30px; margin-top:10px;">
                                <div class="form-a-card">
                                    <div class="form-a-card-title"><i class="fa fa-file-text-o"></i> DATA REGISTRASI GAUGE - FORM A</div>
                                    <div class="form-a-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Spesifikasi <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="reg_a_spesifikasi" placeholder="Contoh: M 24 x P1.5-6e">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Metode Kalibrasi <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="reg_a_metode" placeholder="Contoh: STIS-SOPF-731015">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Traceability</label>
                                                    <input type="text" class="form-control" id="reg_a_traceability" placeholder="Contoh: Gauge Block Kl 0">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-a-card form-a-go" style="margin-top:15px;">
                                            <div class="form-a-card-title">GO</div>
                                            <div class="form-a-body">
                                                <div class="row">
                                                    <div class="col-md-6"><div class="form-group"><label>No. Alat Standar GO <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_a_go_std_no" placeholder="Contoh: 02.6 A19.."></div></div>
                                                    <div class="col-md-6"><div class="form-group"><label>Spesifikasi Alat Standar GO <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_a_go_std_spek" placeholder="Contoh: 0~25 (0.001)"></div></div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-3"><div class="form-group"><label>STD OUT Ø</label><input type="number" step="any" class="form-control" id="reg_a_go_std_out"></div></div>
                                                    <div class="col-md-3"><div class="form-group"><label>Toleransi OUT Ø</label><input type="text" class="form-control" id="reg_a_go_tol_out" placeholder="±0.011"></div></div>
                                                    <div class="col-md-6"><div class="form-group"><label>Range OUT Ø</label><input type="text" class="form-control" id="reg_a_go_range_out" placeholder="Contoh: 23.778 ~ 23.800"></div></div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-3"><div class="form-group"><label>STD PITCH Ø</label><input type="number" step="any" class="form-control" id="reg_a_go_std_pitch"></div></div>
                                                    <div class="col-md-3"><div class="form-group"><label>Toleransi PITCH Ø</label><input type="text" class="form-control" id="reg_a_go_tol_pitch" placeholder="±0.005"></div></div>
                                                    <div class="col-md-6"><div class="form-group"><label>Range PITCH Ø</label><input type="text" class="form-control" id="reg_a_go_range_pitch" placeholder="Contoh: 22.810 ~ 22.820"></div></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-a-card form-a-notgo" style="margin-top:15px;">
                                            <div class="form-a-card-title">NOT GO</div>
                                            <div class="form-a-body">
                                                <div class="row">
                                                    <div class="col-md-6"><div class="form-group"><label>No. Alat Standar NOT GO <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_a_notgo_std_no" placeholder="Contoh: 99.4 A10011.1"></div></div>
                                                    <div class="col-md-6"><div class="form-group"><label>Spesifikasi Alat Standar NOT GO <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_a_notgo_std_spek" placeholder="Contoh: ø 1.3855"></div></div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-3"><div class="form-group"><label>STD OUT Ø</label><input type="number" step="any" class="form-control" id="reg_a_notgo_std_out"></div></div>
                                                    <div class="col-md-3"><div class="form-group"><label>Toleransi OUT Ø</label><input type="text" class="form-control" id="reg_a_notgo_tol_out" placeholder="±0.011"></div></div>
                                                    <div class="col-md-6"><div class="form-group"><label>Range OUT Ø</label><input type="text" class="form-control" id="reg_a_notgo_range_out" placeholder="Contoh: 23.778 ~ 23.800"></div></div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-3"><div class="form-group"><label>STD PITCH Ø</label><input type="number" step="any" class="form-control" id="reg_a_notgo_std_pitch"></div></div>
                                                    <div class="col-md-3"><div class="form-group"><label>Toleransi PITCH Ø</label><input type="text" class="form-control" id="reg_a_notgo_tol_pitch" placeholder="±0.005"></div></div>
                                                    <div class="col-md-6"><div class="form-group"><label>Range PITCH Ø</label><input type="text" class="form-control" id="reg_a_notgo_range_pitch" placeholder="Contoh: 22.810 ~ 22.820"></div></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- REGISTRASI KHUSUS FORM M / PROFILE PROJECTOR -->
                            <div id="reg-form-m-fields" style="display:none; padding:0 30px; margin-top:10px;">
                                <div class="panel panel-default">
                                    <div class="panel-heading" style="font-weight:bold;">
                                        <i class="fa fa-crosshairs"></i> DATA STANDAR PROFILE PROJECTOR - FORM M
                                    </div>
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Standar Uji Sudut <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="reg_m_standar_sudut" placeholder="Contoh: SIKU STANDAR">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Standar Toleransi Sudut (') <span class="text-danger">*</span></label>
                                                    <input type="number" step="any" class="form-control" id="reg_m_tol_sudut" placeholder="Contoh: 0.1 ( = 6 detik )">
                                                </div>
                                            </div>
                                        </div>

                                        <h5 style="font-weight:bold; margin-top:15px;">AXIS ACCURACY</h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th width="8%" class="text-center">No</th>
                                                        <th width="32%">STD (mm)</th>
                                                        <th width="45%">Pengukuran</th>
                                                        <th width="15%" class="text-center">
                                                            <button type="button" class="btn btn-sm btn-primary" id="btn_reg_m_add_axis"><i class="fa fa-plus"></i></button>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody id="reg_m_axis_body"></tbody>
                                            </table>
                                        </div>

                                        <h5 style="font-weight:bold; margin-top:20px;">ANGLE ACCURACY</h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th width="10%" class="text-center">No</th>
                                                        <th width="75%">STD Sudut (°)</th>
                                                        <th width="15%" class="text-center">
                                                            <button type="button" class="btn btn-sm btn-primary" id="btn_reg_m_add_angle"><i class="fa fa-plus"></i></button>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody id="reg_m_angle_body"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- REGISTRASI KHUSUS FORM O / SNAP GAUGE -->
                            <div id="reg-form-o-fields" style="display:none; padding:0 30px; margin-top:10px;">
                                <div class="panel panel-default">
                                    <div class="panel-heading" style="font-weight:bold;">
                                        <i class="fa fa-object-group"></i> DATA SNAP GAUGE - FORM O
                                    </div>
                                    <div class="panel-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th width="4%" class="text-center">No</th>
                                                        <th width="12%">No. Alat</th>
                                                        <th width="8%">Spesifikasi</th>
                                                        <th width="7%">Tol +</th>
                                                        <th width="7%">Tol -</th>
                                                        <th width="7%">GO Std</th>
                                                        <th width="7%">GO Tol Dekat 0</th>
                                                        <th width="7%">GO Tol Jauh 0</th>
                                                        <th width="7%">NOT GO Std</th>
                                                        <th width="7%">NOT GO Tol ±</th>
                                                        <th width="12%">No Gambar</th>
                                                        <th width="6%" class="text-center">
                                                            <button type="button" class="btn btn-sm btn-primary" id="btn_reg_o_add"><i class="fa fa-plus"></i></button>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody id="reg_o_body"></tbody>
                                            </table>
                                        </div>
                                        <p class="help-block" style="margin-bottom:0;">Contoh: Spesifikasi 30, Tol +0.1, Tol -0.3, GO Std 30.1, GO Tol -0.017 & -0.050, NOT GO Std 29.7, NOT GO Tol ±0.017.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- REGISTRASI KHUSUS FORM P / SK GAUGE -->
                            <div id="reg-form-p-fields" style="display:none; padding:0 30px; margin-top:10px;">
                                <div class="panel panel-default">
                                    <div class="panel-heading" style="font-weight:bold; display:flex; justify-content:space-between; align-items:center;">
                                        <span><i class="fa fa-cogs"></i> DATA SK GAUGE - FORM P</span>
                                        <button type="button" class="btn btn-sm btn-primary" id="btn_reg_p_add_item"><i class="fa fa-plus"></i> Tambah Alat</button>
                                    </div>
                                    <div class="panel-body">
                                        <p class="text-muted" style="font-size:12px;">
                                            Satu Form P dapat berisi beberapa SK Gauge. Setiap alat dapat mempunyai beberapa titik pemeriksaan dan alat standar yang berbeda.
                                        </p>
                                        <div id="reg_p_items"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- REGISTRASI KHUSUS FORM Q -->
                            <div id="reg-form-q-fields" style="display:none; padding:0 30px; margin-top:10px;">
                                <div class="panel panel-default">
                                    <div class="panel-heading" style="font-weight:bold;">
                                        <i class="fa fa-wrench"></i> DATA FORM Q - SERATION / PLUG GAUGE
                                    </div>
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Jenis Gauge <span class="text-danger">*</span></label>
                                                    <select id="reg_q_subtype" class="form-control">
                                                        <option value="SERATION">Seration Plug Gauge</option>
                                                        <option value="PLUG">Plug Gauge</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="reg_q_seration">
                                            <h5 style="font-weight:bold;">STD Ø OUT</h5>
                                            <div class="row">
                                                <div class="col-md-3"><div class="form-group"><label>Spesifikasi Alat Standar *</label><input type="text" id="reg_q_out_std_spek" class="form-control" placeholder="Contoh: 0 ~ 25 mm"></div></div>
                                                <div class="col-md-3"><div class="form-group"><label>No. Alat Standar *</label><input type="text" id="reg_q_out_std_no" class="form-control text-uppercase-input" placeholder="Contoh: B731900.1"></div></div>
                                                <div class="col-md-2"><div class="form-group"><label>STD OUT *</label><input type="number" step="any" id="reg_q_out_std" class="form-control" placeholder="10.000"></div></div>
                                                <div class="col-md-2"><div class="form-group"><label>Tol + *</label><input type="number" step="any" id="reg_q_out_plus" class="form-control" placeholder="0.05"></div></div>
                                                <div class="col-md-2"><div class="form-group"><label>Tol - *</label><input type="number" step="any" id="reg_q_out_minus" class="form-control" placeholder="0"></div></div>
                                            </div>

                                            <h5 style="font-weight:bold; margin-top:15px;">STD Ø PITCH</h5>
                                            <div class="row">
                                                <div class="col-md-3"><div class="form-group"><label>Spesifikasi Alat Standar *</label><input type="text" id="reg_q_pitch_std_spek" class="form-control" placeholder="Contoh: 1.3013 mm"></div></div>
                                                <div class="col-md-3"><div class="form-group"><label>No. Alat Standar *</label><input type="text" id="reg_q_pitch_std_no" class="form-control text-uppercase-input" placeholder="Contoh: 7 210015"></div></div>
                                                <div class="col-md-2"><div class="form-group"><label>STD PITCH *</label><input type="number" step="any" id="reg_q_pitch_std" class="form-control" placeholder="9.500"></div></div>
                                                <div class="col-md-2"><div class="form-group"><label>Tol + *</label><input type="number" step="any" id="reg_q_pitch_plus" class="form-control" placeholder="0.03"></div></div>
                                                <div class="col-md-2"><div class="form-group"><label>Tol - *</label><input type="number" step="any" id="reg_q_pitch_minus" class="form-control" placeholder="0.01"></div></div>
                                            </div>
                                        </div>

                                        <div id="reg_q_plug" style="display:none;">
                                            <div class="row">
                                                <div class="col-md-4"><div class="form-group"><label>Spesifikasi Alat Standar *</label><input type="text" id="reg_q_plug_std_spek" class="form-control" placeholder="Contoh: 5 ~ 50 mm (0.001)"></div></div>
                                                <div class="col-md-4"><div class="form-group"><label>No. Alat Standar *</label><input type="text" id="reg_q_plug_std_no" class="form-control text-uppercase-input" placeholder="Contoh: 19.8 G419003.5"></div></div>
                                                <div class="col-md-4"><div class="form-group"><label>No. Gambar *</label><input type="text" id="reg_q_plug_gambar" class="form-control" placeholder="Contoh: S1273080 / PG-580"></div></div>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead><tr><th width="20%">Posisi Ukur</th><th width="25%">STD</th><th width="25%">Tol +</th><th width="25%">Tol -</th></tr></thead>
                                                    <tbody>
                                                        <tr><td style="font-weight:bold;">GO</td><td><input type="number" step="any" id="reg_q_go_std" class="form-control" placeholder="42.4"></td><td><input type="number" step="any" id="reg_q_go_plus" class="form-control" placeholder="0.050"></td><td><input type="number" step="any" id="reg_q_go_minus" class="form-control" placeholder="0"></td></tr>
                                                        <tr><td style="font-weight:bold;">NOT GO</td><td><input type="number" step="any" id="reg_q_not_std" class="form-control" placeholder="42.6"></td><td><input type="number" step="any" id="reg_q_not_plus" class="form-control" placeholder="0.017"></td><td><input type="number" step="any" id="reg_q_not_minus" class="form-control" placeholder="-0.017"></td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PARAMETER TAMBAHAN FORMAT F (TIMBANGAN) -->
                            <div id="group_format_f_extra" style="display:none; background:#e8f4f8; padding:15px 30px; margin: 20px 30px 15px 30px; border-radius:8px; border:1px dashed #1E3F66;">
                                <h5 style="margin-top:0; font-weight:bold;"><i class="fa fa-info-circle"></i> Parameter Tambahan Timbangan (Format F)</h5>
                                <div class="row">
                                    <div class="col-md-6"><label>Standar Repeatability (Section I) <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_f_std_sec1" placeholder="Cth: 100"></div>
                                    <div class="col-md-6"><label>Standar Center Loading (Section III) <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_f_std_sec3" placeholder="Cth: 100"></div>
                                </div>
                            </div>

                            <!-- TABEL TAMBAH STANDAR DINAMIS -->
                            <div class="row" id="wrapper_standar_reg" style="padding: 0 30px; margin-top:10px;">
                                <div class="col-md-12" style="border-top: 2px dashed #ddd; padding-top: 15px;">
                                    <div class="pull-right">
                                        <button type="button" class="btn btn-sm btn-info" id="btn_toggle_bagian"><i class="fa fa-columns"></i> Tambah Kolom 'Bagian'</button>
                                    </div>
                                    <label style="font-size:16px;"><i class="fa fa-list"></i> Titik Standar Kalibrasi <span class="text-danger">*</span></label>
                                    <p class="text-muted" style="font-size:12px;">Tambahkan nilai titik acuan standar (Cth: 5, 6, 7, dst).</p>
                                    
                                    <table class="table table-bordered" id="tabel_reg_standar">
                                        <thead style="background-color: #f8f9fa;">
                                            <tr>
                                                <th width="5%" class="text-center">No</th>
                                                <th width="25%" class="col-bagian" style="display:none;">Bagian <small class="text-muted">(Opsional)</small></th>
                                                <th width="30%">Nilai Standar Uji</th>
                                                <th width="25%" class="col-toleransi" style="display:none;">Toleransi (±)</th>
                                                <th width="15%" class="text-center">
                                                    <button type="button" class="btn btn-sm btn-primary" id="btn_add_standar" title="Tambah Standar"><i class="fa fa-plus"></i></button>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="body_reg_standar"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="row" style="margin-top: 25px; border-top: 1px solid #ddd; padding-top: 15px;">
                                <div class="col-md-4 col-md-offset-2">
                                    <button type="button" id="btn-batal-reg" class="btn btn-default btn-block" style="height: 50px; font-weight:bold;">Batal</button>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" id="btn-simpan-alat" class="btn btn-success btn-block" style="height: 50px; font-weight:bold; font-size:16px;">
                                        Simpan & Daftarkan Alat <i class="fa fa-save"></i>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="../../js/jquery.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script src="../../js/bootstrap-dialog.js"></script>
    <script>
        $(document).ready(function() {
            $('.text-uppercase-input').on('input', function() { $(this).val($(this).val().toUpperCase()); });
            let pakaiBagian = false;

            // SISTEM AUTO SIMBOL (Derajat, Persen) - otomatis nambahin satuan begitu user selesai ngetik angka
            $(document).on('blur', '.auto-simbol', function() {
                var val = $(this).val().trim();
                var sym = $(this).data('satuan');
                if(val !== '' && !val.includes(sym)) {
                    $(this).val(val + ' ' + sym);
                }
            });

            if($('#reg_kls_custom').length) {
                $('#reg_kls').change(function() {
                    if($(this).val() === 'tambah') { $('#group_kls_custom').slideDown().find('input').focus(); } 
                    else { $('#group_kls_custom').slideUp().find('input').val(''); }
                });
                $('#reg_seksi').change(function() {
                    if($(this).val() === 'tambah') { $('#group_seksi_custom').slideDown().find('input').focus(); } 
                    else { $('#group_seksi_custom').slideUp().find('input').val(''); }
                });
            }

            function hapusMaster(jenis, elemenId, namaAksi) {
                var valTerpilih = $('#' + elemenId).val();
                if(!valTerpilih || valTerpilih === 'tambah') { BootstrapDialog.alert('Silakan pilih data yang mau dihapus di kolom tersebut!'); return; }
                var sebutan = (jenis === 'seksi') ? 'seksi' : 'kelas';
                
                BootstrapDialog.confirm({
                    title: 'Konfirmasi Hapus ' + (jenis === 'seksi' ? 'Seksi' : 'Kelas'),
                    message: 'Yakin menghapus ' + sebutan + ' <b>\'' + valTerpilih + '\'</b>?',
                    type: BootstrapDialog.TYPE_DANGER, btnCancelLabel: 'Batal', btnOKLabel: 'Hapus', btnOKClass: 'btn-danger',
                    callback: function(result) {
                        if(result) {
                            var postData = {};
                            if(jenis === 'seksi') postData.nama_seksi = valTerpilih; else postData.nama_kelas = valTerpilih;
                            $.ajax({
                                url: 'proses_qa.php?action=' + namaAksi, type: 'POST', dataType: 'json', data: postData,
                                success: function(res) {
                                    if(res.status === 'success') {
                                        if(jenis === 'seksi') { $('#reg_seksi option[value="'+valTerpilih+'"]').remove(); } 
                                        else { $('#reg_kls option[value="'+valTerpilih+'"]').remove(); }
                                        BootstrapDialog.alert('Berhasil dihapus dari Database!');
                                    } else { BootstrapDialog.alert('Gagal: ' + res.message); }
                                }
                            });
                        }
                    }
                });
            }
            $('#btn_delete_reg_seksi').click(function() { hapusMaster('seksi', 'reg_seksi', 'hapus_master_seksi'); });
            $('#btn_delete_reg_kls').click(function() { hapusMaster('kelas', 'reg_kls', 'hapus_master_kls'); });

            $('#btn_save_kls').click(function() {
                var newKls = $('#reg_kls_custom').val().trim().toUpperCase();
                if(newKls === '') return;
                $.ajax({
                    url: 'proses_qa.php?action=simpan_master_kls', type: 'POST', dataType: 'json', data: { nama_kelas: newKls },
                    success: function(res) {
                        if(res.status === 'success') {
                            $('<option value="'+newKls+'">'+newKls+'</option>').insertBefore('#reg_kls option[value="tambah"]');
                            $('#reg_kls').val(newKls); $('#group_kls_custom').slideUp().find('input').val('');
                        } else { BootstrapDialog.alert(res.message); }
                    }
                });
            });

            $('#btn_save_seksi').click(function() {
                var newSeksi = $('#reg_seksi_custom').val().trim().toUpperCase();
                if(newSeksi === '') return;
                $.ajax({
                    url: 'proses_qa.php?action=simpan_master_seksi', type: 'POST', dataType: 'json', data: { nama_seksi: newSeksi },
                    success: function(res) {
                        if(res.status === 'success') {
                            $('<option value="'+newSeksi+'">'+newSeksi+'</option>').insertBefore('#reg_seksi option[value="tambah"]');
                            $('#reg_seksi').val(newSeksi); $('#group_seksi_custom').slideUp().find('input').val('');
                        } else { BootstrapDialog.alert(res.message); }
                    }
                });
            });

            function formatTanggal(tglStr) {
                if(!tglStr) return "-";
                var p = tglStr.split('-');
                if(p.length !== 3) return tglStr;
                return p[2] + '-' + p[1] + '-' + p[0]; 
            }

            function faValue(id) {
                var v = $('#' + id).val();
                return (v === undefined || v === null || String(v).trim() === '') ? '///' : String(v).trim();
            }

            function resetFormA() {
                $('#form-form-a input, #form-form-a textarea').not('#fa_format, #fa_petugas').val('');
                $('#fa_kep').val('');
                $('#fa_format').val('FORM A');
                $('#fa_petugas').val('<?php echo htmlspecialchars($nama_petugas, ENT_QUOTES, 'UTF-8'); ?>');
                $('#fa_tanggal_periksa').val('<?php echo date('Y-m-d'); ?>');
            }

            function bukaFormA(alat) {
                resetFormA();
                $('#fa_no_alat').val(alat.no_part || '');
                $('#fa_nama_alat').val(alat.nama_part || '');
                $('#fa_spesifikasi').val(alat.spesifikasi || alat.ukuran || '');
                $('#fa_seksi').val(alat.seksi_pemilik || '');
                $('#fa_jadwal').val(formatTanggal(alat.jadwal_kalibrasi));
                $('#fa_metode').val(alat.metode_uji || '');
                $('#fa_traceability').val(alat.traceability || '');

                try {
                    var dataFA = JSON.parse(alat.titik_standar_json || '{}');
                    if (Array.isArray(dataFA)) { dataFA = dataFA.length ? dataFA[0] : {}; }
                    var faReg = dataFA.form_a || dataFA;
                    var stdFA = faReg.alat_standar || {};
                    var goFA = faReg.go || {};
                    var ngFA = faReg.not_go || {};
                    var goStdFA = goFA.alat_standar || stdFA.go || {};
                    var ngStdFA = ngFA.alat_standar || stdFA.not_go || {};

                    $('#fa_go_std_no_alat').val(goStdFA.no_alat || stdFA.no_alat || '');
                    $('#fa_go_std_spesifikasi').val(goStdFA.spesifikasi || stdFA.spesifikasi || '');
                    $('#fa_notgo_std_no_alat').val(ngStdFA.no_alat || stdFA.no_alat || '');
                    $('#fa_notgo_std_spesifikasi').val(ngStdFA.spesifikasi || stdFA.spesifikasi || '');
                    $('#fa_traceability').val(stdFA.traceability || faReg.traceability || alat.traceability || '');

                    $('#fa_go_std_out').val(goFA.std_out || '');
                    $('#fa_go_tol_out').val(goFA.tol_out || '');
                    $('#fa_go_range_out').val(goFA.range_out || '');
                    $('#fa_go_std_pitch').val(goFA.std_pitch || '');
                    $('#fa_go_tol_pitch').val(goFA.tol_pitch || '');
                    $('#fa_go_range_pitch').val(goFA.range_pitch || '');

                    $('#fa_notgo_std_out').val(ngFA.std_out || '');
                    $('#fa_notgo_tol_out').val(ngFA.tol_out || '');
                    $('#fa_notgo_range_out').val(ngFA.range_out || '');
                    $('#fa_notgo_std_pitch').val(ngFA.std_pitch || '');
                    $('#fa_notgo_tol_pitch').val(ngFA.tol_pitch || '');
                    $('#fa_notgo_range_pitch').val(ngFA.range_pitch || '');
                } catch (e) {
                    console.warn('Data Form A tidak dapat dibaca:', e);
                }

                if (!$('#fa_go_std_spesifikasi').val() && alat.resolusi) {
                    $('#fa_go_std_spesifikasi').val(alat.resolusi);
                }
                if (!$('#fa_notgo_std_spesifikasi').val() && alat.resolusi) {
                    $('#fa_notgo_std_spesifikasi').val(alat.resolusi);
                }

                $('#nomor_alat').prop('disabled', true);
                $('#form-update-kalibrasi').hide();
                $('#form-form-a').slideDown();
                setTimeout(function() { $('#fa_no_laporan').focus(); }, 300);
            }

            function collectFormAResult() {
                return {
                    format: 'A',
                    alat_standar: {
                        no_alat: faValue('fa_go_std_no_alat') || faValue('fa_notgo_std_no_alat'),
                        spesifikasi: faValue('fa_go_std_spesifikasi') || faValue('fa_notgo_std_spesifikasi'),
                        traceability: faValue('fa_traceability'),
                        go: { no_alat: faValue('fa_go_std_no_alat'), spesifikasi: faValue('fa_go_std_spesifikasi') },
                        not_go: { no_alat: faValue('fa_notgo_std_no_alat'), spesifikasi: faValue('fa_notgo_std_spesifikasi') }
                    },
                    go: {
                        alat_standar: { no_alat: faValue('fa_go_std_no_alat'), spesifikasi: faValue('fa_go_std_spesifikasi') },
                        std_out: faValue('fa_go_std_out'),
                        tol_out: faValue('fa_go_tol_out'),
                        range_out: faValue('fa_go_range_out'),
                        std_pitch: faValue('fa_go_std_pitch'),
                        tol_pitch: faValue('fa_go_tol_pitch'),
                        range_pitch: faValue('fa_go_range_pitch'),
                        out: { dep: faValue('fa_go_out_dep'), ten: faValue('fa_go_out_ten'), bel: faValue('fa_go_out_bel') },
                        pitch: { dep: faValue('fa_go_pitch_dep'), ten: faValue('fa_go_pitch_ten'), bel: faValue('fa_go_pitch_bel') }
                    },
                    not_go: {
                        alat_standar: { no_alat: faValue('fa_notgo_std_no_alat'), spesifikasi: faValue('fa_notgo_std_spesifikasi') },
                        std_out: faValue('fa_notgo_std_out'),
                        tol_out: faValue('fa_notgo_tol_out'),
                        range_out: faValue('fa_notgo_range_out'),
                        std_pitch: faValue('fa_notgo_std_pitch'),
                        tol_pitch: faValue('fa_notgo_tol_pitch'),
                        range_pitch: faValue('fa_notgo_range_pitch'),
                        out: { dep: faValue('fa_notgo_out_dep'), ten: faValue('fa_notgo_out_ten'), bel: faValue('fa_notgo_out_bel') },
                        pitch: { dep: faValue('fa_notgo_pitch_dep'), ten: faValue('fa_notgo_pitch_ten'), bel: faValue('fa_notgo_pitch_bel') }
                    },
                    kode_varian: faValue('fa_kode_varian'),
                    warna: faValue('fa_warna'),
                    kep: faValue('fa_kep'),
                    periksa_berikut: faValue('fa_periksa_berikut'),
                    keterangan: faValue('fa_keterangan')
                };
            }

            // ================= LOGIKA CEK ALAT =================
            $('#btn-cek').click(function() {
                var noAlat = $('#nomor_alat').val().trim().toUpperCase();
                if(noAlat === '') { $('#group-input').addClass('has-error'); $('#error-msg').slideDown(200); $('#nomor_alat').focus(); return; } 
                $('#group-input').removeClass('has-error'); $('#error-msg').slideUp(200);
                
                var btn = $(this); var textAsli = btn.html();
                btn.html('<i class="fa fa-spinner fa-spin"></i> Mengecek DB...'); btn.prop('disabled', true);

                $.ajax({
                    url: 'proses_qa.php?action=cek_alat', type: 'POST', dataType: 'json', data: { no_part: noAlat },
                    success: function(res) {
                        btn.html(textAsli); btn.prop('disabled', false);
                        $('#form-update-kalibrasi, #form-form-a, #form-registrasi').slideUp();

                        if (res.status === 'error') {
                            BootstrapDialog.alert({ title: 'Informasi', type: BootstrapDialog.TYPE_WARNING, message: res.message });
                            $('#nomor_alat').prop('disabled', false).focus();
                            return; 
                        }

                        if(res.found) {
                            var alat = res.data;
                            window.qaCurrentAlat = alat;
                            if (alat.foto_alat) $('#preview_gambar_alat').attr('src', '../../images/alat/' + alat.foto_alat);
                            else $('#preview_gambar_alat').attr('src', 'https://via.placeholder.com/300x200?text=Tidak+Ada+Gambar');
                            
                            // Auto-isi dari data registrasi, TAPI tetap bisa diubah petugas saat update kalibrasi
                            $('#qa_nomor_alat').val(alat.no_part);
                            $('#qa_nama_alat').val(alat.nama_part).prop('disabled', false);
                            $('#qa_metode').val(alat.metode_uji || "-").prop('disabled', false);
                            $('#qa_standar_uji').val(alat.standar_uji || "-").prop('disabled', false);
                            $('#qa_trace').val(alat.traceability || "-").prop('disabled', false);
                            $('#qa_resolusi_value').val(alat.resolusi || "-");
                            
                            $('.qa_satuan_label').text(alat.satuan || '-'); 
                            $('.qa_satuan_kurung').html(alat.satuan ? `( ${alat.satuan} )` : '');

                            var formatLap = String(alat.format_laporan || '').toUpperCase();
                            var isFormatA = (formatLap === 'A' || formatLap === 'FORM A');

                            if (!isFormatA) {
                                try {
                                    var deteksiFA = JSON.parse(alat.titik_standar_json || '{}');
                                    if (Array.isArray(deteksiFA) && deteksiFA.length > 0) {
                                        var fa0 = deteksiFA[0] || {};
                                        if (String(fa0.format || '').toUpperCase() === 'A' || fa0.alat_standar || fa0.go || fa0.not_go) { isFormatA = true; }
                                    } else if (deteksiFA && (String(deteksiFA.format || '').toUpperCase() === 'A' || deteksiFA.form_a || deteksiFA.go || deteksiFA.not_go)) {
                                        isFormatA = true;
                                    }
                                } catch (e) {}
                            }

                            if (isFormatA) {
                                bukaFormA(alat);
                                return;
                            }

                            $('#form-form-a').hide();
                            $('#wrapper_form_m').hide();
                            $('#wrapper_form_o').hide();
                            $('#wrapper_form_p').hide();
                            $('#wrapper_form_q').hide();
                            var isFormatB = (formatLap === 'B' || formatLap === 'FORM B');
                            var isFormatC = (formatLap === 'C' || formatLap === 'FORM C');
                            var isFormatD = (formatLap === 'D' || formatLap === 'FORM D');
                            var isFormatH = (formatLap === 'H' || formatLap === 'FORM H');
                            var isFormatI = (formatLap === 'I' || formatLap === 'FORM I');
                            var isFormatJ = (formatLap === 'J' || formatLap === 'FORM J');
                            var isFormatK = (formatLap === 'K' || formatLap === 'FORM K');
                            var isFormatL = (formatLap === 'L' || formatLap === 'FORM L');
                            var isFormatM = (formatLap === 'M' || formatLap === 'FORM M');
                            var isFormatO = (formatLap === 'O' || formatLap === 'FORM O');
                            var isFormatP = (formatLap === 'P' || formatLap === 'FORM P');
                            var isFormatQ = (formatLap === 'Q' || formatLap === 'FORM Q');
                            $('#label-qa-no-alat').text(isFormatK ? 'Nomor Mesin' : 'No. Alat');
                            $('#label_qa_standar_uji').text(isFormatB ? 'Alat Standar Uji' : (isFormatJ ? 'Alat Verifikasi' : 'Standar Uji'));
                            var isKustom = (formatLap === 'KUSTOM');
                            var arrStandar = JSON.parse(alat.titik_standar_json || "[]");

                            // Tampilkan riwayat di tabel atas
                            var tGabungan = $('#tabel-gabungan tbody');
                            tGabungan.empty();
                            tGabungan.append('<tr><td>1</td><td><strong>'+(alat.no_part || "-")+'</strong></td><td>'+(alat.nama_part || "-")+'</td><td>'+(alat.spesifikasi || "-")+'</td><td>'+(alat.seksi_pemilik || "-")+'</td><td>'+(alat.lokasi || "-")+'</td><td>'+(alat.type || "-")+'</td><td><span class="label label-warning">'+(alat.kls || "-")+'</span></td><td style="background-color:#eaf2f8;"><span class="label label-info"><i class="fa fa-clock-o"></i> Jadwal</span></td><td style="background-color:#eaf2f8;"><strong>'+formatTanggal(alat.jadwal_kalibrasi)+'</strong></td></tr>');

                            var num = 2;
                            if(res.history && res.history.length > 0) {
                                res.history.forEach(function(row) {
                                    var st = row.status;
                                    var labelStatus = '';
                                    if(st === 'OK' || st === 'Good') { labelStatus = '<span class="label label-success">OK</span>'; } 
                                    else if(st === 'BURUK' || st === 'Bad') { labelStatus = '<span class="label label-danger">BURUK</span>'; } 
                                    else { labelStatus = '<span class="label label-default">'+(st || "-")+'</span>'; }
                                    
                                    tGabungan.append('<tr><td>'+num+'</td><td><strong>'+(alat.no_part || "-")+'</strong></td><td>'+(alat.nama_part || "-")+'</td><td>'+(alat.spesifikasi || "-")+'</td><td>'+(alat.seksi_pemilik || "-")+'</td><td>'+(alat.lokasi || "-")+'</td><td>'+(alat.type || "-")+'</td><td><span class="label label-warning">'+(alat.kls || "-")+'</span></td><td style="background-color:#f4f7fa;">'+labelStatus+'</td><td style="background-color:#f4f7fa;"><strong>'+formatTanggal(row.tanggal)+'</strong></td></tr>');
                                    num++;
                                });
                            }

                            // KONDISI JIKA FORMAT B (AIR DRIVER)
                            if (isFormatB) {
                                $('#tabel_input_kalibrasi').hide();
                                $('#wrapper_form_b').show();
                                $('#qa_status_final').prop('disabled', true);
                                $('#qa_no_laporan_b, #qa_foreman_b, #qa_supervisor_b').val('');
                                $('#qa_ketidakpastian, #qa_confidence').closest('.col-md-3').hide();
                                loadReportsB(alat.no_part);
                                $('#qa_toleransi').val('Format B (Torsi)');
                                $('#qa_toleransi_value').val('');

                                $('#btn-complete-b, #note-complete-b').hide();
                                var tbodyB = $('#tbody_form_b');
                                tbodyB.empty();

                                if (arrStandar.length > 0) {
                                    arrStandar.forEach(function(item) {
                                        tambahBarisB(item.tekanan, item.handle, item.baut, item.std_min, item.std_max, item);
                                    });
                                } else {
                                    tambahBarisB('', '', '', '', '');
                                }
                                $('#wrapper_form_c, #wrapper_form_d, #wrapper_form_h, #wrapper_form_i, #wrapper_form_j, #wrapper_form_k, #wrapper_form_l, #wrapper_form_l').hide();
                            } else if (isFormatC) {
                                // KONDISI FORMAT C / PEMERIKSAAN TEMPERATUR
                                $('#wrapper_form_b, #wrapper_form_k').hide();
                                $('#tabel_input_kalibrasi').hide();
                                $('#wrapper_form_c').show();
                                $('#qa_status_final').prop('disabled', true).val('');
                                $('#qa_ketidakpastian, #qa_confidence').closest('.col-md-3').hide();
                                $('#qa_no_laporan_c, #qa_foreman_c, #qa_supervisor_c').val('');
                                $('#qa_toleransi').val('±' + (alat.toleransi_global || '-') + ' °C');
                                $('#qa_toleransi_value').val(alat.toleransi_global || '');
                                $('.qa_satuan_label').text('°C');

                                var tbodyC = $('#tbody_form_c').empty();
                                if (arrStandar.length > 0) {
                                    arrStandar.forEach(function(item, index) {
                                        var setting = (item && typeof item === 'object') ? item.setting : item;
                                        var alatStd = (item && typeof item === 'object') ? (item.alat_std || '') : '';
                                        tambahBarisC(setting, alatStd, index + 1, alat.toleransi_global);
                                    });
                                } else {
                                    tambahBarisC('', '', 1, alat.toleransi_global);
                                }
                                $('#wrapper_form_d, #wrapper_form_h, #wrapper_form_i, #wrapper_form_j, #wrapper_form_k, #wrapper_form_l').hide();
                            } else if (isFormatD) {
                                // KONDISI FORMAT D / PEMERIKSAAN MULTIMETER
                                $('#wrapper_form_b, #wrapper_form_c, #wrapper_form_h, #wrapper_form_i, #wrapper_form_j, #wrapper_form_k, #wrapper_form_l').hide();
                                $('#tabel_input_kalibrasi').hide();
                                $('#wrapper_form_d').show();
                                $('#qa_status_final').prop('disabled', false).val('');
                                $('#qa_ketidakpastian, #qa_confidence').closest('.col-md-3').hide();
                                $('#qa_no_laporan_d, #qa_foreman_d, #qa_supervisor_d').val('');
                                $('#qa_toleransi').val(alat.toleransi_global || '-');
                                $('#qa_toleransi_value').val(alat.toleransi_global || '');
                                $('.qa_satuan_label').text('');

                                $('#tbody_form_d').empty();
                                if (arrStandar.length > 0) {
                                    arrStandar.forEach(function(item, index) {
                                        tambahBarisD(item || {}, index + 1);
                                    });
                                } else {
                                    tambahBarisD({}, 1);
                                }
                            } else if (isFormatH) {
                                // KONDISI FORMAT H / PEMERIKSAAN THREAD PLUG GAUGE
                                $('#wrapper_form_b, #wrapper_form_c, #wrapper_form_d, #wrapper_form_i, #wrapper_form_j, #wrapper_form_k, #wrapper_form_l').hide();
                                $('#tabel_input_kalibrasi').hide();
                                $('#wrapper_form_h').show();
                                $('#qa_status_final').prop('disabled', true).val('');
                                $('#qa_ketidakpastian, #qa_confidence').closest('.col-md-3').hide();
                                $('#qa_no_laporan_h, #qa_foreman_h, #qa_supervisor_h, #qa_h_kode_warna').val('');
                                $('#qa_h_putusan').val('');
                                $('#qa_toleransi').val('Form H');
                                $('#qa_toleransi_value').val('');
                                $('.qa_satuan_label').text('');

                                $('#tbody_form_h').empty();
                                if (arrStandar.length > 0) {
                                    arrStandar.forEach(function(item, index) {
                                        tambahBarisH(item || {}, index + 1);
                                    });
                                }
                                kumpulkanJSONFormH();
                            } else if (isFormatI) {
                                // KONDISI FORMAT I / PEMERIKSAAN GAUGE
                                $('#wrapper_form_b, #wrapper_form_c, #wrapper_form_d, #wrapper_form_h, #wrapper_form_j, #wrapper_form_k, #wrapper_form_l').hide();
                                $('#tabel_input_kalibrasi').hide();
                                $('#wrapper_form_i').show();
                                $('#qa_status_final').prop('disabled', true).val('');
                                $('#qa_ketidakpastian, #qa_confidence').closest('.col-md-3').hide();
                                $('#qa_no_laporan_i, #qa_foreman_i, #qa_supervisor_i, #qa_i_hasil_atas, #qa_i_hasil_bawah').val('');
                                $('#qa_i_kode_warna').val('HIJAU');
                                $('#qa_i_putusan').val('');
                                $('#qa_toleransi').val('Form I');
                                $('#qa_toleransi_value').val('');
                                $('.qa_satuan_label').text('');

                                var stdI = (arrStandar.length > 0 && typeof arrStandar[0] === 'object') ? arrStandar[0] : {};
                                $('#qa_i_std_spesifikasi').val(stdI.std_spesifikasi || '');
                                $('#qa_i_std_no').val(stdI.std_no_alat || '');
                                kumpulkanJSONFormI();
                            } else if (isFormatJ) {
                                // KONDISI FORMAT J / VERIFIKASI RADIUS GAUGE
                                $('#wrapper_form_b, #wrapper_form_c, #wrapper_form_d, #wrapper_form_h, #wrapper_form_i, #wrapper_form_k, #wrapper_form_l').hide();
                                $('#tabel_input_kalibrasi').hide();
                                $('#wrapper_form_j').show();

                                $('#qa_status_final').prop('disabled', true).val('');
                                $('#qa_ketidakpastian, #qa_confidence').closest('.col-md-3').hide();

                                $('#qa_no_laporan_j, #qa_foreman_j, #qa_supervisor_j').val('');
                                $('#qa_j_hasil').val('');

                                $('#qa_j_no_alat').val(alat.no_part || '');
                                $('#qa_j_spesifikasi').val(alat.spesifikasi || alat.ukuran || '');
                                $('#qa_j_alat_verifikasi').val(alat.standar_uji || '');
                                $('#qa_j_periode').val(alat.periode_kalibrasi || '');
                                $('#qa_j_tgl_berikut').val('');

                                $('#qa_toleransi').val('Form J');
                                $('#qa_toleransi_value').val('');
                                $('.qa_satuan_label').text('');

                                hitungTanggalBerikutJ();
                                kumpulkanJSONFormJ();
                            } else if (isFormatK) {
                                // KONDISI FORMAT K / PEMERIKSAAN VOLTAGE ULTRASONIC
                                $('#wrapper_form_b, #wrapper_form_c, #wrapper_form_d, #wrapper_form_h, #wrapper_form_i, #wrapper_form_j, #wrapper_form_l').hide();
                                $('#tabel_input_kalibrasi').hide();
                                $('#wrapper_form_k').show();
                                $('#qa_status_final').prop('disabled', true).val('');
                                $('#qa_ketidakpastian, #qa_confidence').closest('.col-md-3').hide();
                                $('#qa_no_laporan_k, #qa_foreman_k, #qa_supervisor_k').val('');
                                $('#qa_toleransi').val(alat.toleransi_global || ''); $('#qa_toleransi_value').val(alat.toleransi_global || ''); $('.qa_satuan_label').text('%');
                                $('#tbody_form_k').empty();
                                if (arrStandar.length > 0) { arrStandar.forEach(function(item,index){ tambahBarisK(item || {}, index+1); }); } else { tambahBarisK({},1); }
                                kumpulkanJSONFormK();
                            } else if (isFormatL) {
                                // KONDISI FORMAT L / KALIBRASI PRESSURE GAUGE
                                $('#wrapper_form_b, #wrapper_form_c, #wrapper_form_d, #wrapper_form_h, #wrapper_form_i, #wrapper_form_j, #wrapper_form_k, #wrapper_form_l, #wrapper_form_l').hide();
                                $('#tabel_input_kalibrasi').hide();
                                $('#wrapper_form_l').show();

                                $('#qa_status_final').prop('disabled', true).val('');
                                $('#qa_ketidakpastian, #qa_confidence').closest('.col-md-3').show();

                                $('#qa_no_laporan_l, #qa_foreman_l, #qa_supervisor_l').val('');
                                $('#qa_toleransi').val(alat.toleransi_global || '');
                                $('#qa_toleransi_value').val(alat.toleransi_global || '');
                                $('.qa_satuan_label').text('MPa');

                                $('#tbody_form_l').empty();
                                var stdNoL = '';
                                if (arrStandar.length > 0 && arrStandar[0] && typeof arrStandar[0] === 'object') {
                                    stdNoL = arrStandar[0].std_no_alat || '';
                                }
                                $('#qa_l_std_no').val(stdNoL);

                                arrStandar.forEach(function(item,index){
                                    tambahBarisL(item || {}, index + 1);
                                });

                                kumpulkanJSONFormL();
                            } else if (isFormatM) {
                                $('#wrapper_form_b, #wrapper_form_c, #wrapper_form_d, #wrapper_form_h, #wrapper_form_i, #wrapper_form_j, #wrapper_form_k, #wrapper_form_l').hide();
                                $('#tabel_input_kalibrasi').hide();
                                $('#wrapper_form_m').show();

                                $('#qa_status_final').prop('disabled', true).val('');
                                $('#qa_ketidakpastian, #qa_confidence').closest('.col-md-3').show();

                                $('#qa_no_laporan_m, #qa_foreman_m, #qa_supervisor_m').val('');
                                $('#qa_toleransi').val(alat.toleransi_global || '');
                                $('#qa_toleransi_value').val(alat.toleransi_global || '');
                                $('.qa_satuan_label').text('mm');

                                $('#tbody_form_m_axis, #tbody_form_m_angle').empty();
                                var stdSudutM = '';
                                var tolSudutM = '';

                                arrStandar.forEach(function(item) {
                                    if (!item || typeof item !== 'object') return;
                                    if (!stdSudutM && item.std_sudut) stdSudutM = item.std_sudut;
                                    if (!tolSudutM && item.tol_sudut !== undefined) tolSudutM = item.tol_sudut;

                                    if (String(item.jenis || '').toUpperCase() === 'AXIS') tambahBarisMAxis(item);
                                    else if (String(item.jenis || '').toUpperCase() === 'ANGLE') tambahBarisMAngle(item);
                                });

                                $('#qa_m_standar_sudut').val(stdSudutM);
                                $('#qa_m_tol_sudut').val(tolSudutM);
                                kumpulkanJSONFormM();
                            } else if (isFormatO) {
                                $('#wrapper_form_b, #wrapper_form_c, #wrapper_form_d, #wrapper_form_h, #wrapper_form_i, #wrapper_form_j, #wrapper_form_k, #wrapper_form_l, #wrapper_form_m').hide();
                                $('#tabel_input_kalibrasi').hide();
                                $('#wrapper_form_o').show();

                                $('#qa_status_final').prop('disabled', true).val('');
                                $('#qa_ketidakpastian, #qa_confidence').closest('.col-md-3').hide();

                                $('#qa_no_laporan_o, #qa_foreman_o, #qa_supervisor_o').val('');
                                $('#qa_o_standar_uji').val(alat.standar_uji || '');
                                $('#qa_o_traceability').val(alat.traceability || '');
                                $('#tbody_form_o').empty();
                                window.qaFormOStandar = arrStandar || [];

                                arrStandar.forEach(function(item, idx) { tambahBarisO(item, idx + 1, alat.jadwal_berikutnya || alat.jadwal_kalibrasi || ''); });
                                kumpulkanJSONFormO();
                            } else if (isFormatP) {
                                $('#wrapper_form_b, #wrapper_form_c, #wrapper_form_d, #wrapper_form_h, #wrapper_form_i, #wrapper_form_j, #wrapper_form_k, #wrapper_form_l, #wrapper_form_m, #wrapper_form_o').hide();
                                $('#tabel_input_kalibrasi').hide();
                                $('#wrapper_form_p').show();

                                $('#qa_status_final').prop('disabled', true).val('');
                                $('#qa_ketidakpastian, #qa_confidence').closest('.col-md-3').hide();
                                $('#qa_no_laporan_p, #qa_foreman_p, #qa_supervisor_p').val('');

                                window.qaFormPStandar = Array.isArray(arrStandar) ? arrStandar : [];
                                window.qaFormPPeriode = parseInt(alat.periode_kalibrasi || 0, 10) || 0;
                                $('#tbody_form_p').empty();
                                renderFormP(window.qaFormPStandar);
                                kumpulkanJSONFormP();
                            } else if (isFormatQ) {
                                $('#wrapper_form_b, #wrapper_form_c, #wrapper_form_d, #wrapper_form_h, #wrapper_form_i, #wrapper_form_j, #wrapper_form_k, #wrapper_form_l, #wrapper_form_m, #wrapper_form_o, #wrapper_form_p').hide();
                                $('#tabel_input_kalibrasi').hide();
                                $('#wrapper_form_q').show();
                                $('#qa_status_final').prop('disabled', true).val('');
                                $('#qa_ketidakpastian, #qa_confidence').closest('.col-md-3').hide();
                                $('#qa_no_laporan_q, #qa_foreman_q, #qa_supervisor_q').val('');

                                window.qaFormQStandar = (Array.isArray(arrStandar) && arrStandar.length) ? arrStandar[0] : {};
                                window.qaFormQPeriode = parseInt(alat.periode_kalibrasi || 0,10) || 0;
                                var subtypeQ = String(window.qaFormQStandar.subtype || 'SERATION').toUpperCase();
                                $('#qa_q_title').text(subtypeQ === 'PLUG' ? 'Pemeriksaan Plug Gauge (Form Q)' : 'Pemeriksaan Seration Plug Gauge (Form Q)');
                                $('#qa_q_no_gambar').val(subtypeQ === 'PLUG' ? (window.qaFormQStandar.no_gambar || '') : '');
                                $('#qa_q_gambar_group').toggle(subtypeQ === 'PLUG');
                                $('#qa_q_seration_box').toggle(subtypeQ === 'SERATION');
                                $('#qa_q_plug_box').toggle(subtypeQ === 'PLUG');
                                renderFormQ(window.qaFormQStandar);
                                kumpulkanJSONFormQ();
                            } else {
                                // KONDISI FORMAT STANDARD / KUSTOM
                                $('#wrapper_form_b, #wrapper_form_c, #wrapper_form_d, #wrapper_form_h, #wrapper_form_i, #wrapper_form_j, #wrapper_form_k, #wrapper_form_l, #wrapper_form_l, #wrapper_form_l').hide(); $('#qa_status_final').prop('disabled', false); $('#qa_ketidakpastian, #qa_confidence').closest('.col-md-3').show();
                                $('#tabel_input_kalibrasi').show();

                                var theadInput = $('#tabel_input_kalibrasi thead');
                                var pakaiBagianDb = arrStandar.some(function(item) { return item.bagian && item.bagian.trim() !== ''; });
                                
                                if (isKustom) {
                                    $('#qa_toleransi').val('Format Dinamis').prop('disabled', false);
                                    $('#qa_toleransi_value').val('');
                                    var thBagian = pakaiBagianDb ? '<th width="20%">BAGIAN</th>' : '';
                                    theadInput.html(`
                                        <tr>
                                            <th width="5%">NO</th>
                                            ${thBagian}
                                            <th width="20%">STANDAR <span class="qa_satuan_kurung">${alat.satuan ? `( ${alat.satuan} )` : ''}</span></th>
                                            <th width="15%">TOLERANSI (±)</th>
                                            <th width="20%">HASIL <span class="qa_satuan_kurung">${alat.satuan ? `( ${alat.satuan} )` : ''}</span></th>
                                            <th width="10%">KOREKSI</th>
                                            <th width="10%">STATUS</th>
                                        </tr>
                                    `);
                                } else {
                                    $('#qa_toleransi').val(alat.toleransi_global).prop('disabled', false);
                                    $('#qa_toleransi_value').val(alat.toleransi_global);
                                    theadInput.html(`
                                        <tr>
                                            <th width="5%">NO</th>
                                            <th width="25%">STANDAR <span class="qa_satuan_kurung">${alat.satuan ? `( ${alat.satuan} )` : ''}</span></th>
                                            <th width="30%">HASIL <span class="qa_satuan_kurung">${alat.satuan ? `( ${alat.satuan} )` : ''}</span></th>
                                            <th width="25%">KOREKSI <span class="qa_satuan_kurung">${alat.satuan ? `( ${alat.satuan} )` : ''}</span></th>
                                            <th width="15%">STATUS</th>
                                        </tr>
                                    `);
                                }

                                var tbInput = $('#tabel_input_kalibrasi tbody');
                                tbInput.empty();

                                arrStandar.forEach(function(item, index) {
                                    if (isKustom) {
                                        var tdBagian = pakaiBagianDb ? `<td style="font-weight:bold;">${item.bagian || '-'}</td>` : '';
                                        tbInput.append(`
                                            <tr>
                                                <td>${index + 1}</td>
                                                ${tdBagian}
                                                <td style="font-weight:bold;">${parseFloat(item.standar).toFixed(2)}</td>
                                                <td style="font-weight:bold;">±${item.toleransi}</td>
                                                <td><input type="number" step="any" class="form-control inp-hasil-kalibrasi" data-std="${item.standar}" data-tol="${item.toleransi}" required></td>
                                                <td><input type="text" class="form-control inp-koreksi" readonly></td>
                                                <td class="td-status font-weight-bold">-</td>
                                            </tr>
                                        `);
                                    } else {
                                        tbInput.append(`
                                            <tr>
                                                <td>${index + 1}</td>
                                                <td style="font-weight:bold;">${parseFloat(item).toFixed(2)}</td>
                                                <td><input type="number" step="any" class="form-control inp-hasil-kalibrasi" data-std="${item}" data-tol="${alat.toleransi_global}" required></td>
                                                <td><input type="text" class="form-control inp-koreksi" readonly></td>
                                                <td class="td-status font-weight-bold">-</td>
                                            </tr>
                                        `);
                                    }
                                });
                            }

                            $('#qa_suhu, #qa_kelembapan, #qa_ketidakpastian, #qa_keterangan, #qa_status_final, #qa_hasil_json').val('');
                            $('#qa_confidence').val('95 %');
                            $('#nomor_alat').prop('disabled', true);
                            $('#form-update-kalibrasi').slideDown();
                            setTimeout(function() { $('.inp-hasil-kalibrasi').first().focus(); }, 300);

                        } else {
                            // JIKA ALAT BELUM TERDAFTAR
                            $('#reg_nomor_alat').val(noAlat); 
                            $('#reg_nama_alat, #reg_spesifikasi, #reg_resolusi, #reg_toleransi, #reg_satuan, #reg_lokasi, #reg_type, #reg_metode, #reg_standar_uji, #reg_trace, #reg_periode, #reg_tgl_manual, #reg_b_standar_nama, #reg_b_standar_no, #reg_l_std_no, #reg_m_standar_sudut, #reg_m_tol_sudut, #reg_jadwal_preview').val('');
                $('#reg_o_body').empty(); $('#reg_p_items').empty(); $('#reg_q_subtype').val('SERATION'); $('#reg-form-q-fields input').val(''); $('#wrapper_form_c, #wrapper_form_d, #wrapper_form_h, #wrapper_form_i, #wrapper_form_j, #wrapper_form_k, #wrapper_form_l, #wrapper_form_l').hide();
                            $('#reg_seksi, #reg_kls, #reg_pilih_format').val('');
                            pakaiBagian = false;
                            $('.col-bagian').hide();
                            $('#btn_toggle_bagian').html('<i class="fa fa-columns"></i> Tambah Kolom \'Bagian\'').removeClass('btn-warning').addClass('btn-info');
                            
                            $('#reg_toleransi').prop('readonly', false).val('');
                            $('#preview_gambar_alat').attr('src', 'https://via.placeholder.com/300x200?text=Tidak+Ada+Gambar');

                            $('#section-form-registrasi').hide();
                            $('#section-pilih-format').show();
                            $('#nomor_alat').prop('disabled', true);
                            $('#form-registrasi').slideDown();
                        }
                    }, error: function() { btn.html(textAsli); btn.prop('disabled', false); BootstrapDialog.alert('Koneksi Error!'); }
                });
            });

            $('#nomor_alat').keypress(function(e) { if (e.which == 13) { $('#btn-cek').click(); } });
            $('#btn-batal-qa, #btn-batal-reg, #btn-batal-fa').click(function() { $('.hidden-form-section, #form-form-a').slideUp(); $('#nomor_alat').prop('disabled', false).val('').focus(); });

            function resetFormARegistration() {
                $('#reg_a_spesifikasi, #reg_a_metode, #reg_a_traceability, #reg_a_go_std_no, #reg_a_go_std_spek, #reg_a_go_std_out, #reg_a_go_tol_out, #reg_a_go_range_out, #reg_a_go_std_pitch, #reg_a_go_tol_pitch, #reg_a_go_range_pitch, #reg_a_notgo_std_no, #reg_a_notgo_std_spek, #reg_a_notgo_std_out, #reg_a_notgo_tol_out, #reg_a_notgo_range_out, #reg_a_notgo_std_pitch, #reg_a_notgo_tol_pitch, #reg_a_notgo_range_pitch').val('');
            }

            function toggleRegistrationByFormat(format) {
                var formatDipilih = String(format || '').trim().toUpperCase();

                // Default label spesifikasi untuk semua format.
                $('#label-reg-no-alat').text('No. Alat');
                $('#reg-form-m-fields').hide();
                $('#reg-form-o-fields').hide();
                $('#reg-form-p-fields').hide();
                $('#reg-form-q-fields').hide();
                $('#label-reg-spesifikasi').text('Spesifikasi');
                $('#reg_spesifikasi').attr('placeholder', 'Cth: 5 ~ 15 mm');
                $('#standar-uji-umum label').text('Standar Uji');
                $('#reg_standar_uji').attr('placeholder', 'Cth: Torsi Meter');
                $('#group-reg-l-std-no').hide();
                $('#reg_l_std_no').prop('required', false);

                // Default field toleransi untuk format selain D kembali berupa angka.
                if (formatDipilih !== 'D') {
                    $('#reg_toleransi').attr('type', 'number').attr('step', 'any').attr('placeholder', 'Cth: 0.06');
                }

                if (formatDipilih === 'A') {
                    // FORM A
                    $('#reg-kolom-kanan-umum, #group-reg-kls, #group-reg-lokasi, #group-reg-type').hide();
                    $('#reg-form-a-fields').show();
                    $('#tabel_reg_standar').closest('.row').hide();
                    $('#btn_toggle_bagian').closest('.pull-right').hide();
                    $('#wrapper_standar_reg').hide();

                    $('#standar-uji-umum').show();
                    $('#standar-uji-form-b').hide();
                    $('#reg_b_standar_nama, #reg_b_standar_no').prop('required', false);

                    $('#reg_metode').val($('#reg_a_metode').val());
                    $('#reg_trace').val($('#reg_a_traceability').val());
                    $('#reg_standar_uji').val($('#reg_a_go_std_spek').val());

                    $('#reg_satuan').val('');
                    $('#reg_resolusi').val('');
                    $('#reg_toleransi').val('');
                    $('#reg_lokasi').val('');
                    $('#reg_type').val('');
                    $('#reg_kls').val('');

                } else if (formatDipilih === 'B') {
                    // FORM B / AIR DRIVER
                    $('#reg-kolom-kanan-umum').show();
                    $('#reg-form-a-fields').hide();
                    $('#wrapper_standar_reg').show();
                    $('#tabel_reg_standar').closest('.row').show();

                    // Sembunyikan Kelas dan Lokasi khusus Form B.
                    $('#group-reg-kls, #group-reg-lokasi, #group-reg-type').hide();
                    $('#reg_kls, #reg_lokasi, #reg_type').val('');

                    // Tutup juga isian tambah kelas jika sebelumnya terbuka.
                    $('#group_kls_custom').hide();
                    $('#reg_kls_custom').val('');

                    // Field yang tidak digunakan pada Form B.
                    $('#group-reg-resolusi, #group_reg_toleransi_global, #group-reg-satuan')
                        .hide();

                    $('#reg_resolusi, #reg_toleransi, #reg_satuan').val('');

                    // Informasi standar yang digunakan pada laporan.
                    $('#group-reg-metode, #group-reg-standar-uji, #group-reg-traceability')
                        .show();

                    // Khusus FORM B, "Standar Uji" menjadi "Alat Standar Uji"
                    // dengan input Nama Alat Standar + No. Alat Standar.
                    $('#standar-uji-umum').hide();
                    $('#standar-uji-form-b').show();
                    $('#reg_b_standar_nama, #reg_b_standar_no').prop('required', true);

                    $('#reg_metode, #reg_standar_uji, #reg_trace')
                        .prop('required', false);

                    $('#btn_toggle_bagian').closest('.pull-right').hide();

                    $('#tabel_reg_standar thead tr').html(`
                        <th width="6%" class="text-center">No</th>
                        <th width="18%">Tekanan Udara</th>
                        <th width="18%">Posisi Handle</th>
                        <th width="18%">Pengatur Torsi</th>
                        <th colspan="2">
                            Standar Torsi Pengunci Ulir (kgf.cm)
                            <br>
                            <small>Rentang Min–Max dan tiga nilai standar</small>
                        </th>
                        <th width="10%" class="text-center">
                            <button
                                type="button"
                                class="btn btn-sm btn-primary"
                                id="btn_add_standar"
                                title="Tambah Titik Uji"
                            >
                                <i class="fa fa-plus"></i>
                            </button>
                        </th>
                    `);

                } else if (formatDipilih === 'C') {
                    // FORM C / PEMERIKSAAN TEMPERATUR
                    $('#reg-kolom-kanan-umum').show();
                    $('#reg-form-a-fields').hide();
                    $('#wrapper_standar_reg').show();
                    $('#tabel_reg_standar').closest('.row').show();

                    // Form C pada format laporan tidak memakai KLS, Lokasi, dan Type.
                    $('#group-reg-kls, #group-reg-lokasi, #group-reg-type').hide();
                    $('#reg_kls, #reg_lokasi, #reg_type').val('');

                    // Field yang dipakai Form C.
                    $('#group-reg-resolusi, #group_reg_toleransi_global, #group-reg-metode, #group-reg-standar-uji, #group-reg-traceability').show();
                    $('#group-reg-satuan').hide();
                    $('#reg_satuan').val('°C');

                    $('#standar-uji-umum').show();
                    $('#standar-uji-form-b').hide();
                    $('#reg_b_standar_nama, #reg_b_standar_no').prop('required', false);

                    $('#btn_toggle_bagian').closest('.pull-right').hide();
                    $('#label-info-tol').text('(Standar toleransi Form C)');

                    $('#tabel_reg_standar thead tr').html(`
                        <th width="10%" class="text-center">No</th>
                        <th width="37.5%">Setting (SV) °C</th>
                        <th width="37.5%">Alat STD °C</th>
                        <th width="15%" class="text-center">
                            <button type="button" class="btn btn-sm btn-primary" id="btn_add_standar" title="Tambah Titik Pemeriksaan">
                                <i class="fa fa-plus"></i>
                            </button>
                        </th>
                    `);

                } else if (formatDipilih === 'D') {
                    // FORM D / PEMERIKSAAN MULTIMETER
                    $('#reg-kolom-kanan-umum').show();
                    $('#reg-form-a-fields').hide();
                    $('#wrapper_standar_reg').show();
                    $('#tabel_reg_standar').closest('.row').show();

                    // Format laporan D tidak menampilkan KLS, Lokasi, dan Type.
                    $('#group-reg-kls, #group-reg-lokasi, #group-reg-type').hide();
                    $('#reg_kls, #reg_lokasi, #reg_type').val('');

                    // Data tetap yang dipakai pada laporan Multimeter.
                    $('#group-reg-resolusi, #group_reg_toleransi_global, #group-reg-metode, #group-reg-standar-uji, #group-reg-traceability').show();
                    $('#group-reg-satuan').hide();
                    $('#reg_satuan').val('');

                    // Toleransi Form D berupa rumus, mis. ±0.7% + 3d.
                    $('#reg_toleransi').attr('type', 'text').removeAttr('step').attr('placeholder', 'Contoh: ±0.7% + 3d');
                    $('#label-info-tol').text('(Contoh: ±0.7% + 3d)');

                    $('#standar-uji-umum').show();
                    $('#standar-uji-form-b').hide();
                    $('#reg_b_standar_nama, #reg_b_standar_no').prop('required', false);

                    $('#btn_toggle_bagian').closest('.pull-right').hide();

                    $('#tabel_reg_standar thead tr').html(`
                        <th width="7%" class="text-center">No</th>
                        <th width="14%">Unit</th>
                        <th width="14%">Mode</th>
                        <th width="23%">Setting</th>
                        <th width="27%">Standar</th>
                        <th width="15%" class="text-center">
                            <button type="button" class="btn btn-sm btn-primary" id="btn_add_standar" title="Tambah Titik Pemeriksaan">
                                <i class="fa fa-plus"></i>
                            </button>
                        </th>
                    `);

                } else if (formatDipilih === 'H') {
                    // FORM H / PEMERIKSAAN THREAD PLUG GAUGE
                    $('#reg-kolom-kanan-umum').show();
                    $('#label-reg-spesifikasi').text('Spesifikasi Alat yang Diperiksa');
                    $('#reg_spesifikasi').attr('placeholder', 'Contoh: M 6 x 1.0-6H (P)');
                    $('#reg-form-a-fields').hide();
                    $('#wrapper_standar_reg').show();
                    $('#tabel_reg_standar').closest('.row').show();

                    // Form H tidak memakai KLS, Lokasi, Type, Resolusi, toleransi global, dan satuan umum.
                    $('#group-reg-kls, #group-reg-lokasi, #group-reg-type, #group-reg-resolusi, #group_reg_toleransi_global, #group-reg-satuan').hide();
                    $('#reg_kls, #reg_lokasi, #reg_type, #reg_resolusi, #reg_toleransi, #reg_satuan').val('');

                    $('#group-reg-metode, #group-reg-standar-uji, #group-reg-traceability').show();
                    $('#standar-uji-umum').show();
                    $('#standar-uji-form-b').hide();
                    $('#reg_b_standar_nama, #reg_b_standar_no').prop('required', false);
                    $('#btn_toggle_bagian').closest('.pull-right').hide();
                    $('#label-info-tol').text('');

                    $('#tabel_reg_standar thead tr').html(`
                        <th width="6%" class="text-center">No</th>
                        <th width="15%">Posisi Ukur</th>
                        <th width="24%">Alat Standar<br><small>Spesifikasi & No. Alat</small></th>
                        <th width="22%">STD OUT Ø<br><small>Nilai / Tol / Range</small></th>
                        <th width="22%">STD PITCH Ø<br><small>Nilai / Tol / Range</small></th>
                        <th width="11%" class="text-center">
                            <button type="button" class="btn btn-sm btn-primary" id="btn_add_standar" title="Tambah Posisi Ukur"><i class="fa fa-plus"></i></button>
                        </th>
                    `);

                } else if (formatDipilih === 'I') {
                    // FORM I / PEMERIKSAAN GAUGE
                    $('#reg-kolom-kanan-umum').show();

                    // Ini adalah spesifikasi ALAT YANG DIPERIKSA,
                    // berbeda dengan spesifikasi alat standar di tabel bawah.
                    $('#label-reg-spesifikasi').text('Spesifikasi Alat yang Diperiksa');
                    $('#reg_spesifikasi').attr('placeholder', 'Contoh: M24 x 1-6e GW');
                    $('#reg-form-a-fields').hide();
                    $('#wrapper_standar_reg').show();
                    $('#tabel_reg_standar').closest('.row').show();

                    // Field yang tidak dipakai langsung pada Form I.
                    $('#group-reg-kls, #group-reg-lokasi, #group-reg-type, #group-reg-resolusi, #group_reg_toleransi_global, #group-reg-satuan').hide();
                    $('#reg_kls, #reg_lokasi, #reg_type, #reg_resolusi, #reg_toleransi, #reg_satuan').val('');

                    // Header laporan Form I memakai Metode, Alat Standar, dan Traceability.
                    $('#group-reg-metode, #group-reg-standar-uji, #group-reg-traceability').show();
                    $('#standar-uji-umum').show();
                    $('#standar-uji-form-b').hide();
                    $('#reg_b_standar_nama, #reg_b_standar_no').prop('required', false);

                    $('#btn_toggle_bagian').closest('.pull-right').hide();
                    $('#label-info-tol').text('');

                    $('#tabel_reg_standar thead tr').html(`
                        <th width="8%" class="text-center">No</th>
                        <th width="38%">Spesifikasi Alat Standar</th>
                        <th width="39%">No. Alat Standar</th>
                        <th width="15%" class="text-center"></th>
                    `);

                } else if (formatDipilih === 'J') {
                    // FORM J / VERIFIKASI RADIUS GAUGE
                    $('#reg-kolom-kanan-umum').show();
                    $('#reg-form-a-fields').hide();

                    // Form J tidak membutuhkan tabel titik standar.
                    $('#wrapper_standar_reg').hide();
                    $('#tabel_reg_standar').closest('.row').hide();
                    $('#btn_toggle_bagian').closest('.pull-right').hide();

                    $('#label-reg-spesifikasi').text('Spesifikasi Alat yang Diverifikasi');
                    $('#reg_spesifikasi').attr('placeholder', 'Contoh: 0.1” 1.0R');

                    // Hanya data yang memang muncul pada laporan Form J.
                    $('#group-reg-kls, #group-reg-lokasi, #group-reg-type, #group-reg-resolusi, #group_reg_toleransi_global, #group-reg-satuan, #group-reg-traceability').hide();
                    $('#reg_kls, #reg_lokasi, #reg_type, #reg_resolusi, #reg_toleransi, #reg_satuan, #reg_trace').val('');

                    $('#group-reg-metode, #group-reg-standar-uji').show();
                    $('#standar-uji-umum').show();
                    $('#standar-uji-form-b').hide();
                    $('#reg_b_standar_nama, #reg_b_standar_no').prop('required', false);

                    $('#standar-uji-umum label').text('Alat Verifikasi');
                    $('#reg_standar_uji').attr('placeholder', 'Contoh: R gauge Standar');

                    if ($('#reg_metode').val().trim() === '') {
                        $('#reg_metode').val('KOMPARASI');
                    }

                } else if (formatDipilih === 'K') {
                    // FORM K / PEMERIKSAAN VOLTAGE ULTRASONIC
                    $('#reg-kolom-kanan-umum').show(); $('#reg-form-a-fields').hide(); $('#wrapper_standar_reg').show(); $('#tabel_reg_standar').closest('.row').show();
                    $('#label-reg-no-alat').text('Nomor Mesin'); $('#label-reg-spesifikasi').text('Spesifikasi'); $('#reg_spesifikasi').attr('placeholder','Contoh: 0 ~ 15 Volt');
                    $('#group-reg-kls, #group-reg-lokasi, #group-reg-type, #group-reg-satuan').hide(); $('#reg_kls, #reg_lokasi, #reg_type').val(''); $('#reg_satuan').val('Volt');
                    $('#group-reg-resolusi, #group_reg_toleransi_global, #group-reg-metode, #group-reg-standar-uji, #group-reg-traceability').show();
                    $('#reg_toleransi').attr('type','number').attr('step','any').attr('placeholder','Contoh: 5'); $('#label-info-tol').text('(%)');
                    $('#standar-uji-umum').show(); $('#standar-uji-form-b').hide(); $('#reg_b_standar_nama, #reg_b_standar_no').prop('required',false); $('#btn_toggle_bagian').closest('.pull-right').hide();
                    $('#tabel_reg_standar thead tr').html(`<th width="10%" class="text-center">No</th><th width="75%">Nilai yang Ditentukan (Volt)</th><th width="15%" class="text-center"><button type="button" class="btn btn-sm btn-primary" id="btn_add_standar" title="Tambah Nilai"><i class="fa fa-plus"></i></button></th>`);
                } else if (formatDipilih === 'L') {
                    // FORM L / KALIBRASI PRESSURE GAUGE
                    $('#reg-kolom-kanan-umum').show();
                    $('#reg-form-a-fields').hide();
                    $('#wrapper_standar_reg').show();
                    $('#tabel_reg_standar').closest('.row').show();

                    $('#label-reg-no-alat').text('Nomor Alat');
                    $('#label-reg-spesifikasi').text('Spesifikasi');
                    $('#reg_spesifikasi').attr('placeholder', 'Contoh: 0~25 kgf/cm² (0~2.5 MPa)');

                    $('#group-reg-kls, #group-reg-lokasi, #group-reg-type').hide();
                    $('#reg_kls, #reg_lokasi, #reg_type').val('');

                    $('#group-reg-resolusi, #group_reg_toleransi_global, #group-reg-metode, #group-reg-standar-uji, #group-reg-traceability').show();
                    $('#group-reg-satuan').hide();
                    $('#reg_satuan').val('MPa');

                    $('#reg_toleransi').attr('type','number').attr('step','any').attr('placeholder','Contoh: 0.1');
                    $('#label-info-tol').text('(MPa)');

                    $('#standar-uji-umum').show();
                    $('#standar-uji-form-b').hide();
                    $('#standar-uji-umum label').text('Standar Uji');
                    $('#reg_standar_uji').attr('placeholder','Contoh: TEST GAUGE');

                    $('#group-reg-l-std-no').show();
                    $('#reg_l_std_no').prop('required', true);

                    $('#btn_toggle_bagian').closest('.pull-right').hide();

                    $('#tabel_reg_standar thead tr').html(`
                        <th width="10%" class="text-center">No</th>
                        <th width="25%">Uji / Skala</th>
                        <th width="50%">Applied Value (MPa)</th>
                        <th width="15%" class="text-center"><button type="button" class="btn btn-sm btn-primary" id="btn_add_standar"><i class="fa fa-plus"></i></button></th>
                    `);

                } else if (formatDipilih === 'M') {
                    $('#reg-kolom-kanan-umum').show();
                    $('#reg-form-a-fields').hide();
                    $('#reg-form-m-fields').show();
                    $('#wrapper_standar_reg').hide();
                    $('#tabel_reg_standar').closest('.row').hide();

                    $('#label-reg-no-alat').text('Nomor Alat');
                    $('#label-reg-spesifikasi').text('Spesifikasi');
                    $('#reg_spesifikasi').attr('placeholder','Contoh: X , Y');

                    $('#group-reg-kls, #group-reg-lokasi, #group-reg-type, #group-reg-satuan').hide();
                    $('#reg_kls, #reg_lokasi, #reg_type').val('');
                    $('#reg_satuan').val('mm');

                    $('#group-reg-resolusi, #group_reg_toleransi_global, #group-reg-metode, #group-reg-standar-uji, #group-reg-traceability').show();
                    $('#reg_toleransi').attr('type','number').attr('step','any').attr('placeholder','Contoh: 0.005');
                    $('#label-info-tol').text('(AXIS ± mm)');

                    $('#standar-uji-umum').show();
                    $('#standar-uji-form-b').hide();
                    $('#standar-uji-umum label').text('Standar Uji Axis');
                    $('#reg_standar_uji').attr('placeholder','Contoh: GAUGE BLOCK KL 0');

                    $('#group-reg-l-std-no').hide();
                    $('#reg_l_std_no').prop('required', false);
                    $('#btn_toggle_bagian').closest('.pull-right').hide();

                    if ($('#reg_m_axis_body tr').length === 0) resetRegMDefaults();

                } else if (formatDipilih === 'O') {
                    $('#reg-kolom-kanan-umum').show();
                    $('#reg-form-a-fields, #reg-form-m-fields').hide();
                    $('#reg-form-o-fields').show();
                    $('#wrapper_standar_reg').hide();
                    $('#tabel_reg_standar').closest('.row').hide();

                    $('#label-reg-no-alat').text('Nomor Alat / Kode Master');
                    $('#label-reg-spesifikasi').text('Spesifikasi Umum');
                    $('#reg_spesifikasi').attr('placeholder','Contoh: Snap Gauge Set');

                    $('#group-reg-kls, #group-reg-lokasi, #group-reg-type, #group-reg-resolusi, #group_reg_toleransi_global, #group-reg-satuan').hide();
                    $('#reg_kls, #reg_lokasi, #reg_type, #reg_resolusi, #reg_toleransi').val('');
                    $('#reg_satuan').val('mm');
                    $('#label-info-tol').text('');

                    $('#group-reg-metode, #group-reg-standar-uji, #group-reg-traceability').show();
                    $('#standar-uji-umum').show();
                    $('#standar-uji-form-b').hide();
                    $('#standar-uji-umum label').text('Standar Uji / No Alat');
                    $('#reg_standar_uji').attr('placeholder','Contoh: Gauge Block Kl I');

                    $('#group-reg-l-std-no').hide();
                    $('#reg_l_std_no').prop('required', false);
                    $('#btn_toggle_bagian').closest('.pull-right').hide();

                    if ($('#reg_o_body tr').length === 0) resetRegODefaults();

                } else if (formatDipilih === 'P') {
                    $('#reg-kolom-kanan-umum').show();
                    $('#reg-form-a-fields, #reg-form-m-fields, #reg-form-o-fields').hide();
                    $('#reg-form-p-fields').show();
                    $('#wrapper_standar_reg').hide();
                    $('#tabel_reg_standar').closest('.row').hide();

                    $('#label-reg-no-alat').text('Kode Master / Group');
                    $('#label-reg-spesifikasi').text('Spesifikasi Umum');
                    $('#reg_spesifikasi').attr('placeholder','Contoh: SK Gauge Set');

                    $('#group-reg-kls, #group-reg-lokasi, #group-reg-type, #group-reg-resolusi, #group_reg_toleransi_global, #group-reg-satuan').hide();
                    $('#reg_kls, #reg_lokasi, #reg_type, #reg_resolusi, #reg_toleransi').val('');
                    $('#reg_satuan').val('mm');
                    $('#label-info-tol').text('');

                    $('#group-reg-metode, #group-reg-standar-uji, #group-reg-traceability').show();
                    $('#standar-uji-umum').show();
                    $('#standar-uji-form-b').hide();
                    $('#standar-uji-umum label').text('Alat Standar Utama');
                    $('#reg_standar_uji').attr('placeholder','Contoh: Outside micrometer');

                    $('#group-reg-l-std-no').hide();
                    $('#reg_l_std_no').prop('required', false);
                    $('#btn_toggle_bagian').closest('.pull-right').hide();

                    if ($('#reg_p_items .reg-p-item').length === 0) resetRegPDefaults();
                } else if (formatDipilih === 'Q') {
                    $('#reg-kolom-kanan-umum').show();
                    $('#reg-form-a-fields, #reg-form-m-fields, #reg-form-o-fields, #reg-form-p-fields').hide();
                    $('#reg-form-q-fields').show();
                    $('#wrapper_standar_reg').hide();
                    $('#tabel_reg_standar').closest('.row').hide();

                    $('#label-reg-no-alat').text('No. Alat');
                    $('#label-reg-spesifikasi').text('Spesifikasi Alat yang Diperiksa');
                    $('#reg_spesifikasi').attr('placeholder','Contoh Seration: 10-90° TPI 16 GP / Plug: Ø42.5 ±0.1');
                    $('#group-reg-kls, #group-reg-lokasi, #group-reg-type, #group-reg-resolusi, #group_reg_toleransi_global, #group-reg-satuan').hide();
                    $('#reg_kls, #reg_lokasi, #reg_type, #reg_resolusi, #reg_toleransi').val('');
                    $('#reg_satuan').val('mm');
                    $('#label-info-tol').text('');

                    $('#group-reg-metode, #group-reg-standar-uji, #group-reg-traceability').show();
                    $('#standar-uji-umum').show();
                    $('#standar-uji-form-b').hide();
                    $('#standar-uji-umum label').text('Alat Standar');
                    $('#reg_standar_uji').attr('placeholder','Seration: Micrometer dan Wire gauge / Plug: Outside Micrometer');
                    $('#group-reg-l-std-no').hide();
                    $('#reg_l_std_no').prop('required', false);
                    $('#btn_toggle_bagian').closest('.pull-right').hide();
                    toggleRegQSubtype();
                } else {
                    // FORMAT LAIN
                    $('#group-reg-type').show();
                    $('#reg-kolom-kanan-umum, #group-reg-kls, #group-reg-lokasi')
                        .show();

                    $('#reg-form-a-fields').hide();
                    $('#wrapper_standar_reg').show();
                    $('#tabel_reg_standar').closest('.row').show();

                    $('#group-reg-resolusi, #group_reg_toleransi_global, #group-reg-satuan, #group-reg-metode, #group-reg-standar-uji, #group-reg-traceability')
                        .each(function () {
                            this.style.removeProperty('display');
                        })
                        .show();

                    $('#standar-uji-umum').show();
                    $('#standar-uji-form-b').hide();
                    $('#reg_b_standar_nama, #reg_b_standar_no').prop('required', false);

                    $('#reg_metode, #reg_standar_uji, #reg_trace')
                        .prop('required', false);

                    $('#btn_toggle_bagian').closest('.pull-right').show();

                    $('#tabel_reg_standar thead tr').html(`
                        <th width="5%" class="text-center">No</th>
                        <th width="25%" class="col-bagian" style="display:none;">
                            Bagian <small class="text-muted">(Opsional)</small>
                        </th>
                        <th width="30%">Nilai Standar Uji</th>
                        <th width="25%" class="col-toleransi" style="display:none;">
                            Toleransi (±)
                        </th>
                        <th width="15%" class="text-center">
                            <button
                                type="button"
                                class="btn btn-sm btn-primary"
                                id="btn_add_standar"
                                title="Tambah Standar"
                            >
                                <i class="fa fa-plus"></i>
                            </button>
                        </th>
                    `);
                }
            }

            $('#reg_a_spesifikasi, #reg_a_metode, #reg_a_traceability, #reg_a_go_std_spek, #reg_a_notgo_std_spek').on('input', function(){
                $('#reg_spesifikasi').val($('#reg_a_spesifikasi').val());
                $('#reg_metode').val($('#reg_a_metode').val());
                $('#reg_trace').val($('#reg_a_traceability').val());
                $('#reg_standar_uji').val($('#reg_a_go_std_spek').val() || $('#reg_a_notgo_std_spek').val());
            });

            $('#btn-lanjut-format').click(function() {
                var format = $('#reg_pilih_format').val();
                if(format === '') { BootstrapDialog.alert('Silakan pilih Format Kalibrasi terlebih dahulu!'); return; }
                
                toggleRegistrationByFormat(format);
                if(format === 'A') { resetFormARegistration(); }

                if(format === 'KUSTOM') {
                    $('#label-info-tol').text('(Otomatis copy ke tabel bawah)');
                    $('.col-toleransi').show();
                } else if (['E','F','G','N','R'].includes(format)) {
                    $('#label-info-tol').text('');
                    $('.col-toleransi').show();
                } else {
                    $('#label-info-tol').text('');
                    $('.col-toleransi').hide();
                }

                // FORMAT E/F/G/N/R (format lama) - panel tambahan & label dinamis.
                if (format === 'F') {
                    $('#group_format_f_extra').slideDown();
                } else {
                    $('#group_format_f_extra').slideUp();
                    $('#reg_f_std_sec1, #reg_f_std_sec3').val('');
                }
                if (format === 'G' || format === 'E') {
                    pakaiBagian = false; $('.col-bagian').hide(); $('#btn_toggle_bagian').closest('.pull-right').hide();
                } else if (['F','N','R'].includes(format)) {
                    $('#btn_toggle_bagian').closest('.pull-right').show();
                }

                $('#body_reg_standar').empty();
                if (format === 'C') { addRegRow(); addRegRow(); addRegRow(); }
                else if (format === 'H') { addRegRow({posisi_ukur:'GO'}); addRegRow({posisi_ukur:'PLUG GAUGE'}); }
                else if (format === 'J') { /* Form J tidak memakai tabel titik standar */ }
                else if (format === 'K') { addRegRow(); addRegRow(); addRegRow(); }
                else if (format === 'L') {
                    [
                        {arah:'NAIK', applied_value:'0'},
                        {arah:'NAIK', applied_value:'0.5'},
                        {arah:'NAIK', applied_value:'1'},
                        {arah:'NAIK', applied_value:'1.5'},
                        {arah:'NAIK', applied_value:'2'},
                        {arah:'TURUN', applied_value:'2'},
                        {arah:'TURUN', applied_value:'1.5'},
                        {arah:'TURUN', applied_value:'1'},
                        {arah:'TURUN', applied_value:'0.5'},
                        {arah:'TURUN', applied_value:'0'}
                    ].forEach(function(p){ addRegRow(p); });
                }
                else { addRegRow(); }
                $('#section-pilih-format').slideUp(); $('#section-form-registrasi').slideDown();
                setTimeout(function(){ toggleRegistrationByFormat(format); }, 50);
            });

            $('#reg_toleransi').on('input', function() {
                if($('#reg_pilih_format').val() === 'KUSTOM') { $('.inp-reg-toleransi').val($(this).val()); }
            });

            $('#btn_toggle_bagian').click(function() {
                pakaiBagian = !pakaiBagian;
                if(pakaiBagian) { $('.col-bagian').show(); $(this).html('<i class="fa fa-columns"></i> Hapus Kolom \'Bagian\'').removeClass('btn-info').addClass('btn-warning'); } 
                else { $('.col-bagian').hide(); $('.inp-reg-bagian').val(''); $(this).html('<i class="fa fa-columns"></i> Tambah Kolom \'Bagian\'').removeClass('btn-warning').addClass('btn-info'); }
            });

            $(document).off('click.formb', '#btn_add_standar').on('click.formb', '#btn_add_standar', function() { addRegRow(); });
            function updateRegMNumbers() {
                $('#reg_m_axis_body tr').each(function(i){ $(this).find('.m-axis-no').text(i + 1); });
                $('#reg_m_angle_body tr').each(function(i){ $(this).find('.m-angle-no').text(i + 1); });
            }
            function addRegMAxisRow(preset) {
                preset=preset||{};
                var mode=String(preset.mode||'XY').toUpperCase();
                var std=preset.std_mm!==undefined?preset.std_mm:'';
                $('#reg_m_axis_body').append(`
                    <tr class="reg-m-axis-row">
                        <td class="text-center m-axis-no"></td>
                        <td><input type="number" step="any" class="form-control inp-reg-m-axis-std" value="${std}"></td>
                        <td><select class="form-control inp-reg-m-axis-mode">
                            <option value="XY" ${mode==='XY'?'selected':''}>X + Y Axis</option>
                            <option value="X" ${mode==='X'?'selected':''}>X Axis saja</option>
                            <option value="Y" ${mode==='Y'?'selected':''}>Y Axis saja</option>
                        </select></td>
                        <td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-reg-m-del-axis"><i class="fa fa-trash"></i></button></td>
                    </tr>`);
                updateRegMNumbers();
            }
            function addRegMAngleRow(preset) {
                preset=preset||{};
                var std=preset.std_degree!==undefined?preset.std_degree:'';
                $('#reg_m_angle_body').append(`
                    <tr class="reg-m-angle-row">
                        <td class="text-center m-angle-no"></td>
                        <td><input type="number" step="any" class="form-control inp-reg-m-angle-std" value="${std}"></td>
                        <td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-reg-m-del-angle"><i class="fa fa-trash"></i></button></td>
                    </tr>`);
                updateRegMNumbers();
            }
            function resetRegMDefaults() {
                $('#reg_m_axis_body, #reg_m_angle_body').empty();
                [
                    {std_mm:10,mode:'XY'},{std_mm:20,mode:'XY'},{std_mm:30,mode:'XY'},
                    {std_mm:40,mode:'XY'},{std_mm:50,mode:'XY'},
                    {std_mm:75,mode:'X'},{std_mm:100,mode:'X'}
                ].forEach(addRegMAxisRow);
                [{std_degree:90},{std_degree:180},{std_degree:270},{std_degree:360}].forEach(addRegMAngleRow);
            }
            $(document).on('click','#btn_reg_m_add_axis',function(){addRegMAxisRow({});});
            $(document).on('click','#btn_reg_m_add_angle',function(){addRegMAngleRow({});});
            $(document).on('click','.btn-reg-m-del-axis',function(){$(this).closest('tr').remove();updateRegMNumbers();});
            $(document).on('click','.btn-reg-m-del-angle',function(){$(this).closest('tr').remove();updateRegMNumbers();});

            function toggleRegQSubtype(){
                var subtype = String($('#reg_q_subtype').val() || 'SERATION').toUpperCase();
                $('#reg_q_seration').toggle(subtype === 'SERATION');
                $('#reg_q_plug').toggle(subtype === 'PLUG');
                if ($('#reg_nama_alat').val().trim() === '' || /seration plug gauge|plug gauge/i.test($('#reg_nama_alat').val())) {
                    $('#reg_nama_alat').val(subtype === 'PLUG' ? 'Plug gauge' : 'Seration plug gauge');
                }
                var metodeSekarang=$('#reg_metode').val().trim();
                if (metodeSekarang==='' || metodeSekarang==='STIS-SOPF-731029' || metodeSekarang==='STIS-SOPF-731006') {
                    $('#reg_metode').val(subtype === 'PLUG' ? 'STIS-SOPF-731006' : 'STIS-SOPF-731029');
                }
            }
            $(document).on('change','#reg_q_subtype',function(){ toggleRegQSubtype(); });

            function updateRegPItems() {
                $('#reg_p_items .reg-p-item').each(function(i){
                    $(this).attr('data-item-index', i);
                    $(this).find('.reg-p-title').text('SK Gauge #' + (i + 1));
                    $(this).find('.reg-p-measure-row').each(function(j){ $(this).find('.p-measure-no').text(j + 1); });
                });
            }

            function addRegPMeasure(itemEl, preset) {
                preset = preset || {};
                var tbody = $(itemEl).find('.reg-p-measure-body');
                var actualCount = String(preset.actual_count || '2');
                tbody.append(`
                    <tr class="reg-p-measure-row">
                        <td class="text-center p-measure-no"></td>
                        <td><input type="text" class="form-control p-std-name" value="${preset.alat_nama || ''}" placeholder="Outside micrometer"></td>
                        <td><input type="text" class="form-control p-std-no" value="${preset.alat_no || ''}" placeholder="19.8 G419003.5"></td>
                        <td><input type="text" class="form-control p-std-label" value="${preset.std_label || ''}" placeholder="28 ±0.008"></td>
                        <td><input type="number" step="any" class="form-control p-min" value="${preset.min !== undefined ? preset.min : ''}" placeholder="27.992"></td>
                        <td><input type="number" step="any" class="form-control p-max" value="${preset.max !== undefined ? preset.max : ''}" placeholder="28.008"></td>
                        <td>
                            <select class="form-control p-actual-count">
                                <option value="1" ${actualCount === '1' ? 'selected' : ''}>1 Aktual</option>
                                <option value="2" ${actualCount === '2' ? 'selected' : ''}>2 Aktual</option>
                            </select>
                        </td>
                        <td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-reg-p-del-measure"><i class="fa fa-trash"></i></button></td>
                    </tr>`);
                updateRegPItems();
            }

            function addRegPItem(preset) {
                preset = preset || {};
                var id = 'reg_p_item_' + Date.now() + '_' + Math.floor(Math.random()*100000);
                $('#reg_p_items').append(`
                    <div class="panel panel-info reg-p-item" id="${id}">
                        <div class="panel-heading" style="display:flex;justify-content:space-between;align-items:center;">
                            <strong class="reg-p-title">SK Gauge</strong>
                            <button type="button" class="btn btn-xs btn-danger btn-reg-p-del-item"><i class="fa fa-trash"></i> Hapus Alat</button>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-3"><div class="form-group"><label>No. Alat *</label><input type="text" class="form-control p-item-noalat" value="${preset.no_alat || ''}" placeholder="26.6 E4111809.1"></div></div>
                                <div class="col-md-3"><div class="form-group"><label>Type *</label><input type="text" class="form-control p-item-type" value="${preset.type || ''}" placeholder="SK-867"></div></div>
                                <div class="col-md-2"><div class="form-group"><label>Ukuran *</label><input type="text" class="form-control p-item-ukuran" value="${preset.ukuran || ''}" placeholder="2+0/-0.2"></div></div>
                                <div class="col-md-2"><div class="form-group"><label>U/Part</label><input type="text" class="form-control p-item-upart" value="${preset.upart || ''}" placeholder="U/Part ~S31060"></div></div>
                                <div class="col-md-2"><div class="form-group"><label>No. Gambar *</label><input type="text" class="form-control p-item-gambar" value="${preset.no_gambar || ''}" placeholder="S1275367"></div></div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-condensed">
                                    <thead><tr>
                                        <th width="4%">No</th><th width="18%">Nama Alat Standar</th><th width="15%">No. Alat Standar</th>
                                        <th width="19%">STD Ukuran</th><th width="12%">MIN</th><th width="12%">MAX</th><th width="10%">Aktual</th>
                                        <th width="10%" class="text-center"><button type="button" class="btn btn-xs btn-primary btn-reg-p-add-measure"><i class="fa fa-plus"></i></button></th>
                                    </tr></thead>
                                    <tbody class="reg-p-measure-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>`);
                var el = $('#' + id);
                var ms = Array.isArray(preset.measurements) && preset.measurements.length ? preset.measurements : [{}];
                ms.forEach(function(m){ addRegPMeasure(el, m); });
                updateRegPItems();
            }

            function resetRegPDefaults(){ $('#reg_p_items').empty(); addRegPItem({}); }
            $(document).on('click','#btn_reg_p_add_item',function(){ addRegPItem({}); });
            $(document).on('click','.btn-reg-p-del-item',function(){ $(this).closest('.reg-p-item').remove(); if($('#reg_p_items .reg-p-item').length===0) addRegPItem({}); updateRegPItems(); });
            $(document).on('click','.btn-reg-p-add-measure',function(){ addRegPMeasure($(this).closest('.reg-p-item'), {}); });
            $(document).on('click','.btn-reg-p-del-measure',function(){ var item=$(this).closest('.reg-p-item'); $(this).closest('tr').remove(); if(item.find('.reg-p-measure-row').length===0) addRegPMeasure(item, {}); updateRegPItems(); });

            function updateRegONumbers(){ $('#reg_o_body tr').each(function(i){ $(this).find('.o-no').text(i+1); }); }
            function addRegORow(preset){
                preset = preset || {};
                $('#reg_o_body').append(`
                    <tr class="reg-o-row">
                        <td class="text-center o-no"></td>
                        <td><input type="text" class="form-control inp-reg-o-noalat" value="${preset.no_alat||''}"></td>
                        <td><input type="number" step="any" class="form-control inp-reg-o-spec" value="${preset.spesifikasi||''}"></td>
                        <td><input type="number" step="any" class="form-control inp-reg-o-plus" value="${preset.tol_plus||''}"></td>
                        <td><input type="number" step="any" class="form-control inp-reg-o-minus" value="${preset.tol_minus||''}"></td>
                        <td><input type="number" step="any" class="form-control inp-reg-o-go-std" value="${preset.go_std||''}"></td>
                        <td><input type="number" step="any" class="form-control inp-reg-o-go-near" value="${preset.go_tol_near||''}"></td>
                        <td><input type="number" step="any" class="form-control inp-reg-o-go-far" value="${preset.go_tol_far||''}"></td>
                        <td><input type="number" step="any" class="form-control inp-reg-o-not-std" value="${preset.notgo_std||''}"></td>
                        <td><input type="number" step="any" class="form-control inp-reg-o-not-tol" value="${preset.notgo_tol||''}"></td>
                        <td><input type="text" class="form-control inp-reg-o-gambar" value="${preset.no_gambar||''}"></td>
                        <td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-reg-o-del"><i class="fa fa-trash"></i></button></td>
                    </tr>`);
                updateRegONumbers();
            }
            function resetRegODefaults(){ $('#reg_o_body').empty(); addRegORow({}); }
            $(document).on('click','#btn_reg_o_add',function(){ addRegORow({}); });
            $(document).on('click','.btn-reg-o-del',function(){ $(this).closest('tr').remove(); updateRegONumbers(); if($('#reg_o_body tr').length===0) addRegORow({}); });

            function addRegRow(preset) {
                var trCount = $('#body_reg_standar tr').length + 1;
                var format = $('#reg_pilih_format').val();
                var currentTol = $('#reg_toleransi').val();

                if (format === 'B') {
                    var rowB = `
                        <tr class="reg-b-row">
                            <td class="text-center row-num" style="vertical-align:middle;">${trCount}</td>
                            <td><input type="text" class="form-control inp-reg-b-tekanan text-center" placeholder="0.4"></td>
                            <td><input type="text" class="form-control inp-reg-b-handle text-center" placeholder="3"></td>
                            <td><input type="text" class="form-control inp-reg-b-baut text-center" placeholder="M5"></td>
                            <td><label>Min</label><input type="number" step="any" class="form-control inp-reg-b-min text-center" placeholder="13"><label>Max</label><input type="number" step="any" class="form-control inp-reg-b-max text-center" placeholder="15"></td>
                            <td><input type="number" step="any" class="form-control inp-reg-b-std1 text-center" placeholder="Standar 1" aria-label="Standar 1" required>
                            <input type="number" step="any" class="form-control inp-reg-b-std2 text-center" placeholder="Standar 2" aria-label="Standar 2" required>
                            <input type="number" step="any" class="form-control inp-reg-b-std3 text-center" placeholder="Standar 3" aria-label="Standar 3" required></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-hapus-std"><i class="fa fa-trash"></i></button></td>
                        </tr>`;
                    $('#body_reg_standar').append(rowB); updateRowNumbers(); return;
                }

                if (format === 'C') {
                    var rowC = `
                        <tr class="reg-c-row">
                            <td class="text-center row-num" style="vertical-align:middle;">${trCount}</td>
                            <td><input type="number" step="any" class="form-control inp-reg-c-setting text-center" placeholder="Contoh: 185.0" required></td>
                            <td><input type="number" step="any" class="form-control inp-reg-c-alat-std text-center" placeholder="Contoh: 184.0" required></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-hapus-std"><i class="fa fa-trash"></i></button></td>
                        </tr>`;
                    $('#body_reg_standar').append(rowC); updateRowNumbers(); return;
                }

                if (format === 'H') {
                    preset = preset || {};
                    var posisiH = preset.posisi_ukur || 'GO';
                    var rowH = `
                        <tr class="reg-h-row">
                            <td class="text-center row-num" style="vertical-align:middle;">${trCount}</td>
                            <td>
                                <select class="form-control inp-reg-h-posisi">
                                    <option value="GO" ${posisiH === 'GO' ? 'selected' : ''}>GO</option>
                                    <option value="PLUG GAUGE" ${posisiH === 'PLUG GAUGE' ? 'selected' : ''}>PLUG GAUGE</option>
                                    <option value="NOT GO" ${posisiH === 'NOT GO' ? 'selected' : ''}>NOT GO</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control inp-reg-h-std-spek" placeholder="Spesifikasi standar" value="${preset.std_spesifikasi || ''}" style="margin-bottom:5px;">
                                <input type="text" class="form-control inp-reg-h-std-no text-uppercase-input" placeholder="No. alat standar" value="${preset.std_no_alat || ''}">
                            </td>
                            <td>
                                <input type="text" class="form-control inp-reg-h-out-std" placeholder="STD OUT Ø" value="${preset.std_out || ''}" style="margin-bottom:4px;">
                                <input type="text" class="form-control inp-reg-h-out-tol" placeholder="Toleransi, cth ±0.011" value="${preset.tol_out || ''}" style="margin-bottom:4px;">
                                <input type="text" class="form-control inp-reg-h-out-range" placeholder="Range, cth 6.001 ~ 6.023" value="${preset.range_out || ''}">
                            </td>
                            <td>
                                <input type="text" class="form-control inp-reg-h-pitch-std" placeholder="STD PITCH Ø" value="${preset.std_pitch || ''}" style="margin-bottom:4px;">
                                <input type="text" class="form-control inp-reg-h-pitch-tol" placeholder="Toleransi, cth ±0.005" value="${preset.tol_pitch || ''}" style="margin-bottom:4px;">
                                <input type="text" class="form-control inp-reg-h-pitch-range" placeholder="Range, cth 5.356 ~ 5.368" value="${preset.range_pitch || ''}">
                            </td>
                            <td class="text-center" style="vertical-align:middle;"><button type="button" class="btn btn-sm btn-danger btn-hapus-std"><i class="fa fa-trash"></i></button></td>
                        </tr>`;
                    $('#body_reg_standar').append(rowH); updateRowNumbers(); return;
                }

                if (format === 'I') {
                    preset = preset || {};
                    var rowI = `
                        <tr class="reg-i-row">
                            <td class="text-center row-num" style="vertical-align:middle;">${trCount}</td>
                            <td><input type="text" class="form-control inp-reg-i-std-spek" placeholder="Contoh: M24 x 1-6e GW" value="${preset.std_spesifikasi || ''}" required></td>
                            <td><input type="text" class="form-control inp-reg-i-std-no text-uppercase-input" placeholder="Contoh: 07.4.730" value="${preset.std_no_alat || ''}" required></td>
                            <td class="text-center"></td>
                        </tr>`;
                    $('#body_reg_standar').append(rowI); updateRowNumbers(); return;
                }

                if (format === 'K') {
                    preset=preset||{}; var nilaiK=(preset.nilai_ditentukan!==undefined&&preset.nilai_ditentukan!==null)?preset.nilai_ditentukan:'';
                    var rowK=`<tr class="reg-k-row"><td class="text-center row-num" style="vertical-align:middle;">${trCount}</td><td><input type="number" step="any" class="form-control inp-reg-k-nilai" placeholder="Contoh: 4.5" value="${nilaiK}" required></td><td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-hapus-standar" title="Hapus"><i class="fa fa-trash"></i></button></td></tr>`;
                    $('#body_reg_standar').append(rowK); updateRowNumbers(); return;
                }

                if (format === 'L') {
                    preset = preset || {};
                    var arahL = String(preset.arah || 'NAIK').toUpperCase();
                    var appliedL = (preset.applied_value !== undefined && preset.applied_value !== null) ? preset.applied_value : '';

                    var rowL = `
                        <tr class="reg-l-row">
                            <td class="text-center row-num" style="vertical-align:middle;">${trCount}</td>
                            <td>
                                <select class="form-control inp-reg-l-arah">
                                    <option value="NAIK" ${arahL === 'NAIK' ? 'selected' : ''}>NAIK</option>
                                    <option value="TURUN" ${arahL === 'TURUN' ? 'selected' : ''}>TURUN</option>
                                </select>
                            </td>
                            <td><input type="number" step="any" class="form-control inp-reg-l-applied text-center" placeholder="Contoh: 0.5" value="${appliedL}" required></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-hapus-std"><i class="fa fa-trash"></i></button></td>
                        </tr>`;

                    $('#body_reg_standar').append(rowL);
                    updateRowNumbers();
                    return;
                }

                if (format === 'D') {
                    preset = preset || {};
                    var unitD = preset.unit || 'V';
                    var modeD = preset.mode || 'AC';
                    var rowD = `
                        <tr class="reg-d-row">
                            <td class="text-center row-num" style="vertical-align:middle;">${trCount}</td>
                            <td>
                                <select class="form-control inp-reg-d-unit">
                                    <option value="V" ${unitD === 'V' ? 'selected' : ''}>V</option>
                                    <option value="mV" ${unitD === 'mV' ? 'selected' : ''}>mV</option>
                                    <option value="Ω" ${unitD === 'Ω' ? 'selected' : ''}>Ω</option>
                                </select>
                            </td>
                            <td>
                                <select class="form-control inp-reg-d-mode">
                                    <option value="AC" ${modeD === 'AC' ? 'selected' : ''}>AC</option>
                                    <option value="DC" ${modeD === 'DC' ? 'selected' : ''}>DC</option>
                                    <option value="-" ${modeD === '-' ? 'selected' : ''}>-</option>
                                </select>
                            </td>
                            <td><input type="number" step="any" class="form-control inp-reg-d-setting text-center" placeholder="Contoh: 220" value="${preset.setting || ''}" required></td>
                            <td><input type="number" step="any" class="form-control inp-reg-d-standar text-center" placeholder="Contoh: 218.0" value="${preset.standar || ''}" required></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-hapus-std"><i class="fa fa-trash"></i></button></td>
                        </tr>`;
                    $('#body_reg_standar').append(rowD); updateRowNumbers(); return;
                }

                var displayBagian = pakaiBagian ? '' : 'display:none;';
                var displayTol = (format === 'KUSTOM') ? '' : 'display:none;';
                var rowHtml = `
                    <tr>
                        <td class="text-center row-num" style="vertical-align:middle;">${trCount}</td>
                        <td class="col-bagian" style="${displayBagian}"><input type="text" class="form-control inp-reg-bagian" placeholder="Cth: Out Ø / Atas"></td>
                        <td><input type="number" step="any" class="form-control inp-reg-standar" required></td>
                        <td class="col-toleransi" style="${displayTol}"><input type="number" step="any" class="form-control inp-reg-toleransi" value="${currentTol}"></td>
                        <td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-hapus-std"><i class="fa fa-trash"></i></button></td>
                    </tr>`;
                $('#body_reg_standar').append(rowHtml); updateRowNumbers();
            }

            $(document).on('click', '.btn-hapus-std', function() { $(this).closest('tr').remove(); updateRowNumbers(); });
            function updateRowNumbers() { $('#body_reg_standar tr').each(function(index) { $(this).find('.row-num').text(index + 1); }); }

            // ================= LOGIKA FORM B (AIR DRIVER) =================
            $('#btn_tambah_baris_b').hide();
            $('#btn-complete-b').on('click', function() {
                kumpulkanJSONFormB();
                var btn=$(this).prop('disabled',true);
                $.ajax({url:'proses_qa.php?action=complete_form_b',type:'POST',dataType:'json',data:{no_part:$('#qa_nomor_alat').val(),standards:$('#qa_hasil_json').val()},
                    success:function(res) {
                        if(res.status!=='success') { BootstrapDialog.alert(res.message); return; }
                        $('#tbody_form_b .baris-b').each(function(i) { var tr=this; ['std1','std2','std3'].forEach(function(key) { tr.querySelector('.b-'+key).value=res.standards[i][key]; }); });
                        $('#tbody_form_b .b-std1, #tbody_form_b .b-std2, #tbody_form_b .b-std3').prop('readonly',true);
                        $('#btn-complete-b, #note-complete-b').hide();
                        $('#tbody_form_b .b-hasil').each(function() { hitungB(this); });
                    },error:function(){BootstrapDialog.alert('Standar gagal disimpan. Coba kembali.');},complete:function(){btn.prop('disabled',false);}
                });
            });
            function loadReportsB(number) {
                $('#qa_history_b').empty();
                $.getJSON('proses_qa.php', {action:'laporan_b', no_part:number}, function(res) {
                    if (!window.qaCurrentAlat || window.qaCurrentAlat.no_part !== number) return;
                    (res.reports || []).forEach(function(item) {
                        $('<button>', {type:'button', 'class':'btn btn-default btn-sm', text:'Cetak '+item.laporan.no_laporan+' ('+item.laporan.tanggal_periksa+')'})
                        .on('click', function() { sessionStorage.setItem('cetak_data',JSON.stringify(item.laporan)); window.location.href='cetak_laporan.php'; })
                        .appendTo('#qa_history_b');
                    });
                });
            }
            $(document).on('input change', '#tbody_form_b input', function() { hitungB(this); });

            function tambahBarisB(tekanan, handle, baut, min, max, item) {
                item = item || {};
                var tr = document.createElement('tr');
                tr.className = 'baris-b';
                tr.innerHTML = `
                    <td><input class="form-control b-tekanan" readonly></td>
                    <td><input class="form-control b-handle" readonly></td>
                    <td><input class="form-control b-baut" readonly></td>
                    <td><input class="form-control b-min" readonly><input class="form-control b-max" readonly></td>
                    <td class="b-tests"><input type="number" step="any" class="form-control b-std1" aria-label="Standar 1" placeholder="Standar 1" readonly>
                    <input type="number" step="any" class="form-control b-std2" aria-label="Standar 2" placeholder="Standar 2" readonly>
                    <input type="number" step="any" class="form-control b-std3" aria-label="Standar 3" placeholder="Standar 3" readonly></td>
                    <td><input type="number" step="any" class="form-control b-hasil" placeholder="Isi hasil kalibrasi" aria-label="Hasil kalibrasi"></td>
                    <td class="b-status">-</td>`;
                ['tekanan','handle','baut','min','max'].forEach(function(key,i) {
                    var value=[tekanan,handle,baut,min,max][i];
                    tr.querySelector('.b-'+key).value=value == null ? '' : value;
                });
                ['std1','std2','std3'].forEach(function(key) { tr.querySelector('.b-'+key).value=item[key] == null ? '' : item[key]; });
                if (['std1','std2','std3'].some(function(key) { return item[key] == null || String(item[key]).trim()===''; })) {
                    ['std1','std2','std3'].forEach(function(key) { tr.querySelector('.b-'+key).readOnly=false; });
                    $('#btn-complete-b, #note-complete-b').show();
                }
                document.getElementById('tbody_form_b').appendChild(tr);
            }

            window.hitungB = function(element) {
                var tr=element.closest('tr');
                var read=function(key) { return tr.querySelector('.b-'+key).value.trim(); };
                var ready=['min','max','std1','std2','std3','hasil'].every(function(key) { return read(key)!=='' && Number.isFinite(Number(read(key))); });
                var min=Number(read('min')), max=Number(read('max')), hasil=Number(read('hasil'));
                tr.dataset.status=ready && min<=max ? (hasil>=min && hasil<=max ? 'OK':'BURUK') : '';
                tr.querySelector('.b-status').textContent=tr.dataset.status==='OK' ? 'Lulus' : (tr.dataset.status==='BURUK' ? 'Tidak Lulus':'-');
                kumpulkanJSONFormB();
            };
            function kumpulkanJSONFormB() {
                var data=[];
                document.querySelectorAll('.baris-b').forEach(function(tr) {
                    var row={};
                    ['tekanan','handle','baut','std1','std2','std3','hasil'].forEach(function(key) { row[key]=tr.querySelector('.b-'+key).value; });
                    row.std_min=tr.querySelector('.b-min').value; row.std_max=tr.querySelector('.b-max').value;
                    row.status=tr.dataset.status || 'KOSONG'; data.push(row);
                });
                $('#qa_hasil_json').val(JSON.stringify(data));
                var ready=data.length>0 && data.every(function(row) { return row.status==='OK' || row.status==='BURUK'; });
                $('#qa_status_final').val(ready ? (data.some(function(row) { return row.status==='BURUK'; }) ? 'Bad':'Good') : '');
            }

            // ================= LOGIKA TABEL KUSTOM / STANDARD =================
            $(document).on('input', '.inp-hasil-kalibrasi', function() {
                var valHasil = parseFloat($(this).val());
                var valStd = parseFloat($(this).data('std'));
                var toleransi = Math.abs(parseFloat($(this).data('tol')));
                
                var tr = $(this).closest('tr');
                var inpkoreksi = tr.find('.inp-koreksi');
                var tdStatus = tr.find('.td-status');

                if (isNaN(valHasil)) {
                    inpkoreksi.val(''); tdStatus.text('-').removeClass('text-success text-danger');
                } else {
                    var koreksiRaw = valHasil - valStd;
                    var koreksi = parseFloat(koreksiRaw.toFixed(4)); 
                    inpkoreksi.val(koreksi.toFixed(3));

                    if (Math.abs(koreksi) <= toleransi) { tdStatus.text('OK').removeClass('text-danger').addClass('text-success'); } 
                    else { tdStatus.text('BURUK').removeClass('text-success').addClass('text-danger'); }
                }
                
                var resultJSON = [];
                $('.inp-hasil-kalibrasi').each(function() {
                    var val = parseFloat($(this).val());
                    if (!isNaN(val)) {
                        var std = parseFloat($(this).data('std'));
                        var tolData = parseFloat($(this).data('tol'));
                        var statusRow = $(this).closest('tr').find('.td-status').text();
                        var korek = $(this).closest('tr').find('.inp-koreksi').val();
                        var bag = $(this).closest('tr').find('td:eq(1)').text(); 
                        
                        var hasBagian = $('#tabel_input_kalibrasi thead th:contains("BAGIAN")').length > 0;
                        if(hasBagian) { resultJSON.push({ bagian: bag, standar: std, toleransi: tolData, hasil: val, koreksi: korek, status: statusRow }); } 
                        else { resultJSON.push({ standar: std, toleransi: tolData, hasil: val, koreksi: korek, status: statusRow }); }
                    }
                });
                $('#qa_hasil_json').val(JSON.stringify(resultJSON));
            });

            // ================= LOGIKA FORM C (TEMPERATUR) =================
            function tambahBarisC(setting, alatStd, nomor, toleransi) {
                var tr = document.createElement('tr');
                tr.className = 'baris-c';
                tr.dataset.toleransi = toleransi == null ? '' : toleransi;
                tr.innerHTML = `
                    <td class="text-center">${nomor}</td>
                    <td><input type="number" step="any" class="form-control c-setting text-center" readonly style="background:#eee;"></td>
                    <td><input type="number" step="any" class="form-control c-actual text-center" placeholder="Actual PV"></td>
                    <td><input type="number" step="any" class="form-control c-std text-center" readonly style="background:#eee;"></td>
                    <td><input type="text" class="form-control c-selisih text-center" readonly style="background:#eee;"></td>
                    <td class="c-status text-center" style="font-weight:bold;">-</td>`;
                tr.querySelector('.c-setting').value = setting == null ? '' : setting;
                tr.querySelector('.c-std').value = alatStd == null ? '' : alatStd;
                document.getElementById('tbody_form_c').appendChild(tr);
            }

            function kumpulkanJSONFormC() {
                var data = [];
                var semuaTerisi = true;
                var adaTidakLulus = false;
                document.querySelectorAll('#tbody_form_c .baris-c').forEach(function(tr) {
                    var setting = tr.querySelector('.c-setting').value.trim();
                    var actual = tr.querySelector('.c-actual').value.trim();
                    var std = tr.querySelector('.c-std').value.trim();
                    var tol = Number(tr.dataset.toleransi || $('#qa_toleransi_value').val() || 0);
                    var selisih = '';
                    var status = 'KOSONG';

                    if (actual !== '' && std !== '' && Number.isFinite(Number(actual)) && Number.isFinite(Number(std))) {
                        selisih = Number(actual) - Number(std);
                        status = Math.abs(selisih) <= tol ? 'OK' : 'BURUK';
                        if (status === 'BURUK') adaTidakLulus = true;
                    } else {
                        semuaTerisi = false;
                    }

                    tr.querySelector('.c-selisih').value = selisih === '' ? '' : selisih.toFixed(1);
                    tr.querySelector('.c-status').textContent = status === 'OK' ? 'Lulus' : (status === 'BURUK' ? 'Tidak Lulus' : '-');
                    tr.querySelector('.c-status').style.color = status === 'OK' ? '#198754' : (status === 'BURUK' ? '#dc3545' : '');

                    data.push({
                        setting: setting,
                        actual: actual,
                        alat_std: std,
                        selisih: selisih === '' ? '' : selisih.toFixed(1),
                        toleransi: tol,
                        status: status
                    });
                });

                $('#qa_hasil_json').val(JSON.stringify(data));
                if (semuaTerisi && data.length > 0) $('#qa_status_final').val(adaTidakLulus ? 'Bad' : 'Good');
                else $('#qa_status_final').val('');
                return data;
            }

            $(document).on('input change', '#tbody_form_c .c-actual', function() {
                kumpulkanJSONFormC();
            });

            // ================= LOGIKA FORM D (MULTIMETER) =================
            function tambahBarisD(item, nomor) {
                item = item || {};
                var tr = document.createElement('tr');
                tr.className = 'baris-d';
                tr.innerHTML = `
                    <td class="text-center">${nomor}</td>
                    <td><input type="text" class="form-control d-mode text-center" readonly style="background:#eee;"></td>
                    <td><input type="number" step="any" class="form-control d-setting text-center" readonly style="background:#eee;"></td>
                    <td><input type="number" step="any" class="form-control d-standar text-center" readonly style="background:#eee;"></td>
                    <td><input type="number" step="any" class="form-control d-hasil text-center" placeholder="Isi hasil"></td>
                    <td><input type="text" class="form-control d-koreksi text-center" readonly style="background:#eee;"></td>
                    <td><input type="text" class="form-control d-unit text-center" readonly style="background:#eee;"></td>`;
                tr.querySelector('.d-mode').value = item.mode || '-';
                tr.querySelector('.d-setting').value = item.setting == null ? '' : item.setting;
                tr.querySelector('.d-standar').value = item.standar == null ? '' : item.standar;
                tr.querySelector('.d-unit').value = item.unit || '';
                document.getElementById('tbody_form_d').appendChild(tr);
            }

            function kumpulkanJSONFormD() {
                var data = [];
                document.querySelectorAll('#tbody_form_d .baris-d').forEach(function(tr) {
                    var mode = tr.querySelector('.d-mode').value.trim();
                    var unit = tr.querySelector('.d-unit').value.trim();
                    var setting = tr.querySelector('.d-setting').value.trim();
                    var standar = tr.querySelector('.d-standar').value.trim();
                    var hasil = tr.querySelector('.d-hasil').value.trim();
                    var koreksi = '';

                    if (standar !== '' && hasil !== '' && Number.isFinite(Number(standar)) && Number.isFinite(Number(hasil))) {
                        koreksi = Number(standar) - Number(hasil);
                    }

                    tr.querySelector('.d-koreksi').value = koreksi === '' ? '' : koreksi.toFixed(1);
                    data.push({
                        unit: unit,
                        mode: mode,
                        setting: setting,
                        standar: standar,
                        hasil: hasil,
                        koreksi: koreksi === '' ? '' : koreksi.toFixed(1)
                    });
                });
                $('#qa_hasil_json').val(JSON.stringify(data));
                return data;
            }

            $(document).on('input change', '#tbody_form_d .d-hasil', function() {
                kumpulkanJSONFormD();
            });

            // ================= LOGIKA FORM Q (SERATION / PLUG GAUGE) =================
            function qFmt(v){ if(!Number.isFinite(v)) return ''; var s=v.toFixed(3).replace(/0+$/,'').replace(/\.$/,''); return s==='-0'?'0':s; }
            function qRange(std, plus, minus){
                std=parseFloat(std); plus=parseFloat(plus); minus=parseFloat(minus);
                if(!Number.isFinite(std)||!Number.isFinite(plus)||!Number.isFinite(minus)) return {min:null,max:null};
                return {min:std+Math.min(plus,minus), max:std+Math.max(plus,minus)};
            }
            function qNextDate(){
                var d=$('#qa_tanggal_q').val(), periode=parseInt(window.qaFormQPeriode||0,10)||0;
                if(!d||periode<=0) return '';
                var p=d.split('-'), dt=new Date(parseInt(p[0]),parseInt(p[1])-1,parseInt(p[2]));
                dt.setDate(dt.getDate()+periode);
                return dt.getFullYear()+'-'+String(dt.getMonth()+1).padStart(2,'0')+'-'+String(dt.getDate()).padStart(2,'0');
            }
            function renderFormQ(data){
                data=data||{};
                $('#tbody_form_q_seration, #tbody_form_q_plug').empty();
                var subtype=String(data.subtype||'SERATION').toUpperCase();
                if(subtype==='SERATION'){
                    ['out','pitch'].forEach(function(k){
                        var r=data[k]||{}, rg=qRange(r.std,r.tol_plus,r.tol_minus);
                        $('#tbody_form_q_seration').append(`
                            <tr class="qa-q-ser-row" data-kind="${k}">
                                <td style="font-weight:bold;vertical-align:middle;">Ø ${k.toUpperCase()}</td>
                                <td>${r.std_spesifikasi||''}</td><td>${r.std_no_alat||''}</td>
                                <td>${r.std||''}<br><small>${r.tol_plus||''} / ${r.tol_minus||''}</small><br><small>(${qFmt(rg.min)} ~ ${qFmt(rg.max)})</small></td>
                                <td><input type="number" step="any" class="form-control q-ser-a1"></td>
                                <td><input type="number" step="any" class="form-control q-ser-a2"></td>
                                <td><input type="number" step="any" class="form-control q-ser-a3"></td>
                            </tr>`);
                    });
                } else {
                    var stdLabel=(data.std_spesifikasi||'')+' / '+(data.std_no_alat||'');
                    ['go','notgo'].forEach(function(k){
                        var r=data[k]||{}, rg=qRange(r.std,r.tol_plus,r.tol_minus);
                        $('#tbody_form_q_plug').append(`
                            <tr class="qa-q-plug-row" data-kind="${k}">
                                <td style="font-weight:bold;vertical-align:middle;">${k==='go'?'GO':'NOT GO'}</td>
                                <td>${stdLabel}</td>
                                <td>${r.std||''}<br><small>${r.tol_plus||''} / ${r.tol_minus||''}</small><br><small>(${qFmt(rg.min)} ~ ${qFmt(rg.max)})</small></td>
                                <td><input type="number" step="any" class="form-control q-plug-f1"></td>
                                <td><input type="number" step="any" class="form-control q-plug-f2"></td>
                                <td><input type="number" step="any" class="form-control q-plug-b1"></td>
                                <td><input type="number" step="any" class="form-control q-plug-b2"></td>
                            </tr>`);
                    });
                }
            }
            function kumpulkanJSONFormQ(){
                var base=JSON.parse(JSON.stringify(window.qaFormQStandar||{}));
                var subtype=String(base.subtype||'SERATION').toUpperCase();
                var lengkap=true, semuaOK=true;
                if(subtype==='SERATION'){
                    ['out','pitch'].forEach(function(k){
                        var cfg=base[k]||{}, rg=qRange(cfg.std,cfg.tol_plus,cfg.tol_minus), tr=$('#tbody_form_q_seration .qa-q-ser-row[data-kind="'+k+'"]');
                        var vals=[tr.find('.q-ser-a1').val().trim(),tr.find('.q-ser-a2').val().trim(),tr.find('.q-ser-a3').val().trim()];
                        var ok=true;
                        vals.forEach(function(v){ if(v===''||!Number.isFinite(Number(v))){lengkap=false;ok=false;} else if(Number(v)<rg.min||Number(v)>rg.max) ok=false; });
                        cfg.min=qFmt(rg.min);cfg.max=qFmt(rg.max);cfg.actual=vals;cfg.status=ok?'OK':'BAD';base[k]=cfg;if(!ok)semuaOK=false;
                    });
                } else {
                    ['go','notgo'].forEach(function(k){
                        var cfg=base[k]||{}, rg=qRange(cfg.std,cfg.tol_plus,cfg.tol_minus), tr=$('#tbody_form_q_plug .qa-q-plug-row[data-kind="'+k+'"]');
                        var vals={front1:tr.find('.q-plug-f1').val().trim(),front2:tr.find('.q-plug-f2').val().trim(),back1:tr.find('.q-plug-b1').val().trim(),back2:tr.find('.q-plug-b2').val().trim()};
                        var filled=0, ok=true;
                        Object.keys(vals).forEach(function(n){ var v=vals[n]; if(v!==''){filled++; if(!Number.isFinite(Number(v))||Number(v)<rg.min||Number(v)>rg.max)ok=false;} });
                        if(filled===0){lengkap=false;ok=false;}
                        cfg.min=qFmt(rg.min);cfg.max=qFmt(rg.max);cfg.actual=vals;cfg.status=ok?'OK':'BAD';base[k]=cfg;if(!ok)semuaOK=false;
                    });
                }
                base.kode_warna=(lengkap?(semuaOK?'HIJAU':'MERAH'):'');
                base.keputusan=(lengkap?(semuaOK?'OK':'BAD'):'');
                base.jadwal_berikutnya=qNextDate();
                $('#qa_q_kode').val(base.kode_warna);$('#qa_q_keputusan').val(base.keputusan);$('#qa_q_next').val(base.jadwal_berikutnya);
                $('#qa_hasil_json').val(JSON.stringify([base]));
                $('#qa_status_final').val(lengkap?(semuaOK?'Good':'Bad'):'');
                return [base];
            }
            $(document).on('input change','#tbody_form_q_seration input, #tbody_form_q_plug input, #qa_tanggal_q',function(){kumpulkanJSONFormQ();});

            // ================= LOGIKA FORM P (SK GAUGE) =================
            function formatTanggalP(dateStr){
                if(!dateStr) return '';
                var p=String(dateStr).split('-');
                if(p.length!==3) return dateStr;
                return p[2]+' - '+p[1]+' - '+p[0];
            }
            function hitungTanggalBerikutP(){
                var d=$('#qa_tanggal_p').val();
                var periode=parseInt(window.qaFormPPeriode||0,10)||0;
                if(!d || periode<=0) return '';
                var dt=new Date(d+'T00:00:00');
                dt.setDate(dt.getDate()+periode);
                var y=dt.getFullYear(),m=String(dt.getMonth()+1).padStart(2,'0'),day=String(dt.getDate()).padStart(2,'0');
                return y+'-'+m+'-'+day;
            }
            function formatTanggalRegPreview(dateStr){
                if(!dateStr) return '';
                var p=String(dateStr).split('-');
                if(p.length!==3) return dateStr;
                return p[2]+' - '+p[1]+' - '+p[0];
            }
            function hitungJadwalRegistrasi(){
                var periode = parseInt($('#reg_periode').val() || '0', 10);
                if(!periode || periode <= 0){ $('#reg_jadwal_preview').val(''); return ''; }
                var tglManual = $('#reg_tgl_manual').val();
                var basis = tglManual ? new Date(tglManual + 'T00:00:00') : new Date();
                if(isNaN(basis.getTime())){ $('#reg_jadwal_preview').val(''); return ''; }
                basis.setDate(basis.getDate() + periode);
                var y = basis.getFullYear(), m = String(basis.getMonth()+1).padStart(2,'0'), d = String(basis.getDate()).padStart(2,'0');
                var hasil = y+'-'+m+'-'+d;
                $('#reg_jadwal_preview').val(formatTanggalRegPreview(hasil));
                return hasil;
            }
            $(document).on('input change', '#reg_periode, #reg_tgl_manual', hitungJadwalRegistrasi);

            function renderFormP(items){
                var tbody=$('#tbody_form_p').empty();
                items = Array.isArray(items) ? items : [];
                var next=formatTanggalP(hitungTanggalBerikutP());
                items.forEach(function(item,itemIndex){
                    var ms=Array.isArray(item.measurements)?item.measurements:[];
                    if(ms.length===0) ms=[{}];
                    ms.forEach(function(m,j){
                        var first=j===0, rowspan=ms.length;
                        var c2=String(m.actual_count||'2')==='2';
                        var typeHtml=(item.type||'') + (item.ukuran ? '<br>'+item.ukuran : '') + (item.upart ? '<br>'+item.upart : '');
                        var row=`<tr class="qa-p-row" data-item="${itemIndex}" data-measure="${j}">`;
                        if(first){
                            row += `<td rowspan="${rowspan}" class="text-center" style="vertical-align:middle;">${itemIndex+1}</td>`;
                            row += `<td rowspan="${rowspan}" class="text-center" style="vertical-align:middle;">${item.no_alat||''}</td>`;
                            row += `<td rowspan="${rowspan}" style="vertical-align:middle;">${typeHtml}</td>`;
                        }
                        row += `<td>${m.alat_nama||''}</td><td>${m.alat_no||''}</td>`;
                        row += `<td>${m.std_label||''}</td><td class="text-center">( ${m.min!==undefined?m.min:''} ~ ${m.max!==undefined?m.max:''} )</td>`;
                        row += `<td><input type="number" step="any" class="form-control text-center qa-p-actual1"></td>`;
                        row += `<td><input type="number" step="any" class="form-control text-center qa-p-actual2" ${c2?'':'disabled'} placeholder="${c2?'':'~'}"></td>`;
                        if(first){
                            row += `<td rowspan="${rowspan}" class="qa-p-keputusan text-center" style="vertical-align:middle;font-weight:bold;"></td>`;
                            row += `<td rowspan="${rowspan}" class="qa-p-next text-center" style="vertical-align:middle;">${next}</td>`;
                            row += `<td rowspan="${rowspan}" class="text-center" style="vertical-align:middle;">${item.no_gambar||''}</td>`;
                        }
                        row += `</tr>`;
                        tbody.append(row);
                    });
                });
            }
            function kumpulkanJSONFormP(){
                var items=JSON.parse(JSON.stringify(window.qaFormPStandar||[]));
                var lengkap=true, semuaOK=true;
                items.forEach(function(item,idx){
                    var itemOK=true;
                    var ms=Array.isArray(item.measurements)?item.measurements:[];
                    ms.forEach(function(m,j){
                        var tr=$('#tbody_form_p .qa-p-row[data-item="'+idx+'"][data-measure="'+j+'"]');
                        var a1=tr.find('.qa-p-actual1').val().trim();
                        var a2=tr.find('.qa-p-actual2').val().trim();
                        var min=Number(m.min), max=Number(m.max), cnt=String(m.actual_count||'2');
                        var rowOK=true;
                        if(a1==='' || !Number.isFinite(Number(a1))){ lengkap=false; rowOK=false; }
                        else if(Number(a1)<min || Number(a1)>max) rowOK=false;
                        if(cnt==='2'){
                            if(a2==='' || !Number.isFinite(Number(a2))){ lengkap=false; rowOK=false; }
                            else if(Number(a2)<min || Number(a2)>max) rowOK=false;
                        } else { a2=''; }
                        m.actual1=a1; m.actual2=a2; m.status=rowOK?'OK':'BAD';
                        if(!rowOK) itemOK=false;
                    });
                    item.keputusan=itemOK?'OK':'BAD';
                    if(!itemOK) semuaOK=false;
                    $('#tbody_form_p .qa-p-row[data-item="'+idx+'"]').first().find('.qa-p-keputusan').text(lengkap?item.keputusan:'');
                });
                $('#qa_hasil_json').val(JSON.stringify(items));
                $('#qa_status_final').val(lengkap&&items.length ? (semuaOK?'Good':'Bad') : '');
                return items;
            }
            $(document).on('input change','#tbody_form_p .qa-p-actual1, #tbody_form_p .qa-p-actual2',function(){ kumpulkanJSONFormP(); });
            $(document).on('change','#qa_tanggal_p',function(){ $('.qa-p-next').text(formatTanggalP(hitungTanggalBerikutP())); });

            // ================= LOGIKA FORM O (SNAP GAUGE) =================
            function fmtNumO(v,d){ if(!Number.isFinite(v)) return ''; d=(typeof d==='number'?d:3); var s=v.toFixed(d).replace(/0+$/,'').replace(/\.$/,''); return s==='-0'?'0':s; }
            function calcO(item){
                var gs=parseFloat(item.go_std||'0'), gn=parseFloat(item.go_tol_near||'0'), gf=parseFloat(item.go_tol_far||'0'), ns=parseFloat(item.notgo_std||'0'), nt=Math.abs(parseFloat(item.notgo_tol||'0'));
                var goMin = gs + Math.min(gn, gf), goMax = gs + Math.max(gn, gf);
                var notMin = ns - nt, notMax = ns + nt;
                return {goMin:goMin, goMax:goMax, notMin:notMin, notMax:notMax};
            }
            function tambahBarisO(item,no,nextDate){
                item=item||{}; var c=calcO(item), nextText=nextDate||'';
                $('#tbody_form_o').append(`
                    <tr class="qa-o-row" data-idx="${no-1}">
                        <td rowspan="2" class="text-center" style="vertical-align:middle;">${no}</td>
                        <td rowspan="2" class="text-center" style="vertical-align:middle;">${item.no_alat||''}</td>
                        <td rowspan="2" style="vertical-align:middle; text-align:center;">${item.spesifikasi||''}<br><small>* ${item.tol_plus||''}<br>* ${item.tol_minus||''}</small></td>
                        <td class="text-center" style="vertical-align:middle;">GO</td>
                        <td class="text-center" style="vertical-align:middle;">${item.go_std||''}</td>
                        <td class="text-center" style="vertical-align:middle;">${item.go_tol_near||''}<br>${item.go_tol_far||''}</td>
                        <td class="text-center" style="vertical-align:middle;">${fmtNumO(c.goMin,3)}</td>
                        <td class="text-center" style="vertical-align:middle;">${fmtNumO(c.goMax,3)}</td>
                        <td><select class="form-control qa-o-go-min"><option value="">--</option><option value="OK">OK</option><option value="NG">NG</option></select></td>
                        <td><select class="form-control qa-o-go-max"><option value="">--</option><option value="OK">OK</option><option value="NG">NG</option></select></td>
                        <td rowspan="2" class="qa-o-kode text-center" style="vertical-align:middle;font-weight:bold;"></td>
                        <td rowspan="2" class="qa-o-keputusan text-center" style="vertical-align:middle;font-weight:bold;"></td>
                        <td rowspan="2" class="text-center qa-o-next" style="vertical-align:middle;">${nextText}</td>
                        <td rowspan="2" class="text-center" style="vertical-align:middle;">${item.no_gambar||''}</td>
                    </tr>
                    <tr class="qa-o-row-2" data-idx="${no-1}">
                        <td class="text-center" style="vertical-align:middle;">NOT GO</td>
                        <td class="text-center" style="vertical-align:middle;">${item.notgo_std||''}</td>
                        <td class="text-center" style="vertical-align:middle;">±${item.notgo_tol||''}</td>
                        <td class="text-center" style="vertical-align:middle;">${fmtNumO(c.notMin,3)}</td>
                        <td class="text-center" style="vertical-align:middle;">${fmtNumO(c.notMax,3)}</td>
                        <td><select class="form-control qa-o-not-min"><option value="">--</option><option value="OK">OK</option><option value="NG">NG</option></select></td>
                        <td><select class="form-control qa-o-not-max"><option value="">--</option><option value="OK">OK</option><option value="NG">NG</option></select></td>
                    </tr>`);
            }
            function kumpulkanJSONFormO(){
                var rows=[], lengkap=true, semuaOK=true;
                $('#tbody_form_o .qa-o-row').each(function(){
                    var tr=$(this), idx=tr.data('idx'), item=(window.qaFormOStandar||[])[idx]||{}, c=calcO(item);
                    var goMin=tr.find('.qa-o-go-min').val(), goMax=tr.find('.qa-o-go-max').val();
                    var tr2=$('#tbody_form_o .qa-o-row-2[data-idx="'+idx+'"]');
                    var notMin=tr2.find('.qa-o-not-min').val(), notMax=tr2.find('.qa-o-not-max').val();
                    if(!goMin||!goMax||!notMin||!notMax) lengkap=false;
                    var ok = (goMin==='OK' && goMax==='OK' && notMin==='OK' && notMax==='OK');
                    if(goMin&&goMax&&notMin&&notMax&&!ok) semuaOK=false;
                    var kode = (!goMin||!goMax||!notMin||!notMax) ? '' : (ok ? 'HIJAU' : 'MERAH');
                    var keputusan = (!goMin||!goMax||!notMin||!notMax) ? '' : (ok ? 'OK' : 'BAD');
                    tr.find('.qa-o-kode').text(kode); tr.find('.qa-o-keputusan').text(keputusan);
                    rows.push({
                        no_alat:item.no_alat||'', spesifikasi:item.spesifikasi||'', tol_plus:item.tol_plus||'', tol_minus:item.tol_minus||'',
                        go_std:item.go_std||'', go_tol_near:item.go_tol_near||'', go_tol_far:item.go_tol_far||'', go_min:fmtNumO(c.goMin,3), go_max:fmtNumO(c.goMax,3),
                        notgo_std:item.notgo_std||'', notgo_tol:item.notgo_tol||'', notgo_min:fmtNumO(c.notMin,3), notgo_max:fmtNumO(c.notMax,3),
                        go_hasil_min:goMin, go_hasil_max:goMax, notgo_hasil_min:notMin, notgo_hasil_max:notMax,
                        kode_warna:kode, keputusan:keputusan, no_gambar:item.no_gambar||'', status:(ok?'OK':'BAD')
                    });
                });
                $('#qa_hasil_json').val(JSON.stringify(rows));
                $('#qa_status_final').val(lengkap && rows.length ? (semuaOK ? 'Good' : 'Bad') : '');
                return rows;
            }
            $(document).on('change','#tbody_form_o select', function(){ kumpulkanJSONFormO(); });

            // ================= LOGIKA FORM M (PROFILE PROJECTOR) =================
            function tambahBarisMAxis(item){
                item=item||{};
                var xOn=!!item.x_enabled,yOn=!!item.y_enabled;
                function h(cls,on){return `<input type="number" step="any" class="form-control text-center ${cls}" ${on?'':'disabled'} placeholder="${on?'Hasil':'~'}">`;}
                function c(cls,on){return `<input type="text" class="form-control text-center ${cls}" readonly style="background:#eee;" value="${on?'':'~'}">`;}
                $('#tbody_form_m_axis').append(`
                    <tr class="qa-m-axis-row" data-x="${xOn?'1':'0'}" data-y="${yOn?'1':'0'}">
                        <td><input type="number" step="any" class="form-control text-center qa-m-axis-std" value="${item.std_mm||''}" readonly style="background:#eee;"></td>
                        <td>${h('qa-m-x-left',xOn)}</td><td>${c('qa-m-x-left-corr',xOn)}</td>
                        <td>${h('qa-m-x-right',xOn)}</td><td>${c('qa-m-x-right-corr',xOn)}</td>
                        <td>${h('qa-m-y-front',yOn)}</td><td>${c('qa-m-y-front-corr',yOn)}</td>
                        <td>${h('qa-m-y-back',yOn)}</td><td>${c('qa-m-y-back-corr',yOn)}</td>
                    </tr>`);
            }
            function tambahBarisMAngle(item){
                item=item||{};var no=$('#tbody_form_m_angle tr').length+1;
                $('#tbody_form_m_angle').append(`
                    <tr class="qa-m-angle-row">
                        <td class="text-center">${no}</td>
                        <td><input type="number" step="any" class="form-control text-center qa-m-angle-std" value="${item.std_degree||''}" readonly style="background:#eee;"></td>
                        <td><input type="number" step="1" class="form-control text-center qa-m-angle-deg"></td>
                        <td><input type="number" step="1" min="0" max="59" class="form-control text-center qa-m-angle-min"></td>
                        <td><input type="number" step="any" min="0" max="59.999" class="form-control text-center qa-m-angle-sec"></td>
                        <td><input type="text" class="form-control text-center qa-m-angle-corr" readonly style="background:#eee;"></td>
                    </tr>`);
            }
            function fmtAxisM(v){if(!Number.isFinite(v))return'';var s=v.toFixed(4).replace(/0+$/,'').replace(/\.$/,'');return s==='-0'?'0':s;}
            function fmtAngleM(ts){if(!Number.isFinite(ts))return'';var sg=ts<0?'-':'',a=Math.abs(ts),m=Math.floor(a/60),s=a-m*60,st=s.toFixed(2).replace(/0+$/,'').replace(/\.$/,'');return sg+String(m).padStart(2,'0')+" ' "+st.padStart(2,'0')+' "';}
            function kumpulkanJSONFormM(){
                var rows=[],tolAxis=parseFloat($('#qa_toleransi_value').val()||$('#qa_toleransi').val()||'0'),tolSudutMenit=parseFloat($('#qa_m_tol_sudut').val()||'0'),lengkap=true,semuaOK=true;
                $('#tbody_form_m_axis .qa-m-axis-row').each(function(){
                    var tr=$(this),sr=tr.find('.qa-m-axis-std').val().trim(),std=parseFloat(sr),xOn=tr.attr('data-x')==='1',yOn=tr.attr('data-y')==='1';
                    function baca(sel,corr,on){
                        if(!on)return{hasil:'~',corr:'~',ok:true};
                        var raw=tr.find(sel).val().trim(),num=parseFloat(raw);
                        if(raw===''||!Number.isFinite(num)||!Number.isFinite(std)){lengkap=false;tr.find(corr).val('');return{hasil:raw,corr:'',ok:false};}
                        var cv=std-num,ok=Number.isFinite(tolAxis)&&tolAxis>0&&Math.abs(cv)<=Math.abs(tolAxis)+1e-12,ct=fmtAxisM(cv);
                        if(!ok)semuaOK=false;tr.find(corr).val(ct);return{hasil:raw,corr:ct,ok:ok};
                    }
                    var xl=baca('.qa-m-x-left','.qa-m-x-left-corr',xOn),xr=baca('.qa-m-x-right','.qa-m-x-right-corr',xOn),yf=baca('.qa-m-y-front','.qa-m-y-front-corr',yOn),yb=baca('.qa-m-y-back','.qa-m-y-back-corr',yOn);
                    rows.push({jenis:'AXIS',std_mm:sr,x_enabled:xOn,y_enabled:yOn,x_left:xl.hasil,x_left_correction:xl.corr,x_right:xr.hasil,x_right_correction:xr.corr,y_front:yf.hasil,y_front_correction:yf.corr,y_back:yb.hasil,y_back_correction:yb.corr,status:(xl.ok&&xr.ok&&yf.ok&&yb.ok)?'OK':'BURUK'});
                });
                $('#tbody_form_m_angle .qa-m-angle-row').each(function(){
                    var tr=$(this),sr=tr.find('.qa-m-angle-std').val().trim(),dr=tr.find('.qa-m-angle-deg').val().trim(),mr=tr.find('.qa-m-angle-min').val().trim(),rr=tr.find('.qa-m-angle-sec').val().trim();
                    var sd=parseFloat(sr),d=parseFloat(dr),mi=parseFloat(mr),se=parseFloat(rr);
                    if(sr===''||!Number.isFinite(sd)||dr===''||!Number.isFinite(d)||mr===''||!Number.isFinite(mi)||rr===''||!Number.isFinite(se)||mi<0||mi>=60||se<0||se>=60){lengkap=false;tr.find('.qa-m-angle-corr').val('');rows.push({jenis:'ANGLE',std_degree:sr,hasil_degree:dr,hasil_minute:mr,hasil_second:rr,correction:'',status:''});return;}
                    var cs=(d*3600+mi*60+se)-(sd*3600);
                    var cd=cs/3600;
                    var tolSudutDetik=Number.isFinite(tolSudutMenit)?Math.abs(tolSudutMenit)*60:0;
                    var ok=tolSudutDetik>0&&Math.abs(cs)<=tolSudutDetik+1e-12;
                    var ct=fmtAngleM(cs);
                    if(!ok)semuaOK=false;
                    tr.find('.qa-m-angle-corr').val(ct);
                    rows.push({
                        jenis:'ANGLE',
                        std_degree:sr,
                        hasil_degree:dr,
                        hasil_minute:mr,
                        hasil_second:rr,
                        correction:ct,
                        correction_seconds:cs,
                        correction_degree:cd,
                        toleransi_menit:tolSudutMenit,
                        toleransi_detik:tolSudutDetik,
                        status:ok?'OK':'BURUK'
                    });
                });
                $('#qa_hasil_json').val(JSON.stringify(rows));$('#qa_status_final').val(lengkap&&rows.length?(semuaOK?'Good':'Bad'):'');return rows;
            }
            $(document).on('input change','#tbody_form_m_axis input, #tbody_form_m_angle input',function(){kumpulkanJSONFormM();});

            // ================= LOGIKA FORM L (PRESSURE GAUGE) =================
            function tambahBarisL(item, nomor) {
                item = item || {};
                var html = `
                    <tr class="qa-l-row">
                        <td><input type="text" class="form-control text-center qa-l-arah" value="${String(item.arah || '').toUpperCase()}" readonly style="background:#eee;font-weight:bold;"></td>
                        <td><input type="number" step="any" class="form-control text-center qa-l-applied" value="${item.applied_value !== undefined ? item.applied_value : ''}" readonly style="background:#eee;"></td>
                        <td><input type="number" step="any" class="form-control text-center qa-l-indicated" placeholder="Indicated Value"></td>
                        <td><input type="text" class="form-control text-center qa-l-correction" readonly style="background:#eee;font-weight:bold;"></td>
                    </tr>`;
                $('#tbody_form_l').append(html);
            }

            function formatCorrectionL(value) {
                if (!Number.isFinite(value)) return '';
                var s = value.toFixed(4).replace(/0+$/, '').replace(/\.$/, '');
                return s === '-0' ? '0' : s;
            }

            function kumpulkanJSONFormL() {
                var rows = [];
                var tol = parseFloat($('#qa_toleransi_value').val() || $('#qa_toleransi').val() || '0');
                var lengkap = true;
                var semuaOK = true;

                $('#tbody_form_l .qa-l-row').each(function() {
                    var arah = $(this).find('.qa-l-arah').val().trim().toUpperCase();
                    var appliedRaw = $(this).find('.qa-l-applied').val().trim();
                    var indicatedRaw = $(this).find('.qa-l-indicated').val().trim();

                    var applied = parseFloat(appliedRaw);
                    var indicated = parseFloat(indicatedRaw);
                    var correctionText = '';
                    var status = '';

                    if (appliedRaw === '' || !Number.isFinite(applied) || indicatedRaw === '' || !Number.isFinite(indicated)) {
                        lengkap = false;
                    } else {
                        var correction = applied - indicated;
                        correctionText = formatCorrectionL(correction);
                        var ok = Number.isFinite(tol) && tol > 0 && Math.abs(correction) <= Math.abs(tol) + 1e-12;
                        status = ok ? 'OK' : 'BURUK';
                        if (!ok) semuaOK = false;
                    }

                    $(this).find('.qa-l-correction').val(correctionText);

                    rows.push({
                        arah: arah,
                        applied_value: appliedRaw,
                        indicated_value: indicatedRaw,
                        correction: correctionText,
                        toleransi: Number.isFinite(tol) ? tol : '',
                        status: status
                    });
                });

                $('#qa_hasil_json').val(JSON.stringify(rows));
                $('#qa_status_final').val(lengkap && rows.length ? (semuaOK ? 'Good' : 'Bad') : '');
                return rows;
            }

            $(document).on('input change', '#tbody_form_l .qa-l-indicated', function() {
                kumpulkanJSONFormL();
            });

            // ================= LOGIKA FORM K (VOLTAGE ULTRASONIC) =================
            function tambahBarisK(item, nomor) {
                item=item||{}; var nilai=item.nilai_ditentukan!==undefined?item.nilai_ditentukan:'';
                $('#tbody_form_k').append(`<tr class="qa-k-row"><td class="text-center">${nomor}</td><td><input type="number" step="any" class="form-control text-center qa-k-nilai" value="${nilai}" readonly style="background:#eee;"></td><td><input type="number" step="any" class="form-control text-center qa-k-pengukuran" placeholder="Masukkan hasil pengukuran"></td><td><input type="text" class="form-control text-center qa-k-selisih" readonly style="background:#eee;font-weight:bold;"></td><td><input type="text" class="form-control text-center qa-k-status" readonly style="background:#eee;font-weight:bold;"></td></tr>`);
            }
            function kumpulkanJSONFormK(){var rows=[],tol=parseFloat($('#qa_toleransi_value').val()||$('#qa_toleransi').val()||'0'),lengkap=true,semuaLulus=true;$('#tbody_form_k .qa-k-row').each(function(){var nr=$(this).find('.qa-k-nilai').val().trim(),ur=$(this).find('.qa-k-pengukuran').val().trim(),n=parseFloat(nr),u=parseFloat(ur),sel='',status='';if(nr===''||!Number.isFinite(n)||ur===''||!Number.isFinite(u)){lengkap=false;}else{var s=n-u,batas=Math.abs(n)*(Math.abs(tol)/100),lulus=Math.abs(s)<=batas+1e-12;sel=s.toFixed(1);status=lulus?'Lulus':'Tidak Lulus';if(!lulus)semuaLulus=false;}$(this).find('.qa-k-selisih').val(sel);$(this).find('.qa-k-status').val(status);rows.push({nilai_ditentukan:nr,nilai_pengukuran:ur,selisih:sel,toleransi_persen:Number.isFinite(tol)?tol:'',status:status});});$('#qa_hasil_json').val(JSON.stringify(rows));$('#qa_status_final').val(lengkap&&rows.length?(semuaLulus?'Good':'Bad'):'');return rows;}
            $(document).on('input change','#tbody_form_k .qa-k-pengukuran',function(){kumpulkanJSONFormK();});

            // ================= LOGIKA FORM J (VERIFIKASI RADIUS GAUGE) =================
            function hitungTanggalBerikutJ() {
                var tanggal = $('#qa_tanggal_j').val();
                var periode = parseInt($('#qa_j_periode').val() || '0', 10);

                if (!tanggal || !periode || periode <= 0) {
                    $('#qa_j_tgl_berikut').val('');
                    return '';
                }

                var d = new Date(tanggal + 'T00:00:00');
                if (isNaN(d.getTime())) {
                    $('#qa_j_tgl_berikut').val('');
                    return '';
                }

                d.setDate(d.getDate() + periode);

                var yyyy = d.getFullYear();
                var mm = String(d.getMonth() + 1).padStart(2, '0');
                var dd = String(d.getDate()).padStart(2, '0');
                var hasil = dd + '-' + mm + '-' + yyyy;

                $('#qa_j_tgl_berikut').val(hasil);
                return hasil;
            }

            function kumpulkanJSONFormJ() {
                var hasil = $('#qa_j_hasil').val();

                var data = [{
                    no_part: $('#qa_j_no_alat').val().trim(),
                    spesifikasi: $('#qa_j_spesifikasi').val().trim(),
                    alat_verifikasi: $('#qa_j_alat_verifikasi').val().trim(),
                    hasil_verifikasi: hasil,
                    tanggal_berikut: hitungTanggalBerikutJ()
                }];

                $('#qa_hasil_json').val(JSON.stringify(data));

                if (hasil === 'O') $('#qa_status_final').val('Good');
                else if (hasil === 'X') $('#qa_status_final').val('Bad');
                else $('#qa_status_final').val('');

                return data;
            }

            $(document).on('input change', '#qa_tanggal_j, #qa_j_hasil', function() {
                kumpulkanJSONFormJ();
            });

            // ================= LOGIKA FORM H (THREAD PLUG GAUGE) =================
            function formatStdH(nilai, tol, range) {
                var parts = [];
                if (nilai) parts.push(nilai);
                if (tol) parts.push(tol);
                if (range) parts.push('(' + range + ')');
                return parts.join(' | ');
            }

            function tambahBarisH(item, nomor) {
                item = item || {};
                var hasOut = String(item.std_out || '').trim() !== '';
                var hasPitch = String(item.std_pitch || '').trim() !== '';
                var html = `
                    <tr class="h-row"
                        data-posisi="${String(item.posisi_ukur || '').replace(/"/g, '&quot;')}"
                        data-std-spek="${String(item.std_spesifikasi || '').replace(/"/g, '&quot;')}"
                        data-std-no="${String(item.std_no_alat || '').replace(/"/g, '&quot;')}"
                        data-std-out="${String(item.std_out || '').replace(/"/g, '&quot;')}"
                        data-tol-out="${String(item.tol_out || '').replace(/"/g, '&quot;')}"
                        data-range-out="${String(item.range_out || '').replace(/"/g, '&quot;')}"
                        data-std-pitch="${String(item.std_pitch || '').replace(/"/g, '&quot;')}"
                        data-tol-pitch="${String(item.tol_pitch || '').replace(/"/g, '&quot;')}"
                        data-range-pitch="${String(item.range_pitch || '').replace(/"/g, '&quot;')}">
                        <td style="vertical-align:middle;font-weight:bold;">${item.posisi_ukur || '-'}</td>
                        <td style="vertical-align:middle;">${item.std_spesifikasi || '-'} </td>
                        <td style="vertical-align:middle;">${item.std_no_alat || '-'}</td>
                        <td style="vertical-align:middle; font-size:12px;">${formatStdH(item.std_out, item.tol_out, item.range_out) || '///'}</td>
                        <td style="vertical-align:middle; font-size:12px;">${formatStdH(item.std_pitch, item.tol_pitch, item.range_pitch) || '///'}</td>
                        <td><input type="text" class="form-control text-center h-out-dep" ${hasOut ? '' : 'disabled'}></td>
                        <td><input type="text" class="form-control text-center h-out-ten" ${hasOut ? '' : 'disabled'}></td>
                        <td><input type="text" class="form-control text-center h-out-bel" ${hasOut ? '' : 'disabled'}></td>
                        <td><input type="text" class="form-control text-center h-pitch-dep" ${hasPitch ? '' : 'disabled'}></td>
                        <td><input type="text" class="form-control text-center h-pitch-ten" ${hasPitch ? '' : 'disabled'}></td>
                        <td><input type="text" class="form-control text-center h-pitch-bel" ${hasPitch ? '' : 'disabled'}></td>
                    </tr>`;
                $('#tbody_form_h').append(html);
            }

            function kumpulkanJSONFormH() {
                var warna = $('#qa_h_kode_warna').val().trim().toUpperCase();
                var putusan = $('#qa_h_putusan').val();
                var data = [];

                $('#tbody_form_h .h-row').each(function() {
                    var tr = $(this);
                    data.push({
                        posisi_ukur: tr.attr('data-posisi') || '',
                        std_spesifikasi: tr.attr('data-std-spek') || '',
                        std_no_alat: tr.attr('data-std-no') || '',
                        std_out: tr.attr('data-std-out') || '',
                        tol_out: tr.attr('data-tol-out') || '',
                        range_out: tr.attr('data-range-out') || '',
                        std_pitch: tr.attr('data-std-pitch') || '',
                        tol_pitch: tr.attr('data-tol-pitch') || '',
                        range_pitch: tr.attr('data-range-pitch') || '',
                        out: {
                            dep: tr.find('.h-out-dep').val() || '',
                            ten: tr.find('.h-out-ten').val() || '',
                            bel: tr.find('.h-out-bel').val() || ''
                        },
                        pitch: {
                            dep: tr.find('.h-pitch-dep').val() || '',
                            ten: tr.find('.h-pitch-ten').val() || '',
                            bel: tr.find('.h-pitch-bel').val() || ''
                        },
                        kode_warna: warna,
                        putusan: putusan
                    });
                });

                $('#qa_hasil_json').val(JSON.stringify(data));
                if (putusan === 'OK') $('#qa_status_final').val('Good');
                else if (putusan === 'BAD') $('#qa_status_final').val('Bad');
                else $('#qa_status_final').val('');
                return data;
            }

            $(document).on('input change', '#tbody_form_h input, #qa_h_kode_warna, #qa_h_putusan', function() {
                kumpulkanJSONFormH();
            });

            // ================= LOGIKA FORM I (GAUGE) =================
            function kumpulkanJSONFormI() {
                var stdSpek = $('#qa_i_std_spesifikasi').val().trim();
                var stdNo = $('#qa_i_std_no').val().trim();
                var hasilAtas = $('#qa_i_hasil_atas').val().trim();
                var hasilBawah = $('#qa_i_hasil_bawah').val().trim();
                var warna = $('#qa_i_kode_warna').val().trim().toUpperCase();
                var putusan = $('#qa_i_putusan').val();

                var data = [{
                    std_spesifikasi: stdSpek,
                    std_no_alat: stdNo,
                    hasil_atas: hasilAtas,
                    hasil_bawah: hasilBawah,
                    kode_warna: warna,
                    putusan: putusan
                }];

                $('#qa_hasil_json').val(JSON.stringify(data));
                if (putusan === 'OK') $('#qa_status_final').val('Good');
                else if (putusan === 'NG') $('#qa_status_final').val('Bad');
                else $('#qa_status_final').val('');
                return data;
            }

            $(document).on('input change', '#qa_i_hasil_atas, #qa_i_hasil_bawah, #qa_i_kode_warna, #qa_i_putusan', function() {
                kumpulkanJSONFormI();
            });

            // ================= SIMPAN FORM A =================
            $('#btn-simpan-fa').click(function() {
                var noAlat = $('#fa_no_alat').val().trim();
                var noLaporan = $('#fa_no_laporan').val().trim();
                var tanggal = $('#fa_tanggal_periksa').val();

                if (noAlat === '' || noLaporan === '' || tanggal === '') {
                    BootstrapDialog.alert('No. Alat, No. Laporan, dan Tanggal Periksa wajib diisi!');
                    return;
                }

                var btn = $(this); var oldHtml = btn.html();
                var hasilFA = collectFormAResult();

                btn.html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...'); btn.prop('disabled', true);

                $.ajax({
                    url: 'proses_qa.php?action=simpan_qa', type: 'POST', dataType: 'json',
                    data: {
                        no_part: noAlat,
                        status_qa: ($('#fa_kep').val() === 'OK' ? 'Good' : ($('#fa_kep').val() === 'NG' ? 'Bad' : '')),
                        hasil_json: JSON.stringify(hasilFA),
                        suhu: $('#fa_suhu').val(), kelembapan: $('#fa_humidity').val(), ketidakpastian: '', confidence: '',
                        keterangan: $('#fa_keterangan').val(), tanggal_periksa: tanggal, format_laporan: 'A',
                        no_laporan: noLaporan, kode_varian: $('#fa_kode_varian').val(), warna: $('#fa_warna').val(),
                        kep: $('#fa_kep').val(), periksa_berikut: $('#fa_periksa_berikut').val()
                    },
                    success: function(res) {
                        btn.html(oldHtml); btn.prop('disabled', false);
                        if (res.status === 'success') {
                            var dataCetak = {
                                format: 'A', no_laporan: noLaporan, no_part: noAlat, nama_part: $('#fa_nama_alat').val(),
                                spesifikasi: $('#fa_spesifikasi').val(), seksi_pemilik: $('#fa_seksi').val(),
                                metode: $('#fa_metode').val(), traceability: $('#fa_traceability').val(),
                                suhu: $('#fa_suhu').val(), kelembapan: $('#fa_humidity').val(), tanggal_periksa: tanggal,
                                jadwal_berikutnya: res.jadwal_selanjutnya || $('#fa_periksa_berikut').val(),
                                petugas: $('#fa_petugas').val(), keterangan: $('#fa_keterangan').val(),
                                kode_varian: $('#fa_kode_varian').val(), warna: $('#fa_warna').val(), kep: $('#fa_kep').val(),
                                periksa_berikut: $('#fa_periksa_berikut').val(),
                                alat_standar: {
                                    no_alat: faValue('fa_go_std_no_alat') || faValue('fa_notgo_std_no_alat'),
                                    spesifikasi: faValue('fa_go_std_spesifikasi') || faValue('fa_notgo_std_spesifikasi'),
                                    traceability: faValue('fa_traceability'),
                                    go: { no_alat: faValue('fa_go_std_no_alat'), spesifikasi: faValue('fa_go_std_spesifikasi') },
                                    not_go: { no_alat: faValue('fa_notgo_std_no_alat'), spesifikasi: faValue('fa_notgo_std_spesifikasi') }
                                },
                                hasil_array: [hasilFA]
                            };
                            sessionStorage.setItem('cetak_data', JSON.stringify(dataCetak));
                            if (isFormJ) {
                                var dataCetakJ = res.laporan_j || {
                                    format: 'J',
                                    no_laporan: $('#qa_no_laporan_j').val().trim(),
                                    no_part: noAlat,
                                    nama_part: $('#qa_nama_alat').val(),
                                    spesifikasi: $('#qa_j_spesifikasi').val(),
                                    seksi_pemilik: (window.qaCurrentAlat && window.qaCurrentAlat.seksi_pemilik) || '',
                                    metode: $('#qa_metode').val(),
                                    alat_verifikasi: $('#qa_j_alat_verifikasi').val(),
                                    tanggal_periksa: $('#qa_tanggal_j').val(),
                                    jadwal_berikutnya: res.jadwal_selanjutnya || '',
                                    petugas: $('#qa_petugas').val(),
                                    foreman: $('#qa_foreman_j').val(),
                                    supervisor: $('#qa_supervisor_j').val(),
                                    keterangan: $('#qa_keterangan').val(),
                                    keputusan: statusQA,
                                    hasil_array: JSON.parse(jsonHasil || '[]')
                                };
                                sessionStorage.setItem('cetak_data', JSON.stringify(dataCetakJ));
                            }

                            if (isFormK) {
                                var dataCetakK=res.laporan_k||{format:'K',no_laporan:$('#qa_no_laporan_k').val().trim(),no_part:noAlat,nama_part:$('#qa_nama_alat').val(),spesifikasi:(window.qaCurrentAlat&&(window.qaCurrentAlat.spesifikasi||window.qaCurrentAlat.ukuran))||'',resolusi:(window.qaCurrentAlat&&window.qaCurrentAlat.resolusi)||'',seksi_pemilik:(window.qaCurrentAlat&&window.qaCurrentAlat.seksi_pemilik)||'',metode:$('#qa_metode').val(),standar_uji:$('#qa_standar_uji').val(),traceability:$('#qa_trace').val(),toleransi:$('#qa_toleransi_value').val(),suhu:$('#qa_suhu').val(),kelembapan:$('#qa_kelembapan').val(),tanggal_periksa:$('#qa_tanggal_k').val(),jadwal_berikutnya:res.jadwal_selanjutnya||'',petugas:$('#qa_petugas').val(),foreman:$('#qa_foreman_k').val(),supervisor:$('#qa_supervisor_k').val(),keterangan:$('#qa_keterangan').val(),keputusan:statusQA,hasil_array:JSON.parse(jsonHasil||'[]')};
                                sessionStorage.setItem('cetak_data',JSON.stringify(dataCetakK));
                            }

                            if (isFormL) {
                                var dataCetakL = res.laporan_l || {
                                    format:'L',
                                    no_laporan:$('#qa_no_laporan_l').val().trim(),
                                    no_part:noAlat,
                                    nama_part:$('#qa_nama_alat').val(),
                                    spesifikasi:(window.qaCurrentAlat&&(window.qaCurrentAlat.spesifikasi||window.qaCurrentAlat.ukuran))||'',
                                    resolusi:(window.qaCurrentAlat&&window.qaCurrentAlat.resolusi)||'',
                                    seksi_pemilik:(window.qaCurrentAlat&&window.qaCurrentAlat.seksi_pemilik)||'',
                                    metode:$('#qa_metode').val(),
                                    standar_uji:$('#qa_standar_uji').val(),
                                    std_no_alat:$('#qa_l_std_no').val(),
                                    traceability:$('#qa_trace').val(),
                                    toleransi:$('#qa_toleransi_value').val(),
                                    suhu:$('#qa_suhu').val(),
                                    kelembapan:$('#qa_kelembapan').val(),
                                    ketidakpastian:$('#qa_ketidakpastian').val(),
                                    confidence:$('#qa_confidence').val(),
                                    tanggal_periksa:$('#qa_tanggal_l').val(),
                                    jadwal_berikutnya:res.jadwal_selanjutnya||'',
                                    petugas:$('#qa_petugas').val(),
                                    foreman:$('#qa_foreman_l').val(),
                                    supervisor:$('#qa_supervisor_l').val(),
                                    keterangan:$('#qa_keterangan').val(),
                                    keputusan:statusQA,
                                    hasil_array:JSON.parse(jsonHasil||'[]')
                                };
                                sessionStorage.setItem('cetak_data',JSON.stringify(dataCetakL));
                            }

                            if(isFormM){
                                var dataCetakM=res.laporan_m||{
                                    format:'M',no_laporan:$('#qa_no_laporan_m').val().trim(),no_part:noAlat,nama_part:$('#qa_nama_alat').val(),
                                    spesifikasi:(window.qaCurrentAlat&&(window.qaCurrentAlat.spesifikasi||window.qaCurrentAlat.ukuran))||'',resolusi:(window.qaCurrentAlat&&window.qaCurrentAlat.resolusi)||'',
                                    seksi_pemilik:(window.qaCurrentAlat&&window.qaCurrentAlat.seksi_pemilik)||'',metode:$('#qa_metode').val(),standar_uji:$('#qa_standar_uji').val(),
                                    standar_uji_sudut:$('#qa_m_standar_sudut').val(),traceability:$('#qa_trace').val(),toleransi_axis:$('#qa_toleransi_value').val(),toleransi_sudut:$('#qa_m_tol_sudut').val(),
                                    suhu:$('#qa_suhu').val(),kelembapan:$('#qa_kelembapan').val(),ketidakpastian:$('#qa_ketidakpastian').val(),confidence:$('#qa_confidence').val(),
                                    tanggal_periksa:$('#qa_tanggal_m').val(),jadwal_berikutnya:res.jadwal_selanjutnya||'',petugas:$('#qa_petugas').val(),foreman:$('#qa_foreman_m').val(),supervisor:$('#qa_supervisor_m').val(),
                                    keterangan:$('#qa_keterangan').val(),keputusan:statusQA,hasil_array:JSON.parse(jsonHasil||'[]')
                                };
                                sessionStorage.setItem('cetak_data',JSON.stringify(dataCetakM));
                            }

                            if(isFormO){
                                var dataCetakO = res.laporan_o || {
                                    format:'O', no_laporan:$('#qa_no_laporan_o').val().trim(), nama_alat:$('#qa_nama_alat').val(),
                                    seksi:(window.qaCurrentAlat&&window.qaCurrentAlat.seksi_pemilik)||'', petugas:$('#qa_petugas').val(),
                                    metode:$('#qa_metode').val(), standar_uji:$('#qa_o_standar_uji').val(), traceability:$('#qa_o_traceability').val(),
                                    suhu:$('#qa_suhu').val(), kelembapan:$('#qa_kelembapan').val(), tanggal_periksa:$('#qa_tanggal_o').val(),
                                    jadwal_berikutnya:res.jadwal_selanjutnya||'', foreman:$('#qa_foreman_o').val().trim(), supervisor:$('#qa_supervisor_o').val().trim(),
                                    keputusan:statusQA, hasil_array:JSON.parse(jsonHasil||'[]'), halaman:'1 / 1'
                                };
                                sessionStorage.setItem('cetak_data', JSON.stringify(dataCetakO));
                            }

                            if(isFormP){
                                var dataCetakP = res.laporan_p || {
                                    format:'P', no_laporan:$('#qa_no_laporan_p').val().trim(), nama_alat:$('#qa_nama_alat').val(),
                                    seksi:(window.qaCurrentAlat&&window.qaCurrentAlat.seksi_pemilik)||'', petugas:$('#qa_petugas').val(),
                                    metode:$('#qa_metode').val(), standar_uji:$('#qa_standar_uji').val(), traceability:$('#qa_trace').val(),
                                    suhu:$('#qa_suhu').val(), kelembapan:$('#qa_kelembapan').val(), tanggal_periksa:$('#qa_tanggal_p').val(),
                                    jadwal_berikutnya:res.jadwal_selanjutnya||hitungTanggalBerikutP(), foreman:$('#qa_foreman_p').val().trim(), supervisor:$('#qa_supervisor_p').val().trim(),
                                    keputusan:statusQA, hasil_array:JSON.parse(jsonHasil||'[]'), halaman:'1 / 1', keterangan:$('#qa_keterangan').val()
                                };
                                sessionStorage.setItem('cetak_data', JSON.stringify(dataCetakP));
                            }

                            BootstrapDialog.confirm({
                                title: 'SUKSES!', type: BootstrapDialog.TYPE_SUCCESS,
                                message: isFormP ? 'Laporan Form P SK Gauge berhasil disimpan.<br><br><b>Cetak Laporan?</b>' : (isFormO ? 'Laporan Form O Snap Gauge berhasil disimpan.<br><br><b>Cetak Laporan?</b>' : (isFormM ? 'Laporan Form M Profile Projector berhasil disimpan.<br><br><b>Cetak Laporan?</b>' : (isFormL ? 'Laporan Form L Pressure Gauge berhasil disimpan.<br><br><b>Cetak Laporan?</b>' : 'Laporan Pemeriksaan berhasil disimpan.<br><br><b>Cetak Laporan?</b>'))),
                                btnCancelLabel: 'Tidak', btnOKLabel: 'Ya, Cetak', btnOKClass: 'btn-primary',
                                callback: function(result) {
                                    if (result) { window.location.href = 'cetak_laporan.php'; } 
                                    else { $('#form-form-a').slideUp(); $('#nomor_alat').prop('disabled', false).val('').focus(); }
                                }
                            });
                        } else { BootstrapDialog.alert('Gagal: ' + (res.message || 'Tidak dapat menyimpan.')); }
                    },
                    error: function() { btn.html(oldHtml); btn.prop('disabled', false); BootstrapDialog.alert('Error Submit Form A!'); }
                });
            });

            // ================= SUBMIT QA UMUM / FORM B =================
            $('#btn-simpan-qa').click(function() {
                var noAlat = $('#qa_nomor_alat').val();
                var statusQA = $('#qa_status_final').val();
                if ($('#wrapper_form_b').is(':visible')) {
                    $('#tbody_form_b .b-hasil').each(function() { hitungB(this); });
                    statusQA = $('#qa_status_final').val();
                }
                if ($('#wrapper_form_c').is(':visible')) {
                    kumpulkanJSONFormC();
                    statusQA = $('#qa_status_final').val();
                }
                if ($('#wrapper_form_h').is(':visible')) {
                    kumpulkanJSONFormH();
                    statusQA = $('#qa_status_final').val();
                }
                if ($('#wrapper_form_i').is(':visible')) {
                    kumpulkanJSONFormI();
                    statusQA = $('#qa_status_final').val();
                }
                if ($('#wrapper_form_j').is(':visible')) {
                    kumpulkanJSONFormJ();
                    statusQA = $('#qa_status_final').val();
                }
                if ($('#wrapper_form_k').is(':visible')) { kumpulkanJSONFormK(); statusQA = $('#qa_status_final').val(); }
                if ($('#wrapper_form_l').is(':visible')) { kumpulkanJSONFormL(); statusQA = $('#qa_status_final').val(); }
                if ($('#wrapper_form_m').is(':visible')) { kumpulkanJSONFormM(); statusQA = $('#qa_status_final').val(); }
                if ($('#wrapper_form_o').is(':visible')) { kumpulkanJSONFormO(); statusQA = $('#qa_status_final').val(); }
                if ($('#wrapper_form_p').is(':visible')) { kumpulkanJSONFormP(); statusQA = $('#qa_status_final').val(); }
                if ($('#wrapper_form_q').is(':visible')) { kumpulkanJSONFormQ(); statusQA = $('#qa_status_final').val(); }
                var jsonHasil = $('#qa_hasil_json').val(); 
                
                var isFormB = $('#wrapper_form_b').is(':visible');
                var isFormC = $('#wrapper_form_c').is(':visible');
                var isFormD = $('#wrapper_form_d').is(':visible');
                var isFormH = $('#wrapper_form_h').is(':visible');
                var isFormI = $('#wrapper_form_i').is(':visible');
                var isFormJ = $('#wrapper_form_j').is(':visible');
                var isFormK = $('#wrapper_form_k').is(':visible');
                var isFormL = $('#wrapper_form_l').is(':visible');
                var isFormM = $('#wrapper_form_m').is(':visible');
                var isFormO = $('#wrapper_form_o').is(':visible');
                var isFormP = $('#wrapper_form_p').is(':visible');
                var isFormQ = $('#wrapper_form_q').is(':visible');
                if (isFormB) {
                    if ($('#btn-complete-b').is(':visible')) { BootstrapDialog.alert('Simpan tiga standar alat lama terlebih dahulu.'); return; }
                    var dataB = [];
                    try { dataB = JSON.parse(jsonHasil || '[]'); } catch(e) { dataB = []; }
                    var noLaporanB = $('#qa_no_laporan_b').val().trim();
                    var tanggalB = $('#qa_tanggal_b').val();
                    if (noLaporanB === '' || tanggalB === '') {
                        BootstrapDialog.alert('No. Laporan dan Tanggal Pemeriksaan Form B wajib diisi!'); return;
                    }
                    if (statusQA === '' || dataB.length === 0) {
                        BootstrapDialog.alert('Lengkapi pengujian Torsi Air Driver dan pilih Status Final!'); return;
                    }
                    var validRowsB = true;
                    var hasBadB = false;
                    dataB.forEach(function(row) {
                        if (row.tekanan === '' || row.handle === '' || row.baut === '' || row.std_min === '' || row.std_max === '' || row.std1 === '' || row.std2 === '' || row.std3 === '' || row.hasil === '' || !Number.isFinite(Number(row.hasil))) validRowsB = false;
                        if (row.status === 'BURUK') hasBadB = true;
                    });
                    if (!validRowsB) { BootstrapDialog.alert('Lengkapi tiga nilai standar registrasi dan hasil kalibrasi pada setiap baris Form B!'); return; }
                    if (hasBadB && statusQA === 'Good') { BootstrapDialog.alert('Ada titik pengujian yang BURUK. Status Final tidak boleh OK.'); return; }
                    if (!hasBadB && statusQA === 'Bad') { BootstrapDialog.alert('Semua titik pengujian berada dalam batas standar. Status Final harus OK.'); return; }
                } else if (isFormC) {
                    var dataC = [];
                    try { dataC = JSON.parse(jsonHasil || '[]'); } catch(e) { dataC = []; }
                    var noLaporanC = $('#qa_no_laporan_c').val().trim();
                    var tanggalC = $('#qa_tanggal_c').val();
                    if (noLaporanC === '' || tanggalC === '') {
                        BootstrapDialog.alert('No. Laporan dan Tanggal Periksa Form C wajib diisi!'); return;
                    }
                    if (dataC.length === 0 || statusQA === '') {
                        BootstrapDialog.alert('Lengkapi seluruh Actual (PV) dan Alat STD Form C!'); return;
                    }
                    var validC = dataC.every(function(row) {
                        return row.setting !== '' && row.actual !== '' && row.alat_std !== '' && row.status !== 'KOSONG';
                    });
                    if (!validC) { BootstrapDialog.alert('Semua baris Form C wajib diisi lengkap.'); return; }
                } else if (isFormM) {
                    var dataM=[];try{dataM=JSON.parse(jsonHasil||'[]');}catch(e){dataM=[];}
                    if($('#qa_no_laporan_m').val().trim()===''||$('#qa_tanggal_m').val()===''){BootstrapDialog.alert('No. Laporan dan Tanggal Kalibrasi Form M wajib diisi!');return;}
                    var axisM=dataM.filter(function(r){return r&&r.jenis==='AXIS';}),angleM=dataM.filter(function(r){return r&&r.jenis==='ANGLE';});
                    if(axisM.length===0||angleM.length===0){BootstrapDialog.alert('Data AXIS dan ANGLE Form M belum tersedia.');return;}
                    var validM=true;
                    axisM.forEach(function(r){if(!r.std_mm)validM=false;if(r.x_enabled&&(!r.x_left||!r.x_right||!r.x_left_correction||!r.x_right_correction))validM=false;if(r.y_enabled&&(!r.y_front||!r.y_back||!r.y_front_correction||!r.y_back_correction))validM=false;});
                    angleM.forEach(function(r){if(!r.std_degree||r.hasil_degree===''||r.hasil_minute===''||r.hasil_second===''||!r.correction)validM=false;});
                    if(!validM){BootstrapDialog.alert('Lengkapi seluruh hasil AXIS dan ANGLE Form M.');return;}
                    if($('#qa_ketidakpastian').val().trim()===''||$('#qa_confidence').val().trim()===''){BootstrapDialog.alert('Ketidakpastian dan Confidence Level Form M wajib diisi.');return;}
                    if(statusQA===''){BootstrapDialog.alert('Keputusan Form M belum dapat ditentukan.');return;}

                } else if (isFormQ) {
                    var dataQ=[]; try{dataQ=JSON.parse(jsonHasil||'[]');}catch(e){dataQ=[];}
                    if($('#qa_no_laporan_q').val().trim()==='' || $('#qa_tanggal_q').val()===''){BootstrapDialog.alert('No. Laporan dan Tanggal Periksa Form Q wajib diisi!');return;}
                    if(dataQ.length!==1 || !dataQ[0] || !dataQ[0].keputusan){BootstrapDialog.alert('Lengkapi seluruh hasil pemeriksaan Form Q.');return;}
                    if(statusQA===''){BootstrapDialog.alert('Keputusan Form Q belum dapat ditentukan.');return;}

                } else if (isFormP) {
                    var dataP=[]; try{ dataP=JSON.parse(jsonHasil||'[]'); }catch(e){ dataP=[]; }
                    if($('#qa_no_laporan_p').val().trim()==='' || $('#qa_tanggal_p').val()===''){ BootstrapDialog.alert('No. Laporan dan Tanggal Periksa Form P wajib diisi!'); return; }
                    if(dataP.length===0){ BootstrapDialog.alert('Data SK Gauge Form P belum tersedia.'); return; }
                    var validP=true;
                    dataP.forEach(function(item){
                        if(!item || !Array.isArray(item.measurements) || item.measurements.length===0 || !item.keputusan) validP=false;
                        (item.measurements||[]).forEach(function(m){ if(m.actual1==='' || !m.status || (String(m.actual_count||'2')==='2' && m.actual2==='')) validP=false; });
                    });
                    if(!validP){ BootstrapDialog.alert('Lengkapi seluruh nilai Aktual Form P.'); return; }
                    if(statusQA===''){ BootstrapDialog.alert('Keputusan Form P belum dapat ditentukan.'); return; }

                } else if (isFormO) {
                    var dataO=[]; try{ dataO=JSON.parse(jsonHasil||'[]'); }catch(e){ dataO=[]; }
                    if($('#qa_no_laporan_o').val().trim()==='' || $('#qa_tanggal_o').val()===''){ BootstrapDialog.alert('No. Laporan dan Tanggal Periksa Form O wajib diisi!'); return; }
                    if(dataO.length===0){ BootstrapDialog.alert('Data Snap Gauge Form O belum tersedia.'); return; }
                    var validO=true;
                    dataO.forEach(function(r){ if(!r.go_hasil_min || !r.go_hasil_max || !r.notgo_hasil_min || !r.notgo_hasil_max || !r.keputusan) validO=false; });
                    if(!validO){ BootstrapDialog.alert('Lengkapi seluruh hasil Standar Hasil pada Form O.'); return; }
                    if(statusQA===''){ BootstrapDialog.alert('Keputusan Form O belum dapat ditentukan.'); return; }

                } else if (isFormL) {
                    var dataL = [];
                    try { dataL = JSON.parse(jsonHasil || '[]'); } catch(e) { dataL = []; }

                    if ($('#qa_no_laporan_l').val().trim() === '' || $('#qa_tanggal_l').val() === '') {
                        BootstrapDialog.alert('No. Laporan dan Tanggal Kalibrasi Form L wajib diisi!');
                        return;
                    }

                    if (dataL.length === 0 || dataL.some(function(r) {
                        return !r || r.applied_value === '' || r.indicated_value === '' || r.correction === '';
                    })) {
                        BootstrapDialog.alert('Lengkapi seluruh Indicated Value Form L.');
                        return;
                    }

                    if ($('#qa_ketidakpastian').val().trim() === '' || $('#qa_confidence').val().trim() === '') {
                        BootstrapDialog.alert('Ketidakpastian dan Confidence Level Form L wajib diisi.');
                        return;
                    }

                    if (statusQA === '') {
                        BootstrapDialog.alert('Keputusan Form L belum dapat ditentukan.');
                        return;
                    }

                } else if (isFormK) {
                    var dataK=[];try{dataK=JSON.parse(jsonHasil||'[]');}catch(e){dataK=[];} if($('#qa_no_laporan_k').val().trim()===''||$('#qa_tanggal_k').val()===''){BootstrapDialog.alert('No. Laporan dan Tanggal Periksa Form K wajib diisi!');return;} if(dataK.length===0||dataK.some(function(r){return !r||r.nilai_ditentukan===''||r.nilai_pengukuran===''||r.status==='';})){BootstrapDialog.alert('Lengkapi Nilai Pengukuran untuk seluruh titik Form K.');return;} if(statusQA===''){BootstrapDialog.alert('Hasil Form K belum dapat ditentukan.');return;}
                } else if (isFormJ) {
                    var dataJ = [];
                    try { dataJ = JSON.parse(jsonHasil || '[]'); } catch(e) { dataJ = []; }

                    var noLaporanJ = $('#qa_no_laporan_j').val().trim();
                    var tanggalJ = $('#qa_tanggal_j').val();

                    if (noLaporanJ === '' || tanggalJ === '') {
                        BootstrapDialog.alert('No. Laporan dan Tanggal Verifikasi Form J wajib diisi!');
                        return;
                    }

                    if (dataJ.length === 0 || !dataJ[0]) {
                        BootstrapDialog.alert('Data Form J belum tersedia.');
                        return;
                    }

                    if (!dataJ[0].alat_verifikasi) {
                        BootstrapDialog.alert('Alat Verifikasi belum tersedia pada data registrasi.');
                        return;
                    }

                    if (dataJ[0].hasil_verifikasi !== 'O' && dataJ[0].hasil_verifikasi !== 'X') {
                        BootstrapDialog.alert('Hasil Verifikasi Form J wajib dipilih O atau X.');
                        return;
                    }

                    if (statusQA === '') {
                        BootstrapDialog.alert('Hasil Verifikasi Form J wajib dipilih.');
                        return;
                    }
                } else if (isFormH) {
                    var dataH = [];
                    try { dataH = JSON.parse(jsonHasil || '[]'); } catch(e) { dataH = []; }
                    var noLaporanH = $('#qa_no_laporan_h').val().trim();
                    var tanggalH = $('#qa_tanggal_h').val();
                    if (noLaporanH === '' || tanggalH === '') {
                        BootstrapDialog.alert('No. Laporan dan Tanggal Periksa Form H wajib diisi!'); return;
                    }
                    if (dataH.length === 0) {
                        BootstrapDialog.alert('Data pemeriksaan Form H belum tersedia.'); return;
                    }
                    var validH = true;
                    dataH.forEach(function(row) {
                        if (!row.posisi_ukur || !row.std_spesifikasi || !row.std_no_alat) validH = false;
                        if (row.std_out) {
                            if (!row.out || !row.out.dep || !row.out.ten || !row.out.bel) validH = false;
                        }
                        if (row.std_pitch) {
                            if (!row.pitch || !row.pitch.dep || !row.pitch.ten || !row.pitch.bel) validH = false;
                        }
                    });
                    if (!validH) {
                        BootstrapDialog.alert('Lengkapi hasil OUT/PITCH yang mempunyai standar pada Form H.'); return;
                    }
                    if ($('#qa_h_kode_warna').val().trim() === '' || $('#qa_h_putusan').val() === '' || statusQA === '') {
                        BootstrapDialog.alert('Kode Warna dan KEP / Putusan Form H wajib diisi.'); return;
                    }
                } else if (isFormI) {
                    var dataI = [];
                    try { dataI = JSON.parse(jsonHasil || '[]'); } catch(e) { dataI = []; }
                    var noLaporanI = $('#qa_no_laporan_i').val().trim();
                    var tanggalI = $('#qa_tanggal_i').val();
                    if (noLaporanI === '' || tanggalI === '') {
                        BootstrapDialog.alert('No. Laporan dan Tanggal Periksa Form I wajib diisi!'); return;
                    }
                    if (dataI.length === 0) {
                        BootstrapDialog.alert('Data pemeriksaan Form I belum tersedia.'); return;
                    }
                    var i0 = dataI[0] || {};
                    if (!i0.std_spesifikasi || !i0.std_no_alat || !i0.hasil_atas || !i0.hasil_bawah || !i0.kode_warna || !i0.putusan) {
                        BootstrapDialog.alert('Lengkapi Hasil ATAS, Hasil BAWAH, Kode Warna, dan Putusan Form I.'); return;
                    }
                    if (statusQA === '') {
                        BootstrapDialog.alert('Putusan Form I wajib dipilih.'); return;
                    }
                } else if (isFormD) {
                    var dataD = [];
                    try { dataD = JSON.parse(jsonHasil || '[]'); } catch(e) { dataD = []; }
                    var noLaporanD = $('#qa_no_laporan_d').val().trim();
                    var tanggalD = $('#qa_tanggal_d').val();
                    if (noLaporanD === '' || tanggalD === '') {
                        BootstrapDialog.alert('No. Laporan dan Tanggal Periksa Form D wajib diisi!'); return;
                    }
                    if (dataD.length === 0) {
                        BootstrapDialog.alert('Data pemeriksaan Form D belum tersedia.'); return;
                    }
                    var validD = dataD.every(function(row) {
                        return row.unit !== '' && row.setting !== '' && row.standar !== '' && row.hasil !== '' && Number.isFinite(Number(row.hasil));
                    });
                    if (!validD) { BootstrapDialog.alert('Semua kolom Hasil Form D wajib diisi.'); return; }
                    if (statusQA === '') { BootstrapDialog.alert('Keputusan Form D wajib dipilih.'); return; }
                } else {
                    var totalRow = $('.inp-hasil-kalibrasi').length;
                    var isiRow = JSON.parse(jsonHasil || "[]").length;
                    if(isiRow < totalRow || statusQA === '') { BootstrapDialog.alert('Lengkapi semua hasil ukur dan Keputusan!'); return; }
                }

                var btn = $(this); btn.html('<i class="fa fa-spinner fa-spin"></i> Submitting...'); btn.prop('disabled', true);

                var tanggalSubmit = '';
                var noLaporanSubmit = '';
                var formatSubmit = '';
                var foremanSubmit = '';
                var supervisorSubmit = '';

                if (isFormB) {
                    tanggalSubmit = $('#qa_tanggal_b').val(); noLaporanSubmit = $('#qa_no_laporan_b').val().trim(); formatSubmit = 'B'; foremanSubmit = $('#qa_foreman_b').val().trim(); supervisorSubmit = $('#qa_supervisor_b').val().trim();
                } else if (isFormC) {
                    tanggalSubmit = $('#qa_tanggal_c').val(); noLaporanSubmit = $('#qa_no_laporan_c').val().trim(); formatSubmit = 'C'; foremanSubmit = $('#qa_foreman_c').val().trim(); supervisorSubmit = $('#qa_supervisor_c').val().trim();
                } else if (isFormD) {
                    tanggalSubmit = $('#qa_tanggal_d').val(); noLaporanSubmit = $('#qa_no_laporan_d').val().trim(); formatSubmit = 'D'; foremanSubmit = $('#qa_foreman_d').val().trim(); supervisorSubmit = $('#qa_supervisor_d').val().trim();
                } else if (isFormH) {
                    tanggalSubmit = $('#qa_tanggal_h').val(); noLaporanSubmit = $('#qa_no_laporan_h').val().trim(); formatSubmit = 'H'; foremanSubmit = $('#qa_foreman_h').val().trim(); supervisorSubmit = $('#qa_supervisor_h').val().trim();
                } else if (isFormI) {
                    tanggalSubmit = $('#qa_tanggal_i').val(); noLaporanSubmit = $('#qa_no_laporan_i').val().trim(); formatSubmit = 'I'; foremanSubmit = $('#qa_foreman_i').val().trim(); supervisorSubmit = $('#qa_supervisor_i').val().trim();
                } else if (isFormJ) {
                    tanggalSubmit = $('#qa_tanggal_j').val(); noLaporanSubmit = $('#qa_no_laporan_j').val().trim(); formatSubmit = 'J'; foremanSubmit = $('#qa_foreman_j').val().trim(); supervisorSubmit = $('#qa_supervisor_j').val().trim();
                } else if (isFormK) {
                    tanggalSubmit = $('#qa_tanggal_k').val(); noLaporanSubmit = $('#qa_no_laporan_k').val().trim(); formatSubmit = 'K'; foremanSubmit = $('#qa_foreman_k').val().trim(); supervisorSubmit = $('#qa_supervisor_k').val().trim();
                } else if (isFormL) {
                    tanggalSubmit = $('#qa_tanggal_l').val(); noLaporanSubmit = $('#qa_no_laporan_l').val().trim(); formatSubmit = 'L'; foremanSubmit = $('#qa_foreman_l').val().trim(); supervisorSubmit = $('#qa_supervisor_l').val().trim();
                } else if (isFormM) {
                    tanggalSubmit = $('#qa_tanggal_m').val(); noLaporanSubmit = $('#qa_no_laporan_m').val().trim(); formatSubmit = 'M'; foremanSubmit = $('#qa_foreman_m').val().trim(); supervisorSubmit = $('#qa_supervisor_m').val().trim();
                } else if (isFormO) {
                    tanggalSubmit = $('#qa_tanggal_o').val(); noLaporanSubmit = $('#qa_no_laporan_o').val().trim(); formatSubmit = 'O'; foremanSubmit = $('#qa_foreman_o').val().trim(); supervisorSubmit = $('#qa_supervisor_o').val().trim();
                } else if (isFormP) {
                    tanggalSubmit = $('#qa_tanggal_p').val(); noLaporanSubmit = $('#qa_no_laporan_p').val().trim(); formatSubmit = 'P'; foremanSubmit = $('#qa_foreman_p').val().trim(); supervisorSubmit = $('#qa_supervisor_p').val().trim();
                } else if (isFormQ) {
                    tanggalSubmit = $('#qa_tanggal_q').val(); noLaporanSubmit = $('#qa_no_laporan_q').val().trim(); formatSubmit = 'Q'; foremanSubmit = $('#qa_foreman_q').val().trim(); supervisorSubmit = $('#qa_supervisor_q').val().trim();
                }

                $.ajax({
                    url: 'proses_qa.php?action=simpan_qa', type: 'POST', dataType: 'json',
                    data: { 
                        no_part: noAlat, status_qa: statusQA, hasil_json: jsonHasil, 
                        suhu: $('#qa_suhu').val(), kelembapan: $('#qa_kelembapan').val(), ketidakpastian: $('#qa_ketidakpastian').val(),
                        confidence: $('#qa_confidence').val(), keterangan: $('#qa_keterangan').val(),
                        tanggal_periksa: tanggalSubmit,
                        no_laporan: noLaporanSubmit,
                        format_laporan: formatSubmit,
                        foreman: foremanSubmit,
                        supervisor: supervisorSubmit
                    },
                    success: function(res) {
                        btn.html('Submit Hasil QA'); btn.prop('disabled', false);
                        if(res.status === 'success') {
                            if (isFormB) {
                                var dataCetakB = res.laporan_b;
                                sessionStorage.setItem('cetak_data', JSON.stringify(dataCetakB));
                            }
                            if (isFormC) {
                                var dataCetakC = res.laporan_c || {
                                    format: 'C',
                                    no_laporan: $('#qa_no_laporan_c').val().trim(),
                                    no_part: noAlat,
                                    nama_part: $('#qa_nama_alat').val(),
                                    spesifikasi: (window.qaCurrentAlat && window.qaCurrentAlat.spesifikasi) || '',
                                    resolusi: (window.qaCurrentAlat && window.qaCurrentAlat.resolusi) || '',
                                    seksi_pemilik: (window.qaCurrentAlat && window.qaCurrentAlat.seksi_pemilik) || '',
                                    metode: $('#qa_metode').val(),
                                    standar_uji: $('#qa_standar_uji').val(),
                                    traceability: $('#qa_trace').val(),
                                    toleransi: $('#qa_toleransi_value').val(),
                                    suhu: $('#qa_suhu').val(),
                                    kelembapan: $('#qa_kelembapan').val(),
                                    tanggal_periksa: $('#qa_tanggal_c').val(),
                                    jadwal_berikutnya: res.jadwal_selanjutnya || '',
                                    petugas: $('#qa_petugas').val(),
                                    foreman: $('#qa_foreman_c').val(),
                                    supervisor: $('#qa_supervisor_c').val(),
                                    keterangan: $('#qa_keterangan').val(),
                                    keputusan: statusQA,
                                    hasil_array: JSON.parse(jsonHasil || '[]')
                                };
                                sessionStorage.setItem('cetak_data', JSON.stringify(dataCetakC));
                            }
                            if (isFormD) {
                                var dataCetakD = res.laporan_d || {
                                    format: 'D',
                                    no_laporan: $('#qa_no_laporan_d').val().trim(),
                                    no_part: noAlat,
                                    nama_part: $('#qa_nama_alat').val(),
                                    spesifikasi: (window.qaCurrentAlat && window.qaCurrentAlat.spesifikasi) || '',
                                    resolusi: (window.qaCurrentAlat && window.qaCurrentAlat.resolusi) || '',
                                    seksi_pemilik: (window.qaCurrentAlat && window.qaCurrentAlat.seksi_pemilik) || '',
                                    metode: $('#qa_metode').val(),
                                    standar_uji: $('#qa_standar_uji').val(),
                                    traceability: $('#qa_trace').val(),
                                    toleransi: $('#qa_toleransi_value').val(),
                                    suhu: $('#qa_suhu').val(),
                                    kelembapan: $('#qa_kelembapan').val(),
                                    tanggal_periksa: $('#qa_tanggal_d').val(),
                                    jadwal_berikutnya: res.jadwal_selanjutnya || '',
                                    petugas: $('#qa_petugas').val(),
                                    foreman: $('#qa_foreman_d').val(),
                                    supervisor: $('#qa_supervisor_d').val(),
                                    keterangan: $('#qa_keterangan').val(),
                                    keputusan: statusQA,
                                    hasil_array: JSON.parse(jsonHasil || '[]')
                                };
                                sessionStorage.setItem('cetak_data', JSON.stringify(dataCetakD));
                            }
                            if (isFormH) {
                                var dataCetakH = res.laporan_h || {
                                    format: 'H',
                                    no_laporan: $('#qa_no_laporan_h').val().trim(),
                                    no_part: noAlat,
                                    nama_part: $('#qa_nama_alat').val(),
                                    spesifikasi: (window.qaCurrentAlat && window.qaCurrentAlat.spesifikasi) || '',
                                    seksi_pemilik: (window.qaCurrentAlat && window.qaCurrentAlat.seksi_pemilik) || '',
                                    metode: $('#qa_metode').val(),
                                    standar_uji: $('#qa_standar_uji').val(),
                                    traceability: $('#qa_trace').val(),
                                    suhu: $('#qa_suhu').val(),
                                    kelembapan: $('#qa_kelembapan').val(),
                                    tanggal_periksa: $('#qa_tanggal_h').val(),
                                    jadwal_berikutnya: res.jadwal_selanjutnya || '',
                                    petugas: $('#qa_petugas').val(),
                                    foreman: $('#qa_foreman_h').val(),
                                    supervisor: $('#qa_supervisor_h').val(),
                                    keterangan: $('#qa_keterangan').val(),
                                    keputusan: statusQA,
                                    hasil_array: JSON.parse(jsonHasil || '[]')
                                };
                                sessionStorage.setItem('cetak_data', JSON.stringify(dataCetakH));
                            }
                            if (isFormI) {
                                var dataCetakI = res.laporan_i || {
                                    format: 'I',
                                    no_laporan: $('#qa_no_laporan_i').val().trim(),
                                    no_part: noAlat,
                                    nama_part: $('#qa_nama_alat').val(),
                                    spesifikasi: (window.qaCurrentAlat && window.qaCurrentAlat.spesifikasi) || '',
                                    seksi_pemilik: (window.qaCurrentAlat && window.qaCurrentAlat.seksi_pemilik) || '',
                                    metode: $('#qa_metode').val(),
                                    standar_uji: $('#qa_standar_uji').val(),
                                    traceability: $('#qa_trace').val(),
                                    suhu: $('#qa_suhu').val(),
                                    kelembapan: $('#qa_kelembapan').val(),
                                    tanggal_periksa: $('#qa_tanggal_i').val(),
                                    jadwal_berikutnya: res.jadwal_selanjutnya || '',
                                    petugas: $('#qa_petugas').val(),
                                    foreman: $('#qa_foreman_i').val(),
                                    supervisor: $('#qa_supervisor_i').val(),
                                    keterangan: $('#qa_keterangan').val(),
                                    keputusan: statusQA,
                                    hasil_array: JSON.parse(jsonHasil || '[]')
                                };
                                sessionStorage.setItem('cetak_data', JSON.stringify(dataCetakI));
                            }

                            if (isFormJ) {
                                sessionStorage.setItem('cetak_data', JSON.stringify(res.laporan_j || {
                                    format:'J', no_laporan:$('#qa_no_laporan_j').val().trim(), no_part:noAlat,
                                    nama_part:$('#qa_nama_alat').val(), spesifikasi:(window.qaCurrentAlat&&window.qaCurrentAlat.spesifikasi)||'',
                                    seksi_pemilik:(window.qaCurrentAlat&&window.qaCurrentAlat.seksi_pemilik)||'', metode:$('#qa_metode').val(),
                                    alat_verifikasi:$('#qa_j_alat_verifikasi').val(), tanggal_periksa:$('#qa_tanggal_j').val(),
                                    jadwal_berikutnya:res.jadwal_selanjutnya||'', petugas:$('#qa_petugas').val(),
                                    foreman:$('#qa_foreman_j').val(), supervisor:$('#qa_supervisor_j').val(),
                                    keterangan:$('#qa_keterangan').val(), keputusan:statusQA, hasil_array:JSON.parse(jsonHasil||'[]')
                                }));
                            }
                            if (isFormK) {
                                sessionStorage.setItem('cetak_data', JSON.stringify(res.laporan_k || {
                                    format:'K', no_laporan:$('#qa_no_laporan_k').val().trim(), no_part:noAlat,
                                    nama_part:$('#qa_nama_alat').val(), spesifikasi:(window.qaCurrentAlat&&(window.qaCurrentAlat.spesifikasi||window.qaCurrentAlat.ukuran))||'',
                                    resolusi:(window.qaCurrentAlat&&window.qaCurrentAlat.resolusi)||'', seksi_pemilik:(window.qaCurrentAlat&&window.qaCurrentAlat.seksi_pemilik)||'',
                                    metode:$('#qa_metode').val(), standar_uji:$('#qa_standar_uji').val(), traceability:$('#qa_trace').val(), toleransi:$('#qa_toleransi_value').val(),
                                    suhu:$('#qa_suhu').val(), kelembapan:$('#qa_kelembapan').val(), tanggal_periksa:$('#qa_tanggal_k').val(), jadwal_berikutnya:res.jadwal_selanjutnya||'',
                                    petugas:$('#qa_petugas').val(), foreman:$('#qa_foreman_k').val(), supervisor:$('#qa_supervisor_k').val(), keterangan:$('#qa_keterangan').val(),
                                    keputusan:statusQA, hasil_array:JSON.parse(jsonHasil||'[]')
                                }));
                            }
                            if (isFormL) {
                                sessionStorage.setItem('cetak_data', JSON.stringify(res.laporan_l || {
                                    format:'L', no_laporan:$('#qa_no_laporan_l').val().trim(), no_part:noAlat, nama_part:$('#qa_nama_alat').val(),
                                    spesifikasi:(window.qaCurrentAlat&&(window.qaCurrentAlat.spesifikasi||window.qaCurrentAlat.ukuran))||'', resolusi:(window.qaCurrentAlat&&window.qaCurrentAlat.resolusi)||'',
                                    seksi_pemilik:(window.qaCurrentAlat&&window.qaCurrentAlat.seksi_pemilik)||'', metode:$('#qa_metode').val(), standar_uji:$('#qa_standar_uji').val(),
                                    std_no_alat:$('#qa_l_std_no').val(), traceability:$('#qa_trace').val(), toleransi:$('#qa_toleransi_value').val(), suhu:$('#qa_suhu').val(), kelembapan:$('#qa_kelembapan').val(),
                                    ketidakpastian:$('#qa_ketidakpastian').val(), confidence:$('#qa_confidence').val(), tanggal_periksa:$('#qa_tanggal_l').val(), jadwal_berikutnya:res.jadwal_selanjutnya||'',
                                    petugas:$('#qa_petugas').val(), foreman:$('#qa_foreman_l').val(), supervisor:$('#qa_supervisor_l').val(), keterangan:$('#qa_keterangan').val(),
                                    keputusan:statusQA, hasil_array:JSON.parse(jsonHasil||'[]')
                                }));
                            }
                            if (isFormM) {
                                sessionStorage.setItem('cetak_data', JSON.stringify(res.laporan_m || {
                                    format:'M', no_laporan:$('#qa_no_laporan_m').val().trim(), no_part:noAlat, nama_part:$('#qa_nama_alat').val(),
                                    spesifikasi:(window.qaCurrentAlat&&(window.qaCurrentAlat.spesifikasi||window.qaCurrentAlat.ukuran))||'', resolusi:(window.qaCurrentAlat&&window.qaCurrentAlat.resolusi)||'',
                                    seksi_pemilik:(window.qaCurrentAlat&&window.qaCurrentAlat.seksi_pemilik)||'', metode:$('#qa_metode').val(), standar_uji:$('#qa_standar_uji').val(),
                                    standar_uji_sudut:$('#qa_m_standar_sudut').val(), traceability:$('#qa_trace').val(), toleransi_axis:$('#qa_toleransi_value').val(), toleransi_sudut:$('#qa_m_tol_sudut').val(),
                                    suhu:$('#qa_suhu').val(), kelembapan:$('#qa_kelembapan').val(), ketidakpastian:$('#qa_ketidakpastian').val(), confidence:$('#qa_confidence').val(),
                                    tanggal_periksa:$('#qa_tanggal_m').val(), jadwal_berikutnya:res.jadwal_selanjutnya||'', petugas:$('#qa_petugas').val(), foreman:$('#qa_foreman_m').val(), supervisor:$('#qa_supervisor_m').val(),
                                    keterangan:$('#qa_keterangan').val(), keputusan:statusQA, hasil_array:JSON.parse(jsonHasil||'[]')
                                }));
                            }
                            if (isFormO) {
                                sessionStorage.setItem('cetak_data', JSON.stringify(res.laporan_o || {
                                    format:'O', no_laporan:$('#qa_no_laporan_o').val().trim(), nama_alat:$('#qa_nama_alat').val(),
                                    seksi:(window.qaCurrentAlat&&window.qaCurrentAlat.seksi_pemilik)||'', petugas:$('#qa_petugas').val(), metode:$('#qa_metode').val(),
                                    standar_uji:$('#qa_o_standar_uji').val(), traceability:$('#qa_o_traceability').val(), suhu:$('#qa_suhu').val(), kelembapan:$('#qa_kelembapan').val(),
                                    tanggal_periksa:$('#qa_tanggal_o').val(), jadwal_berikutnya:res.jadwal_selanjutnya||'', foreman:$('#qa_foreman_o').val().trim(), supervisor:$('#qa_supervisor_o').val().trim(),
                                    keputusan:statusQA, hasil_array:JSON.parse(jsonHasil||'[]'), halaman:'1 / 1', keterangan:$('#qa_keterangan').val()
                                }));
                            }
                            if (isFormP) {
                                sessionStorage.setItem('cetak_data', JSON.stringify(res.laporan_p || {
                                    format:'P', no_laporan:$('#qa_no_laporan_p').val().trim(), nama_alat:$('#qa_nama_alat').val(),
                                    seksi:(window.qaCurrentAlat&&window.qaCurrentAlat.seksi_pemilik)||'', petugas:$('#qa_petugas').val(), metode:$('#qa_metode').val(),
                                    standar_uji:$('#qa_standar_uji').val(), traceability:$('#qa_trace').val(), suhu:$('#qa_suhu').val(), kelembapan:$('#qa_kelembapan').val(),
                                    tanggal_periksa:$('#qa_tanggal_p').val(), jadwal_berikutnya:res.jadwal_selanjutnya||hitungTanggalBerikutP(), foreman:$('#qa_foreman_p').val().trim(), supervisor:$('#qa_supervisor_p').val().trim(),
                                    keputusan:statusQA, hasil_array:JSON.parse(jsonHasil||'[]'), halaman:'1 / 1', keterangan:$('#qa_keterangan').val()
                                }));
                            }
                            if (isFormQ) {
                                sessionStorage.setItem('cetak_data', JSON.stringify(res.laporan_q || {
                                    format:'Q', no_laporan:$('#qa_no_laporan_q').val().trim(), no_part:noAlat, nama_alat:$('#qa_nama_alat').val(),
                                    spesifikasi:(window.qaCurrentAlat&&window.qaCurrentAlat.spesifikasi)||'', seksi:(window.qaCurrentAlat&&window.qaCurrentAlat.seksi_pemilik)||'',
                                    petugas:$('#qa_petugas').val(), metode:$('#qa_metode').val(), standar_uji:$('#qa_standar_uji').val(), traceability:$('#qa_trace').val(),
                                    suhu:$('#qa_suhu').val(), kelembapan:$('#qa_kelembapan').val(), tanggal_periksa:$('#qa_tanggal_q').val(), jadwal_berikutnya:res.jadwal_selanjutnya||qNextDate(),
                                    foreman:$('#qa_foreman_q').val().trim(), supervisor:$('#qa_supervisor_q').val().trim(), keputusan:statusQA,
                                    hasil_array:JSON.parse(jsonHasil||'[]'), halaman:'1 / 1', keterangan:$('#qa_keterangan').val()
                                }));
                            }

                            if (!isFormB && !isFormC && !isFormD && !isFormH && !isFormI && !isFormJ && !isFormK && !isFormL && !isFormM && !isFormO && !isFormP && !isFormQ) {
                                // FORMAT DASAR / LEGACY (E, F, G, N, R, dan KUSTOM) - sebelumnya TIDAK PERNAH
                                // menulis sessionStorage 'cetak_data', jadi cetak_laporan.php selalu memakai
                                // data cetak terakhir yang tersimpan (sering kali data Format A sebelumnya).
                                var formatAlatSekarang = (window.qaCurrentAlat && window.qaCurrentAlat.format_laporan)
                                    ? String(window.qaCurrentAlat.format_laporan).toUpperCase()
                                    : '';
                                sessionStorage.setItem('cetak_data', JSON.stringify({
                                    format: formatAlatSekarang,
                                    no_part: noAlat,
                                    nama_part: $('#qa_nama_alat').val(),
                                    spesifikasi: (window.qaCurrentAlat && window.qaCurrentAlat.spesifikasi) || '',
                                    resolusi: (window.qaCurrentAlat && window.qaCurrentAlat.resolusi) || '',
                                    seksi_pemilik: (window.qaCurrentAlat && window.qaCurrentAlat.seksi_pemilik) || '',
                                    satuan: (window.qaCurrentAlat && window.qaCurrentAlat.satuan) || '',
                                    metode: $('#qa_metode').val(),
                                    standar_uji: $('#qa_standar_uji').val(),
                                    traceability: $('#qa_trace').val(),
                                    toleransi: $('#qa_toleransi_value').val(),
                                    suhu: $('#qa_suhu').val(),
                                    kelembapan: $('#qa_kelembapan').val(),
                                    ketidakpastian: $('#qa_ketidakpastian').val(),
                                    confidence_level: $('#qa_confidence').val(),
                                    tanggal_periksa: tanggalSubmit || new Date().toISOString().slice(0,10),
                                    jadwal_berikutnya: res.jadwal_selanjutnya || '',
                                    petugas: $('#qa_petugas').val(),
                                    keterangan: $('#qa_keterangan').val(),
                                    keputusan: statusQA,
                                    hasil_array: JSON.parse(jsonHasil || '[]')
                                }));
                            }

                            BootstrapDialog.confirm({
                                title: 'SUKSES!', type: BootstrapDialog.TYPE_SUCCESS,
                                message: 'Laporan berhasil disimpan ke Database!<br><br><b>Cetak Laporan?</b>',
                                btnCancelLabel: 'Tidak',
                                btnOKLabel: 'Ya, Cetak',
                                callback: function(result) {
                                    if (result) { window.location.href = 'cetak_laporan.php'; }
                                    else { $('.hidden-form-section').slideUp(); $('#nomor_alat').prop('disabled', false).val('').focus(); }
                                }
                            });
                        } else { BootstrapDialog.alert('Gagal: ' + res.message); }
                    }, error: function() { btn.html('Submit Hasil QA'); btn.prop('disabled', false); BootstrapDialog.alert('Error Submit!'); }
                });
            });

            // ================= SIMPAN ALAT BARU =================
            $('#btn-simpan-alat').click(function() {
                var format = $('#reg_pilih_format').val();
                var regNo = $('#reg_nomor_alat').val().toUpperCase();
                var arrStandar = []; var validToleransiFormatN = true;

                if(format === 'A') {
                    var aGoStdNo = $('#reg_a_go_std_no').val().trim();
                    var aGoStdSpek = $('#reg_a_go_std_spek').val().trim();
                    var aNgStdNo = $('#reg_a_notgo_std_no').val().trim();
                    var aNgStdSpek = $('#reg_a_notgo_std_spek').val().trim();
                    var aMetode = $('#reg_a_metode').val().trim();
                    var aSeksi = $('#reg_seksi').val();
                    var aNama = $('#reg_nama_alat').val().trim();
                    var aSpek = $('#reg_a_spesifikasi').val().trim();
                    var aPeriode = $('#reg_periode').val().trim();

                    var missingA = [];
                    if(regNo === '') missingA.push('No. Alat');
                    if(aNama === '') missingA.push('Nama Alat');
                    if(aSpek === '') missingA.push('Spesifikasi');
                    if(aSeksi === '' || aSeksi === 'tambah') missingA.push('Seksi Pemilik');
                    if(aPeriode === '') missingA.push('Periode Kalibrasi');
                    if(aMetode === '') missingA.push('Metode Kalibrasi');
                    if(aGoStdNo === '') missingA.push('No. Alat Standar GO');
                    if(aGoStdSpek === '') missingA.push('Spesifikasi Alat Standar GO');
                    if(aNgStdNo === '') missingA.push('No. Alat Standar NOT GO');
                    if(aNgStdSpek === '') missingA.push('Spesifikasi Alat Standar NOT GO');

                    if(missingA.length > 0) {
                        BootstrapDialog.alert('<b>Field FORM A belum lengkap:</b><br>' + missingA.map(function(item){ return '• ' + item; }).join('<br>'));
                        return;
                    }

                    $('#reg_spesifikasi').val(aSpek);
                    $('#reg_metode').val(aMetode);
                    $('#reg_trace').val($('#reg_a_traceability').val().trim());
                    $('#reg_standar_uji').val((aGoStdSpek || '') + ((aGoStdSpek && aNgStdSpek) ? ' | ' : '') + (aNgStdSpek || ''));
                    $('#reg_satuan').val('');

                    arrStandar = [{
                        format: 'A',
                        alat_standar: {
                            no_alat: aGoStdNo || aNgStdNo,
                            spesifikasi: aGoStdSpek || aNgStdSpek,
                            traceability: $('#reg_a_traceability').val().trim() || '///',
                            go: { no_alat: aGoStdNo, spesifikasi: aGoStdSpek },
                            not_go: { no_alat: aNgStdNo, spesifikasi: aNgStdSpek }
                        },
                        go: {
                            alat_standar: { no_alat: aGoStdNo, spesifikasi: aGoStdSpek },
                            std_out: $('#reg_a_go_std_out').val().trim() || '///',
                            tol_out: $('#reg_a_go_tol_out').val().trim() || '///',
                            range_out: $('#reg_a_go_range_out').val().trim() || '///',
                            std_pitch: $('#reg_a_go_std_pitch').val().trim() || '///',
                            tol_pitch: $('#reg_a_go_tol_pitch').val().trim() || '///',
                            range_pitch: $('#reg_a_go_range_pitch').val().trim() || '///'
                        },
                        not_go: {
                            alat_standar: { no_alat: aNgStdNo, spesifikasi: aNgStdSpek },
                            std_out: $('#reg_a_notgo_std_out').val().trim() || '///',
                            tol_out: $('#reg_a_notgo_tol_out').val().trim() || '///',
                            range_out: $('#reg_a_notgo_range_out').val().trim() || '///',
                            std_pitch: $('#reg_a_notgo_std_pitch').val().trim() || '///',
                            tol_pitch: $('#reg_a_notgo_tol_pitch').val().trim() || '///',
                            range_pitch: $('#reg_a_notgo_range_pitch').val().trim() || '///'
                        }
                    }];
                } else if(format === 'B') {
                    var namaStandarB = $('#reg_b_standar_nama').val().trim();
                    var noStandarB = $('#reg_b_standar_no').val().trim().toUpperCase();

                    if (!$('#reg_metode').val().trim() || namaStandarB === '' || noStandarB === '' || !$('#reg_trace').val().trim()) {
                        BootstrapDialog.alert('Metode Kalibrasi, Nama Alat Standar, No. Alat Standar, dan Traceability wajib diisi.');
                        return;
                    }

                    // Tetap gunakan kolom standar_uji yang sudah ada di database.
                    // Contoh hasil: TORQUE ANALYZER | QA-TQ-001
                    $('#reg_standar_uji').val(namaStandarB + ' | ' + noStandarB);

                    $('#body_reg_standar .reg-b-row').each(function() {
                        var tekanan = $(this).find('.inp-reg-b-tekanan').val().trim();
                        var handle = $(this).find('.inp-reg-b-handle').val().trim();
                        var baut = $(this).find('.inp-reg-b-baut').val().trim();
                        var min = $(this).find('.inp-reg-b-min').val().trim();
                        var max = $(this).find('.inp-reg-b-max').val().trim();
                        {
                            arrStandar.push({tekanan: tekanan, handle: handle, baut: baut, std_min: min, std_max: max,
                                std1: $(this).find('.inp-reg-b-std1').val().trim(), std2: $(this).find('.inp-reg-b-std2').val().trim(), std3: $(this).find('.inp-reg-b-std3').val().trim()});
                        }
                    });
                    if(arrStandar.length === 0) { BootstrapDialog.alert('Format B wajib memiliki minimal 1 titik pengujian.'); return; }
                    var validB = true;
                    arrStandar.forEach(function(item) {
                        if(item.tekanan === '' || item.handle === '' || item.baut === '' || item.std_min === '' || item.std_max === '') validB = false;
                        if (['std1','std2','std3'].some(function(key) { return item[key] === '' || !Number.isFinite(Number(item[key])); })) validB = false;
                        if(!Number.isFinite(Number(item.std_min)) || !Number.isFinite(Number(item.std_max)) || Number(item.std_min)>Number(item.std_max)) validB = false;
                    });
                    if(!validB) { BootstrapDialog.alert('Lengkapi semua kolom Form B dan pastikan Std. Min tidak lebih besar dari Std. Max.'); return; }
                } else if(format === 'C') {
                    $('#reg_satuan').val('°C');
                    var validTitikC = true;
                    $('#body_reg_standar .reg-c-row').each(function() {
                        var setting = $(this).find('.inp-reg-c-setting').val().trim();
                        var alatStd = $(this).find('.inp-reg-c-alat-std').val().trim();

                        if (setting !== '' || alatStd !== '') {
                            if (setting === '' || alatStd === '' || !Number.isFinite(Number(setting)) || !Number.isFinite(Number(alatStd))) {
                                validTitikC = false;
                            }
                            arrStandar.push({ setting: setting, alat_std: alatStd });
                        }
                    });

                    var missingC = [];
                    if(regNo === '') missingC.push('No. Alat');
                    if($('#reg_nama_alat').val().trim() === '') missingC.push('Nama Alat');
                    if($('#reg_spesifikasi').val().trim() === '') missingC.push('Spesifikasi');
                    if($('#reg_seksi').val() === '' || $('#reg_seksi').val() === 'tambah') missingC.push('Seksi Pemilik');
                    if($('#reg_periode').val().trim() === '' || Number($('#reg_periode').val()) <= 0) missingC.push('Periode Kalibrasi');
                    if($('#reg_resolusi').val().trim() === '') missingC.push('Resolusi');
                    if($('#reg_toleransi').val().trim() === '') missingC.push('Standar Toleransi');
                    if($('#reg_metode').val().trim() === '') missingC.push('Metode');
                    if($('#reg_standar_uji').val().trim() === '') missingC.push('Standar Uji');
                    if($('#reg_trace').val().trim() === '') missingC.push('Traceability');
                    if(arrStandar.length === 0) missingC.push('Titik Pemeriksaan (Setting SV + Alat STD)');
                    if(!validTitikC) missingC.push('Setting (SV) dan Alat STD pada setiap baris harus diisi angka');

                    if(missingC.length > 0) {
                        BootstrapDialog.alert('<b>Field FORM C belum lengkap:</b><br>' + missingC.map(function(item){ return '• ' + item; }).join('<br>'));
                        return;
                    }
                } else if(format === 'H') {
                    var validTitikH = true;
                    $('#body_reg_standar .reg-h-row').each(function() {
                        var posisi = $(this).find('.inp-reg-h-posisi').val().trim();
                        var stdSpek = $(this).find('.inp-reg-h-std-spek').val().trim();
                        var stdNo = $(this).find('.inp-reg-h-std-no').val().trim().toUpperCase();
                        var stdOut = $(this).find('.inp-reg-h-out-std').val().trim();
                        var tolOut = $(this).find('.inp-reg-h-out-tol').val().trim();
                        var rangeOut = $(this).find('.inp-reg-h-out-range').val().trim();
                        var stdPitch = $(this).find('.inp-reg-h-pitch-std').val().trim();
                        var tolPitch = $(this).find('.inp-reg-h-pitch-tol').val().trim();
                        var rangePitch = $(this).find('.inp-reg-h-pitch-range').val().trim();

                        var adaIsi = stdSpek || stdNo || stdOut || stdPitch || tolOut || tolPitch || rangeOut || rangePitch;
                        if (adaIsi) {
                            if (posisi === '' || stdSpek === '' || stdNo === '' || (stdOut === '' && stdPitch === '')) validTitikH = false;
                            arrStandar.push({
                                posisi_ukur: posisi,
                                std_spesifikasi: stdSpek,
                                std_no_alat: stdNo,
                                std_out: stdOut,
                                tol_out: tolOut,
                                range_out: rangeOut,
                                std_pitch: stdPitch,
                                tol_pitch: tolPitch,
                                range_pitch: rangePitch
                            });
                        }
                    });

                    var missingH = [];
                    if(regNo === '') missingH.push('No. Alat');
                    if($('#reg_nama_alat').val().trim() === '') missingH.push('Nama Alat');
                    if($('#reg_spesifikasi').val().trim() === '') missingH.push('Spesifikasi Alat yang Diperiksa');
                    if($('#reg_seksi').val() === '' || $('#reg_seksi').val() === 'tambah') missingH.push('Seksi Pemilik');
                    if($('#reg_periode').val().trim() === '' || Number($('#reg_periode').val()) <= 0) missingH.push('Periode Kalibrasi');
                    if($('#reg_metode').val().trim() === '') missingH.push('Metode');
                    if($('#reg_standar_uji').val().trim() === '') missingH.push('Alat Standar');
                    if($('#reg_trace').val().trim() === '') missingH.push('Traceability');
                    if(arrStandar.length === 0) missingH.push('Minimal 1 Posisi Ukur / Standar');
                    if(!validTitikH) missingH.push('Setiap baris harus punya Posisi, Spesifikasi Standar, No. Standar, dan minimal STD OUT atau STD PITCH');

                    if(missingH.length > 0) {
                        BootstrapDialog.alert('<b>Field FORM H belum lengkap:</b><br>' + missingH.map(function(item){ return '• ' + item; }).join('<br>'));
                        return;
                    }
                } else if(format === 'I') {
                    var validTitikI = true;
                    $('#body_reg_standar .reg-i-row').each(function() {
                        var stdSpek = $(this).find('.inp-reg-i-std-spek').val().trim();
                        var stdNo = $(this).find('.inp-reg-i-std-no').val().trim().toUpperCase();

                        if (stdSpek !== '' || stdNo !== '') {
                            if (stdSpek === '' || stdNo === '') validTitikI = false;
                            arrStandar.push({
                                std_spesifikasi: stdSpek,
                                std_no_alat: stdNo,
                                lokasi: ['ATAS', 'BAWAH']
                            });
                        }
                    });

                    var missingI = [];
                    if(regNo === '') missingI.push('No. Alat');
                    if($('#reg_nama_alat').val().trim() === '') missingI.push('Nama Alat');
                    if($('#reg_spesifikasi').val().trim() === '') missingI.push('Spesifikasi');
                    if($('#reg_seksi').val() === '' || $('#reg_seksi').val() === 'tambah') missingI.push('Seksi Pemilik');
                    if($('#reg_periode').val().trim() === '' || Number($('#reg_periode').val()) <= 0) missingI.push('Periode Kalibrasi');
                    if($('#reg_metode').val().trim() === '') missingI.push('Metode');
                    if($('#reg_standar_uji').val().trim() === '') missingI.push('Alat Standar');
                    if($('#reg_trace').val().trim() === '') missingI.push('Traceability');
                    if(arrStandar.length === 0) missingI.push('Spesifikasi dan No. Alat Standar');
                    if(!validTitikI) missingI.push('Spesifikasi dan No. Alat Standar harus lengkap');

                    if(missingI.length > 0) {
                        BootstrapDialog.alert('<b>Field FORM I belum lengkap:</b><br>' + missingI.map(function(item){ return '• ' + item; }).join('<br>'));
                        return;
                    }
                } else if(format === 'J') {
                    var missingJ = [];

                    if(regNo === '') missingJ.push('No. Alat');
                    if($('#reg_nama_alat').val().trim() === '') missingJ.push('Nama Alat');
                    if($('#reg_spesifikasi').val().trim() === '') missingJ.push('Spesifikasi Alat yang Diverifikasi');
                    if($('#reg_seksi').val() === '' || $('#reg_seksi').val() === 'tambah') missingJ.push('Seksi');
                    if($('#reg_periode').val().trim() === '' || Number($('#reg_periode').val()) <= 0) missingJ.push('Periode Verifikasi');
                    if($('#reg_metode').val().trim() === '') missingJ.push('Metode');
                    if($('#reg_standar_uji').val().trim() === '') missingJ.push('Alat Verifikasi');

                    if(missingJ.length > 0) {
                        BootstrapDialog.alert('<b>Field FORM J belum lengkap:</b><br>' + missingJ.map(function(item){ return '• ' + item; }).join('<br>'));
                        return;
                    }

                    arrStandar = [{
                        alat_verifikasi: $('#reg_standar_uji').val().trim()
                    }];
                } else if(format === 'K') {
                    var validTitikK=true; $('#body_reg_standar .reg-k-row').each(function(){var nilai=$(this).find('.inp-reg-k-nilai').val().trim();if(nilai!==''){if(!Number.isFinite(Number(nilai)))validTitikK=false;arrStandar.push({nilai_ditentukan:nilai});}}); var missingK=[];if(regNo==='')missingK.push('Nomor Mesin');if($('#reg_nama_alat').val().trim()==='')missingK.push('Nama Alat');if($('#reg_spesifikasi').val().trim()==='')missingK.push('Spesifikasi');if($('#reg_seksi').val()===''||$('#reg_seksi').val()==='tambah')missingK.push('Seksi');if($('#reg_periode').val().trim()===''||Number($('#reg_periode').val())<=0)missingK.push('Periode Kalibrasi');if($('#reg_resolusi').val().trim()==='')missingK.push('Resolusi');if($('#reg_toleransi').val().trim()===''||Number($('#reg_toleransi').val())<=0)missingK.push('Standar Toleransi (%)');if($('#reg_metode').val().trim()==='')missingK.push('Metode');if($('#reg_standar_uji').val().trim()==='')missingK.push('Standar Uji');if($('#reg_trace').val().trim()==='')missingK.push('Traceability');if(arrStandar.length===0)missingK.push('Nilai yang Ditentukan');if(!validTitikK)missingK.push('Nilai yang Ditentukan harus berupa angka');if(missingK.length>0){BootstrapDialog.alert('<b>Field FORM K belum lengkap:</b><br>'+missingK.map(function(item){return '• '+item;}).join('<br>'));return;}$('#reg_satuan').val('Volt');
                } else if(format === 'M') {
                    var stdSudutM=$('#reg_m_standar_sudut').val().trim(),tolSudutM=$('#reg_m_tol_sudut').val().trim(),validM=true;
                    $('#reg_m_axis_body .reg-m-axis-row').each(function(){
                        var std=$(this).find('.inp-reg-m-axis-std').val().trim(),mode=$(this).find('.inp-reg-m-axis-mode').val();
                        if(std!==''){if(!Number.isFinite(Number(std))||!['XY','X','Y'].includes(mode))validM=false;arrStandar.push({jenis:'AXIS',std_mm:std,x_enabled:(mode==='XY'||mode==='X'),y_enabled:(mode==='XY'||mode==='Y'),std_sudut:stdSudutM,tol_sudut:tolSudutM});}
                    });
                    $('#reg_m_angle_body .reg-m-angle-row').each(function(){
                        var std=$(this).find('.inp-reg-m-angle-std').val().trim();
                        if(std!==''){if(!Number.isFinite(Number(std)))validM=false;arrStandar.push({jenis:'ANGLE',std_degree:std,std_sudut:stdSudutM,tol_sudut:tolSudutM});}
                    });
                    var ac=arrStandar.filter(function(r){return r.jenis==='AXIS';}).length,angc=arrStandar.filter(function(r){return r.jenis==='ANGLE';}).length,missingM=[];
                    if(regNo==='')missingM.push('Nomor Alat');if($('#reg_nama_alat').val().trim()==='')missingM.push('Nama Alat');if($('#reg_spesifikasi').val().trim()==='')missingM.push('Spesifikasi');
                    if($('#reg_seksi').val()===''||$('#reg_seksi').val()==='tambah')missingM.push('Seksi');if($('#reg_periode').val().trim()===''||Number($('#reg_periode').val())<=0)missingM.push('Periode Kalibrasi');
                    if($('#reg_resolusi').val().trim()==='')missingM.push('Resolusi');if($('#reg_toleransi').val().trim()===''||Number($('#reg_toleransi').val())<=0)missingM.push('Standar Toleransi Axis (mm)');
                    if($('#reg_metode').val().trim()==='')missingM.push('Metode');if($('#reg_standar_uji').val().trim()==='')missingM.push('Standar Uji Axis');if(stdSudutM==='')missingM.push('Standar Uji Sudut');
                    if(tolSudutM===''||Number(tolSudutM)<=0)missingM.push("Standar Toleransi Sudut (menit busur) / contoh 0.1' = 6\"");if($('#reg_trace').val().trim()==='')missingM.push('Traceability');if(ac===0)missingM.push('Titik Axis Accuracy');if(angc===0)missingM.push('Titik Angle Accuracy');if(!validM)missingM.push('Data standar Form M harus valid');
                    if(missingM.length){BootstrapDialog.alert('<b>Field FORM M belum lengkap:</b><br>'+missingM.map(function(i){return '• '+i;}).join('<br>'));return;}
                    $('#reg_satuan').val('mm');

                } else if(format === 'Q') {
                    var subtypeQ=String($('#reg_q_subtype').val()||'').toUpperCase();
                    var qObj={subtype:subtypeQ};
                    var missingQ=[];
                    if(regNo==='')missingQ.push('No. Alat');
                    if($('#reg_nama_alat').val().trim()==='')missingQ.push('Nama Alat');
                    if($('#reg_spesifikasi').val().trim()==='')missingQ.push('Spesifikasi Alat yang Diperiksa');
                    if($('#reg_seksi').val()===''||$('#reg_seksi').val()==='tambah')missingQ.push('Seksi Pemilik');
                    if($('#reg_periode').val().trim()===''||Number($('#reg_periode').val())<=0)missingQ.push('Periode Kalibrasi');
                    if($('#reg_metode').val().trim()==='')missingQ.push('Metode');
                    if($('#reg_standar_uji').val().trim()==='')missingQ.push('Alat Standar');
                    if($('#reg_trace').val().trim()==='')missingQ.push('Traceability');

                    if(subtypeQ==='SERATION'){
                        function getQ(id){return $(id).val().trim();}
                        qObj.out={std_spesifikasi:getQ('#reg_q_out_std_spek'),std_no_alat:getQ('#reg_q_out_std_no').toUpperCase(),std:getQ('#reg_q_out_std'),tol_plus:getQ('#reg_q_out_plus'),tol_minus:getQ('#reg_q_out_minus')};
                        qObj.pitch={std_spesifikasi:getQ('#reg_q_pitch_std_spek'),std_no_alat:getQ('#reg_q_pitch_std_no').toUpperCase(),std:getQ('#reg_q_pitch_std'),tol_plus:getQ('#reg_q_pitch_plus'),tol_minus:getQ('#reg_q_pitch_minus')};
                        ['out','pitch'].forEach(function(k){var r=qObj[k];if(!r.std_spesifikasi||!r.std_no_alat||r.std===''||r.tol_plus===''||r.tol_minus==='')missingQ.push('Data '+k.toUpperCase());else if(!Number.isFinite(Number(r.std))||!Number.isFinite(Number(r.tol_plus))||!Number.isFinite(Number(r.tol_minus)))missingQ.push('Nilai '+k.toUpperCase()+' harus angka');});
                    } else if(subtypeQ==='PLUG'){
                        function getQP(id){return $(id).val().trim();}
                        qObj.std_spesifikasi=getQP('#reg_q_plug_std_spek');qObj.std_no_alat=getQP('#reg_q_plug_std_no').toUpperCase();qObj.no_gambar=getQP('#reg_q_plug_gambar');
                        qObj.go={std:getQP('#reg_q_go_std'),tol_plus:getQP('#reg_q_go_plus'),tol_minus:getQP('#reg_q_go_minus')};
                        qObj.notgo={std:getQP('#reg_q_not_std'),tol_plus:getQP('#reg_q_not_plus'),tol_minus:getQP('#reg_q_not_minus')};
                        if(!qObj.std_spesifikasi||!qObj.std_no_alat||!qObj.no_gambar)missingQ.push('Alat Standar dan No. Gambar');
                        ['go','notgo'].forEach(function(k){var r=qObj[k];if(r.std===''||r.tol_plus===''||r.tol_minus==='')missingQ.push('Data '+k.toUpperCase());else if(!Number.isFinite(Number(r.std))||!Number.isFinite(Number(r.tol_plus))||!Number.isFinite(Number(r.tol_minus)))missingQ.push('Nilai '+k.toUpperCase()+' harus angka');});
                    } else missingQ.push('Jenis Gauge');
                    if(missingQ.length){BootstrapDialog.alert('<b>Field FORM Q belum lengkap:</b><br>'+missingQ.map(function(i){return '• '+i;}).join('<br>'));return;}
                    arrStandar=[qObj]; $('#reg_satuan').val('mm');

                } else if(format === 'P') {
                    var validTitikP=true;
                    $('#reg_p_items .reg-p-item').each(function(){
                        var el=$(this);
                        var item={
                            no_alat:el.find('.p-item-noalat').val().trim().toUpperCase(),
                            type:el.find('.p-item-type').val().trim(),
                            ukuran:el.find('.p-item-ukuran').val().trim(),
                            upart:el.find('.p-item-upart').val().trim(),
                            no_gambar:el.find('.p-item-gambar').val().trim(),
                            measurements:[]
                        };
                        if(!item.no_alat || !item.type || !item.ukuran || !item.no_gambar) validTitikP=false;
                        el.find('.reg-p-measure-row').each(function(){
                            var m={
                                alat_nama:$(this).find('.p-std-name').val().trim(),
                                alat_no:$(this).find('.p-std-no').val().trim().toUpperCase(),
                                std_label:$(this).find('.p-std-label').val().trim(),
                                min:$(this).find('.p-min').val().trim(),
                                max:$(this).find('.p-max').val().trim(),
                                actual_count:$(this).find('.p-actual-count').val()
                            };
                            if(!m.alat_nama || !m.alat_no || !m.std_label || m.min==='' || m.max==='' || !Number.isFinite(Number(m.min)) || !Number.isFinite(Number(m.max)) || Number(m.min)>Number(m.max)) validTitikP=false;
                            item.measurements.push(m);
                        });
                        if(item.measurements.length===0) validTitikP=false;
                        arrStandar.push(item);
                    });
                    var missingP=[];
                    if(regNo==='') missingP.push('Kode Master / Group');
                    if($('#reg_nama_alat').val().trim()==='') missingP.push('Nama Alat');
                    if($('#reg_seksi').val()===''||$('#reg_seksi').val()==='tambah') missingP.push('Seksi');
                    if($('#reg_periode').val().trim()===''||Number($('#reg_periode').val())<=0) missingP.push('Periode Kalibrasi');
                    if($('#reg_metode').val().trim()==='') missingP.push('Metode');
                    if($('#reg_standar_uji').val().trim()==='') missingP.push('Alat Standar Utama');
                    if($('#reg_trace').val().trim()==='') missingP.push('Traceability');
                    if(arrStandar.length===0) missingP.push('Data SK Gauge');
                    if(!validTitikP) missingP.push('Data alat/titik Form P harus lengkap dan range MIN <= MAX');
                    if(missingP.length){ BootstrapDialog.alert('<b>Field FORM P belum lengkap:</b><br>'+missingP.map(function(i){return '• '+i;}).join('<br>')); return; }
                    $('#reg_satuan').val('mm');

                } else if(format === 'O') {
                    var validTitikO = true;
                    $('#reg_o_body .reg-o-row').each(function(){
                        var row={
                            no_alat:$(this).find('.inp-reg-o-noalat').val().trim(),
                            spesifikasi:$(this).find('.inp-reg-o-spec').val().trim(),
                            tol_plus:$(this).find('.inp-reg-o-plus').val().trim(),
                            tol_minus:$(this).find('.inp-reg-o-minus').val().trim(),
                            go_std:$(this).find('.inp-reg-o-go-std').val().trim(),
                            go_tol_near:$(this).find('.inp-reg-o-go-near').val().trim(),
                            go_tol_far:$(this).find('.inp-reg-o-go-far').val().trim(),
                            notgo_std:$(this).find('.inp-reg-o-not-std').val().trim(),
                            notgo_tol:$(this).find('.inp-reg-o-not-tol').val().trim(),
                            no_gambar:$(this).find('.inp-reg-o-gambar').val().trim()
                        };
                        var empty = Object.values(row).every(function(v){ return v===''; });
                        if(empty) return;
                        if(!row.no_alat || !row.no_gambar) validTitikO=false;
                        ['spesifikasi','tol_plus','tol_minus','go_std','go_tol_near','go_tol_far','notgo_std','notgo_tol'].forEach(function(k){ if(row[k]==='' || !Number.isFinite(Number(row[k]))) validTitikO=false; });
                        arrStandar.push(row);
                    });
                    var missingO=[];
                    if(regNo==='') missingO.push('Nomor Alat / Kode Master');
                    if($('#reg_nama_alat').val().trim()==='') missingO.push('Nama Alat');
                    if($('#reg_seksi').val()===''||$('#reg_seksi').val()==='tambah') missingO.push('Seksi');
                    if($('#reg_periode').val().trim()===''||Number($('#reg_periode').val())<=0) missingO.push('Periode Kalibrasi');
                    if($('#reg_metode').val().trim()==='') missingO.push('Metode');
                    if($('#reg_standar_uji').val().trim()==='') missingO.push('Standar Uji / No Alat');
                    if($('#reg_trace').val().trim()==='') missingO.push('Traceability');
                    if(arrStandar.length===0) missingO.push('Data Snap Gauge');
                    if(!validTitikO) missingO.push('Data Snap Gauge harus lengkap dan numerik');
                    if(missingO.length){ BootstrapDialog.alert('<b>Field FORM O belum lengkap:</b><br>'+missingO.map(function(i){ return '• '+i; }).join('<br>')); return; }
                    $('#reg_satuan').val('mm');

                } else if(format === 'L') {
                    var validTitikL = true;
                    var stdNoL = $('#reg_l_std_no').val().trim().toUpperCase();

                    $('#body_reg_standar .reg-l-row').each(function() {
                        var arah = $(this).find('.inp-reg-l-arah').val().trim().toUpperCase();
                        var applied = $(this).find('.inp-reg-l-applied').val().trim();

                        if (applied !== '') {
                            if ((arah !== 'NAIK' && arah !== 'TURUN') || !Number.isFinite(Number(applied))) {
                                validTitikL = false;
                            }
                            arrStandar.push({
                                arah: arah,
                                applied_value: applied,
                                std_no_alat: stdNoL
                            });
                        }
                    });

                    var missingL = [];
                    if(regNo === '') missingL.push('Nomor Alat');
                    if($('#reg_nama_alat').val().trim() === '') missingL.push('Nama Alat');
                    if($('#reg_spesifikasi').val().trim() === '') missingL.push('Spesifikasi');
                    if($('#reg_seksi').val() === '' || $('#reg_seksi').val() === 'tambah') missingL.push('Seksi');
                    if($('#reg_periode').val().trim() === '' || Number($('#reg_periode').val()) <= 0) missingL.push('Periode Kalibrasi');
                    if($('#reg_resolusi').val().trim() === '') missingL.push('Resolusi');
                    if($('#reg_toleransi').val().trim() === '' || Number($('#reg_toleransi').val()) <= 0) missingL.push('Standar Toleransi (MPa)');
                    if($('#reg_metode').val().trim() === '') missingL.push('Metode');
                    if($('#reg_standar_uji').val().trim() === '') missingL.push('Standar Uji');
                    if(stdNoL === '') missingL.push('No. Alat Standar');
                    if($('#reg_trace').val().trim() === '') missingL.push('Traceability');
                    if(arrStandar.length === 0) missingL.push('Applied Value');
                    if(!validTitikL) missingL.push('Uji/Skala dan Applied Value harus valid');

                    if(missingL.length > 0) {
                        BootstrapDialog.alert('<b>Field FORM L belum lengkap:</b><br>' + missingL.map(function(item){ return '• ' + item; }).join('<br>'));
                        return;
                    }

                    $('#reg_satuan').val('MPa');

                } else if(format === 'D') {
                    var validTitikD = true;
                    $('#body_reg_standar .reg-d-row').each(function() {
                        var unit = $(this).find('.inp-reg-d-unit').val().trim();
                        var mode = $(this).find('.inp-reg-d-mode').val().trim();
                        var setting = $(this).find('.inp-reg-d-setting').val().trim();
                        var standar = $(this).find('.inp-reg-d-standar').val().trim();

                        if (setting !== '' || standar !== '') {
                            if (unit === '' || setting === '' || standar === '' || !Number.isFinite(Number(setting)) || !Number.isFinite(Number(standar))) {
                                validTitikD = false;
                            }
                            arrStandar.push({ unit: unit, mode: mode || '-', setting: setting, standar: standar });
                        }
                    });

                    var missingD = [];
                    if(regNo === '') missingD.push('No. Alat');
                    if($('#reg_nama_alat').val().trim() === '') missingD.push('Nama Alat');
                    if($('#reg_spesifikasi').val().trim() === '') missingD.push('Spesifikasi');
                    if($('#reg_seksi').val() === '' || $('#reg_seksi').val() === 'tambah') missingD.push('Seksi Pemilik');
                    if($('#reg_periode').val().trim() === '' || Number($('#reg_periode').val()) <= 0) missingD.push('Periode Kalibrasi');
                    if($('#reg_resolusi').val().trim() === '') missingD.push('Resolusi');
                    if($('#reg_toleransi').val().trim() === '') missingD.push('Standar Toleransi');
                    if($('#reg_metode').val().trim() === '') missingD.push('Metode');
                    if($('#reg_standar_uji').val().trim() === '') missingD.push('Standar Uji');
                    if($('#reg_trace').val().trim() === '') missingD.push('Traceability');
                    if(arrStandar.length === 0) missingD.push('Titik Pemeriksaan Multimeter');
                    if(!validTitikD) missingD.push('Unit, Setting, dan Standar pada setiap baris harus diisi dengan benar');

                    if(missingD.length > 0) {
                        BootstrapDialog.alert('<b>Field FORM D belum lengkap:</b><br>' + missingD.map(function(item){ return '• ' + item; }).join('<br>'));
                        return;
                    }
                } else {
                    var useBagian = $('.col-bagian').is(':visible');
                    var isLegacyToleransiFormat = ['KUSTOM','E','F','G','N','R'].includes(format);

                    if (format === 'F') {
                        var stdSec1 = $('#reg_f_std_sec1').val().trim();
                        var stdSec3 = $('#reg_f_std_sec3').val().trim();
                        if (stdSec1 === '' || stdSec3 === '') {
                            BootstrapDialog.alert('<b>Gagal!</b> Standar Repeatability dan Center Loading wajib diisi untuk Format F!'); return;
                        }
                        arrStandar.push({ meta: 'F', std_sec1: stdSec1, std_sec3: stdSec3 });
                    }

                    $('#body_reg_standar tr').each(function() {
                        var bg = $(this).find('.inp-reg-bagian').val();
                        var std = $(this).find('.inp-reg-standar').val();
                        var tol = $(this).find('.inp-reg-toleransi').val();
                        
                        if(std !== '') {
                            if(isLegacyToleransiFormat) {
                                if(tol === '') validToleransiFormatN = false;
                                var r = { standar: parseFloat(std), toleransi: tol !== '' ? parseFloat(tol) : 0 };
                                if(useBagian) r.bagian = bg; 
                                arrStandar.push(r);
                            } else { arrStandar.push(parseFloat(std)); }
                        }
                    });
                }

                if(format !== 'A') {
                    if (format === 'B') {
                        // Air Driver tidak menggunakan Satuan/Toleransi Global.
                        if($('#reg_nama_alat').val().trim() === '' || $('#reg_spesifikasi').val().trim() === '' || arrStandar.length === 0) {
                            BootstrapDialog.alert('Nama Alat, Spesifikasi, dan minimal 1 titik standar Form B wajib diisi!'); return;
                        }
                        if($('#reg_seksi').val() === '' || $('#reg_seksi').val() === 'tambah') {
                            BootstrapDialog.alert('Seksi Pemilik wajib diisi untuk Format B!'); return;
                        }
                        if($('#reg_periode').val().trim() === '' || parseFloat($('#reg_periode').val()) <= 0) {
                            BootstrapDialog.alert('Periode Kalibrasi wajib diisi dengan nilai lebih dari 0!'); return;
                        }
                    } else if (format === 'C') {
                        // Validasi rinci Form C sudah dilakukan di blok khusus di atas.
                    } else if (format === 'D') {
                        // Validasi rinci Form D sudah dilakukan di blok khusus di atas.
                    } else if (format === 'H') {
                        // Validasi rinci Form H sudah dilakukan di blok khusus di atas.
                    } else if (format === 'I') {
                        // Validasi rinci Form I sudah dilakukan di blok khusus di atas.
                    } else if (format === 'J') {
                        // Validasi rinci Form J sudah dilakukan di blok khusus di atas.
                    } else if (format === 'K') {
                        // Validasi rinci Form K sudah dilakukan di blok khusus di atas.
                    } else if (format === 'L') {
                        // Validasi rinci Form L sudah dilakukan di blok khusus di atas.
                    } else if (format === 'M') {
                        // Validasi rinci Form M sudah dilakukan di blok khusus di atas.
                    } else if (format === 'O') {
                        // Validasi rinci Form O sudah dilakukan di blok khusus di atas.
                    } else if (format === 'P') {
                        // Validasi rinci Form P sudah dilakukan di blok khusus di atas.
                    } else if (format === 'Q') {
                        // Validasi rinci Form Q sudah dilakukan di blok khusus di atas.
                    } else {
                        if($('#reg_nama_alat').val() === '' || $('#reg_spesifikasi').val() === '' || $('#reg_satuan').val() === '' || arrStandar.length === (format === 'F' ? 1 : 0)) {
                            BootstrapDialog.alert('Nama Alat, Spesifikasi, Satuan, dan minimal 1 Nilai Standar Uji wajib diisi!'); return;
                        }
                        if((format === 'KUSTOM' || format === 'E' || format === 'F' || format === 'G' || format === 'N' || format === 'R') && !validToleransiFormatN) { BootstrapDialog.alert('Toleransi (±) Wajib diisi per baris!'); return; }
                    }
                }

                var btn = $(this); btn.html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...'); btn.prop('disabled', true);

                if(format === 'A') {
                    $('#reg_metode').val($('#reg_a_metode').val().trim());
                    $('#reg_trace').val($('#reg_a_traceability').val().trim());
                    $('#reg_standar_uji').val(($('#reg_a_go_std_spek').val().trim()) + (($('#reg_a_go_std_spek').val().trim() && $('#reg_a_notgo_std_spek').val().trim()) ? ' | ' : '') + ($('#reg_a_notgo_std_spek').val().trim()));
                    $('#reg_satuan').val('');
                }

                var formData = new FormData();
                formData.append('no_part', regNo); 
                formData.append('nama_part', $('#reg_nama_alat').val().trim());
                formData.append('seksi', $('#reg_seksi').val()); 
                formData.append('periode', $('#reg_periode').val());
                formData.append('kls', $('#reg_kls').val()); 
                formData.append('ukuran', '');
                formData.append('resolusi', $('#reg_resolusi').val().trim()); 
                formData.append('lokasi', $('#reg_lokasi').val().trim());
                formData.append('type', $('#reg_type').val().trim().toUpperCase()); 
                formData.append('tgl_manual', $('#reg_tgl_manual').val()); 
                formData.append('spesifikasi', (format === 'A' ? $('#reg_a_spesifikasi').val().trim() : $('#reg_spesifikasi').val().trim())); 
                formData.append('toleransi_global', $('#reg_toleransi').val() || '');
                formData.append('satuan', $('#reg_satuan').val().trim()); 
                formData.append('metode_uji', $('#reg_metode').val().trim());
                formData.append('standar_uji', $('#reg_standar_uji').val().trim()); 
                formData.append('traceability', $('#reg_trace').val().trim());
                formData.append('format_laporan', format); 
                formData.append('titik_standar_json', JSON.stringify(arrStandar));
                // Upload foto berlaku untuk semua format, termasuk FORM A.
                if ($('#reg_foto_alat').length && $('#reg_foto_alat')[0].files.length > 0) {
                    formData.append('foto_alat', $('#reg_foto_alat')[0].files[0]);
                }

                $.ajax({
                    url: 'proses_qa.php?action=simpan_alat', type: 'POST', dataType: 'json', data: formData, processData: false, contentType: false, 
                    success: function(res) {
                        btn.html('Simpan & Daftarkan Alat <i class="fa fa-save"></i>'); btn.prop('disabled', false);
                        if(res.status === 'success') {
                            BootstrapDialog.alert({ 
                                title: 'Berhasil Masuk DB', type: BootstrapDialog.TYPE_SUCCESS, message: 'Alat berhasil didaftarkan.', 
                                callback: function() { $('.hidden-form-section, #form-form-a').slideUp(); $('#nomor_alat').prop('disabled', false).val(regNo); $('#btn-cek').click(); } 
                            });
                        } else { BootstrapDialog.alert('Error DB: ' + res.message); }
                    }, error: function() { btn.html('Simpan & Daftarkan Alat <i class="fa fa-save"></i>'); btn.prop('disabled', false); BootstrapDialog.alert('Koneksi Error saat Registrasi!'); }
                });
            });

        });
    </script>
</body>
</html>
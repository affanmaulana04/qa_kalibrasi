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
$role_session = strtolower((string)($_SESSION['role'] ?? ''));
$is_admin = ($status_admin == 'Y' || $status_admin == '1' || $role_session == 'admin');

mysqli_query($konek, "CREATE TABLE IF NOT EXISTS qa_kls (id INT AUTO_INCREMENT PRIMARY KEY, nama_kelas VARCHAR(50) NOT NULL UNIQUE)");
mysqli_query($konek, "INSERT IGNORE INTO qa_kls (nama_kelas) VALUES ('KLS 1'), ('KLS 2'), ('KLS 3')");
mysqli_query($konek, "CREATE TABLE IF NOT EXISTS qa_seksi_master (id INT AUTO_INCREMENT PRIMARY KEY, nama_seksi VARCHAR(100) NOT NULL UNIQUE)");
mysqli_query($konek, "INSERT IGNORE INTO qa_seksi_master (nama_seksi) VALUES ('WH'), ('GA'), ('INJ'), ('QC'), ('MO'), ('TECH'), ('QA'), ('Maintenance')");

$q_seksi = mysqli_query($konek, "SELECT * FROM qa_seksi_master ORDER BY nama_seksi ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengecekan Alat Kalibrasi | QA System</title>
    <link rel="icon" type="image/png" href="/qa_kalibrasi/images/favicon-toto-qa.png?v=1">
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
        /* QA update: field lingkungan dibuat 50:50 */
        #form-update-kalibrasi .div-ruangan { width: 50%; float: left; }
        #form-update-kalibrasi .div-ketidakpastian { width: 25%; float: left; }
        #form-update-kalibrasi .qa-report-meta-row > [class*="col-"] { width: 50%; float: left; }
        @media (max-width: 767px) {
            #form-update-kalibrasi .div-ruangan,
            #form-update-kalibrasi .div-ketidakpastian,
            #form-update-kalibrasi .qa-report-meta-row > [class*="col-"] { width: 100%; float: none; }
        }

        #form-registrasi .input-group-btn .btn, #form-update-kalibrasi .input-group-btn .btn { height: 42px !important; }
        .table-entry th { background-color: #f8f9fa; color: #333; text-align: center; vertical-align: middle !important; border-bottom: 2px solid #ddd !important; font-size: 13px; }
        .table-entry td { text-align: center; vertical-align: middle !important; }
        .qa-toolbar-box { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:10px; padding:10px 12px; background:#f7fbff; border:1px solid #d9e8f5; border-radius:6px; }
        .qa-toolbar-left { display:flex; align-items:center; flex-wrap:wrap; gap:8px; }
        .qa-toolbar-help { color:#666; font-size:12px; }
        .qa-n-col-label { min-width:130px; text-align:center; font-weight:bold; }
        #tabel_input_kalibrasi input.form-control, #tabel_input_kalibrasi select.form-control { min-width:70px; }
        #tabel_input_kalibrasi .qa-readonly { background:#f1f3f5; }
        .inp-hasil-kalibrasi, .inp-f { text-align: center; font-weight: bold; }
        .inp-koreksi { text-align: center; background-color: #f4f6f9 !important; font-weight:bold; }
        .btn-sym { font-weight: bold; color: #1E3F66; border: 1px solid #1E3F66; background: #fff; margin-right: 5px;}
        .btn-sym:hover { background: #1E3F66; color: #fff; }
        /* CEK ALAT QR V6 - FIX NYATA QR + INPUT SATU BARIS SEPERTI LAYOUT AWAL */
        #formCekAlat #group-input .input-group {
            display:flex !important;
            width:100% !important;
            align-items:stretch;
        }
        #formCekAlat #group-input #btn-scan-qr {
            display:flex !important;
            align-items:center;
            justify-content:center;
            flex:0 0 80px;
            width:80px !important;
            min-width:80px !important;
            height:55px !important;
            padding:0 !important;
            margin:0 !important;
            border:1px solid #ccc !important;
            border-right:0 !important;
            border-radius:6px 0 0 6px !important;
            background:#f5f5f5 !important;
            color:#555 !important;
            box-sizing:border-box;
        }
        #formCekAlat #group-input #btn-scan-qr i { line-height:1 !important; }
        #formCekAlat #group-input #nomor_alat {
            display:block !important;
            float:none !important;
            flex:1 1 auto;
            width:1% !important;
            min-width:0;
            height:55px !important;
            margin:0 !important;
            border-radius:0 6px 6px 0 !important;
            box-sizing:border-box;
        }
        /* CEK ALAT QR V3 - RAPIIKAN SHORTCUT BBRAU + QR AGAR SELALU SEBELAHAN */
        .qa-detail-heading { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; border-bottom:2px solid #eee; padding-bottom:10px; margin-bottom:15px; }
        .qa-detail-heading .section-title { border-bottom:0; padding-bottom:0; margin:0; }
        .qa-detail-actions { display:flex; align-items:center; gap:7px; flex-wrap:nowrap; margin-left:auto; }
        .qa-detail-actions .btn { margin:0 !important; white-space:nowrap; }
        @media (max-width:767px) { .qa-detail-heading { align-items:flex-start; } .qa-detail-actions { width:100%; margin-left:0; } }
    </style>
</head>
<body oncontextmenu="return false;">

    <nav class="navbar navbar-default navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header"><a class="navbar-brand" href="../../media.php"><img class="img-responsive" src="../../images/toto.png" style="height: 25px; margin-top: -3px;"></a></div>
            <div class="collapse navbar-collapse">
                <ul class="nav navbar-nav navbar-left"><li><a href="../../media.php"><i class="fa fa-arrow-left"></i> Kembali ke Menu Utama</a></li></ul>
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="masterdata.php" style="padding-top: 10px; padding-bottom: 10px;"><button class="btn btn-default btn-sm" style="font-weight: bold; border: 1px solid #ccc; color:#333;"><i class="fa fa-database"></i> Master Data</button></a></li>
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
                            <p class="text-muted" style="margin-bottom: 30px;">Ketik Nomor Alat untuk mendaftarkan alat atau melakukan kalibrasi.</p>
                            <form id="formCekAlat" onsubmit="return false;">
                                <div class="form-group" id="group-input">
                                    <div class="input-group input-group-lg">
                                        <button type="button" id="btn-scan-qr" class="input-group-addon" title="Scan QR Code Alat" style="cursor:pointer;border:1px solid #ccc;background:#f5f5f5;"><i class="fa fa-qrcode"></i></button>
                                        <input type="text" class="form-control text-center text-uppercase-input" id="nomor_alat" placeholder="Ketik No. Alat" autocomplete="off">
                                    </div>
                                    <span id="error-msg" class="text-danger" style="display:none; text-align:left; margin-top:8px;"><i class="fa fa-exclamation-circle"></i> Nomor alat wajib diisi!</span>
                                </div>
                                <div class="form-group" style="margin-top: 20px; margin-bottom: 0;"><button type="button" id="btn-cek" class="btn btn-validasi btn-block">Cek Alat</button></div>
                            </form>
                        </div>
                        <div class="col-md-6 flex-col-center" id="visual-alat-panel" style="padding-left: 30px; text-align: center;">
                            <h4 style="font-weight: bold; color:#555; margin-top:0; margin-bottom: 15px;">Visual Alat</h4>
                            <div class="foto-alat-container">
                                <img id="preview_gambar_alat" src="" alt="Visual Alat" style="display:none;">
                            </div>
                        </div>
                    </div>

                    <!-- 1. FORM UPDATE KALIBRASI -->
                    <div id="form-update-kalibrasi" class="hidden-form-section">
                        <div class="qa-detail-heading">
                            <h4 class="section-title text-success"><i class="fa fa-list-alt"></i> Detail & Riwayat Kalibrasi</h4>
                            <div id="qa-detail-actions" class="qa-detail-actions"></div>
                        </div>
                        <div class="table-responsive" style="margin-bottom: 30px;">
                            <table class="table table-bordered table-striped table-hover table-custom" id="tabel-gabungan">
                                <thead><tr><th width="3%">NO</th><th>NO ALAT</th><th>NAMA ALAT</th><th>SPESIFIKASI</th><th>SEKSI PEMILIK</th><th>LOKASI</th><th style="background-color:#e2e6ea;">STATUS</th><th style="background-color:#e2e6ea;">TGL QA</th><th style="background-color:#e2e6ea;">OPERATOR</th><th style="background-color:#e2e6ea; text-align:center;">AKSI</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <h4 class="section-title text-info"><i class="fa fa-edit"></i> Input Update Hasil Kalibrasi</h4>
                        <div class="row">
                            <div class="form-group col-md-6 col-sm-12" >
                                <label style="display:block;">No. Alat</label>
                                <input type="text" class="form-control text-uppercase-input" id="qa_nomor_alat" disabled style="background-color: #eee; font-weight:bold;">
                            </div>
                            <div class="form-group col-md-6 col-sm-12" >
                                <label style="display:block;">Nama Alat</label>
                                <input type="text" class="form-control" id="qa_nama_alat">
                            </div>
                            
                            <div class="form-group col-md-6 col-sm-12" >
                                <label style="display:block;" id="label-qa-spesifikasi">Spesifikasi</label>
                                <input type="text" id="qa_spesifikasi" class="form-control">
                            </div>
                            <div class="form-group col-md-6 col-sm-12" >
                                <label style="display:block;">Resolusi</label>
                                <input type="text" id="qa_resolusi" class="form-control">
                            </div>

                            <div class="form-group col-md-6 col-sm-12" >
                                <label style="display:block;">Metode Uji</label>
                                <input type="text" id="qa_metode" class="form-control">
                            </div>
                            <div class="form-group col-md-6 col-sm-12" >
                                <label style="display:block;">Standar Uji</label>
                                <input type="text" id="qa_standar_uji" class="form-control">
                            </div>

                            <div class="form-group col-md-6 col-sm-12" >
                                <label style="display:block;">Traceability</label>
                                <input type="text" id="qa_trace" class="form-control">
                            </div>
                            <div class="form-group col-md-6 col-sm-12" >
                                <label style="display:block;" class="qa-tol-label-normal">Standar Tol (±)</label>
                                <div class="input-group qa-tol-group-normal">
                                    <input type="text" id="qa_toleransi" class="form-control" style="font-weight:bold;">
                                    <span class="input-group-addon qa_satuan_label"></span>
                                </div>
                                <div class="qa-tol-group-f" style="display:none; margin-top:5px;">
                                    <div class="row">
                                        <div class="col-xs-6"><label style="font-size:11px;">Batas Bawah</label><input type="text" id="qa_toleransi_f_rendah" class="form-control" style="font-weight:bold;"></div>
                                        <div class="col-xs-6"><label style="font-size:11px;">Batas Atas</label><input type="text" id="qa_toleransi_f_tinggi" class="form-control" style="font-weight:bold;"></div>
                                    </div>
                                </div>
                                <input type="hidden" id="qa_toleransi_value"><input type="hidden" id="qa_resolusi_value"><input type="hidden" id="qa_format_alat">
                                </div>
                            </div>

                        <div class="row">
                            <div class="form-group col-md-12 col-sm-12 qa-update-toolbar" style="margin-top: 25px;">
                                <div class="qa-toolbar-box">
                                    <div class="qa-toolbar-left">
                                        <button type="button" class="btn btn-sm btn-success" id="btn_add_update_row" style="display:none !important;"><i class="fa fa-plus"></i> Tambah Baris</button>
                                        <button type="button" class="btn btn-sm btn-info" id="btn_add_n_column" style="display:none;"><i class="fa fa-columns"></i> Tambah Kolom Bagian / Posisi</button>
                                        <div class="qa-bantuan-simbol-inline" style="display:inline-block; margin-left: 10px;">
                                            <strong style="margin-right: 5px;"><i class="fa fa-keyboard-o"></i> Simbol:</strong>
                                            <button type="button" class="btn btn-xs btn-sym" data-sym="°">° (Derajat)</button>
                                            <button type="button" class="btn btn-xs btn-sym" data-sym="±">± (Plus-Minus)</button>
                                            <button type="button" class="btn btn-xs btn-sym" data-sym="'">' (Menit)</button>
                                            <button type="button" class="btn btn-xs btn-sym" data-sym='\"'>" (Detik)</button>
                                            <button type="button" class="btn btn-xs btn-sym" data-sym="µ">µ (Mikro)</button>
                                            <button type="button" class="btn btn-xs btn-sym" data-sym="Ø">Ø (Diameter)</button>
                                            <button type="button" class="btn btn-xs btn-sym" data-sym="Ω">Ω (Ohm)</button>
                                        </div>
                                    </div>
                                    <div class="qa-toolbar-help" id="qa_n_column_help" style="display:none;">Pada Format N, isi nama Bagian/Posisi pada header kolom yang ditambahkan.</div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-entry" id="tabel_input_kalibrasi">
                                        <thead></thead><tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="row" style="margin-top: 15px;">
                                <div class="form-group col-md-6 col-sm-12" ><label style="display:block;">Suhu Ruangan</label><input type="text" id="qa_suhu" class="form-control auto-simbol" data-satuan="°C" placeholder="Cth: 21 atau Ambient"></div>
                                <div class="form-group col-md-6 col-sm-12" ><label style="display:block;">Kelembapan</label><input type="text" id="qa_kelembapan" class="form-control auto-simbol" data-satuan="%" placeholder="Cth: 55 atau Ambient"></div>
                                
                                <div class="form-group col-md-6 col-sm-12" ><label style="display:block;">Ketidakpastian</label><input type="text" id="qa_ketidakpastian" class="form-control" placeholder="Cth: 0.05 gram"></div>
                                <div class="form-group col-md-6 col-sm-12" ><label style="display:block;">Confidence Lvl</label><input type="text" id="qa_confidence" class="form-control auto-simbol" data-satuan="%" value="95 %"></div>

                                <div class="form-group col-md-6 col-sm-12" ><label style="display:block;">Kode Warna (opsional)</label><input type="text" id="qa_kode_warna" class="form-control"></div>
                                <div class="form-group col-md-6 col-sm-12" ><label style="display:block;">No. Gambar / Referensi (opsional)</label><input type="text" id="qa_no_gambar" class="form-control"></div>

                                <div class="form-group col-md-6 col-sm-12" ><label style="display:block;">No. Laporan <span class="text-danger">*</span></label><input type="text" id="qa_no_laporan" class="form-control" placeholder="Masukkan No. Laporan"></div>
                                <div class="form-group col-md-6 col-sm-12" ><label style="display:block;">Operator / User Pengecekan</label><div class="input-group"><span class="input-group-addon"><i class="fa fa-user"></i></span><input type="text" class="form-control" id="qa_petugas" value="<?php echo htmlspecialchars($nama_petugas, ENT_QUOTES); ?>" disabled style="background-color: #eee;"></div></div>

                                <div class="form-group col-md-6 col-sm-12" ><label style="display:block;">Keputusan (Status Final) <span class="text-danger">*</span></label><select id="qa_status_final" class="form-control" style="font-weight:bold; font-size:16px;"><option value="">-- Pilih Status --</option><option value="OK">OK</option><option value="NOT GOOD">NOT GOOD</option><option value="RUSAK">RUSAK</option><option value="DISCONTINUE">DISCONTINUE</option><option value="DITARIK TECHNICAL">DITARIK TECHNICAL</option></select></div>
                                <div class="form-group col-md-6 col-sm-12" ><label style="display:block;">Keterangan Tambahan</label><input type="text" id="qa_keterangan" class="form-control" placeholder="Catatan opsional..."></div>
                        </div>
                        <input type="hidden" id="qa_hasil_json">

                        <!-- BUTTON SECTION: SEPARATE ROW -->
                        <div class="row" style="margin-top: 25px; border-top:1px solid #ddd; padding-top:15px; clear:both; display:block;">
                            <div class="col-sm-12 text-right">
                                    <button type="button" id="btn-batal-qa" class="btn btn-default" style="padding: 8px 20px; margin-right: 10px; font-weight:bold;">Tutup</button>
                                    <button type="button" id="btn-simpan-qa" class="btn btn-primary" style="padding: 8px 30px; font-weight:bold;">Submit Hasil QA</button>
                                </div>
                            </div>
                    </div>

                    <!-- 2. FORM REGISTRASI ALAT -->
                    <div id="form-registrasi" class="hidden-form-section">
                        <div id="section-pilih-format" class="text-center" style="margin-bottom: 20px; padding: 25px; border: 2px dashed #ff9800; background-color: #fffaf3; border-radius: 10px;">
                            <h4 style="color: #e65100; font-weight: bold;"><i class="fa fa-warning"></i> Alat Belum Terdaftar</h4>
                            <p style="font-size: 15px; margin-bottom: 20px;">Silakan pilih jenis format kalibrasi untuk memulai registrasi alat baru.</p>
                            <div class="row">
                                <div class="col-md-6 col-md-offset-3">
                                    <select id="reg_pilih_format" class="form-control input-lg" style="border: 2px solid #ff9800; font-weight: bold; text-align: center; font-size:16px;">
                                        <option value="">-- Pilih Format Kalibrasi --</option>
                                        <option value="A">Format A - Gauge (GO / NOT GO)</option>
                                        <option value="B">Format B - Air Driver</option>
                                        <option value="C">Format C - Temperatur</option>
                                        <option value="D">Format D - Multimeter</option>
                                        <option value="E">Format E - Thermometer (T1 / T2)</option>
                                        <option value="F">Format F - Timbangan</option>
                                        <option value="G">Format G - Air Torque</option>
                                        <option value="H">Format H - Thread Plug Gauge</option>
                                        <option value="I">Format I - Gauge ATAS / BAWAH</option>
                                        <option value="J">Format J - Verifikasi Radius Gauge</option>
                                        <option value="K">Format K - Voltage Ultrasonic</option>
                                        <option value="L">Format L - Pressure Gauge</option>
                                        <option value="M">Format M - Profile Projector</option>
                                        <option value="N">Format N - Acuan Utama / Toleransi Dinamis</option>
                                        <option value="O">Format O - Snap Gauge</option>
                                        <option value="P">Format P - SK Gauge</option>
                                        <option value="Q">Format Q - Seration / Plug</option>
                                        <option value="R">Format R - Range 3 Titik</option>
                                        <option value="T">Format T - GO / NOT GO + Toleransi</option>
                                        <option value="U">Format U - Laporan Kalibrasi</option>
                                        <option value="V">Format V - Laporan Kalibrasi (Outside / Inside / Depth)</option>
                                        <option value="W">Format W - Laporan Pemeriksaan Sudut</option>
                                    </select>
                                    <button type="button" id="btn-lanjut-format" class="btn btn-warning btn-block" style="margin-top:15px; font-weight:bold; font-size: 16px; height: 50px;">Lanjut Registrasi <i class="fa fa-arrow-right"></i></button>
                                </div>
                            </div>
                        </div>

                        <div id="section-form-registrasi" style="display:none;">

                            <div style="padding: 0 30px;">
                                <h4 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px; color: #1E3F66; font-weight: bold;"><i class="fa fa-header"></i> Bagian Header Laporan</h4>
                                <div class="row" style="display: flex; flex-wrap: wrap;">
                                    <!-- KOLOM NO ALAT DIGEMBOK MATI (disabled & readonly) -->
                                    <div class="form-group col-md-6 col-sm-12"><label>No. Alat <span class="text-danger">*</span></label><input type="text" class="form-control text-uppercase-input" id="reg_nomor_alat" readonly disabled style="background-color: #eee; cursor:not-allowed;" placeholder="Masukan Nomor Alat"></div>
                                    <div class="form-group col-md-6 col-sm-12"><label>Spesifikasi <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_spesifikasi" placeholder="Cth: 5 ~ 15 mm"></div>
                                    
                                    <div class="form-group col-md-6 col-sm-12"><label>No. Laporan <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_no_laporan" placeholder="Masukkan No. Laporan"></div>
                                    <div class="form-group col-md-6 col-sm-12"><label>Resolusi <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_resolusi" placeholder="Cth: 0.01 mm"></div>

                                    <div class="form-group col-md-6 col-sm-12"><label>Nama Alat <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_nama_alat" placeholder="Contoh: Dial Caliper gage"></div>
                                    <div class="form-group col-md-6 col-sm-12"><label>Fungsi <span class="text-danger">*</span></label><textarea class="form-control" id="reg_fungsi" rows="1" placeholder="Fungsi Alat"></textarea></div>

                                    <div class="form-group col-md-6 col-sm-12"><label>No. Asset / FA</label><input type="text" class="form-control" id="reg_no_fa" placeholder="Masukkan No. Asset"></div>
                                    <div class="form-group col-md-6 col-sm-12"><label>Lokasi <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_lokasi" placeholder="Contoh: Lab GA"></div>
                                    
                                    <div class="form-group col-md-6 col-sm-12"><label>Merk <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_merk" placeholder="Masukkan Merk"></div>
                                    <div class="form-group col-md-6 col-sm-12"><label>Type <span class="text-danger">*</span></label><input type="text" class="form-control text-uppercase-input" id="reg_type" placeholder="Cth: TBI"></div>
                                    
                                    <div class="form-group col-md-6 col-sm-12"><label>Buatan <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_buatan" placeholder="Masukkan Buatan (Negara/Pabrikan)"></div>
                                    <div class="form-group col-md-6 col-sm-12" style="display:none;"><label>Kelas (KLS) <span class="text-danger">*</span></label><select class="form-control" id="reg_kls"><option value="">-- Pilih Kelas --</option></select></div>
                                    
                                    <div class="form-group col-md-6 col-sm-12">
                                        <label>Seksi Pemilik <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <select class="form-control" id="reg_seksi">
                                                <option value="">-- Pilih Seksi --</option>
                                                <?php mysqli_data_seek($q_seksi, 0); while($r_seksi = mysqli_fetch_assoc($q_seksi)) { echo '<option value="'.$r_seksi['nama_seksi'].'">'.$r_seksi['nama_seksi'].'</option>'; } ?>
                                                <?php if($is_admin) { ?><option value="tambah">+ Tambah Seksi Baru...</option><?php } ?>
                                            </select>
                                            <?php if($is_admin) { ?><span class="input-group-btn"><button class="btn btn-danger" type="button" id="btn_delete_reg_seksi" title="Hapus Seksi"><i class="fa fa-trash"></i></button></span><?php } ?>
                                        </div>
                                        <?php if($is_admin) { ?><div class="input-group" id="group_seksi_custom" style="display: none; margin-top: 10px;"><input type="text" class="form-control text-uppercase-input" id="reg_seksi_custom" placeholder="Ketik Seksi Baru"><span class="input-group-btn"><button class="btn btn-simpan-inline" type="button" id="btn_save_seksi"><i class="fa fa-save"></i> Simpan</button></span></div><?php } ?>
                                    </div>
                                    
                                    <div class="form-group col-md-6 col-sm-12">
                                        <div class="row" style="margin-left: -15px; margin-right: -15px;">
                                            <div class="col-xs-6" style="padding: 0 15px;">
                                                <label>Periode Kalibrasi <span class="text-danger">*</span></label>
                                                <div class="input-group"><input type="number" class="form-control" id="reg_periode" placeholder="180"><span class="input-group-addon">Hari</span></div>
                                            </div>
                                            <div class="col-xs-6" style="padding: 0 15px;">
                                                <label>Input Tanggal ( Opsional )</label>
                                                <input type="date" class="form-control" id="reg_tgl_manual" title="Gunakan untuk backdate">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <h4 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-top: 25px; margin-bottom: 15px; color: #1E3F66; font-weight: bold;"><i class="fa fa-align-bottom"></i> Bagian Footer Laporan</h4>
                                <div class="row" style="display: flex; flex-wrap: wrap;">
                                    <div class="form-group col-md-6 col-sm-12"><label>Metode Kalibrasi <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_metode" placeholder="Cth: STIS-SOPF-731010"></div>
                                    <div class="form-group col-md-6 col-sm-12"><label>Standar Toleransi (±) <span class="text-danger">*</span></label><textarea class="form-control" id="reg_toleransi" rows="1" placeholder="Cth: 0.06 atau < 1/4 Range"></textarea></div>

                                    <div class="form-group col-md-6 col-sm-12"><label>Standar Uji <span class="text-danger">*</span></label><textarea class="form-control" id="reg_standar_uji" rows="1" placeholder="Cth: Inside Micrometer"></textarea></div>
                                    <div class="form-group col-md-6 col-sm-12"><label>Satuan (Unit) <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_satuan" placeholder="Cth: mm / °C / Nm"></div>

                                    <div class="form-group col-md-6 col-sm-12"><label>Traceability <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_trace" placeholder="Cth: Gauge Block KL 0"></div>
                                    <div class="col-md-6 col-sm-12" id="reg-foto-home">
                                        <div class="form-group" id="reg_foto_group" style="margin-top:0;">
                                            <label><i class="fa fa-upload"></i> Upload Foto Alat ( Opsional )</label>
                                            <div class="input-group">
                                                <input type="file" class="form-control" id="reg_foto_alat" accept="image/jpeg, image/png, image/jpg">
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-danger" id="btn-batal-foto" title="Batalkan foto yang dipilih" style="display:none;">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div style="background: #eaf2f8; padding: 8px 15px; border-radius: 5px; margin-top: 15px; border: 1px solid #c2dced;">
                                    <strong style="margin-right: 15px;"><i class="fa fa-keyboard-o"></i> Bantuan Simbol:</strong>
                                    <button type="button" class="btn btn-xs btn-sym" data-sym="°">° (Derajat)</button>
                                    <button type="button" class="btn btn-xs btn-sym" data-sym="±">± (Plus-Minus)</button>
                                    <button type="button" class="btn btn-xs btn-sym" data-sym="'">' (Menit)</button>
                                    <button type="button" class="btn btn-xs btn-sym" data-sym='\"'>" (Detik)</button>
                                    <button type="button" class="btn btn-xs btn-sym" data-sym="µ">µ (Mikro)</button>
                                    <button type="button" class="btn btn-xs btn-sym" data-sym="Ø">Ø (Diameter)</button>
                                    <button type="button" class="btn btn-xs btn-sym" data-sym="Ω">Ω (Ohm)</button>
                                </div>
                            </div>
                            <div id="reg-foto-j-target" style="display:none; margin-top:10px;"></div>
                            
                            <div id="group_format_f_extra" style="display:none; background:#e8f4f8; padding:15px 30px; margin: 20px 30px 15px 30px; border-radius:8px; border:1px dashed #1E3F66;">
                                <h5 style="margin-top:0; font-weight:bold;"><i class="fa fa-info-circle"></i> Parameter Tambahan Timbangan (Format F)</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Standar Repeatability <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="reg_f_std_sec1" placeholder="Cth: 100">
                                    </div>
                                    <div class="col-md-6">
                                        <label>Standar Center Loading <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="reg_f_std_sec3" placeholder="Cth: 100">
                                    </div>
                                </div>
                                <div class="row" style="margin-top:15px;">
                                    <div class="col-md-6"><label>Batas Bawah</label><input type="text" class="form-control" id="reg_f_batas_bawah" placeholder="Cth: < 1/4 range = 2 x Res"></div>
                                    <div class="col-md-6"><label>Batas Atas</label><input type="text" class="form-control" id="reg_f_batas_atas" placeholder="Cth: > 1/4 range = 3 x Res"></div>
                                </div>
                            </div>

                            <div class="row" style="padding: 0 30px; margin-top:20px;">
                                <div class="col-md-12" style="border-top: 2px dashed #ddd; padding-top: 15px;">
                                    <div class="pull-right" id="container_btn_bagian">
                                        <button type="button" class="btn btn-sm btn-info" id="btn_toggle_bagian"><i class="fa fa-columns"></i> Tambah Kolom 'Bagian / Posisi'</button>
                                    </div>
                                    <label style="font-size:16px;" id="lbl_titik_standar"><i class="fa fa-list"></i> Titik Standar Kalibrasi <span class="text-danger">*</span></label>
                                    <p class="text-muted" style="font-size:12px;" id="sub_lbl_titik_standar">Tambahkan nilai titik acuan standar (Cth: 5, 6, 7, dst).</p>
                                    
                                    <table class="table table-bordered" id="tabel_reg_standar">
                                        <thead style="background-color: #f8f9fa;">
                                            <tr>
                                                <th width="5%" class="text-center">No</th>
                                                <th width="25%" class="col-bagian" style="display:none;"><span id="lbl_col_bagian">Bagian</span> <small class="text-muted">(Opsional)</small></th>
                                                <th width="30%">Nilai Standar Uji</th>
                                                <th width="25%" class="col-toleransi" style="display:none;">Toleransi (±)</th>
                                                <th width="15%" class="text-center"><button type="button" class="btn btn-sm btn-success" id="btn_add_standar"><i class="fa fa-plus"></i> Tambah Baris</button></th>
                                            </tr>
                                        </thead>
                                        <tbody id="body_reg_standar"></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="row" style="margin-top: 25px; border-top: 1px solid #ddd; padding-top: 15px;">
                                <div class="col-md-4 col-md-offset-2"><button type="button" id="btn-batal-reg" class="btn btn-default btn-block" style="height: 50px; font-weight:bold;">Batal</button></div>
                                <div class="col-md-4"><button type="button" id="btn-simpan-alat" class="btn btn-success btn-block" style="height: 50px; font-weight:bold; font-size:16px;">Simpan & Daftarkan Alat <i class="fa fa-save"></i></button></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- CEK ALAT QR V7 - LABEL QR 8CM + IDENTITAS ALAT + PRINT -->
    <div class="modal fade" id="modalQrGenerator" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document" style="max-width:520px;"><div class="modal-content">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><i class="fa fa-qrcode"></i> QR Code Alat</h4></div>
        <div class="modal-body text-center" style="background:#f5f6f7;">
          <div id="qr-label-print" style="width:8cm;max-width:100%;height:auto;margin:0 auto;background:#fff;border:1px solid #222;padding:0.35cm;box-sizing:border-box;text-align:center;">
            <div id="qr-generator-box" style="display:inline-block;padding:5px;background:#fff;"></div>
            <div style="border-top:1px solid #222;margin-top:6px;padding-top:7px;text-align:left;font-size:12px;line-height:1.45;">
              <div><strong>NO. ALAT</strong> : <span id="qr-generator-no"></span></div>
              <div><strong>NAMA ALAT</strong> : <span id="qr-generator-nama"></span></div>
              <div><strong>SEKSI PEMILIK</strong> : <span id="qr-generator-seksi"></span></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
          <button type="button" id="btn-print-qr" class="btn btn-primary"><i class="fa fa-print"></i> Print QR</button>
          <button type="button" id="btn-save-qr" class="btn btn-success"><i class="fa fa-save"></i> Simpan QR</button>
        </div>
      </div></div>
    </div>
    <div class="modal fade" id="modalQrScanner" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document"><div class="modal-content">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><i class="fa fa-camera"></i> Scan QR Code Alat</h4></div>
        <div class="modal-body"><div id="qr-reader" style="width:100%;"></div><p class="text-muted text-center" style="margin-top:10px;">Arahkan kamera ke QR Code alat.</p></div>
        <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button></div>
      </div></div>
    </div>

    <script src="../../js/jquery.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script src="../../js/bootstrap-dialog.js"></script>
    <script src="../../js/qrcode.min.js"></script>
    <script src="../../js/html5-qrcode.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.text-uppercase-input').on('input', function() { $(this).val($(this).val().toUpperCase()); });

            // QR READER USB/KEYBOARD: QR lama boleh tetap berisi "QA_ALAT:",
            // tetapi nilai yang tampil/terkirim dari kotak Cek Alat selalu nomor alat murni.
            function qaNormalizeNoAlatInput(value) {
                return String(value == null ? '' : value)
                    .replace(/^\s*QA_ALAT\s*:\s*/i, '')
                    .trim();
            }

            $(document).off('input.qaHardwareQr paste.qaHardwareQr', '#nomor_alat')
                .on('input.qaHardwareQr paste.qaHardwareQr', '#nomor_alat', function() {
                    var $input = $(this);
                    setTimeout(function() {
                        var raw = String($input.val() || '');
                        var clean = qaNormalizeNoAlatInput(raw).toUpperCase();
                        if (raw !== clean) $input.val(clean);
                    }, 0);
                });

            // BATAL UPLOAD FOTO REGISTRASI
            $(document).on('change', '#reg_foto_alat', function() {
                $('#btn-batal-foto').toggle(this.files && this.files.length > 0);
            });

            $(document).on('click', '#btn-batal-foto', function() {
                $('#reg_foto_alat').val('');
                $(this).hide();
                $('#reg_foto_alat').focus();
            });

            let pakaiBagian = false;
            let currentAlatHistory = [];
            let currentAlatData = null;

            const urlParams = new URLSearchParams(window.location.search);
            const noAlatParam = urlParams.get('no_alat');
            if (noAlatParam) {
                $('#nomor_alat').val(noAlatParam);
                setTimeout(function() { $('#btn-cek').click(); }, 300);
            }

            // SISTEM AUTO SIMBOL (Derajat, Persen)
            $(document).on('blur', '.auto-simbol', function() {
                var val = $(this).val().trim();
                var sym = $(this).data('satuan');
                if(val !== '' && !val.includes(sym)) {
                    $(this).val(val + ' ' + sym);
                }
            });

            var lastFocusedInput = null;
            $(document).on('focus', 'input[type="text"], input[type="number"], textarea', function() { lastFocusedInput = this; });
            $('.btn-sym').click(function(e) {
                e.preventDefault();
                if(lastFocusedInput) {
                    var sym = $(this).data('sym');
                    var start = lastFocusedInput.selectionStart;
                    var end = lastFocusedInput.selectionEnd;
                    var val = $(lastFocusedInput).val();
                    $(lastFocusedInput).val(val.slice(0, start) + sym + val.slice(end));
                    $(lastFocusedInput).focus();
                    lastFocusedInput.setSelectionRange(start + sym.length, start + sym.length);
                    $(lastFocusedInput).trigger('input'); 
                } else { BootstrapDialog.alert('Klik dulu kolom input yang mau diisi simbol.'); }
            });

            $('#reg_seksi').change(function() {
                if($(this).val() === 'tambah') { $('#group_seksi_custom').slideDown().find('input').focus(); } 
                else { $('#group_seksi_custom').slideUp().find('input').val(''); }
            });
            
            function hapusMaster(jenis, elemenId, namaAksi) {
                var valTerpilih = $('#' + elemenId).val();
                if(!valTerpilih || valTerpilih === 'tambah') { BootstrapDialog.alert('Silakan pilih data yang mau dihapus di kolom tersebut!'); return; }
                BootstrapDialog.confirm({
                    title: 'Konfirmasi Hapus Seksi', message: 'Yakin menghapus <b>\'' + valTerpilih + '\'</b>?',
                    type: BootstrapDialog.TYPE_DANGER, btnCancelLabel: 'Batal', btnOKLabel: 'Hapus', btnOKClass: 'btn-danger',
                    callback: function(result) {
                        if(result) {
                            $.ajax({ url: 'proses_qa.php?action=' + namaAksi, type: 'POST', dataType: 'json', data: { nama_seksi: valTerpilih },
                                success: function(res) {
                                    if(res.status === 'success') { $('#reg_seksi option[value="'+valTerpilih+'"]').remove(); BootstrapDialog.alert('Berhasil dihapus!'); } 
                                    else { BootstrapDialog.alert('Gagal: ' + res.message); }
                                }
                            });
                        }
                    }
                });
            }
            $('#btn_delete_reg_seksi').click(function() { hapusMaster('seksi', 'reg_seksi', 'hapus_master_seksi'); });
            $('#btn_save_seksi').click(function() {
                var newSeksi = $('#reg_seksi_custom').val().trim().toUpperCase(); if(newSeksi === '') return;
                $.ajax({ url: 'proses_qa.php?action=simpan_master_seksi', type: 'POST', dataType: 'json', data: { nama_seksi: newSeksi },
                    success: function(res) {
                        if(res.status === 'success') { $('<option value="'+newSeksi+'">'+newSeksi+'</option>').insertBefore('#reg_seksi option[value="tambah"]'); $('#reg_seksi').val(newSeksi); $('#group_seksi_custom').slideUp().find('input').val(''); } else { BootstrapDialog.alert(res.message); }
                    }
                });
            });

            function formatTanggal(tglStr) {
                if(!tglStr) return "-";
                var p = tglStr.split('-');
                if(p.length !== 3) return tglStr;
                return p[2] + '-' + p[1] + '-' + p[0]; 
            }

            function formatTanggalJadwal(tglStr) {
                if(!tglStr || tglStr === '0000-00-00' || tglStr === 'null') return '<span class="text-danger">Memerlukan Kalibrasi</span>';
                var p = tglStr.split('-');
                if(p.length !== 3) return tglStr;
                return p[2] + '-' + p[1] + '-' + p[0]; 
            }


            // Area Visual Alat tetap mempertahankan ukuran normal.
            // Jika foto kosong/rusak, hanya gambarnya yang disembunyikan tanpa placeholder.
            function setVisualAlat(fotoAlat) {
                var $panel = $('#visual-alat-panel');
                var $img = $('#preview_gambar_alat');
                var foto = String(fotoAlat || '').trim();

                $panel.show();
                $img.off('error.qaVisualAlat');

                if (!foto) {
                    $img.attr('src', '').hide();
                    return;
                }

                $img.one('error.qaVisualAlat', function() {
                    $(this).attr('src', '').hide();
                });

                $img.show().attr('src', '../../images/alat/' + foto);
            }

            // Format O menyimpan Spesifikasi pada titik_standar_json.
            // Format lain tetap menggunakan qa_part.spesifikasi seperti sebelumnya.
            function getAlatSpesifikasi(alat) {
                alat = alat || {};
                var fallback = String(alat.spesifikasi || '').trim();
                var format = String(alat.format_laporan || '').toUpperCase();
                if (format !== 'O') return fallback;

                try {
                    var points = JSON.parse(alat.titik_standar_json || '[]');
                    if (Array.isArray(points)) {
                        for (var i = 0; i < points.length; i++) {
                            if (!points[i] || typeof points[i] !== 'object') continue;
                            var spesifikasi = String(points[i].spesifikasi || '').trim();
                            if (spesifikasi !== '') return spesifikasi;
                        }
                    }
                } catch (e) {}

                return fallback;
            }

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
                        $('#form-update-kalibrasi, #form-registrasi').slideUp();
                        
                        if(res.found) {
                            var alat = res.data; 
                            var history = res.history;
                            currentAlatData = alat;
                            currentAlatHistory = history;
                            
                            setVisualAlat(alat.foto_alat);
                            
                            // FORM UPDATE DI UNLOCK TAPI ADA NILAI DEFAULT DARI REGISTRASI
                            $('#qa_nomor_alat').val(alat.no_part); 
                            $('#qa_nama_alat').val(alat.nama_part).prop('disabled', false); 
                            $('#qa_metode').val(alat.metode_uji || "-").prop('disabled', false); 
                            $('#qa_standar_uji').val(alat.standar_uji || "-").prop('disabled', false); 
                            $('#qa_trace').val(alat.traceability || "-").prop('disabled', false); 
                            $('#qa_toleransi').val(alat.toleransi_global).prop('disabled', false); 
                            if(alat.format_laporan==='F' && (alat.toleransi_global||'').includes('|')){ const ft=alat.toleransi_global.split('|'); $('#qa_toleransi_f_rendah').val(ft[0]); $('#qa_toleransi_f_tinggi').val(ft[1]); }
                            
                            $('#qa_resolusi_value').val(alat.resolusi || "-");
                            $('#qa_format_alat').val(alat.format_laporan);
                            $('.qa_satuan_label').text(alat.satuan); $('.qa_satuan_kurung').html(`( ${alat.satuan} )`);
                            $('#qa_toleransi_value').val(alat.toleransi_global);

                            var theadInput = $('#tabel_input_kalibrasi thead');
                            var tbInput = $('#tabel_input_kalibrasi tbody');
                            
                            var isKustom = (alat.format_laporan === 'N');
                            var isFormatR = (alat.format_laporan === 'R');
                            var isFormatG = (alat.format_laporan === 'G');
                            var isFormatF = (alat.format_laporan === 'F');
                            var isFormatE = (alat.format_laporan === 'E'); 
                            
                            var arrStandar = JSON.parse(alat.titik_standar_json || "[]");
                            var pakaiBagianDb = arrStandar.some(function(item) { return item.bagian && item.bagian.trim() !== ''; });
                            
                            // Semua format menggunakan area Suhu/Kelembapan 50:50.
                            $('.div-ruangan').removeClass('col-md-3 col-md-6').addClass('col-md-6');
                            qaApplyUpdateMetaVisibility(String(alat.format_laporan || 'A').toUpperCase());

                            if (isFormatF) {
                                theadInput.empty(); 
                                var metaF = arrStandar.find(x => x.meta === 'F') || {std_sec1: "100", std_sec3: "100"};
                                var pointsF = arrStandar.filter(x => x.meta !== 'F');
                                var stdSec1Str = String(metaF.std_sec1); var stdSec3Str = String(metaF.std_sec3);

                                var htmlF = `
                                    <tr><td colspan="5" style="background:#eaf2f8; text-align:left; font-weight:bold;">I. KEMAMPUAN ULANG ( REPEATABILITY OF READING )</td></tr>
                                    <tr>
                                        <td colspan="2" style="font-weight:bold; text-align:center;">STANDAR<br>( ${alat.satuan} )</td>
                                        <td colspan="2" style="font-weight:bold; text-align:center;">HASIL<br>( ${alat.satuan} )</td>
                                        <td style="font-weight:bold; text-align:center;">DEVIASI<br>( ${alat.satuan} )</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" style="font-weight:bold; font-size:15px; vertical-align:middle; text-align:center;">${stdSec1Str}</td>
                                        <td colspan="2"><input type="text" class="form-control inp-f inp-f-sec1-hasil" data-std="${stdSec1Str}"></td>
                                        <td><input type="text" class="form-control inp-f inp-f-sec1-deviasi" readonly></td>
                                    </tr>
                                    <tr><td colspan="5" style="background:#eaf2f8; text-align:left; font-weight:bold;">II. KEMAMPUAN NILAI SCALA ( SCALA TEST )</td></tr>
                                    <tr>
                                        <td width="10%" style="font-weight:bold; text-align:center;">NO</td>
                                        <td width="30%" style="font-weight:bold; text-align:center;">STANDAR<br>( ${alat.satuan} )</td>
                                        <td colspan="2" width="30%" style="font-weight:bold; text-align:center;">HASIL<br>( ${alat.satuan} )</td>
                                        <td width="30%" style="font-weight:bold; text-align:center;">KOREKSI<br>( ${alat.satuan} )</td>
                                    </tr>
                                `;
                                pointsF.forEach(function(pt, i) {
                                    var stdValStr = String(pt.standar !== undefined ? pt.standar : pt);
                                    htmlF += `<tr>
                                        <td style="text-align:center; vertical-align:middle;">${i+1}</td>
                                        <td style="font-weight:bold; text-align:center; vertical-align:middle;">${stdValStr}</td>
                                        <td colspan="2"><input type="text" class="form-control inp-f inp-f-sec2-hasil" data-std="${stdValStr}"></td>
                                        <td><input type="text" class="form-control inp-f inp-f-sec2-koreksi" readonly></td>
                                    </tr>`;
                                });
                                htmlF += `
                                    <tr><td colspan="5" style="background:#eaf2f8; text-align:left; font-weight:bold;">III. EFECT CENTER LOADING</td></tr>
                                    <tr>
                                        <td width="20%" style="font-weight:bold; text-align:center;">TENGAH<br>( ${alat.satuan} )</td>
                                        <td width="20%" style="font-weight:bold; text-align:center;">BELAKANG<br>( ${alat.satuan} )</td>
                                        <td width="20%" style="font-weight:bold; text-align:center;">KANAN<br>( ${alat.satuan} )</td>
                                        <td width="20%" style="font-weight:bold; text-align:center;">DEPAN<br>( ${alat.satuan} )</td>
                                        <td width="20%" style="font-weight:bold; text-align:center;">KIRI<br>( ${alat.satuan} )</td>
                                    </tr>
                                    <tr>
                                        <td><input type="text" class="form-control inp-f inp-f-sec3" id="f_tengah" placeholder="Std: ${stdSec3Str}"></td>
                                        <td><input type="text" class="form-control inp-f inp-f-sec3" id="f_belakang"></td>
                                        <td><input type="text" class="form-control inp-f inp-f-sec3" id="f_kanan"></td>
                                        <td><input type="text" class="form-control inp-f inp-f-sec3" id="f_depan"></td>
                                        <td><input type="text" class="form-control inp-f inp-f-sec3" id="f_kiri"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" style="text-align:right; vertical-align:middle; font-weight:bold;">PERBEDAAN DARI TENGAH ( ${alat.satuan} ) : </td>
                                        <td><input type="text" class="form-control inp-f" id="f_perbedaan" readonly></td>
                                    </tr>
                                    <tr style="display:none;"><td class="td-status">OK</td></tr>
                                `;
                                tbInput.html(htmlF);
                            } 
                            else {
                                if (isFormatE) {
                                    theadInput.html(`
                                        <tr>
                                            <th width="5%" rowspan="2" style="vertical-align:middle;">NO</th>
                                            <th width="15%" rowspan="2" style="vertical-align:middle;">STANDAR <span class="qa_satuan_kurung"></span></th>
                                            <th width="15%" rowspan="2" style="vertical-align:middle;">TOLERANSI (±)</th>
                                            <th width="30%" colspan="2">T1</th>
                                            <th width="30%" colspan="2">T2</th>
                                            <th width="5%" rowspan="2" style="vertical-align:middle;">STATUS</th>
                                        </tr>
                                        <tr>
                                            <th width="15%">HASIL</th><th width="15%">KOREKSI</th>
                                            <th width="15%">HASIL</th><th width="15%">KOREKSI</th>
                                        </tr>
                                    `);
                                } else if (isFormatR) {
                                    var thBagian = pakaiBagianDb ? '<th rowspan="2" style="vertical-align:middle;">POSISI</th>' : '';
                                    theadInput.html(`<tr><th rowspan="2" style="vertical-align:middle;" width="5%">NO</th>${thBagian}<th rowspan="2" style="vertical-align:middle;" width="15%">STANDAR</th><th colspan="3">HASIL <span class="qa_satuan_kurung"></span></th><th rowspan="2" style="vertical-align:middle;" width="10%">STATUS</th></tr><tr><th width="15%">DEPAN</th><th width="15%">TENGAH</th><th width="15%">BELAKANG</th></tr>`);
                                } else if (isFormatG) {
                                    theadInput.html(`<tr><th width="5%">NO</th><th width="20%">STANDAR <span class="qa_satuan_kurung"></span></th><th width="15%">TOLERANSI (±)</th><th width="25%">HASIL 1 <span class="qa_satuan_kurung"></span></th><th width="25%">HASIL 2 <span class="qa_satuan_kurung"></span></th><th width="10%">STATUS</th></tr>`);
                                } else if (isKustom) {
                                    var thBagian = pakaiBagianDb ? '<th width="20%">BAGIAN</th>' : '';
                                    theadInput.html(`<tr><th width="5%">NO</th>${thBagian}<th width="20%">STANDAR <span class="qa_satuan_kurung"></span></th><th width="15%">TOLERANSI (±)</th><th width="20%">HASIL <span class="qa_satuan_kurung"></span></th><th width="10%">KOREKSI</th><th width="10%">STATUS</th></tr>`);
                                } else {
                                    theadInput.html(`<tr><th width="5%">NO</th><th width="25%">STANDAR <span class="qa_satuan_kurung"></span></th><th width="30%">HASIL <span class="qa_satuan_kurung"></span></th><th width="25%">KOREKSI <span class="qa_satuan_kurung"></span></th><th width="15%">STATUS</th></tr>`);
                                }

                                tbInput.empty();
                                arrStandar.forEach(function(item, index) {
                                    var stdValStr = String(item.standar !== undefined ? item.standar : item);
                                    var tolValStr = String(item.toleransi !== undefined ? item.toleransi : alat.toleransi_global);

                                    if (isFormatE) {
                                        tbInput.append(`<tr><td style="vertical-align:middle;">${index + 1}</td><td style="font-weight:bold; vertical-align:middle;">${stdValStr}</td><td style="font-weight:bold; vertical-align:middle;">±${Math.abs(parseFloat(tolValStr))}</td><td style="vertical-align:middle;"><input type="text" class="form-control inp-hasil-kalibrasi inp-e-t1" data-std="${stdValStr}" data-tol="${tolValStr}" placeholder="Kosongkan jika silang"></td><td style="vertical-align:middle;"><input type="text" class="form-control inp-koreksi inp-e-kor1" readonly></td><td style="vertical-align:middle;"><input type="text" class="form-control inp-hasil-kalibrasi inp-e-t2" data-std="${stdValStr}" data-tol="${tolValStr}"></td><td style="vertical-align:middle;"><input type="text" class="form-control inp-koreksi inp-e-kor2" readonly></td><td style="vertical-align:middle;" class="td-status font-weight-bold">-</td></tr>`);
                                    } else if (isFormatR) {
                                        var minRange = (parseFloat(stdValStr) - Math.abs(parseFloat(tolValStr))).toFixed(3); var maxRange = (parseFloat(stdValStr) + Math.abs(parseFloat(tolValStr))).toFixed(3);
                                        var tdBagian = pakaiBagianDb ? `<td style="font-weight:bold;">${item.bagian || '-'}</td>` : '';
                                        tbInput.append(`<tr><td style="vertical-align:middle;">${index + 1}</td>${tdBagian}<td style="vertical-align:middle;"><span style="font-weight:bold; font-size:15px;">${stdValStr}</span> <span class="text-muted">±${tolValStr}</span><br><small class="text-muted">${minRange} ~ ${maxRange}</small></td><td style="vertical-align:middle;"><input type="text" class="form-control inp-hasil-kalibrasi inp-depan" data-std="${stdValStr}" data-tol="${tolValStr}"></td><td style="vertical-align:middle;"><input type="text" class="form-control inp-hasil-kalibrasi inp-tengah" data-std="${stdValStr}" data-tol="${tolValStr}"></td><td style="vertical-align:middle;"><input type="text" class="form-control inp-hasil-kalibrasi inp-belakang" data-std="${stdValStr}" data-tol="${tolValStr}"></td><td style="vertical-align:middle;" class="td-status font-weight-bold">-</td></tr>`);
                                    } else if (isFormatG) {
                                        tbInput.append(`<tr><td style="vertical-align:middle;">${index + 1}</td><td style="font-weight:bold; vertical-align:middle;">${stdValStr}</td><td style="font-weight:bold; vertical-align:middle;">±${Math.abs(parseFloat(tolValStr))}</td><td style="vertical-align:middle;"><input type="text" class="form-control inp-hasil-kalibrasi inp-hasil-1" data-std="${stdValStr}" data-tol="${tolValStr}"></td><td style="vertical-align:middle;"><input type="text" class="form-control inp-hasil-kalibrasi inp-hasil-2" data-std="${stdValStr}" data-tol="${tolValStr}" placeholder="(Opsional)"></td><td style="vertical-align:middle;" class="td-status font-weight-bold">-</td></tr>`);
                                    } else if (isKustom) {
                                        var tdBagian = pakaiBagianDb ? `<td style="font-weight:bold; vertical-align:middle;">${item.bagian || '-'}</td>` : '';
                                        tbInput.append(`<tr><td style="vertical-align:middle;">${index + 1}</td>${tdBagian}<td style="font-weight:bold; vertical-align:middle;">${stdValStr}</td><td style="font-weight:bold; vertical-align:middle;">±${Math.abs(parseFloat(tolValStr))}</td><td style="vertical-align:middle;"><input type="text" class="form-control inp-hasil-kalibrasi" data-std="${stdValStr}" data-tol="${tolValStr}"></td><td style="vertical-align:middle;"><input type="text" class="form-control inp-koreksi" readonly></td><td style="vertical-align:middle;" class="td-status font-weight-bold">-</td></tr>`);
                                    } else {
                                        tbInput.append(`<tr><td style="vertical-align:middle;">${index + 1}</td><td style="font-weight:bold; vertical-align:middle;">${stdValStr}</td><td style="vertical-align:middle;"><input type="text" class="form-control inp-hasil-kalibrasi" data-std="${stdValStr}" data-tol="${alat.toleransi_global}"></td><td style="vertical-align:middle;"><input type="text" class="form-control inp-koreksi" readonly></td><td style="vertical-align:middle;" class="td-status font-weight-bold">-</td></tr>`);
                                    }
                                });
                            }

                            var tGabungan = $('#tabel-gabungan tbody');
                            tGabungan.empty();
                            tGabungan.append('<tr><td>1</td><td><strong>'+qaEsc(alat.no_part || '-')+'</strong></td><td>'+qaEsc(alat.nama_part || '-')+'</td><td>'+qaEsc(getAlatSpesifikasi(alat) || '-')+'</td><td>'+qaEsc(alat.seksi_pemilik || '-')+'</td><td>'+qaEsc(alat.lokasi || '-')+'</td><td style="background-color:#eaf2f8;"><span class="label label-info"><i class="fa fa-clock-o"></i> Jadwal</span></td><td style="background-color:#eaf2f8;"><strong>'+formatTanggalJadwal(alat.jadwal_kalibrasi)+'</strong></td><td style="background-color:#eaf2f8; text-align:center;">-</td><td style="background-color:#eaf2f8; text-align:center;">-</td></tr>');

                            var num = 2;
                            if(history && history.length > 0) {
                                history.forEach(function(row, idx) {
                                    // STATUS RIWAYAT V1 - BERLAKU SEMUA FORMAT: REGISTER + TAHAP VALIDASI SPESIFIK
                                    var stKali = String(row.status_kalibrasi || '').toUpperCase();
                                    var stVali = String(row.status_validasi || '').toLowerCase();
                                    var jenisTrx = String(row.jenis_transaksi || row.tipe_transaksi || '').toLowerCase();
                                    var ketTrx = String(row.keterangan || '').toLowerCase();
                                    var isRegist = (jenisTrx === 'registrasi_alat' || jenisTrx.indexOf('registr') !== -1 || ketTrx.indexOf('registrasi alat') !== -1);
                                    var st = '';
                                    if(isRegist) {
                                        st = 'REGISTER';
                                    } else if(stVali === 'menunggu_pemeriksa') {
                                        st = 'MENUNGGU VALIDASI PEMERIKSA';
                                    } else if(stVali === 'menunggu_penyetuju') {
                                        st = 'MENUNGGU VALIDASI PENYETUJU';
                                    } else {
                                        if(stKali === 'GOOD' || stKali === 'OK' || stKali === 'LULUS') st = 'OK';
                                        else if(stKali === 'NOT GOOD' || stKali === 'NG' || stKali === 'BAD') st = 'NOT GOOD';
                                        else st = stKali;
                                    }
                                    var labelStatus = '';
                                    if(st === 'OK') { labelStatus = '<span class="label label-success">OK</span>'; }
                                    else if(st === 'REGISTER') { labelStatus = '<span class="label label-info">Register</span>'; }
                                    else if(st === 'NOT GOOD' || st === 'RUSAK' || st === 'DISCONTINUE' || st === 'DITARIK TECHNICAL') { labelStatus = '<span class="label label-danger">'+qaEsc(st)+'</span>'; }
                                    else { labelStatus = '<span class="label label-warning"><i class="fa fa-clock-o"></i> '+qaEsc(st || 'MENUNGGU VALIDASI')+'</span>'; }
                                    
                                    var btnAksi = '-';
                                    if(row.hasil_json && row.hasil_json.length > 2 && row.hasil_json !== '[]') {
                                        btnAksi = '<button class="btn btn-xs btn-primary btn-print-hist" data-idx="'+idx+'"><i class="fa fa-print"></i> Cetak</button>';
                                    }

                                    tGabungan.append('<tr><td>'+num+'</td><td><strong>'+(alat.no_part || "-")+'</strong></td><td>'+(alat.nama_part || "-")+'</td><td>'+(getAlatSpesifikasi(alat) || "-")+'</td><td>'+(alat.seksi_pemilik || "-")+'</td><td>'+(alat.lokasi || "-")+'</td><td style="background-color:#f4f7fa;">'+labelStatus+'</td><td style="background-color:#f4f7fa;"><strong>'+formatTanggal(row.tanggal)+'</strong></td><td style="background-color:#f4f7fa; text-align:center;">'+btnAksi+'</td></tr>');
                                    num++;
                                });
                            }

                            $('#qa_suhu, #qa_kelembapan, #qa_ketidakpastian, #qa_keterangan, #qa_status_final, #qa_hasil_json').val('');
                            $('#qa_confidence').val('95 %');
                            
                            $('#nomor_alat').prop('disabled', true);
                            $('#form-update-kalibrasi').slideDown();
                            setTimeout(function() { 
                                if(isFormatF) $('.inp-f-sec1-hasil').focus(); 
                                else if(isFormatE) $('.inp-e-t1').first().focus();
                                else $('.inp-hasil-kalibrasi').first().focus(); 
                            }, 300);

                        } else {
                            if(res.is_rusak) {
                                BootstrapDialog.alert({type: BootstrapDialog.TYPE_DANGER, title: 'PERINGATAN', message: res.message});
                                $('#nomor_alat').val('').focus();
                            } else {
                                // JIKA ALAT BELUM TERDAFTAR (REGISTRASI BARU) -> GEMBOK NO ALAT
                                $('#nomor_alat').prop('disabled', true); 
                                
                                var userNoAlat = $('#nomor_alat').val().toUpperCase();
                                $('#reg_nomor_alat').val(userNoAlat); 
                                
                                $('#reg_nama_alat, #reg_spesifikasi, #reg_resolusi, #reg_toleransi, #reg_satuan, #reg_lokasi, #reg_metode, #reg_standar_uji, #reg_trace, #reg_periode, #reg_tgl_manual').val('');
                                $('#reg_seksi, #reg_pilih_format').val('');
                                pakaiBagian = false; $('.col-bagian').hide();
                                $('#btn_toggle_bagian').show().html('<i class="fa fa-columns"></i> Tambah Kolom \'Bagian/Posisi\'').removeClass('btn-warning').addClass('btn-info');
                                
                                setVisualAlat('');

                                $('#section-form-registrasi').hide(); $('#section-pilih-format').show();
                                $('#form-registrasi').slideDown();
                            }
                        }
                    }, error: function() { btn.html(textAsli); btn.prop('disabled', false); BootstrapDialog.alert('Koneksi Error!'); }
                });
            });

            $('#nomor_alat').keypress(function(e) { if (e.which == 13) { $('#btn-cek').click(); } });
            $('#btn-batal-qa, #btn-batal-reg').click(function() { $('.hidden-form-section').slideUp(); $('#nomor_alat').prop('disabled', false).val('').focus(); });

            $('#btn-lanjut-format').click(function() {
                var format = $('#reg_pilih_format').val();
                if(format === '') { BootstrapDialog.alert('Silakan pilih Format Kalibrasi terlebih dahulu!'); return; }
                
                $('.col-toleransi').show();

                if(format === 'G' || format === 'E') {
                    pakaiBagian = false; $('.col-bagian').hide(); $('#container_btn_bagian').hide();
                } else {
                    $('#container_btn_bagian').show();
                }

                if(format === 'F') { 
                    $('#group_format_f_extra').slideDown(); 
                    $('#lbl_titik_standar').html('<i class="fa fa-list"></i> Titik Standar (Section II Scala Test)');
                    $('#sub_lbl_titik_standar').text('Tambahkan nilai beban uji Scala Test (Format F).');
                } else { 
                    $('#group_format_f_extra').slideUp(); 
                    $('#lbl_titik_standar').html('<i class="fa fa-list"></i> Titik Standar Kalibrasi <span class="text-danger">*</span>');
                    $('#sub_lbl_titik_standar').text('Tambahkan nilai titik acuan standar (Cth: 5, 6, 7, dst).');
                }
                if(format === 'R') { $('#lbl_col_bagian').text('Posisi'); } else { $('#lbl_col_bagian').text('Bagian'); }

                $('#body_reg_standar').empty(); addRegRow();
                $('#section-pilih-format').slideUp(); $('#section-form-registrasi').slideDown();
            });

            $('#btn_toggle_bagian').click(function() {
                if(String($('#reg_pilih_format').val()||'').toUpperCase()==='N'){
                    // Format N: Tambah Bagian = menambahkan KOLOM Bagian di antara No dan Standar.
                    // Sekali diaktifkan pada registrasi, tidak ada tombol Hapus Bagian.
                    let currentRows = (typeof qaCollectReg === 'function') ? qaCollectReg('N').filter(function(r){ return String(r.meta||r.jenis||'').toUpperCase()!=='N_KESEJAJARAN'; }) : [];
                    const oldRows = currentRows.slice();
                    currentRows = [
                        Object.assign({}, oldRows[0] || {}, {bagian:'OUTSIDE'}),
                        Object.assign({}, oldRows[1] || {}, {bagian:'INSIDE'}),
                        Object.assign({}, oldRows[2] || {}, {bagian:'DEPTH'})
                    ];
                    // Baris tambahan yang sudah dibuat user tetap dipertahankan.
                    if(oldRows.length > 3) currentRows = currentRows.concat(oldRows.slice(3));
                    $(this).data('n-kesejajaran',1);
                    if(typeof qaRenderRegTable === 'function') qaRenderRegTable('N', currentRows);
                    return;
                }
                pakaiBagian = !pakaiBagian;
                var format = $('#reg_pilih_format').val();
                var txt = format === 'R' ? 'Posisi' : 'Bagian';
                if(pakaiBagian) { $('.col-bagian').show(); $(this).html('<i class="fa fa-columns"></i> Hapus Kolom \''+txt+'\'').removeClass('btn-info').addClass('btn-warning'); } 
                else { $('.col-bagian').hide(); $('.inp-reg-bagian').val(''); $(this).html('<i class="fa fa-columns"></i> Tambah Kolom \''+txt+'\'').removeClass('btn-warning').addClass('btn-info'); }
            });

            $('#btn_add_standar').click(function() { addRegRow(); });
            function addRegRow() {
                var trCount = $('#body_reg_standar tr').length + 1;
                var format = $('#reg_pilih_format').val();
                var displayBagian = pakaiBagian ? '' : 'display:none;';
                var phBagian = format === 'R' ? 'Cth: Ø Out / Height' : 'Cth: Naik / Turun';
                
                var rowHtml = `
                    <tr>
                        <td class="text-center row-num" style="vertical-align:middle;">${trCount}</td>
                        <td class="col-bagian" style="${displayBagian}"><input type="text" class="form-control inp-reg-bagian" placeholder="${phBagian}"></td>
                        <td><input type="text" class="form-control inp-reg-standar" required></td>
                        <td class="col-toleransi"><input type="text" class="form-control inp-reg-toleransi"></td>
                        <td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-hapus-std"><i class="fa fa-trash"></i></button></td>
                    </tr>
                `;
                $('#body_reg_standar').append(rowHtml); updateRowNumbers();
            }
            $(document).on('click', '.btn-hapus-std', function() { $(this).closest('tr').remove(); updateRowNumbers(); });
            function updateRowNumbers() { $('#body_reg_standar tr').each(function(index) { $(this).find('.row-num').text(index + 1); }); }

            function updateKeputusanFinal() {
                var vals = [];
                $('#tabel_input_kalibrasi .td-status').each(function() {
                    vals.push($(this).text().trim().toUpperCase());
                });
                vals = vals.filter(x => x !== '');
                if (vals.length === 0) return;
                if (vals.some(x => /NOT GOOD|NG|X|TIDAK|FAIL/.test(x))) { 
                    $('#qa_status_final').val('NOT GOOD'); 
                } else if (vals.every(x => /^OK|GOOD|LULUS|PASS$/.test(x))) { 
                    $('#qa_status_final').val('OK'); 
                }
            }

            $(document).on('input', '.inp-f-sec1-hasil', function() {
                var val = parseFloat($(this).val()); var std = parseFloat($(this).attr('data-std'));
                if(!isNaN(val)) { $(this).closest('tr').find('.inp-f-sec1-deviasi').val((val - std).toFixed(4)); } else { $(this).closest('tr').find('.inp-f-sec1-deviasi').val(''); }
                buildJsonFormatF();
            });
            $(document).on('input', '.inp-f-sec2-hasil', function() {
                var val = parseFloat($(this).val()); var std = parseFloat($(this).attr('data-std'));
                if(!isNaN(val)) { $(this).closest('tr').find('.inp-f-sec2-koreksi').val((val - std).toFixed(4)); } else { $(this).closest('tr').find('.inp-f-sec2-koreksi').val(''); }
                buildJsonFormatF();
            });
            $(document).on('input', '.inp-f-sec3', function() {
                var t = parseFloat($('#f_tengah').val()); var b = parseFloat($('#f_belakang').val());
                var ka = parseFloat($('#f_kanan').val()); var d = parseFloat($('#f_depan').val()); var ki = parseFloat($('#f_kiri').val());
                if(!isNaN(t) && !isNaN(b) && !isNaN(ka) && !isNaN(d) && !isNaN(ki)) {
                    var maxDiff = Math.max(Math.abs(b-t), Math.abs(ka-t), Math.abs(d-t), Math.abs(ki-t));
                    $('#f_perbedaan').val(maxDiff.toFixed(4));
                } else { $('#f_perbedaan').val(''); }
                buildJsonFormatF();
            });

            function buildJsonFormatF() {
                if($('#qa_format_alat').val() !== 'F') return;
                var json = { format: 'F', sec1: {}, sec2: [], sec3: {} };
                var h1Str = $('.inp-f-sec1-hasil').val();
                if(!isNaN(parseFloat(h1Str))) { json.sec1 = { standar: $('.inp-f-sec1-hasil').attr('data-std'), hasil: h1Str, deviasi: $('.inp-f-sec1-deviasi').val() }; }
                
                $('.inp-f-sec2-hasil').each(function() {
                    var vStr = $(this).val();
                    if(vStr !== '') { json.sec2.push({ standar: $(this).attr('data-std'), hasil: vStr, koreksi: $(this).closest('tr').find('.inp-f-sec2-koreksi').val() }); }
                });

                var tStr = $('#f_tengah').val();
                if(tStr !== '') {
                    json.sec3 = { tengah: tStr, belakang: $('#f_belakang').val(), kanan: $('#f_kanan').val(), depan: $('#f_depan').val(), kiri: $('#f_kiri').val(), perbedaan: $('#f_perbedaan').val() };
                }
                $('#qa_hasil_json').val(JSON.stringify([json])); 
                $('#qa_status_final').val('OK');
            }

            $(document).on('input', '.inp-hasil-kalibrasi', function() {
                var format = $('#qa_format_alat').val();
                if(format === 'F') return; 
                
                var tr = $(this).closest('tr');
                var stdStr = $(this).closest('tr').find('.inp-hasil-kalibrasi').first().attr('data-std');
                var tolStr = $(this).closest('tr').find('.inp-hasil-kalibrasi').first().attr('data-tol');
                
                var valStd = parseFloat(stdStr);
                var toleransi = Math.abs(parseFloat(tolStr)); 
                var tdStatus = tr.find('.td-status');

                if (format === 'R') {
                    var depStr = tr.find('.inp-depan').val(); var tengStr = tr.find('.inp-tengah').val(); var belStr = tr.find('.inp-belakang').val();
                    var dep = parseFloat(depStr), teng = parseFloat(tengStr), bel = parseFloat(belStr);
                    
                    var stDepan = isNaN(dep) ? true : (Math.abs(parseFloat((dep - valStd).toFixed(4))) <= toleransi);
                    var stTengah = isNaN(teng) ? true : (Math.abs(parseFloat((teng - valStd).toFixed(4))) <= toleransi);
                    var stBelakang = isNaN(bel) ? true : (Math.abs(parseFloat((bel - valStd).toFixed(4))) <= toleransi);

                    if(!isNaN(dep) || !isNaN(teng) || !isNaN(bel)) {
                        if(stDepan && stTengah && stBelakang) { tdStatus.text('OK').removeClass('text-danger').addClass('text-success'); }
                        else { tdStatus.text('NOT GOOD').removeClass('text-success').addClass('text-danger'); }
                    } else { tdStatus.text('-').removeClass('text-success text-danger'); }

                } else if (format === 'E') {
                    var t1Str = tr.find('.inp-e-t1').val(); var t2Str = tr.find('.inp-e-t2').val();
                    var t1 = parseFloat(t1Str); var t2 = parseFloat(t2Str);
                    var kor1 = (!isNaN(t1)) ? parseFloat((t1 - valStd).toFixed(4)) : NaN;
                    var kor2 = (!isNaN(t2)) ? parseFloat((t2 - valStd).toFixed(4)) : NaN;
                    
                    if(!isNaN(kor1)) tr.find('.inp-e-kor1').val(kor1.toFixed(4)); else tr.find('.inp-e-kor1').val('');
                    if(!isNaN(kor2)) tr.find('.inp-e-kor2').val(kor2.toFixed(4)); else tr.find('.inp-e-kor2').val('');
                    
                    var st1 = isNaN(kor1) ? true : (Math.abs(kor1) <= toleransi);
                    var st2 = isNaN(kor2) ? true : (Math.abs(kor2) <= toleransi);
                    
                    if(!isNaN(t1) || !isNaN(t2)) {
                        if(st1 && st2) { tdStatus.text('OK').removeClass('text-danger').addClass('text-success'); }
                        else { tdStatus.text('NOT GOOD').removeClass('text-success').addClass('text-danger'); }
                    } else { tdStatus.text('-').removeClass('text-success text-danger'); }

                } else if (format === 'G') {
                    var h1Str = tr.find('.inp-hasil-1').val().trim(); 
                    var h2Str = tr.find('.inp-hasil-2').val().trim();
                    var h1 = parseFloat(h1Str), h2 = parseFloat(h2Str);
                    
                    var st1 = (h1Str === '') ? true : (!isNaN(h1) && Math.abs(parseFloat((h1 - valStd).toFixed(4))) <= toleransi);
                    var st2 = (h2Str === '') ? true : (!isNaN(h2) && Math.abs(parseFloat((h2 - valStd).toFixed(4))) <= toleransi);

                    if(h1Str !== '' || h2Str !== '') {
                        if(st1 && st2) { tdStatus.text('OK').removeClass('text-danger').addClass('text-success'); }
                        else { tdStatus.text('NOT GOOD').removeClass('text-success').addClass('text-danger'); }
                    } else { tdStatus.text('-').removeClass('text-success text-danger'); }

                } else {
                    var valStr = $(this).val();
                    var valHasil = parseFloat(valStr);
                    var inpkoreksi = tr.find('.inp-koreksi');

                    if (isNaN(valHasil)) {
                        inpkoreksi.val(''); tdStatus.text('-').removeClass('text-success text-danger');
                    } else {
                        var koreksi = parseFloat((valHasil - valStd).toFixed(4)); 
                        inpkoreksi.val(koreksi.toFixed(4));
                        if (Math.abs(koreksi) <= toleransi) { tdStatus.text('OK').removeClass('text-danger').addClass('text-success'); } 
                        else { tdStatus.text('NOT GOOD').removeClass('text-success').addClass('text-danger'); }
                    }
                }
                
                var resultJSON = [];
                $('#tabel_input_kalibrasi tbody tr').each(function() {
                    var inputEl = $(this).find('.inp-hasil-kalibrasi').first();
                    if(inputEl.length === 0) return;
                    
                    var rowStdStr = inputEl.attr('data-std');
                    var rowTolStr = inputEl.attr('data-tol');
                    var statusRow = $(this).find('.td-status').text();
                    var bag = $(this).find('td:eq(1)').text(); 
                    var hasBagian = $('#tabel_input_kalibrasi thead th').text().indexOf("BAGIAN") >= 0 || $('#tabel_input_kalibrasi thead th').text().indexOf("POSISI") >= 0;

                    if(format === 'R') {
                        var rDepStr = $(this).find('.inp-depan').val(); var rTengStr = $(this).find('.inp-tengah').val(); var rBelStr = $(this).find('.inp-belakang').val();
                        if(!isNaN(parseFloat(rDepStr)) || !isNaN(parseFloat(rTengStr)) || !isNaN(parseFloat(rBelStr))) {
                            var r = { standar: rowStdStr, toleransi: rowTolStr, depan: isNaN(parseFloat(rDepStr)) ? '' : rDepStr, tengah: isNaN(parseFloat(rTengStr)) ? '' : rTengStr, belakang: isNaN(parseFloat(rBelStr)) ? '' : rBelStr, status: statusRow };
                            if(hasBagian) r.bagian = bag; resultJSON.push(r);
                        }
                    } else if (format === 'E') {
                        var t1Str = $(this).find('.inp-e-t1').val(); var t2Str = $(this).find('.inp-e-t2').val();
                        if(!isNaN(parseFloat(t1Str)) || !isNaN(parseFloat(t2Str))) { 
                            resultJSON.push({ standar: rowStdStr, toleransi: rowTolStr, t1_hasil: isNaN(parseFloat(t1Str)) ? '' : t1Str, t1_koreksi: $(this).find('.inp-e-kor1').val(), t2_hasil: isNaN(parseFloat(t2Str)) ? '' : t2Str, t2_koreksi: $(this).find('.inp-e-kor2').val(), status: statusRow }); 
                        }
                    } else if (format === 'G') {
                        var rH1Str = $(this).find('[data-key="hasil"]').val(); var rH2Str = $(this).find('[data-key="hasil_2"]').val();
                        resultJSON.push({ standar: rowStdStr, toleransi: rowTolStr, hasil: isNaN(parseFloat(rH1Str)) ? '' : rH1Str, hasil_2: isNaN(parseFloat(rH2Str)) ? '' : rH2Str, status: statusRow });
                    } else {
                        var rValStr = $(this).find('.inp-hasil-kalibrasi').val();
                        if (!isNaN(parseFloat(rValStr))) {
                            var korekStr = $(this).find('.inp-koreksi').val();
                            var rn = { standar: rowStdStr, toleransi: rowTolStr, hasil: rValStr, koreksi: korekStr, status: statusRow };
                            if(hasBagian) rn.bagian = bag; resultJSON.push(rn);
                        }
                    }
                });
                $('#qa_hasil_json').val(JSON.stringify(resultJSON));
                updateKeputusanFinal();
            });

            $('#btn-simpan-qa').click(function() {
                var noAlat = $('#qa_nomor_alat').val();
                var statusQA = $('#qa_status_final').val();
                var jsonHasil = $('#qa_hasil_json').val(); 
                var format = $('#qa_format_alat').val();
                
                if (format === 'F') {
                    var parsedF = JSON.parse(jsonHasil || "[{}]")[0];
                    if(!parsedF.sec1 || !parsedF.sec2 || parsedF.sec2.length === 0 || !parsedF.sec3 || statusQA === '') {
                        BootstrapDialog.alert('Lengkapi semua Section Hasil Ukur dan Keputusan Final!'); return;
                    }
                } else {
                    var totalRow = $('#tabel_input_kalibrasi tbody tr').length;
                    var isiRow = JSON.parse(jsonHasil || "[]").length;
                    if(isiRow < totalRow || statusQA === '') { BootstrapDialog.alert('Lengkapi semua hasil ukur dan Keputusan!'); return; }
                }

                if (statusQA !== 'OK') {
                    BootstrapDialog.confirm({
                        title: 'PERINGATAN! ALAT NOT GOOD/RUSAK',
                        message: 'Keputusan Final alat ini adalah <b>NOT GOOD</b>.<br><br>Alat ini akan dipindahkan ke database barang rusak dan <b>tidak akan memiliki jadwal kalibrasi berikutnya</b>.<br>Yakin ingin melanjutkan?',
                        type: BootstrapDialog.TYPE_DANGER, btnCancelLabel: 'Batal', btnOKLabel: 'Ya, Lanjutkan', btnOKClass: 'btn-danger',
                        callback: function(result) {
                            if(result) eksekusiSimpanQA(noAlat, statusQA, jsonHasil, format);
                        }
                    });
                } else {
                    eksekusiSimpanQA(noAlat, statusQA, jsonHasil, format);
                }
            });

            function eksekusiSimpanQA(noAlat, statusQA, jsonHasil, format) {
                var btn = $('#btn-simpan-qa'); btn.html('<i class="fa fa-spinner fa-spin"></i> Submitting...'); btn.prop('disabled', true);

                $.ajax({
                    url: 'proses_qa.php?action=simpan_qa', type: 'POST', dataType: 'json',
                    data: { 
                        no_part: noAlat, status_qa: statusQA, hasil_json: jsonHasil, 
                        traceability: $('#qa_trace').val(),
                        nama_part: $('#qa_nama_alat').val(),
                        metode_uji: $('#qa_metode').val(),
                        standar_uji: $('#qa_standar_uji').val(),
                        toleransi_global: ($('#reg_pilih_format').val()==='F' ? $('#qa_toleransi_f_rendah').val()+'|'+$('#qa_toleransi_f_tinggi').val() : $('#qa_toleransi').val()),
                        suhu: $('#qa_suhu').val(), kelembapan: $('#qa_kelembapan').val(), ketidakpastian: $('#qa_ketidakpastian').val(),
                        confidence: $('#qa_confidence').val(), keterangan: $('#qa_keterangan').val()
                    },
                    success: function(res) {
                        btn.html('Submit Hasil QA'); btn.prop('disabled', false);
                        if(res.status === 'success') {
                            
                            var dataCetak = {
                                no_part: currentAlatData.no_part, 
                                nama_part: $('#qa_nama_alat').val(), 
                                seksi_pemilik: currentAlatData.seksi_pemilik, 
                                spesifikasi: currentAlatData.spesifikasi, 
                                resolusi: currentAlatData.resolusi, 
                                metode: $('#qa_metode').val(), 
                                standar_uji: $('#qa_standar_uji').val(), 
                                traceability: $('#qa_trace').val(),
                                suhu: $('#qa_suhu').val(), 
                                kelembapan: $('#qa_kelembapan').val(), 
                                ketidakpastian: $('#qa_ketidakpastian').val(), 
                                confidence_level: $('#qa_confidence').val(), 
                                toleransi_global: ($('#reg_pilih_format').val()==='F' ? $('#qa_toleransi_f_rendah').val()+'|'+$('#qa_toleransi_f_tinggi').val() : $('#qa_toleransi').val()), 
                                satuan: currentAlatData.satuan,
                                status_final: statusQA, 
                                tanggal_periksa: "<?php echo date('Y-m-d'); ?>", 
                                jadwal_berikutnya: (statusQA === 'OK' ? res.jadwal_selanjutnya : null), 
                                petugas: $('#qa_petugas').val(), 
                                keterangan: $('#qa_keterangan').val(), 
                                format: format,
                                hasil_array: JSON.parse(jsonHasil)
                            };
                            sessionStorage.setItem('cetak_data', JSON.stringify(dataCetak));

                            BootstrapDialog.confirm({
                                title: 'SUKSES!', type: BootstrapDialog.TYPE_SUCCESS, message: 'Laporan Kalibrasi Berhasil Disimpan.<br><br><b>Cetak Laporan Pemeriksaan?</b>',
                                btnCancelLabel: 'Tidak', btnOKLabel: 'Ya, Cetak', btnOKClass: 'btn-primary',
                                callback: function(result) {
                                    if(result) { window.location.href = 'cetak_laporan.php'; } 
                                    else { $('.hidden-form-section').slideUp(); $('#nomor_alat').prop('disabled', false).val('').focus(); $('#btn-cek').click();}
                                }
                            });
                        } else { BootstrapDialog.alert('Gagal: ' + res.message); }
                    }, error: function() { btn.html('Submit Hasil QA'); btn.prop('disabled', false); BootstrapDialog.alert('Error Submit!'); }
                });
            }

            $('#btn-simpan-alat').click(function() {
                var format = $('#reg_pilih_format').val();
                var regNo = $('#reg_nomor_alat').val().toUpperCase();
                var regNama = $('#reg_nama_alat').val().trim();
                var regSeksi = $('#reg_seksi').val();
                var regPeriode = $('#reg_periode').val().trim();
                var regSpek = $('#reg_spesifikasi').val().trim();
                var regRes = $('#reg_resolusi').val().trim();
                var regTol = $('#reg_toleransi').val().trim();
                var regSatuan = $('#reg_satuan').val().trim();
                var regMetode = $('#reg_metode').val().trim();
                var regStandarUji = $('#reg_standar_uji').val().trim();
                var regTrace = $('#reg_trace').val().trim();
                var regLokasi = $('#reg_lokasi').val().trim();
                
                if(regNama === '' || regSeksi === '' || regPeriode === '' || regLokasi === '' || regSpek === '' || regRes === '' || regTol === '' || regSatuan === '' || regMetode === '' || regStandarUji === '' || regTrace === '') {
                    BootstrapDialog.alert('<b>Gagal!</b> Semua form dengan tanda bintang merah wajib diisi secara lengkap!'); return;
                }

                var arrStandar = []; var validToleransiFormatN = true;
                var useBagian = $('.col-bagian').is(':visible');

                if(format === 'F') { 
                    var std1 = $('#reg_f_std_sec1').val().trim();
                    var std3 = $('#reg_f_std_sec3').val().trim();
                    var bw = $('#reg_f_batas_bawah').val().trim();
                    var at = $('#reg_f_batas_atas').val().trim();
                    if(std1 === '' || std3 === '') {
                        BootstrapDialog.alert('<b>Gagal!</b> Standar Repeatability dan Center Loading wajib diisi untuk Format F!'); return;
                    }
                    regTol = bw + '|' + at; // Override global toleransi
                    arrStandar.push({ meta: 'F', std_sec1: std1, std_sec3: std3 }); 
                }

                $('#body_reg_standar tr').each(function() {
                    var bg = $(this).find('.inp-reg-bagian').val();
                    var stdStr = $(this).find('.inp-reg-standar').val();
                    var tolStr = $(this).find('.inp-reg-toleransi').val();
                    
                    if(stdStr !== '') {
                        if(format === 'N' || format === 'R' || format === 'G' || format === 'F' || format === 'E') {
                            if(tolStr === '') validToleransiFormatN = false;
                            var cleanTol = tolStr.replace('-', ''); 
                            var r = { standar: stdStr, toleransi: cleanTol };
                            if(useBagian) r.bagian = bg; 
                            arrStandar.push(r);
                        } else { arrStandar.push(stdStr); }
                    }
                });

                if(arrStandar.length === (format==='F'?1:0)) { BootstrapDialog.alert('Minimal isi 1 Nilai Standar Uji di Tabel!'); return; }
                if((format === 'N' || format === 'R' || format === 'G' || format === 'F' || format === 'E') && !validToleransiFormatN) { BootstrapDialog.alert('Toleransi (±) di Tabel Wajib diisi secara lengkap!'); return; }

                var btn = $(this); btn.html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...'); btn.prop('disabled', true);

                var formData = new FormData();
                formData.append('no_part', regNo); formData.append('nama_part', regNama);
                formData.append('seksi', regSeksi); formData.append('periode', regPeriode);
                formData.append('ukuran', '');
                formData.append('resolusi', regRes); formData.append('lokasi', regLokasi);
                formData.append('tgl_manual', $('#reg_tgl_manual').val()); 
                formData.append('spesifikasi', regSpek); formData.append('toleransi_global', regTol);
                formData.append('satuan', regSatuan); formData.append('metode_uji', regMetode);
                formData.append('standar_uji', regStandarUji); formData.append('traceability', regTrace);
                formData.append('format_laporan', format); formData.append('titik_standar_json', JSON.stringify(arrStandar));
                if ($('#reg_foto_alat')[0].files[0]) { formData.append('foto_alat', $('#reg_foto_alat')[0].files[0]); }

                $.ajax({
                    url: 'proses_qa.php?action=simpan_alat', type: 'POST', dataType: 'json', data: formData, processData: false, contentType: false, 
                    success: function(res) {
                        btn.html('Simpan & Daftarkan Alat <i class="fa fa-save"></i>'); btn.prop('disabled', false);
                        if(res.status === 'success') {
                            BootstrapDialog.alert({ title: 'Berhasil Masuk DB', type: BootstrapDialog.TYPE_SUCCESS, message: 'Alat berhasil didaftarkan.', callback: function() { $('.hidden-form-section').slideUp(); $('#nomor_alat').prop('disabled', false).val(regNo); $('#btn-cek').click(); } });
                        } else { BootstrapDialog.alert('Error DB: ' + res.message); }
                    }, error: function() { btn.html('Simpan & Daftarkan Alat <i class="fa fa-save"></i>'); btn.prop('disabled', false); BootstrapDialog.alert('Koneksi Error!'); }
                });
            });



            /* =================================================================
             * PATCH REQUIREMENTS: A-R, historical snapshot, editable header/footer
             * -----------------------------------------------------------------
             * Patch ini sengaja menimpa handler lama saja; struktur halaman dan
             * renderer cetak yang sudah ada tetap digunakan.
             * ================================================================= */
            (function initQAPatch() {
                function qaEsc(v) {
                    return String(v == null ? '' : v)
                        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
                        .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
                }
                function qaNum(v) {
                    if (typeof v === 'number') return v;
                    if (v == null || String(v).trim() === '') return NaN;
                    let str = String(v).trim().replace(/,/g, '.');
                    let match = str.match(/-?\d+(\.\d+)?/);
                    if (match) {
                        return parseFloat(match[0]);
                    }
                    return NaN;
                }
                function qaJ(v, fallback) {
                    if (v == null || v === '') return fallback;
                    try { return JSON.parse(v); } catch (e) { return fallback; }
                }
                function qaStoredHistory(row) {
                    const raw = row && row.hasil_json ? row.hasil_json : '[]';
                    const j = qaJ(raw, []);
                    if (Array.isArray(j)) return { rows: j, snapshot: null };
                    if (j && typeof j === 'object') {
                        return { rows: Array.isArray(j.rows) ? j.rows : [], snapshot: j };
                    }
                    return { rows: [], snapshot: null };
                }
                function qaSnapshotFromHistory(row) {
                    const parsed = qaStoredHistory(row);
                    const snap = parsed.snapshot || {};
                    const h = snap.header || {};
                    const env = snap.environment || {};
                    const f = snap.footer || {};
                    return {
                        no_part: h.no_part || '',
                        nama_part: h.nama_part || '',
                        seksi_pemilik: h.seksi_pemilik || '',
                        spesifikasi: h.spesifikasi || '',
                        resolusi: h.resolusi || '',
                        lokasi: h.lokasi || '',
                        metode_uji: h.metode_uji || '',
                        standar_uji: h.standar_uji || '',
                        traceability: h.traceability || '',
                        toleransi_global: h.toleransi_global || '',
                        satuan: h.satuan || '',
                        format_laporan: h.format_laporan || '',
                        titik_standar_json: h.titik_standar_json || snap.titik_standar_json || '',
                        suhu: env.suhu != null ? env.suhu : (row && row.suhu || ''),
                        kelembapan: env.kelembapan != null ? env.kelembapan : (row && row.kelembapan || ''),
                        ketidakpastian: env.ketidakpastian != null ? env.ketidakpastian : (row && row.ketidakpastian || ''),
                        confidence_level: env.confidence_level != null ? env.confidence_level : (row && row.confidence_level || ''),
                        keterangan: f.keterangan != null ? f.keterangan : (row && row.keterangan || ''),
                        petugas: f.petugas || (row && row.petugas || ''),
                        operator: f.operator || (row && row.operator || '')
                    };
                }

                function qaRegSchema(format) {
                    const n = (keys, labels, rows) => ({keys, labels, rows});
                    switch (format) {
                        case 'A': return n(['posisi','std_spesifikasi','std_no_alat','std_out','tol_out','std_pitch','tol_pitch'], ['Posisi','STD Spesifikasi','STD No Alat','STD OUT Ø','Tol OUT Ø','STD PITCH Ø','Tol PITCH Ø'], [{posisi:'GO'},{posisi:'NOT GO'}]);
                        case 'B': return n(['tekanan','handle','baut','std_min','std_max'], ['Tekanan','Posisi Handle','Baut / Setting','Standar Min','Standar Max'], [{},{},{}]);
                        case 'C': return n(['setting','alat_std','toleransi'], ['Setting (SV)','Alat STD','Toleransi'], [{},{},{}]);
                        case 'D': return n(['unit','mode','setting','standar'], ['Unit','Mode','Setting','Standar'], [{},{},{}]);
                        case 'E': return n(['standar','toleransi'], ['Standar','Toleransi'], [{},{}]);
                        case 'F': return n(['standar'], ['Standar Scala Test'], [{},{},{}]);
                        case 'G': return n(['standar','toleransi'], ['Standar','Toleransi'], [{},{}]);
                        case 'H': return n(['posisi_ukur','std_spesifikasi','std_no_alat','std_out','tol_out','range_out','std_pitch','tol_pitch','range_pitch'], ['Posisi','STD Spesifikasi','STD No Alat','STD OUT','Tol OUT','Range OUT','STD PITCH','Tol PITCH','Range PITCH'], [{posisi_ukur:'GO'},{posisi_ukur:'NOT GO'},{posisi_ukur:'PLUG'}]);
                        case 'I': return n(['spesifikasi_alat','std_spesifikasi','std_no_alat'], ['Spesifikasi Alat yang Diperiksa','STD Spesifikasi','STD No Alat'], [{}]);
                        case 'J': return n(['alat_verifikasi'], ['Alat Verifikasi'], [{}]);
                        case 'K': return n(['nilai_ditentukan','toleransi_persen'], ['Nilai Ditentukan','Toleransi (%)'], [{},{},{}]);
                        case 'L': return n(['arah','applied_value','toleransi'], ['Uji','Applied','Toleransi'], [{arah:'NAIK'},{arah:'NAIK'},{arah:'TURUN'},{arah:'TURUN'}]);
                        case 'M': return n(['jenis','std_mm','toleransi_axis','std_degree','toleransi_sudut'], ['Jenis','STD AXIS (mm)','Tol AXIS','STD ANGLE (°)','Tol ANGLE (\')'], [{jenis:'AXIS'},{jenis:'ANGLE'}]);
                        case 'N': return n(['standar','toleransi'], ['Standar','Toleransi'], [{},{},{}]);
                        case 'O': return n(['spesifikasi','tol_plus','tol_minus','go_std','go_tol_near','go_tol_far','notgo_std','notgo_tol'], ['Spesifikasi','Tol +','Tol -','GO Alat','GO Tol Near','GO Tol Far','NOT GO Alat','NOT GO Tol'], [{}]);
                        case 'P': return n(['type','ukuran','upart','alat_nama','alat_no','std_label','min','max','actual_count'], ['Type','Ukuran','Upart','Alat Standar','No Alat STD','STD Ukuran','Min','Max','Jumlah Aktual'], [{}]);
                        case 'Q': return n(['subtype','std_spesifikasi','std_no_alat','std','tol_plus','tol_minus','min','max'], ['Subtipe','STD Spesifikasi','STD No Alat','Standar','Tol +','Tol -','Min','Max'], [{subtype:'SERATION'}]);
                        case 'R': return n(['bagian','standar','toleransi'], ['Posisi','Standar','Toleransi'], [{bagian:''},{bagian:''},{bagian:''}]);
                    }
                    return n(['standar','toleransi'], ['Standar','Toleransi'], [{}]);
                }

                function qaRenderRegTable(format, presetRows) {
                    const sc = qaRegSchema(format);
                    let rows = (presetRows && presetRows.length) ? presetRows : sc.rows;
                    
                    if (format === 'F') {
                        let metaRow = {std_sec1:'', std_sec3:''};
                        if (rows.length > 0 && rows[0].meta === 'F') {
                            metaRow = rows[0];
                            rows = rows.slice(1);
                        }
                        // Ensure we always have at least some rows for scale test if it was empty after slicing
                        if (rows.length === 0) rows = sc.rows;
                        
                        $('#reg_f_std_sec1').val(metaRow.std_sec1 || '');
                        $('#reg_f_std_sec3').val(metaRow.std_sec3 || '');
                    }
                    
                    if (format === 'Q' && (!presetRows || !presetRows.length)) {
                        rows = [
                            {subtype:'SERATION', bagian:'OUT'},
                            {subtype:'SERATION', bagian:'PITCH'}
                        ];
                    }
                    let h = '<tr><th style="width:5%;">No</th>';
                    if (format === 'Q') h += '<th>Bagian</th>';
                    sc.labels.forEach(function(label){ h += '<th>' + label + '</th>'; });
                    h += '<th style="width:14%;"><button type="button" class="btn btn-sm btn-success" id="btn_add_reg_row"><i class="fa fa-plus"></i> Tambah Baris</button></th></tr>';
                    $('#tabel_reg_standar thead').html(h);
                    let b = '';
                    rows.forEach(function(row, i){
                        b += '<tr>';
                        b += '<td class="row-num text-center">'+(i+1)+'</td>';
                        if (format === 'Q') {
                        b += '<td><b>'+qaEsc(row.bagian || (i === 0 ? 'OUT' : 'PITCH'))+'</b></td>';
                    }
                    sc.keys.forEach(function(key){
                            const value = row && row[key] != null ? row[key] : '';
                            const isSelect = (format === 'A' && key === 'posisi') || (format === 'H' && key === 'posisi_ukur') || (format === 'L' && key === 'arah') || (format === 'M' && key === 'jenis') || (format === 'Q' && key === 'subtype') || (format === 'R' && key === 'bagian') || (format === 'T' && key === 'posisi');
                            if (isSelect) {
                                let opts = [];
                                if (format === 'A' && key === 'posisi') opts = ['GO','NOT GO'];
                                if (format === 'H' && key === 'posisi_ukur') opts = ['GO','NOT GO','PLUG'];
                                if (format === 'L' && key === 'arah') opts = ['NAIK','TURUN'];
                                if (format === 'M' && key === 'jenis') opts = ['AXIS','ANGLE'];
                                if (format === 'Q' && key === 'subtype') opts = ['SERATION','PLUG'];
                                if (format === 'R' && key === 'bagian') opts = ['', 'Ø OUT', 'Ø DALAM', 'HEIGHT'];
                                if (format === 'T' && key === 'posisi') opts = ['GO','NOT GO'];
                                b += '<td><select class="form-control qa-reg-field" data-key="'+key+'">' + opts.map(function(o){ var lbl=(format === 'R' && key === 'bagian' && o === '') ? '-- Pilih Posisi --' : o; return '<option value="'+qaEsc(o)+'" '+(String(value).toUpperCase()===String(o).toUpperCase()?'selected':'')+'>'+qaEsc(lbl)+'</option>'; }).join('') + '</select></td>';
                            } else {
                                b += '<td><input type="text" class="form-control qa-reg-field" data-key="'+key+'" value="'+qaEsc(value)+'"></td>';
                            }
                        });
                        b += '<td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-hapus-std"><i class="fa fa-trash"></i></button></td></tr>';
                    });
                    $('#body_reg_standar').html(b);
                    $('#tabel_reg_standar').data('qa-format', format);
                    $('.col-bagian,.col-toleransi').hide();
                    $('#container_btn_bagian').hide();
                }

                function qaCollectReg(format) {
                    const rows = [];
                    $('#body_reg_standar tr').each(function(){
                        const obj = {};
                        $(this).find('.qa-reg-field').each(function(){ obj[$(this).data('key')] = $(this).val(); });
                        if (Object.keys(obj).length) rows.push(obj);
                    });
                    if (format === 'F') {
                        const sec1 = $('#reg_f_std_sec1').val().trim();
                        const sec3 = $('#reg_f_std_sec3').val().trim();
                        rows.unshift({meta:'F', std_sec1:sec1, std_sec3:sec3});
                    }
                    return rows;
                }

                $(document).off('click.qaAddRegRow', '#btn_add_reg_row').on('click.qaAddRegRow', '#btn_add_reg_row', function(){
                    const format = String($('#reg_pilih_format').val() || '').toUpperCase();
                    if (!format) return;
                    let rows = qaCollectReg(format);
                    if (format === 'F' && rows.length && rows[0].meta === 'F') rows = rows.slice(1);
                    const blank = {};
                    if (format === 'Q') blank.subtype = 'SERATION';
                    rows.push(blank);
                    qaRenderRegTable(format, rows);
                    $('#body_reg_standar tr:last .qa-reg-field').first().focus();
                });

                function qaEnsureUpdateExtras() {
                    if (!$('#qa_spesifikasi').length) {
                        $('#qa_metode').closest('.form-group').before('<div class="form-group" id="qa-extra-header-row"><label class="col-sm-2 control-label" id="label-qa-spesifikasi">Spesifikasi <span style="float:right;">:</span></label><div class="col-sm-4"><input type="text" id="qa_spesifikasi" class="form-control"></div><label class="col-sm-2 control-label">Resolusi <span style="float:right;">:</span></label><div class="col-sm-4"><input type="text" id="qa_resolusi" class="form-control"></div></div>');
                    }
                    if (!$('#qa_type').length) {
                        $('#qa-extra-header-row').after('<div class="form-group" id="qa-type-row" style="display:none;"><label class="col-sm-2 control-label">Type <span style="float:right;">:</span></label><div class="col-sm-4"><input type="text" id="qa_type" class="form-control text-uppercase-input"></div></div>');
                    }
                    if (!$('#qa_kode_warna').length) {
                        $('#qa_keterangan').closest('.row').before('<div class="row qa-report-meta-row" id="qa-report-meta-row"><div class="col-md-6"><div class="form-group" style="margin-left:0;margin-right:0;"><label>Kode Warna</label><input type="text" id="qa_kode_warna" class="form-control"></div></div><div class="col-md-6"><div class="form-group" style="margin-left:0;margin-right:0;"><label>No. Gambar / Referensi (opsional)</label><input type="text" id="qa_no_gambar" class="form-control"></div></div></div>');
                    }
                }

                function qaFillCommon(alat, history) {
                    qaEnsureUpdateExtras();
                    const latest = history && history.length ? history[0] : null;
                    const snap = latest ? qaSnapshotFromHistory(latest) : {};
                    const get = function(name, fallback){ return snap[name] !== '' && snap[name] != null ? snap[name] : (alat[name] != null ? alat[name] : (fallback || '')); };
                    $('#qa_nomor_alat').val(alat.no_part || '');
                    $('#qa_nama_alat').val(get('nama_part', ''));
                    $('#qa_spesifikasi').val(String(alat.format_laporan || '').toUpperCase() === 'O' ? getAlatSpesifikasi(alat) : get('spesifikasi', ''));
                    $('#qa_resolusi').val(get('resolusi', ''));
                    $('#qa_metode').val(get('metode_uji','-'));
                    $('#qa_standar_uji').val(get('standar_uji','-'));
                    $('#qa_trace').val(get('traceability','-'));
                    $('#qa_toleransi').val(get('toleransi_global', ''));
                    if(get('format_laporan', '')==='F' && (get('toleransi_global','')||'').includes('|')){ const ft=get('toleransi_global','').split('|'); $('#qa_toleransi_f_rendah').val(ft[0]); $('#qa_toleransi_f_tinggi').val(ft[1]); }
                    $('#qa_suhu').val(snap.suhu || (latest && latest.suhu) || '');
                    $('#qa_kelembapan').val(snap.kelembapan || (latest && latest.kelembapan) || '');
                    $('#qa_ketidakpastian').val(snap.ketidakpastian || (latest && latest.ketidakpastian) || '');
                    $('#qa_confidence').val(snap.confidence_level || (latest && latest.confidence_level) || '95 %');
                    // Keterangan saat UPDATE selalu kosong: petugas mengisi manual.
                    // Histori lama tetap disimpan dan tetap dapat dicetak dari snapshot histori.
                    $('#qa_keterangan').val('');
                    $('#qa_kode_warna').val('');
                    $('#qa_no_gambar').val('');
                    $('#qa_petugas').val('<?php echo addslashes($nama_petugas); ?>');
                    $('#qa_format_alat').val(alat.format_laporan || 'A');
                    qaApplyUpdateMetaVisibility(String(alat.format_laporan || 'A').toUpperCase());
                    $('#qa_resolusi_value').val(get('resolusi',''));
                    $('.qa_satuan_label').text(alat.satuan || '');
                    $('.qa_satuan_kurung').html('( '+qaEsc(alat.satuan || '')+' )');
                    $('#qa_toleransi_value').val(alat.toleransi_global || '');
                }

                function qaStandardPoints(alat) {
                    const pts = qaJ(alat.titik_standar_json || '[]', []);
                    if (Array.isArray(pts)) {
                        if (String(alat.format_laporan || '').toUpperCase() === 'F') {
                            pts.forEach(p => {
                                if (p.meta !== 'F' && (p.standar == null || p.standar === '')) {
                                    const bb = p.batas_bawah || p.rendah || p.low || '';
                                    const ba = p.batas_atas || p.tinggi || p.high || '';
                                    if (bb !== '') {
                                        p.standar = bb;
                                        p.zona = 'B';
                                    } else if (ba !== '') {
                                        p.standar = ba;
                                        p.zona = 'A';
                                    }
                                }
                            });
                        }
                        return pts;
                    }
                    return [];
                }

                function qaApplyUpdateMetaVisibility(format){
                    format = String(format || 'A').toUpperCase();
                    // Ketidakpastian tidak dipakai pada Format N, tetapi Confidence Level tetap dipakai.
                    // FORMAT U UPDATE V3 - TAMPILKAN KETIDAKPASTIAN + CONFIDENCE LEVEL
                    const showUncertainty = ['L','M','F','U','V'].includes(format);
                    const showConfidence = ['L','M','F','N','U','V'].includes(format);
                    $('#qa_ketidakpastian').closest('.col-md-6').toggle(showUncertainty);
                    $('#qa_confidence').closest('.col-md-6').toggle(showConfidence);
                    if(!showUncertainty){
                        $('#qa_ketidakpastian').val('');
                    }
                    if(!showConfidence){
                        $('#qa_confidence').val('95 %');
                    }

                    // Kode warna hanya tersedia pada A, H, I, O, Q.
                    const showColor = ['A','H','I','O','Q'].includes(format);
                    const $color = $('#qa_kode_warna');
                    if($color.length){
                        $color.closest('.col-md-6').toggle(showColor);
                        if(!showColor) $color.val('');
                    }

                    // Traceability disembunyikan pada Input Update Hasil Kalibrasi Format I.
                    const $trace = $('#qa_trace');
                    if($trace.length){
                        $trace.closest('.col-md-6').toggle(format !== 'I');
                    }

                    // No. Gambar hanya tersedia pada O dan P.
                    const showNoGambar = ['O','P'].includes(format);
                    const $noGambar = $('#qa_no_gambar');
                    if($noGambar.length){
                        $noGambar.closest('.col-md-6').toggle(showNoGambar);
                        if(!showNoGambar) $noGambar.val('');
                    }
                }

                function qaStandardKeys(format) {
                    switch(String(format || '').toUpperCase()) {
                        case 'A': return ['posisi','std_spesifikasi','std_no_alat','std_out','tol_out','std_pitch','tol_pitch'];
                        case 'B': return ['std_min','std_max'];
                        case 'C': return ['toleransi'];
                        case 'D': return ['standar'];
                        case 'E': return ['standar','toleransi'];
                        case 'F': return ['standar','zona'];
                        case 'G': return ['standar','toleransi'];
                        case 'H': return ['posisi_ukur','std_spesifikasi','std_no_alat','std_out','tol_out','range_out','std_pitch','tol_pitch','range_pitch'];
                        case 'I': return ['spesifikasi_alat','std_spesifikasi','std_no_alat'];
                        case 'J': return ['spesifikasi','spesifikasi_alat_verifikasi'];
                        case 'K': return ['nilai_ditentukan','toleransi_persen'];
                        case 'L': return ['toleransi'];
                        case 'M': return ['jenis','std_mm','toleransi_axis','std_degree','toleransi_sudut'];
                        case 'N': return ['standar','toleransi'];
                        case 'O': return ['spesifikasi','tol_plus','tol_minus','go_std','go_tol_near','go_tol_far','notgo_std','notgo_tol'];
                        case 'P': return ['std_label','min','max','actual_count','alat_nama','alat_no'];
                        case 'Q': return ['subtype','std_spesifikasi','std_no_alat','std','tol_plus','tol_minus','min','max'];
                        case 'R': return ['bagian','standar','toleransi'];
                        case 'V': return ['bagian','standar'];
                        case 'W': return ['standar'];
                        default: return [];
                    }
                }

                function qaReadonly(value, extra){
                    return '<input type="text" class="form-control qa-readonly" readonly value="'+qaEsc(value)+'"'+(extra||'')+'>';
                }
                function qaField(value, key, extra){
                    return '<input type="text" class="form-control qa-update-field" data-key="'+key+'" value="'+qaEsc(value == null ? '' : value)+'"'+(extra||'')+'>';
                }
                function qaSelect(value, key, opts){
                    const selectedValue = String(value == null ? '' : value).trim().toUpperCase();

                    return '<select class="form-control qa-update-field" data-key="'+key+'">'+
                        opts.map(function(o){
                            const optionValue = String(o == null ? '' : o).trim().toUpperCase();

                            return '<option '+
                                (selectedValue === optionValue ? 'selected' : '')+
                                '>'+o+'</option>';
                        }).join('')+
                    '</select>';
                }

                var qaNColumns = [{key:'hasil_0',label:'Hasil'}];

                function qaCaptureNTable(){
                    var data=[];
                    $('#tabel_input_kalibrasi tbody tr[data-index]').each(function(){
                        var $tr=$(this), item={standar:$tr.find('[data-n-key="standar"]').val()||'',toleransi:$tr.find('[data-n-key="toleransi"]').val()||'',posisi_hasil:[]};
                        qaNColumns.forEach(function(col,j){ item.posisi_hasil.push({posisi:col.label,label:col.label,hasil:$tr.find('[data-n-result="'+j+'"]').val()||'',koreksi:''}); });
                        data.push(item);
                    });
                    return data;
                }

                function qaRenderUpdateTable(format, points) {
                    qaApplyUpdateMetaVisibility(format);
                    const $th = $('#tabel_input_kalibrasi thead'), $tb = $('#tabel_input_kalibrasi tbody');
                    let head='', body='';
                    if (format === 'A') {
                        head='<tr><th>No</th><th>Posisi</th><th>STD Spesifikasi</th><th>STD No Alat</th><th>STD OUT Ø</th><th>STD PITCH Ø</th><th>Actual OUT I</th><th>Actual OUT II</th><th>Actual OUT III</th><th>Actual PITCH I</th><th>Actual PITCH II</th><th>Actual PITCH III</th><th>Status</th></tr>';
                        points.forEach(function(p,i){ body+='<tr data-index="'+i+'" data-tol-out="'+qaEsc(p.tol_out == null ? '' : p.tol_out)+'" data-tol-pitch="'+qaEsc(p.tol_pitch == null ? '' : p.tol_pitch)+'"><td>'+(i+1)+'</td><td><b>'+qaEsc(p.posisi||'')+'</b></td><td>'+qaReadonly(p.std_spesifikasi)+'</td><td>'+qaReadonly(p.std_no_alat)+'</td><td>'+qaReadonly(p.std_out)+'<div class="qa-hidden-std" data-key="std_out">'+qaEsc(p.std_out)+'</div></td><td>'+qaReadonly(p.std_pitch)+'<div class="qa-hidden-std" data-key="std_pitch">'+qaEsc(p.std_pitch)+'</div></td><td>'+qaField('','out1')+'</td><td>'+qaField('','out2')+'</td><td>'+qaField('','out3')+'</td><td>'+qaField('','pitch1')+'</td><td>'+qaField('','pitch2')+'</td><td>'+qaField('','pitch3')+'</td><td class="qa-row-status">-</td></tr>'; });
                    } else if (format === 'B') {
                        head='<tr><th>No</th><th>Tekanan</th><th>Posisi Handle</th><th>Baut / Setting</th><th>Standar Min</th><th>Standar Max</th><th>Hasil 1</th><th>Hasil 2</th><th>Hasil 3</th><th>Rata-rata</th><th>Status</th></tr>';
                        points.forEach(function(p,i){body+='<tr data-index="'+i+'"><td>'+(i+1)+'</td><td>'+qaField(p.tekanan,'tekanan')+'</td><td>'+qaField(p.handle,'handle')+'</td><td>'+qaField(p.baut,'baut')+'</td><td>'+qaReadonly(p.std_min)+' </td><td>'+qaReadonly(p.std_max)+'</td><td>'+qaField('','std1')+'</td><td>'+qaField('','std2')+'</td><td>'+qaField('','std3')+'</td><td class="qa-rata"></td><td class="qa-row-status">-</td></tr>';});
                    } else if (format === 'C') {
                        head='<tr><th>No</th><th>Setting (SV)</th><th>Actual (PV)</th><th>Alat STD</th><th>Selisih</th><th>Status</th></tr>';
                        points.forEach(function(p,i){body+='<tr data-index="'+i+'"><td>'+(i+1)+'</td><td>'+qaField(p.setting,'setting')+'</td><td>'+qaField('','actual')+'</td><td>'+qaField(p.alat_std,'alat_std')+'</td><td class="qa-diff"></td><td class="qa-row-status">-</td></tr>';});
                    } else if (format === 'D') {
                        head='<tr><th>No</th><th>Unit</th><th>Mode</th><th>Setting</th><th>Standar</th><th>Hasil</th><th>Koreksi</th><th>Status</th></tr>';
                        points.forEach(function(p,i){body+='<tr data-index="'+i+'"><td>'+(i+1)+'</td><td>'+qaField(p.unit,'unit')+'</td><td>'+qaField(p.mode,'mode')+'</td><td>'+qaField(p.setting,'setting')+'</td><td>'+qaReadonly(p.standar)+'</td><td>'+qaField('','hasil')+'</td><td class="qa-koreksi"></td><td class="qa-row-status">-</td></tr>';});
                    } else if (format === 'E') {
                        head='<tr><th>No</th><th>Standar</th><th>Toleransi</th><th>T1 Hasil</th><th>T1 Koreksi</th><th>T2 Hasil</th><th>T2 Koreksi</th><th>Status</th></tr>';
                        points.forEach(function(p,i){body+='<tr data-index="'+i+'"><td>'+(i+1)+'</td><td>'+qaReadonly(p.standar)+'</td><td>'+qaReadonly(p.toleransi)+'</td><td>'+qaField('','t1_hasil')+'</td><td class="qa-t1-kor"></td><td>'+qaField('','t2_hasil')+'</td><td class="qa-t2-kor"></td><td class="qa-row-status">-</td></tr>';});
                    } else if (format === 'F') {
                        const meta = points.find(function(x){return x.meta==='F';}) || {std_sec1:'',std_sec3:''};
                        const scale = points.filter(function(x){return x.meta!=='F';});
                        
                        head = '<tr><th style="width:5%; text-align:center;">Bagian</th><th style="width:15%; text-align:center;">NO</th><th style="width:20%; text-align:center;">Standar</th><th style="width:20%; text-align:center;">Hasil / Pengukuran</th><th style="width:20%; text-align:center;">Koreksi / Deviasi</th><th style="width:20%; text-align:center;">Status</th></tr>';
                        
                        body += '<tr style="background-color:#f9f9f9;"><td rowspan="2" style="vertical-align:middle; text-align:center; font-weight:bold;">I</td><td colspan="5" style="font-weight:bold;">KEMAMPUAN ULANG ( REPEATABILITY )</td></tr>';
                        body += '<tr style="background-color:#f9f9f9;"><td></td><td style="text-align:center; vertical-align:middle;">'+qaReadonly(meta.std_sec1)+'</td><td style="text-align:center; vertical-align:middle;">'+qaField(meta.sec1_hasil || meta.std_sec1,'sec1_hasil')+'</td><td class="qa-sec1-dev text-center" style="vertical-align:middle;"></td><td></td></tr>';
                        
                        body += '<tr><td rowspan="'+(scale.length+1)+'" style="vertical-align:middle; text-align:center; font-weight:bold;">II</td><td colspan="5" style="font-weight:bold; text-align:center;">NILAI SCALA ( SCALA TEST )</td></tr>';
                        scale.forEach(function(p,i){
                            const bg = i % 2 === 0 ? 'background-color:#ffffff;' : 'background-color:#f2f2f2;';
                            body+='<tr data-index="'+i+'" style="'+bg+'"><td style="text-align:center; vertical-align:middle;">'+(i+1)+'</td><td style="text-align:center; vertical-align:middle;">'+qaReadonly(p.standar)+'</td><td style="text-align:center; vertical-align:middle;">'+qaField(p.sec2_hasil || p.standar,'sec2_hasil')+'</td><td class="qa-sec2-kor text-center" style="vertical-align:middle;"></td><td class="qa-row-status text-center" style="vertical-align:middle;">-</td></tr>';
                        });
                        
                        body += '<tr style="background-color:#e8f4f8;"><td rowspan="3" style="vertical-align:middle; text-align:center; font-weight:bold;">III</td><td colspan="5" style="font-weight:bold;">EFECT CENTER LOADING ( Standar: '+qaEsc(meta.std_sec3||'')+')</td></tr>';
                        body += '<tr style="background-color:#e8f4f8;"><td colspan="5"><div style="display:flex; justify-content:space-around; text-align:center;">' +
                                '<div><b>Tengah</b><br>'+qaField(meta.tengah || meta.std_sec3,'tengah')+'</div>' +
                                '<div><b>Belakang</b><br>'+qaField(meta.belakang || meta.std_sec3,'belakang')+'</div>' +
                                '<div><b>Kanan</b><br>'+qaField(meta.kanan || meta.std_sec3,'kanan')+'</div>' +
                                '<div><b>Depan</b><br>'+qaField(meta.depan || meta.std_sec3,'depan')+'</div>' +
                                '<div><b>Kiri</b><br>'+qaField(meta.kiri || meta.std_sec3,'kiri')+'</div>' +
                                '</div></td></tr>';
                        body += '<tr style="background-color:#e8f4f8;"><td><b>Perbedaan Maks</b></td><td colspan="4" class="qa-sec3-diff text-center" style="vertical-align:middle; font-size:16px; font-weight:bold;"></td></tr>';
                    } else if (format === 'G') {
                        head='<tr><th>No</th><th>Standar</th><th>Toleransi</th><th>Hasil 1</th><th>Hasil 2</th><th>Status</th></tr>';
                        points.forEach(function(p,i){body+='<tr data-index="'+i+'"><td>'+(i+1)+'</td><td>'+qaReadonly(p.standar)+'</td><td>'+qaReadonly(p.toleransi)+'</td><td>'+qaField('','hasil')+'</td><td>'+qaField('','hasil_2')+'</td><td class="qa-row-status">-</td></tr>';});
                    } else if (format === 'H') {
                        head='<tr><th>No</th><th>Posisi</th><th>STD OUT</th><th>STD PITCH</th><th>OUT I</th><th>OUT II</th><th>OUT III</th><th>PITCH I</th><th>PITCH II</th><th>PITCH III</th><th>Status</th></tr>';
                        points.forEach(function(p,i){body+='<tr data-index="'+i+'"><td>'+(i+1)+'</td><td>'+qaEsc(p.posisi_ukur||'')+'</td><td>'+qaReadonly(p.std_out)+'</td><td>'+qaReadonly(p.std_pitch)+'</td><td>'+qaField('','out_dep')+'</td><td>'+qaField('','out_ten')+'</td><td>'+qaField('','out_bel')+'</td><td>'+qaField('','pitch_dep')+'</td><td>'+qaField('','pitch_ten')+'</td><td>'+qaField('','pitch_bel')+'</td><td class="qa-row-status">-</td></tr>';});
                    } else if (format === 'I') {
                        head='<tr><th>No</th><th>STD Spesifikasi</th><th>STD No Alat</th><th>ATAS</th><th>BAWAH</th><th>Kode Warna</th><th>Status</th></tr>';
                        points.forEach(function(p,i){body+='<tr data-index="'+i+'"><td>'+(i+1)+'</td><td>'+qaReadonly(p.std_spesifikasi)+'</td><td>'+qaReadonly(p.std_no_alat)+'</td><td>'+qaField('','hasil_atas')+'</td><td>'+qaField('','hasil_bawah')+'</td><td>'+qaField(p.kode_warna||'','kode_warna')+'</td><td class="qa-row-status">-</td></tr>';});
                    } else if (format === 'J') {
                        head='<tr><th>No</th><th>No Alat</th><th>Spesifikasi</th><th>Alat Verifikasi</th><th>Hasil</th></tr>';
                        points.forEach(function(p,i){body+='<tr data-index="'+i+'"><td>'+(i+1)+'</td><td>'+qaReadonly(alatForNoPart())+'</td><td>'+qaReadonly($('#qa_spesifikasi').val())+'</td><td>'+qaField(p.alat_verifikasi,'alat_verifikasi')+'</td><td>'+qaSelect(p.hasil_verifikasi||'O','hasil_verifikasi',['O','X'])+'</td></tr>';});
                    } else if (format === 'K') {
                        head='<tr><th>No</th><th>Nilai Ditentukan</th><th>Nilai Pengukuran</th><th>Selisih</th><th>Status</th></tr>';
                        points.forEach(function(p,i){body+='<tr data-index="'+i+'"><td>'+(i+1)+'</td><td>'+qaReadonly(p.nilai_ditentukan)+'</td><td>'+qaField('','nilai_pengukuran')+'</td><td class="qa-selisih"></td><td class="qa-row-status">-</td></tr>';});
                    } else if (format === 'L') {
                        head='<tr><th>No</th><th>Uji</th><th>Applied</th><th>Indicated</th><th>Correction</th><th>Status</th></tr>';
                        points.forEach(function(p,i){body+='<tr data-index="'+i+'"><td>'+(i+1)+'</td><td>'+qaSelect(p.arah||'NAIK','arah',['NAIK','TURUN'])+'</td><td>'+qaField(p.applied_value,'applied_value')+'</td><td>'+qaField('','indicated_value')+'</td><td class="qa-koreksi"></td><td class="qa-row-status">-</td></tr>';});
                    } else if (format === 'M') {
                        head='<tr><th>Jenis</th><th>STD AXIS (mm)</th><th>X Left</th><th>Corr</th><th>X Right</th><th>Corr</th><th>Y Front</th><th>Corr</th><th>Y Back</th><th>Corr</th><th>STD ANGLE (°)</th><th>Hasil °</th><th>\'</th><th>\"</th><th>Corr</th></tr>';
                        points.forEach(function(p,i){ if(String(p.jenis||'').toUpperCase()==='ANGLE') body+='<tr data-index="'+i+'"><td>ANGLE</td><td colspan="9">-</td><td>'+qaReadonly(p.std_degree)+'</td><td>'+qaField('','hasil_degree')+'</td><td>'+qaField('','hasil_minute')+'</td><td>'+qaField('','hasil_second')+'</td><td>'+qaField('','correction')+'</td></tr>'; else body+='<tr data-index="'+i+'"><td>AXIS</td><td>'+qaReadonly(p.std_mm)+'</td><td>'+qaField('','x_left')+'</td><td>'+qaField('','x_left_correction')+'</td><td>'+qaField('','x_right')+'</td><td>'+qaField('','x_right_correction')+'</td><td>'+qaField('','y_front')+'</td><td>'+qaField('','y_front_correction')+'</td><td>'+qaField('','y_back')+'</td><td>'+qaField('','y_back_correction')+'</td><td colspan="5">-</td></tr>';});
                    } else if (format === 'N') {
                        qaNColumns = [{key:'hasil_0',label:'Hasil'}];
                        head='<tr><th style="width:6%">No</th><th style="width:24%">Standar</th><th style="width:18%">Toleransi</th><th style="width:24%">Hasil</th><th style="width:16%">Koreksi</th><th style="width:12%">Status</th></tr>';
                        points.forEach(function(p,i){
                            var vals=Array.isArray(p.posisi_hasil)?p.posisi_hasil:[];
                            var ex=(vals[0]&&vals[0].hasil!=null)?vals[0].hasil:(p.hasil||'');
                            body+='<tr data-index="'+i+'"><td>'+(i+1)+'</td><td>'+qaReadonly(p.standar)+'<input type="hidden" data-n-key="standar" value="'+qaEsc(p.standar||'')+'"></td><td>'+qaReadonly(p.toleransi)+'<input type="hidden" data-n-key="toleransi" value="'+qaEsc(p.toleransi||'')+'"></td><td>'+qaField(ex,'hasil_pos_0',' data-n-result="0"')+'</td><td class="qa-koreksi"></td><td class="qa-row-status">-</td></tr>';
                        });
                    } else if (format === 'O') {
                        const p=points[0]||{};
                        head='<tr><th>Posisi Ukur</th><th>Standar</th><th>Toleransi</th><th>Min</th><th>Max</th><th>Hasil Masuk</th><th>Hasil Tidak Masuk</th><th>Status</th></tr>';
                        body='<tr data-index="0" data-o-row="go"><td><b>GO</b></td><td>'+qaReadonly(p.go_std)+'</td><td>'+qaReadonly(p.go_tol_near||p.go_tol_far)+'</td><td>'+qaReadonly(p.go_min)+'</td><td>'+qaReadonly(p.go_max)+'</td><td>'+qaSelect('','go_hasil_min',['OK','TIDAK'])+'</td><td>'+qaSelect('','go_hasil_max',['OK','TIDAK'])+'</td><td class="qa-row-status">-</td></tr>'+
                             '<tr data-index="1" data-o-row="notgo"><td><b>NOT GO</b></td><td>'+qaReadonly(p.notgo_std)+'</td><td>'+qaReadonly(p.notgo_tol)+'</td><td>'+qaReadonly(p.notgo_min)+'</td><td>'+qaReadonly(p.notgo_max)+'</td><td>'+qaSelect('','notgo_hasil_min',['OK','TIDAK'])+'</td><td>'+qaSelect('','notgo_hasil_max',['OK','TIDAK'])+'</td><td class="qa-row-status">-</td></tr>';
                    } else if (format === 'P') {
                        head='<tr><th>No</th><th>Type dan Ukuran</th><th>Nama Alat yang Dipakai</th><th>No Alat</th><th>STD Ukuran</th><th>Min</th><th>Max</th><th>Aktual</th><th>Status</th></tr>';
                        points.forEach(function(p,i){
                            const std=qaNum(p.std_label), mode=String(p.toleransi_mode||((p.tol_plus!==undefined||p.tol_minus!==undefined)?'range':'pm')); let min=qaNum(p.min),max=qaNum(p.max),stdText=p.std_label||'';
                            if(mode==='range'){const a=qaNum(p.tol_plus),b=qaNum(p.tol_minus);if(Number.isFinite(std)&&Number.isFinite(a)&&Number.isFinite(b)){min=std+Math.min(a,b);max=std+Math.max(a,b);} if(p.tol_plus!==''||p.tol_minus!=='')stdText+=(p.tol_plus!==''?(' '+(String(p.tol_plus).charAt(0)==='-'?'':'+')+p.tol_plus):'')+'/'+(p.tol_minus!==''?((String(p.tol_minus).charAt(0)==='-'?'':'+')+p.tol_minus):'');}
                            else {const t=qaNum(p.toleransi);if(Number.isFinite(std)&&Number.isFinite(t)){min=std-Math.abs(t);max=std+Math.abs(t);}if(p.toleransi)stdText+=' ±'+p.toleransi;}
                            const fmtRange=function(v){return Number.isFinite(v)?Number(v.toFixed(6)).toString():'';};
                            body+='<tr data-index="'+i+'"><td>'+(i+1)+'</td><td>'+qaReadonly(p.type_ukuran||p.type)+'</td><td>'+qaReadonly(p.alat_nama)+'</td><td>'+qaReadonly(p.alat_no)+'</td><td>'+qaReadonly(stdText)+'</td><td>'+qaReadonly(fmtRange(min))+'</td><td>'+qaReadonly(fmtRange(max))+'</td><td>'+qaField('','actual1')+'</td><td class="qa-row-status">-</td></tr>';
                        });
                    } else if (format === 'Q') {
                        const subtype = String((points[0]||{}).subtype||'SERATION').toUpperCase();
                        head='<tr><th>Subtipe</th><th>STD Spesifikasi</th><th>STD No Alat</th><th>Standar</th><th>Tol+</th><th>Tol-</th><th>Min</th><th>Max</th><th>Actual 1</th><th>Actual 2</th><th>Actual 3</th><th>Actual 4</th><th>Status</th></tr>';
                        points.forEach(function(p,i){body+='<tr data-index="'+i+'"><td>'+qaSelect(p.subtype||subtype,'subtype',['SERATION','PLUG'])+'</td><td>'+qaReadonly(p.std_spesifikasi)+'</td><td>'+qaReadonly(p.std_no_alat)+'</td><td>'+qaReadonly(p.std)+'</td><td>'+qaReadonly(p.tol_plus)+'</td><td>'+qaReadonly(p.tol_minus)+'</td><td>'+qaReadonly(p.min)+'</td><td>'+qaReadonly(p.max)+'</td><td>'+qaField('','actual1')+'</td><td>'+qaField('','actual2')+'</td><td>'+qaField('','actual3')+'</td><td>'+qaField('','actual4')+'</td><td class="qa-row-status">-</td></tr>';});
                    } else if (format === 'R') {
                        head='<tr><th>No</th><th>Standar</th><th>Toleransi</th><th>Depan</th><th>Tengah</th><th>Belakang</th><th>Status</th></tr>';
                        points.forEach(function(p,i){body+='<tr data-index="'+i+'"><td>'+(i+1)+'</td><td>'+qaReadonly(p.standar)+'</td><td>'+qaReadonly(p.toleransi)+'</td><td>'+qaField('','depan')+'</td><td>'+qaField('','tengah')+'</td><td>'+qaField('','belakang')+'</td><td class="qa-row-status">-</td></tr>';});
                    }
                    $th.html(head); $tb.html(body);
                    $('#tabel_input_kalibrasi').addClass('qa-patched-table');
                    $('#tabel_input_kalibrasi .qa-hidden-std').hide();
                    $('#btn_add_update_row').hide();
                    $('#btn_add_n_column, #qa_n_column_help').hide();
                    bindQaCalculations(format);
                }

                function alatForNoPart(){ return $('#qa_nomor_alat').val() || (currentAlatData && currentAlatData.no_part) || ''; }

                function qaTolCommon(){ return Math.abs(qaNum($('#qa_toleransi').val())); }
                function qaSetRowStatus($tr, ok){
                    const text = ok === null ? '-' : (ok ? 'OK' : 'NOT GOOD');
                    $tr.find('.qa-row-status').text(text).removeClass('text-success text-danger').addClass(ok === null ? '' : (ok ? 'text-success' : 'text-danger'));
                }
                function bindQaCalculations(format){
                    $('#tabel_input_kalibrasi').off('input.qaCalc change.qaCalc').on('input.qaCalc change.qaCalc', '.qa-update-field', function(){
                        const $tr=$(this).closest('tr');
                        if(format==='A'){
                            const stdOut=qaNum($tr.find('[data-key="std_out"]').val());
                            const stdPitch=qaNum($tr.find('[data-key="std_pitch"]').val());
                            const tolOut=Math.abs(qaNum($tr.attr('data-tol-out')));
                            const tolPitch=Math.abs(qaNum($tr.attr('data-tol-pitch')));
                            const outVals=['out1','out2','out3'].map(k=>qaNum($tr.find('[data-key="'+k+'"]').val()));
                            const pitchVals=['pitch1','pitch2','pitch3'].map(k=>qaNum($tr.find('[data-key="'+k+'"]').val()));
                            const allFilled=outVals.every(Number.isFinite)&&pitchVals.every(Number.isFinite);
                            const validStd=Number.isFinite(stdOut)&&Number.isFinite(stdPitch)&&Number.isFinite(tolOut)&&Number.isFinite(tolPitch);
                            if(!allFilled||!validStd){ qaSetRowStatus($tr,null); }
                            else {
                                const okOut=outVals.every(v=>Math.abs(v-stdOut)<=tolOut);
                                const okPitch=pitchVals.every(v=>Math.abs(v-stdPitch)<=tolPitch);
                                qaSetRowStatus($tr,okOut&&okPitch);
                            }
                        } else if(format==='B'){
                            const vals=['std1','std2','std3'].map(k=>qaNum($tr.find('[data-key="'+k+'"]').val())).filter(Number.isFinite); const min=qaNum($tr.find('[data-key="std_min"]').val()), max=qaNum($tr.find('[data-key="std_max"]').val());
                            if(vals.length){const avg=vals.reduce((a,b)=>a+b,0)/vals.length;$tr.find('.qa-rata').text(avg.toFixed(2));qaSetRowStatus($tr,Number.isFinite(min)&&Number.isFinite(max)?(avg>=min&&avg<=max):null);} else {$tr.find('.qa-rata').text('');qaSetRowStatus($tr,null);}
                        } else if(format==='C'){ const a=qaNum($tr.find('[data-key="actual"]').val()), s=qaNum($tr.find('[data-key="setting"]').val()), std=qaNum($tr.find('[data-key="alat_std"]').val()), d=Number.isFinite(a)&&Number.isFinite(std)?a-std:NaN; $tr.find('.qa-diff').text(Number.isFinite(d)?d.toFixed(4):''); qaSetRowStatus($tr,Number.isFinite(d)?Math.abs(d)<=qaTolCommon():null); }
                        else if(format==='D'){ const a=qaNum($tr.find('[data-key="hasil"]').val()),s=qaNum($tr.find('td:eq(4) input').val()),d=Number.isFinite(a)&&Number.isFinite(s)?s-a:NaN;$tr.find('.qa-koreksi').text(Number.isFinite(d)?d.toFixed(4):'');qaSetRowStatus($tr,Number.isFinite(d)?Math.abs(d)<=qaTolCommon():null); }
                        else if(format==='E'){ const s=qaNum($tr.find('td:eq(1) input').val()),t=qaNum($tr.find('td:eq(2) input').val()),a=qaNum($tr.find('[data-key="t1_hasil"]').val()),b=qaNum($tr.find('[data-key="t2_hasil"]').val());const k1=Number.isFinite(a)&&Number.isFinite(s)?s-a:NaN,k2=Number.isFinite(b)&&Number.isFinite(s)?s-b:NaN;$tr.find('.qa-t1-kor').text(Number.isFinite(k1)?k1.toFixed(4):'');$tr.find('.qa-t2-kor').text(Number.isFinite(k2)?k2.toFixed(4):''); const tVal=Number.isFinite(t)?Math.abs(t):qaTolCommon(); const hasVal=Number.isFinite(k1)||Number.isFinite(k2); const ok1=!Number.isFinite(k1)||Math.abs(k1)<=tVal; const ok2=!Number.isFinite(k2)||Math.abs(k2)<=tVal; qaSetRowStatus($tr,hasVal?(ok1&&ok2):null); }
                        else if(format==='F'){ const a=qaNum($tr.find('[data-key="sec2_hasil"]').val()),s=qaNum($tr.find('td:eq(1) input').val()),k=Number.isFinite(a)&&Number.isFinite(s)?a-s:NaN;$tr.find('.qa-sec2-kor').text(Number.isFinite(k)?k.toFixed(4):'');qaSetRowStatus($tr,Number.isFinite(k)?Math.abs(k)<=qaTolCommon():null); }
                        else if(format==='G'){
                            const s=qaNum($tr.find('td:eq(1) input').val());
                            const t=qaNum($tr.find('td:eq(2) input').val());
                            const aStr=String($tr.find('[data-key="hasil"]').val() || '').trim();
                            const bStr=String($tr.find('[data-key="hasil_2"]').val() || '').trim();
                            const a=aStr === '' ? NaN : qaNum(aStr);
                            const b=bStr === '' ? NaN : qaNum(bStr);
                            const vals=[];
                            if(aStr !== '' && Number.isFinite(a)) vals.push(a);
                            if(bStr !== '' && Number.isFinite(b)) vals.push(b);
                            const tVal=Number.isFinite(t) ? Math.abs(t) : qaTolCommon();
                            const ok=vals.length && Number.isFinite(s) && Number.isFinite(tVal)
                                ? vals.every(function(v){ return Math.abs(v-s) <= tVal; })
                                : null;
                            qaSetRowStatus($tr,ok);
                        }
                        else if(format==='K'){ const d=qaNum($tr.find('td:eq(1) input').val()),a=qaNum($tr.find('[data-key="nilai_pengukuran"]').val()),diff=Number.isFinite(a)&&Number.isFinite(d)?a-d:NaN;$tr.find('.qa-selisih').text(Number.isFinite(diff)?diff.toFixed(4):'');const tol=qaTolCommon()||Math.abs(qaNum($('#qa_toleransi').val()));qaSetRowStatus($tr,Number.isFinite(diff)?Math.abs(diff)<=tol:null); }
                        else if(format==='L'){ const a=qaNum($tr.find('[data-key="applied_value"]').val()),i=qaNum($tr.find('[data-key="indicated_value"]').val()),c=Number.isFinite(a)&&Number.isFinite(i)?i-a:NaN;$tr.find('.qa-koreksi').text(Number.isFinite(c)?c.toFixed(4):'');qaSetRowStatus($tr,Number.isFinite(c)?Math.abs(c)<=qaTolCommon():null); }
                        else if(format==='N'){
                            const s=qaNum($tr.find('[data-n-key="standar"]').val()), t=Math.abs(qaNum($tr.find('[data-n-key="toleransi"]').val()));
                            const vals=[], corrs=[];
                            qaNColumns.forEach(function(col,j){ const a=qaNum($tr.find('[data-n-result="'+j+'"]').val()); if(Number.isFinite(a)&&Number.isFinite(s)){ const c=a-s; vals.push(c); corrs.push(c.toFixed(4)); }});
                            $tr.find('.qa-koreksi').html(corrs.join('<br>'));
                            qaSetRowStatus($tr, vals.length ? vals.every(function(c){return Number.isFinite(t)&&Math.abs(c)<=t;}) : null);
                        }
                        else if(format==='R'){ const s=qaNum($tr.find('td:eq(1) input').val()),t=Math.abs(qaNum($tr.find('td:eq(2) input').val())),vals=['depan','tengah','belakang'].map(k=>qaNum($tr.find('[data-key="'+k+'"]').val())).filter(Number.isFinite);qaSetRowStatus($tr,vals.length?vals.every(v=>Number.isFinite(s)&&Number.isFinite(t)&&Math.abs(v-s)<=t):null); }
                        else if(format==='T'){ const mn=qaNum($tr.attr('data-t-min')),mx=qaNum($tr.attr('data-t-max')),eps=1e-9; const vals=['hasil_depan1','hasil_depan2','hasil_belakang1','hasil_belakang2'].map(k=>{const x=String($tr.find('[data-key="'+k+'"]').val()||'').trim();return x===''?NaN:qaNum(x);}).filter(Number.isFinite); qaSetRowStatus($tr,vals.length&&Number.isFinite(mn)&&Number.isFinite(mx)?vals.every(v=>v>=mn-eps&&v<=mx+eps):null); }
                        else if(format==='I'){ const a=$tr.find('[data-key="hasil_atas"]').val().trim(),b=$tr.find('[data-key="hasil_bawah"]').val().trim();qaSetRowStatus($tr,(!a&&!b)?null:(/^(OK|OKE)$/i.test(a||b))); }
                        else if(format==='J'){ const h=$tr.find('[data-key="hasil_verifikasi"]').val();qaSetRowStatus($tr,h==='O'); }
                        else if(format==='O'){ const vals=$tr.find('select.qa-update-field').map(function(){return String($(this).val()||'').toUpperCase();}).get().filter(Boolean); qaSetRowStatus($tr,vals.length?vals.every(v=>v==='OK'):null); }
                        else if(format==='P'){ const a=qaNum($tr.find('[data-key="actual1"]').val()),min=qaNum($tr.find('td:eq(5) input').val()),max=qaNum($tr.find('td:eq(6) input').val()),eps=1e-9;qaSetRowStatus($tr,Number.isFinite(a)&&Number.isFinite(min)&&Number.isFinite(max)?(a>=min-eps&&a<=max+eps):null); }
                        else if(format==='Q'){ const s=qaNum($tr.find('input').eq(3).val()),t=Math.abs(qaNum($tr.find('input').eq(4).val())),vals=['actual1','actual2','actual3','actual4'].map(k=>qaNum($tr.find('[data-key="'+k+'"]').val())).filter(Number.isFinite);qaSetRowStatus($tr,vals.length&&Number.isFinite(s)&&Number.isFinite(t)?vals.every(v=>Math.abs(v-s)<=t):null); }
                        qaUpdateFinalFromRows(format);
                    });
                    if(['M','G','F'].includes(format)) $('#tabel_input_kalibrasi .qa-update-field').trigger('change');
                }

                function qaUpdateFinalFromRows(format){
                    format = String(format||'').toUpperCase();
                    if(format==='F'){ const ok=qaCalcF(); if(ok)$('#qa_status_final').val('OK'); else $('#qa_status_final').val('NOT GOOD'); return; }
                    const $rows = $('#tabel_input_kalibrasi .qa-row-status, #tabel_input_kalibrasi .td-status');
                    if(!$rows.length) return;
                    const vals = $rows.map(function(){return String($(this).text()).trim().toUpperCase();}).get().filter(x=>x!=='');
                    if(vals.length === 0) return;
                    if(vals.some(function(st){return /NOT GOOD|NG|X|TIDAK|FAIL/.test(st);})) { $('#qa_status_final').val('NOT GOOD'); return; }
                    if(vals.every(function(st){return /^OK|GOOD|LULUS|PASS$/.test(st);})) { $('#qa_status_final').val('OK'); return; }
                }

                function qaCollectUpdate(format){
                    const rows=[];
                    $('#tabel_input_kalibrasi tbody tr[data-index]').each(function(){
                        const o={};
                        $(this).find('.qa-update-field').each(function(){o[$(this).data('key')]=$(this).val();});
                        const $tr=$(this);
                        o.status=$tr.find('.qa-row-status').text();
                        if(format==='A'){
                            o.posisi=$tr.find('td:eq(1)').text(); o.std_spesifikasi=$tr.find('td:eq(2) input').val()||''; o.std_no_alat=$tr.find('td:eq(3) input').val()||''; o.std_out=$tr.find('td:eq(4) input').val()||''; o.std_pitch=$tr.find('td:eq(5) input').val()||''; o.out={dep:o.out1||'',ten:o.out2||'',bel:o.out3||''}; o.pitch={dep:o.pitch1||'',ten:o.pitch2||'',bel:o.pitch3||''}; delete o.out1;delete o.out2;delete o.out3;delete o.pitch1;delete o.pitch2;delete o.pitch3;
                        } else if(format==='B'){ const v=['std1','std2','std3'].map(k=>qaNum(o[k])).filter(Number.isFinite); o.uji1=o.std1||'';o.uji2=o.std2||'';o.uji3=o.std3||'';o.hasil=v.length?(v.reduce((a,b)=>a+b,0)/v.length).toFixed(2):'';o.rata=o.hasil;delete o.std1;delete o.std2;delete o.std3; }
                        else if(format==='C'){ o.actual=o.actual||''; o.selisih=$tr.find('.qa-diff').text(); }
                        else if(format==='D'){ o.standar=$tr.find('td:eq(4) input').val(); o.hasil=o.hasil||'';o.koreksi=$tr.find('.qa-koreksi').text(); }
                        else if(format==='E'){ o.standar=$tr.find('td:eq(1) input').val();o.toleransi=$tr.find('td:eq(2) input').val();o.t1_koreksi=$tr.find('.qa-t1-kor').text();o.t2_koreksi=$tr.find('.qa-t2-kor').text(); }
                        else if(format==='F'){ o.koreksi=$tr.find('.qa-sec2-kor').text(); }
                        else if(format==='G'){ o.standar=$tr.find('td:eq(1) input').val()||''; o.toleransi=$tr.find('td:eq(2) input').val()||''; }
                        else if(format==='H'){const idx=$tr.data('index'), master=(qaStandardPoints(currentAlatData)||[])[idx]||{}; o.posisi_ukur=$tr.find('td:eq(1)').text();o.std_out=$tr.find('[data-key="std_out"]').val()||'';o.std_pitch=$tr.find('[data-key="std_pitch"]').val()||'';o.out={dep:o.out_dep||'',ten:o.out_ten||'',bel:o.out_bel||''};o.pitch={dep:o.pitch_dep||'',ten:o.pitch_ten||'',bel:o.pitch_bel||''}; o.tol_out=master.tol_out||'';o.range_out=master.range_out||'';o.tol_pitch=master.tol_pitch||'';o.range_pitch=master.range_pitch||'';o.std_spesifikasi=master.std_spesifikasi||'';o.std_no_alat=master.std_no_alat||'';}
                        else if(format==='I'){ o.putusan=o.status==='OK'?'OK':'NOT GOOD'; }
                        else if(format==='K'){ o.selisih=$tr.find('.qa-selisih').text(); }
                        else if(format==='L'){ o.correction=$tr.find('.qa-koreksi').text();o.applied_value=o.applied_value||'';o.indicated_value=o.indicated_value||''; }
                        else if(format==='N'){
                            const idx=$('#tabel_input_kalibrasi tbody tr[data-index]').index($tr);
                            const master=qaStandardPoints(currentAlatData)[idx]||{};
                            o.standar=$tr.find('[data-n-key="standar"]').val()||master.standar||master.nilai_standar||master.std||'';
                            o.toleransi=$tr.find('[data-n-key="toleransi"]').val()||master.toleransi||master.tol||master.toleransi_global||'';
                            o.posisi_kolom=qaNColumns.map(function(col,j){return {label:col.label,posisi:col.label,hasil:$tr.find('[data-n-result="'+j+'"]').val()||'',koreksi:''};});
                            o.posisi_hasil=o.posisi_kolom.slice();
                            o.hasil=o.posisi_kolom.length?(o.posisi_kolom[0].hasil||''):'';
                            o.koreksi=$tr.find('.qa-koreksi').text();
                        }
                        else if(format==='O'){ o.kode_warna=$('#qa_kode_warna').val();o.keputusan=$('#qa_status_final').val()==='OK'?'OK':'NG';o.no_alat=alatForNoPart();o.spesifikasi=$('#qa_spesifikasi').val(); }
                        else if(format==='P'){ o.aktual_status=o.status; }
                        else if(format==='Q'){ o.kode_warna=$('#qa_kode_warna').val(); }
                        else if(format==='R'){ }
                        rows.push(o);
                    });

                    // Pastikan field standar/read-only dari qa_part.titik_standar_json ikut tersimpan
                    // di hasil_json. Nilai editable petugas tidak ditimpa.
                    if (format !== 'F') {
                        const masters = qaStandardPoints(currentAlatData);
                        const keys = qaStandardKeys(format);
                        rows.forEach(function(row, idx) {
                            const rawMaster = masters[idx];
                            const master = (rawMaster && typeof rawMaster === 'object') ? rawMaster : {standar: rawMaster};
                            keys.forEach(function(key) {
                                if ((row[key] == null || String(row[key]).trim() === '') && master[key] != null) row[key] = master[key];
                            });
                        });
                    }

                    if(format==='A'){
                        const go=rows.find(r=>String(r.posisi).toUpperCase()==='GO')||{}; const ng=rows.find(r=>String(r.posisi).toUpperCase()==='NOT GO')||{};
                        return [{kode_varian:'', kode_warna:$('#qa_kode_warna').val(), warna:$('#qa_kode_warna').val(), kep:$('#qa_status_final').val()==='OK'?'OK':'NOT GOOD', keputusan:$('#qa_status_final').val()==='OK'?'OK':'NOT GOOD', keterangan:$('#qa_keterangan').val(), alat_standar:{go:{no_alat:go.std_no_alat||'',spesifikasi:go.std_spesifikasi||''},not_go:{no_alat:ng.std_no_alat||'',spesifikasi:ng.std_spesifikasi||''}}, go:go, not_go:ng, periksa_berikut:null}];
                    }
                    if(format==='P'){
                        $('#body_reg_standar .qa-p-group').each(function(gi){
                            const $g=$(this), master={}; $g.find('.qa-p-master').each(function(){master[$(this).data('key')]=$(this).val().trim();});
                            $g.find('.qa-p-std').each(function(){
                                const o={group_id:'P'+(gi+1),type_ukuran:master.type_ukuran||'',type:master.type_ukuran||'',ukuran:'',upart:'',alat_nama:master.alat_nama||'',alat_no:master.alat_no||'',actual_count:'2',no_gambar:$('#qa_no_gambar').val()||''};
                                $(this).find('.qa-p-std-input').each(function(){o[$(this).data('key')]=$(this).val().trim();});
                                if(o.std_label!=='' || o.toleransi!=='') rows.push(o);
                            });
                        }); return rows;
                    }
                    if(format==='O'){
                        const master=qaStandardPoints(currentAlatData)[0]||{};
                        const go=rows.find(r=>r.go_hasil_min!=null||r.go_hasil_max!=null)||{};
                        const ng=rows.find(r=>r.notgo_hasil_min!=null||r.notgo_hasil_max!=null)||{};
                        return [Object.assign({}, master, {
                            go_hasil_min:go.go_hasil_min||'', go_hasil_max:go.go_hasil_max||'',
                            notgo_hasil_min:ng.notgo_hasil_min||'', notgo_hasil_max:ng.notgo_hasil_max||'',
                            kode_warna:$('#qa_kode_warna').val(),
                            keputusan:$('#qa_status_final').val()==='OK'?'OK':'NOT GOOD',
                            no_alat:alatForNoPart(), spesifikasi:$('#qa_spesifikasi').val(),
                            no_gambar:$('#qa_no_gambar').val()
                        })];
                    }
                    if(format==='Q'){
                        const subtype=String((rows[0]&&rows[0].subtype)||'SERATION').toUpperCase();
                        if(subtype==='PLUG'){
                            const go=rows[0]||{}, ng=rows[1]||{};
                            return [{subtype:'PLUG',
                                std_spesifikasi:go.std_spesifikasi||ng.std_spesifikasi||'',
                                std_no_alat:go.std_no_alat||ng.std_no_alat||'',
                                kode_warna:$('#qa_kode_warna').val(),
                                keputusan:$('#qa_status_final').val()==='OK'?'OK':'NOT GOOD', go:go, notgo:ng}];
                        }
                        const out=rows[0]||{}, pitch=rows[1]||{};
                        return [{subtype:'SERATION', kode_warna:$('#qa_kode_warna').val(), keputusan:$('#qa_status_final').val()==='OK'?'OK':'NOT GOOD', out:{std_spesifikasi:out.std_spesifikasi||'',std_no_alat:out.std_no_alat||'',std:out.std||'',tol_plus:out.tol_plus||'',tol_minus:out.tol_minus||'',min:out.min||'',max:out.max||'',actual:[out.actual1||'',out.actual2||'',out.actual3||'',out.actual4||'']}, pitch:{std_spesifikasi:pitch.std_spesifikasi||'',std_no_alat:pitch.std_no_alat||'',std:pitch.std||'',tol_plus:pitch.tol_plus||'',tol_minus:pitch.tol_minus||'',min:pitch.min||'',max:pitch.max||'',actual:[pitch.actual1||'',pitch.actual2||'',pitch.actual3||'',pitch.actual4||'']}}];
                    }
                    if(format==='F'){
                        const meta=qaStandardPoints(currentAlatData).find(x=>x.meta==='F')||{}; const out={format:'F',sec1:{},sec2:[],sec3:{}};
                        const sec1=$('#tabel_input_kalibrasi [data-key="sec1_hasil"]').val(); out.sec1={standar:meta.std_sec1||'',hasil:sec1||'',deviasi:(sec1!==''&&Number.isFinite(qaNum(sec1)))?qaFmt(qaNum(sec1)-qaNum(meta.std_sec1)):''};
                        $('#tabel_input_kalibrasi tbody tr[data-index]').each(function(){out.sec2.push({standar:$(this).find('td:eq(1) input').val()||'',hasil:$(this).find('[data-key="sec2_hasil"]').val()||'',koreksi:$(this).find('.qa-sec2-kor').text()});});
                        out.sec3={standar:meta.std_sec3||'',tengah:$('#tabel_input_kalibrasi [data-key="tengah"]').val()||'',belakang:$('#tabel_input_kalibrasi [data-key="belakang"]').val()||'',kanan:$('#tabel_input_kalibrasi [data-key="kanan"]').val()||'',depan:$('#tabel_input_kalibrasi [data-key="depan"]').val()||'',kiri:$('#tabel_input_kalibrasi [data-key="kiri"]').val()||'',perbedaan:$('#tabel_input_kalibrasi .qa-sec3-diff').text()};
                        return [out];
                    }
                    return rows;
                }

                function qaValidateUpdate(format, rows){
                    if(format==='F') return rows.length===1 && rows[0].sec2 && rows[0].sec2.length>0;
                    if(format==='A') return true;
                    return rows.length>0;
                }

                function qaBuildPrintData(format, hasilArray, res, statusQA, snapshotOverride){
                    const snap = snapshotOverride || {};
                    const name = $('#qa_nama_alat').val();
                    const spec = $('#qa_spesifikasi').val();
                    const reso = $('#qa_resolusi').val();
                    const standard = $('#qa_standar_uji').val();
                    const meta = {
                        no_part: currentAlatData ? currentAlatData.no_part : $('#qa_nomor_alat').val(),
                        no_alat: currentAlatData ? currentAlatData.no_part : $('#qa_nomor_alat').val(),
                        nama_part: name, nama_alat: name,
                        spesifikasi: spec, resolusi: reso,
                        seksi_pemilik: currentAlatData ? currentAlatData.seksi_pemilik : '', seksi: currentAlatData ? currentAlatData.seksi_pemilik : '',
                        lokasi: currentAlatData ? currentAlatData.lokasi : '',
                        metode: $('#qa_metode').val(), metode_uji: $('#qa_metode').val(),
                        standar_uji: standard, traceability: $('#qa_trace').val(),
                        toleransi_global: (format==='F' ? $('#qa_toleransi_f_rendah').val()+'|'+$('#qa_toleransi_f_tinggi').val() : $('#qa_toleransi').val()), toleransi: $('#qa_toleransi').val(),
                        satuan: currentAlatData ? currentAlatData.satuan : '',
                        suhu: $('#qa_suhu').val(), kelembapan: $('#qa_kelembapan').val(),
                        ketidakpastian: $('#qa_ketidakpastian').val(), confidence_level: $('#qa_confidence').val(),
                        status_final: statusQA, keputusan: statusQA,
                        kode_warna: $('#qa_kode_warna').val() || (statusQA ? statusQA.kode_warna : '') || '',
                        tanggal_periksa: "<?php echo date('Y-m-d'); ?>",
                        jadwal_berikutnya: statusQA === 'OK' ? (res ? res.jadwal_selanjutnya : null) : null,
                        petugas: $('#qa_petugas').val(), keterangan: $('#qa_keterangan').val(),
                        format: format, hasil_array: hasilArray,
                        posisi_kolom: format === 'N' ? qaNColumns.map(function(x){ return {label:x.label,posisi:x.label}; }) : [],
                        // Master standar yang diinput saat registrasi (qa_part.titik_standar_json).
                        // Ini sengaja ikut dibawa ke laporan supaya kolom ALAT STANDAR pada Format A
                        // mengambil data asli registrasi, bukan data actual/hasil kalibrasi.
                        titik_standar_json: currentAlatData ? (currentAlatData.titik_standar_json || '[]') : '[]',
                        kode_warna: $('#qa_kode_warna').val(),
                        no_gambar: ['O','P'].includes(String(format).toUpperCase()) ? $('#qa_no_gambar').val() : '',
                        no_laporan: res && (res.no_laporan || res.no_pengecekan) ? (res.no_laporan || res.no_pengecekan) : '',
                        nomor_pengecekan: res && (res.no_pengecekan || res.no_laporan) ? (res.no_pengecekan || res.no_laporan) : '',
                        tanggal_pengecekan: "<?php echo date('Y-m-d'); ?>",
                        periode_berikutnya: statusQA === 'OK' ? (res ? res.jadwal_selanjutnya : null) : null,
                        operator: $('#qa_petugas').val(),
                        seksi_qa: 'QA',
                        status_validasi: 'menunggu_pemeriksa',
                        type: currentAlatData ? (currentAlatData.type || '') : '',
                        halaman: '1 / 1'
                    };
                    if (format==='M'){
                        const axis=hasilArray.find(x=>String(x.jenis).toUpperCase()==='AXIS')||{}, angle=hasilArray.find(x=>String(x.jenis).toUpperCase()==='ANGLE')||{};
                        meta.toleransi_axis = axis.toleransi_axis || '';
                        meta.toleransi_sudut = angle.toleransi_sudut || '';
                    }
                    if(format==='J') meta.alat_verifikasi = hasilArray[0] ? (hasilArray[0].spesifikasi_alat_verifikasi || hasilArray[0].alat_verifikasi || '') : '';
                    if(format==='P') meta.nama_alat = name;
                    if(format==='Q') meta.nama_alat = name;
                    if(format==='O') meta.nama_alat = name;
                    return meta;
                }

                window.qaRenderUpdateEditor = function(alat, history){
                    currentAlatData = alat; currentAlatHistory = history || [];
                    qaFillCommon(alat, history || []);
                    const format = String(alat.format_laporan || 'A').toUpperCase();
                    if (format === 'N') qaNColumns = [{key:'hasil_0',label:'Hasil'}];
                    else qaNColumns = [{key:'hasil_0',label:'Hasil'}];
                    const points = qaStandardPoints(alat);
                    qaRenderUpdateTable(format, points);
                    $('#qa_status_final').val('');
                    $('#qa_no_laporan').val('');
                    $('#qa_hasil_json').val('');
                    $('.qa-update-field').first().focus();
                };

                // Ganti handler format registrasi lama dengan versi A-R.
                $('#btn-lanjut-format').off('click').on('click', function(){
                    const format=$('#reg_pilih_format').val();
                    if(!format) { BootstrapDialog.alert('Silakan pilih Format Kalibrasi terlebih dahulu!'); return; }
                    $('#reg_nomor_alat').val($('#nomor_alat').val().toUpperCase()).prop('readonly',true).prop('disabled',true);
                    $('#group_format_f_extra').toggle(format==='F');
                    $('#lbl_titik_standar').html('<i class="fa fa-list"></i> Parameter Standar Format '+format);
                    $('#sub_lbl_titik_standar').text('Data standar tersimpan di titik_standar_json dan akan menjadi acuan pada update kalibrasi.');
                    const pts = qaStandardPoints({titik_standar_json:'[]'});
                    qaRenderRegTable(format, pts);
                    $('#section-pilih-format').slideUp(); $('#section-form-registrasi').slideDown();
                });

                // Rebind save registration.
                $('#btn-simpan-alat').off('click').on('click', function(){
                    const format=$('#reg_pilih_format').val();
                    const regNo=$('#reg_nomor_alat').val().trim().toUpperCase();
                    const regNoLaporan=$('#reg_no_laporan').val().trim();
                    const regNama=$('#reg_nama_alat').val().trim();
                    const regSeksi=$('#reg_seksi').val();
                    const regPeriode=$('#reg_periode').val().trim();
                    const regSpek=$('#reg_spesifikasi').val().trim();
                    const regRes=$('#reg_resolusi').val().trim();
                    const regTol=$('#reg_toleransi').val().trim();
                    const regSatuan=$('#reg_satuan').val().trim();
                    const regMetode=$('#reg_metode').val().trim();
                    const regStandar=$('#reg_standar_uji').val().trim();
                    const regTrace=$('#reg_trace').val().trim();
                    const regLokasi=$('#reg_lokasi').val().trim();
                    if(!format || !regNo || !regNoLaporan || !regNama || !regSeksi || !regPeriode || !regSpek || !regRes || !regTol || !regSatuan || !regMetode || !regStandar || !regTrace || !regLokasi){ BootstrapDialog.alert('<b>Gagal!</b> Header/Footer wajib diisi lengkap.'); return; }
                    const arr=qaCollectReg(format);
                    if(!arr.length){ BootstrapDialog.alert('Minimal ada satu konfigurasi standar untuk Format '+format+'.'); return; }
                    if(format==='F' && (!$('#reg_f_std_sec1').val().trim() || !$('#reg_f_std_sec3').val().trim())){ BootstrapDialog.alert('Standar Repeatability dan Center Loading wajib diisi untuk Format F.'); return; }
                    const btn=$(this); btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
                    const fd=new FormData();
                    fd.append('no_part',regNo);fd.append('no_laporan',regNoLaporan);fd.append('nama_part',regNama);fd.append('seksi',regSeksi);fd.append('periode',regPeriode);
                    fd.append('ukuran','');fd.append('resolusi',regRes);fd.append('lokasi',regLokasi);fd.append('tgl_manual',$('#reg_tgl_manual').val());
                    fd.append('spesifikasi',regSpek);fd.append('toleransi_global',regTol);fd.append('satuan',regSatuan);fd.append('metode_uji',regMetode);fd.append('standar_uji',regStandar);fd.append('traceability',regTrace);
                    fd.append('format_laporan',format);fd.append('titik_standar_json',JSON.stringify(arr));
                    if($('#reg_foto_alat')[0] && $('#reg_foto_alat')[0].files[0]) fd.append('foto_alat',$('#reg_foto_alat')[0].files[0]);
                    $.ajax({url:'proses_qa.php?action=simpan_alat',type:'POST',dataType:'json',data:fd,processData:false,contentType:false,success:function(res){
                        btn.prop('disabled',false).html('Simpan & Daftarkan Alat <i class="fa fa-save"></i>');
                        if(res.status==='success'){
                            sessionStorage.setItem('cetak_data', JSON.stringify(res.print_data || {
                                no_laporan:regNoLaporan,no_part:regNo,no_alat:regNo,nama_part:regNama,nama_alat:regNama,spesifikasi:regSpek,seksi_pemilik:regSeksi,seksi:regSeksi,lokasi:regLokasi,resolusi:regRes,
                                metode_uji:regMetode,standar_uji:regStandar,traceability:regTrace,suhu:'',kelembapan:'',ketidakpastian:'',confidence_level:'',
                                format:format,format_laporan:format,hasil_array:arr,petugas:'<?php echo addslashes($nama_petugas); ?>',operator:'<?php echo addslashes($nama_petugas); ?>',
                                tanggal_periksa:$('#reg_tgl_manual').val() || '<?php echo date('Y-m-d'); ?>',jadwal_berikutnya:res.jadwal_selanjutnya || '',status_final:'OK',keputusan:'OK',
                                signature_pembuat:true,signature_pemeriksa:false,signature_penyetuju:false,keterangan:'Registrasi Alat Baru',titik_standar_json:JSON.stringify(arr)
                            }));
                            BootstrapDialog.confirm({title:'Berhasil',type:BootstrapDialog.TYPE_SUCCESS,message:'Alat berhasil didaftarkan.<br><br><b>Cetak Laporan Pemeriksaan?</b>',btnCancelLabel:'Tidak',btnOKLabel:'Ya, Cetak',btnOKClass:'btn-primary',callback:function(ok){
                                if(ok) window.location.href='cetak_laporan.php';
                                else { $('#form-registrasi').slideUp(); $('#nomor_alat').prop('disabled',false).val(regNo); $('#btn-cek').click(); }
                            }});
                        } else BootstrapDialog.alert('Error DB: '+(res.message||'Gagal menyimpan.'));
                    },error:function(xhr){btn.prop('disabled',false).html('Simpan & Daftarkan Alat <i class="fa fa-save"></i>');BootstrapDialog.alert('Koneksi Error! '+(xhr.responseText||''));}});
                });

                // Tambah baris update: standar tetap, actual dikosongkan agar bisa diisi ulang.
                $(document).off('click.qaAddRow', '#btn_add_update_row').on('click.qaAddRow', '#btn_add_update_row', function(){ return false; });

                // Format N: kolom Bagian/Posisi dinamis yang ikut tersimpan dan dicetak.
                $(document).off('click.qaAddNCol', '#btn_add_n_column').on('click.qaAddNCol', '#btn_add_n_column', function(){
                    if(String($('#qa_format_alat').val()||'').toUpperCase()!=='N') return;
                    const captured=qaCaptureNTable(), idx=qaNColumns.length;
                    qaNColumns.push({key:'hasil_'+idx,label:'Posisi '+(idx+1)});
                    captured.forEach(function(row){ row.posisi_hasil.push({posisi:qaNColumns[idx].label,label:qaNColumns[idx].label,hasil:'',koreksi:''}); });
                    qaRenderUpdateTable('N',captured);
                    $('#tabel_input_kalibrasi .qa-n-col-label').last().focus().select();
                });

                $(document).off('click.qaRemoveNCol', '.btn-remove-n-column').on('click.qaRemoveNCol', '.btn-remove-n-column', function(){
                    if(String($('#qa_format_alat').val()||'').toUpperCase()!=='N' || qaNColumns.length<=1) return;
                    const removeIndex=parseInt($(this).data('col'),10);
                    if(!Number.isInteger(removeIndex) || removeIndex<=0) return;
                    const captured=qaCaptureNTable(); qaNColumns.splice(removeIndex,1);
                    captured.forEach(function(row){ row.posisi_hasil.splice(removeIndex,1); });
                    qaRenderUpdateTable('N',captured);
                });

                $(document).off('change.qaNLabels', '.qa-n-col-label').on('change.qaNLabels', '.qa-n-col-label', function(){
                    const idx=parseInt($(this).data('n-col'),10);
                    if(!Number.isInteger(idx) || !qaNColumns[idx]) return;
                    qaNColumns[idx].label=$(this).val().trim()||('Posisi '+(idx+1));
                });

                // Ganti handler simpan QA agar memakai result schema A-R + snapshot.
                $('#btn-simpan-qa').off('click').on('click', function(){
                    const format=String($('#qa_format_alat').val()||'A').toUpperCase();
                    const rows=qaCollectUpdate(format);
                    const noLaporan=String($('#qa_no_laporan').val()||'').trim();
                    if(!noLaporan){BootstrapDialog.alert('No. Laporan wajib diisi!');return;}
                    if(!qaValidateUpdate(format,rows)){BootstrapDialog.alert('Lengkapi hasil pengukuran sesuai Format '+format+'.');return;}
                    if(!$('#qa_status_final').val()){qaUpdateFinalFromRows(format);}
                    const statusQA=$('#qa_status_final').val();
                    if(!statusQA){BootstrapDialog.alert('Keputusan / Status Final wajib diisi.');return;}
                    const jsonHasil=JSON.stringify(rows);
                    $('#qa_hasil_json').val(jsonHasil);
                    const send=function(){
                        const btn=$('#btn-simpan-qa');btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
                        const data={no_part:$('#qa_nomor_alat').val(),no_laporan:noLaporan,status_qa:statusQA,kode_warna:$('#qa_kode_warna').val()||'',hasil_json:jsonHasil,format_laporan:format,operator:$('#qa_petugas').val(),
                            nama_part:$('#qa_nama_alat').val(),type:$('#qa_type').val(),spesifikasi:$('#qa_spesifikasi').val(),resolusi:$('#qa_resolusi').val(),lokasi:currentAlatData?currentAlatData.lokasi:'',
                            metode_uji:$('#qa_metode').val(),standar_uji:$('#qa_standar_uji').val(),traceability:$('#qa_trace').val(),toleransi_global:(format==='F' ? $('#qa_toleransi_f_rendah').val()+'|'+$('#qa_toleransi_f_tinggi').val() : $('#qa_toleransi').val()),satuan:currentAlatData?currentAlatData.satuan:'',
                            suhu:$('#qa_suhu').val(),kelembapan:$('#qa_kelembapan').val(),ketidakpastian:$('#qa_ketidakpastian').val(),confidence:$('#qa_confidence').val(),keterangan:$('#qa_keterangan').val()};
                        $.ajax({url:'proses_qa.php?action=simpan_qa',type:'POST',dataType:'json',data:data,success:function(res){
                            btn.prop('disabled',false).html('Submit Hasil QA');
                            if(res.status==='success'){
                                const cetak=qaBuildPrintData(format,rows,res,statusQA);
                                sessionStorage.setItem('cetak_data',JSON.stringify(cetak));
                                BootstrapDialog.confirm({title:'SUKSES!',type:BootstrapDialog.TYPE_SUCCESS,message:'Hasil kalibrasi berhasil diajukan dan menunggu validasi Pemeriksa lalu Penyetuju.<br><br><b>Cetak laporan?</b>',btnCancelLabel:'Tidak',btnOKLabel:'Ya, Cetak',btnOKClass:'btn-primary',callback:function(ok){if(ok) window.location.href='cetak_laporan.php'; else {$('#form-update-kalibrasi').slideUp();$('#nomor_alat').prop('disabled',false).val('').focus();}}});
                            } else BootstrapDialog.alert('Gagal: '+(res.message||'Gagal menyimpan.'));
                        },error:function(xhr){btn.prop('disabled',false).html('Submit Hasil QA');BootstrapDialog.alert('Error Submit! '+(xhr.responseText||''));}});
                    };
                    BootstrapDialog.confirm({title:'AJUKAN HASIL KALIBRASI',type:BootstrapDialog.TYPE_INFO,message:'Hasil <b>'+qaEsc(statusQA)+'</b> akan diajukan untuk validasi <b>Pemeriksa</b> dan <b>Penyetuju</b>. Status tersebut <b>belum dinyatakan final</b> sebelum kedua tahap validasi selesai.<br><br>Lanjutkan?',btnCancelLabel:'Batal',btnOKLabel:'Ajukan',btnOKClass:'btn-primary',callback:function(ok){if(ok)send();}});
                });
                // Histori print mengambil validator terbaru dari server (qa_validasi), bukan session/local form.
                $(document).off('click', '.btn-print-hist').on('click.qaHist', '.btn-print-hist', function(){
                    const idx=$(this).data('idx'); const hist=currentAlatHistory[idx]; if(!hist) return;
                    const btn=$(this).prop('disabled',true);
                    $.post('proses_qa.php?action=get_history_print',{pengecekan_id:hist.id},function(res){
                        btn.prop('disabled',false);
                        if(res&&res.status==='success'){
                            sessionStorage.setItem('cetak_data',JSON.stringify(res.print_data||{}));
                            window.location.href='cetak_laporan.php';
                        } else BootstrapDialog.alert({title:'Gagal',type:BootstrapDialog.TYPE_DANGER,message:(res&&res.message)||'Data laporan gagal dimuat.'});
                    },'json').fail(function(xhr){btn.prop('disabled',false);BootstrapDialog.alert({title:'Gagal',type:BootstrapDialog.TYPE_DANGER,message:xhr.responseText||'Request gagal.'});});
                });

                // Agar alat Bad bisa langsung masuk flow registrasi unit baru.
                const oldBtnCek = $('#btn-cek').data('qaPatchBound');
                if(!oldBtnCek){
                    $('#btn-cek').data('qaPatchBound',true);
                    $('#btn-cek').off('click').on('click', function(){
                        var noAlat=qaNormalizeNoAlatInput($('#nomor_alat').val()).toUpperCase();
                        $('#nomor_alat').val(noAlat);
                        if(!noAlat){$('#group-input').addClass('has-error');$('#error-msg').slideDown(200);$('#nomor_alat').focus();return;}
                        $('#group-input').removeClass('has-error');$('#error-msg').slideUp(200);
                        var btn=$(this),textAsli=btn.html();btn.html('<i class="fa fa-spinner fa-spin"></i> Mengecek DB...').prop('disabled',true);
                        $.ajax({url:'proses_qa.php?action=cek_alat',type:'POST',dataType:'json',data:{no_part:noAlat},success:function(res){
                            btn.html(textAsli).prop('disabled',false);$('#form-update-kalibrasi,#form-registrasi').hide();
                            if(res.found){
                                var alat=res.data,history=res.history||[];currentAlatData=alat;currentAlatHistory=history;
                                setVisualAlat(alat.foto_alat);
                                var tGabungan=$('#tabel-gabungan tbody').empty();tGabungan.append('<tr><td>1</td><td><strong>'+qaEsc(alat.no_part||'-')+'</strong></td><td>'+qaEsc(alat.nama_part||'-')+'</td><td>'+qaEsc(getAlatSpesifikasi(alat)||'-')+'</td><td>'+qaEsc(alat.seksi_pemilik||'-')+'</td><td>'+qaEsc(alat.lokasi||'-')+'</td><td><span class="label label-info">Jadwal</span></td><td><strong>'+formatTanggalJadwal(alat.jadwal_kalibrasi)+'</strong></td><td>-</td><td>-</td></tr>');
                                history.forEach(function(row,idx){var rawSt=String(row.display_status||row.status_display||row.status_kalibrasi||row.status||'').toUpperCase();var sv=String(row.status_validasi||'').toLowerCase();var jt=String(row.jenis_transaksi||row.tipe_transaksi||'').toLowerCase();var kt=String(row.keterangan||'').toLowerCase();var isRegist=(jt==='registrasi_alat'||jt.indexOf('registr')!==-1||kt.indexOf('registrasi alat')!==-1);var st=isRegist?'REGISTER':(sv==='menunggu_pemeriksa'?'MENUNGGU VALIDASI (PEMERIKSA)':(sv==='menunggu_penyetuju'?'MENUNGGU VALIDASI (PENYETUJU)':rawSt));if(st==='GOOD'||st==='LULUS')st='OK';if(st==='NG'||st==='BAD')st='NOT GOOD';var label=st==='OK'?'<span class="label label-success">OK</span>':(st==='REGISTER'?'<span class="label label-info">Register</span>':(st==='NOT GOOD'||st==='RUSAK'||st==='DISCONTINUE'||st==='DITARIK TECHNICAL'||st==='DITOLAK PEMERIKSA'||st==='DITOLAK PENYETUJU'?'<span class="label label-danger">'+qaEsc(st)+'</span>':'<span class="label label-warning"><i class="fa fa-clock-o"></i> '+qaEsc(st||'MENUNGGU VALIDASI')+'</span>'));var btnA='<button class="btn btn-xs btn-primary btn-print-hist" data-idx="'+idx+'"><i class="fa fa-print"></i> '+'Cetak'+'</button>';tGabungan.append('<tr><td>'+(idx+2)+'</td><td><strong>'+qaEsc(row.no_part||alat.no_part||'-')+'</strong></td><td>'+qaEsc(alat.nama_part||'-')+'</td><td>'+qaEsc(getAlatSpesifikasi(alat)||'-')+'</td><td>'+qaEsc(alat.seksi_pemilik||'-')+'</td><td>'+qaEsc(alat.lokasi||'-')+'</td><td>'+label+'</td><td><strong>'+formatTanggal(row.tanggal_pengecekan||row.tanggal)+'</strong></td><td>'+qaEsc(row.operator||row.petugas||'-')+'</td><td>'+btnA+'</td></tr>');});
                                // BBRAU SHORTCUT V1 - NOMOR ALAT TERDAFTAR LANGSUNG BISA BUKA LAPORAN BBRAU
                                var bbNo=encodeURIComponent(alat.no_part||noAlat);
                                if(!$('#btn-bbrau-shortcut').length){ $('#qa-detail-actions').append('<a id="btn-bbrau-shortcut" class="btn btn-sm btn-default" target="_self"><i class="fa fa-book"></i> Laporan BBRAU</a>'); }
                                $('#btn-bbrau-shortcut').attr('href','laporan_bbrau.php?no_alat='+bbNo);
                                // CEK ALAT QR V2 - GENERATOR DI SAMPING SHORTCUT BBRAU
                                if(!$('#btn-qr-generator').length){ $('#qa-detail-actions').append('<button type="button" id="btn-qr-generator" class="btn btn-sm btn-default"><i class="fa fa-qrcode"></i> Buat QR Code</button>'); }
                                $('#btn-qr-generator').data('no-alat',alat.no_part||noAlat).data('nama-alat',alat.nama_part||alat.nama_alat||'').data('seksi-pemilik',alat.seksi_pemilik||alat.seksi||'');
                                $('#qa_nomor_alat').val(alat.no_part);$('#qa_format_alat').val(alat.format_laporan);qaRenderUpdateEditor(alat,history);$('#nomor_alat').prop('disabled',true);$('#form-update-kalibrasi').slideDown();
                            }else{
                                const openReg=function(){ 
                                    $('#nomor_alat').prop('disabled', true); 
                                    $('#reg_nomor_alat').val(res.original_no_part || noAlat).prop('readonly', true).prop('disabled', false); 
                                    $('#reg_nama_alat, #reg_spesifikasi, #reg_resolusi, #reg_toleransi, #reg_satuan, #reg_lokasi, #reg_metode, #reg_standar_uji, #reg_trace, #reg_periode, #reg_tgl_manual, #reg_merk, #reg_buatan, #reg_fungsi, #reg_type').val('');
                                    $('#reg_seksi, #reg_pilih_format').val('');
                                    pakaiBagian = false; $('.col-bagian').hide();
                                    $('#btn_toggle_bagian').show().html('<i class="fa fa-columns"></i> Tambah Kolom \'Bagian/Posisi\'').removeClass('btn-warning').addClass('btn-info');
                                    setVisualAlat('');
                                    $('#section-form-registrasi').hide(); $('#section-pilih-format').show();
                                    $('#form-registrasi').slideDown();
                                };
                                if(res.is_archived) {
                                    var noAlatArsip=res.archive_no_part||res.original_no_part||noAlat;
                                    var noArsip=res.nomor_arsip||((res.registry||{}).nomor_arsip_terakhir)||'-';
                                    var statusArsip=res.archive_status||res.alasan_status||'-';
                                    var histHtml='<div><b>No. Alat:</b> '+qaEsc(noAlatArsip)+'<br><b>Status Nomor:</b> '+qaEsc(statusArsip)+'<br><b>No. Arsip:</b> '+qaEsc(noArsip)+'</div>';
                                    histHtml+='<hr><b>History Nomor Alat</b><div class="table-responsive" style="margin-top:10px;max-height:240px;overflow:auto;"><table class="table table-bordered table-condensed"><thead><tr><th>Tanggal</th><th>Status</th><th>Operator</th><th>Keterangan</th></tr></thead><tbody>';
                                    (res.history||[]).forEach(function(h){histHtml+='<tr><td>'+qaEsc(h.tanggal_pengecekan||h.tanggal||'-')+'</td><td>'+qaEsc(h.display_status||h.status_display||h.status_kalibrasi||h.status||'-')+'</td><td>'+qaEsc(h.operator||h.created_by||'-')+'</td><td>'+qaEsc(h.keterangan||'-')+'</td></tr>';});
                                    if(!(res.history||[]).length) histHtml+='<tr><td colspan="4" class="text-center">Belum ada history.</td></tr>';
                                    histHtml+='</tbody></table></div>';
                                    var canRestore=!!res.can_restore && !res.is_rusak;
                                    var dlg=BootstrapDialog.show({title:res.is_rusak?'NOMOR ALAT BLOKIR PERMANEN':'NOMOR ALAT NONAKTIF',type:res.is_rusak?BootstrapDialog.TYPE_DANGER:BootstrapDialog.TYPE_WARNING,message:(res.message||'Nomor alat sedang nonaktif.')+'<br><br>'+histHtml,closable:true,buttons:[{label:'Tutup',cssClass:'btn-default',action:function(d){d.close();}}]});
                                    if(canRestore){
                                        dlg.getModalFooter().prepend('<button type="button" class="btn btn-success" id="btnRestoreArchived"><i class="fa fa-check"></i> Aktifkan Kembali Nomor Alat</button>');
                                    }

                                    // Gunakan delegated handler karena tombol dibuat dinamis oleh BootstrapDialog.
                                    // Ini memastikan klik tombol restore selalu tertangkap walaupun footer dialog dibuat ulang.
                                    $(document).off('click.qaRestoreArchived', '#btnRestoreArchived').on('click.qaRestoreArchived', '#btnRestoreArchived', function(e){
                                        e.preventDefault();
                                        var rb=$(this);
                                        if(rb.data('restoreBusy')) return;
                                        rb.data('restoreBusy',true).prop('disabled',true);
                                        rb.html('<i class="fa fa-spinner fa-spin"></i> Mengaktifkan...');

                                        $.ajax({
                                            url:'proses_qa.php?action=restore_alat',
                                            type:'POST',
                                            dataType:'json',
                                            data:{
                                                no_part:noAlatArsip,
                                                archive_table:res.archive_table||'',
                                                archive_no_part:noAlatArsip,
                                                nomor_arsip:(noArsip==='-'?'':noArsip)
                                            },
                                            success:function(rr){
                                                if(rr&&rr.status==='success'){
                                                    dlg.close();
                                                    BootstrapDialog.alert({
                                                        title:'Aktivasi Berhasil',
                                                        type:BootstrapDialog.TYPE_SUCCESS,
                                                        message:'Nomor <b>'+qaEsc(rr.no_part)+'</b> sudah kembali AKTIF.',
                                                        callback:function(){ location.reload(); }
                                                    });
                                                }else{
                                                    rb.data('restoreBusy',false).prop('disabled',false).html('<i class="fa fa-check"></i> Aktifkan Kembali Nomor Alat');
                                                    BootstrapDialog.alert({title:'Aktivasi Gagal',type:BootstrapDialog.TYPE_DANGER,message:qaEsc(rr&&rr.message||'Aktivasi gagal.')});
                                                }
                                            },
                                            error:function(xhr){
                                                rb.data('restoreBusy',false).prop('disabled',false).html('<i class="fa fa-check"></i> Aktifkan Kembali Nomor Alat');
                                                var msg='Request gagal.';
                                                try{ var er=JSON.parse(xhr.responseText||'{}'); if(er.message) msg=er.message; }catch(ex){ if(xhr.responseText) msg=xhr.responseText; }
                                                BootstrapDialog.alert({title:'Aktivasi Gagal',type:BootstrapDialog.TYPE_DANGER,message:qaEsc(msg)});
                                            }
                                        });
                                    });
                                } else openReg();
                            }
                        },error:function(){btn.html(textAsli).prop('disabled',false);BootstrapDialog.alert('Koneksi Error!');}});
                    });
                }


                // FORMAT U V1 - REGISTRASI MASTER + UPDATE HASIL SAJA + KOREKSI OTOMATIS
                /* ============================================================
                 * QA BUGFIX PACK v2 - A sampai U
                 * ========================================================== */
                qaNum = function(v){
                    if(v === null || v === undefined) return NaN;
                    let s=String(v).trim();
                    if(!s) return NaN;
                    s=s.replace(/[±%]/g,'').replace(/,/g,'.');
                    const m=s.match(/[+-]?\d+(?:\.\d+)?/);
                    return m ? parseFloat(m[0]) : NaN;
                };
                function qaTolValue(v, fallback){
                    if(typeof v === 'string' && /(\d+(?:\.\d+)?)\s*(?:x|\*)\s*res/i.test(v)){
                        const m = parseFloat(v.match(/(\d+(?:\.\d+)?)\s*(?:x|\*)\s*res/i)[1]);
                        const r = qaNum($('#qa_resolusi_value').val());
                        if(Number.isFinite(m) && Number.isFinite(r)) return Math.abs(m * r);
                    }
                    const n=qaNum(v);
                    return Number.isFinite(n) ? Math.abs(n) : (Number.isFinite(fallback)?Math.abs(fallback):NaN);
                }
                function qaGetDecimals(){
                    var rText = $('#qa_resolusi').val() || $('#qa_resolusi_value').val() || '';
                    var m = String(rText).replace(',', '.').match(/\.(\d+)/);
                    return m ? m[1].length : (String(rText).match(/^\d+$/) ? 0 : 4);
                }
                function qaFmt(n,d){ return Number.isFinite(n) ? Number(n).toFixed(d==null?qaGetDecimals():d) : ''; }
                function qaORange(master, isGo){
                    master=master||{};
                    const std=qaNum(isGo?master.go_std:master.notgo_std);
                    let lower=NaN, upper=NaN;
                    if(isGo){
                        const near=qaNum(master.go_tol_near), far=qaNum(master.go_tol_far);
                        if(Number.isFinite(std)&&Number.isFinite(near)&&Number.isFinite(far)){
                            lower=std+Math.min(near,far);
                            upper=std+Math.max(near,far);
                        }else{
                            const tm=qaNum(master.tol_minus), tp=qaNum(master.tol_plus);
                            if(Number.isFinite(std)&&Number.isFinite(tm)&&Number.isFinite(tp)){
                                lower=std+Math.min(tm,tp);
                                upper=std+Math.max(tm,tp);
                            }
                        }
                    }else{
                        const tol=qaNum(master.notgo_tol);
                        if(Number.isFinite(std)&&Number.isFinite(tol)){
                            lower=std-Math.abs(tol);
                            upper=std+Math.abs(tol);
                        }else{
                            const tm=qaNum(master.tol_minus), tp=qaNum(master.tol_plus);
                            if(Number.isFinite(std)&&Number.isFinite(tm)&&Number.isFinite(tp)){
                                lower=std+Math.min(tm,tp);
                                upper=std+Math.max(tm,tp);
                            }
                        }
                    }
                    return {min:lower,max:upper};
                }
                function qaOStatusHtml($cell, ready, ok){
                    const text=ready?(ok?'OK':'NOT GOOD'):'-';
                    $cell.text(text).removeClass('text-success text-danger').addClass(!ready?'':(ok?'text-success':'text-danger'));
                }
                function qaToDms(decimal){
                    if(!Number.isFinite(decimal)) return '';
                    const sign=decimal<0?'-':''; let x=Math.abs(decimal);
                    const deg=Math.floor(x+1e-10); x=(x-deg)*60;
                    const min=Math.floor(x+1e-10); let sec=(x-min)*60;
                    if(sec>=59.995){sec=0; if(min>=59){return sign+(deg+1)+'° 00\' 00"';} return sign+deg+'° '+String(min+1).padStart(2,'0')+'\' 00"';}
                    return sign+deg+'° '+String(min).padStart(2,'0')+'\' '+String(Math.round(sec)).padStart(2,'0')+'"';
                }
                function qaDmsDecimal(d,m,s){
                    const a=qaNum(d),b=qaNum(m),c=qaNum(s);
                    if(!Number.isFinite(a)||!Number.isFinite(b)||!Number.isFinite(c)) return NaN;
                    const sign=a<0?-1:1; return sign*(Math.abs(a)+Math.abs(b)/60+Math.abs(c)/3600);
                }

                function qaRegSchema(format){
                    const n=(keys,labels,rows)=>( {keys:keys,labels:labels,rows:rows} );
                    switch(String(format).toUpperCase()){
                        case 'A': return n(['posisi','std_spesifikasi','std_no_alat','std_out','tol_out','std_pitch','tol_pitch'],['Posisi','STD Spesifikasi','STD No Alat','STD OUT Ø','Tol OUT Ø','STD PITCH Ø','Tol PITCH Ø'],[{posisi:'GO'},{posisi:'NOT GO'}]);
                        case 'B': return n(['tekanan','handle','baut','std_min','std_max'],['Tekanan','Posisi Handle','Baut / Setting','Standar Min','Standar Max'],[{},{},{}]);
                        case 'C': return n(['setting','alat_std','toleransi'],['Setting (SV)','Alat STD','Toleransi'],[{},{},{}]);
                        case 'D': return n(['unit','mode','setting','standar'],['Unit','Mode','Setting','Standar'],[{},{},{}]);
                        case 'E': return n(['standar','toleransi'],['Standar','Toleransi'],[{},{ }]);
                        case 'F': return n(['zona','standar'],['Zona','Standar Scala Test'],[{zona:'B'},{zona:'B'},{zona:'B'}]);
                        case 'G': return n(['standar','toleransi'],['Standar','Toleransi'],[{},{ }]);
                        case 'H': return n(['posisi_ukur','std_spesifikasi','std_no_alat','std_out','tol_out','range_out','std_pitch','tol_pitch','range_pitch'],['Posisi','STD Spesifikasi','STD No Alat','STD OUT','Tol OUT','Range OUT','STD PITCH','Tol PITCH','Range PITCH'],[{posisi_ukur:'GO'},{posisi_ukur:'NOT GO'},{posisi_ukur:'PLUG'}]);
                        case 'I': return n(['spesifikasi_alat','std_spesifikasi','std_no_alat'],['Spesifikasi Alat yang Diperiksa','STD Spesifikasi','STD No Alat'],[{}]);
                        case 'J': return n(['spesifikasi','spesifikasi_alat_verifikasi'],['Spesifikasi','Spesifikasi Alat Verifikasi'],[{}]);
                        case 'K': return n(['nilai_ditentukan','toleransi_persen'],['Nilai Ditentukan','Toleransi (%)'],[{},{},{}]);
                        case 'L': return n(['arah','applied_value','toleransi'],['Uji','Applied','Toleransi'],[{arah:'NAIK'},{arah:'NAIK'},{arah:'TURUN'},{arah:'TURUN'}]);
                        case 'M': return n(['jenis','std_x','toleransi_x','std_y','toleransi_y','std_angle','toleransi_angle'],['Jenis','STD X Axis (mm)','Tol X','STD Y Axis (mm)','Tol Y','STD Angle (°)','Tol Angle'],[{jenis:'AXIS'},{jenis:'ANGLE'}]);
                        case 'N': return n(['standar','toleransi'],['Standar','Toleransi'],[{},{},{}]);
                        case 'O': return n(['spesifikasi','tol_plus','tol_minus','go_std','go_tol_near','go_tol_far','notgo_std','notgo_tol'],['Spesifikasi','Tol +','Tol -','GO Alat','GO Tol Near','GO Tol Far','NOT GO Alat','NOT GO Tol'],[{}]);
                        case 'P': return n(['type_ukuran','alat_nama','alat_no','std_label','toleransi'],['Type dan Ukuran','Nama Alat','No Alat','Standar','Toleransi'],[{type_ukuran:'',alat_nama:'',alat_no:'',std_label:'',toleransi:''}]);
                        case 'Q': return n(['std_spesifikasi','std_no_alat','std','tol_minus','tol_plus'],['Spesifikasi Alat Standar','No Alat Standar','Standar Ukuran','Tol. Bawah','Tol. Atas'],[{bagian:'OUT',tol_minus:'-0',tol_plus:'+0'},{bagian:'PITCH',tol_minus:'-0',tol_plus:'+0'}]);
                        case 'R': return n(['bagian','standar','toleransi'],['Posisi','Standar','Toleransi'],[{bagian:'Ø Out'},{bagian:'Ø Dalam'},{bagian:'Height'}]);
                        case 'T': return n(['posisi','spesifikasi_standar','no_alat_standar','standar','tol_minus','tol_plus'],['Posisi','Spesifikasi Alat Standar','No Alat Standar','Standar Ukuran','Tol. Bawah','Tol. Atas'],[{posisi:'GO',tol_minus:'',tol_plus:''},{posisi:'NOT GO',tol_minus:'',tol_plus:''}]);
                        case 'U': return n(['standar'],['Standar'],[{standar:''},{standar:''},{standar:''}]);
                        // FORMAT V V1 - BAGIAN DROPDOWN + STANDAR MASTER; HASIL DIISI SAAT UPDATE
                        case 'V': return n(['bagian','standar'],['Bagian','Standar'],[{bagian:'OUTSIDE',standar:''},{bagian:'INSIDE',standar:''},{bagian:'DEPTH',standar:''}]);
                        // FORMAT W V2 - MASTER STANDAR KOSONG; USER TAMBAH/ISI SENDIRI
                        case 'W': return n(['standar'],['Standar (derajat)'],[{standar:''}]);
                    }
                    return n(['standar','toleransi'],['Standar','Toleransi'],[{}]);
                }

                function qaCommonGroup(id){ const $e=$(id); return $e.length ? $e.closest('.form-group') : $e; }
                function qaRegVisibility(format){
                    format=String(format||'').toUpperCase();
                    
                    // Default Show all
                    ['#reg_type','#reg_spesifikasi','#reg_resolusi','#reg_metode','#reg_standar_uji','#reg_toleransi','#reg_satuan','#reg_trace'].forEach(x=>qaCommonGroup(x).show());

                    // Conditional Hiding based on Whitelists
                    // FORMAT W INPUT V3 - RESOLUSI + STANDAR TOLERANSI WAJIB TERSEDIA UNTUK MASTER/PRINTING W
                    if(!['C','D','E','F','G','K','L','M','N','R','U','V','W'].includes(format)) qaCommonGroup('#reg_resolusi').hide();
                    if(!['A','B','C','D','E','G','K','L','M','N','T','U','V','W'].includes(format)) qaCommonGroup('#reg_toleransi').hide();
                    if(!['B','C','E','F','G','L','M','N','O','U','V'].includes(format)) qaCommonGroup('#reg_satuan').hide();

                    const hiddenBase={
                        I:['#reg_spesifikasi'],
                        J:['#reg_standar_uji','#reg_trace'],
                        O:['#reg_spesifikasi'],
                        P:['#reg_spesifikasi']
                    };
                    (hiddenBase[format]||[]).forEach(x=>qaCommonGroup(x).hide());
                    if(format==='T') qaCommonGroup('#reg_toleransi').hide();

                    if($('#reg_spesifikasi').length){ $('#reg_spesifikasi').closest('.form-group').find('label').html(format==='J' ? 'Alat Verifikasi <span class="text-danger">*</span>' : 'Spesifikasi <span class="text-danger">*</span>'); }
                    if(format==='J'){ $('#reg-foto-j-target').append($('#reg_foto_group')).show(); } else { $('#reg-foto-home').append($('#reg_foto_group')); $('#reg-foto-j-target').hide().empty(); }
                }
                // QA UPDATE UI V8 - STATUS FINAL NETRAL + FORMAT T TAMPILKAN SUHU DAN KELEMBAPAN
                function qaUpdateFieldVisibility(format){
                    if(String(format||'').toUpperCase()==='T'){
                        ['#qa_nama_alat','#qa_spesifikasi','#qa_resolusi','#qa_metode','#qa_standar_uji','#qa_trace','#qa_toleransi','#qa_ketidakpastian','#qa_confidence','#qa_keterangan'].forEach(function(sel){ qaCommonGroup(sel).hide(); });
                        qaCommonGroup('#qa_suhu').show(); qaCommonGroup('#qa_kelembapan').show();
                        qaCommonGroup('#qa_kode_warna').show(); qaCommonGroup('#qa_no_gambar').show(); qaCommonGroup('#qa_no_laporan').show();
                        $('#qa_status_final').prop('disabled',false).css('background','');
                    } else { $('#qa_status_final').prop('disabled',false).css('background',''); }
                    format=String(format||'').toUpperCase();
                    ['#qa_spesifikasi','#qa_resolusi','#qa_metode','#qa_standar_uji','#qa_toleransi'].forEach(x=>qaCommonGroup(x).show());
                    
                    // FORMAT W UPDATE V3 - TAMPILKAN RESOLUSI + STANDAR TOLERANSI AGAR SNAPSHOT PRINT TERISI
                    if(!['C','D','E','F','G','K','L','M','N','R','U','V','W'].includes(format)) qaCommonGroup('#qa_resolusi').hide();
                    if(!['A','B','C','D','E','F','G','K','L','M','N','T','W'].includes(format)) qaCommonGroup('#qa_toleransi').hide();
                    if(format==='F'){ $('.qa-tol-label-normal, .qa-tol-group-normal').hide(); $('.qa-tol-group-f').show(); } else { $('.qa-tol-group-f').hide(); $('.qa-tol-label-normal, .qa-tol-group-normal').show(); }

                    const hiddenBase={
                        I:['#qa_spesifikasi'],
                        J:['#qa_metode','#qa_standar_uji'],
                        P:['#qa_spesifikasi']
                    };
                    (hiddenBase[format]||[]).forEach(x=>qaCommonGroup(x).hide());

                    $('#qa-type-row').toggle(format==='B');
                    if(format==='Q'){
                        ['#qa_nama_alat','#qa_spesifikasi','#qa_metode','#qa_standar_uji','#qa_trace'].forEach(function(sel){$(sel).prop('readonly',true).css('background','#f1f3f5');});
                        qaCommonGroup('#qa_resolusi').hide(); qaCommonGroup('#qa_toleransi').hide();
                        qaCommonGroup('#qa_ketidakpastian').hide(); qaCommonGroup('#qa_confidence').hide();
                        qaCommonGroup('#qa_no_gambar').hide();
                        qaCommonGroup('#qa_status_final').hide();
                        qaCommonGroup('#qa_keterangan').hide();
                        $('#qa_kode_warna').closest('.form-group').show();
                    } else {
                        ['#qa_nama_alat','#qa_spesifikasi','#qa_metode','#qa_standar_uji','#qa_trace'].forEach(function(sel){$(sel).prop('readonly',false).css('background','');});
                        qaCommonGroup('#qa_status_final').show(); qaCommonGroup('#qa_keterangan').show();
                    }
                    if(format==='T'){
                        ['#qa_nama_alat','#qa_spesifikasi','#qa_resolusi','#qa_metode','#qa_standar_uji','#qa_trace','#qa_toleransi','#qa_ketidakpastian','#qa_confidence','#qa_keterangan'].forEach(function(sel){ qaCommonGroup(sel).hide(); });
                        qaCommonGroup('#qa_suhu').show(); qaCommonGroup('#qa_kelembapan').show();
                        qaCommonGroup('#qa_kode_warna').hide(); qaCommonGroup('#qa_no_gambar').show(); qaCommonGroup('#qa_no_laporan').show(); qaCommonGroup('#qa_status_final').show();
                        $('#qa_status_final').prop('disabled',false).css('background','');
                    }
                    if($('#label-qa-spesifikasi').length){ $('#label-qa-spesifikasi').html(format==='J' ? 'Alat Verifikasi <span style="float:right;">:</span>' : 'Spesifikasi <span style="float:right;">:</span>'); }
                }
                const _qaOldMetaVisibility=qaApplyUpdateMetaVisibility;
                qaApplyUpdateMetaVisibility=function(format){ _qaOldMetaVisibility(format); qaUpdateFieldVisibility(format); };

                // FORMAT T REGISTER V5 - TOLERANSI DEFAULT KOSONG, TANDA +/- DIISI USER SENDIRI
                qaRenderRegTable=function(format,presetRows){
                    format=String(format||'').toUpperCase(); qaRegVisibility(format);
                    const sc=qaRegSchema(format); let rows=(presetRows&&presetRows.length)?presetRows:sc.rows;
                    const nHasBagian = false; // Format N rollback: tanpa Bagian/Periksa Kesejajaran
                    if(format==='N') rows=(rows||[]).filter(function(r){ return String(r.meta||r.jenis||'').toUpperCase()!=='N_KESEJAJARAN'; });
                    if(format==='F') rows=(rows||[]).filter(r=>r.meta!=='F');
                    if(format==='Q' && (!presetRows||!presetRows.length)) rows=[{bagian:'OUT',tol_minus:'-0',tol_plus:'+0'},{bagian:'PITCH',tol_minus:'-0',tol_plus:'+0'}];
                    if(format==='T' && (!presetRows||!presetRows.length)) rows=[{posisi:'GO',tol_minus:'',tol_plus:''},{posisi:'NOT GO',tol_minus:'',tol_plus:''}];
                    if(format==='T'){
                        // Format T: 1 nomor = 1 grup alat standar + 2 sub-baris GO / NOT GO.
                        let groups=[];
                        (rows||[]).forEach(function(r,idx){
                            let gid=String(r.group_id||'');
                            let g=gid?groups.find(x=>x.group_id===gid):null;
                            if(!g){
                                // Data lama belum punya group_id: pasangkan GO + NOT GO berurutan sebagai satu nomor.
                                if(!gid) gid='T'+(Math.floor(idx/2)+1);
                                g=groups.find(x=>x.group_id===gid);
                                if(!g){g={group_id:gid,spesifikasi_standar:r.spesifikasi_standar||'',resolusi_standar:r.resolusi_standar||'',no_alat_standar:r.no_alat_standar||'',items:[]};groups.push(g);}
                            }
                            if(!g.spesifikasi_standar) g.spesifikasi_standar=r.spesifikasi_standar||'';
                            if(!g.resolusi_standar) g.resolusi_standar=r.resolusi_standar||'';
                            if(!g.no_alat_standar) g.no_alat_standar=r.no_alat_standar||'';
                            g.items.push(r);
                        });
                        if(!groups.length) groups=[{group_id:'T1',spesifikasi_standar:'',resolusi_standar:'',no_alat_standar:'',items:[{posisi:'GO',tol_minus:'',tol_plus:''},{posisi:'NOT GO',tol_minus:'',tol_plus:''}]}];
                        let ht='<tr><th style="width:5%">No</th><th style="width:18%">Spesifikasi Alat Standar</th><th style="width:15%">No Alat Standar</th><th style="width:10%">Posisi</th><th style="width:16%">Standar Ukuran</th><th style="width:13%">Tol. Bawah (-)</th><th style="width:13%">Tol. Atas (+)</th><th style="width:10%"><button type="button" class="btn btn-sm btn-success" id="btn_add_reg_row"><i class="fa fa-plus"></i> Tambah</button></th></tr>';
                        $('#tabel_reg_standar thead').html(ht);
                        let bt='';
                        groups.forEach(function(g,gi){
                            let go=(g.items||[]).find(r=>String(r.posisi||'').toUpperCase()==='GO')||{};
                            let ng=(g.items||[]).find(r=>String(r.posisi||'').toUpperCase()==='NOT GO')||{};
                            function sub(r,pos,isFirst){
                                let x='<tr class="qa-t-row" data-t-group="'+qaEsc(g.group_id)+'">';
                                if(isFirst){
                                    x+='<td rowspan="2" class="text-center" style="vertical-align:middle"><b>'+(gi+1)+'</b></td>';
                                    x+='<td rowspan="2" style="vertical-align:middle"><input type="text" class="form-control qa-t-master" data-key="spesifikasi_standar" value="'+qaEsc(g.spesifikasi_standar||'')+'" placeholder="Contoh: 25 ~ 50 mm"><input type="text" class="form-control qa-t-master" data-key="resolusi_standar" value="'+qaEsc(g.resolusi_standar||'')+'" placeholder="Resolusi, contoh: 0.001" style="margin-top:7px"></td>';
                                    x+='<td rowspan="2" style="vertical-align:middle"><input type="text" class="form-control qa-t-master" data-key="no_alat_standar" value="'+qaEsc(g.no_alat_standar||'')+'" placeholder="No alat standar"></td>';
                                }
                                x+='<td><select class="form-control qa-t-item" data-key="posisi" disabled><option '+(pos==='GO'?'selected':'')+'>GO</option><option '+(pos==='NOT GO'?'selected':'')+'>NOT GO</option></select><input type="hidden" class="qa-t-item" data-key="posisi" value="'+pos+'"></td>';
                                x+='<td><input type="text" class="form-control qa-t-item" data-key="standar" value="'+qaEsc(r.standar||'')+'"></td>';
                                x+='<td><input type="text" class="form-control qa-t-item" data-key="tol_minus" value="'+qaEsc(r.tol_minus!==undefined?r.tol_minus:'')+'"></td>';
                                x+='<td><input type="text" class="form-control qa-t-item" data-key="tol_plus" value="'+qaEsc(r.tol_plus!==undefined?r.tol_plus:'')+'"></td>';
                                if(isFirst) x+='<td rowspan="2" class="text-center" style="vertical-align:middle"><button type="button" class="btn btn-sm btn-danger qa-t-del-group"><i class="fa fa-trash"></i></button></td>';
                                x+='</tr>'; return x;
                            }
                            bt+=sub(go,'GO',true)+sub(ng,'NOT GO',false);
                        });
                        $('#body_reg_standar').html(bt); $('#tabel_reg_standar').data('qa-format',format); $('#container_btn_bagian').hide(); return;
                    }
                    if(format==='O'){
                        // FORMAT O - MASTER REGISTRASI:
                        // MIN/MAX bukan master; keduanya diisi saat update hasil kalibrasi.
                        const p=(rows&&rows.length?rows[0]:{})||{};
                        const noAlat=String($('#reg_nomor_alat').val()||'').trim().toUpperCase();
                        const hO=
                            '<tr>'+
                                '<th rowspan="3" style="width:5%;vertical-align:bottom;">NO</th>'+
                                '<th rowspan="3" style="width:12%;vertical-align:bottom;">NO ALAT</th>'+
                                '<th rowspan="3" style="width:28%;vertical-align:bottom;">SPESIFIKASI</th>'+
                                '<th colspan="3" style="text-align:left;">HASIL PERIKSA</th>'+
                            '</tr>'+
                            '<tr><th colspan="3" style="text-align:left;">STD UKURAN</th></tr>'+
                            '<tr>'+
                                '<th style="width:12%;">POSISI UKUR</th>'+
                                '<th style="width:18%;">ALAT</th>'+
                                '<th style="width:25%;">TOLERANSI</th>'+
                            '</tr>';
                        let bO='';
                        bO+='<tr class="qa-o-master-row">'+
                            '<td rowspan="2" class="text-center" style="vertical-align:top;"><b>1</b></td>'+
                            '<td rowspan="2" style="vertical-align:top;"><b>'+qaEsc(noAlat)+'</b></td>'+
                            '<td rowspan="2" style="vertical-align:middle;">'+
                                '<input type="text" class="form-control qa-reg-field" data-key="spesifikasi" value="'+qaEsc(p.spesifikasi||'')+'" placeholder="Spesifikasi">'+
                                '<label style="display:block;margin:8px 0 4px;">Tol +</label>'+
                                '<input type="text" class="form-control qa-reg-field" data-key="tol_plus" value="'+qaEsc(p.tol_plus||'')+'" placeholder="+0.000">'+
                                '<label style="display:block;margin:8px 0 4px;">Tol -</label>'+
                                '<input type="text" class="form-control qa-reg-field" data-key="tol_minus" value="'+qaEsc(p.tol_minus||'')+'" placeholder="-0.000">'+
                            '</td>'+
                            '<td style="vertical-align:middle;"><b>GO</b></td>'+
                            '<td><input type="text" class="form-control qa-reg-field" data-key="go_std" value="'+qaEsc(p.go_std||'')+'" placeholder="STD alat GO"></td>'+
                            '<td>'+
                                '<label style="display:block;margin:0 0 4px;font-size:11px;">Near</label>'+
                                '<input type="text" class="form-control qa-reg-field" data-key="go_tol_near" value="'+qaEsc(p.go_tol_near||'')+'" placeholder="-0.017">'+
                                '<label style="display:block;margin:7px 0 4px;font-size:11px;">Far</label>'+
                                '<input type="text" class="form-control qa-reg-field" data-key="go_tol_far" value="'+qaEsc(p.go_tol_far||'')+'" placeholder="-0.050">'+
                            '</td>'+
                        '</tr>';
                        bO+='<tr class="qa-o-master-row">'+
                            '<td style="vertical-align:middle;"><b>NOT GO</b></td>'+
                            '<td><input type="text" class="form-control qa-reg-field" data-key="notgo_std" value="'+qaEsc(p.notgo_std||'')+'" placeholder="STD alat NOT GO"></td>'+
                            '<td><input type="text" class="form-control qa-reg-field" data-key="notgo_tol" value="'+qaEsc(p.notgo_tol||'')+'" placeholder="±0.017"></td>'+
                        '</tr>';
                        $('#tabel_reg_standar thead').html(hO);
                        $('#body_reg_standar').html(bO);
                        $('#tabel_reg_standar').data('qa-format',format);
                        $('#container_btn_bagian').hide();
                        return;
                    }
                    if(format==='P'){
                        // Format P: registrasi dibuat per kelompok agar user tidak mengisi tabel laporan mentah.
                        // Satu kelompok = Type dan Ukuran + Alat yang Dipakai + beberapa STD (Standar + Toleransi).
                        let groups=[];
                        (rows||[]).forEach(function(r){
                            const groupId=String(r.group_id||'');
                            let g=null;
                            if(groupId) g=groups.find(x=>x.group_id===groupId);
                            if(!g){
                                const signature=[r.type_ukuran||r.type||'',r.alat_nama||'',r.alat_no||''].join('||');
                                g=groups.find(x=>x.signature===signature);
                                if(!g){ g={group_id:groupId||('P'+(groups.length+1)),signature:signature,type_ukuran:r.type_ukuran||r.type||'',alat_nama:r.alat_nama||'',alat_no:r.alat_no||'',stds:[]}; groups.push(g); }
                            }
                            g.stds.push({std_label:r.std_label||r.standar||'',toleransi_mode:r.toleransi_mode||((r.tol_plus!==undefined||r.tol_minus!==undefined)?'range':'pm'),toleransi:r.toleransi||'',tol_plus:r.tol_plus||'',tol_minus:r.tol_minus||''});
                        });
                        if(!groups.length) groups=[{group_id:'P1',type_ukuran:'',alat_nama:'',alat_no:'',stds:[{std_label:'',toleransi_mode:'pm',toleransi:'',tol_plus:'',tol_minus:''}]}];
                        let html='';
                        groups.forEach(function(g,gi){
                            html+='<tr class="qa-p-group" data-p-group="'+qaEsc(g.group_id)+'"><td colspan="4" style="padding:14px;background:#fff;text-align:left;border:2px solid #d9edf7;border-radius:4px;">'+
                                '<div class="row"><div class="col-md-4"><label>TYPE DAN UKURAN</label><input type="text" class="form-control qa-p-master" data-key="type_ukuran" value="'+qaEsc(g.type_ukuran||'')+'" placeholder="Contoh: SK-867 / U/Part ~S31060"></div>'+
                                '<div class="col-md-4"><label>NAMA ALAT YANG DIPAKAI</label><input type="text" class="form-control qa-p-master" data-key="alat_nama" value="'+qaEsc(g.alat_nama||'')+'" placeholder="Contoh: Outside micrometer"></div>'+
                                '<div class="col-md-4"><label>NO ALAT</label><input type="text" class="form-control qa-p-master" data-key="alat_no" value="'+qaEsc(g.alat_no||'')+'" placeholder="Contoh: 19.8 G419"></div></div>'+
                                '<div style="margin-top:12px"><table class="table table-bordered" style="margin-bottom:8px;background:#fff"><thead><tr><th style="width:6%">No</th><th style="width:18%">Standar</th><th style="width:16%">Mode Toleransi</th><th style="width:24%">Toleransi</th><th>Preview / Range</th><th style="width:8%">Aksi</th></tr></thead><tbody class="qa-p-std-body">';
                            (g.stds.length?g.stds:[{}]).forEach(function(r,si){
                                const mode=String(r.toleransi_mode||'pm');
                                const std=qaNum(r.std_label), tp=qaNum(r.tol_plus), tm=qaNum(r.tol_minus), t=qaNum(r.toleransi);
                                let min=NaN,max=NaN,pv=r.std_label||'';
                                if(mode==='range') { if(Number.isFinite(std)&&Number.isFinite(tp)&&Number.isFinite(tm)){min=std+Math.min(tp,tm);max=std+Math.max(tp,tm);} if(r.tol_plus!==''||r.tol_minus!=='') pv+=(r.tol_plus!==''?(' '+(String(r.tol_plus).charAt(0)==='-'?'':'+')+r.tol_plus):'')+'/'+(r.tol_minus!==''?((String(r.tol_minus).charAt(0)==='-'?'':'+')+r.tol_minus):''); }
                                else { if(Number.isFinite(std)&&Number.isFinite(t)){min=std-Math.abs(t);max=std+Math.abs(t);} if(r.toleransi) pv+=' ±'+r.toleransi; }
                                const rangeTxt=Number.isFinite(min)&&Number.isFinite(max)?(' ('+qaFmt(min,3)+' - '+qaFmt(max,3)+')'):'';
                                html+='<tr class="qa-p-std"><td class="text-center">'+(si+1)+'</td><td><input type="text" class="form-control qa-p-std-input" data-key="std_label" value="'+qaEsc(r.std_label||'')+'" placeholder="28"></td><td><select class="form-control qa-p-std-input qa-p-tol-mode" data-key="toleransi_mode"><option value="pm"'+(mode==='pm'?' selected':'')+'>± Simetris</option><option value="range"'+(mode==='range'?' selected':'')+'>+ / - Asimetris</option></select></td><td><div class="qa-p-tol-pm"'+(mode==='range'?' style="display:none"':'')+'><input type="text" class="form-control qa-p-std-input" data-key="toleransi" value="'+qaEsc(r.toleransi||'')+'" placeholder="0.008"></div><div class="qa-p-tol-range"'+(mode!=='range'?' style="display:none"':'')+'><div class="row"><div class="col-xs-6"><input type="text" class="form-control qa-p-std-input" data-key="tol_plus" value="'+qaEsc(r.tol_plus||'')+'" placeholder="+0.07"></div><div class="col-xs-6"><input type="text" class="form-control qa-p-std-input" data-key="tol_minus" value="'+qaEsc(r.tol_minus||'')+'" placeholder="+0.02 / -0.05"></div></div></div></td><td class="qa-p-preview text-center" style="font-weight:bold;vertical-align:middle">'+qaEsc(pv+rangeTxt)+'</td><td class="text-center"><button type="button" class="btn btn-sm btn-danger qa-p-del-std"><i class="fa fa-trash"></i></button></td></tr>';
                            });
                            html+='</tbody></table><button type="button" class="btn btn-sm btn-success qa-p-add-std"><i class="fa fa-plus"></i> Tambah STD Ukuran</button>'+(groups.length>1?' <button type="button" class="btn btn-sm btn-danger qa-p-del-group"><i class="fa fa-trash"></i> Hapus Tipe dan Alat Ukur</button>':'')+'</div></td></tr>';
                        });
                        html+='<tr><td colspan="4" class="text-right" style="padding:10px"><button type="button" class="btn btn-info" id="qa_p_add_group"><i class="fa fa-plus"></i> Tambah TYPE DAN UKURAN + ALAT UKUR</button></td></tr>';
                        $('#tabel_reg_standar thead').html('<tr><th colspan="4" class="text-left">FORMAT P - MASTER PEMERIKSAAN <small style="font-weight:normal">(Aktual diisi saat update kalibrasi)</small></th></tr>');
                        $('#body_reg_standar').html(html); $('#tabel_reg_standar').data('qa-format',format); $('#container_btn_bagian').hide(); return;
                    }
                    if(format==='M'){
                        const axisRows=(rows||[]).filter(r=>String(r.jenis||'AXIS').toUpperCase()==='AXIS');
                        const angleRows=(rows||[]).filter(r=>String(r.jenis||'').toUpperCase()==='ANGLE');
                        const ax=axisRows.length?axisRows:[{jenis:'AXIS',std_x:'',std_y:''}];
                        const an=angleRows.length?angleRows:[{jenis:'ANGLE',std_angle:''}];
                        const ar=ax.find(r=>r.toleransi_axis||r.toleransi_x||r.toleransi_y)||{};
                        const gr=an.find(r=>r.toleransi_angle||r.toleransi_sudut)||{};
                        const ta=ar.toleransi_axis||ar.toleransi_x||ar.toleransi_y||'';
                        const tg=gr.toleransi_angle||gr.toleransi_sudut||'';
                        let bM='';
                        bM+='<tr style="background:#fff;"><th colspan="4" style="vertical-align:middle; padding:9px 10px;"><span>AXIS</span><button type="button" class="btn btn-xs btn-success btn-add-m-row pull-right" data-kind="AXIS"><i class="fa fa-plus"></i> Tambah Standar Axis</button></th></tr>';
                        bM+='<tr style="background:#fff;"><th style="width:8%">No</th><th colspan="2">STD AXIS (mm)</th><th style="width:12%">Aksi</th></tr>';
                        ax.forEach(function(r,i){bM+='<tr data-m-kind="AXIS" style="background:#fff;"><td class="text-center">'+(i+1)+'</td><td colspan="2"><input type="text" class="form-control qa-reg-field" data-key="std_mm" value="'+qaEsc(r.std_mm||r.std_x||r.std_y||'')+'" placeholder="STD Axis (mm)"><input type="hidden" class="qa-reg-field" data-key="jenis" value="AXIS"></td><td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-hapus-std"><i class="fa fa-trash"></i></button></td></tr>';});
                        bM+='<tr style="background:#fff;"><td colspan="2"><b>TOLERANSI AXIS (mm)</b></td><td colspan="2"><input type="text" id="reg_m_tol_axis" class="form-control" value="'+qaEsc(ta)+'" placeholder="Contoh: 0.005"></td></tr>';
                        bM+='<tr><td colspan="4" style="height:14px; padding:0; border-left:0; border-right:0; background:#fff;"></td></tr>';
                        bM+='<tr style="background:#fff;"><th colspan="4" style="vertical-align:middle; padding:9px 10px;"><span>ANGLE ACCURACY</span><button type="button" class="btn btn-xs btn-success btn-add-m-row pull-right" data-kind="ANGLE"><i class="fa fa-plus"></i> Tambah Standar Angle</button></th></tr>';
                        bM+='<tr style="background:#fff;"><th style="width:8%">No</th><th colspan="2">STD ANGLE (deg)</th><th style="width:12%">Aksi</th></tr>';
                        an.forEach(function(r,i){bM+='<tr data-m-kind="ANGLE" style="background:#fff;"><td class="text-center">'+(i+1)+'</td><td colspan="2"><input type="text" class="form-control qa-reg-field" data-key="std_angle" value="'+qaEsc(r.std_angle||r.std_degree||'')+'"><input type="hidden" class="qa-reg-field" data-key="jenis" value="ANGLE"></td><td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-hapus-std"><i class="fa fa-trash"></i></button></td></tr>';});
                        bM+='<tr style="background:#fff;"><td colspan="2"><b>TOLERANSI ANGLE (deg)</b></td><td colspan="2"><input type="text" id="reg_m_tol_angle" class="form-control" value="'+qaEsc(tg)+'" placeholder="Contoh: 0.1"></td></tr>';
                        $('#tabel_reg_standar thead').empty();
                        $('#body_reg_standar').html(bM); $('#tabel_reg_standar').data('qa-format',format); $('#container_btn_bagian').hide(); return;
                    }
                    let h='<tr><th style="width:5%">No</th>';
                    if(format==='Q') h+='<th style="width:10%">Parameter</th>'; else if(format==='N'&&nHasBagian) h+='<th>Bagian</th>';
                    sc.labels.forEach(l=>h+='<th>'+qaEsc(l)+'</th>');
                    h+='<th style="width:8%"><button type="button" class="btn btn-sm btn-success" id="btn_add_reg_row"><i class="fa fa-plus"></i> Tambah</button></th></tr>';
                    $('#tabel_reg_standar thead').html(h);
                    if(format==='Q'){ $('#tabel_reg_standar thead').prepend('<tr><th colspan="7" style="text-align:left;background:#f7fbff;padding:10px">FORMAT Q - MASTER PEMERIKSAAN <small style="font-weight:normal">Isi parameter, alat standar, standar ukuran, serta toleransi bawah/atas. Hasil ukur dan kode warna diisi saat kalibrasi.</small></th></tr>'); }
                    let b='';
                    (rows||[]).forEach(function(row,i){
                        b+='<tr data-reg-index="'+i+'"><td class="row-num text-center">'+(i+1)+'</td>';
                        if(format==='Q') b+='<td><select class="form-control qa-reg-field" data-key="bagian"><option '+(String(row.bagian||'OUT').toUpperCase()==='OUT'?'selected':'')+'>OUT</option><option '+(String(row.bagian||'').toUpperCase()==='PITCH'?'selected':'')+'>PITCH</option></select></td>';
                        if(format==='N'&&nHasBagian) b+='<td><select class="form-control qa-reg-field" data-key="bagian"><option value="OUTSIDE" '+(String(row.bagian||'OUTSIDE').toUpperCase()==='OUTSIDE'?'selected':'')+'>OUTSIDE</option><option value="INSIDE" '+(String(row.bagian||'').toUpperCase()==='INSIDE'?'selected':'')+'>INSIDE</option><option value="DEPTH" '+(String(row.bagian||'').toUpperCase()==='DEPTH'?'selected':'')+'>DEPTH</option></select></td>';
                        if(format==='T') b+='<td><select class="form-control qa-reg-field" data-key="posisi"><option '+(String(row.posisi||'GO').toUpperCase()==='GO'?'selected':'')+'>GO</option><option '+(String(row.posisi||'').toUpperCase()==='NOT GO'?'selected':'')+'>NOT GO</option></select></td>';
                        sc.keys.forEach(function(key){
                            if((format==='Q'||format==='T') && key=== (format==='Q'?'subtype':'posisi')) return;
                            let value=row&&row[key]!=null?row[key]:'';
                            let select=false,opts=[],optVals=[];
                            if(format==='A'&&key==='posisi'){select=true;opts=['GO','NOT GO'];}
                            if(format==='D'&&key==='unit'){select=true;opts=['V','mV','Ω'];}
                            if(format==='D'&&key==='mode'){select=true;opts=['AC','DC','Kosong']; if(String(value)==='') value='Kosong';}
                            if(format==='F'&&key==='zona'){select=true;opts=['Batas Bawah','Batas Atas'];optVals=['B','A'];}
                            if(format==='H'&&key==='posisi_ukur'){select=true;opts=['GO','NOT GO','PLUG'];}
                            if(format==='R'&&key==='bagian'){select=true;opts=['Ø Out','Ø Dalam','Height'];}
                            if(format==='V'&&key==='bagian'){select=true;opts=['OUTSIDE','INSIDE','DEPTH'];}
                            if(format==='L'&&key==='arah'){select=true;opts=['NAIK','TURUN'];}
                            if(format==='M'&&key==='jenis'){select=true;opts=['AXIS','ANGLE'];}
                            if(format==='Q'&&key==='subtype'){select=true;opts=['SERATION'];}
                            if(select) {
                                let optHtml = '';
                                if(optVals.length){
                                    opts.forEach((o,i)=>{ optHtml += '<option value="'+qaEsc(optVals[i])+'" '+(String(value).toUpperCase()===String(optVals[i]).toUpperCase()?'selected':'')+'>'+o+'</option>'; });
                                } else {
                                    optHtml = opts.map(o=>'<option '+(String(value).toUpperCase()===o.toUpperCase()?'selected':'')+'>'+o+'</option>').join('');
                                }
                                b+='<td><select class="form-control qa-reg-field" data-key="'+key+'">'+optHtml+'</select></td>';
                            }
                            else b+='<td><input type="text" class="form-control qa-reg-field" data-key="'+key+'" value="'+qaEsc(value)+'"></td>';
                        });
                        b+='<td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-hapus-std"><i class="fa fa-trash"></i></button></td></tr>';
                    });
                    if(format==='M'){
                        // Axis/Angle ditampilkan sebagai dua section sesuai laporan asli.
                        $('#tabel_reg_standar thead').html('<tr><th colspan="8" class="text-center">FORMAT M - AXIS DAN ANGLE ACCURACY</th></tr><tr><th>Jenis</th><th>STD X Axis</th><th>Tol X</th><th>STD Y Axis</th><th>Tol Y</th><th>STD Angle (°)</th><th>Tol Angle</th><th>Aksi</th></tr>');
                    }
                    $('#body_reg_standar').html(b); $('#tabel_reg_standar').data('qa-format',format);
                    $('#container_btn_bagian').hide(); // Format N rollback: tidak ada tombol Bagian
                };
                // Re-bind tombol Bagian setelah renderer registrasi patch terpasang.
                // Handler lama dibuat sebelum qaRenderRegTable tersedia, sehingga pada Format N
                // tombol bisa terlihat tetapi tidak merender kolom Bagian.
                $('#btn_toggle_bagian').off('click').on('click', function(e){
                    e.preventDefault();
                    const format=String($('#reg_pilih_format').val()||'').toUpperCase();
                    if(format==='N'){
                        // Tombol ini hanya milik halaman Daftar Alat. Jika Bagian sudah aktif,
                        // klik lagi berarti batalkan Bagian sebelum registrasi disimpan.
                        if(Number($(this).data('n-kesejajaran')||0)===1){
                            const normalRows=[];
                            $('#body_reg_standar tr').each(function(){
                                const o={};
                                $(this).find('.qa-reg-field').each(function(){ o[$(this).data('key')]=$(this).val(); });
                                if(String(o.meta||o.jenis||'').toUpperCase()==='N_KESEJAJARAN') return;
                                delete o.bagian;
                                if(Object.keys(o).length) normalRows.push(o);
                            });
                            $(this).data('n-kesejajaran',0);
                            qaRenderRegTable('N',normalRows.length?normalRows:[{standar:'',toleransi:''}]);
                            return false;
                        }
                        let currentRows=[];
                        $('#body_reg_standar tr').each(function(){
                            const o={};
                            $(this).find('.qa-reg-field').each(function(){ o[$(this).data('key')]=$(this).val(); });
                            // Kompatibel dengan tabel registrasi lama sebelum renderer Format N aktif.
                            if(!Object.keys(o).length){
                                const std=$(this).find('.inp-reg-standar').val();
                                const tol=$(this).find('.inp-reg-toleransi').val();
                                if(std!==undefined || tol!==undefined) currentRows.push({standar:std||'',toleransi:tol||''});
                            }else if(String(o.meta||o.jenis||'').toUpperCase()!=='N_KESEJAJARAN'){
                                currentRows.push(o);
                            }
                        });
                        const oldRows=currentRows.slice();
                        const rows=[
                            Object.assign({},oldRows[0]||{},{bagian:'OUTSIDE'}),
                            Object.assign({},oldRows[1]||{},{bagian:'INSIDE'}),
                            Object.assign({},oldRows[2]||{},{bagian:'DEPTH'})
                        ];
                        if(oldRows.length>3) rows.push.apply(rows,oldRows.slice(3));
                        $(this).data('n-kesejajaran',1);
                        qaRenderRegTable('N',rows);
                        return false;
                    }
                    pakaiBagian=!pakaiBagian;
                    const txt=format==='R'?'Posisi':'Bagian';
                    if(pakaiBagian){
                        $('.col-bagian').show();
                        $(this).html('<i class="fa fa-columns"></i> Hapus Kolom \''+txt+'\'').removeClass('btn-info').addClass('btn-warning');
                    }else{
                        $('.col-bagian').hide(); $('.inp-reg-bagian').val('');
                        $(this).html('<i class="fa fa-columns"></i> Tambah Kolom \''+txt+'\'').removeClass('btn-warning').addClass('btn-info');
                    }
                });

                qaCollectReg=function(format){
                    format=String(format||'').toUpperCase(); const rows=[];
                    if(format==='O'){
                        const o={}; $('#body_reg_standar .qa-reg-field').each(function(){o[$(this).data('key')]=$(this).val();});
                        return Object.keys(o).length?[o]:[];
                    }
                    if(format==='T'){
                        $('#body_reg_standar tr.qa-t-row').each(function(){
                            const $r=$(this), gid=String($r.data('t-group')||'T1');
                            const $first=$('#body_reg_standar tr.qa-t-row[data-t-group="'+gid+'"]').first();
                            const master={group_id:gid,spesifikasi_standar:$first.find('.qa-t-master[data-key="spesifikasi_standar"]').val()||'',resolusi_standar:$first.find('.qa-t-master[data-key="resolusi_standar"]').val()||'',no_alat_standar:$first.find('.qa-t-master[data-key="no_alat_standar"]').val()||''};
                            const o=Object.assign({},master);
                            $r.find('.qa-t-item').each(function(){ if($(this).is(':disabled')) return; o[$(this).data('key')]=$(this).val(); });
                            if(o.posisi) rows.push(o);
                        });
                        return rows;
                    }
                    if(format==='P'){
                        $('#body_reg_standar .qa-p-group').each(function(gi){
                            const $g=$(this);
                            const groupId='P'+(gi+1);
                            const master={
                                group_id:groupId,
                                type_ukuran:$g.find('.qa-p-master[data-key="type_ukuran"]').val()||'',
                                alat_nama:$g.find('.qa-p-master[data-key="alat_nama"]').val()||'',
                                alat_no:$g.find('.qa-p-master[data-key="alat_no"]').val()||''
                            };
                            $g.find('.qa-p-std').each(function(){
                                rows.push(Object.assign({},master,{
                                    std_label:$(this).find('.qa-p-std-input[data-key="std_label"]').val()||'',
                                    toleransi_mode:$(this).find('.qa-p-std-input[data-key="toleransi_mode"]').val()||'pm',
                                    toleransi:$(this).find('.qa-p-std-input[data-key="toleransi"]').val()||'',
                                    tol_plus:$(this).find('.qa-p-std-input[data-key="tol_plus"]').val()||'',
                                    tol_minus:$(this).find('.qa-p-std-input[data-key="tol_minus"]').val()||''
                                }));
                            });
                        });
                        return rows;
                    }
                    if(format==='M'){
                        const ta=$('#reg_m_tol_axis').val()||'', tg=$('#reg_m_tol_angle').val()||'';
                        $('#body_reg_standar tr[data-m-kind]').each(function(){
                            const o={}; $(this).find('.qa-reg-field').each(function(){o[$(this).data('key')]=$(this).val();});
                            if(String(o.jenis||'').toUpperCase()==='AXIS'){o.std_mm=o.std_mm||o.std_x||o.std_y||'';o.std_x=o.std_mm;o.std_y=o.std_mm;o.toleransi_axis=ta;o.toleransi_x=ta;o.toleransi_y=ta;}
                            else {o.toleransi_angle=tg;o.toleransi_sudut=tg;o.std_degree=o.std_angle||'';}
                            rows.push(o);
                        }); return rows;
                    }
                    $('#body_reg_standar tr').each(function(){ const o={}; $(this).find('.qa-reg-field').each(function(){o[$(this).data('key')]=$(this).val();}); if(format==='Q') o.bagian=o.bagian||''; if(Object.keys(o).length) rows.push(o); });
                    // Format N rollback: tidak menyimpan marker Bagian/Kesejajaran.
                    if(format==='F'){ rows.unshift({meta:'F',std_sec1:$('#reg_f_std_sec1').val().trim(),std_sec3:$('#reg_f_std_sec3').val().trim()}); }
                    return rows;
                };
                $(document).off('click.qaAddMRow').on('click.qaAddMRow','.btn-add-m-row',function(){
                    const kind=String($(this).data('kind')||'AXIS').toUpperCase(), rows=qaCollectReg('M');
                    rows.push(kind==='ANGLE'?{jenis:'ANGLE',std_angle:'',toleransi_angle:$('#reg_m_tol_angle').val()||''}:{jenis:'AXIS',std_mm:'',std_x:'',std_y:'',toleransi_axis:$('#reg_m_tol_axis').val()||''});
                    qaRenderRegTable('M',rows);
                });
                $(document).off('.qaFormatP')
                    .on('click.qaFormatP','#qa_p_add_group',function(){
                        // Tambah TYPE + ALAT UKUR = kelompok/tabel baru (contoh: U/Part + Sigmat Digital).
                        // Berbeda dengan Tambah STD Ukuran yang hanya menambah titik ukur di kelompok aktif.
                        const rows=qaCollectReg('P');
                        const nextGroup='P'+($('#body_reg_standar .qa-p-group').length+1);
                        rows.push({group_id:nextGroup,type_ukuran:'',alat_nama:'',alat_no:'',std_label:'',toleransi_mode:'pm',toleransi:'',tol_plus:'',tol_minus:''});
                        qaRenderRegTable('P',rows);
                        const $newGroup=$('#body_reg_standar .qa-p-group').last();
                        if($newGroup.length){
                            $newGroup.find('.qa-p-master[data-key="type_ukuran"]').attr('placeholder','Contoh: U/Part ~S31060');
                            $newGroup.find('.qa-p-master[data-key="alat_nama"]').attr('placeholder','Contoh: Sigmat digital');
                            $newGroup.find('.qa-p-master[data-key="alat_no"]').attr('placeholder','Contoh: A18001.10');
                            $('html,body').animate({scrollTop:$newGroup.offset().top-100},200);
                            $newGroup.find('.qa-p-master[data-key="type_ukuran"]').focus();
                        }
                    })
                    .on('click.qaFormatP','.qa-p-add-std',function(){
                        const $g=$(this).closest('.qa-p-group');
                        const gi=$('#body_reg_standar .qa-p-group').index($g);
                        const targetId='P'+(gi+1);
                        const rows=qaCollectReg('P');
                        const same=rows.filter(function(r){return String(r.group_id)===targetId;});
                        const base=same[0]||{group_id:targetId,type_ukuran:$g.find('[data-key="type_ukuran"]').val()||'',alat_nama:$g.find('[data-key="alat_nama"]').val()||'',alat_no:$g.find('[data-key="alat_no"]').val()||''};
                        const insertAt=rows.reduce(function(last,r,i){return String(r.group_id)===targetId?i:last;},-1);
                        const fresh=Object.assign({},base,{std_label:'',toleransi_mode:'pm',toleransi:'',tol_plus:'',tol_minus:''});
                        if(insertAt>=0) rows.splice(insertAt+1,0,fresh); else rows.push(fresh);
                        qaRenderRegTable('P',rows);
                    })
                    .on('click.qaFormatP','.qa-p-del-std',function(){ const $row=$(this).closest('.qa-p-std'),$g=$(this).closest('.qa-p-group'); if($g.find('.qa-p-std').length>1)$row.remove(); })
                    .on('click.qaFormatP','.qa-p-del-group',function(){ $(this).closest('.qa-p-group').remove(); })
                    .on('input.qaFormatP change.qaFormatP','.qa-p-std-input',function(){
                        const $r=$(this).closest('.qa-p-std'), mode=$r.find('[data-key="toleransi_mode"]').val()||'pm';
                        $r.find('.qa-p-tol-pm').toggle(mode==='pm'); $r.find('.qa-p-tol-range').toggle(mode==='range');
                        const stdTxt=$r.find('[data-key="std_label"]').val()||'', std=qaNum(stdTxt), tolTxt=$r.find('[data-key="toleransi"]').val()||'', plusTxt=$r.find('[data-key="tol_plus"]').val()||'', minusTxt=$r.find('[data-key="tol_minus"]').val()||'';
                        let min=NaN,max=NaN,pv=stdTxt;
                        if(mode==='range'){
                            const a=qaNum(plusTxt),b=qaNum(minusTxt); if(Number.isFinite(std)&&Number.isFinite(a)&&Number.isFinite(b)){min=std+Math.min(a,b);max=std+Math.max(a,b);}
                            if(plusTxt!==''||minusTxt!=='') pv+=(plusTxt!==''?(' '+(plusTxt.charAt(0)==='-'?'':'+')+plusTxt):'')+'/'+(minusTxt!==''?((minusTxt.charAt(0)==='-'?'':'+')+minusTxt):'');
                        } else { const t=qaNum(tolTxt); if(Number.isFinite(std)&&Number.isFinite(t)){min=std-Math.abs(t);max=std+Math.abs(t);} if(tolTxt)pv+=' ±'+tolTxt; }
                        $r.find('.qa-p-preview').text(pv+(Number.isFinite(min)&&Number.isFinite(max)?(' ('+qaFmt(min,3)+' - '+qaFmt(max,3)+')'):''));
                    });

                $(document).off('click.qaFormatT').on('click.qaFormatT','.qa-t-del-group',function(){
                    const gid=String($(this).closest('tr').data('t-group')||'');
                    if(gid) $('#body_reg_standar tr.qa-t-row').filter(function(){return String($(this).data('t-group')||'')===gid;}).remove();
                    $('#body_reg_standar tr.qa-t-row').each(function(i){ if(i%2===0) $(this).find('td:first b').text(Math.floor(i/2)+1); });
                });

                $(document).off('click.qaAddRegRow').on('click.qaAddRegRow','#btn_add_reg_row',function(){
                    const format=String($('#reg_pilih_format').val()||'').toUpperCase(); if(!format)return;
                    let rows=qaCollectReg(format).filter(r=>r.meta!=='F');
                    if(format==='Q'){ rows.push({bagian:'OUT',tol_minus:'-0',tol_plus:'+0'},{bagian:'PITCH',tol_minus:'-0',tol_plus:'+0'}); }
                    else if(format==='T'){ const gid='T'+(Math.floor(rows.length/2)+1); rows.push({group_id:gid,posisi:'GO',tol_minus:'',tol_plus:''},{group_id:gid,posisi:'NOT GO',tol_minus:'',tol_plus:''}); }
                    else if(format==='M'){ rows.push({jenis:'AXIS'}); }
                    else rows.push({});
                    qaRenderRegTable(format,rows);
                    $('#body_reg_standar tr:last .qa-reg-field').first().focus();
                });

                qaFillCommon=function(alat,history){
                    qaEnsureUpdateExtras(); const latest=history&&history.length?history[0]:null; const snap=latest?qaSnapshotFromHistory(latest):{};
                    const get=(name,fb)=>snap[name]!==''&&snap[name]!=null?snap[name]:(alat[name]!=null?alat[name]:(fb||''));
                    const format=String(alat.format_laporan||'A').toUpperCase();
                    $('#qa_nomor_alat').val(alat.no_part||''); $('#qa_nama_alat').val(get('nama_part','')); $('#qa_spesifikasi').val(format==='O' ? getAlatSpesifikasi(alat) : get('spesifikasi','')); $('#qa_resolusi').val(get('resolusi','')); $('#qa_type').val(get('type', alat.type || ''));
                    $('#qa_metode').val(get('metode_uji','')); $('#qa_standar_uji').val(get('standar_uji','')); $('#qa_trace').val(get('traceability','')); $('#qa_toleransi').val(get('toleransi_global',''));
                    if(get('format_laporan', '')==='F' && (get('toleransi_global','')||'').includes('|')){ const ft=get('toleransi_global','').split('|'); $('#qa_toleransi_f_rendah').val(ft[0]); $('#qa_toleransi_f_tinggi').val(ft[1]); }
                    $('#qa_suhu').val(snap.suhu||(latest&&latest.suhu)||''); $('#qa_kelembapan').val(snap.kelembapan||(latest&&latest.kelembapan)||''); $('#qa_ketidakpastian').val(snap.ketidakpastian||(latest&&latest.ketidakpastian)||''); $('#qa_confidence').val(snap.confidence_level||(latest&&latest.confidence_level)||'95 %');
                    $('#qa_keterangan').val(''); $('#qa_kode_warna').val(''); $('#qa_no_gambar').val(''); $('#qa_petugas').val('<?php echo addslashes($nama_petugas); ?>'); $('#qa_format_alat').val(format);
                    qaApplyUpdateMetaVisibility(format);
                    const laporan=get('no_laporan',latest&&latest.no_laporan?latest.no_laporan:''); $('#qa_no_laporan').val(laporan||'');
                    $('#qa_resolusi_value').val(get('resolusi','')); $('.qa_satuan_label').text(alat.satuan||''); $('.qa_satuan_kurung').html('( '+qaEsc(alat.satuan||'')+' )'); $('#qa_toleransi_value').val(alat.toleransi_global||'');
                };
                window.qaRenderUpdateEditor=function(alat,history){

                    currentAlatData=alat;
                    currentAlatHistory=history||[];

                    qaFillCommon(alat,history||[]);

                    const format=String(
                        alat.format_laporan || 'A'
                    ).toUpperCase();

                    if(format==='N'){
                        qaNColumns=[{key:'hasil_0',label:'Hasil'}];
                    }else{
                        qaNColumns=[
                            {
                                key:'hasil_0',
                                label:'Hasil'
                            }
                        ];
                    }


                    /*
                    * ============================================================
                    * DATA DASAR
                    * ============================================================
                    *
                    * Default tetap menggunakan titik standar dari master qa_part.
                    *
                    * Format lain TIDAK DIUBAH.
                    */
                    let renderPoints = qaStandardPoints(alat);


                    /*
                    * ============================================================
                    * FORMAT D - MULTIMETER
                    * ============================================================
                    *
                    * Untuk Format D:
                    *
                    * UNIT dan MODE harus mengikuti hasil kalibrasi TERAKHIR.
                    *
                    * Contoh:
                    *
                    * Master:
                    *      unit = V
                    *      mode = AC
                    *
                    * Kalibrasi terakhir:
                    *      unit = mV
                    *      mode = DC
                    *
                    * Maka ketika alat dibuka lagi:
                    *
                    *      UNIT = mV
                    *      MODE = DC
                    *
                    *
                    * Kalau mode terakhir kosong:
                    *
                    *      mode = ""
                    *
                    * maka dropdown akan menampilkan:
                    *
                    *      Kosong
                    *
                    * BUKAN otomatis AC.
                    *
                    *
                    * Perubahan ini HANYA berlaku Format D.
                    * Format A/B/C/E/F/... tidak terkena.
                    * ============================================================
                    */
                    if(
                        format === 'D' &&
                        history &&
                        history.length
                    ){

                        const latestStored = qaStoredHistory(
                            history[0]
                        );

                        const latestRows =
                            Array.isArray(latestStored.rows)
                                ? latestStored.rows
                                : [];


                        /*
                        * Kalau histori terakhir memiliki hasil_json,
                        * gabungkan dengan master berdasarkan index row.
                        *
                        * Master tetap menjadi fallback sehingga struktur
                        * tabel tidak berubah.
                        */
                        if(latestRows.length){

                            renderPoints = renderPoints.map(
                                function(masterRow, i){

                                    masterRow =
                                        masterRow &&
                                        typeof masterRow === 'object'
                                            ? masterRow
                                            : {};


                                    const latestRow =
                                        latestRows[i] &&
                                        typeof latestRows[i] === 'object'
                                            ? latestRows[i]
                                            : {};


                                    /*
                                    * Object.assign:
                                    *
                                    * master
                                    *    ↓
                                    * latest hasil kalibrasi
                                    *
                                    * Jadi data latest memiliki prioritas.
                                    */
                                    return Object.assign(
                                        {},
                                        masterRow,
                                        latestRow,
                                        {

                                            /*
                                            * UNIT
                                            *
                                            * Jangan pakai:
                                            *
                                            * latestRow.unit || masterRow.unit
                                            *
                                            * karena kita harus membedakan
                                            * antara value kosong dengan field
                                            * yang memang tidak tersedia.
                                            */
                                            unit:
                                                latestRow.unit != null
                                                    ? latestRow.unit
                                                    : (masterRow.unit || ''),


                                            /*
                                            * MODE
                                            *
                                            * Penting:
                                            *
                                            * mode = ""
                                            *
                                            * adalah VALUE VALID untuk Format D.
                                            *
                                            * Artinya user memilih "Kosong".
                                            *
                                            * Jadi jangan fallback ke AC/DC
                                            * kalau value memang string kosong.
                                            */
                                            mode:
                                                latestRow.mode != null
                                                    ? latestRow.mode
                                                    : (masterRow.mode || '')

                                        }
                                    );

                                }
                            );

                        }

                    }


                    /*
                    * Render tabel.
                    */
                    qaRenderUpdateTable(
                        format,
                        renderPoints
                    );


                    /*
                    * Reset hasil final karena user akan melakukan
                    * pengecekan / update baru.
                    */
                    $('#qa_status_final').val('');

                    $('#qa_hasil_json').val('');


                    /*
                    * Fokus ke field pertama.
                    */
                    $('.qa-update-field')
                        .first()
                        .focus();
                };

                qaRenderUpdateTable=function(format,points){
                    format=String(format||'A').toUpperCase(); qaApplyUpdateMetaVisibility(format); const $th=$('#tabel_input_kalibrasi thead'),$tb=$('#tabel_input_kalibrasi tbody'); let h='',b=''; points=Array.isArray(points)?points:[];
                    const rowStart=(i)=>'<tr data-index="'+i+'">', rowEnd='</tr>';
                    if(format==='A'){
                        h='<tr><th>No</th><th>Posisi</th><th>STD Spesifikasi</th><th>STD No Alat</th><th>STD OUT Ø</th><th>STD PITCH Ø</th><th>OUT 1</th><th>OUT 2</th><th>OUT 3</th><th>PITCH 1</th><th>PITCH 2</th><th>PITCH 3</th><th>Status</th></tr>';
                        points.forEach((p,i)=>{b+=rowStart(i)+'<td>'+(i+1)+'</td><td><b>'+qaEsc(p.posisi||'')+'</b></td><td>'+qaReadonly(p.std_spesifikasi)+'</td><td>'+qaReadonly(p.std_no_alat)+'</td><td>'+qaReadonly(p.std_out)+'<input type="hidden" data-key="std_out" value="'+qaEsc(p.std_out||'')+'"></td><td>'+qaReadonly(p.std_pitch)+'<input type="hidden" data-key="std_pitch" value="'+qaEsc(p.std_pitch||'')+'"></td><td>'+qaField('','out1')+'</td><td>'+qaField('','out2')+'</td><td>'+qaField('','out3')+'</td><td>'+qaField('','pitch1')+'</td><td>'+qaField('','pitch2')+'</td><td>'+qaField('','pitch3')+'</td><td class="qa-row-status">-</td>'+rowEnd;});
                    } else if(format==='B'){
                        h='<tr><th>No</th><th>Tekanan</th><th>Posisi Handle</th><th>Baut / Setting</th><th>Min</th><th>Max</th><th>Hasil 1</th><th>Hasil 2</th><th>Hasil 3</th><th>Rata-rata</th><th>Status</th></tr>';
                        points.forEach((p,i)=>{b+=rowStart(i)+'<td>'+(i+1)+'</td><td>'+qaField(p.tekanan,'tekanan')+'</td><td>'+qaField(p.handle,'handle')+'</td><td>'+qaField(p.baut,'baut')+'</td><td>'+qaReadonly(p.std_min)+'<input type="hidden" class="qa-update-field" data-key="std_min" value="'+qaEsc(p.std_min||'')+'"></td><td>'+qaReadonly(p.std_max)+'<input type="hidden" class="qa-update-field" data-key="std_max" value="'+qaEsc(p.std_max||'')+'"></td><td>'+qaField('','std1')+'</td><td>'+qaField('','std2')+'</td><td>'+qaField('','std3')+'</td><td class="qa-rata"></td><td class="qa-row-status">-</td>'+rowEnd;});
                    } else if(format==='C'){
                        h='<tr><th>No</th><th>Setting (SV)</th><th>Actual (PV)</th><th>Alat STD</th><th>Toleransi</th><th>Selisih</th><th>Status</th></tr>';
                        points.forEach((p,i)=>{b+=rowStart(i)+'<td>'+(i+1)+'</td><td>'+qaField(p.setting,'setting')+'</td><td>'+qaField('','actual')+'</td><td>'+qaField(p.alat_std,'alat_std')+'</td><td>'+qaReadonly(p.toleransi)+'</td><td class="qa-diff"></td><td class="qa-row-status">-</td>'+rowEnd;});
                    } else if(format==='D'){
                        h='<tr><th>No</th><th>Unit</th><th>Mode</th><th>Setting</th><th>Standar</th><th>Hasil</th><th>Koreksi</th><th>Status</th></tr>';
                        points.forEach((p,i)=>{b+=rowStart(i)+'<td>'+(i+1)+'</td><td>'+qaSelect(p.unit||'','unit',['V','mV','Ω'])+'</td><td>'+qaSelect(p.mode===''?'Kosong':p.mode,'mode',['AC','DC','Kosong'])+'</td><td>'+qaField(p.setting,'setting')+'</td><td>'+qaReadonly(p.standar)+'</td><td>'+qaField('','hasil')+'</td><td class="qa-koreksi"></td><td class="qa-row-status">-</td>'+rowEnd;});
                    } else if(format==='E'){
                        h='<tr><th>No</th><th>Standar</th><th>Toleransi</th><th>T1 Hasil</th><th>T1 Koreksi</th><th>T2 Hasil</th><th>T2 Koreksi</th><th>Status</th></tr>';
                        points.forEach((p,i)=>{b+=rowStart(i)+'<td>'+(i+1)+'</td><td>'+qaReadonly(p.standar)+'</td><td>'+qaReadonly(p.toleransi)+'</td><td>'+qaField('','t1_hasil')+'</td><td class="qa-t1-kor"></td><td>'+qaField('','t2_hasil')+'</td><td class="qa-t2-kor"></td><td class="qa-row-status">-</td>'+rowEnd;});
                    } else if (format === 'F') {
                        const meta = points.find(function(x){return x.meta==='F';}) || {std_sec1:'',std_sec3:''};
                        const scale = points.filter(function(x){return x.meta!=='F';});
                        
                        h = '<tr><th style="width:5%; text-align:center;">Bagian</th><th style="width:15%; text-align:center;">NO</th><th style="width:20%; text-align:center;">Standar</th><th style="width:20%; text-align:center;">Hasil / Pengukuran</th><th style="width:20%; text-align:center;">Koreksi / Deviasi</th><th style="width:20%; text-align:center;">Status</th></tr>';
                        
                        b += '<tr style="background-color:#f9f9f9;"><td rowspan="2" style="vertical-align:middle; text-align:center; font-weight:bold;">I</td><td colspan="5" style="font-weight:bold;">KEMAMPUAN ULANG ( REPEATABILITY )</td></tr>';
                        b += '<tr style="background-color:#f9f9f9;"><td></td><td style="text-align:center; vertical-align:middle;">'+qaReadonly(meta.std_sec1)+'</td><td style="text-align:center; vertical-align:middle;">'+qaField(meta.sec1_hasil || meta.std_sec1,'sec1_hasil')+'</td><td class="qa-sec1-dev text-center" style="vertical-align:middle;"></td><td></td></tr>';
                        
                        b += '<tr><td rowspan="'+(scale.length+1)+'" style="vertical-align:middle; text-align:center; font-weight:bold;">II</td><td colspan="5" style="font-weight:bold; text-align:center;">NILAI SCALA ( SCALA TEST )</td></tr>';
                        scale.forEach(function(p,i){
                            const bg = i % 2 === 0 ? 'background-color:#ffffff;' : 'background-color:#f2f2f2;';
                            const dropdown = '<div style="margin-top:5px;"><select class="form-control qa-update-field qa-f-zona-sec2" data-key="zona" style="display:inline-block; width:100%;"><option value="B" '+(String(p.zona||'B')==='B'?'selected':'')+'>Batas Bawah</option><option value="A" '+(String(p.zona||'B')==='A'?'selected':'')+'>Batas Atas</option></select></div>';
                            b+='<tr data-index="'+i+'" style="'+bg+'"><td style="text-align:center; vertical-align:middle;">'+(i+1)+'</td><td style="text-align:center; vertical-align:middle;">'+qaReadonly(p.standar)+dropdown+'</td><td style="text-align:center; vertical-align:middle;">'+qaField(p.sec2_hasil || p.standar,'sec2_hasil')+'</td><td class="qa-sec2-kor text-center" style="vertical-align:middle;"></td><td class="qa-row-status text-center" style="vertical-align:middle;">-</td></tr>';
                        });
                        
                        b += '<tr style="background-color:#e8f4f8;"><td rowspan="3" style="vertical-align:middle; text-align:center; font-weight:bold;">III</td><td colspan="5" style="font-weight:bold;">EFECT CENTER LOADING ( Standar: '+qaEsc(meta.std_sec3||'')+')</td></tr>';
                        b += '<tr style="background-color:#e8f4f8;"><td colspan="5"><div style="display:flex; justify-content:space-around; text-align:center;">' +
                                '<div><b>Tengah</b><br>'+qaField(meta.tengah || meta.std_sec3,'tengah')+'</div>' +
                                '<div><b>Belakang</b><br>'+qaField(meta.belakang || meta.std_sec3,'belakang')+'</div>' +
                                '<div><b>Kanan</b><br>'+qaField(meta.kanan || meta.std_sec3,'kanan')+'</div>' +
                                '<div><b>Depan</b><br>'+qaField(meta.depan || meta.std_sec3,'depan')+'</div>' +
                                '<div><b>Kiri</b><br>'+qaField(meta.kiri || meta.std_sec3,'kiri')+'</div>' +
                                '</div></td></tr>';
                        b += '<tr style="background-color:#e8f4f8;"><td><b>Perbedaan Dari Tengah</b></td><td colspan="4" class="qa-sec3-diff text-center" style="vertical-align:middle; font-size:16px; font-weight:bold;"></td></tr>';
                    } else if(format==='G'){
                        h='<tr><th>No</th><th>Standar</th><th>Toleransi</th><th>Hasil 1</th><th>Hasil 2</th><th>Status</th></tr>'; points.forEach((p,i)=>{b+=rowStart(i)+'<td>'+(i+1)+'</td><td>'+qaReadonly(p.standar)+'</td><td>'+qaReadonly(p.toleransi)+'</td><td>'+qaField('','hasil')+'</td><td>'+qaField('','hasil_2')+'</td><td class="qa-row-status">-</td>'+rowEnd;});
                    } else if(format==='H'){
                        h='<tr><th>No</th><th>Posisi</th><th>STD OUT</th><th>STD PITCH</th><th>OUT I</th><th>OUT II</th><th>OUT III</th><th>PITCH I</th><th>PITCH II</th><th>PITCH III</th><th>Status</th></tr>'; points.forEach((p,i)=>{b+=rowStart(i)+'<td>'+(i+1)+'</td><td>'+qaEsc(p.posisi_ukur||'')+'</td><td>'+qaReadonly(p.std_out)+'<input type="hidden" data-key="std_out" value="'+qaEsc(p.std_out||'')+'"></td><td>'+qaReadonly(p.std_pitch)+'<input type="hidden" data-key="std_pitch" value="'+qaEsc(p.std_pitch||'')+'"></td><td>'+qaField('','out_dep')+'</td><td>'+qaField('','out_ten')+'</td><td>'+qaField('','out_bel')+'</td><td>'+qaField('','pitch_dep')+'</td><td>'+qaField('','pitch_ten')+'</td><td>'+qaField('','pitch_bel')+'</td><td class="qa-row-status">-</td>'+rowEnd;});
                    } else if(format==='I'){
                        h='<tr><th>No</th><th>Spesifikasi Alat yang Diperiksa</th><th>STD Spesifikasi</th><th>STD No Alat</th><th>ATAS</th><th>BAWAH</th><th>Kode Warna</th><th>Status</th></tr>'; points.forEach((p,i)=>{b+=rowStart(i)+'<td>'+(i+1)+'</td><td>'+qaReadonly(p.spesifikasi_alat)+'</td><td>'+qaReadonly(p.std_spesifikasi)+'</td><td>'+qaReadonly(p.std_no_alat)+'</td><td>'+qaField('','hasil_atas')+'</td><td>'+qaField('','hasil_bawah')+'</td><td>'+qaField(p.kode_warna||'','kode_warna')+'</td><td class="qa-row-status">-</td>'+rowEnd;});
                    } else if(format==='J'){
                        h='<tr><th>No</th><th>No Alat</th><th>Spesifikasi</th><th>Spesifikasi Alat Verifikasi</th><th>Hasil</th><th>Status</th></tr>'; points.forEach((p,i)=>{b+=rowStart(i)+'<td>'+(i+1)+'</td><td>'+qaReadonly(alatForNoPart())+'</td><td>'+qaField(p.spesifikasi || $('#qa_spesifikasi').val(),'spesifikasi')+'</td><td>'+qaField(p.spesifikasi_alat_verifikasi || p.alat_verifikasi,'spesifikasi_alat_verifikasi')+'</td><td>'+qaSelect(p.hasil_verifikasi||'O','hasil_verifikasi',['O','X'])+'</td><td class="qa-row-status">-</td>'+rowEnd;});
                    } else if(format==='K'){
                        h='<tr><th>No</th><th>Nilai Ditentukan</th><th>Nilai Pengukuran</th><th>Selisih</th><th>Status</th>'; points.forEach((p,i)=>{b+=rowStart(i)+'<td>'+(i+1)+'</td><td>'+qaReadonly(p.nilai_ditentukan)+'</td><td>'+qaField('','nilai_pengukuran')+'</td><td class="qa-selisih"></td><td class="qa-row-status">-</td>'+rowEnd;}); h+='</tr>';
                    } else if(format==='L'){
                        h='<tr><th>No</th><th>Uji</th><th>Applied</th><th>Indicated</th><th>Correction</th><th>Status</th>'; points.forEach((p,i)=>{b+=rowStart(i)+'<td>'+(i+1)+'</td><td>'+qaSelect(p.arah||'NAIK','arah',['NAIK','TURUN'])+'</td><td>'+qaField(p.applied_value,'applied_value')+'</td><td>'+qaField('','indicated_value')+'</td><td class="qa-koreksi"></td><td class="qa-row-status">-</td>'+rowEnd;}); h+='</tr>';
                    } else if(format==='M'){
                        const ax=points.filter(p=>String(p.jenis||'').toUpperCase()==='AXIS'), an=points.filter(p=>String(p.jenis||'').toUpperCase()==='ANGLE');
                        const tolAxis=(ax[0]&&(ax[0].toleransi_axis||ax[0].toleransi_x||ax[0].toleransi_y))||'';
                        const tolAngle=(an[0]&&(an[0].toleransi_angle||an[0].toleransi_sudut))||'';
                        h='<tr style="background:#fff;"><th colspan="13" style="padding:10px; font-size:14px;">AXIS &nbsp; | &nbsp; Toleransi Axis: '+qaEsc(tolAxis)+' mm</th></tr>'+
                          '<tr style="background:#fff;"><th>STD</th><th style="display:none">Tol</th><th>X Left</th><th>Koreksi</th><th>X Right</th><th>Koreksi</th><th>STD</th><th style="display:none">Tol</th><th>Y Front</th><th>Koreksi</th><th>Y Back</th><th>Koreksi</th><th>Status</th></tr>';
                        ax.forEach((p,i)=>{const tm=p.toleransi_axis||p.toleransi_x||p.toleransi_y||tolAxis;b+=rowStart(i)+'<td>'+qaReadonly(p.std_x||p.std_mm)+'</td><td style="display:none">'+qaReadonly(tm)+'</td><td>'+qaField('','x_left')+'</td><td class="qa-m-corr-xl"></td><td>'+qaField('','x_right')+'</td><td class="qa-m-corr-xr"></td><td>'+qaReadonly(p.std_y||p.std_mm)+'</td><td style="display:none">'+qaReadonly(tm)+'</td><td>'+qaField('','y_front')+'</td><td class="qa-m-corr-yf"></td><td>'+qaField('','y_back')+'</td><td class="qa-m-corr-yb"></td><td class="qa-row-status">-</td>'+rowEnd;});
                        b+='<tr class="qa-m-section-gap"><td colspan="13" style="height:18px; padding:0; border-left:0; border-right:0; background:#fff;"></td></tr>';
                        b+='<tr class="qa-m-angle-title" style="background:#fff;"><th colspan="13" style="padding:10px; font-size:14px; text-align:center;">ANGLE ACCURACY &nbsp; | &nbsp; Toleransi Angle: '+qaEsc(tolAngle)+' deg</th></tr>';
                        b+='<tr class="qa-m-angle-head" style="background:#fff;"><th colspan="3">STD (deg)</th><th colspan="2">Hasil deg</th><th>&prime;</th><th>&Prime;</th><th colspan="3">Koreksi</th><th colspan="3">Status</th></tr>';
                        an.forEach((p,i)=>{const idx=100+i;b+=rowStart(idx)+'<td colspan="3">'+qaReadonly(p.std_angle||p.std_degree)+'</td><td colspan="2">'+qaField('','hasil_degree')+'</td><td>'+qaField('','hasil_minute')+'</td><td>'+qaField('','hasil_second')+'</td><td colspan="3" class="qa-m-angle-corr"></td><td colspan="3" class="qa-row-status">-</td>'+rowEnd;});
                    } else if(format==='N'){
                        const normalPoints=points.filter(p=>String(p.meta||p.jenis||'').toUpperCase()!=='N_KESEJAJARAN');
                        h='<tr><th>No</th><th>Standar</th><th>Toleransi</th><th>Hasil</th><th>Koreksi</th><th>Status</th></tr>';
                        normalPoints.forEach(function(p,i){
                            b+=rowStart(i)+'<td>'+(i+1)+'</td><td>'+qaReadonly(p.standar)+'<input type="hidden" data-n-key="standar" value="'+qaEsc(p.standar||'')+'"></td><td>'+qaReadonly(p.toleransi)+'<input type="hidden" data-n-key="toleransi" value="'+qaEsc(p.toleransi||'')+'"></td><td>'+qaField(p.hasil||'','hasil_pos_0',' data-n-result="0"')+'</td><td class="qa-koreksi"></td><td class="qa-row-status">-</td>'+rowEnd;
                        });
                    } else if(format==='O'){
                        const p=points[0]||{};
                        const goTol=[p.go_tol_near,p.go_tol_far].filter(function(v){return String(v==null?'':v).trim()!=='';}).join(' / ');
                        const goRange=qaORange(p,true), ngRange=qaORange(p,false);
                        const goMinStd=Number.isFinite(goRange.min)?qaFmt(goRange.min,3):'';
                        const goMaxStd=Number.isFinite(goRange.max)?qaFmt(goRange.max,3):'';
                        const ngMinStd=Number.isFinite(ngRange.min)?qaFmt(ngRange.min,3):'';
                        const ngMaxStd=Number.isFinite(ngRange.max)?qaFmt(ngRange.max,3):'';
                        h='<tr><th>Posisi Ukur</th><th>Alat</th><th>Toleransi</th><th>MIN (mm)</th><th>MAX (mm)</th><th>MIN (MASUK)</th><th>MAX (TIDAK MASUK)</th></tr>';
                        b+='<tr data-index="0" data-o-row="go">'+
                                '<td><b>GO</b></td>'+
                                '<td>'+qaReadonly(p.go_std)+'</td>'+
                                '<td>'+qaReadonly(goTol)+'</td>'+
                                '<td>'+qaField('','go_hasil_min',' placeholder="Std: '+qaEsc(goMinStd)+'"')+'</td>'+
                                '<td>'+qaField('','go_hasil_max',' placeholder="Std: '+qaEsc(goMaxStd)+'"')+'</td>'+
                                '<td class="qa-o-min-status" style="font-weight:bold;">-</td>'+
                                '<td class="qa-o-max-status" style="font-weight:bold;">-</td>'+
                                '<td class="qa-row-status" style="display:none;">-</td>'+
                            '</tr>'+
                            '<tr data-index="1" data-o-row="notgo">'+
                                '<td><b>NOT GO</b></td>'+
                                '<td>'+qaReadonly(p.notgo_std)+'</td>'+
                                '<td>'+qaReadonly(p.notgo_tol)+'</td>'+
                                '<td>'+qaField('','notgo_hasil_min',' placeholder="Std: '+qaEsc(ngMinStd)+'"')+'</td>'+
                                '<td>'+qaField('','notgo_hasil_max',' placeholder="Std: '+qaEsc(ngMaxStd)+'"')+'</td>'+
                                '<td class="qa-o-min-status" style="font-weight:bold;">-</td>'+
                                '<td class="qa-o-max-status" style="font-weight:bold;">-</td>'+
                                '<td class="qa-row-status" style="display:none;">-</td>'+
                            '</tr>';
                    } else if(format==='P'){
                        h='<tr><th>No</th><th>Type dan Ukuran</th><th>Nama Alat yang Dipakai</th><th>No Alat</th><th>STD Ukuran</th><th>Range</th><th>Aktual 1</th><th>Aktual 2</th><th>Status</th></tr>';
                        points.forEach(function(p,i){
                            const std=qaNum(p.std_label||p.standar), mode=String(p.toleransi_mode||((p.tol_plus!==undefined||p.tol_minus!==undefined)?'range':'pm'));
                            let min=qaNum(p.min), max=qaNum(p.max), stdText=p.std_label||p.standar||'';
                            if(mode==='range'){
                                const plus=qaNum(p.tol_plus), minus=qaNum(p.tol_minus);
                                if(Number.isFinite(std)&&Number.isFinite(plus)&&Number.isFinite(minus)){min=std+Math.min(plus,minus);max=std+Math.max(plus,minus);}
                                if(p.tol_plus!==''||p.tol_minus!=='') stdText+=(p.tol_plus!==''?(' '+(String(p.tol_plus).charAt(0)==='-'?'':'+')+p.tol_plus):'')+'/'+(p.tol_minus!==''?((String(p.tol_minus).charAt(0)==='-'?'':'+')+p.tol_minus):'');
                            } else {
                                const tol=qaNum(p.toleransi);
                                if(Number.isFinite(std)&&Number.isFinite(tol)){min=std-Math.abs(tol);max=std+Math.abs(tol);}
                                if(p.toleransi) stdText+=' ±'+p.toleransi;
                            }
                            const clean=function(v){return Number.isFinite(v)?Number(v.toFixed(6)).toString():'';};
                            const minText=clean(min), maxText=clean(max);
                            b+=rowStart(i).replace('>',' data-p-min="'+qaEsc(minText)+'" data-p-max="'+qaEsc(maxText)+'">')+
                                '<td>'+(i+1)+'</td><td>'+qaReadonly(p.type_ukuran||p.type||'')+'</td><td>'+qaReadonly(p.alat_nama||'')+'</td><td>'+qaReadonly(p.alat_no||'')+'</td><td>'+qaReadonly(stdText)+'</td><td class="qa-range">'+qaEsc(minText+(minText!==''&&maxText!==''?' ~ ':'')+maxText)+'</td><td>'+qaField('','actual1')+'</td><td>'+qaField('','actual2')+'</td><td class="qa-row-status">-</td>'+rowEnd;
                        });
                    } else if(format==='Q'){
                        h='<tr><th style="width:8%">Parameter</th><th>Alat Standar</th><th>No Alat Standar</th><th>Standar / Range</th><th style="width:11%">Hasil I</th><th style="width:11%">Hasil II</th><th style="width:11%">Hasil III</th><th style="width:10%">Status</th></tr>';
                        points.forEach(function(p,i){
                            const std=qaNum(p.std), lo=qaNum(p.tol_minus), hi=qaNum(p.tol_plus);
                            const end1=(Number.isFinite(std)&&Number.isFinite(lo))?std+lo:qaNum(p.min);
                            const end2=(Number.isFinite(std)&&Number.isFinite(hi))?std+hi:qaNum(p.max);
                            const min=(Number.isFinite(end1)&&Number.isFinite(end2))?Math.min(end1,end2):end1;
                            const max=(Number.isFinite(end1)&&Number.isFinite(end2))?Math.max(end1,end2):end2;
                            const clean=function(v){return Number.isFinite(v)?Number(v).toFixed(3):'';};
                            const stdText=String(p.std||'')+(String(p.tol_plus||'').trim()!==''?' '+String(p.tol_plus):'')+'/'+(String(p.tol_minus||'').trim()!==''?String(p.tol_minus):'');
                            b+=rowStart(i)+'<td><b>Ø '+qaEsc(p.bagian||((i%2===0)?'OUT':'PITCH'))+'</b></td><td>'+qaReadonly(p.std_spesifikasi)+'</td><td>'+qaReadonly(p.std_no_alat)+'</td><td>'+qaReadonly(stdText)+'<div style="margin-top:4px;font-size:12px;color:#666">Range: '+qaEsc(clean(min))+' ~ '+qaEsc(clean(max))+'</div></td><td>'+qaField('','actual1')+'</td><td>'+qaField('','actual2')+'</td><td>'+qaField('','actual3')+'</td><td class="qa-row-status" style="font-weight:bold">-</td>'+rowEnd;
                        });
                    } else if(format==='R'){
                        h='<tr><th>No</th><th>Posisi</th><th>Standar</th><th>Depan</th><th>Tengah</th><th>Belakang</th><th>Status</th></tr>'; points.forEach((p,i)=>{b+=rowStart(i)+'<td>'+(i+1)+'</td><td>'+qaSelect(p.bagian||'Ø Out','bagian',['Ø Out','Ø Dalam','Height'])+'</td><td>'+qaReadonly(p.standar)+'</td><td>'+qaField('','depan')+'</td><td>'+qaField('','tengah')+'</td><td>'+qaField('','belakang')+'</td><td class="qa-row-status">-</td>'+rowEnd;});
                    } else if(format==='U'){
                        // FORMAT U V1 - UPDATE HANYA INPUT HASIL; KOREKSI = STANDAR - HASIL
                        h='<tr><th style="width:8%">NO</th><th style="width:27%">STANDAR</th><th style="width:27%">HASIL</th><th style="width:23%">KOREKSI</th><th style="width:15%">STATUS</th></tr>';
                        points.forEach(function(p,i){b+=rowStart(i)+'<td>'+(i+1)+'</td><td>'+qaReadonly(p.standar)+'<input type="hidden" data-key="standar" value="'+qaEsc(p.standar||'')+'"><input type="hidden" data-key="toleransi" value="'+qaEsc(p.toleransi||'')+'"></td><td>'+qaField('','hasil')+'</td><td class="qa-u-koreksi" style="font-weight:bold"></td><td class="qa-row-status" style="font-weight:bold">-</td>'+rowEnd;});
                    } else if(format==='W'){
                        // FORMAT W V1 - UPDATE HANYA INPUT HASIL SUDUT; KOREKSI OTOMATIS DALAM MENIT
                        h='<tr><th style="width:10%">NO</th><th style="width:28%">STANDAR (derajat)</th><th style="width:32%">HASIL (derajat, menit)</th><th style="width:18%">KOREKSI (menit)</th><th style="width:12%">STATUS</th></tr>';
                        points.forEach(function(p,i){b+=rowStart(i)+'<td>'+(i+1)+'</td><td>'+qaReadonly(p.standar)+'<input type="hidden" data-key="standar" value="'+qaEsc(p.standar||'')+'"></td><td>'+qaField('','hasil')+'</td><td class="qa-w-koreksi"></td><td class="qa-row-status" style="font-weight:bold">-</td>'+rowEnd;});
                    } else if(format==='V'){
                        // FORMAT V V1 - UPDATE HANYA HASIL; BAGIAN/STANDAR DARI MASTER
                        h='<tr><th style="width:18%">BAGIAN</th><th style="width:25%">STANDAR</th><th style="width:25%">HASIL</th><th style="width:17%">KOREKSI</th><th style="width:15%">KEP</th></tr>';
                        points.forEach(function(p,i){b+=rowStart(i)+'<td style="font-weight:bold">'+qaEsc(p.bagian||'')+'</td><td>'+qaReadonly(p.standar)+'<input type="hidden" data-key="standar" value="'+qaEsc(p.standar||'')+'"></td><td>'+qaField('','hasil')+'</td><td class="qa-v-koreksi" style="font-weight:bold"></td><td class="qa-row-status" style="font-weight:bold">-</td>'+rowEnd;});
                    } else if(format==='T'){
                        // FORMAT T UPDATE V2 - KODE WARNA PER BARIS + RANGE 3 DESIMAL
                        h='<tr><th style="width:7%">Posisi</th><th>Spesifikasi Alat Standar</th><th>No Alat Standar</th><th style="width:18%">Standar / Toleransi / Range</th><th>Depan 1</th><th>Depan 2</th><th>Belakang 1</th><th>Belakang 2</th><th style="width:11%">Kode Warna</th><th style="width:9%">Status</th></tr>';
                        points.forEach(function(p,i){
                            // FORMAT T RANGE V4 - TANDA +/- MURNI MENGIKUTI INPUT USER, TANPA DIPAKSA BERDASARKAN KOLOM
                            const st=qaNum(p.standar), loRaw=(p.tol_minus!==undefined&&String(p.tol_minus)!=='')?p.tol_minus:(p.toleransi!==undefined?p.toleransi:''), hiRaw=(p.tol_plus!==undefined&&String(p.tol_plus)!=='')?p.tol_plus:(p.toleransi!==undefined?p.toleransi:'');
                            const lo=qaNum(loRaw), hi=qaNum(hiRaw); let mn=NaN,mx=NaN;
                            if(Number.isFinite(st)&&Number.isFinite(lo)&&Number.isFinite(hi)){const end1=st+lo,end2=st+hi;mn=Math.min(end1,end2);mx=Math.max(end1,end2);}
                            const sameTol=Number.isFinite(lo)&&Number.isFinite(hi)&&lo*hi<=0&&Math.abs(Math.abs(lo)-Math.abs(hi))<1e-9;
                            const signed3=function(v){return Number.isFinite(v)?((v>0?'+':'')+v.toFixed(3)):'';};
                            const tolText=sameTol?'±'+Math.abs(hi).toFixed(3):signed3(lo)+'/'+signed3(hi);
                            const stdInfo=qaEsc(Number.isFinite(st)?st.toFixed(3):String(p.standar||''))+' <span style="color:#666">'+qaEsc(tolText)+'</span><div style="margin-top:4px;font-size:12px;color:#666">Range: '+qaEsc(Number.isFinite(mn)?mn.toFixed(3):'')+' ~ '+qaEsc(Number.isFinite(mx)?mx.toFixed(3):'')+'</div>';
                            b+=rowStart(i).replace('>',' data-t-min="'+qaEsc(Number.isFinite(mn)?String(mn):'')+'" data-t-max="'+qaEsc(Number.isFinite(mx)?String(mx):'')+'">')+'<td><b>'+qaEsc(p.posisi||'')+'</b></td><td>'+qaReadonly(p.spesifikasi_standar)+'</td><td>'+qaReadonly(p.no_alat_standar)+'</td><td>'+stdInfo+'</td><td>'+qaField('','hasil_depan1')+'</td><td>'+qaField('','hasil_depan2')+'</td><td>'+qaField('','hasil_belakang1')+'</td><td>'+qaField('','hasil_belakang2')+'</td><td>'+qaField(p.kode_warna||'','kode_warna')+'</td><td class="qa-row-status" style="font-weight:bold">-</td>'+rowEnd;
                        });
                    }
                    $th.html(h); $tb.html(b);
                    if(format==='I'){ $th.find('th:last-child').hide(); $tb.find('.qa-row-status').hide(); }
                    if(format==='H'){ $tb.find('tr[data-index]').each(function(){ const idx=$(this).data('index'), p=points[idx]||{}; $(this).attr('data-tol-out',p.tol_out||'').attr('data-tol-pitch',p.tol_pitch||''); }); }
                    if(format==='F'){ const m=points.find(x=>x.meta==='F')||{}; $('.qa-f-zona-sec1').val(m.sec1_zona||'R'); $('.qa-f-zona-sec3').val(m.sec3_zona||'R'); }
                    $('#tabel_input_kalibrasi').addClass('qa-patched-table'); $('#btn_add_update_row').hide(); $('#btn_add_n_column,#qa_n_column_help').hide(); bindQaCalculations(format);
                };

                function qaGetFTol(zonaSel){ return qaTolValue(zonaSel==='A'?$('#qa_toleransi_f_tinggi').val():$('#qa_toleransi_f_rendah').val()); }
                function qaCalcF(){
                    const meta=qaStandardPoints(currentAlatData).find(x=>x.meta==='F')||{}; const s1=qaNum(meta.std_sec1),a1=qaNum($('#tabel_input_kalibrasi [data-key="sec1_hasil"]').val()),tol1=qaGetFTol($('.qa-f-zona-sec1').val()); const d1=Number.isFinite(s1)&&Number.isFinite(a1)?s1-a1:NaN; $('.qa-sec1-dev').text(Number.isFinite(d1)?qaFmt(d1):'');
                    let sec2Ok=true, sec2Any=false; $('#tabel_input_kalibrasi tbody tr[data-index]').each(function(){const $tr=$(this),s=qaNum($tr.find('td:eq(1) input').val()),a=qaNum($tr.find('[data-key="sec2_hasil"]').val()),c=Number.isFinite(s)&&Number.isFinite(a)?s-a:NaN,tRow=qaGetFTol($tr.find('.qa-f-zona-sec2').val());$tr.find('.qa-sec2-kor').text(Number.isFinite(c)?qaFmt(c):'');if(Number.isFinite(c)){sec2Any=true;const rowOk=Number.isFinite(tRow)&&Math.abs(c)<=tRow;if(!rowOk)sec2Ok=false;qaSetRowStatus($tr,rowOk);}});
                    const vals=['tengah','belakang','kanan','depan','kiri'].map(k=>qaNum($('#tabel_input_kalibrasi [data-key="'+k+'"]').val())); const cv=vals[0]; let d3=NaN,sec3Ok=true; const tol3=qaGetFTol($('.qa-f-zona-sec3').val()); if(Number.isFinite(cv)&&vals.slice(1).some(Number.isFinite)){const vv=vals.slice(1).filter(Number.isFinite); d3=Math.max.apply(null,vv.map(v=>Math.abs(v-cv))); sec3Ok=Number.isFinite(tol3)&&d3<=tol3;} $('.qa-sec3-diff').text(Number.isFinite(d3)?qaFmt(d3):'');
                    const s1Ok=Number.isFinite(d1)&&Number.isFinite(tol1)&&Math.abs(d1)<=tol1; const all=sec2Any&&s1Ok&&sec2Ok&&Number.isFinite(d3)&&sec3Ok; $('#qa_status_final').val(all?'OK':(sec2Any||Number.isFinite(d1)||Number.isFinite(d3)?'NOT GOOD':''));
                    return all;
                }

                bindQaCalculations=function(format){
                    $('#tabel_input_kalibrasi').off('input.qaCalc2 change.qaCalc2').on('input.qaCalc2 change.qaCalc2','.qa-update-field',function(){
                        const $tr=$(this).closest('tr');
                        if(format==='F'){qaCalcF();return;}
                        if(format==='A'){
                            const pp=(qaStandardPoints(currentAlatData)||[])[$tr.data('index')]||{}; const so=qaNum($tr.find('[data-key="std_out"]').val()),sp=qaNum($tr.find('[data-key="std_pitch"]').val()),to=qaTolValue(pp.tol_out,qaTolValue($('#qa_toleransi').val())),tp=qaTolValue(pp.tol_pitch,qaTolValue($('#qa_toleransi').val()));
                            const out=['out1','out2','out3'].map(k=>qaNum($tr.find('[data-key="'+k+'"]').val())),pitch=['pitch1','pitch2','pitch3'].map(k=>qaNum($tr.find('[data-key="'+k+'"]').val())); const ok=out.every(Number.isFinite)&&pitch.every(Number.isFinite)&&Number.isFinite(so)&&Number.isFinite(sp)&&Number.isFinite(to)&&Number.isFinite(tp)&&out.every(v=>Math.abs(v-so)<=to)&&pitch.every(v=>Math.abs(v-sp)<=tp); qaSetRowStatus($tr,(out.every(Number.isFinite)&&pitch.every(Number.isFinite))?ok:null);
                        } else if(format==='B'){
                            const vals=['std1','std2','std3'].map(k=>qaNum($tr.find('[data-key="'+k+'"]').val())).filter(Number.isFinite); const min=qaNum($tr.find('td:eq(4) input').val()),max=qaNum($tr.find('td:eq(5) input').val()); if(vals.length===3&&Number.isFinite(min)&&Number.isFinite(max)){const avg=vals.reduce((a,b)=>a+b,0)/3;$tr.find('.qa-rata').text(qaFmt(avg,2));qaSetRowStatus($tr,avg>=min&&avg<=max);} else {$tr.find('.qa-rata').text('');qaSetRowStatus($tr,null);}
                        } else if(format==='C'){
                            const a=qaNum($tr.find('[data-key="actual"]').val()),s=qaNum($tr.find('[data-key="alat_std"]').val()),d=a-s,t=qaTolValue($tr.find('td:eq(4) input').val(),qaTolValue($('#qa_toleransi').val()));$tr.find('.qa-diff').text(Number.isFinite(d)?qaFmt(d):'');qaSetRowStatus($tr,Number.isFinite(a)&&Number.isFinite(s)&&Number.isFinite(t)?Math.abs(d)<=t:null);
                        } else if(format==='D'){
                            const a=qaNum($tr.find('[data-key="hasil"]').val()),s=qaNum($tr.find('td:eq(4) input').val()),d=s-a,t=qaTolValue($('#qa_toleransi').val());$tr.find('.qa-koreksi').text(Number.isFinite(d)?qaFmt(d):'');qaSetRowStatus($tr,Number.isFinite(a)&&Number.isFinite(s)&&Number.isFinite(t)?Math.abs(d)<=t:null);
                        } else if(format==='E'){
                            const s=qaNum($tr.find('td:eq(1) input').val()),t=qaTolValue($tr.find('td:eq(2) input').val(),qaTolValue($('#qa_toleransi').val())),a=qaNum($tr.find('[data-key="t1_hasil"]').val()),b=qaNum($tr.find('[data-key="t2_hasil"]').val());const c1=Number.isFinite(a)&&Number.isFinite(s)?s-a:NaN,c2=Number.isFinite(b)&&Number.isFinite(s)?s-b:NaN;$tr.find('.qa-t1-kor').text(Number.isFinite(c1)?qaFmt(c1):'');$tr.find('.qa-t2-kor').text(Number.isFinite(c2)?qaFmt(c2):''); const hasVal=Number.isFinite(c1)||Number.isFinite(c2); const ok1=!Number.isFinite(c1)||Number.isFinite(t)&&Math.abs(c1)<=t; const ok2=!Number.isFinite(c2)||Number.isFinite(t)&&Math.abs(c2)<=t; qaSetRowStatus($tr,hasVal?(ok1&&ok2):null);
                        } else if(format==='G'){
                            const s=qaNum($tr.find('td:eq(1) input').val()),t=qaTolValue($tr.find('td:eq(2) input').val()),v1=qaNum($tr.find('[data-key="hasil"]').val()),v2=qaNum($tr.find('[data-key="hasil_2"]').val());
                            // Format G: batas toleransi bersifat inklusif. Tambahkan epsilon kecil
                            // untuk menghindari floating-point JS (contoh 15.9 - 15 = 0.9000000000000004).
                            const eps=Math.max(1e-12,Math.abs(s||0)*Number.EPSILON*10,Math.abs(t||0)*Number.EPSILON*10);
                            const inTol=function(v){return !Number.isFinite(v) || Math.abs(v-s)<=t+eps;};
                            qaSetRowStatus($tr, (Number.isFinite(s)&&Number.isFinite(t)&&(Number.isFinite(v1)||Number.isFinite(v2))) ? (inTol(v1)&&inTol(v2)) : null);
                        } else if(format==='H'){
                            const so=qaNum($tr.find('[data-key="std_out"]').val()),sp=qaNum($tr.find('[data-key="std_pitch"]').val()),to=qaTolValue($tr.data('tol-out')||$tr.find('[data-key="tol_out"]').val(),qaTolValue($('#qa_toleransi').val())),tp=qaTolValue($tr.data('tol-pitch')||$tr.find('[data-key="tol_pitch"]').val(),qaTolValue($('#qa_toleransi').val()));const ov=['out_dep','out_ten','out_bel'].map(k=>qaNum($tr.find('[data-key="'+k+'"]').val())),pv=['pitch_dep','pitch_ten','pitch_bel'].map(k=>qaNum($tr.find('[data-key="'+k+'"]').val()));qaSetRowStatus($tr,ov.every(Number.isFinite)&&pv.every(Number.isFinite)&&Math.abs(qaNum(ov[0])-so)<=to&&ov.every(v=>Math.abs(v-so)<=to)&&pv.every(v=>Math.abs(v-sp)<=tp)?true:null);
                        } else if(format==='I'){
                            const a=$tr.find('[data-key="hasil_atas"]').val().trim(),b=$tr.find('[data-key="hasil_bawah"]').val().trim(); qaSetRowStatus($tr,(!a&&!b)?null:(!/^(NG|X|NOT GOOD)$/i.test(a)&&!/^(NG|X|NOT GOOD)$/i.test(b)));
                        } else if(format==='J'){
                            const h=$tr.find('[data-key="hasil_verifikasi"]').val();qaSetRowStatus($tr,h?(/^(O|OK)$/i.test(h)):null);
                        } else if(format==='K'){
                            const d=qaNum($tr.find('td:eq(1) input').val()),a=qaNum($tr.find('[data-key="nilai_pengukuran"]').val()),tol=qaTolValue(currentAlatData&&qaStandardPoints(currentAlatData)[0]&&qaStandardPoints(currentAlatData)[0].toleransi_persen,qaTolValue($('#qa_toleransi').val())),diff=a-d;$tr.find('.qa-selisih').text(Number.isFinite(diff)?qaFmt(diff):'');qaSetRowStatus($tr,Number.isFinite(diff)&&Number.isFinite(tol)?Math.abs(diff)<=tol:null);
                        } else if(format==='L'){
                            const idx=$tr.data('index'),p=(qaStandardPoints(currentAlatData)||[])[idx]||{},a=qaNum($tr.find('[data-key="applied_value"]').val()),i=qaNum($tr.find('[data-key="indicated_value"]').val()),c=i-a,t=qaTolValue(p.toleransi,qaTolValue($('#qa_toleransi').val()));$tr.find('.qa-koreksi').text(Number.isFinite(c)?qaFmt(c,4):'');qaSetRowStatus($tr,Number.isFinite(c)&&Number.isFinite(t)?Math.abs(c)<=t:null);
                        } else if(format==='M'){
                            const j=String($tr.find('.qa-m-angle-corr').length?'ANGLE':'AXIS');
                            if(j==='AXIS'){
                                const sx=qaNum($tr.find('td:eq(0) input').val()),tx=qaTolValue($tr.find('td:eq(1) input').val()),xl=qaNum($tr.find('[data-key="x_left"]').val()),xr=qaNum($tr.find('[data-key="x_right"]').val()),sy=qaNum($tr.find('td:eq(6) input').val()),ty=qaTolValue($tr.find('td:eq(7) input').val()),yf=qaNum($tr.find('[data-key="y_front"]').val()),yb=qaNum($tr.find('[data-key="y_back"]').val());const cx=xl-sx,rx=xr-sx,cy=yf-sy,ry=yb-sy;$tr.find('.qa-m-corr-xl').text(Number.isFinite(cx)?qaFmt(cx,3):'');$tr.find('.qa-m-corr-xr').text(Number.isFinite(rx)?qaFmt(rx,3):'');$tr.find('.qa-m-corr-yf').text(Number.isFinite(cy)?qaFmt(cy,3):'');$tr.find('.qa-m-corr-yb').text(Number.isFinite(ry)?qaFmt(ry,3):'');qaSetRowStatus($tr,Number.isFinite(sx)&&Number.isFinite(tx)&&Number.isFinite(sy)&&Number.isFinite(ty)&&[xl,xr,yf,yb].every(Number.isFinite)&&Math.abs(cx)<=tx&&Math.abs(rx)<=tx&&Math.abs(cy)<=ty&&Math.abs(ry)<=ty?true:null);
                            } else { const sd=qaNum($tr.find('td:eq(0) input').val()),rd=qaNum($tr.find('[data-key="hasil_degree"]').val()),rm=qaNum($tr.find('[data-key="hasil_minute"]').val()),rs=qaNum($tr.find('[data-key="hasil_second"]').val()),out=qaDmsDecimal(rd,rm,rs),corr=out-sd,t=qaTolValue(currentAlatData&&qaStandardPoints(currentAlatData).find(x=>String(x.jenis||'').toUpperCase()==='ANGLE')&&qaStandardPoints(currentAlatData).find(x=>String(x.jenis||'').toUpperCase()==='ANGLE').toleransi_angle,qaTolValue($('#qa_toleransi').val()));$tr.find('.qa-m-angle-corr').text(Number.isFinite(corr)?qaToDms(corr):'');qaSetRowStatus($tr,Number.isFinite(out)&&Number.isFinite(sd)&&Number.isFinite(t)?Math.abs(corr)<=t:null); }
                        } else if(format==='N'){
                            const s=qaNum($tr.find('[data-n-key="standar"]').val()),t=qaTolValue($tr.find('[data-n-key="toleransi"]').val());
                            const a=qaNum($tr.find('[data-n-result="0"]').val()),c=Number.isFinite(a)&&Number.isFinite(s)?a-s:NaN;
                            $tr.find('.qa-koreksi').text(Number.isFinite(c)?c.toFixed(4):'');
                            qaSetRowStatus($tr,Number.isFinite(c)&&Number.isFinite(t)?(Math.abs(c)<=t?'OK':'NOT GOOD'):null);
                        } else if(format==='O'){
                            const master=(qaStandardPoints(currentAlatData)||[])[0]||{};
                            const isGo=String($tr.data('o-row')||'').toLowerCase()==='go';
                            const minKey=isGo?'go_hasil_min':'notgo_hasil_min';
                            const maxKey=isGo?'go_hasil_max':'notgo_hasil_max';
                            const minText=String($tr.find('[data-key="'+minKey+'"]').val()||'').trim();
                            const maxText=String($tr.find('[data-key="'+maxKey+'"]').val()||'').trim();
                            const actualMin=qaNum(minText), actualMax=qaNum(maxText);
                            const range=qaORange(master,isGo), lower=range.min, upper=range.max, eps=1e-9;
                            const minReady=minText!==''&&Number.isFinite(actualMin)&&Number.isFinite(lower)&&Number.isFinite(upper);
                            const maxReady=maxText!==''&&Number.isFinite(actualMax)&&Number.isFinite(lower)&&Number.isFinite(upper);
                            const minOk=minReady&&(actualMin>=lower-eps&&actualMin<=upper+eps);
                            const maxOk=maxReady&&(actualMax>=lower-eps&&actualMax<=upper+eps);

                            qaOStatusHtml($tr.find('.qa-o-min-status'),minReady,minOk);
                            qaOStatusHtml($tr.find('.qa-o-max-status'),maxReady,maxOk);
                            qaSetRowStatus($tr,(minReady&&maxReady)?(minOk&&maxOk):null);
                        } else if(format==='P'){
                            // Range Format P adalah master dari registrasi: jangan pernah dihitung ulang dari nilai Aktual.
                            const actual1Text=String($tr.find('[data-key="actual1"]').val()||'').trim();
                            const actual2Text=String($tr.find('[data-key="actual2"]').val()||'').trim();
                            const values=[];
                            if(actual1Text!==''){ const v1=qaNum(actual1Text); if(Number.isFinite(v1)) values.push(v1); }
                            if(actual2Text!==''){ const v2=qaNum(actual2Text); if(Number.isFinite(v2)) values.push(v2); }
                            const min=qaNum($tr.attr('data-p-min')), max=qaNum($tr.attr('data-p-max')), eps=1e-9;
                            const hasInput=(actual1Text!==''||actual2Text!=='');
                            const allNumeric=values.length===((actual1Text!==''?1:0)+(actual2Text!==''?1:0));
                            qaSetRowStatus($tr,(hasInput&&allNumeric&&Number.isFinite(min)&&Number.isFinite(max))?values.every(function(v){return v>=min-eps&&v<=max+eps;}):null);
                        } else if(format==='Q'){
                            const master=(qaStandardPoints(currentAlatData)||[])[$tr.data('index')]||{};
                            const std=qaNum(master.std), lo=qaNum(master.tol_minus), hi=qaNum(master.tol_plus);
                            const end1=(Number.isFinite(std)&&Number.isFinite(lo))?std+lo:qaNum(master.min);
                            const end2=(Number.isFinite(std)&&Number.isFinite(hi))?std+hi:qaNum(master.max);
                            const min=(Number.isFinite(end1)&&Number.isFinite(end2))?Math.min(end1,end2):end1;
                            const max=(Number.isFinite(end1)&&Number.isFinite(end2))?Math.max(end1,end2):end2;
                            const texts=['actual1','actual2','actual3'].map(function(k){return String($tr.find('[data-key="'+k+'"]').val()||'').trim();});
                            const nums=texts.filter(function(x){return x!=='';}).map(qaNum);
                            const has=texts.some(function(x){return x!=='';});
                            const allNumeric=nums.length===texts.filter(function(x){return x!=='';}).length && nums.every(Number.isFinite);
                            const eps=1e-9;
                            qaSetRowStatus($tr,(has&&allNumeric&&Number.isFinite(min)&&Number.isFinite(max))?nums.every(function(v){return v>=min-eps&&v<=max+eps;}):null);
                        } else if(format==='R'){
                            const idx=$tr.data('index'),p=(qaStandardPoints(currentAlatData)||[])[idx]||{},s=qaNum($tr.find('td:eq(2) input').val()),t=qaTolValue(p.toleransi||$('#qa_toleransi').val()),v=['depan','tengah','belakang'].map(k=>qaNum($tr.find('[data-key="'+k+'"]').val()));qaSetRowStatus($tr,v.every(Number.isFinite)&&Number.isFinite(s)&&Number.isFinite(t)?v.every(x=>Math.abs(x-s)<=t):null);
                        } else if(format==='U'){
                            // FORMAT U V1 - STATUS: ABS(STANDAR - HASIL) <= TOLERANSI
                            const master=(qaStandardPoints(currentAlatData)||[])[$tr.data('index')]||{},st=qaNum(master.standar),hv=qaNum($tr.find('[data-key="hasil"]').val()),tol=qaTolValue(master.toleransi,qaTolValue($('#qa_toleransi').val())),kor=(Number.isFinite(st)&&Number.isFinite(hv))?st-hv:NaN;
                            $tr.find('.qa-u-koreksi').text(Number.isFinite(kor)?qaFmt(kor,3):'');
                            qaSetRowStatus($tr,Number.isFinite(kor)&&Number.isFinite(tol)?Math.abs(kor)<=tol+1e-9:null);
                        } else if(format==='W'){
                            // FORMAT W V2 - TOLERANSI GLOBAL DALAM DERAJAT DIKONVERSI KE MENIT (0.1° = 6')
                            const master=(qaStandardPoints(currentAlatData)||[])[$tr.data('index')]||{},stdDeg=qaNum(master.standar),txt=String($tr.find('[data-key="hasil"]').val()||'').trim();
                            const nums=txt.replace(',','.').match(/[+-]?\d+(?:\.\d+)?/g)||[]; let hasilMin=NaN;
                            if(nums.length){const deg=parseFloat(nums[0]),min=nums.length>1?parseFloat(nums[1]):0,sec=nums.length>2?parseFloat(nums[2]):0;hasilMin=deg*60+(deg<0?-1:1)*(Math.abs(min)+Math.abs(sec)/60);}
                            const kor=(Number.isFinite(stdDeg)&&Number.isFinite(hasilMin))?stdDeg*60-hasilMin:NaN;
                            const tolDeg=qaTolValue($('#qa_toleransi').val()),tolMin=Number.isFinite(tolDeg)?tolDeg*60:NaN;
                            $tr.find('.qa-w-koreksi').text(Number.isFinite(kor)?qaFmt(kor,2).replace(/\.00$/,''):'');
                            qaSetRowStatus($tr,Number.isFinite(kor)&&Number.isFinite(tolMin)?Math.abs(kor)<=tolMin+1e-9:null);
                        } else if(format==='V'){
                            // FORMAT V V1 - KOREKSI = STANDAR - HASIL; KEP BERDASARKAN TOLERANSI GLOBAL
                            const master=(qaStandardPoints(currentAlatData)||[])[$tr.data('index')]||{},st=qaNum(master.standar),hv=qaNum($tr.find('[data-key="hasil"]').val()),tol=qaTolValue($('#qa_toleransi').val()),kor=(Number.isFinite(st)&&Number.isFinite(hv))?st-hv:NaN;
                            $tr.find('.qa-v-koreksi').text(Number.isFinite(kor)?qaFmt(kor,2):'');
                            qaSetRowStatus($tr,Number.isFinite(kor)&&Number.isFinite(tol)?Math.abs(kor)<=tol+1e-9:null);
                        } else if(format==='T'){
                            // FORMAT T STATUS V2 - SATU HASIL TERISI SUDAH LANGSUNG DINILAI
                            const mn=qaNum($tr.attr('data-t-min')), mx=qaNum($tr.attr('data-t-max')), eps=1e-9;
                            const texts=['hasil_depan1','hasil_depan2','hasil_belakang1','hasil_belakang2'].map(function(k){return String($tr.find('[data-key="'+k+'"]').val()||'').trim();});
                            const filled=texts.filter(function(v){return v!=='';});
                            const nums=filled.map(qaNum);
                            const valid=filled.length>0 && nums.length===filled.length && nums.every(Number.isFinite) && Number.isFinite(mn) && Number.isFinite(mx);
                            qaSetRowStatus($tr,valid?nums.every(function(v){return v>=mn-eps&&v<=mx+eps;}):null);
                        }
                        qaUpdateFinalFromRows(format);
                    });
                    if(format==='F') qaCalcF();
                    if(['G','M','O'].includes(format)) $('#tabel_input_kalibrasi .qa-update-field').trigger('change');
                };

                // QA FINAL STATUS V7 - AUTO HANYA REKOMENDASI, USER TETAP BISA UBAH SEMUA FORMAT
                qaUpdateFinalFromRows=function(format){
                    format=String(format||'').toUpperCase();
                    if(format==='F'){ const ok=qaCalcF(); if(ok)$('#qa_status_final').val('OK'); else $('#qa_status_final').val('NOT GOOD'); return; }
                    const $st=$('#tabel_input_kalibrasi .qa-row-status, #tabel_input_kalibrasi .td-status'); 
                    if(!$st.length)return; 
                    const vals=$st.map(function(){return $(this).text().trim().toUpperCase();}).get().filter(x=>x!==''); 
                    if(vals.length===0)return;
                    if(format==='Q'){
                        const decided=vals.filter(function(x){return x!=='-'&&x!=='';});
                        if(!decided.length){ $('#qa_status_final').val(''); return; }
                        if(decided.some(function(x){return /NOT GOOD|NG|X|TIDAK|FAIL/.test(x);})) $('#qa_status_final').val('NOT GOOD');
                        else if(decided.some(function(x){return /^OK|GOOD|LULUS|PASS$/.test(x);})) $('#qa_status_final').val('OK');
                        return;
                    }
                    if(vals.some(x=>/NOT GOOD|NG|X|TIDAK|FAIL/.test(x))) $('#qa_status_final').val('NOT GOOD'); 
                    else if(vals.every(x=>/^OK|GOOD|LULUS|PASS$/.test(x))) $('#qa_status_final').val('OK'); 
                };

                qaCollectUpdate=function(format){
                    format=String(format||'').toUpperCase(); const rows=[];
                    if(format==='F'){
                        const meta=(qaStandardPoints(currentAlatData)||[]).find(x=>x.meta==='F')||{}; const out={format:'F',sec1:{},sec2:[],sec3:{}};
                        const sec1=$('#tabel_input_kalibrasi [data-key="sec1_hasil"]').val(); out.sec1={standar:meta.std_sec1||'',hasil:sec1||'',deviasi:(sec1!==''&&Number.isFinite(qaNum(sec1)))?qaFmt(qaNum(sec1)-qaNum(meta.std_sec1)):''};
                        $('#tabel_input_kalibrasi tbody tr[data-index]').each(function(){out.sec2.push({standar:$(this).find('td:eq(1) input:eq(0)').val()||'',hasil:$(this).find('[data-key="sec2_hasil"]').val()||'',koreksi:$(this).find('.qa-sec2-kor').text(),zona:$(this).find('.qa-f-zona-sec2').val()});});
                        out.sec3={standar:meta.std_sec3||'',tengah:$('#tabel_input_kalibrasi [data-key="tengah"]').val()||'',belakang:$('#tabel_input_kalibrasi [data-key="belakang"]').val()||'',kanan:$('#tabel_input_kalibrasi [data-key="kanan"]').val()||'',depan:$('#tabel_input_kalibrasi [data-key="depan"]').val()||'',kiri:$('#tabel_input_kalibrasi [data-key="kiri"]').val()||'',perbedaan:$('#tabel_input_kalibrasi .qa-sec3-diff').text()};
                        return [out];
                    }
                    $('#tabel_input_kalibrasi tbody tr[data-index]').each(function(){const $tr=$(this),o={status:$tr.find('.qa-row-status').text()};$tr.find('.qa-update-field').each(function(){o[$(this).data('key')]=$(this).val();});
                        if(format==='A'){o.posisi=$tr.find('td:eq(1)').text();o.std_spesifikasi=$tr.find('td:eq(2) input').val()||'';o.std_no_alat=$tr.find('td:eq(3) input').val()||'';o.std_out=$tr.find('[data-key="std_out"]').val()||'';o.std_pitch=$tr.find('[data-key="std_pitch"]').val()||'';o.out={dep:o.out1||'',ten:o.out2||'',bel:o.out3||''};o.pitch={dep:o.pitch1||'',ten:o.pitch2||'',bel:o.pitch3||''};}
                        else if(format==='B'){const v=['std1','std2','std3'].map(k=>o[k]).filter(x=>x!==undefined);o.uji1=o.std1||'';o.uji2=o.std2||'';o.uji3=o.std3||'';o.hasil=$tr.find('.qa-rata').text();}
                        else if(format==='C'){o.selisih=$tr.find('.qa-diff').text();}
                        else if(format==='D'){o.standar=$tr.find('td:eq(4) input').val()||'';o.koreksi=$tr.find('.qa-koreksi').text();}
                        else if(format==='E'){o.standar=$tr.find('td:eq(1) input').val()||'';o.toleransi=$tr.find('td:eq(2) input').val()||'';o.t1_koreksi=$tr.find('.qa-t1-kor').text();o.t2_koreksi=$tr.find('.qa-t2-kor').text();}
                        else if(format==='G'){o.standar=$tr.find('td:eq(1)').text()||''; o.toleransi=$tr.find('td:eq(2)').text()||'';}
                        else if(format==='H'){const idx=$tr.data('index');const master=(qaStandardPoints(currentAlatData)||[])[idx]||{};o.posisi_ukur=$tr.find('td:eq(1)').text();o.std_out=$tr.find('[data-key="std_out"]').val()||'';o.std_pitch=$tr.find('[data-key="std_pitch"]').val()||'';o.out={dep:o.out_dep||'',ten:o.out_ten||'',bel:o.out_bel||''};o.pitch={dep:o.pitch_dep||'',ten:o.pitch_ten||'',bel:o.pitch_bel||''};o.tol_out=master.tol_out||'';o.range_out=master.range_out||'';o.tol_pitch=master.tol_pitch||'';o.range_pitch=master.range_pitch||'';o.std_spesifikasi=master.std_spesifikasi||'';o.std_no_alat=master.std_no_alat||'';o.kode_warna=$('#qa_kode_warna').val()||'';}
                        else if(format==='I'){const master=(qaStandardPoints(currentAlatData)||[])[$tr.data('index')]||{};o.spesifikasi_alat=master.spesifikasi_alat||'';o.std_spesifikasi=master.std_spesifikasi||'';o.std_no_alat=master.std_no_alat||'';o.putusan=o.status||'';}
                        else if(format==='K'){o.selisih=$tr.find('.qa-selisih').text();}
                        else if(format==='L'){o.correction=$tr.find('.qa-koreksi').text();}
                        else if(format==='M'){o.correction=($tr.find('.qa-m-angle-corr').text()||''); if($tr.find('.qa-m-corr-xl').length){o.x_left_correction=$tr.find('.qa-m-corr-xl').text();o.x_right_correction=$tr.find('.qa-m-corr-xr').text();o.y_front_correction=$tr.find('.qa-m-corr-yf').text();o.y_back_correction=$tr.find('.qa-m-corr-yb').text();o.std_x=$tr.find('td:eq(0) input').val()||'';o.toleransi_x=$tr.find('td:eq(1) input').val()||'';o.std_y=$tr.find('td:eq(6) input').val()||'';o.toleransi_y=$tr.find('td:eq(7) input').val()||'';o.std_mm=o.std_x;} else {o.std_degree=$tr.find('td:eq(0) input').val()||'';o.std_angle=o.std_degree;o.hasil_degree=$tr.find('[data-key="hasil_degree"]').val()||'';o.hasil_minute=$tr.find('[data-key="hasil_minute"]').val()||'';o.hasil_second=$tr.find('[data-key="hasil_second"]').val()||'';o.toleransi_angle=(qaStandardPoints(currentAlatData)||[]).find(x=>String(x.jenis||'').toUpperCase()==='ANGLE')?.toleransi_angle||'';}}
                        else if(format==='N'){const idx=$('#tabel_input_kalibrasi tbody tr[data-index]').index($tr),masters=(qaStandardPoints(currentAlatData)||[]).filter(x=>String(x.meta||x.jenis||'').toUpperCase()!=='N_KESEJAJARAN'),master=masters[idx]||{};o.standar=$tr.find('[data-n-key="standar"]').val()||master.standar||'';o.toleransi=$tr.find('[data-n-key="toleransi"]').val()||master.toleransi||'';o.hasil=$tr.find('[data-n-result="0"]').val()||'';o.koreksi=$tr.find('.qa-koreksi').text();}
                        else if(format==='O'){
                            const master=(qaStandardPoints(currentAlatData)||[])[0]||{};
                            const isGo=String($tr.data('o-row')||'').toLowerCase()==='go';
                            const range=qaORange(master,isGo);
                            const minStatus=String($tr.find('.qa-o-min-status').text()||'').trim();
                            const maxStatus=String($tr.find('.qa-o-max-status').text()||'').trim();
                            Object.assign(o,{
                                posisi_ukur:isGo?'GO':'NOT GO',
                                kode_warna:$('#qa_kode_warna').val(),
                                keputusan:o.status==='OK'?'OK':'NG',
                                no_alat:alatForNoPart(),
                                spesifikasi:master.spesifikasi||'',
                                tol_plus:master.tol_plus||'',
                                tol_minus:master.tol_minus||'',
                                go_std:master.go_std||'',
                                go_tol_near:master.go_tol_near||'',
                                go_tol_far:master.go_tol_far||'',
                                notgo_std:master.notgo_std||'',
                                notgo_tol:master.notgo_tol||''
                            });
                            if(isGo){
                                o.go_min=Number.isFinite(range.min)?qaFmt(range.min,3):'';
                                o.go_max=Number.isFinite(range.max)?qaFmt(range.max,3):'';
                                o.go_status_min=minStatus==='-'?'':minStatus;
                                o.go_status_max=maxStatus==='-'?'':maxStatus;
                            }else{
                                o.notgo_min=Number.isFinite(range.min)?qaFmt(range.min,3):'';
                                o.notgo_max=Number.isFinite(range.max)?qaFmt(range.max,3):'';
                                o.notgo_status_min=minStatus==='-'?'':minStatus;
                                o.notgo_status_max=maxStatus==='-'?'':maxStatus;
                            }
                        }
                        else if(format==='P'){const master=(qaStandardPoints(currentAlatData)||[])[$tr.data('index')]||{},std=qaNum(master.std_label||master.standar),mode=String(master.toleransi_mode||((master.tol_plus!==undefined||master.tol_minus!==undefined)?'range':'pm'));let min=NaN,max=NaN;if(mode==='range'){const a=qaNum(master.tol_plus),b=qaNum(master.tol_minus);if(Number.isFinite(std)&&Number.isFinite(a)&&Number.isFinite(b)){min=std+Math.min(a,b);max=std+Math.max(a,b);}}else{const t=qaNum(master.toleransi);if(Number.isFinite(std)&&Number.isFinite(t)){min=std-Math.abs(t);max=std+Math.abs(t);}}const clean=v=>Number.isFinite(v)?Number(v.toFixed(6)).toString():'';o.group_id=master.group_id||'';o.type_ukuran=master.type_ukuran||master.type||'';o.type=o.type_ukuran;o.ukuran='';o.upart='';o.alat_nama=master.alat_nama||'';o.alat_no=master.alat_no||'';o.std_label=master.std_label||master.standar||'';o.toleransi_mode=mode;o.toleransi=master.toleransi||'';o.tol_plus=master.tol_plus||'';o.tol_minus=master.tol_minus||'';o.min=clean(min);o.max=clean(max);o.aktual1=o.actual1||'';o.aktual2=o.actual2||'';o.no_gambar=$('#qa_no_gambar').val()||'';}
                        else if(format==='Q'){const master=(qaStandardPoints(currentAlatData)||[])[$tr.data('index')]||{};o.subtype='SERATION';o.bagian=master.bagian||((($tr.data('index')||0)%2===0)?'OUT':'PITCH');o.std_spesifikasi=master.std_spesifikasi||'';o.std_no_alat=master.std_no_alat||'';o.std=master.std||'';o.tol_minus=master.tol_minus!==undefined?master.tol_minus:(master.toleransi!==undefined?('-'+Math.abs(qaNum(master.toleransi)||0)):'');o.tol_plus=master.tol_plus!==undefined?master.tol_plus:(master.toleransi!==undefined?('+'+Math.abs(qaNum(master.toleransi)||0)):'');const sv=qaNum(o.std),lo=qaNum(o.tol_minus),hi=qaNum(o.tol_plus),e1=(Number.isFinite(sv)&&Number.isFinite(lo))?sv+lo:NaN,e2=(Number.isFinite(sv)&&Number.isFinite(hi))?sv+hi:NaN;o.min=(Number.isFinite(e1)&&Number.isFinite(e2))?Math.min(e1,e2).toFixed(3):'';o.max=(Number.isFinite(e1)&&Number.isFinite(e2))?Math.max(e1,e2).toFixed(3):'';}
                        else if(format==='R'){const master=(qaStandardPoints(currentAlatData)||[])[$tr.data('index')]||{};o.bagian=o.bagian||master.bagian||'Ø Out';o.toleransi=master.toleransi||'';}
                        else if(format==='U'){const master=(qaStandardPoints(currentAlatData)||[])[$tr.data('index')]||{};o.standar=master.standar||$tr.find('[data-key="standar"]').val()||'';o.toleransi=master.toleransi||$tr.find('[data-key="toleransi"]').val()||'';o.hasil=$tr.find('[data-key="hasil"]').val()||'';o.koreksi=$tr.find('.qa-u-koreksi').text()||'';}
                        else if(format==='W'){const master=(qaStandardPoints(currentAlatData)||[])[$tr.data('index')]||{};o.standar=master.standar||$tr.find('[data-key="standar"]').val()||'';o.hasil=$tr.find('[data-key="hasil"]').val()||'';o.koreksi=$tr.find('.qa-w-koreksi').text()||'';}
                        else if(format==='V'){const master=(qaStandardPoints(currentAlatData)||[])[$tr.data('index')]||{};o.bagian=master.bagian||'';o.standar=master.standar||$tr.find('[data-key="standar"]').val()||'';o.hasil=$tr.find('[data-key="hasil"]').val()||'';o.koreksi=$tr.find('.qa-v-koreksi').text()||'';}
                        else if(format==='T'){const master=(qaStandardPoints(currentAlatData)||[])[$tr.data('index')]||{};o.posisi=master.posisi||'';o.standar=master.standar||'';o.spesifikasi_standar=master.spesifikasi_standar||'';o.resolusi_standar=master.resolusi_standar||'';o.no_alat_standar=master.no_alat_standar||'';o.tol_minus=(master.tol_minus!==undefined&&String(master.tol_minus)!=='')?master.tol_minus:(master.toleransi!==undefined?String(-Math.abs(qaNum(master.toleransi)||0)):'');o.tol_plus=(master.tol_plus!==undefined&&String(master.tol_plus)!=='')?master.tol_plus:(master.toleransi!==undefined?String(Math.abs(qaNum(master.toleransi)||0)):'');const st=qaNum(o.standar),lo=qaNum(o.tol_minus),hi=qaNum(o.tol_plus);if(Number.isFinite(st)&&Number.isFinite(lo)&&Number.isFinite(hi)){o.min=Math.min(st+lo,st+hi);o.max=Math.max(st+lo,st+hi);}else{o.min='';o.max='';}o.kode_warna=o.kode_warna||'';o.no_gambar=$('#qa_no_gambar').val()||'';o.keputusan=$('#qa_status_final').val()||'';}
                        rows.push(o);
                    });
                    // Format N rollback: tidak ada data Periksa Kesejajaran.
                    if(format==='O'){
                        const master=(qaStandardPoints(currentAlatData)||[])[0]||{};
                        const go=rows.find(r=>String(r.posisi_ukur||'').toUpperCase()==='GO')||{};
                        const ng=rows.find(r=>String(r.posisi_ukur||'').toUpperCase()==='NOT GO')||{};
                        const goRange=qaORange(master,true), ngRange=qaORange(master,false);
                        return [Object.assign({},master,{
                            no_alat:alatForNoPart(),
                            spesifikasi:master.spesifikasi||'',
                            kode_warna:$('#qa_kode_warna').val(),
                            keputusan:$('#qa_status_final').val()==='OK'?'OK':'NOT GOOD',
                            status:$('#qa_status_final').val()||'',
                            go_min:Number.isFinite(goRange.min)?qaFmt(goRange.min,3):'',
                            go_max:Number.isFinite(goRange.max)?qaFmt(goRange.max,3):'',
                            go_hasil_min:go.go_hasil_min||'',
                            go_hasil_max:go.go_hasil_max||'',
                            go_status_min:go.go_status_min||'',
                            go_status_max:go.go_status_max||'',
                            go_status:go.status||'',
                            notgo_min:Number.isFinite(ngRange.min)?qaFmt(ngRange.min,3):'',
                            notgo_max:Number.isFinite(ngRange.max)?qaFmt(ngRange.max,3):'',
                            notgo_hasil_min:ng.notgo_hasil_min||'',
                            notgo_hasil_max:ng.notgo_hasil_max||'',
                            notgo_status_min:ng.notgo_status_min||'',
                            notgo_status_max:ng.notgo_status_max||'',
                            notgo_status:ng.status||'',
                            no_gambar:$('#qa_no_gambar').val()||''
                        })];
                    }
                    if(format==='A'){const go=rows.find(r=>String(r.posisi).toUpperCase()==='GO')||{},ng=rows.find(r=>String(r.posisi).toUpperCase()==='NOT GO')||{};return [{kode_varian:'',kode_warna:$('#qa_kode_warna').val(),warna:$('#qa_kode_warna').val(),kep:$('#qa_status_final').val(),keputusan:$('#qa_status_final').val(),keterangan:$('#qa_keterangan').val(),alat_standar:{go:{no_alat:go.std_no_alat||'',spesifikasi:go.std_spesifikasi||''},not_go:{no_alat:ng.std_no_alat||'',spesifikasi:ng.std_spesifikasi||''}},go:go,not_go:ng,periksa_berikut:null}];}
                    if(format==='Q'){const out=rows.find(r=>String(r.bagian||'').toUpperCase()==='OUT')||{},pitch=rows.find(r=>String(r.bagian||'').toUpperCase()==='PITCH')||{};return [{subtype:'SERATION',kode_warna:$('#qa_kode_warna').val(),keputusan:$('#qa_status_final').val(),out:{std_spesifikasi:out.std_spesifikasi||'',std_no_alat:out.std_no_alat||'',std:out.std||'',tol_plus:out.tol_plus||'',tol_minus:out.tol_minus||'',min:out.min||'',max:out.max||'',actual:[out.actual1||'',out.actual2||'',out.actual3||'']},pitch:{std_spesifikasi:pitch.std_spesifikasi||'',std_no_alat:pitch.std_no_alat||'',std:pitch.std||'',tol_plus:pitch.tol_plus||'',tol_minus:pitch.tol_minus||'',min:pitch.min||'',max:pitch.max||'',actual:[pitch.actual1||'',pitch.actual2||'',pitch.actual3||'']}}];}
                    return rows;
                };

                qaValidateUpdate=function(format,rows){
                    format=String(format||'').toUpperCase();
                    if(format==='F') return rows.length===1;
                    if(format==='O'){
                        const row=Array.isArray(rows)&&rows.length?rows[0]:null;
                        if(!row) return false;
                        return ['go_hasil_min','go_hasil_max','notgo_hasil_min','notgo_hasil_max'].every(function(key){
                            return String(row[key]||'').trim()!=='';
                        });
                    }
                    return Array.isArray(rows)&&rows.length>0;
                };

                // VERIFIKASI REGISTRASI V1 - REVIEW HEADER & FOOTER SEBELUM DATA DISIMPAN
                function qaRegFieldVisible(selector){
                    const $el=$(selector);
                    if(!$el.length) return false;
                    const $group=$el.closest('.form-group');
                    return $group.length ? $group.is(':visible') : $el.is(':visible');
                }

                function qaRegReviewValue(selector){
                    const $el=$(selector);
                    if(!$el.length) return '';
                    if($el.is('select')) return String($el.find('option:selected').text()||'').trim();
                    return String($el.val()||'').trim();
                }

                function qaBuildRegistrationReview(format){
                    const headerRows=[];
                    const footerRows=[];
                    const addRow=function(target,label,selector,always,suffix){
                        if(!always && !qaRegFieldVisible(selector)) return;
                        let value=qaRegReviewValue(selector);
                        if(value && suffix) value+=' '+suffix;
                        target.push('<tr><th style="width:38%;background:#f7f7f7;vertical-align:middle;">'+qaEsc(label)+'</th><td style="vertical-align:middle;word-break:break-word;">'+qaEsc(value||'-')+'</td></tr>');
                    };

                    // Header laporan / identitas alat.
                    addRow(headerRows,'Format Laporan','#reg_pilih_format',true);
                    addRow(headerRows,'No. Alat','#reg_nomor_alat',true);
                    addRow(headerRows,'No. Laporan','#reg_no_laporan',true);
                    addRow(headerRows,'Nama Alat','#reg_nama_alat',true);
                    addRow(headerRows,'Spesifikasi','#reg_spesifikasi',false);
                    addRow(headerRows,'Resolusi','#reg_resolusi',false);
                    addRow(headerRows,'Fungsi','#reg_fungsi',true);
                    addRow(headerRows,'No. Asset / FA','#reg_no_fa',true);
                    addRow(headerRows,'Lokasi','#reg_lokasi',true);
                    addRow(headerRows,'Merk','#reg_merk',true);
                    addRow(headerRows,'Type','#reg_type',false);
                    addRow(headerRows,'Buatan','#reg_buatan',true);
                    addRow(headerRows,'Seksi Pemilik','#reg_seksi',true);
                    addRow(headerRows,'Periode Kalibrasi','#reg_periode',true,'Hari');
                    addRow(headerRows,'Tanggal Manual','#reg_tgl_manual',true);

                    // Footer laporan. Hanya field yang memang tampil pada format terpilih.
                    addRow(footerRows,'Metode Kalibrasi','#reg_metode',false);
                    addRow(footerRows,'Standar Toleransi','#reg_toleransi',false);
                    addRow(footerRows,'Standar Uji','#reg_standar_uji',false);
                    addRow(footerRows,'Satuan (Unit)','#reg_satuan',false);
                    addRow(footerRows,'Traceability','#reg_trace',false);

                    if(format==='F'){
                        addRow(footerRows,'Standar Repeatability','#reg_f_std_sec1',true);
                        addRow(footerRows,'Standar Center Loading','#reg_f_std_sec3',true);
                        addRow(footerRows,'Batas Bawah','#reg_f_batas_bawah',true);
                        addRow(footerRows,'Batas Atas','#reg_f_batas_atas',true);
                    }

                    const fotoInput=$('#reg_foto_alat')[0];
                    const fotoName=(fotoInput&&fotoInput.files&&fotoInput.files[0])?fotoInput.files[0].name:'Tidak ada foto';
                    footerRows.push('<tr><th style="width:38%;background:#f7f7f7;vertical-align:middle;">Foto Alat</th><td style="vertical-align:middle;word-break:break-word;">'+qaEsc(fotoName)+'</td></tr>');

                    return ''+
                        '<div style="max-height:65vh;overflow:auto;padding-right:4px;">'+
                            '<div class="alert alert-info" style="margin-bottom:12px;">'+
                                '<i class="fa fa-check-circle"></i> <b>Periksa kembali data sebelum disimpan.</b><br>'+
                                '<span style="font-size:12px;">Jika ada typo atau data yang salah, pilih <b>Kembali Edit</b>.</span>'+
                            '</div>'+
                            '<h5 style="font-weight:bold;color:#1E3F66;margin:12px 0 7px;"><i class="fa fa-header"></i> HEADER LAPORAN</h5>'+
                            '<table class="table table-bordered table-condensed" style="margin-bottom:16px;">'+headerRows.join('')+'</table>'+
                            '<h5 style="font-weight:bold;color:#1E3F66;margin:12px 0 7px;"><i class="fa fa-align-bottom"></i> FOOTER LAPORAN</h5>'+
                            '<table class="table table-bordered table-condensed" style="margin-bottom:0;">'+footerRows.join('')+'</table>'+
                        '</div>';
                }

                // Simpan registrasi dengan validasi required yang menyesuaikan format.
                $('#btn-simpan-alat').off('click').on('click',function(){
                    const format=String($('#reg_pilih_format').val()||'').toUpperCase(), regNo=$('#reg_nomor_alat').val().trim().toUpperCase(), noLap=$('#reg_no_laporan').val().trim();
                    const base={no:regNo,lap:noLap,nama:$('#reg_nama_alat').val().trim(),seksi:$('#reg_seksi').val(),periode:$('#reg_periode').val().trim(),lokasi:$('#reg_lokasi').val().trim(),merk:$('#reg_merk').val().trim(),buatan:$('#reg_buatan').val().trim(),fungsi:$('#reg_fungsi').val().trim(),type:$('#reg_type').val().trim()};
                    const required={A:['spesifikasi','tol','metode','standar','trace'],B:['spesifikasi','tol','satuan','metode','standar','trace'],C:['spesifikasi','resolusi','tol','satuan','metode','standar','trace'],D:['spesifikasi','resolusi','tol','metode','standar','trace'],E:['spesifikasi','resolusi','tol','satuan','metode','standar','trace'],F:['spesifikasi','resolusi','tol','satuan','metode','standar','trace'],G:['spesifikasi','resolusi','tol','satuan','metode','standar','trace'],H:['spesifikasi','trace'],I:['metode','standar','trace'],J:['spesifikasi','metode'],K:['spesifikasi','resolusi','tol','metode','standar','trace'],L:['spesifikasi','resolusi','tol','satuan','metode','standar','trace'],M:['spesifikasi','resolusi','tol','satuan','metode','standar','trace'],N:['spesifikasi','resolusi','tol','satuan','metode','standar','trace'],O:['satuan','metode','standar','trace'],P:['metode','standar','trace'],Q:['spesifikasi','metode','standar','trace'],R:['resolusi','metode','standar','trace'],T:['spesifikasi','metode','standar','trace']};
                    Object.assign(base,{spesifikasi:$('#reg_spesifikasi').val().trim(),resolusi:$('#reg_resolusi').val().trim(),tol:$('#reg_toleransi').val().trim(),satuan:$('#reg_satuan').val().trim(),metode:$('#reg_metode').val().trim(),standar:$('#reg_standar_uji').val().trim(),trace:$('#reg_trace').val().trim()});
                    if(format === 'F') {
                        const bw = $('#reg_f_batas_bawah').val().trim(), at = $('#reg_f_batas_atas').val().trim();
                        base.tol = bw + '|' + at;
                    }
                    if(!format||!base.no||!base.lap||!base.nama||!base.seksi||!base.periode||!base.lokasi){BootstrapDialog.alert('No. Alat, No. Laporan, Nama Alat, Seksi, Periode, dan Lokasi wajib diisi.');return;}
                    const missing=(required[format]||[]).filter(k=>!base[k]); if(missing.length){BootstrapDialog.alert('Field wajib belum lengkap: '+missing.join(', '));return;}
                    if(format==='F'&&(!$('#reg_f_std_sec1').val().trim()||!$('#reg_f_std_sec3').val().trim())){BootstrapDialog.alert('Standar Repeatability dan Center Loading wajib diisi untuk Format F.');return;}
                    const arr=qaCollectReg(format); if(!arr.length){BootstrapDialog.alert('Minimal ada satu konfigurasi standar untuk Format '+format+'.');return;}
                    if(format==='O'){
                        const o=arr[0]||{};
                        const requiredO=['spesifikasi','tol_plus','tol_minus','go_std','go_tol_near','go_tol_far','notgo_std','notgo_tol'];
                        const missingO=requiredO.filter(function(key){return String(o[key]||'').trim()==='';});
                        if(missingO.length){
                            BootstrapDialog.alert('Format O belum lengkap. Isi Spesifikasi, Tol +, Tol -, ALAT dan TOLERANSI untuk GO / NOT GO.');
                            return;
                        }
                    }
                    const btn=$(this);
                    BootstrapDialog.show({
                        title:'Verifikasi Data Registrasi - Format '+qaEsc(format),
                        type:BootstrapDialog.TYPE_INFO,
                        message:qaBuildRegistrationReview(format),
                        closable:true,
                        buttons:[
                            {
                                label:'<i class="fa fa-pencil"></i> Kembali Edit',
                                cssClass:'btn-default',
                                action:function(dialog){ dialog.close(); }
                            },
                            {
                                label:'<i class="fa fa-check"></i> Data Benar, Simpan',
                                cssClass:'btn-success',
                                action:function(dialog){
                                    dialog.close();
                                    btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
                                    const fd=new FormData();
                                    fd.append('no_part',base.no);fd.append('no_laporan',base.lap);fd.append('nama_part',base.nama);fd.append('seksi',base.seksi);fd.append('periode',base.periode);fd.append('ukuran','');fd.append('resolusi',base.resolusi);fd.append('lokasi',base.lokasi);fd.append('tgl_manual',$('#reg_tgl_manual').val());fd.append('spesifikasi',base.spesifikasi);fd.append('toleransi_global',base.tol);fd.append('satuan',base.satuan);fd.append('metode_uji',base.metode);fd.append('standar_uji',base.standar);fd.append('traceability',base.trace);fd.append('format_laporan',format);fd.append('titik_standar_json',JSON.stringify(arr));fd.append('type',base.type);fd.append('no_fa',$('#reg_no_fa').val()||'');fd.append('merk',base.merk);fd.append('fungsi',base.fungsi);fd.append('buatan',base.buatan);
                                    if($('#reg_foto_alat')[0]&&$('#reg_foto_alat')[0].files[0])fd.append('foto_alat',$('#reg_foto_alat')[0].files[0]);
                                    $.ajax({url:'proses_qa.php?action=simpan_alat',type:'POST',dataType:'json',data:fd,processData:false,contentType:false,success:function(res){btn.prop('disabled',false).html('Simpan & Daftarkan Alat <i class="fa fa-save"></i>');if(res.status==='success'){sessionStorage.setItem('cetak_data',JSON.stringify(res.print_data||{no_laporan:base.lap,no_part:base.no,nama_part:base.nama,nama_alat:base.nama,format:format,format_laporan:format,hasil_array:arr,tanggal_periksa:$('#reg_tgl_manual').val()||'<?php echo date('Y-m-d'); ?>',jadwal_berikutnya:res.jadwal_selanjutnya||'',status_final:'OK',keputusan:'OK',petugas:'<?php echo addslashes($nama_petugas); ?>',operator:'<?php echo addslashes($nama_petugas); ?>'}));BootstrapDialog.confirm({title:'Berhasil',type:BootstrapDialog.TYPE_SUCCESS,message:'Alat berhasil didaftarkan.<br><br><b>Cetak laporan?</b>',btnCancelLabel:'Tidak',btnOKLabel:'Ya, Cetak',btnOKClass:'btn-primary',callback:function(ok){if(ok)window.location.href='cetak_laporan.php';else{$('#form-registrasi').slideUp();$('#nomor_alat').prop('disabled',false).val(base.no);$('#btn-cek').click();}}});}else BootstrapDialog.alert('Error DB: '+(res.message||'Gagal menyimpan.'));},error:function(xhr){btn.prop('disabled',false).html('Simpan & Daftarkan Alat <i class="fa fa-save"></i>');BootstrapDialog.alert('Koneksi Error! '+(xhr.responseText||''));}});
                                }
                            }
                        ]
                    });
                });

                // No laporan otomatis dari registrasi tetapi tetap editable saat update.
                $(document).off('change.qaFormatMeta','#reg_pilih_format').on('change.qaFormatMeta','#reg_pilih_format',function(){qaRegVisibility($(this).val());});

                // Pastikan data print memakai No. Laporan registrasi dan tanggal berikutnya kandidat.
                const _oldBuildPrintData=qaBuildPrintData;
                qaBuildPrintData=function(format,hasilArray,res,statusQA,snapshotOverride){ const meta=_oldBuildPrintData(format,hasilArray,res,statusQA,snapshotOverride); meta.no_laporan=String($('#qa_no_laporan').val()||'').trim()||String((currentAlatHistory&&currentAlatHistory[0]&&currentAlatHistory[0].no_laporan)||''); meta.jadwal_berikutnya=statusQA==='OK'?(res&&res.jadwal_selanjutnya?res.jadwal_selanjutnya:(currentAlatData&&currentAlatData.jadwal_kalibrasi?currentAlatData.jadwal_kalibrasi:'')):''; meta.periode_berikutnya=meta.jadwal_berikutnya; return meta; };


                // CEK ALAT QR V7 - GENERATOR LABEL 8CM, IDENTITAS ALAT, PRINT, SAVE, SCANNER
                let qaQrScanner = null;
                $(document).off('click.qaQrGen','#btn-qr-generator').on('click.qaQrGen','#btn-qr-generator',function(){
                    const no=String($(this).data('no-alat')||'').trim().toUpperCase(); if(!no) return;
                    const nama=String($(this).data('nama-alat')||(currentAlatData&&(currentAlatData.nama_part||currentAlatData.nama_alat))||'-').trim();
                    const seksi=String($(this).data('seksi-pemilik')||(currentAlatData&&(currentAlatData.seksi_pemilik||currentAlatData.seksi))||'-').trim();
                    // CEK ALAT QR V8 - JIKA SUDAH TERSIMPAN, JANGAN GENERATE ULANG; TAMPILKAN FILE PERMANEN
                    $('#qr-generator-box').empty();
                    $('#qr-generator-no').text(no); $('#qr-generator-nama').text(nama||'-'); $('#qr-generator-seksi').text(seksi||'-');
                    $.post('proses_qa.php?action=cek_qr_alat',{no_part:no},function(res){
                        if(!res||res.status!=='success'){BootstrapDialog.alert((res&&res.message)||'Gagal mengecek QR alat.');return;}
                        if(res.exists){
                            $('#qr-generator-box').html('<img src="'+qaEsc(res.file)+'?v=locked" alt="QR '+qaEsc(no)+'" style="width:190px;height:190px;display:block;">');
                            $('#btn-save-qr').prop('disabled',true).html('<i class="fa fa-lock"></i> QR Terkunci Permanen');
                        }else{
                            new QRCode(document.getElementById('qr-generator-box'),{text:no,width:190,height:190,correctLevel:QRCode.CorrectLevel.H});
                            $('#btn-save-qr').prop('disabled',false).html('<i class="fa fa-save"></i> Simpan QR Permanen');
                        }
                        $('#modalQrGenerator').modal('show');
                    },'json').fail(function(xhr){BootstrapDialog.alert(xhr.responseText||'Gagal mengecek QR alat.');});
                });
                $(document).off('click.qaQrPrint','#btn-print-qr').on('click.qaQrPrint','#btn-print-qr',function(){
                    const no=$('#qr-generator-no').text().trim(), nama=$('#qr-generator-nama').text().trim(), seksi=$('#qr-generator-seksi').text().trim();
                    const canvas=$('#qr-generator-box canvas')[0], img=$('#qr-generator-box img')[0];
                    let qrSrc=''; if(canvas) qrSrc=canvas.toDataURL('image/png'); else if(img) qrSrc=img.src||'';
                    if(!no||!qrSrc){BootstrapDialog.alert('QR belum siap untuk dicetak.');return;}
                    const esc=function(v){return String(v||'').replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];});};
                    const w=window.open('','_blank','width=520,height=700'); if(!w){BootstrapDialog.alert('Popup print diblokir browser. Izinkan popup lalu coba lagi.');return;}
                    w.document.write('<!doctype html><html><head><meta charset="utf-8"><title>QR '+esc(no)+'</title><style>@page{margin:0.5cm}html,body{margin:0;padding:0;font-family:Arial,sans-serif}.label{width:8cm;height:auto;box-sizing:border-box;border:1px solid #000;padding:.35cm;text-align:center}.qr{width:4.5cm;height:4.5cm;display:block;margin:0 auto}.info{border-top:1px solid #000;margin-top:6px;padding-top:7px;text-align:left;font-size:12px;line-height:1.45}.row{margin:2px 0}@media print{.label{break-inside:avoid}}</style></head><body><div class="label"><img class="qr" src="'+qrSrc+'"><div class="info"><div class="row"><b>NO. ALAT</b> : '+esc(no)+'</div><div class="row"><b>NAMA ALAT</b> : '+esc(nama)+'</div><div class="row"><b>SEKSI PEMILIK</b> : '+esc(seksi)+'</div></div></div><script>window.onload=function(){window.print();}<\/script></body></html>');
                    w.document.close();
                });
                $(document).off('click.qaQrSave','#btn-save-qr').on('click.qaQrSave','#btn-save-qr',function(){
                    const no=$('#qr-generator-no').text().trim(), canvas=$('#qr-generator-box canvas')[0], img=$('#qr-generator-box img')[0];
                    let data=''; if(canvas) data=canvas.toDataURL('image/png'); else if(img&&img.src.indexOf('data:image/png')===0) data=img.src;
                    if(!no||!data){BootstrapDialog.alert('QR belum siap.');return;}
                    const btn=$(this).prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
                    $.post('proses_qa.php?action=simpan_qr_alat',{no_part:no,qr_data:data},function(res){
                        btn.prop('disabled',false).html('<i class="fa fa-save"></i> Simpan QR');
                        if(res&&res.status==='success'){
                            btn.prop('disabled',true).html('<i class="fa fa-lock"></i> QR Terkunci Permanen');
                            BootstrapDialog.alert({title:'QR Tersimpan Permanen',type:BootstrapDialog.TYPE_SUCCESS,message:'QR Code <b>'+qaEsc(no)+'</b> sudah disimpan permanen dan tidak dapat dibuat ulang atau diganti.'});
                        } else BootstrapDialog.alert((res&&res.message)||'QR gagal disimpan.');
                    },'json').fail(function(xhr){btn.prop('disabled',false).html('<i class="fa fa-save"></i> Simpan QR');BootstrapDialog.alert(xhr.responseText||'QR gagal disimpan.');});
                });
                function qaStopQrScanner(){ if(qaQrScanner){ try{qaQrScanner.stop().then(function(){qaQrScanner.clear();}).catch(function(){});}catch(e){} qaQrScanner=null; } }
                $('#modalQrScanner').on('hidden.bs.modal',qaStopQrScanner);
                $(document).off('click.qaQrScan','#btn-scan-qr').on('click.qaQrScan','#btn-scan-qr',function(){
                    $('#modalQrScanner').modal('show');
                    setTimeout(function(){
                        if(typeof Html5Qrcode==='undefined'){BootstrapDialog.alert('Library scanner QR lokal gagal dimuat. Pastikan file ../../js/html5-qrcode.min.js tersedia.');return;}
                        qaStopQrScanner(); qaQrScanner=new Html5Qrcode('qr-reader');
                        qaQrScanner.start({facingMode:'environment'},{fps:10,qrbox:{width:230,height:230}},function(decoded){
                            let val=String(decoded||'').trim(); if(val.indexOf('QA_ALAT:')===0) val=val.substring(8); else {try{const u=new URL(val);val=u.searchParams.get('no_alat')||u.searchParams.get('no_part')||val;}catch(e){}}
                            val=String(val).trim().toUpperCase(); if(!val)return;
                            qaStopQrScanner(); $('#modalQrScanner').modal('hide'); $('#nomor_alat').prop('disabled',false).val(val); setTimeout(function(){$('#btn-cek').click();},250);
                        },function(){}).catch(function(){BootstrapDialog.alert('Kamera tidak dapat dibuka. Izinkan akses kamera dan gunakan HTTPS/localhost.');});
                    },400);
                });

                // Status lama di UI juga dibaca sebagai DITARIK TECHNICAL.
                $('#qa_status_final option[value="DITARIK TECHNICAL"]').length || $('#qa_status_final option[value="DITARIK MECHANICAL"]').attr('value','DITARIK TECHNICAL').text('DITARIK TECHNICAL');
                $('#qa_status_final option[value="DITARIK MECHANICAL"]').remove();

            })();

        });
    </script>
</body>
</html>

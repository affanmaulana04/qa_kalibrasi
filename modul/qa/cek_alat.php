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

$q_kls = mysqli_query($konek, "SELECT * FROM qa_kls ORDER BY nama_kelas ASC");
$q_seksi = mysqli_query($konek, "SELECT * FROM qa_seksi_master ORDER BY nama_seksi ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengecekan Alat Kalibrasi | QA System</title>
    <link rel="shortcut icon" href="../../images/favicon1.ico">
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
                            <p class="text-muted" style="margin-bottom: 30px;">Ketik Nomor Alat untuk mengecek status atau mendaftar baru.</p>
                            <form id="formCekAlat" onsubmit="return false;">
                                <div class="form-group" id="group-input">
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-addon"><i class="fa fa-barcode"></i></span>
                                        <input type="text" class="form-control text-center text-uppercase-input" id="nomor_alat" placeholder="Ketik No. Alat" autocomplete="off">
                                    </div>
                                    <span id="error-msg" class="text-danger" style="display:none; text-align:left; margin-top:8px;"><i class="fa fa-exclamation-circle"></i> Nomor alat wajib diisi!</span>
                                </div>
                                <div class="form-group" style="margin-top: 20px; margin-bottom: 0;"><button type="button" id="btn-cek" class="btn btn-validasi btn-block">Cek Alat</button></div>
                            </form>
                        </div>
                        <div class="col-md-6 flex-col-center" style="padding-left: 30px; text-align: center;">
                            <h4 style="font-weight: bold; color:#555; margin-top:0; margin-bottom: 15px;">Visual Alat</h4>
                            <div class="foto-alat-container"><img src="../../images/no-image.png" onerror="this.src='https://via.placeholder.com/300x200?text=Tidak+Ada+Gambar'" id="preview_gambar_alat"></div>
                        </div>
                    </div>

                    <!-- 1. FORM UPDATE KALIBRASI -->
                    <div id="form-update-kalibrasi" class="hidden-form-section">
                        <h4 class="section-title text-success"><i class="fa fa-list-alt"></i> Detail & Riwayat Kalibrasi</h4>
                        <div class="table-responsive" style="margin-bottom: 30px;">
                            <table class="table table-bordered table-striped table-hover table-custom" id="tabel-gabungan">
                                <thead><tr><th width="3%">NO</th><th>NO ALAT</th><th>NAMA ALAT</th><th>SPESIFIKASI</th><th>SEKSI PEMILIK</th><th>LOKASI</th><th style="background-color:#e2e6ea;">STATUS</th><th style="background-color:#e2e6ea;">TGL QA</th><th style="background-color:#e2e6ea;">OPERATOR</th><th style="background-color:#e2e6ea; text-align:center;">AKSI</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <div style="background: #eaf2f8; padding: 8px 15px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #c2dced;">
                            <strong style="margin-right: 15px;"><i class="fa fa-keyboard-o"></i> Simbol:</strong>
                            <button type="button" class="btn btn-xs btn-sym" data-sym="°">° (Derajat)</button>
                            <button type="button" class="btn btn-xs btn-sym" data-sym="±">± (Plus-Minus)</button>
                            <button type="button" class="btn btn-xs btn-sym" data-sym="'">' (Menit)</button>
                            <button type="button" class="btn btn-xs btn-sym" data-sym='\"'>" (Detik)</button>
                            <button type="button" class="btn btn-xs btn-sym" data-sym="µ">µ (Mikro)</button>
                            <button type="button" class="btn btn-xs btn-sym" data-sym="Ø">Ø (Diameter)</button>
                        </div>

                        <h4 class="section-title text-info"><i class="fa fa-edit"></i> Input Update Hasil Kalibrasi</h4>
                        <div class="form-horizontal" style="padding: 0 15px;">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">No. Alat <span style="float:right;">:</span></label>
                                <div class="col-sm-4"><input type="text" class="form-control text-uppercase-input" id="qa_nomor_alat" disabled style="background-color: #eee; font-weight:bold;"></div>
                                <label class="col-sm-2 control-label">Nama Alat <span style="float:right;">:</span></label>
                                <div class="col-sm-4"><input type="text" class="form-control" id="qa_nama_alat"></div>
                            </div>
                            <div class="form-group" id="qa-extra-header-row">
                                <label class="col-sm-2 control-label">Spesifikasi <span style="float:right;">:</span></label>
                                <div class="col-sm-4"><input type="text" id="qa_spesifikasi" class="form-control"></div>
                                <label class="col-sm-2 control-label">Resolusi <span style="float:right;">:</span></label>
                                <div class="col-sm-4"><input type="text" id="qa_resolusi" class="form-control"></div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Metode Uji <span style="float:right;">:</span></label>
                                <div class="col-sm-4"><input type="text" id="qa_metode" class="form-control"></div>
                                <label class="col-sm-2 control-label">Standar Uji <span style="float:right;">:</span></label>
                                <div class="col-sm-4"><input type="text" id="qa_standar_uji" class="form-control"></div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Traceability <span style="float:right;">:</span></label>
                                <div class="col-sm-4"><input type="text" id="qa_trace" class="form-control"></div>
                                <label class="col-sm-2 control-label">Standar Tol (±) <span style="float:right;">:</span></label>
                                <div class="col-sm-4">
                                    <div class="input-group">
                                        <input type="text" id="qa_toleransi" class="form-control" style="font-weight:bold;">
                                        <span class="input-group-addon qa_satuan_label"></span>
                                    </div>
                                    <input type="hidden" id="qa_toleransi_value"><input type="hidden" id="qa_resolusi_value"><input type="hidden" id="qa_format_alat">
                                </div>
                            </div>

                            <div class="form-group qa-update-toolbar" style="margin-top: 25px;">
                                <div class="col-sm-12">
                                    <div class="qa-toolbar-box">
                                        <div class="qa-toolbar-left">
                                            <button type="button" class="btn btn-sm btn-success" id="btn_add_update_row" style="display:none !important;"><i class="fa fa-plus"></i> Tambah Baris</button>
                                            <button type="button" class="btn btn-sm btn-info" id="btn_add_n_column" style="display:none;"><i class="fa fa-columns"></i> Tambah Kolom Bagian / Posisi</button>
                                        </div>
                                        <div class="qa-toolbar-help" id="qa_n_column_help" style="display:none;">Pada Format N, isi nama Bagian/Posisi pada header kolom yang ditambahkan.</div>
                                    </div>
                                    <table class="table table-bordered table-entry" id="tabel_input_kalibrasi">
                                        <thead></thead><tbody></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="row" style="margin-top: 20px;">
                                <div class="col-md-6 div-ruangan">
                                    <div class="form-group" style="margin-left:0; margin-right:0;">
                                        <label>Suhu Ruangan</label>
                                        <input type="text" id="qa_suhu" class="form-control auto-simbol" data-satuan="°C" placeholder="Cth: 21 atau Ambient">
                                    </div>
                                </div>
                                <div class="col-md-6 div-ruangan">
                                    <div class="form-group" style="margin-left:0; margin-right:0;">
                                        <label>Kelembapan</label>
                                        <input type="text" id="qa_kelembapan" class="form-control auto-simbol" data-satuan="%" placeholder="Cth: 55 atau Ambient">
                                    </div>
                                </div>
                                <div class="col-md-3 div-ketidakpastian">
                                    <div class="form-group" style="margin-left:0; margin-right:0;">
                                        <label>Ketidakpastian</label>
                                        <div class="input-group">
                                            <input type="text" id="qa_ketidakpastian" class="form-control" placeholder="0.05">
                                            <span class="input-group-addon qa_satuan_label"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 div-ketidakpastian">
                                    <div class="form-group" style="margin-left:0; margin-right:0;">
                                        <label>Confidence Lvl</label>
                                        <input type="text" id="qa_confidence" class="form-control auto-simbol" data-satuan="%" value="95 %">
                                    </div>
                                </div>
                            </div>

                            <div class="row qa-report-meta-row" id="qa-report-meta-row">
                                <div class="col-md-6"><div class="form-group" style="margin-left:0; margin-right:0;"><label>Kode Warna (opsional)</label><input type="text" id="qa_kode_warna" class="form-control"></div></div>
                                <div class="col-md-6"><div class="form-group" style="margin-left:0; margin-right:0;"><label>No. Gambar / Referensi (opsional)</label><input type="text" id="qa_no_gambar" class="form-control"></div></div>
                            </div>

                            <div class="row">
                                <div class="col-md-6"><div class="form-group" style="margin-left:0; margin-right:0;"><label>No. Laporan <span class="text-danger">*</span></label><input type="text" id="qa_no_laporan" class="form-control" placeholder="Masukkan No. Laporan"></div></div>
                                <div class="col-md-6"></div>
                            </div>

                            <div class="row"><div class="col-md-12"><div class="form-group" style="margin-left:0; margin-right:0;"><label>Keterangan Tambahan</label><textarea id="qa_keterangan" class="form-control" rows="2" placeholder="Catatan opsional..."></textarea></div></div></div>

                            <div class="row" style="margin-top: 15px;">
                                <div class="col-md-6"><div class="form-group" style="margin-left:0; margin-right:0;"><label>Operator / User Pengecekan</label><div class="input-group"><span class="input-group-addon"><i class="fa fa-user"></i></span><input type="text" class="form-control" id="qa_petugas" value="<?php echo htmlspecialchars($nama_petugas, ENT_QUOTES); ?>" disabled style="background-color: #eee;"></div></div></div>
                                <div class="col-md-6">
                                    <div class="form-group" style="margin-left:0; margin-right:0;">
                                        <label>Keputusan (Status Final) <span class="text-danger">*</span></label>
                                        <select id="qa_status_final" class="form-control" style="font-weight:bold; font-size:16px;">
                                            <option value="">-- Pilih Status --</option><option value="OK">OK</option><option value="NOT GOOD">NOT GOOD</option><option value="RUSAK">RUSAK</option><option value="DISCONTINUE">DISCONTINUE</option><option value="DITARIK MECHANICAL">DITARIK MECHANICAL</option>
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
                                    </select>
                                    <button type="button" id="btn-lanjut-format" class="btn btn-warning btn-block" style="margin-top:15px; font-weight:bold; font-size: 16px; height: 50px;">Lanjut Registrasi <i class="fa fa-arrow-right"></i></button>
                                </div>
                            </div>
                        </div>

                        <div id="section-form-registrasi" style="display:none;">
                            <div style="background: #eaf2f8; padding: 8px 15px; border-radius: 5px; margin: 0 30px 15px 30px; border: 1px solid #c2dced;">
                                <strong style="margin-right: 15px;"><i class="fa fa-keyboard-o"></i> Bantuan Simbol:</strong>
                                <button type="button" class="btn btn-xs btn-sym" data-sym="°">° (Derajat)</button>
                                <button type="button" class="btn btn-xs btn-sym" data-sym="±">± (Plus-Minus)</button>
                                <button type="button" class="btn btn-xs btn-sym" data-sym="'">' (Menit)</button>
                                <button type="button" class="btn btn-xs btn-sym" data-sym='\"'>" (Detik)</button>
                                <button type="button" class="btn btn-xs btn-sym" data-sym="µ">µ (Mikro)</button>
                                <button type="button" class="btn btn-xs btn-sym" data-sym="Ø">Ø (Diameter)</button>
                            </div>

                            <div style="padding: 0 30px;">
                                <h4 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px; color: #1E3F66; font-weight: bold;"><i class="fa fa-header"></i> Bagian Header Laporan</h4>
                                <div class="row">
                                    <div class="col-md-6" style="padding-right: 25px;">
                                        <!-- KOLOM NO ALAT DIGEMBOK MATI (disabled & readonly) -->
                                        <div class="form-group"><label>No. Alat <span class="text-danger">*</span></label><input type="text" class="form-control text-uppercase-input" id="reg_nomor_alat" readonly disabled style="background-color: #eee; cursor:not-allowed;" placeholder="Masukan Nomor Alat"></div>
                                        <div class="form-group"><label>No. Laporan <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_no_laporan" placeholder="Masukkan No. Laporan"></div>
                                        <div class="form-group"><label>Nama Alat <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_nama_alat" placeholder="Contoh: Dial Caliper gage"></div>
                                        <div class="form-group">
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
                                        <div class="row">
                                            <div class="col-md-6"><div class="form-group"><label>Periode Kalibrasi <span class="text-danger">*</span></label><div class="input-group"><input type="number" class="form-control" id="reg_periode" placeholder="180"><span class="input-group-addon">Hari</span></div></div></div>
                                            <div class="col-md-6"><div class="form-group"><label>Input Tanggal ( Opsional )</label><input type="date" class="form-control" id="reg_tgl_manual" title="Gunakan untuk backdate"></div></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6" style="padding-left: 25px;">
                                        <div class="form-group"><label>Spesifikasi <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_spesifikasi" placeholder="Cth: 5 ~ 15 mm"></div>
                                        <div class="form-group"><label>Resolusi <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_resolusi" placeholder="Cth: 0.01 mm"></div>
                                        <div class="form-group"><label>Lokasi <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_lokasi" placeholder="Contoh: Lab GA"></div>
                                        <div class="form-group" style="display:none;"><label>Type</label><input type="text" class="form-control text-uppercase-input" id="reg_type" placeholder="Cth: TBI"></div>
                                        <div class="form-group" style="display:none;"><label>Kelas (KLS) <span class="text-danger">*</span></label><select class="form-control" id="reg_kls"><option value="">-- Pilih Kelas --</option></select></div>
                                    </div>
                                </div>

                                <h4 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-top: 25px; margin-bottom: 15px; color: #1E3F66; font-weight: bold;"><i class="fa fa-align-bottom"></i> Bagian Footer Laporan</h4>
                                <div class="row">
                                    <div class="col-md-6" style="padding-right: 25px;">
                                        <div class="form-group"><label>Metode Kalibrasi <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_metode" placeholder="Cth: STIS-SOPF-731010"></div>
                                        <div class="form-group"><label>Standar Uji <span class="text-danger">*</span></label><textarea class="form-control" id="reg_standar_uji" rows="2" placeholder="Cth: Inside Micrometer"></textarea></div>
                                        <div class="form-group"><label>Traceability <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_trace" placeholder="Cth: Gauge Block KL 0"></div>
                                    </div>
                                    <div class="col-md-6" style="padding-left: 25px;">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="form-group"><label>Standar Toleransi (±) <span class="text-danger">*</span></label><textarea class="form-control" id="reg_toleransi" rows="2" placeholder="Cth: 0.06 atau < 1/4 Range"></textarea></div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group"><label>Satuan (Unit) <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_satuan" placeholder="Cth: mm / °C / Nm"></div>
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-top:5px;"><label><i class="fa fa-upload"></i> Upload Foto Alat ( Opsional )</label><input type="file" class="form-control" id="reg_foto_alat" accept="image/jpeg, image/png, image/jpg"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="group_format_f_extra" style="display:none; background:#e8f4f8; padding:15px 30px; margin: 20px 30px 15px 30px; border-radius:8px; border:1px dashed #1E3F66;">
                                <h5 style="margin-top:0; font-weight:bold;"><i class="fa fa-info-circle"></i> Parameter Tambahan Timbangan (Format F)</h5>
                                <div class="row">
                                    <div class="col-md-6"><label>Standar Repeatability (Section I) <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_f_std_sec1" placeholder="Cth: 100"></div>
                                    <div class="col-md-6"><label>Standar Center Loading (Section III) <span class="text-danger">*</span></label><input type="text" class="form-control" id="reg_f_std_sec3" placeholder="Cth: 100"></div>
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

    <script src="../../js/jquery.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script src="../../js/bootstrap-dialog.js"></script>
    <script>
        $(document).ready(function() {
            $('.text-uppercase-input').on('input', function() { $(this).val($(this).val().toUpperCase()); });
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
                            
                            if (alat.foto_alat) $('#preview_gambar_alat').attr('src', '../../images/alat/' + alat.foto_alat);
                            else $('#preview_gambar_alat').attr('src', 'https://via.placeholder.com/300x200?text=Tidak+Ada+Gambar');
                            
                            // FORM UPDATE DI UNLOCK TAPI ADA NILAI DEFAULT DARI REGISTRASI
                            $('#qa_nomor_alat').val(alat.no_part); 
                            $('#qa_nama_alat').val(alat.nama_part).prop('disabled', false); 
                            $('#qa_metode').val(alat.metode_uji || "-").prop('disabled', false); 
                            $('#qa_standar_uji').val(alat.standar_uji || "-").prop('disabled', false); 
                            $('#qa_trace').val(alat.traceability || "-").prop('disabled', false); 
                            $('#qa_toleransi').val(alat.toleransi_global).prop('disabled', false); 
                            
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
                            tGabungan.append('<tr><td>1</td><td><strong>'+qaEsc(alat.no_part || '-')+'</strong></td><td>'+qaEsc(alat.nama_part || '-')+'</td><td>'+qaEsc(alat.spesifikasi || '-')+'</td><td>'+qaEsc(alat.seksi_pemilik || '-')+'</td><td>'+qaEsc(alat.lokasi || '-')+'</td><td style="background-color:#eaf2f8;"><span class="label label-info"><i class="fa fa-clock-o"></i> Jadwal</span></td><td style="background-color:#eaf2f8;"><strong>'+formatTanggalJadwal(alat.jadwal_kalibrasi)+'</strong></td><td style="background-color:#eaf2f8; text-align:center;">-</td><td style="background-color:#eaf2f8; text-align:center;">-</td></tr>');

                            var num = 2;
                            if(history && history.length > 0) {
                                history.forEach(function(row, idx) {
                                    var st = String(row.display_status || row.status_display || row.status || '').toUpperCase(); var labelStatus = '';
                                    if(st === 'OK') { labelStatus = '<span class="label label-success">OK</span>'; } 
                                    else if(st === 'NOT GOOD' || st === 'BAD' || st === 'RUSAK' || st === 'DISCONTINUE' || st === 'DITARIK MECHANICAL') { labelStatus = '<span class="label label-danger">'+qaEsc(st === 'BAD' ? 'NOT GOOD' : st)+'</span>'; } 
                                    else { labelStatus = '<span class="label label-warning"><i class="fa fa-clock-o"></i> '+qaEsc(st || 'MENUNGGU VALIDASI')+'</span>'; }
                                    
                                    var btnAksi = '-';
                                    if(row.hasil_json && row.hasil_json.length > 2 && row.hasil_json !== '[]') {
                                        btnAksi = '<button class="btn btn-xs btn-primary btn-print-hist" data-idx="'+idx+'"><i class="fa fa-print"></i> Cetak</button>';
                                    }

                                    tGabungan.append('<tr><td>'+num+'</td><td><strong>'+(alat.no_part || "-")+'</strong></td><td>'+(alat.nama_part || "-")+'</td><td>'+(alat.spesifikasi || "-")+'</td><td>'+(alat.seksi_pemilik || "-")+'</td><td>'+(alat.lokasi || "-")+'</td><td style="background-color:#f4f7fa;">'+labelStatus+'</td><td style="background-color:#f4f7fa;"><strong>'+formatTanggal(row.tanggal)+'</strong></td><td style="background-color:#f4f7fa; text-align:center;">'+btnAksi+'</td></tr>');
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
                                
                                $('#preview_gambar_alat').attr('src', 'https://via.placeholder.com/300x200?text=Tidak+Ada+Gambar');

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
                var isNotGood = false;
                $('#tabel_input_kalibrasi .td-status').each(function() {
                    if($(this).text() === 'NOT GOOD') { isNotGood = true; }
                });
                if(isNotGood) { $('#qa_status_final').val('NOT GOOD'); } 
                else { $('#qa_status_final').val('OK'); }
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
                    var h1Str = tr.find('.inp-hasil-1').val(); var h2Str = tr.find('.inp-hasil-2').val();
                    var h1 = parseFloat(h1Str), h2 = parseFloat(h2Str);
                    var st1 = isNaN(h1) ? true : (Math.abs(parseFloat((h1 - valStd).toFixed(4))) <= toleransi);
                    var st2 = isNaN(h2) ? true : (Math.abs(parseFloat((h2 - valStd).toFixed(4))) <= toleransi);

                    if(!isNaN(h1) || !isNaN(h2)) {
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
                        var rH1Str = $(this).find('.inp-hasil-1').val(); var rH2Str = $(this).find('.inp-hasil-2').val();
                        if(!isNaN(parseFloat(rH1Str)) || !isNaN(parseFloat(rH2Str))) { resultJSON.push({ standar: rowStdStr, toleransi: rowTolStr, hasil: isNaN(parseFloat(rH1Str)) ? '' : rH1Str, hasil_2: isNaN(parseFloat(rH2Str)) ? '' : rH2Str, status: statusRow }); }
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
                        toleransi_global: $('#qa_toleransi').val(),
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
                                toleransi_global: $('#qa_toleransi').val(), 
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
                    if(std1 === '' || std3 === '') {
                        BootstrapDialog.alert('<b>Gagal!</b> Standar Repeatability dan Center Loading wajib diisi untuk Format F!'); return;
                    }
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
                const QA_FORMATS = 'ABCDEFGHIJKLMNOPQR'.split('');

                function qaEsc(v) {
                    return String(v == null ? '' : v)
                        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
                        .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
                }
                function qaNum(v) {
                    const n = parseFloat(String(v == null ? '' : v).replace(',', '.'));
                    return Number.isFinite(n) ? n : NaN;
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
                        case 'F': return n(['standar','toleransi'], ['Standar Scala Test','Toleransi'], [{},{},{}]);
                        case 'G': return n(['standar','toleransi'], ['Standar','Toleransi'], [{},{}]);
                        case 'H': return n(['posisi_ukur','std_spesifikasi','std_no_alat','std_out','tol_out','range_out','std_pitch','tol_pitch','range_pitch'], ['Posisi','STD Spesifikasi','STD No Alat','STD OUT','Tol OUT','Range OUT','STD PITCH','Tol PITCH','Range PITCH'], [{posisi_ukur:'GO'},{posisi_ukur:'NOT GO'},{posisi_ukur:'PLUG'}]);
                        case 'I': return n(['std_spesifikasi','std_no_alat'], ['STD Spesifikasi','STD No Alat'], [{}]);
                        case 'J': return n(['alat_verifikasi'], ['Alat Verifikasi'], [{}]);
                        case 'K': return n(['nilai_ditentukan','toleransi_persen'], ['Nilai Ditentukan','Toleransi (%)'], [{},{},{}]);
                        case 'L': return n(['arah','applied_value','toleransi'], ['Uji','Applied','Toleransi'], [{arah:'NAIK'},{arah:'NAIK'},{arah:'TURUN'},{arah:'TURUN'}]);
                        case 'M': return n(['jenis','std_mm','toleransi_axis','std_degree','toleransi_sudut'], ['Jenis','STD AXIS (mm)','Tol AXIS','STD ANGLE (°)','Tol ANGLE (\')'], [{jenis:'AXIS'},{jenis:'ANGLE'}]);
                        case 'N': return n(['standar','toleransi'], ['Standar','Toleransi'], [{},{},{}]);
                        case 'O': return n(['spesifikasi','tol_plus','tol_minus','go_std','go_tol_near','go_tol_far','go_min','go_max','notgo_std','notgo_tol','notgo_min','notgo_max'], ['Spesifikasi','Tol +','Tol -','GO Std','GO Tol Near','GO Tol Far','GO Min','GO Max','NOT GO Std','NOT GO Tol','NOT GO Min','NOT GO Max'], [{}]);
                        case 'P': return n(['type','ukuran','upart','alat_nama','alat_no','std_label','min','max','actual_count'], ['Type','Ukuran','Upart','Alat Standar','No Alat STD','STD Ukuran','Min','Max','Jumlah Aktual'], [{}]);
                        case 'Q': return n(['subtype','std_spesifikasi','std_no_alat','std','tol_plus','tol_minus','min','max'], ['Subtipe','STD Spesifikasi','STD No Alat','Standar','Tol +','Tol -','Min','Max'], [{subtype:'SERATION'}]);
                        case 'R': return n(['standar','toleransi'], ['Standar','Toleransi'], [{},{},{}]);
                    }
                    return n(['standar','toleransi'], ['Standar','Toleransi'], [{}]);
                }

                function qaRenderRegTable(format, presetRows) {
                    const sc = qaRegSchema(format);
                    let rows = (presetRows && presetRows.length) ? presetRows : sc.rows;
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
                            const isSelect = (format === 'A' && key === 'posisi') || (format === 'H' && key === 'posisi_ukur') || (format === 'L' && key === 'arah') || (format === 'M' && key === 'jenis') || (format === 'Q' && key === 'subtype');
                            if (isSelect) {
                                let opts = [];
                                if (format === 'A' && key === 'posisi') opts = ['GO','NOT GO'];
                                if (format === 'H' && key === 'posisi_ukur') opts = ['GO','NOT GO','PLUG'];
                                if (format === 'L' && key === 'arah') opts = ['NAIK','TURUN'];
                                if (format === 'M' && key === 'jenis') opts = ['AXIS','ANGLE'];
                                if (format === 'Q' && key === 'subtype') opts = ['SERATION','PLUG'];
                                b += '<td><select class="form-control qa-reg-field" data-key="'+key+'">' + opts.map(function(o){ return '<option '+(String(value).toUpperCase()===o?'selected':'')+'>'+o+'</option>'; }).join('') + '</select></td>';
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
                        $('#qa_metode').closest('.form-group').before('<div class="form-group" id="qa-extra-header-row"><label class="col-sm-2 control-label">Spesifikasi <span style="float:right;">:</span></label><div class="col-sm-4"><input type="text" id="qa_spesifikasi" class="form-control"></div><label class="col-sm-2 control-label">Resolusi <span style="float:right;">:</span></label><div class="col-sm-4"><input type="text" id="qa_resolusi" class="form-control"></div></div>');
                    }
                    if (!$('#qa_kode_warna').length) {
                        $('#qa_keterangan').closest('.row').before('<div class="row qa-report-meta-row" id="qa-report-meta-row"><div class="col-md-6"><div class="form-group" style="margin-left:0;margin-right:0;"><label>Kode Warna (opsional)</label><input type="text" id="qa_kode_warna" class="form-control"></div></div><div class="col-md-6"><div class="form-group" style="margin-left:0;margin-right:0;"><label>No. Gambar / Referensi (opsional)</label><input type="text" id="qa_no_gambar" class="form-control"></div></div></div>');
                    }
                }

                function qaFillCommon(alat, history) {
                    qaEnsureUpdateExtras();
                    const latest = history && history.length ? history[0] : null;
                    const snap = latest ? qaSnapshotFromHistory(latest) : {};
                    const get = function(name, fallback){ return snap[name] !== '' && snap[name] != null ? snap[name] : (alat[name] != null ? alat[name] : (fallback || '')); };
                    $('#qa_nomor_alat').val(alat.no_part || '');
                    $('#qa_nama_alat').val(get('nama_part', ''));
                    $('#qa_spesifikasi').val(get('spesifikasi', ''));
                    $('#qa_resolusi').val(get('resolusi', ''));
                    $('#qa_metode').val(get('metode_uji','-'));
                    $('#qa_standar_uji').val(get('standar_uji','-'));
                    $('#qa_trace').val(get('traceability','-'));
                    $('#qa_toleransi').val(get('toleransi_global', ''));
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
                    if (Array.isArray(pts)) return pts;
                    return [];
                }

                function qaApplyUpdateMetaVisibility(format){
                    format = String(format || 'A').toUpperCase();
                    // Ketidakpastian + Confidence hanya tersedia untuk format A, F, L, M.
                    const showUncertainty = ['A','F','L','M'].includes(format);
                    $('.div-ketidakpastian').toggle(showUncertainty);
                    if(!showUncertainty){
                        $('#qa_ketidakpastian').val('');
                        $('#qa_confidence').val('');
                    }

                    // Kode warna hanya tersedia pada A, H, I, O, Q.
                    const showColor = ['A','H','I','O','Q'].includes(format);
                    const $color = $('#qa_kode_warna');
                    if($color.length){
                        $color.closest('.col-md-6').toggle(showColor);
                        if(!showColor) $color.val('');
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
                        case 'F': return ['standar','toleransi'];
                        case 'G': return ['standar','toleransi'];
                        case 'H': return ['posisi_ukur','std_spesifikasi','std_no_alat','std_out','tol_out','range_out','std_pitch','tol_pitch','range_pitch'];
                        case 'I': return ['std_spesifikasi','std_no_alat'];
                        case 'J': return [];
                        case 'K': return ['nilai_ditentukan','toleransi_persen'];
                        case 'L': return ['toleransi'];
                        case 'M': return ['jenis','std_mm','toleransi_axis','std_degree','toleransi_sudut'];
                        case 'N': return ['standar','toleransi'];
                        case 'O': return ['spesifikasi','tol_plus','tol_minus','go_std','go_tol_near','go_tol_far','go_min','go_max','notgo_std','notgo_tol','notgo_min','notgo_max'];
                        case 'P': return ['std_label','min','max','actual_count','alat_nama','alat_no'];
                        case 'Q': return ['subtype','std_spesifikasi','std_no_alat','std','tol_plus','tol_minus','min','max'];
                        case 'R': return ['bagian','standar','toleransi'];
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
                    return '<select class="form-control qa-update-field" data-key="'+key+'">'+opts.map(function(o){return '<option '+(String(value||'').toUpperCase()===o?'selected':'')+'>'+o+'</option>';}).join('')+'</select>';
                }

                var qaNColumns = [{key:'hasil_0',label:'Hasil'}];

                function qaGetNColumns(history){
                    var found = null;
                    try {
                        (history || []).some(function(h){
                            var parsed = qaJ(h.hasil_json || '[]', []);
                            var first = Array.isArray(parsed) ? parsed[0] : null;
                            if (first && Array.isArray(first.posisi_kolom) && first.posisi_kolom.length) { found = first.posisi_kolom; return true; }
                            if (first && Array.isArray(first.posisi_hasil) && first.posisi_hasil.length) { found = first.posisi_hasil; return true; }
                            return false;
                        });
                    } catch(e) {}
                    if(found && found.length){ qaNColumns = found.map(function(x,j){ return {key:'hasil_'+j,label:String(x.label || x.posisi || ('Posisi '+(j+1)))}; }); }
                    else if(!qaNColumns || !qaNColumns.length){ qaNColumns=[{key:'hasil_0',label:'Hasil'}]; }
                }

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
                        points.forEach(function(p,i){ body+='<tr data-index="'+i+'"><td>'+(i+1)+'</td><td><b>'+qaEsc(p.posisi||'')+'</b></td><td>'+qaReadonly(p.std_spesifikasi)+'</td><td>'+qaReadonly(p.std_no_alat)+'</td><td>'+qaReadonly(p.std_out)+'<div class="qa-hidden-std" data-key="std_out">'+qaEsc(p.std_out)+'</div></td><td>'+qaReadonly(p.std_pitch)+'<div class="qa-hidden-std" data-key="std_pitch">'+qaEsc(p.std_pitch)+'</div></td><td>'+qaField('','out1')+'</td><td>'+qaField('','out2')+'</td><td>'+qaField('','out3')+'</td><td>'+qaField('','pitch1')+'</td><td>'+qaField('','pitch2')+'</td><td>'+qaField('','pitch3')+'</td><td class="qa-row-status">-</td></tr>'; });
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
                        head='<tr><th colspan="5">I. REPEATABILITY</th></tr><tr><th>Standar</th><th>Hasil</th><th>Deviasi</th><th colspan="2">II. SCALA TEST</th></tr><tr><td>'+qaReadonly(meta.std_sec1)+'</td><td>'+qaField('','sec1_hasil')+'</td><td class="qa-sec1-dev"></td><td colspan="2">Isi nilai pada baris di bawah</td></tr><tr><th>No</th><th>Standar Scala</th><th>Hasil</th><th>Koreksi</th><th>Status</th></tr>';
                        scale.forEach(function(p,i){body+='<tr data-index="'+i+'"><td>'+(i+1)+'</td><td>'+qaReadonly(p.standar)+'</td><td>'+qaField('','sec2_hasil')+'</td><td class="qa-sec2-kor"></td><td class="qa-row-status">-</td></tr>';});
                        head += '<tr><th colspan="5">III. CENTER LOADING</th></tr><tr><th>Tengah</th><th>Belakang</th><th>Kanan</th><th>Depan</th><th>Kiri</th></tr><tr class="qa-sec3-row"><td>'+qaField('','tengah')+'</td><td>'+qaField('','belakang')+'</td><td>'+qaField('','kanan')+'</td><td>'+qaField('','depan')+'</td><td>'+qaField('','kiri')+'</td></tr><tr><th colspan="4">Perbedaan dari Tengah</th><td class="qa-sec3-diff"></td></tr>';
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
                        qaNColumns = (qaNColumns && qaNColumns.length) ? qaNColumns : [{key:'hasil_0',label:'Hasil'}];
                        head='<tr><th style="width:5%">No</th><th>Standar</th><th>Toleransi</th>';
                        qaNColumns.forEach(function(col,j){
                            head+='<th><div style="display:flex;align-items:center;gap:4px;justify-content:center;"><input type="text" class="form-control qa-n-col-label" data-n-col="'+j+'" value="'+qaEsc(col.label)+'" placeholder="Bagian / Posisi">'+(j>0?'<button type="button" class="btn btn-xs btn-danger btn-remove-n-column" data-col="'+j+'" title="Hapus kolom"><i class="fa fa-times"></i></button>':'')+'</div></th>';
                        });
                        head+='<th>Koreksi</th><th>Status</th></tr>';
                        points.forEach(function(p,i){
                            var vals=Array.isArray(p.posisi_hasil)?p.posisi_hasil:[];
                            body+='<tr data-index="'+i+'"><td>'+(i+1)+'</td><td>'+qaReadonly(p.standar)+'<input type="hidden" data-n-key="standar" value="'+qaEsc(p.standar||'')+'"></td><td>'+qaReadonly(p.toleransi)+'<input type="hidden" data-n-key="toleransi" value="'+qaEsc(p.toleransi||'')+'"></td>';
                            qaNColumns.forEach(function(col,j){ var ex=vals[j]&&vals[j].hasil!=null?vals[j].hasil:''; body+='<td>'+qaField(ex,'hasil_pos_'+j,' data-n-result="'+j+'"')+'</td>'; });
                            body+='<td class="qa-koreksi"></td><td class="qa-row-status">-</td></tr>';
                        });
                    } else if (format === 'O') {
                        const p=points[0]||{};
                        head='<tr><th>Spesifikasi</th><th>Tol+</th><th>Tol-</th><th>GO Std</th><th>GO Tol Near</th><th>GO Tol Far</th><th>GO Min</th><th>GO Max</th><th>GO Hasil Min</th><th>GO Hasil Max</th><th>NOT GO Std</th><th>NOT GO Tol</th><th>NOT GO Min</th><th>NOT GO Max</th><th>NOT GO Hasil Min</th><th>NOT GO Hasil Max</th><th>Status</th></tr>';
                        body='<tr data-index="0"><td>'+qaReadonly(p.spesifikasi)+'</td><td>'+qaReadonly(p.tol_plus)+'</td><td>'+qaReadonly(p.tol_minus)+'</td><td>'+qaReadonly(p.go_std)+'</td><td>'+qaReadonly(p.go_tol_near)+'</td><td>'+qaReadonly(p.go_tol_far)+'</td><td>'+qaReadonly(p.go_min)+'</td><td>'+qaReadonly(p.go_max)+'</td><td>'+qaField('','go_hasil_min')+'</td><td>'+qaField('','go_hasil_max')+'</td><td>'+qaReadonly(p.notgo_std)+'</td><td>'+qaReadonly(p.notgo_tol)+'</td><td>'+qaReadonly(p.notgo_min)+'</td><td>'+qaReadonly(p.notgo_max)+'</td><td>'+qaField('','notgo_hasil_min')+'</td><td>'+qaField('','notgo_hasil_max')+'</td><td class="qa-row-status">-</td></tr>';
                    } else if (format === 'P') {
                        head='<tr><th>No</th><th>Type</th><th>Ukuran</th><th>Upart</th><th>Alat Standar</th><th>No Alat STD</th><th>STD Ukuran</th><th>Min</th><th>Max</th><th>Actual 1</th><th>Actual 2</th><th>Status</th></tr>';
                        points.forEach(function(p,i){body+='<tr data-index="'+i+'"><td>'+(i+1)+'</td><td>'+qaField(p.type,'type')+'</td><td>'+qaField(p.ukuran,'ukuran')+'</td><td>'+qaField(p.upart,'upart')+'</td><td>'+qaField(p.alat_nama,'alat_nama')+'</td><td>'+qaField(p.alat_no,'alat_no')+'</td><td>'+qaReadonly(p.std_label)+'</td><td>'+qaReadonly(p.min)+'</td><td>'+qaReadonly(p.max)+'</td><td>'+qaField('','actual1')+'</td><td>'+qaField('','actual2')+'</td><td class="qa-row-status">-</td></tr>';});
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
                    $('#btn_add_n_column, #qa_n_column_help').toggle(String(format).toUpperCase()==='N');
                    bindQaCalculations(format);
                }

                function alatForNoPart(){ return $('#qa_nomor_alat').val() || (currentAlatData && currentAlatData.no_part) || ''; }

                function qaTolCommon(){ return Math.abs(qaNum($('#qa_toleransi').val())); }
                function qaSetRowStatus($tr, ok){
                    const text = ok === null ? '-' : (ok ? 'OK' : 'NOT GOOD');
                    $tr.find('.qa-row-status').text(text).removeClass('text-success text-danger').addClass(ok === null ? '' : (ok ? 'text-success' : 'text-danger'));
                }
                function qaEvaluateStandard(actual, std, tol){
                    const a=qaNum(actual), s=qaNum(std), t=Number.isFinite(qaNum(tol)) ? Math.abs(qaNum(tol)) : qaTolCommon();
                    if (!Number.isFinite(a) || !Number.isFinite(s) || !Number.isFinite(t)) return null;
                    return Math.abs(a-s) <= t;
                }
                function bindQaCalculations(format){
                    $('#tabel_input_kalibrasi').off('input.qaCalc change.qaCalc').on('input.qaCalc change.qaCalc', '.qa-update-field', function(){
                        const $tr=$(this).closest('tr');
                        if(format==='B'){
                            const vals=['std1','std2','std3'].map(k=>qaNum($tr.find('[data-key="'+k+'"]').val())).filter(Number.isFinite); const min=qaNum($tr.find('td:eq(4) input').val()), max=qaNum($tr.find('td:eq(5) input').val());
                            if(vals.length){const avg=vals.reduce((a,b)=>a+b,0)/vals.length;$tr.find('.qa-rata').text(avg.toFixed(2));qaSetRowStatus($tr,Number.isFinite(min)&&Number.isFinite(max)?(avg>=min&&avg<=max):null);} else {$tr.find('.qa-rata').text('');qaSetRowStatus($tr,null);}
                        } else if(format==='C'){ const a=qaNum($tr.find('[data-key="actual"]').val()), s=qaNum($tr.find('[data-key="setting"]').val()), std=qaNum($tr.find('[data-key="alat_std"]').val()), d=Number.isFinite(a)&&Number.isFinite(std)?a-std:NaN; $tr.find('.qa-diff').text(Number.isFinite(d)?d.toFixed(4):''); qaSetRowStatus($tr,Number.isFinite(d)?Math.abs(d)<=qaTolCommon():null); }
                        else if(format==='D'){ const a=qaNum($tr.find('[data-key="hasil"]').val()),s=qaNum($tr.find('td:eq(4) input').val()),d=Number.isFinite(a)&&Number.isFinite(s)?a-s:NaN;$tr.find('.qa-koreksi').text(Number.isFinite(d)?d.toFixed(4):'');qaSetRowStatus($tr,Number.isFinite(d)?Math.abs(d)<=qaTolCommon():null); }
                        else if(format==='E'){ const s=qaNum($tr.find('td:eq(1) input').val()),t=qaNum($tr.find('td:eq(2) input').val()),a=qaNum($tr.find('[data-key="t1_hasil"]').val()),b=qaNum($tr.find('[data-key="t2_hasil"]').val());const k1=Number.isFinite(a)&&Number.isFinite(s)?a-s:NaN,k2=Number.isFinite(b)&&Number.isFinite(s)?b-s:NaN;$tr.find('.qa-t1-kor').text(Number.isFinite(k1)?k1.toFixed(4):'');$tr.find('.qa-t2-kor').text(Number.isFinite(k2)?k2.toFixed(4):'');qaSetRowStatus($tr,(!Number.isFinite(k1)&&!Number.isFinite(k2))?null:((!Number.isFinite(k1)||Math.abs(k1)<=(Number.isFinite(t)?Math.abs(t):qaTolCommon()))&&(!Number.isFinite(k2)||Math.abs(k2)<=(Number.isFinite(t)?Math.abs(t):qaTolCommon())))); }
                        else if(format==='F'){ const a=qaNum($tr.find('[data-key="sec2_hasil"]').val()),s=qaNum($tr.find('td:eq(1) input').val()),k=Number.isFinite(a)&&Number.isFinite(s)?a-s:NaN;$tr.find('.qa-sec2-kor').text(Number.isFinite(k)?k.toFixed(4):'');qaSetRowStatus($tr,Number.isFinite(k)?Math.abs(k)<=qaTolCommon():null); }
                        else if(format==='G'){ const s=qaNum($tr.find('td:eq(1) input').val()),t=qaNum($tr.find('td:eq(2) input').val()),a=qaNum($tr.find('[data-key="hasil"]').val()),b=qaNum($tr.find('[data-key="hasil_2"]').val());const vals=[a,b].filter(Number.isFinite);qaSetRowStatus($tr,vals.length?vals.every(v=>Number.isFinite(s)&&Math.abs(v-s)<= (Number.isFinite(t)?Math.abs(t):qaTolCommon())):null); }
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
                        else if(format==='I'){ const a=$tr.find('[data-key="hasil_atas"]').val().trim(),b=$tr.find('[data-key="hasil_bawah"]').val().trim();qaSetRowStatus($tr,(!a&&!b)?null:(/^(OK|OKE)$/i.test(a||b))); }
                        else if(format==='J'){ const h=$tr.find('[data-key="hasil_verifikasi"]').val();qaSetRowStatus($tr,h==='O'); }
                        else if(format==='O'){ const pairs=[['go_hasil_min','go_min'],['go_hasil_max','go_max'],['notgo_hasil_min','notgo_min'],['notgo_hasil_max','notgo_max']];const vals=pairs.map(p=>{const a=qaNum($tr.find('[data-key="'+p[0]+'"]').val()),b=qaNum($tr.find('input').eq(0).val());return Number.isFinite(a)?a:null;}).filter(v=>v!=null);qaSetRowStatus($tr,vals.length?null:null); }
                        else if(format==='P'){ const a=qaNum($tr.find('[data-key="actual1"]').val()),b=qaNum($tr.find('[data-key="actual2"]').val()),min=qaNum($tr.find('input').eq(7).val()),max=qaNum($tr.find('input').eq(8).val()),vals=[a,b].filter(Number.isFinite);qaSetRowStatus($tr,vals.length&&Number.isFinite(min)&&Number.isFinite(max)?vals.every(v=>v>=min&&v<=max):null); }
                        else if(format==='Q'){ const s=qaNum($tr.find('input').eq(3).val()),t=Math.abs(qaNum($tr.find('input').eq(4).val())),vals=['actual1','actual2','actual3','actual4'].map(k=>qaNum($tr.find('[data-key="'+k+'"]').val())).filter(Number.isFinite);qaSetRowStatus($tr,vals.length&&Number.isFinite(s)&&Number.isFinite(t)?vals.every(v=>Math.abs(v-s)<=t):null); }
                        qaUpdateFinalFromRows(format);
                    });
                    if(format==='M') $('#tabel_input_kalibrasi .qa-update-field').trigger('change');
                }

                function qaUpdateFinalFromRows(format){
                    const bad = $('#tabel_input_kalibrasi .qa-row-status').filter(function(){return /NOT GOOD|NG|X|TIDAK/i.test($(this).text());}).length>0;
                    const goodCount = $('#tabel_input_kalibrasi .qa-row-status').filter(function(){return /^OK$/i.test($(this).text());}).length;
                    if(bad) $('#qa_status_final').val('NOT GOOD');
                    else if(goodCount) $('#qa_status_final').val('OK');
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
                        else if(format==='G'){ }
                        else if(format==='H'){ o.std_out=$tr.find('td:eq(2) input').val();o.std_pitch=$tr.find('td:eq(3) input').val();o.out={dep:o.out_dep||'',ten:o.out_ten||'',bel:o.out_bel||''};o.pitch={dep:o.pitch_dep||'',ten:o.pitch_ten||'',bel:o.pitch_bel||''};delete o.out_dep;delete o.out_ten;delete o.out_bel;delete o.pitch_dep;delete o.pitch_ten;delete o.pitch_bel; }
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
                        const sec1=$('#tabel_input_kalibrasi [data-key="sec1_hasil"]').val(); if(sec1!=='') out.sec1={standar:meta.std_sec1||'',hasil:sec1,deviasi:(qaNum(sec1)-qaNum(meta.std_sec1)).toFixed(4)};
                        $('#tabel_input_kalibrasi tbody tr[data-index]').each(function(){out.sec2.push({standar:$(this).find('td:eq(1) input').val()||'',hasil:$(this).find('[data-key="sec2_hasil"]').val()||'',koreksi:$(this).find('.qa-sec2-kor').text()});});
                        out.sec3={tengah:$('#tabel_input_kalibrasi [data-key="tengah"]').val()||'',belakang:$('#tabel_input_kalibrasi [data-key="belakang"]').val()||'',kanan:$('#tabel_input_kalibrasi [data-key="kanan"]').val()||'',depan:$('#tabel_input_kalibrasi [data-key="depan"]').val()||'',kiri:$('#tabel_input_kalibrasi [data-key="kiri"]').val()||'',perbedaan:$('#tabel_input_kalibrasi .qa-sec3-diff').text()};
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
                        toleransi_global: $('#qa_toleransi').val(), toleransi: $('#qa_toleransi').val(),
                        satuan: currentAlatData ? currentAlatData.satuan : '',
                        suhu: $('#qa_suhu').val(), kelembapan: $('#qa_kelembapan').val(),
                        ketidakpastian: $('#qa_ketidakpastian').val(), confidence_level: $('#qa_confidence').val(),
                        status_final: statusQA, keputusan: statusQA,
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
                    if(format==='J') meta.alat_verifikasi = hasilArray[0] ? hasilArray[0].alat_verifikasi : '';
                    if(format==='P') meta.nama_alat = name;
                    if(format==='Q') meta.nama_alat = name;
                    if(format==='O') meta.nama_alat = name;
                    return meta;
                }

                window.qaRenderUpdateEditor = function(alat, history){
                    currentAlatData = alat; currentAlatHistory = history || [];
                    qaFillCommon(alat, history || []);
                    const format = String(alat.format_laporan || 'A').toUpperCase();
                    if (format === 'N') qaGetNColumns(history || []);
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
                            sessionStorage.setItem('registrasi_data', JSON.stringify(res.registration_print || {
                                no_laporan:regNoLaporan,no_part:regNo,nama_part:regNama,spesifikasi:regSpek,seksi_pemilik:regSeksi,lokasi:regLokasi,resolusi:regRes,
                                type:$('#reg_type').val()||'',kls:$('#reg_kls').val()||'',status:'Sudah Kalibrasi',tanggal_daftar:$('#reg_tgl_manual').val()||'',
                                petugas:'<?php echo addslashes($nama_petugas); ?>',operator:'<?php echo addslashes($nama_petugas); ?>',jenis_check:'Kalibrasi',hasil:'OK',diperiksa:'',pelaksanaan:'INTERNAL',keterangan:'Registrasi Alat Baru'
                            }));
                            BootstrapDialog.confirm({title:'Berhasil',type:BootstrapDialog.TYPE_SUCCESS,message:'Alat berhasil didaftarkan.<br><br><b>Cetak Buku Besar Riwayat Alat Ukur?</b>',btnCancelLabel:'Tidak',btnOKLabel:'Ya, Cetak',btnOKClass:'btn-primary',callback:function(ok){
                                if(ok) window.location.href='cetak_registrasi.php';
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
                        const data={no_part:$('#qa_nomor_alat').val(),no_laporan:noLaporan,status_qa:statusQA,hasil_json:jsonHasil,format_laporan:format,operator:$('#qa_petugas').val(),
                            nama_part:$('#qa_nama_alat').val(),spesifikasi:$('#qa_spesifikasi').val(),resolusi:$('#qa_resolusi').val(),lokasi:currentAlatData?currentAlatData.lokasi:'',
                            metode_uji:$('#qa_metode').val(),standar_uji:$('#qa_standar_uji').val(),traceability:$('#qa_trace').val(),toleransi_global:$('#qa_toleransi').val(),satuan:currentAlatData?currentAlatData.satuan:'',
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
                            if(hist.jenis_transaksi==='registrasi_alat'){
                                sessionStorage.setItem('registrasi_data',JSON.stringify(res.registration_print||{}));
                                window.location.href='cetak_registrasi.php';
                            }else{
                                sessionStorage.setItem('cetak_data',JSON.stringify(res.print_data||{}));
                                window.location.href='cetak_laporan.php';
                            }
                        } else BootstrapDialog.alert({title:'Gagal',type:BootstrapDialog.TYPE_DANGER,message:(res&&res.message)||'Data laporan gagal dimuat.'});
                    },'json').fail(function(xhr){btn.prop('disabled',false);BootstrapDialog.alert({title:'Gagal',type:BootstrapDialog.TYPE_DANGER,message:xhr.responseText||'Request gagal.'});});
                });

                // Agar alat Bad bisa langsung masuk flow registrasi unit baru.
                const oldBtnCek = $('#btn-cek').data('qaPatchBound');
                if(!oldBtnCek){
                    $('#btn-cek').data('qaPatchBound',true);
                    const originalClick = $('#btn-cek').off('click').on('click', function(){
                        var noAlat=$('#nomor_alat').val().trim().toUpperCase();
                        if(!noAlat){$('#group-input').addClass('has-error');$('#error-msg').slideDown(200);$('#nomor_alat').focus();return;}
                        $('#group-input').removeClass('has-error');$('#error-msg').slideUp(200);
                        var btn=$(this),textAsli=btn.html();btn.html('<i class="fa fa-spinner fa-spin"></i> Mengecek DB...').prop('disabled',true);
                        $.ajax({url:'proses_qa.php?action=cek_alat',type:'POST',dataType:'json',data:{no_part:noAlat},success:function(res){
                            btn.html(textAsli).prop('disabled',false);$('#form-update-kalibrasi,#form-registrasi').hide();
                            if(res.found){
                                var alat=res.data,history=res.history||[];currentAlatData=alat;currentAlatHistory=history;
                                $('#preview_gambar_alat').attr('src',alat.foto_alat?'../../images/alat/'+alat.foto_alat:'https://via.placeholder.com/300x200?text=Tidak+Ada+Gambar');
                                var tGabungan=$('#tabel-gabungan tbody').empty();tGabungan.append('<tr><td>1</td><td><strong>'+qaEsc(alat.no_part||'-')+'</strong></td><td>'+qaEsc(alat.nama_part||'-')+'</td><td>'+qaEsc(alat.spesifikasi||'-')+'</td><td>'+qaEsc(alat.seksi_pemilik||'-')+'</td><td>'+qaEsc(alat.lokasi||'-')+'</td><td><span class="label label-info">Jadwal</span></td><td><strong>'+formatTanggalJadwal(alat.jadwal_kalibrasi)+'</strong></td><td>-</td><td>-</td></tr>');
                                history.forEach(function(row,idx){var st=String(row.display_status||row.status_display||row.status||'').toUpperCase();var label=st==='OK'?'<span class="label label-success">OK</span>':(st==='NOT GOOD'||st==='BAD'||st==='RUSAK'||st==='DISCONTINUE'||st==='DITARIK MECHANICAL'?'<span class="label label-danger">'+qaEsc(st==='BAD'?'NOT GOOD':st)+'</span>':'<span class="label label-warning"><i class="fa fa-clock-o"></i> '+qaEsc(st||'MENUNGGU VALIDASI')+'</span>');var btnA='<button class="btn btn-xs btn-primary btn-print-hist" data-idx="'+idx+'"><i class="fa fa-print"></i> '+(row.jenis_transaksi==='registrasi_alat'?'Cetak Registrasi':'Cetak')+'</button>';tGabungan.append('<tr><td>'+(idx+2)+'</td><td><strong>'+qaEsc(row.no_part||alat.no_part||'-')+'</strong></td><td>'+qaEsc(alat.nama_part||'-')+'</td><td>'+qaEsc(alat.spesifikasi||'-')+'</td><td>'+qaEsc(alat.seksi_pemilik||'-')+'</td><td>'+qaEsc(alat.lokasi||'-')+'</td><td>'+label+'</td><td><strong>'+formatTanggal(row.tanggal)+'</strong></td><td>'+qaEsc(row.operator||row.petugas||'-')+'</td><td>'+btnA+'</td></tr>');});
                                $('#qa_nomor_alat').val(alat.no_part);$('#qa_format_alat').val(alat.format_laporan);qaRenderUpdateEditor(alat,history);$('#nomor_alat').prop('disabled',true);$('#form-update-kalibrasi').slideDown();
                            }else{
                                const openReg=function(){ $('#nomor_alat').prop('disabled',true);$('#reg_nomor_alat').val(res.original_no_part||noAlat).prop('readonly',true).prop('disabled',true);$('#section-form-registrasi').hide();$('#section-pilih-format').show();$('#reg_pilih_format').val('');$('#reg_no_laporan,#reg_nama_alat,#reg_spesifikasi,#reg_resolusi,#reg_toleransi,#reg_satuan,#reg_lokasi,#reg_metode,#reg_standar_uji,#reg_trace,#reg_periode,#reg_tgl_manual').val('');$('#preview_gambar_alat').attr('src','https://via.placeholder.com/300x200?text=Tidak+Ada+Gambar');$('#form-registrasi').slideDown();};
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
                                    if(canRestore){dlg.getModalFooter().prepend('<button type="button" class="btn btn-success" id="btnRestoreArchived"><i class="fa fa-check"></i> Aktifkan Kembali Nomor Alat</button>');
                                        $('#btnRestoreArchived').off('click').on('click',function(){
                                            var rb=$(this).prop('disabled',true);
                                            $.post('proses_qa.php?action=restore_alat',{archive_table:res.archive_table,archive_no_part:noAlatArsip,nomor_arsip:noArsip},function(rr){
                                                if(rr&&rr.status==='success'){
                                                    dlg.close();
                                                    BootstrapDialog.alert({title:'Aktivasi Berhasil',type:BootstrapDialog.TYPE_SUCCESS,message:'Nomor <b>'+qaEsc(rr.no_part)+'</b> sudah kembali AKTIF.',callback:function(){location.reload();}});
                                                }else{
                                                    rb.prop('disabled',false);
                                                    BootstrapDialog.alert({title:'Aktivasi Gagal',type:BootstrapDialog.TYPE_DANGER,message:qaEsc(rr&&rr.message||'Aktivasi gagal.')});
                                                }
                                            },'json').fail(function(xhr){
                                                rb.prop('disabled',false);
                                                BootstrapDialog.alert({title:'Aktivasi Gagal',type:BootstrapDialog.TYPE_DANGER,message:qaEsc(xhr.responseText||'Request gagal.')});
                                            });
                                        });
                                    }
                                } else openReg();
                            }
                        },error:function(){btn.html(textAsli).prop('disabled',false);BootstrapDialog.alert('Koneksi Error!');}});
                    });
                }

            })();

        });
    </script>
</body>
</html>
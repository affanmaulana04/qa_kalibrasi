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
$is_admin = ($status_admin == 'Y' || $status_admin == '1' || strtolower($status_admin) == 'admin' || strtolower($_SESSION['role']) == 'admin' || strtolower($_SESSION['level']) == 'admin');

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
        .table-entry th { background-color: #f8f9fa; color: #333; text-align: center; vertical-align: middle !important; border-bottom: 2px solid #ddd !important; font-size: 13px; }
        .table-entry td { text-align: center; vertical-align: middle !important; }
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
                                <thead><tr><th width="3%">NO</th><th>NO ALAT</th><th>NAMA ALAT</th><th>SPESIFIKASI</th><th>SEKSI PEMILIK</th><th>LOKASI</th><th style="background-color:#e2e6ea;">STATUS</th><th style="background-color:#e2e6ea;">TGL QA</th><th style="background-color:#e2e6ea; text-align:center;">AKSI</th></tr></thead>
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

                            <div class="form-group" style="margin-top: 25px;">
                                <div class="col-sm-12">
                                    <table class="table table-bordered table-entry" id="tabel_input_kalibrasi">
                                        <thead></thead><tbody></tbody>
                                    </table>
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

                            <div class="row"><div class="col-md-12"><div class="form-group" style="margin-left:0; margin-right:0;"><label>Keterangan Tambahan</label><textarea id="qa_keterangan" class="form-control" rows="2" placeholder="Catatan opsional..."></textarea></div></div></div>

                            <div class="row" style="margin-top: 15px;">
                                <div class="col-md-6"><div class="form-group" style="margin-left:0; margin-right:0;"><label>Petugas QA</label><div class="input-group"><span class="input-group-addon"><i class="fa fa-user"></i></span><input type="text" class="form-control" id="qa_petugas" value="<?php echo $nama_petugas; ?>" disabled style="background-color: #eee;"></div></div></div>
                                <div class="col-md-6">
                                    <div class="form-group" style="margin-left:0; margin-right:0;">
                                        <label>Keputusan (Status Final) <span class="text-danger">*</span></label>
                                        <select id="qa_status_final" class="form-control" style="font-weight:bold; font-size:16px;">
                                            <option value="">-- Pilih Status --</option><option value="Good">OK</option><option value="Bad">BURUK</option>
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
                                        <option value="A">Format A (Standar)</option>
                                        <option value="B">Format B</option>
                                        <option value="C">Format C</option>
                                        <option value="E">Format E (Thermometer / T1 & T2)</option>
                                        <option value="F">Format F (Timbangan / 3 Section)</option>
                                        <option value="G">Format G (Air Torque / 2 Kolom Hasil)</option>
                                        <option value="N">Format N (Toleransi Dinamis)</option>
                                        <option value="R">Format R (Range Standar & 3 Titik Hasil)</option>
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
                                                <th width="15%" class="text-center"><button type="button" class="btn btn-sm btn-primary" id="btn_add_standar" title="Tambah Standar"><i class="fa fa-plus"></i></button></th>
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
                            
                            // HIDE KETIDAKPASTIAN & CONFIDENCE UNTUK FORMAT G, N, R, E DI UI INPUT
                            if(['G','N','R','E'].includes(alat.format_laporan)) {
                                $('.div-ketidakpastian').hide();
                                $('.div-ruangan').removeClass('col-md-3').addClass('col-md-6');
                            } else {
                                $('.div-ketidakpastian').show();
                                $('.div-ruangan').removeClass('col-md-6').addClass('col-md-3');
                            }

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
                            tGabungan.append('<tr><td>1</td><td><strong>'+(alat.no_part || "-")+'</strong></td><td>'+(alat.nama_part || "-")+'</td><td>'+(alat.spesifikasi || "-")+'</td><td>'+(alat.seksi_pemilik || "-")+'</td><td>'+(alat.lokasi || "-")+'</td><td style="background-color:#eaf2f8;"><span class="label label-info"><i class="fa fa-clock-o"></i> Jadwal</span></td><td style="background-color:#eaf2f8;"><strong>'+formatTanggalJadwal(alat.jadwal_kalibrasi)+'</strong></td><td style="background-color:#eaf2f8; text-align:center;">-</td></tr>');

                            var num = 2;
                            if(history && history.length > 0) {
                                history.forEach(function(row, idx) {
                                    var st = row.status; var labelStatus = '';
                                    if(st === 'OK' || st === 'Good') { labelStatus = '<span class="label label-success">OK</span>'; } 
                                    else if(st === 'BURUK' || st === 'Bad' || st === 'rusak') { labelStatus = '<span class="label label-danger">BURUK</span>'; } 
                                    else { labelStatus = '<span class="label label-default">'+(st || "-")+'</span>'; }
                                    
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

            $(document).on('click', '.btn-print-hist', function() {
                var idx = $(this).data('idx');
                var histRow = currentAlatHistory[idx];
                
                var dataCetak = {
                    no_part: currentAlatData.no_part, 
                    nama_part: $('#qa_nama_alat').val(), 
                    seksi_pemilik: currentAlatData.seksi_pemilik, 
                    spesifikasi: currentAlatData.spesifikasi, 
                    resolusi: currentAlatData.resolusi, 
                    metode: $('#qa_metode').val(), 
                    standar_uji: $('#qa_standar_uji').val(), 
                    traceability: $('#qa_trace').val(),
                    suhu: histRow.suhu || '', 
                    kelembapan: histRow.kelembapan || '', 
                    ketidakpastian: histRow.ketidakpastian || '', 
                    confidence_level: histRow.confidence_level || '', 
                    toleransi_global: $('#qa_toleransi').val(), 
                    satuan: currentAlatData.satuan,
                    status_final: (histRow.status === 'Good' ? 'OK' : (histRow.status === 'Bad' ? 'BURUK' : histRow.status)), 
                    tanggal_periksa: histRow.tanggal, 
                    jadwal_berikutnya: (histRow.status === 'Bad' || histRow.status === 'rusak' ? null : histRow.jadwal_berikutnya), 
                    petugas: histRow.petugas || '<?php echo $nama_petugas; ?>', 
                    keterangan: histRow.keterangan || '', 
                    format: currentAlatData.format_laporan,
                    hasil_array: JSON.parse(histRow.hasil_json || "[]")
                };
                sessionStorage.setItem('cetak_data', JSON.stringify(dataCetak));
                window.location.href = 'cetak_laporan.php'; 
            });

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
                var isBuruk = false;
                $('#tabel_input_kalibrasi .td-status').each(function() {
                    if($(this).text() === 'BURUK') { isBuruk = true; }
                });
                if(isBuruk) { $('#qa_status_final').val('Bad'); } 
                else { $('#qa_status_final').val('Good'); }
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
                $('#qa_status_final').val('Good');
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
                        else { tdStatus.text('BURUK').removeClass('text-success').addClass('text-danger'); }
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
                        else { tdStatus.text('BURUK').removeClass('text-success').addClass('text-danger'); }
                    } else { tdStatus.text('-').removeClass('text-success text-danger'); }

                } else if (format === 'G') {
                    var h1Str = tr.find('.inp-hasil-1').val(); var h2Str = tr.find('.inp-hasil-2').val();
                    var h1 = parseFloat(h1Str), h2 = parseFloat(h2Str);
                    var st1 = isNaN(h1) ? true : (Math.abs(parseFloat((h1 - valStd).toFixed(4))) <= toleransi);
                    var st2 = isNaN(h2) ? true : (Math.abs(parseFloat((h2 - valStd).toFixed(4))) <= toleransi);

                    if(!isNaN(h1) || !isNaN(h2)) {
                        if(st1 && st2) { tdStatus.text('OK').removeClass('text-danger').addClass('text-success'); }
                        else { tdStatus.text('BURUK').removeClass('text-success').addClass('text-danger'); }
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
                        else { tdStatus.text('BURUK').removeClass('text-success').addClass('text-danger'); }
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

                if (statusQA === 'Bad') {
                    BootstrapDialog.confirm({
                        title: 'PERINGATAN! ALAT BURUK/RUSAK',
                        message: 'Keputusan Final alat ini adalah <b>BURUK</b>.<br><br>Alat ini akan dipindahkan ke database barang rusak dan <b>tidak akan memiliki jadwal kalibrasi berikutnya</b>.<br>Yakin ingin melanjutkan?',
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
                                status_final: (statusQA === 'Good' ? 'OK' : (statusQA === 'Bad' ? 'BURUK' : statusQA)), 
                                tanggal_periksa: "<?php echo date('Y-m-d'); ?>", 
                                jadwal_berikutnya: (statusQA === 'Bad' ? null : res.jadwal_selanjutnya), 
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

        });
    </script>
</body>
</html>
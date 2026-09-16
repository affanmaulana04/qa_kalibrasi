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
        
        .table-entry th { background-color: #1E3F66; color: white; text-align: center; }
        .table-entry td { text-align: center; vertical-align: middle !important; }
        .inp-hasil-kalibrasi { text-align: center; font-weight: bold; }
        .inp-koreksi { text-align: center; background-color: #f4f6f9 !important; font-weight:bold; }
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
                    <!-- 1. FORM UPDATE KALIBRASI                   -->
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
                                <label class="col-sm-2 control-label">No. Alat <span style="float:right;">:</span></label>
                                <div class="col-sm-4"><input type="text" class="form-control text-uppercase-input" id="qa_nomor_alat" disabled style="background-color: #eee; font-weight:bold;"></div>
                                <label class="col-sm-2 control-label">Nama Alat <span style="float:right;">:</span></label>
                                <div class="col-sm-4"><input type="text" class="form-control" id="qa_nama_alat" disabled style="background-color: #eee;"></div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Metode Uji <span style="float:right;">:</span></label>
                                <div class="col-sm-4"><input type="text" id="qa_metode" class="form-control" disabled style="background-color: #eee;"></div>
                                <label class="col-sm-2 control-label">Standar Uji <span style="float:right;">:</span></label>
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
                                    <table class="table table-bordered table-entry" id="tabel_input_kalibrasi">
                                        <thead></thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="row" style="margin-top: 20px;">
                                <div class="col-md-3">
                                    <div class="form-group" style="margin-left:0; margin-right:0;">
                                        <label>Suhu Ruangan</label>
                                        <div class="input-group">
                                            <input type="number" step="any" id="qa_suhu" class="form-control" placeholder="Cth: 21">
                                            <span class="input-group-addon">°C</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group" style="margin-left:0; margin-right:0;">
                                        <label>Kelembapan</label>
                                        <div class="input-group">
                                            <input type="number" step="any" id="qa_kelembapan" class="form-control" placeholder="Cth: 55">
                                            <span class="input-group-addon">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group" style="margin-left:0; margin-right:0;">
                                        <label>Ketidakpastian (U)</label>
                                        <div class="input-group">
                                            <input type="number" step="any" id="qa_ketidakpastian" class="form-control" placeholder="0.05">
                                            <span class="input-group-addon qa_satuan_label"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group" style="margin-left:0; margin-right:0;">
                                        <label>Confidence Lvl</label>
                                        <div class="input-group">
                                            <input type="number" step="any" id="qa_confidence" class="form-control" value="95">
                                            <span class="input-group-addon">%</span>
                                        </div>
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
                                        <option value="A">Format A</option>
                                        <option value="B">Format B</option>
                                        <option value="C">Format C</option>
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
                                        <label>No. Alat <span class="text-danger">*</span></label>
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
                                    
                                    <div class="form-group">
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
                                    
                                    <div class="form-group">
                                        <label>Lokasi</label>
                                        <input type="text" class="form-control" id="reg_lokasi" placeholder="Contoh: Lab GA">
                                    </div>
                                </div>

                                <!-- KOLOM KANAN -->
                                <div class="col-md-6" style="padding-left: 25px;">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Spesifikasi <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="reg_spesifikasi" placeholder="Cth: 5 ~ 15 mm">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
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
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Satuan (Unit) <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="reg_satuan" placeholder="Cth: mm / °C / Nm">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Metode Kalibrasi</label>
                                        <input type="text" class="form-control" id="reg_metode" placeholder="Cth: STIS-SOPF-731010">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Standar Uji</label>
                                                <input type="text" class="form-control" id="reg_standar_uji" placeholder="Cth: Inside Micrometer">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Traceability</label>
                                                <input type="text" class="form-control" id="reg_trace" placeholder="Cth: Gauge Block KL 0">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Type</label>
                                        <input type="text" class="form-control text-uppercase-input" id="reg_type" placeholder="Cth: TBI">
                                    </div>
                                    
                                    <div class="form-group" style="margin-top:15px;">
                                        <label><i class="fa fa-upload"></i> Upload Foto Alat</label>
                                        <input type="file" class="form-control" id="reg_foto_alat" accept="image/jpeg, image/png, image/jpg">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- TABEL TAMBAH STANDAR DINAMIS -->
                            <div class="row" style="padding: 0 30px; margin-top:10px;">
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
                            if (alat.foto_alat) $('#preview_gambar_alat').attr('src', '../../images/alat/' + alat.foto_alat);
                            else $('#preview_gambar_alat').attr('src', 'https://via.placeholder.com/300x200?text=Tidak+Ada+Gambar');
                            
                            $('#qa_nomor_alat').val(alat.no_part); $('#qa_nama_alat').val(alat.nama_part); 
                            $('#qa_metode').val(alat.metode_uji || "-"); $('#qa_standar_uji').val(alat.standar_uji || "-"); $('#qa_trace').val(alat.traceability || "-");
                            $('#qa_resolusi_value').val(alat.resolusi || "-");
                            $('.qa_satuan_label').text(alat.satuan); $('.qa_satuan_kurung').html(`( ${alat.satuan} )`);

                            var theadInput = $('#tabel_input_kalibrasi thead');
                            var isKustom = (alat.format_laporan === 'KUSTOM');
                            var arrStandar = JSON.parse(alat.titik_standar_json || "[]");
                            
                            var pakaiBagianDb = arrStandar.some(function(item) { return item.bagian && item.bagian.trim() !== ''; });
                            
                            if (isKustom) {
                                $('#qa_toleransi').val('Format Dinamis');
                                $('#qa_toleransi_value').val('');
                                var thBagian = pakaiBagianDb ? '<th width="20%">BAGIAN</th>' : '';
                                theadInput.html(`
                                    <tr>
                                        <th width="5%">NO</th>
                                        ${thBagian}
                                        <th width="20%">STANDAR <span class="qa_satuan_kurung"></span></th>
                                        <th width="15%">TOLERANSI (±)</th>
                                        <th width="20%">HASIL <span class="qa_satuan_kurung"></span></th>
                                        <th width="10%">KOREKSI</th>
                                        <th width="10%">STATUS</th>
                                    </tr>
                                `);
                            } else {
                                $('#qa_toleransi').val(alat.toleransi_global);
                                $('#qa_toleransi_value').val(alat.toleransi_global);
                                theadInput.html(`
                                    <tr>
                                        <th width="5%">NO</th>
                                        <th width="25%">STANDAR <span class="qa_satuan_kurung"></span></th>
                                        <th width="30%">HASIL <span class="qa_satuan_kurung"></span></th>
                                        <th width="25%">KOREKSI <span class="qa_satuan_kurung"></span></th>
                                        <th width="15%">STATUS</th>
                                    </tr>
                                `);
                            }
                            $('.qa_satuan_kurung').html(`( ${alat.satuan} )`);

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

                            $('#qa_suhu, #qa_kelembapan, #qa_ketidakpastian, #qa_keterangan, #qa_status_final, #qa_hasil_json').val('');
                            $('#qa_confidence').val('95');
                            $('#nomor_alat').prop('disabled', true);
                            $('#form-update-kalibrasi').slideDown();
                            setTimeout(function() { $('.inp-hasil-kalibrasi').first().focus(); }, 300);

                        } else {
                            $('#reg_nomor_alat').val(noAlat); 
                            $('#reg_nama_alat, #reg_spesifikasi, #reg_resolusi, #reg_toleransi, #reg_satuan, #reg_lokasi, #reg_type, #reg_metode, #reg_standar_uji, #reg_trace, #reg_periode, #reg_tgl_manual').val('');
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
            $('#btn-batal-qa, #btn-batal-reg').click(function() { $('.hidden-form-section').slideUp(); $('#nomor_alat').prop('disabled', false).val('').focus(); });

            $('#btn-lanjut-format').click(function() {
                var format = $('#reg_pilih_format').val();
                if(format === '') { BootstrapDialog.alert('Silakan pilih Format Kalibrasi terlebih dahulu!'); return; }
                
                if(format === 'KUSTOM') {
                    $('#label-info-tol').text('(Otomatis copy ke tabel bawah)');
                    $('.col-toleransi').show();
                } else {
                    $('#label-info-tol').text('');
                    $('.col-toleransi').hide();
                }
                $('#body_reg_standar').empty(); addRegRow();
                $('#section-pilih-format').slideUp(); $('#section-form-registrasi').slideDown();
            });

            $('#reg_toleransi').on('input', function() {
                if($('#reg_pilih_format').val() === 'KUSTOM') { $('.inp-reg-toleransi').val($(this).val()); }
            });

            $('#btn_toggle_bagian').click(function() {
                pakaiBagian = !pakaiBagian;
                if(pakaiBagian) { $('.col-bagian').show(); $(this).html('<i class="fa fa-columns"></i> Hapus Kolom \'Bagian\'').removeClass('btn-info').addClass('btn-warning'); } 
                else { $('.col-bagian').hide(); $('.inp-reg-bagian').val(''); $(this).html('<i class="fa fa-columns"></i> Tambah Kolom \'Bagian\'').removeClass('btn-warning').addClass('btn-info'); }
            });

            $('#btn_add_standar').click(function() { addRegRow(); });
            function addRegRow() {
                var trCount = $('#body_reg_standar tr').length + 1;
                var format = $('#reg_pilih_format').val();
                var currentTol = $('#reg_toleransi').val(); 
                
                var displayBagian = pakaiBagian ? '' : 'display:none;';
                var displayTol = (format === 'KUSTOM') ? '' : 'display:none;';
                
                var rowHtml = `
                    <tr>
                        <td class="text-center row-num" style="vertical-align:middle;">${trCount}</td>
                        <td class="col-bagian" style="${displayBagian}"><input type="text" class="form-control inp-reg-bagian" placeholder="Cth: Out Ø / Atas"></td>
                        <td><input type="number" step="any" class="form-control inp-reg-standar" required></td>
                        <td class="col-toleransi" style="${displayTol}"><input type="number" step="any" class="form-control inp-reg-toleransi" value="${currentTol}"></td>
                        <td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-hapus-std"><i class="fa fa-trash"></i></button></td>
                    </tr>
                `;
                $('#body_reg_standar').append(rowHtml); updateRowNumbers();
            }

            $(document).on('click', '.btn-hapus-std', function() { $(this).closest('tr').remove(); updateRowNumbers(); });
            function updateRowNumbers() { $('#body_reg_standar tr').each(function(index) { $(this).find('.row-num').text(index + 1); }); }

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

            $('#btn-simpan-qa').click(function() {
                var noAlat = $('#qa_nomor_alat').val();
                var statusQA = $('#qa_status_final').val();
                var jsonHasil = $('#qa_hasil_json').val(); 
                var totalRow = $('.inp-hasil-kalibrasi').length;
                var isiRow = JSON.parse(jsonHasil || "[]").length;

                if(isiRow < totalRow || statusQA === '') { BootstrapDialog.alert('Lengkapi semua hasil ukur dan Keputusan!'); return; }

                var btn = $(this); btn.html('<i class="fa fa-spinner fa-spin"></i> Submitting...'); btn.prop('disabled', true);

                $.ajax({
                    url: 'proses_qa.php?action=simpan_qa', type: 'POST', dataType: 'json',
                    data: { 
                        no_part: noAlat, status_qa: statusQA, hasil_json: jsonHasil, 
                        suhu: $('#qa_suhu').val(), kelembapan: $('#qa_kelembapan').val(), ketidakpastian: $('#qa_ketidakpastian').val(),
                        confidence: $('#qa_confidence').val(), keterangan: $('#qa_keterangan').val()
                    },
                    success: function(res) {
                        btn.html('Submit Hasil QA'); btn.prop('disabled', false);
                        if(res.status === 'success') {
                            
                            var dataCetak = {
                                no_part: noAlat, 
                                nama_part: $('#qa_nama_alat').val(), 
                                seksi_pemilik: $('#tabel-gabungan tbody tr:eq(0) td:eq(4)').text(), 
                                spesifikasi: $('#tabel-gabungan tbody tr:eq(0) td:eq(3)').text(),  
                                resolusi: $('#qa_resolusi_value').val(), 
                                metode: $('#qa_metode').val(), 
                                standar_uji: $('#qa_standar_uji').val(), 
                                traceability: $('#qa_trace').val(),
                                suhu: $('#qa_suhu').val(), 
                                kelembapan: $('#qa_kelembapan').val(), 
                                ketidakpastian: $('#qa_ketidakpastian').val(),
                                confidence_level: $('#qa_confidence').val(), // PENTING: DITAMBAHKAN UNTUK PRINTING CL
                                toleransi_global: $('#qa_toleransi').val(), 
                                satuan: $('.qa_satuan_label').first().text(),
                                status_final: (statusQA === 'Good' ? 'OK' : (statusQA === 'Bad' ? 'BURUK' : statusQA)), 
                                tanggal_periksa: "<?php echo date('Y-m-d'); ?>", 
                                jadwal_berikutnya: $('#tabel-gabungan tbody tr:eq(0) td:eq(9)').text(), 
                                petugas: $('#qa_petugas').val(), 
                                keterangan: $('#qa_keterangan').val(),
                                format: ($('#tabel_input_kalibrasi thead th:contains("TOLERANSI")').length > 0) ? 'KUSTOM' : 'A',
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
            });

            $('#btn-simpan-alat').click(function() {
                var format = $('#reg_pilih_format').val();
                var regNo = $('#reg_nomor_alat').val().toUpperCase();
                var arrStandar = []; var validToleransiFormatN = true;
                
                var useBagian = $('.col-bagian').is(':visible');

                $('#body_reg_standar tr').each(function() {
                    var bg = $(this).find('.inp-reg-bagian').val();
                    var std = $(this).find('.inp-reg-standar').val();
                    var tol = $(this).find('.inp-reg-toleransi').val();
                    
                    if(std !== '') {
                        if(format === 'KUSTOM') {
                            if(tol === '') validToleransiFormatN = false;
                            var r = { standar: parseFloat(std), toleransi: tol !== '' ? parseFloat(tol) : 0 };
                            if(useBagian) r.bagian = bg; 
                            arrStandar.push(r);
                        } else { arrStandar.push(parseFloat(std)); }
                    }
                });

                if($('#reg_nama_alat').val() === '' || $('#reg_spesifikasi').val() === '' || $('#reg_satuan').val() === '' || arrStandar.length === 0) {
                    BootstrapDialog.alert('Nama Alat, Spesifikasi, Satuan, dan minimal 1 Nilai Standar Uji wajib diisi!'); return;
                }
                if(format === 'KUSTOM' && !validToleransiFormatN) { BootstrapDialog.alert('Toleransi (±) Wajib diisi per baris pada <b>Format Kustom</b>!'); return; }

                var btn = $(this); btn.html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...'); btn.prop('disabled', true);

                var formData = new FormData();
                formData.append('no_part', regNo); formData.append('nama_part', $('#reg_nama_alat').val().trim());
                formData.append('seksi', $('#reg_seksi').val()); formData.append('periode', $('#reg_periode').val());
                formData.append('kls', $('#reg_kls').val()); formData.append('ukuran', '');
                formData.append('resolusi', $('#reg_resolusi').val().trim()); formData.append('lokasi', $('#reg_lokasi').val().trim());
                formData.append('type', $('#reg_type').val().trim().toUpperCase()); formData.append('tgl_manual', $('#reg_tgl_manual').val()); 
                formData.append('spesifikasi', $('#reg_spesifikasi').val().trim()); formData.append('toleransi_global', $('#reg_toleransi').val() || '');
                formData.append('satuan', $('#reg_satuan').val().trim()); formData.append('metode_uji', $('#reg_metode').val().trim());
                formData.append('standar_uji', $('#reg_standar_uji').val().trim()); formData.append('traceability', $('#reg_trace').val().trim());
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
<?php
session_start();
if ($_SESSION['login'] == 0) {
    header('location: ../../logout.php');
    exit();
}
include "../../inc/inc_koneksi.php"; 

$id_pemakai = $_SESSION['userid'];
$status_admin = isset($_SESSION['admin']) ? $_SESSION['admin'] : 'N';
$is_admin = ($status_admin == 'Y' || $status_admin == '1' || strtolower($status_admin) == 'admin' || strtolower($_SESSION['role']) == 'admin');

mysqli_query($konek, "CREATE TABLE IF NOT EXISTS qa_kls (id INT AUTO_INCREMENT PRIMARY KEY, nama_kelas VARCHAR(50) NOT NULL UNIQUE)");
mysqli_query($konek, "INSERT IGNORE INTO qa_kls (nama_kelas) VALUES ('KLS 1'), ('KLS 2'), ('KLS 3')");

mysqli_query($konek, "CREATE TABLE IF NOT EXISTS qa_seksi_master (id INT AUTO_INCREMENT PRIMARY KEY, nama_seksi VARCHAR(100) NOT NULL UNIQUE)");
mysqli_query($konek, "INSERT IGNORE INTO qa_seksi_master (nama_seksi) VALUES ('WH'), ('GA'), ('INJ'), ('QC'), ('MO'), ('TECH'), ('QA'), ('Maintenance')");

$cek_kolom_foto = mysqli_query($konek, "SHOW COLUMNS FROM qa_part LIKE 'foto_alat'");
if(mysqli_num_rows($cek_kolom_foto) == 0){
    mysqli_query($konek, "ALTER TABLE qa_part ADD foto_alat VARCHAR(255) NULL AFTER kls");
}

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
        .form-horizontal .form-group { margin-bottom: 15px; }
        .section-title { font-weight:bold; border-bottom:2px solid #eee; padding-bottom:10px; margin-bottom: 15px; margin-top: 0;}
        
        .text-uppercase-input { text-transform: uppercase; }
        .btn-simpan-inline { background-color: #ff9800; color: white; border: none; font-weight: bold; }
        
        .foto-alat-container { border: 2px dashed #ccc; border-radius: 10px; display: flex; align-items: center; justify-content: center; background-color: #fcfcfc; overflow: hidden; padding: 5px; height: 100%; min-height: 280px; aspect-ratio: 16/9;}
        .foto-alat-container img { width: 100%; height: 100%; object-fit: contain; }
        
        .flex-header-row { display: flex; align-items: stretch; flex-wrap: wrap; }
        .flex-col-center { display: flex; flex-direction: column; justify-content: center; }

        #form-registrasi .form-control, 
        #form-update-kalibrasi .form-control {
            height: 42px; 
            font-size: 14px;
        }
        
        #form-registrasi select.form-control, 
        #form-update-kalibrasi select.form-control {
            height: 42px !important;
        }

        #form-registrasi .input-group-addon, 
        #form-update-kalibrasi .input-group-addon {
            background-color: #e9ecef;
        }

        .input-group {
            width: 100%;
        }

        input[type="file"].form-control {
            height: auto !important;
            min-height: 42px;
            padding-top: 8px;
        }

        #form-registrasi .input-group-btn .btn, 
        #form-update-kalibrasi .input-group-btn .btn {
            height: 42px !important;
        }
    </style>
</head>
<body oncontextmenu="return false;">

    <nav class="navbar navbar-default navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header">
                <a class="navbar-brand" href="../../media.php"><img class="img-responsive" src="../../images/toto.png" style="height: 25px; margin-top: -3px;"></a>
            </div>
            <div class="collapse navbar-collapse">
                <ul class="nav navbar-nav navbar-left">
                    <li><a href="../../media.php"><i class="fa fa-arrow-left"></i> Kembali ke Menu Utama</a></li>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <li>
                        <a href="daftar_kalibrasi.php" style="padding-top: 10px; padding-bottom: 10px;">
                            <button class="btn btn-warning btn-sm" style="font-weight: bold; color: #333; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                <i class="fa fa-list-alt"></i> Tabel Jadwal Kalibrasi
                            </button>
                        </a>
                    </li>
                    <li><a href="#"><span style='color: #1469EA; font-weight: bold;'><i class="fa fa-fw fa-user"></i> <?php echo $id_pemakai; ?></span></a></li>
                    <li><a href="#" onclick="if(confirm('Apakah Anda Yakin Ingin Keluar?')) { window.location='../../logout.php'; } return false;"><i class="fa fa-sign-out"></i> Logout</a></li>
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

                    <div id="form-update-kalibrasi" class="hidden-form-section">
                        <h4 class="section-title text-success"><i class="fa fa-list-alt"></i> Detail & Riwayat Kalibrasi (Top 5)</h4>
                        <div class="table-responsive" style="margin-bottom: 30px;">
                            <table class="table table-bordered table-striped table-hover table-custom" id="tabel-gabungan">
                                <thead>
                                    <tr>
                                        <th width="3%">NO</th><th>NO ALAT</th><th>NAMA ALAT</th><th>UKURAN</th><th>NO FA</th><th>RESOLUSI</th><th>MERK</th><th>SEKSI PEMILIK</th><th>LOKASI</th><th>NO SERI</th><th>TYPE</th><th>KLS</th><th style="background-color:#e2e6ea;">SEKSI QA</th><th style="background-color:#e2e6ea;">STATUS</th><th style="background-color:#e2e6ea;">TGL QA</th>
                                    </tr>
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
                                <label class="col-sm-2 control-label">Status QA <span class="text-danger">*</span> <span style="float:right;">:</span></label>
                                <div class="col-sm-4">
                                    <select class="form-control" id="qa_status">
                                        <option value="">-- Pilih --</option><option value="Good">Good (Layak)</option><option value="Bad">Bad (Rusak)</option>
                                    </select>
                                </div>
                                <label class="col-sm-2 control-label">Seksi QA <span class="text-danger">*</span> <span style="float:right;">:</span></label>
                                <div class="col-sm-4">
                                    <div class="input-group">
                                        <select class="form-control" id="qa_seksi">
                                            <option value="">-- Pilih Seksi --</option>
                                            <?php 
                                            mysqli_data_seek($q_seksi, 0);
                                            while($r_seksi = mysqli_fetch_assoc($q_seksi)) { 
                                                echo '<option value="'.$r_seksi['nama_seksi'].'">'.$r_seksi['nama_seksi'].'</option>';
                                            } 
                                            ?>
                                        </select>
                                        <?php if($is_admin) { ?>
                                            <span class="input-group-btn">
                                                <button class="btn btn-danger" type="button" id="btn_delete_qa_seksi" title="Hapus Seksi Terpilih"><i class="fa fa-trash"></i></button>
                                            </span>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-top: 25px;">
                                <div class="col-sm-12 text-right">
                                    <button type="button" id="btn-batal-qa" class="btn btn-default" style="padding: 8px 20px; margin-right: 10px; font-weight:bold;">Tutup</button>
                                    <button type="button" id="btn-simpan-qa" class="btn btn-primary" style="padding: 8px 30px; font-weight:bold;">Submit Hasil QA</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="form-registrasi" class="hidden-form-section">
                        <div class="row">
                            <div class="col-md-8 col-md-offset-2 text-center">
                                <div class="alert alert-warning" style="font-size: 15px;">
                                    <strong><i class="fa fa-warning"></i> Nomor ini belum ada di master data. Silakan daftarkan Alat baru ini.</strong><br>
                                    <em>(Kolom tanpa bintang merah boleh dikosongkan).</em>
                                </div>
                            </div>
                        </div>

                        <div class="row" style="padding: 0 30px;">
                            <!-- KOLOM KIRI -->
                            <div class="col-md-6" style="padding-right: 25px;">
                                <div class="form-group">
                                    <label>No. Alat <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-uppercase-input" id="reg_nomor_alat" readonly style="background-color: #eee;">
                                </div>
                                <div class="form-group">
                                    <label>Nama Alat <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="reg_nama_alat" placeholder="Contoh: Timbangan mekanik">
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
                                            <input type="text" class="form-control text-uppercase-input" id="reg_seksi_custom" placeholder="Ketik Seksi Baru (Misal: GUDANG)">
                                            <span class="input-group-btn">
                                                <button class="btn btn-simpan-inline" type="button" id="btn_save_seksi"><i class="fa fa-save"></i> Simpan List</button>
                                            </span>
                                        </div>
                                    <?php } ?>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Periode Kalibrasi <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="reg_periode" placeholder="Contoh: 180" min="1">
                                                <span class="input-group-addon">Hari</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Tgl Input Manual</label>
                                            <input type="date" class="form-control" id="reg_tgl_manual" title="Gunakan untuk backdate alat lama">
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
                                            while($r_kls = mysqli_fetch_assoc($q_kls)) { 
                                                echo '<option value="'.$r_kls['nama_kelas'].'">'.$r_kls['nama_kelas'].'</option>';
                                            } 
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
                                            <span class="input-group-btn">
                                                <button class="btn btn-simpan-inline" type="button" id="btn_save_kls"><i class="fa fa-save"></i> Simpan List</button>
                                            </span>
                                        </div>
                                    <?php } ?>
                                </div>
                                
                                <!-- INPUT FOTO -->
                                <div class="form-group" style="margin-top:15px;">
                                    <label><i class="fa fa-upload"></i> Upload Foto Alat</label>
                                    <input type="file" class="form-control" id="reg_foto_alat" accept="image/jpeg, image/png, image/jpg">
                                    <small class="text-muted" style="display: inline-block; margin-top: 4px;">
                                        <i class="fa fa-info-circle"></i> Support file: <b>JPG, JPEG, PNG</b> (Max. 10MB)
                                    </small>
                                </div>
                            </div>

                            <!-- KOLOM KANAN -->
                            <div class="col-md-6" style="padding-left: 25px;">
                                <div class="form-group">
                                    <label>Ukuran</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="reg_ukuran" placeholder="Contoh: 150">
                                        <span class="input-group-addon" style="font-weight:bold;">KG</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Resolusi</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="reg_resolusi" placeholder="Contoh: 100">
                                        <span class="input-group-addon" style="font-weight:bold;">gram</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Merk</label>
                                    <input type="text" class="form-control" id="reg_merk" placeholder="Contoh: Tjie A Kai">
                                </div>
                                <div class="form-group">
                                    <label>Lokasi</label>
                                    <input type="text" class="form-control" id="reg_lokasi" placeholder="Contoh: Casting Pb. 2">
                                </div>
                                <div class="form-group">
                                    <label>No. Seri</label>
                                    <input type="text" class="form-control text-uppercase-input" id="reg_no_seri" placeholder="Contoh: G 010032">
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Type</label>
                                            <input type="text" class="form-control text-uppercase-input" id="reg_type" placeholder="Contoh: TBI">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>No. FA</label>
                                            <input type="text" class="form-control" id="reg_no_fa" placeholder="Contoh: 7101063">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row" style="margin-top: 25px; border-top: 1px solid #ddd; padding-top: 15px;">
                            <div class="col-md-4 col-md-offset-2">
                                <button type="button" id="btn-batal-reg" class="btn btn-default btn-block" style="height: 50px; font-weight:bold;">Batal</button>
                            </div>
                            <div class="col-md-4">
                                <button type="button" id="btn-simpan-alat" class="btn btn-success btn-block" style="height: 50px; font-weight:bold; font-size:16px;">
                                    Daftarkan Alat Baru <i class="fa fa-plus"></i>
                                </button>
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
                
                if(!valTerpilih || valTerpilih === 'tambah') {
                    BootstrapDialog.alert('Silakan pilih data yang mau dihapus di kolom tersebut!');
                    return;
                }

                var sebutan = (jenis === 'seksi') ? 'seksi' : 'kelas';
                
                BootstrapDialog.confirm({
                    title: 'Konfirmasi Hapus ' + (jenis === 'seksi' ? 'Seksi' : 'Kelas'),
                    message: 'Apakah anda yakin ingin menghapus ' + sebutan + ' <b>\'' + valTerpilih + '\'</b>?',
                    type: BootstrapDialog.TYPE_DANGER,
                    btnCancelLabel: 'Batal',
                    btnOKLabel: 'Hapus',
                    btnOKClass: 'btn-danger',
                    callback: function(result) {
                        if(result) {
                            var postData = {};
                            if(jenis === 'seksi') postData.nama_seksi = valTerpilih;
                            else postData.nama_kelas = valTerpilih;

                            $.ajax({
                                url: 'proses_qa.php?action=' + namaAksi,
                                type: 'POST', dataType: 'json', data: postData,
                                success: function(res) {
                                    if(res.status === 'success') {
                                        if(jenis === 'seksi') {
                                            $('#reg_seksi option[value="'+valTerpilih+'"]').remove();
                                            $('#qa_seksi option[value="'+valTerpilih+'"]').remove();
                                        } else {
                                            $('#reg_kls option[value="'+valTerpilih+'"]').remove();
                                        }
                                        BootstrapDialog.alert('Berhasil dihapus dari Database!');
                                    } else { BootstrapDialog.alert('Gagal: ' + res.message); }
                                }
                            });
                        }
                    }
                });
            }

            $('#btn_delete_reg_seksi, #btn_delete_qa_seksi').click(function() {
                var curId = $(this).attr('id') === 'btn_delete_reg_seksi' ? 'reg_seksi' : 'qa_seksi';
                hapusMaster('seksi', curId, 'hapus_master_seksi');
            });

            $('#btn_delete_reg_kls').click(function() {
                hapusMaster('kelas', 'reg_kls', 'hapus_master_kls');
            });


            $('#btn_save_kls').click(function() {
                var newKls = $('#reg_kls_custom').val().trim().toUpperCase();
                if(newKls === '') { BootstrapDialog.alert('Nama Kelas Baru wajib diisi!'); return; }
                var btn = $(this); var oriText = btn.html();
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
                $.ajax({
                    url: 'proses_qa.php?action=simpan_master_kls', type: 'POST', dataType: 'json', data: { nama_kelas: newKls },
                    success: function(res) {
                        btn.prop('disabled', false).html(oriText);
                        if(res.status === 'success') {
                            $('<option value="'+newKls+'">'+newKls+'</option>').insertBefore('#reg_kls option[value="tambah"]');
                            $('#reg_kls').val(newKls); $('#group_kls_custom').slideUp().find('input').val('');
                        } else { BootstrapDialog.alert('Gagal: ' + res.message); }
                    },
                    error: function() { btn.prop('disabled', false).html(oriText); BootstrapDialog.alert('Terjadi kesalahan koneksi!'); }
                });
            });

            $('#btn_save_seksi').click(function() {
                var newSeksi = $('#reg_seksi_custom').val().trim().toUpperCase();
                if(newSeksi === '') { BootstrapDialog.alert('Nama Seksi Baru wajib diisi!'); return; }
                var btn = $(this); var oriText = btn.html();
                btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
                $.ajax({
                    url: 'proses_qa.php?action=simpan_master_seksi', type: 'POST', dataType: 'json', data: { nama_seksi: newSeksi },
                    success: function(res) {
                        btn.prop('disabled', false).html(oriText);
                        if(res.status === 'success') {
                            $('<option value="'+newSeksi+'">'+newSeksi+'</option>').insertBefore('#reg_seksi option[value="tambah"]');
                            $('#qa_seksi').append('<option value="'+newSeksi+'">'+newSeksi+'</option>');
                            $('#reg_seksi').val(newSeksi); $('#group_seksi_custom').slideUp().find('input').val('');
                        } else { BootstrapDialog.alert('Gagal: ' + res.message); }
                    },
                    error: function() { btn.prop('disabled', false).html(oriText); BootstrapDialog.alert('Terjadi kesalahan koneksi!'); }
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
                if(noAlat === '') {
                    $('#group-input').addClass('has-error'); $('#error-msg').slideDown(200); $('#nomor_alat').focus(); return;
                } 

                $('#group-input').removeClass('has-error'); $('#error-msg').slideUp(200);
                var btn = $(this); var textAsli = btn.html();
                btn.html('<i class="fa fa-spinner fa-spin"></i> Mengecek DB...'); btn.prop('disabled', true);

                $.ajax({
                    url: 'proses_qa.php?action=cek_alat', type: 'POST', dataType: 'json', data: { no_part: noAlat },
                    success: function(response) {
                        btn.html(textAsli); btn.prop('disabled', false);
                        $('#form-update-kalibrasi, #form-registrasi').slideUp();

                        if(response.found) {
                            var alatDitemukan = response.data;
                            
                            if (alatDitemukan.foto_alat && alatDitemukan.foto_alat !== "") {
                                $('#preview_gambar_alat').attr('src', '../../images/alat/' + alatDitemukan.foto_alat);
                            } else {
                                $('#preview_gambar_alat').attr('src', 'https://via.placeholder.com/300x200?text=Tidak+Ada+Gambar');
                            }

                            var tGabungan = $('#tabel-gabungan tbody');
                            tGabungan.empty();
                            
                            var seksiPemilik = (alatDitemukan.seksi_pemilik === '0' || !alatDitemukan.seksi_pemilik) ? "-" : alatDitemukan.seksi_pemilik;
                            var num = 1;

                            tGabungan.append('<tr>'+
                                '<td>'+num+'</td>'+
                                '<td><strong>'+(alatDitemukan.no_part || "-")+'</strong></td>'+
                                '<td>'+(alatDitemukan.nama_part || "-")+'</td>'+
                                '<td>'+(alatDitemukan.ukuran || "-")+'</td>'+
                                '<td>'+(alatDitemukan.no_fa || "-")+'</td>'+
                                '<td>'+(alatDitemukan.resolusi || "-")+'</td>'+
                                '<td>'+(alatDitemukan.merk || "-")+'</td>'+
                                '<td>'+seksiPemilik+'</td>'+
                                '<td>'+(alatDitemukan.lokasi || "-")+'</td>'+
                                '<td>'+(alatDitemukan.no_seri || "-")+'</td>'+
                                '<td>'+(alatDitemukan.type || "-")+'</td>'+
                                '<td><span class="label label-warning">'+(alatDitemukan.kls || "-")+'</span></td>'+
                                '<td style="background-color:#eaf2f8;">-</td>'+ 
                                '<td style="background-color:#eaf2f8;"><span class="label label-info"><i class="fa fa-clock-o"></i> Jadwal</span></td>'+
                                '<td style="background-color:#eaf2f8;"><strong>'+formatTanggal(alatDitemukan.jadwal_kalibrasi)+'</strong></td>'+
                            '</tr>');
                            num++;
                            
                            if(response.history && response.history.length > 0) {
                                response.history.forEach(function(row) {
                                    var labelStatus = '';
                                    if(row.status === 'Good') labelStatus = '<span class="label label-success">Good</span>';
                                    else if(row.status === 'Bad') labelStatus = '<span class="label label-danger">Bad</span>';
                                    else labelStatus = '<span class="label label-default">'+row.status+'</span>'; 
                                    
                                    tGabungan.append('<tr>'+
                                        '<td>'+num+'</td>'+
                                        '<td><strong>'+(alatDitemukan.no_part || "-")+'</strong></td>'+
                                        '<td>'+(alatDitemukan.nama_part || "-")+'</td>'+
                                        '<td>'+(alatDitemukan.ukuran || "-")+'</td>'+
                                        '<td>'+(alatDitemukan.no_fa || "-")+'</td>'+
                                        '<td>'+(alatDitemukan.resolusi || "-")+'</td>'+
                                        '<td>'+(alatDitemukan.merk || "-")+'</td>'+
                                        '<td>'+seksiPemilik+'</td>'+
                                        '<td>'+(alatDitemukan.lokasi || "-")+'</td>'+
                                        '<td>'+(alatDitemukan.no_seri || "-")+'</td>'+
                                        '<td>'+(alatDitemukan.type || "-")+'</td>'+
                                        '<td><span class="label label-warning">'+(alatDitemukan.kls || "-")+'</span></td>'+
                                        '<td style="background-color:#f4f7fa;"><strong>'+(row.seksi_qa || "-")+'</strong></td>'+ 
                                        '<td style="background-color:#f4f7fa;">'+labelStatus+'</td>'+
                                        '<td style="background-color:#f4f7fa;"><strong>'+formatTanggal(row.tanggal)+'</strong></td>'+
                                    '</tr>');
                                    num++;
                                });
                            }

                            $('#qa_nama_alat').val(alatDitemukan.nama_part);
                            $('#qa_nomor_alat').val(alatDitemukan.no_part);
                            $('#qa_status').val(''); $('#qa_seksi').val('');
                            
                            $('#nomor_alat').prop('disabled', true);
                            $('#form-update-kalibrasi').slideDown();

                        } else {
                            $('#reg_nomor_alat').val(noAlat); 
                            $('#reg_nama_alat, #reg_ukuran, #reg_resolusi, #reg_merk, #reg_lokasi, #reg_no_seri, #reg_type, #reg_no_fa, #reg_periode, #reg_tgl_manual, #reg_foto_alat').val('');
                            $('#reg_seksi, #reg_kls').val('');
                            $('#preview_gambar_alat').attr('src', 'https://via.placeholder.com/300x200?text=Tidak+Ada+Gambar');
                            if($('#reg_kls_custom').length) $('#group_kls_custom, #group_seksi_custom').hide().find('input').val('');
                            
                            $('#nomor_alat').prop('disabled', true);
                            $('#form-registrasi').slideDown();
                        }
                    },
                    error: function() {
                        btn.html(textAsli); btn.prop('disabled', false); BootstrapDialog.alert('Terjadi kesalahan koneksi DB!');
                    }
                });
            });

            $('#nomor_alat').keypress(function(e) { if (e.which == 13) { $('#btn-cek').click(); } });

            $('#btn-batal-qa, #btn-batal-reg').click(function() {
                $('.hidden-form-section').slideUp();
                $('#nomor_alat').prop('disabled', false).val('').focus();
                $('#preview_gambar_alat').attr('src', '../../images/no-image.png');
            });

            $('#btn-simpan-qa').click(function() {
                var noAlat = $('#qa_nomor_alat').val();
                var statusQA = $('#qa_status').val();
                var seksiQA = $('#qa_seksi').val();

                if(statusQA === '' || seksiQA === '') { BootstrapDialog.alert('Status dan Seksi wajib dipilih!'); return; }

                var btn = $(this);
                btn.html('<i class="fa fa-spinner fa-spin"></i> Submitting...'); btn.prop('disabled', true);

                $.ajax({
                    url: 'proses_qa.php?action=simpan_qa', type: 'POST', dataType: 'json',
                    data: { no_part: noAlat, status_qa: statusQA, seksi_qa: seksiQA },
                    success: function(res) {
                        btn.html('Submit Hasil QA'); btn.prop('disabled', false);
                        if(res.status === 'success') {
                            BootstrapDialog.confirm({
                                title: 'QA Berhasil Disimpan',
                                message: 'Hasil kalibrasi disimpan.<br>Jadwal berikutnya diperbarui ke: <strong>' + formatTanggal(res.jadwal_selanjutnya) + '</strong><br><br><strong>Apakah Anda mau cetak slip kalibrasi?</strong>',
                                type: BootstrapDialog.TYPE_SUCCESS,
                                btnCancelLabel: 'Tidak (Refresh Data)',
                                btnOKLabel: 'Ya, Cetak Slip!',
                                btnOKClass: 'btn-primary',
                                callback: function(result) {
                                    if(result) { window.location.href = 'cetak_slip.php?no_pengecekan=' + res.no_pengecekan; } 
                                    else {
                                        $('.hidden-form-section').slideUp();
                                        $('#nomor_alat').prop('disabled', false).focus();
                                        $('#btn-cek').click(); 
                                    }
                                }
                            });
                        } else { BootstrapDialog.alert('Error DB: ' + res.message); }
                    },
                    error: function() { btn.html('Submit Hasil QA'); btn.prop('disabled', false); BootstrapDialog.alert('Terjadi kesalahan!'); }
                });
            });

            $('#btn-simpan-alat').click(function() {
                var regNo = $('#reg_nomor_alat').val().toUpperCase();
                var regNama = $('#reg_nama_alat').val().trim();
                var regSeksi = $('#reg_seksi').val();
                var regPeriode = $('#reg_periode').val().trim(); 
                var regKls = $('#reg_kls').val();
                var regTglManual = $('#reg_tgl_manual').val(); 

                if(regNama === '' || regSeksi === '' || regSeksi === 'tambah' || regPeriode === '' || regKls === '') {
                    BootstrapDialog.alert('Mohon lengkapi kolom yang memiliki tanda bintang merah (*)!'); return;
                }
                if(regKls === 'tambah') { BootstrapDialog.alert('Harap klik tombol "Simpan List" pada Kelas!'); return; }

                var rawUkuran = $('#reg_ukuran').val().trim();
                var regUkuran = rawUkuran !== '' ? rawUkuran + ' KG' : '';

                var rawResolusi = $('#reg_resolusi').val().trim();
                var regResolusi = rawResolusi !== '' ? rawResolusi + ' gram' : '';
                
                var inputFoto = $('#reg_foto_alat')[0];
                if (inputFoto.files && inputFoto.files[0]) {
                    if(inputFoto.files[0].size > 10485760) { BootstrapDialog.alert('Gagal! Ukuran gambar maksimal 10MB.'); return; }
                }

                var btn = $(this);
                btn.html('<i class="fa fa-spinner fa-spin"></i> Mendaftarkan ke DB...'); btn.prop('disabled', true);

                var formData = new FormData();
                formData.append('no_part', regNo);
                formData.append('nama_part', regNama);
                formData.append('seksi', regSeksi);
                formData.append('periode', regPeriode);
                formData.append('kls', regKls);
                formData.append('ukuran', regUkuran);
                formData.append('resolusi', regResolusi);
                formData.append('merk', $('#reg_merk').val().trim());
                formData.append('lokasi', $('#reg_lokasi').val().trim());
                formData.append('no_seri', $('#reg_no_seri').val().trim().toUpperCase());
                formData.append('type', $('#reg_type').val().trim().toUpperCase());
                formData.append('no_fa', $('#reg_no_fa').val().trim());
                formData.append('tgl_manual', regTglManual); 
                if (inputFoto.files && inputFoto.files[0]) { formData.append('foto_alat', inputFoto.files[0]); }

                $.ajax({
                    url: 'proses_qa.php?action=simpan_alat', type: 'POST', dataType: 'json',
                    data: formData, processData: false, contentType: false, 
                    success: function(res) {
                        btn.html('Daftarkan Alat Baru <i class="fa fa-plus"></i>'); btn.prop('disabled', false);
                        if(res.status === 'success') {
                            BootstrapDialog.alert({
                                title: 'Alat Masuk Master Data', type: BootstrapDialog.TYPE_SUCCESS,
                                message: 'Data berhasil disave di MySQL.<br>Jadwal otomatis: <strong>' + formatTanggal(res.jadwal_selanjutnya) + '</strong>',
                                callback: function() {
                                    $('#form-registrasi').slideUp();
                                    $('#nomor_alat').prop('disabled', false).val(regNo);
                                    $('#btn-cek').click(); 
                                }
                            });
                        } else { BootstrapDialog.alert('Error DB: ' + res.message); }
                    },
                    error: function() { btn.html('Daftarkan Alat Baru <i class="fa fa-plus"></i>'); btn.prop('disabled', false); BootstrapDialog.alert('Terjadi kesalahan AJAX upload!'); }
                });
            });

        });
    </script>
</body>
</html>
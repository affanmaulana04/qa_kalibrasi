<?php
session_start();
if (empty($_SESSION['login'])) { header('Location: ../../logout.php'); exit; }
include '../../inc/inc_koneksi.php';
require_once __DIR__.'/../../inc/qa_rbac.php';
if (empty($_SESSION['qa_user_admin_csrf'])) $_SESSION['qa_user_admin_csrf']=bin2hex(random_bytes(20));

if (!qaRbacIsAdmin($konek)) {http_response_code(403);exit('Hanya Admin QA.');}
qaRbacRequire($konek,'pengguna',false);
$id_pemakai = trim((string)($_SESSION['userid'] ?? ''));
$nama_pemakai = trim((string)($_SESSION['username'] ?? $id_pemakai));
$is_admin = strtoupper((string)($_SESSION['admin'] ?? 'N')) === 'Y' || strtolower((string)($_SESSION['role'] ?? '')) === 'admin';
if (!$is_admin) { http_response_code(403); echo '<div style="padding:30px;font-family:Arial"><h3>Akses ditolak</h3><p>Halaman ini hanya dapat diakses Admin QA.</p></div>'; exit; }

function ensure_user_column($db,$col,$def){
    $q=mysqli_query($db,"SHOW COLUMNS FROM `user` LIKE '".mysqli_real_escape_string($db,$col)."'");
    if($q && mysqli_num_rows($q)===0) mysqli_query($db,"ALTER TABLE `user` ADD COLUMN `".$col."` ".$def);
}
ensure_user_column($konek,'pembuat',"ENUM('Y','N') NOT NULL DEFAULT 'N'");
ensure_user_column($konek,'pemeriksa',"ENUM('Y','N') NOT NULL DEFAULT 'N'");
ensure_user_column($konek,'penyetuju',"ENUM('Y','N') NOT NULL DEFAULT 'N'");
ensure_user_column($konek,'block',"ENUM('Y','N') NOT NULL DEFAULT 'N'");

?>
<!DOCTYPE html><html lang="id"><head><link rel="icon" type="image/png" href="/qa_kalibrasi/images/favicon-toto-qa.png?v=1"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Pemakai QA</title>
<link href="../../css/bootstrap.min.css" rel="stylesheet"><link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet"><link href="../../css/jquery.dataTables.css" rel="stylesheet"><link href="../../css/dataTables.responsive.css" rel="stylesheet"><link href="../../css/bootstrap-dialog.css" rel="stylesheet"><style>body{padding-top:70px;background:#f4f6f9}.navbar-brand img{height:25px;margin-top:-3px}.card{background:#fff;border:1px solid #ddd;border-radius:10px;padding:22px}.toolbar{margin:0 0 15px}.table thead th{background:#e9eef4;text-align:center;vertical-align:middle}.table tbody td{vertical-align:middle}</style></head><body>
<nav class="navbar navbar-default navbar-fixed-top"><div class="container-fluid"><div class="navbar-header"><a class="navbar-brand" href="../../media.php"><img src="../../images/toto.png"></a></div><ul class="nav navbar-nav navbar-left"><li><a href="../../media.php"><i class="fa fa-arrow-left"></i> Menu QA</a></li></ul><ul class="nav navbar-nav navbar-right"><li><a><i class="fa fa-user"></i> <?php echo htmlspecialchars($nama_pemakai,ENT_QUOTES); ?></a></li><li><a href="../../logout.php">Logout</a></li></ul></div></nav>
<div class="container-fluid"><div class="card"><div class="clearfix"><div class="pull-left"><h3 style="margin-top:0;font-weight:bold;color:#1E3F66">Tabel Pemakai QA</h3></div><div class="pull-right"><a class="btn btn-default" href="../akses/roles.php"><i class="fa fa-lock"></i> Custom Role & Hak Akses</a> <a class="btn btn-success" href="pemakai_tambah.php"><i class="fa fa-plus"></i> Tambah User</a></div></div><hr>
<div id="tblUserError" class="alert alert-danger" style="display:none">Daftar user gagal dimuat. Coba muat ulang halaman.</div>
<div class="table-responsive"><table id="tblUser" class="table table-bordered table-striped table-hover"><thead><tr><th>ID</th><th>Nama</th><th>Role</th><th>Seksi</th><th>Admin</th><th>Pembuat</th><th>Pemeriksa</th><th>Penyetuju</th><th>Block</th><th>Online</th><th>Aksi</th></tr></thead><tbody></tbody></table></div></div></div>
<script src="../../js/jquery.js"></script><script src="../../js/bootstrap.min.js"></script><script src="../../js/jquery.dataTables.js"></script><script src="../../js/bootstrap-dialog.js"></script><script>
$(function(){
    $.fn.dataTable.ext.errMode = 'none';
    $('#tblUser').on('error.dt', function(){ $('#tblUserError').show(); });
    $('#tblUser').on('xhr.dt', function(e, settings, json){ if (json && !json.error) $('#tblUserError').hide(); });
    $('#tblUser').DataTable({
        processing: true,
        serverSide: true,
        ajax: {url: 'pemakai_data.php', type: 'POST'},
        scrollX: true,
        pageLength: 10,
        order: [[0, 'asc']],
        columnDefs: [{targets: 10, orderable: false, searchable: false}],
        language: {
            processing: 'Memuat data user...',
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ user',
            zeroRecords: 'User tidak ditemukan',
            info: 'Menampilkan _START_ - _END_ dari _TOTAL_ user',
            infoEmpty: 'Tidak ada user',
            paginate: {previous: 'Sebelumnya', next: 'Berikutnya'}
        }
    });
    $('#tblUser').on('click','.btn-hapus',function(){var uid=$(this).attr('data-uid');var safeUid=$('<div>').text(uid).html();BootstrapDialog.confirm({title:'Hapus User',type:BootstrapDialog.TYPE_DANGER,message:'Hapus user <b>'+safeUid+'</b>?',btnCancelLabel:'Batal',btnOKLabel:'Hapus',callback:function(ok){if(!ok)return;$.post('pemakai_hapus.php',{uid:uid,qa_user_csrf:<?=json_encode($_SESSION['qa_user_admin_csrf'])?>},function(r){BootstrapDialog.alert({title:'Hasil',message:r,type:r.indexOf('berhasil')>=0?BootstrapDialog.TYPE_SUCCESS:BootstrapDialog.TYPE_DANGER,callback:function(){location.reload();}});});}});});
});
</script></body></html>

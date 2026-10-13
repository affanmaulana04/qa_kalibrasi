<?php
session_start();
if (empty($_SESSION['login'])) { header('Location: ../../logout.php'); exit; }
include '../../inc/inc_koneksi.php';

$id_pemakai = trim((string)($_SESSION['userid'] ?? ''));
$nama_pemakai = trim((string)($_SESSION['username'] ?? $id_pemakai));
$is_admin = strtoupper((string)($_SESSION['admin'] ?? 'N')) === 'Y' || strtolower((string)($_SESSION['role'] ?? '')) === 'admin';
if (!$is_admin) { http_response_code(403); exit('Akses ditolak.'); }

function ensure_user_column($db, $col, $def) {
    $safe = mysqli_real_escape_string($db, $col);
    $q = mysqli_query($db, "SHOW COLUMNS FROM `user` LIKE '$safe'");
    if ($q && mysqli_num_rows($q) === 0) {
        mysqli_query($db, "ALTER TABLE `user` ADD COLUMN `$col` $def");
    }
}
ensure_user_column($konek, 'pembuat', "ENUM('Y','N') NOT NULL DEFAULT 'N'");
ensure_user_column($konek, 'pemeriksa', "ENUM('Y','N') NOT NULL DEFAULT 'N'");
ensure_user_column($konek, 'penyetuju', "ENUM('Y','N') NOT NULL DEFAULT 'N'");
ensure_user_column($konek, 'block', "ENUM('Y','N') NOT NULL DEFAULT 'N'");

$target = trim((string)($_GET['xuid'] ?? ''));
$stmt = mysqli_prepare($konek, "SELECT * FROM `user` WHERE uid=? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $target);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$user) { http_response_code(404); exit('User tidak ditemukan.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" type="image/png" href="/qa_kalibrasi/images/favicon-toto-qa.png?v=1">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit User QA</title>
<link href="../../css/bootstrap.min.css" rel="stylesheet">
<link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet">
<link href="../../css/bootstrap-dialog.css" rel="stylesheet">
<style>
body{padding-top:70px;background:#f4f6f9}.navbar-brand img{height:25px;margin-top:-3px}
.card{background:#fff;border:1px solid #ddd;border-radius:10px;padding:24px;margin-bottom:20px}
.form-title{font-weight:700;color:#1E3F66;margin-top:0}.flag-box{padding:15px;background:#f7f9fb;border:1px solid #d9e2ec;border-radius:8px}.btn-row{margin-top:25px}
</style>
</head>
<body>
<nav class="navbar navbar-default navbar-fixed-top">
<div class="container-fluid">
<div class="navbar-header"><a class="navbar-brand" href="../../media.php"><img src="../../images/toto.png"></a></div>
<ul class="nav navbar-nav navbar-left"><li><a href="pemakai.php"><i class="fa fa-arrow-left"></i> Tabel Pemakai QA</a></li></ul>
<ul class="nav navbar-nav navbar-right"><li><a><i class="fa fa-user"></i> <?php echo htmlspecialchars($nama_pemakai, ENT_QUOTES); ?></a></li><li><a href="../../logout.php">Logout</a></li></ul>
</div>
</nav>
<div class="container">
<div class="card">
<h3 class="form-title">Edit User QA</h3>
<p class="text-muted">Kelola identitas dan hak akses user QA.</p>
<form id="formUser">
<input type="hidden" name="status_proses" value="edit">
<input type="hidden" name="zuid" value="<?php echo htmlspecialchars($user['uid'], ENT_QUOTES); ?>">
<input type="hidden" name="xloginid" value="<?php echo htmlspecialchars($id_pemakai, ENT_QUOTES); ?>">
<div class="row">
<div class="col-md-6">
<div class="form-group"><label>ID User</label><input class="form-control" value="<?php echo htmlspecialchars($user['uid'], ENT_QUOTES); ?>" readonly></div>
<div class="form-group"><label>Nama *</label><input class="form-control" name="input_uname" value="<?php echo htmlspecialchars($user['uname'], ENT_QUOTES); ?>" required></div>
<div class="form-group"><label>Sapaan</label><select class="form-control" name="input_sapaan"><option value="">-</option><?php foreach(['Bpk.','Ibu','Mr.','Mrs.','Miss'] as $s){?><option value="<?php echo $s; ?>" <?php echo ($user['sapaan'] === $s ? 'selected' : ''); ?>><?php echo $s; ?></option><?php } ?></select></div>
<div class="form-group"><label>Seksi</label><input class="form-control" name="input_seksi" value="<?php echo htmlspecialchars($user['seksi'] ?? '', ENT_QUOTES); ?>"></div>
<div class="form-group"><label>E-mail</label><input type="email" class="form-control" name="input_email" value="<?php echo htmlspecialchars($user['email_pembuat'] ?? '', ENT_QUOTES); ?>"></div>
</div>
<div class="col-md-6">
<div class="flag-box">
<h4 style="margin-top:0">Hak Akses QA</h4>
<label class="checkbox"><input type="checkbox" name="input_admin" value="Y" <?php echo strtoupper((string)$user['admin'])==='Y'?'checked':''; ?>> <b>Admin QA</b></label>
<label class="checkbox"><input type="checkbox" name="input_pembuat" value="Y" <?php echo strtoupper((string)$user['pembuat'])==='Y'?'checked':''; ?>> <b>Pembuat</b> — Check / Update QA</label>
<label class="checkbox"><input type="checkbox" name="input_pemeriksa" value="Y" <?php echo strtoupper((string)$user['pemeriksa'])==='Y'?'checked':''; ?>> <b>Pemeriksa</b> — validasi tahap pemeriksa</label>
<label class="checkbox"><input type="checkbox" name="input_penyetuju" value="Y" <?php echo strtoupper((string)$user['penyetuju'])==='Y'?'checked':''; ?>> <b>Penyetuju</b> — validasi tahap penyetuju</label>
<label class="checkbox"><input type="checkbox" name="input_block" value="Y" <?php echo strtoupper((string)$user['block'])==='Y'?'checked':''; ?>> <b>Block</b> — tidak dapat login</label>
</div>
<div class="form-group" style="margin-top:15px"><label>Role</label><select class="form-control" name="input_role"><option value="QA" <?php echo strtolower((string)$user['role']) !== 'admin' ? 'selected' : ''; ?>>QA</option><option value="admin" <?php echo strtolower((string)$user['role']) === 'admin' ? 'selected' : ''; ?>>admin</option></select></div>
<div class="btn-row"><button type="button" id="simpan" class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button> <button type="button" id="batal" class="btn btn-default"><i class="fa fa-arrow-left"></i> Kembali</button></div>
</div>
</div>
</form>
</div>
</div>
<script src="../../js/jquery.js"></script><script src="../../js/bootstrap.min.js"></script><script src="../../js/bootstrap-dialog.js"></script>
<script>
$(function(){
  $('#batal').on('click',function(){window.location.href='pemakai.php';});
  $('#simpan').on('click',function(){
    var f=$('#formUser')[0]; if(!f.checkValidity()){f.reportValidity();return;}
    var b=$(this).prop('disabled',true);
    $.post('pemakai_simpan.php',$('#formUser').serialize(),function(r){
      if(r.status==='success'){
        BootstrapDialog.alert({title:'Berhasil',type:BootstrapDialog.TYPE_SUCCESS,message:r.message,callback:function(){window.location.href='pemakai.php';}});
      }else{
        b.prop('disabled',false); BootstrapDialog.alert({title:'Gagal',type:BootstrapDialog.TYPE_DANGER,message:r.message||'Gagal memperbarui user.'});
      }
    },'json').fail(function(x){b.prop('disabled',false);BootstrapDialog.alert({title:'Gagal',type:BootstrapDialog.TYPE_DANGER,message:x.responseText||'Request gagal.'});});
  });
});
</script>
</body>
</html>

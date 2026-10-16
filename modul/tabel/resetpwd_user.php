<?php
session_start();
if (empty($_SESSION['login'])) { header('Location: ../../logout.php'); exit; }
include '../../inc/inc_koneksi.php';
$is_admin = strtoupper((string)($_SESSION['admin'] ?? 'N')) === 'Y' || strtolower((string)($_SESSION['role'] ?? '')) === 'admin';
if (!$is_admin) { http_response_code(403); exit('Akses ditolak.'); }
$target = trim((string)($_SESSION['userid'] ?? ''));
$stmt = mysqli_prepare($konek, "SELECT uid, uname FROM `user` WHERE uid=? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $target);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$user) { http_response_code(404); exit('User tidak ditemukan.'); }
?>
<!DOCTYPE html><html lang="id"><head><link rel="icon" type="image/png" href="/qa_kalibrasi/images/favicon-toto-qa.png?v=1"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reset Password</title>
<link href="../../css/bootstrap.min.css" rel="stylesheet"><link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet"><link href="../../css/bootstrap-dialog.css" rel="stylesheet">
<style>body{padding-top:50px}.navbar-default .navbar-nav>li>a:hover,.navbar-default .navbar-nav>li>a:focus{background:#E7E7E7;color:#333}.card{padding:30px 15px}</style></head>
<body oncontextmenu="return false;">
<nav class="navbar navbar-default navbar-fixed-top" role="navigation"><div class="container"><div class="navbar-header"><button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#nav"><span class="sr-only">Toggle navigation</span><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></button><a class="navbar-brand" href="../../media.php"><img class="img-responsive" src="../../images/toto.png"></a></div><div class="collapse navbar-collapse" id="nav"><ul class="nav navbar-nav navbar-right"><li><a href="../../logout.php">Logout</a></li></ul></div></div></nav>
<div class="container"><div class="row"><div class="col-lg-12"><h2 class="page-header">Reset Password</h2></div><div class="col-lg-6"><form id="my-form" class="form-horizontal" role="form"><div class="form-group"><label class="col-sm-3 control-label">ID</label><div class="col-sm-9"><input type="text" class="form-control" value="<?php echo htmlspecialchars($user['uid'],ENT_QUOTES); ?>" disabled></div></div><div class="form-group"><label class="col-sm-3 control-label">Nama</label><div class="col-sm-9"><input type="text" class="form-control" value="<?php echo htmlspecialchars($user['uname'],ENT_QUOTES); ?>" disabled></div></div><div class="form-group"><label class="col-sm-3 control-label">Password Baru</label><div class="col-sm-9"><input type="password" class="form-control" name="input_upwd" id="input_upwd" maxlength="35" placeholder="Maksimal 35 karakter"></div></div><div class="form-group"><div class="col-sm-offset-3 col-sm-9"><button id="simpan" type="button" class="btn btn-primary">Simpan</button> <button id="batal" type="button" class="btn btn-default">Kembali</button><input type="hidden" name="input_uid" value="<?php echo htmlspecialchars($user['uid'],ENT_QUOTES); ?>"><input type="hidden" name="xloginid" value="<?php echo htmlspecialchars($_SESSION['userid']??'',ENT_QUOTES); ?>"><input type="hidden" name="status_proses" value="resetpwd"></div></div></form></div></div></div>
<script src="../../js/jquery.js"></script><script src="../../js/bootstrap.min.js"></script><script src="../../js/bootstrap-dialog.js"></script><script>
$(function(){ $('#batal').on('click',function(){location.href='../../media.php';}); $('#input_upwd').focus(); $('#simpan').on('click',function(){var p=$.trim($('#input_upwd').val()); if(!p){BootstrapDialog.alert('Password tidak boleh kosong');return;} $.post('pemakai_simpan.php',$('#my-form').serialize(),function(data){if($.trim(data)==='Proses reset password berhasil dilakukan'){BootstrapDialog.alert({message:'Proses reset password berhasil dilakukan',callback:function(){location.href='../../media.php';}});}else{BootstrapDialog.alert({message:data});}},'text');});});
</script></body></html>

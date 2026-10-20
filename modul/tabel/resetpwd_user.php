<?php
session_start();
if (empty($_SESSION['login'])) { header('Location: ../../logout.php'); exit; }
include '../../inc/inc_koneksi.php';
require_once __DIR__.'/../../inc/qa_rbac.php';
// Reset Password adalah fitur mandiri untuk semua user login, bukan izin Custom Role.
// Akun yang diblokir tetap tidak boleh melakukan perubahan password.
$target = trim((string)($_SESSION['userid'] ?? ''));
$stmt = mysqli_prepare($konek, "SELECT uid, uname, block FROM `user` WHERE uid=? LIMIT 1");
if (!$stmt) { http_response_code(500); exit('Gagal memuat data user.'); }
mysqli_stmt_bind_param($stmt, 's', $target);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$user || strtoupper((string)($user['block'] ?? 'N')) === 'Y') {
    http_response_code(403);
    exit('Akun tidak aktif atau tidak ditemukan.');
}
if (empty($_SESSION['qa_reset_password_csrf'])) {
    $_SESSION['qa_reset_password_csrf'] = bin2hex(random_bytes(20));
}
$resetCsrf = $_SESSION['qa_reset_password_csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    if (!hash_equals($resetCsrf, (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        echo json_encode(['status'=>'error','message'=>'Token keamanan tidak valid. Refresh halaman.']);
        exit;
    }
    // Target selalu berasal dari session, tidak dari input_uid / xloginid browser.
    $newPassword = trim((string)($_POST['input_upwd'] ?? ''));
    if ($newPassword === '' || strlen($newPassword) > 35) {
        http_response_code(400);
        echo json_encode(['status'=>'error','message'=>'Password baru wajib diisi (maksimal 35 karakter).']);
        exit;
    }
    // Ikuti format penyimpanan password aplikasi saat ini (cek_login.php).
    // Migrasi algoritma password dikerjakan terpisah supaya login lama tidak berubah.
    $passwordHash = md5($newPassword);
    $update = mysqli_prepare($konek, "UPDATE `user` SET pword=? WHERE uid=? AND block <> 'Y' LIMIT 1");
    if (!$update) {
        http_response_code(500);
        echo json_encode(['status'=>'error','message'=>'Gagal memproses reset password.']);
        exit;
    }
    mysqli_stmt_bind_param($update, 'ss', $passwordHash, $target);
    $ok = mysqli_stmt_execute($update);
    mysqli_stmt_close($update);
    if (!$ok) {
        http_response_code(500);
        echo json_encode(['status'=>'error','message'=>'Gagal menyimpan password baru.']);
        exit;
    }
    $_SESSION['qa_reset_password_csrf'] = bin2hex(random_bytes(20));
    echo json_encode(['status'=>'success','message'=>'Password berhasil diubah.']);
    exit;
}
?>
<!DOCTYPE html><html lang="id"><head><link rel="icon" type="image/png" href="/qa_kalibrasi/images/favicon-toto-qa.png?v=1"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reset Password</title>
<link href="../../css/bootstrap.min.css" rel="stylesheet"><link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet"><link href="../../css/bootstrap-dialog.css" rel="stylesheet">
<style>body{padding-top:50px}.navbar-default .navbar-nav>li>a:hover,.navbar-default .navbar-nav>li>a:focus{background:#E7E7E7;color:#333}.card{padding:30px 15px}</style></head>
<body oncontextmenu="return false;">
<nav class="navbar navbar-default navbar-fixed-top" role="navigation"><div class="container"><div class="navbar-header"><button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#nav"><span class="sr-only">Toggle navigation</span><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></button><a class="navbar-brand" href="../../media.php"><img class="img-responsive" src="../../images/toto.png"></a></div><div class="collapse navbar-collapse" id="nav"><ul class="nav navbar-nav navbar-right"><li><a href="../../logout.php">Logout</a></li></ul></div></div></nav>
<div class="container"><div class="row"><div class="col-lg-12"><h2 class="page-header">Reset Password</h2></div><div class="col-lg-6"><form id="my-form" class="form-horizontal" role="form"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($resetCsrf, ENT_QUOTES, 'UTF-8'); ?>"><div class="form-group"><label class="col-sm-3 control-label">ID</label><div class="col-sm-9"><input type="text" class="form-control" value="<?php echo htmlspecialchars($user['uid'],ENT_QUOTES); ?>" disabled></div></div><div class="form-group"><label class="col-sm-3 control-label">Nama</label><div class="col-sm-9"><input type="text" class="form-control" value="<?php echo htmlspecialchars($user['uname'],ENT_QUOTES); ?>" disabled></div></div><div class="form-group"><label class="col-sm-3 control-label">Password Baru</label><div class="col-sm-9"><input type="password" class="form-control" name="input_upwd" id="input_upwd" maxlength="35" placeholder="Maksimal 35 karakter"></div></div><div class="form-group"><div class="col-sm-offset-3 col-sm-9"><button id="simpan" type="button" class="btn btn-primary">Simpan</button> <button id="batal" type="button" class="btn btn-default">Kembali</button></div></div></form></div></div></div>
<script src="../../js/jquery.js"></script><script src="../../js/bootstrap.min.js"></script><script src="../../js/bootstrap-dialog.js"></script><script>
$(function(){ $('#batal').on('click',function(){location.href='../../media.php';}); $('#input_upwd').focus(); $('#simpan').on('click',function(){var p=$.trim($('#input_upwd').val()); if(!p){BootstrapDialog.alert('Password tidak boleh kosong');return;} var b=$(this).prop('disabled',true);$.post('resetpwd_user.php',$('#my-form').serialize(),function(data){b.prop('disabled',false);if(data&&data.status==='success'){BootstrapDialog.alert({message:data.message,callback:function(){location.href='../../media.php';}});}else{BootstrapDialog.alert({message:(data&&data.message)||'Gagal mengubah password.'});}},'json').fail(function(xhr){b.prop('disabled',false);var data=xhr.responseJSON;BootstrapDialog.alert({message:(data&&data.message)||'Gagal mengubah password. Silakan coba lagi.'});});});});
</script></body></html>

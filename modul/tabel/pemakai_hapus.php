<?php
session_start();
if (empty($_SESSION['login'])) { http_response_code(401); exit('Login diperlukan.'); }
include '../../inc/inc_koneksi.php';
$is_admin=strtoupper((string)($_SESSION['admin']??'N'))==='Y'||strtolower((string)($_SESSION['role']??''))==='admin'; if(!$is_admin) exit('Akses ditolak.');
$uid=trim((string)($_POST['uid']??''));
if($uid==='' ) exit('ID user tidak valid.'); if(strtolower($uid)==='admin') exit('Id pemakai admin tidak bisa dihapus.');
$stmt=$konek->prepare("DELETE FROM `user` WHERE uid=?");$stmt->bind_param('s',$uid);$ok=$stmt->execute();$stmt->close();
echo $ok?'Data '.$uid.' berhasil dihapus':'Gagal menghapus user.';
?>
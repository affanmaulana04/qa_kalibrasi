<?php
session_start();
if (empty($_SESSION['login'])) { http_response_code(401); exit('Login diperlukan.'); }
include '../../inc/inc_koneksi.php';
require_once __DIR__.'/../../inc/qa_rbac.php';
if (empty($_SESSION['qa_user_admin_csrf']) || !hash_equals((string)$_SESSION['qa_user_admin_csrf'],(string)($_POST['qa_user_csrf']??''))) {http_response_code(403);exit('Token keamanan user tidak valid. Refresh halaman.');}

if (!qaRbacIsAdmin($konek)) {http_response_code(403);exit('Hanya Admin QA.');}
qaRbacRequire($konek,'pengguna',false);
$is_admin=strtoupper((string)($_SESSION['admin']??'N'))==='Y'||strtolower((string)($_SESSION['role']??''))==='admin'; if(!$is_admin) exit('Akses ditolak.');
$uid=trim((string)($_POST['uid']??''));
if($uid==='' ) exit('ID user tidak valid.'); if(strtolower($uid)==='admin') exit('Id pemakai admin tidak bisa dihapus.');
$stmt=$konek->prepare("DELETE FROM `user` WHERE uid=?");$stmt->bind_param('s',$uid);$ok=$stmt->execute();$stmt->close();
if($ok && qaRbacReady($konek)) { $st=$konek->prepare('DELETE FROM qa_user_roles WHERE uid=?'); if($st){$st->bind_param('s',$uid);$st->execute();$st->close();} }
echo $ok?'Data '.$uid.' berhasil dihapus':'Gagal menghapus user.';
?>
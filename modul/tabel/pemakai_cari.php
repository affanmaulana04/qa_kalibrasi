<?php
session_start();
if (empty($_SESSION['login'])) { http_response_code(401); echo json_encode(['status'=>'error','message'=>'Login diperlukan.']); exit; }
include '../../inc/inc_koneksi.php'; header('Content-Type: application/json; charset=utf-8');
$uid=trim((string)($_POST['uid']??''));
$stmt=$konek->prepare("SELECT uid,uname,pword,admin,role,sapaan,seksi,email_pembuat,pembuat,pemeriksa,penyetuju,block,online FROM `user` WHERE uid=? LIMIT 1");
if(!$stmt){echo json_encode(['status'=>'error','message'=>mysqli_error($konek)]);exit;} $stmt->bind_param('s',$uid);$stmt->execute();$r=$stmt->get_result()->fetch_assoc();$stmt->close();
echo json_encode($r?['status'=>'success','data'=>$r]:['status'=>'error','message'=>'User tidak ditemukan.'],JSON_UNESCAPED_UNICODE);
?>
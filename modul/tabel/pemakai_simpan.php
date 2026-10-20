<?php
session_start();
if (empty($_SESSION['login'])) { http_response_code(401); header('Content-Type: application/json'); echo json_encode(['status'=>'error','message'=>'Login diperlukan.']); exit; }
include '../../inc/inc_koneksi.php';
require_once __DIR__.'/../../inc/qa_rbac.php';
if (empty($_SESSION['qa_user_admin_csrf']) || !hash_equals((string)$_SESSION['qa_user_admin_csrf'],(string)($_POST['qa_user_csrf']??''))) {http_response_code(403);exit('Token keamanan user tidak valid. Refresh halaman.');}

if (!qaRbacIsAdmin($konek)) {http_response_code(403);exit('Hanya Admin QA.');}
qaRbacRequire($konek,'pengguna',true); header('Content-Type: application/json; charset=utf-8');
$is_admin=strtoupper((string)($_SESSION['admin']??'N'))==='Y'||strtolower((string)($_SESSION['role']??''))==='admin'; if(!$is_admin){http_response_code(403);echo json_encode(['status'=>'error','message'=>'Hanya Admin QA yang dapat mengelola user.']);exit;}
function ensure_col($db,$c,$def){$q=mysqli_query($db,"SHOW COLUMNS FROM `user` LIKE '".mysqli_real_escape_string($db,$c)."'");if($q&&mysqli_num_rows($q)===0)mysqli_query($db,"ALTER TABLE `user` ADD COLUMN `$c` $def");}
ensure_col($konek,'pembuat',"ENUM('Y','N') NOT NULL DEFAULT 'N'");ensure_col($konek,'pemeriksa',"ENUM('Y','N') NOT NULL DEFAULT 'N'");ensure_col($konek,'penyetuju',"ENUM('Y','N') NOT NULL DEFAULT 'N'");ensure_col($konek,'block',"ENUM('Y','N') NOT NULL DEFAULT 'N'");
$status=trim((string)($_POST['status_proses']??'')); $uid=trim((string)($_POST['input_uid']??($_POST['zuid']??''))); $uidOld=trim((string)($_POST['zuid']??'')); $uname=trim((string)($_POST['input_uname']??'')); $role=trim((string)($_POST['input_role']??'QA')); $admin=isset($_POST['input_admin'])?'Y':'N'; $pembuat=isset($_POST['input_pembuat'])?'Y':'N'; $pemeriksa=isset($_POST['input_pemeriksa'])?'Y':'N'; $penyetuju=isset($_POST['input_penyetuju'])?'Y':'N'; $block=isset($_POST['input_block'])?'Y':'N'; $sapaan=trim((string)($_POST['input_sapaan']??''));$seksi=trim((string)($_POST['input_seksi']??''));$email=trim((string)($_POST['input_email']??''));$creator=trim((string)($_SESSION['userid']??''));

// Role custom ditentukan Admin di server; POST tidak bisa membuat izin sendiri.
$roleIdRaw=trim((string)($_POST['input_custom_role']??'0'));
$customRoleId=ctype_digit($roleIdRaw)?(int)$roleIdRaw:-1;
if (qaRbacReady($konek)) {
  if ($customRoleId<0) {echo json_encode(['status'=>'error','message'=>'Pilihan Custom Role tidak valid.']);exit;}
  if ($customRoleId>0) {
    $test=mysqli_query($konek,'SELECT id FROM qa_roles WHERE aktif=1 AND id='.$customRoleId.' LIMIT 1');
    if (!$test || mysqli_num_rows($test)===0) {echo json_encode(['status'=>'error','message'=>'Custom Role tidak ditemukan.']);exit;}
  }
  if ($status==='tambah' && $admin!=='Y' && $customRoleId===0) {
    echo json_encode(['status'=>'error','message'=>'Pilih Custom Role untuk user baru.']);exit;
  }
}
$role = $admin==='Y' ? 'admin' : 'QA'; // Kolom lama dipertahankan untuk kompatibilitas login; bukan sumber izin.
if ($customRoleId>0 && $admin!=='Y') { $pembuat='N';$pemeriksa='N';$penyetuju='N'; } // Role gagal dipetakan tidak boleh membuka izin legacy.
if($status==='tambah'){
  if($uid===''||$uname===''||trim((string)($_POST['input_upwd']??''))===''){echo json_encode(['status'=>'error','message'=>'ID, Nama, dan Password wajib diisi.']);exit;}
  $chk=$konek->prepare("SELECT uid FROM `user` WHERE uid=? LIMIT 1");$chk->bind_param('s',$uid);$chk->execute();$exists=$chk->get_result()->num_rows>0;$chk->close();if($exists){echo json_encode(['status'=>'error','message'=>'ID Pemakai sudah ada.']);exit;}
  if($admin==='Y'){$role='admin';$pembuat='Y';$pemeriksa='Y';$penyetuju='Y';}
  $hash=md5(trim((string)$_POST['input_upwd']));
  $stmt=$konek->prepare("INSERT INTO `user` (uid,uname,pword,admin,role,sapaan,seksi,email_pembuat,pembuat,pemeriksa,penyetuju,block,createdby,crtdate) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
  $stmt->bind_param('sssssssssssss',$uid,$uname,$hash,$admin,$role,$sapaan,$seksi,$email,$pembuat,$pemeriksa,$penyetuju,$block,$creator); $ok=$stmt->execute();$err=$stmt->error;$stmt->close();
  if ($ok && qaRbacReady($konek) && !qaRbacSetUserRole($konek,$uid,$customRoleId)) {
    echo json_encode(['status'=>'error','message'=>'User tersimpan tetapi Custom Role gagal dipasang. Edit kembali user ini.']);exit;
  }
  echo json_encode($ok?['status'=>'success','message'=>'User berhasil ditambahkan.']:['status'=>'error','message'=>$err?:'Gagal menambahkan user.']);exit;
}
if($status==='edit'){
  if($uidOld===''||$uname===''){echo json_encode(['status'=>'error','message'=>'Data user tidak lengkap.']);exit;} if(strtolower($uidOld)==='admin' && $admin!=='Y'){echo json_encode(['status'=>'error','message'=>'User admin utama harus tetap memiliki hak Admin.']);exit;} if($admin==='Y'){$role='admin';$pembuat='Y';$pemeriksa='Y';$penyetuju='Y';}
  $stmt=$konek->prepare("UPDATE `user` SET uname=?,admin=?,role=?,sapaan=?,seksi=?,email_pembuat=?,pembuat=?,pemeriksa=?,penyetuju=?,block=?,changeby=?,chgdate=NOW() WHERE uid=?");
  $stmt->bind_param('ssssssssssss',$uname,$admin,$role,$sapaan,$seksi,$email,$pembuat,$pemeriksa,$penyetuju,$block,$creator,$uidOld);$ok=$stmt->execute();$err=$stmt->error;$stmt->close();if ($ok && qaRbacReady($konek) && !qaRbacSetUserRole($konek,$uidOld,$customRoleId)) {
    echo json_encode(['status'=>'error','message'=>'Data user berubah tetapi pemetaan Custom Role gagal. Silakan edit kembali.']);exit;
  }
  echo json_encode($ok?['status'=>'success','message'=>'User berhasil diperbarui.']:['status'=>'error','message'=>$err?:'Gagal memperbarui user.']);exit;
}
echo json_encode(['status'=>'error','message'=>'Status proses tidak valid.']);
?>
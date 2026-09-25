<?php
session_start();
if (empty($_SESSION['login'])) { header('Location: ../../logout.php'); exit; }
include '../../inc/inc_koneksi.php';
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

$res=mysqli_query($konek,"SELECT uid,uname,admin,role,sapaan,seksi,email_pembuat,pembuat,pemeriksa,penyetuju,block,online,pc_login FROM `user` ORDER BY uid");
?>
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Pemakai QA</title>
<link href="../../css/bootstrap.min.css" rel="stylesheet"><link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet"><link href="../../css/jquery.dataTables.css" rel="stylesheet"><link href="../../css/dataTables.responsive.css" rel="stylesheet"><link href="../../css/bootstrap-dialog.css" rel="stylesheet"><style>body{padding-top:70px;background:#f4f6f9}.navbar-brand img{height:25px;margin-top:-3px}.card{background:#fff;border:1px solid #ddd;border-radius:10px;padding:22px}.toolbar{margin:0 0 15px}.table thead th{background:#e9eef4;text-align:center;vertical-align:middle}.table tbody td{vertical-align:middle}</style></head><body>
<nav class="navbar navbar-default navbar-fixed-top"><div class="container-fluid"><div class="navbar-header"><a class="navbar-brand" href="../../media.php"><img src="../../images/toto.png"></a></div><ul class="nav navbar-nav navbar-left"><li><a href="../../media.php"><i class="fa fa-arrow-left"></i> Menu QA</a></li></ul><ul class="nav navbar-nav navbar-right"><li><a><i class="fa fa-user"></i> <?php echo htmlspecialchars($nama_pemakai,ENT_QUOTES); ?></a></li><li><a href="../../logout.php">Logout</a></li></ul></div></nav>
<div class="container-fluid"><div class="card"><div class="clearfix"><div class="pull-left"><h3 style="margin-top:0;font-weight:bold;color:#1E3F66">Tabel Pemakai QA</h3></div><div class="pull-right"><a class="btn btn-success" href="pemakai_tambah.php"><i class="fa fa-plus"></i> Tambah User</a></div></div><hr>
<div class="table-responsive"><table id="tblUser" class="table table-bordered table-striped table-hover"><thead><tr><th>ID</th><th>Nama</th><th>Role</th><th>Seksi</th><th>Admin</th><th>Pembuat</th><th>Pemeriksa</th><th>Penyetuju</th><th>Block</th><th>Online</th><th>Aksi</th></tr></thead><tbody>
<?php while($r=mysqli_fetch_assoc($res)){ $uid=htmlspecialchars($r['uid'],ENT_QUOTES); ?><tr>
<td><b><?php echo $uid; ?></b></td><td><?php echo htmlspecialchars($r['uname'],ENT_QUOTES); ?></td><td><?php echo htmlspecialchars($r['role'],ENT_QUOTES); ?></td><td><?php echo htmlspecialchars($r['seksi'],ENT_QUOTES); ?></td>
<td class="text-center"><?php echo strtoupper($r['admin'])==='Y'?'✓':''; ?></td><td class="text-center"><?php echo strtoupper($r['pembuat'])==='Y'?'✓':''; ?></td><td class="text-center"><?php echo strtoupper($r['pemeriksa'])==='Y'?'✓':''; ?></td><td class="text-center"><?php echo strtoupper($r['penyetuju'])==='Y'?'✓':''; ?></td><td class="text-center"><?php echo strtoupper($r['block'])==='Y'?'✓':''; ?></td><td class="text-center"><?php echo strtoupper($r['online'])==='Y'?'●':''; ?></td>
<td class="text-center" style="white-space:nowrap"><a class="btn btn-xs btn-warning" href="pemakai_edit.php?xuid=<?php echo urlencode($r['uid']); ?>"><i class="fa fa-pencil"></i> Edit</a> <a class="btn btn-xs btn-primary" href="pemakai_resetpwd.php?xuid=<?php echo urlencode($r['uid']); ?>"><i class="fa fa-refresh"></i> Reset Password</a> <?php if(strtolower($r['uid'])!=='admin'){ ?><button class="btn btn-xs btn-danger btn-hapus" data-uid="<?php echo $uid; ?>"><i class="fa fa-trash"></i> Hapus</button><?php } ?></td>
</tr><?php } ?></tbody></table></div></div></div>
<script src="../../js/jquery.js"></script><script src="../../js/bootstrap.min.js"></script><script src="../../js/jquery.dataTables.js"></script><script src="../../js/bootstrap-dialog.js"></script><script>
$(function(){ $('#tblUser').DataTable({scrollX:true,"pageLength":10,"language":{"search":"Cari:","lengthMenu":"Tampilkan _MENU_ user","zeroRecords":"User tidak ditemukan","info":"Menampilkan _START_ - _END_ dari _TOTAL_ user","infoEmpty":"Tidak ada user","paginate":{"previous":"Sebelumnya","next":"Berikutnya"}}}); $('.btn-hapus').on('click',function(){var uid=$(this).data('uid');BootstrapDialog.confirm({title:'Hapus User',type:BootstrapDialog.TYPE_DANGER,message:'Hapus user <b>'+uid+'</b>?',btnCancelLabel:'Batal',btnOKLabel:'Hapus',callback:function(ok){if(!ok)return;$.post('pemakai_hapus.php',{uid:uid},function(r){BootstrapDialog.alert({title:'Hasil',message:r,type:r.indexOf('berhasil')>=0?BootstrapDialog.TYPE_SUCCESS:BootstrapDialog.TYPE_DANGER,callback:function(){location.reload();}});});}});}); });
</script></body></html>
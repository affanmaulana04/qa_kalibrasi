<?php
session_start();
if (empty($_SESSION['login'])) { header('location: ../../logout.php'); exit(); }
include '../../inc/inc_koneksi.php';
$id_pemakai=$_SESSION['userid'] ?? '';
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$sql="SELECT l.*, COALESCE(sp.kode_pemilik, s.kode_seksi, '-') AS seksi
      FROM qa_email_log l
      LEFT JOIN qa_part p ON p.id=l.alat_id
      LEFT JOIN qa_seksi_pemilik sp ON sp.id=p.seksi_pemilik_id
      LEFT JOIN qa_seksi s ON s.id=l.seksi_id
      ORDER BY l.tanggal_kirim DESC, l.id DESC";
$res=$konek->query($sql); $rows=[]; if($res) while($r=$res->fetch_assoc())$rows[]=$r;
?>
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Log Pengiriman Email QA</title><link rel="shortcut icon" href="../../images/hrd.ico"><link href="../../css/bootstrap.min.css" rel="stylesheet"><link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet">
<style>body{padding-top:70px;background:#f4f6f9}.card{background:#fff;border-radius:10px;padding:25px;box-shadow:0 3px 12px rgba(0,0,0,.08)}th{text-align:center;background:#1E3F66;color:#fff}td{vertical-align:middle!important}</style></head>
<body><nav class="navbar navbar-default navbar-fixed-top"><div class="container-fluid"><div class="navbar-header"><a class="navbar-brand" href="cek_alat.php"><img src="../../images/toto.png" style="height:25px"></a></div><ul class="nav navbar-nav navbar-left"><li><a href="cek_alat.php"><i class="fa fa-arrow-left"></i> Kembali</a></li></ul><ul class="nav navbar-nav navbar-right"><li><a href="#"><i class="fa fa-user"></i> <?php echo e($id_pemakai); ?></a></li></ul></div></nav>
<div class="container-fluid"><div class="card"><h3><i class="fa fa-envelope"></i> Log Pengiriman Email Jadwal QA</h3><div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>No</th><th>Email</th><th>Seksi</th><th>Jenis Email</th><th>Tanggal Jadwal</th><th>Periode</th><th>Tgl Kirim</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>
<?php if($rows):$no=1;foreach($rows as $r):?><tr><td class="text-center"><?php echo $no++;?></td><td><?php echo e($r['email']);?></td><td><?php echo e($r['seksi']);?></td><td class="text-center"><?php echo e($r['jenis_email']);?></td><td class="text-center"><?php echo e($r['tanggal_jadwal']?:'-');?></td><td class="text-center"><?php echo e(($r['periode_mulai']?:'-').' s.d. '.($r['periode_selesai']?:'-'));?></td><td class="text-center"><?php echo e($r['tanggal_kirim']?:'-');?></td><td class="text-center"><?php echo $r['status']==='sent'?'<span class="label label-success">Terkirim</span>':'<span class="label label-danger">Gagal</span>';?></td><td><?php echo e($r['keterangan']);?></td></tr><?php endforeach;else:?><tr><td colspan="9" class="text-center text-muted">Belum ada log email.</td></tr><?php endif;?></tbody></table></div></div></div></body></html>

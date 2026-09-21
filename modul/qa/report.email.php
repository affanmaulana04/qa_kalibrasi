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
<<<<<<< HEAD
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report Email PIC | HRD Online</title>

    <link rel="icon" type="image/png" href="../../images/LogoTOTO.png?v=<?= time(); ?>"/>
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet">

    <style>
        body { padding-top: 80px; background-color: #f4f6f9; }
        .card-container { background-color: #ffffff; border-radius: 12px; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); padding: 40px 30px; margin-top: 2vh; border-top: 5px solid #ff9800; }
        .table th { background-color: #1E3F66; color: white; text-align: center; }
        .table td { vertical-align: middle !important; text-align: center; }
    </style>
</head>
<body oncontextmenu="return false;">

    <nav class="navbar navbar-default navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header">
                <a class="navbar-brand" href="cek_alat.php"><img class="img-responsive" src="../../images/toto.png" style="height: 25px; margin-top: -3px;"></a>
            </div>
            <div class="collapse navbar-collapse">
                <ul class="nav navbar-nav navbar-left">
                    <li><a href="cek_alat.php"><i class="fa fa-arrow-left"></i> Kembali ke Form Cek Alat</a></li>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="#"><span style='color: #1469EA;'><i class="fa fa-fw fa-user"></i> <?php echo $id_pemakai; ?></span></a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid" style="padding: 0 30px;">
        <div class="row">
            <div class="col-md-10 col-md-offset-1"> 
                <div class="card-container">
                    <h3 style="font-weight: bold; color:#333; margin-bottom: 25px;"><i class="fa fa-envelope" style="color: #ff9800;"></i> Log Pengiriman Email Jadwal QA ke PIC</h3>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">NO</th>
                                    <th>NAMA PIC</th>
                                    <th>EMAIL</th>
                                    <th>SEKSI</th>
                                    <th>JUMLAH ALAT</th>
                                    <th>TGL KIRIM (SISTEM)</th>
                                    <th>STATUS EMAIL</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DUMMY DATA UNTUK SEMENTARA -->
                                <tr>
                                    <td>1</td>
                                    <td>Budi Santoso</td>
                                    <td>budi.santoso@toto.co.id</td>
                                    <td>WH</td>
                                    <td><span class="badge">3 Alat</span></td>
                                    <td>07-09-2026 08:00</td>
                                    <td><span class="label label-success"><i class="fa fa-check"></i> Terkirim</span></td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>Siti Aminah</td>
                                    <td>siti.aminah@toto.co.id</td>
                                    <td>QC</td>
                                    <td><span class="badge">1 Alat</span></td>
                                    <td>07-09-2026 08:00</td>
                                    <td><span class="label label-success"><i class="fa fa-check"></i> Terkirim</span></td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td>Joko Anwar</td>
                                    <td>joko.anwar@toto.co.id</td>
                                    <td>Maintenance</td>
                                    <td><span class="badge">5 Alat</span></td>
                                    <td>07-09-2026 08:00</td>
                                    <td><span class="label label-danger"><i class="fa fa-times"></i> Gagal (Bounced)</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted text-right"><small><em>*Tabel ini nantinya akan otomatis terhubung dengan cron job backend.</em></small></p>

                </div>
            </div>
        </div>
    </div>

    <script src="../../js/jquery.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
</body>
</html>

=======
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Log Pengiriman Email QA</title><link rel="shortcut icon" href="../../images/hrd.ico"><link href="../../css/bootstrap.min.css" rel="stylesheet"><link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet">
<style>body{padding-top:70px;background:#f4f6f9}.card{background:#fff;border-radius:10px;padding:25px;box-shadow:0 3px 12px rgba(0,0,0,.08)}th{text-align:center;background:#1E3F66;color:#fff}td{vertical-align:middle!important}</style></head>
<body><nav class="navbar navbar-default navbar-fixed-top"><div class="container-fluid"><div class="navbar-header"><a class="navbar-brand" href="cek_alat.php"><img src="../../images/toto.png" style="height:25px"></a></div><ul class="nav navbar-nav navbar-left"><li><a href="cek_alat.php"><i class="fa fa-arrow-left"></i> Kembali</a></li></ul><ul class="nav navbar-nav navbar-right"><li><a href="#"><i class="fa fa-user"></i> <?php echo e($id_pemakai); ?></a></li></ul></div></nav>
<div class="container-fluid"><div class="card"><h3><i class="fa fa-envelope"></i> Log Pengiriman Email Jadwal QA</h3><div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>No</th><th>Email</th><th>Seksi</th><th>Jenis Email</th><th>Tanggal Jadwal</th><th>Periode</th><th>Tgl Kirim</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>
<?php if($rows):$no=1;foreach($rows as $r):?><tr><td class="text-center"><?php echo $no++;?></td><td><?php echo e($r['email']);?></td><td><?php echo e($r['seksi']);?></td><td class="text-center"><?php echo e($r['jenis_email']);?></td><td class="text-center"><?php echo e($r['tanggal_jadwal']?:'-');?></td><td class="text-center"><?php echo e(($r['periode_mulai']?:'-').' s.d. '.($r['periode_selesai']?:'-'));?></td><td class="text-center"><?php echo e($r['tanggal_kirim']?:'-');?></td><td class="text-center"><?php echo $r['status']==='sent'?'<span class="label label-success">Terkirim</span>':'<span class="label label-danger">Gagal</span>';?></td><td><?php echo e($r['keterangan']);?></td></tr><?php endforeach;else:?><tr><td colspan="9" class="text-center text-muted">Belum ada log email.</td></tr><?php endif;?></tbody></table></div></div></div></body></html>
>>>>>>> origin/update-form-qa

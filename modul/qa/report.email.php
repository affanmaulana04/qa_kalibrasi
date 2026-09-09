<?php
session_start();
if ($_SESSION['login'] == 0) {
    header('location: ../../logout.php');
    exit();
}
$id_pemakai = $_SESSION['userid'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Report Email PIC | HRD Online</title>

    <link rel="shortcut icon" href="../../images/hrd.ico">
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
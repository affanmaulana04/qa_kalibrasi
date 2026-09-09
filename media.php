<?php
session_start();
// Cek apakah user sudah login
if (empty($_SESSION['login'])) {
    header('location: index.php');
    exit();
} 

include "inc/inc_koneksi.php";

// Ambil data session
$id_pemakai = isset($_SESSION['userid']) ? $_SESSION['userid'] : '';
$nama_pemakai = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$jam_login = isset($_SESSION['jamlogin']) ? $_SESSION['jamlogin'] : date('H:i:s');
$sapaan = isset($_SESSION['sapaan']) ? $_SESSION['sapaan'] : 'Bpk/Ibu';

// PENGECEKAN HAK AKSES ADMIN
$status_admin = isset($_SESSION['admin']) ? $_SESSION['admin'] : 'N';
$is_admin = ($status_admin == 'Y' || $status_admin == '1' || strtolower($role) == 'admin');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X_UA_Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QA System | Dashboard</title>
    <link rel="shortcut icon" href="images/hrd.ico">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/jquery.smartmenus.bootstrap.css" rel="stylesheet">
    <link href="font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    
    <style>
        .navbar-default { background-color: #ffffff; opacity: 1 !important; }
        .navbar-default .navbar-nav>li>a { color: #333333; font-weight: bold; }
        .navbar-default .navbar-nav>li>a:hover, .navbar-default .navbar-nav>li>a:focus { background-color: #E7E7E7; color: #333333; font-weight: bold; }
        .navbar-default .navbar-brand { color: #000000; }
        html, body { height: 100%; }
        body { padding-top: 50px; background-color: #f9f9f9; }
        
        header.carousel { height: 50%; }
        header.carousel .item, header.carousel .item.active, header.carousel .carousel-inner { height: 100%; }
        header.carousel .fill { width: 100%; height: 100%; background-position: center; background-size: cover; }
        
        footer { margin: 50px 0; }
        .section-heading { margin-top: 50px; margin-bottom: 20px; position: relative; padding-bottom: 15px; font-weight: bold; }
        .section-heading::after { content: ''; position: absolute; left: 50%; bottom: 0; transform: translateX(-50%); width: 75px; height: 4px; background-color: #333; border-radius: 2px; }
    
        .demografi-card-section .row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: stretch;
            padding-top: 40px;
            padding-bottom: 20px;
        }

        .demografi-card-section .col-sm-6.col-md-4 {
            display: flex;
            justify-content: center;
            align-items: stretch;
        }

        .demografi-card { 
            background-color: #1E3F66; 
            border-radius: 15px; 
            padding: 20px; 
            padding-top: 60px; 
            margin-bottom: 20px; 
            box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.3); 
            text-align: center; 
            height: 100%; 
            display: flex; 
            flex-direction: column; 
            color: #fff; 
            position: relative; 
            max-width: 400px; 
            width: 100%;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Transisi membal */
        }
        
        /* Efek Hover Zoom-In */
        .demografi-card:hover {
            transform: translateY(-10px);
            box-shadow: 5px 15px 25px rgba(0, 0, 0, 0.4);
        }

        .card-icon-container { position: absolute; top: -40px; left: 50%; transform: translateX(-50%); z-index: 10; }
        .icon-background { background-color: #fff; border-radius: 50%; padding: 15px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); display: flex; justify-content: center; align-items: center; width: 80px; height: 80px; }
        .icon-background img { width: 100%; max-width: 50px; height: auto; display: block; }
        .demografi-card h4 { margin-top: 0; color: #fff; font-size: 20px; font-weight: bold; margin-bottom: 10px; }
        .demografi-card p { color: #E0E0E0; font-size: 14px; line-height: 1.5; }
        .card-content { padding-top: 10px; }
        
        /* Fitur Box */
        .fitur-container { background-color: #fff; border-radius: 20px; box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15); padding: 30px; display: flex; flex-wrap: wrap; justify-content: space-between; }
        .fitur-card { flex: 1; padding: 0 20px; border-right: 1px solid #ddd; box-sizing: border-box; }
        .fitur-card:last-child { border-right: none; }
        .fitur-icon { width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; }
        .pink-bg { background: rgba(255, 99, 132, 0.1); }
        .blue-bg { background: rgba(54, 162, 235, 0.1); }
        .yellow-bg { background: rgba(255, 206, 86, 0.1); }
        .fitur-title { font-weight: 600; font-size: 18px; margin-bottom: 10px; text-align: center; }
        .fitur-desc { font-size: 14px; color: #555; text-align: center; }
        .view-more-link { display: block; margin-top: 15px; color: #000; font-weight: 500; text-decoration: none; text-align: center; }
        
        @media (max-width: 991.98px) { .fitur-card { border-right: none; margin-bottom: 20px; } }
    </style>
</head>

<body oncontextmenu="return false;">

    <nav class="navbar navbar-default navbar-fixed-top" role="navigation">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="media.php"><img class="img-responsive" src="images/toto.png"></a>
            </div>
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav navbar-left">
                    
                    <?php if ($is_admin) { ?>
                    <li><a href="#">Tabel</a>
                        <ul class="dropdown-menu">
                            <li><a href="modul/tabel/pemakai.php">Data Pemakai Aplikasi</a></li>
                        </ul>
                    </li>
					
                    <?php } ?>

                    <li><a href="#">QA (Kalibrasi Alat)</a>
                        <ul class="dropdown-menu">
                            <li><a href="modul/qa/cek_alat.php">Check / Update QA Alat</a></li>
                            <li><a href="modul/qa/daftar_kalibrasi.php">Daftar Kalibrasi Terdekat</a></li>
                        </ul>
                    </li>
                    
                    <?php if ($is_admin) { ?>
                    <li><a href="#">Alat Bantu</a>
                        <ul class="dropdown-menu">
                            <li><a href="modul/tabel/resetpwd_user.php">Reset Password</a></li>
                        </ul>
                    </li>
                    <?php } ?>

                </ul>
                
                <ul class="nav navbar-nav navbar-right">
                    <li class="mega-menu">
                        <a href="#"><span style="color: #1469EA; font-weight: bold;"><i class="fa fa-user"></i> <?php echo $id_pemakai; ?></span></a>
                        <ul class="dropdown-menu mega-menu">
                            <div class="container">
                                <div class="row">
                                    <li style='padding-left:10px;'>Id Pemakai : <span style='color: #009856;'><?php echo $id_pemakai; ?></span></li>
                                    <li style='padding-left:10px;'>Nama Pemakai : <span style='color: #1469EA;'><?php echo $nama_pemakai; ?></span></li>
                                    <li style='padding-left:10px;'>Mulai Login : <span style='color: red;'><?php echo $jam_login; ?></span> WIB</li>
                                </div>
                            </div>
                        </ul>
                    </li>
                    <li>
                        <a href="logout.php" onclick="return confirm('Apakah Anda Yakin Ingin Keluar?')">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header id="myCarousel" class="carousel slide">
        <ol class="carousel-indicators">
            <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
            <li data-target="#myCarousel" data-slide-to="1"></li>
            <li data-target="#myCarousel" data-slide-to="2"></li>
            <li data-target="#myCarousel" data-slide-to="3"></li>
            <li data-target="#myCarousel" data-slide-to="4"></li>
        </ol>

        <div class="carousel-inner">
            <div class="item active"><div class="fill" style="background-image:url('images/gambar_layar_utama/1.jpg');"></div></div>
            <div class="item"><div class="fill" style="background-image:url('images/gambar_layar_utama/2.jpg');"></div></div>
            <div class="item"><div class="fill" style="background-image:url('images/gambar_layar_utama/3.jpg');"></div></div>
            <div class="item"><div class="fill" style="background-image:url('images/gambar_layar_utama/4.jpg');"></div></div>
            <div class="item"><div class="fill" style="background-image:url('images/gambar_layar_utama/5.jpg');"></div></div>
        </div>
        <a class="left carousel-control" href="#myCarousel" data-slide="prev"><span class="icon-prev"></span></a>
        <a class="right carousel-control" href="#myCarousel" data-slide="next"><span class="icon-next"></span></a>
    </header>

    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h3 class="page-header" id="judul" style="margin-top: 30px;">
                    Selamat datang di Sistem QA Kalibrasi Online, <?php echo $sapaan . ' ' . $nama_pemakai; ?>
                </h3>
            </div>
            
            <div class="col-lg-12">
                <div class="fitur-container">
                    <div class="fitur-card">
                        <div class="fitur-icon pink-bg">
                            <img src="images/icons/profile.png" alt="Profile Icon" style="height: 120px;">
                        </div>
                        <h5 class="fitur-title">Profil</h5>
                        <p class="fitur-desc">Informasi seputar identitas perusahaan, peran, visi dan misi, serta prinsip budaya kerja yang diterapkan.</p>
                    </div>
                    <div class="fitur-card">
                        <div class="fitur-icon blue-bg">
                            <img src="images/icons/struktur.png" alt="Structure Icon" style="height: 160px;">
                        </div>
                        <h5 class="fitur-title">Struktur</h5>
                        <p class="fitur-desc">Menampilkan susunan jabatan dan pembagian tanggung jawab dalam organisasi untuk mendukung efektivitas.</p>
                    </div>
                    <div class="fitur-card">
                        <div class="fitur-icon yellow-bg">
                            <img src="images/icons/karir.png" alt="Carrier Icon" style="height: 130px;">
                        </div>
                        <h5 class="fitur-title">Karir</h5>
                        <p class="fitur-desc">Informasi seputar peluang karir, posisi yang tersedia, dan proses rekrutmen bagi talenta yang ingin berkembang.</p>
                    </div>
                </div>
            </div>

            <div class="container demografi-card-section">
                <div class="col-lg-12">
                    <h3 class="text-center section-heading">Demografi Organisasi</h3>
                </div>
                <div class="row d-flex justify-content-center align-items-stretch">
                    <div class="col-sm-6 col-md-4">
                        <div class="demografi-card">
                            <div class="card-icon-container">
                                <div class="icon-background"> <img src="images/icons/karyawan.png" alt="Organization Icon"></div>
                            </div>
                            <div class="card-content">
                                <h4>Data Karyawan</h4>
                                <p>Ringkasan karakteristik karyawan seperti usia, jenis kelamin, jabatan, departemen, dan pendidikan.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="demografi-card">
                            <div class="card-icon-container">
                                <div class="icon-background"> <img src="images/icons/pelatihan.png" alt="Presentation Icon"></div>
                            </div>
                            <div class="card-content">
                                <h4>Data Pelatihan</h4>
                                <p>Ringkasan peserta pelatihan berdasarkan kategori, membantu mengevaluasi efektivitas program.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer>
                <div class="row">
                    <div class="col-lg-12 text-center">
                        <br><br>
                        <p>QA System Online created by <span style='color: blue; font-weight:bold;'>PT. SURYA TOTO INDONESIA</span> &copy; <?php echo date('Y'); ?></p>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.smartmenus.min.js"></script>
    <script src="js/jquery.smartmenus.bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#myCarousel').carousel({ interval: 5000 });
        });
    </script>
</body>
</html>
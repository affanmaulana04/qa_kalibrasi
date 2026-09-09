<?php
date_default_timezone_set('Asia/Jakarta');
// panggil fungsi validasi xss dan injection
require_once('fungsi_validasi.php');

$server = "localhost"; //
$database = "qa_kalibrasi";
$user = "root";
$pass = "";

$konek = mysqli_connect($server, $user, $pass ,$database) or die ("Gagal konek ke server MySQL" .mysqli_error($konek));

// buat variabel untuk validasi dari file fungsi_validasi.php
$val = new Lokovalidasi;
?>
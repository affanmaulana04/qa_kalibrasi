<?php
date_default_timezone_set('Asia/Jakarta');
// panggil fungsi validasi xss dan injection
require_once('fungsi_validasi.php');

$server = "localhost"; //
$database = "pko_migrasi";
$konek_migrasi = mysqli_connect($server, $server, base64_decode('cm9vdA=='), base64_decode('eGFtcHBtYXJpYWRi'),$database) or die ("Gagal konek ke server MySQL" .mysqli_error($connect));

// buat variabel untuk validasi dari file fungsi_validasi.php
$val = new Lokovalidasi;
?>
<?php
session_start();
if(!isset($_SESSION['login']) || $_SESSION['login'] == 0){
    echo "Sesi berakhir, silakan login kembali";
    exit;
}

include "../../inc/inc_koneksi.php";
$uid = isset($_POST['xprn']) ? mysqli_real_escape_string($konek, $_POST['xprn']) : "";

if(empty($uid)){
    echo "ID tidak ditemukan. Pastikan data sudah dipilih.";
    exit;
}

if($uid == 'admin'){
    echo "Id karyawan admin tidak bisa dihapus";
} else {
    // Query hapus
    $q1 = mysqli_query($konek, "DELETE FROM data_karyawan WHERE prn='$uid'");
    $q2 = mysqli_query($konek, "DELETE FROM user WHERE uid='$uid'");
    $q3 = mysqli_query($konek, "DELETE FROM user_menu WHERE uid='$uid'");

    if($q1){
        echo "Data NIK: ".$uid." telah berhasil dihapus";
    } else {
        echo "Gagal menghapus data: " . mysqli_error($konek);
    }
}
?>
<?php
  session_start();
  $id_pemakai = $_SESSION[$userid];
  include "inc/inc_koneksi.php";
  mysqli_query($konek,"UPDATE user SET online='N' WHERE uid='$id_pemakai'");
  session_destroy();
  echo "<script>alert('Anda Telah Keluar Dari Sistem!'); window.location = 'index.php' </script>";
?>
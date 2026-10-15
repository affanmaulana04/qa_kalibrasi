<?php
  session_start();
  $id_pemakai = $_SESSION[$userid];
  include "inc/inc_koneksi.php";
  mysqli_query($konek,"UPDATE user SET online='N' WHERE uid='$id_pemakai'");
  session_destroy();
  header('Location: index.php');
  exit();
?>
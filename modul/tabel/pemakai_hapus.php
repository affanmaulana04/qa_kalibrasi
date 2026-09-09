<?php
session_start();
if($_SESSION['login']==0){
	header('location: ../../logout.php');
}
include "../../inc/inc_koneksi.php";

$uid=trim(str_replace("'","\'",$_POST['uid']));
if($uid=='admin'){
	echo "Id pemakai admin tidak bisa dihapus";
}else{
	mysqli_query($konek,"DELETE FROM user WHERE uid='$uid'");
	mysqli_query($konek,"DELETE FROM user_menu WHERE uid='$uid'");
	echo "Data ".$uid." telah berhasil dihapus";
}
?>
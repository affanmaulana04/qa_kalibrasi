<?php
function sukses_masuk($userid,$name){
	//header('location:media.php?module=home');
	
	
	header('location:media.php?module=home');
	// Apabila username dan password ditemukan
/*	$login=mysql_query("SELECT * FROM admins WHERE username='$username' AND password='$pass' AND blokir='N'");
	$ketemu=mysql_num_rows($login);
	$r=mysql_fetch_array($login);
	if ($ketemu > 0){
		session_start();
		include "timeout.php";
	
		$_SESSION[namauser]     = $r[username];
		$_SESSION[namalengkap]  = $r[namalengkap];
		$_SESSION[passuser]     = $r[password];
		$_SESSION[leveluser]    = $r[level];
		$_SESSION[seksiuser]    = $r[nama_seksi];
		$_SESSION[tbltmpjkb] = 'jkbtmp_'.$r[username];  //temporary file laporan Jadwal Kalibrasi Bulanan STIS-CMF-73-10
		
		//akses menu dan submenu
		$_SESSION[file_badankali] = $r[file_badankali];
		$_SESSION[file_sie_pemilik_alat] = $r[file_sie_pemilik_alat];
		$_SESSION[file_jenis_check] = $r[file_jenis_check];
		$_SESSION[file_hasil_check] = $r[file_hasil_check];		
		$_SESSION[file_pemakai] = $r[file_pemakai];
		$_SESSION[file_kelompok] = $r[file_kelompok];
		
		
		// session timeout 
		$_SESSION[login] = 1;
		timer();
		
		$ipaddress = empty($_SERVER['HTTP_CLIENT_IP'])?(empty($_SERVER['HTTP_X_FORWARDED_FOR'])? $_SERVER['REMOTE_ADDR']:$_SERVER['HTTP_X_FORWARDED_FOR']):$_SERVER['HTTP_CLIENT_IP'];
	
		$sql	= "UPDATE admins SET lastlogin=now(),ipaddress='$ipaddress'  WHERE username='$username' AND password='$pass'";
		mysql_query($sql);
	
		//hapus tabel jkbtmp_
		$tabeluser= 'jkbtmp_'.$r[username];
		mysql_query("DROP TABLE $tabeluser");
		header('location:media.php?module=home');
	}
	*/
//	echo "<script>alert('".$userid." - ".$name."');</script>";
	return false;
}
?>
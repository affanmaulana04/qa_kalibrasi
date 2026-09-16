<?php	
session_start();
	include "../../inc/inc_koneksi.php";
	$seksi_user=$_SESSION['seksi_user'];
	$xno_baris=$_GET['prmfile'];
	$prmfile=$_GET['prmfile'];
	
	 //$files = glob("../../images/gambar_layar_utama/$prmfile");
	 
	 $dir = '../../images/gambar_layar_utama/$prmfile'; // Replace with the actual directory path
		$files = glob($dir . '$prmfile');

		if ($files !== false) {
			foreach ($files as $file) {
				if (is_file($file)) {
					if (unlink($file)) {
						echo "Deleted: " . $file . "<br>";
					} else {
						echo "Failed to delete: " . $file . "<br>";
					}
				}
			}
		} else {
			echo "No files found in the directory.";
		}
	$alamat_folder='../../images/gambar_layar_utama/';
	
	if (!file_exists($alamat_folder)) {
		mkdir($alamat_folder, 0777, true);
	}
		

set_time_limit(0);
ini_set('upload_max_filesize', 2000);
	
	foreach ($_FILES['upload']['name'] as $key => $name){
		
		$isi="$name";
			$nama_part=substr($isi, 0,7);//0=nomor antrian di judul, 10=panjang antrian yg mau diambil
			$seksi_dituju=substr($isi, 8,3);
			
			
			$nama_baru=$xno_baris;
			
		$newFilename = $nama_baru;
		
		$lokasi_folder='../../images/gambar_layar_utama/';
		
		move_uploaded_file($_FILES['upload']['tmp_name'][$key], $lokasi_folder . $newFilename);
		$location = $newFilename;
		
	}
ini_set('upload_max_filesize', 2);
clearstatcache();
?>

<html>
<head>
</head>	
<body>
	<div style="height:50px;"></div>
	<div style="margin:auto; padding:auto; width:80%;">
		<span style="font-size:25px; color:blue"><center><strong>Upload File Gambar</strong></center></span>
		<br>
		<center><?php echo"Telah Selesai";?></center>
		<hr>
	</div>
	<center>
	<?php
		echo '<a href="javascript:window.close();">Tutup Halaman</a>';		
	?>
	</center>
</body>
</html>	


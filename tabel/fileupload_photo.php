<?php
$prmfile=$_GET['xno_baris'].'.jpg';
$jdl='Gambar layar utama slide ke-'.$_GET['xno_baris'].'.jpg';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<title>Upload <?php echo $jdl;?></title>
</head>
<body oncontextmenu="return false;" style="padding: 20px 20px 20px 20px;">
	<p><?php echo $jdl;?></p>
	<p style="color: green;">*Size 1875 x 420 px</p>
	<form method="POST" action="upload.php?prmfile=<?php echo $prmfile; ?>" method="post" enctype="multipart/form-data">
		<input type="file" name="upload[]">
		<input type="submit" value="Upload"> 
	</form>
</script>
</body>
</html>



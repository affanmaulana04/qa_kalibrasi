<?php

$prn = isset($_GET['xprn']) ? $_GET['xprn'] : '';
$prmfile = $prn . '.jpg';
$jdl = 'Foto untuk karyawan dengan NIK: ' . $prn;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <title>Upload <?php echo $jdl; ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body style="padding: 20px;">
    <p><strong><?php echo $jdl; ?></strong></p>

    <form id="formUpload" method="POST" action="upload_foto_karyawan.php?prmfile=<?php echo $prmfile; ?>&prn=<?php echo $prn; ?>" enctype="multipart/form-data">
        <input type="file" name="upload[]" id="fileInput">
        <input type="submit" value="Upload"> 
    </form>

    <script>
        $(document).ready(function() {
            $('#formUpload').on('submit', function(e) {
                var fileInput = $('#fileInput');
                if (fileInput.get(0).files.length === 0) {
                    e.preventDefault();
                    alert("Peringatan: Silakan pilih foto terlebih dahulu!");
                }
            });
        });
    </script>
</body>
</html>
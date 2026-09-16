<?php
	session_start();
	if($_SESSION['login']==0){
		header('location: ../../logout.php');
	}else{
		if($_SESSION['tabel_karyawan']=='Y'){
			include "../../inc/inc_koneksi.php";		
			$id_pemakai=$_SESSION['userid'];
			$nama_pemakai=$_SESSION['username'];
			$jam_login=$_SESSION['jamlogin'];
		}else{
			header('location: ../../logout.php');
		}
	}
	clearstatcache();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>HRD Online</title>
	<link rel="shortcut icon" href="../../images/hrd.ico">
	<link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
	<link href="../../css/jquery.dataTables.css" rel="stylesheet">
	<link href="../../css/dataTables.responsive.css" rel="stylesheet">
	<link href="../../css/bootstrap-dialog.css" rel="stylesheet">
	<link href="../../css/jquery-ui.css" rel="stylesheet">
	<link href="../../css/jquery.smartmenus.bootstrap.css" rel="stylesheet" >
	<link href="../../css/bootstrap-datetimepicker.min.css" rel="stylesheet" >
	<link href="../../css/magnific-popup.css" rel="stylesheet">	
	<style>

		#jsontable tbody tr.inactive, #jsontable tbody tr.inactive td.sorting_1 {
			background-color: #fccfcf
		}
		#jsontable tbody tr:hover, #jsontable tbody tr:hover td.sorting_1 {
			background-color: #e2ebff;
			cursor: pointer
		}		
		
		.navbar-default .navbar-nav > li > a:hover, .navbar-default .navbar-nav > li > a:focus {
			background-color: #E7E7E7;
			color: #333333; /*7F7F7F;*/
		}
		.bootstrap-dialog .modal-header.bootstrap-dialog-draggable{
			cursor: move;
		}
		html,body {height: 100%;}		
		body {padding-top: 50px;}			
		@media(max-width:991px) {
			.customer-img,
			.img-related {margin-bottom: 30px;}
		}
		@media(max-width:767px) {
			.img-portfolio {margin-bottom: 15px;}
		}
		footer {margin: 50px 0;}
		tfoot input {
			width: 100%;
			padding: 0px;
			margin: 0px;
			box-sizing: border-box;
		}
		.page-header {
		  padding-bottom: 9px;
		  margin: 20px 0 20px;
		  border-bottom: 1px solid #eee;
		  font-size: 150%;
		}
	</style>
</head>

<body oncontextmenu="return false;">

	<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
        <div class="container">
            <!-- logo dan toggle display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
				<a class="navbar-brand" href="../../media.php"><img class="img-responsive" src="../../images/toto.png"></a>
            </div>
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
				<ul class="nav navbar-nav navbar-left">
					<li><a href="../../media.php">Menu Utama</a></li>			
                </ul>
				<ul class="nav navbar-nav navbar-right">
					<li class="mega-menu"><a href="#"><span style='color: #1469EA;'><i class="fa fa-fw fa-user"></i></span><?php echo $id_pemakai; ?></a>
						<ul class="dropdown-menu mega-menu">
							<div class="container">
								<div class="row">
									<li style='padding-left:10px;'>Id Pemakai : <span style='color: #009856;'><?php echo $id_pemakai; ?></span></li>
									<li style='padding-left:10px;'>Nama Pemakai : <span style='color: #1469EA;'><?php echo $nama_pemakai; ?></span></li>
									<li style='padding-left:10px;'>Mulai Login : <span style='color: red;'><?php echo $jam_login; ?></span> WIB</li>
								</div>
							</div>
						</ul>						
                    </li>
					<li><a href="../../logout.php">Logout</a></li>				
                </ul>
				
            </div>
            <!-- /.navbar-collapse -->
        </div>
        <!-- /.container -->
    </nav>

    <!-- Page Content -->
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h3 class="page-header" id='judul'>Gambar Layar Utama</h3>
            </div>

        </div>
	    <!-- /.row -->

        <div class="row">
			<br>
            <div class="col-md-12" >
				<div class="col-12" >
					<table id="jsontable" class="display table table-bordered" cellspacing="0" width="100%">
						<thead>
							<tr>
								<th class="text-center">No</th><th class="text-center">Status</th><th class="text-center">Lokasi Gambar</th><th class="text-center">Preview</th><th class="text-center">Aksi</th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<th class="text-center">No</th><th class="text-center">Status</th><th class="text-center">Lokasi Gambar</th><th class="text-center">Preview</th><th class="text-center">Aksi</th>
							</tr>
						</tfoot>
						<tbody>
							<?php
							define('IMG_DIR', 'images/gambar_layar_utama/');
							define('IMG_ROOT', __DIR__ . '../../images/gambar_layar_utama/');

							include "../../inc/inc_koneksi.php";
							include "../../inc/fungsi_tanggal.php";

							$query = mysqli_query($konek, "SELECT * FROM gambar_layar_utama ORDER BY no_baris");

							while ($fetch = mysqli_fetch_array($query)) {
								$file     = $fetch['lokasi_gambar'];
								$fullPath = "../../" . $file; 
								$isExist = (!empty($file) && file_exists($fullPath));
								
								// Tentukan label status
								$statusLabel = $isExist 
									? "<span class='label label-success'>Aktif</span>" 
									: "<span class='label label-danger'>Tidak ada</span>";

								echo "<tr>";
								echo "<td class='text-center'>{$fetch['no_baris']}</td>";
								echo "<td class='text-center'>{$statusLabel}</td>";
								echo "<td>{$file}</td>";
								echo "<td class='text-center'>";
								
								if ($isExist) {
									echo "<img src='{$fullPath}?t=" . time() . "' width='80'>";
								} else {
									echo "-";
								}
								
								echo "</td>";
								echo "<input type='hidden' class='xno_baris_row' value='".$fetch['no_baris']."'>";
								echo "<td class='text-center'>
								<button class='btn btn-info btn-sm btn-cek-foto'> Lihat</button>
								<button class='btn btn-success btn-sm btn-upload-foto'> Upload</button>
								</td>";
								echo "</tr>";
							}
							?>
						</tbody>
					</table>
				</div>
            </div>
        </div>					
		<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
		<input type="hidden" name="xno_baris"  id="xno_baris" />
		<input type="hidden" name="xid_login"  id="xid_login" value='<?php echo $id_pemakai; ?>' />
		
    </div>
    <!-- /.container -->
	
	<script src="../../js/jquery.js"></script>
	<script src="../../js/moment.js"></script>
	<script src="../../js/transition.js"></script> <!--dari bootstrap u./bootstrap-datetimepicker-->
	<script src="../../js/collapse.js"></script>   <!--di bootstrap u./bootstrap-datetimepicker-->
	
    <script src="../../js/bootstrap.min.js"></script>
	<script src="../../js/bootstrap-datetimepicker.min.js"></script>
	
	<script src="../../js/jquery.dataTables.js"></script>
	<script src="../../js/dataTables.responsive.js"></script>
	<script src="../../js/bootstrap-dialog.js"></script>
	<script src="../../js/jquery-ui.js"></script>
	<script src="../../js/jquery.smartmenus.min.js" defer></script>
	<script src="../../js/jquery.smartmenus.bootstrap.min.js" defer></script>
	<script src="../../js/jquery.magnific-popup.js"></script>
    <script>
		$(document).ready( function () {
			$('.btn-upload-foto').on('click', function(event) {
				var row = $(this).closest('tr');
				var xno_baris = $.trim(row.find('.xno_baris_row').val());

				if(xno_baris.length == 0){
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Upload: ',
						message: 'Record belum dipilih atau baris tidak valid'
					});
				} else {          
					window.open("fileupload_photo.php?xno_baris=" + xno_baris, '_blank', 'width=500,height=400');
				}                                               
			});	

			$('.btn-cek-foto').on('click', function(event) {
				var row = $(this).closest('tr');
				var xno_baris=$.trim(row.find('.xno_baris_row').val());
				if (xno_baris.length == 0) {
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Tampilan Lengkap: ',
						message: 'Record belum dipilih'
					});
				}else{		
					window.open("../../images/gambar_layar_utama/"+ xno_baris + '.jpg', "_blank", false);
				}												
			});	

			$('#jsontable tfoot th').each( function () {
				$(this).html( '<input type="text"/>' );
			});
						
			var table_arah = $('#jsontable').DataTable({
				scrollX: true,
				"aLengthMenu": [[5, 10, 25, 50, 75, -1], [5, 10, 25, 50, 75, "All"]],
				"iDisplayLength": 5,				
				"language": {
					   "sLengthMenu": "Per _MENU_ records",
					   "sSearch": "Cari:",
			           "sInfo": "Menampilkan _START_ s.d. _END_ dari _TOTAL_ records",
					   "sInfoEmpty": "Menampilkan 0 s.d. 0 dari 0 records",
					   "sInfoFiltered": "(dibaca dari _MAX_ total records)",
					   "sZeroRecords": "Tidak ada record yang ditemukan",
					   "sEmptyTable": "Tidak ada data pada tabel",
					   "paginate": {
							"sPrevious": "Sebelumnya",
							"sNext": "Berikutnya"
				        }
			        },
				"columns": [
					{ "width": "5%" },{ "width": "10%" },{ "width": "35%" },{ "width": "20%" },{"width": "20%"}
				]
			});
			
			table_arah.columns().eq( 0 ).each( function ( colIdx ) {
				$( 'input', table_arah.column( colIdx ).footer() ).on( 'keyup change', function () {
					table_arah
					.column( colIdx )
					.search( this.value )
					.draw();
				});
			});
			
			function escapepetik1(title) {
				title = title.replace(/'/g, "'")
				title = escape(title)
				return title
			}
		});		
		window.onfocus = function() {
			location.reload(); 
		};
		
    </script>
</body>

</html>

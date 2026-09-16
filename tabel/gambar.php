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
	<link rel="shortcut icon" href="../../images/icon.png">
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

    <!-- Navigation -->
	<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
        <div class="container">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
				<a class="navbar-brand" href="../../media.php"><img class="img-responsive" src="../../images/toto.png"></a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
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
            <div class="col-md-12">
				<div class="col-md-12">
					<table id="jsontable" class="display table table-bordered" cellspacing="0" width="100%">
						<thead>
							<tr>
								<th>No.Baris Gambar</th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<th>No.Baris Gambar</th>
							</tr>
						</tfoot>
						<tbody>
							<?php
								include "../../inc/inc_koneksi.php";
								include "../../inc/fungsi_tanggal.php";
								$query = mysqli_query($konek,"select * from gambar_layar_utama order by no_baris");
								while($fetch = mysqli_fetch_array($query)){
									echo "<tr>
										<td>".$fetch['no_baris'] ."</td>
									</tr>";						
								}
							?>
						</tbody>
					</table>
				</div>
            </div>
        </div>					
		<br>

		<button id='hapus' type="button" class="btn btn-danger"><i class="fa fa-fw fa-trash-o"></i> Hapus</button>
		<button id='uploadfoto' type="button" class="btn btn-success"><i class="fa fa-fw fa-upload"></i> Upload</button>
		<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
		
		<input type="hidden" name="xno_baris"  id="xno_baris" />
		<input type="hidden" name="xid_login"  id="xid_login" value='<?php echo $id_pemakai; ?>' />
		
    </div>	
	<script src="../../js/jquery.js"></script>
	<script src="../../js/moment.js"></script>
	<script src="../../js/transition.js"></script> <!--from bootstrap u./bootstrap-datetimepicker-->
	<script src="../../js/collapse.js"></script>   <!--in bootstrap u./bootstrap-datetimepicker-->
	
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
			$('#uploadfoto').on('click', function(event) {
				var xno_baris=$.trim($('#xno_baris').val());
				if(xno_baris.length==0){
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Upload: ',
						message: 'Record belum dipilih'
					});
				}else{
					var xfolder='../../images/foto/';
					var xid_pemakai=$.trim($('#xid_login').val());
					var xjudul='Karyawan';
					var xnamafile=$.trim($('#xno_baris').val());				
					window.open("fileupload_photo.php?xno_baris="+xno_baris,'_blank',false);
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
					{ "width": "100%" }
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
			
			$('#jsontable tbody').on( 'click', 'tr', function () {
				if ( $(this).hasClass('selected') ) {
					$(this).removeClass('selected');
					$('#xno_baris').val('');
					$('#foto_kyw').attr('src', '../../images/foto/blank.jpg');
				} else {
					table_arah.$('tr.selected').removeClass('selected');
					$(this).addClass('selected');
					var row1 = table_arah.row( this ).data();
					$('#xno_baris').val(row1[0]);
				}
			});
			
			$('#updatex').on('click', function(event) {
				window.open("../upload_data/upload_master_data.php",'_blank',false);
			});
			
			$('#downloadkyw').on('click', function(event) {
				window.open("../listlapor/karyawan_download.php",'_self',false);
			});
			
			$('#refresh_data').on('click', function(event) {
				window.setTimeout( function(){location.reload();}, 10 );
			});
			
			$('#tambah').on('click', function(event) {
				window.open("karyawan_tambah.php",'_self',false);
			});
			
			$('#edit').on('click', function(event) {
				var xpmast_nik=$.trim($('#xpmast_nik').val());
				var xpmast_name=$.trim($('#xpmast_name').val());
				if(xpmast_nik.length==0){
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Edit: ',
						message: 'Record belum dipilih'
					});
				}else{
					var xpmast_name = escapepetik1(xpmast_name);
					window.open("karyawan_edit.php?xpmast_nik="+xpmast_nik+"&xpmast_name="+xpmast_name,'_self',false);	
				}
			});
			function escapepetik1(title) {
				title = title.replace(/'/g, "'")
				title = escape(title)
				return title
			}
			
			$('#hapus').on('click', function(event) {
				var xpmast_nik=$.trim($('#xpmast_nik').val());
				var xpmast_name=$.trim($('#xpmast_name').val());
				
				if(xpmast_nik.length==0){
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Hapus: ',
						message: 'Record belum dipilih'
					});
				}else{
					BootstrapDialog.confirm({
						title: 'Konfirmasi Hapus',
						message: 'Data NIK <span style="color: red;">'+xpmast_nik +'</span><br> <span style="color: red;">'+xpmast_name +'</span><br>akan dihapus ?',
						type: BootstrapDialog.TYPE_DANGER, // <-- Default value is BootstrapDialog.TYPE_PRIMARY TYPE_WARNING
						closable: false, // <-- Default value is false
						draggable: true, // <-- Default value is false
						btnCancelLabel: 'Tidak', // <-- Default value is 'Cancel',
						btnOKLabel: 'Hapus', // <-- Default value is 'OK',
						//btnOKClass: 'btn-warning', // <-- If you didn't specify it, dialog type will be used,
						callback: function(result) {
							// result will be true if button was click, while it will be false if users close the dialog directly.
							if(result) {
								$.ajax({
									type	: "POST",
									url		: "karyawan_hapus.php",
									data	: "xpmast_name="+$('#xpmast_name').val()+"&xpmast_nik="+$('#xpmast_nik').val(),		  
									success	: function(data){
										if(data=='Ada transaksi'){
											BootstrapDialog.show({
												type: BootstrapDialog.TYPE_WARNING,
												title: 'Konfirmasi Hapus: ',
												message: 'Data masih dipakai pada transaksi pesanan'
											});
										}else{
											BootstrapDialog.show({
												type: BootstrapDialog.TYPE_INFO,
												title: 'Konfirmasi Hapus: ',
												message: data
											});
											window.setTimeout( function(){location.reload();}, 1500 );
										}
									}
								});
							}else {
								//alert('Nope.');
							}
						}
					});						
				}
			});
			
			$('#setmanager').on('click', function(event) {
				var xpmast_nik=$.trim($('#xpmast_nik').val());
				if(xpmast_nik.length==0){
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Set Validasi: ',
						message: 'Record belum dipilih'
					});
				}else{
					window.open("karyawan_setmanager.php?pmast_nik="+xpmast_nik,'_self',false);	
				}
			});
		});		
    </script>
</body>

</html>

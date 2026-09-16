<?php
	session_start();
	if($_SESSION['login']==0){
		header('location: ../../logout.php');
	}else{
		if($_SESSION['tabel_referensi']=='Y'){
			include "../../inc/inc_koneksi.php";
			$id_pemakai=$_SESSION['userid'];
			$nama_pemakai=$_SESSION['username'];
			$jam_login=$_SESSION['jamlogin'];
		}else{
			header('location: ../../logout.php');
		}
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Referensi Email</title>
	<link rel="shortcut icon" href="../../images/hrd.ico">
	<link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
	<link href="../../css/jquery.dataTables.css" rel="stylesheet">
	<link href="../../css/dataTables.responsive.css" rel="stylesheet">
	<link href="../../css/bootstrap-dialog.css" rel="stylesheet">
	<link href="../../css/jquery-ui.css" rel="stylesheet">
<link href="../../css/jquery.smartmenus.bootstrap.css" rel="stylesheet" >
<link href="../../css/bootstrap-datetimepicker.min.css" rel="stylesheet" >
	
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
        <div class="col-lg-12">
			<div class="col-lg-6">
				<div class="row">
					<div class="col-lg-12">
						<h3 style="color : green;" class="page-header" id='judul_infosistem'>Referensi - Info Sistem</h3>
					</div>
				</div>
				<div class="row">
					<br>
					<div class="col-md-12">
						<label class='checkbox-inline'><input type='checkbox' name='info_sistem' id='info_sistem' value="Y" disabled>Tampilkan Info Sistem</label>
						<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
						<button id='edit_infosistem' type="button" class="btn btn-primary"><i class="fa fa-fw fa-pencil"></i> Edit</button>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<h3 class="page-header" id='judul_merek'>Referensi - Email</h3>
					</div>
				</div>
				<!-- /.row -->
				<div class="row">
					<br>
					<div class="col-md-12">
						<table id="jsontable_merek" class="display table table-bordered" cellspacing="0" width="100%">
							<thead>
								<tr>
									<th>Email</th>
								</tr>
							</thead>
							<tbody>
								<?php
									include "../../inc/inc_koneksi.php";
									$query = mysqli_query($konek,"select * from data_karyawan order by prn");
									while($fetch = mysqli_fetch_array($query)){
										echo "<tr>
											<td>".$fetch['email'] ."</td>
										</tr>";						
									}
								?>
							</tbody>
						</table>		
					</div>
				</div>					
				<br>
				<button id='tambah_merek' type="button" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Tambah</button>
				<button id='hapus_merek' type="button" class="btn btn-primary"><i class="fa fa-fw fa-trash-o"></i> Hapus</button>
				<input type="hidden" name="xmerek"  id="xmerek" />
				
				<br>
				<br>
				<div class="row">
					<div class="col-lg-12">
						<h3 style="color : green;" class="page-header" id='judul_kepemilikan'>Referensi - Kepemilikan</h3>
					</div>
				</div>
				<div class="row">
					<br>
					<div class="col-md-12">
						<table id="jsontable_kepemilikan" class="display table table-bordered" cellspacing="0" width="100%">
							<thead>
								<tr>
									<th>Kepemilikan</th>
								</tr>
							</thead>
							<tbody>
								<?php
									include "../../inc/inc_koneksi.php";
									$query_kepemilikan = mysqli_query($konek,"select * from asset order by kepemilikan");
									while($fetch_kepemilikan = mysqli_fetch_array($query_kepemilikan)){
										echo "<tr>
											<td>".$fetch_kepemilikan['kepemilikan'] ."</td>
										</tr>";						
									}
								?>
							</tbody>
						</table>		
					</div>
				</div>
				<br>
				<button id='tambah_kepemilikan' type="button" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Tambah</button>
				<button id='hapus_kepemilikan' type="button" class="btn btn-primary"><i class="fa fa-fw fa-trash-o"></i> Hapus</button>
				<input type="hidden" name="xkepemilikan"  id="xkepemilikan" />
				
			</div>
			<div class="col-lg-6">
				<div class="row">
					<div class="col-lg-12">
						<h3 class="page-header" id='judul_kelompok'>Referensi - Kelompok</h3>
					</div>
				</div>
				<div class="row">
					<br>
					<div class="col-md-12">
						<table id="jsontable_kelompok" class="display table table-bordered" cellspacing="0" width="100%">
							<thead>
								<tr>
									<th>Kelompok</th>
								</tr>
							</thead>
							<tbody>
								<?php
									include "../../inc/inc_koneksi.php";
									$query_kelompok = mysqli_query($konek,"select * from kelompok order by grup");
									while($fetch_kelompok = mysqli_fetch_array($query_kelompok)){
										echo "<tr>
											<td>".$fetch_kelompok['grup'] ."</td>
										</tr>";						
									}
								?>
							</tbody>
						</table>		
					</div>
				</div>					
				<br>
				<button id='tambah_kelompok' type="button" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Tambah</button>
				<button id='hapus_kelompok' type="button" class="btn btn-primary"><i class="fa fa-fw fa-trash-o"></i> Hapus</button>
				<input type="hidden" name="xkelompok"  id="xkelompok" />
				<br>
				<br>
				<div class="row">
					<div class="col-lg-12">
						<h3 style="color : green;" class="page-header" id='judul_jeniskendaraan'>Referensi - Jenis Kendaraan</h3>
					</div>
				</div>
				<div class="row">
					<br>
					<div class="col-md-12">
						<table id="jsontable_jeniskendaraan" class="display table table-bordered" cellspacing="0" width="100%">
							<thead>
								<tr>
									<th>Jenis Kendaraan</th><th>Perlu Validasi Keberangkatan?</th>
								</tr>
							</thead>
							<tbody>
								<?php
									include "../../inc/inc_koneksi.php";
									$query_jeniskendaraan = mysqli_query($konek,"select * from jenis_kendaraan order by jenis");
									while($fetch_jeniskendaraan = mysqli_fetch_array($query_jeniskendaraan)){
										echo "<tr>
											<td>".$fetch_jeniskendaraan['jenis'] ."</td>
											<td>".$fetch_jeniskendaraan['perlu_vld_berangkat'] ."</td>
										</tr>";						
									}
								?>
							</tbody>
						</table>		
					</div>
				</div>
				<br>
				<button id='tambah_jeniskendaraan' type="button" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Tambah</button>
				<button id='hapus_jeniskendaraan' type="button" class="btn btn-primary"><i class="fa fa-fw fa-trash-o"></i> Hapus</button>
				<input type="hidden" name="xjeniskendaraan"  id="xjeniskendaraan" />
				<br>
				<br>
				<div class="row">
					<div class="col-lg-12">
						<h3 style="color : black;" class="page-header" id='judul_userldap'>Referensi - user LDAP</h3>
					</div>
				</div>
				<div class="row">
					<br>
					<div class="col-md-12">
						<table id="jsontable_userldap" class="display table table-bordered" cellspacing="0" width="100%">
							<thead>
								<tr>
									<th>Id</th><th>Nama</th><th>LDAP</th>
								</tr>
							</thead>
							<tbody>
								<?php
									include "../../inc/inc_koneksi.php";
									$query_userldap = mysqli_query($konek,"select * from user where uid<>'admin' order by uid");
									while($fetch_userldap = mysqli_fetch_array($query_userldap)){
										echo "<tr>
											<td>".$fetch_userldap['uid'] ."</td>
											<td>".$fetch_userldap['uname'] ."</td>
											<td>".$fetch_userldap['ldap'] ."</td>
										</tr>";						
									}
								?>
							</tbody>
						</table>		
					</div>
				</div>
				<br>
				<button id='tambah_userldap' type="button" class="btn btn-primary"><i class="fa fa-fw fa-check-square-o"></i> Pasang LDAP</button>
				<button id='hapus_userldap' type="button" class="btn btn-primary"><i class="fa fa-fw fa-square-o"></i> Lepas LDAP</button>
				<input type="hidden" name="xuserldap"  id="xuserldap" />
				<br>
				<br>
			</div>
		</div>
		<input type="hidden" name="xid_login"  id="xid_login" value='<?php echo $id_pemakai; ?>' />
		
    </div>
    <!-- /.container -->
	
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
	
    <script>
		$(document).ready( function () {
			bacarefer();
			function bacarefer(){
				$.ajax({
					type	: "POST",
					url		: "baca_refer.php",
					dataType : "json",				  
					success	: function(data){
						if(data.info_sistem=='Y'){
							$('#info_sistem').prop('checked',true);
						}else{
							$('#info_sistem').prop('checked',false);
						}
					}
				});
			}
			
			$('#edit_infosistem').on('click', function(event) {
				window.open("refer_infosistem_edit.php",'_self',false);
			});
			
			var tabel_merek = $('#jsontable_merek').DataTable({
				scrollX: true,
				//responsive: true,
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
			
			tabel_merek.columns().eq( 0 ).each( function ( colIdx ) {
				$( 'input', tabel_merek.column( colIdx ).footer() ).on( 'keyup change', function () {
					tabel_merek
					.column( colIdx )
					.search( this.value )
					.draw();
				});
			});
			
			$('#jsontable_merek tbody').on( 'click', 'tr', function () {
				if ( $(this).hasClass('selected') ) {
					$(this).removeClass('selected');
					$('#xmerek').val('');
				} else {
					tabel_merek.$('tr.selected').removeClass('selected');
					$(this).addClass('selected');
					var row1 = tabel_merek.row( this ).data();
					$('#xmerek').val(HtmlDecode(row1[0]));
				}
			});
			
			function HtmlDecode(html) {
				var div = document.createElement("div");
				div.innerHTML = html;
				return div.childNodes[0].nodeValue;
			}
			
			$('#tambah_merek').on('click', function(event) {
				window.open("refer_merek_tambah.php",'_self',false);
			});			
			
			$('#hapus_merek').on('click', function(event) {
				var xmerek=encodeURIComponent($.trim($('#xmerek').val()));
				if(xmerek.length==0){
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Hapus: ',
						message: 'Record belum dipilih'
					});
				}else{
					BootstrapDialog.confirm({
						title: 'Konfirmasi Hapus',
						message: 'Data '+xmerek +' akan dihapus ?',
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
									url		: "refer_merek_hapus.php",
									data	: "merek="+xmerek,		  
									success	: function(data){
										//location.reload();
										if(data=='Merek telah berhasil dihapus'){
											BootstrapDialog.show({
												type: BootstrapDialog.TYPE_SUCCESS,
												title: 'Konfirmasi Hapus: ',
												message: data
											});
										}else{
											BootstrapDialog.show({
												type: BootstrapDialog.TYPE_WARNING,
												title: 'Konfirmasi Hapus: ',
												message: data
											});
										}
										window.setTimeout( function(){location.reload();}, 2000 );
									}
								});
							}else {
								//alert('Nope.');
							}
						}
					});						
				}
			});
			//=========================================================KELOMPOK
			var tabel_kelompok = $('#jsontable_kelompok').DataTable({
				scrollX: true,
				//responsive: true,
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
			
			tabel_kelompok.columns().eq( 0 ).each( function ( colIdx ) {
				$( 'input', tabel_kelompok.column( colIdx ).footer() ).on( 'keyup change', function () {
					tabel_kelompok
					.column( colIdx )
					.search( this.value )
					.draw();
				});
			});
			
			$('#jsontable_kelompok tbody').on( 'click', 'tr', function () {
				if ( $(this).hasClass('selected') ) {
					$(this).removeClass('selected');
					$('#xkelompok').val('');
				} else {
					tabel_kelompok.$('tr.selected').removeClass('selected');
					$(this).addClass('selected');
					var row1 = tabel_kelompok.row( this ).data();
					$('#xkelompok').val(HtmlDecode(row1[0]));
				}
			});
			$('#tambah_kelompok').on('click', function(event) {
				window.open("refer_kelompok_tambah.php",'_self',false);
			});			
			
			$('#hapus_kelompok').on('click', function(event) {
				var xkelompok=encodeURIComponent($.trim($('#xkelompok').val()));
				if(xkelompok.length==0){
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Hapus: ',
						message: 'Record belum dipilih'
					});
				}else{
					BootstrapDialog.confirm({
						title: 'Konfirmasi Hapus',
						message: 'Data '+xkelompok +' akan dihapus ?',
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
									url		: "refer_kelompok_hapus.php",
									data	: "kelompok="+xkelompok,		  
									success	: function(data){
										//location.reload();
										if(data=='Kelompok telah berhasil dihapus'){
											BootstrapDialog.show({
												type: BootstrapDialog.TYPE_SUCCESS,
												title: 'Konfirmasi Hapus: ',
												message: data
											});
										}else{
											BootstrapDialog.show({
												type: BootstrapDialog.TYPE_WARNING,
												title: 'Konfirmasi Hapus: ',
												message: data
											});
										}
										window.setTimeout( function(){location.reload();}, 2000 );
									}
								});
							}else {
								//alert('Nope.');
							}
						}
					});						
				}
			});
			//=========================================================KEPEMILIKAN
			var tabel_kepemilikan = $('#jsontable_kepemilikan').DataTable({
				scrollX: true,
				//responsive: true,
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
			
			tabel_kepemilikan.columns().eq( 0 ).each( function ( colIdx ) {
				$( 'input', tabel_kepemilikan.column( colIdx ).footer() ).on( 'keyup change', function () {
					tabel_kepemilikan
					.column( colIdx )
					.search( this.value )
					.draw();
				});
			});
			
			$('#jsontable_kepemilikan tbody').on( 'click', 'tr', function () {
				if ( $(this).hasClass('selected') ) {
					$(this).removeClass('selected');
					$('#xkepemilikan').val('');
				} else {
					tabel_kepemilikan.$('tr.selected').removeClass('selected');
					$(this).addClass('selected');
					var row1 = tabel_kepemilikan.row( this ).data();
					$('#xkepemilikan').val(HtmlDecode(row1[0]));
				}
			});
			$('#tambah_kepemilikan').on('click', function(event) {
				window.open("refer_kepemilikan_tambah.php",'_self',false);
			});			
			
			$('#hapus_kepemilikan').on('click', function(event) {
				var xkepemilikan=encodeURIComponent($.trim($('#xkepemilikan').val()));
				if(xkepemilikan.length==0){
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Hapus: ',
						message: 'Record belum dipilih'
					});
				}else{
					BootstrapDialog.confirm({
						title: 'Konfirmasi Hapus',
						message: 'Data '+xkepemilikan +' akan dihapus ?',
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
									url		: "refer_kepemilikan_hapus.php",
									data	: "kepemilikan="+xkepemilikan,		  
									success	: function(data){
										//location.reload();
										if(data=='Kepemilikan telah berhasil dihapus'){
											BootstrapDialog.show({
												type: BootstrapDialog.TYPE_SUCCESS,
												title: 'Konfirmasi Hapus: ',
												message: data
											});
										}else{
											BootstrapDialog.show({
												type: BootstrapDialog.TYPE_WARNING,
												title: 'Konfirmasi Hapus: ',
												message: data
											});
										}
										window.setTimeout( function(){location.reload();}, 2000 );
									}
								});
							}else {
								//alert('Nope.');
							}
						}
					});						
				}
			});
			//=========================================================JENISKENDARAAN
			var tabel_jeniskendaraan = $('#jsontable_jeniskendaraan').DataTable({
				scrollX: true,
				//responsive: true,
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
					{ "width": "40%" },{ "width": "60%" }
				]
			});
			
			tabel_jeniskendaraan.columns().eq( 0 ).each( function ( colIdx ) {
				$( 'input', tabel_jeniskendaraan.column( colIdx ).footer() ).on( 'keyup change', function () {
					tabel_jeniskendaraan
					.column( colIdx )
					.search( this.value )
					.draw();
				});
			});
			
			$('#jsontable_jeniskendaraan tbody').on( 'click', 'tr', function () {
				if ( $(this).hasClass('selected') ) {
					$(this).removeClass('selected');
					$('#xjeniskendaraan').val('');
				} else {
					tabel_jeniskendaraan.$('tr.selected').removeClass('selected');
					$(this).addClass('selected');
					var row1 = tabel_jeniskendaraan.row( this ).data();
					$('#xjeniskendaraan').val(HtmlDecode(row1[0]));
				}
			});
			$('#tambah_jeniskendaraan').on('click', function(event) {
				window.open("refer_jeniskendaraan_tambah.php",'_self',false);
			});			
			
			$('#hapus_jeniskendaraan').on('click', function(event) {
				var xjeniskendaraan=encodeURIComponent($.trim($('#xjeniskendaraan').val()));
				if(xjeniskendaraan.length==0){
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Hapus: ',
						message: 'Record belum dipilih'
					});
				}else{
					BootstrapDialog.confirm({
						title: 'Konfirmasi Hapus',
						message: 'Data '+xjeniskendaraan +' akan dihapus ?',
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
									url		: "refer_jeniskendaraan_hapus.php",
									data	: "jenis="+xjeniskendaraan,		  
									success	: function(data){
										//location.reload();
										if(data=='Kepemilikan telah berhasil dihapus'){
											BootstrapDialog.show({
												type: BootstrapDialog.TYPE_SUCCESS,
												title: 'Konfirmasi Hapus: ',
												message: data
											});
										}else{
											BootstrapDialog.show({
												type: BootstrapDialog.TYPE_WARNING,
												title: 'Konfirmasi Hapus: ',
												message: data
											});
										}
										window.setTimeout( function(){location.reload();}, 2000 );
									}
								});
							}else {
								//alert('Nope.');
							}
						}
					});						
				}
			});
			//=========================================================LDAP
			var tabel_userldap = $('#jsontable_userldap').DataTable({
				scrollX: true,
				//responsive: true,
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
					{ "width": "30%" },{ "width": "50%" },{ "width": "20%" }
				]
			});
			
			tabel_userldap.columns().eq( 0 ).each( function ( colIdx ) {
				$( 'input', tabel_userldap.column( colIdx ).footer() ).on( 'keyup change', function () {
					tabel_userldap
					.column( colIdx )
					.search( this.value )
					.draw();
				});
			});
			
			$('#jsontable_userldap tbody').on( 'click', 'tr', function () {
				if ( $(this).hasClass('selected') ) {
					$(this).removeClass('selected');
					$('#xuserldap').val('');				
				} else {
					tabel_userldap.$('tr.selected').removeClass('selected');
					$(this).addClass('selected');
					var row1 = tabel_userldap.row( this ).data();
					$('#xuserldap').val(HtmlDecode(row1[0]));
				}
			});
			$('#tambah_userldap').on('click', function(event) {
				var xuserldap=encodeURIComponent($.trim($('#xuserldap').val()));
				$.ajax({
					type	: "POST",
					url		: "refer_userldap_set.php",
					data	: "xuid="+xuserldap+"&status=tambah",		  
					success	: function(data){
						BootstrapDialog.show({
							type: BootstrapDialog.TYPE_WARNING,
							title: 'Konfirmasi Set LDAP: ',
							message: data
						});
						window.setTimeout( function(){location.reload();}, 2000 );
					}
				});				
			});			
			
			$('#hapus_userldap').on('click', function(event) {
				var xuserldap=encodeURIComponent($.trim($('#xuserldap').val()));
				$.ajax({
					type	: "POST",
					url		: "refer_userldap_set.php",
					data	: "xuid="+xuserldap+"&status=hapus",		  
					success	: function(data){
						BootstrapDialog.show({
							type: BootstrapDialog.TYPE_WARNING,
							title: 'Konfirmasi Set LDAP: ',
							message: data
						});
						window.setTimeout( function(){location.reload();}, 2000 );
					}
				});		
			});
		});		
    </script>
</body>

</html>

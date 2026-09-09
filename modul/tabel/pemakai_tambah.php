<?php
	session_start();
	if($_SESSION['login']==0){
		header('location: ../../logout.php');
	}else{
		include "../../inc/inc_koneksi.php";
		$id_pemakai=$_SESSION['userid'];
		$nama_pemakai=$_SESSION['username'];
		$jam_login=$_SESSION['jamlogin'];
	}
	include "../../inc/inc_koneksi.php";
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
                <h3 class="page-header" id='judul'>
                    Pemakai Aplikasi
                </h3>
				<ol class="breadcrumb">
                    <li><a href="../../media.php">Menu Utama</a></li>
					<li><a href="pemakai.php">Tabel Pemakai Aplikasi</a></li>
					<li class="active">Tambah Data</li>
                </ol>				
            </div>
			<div class="col-lg-12">
				<form id='my-form' class="form-horizontal" role="form">
					<div class="col-lg-12">
						<div class="col-lg-5">
							<!--<br>-->
							<div class="form-group">
								<label for="input_uid" class="col-sm-2 control-label">ID</label>
								<div class="col-sm-10">
									<input type="text" class="form-control" name="input_uid" id="input_uid" maxlength='20'>
								</div>
							</div>
							<div class="form-group">
								<label for="input_uname" class="col-sm-2 control-label">Nama</label>
								<div class="col-sm-10">
									<input type="text" class="form-control" name="input_uname" id="input_uname" maxlength='30'>
								</div>
							</div>
							<div class="form-group">
								<label for="input_upwd" class="col-sm-2 control-label">Password</label>
								<div class="col-sm-10">
									<input type="password" class="form-control" name="input_upwd" id="input_upwd" maxlength='35' placeholder='Maksimal 35 karakter'>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-2 control-label">Status</label>
								<div class="col-sm-10">
									<label class='checkbox-inline'><input type='checkbox' name='input_admin' id='input_admin' value="Y">Admin</label>
									<label class='checkbox-inline'><input type='checkbox' name='input_hrd' id='input_hrd' value="Y">HRD</label>
									<label class='checkbox-inline'><input type='checkbox' name='input_manager' id='input_manager' value="Y">Manager</label>
									<label class='checkbox-inline'><input type='checkbox' name='input_supervisor' id='input_supervisor' value="Y">Supervisor</label>
									<label class='checkbox-inline'><input type='checkbox' name='input_user' id='input_user' value='Y'>Indirect/User</label>
								</div>
							</div>
							<div class="form-group">
								<label for="input_sapaan" class="col-sm-2 control-label">Sapaan</label>
								<div class="col-sm-10">
									<select class="form-control" name='input_sapaan' id="input_sapaan">
										<option value=''></option>
										<option value='Bpk.'>Bpk.</option>
										<option value='Ibu'>Ibu</option>
										<option value='Mr.'>Mr.</option>
										<option value='Mrs.'>Mrs.</option>
										<option value='Miss'>Miss</option>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label for="input_seksi" class="col-sm-2 control-label">Seksi</label>
								<div class="col-sm-10">
									<select class="form-control" name='input_seksi' id="input_seksi">
										<option value=''></option>
										<?php
											//include "../../inc/inc_koneksi.php";
											//include "../../inc/fungsi_tanggal.php";
											$query = mysqli_query($konek,"select seksi from data_karyawan group by seksi order by seksi");
											while($fetch = mysqli_fetch_array($query)){
												$psectnm=$fetch['seksi'];
												echo "<option value='$psectnm'>$psectnm</option>";				
											}
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label for="input_email" class="col-sm-2 control-label">e-Mail</label>
								<div class="col-sm-10">
									<input type="text" class="form-control" name="input_email" id="input_email" maxlength='50'>
								</div>
							</div>
							<div class="form-group">
								<br>
								<div class="col-sm-offset-2 col-sm-10">
									<button id='simpan' type="button" class="btn btn-primary">Simpan</button>
									<button id='batal' type="button" class="btn btn-default">Kembali</button>
									<!--<button id='import_indirect' type="button" class="btn btn-link">Import Indirect</button>-->
									<input type="hidden" name="xloginid"  id="xloginid" value='<?php echo $id_pemakai; ?>' />
									<input type="hidden" name="status_proses"  id="status_proses" value='tambah' />
								</div>
							</div>
						</div>
						<div class="col-lg-7">
							<div class="panel-group" id="accordion">
								<div class="panel panel-default">
									<div class="panel-heading">
										<h4 class="panel-title">
											<a data-toggle="collapse" data-parent="#accordion" href="#collapse1">Tabel</a>
										</h4>
									</div>
									<!--<div id="collapse1" class="panel-collapse collapse in">-->
									<div id="collapse1" class="panel-collapse collapse">
										<div class="panel-body">
											<div class="col-lg-12">
												<label class="checkbox"><input type="checkbox" name='tbl_glu' id='tbl_glu' value="Y">Gambar Layar Utama</label>
												<label class="checkbox"><input type="checkbox" name='tbl_karyawan' id='tbl_karyawan' value="Y">Karyawan</label>
												<label class="checkbox"><input type="checkbox" name='tbl_pemakai_aplikasi' id='tbl_pemakai_aplikasi' value="Y">Pemakai Aplikasi</label>
											</div>
										</div>
									</div>
								</div>
								<div class="panel panel-default">
									<div class="panel-heading">
										<h4 class="panel-title">
											<a data-toggle="collapse" data-parent="#accordion" href="#collapse3">Transaksi</a>
										</h4>
									</div>
									<div id="collapse3" class="panel-collapse collapse">
										<div class="panel-body">
											<div class="col-lg-12">
												<div class="col-lg-4">Transaksi - Data Karyawan</div>
												<div class="col-lg-8">
													<label class="checkbox"><input type="checkbox" name='tran_data_karyawan' id='tran_data_karyawan' value="Y">Data Karyawan</label>
												</div>
											</div>
											<div class="col-lg-12">
												<div class="col-lg-4">Transaksi - Mutasi Karyawan</div>
												<div class="col-lg-8">
													<label class="checkbox"><input type="checkbox" name='tran_data_mutasi' id='tran_data_mutasi' value="Y">Mutasi Karyawan - regist</label>
													<label class="checkbox"><input type="checkbox" name='tran_val_mutasi_mng_asal' id='tran_val_mutasi_mng_asal' value="Y">Mutasi Karyawan - Validasi Manager (Asal)</label>
													<label class="checkbox"><input type="checkbox" name='tran_val_mutasi_mng_tujuan' id='tran_val_mutasi_mng_tujuan' value="Y">Mutasi Karyawan - Validasi Manager (Tujuan)</label>
													<label class="checkbox"><input type="checkbox" name='tran_val_mutasi_sv_asal' id='tran_val_mutasi_sv_asal' value="Y">Mutasi Karyawan - Validasi Supervisor (Asal)</label>
													<label class="checkbox"><input type="checkbox" name='tran_val_mutasi_sv_tujuan' id='tran_val_mutasi_sv_tujuan' value="Y">Mutasi Karyawan - Validasi Supervisor (Tujuan)</label>
													<label class="checkbox"><input type="checkbox" name='tran_val_mutasi_hrd' id='tran_val_mutasi_hrd' value="Y">Mutasi Karyawan - Validasi HRD</label>
												</div>
											</div>
											<div class="col-lg-12">
												<div class="col-lg-4">Transaksi - Rotasi Karyawan</div>
												<div class="col-lg-8">
													<label class="checkbox"><input type="checkbox" name='tran_data_rotasi' id='tran_data_rotasi' value="Y">Rotasi Karyawan - regist</label>
													<label class="checkbox"><input type="checkbox" name='tran_val_rotasi_mng' id='tran_val_rotasi_mng' value="Y">Rotasi Karyawan - Validasi Manager</label>
													<label class="checkbox"><input type="checkbox" name='tran_val_rotasi_sv_asal' id='tran_val_rotasi_sv_asal' value="Y">Rotasi Karyawan - Validasi Supervisor (Asal)</label>
													<label class="checkbox"><input type="checkbox" name='tran_val_rotasi_sv_tujuan' id='tran_val_rotasi_sv_tujuan' value="Y">Rotasi Karyawan - Validasi Supervisor (Tujuan)</label>
													<label class="checkbox"><input type="checkbox" name='tran_val_rotasi_hrd' id='tran_val_rotasi_hrd' value="Y">Rotasi Karyawan - Validasi HRD</label>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>				
						</div>									
					</div>
				</form>
			</div>
        </div>
	    <!-- /.row -->				
		<br>
		<br>
		
    </div>
    <!-- /.container -->

    <script src="../../js/jquery.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
	<script src="../../js/jquery.dataTables.js"></script>
	<script src="../../js/dataTables.responsive.js"></script>
	<script src="../../js/bootstrap-dialog.js"></script>
	<script src="../../js/jquery-ui.js"></script>
	<script src="../../js/jquery.smartmenus.min.js" defer></script>
	<script src="../../js/jquery.smartmenus.bootstrap.min.js" defer></script>
    <script>
		$(document).ready( function () {
			$("#tbl_glu").prop('checked',false);
			$("#tbl_karyawan").prop('checked',false);
			$("#tbl_pemakai_aplikasi").prop('checked',false);
			
			$("#tran_data_karyawan").prop('checked',false);
			
			$("#tran_data_mutasi").prop('checked',false);
			$("#tran_val_mutasi_mng_asal").prop('checked',false);
			$("#tran_val_mutasi_mng_tujuan").prop('checked',false);
			$("#tran_val_mutasi_sv_asal").prop('checked',false);
			$("#tran_val_mutasi_sv_tujuan").prop('checked',false);
			$("#tran_val_mutasi_hrd").prop('checked',false);
			
			$("#tran_data_rotasi").prop('checked',false);
			$("#tran_val_rotasi_mng").prop('checked',false);
			$("#tran_val_rotasi_sv_asal").prop('checked',false);
			$("#tran_val_rotasi_sv_tujuan").prop('checked',false);
			$("#tran_val_rotasi_hrd").prop('checked',false);
						
			$('#input_uid').focus();
			
			$('#input_admin').change(function() {
				if($(this).is(":checked")) {
					$("#tbl_glu").prop('checked',true);
					$("#tbl_karyawan").prop('checked',true);
					$("#tbl_pemakai_aplikasi").prop('checked',true);
										
					$("#tran_data_karyawan").prop('checked',true);
			
					$("#tran_data_mutasi").prop('checked',true);
					$("#tran_val_mutasi_mng_asal").prop('checked',true);
					$("#tran_val_mutasi_mng_tujuan").prop('checked',true);
					$("#tran_val_mutasi_sv_asal").prop('checked',true);
					$("#tran_val_mutasi_sv_tujuan").prop('checked',true);
					$("#tran_val_mutasi_hrd").prop('checked',true);
					
					$("#tran_data_rotasi").prop('checked',true);
					$("#tran_val_rotasi_mng").prop('checked',true);
					$("#tran_val_rotasi_sv_asal").prop('checked',true);
					$("#tran_val_rotasi_sv_tujuan").prop('checked',true);
					$("#tran_val_rotasi_hrd").prop('checked',true);

					$("#input_hrd").prop('checked',false);
					$("#input_manager").prop('checked',false);
					$("#input_supervisor").prop('checked',false);
					$("#input_user").prop('checked',false);
				}       
			});		
			$('#input_hrd').change(function() {
				if($(this).is(":checked")) {
					$("#tbl_glu").prop('checked',true);
					$("#tbl_karyawan").prop('checked',true);
					$("#tbl_pemakai_aplikasi").prop('checked',true);
					
					$("#tran_data_karyawan").prop('checked',true);
			
					$("#tran_data_mutasi").prop('checked',true);
					$("#tran_val_mutasi_mng_asal").prop('checked',false);
					$("#tran_val_mutasi_mng_tujuan").prop('checked',false);
					$("#tran_val_mutasi_sv_asal").prop('checked',false);
					$("#tran_val_mutasi_sv_tujuan").prop('checked',false);
					$("#tran_val_mutasi_hrd").prop('checked',true);
					
					$("#tran_data_rotasi").prop('checked',true);
					$("#tran_val_rotasi_mng").prop('checked',false);
					$("#tran_val_rotasi_sv_asal").prop('checked',false);
					$("#tran_val_rotasi_sv_tujuan").prop('checked',false);
					$("#tran_val_rotasi_hrd").prop('checked',true);

					$("#input_admin").prop('checked',false);
					$("#input_manager").prop('checked',false);
					$("#input_supervisor").prop('checked',false);
					$("#input_user").prop('checked',false);	
				}       
			});
			$('#input_manager').change(function() {
				if($(this).is(":checked")) {
					$("#tbl_glu").prop('checked',false);
					$("#tbl_karyawan").prop('checked',false);
					$("#tbl_pemakai_aplikasi").prop('checked',false);
					
					$("#tran_data_karyawan").prop('checked',true);
			
					$("#tran_data_mutasi").prop('checked',false);
					$("#tran_val_mutasi_mng_asal").prop('checked',true);
					$("#tran_val_mutasi_mng_tujuan").prop('checked',true);
					$("#tran_val_mutasi_sv_asal").prop('checked',false);
					$("#tran_val_mutasi_sv_tujuan").prop('checked',false);
					$("#tran_val_mutasi_hrd").prop('checked',false);
					
					$("#tran_data_rotasi").prop('checked',false);
					$("#tran_val_rotasi_mng").prop('checked',true);
					$("#tran_val_rotasi_sv_asal").prop('checked',false);
					$("#tran_val_rotasi_sv_tujuan").prop('checked',false);
					$("#tran_val_rotasi_hrd").prop('checked',false);

					$("#input_hrd").prop('checked',false);
					$("#input_admin").prop('checked',false);
					$("#input_supervisor").prop('checked',false);
					$("#input_user").prop('checked',false);	
				}       
			});
			$('#input_supervisor').change(function() {
				if($(this).is(":checked")) {
					$("#tbl_glu").prop('checked',false);
					$("#tbl_karyawan").prop('checked',false);
					$("#tbl_pemakai_aplikasi").prop('checked',false);
					
					$("#tran_data_karyawan").prop('checked',false);
			
					$("#tran_data_mutasi").prop('checked',true);
					$("#tran_val_mutasi_mng_asal").prop('checked',false);
					$("#tran_val_mutasi_mng_tujuan").prop('checked',false);
					$("#tran_val_mutasi_sv_asal").prop('checked',true);
					$("#tran_val_mutasi_sv_tujuan").prop('checked',true);
					$("#tran_val_mutasi_hrd").prop('checked',false);
					
					$("#tran_data_rotasi").prop('checked',true);
					$("#tran_val_rotasi_mng").prop('checked',false);
					$("#tran_val_rotasi_sv_asal").prop('checked',true);
					$("#tran_val_rotasi_sv_tujuan").prop('checked',true);
					$("#tran_val_rotasi_hrd").prop('checked',false);

					$("#input_hrd").prop('checked',false);
					$("#input_manager").prop('checked',false);
					$("#input_admin").prop('checked',false);
					$("#input_user").prop('checked',false);	
				}      
			});
			$('#input_user').change(function() {
				if($(this).is(":checked")) {
					$("#tbl_glu").prop('checked',false);
					$("#tbl_karyawan").prop('checked',false);
					$("#tbl_pemakai_aplikasi").prop('checked',false);
					
					$("#tran_data_karyawan").prop('checked',false);
			
					$("#tran_data_mutasi").prop('checked',true);
					$("#tran_val_mutasi_mng_asal").prop('checked',false);
					$("#tran_val_mutasi_mng_tujuan").prop('checked',false);
					$("#tran_val_mutasi_sv_asal").prop('checked',false);
					$("#tran_val_mutasi_sv_tujuan").prop('checked',false);
					$("#tran_val_mutasi_hrd").prop('checked',false);
					
					$("#tran_data_rotasi").prop('checked',true);
					$("#tran_val_rotasi_mng").prop('checked',false);
					$("#tran_val_rotasi_sv_asal").prop('checked',false);
					$("#tran_val_rotasi_sv_tujuan").prop('checked',false);
					$("#tran_val_rotasi_hrd").prop('checked',false);

					$("#input_hrd").prop('checked',false);
					$("#input_manager").prop('checked',false);
					$("#input_supervisor").prop('checked',false);
					$("#input_admin").prop('checked',false);	
				}       
			});
			
			
			$('#input_sapaan').change(function() {
				$("#input_seksi").focus();
			});
			$('#input_seksi').change(function() {
				$("#input_email").focus();
			});
			
			$('#batal').on('click', function(event) {
				window.history.back();
			});

			$('#input_uid').keydown(function(e) {if (e.keyCode == 13) {e.preventDefault();if($('#input_ldap').prop('checked')) {$('#input_sapaan').focus();}else{$('#input_uname').focus();}}});
			$('#input_uname').keydown(function(e) {if (e.keyCode == 13) {e.preventDefault();$('#input_upwd').focus();}});
			$('#input_upwd').keydown(function(e) {if (e.keyCode == 13) {e.preventDefault();$('#input_sapaan').focus();}});
			
			$('#simpan').on('click', function(event) {
				var input_uid=$.trim($('#input_uid').val());
				var input_uname=$.trim($('#input_uname').val());
				var input_upwd=$.trim($('#input_upwd').val());
				var input_sapaan=$.trim($('#input_sapaan').val());
				var input_seksi=$.trim($('#input_seksi').val());
				var input_email=$.trim($('#input_email').val());
				
				if(input_uid.length==0){
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Simpan: ',
						message: 'ID Pemakai tidak boleh kosong',
						buttons: [{
							label: 'OK',
							action: function(dialogRef){
								dialogRef.close();
								$("#input_uid").focus();
							}
						}]
					});
					return false;
				}			
				
				if($('#input_ldap').is(":checked")) {
					var input_ldap = "Y";
				}else{
					var input_ldap = "N";
					if(input_uname.length==0){
						BootstrapDialog.show({
							type: BootstrapDialog.TYPE_WARNING,
							title: 'Konfirmasi Simpan: ',
							message: 'Nama Pemakai tidak boleh kosong',
							buttons: [{
								label: 'OK',
								action: function(dialogRef){
									dialogRef.close();
									$("#input_uname").focus();
								}
							}]
						});
						return false;												
					}
					if(input_upwd.length==0){
						BootstrapDialog.show({
							type: BootstrapDialog.TYPE_WARNING,
							title: 'Konfirmasi Simpan: ',
							message: 'Password tidak boleh kosong',
							buttons: [{
								label: 'OK',
								action: function(dialogRef){
									dialogRef.close();
									$("#input_upwd").focus();
								}
							}]
						});
						return false;
					}
				}
				if(input_sapaan.length==0){
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Simpan: ',
						message: 'Sapaan tidak boleh kosong',
						buttons: [{
							label: 'OK',
							action: function(dialogRef){
								dialogRef.close();
								$("#input_sapaan").focus();
							}
						}]
					});
					return false;
				}

				if(input_seksi.length==0){
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Simpan: ',
						message: 'Seksi tidak boleh kosong',
						buttons: [{
							label: 'OK',
							action: function(dialogRef){
								dialogRef.close();
								$("#input_seksi").focus();
							}
						}]
					});
					return false;
				}
				
				if(input_email.length==0){
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Simpan: ',
						message: 'E-mail tidak boleh kosong',
						buttons: [{
							label: 'OK',
							action: function(dialogRef){
								dialogRef.close();
								$("#input_email").focus();
							}
						}]
					});
					return false;
				}

				var string = $("#my-form").serialize();
				
				$.ajax({
					type	: "POST",
					url		: "pemakai_simpan.php",  
					data	: string,
					success	: function(data){
						if(data=='Id Pemakai sudah ada'){
							BootstrapDialog.show({
								type: BootstrapDialog.TYPE_WARNING,
								title: 'Konfirmasi Simpan: ',
								message: data,
								buttons: [{
									label: 'OK',
									action: function(dialogRef){
										dialogRef.close();
										$("#input_uid").focus();
									}
								}]
							});						
						}else{
							BootstrapDialog.show({
								type: BootstrapDialog.TYPE_INFO,
								title: 'Konfirmasi Tambah: ',
								message: data
							});
							window.setTimeout( function(){window.open("pemakai.php",'_self',false);}, 2000 );
						}
					}
				});				
			});			
		});		
    </script>
</body>

</html>

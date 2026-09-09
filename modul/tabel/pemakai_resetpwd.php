<?php
	session_start();
	if($_SESSION['login']==0){
		header('location: ../../logout.php');
	}else{
		include "../../inc/inc_koneksi.php";
		$id_pemakai=$_SESSION['userid'];
		$nama_pemakai=$_SESSION['username'];
		$jam_login=$_SESSION['jamlogin'];
		$xuid=$_GET['xuid'];				
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
                <h2 class="page-header" id='judul'>
                    Reset Password
                </h2>
				<ol class="breadcrumb">
                    <li><a href="../../media.php">Menu Utama</a></li>
					<li><a href="pemakai.php">Tabel Pemakai Aplikasi</a></li>
					<li class="active">Reset Password</li>
                </ol>				
            </div>
			<div class="col-lg-12">
				<form id='my-form' class="form-horizontal" role="form">
					<div class="col-lg-12">
						<div class="col-lg-6">
							<br>
							<div class="form-group">
								<label for="input_uid" class="col-sm-3 control-label">ID</label>
								<div class="col-sm-9">
									<input type="text" class="form-control" name="input_uid" id="input_uid" value='<?php echo $xuid;?>' disabled>
								</div>
							</div>
							<div class="form-group">
								<label for="input_uname" class="col-sm-3 control-label">Nama</label>
								<div class="col-sm-9">
									<input type="text" class="form-control" name="input_uname" id="input_uname" disabled>
								</div>
							</div>
							<div class="form-group">
								<label for="input_upwd" class="col-sm-3 control-label">Password Baru</label>
								<div class="col-sm-9">
									<input type="password" class="form-control" name="input_upwd" id="input_upwd" maxlength='35' placeholder='Maksimal 35 karakter'>
								</div>
							</div>
							
																					
							<div class="form-group">
								<br>
								<div class="col-sm-offset-3 col-sm-9">
									<button id='simpan' type="button" class="btn btn-primary">Simpan</button>
									<button id='batal' type="button" class="btn btn-default">Kembali</button>
									<input type="hidden" name="xloginid"  id="xloginid" value='<?php echo $id_pemakai; ?>' />
									<input type="hidden" name="status_proses"  id="status_proses" value='resetpwd' />
									<input type="hidden" name="zuid"  id="zuid" />
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
    <script>
		$(document).ready( function () {
			bacadata($('#input_uid').val());
			function bacadata(e){
				$.ajax({
					type	: "POST",
					url		: "pemakai_cari.php",
					data	: "uid="+e,
					dataType : "json",				  
					success	: function(data){
						$("#zuid").val(data.uid);
						$("#input_uname").val(data.uname);					
					}
				});
			}
			
			$('#input_upwd').focus();
			
			$('#batal').on('click', function(event) {
				window.open("pemakai.php",'_self',false);
			});
			
			//$('#input_upwd').keydown(function(e) {if (e.keyCode == 13) {e.preventDefault();$('#simpan').focus();}});
			
			$('#simpan').on('click', function(event) {
				var input_uid=$.trim($('#input_uid').val());
				var input_uname=$.trim($('#input_uname').val());
				var input_upwd=$.trim($('#input_upwd').val());
				
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
				$("#input_uid").prop("disabled",false);
				var string = $("#my-form").serialize();
				
				$.ajax({
					type	: "POST",
					url		: "pemakai_simpan.php",  
					data	: string,
					success	: function(data){
						if(data=='Proses reset password berhasil dilakukan'){
							BootstrapDialog.show({
								type: BootstrapDialog.TYPE_INFO,
								title: 'Konfirmasi Reset Password: ',
								message: 'Proses reset password berhasil dilakukan'
							});
							window.setTimeout( function(){window.history.back();}, 2000 );
						}else{
							BootstrapDialog.show({
								type: BootstrapDialog.TYPE_INFO,
								title: 'Konfirmasi Tambah: ',
								message: data
							});
							window.setTimeout( function(){window.open("pemakai_resetpwd.php",'_self',false);}, 2000 );
						}				
					}
				});		
			});
			
		});		
    </script>
</body>

</html>
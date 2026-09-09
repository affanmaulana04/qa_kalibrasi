<?php
	session_start();
	if($_SESSION['login']==0){
		header('location: ../../logout.php');
	}else{
		if($_SESSION['tabel_pemakai_app']=='Y'){
			include "../../inc/inc_koneksi.php";
			$id_pemakai=$_SESSION['userid'];
			$nama_pemakai=$_SESSION['username'];
			$role=$_SESSION['role'];
			$jam_login=$_SESSION['jamlogin'];
			$seksi_user = $_SESSION['seksi_user'];
			$cek_user = mysqli_query($konek, "SELECT role, seksi FROM user WHERE uid = '$id_pemakai'");
			$data_user = mysqli_fetch_assoc($cek_user);
			$kategori = $data_user['role'];
			if (!empty($kategori)) {
			$query = mysqli_query($konek, "SELECT * FROM user WHERE role >= '$kategori' ORDER BY uid");
    
			} else {
				echo "<script>
						alert('Jabatan Anda belum terdaftar di sistem. Silakan hubungi Admin!');
						window.history.back();
					</script>";
				exit(); // Hentikan script biar gak error ke bawah
			}
			$array_seksi = preg_split('/[\s,]+/', $seksi_user);
			$kondisi = array();
		foreach ($array_seksi as $s) {
    		$s = trim($s); // Hilangkan spasi sekitar koma
    	if (!empty($s)) {
        	// Gunakan LIKE untuk mencari kata kunci di dalam kolom seksi
        	$kondisi[] = "seksi LIKE '%$s%'";
  			  }
			}
			// Gabungkan semua kondisi dengan "OR"
			// Hasilnya: seksi LIKE '%hrd%' OR seksi LIKE '%ge%' OR seksi LIKE '%accounting%'
			$filter_sql = implode(" OR ", $kondisi);
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

    <title>Pemakai</title>
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
        <div class="row">
            <div class="col-lg-12">
                <h3 class="page-header" id='judul'>Pemakai Aplikasi</h3>
            </div>

        </div>
	    <!-- /.row -->

        <div class="row">
			<br>
            <div class="col-md-12">
				<table id="jsontable" class="display table table-bordered" cellspacing="0" width="100%">
					<thead>
						<tr>
							<th>ID Pemakai</th><th>Nama</th><th>Password</th><th>Admin</th>
							<th>Blok</th><th>Online</th><th>Role</th><th>Seksi</th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th>ID Pemakai</th><th>Nama</th><th>Password</th><th>Admin</th>
							<th>Blok</th><th>Online</th><th>Role</th><th>Seksi</th>
						</tr>
					</tfoot>
					<tbody>
						<?php
							include "../../inc/inc_koneksi.php";
							$list_jabatan = "'Manager', 'Asst. Manager', 'Supervisor / Sr.Spv', 'Foreman / Jr.Spv', 'Group Leader', 'Sr.Worker / Sr.Staf', 'G'";
							if ($role == 'admin') {
							$query = mysqli_query($konek,"select * from user order by uid");
							while($fetch = mysqli_fetch_array($query)){
								echo "<tr>
									<td>".$fetch['uid'] ."</td>
									<td>".$fetch['uname']."</td>
									<td>".$fetch['pword']."</td>
									<td>".$fetch['admin']."</td>
									<td>".$fetch['block']."</td>
									<td>".$fetch['online']."</td>
									<td>".$fetch['role']."</td>
									<td>".$fetch['seksi']."</td>
								</tr>"; }
								} else {
								$query = mysqli_query($konek, "SELECT * FROM user as c LEFT JOIN data_karyawan AS k ON c.uid = k.prn
									WHERE '$id_pemakai' IN (k.manager, k.foreman, k.supervisor)
									ORDER BY c.uid");
								while($fetch = mysqli_fetch_array($query)){
								echo "<tr>
									<td>".$fetch['uid'] ."</td>
									<td>".$fetch['uname']."</td>
									<td>".$fetch['pword']."</td>
									<td>".$fetch['admin']."</td>
									<td>".$fetch['block']."</td>
									<td>".$fetch['online']."</td>
									<td>".$fetch['role']."</td>
									<td>".$fetch['seksi']."</td>
								</tr>";							
								} }
						?>
					</tbody>
				</table>		
            </div>
        </div>					
		<br>
		<button id='tambah' type="button" class="btn btn-success"><i class="fa fa-fw fa-plus"></i> Tambah</button>
		<button id='edit' type="button" class="btn btn-warning"><i class="fa fa-fw fa-pencil"></i> Edit</button>
		<button id='hapus' type="button" class="btn btn-danger"><i class="fa fa-fw fa-trash-o"></i> Hapus</button>
		<button id='resetpwd' type="button" class="btn btn-link"><i class="fa fa-fw fa-refresh"></i> Reset Password</button>
		<input type="hidden" name="xuid"  id="xuid" />
		<input type="hidden" name="xldap"  id="xldap" />
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
	
    <script>
		$(document).ready( function () {
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
					{ "width": "23%" },{ "width": "23%" },{ "width": "10%" },{ "width": "4%" },{ "width": "3%" },
					{ "width": "4%" },{ "width": "4%" },{ "width": "4%" }
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
					$('#xuid').val('');
					$('#xldap').val('');
				} else {
					table_arah.$('tr.selected').removeClass('selected');
					$(this).addClass('selected');
					var row1 = table_arah.row( this ).data();
					$('#xuid').val(row1[0]);
					$('#xldap').val(row1[4]);
				}
			});
			
			$('#tambah').on('click', function(event) {
				window.open("pemakai_tambah.php",'_self',false);
			});
			
			$('#edit').on('click', function(event) {
				var xuid=$.trim($('#xuid').val());
				if(xuid.length==0){
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Edit: ',
						message: 'Record belum dipilih'
					});
				}else{
					if(xuid=='admin'){
						var xidlogin=$('#xid_login').val();
						var xidlogin1=xidlogin.toUpperCase();
						if(xidlogin1=='ADMIN'){
							window.open("pemakai_edit.php?xuid="+xuid,'_self',false);
						}else{
							BootstrapDialog.show({
								type: BootstrapDialog.TYPE_WARNING,
								title: 'Konfirmasi Edit: ',
								message: 'Anda tidak diijinkan mengedit Id pemakai admin'
							});
						}
					}else{
						window.open("pemakai_edit.php?xuid="+xuid,'_self',false);
					}	
				}
			});
			
			$('#hapus').on('click', function(event) {
				var xuid=$.trim($('#xuid').val());
				if(xuid.length==0){
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Hapus: ',
						message: 'Record belum dipilih'
					});
				}else{
					if(xuid=='admin'){
						BootstrapDialog.show({
							type: BootstrapDialog.TYPE_WARNING,
							title: 'Konfirmasi Hapus: ',
							message: 'Id pemakai admin tidak bisa dihapus'
						});
					}else{
						BootstrapDialog.confirm({
							title: 'Konfirmasi Hapus',
							message: 'Data '+xuid +' akan dihapus ?',
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
										url		: "pemakai_hapus.php",
										data	: "uid="+$('#xuid').val(),		  
										success	: function(data){
											//location.reload();
											BootstrapDialog.show({
												type: BootstrapDialog.TYPE_INFO,
												title: 'Konfirmasi Hapus: ',
												message: data
											});
											window.setTimeout( function(){location.reload();}, 2000 );
										}
									});
								}else {
									//alert('Nope.');
								}
							}
						});						
					}
				}
			});
			$('#resetpwd').on('click', function(event) {
				var xuid=$.trim($('#xuid').val());
				if(xuid.length==0){
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Reset Password: ',
						message: 'Record belum dipilih'
					});
				}else{
					if(xuid=='admin'){
						var xidlogin=$('#xid_login').val();
						var xidlogin1=xidlogin.toUpperCase();
						if(xidlogin1=='ADMIN'){
							window.open("pemakai_resetpwd.php?xuid="+xuid,'_self',false);
						}else{
							BootstrapDialog.show({
								type: BootstrapDialog.TYPE_WARNING,
								title: 'Konfirmasi Reset Password: ',
								message: 'Anda tidak diijinkan mereset password Id pemakai admin'
							});
						}
					}else{
						if($('#xldap').val()=='N'){
							window.open("pemakai_resetpwd.php?xuid="+xuid,'_self',false);
						}else{
							BootstrapDialog.show({
								type: BootstrapDialog.TYPE_WARNING,
								title: 'Konfirmasi Reset Password: ',
								message: 'Id Pemakai tidak perlu direset'
							});
						}
					}	
				}
			});
			
		});		
    </script>
</body>

</html>

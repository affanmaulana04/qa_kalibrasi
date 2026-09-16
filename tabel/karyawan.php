<?php
	session_start();
	if($_SESSION['login']==0){
		header('location: ../../logout.php');
	}else{
		if($_SESSION['tabel_karyawan']=='Y'){
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
				$query = mysqli_query($konek, "SELECT * FROM data_karyawan WHERE jabatan >= '$kategori' ORDER BY prn");
			} else {
				echo "<script>
						alert('Jabatan Anda belum terdaftar di sistem. Silakan hubungi Admin!');
						window.history.back(); // Arahkan ke menu profil atau home
					</script>";
				exit(); // Hentikan script biar gak error ke bawah
			}
			$array_seksi = preg_split('/[\s,]+/', $seksi_user);
			$kondisi = array();
		foreach ($array_seksi as $s) {
    		$s = trim($s);
    	if (!empty($s)) {
        	// Gunakan LIKE untuk mencari kata kunci di dalam kolom seksi
        	$kondisi[] = "seksi LIKE '%$s%'";
  			  }
			}
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
    <title>Data Karyawan</title>
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
									<li style='padding-left:10px;'>Seksi : <span style='color: #0ca81b;'><?php echo $seksi_user; ?></span></li>
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
                <h3 class="page-header" id='judul'>Karyawan</h3>
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
								<th class="text-center">NIK</th><th class="text-center">Nama</th>
								<th class="text-center">Seksi</th><th class="text-center">Jabatan</th><th class="text-center">Foto</th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<th class="text-center">NIK</th><th class="text-center">Nama</th>
								<th class="text-center">Seksi</th><th class="text-center">Jabatan</th><th class="text-center">Foto</th>
							</tr>
						</tfoot>
						<tbody>
							<?php
								include "../../inc/inc_koneksi.php";
								include "../../inc/fungsi_tanggal.php";
								$list_jabatan = "'Manager', 'Asst. Manager', 'Supervisor / Sr.Spv', 'Foreman / Jr.Spv', 'Group Leader', 'Sr.Worker / Sr.Staf', 'G'";
								if ($role == 'admin') {
									$query = mysqli_query($konek,"select * from data_karyawan order by prn");
									while($fetch = mysqli_fetch_array($query)){
									echo "<tr>
										<td class='text-center'>".$fetch['prn'] ."</td>
										<td>".$fetch['nama'] ."</td>
										<td>".$fetch['seksi'] ."</td>
										<td>".$fetch['jabatan'] ."</td>
										<td class='text-center'>".$fetch['foto'] ."</td>
									</tr>"; }
								} else {
								$query = mysqli_query($konek, "SELECT * FROM user as c LEFT JOIN data_karyawan AS k ON c.uid = k.prn
									WHERE '$id_pemakai' IN (k.manager, k.foreman, k.supervisor)
									ORDER BY c.uid");
								while($fetch = mysqli_fetch_array($query)){
									echo "<tr>
										<td>".$fetch['prn'] ."</td>
										<td>".$fetch['nama'] ."</td>
										<td>".$fetch['seksi'] ."</td>
										<td>".$fetch['jabatan'] ."</td>
										<td class='text-center' >".$fetch['foto'] ."</td>
									</tr>";						
								} }
							?>
						</tbody>
					</table>
				</div>
            </div>
        </div>					
		<br>

		<button id='updatex' type="button" class="btn btn-success"><i class="fa fa-fw fa-upload"></i> Upload</button>
		<button id='cek' type="button" class="btn btn-info"><i class="fa fa-arrows-alt"></i> Lihat Detail</button>
		<button id='hapus' type="button" class="btn btn-danger"><i class="fa fa-fw fa-trash-o"></i> Hapus</button>
		<button id='upload_foto' type="button" class="btn btn-link"><i class="fa fa-fw fa fa-file-image-o"></i> Upload Foto Karyawan</button>
		<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
		
		<input type="hidden" name="xuid"  id="xuid" />
		<input type="hidden" name="xpmast_nik"  id="xpmast_nik"/>
		<input type="hidden" name="xpmast_name"  id="xpmast_name"/>
		<input type="hidden" name="xprn"  id="xprn"/>
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
	<script src="../../js/jquery.magnific-popup.js"></script>
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
					{ "width": "15%" },{ "width": "30%" },{ "width": "30%" },{"width":"20%"},{ "width": "5%" }
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
					$('#xprn').val('');
					$('#xpmast_name').val('');
					$('#foto_kyw').attr('src', '../../images/foto/blank.jpg');
				} else {
					table_arah.$('tr.selected').removeClass('selected');
					$(this).addClass('selected');
					var row1 = table_arah.row( this ).data();
					$('#xprn').val(row1[0]);
					$('#xpmast_name').val(row1[1]);
					$('#foto_kyw').val(row1[1]);
					$.ajax({
						type	: "POST",
						url		: "karyawan_lihat.php",
						data	: "xpmast_nik="+$('#xpmast_nik').val(),
						//dataType : "json",
						success	: function(data){
							var JSONObject = $.parseJSON("["+data+"]");						
							$('#input_pmast_dessect').val(JSONObject[0]["seksi"]);
							$('#input_pmast_desposit').val(JSONObject[0]["bagian"]);
							$('#input_pmast_awaldt').val(JSONObject[0]["tgl_masuk"]);
							$('#input_sts_bagian').val(JSONObject[0]["shift"])
							$('#input_desstatkyw').val(JSONObject[0]["status_kontrak"])
							
							if(JSONObject[0]["foto"]=='ada'){
								$('#foto_kyw').attr('src', '../../images/foto/'+row1[0]+'.jpg');
							}else{
								$('#foto_kyw').attr('src', '../../images/foto/blank.jpg');
							}
						}
					});
				}
			});
			
			$('#updatex').on('click', function(event) {
				window.open("../upload_data/upload_master_data.php",'_blank',false);
			});

			function escapepetik1(title) {
				title = title.replace(/'/g, "'")
				title = escape(title)
				return title
			}
			
			$('.btn-danger').on('click', function(event) {
			var xprn = $.trim($('#xprn').val()); 
			var xpmast_name = $.trim($('#xpmast_name').val());

			if(xprn.length == 0){
				BootstrapDialog.show({
					type: BootstrapDialog.TYPE_WARNING,
					title: 'Konfirmasi Hapus',
					message: 'Record belum dipilih (NIK Kosong)'
				});
			} else {
				BootstrapDialog.confirm({
					title: 'Konfirmasi Hapus',
					message: 'Data NIK <span style="color: red;">'+ xprn +'</span><br> <span style="color: red;">'+ xpmast_name +'</span><br>akan dihapus?',
					type: BootstrapDialog.TYPE_DANGER,
					btnCancelLabel: 'Tidak',
					btnOKLabel: 'Hapus',
					callback: function(result) {
						if(result) {
							$.ajax({
								type: "POST",
								url: "karyawan_hapus.php",
								data: { 
								xprn: xprn 
								}, 
								success: function(data){
									var response = data.trim();
									if(response == 'Ada transaksi'){
										BootstrapDialog.show({
											type: BootstrapDialog.TYPE_WARNING,
											title: 'Gagal',
											message: 'Data masih dipakai pada transaksi'
										});
									} else {
										BootstrapDialog.show({
											type: BootstrapDialog.TYPE_INFO,
											title: 'Hasil',
											message: response
										});
										if(response.indexOf('berhasil') > -1) {
											window.setTimeout(function(){ location.reload(); }, 1500);
										}
									}
								}
							});
						}
					}
				});
			}
		});

			$('#cek').on('click', function(event) {
				var xprn=$.trim($('#xprn').val());
				if (xprn.length == 0) {
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Tampilan Lengkap: ',
						message: 'Record belum dipilih'
					});
				}else{
					var xid_pemakai=$.trim($('#xid_login').val());
					var xjudul='Karyawan';
					var xnamafile=$.trim($('#xprn').val());	
					var xpmast_name=$.trim($('#xpmast_name').val());		
					window.open("data_lengkap.php?xprn="+xprn,'_blank',false);
				}												
			});	

			$('#upload_foto').on('click', function(event) {
				var xprn=$.trim($('#xprn').val());
				if(xprn.length==0){
					BootstrapDialog.show({
						type: BootstrapDialog.TYPE_WARNING,
						title: 'Konfirmasi Upload: ',
						message: 'Record belum dipilih'
					});
				}else{		
					window.open("fileupload_photo_karyawan.php?xprn="+xprn,'_blank',false);
					
				}						
			});	
		});		
    </script>
</body>

</html>

<?php
session_start();
if($_SESSION['login']==0){
	header('location: ../../logout.php');
}
include "../../inc/inc_koneksi.php";

$status_proses=$_POST['status_proses'];
if($status_proses=='edit'){
	$zuid=trim(str_replace("'","\'",$_POST['zuid']));
}
if($status_proses=='tambah' || $status_proses=='resetpwd'){
	$input_uid=trim(str_replace("'","\'",$_POST['input_uid']));
	$input_upwd=trim(str_replace("'","\'",$_POST['input_upwd']));
}

$uid=str_replace("'","\'",$_POST['xloginid']);

if($status_proses=='tambah' || $status_proses=='edit'){
	$input_uname=trim(str_replace("'","\'",$_POST['input_uname']));
	
	$input_admin = isset($_POST['input_admin']) ? $_POST['input_admin'] : 'N';
	$input_hrd = isset($_POST['input_hrd']) ? $_POST['input_hrd'] : 'N';
	$input_manager = isset($_POST['input_manager']) ? $_POST['input_manager'] : 'N';
	$input_supervisor = isset($_POST['input_supervisor']) ? $_POST['input_supervisor'] : 'N';
	$input_foreman = isset($_POST['input_foreman']) ? $_POST['input_foreman'] : 'N';
	$input_user = isset($_POST['input_user']) ? $_POST['input_user'] : 'N';
	
	
	if($input_admin=='Y'){$status='admin';}
	if($input_hrd=='Y'){$status='hrd';}
	if($input_manager=='Y'){$status='manager';}
	if($input_supervisor=='Y'){$status='Supervisor / Sr.Spv';}
	if($input_foreman=='Y'){$status='Foreman / Jr.Spv';}
	if($input_user=='Y'){$status='user';}
	
	$input_sapaan=trim(str_replace("'","\'",$_POST['input_sapaan']));
	$input_seksi=trim(str_replace("'","\'",$_POST['input_seksi']));
	$input_email=trim(str_replace("'","\'",$_POST['input_email']));
	
//TABEL////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
	$tbl_glu = isset($_POST['tbl_glu']) ? $_POST['tbl_glu'] : 'N';
	$tbl_karyawan = isset($_POST['tbl_karyawan']) ? $_POST['tbl_karyawan'] : 'N';
	$tbl_pemakai_aplikasi = isset($_POST['tbl_pemakai_aplikasi']) ? $_POST['tbl_pemakai_aplikasi'] : 'N';
	
	$mn_tabel=$tbl_glu.$tbl_karyawan.$tbl_pemakai_aplikasi;
		if($mn_tabel=='NNN'){$mn_tabel='N';}else{$mn_tabel='Y';}
		
//TRANSAKSI////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$tran_data_karyawan = isset($_POST['tran_data_karyawan']) ? $_POST['tran_data_karyawan'] : 'N';
	
	$mn_tran_data_karyawan=$tran_data_karyawan;
		if($mn_tran_data_karyawan=='N'){$mn_tran_data_karyawan='N';}else{$mn_tran_data_karyawan='Y';}
		
	$tran_data_mutasi = isset($_POST['tran_data_mutasi']) ? $_POST['tran_data_mutasi'] : 'N';
	$tran_val_mutasi_mng_asal = isset($_POST['tran_val_mutasi_mng_asal']) ? $_POST['tran_val_mutasi_mng_asal'] : 'N';
	$tran_val_mutasi_mng_tujuan = isset($_POST['tran_val_mutasi_mng_tujuan']) ? $_POST['tran_val_mutasi_mng_tujuan'] : 'N';
	$tran_val_mutasi_sv_asal = isset($_POST['tran_val_mutasi_sv_asal']) ? $_POST['tran_val_mutasi_sv_asal'] : 'N';
	$tran_val_mutasi_sv_tujuan = isset($_POST['tran_val_mutasi_sv_tujuan']) ? $_POST['tran_val_mutasi_sv_tujuan'] : 'N';
	$tran_val_mutasi_hrd = isset($_POST['tran_val_mutasi_hrd']) ? $_POST['tran_val_mutasi_hrd'] : 'N';
	
	$mn_tran_mutasi_karyawan=$tran_data_mutasi.$tran_val_mutasi_mng_asal.$tran_val_mutasi_mng_tujuan.$tran_val_mutasi_sv_asal.$tran_val_mutasi_sv_tujuan.$tran_val_mutasi_hrd;
		if($mn_tran_mutasi_karyawan=='NNNNNN'){$mn_tran_mutasi_karyawan='N';}else{$mn_tran_mutasi_karyawan='Y';}
	
	$tran_data_rotasi = isset($_POST['tran_data_rotasi']) ? $_POST['tran_data_rotasi'] : 'N';
	$tran_val_rotasi_mng = isset($_POST['tran_val_rotasi_mng']) ? $_POST['tran_val_rotasi_mng'] : 'N';
	$tran_val_rotasi_sv_asal = isset($_POST['tran_val_rotasi_sv_asal']) ? $_POST['tran_val_rotasi_sv_asal'] : 'N';
	$tran_val_rotasi_sv_tujuan = isset($_POST['tran_val_rotasi_sv_tujuan']) ? $_POST['tran_val_rotasi_sv_tujuan'] : 'N';
	$tran_val_rotasi_hrd = isset($_POST['tran_val_rotasi_hrd']) ? $_POST['tran_val_rotasi_hrd'] : 'N';
	
	$mn_tran_rotasi_karyawan=$tran_data_rotasi.$tran_val_rotasi_mng.$tran_val_rotasi_sv_asal.$tran_val_rotasi_sv_tujuan.$tran_val_rotasi_hrd;
		if($mn_tran_rotasi_karyawan=='NNNNN'){$mn_tran_rotasi_karyawan='N';}else{$mn_tran_rotasi_karyawan='Y';}

	$mn_transaksi=$mn_tran_data_karyawan.$mn_tran_mutasi_karyawan.$mn_tran_rotasi_karyawan;
		if($mn_transaksi=='NNN'){$mn_transaksi='N';}else{$mn_transaksi='Y';}
	if($status_proses=='tambah'){
		//cek sudah ada atau belum pada tabel user
		$row = mysqli_num_rows(mysqli_query($konek,"SELECT * from user WHERE uid = '$input_uid'"));
		if ($row>0){
			echo "Id Pemakai sudah ada";
		}else{
			if(strlen(trim($input_upwd))>0){
				$input_upwd=md5(trim($input_upwd));
			}
			mysqli_query($konek,"INSERT INTO user (uid,uname,pword,admin,sapaan,createdby,crtdate,seksi,email_pembuat,role) 
			VALUES ('$input_uid','$input_uname','$input_upwd','$input_admin','$input_sapaan','$uid',now(),'$input_seksi','$input_email','$status')");

			mysqli_query($konek,"INSERT INTO user_menu (uid,mn_tabel,mn_transaksi,
			tabel_glu,tabel_karyawan,tabel_pemakai_app,
			tran_data_karyawan,
			tran_data_mutasi,tran_val_mutasi_mng_asal,tran_val_mutasi_mng_tujuan,tran_val_mutasi_sv_asal,tran_val_mutasi_sv_tujuan,tran_val_mutasi_hrd,
			tran_data_rotasi,tran_val_rotasi_mng,tran_val_rotasi_sv_asal,tran_val_rotasi_sv_tujuan,tran_val_rotasi_hrd) 
			VALUES ('$input_uid','$mn_tabel','$mn_transaksi',
			'$tbl_glu','$tbl_karyawan','$tbl_pemakai_aplikasi',
			'$tran_data_karyawan',
			'$tran_data_mutasi','$tran_val_mutasi_mng_asal','$tran_val_mutasi_mng_tujuan','$tran_val_mutasi_sv_asal','$tran_val_mutasi_sv_tujuan','$tran_val_mutasi_hrd',
			'$tran_data_rotasi','$tran_val_rotasi_mng','$tran_val_rotasi_sv_asal','$tran_val_rotasi_sv_tujuan','$tran_val_rotasi_hrd')");

			echo "Proses tambah data berhasil dilakukan";
			
		}
	}else{
		//cek sudah ada atau belum pada tabel user
		$rowx = mysqli_num_rows(mysqli_query($konek,"SELECT * from user WHERE uid = '$zuid'"));
		if ($rowx>0){
			mysqli_query($konek,"UPDATE user SET uname='$input_uname',admin='$input_admin',
			sapaan='$input_sapaan',changeby='$uid',seksi='$input_seksi',email_pembuat='$input_email',role='$status' WHERE uid='$zuid'"); 
			
			mysqli_query($konek,"UPDATE user_menu SET mn_tabel='$mn_tabel',mn_transaksi='$mn_transaksi',
			tabel_glu='$tbl_glu',tabel_karyawan='$tbl_karyawan',tabel_pemakai_app='$tbl_pemakai_aplikasi',
			tran_data_karyawan='$tran_data_karyawan',
			tran_data_mutasi='$tran_data_mutasi',tran_val_mutasi_mng_asal='$tran_val_mutasi_mng_asal',tran_val_mutasi_mng_tujuan='$tran_val_mutasi_mng_tujuan',tran_val_mutasi_sv_asal='$tran_val_mutasi_sv_asal',tran_val_mutasi_sv_tujuan='$tran_val_mutasi_sv_tujuan',tran_val_mutasi_hrd='$tran_val_mutasi_hrd',
			tran_data_rotasi='$tran_data_rotasi',tran_val_rotasi_mng='$tran_val_rotasi_mng',tran_val_rotasi_sv_asal='$tran_val_rotasi_sv_asal',tran_val_rotasi_sv_tujuan='$tran_val_rotasi_sv_tujuan',tran_val_rotasi_hrd='$tran_val_rotasi_hrd' WHERE uid='$zuid'");	
		
			
			echo "Proses edit data berhasil dilakukan";	
		}else{
			echo "Id Pemakai tidak ada";
		}	
	}
}else{
	$rowz = mysqli_num_rows(mysqli_query($konek,"SELECT * from user WHERE uid = '$input_uid'"));
	if ($rowz>0){
		$input_upwd=md5(trim($input_upwd));
		mysqli_query($konek,"UPDATE user SET pword='$input_upwd',changeby='$uid' WHERE uid='$input_uid'"); 
		
		echo "Proses reset password berhasil dilakukan";
	}else{
		echo "Id Pemakai tidak ada";
	}
}

?>
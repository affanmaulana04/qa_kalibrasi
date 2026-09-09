<?php
session_start();
if($_SESSION['login']==0){
    header('location: ../../logout.php');
    exit();
}
include "../../inc/inc_koneksi.php";
$uid = $_POST['uid'];

$sql=mysqli_query($konek,"SELECT * from user WHERE uid = '$uid'");
$row = mysqli_num_rows($sql);
$data = array();

if ($row>0){
    while ($r=mysqli_fetch_array($sql)){        
        $data['uid'] = $uid; 
        $data['uname'] = $r['uname']; 
        $data['pword'] = $r['pword'];
        $data['admin'] = $r['admin'];
        $data['role'] = $r['role'];
        $data['sapaan'] = $r['sapaan'];
        $data['seksi'] = $r['seksi'];
        $data['email_pembuat'] = $r['email_pembuat'];
    }
}else{
    $data['uid'] = '';
    $data['uname'] = '';
    $data['pword'] = '';
    $data['admin'] = '';
    $data['role'] = '';
    $data['sapaan'] = '';
    $data['seksi'] = '';
    $data['email_pembuat'] = '';
}

echo json_encode($data);
?>
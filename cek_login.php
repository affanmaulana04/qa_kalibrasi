<?php
include "inc/inc_koneksi.php";
session_start();

$_SESSION['jamlogin'] = gmdate("H:i:s", time() + 60 * 60 * 7);

$userid = trim((string)($_POST['userid'] ?? ''));
$pass   = (string)($_POST['password'] ?? '');

if ($userid === '' || $pass === '') {
    echo "<center><br><br><br><br><br><br>Username dan Password wajib diisi<br><br><a href='index.php'>Kembali</a></center>";
    exit;
}

$pc_name = gethostbyaddr($_SERVER['REMOTE_ADDR']);
$_SESSION['nama_pc'] = $pc_name;

$stmt = mysqli_prepare($konek, "SELECT * FROM `user` WHERE uid=? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $userid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$r = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$r) {
    echo "<center><br><br><br><br><br><br>User id <b>" . htmlspecialchars($userid, ENT_QUOTES) . "</b> hasn't been registered yet<br><br>";
    echo "<a href='index.php'>Kembali</a></center>";
    exit;
}

if (strtoupper((string)($r['block'] ?? 'N')) === 'Y') {
    echo "<center><br><br><br><br><br><br>User id <b>" . htmlspecialchars($userid, ENT_QUOTES) . "</b> has been blocked<br><br>";
    echo "<a href='index.php'>Kembali</a></center>";
    exit;
}

if ((string)($r['pword'] ?? '') !== md5($pass)) {
    echo "<center><br><br><br><br><br><br>Invalid Password<br><br><a href='index.php'>Kembali</a></center>";
    exit;
}

$_SESSION['userid']         = $r['uid'];
$_SESSION['username']       = $r['uname'];
$_SESSION['nama_lengkap']   = $r['uname'];
$_SESSION['role']           = $r['role'];
$_SESSION['login']          = 1;
$_SESSION['admin']          = $r['admin'] ?? 'N';
$_SESSION['sapaan']         = $r['sapaan'] ?? '';
$_SESSION['seksi_user']     = $r['seksi'] ?? '';
$_SESSION['email_user']     = $r['email_pembuat'] ?? '';
$_SESSION['namaemail_user'] = $r['uname'];
$_SESSION['pembuat']        = $r['pembuat'] ?? 'N';
$_SESSION['pemeriksa']      = $r['pemeriksa'] ?? 'N';
$_SESSION['penyetuju']      = $r['penyetuju'] ?? 'N';

$up = mysqli_prepare($konek, "UPDATE `user` SET online='Y', pc_login=? WHERE uid=?");
if ($up) {
    mysqli_stmt_bind_param($up, 'ss', $pc_name, $r['uid']);
    mysqli_stmt_execute($up);
    mysqli_stmt_close($up);
}

if (!empty($_POST['remember'])) {
    setcookie('userid', $userid, time() + (60 * 60 * 24 * 3), '/');
} else {
    setcookie('userid', '', time() - 3600, '/');
}

header('Location: media.php');
exit;
?>

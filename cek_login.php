<?php
include "inc/inc_koneksi.php";
session_start();

$wantJson = ((string)($_POST['ajax'] ?? '') === '1')
    || strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

function loginResponse($status, $message = '', $code = '', $redirect = '') {
    global $wantJson;
    if ($wantJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'code' => $code,
            'redirect' => $redirect
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($status === 'success') {
        header('Location: media.php');
        exit;
    }

    $query = http_build_query(['error' => $code ?: 'login_failed', 'message' => $message]);
    header('Location: index.php?' . $query);
    exit;
}

$_SESSION['jamlogin'] = gmdate("H:i:s", time() + 60 * 60 * 7);

$userid = trim((string)($_POST['userid'] ?? ''));
$pass   = (string)($_POST['password'] ?? '');

if ($userid === '' || $pass === '') {
    loginResponse('error', 'Username dan password wajib diisi.', 'required');
}

$pc_name = gethostbyaddr($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
$_SESSION['nama_pc'] = $pc_name;

$stmt = mysqli_prepare($konek, "SELECT * FROM `user` WHERE uid=? LIMIT 1");
if (!$stmt) {
    loginResponse('error', 'Gagal memeriksa login. Silakan coba lagi.', 'server_error');
}
mysqli_stmt_bind_param($stmt, 's', $userid);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$r = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$r) {
    loginResponse('error', 'User ID belum terdaftar.', 'user_not_found');
}

if (strtoupper((string)($r['block'] ?? 'N')) === 'Y') {
    loginResponse('error', 'User ID ini sedang diblokir.', 'blocked');
}

if ((string)($r['pword'] ?? '') !== md5($pass)) {
    loginResponse('error', 'Password salah.', 'password');
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

loginResponse('success', 'Login berhasil.', '', 'media.php');
?>

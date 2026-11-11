<?php
session_start();
if (empty($_SESSION['login'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Login diperlukan.']);
    exit;
}
include '../../inc/inc_koneksi.php';
require_once __DIR__ . '/../../inc/qa_rbac.php';
require_once __DIR__ . '/../../inc/qa_seksi_akses.php';
if (empty($_SESSION['qa_user_admin_csrf']) || !hash_equals((string)$_SESSION['qa_user_admin_csrf'], (string)($_POST['qa_user_csrf'] ?? ''))) {
    http_response_code(403);
    exit('Token keamanan user tidak valid. Refresh halaman.');
}

if (!qaRbacIsAdmin($konek)) {
    http_response_code(403);
    exit('Hanya Admin QA.');
}
qaRbacRequire($konek, 'pengguna', true);
header('Content-Type: application/json; charset=utf-8');
$is_admin = strtoupper((string)($_SESSION['admin'] ?? 'N')) === 'Y' || strtolower((string)($_SESSION['role'] ?? '')) === 'admin';
if (!$is_admin) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Hanya Admin QA yang dapat mengelola user.']);
    exit;
}

$status = trim((string)($_POST['status_proses'] ?? ''));
if ($status === 'resetpwd') {
    $targetUid = trim((string)($_POST['input_uid'] ?? ''));
    $newPassword = (string)($_POST['input_upwd'] ?? '');
    if ($targetUid === '' || trim($newPassword) === '' || strlen($newPassword) > 35) {
        echo json_encode(['status' => 'error', 'message' => 'ID user dan password baru wajib diisi (maksimal 35 karakter).']);
        exit;
    }

    $check = $konek->prepare('SELECT uid FROM `user` WHERE uid=? LIMIT 1');
    if (!$check) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memeriksa user.']);
        exit;
    }
    $check->bind_param('s', $targetUid);
    $checkOk = $check->execute();
    $targetExists = $checkOk && $check->get_result()->fetch_assoc();
    $check->close();
    if (!$checkOk || !$targetExists) {
        echo json_encode(['status' => 'error', 'message' => $checkOk ? 'User tidak ditemukan.' : 'Gagal memeriksa user.']);
        exit;
    }

    $passwordHash = md5($newPassword);
    $update = $konek->prepare('UPDATE `user` SET pword=? WHERE uid=? LIMIT 1');
    if (!$update) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memproses reset password.']);
        exit;
    }
    $update->bind_param('ss', $passwordHash, $targetUid);
    $ok = $update->execute();
    $update->close();
    echo json_encode($ok
        ? ['status' => 'success', 'message' => 'Password user berhasil direset.']
        : ['status' => 'error', 'message' => 'Gagal menyimpan password baru.']);
    exit;
}

function ensure_col($db, $c, $def) {
    $q = mysqli_query($db, "SHOW COLUMNS FROM `user` LIKE '" . mysqli_real_escape_string($db, $c) . "'");
    if ($q && mysqli_num_rows($q) === 0)
        mysqli_query($db, "ALTER TABLE `user` ADD COLUMN `$c` $def");
}
ensure_col($konek, 'pembuat', "ENUM('Y','N') NOT NULL DEFAULT 'N'");
ensure_col($konek, 'pemeriksa', "ENUM('Y','N') NOT NULL DEFAULT 'N'");
ensure_col($konek, 'penyetuju', "ENUM('Y','N') NOT NULL DEFAULT 'N'");
ensure_col($konek, 'block', "ENUM('Y','N') NOT NULL DEFAULT 'N'");
$uid = trim((string)($_POST['input_uid'] ?? ($_POST['zuid'] ?? '')));
$uidOld = trim((string)($_POST['zuid'] ?? ''));
$uname = trim((string)($_POST['input_uname'] ?? ''));
$sapaan = trim((string)($_POST['input_sapaan'] ?? ''));
$seksi = trim((string)($_POST['input_seksi'] ?? ''));
$email = trim((string)($_POST['input_email'] ?? ''));
$creator = trim((string)($_SESSION['userid'] ?? ''));
// Status/flag lama tidak diterima dari browser. User baru default N,
// sedangkan pada edit kolom legacy existing tidak diubah.
$admin = 'N';
$role = 'QA';
$pembuat = 'N';
$pemeriksa = 'N';
$penyetuju = 'N';
$block = 'N';

// Role custom ditentukan Admin di server; POST tidak bisa membuat izin sendiri.
$roleIdRaw = trim((string)($_POST['input_custom_role'] ?? '0'));
$customRoleId = ctype_digit($roleIdRaw)?(int)$roleIdRaw:-1;
if (!qaRbacReady($konek)) {
    echo json_encode(['status' => 'error', 'message' => 'Tabel Custom Role belum tersedia. Import migrasi role terlebih dahulu.']);
    exit;
}
if ($customRoleId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Pilih Custom Role untuk user.']);
    exit;
}
$test = mysqli_query($konek, 'SELECT id FROM qa_roles WHERE aktif=1 AND id=' . $customRoleId . ' LIMIT 1');
if (!$test || mysqli_num_rows($test) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Custom Role tidak ditemukan atau tidak aktif.']);
    exit;
}
if (!qaSeksiAksesReady($konek)) {
    echo json_encode(['status' => 'error', 'message' => 'Import SQL akses seksi dahulu.']);
    exit;
}
$requestedSeksi = $_POST['qa_seksi_akses'] ?? [];
if (!is_array($requestedSeksi)) {
    echo json_encode(['status' => 'error', 'message' => 'Pilihan seksi tidak valid.']);
    exit;
}
foreach ($requestedSeksi as $item)
    if (!is_string($item)) {
    echo json_encode(['status' => 'error', 'message' => 'Pilihan seksi tidak valid.']);
    exit;
}
$requestedSeksi = array_values(array_unique(array_map('trim', $requestedSeksi)));
$validSeksi = [];
$qSek = mysqli_query($konek, "SELECT nama_seksi FROM qa_seksi_master UNION SELECT DISTINCT seksi_pemilik AS nama_seksi FROM qa_part WHERE seksi_pemilik IS NOT NULL AND TRIM(seksi_pemilik)<>''");
if ($qSek)
    while ($row = mysqli_fetch_assoc($qSek))
        $validSeksi[strtoupper($row['nama_seksi'])] = $row['nama_seksi'];
foreach ($requestedSeksi as $sec)
    if (!isset($validSeksi[strtoupper($sec)])) {
    echo json_encode(['status' => 'error', 'message' => 'Seksi QA tidak dikenal.']);
    exit;
}
function qaSaveUserSeksi($db, $uid, $sections) {
    if (!mysqli_query($db, 'LOCK TABLES qa_user_seksi_akses WRITE'))
        return false;
    try {
        $st = $db->prepare('DELETE FROM qa_user_seksi_akses WHERE uid=?');
        if (!$st)
            return false;
        $st->bind_param('s', $uid);
        if (!$st->execute())
            return false;
        $st->close();
        $st = $db->prepare('INSERT INTO qa_user_seksi_akses (uid,nama_seksi) VALUES (?,?)');
        if (!$st)
            return false;
        foreach ($sections as $sec) {
            $st->bind_param('ss', $uid, $sec);
            if (!$st->execute())
                return false;
        }
        $st->close();
        return true;
    } finally {
        mysqli_query($db, 'UNLOCK TABLES');
    }
}
if ($status === 'tambah') {
    if ($uid === '' || $uname === '' || trim((string)($_POST['input_upwd'] ?? '')) === '') {
        echo json_encode(['status' => 'error', 'message' => 'ID, Nama, dan Password wajib diisi.']);
        exit;
    }
    $chk = $konek->prepare("SELECT uid FROM `user` WHERE uid=? LIMIT 1");
    $chk->bind_param('s', $uid);
    $chk->execute();
    $exists = $chk->get_result()->num_rows > 0;
    $chk->close();
    if ($exists) {
        echo json_encode(['status' => 'error', 'message' => 'ID Pemakai sudah ada.']);
        exit;
    }
    $hash = md5(trim((string)$_POST['input_upwd']));
    $stmt = $konek->prepare("INSERT INTO `user` (uid,uname,pword,admin,role,sapaan,seksi,email_pembuat,pembuat,pemeriksa,penyetuju,block,createdby,crtdate) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
    $stmt->bind_param('sssssssssssss', $uid, $uname, $hash, $admin, $role, $sapaan, $seksi, $email, $pembuat, $pemeriksa, $penyetuju, $block, $creator);
    $ok = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();
    if ($ok && qaRbacReady($konek) && !qaRbacSetUserRole($konek, $uid, $customRoleId)) {
        echo json_encode(['status' => 'error', 'message' => 'User tersimpan tetapi Custom Role gagal dipasang. Edit kembali user ini.']);
        exit;
    }
    if ($ok && !qaSaveUserSeksi($konek, $uid, array_map(fn ($x) => $validSeksi[strtoupper($x)], $requestedSeksi))) {
        echo json_encode(['status' => 'error', 'message' => 'User tersimpan, tetapi akses seksinya gagal. Edit kembali user.']);
        exit;
    }
    echo json_encode($ok?['status' => 'success', 'message' => 'User berhasil ditambahkan.']:['status' => 'error', 'message' => $err?:'Gagal menambahkan user.']);
    exit;
}
if ($status === 'edit') {
    if ($uidOld === '' || $uname === '') {
        echo json_encode(['status' => 'error', 'message' => 'Data user tidak lengkap.']);
        exit;
    }
    // Jangan mengubah admin/role/block/izin legacy hanya karena kotak Status User dihapus.
    $stmt = $konek->prepare("UPDATE `user` SET uname=?,sapaan=?,seksi=?,email_pembuat=?,changeby=?,chgdate=NOW() WHERE uid=?");
    $stmt->bind_param('ssssss', $uname, $sapaan, $seksi, $email, $creator, $uidOld);
    $ok = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();
    if ($ok && !qaRbacSetUserRole($konek, $uidOld, $customRoleId)) {
        echo json_encode(['status' => 'error', 'message' => 'Data user berubah tetapi pemetaan Custom Role gagal. Silakan edit kembali.']);
        exit;
    }
    if ($ok && !qaSaveUserSeksi($konek, $uidOld, array_map(fn ($x) => $validSeksi[strtoupper($x)], $requestedSeksi))) {
        echo json_encode(['status' => 'error', 'message' => 'User berubah, tetapi akses seksinya gagal. Edit kembali user.']);
        exit;
    }
    echo json_encode($ok?['status' => 'success', 'message' => 'User berhasil diperbarui.']:['status' => 'error', 'message' => $err?:'Gagal memperbarui user.']);
    exit;
}
echo json_encode(['status' => 'error', 'message' => 'Status proses tidak valid.']);
?>

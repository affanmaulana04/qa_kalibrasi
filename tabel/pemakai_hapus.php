<?php
session_start();

if (empty($_SESSION['login'])) {
    http_response_code(401);
    exit('Login diperlukan.');
}

include '../../inc/inc_koneksi.php';

$isAdmin = strtoupper((string)($_SESSION['admin'] ?? 'N')) === 'Y'
    || strtolower((string)($_SESSION['role'] ?? '')) === 'admin';

if (!$isAdmin) {
    http_response_code(403);
    exit('Akses ditolak.');
}

$uid = trim((string)($_POST['uid'] ?? ''));

if ($uid === '') {
    exit('ID user tidak valid.');
}

if (strtolower($uid) === 'admin') {
    exit('Id pemakai admin tidak bisa dihapus.');
}

try {
    $konek->begin_transaction();

    // Project lama memiliki tabel user_menu. Jika tabel tersebut masih ada
    // di database lama dan mempunyai relasi ke user, hapus relasinya dulu.
    $hasUserMenu = false;
    $check = $konek->query("SHOW TABLES LIKE 'user_menu'");
    if ($check) {
        $hasUserMenu = $check->num_rows > 0;
        $check->free();
    }

    if ($hasUserMenu) {
        $stmtMenu = $konek->prepare("DELETE FROM `user_menu` WHERE `uid` = ?");
        if (!$stmtMenu) {
            throw new Exception('Gagal menyiapkan penghapusan menu user: ' . $konek->error);
        }
        $stmtMenu->bind_param('s', $uid);
        if (!$stmtMenu->execute()) {
            $err = $stmtMenu->error;
            $stmtMenu->close();
            throw new Exception('Gagal menghapus data menu user: ' . $err);
        }
        $stmtMenu->close();
    }

    $stmt = $konek->prepare("DELETE FROM `user` WHERE `uid` = ?");
    if (!$stmt) {
        throw new Exception('Gagal menyiapkan penghapusan user: ' . $konek->error);
    }

    $stmt->bind_param('s', $uid);

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new Exception('Gagal menghapus user: ' . $err);
    }

    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected < 1) {
        throw new Exception('User ' . $uid . ' tidak ditemukan.');
    }

    $konek->commit();
    echo 'Data ' . htmlspecialchars($uid, ENT_QUOTES, 'UTF-8') . ' berhasil dihapus.';
} catch (Throwable $e) {
    $konek->rollback();
    http_response_code(500);
    echo $e->getMessage();
}
?>

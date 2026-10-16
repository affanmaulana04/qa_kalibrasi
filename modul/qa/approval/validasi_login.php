<?php
/**
 * ============================================================
 * MAGIC LOGIN VALIDASI QA
 * ============================================================
 *
 * URL yang akan dipakai dari email:
 *   validasi_login.php?token=<TOKEN_ACAK>
 *
 * Flow:
 * 1. Token dari URL di-hash SHA-256.
 * 2. Sistem mencari hash token pada qa_validasi_login_token.
 * 3. Cek token aktif + belum expired.
 * 4. Cek user tidak diblokir dan masih memiliki role yang sesuai.
 * 5. Session lama (jika ada) diganti menjadi session penerima email.
 * 6. Redirect langsung ke transaksi_kalibrasi.php.
 *
 * Catatan keamanan:
 * - Token asli tidak disimpan di database.
 * - UID/password tidak pernah ditaruh di URL.
 * - Link email berfungsi sebagai kredensial sementara sampai expired_at.
 * ============================================================
 */

session_start();
date_default_timezone_set('Asia/Jakarta');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

require_once __DIR__ . '/../../../inc/inc_koneksi.php';

/** @var mysqli $konek */
if (!isset($konek) || !($konek instanceof mysqli)) {
    http_response_code(500);
    exit('Koneksi database tidak tersedia.');
}

function qaMagicLoginFail($message, $statusCode = 400)
{
    http_response_code((int)$statusCode);

    $safeMessage = htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8');
    $loginUrl = '../../../index.php';

    echo '<!doctype html>';
    echo '<html lang="id"><head><link rel="icon" type="image/png" href="../../../images/LogoTOTO.png?v=20261013"><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Link Validasi QA</title>';
    echo '<style>';
    echo 'body{margin:0;background:#f4f6f9;font-family:Arial,sans-serif;color:#333}';
    echo '.box{max-width:560px;margin:90px auto;background:#fff;border:1px solid #ddd;border-radius:10px;padding:28px;box-shadow:0 4px 16px rgba(0,0,0,.06)}';
    echo 'h3{margin-top:0;color:#1E3F66}.btn{display:inline-block;margin-top:12px;padding:10px 16px;background:#005AAC;color:#fff;text-decoration:none;border-radius:5px}';
    echo '</style></head><body>';
    echo '<div class="box"><h3>Link Validasi QA Tidak Dapat Digunakan</h3>';
    echo '<p>' . $safeMessage . '</p>';
    echo '<a class="btn" href="' . $loginUrl . '">Ke Halaman Login</a></div>';
    echo '</body></html>';
    exit;
}

$token = trim((string)($_GET['token'] ?? ''));

// Generator token yang akan dibuat pada daily_validasi_reminder.php menggunakan:
// bin2hex(random_bytes(32)) -> tepat 64 karakter hex.
if ($token === '' || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
    qaMagicLoginFail('Token validasi tidak valid atau tidak lengkap.', 400);
}

$tokenHash = hash('sha256', $token);

$stmt = $konek->prepare("
    SELECT
        t.id AS token_id,
        t.user_uid,
        t.jenis_validasi,
        t.expired_at,
        t.status AS token_status,
        u.uid,
        u.uname,
        u.role,
        u.admin,
        u.block,
        u.sapaan,
        u.seksi,
        u.email_pembuat,
        u.pembuat,
        u.pemeriksa,
        u.penyetuju
    FROM qa_validasi_login_token t
    INNER JOIN `user` u
        ON u.uid = t.user_uid
    WHERE t.token_hash = ?
    LIMIT 1
");

if (!$stmt) {
    qaMagicLoginFail('Sistem gagal memeriksa token validasi.', 500);
}

$stmt->bind_param('s', $tokenHash);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    qaMagicLoginFail('Link validasi tidak ditemukan atau sudah tidak berlaku.', 401);
}

if (strtolower(trim((string)$row['token_status'])) !== 'aktif') {
    qaMagicLoginFail('Link validasi sudah dinonaktifkan.', 401);
}

$expiredAt = strtotime((string)$row['expired_at']);
if (!$expiredAt || $expiredAt < time()) {
    qaMagicLoginFail('Masa berlaku link validasi sudah berakhir.', 401);
}

if (strtoupper(trim((string)($row['block'] ?? 'N'))) === 'Y') {
    qaMagicLoginFail('Akun penerima email sedang diblokir.', 403);
}

$jenisValidasi = strtolower(trim((string)$row['jenis_validasi']));

if ($jenisValidasi === 'pemeriksaan') {
    if (strtoupper(trim((string)($row['pemeriksa'] ?? 'N'))) !== 'Y') {
        qaMagicLoginFail('Akun ini tidak lagi memiliki hak sebagai Pemeriksa.', 403);
    }
} elseif ($jenisValidasi === 'persetujuan') {
    if (strtoupper(trim((string)($row['penyetuju'] ?? 'N'))) !== 'Y') {
        qaMagicLoginFail('Akun ini tidak lagi memiliki hak sebagai Penyetuju.', 403);
    }
} else {
    qaMagicLoginFail('Jenis validasi pada link tidak dikenali.', 400);
}

$userUid = trim((string)$row['uid']);
if ($userUid === '') {
    qaMagicLoginFail('Data user penerima email tidak valid.', 401);
}

// Selalu ganti session, termasuk bila browser sebelumnya login sebagai user lain.
session_unset();
session_regenerate_id(true);

$remoteIp = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
$pcName = $remoteIp;
if ($remoteIp !== '') {
    $resolved = @gethostbyaddr($remoteIp);
    if (is_string($resolved) && $resolved !== '') {
        $pcName = $resolved;
    }
}

$_SESSION['jamlogin']        = gmdate('H:i:s', time() + (60 * 60 * 7));
$_SESSION['nama_pc']         = $pcName;
$_SESSION['userid']          = $row['uid'];
$_SESSION['username']        = $row['uname'];
$_SESSION['nama_lengkap']    = $row['uname'];
$_SESSION['role']            = $row['role'];
$_SESSION['login']           = 1;
$_SESSION['admin']           = $row['admin'] ?? 'N';
$_SESSION['sapaan']          = $row['sapaan'] ?? '';
$_SESSION['seksi_user']      = $row['seksi'] ?? '';
$_SESSION['email_user']      = $row['email_pembuat'] ?? '';
$_SESSION['namaemail_user']  = $row['uname'];
$_SESSION['pembuat']         = $row['pembuat'] ?? 'N';
$_SESSION['pemeriksa']       = $row['pemeriksa'] ?? 'N';
$_SESSION['penyetuju']       = $row['penyetuju'] ?? 'N';

// Penanda audit bahwa login ini berasal dari magic link validasi.
$_SESSION['qa_magic_login'] = 1;
$_SESSION['qa_magic_login_jenis'] = $jenisValidasi;

// Samakan status online dengan login normal aplikasi.
$upUser = $konek->prepare("UPDATE `user` SET online='Y', pc_login=? WHERE uid=?");
if ($upUser) {
    $upUser->bind_param('ss', $pcName, $userUid);
    $upUser->execute();
    $upUser->close();
}

// Catat penggunaan token. Token tetap dapat dipakai kembali sampai expired_at
// (berguna bila user menutup browser lalu membuka email yang sama lagi).
$tokenId = (int)$row['token_id'];
$userAgent = substr(trim((string)($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 255);

$upToken = $konek->prepare("
    UPDATE qa_validasi_login_token
    SET used_at = NOW(),
        used_count = used_count + 1,
        last_ip = ?,
        last_user_agent = ?
    WHERE id = ?
    LIMIT 1
");

if ($upToken) {
    $upToken->bind_param('ssi', $remoteIp, $userAgent, $tokenId);
    $upToken->execute();
    $upToken->close();
}

header('Location: transaksi_kalibrasi.php');
exit;

<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sesi login tidak valid.']);
    exit;
}

include "../../inc/inc_koneksi.php";

$action = $_POST['action'] ?? '';
$userId = (string) ($_SESSION['userid'] ?? '');

function postTrim($key) {
    return trim($_POST[$key] ?? '');
}

function jsonError($message, $http = 200) {
    http_response_code($http);
    echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ------------------------------------------------------------
 * REGISTER MASTER PART/ALAT
 * ------------------------------------------------------------ */
if ($action === 'register') {
    $nomor = postTrim('nomor_alat') ?: postTrim('no_part');
    $nama = postTrim('nama_alat') ?: postTrim('nama_part');
    $jenis = postTrim('jenis_part');
    $seksi = postTrim('seksi');
    $seksiId = (int) ($_POST['seksi_id'] ?? 0);
    $periode = (int) ($_POST['periode_kalibrasi'] ?? 0);

    if ($nomor === '' || $nama === '' || $periode < 1) {
        jsonError('Nomor alat, nama alat, dan periode kalibrasi wajib diisi.');
    }

    $seksiPemilikId = 0;
    if ($seksi !== '') {
        $stmt = $konek->prepare("SELECT id FROM qa_seksi_pemilik WHERE status='aktif' AND kode_pemilik=? LIMIT 1");
        $stmt->bind_param('s', $seksi);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $seksiPemilikId = $row ? (int)$row['id'] : 0;
    }
    if ($seksiPemilikId < 1 && $seksiId > 0) {
        // Kompatibilitas untuk caller lama yang sudah mengirim seksi_id.
        $stmt = $konek->prepare("SELECT id FROM qa_seksi WHERE status='aktif' AND id=? LIMIT 1");
        $stmt->bind_param('i', $seksiId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) $seksiId = 0;
    }
    if ($seksiPemilikId < 1 && $seksiId < 1) {
        jsonError('Seksi pemilik wajib dipilih.');
    }

    $stmt = $konek->prepare("SELECT id FROM qa_part WHERE no_part = ? LIMIT 1");
    $stmt->bind_param('s', $nomor);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($exists) {
        jsonError('Nomor alat/part sudah terdaftar.');
    }

    $jadwal = date('Y-m-d', strtotime('+' . $periode . ' days'));

    $stmt = $konek->prepare("INSERT INTO qa_part
        (no_part, jenis_part, nama_part, seksi_id, seksi_pemilik_id, periode_kalibrasi, jadwal_kalibrasi, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif')");
    $stmt->bind_param('sssiiis', $nomor, $jenis, $nama, $seksiId, $seksiPemilikId, $periode, $jadwal);

    if (!$stmt->execute()) {
        $msg = $stmt->error;
        $stmt->close();
        jsonError($msg);
    }
    $alatId = $stmt->insert_id;
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'message' => 'Alat berhasil didaftarkan.',
        'alat_id' => $alatId,
        'jadwal_berikutnya' => $jadwal
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ------------------------------------------------------------
 * SIMPAN TRANSAKSI PENGECEKAN
 * ------------------------------------------------------------ */
if ($action === 'check') {
    $alatId = (int) ($_POST['alat_id'] ?? 0);
    $nomor = postTrim('nomor_alat') ?: postTrim('no_part');
    $status = postTrim('status_qa');
    $seksiQa = postTrim('seksi_qa');
    $keterangan = postTrim('keterangan');
    $tanggal = postTrim('tanggal_pengecekan') ?: date('Y-m-d');

    if ($alatId < 1 && $nomor !== '') {
        $stmt = $konek->prepare("SELECT id FROM qa_part WHERE no_part = ? LIMIT 1");
        $stmt->bind_param('s', $nomor);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $alatId = $row ? (int) $row['id'] : 0;
    }

    if ($alatId < 1) jsonError('Alat/part tidak ditemukan.');
    if (!in_array($status, ['Good', 'Bad'], true)) jsonError('Hasil kalibrasi harus Good atau Bad.');
    if ($seksiQa === '') jsonError('Seksi QA wajib diisi.');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) jsonError('Tanggal pengecekan tidak valid.');

    $stmt = $konek->prepare("SELECT id, periode_kalibrasi, jadwal_kalibrasi, status
        FROM qa_part WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $alatId);
    $stmt->execute();
    $part = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$part || $part['status'] !== 'aktif') jsonError('Data alat/part tidak ditemukan atau sudah nonaktif.');

    $jadwalLama = $part['jadwal_kalibrasi'];
    $periode = (int) $part['periode_kalibrasi'];
    $jadwalBaru = date('Y-m-d', strtotime($tanggal . ' +' . $periode . ' days'));
    $nomorPengecekan = 'QA-' . date('YmdHis') . '-' . $alatId;

    $stmt = $konek->prepare("INSERT INTO qa_pengecekan
        (alat_id, nomor_pengecekan, tanggal_pengecekan,
         tanggal_jadwal_saat_pengecekan, status_kalibrasi, seksi_qa,
         keterangan, periode_berikutnya, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        'issssssss',
        $alatId, $nomorPengecekan, $tanggal, $jadwalLama,
        $status, $seksiQa, $keterangan, $jadwalBaru, $userId
    );

    if (!$stmt->execute()) {
        $msg = $stmt->error;
        $stmt->close();
        jsonError($msg);
    }
    $pengecekanId = $stmt->insert_id;
    $stmt->close();

    /* Jadwal baru selalu dihitung dari tanggal pengecekan aktual. */
    $stmt = $konek->prepare("UPDATE qa_part SET jadwal_kalibrasi = ? WHERE id = ?");
    $stmt->bind_param('si', $jadwalBaru, $alatId);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'message' => 'Hasil kalibrasi berhasil disimpan.',
        'pengecekan_id' => $pengecekanId,
        'jadwal_berikutnya' => $jadwalBaru
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

jsonError('Aksi tidak dikenali.');

<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sesi login tidak valid.']);
    exit;
}

include "../../inc/inc_koneksi.php";

$nomor = trim($_POST['nomor_alat'] ?? '');
if ($nomor === '') {
    echo json_encode(['status' => 'error', 'message' => 'Nomor alat wajib diisi.']);
    exit;
}

$stmt = $konek->prepare("SELECT p.*, s.kode_seksi, s.nama_seksi, sp.kode_pemilik, sp.nama_pemilik
    FROM qa_part p
    LEFT JOIN qa_seksi s ON s.id = p.seksi_id
    LEFT JOIN qa_seksi_pemilik sp ON sp.id = p.seksi_pemilik_id
    WHERE LOWER(TRIM(p.no_part)) = LOWER(TRIM(?))
    LIMIT 1");
$stmt->bind_param('s', $nomor);
$stmt->execute();
$part = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$part) {
    echo json_encode(['status' => 'unregistered']);
    exit;
}

$today = date('Y-m-d');
$warningHari = 7;
$kodeStatus = 'BELUM_TERJADWAL';
$labelStatus = 'Belum Ada Jadwal';

if (!empty($part['jadwal_kalibrasi'])) {
    $selisih = (int) ((strtotime($part['jadwal_kalibrasi']) - strtotime($today)) / 86400);
    if ($selisih < 0) {
        $kodeStatus = 'OVERDUE';
        $labelStatus = 'Jadwal Terlewat';
    } elseif ($selisih === 0) {
        $kodeStatus = 'HARI_INI';
        $labelStatus = 'Jadwal Hari Ini';
    } elseif ($selisih <= $warningHari) {
        $kodeStatus = 'WARNING';
        $labelStatus = 'Segera Dilakukan';
    } else {
        $kodeStatus = 'TERJADWAL';
        $labelStatus = 'Terjadwal';
    }
}

$stmt = $konek->prepare("SELECT id, nomor_pengecekan, tanggal_pengecekan,
        tanggal_jadwal_saat_pengecekan, status_kalibrasi, seksi_qa,
        keterangan, created_by, created_at
    FROM qa_pengecekan
    WHERE alat_id = ?
    ORDER BY COALESCE(tanggal_pengecekan, DATE(created_at)) DESC, id DESC
    LIMIT 5");
$stmt->bind_param('i', $part['id']);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$part['nomor_alat'] = $part['no_part'];
$part['nama_alat'] = $part['nama_part'];
foreach (['ukuran','no_fa','resolusi','merk','lokasi','no_seri','type','kls'] as $field) {
    if (!array_key_exists($field, $part)) $part[$field] = null;
}
$part['seksi'] = $part['kode_pemilik'] ?: ($part['kode_seksi'] ?: ($part['nama_seksi'] ?: '-'));
$part['seksi_pemilik'] = $part['kode_pemilik'] ?: ($part['nama_seksi'] ?: '-');
$part['status_jadwal'] = [
    'kode' => $kodeStatus,
    'label' => $labelStatus
];
$part['history'] = $history;
$part['tasks'] = [];

// Task Scheduler yang dimaksud sistem adalah scheduler Windows untuk proses email,
// bukan daftar task yang disimpan di database.

echo json_encode(['status' => 'registered', 'data' => $part], JSON_UNESCAPED_UNICODE);

<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sesi login tidak valid.']);
    exit;
}

include "../../inc/inc_koneksi.php";

$nomor = strtoupper(trim((string)($_POST['nomor_alat'] ?? '')));
if ($nomor === '') {
    echo json_encode(['status' => 'error', 'message' => 'Nomor alat wajib diisi.']);
    exit;
}

$nomorEsc = mysqli_real_escape_string($konek, $nomor);

/* ------------------------------------------------------------------
 * Registry adalah sumber kebenaran nomor alat.
 * NO ALAT selalu murni dan hanya ada satu baris di qa_alat_registry.
 * ------------------------------------------------------------------ */
$stmt = $konek->prepare("SELECT * FROM qa_alat_registry
    WHERE UPPER(TRIM(no_part)) = UPPER(TRIM(?))
    LIMIT 1");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal membaca registry nomor alat: ' . $konek->error]);
    exit;
}
$stmt->bind_param('s', $nomor);
$stmt->execute();
$registry = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* Fallback untuk DB sebelum migration: baca qa_part langsung. */
if (!$registry) {
    $stmt = $konek->prepare("SELECT * FROM qa_part
        WHERE UPPER(TRIM(no_part)) = UPPER(TRIM(?))
        LIMIT 1");
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal membaca data alat: ' . $konek->error]);
        exit;
    }
    $stmt->bind_param('s', $nomor);
    $stmt->execute();
    $part = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$part) {
        echo json_encode(['status' => 'unregistered']);
        exit;
    }

    $today = date('Y-m-d');
    $kodeStatus = 'BELUM_TERJADWAL';
    $labelStatus = 'Belum Ada Jadwal';
    if (!empty($part['jadwal_kalibrasi'])) {
        $selisih = (int)((strtotime($part['jadwal_kalibrasi']) - strtotime($today)) / 86400);
        if ($selisih < 0) {
            $kodeStatus = 'OVERDUE';
            $labelStatus = 'Jadwal Terlewat';
        } elseif ($selisih === 0) {
            $kodeStatus = 'HARI_INI';
            $labelStatus = 'Jadwal Hari Ini';
        } else {
            $kodeStatus = 'TERJADWAL';
            $labelStatus = 'Terjadwal';
        }
    }

    $part['nomor_alat'] = $part['no_part'];
    $part['nama_alat'] = $part['nama_part'];
    $part['seksi'] = $part['seksi_pemilik'] ?: '-';
    $part['seksi_pemilik'] = $part['seksi_pemilik'] ?: '-';
    $part['status_jadwal'] = ['kode' => $kodeStatus, 'label' => $labelStatus];
    $part['history'] = [];
    $part['tasks'] = [];

    echo json_encode(['status' => 'registered', 'data' => $part], JSON_UNESCAPED_UNICODE);
    exit;
}

$registryId = (int)($registry['id'] ?? 0);
$registryStatus = strtoupper(trim((string)($registry['status_alat'] ?? '')));
$archiveStatus = (string)($registry['alasan_status'] ?? ($registry['hasil_terakhir'] ?? ''));

/* ------------------------------------------------------------------
 * Ambil master aktif / data arsip sesuai state registry.
 * ------------------------------------------------------------------ */
$part = null;
$sourceTable = '';

if ($registryStatus === 'AKTIF') {
    $stmt = $konek->prepare("SELECT * FROM qa_part
        WHERE alat_registry_id = ? OR UPPER(TRIM(no_part)) = UPPER(TRIM(?))
        LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('is', $registryId, $nomor);
        $stmt->execute();
        $part = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    $sourceTable = 'qa_part';
} else {
    $allowedTables = [
        'qa_alat_ng' => 'NOT GOOD',
        'qa_alat_rusak' => 'RUSAK',
        'qa_alat_discontinue' => 'DISCONTINUE',
        'qa_alat_ditarik' => 'DITARIK MECHANICAL'
    ];

    $sourceTable = (string)($registry['sumber_tabel'] ?? '');
    if (!isset($allowedTables[$sourceTable])) {
        $sourceTable = '';
        foreach ($allowedTables as $table => $label) {
            if (strcasecmp($label, $archiveStatus) === 0) {
                $sourceTable = $table;
                break;
            }
        }
    }

    /* Legacy sementara yang belum pernah dipindahkan ke archive:
       tetap bisa ditampilkan dari qa_part. */
    if ($sourceTable === '') {
        $sourceTable = 'qa_part';
    }

    $stmt = $konek->prepare("SELECT * FROM `$sourceTable`
        WHERE alat_registry_id = ? OR UPPER(TRIM(no_part)) = UPPER(TRIM(?))
        ORDER BY id DESC
        LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('is', $registryId, $nomor);
        $stmt->execute();
        $part = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

/* ------------------------------------------------------------------
 * Histori berdasarkan registry_id; fallback no_part untuk data lama.
 * ------------------------------------------------------------------ */
$history = [];
$stmt = $konek->prepare("SELECT id, alat_id, alat_registry_id, nomor_pengecekan,
        jenis_transaksi, no_laporan, tanggal_pengecekan,
        tanggal_jadwal_saat_pengecekan, status_kalibrasi, status_validasi,
        seksi_qa, keterangan, created_by, operator, created_at
    FROM qa_pengecekan
    WHERE alat_registry_id = ?
       OR UPPER(TRIM(no_part)) = UPPER(TRIM(?))
    ORDER BY COALESCE(tanggal_pengecekan, DATE(created_at)) DESC, id DESC
    LIMIT 5");
if ($stmt) {
    $stmt->bind_param('is', $registryId, $nomor);
    $stmt->execute();
    $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

/* ------------------------------------------------------------------
 * Format response.
 * ------------------------------------------------------------------ */
if ($part) {
    $part['nomor_alat'] = $part['no_part'];
    $part['nama_alat'] = $part['nama_part'];
    foreach (['ukuran','no_fa','resolusi','merk','lokasi','no_seri','type','kls','fungsi'] as $field) {
        if (!array_key_exists($field, $part)) $part[$field] = null;
    }
    $part['seksi'] = $part['seksi_pemilik'] ?: '-';
    $part['seksi_pemilik'] = $part['seksi_pemilik'] ?: '-';
}

if ($registryStatus === 'AKTIF' && $part) {
    $today = date('Y-m-d');
    $kodeStatus = 'BELUM_TERJADWAL';
    $labelStatus = 'Belum Ada Jadwal';
    if (!empty($part['jadwal_kalibrasi'])) {
        $selisih = (int)((strtotime($part['jadwal_kalibrasi']) - strtotime($today)) / 86400);
        if ($selisih < 0) {
            $kodeStatus = 'OVERDUE';
            $labelStatus = 'Jadwal Terlewat';
        } elseif ($selisih === 0) {
            $kodeStatus = 'HARI_INI';
            $labelStatus = 'Jadwal Hari Ini';
        } else {
            $kodeStatus = 'TERJADWAL';
            $labelStatus = 'Terjadwal';
        }
    }
    $part['status_jadwal'] = ['kode' => $kodeStatus, 'label' => $labelStatus];
}

$part['history'] = $history;
$part['tasks'] = [];
$part['registry_id'] = $registryId;
$part['registry_status'] = $registryStatus;
$part['alasan_status'] = $archiveStatus;
$part['nomor_arsip'] = (string)($registry['nomor_arsip_terakhir'] ?? '');
$part['source_table'] = $sourceTable;

if ($registryStatus === 'AKTIF' && $part) {
    echo json_encode(['status' => 'registered', 'data' => $part, 'registry' => $registry], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($registryStatus === 'BLOKIR_PERMANEN') {
    echo json_encode([
        'status' => 'blocked',
        'data' => $part,
        'registry' => $registry,
        'history' => $history,
        'message' => 'Nomor alat ini diblokir permanen karena ' . ($archiveStatus ?: 'RUSAK/NOT GOOD') . ' dan tidak dapat digunakan kembali.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'status' => 'inactive',
    'data' => $part,
    'registry' => $registry,
    'history' => $history,
    'can_restore' => (strtoupper(trim((string)($_SESSION['admin'] ?? 'N'))) === 'Y' || strtolower(trim((string)($_SESSION['role'] ?? ''))) === 'admin'),
    'message' => 'Nomor alat sedang nonaktif karena ' . ($archiveStatus ?: 'status sementara') . '. Hanya Admin yang dapat mengaktifkannya kembali.'
], JSON_UNESCAPED_UNICODE);

<?php
/**
 * proses_qa.php
 * ---------------------------------------------------------------------
 * QA Tool Calibration System - Backend Logic & AJAX Endpoints
 *
 * Perubahan dari versi sebelumnya (lihat catatan di kepala tiap fungsi):
 *   - [MEETING] Kolom "operator" (petugas yang benar-benar mengecek alat)
 *   - [MEETING] Alur Validator 2 tahap: Pemeriksa -> Penyetuju
 *   - [MEETING] Keterangan validator OPSIONAL (tidak wajib)
 *   - [MEETING] No. alat berstatus "Bad" tidak bisa dipakai ulang secara langsung
 *   - [MEETING] Status "NOT GOOD" dibakukan menjadi "Bad" di seluruh sistem
 *   - [FIX] Normalisasi no_part (trim + uppercase) di semua query pencarian
 *   - [FIX] Validasi periode_kalibrasi harus integer positif
 *   - [FIX] Proses status Bad dibungkus transaction (all-or-nothing)
 *   - [FIX] Auto-migration kolom baru agar tidak perlu ALTER TABLE manual
 * ---------------------------------------------------------------------
 */

session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

include "../../inc/inc_koneksi.php";
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sesi login tidak valid. Silakan login ulang.']);
    exit;
}

$action        = isset($_GET['action']) ? $_GET['action'] : '';
$id_pemakai     = isset($_SESSION['userid']) ? $_SESSION['userid'] : '';
$nama_petugas   = isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : (isset($_SESSION['nama_lengkap']) && $_SESSION['nama_lengkap'] !== '' ? $_SESSION['nama_lengkap'] : ($id_pemakai ?: 'SISTEM'));

/* =====================================================================
 * HELPER: normalisasi payload hasil JSON sekaligus menyimpan snapshot
 * header/footer pada saat pengecekan.
 * ---------------------------------------------------------------------
 * Tidak menambah tabel/kolom baru. Snapshot disimpan di qa_pengecekan.hasil_json
 * sehingga histori lama tetap immutable walaupun master qa_part diedit.
 * =================================================================== */
function build_hasil_snapshot_json($rawJson, $meta)
{
    $decoded = json_decode($rawJson, true);

    if (!is_array($decoded)) {
        $rows = [];
    } elseif (isset($decoded['rows']) && is_array($decoded['rows'])) {
        $rows = $decoded['rows'];
    } elseif (isset($decoded['hasil_array']) && is_array($decoded['hasil_array'])) {
        $rows = $decoded['hasil_array'];
    } else {
        $rows = $decoded;
    }

    $payload = [
        'version' => 2,
        'format'  => (string)($meta['format'] ?? ''),
        'header'  => [
            'no_part'          => (string)($meta['no_part'] ?? ''),
            'nama_part'        => (string)($meta['nama_part'] ?? ''),
            'seksi_pemilik'    => (string)($meta['seksi_pemilik'] ?? ''),
            'spesifikasi'      => (string)($meta['spesifikasi'] ?? ''),
            'resolusi'         => (string)($meta['resolusi'] ?? ''),
            'lokasi'           => (string)($meta['lokasi'] ?? ''),
            'metode_uji'       => (string)($meta['metode_uji'] ?? ''),
            'standar_uji'      => (string)($meta['standar_uji'] ?? ''),
            'traceability'     => (string)($meta['traceability'] ?? ''),
            'toleransi_global' => (string)($meta['toleransi_global'] ?? ''),
            'satuan'           => (string)($meta['satuan'] ?? ''),
            'format_laporan'   => (string)($meta['format'] ?? ''),
        ],
        'environment' => [
            'suhu'             => (string)($meta['suhu'] ?? ''),
            'kelembapan'       => (string)($meta['kelembapan'] ?? ''),
            'ketidakpastian'   => (string)($meta['ketidakpastian'] ?? ''),
            'confidence_level' => (string)($meta['confidence_level'] ?? ''),
        ],
        'footer' => [
            'keterangan' => (string)($meta['keterangan'] ?? ''),
            'petugas'    => (string)($meta['petugas'] ?? ''),
            'operator'   => (string)($meta['operator'] ?? ''),
        ],
        'rows' => array_values($rows),
    ];

    return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/* =====================================================================
 * HELPER: normalisasi nomor alat (trim + uppercase) supaya "ab-1" dan
 * "AB-1" selalu dianggap alat yang sama. [FIX BUG]
 * =================================================================== */
function normalisasi_no_part($konek, $raw)
{
    return mysqli_real_escape_string($konek, strtoupper(trim($raw)));
}
function esc($konek, $value) { return mysqli_real_escape_string($konek, (string)$value); }

function qaIsAdmin()
{
    $role = strtolower(trim((string)($_SESSION['role'] ?? '')));
    $admin = strtoupper(trim((string)($_SESSION['admin'] ?? 'N')));
    return $admin === 'Y' || $admin === '1' || $role === 'admin';
}

function qaUserFlags($konek)
{
    $uid = trim((string)($_SESSION['userid'] ?? ''));
    $uname = trim((string)($_SESSION['username'] ?? ''));
    $row = null;
    if ($uid !== '') {
        $stmt = $konek->prepare("SELECT pembuat,pemeriksa,penyetuju FROM `user` WHERE uid=? LIMIT 1");
        if ($stmt) { $stmt->bind_param('s',$uid); $stmt->execute(); $row=$stmt->get_result()->fetch_assoc(); $stmt->close(); }
    }
    if (!$row && $uname !== '') {
        $stmt = $konek->prepare("SELECT pembuat,pemeriksa,penyetuju FROM `user` WHERE uname=? LIMIT 1");
        if ($stmt) { $stmt->bind_param('s',$uname); $stmt->execute(); $row=$stmt->get_result()->fetch_assoc(); $stmt->close(); }
    }
    $row = $row ?: ['pembuat'=>'N','pemeriksa'=>'N','penyetuju'=>'N'];
    return [
        'pembuat' => strtoupper((string)($row['pembuat'] ?? 'N')) === 'Y',
        'pemeriksa' => strtoupper((string)($row['pemeriksa'] ?? 'N')) === 'Y',
        'penyetuju' => strtoupper((string)($row['penyetuju'] ?? 'N')) === 'Y'
    ];
}
function qaCanPembuat($konek){ $f=qaUserFlags($konek); return qaIsAdmin() || $f['pembuat']; }
function qaCanPemeriksa($konek){ $f=qaUserFlags($konek); return qaIsAdmin() || $f['pemeriksa']; }
function qaCanPenyetuju($konek){ $f=qaUserFlags($konek); return qaIsAdmin() || $f['penyetuju']; }

function qaArchiveDefs(){
    return [
        'NOT GOOD' => ['table'=>'qa_alat_ng','code'=>'NOTGOOD','permanent'=>false,'label'=>'NOT GOOD'],
        'RUSAK' => ['table'=>'qa_alat_rusak','code'=>'RUSAK','permanent'=>true,'label'=>'RUSAK'],
        'DISCONTINUE' => ['table'=>'qa_alat_discontinue','code'=>'DISCONTINUE','permanent'=>false,'label'=>'DISCONTINUE'],
        'DITARIK MECHANICAL' => ['table'=>'qa_alat_ditarik','code'=>'DITARIK','permanent'=>false,'label'=>'DITARIK MECHANICAL'],
    ];
}
function qaNormalizeFinalStatus($raw){
    $s=strtoupper(trim((string)$raw));
    $map=['GOOD'=>'OK','OK'=>'OK','BAD'=>'NOT GOOD','NOT GOOD'=>'NOT GOOD','BURUK'=>'NOT GOOD','RUSAK'=>'RUSAK','DISCONTINUE'=>'DISCONTINUE','DITARIK'=>'DITARIK MECHANICAL','DITARIK MECHANICAL'=>'DITARIK MECHANICAL'];
    return $map[$s] ?? '';
}
function qaArchiveNoPart($original,$code){ return '_'.$code.'_'.date('YmdHis'); }
function qaDisplayStatus($status, $validation){
    $final = qaNormalizeFinalStatus($status);
    $validation = strtolower(trim((string)$validation));
    if ($validation === 'final_disetujui') return $final !== '' ? $final : 'FINAL';
    if ($validation === 'ditolak_pemeriksa') return 'DITOLAK PEMERIKSA';
    if ($validation === 'ditolak_penyetuju') return 'DITOLAK PENYETUJU';
    return 'MENUNGGU VALIDASI';
}

function qaExtractOriginalNoPart($archiveNo){
    $archiveNo=trim((string)$archiveNo);
    $parsed=preg_replace('/_(?:NOTGOOD|RUSAK|DISCONTINUE|DITARIK)_\d{14}$/i','',$archiveNo);
    return ($parsed === '' || $parsed === null) ? $archiveNo : $parsed;
}

/* =====================================================================
 * HELPER: pastikan kolom tertentu ada di tabel (auto-migration ringan).
 * Dipanggil sekali saat file di-load supaya deploy tidak perlu ALTER
 * TABLE manual untuk field-field baru hasil meeting.
 * =================================================================== */
function pastikan_kolom($konek, $table, $kolom, $definisi)
{
    $cek = mysqli_query($konek, "SHOW COLUMNS FROM `$table` LIKE '$kolom'");
    if ($cek && mysqli_num_rows($cek) === 0) {
        mysqli_query($konek, "ALTER TABLE `$table` ADD COLUMN `$kolom` $definisi");
    }
}

/* --- Auto-migration: kolom baru di qa_pengecekan --- */
pastikan_kolom($konek, 'qa_pengecekan', 'operator', "VARCHAR(100) NULL AFTER created_by");
pastikan_kolom($konek, 'qa_pengecekan', 'status_validasi', "VARCHAR(30) NOT NULL DEFAULT 'menunggu_pemeriksa' AFTER status_kalibrasi");
pastikan_kolom($konek, 'qa_pengecekan', 'jenis_transaksi', "VARCHAR(40) NULL AFTER nomor_pengecekan");
pastikan_kolom($konek, 'qa_pengecekan', 'no_laporan', "VARCHAR(150) NULL AFTER nomor_pengecekan");
foreach (['qa_alat_ng','qa_alat_rusak','qa_alat_discontinue','qa_alat_ditarik'] as $archiveTable) {
    mysqli_query($konek, "CREATE TABLE IF NOT EXISTS `$archiveTable` LIKE `qa_part`");
    pastikan_kolom($konek, $archiveTable, 'nomor_arsip', "VARCHAR(150) NULL AFTER no_part");
}

/* Laporan Harian/Bulanan/Tahunan juga memakai validator 2 tahap yang sama. */
mysqli_query($konek, "CREATE TABLE IF NOT EXISTS qa_laporan_transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenis_transaksi VARCHAR(40) NOT NULL,
    periode_key VARCHAR(30) NOT NULL,
    periode_label VARCHAR(120) NOT NULL,
    status_validasi VARCHAR(40) NOT NULL DEFAULT 'menunggu_pemeriksa',
    created_by VARCHAR(100) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    payload_json LONGTEXT NULL,
    UNIQUE KEY uq_jenis_periode (jenis_transaksi, periode_key),
    INDEX idx_laporan_status (status_validasi),
    INDEX idx_laporan_periode (periode_key)
)");
mysqli_query($konek, "CREATE TABLE IF NOT EXISTS qa_laporan_validasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    laporan_id INT NOT NULL,
    tahap ENUM('pemeriksa','penyetuju') NOT NULL,
    keputusan ENUM('setuju','tolak') NOT NULL,
    nama_validator VARCHAR(100) NOT NULL,
    keterangan VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_laporan_validasi (laporan_id)
)");

/* --- Auto-migration: tabel validator (audit trail pemeriksa & penyetuju) --- */
mysqli_query($konek, "CREATE TABLE IF NOT EXISTS qa_validasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pengecekan_id INT NOT NULL,
    tahap ENUM('pemeriksa','penyetuju') NOT NULL,
    keputusan ENUM('setuju','tolak') NOT NULL,
    nama_validator VARCHAR(100) NOT NULL,
    keterangan VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_pengecekan (pengecekan_id)
)");

/* =====================================================================
 * FINALISASI HASIL setelah kedua validator menyetujui.
 * Hanya di sini hasil OK/RUSAK/DISCONTINUE/DITARIK dinyatakan final.
 * =================================================================== */
function qaFinalizeApprovedResult($konek, $pengecekan_id)
{
    $q = mysqli_query($konek, "SELECT * FROM qa_pengecekan WHERE id='".(int)$pengecekan_id."' LIMIT 1");
    $row = $q ? mysqli_fetch_assoc($q) : null;
    if (!$row) throw new Exception('Data pengecekan tidak ditemukan.');

    $status = qaNormalizeFinalStatus($row['status_kalibrasi'] ?? '');
    $alat_id = (int)($row['alat_id'] ?? 0);
    $periode = 30;
    $part = null;
    if ($alat_id > 0) {
        $qp = mysqli_query($konek, "SELECT * FROM qa_part WHERE id='$alat_id' LIMIT 1");
        $part = $qp ? mysqli_fetch_assoc($qp) : null;
    }

    if ($status === 'OK') {
        if (!$part) throw new Exception('Alat aktif tidak ditemukan untuk finalisasi status OK.');
        $periode = !empty($part['periode_kalibrasi']) ? (int)$part['periode_kalibrasi'] : 30;
        $tgl = $row['tanggal_pengecekan'] ?: date('Y-m-d');
        $jadwal = date('Y-m-d', strtotime($tgl.' +'.$periode.' days'));
        if (!mysqli_query($konek, "UPDATE qa_part SET status='aktif', jadwal_kalibrasi='".mysqli_real_escape_string($konek,$jadwal)."' WHERE id='$alat_id' LIMIT 1")) {
            throw new Exception('Gagal memperbarui jadwal alat: '.mysqli_error($konek));
        }
        if (!mysqli_query($konek, "UPDATE qa_pengecekan SET periode_berikutnya='".mysqli_real_escape_string($konek,$jadwal)."' WHERE id='".(int)$pengecekan_id."' LIMIT 1")) {
            throw new Exception('Gagal memperbarui periode berikutnya: '.mysqli_error($konek));
        }
        return;
    }

    $defs = qaArchiveDefs();
    $def = $defs[$status] ?? null;
    if (!$def) throw new Exception('Status final tidak didukung untuk finalisasi.');
    if (!$part) throw new Exception('Alat aktif tidak ditemukan untuk finalisasi status '.$status.'.');

    // Pastikan tabel arsip status ini memang ada sebelum proses pindah data.
    // Ini mencegah tombol validasi/restore gagal hanya karena tabel arsip belum dibuat.
    if (!mysqli_query($konek, "CREATE TABLE IF NOT EXISTS `{$def['table']}` LIKE `qa_part`")) {
        throw new Exception('Gagal menyiapkan tabel arsip '.$def['table'].': '.mysqli_error($konek));
    }

    // Nomor alat tetap sama. Identitas arsip disimpan terpisah pada nomor_arsip
    // dengan format: _STATUS_YYYYMMDDHHMMSS
    pastikan_kolom($konek, $def['table'], 'nomor_arsip', "VARCHAR(150) NULL AFTER no_part");
    $originalNo = (string)$part['no_part'];
    $originalEsc = mysqli_real_escape_string($konek, $originalNo);
    $archiveNo = qaArchiveNoPart($originalNo, $def['code']);
    $archiveNoEsc = mysqli_real_escape_string($konek, $archiveNo);
    $exists = mysqli_query($konek, "SELECT id FROM `{$def['table']}` WHERE nomor_arsip='$archiveNoEsc' LIMIT 1");
    if ($exists && mysqli_num_rows($exists) > 0) throw new Exception('Nomor arsip bentrok.');

    // Tabel arsip memiliki kolom tambahan nomor_arsip, jadi jangan gunakan
    // INSERT ... SELECT * karena jumlah kolom bisa tidak sama. Salin hanya
    // kolom yang memang berasal dari qa_part.
    $kolomPart = [];
    $qKolomPart = mysqli_query($konek, "SHOW COLUMNS FROM `qa_part`");
    if (!$qKolomPart) {
        throw new Exception('Gagal membaca struktur qa_part: '.mysqli_error($konek));
    }
    while ($c = mysqli_fetch_assoc($qKolomPart)) {
        $field = str_replace('`', '``', $c['Field']);
        $kolomPart[] = '`'.$field.'`';
    }
    if (!$kolomPart) {
        throw new Exception('Kolom qa_part tidak ditemukan.');
    }
    $kolomInsert = implode(',', $kolomPart);
    $sqlInsertArsip = "INSERT INTO `{$def['table']}` ($kolomInsert) SELECT $kolomInsert FROM `qa_part` WHERE id='$alat_id' LIMIT 1";
    if (!mysqli_query($konek, $sqlInsertArsip)) {
        throw new Exception('Gagal memindahkan alat ke arsip: '.mysqli_error($konek));
    }
    $statusArchive = mysqli_real_escape_string($konek, strtolower($status));
    if (!mysqli_query($konek, "UPDATE `{$def['table']}` SET no_part='$originalEsc', nomor_arsip='$archiveNoEsc', status='$statusArchive', jadwal_kalibrasi=NULL WHERE id='$alat_id' LIMIT 1")) {
        throw new Exception('Gagal memberi nomor arsip: '.mysqli_error($konek));
    }
    if (!mysqli_query($konek, "UPDATE qa_pengecekan SET no_part='$originalEsc', periode_berikutnya=NULL WHERE id='".(int)$pengecekan_id."' LIMIT 1")) {
        throw new Exception('Gagal mengunci histori hasil final: '.mysqli_error($konek));
    }
    if (!mysqli_query($konek, "DELETE FROM qa_part WHERE id='$alat_id' LIMIT 1")) {
        throw new Exception('Gagal menghapus master aktif setelah finalisasi arsip: '.mysqli_error($konek));
    }
}

/* =====================================================================
 * ACTION: cek_alat
 * Cek status sebuah alat: terdaftar aktif, belum terdaftar, atau sudah
 * divonis NOT GOOD/RUSAK/DISCONTINUE/DITARIK MECHANICAL (di tabel qa_alat_rusak).
 * =================================================================== */
if ($action == 'cek_alat') {
    try {
        $no_part = isset($_POST['no_part']) ? normalisasi_no_part($konek, $_POST['no_part']) : '';

        if ($no_part === '') {
            echo json_encode(['found' => false, 'error_msg' => 'Nomor alat wajib diisi.']);
            exit;
        }

        $query = mysqli_query($konek, "SELECT * FROM qa_part WHERE UPPER(TRIM(no_part)) = '$no_part'");

        if ($query && mysqli_num_rows($query) > 0) {
            $data_alat = mysqli_fetch_assoc($query);
            $history = [];

            // Limit riwayat ke 5 baris terakhir saja (mencegah payload besar / bocor data historis penuh)
            $q_hist = mysqli_query($konek, "SELECT * FROM qa_pengecekan WHERE alat_id = '{$data_alat['id']}' ORDER BY tanggal_pengecekan DESC, id DESC LIMIT 5");
            if ($q_hist) {
                while ($row = mysqli_fetch_assoc($q_hist)) {
                    $history[] = [
                        'id'                => $row['id'],
                        'tanggal'           => $row['tanggal_pengecekan'],
                        'status'            => $row['status_kalibrasi'],           // hasil kandidat/internal
                        'status_validasi'   => isset($row['status_validasi']) ? $row['status_validasi'] : 'menunggu_pemeriksa',
                        'display_status'    => qaDisplayStatus($row['status_kalibrasi'] ?? '', $row['status_validasi'] ?? 'menunggu_pemeriksa'),
                        'seksi_qa'          => $row['seksi_qa'],
                        'operator'          => isset($row['operator']) ? $row['operator'] : null, // [MEETING] operator pengecek
                        'suhu'              => $row['suhu'],
                        'kelembapan'        => $row['kelembapan'],
                        'ketidakpastian'    => $row['ketidakpastian'],
                        'confidence_level'  => $row['confidence_level'],
                        'keterangan'        => $row['keterangan'],
                        'hasil_json'        => $row['hasil_json'],
                        'petugas'           => $row['created_by'],
                        'jadwal_berikutnya' => $row['periode_berikutnya']
                    ];
                }
            }
            echo json_encode(['found' => true, 'data' => $data_alat, 'history' => $history]);
        } else {
            // Cari seluruh arsip status negatif berdasarkan nomor alat asli.
            foreach (qaArchiveDefs() as $statusLabel=>$def) {
                $table=$def['table'];
                $qArc=mysqli_query($konek, "SELECT * FROM `$table` WHERE UPPER(TRIM(no_part)) LIKE '{$no_part}\\_{$def['code']}\\_%' ORDER BY id DESC LIMIT 1");
                if ($qArc && mysqli_num_rows($qArc)>0) {
                    $arc=mysqli_fetch_assoc($qArc);
                    $history=[];
                    $arcNo=$arc['no_part'];
                    $qhh=mysqli_query($konek,"SELECT id,tanggal_pengecekan,status_kalibrasi,status_validasi,operator,created_by,keterangan,periode_berikutnya,hasil_json FROM qa_pengecekan WHERE UPPER(TRIM(no_part)) = '".mysqli_real_escape_string($konek,$arcNo)."' OR UPPER(TRIM(no_part)) = '".mysqli_real_escape_string($konek,$no_part)."' ORDER BY id DESC LIMIT 5");
                    if($qhh){while($hr=mysqli_fetch_assoc($qhh)){$hr['display_status']=qaDisplayStatus($hr['status_kalibrasi']??'', $hr['status_validasi']??'menunggu_pemeriksa');$history[]=$hr;}}
                    echo json_encode([
                        'found'=>false,'is_archived'=>true,'is_rusak'=>$def['permanent'],
                        'archive_status'=>$statusLabel,'archive_table'=>$table,'archive_no_part'=>$arcNo,
                        'original_no_part'=>$no_part,'can_restore'=>(qaIsAdmin() && !$def['permanent']),
                        'archive_data'=>$arc,'history'=>$history,
                        'message'=>$def['permanent']
                            ? "Nomor alat ini sudah berstatus RUSAK dan permanen tidak dapat digunakan kembali."
                            : "Nomor alat ini pernah berstatus {$statusLabel}. Admin dapat mengembalikan data alat yang sama."
                    ]); exit;
                }
            }
            echo json_encode(['found' => false]);
        }
    } catch (Exception $e) {
        echo json_encode(['found' => false, 'error_msg' => $e->getMessage()]);
    }
    exit;
}

/* =====================================================================
 * ACTION: simpan_alat
 * Registrasi alat baru. Menolak jika no_part masih aktif ATAU jika
 * no_part yang diminta adalah "hantu" dari nomor yang sudah divonis NOT GOOD/RUSAK/DISCONTINUE/DITARIK MECHANICAL
 * (mengandung pola arsip internal) - [MEETING] no alat Bad tidak bisa dipakai lagi.
 * =================================================================== */
elseif ($action == 'restore_alat') {
    if (!qaIsAdmin()) {
        http_response_code(403);
        echo json_encode(['status'=>'error','message'=>'Hanya admin yang dapat mengembalikan nomor alat.']);
        exit;
    }

    $archiveTable = preg_replace('/[^a-z_]/', '', strtolower((string)($_POST['archive_table'] ?? '')));
    $archiveNo = trim((string)($_POST['archive_no_part'] ?? ''));
    $allowed = array_column(qaArchiveDefs(), 'table');

    if (!in_array($archiveTable, $allowed, true) || $archiveNo === '') {
        echo json_encode(['status'=>'error','message'=>'Data arsip tidak valid.']);
        exit;
    }

    if ($archiveTable === 'qa_alat_rusak') {
        echo json_encode(['status'=>'error','message'=>'Alat RUSAK bersifat permanen dan tidak dapat dikembalikan.']);
        exit;
    }

    // Pastikan tabel arsip tersedia.
    if (!mysqli_query($konek, "CREATE TABLE IF NOT EXISTS `$archiveTable` LIKE `qa_part`")) {
        echo json_encode(['status'=>'error','message'=>'Gagal menyiapkan tabel arsip: '.mysqli_error($konek)]);
        exit;
    }

    $archiveNoEsc = mysqli_real_escape_string($konek, $archiveNo);
    $original = qaExtractOriginalNoPart($archiveNo);
    $originalEsc = mysqli_real_escape_string($konek, $original);

    mysqli_begin_transaction($konek);
    try {
        $q = mysqli_query($konek, "SELECT * FROM `$archiveTable` WHERE no_part='$archiveNoEsc' LIMIT 1");
        if (!$q) throw new Exception('Gagal membaca data arsip: '.mysqli_error($konek));
        $arc = mysqli_fetch_assoc($q);
        if (!$arc) throw new Exception('Data arsip tidak ditemukan.');

        $ex = mysqli_query($konek, "SELECT id FROM qa_part WHERE UPPER(TRIM(no_part))='$originalEsc' LIMIT 1");
        if (!$ex) throw new Exception('Gagal mengecek nomor alat aktif: '.mysqli_error($konek));
        if (mysqli_num_rows($ex) > 0) throw new Exception('Nomor alat sudah digunakan kembali di data aktif.');

        $set = [];
        foreach ($arc as $column => $value) {
            if ($column === 'id' || $column === 'no_part' || $column === 'status') continue;
            $colSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
            if ($value === null) {
                $set[] = "`$colSafe`=NULL";
            } else {
                $set[] = "`$colSafe`='".mysqli_real_escape_string($konek, (string)$value)."'";
            }
        }
        $set[] = "`no_part`='$originalEsc'";
        $set[] = "`status`='aktif'";

        $sqlInsert = 'INSERT INTO qa_part SET '.implode(',', $set);
        if (!mysqli_query($konek, $sqlInsert)) {
            throw new Exception('Gagal mengembalikan data ke qa_part: '.mysqli_error($konek));
        }
        $newId = (int)mysqli_insert_id($konek);
        if ($newId <= 0) throw new Exception('ID alat hasil restore tidak terbentuk.');

        $sqlHist = "UPDATE qa_pengecekan
                     SET alat_id='$newId', no_part='$originalEsc'
                     WHERE UPPER(TRIM(no_part))='$archiveNoEsc'";
        if (!mysqli_query($konek, $sqlHist)) {
            throw new Exception('Gagal mengembalikan riwayat alat: '.mysqli_error($konek));
        }

        if (!mysqli_query($konek, "DELETE FROM `$archiveTable` WHERE no_part='$archiveNoEsc' LIMIT 1")) {
            throw new Exception('Gagal menghapus salinan arsip setelah restore: '.mysqli_error($konek));
        }

        mysqli_commit($konek);
        echo json_encode([
            'status'=>'success',
            'message'=>'Nomor alat berhasil dikembalikan ke data aktif beserta data lamanya.',
            'no_part'=>$original,
            'alat_id'=>$newId
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        mysqli_rollback($konek);
        echo json_encode(['status'=>'error','message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

elseif ($action == 'simpan_alat') {
    try {
        $no_part_raw = isset($_POST['no_part']) ? trim($_POST['no_part']) : '';

        if ($no_part_raw === '') {
            echo json_encode(['status' => 'error', 'message' => 'Nomor alat wajib diisi.']);
            exit;
        }
        // [FIX BUG] no_part tidak boleh mengandung pola arsip _RUSAK_ - mencegah
        // orang mendaftarkan ulang nomor arsip yang sudah divonis NOT GOOD/RUSAK/DISCONTINUE/DITARIK MECHANICAL secara langsung.
        if (stripos($no_part_raw, '_RUSAK_') !== false) {
            echo json_encode(['status' => 'error', 'message' => 'Format nomor alat tidak valid (mengandung pola arsip internal).']);
            exit;
        }

        // Nomor alat yang pernah masuk arsip tidak boleh didaftarkan ulang secara langsung.
        // Untuk NOT GOOD/DISCONTINUE/DITARIK hanya admin yang boleh mengembalikan data lama melalui restore_alat.
        foreach (qaArchiveDefs() as $def) {
            $t=$def['table'];
            $safeNo=$no_part_raw;
            $qq=mysqli_query($konek,"SELECT no_part FROM `$t` WHERE UPPER(TRIM(no_part)) LIKE '".strtoupper(mysqli_real_escape_string($konek,$safeNo))."\\_{$def['code']}\\_%' LIMIT 1");
            if($qq && mysqli_num_rows($qq)>0){
                echo json_encode(['status'=>'error','message'=>'Nomor alat pernah masuk arsip '.$def['label'].'. Nomor ini tidak dapat didaftarkan ulang langsung; gunakan proses Restore oleh Admin.']); exit;
            }
        }

        $no_part   = normalisasi_no_part($konek, $no_part_raw);
        $nama_part = mysqli_real_escape_string($konek, trim($_POST['nama_part']));
        $seksi     = mysqli_real_escape_string($konek, trim($_POST['seksi']));

        // [FIX BUG] validasi periode harus integer positif, sebelumnya (int)$_POST['periode']
        // yang kosong/invalid akan menjadi 0 dan membuat jadwal_kalibrasi = hari ini juga.
        $periode = isset($_POST['periode']) ? (int) $_POST['periode'] : 0;
        if ($periode <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Periode kalibrasi wajib diisi dengan angka lebih dari 0 (dalam hari).']);
            exit;
        }

        $kls  = "";
        $type = "";

        $ukuran   = isset($_POST['ukuran'])   ? mysqli_real_escape_string($konek, trim($_POST['ukuran']))   : '';
        $resolusi = isset($_POST['resolusi']) ? mysqli_real_escape_string($konek, trim($_POST['resolusi'])) : '';
        $lokasi   = isset($_POST['lokasi'])   ? mysqli_real_escape_string($konek, trim($_POST['lokasi']))   : '';

        $tgl_manual = isset($_POST['tgl_manual']) ? mysqli_real_escape_string($konek, trim($_POST['tgl_manual'])) : '';

        $spesifikasi      = isset($_POST['spesifikasi'])      ? mysqli_real_escape_string($konek, trim($_POST['spesifikasi']))      : '';
        $toleransi_global = isset($_POST['toleransi_global'])  ? mysqli_real_escape_string($konek, trim($_POST['toleransi_global'])) : '';
        $satuan           = isset($_POST['satuan'])           ? mysqli_real_escape_string($konek, trim($_POST['satuan']))           : '';
        $metode_uji       = isset($_POST['metode_uji'])       ? mysqli_real_escape_string($konek, trim($_POST['metode_uji']))       : '';
        $standar_uji      = isset($_POST['standar_uji'])      ? mysqli_real_escape_string($konek, trim($_POST['standar_uji']))      : '';
        $traceability     = isset($_POST['traceability'])     ? mysqli_real_escape_string($konek, trim($_POST['traceability']))     : '';
        $format_laporan   = isset($_POST['format_laporan'])   ? mysqli_real_escape_string($konek, trim($_POST['format_laporan']))   : '';
        $titik_standar_json = isset($_POST['titik_standar_json']) ? mysqli_real_escape_string($konek, $_POST['titik_standar_json']) : '[]';

        // Validasi format laporan harus salah satu dari A..R
        $format_valid = str_split('ABCDEFGHIJKLMNOPQR');
        if (!in_array($format_laporan, $format_valid)) {
            echo json_encode(['status' => 'error', 'message' => 'Format laporan tidak valid.']);
            exit;
        }

        $tgl_start        = !empty($tgl_manual) ? $tgl_manual : date('Y-m-d');
        $jadwal_kalibrasi = date('Y-m-d', strtotime($tgl_start . " +$periode days"));
        $created_at       = date('Y-m-d H:i:s');

        $nama_file_foto = '';
        if (isset($_FILES['foto_alat']) && $_FILES['foto_alat']['error'] == 0) {
            if ($_FILES['foto_alat']['size'] > 10485760) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal! Ukuran foto maksimal 10MB.']);
                exit;
            }
            $ext_allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['foto_alat']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $ext_allowed)) {
                echo json_encode(['status' => 'error', 'message' => 'Format foto tidak didukung. Gunakan JPG/PNG/WEBP.']);
                exit;
            }
            $folder_tujuan = "../../images/alat/";
            if (!is_dir($folder_tujuan)) { mkdir($folder_tujuan, 0777, true); }
            $nama_file_foto = "ALAT_" . preg_replace('/[^A-Za-z0-9]/', '', $no_part) . "_" . time() . "." . $ext;
            move_uploaded_file($_FILES['foto_alat']['tmp_name'], $folder_tujuan . $nama_file_foto);
        }

        // Cek dobel: no_part masih aktif terdaftar
        $cek_dobel = mysqli_query($konek, "SELECT no_part FROM qa_part WHERE UPPER(TRIM(no_part)) = '$no_part'");
        if ($cek_dobel && mysqli_num_rows($cek_dobel) > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Nomor Alat ini masih aktif terdaftar! Jika alat sudah rusak, update hasil kalibrasinya jadi "Bad" dan gunakan Restore Admin untuk nomor yang masih dapat dikembalikan.']);
            exit;
        }

        mysqli_begin_transaction($konek);
        try {
            $sql = "INSERT INTO qa_part
                    (no_part, nama_part, seksi_pemilik, periode_kalibrasi, jadwal_kalibrasi, status, created_at, ukuran, resolusi, lokasi, type, kls, foto_alat, spesifikasi, toleransi_global, satuan, metode_uji, standar_uji, traceability, format_laporan, titik_standar_json)
                    VALUES
                    ('$no_part', '$nama_part', '$seksi', '$periode', '$jadwal_kalibrasi', 'aktif', '$created_at', '$ukuran', '$resolusi', '$lokasi', '$type', '$kls', '$nama_file_foto', '$spesifikasi', '$toleransi_global', '$satuan', '$metode_uji', '$standar_uji', '$traceability', '$format_laporan', '$titik_standar_json')";

            if (!mysqli_query($konek, $sql)) {
                throw new Exception(mysqli_error($konek));
            }

            $alat_id = mysqli_insert_id($konek);
            $nomor_pengecekan = "QA-REG-" . date('YmdHis') . "-" . $alat_id;

            $sql_hist = "INSERT INTO qa_pengecekan
                         (alat_id, no_part, nomor_pengecekan, tanggal_pengecekan, tanggal_jadwal_saat_pengecekan, status_kalibrasi, status_validasi, jenis_transaksi, seksi_qa, operator, periode_berikutnya, created_by, keterangan, created_at)
                         VALUES
                         ('$alat_id', '$no_part', '$nomor_pengecekan', '$tgl_start', '$tgl_start', 'Good', 'setuju', 'registrasi_alat', 'QA', '$nama_petugas', '$jadwal_kalibrasi', '$nama_petugas', '~ Registrasi Alat Baru', '$created_at')";

            if (!mysqli_query($konek, $sql_hist)) {
                throw new Exception(mysqli_error($konek));
            }

            mysqli_commit($konek);
            echo json_encode(['status' => 'success', 'jadwal_selanjutnya' => $jadwal_kalibrasi]);
        } catch (Exception $ex) {
            mysqli_rollback($konek);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $ex->getMessage()]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

/* =====================================================================
 * ACTION: simpan_qa
 * Update kalibrasi berkala. Jika status_qa === 'Bad', alat dikarantina
 * ke qa_alat_rusak dan nomor lama dibebaskan untuk unit fisik baru.
 * [MEETING] operator wajib diisi; status hasil disimpan sesuai 5 status final
 * (bukan 'NOT GOOD'); riwayat baru masuk status_validasi = 'menunggu_pemeriksa'.
 * =================================================================== */
elseif ($action == 'simpan_qa') {
    try {
        $no_laporan_raw = isset($_POST['no_laporan']) ? trim((string)$_POST['no_laporan']) : '';
        if ($no_laporan_raw === '') { echo json_encode(['status'=>'error','message'=>'No. Laporan wajib diisi.']); exit; }
        $no_laporan = mysqli_real_escape_string($konek, $no_laporan_raw);

        $no_part_raw = isset($_POST['no_part']) ? trim($_POST['no_part']) : '';
        $no_part     = normalisasi_no_part($konek, $no_part_raw);
        $status_raw  = isset($_POST['status_qa']) ? trim($_POST['status_qa']) : '';
        $status_qa   = qaNormalizeFinalStatus($status_raw);
        if ($status_qa === '') { echo json_encode(['status'=>'error','message'=>'Keputusan Final tidak valid. Pilih OK, NOT GOOD, RUSAK, DISCONTINUE, atau DITARIK MECHANICAL.']); exit; }
        $seksi_qa    = 'QA';

        if ($no_part === '') {
            echo json_encode(['status' => 'error', 'message' => 'Nomor alat wajib diisi.']);
            exit;
        }

        $operator = isset($_POST['operator']) ? mysqli_real_escape_string($konek, trim($_POST['operator'])) : '';
        if ($operator === '') {
            $operator = $nama_petugas;
        }

        $suhu             = isset($_POST['suhu']) ? mysqli_real_escape_string($konek, trim($_POST['suhu'])) : '';
        $kelembapan       = isset($_POST['kelembapan']) ? mysqli_real_escape_string($konek, trim($_POST['kelembapan'])) : '';
        $ketidakpastian   = isset($_POST['ketidakpastian']) ? mysqli_real_escape_string($konek, trim($_POST['ketidakpastian'])) : '';
        $confidence       = isset($_POST['confidence']) ? mysqli_real_escape_string($konek, trim($_POST['confidence'])) : '';
        $keterangan       = isset($_POST['keterangan']) ? mysqli_real_escape_string($konek, trim($_POST['keterangan'])) : '';
        $traceability     = isset($_POST['traceability']) ? mysqli_real_escape_string($konek, trim($_POST['traceability'])) : '';
        $nama_part        = isset($_POST['nama_part']) ? mysqli_real_escape_string($konek, trim($_POST['nama_part'])) : '';
        $spesifikasi      = isset($_POST['spesifikasi']) ? mysqli_real_escape_string($konek, trim($_POST['spesifikasi'])) : '';
        $resolusi         = isset($_POST['resolusi']) ? mysqli_real_escape_string($konek, trim($_POST['resolusi'])) : '';
        $metode_uji       = isset($_POST['metode_uji']) ? mysqli_real_escape_string($konek, trim($_POST['metode_uji'])) : '';
        $standar_uji      = isset($_POST['standar_uji']) ? mysqli_real_escape_string($konek, trim($_POST['standar_uji'])) : '';
        $toleransi_global = isset($_POST['toleransi_global']) ? mysqli_real_escape_string($konek, trim($_POST['toleransi_global'])) : '';
        $satuan           = isset($_POST['satuan']) ? mysqli_real_escape_string($konek, trim($_POST['satuan'])) : '';
        $lokasi           = isset($_POST['lokasi']) ? mysqli_real_escape_string($konek, trim($_POST['lokasi'])) : '';
        $format_laporan   = isset($_POST['format_laporan']) ? strtoupper(trim($_POST['format_laporan'])) : '';
        $hasil_raw        = isset($_POST['hasil_json']) ? $_POST['hasil_json'] : '[]';

        if (!in_array($format_laporan, str_split('ABCDEFGHIJKLMNOPQR'), true)) {
            echo json_encode(['status' => 'error', 'message' => 'Format laporan tidak valid.']);
            exit;
        }

        $tgl_pengecekan = date('Y-m-d');
        $waktu_sekarang = date('Y-m-d H:i:s');

        $q_part = mysqli_query($konek, "SELECT * FROM qa_part WHERE UPPER(TRIM(no_part)) = '$no_part' LIMIT 1");
        $d_part = $q_part ? mysqli_fetch_assoc($q_part) : null;
        if (!$d_part) {
            echo json_encode(['status' => 'error', 'message' => 'Alat tidak ditemukan atau sudah tidak aktif.']);
            exit;
        }

        // Semua field editable saat pengecekan: jika dikirim kosong, pertahankan nilai master.
        $nama_part        = $nama_part !== '' ? $nama_part : mysqli_real_escape_string($konek, $d_part['nama_part'] ?? '');
        $spesifikasi      = $spesifikasi !== '' ? $spesifikasi : mysqli_real_escape_string($konek, $d_part['spesifikasi'] ?? '');
        $resolusi         = $resolusi !== '' ? $resolusi : mysqli_real_escape_string($konek, $d_part['resolusi'] ?? '');
        $metode_uji       = $metode_uji !== '' ? $metode_uji : mysqli_real_escape_string($konek, $d_part['metode_uji'] ?? '');
        $standar_uji      = $standar_uji !== '' ? $standar_uji : mysqli_real_escape_string($konek, $d_part['standar_uji'] ?? '');
        $traceability     = $traceability !== '' ? $traceability : mysqli_real_escape_string($konek, $d_part['traceability'] ?? '');
        $toleransi_global = $toleransi_global !== '' ? $toleransi_global : mysqli_real_escape_string($konek, $d_part['toleransi_global'] ?? '');
        $satuan           = $satuan !== '' ? $satuan : mysqli_real_escape_string($konek, $d_part['satuan'] ?? '');
        $lokasi           = $lokasi !== '' ? $lokasi : mysqli_real_escape_string($konek, $d_part['lokasi'] ?? '');

        $seksi_pemilik = mysqli_real_escape_string($konek, $d_part['seksi_pemilik'] ?? '');
        $tgl_jadwal_lama = $d_part['jadwal_kalibrasi'] ?: date('Y-m-d');
        $periode = !empty($d_part['periode_kalibrasi']) ? (int)$d_part['periode_kalibrasi'] : 30;
        $alat_id = (int)$d_part['id'];
        $nomor_pengecekan = 'QA-' . date('YmdHis') . '-' . $alat_id;
        $no_part_asli = $no_part;
        $no_part_final = $no_part;
        $jadwal_baru = null;

        mysqli_begin_transaction($konek);
        try {
            // Simpan perubahan header/footer ke master terlebih dahulu.
            $update_master = "UPDATE qa_part SET
                nama_part='$nama_part',
                spesifikasi='$spesifikasi',
                resolusi='$resolusi',
                metode_uji='$metode_uji',
                standar_uji='$standar_uji',
                traceability='$traceability',
                toleransi_global='$toleransi_global',
                satuan='$satuan',
                lokasi='$lokasi'
                WHERE id='$alat_id' LIMIT 1";
            if (!mysqli_query($konek, $update_master)) {
                throw new Exception(mysqli_error($konek));
            }

            // PENTING: hasil kalibrasi belum boleh mengubah status aktif/arsip sebelum
            // Pemeriksa DAN Penyetuju menyatakan setuju. Sampai tahap final, qa_part
            // tetap aktif dan hasil hanya disimpan sebagai kandidat pada qa_pengecekan.
            $jadwal_baru = null;
            $no_part_final = $no_part_asli;

            $snapshot_json = build_hasil_snapshot_json($hasil_raw, [
                'format'            => $format_laporan,
                'no_part'           => $no_part_final,
                'nama_part'         => $nama_part,
                'seksi_pemilik'     => $seksi_pemilik,
                'spesifikasi'       => $spesifikasi,
                'resolusi'          => $resolusi,
                'lokasi'            => $lokasi,
                'metode_uji'        => $metode_uji,
                'standar_uji'       => $standar_uji,
                'traceability'      => $traceability,
                'toleransi_global'  => $toleransi_global,
                'satuan'            => $satuan,
                'suhu'              => $suhu,
                'kelembapan'        => $kelembapan,
                'ketidakpastian'    => $ketidakpastian,
                'confidence_level'  => $confidence,
                'keterangan'        => $keterangan,
                'petugas'           => $nama_petugas,
                'operator'          => $operator,
            ]);
            $snapshot_json_sql = mysqli_real_escape_string($konek, $snapshot_json);

            $jadwal_sql = 'NULL';
            $sql_hist = "INSERT INTO qa_pengecekan
                (alat_id, no_part, nomor_pengecekan, no_laporan, tanggal_pengecekan,
                 tanggal_jadwal_saat_pengecekan, status_kalibrasi, status_validasi, jenis_transaksi,
                 seksi_qa, operator, periode_berikutnya, created_by, keterangan,
                 created_at, suhu, kelembapan, ketidakpastian, confidence_level, hasil_json)
                VALUES
                ('$alat_id', '$no_part_final', '$nomor_pengecekan', '$no_laporan', '$tgl_pengecekan',
                 '$tgl_jadwal_lama', '$status_qa', 'menunggu_pemeriksa', 'update_hasil_kalibrasi',
                 '$seksi_qa', '$operator', $jadwal_sql, '$nama_petugas', '$keterangan',
                 '$waktu_sekarang', '$suhu', '$kelembapan', '$ketidakpastian', '$confidence', '$snapshot_json_sql')";

            if (!mysqli_query($konek, $sql_hist)) {
                throw new Exception('Gagal menyimpan histori kalibrasi: ' . mysqli_error($konek));
            }

            $pengecekan_id = mysqli_insert_id($konek);
            mysqli_commit($konek);

            echo json_encode([
                'status'             => 'success',
                'jadwal_selanjutnya' => $jadwal_baru,
                'no_pengecekan'      => $nomor_pengecekan,
                'no_laporan'         => $no_laporan_raw,
                'pengecekan_id'      => $pengecekan_id,
                'no_part_final'      => $no_part_final,
                'status_final'       => $status_qa,
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $ex) {
            mysqli_rollback($konek);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan hasil kalibrasi: ' . $ex->getMessage()]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

/* =====================================================================
 * ACTION: validasi_pemeriksa  [MEETING - tahap 1 dari Validator]
 * Pemeriksa menyetujui / menolak sebuah riwayat pengecekan.
 * Keterangan validator wajib diisi untuk setiap keputusan.
 * =================================================================== */
elseif ($action == 'validasi_pemeriksa') {
    if (!qaCanPemeriksa($konek)) { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Anda tidak memiliki hak sebagai validator tahap ini.']); exit; }

    try {
        $pengecekan_id = isset($_POST['pengecekan_id']) ? (int) $_POST['pengecekan_id'] : 0;
        $keputusan     = isset($_POST['keputusan']) ? mysqli_real_escape_string($konek, trim($_POST['keputusan'])) : '';
        $keterangan_raw = isset($_POST['keterangan']) ? trim((string)$_POST['keterangan']) : '';
        $keterangan_len = function_exists('mb_strlen') ? mb_strlen($keterangan_raw, 'UTF-8') : strlen($keterangan_raw);
        if ($keterangan_raw === '') {
            echo json_encode(['status' => 'error', 'message' => 'Keterangan validator wajib diisi.']);
            exit;
        }
        if ($keterangan_len > 500) {
            echo json_encode(['status' => 'error', 'message' => 'Keterangan validator maksimal 500 karakter.']);
            exit;
        }
        $keterangan = mysqli_real_escape_string($konek, $keterangan_raw);

        if ($pengecekan_id <= 0 || !in_array($keputusan, ['setuju', 'tolak'])) {
            echo json_encode(['status' => 'error', 'message' => 'Data validasi tidak lengkap.']);
            exit;
        }

        $q = mysqli_query($konek, "SELECT status_validasi FROM qa_pengecekan WHERE id = '$pengecekan_id'");
        $row = $q ? mysqli_fetch_assoc($q) : null;
        if (!$row) {
            echo json_encode(['status' => 'error', 'message' => 'Data pengecekan tidak ditemukan.']);
            exit;
        }
        if ($row['status_validasi'] !== 'menunggu_pemeriksa') {
            echo json_encode(['status' => 'error', 'message' => 'Data ini sudah melewati tahap validasi Pemeriksa.']);
            exit;
        }

        $status_baru = ($keputusan === 'setuju') ? 'menunggu_penyetuju' : 'ditolak_pemeriksa';
        $waktu = date('Y-m-d H:i:s');

        mysqli_begin_transaction($konek);
        try {
            mysqli_query($konek, "UPDATE qa_pengecekan SET status_validasi = '$status_baru' WHERE id = '$pengecekan_id'");
            mysqli_query($konek, "INSERT INTO qa_validasi (pengecekan_id, tahap, keputusan, nama_validator, keterangan, created_at)
                                   VALUES ('$pengecekan_id', 'pemeriksa', '$keputusan', '$nama_petugas', '$keterangan', '$waktu')");
            mysqli_commit($konek);
            echo json_encode(['status' => 'success', 'status_validasi' => $status_baru]);
        } catch (Exception $ex) {
            mysqli_rollback($konek);
            echo json_encode(['status' => 'error', 'message' => $ex->getMessage()]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

/* =====================================================================
 * ACTION: validasi_penyetuju  [MEETING - tahap 2 dari Validator]
 * Hanya bisa dijalankan setelah Pemeriksa menyetujui.
 * Keterangan validator wajib diisi untuk setiap keputusan.
 * =================================================================== */
elseif ($action == 'validasi_penyetuju') {
    if (!qaCanPenyetuju($konek)) { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Anda tidak memiliki hak sebagai validator tahap ini.']); exit; }

    try {
        $pengecekan_id = isset($_POST['pengecekan_id']) ? (int) $_POST['pengecekan_id'] : 0;
        $keputusan     = isset($_POST['keputusan']) ? mysqli_real_escape_string($konek, trim($_POST['keputusan'])) : '';
        $keterangan_raw = isset($_POST['keterangan']) ? trim((string)$_POST['keterangan']) : '';
        $keterangan_len = function_exists('mb_strlen') ? mb_strlen($keterangan_raw, 'UTF-8') : strlen($keterangan_raw);
        if ($keterangan_raw === '') {
            echo json_encode(['status' => 'error', 'message' => 'Keterangan validator wajib diisi.']);
            exit;
        }
        if ($keterangan_len > 500) {
            echo json_encode(['status' => 'error', 'message' => 'Keterangan validator maksimal 500 karakter.']);
            exit;
        }
        $keterangan = mysqli_real_escape_string($konek, $keterangan_raw);

        if ($pengecekan_id <= 0 || !in_array($keputusan, ['setuju', 'tolak'])) {
            echo json_encode(['status' => 'error', 'message' => 'Data validasi tidak lengkap.']);
            exit;
        }

        $q = mysqli_query($konek, "SELECT status_validasi FROM qa_pengecekan WHERE id = '$pengecekan_id'");
        $row = $q ? mysqli_fetch_assoc($q) : null;
        if (!$row) {
            echo json_encode(['status' => 'error', 'message' => 'Data pengecekan tidak ditemukan.']);
            exit;
        }
        if ($row['status_validasi'] !== 'menunggu_penyetuju') {
            echo json_encode(['status' => 'error', 'message' => 'Data ini belum disetujui Pemeriksa, atau sudah final.']);
            exit;
        }

        $status_baru = ($keputusan === 'setuju') ? 'final_disetujui' : 'ditolak_penyetuju';
        $waktu = date('Y-m-d H:i:s');

        mysqli_begin_transaction($konek);
        try {
            if (!mysqli_query($konek, "UPDATE qa_pengecekan SET status_validasi = '$status_baru' WHERE id = '$pengecekan_id'")) { throw new Exception(mysqli_error($konek)); }
            if (!mysqli_query($konek, "INSERT INTO qa_validasi (pengecekan_id, tahap, keputusan, nama_validator, keterangan, created_at)
                                   VALUES ('$pengecekan_id', 'penyetuju', '$keputusan', '$nama_petugas', '$keterangan', '$waktu')")) { throw new Exception(mysqli_error($konek)); }
            if ($keputusan === 'setuju') {
                qaFinalizeApprovedResult($konek, $pengecekan_id);
            }
            mysqli_commit($konek);
            echo json_encode(['status' => 'success', 'status_validasi' => $status_baru]);
        } catch (Exception $ex) {
            mysqli_rollback($konek);
            echo json_encode(['status' => 'error', 'message' => $ex->getMessage()]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

/* =====================================================================
 * ACTION: get_history_print
 * Payload cetak histori membaca status validasi TERKINI dari qa_validasi.
 * Ini dipakai tombol Cetak di Detail & Riwayat Kalibrasi.
 * =================================================================== */
if ($action === 'get_history_print') {
    $flags = qaUserFlags($konek);
    if (!(qaIsAdmin() || $flags['pembuat'] || $flags['pemeriksa'] || $flags['penyetuju'])) {
        http_response_code(403);
        echo json_encode(['status'=>'error','message'=>'Akses laporan histori ditolak.']);
        exit;
    }

    $id = (int)($_POST['pengecekan_id'] ?? 0);
    if ($id < 1) {
        echo json_encode(['status'=>'error','message'=>'ID pengecekan tidak valid.']);
        exit;
    }

    $q = mysqli_query($konek, "SELECT q.*,
        COALESCE(p.nama_part, ar.nama_part, an.nama_part, ad.nama_part, at.nama_part) AS nama_part,
        COALESCE(p.spesifikasi, ar.spesifikasi, an.spesifikasi, ad.spesifikasi, at.spesifikasi) AS spesifikasi,
        COALESCE(p.seksi_pemilik, ar.seksi_pemilik, an.seksi_pemilik, ad.seksi_pemilik, at.seksi_pemilik) AS seksi_pemilik
        FROM qa_pengecekan q
        LEFT JOIN qa_part p ON p.id=q.alat_id
        LEFT JOIN qa_alat_rusak ar ON ar.no_part=q.no_part
        LEFT JOIN qa_alat_ng an ON an.no_part=q.no_part
        LEFT JOIN qa_alat_discontinue ad ON ad.no_part=q.no_part
        LEFT JOIN qa_alat_ditarik at ON at.no_part=q.no_part
        WHERE q.id='$id' LIMIT 1");
    $row = $q ? mysqli_fetch_assoc($q) : null;
    if (!$row) {
        echo json_encode(['status'=>'error','message'=>'Riwayat pengecekan tidak ditemukan.']);
        exit;
    }

    $audit = [];
    $qa = mysqli_query($konek, "SELECT * FROM qa_validasi WHERE pengecekan_id='$id' ORDER BY id ASC");
    if ($qa) while ($a = mysqli_fetch_assoc($qa)) $audit[] = $a;

    $pemeriksa = '';
    $penyetuju = '';
    $pOk = false;
    $sOk = false;
    foreach ($audit as $a) {
        if (($a['tahap'] ?? '') === 'pemeriksa' && ($a['keputusan'] ?? '') === 'setuju') {
            $pemeriksa = (string)$a['nama_validator'];
            $pOk = true;
        }
        if (($a['tahap'] ?? '') === 'penyetuju' && ($a['keputusan'] ?? '') === 'setuju') {
            $penyetuju = (string)$a['nama_validator'];
            $sOk = true;
        }
    }

    $raw = json_decode($row['hasil_json'] ?: '[]', true);
    if (is_array($raw) && isset($raw['header'])) {
        $h = $raw['header'] ?? [];
        $env = $raw['environment'] ?? [];
        $ft = $raw['footer'] ?? [];
        $rows = $raw['rows'] ?? [];
        $format = $raw['format'] ?? ($row['format_laporan'] ?? '');
    } else {
        $h = [];
        $env = [];
        $ft = [];
        $rows = is_array($raw) ? $raw : [];
        $format = $row['format_laporan'] ?? '';
    }

    $print = [
        'no_part' => $row['no_part'],
        'no_alat' => $row['no_part'],
        'nama_part' => $row['nama_part'] ?? ($h['nama_part'] ?? ''),
        'nama_alat' => $row['nama_part'] ?? ($h['nama_part'] ?? ''),
        'spesifikasi' => $row['spesifikasi'] ?? ($h['spesifikasi'] ?? ''),
        'seksi_pemilik' => $row['seksi_pemilik'] ?? ($h['seksi_pemilik'] ?? ''),
        'seksi' => $row['seksi_pemilik'] ?? ($h['seksi_pemilik'] ?? ''),
        'metode' => $h['metode_uji'] ?? ($h['metode'] ?? ''),
        'metode_uji' => $h['metode_uji'] ?? ($h['metode'] ?? ''),
        'standar_uji' => $h['standar_uji'] ?? '',
        'traceability' => $h['traceability'] ?? '',
        'toleransi_global' => $h['toleransi_global'] ?? '',
        'toleransi' => $h['toleransi_global'] ?? '',
        'resolusi' => $h['resolusi'] ?? '',
        'satuan' => $h['satuan'] ?? '',
        'suhu' => $row['suhu'] ?? ($env['suhu'] ?? ''),
        'kelembapan' => $row['kelembapan'] ?? ($env['kelembapan'] ?? ''),
        'ketidakpastian' => $row['ketidakpastian'] ?? ($env['ketidakpastian'] ?? ''),
        'confidence_level' => $row['confidence_level'] ?? ($env['confidence_level'] ?? ''),
        'keterangan' => $row['keterangan'] ?? ($ft['keterangan'] ?? ''),
        'format' => strtoupper((string)$format),
        'format_laporan' => strtoupper((string)$format),
        'hasil_array' => $rows,
        'tanggal_periksa' => $row['tanggal_pengecekan'],
        'jadwal_berikutnya' => $row['periode_berikutnya'],
        'operator' => $row['operator'] ?? ($row['created_by'] ?? ''),
        'petugas' => $row['created_by'] ?? ($row['operator'] ?? ''),
        'foreman' => $pemeriksa,
        'supervisor' => $penyetuju,
        'pemeriksa' => $pemeriksa,
        'penyetuju' => $penyetuju,
        'signature_pembuat' => true,
        'signature_pemeriksa' => $pOk,
        'signature_penyetuju' => $sOk,
        'nomor_pengecekan' => $row['nomor_pengecekan'] ?? '',
        'no_laporan' => $row['nomor_pengecekan'] ?? '',
        'status_final' => qaDisplayStatus($row['status_kalibrasi'] ?? '', $row['status_validasi'] ?? ''),
        'keputusan' => qaDisplayStatus($row['status_kalibrasi'] ?? '', $row['status_validasi'] ?? ''),
        'status_validasi' => $row['status_validasi'] ?? '',
        'audit' => $audit,
        'titik_standar_json' => $h['titik_standar_json'] ?? ''
    ];

    echo json_encode(['status'=>'success','print_data'=>$print,'audit'=>$audit], JSON_UNESCAPED_UNICODE);
    exit;
}

/* =====================================================================
 * ACTION: get_transaksi_detail
 * Detail transaksi + audit validator + payload printing.
 * =================================================================== */
if ($action === 'get_transaksi_detail') {
    if (!(qaIsAdmin() || qaUserFlags($konek)['pemeriksa'] || qaUserFlags($konek)['penyetuju'])) { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Akses transaksi ditolak.']); exit; }
    $id=(int)($_POST['pengecekan_id'] ?? 0);
    if($id<1){ echo json_encode(['status'=>'error','message'=>'ID transaksi tidak valid.']); exit; }
    $q=mysqli_query($konek,"SELECT q.*, COALESCE(p.nama_part, ar.nama_part, an.nama_part, ad.nama_part, at.nama_part) AS nama_part,
        COALESCE(p.spesifikasi, ar.spesifikasi, an.spesifikasi, ad.spesifikasi, at.spesifikasi) AS spesifikasi,
        COALESCE(p.seksi_pemilik, ar.seksi_pemilik, an.seksi_pemilik, ad.seksi_pemilik, at.seksi_pemilik) AS seksi_pemilik
        FROM qa_pengecekan q
        LEFT JOIN qa_part p ON p.id=q.alat_id
        LEFT JOIN qa_alat_rusak ar ON ar.no_part=q.no_part
        LEFT JOIN qa_alat_ng an ON an.no_part=q.no_part
        LEFT JOIN qa_alat_discontinue ad ON ad.no_part=q.no_part
        LEFT JOIN qa_alat_ditarik at ON at.no_part=q.no_part
        WHERE q.id='$id' LIMIT 1");
    $row=$q?mysqli_fetch_assoc($q):null;
    if(!$row){echo json_encode(['status'=>'error','message'=>'Transaksi tidak ditemukan.']);exit;}
    $audit=[];$qa=mysqli_query($konek,"SELECT * FROM qa_validasi WHERE pengecekan_id='$id' ORDER BY id ASC"); if($qa) while($a=mysqli_fetch_assoc($qa)) $audit[]=$a;
    $pemeriksa='';$penyetuju='';$pOk=false;$sOk=false;
    foreach($audit as $a){ if($a['tahap']==='pemeriksa' && $a['keputusan']==='setuju'){ $pemeriksa=$a['nama_validator'];$pOk=true; } if($a['tahap']==='penyetuju' && $a['keputusan']==='setuju'){ $penyetuju=$a['nama_validator'];$sOk=true; } }
    $raw=json_decode($row['hasil_json']?:'[]',true); $snap=$raw;
    if(is_array($raw)&&isset($raw['header'])){$h=$raw['header'];$env=$raw['environment']??[];$ft=$raw['footer']??[];$rows=$raw['rows']??[];} else {$h=[];$env=[];$ft=[];$rows=is_array($raw)?$raw:[];}
    $print=[
      'no_part'=>$row['no_part'],'no_alat'=>$row['no_part'],'nama_part'=>$row['nama_part']??($h['nama_part']??''),'nama_alat'=>$row['nama_part']??($h['nama_part']??''),'spesifikasi'=>$row['spesifikasi']??($h['spesifikasi']??''),'seksi_pemilik'=>$row['seksi_pemilik']??($h['seksi_pemilik']??''),'seksi'=>$row['seksi_pemilik']??($h['seksi_pemilik']??''),'metode'=>$h['metode_uji']??'','metode_uji'=>$h['metode_uji']??'','standar_uji'=>$h['standar_uji']??'','traceability'=>$h['traceability']??'','toleransi_global'=>$h['toleransi_global']??'','toleransi'=>$h['toleransi_global']??'','resolusi'=>$h['resolusi']??'','satuan'=>$h['satuan']??'','suhu'=>$row['suhu']??($env['suhu']??''),'kelembapan'=>$row['kelembapan']??($env['kelembapan']??''),'ketidakpastian'=>$row['ketidakpastian']??($env['ketidakpastian']??''),'confidence_level'=>$row['confidence_level']??($env['confidence_level']??''),'keterangan'=>$row['keterangan']??($ft['keterangan']??''),'format'=>$row['format_laporan']??($raw['format']??''),'hasil_array'=>$rows,'tanggal_periksa'=>$row['tanggal_pengecekan'],'jadwal_berikutnya'=>$row['periode_berikutnya'],'operator'=>$row['operator']??($ft['operator']??$row['created_by']),'petugas'=>$row['created_by'],'foreman'=>$pemeriksa,'supervisor'=>$penyetuju,'signature_pembuat'=>true,'signature_pemeriksa'=>$pOk,'signature_penyetuju'=>$sOk,'nomor_pengecekan'=>$row['nomor_pengecekan'],'status_final'=>qaDisplayStatus($row['status_kalibrasi']??'', $row['status_validasi']??''),'keputusan'=>qaDisplayStatus($row['status_kalibrasi']??'', $row['status_validasi']??''),'status_validasi'=>$row['status_validasi'],'audit'=>$audit,'no_laporan'=>(($row['no_laporan'] ?? '') !== '' ? $row['no_laporan'] : $row['nomor_pengecekan']),'titik_standar_json'=>$h['titik_standar_json']??''
    ];
    echo json_encode(['status'=>'success','data'=>$row,'audit'=>$audit,'print_data'=>$print],JSON_UNESCAPED_UNICODE);exit;
}

/* =====================================================================
 * ACTION: get_riwayat_validasi
 * Mengambil audit trail (siapa, kapan, keterangan) untuk satu pengecekan.
 * =================================================================== */
elseif ($action == 'get_riwayat_validasi') {
    try {
        $pengecekan_id = isset($_POST['pengecekan_id']) ? (int) $_POST['pengecekan_id'] : 0;
        if ($pengecekan_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
            exit;
        }
        $q = mysqli_query($konek, "SELECT * FROM qa_validasi WHERE pengecekan_id = '$pengecekan_id' ORDER BY id ASC");
        $rows = [];
        while ($r = mysqli_fetch_assoc($q)) { $rows[] = $r; }
        echo json_encode(['status' => 'success', 'data' => $rows]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

/* =====================================================================
 * ACTION: get_bbrau
 * Ambil data alat + full history (untuk cetak laporan / BBRAU).
 * =================================================================== */
elseif ($action == 'get_bbrau') {
    $no_part = isset($_POST['no_part']) ? normalisasi_no_part($konek, trim($_POST['no_part'])) : '';
    $alat = null;

    $q1 = mysqli_query($konek, "SELECT * FROM qa_part WHERE UPPER(TRIM(no_part)) = '$no_part'");
    if ($q1 && mysqli_num_rows($q1) > 0) {
        $alat = mysqli_fetch_assoc($q1);
    } else {
        foreach (qaArchiveDefs() as $def) {
            $table = $def['table'];
            @mysqli_query($konek, "CREATE TABLE IF NOT EXISTS `$table` LIKE `qa_part`");
            $q2 = mysqli_query($konek, "SELECT * FROM `$table` WHERE UPPER(TRIM(no_part)) = '$no_part' OR UPPER(TRIM(no_part)) LIKE '{$no_part}\\_{$def['code']}\\_%' ORDER BY id DESC LIMIT 1");
            if ($q2 && mysqli_num_rows($q2) > 0) {
                $alat = mysqli_fetch_assoc($q2);
                break;
            }
        }
    }

    if ($alat) {
        $history = [];
        $qh = mysqli_query($konek, "SELECT * FROM qa_pengecekan WHERE alat_id = '{$alat['id']}' ORDER BY tanggal_pengecekan ASC, id ASC");
        while ($r = mysqli_fetch_assoc($qh)) { $history[] = $r; }
        echo json_encode(['status' => 'success', 'alat' => $alat, 'history' => $history], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Alat tidak ditemukan.']);
    }
    exit;
}

/* =====================================================================
 * ACTION: simpan_master_seksi / hapus_master_seksi
 * Master data Seksi Pemilik QA.
 * =================================================================== */
elseif ($action == 'simpan_master_seksi') {
    try {
        $nama_seksi = mysqli_real_escape_string($konek, trim($_POST['nama_seksi']));
        if ($nama_seksi === '') {
            echo json_encode(['status' => 'error', 'message' => 'Nama seksi wajib diisi.']);
            exit;
        }
        $cek = mysqli_query($konek, "SELECT * FROM qa_seksi_master WHERE nama_seksi = '$nama_seksi'");
        if ($cek && mysqli_num_rows($cek) > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Seksi ini sudah ada di Database!']);
        } else {
            if (mysqli_query($konek, "INSERT INTO qa_seksi_master (nama_seksi) VALUES ('$nama_seksi')")) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal Insert: ' . mysqli_error($konek)]);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

elseif ($action == 'hapus_master_seksi') {
    try {
        $nama_seksi = mysqli_real_escape_string($konek, trim($_POST['nama_seksi']));
        mysqli_query($konek, "DELETE FROM qa_seksi_master WHERE nama_seksi = '$nama_seksi'");
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}


/* =====================================================================
 * ACTION: simpan_laporan_transaksi
 * Snapshot laporan Harian/Bulanan/Tahunan untuk divalidasi 2 tahap.
 * =================================================================== */
elseif ($action === 'simpan_laporan_transaksi') {
    if (!qaCanPembuat($konek)) {
        http_response_code(403);
        echo json_encode(['status'=>'error','message'=>'Anda tidak memiliki hak membuat laporan QA.']);
        exit;
    }
    $jenis = trim((string)($_POST['jenis_transaksi'] ?? ''));
    $periodeKey = trim((string)($_POST['periode_key'] ?? ''));
    $periodeLabel = trim((string)($_POST['periode_label'] ?? ''));
    $payload = (string)($_POST['payload_json'] ?? '');
    $allowedJenis = ['laporan_harian','laporan_bulanan','laporan_tahunan'];
    if (!in_array($jenis,$allowedJenis,true) || $periodeKey==='' || $periodeLabel==='' || $payload==='') {
        echo json_encode(['status'=>'error','message'=>'Data laporan tidak lengkap.']); exit;
    }
    $payloadDecoded=json_decode($payload,true);
    if (!is_array($payloadDecoded)) { echo json_encode(['status'=>'error','message'=>'Snapshot laporan tidak valid.']); exit; }
    $jenisEsc=esc($konek,$jenis); $keyEsc=esc($konek,$periodeKey); $labelEsc=esc($konek,$periodeLabel); $payloadEsc=esc($konek,$payload); $creatorEsc=esc($konek,$nama_petugas); $now=date('Y-m-d H:i:s');
    mysqli_begin_transaction($konek);
    try {
        $q=mysqli_query($konek,"SELECT id,status_validasi FROM qa_laporan_transaksi WHERE jenis_transaksi='$jenisEsc' AND periode_key='$keyEsc' LIMIT 1");
        $existing=$q?mysqli_fetch_assoc($q):null;
        if($existing){
            $id=(int)$existing['id'];
            if (!mysqli_query($konek,"UPDATE qa_laporan_transaksi SET periode_label='$labelEsc',status_validasi='menunggu_pemeriksa',created_by='$creatorEsc',updated_at='$now',payload_json='$payloadEsc' WHERE id='$id' LIMIT 1")) throw new Exception(mysqli_error($konek));
            // Saat laporan ditolak lalu diajukan lagi, audit lama tidak boleh menjadi status baru.
            mysqli_query($konek,"DELETE FROM qa_laporan_validasi WHERE laporan_id='$id'");
        } else {
            if (!mysqli_query($konek,"INSERT INTO qa_laporan_transaksi (jenis_transaksi,periode_key,periode_label,status_validasi,created_by,created_at,updated_at,payload_json) VALUES ('$jenisEsc','$keyEsc','$labelEsc','menunggu_pemeriksa','$creatorEsc','$now','$now','$payloadEsc')")) throw new Exception(mysqli_error($konek));
            $id=(int)mysqli_insert_id($konek);
        }
        mysqli_commit($konek);
        echo json_encode(['status'=>'success','laporan_id'=>$id,'status_validasi'=>'menunggu_pemeriksa']);
    } catch(Throwable $e) { mysqli_rollback($konek); echo json_encode(['status'=>'error','message'=>$e->getMessage()]); }
    exit;
}

/* =====================================================================
 * ACTION: validasi_laporan_pemeriksa
 * =================================================================== */
elseif ($action === 'validasi_laporan_pemeriksa') {
    if (!qaCanPemeriksa($konek)) { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Anda tidak memiliki hak sebagai Pemeriksa.']); exit; }
    $id=(int)($_POST['laporan_id']??0); $keputusan=trim((string)($_POST['keputusan']??'')); $ket=esc($konek,$_POST['keterangan']??'');
    if($id<1 || !in_array($keputusan,['setuju','tolak'],true)){echo json_encode(['status'=>'error','message'=>'Data validasi laporan tidak lengkap.']);exit;}
    $q=mysqli_query($konek,"SELECT status_validasi FROM qa_laporan_transaksi WHERE id='$id' LIMIT 1"); $row=$q?mysqli_fetch_assoc($q):null;
    if(!$row){echo json_encode(['status'=>'error','message'=>'Laporan tidak ditemukan.']);exit;}
    if(($row['status_validasi']??'')!=='menunggu_pemeriksa'){echo json_encode(['status'=>'error','message'=>'Laporan sudah melewati tahap Pemeriksa.']);exit;}
    $new=$keputusan==='setuju'?'menunggu_penyetuju':'ditolak_pemeriksa'; $now=date('Y-m-d H:i:s');
    mysqli_begin_transaction($konek);
    try{
        if(!mysqli_query($konek,"UPDATE qa_laporan_transaksi SET status_validasi='$new',updated_at='$now' WHERE id='$id' LIMIT 1")) throw new Exception(mysqli_error($konek));
        if(!mysqli_query($konek,"INSERT INTO qa_laporan_validasi (laporan_id,tahap,keputusan,nama_validator,keterangan,created_at) VALUES ('$id','pemeriksa','$keputusan','".esc($konek,$nama_petugas)."','$ket','$now')")) throw new Exception(mysqli_error($konek));
        mysqli_commit($konek); echo json_encode(['status'=>'success','status_validasi'=>$new]);
    }catch(Throwable $e){mysqli_rollback($konek);echo json_encode(['status'=>'error','message'=>$e->getMessage()]);}
    exit;
}

/* =====================================================================
 * ACTION: validasi_laporan_penyetuju
 * =================================================================== */
elseif ($action === 'validasi_laporan_penyetuju') {
    if (!qaCanPenyetuju($konek)) { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Anda tidak memiliki hak sebagai Penyetuju.']); exit; }
    $id=(int)($_POST['laporan_id']??0); $keputusan=trim((string)($_POST['keputusan']??'')); $ket=esc($konek,$_POST['keterangan']??'');
    if($id<1 || !in_array($keputusan,['setuju','tolak'],true)){echo json_encode(['status'=>'error','message'=>'Data validasi laporan tidak lengkap.']);exit;}
    $q=mysqli_query($konek,"SELECT status_validasi FROM qa_laporan_transaksi WHERE id='$id' LIMIT 1"); $row=$q?mysqli_fetch_assoc($q):null;
    if(!$row){echo json_encode(['status'=>'error','message'=>'Laporan tidak ditemukan.']);exit;}
    if(($row['status_validasi']??'')!=='menunggu_penyetuju'){echo json_encode(['status'=>'error','message'=>'Laporan belum disetujui Pemeriksa atau sudah final.']);exit;}
    $new=$keputusan==='setuju'?'final_disetujui':'ditolak_penyetuju'; $now=date('Y-m-d H:i:s');
    mysqli_begin_transaction($konek);
    try{
        if(!mysqli_query($konek,"UPDATE qa_laporan_transaksi SET status_validasi='$new',updated_at='$now' WHERE id='$id' LIMIT 1")) throw new Exception(mysqli_error($konek));
        if(!mysqli_query($konek,"INSERT INTO qa_laporan_validasi (laporan_id,tahap,keputusan,nama_validator,keterangan,created_at) VALUES ('$id','penyetuju','$keputusan','".esc($konek,$nama_petugas)."','$ket','$now')")) throw new Exception(mysqli_error($konek));
        mysqli_commit($konek); echo json_encode(['status'=>'success','status_validasi'=>$new]);
    }catch(Throwable $e){mysqli_rollback($konek);echo json_encode(['status'=>'error','message'=>$e->getMessage()]);}
    exit;
}

/* =====================================================================
 * ACTION: get_riwayat_laporan_validasi
 * =================================================================== */
elseif ($action === 'get_riwayat_laporan_validasi') {
    $id=(int)($_POST['laporan_id']??0);
    if($id<1){echo json_encode(['status'=>'error','message'=>'ID laporan tidak valid.']);exit;}
    $q=mysqli_query($konek,"SELECT * FROM qa_laporan_validasi WHERE laporan_id='$id' ORDER BY id ASC"); $rows=[]; if($q) while($r=mysqli_fetch_assoc($q))$rows[]=$r;
    echo json_encode(['status'=>'success','data'=>$rows]); exit;
}

/* =====================================================================
 * Fallback: action tidak dikenali
 * =================================================================== */
else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali: ' . htmlspecialchars($action)]);
    exit;
}
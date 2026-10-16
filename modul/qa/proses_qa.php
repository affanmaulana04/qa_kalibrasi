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

    // FORMAT U V1 - snapshot eksplisit Standar / Hasil / Koreksi.
    if (strtoupper((string)($meta['format'] ?? '')) === 'U') {
        foreach ($rows as &$uRow) {
            if (!is_array($uRow)) $uRow = [];
            $uRow['standar'] = (string)($uRow['standar'] ?? '');
            $uRow['hasil'] = (string)($uRow['hasil'] ?? '');
            $uRow['koreksi'] = (string)($uRow['koreksi'] ?? '');
            $uRow['toleransi'] = (string)($uRow['toleransi'] ?? '');
        }
        unset($uRow);
    }

    $payload = [
        'version' => 2,
        'format'  => (string)($meta['format'] ?? ''),
        'header'  => [
            'no_part'          => (string)($meta['no_part'] ?? ''),
            'no_laporan'       => (string)($meta['no_laporan'] ?? ''),
            'jenis_laporan'    => (string)($meta['jenis_laporan'] ?? ''),
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

/* Jenis nomor laporan mengikuti judul renderer cetak.
 * Format L/M/R/U/V = Laporan Kalibrasi. Format lain masuk Laporan Periksa.
 * Format J (Verifikasi) masuk kelompok Periksa karena sistem nomor hanya punya 2 kategori. */
function qaJenisLaporanDariFormat($format)
{
    $format = strtoupper(trim((string)$format));
    return in_array($format, ['L','M','R','U','V'], true) ? 'kalibrasi' : 'periksa';
}

function qaBulanRomawi($bulan)
{
    $romawi = [1=>'I',2=>'II',3=>'III',4=>'IV',5=>'V',6=>'VI',7=>'VII',8=>'VIII',9=>'IX',10=>'X',11=>'XI',12=>'XII'];
    return $romawi[(int)$bulan] ?? '';
}

function qaLegacyMaxNoLaporan($konek, $jenis, $tanggalRef)
{
    $ts = strtotime((string)$tanggalRef);
    if ($ts === false) $ts = time();
    $tahun = (int)date('Y', $ts);
    $bulan = (int)date('m', $ts);
    $awal = sprintf('%04d-%02d-01', $tahun, $bulan);
    $akhir = date('Y-m-t', strtotime($awal));
    $max = 0;
    $q = mysqli_query($konek, "SELECT no_laporan,jenis_laporan FROM qa_pengecekan WHERE tanggal_pengecekan BETWEEN '$awal' AND '$akhir' AND no_laporan IS NOT NULL AND no_laporan<>''");
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $rowJenis = strtolower(trim((string)($r['jenis_laporan'] ?? '')));
            $no = trim((string)($r['no_laporan'] ?? ''));
            if ($rowJenis === '') {
                $upper = strtoupper($no);
                if (strpos($upper, '/LAP/KALIBRASI/') !== false) $rowJenis = 'kalibrasi';
                elseif (strpos($upper, '/LAP/PERIKSA/') !== false || strpos($upper, '/LAP/PEMERIKSAAN/') !== false) $rowJenis = 'periksa';
            }
            if ($rowJenis !== $jenis) continue;
            if (preg_match('/^(\d+)\s*\//', $no, $m)) $max = max($max, (int)$m[1]);
        }
    }
    return $max;
}

function qaGenerateNoLaporan($konek, $jenis, $tanggalRef)
{
    $jenis = strtolower(trim((string)$jenis));
    if (!in_array($jenis, ['periksa','kalibrasi'], true)) throw new Exception('Jenis laporan tidak valid.');
    $ts = strtotime((string)$tanggalRef);
    if ($ts === false) $ts = time();
    $tahun = (int)date('Y', $ts);
    $bulan = (int)date('m', $ts);
    $legacyMax = qaLegacyMaxNoLaporan($konek, $jenis, date('Y-m-d', $ts));
    $jenisEsc = esc($konek, $jenis);

    $sqlSeed = "INSERT IGNORE INTO qa_nomor_laporan_counter (jenis_laporan,tahun,bulan,nomor_terakhir,updated_at) VALUES ('$jenisEsc',$tahun,$bulan,$legacyMax,NOW())";
    if (!mysqli_query($konek, $sqlSeed)) throw new Exception('Gagal menyiapkan nomor laporan: '.mysqli_error($konek));
    $sqlNext = "UPDATE qa_nomor_laporan_counter SET nomor_terakhir=LAST_INSERT_ID(GREATEST(nomor_terakhir,$legacyMax)+1),updated_at=NOW() WHERE jenis_laporan='$jenisEsc' AND tahun=$tahun AND bulan=$bulan LIMIT 1";
    if (!mysqli_query($konek, $sqlNext) || mysqli_affected_rows($konek) < 1) throw new Exception('Gagal membuat nomor laporan berikutnya.');
    $qNo = mysqli_query($konek, "SELECT LAST_INSERT_ID() AS nomor");
    $rNo = $qNo ? mysqli_fetch_assoc($qNo) : null;
    $nomor = (int)($rNo['nomor'] ?? 0);
    if ($nomor <= 0) throw new Exception('Nomor laporan otomatis tidak terbentuk.');

    $label = $jenis === 'kalibrasi' ? 'Kalibrasi' : 'Periksa';
    return str_pad((string)$nomor, 2, '0', STR_PAD_LEFT).'/Lap/'.$label.'/'.qaBulanRomawi($bulan).'/'.$tahun;
}

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

function qaEnsureRegistryTable($konek){
    mysqli_query($konek, "CREATE TABLE IF NOT EXISTS qa_alat_registry (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        no_part VARCHAR(50) NOT NULL,
        status_alat VARCHAR(30) NOT NULL DEFAULT 'AKTIF',
        alasan_status VARCHAR(50) NULL,
        hasil_terakhir VARCHAR(40) NULL,
        tanggal_hasil_terakhir DATE NULL,
        sumber_tabel VARCHAR(64) NULL,
        sumber_id INT(10) UNSIGNED NULL,
        nomor_arsip_terakhir VARCHAR(150) NULL,
        diaktifkan_oleh VARCHAR(100) NULL,
        diaktifkan_at DATETIME NULL,
        created_by VARCHAR(100) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_qa_registry_no_part (no_part),
        KEY idx_qa_registry_status (status_alat),
        KEY idx_qa_registry_source (sumber_tabel, sumber_id)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}
qaEnsureRegistryTable($konek);

function qaArchiveDefs(){
    return [
        'NOT GOOD' => ['table'=>'qa_alat_ng','code'=>'NOTGOOD','permanent'=>true,'label'=>'NOT GOOD'],
        'RUSAK' => ['table'=>'qa_alat_rusak','code'=>'RUSAK','permanent'=>true,'label'=>'RUSAK'],
        'DISCONTINUE' => ['table'=>'qa_alat_discontinue','code'=>'DISCONTINUE','permanent'=>false,'label'=>'DISCONTINUE'],
        'DITARIK TECHNICAL' => ['table'=>'qa_alat_ditarik','code'=>'DITARIK','permanent'=>false,'label'=>'DITARIK TECHNICAL'],
        // Legacy label tetap dikenali supaya histori lama tidak rusak.
        'DITARIK MECHANICAL' => ['table'=>'qa_alat_ditarik','code'=>'DITARIK','permanent'=>false,'label'=>'DITARIK TECHNICAL'],
    ];
}
function qaNormalizeFinalStatus($raw){
    $s=strtoupper(trim((string)$raw));
    $map=['GOOD'=>'OK','OK'=>'OK','BAD'=>'NOT GOOD','NOT GOOD'=>'NOT GOOD','BURUK'=>'NOT GOOD','RUSAK'=>'RUSAK','DISCONTINUE'=>'DISCONTINUE','DITARIK'=>'DITARIK TECHNICAL','DITARIK TECHNICAL'=>'DITARIK TECHNICAL','DITARIK MECHANICAL'=>'DITARIK TECHNICAL'];
    return $map[$s] ?? '';
}
function qaArchiveNoPart($original,$code){ return '_'.$code.'_'.date('YmdHis'); }

function qaGetRegistry($konek,$no_part){
    $no=esc($konek,strtoupper(trim((string)$no_part)));
    $q=mysqli_query($konek,"SELECT * FROM qa_alat_registry WHERE UPPER(TRIM(no_part))='$no' LIMIT 1");
    return $q ? (mysqli_fetch_assoc($q) ?: null) : null;
}
function qaRegistryHistory($konek,$registryId,$no_part,$limit=5){
    $registryId=(int)$registryId; $no=esc($konek,$no_part); $limit=max(1,(int)$limit); $rows=[];
    if($registryId>0){
        $q=mysqli_query($konek,"SELECT * FROM qa_pengecekan WHERE alat_registry_id='$registryId' ORDER BY tanggal_pengecekan DESC,id DESC LIMIT $limit");
        if($q){while($r=mysqli_fetch_assoc($q))$rows[]=$r;}
    }
    if(!$rows){
        $q=mysqli_query($konek,"SELECT * FROM qa_pengecekan WHERE UPPER(TRIM(no_part))='$no' ORDER BY tanggal_pengecekan DESC,id DESC LIMIT $limit");
        if($q){while($r=mysqli_fetch_assoc($q))$rows[]=$r;}
    }
    return $rows;
}
function qaRegistrySet($konek,$no_part,$status,$reason,$result,$resultDate,$sourceTable,$sourceId,$archiveNo=null,$activatedBy=null,$activatedAt=null){
    $no=esc($konek,$no_part); $status=esc($konek,$status);
    $reason=$reason===null?'NULL':"'".esc($konek,$reason)."'";
    $result=$result===null?'NULL':"'".esc($konek,$result)."'";
    $resultDate=$resultDate?"'".esc($konek,substr((string)$resultDate,0,10))."'":'NULL';
    $sourceTable=$sourceTable?"'".esc($konek,$sourceTable)."'":'NULL'; $sourceId=(int)$sourceId;
    $archiveNo=$archiveNo?"'".esc($konek,$archiveNo)."'":'NULL';
    $activatedBy=$activatedBy?"'".esc($konek,$activatedBy)."'":'NULL';
    $activatedAt=$activatedAt?"'".esc($konek,$activatedAt)."'":'NULL';
    $createdBy=esc($konek,$GLOBALS['nama_petugas']??'SISTEM');
    $sql="INSERT INTO qa_alat_registry (no_part,status_alat,alasan_status,hasil_terakhir,tanggal_hasil_terakhir,sumber_tabel,sumber_id,nomor_arsip_terakhir,diaktifkan_oleh,diaktifkan_at,created_by)
        VALUES ('$no','$status',$reason,$result,$resultDate,$sourceTable,$sourceId,$archiveNo,$activatedBy,$activatedAt,'$createdBy')
        ON DUPLICATE KEY UPDATE status_alat=VALUES(status_alat),alasan_status=VALUES(alasan_status),hasil_terakhir=VALUES(hasil_terakhir),tanggal_hasil_terakhir=VALUES(tanggal_hasil_terakhir),sumber_tabel=VALUES(sumber_tabel),sumber_id=VALUES(sumber_id),nomor_arsip_terakhir=VALUES(nomor_arsip_terakhir),diaktifkan_oleh=VALUES(diaktifkan_oleh),diaktifkan_at=VALUES(diaktifkan_at)";
    if(!mysqli_query($konek,$sql)) throw new Exception('Gagal memperbarui registry nomor alat: '.mysqli_error($konek));
}
function qaDisplayStatus($status, $validation){
    $final = qaNormalizeFinalStatus($status);
    $validation = strtolower(trim((string)$validation));
    if ($validation === 'final_disetujui') return $final !== '' ? $final : 'FINAL';
    if ($validation === 'ditolak_pemeriksa') return 'DITOLAK PEMERIKSA';
    if ($validation === 'ditolak_penyetuju') return 'DITOLAK PENYETUJU';
    return 'MENUNGGU VALIDASI';
}

function qaExtractOriginalNoPart($archiveNo){
    return preg_replace('/_(?:NOTGOOD|RUSAK|DISCONTINUE|DITARIK)_\d{14}$/i','',$archiveNo);
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
pastikan_kolom($konek, 'qa_part', 'no_laporan', "VARCHAR(150) NULL AFTER no_part");
pastikan_kolom($konek, 'qa_part', 'jenis_laporan', "VARCHAR(20) NULL AFTER no_laporan");
pastikan_kolom($konek, 'qa_pengecekan', 'status_validasi', "VARCHAR(30) NOT NULL DEFAULT 'menunggu_pemeriksa' AFTER status_kalibrasi");
pastikan_kolom($konek, 'qa_pengecekan', 'jenis_transaksi', "VARCHAR(40) NULL AFTER nomor_pengecekan");
pastikan_kolom($konek, 'qa_pengecekan', 'no_laporan', "VARCHAR(150) NULL AFTER nomor_pengecekan");
pastikan_kolom($konek, 'qa_pengecekan', 'jenis_laporan', "VARCHAR(20) NULL AFTER no_laporan");
foreach (['qa_part','qa_alat_ng','qa_alat_rusak','qa_alat_discontinue','qa_alat_ditarik'] as $masterTable) {
    if ($masterTable !== 'qa_part') mysqli_query($konek, "CREATE TABLE IF NOT EXISTS `$masterTable` LIKE `qa_part`");
    pastikan_kolom($konek, $masterTable, 'alat_registry_id', "INT(10) UNSIGNED NULL AFTER id");
}
foreach (['qa_alat_ng','qa_alat_rusak','qa_alat_discontinue','qa_alat_ditarik'] as $archiveTable) {
    pastikan_kolom($konek, $archiveTable, 'nomor_arsip', "VARCHAR(150) NULL AFTER no_part");
    pastikan_kolom($konek, $archiveTable, 'no_laporan', "VARCHAR(150) NULL AFTER no_part");
    pastikan_kolom($konek, $archiveTable, 'jenis_laporan', "VARCHAR(20) NULL AFTER no_laporan");
}
pastikan_kolom($konek, 'qa_pengecekan', 'alat_registry_id', "INT(10) UNSIGNED NULL AFTER id");

/* Counter nomor laporan: terpisah per jenis + tahun + bulan, reset otomatis tiap bulan. */
mysqli_query($konek, "CREATE TABLE IF NOT EXISTS qa_nomor_laporan_counter (
    jenis_laporan VARCHAR(20) NOT NULL,
    tahun SMALLINT(4) UNSIGNED NOT NULL,
    bulan TINYINT(2) UNSIGNED NOT NULL,
    nomor_terakhir INT(10) UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (jenis_laporan,tahun,bulan)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci");

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
// FINAL FLOW NON-OK V3 - SUMBER KUNCI FINAL BERDASARKAN APPROVAL PENYETUJU
// NOT GOOD/RUSAK = permanen. DISCONTINUE/DITARIK TECHNICAL = nonaktif, restore Admin saja.
function qaLatestApprovedNonOk($konek, $no_part){
    $no=esc($konek,strtoupper(trim((string)$no_part)));
    $q=mysqli_query($konek,"SELECT * FROM qa_pengecekan WHERE UPPER(TRIM(no_part))='$no' AND status_validasi='final_disetujui' ORDER BY tanggal_pengecekan DESC,id DESC");
    if($q){ while($r=mysqli_fetch_assoc($q)){ $st=qaNormalizeFinalStatus($r['status_kalibrasi']??''); if($st!=='' && $st!=='OK') return ['row'=>$r,'status'=>$st]; } }
    return null;
}

function qaFinalizeApprovedResult($konek, $pengecekan_id)
{
    $q=mysqli_query($konek,"SELECT * FROM qa_pengecekan WHERE id='".(int)$pengecekan_id."' LIMIT 1");
    $row=$q?mysqli_fetch_assoc($q):null;
    if(!$row) throw new Exception('Data pengecekan tidak ditemukan.');

    $status=qaNormalizeFinalStatus($row['status_kalibrasi']??'');
    $no_part=normalisasi_no_part($konek,$row['no_part']??'');
    if($no_part==='') throw new Exception('Nomor alat pada histori finalisasi kosong.');

    $reg=qaGetRegistry($konek,$no_part);
    if(!$reg) throw new Exception('Registry nomor alat tidak ditemukan. Jalankan migration registry terlebih dahulu.');
    $registryId=(int)$reg['id'];

    $alat_id=(int)($row['alat_id']??0); $part=null;
    if($alat_id>0){$qp=mysqli_query($konek,"SELECT * FROM qa_part WHERE id='$alat_id' LIMIT 1");$part=$qp?mysqli_fetch_assoc($qp):null;}
    if(!$part){$qp=mysqli_query($konek,"SELECT * FROM qa_part WHERE alat_registry_id='$registryId' OR UPPER(TRIM(no_part))='$no_part' LIMIT 1");$part=$qp?mysqli_fetch_assoc($qp):null;}
    if(!$part) throw new Exception('Alat aktif tidak ditemukan untuk finalisasi status '.$status.'.');

    $resultDate=$row['tanggal_pengecekan']?:date('Y-m-d');
    if($status==='OK'){
        $periode=!empty($part['periode_kalibrasi'])?(int)$part['periode_kalibrasi']:30;
        $jadwal=date('Y-m-d',strtotime($resultDate.' +'.$periode.' days')); $jadwalEsc=esc($konek,$jadwal); $partId=(int)$part['id'];
        if(!mysqli_query($konek,"UPDATE qa_part SET status='aktif',jadwal_kalibrasi='$jadwalEsc',alat_registry_id='$registryId' WHERE id='$partId' LIMIT 1")) throw new Exception('Gagal memperbarui jadwal alat: '.mysqli_error($konek));
        if(!mysqli_query($konek,"UPDATE qa_pengecekan SET periode_berikutnya='$jadwalEsc',alat_registry_id='$registryId',no_part='".esc($konek,$no_part)."',alat_id='$partId' WHERE id='".(int)$pengecekan_id."' LIMIT 1")) throw new Exception('Gagal memperbarui histori final OK: '.mysqli_error($konek));
        qaRegistrySet($konek,$no_part,'AKTIF',null,'OK',$resultDate,'qa_part',$partId,null,null,null); return;
    }

    $defs=qaArchiveDefs(); $def=$defs[$status]??null;
    if(!$def) throw new Exception('Status final tidak didukung untuk finalisasi.');
    if(!mysqli_query($konek,"CREATE TABLE IF NOT EXISTS `{$def['table']}` LIKE `qa_part`")) throw new Exception('Gagal menyiapkan tabel arsip '.$def['table'].': '.mysqli_error($konek));
    pastikan_kolom($konek,$def['table'],'alat_registry_id',"INT(10) UNSIGNED NULL AFTER id");
    pastikan_kolom($konek,$def['table'],'nomor_arsip',"VARCHAR(150) NULL AFTER no_part");

    $archiveNo=qaArchiveNoPart($no_part,$def['code']); $archiveNoEsc=esc($konek,$archiveNo); $noEsc=esc($konek,$no_part);
    $exists=mysqli_query($konek,"SELECT id FROM `{$def['table']}` WHERE alat_registry_id='$registryId' OR UPPER(TRIM(no_part))='$noEsc' LIMIT 1");
    if($exists&&mysqli_num_rows($exists)>0) throw new Exception('Nomor alat sudah memiliki arsip aktif pada status '.$status.'.');
    $existsNo=mysqli_query($konek,"SELECT id FROM `{$def['table']}` WHERE nomor_arsip='$archiveNoEsc' LIMIT 1");
    if($existsNo&&mysqli_num_rows($existsNo)>0) throw new Exception('Nomor arsip bentrok.');

    // FINALISASI ARSIP V4 - hanya copy kolom yang BENAR-BENAR ada di qa_part DAN tabel arsip.
    // Struktur tabel arsip legacy tidak selalu punya kolom baru seperti buatan/fungsi.
    // Sebelumnya INSERT SELECT memakai semua kolom qa_part sehingga approval pertama gagal: Unknown column 'buatan'.
    $partCols=[]; $archiveCols=[];
    $qCols=mysqli_query($konek,"SHOW COLUMNS FROM `qa_part`");
    if(!$qCols) throw new Exception('Gagal membaca struktur qa_part: '.mysqli_error($konek));
    while($c=mysqli_fetch_assoc($qCols)){ $partCols[$c['Field']]=true; }
    $qArcCols=mysqli_query($konek,"SHOW COLUMNS FROM `{$def['table']}`");
    if(!$qArcCols) throw new Exception('Gagal membaca struktur arsip '.$def['table'].': '.mysqli_error($konek));
    while($c=mysqli_fetch_assoc($qArcCols)){ $archiveCols[$c['Field']]=true; }
    $cols=[];
    foreach(array_keys($partCols) as $field){
        if($field==='id' || !isset($archiveCols[$field])) continue;
        $cols[]='`'.str_replace('`','``',$field).'`';
    }
    if(!$cols) throw new Exception('Tidak ada kolom master yang cocok dengan tabel arsip '.$def['table'].'.');
    $colList=implode(',',$cols);
    if(!mysqli_query($konek,"INSERT INTO `{$def['table']}` ($colList) SELECT $colList FROM qa_part WHERE id='".(int)$part['id']."' LIMIT 1")) throw new Exception('Gagal memindahkan alat ke arsip '.$def['table'].': '.mysqli_error($konek));
    $archiveId=(int)mysqli_insert_id($konek); if($archiveId<=0) throw new Exception('ID arsip tidak terbentuk.');

    // FINALISASI ARSIP V2 - tabel arsip memakai ENUM status aktif/nonaktif, bukan nama hasil kalibrasi
    // Semua hasil selain OK dipindahkan ke tabel arsip terkait dan selalu dibuat NONAKTIF.
    $statusArchive='nonaktif';
    if(!mysqli_query($konek,"UPDATE `{$def['table']}` SET no_part='$noEsc',nomor_arsip='$archiveNoEsc',alat_registry_id='$registryId',status='$statusArchive',jadwal_kalibrasi=NULL WHERE id='$archiveId' LIMIT 1")) throw new Exception('Gagal menyimpan identitas arsip: '.mysqli_error($konek));
    if(!mysqli_query($konek,"UPDATE qa_pengecekan SET no_part='$noEsc',alat_registry_id='$registryId',alat_id='$archiveId',periode_berikutnya=NULL WHERE id='".(int)$pengecekan_id."' LIMIT 1")) throw new Exception('Gagal mengunci histori hasil final: '.mysqli_error($konek));
    if(!mysqli_query($konek,"DELETE FROM qa_part WHERE id='".(int)$part['id']."' LIMIT 1")) throw new Exception('Gagal menghapus master aktif setelah finalisasi arsip: '.mysqli_error($konek));

    $registryStatus=$def['permanent']?'BLOKIR_PERMANEN':'NONAKTIF_SEMENTARA';
    qaRegistrySet($konek,$no_part,$registryStatus,$status,$status,$resultDate,$def['table'],$archiveId,$archiveNo,null,null);
}

/* CEK ALAT QR V8 - CEK QR PERMANEN TANPA MEMBUAT/MENGUBAH FILE */
if ($action == 'cek_qr_alat') {
    $no_part = isset($_POST['no_part']) ? normalisasi_no_part($konek, $_POST['no_part']) : '';
    if ($no_part === '') { echo json_encode(['status'=>'error','message'=>'No. Alat kosong.']); exit; }
    $safe = preg_replace('/[^A-Z0-9._-]+/i', '_', $no_part);
    $file = 'QR_' . $safe . '.png';
    $path = dirname(__DIR__, 2) . '/images/qr_alat/' . $file;
    echo json_encode(['status'=>'success','exists'=>is_file($path),'file'=>is_file($path)?'../../images/qr_alat/'.$file:''], JSON_UNESCAPED_UNICODE);
    exit;
}

/* CEK ALAT QR V8 - SIMPAN QR CODE PERMANEN PER NOMOR ALAT (CREATE-ONLY) */
if ($action == 'simpan_qr_alat') {
    try {
        $no_part = isset($_POST['no_part']) ? normalisasi_no_part($konek, $_POST['no_part']) : '';
        $qr_data = isset($_POST['qr_data']) ? trim((string)$_POST['qr_data']) : '';
        if ($no_part === '' || $qr_data === '') throw new Exception('No. Alat / data QR tidak lengkap.');

        $reg = qaGetRegistry($konek, $no_part);
        $qPart = mysqli_query($konek, "SELECT id FROM qa_part WHERE UPPER(TRIM(no_part))='$no_part' LIMIT 1");
        if (!$reg && (!$qPart || mysqli_num_rows($qPart) < 1)) throw new Exception('No. Alat tidak terdaftar.');
        if (!preg_match('#^data:image/png;base64,#', $qr_data)) throw new Exception('Format QR tidak valid.');

        $raw = base64_decode(substr($qr_data, strpos($qr_data, ',') + 1), true);
        if ($raw === false || strlen($raw) < 50) throw new Exception('Data gambar QR rusak.');
        if (strlen($raw) > 2 * 1024 * 1024) throw new Exception('Ukuran QR terlalu besar.');

        $dir = dirname(__DIR__, 2) . '/images/qr_alat';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) throw new Exception('Folder images/qr_alat tidak dapat dibuat.');
        $safe = preg_replace('/[^A-Z0-9._-]+/i', '_', $no_part);
        $file = 'QR_' . $safe . '.png';
        $target = $dir . '/' . $file;
        // CEK ALAT QR V8 - QR IMMUTABLE: SATU NO ALAT HANYA BOLEH GENERATE/SIMPAN 1X
        if (is_file($target)) throw new Exception('QR Code untuk No. Alat ini sudah pernah dibuat dan dikunci permanen. QR tidak dapat dibuat ulang atau diganti.');
        $fp = @fopen($target, 'x'); // mode x = create-only, gagal jika file sudah ada (anti overwrite/race)
        if (!$fp) throw new Exception('QR Code untuk No. Alat ini sudah ada atau file tidak dapat dibuat.');
        $written = @fwrite($fp, $raw);
        @fflush($fp); @fclose($fp);
        if ($written === false || $written !== strlen($raw)) { @unlink($target); throw new Exception('QR gagal disimpan ke server.'); }
        @chmod($target, 0444); // read-only sebagai lapisan proteksi tambahan

        echo json_encode(['status'=>'success','message'=>'QR Code berhasil disimpan dan dikunci permanen.','file'=>'../../images/qr_alat/'.$file], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status'=>'error','message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

/* =====================================================================
 * ACTION: cek_alat
 * Cek status sebuah alat: terdaftar aktif, belum terdaftar, atau sudah
 * divonis NOT GOOD/RUSAK/DISCONTINUE/DITARIK TECHNICAL (di tabel qa_alat_rusak).
 * =================================================================== */
if ($action == 'cek_alat') {
    try {
        $no_part=isset($_POST['no_part'])?normalisasi_no_part($konek,$_POST['no_part']):'';
        if($no_part===''){echo json_encode(['found'=>false,'error_msg'=>'Nomor alat wajib diisi.']);exit;}
        // FINAL FLOW NON-OK V3 - fail-safe utama: keputusan final approved mengalahkan registry/master legacy.
        $lockedFinal=qaLatestApprovedNonOk($konek,$no_part);
        if($lockedFinal){
            $lockStatus=$lockedFinal['status']; $lockRow=$lockedFinal['row']; $defsLock=qaArchiveDefs(); $defLock=$defsLock[$lockStatus]??null;
            $regLock=qaGetRegistry($konek,$no_part);
            // Repair otomatis data legacy yang sudah final tetapi belum sempat terarsip.
            if($defLock && (!$regLock || strtoupper((string)($regLock['status_alat']??''))==='AKTIF')){ try{ qaFinalizeApprovedResult($konek,(int)$lockRow['id']); }catch(Exception $repairEx){} $regLock=qaGetRegistry($konek,$no_part); }
            $permanentLock=$defLock ? (bool)$defLock['permanent'] : true;
            echo json_encode(['found'=>false,'is_archived'=>true,'is_rusak'=>$permanentLock,'archive_status'=>$lockStatus,'original_no_part'=>$no_part,'can_restore'=>(qaIsAdmin()&&!$permanentLock),'registry'=>$regLock,'history'=>qaRegistryHistory($konek,(int)($regLock['id']??0),$no_part,5),'message'=>$permanentLock?'Nomor alat ini NONAKTIF PERMANEN karena '.$lockStatus.' dan tidak dapat digunakan kembali.':'Nomor alat ini NONAKTIF karena '.$lockStatus.'. Hanya Admin yang dapat mengaktifkan kembali beserta history alat.'],JSON_UNESCAPED_UNICODE); exit;
        }
        $reg=qaGetRegistry($konek,$no_part);
        if($reg){
            $rid=(int)$reg['id']; $st=strtoupper(trim((string)$reg['status_alat']));
            if($st==='AKTIF'){
                // CEK ALAT OFF V2 - fail-safe: bila alat sudah ada di tabel arsip, jangan pernah izinkan kalibrasi walau registry legacy masih AKTIF.
                foreach(qaArchiveDefs() as $offLabel=>$offDef){
                    $qOff=mysqli_query($konek,"SELECT id FROM `{$offDef['table']}` WHERE alat_registry_id='$rid' OR UPPER(TRIM(no_part))='$no_part' LIMIT 1");
                    if($qOff && mysqli_num_rows($qOff)>0){
                        $offStatus=$offDef['permanent']?'BLOKIR_PERMANEN':'NONAKTIF_SEMENTARA';
                        qaRegistrySet($konek,$no_part,$offStatus,$offLabel,$offLabel,date('Y-m-d'),$offDef['table'],(int)mysqli_fetch_assoc($qOff)['id'],null,null,null);
                        $reg=qaGetRegistry($konek,$no_part); $st=$offStatus;
                        break;
                    }
                }
                $q=mysqli_query($konek,"SELECT * FROM qa_part WHERE alat_registry_id='$rid' OR UPPER(TRIM(no_part))='$no_part' LIMIT 1"); $alat=$q?mysqli_fetch_assoc($q):null;
                if($alat && $st==='AKTIF'){
                    $alat['registry_id']=$rid; $alat['registry_status']='AKTIF'; $alat['hasil_terakhir']=$reg['hasil_terakhir']; $alat['alasan_status']=$reg['alasan_status'];
                    echo json_encode(['found'=>true,'data'=>$alat,'history'=>qaRegistryHistory($konek,$rid,$no_part,5),'registry'=>$reg],JSON_UNESCAPED_UNICODE); exit;
                }
            }
            $archiveTable=$reg['sumber_tabel']??''; $allowed=array_column(qaArchiveDefs(),'table');
            if(!in_array($archiveTable,$allowed,true)){
                $archiveTable=''; foreach(qaArchiveDefs() as $label=>$def){if(strtoupper($label)===strtoupper((string)$reg['alasan_status'])){$archiveTable=$def['table'];break;}}
            }
            $arc=null; if($archiveTable!==''){$qArc=mysqli_query($konek,"SELECT * FROM `$archiveTable` WHERE alat_registry_id='$rid' OR UPPER(TRIM(no_part))='$no_part' ORDER BY id DESC LIMIT 1");$arc=$qArc?mysqli_fetch_assoc($qArc):null;}
            $permanent=($st==='BLOKIR_PERMANEN'); $reason=$reg['alasan_status']?:$reg['hasil_terakhir']?:'ARSIP';
            echo json_encode(['found'=>false,'is_archived'=>true,'is_rusak'=>$permanent,'archive_status'=>$reason,'archive_table'=>$archiveTable,'archive_no_part'=>$no_part,'original_no_part'=>$no_part,'nomor_arsip'=>$arc['nomor_arsip']??($reg['nomor_arsip_terakhir']??''),'can_restore'=>(qaIsAdmin()&&$st==='NONAKTIF_SEMENTARA'),'archive_data'=>$arc,'registry'=>$reg,'history'=>qaRegistryHistory($konek,$rid,$no_part,5),'message'=>$permanent?"Nomor alat ini sudah diblokir permanen karena {$reason} dan tidak dapat digunakan kembali.":"Nomor alat ini sedang nonaktif karena {$reason}. Admin dapat mengaktifkan kembali nomor alat ini."],JSON_UNESCAPED_UNICODE); exit;
        }
        $q=mysqli_query($konek,"SELECT * FROM qa_part WHERE UPPER(TRIM(no_part))='$no_part' LIMIT 1");
        if($q&&mysqli_num_rows($q)>0){$alat=mysqli_fetch_assoc($q);echo json_encode(['found'=>true,'data'=>$alat,'history'=>qaRegistryHistory($konek,(int)($alat['alat_registry_id']??0),$no_part,5)],JSON_UNESCAPED_UNICODE);exit;}
        echo json_encode(['found'=>false]);
    }catch(Exception $e){echo json_encode(['found'=>false,'error_msg'=>$e->getMessage()]);}
    exit;
}

/* =====================================================================
 * ACTION: simpan_alat
 * Registrasi alat baru. Menolak jika no_part masih aktif ATAU jika
 * no_part yang diminta adalah "hantu" dari nomor yang sudah divonis NOT GOOD/RUSAK/DISCONTINUE/DITARIK TECHNICAL
 * (mengandung pola arsip internal) - [MEETING] no alat Bad tidak bisa dipakai lagi.
 * =================================================================== */
elseif ($action == 'restore_alat') {
    if (!qaIsAdmin()) {
        http_response_code(403);
        echo json_encode(['status'=>'error','message'=>'Hanya admin yang dapat mengaktifkan kembali nomor alat.']);
        exit;
    }

    try {
        // NO ALAT selalu pure. archive_no_part/nomor_arsip hanya dipakai
        // sebagai informasi tambahan; sumber kebenaran adalah registry.
        $no_part = normalisasi_no_part($konek, $_POST['no_part'] ?? $_POST['archive_no_part'] ?? '');
        if ($no_part === '') {
            throw new Exception('Nomor alat tidak valid.');
        }

        $reg = qaGetRegistry($konek, $no_part);
        if (!$reg) {
            throw new Exception('Registry nomor alat tidak ditemukan.');
        }

        $registryId = (int)$reg['id'];
        $registryStatus = strtoupper(trim((string)($reg['status_alat'] ?? '')));
        if ($registryStatus !== 'NONAKTIF_SEMENTARA') {
            if ($registryStatus === 'BLOKIR_PERMANEN') {
                throw new Exception('Nomor alat '.$no_part.' bersifat permanen dan tidak dapat diaktifkan kembali.');
            }
            throw new Exception('Nomor alat ini tidak berstatus nonaktif sementara.');
        }

        $defs = qaArchiveDefs();
        $archiveTable = trim((string)($_POST['archive_table'] ?? ''));
        $allowedTables = array_column($defs, 'table');
        if (!in_array($archiveTable, $allowedTables, true)) {
            $archiveTable = '';
            $reason = strtoupper(trim((string)($reg['alasan_status'] ?? '')));
            foreach ($defs as $label => $def) {
                if ($archiveTable === '' && (strtoupper($label) === $reason || strtoupper($def['label']) === $reason)) {
                    $archiveTable = $def['table'];
                }
            }
        }
        if (!in_array($archiveTable, $allowedTables, true)) {
            throw new Exception('Tabel arsip nomor alat tidak dapat ditentukan.');
        }

        $def = null;
        foreach ($defs as $d) {
            if ($d['table'] === $archiveTable) { $def = $d; break; }
        }
        if (!$def || $def['permanent']) {
            throw new Exception('Nomor alat ini tidak dapat diaktifkan kembali.');
        }

        // Pastikan kolom yang dibutuhkan tersedia pada arsip lama.
        if (!mysqli_query($konek, "CREATE TABLE IF NOT EXISTS `$archiveTable` LIKE `qa_part`")) {
            throw new Exception('Gagal menyiapkan tabel arsip: '.mysqli_error($konek));
        }
        pastikan_kolom($konek, $archiveTable, 'alat_registry_id', "INT(10) UNSIGNED NULL AFTER id");
        pastikan_kolom($konek, $archiveTable, 'nomor_arsip', "VARCHAR(150) NULL AFTER no_part");
    pastikan_kolom($konek, $archiveTable, 'no_laporan', "VARCHAR(150) NULL AFTER no_part");

        // Kalau ternyata master aktif sudah ada, cukup sinkronkan registry.
        $qActive = mysqli_query($konek, "SELECT * FROM qa_part WHERE (alat_registry_id='$registryId' OR UPPER(TRIM(no_part))='$no_part') LIMIT 1");
        $active = $qActive ? mysqli_fetch_assoc($qActive) : null;
        if ($active) {
            $activeId = (int)$active['id'];
            if (!mysqli_query($konek, "UPDATE qa_part SET alat_registry_id='$registryId', status='aktif' WHERE id='$activeId' LIMIT 1")) {
                throw new Exception('Gagal menyinkronkan alat aktif: '.mysqli_error($konek));
            }
            if (!mysqli_query($konek, "UPDATE qa_pengecekan SET alat_id='$activeId', alat_registry_id='$registryId', no_part='$no_part' WHERE alat_registry_id='$registryId' OR UPPER(TRIM(no_part))='$no_part'")) {
                throw new Exception('Gagal menyinkronkan histori nomor alat: '.mysqli_error($konek));
            }
            qaRegistrySet($konek, $no_part, 'AKTIF', null, $reg['hasil_terakhir'] ?? 'OK', $reg['tanggal_hasil_terakhir'] ?? date('Y-m-d'), 'qa_part', $activeId, $reg['nomor_arsip_terakhir'] ?? null, $nama_petugas, date('Y-m-d H:i:s'));
            echo json_encode(['status'=>'success','message'=>'Nomor alat berhasil diaktifkan kembali.','no_part'=>$no_part,'alat_id'=>$activeId], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Cari data arsip berdasarkan registry ID terlebih dahulu. Ini aman
        // karena no_part pada tabel arsip sekarang tetap murni.
        $arc = null;
        $qArc = mysqli_query($konek, "SELECT * FROM `$archiveTable` WHERE alat_registry_id='$registryId' ORDER BY id DESC LIMIT 1");
        if ($qArc) $arc = mysqli_fetch_assoc($qArc);
        if (!$arc) {
            $nomorArsip = trim((string)($_POST['nomor_arsip'] ?? ''));
            if ($nomorArsip !== '') {
                $na = esc($konek, $nomorArsip);
                $qArc = mysqli_query($konek, "SELECT * FROM `$archiveTable` WHERE UPPER(TRIM(no_part))='$no_part' AND nomor_arsip='$na' ORDER BY id DESC LIMIT 1");
            } else {
                $qArc = mysqli_query($konek, "SELECT * FROM `$archiveTable` WHERE UPPER(TRIM(no_part))='$no_part' ORDER BY id DESC LIMIT 1");
            }
            if ($qArc) $arc = mysqli_fetch_assoc($qArc);
        }
        if (!$arc) {
            throw new Exception('Data arsip nomor alat '.$no_part.' tidak ditemukan.');
        }

        // Jangan membuat dua No. Alat yang sama aktif.
        $noEsc = esc($konek, $no_part);
        $qDup = mysqli_query($konek, "SELECT id FROM qa_part WHERE UPPER(TRIM(no_part))='$noEsc' LIMIT 1");
        if ($qDup && mysqli_num_rows($qDup) > 0) {
            throw new Exception('Nomor alat '.$no_part.' sudah digunakan di data aktif.');
        }

        // MyISAM tidak mendukung rollback transaction. Karena requirement DB
        // proyek adalah MyISAM, operasi dilakukan berurutan dan setiap langkah
        // gagal akan menghentikan proses sebelum archive dihapus.
        $cols = [];
        $vals = [];
        foreach ($arc as $column => $value) {
            if ($column === 'id' || $column === 'nomor_arsip') continue;
            $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
            if ($safe === '') continue;
            $cols[] = '`'.$safe.'`';
            if ($column === 'no_part') {
                $vals[] = "'$noEsc'";
            } elseif ($column === 'alat_registry_id') {
                $vals[] = "'$registryId'";
            } elseif ($column === 'status') {
                $vals[] = "'aktif'";
            } elseif ($value === null) {
                $vals[] = 'NULL';
            } else {
                $vals[] = "'".esc($konek, (string)$value)."'";
            }
        }

        // Pastikan field registry selalu ikut diset pada master aktif.
        if (!in_array('`alat_registry_id`', $cols, true)) {
            $cols[] = '`alat_registry_id`';
            $vals[] = "'$registryId'";
        }

        $sqlInsert = 'INSERT INTO qa_part ('.implode(',', $cols).') VALUES ('.implode(',', $vals).')';
        if (!mysqli_query($konek, $sqlInsert)) {
            throw new Exception('Gagal mengaktifkan kembali data alat: '.mysqli_error($konek));
        }
        $newId = (int)mysqli_insert_id($konek);
        if ($newId <= 0) {
            throw new Exception('ID alat hasil aktivasi tidak terbentuk.');
        }

        if (!mysqli_query($konek, "UPDATE qa_pengecekan SET alat_id='$newId', alat_registry_id='$registryId', no_part='$noEsc' WHERE alat_registry_id='$registryId' OR UPPER(TRIM(no_part))='$noEsc'")) {
            mysqli_query($konek, "DELETE FROM qa_part WHERE id='$newId' LIMIT 1");
            throw new Exception('Gagal menyinkronkan histori nomor alat: '.mysqli_error($konek));
        }

        $archiveNo = $arc['nomor_arsip'] ?? ($reg['nomor_arsip_terakhir'] ?? null);
        qaRegistrySet($konek, $no_part, 'AKTIF', null, $reg['hasil_terakhir'] ?? 'OK', $reg['tanggal_hasil_terakhir'] ?? date('Y-m-d'), 'qa_part', $newId, $archiveNo, $nama_petugas, date('Y-m-d H:i:s'));

        // Setelah master aktif dan registry sudah sinkron, hapus salinan arsip.
        if (!mysqli_query($konek, "DELETE FROM `$archiveTable` WHERE id='".(int)$arc['id']."' LIMIT 1")) {
            throw new Exception('Nomor alat sudah aktif, tetapi salinan arsip gagal dihapus: '.mysqli_error($konek));
        }

        echo json_encode([
            'status'=>'success',
            'message'=>'Nomor alat berhasil diaktifkan kembali.',
            'no_part'=>$no_part,
            'alat_id'=>$newId
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
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
        // orang mendaftarkan ulang nomor arsip yang sudah divonis NOT GOOD/RUSAK/DISCONTINUE/DITARIK TECHNICAL secara langsung.
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
        $type = isset($_POST['type']) ? mysqli_real_escape_string($konek, trim((string)$_POST['type'])) : '';

        $ukuran   = isset($_POST['ukuran'])   ? mysqli_real_escape_string($konek, trim($_POST['ukuran']))   : '';
        $resolusi = isset($_POST['resolusi']) ? mysqli_real_escape_string($konek, trim($_POST['resolusi'])) : '';
        $lokasi   = isset($_POST['lokasi'])   ? mysqli_real_escape_string($konek, trim($_POST['lokasi']))   : '';
        $no_fa    = isset($_POST['no_fa'])    ? mysqli_real_escape_string($konek, trim($_POST['no_fa']))    : '';
        $merk     = isset($_POST['merk'])     ? mysqli_real_escape_string($konek, trim($_POST['merk']))     : '';
        $buatan   = isset($_POST['buatan'])   ? mysqli_real_escape_string($konek, trim($_POST['buatan']))   : '';
        $fungsi   = isset($_POST['fungsi'])   ? mysqli_real_escape_string($konek, trim($_POST['fungsi']))   : '';

        $tgl_manual = isset($_POST['tgl_manual']) ? mysqli_real_escape_string($konek, trim($_POST['tgl_manual'])) : '';

        $spesifikasi      = isset($_POST['spesifikasi'])      ? mysqli_real_escape_string($konek, trim($_POST['spesifikasi']))      : '';
        $toleransi_global = isset($_POST['toleransi_global'])  ? mysqli_real_escape_string($konek, trim($_POST['toleransi_global'])) : '';
        $satuan           = isset($_POST['satuan'])           ? mysqli_real_escape_string($konek, trim($_POST['satuan']))           : '';
        $metode_uji       = isset($_POST['metode_uji'])       ? mysqli_real_escape_string($konek, trim($_POST['metode_uji']))       : '';
        $standar_uji      = isset($_POST['standar_uji'])      ? mysqli_real_escape_string($konek, trim($_POST['standar_uji']))      : '';
        $traceability     = isset($_POST['traceability'])     ? mysqli_real_escape_string($konek, trim($_POST['traceability']))     : '';
        $format_laporan   = isset($_POST['format_laporan'])   ? mysqli_real_escape_string($konek, trim($_POST['format_laporan']))   : '';
        $titik_standar_raw = isset($_POST['titik_standar_json']) ? (string)$_POST['titik_standar_json'] : '[]';
        $titik_standar_json = mysqli_real_escape_string($konek, $titik_standar_raw);
        // No. Laporan dibuat otomatis setelah format dan tanggal laporan tervalidasi.

        // FORMAT W DB V1 - validasi registrasi menerima format A sampai W
        $format_laporan = strtoupper(trim((string)$format_laporan));
        $format_valid = str_split('ABCDEFGHIJKLMNOPQRSTUVW');
        if (!in_array($format_laporan, $format_valid)) {
            echo json_encode(['status' => 'error', 'message' => 'Format laporan tidak valid.']);
            exit;
        }

        $tgl_start        = !empty($tgl_manual) ? $tgl_manual : date('Y-m-d');
        $jenis_laporan    = qaJenisLaporanDariFormat($format_laporan);
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

        mysqli_begin_transaction($konek);
        try {
            $existingRegistry=qaGetRegistry($konek,$no_part);
            if($existingRegistry){
                $st=strtoupper((string)$existingRegistry['status_alat']);
                if($st==='BLOKIR_PERMANEN')throw new Exception('Nomor Alat '.$no_part.' sudah diblokir permanen karena '.($existingRegistry['alasan_status']?:$existingRegistry['hasil_terakhir']).' dan tidak dapat digunakan kembali.');
                if($st==='NONAKTIF_SEMENTARA')throw new Exception('Nomor Alat '.$no_part.' sedang nonaktif karena '.($existingRegistry['alasan_status']?:'status sementara').'. Hanya Admin yang dapat mengaktifkan kembali nomor alat ini.');
                throw new Exception('Nomor Alat '.$no_part.' sudah terdaftar dan masih aktif.');
            }
            $no_laporan_raw = qaGenerateNoLaporan($konek, $jenis_laporan, $tgl_start);
            $no_laporan = esc($konek, $no_laporan_raw);
            $registrySql="INSERT INTO qa_alat_registry (no_part,status_alat,hasil_terakhir,tanggal_hasil_terakhir,created_by) VALUES ('".esc($konek,$no_part)."','AKTIF','OK','".esc($konek,$tgl_start)."','".esc($konek,$nama_petugas)."')";
            if(!mysqli_query($konek,$registrySql))throw new Exception('Gagal membuat registry nomor alat: '.mysqli_error($konek));
            $registryId=(int)mysqli_insert_id($konek); if($registryId<=0)throw new Exception('Registry nomor alat tidak terbentuk.');
            $sql="INSERT INTO qa_part
                    (alat_registry_id,no_part,no_laporan,jenis_laporan,nama_part,seksi_pemilik,periode_kalibrasi,jadwal_kalibrasi,status,created_at,ukuran,resolusi,lokasi,type,kls,foto_alat,spesifikasi,toleransi_global,satuan,metode_uji,standar_uji,traceability,format_laporan,titik_standar_json,no_fa,merk,buatan,fungsi)
                    VALUES
                    ('$registryId','$no_part','$no_laporan','$jenis_laporan','$nama_part','$seksi','$periode','$jadwal_kalibrasi','aktif','$created_at','$ukuran','$resolusi','$lokasi','$type','$kls','$nama_file_foto','$spesifikasi','$toleransi_global','$satuan','$metode_uji','$standar_uji','$traceability','$format_laporan','$titik_standar_json','$no_fa','$merk','$buatan','$fungsi')";
            if(!mysqli_query($konek,$sql))throw new Exception(mysqli_error($konek));
            $alat_id=(int)mysqli_insert_id($konek);if($alat_id<=0)throw new Exception('ID alat tidak terbentuk.');
            if(!mysqli_query($konek,"UPDATE qa_alat_registry SET sumber_tabel='qa_part',sumber_id='$alat_id' WHERE id='$registryId' LIMIT 1"))throw new Exception(mysqli_error($konek));
            $nomor_pengecekan='QA-REG-'.date('YmdHis').'-'.$alat_id;
            $sql_hist="INSERT INTO qa_pengecekan
                (alat_registry_id,alat_id,no_part,nomor_pengecekan,no_laporan,jenis_laporan,tanggal_pengecekan,tanggal_jadwal_saat_pengecekan,status_kalibrasi,status_validasi,jenis_transaksi,seksi_qa,operator,periode_berikutnya,created_by,keterangan,created_at,hasil_json)
                VALUES
                ('$registryId','$alat_id','$no_part','$nomor_pengecekan','$no_laporan','$jenis_laporan','$tgl_start','$tgl_start','Good','setuju','registrasi_alat','QA','$nama_petugas','$jadwal_kalibrasi','$nama_petugas','~ Registrasi Alat Baru','$created_at','$titik_standar_json')";
            if(!mysqli_query($konek,$sql_hist))throw new Exception(mysqli_error($konek));
            // Payload cetak registrasi menggunakan renderer laporan kalibrasi yang sama.
            $regRows = json_decode($titik_standar_raw, true);
            if (!is_array($regRows)) $regRows = [];
            $formatUpper = strtoupper((string)$format_laporan);
            if ($formatUpper === 'A') {
                $goRow = []; $ngRow = [];
                foreach ($regRows as $rr) {
                    $pos = strtoupper(trim((string)($rr['posisi'] ?? '')));
                    if ($pos === 'GO' && !$goRow) $goRow = $rr;
                    if ($pos === 'NOT GO' && !$ngRow) $ngRow = $rr;
                }
                $regRows = [[
                    'kode_varian'=>'', 'kode_warna'=>'', 'warna'=>'', 'kep'=>'OK', 'keputusan'=>'OK',
                    'keterangan'=>'Registrasi Alat Baru', 'alat_standar'=>[
                        'go'=>['no_alat'=>$goRow['std_no_alat']??'','spesifikasi'=>$goRow['std_spesifikasi']??''],
                        'not_go'=>['no_alat'=>$ngRow['std_no_alat']??'','spesifikasi'=>$ngRow['std_spesifikasi']??'']
                    ],
                    'go'=>$goRow, 'not_go'=>$ngRow, 'periksa_berikut'=>$jadwal_kalibrasi
                ]];
            }
            $registrationPrint = [
                'no_laporan'=>$no_laporan_raw, 'jenis_laporan'=>$jenis_laporan, 'no_part'=>$no_part, 'no_alat'=>$no_part,
                'nama_part'=>trim($_POST['nama_part']??''), 'nama_alat'=>trim($_POST['nama_part']??''),
                'spesifikasi'=>trim($_POST['spesifikasi']??''), 'seksi_pemilik'=>trim($_POST['seksi']??''), 'seksi'=>trim($_POST['seksi']??''),
                'lokasi'=>trim($_POST['lokasi']??''), 'resolusi'=>trim($_POST['resolusi']??''),
                'no_fa'=>trim($_POST['no_fa']??''), 'merk'=>trim($_POST['merk']??''), 'buatan'=>trim($_POST['buatan']??''), 'fungsi'=>trim($_POST['fungsi']??''), 'type'=>trim($_POST['type']??''),
                'metode_uji'=>trim($_POST['metode_uji']??''), 'standar_uji'=>trim($_POST['standar_uji']??''), 'traceability'=>trim($_POST['traceability']??''),
                'suhu'=>'', 'kelembapan'=>'', 'ketidakpastian'=>'', 'confidence_level'=>'',
                'keterangan'=>'Registrasi Alat Baru', 'format'=>$formatUpper, 'format_laporan'=>$formatUpper, 'hasil_array'=>$regRows,
                'tanggal_periksa'=>$tgl_start, 'tanggal_pengecekan'=>$tgl_start, 'jadwal_berikutnya'=>$jadwal_kalibrasi, 'periode_berikutnya'=>$jadwal_kalibrasi,
                'operator'=>$nama_petugas, 'petugas'=>$nama_petugas, 'pemeriksa'=>'', 'penyetuju'=>'',
                'foreman'=>'', 'supervisor'=>'', 'signature_pembuat'=>true, 'signature_pemeriksa'=>false, 'signature_penyetuju'=>false,
                'status_final'=>'OK', 'keputusan'=>'OK', 'status_validasi'=>'setuju', 'nomor_pengecekan'=>$nomor_pengecekan,
                'titik_standar_json'=>$titik_standar_raw, 'audit'=>[]
            ];
            mysqli_commit($konek);
            echo json_encode(['status'=>'success','jadwal_selanjutnya'=>$jadwal_kalibrasi,'registry_id'=>$registryId,'no_laporan'=>$no_laporan_raw,'jenis_laporan'=>$jenis_laporan,'no_pengecekan'=>$nomor_pengecekan,'print_data'=>$registrationPrint]);
        } catch (Exception $ex) {
            mysqli_rollback($konek);
            echo json_encode(['status'=>'error','message'=>'Gagal menyimpan: '.$ex->getMessage()]);
        }
    } catch (Exception $e) {
        echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    }
    exit;
}

/* =====================================================================
 * ACTION: simpan_qa
 * Update kalibrasi berkala. Jika status_qa === 'Bad', alat dikarantina
 * ke qa_alat_rusak dan nomor lama dibebaskan untuk unit fisik baru.
 * [MEETING] operator otomatis dari user login; status hasil disimpan sesuai 5 status final
 * (bukan 'NOT GOOD'); riwayat baru masuk status_validasi = 'menunggu_pemeriksa'.
 * =================================================================== */
elseif ($action == 'simpan_qa') {
    try {
        $no_part_raw = isset($_POST['no_part']) ? trim($_POST['no_part']) : '';
        $no_part     = normalisasi_no_part($konek, $no_part_raw);
        $status_raw  = isset($_POST['status_qa']) ? trim($_POST['status_qa']) : '';
        $status_qa   = qaNormalizeFinalStatus($status_raw);
        $kode_warna  = isset($_POST['kode_warna']) ? trim($_POST['kode_warna']) : '';
        if ($status_qa === '') { echo json_encode(['status'=>'error','message'=>'Keputusan Final tidak valid. Pilih OK, NOT GOOD, RUSAK, DISCONTINUE, atau DITARIK TECHNICAL.']); exit; }
        $seksi_qa    = 'QA';

        if ($no_part === '') {
            echo json_encode(['status' => 'error', 'message' => 'Nomor alat wajib diisi.']);
            exit;
        }
        // FINAL FLOW NON-OK V3 - backend tidak pernah menerima kalibrasi baru untuk alat yang sudah final non-OK.
        $lockedFinal = qaLatestApprovedNonOk($konek,$no_part);
        if($lockedFinal){ echo json_encode(['status'=>'error','message'=>'Nomor alat sudah NONAKTIF karena '.$lockedFinal['status'].'. Tidak ada kalibrasi lanjutan untuk alat ini.']); exit; }

        // Operator pengecekan selalu user login; nilai dari browser tidak dipakai.
        $operator = mysqli_real_escape_string($konek, $nama_petugas);

        $suhu             = isset($_POST['suhu']) ? mysqli_real_escape_string($konek, trim($_POST['suhu'])) : '';
        $kelembapan       = isset($_POST['kelembapan']) ? mysqli_real_escape_string($konek, trim($_POST['kelembapan'])) : '';
        $ketidakpastian   = isset($_POST['ketidakpastian']) ? mysqli_real_escape_string($konek, trim($_POST['ketidakpastian'])) : '';
        $confidence       = isset($_POST['confidence']) ? mysqli_real_escape_string($konek, trim($_POST['confidence'])) : '';
        $keterangan       = isset($_POST['keterangan']) ? mysqli_real_escape_string($konek, trim($_POST['keterangan'])) : '';
        $traceability     = isset($_POST['traceability']) ? mysqli_real_escape_string($konek, trim($_POST['traceability'])) : '';
        $nama_part        = isset($_POST['nama_part']) ? mysqli_real_escape_string($konek, trim($_POST['nama_part'])) : '';
        $type             = isset($_POST['type']) ? mysqli_real_escape_string($konek, trim((string)$_POST['type'])) : '';
        $spesifikasi      = isset($_POST['spesifikasi']) ? mysqli_real_escape_string($konek, trim($_POST['spesifikasi'])) : '';
        $resolusi         = isset($_POST['resolusi']) ? mysqli_real_escape_string($konek, trim($_POST['resolusi'])) : '';
        $metode_uji       = isset($_POST['metode_uji']) ? mysqli_real_escape_string($konek, trim($_POST['metode_uji'])) : '';
        $standar_uji      = isset($_POST['standar_uji']) ? mysqli_real_escape_string($konek, trim($_POST['standar_uji'])) : '';
        $toleransi_global = isset($_POST['toleransi_global']) ? mysqli_real_escape_string($konek, trim($_POST['toleransi_global'])) : '';
        $satuan           = isset($_POST['satuan']) ? mysqli_real_escape_string($konek, trim($_POST['satuan'])) : '';
        $lokasi           = isset($_POST['lokasi']) ? mysqli_real_escape_string($konek, trim($_POST['lokasi'])) : '';
        $format_laporan   = isset($_POST['format_laporan']) ? strtoupper(trim($_POST['format_laporan'])) : '';
        $hasil_raw        = isset($_POST['hasil_json']) ? $_POST['hasil_json'] : '[]';

        // FORMAT W DB V1 - validasi update kalibrasi menerima format A sampai W
        if (!in_array($format_laporan, str_split('ABCDEFGHIJKLMNOPQRSTUVW'), true)) {
            echo json_encode(['status' => 'error', 'message' => 'Format laporan tidak valid.']);
            exit;
        }

        $tgl_pengecekan = date('Y-m-d');
        $jenis_laporan = strtolower(trim((string)($_POST['jenis_laporan'] ?? '')));
        if (!in_array($jenis_laporan, ['periksa','kalibrasi'], true)) {
            echo json_encode(['status'=>'error','message'=>'Jenis laporan wajib dipilih: Laporan Pemeriksaan atau Laporan Kalibrasi.']);
            exit;
        }
        $waktu_sekarang = date('Y-m-d H:i:s');

        $q_part = mysqli_query($konek, "SELECT * FROM qa_part WHERE UPPER(TRIM(no_part)) = '$no_part' LIMIT 1");
        $d_part = $q_part ? mysqli_fetch_assoc($q_part) : null;
        if (!$d_part) {
            echo json_encode(['status' => 'error', 'message' => 'Alat tidak ditemukan atau sudah tidak aktif.']);
            exit;
        }
        $registryId=(int)($d_part['alat_registry_id']??0); if($registryId<=0){$regTmp=qaGetRegistry($konek,$no_part);$registryId=(int)($regTmp['id']??0);} if($registryId<=0){echo json_encode(['status'=>'error','message'=>'Registry nomor alat belum tersedia. Jalankan migration registry terlebih dahulu.']);exit;}
        $regCurrent=qaGetRegistry($konek,$no_part); if($regCurrent && strtoupper((string)$regCurrent['status_alat'])!=='AKTIF'){echo json_encode(['status'=>'error','message'=>'Nomor alat sedang tidak aktif dan tidak dapat digunakan untuk update hasil.']);exit;}
        $no_laporan_raw = qaGenerateNoLaporan($konek, $jenis_laporan, $tgl_pengecekan);
        $no_laporan = esc($konek, $no_laporan_raw);

        // Semua field editable saat pengecekan: jika dikirim kosong, pertahankan nilai master.
        $nama_part        = $nama_part !== '' ? $nama_part : mysqli_real_escape_string($konek, $d_part['nama_part'] ?? '');
        $type             = $type !== '' ? $type : mysqli_real_escape_string($konek, $d_part['type'] ?? '');
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
                type='$type',
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
            // Jadwal berikutnya ditampilkan pada print sejak hasil diajukan.
            // Master qa_part baru benar-benar diperbarui saat kedua validator menyetujui.
            // FINAL FLOW NON-OK V3 - selain OK tidak punya jadwal kalibrasi lanjutan.
            $jadwal_baru = ($status_qa === 'OK') ? date('Y-m-d', strtotime($tgl_pengecekan . ' +' . $periode . ' days')) : null;
            $no_part_final = $no_part_asli;

            $snapshot_json = build_hasil_snapshot_json($hasil_raw, [
                'format'            => $format_laporan,
                'no_part'           => $no_part_final,
                'no_laporan'       => $no_laporan_raw,
                'jenis_laporan'    => $jenis_laporan,
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
                'kode_warna'        => $kode_warna,
            ]);
            $snapshot_json_sql = mysqli_real_escape_string($konek, $snapshot_json);

            $jadwal_sql = $jadwal_baru ? "'" . mysqli_real_escape_string($konek, $jadwal_baru) . "'" : 'NULL';
            $sql_hist = "INSERT INTO qa_pengecekan
                (alat_registry_id, alat_id, no_part, nomor_pengecekan, no_laporan, jenis_laporan, tanggal_pengecekan,
                 tanggal_jadwal_saat_pengecekan, status_kalibrasi, status_validasi, jenis_transaksi,
                 seksi_qa, operator, periode_berikutnya, created_by, keterangan,
                 created_at, suhu, kelembapan, ketidakpastian, confidence_level, hasil_json)
                VALUES
                ('$registryId', '$alat_id', '$no_part_final', '$nomor_pengecekan', '$no_laporan', '$jenis_laporan', '$tgl_pengecekan',
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
                'jenis_laporan'      => $jenis_laporan,
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
        COALESCE(p.seksi_pemilik, ar.seksi_pemilik, an.seksi_pemilik, ad.seksi_pemilik, at.seksi_pemilik) AS seksi_pemilik,
        COALESCE(p.titik_standar_json, ar.titik_standar_json, an.titik_standar_json, ad.titik_standar_json, at.titik_standar_json) AS db_titik_standar_json
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
    $tanggal_validasi_pemeriksa = '';
    $tanggal_validasi_penyetuju = '';
    $pOk = false;
    $sOk = false;
    foreach ($audit as $a) {
        if (($a['tahap'] ?? '') === 'pemeriksa' && ($a['keputusan'] ?? '') === 'setuju') {
            $pemeriksa = (string)$a['nama_validator'];
            $tanggal_validasi_pemeriksa = (string)($a['created_at'] ?? '');
            $pOk = true;
        }
        if (($a['tahap'] ?? '') === 'penyetuju' && ($a['keputusan'] ?? '') === 'setuju') {
            $penyetuju = (string)$a['nama_validator'];
            $tanggal_validasi_penyetuju = (string)($a['created_at'] ?? '');
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

    // Registrasi memakai renderer cetak laporan kalibrasi yang sama.
    // Ambil master yang menyimpan format + titik_standar_json karena histori registrasi
    // memang tidak melakukan pengukuran actual.
    if (($row['jenis_transaksi'] ?? '') === 'registrasi_alat') {
        $m = null;
        $rid = (int)($row['alat_registry_id'] ?? 0);
        if ($rid > 0) {
            $qm = mysqli_query($konek, "SELECT * FROM qa_part WHERE alat_registry_id='$rid' LIMIT 1");
            $m = $qm ? (mysqli_fetch_assoc($qm) ?: null) : null;
            if (!$m) {
                foreach (array_column(qaArchiveDefs(),'table') as $tbl) {
                    $qm = mysqli_query($konek, "SELECT * FROM `$tbl` WHERE alat_registry_id='$rid' ORDER BY id DESC LIMIT 1");
                    if ($qm && ($tmp=mysqli_fetch_assoc($qm))) { $m=$tmp; break; }
                }
            }
        }
        if (!$m && !empty($row['alat_id'])) {
            $qm = mysqli_query($konek, "SELECT * FROM qa_part WHERE id='".(int)$row['alat_id']."' LIMIT 1");
            $m = $qm ? (mysqli_fetch_assoc($qm) ?: null) : null;
        }
        if ($m) {
            $format = strtoupper((string)($m['format_laporan'] ?? $format));
            $tjs = (string)($m['titik_standar_json'] ?? '[]');
            $masterRows = json_decode($tjs, true);
            if (is_array($masterRows)) {
                if ($format === 'A') {
                    $goRow=[]; $ngRow=[];
                    foreach($masterRows as $rr){
                        $pos=strtoupper(trim((string)($rr['posisi']??'')));
                        if($pos==='GO'&&!$goRow)$goRow=$rr;
                        if($pos==='NOT GO'&&!$ngRow)$ngRow=$rr;
                    }
                    $rows=[[
                        'kode_varian'=>'','kode_warna'=>'','warna'=>'','kep'=>'OK','keputusan'=>'OK','keterangan'=>'Registrasi Alat Baru',
                        'alat_standar'=>['go'=>['no_alat'=>$goRow['std_no_alat']??'','spesifikasi'=>$goRow['std_spesifikasi']??''], 'not_go'=>['no_alat'=>$ngRow['std_no_alat']??'','spesifikasi'=>$ngRow['std_spesifikasi']??'']],
                        'go'=>$goRow,'not_go'=>$ngRow,'periksa_berikut'=>(qaNormalizeFinalStatus($row['status_kalibrasi']??'')==='OK' ? ($row['periode_berikutnya']??'') : '')
                    ]];
                } else {
                    $rows=$masterRows;
                }
                $h['titik_standar_json']=$tjs;
            }
            if (empty($h['nama_part'])) $h['nama_part']=$m['nama_part']??'';
            if (empty($h['spesifikasi'])) $h['spesifikasi']=$m['spesifikasi']??'';
            if (empty($h['seksi_pemilik'])) $h['seksi_pemilik']=$m['seksi_pemilik']??'';
            $h['lokasi']=$m['lokasi']??($h['lokasi']??'');
            $h['resolusi']=$m['resolusi']??($h['resolusi']??'');
            $h['metode_uji']=$m['metode_uji']??($h['metode_uji']??'');
            $h['standar_uji']=$m['standar_uji']??($h['standar_uji']??'');
            $h['traceability']=$m['traceability']??($h['traceability']??'');
            $h['toleransi_global']=$m['toleransi_global']??($h['toleransi_global']??'');
            $h['satuan']=$m['satuan']??($h['satuan']??'');
        }
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
        'jadwal_berikutnya' => (qaNormalizeFinalStatus($row['status_kalibrasi']??'')==='OK' ? $row['periode_berikutnya'] : null),
        'operator' => $row['operator'] ?? ($row['created_by'] ?? ''),
        'petugas' => $row['created_by'] ?? ($row['operator'] ?? ''),
        'foreman' => $pemeriksa,
        'supervisor' => $penyetuju,
        'pemeriksa' => $pemeriksa,
        'penyetuju' => $penyetuju,
        'tanggal_validasi_pemeriksa' => $tanggal_validasi_pemeriksa,
        'tanggal_validasi_penyetuju' => $tanggal_validasi_penyetuju,
        'validasi_pemeriksa_at' => $tanggal_validasi_pemeriksa,
        'validasi_penyetuju_at' => $tanggal_validasi_penyetuju,
        'signature_pembuat' => true,
        'signature_pemeriksa' => $pOk,
        'signature_penyetuju' => $sOk,
        'nomor_pengecekan' => $row['nomor_pengecekan'] ?? '',
        'no_laporan' => (($row['no_laporan'] ?? '') !== '' ? $row['no_laporan'] : ($row['nomor_pengecekan'] ?? '')),
        'jenis_laporan' => strtolower(trim((string)($row['jenis_laporan'] ?? ''))),
        'status_final' => qaNormalizeFinalStatus($row['status_kalibrasi'] ?? ''),
        'keputusan' => qaNormalizeFinalStatus($row['status_kalibrasi'] ?? ''),
        'status_validasi' => $row['status_validasi'] ?? '',
        'audit' => $audit,
        'titik_standar_json' => !empty($h['titik_standar_json']) ? $h['titik_standar_json'] : ($row['db_titik_standar_json'] ?? '')
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
        COALESCE(p.seksi_pemilik, ar.seksi_pemilik, an.seksi_pemilik, ad.seksi_pemilik, at.seksi_pemilik) AS seksi_pemilik,
        COALESCE(p.type, ar.type, an.type, ad.type, at.type) AS type,
        COALESCE(p.titik_standar_json, ar.titik_standar_json, an.titik_standar_json, ad.titik_standar_json, at.titik_standar_json) AS db_titik_standar_json
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
    $pemeriksa='';$penyetuju='';$tanggal_validasi_pemeriksa='';$tanggal_validasi_penyetuju='';$pOk=false;$sOk=false;
    foreach($audit as $a){ if($a['tahap']==='pemeriksa' && $a['keputusan']==='setuju'){ $pemeriksa=$a['nama_validator'];$tanggal_validasi_pemeriksa=$a['created_at']??'';$pOk=true; } if($a['tahap']==='penyetuju' && $a['keputusan']==='setuju'){ $penyetuju=$a['nama_validator'];$tanggal_validasi_penyetuju=$a['created_at']??'';$sOk=true; } }
    $raw=json_decode($row['hasil_json']?:'[]',true); $snap=$raw;
    if(is_array($raw)&&isset($raw['header'])){$h=$raw['header'];$env=$raw['environment']??[];$ft=$raw['footer']??[];$rows=$raw['rows']??[];} else {$h=[];$env=[];$ft=[];$rows=is_array($raw)?$raw:[];}

    // No Gambar Format P disimpan bersama row hasil. Pada halaman Transaksi
    // payload print dibangun ulang dari hasil_json, jadi ambil nilainya secara
    // rekursif agar tetap terbaca baik pada struktur flat maupun grouped.
    $no_gambar_print = '';
    $findNoGambar = function($value) use (&$findNoGambar, &$no_gambar_print) {
        if ($no_gambar_print !== '' || !is_array($value)) return;
        if (isset($value['no_gambar']) && trim((string)$value['no_gambar']) !== '') {
            $no_gambar_print = trim((string)$value['no_gambar']);
            return;
        }
        foreach ($value as $child) {
            if (is_array($child)) $findNoGambar($child);
            if ($no_gambar_print !== '') return;
        }
    };
    $findNoGambar($raw);
    if ($no_gambar_print === '') $findNoGambar($rows);

    $print=[
      'no_part'=>$row['no_part'],'no_alat'=>$row['no_part'],'nama_part'=>$row['nama_part']??($h['nama_part']??''),'nama_alat'=>$row['nama_part']??($h['nama_part']??''),'spesifikasi'=>$row['spesifikasi']??($h['spesifikasi']??''),'seksi_pemilik'=>$row['seksi_pemilik']??($h['seksi_pemilik']??''),'seksi'=>$row['seksi_pemilik']??($h['seksi_pemilik']??''),'metode'=>$h['metode_uji']??'','metode_uji'=>$h['metode_uji']??'','standar_uji'=>$h['standar_uji']??'','traceability'=>$h['traceability']??'','toleransi_global'=>$h['toleransi_global']??'','toleransi'=>$h['toleransi_global']??'','resolusi'=>$h['resolusi']??'','satuan'=>$h['satuan']??'','suhu'=>$row['suhu']??($env['suhu']??''),'kelembapan'=>$row['kelembapan']??($env['kelembapan']??''),'ketidakpastian'=>$row['ketidakpastian']??($env['ketidakpastian']??''),'confidence_level'=>$row['confidence_level']??($env['confidence_level']??''),'keterangan'=>$row['keterangan']??($ft['keterangan']??''),'format'=>$row['format_laporan']??($raw['format']??''),'hasil_array'=>$rows,'tanggal_periksa'=>$row['tanggal_pengecekan'],'jadwal_berikutnya'=>(qaNormalizeFinalStatus($row['status_kalibrasi']??'')==='OK' ? $row['periode_berikutnya'] : null),'operator'=>$row['operator']??($ft['operator']??$row['created_by']),'petugas'=>$row['created_by'],'foreman'=>$pemeriksa,'supervisor'=>$penyetuju,'pemeriksa'=>$pemeriksa,'penyetuju'=>$penyetuju,'tanggal_validasi_pemeriksa'=>$tanggal_validasi_pemeriksa,'tanggal_validasi_penyetuju'=>$tanggal_validasi_penyetuju,'validasi_pemeriksa_at'=>$tanggal_validasi_pemeriksa,'validasi_penyetuju_at'=>$tanggal_validasi_penyetuju,'signature_pembuat'=>true,'signature_pemeriksa'=>$pOk,'signature_penyetuju'=>$sOk,'nomor_pengecekan'=>$row['nomor_pengecekan'],'status_final'=>qaNormalizeFinalStatus($row['status_kalibrasi']??''),'keputusan'=>qaNormalizeFinalStatus($row['status_kalibrasi']??''),'status_validasi'=>$row['status_validasi'],'audit'=>$audit,'no_gambar'=>$no_gambar_print,'no_laporan'=>(($row['no_laporan'] ?? '') !== '' ? $row['no_laporan'] : $row['nomor_pengecekan']),'jenis_laporan'=>strtolower(trim((string)($row['jenis_laporan'] ?? ''))),'titik_standar_json'=>!empty($h['titik_standar_json']) ? $h['titik_standar_json'] : ($row['db_titik_standar_json'] ?? ''),'type'=>$row['type']??($h['type']??'')
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
    $no_part=isset($_POST['no_part'])?normalisasi_no_part($konek,$_POST['no_part']):'';
    if($no_part===''){echo json_encode(['status'=>'error','message'=>'Nomor alat wajib diisi.']);exit;}
    $reg=qaGetRegistry($konek,$no_part);$alat=null;
    if($reg){
        $src=$reg['sumber_tabel']??''; if($src==='qa_part'||$src===''){$q=mysqli_query($konek,"SELECT * FROM qa_part WHERE alat_registry_id='".(int)$reg['id']."' OR UPPER(TRIM(no_part))='$no_part' LIMIT 1");$alat=$q?mysqli_fetch_assoc($q):null;}
        elseif(in_array($src,array_column(qaArchiveDefs(),'table'),true)){$q=mysqli_query($konek,"SELECT * FROM `$src` WHERE alat_registry_id='".(int)$reg['id']."' OR UPPER(TRIM(no_part))='$no_part' ORDER BY id DESC LIMIT 1");$alat=$q?mysqli_fetch_assoc($q):null;}
    }
    if(!$alat){$q=mysqli_query($konek,"SELECT * FROM qa_part WHERE UPPER(TRIM(no_part))='$no_part' LIMIT 1");$alat=$q?mysqli_fetch_assoc($q):null;}
    if(!$alat){foreach(qaArchiveDefs() as $def){$q=mysqli_query($konek,"SELECT * FROM `{$def['table']}` WHERE UPPER(TRIM(no_part))='$no_part' ORDER BY id DESC LIMIT 1");if($q&&mysqli_num_rows($q)>0){$alat=mysqli_fetch_assoc($q);break;}}}
    if($alat){echo json_encode(['status'=>'success','alat'=>$alat,'history'=>qaRegistryHistory($konek,(int)($reg['id']??($alat['alat_registry_id']??0)),$no_part,1000),'registry'=>$reg],JSON_UNESCAPED_UNICODE);}else echo json_encode(['status'=>'error','message'=>'Alat tidak ditemukan.']);
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
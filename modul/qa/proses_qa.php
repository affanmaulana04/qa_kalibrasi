<?php
session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

include "../../inc/inc_koneksi.php";
header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$nama_petugas = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : (isset($_SESSION['userid']) ? $_SESSION['userid'] : 'SISTEM');

if ($action == 'cek_alat') {
    try {
        $no_part = isset($_POST['no_part']) ? mysqli_real_escape_string($konek, trim($_POST['no_part'])) : '';
        
        $query = mysqli_query($konek, "SELECT * FROM qa_part WHERE no_part = '$no_part'");
        
        if ($query && mysqli_num_rows($query) > 0) {
            $data_alat = mysqli_fetch_assoc($query);
            $history = [];
            
            // FIX BUG BOCOR: Limit dikembalikan mutlak ke 5 baris saja sesuai request awal
            $q_hist = mysqli_query($konek, "SELECT * FROM qa_pengecekan WHERE no_part = '$no_part' ORDER BY tanggal_pengecekan DESC LIMIT 5");
            if ($q_hist) {
                while ($row = mysqli_fetch_assoc($q_hist)) {
                    $history[] = [
                        'tanggal'  => $row['tanggal_pengecekan'],
                        'status'   => $row['status_kalibrasi'],
                        'seksi_qa' => $row['seksi_qa'],
                        'suhu' => $row['suhu'],
                        'kelembapan' => $row['kelembapan'],
                        'ketidakpastian' => $row['ketidakpastian'],
                        'confidence_level' => $row['confidence_level'],
                        'keterangan' => $row['keterangan'],
                        'hasil_json' => $row['hasil_json'],
                        'petugas' => $row['created_by'],
                        'jadwal_berikutnya' => $row['periode_berikutnya']
                    ];
                }
            }
            echo json_encode(['found' => true, 'data' => $data_alat, 'history' => $history]);
        } else {
            // Cek apakah ada di tabel alat rusak
            $q_rusak = mysqli_query($konek, "SHOW TABLES LIKE 'qa_alat_rusak'");
            if(mysqli_num_rows($q_rusak) > 0) {
                $cek_rusak = mysqli_query($konek, "SELECT no_part FROM qa_alat_rusak WHERE no_part = '$no_part'");
                if(mysqli_num_rows($cek_rusak) > 0) {
                    echo json_encode(['found' => false, 'is_rusak' => true, 'message' => 'ALAT INI SUDAH DIVONIS RUSAK / AFKIR. Silakan lihat di Database Alat Rusak.']);
                    exit;
                }
            }
            echo json_encode(['found' => false]);
        }
    } catch (Exception $e) { echo json_encode(['found' => false, 'error_msg' => $e->getMessage()]); }
    exit;
}

elseif ($action == 'simpan_alat') {
    try {
        $no_part   = mysqli_real_escape_string($konek, $_POST['no_part']);
        $nama_part = mysqli_real_escape_string($konek, $_POST['nama_part']);
        $seksi     = mysqli_real_escape_string($konek, $_POST['seksi']);
        $periode   = (int)$_POST['periode'];
        
        $kls       = ""; 
        $type      = "";
        
        $ukuran    = isset($_POST['ukuran']) ? mysqli_real_escape_string($konek, $_POST['ukuran']) : '';
        $resolusi  = isset($_POST['resolusi']) ? mysqli_real_escape_string($konek, $_POST['resolusi']) : '';
        $lokasi    = isset($_POST['lokasi']) ? mysqli_real_escape_string($konek, $_POST['lokasi']) : '';
        
        $tgl_manual = isset($_POST['tgl_manual']) ? mysqli_real_escape_string($konek, $_POST['tgl_manual']) : '';

        $spesifikasi = isset($_POST['spesifikasi']) ? mysqli_real_escape_string($konek, $_POST['spesifikasi']) : '';
        $toleransi_global = isset($_POST['toleransi_global']) ? mysqli_real_escape_string($konek, $_POST['toleransi_global']) : '';
        $satuan = isset($_POST['satuan']) ? mysqli_real_escape_string($konek, $_POST['satuan']) : '';
        $metode_uji = isset($_POST['metode_uji']) ? mysqli_real_escape_string($konek, $_POST['metode_uji']) : '';
        $standar_uji = isset($_POST['standar_uji']) ? mysqli_real_escape_string($konek, $_POST['standar_uji']) : '';
        $traceability = isset($_POST['traceability']) ? mysqli_real_escape_string($konek, $_POST['traceability']) : '';
        $format_laporan = isset($_POST['format_laporan']) ? mysqli_real_escape_string($konek, $_POST['format_laporan']) : '';
        $titik_standar_json = isset($_POST['titik_standar_json']) ? mysqli_real_escape_string($konek, $_POST['titik_standar_json']) : '[]';

        $tgl_start = !empty($tgl_manual) ? $tgl_manual : date('Y-m-d');
        $jadwal_kalibrasi = date('Y-m-d', strtotime($tgl_start . " +$periode days"));
        $created_at       = date('Y-m-d H:i:s');

        $nama_file_foto = '';
        if(isset($_FILES['foto_alat']) && $_FILES['foto_alat']['error'] == 0){
            if ($_FILES['foto_alat']['size'] > 10485760) { echo json_encode(['status' => 'error', 'message' => 'Gagal! Ukuran max 10MB.']); exit; }
            $folder_tujuan = "../../images/alat/";
            if (!is_dir($folder_tujuan)) { mkdir($folder_tujuan, 0777, true); }
            $ext = pathinfo($_FILES['foto_alat']['name'], PATHINFO_EXTENSION);
            $nama_file_foto = "ALAT_" . preg_replace('/[^A-Za-z0-9]/', '', $no_part) . "_" . time() . "." . $ext;
            move_uploaded_file($_FILES['foto_alat']['tmp_name'], $folder_tujuan . $nama_file_foto);
        }

        // Cek dulu apakah nomor ini beneran kosong
        $cek_dobel = mysqli_query($konek, "SELECT no_part FROM qa_part WHERE no_part = '$no_part'");
        if(mysqli_num_rows($cek_dobel) > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Nomor Alat ini masih aktif terdaftar! Jika alat sudah rusak, update hasil kalibrasinya jadi BURUK agar no alat bisa dipakai ulang.']);
            exit;
        }

        $sql = "INSERT INTO qa_part 
                (no_part, nama_part, seksi_pemilik, periode_kalibrasi, jadwal_kalibrasi, status, created_at, ukuran, resolusi, lokasi, type, kls, foto_alat, spesifikasi, toleransi_global, satuan, metode_uji, standar_uji, traceability, format_laporan, titik_standar_json) 
                VALUES 
                ('$no_part', '$nama_part', '$seksi', '$periode', '$jadwal_kalibrasi', 'aktif', '$created_at', '$ukuran', '$resolusi', '$lokasi', '$type', '$kls', '$nama_file_foto', '$spesifikasi', '$toleransi_global', '$satuan', '$metode_uji', '$standar_uji', '$traceability', '$format_laporan', '$titik_standar_json')";
        
        if (mysqli_query($konek, $sql)) {
            $alat_id = mysqli_insert_id($konek); 
            $nomor_pengecekan = "QA-REG-" . date('YmdHis') . "-" . $alat_id;
            
            $sql_hist = "INSERT INTO qa_pengecekan 
                         (alat_id, no_part, nomor_pengecekan, tanggal_pengecekan, tanggal_jadwal_saat_pengecekan, status_kalibrasi, seksi_qa, periode_berikutnya, created_by, keterangan, created_at) 
                         VALUES 
                         ('$alat_id', '$no_part', '$nomor_pengecekan', '$tgl_start', '$tgl_start', 'Good', 'QA', '$jadwal_kalibrasi', '$nama_petugas', '~ Registrasi', '$created_at')";
            mysqli_query($konek, $sql_hist);

            echo json_encode(['status' => 'success', 'jadwal_selanjutnya' => $jadwal_kalibrasi]);
        } else { echo json_encode(['status' => 'error', 'message' => mysqli_error($konek)]); }
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}

elseif ($action == 'simpan_qa') {
    try {
        $no_part    = mysqli_real_escape_string($konek, $_POST['no_part']);
        $status_qa  = mysqli_real_escape_string($konek, $_POST['status_qa']); 
        $seksi_qa   = "QA"; 
        
        $suhu = isset($_POST['suhu']) ? mysqli_real_escape_string($konek, $_POST['suhu']) : '';
        $kelembapan = isset($_POST['kelembapan']) ? mysqli_real_escape_string($konek, $_POST['kelembapan']) : '';
        $ketidakpastian = isset($_POST['ketidakpastian']) ? mysqli_real_escape_string($konek, $_POST['ketidakpastian']) : '';
        $confidence = isset($_POST['confidence']) ? mysqli_real_escape_string($konek, $_POST['confidence']) : '';
        $keterangan = isset($_POST['keterangan']) ? mysqli_real_escape_string($konek, $_POST['keterangan']) : '';
        $hasil_json = isset($_POST['hasil_json']) ? mysqli_real_escape_string($konek, $_POST['hasil_json']) : '[]';
        $traceability = isset($_POST['traceability']) ? mysqli_real_escape_string($konek, $_POST['traceability']) : '';

        $tgl_pengecekan = date('Y-m-d');
        $waktu_sekarang = date('Y-m-d H:i:s');
        
        $q_part = mysqli_query($konek, "SELECT id, jadwal_kalibrasi, periode_kalibrasi FROM qa_part WHERE no_part = '$no_part'");
        $d_part = mysqli_fetch_assoc($q_part);
        
        $alat_id = isset($d_part['id']) ? $d_part['id'] : 0;
        $tgl_jadwal_lama = isset($d_part['jadwal_kalibrasi']) ? $d_part['jadwal_kalibrasi'] : date('Y-m-d');
        $periode = (!empty($d_part['periode_kalibrasi'])) ? (int)$d_part['periode_kalibrasi'] : 30;

        $nomor_pengecekan = "QA-" . date('YmdHis') . "-" . $alat_id;
        $jadwal_baru = NULL;

        if ($status_qa === 'Bad') {
            $jadwal_baru_sql = "NULL"; 
            $no_part_rusak = $no_part . "_RUSAK_" . time(); // FIX: Supaya nomor lama bisa dipakai ulang

            // 1. Buat tabel qa_alat_rusak otomatis
            mysqli_query($konek, "CREATE TABLE IF NOT EXISTS qa_alat_rusak LIKE qa_part");
            
            // 2. Clone data ke qa_alat_rusak dengan nomor baru
            mysqli_query($konek, "INSERT INTO qa_alat_rusak SELECT * FROM qa_part WHERE no_part = '$no_part'");
            mysqli_query($konek, "UPDATE qa_alat_rusak SET no_part = '$no_part_rusak', status = 'rusak', jadwal_kalibrasi = NULL, traceability = '$traceability', updated_at = NOW() WHERE no_part = '$no_part'");
            
            // 3. Pindahkan seluruh riwayat pengecekan ke nomor yang rusak ini (supaya historinya kebawa)
            mysqli_query($konek, "UPDATE qa_pengecekan SET no_part = '$no_part_rusak' WHERE no_part = '$no_part'");

            // 4. Hapus dari database alat aktif biar no aslinya bisa dipakai ulang
            mysqli_query($konek, "DELETE FROM qa_part WHERE no_part = '$no_part'");

            // Pakai nomor yang rusak untuk insert histori saat ini
            $no_part = $no_part_rusak;

        } else {
            $jadwal_baru = date('Y-m-d', strtotime($tgl_pengecekan . " +$periode days"));
            $jadwal_baru_sql = "'$jadwal_baru'";
            $update_part = "UPDATE qa_part SET jadwal_kalibrasi = '$jadwal_baru', status = 'aktif', traceability = '$traceability', updated_at = NOW() WHERE no_part = '$no_part'";
            mysqli_query($konek, $update_part);
        }

        $sql_hist = "INSERT INTO qa_pengecekan 
                     (alat_id, no_part, nomor_pengecekan, tanggal_pengecekan, tanggal_jadwal_saat_pengecekan, status_kalibrasi, seksi_qa, periode_berikutnya, created_by, keterangan, created_at, suhu, kelembapan, ketidakpastian, confidence_level, hasil_json) 
                     VALUES 
                     ('$alat_id', '$no_part', '$nomor_pengecekan', '$tgl_pengecekan', '$tgl_jadwal_lama', '$status_qa', '$seksi_qa', $jadwal_baru_sql, '$nama_petugas', '$keterangan', '$waktu_sekarang', '$suhu', '$kelembapan', '$ketidakpastian', '$confidence', '$hasil_json')";
        
        if(mysqli_query($konek, $sql_hist)) {
            echo json_encode(['status' => 'success', 'jadwal_selanjutnya' => $jadwal_baru, 'no_pengecekan' => $nomor_pengecekan]);
        } else { echo json_encode(['status' => 'error', 'message' => mysqli_error($konek)]); }
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}

elseif ($action == 'get_bbrau') {
    $no_part = isset($_POST['no_part']) ? mysqli_real_escape_string($konek, trim($_POST['no_part'])) : '';
    
    $q1 = mysqli_query($konek, "SELECT * FROM qa_part WHERE no_part = '$no_part'");
    if(mysqli_num_rows($q1) > 0) { $alat = mysqli_fetch_assoc($q1); } 
    else {
        $q2 = mysqli_query($konek, "SELECT * FROM qa_alat_rusak WHERE no_part = '$no_part'");
        if(mysqli_num_rows($q2) > 0) { $alat = mysqli_fetch_assoc($q2); }
    }

    if(isset($alat)) {
        $history = [];
        $qh = mysqli_query($konek, "SELECT * FROM qa_pengecekan WHERE no_part = '$no_part' ORDER BY tanggal_pengecekan ASC");
        while($r = mysqli_fetch_assoc($qh)) { $history[] = $r; }
        echo json_encode(['status' => 'success', 'alat' => $alat, 'history' => $history]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Alat tidak ditemukan.']);
    }
    exit;
}

elseif ($action == 'simpan_master_seksi') {
    try {
        $nama_seksi = mysqli_real_escape_string($konek, trim($_POST['nama_seksi']));
        $cek = mysqli_query($konek, "SELECT * FROM qa_seksi_master WHERE nama_seksi = '$nama_seksi'");
        if (mysqli_num_rows($cek) > 0) { echo json_encode(['status' => 'error', 'message' => 'Seksi ini sudah ada di Database!']); } 
        else { 
            if(mysqli_query($konek, "INSERT INTO qa_seksi_master (nama_seksi) VALUES ('$nama_seksi')")) { echo json_encode(['status' => 'success']); } 
            else { echo json_encode(['status' => 'error', 'message' => 'Gagal Insert: ' . mysqli_error($konek)]); }
        }
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}

elseif ($action == 'hapus_master_seksi') {
    try {
        $nama_seksi = mysqli_real_escape_string($konek, trim($_POST['nama_seksi']));
        mysqli_query($konek, "DELETE FROM qa_seksi_master WHERE nama_seksi = '$nama_seksi'");
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}
?>
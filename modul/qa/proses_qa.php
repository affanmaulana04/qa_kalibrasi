<?php
session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

include "../../inc/inc_koneksi.php";
header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'cek_alat') {
    try {
        $no_part = isset($_POST['no_part']) ? mysqli_real_escape_string($konek, trim($_POST['no_part'])) : '';
        
        $query = mysqli_query($konek, "SELECT * FROM qa_part WHERE no_part = '$no_part'");
        
        if ($query && mysqli_num_rows($query) > 0) {
            $data_alat = mysqli_fetch_assoc($query);
            $history = [];
            
            $q_hist = mysqli_query($konek, "SELECT * FROM qa_pengecekan WHERE no_part = '$no_part' ORDER BY tanggal_pengecekan DESC LIMIT 4");
            if ($q_hist) {
                while ($row = mysqli_fetch_assoc($q_hist)) {
                    $history[] = [
                        'tanggal'  => $row['tanggal_pengecekan'],
                        'status'   => $row['status_kalibrasi'],
                        'seksi_qa' => $row['seksi_qa']
                    ];
                }
            }

            echo json_encode(['found' => true, 'data' => $data_alat, 'history' => $history]);
        } else {
            echo json_encode(['found' => false]);
        }
    } catch (Exception $e) {
        echo json_encode(['found' => false, 'error_msg' => $e->getMessage()]);
    }
    exit;
}

elseif ($action == 'simpan_alat') {
    try {
        $no_part   = mysqli_real_escape_string($konek, $_POST['no_part']);
        $nama_part = mysqli_real_escape_string($konek, $_POST['nama_part']);
        $seksi     = mysqli_real_escape_string($konek, $_POST['seksi']);
        $periode   = (int)$_POST['periode'];
        $kls       = mysqli_real_escape_string($konek, $_POST['kls']);
        
        $ukuran    = isset($_POST['ukuran']) ? mysqli_real_escape_string($konek, $_POST['ukuran']) : '';
        $resolusi  = isset($_POST['resolusi']) ? mysqli_real_escape_string($konek, $_POST['resolusi']) : '';
        $lokasi    = isset($_POST['lokasi']) ? mysqli_real_escape_string($konek, $_POST['lokasi']) : '';
        $type      = isset($_POST['type']) ? mysqli_real_escape_string($konek, $_POST['type']) : '';
        
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

        $sql = "INSERT INTO qa_part 
                (no_part, nama_part, seksi_pemilik, periode_kalibrasi, jadwal_kalibrasi, status, created_at, ukuran, resolusi, lokasi, type, kls, foto_alat, spesifikasi, toleransi_global, satuan, metode_uji, standar_uji, traceability, format_laporan, titik_standar_json) 
                VALUES 
                ('$no_part', '$nama_part', '$seksi', '$periode', '$jadwal_kalibrasi', 'aktif', '$created_at', '$ukuran', '$resolusi', '$lokasi', '$type', '$kls', '$nama_file_foto', '$spesifikasi', '$toleransi_global', '$satuan', '$metode_uji', '$standar_uji', '$traceability', '$format_laporan', '$titik_standar_json')";
        
        if (mysqli_query($konek, $sql)) {
            $alat_id = mysqli_insert_id($konek); 
            $nomor_pengecekan = "QA-REG-" . date('YmdHis') . "-" . $alat_id;
            $created_by = isset($_SESSION['userid']) ? mysqli_real_escape_string($konek, $_SESSION['userid']) : 'SISTEM';
            
            // STATUS DEFAULT UDAH DIGANTI JADI 'OK'
            $sql_hist = "INSERT INTO qa_pengecekan 
                         (alat_id, no_part, nomor_pengecekan, tanggal_pengecekan, tanggal_jadwal_saat_pengecekan, status_kalibrasi, seksi_qa, periode_berikutnya, created_by, keterangan, created_at) 
                         VALUES 
                         ('$alat_id', '$no_part', '$nomor_pengecekan', '$tgl_start', '$tgl_start', 'OK', 'QA', '$jadwal_kalibrasi', '$created_by', 'Registrasi Alat Awal', '$created_at')";
            mysqli_query($konek, $sql_hist);

            echo json_encode(['status' => 'success', 'jadwal_selanjutnya' => $jadwal_kalibrasi]);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($konek)]);
        }
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}

elseif ($action == 'simpan_qa') {
    try {
        $no_part    = mysqli_real_escape_string($konek, $_POST['no_part']);
        $status_qa  = mysqli_real_escape_string($konek, $_POST['status_qa']); 
        $seksi_qa   = "QA"; 
        $created_by = isset($_SESSION['userid']) ? mysqli_real_escape_string($konek, $_SESSION['userid']) : '';
        
        $suhu = isset($_POST['suhu']) ? mysqli_real_escape_string($konek, $_POST['suhu']) : '';
        $kelembapan = isset($_POST['kelembapan']) ? mysqli_real_escape_string($konek, $_POST['kelembapan']) : '';
        $ketidakpastian = isset($_POST['ketidakpastian']) ? mysqli_real_escape_string($konek, $_POST['ketidakpastian']) : '';
        $confidence = isset($_POST['confidence']) ? mysqli_real_escape_string($konek, $_POST['confidence']) : '';
        $keterangan = isset($_POST['keterangan']) ? mysqli_real_escape_string($konek, $_POST['keterangan']) : '';
        $hasil_json = isset($_POST['hasil_json']) ? mysqli_real_escape_string($konek, $_POST['hasil_json']) : '[]';

        $tgl_pengecekan = date('Y-m-d');
        $waktu_sekarang = date('Y-m-d H:i:s');
        
        $q_part = mysqli_query($konek, "SELECT id, jadwal_kalibrasi, periode_kalibrasi FROM qa_part WHERE no_part = '$no_part'");
        $d_part = mysqli_fetch_assoc($q_part);
        
        $alat_id = isset($d_part['id']) ? $d_part['id'] : 0;
        $tgl_jadwal_lama = isset($d_part['jadwal_kalibrasi']) ? $d_part['jadwal_kalibrasi'] : date('Y-m-d');
        $periode = (!empty($d_part['periode_kalibrasi'])) ? (int)$d_part['periode_kalibrasi'] : 30;

        $jadwal_baru = date('Y-m-d', strtotime($tgl_pengecekan . " +$periode days"));
        $nomor_pengecekan = "QA-" . date('YmdHis') . "-" . $alat_id;

        $sql_hist = "INSERT INTO qa_pengecekan 
                     (alat_id, no_part, nomor_pengecekan, tanggal_pengecekan, tanggal_jadwal_saat_pengecekan, status_kalibrasi, seksi_qa, periode_berikutnya, created_by, keterangan, created_at, suhu, kelembapan, ketidakpastian, confidence_level, hasil_json) 
                     VALUES 
                     ('$alat_id', '$no_part', '$nomor_pengecekan', '$tgl_pengecekan', '$tgl_jadwal_lama', '$status_qa', '$seksi_qa', '$jadwal_baru', '$created_by', '$keterangan', '$waktu_sekarang', '$suhu', '$kelembapan', '$ketidakpastian', '$confidence', '$hasil_json')";
        
        $insert_hist = mysqli_query($konek, $sql_hist);

        if($insert_hist) {
            mysqli_query($konek, "UPDATE qa_part SET jadwal_kalibrasi = '$jadwal_baru', updated_at = NOW() WHERE no_part = '$no_part'");
            echo json_encode(['status' => 'success', 'jadwal_selanjutnya' => $jadwal_baru, 'no_pengecekan' => $nomor_pengecekan]);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($konek)]);
        }
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}

elseif ($action == 'simpan_master_kls') {
    try {
        $nama_kelas = mysqli_real_escape_string($konek, trim($_POST['nama_kelas']));
        $cek = mysqli_query($konek, "SELECT * FROM qa_kls WHERE nama_kelas = '$nama_kelas'");
        if (mysqli_num_rows($cek) > 0) { 
            echo json_encode(['status' => 'error', 'message' => 'Nama Kelas ini sudah ada di Database!']); 
        } else { 
            if(mysqli_query($konek, "INSERT INTO qa_kls (nama_kelas) VALUES ('$nama_kelas')")) {
                echo json_encode(['status' => 'success']); 
            } else { echo json_encode(['status' => 'error', 'message' => 'Gagal Insert: ' . mysqli_error($konek)]); }
        }
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}

elseif ($action == 'simpan_master_seksi') {
    try {
        $nama_seksi = mysqli_real_escape_string($konek, trim($_POST['nama_seksi']));
        $cek = mysqli_query($konek, "SELECT * FROM qa_seksi_master WHERE nama_seksi = '$nama_seksi'");
        if (mysqli_num_rows($cek) > 0) { 
            echo json_encode(['status' => 'error', 'message' => 'Seksi ini sudah ada di Database!']); 
        } else { 
            if(mysqli_query($konek, "INSERT INTO qa_seksi_master (nama_seksi) VALUES ('$nama_seksi')")) {
                echo json_encode(['status' => 'success']); 
            } else { echo json_encode(['status' => 'error', 'message' => 'Gagal Insert: ' . mysqli_error($konek)]); }
        }
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}

elseif ($action == 'hapus_master_kls') {
    try {
        $nama_kelas = mysqli_real_escape_string($konek, trim($_POST['nama_kelas']));
        mysqli_query($konek, "DELETE FROM qa_kls WHERE nama_kelas = '$nama_kelas'");
        echo json_encode(['status' => 'success']);
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
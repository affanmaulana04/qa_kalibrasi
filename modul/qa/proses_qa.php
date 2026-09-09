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
            
            $q_hist = mysqli_query($konek, "SELECT * FROM qa_pengecekan WHERE no_part = '$no_part' ORDER BY tanggal_pengecekan DESC, id DESC LIMIT 5");
            if ($q_hist) {
                while ($row = mysqli_fetch_assoc($q_hist)) {
                    $history[] = [
                        'tanggal'  => $row['tanggal_pengecekan'],
                        'status'   => $row['status_kalibrasi'],
                        'seksi_qa' => $row['seksi_qa']
                    ];
                }
            }

            echo json_encode([
                'found'   => true,
                'data'    => $data_alat,
                'history' => $history
            ]);
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
        $ukuran    = mysqli_real_escape_string($konek, $_POST['ukuran']);
        $resolusi  = mysqli_real_escape_string($konek, $_POST['resolusi']);
        $merk      = mysqli_real_escape_string($konek, $_POST['merk']);
        $lokasi    = mysqli_real_escape_string($konek, $_POST['lokasi']);
        $no_seri   = mysqli_real_escape_string($konek, $_POST['no_seri']);
        $type      = mysqli_real_escape_string($konek, $_POST['type']);
        $no_fa     = mysqli_real_escape_string($konek, $_POST['no_fa']);
        $tgl_manual = isset($_POST['tgl_manual']) ? mysqli_real_escape_string($konek, $_POST['tgl_manual']) : '';

        $tgl_start = !empty($tgl_manual) ? $tgl_manual : date('Y-m-d');
        $jadwal_kalibrasi = date('Y-m-d', strtotime($tgl_start . " +$periode days"));
        $created_at       = date('Y-m-d H:i:s');

        $nama_file_foto = '';
        if(isset($_FILES['foto_alat']) && $_FILES['foto_alat']['error'] == 0){
            if ($_FILES['foto_alat']['size'] > 10485760) { 
                echo json_encode(['status' => 'error', 'message' => 'Gagal! Ukuran gambar maksimal 10MB.']);
                exit;
            }

            $folder_tujuan = "../../images/alat/";
            if (!is_dir($folder_tujuan)) { mkdir($folder_tujuan, 0777, true); }
            
            $ext = pathinfo($_FILES['foto_alat']['name'], PATHINFO_EXTENSION);
            $nama_file_foto = "ALAT_" . preg_replace('/[^A-Za-z0-9]/', '', $no_part) . "_" . time() . "." . $ext;
            
            move_uploaded_file($_FILES['foto_alat']['tmp_name'], $folder_tujuan . $nama_file_foto);
        }

        $sql = "INSERT INTO qa_part 
                (no_part, nama_part, seksi_pemilik, periode_kalibrasi, jadwal_kalibrasi, status, created_at, ukuran, resolusi, merk, lokasi, no_seri, type, no_fa, kls, foto_alat) 
                VALUES 
                ('$no_part', '$nama_part', '$seksi', '$periode', '$jadwal_kalibrasi', 'aktif', '$created_at', '$ukuran', '$resolusi', '$merk', '$lokasi', '$no_seri', '$type', '$no_fa', '$kls', '$nama_file_foto')";
        
        if (mysqli_query($konek, $sql)) {
            $alat_id = mysqli_insert_id($konek); 
            
            $nomor_pengecekan = "QA-REG-" . date('YmdHis') . "-" . $alat_id;
            $created_by = isset($_SESSION['userid']) ? mysqli_real_escape_string($konek, $_SESSION['userid']) : 'SISTEM';
            
            $sql_hist = "INSERT INTO qa_pengecekan 
                         (alat_id, no_part, nomor_pengecekan, tanggal_pengecekan, tanggal_jadwal_saat_pengecekan, status_kalibrasi, seksi_qa, periode_berikutnya, created_by, keterangan, created_at, updated_at) 
                         VALUES 
                         ('$alat_id', '$no_part', '$nomor_pengecekan', '$tgl_start', '$tgl_start', 'Good', 'QA', '$jadwal_kalibrasi', '$created_by', 'Registrasi Alat Awal', '$created_at', '$created_at')";
            mysqli_query($konek, $sql_hist);

            echo json_encode(['status' => 'success', 'jadwal_selanjutnya' => $jadwal_kalibrasi]);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($konek)]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

elseif ($action == 'simpan_qa') {
    try {
        $no_part    = mysqli_real_escape_string($konek, $_POST['no_part']);
        $status_qa  = mysqli_real_escape_string($konek, $_POST['status_qa']); 
        $seksi_qa   = mysqli_real_escape_string($konek, $_POST['seksi_qa']);
        $created_by = isset($_SESSION['userid']) ? mysqli_real_escape_string($konek, $_SESSION['userid']) : '';
        
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
                     (alat_id, no_part, nomor_pengecekan, tanggal_pengecekan, tanggal_jadwal_saat_pengecekan, status_kalibrasi, seksi_qa, periode_berikutnya, created_by, keterangan, created_at, updated_at) 
                     VALUES 
                     ('$alat_id', '$no_part', '$nomor_pengecekan', '$tgl_pengecekan', '$tgl_jadwal_lama', '$status_qa', '$seksi_qa', '$jadwal_baru', '$created_by', '', '$waktu_sekarang', '$waktu_sekarang')";
        
        $insert_hist = mysqli_query($konek, $sql_hist);

        if($insert_hist) {
            mysqli_query($konek, "UPDATE qa_part SET jadwal_kalibrasi = '$jadwal_baru', updated_at = NOW() WHERE no_part = '$no_part'");
            echo json_encode(['status' => 'success', 'jadwal_selanjutnya' => $jadwal_baru, 'no_pengecekan' => $nomor_pengecekan]);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($konek)]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

elseif ($action == 'simpan_master_kls') {
    try {
        $nama_kelas = mysqli_real_escape_string($konek, trim($_POST['nama_kelas']));
        $cek = mysqli_query($konek, "SELECT * FROM qa_kls WHERE nama_kelas = '$nama_kelas'");
        if (mysqli_num_rows($cek) > 0) { echo json_encode(['status' => 'error', 'message' => 'Nama Kelas ini sudah ada di Database!']); } 
        else { mysqli_query($konek, "INSERT INTO qa_kls (nama_kelas) VALUES ('$nama_kelas')"); echo json_encode(['status' => 'success']); }
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}

elseif ($action == 'simpan_master_seksi') {
    try {
        $nama_seksi = mysqli_real_escape_string($konek, trim($_POST['nama_seksi']));
        $cek = mysqli_query($konek, "SELECT * FROM qa_seksi_master WHERE nama_seksi = '$nama_seksi'");
        if (mysqli_num_rows($cek) > 0) { echo json_encode(['status' => 'error', 'message' => 'Seksi ini sudah ada di Database!']); } 
        else { mysqli_query($konek, "INSERT INTO qa_seksi_master (nama_seksi) VALUES ('$nama_seksi')"); echo json_encode(['status' => 'success']); }
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
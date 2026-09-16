<?php
  session_start();
  // Cek Login
  if($_SESSION['login'] == 0){
    header('location: ../../logout.php');
    exit;
  }
  if($_SESSION['tabel_karyawan'] !== 'Y'){
    echo "Anda tidak punya akses!";
    exit;
  }
  include "../../inc/inc_koneksi.php";  
  include "../../inc/fungsi_tanggal.php";
  $prmfile = $_GET['xprn']; 
  $query = mysqli_query($konek, "SELECT * FROM data_karyawan WHERE prn = '$prmfile'");
  $data = mysqli_fetch_assoc($query);
  if (!$data) {
      echo "Data tidak ditemukan! ID: " . $prmfile;
      exit;
  }
  // Hitung Umur
 if (!empty($data['tgl_lahir']) && $data['tgl_lahir'] != '0000-00-00') {
    $lahir = date_create($data['tgl_lahir']);

    if ($lahir !== false) {
        $sekarang = date_create('today');
        $diff = date_diff($lahir, $sekarang);
        $umur = $diff->y;
    } else {
        $umur = "-";
    }
} else {
    $umur = "-";
}
  // Tentukan path folder foto
  $folder_foto = "../../images/karyawan/"; 
  $nama_file = $data['prn'] . ".jpg";
  $path_lengkap = $folder_foto . $nama_file;

  if (file_exists($path_lengkap) && !empty($data['prn'])) {
      $tampil_foto = $path_lengkap;
  } else {
      // Foto kalau profil kosong
      $tampil_foto = "../../images/default_avatar.jpg"; 
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Detail Karyawan - <?php echo $data['nama']; ?></title>
    <style>
        /* CSS biar fleksibel di HP */
        @media (max-width: 576px) {
            .table-detail {
                font-size: 12px; /* Font mengecil di HP */
            }
            .card-header { font-size: 14px; }
        }
        .table-detail th {   
            background-color: #f8f9fa;
            width: 20%;
        }
        .text-break-all {
            word-break: break-all; /* teks panjang (Seksi) buat turun ke bawah */
        }
        @media print {
            body { background-color: #ffffff !important; } /* Hilangkan background abu-abu body */
            .card {border: none !important; }/* Hilangkan shadow card biar bersih */
            @page { margin: 1cm; }/* Atur margin kertas */
        }
    </style>
</head>
<body class="bg-light">
<div class="container mt-3 mb-5">
    <div class="card shadow-sm mx-auto" style="max-width: 850px;">
        <div class="card-header bg-primary text-white fw-bold">
            Detail Profil Karyawan
        </div>
        
        <div class="card-body p-3"> <div class="row">
                <div class="col-md-3 text-center mb-3 mb-md-0">
                    <img src="<?php echo $tampil_foto; ?>" 
                         class="img-thumbnail shadow-sm" 
                         style="width: 100%; max-width: 180px; height: 220px; object-fit: cover;" 
                         alt="Foto Karyawan">
                </div>

                <div class="col-md-9">
                    <div class="table-responsive">
                        <table class="table table-detail mb-0">
                            <tbody>
                                <tr>
                                    <th style="width: 20%;">NIK</th>
                                    <td>: <?php echo $data['prn']; ?></td>
                                </tr>
                                <tr>
                                    <th>Nama</th>
                                    <td class="fw-bold">: <?php echo $data['nama']; ?></td>
                                </tr>
                                <tr>
                                    <th>Jabatan</th>
                                    <td class="fw-bold">: <?php echo $data['jabatan']; ?></td>
                                </tr>
                                <tr>
                                    <th>Seksi</th>
                                    <td class="text-break-all">: <?php echo $data['seksi']; ?></td>
                                </tr>
                                <tr>
                                    <th>Subseksi</th>
                                    <td class="text-break-all">: <?php echo $data['subseksi']; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-detail mb-0">
                    <tbody>
                        <tr>
                            <th>Tgl Lahir</th>
                            <td>: <?php echo $data['tgl_lahir']; ?></td>
                            <th>Tgl Masuk</th>
                            <td>: <?php echo $data['tgl_masuk']; ?></td>
                        </tr>
                        <tr>
                            <th>Umur</th>
                            <td>: <?php echo $umur; ?> Tahun</td>
                            <th>Tgl Pensiun</th>
                            <td>: <?php echo $data['tgl_pensiun']; ?></td>
                        </tr>
                        <tr>
                            <th>No. KTP</th>
                            <td>: <?php echo $data['no_ktp']; ?></td>
                            <th>Pendidikan Terakhir</th>
                            <td>: <?php echo $data['pendidikan_terakhir_kerja']; ?></td>
                        </tr>
                        <tr>
                            <th>No. Telp</th>
                            <td>: <?php echo $data['no_hp']; ?></td>
                            <th>Email</th>
                            <td>: <?php echo $data['email']; ?></td>
                        </tr>
                    </tbody>
                </table>
                <table class="table table-detail mb-0 border-top-0">
                    <tbody>
                        <tr>
                            <th style="width: 20%;">Alamat</th>
                            <td>: <?php echo $data['alamat_tinggal']; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card-footer bg-white d-flex gap-2">
            <button onclick="window.close()" class="btn btn-secondary flex-grow-1 d-print-none">Tutup</button>
            <button onclick="window.print()" class="btn btn-danger flex-grow-1 d-print-none">Ekspor ke PDF</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
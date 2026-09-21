<?php
session_start();
include "../../inc/inc_koneksi.php";

$no_pengecekan = isset($_GET['no_pengecekan']) ? $_GET['no_pengecekan'] : '';

if(empty($no_pengecekan)) {
    die("Nomor pengecekan tidak ditemukan!");
}

$stmt = $konek->prepare("
    SELECT p.*, q.no_part, q.nama_part, q.ukuran, q.merk, q.resolusi, q.lokasi, q.no_seri, q.type
    FROM qa_pengecekan p
    LEFT JOIN qa_part q ON p.alat_id = q.id
    WHERE p.nomor_pengecekan = ?
    LIMIT 1");
$stmt->bind_param('s', $no_pengecekan);
$stmt->execute();
$query = $stmt->get_result();

$data = mysqli_fetch_assoc($query);
if(!$data) die("Data kalibrasi tidak ditemukan!");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cetak Slip QA - <?php echo $data['no_part']; ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: #333; background: #f4f6f9; margin: 0; padding: 20px; }
        .slip-container { 
            background: #fff; width: 100%; max-width: 450px; border: 2px dashed #333; 
            padding: 20px; margin: 20px auto; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px; }
        .header h3 { margin: 0; font-size: 20px; font-weight: bold; text-transform: uppercase; }
        .header p { margin: 5px 0 0 0; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 6px 4px; vertical-align: top; }
        .title-td { width: 40%; font-weight: bold; }
        
        .action-buttons { text-align: center; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; cursor: pointer; border: none; font-size: 14px; }
        .btn-back { background: #6c757d; color: #fff; margin-right: 10px; }
        .btn-back:hover { background: #5a6268; }
        .btn-print { background: #1E3F66; color: #fff; }
        .btn-print:hover { background: #0d1e33; }
        
        @media print { 
            @page { margin: 0; size: auto; }
            body { padding: 15mm; background: #fff; }
            .no-print { display: none !important; } 
            .slip-container { border: 1px solid #000; margin: 0; border-radius: 0; box-shadow: none;}
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print action-buttons">
        <a href="cek_alat.php" class="btn btn-back">&larr; Kembali ke Cek Alat</a>
        <button onclick="window.print()" class="btn btn-print">Cetak Slip Sekarang</button>
    </div>

    <div class="slip-container">
        <div class="header">
            <h3>SLIP KALIBRASI ALAT</h3>
            <p>PT. SURYA TOTO INDONESIA</p>
        </div>
        <table>
            <tr><td class="title-td">No. Pengecekan</td><td>: <strong><?php echo $data['nomor_pengecekan']; ?></strong></td></tr>
            <tr><td class="title-td">No. Alat</td><td>: <?php echo $data['no_part']; ?></td></tr>
            <tr><td class="title-td">Nama Alat</td><td>: <?php echo $data['nama_part']; ?></td></tr>
            <tr><td class="title-td">Lokasi</td><td>: <?php echo ($data['lokasi'] ? $data['lokasi'] : '-'); ?></td></tr>
            <tr><td colspan="2"><hr style="border-top:1px dashed #333;"></td></tr>
            <tr><td class="title-td">Tgl. Aktual QA</td><td>: <?php echo date('d-m-Y', strtotime($data['tanggal_pengecekan'])); ?></td></tr>
            <tr><td class="title-td">Status QA</td><td>: <strong><?php echo $data['status_kalibrasi']; ?></strong></td></tr>
            <tr><td class="title-td">Seksi QA</td><td>: <?php echo $data['seksi_qa']; ?></td></tr>
            <tr><td colspan="2"><hr style="border-top:1px dashed #333;"></td></tr>
            <tr><td class="title-td">Jadwal Berikutnya</td><td>: <span style="font-size: 16px; font-weight:bold;"><?php echo date('d-m-Y', strtotime($data['periode_berikutnya'])); ?></span></td></tr>
            <tr><td class="title-td">PIC / Checker</td><td>: <?php echo $data['created_by']; ?></td></tr>
        </table>
    </div>
</body>
</html>
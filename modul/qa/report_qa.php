<<<<<<< HEAD
﻿<!DOCTYPE html>
<html>
<head>
    <title>Laporan Kalibrasi Alat</title>
    <link rel="icon" type="image/png" href="../../images/LogoTOTO.png?v=<?= time(); ?>"/>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #000; margin: 0; padding: 20px; background: #e0e0e0;}
        .a4-page { width: 210mm; min-height: 297mm; background: #fff; margin: 0 auto; padding: 15mm; box-sizing: border-box; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .table-excel { width: 100%; border-collapse: collapse; border: 1px solid #000; }
        .table-excel th, .table-excel td { border: 1px solid #000; padding: 6px; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .header-title { font-size: 24px; font-weight: normal; text-align: center; margin: 15px 0 20px 0; letter-spacing: 1px;}
        .pt-title { font-weight: bold; text-align: center; font-size: 14px; margin-top:-5px;}
        .diagonal-strike { position: relative; overflow: hidden; }
        .diagonal-strike::after { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(to top right, transparent 49%, #000 50%, transparent 51%); }
        .btn-action { display: block; width: 200px; padding: 12px; margin: 20px auto; background: #1E3F66; color: white; text-align: center; text-decoration: none; font-weight: bold; border-radius: 5px; cursor: pointer;}
        @media print { body { background: #fff; padding: 0; margin: 0; } .a4-page { box-shadow: none; margin: 0; padding: 10mm; } .no-print { display: none; } }
    </style>
=======
<?php
session_start();
if (empty($_SESSION['login'])) {
    header('location: ../../logout.php');
    exit();
}

$id_pemakai = $_SESSION['userid'] ?? '';
include '../../inc/inc_koneksi.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatTanggalTitik($value) {
    if (!$value) return '-';
    $time = strtotime($value);
    return $time ? date('d.m.Y', $time) : '-';
}

// LOGIKA 3 WARNA SAJA: Terlewat (Merah), Hari Ini (Kuning), Terjadwal (Hijau)
function statusJadwal($jadwal) {
    if (!$jadwal) return ['label' => 'Terjadwal', 'class' => 'status-scheduled'];
    
<<<<<<< HEAD
    if ($selisih < 0) return ['label' => 'Jadwal Terlewat', 'class' => 'status-overdue', 'color' => '#842029'];
    if ($selisih === 0) return ['label' => 'Jadwal Hari Ini', 'class' => 'status-today', 'color' => '#155724'];
    return ['label' => 'Terjadwal', 'class' => 'status-scheduled', 'color' => '#084298'];
}

$sql = "SELECT p.id, p.no_part AS nomor_alat, p.nama_part AS nama_alat,
               COALESCE(sp.kode_pemilik, '-') AS kode_pemilik,
               p.jadwal_kalibrasi
        FROM qa_part p
        LEFT JOIN qa_seksi_pemilik sp ON sp.id = p.seksi_pemilik_id
        WHERE p.status = 'aktif'
        ORDER BY COALESCE(sp.kode_pemilik, '') ASC, p.jadwal_kalibrasi ASC, p.no_part ASC";
=======
    $tgl_sekarang = date('Y-m-d');
    $selisih = (int)((strtotime($jadwal) - strtotime($tgl_sekarang)) / 86400);
    
    if ($selisih < 0) {
        return ['label' => 'Jadwal Terlewat', 'class' => 'status-overdue'];
    } elseif ($selisih === 0) {
        return ['label' => 'Jadwal Hari Ini', 'class' => 'status-today'];
    } else {
        return ['label' => 'Terjadwal', 'class' => 'status-scheduled'];
    }
}

// QUERY BARU: Langsung ambil teks seksi_pemilik, nggak perlu JOIN tabel HRD lama
$sql = "SELECT id, no_part AS nomor_alat, nama_part AS nama_alat,
               seksi_pemilik AS kode_pemilik,
               jadwal_kalibrasi
        FROM qa_part
        WHERE status = 'aktif'
        ORDER BY seksi_pemilik ASC, jadwal_kalibrasi ASC, no_part ASC";
>>>>>>> 0a5ed7ff6ba01531b8284390619185fbc099bef4

$result = $konek->query($sql);
$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) $rows[] = $row;
}

// Target 23 alat per lembar A4 landscape.
$perPage = 23;
$pages = $rows ? array_chunk($rows, $perPage) : [[]];
$totalPages = count($pages);
$todayLabel = date('d.m.Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar Kalibrasi Alat</title>
<link rel="shortcut icon" href="../../images/hrd.ico">
<link href="../../css/bootstrap.min.css" rel="stylesheet">
<link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet">
<style>
body{padding-top:80px;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;color:#222}
.card-report{background:#fff;border-radius:10px;box-shadow:0 4px 15px rgba(0,0,0,.05);padding:25px;margin-top:20px}
.screen-title{font-weight:700;margin:0 0 20px}
.screen-table th{text-align:center;background:#f3f4f6}.screen-table td{vertical-align:middle!important}
.print-area{display:none}

<<<<<<< HEAD
.status-scheduled{background:#b9d9f4!important; color:#084298!important; font-weight:bold;}
.status-today{background:#c9e8c4!important; color:#155724!important; font-weight:bold;}
.status-overdue{background:#f7b9bd!important; color:#842029!important; font-weight:bold;}
=======
/* WARNA UNTUK LAYAR MONITOR (3 WARNA) */
.status-scheduled{background:#d1e7dd!important; color:#0f5132!important; font-weight:bold;} /* Hijau */
.status-today{background:#fff3cd!important; color:#664d03!important; font-weight:bold;} /* Kuning */
.status-overdue{background:#f8d7da!important; color:#842029!important; font-weight:bold;} /* Merah */
>>>>>>> 0a5ed7ff6ba01531b8284390619185fbc099bef4

@media print{
    @page{size:A4 landscape;margin:8mm}
    *{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}
    html,body{margin:0!important;padding:0!important;background:#fff!important}
    .no-print{display:none!important}
    .print-area{display:block!important}
    .print-page{width:100%;height:194mm;box-sizing:border-box;position:relative;page-break-after:always;break-after:page;overflow:hidden}
    .print-page:last-child{page-break-after:auto;break-after:auto}
    .print-top{
        position:relative;
        height:24mm;
    }

    .email-date{
        position:absolute;
        left:0;
        top:0;
        font-size:10pt;
        font-weight:700;
    }

    .report-title{
        text-align:center;
        font-size:19pt;
        font-weight:700;
        line-height:1.15;
        margin:0;
        padding-top:7mm;
    }

    .page-number{
        position:absolute;
        right:0;
        top:13mm;
        font-size:10pt;
        font-weight:700;
    }
    .print-table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:8.7pt}
    .print-table th,.print-table td{border:1px solid #6d7378;padding:2.4px 4px;vertical-align:middle}
    .print-table th{background:#e7eaed;text-align:center;font-weight:700;height:7mm}
    .print-table td:nth-child(1){width:5%;text-align:center}
    .print-table td:nth-child(2){width:17%}
    .print-table td:nth-child(3){width:15%}
    .print-table td:nth-child(4){width:27%}
    .print-table td:nth-child(5){width:17%;text-align:center}
    .print-table td:nth-child(6){width:19%;text-align:center;font-weight:700}
    .section-row td{font-weight:700;padding:2.2px 7px;background:#e9ecef!important}
    
    /* WARNA UNTUK KERTAS PRINT (3 WARNA) */
    .status-scheduled{background:#d1e7dd!important; color:#000!important;}
    .status-today{background:#fff3cd!important; color:#000!important;}
    .status-overdue{background:#f8d7da!important; color:#000!important;}
    
    .print-footer{position:absolute;left:0;right:0;bottom:0;height:32mm;display:flex;gap:4mm;box-sizing:border-box}
    .legend-box,.note-box{border:1px solid #6d7378;box-sizing:border-box;padding:3mm}
    .legend-box{width:30%}.note-box{width:70%}
    .footer-title{font-size:10.5pt;font-weight:700;margin-bottom:2mm}
    .legend-item{display:flex;align-items:center;font-size:8.2pt;line-height:1.25;margin-bottom:1.1mm}
    
    /* KOTAK KECIL LEGENDA BAWAH */
    .legend-color{display:inline-block;width:6mm;height:4mm;border:1px solid #777;margin-right:2mm;flex:none}
    
    .note-line{border-bottom:1px dotted #555;height:5mm;margin-top:0.5mm}
}
</style>
>>>>>>> origin/update-form-qa
</head>
<body>

    <div class="no-print text-center">
        <a href="cek_alat.php" style="color:#1E3F66; font-weight:bold; text-decoration:none;">&larr; Kembali ke Pengecekan</a>
        <button class="btn-action" onclick="window.print()">Cetak Laporan</button>
    </div>

    <!-- AREA KERTAS -->
    <div class="a4-page" id="area-kertas">
        <!-- Akan di render otomatis lewat Javascript berdasarkan Format nya -->
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var dataStr = sessionStorage.getItem('cetak_data');
            if(!dataStr) { alert("Data tidak ditemukan! Silakan update kalibrasi melalui halaman Cek Alat terlebih dahulu."); return; }
            var data = JSON.parse(dataStr);

            const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
            let d = new Date(data.tanggal_periksa);
            let dStr = d.getDate().toString().padStart(2, '0') + ' - ' + (d.getMonth()+1).toString().padStart(2, '0') + ' - ' + d.getFullYear();
            let dSign = d.getDate() + ' - ' + (d.getMonth()+1).toString().padStart(2, '0') + ' - ' + d.getFullYear(); // Khusus R pakai angka
            
            function formatTanggalReversed(tglStr) {
                var p = tglStr.split('-');
                if(p.length !== 3) return tglStr;
                return p[2] + ' - ' + p[1] + ' - ' + p[0];
            }
            let dbStr = data.jadwal_berikutnya ? formatTanggalReversed(data.jadwal_berikutnya) : '-';

            // HTML RENDERER
            var html = '';
            
            // ==============================================================================
            // FORMAT R (LAYOUT KHUSUS SESUAI GAMBAR)
            // ==============================================================================
            if(data.format === 'R') {
                html += `
                <div class="pt-title">PT. SURYA TOTO INDONESIA Tbk.</div>
                <table class="table-excel">
                    <tr><td colspan="4" style="border-bottom: 1px solid #000;"><div class="header-title">LAPORAN KALIBRASI</div></td></tr>
                    
                    <tr>
                        <td width="20%">NO. LAPORAN</td><td width="30%">-</td>
                        <td width="20%">SEKSI</td><td width="30%" class="text-bold text-center">${data.seksi_pemilik}</td>
                    </tr>
                    <tr>
                        <td>NAMA ALAT</td><td>${data.nama_part}</td>
                        <td>PETUGAS KALIBRASI</td><td class="text-center">${data.petugas}</td>
                    </tr>
                    <tr>
                        <td>NOMOR. ALAT</td><td>${data.no_part}</td>
                        <td>TANGGAL KALIBRASI</td><td class="text-center">${dStr}</td>
                    </tr>
                    <tr>
                        <td>SPESIFIKASI</td><td>${data.spesifikasi}</td>
                        <td style="border-bottom:none;"></td><td style="border-bottom:none;"></td>
                    </tr>
                    <tr>
                        <td>RESOLUSI</td><td>${data.resolusi || '-'}</td>
                        <td style="border-top:none;"></td><td style="border-top:none;"></td>
                    </tr>

<<<<<<< HEAD
                    <tr><td colspan="4" class="text-center" style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 5px 0;">HASIL KALIBRASI</td></tr>
                    <tr>
                        <td colspan="4" style="padding:0; border:none;">
                            <table style="width:100%; border-collapse: collapse; text-align:center;">
                                <tr>
                                    <td rowspan="2" width="20%" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">POSISI</td>
                                    <td rowspan="2" width="20%" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">STANDAR</td>
                                    <td colspan="3" style="border-bottom: 1px solid #000;">HASIL</td>
                                </tr>
                                <tr>
                                    <td width="20%" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">DEPAN</td>
                                    <td width="20%" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">TENGAH</td>
                                    <td width="20%" style="border-bottom: 1px solid #000;">BELAKANG</td>
                                </tr>
                                <tr><td colspan="5" style="height:40px; border-bottom:1px solid #000;"></td></tr>
                `;
=======
<div class="print-area">
<?php foreach ($pages as $pageIndex => $pageRows): ?>
    <section class="print-page">
        <div class="print-top">
            <div class="email-date">
                Tanggal: <?php echo $todayLabel; ?>
            </div>

            <div class="report-title">
                DAFTAR KALIBRASI ALAT
            </div>

            <div class="page-number">
                Halaman <?php echo ($pageIndex + 1) . '/' . $totalPages; ?>
            </div>
        </div>
>>>>>>> origin/update-form-qa

                // Looping Data R
                data.hasil_array.forEach(function(row) {
                    var std = parseFloat(row.standar);
                    var tol = parseFloat(row.toleransi);
                    var minRange = (std - tol).toFixed(3);
                    var maxRange = (std + tol).toFixed(3);

                    html += `
                                <tr>
                                    <td style="border-right: 1px solid #000;">${row.bagian || '-'}</td>
                                    <td style="border-right: 1px solid #000; line-height:1.6; padding: 10px 0;">
                                        <span style="font-size:14px;">${std}</span> <span style="margin-left:10px;">Â±${tol}</span><br>
                                        ${minRange} ~ ${maxRange}
                                    </td>
                                    <td style="border-right: 1px solid #000;">${!isNaN(row.depan) ? row.depan : '-'}</td>
                                    <td style="border-right: 1px solid #000;">${!isNaN(row.tengah) ? row.tengah : '-'}</td>
                                    <td>${!isNaN(row.belakang) ? row.belakang : '-'}</td>
                                </tr>
                    `;
                });

                // Footer Bawah Format R
                let crossKelembapan = !data.kelembapan ? 'diagonal-strike' : '';
                html += `
                                <tr><td colspan="5" style="height:30px;"></td></tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-top: 1px solid #000;">METODE</td><td style="border-top: 1px solid #000;">${data.metode}</td>
                        <td style="border-top: 1px solid #000;">KELEMBABAN RUANGAN</td><td style="border-top: 1px solid #000;" class="text-center ${crossKelembapan}">${data.kelembapan ? data.kelembapan+' %' : ''}</td>
                    </tr>
                    <tr>
                        <td>STANDAR UJI</td><td>${data.standar_uji.replace(/\n/g, "<br>")}</td>
                        <td rowspan="2" style="vertical-align:middle;">KEPUTUSAN</td><td rowspan="2" class="text-center text-bold" style="font-size: 16px; vertical-align:middle;">${data.status_final}</td>
                    </tr>
                    <tr>
                        <td>TRACEABILITY</td><td>${data.traceability}</td>
                    </tr>
                    <tr>
                        <td>SUHU RUANGAN</td><td class="text-center ${!data.suhu ? 'diagonal-strike' : ''}">${data.suhu ? data.suhu+' Â°C' : ''}</td>
                        <td>TGL KALIBRASI BERIKUTNYA</td><td class="text-center">${dbStr}</td>
                    </tr>
                    <tr>
                        <td colspan="4" style="height: 60px; vertical-align:top; border-top: 1px solid #000; border-bottom: 1px solid #000;">
                            KETERANGAN : <br>
                            <span style="white-space: pre-wrap; font-style:italic;">${data.keterangan || '-'}</span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" style="border:none; padding-top: 20px;">
                            <div style="width: 60%; float: right; text-align: center;">
                                <div style="text-align:left; margin-bottom: 5px; font-weight:bold;">TANGERANG , ${dSign}</div>
                                <table class="table-excel" style="width: 100%;">
                                    <tr><td width="33%">DIBUAT</td><td width="33%">DIPERIKSA</td><td width="33%">DISETUJUI</td></tr>
                                    <tr>
                                        <td style="height: 70px; border-bottom: none;"></td>
                                        <td style="height: 70px; border-bottom: none;"></td>
                                        <td style="height: 70px; border-bottom: none;"></td>
                                    </tr>
                                    <tr>
                                        <td style="border-top: none;">PETUGAS</td><td style="border-top: none;">FOREMAN</td><td style="border-top: none;">SUPERVISOR</td>
                                    </tr>
                                </table>
                            </div>
                            <div style="clear:both;"></div>
                        </td>
                    </tr>
                </table>
                <div style="text-align: right; font-size: 11px; margin-top: 5px; font-weight: bold;">STIS-CMFG4-03/R4</div>
                `;
            } 
            
            // ==============================================================================
            // FORMAT A DAN N (STANDAR)
            // ==============================================================================
            else {
                let crossSuhu = !data.suhu ? 'diagonal-strike' : '';
                let crossKelembapan = !data.kelembapan ? 'diagonal-strike' : '';
                let crossKetidakpastian = !data.ketidakpastian ? 'diagonal-strike' : '';
                let crossCL = !data.confidence_level ? 'diagonal-strike' : '';

                html += `
                <div class="pt-title">PT. SURYA TOTO INDONESIA Tbk.</div>
                <table class="table-excel">
                    <tr><td colspan="4" style="border-bottom: 1px solid #000;"><div class="header-title">LAPORAN PEMERIKSAAN</div></td></tr>
                    
                    <tr>
                        <td width="15%">NO. LAPORAN</td><td width="35%">-</td>
                        <td width="15%">SEKSI</td><td width="35%" class="text-bold text-center">${data.seksi_pemilik}</td>
                    </tr>
                    <tr>
                        <td>NAMA ALAT</td><td>${data.nama_part}</td>
                        <td rowspan="2">PETUGAS PERIKSA</td><td rowspan="2" class="text-center">${data.petugas}</td>
                    </tr>
                    <tr>
                        <td>NO. ALAT</td><td>${data.no_part}</td>
                    </tr>
                    <tr>
                        <td>SPESIFIKASI</td><td>${data.spesifikasi}</td>
                        <td>TANGGAL PERIKSA</td><td class="text-center">${dStr}</td>
                    </tr>
                    <tr>
                        <td>RESOLUSI</td><td colspan="3">${data.resolusi || '-'}</td>
                    </tr>

                    <tr><td colspan="4" class="text-center" style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 10px 0;">HASIL PEMERIKSAAN</td></tr>
                    <tr>
                        <td colspan="4" style="padding:0; border:none;">
                            <table style="width:100%; border-collapse: collapse; text-align:center;">
                `;

                var hasBagian = data.hasil_array.some(r => r.bagian && r.bagian.trim() !== '' && r.bagian.trim() !== '-');
                var hasToleransi = data.hasil_array.some(r => r.toleransi !== undefined && r.toleransi !== null && r.toleransi !== '' && r.toleransi !== 0 && r.toleransi !== '-');

                html += '<tr>';
                html += '<td width="5%" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">NO</td>';
                if(hasBagian) { html += '<td width="15%" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">BAGIAN</td>'; }
                html += '<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">STANDAR<br>( ' + data.satuan + ' )</td>';
                if(hasToleransi) { html += '<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">TOLERANSI (Â±)</td>'; }
                html += '<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">HASIL<br>( ' + data.satuan + ' )</td>';
                html += '<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">KOREKSI<br>( ' + data.satuan + ' )</td>';
                html += '<td width="12%" style="border-bottom: 1px solid #000;">STATUS</td>';
                html += '</tr>';

                data.hasil_array.forEach(function(row, idx) {
                    html += '<tr>';
                    html += '<td style="border-right: 1px solid #000;">' + (idx+1) + '</td>';
                    if(hasBagian) { html += '<td style="border-right: 1px solid #000;">' + (row.bagian || '-') + '</td>'; }
                    html += '<td style="border-right: 1px solid #000;">' + row.standar + '</td>';
                    if(hasToleransi) { html += '<td style="border-right: 1px solid #000;">Â± ' + (row.toleransi || '-') + '</td>'; }
                    html += '<td style="border-right: 1px solid #000;">' + row.hasil + '</td>';
                    html += '<td style="border-right: 1px solid #000;">' + row.koreksi + '</td>';
                    html += '<td style="font-weight:bold;">' + (row.status || '-') + '</td>';
                    html += '</tr>';
                });

                html += `
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-top: 1px solid #000;">METODE</td><td style="border-top: 1px solid #000;">${data.metode}</td>
                        <td style="border-top: 1px solid #000;">KELEMBABAN RUANGAN</td><td style="border-top: 1px solid #000;" class="text-center ${crossKelembapan}">${data.kelembapan ? data.kelembapan+' %' : ''}</td>
                    </tr>
                    <tr>
                        <td>STANDAR UJI</td><td>${data.standar_uji.replace(/\n/g, "<br>")}</td>
                        <td>STANDAR TOLERANSI</td><td class="text-center">${data.toleransi_global}</td>
                    </tr>
                    <tr>
                        <td>CONFIDENCE LEVEL</td><td class="${crossCL}">${data.confidence_level ? data.confidence_level+' %' : ''}</td>
                        <td>KETIDAKPASTIAN</td><td class="text-center ${crossKetidakpastian}">${data.ketidakpastian ? 'Â± '+data.ketidakpastian : ''}</td>
                    </tr>
                    <tr>
                        <td>TRACEABILITY</td><td>${data.traceability}</td>
                        <td>KEPUTUSAN</td><td class="text-center text-bold" style="font-size: 16px;">${data.status_final}</td>
                    </tr>
                    <tr>
                        <td>SUHU RUANGAN</td><td class="text-center ${crossSuhu}">${data.suhu ? data.suhu+' Â°C' : ''}</td>
                        <td>PERIKSA BERIKUTNYA</td><td class="text-center">${dbStr}</td>
                    </tr>
                    <tr>
                        <td colspan="4" style="height: 60px; vertical-align:top; border-top: 1px solid #000; border-bottom: 1px solid #000;">
                            KETERANGAN : <br>
                            <span style="white-space: pre-wrap; font-style:italic;">${data.keterangan || '-'}</span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" style="border:none; padding-top: 20px;">
                            <div style="width: 50%; float: right; text-align: center;">
                                <div style="text-align:left; margin-bottom: 5px;">TANGERANG , ${dSign}</div>
                                <table class="table-excel" style="width: 100%;">
                                    <tr><td width="33%">DIBUAT</td><td width="33%">DIPERIKSA</td><td width="33%">DISETUJUI</td></tr>
                                    <tr>
                                        <td style="height: 70px; border-bottom: none;"></td>
                                        <td style="height: 70px; border-bottom: none;"></td>
                                        <td style="height: 70px; border-bottom: none;"></td>
                                    </tr>
                                    <tr><td style="border-top: none;">PETUGAS</td><td style="border-top: none;">FOREMAN</td><td style="border-top: none;">SUPERVISOR</td></tr>
                                </table>
                            </div>
                            <div style="clear:both;"></div>
                        </td>
                    </tr>
                </table>
                <div style="text-align: right; font-size: 11px; margin-top: 5px; font-weight: bold;">STIS-CMFG4-03/R4</div>
                `;
            }
            
            document.getElementById('area-kertas').innerHTML = html;
        });
    </script>
</body>
</html>

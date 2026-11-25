<?php
session_start();
if ($_SESSION['login'] == 0) {
    header('location: ../../../logout.php');
    exit();
}
require_once __DIR__ . '/../../../inc/inc_koneksi.php';
require_once __DIR__ . '/../../../inc/qa_rbac.php';
require_once __DIR__ . '/../../../inc/qa_seksi_akses.php';
require_once __DIR__ . '/../../../inc/qa_jadwal_validasi.php';
$validationPreview = ($_GET['validation_preview'] ?? '') === '1'
    && qaRbacCanAny($konek, ['pengajuan_jadwal', 'approval_pemeriksa', 'approval_penyetuju']);
if (!$validationPreview) qaRbacRequire($konek, 'jadwal_harian');

// Tombol Kembali mengikuti asal navigasi. URL langsung/menu kembali ke Media,
// sedangkan akses dari Master Data (atau ?from=masterdata) kembali ke Master Data.
$referrerPath = parse_url((string)($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH);
$fromMasterdata = strtolower(trim((string)($_GET['from'] ?? ''))) === 'masterdata'
    || (is_string($referrerPath) && preg_match('~/modul/qa/masterdata/masterdata\.php$~i', $referrerPath));
$backUrl = $fromMasterdata ? '../masterdata/masterdata.php' : '../../../media.php';
$backLabel = $fromMasterdata ? 'Kembali ke Master Data' : 'Kembali ke Menu Utama';

$filter_seksi = isset($_GET['seksi']) ? mysqli_real_escape_string($konek, $_GET['seksi']) : 'ALL';
$filter_tgl = isset($_GET['tgl']) ? mysqli_real_escape_string($konek, $_GET['tgl']) : date('Y-m-d');
$dateParts = explode('-', $filter_tgl);
if (count($dateParts) !== 3 || !ctype_digit(implode('', $dateParts)) ||
    !checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0]) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_tgl)) {
    $filter_tgl = date('Y-m-d');
}
$signatures = qaJadwalSignatures($konek, substr($filter_tgl, 0, 7));

$masters = [];
$jadwalRows = [];
foreach (['qa_part', 'qa_alat_ng', 'qa_alat_rusak', 'qa_alat_discontinue', 'qa_alat_ditarik'] as $table) {
    $where = qaSeksiSql($konek, 'seksi_pemilik');
    $query = @mysqli_query($konek, "SELECT no_part,nama_part,spesifikasi,resolusi,seksi_pemilik,periode_kalibrasi,jadwal_kalibrasi,status FROM `$table` WHERE $where");
    if (!$query)
        continue;
    while ($r = mysqli_fetch_assoc($query)) {
        $no = strtoupper(trim((string)$r['no_part']));
        if ($no === '')
            continue;
        if (!isset($masters[$no]) || $table === 'qa_part')
            $masters[$no] = $r;
        if ($table !== 'qa_part' || strtolower((string)$r['status']) !== 'aktif')
            continue;
        $due = substr((string)$r['jadwal_kalibrasi'], 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $due) || $due > $filter_tgl)
            continue;
        if ($filter_seksi !== 'ALL' && strcasecmp((string)$r['seksi_pemilik'], $filter_seksi) !== 0)
            continue;
        $r['tanggal_rencana'] = $due;
        $r['tanggal_aktual'] = '';
        $r['hasil_qa'] = '';
        $r['tanggal_berikutnya'] = '';
        $jadwalRows[$no . '|' . $due] = $r;
    }
}

// Jadwal master bergeser setelah approval. Snapshot transaksi menjaga rencana lama tetap tercetak.
$qFinal = mysqli_query($konek, "SELECT no_part,tanggal_jadwal_saat_pengecekan,tanggal_pengecekan,
    status_kalibrasi,periode_berikutnya,hasil_json
    FROM qa_pengecekan
    WHERE (tanggal_jadwal_saat_pengecekan <= '$filter_tgl'
      OR LEFT(tanggal_pengecekan,10) <= '$filter_tgl')
      AND status_validasi='final_disetujui'
      AND COALESCE(jenis_transaksi,'update_hasil_kalibrasi') <> 'registrasi_alat'
      AND (nomor_pengecekan IS NULL OR nomor_pengecekan NOT LIKE 'QA-REG-%')
    ORDER BY tanggal_pengecekan ASC,id ASC");
if ($qFinal) {
    while ($q = mysqli_fetch_assoc($qFinal)) {
        $no = strtoupper(trim((string)$q['no_part']));
        $due = substr((string)($q['tanggal_jadwal_saat_pengecekan'] ?: $q['tanggal_pengecekan']), 0, 10);
        if ($no === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due))
            continue;
        $snapshot = json_decode((string)($q['hasil_json'] ?? ''), true);
        $header = is_array($snapshot['header'] ?? null) ? $snapshot['header'] : [];
        $master = $masters[$no] ?? [];
        $seksi = trim((string)($header['seksi_pemilik'] ?? ($master['seksi_pemilik'] ?? '')));
        if (!qaSeksiBoleh($konek, $seksi))
            continue;
        if ($filter_seksi !== 'ALL' && strcasecmp($seksi, $filter_seksi) !== 0)
            continue;
        $key = $no . '|' . $due;
        $row = $jadwalRows[$key] ?? $master;
        $row['no_part'] = $q['no_part'];
        $row['nama_part'] = $header['nama_part'] ?? ($row['nama_part'] ?? $q['no_part']);
        $row['spesifikasi'] = $header['spesifikasi'] ?? ($row['spesifikasi'] ?? '');
        $row['resolusi'] = $header['resolusi'] ?? ($row['resolusi'] ?? '');
        $row['seksi_pemilik'] = $seksi;
        $row['periode_kalibrasi'] = $master['periode_kalibrasi'] ?? ($row['periode_kalibrasi'] ?? '');
        $row['tanggal_rencana'] = $due;
        $row['tanggal_aktual'] = substr((string)$q['tanggal_pengecekan'], 0, 10);
        $status = strtoupper(trim((string)$q['status_kalibrasi']));
        $row['hasil_qa'] = in_array($status, ['GOOD', 'OK'], true) ? 'OK'
            : (in_array($status, ['BAD', 'NG', 'NOT GOOD', 'BURUK'], true) ? 'NOT GOOD'
            : ($status === 'RUSAK VISUAL' ? 'RUSAK'
            : ($status === 'DITARIK' ? 'DITARIK TECHNICAL' : $status)));
        $row['tanggal_berikutnya'] = $row['hasil_qa'] === 'OK'
            ? substr((string)($q['periode_berikutnya'] ?? ''), 0, 10) : '-';
        $jadwalRows[$key] = $row;
    }
}

uasort($jadwalRows, static function ($a, $b) {
    return [strtoupper((string)$a['seksi_pemilik']), (string)$a['tanggal_rencana'], strtoupper((string)$a['no_part'])]
        <=> [strtoupper((string)$b['seksi_pemilik']), (string)$b['tanggal_rencana'], strtoupper((string)$b['no_part'])];
});
$data_per_seksi = [];
foreach ($jadwalRows as $r) {
    $seksi = $r['seksi_pemilik'];
    if (!isset($data_per_seksi[$seksi])) {
        $data_per_seksi[$seksi] = [];
    }
    $data_per_seksi[$seksi][] = $r;
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Cetak Jadwal Harian</title>
        <link rel="icon" type="image/png" href="../../../images/LogoTOTO.png?v=20261013">
        <style>
            * {
                box-sizing: border-box;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            body {
                font-family: Arial, sans-serif;
                font-size: 11px;
                color: #000;
                margin: 0;
                padding: 20px;
                background: #525659;
            }
            .no-print {
                text-align: center;
                margin-bottom: 20px;
            }
            .btn-action {
                display: inline-block;
                width: 200px;
                padding: 12px;
                background: #fff;
                color: #333;
                text-align: center;
                text-decoration: none;
                font-weight: bold;
                border-radius: 5px;
                cursor: pointer;
                border: none;
                margin-top: 10px;
            }
            .btn-action:hover {
                background: #ddd;
            }
            .a4-landscape {
                width: 297mm;
                min-height: 210mm;
                background: #fff;
                margin: 0 auto 20px auto;
                padding: 10mm;
                box-shadow: 0 4px 10px rgba(0,0,0,0.5);
                position: relative;
                page-break-after: always;
            }
            .a4-landscape:last-child {
                page-break-after: auto;
                margin-bottom: 0;
            }
            .table-excel {
                width: 100%;
                border-collapse: collapse;
                border: 2px solid #000;
            }
            .table-excel th, .table-excel td {
                border: 1px solid #000;
                padding: 4px;
                page-break-inside: avoid;
                text-align: center;
                vertical-align: middle;
            }
            .table-excel tbody tr, .table-excel tbody td {
                height: 20px;
            }
            .text-bold {
                font-weight: bold;
            }
            .text-left {
                text-align: left !important;
                padding-left: 5px !important;
            }
            .header-title {
                font-size: 20px;
                font-weight: bold;
                text-align: center;
                margin: 15px 0;
            }
            .pt-title {
                font-weight: bold;
                text-align: center;
                font-size: 13px;
                margin-top: -5px;
            }
            .sign-box {
                border: 1px solid #000;
                width: 300px;
                text-align: center;
                float: right;
                margin-bottom: 10px;
            }
            .sign-box th {
                border: 1px solid #000;
                padding: 2px;
            }
            .sign-box td {
                border: 1px solid #000;
                height: 50px;
            }
            .sign-date {
                display: block;
                font-size: 9px;
                margin-top: 3px;
            }
            @media print {
                @page {
                    size: A4 landscape;
                    margin: 0;
                }
                body {
                    background: #fff;
                    padding: 0;
                    margin: 0;
                }
                .a4-landscape {
                    box-shadow: none;
                    margin: 0;
                    padding: 10mm;
                    border: none;
                }
                .no-print {
                    display: none !important;
                }
                /* Sel kosong dan sel berisi memakai tinggi baris dan line-height yang sama. */
                .table-excel tbody tr, .table-excel tbody td {
                    height: 4mm;
                }
                .table-excel tbody td {
                    padding: .2mm 1mm;
                    line-height: 3mm;
                }
            }
        </style>
    </head>
    <body>
        <div class="no-print" <?= $validationPreview ? 'style="display:none"' : '' ?>>
        <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8')?>" style="color:#fff; font-weight:bold; text-decoration:none;">&larr; <?= htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8')?></a><br>
        <button class="btn-action" onclick="window.print()">Cetak Jadwal Harian</button>
    </div>

    <?php
    if (count($data_per_seksi) == 0) {
        echo "<div class='a4-landscape text-center' style='padding-top:100px;'><h3>Tidak ada jadwal kalibrasi sampai tanggal " . date('d-m-Y', strtotime($filter_tgl)) . "</h3></div>";
    }

$tgl_indo = date('d-m-Y');

foreach ($data_per_seksi as $seksi => $items) {
    $chunks = array_chunk($items, 30); // Max 30 baris per halaman biar fit
    $total_hal = count($chunks);

    foreach ($chunks as $idx => $chunk) {
        $hal_ke = $idx + 1;
        ?>
            <div class="a4-landscape">
            <div class="pt-title">PT. SURYA TOTO INDONESIA Tbk.</div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 5px;">
                <div style="width: 70%;">
                    <div class="header-title">JADUAL KALIBRASI HARIAN</div>
                </div>
                <div style="width: 30%;">
                    <div style="margin-bottom: 5px;">Tanggal , <?php echo $tgl_indo; ?></div>
                    <table class="sign-box">
                        <tr>
                            <th width="33%">DIBUAT</th>
                            <th width="33%">DIPERIKSA</th>
                            <th width="33%">DISETUJUI</th>
                        </tr>
                        <tr>
                            <td><?= htmlspecialchars($signatures['dibuat'], ENT_QUOTES, 'UTF-8') ?><?php if ($signatures['dibuat_tanggal'] !== ''): ?><span class="sign-date"><?= htmlspecialchars($signatures['dibuat_tanggal'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></td>
                            <td><?= htmlspecialchars($signatures['diperiksa'], ENT_QUOTES, 'UTF-8') ?><?php if ($signatures['diperiksa_tanggal'] !== ''): ?><span class="sign-date"><?= htmlspecialchars($signatures['diperiksa_tanggal'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></td>
                            <td><?= htmlspecialchars($signatures['disetujui'], ENT_QUOTES, 'UTF-8') ?><?php if ($signatures['disetujui_tanggal'] !== ''): ?><span class="sign-date"><?= htmlspecialchars($signatures['disetujui_tanggal'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?></td>
                        </tr>
                        <tr>
                            <th>PETUGAS</th>
                            <th>SUPERVISOR</th>
                            <th>MANAGER</th>
                        </tr>
                    </table>
                    <div style="clear:both; text-align:right; font-weight:bold;">HAL : <?php echo $hal_ke; ?> / <?php echo $total_hal; ?></div>
                </div>
            </div>
            <div style="font-weight:bold; margin-bottom: 5px; font-size:12px;">SEKSI : <?php echo strtoupper($seksi); ?></div>
            <table class="table-excel">
                <thead>
                    <tr>
                        <th width="3%">NO</th>
                        <th width="15%">NAMA ALAT</th>
                        <th width="20%">SPESIFIKASI</th>
                        <th width="10%">NO. ALAT</th>
                        <th width="8%">RESOLUSI</th>
                        <th width="8%">PERIODE<br>KALIBRASI</th>
                        <th width="9%">RENCANA<br>KALIBRASI</th>
                        <th width="9%">AKTUAL<br>KALIBRASI</th>
                        <th width="6%">HASIL</th>
                        <th width="12%">KALIBRASI BERIKUTNYA</th>
                    </tr>
                </thead>
                <tbody>
                        <?php
                    $no = ($idx * 30) + 1;
        foreach ($chunk as $row) {
            $periode = (int)($row['periode_kalibrasi'] ?? 0);
            $periode_txt = $periode === 365 ? '1 Tahun' : ($periode === 180 ? '6 Bulan' : ($periode > 0 ? $periode . ' Hari' : '-'));
            $tgl_ren = date('d-m-Y', strtotime($row['tanggal_rencana']));
            $tgl_aktual = !empty($row['tanggal_aktual']) ? date('d-m-Y', strtotime($row['tanggal_aktual'])) : '-';
            $hasil = $row['hasil_qa'] !== '' ? $row['hasil_qa'] : '-';
            $tgl_berikutnya = !empty($row['tanggal_berikutnya']) && $row['tanggal_berikutnya'] !== '-'
                ? date('d-m-Y', strtotime($row['tanggal_berikutnya'])) : '-';
            $nama = htmlspecialchars((string)$row['nama_part'], ENT_QUOTES, 'UTF-8');
            $spesifikasi = htmlspecialchars((string)$row['spesifikasi'], ENT_QUOTES, 'UTF-8');
            $no_alat = htmlspecialchars((string)$row['no_part'], ENT_QUOTES, 'UTF-8');
            $resolusi = htmlspecialchars((string)($row['resolusi'] ?: '~'), ENT_QUOTES, 'UTF-8');
            $hasil = htmlspecialchars((string)$hasil, ENT_QUOTES, 'UTF-8');
            echo "<tr>
                                <td>{$no}</td>
                                <td class='text-left'>{$nama}</td>
                                <td class='text-left'>{$spesifikasi}</td>
                                <td class='text-left'>{$no_alat}</td>
                                <td>{$resolusi}</td>
                                <td>{$periode_txt}</td>
                                <td>{$tgl_ren}</td>
                                <td>{$tgl_aktual}</td>
                                <td>{$hasil}</td>
                                <td>{$tgl_berikutnya}</td>
                            </tr>";
            $no++;
        }
                        
        // Fill sisa baris kosong jika kurang dari 30
        $sisa = 30 - count($chunk);
        for ($i = 0; $i < $sisa; $i++) {
            echo "<tr>" . str_repeat("<td>&nbsp;</td>", 10) . "</tr>";
        }
        ?>
                    </tbody>
            </table>
            <div style="text-align: right; font-size: 10px; margin-top: 5px; font-weight: bold;">STIS-CMFG4-04/R3</div>
        </div>
            <?php
    }
}
?>

</body>
</html>

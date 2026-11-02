<?php
session_start();
if (empty($_SESSION['login'])) { header('Location: ../../logout.php'); exit; }
require_once __DIR__.'/../../inc/inc_koneksi.php';
require_once __DIR__.'/../../inc/qa_rbac.php';
qaRbacRequire($konek,'upload_master');
require_once __DIR__ . '/../../inc/qa_master_csv.php';
require_once __DIR__ . '/../../inc/qa_master_workbook.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$konek->set_charset('utf8mb4');
function csvH($value){return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');}
if (empty($_SESSION['qa_csv_csrf'])) $_SESSION['qa_csv_csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['qa_csv_csrf'];
$error = ''; $schema = []; $transactional = false;
$flash = $_SESSION['qa_csv_flash'] ?? null;
unset($_SESSION['qa_csv_flash']);
$actor = trim((string)($_SESSION['userid'] ?? ''));
try {
    $schema = qaCsvSchema($konek);
    $transactional = qaCsvTransactional($konek);
} catch (Throwable $e) {
    error_log('QA CSV schema: '.$e->getMessage());
    $error = $e instanceof mysqli_sql_exception ? 'Struktur database belum siap untuk upload master data.' : $e->getMessage();
}
if (isset($_GET['template']) && in_array($_GET['template'], ['1','csv','xlsx'], true)) {
    if ($_GET['template'] !== 'xlsx') {
        header('Location: upload_master_csv.php?template=xlsx');
        exit;
    }
    if ($error) { http_response_code(503); echo csvH($error); exit; }
    try {
        $workbook = qaMasterWorkbookTemplate();
    } catch (Throwable $e) {
        error_log('QA Excel template: '.$e->getMessage());
        http_response_code(503);
        echo csvH('Template Excel tidak dapat dibuat. Coba lagi atau hubungi administrator.');
        exit;
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="template_master_alat.xlsx"');
    header('Cache-Control: no-store');
    echo $workbook;
    exit;
}
// Discard previews from the former two-step upload flow.
unset($_SESSION['qa_csv_preview']);
if (isset($_SESSION['qa_csv_errors']) && time() - $_SESSION['qa_csv_errors']['created'] > 1800) unset($_SESSION['qa_csv_errors']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validToken = is_string($_POST['csrf'] ?? null) && hash_equals($csrf, $_POST['csrf']);
    if (!$validToken) {
        http_response_code(403);
        $error = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (!$error) {
        try {
            $action = $_POST['action'] ?? '';
            if ($action === 'upload') {
                unset($_SESSION['qa_csv_errors']);
                $file = $_FILES['csv_file'] ?? null;
                if (!$file || !is_int($file['error'] ?? null) || $file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload gagal. Pilih Excel (.xlsx) atau CSV maksimal 2 MB dan coba lagi.');
                $extension = is_string($file['name'] ?? null) ? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) : '';
                if (!in_array($extension, ['csv','xlsx'], true)) throw new RuntimeException('File harus berformat .xlsx atau .csv. File .xls perlu disimpan ulang sebagai .xlsx.');
                if ($file['size'] > QA_CSV_MAX_BYTES) throw new RuntimeException('Ukuran file maksimal 2 MB.');
                if (!is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) throw new RuntimeException('File upload tidak valid.');
                if ($extension === 'xlsx') {
                    $rows = qaMasterWorkbookParse($file['tmp_name'], $schema);
                } else {
                    $text = file_get_contents($file['tmp_name'], false, null, 0, QA_CSV_MAX_BYTES + 1);
                    if ($text === false) throw new RuntimeException('CSV tidak dapat dibaca.');
                    $rows = qaCsvParse($text, $schema);
                }
                $plan = qaCsvPlan($konek, $rows);
                if ($plan['counts']['error']) {
                    $_SESSION['qa_csv_errors'] = ['created'=>time(), 'filename'=>basename($file['name']),
                        'items'=>array_values(array_filter($plan['items'],static function($item){return !empty($item['errors']);}))];
                    header('Location: upload_master_csv.php#csvErrors'); exit;
                }
                // State is checked again under an InnoDB transaction or MyISAM table locks.
                $counts = qaCsvApply($konek, $rows, $plan['signature'], $actor);
                $_SESSION['qa_csv_flash'] = $counts;
                // A repeated POST with the previous form token cannot import again.
                $_SESSION['qa_csv_csrf'] = bin2hex(random_bytes(32));
                if ($extension === 'xlsx' && !empty($rows[0]['header_only'])) {
                    foreach ($plan['items'] as $item) if ($item['action']==='new') {
                        header('Location: ../qa/kalibrasi/cek_alat.php?no_alat='.rawurlencode($item['data']['no_part'])); exit;
                    }
                }
                header('Location: upload_master_csv.php'); exit;
            } else { throw new RuntimeException('Aksi formulir tidak dikenal.'); }
        } catch (Throwable $e) {
            if ($e instanceof mysqli_sql_exception) {
                error_log('QA CSV import: '.$e->getMessage());
                $error = $transactional
                    ? 'Proses database gagal. Muat ulang halaman dan periksa data sebelum mencoba kembali.'
                    : 'Proses database gagal. Karena MyISAM tidak mendukung rollback, sebagian data mungkin sudah masuk. Periksa qa_part dan qa_alat_registry sebelum mengulang import.';
            } else { $error = $e->getMessage(); }
        }
    }
}
$validation = $_SESSION['qa_csv_errors'] ?? null;
$page = filter_var($_GET['halaman'] ?? 1, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: 1;
$pages = $validation ? max(1, (int)ceil(count($validation['items']) / 100)) : 1;
$page = min($page, $pages);
$items = $validation ? array_slice($validation['items'], ($page-1)*100, 100) : [];
?>
<!doctype html>
<html lang="id"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload Master Data Excel / CSV | QA System</title>
    <link rel="icon" href="../../images/favicon-toto-qa.png?v=1">
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../font-awesome-4.3.0/css/font-awesome.min.css">
    <style>
        body{background:#f4f6f9;padding-top:75px;color:#25364a}
        .navbar .container-fluid{display:flex;align-items:center}
        .navbar .navbar-header{flex-shrink:0}
        .navbar .navbar-nav{margin:0}
        .navbar-brand img{height:25px;margin-top:-3px}
        .csv-container{max-width:1400px;margin:auto;padding:0 20px 30px}
        .csv-card{padding:22px;background:#fff;border:1px solid #ddd;border-radius:8px;margin-bottom:20px}
        h2{font-size:24px;font-weight:bold;margin-top:0;color:#1e3f66}
        .csv-upload{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;margin-top:18px}
        .csv-upload .form-group{margin:0;flex:1;min-width:200px}
        .csv-table-wrap{overflow:auto}
        .csv-table{min-width:900px;margin-bottom:0}
        .csv-table th{background:#e9eef4;white-space:nowrap}
        .csv-table td{vertical-align:top!important;overflow-wrap:anywhere}
        .csv-table ul{padding-left:18px;margin-bottom:0}
        .csv-pager{display:flex;gap:12px;align-items:center;justify-content:flex-end;margin-top:15px}
        .text-muted{color:#667687}
        .example-help{padding:16px 18px;border:1px solid #d5e2ef;border-radius:8px;background:#f5f9fd;margin:18px 0}
        .example-help h3{font-size:18px;margin:0 0 8px;color:#1e3f66}
        .example-help p{margin:0 0 10px}
        .example-help .csv-table{min-width:760px;background:#fff}
        .example-help .csv-table td:first-child{font-weight:700;white-space:nowrap}
        .example-sample td{background:#fff1c7}
        .example-entry td{background:#fff}
        .example-list{margin:10px 0 0}
        .example-list summary{cursor:pointer;font-weight:700;color:#1e3f66;padding:4px 0}
        .source-badge{display:inline-block;padding:2px 7px;border-radius:12px;background:#dcece1;color:#275735;font-size:12px;white-space:nowrap}
        .source-badge.is-illustrative{background:#fff1c7;color:#745520}
        @media(max-width:600px){.csv-container{padding:0 12px 20px}.csv-card{padding:16px}.csv-upload .btn{width:100%}}
    </style>
</head><body>
<nav class="navbar navbar-default navbar-fixed-top"><div class="container-fluid">
    <div class="navbar-header"><a class="navbar-brand" href="../../media.php"><img src="../../images/toto.png" alt="TOTO"></a></div>
    <ul class="nav navbar-nav"><li><a href="../../media.php"><i class="fa fa-arrow-left"></i> Menu QA</a></li></ul>
</div></nav>
<main class="csv-container">
    <section class="csv-card">
        <h2>Upload Master Data Excel / CSV</h2>
        <p>Daftarkan alat baru berdasarkan <b>No. Alat</b>. Alat yang sudah ada dilewati.</p>
        <?php if ($error): ?><div class="alert alert-danger" role="alert"><?=csvH($error)?></div><?php endif; ?>
        <?php if ($flash): ?><div class="alert alert-success" role="status">Import selesai: <b><?=(int)$flash['new']?></b> alat ditambahkan, <b><?=(int)$flash['update']?></b> diperbarui, dan <b><?=(int)$flash['unchanged']?></b> tidak berubah.</div><?php endif; ?>
        <a class="btn btn-default" href="?template=xlsx"><i class="fa fa-download"></i> Unduh Template Excel</a>
        <p class="text-muted" style="margin-top:10px">Satu template mencakup seluruh 26 format, masing-masing pada tab terpisah. Saat dibuka, mulai dari sheet <b>Mulai di Sini</b>.</p>
        <div class="example-help" aria-labelledby="exampleTitle">
            <h3 id="exampleTitle">Contoh pengisian di halaman ini</h3>
            <p>Misalnya alat <b>Format P - SK Gauge</b>. Di Excel, buka tab itu dan isi baris 2 seperti berikut. Contoh ini hanya panduan di halaman; <b>file Excel yang diunduh tetap kosong</b>.</p>
            <div class="csv-table-wrap"><table class="table table-bordered csv-table">
                <thead><tr><th>NO. ALAT</th><th>HEADER LAPORAN + BBRAU | NAMA ALAT</th><th>HEADER LAPORAN + BBRAU | SEKSI</th><th>FOOTER LAPORAN + BBRAU | PERIODE KALIBRASI (HARI)</th><th>HEADER LAPORAN + BBRAU | TANGGAL REGISTRASI</th></tr></thead>
                <tbody><tr class="example-sample"><td>CONTOH-P-001</td><td>SK Gauge</td><td>MACHINING 3</td><td>365</td><td>2026-11-02</td></tr></tbody>
            </table></div>
            <p class="text-muted">Ganti semua nilai contoh dengan data alat sebenarnya. Kolom lain seperti spesifikasi, metode kalibrasi, standar uji, dan traceability diisi sesuai dokumen alat. Titik standar diisi di halaman kalibrasi setelah upload.</p>
        </div>
        <p><b>Cara singkat:</b> pilih tab format, lalu isi satu alat per baris mulai baris 2. Semua tab kosong. Penanda kolom menunjukkan data yang masuk ke header/footer laporan dan BBRAU.</p>
        <p>Kolom body/titik standar tidak diisi di Excel. Setelah upload, sistem membuka halaman kalibrasi untuk melengkapi titik standar pertama kali. Alat baru langsung berstatus <b>Register</b>; setelah titik standar disimpan, form kalibrasi biasa muncul.</p>
        <p>Untuk banyak alat dalam satu file, sistem membuka alat pertama. Cari No. Alat berikutnya di halaman kalibrasi untuk melengkapi titik standarnya. Format S dan Z sudah tersedia; standar awalnya diisi di halaman kalibrasi setelah upload.</p>
        <p>No. Alat yang belum ada akan didaftarkan. No. Alat yang sudah ada dilewati tanpa mengubah metadata atau titik standar.</p>
        <form method="post" action="upload_master_csv.php" enctype="multipart/form-data" class="csv-upload" id="masterUploadForm">
            <input type="hidden" name="csrf" value="<?=csvH($csrf)?>"><input type="hidden" name="action" value="upload">
            <input type="hidden" name="MAX_FILE_SIZE" value="<?=QA_CSV_MAX_BYTES?>">
            <div class="form-group"><label for="csvFile">Template Excel (.xlsx) atau CSV format lama</label><input class="form-control" type="file" id="csvFile" name="csv_file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,.csv,text/csv" required aria-describedby="csvFileHelp"></div>
            <button class="btn btn-primary" type="submit" id="masterUploadSubmit"><i class="fa fa-upload"></i> Upload &amp; Simpan</button>
        </form>
        <?php if (!$transactional): ?><p class="text-warning" style="margin-top:12px"><b>Catatan:</b> database MyISAM tidak mendukung rollback jika server gagal saat batch berjalan. Backup database sebelum upload banyak alat.</p><?php endif; ?>
        <p id="csvFileHelp" class="text-muted" style="margin-top:10px">Untuk template per format, simpan sebagai <b>Excel Workbook (.xlsx)</b>, bukan CSV; CSV tidak menyimpan tab. Maksimal 2 MB dan 1.000 alat per file. CSV format lama harus UTF-8; pemisah koma, titik koma, atau tab dikenali otomatis.</p>
        <p><b>Nama Alat, No. Alat, Periode Kalibrasi, Seksi, dan Tanggal Registrasi wajib diisi pada Excel.</b> Gunakan nama seksi yang terdaftar; satu file boleh berisi beberapa seksi dan format. Rencana Kalibrasi dapat dibiarkan kosong agar dihitung otomatis dari Tanggal Registrasi + Periode. CSV format lama tetap wajib berisi Rencana Kalibrasi.</p>
        <p>Isi <b>Periode Kalibrasi</b> dalam <b>satuan hari</b>, misalnya <b>180</b> atau <b>365</b>. Tanggal dapat ditulis <b>16 - 09 - 2026</b>, <b>16-09-2026</b>, atau <b>2026-09-16</b>. CSV lama tetap hanya memperbarui data dasar; isian Spesifikasi dan Resolusi yang kosong mempertahankan nilai lama.</p>
    </section>
    <?php if ($validation): ?>
    <section class="csv-card" id="csvErrors">
        <h3 style="margin-top:0">Upload gagal: <?=csvH($validation['filename'])?></h3>
        <div class="alert alert-warning" role="alert">Data belum disimpan karena validasi menemukan masalah. Perbaiki <?=count($validation['items'])?> baris bermasalah di bawah, lalu upload ulang file.</div>
        <div class="csv-table-wrap"><table class="table table-bordered csv-table">
            <thead><tr><th>Baris Data Alat / CSV</th><th>No. Alat</th><th>Nama Alat</th><th>Seksi</th><th>Periode (hari)</th><th>Jadwal</th><th>Kesalahan</th></tr></thead><tbody>
            <?php foreach ($items as $item): $d=$item['data']; ?>
            <tr><td><?=(int)$item['line']?></td>
                <td><?=csvH($d['no_part']??'-')?></td><td><?=csvH($d['nama_part']??'-')?></td><td><?=csvH($d['seksi_pemilik']??'-')?></td><td><?=csvH($d['periode_kalibrasi']??'-')?></td><td><?=csvH($d['jadwal_kalibrasi']??'-')?></td>
                <td><ul class="text-danger"><?php foreach ($item['errors'] as $message): ?><li><?=csvH($message)?></li><?php endforeach; ?></ul></td>
            </tr><?php endforeach; ?>
            </tbody></table></div>
        <div class="csv-pager">
            <?php if ($page>1): ?><a class="btn btn-default btn-sm" href="?halaman=<?=$page-1?>#csvErrors">Sebelumnya</a><?php endif; ?>
            <span>Halaman <?=$page?> / <?=$pages?> (maksimal 100 baris per halaman)</span>
            <?php if ($page<$pages): ?><a class="btn btn-default btn-sm" href="?halaman=<?=$page+1?>#csvErrors">Berikutnya</a><?php endif; ?>
        </div>
    </section>
    <?php endif; ?>
</main>
<script>
(function () {
    var form = document.getElementById('masterUploadForm');
    var button = document.getElementById('masterUploadSubmit');
    var original = button.innerHTML;
    form.addEventListener('submit', function (event) {
        if (button.disabled) { event.preventDefault(); return; }
        button.style.width = button.getBoundingClientRect().width + 'px';
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.textContent = 'Menyimpan...';
    });
    window.addEventListener('pageshow', function () {
        button.disabled = false;
        button.removeAttribute('aria-busy');
        button.style.width = '';
        button.innerHTML = original;
    });
}());
</script>
</body></html>

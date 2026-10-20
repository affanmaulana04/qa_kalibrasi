<?php
session_start();
if (empty($_SESSION['login'])) { header('Location: ../../logout.php'); exit; }
require_once __DIR__.'/../../inc/inc_koneksi.php';
require_once __DIR__.'/../../inc/qa_rbac.php';
qaRbacRequire($konek,'upload_master');
require_once __DIR__ . '/../../inc/qa_master_csv.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$konek->set_charset('utf8mb4');
function csvH($value){return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');}
if (empty($_SESSION['qa_csv_csrf'])) $_SESSION['qa_csv_csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['qa_csv_csrf'];
$error = ''; $schema = [];
$flash = $_SESSION['qa_csv_flash'] ?? null;
unset($_SESSION['qa_csv_flash']);
$actor = trim((string)($_SESSION['userid'] ?? ''));
try {
    $schema = qaCsvSchema($konek);
} catch (Throwable $e) {
    error_log('QA CSV schema: '.$e->getMessage());
    $error = $e instanceof mysqli_sql_exception ? 'Struktur database belum siap untuk upload master data.' : $e->getMessage();
}
if (isset($_GET['template']) && in_array($_GET['template'], ['1','csv'], true)) {
    if ($error) { http_response_code(503); echo csvH($error); exit; }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="template_master_alat.csv"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, qaCsvTemplateHeaders(), ',', '"', '');
    foreach (qaCsvTemplateExamples() as $example) fputcsv($out, $example, ',', '"', '');
    fclose($out);
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
                if (!$file || !is_int($file['error'] ?? null) || $file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload gagal. Pilih CSV maksimal 2 MB dan coba lagi.');
                if (!is_string($file['name']) || strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') throw new RuntimeException('File harus berformat .csv.');
                if ($file['size'] > QA_CSV_MAX_BYTES) throw new RuntimeException('Ukuran CSV maksimal 2 MB.');
                if (!is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) throw new RuntimeException('File upload tidak valid.');
                $text = file_get_contents($file['tmp_name'], false, null, 0, QA_CSV_MAX_BYTES + 1);
                if ($text === false) throw new RuntimeException('CSV tidak dapat dibaca.');
                $rows = qaCsvParse($text, $schema);
                $plan = qaCsvPlan($konek, $rows);
                if ($plan['counts']['error']) {
                    $_SESSION['qa_csv_errors'] = ['created'=>time(), 'filename'=>basename($file['name']),
                        'items'=>array_values(array_filter($plan['items'],static function($item){return !empty($item['errors']);}))];
                    header('Location: upload_master_csv.php#csvErrors'); exit;
                }
                // Import diverifikasi ulang dengan table locks MyISAM; backup dianjurkan sebelum import massal.
                $counts = qaCsvApply($konek, $rows, $plan['signature'], $actor);
                $_SESSION['qa_csv_flash'] = $counts;
                // A repeated POST with the previous form token cannot import again.
                $_SESSION['qa_csv_csrf'] = bin2hex(random_bytes(32));
                header('Location: upload_master_csv.php'); exit;
            } else { throw new RuntimeException('Aksi formulir tidak dikenal.'); }
        } catch (Throwable $e) {
            if ($e instanceof mysqli_sql_exception) {
                error_log('QA CSV import: '.$e->getMessage());
                $error = 'Proses database gagal. Karena MyISAM tidak mendukung rollback, sebagian data mungkin sudah masuk. Periksa qa_part dan qa_alat_registry sebelum mengulang import.';
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
    <title>Upload Master Data CSV | QA System</title>
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
        .csv-template{margin:15px 0;min-width:780px;color:#25364a}
        .csv-template td,.csv-template th{border:1px solid #999;padding:10px;font-weight:normal}
        .csv-template caption{color:#25364a;padding:8px 0}
        .csv-pager{display:flex;gap:12px;align-items:center;justify-content:flex-end;margin-top:15px}
        .text-muted{color:#667687}
        @media(max-width:600px){.csv-container{padding:0 12px 20px}.csv-card{padding:16px}.csv-upload .btn{width:100%}}
    </style>
</head><body>
<nav class="navbar navbar-default navbar-fixed-top"><div class="container-fluid">
    <div class="navbar-header"><a class="navbar-brand" href="../../media.php"><img src="../../images/toto.png" alt="TOTO"></a></div>
    <ul class="nav navbar-nav"><li><a href="../../media.php"><i class="fa fa-arrow-left"></i> Menu QA</a></li></ul>
</div></nav>
<main class="csv-container">
    <section class="csv-card">
        <h2>Upload Master Data CSV</h2>
        <p>Tambahkan alat baru atau perbarui alat yang sudah ada berdasarkan <b>No. Alat</b>.</p>
        <?php if ($error): ?><div class="alert alert-danger" role="alert"><?=csvH($error)?></div><?php endif; ?>
        <?php if ($flash): ?><div class="alert alert-success" role="status">Import selesai: <b><?=(int)$flash['new']?></b> alat ditambahkan, <b><?=(int)$flash['update']?></b> diperbarui, dan <b><?=(int)$flash['unchanged']?></b> tidak berubah.</div><?php endif; ?>
        <a class="btn btn-default" href="?template=1"><i class="fa fa-download"></i> Unduh Template CSV</a>
        <div class="csv-table-wrap"><table class="csv-template">
            <caption>Template berisi contoh berikut. Ganti atau hapus baris contoh sebelum upload.</caption>
            <thead><tr><?php foreach (qaCsvTemplateHeaders() as $heading): ?><th><?=csvH($heading)?></th><?php endforeach; ?></tr></thead>
            <tbody><?php foreach (qaCsvTemplateExamples() as $example): ?><tr><?php foreach ($example as $value): ?><td><?=csvH($value)?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
        </table></div>
        <form method="post" action="upload_master_csv.php" enctype="multipart/form-data" class="csv-upload">
            <input type="hidden" name="csrf" value="<?=csvH($csrf)?>"><input type="hidden" name="action" value="upload">
            <input type="hidden" name="MAX_FILE_SIZE" value="<?=QA_CSV_MAX_BYTES?>">
            <div class="form-group"><label for="csvFile">File CSV</label><input class="form-control" type="file" id="csvFile" name="csv_file" accept=".csv,text/csv" required aria-describedby="csvFileHelp"></div>
            <button class="btn btn-primary" type="submit"><i class="fa fa-upload"></i> Upload &amp; Simpan</button>
        </form>
        <p class="text-warning" style="margin-top:12px"><b>Catatan:</b> database MyISAM tidak mendukung rollback jika server gagal saat batch berjalan. Backup database sebelum upload banyak alat.</p>
        <p id="csvFileHelp" class="text-muted" style="margin-top:10px">CSV UTF-8, maksimal 2 MB / 1.000 baris. Pemisah koma, titik koma, atau tab dikenali otomatis.</p>
        <p>Baris pertama berisi tujuh judul kolom; isi alat mulai baris kedua. Isi <b>Seksi</b> pada setiap baris, di kolom setelah <b>Rencana Kalibrasi</b>, menggunakan nama seksi yang terdaftar. Satu file boleh berisi beberapa seksi.</p>
        <p><b>Nama Alat, No. Alat, Periode Kalibrasi, Rencana Kalibrasi, dan Seksi wajib diisi.</b> Spesifikasi dan Resolusi boleh kosong; saat memperbarui alat, isian kosong mempertahankan nilai lama.</p>
        <p>Isi <b>Periode Kalibrasi</b> dengan angka dalam <b>satuan hari</b>, misalnya <b>180</b> atau <b>365</b>. Tanggal dapat ditulis <b>16 - 09 - 2026</b>, <b>16-09-2026</b>, atau <b>2026-09-16</b>. Simpan file sebagai CSV UTF-8 setelah mengisi template.</p>
    </section>
    <?php if ($validation): ?>
    <section class="csv-card" id="csvErrors">
        <h3 style="margin-top:0">Upload gagal: <?=csvH($validation['filename'])?></h3>
        <div class="alert alert-warning" role="alert">Data belum disimpan karena validasi menemukan masalah. Perbaiki <?=count($validation['items'])?> baris bermasalah di bawah, lalu upload ulang CSV.</div>
        <div class="csv-table-wrap"><table class="table table-bordered csv-table">
            <thead><tr><th>Baris CSV</th><th>No. Alat</th><th>Nama Alat</th><th>Seksi</th><th>Periode (hari)</th><th>Jadwal</th><th>Kesalahan</th></tr></thead><tbody>
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
</body></html>

<?php
session_start();
if (empty($_SESSION['login'])) { header('Location: ../../../logout.php'); exit; }
require_once __DIR__.'/../../../inc/inc_koneksi.php';
require_once __DIR__ . '/../../../inc/qa_rbac.php';
qaRbacRequire($konek,'laporan_hasil');
require_once __DIR__.'/../../../inc/qa_daily_report.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$konek->set_charset('utf8mb4');
function dailyH($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$today = (new DateTimeImmutable('now',new DateTimeZone('Asia/Bangkok')))->format('Y-m-d');
$date = is_string($_GET['tanggal'] ?? null) ? $_GET['tanggal'] : $today;
$section = is_string($_GET['seksi'] ?? null) ? trim($_GET['seksi']) : '';
$format = is_string($_GET['format'] ?? null) ? $_GET['format'] : '';
$error = ''; $records = []; $sections = []; $matching = [];
$submitted = isset($_GET['tanggal']) || isset($_GET['render']);
try {
    foreach (['tanggal','seksi','format'] as $key) {
        if (isset($_GET[$key]) && !is_string($_GET[$key])) throw new InvalidArgumentException('Filter laporan tidak valid.');
    }
    if (!qaDailyDate($date)) throw new InvalidArgumentException('Isi tanggal kalibrasi yang valid.');
    if ($format !== '' && !isset(qaDailyFormats()[$format])) throw new InvalidArgumentException('Format laporan tidak tersedia.');
    $records = qaDailyRecords($konek,$date);
    foreach (qaDailyQuery($konek,'SELECT nama_seksi FROM qa_seksi_master ORDER BY nama_seksi') as $row) $sections[$row['nama_seksi']] = true;
    foreach ($records as $record) if ($record['payload']['seksi_pemilik'] !== '') $sections[$record['payload']['seksi_pemilik']] = true;
    ksort($sections,SORT_NATURAL | SORT_FLAG_CASE);
    if ($submitted && !isset($sections[$section])) throw new InvalidArgumentException('Pilih seksi yang tersedia.');
    $matching = array_values(array_filter($records,static function($r)use($section,$format){
        return $r['payload']['seksi_pemilik'] === $section && ($format === '' || $r['payload']['format'] === $format);
    }));
} catch (Throwable $e) {
    if ($e instanceof mysqli_sql_exception) { error_log('Daily QA report: '.$e->getMessage()); $error = 'Laporan gagal dimuat. Silakan coba kembali.'; }
    else $error = $e->getMessage();
}
if (isset($_GET['render'])) {
    $id = is_string($_GET['render']) ? filter_var($_GET['render'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) : false;
    $record = null;
    foreach ($matching as $candidate) if ($candidate['id'] === $id) $record = $candidate;
    if ($error || !$record) { http_response_code(404); echo 'Transaksi tidak ditemukan pada filter yang dipilih.'; exit; }
    if (!is_string($_GET['revision'] ?? null) || !hash_equals($record['revision'],$_GET['revision'])) {
        http_response_code(409); echo 'Data transaksi berubah. Tampilkan ulang daftar transaksi.'; exit;
    }
    if (!$record['payload']['hasil_array']) { http_response_code(422); echo 'Transaksi belum memiliki rincian hasil.'; exit; }
    $qaReportPayload = $record['payload'];
    // Only the existing renderer script may execute inside the preview frame.
    $nonce = bin2hex(random_bytes(16));
    header("Content-Security-Policy: default-src 'none'; script-src 'nonce-$nonce'; style-src 'unsafe-inline'; img-src 'self' data:; frame-ancestors 'self'");
    header('Cache-Control: no-store');
    session_write_close();
    ob_start();
    require __DIR__.'/../kalibrasi/cetak_laporan.php';
    $html = ob_get_clean();
    echo str_replace('<script>', '<script nonce="'.$nonce.'">', $html);
    exit;
}
header('Cache-Control: no-store');
$dailyScriptVersion = substr(hash_file('sha256', __DIR__.'/../../../js/qa_daily_report.js'), 0, 16);
?>
<!doctype html><html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Laporan Kalibrasi Multi-Alat | QA System</title>
<link rel="stylesheet" href="../../../css/bootstrap.min.css">
<style>
body{background:#f4f6f9;color:#25364a;padding-top:75px;font-family:Arial,sans-serif}
.navbar .container-fluid{display:flex;align-items:center}.navbar-brand img{height:25px}.navbar .navbar-nav{margin:0}
.daily-container{padding:0 24px 24px;max-width:1800px;margin:auto}.daily-card{background:white;border:1px solid #ddd;border-radius:8px;padding:22px;margin-bottom:20px}
h2{font-size:24px;color:#1e3f66;margin-top:0;font-weight:bold}.daily-filter{display:flex;gap:15px;flex-wrap:wrap;align-items:end}.daily-filter .form-group{min-width:190px;margin:0;flex:1}
.daily-scroll{overflow:auto}.daily-list{min-width:900px}.daily-list th{background:#e9eef4}.daily-actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:15px 0}
.daily-report{background:white;padding:20px;margin:20px 0;border:1px solid #bbb;overflow:auto}.daily-report h3{text-align:center;font-size:19px;margin:8px 0}.daily-company,.daily-period{text-align:center;margin:6px 0 14px}
.daily-grid .diagonal-strike-td{background:linear-gradient(to top right,transparent 49.5%,#999 50%,transparent 50.5%)}
.daily-grid{border-collapse:collapse;width:100%;min-width:1000px;table-layout:fixed;color:#000;font-size:11px}.daily-grid td,.daily-grid th{border:1px solid #777;padding:5px;vertical-align:middle;text-align:center;overflow-wrap:anywhere}.daily-grid th{background:#edf0f3}.daily-grid .daily-meta{text-align:left;background:#f5f5f5;font-size:11px;line-height:1.6}.daily-meta span{display:inline-block;margin-right:18px}.daily-meta strong{font-weight:600}.daily-error{color:#a12622}.daily-render-frame{position:absolute;left:-20000px;width:1400px;height:1000px;border:0}
.daily-print-frame{display:block;width:100%;height:85vh;min-height:600px;border:1px solid #bbb;background:#525659}
@media(max-width:600px){.daily-container{padding:0 10px 20px}.daily-card{padding:15px}}
@media print{@page{size:A4 landscape;margin:8mm}html,body{padding:0;margin:0;background:white;color:black}.no-print{display:none!important}.daily-container{max-width:none;margin:0;padding:0}.daily-report{padding:0;margin:0;border:0;overflow:visible;break-after:page}.daily-report:last-child{break-after:auto}.daily-grid{min-width:0;font-size:8px}.daily-grid td,.daily-grid th{padding:3px}.daily-grid .daily-meta{font-size:8px}.daily-grid thead{display:table-header-group}.daily-grid tbody{break-inside:avoid}.daily-render-frame{display:none}.daily-report h3{font-size:15px}}
</style></head><body>
<nav class="navbar navbar-default navbar-fixed-top no-print"><div class="container-fluid"><a class="navbar-brand" href="../../../media.php"><img src="../../../images/toto.png" alt="TOTO"></a><ul class="nav navbar-nav"><li><a href="../../../media.php">← Menu QA</a></li></ul></div></nav>
<main class="daily-container">
<section class="daily-card no-print"><h2>Laporan Kalibrasi Multi-Alat</h2>
<p>Laporan dengan lebih dari satu nomor alat dalam satu tabel hasil pemeriksaan.</p>
<form method="get" class="daily-filter">
<div class="form-group"><label for="dailySection">Seksi</label><select id="dailySection" name="seksi" class="form-control" required><option value="">Pilih seksi</option><?php foreach ($sections as $name=>$unused): ?><option value="<?=dailyH($name)?>" <?=$section===$name?'selected':''?>><?=dailyH($name)?></option><?php endforeach; ?></select></div>
<div class="form-group"><label for="dailyDate">Tanggal kalibrasi</label><input id="dailyDate" type="date" name="tanggal" class="form-control" value="<?=dailyH($date)?>" required></div>
<div class="form-group"><label for="dailyFormat">Format laporan</label><select id="dailyFormat" name="format" class="form-control"><option value="">Semua format</option><?php foreach (qaDailyFormats() as $code=>$label): ?><option value="<?=$code?>" <?=$format===$code?'selected':''?>><?=$code?> — <?=dailyH($label)?></option><?php endforeach; ?></select></div>
<button class="btn btn-primary" type="submit">Tampilkan</button></form>
<?php if ($error): ?><div class="alert alert-danger" style="margin-top:18px" role="alert"><?=dailyH($error)?></div><?php endif; ?>
</section>
<?php if ($submitted && !$error): ?>
<section class="daily-card no-print">
<?php if (!$matching): ?><p style="margin:0">Tidak ada transaksi untuk seksi, tanggal, dan format yang dipilih.</p><?php else: ?>
<div class="daily-scroll"><table class="table table-bordered daily-list"><thead><tr><th><input type="checkbox" id="dailyAll" checked aria-label="Pilih semua transaksi"></th><th>No. Alat</th><th>Nama Alat</th><th>Format</th><th>No. Transaksi / Laporan</th><th>Dibuat</th><th>Tanggal Periksa</th><th>Status Validasi</th></tr></thead><tbody>
<?php foreach ($matching as $record): $p=$record['payload']; ?><tr><td><input type="checkbox" class="daily-choice" value="<?=$record['id']?>" <?=$p['hasil_array']?'checked':'disabled'?> aria-label="Pilih transaksi <?=$record['id']?>"></td><td><?=dailyH($p['no_part'])?></td><td><?=dailyH($p['nama_part'])?></td><td><?=dailyH($p['format'])?></td><td><?=dailyH($p['nomor_pengecekan'])?><br><?=dailyH($p['no_laporan'])?></td><td><?=dailyH($record['created_at'])?></td><td><?=dailyH($p['tanggal_periksa'])?></td><td><?=dailyH($record['status'])?><?=$p['hasil_array']?'':' · Rincian hasil kosong'?></td></tr><?php endforeach; ?>
</tbody></table></div>
<div class="daily-actions"><button type="button" class="btn btn-primary" id="dailyPreview">Tampilkan Laporan Gabungan</button><button type="button" class="btn btn-success" id="dailyPrint" disabled>Cetak Laporan</button><span id="dailyProgress" role="status" aria-live="polite"></span></div>
<?php endif; ?></section>
<?php endif; ?>
<div id="dailyReports"></div>
</main>
<script type="application/json" id="dailyData"><?=json_encode(['records'=>$matching,'formats'=>qaDailyFormats(),'section'=>$section,'date'=>$date],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE)?></script>
<script src="../../../js/qa_daily_report.js?v=<?=$dailyScriptVersion?>"></script>
</body></html>

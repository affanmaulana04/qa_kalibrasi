<?php
// Nama file/URL lama dipertahankan agar menu + RBAC tidak putus.
session_start();
if (empty($_SESSION['login'])) {
    header('Location: ../../logout.php');
    exit;
}
require_once __DIR__ . '/../../inc/inc_koneksi.php';
require_once __DIR__ . '/../../inc/qa_rbac.php';
require_once __DIR__ . '/../../inc/qa_seksi_akses.php';
function qaMigRequireSections($db, $data) {
    foreach ($data['master'] ?? [] as $m) {
        qaSeksiRequire($db, $m['SEKSI_PEMILIK'] ?? '');
        qaSeksiRequireNo($db, $m['NO_ALAT'] ?? '', false);
    }
}
qaRbacRequire($konek, 'upload_master');
require_once __DIR__ . '/../../inc/qa_migrasi_excel.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$konek->set_charset('utf8mb4'); // qa_part dan registry memakai latin1_swedish_ci
function mH($x) {
    return htmlspecialchars((string)$x, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
if (empty($_SESSION['qa_mig_csrf']))
    $_SESSION['qa_mig_csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['qa_mig_csrf'];
$error = '';
$success = $_SESSION['qa_mig_success'] ?? null;
unset($_SESSION['qa_mig_success']);
// Download template bersih langsung dari halaman upload (tidak membutuhkan extension ZIP).
if (isset($_GET['template']) && $_GET['template'] === 'xlsx') {
    $templatePath = __DIR__ . '/templates/template_migrasi.xlsx';
    if (!is_file($templatePath) || !is_readable($templatePath)) {
        http_response_code(404);
        echo 'Template Excel tidak ditemukan. Pastikan folder modul/tabel/templates ikut disalin.';
        exit;
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="template_migrasi.xlsx"');
    header('Content-Length: ' . filesize($templatePath));
    header('Cache-Control: private, no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');
    readfile($templatePath);
    exit;
}
if (isset($_SESSION['qa_mig_pending']) && (time() - ($_SESSION['qa_mig_pending']['at'] ?? 0) > 1800))
    unset($_SESSION['qa_mig_pending']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($csrf, (string)($_POST['csrf'] ?? '')))
            throw new RuntimeException('Sesi formulir tidak valid, refresh halaman.');
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'cancel') {
            unset($_SESSION['qa_mig_pending']);
            header('Location: upload_master_csv.php');
            exit;
        }
        qaMigEnsureTables($konek);
        if ($action === 'preview') {
            unset($_SESSION['qa_mig_pending']);
            $file = $_FILES['xlsx_file'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name']))
                throw new RuntimeException('Pilih file XLSX yang valid.');
            if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'xlsx')
                throw new RuntimeException('File wajib .xlsx (bukan .xls atau .csv).');
            if ((int)$file['size'] <= 0 || (int)$file['size'] > QA_MIG_MAX_BYTES)
                throw new RuntimeException('Ukuran XLSX maksimal 5 MB.');
            $data = qaMigParse($file['tmp_name']);
            qaMigRequireSections($konek, $data);
            $plan = qaMigPlan($konek, $data);
            $_SESSION['qa_mig_pending'] = ['at' => time(), 'data' => $data, 'plan' => $plan, 'filename' => basename($file['name']),
                'ticket' => bin2hex(random_bytes(16))];
        } elseif ($action === 'import') {
            $pending = $_SESSION['qa_mig_pending'] ?? null;
            if (!$pending || !hash_equals((string)$pending['ticket'], (string)($_POST['ticket'] ?? '')))
                throw new RuntimeException('Preview sudah kedaluwarsa. Upload ulang.');
            if ($pending['plan']['errors'])
                throw new RuntimeException('File masih memiliki kesalahan. Perbaiki Excel lalu Preview ulang.');
            $actor = trim((string)($_SESSION['userid'] ?? $_SESSION['username'] ?? 'migrasi'));
            qaMigRequireSections($konek, $pending['data']);
            $result = qaMigApply($konek, $pending['data'], $actor);
            unset($_SESSION['qa_mig_pending']);
            $_SESSION['qa_mig_success'] = $result;
            $_SESSION['qa_mig_csrf'] = bin2hex(random_bytes(32));
            header('Location: upload_master_csv.php');
            exit;
        } else
            throw new RuntimeException('Aksi tidak dikenal.');
    } catch (Throwable $e) {
        if ($e instanceof mysqli_sql_exception) {
            error_log('QA Migrasi XLSX: ' . $e->getMessage());
            $error = 'Data gagal disimpan. Hubungi admin sistem untuk pemeriksaan sebelum mencoba lagi.';
        } else
            $error = $e->getMessage();
    }
}
$pending = $_SESSION['qa_mig_pending'] ?? null;
$plan = $pending['plan'] ?? null;
?>
<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Migrasi Masterdata Excel | QA System</title>
        <link rel="icon" href="../../images/favicon-toto-qa.png?v=1">
        <link rel="stylesheet" href="../../css/bootstrap.min.css">
        <link rel="stylesheet" href="../../font-awesome-4.3.0/css/font-awesome.min.css">
        <style>
            /* Palet netral: warna hanya dipakai untuk aksi utama dan hasil validasi. */
            body {
                background: #f6f7f8;
                padding: 75px 0 35px;
                color: #30343b
            }
            .navbar-brand img {
                height: 25px;
                margin-top: -3px
            }
            .wrap {
                max-width: 1200px;
                margin: auto;
                padding: 0 16px
            }
            .panel-box {
                background: #fff;
                border: 1px solid #dedfe3;
                border-radius: 5px;
                padding: 23px;
                margin-bottom: 16px
            }
            h2 {
                margin-top: 0;
                color: #30343b;
                font-weight: 600;
                font-size: 25px
            }
            h3 {
                color: #30343b;
                font-weight: 600
            }
            h4 {
                font-weight: 600
            }
            .small-muted {
                color: #68717c
            }
            .upload-row {
                display: flex;
                gap: 12px;
                align-items: flex-end;
                flex-wrap: wrap
            }
            .upload-row .form-group {
                flex: 1;
                min-width: 270px;
                margin: 0
            }
            .stat {
                display: inline-block;
                margin: 5px 7px 5px 0;
                padding: 7px 11px;
                border-radius: 3px;
                background: #fafafa;
                border: 1px solid #e2e3e6;
                color: #474e58
            }
            .stat strong {
                color: #20252c;
                margin-left: 5px
            }
            .scroll {
                overflow: auto;
                max-height: 390px;
                border: 1px solid #e1e2e5
            }
            .scroll .table {
                margin-bottom: 0
            }
            .table td,.table th {
                vertical-align: middle!important
            }
            .table th {
                background: #f6f6f6;
                white-space: nowrap;
                color: #3e4248;
                font-weight: 600
            }
            .tag {
                display: inline-block;
                padding: 2px 7px;
                border: 1px solid #d8dade;
                border-radius: 3px;
                background: #f5f6f7;
                color: #444a52;
                font-weight: 600;
                font-size: 12px
            }
            .tag-error {
                color: #a94442;
                background: #fff;
                border-color: #ebccd1
            }
            .confirm-area {
                border-top: 1px solid #e4e5e8;
                margin-top: 20px;
                padding-top: 18px
            }
            .alert ul {
                margin-bottom: 0
            }
            .file-note {
                margin: 10px 0 0
            }
            .step {
                font-weight: 600;
                color: #4b515b
            }
            .note {
                background: #fafafa;
                border: 1px solid #e2e3e6;
                padding: 12px 14px;
                margin-top: 14px;
                color: #505761;
                border-radius: 3px
            }
            .note p:last-child {
                margin-bottom: 0
            }
            .section-heading {
                margin-top: 22px
            }
            .import-steps {
                padding-left: 21px;
                margin: 12px 0 9px;
                color: #414852
            }
            .import-steps li {
                margin-bottom: 5px
            }
            .modal-header {
                background: #fafafa;
                border-top-left-radius: 5px;
                border-top-right-radius: 5px
            }
            .modal-title {
                font-size: 17px;
                font-weight: 600
            }
            .btn+.btn {
                margin-left: 6px
            }
            @media(max-width:767px) {
                .panel-box {
                    padding: 16px
                }
                .upload-row .form-group {
                    min-width: 100%
                }
            }
        </style>
    </head>
    <body>
        <nav class="navbar navbar-default navbar-fixed-top">
            <div class="container-fluid">
                <div class="navbar-header"><a class="navbar-brand" href="../../media.php"><img src="../../images/toto.png" alt="TOTO"></a></div>
                <ul class="nav navbar-nav">
                    <li><a href="../../media.php"><i class="fa fa-arrow-left"></i> Menu QA</a></li>
                </ul>
            </div>
        </nav>
        <main class="wrap">
            <section class="panel-box">
                <h2><i class="fa fa-file-excel-o"></i> Migrasi Masterdata QA — Excel</h2>
                <p>Gunakan halaman ini untuk memasukkan data alat dan riwayat BBRAU melalui template Excel.</p>
<?php if ($error):?>
<div class="alert alert-danger alert-dismissible" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
<strong>Proses gagal.</strong> <?= mH($error)?>
</div>
<?php endif;?>
<?php if ($success):?>
<div class="alert alert-success alert-dismissible" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
<strong>Import berhasil.</strong> <?= intval($success['seksi_baru'] ?? 0)?> seksi baru, <?= intval($success['alat_baru'])?> alat baru, <?= intval($success['alat_dilewati'])?> alat existing dilewati, <?= intval($success['kalibrator_diperbarui'] ?? 0)?> kalibrator diperbarui, <?= intval($success['jenis_diperbarui'] ?? 0)?> jenis pemeriksaan diperbarui, <?= intval($success['riwayat_baru'])?> riwayat baru, <?= intval($success['riwayat_dilewati'])?> riwayat duplikat dilewati.
</div>
<?php endif;?>
<p><a href="upload_master_csv.php?template=xlsx" class="btn btn-success"><i class="fa fa-download"></i> Download Template Migrasi (.xlsx)</a></p>
                <ol class="import-steps">
                    <li>Download template Excel lalu isi sheet <strong>MASTER_ALAT</strong>. Kolom KALIBRATOR dan JENIS_PEMERIKSAAN boleh kosong; isi KALIBRASI atau VERIFIKASI bila diperlukan.</li>
                    <li>Isi sheet <strong>RIWAYAT_BBRAU</strong> jika ada riwayat lama. Boleh dikosongkan.</li>
                    <li>Upload file, klik <strong>Preview &amp; Validasi</strong>, lalu periksa hasilnya sebelum disimpan.</li>
                </ol>
                <p class="small-muted">Panduan pengisian lengkap tersedia pada sheet <strong>REFERENSI_FORMAT</strong> di template Excel.</p>
                <p class="step">Pilih file Excel untuk di-preview</p>
                <form class="upload-row" method="post" enctype="multipart/form-data" action="upload_master_csv.php">
<input type="hidden" name="csrf" value="<?= mH($csrf)?>"><input type="hidden" name="action" value="preview">
<div class="form-group"><label for="fileXlsx">File Template Migrasi (.xlsx)</label><input class="form-control" id="fileXlsx" type="file" name="xlsx_file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required></div>
<button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Preview &amp; Validasi</button></form>
                <p class="small-muted file-note">File harus berformat <strong>.xlsx</strong>, maksimal 5 MB.</p>
            </section>
<?php if ($pending && $plan):?>
<section class="panel-box" id="preview">
                <h3 style="margin-top:0"><i class="fa fa-list-alt"></i> Tahap 2 — Preview: <?= mH($pending['filename'])?></h3>
                <div aria-label="Ringkasan preview"><span class="stat">Seksi baru <strong><?= intval($plan['counts']['seksi_baru'] ?? 0)?></strong></span><span class="stat">Alat baru <strong><?= intval($plan['counts']['master_baru'])?></strong></span><span class="stat">Alat sudah ada <strong><?= intval($plan['counts']['master_ada'])?></strong></span><span class="stat">Kalibrator diperbarui <strong><?= intval($plan['counts']['kalibrator_update'] ?? 0)?></strong></span><span class="stat">Jenis diperbarui <strong><?= intval($plan['counts']['jenis_update'] ?? 0)?></strong></span><span class="stat">Riwayat baru <strong><?= intval($plan['counts']['hist_baru'])?></strong></span><span class="stat">Riwayat sudah ada <strong><?= intval($plan['counts']['hist_ada'])?></strong></span><span class="stat">Error <strong><?= count($plan['errors'])?></strong></span></div>
<?php if ($plan['errors']):?><div class="alert alert-danger"><strong><?= count($plan['errors'])?> masalah ditemukan; data BELUM disimpan.</strong><ul><?php foreach (array_slice($plan['errors'], 0, 100) as $e):?><li><?= mH($e)?></li><?php endforeach;?></ul><?php if (count($plan['errors']) > 100):?><p>Masih ada error lain. Periksa template.</p><?php endif;?></div><?php else:?><div class="note" role="status"><strong>Validasi selesai.</strong> Data siap di-import. Untuk alat existing, hanya KALIBRATOR dan JENIS_PEMERIKSAAN yang diisi di Excel dapat diperbarui; data lainnya tetap.</div><?php endif;?>
<?php if (!empty($plan['seksi_baru'])):?><div class="note"><strong>Seksi baru akan ditambahkan saat import:</strong> <?= mH(implode(', ', $plan['seksi_baru']))?>.</div><?php endif;?>
<h4 class="section-heading">MASTER_ALAT</h4>
                <div class="scroll">
                    <table class="table table-bordered table-condensed">
                        <thead>
                            <tr>
                                <th>Baris</th>
                                <th>No. Alat</th>
                                <th>Nama Alat</th>
                                <th>Format</th>
                                <th>Kalibrator</th>
                                <th>Jenis</th>
                                <th>Status</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
<?php foreach (array_slice($plan['master'], 0, 100) as $item):?><tr>
                                <td><?= intval($item['row'])?></td>
                                <td><?= mH($item['no'])?></td>
                                <td><?= mH($item['nama'])?></td>
                                <td><?= mH($item['format'])?></td>
                                <td><?= mH($item['kalibrator'] ?? '')?></td>
                                <td><?= mH($item['jenis_pemeriksaan'] ?? 'KALIBRASI')?></td>
                                <td><span class="tag <?= $item['status'] === 'ERROR'?'tag-error':'' ?>"><?= mH(['KALIBRATOR_UPDATE' => 'UPDATE KALIBRATOR', 'JENIS_UPDATE' => 'UPDATE JENIS', 'MASTER_UPDATE' => 'UPDATE DATA'][$item['status']] ?? $item['status'])?></span></td>
                                <td><?= mH(implode('; ', $item['errors']))?></td>
                            </tr><?php endforeach;?></tbody>
                    </table>
                </div>
<?php if ($plan['history']):?><h4 class="section-heading">RIWAYAT_BBRAU</h4>
                <div class="scroll">
                    <table class="table table-bordered table-condensed">
                        <thead>
                            <tr>
                                <th>Baris</th>
                                <th>No. Alat</th>
                                <th>Status</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody><?php foreach (array_slice($plan['history'], 0, 100) as $item):?><tr>
                                <td><?= mH($item['row'])?></td>
                                <td><?= mH($item['no'])?></td>
                                <td><?= mH($item['status'])?></td>
                                <td><?= mH(implode('; ', $item['errors']))?></td>
                            </tr><?php endforeach;?></tbody>
                    </table>
                </div><?php endif;?>
<div class="confirm-area">
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#konfirmasiImportModal" <?= $plan['errors']?'disabled':'' ?>><i class="fa fa-database"></i> Konfirmasi &amp; Simpan Import</button>
<form method="post" style="display:inline;margin-left:8px"><input type="hidden" name="csrf" value="<?= mH($csrf)?>"><input type="hidden" name="action" value="cancel"><button type="submit" class="btn btn-default">Batalkan Preview</button></form>
                </div>
            </section>
<?php endif;?>

</main>
<?php if ($pending && $plan && !$plan['errors']):?>
<!-- Konfirmasi Bootstrap Modal, bukan confirm() browser. -->
<div class="modal fade" id="konfirmasiImportModal" tabindex="-1" role="dialog" aria-labelledby="konfirmasiImportLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button><h4 class="modal-title" id="konfirmasiImportLabel">Konfirmasi Import Masterdata QA</h4>
                    </div>
                    <div class="modal-body">
                        <p>Data dari <strong><?= mH($pending['filename'])?></strong> akan disimpan ke sistem QA.</p>
                        <p>Seksi baru: <strong><?= intval($plan['counts']['seksi_baru'] ?? 0)?></strong> &nbsp;|&nbsp; Alat baru: <strong><?= intval($plan['counts']['master_baru'])?></strong> &nbsp;|&nbsp; Kalibrator diperbarui: <strong><?= intval($plan['counts']['kalibrator_update'] ?? 0)?></strong> &nbsp;|&nbsp; Jenis diperbarui: <strong><?= intval($plan['counts']['jenis_update'] ?? 0)?></strong> &nbsp;|&nbsp; Riwayat baru: <strong><?= intval($plan['counts']['hist_baru'])?></strong></p>
                        <p class="small-muted">Pastikan hasil preview sudah sesuai sebelum melanjutkan.</p>
                    </div>
                    <div class="modal-footer">
<button type="button" class="btn btn-default" data-dismiss="modal">Kembali</button>
<form id="formConfirmImport" method="post" action="upload_master_csv.php" style="display:inline">
<input type="hidden" name="csrf" value="<?= mH($csrf)?>">
<input type="hidden" name="action" value="import">
<input type="hidden" name="ticket" value="<?= mH($pending['ticket'])?>">
<button type="submit" class="btn btn-primary" id="btnProsesImport"><i class="fa fa-check"></i> Ya, Simpan Import</button>
</form>
                    </div>
                </div>
            </div>
        </div>
<?php endif;?>
<?php if ($success || ($error && ($_POST['action'] ?? '') === 'import')):?>
<!-- Notifikasi hasil import Bootstrap Modal. Data pesan telah di-escape server-side. -->
<div class="modal fade" id="hasilImportModal" tabindex="-1" role="dialog" aria-labelledby="hasilImportLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
<h4 class="modal-title" id="hasilImportLabel"><?= $success ? 'Import Berhasil' : 'Import Gagal' ?></h4>
                    </div>
                    <div class="modal-body">
<?php if ($success):?>
<p>Data migrasi berhasil diproses dan disimpan.</p>
                        <table class="table table-condensed">
                            <tbody>
                                <tr>
                                    <td>Seksi baru</td>
                                    <td class="text-right"><strong><?= intval($success['seksi_baru'] ?? 0)?></strong></td>
                                </tr>
                                <tr>
                                    <td>Alat baru</td>
                                    <td class="text-right"><strong><?= intval($success['alat_baru'])?></strong></td>
                                </tr>
                                <tr>
                                    <td>Alat existing dilewati</td>
                                    <td class="text-right"><strong><?= intval($success['alat_dilewati'])?></strong></td>
                                </tr>
                                <tr>
                                    <td>Kalibrator diperbarui</td>
                                    <td class="text-right"><strong><?= intval($success['kalibrator_diperbarui'] ?? 0)?></strong></td>
                                </tr>
                                <tr>
                                    <td>Jenis pemeriksaan diperbarui</td>
                                    <td class="text-right"><strong><?= intval($success['jenis_diperbarui'] ?? 0)?></strong></td>
                                </tr>
                                <tr>
                                    <td>Riwayat baru</td>
                                    <td class="text-right"><strong><?= intval($success['riwayat_baru'])?></strong></td>
                                </tr>
                                <tr>
                                    <td>Riwayat duplikat dilewati</td>
                                    <td class="text-right"><strong><?= intval($success['riwayat_dilewati'])?></strong></td>
                                </tr>
                            </tbody>
                        </table>
<?php else:?>
<p class="text-danger"><strong>Import tidak berhasil diselesaikan.</strong></p>
                        <p><?= mH($error)?></p>
<?php endif;?>
</div>
                    <div class="modal-footer"><button type="button" class="btn btn-primary" data-dismiss="modal">Tutup</button></div>
                </div>
            </div>
        </div>
<?php endif;?>
<script src="../../js/jquery.js"></script>
        <script src="../../js/bootstrap.min.js"></script>
        <script>
$(function(){
    $('#formConfirmImport').on('submit',function(){
        $('#btnProsesImport').prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
    });
    <?php if ($success || ($error && ($_POST['action'] ?? '') === 'import')):?>
    $('#hasilImportModal').modal('show');
    <?php endif;?>
});
</script>
    </body>
</html>

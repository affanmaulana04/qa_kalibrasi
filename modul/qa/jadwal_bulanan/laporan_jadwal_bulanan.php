<?php
// Reuse the monthly R/H calculation and table from Master Data.
session_start();
if (empty($_SESSION['login'])) {
    header('Location: ../../../logout.php');
    exit;
}

require_once __DIR__.'/../../../inc/inc_koneksi.php';
require_once __DIR__.'/../../../inc/qa_rbac.php';
$monthlyPermissions = ['jadwal_bulanan', 'jadwal_bulanan_internal', 'jadwal_bulanan_external'];
qaRbacRequireAny($konek, $monthlyPermissions);
$monthlyAvailableFormats = [];
foreach (['1','2','3'] as $formatCode) {
    if (qaRbacCan($konek, qaRbacMonthlyPermission($formatCode))) $monthlyAvailableFormats[] = $formatCode;
}
$monthlyDefaultFormat = $monthlyAvailableFormats[0];

$now = new DateTimeImmutable('now', new DateTimeZone('Asia/Bangkok'));
$monthlyReportOptions = [
    'seksi' => 'ALL',
    'bulan' => (int)$now->format('n'),
    'tahun' => (int)$now->format('Y'),
    'cetak' => isset($_GET['cetak']) && $_GET['cetak'] === '1',
    'format' => $monthlyDefaultFormat,
    'errors' => [],
];
if (isset($_GET['format'])) {
    if (in_array((string)$_GET['format'], ['1','2','3'], true)) {
        $monthlyReportOptions['format'] = (string)$_GET['format'];
        qaRbacRequire($konek, qaRbacMonthlyPermission($monthlyReportOptions['format']));
    } else {
        $monthlyReportOptions['errors'][] = 'Pilih Format 1, Internal, atau External.';
    }
}
foreach (['bulan' => [1, 12], 'tahun' => [1900, 9999]] as $name => $range) {
    if (!isset($_GET[$name])) continue;
    $value = is_string($_GET[$name]) ? filter_var($_GET[$name], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => $range[0], 'max_range' => $range[1]]
    ]) : false;
    if ($value === false) {
        $monthlyReportOptions['errors'][] = $name === 'bulan'
            ? 'Pilih bulan antara Januari dan Desember.'
            : 'Isi tahun antara 1900 dan 9999.';
    } else {
        $monthlyReportOptions[$name] = $value;
    }
}
if (isset($_GET['seksi'])) {
    if (!is_string($_GET['seksi']) || strlen($_GET['seksi']) > 150 || trim($_GET['seksi']) === '') {
        $monthlyReportOptions['errors'][] = 'Pilih seksi yang tersedia.';
    } else {
        $monthlyReportOptions['seksi'] = trim($_GET['seksi']);
    }
}
require __DIR__ . '/../masterdata/masterdata.php';

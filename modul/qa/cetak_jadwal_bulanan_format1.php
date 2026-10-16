<?php
// Render only through the authenticated, filtered monthly report controller.
if (empty($isMonthlyReport) || !isset($monthlyPlanRows, $monthlyReportOptions)) {
    http_response_code(404);
    exit;
}
$backUrl = 'laporan_jadwal_bulanan.php?'.http_build_query([
    'seksi'=>$filter_seksi, 'bulan'=>$selectedMonth, 'tahun'=>$selectedYear
]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jadwal Kalibrasi <?=h($monthlyTitle)?> — Format 1</title>
    <link rel="icon" type="image/png" href="../../images/favicon-toto-qa.png?v=1">
    <style>
        *{box-sizing:border-box}
        body{margin:0;background:#eef1f4;color:#000;font-family:Arial,sans-serif}
        .toolbar{max-width:210mm;margin:20px auto 12px;padding:14px 18px;display:flex;flex-wrap:wrap;align-items:center;gap:12px;background:#fff;border:1px solid #d7dce1;border-radius:6px}
        .toolbar strong{flex:1}
        .toolbar a,.toolbar button{padding:9px 14px;border:1px solid #bbb;border-radius:4px;background:#fff;color:#222;text-decoration:none;font:14px Arial;cursor:pointer}
        .toolbar button{background:#337ab7;border-color:#2e6da4;color:#fff}
        .toolbar button:disabled{opacity:.5;cursor:not-allowed}
        .paper{max-width:210mm;min-height:297mm;margin:0 auto 24px;padding:12mm;background:#fff}
        h1{font-size:16px;text-align:center;margin:0 0 8px}
        .period{text-align:center;margin:0 0 20px;font-size:14px}
        .section-table{width:100%;border-collapse:collapse;table-layout:fixed;margin-bottom:18px;font-size:12px}
        .section-table th,.section-table td{border:1px solid #000;padding:8px 6px;vertical-align:middle;overflow-wrap:anywhere}
        .section-table th{text-align:center;font-weight:normal;height:48px}
        .section-table .section-label{text-align:left;font-weight:normal;height:auto;padding:6px}
        .number,.planned-date{text-align:center}
        .planned-date{font-weight:bold;-webkit-print-color-adjust:exact;print-color-adjust:exact}
        .planned-date.status-overdue{background:#f8d7da;color:#842029}
        .planned-date.status-today{background:#cfe2ff;color:#084298}
        .planned-date.status-scheduled{background:#d1e7dd;color:#0f5132}
        .notice{padding:12px;border:1px solid #bbb;line-height:1.5}
        .error{color:#a94442;border-color:#a94442}
        @page{size:A4 portrait;margin:12mm}
        @media(max-width:600px){.toolbar{margin:12px}.paper{padding:18px 12px;min-height:0}.section-table{font-size:11px}.section-table th,.section-table td{padding:6px 4px}}
        @media print{
            body{background:#fff}
            .toolbar{display:none!important}
            .paper{width:100%;max-width:none;min-height:0;padding:0;margin:0}
            h1{font-size:12pt}
            .section-table{font-size:10pt}
            .section-table thead{display:table-header-group}
            .section-table tr{break-inside:avoid;page-break-inside:avoid}
            .section-table th{height:14mm}
            .section-table .section-label{height:auto}
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <strong>Pratinjau Format 1</strong>
        <a href="<?=h($backUrl)?>">Kembali ke Laporan</a>
        <button type="button" onclick="window.print()" <?=!$monthlyPlanRows?'disabled':''?>>Cetak</button>
    </div>
    <main class="paper">
        <h1>JADWAL KALIBRASI BULANAN</h1>
        <p class="period"><?=h($monthlyTitle)?></p>
        <?php require __DIR__ . '/jadwal_bulanan_format1_tabel.php'; ?>
    </main>
</body>
</html>

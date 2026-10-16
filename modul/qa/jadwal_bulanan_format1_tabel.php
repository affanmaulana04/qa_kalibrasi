<?php
if (empty($isMonthlyReport) || !isset($monthlyPlanRows, $monthlyReportOptions)) {
    http_response_code(404);
    exit;
}
$sections = [];
foreach ($monthlyPlanRows as $row) {
    $key = strtoupper(trim($row['seksi']));
    if (!isset($sections[$key])) $sections[$key] = ['label'=>$row['seksi'], 'rows'=>[]];
    $sections[$key]['rows'][] = $row;
}
?>
        <?php if ($monthlyReportOptions['errors']): ?>
        <p class="notice error" role="alert"><?=h(implode(' ', $monthlyReportOptions['errors']))?></p>
        <?php elseif (!$sections): ?>
        <p class="notice">Tidak ada rencana kalibrasi untuk <?=h($filter_seksi==='ALL'?'semua seksi':'seksi '.$filter_seksi)?> pada periode ini.</p>
        <?php else: $number=0; foreach ($sections as $section): ?>
        <table class="section-table">
            <colgroup><col style="width:9%"><col style="width:34%"><col style="width:25%"><col style="width:32%"></colgroup>
            <thead>
                <tr><th scope="col">NO</th><th scope="col">NAMA ALAT</th><th scope="col">NO. ALAT</th><th scope="col">RENCANA KALIBRASI</th></tr>
                <tr><th colspan="4" class="section-label">SEKSI: <?=h($section['label']?:'-')?></th></tr>
            </thead>
            <tbody>
                <?php foreach ($section['rows'] as $row): $number++;
                    $dateClass = $row['plan_date'] < $today ? 'status-overdue' : ($row['plan_date'] === $today ? 'status-today' : 'status-scheduled');
                    $dateLabel = $row['plan_date'] < $today ? 'Jadwal terlewat' : ($row['plan_date'] === $today ? 'Jadwal hari ini' : 'Terjadwal');
                ?>
                <tr>
                    <td class="number"><?=$number?></td>
                    <td><?=h($row['nama_part'])?></td>
                    <td><?=h($row['no_part'])?></td>
                    <td class="planned-date <?=$dateClass?>" title="<?=$dateLabel?>"><?=h(substr($row['plan_date'],8,2).'-'.substr($row['plan_date'],5,2).'-'.substr($row['plan_date'],0,4))?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endforeach; endif; ?>


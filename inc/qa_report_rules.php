<?php
// Presentation keys only; original migrated names in the database are preserved.
function qaSectionKey($value) {
    $value = (string)$value;
    // Spasi Unicode dan karakter tanpa lebar dari Excel/hasil copy-paste.
    // Proses sebagai byte agar tetap bekerja pada koneksi database latin1.
    $spaces = ["\xc2\xa0", "\xa0", "\xe1\x9a\x80", "\xe2\x80\xaf", "\xe2\x81\x9f", "\xe3\x80\x80"];
    for ($b = 0x80;$b <= 0x8a;$b++)
        $spaces[] = "\xe2\x80" . chr($b);
    $value = str_replace($spaces, ' ', $value);
    $invisible = ["\xc2\xad", "\xad", "\xe2\x81\xa0", "\xef\xbb\xbf"];
    for ($b = 0x8b;$b <= 0x8f;$b++)
        $invisible[] = "\xe2\x80" . chr($b);
    for ($b = 0xaa;$b <= 0xae;$b++)
        $invisible[] = "\xe2\x80" . chr($b);
    for ($b = 0xa6;$b <= 0xa9;$b++)
        $invisible[] = "\xe2\x81" . chr($b);
    $value = str_replace($invisible, '', $value);
    return strtoupper(trim(preg_replace('/\s+/', ' ', $value)));
}
function qaMasterResultLabel($status, $masterType, $approvedType = '') {
    if (strtoupper(trim((string)$status)) !== 'OK')
        return $status;
    $type = strtolower(trim((string)$approvedType));
    if ($type === '')
        $type = strtolower(trim((string)$masterType));
    return ['kalibrasi' => 'O', 'verifikasi' => 'V'][$type] ?? 'OK';
}
function qaMonthlyCounts($plans, $actuals, $month, $days, $section = 'ALL', $status = 'ALL', $consumePlans = false) {
    $groups = [];
    $seen = [];
    $completed = [];
    if ($consumePlans)
        foreach ($actuals as $actual) {
        $due = substr((string)($actual['plan_date'] ?? ''), 0, 10);
        $no = strtoupper(trim((string)($actual['no_part'] ?? '')));
        if ($no !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $due))
            $completed[$no . '|' . $due] = true;
    }
    foreach (['R' => $plans, 'H' => $actuals] as $kind => $events)
        foreach ($events as $e) {
        $date = substr((string)($e[$kind === 'R'?'plan_date':'actual_date'] ?? ''), 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || substr($date, 0, 7) !== $month)
            continue;
        $day = (int)substr($date, 8, 2);
        if ($day < 1 || $day > $days)
            continue;
        $sec = qaSectionKey($e['seksi'] ?? '');
        if ($section !== 'ALL' && $sec !== qaSectionKey($section))
            continue;
        if ($status !== 'ALL' && strtoupper($e['status'] ?? '') !== strtoupper($status))
            continue;
        $key = $kind . '|' . strtoupper(trim($e['no_part'] ?? '')) . '|' . $date;
        if (isset($seen[$key]))
            continue;
        $seen[$key] = true;
        if ($kind === 'R' && isset($completed[strtoupper(trim((string)($e['no_part'] ?? ''))) . '|' . $date]))
            continue;
        $gk = $sec . '|' . qaSectionKey($e['nama_part'] ?? '');
        if (!isset($groups[$gk]))
            $groups[$gk] = ['seksi' => $sec, 'nama_part' => $e['nama_part'] ?? '', 'petugas' => [], 'R' => array_fill(1, $days, 0), 'H' => array_fill(1, $days, 0)];
        $groups[$gk][$kind][$day]++;
        if ($kind === 'H' && !empty($e['petugas']))
            $groups[$gk]['petugas'][] = $e['petugas'];
    }
    foreach ($groups as &$g) {
        $g['totalR'] = array_sum($g['R']);
        $g['totalH'] = array_sum($g['H']);
        $g['petugas'] = implode(', ', array_unique($g['petugas']));
    }
    unset($g);
    uasort($groups, function ($a, $b) {
        return [$a['seksi'], strtoupper($a['nama_part'])] <=> [$b['seksi'], strtoupper($b['nama_part'])];
    });
    return $groups;
}

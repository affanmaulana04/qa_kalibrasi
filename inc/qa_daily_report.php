<?php
// Read-only report queries; historical snapshots take precedence over master data.
function qaDailyFormats(): array {
    return ['A'=>'Gauge (GO / NOT GO)','H'=>'Thread Plug Gauge','I'=>'Gauge ATAS / BAWAH',
        'J'=>'Verifikasi Radius Gauge','O'=>'Snap Gauge','P'=>'SK Gauge','Q'=>'Seration / Plug','T'=>'GO / NOT GO + Toleransi'];
}
function qaDailyQuery(mysqli $db, string $sql, array $params = []): array {
    $stmt = $db->prepare($sql);
    try {
        if ($params) $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } finally { $stmt->close(); }
}
function qaDailyDate(string $value): bool {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value && (int)$date->format('Y') >= 1000 && (int)$date->format('Y') < 9999;
}
function qaDailyStatus(string $value): string {
    return ['menunggu_pemeriksa'=>'Menunggu pemeriksa','menunggu_penyetuju'=>'Menunggu penyetuju',
        'final_disetujui'=>'Final disetujui','ditolak_pemeriksa'=>'Ditolak pemeriksa',
        'ditolak_penyetuju'=>'Ditolak penyetuju','setuju'=>'Disetujui'][$value] ?? ($value ?: 'Belum ada status');
}
function qaDailyPayload(array $row, array $master, array $audit): array {
    $raw = json_decode($row['hasil_json'] ?? '', true);
    $raw = is_array($raw) ? $raw : [];
    $h = is_array($raw['header'] ?? null) ? $raw['header'] : [];
    $env = is_array($raw['environment'] ?? null) ? $raw['environment'] : [];
    $footer = is_array($raw['footer'] ?? null) ? $raw['footer'] : [];
    $rows = $raw['rows'] ?? $raw['hasil_array'] ?? (array_is_list($raw) ? $raw : []);
    $payload = [];
    foreach (['nama_part','seksi_pemilik','spesifikasi','resolusi','metode_uji','standar_uji','traceability',
        'satuan','toleransi_global','lokasi','type','titik_standar_json'] as $key) {
        $payload[$key] = $h[$key] ?? $master[$key] ?? '';
    }
    $payload['format'] = strtoupper(trim((string)($raw['format'] ?? $h['format_laporan'] ?? $master['format_laporan'] ?? '')));
    $payload['format_laporan'] = $payload['format'];
    $payload['hasil_array'] = is_array($rows) ? array_values($rows) : [];
    $payload['no_part'] = $row['no_part'];
    $payload['no_alat'] = $row['no_part'];
    $payload['nama_alat'] = $payload['nama_part'];
    $payload['seksi'] = $payload['seksi_pemilik'];
    $payload['metode'] = $payload['metode_uji'];
    $payload['toleransi'] = $payload['toleransi_global'];
    $payload['tanggal_periksa'] = $row['tanggal_pengecekan'];
    $payload['no_laporan'] = $row['no_laporan'] ?: ($h['no_laporan'] ?? $row['nomor_pengecekan']);
    $payload['nomor_pengecekan'] = $row['nomor_pengecekan'];
    foreach (['suhu','kelembapan','ketidakpastian','confidence_level'] as $key) $payload[$key] = $row[$key] ?? $env[$key] ?? '';
    $payload['keterangan'] = $row['keterangan'] ?? $footer['keterangan'] ?? '';
    $payload['operator'] = $row['operator'] ?: ($footer['operator'] ?? $row['created_by']);
    $payload['petugas'] = $payload['operator'];
    $result = strtoupper(trim((string)$row['status_kalibrasi']));
    $payload['status_final'] = $payload['keputusan'] = ['GOOD'=>'OK','BAD'=>'NOT GOOD','NG'=>'NOT GOOD','BURUK'=>'NOT GOOD'][$result] ?? $result;
    $payload['jadwal_berikutnya'] = $payload['keputusan'] === 'OK' ? $row['periode_berikutnya'] : '';
    $payload['status_validasi'] = $row['status_validasi'];
    $payload['signature_pembuat'] = true;
    foreach (['pemeriksa','penyetuju'] as $stage) {
        $latest = null;
        foreach ($audit as $event) if ($event['tahap'] === $stage) $latest = $event;
        $approved = $latest && $latest['keputusan'] === 'setuju';
        $payload['signature_'.$stage] = (bool)$approved;
        $payload[$stage] = $approved ? $latest['nama_validator'] : '';
        $payload['tanggal_validasi_'.$stage] = $approved ? $latest['created_at'] : '';
    }
    $payload['foreman'] = $payload['pemeriksa'];
    $payload['supervisor'] = $payload['penyetuju'];
    $payload['audit'] = $audit;
    return $payload;
}
function qaDailyRecords(mysqli $db, string $date): array {
    if (!qaDailyDate($date)) throw new InvalidArgumentException('Tanggal kalibrasi tidak valid.');
    $end = (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d');
    $rows = qaDailyQuery($db, "SELECT * FROM qa_pengecekan WHERE tanggal_pengecekan >= ? AND tanggal_pengecekan < ?
        AND COALESCE(jenis_transaksi,'update_hasil_kalibrasi') <> 'registrasi_alat'
        AND (nomor_pengecekan IS NULL OR nomor_pengecekan NOT LIKE 'QA-REG-%') ORDER BY created_at,id", [$date,$end]);
    if (!$rows) return [];
    $numbers = array_values(array_unique(array_column($rows,'no_part')));
    $registryIds = array_values(array_unique(array_filter(array_column($rows,'alat_registry_id'))));
    $slots = implode(',',array_fill(0,count($numbers),'?'));
    $where = "no_part IN ($slots)";
    $params = $numbers;
    if ($registryIds) {
        $where .= ' OR alat_registry_id IN ('.implode(',',array_fill(0,count($registryIds),'?')).')';
        $params = array_merge($params,$registryIds);
    }
    $byRegistry = []; $byNumber = [];
    foreach (['qa_part','qa_alat_ng','qa_alat_rusak','qa_alat_discontinue','qa_alat_ditarik'] as $table) {
        foreach (qaDailyQuery($db,"SELECT * FROM `$table` WHERE $where ORDER BY id DESC",$params) as $master) {
            $key = strtoupper(trim($master['no_part']));
            if (!isset($byNumber[$key])) $byNumber[$key] = $master;
            if (!empty($master['alat_registry_id']) && !isset($byRegistry[$master['alat_registry_id']])) $byRegistry[$master['alat_registry_id']] = $master;
        }
    }
    $audit = [];
    foreach (array_chunk(array_column($rows,'id'),500) as $ids) {
        $slots = implode(',',array_fill(0,count($ids),'?'));
        foreach (qaDailyQuery($db,"SELECT * FROM qa_validasi WHERE pengecekan_id IN ($slots) ORDER BY id",$ids) as $event) $audit[$event['pengecekan_id']][] = $event;
    }
    $records = [];
    foreach ($rows as $row) {
        $master = $byRegistry[$row['alat_registry_id'] ?? ''] ?? $byNumber[strtoupper(trim($row['no_part']))] ?? [];
        $payload = qaDailyPayload($row,$master,$audit[$row['id']] ?? []);
        if (!isset(qaDailyFormats()[$payload['format']])) continue;
        $record = ['id'=>(int)$row['id'],'created_at'=>$row['created_at'],'status'=>qaDailyStatus($row['status_validasi'] ?? ''),'payload'=>$payload];
        $record['revision'] = hash('sha256',serialize($record));
        $records[] = $record;
    }
    return $records;
}

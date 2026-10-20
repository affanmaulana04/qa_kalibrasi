<?php
// CSV import helpers. No session, output, migrations, or writes on include.
const QA_CSV_MAX_BYTES = 2097152;
const QA_CSV_MAX_ROWS = 1000;

function qaCsvFields(): array {
    return [
        'no_part'=>'No. Alat', 'nama_part'=>'Nama Alat', 'seksi_pemilik'=>'Seksi',
        'periode_kalibrasi'=>'Periode Kalibrasi (hari)', 'jadwal_kalibrasi'=>'Jadwal Kalibrasi',
        'spesifikasi'=>'Spesifikasi', 'ukuran'=>'Ukuran', 'resolusi'=>'Resolusi',
        'merk'=>'Merk', 'type'=>'Tipe', 'no_seri'=>'No. Seri', 'no_fa'=>'No. Aset',
        'lokasi'=>'Lokasi', 'format_laporan'=>'Format Laporan',
        'satuan'=>'Satuan', 'toleransi_global'=>'Toleransi', 'metode_uji'=>'Metode Uji',
        'standar_uji'=>'Standar Uji', 'traceability'=>'Traceability',
        'kls'=>'Kelas', 'fungsi'=>'Fungsi', 'buatan'=>'Buatan', 'keterangan'=>'Keterangan',
        'kalibrator'=>'Kalibrator'
    ];
}
function qaCsvRequired(): array {
    return ['no_part','nama_part','seksi_pemilik','periode_kalibrasi','jadwal_kalibrasi'];
}
function qaCsvTemplateHeaders(): array {
    return ['NAMA ALAT','SPESIFIKASI','NO. ALAT','RESOLUSI','PERIODE KALIBRASI (HARI)','RENCANA KALIBRASI','SEKSI'];
}
function qaCsvTemplateExamples(): array {
    return [
        ['SK gauge','SK-571','CONTOH-001','~','365','16 - 10 - 2026','QA'],
        ['Jangka sorong','0–150 mm','CONTOH-002','0.01 mm','180','17 - 10 - 2026','INJ'],
        ['Micrometer','0–25 mm','CONTOH-003','0.001 mm','365','18 - 10 - 2026','TECH'],
    ];
}
function qaCsvSectionRow(array $cells): ?string {
    if (!preg_match('/^SEKSI\s*:\s*(.*)$/iu', trim((string)($cells[0] ?? '')), $match)) return null;
    foreach (array_slice($cells, 1) as $cell) {
        if (trim((string)$cell) !== '') throw new RuntimeException('Isi seksi pada sel pertama setelah SEKSI:, lalu kosongkan sel lainnya di baris tersebut.');
    }
    return trim($match[1]);
}
function qaCsvPeriod(string $value): ?string {
    if (!preg_match('/^([0-9]+)\s*(hari|tahun)?$/i', trim($value), $match)) return null;
    $days = (float)$match[1] * (strtolower($match[2] ?? '') === 'tahun' ? 365 : 1);
    return $days >= 1 && $days <= 2147483647 ? (string)(int)$days : null;
}
function qaCsvFetch(mysqli $db, string $sql, array $params = []): array {
    $stmt = $db->prepare($sql);
    try {
        if ($params) $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } finally { $stmt->close(); }
}
function qaCsvWrite(mysqli $db, string $sql, array $params): void {
    $stmt = $db->prepare($sql);
    try {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
    } finally { $stmt->close(); }
}
function qaCsvSchema(mysqli $db): array {
    $schema = [];
    foreach (qaCsvFetch($db, 'SHOW FULL COLUMNS FROM qa_part') as $col) $schema[$col['Field']] = $col;
    foreach (array_merge(qaCsvRequired(), ['id','alat_registry_id','status']) as $name) {
        if (!isset($schema[$name])) throw new RuntimeException('Struktur master alat belum lengkap: '.$name.'.');
    }
    $engines = qaCsvFetch($db, "SHOW TABLE STATUS WHERE Name IN ('qa_part','qa_alat_registry')");
    if (count($engines) !== 2) throw new RuntimeException('Tabel master atau registry alat belum tersedia.');
    foreach ($engines as $table) {
        if (strcasecmp($table['Engine'], 'MyISAM') !== 0) {
            throw new RuntimeException('Import CSV membutuhkan qa_part dan qa_alat_registry berjenis MyISAM sesuai database QA.');
        }
    }
    return $schema;
}
function qaCsvHeader($header): string {
    $header = (string)$header;
    $key = trim(strtolower((string)preg_replace('/[^a-zA-Z0-9]+/', '_', trim($header))), '_');
    $aliases = ['no_alat'=>'no_part','nomor_alat'=>'no_part','nama_alat'=>'nama_part',
        'seksi'=>'seksi_pemilik','periode_hari'=>'periode_kalibrasi','rencana_kalibrasi'=>'jadwal_kalibrasi',
        'no_asset'=>'no_fa','no_aset'=>'no_fa','tipe'=>'type',
        'periode_kalibrasi_hari'=>'periode_kalibrasi'];
    return $aliases[$key] ?? $key;
}
function qaCsvDate(string $value): ?string {
    $value = preg_replace('/\s*([-\/])\s*/', '$1', trim($value));
    foreach (['Y-m-d','d-m-Y','d/m/Y'] as $format) {
        $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
        if ($date && $date->format($format) === $value && (int)$date->format('Y') >= 1000) return $date->format('Y-m-d');
    }
    return null;
}
function qaCsvCheckQuotes(string $text, string $delimiter): void {
    $state = 'start';
    for ($i=0, $length=strlen($text); $i<$length; $i++) {
        $char = $text[$i];
        if ($state === 'quoted') {
            if ($char === '"') {
                if ($i+1<$length && $text[$i+1] === '"') $i++;
                else $state = 'closed';
            }
            continue;
        }
        if ($char === $delimiter || $char === "\r" || $char === "\n") { $state = 'start'; continue; }
        if (($state === 'start' || $state === 'closed') && ($char === ' ' || $char === "\t")) continue;
        if ($state === 'start' && $char === '"') { $state = 'quoted'; continue; }
        if ($state === 'closed' || $char === '"') throw new RuntimeException('Tanda kutip CSV tidak valid. Ekspor ulang sebagai CSV UTF-8.');
        $state = 'unquoted';
    }
    if ($state === 'quoted') throw new RuntimeException('Ada tanda kutip CSV yang belum ditutup.');
}
function qaCsvParse(string $text, array $schema): array {
    if (strlen($text) > QA_CSV_MAX_BYTES) throw new RuntimeException('Ukuran CSV maksimal 2 MB.');
    $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
    if (!preg_match('//u', $text) || strpos($text, "\0") !== false) throw new RuntimeException('Simpan file sebagai CSV UTF-8, lalu unggah ulang.');
    if (trim($text) === '') throw new RuntimeException('File CSV kosong.');
    $stream = fopen('php://temp', 'r+');
    fwrite($stream, $text);
    $delimiter = ','; $best = 0;
    foreach ([',',';',"\t"] as $candidate) {
        rewind($stream);
        $header = fgetcsv($stream, 0, $candidate, '"', '');
        if (preg_match('/^SEKSI\s*:/i', trim((string)($header[0] ?? '')))) $header = fgetcsv($stream, 0, $candidate, '"', '');
        $score = count(array_intersect(array_map('qaCsvHeader', $header ?: []), qaCsvRequired()));
        if ($score > $best) { $delimiter = $candidate; $best = $score; }
    }
    qaCsvCheckQuotes($text, $delimiter);
    rewind($stream);
    $first = fgetcsv($stream, 0, $delimiter, '"', '') ?: [];
    $section = qaCsvSectionRow($first);
    $record = 1;
    if ($section !== null) {
        if ($section === '') throw new RuntimeException('Isi nama seksi setelah SEKSI: pada baris pertama.');
        $first = fgetcsv($stream, 0, $delimiter, '"', '') ?: [];
        $record++;
    }
    $headers = array_map('qaCsvHeader', $first);
    $allowed = array_intersect_key(qaCsvFields(), $schema);
    if (count($headers) !== count(array_unique($headers))) throw new RuntimeException('Nama kolom CSV tidak boleh berulang.');
    foreach ($headers as $header) {
        if (!isset($allowed[$header])) throw new RuntimeException('Kolom CSV tidak dikenal atau belum tersedia: '.($header ?: '(kosong)').'. Gunakan template.');
    }
    $missing = array_diff(qaCsvRequired(), $headers);
    if ($section !== null) $missing = array_diff($missing, ['seksi_pemilik']);
    if ($missing) throw new RuntimeException('Kolom wajib belum ada: '.implode(', ', $missing).'.');
    $rows = []; $seen = [];
    while (($values = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
        $record++;
        if (!array_filter($values, static function($v){return trim((string)$v) !== '';})) continue;
        if (count($rows) >= QA_CSV_MAX_ROWS) throw new RuntimeException('Maksimal 1.000 baris data per upload.');
        $errors = []; $data = [];
        if (count($values) !== count($headers)) {
            $errors[] = 'Jumlah kolom tidak sesuai header.';
        } else {
            $data = array_combine($headers, array_map(static function($v){return trim((string)$v);}, $values));
            if ($section !== null) {
                if (isset($data['seksi_pemilik']) && strcasecmp($data['seksi_pemilik'], $section) !== 0) $errors[] = 'Seksi baris tidak sesuai dengan seksi di bagian atas CSV.';
                $data['seksi_pemilik'] = $section;
            }
            $data['no_part'] = strtoupper($data['no_part']);
            foreach (qaCsvRequired() as $field) if ($data[$field] === '') $errors[] = qaCsvFields()[$field].' wajib diisi.';
            if (preg_match('/_(NOTGOOD|RUSAK|DISCONTINUE|DITARIK)_/i', $data['no_part'])) $errors[] = 'Nomor alat memakai pola arsip internal.';
            if (isset($seen[$data['no_part']])) $errors[] = 'Nomor alat berulang di CSV (baris '.$seen[$data['no_part']].').';
            $seen[$data['no_part']] = $record;
            $period = qaCsvPeriod($data['periode_kalibrasi']);
            if ($period === null) {
                $errors[] = 'Isi periode kalibrasi dengan angka bulat positif dalam satuan hari, misalnya 180 atau 365.';
            } else { $data['periode_kalibrasi'] = $period; }
            $date = qaCsvDate($data['jadwal_kalibrasi']);
            if (!$date) $errors[] = 'Jadwal tidak valid. Gunakan YYYY-MM-DD atau DD-MM-YYYY.';
            else $data['jadwal_kalibrasi'] = $date;
            if (!empty($data['format_laporan'])) {
                $data['format_laporan'] = strtoupper($data['format_laporan']);
                if (!preg_match('/^[A-W]$/', $data['format_laporan'])) $errors[] = 'Format laporan harus A sampai W.';
            }
            foreach ($data as $field => $value) {
                if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) $errors[] = qaCsvFields()[$field].' mengandung karakter kontrol.';
                if (preg_match('/^(?:var)?char\((\d+)\)/i', $schema[$field]['Type'], $m) && mb_strlen($value, 'UTF-8') > (int)$m[1]) $errors[] = qaCsvFields()[$field].' maksimal '.$m[1].' karakter.';
                $textLimits = ['tinytext'=>255, 'text'=>65535, 'mediumtext'=>16777215];
                $type = strtolower($schema[$field]['Type']);
                if (isset($textLimits[$type]) && strlen($value) > $textLimits[$type]) $errors[] = qaCsvFields()[$field].' terlalu panjang.';
                if (strpos((string)$schema[$field]['Collation'], 'latin1_') === 0 && @iconv('UTF-8', 'Windows-1252', $value) === false) $errors[] = qaCsvFields()[$field].' berisi karakter yang tidak didukung database.';
            }
        }
        $rows[] = ['line'=>$record, 'data'=>$data, 'errors'=>$errors];
    }
    fclose($stream);
    if (!$rows) throw new RuntimeException('CSV hanya berisi header, belum ada data alat.');
    return $rows;
}
function qaCsvContext(mysqli $db, array $rows, bool $lock): array {
    $numbers = array_values(array_unique(array_filter(array_map(static function($r){return $r['data']['no_part'] ?? '';}, $rows))));
    $context = ['master'=>[], 'registry'=>[], 'archive'=>[], 'history'=>[], 'sections'=>[]];
    // MyISAM uses table-level locking (in qaCsvApply), not SELECT ... FOR UPDATE.
    $suffix = '';
    foreach (qaCsvFetch($db, 'SELECT nama_seksi FROM qa_seksi_master ORDER BY nama_seksi'.$suffix) as $section) $context['sections'][strtoupper(trim($section['nama_seksi']))] = $section['nama_seksi'];
    if (!$numbers) return $context;
    $in = implode(',', array_fill(0, count($numbers), '?'));
    foreach (['master'=>'qa_part','registry'=>'qa_alat_registry'] as $key=>$table) {
        foreach (qaCsvFetch($db, "SELECT * FROM `$table` WHERE UPPER(TRIM(no_part)) IN ($in) ORDER BY id".$suffix, $numbers) as $row) $context[$key][strtoupper(trim($row['no_part']))][] = $row;
    }
    foreach (['qa_alat_ng','qa_alat_rusak','qa_alat_discontinue','qa_alat_ditarik'] as $table) {
        foreach (qaCsvFetch($db, "SELECT id,no_part FROM `$table` ORDER BY id".$suffix) as $row) {
            $no = preg_replace('/_(?:NOTGOOD|RUSAK|DISCONTINUE|DITARIK)_\d{10,14}$/i', '', strtoupper(trim($row['no_part'])));
            if (in_array($no, $numbers, true)) $context['archive'][$no][] = [$table, $row];
        }
    }
    foreach (qaCsvFetch($db, "SELECT id,no_part,status_validasi,status_kalibrasi FROM qa_pengecekan WHERE UPPER(TRIM(no_part)) IN ($in) ORDER BY id".$suffix, $numbers) as $row) $context['history'][strtoupper(trim($row['no_part']))][] = $row;
    return $context;
}
function qaCsvPlan(mysqli $db, array $rows, bool $lock = false): array {
    $context = qaCsvContext($db, $rows, $lock);
    $items = []; $counts = ['new'=>0,'update'=>0,'unchanged'=>0,'error'=>0];
    foreach ($rows as $row) {
        $data = $row['data']; $errors = $row['errors']; $no = $data['no_part'] ?? '';
        $masters = $context['master'][$no] ?? []; $registries = $context['registry'][$no] ?? [];
        $master = $masters[0] ?? null; $registry = $registries[0] ?? null;
        $history = $context['history'][$no] ?? []; $archives = $context['archive'][$no] ?? [];
        if (count($masters) > 1 || count($registries) > 1) $errors[] = 'Nomor alat ambigu/duplikat di database. Rapikan data terlebih dahulu.';
        if ($archives || ($master && strtolower($master['status']) !== 'aktif') || ($registry && strtoupper($registry['status_alat']) !== 'AKTIF')) $errors[] = 'Alat nonaktif atau sudah diarsipkan. Tidak dapat diubah melalui upload CSV.';
        foreach ($history as $event) {
            if (in_array($event['status_validasi'], ['menunggu_pemeriksa','menunggu_penyetuju'], true)) { $errors[] = 'Alat sedang menunggu validasi. Selesaikan validasi sebelum mengubah master.'; break; }
        }
        $latest = $history ? end($history) : null;
        if ($latest && $latest['status_validasi'] === 'final_disetujui' && in_array(strtoupper($latest['status_kalibrasi']), ['BAD','NG','NOT GOOD','BURUK','RUSAK','DISCONTINUE','DITARIK','DITARIK TECHNICAL','DITARIK MECHANICAL'], true)) $errors[] = 'Hasil terakhir alat nonaktif. Periksa status alat sebelum import.';
        if ($registry && !$master) $errors[] = 'Nomor sudah tercatat di registry tetapi master aktif tidak ditemukan.';
        if ($master && !empty($master['alat_registry_id']) && (!$registry || (int)$master['alat_registry_id'] !== (int)$registry['id'])) $errors[] = 'Hubungan master dan registry tidak sesuai. Periksa data alat terlebih dahulu.';
        if ($registry && ((!empty($registry['sumber_tabel']) && $registry['sumber_tabel'] !== 'qa_part') || (!empty($registry['sumber_id']) && (int)$registry['sumber_id'] !== (int)($master['id'] ?? 0)))) $errors[] = 'Registry menunjuk ke data alat lain.';
        $section = strtoupper(trim($data['seksi_pemilik'] ?? ''));
        if (!isset($context['sections'][$section])) $errors[] = 'Seksi belum terdaftar di master seksi.';
        else $data['seksi_pemilik'] = $context['sections'][$section];
        $changes = []; $values = [];
        foreach ($data as $field=>$value) {
            // Empty optional cells deliberately preserve existing values.
            if ($value === '' && !in_array($field, qaCsvRequired(), true)) continue;
            $values[$field] = $value;
            if (!$master || (string)($master[$field] ?? '') !== $value) $changes[$field] = ['old'=>$master[$field] ?? '', 'new'=>$value];
        }
        $needsRegistry = !$registry || ($master && (int)$master['alat_registry_id'] !== (int)$registry['id']) || ($registry && (empty($registry['sumber_tabel']) || empty($registry['sumber_id'])));
        $action = $errors ? 'error' : (!$master ? 'new' : (($changes || $needsRegistry) ? 'update' : 'unchanged'));
        $counts[$action]++;
        $items[] = ['line'=>$row['line'], 'data'=>$values, 'errors'=>array_values(array_unique($errors)), 'action'=>$action,
            'changes'=>$changes, 'master_id'=>$master['id'] ?? null, 'registry_id'=>$registry['id'] ?? null,
            'needs_registry'=>$needsRegistry, 'state'=>hash('sha256', serialize([$masters,$registries,$archives,$history]))];
    }
    return ['items'=>$items, 'counts'=>$counts, 'signature'=>hash('sha256', serialize($items))];
}
function qaCsvApply(mysqli $db, array $rows, string $signature, string $actor): array {
    qaCsvSchema($db);
    // MyISAM does not implement BEGIN/COMMIT/ROLLBACK. Validate first, then
    // hold table locks while rechecking the plan and executing each statement.
    // A server or disk failure mid-write may still leave a partial batch.
    $db->query("SET SESSION sql_mode = CONCAT_WS(',', @@SESSION.sql_mode, 'STRICT_ALL_TABLES')");
    $db->query('LOCK TABLES `qa_part` WRITE, `qa_alat_registry` WRITE, `qa_seksi_master` READ, `qa_pengecekan` READ, `qa_alat_ng` READ, `qa_alat_rusak` READ, `qa_alat_discontinue` READ, `qa_alat_ditarik` READ');
    try {
        $plan = qaCsvPlan($db, $rows);
        if ($plan['counts']['error'] || !hash_equals($signature, $plan['signature'])) {
            throw new RuntimeException('Data berubah sebelum import disimpan atau ada baris tidak valid. Tidak ada data yang disimpan; upload kembali untuk memeriksa data terbaru.');
        }
        $written = 0;
        foreach ($plan['items'] as $item) {
            if ($item['action'] === 'unchanged') continue;
            $values = $item['data']; $registryId = $item['registry_id'];
            try {
                if (!$registryId) {
                    qaCsvWrite($db, "INSERT INTO qa_alat_registry (no_part,status_alat,created_by) VALUES (?,'AKTIF',?)", [$values['no_part'],$actor]);
                    $registryId = $db->insert_id;
                }
                if ($item['action'] === 'new') {
                    $values['alat_registry_id'] = $registryId;
                    $columns = '`'.implode('`,`', array_keys($values)).'`';
                    qaCsvWrite($db, 'INSERT INTO qa_part ('.$columns.') VALUES ('.implode(',', array_fill(0,count($values),'?')).')', array_values($values));
                    $masterId = $db->insert_id;
                } else {
                    $masterId = $item['master_id'];
                    $updates = [];
                    foreach ($item['changes'] as $field=>$change) $updates[$field] = $change['new'];
                    $updates['alat_registry_id'] = $registryId;
                    $assignments = implode(',', array_map(static function($field){return '`'.$field.'`=?';}, array_keys($updates)));
                    qaCsvWrite($db, 'UPDATE qa_part SET '.$assignments.' WHERE id=?', array_merge(array_values($updates), [$masterId]));
                }
                qaCsvWrite($db, "UPDATE qa_alat_registry SET sumber_tabel='qa_part',sumber_id=? WHERE id=?", [$masterId,$registryId]);
                $written++;
            } catch (Throwable $error) {
                error_log('QA MyISAM CSV gagal pada no_part '.$values['no_part'].': '.$error->getMessage());
                throw new RuntimeException('Import terhenti pada nomor alat '.$values['no_part'].'. MyISAM tidak menyediakan rollback; '.$written.' alat sebelumnya mungkin sudah tersimpan. Periksa master dan registry sebelum mengulangi upload.', 0, $error);
            }
        }
        return $plan['counts'];
    } finally {
        $db->query('UNLOCK TABLES');
    }
}

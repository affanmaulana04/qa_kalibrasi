<?php
require_once __DIR__ . '/qa_format_aa.php';
/**
 * Import migrasi QA A-Z. Native PHP mysqli + ZipArchive + SimpleXML.
 * Database: MyISAM, latin1_swedish_ci. No transactions or foreign keys.
 */
const QA_MIG_MAX_BYTES = 5242880;
const QA_MIG_MAX_MASTER = 1000;
const QA_MIG_MAX_HISTORY = 4000;

function qaMigCatalog(): array {
    return [
        'A' => 'Abrassion inspection gauge', 'B' => 'Air Driver',
        'C' => 'Temperature kontrol / Temp. Normal Regulator', 'D' => 'Multimeter digital',
        'E' => 'Thermometer digital', 'F' => 'Timbangan digital', 'G' => 'Air Torque',
        'H' => 'Thread plug gauge', 'I' => 'Thread Ring gauge', 'J' => 'Radius gauge',
        'K' => 'Voltage Ultrasonic PL PB II', 'L' => 'Lux Meter / Pressure gauge',
        'M' => 'Profile Projector', 'N' => 'Puller gauge', 'O' => 'Snap gauge',
        'P' => 'SK Gauge', 'Q' => 'Seration plug gauge', 'R' => 'Gauge Verifikasi',
        'S' => 'Gelas ukur', 'T' => 'Plug gauge',
        'U' => 'Ring standar / Dial Caliper gage / Height gage / Cylinder gage / Inside micrometer / Penggaris / Tapered Gage / Infrared Thermometer / Stop Watch',
        'V' => 'Sigmat Digital', 'W' => 'Bevel protractor', 'X' => 'Dial Indicator',
        'Y' => 'Hygro Thermometer', 'Z' => 'Blow Timer', 'AA' => 'pH meter',
        'AB' => 'Electric Driver'
    ];
}
function qaMigAliases(): array {
    $names = [];
    foreach (qaMigCatalog() as $code => $description) {
        foreach (explode(' / ', $description) as $alias) {
            $key = qaMigNameKey($alias);
            if (isset($names[$key]) && $names[$key] !== $code)
                unset($names[$key]);
            else
                $names[$key] = $code;
        }
    }
    $names[qaMigNameKey('Temperature control')] = 'C';
    $names[qaMigNameKey('Pressure Gauge')] = 'L';
    $names[qaMigNameKey('Gelas Ukur')] = 'S';
    $names[qaMigNameKey('Stopwatch')] = 'U';
    $names[qaMigNameKey('pH-meter')] = 'AA';
    return $names;
}
function qaMigNameKey(string $input): string {
    return strtolower(preg_replace('/\s+/', ' ', trim($input)));
}
function qaMigNo(string $v): string {
    // Whitespace at both ends ignored; internal spaces are significant.
    return strtoupper(trim(str_replace("\xc2\xa0", ' ', $v)));
}
function qaMigColsMaster(): array {
    return [
        'NO_ALAT', 'NAMA_ALAT', 'FORMAT_OVERRIDE', 'SEKSI_PEMILIK', 'SPESIFIKASI', 'RESOLUSI', 'SATUAN',
        'MERK', 'BUATAN', 'TYPE', 'FUNGSI', 'NO_ASSET', 'LOKASI', 'PERIODE_KALIBRASI_HARI',
        'JADWAL_KALIBRASI', 'TANGGAL_BELI', 'METODE_KALIBRASI', 'STANDAR_UJI',
        'TRACEABILITY', 'STANDAR_TOLERANSI', 'KALIBRATOR', 'JENIS_PEMERIKSAAN'
    ];
}
function qaMigColsHistory(): array {
    return ['NO_ALAT', 'TANGGAL', 'TIPE_RIWAYAT', 'JENIS_CHECK', 'HASIL', 'PETUGAS', 'DIPERIKSA', 'PELAKSANAAN', 'KETERANGAN'];
}
function qaMigClean(string $s): string {
    return trim(str_replace("\xc2\xa0", ' ', $s));
}
function qaMigDate(string $v, bool $allowBlank = true): ?string {
    // Input Excel: DD-MM-YYYY (10-10-2026), boleh juga 10 - 10 - 2026.
    // Nilai DB tetap dinormalisasi menjadi YYYY-MM-DD agar sesuai kolom DATE MySQL.
    $v = trim($v);
    if ($v === '')
        return $allowBlank ? '' : null;
    $v = preg_replace('/\s*([-\/])\s*/u', '$1', $v);
    if (preg_match('/^\d{4,6}(?:\.0+)?$/', $v) && (float)$v >= 20000 && (float)$v <= 100000) {
        return (new DateTimeImmutable('1899-12-30'))->modify('+' . (int)(float)$v . ' days')->format('Y-m-d');
    }
    foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d'] as $pattern) {
        $d = DateTimeImmutable::createFromFormat('!' . $pattern, $v);
        if ($d && $d->format($pattern) === $v && (int)$d->format('Y') >= 1900 && (int)$d->format('Y') <= 2100)
            return $d->format('Y-m-d');
    }
    return null;
}
function qaMigExcelCol(string $cell): int {
    preg_match('/^[A-Z]+/i', $cell, $m);
    $letters = strtoupper($m[0] ?? '');
    $n = 0;
    foreach (str_split($letters) as $char)
        $n = $n * 26 + ord($char) - 64;
    return $n - 1;
}
function qaMigXml(string $str) {
    $old = libxml_use_internal_errors(true);
    try {
        $xml = simplexml_load_string($str, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        if ($xml === false)
            throw new RuntimeException('XML dalam Excel rusak. Simpan ulang file XLSX.');
        return $xml;
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($old);
    }
}
/**
 * XLSX parser v1.2 — namespace-independent and row-2-data compatible.
 * Baris pertama boleh berisi judul kolom/petunjuk.
 */
function qaMigReadXlsx(string $path): array {
    if (!class_exists('ZipArchive') || !function_exists('simplexml_load_string')) {
        throw new RuntimeException('Aktifkan extension PHP zip dan simplexml untuk membaca .xlsx.');
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true)
        throw new RuntimeException('File gagal dibuka sebagai XLSX.');
    try {
        if ($zip->numFiles > 250)
            throw new RuntimeException('Terlalu banyak komponen XLSX.');
        $total = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $st = $zip->statIndex($i);
            $total += (int)$st['size'];
            if ((int)$st['size'] > 25000000 || $total > 35000000) {
                throw new RuntimeException('Isi XLSX melampaui batas ukuran.');
            }
        }
        $xmlWorkbook = $zip->getFromName('xl/workbook.xml');
        $xmlRels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($xmlWorkbook === false || $xmlRels === false)
            throw new RuntimeException('Struktur XLSX tidak lengkap.');
        $workbook = qaMigXml($xmlWorkbook);
        $rels = qaMigXml($xmlRels);
        $rNamespace = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        // local-name(): bekerja untuk <x:row>, <row>, dan prefix lain.
        $relationshipMap = [];
        foreach ($rels->xpath('/*[local-name()="Relationships"]/*[local-name()="Relationship"]') ?: [] as $r) {
            $relationshipMap[(string)$r['Id']] = (string)$r['Target'];
        }
        $shared = [];
        $xmlShared = $zip->getFromName('xl/sharedStrings.xml');
        if ($xmlShared !== false) {
            $sharedDoc = qaMigXml($xmlShared);
            foreach ($sharedDoc->xpath('/*[local-name()="sst"]/*[local-name()="si"]') ?: [] as $si) {
                $str = '';
                foreach ($si->xpath('.//*[local-name()="t"]') ?: [] as $piece)
                    $str .= (string)$piece;
                $shared[] = $str;
                if (count($shared) > 100000)
                    throw new RuntimeException('Terlalu banyak shared strings.');
            }
        }
        $result = [];
        $sheets = $workbook->xpath('/*[local-name()="workbook"]/*[local-name()="sheets"]/*[local-name()="sheet"]') ?: [];
        foreach ($sheets as $sheetNode) {
            $name = strtoupper(trim((string)$sheetNode['name']));
            if (!in_array($name, ['MASTER_ALAT', 'RIWAYAT_BBRAU'], true))
                continue;
            $rId = (string)$sheetNode->attributes($rNamespace)['id'];
            $target = $relationshipMap[$rId] ?? '';
            if ($target === '')
                throw new RuntimeException('Relasi sheet ' . $name . ' tidak ditemukan.');
            $member = ltrim(str_replace('\\', '/', $target), '/');
            if (strpos($member, 'xl/') !== 0)
                $member = 'xl/' . $member;
            if (strpos($member, '..') !== false)
                throw new RuntimeException('Path worksheet tidak valid.');
            $xmlSheet = $zip->getFromName($member);
            if ($xmlSheet === false)
                throw new RuntimeException('Konten worksheet ' . $name . ' tidak ditemukan.');
            $worksheet = qaMigXml($xmlSheet);
            $rows = [];
            $rowNodes = $worksheet->xpath('/*[local-name()="worksheet"]/*[local-name()="sheetData"]/*[local-name()="row"]') ?: [];
            $sequentialRow = 0;
            foreach ($rowNodes as $rowNode) {
                $line = (int)$rowNode['r'];
                if ($line <= 0)
                    $line = ++$sequentialRow;
                else
                    $sequentialRow = $line;
                $values = [];
                $sequentialCol = 0;
                foreach ($rowNode->xpath('./*[local-name()="c"]') ?: [] as $cell) {
                    $cellRef = (string)$cell['r'];
                    $column = $cellRef === '' ? $sequentialCol : qaMigExcelCol($cellRef);
                    $sequentialCol = $column + 1;
                    if ($column < 0 || $column > 80)
                        continue;
                    $type = (string)$cell['t'];
                    $vNodes = $cell->xpath('./*[local-name()="v"]') ?: [];
                    $v = $vNodes ? (string)$vNodes[0] : '';
                    if ($type === 's') {
                        $value = $v !== '' ? ($shared[(int)$v] ?? '') : '';
                    } elseif ($type === 'inlineStr') {
                        $value = '';
                        foreach ($cell->xpath('./*[local-name()="is"]//*[local-name()="t"]') ?: [] as $part) {
                            $value .= (string)$part;
                        }
                    } elseif ($type === 'b') {
                        $value = $v === '1' ? 'TRUE' : 'FALSE';
                    } else {
                        $value = $v;
                    }
                    $formulas = $cell->xpath('./*[local-name()="f"]') ?: [];
                    if ($formulas && $value === '') {
                        throw new RuntimeException('Rumus ' . $name . ' baris ' . $line . ' tidak punya nilai hasil tersimpan. Ganti dengan nilai biasa.');
                    }
                    $values[$column] = qaMigClean($value);
                }
                if ($values)
                    $rows[$line] = $values;
                if (count($rows) > QA_MIG_MAX_HISTORY + 10) {
                    throw new RuntimeException('Jumlah baris XLSX melebihi batas.');
                }
            }
            $result[$name] = $rows;
        }
        return $result;
    } finally {
        $zip->close();
    }
}

/**
 * Layout sheet yang didukung:
 * - Baris 1: nama kolom/petunjuk; data mulai baris 2 (default).
 * - Baris 1 petunjuk, baris 2 nama kolom; data mulai baris 3.
 * - Jika baris 1 adalah petunjuk, data baris 2 tetap diterima berdasarkan
 *   urutan kolom template; validasi per baris tetap dijalankan oleh qaMigPlan.
 */
function qaMigSheetRows(array $sheet, array $headers, int $limit, string $name): array {
    // Riwayat BBRAU opsional: sheet kosong sama dengan tanpa data histori.
    if (!$sheet) {
        if ($name === 'RIWAYAT_BBRAU')
            return [];
        throw new RuntimeException('Sheet ' . $name . ' tidak memiliki sel yang terbaca (parser XLSX v1.2). Periksa isi file, bukan baris headernya.');
    }
    ksort($sheet, SORT_NUMERIC);
    $normal = static function ($v): string {
        return strtoupper(trim(preg_replace('/\\s+/', '_', (string)$v), '_'));
    };
    $headerRow = null;
    foreach ($sheet as $line => $cells) {
        if ((int)$line > 5)
            break;
        $complete = true;
        foreach ($headers as $col => $expected) {
            if ($normal($cells[$col] ?? '') !== $expected) {
                $complete = false;
                break;
            }
        }
        if ($complete) {
            $headerRow = (int)$line;
            break;
        }
    }
    $startRow = $headerRow !== null ? $headerRow + 1 : 2;
    // Jika ada sebagian nama kolom tetapi tidak lengkap/urut, jangan
    // diam-diam memasukkan nilai yang salah ke kolom database.
    if ($headerRow === null) {
        foreach ([1, 2] as $candidate) {
            $cells = $sheet[$candidate] ?? [];
            $knownHeaders = 0;
            $matchPosition = 0;
            foreach ($cells as $col => $v) {
                $key = $normal($v);
                if (in_array($key, $headers, true)) {
                    $knownHeaders++;
                    if (isset($headers[$col]) && $headers[$col] === $key)
                        $matchPosition++;
                }
            }
            if ($knownHeaders >= 3) {
                throw new RuntimeException('Judul kolom ' . $name . ' baris ' . $candidate . ' tidak lengkap atau urutannya berubah. Cocokkan urutan dengan template (parser v1.2).');
            }
        }
    }
    $parsed = [];
    foreach ($sheet as $line => $cells) {
        if ((int)$line < $startRow)
            continue;
        $item = [];
        $hasData = false;
        foreach ($headers as $col => $key) {
            $item[$key] = qaMigClean((string)($cells[$col] ?? ''));
            if ($item[$key] !== '')
                $hasData = true;
        }
        if (!$hasData)
            continue;
        $item['_row'] = (int)$line;
        $parsed[] = $item;
        if (count($parsed) > $limit)
            throw new RuntimeException('Maksimum ' . $limit . ' baris di ' . $name . '.');
    }
    if ($name === 'MASTER_ALAT' && !$parsed) {
        throw new RuntimeException('Tidak ditemukan data MASTER_ALAT mulai baris ' . $startRow . ' (parser v1.2). Isi minimal NO_ALAT dan NAMA_ALAT.');
    }
    return $parsed;
}

function qaMigParse(string $file): array {
    $sheets = qaMigReadXlsx($file);
    if (!isset($sheets['MASTER_ALAT']))
        throw new RuntimeException('Workbook wajib punya sheet MASTER_ALAT.');
    // File lama (20/21 kolom) tetap bisa diimport; kolom baru ada di paling kanan.
    $masterCols = qaMigColsMaster();
    $hasKalibratorHeader = false;
    $hasJenisHeader = false;
    foreach ($sheets['MASTER_ALAT'] as $line => $cells) {
        if ((int)$line > 5)
            break;
        foreach ($cells as $text) {
            $label = strtoupper(trim((string)$text));
            if ($label === 'KALIBRATOR')
                $hasKalibratorHeader = true;
            if ($label === 'JENIS_PEMERIKSAAN')
                $hasJenisHeader = true;
        }
    }
    if ($hasJenisHeader && !$hasKalibratorHeader) {
        throw new RuntimeException('Header MASTER_ALAT tidak sesuai template: KALIBRATOR wajib ada sebelum JENIS_PEMERIKSAAN.');
    }
    if (!$hasKalibratorHeader)
        $masterCols = array_slice($masterCols, 0, -2);
    elseif (!$hasJenisHeader)
        $masterCols = array_slice($masterCols, 0, -1);
    $master = qaMigSheetRows($sheets['MASTER_ALAT'], $masterCols, QA_MIG_MAX_MASTER, 'MASTER_ALAT');
    foreach ($master as &$item) {
        $item['KALIBRATOR'] = $item['KALIBRATOR'] ?? '';
        $item['JENIS_PEMERIKSAAN'] = strtoupper(trim((string)($item['JENIS_PEMERIKSAAN'] ?? '')));
    }
    unset($item);
    $hist = qaMigSheetRows($sheets['RIWAYAT_BBRAU'] ?? [], qaMigColsHistory(), QA_MIG_MAX_HISTORY, 'RIWAYAT_BBRAU');
    if (!$master)
        throw new RuntimeException('MASTER_ALAT belum berisi data.');
    foreach ($master as &$m) {
        $m['NO_ALAT'] = qaMigNo($m['NO_ALAT']);
        foreach (['JADWAL_KALIBRASI', 'TANGGAL_BELI'] as $key) {
            $date = qaMigDate($m[$key]);
            if ($date !== null)
                $m[$key] = $date;
        }
        $m['FORMAT_FINAL'] = strtoupper($m['FORMAT_OVERRIDE']?:qaMigAliases()[qaMigNameKey($m['NAMA_ALAT'])] ?? '');
    }
    unset($m);
    foreach ($hist as &$h) {
        $h['NO_ALAT'] = qaMigNo($h['NO_ALAT']);
        $h['TIPE_RIWAYAT'] = strtoupper($h['TIPE_RIWAYAT']);
        $h['JENIS_CHECK'] = strtoupper($h['JENIS_CHECK']);
        $h['HASIL'] = strtoupper($h['HASIL']);
        $date = qaMigDate($h['TANGGAL']);
        if ($date !== null)
            $h['TANGGAL'] = $date;
    }
    unset($h);
    return ['master' => $master, 'history' => $hist];
}
function qaMigQuery(mysqli $db, string $sql, array $params = []): array {
    $stmt = $db->prepare($sql);
    try {
        if ($params)
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } finally {
        $stmt->close();
    }
}
function qaMigExec(mysqli $db, string $sql, array $params): int {
    $stmt = $db->prepare($sql);
    try {
        if ($params)
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        return $db->insert_id;
    } finally {
        $stmt->close();
    }
}
function qaMigEnsureTables(mysqli $db): void {
    $db->query("CREATE TABLE IF NOT EXISTS qa_migrasi_alat_meta (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        no_part VARCHAR(50) NOT NULL,
        alat_id INT UNSIGNED NOT NULL,
        tanggal_beli DATE DEFAULT NULL,
        status_standar ENUM('BELUM_DIISI','DRAFT','TERKUNCI') NOT NULL DEFAULT 'BELUM_DIISI',
        import_by VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_qa_mig_meta_no (no_part)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci");
    $db->query("CREATE TABLE IF NOT EXISTS qa_migrasi_bbrau (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        no_part VARCHAR(50) NOT NULL,
        tanggal DATE NOT NULL,
        tipe_riwayat ENUM('REGISTRASI','CHECK') NOT NULL DEFAULT 'CHECK',
        jenis_check VARCHAR(40) NOT NULL DEFAULT '',
        hasil VARCHAR(50) NOT NULL DEFAULT '',
        petugas VARCHAR(100) NOT NULL DEFAULT '',
        diperiksa VARCHAR(100) NOT NULL DEFAULT '',
        pelaksanaan VARCHAR(50) NOT NULL DEFAULT '',
        keterangan VARCHAR(255) NOT NULL DEFAULT '',
        fingerprint CHAR(64) NOT NULL,
        import_by VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_qa_mig_bbrau_fingerprint (fingerprint),
        KEY idx_qa_mig_bbrau_no_tanggal (no_part,tanggal)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci");
    foreach (['qa_part', 'qa_alat_registry', 'qa_migrasi_alat_meta', 'qa_migrasi_bbrau'] as $table) {
        $result = $db->query("SHOW TABLE STATUS LIKE '" . $table . "'");
        $row = $result->fetch_assoc();
        if (!$row || strcasecmp($row['Engine'], 'MyISAM') !== 0)
            throw new RuntimeException('Tabel ' . $table . ' wajib menggunakan engine MyISAM.');
    }
    $columns = [];
    foreach ($db->query('SHOW COLUMNS FROM qa_part') as $col)
        $columns[$col['Field']] = true;
    foreach (['id', 'no_part', 'nama_part', 'seksi_pemilik', 'format_laporan', 'titik_standar_json', 'alat_registry_id', 'status', 'periode_kalibrasi'] as $key) {
        if (!isset($columns[$key]))
            throw new RuntimeException('Kolom qa_part tidak ada: ' . $key . '.');
    }
}
/**
 * Menambahkan metadata kalibrator bila dipakai. Dijalankan hanya saat SIMPAN,
 * tidak saat preview, dan sebelum LOCK TABLES (MyISAM).
 * Arsip diberi kolom yang sama supaya kalibrator ikut terbawa saat finalisasi.
 */
function qaMigEnsureKalibratorColumns(mysqli $db): void {
    foreach (['qa_part', 'qa_alat_ng', 'qa_alat_rusak', 'qa_alat_discontinue', 'qa_alat_ditarik'] as $table) {
        $exists = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($table) . "'");
        if (!$exists || $exists->num_rows === 0)
            continue;
        $has = $db->query("SHOW COLUMNS FROM `$table` LIKE 'kalibrator'");
        if ($has && $has->num_rows > 0)
            continue;
        $db->query("ALTER TABLE `$table` ADD COLUMN `kalibrator` VARCHAR(100) NOT NULL DEFAULT ''");
    }
}

function qaMigValidateText(string $value, string $field, int $max, array &$errors): void {
    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value))
        $errors[] = $field . ' berisi karakter kontrol.';
    $latin = @iconv('UTF-8', 'Windows-1252', $value);
    if ($latin === false)
        $errors[] = $field . ' mengandung karakter yang tidak didukung latin1.';
    elseif (strlen($latin) > $max)
        $errors[] = $field . ' maksimal ' . $max . ' karakter.';
}
function qaMigFingerprint(array $h): string {
    $cols = qaMigColsHistory();
    $vals = [];
    foreach ($cols as $c)
        $vals[] = $h[$c] ?? '';
    return hash('sha256', json_encode($vals, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
}
/**
 * Satu REGISTRASI otomatis ketika master tidak memiliki registrasi di database/Excel.
 * Tanggal acuan: TANGGAL_BELI, CHECK paling awal, atau tanggal import.
 * KETERANGAN registrasi otomatis sengaja dibiarkan kosong.
 */
function qaMigAutoRegistration(array $m, array $history): array {
    $date = qaMigDate((string)$m['TANGGAL_BELI']);
    if (!$date) {
        $earliest = '';
        foreach ($history as $h) {
            if ($h['NO_ALAT'] !== $m['NO_ALAT'] || $h['TIPE_RIWAYAT'] !== 'CHECK')
                continue;
            $d = qaMigDate((string)$h['TANGGAL'], false);
            if ($d !== null && ($earliest === '' || $d < $earliest))
                $earliest = $d;
        }
        if ($earliest !== '')
            $date = $earliest;
        else
            $date = date('Y-m-d');
    }
    return [
        'NO_ALAT' => $m['NO_ALAT'], 'TANGGAL' => $date, 'TIPE_RIWAYAT' => 'REGISTRASI',
        'JENIS_CHECK' => '', 'HASIL' => '', 'PETUGAS' => '', 'DIPERIKSA' => '',
        'PELAKSANAAN' => '', 'KETERANGAN' => ''
    ];
}
function qaMigPlan(mysqli $db, array $data): array {
    $errors = [];
    $items = [];
    $history = [];
    $seen = [];
    $dbInfo = [];
    $sections = [];
    $newSections = [];
    $kalColResult = $db->query("SHOW COLUMNS FROM qa_part LIKE 'kalibrator'");
    $kalColExists = $kalColResult && $kalColResult->num_rows > 0;
    foreach (qaMigQuery($db, 'SELECT nama_seksi FROM qa_seksi_master') as $s)
        $sections[strtoupper(trim($s['nama_seksi']))] = $s['nama_seksi'];
    foreach ($data['master'] as $m) {
        $no = $m['NO_ALAT'];
        $e = [];
        if ($no === '' || $m['NAMA_ALAT'] === '' || $m['SEKSI_PEMILIK'] === '')
            $e[] = 'No. Alat, Nama Alat, dan Seksi wajib diisi.';
        $formatValid = (bool)preg_match('/^(?:[A-Z]|AA|AB)$/', $m['FORMAT_FINAL']);
        if (!$formatValid)
            $e[] = 'Nama alat pada baris ' . $m['_row'] . ' tidak dikenali, Silahkan Pilih Format A-Z jika ada format yang sama.';
        if ($m['FORMAT_FINAL'] === 'AA' && qaFormatAATolerance($m['STANDAR_TOLERANSI']) === null)
            $e[] = 'Format AA wajib mengisi STANDAR_TOLERANSI numerik, contoh ± 0.5 pH.';
        if (isset($seen[$no]))
            $e[] = 'Nomor alat duplikat pada MASTER_ALAT (baris ' . $seen[$no] . ').';
        $seen[$no] = $m['_row'];
        $sect = strtoupper(trim($m['SEKSI_PEMILIK']));
        // Seksi baru valid: ditampilkan dalam preview, baru disimpan saat Import.
        // Seksi existing tetap dipakai tanpa menambah duplikat.
        if (isset($sections[$sect]))
            $m['SEKSI_PEMILIK'] = $sections[$sect];
        if (!preg_match('/^[0-9]{1,5}$/', $m['PERIODE_KALIBRASI_HARI']) || (int)$m['PERIODE_KALIBRASI_HARI'] <= 0)
            $e[] = 'Periode wajib berupa angka hari positif.';
        $jenisExcel = strtoupper(trim((string)($m['JENIS_PEMERIKSAAN'] ?? '')));
        if ($jenisExcel !== '' && !in_array($jenisExcel, ['KALIBRASI', 'VERIFIKASI'], true)) {
            $e[] = 'JENIS_PEMERIKSAAN harus KALIBRASI atau VERIFIKASI (atau kosong).';
        }
        foreach (['JADWAL_KALIBRASI', 'TANGGAL_BELI'] as $key) {
            $date = qaMigDate($m[$key]);
            if ($date === null)
                $e[] = $key . ' bukan tanggal valid (DD-MM-YYYY, contoh 10-10-2026).';
            else
                $m[$key] = $date;
        }
        foreach (qaMigColsMaster() as $col)
            qaMigValidateText((string)$m[$col], $col, ($col === 'SPESIFIKASI' || $col === 'STANDAR_UJI' || $col === 'TRACEABILITY'?255:100), $e);
        if (strlen($no) > 50)
            $e[] = 'No. Alat maksimal 50 karakter.';
        if (preg_match('/_(NOTGOOD|RUSAK|DISCONTINUE|DITARIK)_/i', $no))
            $e[] = 'No. Alat memakai pola internal arsip.';
        if ($no !== '' && !isset($dbInfo[$no])) {
            $reg = qaMigQuery($db, 'SELECT id,status_alat,sumber_tabel,sumber_id FROM qa_alat_registry WHERE UPPER(TRIM(no_part))=?', [$no]);
            $partSql = 'SELECT id,status,format_laporan,alat_registry_id,jenis_laporan' . ($kalColExists?',kalibrator':'') . ' FROM qa_part WHERE UPPER(TRIM(no_part))=?';
            $part = qaMigQuery($db, $partSql, [$no]);
            $dbInfo[$no] = ['reg' => $reg, 'part' => $part];
        }
        $match = $dbInfo[$no] ?? ['reg' => [], 'part' => []];
        $isOld = !!($match['reg'] || $match['part']);
        if (count($match['reg']) > 1 || count($match['part']) > 1)
            $e[] = 'Nomor alat ambigu/duplikat pada database.';
        if ($isOld) {
            if (!$match['reg'] || !$match['part'] || strtoupper($match['reg'][0]['status_alat']) !== 'AKTIF' || strtolower($match['part'][0]['status']) !== 'aktif')
                $e[] = 'Registry/master alat tidak aktif atau tidak sinkron.';
            elseif ((int)$match['part'][0]['alat_registry_id'] !== (int)$match['reg'][0]['id'])
                $e[] = 'Registry dan qa_part tidak terhubung.';
            elseif ($formatValid && $match['part'][0]['format_laporan'] !== '' && strtoupper($match['part'][0]['format_laporan']) !== $m['FORMAT_FINAL'])
                $e[] = 'Format tidak sama dengan alat existing.';
        }
        // Alat existing: hanya KALIBRATOR dan JENIS_PEMERIKSAAN yang
        // dinyatakan eksplisit di Excel boleh diperbarui. Kolom lain aman.
        $calUpdate = $isOld && !$e && $m['KALIBRATOR'] !== '' &&
            $m['KALIBRATOR'] !== trim((string)($match['part'][0]['kalibrator'] ?? ''));
        $jenisDb = strtolower(trim((string)($match['part'][0]['jenis_laporan'] ?? '')));
        $jenisTarget = $jenisExcel === 'VERIFIKASI'?'verifikasi':'kalibrasi';
        $jenisUpdate = $isOld && !$e && $jenisExcel !== '' && $jenisDb !== $jenisTarget;
        $status = $e?'ERROR':(!$isOld?'BARU':
            ($calUpdate && $jenisUpdate?'MASTER_UPDATE':
            ($calUpdate?'KALIBRATOR_UPDATE':($jenisUpdate?'JENIS_UPDATE':'SUDAH_ADA'))));
        if ($status === 'BARU' && $sect !== '' && !isset($sections[$sect])) {
            $newSections[$sect] = $m['SEKSI_PEMILIK'];
        }
        $items[] = ['row' => $m['_row'], 'no' => $no, 'nama' => $m['NAMA_ALAT'], 'format' => $m['FORMAT_FINAL'],
            'part_id' => (int)($match['part'][0]['id'] ?? 0),
            'kalibrator' => $m['KALIBRATOR'],
            'jenis_pemeriksaan' => $jenisExcel !== ''?$jenisExcel:($jenisDb === 'verifikasi'?'VERIFIKASI':'KALIBRASI'),
            'kalibrator_update' => $calUpdate, 'jenis_update' => $jenisUpdate,
            'seksi' => $m['SEKSI_PEMILIK'],
            'seksi_baru' => $status === 'BARU' && $sect !== '' && !isset($sections[$sect]),
            'status' => $status, 'errors' => $e];
        foreach ($e as $msg) {
            // Pesan ini sudah menyebut nomor baris, tidak perlu prefiks dua kali.
            $errors[] = strpos($msg, 'Nama alat pada baris ') === 0 ? $msg : 'MASTER_ALAT baris ' . $m['_row'] . ': ' . $msg;
        }
    }
    // Catat REGISTRASI yang telah ada, termasuk yang berasal dari sistem sebelum migrasi.
    // Prioritas: registrasi database > 1 registrasi Excel tertua > registrasi otomatis.
    $dbRegistrasi = [];
    foreach ($data['master'] as $m) {
        $no = $m['NO_ALAT'];
        if ($no === '' || isset($dbRegistrasi[$no]))
            continue;
        $fromExcelDb = qaMigQuery($db, "SELECT id FROM qa_migrasi_bbrau WHERE UPPER(TRIM(no_part))=? AND tipe_riwayat='REGISTRASI' LIMIT 1", [$no]);
        $fromQa = qaMigQuery($db, "SELECT id FROM qa_pengecekan WHERE UPPER(TRIM(no_part))=? AND jenis_transaksi='registrasi_alat' LIMIT 1", [$no]);
        $dbRegistrasi[$no] = !!($fromExcelDb || $fromQa);
    }
    // Bila file berisi beberapa REGISTRASI untuk alat yang sama, pilih tanggal tertua.
    $pilihanRegistrasi = [];
    foreach ($data['history'] as $i => $h) {
        if ($h['TIPE_RIWAYAT'] !== 'REGISTRASI')
            continue;
        $date = qaMigDate((string)$h['TANGGAL'], false);
        if ($date === null)
            continue;
        $no = $h['NO_ALAT'];
        if (!isset($pilihanRegistrasi[$no]) || $date < $pilihanRegistrasi[$no]['tanggal']) {
            $pilihanRegistrasi[$no] = ['index' => $i, 'tanggal' => $date];
        }
    }
    $fingerprints = [];
    foreach ($data['history'] as $i => $h) {
        $e = [];
        $no = $h['NO_ALAT'];
        if (!isset($seen[$no]))
            $e[] = 'No. Alat tidak ada di MASTER_ALAT.';
        if (!$h['TANGGAL'] || qaMigDate($h['TANGGAL'], false) === null)
            $e[] = 'Tanggal tidak valid (DD-MM-YYYY, contoh 10-10-2026).';
        else
            $h['TANGGAL'] = qaMigDate($h['TANGGAL'], false);
        if (!in_array($h['TIPE_RIWAYAT'], ['REGISTRASI', 'CHECK'], true))
            $e[] = 'TIPE_RIWAYAT harus REGISTRASI atau CHECK.';
        if ($h['TIPE_RIWAYAT'] === 'CHECK' && $h['JENIS_CHECK'] === '')
            $e[] = 'JENIS_CHECK wajib untuk CHECK.';
        foreach (qaMigColsHistory() as $key)
            qaMigValidateText((string)$h[$key], $key, $key === 'KETERANGAN'?255:100, $e);
        $fp = qaMigFingerprint($h);
        if ($h['TIPE_RIWAYAT'] === 'CHECK' && isset($fingerprints[$fp]))
            $e[] = 'Riwayat duplikat pada file (baris ' . $fingerprints[$fp] . ').';
        $fingerprints[$fp] = $h['_row'];
        $existing = qaMigQuery($db, 'SELECT id FROM qa_migrasi_bbrau WHERE fingerprint=? LIMIT 1', [$fp]);
        if (!$existing && $h['TANGGAL'] !== '' && isset($seen[$no]) && $h['TIPE_RIWAYAT'] === 'CHECK') {
            $existing = qaMigQuery($db, "SELECT id FROM qa_pengecekan WHERE UPPER(TRIM(no_part))=? AND tanggal_pengecekan=? AND UPPER(TRIM(status_kalibrasi))=? AND jenis_transaksi <> 'registrasi_alat' LIMIT 1", [$no, $h['TANGGAL'], $h['HASIL']]);
        }
        $status = $e?'ERROR':($existing?'SUDAH_ADA':'BARU');
        if (!$e && $h['TIPE_RIWAYAT'] === 'REGISTRASI') {
            if (!empty($dbRegistrasi[$no]))
                $status = 'SUDAH_ADA';
            elseif (isset($pilihanRegistrasi[$no]) && $pilihanRegistrasi[$no]['index'] !== $i)
                $status = 'DIABAIKAN';
        }
        $history[] = ['row' => $h['_row'], 'no' => $no, 'status' => $status, 'errors' => $e];
        foreach ($e as $msg)
            $errors[] = 'RIWAYAT_BBRAU baris ' . $h['_row'] . ': ' . $msg;
    }
    // Tambahkan rencana REGISTRASI otomatis hanya jika tidak ada di DB dan Excel.
    foreach ($data['master'] as $i => $m) {
        $no = $m['NO_ALAT'];
        if ($items[$i]['status'] === 'ERROR' || !empty($dbRegistrasi[$no]) || isset($pilihanRegistrasi[$no]))
            continue;
        $history[] = ['row' => 'AUTO', 'no' => $no, 'status' => 'BARU', 'errors' => [],
            'auto_reg' => qaMigAutoRegistration($m, $data['history'])];
    }
    return ['master' => $items, 'history' => $history, 'errors' => $errors,
        'seksi_baru' => array_values($newSections),
        'counts' => ['seksi_baru' => count($newSections),
            'master_baru' => count(array_filter($items, static function ($x) {
                return $x['status'] === 'BARU';
            })),
            'master_ada' => count(array_filter($items, static function ($x) {
                return $x['status'] === 'SUDAH_ADA';
            })),
            'kalibrator_update' => count(array_filter($items, static function ($x) {
                return !empty($x['kalibrator_update']);
            })),
            'jenis_update' => count(array_filter($items, static function ($x) {
                return !empty($x['jenis_update']);
            })),
            'hist_baru' => count(array_filter($history, static function ($x) {
                return $x['status'] === 'BARU';
            })),
            'hist_ada' => count(array_filter($history, static function ($x) {
                return $x['status'] === 'SUDAH_ADA' || $x['status'] === 'DIABAIKAN';
            }))]];
}
function qaMigApply(mysqli $db, array $data, string $actor): array {
    qaMigEnsureTables($db);
    // DDL jangan dijalankan di dalam LOCK TABLES MyISAM.
    foreach ($data['master'] as $item) {
        if (trim((string)($item['KALIBRATOR'] ?? '')) !== '') {
            qaMigEnsureKalibratorColumns($db);
            break;
        }
    }
    $db->query('LOCK TABLES qa_alat_registry WRITE, qa_part WRITE, qa_migrasi_alat_meta WRITE, qa_migrasi_bbrau WRITE, qa_seksi_master WRITE, qa_pengecekan READ');
    $result = ['seksi_baru' => 0, 'alat_baru' => 0, 'alat_dilewati' => 0, 'kalibrator_diperbarui' => 0, 'jenis_diperbarui' => 0, 'riwayat_baru' => 0, 'riwayat_dilewati' => 0];
    try {
        $plan = qaMigPlan($db, $data);
        if ($plan['errors'])
            throw new RuntimeException('Data berubah sejak preview. Import dihentikan: ' . $plan['errors'][0]);
        // Hanya buat seksi untuk alat BARU yang lolos validasi; satu insert per nama.
        // Dikerjakan saat import (bukan preview) dan di bawah WRITE lock MyISAM.
        foreach ($plan['seksi_baru'] as $seksi) {
            qaMigExec($db, 'INSERT INTO qa_seksi_master (nama_seksi) VALUES (?)', [$seksi]);
            $result['seksi_baru']++;
        }
        foreach ($data['master'] as $i => $m) {
            $no = $m['NO_ALAT'];
            $status = $plan['master'][$i]['status'];
            if ($status === 'SUDAH_ADA') {
                $result['alat_dilewati']++;
                continue;
            }
            if (in_array($status, ['KALIBRATOR_UPDATE', 'JENIS_UPDATE', 'MASTER_UPDATE'], true)) {
                $item = $plan['master'][$i];
                $id = (int)$item['part_id'];
                if ($id <= 0)
                    throw new RuntimeException('ID alat existing tidak ditemukan saat memperbarui data.');
                $sets = [];
                $args = [];
                if (!empty($item['kalibrator_update'])) {
                    $sets[] = 'kalibrator=?';
                    $args[] = $m['KALIBRATOR'];
                }
                if (!empty($item['jenis_update'])) {
                    $sets[] = 'jenis_laporan=?';
                    $args[] = $m['JENIS_PEMERIKSAAN'] === 'VERIFIKASI'?'verifikasi':'kalibrasi';
                }
                if ($sets) {
                    $args[] = (string)$id;
                    qaMigExec($db, 'UPDATE qa_part SET ' . implode(',', $sets) . ' WHERE id=?', $args);
                    if (!empty($item['kalibrator_update']))
                        $result['kalibrator_diperbarui']++;
                    if (!empty($item['jenis_update']))
                        $result['jenis_diperbarui']++;
                }
                continue;
            }
            qaMigExec($db, "INSERT INTO qa_alat_registry (no_part,status_alat,created_by) VALUES (?,'AKTIF',?)", [$no, $actor]);
            $regId = (int)$db->insert_id;
            $fields = ['alat_registry_id' => $regId, 'no_part' => $no, 'nama_part' => $m['NAMA_ALAT'], 'seksi_pemilik' => $m['SEKSI_PEMILIK'],
                'jenis_laporan' => $m['JENIS_PEMERIKSAAN'] === 'VERIFIKASI'?'verifikasi':'kalibrasi',
                'format_laporan' => $m['FORMAT_FINAL'], 'periode_kalibrasi' => (int)$m['PERIODE_KALIBRASI_HARI'],
                'status' => 'aktif', 'titik_standar_json' => '[]', 'spesifikasi' => $m['SPESIFIKASI'],
                'resolusi' => $m['RESOLUSI'], 'satuan' => $m['SATUAN'], 'merk' => $m['MERK'], 'buatan' => $m['BUATAN'],
                'type' => $m['TYPE'], 'fungsi' => $m['FUNGSI'], 'no_fa' => $m['NO_ASSET'], 'lokasi' => $m['LOKASI'],
                'metode_uji' => $m['METODE_KALIBRASI'], 'standar_uji' => $m['STANDAR_UJI'],
                'traceability' => $m['TRACEABILITY'], 'toleransi_global' => $m['STANDAR_TOLERANSI']];
            if ($m['KALIBRATOR'] !== '')
                $fields['kalibrator'] = $m['KALIBRATOR'];
            if ($m['JADWAL_KALIBRASI'] !== '')
                $fields['jadwal_kalibrasi'] = $m['JADWAL_KALIBRASI'];
            $names = array_keys($fields);
            $keys = '`' . implode('`,`', $names) . '`';
            qaMigExec($db, 'INSERT INTO qa_part (' . $keys . ') VALUES (' . implode(',', array_fill(0, count($names), '?')) . ')', array_map('strval', array_values($fields)));
            $partId = (int)$db->insert_id;
            qaMigExec($db, "UPDATE qa_alat_registry SET sumber_tabel='qa_part',sumber_id=? WHERE id=?", [(string)$partId, (string)$regId]);
            qaMigExec(
                $db,
                "INSERT INTO qa_migrasi_alat_meta (no_part,alat_id,tanggal_beli,status_standar,import_by) VALUES (?,?,?,'BELUM_DIISI',?)",
                [$no, (string)$partId, $m['TANGGAL_BELI'] === ''?null:$m['TANGGAL_BELI'], $actor]
            );
            $result['alat_baru']++;
        }
        // Baris registrasi berlebih di Excel tidak di-insert, karena hanya boleh 1.
        foreach ($data['history'] as $i => $h) {
            if ($plan['history'][$i]['status'] !== 'BARU') {
                $result['riwayat_dilewati']++;
                continue;
            }
            qaMigExec(
                $db,
                'INSERT INTO qa_migrasi_bbrau (no_part,tanggal,tipe_riwayat,jenis_check,hasil,petugas,diperiksa,pelaksanaan,keterangan,fingerprint,import_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                [$h['NO_ALAT'], $h['TANGGAL'], $h['TIPE_RIWAYAT'], $h['JENIS_CHECK'], $h['HASIL'], $h['PETUGAS'], $h['DIPERIKSA'], $h['PELAKSANAAN'], $h['KETERANGAN'], qaMigFingerprint($h), $actor]
            );
            $result['riwayat_baru']++;
        }
        // Rencana otomatis diletakkan sesudah baris Excel, namun laporan selalu
        // menampilkan REGISTRASI sebagai riwayat nomor 1.
        foreach (array_slice($plan['history'], count($data['history'])) as $item) {
            if ($item['status'] !== 'BARU' || empty($item['auto_reg']))
                continue;
            $h = $item['auto_reg'];
            qaMigExec($db,'INSERT INTO qa_migrasi_bbrau (no_part,tanggal,tipe_riwayat,jenis_check,hasil,petugas,diperiksa,pelaksanaan,keterangan,fingerprint,import_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                [$h['NO_ALAT'], $h['TANGGAL'], $h['TIPE_RIWAYAT'], $h['JENIS_CHECK'], $h['HASIL'], $h['PETUGAS'], $h['DIPERIKSA'], $h['PELAKSANAAN'], $h['KETERANGAN'], qaMigFingerprint($h), $actor]);
            $result['riwayat_baru']++;
        }
        return $result;
    } finally {
        $db->query('UNLOCK TABLES');
    }
}

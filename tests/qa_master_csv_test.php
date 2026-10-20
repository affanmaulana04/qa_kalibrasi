<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Run: C:/xampp/php/php.exe tests/qa_master_csv_test.php
// Every write is to connection-local TEMPORARY tables shadowing the real tables.
require __DIR__.'/../inc/inc_koneksi.php';
require __DIR__.'/../inc/qa_master_csv.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$konek->set_charset('utf8mb4');
foreach (['qa_part','qa_alat_registry','qa_seksi_master','qa_pengecekan','qa_alat_ng','qa_alat_rusak','qa_alat_discontinue','qa_alat_ditarik'] as $table) {
    $definition = $konek->query("SHOW CREATE TABLE `$table`")->fetch_row()[1];
    $konek->query(preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $definition));
}
$checks = 0;
function checkCsv($condition, $message) {
    global $checks;
    if (!$condition) throw new RuntimeException('FAIL: '.$message);
    $checks++;
    echo 'PASS: '.$message.PHP_EOL;
}
function rejectsCsv(callable $fn, string $message) {
    $failed = false;
    try { $fn(); } catch (Throwable $e) { $failed = true; }
    checkCsv($failed, $message);
}
qaCsvWrite($konek, 'INSERT INTO qa_seksi_master (nama_seksi) VALUES (?)', ['QA']);
qaCsvWrite($konek, "INSERT INTO qa_alat_registry (no_part,status_alat,sumber_tabel,created_by) VALUES (?,'AKTIF','qa_part','fixture')", ['EXIST001']);
$registryId = $konek->insert_id;
qaCsvWrite($konek, 'INSERT INTO qa_part (no_part,nama_part,seksi_pemilik,periode_kalibrasi,jadwal_kalibrasi,merk,alat_registry_id) VALUES (?,?,?,?,?,?,?)', ['EXIST001','Old name','QA','180','2026-10-31','Keep this brand',$registryId]);
$masterId = $konek->insert_id;
qaCsvWrite($konek, 'UPDATE qa_alat_registry SET sumber_id=? WHERE id=?', [$masterId,$registryId]);
$schema = qaCsvSchema($konek);
$header = 'no_part,nama_part,seksi_pemilik,periode_kalibrasi,jadwal_kalibrasi';
$csv = $header.",merk,no_seri\nEXIST001,Updated,QA,365,31-12-2026,,00991\n00012,New tool,QA,90,2028-02-29,Brand,00002\n";
$rows = qaCsvParse($csv, $schema);
$plan = qaCsvPlan($konek, $rows);
checkCsv($plan['counts']['new']===1 && $plan['counts']['update']===1 && !$plan['counts']['error'], 'preview identifies insert and update');
checkCsv(count(qaCsvFetch($konek, 'SELECT id FROM qa_part'))===1, 'preview does not write');
$result = qaCsvApply($konek, $rows, $plan['signature'], 'admin-test');
checkCsv($result['new']===1 && $result['update']===1, 'mixed batch saves');
$existing = qaCsvFetch($konek, 'SELECT * FROM qa_part WHERE id=?', [$masterId])[0];
checkCsv($existing['nama_part']==='Updated' && $existing['merk']==='Keep this brand' && $existing['no_seri']==='00991', 'updates metadata and preserves blank optional values and leading zeros');
$inserted = qaCsvFetch($konek, "SELECT * FROM qa_part WHERE no_part='00012'")[0];
$registry = qaCsvFetch($konek, 'SELECT * FROM qa_alat_registry WHERE id=?', [$inserted['alat_registry_id']])[0];
checkCsv($registry['sumber_tabel']==='qa_part' && (int)$registry['sumber_id']===(int)$inserted['id'], 'new tool has linked registry');
checkCsv($registry['hasil_terakhir']===null && count(qaCsvFetch($konek, 'SELECT id FROM qa_pengecekan'))===0, 'import does not fabricate calibration results or history');
checkCsv(qaCsvPlan($konek,$rows)['counts']['unchanged']===2, 'same upload is idempotent');
$quoted = qaCsvParse("\xEF\xBB\xBFno_alat;nama_alat;seksi;periode_hari;rencana_kalibrasi\nQ1;\"Gauge; line\nsecond \"\"quote\"\"\";QA;12;29/02/2028\n", $schema);
checkCsv(strpos($quoted[0]['data']['nama_part'], "line\nsecond")!==false && $quoted[0]['data']['jadwal_kalibrasi']==='2028-02-29', 'BOM, semicolon, aliases, escaped quotes and multiline fields');
$tab = qaCsvParse(str_replace(',', "\t", $header."\nT1,Tab tool,QA,2,2026-11-01\n"), $schema);
checkCsv($tab[0]['data']['no_part']==='T1', 'tab delimiter');
foreach ([',',';',"\t"] as $delimiter) {
    $sample = implode($delimiter,qaCsvTemplateHeaders())."\n".
        implode($delimiter,['SK gauge','SK-571','311099.1','~','1 Tahun','16 - 09 - 2026','QA'])."\n".str_repeat($delimiter,6)."\n";
    $formatted = qaCsvParse($sample,$schema);
    checkCsv(count($formatted)===1 && $formatted[0]['line']===2 && !$formatted[0]['errors'] &&
        $formatted[0]['data']['seksi_pemilik']==='QA' && $formatted[0]['data']['periode_kalibrasi']==='365' &&
        $formatted[0]['data']['jadwal_kalibrasi']==='2026-09-16' && $formatted[0]['data']['resolusi']==='~',
        'section column template parses with delimiter '.json_encode($delimiter));
}
$formattedPlan = qaCsvPlan($konek,$formatted);
qaCsvApply($konek,$formatted,$formattedPlan['signature'],'admin-test');
$savedFormat = qaCsvFetch($konek,"SELECT * FROM qa_part WHERE no_part='311099.1'")[0];
checkCsv($savedFormat['spesifikasi']==='SK-571' && $savedFormat['seksi_pemilik']==='QA' && (int)$savedFormat['periode_kalibrasi']===365, 'section template saves mapped fields and converted period');
checkCsv(qaCsvPeriod('180 Hari')==='180' && qaCsvPeriod('2 TAHUN')==='730' && qaCsvPeriod('0 Tahun')===null && qaCsvPeriod('999999999 Tahun')===null && qaCsvPeriod('1abc')===null, 'period units and bounds validated');
rejectsCsv(function()use($schema){qaCsvParse("SEKSI:,,,,,\n".implode(',',qaCsvTemplateHeaders())."\nA,B,C,D,1,2026-01-01",$schema);}, 'blank section heading rejected');
rejectsCsv(function()use($schema){qaCsvParse("SEKSI: QA,unexpected,,,,\n".implode(',',qaCsvTemplateHeaders())."\nA,B,C,D,1,2026-01-01",$schema);}, 'unexpected cells in section heading rejected');
$conflict=qaCsvParse("SEKSI: QA\n".$header."\nC,Conflict,OTHER,1,2026-01-01",$schema);
checkCsv(count($conflict[0]['errors'])>0,'conflicting per-row section rejected');
$unknownSection=qaCsvParse("SEKSI: UNKNOWN\n".implode(',',array_slice(qaCsvTemplateHeaders(),0,6))."\nA,B,C,D,1,2026-01-01",$schema);
checkCsv(qaCsvPlan($konek,$unknownSection)['counts']['error']===1,'unknown section heading cannot be saved');
$legacy=qaCsvParse("SEKSI: QA\n".implode(',',array_slice(qaCsvTemplateHeaders(),0,6))."\nLegacy,Spec,L1,~,180,2026-10-16",$schema);
checkCsv(!$legacy[0]['errors'] && $legacy[0]['data']['seksi_pemilik']==='QA','older section-heading CSV still supported');
qaCsvWrite($konek,'INSERT INTO qa_seksi_master (nama_seksi) VALUES (?)',['INJ']);
qaCsvWrite($konek,'INSERT INTO qa_seksi_master (nama_seksi) VALUES (?)',['TECH']);
$exampleCsv=implode(',',qaCsvTemplateHeaders())."\n";
foreach(qaCsvTemplateExamples() as $example) $exampleCsv.=implode(',',$example)."\n";
$exampleRows=qaCsvParse($exampleCsv,$schema);
$examplePlan=qaCsvPlan($konek,$exampleRows);
checkCsv($examplePlan['counts']['new']===3 && $examplePlan['counts']['error']===0 && array_column(array_column($exampleRows,'data'),'seksi_pemilik')===['QA','INJ','TECH'],'download examples support different sections per row');
$missingSection=qaCsvParse(implode(',',qaCsvTemplateHeaders())."\nTool,Spec,M1,~,180,2026-10-16,",$schema);
checkCsv(qaCsvPlan($konek,$missingSection)['counts']['error']===1,'section required on each row');
rejectsCsv(function()use($schema){qaCsvParse('no_part,nama_part', $schema);}, 'missing required headers rejected');
rejectsCsv(function()use($schema,$header){qaCsvParse($header.",status\nA,A,QA,1,2026-10-01,aktif",$schema);}, 'CSV cannot change lifecycle status');
rejectsCsv(function()use($schema,$header){qaCsvParse($header.",no_alat\nA,A,QA,1,2026-10-01,A",$schema);}, 'duplicate alias header rejected');
rejectsCsv(function()use($schema,$header){qaCsvParse($header."\nA,\"Unclosed,QA,1,2026-10-01",$schema);}, 'unclosed CSV quotes rejected');
rejectsCsv(function()use($schema,$header){qaCsvParse($header."\nA,\"Closed\"garbage,QA,1,2026-10-01",$schema);}, 'malformed quoted CSV rejected');
rejectsCsv(function()use($schema){qaCsvParse(str_repeat('x',QA_CSV_MAX_BYTES+1),$schema);}, 'oversized file rejected');
rejectsCsv(function()use($schema,$header){qaCsvParse($header."\n".str_repeat("A,Tool,QA,1,2026-10-01\n",1001),$schema);}, 'row limit enforced');
$bad = qaCsvParse($header."\nA,Tool,UNKNOWN,0,2026-02-29\na,Tool,QA,1,2026-10-01\n", $schema);
$badPlan = qaCsvPlan($konek,$bad);
checkCsv($badPlan['counts']['error']===2, 'invalid date, interval, section and duplicate number flagged');
rejectsCsv(function()use($konek,$bad,$badPlan){qaCsvApply($konek,$bad,$badPlan['signature'],'test');}, 'invalid batch cannot save any rows');
$long = qaCsvParse($header."\nL1,".str_repeat('x',101).",QA,1,2026-10-01",$schema);
checkCsv(count($long[0]['errors'])>0, 'field length validated before writes');
$staleRows=qaCsvParse($header."\nEXIST001,Changed again,QA,365,2026-12-31",$schema);
$stale=qaCsvPlan($konek,$staleRows);
qaCsvWrite($konek,'UPDATE qa_part SET lokasi=? WHERE id=?',['Changed concurrently',$masterId]);
rejectsCsv(function()use($konek,$staleRows,$stale){qaCsvApply($konek,$staleRows,$stale['signature'],'test');}, 'concurrent change requires a fresh preview');
qaCsvWrite($konek,"INSERT INTO qa_pengecekan (alat_id,no_part,nomor_pengecekan,created_by,status_validasi) VALUES (?,?,?,'test','menunggu_pemeriksa')",[$masterId,'EXIST001','TEST-PENDING']);
checkCsv(qaCsvPlan($konek,$staleRows)['counts']['error']===1, 'pending validation blocks metadata update');
qaCsvWrite($konek,"INSERT INTO qa_alat_ng (no_part,nama_part) VALUES (?,?)",['ARCH_NOTGOOD_20261015120000','Archived']);
checkCsv(qaCsvPlan($konek,qaCsvParse($header."\nARCH,Archived,QA,1,2026-10-01",$schema))['counts']['error']===1, 'legacy archived number cannot be recreated');
qaCsvWrite($konek,"INSERT INTO qa_alat_registry (no_part,status_alat) VALUES (?,'BLOKIR_PERMANEN')",['BLOCKED']);
checkCsv(qaCsvPlan($konek,qaCsvParse($header."\nBLOCKED,Blocked,QA,1,2026-10-01",$schema))['counts']['error']===1, 'blocked registry cannot be recreated');
// MyISAM cannot roll back a completed write. Force a failure on the second row
// and verify the error is raised; an operator must reconcile earlier saved rows.
$konek->query('ALTER TABLE qa_part ADD UNIQUE KEY test_unique_name (nama_part)');
$rollbackRows=qaCsvParse($header."\nROLL1,Collision test,QA,1,2026-10-01\nROLL2,Collision test,QA,1,2026-10-02",$schema);
$rollbackPlan=qaCsvPlan($konek,$rollbackRows);
rejectsCsv(function()use($konek,$rollbackRows,$rollbackPlan){qaCsvApply($konek,$rollbackRows,$rollbackPlan['signature'],'test');}, 'database failure aborts batch');
checkCsv(count(qaCsvFetch($konek,"SELECT id FROM qa_part WHERE no_part='ROLL1'"))===1 && count(qaCsvFetch($konek,"SELECT id FROM qa_part WHERE no_part='ROLL2'"))===0, 'MyISAM failure reports partial batch; first saved row remains');
echo $checks.' checks passed; temporary tables disappear when this connection closes.'.PHP_EOL;

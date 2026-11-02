<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// All test writes are connection-local temporary tables. Never import fixtures into real tables.
require __DIR__.'/../inc/inc_koneksi.php';
require __DIR__.'/../inc/qa_master_workbook.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$konek->set_charset('utf8mb4');
foreach (['qa_part','qa_alat_registry','qa_seksi_master','qa_pengecekan','qa_alat_ng','qa_alat_rusak','qa_alat_discontinue','qa_alat_ditarik'] as $table) {
    $ddl=$konek->query("SHOW CREATE TABLE `$table`")->fetch_row()[1];
    $konek->query(preg_replace('/^CREATE TABLE/','CREATE TEMPORARY TABLE',$ddl));
}
$migration=file_get_contents(__DIR__.'/../database/migrations/20261026_qa_master_excel.sql');
$konek->query($migration);
qaCsvWrite($konek,'INSERT INTO qa_seksi_master (nama_seksi) VALUES (?)',['QA']);
$schema=qaCsvSchema($konek); $checks=0;
function wbCheck($ok,$message) { global $checks; if (!$ok) throw new RuntimeException('FAIL: '.$message); $checks++; echo 'PASS: '.$message.PHP_EOL; }
function wbReject(callable $fn,$message) { $failed=false; try {$fn();}catch(Throwable $e){$failed=true;} wbCheck($failed,$message); }
function wbParse(string $bytes, array $schema): array {
    $path=tempnam(sys_get_temp_dir(),'qa-wb-test-'); file_put_contents($path,$bytes);
    try { return qaMasterWorkbookParse($path,$schema); } finally { unlink($path); }
}
function wbPatch(string $bytes, array $parts): string {
    $path=qaXlsxTemp();file_put_contents($path,$bytes);
    try {
        $zip=new PharData($path);
        foreach($parts as $name=>$content) $zip[$name]=is_callable($content)?$content($zip[$name]->getContent()):$content;
        unset($zip);
        return file_get_contents($path);
    } finally { if(is_file($path))unlink($path); }
}
function wbHeaderExampleRow(string $format,string $number): array {
    $fields=qaMasterHeaderFields(); unset($fields['format_laporan'],$fields['no_part']);
    $data=qaMasterExampleData($format,$number); $row=[$number];
    foreach (array_keys($fields) as $key) $row[]=$data[$key]??'';
    return $row;
}
$templates=[];
foreach (array_keys(qaMasterFormats()) as $format) {
    $bytes=qaXlsxWrite(qaMasterWorkbookSheets([$format])); $templates[$format]=$bytes;
    $rows=wbParse($bytes,$schema);
    wbCheck(count($rows)===1 && !$rows[0]['errors'],'Format '.$format.' complete template parses: '.implode('; ',$rows[0]['errors']));
    $plan=qaCsvPlan($konek,$rows);
    wbCheck($plan['counts']['new']===1 && !$plan['counts']['error'],'Format '.$format.' creates a valid plan');
    $rows[0]['data']['no_part']='TEST-'.$format;
    $plan=qaCsvPlan($konek,$rows); qaCsvApply($konek,$rows,$plan['signature'],'test');
    $saved=qaCsvFetch($konek,'SELECT * FROM qa_part WHERE no_part=?',['TEST-'.$format])[0];
    $body=json_decode($saved['titik_standar_json'],true);
    wbCheck(is_array($body) && count($body)>=1 && $saved['fungsi']==='Mengukur dimensi' && $saved['buatan']==='Jepang' && $saved['metode_uji']==='SOP-KAL-001' && $saved['no_seri']==='00001','Format '.$format.' saves header, body, footer and leading zeros');
    wbCheck(qaCsvPlan($konek,$rows)['counts']['unchanged']===1,'Format '.$format.' repeated import is idempotent');
}
wbCheck(count(qaCsvFetch($konek,'SELECT id FROM qa_pengecekan'))===0,'imports do not invent measurement/approval history');
wbReject(function()use($schema){wbParse(qaMasterWorkbookTemplate(),$schema);},'empty template has no tools to import');
$template=qaMasterWorkbookTemplate();
$universal=qaXlsxWrite(qaMasterWorkbookSheets());
$universalRows=wbParse($universal,$schema);
wbCheck(count($universalRows)===26 && count(array_filter($universalRows,static function($row){return $row['errors'];}))===0,'single universal download accepts all 26 mixed formats with irrelevant columns blank');
wbCheck(array_column(array_column($universalRows,'data'),'format_laporan')===array_keys(qaMasterFormats()),'universal examples cover every supported format in one workbook');
$universalPlan=qaCsvPlan($konek,$universalRows);
wbCheck($universalPlan['counts']['new']===26 && !$universalPlan['counts']['error'],'all formats can be planned together in one import');
qaCsvApply($konek,$universalRows,$universalPlan['signature'],'test');
foreach ($universalRows as $row) {
    $saved=qaCsvFetch($konek,'SELECT format_laporan,titik_standar_json FROM qa_part WHERE no_part=?',[$row['data']['no_part']])[0];
    wbCheck($saved['format_laporan']===$row['data']['format_laporan'] && json_decode($saved['titik_standar_json'],true)===json_decode($row['data']['titik_standar_json'],true),'universal batch preserves the correct body for Format '.$row['data']['format_laporan']);
}
wbCheck(qaCsvPlan($konek,$universalRows)['counts']['unchanged']===26,'universal mixed-format reupload is idempotent');
wbCheck(count(qaCsvFetch($konek,'SELECT id FROM qa_pengecekan'))===0,'universal batch does not create measurement history');
$wrongFormatRows=$universalRows;
$wrongFormatRows[0]['errors'][]='Titik Standar: kolom format lain tidak boleh diisi.';
$wrongFormatPlan=qaCsvPlan($konek,$wrongFormatRows);
wbReject(function()use($konek,$wrongFormatRows,$wrongFormatPlan){qaCsvApply($konek,$wrongFormatRows,$wrongFormatPlan['signature'],'test');},'invalid mixed-format batch cannot bypass validation');
$wrongColumns=qaMasterWorkbookSheets(['N']);
$foreignIndex=array_search('POSISI',$wrongColumns['Titik Standar'][0],true);
$wrongColumns['Titik Standar'][1][$foreignIndex]='GO';
wbCheck(count(wbParse(qaXlsxWrite($wrongColumns),$schema)[0]['errors'])>0,'universal body rejects populated columns belonging to another format');
$path=tempnam(sys_get_temp_dir(),'qa-wb-rule-');file_put_contents($path,$template);
try {
    $book=qaXlsxRead($path);
    wbCheck(count(array_intersect(array_values(qaMasterFormatSheetNames()),array_keys($book['sheets'])))===26,'downloaded workbook provides one clearly named sheet per supported format');
    wbCheck(!in_array('DATA | STANDAR REPEATABILITY (F)',$book['sheets']['F - Timbangan digital'][0]['cells'],true) && !in_array('TITIK | ZONA',$book['sheets']['F - Timbangan digital'][0]['cells'],true),'new template leaves Format F body parameters for first-time system entry');
    wbCheck(count($book['sheets']['A - Abrassion inspection'])===1 && count($book['sheets']['Y - Hygro Thermometer'])===1,'formats without real masterdata have no fabricated example row');
} finally { unlink($path); }
$sheetFormat='N'; $sheetDataFields=qaMasterDataFields(); unset($sheetDataFields['format_laporan'],$sheetDataFields['no_part']);
foreach (['std_sec1','std_sec3','batas_bawah','batas_atas'] as $key) unset($sheetDataFields[$key]);
$sheetHeaders=['NO. ALAT']; foreach ($sheetDataFields as $label) $sheetHeaders[]='DATA | '.$label;
foreach (qaMasterFormats()[$sheetFormat] as $key) $sheetHeaders[]='TITIK | '.qaMasterPointLabels()[$key];
$sheetData=qaMasterExampleData($sheetFormat,'SHEET-N-001'); unset($sheetData['format_laporan'],$sheetData['no_part'],$sheetData['std_sec1'],$sheetData['std_sec3'],$sheetData['batas_bawah'],$sheetData['batas_atas']);
$sheetPoint1=qaMasterExamplePoints($sheetFormat)[0]; $sheetPoint2=$sheetPoint1; $sheetPoint2['standar']='20';
$perFormatBytes=qaXlsxWrite([qaMasterFormatSheetNames()[$sheetFormat]=>[$sheetHeaders,array_merge(['SHEET-N-001'],array_values($sheetData),array_values($sheetPoint1)),array_merge(['SHEET-N-001'],array_fill(0,count($sheetDataFields),''),array_values($sheetPoint2))]]);
$perFormatRows=wbParse($perFormatBytes,$schema);
wbCheck(count($perFormatRows)===1 && !$perFormatRows[0]['errors'] && $perFormatRows[0]['data']['format_laporan']==='N' && count(json_decode($perFormatRows[0]['data']['titik_standar_json'],true))===2,'per-format sheet derives its format automatically and groups repeated tool rows as standard points');
$rows=wbParse($templates['N'],$schema);
$rows[0]['data']['no_part']='TEST-N'; $rows[0]['data']['merk']='';
$body=[['standar'=>'25','toleransi'=>'0.02'],['standar'=>'50','toleransi'=>'0.03']];
$rows[0]['data']['titik_standar_json']=json_encode($body);
$plan=qaCsvPlan($konek,$rows);qaCsvApply($konek,$rows,$plan['signature'],'test');
$saved=qaCsvFetch($konek,"SELECT * FROM qa_part WHERE no_part='TEST-N'")[0];
wbCheck(json_decode($saved['titik_standar_json'],true)===$body && $saved['merk']==='Contoh merk','update replaces complete body and preserves blank optional metadata');
wbCheck(qaMasterExcelDate('46321',true,false)==='2026-10-26' && qaMasterExcelDate('60',true,false)===null && qaMasterExcelDate('1',true,true)==='1904-01-02','Excel dates handle serials, leap-year bug and 1904 epoch');
wbReject(function(){qaMasterValidatePoints('X',[['line'=>2,'data'=>['bagian'=>'MAJU','standar'=>'1']]]);},'X rejects missing/out-of-order sections');
wbReject(function(){qaMasterValidatePoints('Y',[['line'=>2,'data'=>['bagian'=>'SUHU','standar'=>'20','toleransi'=>'-1']]]);},'Y rejects negative tolerance');
wbReject(function(){qaMasterValidatePoints('N',[['line'=>2,'data'=>['standar'=>'10','toleransi'=>'0.1','hasil'=>'9']]]);},'body rejects measurement-result columns');
wbReject(function(){qaMasterValidatePoints('T',[['line'=>2,'data'=>qaMasterExamplePoints('T')[0]]]);},'T requires paired GO and NOT GO for each group');
wbReject(function(){qaXlsxXml('<!DOCTYPE x [<!ENTITY x SYSTEM "file:///etc/passwd">]><x>&x;</x>');},'XML entities/DOCTYPE rejected');
wbReject(function()use($schema){wbParse('not a workbook',$schema);},'non-XLSX file rejected');
$dataFields=qaMasterDataFields(); $data=array_fill_keys(array_keys($dataFields),'');
$base=wbParse($templates['N'],$schema)[0]['data'];
foreach($data as $key=>$_) $data[$key]=$base[$key]??'';
$data['no_part']='ZERO-001';$data['tanggal_registrasi']='2026-10-26';
$data['jadwal_kalibrasi']='';$data['format_laporan']='N';
$pointHead=['NO. ALAT','STANDAR','TOLERANSI'];
function wbFixture(array $data, array $pointRows, array $dataFields, array $pointHead): string {
    return qaXlsxWrite(['Data Alat'=>[array_values($dataFields),array_values($data)],'Titik Standar'=>array_merge([$pointHead],$pointRows)]);
}
$fixture=wbFixture($data,[['ZERO-001','10','0.01']],$dataFields,$pointHead);
$parsed=wbParse($fixture,$schema);
wbCheck($parsed[0]['data']['jadwal_kalibrasi']==='2027-10-26' && $parsed[0]['data']['tanggal_registrasi']==='2026-10-26','registration date computes next schedule and is preserved');
$orphan=wbFixture($data,[['MISSING','10','0.01']],$dataFields,$pointHead);
wbReject(function()use($orphan,$schema){wbParse($orphan,$schema);},'orphan standard cannot be silently discarded');
$missing=wbFixture($data,[['ZERO-001','10','']],$dataFields,$pointHead);
wbCheck(count(wbParse($missing,$schema)[0]['errors'])>0,'missing body tolerance yields row error before write');
$duplicate=qaXlsxWrite(['Data Alat'=>[array_values($dataFields),array_values($data),array_values($data)],'Titik Standar'=>[$pointHead,['ZERO-001','10','0.01']]]);
$duplicateRows=wbParse($duplicate,$schema);
wbCheck(count($duplicateRows[1]['errors'])>0,'duplicate tool numbers across workbook rows rejected');
$invalid=$data;$invalid['periode_kalibrasi']='0';
$mixed=qaXlsxWrite(['Data Alat'=>[array_values($dataFields),array_values($data),array_values(array_replace($invalid,['no_part'=>'BAD-001']))], 'Titik Standar'=>[$pointHead,['ZERO-001','10','0.01'],['BAD-001','10','0.01']]]);
$mixedRows=wbParse($mixed,$schema);$mixedPlan=qaCsvPlan($konek,$mixedRows);
wbReject(function()use($konek,$mixedRows,$mixedPlan){qaCsvApply($konek,$mixedRows,$mixedPlan['signature'],'test');},'mixed invalid workbook cannot save valid rows');
wbCheck(count(qaCsvFetch($konek,"SELECT id FROM qa_part WHERE no_part IN ('ZERO-001','BAD-001')"))===0,'invalid batch wrote nothing');
$shared=wbPatch($templates['N'],[
    'xl/sharedStrings.xml'=>'<sst xmlns="'.QA_XLSX_NS.'" count="2" uniqueCount="1"><si><r><t>000</t></r><r><t>12</t></r></si></sst>',
    'xl/worksheets/sheet1.xml'=>static function($xml){
        $xml=preg_replace('/<c r="B2".*?<\/c>/','<c r="B2" t="s"><v>0</v></c>',$xml);
        $xml=preg_replace('/<c r="N2".*?<\/c>/','<c r="N2"><v>46321.5</v></c>',$xml);
        return preg_replace('/<c r="U2".*?<\/c>/','<c r="U2" s="5"><v>12</v></c>',$xml);
    },
    'xl/worksheets/sheet2.xml'=>static function($xml){return preg_replace('/<c r="A2".*?<\/c>/','<c r="A2" t="s"><v>0</v></c>',$xml);},
    'xl/styles.xml'=>static function($xml){
        $xml=str_replace('<fonts count="2">','<numFmts count="1"><numFmt numFmtId="164" formatCode="00000"/></numFmts><fonts count="2">',$xml);
        return str_replace(['<cellXfs count="5">','</cellXfs>'],['<cellXfs count="6">','<xf numFmtId="164" applyNumberFormat="1"/></cellXfs>'],$xml);
    }
]);
$sharedRows=wbParse($shared,$schema);
wbCheck(!$sharedRows[0]['errors'] && $sharedRows[0]['data']['no_part']==='00012' && $sharedRows[0]['data']['no_seri']==='00012' && $sharedRows[0]['data']['tanggal_registrasi']==='2026-10-26','Excel shared/rich strings, custom zero number formats, and numeric dates with time are supported');
wbCheck(qaMasterExcelDate('0',true,true)==='1904-01-01' && qaMasterExcelDate('2957004',true,true)===null,'1904 zero epoch and maximum date validated');
$formula=wbPatch($templates['N'],['xl/worksheets/sheet2.xml'=>static function($xml){return preg_replace('/<c r="B2".*?<\/c>/','<c r="B2"><f>1+1</f><v>2</v></c>',$xml);}]);
wbReject(function()use($formula,$schema){wbParse($formula,$schema);},'cached formula values rejected instead of importing stale calculations');
$badXml=wbPatch($templates['N'],['xl/worksheets/sheet1.xml'=>'<!DOCTYPE x [<!ENTITY a "bad">]><worksheet/>']);
wbReject(function()use($badXml,$schema){wbParse($badXml,$schema);},'DOCTYPE rejected through workbook parser');
wbReject(function()use($schema){qaCsvParse("no_part,nama_part,seksi_pemilik,periode_kalibrasi,jadwal_kalibrasi,titik_standar_json\nA,A,QA,1,2026-01-01,[]",$schema);},'legacy CSV cannot bypass structured body validation');
$headerPath=tempnam(sys_get_temp_dir(),'qa-header-'); file_put_contents($headerPath,$template);
try { $headerBook=qaXlsxRead($headerPath); } finally { unlink($headerPath); }
$headerCells=$headerBook['sheets']['A - Abrassion inspection'][0]['cells'];
$headerValues=wbHeaderExampleRow('A','NEW-HEADER-A');
$headerRows=wbParse(qaXlsxWrite(['A - Abrassion inspection'=>[$headerCells,$headerValues]]),$schema);
wbCheck(count($headerRows)===1 && !$headerRows[0]['errors'] && !isset($headerRows[0]['data']['titik_standar_json']),'header-only Excel parses without body points');
$headerKeys=array_values(array_diff(array_keys(qaMasterHeaderFields()),['format_laporan','no_part']));
$registrationIndex=array_search('tanggal_registrasi',$headerKeys,true)+1;
$scheduleIndex=array_search('jadwal_kalibrasi',$headerKeys,true)+1;
$blankDateValues=$headerValues;
$blankDateValues[$registrationIndex]='';
$blankDateValues[$scheduleIndex]='';
$blankDateRows=wbParse(qaXlsxWrite(['A - Abrassion inspection'=>[$headerCells,$blankDateValues]]),$schema);
$today=(new DateTimeImmutable('today',new DateTimeZone('Asia/Bangkok')))->format('Y-m-d');
$expectedSchedule=(new DateTimeImmutable($today))->modify('+'.qaCsvPeriod($blankDateRows[0]['data']['periode_kalibrasi']).' days')->format('Y-m-d');
wbCheck(!$blankDateRows[0]['errors'] && $blankDateRows[0]['data']['tanggal_registrasi']===$today && $blankDateRows[0]['data']['jadwal_kalibrasi']===$expectedSchedule,'blank registration and schedule use upload date and period: '.implode('; ',$blankDateRows[0]['errors']));
$optionalFields=array_keys(qaMasterHeaderFields());
$optionalFields=array_values(array_diff($optionalFields,['format_laporan','no_part']));
$spesifikasiIndex=array_search('spesifikasi',$optionalFields,true)+1;
$missingRequired=$headerValues; $missingRequired[$spesifikasiIndex]='';
$requiredRows=wbParse(qaXlsxWrite(['A - Abrassion inspection'=>[$headerCells,$missingRequired]]),$schema);
wbCheck((bool)$requiredRows[0]['errors'],'Excel rejects a field required by the matching registration form');
foreach (['S','Z','U','V','W'] as $optionalFormat) {
    $optionalName=qaMasterFormatSheetNames()[$optionalFormat];
    $optionalValues=wbHeaderExampleRow($optionalFormat,'OPTIONAL-'.$optionalFormat);
    foreach (['toleransi_global',...($optionalFormat==='U'||$optionalFormat==='V'||$optionalFormat==='W'?['resolusi','satuan','metode_uji','standar_uji','traceability']:[])] as $field) {
        $optionalValues[array_search($field,$headerKeys,true)+1]='';
    }
    $optionalRows=wbParse(qaXlsxWrite([$optionalName=>[$headerBook['sheets'][$optionalName][0]['cells'],$optionalValues]]),$schema);
    wbCheck(!$optionalRows[0]['errors'],'Format '.$optionalFormat.' accepts fields optional in registration form: '.implode('; ',$optionalRows[0]['errors']));
}
$headerPlan=qaCsvPlan($konek,$headerRows); wbCheck($headerPlan['counts']['new']===1,'header-only Excel plans a new registration');
qaCsvApply($konek,$headerRows,$headerPlan['signature'],'test');
$headerSaved=qaCsvFetch($konek,'SELECT titik_standar_json FROM qa_part WHERE no_part=?',['NEW-HEADER-A'])[0];
$headerEvents=qaCsvFetch($konek,'SELECT jenis_transaksi FROM qa_pengecekan WHERE no_part=?',['NEW-HEADER-A']);
wbCheck(empty($headerSaved['titik_standar_json']) && count($headerEvents)===1 && $headerEvents[0]['jenis_transaksi']==='registrasi_alat','header-only upload starts as Register and leaves body empty');
$reupload=$headerRows; $reupload[0]['data']['nama_part']='Should not overwrite existing tool';
$reuploadPlan=qaCsvPlan($konek,$reupload);
wbCheck($reuploadPlan['counts']['unchanged']===1 && !$reuploadPlan['counts']['error'],'existing tool is skipped on header-only reupload');
qaCsvApply($konek,$reupload,$reuploadPlan['signature'],'test');
$savedName=qaCsvFetch($konek,'SELECT nama_part FROM qa_part WHERE no_part=?',['NEW-HEADER-A'])[0]['nama_part'];
wbCheck($savedName!=='Should not overwrite existing tool','reupload leaves existing master unchanged');
$szTemplatePath=tempnam(sys_get_temp_dir(),'qa-sz-'); file_put_contents($szTemplatePath,$template);
try { $szBook=qaXlsxRead($szTemplatePath); } finally { unlink($szTemplatePath); }
$szSheets=[];
foreach (['S','Z'] as $format) {
    $name=qaMasterFormatSheetNames()[$format];
    $cells=$szBook['sheets'][$name];
    $example=wbHeaderExampleRow($format,'NEW-'.$format.'-001');
    $szSheets[$name]=[$cells[0]['cells'],$example];
}
$szRows=wbParse(qaXlsxWrite($szSheets),$schema);
wbCheck(count($szRows)===2 && !$szRows[0]['errors'] && !$szRows[1]['errors'] && array_column(array_column($szRows,'data'),'format_laporan')===['S','Z'],'new S and Z tabs import header metadata without body');
wbCheck(qaCsvPlan($konek,$szRows)['counts']['new']===2,'S and Z header-only imports plan new registrations');
wbReject(function(){qaMasterValidatePoints('S',[['line'=>2,'data'=>['arah'=>'NAIK','standar'=>'10','toleransi'=>'1']]]);},'S requires both NAIK and TURUN');
wbReject(function(){qaMasterValidatePoints('Z',[['line'=>2,'data'=>['setting'=>'abc']]]);},'Z requires numeric Setting');
echo $checks.' checks passed; only temporary tables were written.'.PHP_EOL;

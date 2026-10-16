<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../inc/inc_koneksi.php';
require __DIR__.'/../inc/qa_daily_report.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$konek->set_charset('utf8mb4');
// Connection-local temporary tables: production rows are never changed.
foreach (['qa_part','qa_alat_ng','qa_alat_rusak','qa_alat_discontinue','qa_alat_ditarik','qa_pengecekan','qa_validasi'] as $table) {
    $ddl=$konek->query("SHOW CREATE TABLE `$table`")->fetch_row()[1];
    $konek->query(preg_replace('/^CREATE TABLE/','CREATE TEMPORARY TABLE',$ddl));
}
function dailyTestInsert($table,$data) {
    global $konek;
    $stmt=$konek->prepare('INSERT INTO '.$table.' (`'.implode('`,`',array_keys($data)).'`) VALUES ('.implode(',',array_fill(0,count($data),'?')).')');
    $values=array_values($data);$stmt->bind_param(str_repeat('s',count($values)),...$values);$stmt->execute();$stmt->close();
    return $konek->insert_id;
}
$checks=0;
function dailyCheck($value,$message) { global $checks; if (!$value) throw new RuntimeException($message); $checks++; echo 'PASS: '.$message.PHP_EOL; }
$master=dailyTestInsert('qa_part',['no_part'=>'DAILY-01','nama_part'=>'Current name','seksi_pemilik'=>'NEW SECTION','format_laporan'=>'J','alat_registry_id'=>501]);
$snapshot=['format'=>'A','header'=>['nama_part'=>'Historic name','seksi_pemilik'=>'OLD SECTION','metode_uji'=>'Method at inspection','spesifikasi'=>'Historic spec'],
    'rows'=>[['go'=>['actual1'=>'4.2'],'not_go'=>['actual1'=>'4.4']]]];
$base=['alat_id'=>$master,'alat_registry_id'=>501,'no_part'=>'DAILY-01','nomor_pengecekan'=>'DAILY-CHECK','no_laporan'=>'REP-01',
    'jenis_transaksi'=>'update_hasil_kalibrasi','tanggal_pengecekan'=>'2026-10-15','tanggal_jadwal_saat_pengecekan'=>'2026-10-13','created_at'=>'2026-10-16 00:00:00',
    'created_by'=>'Tester','operator'=>'Operator','status_kalibrasi'=>'Good','status_validasi'=>'final_disetujui','periode_berikutnya'=>'2027-09-01',
    'hasil_json'=>json_encode($snapshot)];
$first=dailyTestInsert('qa_pengecekan',$base);
$second=dailyTestInsert('qa_pengecekan',array_replace($base,['created_at'=>'2026-10-17 23:59:59','nomor_pengecekan'=>'DAILY-SECOND']));
dailyTestInsert('qa_pengecekan',array_replace($base,['tanggal_pengecekan'=>'2026-10-14','created_at'=>'2026-10-15 00:00:00']));
dailyTestInsert('qa_pengecekan',array_replace($base,['tanggal_pengecekan'=>'2026-10-16','created_at'=>'2026-10-15 23:59:59']));
dailyTestInsert('qa_pengecekan',array_replace($base,['jenis_transaksi'=>'registrasi_alat']));
dailyTestInsert('qa_pengecekan',array_replace($base,['nomor_pengecekan'=>'QA-REG-LEGACY','jenis_transaksi'=>null]));
dailyTestInsert('qa_validasi',['pengecekan_id'=>$first,'tahap'=>'pemeriksa','keputusan'=>'setuju','nama_validator'=>'Reviewer','created_at'=>'2026-10-15 10:00:00']);
dailyTestInsert('qa_validasi',['pengecekan_id'=>$first,'tahap'=>'penyetuju','keputusan'=>'setuju','nama_validator'=>'Approver','created_at'=>'2026-10-15 11:00:00']);
dailyTestInsert('qa_validasi',['pengecekan_id'=>$first,'tahap'=>'penyetuju','keputusan'=>'tolak','nama_validator'=>'Approver','created_at'=>'2026-10-15 12:00:00']);
$records=qaDailyRecords($konek,'2026-10-15');
dailyCheck(count($records)===2,'calibration date used regardless of input date or planned date; registrations excluded');
dailyCheck(array_column($records,'id')===[$first,$second],'multiple transactions for the same tool are preserved in order');
$p=$records[0]['payload'];
dailyCheck($p['format']==='A' && $p['seksi_pemilik']==='OLD SECTION' && $p['nama_part']==='Historic name','snapshot format, section and name override current master');
dailyCheck($p['tanggal_periksa']==='2026-10-15' && $p['jadwal_berikutnya']==='2027-09-01','original inspection and next date preserved');
dailyCheck($p['pemeriksa']==='Reviewer' && $p['signature_pemeriksa'] && !$p['signature_penyetuju'] && $p['penyetuju']==='','latest rejection does not become an approval signature');
dailyCheck($p['hasil_array']===$snapshot['rows'],'all measurement rows preserved');
dailyTestInsert('qa_alat_rusak',['no_part'=>'ARCH_RUSAK_20261015000000','nama_part'=>'Archived tool','seksi_pemilik'=>'ARCH SECTION','format_laporan'=>'H','alat_registry_id'=>502]);
dailyTestInsert('qa_pengecekan',array_replace($base,['no_part'=>'ARCH','alat_registry_id'=>502,'alat_id'=>999,'hasil_json'=>'[{"hasil":"1"}]','status_kalibrasi'=>'RUSAK']));
$records=qaDailyRecords($konek,'2026-10-15');$archive=$records[1]['payload'];
dailyCheck($archive['format']==='H' && $archive['nama_part']==='Archived tool','legacy archived transaction resolves by stable registry identity');
dailyCheck($archive['jadwal_berikutnya']==='' && $archive['keputusan']==='RUSAK','inactive result does not invent a next calibration date');
dailyTestInsert('qa_pengecekan',array_replace($base,['hasil_json'=>json_encode(['format'=>'B','header'=>$snapshot['header'],'rows'=>[['hasil'=>1]]])]));
dailyCheck(count(qaDailyRecords($konek,'2026-10-15'))===3,'formats with tool number only in header are excluded');
dailyCheck(!qaDailyDate('2026-02-29') && qaDailyDate('2028-02-29') && !qaDailyDate('2026-1-01'),'dates validated strictly');
dailyCheck(qaDailyRecords($konek,'2026-10-17')===[],'empty date has no records');
echo $checks.' checks passed; all writes used temporary tables.'.PHP_EOL;

<?php
require_once __DIR__.'/qa_master_csv.php';
require_once __DIR__.'/qa_xlsx.php';

function qaMasterFormats(): array {
    // Matches the active qaRegSchema / qaCollectReg in cek_alat.php (not the legacy renderer).
    return [
        'A'=>['posisi','std_spesifikasi','std_no_alat','std_out','tol_out','std_pitch','tol_pitch'],
        'B'=>['tekanan','handle','baut','std_min','std_max'],
        'C'=>['setting','alat_std','toleransi'], 'D'=>['unit','mode','setting','standar'],
        'E'=>['standar','toleransi'], 'F'=>['zona','standar'], 'G'=>['standar','toleransi'],
        'H'=>['posisi_ukur','std_spesifikasi','std_no_alat','std_out','tol_out','range_out','std_pitch','tol_pitch','range_pitch'],
        'I'=>['spesifikasi_alat','std_spesifikasi','std_no_alat'], 'J'=>['spesifikasi','spesifikasi_alat_verifikasi'],
        'K'=>['nilai_ditentukan','toleransi_persen'], 'L'=>['uji_skala_mode','arah','applied_value','toleransi'],
        'M'=>['jenis','std_mm','toleransi_axis','std_angle','toleransi_angle'], 'N'=>['standar','toleransi'],
        'O'=>['spesifikasi','tol_plus','tol_minus','go_std','go_tol_near','go_tol_far','notgo_std','notgo_tol'],
        'P'=>['group_id','type_ukuran','alat_nama','alat_no','std_label','toleransi_mode','toleransi','tol_plus','tol_minus'],
        'Q'=>['bagian','std_spesifikasi','std_no_alat','std','tol_minus','tol_plus'],
        'R'=>['bagian','standar','toleransi'],
        'T'=>['group_id','spesifikasi_standar','resolusi_standar','no_alat_standar','posisi','standar','tol_minus','tol_plus'],
        'U'=>['standar'], 'V'=>['bagian','standar'], 'W'=>['standar'],
        'X'=>['bagian','standar'], 'Y'=>['bagian','standar','toleransi']
    ];
}
function qaMasterFormatLabels(): array {
    return [
        'A'=>'Gauge (GO / NOT GO)','B'=>'Air Driver','C'=>'Temperatur','D'=>'Multimeter',
        'E'=>'Thermometer (T1 / T2)','F'=>'Timbangan','G'=>'Air Torque','H'=>'Thread Plug Gauge',
        'I'=>'Gauge ATAS / BAWAH','J'=>'Verifikasi Radius Gauge','K'=>'Voltage Ultrasonic',
        'L'=>'Pressure Gauge','M'=>'Profile Projector','N'=>'Acuan Utama / Toleransi Dinamis',
        'O'=>'Snap Gauge','P'=>'SK Gauge','Q'=>'Seration / Plug','R'=>'Range 3 Titik',
        'T'=>'GO / NOT GO + Toleransi','U'=>'Laporan Kalibrasi',
        'V'=>'Laporan Kalibrasi (Outside / Inside / Depth)','W'=>'Laporan Pemeriksaan Sudut',
        'X'=>'Kestabilan / Pembacaan Maju / Mundur','Y'=>'Pemeriksaan Suhu / Humidity'
    ];
}
function qaMasterFormatDisplay(string $format): string {
    return $format.' — '.(qaMasterFormatLabels()[$format]??'');
}
function qaMasterFormatCode(string $value): string {
    $value=mb_strtoupper(trim($value),'UTF-8');
    return preg_match('/^([A-RT-Y])(?:\s|$|[-—:])/', $value, $match) ? $match[1] : $value;
}
function qaMasterFormatSheetNames(): array {
    return [
        'A'=>'A - Gauge','B'=>'B - Air Driver','C'=>'C - Temperatur','D'=>'D - Multimeter',
        'E'=>'E - Thermometer','F'=>'F - Timbangan','G'=>'G - Air Torque','H'=>'H - Thread Plug Gauge',
        'I'=>'I - Gauge Atas Bawah','J'=>'J - Radius Gauge','K'=>'K - Voltage Ultrasonic',
        'L'=>'L - Pressure Gauge','M'=>'M - Profile Projector','N'=>'N - Toleransi Dinamis',
        'O'=>'O - Snap Gauge','P'=>'P - SK Gauge','Q'=>'Q - Seration Plug','R'=>'R - Range 3 Titik',
        'T'=>'T - GO NOT GO Toleransi','U'=>'U - Kalibrasi','V'=>'V - Outside Inside Depth',
        'W'=>'W - Pemeriksaan Sudut','X'=>'X - Maju Mundur','Y'=>'Y - Suhu Humidity'
    ];
}
function qaMasterDataFields(): array {
    return [
        'format_laporan'=>'FORMAT LAPORAN','no_part'=>'NO. ALAT','nama_part'=>'NAMA ALAT',
        'spesifikasi'=>'SPESIFIKASI','resolusi'=>'RESOLUSI','fungsi'=>'FUNGSI','no_fa'=>'NO. ASET',
        'lokasi'=>'LOKASI','merk'=>'MERK','type'=>'TIPE','buatan'=>'BUATAN','seksi_pemilik'=>'SEKSI',
        'periode_kalibrasi'=>'PERIODE KALIBRASI (HARI)','tanggal_registrasi'=>'TANGGAL REGISTRASI',
        'jadwal_kalibrasi'=>'RENCANA KALIBRASI','metode_uji'=>'METODE KALIBRASI',
        'toleransi_global'=>'STANDAR TOLERANSI','standar_uji'=>'STANDAR UJI','satuan'=>'SATUAN',
        'traceability'=>'TRACEABILITY','no_seri'=>'NO. SERI',
        'std_sec1'=>'STANDAR REPEATABILITY (F)','std_sec3'=>'STANDAR CENTER LOADING (F)',
        'batas_bawah'=>'BATAS BAWAH (F)','batas_atas'=>'BATAS ATAS (F)'
    ];
}
function qaMasterPointLabels(): array {
    return [
        'posisi'=>'POSISI','std_spesifikasi'=>'STD SPESIFIKASI','std_no_alat'=>'STD NO ALAT',
        'std_out'=>'STD OUT','tol_out'=>'TOL OUT','std_pitch'=>'STD PITCH','tol_pitch'=>'TOL PITCH',
        'tekanan'=>'TEKANAN','handle'=>'POSISI HANDLE','baut'=>'BAUT SETTING','std_min'=>'STANDAR MIN','std_max'=>'STANDAR MAX',
        'setting'=>'SETTING','alat_std'=>'ALAT STD','toleransi'=>'TOLERANSI','unit'=>'UNIT','mode'=>'MODE',
        'standar'=>'STANDAR','zona'=>'ZONA','posisi_ukur'=>'POSISI UKUR','range_out'=>'RANGE OUT','range_pitch'=>'RANGE PITCH',
        'spesifikasi_alat'=>'SPESIFIKASI ALAT','spesifikasi'=>'SPESIFIKASI','spesifikasi_alat_verifikasi'=>'SPESIFIKASI ALAT VERIFIKASI',
        'nilai_ditentukan'=>'NILAI DITENTUKAN','toleransi_persen'=>'TOLERANSI PERSEN','uji_skala_mode'=>'MODE UJI SKALA',
        'arah'=>'UJI SKALA','applied_value'=>'APPLIED VALUE','jenis'=>'JENIS','std_mm'=>'STD AXIS MM',
        'toleransi_axis'=>'TOLERANSI AXIS','std_angle'=>'STD ANGLE','toleransi_angle'=>'TOLERANSI ANGLE',
        'tol_plus'=>'TOL PLUS','tol_minus'=>'TOL MINUS','go_std'=>'GO ALAT','go_tol_near'=>'GO TOL NEAR',
        'go_tol_far'=>'GO TOL FAR','notgo_std'=>'NOT GO ALAT','notgo_tol'=>'NOT GO TOL',
        'group_id'=>'GRUP','type_ukuran'=>'TYPE DAN UKURAN','alat_nama'=>'NAMA ALAT STANDAR','alat_no'=>'NO ALAT YANG DIPAKAI',
        'std_label'=>'STD UKURAN','toleransi_mode'=>'MODE TOLERANSI','bagian'=>'BAGIAN','std'=>'STANDAR UKURAN',
        'spesifikasi_standar'=>'SPESIFIKASI STANDAR','resolusi_standar'=>'RESOLUSI STANDAR','no_alat_standar'=>'NOMOR ALAT STANDAR'
    ];
}
function qaMasterRequired(string $format): array {
    $required = [
        'A'=>'spesifikasi,toleransi_global,metode_uji,standar_uji,traceability',
        'B'=>'spesifikasi,toleransi_global,satuan,metode_uji,standar_uji,traceability',
        'C'=>'spesifikasi,resolusi,toleransi_global,satuan,metode_uji,standar_uji,traceability',
        'D'=>'spesifikasi,resolusi,toleransi_global,metode_uji,standar_uji,traceability',
        'E'=>'spesifikasi,resolusi,toleransi_global,satuan,metode_uji,standar_uji,traceability',
        'F'=>'spesifikasi,resolusi,satuan,metode_uji,standar_uji,traceability',
        'G'=>'spesifikasi,resolusi,toleransi_global,satuan,metode_uji,standar_uji,traceability',
        'H'=>'spesifikasi,traceability','I'=>'metode_uji,standar_uji,traceability','J'=>'spesifikasi,metode_uji',
        'K'=>'spesifikasi,resolusi,toleransi_global,metode_uji,standar_uji,traceability',
        'L'=>'spesifikasi,resolusi,toleransi_global,satuan,metode_uji,standar_uji,traceability',
        'M'=>'spesifikasi,resolusi,toleransi_global,satuan,metode_uji,standar_uji,traceability',
        'N'=>'spesifikasi,resolusi,toleransi_global,satuan,metode_uji,standar_uji,traceability',
        'O'=>'satuan,metode_uji,standar_uji,traceability','P'=>'metode_uji,standar_uji,traceability',
        'Q'=>'spesifikasi,metode_uji,standar_uji,traceability','R'=>'resolusi,metode_uji,standar_uji,traceability',
        'T'=>'spesifikasi,metode_uji,standar_uji,traceability',
        'U'=>'resolusi,toleransi_global,satuan,metode_uji,standar_uji,traceability',
        'V'=>'resolusi,toleransi_global,satuan,metode_uji,standar_uji,traceability',
        'W'=>'resolusi,toleransi_global,metode_uji,standar_uji,traceability',
        'X'=>'spesifikasi,resolusi,toleransi_global,satuan,metode_uji,standar_uji,traceability',
        'Y'=>'spesifikasi,resolusi,metode_uji,standar_uji,traceability'
    ];
    return array_merge(['format_laporan','no_part','nama_part','seksi_pemilik','periode_kalibrasi','lokasi'], explode(',', $required[$format] ?? ''));
}
function qaMasterExamplePoints(string $format): array {
    $row = array_fill_keys(qaMasterFormats()[$format], '1');
    $row = array_merge($row, array_intersect_key([
        'spesifikasi'=>'10 mm','std_spesifikasi'=>'Gauge block','std_no_alat'=>'STD-001','alat_std'=>'STD-001',
        'std_out'=>'10','tol_out'=>'0.01','std_pitch'=>'8','tol_pitch'=>'0.01','range_out'=>'0.01','range_pitch'=>'0.01',
        'tekanan'=>'6','handle'=>'1','baut'=>'M6','std_min'=>'9','std_max'=>'11','setting'=>'10','unit'=>'V','mode'=>'DC',
        'standar'=>'10','toleransi'=>'0.01','zona'=>'B','spesifikasi_alat'=>'10 mm','spesifikasi_alat_verifikasi'=>'Radius gauge 10 mm',
        'nilai_ditentukan'=>'10','toleransi_persen'=>'1','uji_skala_mode'=>'arah','arah'=>'NAIK','applied_value'=>'10',
        'std_mm'=>'10','std_angle'=>'90','toleransi_axis'=>'0.01','toleransi_angle'=>'1',
        'tol_plus'=>'0.01','tol_minus'=>'-0.01','go_std'=>'10','go_tol_near'=>'-0.01','go_tol_far'=>'-0.02','notgo_std'=>'10','notgo_tol'=>'0.01',
        'group_id'=>$format.'1','type_ukuran'=>'SK-571 / 10 mm','alat_nama'=>'Outside micrometer','alat_no'=>'STD-001',
        'std_label'=>'10','toleransi_mode'=>'pm','std'=>'10','spesifikasi_standar'=>'0-25 mm',
        'resolusi_standar'=>'0.001','no_alat_standar'=>'STD-001'
    ], $row));
    $groups = ['A'=>['posisi',['GO','NOT GO']], 'H'=>['posisi_ukur',['GO','NOT GO','PLUG']],
        'M'=>['jenis',['AXIS','ANGLE']], 'Q'=>['bagian',['OUT','PITCH']], 'R'=>['bagian',['Ø OUT','Ø DALAM','HEIGHT']],
        'T'=>['posisi',['GO','NOT GO']], 'V'=>['bagian',['OUTSIDE','INSIDE','DEPTH']],
        'X'=>['bagian',['KESTABILAN','MAJU','MUNDUR']], 'Y'=>['bagian',['SUHU','HUMIDITY']]];
    if (!isset($groups[$format])) return [$row];
    [$key,$values] = $groups[$format]; $rows = [];
    foreach ($values as $value) {
        $entry = array_merge($row, [$key=>$value]);
        if ($format==='M') foreach ($value==='AXIS'?['std_angle','toleransi_angle']:['std_mm','toleransi_axis'] as $blank) $entry[$blank]='';
        $rows[]=$entry;
    }
    return $rows;
}
function qaMasterExampleData(string $format, string $number): array {
    $dataFields = qaMasterDataFields();
    $data = array_fill_keys(array_keys($dataFields),'');
    $data = array_merge($data, ['format_laporan'=>qaMasterFormatDisplay($format),'no_part'=>$number,'nama_part'=>'Contoh '.$format.' - '.qaMasterFormatLabels()[$format],
        'spesifikasi'=>'0-150 mm','resolusi'=>'0.01','fungsi'=>'Mengukur dimensi','no_fa'=>'FA-001','lokasi'=>'Lab QA',
        'merk'=>'Contoh merk','type'=>'TIPE-001','buatan'=>'Jepang','seksi_pemilik'=>'QA','periode_kalibrasi'=>'365',
        'tanggal_registrasi'=>date('Y-m-d'),'jadwal_kalibrasi'=>'','metode_uji'=>'SOP-KAL-001',
        'toleransi_global'=>'0.01','standar_uji'=>'Gauge block','satuan'=>'mm','traceability'=>'STD-001','no_seri'=>'00001']);
    if ($format==='F') $data=array_merge($data,['std_sec1'=>'100','std_sec3'=>'100','batas_bawah'=>'2 x Res','batas_atas'=>'3 x Res']);
    return $data;
}
function qaMasterWorkbookSheets(array $formats = []): array {
    if (!$formats) $formats=array_keys(qaMasterFormats());
    foreach ($formats as $format) if (!isset(qaMasterFormats()[$format])) throw new RuntimeException('Format laporan tidak dikenal.');
    $dataFields=qaMasterDataFields(); $labels=qaMasterPointLabels();
    $pointKeys=array_keys($labels);
    $dataRows=[array_values($dataFields)];
    $points = [array_merge(['NO. ALAT'],array_map(static function($key) use ($labels){return $labels[$key];},$pointKeys))];
    foreach ($formats as $format) {
        $number='CONTOH-'.$format.'-001';
        $dataRows[]=array_values(qaMasterExampleData($format,$number));
        foreach (qaMasterExamplePoints($format) as $point) $points[]=array_merge([$number],array_map(static function($key)use($point){return $point[$key]??'';},$pointKeys));
    }
    $guide = [
        ['BAGIAN / FORMAT','PETUNJUK / KOLOM WAJIB DATA ALAT','KOLOM TITIK STANDAR'],
        ['Template universal','Satu Excel untuk semua format A–R dan T–Y. Pilih FORMAT LAPORAN pada setiap baris Data Alat. Satu file boleh memuat alat dengan format berbeda.'],
        ['Contoh','Ada contoh untuk seluruh 24 format. Ganti atau hapus SEMUA contoh CONTOH-... yang tidak dipakai pada Data Alat DAN Titik Standar sebelum upload. Jangan menyisakan titik standar tanpa alat.'],
        ['Data Alat','Satu baris per alat; isi FORMAT LAPORAN sesuai jenis laporan. Nama seksi harus sudah terdaftar. Lihat daftar kolom wajib setiap format di bawah.'],
        ['Titik Standar','Satu baris per titik; NO. ALAT menghubungkan ke Data Alat. Isi hanya kolom milik format alat tersebut; kosongkan kolom format lain. Urutan baris dipertahankan. Isi konfigurasi standar registrasi, bukan hasil ukur.'],
        ['Tanggal','Isi TANGGAL REGISTRASI (tanggal acuan) dengan YYYY-MM-DD, DD-MM-YYYY, atau tanggal Excel. Jika RENCANA KALIBRASI kosong, dihitung tanggal registrasi + periode (hari). Isi salah satunya.'],
        ['Nomor','Gunakan format sel Text untuk nomor alat/aset/seri agar nol di depan tetap tersimpan. Rumus tidak didukung: tempel sebagai nilai.'],
        ['Pembaruan','No. Alat sama memperbarui master. Kolom metadata opsional kosong mempertahankan nilai lama. Titik Standar mengganti seluruh konfigurasi body alat tersebut.'],
        ['Registrasi','Upload menyimpan konfigurasi master agar siap dipakai pada Update Hasil Kalibrasi. Nomor laporan, hasil ukur, keputusan, petugas, dan persetujuan mengikuti proses aplikasi; upload tidak membuat histori hasil kalibrasi.'],
        ['Foto','Foto alat tetap diunggah melalui fitur foto aplikasi. Gambar yang ditempel di Excel tidak diimpor.'],
        ['Batas','Maksimal 2 MB, 1.000 alat dan 20.000 titik standar per file. Gunakan Excel Workbook (.xlsx), tanpa password atau macro.'],
        ['Nilai pilihan','A: GO/NOT GO. H: GO/NOT GO/PLUG. F: ZONA B/A. L: MODE UJI SKALA arah/nomor (UJI SKALA NAIK/TURUN atau 1,2,...). M: JENIS AXIS/ANGLE; isi kolom yang sesuai jenis.'],
        ['Kelompok','P: GRUP menyatukan TYPE DAN UKURAN + alat standar. MODE TOLERANSI pm (TOLERANSI) atau range (TOL PLUS/MINUS). T: tiap GRUP memiliki GO dan NOT GO; ulangi identitas alat standar pada kedua baris.'],
        ['Bagian','Q: OUT/PITCH. R: Ø OUT/Ø DALAM/HEIGHT. V: OUTSIDE/INSIDE/DEPTH. X: KESTABILAN, MAJU, MUNDUR berurutan, masing-masing 1–20 baris. Y: SUHU kemudian HUMIDITY, masing-masing 1–20 baris dengan standar/toleransi numerik; toleransi tidak negatif.'],
        ['Khusus F','Isi standar repeatability, center loading, batas bawah/atas pada Data Alat. ZONA B = bawah; A = atas. Semua STANDAR di body harus diisi.']
    ];
    foreach (qaMasterFormats() as $format=>$keys) {
        $guide[]=['Format '.qaMasterFormatDisplay($format),
            implode(', ',array_map(static function($k)use($dataFields){return $dataFields[$k]??$k;},qaMasterRequired($format))),
            implode(', ',array_map(static function($k)use($labels){return $labels[$k];},$keys))];
    }
    return ['Data Alat'=>$dataRows,'Titik Standar'=>$points,'Panduan'=>$guide];
}
function qaMasterSampleRecords(mysqli $db): array {
    $fields=['format_laporan','no_part','nama_part','spesifikasi','resolusi','fungsi','no_fa','lokasi','merk','type','buatan','seksi_pemilik','periode_kalibrasi','tanggal_registrasi','jadwal_kalibrasi','metode_uji','toleransi_global','standar_uji','satuan','traceability','no_seri'];
    $rows=qaCsvFetch($db,'SELECT '.implode(',',array_map(static fn($field)=>'`'.$field.'`',$fields)).' FROM qa_part WHERE format_laporan IS NOT NULL AND format_laporan <> \'\' ORDER BY id');
    $samples=[]; $scores=[];
    foreach ($rows as $row) {
        $format=qaMasterFormatCode((string)$row['format_laporan']);
        if (!isset(qaMasterFormats()[$format]) || trim((string)$row['no_part'])==='' || trim((string)$row['nama_part'])==='') continue;
        $score=0;
        if (!preg_match('/\b(test|testing|demo|contoh)\b/i',(string)$row['nama_part'].' '.$row['no_part'])) $score+=5;
        foreach (['spesifikasi','resolusi','lokasi','merk','metode_uji','standar_uji'] as $field) if (trim((string)$row[$field])!=='') $score++;
        if (!isset($scores[$format]) || $score>$scores[$format]) { $samples[$format]=$row; $scores[$format]=$score; }
    }
    return $samples;
}
function qaMasterWorkbookTemplate(array $samples = []): string {
    $start=[
        ['TEMPLATE UPLOAD MASTER ALAT','Mulai dari sini — cukup ikuti 5 langkah berikut'],
        ['1. Pilih tab format','Buka tab sesuai jenis alat, misalnya A - Gauge atau F - Timbangan. Jangan mengisi format lain.'],
        ['2. Lihat contoh di baris 2','Baris kuning adalah contoh. Kolom awal memakai masterdata asli jika format tersebut sudah punya alat; kolom kosong dan titik standar dilengkapi ilustrasi.'],
        ['3. Isi mulai baris 3','Isi NO. ALAT, seluruh kolom DATA, dan kolom TITIK pada baris baru. Format ditentukan otomatis dari nama tab.'],
        ['4. Tambah titik','Jika satu alat punya beberapa titik, tambah baris dan ulangi NO. ALAT. Kolom DATA pada baris lanjutan boleh dikosongkan.'],
        ['5. Hapus contoh, lalu upload','Hapus baris kuning contoh di setiap tab yang diisi sebelum upload. Tab yang tidak dipakai boleh dibiarkan. Simpan sebagai .xlsx lalu upload.'],
        ['PENTING','Jangan mengganti nama tab atau judul kolom. Satu file boleh berisi banyak alat dan beberapa format.'],
        ['Tanggal','Isi TANGGAL REGISTRASI. RENCANA KALIBRASI boleh kosong agar dihitung otomatis dari tanggal + periode hari.'],
        ['Perbarui alat','Titik Standar yang di-upload mengganti seluruh titik lama alat tersebut, jadi sertakan semuanya.'],
    ];
    $sheets=['Mulai di Sini'=>$start]; $options=['Mulai di Sini'=>['widths'=>[26,105],'wrap'=>true]];
    $dataFields=qaMasterDataFields(); unset($dataFields['format_laporan'],$dataFields['no_part']);
    foreach (qaMasterFormatSheetNames() as $format=>$sheet) {
        $formatData=$dataFields;
        if ($format!=='F') foreach (['std_sec1','std_sec3','batas_bawah','batas_atas'] as $key) unset($formatData[$key]);
        $headers=['NO. ALAT'];
        foreach ($formatData as $label) $headers[]='DATA | '.$label;
        foreach (qaMasterFormats()[$format] as $key) $headers[]='TITIK | '.qaMasterPointLabels()[$key];
        $sample=$samples[$format]??null;
        $number=$sample ? (string)$sample['no_part'] : 'CONTOH-'.$format.'-001';
        $example=qaMasterExampleData($format,$number);
        if ($sample) foreach ($formatData as $key=>$_) if (isset($sample[$key]) && trim((string)$sample[$key])!=='') $example[$key]=(string)$sample[$key];
        $exampleRow=[$number];
        foreach (array_keys($formatData) as $key) $exampleRow[]=$example[$key]??'';
        $examplePoint=qaMasterExamplePoints($format)[0];
        foreach (qaMasterFormats()[$format] as $key) $exampleRow[]=$examplePoint[$key]??'';
        $sheets[$sheet]=[$headers,$exampleRow,array_fill(0,count($headers),'')];
        $options[$sheet]=['freezeColumns'=>1,'exampleRows'=>[2]];
    }
    return qaXlsxWrite($sheets,[],$options);
}

function qaMasterPerFormatSheets(array $book): array {
    $sheets=$book['sheets'];
    if (isset($sheets['Data Alat']) || isset($sheets['Titik Standar'])) return $book;
    $known=array_flip(array_merge(['Mulai di Sini'],array_values(qaMasterFormatSheetNames())));
    foreach ($sheets as $name=>$rows) if (!isset($known[$name]) && count($rows)>1) throw new RuntimeException('Sheet tidak dikenal atau namanya berubah: '.$name.'. Gunakan nama tab dari template.');
    $dataFields=qaMasterDataFields(); $pointLabels=qaMasterPointLabels();
    $dataRows=[['line'=>1,'cells'=>array_values($dataFields),'numeric'=>[]]];
    $pointRows=[['line'=>1,'cells'=>array_merge(['NO. ALAT'],array_values($pointLabels)),'numeric'=>[]]];
    $outputLine=2;
    foreach (qaMasterFormatSheetNames() as $format=>$sheetName) {
        $sheet=$sheets[$sheetName]??[];
        if (!$sheet) continue;
        $header=array_shift($sheet); $formatData=$dataFields; unset($formatData['format_laporan'],$formatData['no_part']);
        if ($format!=='F') foreach (['std_sec1','std_sec3','batas_bawah','batas_atas'] as $key) unset($formatData[$key]);
        $expected=['NO. ALAT']; foreach ($formatData as $label) $expected[]='DATA | '.$label;
        foreach (qaMasterFormats()[$format] as $key) $expected[]='TITIK | '.$pointLabels[$key];
        $actual=[]; foreach ($header['cells'] as $value) $actual[]=$value;
        $marked=($actual[0]??'')==='JENIS BARIS';
        if ($actual!==($marked?array_merge(['JENIS BARIS'],$expected):$expected)) throw new RuntimeException('Sheet '.$sheetName.': judul kolom berubah. Gunakan template terbaru.');
        $tools=[];
        $hasDataRows=false;
        foreach ($sheet as $candidate) if (empty($candidate['example'])) { $hasDataRows=true; break; }
        foreach ($sheet as $row) {
            if (!empty($row['example'])) {
                if ($hasDataRows) throw new RuntimeException($sheetName.' baris '.$row['line'].': hapus baris kuning contoh sebelum upload.');
                continue;
            }
            if ($marked) {
                $kind=mb_strtoupper(trim((string)($row['cells'][0]??'')),'UTF-8');
                if (str_starts_with($kind,'CONTOH')) continue;
                if ($kind!=='' && $kind!=='DATA') throw new RuntimeException($sheetName.' baris '.$row['line'].': JENIS BARIS hanya boleh DATA atau CONTOH.');
            }
            $values=[]; for ($i=0;$i<count($expected);$i++) $values[$i]=$row['cells'][$i+($marked?1:0)]??'';
            $no=strtoupper(trim($values[0]));
            if ($no==='') throw new RuntimeException($sheetName.' baris '.$row['line'].': NO. ALAT wajib diisi.');
            if (!isset($tools[$no])) $tools[$no]=['data'=>array_fill_keys(array_keys($formatData),''),'numeric'=>[],'points'=>[],'line'=>$row['line']];
            $offset=1;
            foreach (array_keys($formatData) as $key) {
                $value=$values[$offset++];
                if ($value!=='' && $tools[$no]['data'][$key]!=='' && $tools[$no]['data'][$key]!==$value) throw new RuntimeException($sheetName.' baris '.$row['line'].': DATA '.($formatData[$key]).' berbeda untuk NO. ALAT '.$no.'.');
                if ($value!=='') {
                    $tools[$no]['data'][$key]=$value;
                    if (isset($row['numeric'][$offset-1+($marked?1:0)])) $tools[$no]['numeric'][$key]=true;
                }
            }
            $point=[]; foreach (qaMasterFormats()[$format] as $key) $point[$key]=$values[$offset++];
            $tools[$no]['points'][]=['data'=>$point,'line'=>$row['line']];
        }
        foreach ($tools as $no=>$tool) {
            $data=['format_laporan'=>$format,'no_part'=>$no]+$tool['data'];
            $numeric=[]; foreach (array_keys($data) as $index=>$key) if (isset($tool['numeric'][$key])) $numeric[$index]=true;
            $dataRows[]=['line'=>$outputLine,'cells'=>array_values($data),'numeric'=>$numeric];
            foreach ($tool['points'] as $entry) {
                $point=['no_part'=>$no]+array_fill_keys(array_keys($pointLabels),'');
                foreach ($entry['data'] as $key=>$value) $point[$key]=$value;
                $pointRows[]=['line'=>$outputLine,'cells'=>array_values($point),'numeric'=>[]];
            }
            $outputLine++;
            if (count($dataRows)>1001 || count($pointRows)>20001) throw new RuntimeException('Maksimal 1.000 alat dan 20.000 baris titik standar per file.');
        }
    }
    $book['sheets']['Data Alat']=$dataRows; $book['sheets']['Titik Standar']=$pointRows;
    return $book;
}
function qaMasterHeaders(array $row, array $fields, string $sheet): array {
    $aliases=[];
    foreach ($fields as $key=>$label) { $aliases[qaCsvHeader($label)]=$key; $aliases[qaCsvHeader($key)]=$key; }
    $headers=[];
    foreach ($row['cells'] as $column=>$value) {
        if ($value==='') continue;
        $normal=qaCsvHeader($value);
        if (!isset($aliases[$normal])) throw new RuntimeException($sheet.': kolom tidak dikenal: '.$value.'. Gunakan template.');
        $key=$aliases[$normal];
        if (in_array($key,$headers,true)) throw new RuntimeException($sheet.': kolom berulang: '.$value.'.');
        $headers[$column]=$key;
    }
    if (!$headers || $row['line']!==1) throw new RuntimeException($sheet.': judul kolom harus ada di baris pertama.');
    return $headers;
}
function qaMasterCells(array $row, array $headers, string $sheet): array {
    foreach ($row['cells'] as $index=>$value) if ($value!=='' && !isset($headers[$index])) throw new RuntimeException($sheet.' baris '.$row['line'].': ada isi pada kolom tanpa judul.');
    $data=[];
    foreach ($headers as $index=>$key) $data[$key]=$row['cells'][$index]??'';
    return $data;
}
function qaMasterExcelDate(string $value, bool $numeric, bool $date1904): ?string {
    if ($numeric && preg_match('/^[0-9]+(?:\.[0-9]+)?$/',$value)) {
        $serial=(int)$value;
        if ($serial<($date1904?0:1) || $serial>($date1904?2957003:2958465) || (!$date1904 && $serial===60)) return null;
        $base=new DateTimeImmutable($date1904?'1904-01-01':($serial<60?'1899-12-31':'1899-12-30'));
        return $base->modify('+'.$serial.' days')->format('Y-m-d');
    }
    return qaCsvDate($value);
}
function qaMasterValidatePoints(string $format, array $entries): array {
    $keys=qaMasterFormats()[$format]??[]; $rows=[];
    foreach ($entries as $entry) {
        $data=$entry['data']; $line=$entry['line']; $point=[];
        foreach ($data as $key=>$value) if ($key!=='no_part' && $value!=='' && !in_array($key,$keys,true)) throw new RuntimeException('Titik Standar baris '.$line.': kolom '.$key.' bukan milik Format '.$format.'.');
        foreach ($keys as $key) $point[$key]=$data[$key]??'';
        $required=$keys;
        if ($format==='M') $required=strtoupper($point['jenis'])==='AXIS'?['jenis','std_mm','toleransi_axis']:['jenis','std_angle','toleransi_angle'];
        if ($format==='P') $required=array_merge(['group_id','type_ukuran','alat_nama','alat_no','std_label','toleransi_mode'],strtolower($point['toleransi_mode'])==='range'?['tol_plus','tol_minus']:['toleransi']);
        foreach ($required as $key) if ($point[$key]==='') throw new RuntimeException('Titik Standar baris '.$line.': '.$key.' wajib diisi untuk Format '.$format.'.');
        $choices=['A'=>['posisi'=>['GO','NOT GO']], 'H'=>['posisi_ukur'=>['GO','NOT GO','PLUG']],
            'F'=>['zona'=>['B','A']], 'M'=>['jenis'=>['AXIS','ANGLE']], 'Q'=>['bagian'=>['OUT','PITCH']],
            'R'=>['bagian'=>['Ø OUT','Ø DALAM','HEIGHT']], 'T'=>['posisi'=>['GO','NOT GO']],
            'V'=>['bagian'=>['OUTSIDE','INSIDE','DEPTH']], 'X'=>['bagian'=>['KESTABILAN','MAJU','MUNDUR']],
            'Y'=>['bagian'=>['SUHU','HUMIDITY']], 'L'=>['uji_skala_mode'=>['ARAH','NOMOR']], 'P'=>['toleransi_mode'=>['PM','RANGE']]];
        foreach ($choices[$format]??[] as $key=>$values) {
            $value=mb_strtoupper($point[$key],'UTF-8');
            if (!in_array($value,$values,true)) throw new RuntimeException('Titik Standar baris '.$line.': '.$key.' harus '.implode(' / ',$values).'.');
            $point[$key]=in_array($key,['toleransi_mode','uji_skala_mode'],true)?strtolower($value):$value;
        }
        if ($format==='L') {
            $point['arah']=strtoupper($point['arah']);
            if (($point['uji_skala_mode']==='arah' && !in_array($point['arah'],['NAIK','TURUN'],true)) ||
                ($point['uji_skala_mode']==='nomor' && $point['arah']!==(string)(count($rows)+1))) throw new RuntimeException('Titik Standar baris '.$line.': UJI SKALA harus NAIK/TURUN atau nomor urut mulai 1.');
            if ($rows && $point['uji_skala_mode']!==$rows[0]['uji_skala_mode']) throw new RuntimeException('Mode Uji Skala harus sama untuk satu alat.');
        }
        if ($format==='M') {
            if ($point['jenis']==='AXIS') { $point['std_x']=$point['std_y']=$point['std_mm']; $point['toleransi_x']=$point['toleransi_y']=$point['toleransi_axis']; }
            else { $point['std_degree']=$point['std_angle']; $point['toleransi_sudut']=$point['toleransi_angle']; }
        }
        if (in_array($format,['X','Y'],true)) {
            foreach ($format==='Y'?['standar','toleransi']:['standar'] as $key) {
                $value=str_replace(',','.',$point[$key]);
                if (!preg_match('/^[+-]?(?:\d+\.?\d*|\.\d+)$/',$value) || !is_finite((float)$value) || ($key==='toleransi' && (float)$value<0)) throw new RuntimeException('Titik Standar baris '.$line.': '.$key.' harus angka valid'.($key==='toleransi'?' tidak negatif':'').'.');
            }
        }
        $rows[]=$point;
    }
    if (!$rows) throw new RuntimeException('Minimal satu baris Titik Standar wajib diisi.');
    if ($format==='O' && count($rows)!==1) throw new RuntimeException('Format O harus memiliki tepat satu baris konfigurasi GO / NOT GO.');
    if (in_array($format,['X','Y'],true)) {
        $groups=$format==='X'?['KESTABILAN','MAJU','MUNDUR']:['SUHU','HUMIDITY']; $counts=array_fill_keys($groups,0); $last=0;
        foreach ($rows as $row) {
            $index=array_search($row['bagian'],$groups,true);
            if ($index<$last || $index>$last+1 || ++$counts[$row['bagian']]>20) throw new RuntimeException('Format '.$format.': bagian harus berurutan, masing-masing 1–20 baris.');
            $last=$index;
        }
        if (in_array(0,$counts,true)) throw new RuntimeException('Format '.$format.': isi semua bagian '.implode(', ',$groups).'.');
    }
    if (in_array($format,['P','T'],true)) {
        $groups=[];
        foreach ($rows as $row) {
            $group=$row['group_id']; $identity=array_intersect_key($row,array_flip($format==='P'?['type_ukuran','alat_nama','alat_no']:['spesifikasi_standar','resolusi_standar','no_alat_standar']));
            if (isset($groups[$group]) && $groups[$group]['identity']!==$identity) throw new RuntimeException('Identitas alat standar dalam GRUP '.$group.' harus sama.');
            $groups[$group]['identity']=$identity; $groups[$group]['rows'][]=$row;
        }
        if ($format==='T') foreach ($groups as $group=>$entries) {
            $positions=array_column($entries['rows'],'posisi'); sort($positions);
            if ($positions!==['GO','NOT GO']) throw new RuntimeException('Format T: GRUP '.$group.' harus tepat dua baris GO dan NOT GO.');
        }
    }
    return $rows;
}
function qaMasterWorkbookParse(string $path, array $schema): array {
    foreach (['fungsi','buatan','tanggal_registrasi','titik_standar_json'] as $field) if (!isset($schema[$field])) throw new RuntimeException('Struktur Excel belum siap: kolom '.$field.'. Jalankan migrasi 20261026_qa_master_excel.sql.');
    $book=qaMasterPerFormatSheets(qaXlsxRead($path)); $sheets=$book['sheets'];
    foreach (['Data Alat','Titik Standar'] as $name) if (empty($sheets[$name]) || count($sheets[$name])<2) throw new RuntimeException('Sheet '.$name.' harus berisi judul dan data. Gunakan template Excel.');
    $dataHeaders=qaMasterHeaders(array_shift($sheets['Data Alat']),qaMasterDataFields(),'Data Alat');
    $pointHeaders=qaMasterHeaders(array_shift($sheets['Titik Standar']),['no_part'=>'NO. ALAT']+qaMasterPointLabels(),'Titik Standar');
    if (!in_array('no_part',$pointHeaders,true)) throw new RuntimeException('Titik Standar: kolom NO. ALAT wajib ada.');
    $points=[];
    foreach ($sheets['Titik Standar'] as $row) {
        $data=qaMasterCells($row,$pointHeaders,'Titik Standar'); $no=strtoupper($data['no_part']);
        if ($no==='') throw new RuntimeException('Titik Standar baris '.$row['line'].': NO. ALAT wajib diisi.');
        $points[$no][]=['line'=>$row['line'],'data'=>$data];
    }
    $rows=[]; $numbers=[];
    foreach ($sheets['Data Alat'] as $row) {
        $data=qaMasterCells($row,$dataHeaders,'Data Alat'); $format=qaMasterFormatCode($data['format_laporan']??'');
        $data['format_laporan']=$format;
        $no=strtoupper($data['no_part']??''); $numbers[$no]=true; $errors=[];
        if (!isset(qaMasterFormats()[$format])) { $rows[]=['line'=>$row['line'],'data'=>['no_part'=>$no],'errors'=>['Format laporan harus A–R atau T–Y.']]; continue; }
        foreach (qaMasterRequired($format) as $key) if (($data[$key]??'')==='') $errors[]=(qaMasterDataFields()[$key]??$key).' wajib diisi untuk Format '.$format.'.';
        $registration='';
        foreach (['tanggal_registrasi','jadwal_kalibrasi'] as $field) if (($data[$field]??'')!=='') {
            $column=array_search($field,$dataHeaders,true);
            $date=qaMasterExcelDate($data[$field],isset($row['numeric'][$column]),$book['date1904']);
            if ($date===null) $errors[]=(qaMasterDataFields()[$field]).' tidak valid.';
            else $data[$field]=$date;
        }
        if (($data['tanggal_registrasi']??'')!=='') $registration=$data['tanggal_registrasi'];
        $period=qaCsvPeriod($data['periode_kalibrasi']??'');
        if (($data['jadwal_kalibrasi']??'')==='' && qaCsvDate($registration)!==null && $period!==null) {
            $next=(new DateTimeImmutable($registration))->modify('+'.$period.' days')->format('Y-m-d');
            if (strlen($next)!==10) $errors[]='Rencana kalibrasi di luar rentang tanggal database.';
            else $data['jadwal_kalibrasi']=$next;
        }
        if (($data['jadwal_kalibrasi']??'')==='') $errors[]='Isi TANGGAL REGISTRASI atau RENCANA KALIBRASI.';
        $extra=array_intersect_key($data,array_flip(['std_sec1','std_sec3','batas_bawah','batas_atas']));
        if ($format==='F') $data['toleransi_global']=($extra['batas_bawah']??'').'|'.($extra['batas_atas']??'');
        foreach (array_merge(['tanggal_registrasi'],array_keys($extra)) as $key) unset($data[$key]);
        // Reuse CSV metadata validation and database limits without permitting raw JSON uploads.
        $stream=fopen('php://temp','r+');
        $data['jadwal_kalibrasi']=$data['jadwal_kalibrasi']??'';
        foreach (qaCsvRequired() as $required) $data[$required]=$data[$required]??'';
        fputcsv($stream,array_keys($data),',','"',''); fputcsv($stream,array_values($data),',','"',''); rewind($stream);
        $parsed=qaCsvParse(stream_get_contents($stream),$schema)[0]; fclose($stream);
        $parsed['line']=$row['line']; $parsed['errors']=array_merge($parsed['errors'],$errors);
        if ($registration!=='' && qaCsvDate($registration)!==null) $parsed['data']['tanggal_registrasi']=$registration;
        try {
            $body=qaMasterValidatePoints($format,$points[$no]??[]);
            if ($format==='F') {
                foreach (['std_sec1','std_sec3','batas_bawah','batas_atas'] as $key) if (($extra[$key]??'')==='') throw new RuntimeException('Data Alat Format F: '.(qaMasterDataFields()[$key]).' wajib diisi.');
                array_unshift($body,['meta'=>'F','std_sec1'=>$extra['std_sec1'],'std_sec3'=>$extra['std_sec3']]);
                $tolerance=$extra['batas_bawah'].'|'.$extra['batas_atas'];
                if (mb_strlen($tolerance,'UTF-8')>100) throw new RuntimeException('Gabungan batas bawah/atas Format F maksimal 100 karakter.');
                $parsed['data']['toleransi_global']=$tolerance;
            } else {
                foreach ($extra as $value) if ($value!=='') throw new RuntimeException('Kolom khusus Format F hanya boleh diisi untuk Format F.');
            }
            $json=json_encode($body,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
            if (strlen($json)>2097152) throw new RuntimeException('Konfigurasi titik standar satu alat maksimal 2 MB.');
            // JSON escaping preserves Greek/CJK labels even on the existing latin1 longtext column.
            $parsed['data']['titik_standar_json']=json_encode($body,JSON_THROW_ON_ERROR);
        } catch (RuntimeException $e) { $parsed['errors'][]=$e->getMessage(); }
        $rows[]=$parsed;
    }
    foreach ($points as $no=>$entries) if (!isset($numbers[$no])) throw new RuntimeException('Titik Standar baris '.$entries[0]['line'].': No. Alat '.$no.' tidak ada di Data Alat.');
    $seen=[];
    foreach ($rows as &$row) {
        $no=$row['data']['no_part'];
        if (isset($seen[$no])) $row['errors'][]='Nomor alat berulang di Data Alat (baris '.$seen[$no].').';
        $seen[$no]=$row['line'];
    }
    unset($row);
    return $rows;
}

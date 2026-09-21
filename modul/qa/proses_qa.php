<?php
session_start();

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

include "../../inc/inc_koneksi.php";
header('Content-Type: application/json; charset=utf-8');

$action = isset($_GET['action']) ? $_GET['action'] : '';
if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['status'=>'error', 'message'=>'Silakan login kembali.']); exit;
}
// Helper Form B digabung di sini; tidak memerlukan file helper terpisah.
// Array standar lama tetap didukung, termasuk JSON lama yang ter-escape dua kali.
function qa_b_decode($raw) {
    if (is_array($raw)) return $raw;
    $data=json_decode((string)$raw,true);
    if (!is_array($data)) $data=json_decode(stripslashes((string)$raw),true);
    return is_array($data) ? $data : [];
}
function qa_b_standards($rows) {
    if (!is_array($rows) || !$rows || array_keys($rows)!==range(0,count($rows)-1)) return [false,'Minimal satu titik standar Form B wajib diisi.',[]];
    $clean=[];
    foreach ($rows as $i=>$row) {
        if (!is_array($row)) return [false,'Titik standar tidak valid.',[]];
        $item=[];
        foreach (['tekanan','handle','baut','std_min','std_max','std1','std2','std3'] as $key) {
            $value=$row[$key] ?? null;
            if (!is_scalar($value) || trim((string)$value)==='') return [false,'Lengkapi standar Form B baris '.($i+1).'.',[]];
            $item[$key]=trim((string)$value);
        }
        foreach (['std_min','std_max','std1','std2','std3'] as $key) {
            if (!is_numeric($item[$key]) || !is_finite((float)$item[$key])) return [false,'Batas torsi harus berupa angka valid.',[]];
        }
        if ((float)$item['std_min']>(float)$item['std_max']) return [false,'Std. Min tidak boleh lebih besar dari Std. Max.',[]];
        $clean[]=$item;
    }
    return [true,'',$clean];
}
function qa_validate_form_b_rows($rows, $master) {
    [$ok,$message,$standards]=qa_b_standards($master);
    if (!$ok) return [false,'Standar alat belum lengkap: '.$message,[],false];
    if (!is_array($rows) || count($rows)!==count($standards) || array_keys($rows)!==range(0,count($rows)-1)) return [false,'Semua titik standar wajib diuji.',[],false];
    $clean=[]; $overall=true;
    foreach ($standards as $i=>$standard) {
        $row=$rows[$i];
        if (!is_array($row)) return [false,'Hasil uji tidak valid.',[],false];
        // Parameter standar selalu berasal dari master, bukan perubahan di browser.
        foreach ($standard as $key=>$value) {
            if (!isset($row[$key]) || !is_scalar($row[$key]) || trim((string)$row[$key])!==$value) return [false,'Standar berubah. Muat ulang alat sebelum menguji.',[],false];
        }
        $value=$row['hasil'] ?? null;
        if (!is_scalar($value) || trim((string)$value)==='' || !is_numeric($value) || !is_finite((float)$value)) return [false,'Hasil kalibrasi baris '.($i+1).' wajib berupa angka.',[],false];
        $actual=(float)$value;
        $pass=$actual>=(float)$standard['std_min'] && $actual<=(float)$standard['std_max'];
        $standard['hasil']=trim((string)$value);
        $standard['status']=$pass?'OK':'BURUK';
        $overall=$overall && $pass; $clean[]=$standard;
    }
    return [true,'',$clean,$overall];
}

/* ============================================================
 * HELPER - INSERT HANYA KE KOLOM YANG MEMANG ADA DI DATABASE
 * Tidak mengubah / ALTER struktur database.
 * ============================================================ */
function qa_get_columns($konek, $table) {
    $table = preg_replace('/[^A-Za-z0-9_]/', '', $table);
    $cols = [];
    $q = mysqli_query($konek, "SHOW COLUMNS FROM `$table`");
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $cols[] = $r['Field'];
        }
    }
    return $cols;
}

function qa_safe_insert($konek, $table, $values) {
    $existing = qa_get_columns($konek, $table);
    $cols = [];
    $vals = [];

    foreach ($values as $column => $value) {
        if (in_array($column, $existing, true)) {
            $safeColumn = preg_replace('/[^A-Za-z0-9_]/', '', $column);
            $cols[] = "`$safeColumn`";
            $vals[] = "'" . mysqli_real_escape_string($konek, (string)$value) . "'";
        }
    }

    if (!$cols) {
        return [false, "Tidak ditemukan kolom yang cocok pada tabel `$table`."];
    }

    $sql = "INSERT INTO `$table` (" . implode(', ', $cols) . ")
            VALUES (" . implode(', ', $vals) . ")";

    $ok = mysqli_query($konek, $sql);
    return [$ok, $ok ? '' : mysqli_error($konek)];
}


/* ============================================================
 * HELPER RESPONSE & FORM A / FORM B
 * ============================================================ */
function json_response($status, $message = '', $extra = []) {
    $response = array_merge(['status' => $status, 'message' => $message], is_array($extra) ? $extra : []);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function qa_merge_form_a($old_json, $post) {
    $old = json_decode((string)$old_json, true);
    if (!is_array($old)) $old = [];
    $data = (isset($old[0]) && is_array($old[0])) ? $old[0] : $old;
    $get = function($key, $fallback = '') use ($post) {
        return isset($post[$key]) ? trim((string)$post[$key]) : $fallback;
    };

    $data['format'] = 'A';

    $oldStd = is_array($data['alat_standar'] ?? null) ? $data['alat_standar'] : [];
    $oldGoStd = is_array($oldStd['go'] ?? null) ? $oldStd['go'] : [];
    $oldNgStd = is_array($oldStd['not_go'] ?? null) ? $oldStd['not_go'] : [];

    // Backward compatible: kalau data lama hanya punya satu alat standar,
    // gunakan sebagai fallback untuk GO dan NOT GO.
    $legacyNo = $oldStd['no_alat'] ?? '';
    $legacySpek = $oldStd['spesifikasi'] ?? '';

    $goStdNo = $get('go_std_no_alat', $get('std_no_alat', $oldGoStd['no_alat'] ?? $legacyNo));
    $goStdSpek = $get('go_std_spesifikasi', $get('std_spesifikasi', $oldGoStd['spesifikasi'] ?? $legacySpek));
    $ngStdNo = $get('notgo_std_no_alat', $oldNgStd['no_alat'] ?? $legacyNo);
    $ngStdSpek = $get('notgo_std_spesifikasi', $oldNgStd['spesifikasi'] ?? $legacySpek);

    $data['alat_standar'] = [
        'no_alat' => $goStdNo !== '' ? $goStdNo : $ngStdNo,
        'spesifikasi' => $goStdSpek !== '' ? $goStdSpek : $ngStdSpek,
        'traceability' => $get('traceability', $oldStd['traceability'] ?? ''),
        'go' => ['no_alat' => $goStdNo, 'spesifikasi' => $goStdSpek],
        'not_go' => ['no_alat' => $ngStdNo, 'spesifikasi' => $ngStdSpek]
    ];

    $go = is_array($data['go'] ?? null) ? $data['go'] : [];
    $ng = is_array($data['not_go'] ?? null) ? $data['not_go'] : [];

    $data['go'] = [
        'alat_standar' => ['no_alat' => $goStdNo, 'spesifikasi' => $goStdSpek],
        'std_out' => $get('go_std_out', $go['std_out'] ?? ''),
        'tol_out' => $get('go_tol_out', $go['tol_out'] ?? ''),
        'range_out' => $get('go_range_out', $go['range_out'] ?? ''),
        'std_pitch' => $get('go_std_pitch', $go['std_pitch'] ?? ''),
        'tol_pitch' => $get('go_tol_pitch', $go['tol_pitch'] ?? ''),
        'range_pitch' => $get('go_range_pitch', $go['range_pitch'] ?? ''),
        'out' => [
            'dep' => $get('go_out_dep', $go['out']['dep'] ?? ''),
            'ten' => $get('go_out_ten', $go['out']['ten'] ?? ''),
            'bel' => $get('go_out_bel', $go['out']['bel'] ?? '')
        ],
        'pitch' => [
            'dep' => $get('go_pitch_dep', $go['pitch']['dep'] ?? ''),
            'ten' => $get('go_pitch_ten', $go['pitch']['ten'] ?? ''),
            'bel' => $get('go_pitch_bel', $go['pitch']['bel'] ?? '')
        ]
    ];

    $data['not_go'] = [
        'alat_standar' => ['no_alat' => $ngStdNo, 'spesifikasi' => $ngStdSpek],
        'std_out' => $get('notgo_std_out', $ng['std_out'] ?? ''),
        'tol_out' => $get('notgo_tol_out', $ng['tol_out'] ?? ''),
        'range_out' => $get('notgo_range_out', $ng['range_out'] ?? ''),
        'std_pitch' => $get('notgo_std_pitch', $ng['std_pitch'] ?? ''),
        'tol_pitch' => $get('notgo_tol_pitch', $ng['tol_pitch'] ?? ''),
        'range_pitch' => $get('notgo_range_pitch', $ng['range_pitch'] ?? ''),
        'out' => [
            'dep' => $get('notgo_out_dep', $ng['out']['dep'] ?? ''),
            'ten' => $get('notgo_out_ten', $ng['out']['ten'] ?? ''),
            'bel' => $get('notgo_out_bel', $ng['out']['bel'] ?? '')
        ],
        'pitch' => [
            'dep' => $get('notgo_pitch_dep', $ng['pitch']['dep'] ?? ''),
            'ten' => $get('notgo_pitch_ten', $ng['pitch']['ten'] ?? ''),
            'bel' => $get('notgo_pitch_bel', $ng['pitch']['bel'] ?? '')
        ]
    ];

    foreach (['kode_varian','warna','kep','periksa_berikut','keterangan'] as $key) {
        if (array_key_exists($key, $post)) $data[$key] = trim((string)$post[$key]);
        elseif (!isset($data[$key])) $data[$key] = '';
    }

    return json_encode([$data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function qa_is_active_status($status) {
    return in_array(strtoupper(trim((string)$status)), ['AKTIF','ACTIVE','1'], true);
}

/* ============================================================
 * CEK ALAT
 * ============================================================ */
if ($action == 'cek_alat') {
    try {
        $no_part = isset($_POST['no_part']) ? mysqli_real_escape_string($konek, trim($_POST['no_part'])) : '';
        
        $query = mysqli_query($konek, "SELECT * FROM qa_part
                 WHERE no_part = '$no_part'
                 ORDER BY
                    CASE WHEN UPPER(TRIM(COALESCE(status, ''))) IN ('AKTIF', 'ACTIVE', '1') THEN 0 ELSE 1 END,
                    CASE WHEN UPPER(TRIM(COALESCE(format_laporan, ''))) IN ('A', 'FORM A') THEN 0 ELSE 1 END,
                    id DESC
                 LIMIT 1");

        if ($query && mysqli_num_rows($query) > 0) {
            $data_alat = mysqli_fetch_assoc($query);

            // Nomor alat ditemukan. Sekarang pastikan statusnya aktif.
            // Validasi di PHP agar nilai seperti "Aktif ", "AKTIF", atau "active"
            // tetap dikenali sebagai status aktif.
            $statusAlat = strtoupper(trim((string)($data_alat['status'] ?? '')));
            if (!in_array($statusAlat, ['AKTIF', 'ACTIVE', '1'], true)) {
                echo json_encode([
                    'found' => false,
                    'message' => 'Nomor alat ditemukan, tetapi status alat tidak aktif.'
                ]);
                exit;
            }

            // Schema DB sekarang tidak wajib punya kolom `spesifikasi`.
            // Untuk frontend Form A, gunakan `ukuran` sebagai sumber Spesifikasi.
            if (!isset($data_alat['spesifikasi'])) {
                $data_alat['spesifikasi'] = isset($data_alat['ukuran']) ? $data_alat['ukuran'] : '';
            }

            if (in_array(strtoupper(trim($data_alat['format_laporan'] ?? '')), ['B','FORM B'], true)) {
                $standards = qa_b_decode($data_alat['titik_standar_json'] ?? '[]');
                foreach (($standards[0]['_meta'] ?? []) as $key=>$value) {
                    if (in_array($key, ['metode_uji','standar_uji','traceability'], true)) $data_alat[$key]=$value;
                }
                $data_alat['titik_standar_json'] = json_encode($standards);
            }
            $history = [];
            
            $q_hist = mysqli_query($konek, "SELECT * FROM qa_pengecekan WHERE no_part = '$no_part' ORDER BY tanggal_pengecekan DESC LIMIT 4");
            if ($q_hist) {
                while ($row = mysqli_fetch_assoc($q_hist)) {
                    $history[] = [
                        'tanggal'  => $row['tanggal_pengecekan'],
                        'status'   => $row['status_kalibrasi'],
                        'seksi_qa' => $row['seksi_qa']
                    ];
                }
            }
            echo json_encode(['found' => true, 'data' => $data_alat, 'history' => $history]);
        } else {
            echo json_encode(['found' => false]);
        }
    } catch (Exception $e) { 
        echo json_encode(['found' => false, 'message' => $e->getMessage()]); 
    }
    exit;
}

/* ============================================================
 * SIMPAN ALAT BARU
 * ============================================================ */
elseif ($action == 'simpan_alat') {
    try {
        $no_part   = mysqli_real_escape_string($konek, trim($_POST['no_part'] ?? ''));
        $nama_part = mysqli_real_escape_string($konek, trim($_POST['nama_part'] ?? ''));
        $seksi     = mysqli_real_escape_string($konek, trim($_POST['seksi'] ?? ''));
        $periode   = (int)($_POST['periode'] ?? 0);
        $kls       = mysqli_real_escape_string($konek, trim($_POST['kls'] ?? ''));

        if ($no_part === '' || $nama_part === '' || $periode <= 0) {
            echo json_encode(['status'=>'error','message'=>'No. Alat, Nama Alat, dan Periode Kalibrasi wajib diisi dengan benar.']); exit;
        }
        
        $ukuran    = isset($_POST['ukuran']) ? mysqli_real_escape_string($konek, $_POST['ukuran']) : '';
        $resolusi  = isset($_POST['resolusi']) ? mysqli_real_escape_string($konek, $_POST['resolusi']) : '';
        $lokasi    = isset($_POST['lokasi']) ? mysqli_real_escape_string($konek, $_POST['lokasi']) : '';
        $type      = isset($_POST['type']) ? mysqli_real_escape_string($konek, $_POST['type']) : '';
        
        $tgl_manual = isset($_POST['tgl_manual']) ? mysqli_real_escape_string($konek, $_POST['tgl_manual']) : '';

        $spesifikasi = isset($_POST['spesifikasi']) ? mysqli_real_escape_string($konek, $_POST['spesifikasi']) : '';
        $toleransi_global = isset($_POST['toleransi_global']) ? mysqli_real_escape_string($konek, $_POST['toleransi_global']) : '';
        $satuan = isset($_POST['satuan']) ? mysqli_real_escape_string($konek, $_POST['satuan']) : '';
        $metode_uji = isset($_POST['metode_uji']) ? mysqli_real_escape_string($konek, $_POST['metode_uji']) : '';
        $standar_uji = isset($_POST['standar_uji']) ? mysqli_real_escape_string($konek, $_POST['standar_uji']) : '';
        $traceability = isset($_POST['traceability']) ? mysqli_real_escape_string($konek, $_POST['traceability']) : '';
        $format_laporan = isset($_POST['format_laporan']) ? mysqli_real_escape_string($konek, $_POST['format_laporan']) : '';
        $titik_standar_json = isset($_POST['titik_standar_json']) ? mysqli_real_escape_string($konek, $_POST['titik_standar_json']) : '[]';

        $tgl_start = !empty($tgl_manual) ? $tgl_manual : date('Y-m-d');
        $jadwal_kalibrasi = date('Y-m-d', strtotime($tgl_start . " +$periode days"));
        $created_at       = date('Y-m-d H:i:s');

        $nama_file_foto = '';
        if(isset($_FILES['foto_alat']) && $_FILES['foto_alat']['error'] == 0){
            if ($_FILES['foto_alat']['size'] > 10485760) { echo json_encode(['status' => 'error', 'message' => 'Gagal! Ukuran max 10MB.']); exit; }
            $folder_tujuan = "../../images/alat/";
            if (!is_dir($folder_tujuan)) { mkdir($folder_tujuan, 0777, true); }
            $ext = pathinfo($_FILES['foto_alat']['name'], PATHINFO_EXTENSION);
            $nama_file_foto = "ALAT_" . preg_replace('/[^A-Za-z0-9]/', '', $no_part) . "_" . time() . "." . $ext;
            move_uploaded_file($_FILES['foto_alat']['tmp_name'], $folder_tujuan . $nama_file_foto);
        }

        $part_values = [
            'no_part'            => $no_part,
            'nama_part'          => $nama_part,
            'seksi_pemilik'      => $seksi,
            'periode_kalibrasi'  => $periode,
            'jadwal_kalibrasi'   => $jadwal_kalibrasi,
            'status'             => 'aktif',
            'created_at'         => $created_at,

            // Pada schema DB sekarang, Spesifikasi Form A dapat memakai `ukuran`.
            'ukuran'             => ($spesifikasi !== '' ? $spesifikasi : $ukuran),

            'resolusi'           => $resolusi,
            'lokasi'             => $lokasi,
            'type'               => $type,
            'kls'                => $kls,
            'foto_alat'          => $nama_file_foto,

            // Kolom-kolom ini hanya akan masuk jika memang ada di DB.
            'spesifikasi'        => $spesifikasi,
            'toleransi_global'   => $toleransi_global,
            'satuan'             => $satuan,
            'metode_uji'         => $metode_uji,
            'standar_uji'        => $standar_uji,
            'traceability'       => $traceability,
            'format_laporan'     => $format_laporan,
            'titik_standar_json' => $titik_standar_json
        ];

        if (in_array(strtoupper(trim($_POST['format_laporan'] ?? '')), ['B','FORM B'], true)) {
            [$ok, $message, $standards] = qa_b_standards(qa_b_decode($_POST['titik_standar_json'] ?? ''));
            if (!$ok) json_response('error', $message);
            foreach (['spesifikasi','seksi','metode_uji','standar_uji','traceability'] as $field) {
                if (trim($_POST[$field] ?? '') === '') json_response('error', 'Form B: '.$field.' wajib diisi.');
            }
            $standards[0]['_meta'] = array_intersect_key($_POST, array_flip(['metode_uji','standar_uji','traceability']));
            // qa_safe_insert melakukan escaping; berikan nilai mentah satu kali.
            foreach (['no_part'=>'no_part','nama_part'=>'nama_part','seksi_pemilik'=>'seksi','ukuran'=>'spesifikasi','type'=>'type','lokasi'=>'lokasi','kls'=>'kls','metode_uji'=>'metode_uji','standar_uji'=>'standar_uji','traceability'=>'traceability'] as $column=>$field) {
                $part_values[$column] = trim($_POST[$field] ?? '');
            }
            $part_values['format_laporan']='B';
            $part_values['titik_standar_json']=json_encode($standards, JSON_UNESCAPED_UNICODE);
        }

        if (in_array(strtoupper(trim($_POST['format_laporan'] ?? '')), ['C','FORM C'], true)) {
            $standardsC = json_decode((string)($_POST['titik_standar_json'] ?? '[]'), true);
            if (!is_array($standardsC) || count($standardsC) === 0) {
                json_response('error', 'Form C: minimal 1 titik pemeriksaan wajib diisi.');
            }

            $cleanC = [];
            foreach ($standardsC as $i => $rowC) {
                if (!is_array($rowC)) {
                    json_response('error', 'Form C: data titik pemeriksaan baris '.($i + 1).' tidak valid.');
                }
                $settingC = trim((string)($rowC['setting'] ?? ''));
                $alatStdC = trim((string)($rowC['alat_std'] ?? ''));
                if ($settingC === '' || !is_numeric($settingC) || $alatStdC === '' || !is_numeric($alatStdC)) {
                    json_response('error', 'Form C: Setting (SV) dan Alat STD baris '.($i + 1).' wajib berupa angka.');
                }
                $cleanC[] = [
                    'setting' => $settingC,
                    'alat_std' => $alatStdC
                ];
            }

            foreach (['spesifikasi','seksi','resolusi','toleransi_global','metode_uji','standar_uji','traceability'] as $field) {
                if (trim((string)($_POST[$field] ?? '')) === '') {
                    json_response('error', 'Form C: '.$field.' wajib diisi.');
                }
            }

            $part_values['format_laporan'] = 'C';
            $part_values['satuan'] = '°C';
            $part_values['titik_standar_json'] = json_encode($cleanC, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (in_array(strtoupper(trim($_POST['format_laporan'] ?? '')), ['D','FORM D'], true)) {
            $standardsD = json_decode((string)($_POST['titik_standar_json'] ?? '[]'), true);
            if (!is_array($standardsD) || count($standardsD) === 0) {
                json_response('error', 'Form D: minimal 1 titik pemeriksaan wajib diisi.');
            }

            $cleanD = [];
            foreach ($standardsD as $i => $rowD) {
                if (!is_array($rowD)) {
                    json_response('error', 'Form D: data titik pemeriksaan baris '.($i + 1).' tidak valid.');
                }
                $unitD = trim((string)($rowD['unit'] ?? ''));
                $modeD = strtoupper(trim((string)($rowD['mode'] ?? '-')));
                $settingD = trim((string)($rowD['setting'] ?? ''));
                $standarD = trim((string)($rowD['standar'] ?? ''));
                if ($unitD === '' || $settingD === '' || !is_numeric($settingD) || $standarD === '' || !is_numeric($standarD)) {
                    json_response('error', 'Form D: Unit, Setting, dan Standar baris '.($i + 1).' wajib diisi dengan benar.');
                }
                if (!in_array($modeD, ['AC','DC','-'], true)) $modeD = '-';
                $cleanD[] = [
                    'unit' => $unitD,
                    'mode' => $modeD,
                    'setting' => $settingD,
                    'standar' => $standarD
                ];
            }

            foreach (['spesifikasi','seksi','resolusi','toleransi_global','metode_uji','standar_uji','traceability'] as $field) {
                if (trim((string)($_POST[$field] ?? '')) === '') {
                    json_response('error', 'Form D: '.$field.' wajib diisi.');
                }
            }

            $part_values['format_laporan'] = 'D';
            $part_values['satuan'] = '';
            $part_values['titik_standar_json'] = json_encode($cleanD, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (in_array(strtoupper(trim($_POST['format_laporan'] ?? '')), ['H','FORM H'], true)) {
            $standardsH = json_decode((string)($_POST['titik_standar_json'] ?? '[]'), true);
            if (!is_array($standardsH) || count($standardsH) === 0) {
                json_response('error', 'Form H: minimal 1 posisi ukur / alat standar wajib diisi.');
            }

            $cleanH = [];
            foreach ($standardsH as $i => $rowH) {
                if (!is_array($rowH)) {
                    json_response('error', 'Form H: data standar baris '.($i + 1).' tidak valid.');
                }
                $posisiH = strtoupper(trim((string)($rowH['posisi_ukur'] ?? '')));
                $stdSpekH = trim((string)($rowH['std_spesifikasi'] ?? ''));
                $stdNoH = strtoupper(trim((string)($rowH['std_no_alat'] ?? '')));
                $stdOutH = trim((string)($rowH['std_out'] ?? ''));
                $tolOutH = trim((string)($rowH['tol_out'] ?? ''));
                $rangeOutH = trim((string)($rowH['range_out'] ?? ''));
                $stdPitchH = trim((string)($rowH['std_pitch'] ?? ''));
                $tolPitchH = trim((string)($rowH['tol_pitch'] ?? ''));
                $rangePitchH = trim((string)($rowH['range_pitch'] ?? ''));

                if ($posisiH === '' || $stdSpekH === '' || $stdNoH === '' || ($stdOutH === '' && $stdPitchH === '')) {
                    json_response('error', 'Form H: Posisi Ukur, Spesifikasi Standar, No. Alat Standar, dan minimal STD OUT/PITCH wajib diisi pada baris '.($i + 1).'.');
                }

                $cleanH[] = [
                    'posisi_ukur' => $posisiH,
                    'std_spesifikasi' => $stdSpekH,
                    'std_no_alat' => $stdNoH,
                    'std_out' => $stdOutH,
                    'tol_out' => $tolOutH,
                    'range_out' => $rangeOutH,
                    'std_pitch' => $stdPitchH,
                    'tol_pitch' => $tolPitchH,
                    'range_pitch' => $rangePitchH
                ];
            }

            foreach (['spesifikasi','seksi','metode_uji','standar_uji','traceability'] as $field) {
                if (trim((string)($_POST[$field] ?? '')) === '') {
                    json_response('error', 'Form H: '.$field.' wajib diisi.');
                }
            }

            $part_values['format_laporan'] = 'H';
            $part_values['resolusi'] = '';
            $part_values['toleransi_global'] = '';
            $part_values['satuan'] = '';
            $part_values['lokasi'] = '';
            $part_values['type'] = '';
            $part_values['kls'] = '';
            $part_values['titik_standar_json'] = json_encode($cleanH, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (in_array(strtoupper(trim($_POST['format_laporan'] ?? '')), ['I','FORM I'], true)) {
            $standardsI = json_decode((string)($_POST['titik_standar_json'] ?? '[]'), true);

            if (!is_array($standardsI) || count($standardsI) === 0) {
                json_response('error', 'Form I: data alat standar wajib diisi.');
            }

            $cleanI = [];

            foreach ($standardsI as $i => $rowI) {
                if (!is_array($rowI)) {
                    json_response('error', 'Form I: data alat standar baris '.($i + 1).' tidak valid.');
                }

                $stdSpesifikasiI = trim((string)($rowI['std_spesifikasi'] ?? ''));
                $stdNoAlatI = strtoupper(trim((string)($rowI['std_no_alat'] ?? '')));

                if ($stdSpesifikasiI === '' || $stdNoAlatI === '') {
                    json_response('error', 'Form I: Spesifikasi Alat Standar dan No. Alat Standar wajib diisi.');
                }

                $cleanI[] = [
                    'std_spesifikasi' => $stdSpesifikasiI,
                    'std_no_alat' => $stdNoAlatI,
                    'lokasi' => ['ATAS', 'BAWAH']
                ];
            }

            foreach (['spesifikasi','seksi','metode_uji','standar_uji','traceability'] as $field) {
                if (trim((string)($_POST[$field] ?? '')) === '') {
                    json_response('error', 'Form I: '.$field.' wajib diisi.');
                }
            }

            $part_values['format_laporan'] = 'I';
            $part_values['resolusi'] = '';
            $part_values['toleransi_global'] = '';
            $part_values['satuan'] = '';
            $part_values['lokasi'] = '';
            $part_values['type'] = '';
            $part_values['kls'] = '';
            $part_values['titik_standar_json'] = json_encode(
                $cleanI,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        if (in_array(strtoupper(trim($_POST['format_laporan'] ?? '')), ['J','FORM J'], true)) {
            foreach (['spesifikasi','seksi','metode_uji','standar_uji'] as $field) {
                if (trim((string)($_POST[$field] ?? '')) === '') {
                    json_response('error', 'Form J: '.$field.' wajib diisi.');
                }
            }

            $part_values['format_laporan'] = 'J';
            $part_values['resolusi'] = '';
            $part_values['toleransi_global'] = '';
            $part_values['satuan'] = '';
            $part_values['lokasi'] = '';
            $part_values['type'] = '';
            $part_values['kls'] = '';
            $part_values['traceability'] = '';

            $part_values['titik_standar_json'] = json_encode(
                [[
                    'alat_verifikasi' => trim((string)($_POST['standar_uji'] ?? ''))
                ]],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        if (in_array(strtoupper(trim($_POST['format_laporan'] ?? '')), ['K','FORM K'], true)) {
            $rowsK=json_decode((string)($_POST['titik_standar_json'] ?? '[]'),true); if(!is_array($rowsK)||count($rowsK)===0) json_response('error','Form K: minimal 1 Nilai yang Ditentukan wajib diisi.'); $cleanK=[];
            foreach($rowsK as $i=>$rowK){$nilai=trim((string)($rowK['nilai_ditentukan']??''));if($nilai===''||!is_numeric($nilai))json_response('error','Form K: Nilai yang Ditentukan baris '.($i+1).' wajib berupa angka.');$cleanK[]=['nilai_ditentukan'=>$nilai];}
            foreach(['spesifikasi','seksi','resolusi','toleransi_global','metode_uji','standar_uji','traceability'] as $field){if(trim((string)($_POST[$field]??''))==='')json_response('error','Form K: '.$field.' wajib diisi.');}
            $tol=trim((string)($_POST['toleransi_global']??''));if(!is_numeric($tol)||(float)$tol<=0)json_response('error','Form K: Standar Toleransi harus angka persen lebih dari 0.');
            $part_values['format_laporan']='K';$part_values['satuan']='Volt';$part_values['lokasi']='';$part_values['type']='';$part_values['kls']='';$part_values['titik_standar_json']=json_encode($cleanK,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }

        if (in_array(strtoupper(trim($_POST['format_laporan'] ?? '')), ['L','FORM L'], true)) {
            $rowsL = json_decode((string)($_POST['titik_standar_json'] ?? '[]'), true);
            if (!is_array($rowsL) || count($rowsL) === 0) {
                json_response('error', 'Form L: minimal 1 Applied Value wajib diisi.');
            }

            $cleanL = [];
            $stdNoL = '';

            foreach ($rowsL as $i => $rowL) {
                if (!is_array($rowL)) {
                    json_response('error', 'Form L: data titik baris '.($i + 1).' tidak valid.');
                }

                $arahL = strtoupper(trim((string)($rowL['arah'] ?? '')));
                $appliedL = trim((string)($rowL['applied_value'] ?? ''));
                $rowStdNoL = strtoupper(trim((string)($rowL['std_no_alat'] ?? '')));

                if (!in_array($arahL, ['NAIK','TURUN'], true)) {
                    json_response('error', 'Form L: Uji/Skala harus NAIK atau TURUN.');
                }
                if ($appliedL === '' || !is_numeric($appliedL)) {
                    json_response('error', 'Form L: Applied Value baris '.($i + 1).' wajib berupa angka.');
                }
                if ($rowStdNoL === '') {
                    json_response('error', 'Form L: No. Alat Standar wajib diisi.');
                }

                if ($stdNoL === '') $stdNoL = $rowStdNoL;
                if ($rowStdNoL !== $stdNoL) {
                    json_response('error', 'Form L: No. Alat Standar harus sama untuk seluruh titik.');
                }

                $cleanL[] = [
                    'arah' => $arahL,
                    'applied_value' => $appliedL,
                    'std_no_alat' => $stdNoL
                ];
            }

            foreach (['spesifikasi','seksi','resolusi','toleransi_global','metode_uji','standar_uji','traceability'] as $field) {
                if (trim((string)($_POST[$field] ?? '')) === '') {
                    json_response('error', 'Form L: '.$field.' wajib diisi.');
                }
            }

            $tolL = trim((string)($_POST['toleransi_global'] ?? ''));
            if (!is_numeric($tolL) || (float)$tolL <= 0) {
                json_response('error', 'Form L: Standar Toleransi harus berupa angka MPa lebih dari 0.');
            }

            $part_values['format_laporan'] = 'L';
            $part_values['satuan'] = 'MPa';
            $part_values['lokasi'] = '';
            $part_values['type'] = '';
            $part_values['kls'] = '';
            $part_values['titik_standar_json'] = json_encode($cleanL, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (in_array(strtoupper(trim($_POST['format_laporan'] ?? '')), ['M','FORM M'], true)) {
            $rowsM = json_decode((string)($_POST['titik_standar_json'] ?? '[]'), true);

            if (!is_array($rowsM) || count($rowsM) === 0) {
                json_response('error', 'Form M: data standar wajib diisi.');
            }

            $cleanM = [];
            $axisCountM = 0;
            $angleCountM = 0;
            $stdSudutM = '';
            $tolSudutM = 0.0;

            foreach ($rowsM as $i => $rowM) {
                if (!is_array($rowM)) {
                    json_response('error', 'Form M: data baris '.($i + 1).' tidak valid.');
                }

                $jenisM = strtoupper(trim((string)($rowM['jenis'] ?? '')));
                $rowStdSudutM = trim((string)($rowM['std_sudut'] ?? ''));
                $rowTolSudutM = trim((string)($rowM['tol_sudut'] ?? ''));

                if ($stdSudutM === '' && $rowStdSudutM !== '') {
                    $stdSudutM = $rowStdSudutM;
                }
                if ($tolSudutM <= 0 && is_numeric($rowTolSudutM)) {
                    $tolSudutM = abs((float)$rowTolSudutM);
                }

                if ($jenisM === 'AXIS') {
                    $stdMm = trim((string)($rowM['std_mm'] ?? ''));
                    $xEnabled = !empty($rowM['x_enabled']);
                    $yEnabled = !empty($rowM['y_enabled']);

                    if ($stdMm === '' || !is_numeric($stdMm)) {
                        json_response('error', 'Form M: STD Axis baris '.($i + 1).' wajib berupa angka.');
                    }
                    if (!$xEnabled && !$yEnabled) {
                        json_response('error', 'Form M: minimal satu Axis harus aktif pada baris '.($i + 1).'.');
                    }

                    $cleanM[] = [
                        'jenis' => 'AXIS',
                        'std_mm' => $stdMm,
                        'x_enabled' => $xEnabled,
                        'y_enabled' => $yEnabled,
                        'std_sudut' => $rowStdSudutM,
                        'tol_sudut' => $rowTolSudutM
                    ];
                    $axisCountM++;
                } elseif ($jenisM === 'ANGLE') {
                    $stdDegree = trim((string)($rowM['std_degree'] ?? ''));

                    if ($stdDegree === '' || !is_numeric($stdDegree)) {
                        json_response('error', 'Form M: STD Angle baris '.($i + 1).' wajib berupa angka.');
                    }

                    $cleanM[] = [
                        'jenis' => 'ANGLE',
                        'std_degree' => $stdDegree,
                        'std_sudut' => $rowStdSudutM,
                        'tol_sudut' => $rowTolSudutM
                    ];
                    $angleCountM++;
                } else {
                    json_response('error', 'Form M: jenis standar baris '.($i + 1).' tidak dikenali.');
                }
            }

            if ($axisCountM === 0 || $angleCountM === 0) {
                json_response('error', 'Form M: AXIS dan ANGLE Accuracy wajib memiliki titik standar.');
            }

            if ($stdSudutM === '' || $tolSudutM <= 0) {
                json_response('error', 'Form M: Standar Uji Sudut dan Toleransi Sudut (menit busur) wajib diisi.');
            }

            foreach (['spesifikasi','seksi','resolusi','toleransi_global','metode_uji','standar_uji','traceability'] as $field) {
                if (trim((string)($_POST[$field] ?? '')) === '') {
                    json_response('error', 'Form M: '.$field.' wajib diisi.');
                }
            }

            $tolAxisM = trim((string)($_POST['toleransi_global'] ?? ''));
            if (!is_numeric($tolAxisM) || (float)$tolAxisM <= 0) {
                json_response('error', 'Form M: Standar Toleransi Axis harus berupa angka mm lebih dari 0.');
            }

            $part_values['format_laporan'] = 'M';
            $part_values['satuan'] = 'mm';
            $part_values['lokasi'] = '';
            $part_values['type'] = '';
            $part_values['kls'] = '';
            $part_values['titik_standar_json'] = json_encode(
                $cleanM,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        if (in_array(strtoupper(trim($_POST['format_laporan'] ?? '')), ['O','FORM O'], true)) {
            $rowsO = json_decode((string)($_POST['titik_standar_json'] ?? '[]'), true);
            if (!is_array($rowsO) || count($rowsO) === 0) {
                json_response('error', 'Form O: data snap gauge wajib diisi.');
            }

            $cleanO = [];
            foreach ($rowsO as $i => $rowO) {
                if (!is_array($rowO)) {
                    json_response('error', 'Form O: data baris '.($i + 1).' tidak valid.');
                }
                $clean = [
                    'no_alat' => strtoupper(trim((string)($rowO['no_alat'] ?? ''))),
                    'spesifikasi' => trim((string)($rowO['spesifikasi'] ?? '')),
                    'tol_plus' => trim((string)($rowO['tol_plus'] ?? '')),
                    'tol_minus' => trim((string)($rowO['tol_minus'] ?? '')),
                    'go_std' => trim((string)($rowO['go_std'] ?? '')),
                    'go_tol_near' => trim((string)($rowO['go_tol_near'] ?? '')),
                    'go_tol_far' => trim((string)($rowO['go_tol_far'] ?? '')),
                    'notgo_std' => trim((string)($rowO['notgo_std'] ?? '')),
                    'notgo_tol' => trim((string)($rowO['notgo_tol'] ?? '')),
                    'no_gambar' => trim((string)($rowO['no_gambar'] ?? '')),
                ];
                if ($clean['no_alat'] === '' || $clean['no_gambar'] === '') {
                    json_response('error', 'Form O: No Alat dan No Gambar baris '.($i + 1).' wajib diisi.');
                }
                foreach (['spesifikasi','tol_plus','tol_minus','go_std','go_tol_near','go_tol_far','notgo_std','notgo_tol'] as $f) {
                    if ($clean[$f] === '' || !is_numeric($clean[$f])) {
                        json_response('error', 'Form O: field '.$f.' baris '.($i + 1).' wajib berupa angka.');
                    }
                }
                $cleanO[] = $clean;
            }

            foreach (['seksi','metode_uji','standar_uji','traceability'] as $field) {
                if (trim((string)($_POST[$field] ?? '')) === '') {
                    json_response('error', 'Form O: '.$field.' wajib diisi.');
                }
            }

            $part_values['format_laporan'] = 'O';
            $part_values['satuan'] = 'mm';
            $part_values['lokasi'] = '';
            $part_values['type'] = '';
            $part_values['kls'] = '';
            $part_values['resolusi'] = '';
            $part_values['toleransi_global'] = null;
            $part_values['titik_standar_json'] = json_encode($cleanO, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (in_array(strtoupper(trim($_POST['format_laporan'] ?? '')), ['P','FORM P'], true)) {
            $itemsP = json_decode((string)($_POST['titik_standar_json'] ?? '[]'), true);
            if (!is_array($itemsP) || count($itemsP) === 0) {
                json_response('error', 'Form P: minimal 1 SK Gauge wajib diisi.');
            }

            $cleanP = [];
            foreach ($itemsP as $i => $itemP) {
                if (!is_array($itemP)) {
                    json_response('error', 'Form P: data alat baris '.($i + 1).' tidak valid.');
                }

                $cleanItem = [
                    'no_alat' => strtoupper(trim((string)($itemP['no_alat'] ?? ''))),
                    'type' => trim((string)($itemP['type'] ?? '')),
                    'ukuran' => trim((string)($itemP['ukuran'] ?? '')),
                    'upart' => trim((string)($itemP['upart'] ?? '')),
                    'no_gambar' => trim((string)($itemP['no_gambar'] ?? '')),
                    'measurements' => []
                ];

                if ($cleanItem['no_alat'] === '' || $cleanItem['type'] === '' || $cleanItem['ukuran'] === '' || $cleanItem['no_gambar'] === '') {
                    json_response('error', 'Form P: No Alat, Type, Ukuran, dan No Gambar alat #'.($i + 1).' wajib diisi.');
                }

                $ms = $itemP['measurements'] ?? [];
                if (!is_array($ms) || count($ms) === 0) {
                    json_response('error', 'Form P: alat #'.($i + 1).' wajib mempunyai minimal 1 titik pemeriksaan.');
                }

                foreach ($ms as $j => $m) {
                    if (!is_array($m)) {
                        json_response('error', 'Form P: titik '.($j + 1).' alat #'.($i + 1).' tidak valid.');
                    }
                    $alatNama = trim((string)($m['alat_nama'] ?? ''));
                    $alatNo = strtoupper(trim((string)($m['alat_no'] ?? '')));
                    $stdLabel = trim((string)($m['std_label'] ?? ''));
                    $min = trim((string)($m['min'] ?? ''));
                    $max = trim((string)($m['max'] ?? ''));
                    $actualCount = (string)($m['actual_count'] ?? '2');

                    if ($alatNama === '' || $alatNo === '' || $stdLabel === '' || $min === '' || $max === '' || !is_numeric($min) || !is_numeric($max)) {
                        json_response('error', 'Form P: titik '.($j + 1).' alat #'.($i + 1).' belum lengkap.');
                    }
                    if ((float)$min > (float)$max) {
                        json_response('error', 'Form P: MIN tidak boleh lebih besar dari MAX pada titik '.($j + 1).' alat #'.($i + 1).'.');
                    }
                    if (!in_array($actualCount, ['1','2'], true)) $actualCount = '2';

                    $cleanItem['measurements'][] = [
                        'alat_nama' => $alatNama,
                        'alat_no' => $alatNo,
                        'std_label' => $stdLabel,
                        'min' => $min,
                        'max' => $max,
                        'actual_count' => $actualCount
                    ];
                }
                $cleanP[] = $cleanItem;
            }

            foreach (['seksi','metode_uji','standar_uji','traceability'] as $field) {
                if (trim((string)($_POST[$field] ?? '')) === '') {
                    json_response('error', 'Form P: '.$field.' wajib diisi.');
                }
            }

            $part_values['format_laporan'] = 'P';
            $part_values['satuan'] = 'mm';
            $part_values['lokasi'] = '';
            $part_values['type'] = '';
            $part_values['kls'] = '';
            $part_values['resolusi'] = '';
            $part_values['toleransi_global'] = null;
            $part_values['titik_standar_json'] = json_encode($cleanP, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (in_array(strtoupper(trim($_POST['format_laporan'] ?? '')), ['Q','FORM Q'], true)) {
            $rowsQ = json_decode((string)($_POST['titik_standar_json'] ?? '[]'), true);
            if (!is_array($rowsQ) || count($rowsQ) !== 1 || !is_array($rowsQ[0])) {
                json_response('error', 'Form Q: data registrasi tidak valid.');
            }
            $q = $rowsQ[0];
            $subtype = strtoupper(trim((string)($q['subtype'] ?? '')));
            if (!in_array($subtype, ['SERATION','PLUG'], true)) {
                json_response('error', 'Form Q: jenis gauge wajib Seration Plug Gauge atau Plug Gauge.');
            }

            foreach (['seksi','metode_uji','standar_uji','traceability'] as $field) {
                if (trim((string)($_POST[$field] ?? '')) === '') {
                    json_response('error', 'Form Q: '.$field.' wajib diisi.');
                }
            }

            $cleanQ = ['subtype' => $subtype];
            if ($subtype === 'SERATION') {
                foreach (['out','pitch'] as $kind) {
                    $r = isset($q[$kind]) && is_array($q[$kind]) ? $q[$kind] : [];
                    $stdSpek = trim((string)($r['std_spesifikasi'] ?? ''));
                    $stdNo = strtoupper(trim((string)($r['std_no_alat'] ?? '')));
                    $std = trim((string)($r['std'] ?? ''));
                    $plus = trim((string)($r['tol_plus'] ?? ''));
                    $minus = trim((string)($r['tol_minus'] ?? ''));
                    if ($stdSpek === '' || $stdNo === '' || $std === '' || $plus === '' || $minus === '' || !is_numeric($std) || !is_numeric($plus) || !is_numeric($minus)) {
                        json_response('error', 'Form Q Seration: data '.strtoupper($kind).' belum lengkap/valid.');
                    }
                    $cleanQ[$kind] = [
                        'std_spesifikasi' => $stdSpek,
                        'std_no_alat' => $stdNo,
                        'std' => $std,
                        'tol_plus' => $plus,
                        'tol_minus' => $minus
                    ];
                }
            } else {
                $stdSpek = trim((string)($q['std_spesifikasi'] ?? ''));
                $stdNo = strtoupper(trim((string)($q['std_no_alat'] ?? '')));
                $noGambar = trim((string)($q['no_gambar'] ?? ''));
                if ($stdSpek === '' || $stdNo === '' || $noGambar === '') {
                    json_response('error', 'Form Q Plug Gauge: alat standar dan No. Gambar wajib diisi.');
                }
                $cleanQ['std_spesifikasi'] = $stdSpek;
                $cleanQ['std_no_alat'] = $stdNo;
                $cleanQ['no_gambar'] = $noGambar;
                foreach (['go','notgo'] as $kind) {
                    $r = isset($q[$kind]) && is_array($q[$kind]) ? $q[$kind] : [];
                    $std = trim((string)($r['std'] ?? ''));
                    $plus = trim((string)($r['tol_plus'] ?? ''));
                    $minus = trim((string)($r['tol_minus'] ?? ''));
                    if ($std === '' || $plus === '' || $minus === '' || !is_numeric($std) || !is_numeric($plus) || !is_numeric($minus)) {
                        json_response('error', 'Form Q Plug Gauge: data '.strtoupper($kind).' belum lengkap/valid.');
                    }
                    $cleanQ[$kind] = ['std'=>$std,'tol_plus'=>$plus,'tol_minus'=>$minus];
                }
            }

            $part_values['format_laporan'] = 'Q';
            $part_values['satuan'] = 'mm';
            $part_values['lokasi'] = '';
            $part_values['type'] = '';
            $part_values['kls'] = '';
            $part_values['resolusi'] = '';
            $part_values['toleransi_global'] = null;
            $part_values['titik_standar_json'] = json_encode([$cleanQ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        [$part_ok, $part_error] = qa_safe_insert($konek, 'qa_part', $part_values);
        
        if ($part_ok) {
            $alat_id = mysqli_insert_id($konek);
            if ($part_values['format_laporan'] === 'B') {
                json_response('success', 'Alat Form B terdaftar. Silakan isi hasil pemeriksaan.');
            }
            if ($part_values['format_laporan'] === 'H') {
                json_response('success', 'Alat Form H terdaftar. Silakan isi hasil pemeriksaan.');
            }

            if ($part_values['format_laporan'] === 'I') {
                json_response('success', 'Alat Form I terdaftar. Silakan isi hasil pemeriksaan.');
            }
            $nomor_pengecekan = "QA-REG-" . date('YmdHis') . "-" . $alat_id;
            $created_by = isset($_SESSION['userid']) ? mysqli_real_escape_string($konek, $_SESSION['userid']) : 'SISTEM';
            
            $sql_hist = "INSERT INTO qa_pengecekan 
                         (alat_id, no_part, nomor_pengecekan, tanggal_pengecekan, tanggal_jadwal_saat_pengecekan, status_kalibrasi, seksi_qa, periode_berikutnya, created_by, keterangan, created_at) 
                         VALUES 
                         ('$alat_id', '$no_part', '$nomor_pengecekan', '$tgl_start', '$tgl_start', 'Good', 'QA', '$jadwal_kalibrasi', '$created_by', 'Registrasi Alat Awal', '$created_at')";
            if (!mysqli_query($konek, $sql_hist)) {
                echo json_encode(['status'=>'error','message'=>'Alat tersimpan, tetapi histori registrasi gagal disimpan: '.mysqli_error($konek)]); exit;
            }
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $part_error ?: mysqli_error($konek)]);
        }
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}

/* ============================================================
 * SIMPAN HASIL QA
 * ============================================================ */

if ($action === 'update_form_a') {
    $no_part = trim($_POST['no_part'] ?? '');

    if ($no_part === '') {
        json_response('error', 'No. alat wajib diisi.');
    }

    $q = mysqli_prepare($konek, "SELECT titik_standar_json FROM qa_part WHERE no_part = ? LIMIT 1");
    if (!$q) json_response('error', mysqli_error($konek));

    mysqli_stmt_bind_param($q, 's', $no_part);
    mysqli_stmt_execute($q);
    $result = mysqli_stmt_get_result($q);
    $old = mysqli_fetch_assoc($result);
    mysqli_stmt_close($q);

    if (!$old) {
        json_response('error', 'Data alat tidak ditemukan.');
    }

    $merged = qa_merge_form_a($old['titik_standar_json'] ?? '', $_POST);

    $u = mysqli_prepare($konek, "UPDATE qa_part SET format_laporan = 'A', titik_standar_json = ? WHERE no_part = ?");
    if (!$u) json_response('error', mysqli_error($konek));

    mysqli_stmt_bind_param($u, 'ss', $merged, $no_part);

    if (!mysqli_stmt_execute($u)) {
        $err = mysqli_stmt_error($u);
        mysqli_stmt_close($u);
        json_response('error', $err);
    }

    mysqli_stmt_close($u);
    json_response('success', 'Data Form A berhasil diperbarui.');
}

elseif ($action == 'simpan_qa') {
    try {
        $no_part    = mysqli_real_escape_string($konek, $_POST['no_part'] ?? '');
        $status_qa  = mysqli_real_escape_string($konek, $_POST['status_qa'] ?? 'Good');
        $seksi_qa   = "QA";
        $created_by = isset($_SESSION['userid']) ? mysqli_real_escape_string($konek, $_SESSION['userid']) : '';

        $suhu = mysqli_real_escape_string($konek, $_POST['suhu'] ?? '');
        $kelembapan = mysqli_real_escape_string($konek, $_POST['kelembapan'] ?? '');
        $ketidakpastian = mysqli_real_escape_string($konek, $_POST['ketidakpastian'] ?? '');
        $confidence = mysqli_real_escape_string($konek, $_POST['confidence'] ?? '');
        $keterangan = mysqli_real_escape_string($konek, $_POST['keterangan'] ?? '');

        /*
         * Semua detail pemeriksaan Form A/Form B/Form C disimpan di hasil_json.
         * Form B divalidasi dan dihitung ulang di server agar hasil tidak
         * bergantung pada nilai yang dikirim dari browser.
         */
        $hasil_json_raw = $_POST['hasil_json'] ?? '[]';
        $hasil_decoded = json_decode($hasil_json_raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Data hasil pemeriksaan tidak valid.'
            ]);
            exit;
        }

        $hasil_json = mysqli_real_escape_string(
            $konek,
            json_encode($hasil_decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        /*
         * Form A boleh menentukan tanggal pemeriksaan.
         * Kalau field tidak dikirim, alur lama tetap memakai tanggal hari ini.
         */
        $tgl_pengecekan = trim($_POST['tanggal_periksa'] ?? '');
        if ($tgl_pengecekan === '') {
            $tgl_pengecekan = date('Y-m-d');
        }

        $dt_pengecekan = DateTime::createFromFormat('Y-m-d', $tgl_pengecekan);
        if (!$dt_pengecekan || $dt_pengecekan->format('Y-m-d') !== $tgl_pengecekan) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Tanggal pemeriksaan tidak valid.'
            ]);
            exit;
        }

        $waktu_sekarang = date('Y-m-d H:i:s');

        $q_part = mysqli_query(
            $konek,
            "SELECT *
             FROM qa_part
             WHERE no_part = '$no_part'
             ORDER BY CASE WHEN UPPER(TRIM(COALESCE(status,''))) IN ('AKTIF','ACTIVE','1') THEN 0 ELSE 1 END, id DESC
             LIMIT 1"
        );

        if (!$q_part || mysqli_num_rows($q_part) === 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Data alat tidak ditemukan.'
            ]);
            exit;
        }

        $d_part = mysqli_fetch_assoc($q_part);

        if (!qa_is_active_status($d_part['status'] ?? '')) {
            echo json_encode(['status'=>'error','message'=>'Nomor alat ditemukan, tetapi status alat tidak aktif.']); exit;
        }

        $format_laporan = strtoupper(trim((string)($d_part['format_laporan'] ?? '')));
        // Fallback: kenali Form B dari struktur hasil_json jika frontend lama
        // belum mengirim field format_laporan.
        $isFormB = ($format_laporan === 'B' || $format_laporan === 'FORM B');
        if (!$isFormB && is_array($hasil_decoded) && isset($hasil_decoded[0]) && is_array($hasil_decoded[0])) {
            $probe = $hasil_decoded[0];
            $isFormB = array_key_exists('uji1', $probe) && array_key_exists('uji2', $probe) && array_key_exists('uji3', $probe)
                    && array_key_exists('std_min', $probe) && array_key_exists('std_max', $probe);
        }
        if ($isFormB) {
            [$bOk,$bMessage,$bRows,$bOverallOk] = qa_validate_form_b_rows($hasil_decoded, qa_b_decode($d_part['titik_standar_json'] ?? ''));
            if (!$bOk) { echo json_encode(['status'=>'error','message'=>$bMessage]); exit; }
            $status_qa = $bOverallOk ? 'Good' : 'Bad';
            $hasil_decoded=$bRows;
            $hasil_json=mysqli_real_escape_string($konek,json_encode($hasil_decoded,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        }


        // FORM C / PEMERIKSAAN TEMPERATUR
        $isFormC = ($format_laporan === 'C' || $format_laporan === 'FORM C');
        if (!$isFormC && is_array($hasil_decoded) && isset($hasil_decoded[0]) && is_array($hasil_decoded[0])) {
            $probeC = $hasil_decoded[0];
            $isFormC = array_key_exists('setting', $probeC)
                    && array_key_exists('actual', $probeC)
                    && array_key_exists('alat_std', $probeC);
        }

        $cRows = [];
        $cOverallOk = true;
        if ($isFormC) {
            $registeredC = json_decode((string)($d_part['titik_standar_json'] ?? '[]'), true);
            if (!is_array($registeredC)) $registeredC = [];

            if (!is_array($hasil_decoded) || count($hasil_decoded) === 0) {
                json_response('error', 'Form C: hasil pemeriksaan belum diisi.');
            }
            if (count($registeredC) !== count($hasil_decoded)) {
                json_response('error', 'Form C: jumlah baris hasil tidak sesuai dengan Setting (SV) registrasi.');
            }

            $tolC = isset($d_part['toleransi_global']) ? abs((float)$d_part['toleransi_global']) : 0.0;
            if ($tolC <= 0) {
                json_response('error', 'Form C: Standar Toleransi belum valid pada data alat.');
            }

            foreach ($registeredC as $i => $regRow) {
                $settingRaw = is_array($regRow) ? trim((string)($regRow['setting'] ?? '')) : trim((string)$regRow);
                $stdRaw = is_array($regRow) ? trim((string)($regRow['alat_std'] ?? '')) : '';
                $incoming = (isset($hasil_decoded[$i]) && is_array($hasil_decoded[$i])) ? $hasil_decoded[$i] : [];
                $actualRaw = trim((string)($incoming['actual'] ?? ''));

                if ($settingRaw === '' || !is_numeric($settingRaw)) {
                    json_response('error', 'Form C: Setting (SV) registrasi baris '.($i + 1).' tidak valid.');
                }
                if ($stdRaw === '' || !is_numeric($stdRaw)) {
                    json_response('error', 'Form C: Alat STD registrasi baris '.($i + 1).' belum valid. Lengkapi data registrasi alat terlebih dahulu.');
                }
                if ($actualRaw === '' || !is_numeric($actualRaw)) {
                    json_response('error', 'Form C: Actual (PV) baris '.($i + 1).' wajib berupa angka.');
                }

                $settingNum = (float)$settingRaw;
                $actualNum = (float)$actualRaw;
                $stdNum = (float)$stdRaw;
                $selisih = $actualNum - $stdNum;
                $lulus = abs($selisih) <= $tolC;
                if (!$lulus) $cOverallOk = false;

                $cRows[] = [
                    'setting' => (string)$settingRaw,
                    'actual' => (string)$actualRaw,
                    'alat_std' => (string)$stdRaw,
                    'selisih' => number_format($selisih, 1, '.', ''),
                    'toleransi' => $tolC,
                    'status' => $lulus ? 'OK' : 'BURUK'
                ];
            }

            $status_qa = $cOverallOk ? 'Good' : 'Bad';
            $hasil_decoded = $cRows;
            $hasil_json = mysqli_real_escape_string(
                $konek,
                json_encode($hasil_decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }


        // FORM D / PEMERIKSAAN MULTIMETER
        $isFormD = ($format_laporan === 'D' || $format_laporan === 'FORM D');
        if (!$isFormD && is_array($hasil_decoded) && isset($hasil_decoded[0]) && is_array($hasil_decoded[0])) {
            $probeD = $hasil_decoded[0];
            $isFormD = array_key_exists('unit', $probeD)
                    && array_key_exists('setting', $probeD)
                    && array_key_exists('standar', $probeD)
                    && array_key_exists('hasil', $probeD);
        }

        $dRows = [];
        if ($isFormD) {
            $registeredD = json_decode((string)($d_part['titik_standar_json'] ?? '[]'), true);
            if (!is_array($registeredD)) $registeredD = [];
            if (!is_array($hasil_decoded) || count($hasil_decoded) === 0) {
                json_response('error', 'Form D: hasil pemeriksaan belum diisi.');
            }
            if (count($registeredD) !== count($hasil_decoded)) {
                json_response('error', 'Form D: jumlah baris hasil tidak sesuai dengan titik registrasi.');
            }

            foreach ($registeredD as $i => $regRow) {
                $regRow = is_array($regRow) ? $regRow : [];
                $unitRaw = trim((string)($regRow['unit'] ?? ''));
                $modeRaw = strtoupper(trim((string)($regRow['mode'] ?? '-')));
                $settingRaw = trim((string)($regRow['setting'] ?? ''));
                $standarRaw = trim((string)($regRow['standar'] ?? ''));
                $incoming = (isset($hasil_decoded[$i]) && is_array($hasil_decoded[$i])) ? $hasil_decoded[$i] : [];
                $hasilRaw = trim((string)($incoming['hasil'] ?? ''));

                if ($unitRaw === '' || $settingRaw === '' || !is_numeric($settingRaw) || $standarRaw === '' || !is_numeric($standarRaw)) {
                    json_response('error', 'Form D: titik registrasi baris '.($i + 1).' tidak valid.');
                }
                if ($hasilRaw === '' || !is_numeric($hasilRaw)) {
                    json_response('error', 'Form D: Hasil baris '.($i + 1).' wajib berupa angka.');
                }

                $koreksi = (float)$standarRaw - (float)$hasilRaw;
                $dRows[] = [
                    'unit' => $unitRaw,
                    'mode' => in_array($modeRaw, ['AC','DC','-'], true) ? $modeRaw : '-',
                    'setting' => $settingRaw,
                    'standar' => $standarRaw,
                    'hasil' => $hasilRaw,
                    'koreksi' => number_format($koreksi, 1, '.', '')
                ];
            }

            if (!in_array(strtoupper($status_qa), ['GOOD','BAD'], true)) {
                json_response('error', 'Form D: Keputusan wajib dipilih.');
            }
            $hasil_decoded = $dRows;
            $hasil_json = mysqli_real_escape_string($konek, json_encode($dRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        // FORM H / PEMERIKSAAN THREAD PLUG GAUGE
        $isFormH = ($format_laporan === 'H' || $format_laporan === 'FORM H');
        if (!$isFormH && is_array($hasil_decoded) && isset($hasil_decoded[0]) && is_array($hasil_decoded[0])) {
            $probeH = $hasil_decoded[0];
            $isFormH = array_key_exists('posisi_ukur', $probeH)
                    && array_key_exists('out', $probeH)
                    && array_key_exists('pitch', $probeH)
                    && array_key_exists('kode_warna', $probeH)
                    && array_key_exists('putusan', $probeH);
        }

        $hRows = [];
        if ($isFormH) {
            $registeredH = json_decode((string)($d_part['titik_standar_json'] ?? '[]'), true);
            if (!is_array($registeredH) || count($registeredH) === 0) {
                json_response('error', 'Form H: data standar registrasi tidak ditemukan.');
            }
            if (!is_array($hasil_decoded) || count($hasil_decoded) !== count($registeredH)) {
                json_response('error', 'Form H: jumlah hasil pemeriksaan tidak sesuai dengan data registrasi.');
            }

            $kodeWarnaH = '';
            $putusanH = '';

            foreach ($registeredH as $i => $regH) {
                $regH = is_array($regH) ? $regH : [];
                $incomingH = (isset($hasil_decoded[$i]) && is_array($hasil_decoded[$i])) ? $hasil_decoded[$i] : [];

                $outInH = is_array($incomingH['out'] ?? null) ? $incomingH['out'] : [];
                $pitchInH = is_array($incomingH['pitch'] ?? null) ? $incomingH['pitch'] : [];

                $stdOutH = trim((string)($regH['std_out'] ?? ''));
                $stdPitchH = trim((string)($regH['std_pitch'] ?? ''));

                $outH = [
                    'dep' => trim((string)($outInH['dep'] ?? '')),
                    'ten' => trim((string)($outInH['ten'] ?? '')),
                    'bel' => trim((string)($outInH['bel'] ?? ''))
                ];
                $pitchH = [
                    'dep' => trim((string)($pitchInH['dep'] ?? '')),
                    'ten' => trim((string)($pitchInH['ten'] ?? '')),
                    'bel' => trim((string)($pitchInH['bel'] ?? ''))
                ];

                if ($stdOutH !== '' && ($outH['dep'] === '' || $outH['ten'] === '' || $outH['bel'] === '')) {
                    json_response('error', 'Form H: hasil OUT Ø baris '.($i + 1).' wajib diisi lengkap.');
                }
                if ($stdPitchH !== '' && ($pitchH['dep'] === '' || $pitchH['ten'] === '' || $pitchH['bel'] === '')) {
                    json_response('error', 'Form H: hasil PITCH Ø baris '.($i + 1).' wajib diisi lengkap.');
                }

                $warnaRowH = strtoupper(trim((string)($incomingH['kode_warna'] ?? '')));
                $putusanRowH = strtoupper(trim((string)($incomingH['putusan'] ?? '')));
                if ($i === 0) {
                    $kodeWarnaH = $warnaRowH;
                    $putusanH = $putusanRowH;
                }

                $hRows[] = [
                    'posisi_ukur' => strtoupper(trim((string)($regH['posisi_ukur'] ?? ''))),
                    'std_spesifikasi' => trim((string)($regH['std_spesifikasi'] ?? '')),
                    'std_no_alat' => strtoupper(trim((string)($regH['std_no_alat'] ?? ''))),
                    'std_out' => $stdOutH,
                    'tol_out' => trim((string)($regH['tol_out'] ?? '')),
                    'range_out' => trim((string)($regH['range_out'] ?? '')),
                    'std_pitch' => $stdPitchH,
                    'tol_pitch' => trim((string)($regH['tol_pitch'] ?? '')),
                    'range_pitch' => trim((string)($regH['range_pitch'] ?? '')),
                    'out' => $outH,
                    'pitch' => $pitchH,
                    'kode_warna' => $warnaRowH,
                    'putusan' => $putusanRowH
                ];
            }

            if ($kodeWarnaH === '') {
                json_response('error', 'Form H: Kode Warna wajib diisi.');
            }
            if (!in_array($putusanH, ['OK','BAD','NG'], true)) {
                json_response('error', 'Form H: Putusan wajib dipilih OK atau BAD.');
            }
            $putusanH = ($putusanH === 'NG') ? 'BAD' : $putusanH;
            foreach ($hRows as &$hRowFix) {
                $hRowFix['kode_warna'] = $kodeWarnaH;
                $hRowFix['putusan'] = $putusanH;
            }
            unset($hRowFix);

            $status_qa = ($putusanH === 'OK') ? 'Good' : 'Bad';
            $hasil_decoded = $hRows;
            $hasil_json = mysqli_real_escape_string($konek, json_encode($hRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        // FORM I / PEMERIKSAAN GAUGE
        $isFormI = ($format_laporan === 'I' || $format_laporan === 'FORM I');

        if (!$isFormI && is_array($hasil_decoded) && isset($hasil_decoded[0]) && is_array($hasil_decoded[0])) {
            $probeI = $hasil_decoded[0];
            $isFormI = array_key_exists('std_spesifikasi', $probeI)
                    && array_key_exists('std_no_alat', $probeI)
                    && array_key_exists('hasil_atas', $probeI)
                    && array_key_exists('hasil_bawah', $probeI)
                    && array_key_exists('kode_warna', $probeI)
                    && array_key_exists('putusan', $probeI);
        }

        $iRows = [];

        if ($isFormI) {
            $registeredI = json_decode((string)($d_part['titik_standar_json'] ?? '[]'), true);

            if (!is_array($registeredI) || count($registeredI) === 0) {
                json_response('error', 'Form I: data alat standar registrasi tidak ditemukan.');
            }

            if (!is_array($hasil_decoded) || count($hasil_decoded) === 0 || !isset($hasil_decoded[0]) || !is_array($hasil_decoded[0])) {
                json_response('error', 'Form I: hasil pemeriksaan belum diisi.');
            }

            $regI = is_array($registeredI[0]) ? $registeredI[0] : [];
            $incomingI = $hasil_decoded[0];

            $stdSpesifikasiI = trim((string)($regI['std_spesifikasi'] ?? ''));
            $stdNoAlatI = strtoupper(trim((string)($regI['std_no_alat'] ?? '')));
            $hasilAtasI = trim((string)($incomingI['hasil_atas'] ?? ''));
            $hasilBawahI = trim((string)($incomingI['hasil_bawah'] ?? ''));
            $kodeWarnaI = strtoupper(trim((string)($incomingI['kode_warna'] ?? '')));
            $putusanI = strtoupper(trim((string)($incomingI['putusan'] ?? '')));

            if ($stdSpesifikasiI === '' || $stdNoAlatI === '') {
                json_response('error', 'Form I: data alat standar registrasi belum lengkap.');
            }
            if ($hasilAtasI === '') {
                json_response('error', 'Form I: Hasil Periksa ATAS wajib diisi.');
            }
            if ($hasilBawahI === '') {
                json_response('error', 'Form I: Hasil Periksa BAWAH wajib diisi.');
            }
            if ($kodeWarnaI === '') {
                json_response('error', 'Form I: Kode Warna wajib diisi.');
            }
            if (!in_array($putusanI, ['OK','NG'], true)) {
                json_response('error', 'Form I: Putusan wajib dipilih OK atau NG.');
            }

            $status_qa = ($putusanI === 'OK') ? 'Good' : 'Bad';

            $iRows[] = [
                'std_spesifikasi' => $stdSpesifikasiI,
                'std_no_alat' => $stdNoAlatI,
                'lokasi_atas' => 'ATAS',
                'lokasi_bawah' => 'BAWAH',
                'hasil_atas' => $hasilAtasI,
                'hasil_bawah' => $hasilBawahI,
                'kode_warna' => $kodeWarnaI,
                'putusan' => $putusanI
            ];

            $hasil_decoded = $iRows;
            $hasil_json = mysqli_real_escape_string(
                $konek,
                json_encode($iRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        // FORM J / VERIFIKASI RADIUS GAUGE
        $isFormJ = ($format_laporan === 'J' || $format_laporan === 'FORM J');

        if (
            !$isFormJ &&
            is_array($hasil_decoded) &&
            isset($hasil_decoded[0]) &&
            is_array($hasil_decoded[0])
        ) {
            $probeJ = $hasil_decoded[0];
            $isFormJ =
                array_key_exists('hasil_verifikasi', $probeJ) &&
                array_key_exists('alat_verifikasi', $probeJ);
        }

        $jRows = [];

        if ($isFormJ) {
            if (
                !is_array($hasil_decoded) ||
                count($hasil_decoded) === 0 ||
                !isset($hasil_decoded[0]) ||
                !is_array($hasil_decoded[0])
            ) {
                json_response('error', 'Form J: hasil verifikasi belum diisi.');
            }

            $incomingJ = $hasil_decoded[0];

            $alatVerifikasiJ = trim((string)($d_part['standar_uji'] ?? ''));
            $hasilVerifikasiJ = strtoupper(trim((string)($incomingJ['hasil_verifikasi'] ?? '')));

            if ($alatVerifikasiJ === '') {
                json_response('error', 'Form J: Alat Verifikasi pada data registrasi belum diisi.');
            }

            if (!in_array($hasilVerifikasiJ, ['O','X'], true)) {
                json_response('error', 'Form J: Hasil Verifikasi wajib O atau X.');
            }

            $status_qa = ($hasilVerifikasiJ === 'O') ? 'Good' : 'Bad';

            $jRows[] = [
                'no_part' => $d_part['no_part'] ?? '',
                'spesifikasi' => $d_part['spesifikasi'] ?? $d_part['ukuran'] ?? '',
                'alat_verifikasi' => $alatVerifikasiJ,
                'hasil_verifikasi' => $hasilVerifikasiJ
            ];

            $hasil_decoded = $jRows;
            $hasil_json = mysqli_real_escape_string(
                $konek,
                json_encode($jRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        // FORM K / PEMERIKSAAN VOLTAGE ULTRASONIC
        $isFormK=($format_laporan==='K'||$format_laporan==='FORM K'); if(!$isFormK&&is_array($hasil_decoded)&&isset($hasil_decoded[0])&&is_array($hasil_decoded[0])){$probeK=$hasil_decoded[0];$isFormK=array_key_exists('nilai_ditentukan',$probeK)&&array_key_exists('nilai_pengukuran',$probeK);} $kRows=[];$kOverallOk=true;
        if($isFormK){$registeredK=json_decode((string)($d_part['titik_standar_json']??'[]'),true);if(!is_array($registeredK)||count($registeredK)===0)json_response('error','Form K: data registrasi titik pemeriksaan tidak ditemukan.');if(!is_array($hasil_decoded)||count($hasil_decoded)===0)json_response('error','Form K: hasil pemeriksaan belum diisi.');if(count($registeredK)!==count($hasil_decoded))json_response('error','Form K: jumlah hasil tidak sesuai titik registrasi.');$tolK=isset($d_part['toleransi_global'])?abs((float)$d_part['toleransi_global']):0.0;if($tolK<=0)json_response('error','Form K: Standar Toleransi (%) belum valid.');foreach($registeredK as $i=>$regRow){$regRow=is_array($regRow)?$regRow:[];$nRaw=trim((string)($regRow['nilai_ditentukan']??''));$incoming=(isset($hasil_decoded[$i])&&is_array($hasil_decoded[$i]))?$hasil_decoded[$i]:[];$uRaw=trim((string)($incoming['nilai_pengukuran']??''));if($nRaw===''||!is_numeric($nRaw))json_response('error','Form K: Nilai yang Ditentukan baris '.($i+1).' tidak valid.');if($uRaw===''||!is_numeric($uRaw))json_response('error','Form K: Nilai Pengukuran baris '.($i+1).' wajib berupa angka.');$n=(float)$nRaw;$u=(float)$uRaw;$sel=$n-$u;$batas=abs($n)*($tolK/100);$lulus=abs($sel)<=($batas+1e-12);if(!$lulus)$kOverallOk=false;$kRows[]=['nilai_ditentukan'=>$nRaw,'nilai_pengukuran'=>$uRaw,'selisih'=>number_format($sel,1,'.',''),'toleransi_persen'=>$tolK,'status'=>$lulus?'Lulus':'Tidak Lulus'];}$status_qa=$kOverallOk?'Good':'Bad';$hasil_decoded=$kRows;$hasil_json=mysqli_real_escape_string($konek,json_encode($kRows,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));}

        // FORM L / KALIBRASI PRESSURE GAUGE
        $isFormL = ($format_laporan === 'L' || $format_laporan === 'FORM L');
        if (!$isFormL && is_array($hasil_decoded) && isset($hasil_decoded[0]) && is_array($hasil_decoded[0])) {
            $probeL = $hasil_decoded[0];
            $isFormL = array_key_exists('arah', $probeL)
                    && array_key_exists('applied_value', $probeL)
                    && array_key_exists('indicated_value', $probeL);
        }

        $lRows = [];
        $lOverallOk = true;

        if ($isFormL) {
            $registeredL = json_decode((string)($d_part['titik_standar_json'] ?? '[]'), true);
            if (!is_array($registeredL) || count($registeredL) === 0) {
                json_response('error', 'Form L: data registrasi Applied Value tidak ditemukan.');
            }
            if (!is_array($hasil_decoded) || count($hasil_decoded) === 0) {
                json_response('error', 'Form L: hasil kalibrasi belum diisi.');
            }
            if (count($registeredL) !== count($hasil_decoded)) {
                json_response('error', 'Form L: jumlah hasil tidak sesuai titik registrasi.');
            }

            $tolL = isset($d_part['toleransi_global']) ? abs((float)$d_part['toleransi_global']) : 0.0;
            if ($tolL <= 0) {
                json_response('error', 'Form L: Standar Toleransi (MPa) belum valid.');
            }

            foreach ($registeredL as $i => $regRowL) {
                $regRowL = is_array($regRowL) ? $regRowL : [];

                $arahL = strtoupper(trim((string)($regRowL['arah'] ?? '')));
                $appliedRawL = trim((string)($regRowL['applied_value'] ?? ''));
                $stdNoL = strtoupper(trim((string)($regRowL['std_no_alat'] ?? '')));

                $incomingL = isset($hasil_decoded[$i]) && is_array($hasil_decoded[$i])
                    ? $hasil_decoded[$i] : [];
                $indicatedRawL = trim((string)($incomingL['indicated_value'] ?? ''));

                if (!in_array($arahL, ['NAIK','TURUN'], true)) {
                    json_response('error', 'Form L: arah titik registrasi tidak valid.');
                }
                if ($appliedRawL === '' || !is_numeric($appliedRawL)) {
                    json_response('error', 'Form L: Applied Value registrasi baris '.($i + 1).' tidak valid.');
                }
                if ($indicatedRawL === '' || !is_numeric($indicatedRawL)) {
                    json_response('error', 'Form L: Indicated Value baris '.($i + 1).' wajib berupa angka.');
                }

                $appliedNumL = (float)$appliedRawL;
                $indicatedNumL = (float)$indicatedRawL;
                $correctionL = $appliedNumL - $indicatedNumL;
                $passL = abs($correctionL) <= ($tolL + 1e-12);

                if (!$passL) $lOverallOk = false;

                $corrTextL = rtrim(rtrim(number_format($correctionL, 4, '.', ''), '0'), '.');
                if ($corrTextL === '-0') $corrTextL = '0';

                $lRows[] = [
                    'arah' => $arahL,
                    'applied_value' => $appliedRawL,
                    'indicated_value' => $indicatedRawL,
                    'correction' => $corrTextL,
                    'std_no_alat' => $stdNoL,
                    'toleransi' => $tolL,
                    'status' => $passL ? 'OK' : 'BURUK'
                ];
            }

            $status_qa = $lOverallOk ? 'Good' : 'Bad';
            $hasil_decoded = $lRows;
            $hasil_json = mysqli_real_escape_string($konek, json_encode($lRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        // FORM M / KALIBRASI PROFILE PROJECTOR
        $isFormM = ($format_laporan === 'M' || $format_laporan === 'FORM M');

        if (
            !$isFormM &&
            is_array($hasil_decoded) &&
            isset($hasil_decoded[0]) &&
            is_array($hasil_decoded[0])
        ) {
            $probeM = $hasil_decoded[0];
            $isFormM = array_key_exists('jenis', $probeM)
                    && (
                        array_key_exists('std_mm', $probeM)
                        || array_key_exists('std_degree', $probeM)
                    );
        }

        $mRows = [];
        $mOverallOk = true;
        $mStdSudut = '';
        $mTolSudut = 0.0;

        if ($isFormM) {
            $registeredM = json_decode((string)($d_part['titik_standar_json'] ?? '[]'), true);

            if (!is_array($registeredM) || count($registeredM) === 0) {
                json_response('error', 'Form M: data standar registrasi tidak ditemukan.');
            }

            if (!is_array($hasil_decoded) || count($hasil_decoded) !== count($registeredM)) {
                json_response('error', 'Form M: jumlah hasil tidak sesuai dengan titik registrasi.');
            }

            $tolAxisM = isset($d_part['toleransi_global'])
                ? abs((float)$d_part['toleransi_global'])
                : 0.0;

            if ($tolAxisM <= 0) {
                json_response('error', 'Form M: Standar Toleransi Axis belum valid.');
            }

            foreach ($registeredM as $i => $regM) {
                $regM = is_array($regM) ? $regM : [];
                $incomingM = isset($hasil_decoded[$i]) && is_array($hasil_decoded[$i])
                    ? $hasil_decoded[$i]
                    : [];

                $jenisM = strtoupper(trim((string)($regM['jenis'] ?? '')));
                $rowStdSudutM = trim((string)($regM['std_sudut'] ?? ''));
                $rowTolSudutM = trim((string)($regM['tol_sudut'] ?? ''));

                if ($mStdSudut === '' && $rowStdSudutM !== '') {
                    $mStdSudut = $rowStdSudutM;
                }
                if ($mTolSudut <= 0 && is_numeric($rowTolSudutM)) {
                    $mTolSudut = abs((float)$rowTolSudutM);
                }

                if ($jenisM === 'AXIS') {
                    $stdRaw = trim((string)($regM['std_mm'] ?? ''));
                    $xEnabled = !empty($regM['x_enabled']);
                    $yEnabled = !empty($regM['y_enabled']);

                    if ($stdRaw === '' || !is_numeric($stdRaw)) {
                        json_response('error', 'Form M: STD Axis registrasi baris '.($i + 1).' tidak valid.');
                    }

                    $stdNum = (float)$stdRaw;
                    $rowOut = [
                        'jenis' => 'AXIS',
                        'std_mm' => $stdRaw,
                        'x_enabled' => $xEnabled,
                        'y_enabled' => $yEnabled
                    ];

                    $measureMap = [
                        'x_left' => $xEnabled,
                        'x_right' => $xEnabled,
                        'y_front' => $yEnabled,
                        'y_back' => $yEnabled
                    ];

                    $rowOk = true;

                    foreach ($measureMap as $key => $enabled) {
                        if (!$enabled) {
                            $rowOut[$key] = '~';
                            $rowOut[$key.'_correction'] = '~';
                            continue;
                        }

                        $raw = trim((string)($incomingM[$key] ?? ''));
                        if ($raw === '' || !is_numeric($raw)) {
                            json_response('error', 'Form M: hasil '.$key.' baris '.($i + 1).' wajib berupa angka.');
                        }

                        $num = (float)$raw;
                        $corr = $stdNum - $num;
                        $ok = abs($corr) <= ($tolAxisM + 1e-12);

                        if (!$ok) {
                            $rowOk = false;
                            $mOverallOk = false;
                        }

                        $corrText = rtrim(rtrim(number_format($corr, 4, '.', ''), '0'), '.');
                        if ($corrText === '-0') $corrText = '0';

                        $rowOut[$key] = $raw;
                        $rowOut[$key.'_correction'] = $corrText;
                    }

                    $rowOut['status'] = $rowOk ? 'OK' : 'BURUK';
                    $mRows[] = $rowOut;

                } elseif ($jenisM === 'ANGLE') {
                    $stdRaw = trim((string)($regM['std_degree'] ?? ''));

                    if ($stdRaw === '' || !is_numeric($stdRaw)) {
                        json_response('error', 'Form M: STD Angle registrasi baris '.($i + 1).' tidak valid.');
                    }

                    if ($mTolSudut <= 0) {
                        json_response('error', 'Form M: Standar Toleransi Sudut (menit busur) belum valid.');
                    }

                    $degRaw = trim((string)($incomingM['hasil_degree'] ?? ''));
                    $minRaw = trim((string)($incomingM['hasil_minute'] ?? ''));
                    $secRaw = trim((string)($incomingM['hasil_second'] ?? ''));

                    if (
                        $degRaw === '' || !is_numeric($degRaw) ||
                        $minRaw === '' || !is_numeric($minRaw) ||
                        $secRaw === '' || !is_numeric($secRaw)
                    ) {
                        json_response('error', 'Form M: hasil Angle baris '.($i + 1).' wajib lengkap.');
                    }

                    $deg = (float)$degRaw;
                    $min = (float)$minRaw;
                    $sec = (float)$secRaw;

                    if ($min < 0 || $min >= 60 || $sec < 0 || $sec >= 60) {
                        json_response('error', 'Form M: menit/detik Angle harus 0 sampai kurang dari 60.');
                    }

                    $stdDeg = (float)$stdRaw;
                    $resultSec = ($deg * 3600) + ($min * 60) + $sec;
                    $stdSec = $stdDeg * 3600;
                    $corrSec = $resultSec - $stdSec;
                    $corrDeg = $corrSec / 3600;

                    // Toleransi sudut pada format asli dinyatakan dalam MENIT BUSUR.
                    // Contoh: 0.1' = 6".
                    $tolSudutDetik = $mTolSudut * 60.0;
                    $ok = abs($corrSec) <= ($tolSudutDetik + 1e-12);

                    if (!$ok) $mOverallOk = false;

                    $sign = $corrSec < 0 ? '-' : '';
                    $absSec = abs($corrSec);
                    $corrMin = floor($absSec / 60);
                    $corrSecond = $absSec - ($corrMin * 60);

                    $secText = rtrim(rtrim(number_format($corrSecond, 2, '.', ''), '0'), '.');
                    if ($secText === '') $secText = '0';

                    $corrText = $sign
                        . str_pad((string)$corrMin, 2, '0', STR_PAD_LEFT)
                        . " ' "
                        . str_pad($secText, 2, '0', STR_PAD_LEFT)
                        . ' "';

                    $mRows[] = [
                        'jenis' => 'ANGLE',
                        'std_degree' => $stdRaw,
                        'hasil_degree' => $degRaw,
                        'hasil_minute' => $minRaw,
                        'hasil_second' => $secRaw,
                        'correction' => $corrText,
                        'correction_seconds' => $corrSec,
                        'correction_degree' => $corrDeg,
                        'toleransi_menit' => $mTolSudut,
                        'toleransi_detik' => $tolSudutDetik,
                        'status' => $ok ? 'OK' : 'BURUK'
                    ];
                } else {
                    json_response('error', 'Form M: jenis standar registrasi tidak dikenali.');
                }
            }

            $status_qa = $mOverallOk ? 'Good' : 'Bad';
            $hasil_decoded = $mRows;

            $hasil_json = mysqli_real_escape_string(
                $konek,
                json_encode($mRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        // FORM O / PEMERIKSAAN SNAP GAUGE
        $isFormO = ($format_laporan === 'O' || $format_laporan === 'FORM O');
        if (
            !$isFormO &&
            is_array($hasil_decoded) &&
            isset($hasil_decoded[0]) &&
            is_array($hasil_decoded[0])
        ) {
            $probeO = $hasil_decoded[0];
            $isFormO = array_key_exists('go_hasil_min', $probeO)
                    && array_key_exists('go_hasil_max', $probeO)
                    && array_key_exists('notgo_hasil_min', $probeO)
                    && array_key_exists('notgo_hasil_max', $probeO);
        }

        $oRows = [];
        if ($isFormO) {
            $registeredO = json_decode((string)($d_part['titik_standar_json'] ?? '[]'), true);
            if (!is_array($registeredO) || count($registeredO) === 0) {
                json_response('error', 'Form O: data registrasi tidak ditemukan.');
            }
            if (!is_array($hasil_decoded) || count($hasil_decoded) !== count($registeredO)) {
                json_response('error', 'Form O: jumlah hasil tidak sesuai dengan data registrasi.');
            }

            $allOkO = true;
            foreach ($registeredO as $i => $regO) {
                $regO = is_array($regO) ? $regO : [];
                $incomingO = isset($hasil_decoded[$i]) && is_array($hasil_decoded[$i]) ? $hasil_decoded[$i] : [];

                foreach (['spesifikasi','tol_plus','tol_minus','go_std','go_tol_near','go_tol_far','notgo_std','notgo_tol'] as $f) {
                    if (!isset($regO[$f]) || !is_numeric((string)$regO[$f])) {
                        json_response('error', 'Form O: data registrasi '.$f.' baris '.($i + 1).' tidak valid.');
                    }
                }

                $goStd = (float)$regO['go_std'];
                $goTolNear = (float)$regO['go_tol_near'];
                $goTolFar = (float)$regO['go_tol_far'];
                $notStd = (float)$regO['notgo_std'];
                $notTol = abs((float)$regO['notgo_tol']);

                $goMin = $goStd + min($goTolNear, $goTolFar);
                $goMax = $goStd + max($goTolNear, $goTolFar);
                $notMin = $notStd - $notTol;
                $notMax = $notStd + $notTol;

                $goHasilMin = strtoupper(trim((string)($incomingO['go_hasil_min'] ?? '')));
                $goHasilMax = strtoupper(trim((string)($incomingO['go_hasil_max'] ?? '')));
                $notHasilMin = strtoupper(trim((string)($incomingO['notgo_hasil_min'] ?? '')));
                $notHasilMax = strtoupper(trim((string)($incomingO['notgo_hasil_max'] ?? '')));

                foreach ([$goHasilMin, $goHasilMax, $notHasilMin, $notHasilMax] as $v) {
                    if (!in_array($v, ['OK','NG'], true)) {
                        json_response('error', 'Form O: hasil standar baris '.($i + 1).' harus OK atau NG.');
                    }
                }

                $rowOk = ($goHasilMin === 'OK' && $goHasilMax === 'OK' && $notHasilMin === 'OK' && $notHasilMax === 'OK');
                if (!$rowOk) $allOkO = false;

                $oRows[] = [
                    'no_alat' => $regO['no_alat'] ?? '',
                    'spesifikasi' => (string)$regO['spesifikasi'],
                    'tol_plus' => (string)$regO['tol_plus'],
                    'tol_minus' => (string)$regO['tol_minus'],
                    'go_std' => (string)$regO['go_std'],
                    'go_tol_near' => (string)$regO['go_tol_near'],
                    'go_tol_far' => (string)$regO['go_tol_far'],
                    'go_min' => rtrim(rtrim(number_format($goMin, 3, '.', ''), '0'), '.'),
                    'go_max' => rtrim(rtrim(number_format($goMax, 3, '.', ''), '0'), '.'),
                    'notgo_std' => (string)$regO['notgo_std'],
                    'notgo_tol' => (string)$regO['notgo_tol'],
                    'notgo_min' => rtrim(rtrim(number_format($notMin, 3, '.', ''), '0'), '.'),
                    'notgo_max' => rtrim(rtrim(number_format($notMax, 3, '.', ''), '0'), '.'),
                    'go_hasil_min' => $goHasilMin,
                    'go_hasil_max' => $goHasilMax,
                    'notgo_hasil_min' => $notHasilMin,
                    'notgo_hasil_max' => $notHasilMax,
                    'kode_warna' => $rowOk ? 'HIJAU' : 'MERAH',
                    'keputusan' => $rowOk ? 'OK' : 'BAD',
                    'no_gambar' => $regO['no_gambar'] ?? '',
                    'status' => $rowOk ? 'OK' : 'BAD'
                ];
            }

            $status_qa = $allOkO ? 'Good' : 'Bad';
            $hasil_decoded = $oRows;
            $hasil_json = mysqli_real_escape_string($konek, json_encode($oRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        // FORM P / PEMERIKSAAN SK GAUGE
        $isFormP = ($format_laporan === 'P' || $format_laporan === 'FORM P');
        if (
            !$isFormP &&
            is_array($hasil_decoded) &&
            isset($hasil_decoded[0]) &&
            is_array($hasil_decoded[0]) &&
            array_key_exists('measurements', $hasil_decoded[0])
        ) {
            $isFormP = true;
        }

        $pRows = [];
        if ($isFormP) {
            $registeredP = json_decode((string)($d_part['titik_standar_json'] ?? '[]'), true);
            if (!is_array($registeredP) || count($registeredP) === 0) {
                json_response('error', 'Form P: data registrasi SK Gauge tidak ditemukan.');
            }
            if (!is_array($hasil_decoded) || count($hasil_decoded) !== count($registeredP)) {
                json_response('error', 'Form P: jumlah alat hasil tidak sesuai registrasi.');
            }

            $allOkP = true;
            foreach ($registeredP as $i => $regItem) {
                $regItem = is_array($regItem) ? $regItem : [];
                $incomingItem = isset($hasil_decoded[$i]) && is_array($hasil_decoded[$i]) ? $hasil_decoded[$i] : [];
                $regMs = $regItem['measurements'] ?? [];
                $inMs = $incomingItem['measurements'] ?? [];

                if (!is_array($regMs) || !is_array($inMs) || count($regMs) !== count($inMs)) {
                    json_response('error', 'Form P: jumlah titik alat #'.($i + 1).' tidak sesuai registrasi.');
                }

                $outItem = [
                    'no_alat' => $regItem['no_alat'] ?? '',
                    'type' => $regItem['type'] ?? '',
                    'ukuran' => $regItem['ukuran'] ?? '',
                    'upart' => $regItem['upart'] ?? '',
                    'no_gambar' => $regItem['no_gambar'] ?? '',
                    'measurements' => []
                ];
                $itemOk = true;

                foreach ($regMs as $j => $regM) {
                    $regM = is_array($regM) ? $regM : [];
                    $inM = isset($inMs[$j]) && is_array($inMs[$j]) ? $inMs[$j] : [];
                    $min = $regM['min'] ?? '';
                    $max = $regM['max'] ?? '';
                    if (!is_numeric((string)$min) || !is_numeric((string)$max)) {
                        json_response('error', 'Form P: range titik '.($j + 1).' alat #'.($i + 1).' tidak valid.');
                    }
                    $minN = (float)$min;
                    $maxN = (float)$max;
                    $actualCount = (string)($regM['actual_count'] ?? '2');

                    $a1 = trim((string)($inM['actual1'] ?? ''));
                    $a2 = trim((string)($inM['actual2'] ?? ''));
                    if ($a1 === '' || !is_numeric($a1)) {
                        json_response('error', 'Form P: Aktual 1 titik '.($j + 1).' alat #'.($i + 1).' wajib berupa angka.');
                    }
                    if ($actualCount === '2' && ($a2 === '' || !is_numeric($a2))) {
                        json_response('error', 'Form P: Aktual 2 titik '.($j + 1).' alat #'.($i + 1).' wajib berupa angka.');
                    }

                    $rowOk = ((float)$a1 >= $minN && (float)$a1 <= $maxN);
                    if ($actualCount === '2') {
                        $rowOk = $rowOk && ((float)$a2 >= $minN && (float)$a2 <= $maxN);
                    } else {
                        $a2 = '';
                    }
                    if (!$rowOk) $itemOk = false;

                    $outItem['measurements'][] = [
                        'alat_nama' => $regM['alat_nama'] ?? '',
                        'alat_no' => $regM['alat_no'] ?? '',
                        'std_label' => $regM['std_label'] ?? '',
                        'min' => (string)$min,
                        'max' => (string)$max,
                        'actual_count' => $actualCount,
                        'actual1' => $a1,
                        'actual2' => $a2,
                        'status' => $rowOk ? 'OK' : 'BAD'
                    ];
                }

                $outItem['keputusan'] = $itemOk ? 'OK' : 'BAD';
                if (!$itemOk) $allOkP = false;
                $pRows[] = $outItem;
            }

            $status_qa = $allOkP ? 'Good' : 'Bad';
            $hasil_decoded = $pRows;
            $hasil_json = mysqli_real_escape_string($konek, json_encode($pRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        // FORM Q / SERATION PLUG GAUGE + PLUG GAUGE
        $isFormQ = ($format_laporan === 'Q' || $format_laporan === 'FORM Q');
        if (!$isFormQ && is_array($hasil_decoded) && count($hasil_decoded) === 1 && is_array($hasil_decoded[0]) && isset($hasil_decoded[0]['subtype'])) {
            $isFormQ = true;
        }
        $qRows = [];
        if ($isFormQ) {
            $registeredQ = json_decode((string)($d_part['titik_standar_json'] ?? '[]'), true);
            if (!is_array($registeredQ) || count($registeredQ) !== 1 || !is_array($registeredQ[0])) {
                json_response('error', 'Form Q: data registrasi tidak ditemukan.');
            }
            if (!is_array($hasil_decoded) || count($hasil_decoded) !== 1 || !is_array($hasil_decoded[0])) {
                json_response('error', 'Form Q: hasil pemeriksaan tidak valid.');
            }
            $regQ = $registeredQ[0];
            $inQ = $hasil_decoded[0];
            $subtype = strtoupper(trim((string)($regQ['subtype'] ?? '')));
            $outQ = $regQ;
            $allOkQ = true;

            $calcRange = function($r) {
                $std = (float)$r['std'];
                $p = (float)$r['tol_plus'];
                $m = (float)$r['tol_minus'];
                return [$std + min($p,$m), $std + max($p,$m)];
            };
            $fmt = function($v) {
                $s = rtrim(rtrim(number_format((float)$v, 3, '.', ''), '0'), '.');
                return $s === '-0' ? '0' : $s;
            };

            if ($subtype === 'SERATION') {
                foreach (['out','pitch'] as $kind) {
                    $r = isset($regQ[$kind]) && is_array($regQ[$kind]) ? $regQ[$kind] : [];
                    if (!isset($r['std'],$r['tol_plus'],$r['tol_minus']) || !is_numeric($r['std']) || !is_numeric($r['tol_plus']) || !is_numeric($r['tol_minus'])) {
                        json_response('error', 'Form Q Seration: standar '.$kind.' registrasi tidak valid.');
                    }
                    [$min,$max] = $calcRange($r);
                    $actual = isset($inQ[$kind]['actual']) && is_array($inQ[$kind]['actual']) ? $inQ[$kind]['actual'] : [];
                    if (count($actual) !== 3) json_response('error', 'Form Q Seration: hasil '.$kind.' harus 3 titik CROSS.');
                    $ok = true; $cleanActual=[];
                    foreach ($actual as $v) {
                        $v = trim((string)$v);
                        if ($v === '' || !is_numeric($v)) json_response('error', 'Form Q Seration: semua hasil '.$kind.' wajib berupa angka.');
                        $n=(float)$v; if($n<$min || $n>$max) $ok=false; $cleanActual[]=$v;
                    }
                    if(!$ok) $allOkQ=false;
                    $outQ[$kind]['min']=$fmt($min); $outQ[$kind]['max']=$fmt($max); $outQ[$kind]['actual']=$cleanActual; $outQ[$kind]['status']=$ok?'OK':'BAD';
                }
            } elseif ($subtype === 'PLUG') {
                foreach (['go','notgo'] as $kind) {
                    $r = isset($regQ[$kind]) && is_array($regQ[$kind]) ? $regQ[$kind] : [];
                    if (!isset($r['std'],$r['tol_plus'],$r['tol_minus']) || !is_numeric($r['std']) || !is_numeric($r['tol_plus']) || !is_numeric($r['tol_minus'])) {
                        json_response('error', 'Form Q Plug Gauge: standar '.$kind.' registrasi tidak valid.');
                    }
                    [$min,$max]=$calcRange($r);
                    $actual = isset($inQ[$kind]['actual']) && is_array($inQ[$kind]['actual']) ? $inQ[$kind]['actual'] : [];
                    $keys=['front1','front2','back1','back2']; $filled=0; $ok=true; $clean=[];
                    foreach($keys as $k){
                        $v=trim((string)($actual[$k]??'')); $clean[$k]=$v;
                        if($v==='') continue;
                        $filled++;
                        if(!is_numeric($v)) json_response('error','Form Q Plug Gauge: nilai '.$kind.' '.$k.' harus angka.');
                        $n=(float)$v; if($n<$min || $n>$max) $ok=false;
                    }
                    if($filled===0) json_response('error','Form Q Plug Gauge: minimal 1 hasil ukur untuk '.$kind.' wajib diisi.');
                    if(!$ok) $allOkQ=false;
                    $outQ[$kind]['min']=$fmt($min); $outQ[$kind]['max']=$fmt($max); $outQ[$kind]['actual']=$clean; $outQ[$kind]['status']=$ok?'OK':'BAD';
                }
            } else {
                json_response('error','Form Q: subtype tidak dikenal.');
            }

            $outQ['kode_warna'] = $allOkQ ? 'HIJAU' : 'MERAH';
            $outQ['keputusan'] = $allOkQ ? 'OK' : 'BAD';
            $status_qa = $allOkQ ? 'Good' : 'Bad';
            $qRows = [$outQ];
            $hasil_decoded = $qRows;
            $hasil_json = mysqli_real_escape_string($konek, json_encode($qRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $alat_id = isset($d_part['id']) ? (int)$d_part['id'] : 0;
        $tgl_jadwal_lama = !empty($d_part['jadwal_kalibrasi'])
            ? $d_part['jadwal_kalibrasi']
            : date('Y-m-d');

        $periode = (!empty($d_part['periode_kalibrasi']))
            ? (int)$d_part['periode_kalibrasi']
            : 30;

        /*
         * Form A dapat mengirim "Periksa Berikut".
         * Jika kosong, tetap gunakan logika sistem lama:
         * tanggal pemeriksaan + periode kalibrasi.
         */
        $periksa_berikut = trim($_POST['periksa_berikut'] ?? '');
        $jadwal_baru = '';

        if ($periksa_berikut !== '') {
            $dt_berikut = DateTime::createFromFormat('Y-m-d', $periksa_berikut);

            if (!$dt_berikut || $dt_berikut->format('Y-m-d') !== $periksa_berikut) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Tanggal Periksa Berikut tidak valid.'
                ]);
                exit;
            }

            $jadwal_baru = $periksa_berikut;
        } else {
            $jadwal_baru = date(
                'Y-m-d',
                strtotime($tgl_pengecekan . " +$periode days")
            );
        }

        $reportB = null;
        if ($isFormB) {
            $reportNo = trim($_POST['no_laporan'] ?? '');
            if ($reportNo === '') json_response('error', 'No. Laporan Form B wajib diisi.');
            $standardsB = qa_b_decode($d_part['titik_standar_json'] ?? '');
            $metaB = $standardsB[0]['_meta'] ?? [];
            $reportB = [
                'format'=>'B', 'no_laporan'=>$reportNo,
                'foreman'=>trim($_POST['foreman'] ?? ''), 'supervisor'=>trim($_POST['supervisor'] ?? ''), 'no_part'=>$d_part['no_part'],
                'nama_part'=>$d_part['nama_part'], 'spesifikasi'=>$d_part['spesifikasi'] ?? $d_part['ukuran'] ?? '',
                'type'=>$d_part['type'] ?? '', 'seksi_pemilik'=>$d_part['seksi_pemilik'] ?? '',
                'petugas'=>$_SESSION['nama_lengkap'] ?? $_SESSION['userid'] ?? '',
                'tanggal_periksa'=>$tgl_pengecekan, 'jadwal_berikutnya'=>$jadwal_baru,
                'metode'=>$metaB['metode_uji'] ?? $d_part['metode_uji'] ?? '',
                'standar_uji'=>$metaB['standar_uji'] ?? $d_part['standar_uji'] ?? '',
                'traceability'=>$metaB['traceability'] ?? $d_part['traceability'] ?? '',
                'suhu'=>$_POST['suhu'] ?? '', 'kelembapan'=>$_POST['kelembapan'] ?? '',
                'keterangan'=>$_POST['keterangan'] ?? '', 'keputusan'=>$status_qa, 'hasil_array'=>$bRows
            ];
            $hasil_json=mysqli_real_escape_string($konek,json_encode($reportB,JSON_UNESCAPED_UNICODE));
        }


        $reportC = null;
        if ($isFormC) {
            $reportNoC = trim($_POST['no_laporan'] ?? '');
            if ($reportNoC === '') json_response('error', 'No. Laporan Form C wajib diisi.');

            $reportC = [
                'format' => 'C',
                'no_laporan' => $reportNoC,
                'foreman' => trim($_POST['foreman'] ?? ''),
                'supervisor' => trim($_POST['supervisor'] ?? ''),
                'no_part' => $d_part['no_part'] ?? '',
                'nama_part' => $d_part['nama_part'] ?? '',
                'spesifikasi' => $d_part['spesifikasi'] ?? $d_part['ukuran'] ?? '',
                'resolusi' => $d_part['resolusi'] ?? '',
                'seksi_pemilik' => $d_part['seksi_pemilik'] ?? '',
                'petugas' => $_SESSION['nama_lengkap'] ?? $_SESSION['userid'] ?? '',
                'tanggal_periksa' => $tgl_pengecekan,
                'jadwal_berikutnya' => $jadwal_baru,
                'metode' => $d_part['metode_uji'] ?? '',
                'standar_uji' => $d_part['standar_uji'] ?? '',
                'traceability' => $d_part['traceability'] ?? '',
                'toleransi' => $d_part['toleransi_global'] ?? '',
                'satuan' => $d_part['satuan'] ?? '°C',
                'suhu' => $_POST['suhu'] ?? '',
                'kelembapan' => $_POST['kelembapan'] ?? '',
                'keterangan' => $_POST['keterangan'] ?? '',
                'keputusan' => $status_qa,
                'hasil_array' => $cRows
            ];

            // Untuk Form C, hasil_json menyimpan snapshot laporan lengkap agar histori
            // tetap bisa dicetak walaupun master alat kemudian berubah.
            $hasil_json = mysqli_real_escape_string(
                $konek,
                json_encode($reportC, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        $reportD = null;
        if ($isFormD) {
            $reportNoD = trim($_POST['no_laporan'] ?? '');
            if ($reportNoD === '') json_response('error', 'No. Laporan Form D wajib diisi.');

            $reportD = [
                'format' => 'D',
                'no_laporan' => $reportNoD,
                'foreman' => trim($_POST['foreman'] ?? ''),
                'supervisor' => trim($_POST['supervisor'] ?? ''),
                'no_part' => $d_part['no_part'] ?? '',
                'nama_part' => $d_part['nama_part'] ?? '',
                'spesifikasi' => $d_part['spesifikasi'] ?? $d_part['ukuran'] ?? '',
                'resolusi' => $d_part['resolusi'] ?? '',
                'seksi_pemilik' => $d_part['seksi_pemilik'] ?? '',
                'petugas' => $_SESSION['nama_lengkap'] ?? $_SESSION['userid'] ?? '',
                'tanggal_periksa' => $tgl_pengecekan,
                'jadwal_berikutnya' => $jadwal_baru,
                'metode' => $d_part['metode_uji'] ?? '',
                'standar_uji' => $d_part['standar_uji'] ?? '',
                'traceability' => $d_part['traceability'] ?? '',
                'toleransi' => $d_part['toleransi_global'] ?? '',
                'suhu' => $_POST['suhu'] ?? '',
                'kelembapan' => $_POST['kelembapan'] ?? '',
                'keterangan' => $_POST['keterangan'] ?? '',
                'keputusan' => $status_qa,
                'hasil_array' => $dRows
            ];
            $hasil_json = mysqli_real_escape_string($konek, json_encode($reportD, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $reportH = null;
        if ($isFormH) {
            $reportNoH = trim($_POST['no_laporan'] ?? '');
            if ($reportNoH === '') json_response('error', 'No. Laporan Form H wajib diisi.');

            $reportH = [
                'format' => 'H',
                'no_laporan' => $reportNoH,
                'foreman' => trim($_POST['foreman'] ?? ''),
                'supervisor' => trim($_POST['supervisor'] ?? ''),
                'no_part' => $d_part['no_part'] ?? '',
                'nama_part' => $d_part['nama_part'] ?? '',
                'spesifikasi' => $d_part['spesifikasi'] ?? $d_part['ukuran'] ?? '',
                'seksi_pemilik' => $d_part['seksi_pemilik'] ?? '',
                'petugas' => $_SESSION['nama_lengkap'] ?? $_SESSION['userid'] ?? '',
                'tanggal_periksa' => $tgl_pengecekan,
                'jadwal_berikutnya' => $jadwal_baru,
                'metode' => $d_part['metode_uji'] ?? '',
                'standar_uji' => $d_part['standar_uji'] ?? '',
                'traceability' => $d_part['traceability'] ?? '',
                'suhu' => $_POST['suhu'] ?? '',
                'kelembapan' => $_POST['kelembapan'] ?? '',
                'keterangan' => $_POST['keterangan'] ?? '',
                'keputusan' => $status_qa,
                'hasil_array' => $hRows
            ];
            $hasil_json = mysqli_real_escape_string($konek, json_encode($reportH, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $reportI = null;
        if ($isFormI) {
            $reportNoI = trim($_POST['no_laporan'] ?? '');
            if ($reportNoI === '') {
                json_response('error', 'No. Laporan Form I wajib diisi.');
            }

            $reportI = [
                'format' => 'I',
                'no_laporan' => $reportNoI,
                'foreman' => trim($_POST['foreman'] ?? ''),
                'supervisor' => trim($_POST['supervisor'] ?? ''),
                'no_part' => $d_part['no_part'] ?? '',
                'nama_part' => $d_part['nama_part'] ?? '',
                'spesifikasi' => $d_part['spesifikasi'] ?? $d_part['ukuran'] ?? '',
                'seksi_pemilik' => $d_part['seksi_pemilik'] ?? '',
                'petugas' => $_SESSION['nama_lengkap'] ?? $_SESSION['userid'] ?? '',
                'tanggal_periksa' => $tgl_pengecekan,
                'jadwal_berikutnya' => $jadwal_baru,
                'metode' => $d_part['metode_uji'] ?? '',
                'standar_uji' => $d_part['standar_uji'] ?? '',
                'traceability' => $d_part['traceability'] ?? '',
                'suhu' => $_POST['suhu'] ?? '',
                'kelembapan' => $_POST['kelembapan'] ?? '',
                'keterangan' => $_POST['keterangan'] ?? '',
                'keputusan' => $status_qa,
                'hasil_array' => $iRows
            ];

            $hasil_json = mysqli_real_escape_string(
                $konek,
                json_encode($reportI, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        $reportJ = null;

        if ($isFormJ) {
            $reportNoJ = trim($_POST['no_laporan'] ?? '');

            if ($reportNoJ === '') {
                json_response('error', 'No. Laporan Form J wajib diisi.');
            }

            $reportJ = [
                'format' => 'J',
                'no_laporan' => $reportNoJ,
                'foreman' => trim($_POST['foreman'] ?? ''),
                'supervisor' => trim($_POST['supervisor'] ?? ''),

                'no_part' => $d_part['no_part'] ?? '',
                'nama_part' => $d_part['nama_part'] ?? '',
                'spesifikasi' => $d_part['spesifikasi'] ?? $d_part['ukuran'] ?? '',
                'seksi_pemilik' => $d_part['seksi_pemilik'] ?? '',

                'petugas' => $_SESSION['nama_lengkap'] ?? $_SESSION['userid'] ?? '',
                'tanggal_periksa' => $tgl_pengecekan,
                'jadwal_berikutnya' => $jadwal_baru,

                'metode' => $d_part['metode_uji'] ?? '',
                'alat_verifikasi' => $d_part['standar_uji'] ?? '',

                'keterangan' => $_POST['keterangan'] ?? '',
                'keputusan' => $status_qa,
                'hasil_array' => $jRows
            ];

            $hasil_json = mysqli_real_escape_string(
                $konek,
                json_encode($reportJ, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        $reportK=null;if($isFormK){$reportNoK=trim($_POST['no_laporan']??'');if($reportNoK==='')json_response('error','No. Laporan Form K wajib diisi.');$reportK=['format'=>'K','no_laporan'=>$reportNoK,'foreman'=>trim($_POST['foreman']??''),'supervisor'=>trim($_POST['supervisor']??''),'no_part'=>$d_part['no_part']??'','nama_part'=>$d_part['nama_part']??'','spesifikasi'=>$d_part['spesifikasi']??$d_part['ukuran']??'','resolusi'=>$d_part['resolusi']??'','seksi_pemilik'=>$d_part['seksi_pemilik']??'','petugas'=>$_SESSION['nama_lengkap']??$_SESSION['userid']??'','tanggal_periksa'=>$tgl_pengecekan,'jadwal_berikutnya'=>$jadwal_baru,'metode'=>$d_part['metode_uji']??'','standar_uji'=>$d_part['standar_uji']??'','traceability'=>$d_part['traceability']??'','toleransi'=>$d_part['toleransi_global']??'','satuan'=>$d_part['satuan']??'Volt','suhu'=>$_POST['suhu']??'','kelembapan'=>$_POST['kelembapan']??'','keterangan'=>$_POST['keterangan']??'','keputusan'=>$status_qa,'hasil_array'=>$kRows];$hasil_json=mysqli_real_escape_string($konek,json_encode($reportK,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));}

        $reportL = null;
        if ($isFormL) {
            $reportNoL = trim($_POST['no_laporan'] ?? '');
            if ($reportNoL === '') {
                json_response('error', 'No. Laporan Form L wajib diisi.');
            }

            $stdNoL = isset($lRows[0]['std_no_alat']) ? trim((string)$lRows[0]['std_no_alat']) : '';

            $reportL = [
                'format' => 'L',
                'no_laporan' => $reportNoL,
                'foreman' => trim($_POST['foreman'] ?? ''),
                'supervisor' => trim($_POST['supervisor'] ?? ''),
                'no_part' => $d_part['no_part'] ?? '',
                'nama_part' => $d_part['nama_part'] ?? '',
                'spesifikasi' => $d_part['spesifikasi'] ?? $d_part['ukuran'] ?? '',
                'resolusi' => $d_part['resolusi'] ?? '',
                'seksi_pemilik' => $d_part['seksi_pemilik'] ?? '',
                'petugas' => $_SESSION['nama_lengkap'] ?? $_SESSION['userid'] ?? '',
                'tanggal_periksa' => $tgl_pengecekan,
                'jadwal_berikutnya' => $jadwal_baru,
                'metode' => $d_part['metode_uji'] ?? '',
                'standar_uji' => $d_part['standar_uji'] ?? '',
                'std_no_alat' => $stdNoL,
                'traceability' => $d_part['traceability'] ?? '',
                'toleransi' => $d_part['toleransi_global'] ?? '',
                'satuan' => 'MPa',
                'suhu' => $_POST['suhu'] ?? '',
                'kelembapan' => $_POST['kelembapan'] ?? '',
                'ketidakpastian' => $_POST['ketidakpastian'] ?? '',
                'confidence' => $_POST['confidence'] ?? '',
                'keterangan' => $_POST['keterangan'] ?? '',
                'keputusan' => $status_qa,
                'hasil_array' => $lRows
            ];

            $hasil_json = mysqli_real_escape_string($konek, json_encode($reportL, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $reportM = null;

        if ($isFormM) {
            $reportNoM = trim($_POST['no_laporan'] ?? '');

            if ($reportNoM === '') {
                json_response('error', 'No. Laporan Form M wajib diisi.');
            }

            $reportM = [
                'format' => 'M',
                'no_laporan' => $reportNoM,

                'foreman' => trim($_POST['foreman'] ?? ''),
                'supervisor' => trim($_POST['supervisor'] ?? ''),

                'no_part' => $d_part['no_part'] ?? '',
                'nama_part' => $d_part['nama_part'] ?? '',
                'spesifikasi' => $d_part['spesifikasi'] ?? $d_part['ukuran'] ?? '',
                'resolusi' => $d_part['resolusi'] ?? '',
                'seksi_pemilik' => $d_part['seksi_pemilik'] ?? '',

                'petugas' => $_SESSION['nama_lengkap'] ?? $_SESSION['userid'] ?? '',
                'tanggal_periksa' => $tgl_pengecekan,
                'jadwal_berikutnya' => $jadwal_baru,

                'metode' => $d_part['metode_uji'] ?? '',
                'standar_uji' => $d_part['standar_uji'] ?? '',
                'standar_uji_sudut' => $mStdSudut,
                'traceability' => $d_part['traceability'] ?? '',

                'toleransi_axis' => $d_part['toleransi_global'] ?? '',
                'toleransi_sudut' => $mTolSudut,
                'satuan' => 'mm',

                'suhu' => $_POST['suhu'] ?? '',
                'kelembapan' => $_POST['kelembapan'] ?? '',
                'ketidakpastian' => $_POST['ketidakpastian'] ?? '',
                'confidence' => $_POST['confidence'] ?? '',
                'keterangan' => $_POST['keterangan'] ?? '',

                'keputusan' => $status_qa,
                'hasil_array' => $mRows
            ];

            $hasil_json = mysqli_real_escape_string(
                $konek,
                json_encode($reportM, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        $reportO = null;
        if ($isFormO) {
            $reportNoO = trim($_POST['no_laporan'] ?? '');
            if ($reportNoO === '') {
                json_response('error', 'No. Laporan Form O wajib diisi.');
            }
            $reportO = [
                'format' => 'O',
                'no_laporan' => $reportNoO,
                'nama_alat' => $d_part['nama_part'] ?? '',
                'metode' => $d_part['metode_uji'] ?? '',
                'seksi' => $d_part['seksi_pemilik'] ?? '',
                'standar_uji' => $d_part['standar_uji'] ?? '',
                'traceability' => $d_part['traceability'] ?? '',
                'suhu' => $_POST['suhu'] ?? '',
                'kelembapan' => $_POST['kelembapan'] ?? '',
                'tanggal_periksa' => $tgl_pengecekan,
                'petugas' => $_SESSION['nama_lengkap'] ?? $_SESSION['userid'] ?? '',
                'foreman' => trim($_POST['foreman'] ?? ''),
                'supervisor' => trim($_POST['supervisor'] ?? ''),
                'jadwal_berikutnya' => $jadwal_baru,
                'keputusan' => $status_qa,
                'halaman' => '1 / 1',
                'hasil_array' => $oRows,
                'keterangan' => $_POST['keterangan'] ?? ''
            ];
            $hasil_json = mysqli_real_escape_string($konek, json_encode($reportO, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $reportP = null;
        if ($isFormP) {
            $reportNoP = trim($_POST['no_laporan'] ?? '');
            if ($reportNoP === '') {
                json_response('error', 'No. Laporan Form P wajib diisi.');
            }

            $reportP = [
                'format' => 'P',
                'no_laporan' => $reportNoP,
                'nama_alat' => $d_part['nama_part'] ?? '',
                'metode' => $d_part['metode_uji'] ?? '',
                'seksi' => $d_part['seksi_pemilik'] ?? '',
                'standar_uji' => $d_part['standar_uji'] ?? '',
                'traceability' => $d_part['traceability'] ?? '',
                'suhu' => $_POST['suhu'] ?? '',
                'kelembapan' => $_POST['kelembapan'] ?? '',
                'tanggal_periksa' => $tgl_pengecekan,
                'petugas' => $_SESSION['nama_lengkap'] ?? $_SESSION['userid'] ?? '',
                'foreman' => trim($_POST['foreman'] ?? ''),
                'supervisor' => trim($_POST['supervisor'] ?? ''),
                'jadwal_berikutnya' => $jadwal_baru,
                'keputusan' => $status_qa,
                'halaman' => '1 / 1',
                'hasil_array' => $pRows,
                'keterangan' => $_POST['keterangan'] ?? ''
            ];
            $hasil_json = mysqli_real_escape_string($konek, json_encode($reportP, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $reportQ = null;
        if ($isFormQ) {
            $reportNoQ = trim($_POST['no_laporan'] ?? '');
            if ($reportNoQ === '') json_response('error', 'No. Laporan Form Q wajib diisi.');
            $reportQ = [
                'format' => 'Q',
                'no_laporan' => $reportNoQ,
                'no_part' => $d_part['no_part'] ?? '',
                'nama_alat' => $d_part['nama_part'] ?? '',
                'spesifikasi' => $d_part['spesifikasi'] ?? $d_part['ukuran'] ?? '',
                'metode' => $d_part['metode_uji'] ?? '',
                'seksi' => $d_part['seksi_pemilik'] ?? '',
                'standar_uji' => $d_part['standar_uji'] ?? '',
                'traceability' => $d_part['traceability'] ?? '',
                'suhu' => $_POST['suhu'] ?? '',
                'kelembapan' => $_POST['kelembapan'] ?? '',
                'tanggal_periksa' => $tgl_pengecekan,
                'petugas' => $_SESSION['nama_lengkap'] ?? $_SESSION['userid'] ?? '',
                'foreman' => trim($_POST['foreman'] ?? ''),
                'supervisor' => trim($_POST['supervisor'] ?? ''),
                'jadwal_berikutnya' => $jadwal_baru,
                'keputusan' => $status_qa,
                'halaman' => '1 / 1',
                'hasil_array' => $qRows,
                'keterangan' => $_POST['keterangan'] ?? ''
            ];
            $hasil_json = mysqli_real_escape_string($konek, json_encode($reportQ, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $nomor_pengecekan = "QA-" . date('YmdHis') . "-" . $alat_id;

        /*
         * Struktur INSERT tetap sama seperti sistem sekarang.
         * Form A tersimpan lengkap melalui hasil_json.
         */
        $sql_hist = "INSERT INTO qa_pengecekan
                     (alat_id, no_part, nomor_pengecekan, tanggal_pengecekan,
                      tanggal_jadwal_saat_pengecekan, status_kalibrasi, seksi_qa,
                      periode_berikutnya, created_by, keterangan, created_at,
                      suhu, kelembapan, ketidakpastian, confidence_level, hasil_json)
                     VALUES
                     ('$alat_id', '$no_part', '$nomor_pengecekan', '$tgl_pengecekan',
                      '$tgl_jadwal_lama', '$status_qa', '$seksi_qa',
                      '$jadwal_baru', '$created_by', '$keterangan', '$waktu_sekarang',
                      '$suhu', '$kelembapan', '$ketidakpastian', '$confidence',
                      '$hasil_json')";

        $insert_hist = mysqli_query($konek, $sql_hist);

        if ($insert_hist) {
            mysqli_query(
                $konek,
                "UPDATE qa_part
                 SET jadwal_kalibrasi = '$jadwal_baru', updated_at = NOW()
                 WHERE id = '$alat_id'"
            );

            echo json_encode([
                'status' => 'success',
                'jadwal_selanjutnya' => $jadwal_baru,
                'no_pengecekan' => $nomor_pengecekan,
                'laporan_b' => $reportB,
                'laporan_c' => $reportC,
                'laporan_d' => $reportD,
                'laporan_h' => $reportH,
                'laporan_i' => $reportI,
                'laporan_j' => $reportJ,
                'laporan_k' => $reportK,
                'laporan_l' => $reportL,
                'laporan_m' => $reportM,
                'laporan_o' => $reportO,
                'laporan_p' => $reportP,
                'laporan_q' => $reportQ,
                'tanggal_periksa' => $tgl_pengecekan
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => mysqli_error($konek)
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

if ($action === 'complete_form_b') {
    $number=trim($_POST['no_part'] ?? '');
    $q=mysqli_prepare($konek,"SELECT id, format_laporan, titik_standar_json FROM qa_part WHERE no_part=? ORDER BY id DESC LIMIT 1");
    mysqli_stmt_bind_param($q,'s',$number); mysqli_stmt_execute($q);
    $part=mysqli_fetch_assoc(mysqli_stmt_get_result($q));
    if (!$part || !in_array(strtoupper(trim($part['format_laporan'] ?? '')),['B','FORM B'],true)) json_response('error','Alat Form B tidak ditemukan.');
    $old=qa_b_decode($part['titik_standar_json']); $incoming=qa_b_decode($_POST['standards'] ?? '');
    if (!$old || count($old)!==count($incoming)) json_response('error','Jumlah titik standar tidak sesuai.');
    foreach ($old as $i=>&$row) {
        foreach (['std1','std2','std3'] as $key) {
            // Hanya melengkapi kolom yang belum ada, tidak mengganti standar tersimpan.
            if (!isset($row[$key]) || trim((string)$row[$key])==='') $row[$key]=$incoming[$i][$key] ?? '';
        }
    }
    unset($row);
    [$ok,$message,$clean]=qa_b_standards($old);
    if (!$ok) json_response('error',$message);
    if (isset($old[0]['_meta'])) $clean[0]['_meta']=$old[0]['_meta'];
    $json=json_encode($clean,JSON_UNESCAPED_UNICODE); $id=(int)$part['id']; $previous=$part['titik_standar_json'];
    $q=mysqli_prepare($konek,"UPDATE qa_part SET titik_standar_json=? WHERE id=? AND titik_standar_json=?");
    mysqli_stmt_bind_param($q,'sis',$json,$id,$previous);
    if (!mysqli_stmt_execute($q)) json_response('error','Standar gagal disimpan.');
    if (mysqli_stmt_affected_rows($q)!==1) json_response('error','Data sudah berubah. Cari ulang alat untuk memuat standar terbaru.');
    json_response('success','Standar tersimpan.', ['standards'=>$clean]);
}

if ($action === 'laporan_b') {
    $number = mysqli_real_escape_string($konek, trim($_GET['no_part'] ?? ''));
    $q = mysqli_query($konek, "SELECT id, hasil_json FROM qa_pengecekan WHERE no_part='$number' ORDER BY id DESC");
    $reports=[];
    while ($q && ($row=mysqli_fetch_assoc($q))) {
        $report=qa_b_decode($row['hasil_json'] ?? '');
        if (($report['format'] ?? '') === 'B') $reports[]=['id'=>$row['id'], 'laporan'=>$report];
    }
    json_response('success', '', ['reports'=>$reports]);
}

/* ============================================================
 * MASTER KELAS & SEKSI
 * ============================================================ */
elseif ($action == 'simpan_master_kls') {
    try {
        $nama_kelas = mysqli_real_escape_string($konek, trim($_POST['nama_kelas']));
        $cek = mysqli_query($konek, "SELECT * FROM qa_kls WHERE nama_kelas = '$nama_kelas'");
        if (mysqli_num_rows($cek) > 0) { 
            echo json_encode(['status' => 'error', 'message' => 'Nama Kelas ini sudah ada di Database!']); 
        } else { 
            if(mysqli_query($konek, "INSERT INTO qa_kls (nama_kelas) VALUES ('$nama_kelas')")) {
                echo json_encode(['status' => 'success']); 
            } else { echo json_encode(['status' => 'error', 'message' => 'Gagal Insert: ' . mysqli_error($konek)]); }
        }
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}
elseif ($action == 'simpan_master_seksi') {
    try {
        $nama_seksi = mysqli_real_escape_string($konek, trim($_POST['nama_seksi']));
        $cek = mysqli_query($konek, "SELECT * FROM qa_seksi_master WHERE nama_seksi = '$nama_seksi'");
        if (mysqli_num_rows($cek) > 0) { 
            echo json_encode(['status' => 'error', 'message' => 'Seksi ini sudah ada di Database!']); 
        } else { 
            if(mysqli_query($konek, "INSERT INTO qa_seksi_master (nama_seksi) VALUES ('$nama_seksi')")) {
                echo json_encode(['status' => 'success']); 
            } else { echo json_encode(['status' => 'error', 'message' => 'Gagal Insert: ' . mysqli_error($konek)]); }
        }
    } catch (Exception $e) { echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); }
    exit;
}
elseif ($action == 'hapus_master_kls') {
    $nama = isset($_POST['nama_kelas']) ? mysqli_real_escape_string($konek, $_POST['nama_kelas']) : '';
    if(mysqli_query($konek, "DELETE FROM qa_kls WHERE nama_kelas = '$nama'")) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($konek)]);
    }
    exit;
}
elseif ($action == 'hapus_master_seksi') {
    $nama = isset($_POST['nama_seksi']) ? mysqli_real_escape_string($konek, $_POST['nama_seksi']) : '';
    if(mysqli_query($konek, "DELETE FROM qa_seksi_master WHERE nama_seksi = '$nama'")) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($konek)]);
    }
    exit;
} 
else {
    echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali.']);
    exit;
}
?>
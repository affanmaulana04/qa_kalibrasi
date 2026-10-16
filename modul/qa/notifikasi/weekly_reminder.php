<?php

/**
 * ============================================================
 * WEEKLY REMINDER QA
 * ============================================================
 *
 * Fungsi:
 * - Mengirim reminder jadwal kalibrasi mingguan
 * - Setiap anggota QA menerima daftar yang sama, satu email per alamat
 * - Jadwal: Senin s/d Jumat pada minggu berjalan
 * - Jadwal terlewat tetap ditampilkan jika belum dikalibrasi
 * Test email langsung:
 * php weekly_reminder.php --test --email=alamat@email.com
 *
 * Production:
 * Waktu pengiriman sepenuhnya diatur oleh Windows Task Scheduler.
 *
 * ============================================================
 */

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../../../inc/inc_koneksi.php';
require_once __DIR__ . '/email_helper.php';


// ============================================================
// MODE TEST
// ============================================================

$isTest = false;
$testEmail = '';

foreach ($argv ?? [] as $arg) {

    if ($arg === '--test') {
        $isTest = true;
    }

    if (strpos($arg, '--email=') === 0) {
        $testEmail = trim(
            substr($arg, strlen('--email='))
        );
    }

    if (strpos($arg, '--to=') === 0) {
        $testEmail = trim(
            substr($arg, strlen('--to='))
        );
    }
}


// ============================================================
// VALIDASI MODE TEST
// ============================================================

if ($isTest && $testEmail === '') {

    echo "Mode test wajib menggunakan email tujuan.\n";
    echo "Contoh: php weekly_reminder.php --test --email=nama@email.com\n";

    exit;
}


// ============================================================
// CEK DATABASE
// ============================================================

if (!$konek) {

    echo "Database gagal terhubung.\n";

    exit;
}


// ============================================================
// PERIODE WEEKDAYS: SENIN - JUMAT
// ============================================================

$mulai = date(
    'Y-m-d',
    strtotime('monday this week')
);

$selesai = date(
    'Y-m-d',
    strtotime('friday this week')
);


// ============================================================
// LOG
// ============================================================

$logFile = __DIR__ . '/weekly_reminder.log';

/**
 * @param mixed $message
 * @return void
 */
function qaWeeklyLog($message)
{
    global $logFile;

    file_put_contents(
        $logFile,
        '[' . date('Y-m-d H:i:s') . '] ' .
        $message .
        PHP_EOL,
        FILE_APPEND
    );
}


qaWeeklyLog(
    '============================================================'
);

qaWeeklyLog(
    'WEEKLY REMINDER START'
);

qaWeeklyLog(
    'Periode: ' .
    $mulai .
    ' s/d ' .
    $selesai
);


// ============================================================
// AMBIL PENERIMA EMAIL
// ============================================================

$sqlPenerima = "
    SELECT uid, uname, sapaan, email_pembuat
    FROM `user`
    WHERE UPPER(COALESCE(block, 'N')) <> 'Y'
      AND (
          UPPER(COALESCE(pembuat, 'N')) = 'Y'
          OR UPPER(COALESCE(pemeriksa, 'N')) = 'Y'
          OR UPPER(COALESCE(penyetuju, 'N')) = 'Y'
      )
      AND email_pembuat IS NOT NULL
      AND TRIM(email_pembuat) <> ''
    ORDER BY uname ASC, uid ASC
";

$resultPenerima = $konek->query($sqlPenerima);

if (!$resultPenerima) {
    qaWeeklyLog('ERROR QUERY PENERIMA: ' . $konek->error);
    echo "Gagal mengambil penerima email.\\n";
    exit;
}

$penerimaList = [];

while ($r = $resultPenerima->fetch_assoc()) {
    $email = strtolower(trim((string)$r['email_pembuat']));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        continue;
    }

    if (!isset($penerimaList[$email])) {
        $penerimaList[$email] = [
            'email' => $email,
            'user_uid' => trim((string)$r['uid']),
            'nama' => trim((string)$r['uname']),
            'sapaan' => trim((string)($r['sapaan'] ?? '')),
            'seksi_ids' => [],
            'seksi_pemilik_ids' => []
        ];
    }
}


// ============================================================
// FILTER EMAIL TEST
// ============================================================

if ($isTest && $testEmail !== '') {

    $testEmail = strtolower(
        trim($testEmail)
    );

    if (isset($penerimaList[$testEmail])) {

        $penerimaList = [
            $testEmail =>
            $penerimaList[$testEmail]
        ];

    } else {

        /*
         * Email test tidak terdaftar.
         * Dalam kondisi ini tetap izinkan test
         * dengan mengambil semua jadwal hanya jika --all-sections diberikan.
         */

        qaWeeklyLog(
            'EMAIL TEST TIDAK TERDAFTAR: ' .
            $testEmail .
            ' | pengiriman hanya ke email test'
        );

        $penerimaList = [

            $testEmail => [

                'email' => $testEmail,

                'user_uid' => '',

                'seksi_ids' => [],

                'seksi_pemilik_ids' => [],
                'nama' => '',
                'sapaan' => ''
            ]
        ];
    }
}


// ============================================================
// CEK PENERIMA
// ============================================================

if (empty($penerimaList)) {

    qaWeeklyLog(
        'TIDAK ADA PENERIMA EMAIL AKTIF'
    );

    echo "Tidak ada penerima email aktif.\n";

    exit;
}



// ============================================================
// BIND PARAMETER DINAMIS
// ============================================================

/**
 * @param mysqli_stmt $stmt
 * @param string $types
 * @param array<int,mixed> $params
 * @return void
 */
function qaBindDynamic(
    $stmt,
    $types,
    &$params
) {

    $bind = [];

    $bind[] = $types;

    foreach ($params as &$value) {

        $bind[] = &$value;
    }

    call_user_func_array(
        [$stmt, 'bind_param'],
        $bind
    );
}


// ============================================================
// LOOP PENERIMA
// ============================================================

foreach ($penerimaList as $penerima) {

    $email =
        $penerima['email'];

    $userUid =
        $penerima['user_uid'];

    $seksiIds =
        array_values(
            $penerima['seksi_ids']
        );

    $pemilikIds =
        array_values(
            $penerima['seksi_pemilik_ids']
        );


    // --------------------------------------------------------
    // LOG MAPPING
    // --------------------------------------------------------

    qaWeeklyLog(
        '------------------------------------------------------------'
    );

    qaWeeklyLog(
        'Memproses: ' .
        $email
    );

    qaWeeklyLog(
        'User UID: ' .
        ($userUid !== '' ? $userUid : '-')
    );

    qaWeeklyLog(
        'Seksi ID: ' .
        (
            !empty($seksiIds)
            ? implode(',', $seksiIds)
            : '-'
        )
    );

    qaWeeklyLog(
        'Seksi Pemilik ID: ' .
        (
            !empty($pemilikIds)
            ? implode(',', $pemilikIds)
            : '-'
        )
    );


    // ========================================================
    // NAMA + SAPAAN PENERIMA
    // ========================================================

    $sapaan = 'Bapak/Ibu';
    $uname = '';

    $uname = trim((string)($penerima['nama'] ?? ''));
    $sapaanUser = trim((string)($penerima['sapaan'] ?? ''));
    if ($sapaanUser !== '') {
        $sapaan = $sapaanUser;
    }


    // ========================================================
    // AMBIL NAMA DEPAN
    // ========================================================

    $namaDepan = $uname;

    if ($uname !== '') {

        $parts =
            preg_split(
                '/\s+/',
                trim($uname)
            );

        if (!empty($parts[0])) {

            $namaDepan =
                $parts[0];
        }
    }


    // ========================================================
    // BUAT NAMA PENERIMA
    // ========================================================

    if ($namaDepan !== '') {

        $namaPenerima =
            trim(
                $sapaan .
                ' ' .
                $namaDepan
            );

    } else {

        $namaPenerima =
            $sapaan;
    }


    // ========================================================
    // ARRAY HASIL
    // ========================================================

    $jadwalRows = [];
    $terlewatRows = [];


    $sectionCondition = '1=1';
    $params = [];
    $types = '';


    // ========================================================
    // QUERY JADWAL MINGGU INI
    // SENIN - JUMAT
    // ========================================================

    $sqlJadwal = "
        SELECT
            p.id AS alat_id,

            p.no_part AS nomor_alat,

            p.nama_part AS nama_alat,

            p.seksi_id,

            p.seksi_pemilik_id,

            COALESCE(
                sm.nama_seksi,
                NULLIF(TRIM(p.seksi_pemilik), ''),
                '-'
            ) AS kode_pemilik,

            p.jadwal_kalibrasi,

            DATEDIFF(
                p.jadwal_kalibrasi,
                CURDATE()
            ) AS selisih_hari

        FROM qa_part p

        LEFT JOIN qa_seksi_master sm
            ON UPPER(TRIM(sm.nama_seksi)) = UPPER(TRIM(p.seksi_pemilik))

        WHERE p.status = 'aktif'


          AND p.jadwal_kalibrasi IS NOT NULL

          AND p.jadwal_kalibrasi <> '0000-00-00'

          AND p.jadwal_kalibrasi
              BETWEEN DATE_SUB(
                  CURDATE(),
                  INTERVAL WEEKDAY(CURDATE()) DAY
              )
              AND DATE_ADD(
                  DATE_SUB(
                      CURDATE(),
                      INTERVAL WEEKDAY(CURDATE()) DAY
                  ),
                  INTERVAL 4 DAY
              )

          AND NOT EXISTS (
              SELECT 1
              FROM qa_pengecekan q
              WHERE q.alat_id = p.id
                AND q.tanggal_pengecekan >= p.jadwal_kalibrasi
                AND LOWER(COALESCE(q.status_validasi, '')) NOT IN
                    ('ditolak', 'rejected', 'menunggu_pemeriksa', 'menunggu_penyetuju')
          )

          AND $sectionCondition

        ORDER BY
            COALESCE(
                sm.nama_seksi,
                NULLIF(TRIM(p.seksi_pemilik), ''),
                '-'
            ) ASC,
            p.jadwal_kalibrasi ASC,
            p.no_part ASC
    ";


    qaWeeklyLog(
        'Query jadwal dibuat untuk ' .
        $email
    );


    $stmtJadwal =
        $konek->prepare(
            $sqlJadwal
        );


    if (!$stmtJadwal) {

        qaWeeklyLog('ERROR PREPARE JADWAL: ' . $konek->error);
        echo "ERROR query jadwal: " . $konek->error . "\\n";
        exit(1);

    } else {

        if (!empty($params)) {

            qaBindDynamic(
                $stmtJadwal,
                $types,
                $params
            );
        }


        if (!$stmtJadwal->execute()) {

            qaWeeklyLog('ERROR EXECUTE JADWAL: ' . $stmtJadwal->error);
            echo "ERROR execute jadwal: " . $stmtJadwal->error . "\\n";
            exit(1);

        } else {

            $resJadwal =
                $stmtJadwal->get_result();

            if ($resJadwal) {

                while (
                    $row =
                    $resJadwal->fetch_assoc()
                ) {

                    $jadwalRows[] = $row;
                }

            } else {

                qaWeeklyLog(
                    'ERROR GET RESULT JADWAL: ' .
                    $stmtJadwal->error
                );
            }
        }


        $stmtJadwal->close();
    }


    // ========================================================
    // QUERY OVERDUE
    // SEMUA JADWAL SEBELUM SENIN MINGGU INI
    // ========================================================

    $sqlOverdue = "
        SELECT
            p.id AS alat_id,

            p.no_part AS nomor_alat,

            p.nama_part AS nama_alat,

            p.seksi_id,

            p.seksi_pemilik_id,

            COALESCE(
                sm.nama_seksi,
                NULLIF(TRIM(p.seksi_pemilik), ''),
                '-'
            ) AS kode_pemilik,

            p.jadwal_kalibrasi,

            DATEDIFF(
                CURDATE(),
                p.jadwal_kalibrasi
            ) AS lama_hari,

            DATEDIFF(
                p.jadwal_kalibrasi,
                CURDATE()
            ) AS selisih_hari

        FROM qa_part p

        LEFT JOIN qa_seksi_master sm
            ON UPPER(TRIM(sm.nama_seksi)) = UPPER(TRIM(p.seksi_pemilik))

        WHERE p.status = 'aktif'


          AND p.jadwal_kalibrasi IS NOT NULL

          AND p.jadwal_kalibrasi <> '0000-00-00'

          AND p.jadwal_kalibrasi < DATE_SUB(
              CURDATE(),
              INTERVAL WEEKDAY(CURDATE()) DAY
          )

          AND NOT EXISTS (
              SELECT 1
              FROM qa_pengecekan q
              WHERE q.alat_id = p.id
                AND q.tanggal_pengecekan >= p.jadwal_kalibrasi
                AND LOWER(COALESCE(q.status_validasi, '')) NOT IN
                    ('ditolak', 'rejected', 'menunggu_pemeriksa', 'menunggu_penyetuju')
          )

          AND $sectionCondition

        ORDER BY
            COALESCE(
                sm.nama_seksi,
                NULLIF(TRIM(p.seksi_pemilik), ''),
                '-'
            ) ASC,
            p.jadwal_kalibrasi ASC,
            p.no_part ASC
    ";


    $stmtOverdue =
        $konek->prepare(
            $sqlOverdue
        );


    if (!$stmtOverdue) {

        qaWeeklyLog('ERROR PREPARE OVERDUE: ' . $konek->error);
        echo "ERROR query overdue: " . $konek->error . "\\n";
        exit(1);

    } else {

        if (!empty($params)) {

            /*
             * Parameter dibuat ulang karena
             * bind_param menggunakan reference.
             */

            $paramsOverdue = [];
            $typesOverdue = '';


            foreach ($seksiIds as $id) {

                $paramsOverdue[] = (int)$id;
                $typesOverdue .= 'i';
            }


            qaBindDynamic(
                $stmtOverdue,
                $typesOverdue,
                $paramsOverdue
            );
        }


        if (!$stmtOverdue->execute()) {

            qaWeeklyLog('ERROR EXECUTE OVERDUE: ' . $stmtOverdue->error);
            echo "ERROR execute overdue: " . $stmtOverdue->error . "\\n";
            exit(1);

        } else {

            $resOverdue =
                $stmtOverdue->get_result();

            if ($resOverdue) {

                while (
                    $row =
                    $resOverdue->fetch_assoc()
                ) {

                    $terlewatRows[] = $row;
                }

            } else {

                qaWeeklyLog(
                    'ERROR GET RESULT OVERDUE: ' .
                    $stmtOverdue->error
                );
            }
        }


        $stmtOverdue->close();
    }


    // ========================================================
    // LOG JUMLAH DATA
    // ========================================================

    qaWeeklyLog(
        'Jadwal ditemukan: ' .
        count($jadwalRows)
    );

    qaWeeklyLog(
        'Jadwal terlewat: ' .
        count($terlewatRows)
    );


    // ========================================================
    // LOG DETAIL JADWAL
    // ========================================================

    foreach ($jadwalRows as $row) {

        qaWeeklyLog(
            'JADWAL: ' .
            $row['nomor_alat'] .
            ' | ' .
            $row['nama_alat'] .
            ' | seksi_id=' .
            ($row['seksi_id'] ?? '-') .
            ' | pemilik_id=' .
            ($row['seksi_pemilik_id'] ?? '-') .
            ' | tanggal=' .
            $row['jadwal_kalibrasi']
        );
    }


    // ========================================================
    // LOG DETAIL OVERDUE
    // ========================================================

    foreach ($terlewatRows as $row) {

        qaWeeklyLog(
            'OVERDUE: ' .
            $row['nomor_alat'] .
            ' | ' .
            $row['nama_alat'] .
            ' | tanggal=' .
            $row['jadwal_kalibrasi'] .
            ' | terlambat=' .
            $row['lama_hari'] .
            ' hari'
        );
    }


    // ========================================================
    // TIDAK ADA DATA
    // ========================================================

    if (
        empty($jadwalRows)
        &&
        empty($terlewatRows)
    ) {

        qaWeeklyLog(
            'TIDAK ADA JADWAL UNTUK ' .
            $email
        );

        continue;
    }


    // ========================================================
    // SUBJECT
    // ========================================================

    $subjek =
        'Pengingat Jadwal Kalibrasi Alat QA – ' .
        date('d.m.Y');


    // ========================================================
    // INTRO
    // ========================================================

    $intro =
        'Dengan hormat, berikut kami sampaikan informasi jadwal kalibrasi alat '
        . 'untuk menjadi perhatian dan tindak lanjut.';


    // ========================================================
    // SNAPSHOT EMAIL
    // ========================================================

    $seksiSnapshotItems = [];

    foreach (
        array_merge(
            $jadwalRows,
            $terlewatRows
        ) as $snapshotRow
    ) {

        $owner = trim(
            (string)(
                $snapshotRow['kode_pemilik']
                ?? '-'
            )
        );

        if ($owner === '') {
            $owner = '-';
        }

        $ownerKey =
            strtoupper($owner);

        if (!isset($seksiSnapshotItems[$ownerKey])) {

            $seksiSnapshotItems[$ownerKey] =
                $owner;
        }
    }


    $seksiSnapshot =
        !empty($seksiSnapshotItems)
        ? implode(
            ', ',
            array_values(
                $seksiSnapshotItems
            )
        )
        : '-';


    // ========================================================
    // PRINT TOKEN
    // ========================================================

    try {

        $printToken =
            bin2hex(
                random_bytes(32)
            );

    } catch (Throwable $e) {

        $printToken =
            hash(
                'sha256',
                $email .
                '|' .
                microtime(true) .
                '|' .
                uniqid('', true)
            );
    }


    // ========================================================
    // DATA SNAPSHOT
    // ========================================================

    $snapshotData = [

        'version' =>
            1,

        'email_date' =>
            date('Y-m-d'),

        'periode_mulai' =>
            $mulai,

        'periode_selesai' =>
            $selesai,

        'user_uid' =>
            $userUid,

        'email' =>
            $email,

        'nama_penerima' =>
            $namaPenerima,

        'seksi' =>
            array_values(
                $seksiSnapshotItems
            ),

        'jadwal' =>
            $jadwalRows,

        'terlewat' =>
            $terlewatRows
    ];


    $snapshotJson =
        json_encode(
            $snapshotData,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );


    if ($snapshotJson === false) {

        qaWeeklyLog(
            'GAGAL MEMBUAT SNAPSHOT EMAIL UNTUK ' .
            $email .
            ' | ' .
            json_last_error_msg()
        );

        continue;
    }


    // ========================================================
    // SIMPAN SNAPSHOT SEBELUM KIRIM
    // ========================================================

    $cekLog =
        $konek->query(
            "SHOW TABLES LIKE 'qa_email_log'"
        );


    if (
        !$cekLog
        ||
        $cekLog->num_rows <= 0
    ) {

        qaWeeklyLog(
            'TABEL qa_email_log TIDAK DITEMUKAN. ' .
            'EMAIL DIBATALKAN UNTUK ' .
            $email
        );

        continue;
    }


    $jumlahJadwal =
        count($jadwalRows);

    $jumlahTerlewat =
        count($terlewatRows);


    $keteranganAwal =
        'Jadwal: ' .
        $jumlahJadwal .
        ', Terlewat: ' .
        $jumlahTerlewat .
        '. Menunggu proses pengiriman email.';


    if ($isTest) {

        $keteranganAwal =
            '[TEST] ' .
            $keteranganAwal;
    }


    // ========================================================
    // INSERT LOG
    // ========================================================

    $stmtLog =
        $konek->prepare("
            INSERT INTO qa_email_log
            (
                user_uid,
                email,
                seksi_snapshot,
                snapshot_json,
                print_token,
                jenis_email,
                periode_mulai,
                periode_selesai,
                tanggal_kirim,
                status,
                keterangan
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                'weekly',
                ?,
                ?,
                NOW(),
                'failed',
                ?
            )
        ");


    if (!$stmtLog) {

        qaWeeklyLog(
            'GAGAL PREPARE SNAPSHOT QA_EMAIL_LOG: ' .
            $konek->error
        );

        continue;
    }


    $stmtLog->bind_param(
        'ssssssss',
        $userUid,
        $email,
        $seksiSnapshot,
        $snapshotJson,
        $printToken,
        $mulai,
        $selesai,
        $keteranganAwal
    );


    if (!$stmtLog->execute()) {

        qaWeeklyLog(
            'GAGAL SIMPAN SNAPSHOT QA_EMAIL_LOG: ' .
            $stmtLog->error
        );

        $stmtLog->close();

        continue;
    }


    $emailLogId =
        (int)$stmtLog->insert_id;

    $stmtLog->close();


    // ========================================================
    // BUAT EMAIL
    // ========================================================

    $isiHtml =
        qaTemplateEmail(
            $namaPenerima,
            $subjek,
            $intro,
            $jadwalRows,
            $terlewatRows,
            $printToken,
            $mulai,
            $selesai
        );


    // ========================================================
    // KIRIM EMAIL
    // ========================================================

    qaWeeklyLog(
        'Mencoba mengirim email ke: ' .
        $email
    );


    $hasil =
        qaKirimEmail(
            $email,
            $namaPenerima,
            $subjek,
            $isiHtml
        );


    if ($hasil['success']) {

        qaWeeklyLog(
            'EMAIL BERHASIL: ' .
            $email
        );

    } else {

        qaWeeklyLog(
            'EMAIL GAGAL: ' .
            $email .
            ' | ' .
            (
                $hasil['message']
                ?? 'Unknown error'
            )
        );
    }


    // ========================================================
    // UPDATE STATUS LOG EMAIL
    // ========================================================

    $statusLog =
        $hasil['success']
        ? 'sent'
        : 'failed';


    $keteranganLog =
        'Jadwal: ' .
        $jumlahJadwal .
        ', Terlewat: ' .
        $jumlahTerlewat .
        '. ' .
        (
            $hasil['message']
            ?? ''
        );


    if ($isTest) {

        $keteranganLog =
            '[TEST] ' .
            $keteranganLog;
    }


    $stmtUpdateLog =
        $konek->prepare("
            UPDATE qa_email_log
            SET
                tanggal_kirim = NOW(),
                status = ?,
                keterangan = ?
            WHERE id = ?
            LIMIT 1
        ");


    if ($stmtUpdateLog) {

        $stmtUpdateLog->bind_param(
            'ssi',
            $statusLog,
            $keteranganLog,
            $emailLogId
        );


        if (!$stmtUpdateLog->execute()) {

            qaWeeklyLog(
                'GAGAL UPDATE QA_EMAIL_LOG: ' .
                $stmtUpdateLog->error
            );
        }


        $stmtUpdateLog->close();

    } else {

        qaWeeklyLog(
            'GAGAL PREPARE UPDATE QA_EMAIL_LOG: ' .
            $konek->error
        );
    }
}


// ============================================================
// SELESAI
// ============================================================

qaWeeklyLog(
    'WEEKLY REMINDER FINISH'
);

qaWeeklyLog(
    '============================================================'
);

echo "Weekly reminder selesai.\n";
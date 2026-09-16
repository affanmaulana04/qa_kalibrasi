<?php

/**
 * ============================================================
 * WEEKLY REMINDER QA
 * ============================================================
 *
 * Fungsi:
 * - Mengirim reminder jadwal kalibrasi mingguan
 * - 1 email bisa memiliki banyak seksi
 * - Jadwal: hari ini s/d 6 hari ke depan
 * - Jadwal terlewat tetap ditampilkan
 *
 * Test email langsung:
 * php weekly_reminder.php --test
 * php weekly_reminder.php --test --email=ridhoahmadfauzan38@gmail.com
 *
 * Production:
 * Waktu pengiriman sepenuhnya diatur oleh Windows Task Scheduler.
 *
 * ============================================================
 */

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../../inc/inc_koneksi.php';
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
// CEK DATABASE
// ============================================================

if (!$konek) {

    echo "Database gagal terhubung.\n";

    exit;
}


// ============================================================
// TANGGAL
// ============================================================

$mulai = date('Y-m-d');

$selesai = date(
    'Y-m-d',
    strtotime('+6 days')
);


// ============================================================
// LOG
// ============================================================

$logFile = __DIR__ . '/weekly_reminder.log';

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
    SELECT
        id,
        seksi_id,
        seksi_pemilik_id,
        user_uid,
        email,
        status
    FROM qa_penerima_email
    WHERE status = 'aktif'
      AND email IS NOT NULL
      AND TRIM(email) <> ''
    ORDER BY
        email ASC,
        id ASC
";

$resultPenerima = $konek->query(
    $sqlPenerima
);


if (!$resultPenerima) {

    qaWeeklyLog(
        'ERROR QUERY PENERIMA: ' .
        $konek->error
    );

    echo "Gagal mengambil penerima email.\n";

    exit;
}


// ============================================================
// GROUP BERDASARKAN EMAIL
// ============================================================

$penerimaList = [];

while ($r = $resultPenerima->fetch_assoc()) {

    $email = strtolower(
        trim($r['email'])
    );

    if ($email === '') {
        continue;
    }


    if (!isset($penerimaList[$email])) {

        $penerimaList[$email] = [
            'email' => $email,
            'user_uid' => '',
            'seksi_ids' => [],
            'seksi_pemilik_ids' => []
        ];
    }


    // --------------------------------------------------------
    // USER UID
    // --------------------------------------------------------

    if (
        empty($penerimaList[$email]['user_uid'])
        &&
        !empty($r['user_uid'])
    ) {

        $penerimaList[$email]['user_uid'] =
            trim($r['user_uid']);
    }


    // --------------------------------------------------------
    // SEKSI ID
    // --------------------------------------------------------

    if (
        isset($r['seksi_id'])
        &&
        $r['seksi_id'] !== null
        &&
        $r['seksi_id'] !== ''
    ) {

        $seksiId = (int) $r['seksi_id'];

        if ($seksiId > 0) {

            $penerimaList[$email]['seksi_ids'][$seksiId] =
                $seksiId;
        }
    }


    // --------------------------------------------------------
    // SEKSI PEMILIK ID
    // --------------------------------------------------------

    if (
        isset($r['seksi_pemilik_id'])
        &&
        $r['seksi_pemilik_id'] !== null
        &&
        $r['seksi_pemilik_id'] !== ''
    ) {

        $pemilikId =
            (int) $r['seksi_pemilik_id'];

        if ($pemilikId > 0) {

            $penerimaList[$email]['seksi_pemilik_ids'][$pemilikId] =
                $pemilikId;
        }
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
         *
         * Dalam kondisi ini kita tetap izinkan test
         * dengan mengambil semua jadwal.
         */

        qaWeeklyLog(
            'EMAIL TEST TIDAK TERDAFTAR: ' .
            $testEmail .
            ' | mode semua jadwal'
        );

        $penerimaList = [

            $testEmail => [

                'email' => $testEmail,

                'user_uid' => '',

                'seksi_ids' => [],

                'seksi_pemilik_ids' => []
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
// PLACEHOLDER
// ============================================================

function qaMakePlaceholders($count)
{
    if ($count <= 0) {
        return '';
    }

    return implode(
        ',',
        array_fill(
            0,
            $count,
            '?'
        )
    );
}


// ============================================================
// BIND PARAMETER DINAMIS
// ============================================================

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
    // NAMA + SAPAAN DARI QA_USER
    // ========================================================

    $sapaan = 'Bpk.';
    $uname = '';

    if ($userUid !== '') {

        $stmtUser = $konek->prepare("
            SELECT
                nama,
                jenis_kelamin
            FROM qa_user
            WHERE uid = ?
              AND status = 'aktif'
            LIMIT 1
        ");

        if ($stmtUser) {

            $stmtUser->bind_param(
                's',
                $userUid
            );

            if ($stmtUser->execute()) {

                $resUser =
                    $stmtUser->get_result();

                if ($resUser) {

                    $userData =
                        $resUser->fetch_assoc();

                    if ($userData) {

                        $uname =
                            trim(
                                $userData['nama'] ?? ''
                            );

                        $jenisKelamin =
                            strtoupper(
                                trim(
                                    $userData['jenis_kelamin'] ?? ''
                                )
                            );

                        /*
                         * L = Bpk.
                         * P = Ibu
                         */

                        if ($jenisKelamin === 'P') {

                            $sapaan = 'Ibu';

                        } else {

                            $sapaan = 'Bpk.';
                        }
                    }
                }
            }

            $stmtUser->close();
        }
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


    // ========================================================
    // BUAT KONDISI SEKSI
    // ========================================================

    $conditions = [];

    $params = [];

    $types = '';


    // --------------------------------------------------------
    // SEKSI ID
    // --------------------------------------------------------

    if (!empty($seksiIds)) {

        $placeholders =
            qaMakePlaceholders(
                count($seksiIds)
            );

        $conditions[] =
            "p.seksi_id IN ($placeholders)";

        foreach ($seksiIds as $id) {

            $params[] = (int) $id;

            $types .= 'i';
        }
    }


    // --------------------------------------------------------
    // SEKSI PEMILIK ID
    // --------------------------------------------------------

    if (!empty($pemilikIds)) {

        $placeholders =
            qaMakePlaceholders(
                count($pemilikIds)
            );

        $conditions[] =
            "p.seksi_pemilik_id IN ($placeholders)";

        foreach ($pemilikIds as $id) {

            $params[] = (int) $id;

            $types .= 'i';
        }
    }


    // ========================================================
    // QUERY JADWAL
    // ========================================================

    if (!empty($conditions)) {

        $sectionCondition =
            '(' .
            implode(
                ' OR ',
                $conditions
            ) .
            ')';

    } else {

        /*
         * Email test tanpa mapping seksi.
         * Ambil semua jadwal.
         */

        $sectionCondition = '1=1';
    }


    $sqlJadwal = "
        SELECT
            p.id AS alat_id,

            p.no_part AS nomor_alat,

            p.nama_part AS nama_alat,

            p.seksi_id,

            p.seksi_pemilik_id,

            COALESCE(
                sp.kode_pemilik,
                s.nama_seksi,
                '-'
            ) AS kode_pemilik,

            p.jadwal_kalibrasi,

            DATEDIFF(
                p.jadwal_kalibrasi,
                CURDATE()
            ) AS selisih_hari

        FROM qa_part p

        LEFT JOIN qa_seksi s
            ON s.id = p.seksi_id

        LEFT JOIN qa_seksi_pemilik sp
            ON sp.id = p.seksi_pemilik_id

        WHERE p.status = 'aktif'

          AND p.jadwal_kalibrasi IS NOT NULL

          AND p.jadwal_kalibrasi <> '0000-00-00'

          AND p.jadwal_kalibrasi
              BETWEEN CURDATE()
              AND DATE_ADD(
                  CURDATE(),
                  INTERVAL 6 DAY
              )

          AND $sectionCondition

        ORDER BY
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

        qaWeeklyLog(
            'ERROR PREPARE JADWAL: ' .
            $konek->error
        );

    } else {

        if (!empty($params)) {

            qaBindDynamic(
                $stmtJadwal,
                $types,
                $params
            );
        }


        if (!$stmtJadwal->execute()) {

            qaWeeklyLog(
                'ERROR EXECUTE JADWAL: ' .
                $stmtJadwal->error
            );

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
    // ========================================================

    $sqlOverdue = "
        SELECT
            p.id AS alat_id,

            p.no_part AS nomor_alat,

            p.nama_part AS nama_alat,

            p.seksi_id,

            p.seksi_pemilik_id,

            COALESCE(
                sp.kode_pemilik,
                s.nama_seksi,
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

        LEFT JOIN qa_seksi s
            ON s.id = p.seksi_id

        LEFT JOIN qa_seksi_pemilik sp
            ON sp.id = p.seksi_pemilik_id

        WHERE p.status = 'aktif'

          AND p.jadwal_kalibrasi IS NOT NULL

          AND p.jadwal_kalibrasi <> '0000-00-00'

          AND p.jadwal_kalibrasi < CURDATE()

          AND $sectionCondition

        ORDER BY
            p.jadwal_kalibrasi ASC,
            p.no_part ASC
    ";


    $stmtOverdue =
        $konek->prepare(
            $sqlOverdue
        );


    if (!$stmtOverdue) {

        qaWeeklyLog(
            'ERROR PREPARE OVERDUE: ' .
            $konek->error
        );

    } else {

        if (!empty($params)) {

            /*
             * Parameter harus dibuat ulang karena
             * bind_param menggunakan reference.
             */

            $paramsOverdue = [];

            $typesOverdue = '';


            foreach ($seksiIds as $id) {

                $paramsOverdue[] = (int) $id;

                $typesOverdue .= 'i';
            }


            foreach ($pemilikIds as $id) {

                $paramsOverdue[] = (int) $id;

                $typesOverdue .= 'i';
            }


            qaBindDynamic(
                $stmtOverdue,
                $typesOverdue,
                $paramsOverdue
            );
        }


        if (!$stmtOverdue->execute()) {

            qaWeeklyLog(
                'ERROR EXECUTE OVERDUE: ' .
                $stmtOverdue->error
            );

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
        'Berikut daftar jadwal kalibrasi alat QA '
        .
        'yang perlu diperhatikan selama satu minggu '
        .
        'ke depan serta jadwal kalibrasi yang telah terlewat.';


    // ========================================================
    // BUAT EMAIL
    // ========================================================

    $isiHtml =
        qaTemplateEmail(
            $namaPenerima,
            $subjek,
            $intro,
            $jadwalRows,
            $terlewatRows
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
            ($hasil['message'] ?? 'Unknown error')
        );
    }


    // ========================================================
    // LOG EMAIL
    // ========================================================

    $cekLog =
        $konek->query(
            "SHOW TABLES LIKE 'qa_email_log'"
        );


    if (
        $cekLog
        &&
        $cekLog->num_rows > 0
    ) {

        $jumlahJadwal =
            count($jadwalRows);

        $jumlahTerlewat =
            count($terlewatRows);

        $statusLog =
            $hasil['success']
            ? 'berhasil'
            : 'gagal';

        $pesanLog =
            'Jadwal: ' .
            $jumlahJadwal .
            ', Terlewat: ' .
            $jumlahTerlewat .
            '. ' .
            ($hasil['message'] ?? '');


        $stmtLog =
            $konek->prepare("
                INSERT INTO qa_email_log
                (
                    email,
                    jenis_email,
                    tanggal_kirim,
                    status,
                    pesan
                )
                VALUES
                (
                    ?,
                    'weekly_reminder',
                    NOW(),
                    ?,
                    ?
                )
            ");


        if ($stmtLog) {

            $stmtLog->bind_param(
                'sss',
                $email,
                $statusLog,
                $pesanLog
            );

            $stmtLog->execute();

            $stmtLog->close();
        }
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
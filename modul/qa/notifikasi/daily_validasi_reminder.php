<?php



/**

 * ============================================================

 * DAILY VALIDASI REMINDER QA + MAGIC LOGIN

 * ============================================================

 *

 * Email dipisah berdasarkan tahap validasi:

 * - menunggu_pemeriksa  -> user.pemeriksa = 'Y'

 * - menunggu_penyetuju  -> user.penyetuju = 'Y'

 *

 * Setiap email mendapatkan magic-login token milik user penerima.

 * Tombol "Buka Transaksi Validasi":

 *   email -> validasi_login.php?token=... -> auto login user penerima

 *   -> transaksi_kalibrasi.php

 *

 * Production:

 *   Windows Task Scheduler, setiap hari pukul 15:00.

 *

 * Test:

 *   php daily_validasi_reminder.php --test --email=alamat@email.com

 *

 * Catatan:

 * - Jalankan 03_validasi_login_token.sql terlebih dahulu.

 * - validasi_login.php harus sudah ada di modul/qa.

 * - CC1/CC2 disiapkan melalui qa_email_validasi_config.

 * - Jika CC diisi nanti, CC menerima salinan INFORMASI terpisah tanpa magic-login token.

 * ============================================================

 */



date_default_timezone_set('Asia/Jakarta');



require_once __DIR__ . '/../../../inc/inc_koneksi.php';

require_once __DIR__ . '/email_helper.php';

require_once __DIR__ . '/email_validasi_pemeriksa.php';

require_once __DIR__ . '/email_validasi_penyetuju.php';

require_once __DIR__ . '/validasi_email_log.php';



/** @var mysqli $konek */

if (!isset($konek) || !($konek instanceof mysqli)) {

    exit("Database gagal terhubung.\n");

}



$isTest = false;

$testEmail = '';



foreach ($argv ?? [] as $arg) {

    if ($arg === '--test') {

        $isTest = true;

        continue;

    }



    if (strpos($arg, '--email=') === 0) {

        $testEmail = strtolower(trim(substr($arg, strlen('--email='))));

        continue;

    }



    if (strpos($arg, '--to=') === 0) {

        $testEmail = strtolower(trim(substr($arg, strlen('--to='))));

    }

}



if (PHP_SAPI !== 'cli') exit("Hanya dapat dijalankan melalui CLI.\n");
if (!$isTest && $testEmail !== '') exit("--email hanya boleh bersama --test.\n");

if ($isTest && $testEmail === '') {

    exit(

        "Mode test wajib menggunakan email tujuan.\n"

        . "Contoh: php daily_validasi_reminder.php --test --email=nama@email.com\n"

    );

}



$logFile = __DIR__ . '/daily_validasi_reminder.log';



function qaDailyValidasiLog($message)

{

    global $logFile;



    file_put_contents(

        $logFile,

        '[' . date('Y-m-d H:i:s') . '] ' . (string)$message . PHP_EOL,

        FILE_APPEND

    );

}



/**

 * @param mysqli $konek

 * @param string $table

 * @return bool

 */

function qaValidasiTableExists($konek, $table)

{

    $table = preg_replace('/[^A-Za-z0-9_]/', '', (string)$table);



    if ($table === '') {

        return false;

    }



    $safe = $konek->real_escape_string($table);

    $res = $konek->query("SHOW TABLES LIKE '{$safe}'");



    return $res && $res->num_rows > 0;

}



/**

 * Mengambil transaksi pending sesuai status validasi.

 *

 * @param mysqli $konek

 * @param string $statusValidasi

 * @return array<int,array<string,mixed>>

 */

function qaAmbilPendingValidasi($konek, $statusValidasi)

{

    $statusValidasi = trim((string)$statusValidasi);



    if (!in_array($statusValidasi, ['menunggu_pemeriksa', 'menunggu_penyetuju'], true)) {

        return [];

    }



    /*

     * Pending validasi belum difinalisasi, sehingga alat masih berada

     * di qa_part. Karena itu listing reminder tidak perlu JOIN ke semua

     * tabel arsip. Ini juga mencegah query reminder gagal diam-diam

     * apabila struktur tabel arsip berbeda.

     */

    $stmt = $konek->prepare("

        SELECT

            q.id,

            q.nomor_pengecekan,

            q.no_part,

            q.tanggal_pengecekan,

            q.status_kalibrasi,

            q.created_by,

            COALESCE(p.nama_part, '-') AS nama_part,

            COALESCE(p.seksi_pemilik, '-') AS seksi_pemilik

        FROM qa_pengecekan q

        LEFT JOIN qa_part p

            ON p.id = q.alat_id

        WHERE TRIM(LOWER(COALESCE(q.status_validasi, ''))) = ?

          AND COALESCE(q.jenis_transaksi, 'update_hasil_kalibrasi') <> 'registrasi_alat'

          AND (q.nomor_pengecekan IS NULL OR q.nomor_pengecekan NOT LIKE 'QA-REG-%')

        ORDER BY q.tanggal_pengecekan ASC, q.id ASC

    ");



    if (!$stmt) {

        qaDailyValidasiLog('ERROR PREPARE PENDING ' . $statusValidasi . ' | ' . $konek->error);

        return [];

    }



    $statusParam = strtolower(trim($statusValidasi));

    $stmt->bind_param('s', $statusParam);



    if (!$stmt->execute()) {

        qaDailyValidasiLog('ERROR EXEC PENDING ' . $statusValidasi . ' | ' . $stmt->error);

        $stmt->close();

        return [];

    }



    $res = $stmt->get_result();

    $rows = [];



    while ($res && ($row = $res->fetch_assoc())) {

        $rows[] = $row;

    }



    $stmt->close();



    return $rows;

}



/**

 * Ambil user aktif berdasarkan flag pemeriksa/penyetuju.

 *

 * @param mysqli $konek

 * @param string $kolomRole

 * @param bool $isTest

 * @param string $testEmail

 * @return array<int,array<string,mixed>>

 */

function qaAmbilPenerimaValidasi($konek, $kolomRole, $isTest, $testEmail)

{

    if (!in_array($kolomRole, ['pemeriksa', 'penyetuju'], true)) {

        return [];

    }



    // Nama kolom sudah dibatasi whitelist di atas, bukan berasal dari input user.

    $jenis = $kolomRole === 'pemeriksa' ? 'PEMERIKSAAN' : 'PERSETUJUAN';

    $filterConfig = '';
    if (!$isTest) {
        if (!qaValidasiTableExists($konek, 'qa_email_validasi_penerima')) {
            qaDailyValidasiLog('SKIP ' . $jenis . ': tabel konfigurasi penerima belum ada.');
            return [];
        }
        // Konfigurasi halaman email_validasi.php adalah sumber tunggal.
        // Tidak fallback ke semua validator jika daftar TUJUAN kosong/nonaktif.
        $filterConfig = " AND EXISTS (SELECT 1 FROM qa_email_validasi_penerima c
            WHERE c.user_uid = u.uid
              AND c.jenis_validasi = '$jenis'
              AND c.tipe_penerima = 'TUJUAN'
              AND c.status = 'aktif')";
    }

    $sql = "

        SELECT

            u.uid,

            u.uname,

            u.sapaan,

            u.email_pembuat,

            u.{$kolomRole},

            u.block

        FROM `user` u

        WHERE UPPER(COALESCE(u.{$kolomRole}, 'N')) = 'Y'

          AND UPPER(COALESCE(u.block, 'N')) <> 'Y'

          AND u.email_pembuat IS NOT NULL

          AND TRIM(u.email_pembuat) <> ''

    ";



    $sql .= $filterConfig;



    if ($isTest) {

        $safeEmail = $konek->real_escape_string(

            strtolower(trim((string)$testEmail))

        );



        $sql .= "\n AND LOWER(TRIM(email_pembuat)) = LOWER(TRIM('{$safeEmail}'))";

    }



    $sql .= "\n ORDER BY uname ASC, uid ASC";



    $res = $konek->query($sql);



    if (!$res) {

        qaDailyValidasiLog(

            'ERROR USER ' . strtoupper($kolomRole) . ' | ' . $konek->error

        );

        return [];

    }



    $users = [];

    $seen = [];



    while ($row = $res->fetch_assoc()) {

        $uid = trim((string)($row['uid'] ?? ''));

        $email = strtolower(trim((string)($row['email_pembuat'] ?? '')));



        // Deduplikasi per UID. Email yang sama tetap boleh dimiliki UID berbeda,

        // karena magic-login token harus selalu mengarah ke user yang tepat.

        if ($uid === '' || $email === '' || isset($seen[$uid])) {

            continue;

        }



        $seen[$uid] = true;

        $users[] = $row;

    }



    return $users;

}



/**

 * Membuat magic-login token untuk satu penerima.

 *

 * Token asli hanya dikirim melalui URL email.

 * Database hanya menyimpan SHA-256 hash token.

 *

 * @param mysqli $konek

 * @param string $userUid

 * @param string $email

 * @param string $jenisValidasi pemeriksaan|persetujuan

 * @param string $baseUrl

 * @return array{success:bool,url:string,token_id:int,message:string}

 */

function qaBuatMagicLoginValidasi(

    $konek,

    $userUid,

    $email,

    $jenisValidasi,

    $baseUrl

) {

    $userUid = trim((string)$userUid);

    $email = strtolower(trim((string)$email));

    $jenisValidasi = strtolower(trim((string)$jenisValidasi));

    $baseUrl = trim((string)$baseUrl);



    if ($userUid === '' || $email === '') {

        return [

            'success' => false,

            'url' => '',

            'token_id' => 0,

            'message' => 'UID/email penerima token tidak valid.'

        ];

    }



    if (!in_array($jenisValidasi, ['pemeriksaan', 'persetujuan'], true)) {

        return [

            'success' => false,

            'url' => '',

            'token_id' => 0,

            'message' => 'Jenis validasi token tidak valid.'

        ];

    }



    if ($baseUrl === '') {

        return [

            'success' => false,

            'url' => '',

            'token_id' => 0,

            'message' => 'Base URL magic login kosong.'

        ];

    }



    try {

        $token = bin2hex(random_bytes(32));

    } catch (Throwable $e) {

        return [

            'success' => false,

            'url' => '',

            'token_id' => 0,

            'message' => 'Gagal membuat token acak: ' . $e->getMessage()

        ];

    }



    $tokenHash = hash('sha256', $token);



    // Berlaku 24 jam sejak email dibuat.

    $expiredAt = date('Y-m-d H:i:s', time() + 86400);



    $stmt = $konek->prepare("

        INSERT INTO qa_validasi_login_token

        (

            token_hash,

            user_uid,

            jenis_validasi,

            email_tujuan,

            expired_at,

            status

        )

        VALUES (?, ?, ?, ?, ?, 'aktif')

    ");



    if (!$stmt) {

        return [

            'success' => false,

            'url' => '',

            'token_id' => 0,

            'message' => $konek->error

        ];

    }



    $stmt->bind_param(

        'sssss',

        $tokenHash,

        $userUid,

        $jenisValidasi,

        $email,

        $expiredAt

    );



    if (!$stmt->execute()) {

        $error = $stmt->error;

        $stmt->close();



        return [

            'success' => false,

            'url' => '',

            'token_id' => 0,

            'message' => $error

        ];

    }



    $tokenId = (int)$stmt->insert_id;

    $stmt->close();



    $separator = strpos($baseUrl, '?') === false ? '?' : '&';

    $url = $baseUrl . $separator . 'token=' . rawurlencode($token);



    return [

        'success' => true,

        'url' => $url,

        'token_id' => $tokenId,

        'message' => 'Token berhasil dibuat.'

    ];

}



/**

 * Cabut token apabila email gagal dikirim.

 *

 * @param mysqli $konek

 * @param int $tokenId

 * @return void

 */

function qaCabutMagicLoginValidasi($konek, $tokenId)

{

    $tokenId = (int)$tokenId;



    if ($tokenId <= 0) {

        return;

    }



    $stmt = $konek->prepare("

        UPDATE qa_validasi_login_token

        SET status = 'dicabut'

        WHERE id = ?

        LIMIT 1

    ");



    if ($stmt) {

        $stmt->bind_param('i', $tokenId);

        $stmt->execute();

        $stmt->close();

    }

}





/**

 * Ambil konfigurasi CC berdasarkan jenis validasi.

 *

 * @param mysqli $konek

 * @param string $jenisValidasi PEMERIKSAAN|PERSETUJUAN

 * @return array<int,string> daftar UID CC

 */

function qaAmbilUidCcValidasi($konek, $jenisValidasi)

{

    $jenisValidasi = strtoupper(trim((string)$jenisValidasi));



    if (!in_array($jenisValidasi, ['PEMERIKSAAN', 'PERSETUJUAN'], true)) {

        return [];

    }



    // Halaman Email Validasi adalah sumber konfigurasi terbaru.

    // Jika tabel tersedia, daftar kosong berarti tidak ada CC.

    if (qaValidasiTableExists($konek, 'qa_email_validasi_penerima')) {

        $stmt = $konek->prepare("SELECT DISTINCT user_uid FROM qa_email_validasi_penerima

            WHERE jenis_validasi=? AND tipe_penerima='MENGETAHUI' AND status='aktif'");

        $stmt->bind_param('s', $jenisValidasi);

        $stmt->execute();

        $res = $stmt->get_result();

        $uids = [];

        while ($row = $res->fetch_assoc()) {

            $uid = trim((string)$row['user_uid']);

            if ($uid !== '') $uids[] = $uid;

        }

        $stmt->close();

        return $uids;

    }



    if (!qaValidasiTableExists($konek, 'qa_email_validasi_config')) {

        return [];

    }



    $stmt = $konek->prepare("

        SELECT cc1_user_uid, cc2_user_uid

        FROM qa_email_validasi_config

        WHERE jenis_validasi = ?

          AND status = 'aktif'

        LIMIT 1

    ");



    if (!$stmt) {

        qaDailyValidasiLog(

            'ERROR CONFIG CC ' . $jenisValidasi . ' | ' . $konek->error

        );

        return [];

    }



    $stmt->bind_param('s', $jenisValidasi);

    $stmt->execute();

    $res = $stmt->get_result();

    $row = $res ? $res->fetch_assoc() : null;

    $stmt->close();



    if (!$row) {

        return [];

    }



    $uids = [];



    foreach (['cc1_user_uid', 'cc2_user_uid'] as $key) {

        $uid = trim((string)($row[$key] ?? ''));



        if ($uid !== '') {

            $uids[$uid] = $uid;

        }

    }



    return array_values($uids);

}



/**

 * Ambil data user untuk penerima salinan CC.

 *

 * @param mysqli $konek

 * @param string $uid

 * @return array<string,mixed>|null

 */

function qaAmbilUserCcValidasi($konek, $uid)

{

    $uid = trim((string)$uid);



    if ($uid === '') {

        return null;

    }



    $stmt = $konek->prepare("

        SELECT

            uid,

            uname,

            sapaan,

            email_pembuat,

            block

        FROM `user`

        WHERE uid = ?

          AND UPPER(COALESCE(block, 'N')) <> 'Y'

          AND email_pembuat IS NOT NULL

          AND TRIM(email_pembuat) <> ''

        LIMIT 1

    ");



    if (!$stmt) {

        return null;

    }



    $stmt->bind_param('s', $uid);

    $stmt->execute();

    $res = $stmt->get_result();

    $row = $res ? $res->fetch_assoc() : null;

    $stmt->close();



    return $row ?: null;

}



/**

 * Kirim salinan informasi CC secara TERPISAH.

 *

 * Penting:

 * - Tidak menggunakan $mail->addCC() pada email utama.

 * - Tidak menyertakan magic-login URL milik penerima utama.

 * - Dengan begitu CC tidak bisa auto-login sebagai penerima utama.

 *

 * @param mysqli $konek

 * @param string $jenisValidasi PEMERIKSAAN|PERSETUJUAN

 * @param array<string,mixed> $penerimaUtama

 * @param array<int,array<string,mixed>> $rows

 * @param bool $isTest

 * @return int jumlah email CC berhasil dikirim

 */

function qaKirimSalinanCcValidasi(

    $konek,

    $jenisValidasi,

    $penerimaUtama,

    $rows,

    $isTest

) {

    $uids = qaAmbilUidCcValidasi($konek, $jenisValidasi);



    if (!$uids) {

        return 0;

    }



    $uidUtama = trim((string)($penerimaUtama['uid'] ?? ''));

    $namaUtama = trim((string)($penerimaUtama['uname'] ?? ''));

    $sent = 0;



    foreach ($uids as $uidCc) {

        if ($uidCc === $uidUtama) {

            continue;

        }



        $userCc = qaAmbilUserCcValidasi($konek, $uidCc);



        if (!$userCc) {

            qaDailyValidasiLog(

                'SKIP CC ' . $jenisValidasi

                . ' | UID ' . $uidCc

                . ' tidak aktif/tidak punya email.'

            );

            continue;

        }



        $emailCc = strtolower(trim((string)($userCc['email_pembuat'] ?? '')));



        if ($emailCc === '') {

            continue;

        }



        $subject = ($isTest ? '[TEST] ' : '')

            . '[CC] Reminder '

            . ($jenisValidasi === 'PEMERIKSAAN' ? 'Pemeriksaan' : 'Persetujuan')

            . ' QA - '

            . date('d.m.Y');



        if ($jenisValidasi === 'PEMERIKSAAN') {

            $htmlCc = qaTemplateEmailValidasiPemeriksa(

                $userCc,

                $rows,

                '',

                date('Y-m-d'),

                true,

                $namaUtama

            );

        } else {

            $htmlCc = qaTemplateEmailValidasiPenyetuju(

                $userCc,

                $rows,

                '',

                date('Y-m-d'),

                true,

                $namaUtama

            );

        }



        $hasilCc = qaKirimEmail(

            $emailCc,

            trim((string)($userCc['uname'] ?? '')),

            $subject,

            $htmlCc

        );



        qaSimpanLogEmailValidasi($konek, $uidCc, $emailCc, $jenisValidasi, count($rows), $hasilCc, $isTest, true);



        if (!empty($hasilCc['success'])) {

            $sent++;

            qaDailyValidasiLog(

                'BERHASIL CC ' . $jenisValidasi

                . ' | to=' . $emailCc

                . ' | untuk=' . $uidUtama

            );

        } else {

            qaDailyValidasiLog(

                'GAGAL CC ' . $jenisValidasi

                . ' | to=' . $emailCc

                . ' | ' . (string)($hasilCc['message'] ?? 'Unknown error')

            );

        }

    }



    return $sent;

}



// ============================================================

// ============================================================
// TEST AMAN: DATA ASLI, SATU EMAIL TEST, TANPA MAGIC LOGIN
// ============================================================
if ($isTest) {
    if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        exit("Alamat email test tidak valid.\n");
    }
    // Mencegah salah kirim ke email karyawan saat testing.
    if ($testEmail !== 'affanmaulanagamer@gmail.com') {
        exit("Mode test hanya diizinkan ke inbox pengujian yang disepakati.\n");
    }
    $pendingTest = [
        'PEMERIKSAAN' => qaAmbilPendingValidasi($konek, 'menunggu_pemeriksa'),
        'PERSETUJUAN' => qaAmbilPendingValidasi($konek, 'menunggu_penyetuju'),
    ];
    echo "Pending Pemeriksa: " . count($pendingTest['PEMERIKSAAN']) . "\n";
    echo "Pending Penyetuju: " . count($pendingTest['PERSETUJUAN']) . "\n";
    $testUser = ['uname' => 'Tim QA (Preview)', 'sapaan' => 'Bapak/Ibu'];
    $sent = 0;
    foreach ($pendingTest as $jenisTest => $rowsTest) {
        if (!$rowsTest) continue; // Jangan kirim email kosong.
        $htmlTest = $jenisTest === 'PEMERIKSAAN'
            ? qaTemplateEmailValidasiPemeriksa($testUser, $rowsTest, '', date('Y-m-d'), true, 'Tim QA')
            : qaTemplateEmailValidasiPenyetuju($testUser, $rowsTest, '', date('Y-m-d'), true, 'Tim QA');
        $subjectTest = '[TEST DATA ASLI - TANPA LOGIN] Reminder '
            . ($jenisTest === 'PEMERIKSAAN' ? 'Pemeriksaan' : 'Persetujuan')
            . ' QA - ' . date('d.m.Y');
        $resultTest = qaKirimEmail($testEmail, 'QA Preview', $subjectTest, $htmlTest);
        $okTest = !empty($resultTest['success']);
        echo $jenisTest . ': ' . count($rowsTest) . ' data, '
            . ($okTest ? 'TERKIRIM' : 'GAGAL: ' . ($resultTest['message'] ?? 'Unknown')) . "\n";
        if ($okTest) $sent++;
    }
    echo "Selesai. Email test terkirim: $sent. Tidak ada token/login atau perubahan status QA.\n";
    exit;
}

// CEK PRASYARAT MAGIC LOGIN

// ============================================================



if (!qaValidasiTableExists($konek, 'qa_validasi_login_token')) {

    qaDailyValidasiLog(

        'ERROR | tabel qa_validasi_login_token belum ada. '

        . 'Jalankan 03_validasi_login_token.sql.'

    );



    exit(

        "Tabel qa_validasi_login_token belum ada.\n"

        . "Jalankan 03_validasi_login_token.sql terlebih dahulu.\n"

    );

}



// URL yang dibuka dari tombol email.

// Untuk server/intranet, set QA_VALIDATION_MAGIC_URL ke URL server yang

// benar-benar dapat diakses komputer penerima.

$magicLoginBaseUrl = trim((string)getenv('QA_VALIDATION_MAGIC_URL'));



if ($magicLoginBaseUrl === '') {

    $magicLoginBaseUrl =

        'http://localhost/qa_kalibrasi/modul/qa/approval/validasi_login.php';

}



qaDailyValidasiLog('============================================================');

qaDailyValidasiLog('DAILY VALIDASI START' . ($isTest ? ' [TEST]' : ''));



$pendingPemeriksa = qaAmbilPendingValidasi(

    $konek,

    'menunggu_pemeriksa'

);



$pendingPenyetuju = qaAmbilPendingValidasi(

    $konek,

    'menunggu_penyetuju'

);



qaDailyValidasiLog(

    'PENDING | pemeriksa=' . count($pendingPemeriksa)

    . ' | penyetuju=' . count($pendingPenyetuju)

);



$totalProcessed = 0;

$totalSent = 0;

$totalCcSent = 0;

$testUserFound = false;



// ============================================================

// EMAIL PEMERIKSA

// ============================================================



if ($pendingPemeriksa) {

    $usersPemeriksa = qaAmbilPenerimaValidasi(

        $konek,

        'pemeriksa',

        $isTest,

        $testEmail

    );



    foreach ($usersPemeriksa as $user) {

        $testUserFound = true;

        $totalProcessed++;



        $uid = trim((string)($user['uid'] ?? ''));

        $email = strtolower(trim((string)($user['email_pembuat'] ?? '')));



        $magic = qaBuatMagicLoginValidasi(

            $konek,

            $uid,

            $email,

            'pemeriksaan',

            $magicLoginBaseUrl

        );



        if (empty($magic['success'])) {

            qaDailyValidasiLog(

                'GAGAL TOKEN PEMERIKSA | uid=' . $uid

                . ' | email=' . $email

                . ' | ' . (string)($magic['message'] ?? 'Unknown error')

            );

            continue;

        }



        $subject = ($isTest ? '[TEST] ' : '')

            . 'Reminder Pemeriksaan QA - '

            . date('d.m.Y');



        $html = qaTemplateEmailValidasiPemeriksa(

            $user,

            $pendingPemeriksa,

            (string)$magic['url'],

            date('Y-m-d')

        );



        qaDailyValidasiLog(

            'KIRIM PEMERIKSA | uid=' . $uid

            . ' | email=' . $email

            . ' | pending=' . count($pendingPemeriksa)

            . ' | token_id=' . (int)$magic['token_id']

        );



        $hasil = qaKirimEmail(

            $email,

            trim((string)($user['uname'] ?? '')),

            $subject,

            $html

        );



        qaSimpanLogEmailValidasi($konek, $uid, $email, 'PEMERIKSAAN', count($pendingPemeriksa), $hasil, $isTest);



        if (!empty($hasil['success'])) {

            $totalSent++;

            qaDailyValidasiLog(

                'BERHASIL PEMERIKSA | ' . $email

                . ' | token_id=' . (int)$magic['token_id']

            );



            // CC sengaja tidak dikirim pada mode --test.

            if (!$isTest) {

                $totalCcSent += qaKirimSalinanCcValidasi(

                    $konek,

                    'PEMERIKSAAN',

                    $user,

                    $pendingPemeriksa,

                    false

                );

            }

        } else {

            qaCabutMagicLoginValidasi(

                $konek,

                (int)$magic['token_id']

            );



            qaDailyValidasiLog(

                'GAGAL PEMERIKSA | ' . $email

                . ' | token dicabut'

                . ' | ' . (string)($hasil['message'] ?? 'Unknown error')

            );

        }

    }

} else {

    qaDailyValidasiLog(

        'SKIP PEMERIKSA | tidak ada transaksi menunggu pemeriksa.'

    );

}



// ============================================================

// EMAIL PENYETUJU

// ============================================================



if ($pendingPenyetuju) {

    $usersPenyetuju = qaAmbilPenerimaValidasi(

        $konek,

        'penyetuju',

        $isTest,

        $testEmail

    );



    foreach ($usersPenyetuju as $user) {

        $testUserFound = true;

        $totalProcessed++;



        $uid = trim((string)($user['uid'] ?? ''));

        $email = strtolower(trim((string)($user['email_pembuat'] ?? '')));



        $magic = qaBuatMagicLoginValidasi(

            $konek,

            $uid,

            $email,

            'persetujuan',

            $magicLoginBaseUrl

        );



        if (empty($magic['success'])) {

            qaDailyValidasiLog(

                'GAGAL TOKEN PENYETUJU | uid=' . $uid

                . ' | email=' . $email

                . ' | ' . (string)($magic['message'] ?? 'Unknown error')

            );

            continue;

        }



        $subject = ($isTest ? '[TEST] ' : '')

            . 'Reminder Persetujuan QA - '

            . date('d.m.Y');



        $html = qaTemplateEmailValidasiPenyetuju(

            $user,

            $pendingPenyetuju,

            (string)$magic['url'],

            date('Y-m-d')

        );



        qaDailyValidasiLog(

            'KIRIM PENYETUJU | uid=' . $uid

            . ' | email=' . $email

            . ' | pending=' . count($pendingPenyetuju)

            . ' | token_id=' . (int)$magic['token_id']

        );



        $hasil = qaKirimEmail(

            $email,

            trim((string)($user['uname'] ?? '')),

            $subject,

            $html

        );



        qaSimpanLogEmailValidasi($konek, $uid, $email, 'PERSETUJUAN', count($pendingPenyetuju), $hasil, $isTest);



        if (!empty($hasil['success'])) {

            $totalSent++;

            qaDailyValidasiLog(

                'BERHASIL PENYETUJU | ' . $email

                . ' | token_id=' . (int)$magic['token_id']

            );



            // CC sengaja tidak dikirim pada mode --test.

            if (!$isTest) {

                $totalCcSent += qaKirimSalinanCcValidasi(

                    $konek,

                    'PERSETUJUAN',

                    $user,

                    $pendingPenyetuju,

                    false

                );

            }

        } else {

            qaCabutMagicLoginValidasi(

                $konek,

                (int)$magic['token_id']

            );



            qaDailyValidasiLog(

                'GAGAL PENYETUJU | ' . $email

                . ' | token dicabut'

                . ' | ' . (string)($hasil['message'] ?? 'Unknown error')

            );

        }

    }

} else {

    qaDailyValidasiLog(

        'SKIP PENYETUJU | tidak ada transaksi menunggu penyetuju.'

    );

}



if ($isTest && !$testUserFound) {

    $debugPemeriksa = qaAmbilPenerimaValidasi(

        $konek,

        'pemeriksa',

        true,

        $testEmail

    );

    $debugPenyetuju = qaAmbilPenerimaValidasi(

        $konek,

        'penyetuju',

        true,

        $testEmail

    );



    qaDailyValidasiLog(

        'TEST NO SEND | email=' . $testEmail

        . ' | pending_pemeriksa=' . count($pendingPemeriksa)

        . ' | pending_penyetuju=' . count($pendingPenyetuju)

        . ' | user_pemeriksa=' . count($debugPemeriksa)

        . ' | user_penyetuju=' . count($debugPenyetuju)

    );



    echo "Tidak ada email validasi yang dikirim.\\n";

    echo "DEBUG:\\n";

    echo "- Pending Pemeriksa : " . count($pendingPemeriksa) . "\n";

    echo "- Pending Penyetuju : " . count($pendingPenyetuju) . "\n";

    echo "- User Pemeriksa dengan email test : " . count($debugPemeriksa) . "\n";

    echo "- User Penyetuju dengan email test : " . count($debugPenyetuju) . "\n";

    exit;

}



qaDailyValidasiLog(

    'SELESAI | proses=' . $totalProcessed

    . ' | email_terkirim=' . $totalSent

    . ' | cc_terkirim=' . $totalCcSent

    . ' | pending_pemeriksa=' . count($pendingPemeriksa)

    . ' | pending_penyetuju=' . count($pendingPenyetuju)

);



qaDailyValidasiLog('DAILY VALIDASI FINISH');

qaDailyValidasiLog('============================================================');



echo "Daily reminder validasi selesai. Email utama terkirim: {$totalSent}, CC informasi: {$totalCcSent}.\n";

<?php
session_start();

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

include "../../inc/inc_koneksi.php";
header('Content-Type: application/json; charset=utf-8');


/* ============================================================
 * HELPER
 * ============================================================ */

$action = $_GET['action'] ?? '';

function outJson($data)
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function postv($key, $default = '')
{
    return isset($_POST[$key])
        ? trim((string) $_POST[$key])
        : $default;
}


/* ============================================================
 * CEK ALAT
 * ============================================================ */

if ($action === 'cek_alat') {

    $no_part = postv('no_part');

    if ($no_part === '') {
        outJson([
            'found' => false,
            'message' => 'Nomor alat wajib diisi.'
        ]);
    }

    $stmt = $konek->prepare("
        SELECT
            p.*,
            sp.kode_pemilik,
            sp.nama_pemilik
        FROM qa_part p
        LEFT JOIN qa_seksi_pemilik sp
            ON sp.id = p.seksi_pemilik_id
        WHERE LOWER(TRIM(p.no_part)) = LOWER(TRIM(?))
          AND p.status = 'aktif'
        LIMIT 1
    ");

    if (!$stmt) {
        outJson([
            'found' => false,
            'message' => 'Query cek alat gagal: ' . $konek->error
        ]);
    }

    $stmt->bind_param('s', $no_part);
    $stmt->execute();

    $res = $stmt->get_result();
    $data = $res->fetch_assoc();

    $stmt->close();

    if (!$data) {
        outJson([
            'found' => false
        ]);
    }


    /* --------------------------------------------------------
     * HISTORY QA
     * -------------------------------------------------------- */

    $history = [];

    $stmt = $konek->prepare("
        SELECT
            id,
            tanggal_pengecekan,
            tanggal_jadwal_saat_pengecekan,
            status_kalibrasi,
            seksi_qa,
            periode_berikutnya,
            nomor_pengecekan,
            keterangan
        FROM qa_pengecekan
        WHERE alat_id = ?
        ORDER BY
            tanggal_pengecekan DESC,
            id DESC
        LIMIT 20
    ");

    if ($stmt) {

        $stmt->bind_param('i', $data['id']);
        $stmt->execute();

        $r = $stmt->get_result();

        while ($row = $r->fetch_assoc()) {
            $history[] = $row;
        }

        $stmt->close();
    }


    outJson([
        'found' => true,
        'data' => $data,
        'history' => $history
    ]);
}


/* ============================================================
 * SIMPAN ALAT BARU
 * ============================================================ */

if ($action === 'simpan_alat') {

    $no_part    = postv('no_part');
    $jenis_part = postv('jenis_part');
    $nama_part  = postv('nama_part');

    $ownerCode  = postv('seksi');

    $periode    = (int) postv('periode', 0);
    $kls        = postv('kls');

    $ukuran     = postv('ukuran');
    $resolusi   = postv('resolusi');
    $merk       = postv('merk');
    $lokasi     = postv('lokasi');
    $no_seri    = postv('no_seri');
    $type       = postv('type');
    $no_fa      = postv('no_fa');

    $tgl_manual = postv('tgl_manual');


    /* --------------------------------------------------------
     * VALIDASI
     * -------------------------------------------------------- */

    if (
        $no_part === '' ||
        $nama_part === '' ||
        $ownerCode === '' ||
        $periode <= 0 ||
        $kls === ''
    ) {

        outJson([
            'status' => 'error',
            'message' => 'Data master alat belum lengkap.'
        ]);
    }


    /* --------------------------------------------------------
     * CEK DUPLIKAT NOMOR ALAT
     * -------------------------------------------------------- */

    $stmt = $konek->prepare("
        SELECT id
        FROM qa_part
        WHERE LOWER(TRIM(no_part)) = LOWER(TRIM(?))
        LIMIT 1
    ");

    if (!$stmt) {

        outJson([
            'status' => 'error',
            'message' => 'Query pengecekan nomor alat gagal: ' . $konek->error
        ]);
    }

    $stmt->bind_param('s', $no_part);
    $stmt->execute();

    $duplicate = $stmt->get_result()->fetch_assoc();

    $stmt->close();


    if ($duplicate) {

        outJson([
            'status' => 'error',
            'message' => 'Nomor alat/part sudah terdaftar.'
        ]);
    }


    /* --------------------------------------------------------
     * CARI SEKSI PEMILIK
     * -------------------------------------------------------- */

    $stmt = $konek->prepare("
        SELECT
            id,
            kode_pemilik,
            nama_pemilik
        FROM qa_seksi_pemilik
        WHERE (
            kode_pemilik = ?
            OR nama_pemilik = ?
        )
        AND status = 'aktif'
        LIMIT 1
    ");

    if (!$stmt) {

        outJson([
            'status' => 'error',
            'message' => 'Query seksi pemilik gagal: ' . $konek->error
        ]);
    }

    $stmt->bind_param(
        'ss',
        $ownerCode,
        $ownerCode
    );

    $stmt->execute();

    $owner = $stmt->get_result()->fetch_assoc();

    $stmt->close();


    if (!$owner) {

        outJson([
            'status' => 'error',
            'message' => 'Seksi pemilik tidak ditemukan: ' . $ownerCode
        ]);
    }

    $ownerId = (int) $owner['id'];


    /* ========================================================
     * HITUNG TANGGAL JADWAL QA
     * ======================================================== */

    if ($tgl_manual !== '') {

        /*
         * Kalau user mengisi Tgl Input Manual,
         * gunakan tanggal tersebut sebagai tanggal awal.
         */

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_manual)) {

            outJson([
                'status' => 'error',
                'message' => 'Format Tgl Input Manual tidak valid.'
            ]);
        }


        $tanggalAwal = DateTime::createFromFormat(
            'Y-m-d',
            $tgl_manual
        );

        $dateErrors = DateTime::getLastErrors();

        if (
            !$tanggalAwal ||
            (
                $dateErrors !== false &&
                (
                    $dateErrors['warning_count'] > 0 ||
                    $dateErrors['error_count'] > 0
                )
            )
        ) {

            outJson([
                'status' => 'error',
                'message' => 'Tanggal Input Manual tidak valid.'
            ]);
        }

    } else {

        /*
         * Kalau Tgl Input Manual kosong,
         * gunakan tanggal hari ini.
         */

        $tanggalAwal = new DateTime();
    }


    /*
     * Tambahkan periode kalibrasi.
     *
     * Contoh:
     *
     * 11-09-2026
     * periode 7 hari
     *
     * hasil:
     * 18-09-2026
     */

    $tanggalAwal->modify('+' . $periode . ' days');

    $jadwal = $tanggalAwal->format('Y-m-d');


    /* ========================================================
     * UPLOAD FOTO
     * ======================================================== */

    $foto = '';

    if (
        isset($_FILES['foto_alat']) &&
        $_FILES['foto_alat']['error'] === UPLOAD_ERR_OK
    ) {

        /* Maksimal 10 MB */

        if ($_FILES['foto_alat']['size'] > 10485760) {

            outJson([
                'status' => 'error',
                'message' => 'Gagal! Ukuran gambar maksimal 10MB.'
            ]);
        }


        $dir = __DIR__ . '/../../images/alat/';


        if (!is_dir($dir)) {

            if (!mkdir($dir, 0777, true)) {

                outJson([
                    'status' => 'error',
                    'message' => 'Folder upload foto tidak dapat dibuat.'
                ]);
            }
        }


        $ext = strtolower(
            pathinfo(
                $_FILES['foto_alat']['name'],
                PATHINFO_EXTENSION
            )
        );


        if (
            !in_array(
                $ext,
                ['jpg', 'jpeg', 'png'],
                true
            )
        ) {

            outJson([
                'status' => 'error',
                'message' => 'Format gambar harus JPG, JPEG, atau PNG.'
            ]);
        }


        $namaFileAman = preg_replace(
            '/[^A-Za-z0-9]/',
            '',
            $no_part
        );


        $foto =
            'ALAT_' .
            $namaFileAman .
            '_' .
            time() .
            '.' .
            $ext;


        if (
            !move_uploaded_file(
                $_FILES['foto_alat']['tmp_name'],
                $dir . $foto
            )
        ) {

            outJson([
                'status' => 'error',
                'message' => 'Gagal menyimpan foto alat.'
            ]);
        }
    }


    /* ========================================================
     * CEK KOLOM FOTO
     * ======================================================== */

    $hasFoto = false;

    $chk = $konek->query("
        SHOW COLUMNS
        FROM qa_part
        LIKE 'foto_alat'
    ");

    if (
        $chk &&
        $chk->num_rows > 0
    ) {

        $hasFoto = true;
    }


    /* ========================================================
     * INSERT KE QA_PART
     * ======================================================== */

    if ($hasFoto) {

        $sql = "
            INSERT INTO qa_part (
                no_part,
                jenis_part,
                nama_part,
                seksi_id,
                seksi_pemilik_id,
                periode_kalibrasi,
                jadwal_kalibrasi,
                status,
                ukuran,
                no_fa,
                resolusi,
                merk,
                lokasi,
                no_seri,
                type,
                kls,
                foto_alat
            )
            VALUES (
                ?,
                ?,
                ?,
                NULL,
                ?,
                ?,
                ?,
                'aktif',
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ";


        $stmt = $konek->prepare($sql);


        if (!$stmt) {

            outJson([
                'status' => 'error',
                'message' => 'Prepare INSERT gagal: ' . $konek->error
            ]);
        }


        /*
         * TOTAL PARAMETER = 15
         *
         * 1  no_part        s
         * 2  jenis_part     s
         * 3  nama_part      s
         * 4  ownerId        i
         * 5  periode        i
         * 6  jadwal         s
         * 7  ukuran         s
         * 8  no_fa          s
         * 9  resolusi       s
         * 10 merk           s
         * 11 lokasi         s
         * 12 no_seri       s
         * 13 type          s
         * 14 kls           s
         * 15 foto          s
         */

        $stmt->bind_param(
            'sssiissssssssss',
            $no_part,
            $jenis_part,
            $nama_part,
            $ownerId,
            $periode,
            $jadwal,
            $ukuran,
            $no_fa,
            $resolusi,
            $merk,
            $lokasi,
            $no_seri,
            $type,
            $kls,
            $foto
        );

    } else {

        $sql = "
            INSERT INTO qa_part (
                no_part,
                jenis_part,
                nama_part,
                seksi_id,
                seksi_pemilik_id,
                periode_kalibrasi,
                jadwal_kalibrasi,
                status,
                ukuran,
                no_fa,
                resolusi,
                merk,
                lokasi,
                no_seri,
                type,
                kls
            )
            VALUES (
                ?,
                ?,
                ?,
                NULL,
                ?,
                ?,
                ?,
                'aktif',
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ";


        $stmt = $konek->prepare($sql);


        if (!$stmt) {

            outJson([
                'status' => 'error',
                'message' => 'Prepare INSERT gagal: ' . $konek->error
            ]);
        }


        /*
         * TOTAL PARAMETER = 14
         *
         * 1  no_part        s
         * 2  jenis_part     s
         * 3  nama_part      s
         * 4  ownerId        i
         * 5  periode        i
         * 6  jadwal         s
         * 7  ukuran         s
         * 8  no_fa          s
         * 9  resolusi       s
         * 10 merk           s
         * 11 lokasi         s
         * 12 no_seri        s
         * 13 type          s
         * 14 kls           s
         */

        $stmt->bind_param(
            'sssiisssssssss',
            $no_part,
            $jenis_part,
            $nama_part,
            $ownerId,
            $periode,
            $jadwal,
            $ukuran,
            $no_fa,
            $resolusi,
            $merk,
            $lokasi,
            $no_seri,
            $type,
            $kls
        );
    }


    /* ========================================================
     * EXECUTE INSERT
     * ======================================================== */

    if (!$stmt->execute()) {

        $error = $stmt->error;

        $stmt->close();

        outJson([
            'status' => 'error',
            'message' => 'Gagal menyimpan alat: ' . $error
        ]);
    }

    $alatId = $stmt->insert_id;

    $stmt->close();


    /* ========================================================
     * RESPONSE
     * ======================================================== */

    outJson([
        'status' => 'success',
        'message' => 'Alat berhasil didaftarkan.',
        'alat_id' => $alatId,
        'jadwal_selanjutnya' => $jadwal
    ]);
}


/* ============================================================
 * SIMPAN HASIL QA
 * ============================================================ */

if ($action === 'simpan_qa') {

    $no_part = postv('no_part');
    $status  = postv('status_qa');
    $seksiQA = postv('seksi_qa');
    $ket     = postv('keterangan');


    /* --------------------------------------------------------
     * VALIDASI
     * -------------------------------------------------------- */

    if (
        !in_array(
            $status,
            ['Good', 'Bad'],
            true
        )
    ) {

        outJson([
            'status' => 'error',
            'message' => 'Status QA harus Good atau Bad.'
        ]);
    }


    if (
        $no_part === '' ||
        $seksiQA === ''
    ) {

        outJson([
            'status' => 'error',
            'message' => 'Nomor alat dan Seksi QA wajib diisi.'
        ]);
    }


    /* --------------------------------------------------------
     * AMBIL DATA ALAT
     * -------------------------------------------------------- */

    $stmt = $konek->prepare("
        SELECT
            id,
            jadwal_kalibrasi,
            periode_kalibrasi
        FROM qa_part
        WHERE no_part = ?
          AND status = 'aktif'
        LIMIT 1
    ");


    if (!$stmt) {

        outJson([
            'status' => 'error',
            'message' => 'Query alat gagal: ' . $konek->error
        ]);
    }


    $stmt->bind_param(
        's',
        $no_part
    );

    $stmt->execute();

    $part = $stmt->get_result()->fetch_assoc();

    $stmt->close();


    if (!$part) {

        outJson([
            'status' => 'error',
            'message' => 'Alat tidak ditemukan.'
        ]);
    }


    $alatId = (int) $part['id'];

    $jadwalLama =
        !empty($part['jadwal_kalibrasi'])
            ? $part['jadwal_kalibrasi']
            : date('Y-m-d');


    $periode = max(
        1,
        (int) $part['periode_kalibrasi']
    );


    /* ========================================================
     * TANGGAL QA SEKARANG
     * ======================================================== */

    $tanggalQA = new DateTime();

    $tgl = $tanggalQA->format('Y-m-d');


    /* ========================================================
     * HITUNG JADWAL BERIKUTNYA
     * ======================================================== */

    $tanggalQA->modify(
        '+' . $periode . ' days'
    );

    $jadwalBaru =
        $tanggalQA->format('Y-m-d');


    /* ========================================================
     * NOMOR PENGECEKAN
     * ======================================================== */

    $nomor =
        'QA-' .
        date('YmdHis') .
        '-' .
        $alatId;


    $createdBy =
        $_SESSION['userid'] ?? 'SISTEM';


    /* ========================================================
     * SIMPAN HISTORY
     * ======================================================== */

    $stmt = $konek->prepare("
        INSERT INTO qa_pengecekan (
            alat_id,
            no_part,
            nomor_pengecekan,
            tanggal_pengecekan,
            tanggal_jadwal_saat_pengecekan,
            status_kalibrasi,
            seksi_qa,
            periode_berikutnya,
            created_by,
            keterangan
        )
        VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");


    if (!$stmt) {

        outJson([
            'status' => 'error',
            'message' => 'Prepare history gagal: ' . $konek->error
        ]);
    }


    $stmt->bind_param(
        'isssssssss',
        $alatId,
        $no_part,
        $nomor,
        $tgl,
        $jadwalLama,
        $status,
        $seksiQA,
        $jadwalBaru,
        $createdBy,
        $ket
    );


    if (!$stmt->execute()) {

        $error = $stmt->error;

        $stmt->close();


        outJson([
            'status' => 'error',
            'message' => 'Gagal menyimpan hasil QA: ' . $error
        ]);
    }


    $stmt->close();


    /* ========================================================
     * UPDATE JADWAL MASTER
     * ======================================================== */

    $stmt = $konek->prepare("
        UPDATE qa_part
        SET
            jadwal_kalibrasi = ?,
            updated_at = NOW()
        WHERE id = ?
    ");


    if (!$stmt) {

        outJson([
            'status' => 'error',
            'message' => 'Prepare update jadwal gagal: ' . $konek->error
        ]);
    }


    $stmt->bind_param(
        'si',
        $jadwalBaru,
        $alatId
    );


    if (!$stmt->execute()) {

        $error = $stmt->error;

        $stmt->close();


        outJson([
            'status' => 'error',
            'message' => 'Gagal update jadwal alat: ' . $error
        ]);
    }


    $stmt->close();


    /* --------------------------------------------------------
     * RESPONSE
     * -------------------------------------------------------- */

    outJson([
        'status' => 'success',
        'message' => 'Hasil QA berhasil disimpan.',
        'jadwal_selanjutnya' => $jadwalBaru,
        'no_pengecekan' => $nomor
    ]);
}


/* ============================================================
 * MASTER KELAS
 * ============================================================ */

if ($action === 'simpan_master_kls') {

    $nama = postv('nama_kelas');


    if ($nama === '') {

        outJson([
            'status' => 'error',
            'message' => 'Nama kelas wajib diisi.'
        ]);
    }


    $stmt = $konek->prepare("
        SELECT id
        FROM qa_kls
        WHERE nama_kelas = ?
        LIMIT 1
    ");

    if (!$stmt) {

        outJson([
            'status' => 'error',
            'message' => 'Query kelas gagal: ' . $konek->error
        ]);
    }


    $stmt->bind_param(
        's',
        $nama
    );

    $stmt->execute();


    if (
        $stmt->get_result()->num_rows > 0
    ) {

        $stmt->close();


        outJson([
            'status' => 'error',
            'message' => 'Nama Kelas ini sudah ada di Database!'
        ]);
    }


    $stmt->close();


    $stmt = $konek->prepare("
        INSERT INTO qa_kls (
            nama_kelas
        )
        VALUES (?)
    ");


    if (!$stmt) {

        outJson([
            'status' => 'error',
            'message' => 'Prepare insert kelas gagal: ' . $konek->error
        ]);
    }


    $stmt->bind_param(
        's',
        $nama
    );


    $ok = $stmt->execute();

    $stmt->close();


    outJson([
        'status' => $ok
            ? 'success'
            : 'error'
    ]);
}


/* ============================================================
 * MASTER SEKSI
 * ============================================================ */

if ($action === 'simpan_master_seksi') {

    $nama = postv('nama_seksi');


    if ($nama === '') {

        outJson([
            'status' => 'error',
            'message' => 'Nama seksi wajib diisi.'
        ]);
    }


    $stmt = $konek->prepare("
        SELECT id
        FROM qa_seksi_master
        WHERE nama_seksi = ?
        LIMIT 1
    ");


    if (!$stmt) {

        outJson([
            'status' => 'error',
            'message' => 'Query seksi gagal: ' . $konek->error
        ]);
    }


    $stmt->bind_param(
        's',
        $nama
    );

    $stmt->execute();


    if (
        $stmt->get_result()->num_rows > 0
    ) {

        $stmt->close();


        outJson([
            'status' => 'error',
            'message' => 'Seksi ini sudah ada di Database!'
        ]);
    }


    $stmt->close();


    $stmt = $konek->prepare("
        INSERT INTO qa_seksi_master (
            nama_seksi
        )
        VALUES (?)
    ");


    if (!$stmt) {

        outJson([
            'status' => 'error',
            'message' => 'Prepare insert seksi gagal: ' . $konek->error
        ]);
    }


    $stmt->bind_param(
        's',
        $nama
    );


    $ok = $stmt->execute();

    $stmt->close();


    outJson([
        'status' => $ok
            ? 'success'
            : 'error'
    ]);
}


/* ============================================================
 * HAPUS MASTER KELAS
 * ============================================================ */

if ($action === 'hapus_master_kls') {

    $nama = postv('nama_kelas');


    $stmt = $konek->prepare("
        DELETE FROM qa_kls
        WHERE nama_kelas = ?
    ");


    if (!$stmt) {

        outJson([
            'status' => 'error',
            'message' => 'Query hapus kelas gagal: ' . $konek->error
        ]);
    }


    $stmt->bind_param(
        's',
        $nama
    );


    $ok = $stmt->execute();

    $stmt->close();


    outJson([
        'status' => $ok
            ? 'success'
            : 'error'
    ]);
}


/* ============================================================
 * HAPUS MASTER SEKSI
 * ============================================================ */

if ($action === 'hapus_master_seksi') {

    $nama = postv('nama_seksi');


    $stmt = $konek->prepare("
        DELETE FROM qa_seksi_master
        WHERE nama_seksi = ?
    ");


    if (!$stmt) {

        outJson([
            'status' => 'error',
            'message' => 'Query hapus seksi gagal: ' . $konek->error
        ]);
    }


    $stmt->bind_param(
        's',
        $nama
    );


    $ok = $stmt->execute();

    $stmt->close();


    outJson([
        'status' => $ok
            ? 'success'
            : 'error'
    ]);
}


/* ============================================================
 * ACTION TIDAK DIKENALI
 * ============================================================ */

outJson([
    'status' => 'error',
    'message' => 'Action tidak dikenali.'
]);

?>
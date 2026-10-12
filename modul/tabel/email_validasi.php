<?php
session_start();

if (empty($_SESSION['login'])) {
    header('location: ../../logout.php');
    exit;
}

require_once __DIR__ . '/../../inc/inc_koneksi.php';

/** @var mysqli $konek */
if (!isset($konek) || !($konek instanceof mysqli)) {
    die('Koneksi database tidak tersedia.');
}

function evEsc($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function evTableExists($konek, $table)
{
    $table = preg_replace('/[^A-Za-z0-9_]/', '', (string)$table);

    if ($table === '') {
        return false;
    }

    $safe = $konek->real_escape_string($table);
    $res = $konek->query("SHOW TABLES LIKE '{$safe}'");

    return $res && $res->num_rows > 0;
}

// ============================================================
// CEK ADMIN DARI TABEL USER UTAMA
// ============================================================

$userUid = trim((string)($_SESSION['userid'] ?? ''));
$adminName = $userUid;
$isAdmin = false;

$stmtAdmin = $konek->prepare("
    SELECT
        uname,
        admin
    FROM `user`
    WHERE uid = ?
    LIMIT 1
");

if ($stmtAdmin) {
    $stmtAdmin->bind_param('s', $userUid);
    $stmtAdmin->execute();

    $resAdmin = $stmtAdmin->get_result();
    $rowAdmin = $resAdmin ? $resAdmin->fetch_assoc() : null;

    if ($rowAdmin) {
        $adminName = trim((string)($rowAdmin['uname'] ?? $userUid));
        $isAdmin = strtoupper(trim((string)($rowAdmin['admin'] ?? 'N'))) === 'Y';
    }

    $stmtAdmin->close();
}

if (!$isAdmin) {
    http_response_code(403);

    die(
        'Akses ditolak. '
        . 'Halaman Konfigurasi Email Validasi hanya untuk Admin.'
    );
}

// ============================================================
// CSRF
// ============================================================

if (empty($_SESSION['qa_email_validasi_csrf'])) {
    try {
        $_SESSION['qa_email_validasi_csrf'] =
            bin2hex(
                random_bytes(24)
            );
    } catch (Throwable $e) {
        $_SESSION['qa_email_validasi_csrf'] =
            hash(
                'sha256',
                session_id()
                . '|'
                . microtime(true)
            );
    }
}

$csrf =
    (string)
    $_SESSION['qa_email_validasi_csrf'];

// ============================================================
// CEK TABEL CONFIG
// ============================================================

$tableReady =
    evTableExists(
        $konek,
        'qa_email_validasi_penerima'
    );

// ============================================================
// FLASH
// ============================================================

$flash = '';
$flashType = 'success';

// ============================================================
// PROSES FORM
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postedCsrf =
        (string)(
            $_POST['csrf']
            ?? ''
        );

    if (
        !hash_equals(
            $csrf,
            $postedCsrf
        )
    ) {
        $flash =
            'Token keamanan tidak valid. '
            . 'Silakan muat ulang halaman.';

        $flashType =
            'danger';

    } elseif (!$tableReady) {

        $flash =
            'Tabel qa_email_validasi_penerima '
            . 'belum tersedia. '
            . 'Jalankan SQL terlebih dahulu.';

        $flashType =
            'danger';

    } else {

        $action =
            trim(
                (string)(
                    $_POST['action']
                    ?? ''
                )
            );

        // ====================================================
        // TAMBAH KONFIGURASI
        // ====================================================

        if ($action === 'add') {

            $jenisValidasi =
                strtoupper(
                    trim(
                        (string)(
                            $_POST['jenis_validasi']
                            ?? ''
                        )
                    )
                );

            $tipePenerima =
                strtoupper(
                    trim(
                        (string)(
                            $_POST['tipe_penerima']
                            ?? ''
                        )
                    )
                );

            $uidDipilih =
                trim(
                    (string)(
                        $_POST['user_uid']
                        ?? ''
                    )
                );

            if (
                !in_array(
                    $jenisValidasi,
                    [
                        'PEMERIKSAAN',
                        'PERSETUJUAN'
                    ],
                    true
                )
            ) {
                $flash =
                    'Jenis validasi tidak valid.';

                $flashType =
                    'danger';

            } elseif (
                !in_array(
                    $tipePenerima,
                    [
                        'TUJUAN',
                        'MENGETAHUI'
                    ],
                    true
                )
            ) {
                $flash =
                    'Kategori penerima tidak valid.';

                $flashType =
                    'danger';

            } elseif ($uidDipilih === '') {

                $flash =
                    'User wajib dipilih.';

                $flashType =
                    'danger';

            } else {

                // ============================================
                // AMBIL USER + EMAIL LIVE
                // ============================================

                $stmtUser =
                    $konek->prepare("
                        SELECT
                            uid,
                            TRIM(uname) AS nama,
                            TRIM(email_pembuat) AS email
                        FROM `user`
                        WHERE uid = ?
                          AND UPPER(
                                COALESCE(
                                    block,
                                    'N'
                                )
                              ) <> 'Y'
                          AND email_pembuat IS NOT NULL
                          AND TRIM(email_pembuat) <> ''
                        LIMIT 1
                    ");

                $userData =
                    null;

                if ($stmtUser) {

                    $stmtUser->bind_param(
                        's',
                        $uidDipilih
                    );

                    $stmtUser->execute();

                    $resUser =
                        $stmtUser->get_result();

                    $userData =
                        $resUser
                        ?
                        $resUser->fetch_assoc()
                        :
                        null;

                    $stmtUser->close();
                }

                if (!$userData) {

                    $flash =
                        'User tidak ditemukan, diblokir, '
                        . 'atau belum memiliki email '
                        . 'pada Data Pemakai Aplikasi.';

                    $flashType =
                        'danger';

                } else {

                    $emailLive =
                        strtolower(
                            trim(
                                (string)
                                $userData[
                                    'email'
                                ]
                            )
                        );

                    // ========================================
                    // CEK DUPLIKAT EMAIL DALAM ROLE YANG SAMA
                    // ========================================

                    $stmtDup =
                        $konek->prepare("
                            SELECT
                                c.id,
                                c.user_uid,
                                c.tipe_penerima,
                                u.uname,
                                u.email_pembuat

                            FROM
                                qa_email_validasi_penerima c

                            INNER JOIN
                                `user` u
                                    ON u.uid
                                    =
                                    c.user_uid

                            WHERE
                                c.jenis_validasi = ?

                            AND
                                c.status = 'aktif'

                            AND
                                LOWER(
                                    TRIM(
                                        u.email_pembuat
                                    )
                                )
                                =
                                LOWER(
                                    TRIM(?)
                                )

                            LIMIT 1
                        ");

                    $duplicate =
                        null;

                    if ($stmtDup) {

                        $stmtDup->bind_param(
                            'ss',
                            $jenisValidasi,
                            $emailLive
                        );

                        $stmtDup->execute();

                        $resDup =
                            $stmtDup->get_result();

                        $duplicate =
                            $resDup
                            ?
                            $resDup->fetch_assoc()
                            :
                            null;

                        $stmtDup->close();
                    }

                    if ($duplicate) {

                        $roleLabel =
                            $jenisValidasi
                            === 'PEMERIKSAAN'
                            ?
                            'Pemeriksa'
                            :
                            'Penyetuju';

                        $tipeLabel =
                            $duplicate[
                                'tipe_penerima'
                            ]
                            === 'TUJUAN'
                            ?
                            'Tujuan'
                            :
                            'Mengetahui';

                        $flash =
                            'Email '
                            . $emailLive
                            . ' sudah digunakan pada role '
                            . $roleLabel
                            . ' sebagai '
                            . $tipeLabel
                            . '.';

                        $flashType =
                            'danger';

                    } else {

                        // ====================================
                        // SIMPAN UID SAJA
                        // ====================================

                        $stmtAdd =
                            $konek->prepare("
                                INSERT INTO
                                    qa_email_validasi_penerima
                                (
                                    jenis_validasi,
                                    tipe_penerima,
                                    user_uid,
                                    status
                                )
                                VALUES
                                (
                                    ?,
                                    ?,
                                    ?,
                                    'aktif'
                                )
                            ");

                        if (!$stmtAdd) {

                            $flash =
                                'Gagal menyiapkan penyimpanan: '
                                . $konek->error;

                            $flashType =
                                'danger';

                        } else {

                            $stmtAdd->bind_param(
                                'sss',
                                $jenisValidasi,
                                $tipePenerima,
                                $uidDipilih
                            );

                            if (
                                $stmtAdd->execute()
                            ) {

                                $flash =
                                    'Konfigurasi email '
                                    . 'berhasil ditambahkan.';

                                $flashType =
                                    'success';

                            } else {

                                if (
                                    (int)$stmtAdd->errno
                                    === 1062
                                ) {

                                    $flash =
                                        'User tersebut sudah terdaftar '
                                        . 'pada role yang sama.';

                                } else {

                                    $flash =
                                        'Gagal menyimpan konfigurasi: '
                                        . $stmtAdd->error;
                                }

                                $flashType =
                                    'danger';
                            }

                            $stmtAdd->close();
                        }
                    }
                }
            }

        // ====================================================
        // HAPUS KONFIGURASI
        // ====================================================

        } elseif ($action === 'delete') {

            $id =
                (int)(
                    $_POST['id']
                    ?? 0
                );

            if ($id <= 0) {

                $flash =
                    'ID konfigurasi tidak valid.';

                $flashType =
                    'danger';

            } else {

                $stmtDel =
                    $konek->prepare("
                        DELETE FROM
                            qa_email_validasi_penerima
                        WHERE id = ?
                        LIMIT 1
                    ");

                if (!$stmtDel) {

                    $flash =
                        'Gagal menyiapkan penghapusan: '
                        . $konek->error;

                    $flashType =
                        'danger';

                } else {

                    $stmtDel->bind_param(
                        'i',
                        $id
                    );

                    $stmtDel->execute();

                    if (
                        $stmtDel->affected_rows
                        >
                        0
                    ) {

                        $flash =
                            'Konfigurasi email '
                            . 'berhasil dihapus.';

                        $flashType =
                            'success';

                    } else {

                        $flash =
                            'Data konfigurasi '
                            . 'tidak ditemukan.';

                        $flashType =
                            'warning';
                    }

                    $stmtDel->close();
                }
            }
        }
    }
}

// ============================================================
// AMBIL USER DARI TABEL `user`
// UID   = user.uid
// NAMA  = user.uname
// EMAIL = user.email_pembuat
// ============================================================

$users = [];

$resUsers =
    $konek->query("
        SELECT
            uid,
            TRIM(uname) AS nama,
            TRIM(email_pembuat) AS email

        FROM `user`

        WHERE
            UPPER(
                COALESCE(
                    block,
                    'N'
                )
            ) <> 'Y'

        AND
            email_pembuat IS NOT NULL

        AND
            TRIM(email_pembuat) <> ''

        ORDER BY
            TRIM(uname) ASC,
            uid ASC
    ");

if ($resUsers) {

    while (
        $row =
            $resUsers->fetch_assoc()
    ) {

        $users[] =
            $row;
    }
}

// ============================================================
// AMBIL KONFIGURASI + EMAIL LIVE
// ============================================================

$configRows = [];

if ($tableReady) {

    $resConfig =
        $konek->query("
            SELECT
                c.id,
                c.jenis_validasi,
                c.tipe_penerima,
                c.user_uid,
                c.status,

                COALESCE(
                    NULLIF(
                        TRIM(
                            u.uname
                        ),
                        ''
                    ),
                    c.user_uid
                ) AS uname,

                u.email_pembuat
                    AS email_live

            FROM
                qa_email_validasi_penerima c

            LEFT JOIN
                `user` u
                    ON u.uid
                    =
                    c.user_uid

            ORDER BY

                FIELD(
                    c.jenis_validasi,
                    'PEMERIKSAAN',
                    'PERSETUJUAN'
                ),

                FIELD(
                    c.tipe_penerima,
                    'TUJUAN',
                    'MENGETAHUI'
                ),

                COALESCE(
                    u.uname,
                    c.user_uid
                ) ASC,

                c.id ASC
        ");

    if ($resConfig) {

        while (
            $row =
                $resConfig->fetch_assoc()
        ) {

            $configRows[] =
                $row;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>
    Konfigurasi Email Validasi QA
</title>

<link
    href="../../css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="../../font-awesome-4.3.0/css/font-awesome.min.css"
    rel="stylesheet"
>

<style>

body {
    padding-top:80px;
    background:#f4f6f9;
}

.qa-card {
    background:#fff;
    border-radius:10px;
    padding:24px;
    margin-bottom:22px;
    box-shadow:0 4px 15px rgba(0,0,0,.06);
}

.qa-card h3 {
    margin-top:0;
}

.hint {
    color:#777;
    font-size:12px;
    line-height:1.6;
}

.config-info {
    margin-top:15px;
    margin-bottom:20px;
    padding:12px 15px;
    background:#f7f9fb;
    border-left:4px solid #337ab7;
}

.table > thead > tr > th {
    background:#eef2f6;
    vertical-align:middle;
}

.table > tbody > tr > td {
    vertical-align:middle;
}

.badge-role {
    display:inline-block;
    padding:4px 8px;
    border-radius:4px;
    font-size:11px;
    font-weight:700;
}

.badge-pemeriksa {
    background:#eaf3ff;
    border:1px solid #bfd7f0;
    color:#23527c;
}

.badge-penyetuju {
    background:#eef8ee;
    border:1px solid #c7dfc7;
    color:#2e6b38;
}

.type-tujuan {
    font-weight:700;
    color:#123b6d;
}

.type-mengetahui {
    color:#777;
}

.email-live {
    font-weight:600;
}

.navbar-brand img {
    height:25px;
    margin-top:-3px;
}


.qa-user-picker { position: relative; }
.qa-user-picker-input { position: relative; }
.qa-user-picker-input input { padding-right: 38px; cursor: text; }
.qa-user-picker-arrow { position: absolute; right: 12px; top: 0; height: 34px;
    display: flex; align-items: center; cursor: pointer; color: #555; }
.qa-user-picker-menu { display: none; position: absolute; z-index: 1050; top: 100%;
    left: 0; right: 0; max-height: 250px; overflow-y: auto; background: #fff;
    border: 1px solid #ccc; border-top: 0; box-shadow: 0 5px 12px rgba(0,0,0,.12); }
.qa-user-picker.open .qa-user-picker-menu { display: block; }
.qa-user-picker-option { display: block; width: 100%; text-align: left; border: 0;
    background: #fff; padding: 9px 12px; color: #333; white-space: normal; }
.qa-user-picker-option:hover, .qa-user-picker-option:focus { background: #f1f3f5; outline: none; }
.qa-user-picker-empty { padding: 10px 12px; color: #777; }

</style>

</head>

<body>

<nav class="navbar navbar-default navbar-fixed-top">

<div class="container-fluid">

<div class="navbar-header">

<a
    class="navbar-brand"
    href="../../media.php"
>

<img
    src="../../images/toto.png"
    alt="TOTO"
>

</a>

</div>

<ul class="nav navbar-nav navbar-left">

<li>

<a href="../../media.php">

<i class="fa fa-arrow-left"></i>

Kembali ke Menu Utama

</a>

</li>

</ul>

<ul class="nav navbar-nav navbar-right">

<li>

<a href="#">

<i class="fa fa-user"></i>

<?php
echo
    evEsc(
        $adminName
    );
?>

(Admin)

</a>

</li>

<li>

<a href="../../logout.php">

Logout

</a>

</li>

</ul>

</div>

</nav>

<div
    class="container-fluid"
    style="padding:0 30px 30px;"
>

<div class="qa-card">

<h3>

<i class="fa fa-envelope"></i>

Konfigurasi Email Pemeriksa &amp; Penyetuju

</h3>


<?php if ($flash !== ''): ?>

<div
    class="alert alert-<?php echo evEsc($flashType); ?>"
>

<?php
echo
    evEsc(
        $flash
    );
?>

</div>

<?php endif; ?>

<?php if (!$tableReady): ?>

<div class="alert alert-danger">

Tabel

<b>
qa_email_validasi_penerima
</b>

belum tersedia.

Jalankan SQL konfigurasi email
terlebih dahulu.

</div>

<?php else: ?>

<form
    method="post"
    class="row"
    style="
        margin-top:20px;
        margin-bottom:20px;
    "
>

<input
    type="hidden"
    name="csrf"
    value="<?php echo evEsc($csrf); ?>"
>

<input
    type="hidden"
    name="action"
    value="add"
>

<div class="col-md-2">

<div class="form-group">

<label>
Role
</label>

<select
    class="form-control"
    name="jenis_validasi"
    id="jenis_validasi"
    required
>

<option value="PEMERIKSAAN">

Pemeriksa

</option>

<option value="PERSETUJUAN">

Penyetuju

</option>

</select>

</div>

</div>

<div class="col-md-2">

<div class="form-group">

<label>
Kategori
</label>

<select
    class="form-control"
    name="tipe_penerima"
    id="tipe_penerima"
    required
>

<option value="TUJUAN">

Tujuan

</option>

<option value="MENGETAHUI">

Mengetahui

</option>

</select>

</div>

</div>

<div class="col-md-6">

<div class="form-group">

<label>
User / Email
</label>

<select
    class="form-control"
    name="user_uid"
    id="user_uid"
    style="display:none;"
>

<option value="">

-- Pilih User --

</option>

<?php foreach ($users as $u): ?>

<option

    value="<?php
        echo
            evEsc(
                $u['uid']
            );
    ?>"

>

<?php

echo
    evEsc(
        $u['uid']
        . ' - '
        . $u['nama']
        . ' ('
        . $u['email']
        . ')'
    );

?>

</option>

<?php endforeach; ?>

</select>

<div class="qa-user-picker" id="qa-user-picker">
    <div class="qa-user-picker-input">
        <input type="text" id="qa-user-search" class="form-control"
               placeholder="Cari UID, nama, atau email..." autocomplete="new-password" name="qa_lookup_only" autocapitalize="off" autocorrect="off" spellcheck="false" data-lpignore="true" data-1p-ignore="true" readonly required>
        <span class="qa-user-picker-arrow" id="qa-user-arrow" aria-hidden="true">&#9662;</span>
    </div>
    <div class="qa-user-picker-menu" id="qa-user-menu" role="listbox"></div>
</div>


</div>

</div>

<div class="col-md-2">

<div class="form-group">

<label>
&nbsp;
</label>

<button
    type="submit"
    class="btn btn-primary btn-block"
>

<i class="fa fa-plus"></i>

Tambah

</button>

</div>

</div>

</form>

<div class="table-responsive">

<table
    class="
        table
        table-bordered
        table-striped
        table-hover
    "
>

<thead>

<tr>

<th
    class="text-center"
    style="width:55px;"
>

No

</th>

<th
    class="text-center"
    style="width:130px;"
>

Role

</th>

<th
    class="text-center"
    style="width:130px;"
>

Jenis

</th>

<th style="width:130px;">

UID

</th>

<th>

Nama

</th>

<th>

Email Aktif

</th>

<th
    class="text-center"
    style="width:90px;"
>

Aksi

</th>

</tr>

</thead>

<tbody>

<?php if ($configRows): ?>

<?php $no = 1; ?>

<?php foreach ($configRows as $row): ?>

<tr>

<td class="text-center">

<?php echo $no++; ?>

</td>

<td class="text-center">

<?php
if (
    $row[
        'jenis_validasi'
    ]
    ===
    'PEMERIKSAAN'
):
?>

<span
    class="
        badge-role
        badge-pemeriksa
    "
>

Pemeriksa

</span>

<?php else: ?>

<span
    class="
        badge-role
        badge-penyetuju
    "
>

Penyetuju

</span>

<?php endif; ?>

</td>

<td class="text-center">

<?php
if (
    $row[
        'tipe_penerima'
    ]
    ===
    'TUJUAN'
):
?>

<span class="type-tujuan">

Tujuan

</span>

<?php else: ?>

<span class="type-mengetahui">

Mengetahui

</span>

<?php endif; ?>

</td>

<td>

<b>

<?php
echo
    evEsc(
        $row[
            'user_uid'
        ]
    );
?>

</b>

</td>

<td>

<?php
echo
    evEsc(
        $row[
            'uname'
        ]
    );
?>

</td>

<td>

<?php

$emailLive =
    trim(
        (string)(
            $row[
                'email_live'
            ]
            ?? ''
        )
    );

?>

<?php if ($emailLive !== ''): ?>

<span class="email-live">

<?php
echo
    evEsc(
        $emailLive
    );
?>

</span>

<?php else: ?>

<span class="text-danger">

Email pemakai tidak tersedia

</span>

<?php endif; ?>

</td>

<td class="text-center">

<button type="button" class="btn btn-danger btn-xs qa-delete-button"
        data-id="<?php echo (int)$row['id']; ?>"
        data-name="<?php echo evEsc($row['uname']); ?>"
        data-toggle="modal" data-target="#qaDeleteModal">
    <i class="fa fa-trash"></i> Hapus
</button>

</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>

<td
    colspan="7"
    class="text-center text-muted"
>

Belum ada konfigurasi email.

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

<?php endif; ?>

</div>

</div>


<div class="modal fade" id="qaDeleteModal" tabindex="-1" role="dialog" aria-labelledby="qaDeleteTitle">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="qaDeleteTitle">Konfirmasi Hapus</h4>
            </div>
            <div class="modal-body">Yakin ingin menghapus penerima email <strong id="qaDeleteName"></strong>?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="csrf" value="<?php echo evEsc($csrf); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="qaDeleteId" value="">
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="../../js/jquery.js"></script>

<script src="../../js/bootstrap.min.js"></script>

<script>
$(document).on('click', '.qa-delete-button', function () {
    $('#qaDeleteId').val($(this).attr('data-id'));
    $('#qaDeleteName').text($(this).attr('data-name'));
});
(function () {
    var select = document.getElementById('user_uid');
    var picker = document.getElementById('qa-user-picker');
    var input = document.getElementById('qa-user-search');
    var menu = document.getElementById('qa-user-menu');
    var arrow = document.getElementById('qa-user-arrow');
    if (!select || !picker || !input || !menu || !arrow) return;

    var options = Array.prototype.slice.call(select.options).filter(function (option) {
        return option.value !== '';
    });

    function render(query) {
        var term = (query || '').toLocaleLowerCase().trim();
        menu.innerHTML = '';
        var count = 0;
        options.forEach(function (option) {
            if (option.textContent.toLocaleLowerCase().indexOf(term) === -1) return;
            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'qa-user-picker-option';
            item.setAttribute('role', 'option');
            item.textContent = option.textContent.trim();
            item.addEventListener('click', function () {
                select.value = option.value;
                input.value = option.textContent.trim();
                input.setCustomValidity('');
                picker.classList.remove('open');
            });
            menu.appendChild(item);
            count++;
        });
        if (!count) {
            var empty = document.createElement('div');
            empty.className = 'qa-user-picker-empty';
            empty.textContent = 'Tidak ada user ditemukan';
            menu.appendChild(empty);
        }
    }

    function open() {
        render(input.value === (select.selectedIndex > 0 ? select.options[select.selectedIndex].textContent.trim() : '') ? '' : input.value);
        picker.classList.add('open');
    }

    input.addEventListener('focus', function () { input.removeAttribute('readonly'); open(); });
    input.addEventListener('click', open);
    input.addEventListener('input', function () {
        select.value = '';
        input.setCustomValidity('');
        render(input.value);
        picker.classList.add('open');
    });
    arrow.addEventListener('click', function () {
        if (picker.classList.contains('open')) picker.classList.remove('open');
        else { input.focus(); open(); }
    });
    document.addEventListener('click', function (event) {
        if (!picker.contains(event.target)) picker.classList.remove('open');
    });
    input.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') picker.classList.remove('open');
        if (event.key === 'Enter' && picker.classList.contains('open')) {
            var first = menu.querySelector('.qa-user-picker-option');
            if (first) { event.preventDefault(); first.click(); }
        }
    });
    var form = select.closest('form');
    if (form) form.addEventListener('submit', function (event) {
        if (!select.value) {
            event.preventDefault();
            input.setCustomValidity('Pilih user dari daftar.');
            input.reportValidity();
            open();
        }
    });
})();
</script>

</body>

</html>

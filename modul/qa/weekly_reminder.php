<?php
// Scheduler produksi: jalankan setiap Senin pagi, misalnya 06:30.
// Testing: jalankan `php weekly_reminder.php --test` untuk menjalankan proses kapan saja.
include __DIR__ . '/../../inc/inc_koneksi.php';
require_once __DIR__ . '/email_helper.php';

$isTest = in_array('--test', $argv ?? [], true);

if (!$isTest && (int)date('N') !== 1) exit("Bukan hari Senin. Gunakan --test untuk testing manual.\n");

if ($isTest) echo "MODE TEST: proses weekly reminder dijalankan manual." . PHP_EOL;

$mulai = date('Y-m-d');
$selesai = date('Y-m-d', strtotime('+6 days'));

$sql = "SELECT r.seksi_id, r.seksi_pemilik_id, r.user_uid, r.email,
        COALESCE(sp.nama_pemilik, s.nama_seksi) AS nama_seksi,
        p.id AS alat_id, p.no_part AS nomor_alat, p.nama_part AS nama_alat,
        p.jadwal_kalibrasi, DATEDIFF(p.jadwal_kalibrasi, CURDATE()) AS selisih_hari
    FROM qa_penerima_email r
    LEFT JOIN qa_seksi s ON s.id = r.seksi_id
    LEFT JOIN qa_seksi_pemilik sp ON sp.id = r.seksi_pemilik_id
    JOIN qa_part p ON p.status = 'aktif'
      AND ((r.seksi_pemilik_id IS NOT NULL AND p.seksi_pemilik_id = r.seksi_pemilik_id)
           OR (r.seksi_pemilik_id IS NULL AND r.seksi_id IS NOT NULL AND p.seksi_id = r.seksi_id))
    WHERE r.status = 'aktif'
      AND r.email <> ''
      AND p.jadwal_kalibrasi BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 DAY)
    ORDER BY COALESCE(sp.kode_pemilik, s.kode_seksi, s.nama_seksi) ASC, p.jadwal_kalibrasi ASC, p.no_part ASC";
$res = $konek->query($sql);
$jadwalPerEmail = [];
while ($r = $res->fetch_assoc()) $jadwalPerEmail[$r['email']][] = $r;

$overdueSql = "SELECT r.seksi_id, r.seksi_pemilik_id, r.user_uid, r.email,
        COALESCE(sp.nama_pemilik, s.nama_seksi) AS nama_seksi,
        p.id AS alat_id, p.no_part AS nomor_alat, p.nama_part AS nama_alat,
        p.jadwal_kalibrasi, DATEDIFF(CURDATE(), p.jadwal_kalibrasi) AS lama_hari
    FROM qa_penerima_email r
    LEFT JOIN qa_seksi s ON s.id = r.seksi_id
    LEFT JOIN qa_seksi_pemilik sp ON sp.id = r.seksi_pemilik_id
    JOIN qa_part p ON p.status = 'aktif'
      AND ((r.seksi_pemilik_id IS NOT NULL AND p.seksi_pemilik_id = r.seksi_pemilik_id)
           OR (r.seksi_pemilik_id IS NULL AND r.seksi_id IS NOT NULL AND p.seksi_id = r.seksi_id))
    WHERE r.status = 'aktif'
      AND r.email <> ''
      AND p.jadwal_kalibrasi < CURDATE()
    ORDER BY COALESCE(sp.kode_pemilik, s.kode_seksi, s.nama_seksi) ASC, p.jadwal_kalibrasi ASC, p.no_part ASC";
$overdueRes = $konek->query($overdueSql);
$overduePerEmail = [];
while ($r = $overdueRes->fetch_assoc()) $overduePerEmail[$r['email']][] = $r;

$emails = array_unique(array_merge(array_keys($jadwalPerEmail), array_keys($overduePerEmail)));
foreach ($emails as $email) {
    $jadwal = $jadwalPerEmail[$email] ?? [];
    $overdue = $overduePerEmail[$email] ?? [];
    $subjek = 'Pengingat Jadwal Kalibrasi Alat QA – ' . qaFormatTanggal($mulai) . ' s.d. ' . qaFormatTanggal($selesai);
    $isi = qaTemplateEmail('Bapak/Ibu', $subjek, 'Berikut jadwal kalibrasi alat QA untuk 7 hari ke depan.', $jadwal, $overdue);
    $send = qaKirimEmail($email, 'Bapak/Ibu', $subjek, $isi);
    $status = $send['success'] ? 'sent' : 'failed';

    foreach (array_merge($jadwal, $overdue) as $r) {
        $jenis = 'weekly';
        $stmt = $konek->prepare("INSERT INTO qa_email_log
            (alat_id,seksi_pemilik_id,seksi_id,user_uid,email,jenis_email,tanggal_jadwal,periode_mulai,periode_selesai,tanggal_kirim,status,keterangan)
            VALUES (?,?,?,?,?,?,?,?,?,NOW(),?,?)");
        $stmt->bind_param('iiissssssss', $r['alat_id'], $r['seksi_pemilik_id'], $r['seksi_id'], $r['user_uid'], $email, $jenis, $r['jadwal_kalibrasi'], $mulai, $selesai, $status, $send['message']);
        $stmt->execute();
        $stmt->close();
    }
    echo $email . ' => ' . $status . PHP_EOL;
}

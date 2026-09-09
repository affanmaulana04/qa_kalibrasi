<?php
require_once __DIR__ . '/../../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../libs/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../../libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ganti dengan alamat aplikasi yang bisa diakses penerima email.
// Contoh produksi: https://server-qa/Toto_QA/modul/qa/report_qa.php?print=1
if (!defined('QA_REPORT_URL')) {
    define('QA_REPORT_URL', 'http://localhost/Toto_QA/modul/qa/report_qa.php?print=1');
}

function qaKirimEmail($email, $namaPenerima, $subjek, $isiHtml) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = ''; // isi email pengirim perusahaan
        $mail->Password = ''; // isi App Password SMTP
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($mail->Username ?: 'noreply@domain.com', 'Sistem QA');
        $mail->addAddress($email, $namaPenerima ?: $email);
        $mail->isHTML(true);
        $mail->Subject = $subjek;
        $mail->Body = $isiHtml;
        $mail->send();
        return ['success' => true, 'message' => 'Email berhasil dikirim.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $mail->ErrorInfo ?: $e->getMessage()];
    }
}

function qaFormatTanggal($tanggal) {
    if (!$tanggal) return '-';
    $ts = strtotime($tanggal);
    return $ts ? date('d.m.Y', $ts) : $tanggal;
}

function qaEsc($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function qaStatusEmail($selisihHari) {
    $selisihHari = (int)$selisihHari;
    if ($selisihHari < 0) return ['label' => 'Jadwal Terlewat', 'bg' => '#f7b9bd'];
    if ($selisihHari === 0) return ['label' => 'Jadwal Hari Ini', 'bg' => '#c9e8c4'];
    if ($selisihHari <= 7) return ['label' => 'Jadwal Minggu Ini', 'bg' => '#ffe9a6'];
    return ['label' => 'Terjadwal', 'bg' => '#b9d9f4'];
}

function qaTabelEmail($rows, $nomorAwal = 1) {
    $html = '<table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;width:100%;font-family:Arial,sans-serif;font-size:13px;color:#222;border-color:#6d7378;">';
    $html .= '<thead><tr style="background:#e7eaed;text-align:center;">';
    $html .= '<th>No</th><th>Seksi Pemilik</th><th>No. Alat</th><th>Nama Alat</th><th>Tanggal Jadwal</th><th>Status</th>';
    $html .= '</tr></thead><tbody>';
    if (!$rows) {
        $html .= '<tr><td colspan="6" style="text-align:center;padding:15px;">Tidak ada data.</td></tr>';
    } else {
        $lastOwner = '';
        $no = $nomorAwal;
        foreach ($rows as $r) {
            $owner = $r['kode_pemilik'] ?? ($r['nama_seksi'] ?? '-');
            $ownerKey = strtoupper((string)$owner);
            if ($ownerKey !== $lastOwner) {
                $html .= '<tr style="background:#eeeeee;font-weight:bold;"><td colspan="6">SEKSI PEMILIK: ' . qaEsc($owner) . '</td></tr>';
                $lastOwner = $ownerKey;
            }
            $status = qaStatusEmail($r['selisih_hari'] ?? 99);
            $html .= '<tr>';
            $html .= '<td style="text-align:center;">' . $no++ . '</td>';
            $html .= '<td>' . qaEsc($owner) . '</td>';
            $html .= '<td>' . qaEsc($r['nomor_alat']) . '</td>';
            $html .= '<td>' . qaEsc($r['nama_alat']) . '</td>';
            $html .= '<td style="text-align:center;">' . qaFormatTanggal($r['jadwal_kalibrasi']) . '</td>';
            $html .= '<td style="text-align:center;font-weight:bold;background:' . $status['bg'] . ';">' . qaEsc($status['label']) . '</td>';
            $html .= '</tr>';
        }
    }
    $html .= '</tbody></table>';
    return $html;
}

function qaLegendEmail() {
    return '<div style="margin-top:18px;font-family:Arial,sans-serif;font-size:12px;border:1px solid #6d7378;padding:12px;">'
        . '<strong>Keterangan Warna:</strong> '
        . '<span style="display:inline-block;background:#b9d9f4;padding:4px 8px;border:1px solid #777;margin-left:8px;">Terjadwal</span> '
        . '<span style="display:inline-block;background:#ffe9a6;padding:4px 8px;border:1px solid #777;margin-left:6px;">Segera Dilakukan</span> '
        . '<span style="display:inline-block;background:#c9e8c4;padding:4px 8px;border:1px solid #777;margin-left:6px;">Jadwal Hari Ini</span> '
        . '<span style="display:inline-block;background:#f7b9bd;padding:4px 8px;border:1px solid #777;margin-left:6px;">Jadwal Terlewat</span>'
        . '</div>';
}

function qaTemplateEmail($sapaan, $subjek, $intro, $jadwal, $terlewat = []) {
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;color:#333;line-height:1.5;margin:0;padding:20px;background:#f4f6f9;">';
    $html .= '<div style="max-width:1100px;margin:auto;background:#fff;padding:22px;">';
    $html .= '<div style="font-size:13px;margin-bottom:8px;">Email: ' . qaEsc(date('d.m.Y')) . '</div>';
    $html .= '<h2 style="margin:0 0 14px;text-align:center;font-size:24px;">DAFTAR KALIBRASI ALAT</h2>';
    $html .= '<p>Yth. ' . qaEsc($sapaan ?: 'Bapak/Ibu') . ',</p><p>' . qaEsc($intro) . '</p>';

    $html .= '<h3 style="margin:18px 0 8px;">Jadwal Kalibrasi 7 Hari Ke Depan</h3>';
    $html .= qaTabelEmail($jadwal);

    if ($terlewat) {
        $html .= '<h3 style="margin:24px 0 8px;">Jadwal Kalibrasi yang Terlewat</h3>';
        $html .= qaTabelEmail($terlewat);
    }

    $html .= qaLegendEmail();
    $html .= '<div style="text-align:right;margin-top:20px;">'
        . '<a href="' . qaEsc(QA_REPORT_URL) . '" style="display:inline-block;background:#1469EA;color:#fff;text-decoration:none;padding:10px 18px;border-radius:4px;font-weight:bold;">Buka &amp; Cetak Daftar Kalibrasi</a>'
        . '</div>';
    $html .= '<p style="margin-top:22px;color:#777;font-size:12px;">Email ini dikirim otomatis oleh Sistem QA.</p>';
    $html .= '</div></body></html>';
    return $html;
}

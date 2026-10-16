<?php

require_once __DIR__ . '/../../../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../../libs/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../../../libs/PHPMailer/src/Exception.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// ============================================================
// REPORT URL
// ============================================================

if (!defined('QA_REPORT_URL')) {
    $qaReportUrl = trim((string)getenv('QA_REPORT_URL'));

    define(
        'QA_REPORT_URL',
        $qaReportUrl !== ''
            ? $qaReportUrl
            : 'http://localhost/qa_kalibrasi/modul/qa/laporan_hasil/report_qa.php?print=1'
    );
}


/**
 * Membentuk URL cetak. Jika token tersedia, report_qa.php akan
 * membaca snapshot email terkait, bukan data qa_part terbaru.
 *
 * @param string $printToken
 * @return string
 */
function qaBuildReportUrl($printToken = '')
{
    $url = QA_REPORT_URL;
    $printToken = trim((string)$printToken);

    if ($printToken === '') {
        return $url;
    }

    $separator = strpos($url, '?') === false ? '?' : '&';

    return $url
        . $separator
        . 'token='
        . rawurlencode($printToken);
}


// ============================================================
// KIRIM EMAIL
// ============================================================

/**
 * Mengirim email QA.
 *
 * Parameter $cc bersifat opsional agar pemanggilan lama (mis. reminder
 * mingguan) tetap kompatibel. Format $cc yang didukung:
 * - ['email1@contoh.com', 'email2@contoh.com']
 * - [['email' => 'email1@contoh.com', 'name' => 'Nama 1'], ...]
 *
 * @param string $email
 * @param string $namaPenerima
 * @param string $subjek
 * @param string $isiHtml
 * @param array<int,mixed>|string|null $cc
 * @return array{success:bool,message:string}
 */
function qaKirimEmail(
    $email,
    $namaPenerima,
    $subjek,
    $isiHtml,
    $cc = []
) {

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = trim((string)(getenv('QA_SMTP_USER') ?: 'informan.toto@gmail.com'));

        $smtpPassword = trim((string)getenv('QA_SMTP_PASSWORD'));
        if ($smtpPassword === '') {
            throw new Exception('QA_SMTP_PASSWORD belum dikonfigurasi.');
        }

        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($mail->Username, 'Sistem QA');
        $mail->addAddress($email, $namaPenerima ?: $email);

        // CC opsional. Jika kosong, perilaku sama seperti versi sebelumnya.
        if (!is_array($cc)) {
            $cc = trim((string)$cc) !== '' ? [(string)$cc] : [];
        }

        $seenCc = [];
        $toNormalized = strtolower(trim((string)$email));

        foreach ($cc as $ccItem) {
            $ccEmail = '';
            $ccName = '';

            if (is_array($ccItem)) {
                $ccEmail = trim((string)($ccItem['email'] ?? ''));
                $ccName = trim((string)($ccItem['name'] ?? ''));
            } else {
                $ccEmail = trim((string)$ccItem);
            }

            if ($ccEmail === '' || !filter_var($ccEmail, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $normalized = strtolower($ccEmail);
            if ($normalized === $toNormalized || isset($seenCc[$normalized])) {
                continue;
            }

            $seenCc[$normalized] = true;
            $mail->addCC($ccEmail, $ccName ?: $ccEmail);
        }

        $mail->isHTML(true);
        $mail->Subject = $subjek;
        $mail->Body = $isiHtml;
        $mail->send();

        return [
            'success' => true,
            'message' => 'Email berhasil dikirim.'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $mail->ErrorInfo ?: $e->getMessage()
        ];
    }
}


// ============================================================
// FORMAT TANGGAL
// ============================================================

/**
 * @param mixed $tanggal
 * @return string
 */
function qaFormatTanggal($tanggal)
{
    if (
        !$tanggal
        || $tanggal === '0000-00-00'
    ) {

        return '-';
    }


    $ts =
        strtotime($tanggal);


    return $ts
        ? date('d.m.Y', $ts)
        : '-';
}


/**
 * Format tanggal untuk teks email, contoh: 28 September 2026.
 *
 * @param mixed $tanggal
 * @return string
 */
function qaFormatTanggalIndonesia($tanggal)
{
    if (!$tanggal || $tanggal === '0000-00-00') {
        return '-';
    }

    $ts = strtotime((string)$tanggal);
    if (!$ts) {
        return '-';
    }

    $bulan = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];

    return date('d', $ts)
        . ' '
        . $bulan[(int)date('n', $ts)]
        . ' '
        . date('Y', $ts);
}


// ============================================================
// ESCAPE HTML
// ============================================================

/**
 * @param mixed $value
 * @return string
 */
function qaEsc($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


// ============================================================
// STATUS EMAIL
// ============================================================

/**
 * @param int|string $selisihHari
 * @return array{label:string,bg:string,color:string,border:string}
 */
function qaStatusEmail($selisihHari)
{
    $selisihHari = (int)$selisihHari;

    if ($selisihHari < 0) {
        return [
            'label' => 'Terlewat',
            'bg' => '#FDECEC',
            'color' => '#A12626',
            'border' => '#E6B9B9'
        ];
    }

    if ($selisihHari === 0) {
        return [
            'label' => 'Hari Ini',
            'bg' => '#EAF5EA',
            'color' => '#236B32',
            'border' => '#B7D8BC'
        ];
    }

    return [
        'label' => '⏱ Terjadwal',
        'bg' => '#EEF5FC',
        'color' => '#24598B',
        'border' => '#BED2E7'
    ];
}


// ============================================================
// TABEL EMAIL CORPORATE
// ============================================================

/**
 * @param array<int,array<string,mixed>> $rows
 * @param int $nomorAwal
 * @param bool $overdueSection
 * @return string
 */
function qaTabelEmail(
    $rows,
    $nomorAwal = 1,
    $overdueSection = false
) {
    $html =
        '<table role="presentation" cellpadding="0" cellspacing="0" border="0" '
        . 'style="border-collapse:collapse;width:100%;font-family:Arial,Helvetica,sans-serif;'
        . 'font-size:12px;color:#33475B;border:1px solid #C9D2DC;">';

    $html .=
        '<thead><tr style="background:#E9EEF3;color:#26384C;">'
        . '<th style="border:1px solid #C9D2DC;padding:9px 6px;text-align:center;width:5%;">No</th>'
        . '<th style="border:1px solid #C9D2DC;padding:9px 7px;text-align:center;width:17%;">Nomor Alat</th>'
        . '<th style="border:1px solid #C9D2DC;padding:9px 7px;text-align:center;">Nama Alat</th>'
        . '<th style="border:1px solid #C9D2DC;padding:9px 7px;text-align:center;width:15%;">Seksi Pemilik</th>'
        . '<th style="border:1px solid #C9D2DC;padding:9px 7px;text-align:center;width:17%;">Jadwal Kalibrasi</th>'
        . '<th style="border:1px solid #C9D2DC;padding:9px 7px;text-align:center;width:16%;">STATUS</th>'
        . '</tr></thead><tbody>';

    if (!$rows) {
        $html .=
            '<tr><td colspan="6" style="border:1px solid #C9D2DC;padding:16px;text-align:center;color:#6E7B88;">'
            . ($overdueSection ? 'Tidak ada jadwal terlewat.' : 'Tidak ada jadwal pada periode ini.')
            . '</td></tr>';
    } else {
        $lastOwner = '';
        $no = $nomorAwal;

        foreach ($rows as $r) {
            $owner = trim((string)($r['kode_pemilik'] ?? '-'));
            if ($owner === '') {
                $owner = '-';
            }

            $ownerKey = strtoupper($owner);

            if ($ownerKey !== $lastOwner) {
                $html .=
                    '<tr><td colspan="6" style="border:1px solid #C9D2DC;padding:7px 9px;'
                    . 'background:#F5F7F9;color:#405164;font-weight:700;font-size:11px;">'
                    . 'SEKSI PEMILIK: ' . qaEsc($owner)
                    . '</td></tr>';
                $lastOwner = $ownerKey;
            }

            $status = qaStatusEmail($r['selisih_hari'] ?? ($overdueSection ? -1 : 99));

            $html .= '<tr>'
                . '<td style="border:1px solid #C9D2DC;padding:8px 6px;text-align:center;">' . $no++ . '</td>'
                . '<td style="border:1px solid #C9D2DC;padding:8px 7px;font-weight:700;">'
                . qaEsc($r['nomor_alat'] ?? '-') . '</td>'
                . '<td style="border:1px solid #C9D2DC;padding:8px 7px;">'
                . qaEsc($r['nama_alat'] ?? '-') . '</td>'
                . '<td style="border:1px solid #C9D2DC;padding:8px 7px;">'
                . qaEsc($owner) . '</td>'
                . '<td style="border:1px solid #C9D2DC;padding:8px 7px;text-align:center;white-space:nowrap;">'
                . qaFormatTanggal($r['jadwal_kalibrasi'] ?? '') . '</td>'
                . '<td style="border:1px solid #C9D2DC;padding:8px 7px;text-align:center;">'
                . '<span style="display:inline-block;padding:4px 7px;background-color:' . $status['bg']
                . ';color:' . $status['color'] . ';border:1px solid ' . $status['border']
                . ';font-weight:700;font-size:11px;line-height:16px;white-space:nowrap;">'
                . qaEsc($status['label']) . '</span>'
                . '</td>'
                . '</tr>';
        }
    }

    $html .= '</tbody></table>';

    return $html;
}


// ============================================================
// LEGEND CORPORATE
// ============================================================

function qaLegendEmail()
{
    return
        '<table role="presentation" cellpadding="0" cellspacing="0" border="0" '
        . 'style="margin-top:14px;font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#536273;">'
        . '<tr>'
        . '<td style="padding-right:12px;font-weight:700;color:#405164;">Keterangan:</td>'
        . '<td style="padding-right:12px;"><span style="display:inline-block;width:9px;height:9px;'
        . 'background:#EEF5FC;border:1px solid #BED2E7;margin-right:5px;vertical-align:middle;"></span>Biru = H+1 dst</td>'
        . '<td style="padding-right:12px;"><span style="display:inline-block;width:9px;height:9px;'
        . 'background:#EAF5EA;border:1px solid #B7D8BC;margin-right:5px;vertical-align:middle;"></span>Hijau = Hari ini</td>'
        . '<td><span style="display:inline-block;width:9px;height:9px;'
        . 'background:#FDECEC;border:1px solid #E6B9B9;margin-right:5px;vertical-align:middle;"></span>Merah = Terlewat</td>'
        . '</tr></table>';
}


// ============================================================
// TEMPLATE EMAIL CORPORATE
// ============================================================

/**
 * @param string $sapaan
 * @param string $subjek
 * @param string $intro
 * @param array<int,array<string,mixed>> $jadwal
 * @param array<int,array<string,mixed>> $terlewat
 * @param string $printToken
 * @param string $periodeMulai Format Y-m-d
 * @param string $periodeSelesai Format Y-m-d
 * @return string
 */
function qaTemplateEmail(
    $sapaan,
    $subjek,
    $intro,
    $jadwal,
    $terlewat = [],
    $printToken = '',
    $periodeMulai = '',
    $periodeSelesai = ''
) {
    $printUrl = qaBuildReportUrl($printToken);

    $jumlahJadwal = count($jadwal);
    $jumlahTerlewat = count($terlewat);

    if ($periodeMulai !== '' && $periodeSelesai !== '') {
        $periodeText =
            qaFormatTanggalIndonesia($periodeMulai)
            . ' s.d. '
            . qaFormatTanggalIndonesia($periodeSelesai);
    } else {
        $periodeText = '7 hari ke depan';
    }

    $html =
        '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1.0"></head>'
        . '<body style="margin:0;padding:0;background:#F3F5F7;font-family:Arial,Helvetica,sans-serif;color:#27384A;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" '
        . 'style="background:#F3F5F7;padding:24px 10px;"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" '
        . 'style="max-width:980px;background:#FFFFFF;border:1px solid #D9E0E7;">'

        // Header
        . '<tr><td style="padding:0;background:#123B6D;height:6px;font-size:0;line-height:0;">&nbsp;</td></tr>'
        . '<tr><td style="padding:20px 28px 18px;border-bottom:1px solid #D9E0E7;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
        . '<td style="vertical-align:middle;">'
        . '<div style="font-size:28px;line-height:30px;font-weight:800;letter-spacing:-1px;color:#12529A;">TOTO</div>'
        . '<div style="margin-top:3px;font-size:12px;color:#6B7785;">Sistem QA Kalibrasi</div>'
        . '</td>'
        . '<td align="right" style="vertical-align:middle;">'
        . '<div style="font-size:17px;font-weight:700;color:#26384C;">Reminder Kalibrasi QA</div>'
        . '<div style="margin-top:6px;"><span style="display:inline-block;padding:5px 9px;'
        . 'border:1px solid #AFC2D6;background:#F3F7FB;color:#315E88;font-size:11px;font-weight:700;">'
        . 'JADWAL MINGGU INI</span></div>'
        . '</td></tr></table></td></tr>'

        // Greeting
        . '<tr><td style="padding:24px 28px 12px;">'
        . '<p style="margin:0 0 12px;font-size:15px;line-height:22px;font-weight:700;color:#24364B;">'
        . 'Yth. ' . qaEsc($sapaan ?: 'Bapak/Ibu') . ',</p>'
        . '<p style="margin:0;font-size:14px;line-height:22px;color:#435466;">'
        . qaEsc($intro) . '</p>'
        . '</td></tr>'

        // Summary
        . '<tr><td style="padding:8px 28px 20px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" '
        . 'style="border:1px solid #D9E0E7;background:#FAFBFC;">'
        . '<tr>'
        . '<td width="42%" style="padding:13px 16px;border-right:1px solid #D9E0E7;">'
        . '<div style="font-size:11px;color:#6E7B88;text-transform:uppercase;letter-spacing:.3px;">Periode</div>'
        . '<div style="margin-top:4px;font-size:13px;font-weight:700;color:#26384C;">'
        . qaEsc($periodeText) . '</div></td>'
        . '<td width="29%" style="padding:13px 16px;border-right:1px solid #D9E0E7;">'
        . '<div style="font-size:11px;color:#6E7B88;text-transform:uppercase;letter-spacing:.3px;">Jadwal minggu ini</div>'
        . '<div style="margin-top:4px;font-size:17px;font-weight:700;color:#26384C;">'
        . $jumlahJadwal . ' alat</div></td>'
        . '<td width="29%" style="padding:13px 16px;">'
        . '<div style="font-size:11px;color:#6E7B88;text-transform:uppercase;letter-spacing:.3px;">Jadwal terlewat</div>'
        . '<div style="margin-top:4px;font-size:17px;font-weight:700;color:'
        . ($jumlahTerlewat > 0 ? '#A12626' : '#26384C') . ';">'
        . $jumlahTerlewat . ' alat</div></td>'
        . '</tr></table></td></tr>'

        // Scheduled section
        . '<tr><td style="padding:0 28px 8px;">'
        . '<div style="font-size:13px;font-weight:700;color:#26384C;margin-bottom:8px;">Jadwal Kalibrasi 7 Hari</div>'
        . qaTabelEmail($jadwal, 1, false)
        . '</td></tr>';

    if ($terlewat) {
        $html .=
            '<tr><td style="padding:16px 28px 8px;">'
            . '<div style="font-size:13px;font-weight:700;color:#8E2A2A;margin-bottom:8px;">Jadwal Terlewat</div>'
            . qaTabelEmail($terlewat, 1, true)
            . '</td></tr>';
    }

    $html .=
        '<tr><td style="padding:6px 28px 10px;">'
        . qaLegendEmail()
        . '</td></tr>'

        // CTA
        . '<tr><td style="padding:10px 28px 24px;text-align:right;">'
        . '<a href="' . qaEsc($printUrl) . '" style="display:inline-block;background:#123B6D;color:#FFFFFF;'
        . 'text-decoration:none;font-size:13px;font-weight:700;padding:11px 17px;border:1px solid #123B6D;">'
        . 'Cetak Tabel</a></td></tr>'

        // Footer
        . '<tr><td style="padding:16px 28px 20px;border-top:1px solid #D9E0E7;background:#FAFBFC;">'
        . '<p style="margin:0;font-size:12px;line-height:18px;color:#4F5F70;">Terima kasih,<br>'
        . '<b>Sistem QA Kalibrasi</b></p>'
        . '<p style="margin:8px 0 0;font-size:10px;line-height:15px;color:#7B8794;">'
        . 'Tombol Cetak Tabel menggunakan snapshot data pada saat email ini dikirim.</p>'
        . '<p style="margin:4px 0 0;font-size:10px;color:#9AA3AC;">'
        . 'Email dikirim otomatis oleh Sistem QA.</p>'
        . '</td></tr>'

        . '</table></td></tr></table></body></html>';

    return $html;
}

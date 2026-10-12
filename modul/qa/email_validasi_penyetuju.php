<?php

/**
 * Template email validasi QA - Corporate TOTO.
 * Fokus: ringkas, formal, minim dekorasi, dan aman untuk email client.
 */

if (!function_exists('qaValidasiEmailEsc')) {
    function qaValidasiEmailEsc($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('qaValidasiTanggal')) {
    function qaValidasiTanggal($value)
    {
        if (!$value || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return '-';
        }

        $ts = strtotime((string)$value);
        return $ts ? date('d.m.Y', $ts) : '-';
    }
}

if (!function_exists('qaValidasiTanggalIndonesia')) {
    function qaValidasiTanggalIndonesia($value)
    {
        $ts = strtotime((string)$value);
        if (!$ts) {
            return '-';
        }

        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    }
}

if (!function_exists('qaValidasiSapaanNama')) {
    function qaValidasiSapaanNama($sapaan, $nama)
    {
        $sapaan = trim((string)$sapaan);
        $nama = trim((string)$nama);

        if ($sapaan === '') {
            $sapaan = 'Bpk.';
        }

        return trim($sapaan . ' ' . $nama);
    }
}

if (!function_exists('qaValidasiHasilStyle')) {
    function qaValidasiHasilStyle($raw)
    {
        $hasil = strtoupper(trim((string)$raw));

        if ($hasil === '') {
            return ['-', '#EEF1F4', '#536273'];
        }

        if (in_array($hasil, ['OK', 'GOOD'], true)) {
            return ['OK', '#E7F3EA', '#216E39'];
        }

        if (in_array($hasil, ['NOT GOOD', 'BAD', 'RUSAK'], true)) {
            return [$hasil === 'BAD' ? 'NOT GOOD' : $hasil, '#FCE8E8', '#A92525'];
        }

        if ($hasil === 'DISCONTINUE') {
            return ['DISCONTINUE', '#F1F1F1', '#565656'];
        }

        if ($hasil === 'DITARIK MECHANICAL') {
            return ['DITARIK MECHANICAL', '#F8EDE4', '#8A4B18'];
        }

        return [$hasil, '#EEF1F4', '#536273'];
    }
}


/**
 * @param array<string,mixed> $penerima
 * @param array<int,array<string,mixed>> $rows
 * @param string $magicLoginUrl
 * @param string $tanggalKirim Format Y-m-d
 * @param bool $isCcInformasi
 * @param string $namaPenerimaUtama
 * @return string
 */
function qaTemplateEmailValidasiPenyetuju(
    $penerima,
    $rows,
    $magicLoginUrl,
    $tanggalKirim,
    $isCcInformasi = false,
    $namaPenerimaUtama = ''
)
{
    $nama = trim((string)($penerima['uname'] ?? ''));
    $sapaan = trim((string)($penerima['sapaan'] ?? ''));
    $namaTujuan = qaValidasiSapaanNama($sapaan, $nama);
    $jumlah = count($rows);
    $periode = qaValidasiTanggalIndonesia($tanggalKirim);

    $html = '<!DOCTYPE html>'
        . '<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>'
        . '<body style="margin:0;padding:0;background:#F3F5F7;font-family:Arial,Helvetica,sans-serif;color:#27384A;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#F3F5F7;padding:24px 10px;">'
        . '<tr><td align="center">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:900px;background:#FFFFFF;border:1px solid #D9E0E7;">'

        // Corporate header
        . '<tr><td style="padding:0;background:#123B6D;height:6px;font-size:0;line-height:0;">&nbsp;</td></tr>'
        . '<tr><td style="padding:20px 28px 18px;border-bottom:1px solid #D9E0E7;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr>'
        . '<td style="vertical-align:middle;">'
        . '<div style="font-size:28px;line-height:30px;font-weight:800;letter-spacing:-1px;color:#12529A;">TOTO</div>'
        . '<div style="margin-top:3px;font-size:12px;color:#6B7785;">Sistem QA Kalibrasi</div>'
        . '</td>'
        . '<td align="right" style="vertical-align:middle;">'
        . '<div style="font-size:17px;font-weight:700;color:#26384C;">Reminder Validasi QA</div>'
        . '<div style="margin-top:6px;"><span style="display:inline-block;padding:5px 9px;border:1px solid #D6B17E;background:#FFF8ED;color:#8A5712;font-size:11px;font-weight:700;">MENUNGGU PERSETUJUAN</span></div>'
        . '</td>'
        . '</tr></table>'
        . '</td></tr>'

        // Greeting
        . '<tr><td style="padding:24px 28px 12px;">'
        . '<p style="margin:0 0 12px;font-size:15px;line-height:22px;font-weight:700;color:#24364B;">Yth. ' . qaValidasiEmailEsc($namaTujuan) . ',</p>'
        . ($isCcInformasi
            ? '<p style="margin:0;font-size:14px;line-height:22px;color:#435466;">Dengan hormat, email ini merupakan salinan informasi (CC) mengenai transaksi QA yang sedang menunggu persetujuan oleh <b>' . qaValidasiEmailEsc($namaPenerimaUtama) . '</b>.</p>'
            : '<p style="margin:0;font-size:14px;line-height:22px;color:#435466;">Dengan hormat, berikut kami sampaikan daftar transaksi hasil pengecekan alat yang telah diperiksa dan menunggu persetujuan Anda.</p>')
        . '</td></tr>'

        // Compact summary
        . '<tr><td style="padding:8px 28px 18px;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #D9E0E7;background:#FAFBFC;">'
        . '<tr>'
        . '<td width="50%" style="padding:13px 16px;border-right:1px solid #D9E0E7;">'
        . '<div style="font-size:11px;color:#6E7B88;text-transform:uppercase;letter-spacing:.3px;">Transaksi pending</div>'
        . '<div style="margin-top:3px;font-size:17px;font-weight:700;color:#26384C;">' . $jumlah . ' transaksi</div>'
        . '</td>'
        . '<td width="50%" style="padding:13px 16px;">'
        . '<div style="font-size:11px;color:#6E7B88;text-transform:uppercase;letter-spacing:.3px;">Tanggal reminder</div>'
        . '<div style="margin-top:3px;font-size:14px;font-weight:700;color:#26384C;">' . qaValidasiEmailEsc($periode) . '</div>'
        . '</td>'
        . '</tr></table>'
        . '</td></tr>'

        // Table
        . '<tr><td style="padding:0 28px 20px;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;border:1px solid #C9D2DC;font-size:12px;color:#33475B;">'
        . '<thead><tr style="background:#E9EEF3;color:#26384C;">'
        . '<th style="border:1px solid #C9D2DC;padding:9px 6px;text-align:center;">No</th>'
        . '<th style="border:1px solid #C9D2DC;padding:9px 6px;text-align:center;">No. Pengecekan</th>'
        . '<th style="border:1px solid #C9D2DC;padding:9px 6px;text-align:center;">No. Part</th>'
        . '<th style="border:1px solid #C9D2DC;padding:9px 6px;text-align:center;">Nama Alat</th>'
        . '<th style="border:1px solid #C9D2DC;padding:9px 6px;text-align:center;">Seksi Pemilik</th>'
        . '<th style="border:1px solid #C9D2DC;padding:9px 6px;text-align:center;">Tanggal QA</th>'
        . '<th style="border:1px solid #C9D2DC;padding:9px 6px;text-align:center;">Hasil QA</th>'
        . '</tr></thead><tbody>';

    if (!$rows) {
        $html .= '<tr><td colspan="7" style="border:1px solid #C9D2DC;padding:16px;text-align:center;color:#6E7B88;">Tidak ada transaksi yang menunggu persetujuan.</td></tr>';
    } else {
        $no = 1;
        foreach ($rows as $row) {
            [$hasilLabel, $hasilBg, $hasilColor] = qaValidasiHasilStyle($row['status_kalibrasi'] ?? '');

            $html .= '<tr>'
                . '<td style="border:1px solid #C9D2DC;padding:8px 6px;text-align:center;">' . $no++ . '</td>'
                . '<td style="border:1px solid #C9D2DC;padding:8px 7px;">' . qaValidasiEmailEsc($row['nomor_pengecekan'] ?? '-') . '</td>'
                . '<td style="border:1px solid #C9D2DC;padding:8px 7px;">' . qaValidasiEmailEsc($row['no_part'] ?? '-') . '</td>'
                . '<td style="border:1px solid #C9D2DC;padding:8px 7px;">' . qaValidasiEmailEsc($row['nama_part'] ?? '-') . '</td>'
                . '<td style="border:1px solid #C9D2DC;padding:8px 7px;">' . qaValidasiEmailEsc($row['seksi_pemilik'] ?? '-') . '</td>'
                . '<td style="border:1px solid #C9D2DC;padding:8px 7px;text-align:center;white-space:nowrap;">' . qaValidasiTanggal($row['tanggal_pengecekan'] ?? '') . '</td>'
                . '<td style="border:1px solid #C9D2DC;padding:8px 7px;text-align:center;">'
                . '<span style="display:inline-block;padding:4px 8px;background:' . $hasilBg . ';color:' . $hasilColor . ';font-weight:700;">' . qaValidasiEmailEsc($hasilLabel) . '</span>'
                . '</td>'
                . '</tr>';
        }
    }

    $html .= '</tbody></table></td></tr>'

        // Action
        . ($isCcInformasi
            ? '<tr><td style="padding:0 28px 22px;"><div style="padding:11px 13px;border-left:3px solid #9AA7B4;background:#F7F8FA;font-size:12px;line-height:18px;color:#5D6A78;">Salinan CC hanya untuk informasi dan tidak menyertakan tautan login otomatis.</div></td></tr>'
            : '<tr><td style="padding:0 28px 24px;text-align:right;"><a href="' . qaValidasiEmailEsc($magicLoginUrl) . '" style="display:inline-block;background:#123B6D;color:#FFFFFF;text-decoration:none;font-size:13px;font-weight:700;padding:11px 17px;border:1px solid #123B6D;">Buka Transaksi Validasi</a></td></tr>')

        // Footer
        . '<tr><td style="padding:16px 28px 20px;border-top:1px solid #D9E0E7;background:#FAFBFC;">'
        . '<p style="margin:0;font-size:12px;line-height:18px;color:#4F5F70;">Terima kasih,<br><b>Sistem QA Kalibrasi</b></p>'
        . ($isCcInformasi
            ? '<p style="margin:10px 0 0;font-size:10px;line-height:15px;color:#7B8794;">Email ini merupakan salinan informasi. Tidak ada hak login otomatis yang diberikan melalui email ini.</p>'
            : '<p style="margin:10px 0 0;font-size:10px;line-height:15px;color:#7B8794;">Tautan validasi bersifat pribadi dan akan masuk otomatis sebagai akun penerima email. Jangan meneruskan tautan ini kepada pihak lain.</p>')
        . '<p style="margin:5px 0 0;font-size:10px;color:#9AA3AC;">Email dikirim otomatis oleh Sistem QA.</p>'
        . '</td></tr>'
        . '</table></td></tr></table></body></html>';

    return $html;
}

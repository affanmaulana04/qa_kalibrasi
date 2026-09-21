<?php

require_once __DIR__ . '/../../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../libs/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../../libs/PHPMailer/src/Exception.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// ============================================================
// REPORT URL
// ============================================================

if (!defined('QA_REPORT_URL')) {

    define(
        'QA_REPORT_URL',
        'http://localhost/qa_kalibrasi/modul/qa/report_qa.php?print=1'
    );
}


// ============================================================
// KIRIM EMAIL
// ============================================================

function qaKirimEmail(
    $email,
    $namaPenerima,
    $subjek,
    $isiHtml
) {

    $mail = new PHPMailer(true);


    try {

        $mail->isSMTP();

        $mail->Host =
            'smtp.gmail.com';

        $mail->SMTPAuth =
            true;

        $mail->Username =
            'informan.toto@gmail.com';

        $mail->Password =
            'ceny hpih jjth cudn';

        $mail->SMTPSecure =
            'tls';

        $mail->Port =
            587;

        $mail->CharSet =
            'UTF-8';


        $mail->setFrom(
            $mail->Username,
            'Sistem QA'
        );


        $mail->addAddress(
            $email,
            $namaPenerima ?: $email
        );


        $mail->isHTML(true);

        $mail->Subject =
            $subjek;

        $mail->Body =
            $isiHtml;


        $mail->send();


        return [
            'success' => true,
            'message' => 'Email berhasil dikirim.'
        ];


    } catch (Exception $e) {

        return [
            'success' => false,
            'message' =>
                $mail->ErrorInfo
                ?: $e->getMessage()
        ];
    }
}


// ============================================================
// FORMAT TANGGAL
// ============================================================

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


// ============================================================
// ESCAPE HTML
// ============================================================

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

function qaStatusEmail($selisihHari)
{
    $selisihHari =
        (int)$selisihHari;


    // OVERDUE

    if ($selisihHari < 0) {

        return [
            'label' => 'Jadwal Terlewat',
            'bg' => '#f7b9bd'
        ];
    }


    // TODAY

    if ($selisihHari === 0) {

        return [
            'label' => 'Jadwal Hari Ini',
            'bg' => '#c9e8c4'
        ];
    }


    // FUTURE

    return [
        'label' => 'Terjadwal',
        'bg' => '#b9d9f4'
    ];
}


// ============================================================
// TABEL EMAIL
// ============================================================
//
// SEKSI PEMILIK TETAP DIPISAH.
//
// Contoh:
//
// SEKSI PEMILIK: WH
// [table]
//
// SEKSI PEMILIK: Technical
// [table]
//
// SEKSI PEMILIK: QA
// [table]
//
// Tetapi semuanya tetap berada di dalam 1 email.
//

function qaTabelEmail(
    $rows,
    $nomorAwal = 1
) {

    $html =
        '<table cellpadding="6" '
        . 'cellspacing="0" '
        . 'border="1" '
        . 'style="'
        . 'border-collapse:collapse;'
        . 'width:100%;'
        . 'font-family:Arial,sans-serif;'
        . 'font-size:13px;'
        . 'color:#222;'
        . 'border-color:#6d7378;'
        . '">';


    $html .=
        '<thead>'
        . '<tr style="'
        . 'background:#e7eaed;'
        . 'text-align:center;'
        . '">'
        . '<th>No</th>'
        . '<th>Seksi Pemilik</th>'
        . '<th>No. Alat</th>'
        . '<th>Nama Alat</th>'
        . '<th>Tanggal Jadwal</th>'
        . '<th>Status</th>'
        . '</tr>'
        . '</thead>';


    $html .= '<tbody>';


    if (!$rows) {

        $html .=
            '<tr>'
            . '<td colspan="6" '
            . 'style="'
            . 'text-align:center;'
            . 'padding:15px;'
            . '">'
            . 'Tidak ada data.'
            . '</td>'
            . '</tr>';


    } else {


        /*
         * Grouping berdasarkan seksi.
         */

        $lastOwner = '';

        $no = $nomorAwal;


        foreach ($rows as $r) {


            $owner =
                trim(
                    (string)(
                        $r['kode_pemilik']
                        ?? '-'
                    )
                );


            if ($owner === '') {

                $owner = '-';
            }


            $ownerKey =
                strtoupper($owner);


            // =================================================
            // PEMISAH SEKSI
            // =================================================

            if (
                $ownerKey !== $lastOwner
            ) {

                $html .=
                    '<tr style="'
                    . 'background:#eeeeee;'
                    . 'font-weight:bold;'
                    . '">'
                    . '<td colspan="6">'
                    . 'SEKSI PEMILIK: '
                    . qaEsc($owner)
                    . '</td>'
                    . '</tr>';


                $lastOwner =
                    $ownerKey;
            }


            // =================================================
            // STATUS
            // =================================================

            $status =
                qaStatusEmail(
                    $r['selisih_hari'] ?? 99
                );


            // =================================================
            // ROW
            // =================================================

            $html .= '<tr>';


            // NO

            $html .=
                '<td style="text-align:center;">'
                . $no++
                . '</td>';


            // SEKSI

            $html .=
                '<td>'
                . qaEsc($owner)
                . '</td>';


            // NO ALAT

            $html .=
                '<td>'
                . qaEsc(
                    $r['nomor_alat'] ?? '-'
                )
                . '</td>';


            // NAMA ALAT

            $html .=
                '<td>'
                . qaEsc(
                    $r['nama_alat'] ?? '-'
                )
                . '</td>';


            // TANGGAL

            $html .=
                '<td style="text-align:center;">'
                . qaFormatTanggal(
                    $r['jadwal_kalibrasi'] ?? ''
                )
                . '</td>';


            // STATUS

            $html .=
                '<td style="'
                . 'text-align:center;'
                . 'font-weight:bold;'
                . 'background:'
                . $status['bg']
                . ';'
                . '">'
                . qaEsc(
                    $status['label']
                )
                . '</td>';


            $html .= '</tr>';
        }
    }


    $html .=
        '</tbody></table>';


    return $html;
}


// ============================================================
// LEGEND
// ============================================================

function qaLegendEmail()
{

    return

        '<div style="'
        . 'margin-top:18px;'
        . 'font-family:Arial,sans-serif;'
        . 'font-size:12px;'
        . 'border:1px solid #6d7378;'
        . 'padding:12px;'
        . '">'

        . '<strong>Keterangan Warna:</strong> '


        . '<span style="'
        . 'display:inline-block;'
        . 'background:#b9d9f4;'
        . 'padding:4px 8px;'
        . 'border:1px solid #777;'
        . 'margin-left:8px;'
        . '">'
        . 'Terjadwal'
        . '</span>'


        . '<span style="'
        . 'display:inline-block;'
        . 'background:#c9e8c4;'
        . 'padding:4px 8px;'
        . 'border:1px solid #777;'
        . 'margin-left:6px;'
        . '">'
        . 'Jadwal Hari Ini'
        . '</span>'


        . '<span style="'
        . 'display:inline-block;'
        . 'background:#f7b9bd;'
        . 'padding:4px 8px;'
        . 'border:1px solid #777;'
        . 'margin-left:6px;'
        . '">'
        . 'Jadwal Terlewat'
        . '</span>'


        . '</div>';
}


// ============================================================
// TEMPLATE EMAIL
// ============================================================

function qaTemplateEmail(
    $sapaan,
    $subjek,
    $intro,
    $jadwal,
    $terlewat = []
) {

    $html =
        '<!DOCTYPE html>'
        . '<html>'
        . '<head>'
        . '<meta charset="UTF-8">'
        . '</head>'


        . '<body style="'
        . 'font-family:Arial,sans-serif;'
        . 'color:#333;'
        . 'line-height:1.5;'
        . 'margin:0;'
        . 'padding:20px;'
        . 'background:#f4f6f9;'
        . '">';


    $html .=
        '<div style="'
        . 'max-width:1100px;'
        . 'margin:auto;'
        . 'background:#fff;'
        . 'padding:22px;'
        . '">';


    // ========================================================
    // TITLE
    // ========================================================

    $html .=
        '<h2 style="'
        . 'margin:0 0 14px;'
        . 'text-align:center;'
        . 'font-size:24px;'
        . '">'
        . 'DAFTAR KALIBRASI ALAT'
        . '</h2>';


    // ========================================================
    // SAPAAN
    // ========================================================

    $html .=
        '<p>'
        . 'Yth. '
        . qaEsc(
            $sapaan ?: 'Bapak/Ibu'
        )
        . ','
        . '</p>';


    $html .=
        '<p>'
        . qaEsc($intro)
        . '</p>';


    // ========================================================
    // JADWAL 7 HARI
    // ========================================================

    $html .=
        '<h3 style="'
        . 'margin:18px 0 8px;'
        . '">'
        . 'Jadwal Kalibrasi Alat'
        . '</h3>';


    $html .=
        qaTabelEmail(
            $jadwal
        );


    // ========================================================
    // OVERDUE
    // ========================================================

    if ($terlewat) {

        $html .=
            '<h3 style="'
            . 'margin:24px 0 8px;'
            . '">'
            . 'Jadwal Kalibrasi yang Terlewat'
            . '</h3>';


        $html .=
            qaTabelEmail(
                $terlewat
            );
    }


    // ========================================================
    // LEGEND
    // ========================================================

    $html .=
        qaLegendEmail();


    // ========================================================
    // CETAK
    // ========================================================

    $html .=
        '<div style="'
        . 'text-align:right;'
        . 'margin-top:20px;'
        . '">'

        . '<a href="'
        . qaEsc(QA_REPORT_URL)
        . '" '

        . 'style="'
        . 'display:inline-block;'
        . 'background:#1469EA;'
        . 'color:#fff;'
        . 'text-decoration:none;'
        . 'padding:10px 18px;'
        . 'border-radius:4px;'
        . 'font-weight:bold;'
        . '">'

        . 'Cetak Tabel'

        . '</a>'

        . '</div>';


    // ========================================================
    // FOOTER
    // ========================================================

    $html .=
        '<p style="'
        . 'margin-top:22px;'
        . 'color:#777;'
        . 'font-size:12px;'
        . '">'
        . 'Email ini dikirim otomatis oleh Sistem QA.'
        . '</p>';


    $html .=
        '</div>'
        . '</body>'
        . '</html>';


    return $html;
}
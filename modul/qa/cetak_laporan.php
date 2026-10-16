<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Pemeriksaan Gauge</title>
<style>
@page {
    size: A4 portrait;
    margin: 0;
}
@page landscapeA4 {
    size: A4 landscape;
    margin: 0;
}
* { box-sizing: border-box; }
html, body {
    margin: 0;
    padding: 0;
    font-family: Arial, Helvetica, sans-serif;
    color: #000;
    font-size: 8px;
}
.no-print {
    padding: 10px;
    margin-bottom: 8px;
}
.no-print button {
    padding: 8px 18px;
    border: 1px solid #555;
    background: #fff;
    cursor: pointer;
}
.report {
    width: 100%;
}
.top-line {
    border-top: 2px solid #000;
    margin-top: 3px;
}
.bottom-line {
    border-bottom: 2px solid #000;
    margin-bottom: 3px;
}
.company {
    font-size: 11px;
    font-weight: bold;
    margin-bottom: 1px;
}
.title {
    text-align: center;
    font-size: 13px;
    font-weight: bold;
}
.date-box {
    width: 220px;
    text-align: left;
    font-weight: bold;
    font-size: 8px;
}
.header {
    width: 100%;
    border-collapse: collapse;
    margin-top: 4px;
}
.header td {
    border: 1px solid #000;
    vertical-align: top;
    padding: 3px 4px;
}
.info {
    width: 100%;
    border-collapse: collapse;
}
.info td {
    border: 0 !important;
    padding: 1px 2px !important;
    vertical-align: top;
}
.label {
    width: 78px;
    font-weight: bold;
}
.approval {
    width: 24%;
    padding: 0 !important;
}
.approval table {
    width: 100%;
    height: 100%;
    border-collapse: collapse;
}
.approval td {
    border: 1px solid #000 !important;
    text-align: center;
    padding: 2px !important;
}
.approval .sign-space {
    height: 55px;
    vertical-align: bottom;
    font-size: 7px;
    line-height: 1.25;
}
.page-no {
    text-align: right;
    font-weight: bold;
    margin: 2px 0;
}
.main-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
.main-table th,
.main-table td {
    border: 1px solid #000;
    text-align: center;
    vertical-align: middle;
    padding: 2px;
    line-height: 1.08;
    word-wrap: break-word;
}
.main-table th {
    font-weight: bold;
}
 .report td, .report th,
 .report-b td, .report-b th,
 .report-c td, .report-c th,
 .report-d td, .report-d th,
 .report-h td, .report-h th,
 .report-i td, .report-i th,
 .report-j td, .report-j th,
 .report-k td, .report-k th,
 .report-l td, .report-l th,
 .report-m td, .report-m th,
 .report-o td, .report-o th,
 .report-p td, .report-p th,
 .report-q td, .report-q th,
 .report-t td, .report-t th {
     white-space: normal !important;
     word-wrap: break-word !important;
     overflow-wrap: break-word !important;
 }

.left {
    text-align: left !important;
}
.bold {
    font-weight: bold;
}
.ok { color: #000; font-weight: bold; }
.bad { color: #000; font-weight: bold; }
.tiny {
    font-size: 6.5px;
}
.footer-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 4px;
}
.footer-table td,
.footer-table th {
    border: 1px solid #000;
    padding: 3px;
    vertical-align: middle;
}
.sign-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 5px;
}
.sign-table td {
    border: 1px solid #000;
    height: 45px;
    text-align: center;
    vertical-align: bottom;
    padding: 4px;
}

/* ================= FORM B - sesuai lembar Air Driver ================= */
.report-b { width:206mm; min-height:293mm; margin:2mm auto; padding:0 0 2mm; border:0.5mm solid #000; background:#fff; color:#000; font:9pt Arial,sans-serif; }
.report-b * { box-sizing:border-box; }
.company-b { text-align:center; font:bold 9pt "Times New Roman",serif; height:4mm; }
.report-title-b { height:18mm; display:flex; align-items:center; justify-content:center; font:16pt "Times New Roman",serif; }
.info-b,.air-b,.detail-b { width:176mm; margin:0 auto; border-collapse:collapse; table-layout:fixed; }
.info-b td { border:0.25mm solid #000; padding:0.7mm 1mm; height:5.2mm; overflow-wrap:anywhere; }
.info-b .type-row td { height:13mm; }
.info-b .w-no { width:18%; } .info-b .w-data { width:36.5%; } .info-b .w-label2 { width:27.3%; } .info-b .w-data2 { width:18.2%; text-align:center; }
.b-bold { font-weight:bold; }
.hasil-title-b { width:176mm; margin:0 auto; height:6mm; border:0.25mm solid #000; border-top:0; text-align:center; padding:1mm; }
.air-b th,.air-b td { border:0.25mm solid #000; padding:1mm; text-align:center; vertical-align:middle; font-weight:normal; }
.air-b th { font-size:8pt; overflow-wrap:anywhere; }
.air-b thead tr:first-child { height:15mm; } .air-b thead tr:last-child { height:13mm; }
.air-b .c-pressure { width:9.1%; } .air-b .c-handle,.air-b .c-baut { width:18.2%; }
.air-b .c-std,.air-b .c-result { width:9.1%; } .air-b .c-rata,.air-b .c-lulus { width:18.15%; }
.air-b tbody tr { height:13mm; }
.air-b .b-result { border-top-style:dotted; border-bottom-style:dotted; }
.air-b tbody.b-group { break-inside:avoid; page-break-inside:avoid; }
.detail-b td { border:0.25mm solid #000; padding:1mm; vertical-align:middle; overflow-wrap:anywhere; }
.detail-b .d-label { width:27.3%; } .detail-b .d-value { width:27.3%; white-space:pre-line; }
.detail-b .d-label:nth-child(3) { width:27.2%; } .detail-b .d-value:nth-child(4) { width:18.2%; text-align:center; }
.detail-b .standard-row { height:13mm; } .detail-b .trace-row { height:14mm; }
.detail-b .diagonal { padding:0; position:relative; }
.detail-b .diagonal svg { position:absolute; inset:0; width:100%; height:100%; }
.detail-b .ket-box { height:26mm; vertical-align:top; white-space:pre-wrap; }
.city-date-b { width:96mm; margin:0 15mm 0 auto; height:5mm; padding-top:1mm; }
.sign-b { width:96mm; margin:0 15mm 0 auto; border-collapse:collapse; table-layout:fixed; break-inside:avoid; }
.sign-b th,.sign-b td { border:0.25mm solid #000; text-align:center; padding:1mm; font-weight:normal; }
.sign-b th { height:5mm; } .sign-space-b td { height:28mm; vertical-align:bottom; font-style:italic; font-size:8pt; }
.sign-b .roles-b td { height:10mm; }
.doc-code-b { text-align:right; font-size:7pt; margin:3mm 1mm 0; }

/* ================= FORM C - PEMERIKSAAN TEMPERATUR ================= */
.report-c {
    width: 206mm;
    min-height: 293mm;
    margin: 2mm auto;
    border: 0.5mm solid #000;
    background: #fff;
    color: #000;
    font: 9pt Arial, sans-serif;
    padding: 0 0 2mm;
}
.report-c * { box-sizing: border-box; }
.company-c { text-align:center; font:bold 9pt "Times New Roman", serif; height:4mm; }
.report-title-c { height:18mm; display:flex; align-items:center; justify-content:center; font:16pt "Times New Roman", serif; }
.info-c, .result-c, .detail-c { width:176mm; margin:0 auto; border-collapse:collapse; table-layout:fixed; }
.info-c td, .detail-c td { border:0.25mm solid #000; padding:0.7mm 1mm; vertical-align:middle; overflow-wrap:anywhere; }
.info-c .c-label { width:20%; }
.info-c .c-value { width:30%; }
.info-c .c-label-r { width:25%; }
.info-c .c-value-r { width:25%; text-align:center; }
.info-c .row-tall td { height:8mm; }
.hasil-title-c { width:176mm; margin:0 auto; height:6mm; border:0.25mm solid #000; border-top:0; text-align:center; padding:1mm; }
.result-c th, .result-c td { border:0.25mm solid #000; text-align:center; vertical-align:middle; padding:1mm; font-weight:normal; }
.result-c th { height:19mm; font-size:8pt; }
.result-c td { height:7mm; }
.result-c .no-c { width:10%; }
.result-c .sv-c { width:20%; }
.result-c .pv-c { width:20%; }
.result-c .std-c { width:15%; }
.result-c .diff-c { width:18%; }
.result-c .pass-c { width:17%; }
.detail-c .d-label-c { width:20%; }
.detail-c .d-value-c { width:30%; }
.detail-c .d-label-r-c { width:25%; }
.detail-c .d-value-r-c { width:25%; text-align:center; }
.detail-c .ket-c { height:28mm; vertical-align:top; white-space:pre-wrap; }
.detail-c .diagonal-c { padding:0; position:relative; min-height:7mm; }
.detail-c .diagonal-c svg { position:absolute; inset:0; width:100%; height:100%; }
.ok-c { font-weight:bold; }
.bad-c { font-weight:bold; }
.city-date-c { width:96mm; margin:0 15mm 0 auto; height:5mm; padding-top:1mm; }
.sign-c { width:96mm; margin:0 15mm 0 auto; border-collapse:collapse; table-layout:fixed; break-inside:avoid; }
.sign-c th, .sign-c td { border:0.25mm solid #000; text-align:center; padding:1mm; font-weight:normal; }
.sign-c th { height:5mm; }
.sign-c .sign-space-c td { height:28mm; vertical-align:bottom; font-style:italic; font-size:8pt; }
.sign-c .roles-c td { height:10mm; }
/* ================= FORM D - PEMERIKSAAN MULTIMETER ================= */
/* Format D mengikuti layout lembar Excel STIS-CMFG4-05/R4. */
.report-d {
    width:206mm; min-height:293mm; margin:2mm auto; padding:0 0 2mm;
    border:0.5mm solid #000; background:#fff; color:#000;
    font:7.5pt Arial,sans-serif;
}
.report-d * { box-sizing:border-box; }
.company-d { text-align:center; font:bold 8pt Arial,sans-serif; height:5mm; line-height:5mm; }
.report-title-d { width:176mm; height:20mm; margin:0 auto; display:flex; align-items:center; justify-content:center; font:14pt Arial,sans-serif; }
.info-d,.result-d,.detail-d { width:176mm; margin:0 auto; border-collapse:collapse; table-layout:fixed; }
.info-d td,.detail-d td { border:0.25mm solid #000; padding:0.35mm 0.8mm; height:5mm; vertical-align:middle; line-height:1.05; overflow-wrap:anywhere; }
.info-d .d-label { width:17%; } .info-d .d-value { width:33%; }
.info-d .d-label-r { width:33%; } .info-d .d-value-r { width:17%; text-align:center; }
.hasil-title-d { width:176mm; margin:0 auto; height:6mm; border-left:0.25mm solid #000; border-right:0.25mm solid #000; border-bottom:0.25mm solid #000; text-align:center; line-height:6mm; }
.result-d th,.result-d td { border:0.25mm solid #000; text-align:center; vertical-align:middle; padding:0.25mm 0.5mm; height:6mm; line-height:1.05; font-weight:normal; }
.result-d thead th { height:8mm; }
.result-d thead .unit-sub-d th { height:5mm; }
.result-d .group-head-d td { height:5mm; padding:0.15mm 0.4mm; }
.result-d .group-mode-d { vertical-align:middle; }
.result-d .no-col { width:8.5%; } .result-d .mode-col { width:8.5%; }
.result-d .setting-col { width:25%; } .result-d .std-col { width:17%; }
.result-d .hasil-col { width:25%; } .result-d .koreksi-col { width:16%; }
.detail-d .d-lbl { width:17%; } .detail-d .d-val { width:33%; }
.detail-d .d-lbl-r { width:33%; } .detail-d .d-val-r { width:17%; text-align:center; }
.detail-d .ket-d { height:15mm; vertical-align:top; white-space:pre-wrap; }
.city-date-d { width:96mm; margin:0 15mm 0 auto; height:6mm; padding-top:2mm; text-align:left; }
.sign-d { width:96mm; margin:0 15mm 0 auto; border-collapse:collapse; table-layout:fixed; break-inside:avoid; }
.sign-d th,.sign-d td { border:0.25mm solid #000; text-align:center; padding:0.5mm; font-weight:normal; }
.sign-d th { height:5mm; }
.sign-d .sign-space-d td { height:25mm; vertical-align:bottom; font-size:7pt; }
.sign-d .roles-d td { height:5mm; }
.doc-code-d { text-align:right; font-size:6.5pt; margin:3mm 1mm 0; font-weight:bold; }


/* ================= FORM H - THREAD PLUG GAUGE ================= */
.report-h { width:206mm; min-height:293mm; margin:2mm auto; padding:0 0 2mm; border:0.5mm solid #000; background:#fff; color:#000; font:6.5pt Arial,sans-serif; }
.report-h * { box-sizing:border-box; }
.company-h { text-align:center; font:bold 8pt Arial,sans-serif; height:5mm; }
.title-h { text-align:center; font:bold 12pt Arial,sans-serif; padding:1mm 0 1mm; }
.header-h { width:196mm; margin:0 auto; border-collapse:collapse; table-layout:fixed; }
.header-h td { padding:0.2mm 0.5mm; vertical-align:top; }
.header-h .left-h { width:38%; } .header-h .mid-h { width:36.5%; } .header-h .sign-h { width:25.5%; padding:0; }
.sign-h-table { width:100%; border-collapse:collapse; table-layout:fixed; }
.sign-h-table td { border:0.25mm solid #000; text-align:center; padding:0.2mm; line-height:1; }
.sign-h-table .space-h td { height:4mm; vertical-align:bottom; font-size:6pt; }
.page-h { width:196mm; margin:0 auto 0.5mm; text-align:right; font-weight:bold; line-height:1; }
.main-h { width:184mm; margin:0 auto; border-collapse:collapse; table-layout:fixed; }
.main-h th,.main-h td { border:0.25mm solid #000; text-align:center; vertical-align:middle; padding:0.45mm; line-height:1.05; overflow-wrap:anywhere; }
.main-h th { font-weight:bold; }
.main-h .small-h { font-size:5.7pt; }
.main-h tbody tr { height:15mm; }
.ket-h { width:184mm; margin:0 auto; border-collapse:collapse; }
.ket-h td { border:0.25mm solid #000; min-height:14mm; padding:1mm; vertical-align:top; }
.doc-h { text-align:right; font-size:6.5pt; margin:2mm 1mm 0; }

/* ================= FORM I - PEMERIKSAAN GAUGE ================= */
.report-i {
    width:206mm; min-height:293mm; margin:2mm auto; padding:0 0 2mm;
    border:0.5mm solid #000; background:#fff; color:#000; font:8pt Arial,sans-serif;
}
.report-i * { box-sizing:border-box; }
.company-i { text-align:center; font:bold 8.5pt Arial,sans-serif; height:5mm; }
.title-i { text-align:center; font:bold 14pt Arial,sans-serif; padding:5mm 0 3mm; }
.header-i { width:196mm; margin:0 auto; border-collapse:collapse; table-layout:fixed; }
.header-i td { padding:0.5mm 1mm; vertical-align:top; }
.header-i .left-i { width:38%; }
.header-i .mid-i { width:36.5%; }
.header-i .sign-i { width:25.5%; padding:0; }
.sign-head-i { width:100%; border-collapse:collapse; table-layout:fixed; }
.sign-head-i td { border:0.25mm solid #000; text-align:center; padding:0.7mm; }
.sign-head-i .space-i td { height:12mm; vertical-align:bottom; font-size:7pt; }
.page-i { width:196mm; margin:1mm auto 1mm; text-align:right; font-weight:bold; }
.main-i { width:176mm; margin:0 auto; border-collapse:collapse; table-layout:fixed; }
.main-i th,.main-i td { border:0.25mm solid #000; text-align:center; vertical-align:middle; padding:0.8mm; overflow-wrap:anywhere; }
.main-i th { font-weight:bold; }
.main-i .c-no { width:7%; }
.main-i .c-noalat { width:14%; }
.main-i .c-spekalat { width:16%; }
.main-i .c-stdspek { width:18%; }
.main-i .c-stdno { width:10%; }
.main-i .c-lokasi { width:14%; }
.main-i .c-hasil { width:11%; }
.main-i .c-warna { width:10%; }
.main-i .c-putusan { width:9%; }
.main-i .c-next { width:12%; }
.main-i .row-i { height:8mm; }
.main-i .blank-i { height:95mm; vertical-align:top; }
.ket-i { width:176mm; margin:0 auto; border-collapse:collapse; }
.ket-i td { border:0.25mm solid #000; padding:1mm; height:15mm; vertical-align:top; }
.doc-code-i { text-align:right; font-size:7pt; margin:3mm 1mm 0; }


/* ================= FORM J - LAPORAN VERIFIKASI ================= */
.report-j {
    width:206mm;
    min-height:293mm;
    margin:2mm auto;
    padding:0 0 2mm;
    border:0.5mm solid #000;
    background:#fff;
    color:#000;
    font:8pt Arial,sans-serif;
}
.report-j * { box-sizing:border-box; }
.company-j { text-align:center; font-weight:bold; height:5mm; padding-top:1mm; }
.title-j { text-align:center; font:15pt "Times New Roman",serif; padding:5mm 0 4mm; }
.info-j,.hasil-j,.ket-j { width:176mm; margin:0 auto; border-collapse:collapse; table-layout:fixed; }
.info-j td { border:0.25mm solid #000; padding:0.7mm 1mm; height:5mm; vertical-align:middle; }
.info-j .label-j { width:18%; }
.info-j .value-j { width:32%; }
.info-j .label2-j { width:22%; }
.info-j .value2-j { width:28%; }
.hasil-title-j { width:176mm; margin:10mm auto 0; border:0.25mm solid #000; height:5mm; text-align:center; padding:1mm; }
.hasil-j th,.hasil-j td { border:0.25mm solid #000; text-align:center; vertical-align:middle; padding:1mm; }
.hasil-j th { font-weight:normal; }
.hasil-j .data-row-j { height:8mm; }
.hasil-j .blank-row-j td { height:7mm; border-top-style:dotted; border-bottom-style:dotted; }
.ket-j td { border:0; padding:1mm 0; vertical-align:top; }
.legend-j { line-height:1.5; font-weight:bold; }
.city-date-j { width:112mm; margin:7mm 13mm 0 auto; padding:1mm 0; }
.sign-j { width:112mm; margin:0 13mm 0 auto; border-collapse:collapse; table-layout:fixed; }
.sign-j th,.sign-j td { border:0.25mm solid #000; text-align:center; padding:1mm; font-weight:normal; }
.sign-j .sign-space-j td { height:24mm; vertical-align:bottom; font-size:7.5pt; }
.sign-j .role-j td { height:6mm; }
.doc-code-j { text-align:right; font-size:7pt; margin:3mm 1mm 0; }

/* ================= FORM K - VOLTAGE ULTRASONIC ================= */
.report-k{width:206mm;min-height:293mm;margin:2mm auto;padding:0 0 2mm;border:.5mm solid #000;background:#fff;color:#000;font:8pt Arial,sans-serif}.report-k *{box-sizing:border-box}.company-k{text-align:center;font-weight:bold;height:5mm;padding-top:1mm}.title-k{text-align:center;font:14pt "Times New Roman",serif;padding:5mm 0 4mm}.info-k,.hasil-k,.detail-k{width:176mm;margin:0 auto;border-collapse:collapse;table-layout:fixed}.info-k td{border:.25mm solid #000;padding:.7mm 1mm;height:5mm;vertical-align:middle}.hasil-title-k{width:176mm;margin:0 auto;border:.25mm solid #000;border-top:0;height:5mm;text-align:center;padding:1mm}.hasil-k th,.hasil-k td{border:.25mm solid #000;text-align:center;vertical-align:middle;padding:1mm}.hasil-k th{font-weight:normal}.hasil-k tbody tr{height:8mm}.hasil-k .blank-k td{height:28mm}.detail-k td{border:.25mm solid #000;padding:.8mm 1mm;vertical-align:middle}.detail-k .ket-k{height:16mm;vertical-align:top}.city-date-k{width:112mm;margin:7mm 13mm 0 auto;padding:1mm 0}.sign-k{width:112mm;margin:0 13mm 0 auto;border-collapse:collapse;table-layout:fixed}.sign-k th,.sign-k td{border:.25mm solid #000;text-align:center;padding:1mm;font-weight:normal}.sign-k .sign-space-k td{height:24mm;vertical-align:bottom;font-size:7.5pt}.sign-k .role-k td{height:6mm}.doc-code-k{text-align:right;font-size:7pt;margin:3mm 1mm 0}


/* ================= FORM L - PRESSURE GAUGE ================= */
.report-l{width:206mm;min-height:293mm;margin:2mm auto;padding:0 0 2mm;border:.5mm solid #000;background:#fff;color:#000;font:8pt Arial,sans-serif}
.report-l *{box-sizing:border-box}.company-l{text-align:center;font-weight:bold;height:5mm;padding-top:1mm}.title-l{text-align:center;font:15pt "Times New Roman",serif;padding:5mm 0 4mm}
.info-l,.hasil-l,.detail-l{width:176mm;margin:0 auto;border-collapse:collapse;table-layout:fixed}.info-l td,.detail-l td{border:.25mm solid #000;padding:.7mm 1mm;height:5mm;vertical-align:middle}
.hasil-title-l{width:176mm;margin:0 auto;border:.25mm solid #000;border-top:0;height:5mm;text-align:center;padding:1mm}.hasil-l th,.hasil-l td{border:.25mm solid #000;text-align:center;vertical-align:middle;padding:.9mm}.hasil-l tbody tr{height:6.8mm}
.detail-l .ket-l{height:17mm;vertical-align:top}.city-date-l{width:112mm;margin:7mm 13mm 0 auto;padding:1mm 0}.sign-l{width:112mm;margin:0 13mm 0 auto;border-collapse:collapse;table-layout:fixed}
.sign-l th,.sign-l td{border:.25mm solid #000;text-align:center;padding:1mm;font-weight:normal}.sign-l .sign-space-l td{height:24mm;vertical-align:bottom;font-size:7.5pt}.sign-l .role-l td{height:6mm}.doc-code-l{text-align:right;font-size:7pt;margin:3mm 1mm 0}


/* ================= FORM Q - SERATION / PLUG GAUGE ================= */
.report-q { width:206mm; min-height:293mm; margin:2mm auto; padding:0 0 2mm; border:0.5mm solid #000; background:#fff; color:#000; font:6.7pt Arial,sans-serif; }
.report-q * { box-sizing:border-box; }
.company-q { text-align:center; font-weight:bold; height:5mm; padding-top:1mm; }
.top-q { width:196mm; margin:0 auto 1mm; display:grid; grid-template-columns:146mm 50mm; align-items:stretch; }
.lefthead-q { min-width:0; padding-top:5mm; }
.title-q { text-align:center; font:14pt Arial,sans-serif; font-weight:bold; padding:2mm 0 1mm; flex:1; }
.datebox-q { width:50mm; font-size:6.5pt; align-self:start; }
.datebox-q table,.head-q table { border-collapse:collapse; width:100%; table-layout:fixed; }
.datebox-q td,.datebox-q th,.head-q td,.grid-q th,.grid-q td { border:0.25mm solid #000; padding:0.45mm 0.55mm; vertical-align:middle; }
.head-q { width:100%; margin:0; }
.head-q td:nth-child(1),.head-q td:nth-child(3){width:26%;font-weight:bold}.head-q td:nth-child(2),.head-q td:nth-child(4){width:24%}
.grid-q { width:196mm; margin:0 auto; border-collapse:collapse; table-layout:fixed; }
.grid-q th,.grid-q td { text-align:center; }
.grid-q .left { text-align:left; }
.ket-q { width:196mm; margin:0 auto; border:0.25mm solid #000; border-top:0; min-height:14mm; padding:1mm; white-space:pre-line; }
.foot-q { width:196mm; margin:1mm auto 0; text-align:right; font-size:6.6pt; }
.q-clean thead th { height:8mm; font-weight:bold; }
.q-clean tbody td { height:16mm; font-size:7pt; }
.q-clean .q-param,.q-clean .q-kep { font-weight:bold; }
.q-clean .q-std { line-height:1.45; }
.q-clean .q-range { margin-top:1.5mm; font-size:6.4pt; font-weight:normal; }
/* ================= FORM P - SK GAUGE ================= */
.report-p { width:206mm; min-height:293mm; margin:2mm auto; padding:0 0 2mm; border:0.5mm solid #000; background:#fff; color:#000; font:6.8pt Arial,sans-serif; }
.report-p * { box-sizing:border-box; }
.company-p { text-align:center; font-weight:bold; height:5mm; padding-top:1mm; }
.top-p { width:196mm; margin:0 auto 1mm; display:grid; grid-template-columns:146mm 50mm; align-items:stretch; }
.lefthead-p { min-width:0; padding-top:5mm; }
.title-p { text-align:center; font:14pt Arial,sans-serif; font-weight:bold; padding:2mm 0 1mm; flex:1; }
.datebox-p { width:50mm; font-size:6.7pt; align-self:start; }
.datebox-p table,.head-p table,.sign-p table { border-collapse:collapse; width:100%; table-layout:fixed; }
.datebox-p td,.datebox-p th,.head-p td,.grid-p th,.grid-p td,.sign-p td,.sign-p th { border:0.25mm solid #000; padding:0.5mm 0.7mm; vertical-align:middle; }
.head-p { width:100%; margin:0; }
.head-p td:nth-child(1),.head-p td:nth-child(3){width:26%;font-weight:bold}.head-p td:nth-child(2),.head-p td:nth-child(4){width:24%}
.grid-p { width:196mm; margin:0 auto; border-collapse:collapse; table-layout:fixed; }
.grid-p th,.grid-p td { text-align:center; }
.grid-p td.left { text-align:left; }
.grid-p .type-cell { line-height:1.25; }
.grid-p .std-label { text-align:left; padding-left:1mm; }
.ket-p { width:196mm; margin:0 auto; border:0.25mm solid #000; border-top:0; min-height:16mm; padding:1mm; white-space:pre-line; }
.foot-p { width:196mm; margin:1mm auto 0; text-align:right; font-size:6.7pt; }
/* ================= FORM O - SNAP GAUGE ================= */
.report-o { width:206mm; min-height:293mm; margin:2mm auto; padding:0 0 2mm; border:0.5mm solid #000; background:#fff; color:#000; font:7pt Arial,sans-serif; }
.report-o * { box-sizing:border-box; }
.company-o { text-align:center; font-weight:bold; height:5mm; padding-top:1mm; }
.top-o { width:196mm; margin:0 auto 1mm; display:grid; grid-template-columns:146mm 50mm; align-items:stretch; }
.lefthead-o { min-width:0; padding-top:5mm; }
.title-o { text-align:center; font:15pt Arial,sans-serif; font-weight:bold; padding:2mm 0 3mm; }
.datebox-o { width:50mm; font-size:7pt; align-self:start; }
.datebox-o table, .head-o table, .detail-o table, .sign-o table { border-collapse:collapse; width:100%; table-layout:fixed; }
.datebox-o td, .datebox-o th, .head-o td, .detail-o td, .grid-o th, .grid-o td, .sign-o td, .sign-o th { border:0.25mm solid #000; padding:0.6mm 1mm; vertical-align:middle; }
.head-o { width:100%; margin:0; }
.head-o td { padding:0.45mm 0.7mm !important; }
.head-o td:nth-child(1), .head-o td:nth-child(3) { width:26%; font-weight:bold; }
.head-o td:nth-child(2), .head-o td:nth-child(4) { width:24%; }
.datebox-o table { height:auto; }
.datebox-o table tr:first-child td { height:7mm !important; vertical-align:bottom; }
.datebox-o table tr:nth-child(2) th { height:6mm !important; text-align:center; }
.datebox-o table tr:nth-child(3) td { height:16mm !important; vertical-align:bottom !important; text-align:center; }
.datebox-o table tr:nth-child(4) td { height:6mm !important; text-align:center; }
.grid-o { width:196mm; margin:0 auto; border-collapse:collapse; table-layout:fixed; }
.grid-o th, .grid-o td { text-align:center; }
.grid-o .spec-cell { line-height:1.2; }
.grid-o .left { text-align:left; }
.detail-o { width:196mm; margin:0 auto; }
.ket-o { width:196mm; margin:0 auto; border:0.25mm solid #000; border-top:0; min-height:8mm; padding:1mm; }
.sign-o { width:85mm; margin:3mm 7mm 0 auto; }
.sign-o .space td { height:18mm; vertical-align:bottom; text-align:center; }
.sign-o .role td, .sign-o th { text-align:center; }
.foot-o { text-align:right; font-size:7pt; margin:1mm 1mm 0; }

/* ================= FORM V - OUTSIDE / INSIDE / DEPTH ================= */

/* FORMAT W PRINT V1 - LAPORAN PEMERIKSAAN SUDUT SESUAI REFERENSI */
.report-w{width:206mm;min-height:293mm;margin:2mm auto;padding:0 0 2mm;border:.5mm solid #000;background:#fff;color:#000;font:8pt Arial,sans-serif}.report-w *{box-sizing:border-box}.company-w{text-align:center;font-weight:bold;height:7mm;padding-top:1mm}.title-w{text-align:center;font:15pt Arial,sans-serif;padding:7mm 0 5mm}.info-w,.hasil-w,.detail-w{width:176mm;margin:0 auto;border-collapse:collapse;table-layout:fixed}.info-w td,.detail-w td{border:.25mm solid #000;padding:.7mm 1mm;height:6mm;vertical-align:middle}.hasil-title-w{width:176mm;margin:0 auto;border:.25mm solid #000;border-bottom:0;height:6mm;text-align:center;padding:1mm}.hasil-w th,.hasil-w td{border:.25mm solid #000;text-align:center;vertical-align:middle;padding:.55mm 1mm;height:6mm;font-weight:normal}.hasil-w thead th{height:20mm}.hasil-w .blank-w td{border-top-style:dotted;border-bottom-style:dotted}.detail-w .ket-w{height:20mm;vertical-align:top}.diag-w{position:relative;overflow:hidden}.diag-w:after{content:'';position:absolute;left:0;top:0;width:142%;border-top:.25mm solid #000;transform:rotate(14deg);transform-origin:0 0}/* FORMAT W PRINT V2 - GARIS DIAGONAL KONSISTEN DARI POJOK KIRI ATAS */.city-date-w{width:106mm;margin:5mm 15mm 0 auto;padding:1mm 0}.sign-w{width:106mm;margin:0 15mm 0 auto;border-collapse:collapse;table-layout:fixed}.sign-w th,.sign-w td{border:.25mm solid #000;text-align:center;padding:1mm;font-weight:normal}.sign-w .space-w td{height:25mm;vertical-align:bottom;font-style:italic}.sign-w .role-w td{height:6mm}.doc-w{text-align:right;font-size:7pt;margin:2mm 1mm 0}.report-w,.report-w table,.report-w th,.report-w td{background:#fff!important;background-color:#fff!important;color:#000!important}

/* FORMAT V PRINT V1 - GROUP BAGIAN SEKALI + PEMBATAS BARIS KOSONG */
.report-v{width:206mm;min-height:293mm;margin:2mm auto;padding:0 0 2mm;border:.5mm solid #000;background:#fff;color:#000;font:8pt Arial,sans-serif}.report-v *{box-sizing:border-box}.company-v{text-align:center;font-weight:bold;height:7mm;padding-top:1mm}.title-v{text-align:center;font:15pt Arial,sans-serif;padding:7mm 0 5mm}.info-v,.hasil-v,.detail-v{width:176mm;margin:0 auto;border-collapse:collapse;table-layout:fixed}.info-v td,.detail-v td{border:.25mm solid #000;padding:.7mm 1mm;height:6mm;vertical-align:middle}.hasil-title-v{width:176mm;margin:0 auto;border:.25mm solid #000;border-bottom:0;height:6mm;text-align:center;padding:1mm}.hasil-v th,.hasil-v td{border:.25mm solid #000;text-align:center;vertical-align:middle;padding:.7mm 1mm;height:6mm;font-weight:normal}.hasil-v thead th{height:20mm}.hasil-v .group-gap-v td{height:6mm;border-top-style:dotted;border-bottom-style:dotted}.hasil-v .bagian-v{text-align:left;font-weight:normal}.detail-v .ket-v{height:18mm;vertical-align:top}.city-date-v{width:106mm;margin:5mm 15mm 0 auto;padding:1mm 0}.sign-v{width:106mm;margin:0 15mm 0 auto;border-collapse:collapse;table-layout:fixed}.sign-v th,.sign-v td{border:.25mm solid #000;text-align:center;padding:1mm;font-weight:normal}.sign-v .space-v td{height:25mm;vertical-align:bottom;font-style:italic}.sign-v .role-v td{height:6mm}.doc-v{text-align:right;font-size:7pt;margin:2mm 1mm 0}
/* ================= FORM U - LAPORAN KALIBRASI ================= */
/* FORMAT U PRINT V1 - LAYOUT REFERENSI STANDAR / HASIL / KOREKSI */
.report-u{width:206mm;min-height:293mm;margin:2mm auto;padding:0 0 2mm;border:.5mm solid #000;background:#fff;color:#000;font:8pt Arial,sans-serif}.report-u *{box-sizing:border-box}.company-u{text-align:center;font-weight:bold;height:7mm;padding-top:1mm}.title-u{text-align:center;font:15pt Arial,sans-serif;padding:7mm 0 5mm}.info-u,.hasil-u,.detail-u{width:176mm;margin:0 auto;border-collapse:collapse;table-layout:fixed}.info-u td,.detail-u td{border:.25mm solid #000;padding:.7mm 1mm;height:6mm;vertical-align:middle}.hasil-title-u{width:176mm;margin:0 auto;border:.25mm solid #000;border-bottom:0;height:6mm;text-align:center;padding:1mm}.hasil-u th,.hasil-u td{border:.25mm solid #000;text-align:center;vertical-align:middle;padding:.7mm 1mm;height:7mm;font-weight:normal}.hasil-u thead th{height:20mm}.hasil-u .blank-u td{height:7mm;border-top-style:dotted;border-bottom-style:dotted}.detail-u .ket-u{height:18mm;vertical-align:top}.city-date-u{width:106mm;margin:5mm 15mm 0 auto;padding:1mm 0}.sign-u{width:106mm;margin:0 15mm 0 auto;border-collapse:collapse;table-layout:fixed}.sign-u th,.sign-u td{border:.25mm solid #000;text-align:center;padding:1mm;font-weight:normal}.sign-u .space-u td{height:25mm;vertical-align:bottom;font-style:italic}.sign-u .role-u td{height:6mm}.doc-u{text-align:right;font-size:7pt;margin:2mm 1mm 0}
/* ================= FORM T - GO / NOT GO GAUGE ================= */
.report-t { width:206mm; min-height:293mm; margin:2mm auto; padding:0 0 2mm; border:0.5mm solid #000; background:#fff; color:#000; font:6.7pt Arial,sans-serif; }
.report-t * { box-sizing:border-box; }
.company-t { text-align:center; font-weight:bold; height:5mm; padding-top:1mm; }
.top-t { width:196mm; margin:0 auto 1mm; position:relative; min-height:24mm; padding-top:1mm; }
.title-t { width:140mm; margin:0 auto; text-align:center; font:15pt Arial,sans-serif; font-weight:normal; padding:4mm 0 3mm; line-height:1.2; }
.datebox-t { position:absolute; right:0; top:0; width:50mm; font-size:6.7pt; }
.datebox-t table,.head-t table,.sign-t { width:100%; border-collapse:collapse; table-layout:fixed; }
.datebox-t td,.datebox-t th,.grid-t th,.grid-t td,.sign-t td,.sign-t th { border:0.25mm solid #000; padding:0.55mm 0.65mm; vertical-align:middle; }
.datebox-t th,.datebox-t td,.sign-t th,.sign-t td { text-align:center; }
.head-t { width:140mm; margin:-8mm 0 3mm 5mm; font-size:7.2pt; }
.head-t table { border-collapse:collapse; table-layout:fixed; }
.head-t td { border:0; padding:0.45mm 0.7mm; vertical-align:top; }
.head-t td:nth-child(1),.head-t td:nth-child(3){width:24%;}.head-t td:nth-child(2),.head-t td:nth-child(4){width:26%;}
.grid-t { width:196mm; margin:2mm auto 0; border-collapse:collapse; table-layout:fixed; }
.grid-t th,.grid-t td { text-align:center; line-height:1.15; overflow-wrap:anywhere; }
.grid-t tbody tr { height:10mm; }
.grid-t .std-t { line-height:1.35; }
.grid-t .range-t { margin-top:1.2mm; font-size:6.3pt; }
/* FORMAT T PRINT V9 - HASIL UKUR 4 BOX FISIK + KODE WARNA PER BARIS */
.grid-t .t-no{width:4%}.grid-t .t-noalat{width:8%}.grid-t .t-spek{width:10%}.grid-t .t-stdspec{width:12%}.grid-t .t-stdno{width:8%}.grid-t .t-posisi{width:6%}.grid-t .t-std{width:14%}.grid-t .t-hasil{width:4%}.grid-t .t-warna{width:7%}.grid-t .t-kep{width:7%}.grid-t .t-next{width:10%}.grid-t .t-gambar{width:6%}
.grid-t .hasil-box-t{padding:0.5mm 0.3mm;text-align:center}.grid-t .shared-t{vertical-align:middle!important}.grid-t .posisi-t{font-weight:bold}.page-t{width:196mm;margin:3mm auto 1mm;text-align:right;font-weight:bold}

.ket-t { width:196mm; margin:0 auto; border:0.25mm solid #000; border-top:0; min-height:12mm; padding:1mm; }
.sign-t { width:85mm; margin:3mm 7mm 0 auto; }
.sign-t .space td { height:18mm; vertical-align:bottom; }
.foot-t { text-align:right; font-size:6.7pt; margin:1mm 5mm 0; }
/* ================= FORM M - PROFILE PROJECTOR ================= */
.report-m {
    width:206mm; min-height:293mm; margin:2mm auto; padding:0 0 2mm;
    border:0.5mm solid #000; background:#fff; color:#000; font:7.6pt Arial,sans-serif;
}
.report-m * { box-sizing:border-box; }
.company-m { text-align:center; font-weight:bold; height:5mm; padding-top:1mm; }
.title-m { text-align:center; font:15pt "Times New Roman",serif; padding:5mm 0 4mm; }
.info-m,.axis-m,.angle-m,.detail-m {
    width:176mm; margin:0 auto; border-collapse:collapse; table-layout:fixed;
}
.info-m td,.detail-m td {
    border:0.25mm solid #000; padding:0.7mm 1mm; height:5mm; vertical-align:middle;
}
.section-m {
    width:176mm; margin:0 auto; border:0.25mm solid #000; border-top:0;
    text-align:center; padding:1mm; font-weight:bold;
}
.axis-m th,.axis-m td,.angle-m th,.angle-m td {
    border:0.25mm solid #000; text-align:center; vertical-align:middle; padding:0.75mm;
}
.axis-m tbody tr { height:5.7mm; }
.angle-m tbody tr { height:6mm; }
.detail-m .ket-m { height:16mm; vertical-align:top; }
.city-date-m { width:112mm; margin:7mm 13mm 0 auto; padding:1mm 0; }
.sign-m { width:112mm; margin:0 13mm 0 auto; border-collapse:collapse; table-layout:fixed; }
.sign-m th,.sign-m td { border:0.25mm solid #000; text-align:center; padding:1mm; font-weight:normal; }
.sign-m .sign-space-m td { height:24mm; vertical-align:bottom; font-size:7.5pt; }
.sign-m .role-m td { height:6mm; }
.doc-code-m { text-align:right; font-size:7pt; margin:3mm 1mm 0; }

/* FORMAT V PRINT V1 - MONOKROM */
.report-v,.report-v table,.report-v th,.report-v td{background:#fff!important;background-color:#fff!important;color:#000!important}
/* ================= MODE MONOKROM SEMUA FORMAT ================= */
.report, .report-b, .report-c, .report-d, .report-h, .report-i, .report-j, .report-k, .report-l, .report-m, .report-o, .report-p, .report-q, .report-t,
.report table, .report-b table, .report-c table, .report-d table, .report-h table, .report-i table, .report-j table, .report-k table, .report-l table, .report-m table, .report-o table, .report-p table, .report-q table,
.report th, .report td, .report-b th, .report-b td, .report-c th, .report-c td, .report-d th, .report-d td, .report-h th, .report-h td, .report-i th, .report-i td, .report-j th, .report-j td, .report-k th, .report-k td, .report-l th, .report-l td, .report-m th, .report-m td, .report-o th, .report-o td, .report-p th, .report-p td, .report-q th, .report-q td, .report-t th, .report-t td {
    background: #fff !important;
    background-color: #fff !important;
    color: #000 !important;
}
.ok, .bad, .ok-b, .bad-b, .ok-c, .bad-c {
    color: #000 !important;
}

@media screen { .report-c, .report-b, .report-d, .report-h, .report-i, .report-j, .report-k, .report-l, .report-m, .report-o, .report-p, .report-q, .report-t { max-width:100%; } }

.sign-operator-name { font-weight: bold; min-height: 14px; margin-bottom: 2px; }
.sign-role-label { font-weight: bold; }

/* ================================================================
 * STANDARD UKURAN KOTAK VALIDASI
 * Mengikuti ukuran kotak validasi Format C.
 * Hanya area validasi/tanda tangan yang distandarkan; layout laporan
 * lainnya tidak diubah.
 * ================================================================ */
.sign-b, .sign-d, .sign-j, .sign-k, .sign-l, .sign-m {
    width: 96mm !important;
    margin: 0 15mm 0 auto !important;
    table-layout: fixed !important;
}
.sign-b th, .sign-c th, .sign-d th, .sign-j th, .sign-k th, .sign-l th, .sign-m th {
    height: 5mm !important;
}
.sign-b .sign-space-b td,
.sign-c .sign-space-c td,
.sign-d .sign-space-d td,
.sign-j .sign-space-j td,
.sign-k .sign-space-k td,
.sign-l .sign-space-l td,
.sign-m .sign-space-m td {
    height: 28mm !important;
    vertical-align: bottom !important;
    font-size: 8pt !important;
}
.sign-b .roles-b td,
.sign-c .roles-c td,
.sign-d .roles-d td,
.sign-j .role-j td,
.sign-k .role-k td,
.sign-l .role-l td,
.sign-m .role-m td {
    height: 10mm !important;
}

/* Header cleanup: tanggal di atas tanda tangan tidak dibox; metadata header tanpa grid. */
.datebox-p table tr:first-child td, .datebox-o table tr:first-child td, .datebox-q table tr:first-child td, .datebox-t table tr:first-child td { border:0 !important; text-align:left; padding-bottom:1.5mm; }
.head-p td, .head-o td, .head-q td, .head-t td { border:0 !important; }
.sign-h-table tr:nth-child(2) td, .sign-head-i tr:nth-child(2) td { height:16mm !important; }
.datebox-p table tr:nth-child(2) td, .datebox-o table tr:nth-child(2) td, .datebox-q table tr:nth-child(2) td, .datebox-t table tr:nth-child(2) td { height:22mm !important; }

/* Box validasi header. H/I dibuat lebih ringkas seperti referensi; P/O/Q/T tetap lega. */
.sign-h-table tr:nth-child(2) td,
.sign-head-i tr:nth-child(2) td {
    height: 16mm !important;
    vertical-align: bottom !important;
    font-size: 7.5pt !important;
}
.datebox-p table tr:nth-child(2) td,
.datebox-o table tr:nth-child(2) td,
.datebox-q table tr:nth-child(2) td,
.datebox-t table tr:nth-child(2) td {
    height: 22mm !important;
    vertical-align: bottom !important;
    font-size: 7.5pt !important;
}
.sign-h-table tr:nth-child(1) td,
.sign-head-i tr:nth-child(1) td,
.datebox-p table tr:nth-child(1) td,
.datebox-o table tr:nth-child(1) td,
.datebox-q table tr:nth-child(1) td,
.datebox-t table tr:nth-child(1) td {
    height: 5mm !important;
}
.sign-h-table tr:nth-child(3) td,
.sign-head-i tr:nth-child(3) td,
.datebox-p table tr:nth-child(3) td,
.datebox-o table tr:nth-child(3) td,
.datebox-q table tr:nth-child(3) td,
.datebox-t table tr:nth-child(3) td {
    height: 10mm !important;
}
.datebox-t table tr:nth-child(1) td { border:0 !important; }
.datebox-t table tr:nth-child(2) td { height:22mm !important; }

@media print {
    .no-print { display: none !important; }
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #000; margin: 0; padding: 20px; background: #525659; }
        
        .no-print { text-align: center; margin-bottom: 20px; }
        .btn-action { display: inline-block; width: 200px; padding: 12px; background: #fff; color: #333; text-align: center; text-decoration: none; font-weight: bold; border-radius: 5px; cursor: pointer; border:none; margin-top:10px; }
        .btn-action:hover { background: #ddd; }

        .a4-page { width: 210mm; min-height: 297mm; background: #fff; margin: 0 auto 20px auto; padding: 10mm 15mm; box-shadow: 0 4px 10px rgba(0,0,0,0.5); position: relative; page-break-after: always; }
        .a4-page:last-child { page-break-after: auto; margin-bottom: 0; }
        
        .table-excel { width: 100%; border-collapse: collapse; border: 1px solid #000; table-layout: fixed; }
        .table-excel th, .table-excel td { border: 1px solid #000; padding: 4px; page-break-inside: avoid; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; white-space: pre-wrap; } 
        
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        
        .header-title { font-size: 22px; font-weight: normal; text-align: center; margin: 5px 0 5px 0; letter-spacing: 1px;}
        .pt-title { font-weight: bold; text-align: center; font-size: 14px; margin-top:0px; margin-bottom: 5px;}
        
        .diagonal-strike { position: relative; overflow: hidden; }
        .diagonal-strike::after { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(to top right, transparent 49%, #000 50%, transparent 51%); }
        .diagonal-strike-td { position: relative; overflow: hidden; }
        .diagonal-strike-td::after { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(to top right, transparent 49%, #000 50%, transparent 51%); }

        .page-info { position: absolute; top: 10mm; right: 15mm; font-size: 11px; font-weight: bold; }

        @media print {
            @page { size: A4 portrait; margin: 0; }
            @page landscapeA4 { size: A4 landscape; margin: 0; }
            body { background: #fff; padding: 0; margin: 0; }
            body.print-landscape { page: landscapeA4; }
            body.print-landscape .report,
            body.print-landscape .report-h,
            body.print-landscape .report-o,
            body.print-landscape .report-p,
            body.print-landscape .report-q,
            body.print-landscape .report-t {
                width: 293mm !important;
                min-height: 206mm !important;
                margin: 2mm auto !important;
            }
            .a4-page { box-shadow: none; margin: 0; padding: 10mm 15mm; border: none; }
            .no-print { display: none !important; }
        }
    
/* Header alignment A mengikuti layout O */
.report > .header { margin-top:0; table-layout:fixed; }
.report > .header > tbody > tr > td:first-child { width:32% !important; }
.report > .header > tbody > tr > td:nth-child(2) { width:42% !important; }
.report > .header > tbody > tr > td.approval { width:26% !important; }
</style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()">🖨 Cetak Laporan</button>
    <button onclick="history.back()">← Kembali</button>
</div>

<div id="report"></div>

<script>
(function () {
    const raw = <?php if (isset($qaReportPayload)): ?><?=json_encode(json_encode($qaReportPayload, JSON_INVALID_UTF8_SUBSTITUTE), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE)?><?php else: ?>sessionStorage.getItem('cetak_data')<?php endif; ?>;

    if (!raw) {
        document.getElementById('report').innerHTML =
            '<div style="padding:20px;font-weight:bold;">Data cetak tidak ditemukan. Silakan kembali ke halaman pemeriksaan.</div>';
        return;
    }

    let data;
    const qaEscText = (v) => String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    const qaSignatureName = (v) => { if (v === null || v === undefined) return ''; const s = String(v).trim(); if (!s || s === '///') return ''; return qaEscText(s); };
    const qaApprovalDate = (role) => {
        let value = '';
        if (role === 'pembuat') {
            value = data.tanggal_periksa || data.tanggal_pengecekan || data.tanggal || '';
        } else if (role === 'pemeriksa') {
            value = data.tanggal_validasi_pemeriksa || data.validasi_pemeriksa_at || '';
        } else if (role === 'penyetuju') {
            value = data.tanggal_validasi_penyetuju || data.validasi_penyetuju_at || '';
        }
        if (!value && Array.isArray(data.audit)) {
            const tahap = role === 'pemeriksa' ? 'pemeriksa' : (role === 'penyetuju' ? 'penyetuju' : '');
            if (tahap) {
                for (const a of data.audit) {
                    if (String(a?.tahap || '').toLowerCase() === tahap && String(a?.keputusan || '').toLowerCase() === 'setuju' && a?.created_at) {
                        value = a.created_at;
                    }
                }
            }
        }
        if (!value || String(value).trim() === '///') return '';
        const s = String(value).trim();
        const m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!m) return `<span class="qa-sign-date qa-sign-date-${role}">${qaEscText(s)}</span>`;
        return `<span class="qa-sign-date qa-sign-date-${role}">${m[3]}-${m[2]}-${m[1]}</span>`;
    };
    try {
        data = JSON.parse(raw);
        data.signature_pembuat = data.signature_pembuat !== false;
        data.signature_pemeriksa = !!data.signature_pemeriksa;
        data.signature_penyetuju = !!data.signature_penyetuju;
        data.operator = data.operator || data.petugas || '';
        data.pemeriksa = data.pemeriksa || data.foreman || '';
        data.penyetuju = data.penyetuju || data.supervisor || '';
        data.foreman = data.pemeriksa;
        data.supervisor = data.penyetuju;
    } catch (e) {
        document.getElementById('report').innerHTML =
            '<div style="padding:20px;font-weight:bold;">Data cetak tidak valid.</div>';
        return;
    }



    /* ================================================================
     * NORMALISASI DATA CETAK
     * ---------------------------------------------------------------
     * Semua format A-T boleh datang dari versi payload yang berbeda
     * (master, riwayat lama, atau hasil_json snapshot). Sebelum masuk
     * renderer, satukan nama field agar template Excel tidak pernah
     * menerima literal "undefined".
     * ================================================================ */
    data = (data && typeof data === 'object') ? data : {};

    const isBlank = (v) => v === undefined || v === null || String(v).trim() === '';
    const pick = (...vals) => {
        for (const v of vals) if (!isBlank(v)) return v;
        return '';
    };
    const toArray = (v) => {
        if (Array.isArray(v)) return v;
        if (typeof v === 'string' && v.trim() !== '') {
            try {
                const parsed = JSON.parse(v);
                return Array.isArray(parsed) ? parsed : [];
            } catch (_) {}
        }
        return [];
    };
    const safeObj = (v) => (v && typeof v === 'object' && !Array.isArray(v)) ? v : {};
    const parseJsonValue = (v, fallback) => {
        if (v && typeof v === 'object') return v;
        if (typeof v === 'string' && v.trim() !== '') {
            try { return JSON.parse(v); } catch (_) {}
        }
        return fallback;
    };

    const htmlCell = (v) => {
        if (isBlank(v)) return '';
        return String(v)
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;')
            .replace(/'/g,'&#039;');
    };

    /* Payload history/snapshot kadang menyimpan field di header/footer. */
    const snapshot = safeObj(data.snapshot);
    const headerSnap = safeObj(data.header || snapshot.header);
    const envSnap = safeObj(data.environment || snapshot.environment);
    const footerSnap = safeObj(data.footer || snapshot.footer);

    data.format = String(pick(
        data.format, data.format_laporan,
        headerSnap.format, snapshot.format
    ) || 'A').trim().toUpperCase();
    data.format_laporan = data.format;

    // Format A, H, O, P, Q dicetak pada kertas A4 landscape.
    document.body.classList.toggle('print-landscape', ['A','H','O','P','Q','T'].includes(data.format));
    if (new URLSearchParams(window.location.search).get('embed') === '1') { const np=document.querySelector('.no-print'); if(np) np.style.display='none'; }

    data.no_part = pick(data.no_part, data.no_alat, headerSnap.no_part, headerSnap.no_alat);
    data.no_alat = pick(data.no_alat, data.no_part, headerSnap.no_alat, headerSnap.no_part);
    data.nama_part = pick(data.nama_part, data.nama_alat, headerSnap.nama_part, headerSnap.nama_alat);
    data.nama_alat = pick(data.nama_alat, data.nama_part, headerSnap.nama_alat, headerSnap.nama_part);

    data.spesifikasi = pick(data.spesifikasi, headerSnap.spesifikasi);
    data.resolusi = pick(data.resolusi, headerSnap.resolusi);
    data.ukuran = pick(data.ukuran, headerSnap.ukuran);
    data.type = pick(data.type, headerSnap.type);
    data.seksi_pemilik = pick(data.seksi_pemilik, data.seksi, headerSnap.seksi_pemilik, headerSnap.seksi);
    data.seksi = pick(data.seksi, data.seksi_pemilik, headerSnap.seksi, headerSnap.seksi_pemilik);

    data.metode = pick(data.metode, data.metode_uji, headerSnap.metode, headerSnap.metode_uji);
    data.metode_uji = pick(data.metode_uji, data.metode, headerSnap.metode_uji, headerSnap.metode);
    data.standar_uji = pick(data.standar_uji, headerSnap.standar_uji);
    data.alat_standar_header = pick(
        data.alat_standar_header,
        headerSnap.alat_standar_header,
        data.standar_uji
    );

    // Format J: ALAT VERIFIKASI hanya berasal dari header.
    if (isBlank(data.alat_verifikasi)) {
        const jPointsRaw = pick(
            data.titik_standar_json,
            headerSnap.titik_standar_json,
            snapshot.titik_standar_json
        );

        const jPoints = Array.isArray(jPointsRaw)
            ? jPointsRaw
            : (() => {
                try {
                    return JSON.parse(jPointsRaw || '[]');
                } catch (_) {
                    return [];
                }
            })();

        const jHeader = Array.isArray(jPoints)
            ? jPoints.find(function (x) {
                return x &&
                    String(x.meta || '').toUpperCase() === 'J_HEADER';
            })
            : null;

        data.alat_verifikasi = pick(
            data.alat_verifikasi,
            headerSnap.alat_verifikasi,
            // Format J memakai field header `spesifikasi` sebagai Alat Verifikasi
            // pada form registrasi. Ambil fallback ini agar ikut masuk printing.
            headerSnap.spesifikasi,
            snapshot.spesifikasi,
            jHeader && jHeader.alat_verifikasi,
            Array.isArray(data.hasil_array) &&
            data.hasil_array[0] &&
            (data.hasil_array[0].alat_verifikasi || data.hasil_array[0].spesifikasi_alat_verifikasi)
        );
    }

    data.traceability = pick(data.traceability, headerSnap.traceability);
    data.satuan = pick(data.satuan, headerSnap.satuan);
    data.toleransi_global = pick(data.toleransi_global, headerSnap.toleransi_global);
    data.toleransi = pick(data.toleransi, data.toleransi_global, headerSnap.toleransi, headerSnap.toleransi_global);

    data.suhu = pick(data.suhu, envSnap.suhu, headerSnap.suhu);
    data.kelembapan = pick(data.kelembapan, envSnap.kelembapan, headerSnap.kelembapan);

    // Printing: cegah simbol suhu / kelembapan tercetak ganda.
    // Nilai sumber boleh sudah mengandung °C / C / %; renderer yang membutuhkan
    // simbol akan menambahkannya tepat satu kali.
    const qaSuhuValue = (v) => String(v ?? '')
        .replace(/&deg;\s*C?/gi, '')
        .replace(/°\s*C?/gi, '')
        .replace(/\s*C\s*$/i, '')
        .trim();
    const qaHumidityValue = (v) => String(v ?? '')
        .replace(/%/g, '')
        .trim();
    data.ketidakpastian = pick(data.ketidakpastian, footerSnap.ketidakpastian, headerSnap.ketidakpastian);
    data.confidence_level = pick(
        data.confidence_level, data.confidence,
        footerSnap.confidence_level, headerSnap.confidence_level
    );
    data.confidence = pick(data.confidence, data.confidence_level);

    data.status_final = pick(data.status_final, data.keputusan, headerSnap.status_final, headerSnap.keputusan);
    data.keputusan = pick(data.keputusan, data.status_final);
    data.tanggal_periksa = pick(
        data.tanggal_periksa, data.tanggal, data.tanggal_pengecekan,
        headerSnap.tanggal_periksa, snapshot.tanggal_periksa
    );
    data.jadwal_berikutnya = pick(
        data.jadwal_berikutnya, data.periksa_berikut,
        data.periode_berikutnya, footerSnap.jadwal_berikutnya
    );
    data.operator = pick(data.operator, data.petugas, data.created_by, headerSnap.operator, headerSnap.petugas);
    data.petugas = data.operator || data.petugas || '';
    data.keterangan = pick(data.keterangan, footerSnap.keterangan);

    // Khusus field KETERANGAN: kosong tetap kosong pada semua format cetak.
    // Jangan gunakan fallback umum '///' untuk field ini.
    const qaKeterangan = (value) => {
        if (value === null || value === undefined) return '';
        const text = String(value).trim();
        if (text === '' || text === '///') return '';
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };
    data.no_laporan = pick(data.no_laporan, data.nomor_pengecekan, headerSnap.no_laporan, snapshot.no_laporan);
    data.foreman = pick(data.foreman, footerSnap.foreman, headerSnap.foreman);
    data.supervisor = pick(data.supervisor, footerSnap.supervisor, headerSnap.supervisor);

    data.hasil_array = toArray(data.hasil_array);
    if (!data.hasil_array.length) data.hasil_array = toArray(data.rows);
    data.rows = data.hasil_array;

    /* Standard point tersimpan sebagai JSON saat registrasi. */
    const stdRaw = pick(
        data.titik_standar_json,
        data.titik_standar,
        data.standard_points,
        headerSnap.titik_standar_json,
        snapshot.titik_standar_json
    );
    data.titik_standar_json = stdRaw || '';
    data.standard_points = toArray(parseJsonValue(stdRaw, []));
    if (!Array.isArray(data.standard_points)) data.standard_points = [];
    
    if (data.format === 'F') {
        data.standard_points.forEach(p => {
            if (p.meta !== 'F' && p.standar == null) {
                if (p.batas_bawah != null && p.batas_bawah !== '') {
                    p.standar = p.batas_bawah;
                    p.zona = 'B';
                } else if (p.batas_atas != null && p.batas_atas !== '') {
                    p.standar = p.batas_atas;
                    p.zona = 'A';
                }
            }
        });
        if (data.standard_points.length > 0) {
            data.reg_f_std_sec1 = data.standard_points[0].std_sec1;
            data.reg_f_std_sec3 = data.standard_points[0].std_sec3;
        }
    }

    const firstStandardPoint = (data.standard_points[0] && typeof data.standard_points[0] === 'object') ? data.standard_points[0] : {standar:data.standard_points[0]};
    data.toleransi = pick(data.toleransi, firstStandardPoint.toleransi, firstStandardPoint.tol, firstStandardPoint.toleransi_persen, data.toleransi_global);

    const findStd = (predicate) => data.standard_points.find((x) => {
        try { return predicate(safeObj(x)); } catch (_) { return false; }
    }) || {};

    /* A / H: lengkapi nilai standar dari JSON master bila snapshot hasil
       hanya menyimpan actual-nya. */
    if (data.format === 'A' && data.hasil_array.length) {
        const r0 = safeObj(data.hasil_array[0]);
        const goStd = findStd(x => String(pick(x.posisi, x.label)).toUpperCase() === 'GO');
        const ngStd = findStd(x => String(pick(x.posisi, x.label)).toUpperCase().replace(/\s+/g,' ') === 'NOT GO');
        const out = safeObj(r0.out), pitch = safeObj(r0.pitch);
        const mergeStd = (target, src) => ({
            ...target,
            // Prioritas: hasil snapshot saat kalibrasi -> master registration JSON.
            // Alias lama tetap didukung agar data existing tidak hilang.
            std_spesifikasi: pick(
                target.std_spesifikasi, target.spesifikasi,
                src.std_spesifikasi, src.spesifikasi, src.std_spek, src.spek
            ),
            std_no_alat: pick(
                target.std_no_alat, target.no_alat,
                src.std_no_alat, src.no_alat, src.nomor_alat, src.no_part
            ),
            std_out: pick(target.std_out, src.std_out, src.out_std, src.standar_out),
            tol_out: pick(target.tol_out, src.tol_out, src.toleransi_out),
            range_out: pick(target.range_out, src.range_out, src.range_out_diameter),
            std_pitch: pick(target.std_pitch, src.std_pitch, src.pitch_std, src.standar_pitch),
            tol_pitch: pick(target.tol_pitch, src.tol_pitch, src.toleransi_pitch),
            range_pitch: pick(target.range_pitch, src.range_pitch, src.range_pitch_diameter)
        });
        const goRow = mergeStd(safeObj(r0.go), goStd);
        const ngRow = mergeStd(safeObj(r0.not_go || r0.notgo), ngStd);
        data.hasil_array[0] = {
            ...r0,
            go: goRow,
            not_go: ngRow,
            notgo: ngRow,
            out: mergeStd(out, goStd),
            pitch: mergeStd(pitch, goStd)
        };
    }

    if (data.format === 'J' && data.hasil_array.length) {
        data.hasil_array = data.hasil_array.map((rawRow, i) => {
            const row = safeObj(rawRow);
            const std = data.standard_points[i] || data.standard_points[0] || {};
            return {
                ...row,
                alat_verifikasi: pick(row.alat_verifikasi, std.alat_verifikasi),
                spesifikasi_verifikasi: pick(row.spesifikasi_verifikasi, std.spesifikasi_verifikasi)
            };
        });
    }

    if (data.format === 'I' && data.hasil_array.length) {
        data.hasil_array = data.hasil_array.map((r, i) => {
            const row = safeObj(r);
            const std = data.standard_points[i] || data.standard_points[0] || {};
            return {
                ...row,
                spesifikasi_alat: pick(row.spesifikasi_alat, std.spesifikasi_alat, data.spesifikasi),
                std_spesifikasi: pick(row.std_spesifikasi, std.std_spesifikasi),
                std_no_alat: pick(row.std_no_alat, std.std_no_alat)
            };
        });
    }

    if (data.format === 'H' && data.hasil_array.length) {
        data.hasil_array = data.hasil_array.map((r, i) => {
            const row = safeObj(r);
            const std = data.standard_points.find(x =>
                String(pick(x.posisi_ukur, x.posisi, x.label)).toUpperCase() ===
                String(pick(row.posisi, row.posisi_ukur, i === 0 ? 'GO' : 'NOT GO')).toUpperCase()
            ) || data.standard_points[i] || {};
            return {
                ...row,
                std_spesifikasi: pick(row.std_spesifikasi, std.std_spesifikasi),
                std_no_alat: pick(row.std_no_alat, std.std_no_alat),
                std_out: pick(row.std_out, std.std_out),
                tol_out: pick(row.tol_out, std.tol_out),
                range_out: pick(row.range_out, std.range_out),
                std_pitch: pick(row.std_pitch, std.std_pitch),
                tol_pitch: pick(row.tol_pitch, std.tol_pitch),
                range_pitch: pick(row.range_pitch, std.range_pitch)
            };
        });
    }

    /* M: toleransi AXIS/ANGLE tidak selalu ikut tersimpan di row actual. */
    /* N: standar dan toleransi adalah master registration points.
       Pastikan row hasil lama yang hanya menyimpan actual/koreksi tetap
       mendapatkan nilai STANDAR persis dari qa_part.titik_standar_json. */
    if (data.format === 'N' && data.hasil_array.length) {
        data.hasil_array = data.hasil_array.map((rawRow, i) => {
            const row = safeObj(rawRow);
            const std = data.standard_points[i] || {};
            return {
                ...row,
                standar: pick(row.standar, row.standard, row.nilai_standar, row.std, std.standar, std.standard, std.nilai_standar, std.std),
                toleransi: pick(row.toleransi, row.tol, row.toleransi_global, std.toleransi, std.tol, std.toleransi_global)
            };
        });
    }

    /* N: posisi/bagian dinamis yang ditambahkan petugas saat update.
       Tetap dukung histori lama yang hanya memiliki field hasil tunggal. */
    if (data.format === 'N') {
        let nCols = Array.isArray(data.posisi_kolom) ? data.posisi_kolom : [];
        if (!nCols.length && data.hasil_array.length) {
            const firstN = safeObj(data.hasil_array[0]);
            const fromRow = Array.isArray(firstN.posisi_kolom) ? firstN.posisi_kolom : (Array.isArray(firstN.posisi_hasil) ? firstN.posisi_hasil : []);
            nCols = fromRow.map((x, i) => ({ label: pick(x && x.label, x && x.posisi, 'Posisi '+(i+1)), posisi: pick(x && x.posisi, x && x.label, 'Posisi '+(i+1)) }));
        }
        if (!nCols.length) nCols = [{ label: 'Hasil', posisi: 'Hasil' }];
        data.posisi_kolom = nCols.map((x,i) => ({ label: pick(x && x.label, x && x.posisi, 'Posisi '+(i+1)), posisi: pick(x && x.posisi, x && x.label, 'Posisi '+(i+1)) }));
        data.hasil_array = data.hasil_array.map(function(rawRow){
            const row = safeObj(rawRow);
            let pos = Array.isArray(row.posisi_hasil) ? row.posisi_hasil : (Array.isArray(row.posisi_kolom) ? row.posisi_kolom : []);
            if (!pos.length) pos = data.posisi_kolom.map(function(col){ return { posisi: col.posisi, label: col.label, hasil: row.hasil || '', koreksi: row.koreksi || '' }; });
            return { ...row, posisi_hasil: pos };
        });
    }

    /* =================================================================
     * UNIVERSAL MASTER-STANDARD MERGE A-T
     * ---------------------------------------------------------------
     * hasil_json menyimpan nilai actual/editable. Nilai standar yang readonly
     * tetap bersumber dari qa_part.titik_standar_json ketika field tersebut
     * belum ada di histori lama. Ini membuat semua format A-T konsisten.
     * ================================================================= */
    const standardKeysByFormat = {
        B:['std_min','std_max'],
        C:['toleransi'],
        D:['standar'],
        E:['standar','toleransi'],
        G:['standar','toleransi'],
        H:['posisi_ukur','std_spesifikasi','std_no_alat','std_out','tol_out','range_out','std_pitch','tol_pitch','range_pitch'],
        I:['spesifikasi_alat','std_spesifikasi','std_no_alat'],
        J:[],
        K:['nilai_ditentukan','toleransi_persen'],
        L:['toleransi'],
        M:['jenis','std_mm','toleransi_axis','std_degree','toleransi_sudut'],
        N:['standar','toleransi'],
        O:['spesifikasi','tol_plus','tol_minus','go_std','go_tol_near','go_tol_far','go_min','go_max','notgo_std','notgo_tol','notgo_min','notgo_max'],
        P:['std_label','min','max','actual_count','alat_nama','alat_no'],
        Q:['subtype','std_spesifikasi','std_no_alat','std','tol_plus','tol_minus','min','max'],
        R:['bagian','standar','toleransi'],
        T:['posisi','spesifikasi_standar','no_alat_standar','standar','toleransi']
    };
    const stdAt = (idx) => {
        const rawStd = data.standard_points[idx];
        return (rawStd && typeof rawStd === 'object' && !Array.isArray(rawStd)) ? rawStd : {standar: rawStd};
    };
    const rowSafetyKeys = {
        B:['tekanan','handle','baut','std_min','std_max','std1','std2','std3','uji1','uji2','uji3','hasil','rata','status'],
        C:['setting','actual','alat_std','selisih','status'],
        D:['unit','mode','setting','standar','hasil','koreksi','status'],
        E:['standar','toleransi','t1_hasil','t1_koreksi','t2_hasil','t2_koreksi','status'],
        G:['standar','toleransi','hasil','hasil_2','status'],
        H:['posisi_ukur','std_spesifikasi','std_no_alat','std_out','tol_out','range_out','std_pitch','tol_pitch','range_pitch','kode_warna','putusan','status'],
        I:['spesifikasi_alat','std_spesifikasi','std_no_alat','hasil_atas','hasil_bawah','kode_warna','putusan','status'],
        J:['no_part','spesifikasi','alat_verifikasi','spesifikasi_verifikasi','hasil_verifikasi','status'],
        K:['nilai_ditentukan','toleransi_persen','nilai_pengukuran','selisih','status'],
        L:['arah','applied_value','indicated_value','toleransi','correction','koreksi','status'],
        M:['jenis','std_mm','toleransi_axis','x_left','x_left_correction','x_right','x_right_correction','y_front','y_front_correction','y_back','y_back_correction','std_degree','toleransi_sudut','hasil_degree','hasil_minute','hasil_second','correction','status'],
        N:['bagian','standar','toleransi','hasil','koreksi','status'],
        O:['spesifikasi','tol_plus','tol_minus','go_std','go_tol_near','go_tol_far','go_min','go_max','go_hasil_min','go_hasil_max','notgo_std','notgo_tol','notgo_min','notgo_max','notgo_hasil_min','notgo_hasil_max','kode_warna','keputusan','no_gambar','status'],
        P:['no_alat','type','ukuran','upart','alat_nama','alat_no','std_label','min','max','actual_count','actual1','actual2','aktual1','aktual2','status','keputusan','no_gambar'],
        Q:['subtype','std_spesifikasi','std_no_alat','std','tol_plus','tol_minus','min','max','actual1','actual2','actual3','actual4','kode_warna','keputusan','no_gambar','status'],
        R:['bagian','standar','toleransi','depan','tengah','belakang','status'],
        T:['posisi','spesifikasi_standar','resolusi_standar','no_alat_standar','standar','toleransi','tol_minus','tol_plus','tol_bawah','tol_atas','min','max','hasil_depan1','hasil_depan2','hasil_belakang1','hasil_belakang2','depan1','depan2','belakang1','belakang2','actual','kode_warna','keputusan','no_gambar','status']
    };

    if (!['A','F'].includes(data.format) && data.hasil_array.length) {
        const keys = standardKeysByFormat[data.format] || [];
        data.hasil_array = data.hasil_array.map(function(rawRow, idx) {
            const row = safeObj(rawRow);
            const master = stdAt(idx);
            const merged = { ...master, ...row };
            keys.forEach(function(key) {
                if (isBlank(row[key]) && !isBlank(master[key])) merged[key] = master[key];
                if (!(key in merged)) merged[key] = '';
            });
            (rowSafetyKeys[data.format] || []).forEach(function(key) {
                if (merged[key] === undefined || merged[key] === null) merged[key] = '';
            });
            if (data.format === 'H') {
                merged.out = {dep:'',ten:'',bel:'', ...safeObj(row.out)};
                merged.pitch = {dep:'',ten:'',bel:'', ...safeObj(row.pitch)};
            }
            return merged;
        });
    }

    if (data.format === 'F' && data.hasil_array.length) {
        const f = safeObj(data.hasil_array[0]);
        f.sec1 = {standar:'',hasil:'',deviasi:'', ...safeObj(f.sec1)};
        f.sec2 = toArray(f.sec2).map(function(r,i){ return {standar:pick(r.standar, stdAt(i).standar), hasil:pick(r.hasil,r.actual,r.actual1), koreksi:pick(r.koreksi,r.correction)}; });
        f.sec3 = {tengah:'',belakang:'',kanan:'',depan:'',kiri:'',perbedaan:'', ...safeObj(f.sec3)};
        data.hasil_array = [f];
    }

    if (data.format === 'M') {
        const mAxis = findStd(x => String(pick(x.jenis, x.posisi, x.label)).toUpperCase().indexOf('AXIS') >= 0);
        const mAngle = findStd(x => String(pick(x.jenis, x.posisi, x.label)).toUpperCase().indexOf('ANGLE') >= 0);
        data.toleransi_axis = pick(data.toleransi_axis, mAxis.toleransi_axis, mAxis.toleransi);
        data.toleransi_sudut = pick(data.toleransi_sudut, mAngle.toleransi_sudut, mAngle.toleransi);
        if (isBlank(data.toleransi_axis)) {
            const a = findStd(x => !isBlank(x.std_mm));
            data.toleransi_axis = pick(a.toleransi_axis, a.toleransi);
        }
        if (isBlank(data.toleransi_sudut)) {
            const a = findStd(x => !isBlank(x.std_degree));
            data.toleransi_sudut = pick(a.toleransi_sudut, a.toleransi);
        }
    }

    /* P: UI menyimpan row flat, sedangkan template Excel membutuhkan
       item -> measurements[]. Bentuk ulang tanpa mengubah urutan data. */
    const normalizePItems = (rows) => {
        if (!Array.isArray(rows) || !rows.length) return [];
        if (rows.some(r => Array.isArray(safeObj(r).measurements))) {
            return rows.map(r => {
                const x = safeObj(r);
                return {
                    ...x,
                    type: pick(x.type, x.type_ukuran),
                    alat_nama: pick(x.alat_nama, x.nama_alat_pakai),
                    alat_no: pick(x.alat_no, x.no_alat_pakai),
                    measurements: toArray(x.measurements).map(m => ({...safeObj(m)}))
                };
            });
        }

        const items = [];
        const map = new Map();
        rows.forEach((rawRow, idx) => {
            const r = safeObj(rawRow);
            if (isBlank(r.min) || isBlank(r.max)) {
                const std = parseFloat(String(pick(r.std_label, r.standar, '')).replace(/[^0-9+\-.]/g,'').replace(',', '.'));
                const mode = String(pick(r.toleransi_mode, ((r.tol_plus !== undefined || r.tol_minus !== undefined) ? 'range' : 'pm')));
                if (mode === 'range') {
                    const a = parseFloat(String(pick(r.tol_plus, '')).replace(',', '.'));
                    const b = parseFloat(String(pick(r.tol_minus, '')).replace(',', '.'));
                    if (Number.isFinite(std) && Number.isFinite(a) && Number.isFinite(b)) { r.min = std + Math.min(a,b); r.max = std + Math.max(a,b); }
                } else {
                    const tol = parseFloat(String(pick(r.toleransi, '')).replace(/[±%]/g,'').replace(',', '.'));
                    if (Number.isFinite(std) && Number.isFinite(tol)) { r.min = std - Math.abs(tol); r.max = std + Math.abs(tol); }
                }
            }
            const groupId = String(pick(r.group_id, '')).trim();
            const keyParts = [pick(r.type_ukuran, r.type), r.alat_nama, r.alat_no];
            const hasIdentity = groupId || keyParts.some(v => !isBlank(v));
            const key = groupId ? 'GROUP::'+groupId : (hasIdentity ? keyParts.map(v => String(v ?? '').trim()).join('||') : '__ROW__' + idx);

            let item = map.get(key);
            if (!item) {
                item = {
                    no_alat: pick(r.no_alat, r.no_part),
                    type: pick(r.type_ukuran, r.type),
                    ukuran: pick(r.ukuran),
                    upart: pick(r.upart),
                    alat_nama: pick(r.alat_nama, r.nama_alat_pakai, r.nama_standar, r.standar_nama),
                    alat_no: pick(r.alat_no, r.no_alat_pakai, r.std_no_alat, r.no_standar),
                    keputusan: pick(r.keputusan, r.status, data.keputusan),
                    no_gambar: pick(r.no_gambar, data.no_gambar),
                    measurements: []
                };
                map.set(key, item);
                items.push(item);
            }
            // Metadata bersama kadang hanya tersimpan pada salah satu row hasil.
            // Jangan hilangkan ketika row pertama group kebetulan kosong.
            if (isBlank(item.no_gambar) && !isBlank(r.no_gambar)) item.no_gambar = r.no_gambar;
            if (isBlank(item.alat_nama)) item.alat_nama = pick(r.alat_nama, r.nama_alat_pakai, r.nama_standar, r.standar_nama);
            if (isBlank(item.alat_no)) item.alat_no = pick(r.alat_no, r.no_alat_pakai, r.std_no_alat, r.no_standar);
            item.measurements.push({
                alat_nama: pick(r.alat_nama, r.nama_standar, r.standar_nama),
                alat_no: pick(r.alat_no, r.std_no_alat, r.no_standar),
                std_label: pick(r.std_label, r.standar, r.posisi),
                toleransi_mode: pick(r.toleransi_mode, ((r.tol_plus !== undefined || r.tol_minus !== undefined) ? 'range' : 'pm')),
                toleransi: pick(r.toleransi),
                tol_plus: pick(r.tol_plus),
                tol_minus: pick(r.tol_minus),
                min: pick(r.min, r.min_range),
                max: pick(r.max, r.max_range),
                actual_count: pick(r.actual_count, r.jumlah_actual, '2'),
                actual1: pick(r.actual1, r.aktual1, r.hasil, r.actual),
                actual2: pick(r.actual2, r.aktual2, r.hasil2, r.actual_2)
            });
        });
        return items;
    };
    if (data.format === 'P') data.hasil_array = normalizePItems(data.hasil_array);

    /* F selalu aman walaupun sec1/sec2/sec3 belum ada karena payload lama. */
    const normalizeFData = (raw) => {
        const root = safeObj(raw);
        let sec2 = toArray(root.sec2);
        if (!sec2.length) {
            sec2 = data.hasil_array
                .filter((x, i) => i > 0 || !root.sec1)
                .map(x => ({
                    posisi: pick(x.posisi, x.label),
                    standar: pick(x.standar, x.std, x.standard),
                    hasil: pick(x.hasil, x.actual, x.actual1),
                    koreksi: pick(x.koreksi, x.correction)
                }));
        }
        const sec1 = safeObj(root.sec1);
        const sec3 = safeObj(root.sec3);
        return {
            ...root,
            sec1: {
                ...sec1,
                standar: pick(sec1.standar, sec1.std, sec1.standard, data.reg_f_std_sec1),
                hasil: pick(sec1.hasil, sec1.actual)
            },
            sec2: sec2.map((x, i) => {
                const stdObj = data.standard_points[i] || {};
                return {
                    ...safeObj(x),
                    standar: pick(x.standar, x.std, x.standard, stdObj.standar)
                };
            }),
            sec3: {
                ...sec3,
                standar: pick(sec3.standar, sec3.std, sec3.standard, data.reg_f_std_sec3),
                tengah: pick(sec3.tengah),
                belakang: pick(sec3.belakang),
                kanan: pick(sec3.kanan),
                depan: pick(sec3.depan),
                kiri: pick(sec3.kiri),
                perbedaan: pick(sec3.perbedaan),
                hasil: pick(sec3.hasil, sec3.actual)
            }
        };
    };
    if (data.format === 'F') {
        data.hasil_array = [normalizeFData(data.hasil_array[0] || {})];
    }

    // Q memakai struktur nested untuk SERATION/PLUG. Pastikan semua field ada
    // supaya template tidak pernah mencetak literal "undefined" pada data lama.
    if (data.format === 'Q' && data.hasil_array.length) {
        data.hasil_array = data.hasil_array.map(function(rawRow, idx) {
            const row = { ...safeObj(rawRow) };
            const subtype = String(pick(row.subtype, stdAt(idx).subtype, 'SERATION')).toUpperCase();
            row.subtype = subtype;
            row.kode_warna = pick(row.kode_warna, data.kode_warna);
            row.keputusan = pick(row.keputusan, data.keputusan);
            row.no_gambar = pick(row.no_gambar, data.no_gambar);
            if (subtype === 'SERATION') {
                row.out = {
                    std_spesifikasi:'', std_no_alat:'', std:'', tol_plus:'', tol_minus:'', min:'', max:'', actual:[],
                    ...safeObj(row.out)
                };
                row.pitch = {
                    std_spesifikasi:'', std_no_alat:'', std:'', tol_plus:'', tol_minus:'', min:'', max:'', actual:[],
                    ...safeObj(row.pitch)
                };
                row.out.actual = Array.isArray(row.out.actual) ? row.out.actual : [];
                row.pitch.actual = Array.isArray(row.pitch.actual) ? row.pitch.actual : [];
                while (row.out.actual.length < 3) row.out.actual.push('');
                while (row.pitch.actual.length < 3) row.pitch.actual.push('');
                [row.out,row.pitch].forEach(r=>{ const std=parseFloat(String(r.std||'').replace(',','.')); const tol=parseFloat(String(r.tol_plus||r.tol_minus||'').replace(/[±%]/g,'').replace(',','.')); if((isBlank(r.min)||isBlank(r.max))&&Number.isFinite(std)&&Number.isFinite(tol)){r.min=std-Math.abs(tol);r.max=std+Math.abs(tol);} });
            } else {
                row.std_spesifikasi = pick(row.std_spesifikasi, stdAt(idx).std_spesifikasi);
                row.std_no_alat = pick(row.std_no_alat, stdAt(idx).std_no_alat);
                row.go = { std:'', tol_plus:'', tol_minus:'', min:'', max:'', actual:{front1:'',front2:'',back1:'',back2:''}, ...safeObj(row.go) };
                row.notgo = { std:'', tol_plus:'', tol_minus:'', min:'', max:'', actual:{front1:'',front2:'',back1:'',back2:''}, ...safeObj(row.notgo) };
                row.go.actual = {front1:'',front2:'',back1:'',back2:'', ...safeObj(row.go.actual)};
                row.notgo.actual = {front1:'',front2:'',back1:'',back2:'', ...safeObj(row.notgo.actual)};
            }
            return row;
        });
    }

/* ================================================================
 * RENDER CLEANUP
 * - Empty input -> blank cell with one diagonal strike.
 * - KETERANGAN stays blank when no content.
 * - Header creator date is hidden; footer validation date remains.
 * - Common typo/label cleanup and Format A print-only fields.
 * ================================================================ */
    const qaCleanReport = () => {
        const root = document.getElementById('report');
        if (!root) return;
        root.querySelectorAll('td').forEach(td => {
            const txt = (td.textContent || '').trim();
            if (!txt) return;
            if (/^KETERANGAN\s*:/i.test(txt)) {
                td.innerHTML = td.innerHTML.replace(/\/\/\//g, '');
                return;
            }
            const hasSlash = txt.includes('///');
            const residual = txt.replace(/\/\/\//g, '').replace(/[\s°%±()~.,:+\-A-Za-z]+/g, '');
            if (txt === '///' || (hasSlash && residual === '')) {
                td.textContent = '';
                td.classList.add('diagonal-strike-td');
            }
        });
        root.querySelectorAll('.qa-sign-date-pembuat').forEach(span => {
            /* Tanggal pembuat tetap disembunyikan; tanggal pemeriksa/penyetuju tetap tampil. */
            span.style.display = 'none';
        });
        root.querySelectorAll('.tiny, small').forEach(el => {
            if (data.format === 'A') el.style.display = 'none';
        });
        if (data.format === 'A') {
            root.querySelectorAll('tr').forEach(tr => {
                if ((tr.textContent || '').toUpperCase().includes('KODE VARIAN')) tr.remove();
            });
        }
        root.querySelectorAll('*').forEach(el => {
            if (el.childElementCount === 0 && el.textContent) {
                const next = el.textContent.replace(/\bDIKET\b/g,'DIKETAHUI').replace(/DITARIK MECHANICAL/g,'DITARIK TECHNICAL');
                if (next !== el.textContent) el.textContent = next;
            }
        });
    };
    const qaReportObserver = new MutationObserver(() => setTimeout(qaCleanReport, 0));
    qaReportObserver.observe(document.getElementById('report'), { childList:true, subtree:true });


// ================= FORM W (PEMERIKSAAN SUDUT) =================
    if (String(data.format || '').toUpperCase() === 'W') {
        // FORMAT W PRINT V1 - STANDAR DERAJAT / HASIL DERAJAT-MENIT / KOREKSI MENIT
        const eW=v=>(v===null||v===undefined||String(v).trim()===''?'///':String(v).trim().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'));
        const rawW=v=>(v===null||v===undefined?'':String(v).trim());
        const dW=v=>{if(!v)return'///';const m=String(v).match(/^(\d{4})-(\d{2})-(\d{2})/);return m?m[3]+' - '+m[2]+' - '+m[1]:eW(v);};
        const dlW=v=>{if(!v)return'///';const m=String(v).match(/^(\d{4})-(\d{2})-(\d{2})/);if(!m)return eW(v);const bl=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];return m[3]+' '+bl[parseInt(m[2],10)-1]+' '+m[1];};
        const rows=Array.isArray(data.hasil_array)?data.hasil_array.filter(Boolean):[]; let body='';
        rows.forEach((r,i)=>{body+=`<tr><td>${i+1}</td><td>${eW(r.standar)}</td><td>${eW(r.hasil)}</td><td>${eW(r.koreksi)}</td></tr>`;});
        for(let i=0;i<Math.max(0,10-rows.length);i++)body+='<tr class="blank-w"><td></td><td></td><td></td><td></td></tr>';
        // FORMAT W PRINT V3 - RESOLUSI + STANDAR TOLERANSI AMBIL DARI SNAPSHOT/MASTER TERSEDIA
        const wResolusi=rawW(data.resolusi||headerSnap.resolusi);
        const metode=rawW(data.metode||data.metode_uji),hum=rawW(data.kelembapan),suhu=rawW(data.suhu),tol=rawW(data.toleransi_global||data.toleransi||headerSnap.toleransi_global||headerSnap.toleransi),kep=String(data.keputusan||data.status_final||'').toUpperCase();
        // FORMAT W PRINT V2 - TOLERANSI DERAJAT DITAMPILKAN JUGA DALAM MENIT: ± 0.1° ( 6' )
        // FORMAT W PRINT V4 - AMBIL ANGKA TOLERANSI PERTAMA SAJA; TEKS "( 6 ' )" BUKAN BAGIAN NILAI DERAJAT
        const tolMatch=String(tol).replace(',','.').match(/[+-]?\d+(?:\.\d+)?/);
        const tolNum=tolMatch?parseFloat(tolMatch[0]):NaN;
        const tolText=Number.isFinite(tolNum)?('± '+Math.abs(tolNum).toFixed(3).replace(/0+$/,'').replace(/\.$/,'')+' ° ( '+(Math.abs(tolNum)*60).toFixed(2).replace(/\.00$/,'').replace(/(\.\d)0$/,'$1')+" ' )"):tol;
        const metodeCell=(metode===''||metode==='-')?'<td class="diag-w"></td>':'<td>'+eW(metode)+'</td>';
        const humCell=(hum===''||hum==='-')?'<td class="diag-w"></td>':'<td style="text-align:center">'+eW(qaHumidityValue(hum))+' %</td>';
        const suhuCell=(suhu===''||suhu==='-')?'<td class="diag-w"></td>':'<td>'+eW(qaSuhuValue(suhu))+' °C</td>';
        document.getElementById('report').innerHTML=`<div class="report-w"><div class="company-w">PT. SURYA TOTO INDONESIA Tbk.</div><div class="title-w">LAPORAN PEMERIKSAAN</div><table class="info-w"><tr><td>NO LAPORAN</td><td>${eW(data.no_laporan)}</td><td>SEKSI</td><td style="font-weight:bold;text-align:center">${eW(data.seksi_pemilik||data.seksi)}</td></tr><tr><td>NAMA ALAT</td><td>${eW(data.nama_part||data.nama_alat)}</td><td>PETUGAS PERIKSA</td><td style="text-align:center">${qaSignatureName(data.operator||data.petugas)}</td></tr><tr><td>NO. ALAT</td><td>${eW(data.no_part||data.no_alat)}</td><td rowspan="2">TANGGAL PERIKSA</td><td rowspan="2" style="text-align:center">${dW(data.tanggal_periksa)}</td></tr><tr><td>SPESIFIKASI</td><td>${eW(data.spesifikasi)}</td></tr><tr><td>RESOLUSI</td><td>${eW(wResolusi)}</td><td></td><td></td></tr></table><div class="hasil-title-w">HASIL PEMERIKSAAN</div><table class="hasil-w"><thead><tr><th style="width:10%">NO</th><th style="width:38%">STANDAR<br><br>( derajat )</th><th style="width:32%">HASIL<br><br>( derajat . menit )</th><th style="width:20%">KOREKSI<br><br>( menit )</th></tr></thead><tbody>${body}</tbody></table><table class="detail-w"><tr><td>METODE</td>${metodeCell}<td>KELEMBABAN RUANGAN</td>${humCell}</tr><tr><td>STANDAR UJI</td><td>${eW(data.standar_uji)}</td><td>STANDAR TOLERANSI</td><td style="text-align:center">${tolText?eW(tolText):'///'}</td></tr><tr><td>TRACEABILITY</td><td>${eW(data.traceability)}</td><td>KEPUTUSAN</td><td style="font-weight:bold;text-align:center">${eW(kep)}</td></tr><tr><td>SUHU RUANGAN</td>${suhuCell}<td>PERIKSA BERIKUTNYA</td><td style="text-align:center">${dW(data.jadwal_berikutnya||data.periode_berikutnya)}</td></tr><tr><td colspan="4" class="ket-w"><b>KETERANGAN :</b><br>${qaKeterangan(data.keterangan)}</td></tr></table><div class="city-date-w">TANGERANG, ${dlW(data.tanggal_periksa)}</div><table class="sign-w"><tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr><tr class="space-w"><td>${qaSignatureName(data.operator||data.petugas)}<br>${qaApprovalDate('pembuat')}</td><td>${qaSignatureName(data.pemeriksa||data.foreman)}<br>${qaApprovalDate('pemeriksa')}</td><td>${qaSignatureName(data.penyetuju||data.supervisor)}<br>${qaApprovalDate('penyetuju')}</td></tr><tr class="role-w"><td>PETUGAS</td><td>FOREMAN</td><td>SUPERVISOR</td></tr></table><div class="doc-w">STIS-CMFG4-03/R4</div></div>`;
        return;
    }

// ================= FORM V (OUTSIDE / INSIDE / DEPTH) =================
    if (String(data.format || '').toUpperCase() === 'V') {
        // FORMAT V PRINT V1 - OUTSIDE/INSIDE/DEPTH HANYA SEKALI, ANTAR BAGIAN 1 BARIS KOSONG
        const eV=v=>(v===null||v===undefined||String(v).trim()===''?'///':String(v).trim().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'));
        const rawV=v=>(v===null||v===undefined?'':String(v).trim());
        const nV=v=>{const n=parseFloat(String(v??'').replace(',','.').replace(/[^0-9+\-.]/g,''));return Number.isFinite(n)?n:NaN;};
        const dV=v=>{if(!v)return'///';const m=String(v).match(/^(\d{4})-(\d{2})-(\d{2})/);return m?m[3]+' - '+m[2]+' - '+m[1]:eV(v);};
        const all=Array.isArray(data.hasil_array)?data.hasil_array.filter(Boolean):[];
        const order=['OUTSIDE','INSIDE','DEPTH']; let body='';
        order.forEach(function(group,gi){
            const rr=all.filter(r=>String(r.bagian||'').trim().toUpperCase()===group);
            if(!rr.length)return;
            if(body) body+='<tr class="group-gap-v"><td></td><td></td><td></td><td></td><td></td></tr>';
            rr.forEach(function(r,i){
                const st=nV(r.standar),hs=nV(r.hasil),kor=Number.isFinite(st)&&Number.isFinite(hs)?st-hs:nV(r.koreksi);
                const kep=String(r.status||'').toUpperCase();
                body+=`<tr><td class="bagian-v">${i===0?eV(group.charAt(0)+group.slice(1).toLowerCase()):''}</td><td>${eV(r.standar)}</td><td>${eV(r.hasil)}</td><td>${Number.isFinite(kor)?kor.toFixed(2):eV(r.koreksi)}</td><td>${eV(kep==='-'?'':kep)}</td></tr>`;
            });
        });
        const extras=all.filter(r=>!order.includes(String(r.bagian||'').trim().toUpperCase()));
        extras.forEach(function(r,i){const st=nV(r.standar),hs=nV(r.hasil),kor=Number.isFinite(st)&&Number.isFinite(hs)?st-hs:nV(r.koreksi);body+=`<tr><td class="bagian-v">${eV(r.bagian)}</td><td>${eV(r.standar)}</td><td>${eV(r.hasil)}</td><td>${Number.isFinite(kor)?kor.toFixed(2):eV(r.koreksi)}</td><td>${eV(r.status)}</td></tr>`;});
        const kep=String(data.keputusan||data.status_final||'').toUpperCase(),tol=rawV(data.toleransi_global||data.toleransi),satuan=rawV(data.satuan)||'mm';
        // FORMAT V PRINT V2 - KELEMBAPAN SELALU TAMPIL KHUSUS FORMAT V, FORMAT LAIN TIDAK DIUBAH
        const humV=rawV(data.kelembapan),metodeV=rawV(data.metode||data.metode_uji),showMetodeV=metodeV!==''&&metodeV!=='-';
        const humDisplayV=(humV!==''&&humV!=='-')?(eV(qaHumidityValue(humV))+' %'):eV(humV||'-');
        let first=''; if(showMetodeV)first=`<tr><td>METODE</td><td>${eV(metodeV)}</td><td>KELEMBABAN RUANGAN</td><td style="text-align:center">${humDisplayV}</td></tr>`;else first=`<tr><td>KELEMBABAN RUANGAN</td><td colspan="3" style="text-align:center">${humDisplayV}</td></tr>`;
        document.getElementById('report').innerHTML=`<div class="report-v"><div class="company-v">PT.SURYA TOTO INDONESIA Tbk.</div><div class="title-v">LAPORAN KALIBRASI</div><table class="info-v"><tr><td>NO LAPORAN</td><td>${eV(data.no_laporan)}</td><td>SEKSI</td><td style="font-weight:bold;text-align:center">${eV(data.seksi_pemilik||data.seksi)}</td></tr><tr><td>NAMA ALAT</td><td>${eV(data.nama_part||data.nama_alat)}</td><td>PETUGAS KALIBRASI</td><td style="text-align:center">${qaSignatureName(data.operator||data.petugas)}</td></tr><tr><td>NOMOR ALAT</td><td>${eV(data.no_part||data.no_alat)}</td><td>TANGGAL KALIBRASI</td><td style="text-align:center">${dV(data.tanggal_periksa)}</td></tr><tr><td>SPESIFIKASI</td><td>${eV(data.spesifikasi)}</td><td></td><td></td></tr><tr><td>RESOLUSI</td><td>${eV(data.resolusi)}</td><td></td><td></td></tr></table><div class="hasil-title-v">HASIL KALIBRASI</div><table class="hasil-v"><thead><tr><th style="width:10%">BAGIAN</th><th style="width:30%">STANDAR<br>( ${eV(satuan)} )</th><th style="width:30%">HASIL<br>( ${eV(satuan)} )</th><th style="width:20%">KOREKSI</th><th style="width:10%">KEP</th></tr></thead><tbody>${body||'<tr><td colspan="5">Tidak ada data.</td></tr>'}</tbody></table><table class="detail-v">${first}<tr><td>STANDAR UJI</td><td>${eV(data.standar_uji)}</td><td>KETIDAKPASTIAN</td><td style="text-align:center">${eV(data.ketidakpastian)}</td></tr><tr><td>TRACEABILITY</td><td>${eV(data.traceability)}</td><td>STANDAR TOLERANSI</td><td style="text-align:center">${tol?eV(tol)+' '+eV(satuan):'///'}</td></tr><tr><td>CONFIDENCE LEVEL</td><td>${eV(data.confidence_level||data.confidence)}</td><td>KEPUTUSAN</td><td style="font-weight:bold;text-align:center">${eV(kep)}</td></tr><tr><td>SUHU RUANGAN</td><td>${rawV(data.suhu)?eV(qaSuhuValue(data.suhu))+' °C':'///'}</td><td>KALIBRASI BERIKUTNYA</td><td style="text-align:center">${dV(data.jadwal_berikutnya||data.periode_berikutnya)}</td></tr><tr><td colspan="4" class="ket-v"><b>KETERANGAN :</b><br>${qaKeterangan(data.keterangan)}</td></tr></table><div class="city-date-v">TANGERANG, ${dV(data.tanggal_periksa)}</div><table class="sign-v"><tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr><tr class="space-v"><td>${qaSignatureName(data.operator||data.petugas)}<br>${qaApprovalDate('pembuat')}</td><td>${qaSignatureName(data.pemeriksa||data.foreman)}<br>${qaApprovalDate('pemeriksa')}</td><td>${qaSignatureName(data.penyetuju||data.supervisor)}<br>${qaApprovalDate('penyetuju')}</td></tr><tr class="role-v"><td>PETUGAS</td><td>FOREMAN</td><td>SUPERVISOR</td></tr></table><div class="doc-v">STIS-CMFG4-03/R4</div></div>`;
        return;
    }

// ================= FORM U (LAPORAN KALIBRASI) =================
    if (String(data.format || '').toUpperCase() === 'U') {
        const eU=v=>(v===null||v===undefined||String(v).trim()===''?'///':String(v).trim().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'));
        const rawU=v=>(v===null||v===undefined?'':String(v).trim());
        const nU=v=>{const n=parseFloat(String(v??'').replace(',','.').replace(/[^0-9+\-.]/g,''));return Number.isFinite(n)?n:NaN;};
        const dU=v=>{if(!v)return'///';const m=String(v).match(/^(\d{4})-(\d{2})-(\d{2})/);return m?m[3]+' - '+m[2]+' - '+m[1]:eU(v);};
        const rows=Array.isArray(data.hasil_array)?data.hasil_array.filter(Boolean):[]; let body='';
        rows.forEach((r,i)=>{const st=nU(r.standar),hs=nU(r.hasil),kor=Number.isFinite(st)&&Number.isFinite(hs)?st-hs:nU(r.koreksi);body+=`<tr><td>${i+1}</td><td>${eU(r.standar)}</td><td>${eU(r.hasil)}</td><td>${Number.isFinite(kor)?kor.toFixed(3):eU(r.koreksi)}</td></tr>`;});
        for(let i=0;i<Math.max(0,10-rows.length);i++)body+='<tr class="blank-u"><td></td><td></td><td></td><td></td></tr>';
        // FORMAT U PRINT V5 - METODE/KELEMBAPAN '-' DIHILANGKAN BESERTA KOTAKNYA, TANPA GARIS CORET
        const kep=String(data.keputusan||data.status_final||'').toUpperCase(),tol=rawU(data.toleransi_global||data.toleransi),satuan=rawU(data.satuan)||'mm';
        const humU=rawU(data.kelembapan), metodeU=rawU(data.metode||data.metode_uji);
        const showMetodeU=(metodeU!=='' && metodeU!=='-');
        const showHumU=(humU!=='' && humU!=='-');
        let detailFirstRowU='';
        if(showMetodeU && showHumU){
            detailFirstRowU=`<tr><td>METODE</td><td>${eU(data.metode||data.metode_uji)}</td><td>KELEMBABAN RUANGAN</td><td style="text-align:center">${eU(qaHumidityValue(humU))} %</td></tr>`;
        }else if(showMetodeU){
            detailFirstRowU=`<tr><td>METODE</td><td colspan="3">${eU(data.metode||data.metode_uji)}</td></tr>`;
        }else if(showHumU){
            detailFirstRowU=`<tr><td>KELEMBABAN RUANGAN</td><td colspan="3" style="text-align:center">${eU(qaHumidityValue(humU))} %</td></tr>`;
        }
        document.getElementById('report').innerHTML=`<div class="report-u"><div class="company-u">PT.SURYA TOTO INDONESIA Tbk.</div><div class="title-u">LAPORAN KALIBRASI</div><table class="info-u"><tr><td>NO LAPORAN</td><td>${eU(data.no_laporan)}</td><td>SEKSI</td><td>${eU(data.seksi_pemilik||data.seksi)}</td></tr><tr><td>NAMA ALAT</td><td>${eU(data.nama_part||data.nama_alat)}</td><td>PETUGAS KALIBRASI</td><td style="text-align:center">${qaSignatureName(data.operator||data.petugas)}</td></tr><tr><td>NOMOR ALAT</td><td>${eU(data.no_part||data.no_alat)}</td><td>TANGGAL KALIBRASI</td><td style="text-align:center">${dU(data.tanggal_periksa)}</td></tr><tr><td>SPESIFIKASI</td><td>${eU(data.spesifikasi)}</td><td></td><td></td></tr><tr><td>RESOLUSI</td><td>${eU(data.resolusi)}</td><td></td><td></td></tr></table><div class="hasil-title-u">HASIL KALIBRASI</div><table class="hasil-u"><thead><tr><th style="width:10%">NO</th><th style="width:30%">STANDAR<br>${eU(satuan)}</th><th style="width:30%">HASIL<br>${eU(satuan)}</th><th style="width:30%">KOREKSI<br>${eU(satuan)}</th></tr></thead><tbody>${body}</tbody></table><table class="detail-u">${detailFirstRowU}<tr><td>STANDAR UJI</td><td>${eU(data.standar_uji)}</td><td>KETIDAKPASTIAN</td><td style="text-align:center">${eU(data.ketidakpastian)}</td></tr><tr><td>TRACEABILITY</td><td>${eU(data.traceability)}</td><td>STANDAR TOLERANSI</td><td style="text-align:center">${tol?(tol.includes('±')?eU(tol):'± '+eU(tol))+' '+eU(satuan):'///'}</td></tr><tr><td>CONFIDENCE LEVEL</td><td>${rawU(data.confidence_level)?eU(data.confidence_level):'///'}</td><td>KEPUTUSAN</td><td style="text-align:center;font-weight:bold">${eU(kep)}</td></tr><tr><td>SUHU RUANGAN</td><td>${rawU(data.suhu)?eU(qaSuhuValue(data.suhu))+' °C':'///'}</td><td>KALIBRASI BERIKUTNYA</td><td style="text-align:center">${dU(data.jadwal_berikutnya||data.periksa_berikut)}</td></tr><tr><td colspan="4" class="ket-u">KETERANGAN :<br>${qaKeterangan(data.keterangan)}</td></tr></table><div class="city-date-u">TANGERANG, ${dU(data.tanggal_periksa)}</div><table class="sign-u"><tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr><tr class="space-u"><td>${qaSignatureName(data.operator||data.petugas)}<br>${qaApprovalDate('pembuat')}</td><td>${qaSignatureName(data.pemeriksa||data.foreman)}<br>${qaApprovalDate('pemeriksa')}</td><td>${qaSignatureName(data.penyetuju||data.supervisor)}<br>${qaApprovalDate('penyetuju')}</td></tr><tr class="role-u"><td>PETUGAS</td><td>FOREMAN</td><td>SUPERVISOR</td></tr></table><div class="doc-u">STIS-CMFG4-03/R4</div></div>`;
        return;
    }

// ================= FORM T (GO / NOT GO GAUGE) =================
    if (String(data.format || '').toUpperCase() === 'T') {
        const escT=v=>{if(v===null||v===undefined||String(v).trim()==='')return '///';return String(v).trim().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');};
        const rawT=v=>v===null||v===undefined?'':String(v).trim();
        const numT=v=>{const n=parseFloat(String(v??'').replace(',','.').replace(/[^0-9+\-.]/g,''));return Number.isFinite(n)?n:NaN;};
        const shortT=v=>{if(!v)return'///';const m=String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);return m?m[3]+' - '+m[2]+' - '+m[1]:escT(v);};
        const longT=v=>{if(!v)return'///';const m=String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);if(!m)return escT(v);const b=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];return m[3]+' '+b[+m[2]-1]+' '+m[1];};
        const fmtT=(n,d=3)=>Number.isFinite(n)?n.toFixed(d):'';
        let rows=Array.isArray(data.hasil_array)?data.hasil_array.filter(Boolean):[];
        if(!rows.length && rawT(data.hasil_json)){try{const parsedT=JSON.parse(data.hasil_json);if(Array.isArray(parsedT))rows=parsedT.filter(Boolean);}catch(_){}}
        if(rows.length===1 && (rows[0].go||rows[0].not_go)){rows=[Object.assign({posisi:'GO'},rows[0].go||{}),Object.assign({posisi:'NOT GO'},rows[0].not_go||{})];}
        if(!rows.length)rows=[{posisi:'GO'},{posisi:'NOT GO'}];
        const normalize=(r,pos)=>{
            r=Object.assign({},r||{}); r.posisi=r.posisi||pos;
            const st=numT(r.standar), oldTol=Math.abs(numT(r.toleransi));
            let lo=numT(r.tol_minus ?? r.tol_bawah), hi=numT(r.tol_plus ?? r.tol_atas);
            if(!Number.isFinite(lo)&&Number.isFinite(oldTol))lo=-oldTol;
            if(!Number.isFinite(hi)&&Number.isFinite(oldTol))hi=oldTol;
            let mn=numT(r.min),mx=numT(r.max);
            if(Number.isFinite(st)&&Number.isFinite(lo)&&Number.isFinite(hi)){mn=Math.min(st+lo,st+hi);mx=Math.max(st+lo,st+hi);}
            r._st=st;r._lo=lo;r._hi=hi;r._min=mn;r._max=mx;return r;
        };
        rows=rows.map((r,i)=>normalize(r,i%2?'NOT GO':'GO'));
        const values=(r,keys)=>keys.map(k=>rawT(r[k])).filter(Boolean);
        const depanVals=r=>values(r,['hasil_depan1','hasil_depan2','depan1','depan2','hasil_depan','depan']);
        const belakangVals=r=>values(r,['hasil_belakang1','hasil_belakang2','belakang1','belakang2','hasil_belakang','belakang']);
        const allVals=rows.flatMap(r=>depanVals(r).concat(belakangVals(r)));
        const calcOK=allVals.length>0 && rows.every(r=>depanVals(r).concat(belakangVals(r)).every(v=>{const n=numT(v);return Number.isFinite(n)&&Number.isFinite(r._min)&&Number.isFinite(r._max)&&n>=r._min-1e-9&&n<=r._max+1e-9;}));
        let kep=String(data.keputusan||data.status_final||'').toUpperCase();
        if(!['OK','NOT GOOD'].includes(kep))kep=allVals.length?(calcOK?'OK':'NOT GOOD'):'///';
        const nogambar=rawT(data.no_gambar)||rawT(rows.find(r=>rawT(r.no_gambar))?.no_gambar);
        const sharedSpec=rawT(rows.find(r=>rawT(r.spesifikasi_standar||r.spesifikasi_alat_standar))?.spesifikasi_standar)||rawT(rows.find(r=>rawT(r.spesifikasi_alat_standar))?.spesifikasi_alat_standar)||rawT(data.spesifikasi_alat_standar)||rawT(data.standar_uji);
        const sharedRes=rawT(rows.find(r=>rawT(r.resolusi_standar||r.resolusi_alat_standar))?.resolusi_standar)||rawT(rows.find(r=>rawT(r.resolusi_alat_standar))?.resolusi_alat_standar)||rawT(data.resolusi_alat_standar);
        const sharedNoStd=rawT(rows.find(r=>rawT(r.no_alat_standar))?.no_alat_standar)||rawT(data.no_alat_standar);
        const body=rows.map((r,i)=>{
            const absLo=Number.isFinite(r._lo)?Math.abs(r._lo):NaN, absHi=Number.isFinite(r._hi)?Math.abs(r._hi):NaN;
            const tol=(Number.isFinite(absLo)&&Number.isFinite(absHi)&&Math.abs(absLo-absHi)<1e-9)
                ? '±'+Number(absHi.toFixed(6)).toString()
                : (Number.isFinite(r._lo)?(r._lo>=0?'+':'')+Number(r._lo.toFixed(6)).toString():'')+'/'+(Number.isFinite(r._hi)?(r._hi>=0?'+':'')+Number(r._hi.toFixed(6)).toString():'');
            const std=`<div class="std-t"><b>${escT(r.standar)}</b> &nbsp; ${escT(tol)}<div class="range-t">( ${escT(fmtT(r._min))} ~ ${escT(fmtT(r._max))} )</div></div>`;
            const depan=depanVals(r), belakang=belakangVals(r);
            const d1=depan[0]||'', d2=depan[1]||'', b1=belakang[0]||'', b2=belakang[1]||'';
            // Kode warna Format T berasal dari input per baris pada Update Hasil Kalibrasi.
            const kode=rawT(r.kode_warna)||rawT(r.kodeWarna)||rawT(r.warna)||rawT(data.kode_warna);
            return `<tr>${i===0?`<td rowspan="${rows.length}">1</td><td rowspan="${rows.length}">${escT(data.no_part||data.no_alat)}</td><td rowspan="${rows.length}">${escT(data.spesifikasi)} ${rawT(data.toleransi)?'&nbsp; '+escT(data.toleransi):''}</td><td rowspan="${rows.length}"><div>${escT(sharedSpec)}</div>${rawT(sharedRes)?`<div style="margin-top:1.2mm">(${escT(sharedRes)})</div>`:''}</td><td rowspan="${rows.length}">${escT(sharedNoStd)}</td>`:''}<td class="posisi-t">${escT(r.posisi)}</td><td>${std}</td><td class="hasil-box-t">${escT(d1)}</td><td class="hasil-box-t">${escT(d2)}</td><td class="hasil-box-t">${escT(b1)}</td><td class="hasil-box-t">${escT(b2)}</td><td>${escT(kode)}</td>${i===0?`<td rowspan="${rows.length}" class="shared-t"><b>${escT(kep)}</b></td><td rowspan="${rows.length}" class="shared-t">${shortT(data.jadwal_berikutnya||data.periksa_berikut)}</td><td rowspan="${rows.length}" class="shared-t">${escT(nogambar)}</td>`:''}</tr>`;
        }).join('');
        document.getElementById('report').innerHTML=`<div class="report report-t"><div class="company-t">PT. SURYA TOTO INDONESIA Tbk.</div><div class="top-t"><div class="title-t">LAPORAN PEMERIKSAAN GAUGE</div><div class="datebox-t"><table><tr><td colspan="3" style="text-align:left">Tanggal : ${longT(data.tanggal_periksa)}</td></tr><tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr><tr style="height:15mm"><td style="vertical-align:bottom">${qaSignatureName(data.operator||data.petugas)}</td><td style="vertical-align:bottom">${qaSignatureName(data.pemeriksa||data.foreman)}<br>${qaApprovalDate('pemeriksa')}</td><td style="vertical-align:bottom">${qaSignatureName(data.penyetuju||data.supervisor)}<br>${qaApprovalDate('penyetuju')}</td></tr><tr><td>PETUGAS</td><td>FOREMAN</td><td>SUPERVISOR</td></tr></table></div></div><div class="head-t"><table><tr><td>NO LAPORAN</td><td>: ${escT(data.no_laporan)}</td><td>ALAT STANDAR</td><td>: ${escT(data.standar_uji)}</td></tr><tr><td>NAMA ALAT</td><td>: ${escT(data.nama_alat||data.nama_part)}</td><td>TRACEABILITY</td><td>: ${escT(data.traceability)}</td></tr><tr><td>METODE</td><td>: ${escT(data.metode)}</td><td>SUHU / HUMIDITY</td><td>: ${escT(qaSuhuValue(data.suhu))} °C / ${escT(qaHumidityValue(data.kelembapan))} %</td></tr><tr><td>SEKSI</td><td>: ${escT(data.seksi_pemilik||data.seksi)}</td><td>TANGGAL PERIKSA</td><td>: ${longT(data.tanggal_periksa)}</td></tr></table></div><div class="page-t">HAL : 1 / 1</div><table class="grid-t"><thead><tr><th rowspan="2" class="t-no">NO</th><th rowspan="2" class="t-noalat">NO ALAT</th><th rowspan="2" class="t-spek">SPESIFIKASI</th><th colspan="2">ALAT STANDAR YANG DIPAKAI</th><th rowspan="2" class="t-posisi">POSISI<br>UKUR</th><th rowspan="2" class="t-std">STD UKURAN</th><th colspan="4">HASIL UKUR</th><th rowspan="2" class="t-warna">KODE<br>WARNA</th><th rowspan="2" class="t-kep">KEP</th><th rowspan="2" class="t-next">TANGGAL PERIKSA<br>BERIKUTNYA</th><th rowspan="2" class="t-gambar">NO<br>GAMBAR</th></tr><tr><th class="t-stdspec">SPESIFIKASI</th><th class="t-stdno">NO ALAT</th><th colspan="2">DEPAN</th><th colspan="2">BELAKANG</th></tr></thead><tbody>${body}</tbody></table><div class="ket-t"><b>KETERANGAN :</b><br>${qaKeterangan(data.keterangan)}</div><div class="foot-t">STIS-CMFG4-06 / R1</div></div>`;
        return;
    }

// ================= FORM Q (SERATION / PLUG GAUGE) =================
    if (String(data.format || '').toUpperCase() === 'Q') {
        const escQ=(v)=>{if(v===null||v===undefined||String(v).trim()==='')return '///';return String(v).trim().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');};
        const cleanQ=(v)=>{const n=parseFloat(String(v??'').replace(',','.'));return Number.isFinite(n)?Number(n.toFixed(6)).toString():'';};
        const dateShortQ=(v)=>{if(!v)return '///';const m=String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);return m?m[3]+' - '+m[2]+' - '+m[1]:escQ(v);};
        const dateLongQ=(v)=>{if(!v)return '///';const m=String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);if(!m)return escQ(v);const b=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];return m[3]+' '+b[parseInt(m[2],10)-1]+' '+m[1];};
        const rawQ=Array.isArray(data.hasil_array)&&data.hasil_array.length?data.hasil_array:[];
        const q=rawQ[0]||{};
        let rowsQ=[];
        if(q.out||q.pitch){ rowsQ=[Object.assign({bagian:'OUT'},q.out||{}),Object.assign({bagian:'PITCH'},q.pitch||{})]; }
        else { rowsQ=rawQ.map((r,i)=>Object.assign({bagian:r.bagian||(i===0?'OUT':'PITCH')},r)); }
        rowsQ=rowsQ.filter(r=>r&&Object.keys(r).length);
        if(!rowsQ.length) rowsQ=[{bagian:'OUT'},{bagian:'PITCH'}];
        const rangeQ=(r)=>{
            let mn=parseFloat(String(r.min??'').replace(',','.')), mx=parseFloat(String(r.max??'').replace(',','.'));
            const st=parseFloat(String(r.std??'').replace(',','.')), lo=parseFloat(String(r.tol_minus??'').replace(',','.')), hi=parseFloat(String(r.tol_plus??'').replace(',','.'));
            if(Number.isFinite(st)&&Number.isFinite(lo))mn=st+lo;
            if(Number.isFinite(st)&&Number.isFinite(hi))mx=st+hi;
            return {min:Number.isFinite(mn)?cleanQ(mn):'',max:Number.isFinite(mx)?cleanQ(mx):''};
        };
        const stdQ=(r)=>{
            const st=String(r.std??'').trim(), hi=String(r.tol_plus??'').trim(), lo=String(r.tol_minus??'').trim(), rg=rangeQ(r);
            let top=st||'///';
            if(hi!==''||lo!=='') top+=' '+(hi||'0')+'/'+(lo||'0');
            const range=(rg.min!==''&&rg.max!=='')?`<div class="q-range">( ${escQ(rg.min)} ~ ${escQ(rg.max)} )</div>`:'';
            return escQ(top)+range;
        };
        const headerQ=`<div class="company-q">PT. SURYA TOTO INDONESIA Tbk.</div><div class="top-q"><div class="lefthead-q"><div class="title-q">LAPORAN PEMERIKSAAN GAUGE</div><div class="head-q"><table>
            <tr><td>NO LAPORAN</td><td>: ${escQ(data.no_laporan)}</td><td>ALAT STANDAR</td><td>: ${escQ(data.standar_uji)}</td></tr>
            <tr><td>NAMA ALAT</td><td>: ${escQ(data.nama_alat)}</td><td>TRACEABILITY</td><td>: ${escQ(data.traceability)}</td></tr>
            <tr><td>METODE</td><td>: ${escQ(data.metode)}</td><td>SUHU / HUMIDITY</td><td>: ${escQ(qaSuhuValue(data.suhu))} °C / ${escQ(qaHumidityValue(data.kelembapan))} %</td></tr>
            <tr><td>SEKSI</td><td>: ${escQ(data.seksi)}</td><td>TANGGAL PERIKSA</td><td>: ${dateLongQ(data.tanggal_periksa)}</td></tr>
        </table></div></div><div class="datebox-q"><table><tr><td colspan="3">TANGGAL : ${dateLongQ(data.tanggal_periksa)}</td></tr><tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr><tr style="height:15mm"><td style="vertical-align:bottom;text-align:center">${qaSignatureName(data.operator||data.petugas)}<br>${qaApprovalDate('pembuat')}</td><td style="vertical-align:bottom;text-align:center">${qaSignatureName(data.pemeriksa||data.foreman)}<br>${qaApprovalDate('pemeriksa')}</td><td style="vertical-align:bottom;text-align:center">${qaSignatureName(data.penyetuju||data.supervisor)}<br>${qaApprovalDate('penyetuju')}</td></tr><tr><td style="text-align:center">PETUGAS</td><td style="text-align:center">FOREMAN</td><td style="text-align:center">SUPERVISOR</td></tr></table></div></div><div style="width:196mm;margin:0 auto 1mm;text-align:right">HAL : ${escQ(data.halaman||'1 / 1')}</div>`;
        let bodyQ='';
        rowsQ.forEach((r,i)=>{
            const a=Array.isArray(r.actual)?r.actual:[r.actual1||'',r.actual2||'',r.actual3||''];
            bodyQ+=`<tr>${i===0?`<td rowspan="${rowsQ.length}">1</td><td rowspan="${rowsQ.length}">${escQ(data.no_part)}</td><td rowspan="${rowsQ.length}">${escQ(data.spesifikasi)}</td>`:''}<td>${escQ(r.std_spesifikasi)}</td><td>${escQ(r.std_no_alat)}</td><td class="q-param">Ø ${escQ(r.bagian||'')}</td><td class="q-std">${stdQ(r)}</td><td>${escQ(a[0])}</td><td>${escQ(a[1])}</td><td>${escQ(a[2])}</td>${i===0?`<td rowspan="${rowsQ.length}">${escQ(q.kode_warna||data.kode_warna)}</td><td rowspan="${rowsQ.length}" class="q-kep">${escQ(q.keputusan||data.status)}</td><td rowspan="${rowsQ.length}">${dateShortQ(data.jadwal_berikutnya)}</td>`:''}</tr>`;
        });
        const tableQ=`<table class="grid-q q-clean"><thead><tr><th rowspan="2" style="width:4%">NO</th><th rowspan="2" style="width:9%">NO ALAT</th><th rowspan="2" style="width:10%">SPESIFIKASI</th><th colspan="2">ALAT STANDAR YANG DIPAKAI</th><th rowspan="2" style="width:8%">PARAMETER</th><th rowspan="2" style="width:18%">STANDAR UKURAN</th><th colspan="3">HASIL UKUR (CROSS)</th><th rowspan="2" style="width:8%">KODE WARNA</th><th rowspan="2" style="width:6%">KEP</th><th rowspan="2" style="width:10%">TANGGAL KALIBRASI BERIKUTNYA</th></tr><tr><th style="width:10%">SPESIFIKASI</th><th style="width:8%">NO ALAT</th><th>I</th><th>II</th><th>III</th></tr></thead><tbody>${bodyQ}</tbody></table>`;
        const ketQ=data.keterangan===null||data.keterangan===undefined?'':String(data.keterangan);
        document.getElementById('report').innerHTML=`<div class="report report-q">${headerQ}${tableQ}<div class="ket-q"><b>KETERANGAN :</b><br>${qaKeterangan(ketQ)}</div><div class="foot-q">STIS-CMFG4-06 / R1</div></div>`;
        return;
    }

// ================= FORM P (SK GAUGE) =================
    if (String(data.format || '').toUpperCase() === 'P') {
        const esc = (v) => {
            if (v === null || v === undefined || String(v).trim() === '') return '///';
            return String(v).trim()
                .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
        };
        const dateShort = (v) => {
            if (!v) return '///';
            const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return m ? m[3]+' - '+m[2]+' - '+m[1] : esc(v);
        };
        const dateLong = (v) => {
            if (!v) return '///';
            const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return esc(v);
            const bulan=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            return m[3]+' '+bulan[parseInt(m[2],10)-1]+' '+m[1];
        };
        const items = Array.isArray(data.hasil_array) ? data.hasil_array : [];

        /* Format P = satu alat utama / satu nomor laporan.
           TYPE + alat yang dipakai adalah sub-group di bawah NO 1. */
        function pStdPrint(m) {
            const std = String(m.std_label ?? '').trim();
            const mode = String(m.toleransi_mode || ((m.tol_plus !== undefined || m.tol_minus !== undefined) ? 'range' : 'pm'));
            if (mode === 'range') {
                const plus = String(m.tol_plus ?? '').trim();
                const minus = String(m.tol_minus ?? '').trim();
                const signed = (v) => {
                    if (!v) return '';
                    return /^[+-]/.test(v) ? v : '+' + v;
                };
                if (plus || minus) return std + ' ' + signed(plus) + '/' + signed(minus);
            }
            const tol = String(m.toleransi ?? '').trim();
            return std + (tol ? ' ±' + tol : '');
        }

        const totalRows = Math.max(1, items.reduce((sum, item) => {
            const ms = Array.isArray(item.measurements) ? item.measurements : [];
            return sum + Math.max(ms.length, 1);
        }, 0));

        const mainNoAlat = data.no_part || data.no_alat || (items[0] && items[0].no_alat) || '';
        const sharedKeputusan = data.keputusan || (items[0] && items[0].keputusan) || '';
        // No Gambar adalah metadata report-level. Untuk data lama, fallback cari
        // dari seluruh group/measurement karena tidak selalu berada di row pertama.
        const sharedNoGambar = pick(
            data.no_gambar,
            ...items.map(x => x && x.no_gambar),
            ...items.flatMap(x => (Array.isArray(x && x.measurements) ? x.measurements : []).map(m => m && m.no_gambar))
        );
        let globalRowIndex = 0;

        function makeItemRows(item) {
            let ms = Array.isArray(item.measurements) ? item.measurements : [];
            if (!ms.length) ms = [{}];
            const n = ms.length;

            return ms.map((m, j) => {
                const firstInGroup = j === 0;
                const firstOverall = globalRowIndex === 0;
                let cells = '';

                // NO, NO ALAT, KEP, tanggal berikutnya, dan NO GAMBAR hanya satu
                // logical tool dan di-rowspan ke seluruh detail Format P.
                if (firstOverall) {
                    cells += `<td rowspan="${totalRows}">1</td>`;
                    cells += `<td rowspan="${totalRows}">${esc(mainNoAlat)}</td>`;
                }

                // Setiap TYPE DAN UKURAN adalah sub-group di bawah NO 1.
                // Nama alat ukur + no alat ikut rowspan sepanjang STD dalam group tersebut.
                if (firstInGroup) {
                    const typeHtml = esc(item.type || item.type_ukuran || '') +
                        (item.ukuran ? '<br>'+esc(item.ukuran) : '') +
                        (item.upart ? '<br>'+esc(item.upart) : '');
                    cells += `<td rowspan="${n}" class="type-cell">${typeHtml}</td>`;
                    cells += `<td rowspan="${n}">${esc(item.alat_nama || (m && m.alat_nama))}</td>`;
                    cells += `<td rowspan="${n}">${esc(item.alat_no || (m && m.alat_no))}</td>`;
                }

                const stdDisplay = esc(pStdPrint(m));
                const dec = (v) => { const x=String(v??'').replace(',','.').replace(/^[+-]/,''); return x.includes('.') ? x.split('.')[1].length : 0; };
                const rangeDp = String(m.toleransi_mode||'pm')==='range' ? Math.max(2,dec(m.tol_plus),dec(m.tol_minus)) : Math.max(dec(m.std_label),dec(m.toleransi));
                const rangeFmt = (v) => { const n=Number(v); if(!Number.isFinite(n)) return esc(v); return n.toFixed(rangeDp); };
                const rangeDisplay = `( ${rangeFmt(m.min)} ~ ${rangeFmt(m.max)} )`;
                cells += `<td class="std-label"><div>${stdDisplay}</div><div style="margin-top:2px;font-size:0.92em;white-space:nowrap;">${rangeDisplay}</div></td>`;
                // Aktual 1 & Aktual 2 tetap berada dalam satu cell tanpa garis pemisah.
                // Beri jarak vertikal agar keduanya terbaca sebagai dua hasil pengukuran.
                cells += `<td class="actual-p"><div>${esc(m.actual1)}</div><div style="margin-top:4px;">${esc(m.actual2)}</div></td>`;

                if (firstOverall) {
                    cells += `<td rowspan="${totalRows}" style="font-weight:bold;">${esc(sharedKeputusan)}</td>`;
                    cells += `<td rowspan="${totalRows}">${dateShort(data.jadwal_berikutnya)}</td>`;
                    cells += `<td rowspan="${totalRows}">${esc(sharedNoGambar)}</td>`;
                }

                globalRowIndex++;
                return `<tr>${cells}</tr>`;
            }).join('');
        }

        const body = items.map(makeItemRows).join('');
        const ket = data.keterangan === null || data.keterangan === undefined ? '' : String(data.keterangan);
        const ketHtml = ket.trim() ? esc(ket).replace(/\n/g,'<br>') : '';

        document.getElementById('report').innerHTML = `
        <div class="report report-p">
            <div class="company-p">PT. SURYA TOTO INDONESIA Tbk.</div>

            <div class="top-p">
                <div class="lefthead-p">
                    <div class="title-p">LAPORAN PEMERIKSAAN GAUGE</div>
                    <div class="head-p">
                        <table>
                    <tr><td>NO. LAPORAN</td><td>: ${esc(data.no_laporan)}</td><td>ALAT STANDAR</td><td>: ${esc(data.standar_uji)}</td></tr>
                    <tr><td>NAMA ALAT</td><td>: ${esc(data.nama_alat)}</td><td>TRACEABILITY</td><td>: ${esc(data.traceability)}</td></tr>
                    <tr><td>METODE</td><td>: ${esc(data.metode)}</td><td>SUHU / HUMIDITY</td><td>: ${esc(qaSuhuValue(data.suhu))} °C / ${esc(qaHumidityValue(data.kelembapan))} %</td></tr>
                    <tr><td>SEKSI</td><td>: ${esc(data.seksi)}</td><td>TANGGAL PERIKSA</td><td>: ${dateLong(data.tanggal_periksa)}</td></tr>
                        </table>
                    </div>
                </div>
                <div class="datebox-p">
                    <table>
                        <tr><td colspan="3">TANGGAL : ${dateLong(data.tanggal_periksa)}</td></tr>
                        <tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr>
                        <tr style="height:15mm;"><td style="vertical-align:bottom;text-align:center;">${qaSignatureName(data.operator || data.petugas)}<br>${qaApprovalDate('pembuat')}</td><td style="vertical-align:bottom;text-align:center;">${qaSignatureName(data.pemeriksa || data.foreman)}<br>${qaApprovalDate('pemeriksa')}</td><td style="vertical-align:bottom;text-align:center;">${qaSignatureName(data.penyetuju || data.supervisor)}<br>${qaApprovalDate('penyetuju')}</td></tr>
                        <tr><td style="text-align:center;">PETUGAS</td><td style="text-align:center;">FOREMAN</td><td style="text-align:center;">SUPERVISOR</td></tr>
                    </table>
                </div>
            </div>

            <div style="width:196mm;margin:0 auto 1mm;text-align:right;">HAL : ${esc(data.halaman || '1 / 1')}</div>

            <table class="grid-p">
                <thead>
                    <tr>
                        <th rowspan="2" style="width:4%;">NO</th>
                        <th rowspan="2" style="width:9%;">NO ALAT</th>
                        <th rowspan="2" style="width:12%;">TYPE DAN UKURAN</th>
                        <th colspan="2">ALAT YANG DIPAKAI</th>
                        <th colspan="2">HASIL PERIKSA</th>
                        <th rowspan="2" style="width:6%;">KEP</th>
                        <th rowspan="2" style="width:10%;">TANGGAL PERIKSA BERIKUTNYA</th>
                        <th rowspan="2" style="width:9%;">NO GAMBAR</th>
                    </tr>
                    <tr>
                        <th style="width:10%;">NAMA ALAT</th>
                        <th style="width:9%;">NO ALAT</th>
                        <th style="width:16%;">STD UKURAN</th>
                        <th style="width:10%;">AKTUAL</th>
                    </tr>
                </thead>
                <tbody>${body || '<tr><td colspan="10">Tidak ada data.</td></tr>'}</tbody>
            </table>

            <div class="ket-p"><b>KETERANGAN :</b><br>${qaKeterangan(ket)}</div>
            <div class="foot-p">STIS-CMFG4-06 / R1</div>
        </div>`;
        return;
    }

// ================= FORM O (SNAP GAUGE) =================
    if (String(data.format || '').toUpperCase() === 'O') {
        const esc = (v) => {
            if (v === null || v === undefined || String(v).trim() === '') return '///';
            return String(v).trim()
                .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
        };
        const dateShort = (v) => {
            if (!v) return '///';
            const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return m ? m[3]+' - '+m[2]+' - '+m[1] : esc(v);
        };
        const dateLong = (v) => {
            if (!v) return '///';
            const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return esc(v);
            const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            return m[3]+' '+bulan[parseInt(m[2],10)-1]+' '+m[1];
        };
        const cleanTolO = (v) => {
            const x = (v === null || v === undefined) ? '' : String(v).trim();
            if (!x) return '///';
            return esc(x.replace(/^(?:±\s*)+/,'').trim());
        };
        const numO = (v) => {
            if (v === null || v === undefined || String(v).trim() === '') return NaN;
            const m = String(v).trim().replace(/[±%]/g,'').replace(/,/g,'.').match(/[+-]?\d+(?:\.\d+)?/);
            return m ? parseFloat(m[0]) : NaN;
        };
        const fmtO = (v) => Number.isFinite(v) ? Number(v).toFixed(3) : '';
        const rangeO = (obj, isGo) => {
            obj = obj || {};
            const std = numO(isGo ? ov(obj,['go_std','standar','std']) : ov(obj,['notgo_std','not_go_std','standar','std']));
            let min = NaN, max = NaN;
            if (isGo) {
                const near = numO(ov(obj,['go_tol_near','tol_near','toleransi_near','toleransi']));
                const far = numO(ov(obj,['go_tol_far','tol_far','toleransi_far']));
                if (Number.isFinite(std) && Number.isFinite(near) && Number.isFinite(far)) {
                    min = std + Math.min(near, far);
                    max = std + Math.max(near, far);
                }
            } else {
                const tol = numO(ov(obj,['notgo_tol','not_go_tol','toleransi','tol']));
                if (Number.isFinite(std) && Number.isFinite(tol)) {
                    min = std - Math.abs(tol);
                    max = std + Math.abs(tol);
                }
            }
            return {min:min,max:max};
        };
        const normalizeStatusO = (v) => {
            const x = String(v == null ? '' : v).trim().toUpperCase();
            if (!x) return '';
            if (/^(OK|GOOD|PASS|LULUS)$/.test(x)) return 'OK';
            if (/^(NG|NOT GOOD|TIDAK|FAIL|X)$/.test(x)) return 'NOT GOOD';
            return x;
        };
        const statusFromValueO = (value, min, max) => {
            const n = numO(value);
            if (!Number.isFinite(n) || !Number.isFinite(min) || !Number.isFinite(max)) return '';
            return (n >= min - 1e-9 && n <= max + 1e-9) ? 'OK' : 'NOT GOOD';
        };
        const rows = Array.isArray(data.hasil_array) ? data.hasil_array : [];

        /* Format O terbaru menyimpan GO dan NOT GO sebagai dua row hasil.
           Di report keduanya harus tetap dianggap SATU nomor/al alat. */
        const groupsO = [];
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i] || {};
            const pos = String(row.posisi || row.posisi_ukur || row.jenis || '').toUpperCase().replace(/\s+/g, ' ').trim();

            // Payload update Format O dapat menyimpan GO dan NOT GO sebagai row
            // terpisah. NOT GO tidak boleh menjadi nomor baru: pasangkan ke GO
            // sebelumnya/berikutnya sebagai sub-baris dari nomor yang sama.
            if (pos === 'NOT GO') {
                if (groupsO.length && !Object.keys(groupsO[groupsO.length - 1].ng || {}).length) {
                    groupsO[groupsO.length - 1].ng = row;
                } else {
                    groupsO.push({base: row, go: {}, ng: row});
                }
                continue;
            }
            if (pos === 'GO') {
                const next = rows[i + 1] || {};
                const nextPos = String(next.posisi || next.posisi_ukur || next.jenis || '').toUpperCase().replace(/\s+/g, ' ').trim();
                groupsO.push({base: row, go: row, ng: nextPos === 'NOT GO' ? next : {}});
                if (nextPos === 'NOT GO') i++;
                continue;
            }

            // Kompatibilitas data lama: satu object sudah memuat field GO/NOT GO.
            groupsO.push({base: row, go: row, ng: row});
        }

        const ov = (a, names, fallback) => {
            for (const n of names) {
                if (a && a[n] !== undefined && a[n] !== null && String(a[n]).trim() !== '') return a[n];
            }
            return fallback === undefined ? '' : fallback;
        };

        // Buang nomor duplikat Format O. Payload lama kadang mengirim satu
        // set lengkap GO/NOT GO lalu satu row tambahan untuk alat yang sama.
        // Report cukup menampilkan set pertama agar tidak muncul NO 2 kosong/duplikat.
        const seenO = new Set();
        const printGroupsO = groupsO.filter((g) => {
            const r = g.base || {};
            const go = g.go || {};
            const ng = g.ng || {};
            const key = String(ov(r,['no_alat','no_part'], ov(go,['no_alat','no_part'], ov(ng,['no_alat','no_part'], data.no_alat || data.no_part)))).trim().toUpperCase();
            if (!key) return true;
            if (seenO.has(key)) return false;
            seenO.add(key);
            return true;
        });

        const body = printGroupsO.map((g,i) => {
            const r = g.base || {};
            const go = g.go || {};
            const ng = g.ng || {};
            const goStd = ov(go, ['go_std','standar','std']);
            const goTolNear = ov(go, ['go_tol_near','tol_near','toleransi_near','toleransi']);
            const goTolFar = ov(go, ['go_tol_far','tol_far','toleransi_far']);
            const goActualMin = ov(go, ['go_hasil_min','hasil_min','hasil']);
            const goActualMax = ov(go, ['go_hasil_max','hasil_max','hasil_2']);
            const goRange = rangeO(go,true);
            const goMin = Number.isFinite(goRange.min) ? fmtO(goRange.min) : ov(go, ['go_min','min']);
            const goMax = Number.isFinite(goRange.max) ? fmtO(goRange.max) : ov(go, ['go_max','max']);
            const goHMin = normalizeStatusO(ov(go,['go_status_min','status_min'])) || statusFromValueO(goActualMin,numO(goMin),numO(goMax));
            const goHMax = normalizeStatusO(ov(go,['go_status_max','status_max'])) || statusFromValueO(goActualMax,numO(goMin),numO(goMax));

            const ngStd = ov(ng, ['notgo_std','not_go_std','standar','std']);
            const ngTol = ov(ng, ['notgo_tol','not_go_tol','toleransi','tol']);
            const ngActualMin = ov(ng, ['notgo_hasil_min','not_go_hasil_min','hasil_min','hasil']);
            const ngActualMax = ov(ng, ['notgo_hasil_max','not_go_hasil_max','hasil_max','hasil_2']);
            const ngRange = rangeO(ng,false);
            const ngMin = Number.isFinite(ngRange.min) ? fmtO(ngRange.min) : ov(ng, ['notgo_min','not_go_min','min']);
            const ngMax = Number.isFinite(ngRange.max) ? fmtO(ngRange.max) : ov(ng, ['notgo_max','not_go_max','max']);
            const ngHMin = normalizeStatusO(ov(ng,['notgo_status_min','status_min'])) || statusFromValueO(ngActualMin,numO(ngMin),numO(ngMax));
            const ngHMax = normalizeStatusO(ov(ng,['notgo_status_max','status_max'])) || statusFromValueO(ngActualMax,numO(ngMin),numO(ngMax));

            // Format O: satu NO terdiri dari dua sub-baris GO + NOT GO.
            // Field identitas hanya dicetak sekali dengan rowspan=2, jadi tidak
            // pernah terbentuk NO kedua hanya karena row NOT GO terpisah di payload.
            const noAlat = ov(r,['no_alat','no_part'], ov(go,['no_alat','no_part'], ov(ng,['no_alat','no_part'], data.no_alat || data.no_part)));
            const spesifikasi = ov(r,['spesifikasi'], ov(go,['spesifikasi'], ov(ng,['spesifikasi'], data.spesifikasi)));
            const tolPlus = ov(r,['tol_plus'], ov(go,['tol_plus'], ov(ng,['tol_plus'])));
            const tolMinus = ov(r,['tol_minus'], ov(go,['tol_minus'], ov(ng,['tol_minus'])));
            const kodeWarna = ov(r,['kode_warna'], ov(go,['kode_warna'],ov(ng,['kode_warna'])));
            const nextDate = data.jadwal_berikutnya || data.periksa_berikut || ov(r,['periksa_berikut'],ov(go,['periksa_berikut'],ov(ng,['periksa_berikut'])));
            const noGambar = ov(r,['no_gambar'],ov(go,['no_gambar'],ov(ng,['no_gambar'],data.no_gambar)));

            return `
            <tr>
                <td rowspan="2">${i+1}</td>
                <td rowspan="2">${esc(noAlat)}</td>
                <td rowspan="2" class="spec-cell">${esc(spesifikasi)}<br>${esc(tolPlus)}<br>${esc(tolMinus)}</td>
                <td>GO</td>
                <td>${esc(goStd)}</td>
                <td>${cleanTolO(goTolNear)}${String(goTolFar||'').trim() ? '<br>'+cleanTolO(goTolFar) : ''}</td>
                <td>${esc(goMin)}</td>
                <td>${esc(goMax)}</td>
                <td>${esc(goHMin)}</td>
                <td>${esc(goHMax)}</td>
                <td rowspan="2">${esc(kodeWarna)}</td>
                <td rowspan="2" style="font-weight:bold;">${esc(data.status_final || data.keputusan)}</td>
                <td rowspan="2">${dateShort(nextDate)}</td>
                <td rowspan="2">${esc(noGambar)}</td>
            </tr>
            <tr>
                <td>NOT GO</td>
                <td>${esc(ngStd)}</td>
                <td>${cleanTolO(ngTol)}</td>
                <td>${esc(ngMin)}</td>
                <td>${esc(ngMax)}</td>
                <td>${esc(ngHMin)}</td>
                <td>${esc(ngHMax)}</td>
            </tr>`;
        }).join('');

        document.getElementById('report').innerHTML = `
        <div class="report report-o">
            <div class="company-o">PT. SURYA TOTO INDONESIA Tbk.</div>
            <div class="top-o">
                <div class="lefthead-o">
                    <div class="title-o">LAPORAN PEMERIKSAAN GAUGE</div>
                    <div class="head-o">
                        <table>
                            <tr><td>NO LAPORAN</td><td>: ${esc(data.no_laporan)}</td><td>STANDAR UJI / NO ALAT</td><td>: ${esc(data.standar_uji)}</td></tr>
                            <tr><td>NAMA ALAT</td><td>: ${esc(data.nama_alat)}</td><td>TRACEABILITY</td><td>: ${esc(data.traceability)}</td></tr>
                            <tr><td>METODE</td><td>: ${esc(data.metode)}</td><td>SUHU / HUMIDITY</td><td>: ${esc(data.suhu)} / ${esc(qaHumidityValue(data.kelembapan))} %</td></tr>
                            <tr><td>SEKSI</td><td>: ${esc(data.seksi)}</td><td>TANGGAL PERIKSA</td><td>: ${dateLong(data.tanggal_periksa)}</td></tr>
                        </table>
                    </div>
                </div>
                <div class="datebox-o">
                    <table>
                        <tr><td colspan="3">TANGGAL : ${dateLong(data.tanggal_periksa)}</td></tr>
                        <tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr>
                        <tr><td>${qaSignatureName(data.operator || data.petugas)}<br>${qaApprovalDate('pembuat')}</td><td>${qaSignatureName(data.pemeriksa || data.foreman)}<br>${qaApprovalDate('pemeriksa')}</td><td>${qaSignatureName(data.penyetuju || data.supervisor)}<br>${qaApprovalDate('penyetuju')}</td></tr>
                        <tr><td>PETUGAS</td><td>FOREMAN</td><td>SUPERVISOR</td></tr>
                    </table>
                </div>
            </div>
            <div style="width:196mm;margin:0 auto 1mm;text-align:right;">${esc(data.halaman || '1 / 1')}</div>
            <table class="grid-o">
                <thead>
                    <tr>
                        <th rowspan="3" style="width:4%;">NO</th>
                        <th rowspan="3" style="width:10%;">NO ALAT</th>
                        <th rowspan="3" style="width:8%;">SPESIFIKASI</th>
                        <th colspan="7">HASIL PERIKSA</th>
                        <th rowspan="3" style="width:6%;">KODE WARNA</th>
                        <th rowspan="3" style="width:6%;">KEPUTUSAN</th>
                        <th rowspan="3" style="width:9%;">TANGGAL PERIKSA BERIKUTNYA</th>
                        <th rowspan="3" style="width:9%;">NO GAMBAR</th>
                    </tr>
                    <tr>
                        <th colspan="5">STD UKURAN</th>
                        <th colspan="2">STANDAR HASIL</th>
                    </tr>
                    <tr>
                        <th style="width:7%;">POSISI UKUR</th>
                        <th style="width:7%;">TOLERANSI ALAT</th>
                        <th style="width:8%;">TOLERANSI</th>
                        <th style="width:7%;">MIN</th>
                        <th style="width:7%;">MAX</th>
                        <th style="width:6%;">MIN<br>(MASUK)</th>
                        <th style="width:6%;">MAX<br>(TIDAK MASUK)</th>
                    </tr>
                </thead>
                <tbody>${body || '<tr><td colspan="14">Tidak ada data.</td></tr>'}</tbody>
            </table>
            <div class="ket-o"><b>KETERANGAN :</b> ${qaKeterangan(data.keterangan)}</div>
            <div class="foot-o">STIS-CMFG4-06 / R1</div>
        </div>`;
        return;
    }

// ================= FORM M (PROFILE PROJECTOR) =================
    if (String(data.format || '').toUpperCase() === 'M') {
        const esc = (v) => {
            if (v === null || v === undefined || String(v).trim() === '') return '///';
            return String(v).trim()
                .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
        };

        const raw = (v) => v === null || v === undefined ? '' : String(v).trim();

        const dateShort = (v) => {
            if (!v) return '///';
            const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return m ? m[3]+' - '+m[2]+' - '+m[1] : esc(v);
        };

        const dateLong = (v) => {
            if (!v) return '///';
            const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return esc(v);
            const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            return m[3]+' '+bulan[parseInt(m[2],10)-1]+' '+m[1];
        };

        const rows = Array.isArray(data.hasil_array) ? data.hasil_array : [];
        const axisRows = rows.filter(r => String(r.jenis || '').toUpperCase() === 'AXIS');
        const angleRows = rows.filter(r => String(r.jenis || '').toUpperCase() === 'ANGLE');

        const axisBody = axisRows.map(r => `
            <tr>
                <td>${esc(r.std_mm || r.std_x)}</td>
                <td>${esc(r.x_left)}</td>
                <td>${esc(r.x_left_correction)}</td>
                <td>${esc(r.x_right)}</td>
                <td>${esc(r.x_right_correction)}</td>
                <td>${esc(r.y_front)}</td>
                <td>${esc(r.y_front_correction)}</td>
                <td>${esc(r.y_back)}</td>
                <td>${esc(r.y_back_correction)}</td>
            </tr>
        `).join('');

        const angleBody = angleRows.map((r, i) => {
            const hasil = `${esc(r.hasil_degree)} ° ${esc(r.hasil_minute)} ' ${esc(r.hasil_second)} "`;
            return `
                <tr>
                    <td>${i + 1}</td>
                    <td>${esc(r.std_degree || r.std_angle)} °</td>
                    <td>${hasil}</td>
                    <td>${esc(r.correction)}</td>
                </tr>
            `;
        }).join('');

        const keputusanRaw = String(data.keputusan || '').toUpperCase();
        const keputusan = (keputusanRaw === 'GOOD' || keputusanRaw === 'OK')
            ? 'OK'
            : ((keputusanRaw === 'NOT GOOD' || keputusanRaw === 'NOT GOOD') ? 'NOT GOOD' : esc(data.keputusan));

        const tolAxisFallback = axisRows.find(r => String(r.toleransi_x || r.toleransi_axis || '').trim() !== '');
        const tolAngleFallback = angleRows.find(r => String(r.toleransi_angle || r.toleransi_sudut || '').trim() !== '');
        const tolAxisText = raw(data.toleransi_axis) ? data.toleransi_axis : (tolAxisFallback ? (tolAxisFallback.toleransi_x || tolAxisFallback.toleransi_axis || '') : '');
        const tolAngleText = raw(data.toleransi_sudut) ? data.toleransi_sudut : (tolAngleFallback ? (tolAngleFallback.toleransi_angle || tolAngleFallback.toleransi_sudut || '') : '');
        const tolSudutMenit = parseFloat(tolAngleText);
        const tolSudutDetik = Number.isFinite(tolSudutMenit)
            ? (tolSudutMenit * 60)
            : null;
        const tolSudutDetikText = tolSudutDetik === null
            ? '///'
            : String(Number(tolSudutDetik.toFixed(4)));

        document.getElementById('report').innerHTML = `
        <div class="report report-m">
            <div class="company-m">PT.SURYA TOTO INDONESIA Tbk.</div>
            <div class="title-m">LAPORAN KALIBRASI</div>

            <table class="info-m">
                <tr>
                    <td>NO LAPORAN</td><td>${esc(data.no_laporan)}</td>
                    <td>SEKSI</td><td>${esc(data.seksi_pemilik || data.seksi)}</td>
                </tr>
                <tr>
                    <td>NAMA ALAT</td><td>${esc(data.nama_part || data.nama_alat)}</td>
                    <td>PETUGAS KALIBRASI</td><td>${qaSignatureName(data.operator || data.petugas)}<br>${qaApprovalDate('pembuat')}</td>
                </tr>
                <tr>
                    <td>NOMOR ALAT</td><td>${esc(data.no_part || data.no_alat)}</td>
                    <td></td><td></td>
                </tr>
                <tr>
                    <td>SPESIFIKASI</td><td>${esc(data.spesifikasi)}</td>
                    <td>TANGGAL KALIBRASI</td><td>${dateShort(data.tanggal_periksa)}</td>
                </tr>
                <tr>
                    <td>RESOLUSI</td><td>${esc(data.resolusi)}</td>
                    <td></td><td></td>
                </tr>
            </table>

            <div class="section-m">HASIL KALIBRASI</div>

            <table class="axis-m">
                <thead>
                    <tr>
                        <th rowspan="3" style="width:10%;">STD<br>(mm)</th>
                        <th colspan="4">X AXIS</th>
                        <th colspan="4">Y AXIS</th>
                    </tr>
                    <tr>
                        <th colspan="2">Left ( mm )</th>
                        <th colspan="2">Right ( mm )</th>
                        <th colspan="2">Front ( mm )</th>
                        <th colspan="2">Back ( mm )</th>
                    </tr>
                    <tr>
                        <th>Hasil</th><th>Koreksi</th>
                        <th>Hasil</th><th>Koreksi</th>
                        <th>Hasil</th><th>Koreksi</th>
                        <th>Hasil</th><th>Koreksi</th>
                    </tr>
                </thead>
                <tbody>
                    ${axisBody || '<tr><td colspan="9">Tidak ada data AXIS.</td></tr>'}
                </tbody>
            </table>

            <div class="section-m">ANGLE ACCURACY</div>

            <table class="angle-m">
                <thead>
                    <tr>
                        <th style="width:12%;">No</th>
                        <th style="width:24%;">STD ( ° )</th>
                        <th style="width:40%;">HASIL ( ° , ' , " )</th>
                        <th style="width:24%;">KOREKSI</th>
                    </tr>
                </thead>
                <tbody>
                    ${angleBody || '<tr><td colspan="4">Tidak ada data ANGLE.</td></tr>'}
                </tbody>
            </table>

            <table class="detail-m">
                <tr>
                    <td>METODE</td><td>${esc(data.metode)}</td>
                    <td>KELEMBABAN RUANGAN</td><td>${raw(data.kelembapan) ? esc(qaHumidityValue(data.kelembapan))+' %' : '///'}</td>
                </tr>
                <tr>
                    <td>STANDAR UJI</td>
                    <td>${esc(data.standar_uji)}<br>${esc(data.standar_uji_sudut)}</td>
                    <td>KETIDAKPASTIAN</td>
                    <td>${raw(data.ketidakpastian) ? esc(data.ketidakpastian) : '///'}</td>
                </tr>
                <tr>
                    <td>TRACEABILITY</td><td>${esc(data.traceability)}</td>
                    <td>STANDAR TOLERANSI</td>
                    <td>
                        AXIS&nbsp;&nbsp;± ${esc(tolAxisText)} mm<br>
                        SUDUT&nbsp;&nbsp;± ${esc(tolAngleText)}' (${tolSudutDetikText}")
                    </td>
                </tr>
                <tr>
                    <td>CONFIDENCE LEVEL</td><td>${raw(data.confidence) ? esc(data.confidence)+' %' : '///'}</td>
                    <td>KEPUTUSAN</td><td style="font-weight:bold;text-align:center;">${keputusan}</td>
                </tr>
                <tr>
                    <td>SUHU RUANGAN</td><td>${esc(data.suhu)}</td>
                    <td>KALIBRASI BERIKUTNYA</td><td>${dateShort(data.jadwal_berikutnya || data.periksa_berikut)}</td>
                </tr>
                <tr>
                    <td colspan="4" class="ket-m">
                        <b>KETERANGAN :</b><br>${qaKeterangan(data.keterangan)}
                    </td>
                </tr>
            </table>

            <div class="city-date-m">TANGERANG, ${dateLong(data.tanggal_periksa)}</div>

            <table class="sign-m">
                <tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr>
                <tr class="sign-space-m">
                    <td>${qaSignatureName(data.operator || data.petugas)}<br>${qaApprovalDate('pembuat')}</td>
                    <td>${qaSignatureName(data.pemeriksa || data.foreman)}<br>${qaApprovalDate('pemeriksa')}</td>
                    <td>${qaSignatureName(data.penyetuju || data.supervisor)}<br>${qaApprovalDate('penyetuju')}</td>
                </tr>
                <tr class="role-m">
                    <td>PETUGAS</td><td>PEMERIKSA</td><td>PENYETUJU</td>
                </tr>
            </table>

            <div class="doc-code-m">STIS-CMFG4-03/R4</div>
        </div>`;

        return;
    }

// ================= FORM L (PRESSURE GAUGE) =================
    if(String(data.format||'').toUpperCase()==='L'){
        const e=v=>(v===null||v===undefined||String(v).trim()===''?'///':String(v).trim().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'));
        const raw=v=>(v===null||v===undefined?'':String(v).trim());
        const dt=v=>{if(!v)return'///';const m=String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);return m?m[3]+' - '+m[2]+' - '+m[1]:e(v)};
        const dl=v=>{if(!v)return'///';const m=String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);if(!m)return e(v);const b=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];return m[3]+' '+b[parseInt(m[2],10)-1]+' '+m[1]};
        const rows=Array.isArray(data.hasil_array)?data.hasil_array:[];
        const naik=rows.filter(r=>String(r.arah||'').toUpperCase()==='NAIK');
        const turun=rows.filter(r=>String(r.arah||'').toUpperCase()==='TURUN');
        const fmtCorr=v=>{const n=parseFloat(String(v??'').replace(',','.'));return Number.isFinite(n)?n.toFixed(4):e(v)};
        // Format L: kelompokkan UJI SKALA berdasarkan pilihan arah saat registrasi.
        // Satu cell NAIK/TURUN membentang sepanjang jumlah data pada section tersebut.
        const mk=(arr,arah)=>arr.map((r,i)=>`<tr>${i===0?`<td rowspan="${arr.length}" style="font-weight:bold;vertical-align:middle;text-align:center;writing-mode:vertical-rl;text-orientation:upright;letter-spacing:2px;">${e(arah)}</td>`:''}<td>${e(r.applied_value)}</td><td>${e(r.indicated_value)}</td><td>${fmtCorr(r.correction)}</td></tr>`).join('');
        const body=mk(naik,'NAIK')+mk(turun,'TURUN');
        const kr=String(data.keputusan||'').toUpperCase();
        const kep=(kr==='GOOD'||kr==='OK')?'OK':((kr==='NOT GOOD'||kr==='NOT GOOD')?'NOT GOOD':e(data.keputusan));

        document.getElementById('report').innerHTML=`<div class="report report-l">
            <div class="company-l">PT.SURYA TOTO INDONESIA Tbk.</div>
            <div class="title-l">LAPORAN KALIBRASI</div>
            <table class="info-l">
                <tr><td>NO. LAPORAN</td><td>${e(data.no_laporan)}</td><td>SEKSI</td><td>${e(data.seksi_pemilik||data.seksi)}</td></tr>
                <tr><td>NAMA ALAT</td><td>${e(data.nama_part||data.nama_alat)}</td><td>PETUGAS KALIBRASI</td><td>${qaSignatureName(data.operator || data.petugas)}<br>${qaApprovalDate('pembuat')}</td></tr>
                <tr><td>NOMOR ALAT</td><td>${e(data.no_part||data.no_alat)}</td><td></td><td></td></tr>
                <tr><td>SPESIFIKASI</td><td>${e(data.spesifikasi)}</td><td>TANGGAL KALIBRASI</td><td>${dt(data.tanggal_periksa)}</td></tr>
                <tr><td>RESOLUSI</td><td>${e(data.resolusi)}</td><td></td><td></td></tr>
            </table>
            <div class="hasil-title-l">HASIL KALIBRASI</div>
            <table class="hasil-l"><thead><tr><th>UJI<br>SKALA</th><th>APPLIED VALUE<br>( MPa )</th><th>INDICATED VALUE<br>( MPa )</th><th>CORRECTION<br>( MPa )</th></tr></thead><tbody>${body||'<tr><td colspan="4">Tidak ada data kalibrasi.</td></tr>'}</tbody></table>
            <table class="detail-l">
                <tr><td>METODE</td><td>${e(data.metode)}</td><td>KELEMBABAN RUANGAN</td><td>${raw(data.kelembapan)?e(qaHumidityValue(data.kelembapan))+' %':'///'}</td></tr>
                <tr><td>STANDAR UJI</td><td>${e(data.standar_uji)}${raw(data.std_no_alat)?'<br>'+e(data.std_no_alat):''}</td><td>KETIDAKPASTIAN</td><td>${raw(data.ketidakpastian)?e(data.ketidakpastian):'///'}</td></tr>
                <tr><td>TRACEABILITY</td><td>${e(data.traceability)}</td><td>STANDAR TOLERANSI</td><td>${raw(data.toleransi)?e(data.toleransi):'///'}</td></tr>
                <tr><td>CONFIDENCE LEVEL</td><td>${raw(data.confidence)?e(data.confidence):'///'}</td><td>KEPUTUSAN</td><td style="font-weight:bold;text-align:center">${kep}</td></tr>
                <tr><td>SUHU RUANGAN</td><td>${raw(data.suhu)?e(qaSuhuValue(data.suhu))+' °C':'///'}</td><td>KALIBRASI BERIKUTNYA</td><td>${dt(data.jadwal_berikutnya||data.periksa_berikut)}</td></tr>
                <tr><td colspan="4" class="ket-l"><b>KETERANGAN :</b><br>${qaKeterangan(data.keterangan)}</td></tr>
            </table>
            <div class="city-date-l">TANGERANG, ${dl(data.tanggal_periksa)}</div>
            <table class="sign-l"><tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr><tr class="sign-space-l"><td>${qaSignatureName(data.operator || data.petugas)}<br>${qaApprovalDate('pembuat')}</td><td>${qaSignatureName(data.pemeriksa || data.foreman)}<br>${qaApprovalDate('pemeriksa')}</td><td>${qaSignatureName(data.penyetuju || data.supervisor)}<br>${qaApprovalDate('penyetuju')}</td></tr><tr class="role-l"><td>PETUGAS</td><td>PEMERIKSA</td><td>PENYETUJU</td></tr></table>
            <div class="doc-code-l">STIS-CMFG4-03/R4</div>
        </div>`;return;
    }

// ================= FORM K (VOLTAGE ULTRASONIC) =================
    if(String(data.format||'').toUpperCase()==='K'){const e=v=>(v===null||v===undefined||String(v).trim()===''?'///':String(v).trim().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;')),raw=v=>(v===null||v===undefined?'':String(v).trim()),dt=v=>{if(!v)return'///';const m=String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);return m?m[3]+'-'+m[2]+'-'+m[1]:e(v)},dl=v=>{if(!v)return'///';const m=String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);if(!m)return e(v);const b=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];return m[3]+' '+b[parseInt(m[2],10)-1]+' '+m[1]},rows=Array.isArray(data.hasil_array)?data.hasil_array:[],body=rows.map((r,i)=>`<tr><td>${i+1}</td><td>${e(r.nilai_ditentukan)}</td><td>${e(r.nilai_pengukuran)}</td><td>${e(r.selisih)}</td><td>${e(r.status)}</td></tr>`).join(''),kr=String(data.keputusan||'').toUpperCase(),kep=(kr==='GOOD'||kr==='OK')?'OK':((kr==='NOT GOOD'||kr==='NOT GOOD')?'NOT GOOD':e(data.keputusan));document.getElementById('report').innerHTML=`<div class="report report-k"><div class="company-k">PT. SURYA TOTO INDONESIA Tbk.</div><div class="title-k">LAPORAN PEMERIKSAAN</div><table class="info-k"><tr><td>NO LAPORAN</td><td>${e(data.no_laporan)}</td><td>SEKSI</td><td style="font-weight:bold;text-align:center">${e(data.seksi_pemilik||data.seksi)}</td></tr><tr><td>NAMA ALAT</td><td>${e(data.nama_part||data.nama_alat)}</td><td>PETUGAS PERIKSA</td><td style="text-align:center">${qaSignatureName(data.operator || data.petugas)}<br>${qaApprovalDate('pembuat')}</td></tr><tr><td>NOMOR MESIN</td><td>${e(data.no_part||data.no_alat)}</td><td>TANGGAL PERIKSA</td><td style="text-align:center">${dt(data.tanggal_periksa)}</td></tr><tr><td>SPESIFIKASI</td><td>${e(data.spesifikasi)}</td><td></td><td></td></tr><tr><td>RESOLUSI</td><td>${e(data.resolusi)}</td><td></td><td></td></tr></table><div class="hasil-title-k">HASIL PEMERIKSAAN</div><table class="hasil-k"><thead><tr><th>NO</th><th>NILAI YG DITENTUKAN</th><th>NILAI PENGUKURAN</th><th>SELISIH</th><th>PERTIMBANGAN KELULUSAN</th></tr></thead><tbody>${body||'<tr><td colspan="5">Tidak ada data.</td></tr>'}<tr class="blank-k"><td></td><td></td><td></td><td></td><td></td></tr></tbody></table><table class="detail-k"><tr><td>METODE</td><td>${e(data.metode)}</td><td>KELEMBABAN RUANGAN</td><td>${raw(data.kelembapan)?e(qaHumidityValue(data.kelembapan))+' %':'///'}</td></tr><tr><td>STANDAR UJI</td><td>${e(data.standar_uji)}</td><td>STANDAR TOLERANSI</td><td style="text-align:center">${raw(data.toleransi)?e(data.toleransi)+' %':'///'}</td></tr><tr><td>TRACEABILITY</td><td>${e(data.traceability)}</td><td>KEPUTUSAN</td><td style="font-weight:bold;text-align:center">${kep}</td></tr><tr><td>SUHU RUANGAN</td><td>${raw(data.suhu)?e(qaSuhuValue(data.suhu))+' °C':'///'}</td><td>TGL PERIKSA BERIKUTNYA</td><td style="text-align:center">${dt(data.jadwal_berikutnya||data.periksa_berikut)}</td></tr><tr><td colspan="4" class="ket-k"><b>KETERANGAN :</b><br>${qaKeterangan(data.keterangan)}</td></tr></table><div class="city-date-k">TANGERANG, ${dl(data.tanggal_periksa)}</div><table class="sign-k"><tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr><tr class="sign-space-k"><td>${qaSignatureName(data.operator || data.petugas)}<br>${qaApprovalDate('pembuat')}</td><td>${qaSignatureName(data.pemeriksa || data.foreman)}<br>${qaApprovalDate('pemeriksa')}</td><td>${qaSignatureName(data.penyetuju || data.supervisor)}<br>${qaApprovalDate('penyetuju')}</td></tr><tr class="role-k"><td>PETUGAS</td><td>PEMERIKSA</td><td>PENYETUJU</td></tr></table><div class="doc-code-k">STIS-CMFG4-05/R3</div></div>`;return;}

// ================= FORM J (VERIFIKASI RADIUS GAUGE) =================
    if (String(data.format || '').toUpperCase() === 'J') {
        const escJ = (v) => {
            if (v === null || v === undefined || String(v).trim() === '') return '///';
            return String(v).trim()
                .replace(/&/g,'&amp;').replace(/</g,'&lt;')
                .replace(/>/g,'&gt;').replace(/"/g,'&quot;')
                .replace(/'/g,'&#039;');
        };

        const dateJ = (v) => {
            if (!v) return '///';
            const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return escJ(v);
            return m[3]+'-'+m[2]+'-'+m[1];
        };

        const dateLongJ = (v) => {
            if (!v) return '///';
            const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return escJ(v);
            const bulan=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            return m[3]+' '+bulan[parseInt(m[2],10)-1]+' '+m[1];
        };

        const rowsJ = Array.isArray(data.hasil_array) ? data.hasil_array : [];
        const firstJ = rowsJ[0] || {};
        const hasilJ = String(firstJ.hasil_verifikasi || '').toUpperCase();

        const bodyJ = rowsJ.map((r, i) => `
            <tr class="data-row-j">
                <td>${i + 1}</td>
                <td>${escJ(r.no_part || data.no_part)}</td>
                <td>${escJ(r.spesifikasi || data.spesifikasi)}</td>
                <td>${escJ(r.alat_verifikasi || data.alat_verifikasi || r.spesifikasi_verifikasi || data.spesifikasi_verifikasi)}</td>
                <td style="font-weight:bold;">${escJ(r.hasil_verifikasi)}</td>
                <td>${dateJ(data.jadwal_berikutnya || data.periksa_berikut)}</td>
            </tr>
        `).join('');

        let blankRowsJ = '';
        for (let i = rowsJ.length; i < 12; i++) {
            blankRowsJ += '<tr class="blank-row-j"><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
        }

        document.getElementById('report').innerHTML = `
        <div class="report report-j">
            <div class="company-j">PT. SURYA TOTO INDONESIA Tbk.</div>
            <div class="title-j">LAPORAN VERIFIKASI</div>

            <table class="info-j">
                <tr>
                    <td class="label-j">NO LAPORAN</td>
                    <td class="value-j">${escJ(data.no_laporan)}</td>
                    <td class="label2-j">SEKSI</td>
                    <td class="value2-j" style="text-align:center;font-weight:bold;">${escJ(data.seksi_pemilik || data.seksi)}</td>
                </tr>
                <tr>
                    <td class="label-j">NAMA ALAT</td>
                    <td class="value-j">${escJ(data.nama_part || data.nama_alat)}</td>
                    <td class="label2-j">PETUGAS</td>
                    <td class="value2-j" style="text-align:center;">${escJ((data.operator || data.petugas))}</td>
                </tr>
                <tr>
                    <td class="label-j">METODE</td>
                    <td class="value-j" style="font-weight:bold;">${escJ(data.metode)}</td>
                    <td class="label2-j">TANGGAL VERIFIKASI</td>
                    <td class="value2-j" style="text-align:center;">${dateJ(data.tanggal_periksa)}</td>
                </tr>
                <tr>
                    <td class="label-j">ALAT VERIFIKASI</td>
                    <td class="value-j" style="font-weight:bold;">${escJ(data.alat_verifikasi)}</td>
                    <td class="label2-j"></td>
                    <td class="value2-j"></td>
                </tr>
            </table>

            <div class="hasil-title-j">HASIL VERIFIKASI</div>

            <table class="hasil-j">
                <colgroup>
                    <col style="width:7%;">
                    <col style="width:18%;">
                    <col style="width:20%;">
                    <col style="width:25%;">
                    <col style="width:15%;">
                    <col style="width:15%;">
                </colgroup>
                <thead>
                    <tr>
                        <th rowspan="2">NO</th>
                        <th colspan="2">ALAT YANG DIVERIFIKASI</th>
                        <th rowspan="2">SPESIFIKASI<br>ALAT VERIFIKASI</th>
                        <th rowspan="2">HASIL<br>VERIFIKASI</th>
                        <th rowspan="2">TGL. VERIFIKASI<br>BERIKUT</th>
                    </tr>
                    <tr>
                        <th>NO ALAT</th>
                        <th>SPESIFIKASI</th>
                    </tr>
                </thead>
                <tbody>
                    ${bodyJ || '<tr><td colspan="6">Tidak ada data verifikasi.</td></tr>'}
                    ${blankRowsJ}
                </tbody>
            </table>

            <table class="ket-j">
                <tr>
                    <td>
                        <b>KETERANGAN :</b><br>
                        <div class="legend-j">&nbsp;&nbsp;&nbsp;&nbsp;O &nbsp;&nbsp;: OKE<br>&nbsp;&nbsp;&nbsp;&nbsp;X &nbsp;&nbsp;: TIDAK OKE/BAD</div>
                        ${qaKeterangan(data.keterangan) ? '<br>'+qaKeterangan(data.keterangan) : ''}
                    </td>
                </tr>
            </table>

            <div class="city-date-j">TANGERANG, ${dateLongJ(data.tanggal_periksa)}</div>
            <table class="sign-j">
                <tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr>
                <tr class="sign-space-j">
                    <td>${escJ((data.operator || data.petugas))}</td>
                    <td>${qaSignatureName(data.pemeriksa || data.foreman)}<br>${qaApprovalDate('pemeriksa')}</td>
                    <td>${qaSignatureName(data.penyetuju || data.supervisor)}<br>${qaApprovalDate('penyetuju')}</td>
                </tr>
                <tr class="role-j"><td>PETUGAS</td><td>PEMERIKSA</td><td>PENYETUJU</td></tr>
            </table>

            <div class="doc-code-j">STIS-CMFG4-39/R3</div>
        </div>`;
        return;
    }

// ================= FORM H (THREAD PLUG GAUGE) =================
    if (String(data.format || '').toUpperCase() === 'H') {
        const rowsH = Array.isArray(data.hasil_array) ? data.hasil_array : [];
        const escH = (v) => {
            if (v === null || v === undefined || String(v).trim() === '') return '';
            return String(v).trim().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
        };
        const dateH = (v) => {
            if (!v) return '';
            const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return escH(v);
            return m[3]+'-'+m[2]+'-'+m[1];
        };
        const dateLongH = (v) => {
            if (!v) return '';
            const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return escH(v);
            const bulan=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            return m[3]+' '+bulan[parseInt(m[2],10)-1]+' '+m[1];
        };
        const stdCellH = (v,t,r) => {
            let s = escH(v);
            if (t) s += (s ? '<br>' : '') + '<span class="small-h">' + escH(t) + '</span>';
            if (r) {
                let rClean = String(r).trim().replace(/^\s*\(\s*(.*?)\s*\)\s*$/, '$1');
                s += (s ? '<br>' : '') + '<span class="small-h">(' + escH(rClean) + ')</span>';
            }
            return s || '///';
        };
        const firstH = rowsH[0] || {};
        const kodeWarnaH = data.kode_warna || firstH.kode_warna || '';
        const statusFinalH = String(data.keputusan || data.status_final || data.status_kalibrasi || '').toUpperCase();
        let putusanH = escH(data.keputusan || data.status_final || data.status_kalibrasi);
        if (statusFinalH === 'GOOD' || statusFinalH === 'OK' || statusFinalH === 'LULUS') putusanH = 'OK';
        else if (statusFinalH === 'NOT GOOD' || statusFinalH === 'NG') putusanH = 'NG';
        const rowspanH = Math.max(rowsH.length, 1);

        const bodyH = rowsH.map((r, idx) => {
            const out = r.out || {};
            const pitch = r.pitch || {};
            return `<tr>
                ${idx===0 ? `<td rowspan="${rowspanH}">1</td><td rowspan="${rowspanH}">${escH(data.no_part)}</td><td rowspan="${rowspanH}">${escH(data.spesifikasi)}</td>` : ''}
                <td>${escH(r.std_spesifikasi)}</td>
                <td>${escH(r.std_no_alat)}</td>
                <td><b>${escH(r.posisi_ukur)}</b></td>
                <td>${stdCellH(r.std_out,r.tol_out,r.range_out)}</td>
                <td>${stdCellH(r.std_pitch,r.tol_pitch,r.range_pitch)}</td>
                <td>${escH(out.dep)}</td><td>${escH(out.ten)}</td><td>${escH(out.bel)}</td>
                <td>${escH(pitch.dep)}</td><td>${escH(pitch.ten)}</td><td>${escH(pitch.bel)}</td>
                ${idx===0 ? `<td rowspan="${rowspanH}">${escH(kodeWarnaH)}</td><td rowspan="${rowspanH}">${escH(putusanH)}</td><td rowspan="${rowspanH}">${dateH(data.jadwal_berikutnya || data.periksa_berikut)}</td>` : ''}
            </tr>`;
        }).join('');

        document.getElementById('report').innerHTML = `
        <div class="report report-h">
            <div class="company-h">PT. SURYA TOTO INDONESIA Tbk.</div>

            <table class="header-h">
                <tr>
                    <td colspan="2" style="text-align:center; vertical-align:middle;">
                        <div class="title-h" style="padding:0;">LAPORAN PEMERIKSAAN GAUGE</div>
                    </td>
                    <td class="sign-h" rowspan="2">
                        <div style="text-align:left; font-size:7pt; margin-bottom:1mm; padding-left:1mm;">TANGGAL : ${dateLongH(data.tanggal_periksa).toUpperCase()}</div>
                        <table class="sign-h-table" style="height:1px;">
                            <tr><td>DIBUAT</td><td>DIPERIKSA</td><td>DISETUJUI</td></tr>
                            <tr class="space-h"><td>${qaSignatureName(data.operator || data.petugas)}<br>${qaApprovalDate('pembuat')}</td><td>${qaSignatureName(data.pemeriksa || data.foreman)}<br>${qaApprovalDate('pemeriksa')}</td><td>${qaSignatureName(data.penyetuju || data.supervisor)}<br>${qaApprovalDate('penyetuju')}</td></tr>
                            <tr><td>PETUGAS</td><td>PEMERIKSA</td><td>PENYETUJU</td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="left-h">
                        <table style="width:100%;border-collapse:collapse;">
                            <tr><td style="width:23mm;">NO LAPORAN</td><td>: ${escH(data.no_laporan)}</td></tr>
                            <tr><td>NAMA ALAT</td><td>: ${escH(data.nama_part || data.nama_alat)}</td></tr>
                            <tr><td>METODE</td><td>: ${escH(data.metode)}</td></tr>
                            <tr><td>SEKSI</td><td>: ${escH(data.seksi_pemilik || data.seksi)}</td></tr>
                        </table>
                    </td>
                    <td class="mid-h">
                        <table style="width:100%;border-collapse:collapse;">
                            <tr><td style="width:30mm;">ALAT STANDAR</td><td>: ${escH(data.standar_uji)}</td></tr>
                            <tr><td>TRACEABILITY</td><td>: ${escH(data.traceability)}</td></tr>
                            <tr><td>SUHU / HUMIDITY</td><td>: ${escH(qaSuhuValue(data.suhu))} &deg;C / ${escH(qaHumidityValue(data.kelembapan))} %</td></tr>
                            <tr><td>TANGGAL PERIKSA</td><td>: ${dateLongH(data.tanggal_periksa)}</td></tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="page-h">HAL : 1/1</div>

            <table class="main-h">
                <colgroup>
                    <col style="width:4%"><col style="width:7%"><col style="width:9%">
                    <col style="width:8%"><col style="width:7%"><col style="width:7%">
                    <col style="width:7%"><col style="width:7%">
                    <col style="width:5%"><col style="width:5%"><col style="width:5%">
                    <col style="width:5%"><col style="width:5%"><col style="width:5%">
                    <col style="width:6%"><col style="width:5%"><col style="width:8%">
                </colgroup>
                <thead>
                    <tr>
                        <th rowspan="3">NO</th>
                        <th colspan="2">ALAT YANG DIPERIKSA</th>
                        <th colspan="2">ALAT STANDAR YANG DIPAKAI</th>
                        <th colspan="9">HASIL PERIKSA</th>
                        <th rowspan="3">KODE<br>WARNA</th>
                        <th rowspan="3">KEP</th>
                        <th rowspan="3">TANGGAL<br>PERIKSA<br>BERIKUTNYA</th>
                    </tr>
                    <tr>
                        <th rowspan="2">NO ALAT</th><th rowspan="2">SPESIFIKASI</th>
                        <th rowspan="2">SPESIFIKASI</th><th rowspan="2">NO ALAT</th>
                        <th rowspan="2">POSISI<br>UKUR</th>
                        <th colspan="2">STD UKURAN</th>
                        <th colspan="6">HASIL UKUR</th>
                    </tr>
                    <tr>
                        <th>OUT Ø</th><th>PITCH Ø</th>
                        <th colspan="3">OUT Ø</th><th colspan="3">PITCH Ø</th>
                    </tr>
                    <tr>
                        <th colspan="8"></th>
                        <th>DEPAN</th><th>TENGAH</th><th>BELAKANG</th>
                        <th>DEPAN</th><th>TENGAH</th><th>BELAKANG</th>
                        <th colspan="3"></th>
                    </tr>
                </thead>
                <tbody>
                    ${bodyH || '<tr><td colspan="17">Tidak ada data pemeriksaan.</td></tr>'}
                </tbody>
            </table>

            <table class="ket-h"><tr><td><b>KETERANGAN :</b><br>${qaKeterangan(data.keterangan)}</td></tr></table>
            <div class="doc-h">STIS-CMFG4-05/R4</div>
        </div>`;
        return;
    }

// ================= FORM I (PEMERIKSAAN GAUGE) =================
    if (String(data.format || '').toUpperCase() === 'I') {
        const rowsI = Array.isArray(data.hasil_array) ? data.hasil_array : [];
        const escI = (v) => {
            if (v === null || v === undefined || String(v).trim() === '') return '///';
            return String(v).trim().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
        };
        const rawI = (v) => (v === null || v === undefined ? '' : String(v).trim());
        const dateI = (v) => {
            if (!v) return '///';
            const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return escI(v);
            return m[3]+'-'+m[2]+'-'+m[1];
        };
        const dateLongI = (v) => {
            if (!v) return '///';
            const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return escI(v);
            const bulan=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            return m[3]+' '+bulan[parseInt(m[2],10)-1]+' '+m[1];
        };

        const bodyI = rowsI.map((r, idx) => `
            <tr class="row-i">
                <td rowspan="2">${idx + 1}</td>
                <td rowspan="2">${escI(data.no_part || data.no_alat)}</td>
                <td rowspan="2">${escI(r.spesifikasi_alat || data.spesifikasi)}</td>
                <td rowspan="2">${escI(r.std_spesifikasi)}</td>
                <td rowspan="2">${escI(r.std_no_alat)}</td>
                <td>ATAS</td>
                <td>${escI(r.hasil_atas)}</td>
                <td rowspan="2">${escI(r.kode_warna)}</td>
                <td rowspan="2">${escI(r.putusan)}</td>
                <td rowspan="2">${dateI(data.jadwal_berikutnya || data.periksa_berikut)}</td>
            </tr>
            <tr class="row-i">
                <td>BAWAH</td>
                <td>${escI(r.hasil_bawah)}</td>
            </tr>
        `).join('');

        document.getElementById('report').innerHTML = `
        <div class="report report-i">
            <div class="company-i">PT. SURYA TOTO INDONESIA Tbk.</div>
            <table class="header-i">
                <tr>
                    <td colspan="2" style="text-align:center;vertical-align:middle;"><div class="title-i" style="padding:0;">LAPORAN PEMERIKSAAN GAUGE</div></td>
                    <td class="sign-i" rowspan="2">
                        <div style="text-align:left;font-size:7pt;margin-bottom:1mm;padding-left:1mm;">TANGGAL : ${dateLongI(data.tanggal_periksa)}</div>
                        <table class="sign-head-i"><tr><td>DIBUAT</td><td>DIPERIKSA</td><td>DISETUJUI</td></tr><tr class="space-i"><td>${qaSignatureName(data.operator || data.petugas)}<br>${qaApprovalDate('pembuat')}</td><td>${qaSignatureName(data.pemeriksa || data.foreman)}<br>${qaApprovalDate('pemeriksa')}</td><td>${qaSignatureName(data.penyetuju || data.supervisor)}<br>${qaApprovalDate('penyetuju')}</td></tr><tr><td>PETUGAS</td><td>PEMERIKSA</td><td>PENYETUJU</td></tr></table>
                    </td>
                </tr>
                <tr>
                    <td class="left-i">
                        <table style="width:100%;border-collapse:collapse;">
                            <tr><td>NO LAPORAN</td><td>: ${escI(data.no_laporan)}</td></tr>
                            <tr><td>NAMA ALAT</td><td>: ${escI(data.nama_part || data.nama_alat)}</td></tr>
                            <tr><td>METODE</td><td>: ${escI(data.metode)}</td></tr>
                            <tr><td>SEKSI</td><td>: ${escI(data.seksi_pemilik || data.seksi)}</td></tr>
                        </table>
                    </td>
                    <td class="mid-i">
                        <table style="width:100%;border-collapse:collapse;">
                            <tr><td>ALAT STANDAR</td><td>: ${escI(data.standar_uji)}</td></tr>
                            <tr><td>TRACEABILITY</td><td>: ${escI(data.traceability)}</td></tr>
                            <tr><td>SUHU/HUMIDITY</td><td>: ${rawI(data.suhu) ? escI(qaSuhuValue(data.suhu))+' °C' : '///'} / ${rawI(data.kelembapan) ? escI(qaHumidityValue(data.kelembapan))+' %' : '///'}</td></tr>
                            <tr><td>TANGGAL PERIKSA</td><td>: ${dateLongI(data.tanggal_periksa)}</td></tr>
                        </table>
                    </td>

                </tr>
            </table>

            <div class="page-i">HAL : 1/1</div>

            <table class="main-i">
                <colgroup>
                    <col class="c-no"><col class="c-noalat"><col class="c-spekalat">
                    <col class="c-stdspek"><col class="c-stdno"><col class="c-lokasi">
                    <col class="c-hasil"><col class="c-warna"><col class="c-putusan"><col class="c-next">
                </colgroup>
                <thead>
                    <tr>
                        <th colspan="3">ALAT YANG DIPERIKSA</th>
                        <th colspan="2">ALAT STANDAR YANG DIPAKAI</th>
                        <th rowspan="2">LOKASI<br>PERIKSA</th>
                        <th rowspan="2">HASIL PERIKSA</th>
                        <th rowspan="2">KODE<br>WARNA</th>
                        <th rowspan="2">PUTUSAN</th>
                        <th rowspan="2">TGL<br>PERIKSA<br>BERIKUT</th>
                    </tr>
                    <tr>
                        <th>NO</th>
                        <th>NO ALAT</th>
                        <th>SPESIFIKASI</th>
                        <th>SPESIFIKASI</th>
                        <th>NO ALAT</th>
                    </tr>
                </thead>
                <tbody>
                    ${bodyI || '<tr><td colspan="10">Tidak ada data pemeriksaan.</td></tr>'}
                    <tr><td colspan="10" class="blank-i"></td></tr>
                </tbody>
            </table>

            <table class="ket-i">
                <tr><td><b>KETERANGAN :</b><br>${qaKeterangan(data.keterangan)}</td></tr>
            </table>
            <div class="doc-code-i">STIS-CMFG4-06/R1</div>
        </div>`;
        return;
    }

// ================= FORM D (PEMERIKSAAN MULTIMETER) =================
    if (String(data.format || '').toUpperCase() === 'D') {
        const rowsD = Array.isArray(data.hasil_array) ? data.hasil_array : [];

        const rawD = (v) => (v === null || v === undefined ? '' : String(v).trim());
        const htmlD = (v, empty = '///') => {
            const value = rawD(v);
            if (!value) return empty;
            return value.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
        };
        const unitRawD = (v) => rawD(v).replace(/Ω/g, 'ohm').replace(/Ω/g, 'ohm');
        const unitD = (value) => {
            let unit = String(value ?? '').trim();

            // Normalisasi semua kemungkinan nilai resistance.
            // Termasuk "?" untuk data lama yang simbol Ω-nya sudah rusak karena charset.
            const normalized = unit.toUpperCase();

            if (
                unit === 'Ω' ||
                unit === 'Ω' ||
                normalized === 'OHM' ||
                normalized === '(OHM)' ||
                unit === '?'
            ) {
                return '(ohm)';
            }

            if (!unit) {
                return '';
            }

            return '(' + unit + ')';
        };
        const dateD = (v) => {
            if (!v) return '///';
            const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return m ? (m[3]+'-'+m[2]+'-'+m[1]) : htmlD(v);
        };
        const suffixD = (v, suffix) => rawD(v) ? htmlD(v) + suffix : '///';

        const statusD = rawD(data.keputusan || data.status_final).toUpperCase();
        const keputusanD = (statusD === 'GOOD' || statusD === 'OK')
            ? 'OK'
            : ((statusD === 'NOT GOOD' || statusD === 'NG') ? 'NG' : htmlD(data.keputusan || data.status_final));

        /*
         * Excel Format D membuat satu section untuk setiap kombinasi UNIT + MODE.
         * Contoh: V/AC, mV/AC, mV/DC, ohm/(kosong).
         * Dengan cara ini print registrasi dan update memakai bentuk tabel yang sama.
         */
        const groupsD = [];
        rowsD.forEach((row, index) => {
            const r = row && typeof row === 'object' ? row : {};
            const unit = rawD(r.unit || r.satuan);
            let mode = rawD(r.mode || r.jenis);
            if (mode === '-' || mode.toUpperCase() === 'KOSONG') mode = '';
            const key = unit + '\u0001' + mode;
            let group = groupsD.length ? groupsD[groupsD.length - 1] : null;
            if (!group || group.key !== key) {
                group = { key:key, unit:unit, mode:mode, rows:[] };
                groupsD.push(group);
            }
            group.rows.push(Object.assign({__index:index}, r));
        });

        const bodyD = groupsD.map((group) => {
            const u = unitD(group.unit);
            let html = `<tr class="group-head-d">
                <td>NO</td>
                <td colspan="2">${u || '///'}</td>
                <td>${u || '///'}</td>
                <td>${u || '///'}</td>
                <td>${u || '///'}</td>
            </tr>`;

            group.rows.forEach((r, i) => {
                html += '<tr>';
                html += `<td>${i + 1}</td>`;
                if (i === 0) {
                    html += `<td class="group-mode-d" rowspan="${group.rows.length}">${htmlD(group.mode, '')}</td>`;
                }
                html += `<td>${htmlD(r.setting)}</td>`;
                html += `<td>${htmlD(r.standar)}</td>`;
                html += `<td>${htmlD(r.hasil)}</td>`;
                html += `<td>${htmlD(r.koreksi)}</td>`;
                html += '</tr>';
            });
            return html;
        }).join('');

        document.getElementById('report').innerHTML = `
        <div class="report report-d">
            <div class="company-d">PT. SURYA TOTO INDONESIA Tbk.</div>
            <div class="report-title-d">LAPORAN PEMERIKSAAN</div>

            <table class="info-d">
                <colgroup><col class="d-label"><col class="d-value"><col class="d-label-r"><col class="d-value-r"></colgroup>
                <tr><td>NO. LAPORAN</td><td>${htmlD(data.no_laporan)}</td><td>SEKSI</td><td><b>${htmlD(data.seksi_pemilik || data.seksi)}</b></td></tr>
                <tr><td>NAMA ALAT</td><td>${htmlD(data.nama_part || data.nama_alat)}</td><td>PETUGAS PERIKSA</td><td>${qaSignatureName(data.operator || data.petugas)}</td></tr>
                <tr><td>NO. ALAT</td><td>${htmlD(data.no_part || data.no_alat)}</td><td></td><td></td></tr>
                <tr><td>SPESIFIKASI</td><td>${htmlD(data.spesifikasi)}</td><td>TANGGAL PERIKSA</td><td>${dateD(data.tanggal_periksa)}</td></tr>
                <tr><td>RESOLUSI</td><td>${htmlD(data.resolusi)}</td><td></td><td></td></tr>
            </table>

            <div class="hasil-title-d">HASIL PEMERIKSAAN</div>
            <table class="result-d">
                <colgroup><col class="no-col"><col class="mode-col"><col class="setting-col"><col class="std-col"><col class="hasil-col"><col class="koreksi-col"></colgroup>
                <thead>
                    <tr><th rowspan="2">NO</th><th colspan="2">SETTING</th><th>STANDAR</th><th>HASIL</th><th>KOREKSI</th></tr>
                    <tr class="unit-sub-d"><th colspan="2"></th><th></th><th></th><th></th></tr>
                </thead>
                <tbody>${bodyD || '<tr><td colspan="6">Tidak ada data pemeriksaan.</td></tr>'}</tbody>
            </table>

            <table class="detail-d">
                <colgroup><col class="d-lbl"><col class="d-val"><col class="d-lbl-r"><col class="d-val-r"></colgroup>
                <tr><td>METODE</td><td>${htmlD(data.metode || data.metode_uji)}</td><td>KELEMBABAN RUANGAN</td><td>${htmlD(data.kelembapan)}</td></tr>
                <tr><td>STANDAR UJI</td><td>${htmlD(data.standar_uji)}</td><td>STANDAR TOLERANSI</td><td>${htmlD(data.toleransi_global || data.toleransi)}</td></tr>
                <tr><td>TRACEABILITY</td><td>${htmlD(data.traceability)}</td><td>KEPUTUSAN</td><td><b>${keputusanD}</b></td></tr>
                <tr><td>SUHU RUANGAN</td><td>${htmlD(data.suhu)}</td><td>PERIKSA BERIKUTNYA</td><td>${dateD(data.jadwal_berikutnya || data.periksa_berikut)}</td></tr>
                <tr><td>KETERANGAN :</td><td colspan="3" class="ket-d">${qaKeterangan(data.keterangan)}</td></tr>
            </table>

            <div class="city-date-d">TANGERANG, ${dateD(data.tanggal_periksa)}</div>
            <table class="sign-d">
                <tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr>
                <tr class="sign-space-d">
                    <td>${qaSignatureName(data.operator || data.petugas)}<br>${qaApprovalDate('pembuat')}</td>
                    <td>${qaSignatureName(data.pemeriksa || data.foreman)}<br>${qaApprovalDate('pemeriksa')}</td>
                    <td>${qaSignatureName(data.penyetuju || data.supervisor)}<br>${qaApprovalDate('penyetuju')}</td>
                </tr>
                <tr class="roles-d"><td>PETUGAS</td><td>FOREMAN</td><td>SUPERVISOR</td></tr>
            </table>
            <div class="doc-code-d">STIS-CMFG4-05/R4</div>
        </div>`;
        return;
    }

// ================= FORM C (PEMERIKSAAN TEMPERATUR) =================
    if (String(data.format || '').toUpperCase() === 'C') {
        const rowsC = Array.isArray(data.hasil_array) ? data.hasil_array : [];

        const escC = (v) => {
            if (v === null || v === undefined || String(v).trim() === '') return '///';
            return String(v).trim()
                .replace(/&/g,'&amp;').replace(/</g,'&lt;')
                .replace(/>/g,'&gt;').replace(/"/g,'&quot;')
                .replace(/'/g,'&#039;');
        };
        const rawC = (v) => (v === null || v === undefined ? '' : String(v).trim());
        const dateC = (v) => {
            if (!v) return '///';
            const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return escC(v);
            return m[3]+'-'+m[2]+'-'+m[1];
        };
        const toleransiTextC = (() => {
            const t = rawC(data.toleransi);
            if (!t) return '///';
            return escC(t);
        })();
        const statusFinalC = String(data.keputusan || data.status_final || '').toUpperCase();
        const keputusanC = (statusFinalC === 'GOOD' || statusFinalC === 'OK')
            ? 'OK'
            : ((statusFinalC === 'NOT GOOD' || statusFinalC === 'NOT GOOD' || statusFinalC === 'NG') ? 'NG' : escC(data.keputusan || data.status_final));

        const bodyC = rowsC.map((r, i) => {
            const st = String(r.status || '').toUpperCase();
            const lulus = st === 'OK' || st === 'GOOD' || st === 'LULUS';
            return `<tr>
                <td>${i + 1}</td>
                <td>${escC(r.setting)}</td>
                <td>${escC(r.actual)}</td>
                <td>${escC(r.alat_std)}</td>
                <td>${escC(r.selisih)}</td>
                <td class="${lulus ? 'ok-c' : 'bad-c'}">${lulus ? 'Lulus' : 'Tidak Lulus'}</td>
            </tr>`;
        }).join('');

        const blankRowsC = Math.max(0, 8 - rowsC.length);
        const fillersC = Array.from({length: blankRowsC}, () => '<tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td></tr>').join('');

        const suhuC = rawC(data.suhu);
        const kelembapanC = rawC(data.kelembapan);
        const diagC = '<div class="diagonal-c"><svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"><line x1="0" y1="100" x2="100" y2="0" stroke="black" stroke-width="1"/></svg>&nbsp;</div>';

        document.getElementById('report').innerHTML = `
        <div class="report report-c">
            <div class="company-c">PT. SURYA TOTO INDONESIA Tbk.</div>
            <div class="report-title-c">LAPORAN PEMERIKSAAN</div>

            <table class="info-c">
                <colgroup><col class="c-label"><col class="c-value"><col class="c-label-r"><col class="c-value-r"></colgroup>
                <tr><td>NO LAPORAN</td><td>${escC(data.no_laporan)}</td><td>SEKSI</td><td>${escC(data.seksi_pemilik || data.seksi)}</td></tr>
                <tr><td>NAMA ALAT</td><td>${escC(data.nama_part || data.nama_alat)}</td><td rowspan="2">PETUGAS PERIKSA</td><td rowspan="2">${qaSignatureName(data.operator || data.petugas)}<br>${qaApprovalDate('pembuat')}</td></tr>
                <tr><td>NOMOR ALAT</td><td>${escC(data.no_part || data.no_alat)}</td></tr>
                <tr><td>SPESIFIKASI</td><td>${escC(data.spesifikasi)}</td><td>TANGGAL PERIKSA</td><td>${dateC(data.tanggal_periksa)}</td></tr>
                <tr class="row-tall"><td>RESOLUSI</td><td>${escC(data.resolusi)}</td><td></td><td></td></tr>
            </table>

            <div class="hasil-title-c">HASIL PEMERIKSAAN</div>
            <table class="result-c">
                <colgroup><col class="no-c"><col class="sv-c"><col class="pv-c"><col class="std-c"><col class="diff-c"><col class="pass-c"></colgroup>
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>SETTING<br>(SV)<br><br>( °C )</th>
                        <th>ACTUAL<br>(PV)<br><br>( °C )</th>
                        <th>ALAT STD<br><br>( °C )</th>
                        <th>SELISIH<br><br>( °C )</th>
                        <th>PERTIMBANGAN<br>KELULUSAN</th>
                    </tr>
                </thead>
                <tbody>${bodyC || '<tr><td colspan="6">Tidak ada data pemeriksaan.</td></tr>'}${fillersC}</tbody>
            </table>

            <table class="detail-c">
                <colgroup><col class="d-label-c"><col class="d-value-c"><col class="d-label-r-c"><col class="d-value-r-c"></colgroup>
                <tr><td>METODE</td><td>${escC(data.metode)}</td><td>KELEMBABAN RUANGAN</td><td>${kelembapanC ? escC(kelembapanC) : diagC}</td></tr>
                <tr><td>STANDAR UJI</td><td>${escC(data.standar_uji)}</td><td>STANDAR TOLERANSI</td><td>${toleransiTextC}</td></tr>
                <tr><td>TRACEABILITY</td><td>${escC(data.traceability)}</td><td>KEPUTUSAN</td><td class="${keputusanC === 'OK' ? 'ok-c' : 'bad-c'}">${keputusanC}</td></tr>
                <tr><td>SUHU RUANGAN</td><td>${suhuC ? escC(suhuC) : diagC}</td><td>TGL PERIKSA BERIKUTNYA</td><td>${dateC(data.jadwal_berikutnya || data.periksa_berikut)}</td></tr>
                <tr><td colspan="4" class="ket-c"><strong>KETERANGAN :</strong><br>${qaKeterangan(data.keterangan)}</td></tr>
            </table>

            <div class="city-date-c">TANGERANG, ${dateC(data.tanggal_periksa)}</div>
            <table class="sign-c">
                <tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr>
                <tr class="sign-space-c"><td>${qaSignatureName(data.operator || data.petugas)}<br>${qaApprovalDate('pembuat')}</td><td>${qaSignatureName(data.pemeriksa || data.foreman)}<br>${qaApprovalDate('pemeriksa')}</td><td>${qaSignatureName(data.penyetuju || data.supervisor)}<br>${qaApprovalDate('penyetuju')}</td></tr>
                <tr class="roles-c"><td>PETUGAS</td><td>PEMERIKSA</td><td>PENYETUJU</td></tr>
            </table>
        </div>`;
        return;
    }

// ================= FORM B (AIR DRIVER) =================
    // Layout mengikuti format laporan Air Driver referensi:
    // A4 portrait, 6 kolom utama, 3 hasil uji per titik + rata-rata.
    if (String(data.format || '').toUpperCase() === 'B') {
        const rows = Array.isArray(data.hasil_array) ? data.hasil_array : [];

        const escB = (v) => {
            if (v === null || v === undefined || String(v).trim() === '') return '///';
            return String(v).trim()
                .replace(/&/g,'&amp;').replace(/</g,'&lt;')
                .replace(/>/g,'&gt;').replace(/"/g,'&quot;')
                .replace(/'/g,'&#039;');
        };
        const plainB = (v) => (v === null || v === undefined ? '' : String(v).trim());
        const dateB = (v) => {
            if (!v) return '///';
            const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return escB(v);
            return m[3]+'-'+m[2]+'-'+m[1];
        };
        const dateLongB = (v) => {
            if (!v) return '///';
            const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return escB(v);
            const bulan=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            return m[3]+' '+bulan[parseInt(m[2],10)-1]+' '+m[1];
        };
        const first = rows[0] || {};
        const statusFinal = String(data.keputusan || data.status_final || '').toUpperCase();
        const keputusan = (statusFinal === 'GOOD' || statusFinal === 'OK') ? 'OK' : (statusFinal === 'NOT GOOD' || statusFinal === 'NOT GOOD' ? 'NG' : escB(data.keputusan || data.status_final));
        const keputusanClass = keputusan === 'OK' ? 'ok-b' : 'bad-b';

        // Data yang tersedia dari Form B. Fallback ke field lama jika ada.
        const metode = data.metode || '';
        const standarUji = data.standar_uji || '';
        const traceability = data.traceability || '';
        const petugas = (data.operator || data.petugas) || '';
        const seksi = data.seksi_pemilik || data.seksi || '';
        const tanggal = data.tanggal_periksa || data.tanggal || '';
        const jadwal = data.jadwal_berikutnya || data.periksa_berikut || '';

        // Kelompokkan tiap titik standar menjadi satu blok 3 baris.
        const body = rows.map((r, i) => {
            const newFlow = Object.prototype.hasOwnProperty.call(r, 'std1');
            let uji = (newFlow ? [r.std1,r.std2,r.std3] : [r.uji1,r.uji2,r.uji3]).map(v => plainB(v));
            let min = parseFloat(r.std_min), max = parseFloat(r.std_max);
            let rata = parseFloat(r.rata);
            if (isNaN(rata) && uji.every(v => v !== '' && !isNaN(parseFloat(v)))) {
                rata = (parseFloat(uji[0]) + parseFloat(uji[1]) + parseFloat(uji[2])) / 3;
            }
            const rataText = newFlow ? escB(r.hasil) : (isNaN(rata) ? '///' : rata.toFixed(1));
            const stdText = (!isNaN(min) && !isNaN(max)) ? `${min} ~ ${max}` : `${escB(r.std_min)} ~ ${escB(r.std_max)}`;
            const st = String(r.status || '').toUpperCase();
            const lulus = st ? st === 'OK' : (!isNaN(rata) && !isNaN(min) && !isNaN(max) && rata >= min && rata <= max);
            const hasilCells = uji.map(v => v === '' ? '///' : escB(v));
            return `
                <tbody class="b-group"><tr class="b-point">
                    <td rowspan="3" class="b-center b-big">${escB(r.tekanan)}</td>
                    <td rowspan="3" class="b-center b-big">${escB(r.handle)}</td>
                    <td rowspan="3" class="b-center b-big">${escB(r.baut)}</td>
                    <td rowspan="3" class="b-center b-std">${stdText}</td>
                    <td class="b-result">${hasilCells[0]}</td>
                    <td rowspan="3" class="b-center b-average">${rataText}</td>
                    <td rowspan="3" class="b-center b-pass ${lulus ? 'ok-b' : 'bad-b'}">${lulus ? 'Lulus' : 'Tidak Lulus'}</td>
                </tr>
                <tr>
                    <td class="b-result">${hasilCells[1]}</td>
                </tr>
                <tr>
                    <td class="b-result">${hasilCells[2]}</td>
                </tr></tbody>`;
        }).join('');

        document.getElementById('report').innerHTML = `
        <div class="report report-b">
            <div class="company-b">PT. SURYA TOTO INDONESIA Tbk.</div>
            <div class="report-title-b">LAPORAN PEMERIKSAAN</div>

            <table class="info-b">
                <colgroup><col class="w-no"><col class="w-data"><col class="w-label2"><col class="w-data2"></colgroup>
                <tr><td>NO. LAPORAN</td><td>${escB(data.no_laporan)}</td><td>SEKSI</td><td class="b-bold b-center">${escB(seksi)}</td></tr>
                <tr><td>NAMA ALAT</td><td>${escB(data.nama_part)}</td><td rowspan="2">PETUGAS PERIKSA</td><td rowspan="2">${qaSignatureName(petugas)}<br>${qaApprovalDate('pembuat')}</td></tr>
                <tr><td>NO. ALAT</td><td>${escB(data.no_part)}</td></tr>
                <tr><td>SPESIFIKASI</td><td>${escB(data.spesifikasi)}</td><td>TANGGAL PERIKSA</td><td>${dateB(tanggal)}</td></tr>
                <tr class="type-row"><td>TYPE</td><td>${escB(data.type || '')}</td><td></td><td></td></tr>
            </table>
            <div class="hasil-title-b">HASIL PEMERIKSAAN</div>
            <table class="air-b">
                <colgroup><col class="c-pressure"><col class="c-handle"><col class="c-baut"><col class="c-std"><col class="c-result"><col class="c-rata"><col class="c-lulus"></colgroup>
                <thead>
                    <tr><th rowspan="2">TEKANAN<br>UDARA</th><th rowspan="2" colspan="2">POSISI HANDLE PENGATUR TORSI</th><th colspan="2">STANDAR TORSI<br>PENGUNCI ULIR</th><th>HASIL</th><th rowspan="2">PERTIMBANGAN<br>KELULUSAN</th></tr>
                    <tr><th colspan="2">( kgf.cm )</th><th>( kgf.cm )</th></tr>
                </thead>
                ${body || '<tbody><tr><td colspan="7">Tidak ada data pengujian.</td></tr></tbody>'}
            </table>

            <table class="detail-b">
                <tr>
                    <td class="d-label">METODE</td><td class="d-value">${escB(metode)}</td>
                    <td class="d-label">KELEMBABAN RUANGAN</td><td class="d-value d-small">${escB(data.kelembapan)}</td>
                </tr>
                <tr class="standard-row">
                    <td class="d-label">STANDAR UJI</td><td class="d-value">${escB(standarUji)}</td>
                    <td class="d-label">STANDAR TOLERANSI</td><td class="d-value diagonal"><svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"><line x1="0" y1="100" x2="100" y2="0" stroke="black" stroke-width="1"/></svg></td>
                </tr>
                <tr class="trace-row">
                    <td class="d-label">TRACEABILITY</td><td class="d-value">${escB(traceability)}</td>
                    <td class="d-label">KEPUTUSAN</td><td class="d-value b-bold ${keputusanClass}">${keputusan}</td>
                </tr>
                <tr>
                    <td class="d-label">SUHU RUANGAN</td><td class="d-value">${escB(data.suhu)}</td>
                    <td class="d-label">TGL PERIKSA BERIKUTNYA</td><td class="d-value">${dateB(jadwal)}</td>
                </tr>
                <tr>
                    <td colspan="4" class="ket-box"><strong>KETERANGAN :</strong><br>${qaKeterangan(data.keterangan)}</td>
                </tr>
            </table>

            <div class="city-date-b">TANGERANG, ${dateB(tanggal)}</div>
            <table class="sign-b">
                <tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr>
                <tr class="sign-space-b"><td>${qaSignatureName(petugas)}<br>${qaApprovalDate('pembuat')}</td><td>${qaSignatureName(data.pemeriksa || data.foreman || '')}<br>${qaApprovalDate('pemeriksa')}</td><td>${qaSignatureName(data.penyetuju || data.supervisor || '')}<br>${qaApprovalDate('penyetuju')}</td></tr>
                <tr class="roles-b"><td>PETUGAS</td><td>PEMERIKSA</td><td>PENYETUJU</td></tr>
            </table>
            <div class="doc-code-b">STIS-CMFG4-05/R4</div>
        </div>`;
        return;
    }

    const hasil = Array.isArray(data.hasil_array)
        ? (data.hasil_array[0] || {})
        : (data.hasil_array || data.form_a || {});

    const go = hasil.go || {};
    const notGo = hasil.not_go || {};
    const alatStandar = hasil.alat_standar || data.alat_standar || {};
    const alatStandarGo = go.alat_standar || alatStandar.go || alatStandar || {};
    const alatStandarNotGo = notGo.alat_standar || alatStandar.not_go || alatStandar || {};

    function esc(value) {
        if (value === null || value === undefined) return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function val(value) {
        if (value === null || value === undefined || String(value).trim() === '') {
            return '///';
        }
        return esc(String(value).trim());
    }

    function indoDate(value) {
        if (!value || String(value).trim() === '') return '///';

        const s = String(value).trim();

        if (!/^\\d{4}-\\d{2}-\\d{2}$/.test(s)) {
            return esc(s);
        }

        const parts = s.split('-');
        const months = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        return parts[2] + ' ' + months[parseInt(parts[1], 10) - 1] + ' ' + parts[0];
    }

    function hasilUkuran(block, jenis) {
        block = block || {};
        const out = block.out || {};
        const pitch = block.pitch || {};

        return {
            stdOut: val(block.std_out),
            tolOut: val(block.tol_out),
            rangeOut: val(block.range_out),
            stdPitch: val(block.std_pitch),
            tolPitch: val(block.tol_pitch),
            rangePitch: val(block.range_pitch),
            outDep: val(out.dep),
            outTen: val(out.ten),
            outBel: val(out.bel),
            pitchDep: val(pitch.dep),
            pitchTen: val(pitch.ten),
            pitchBel: val(pitch.bel),
            jenis: jenis
        };
    }

    const G = hasilUkuran(go, 'GO');
    const NG = hasilUkuran(notGo, 'NOT GO');

    const noAlat = data.no_part || data.no_alat || '';
    const namaAlat = data.nama_part || data.nama_alat || '';
    const spesifikasi = data.spesifikasi || '';
    const seksi = data.seksi_pemilik || data.seksi || '';
    const tanggalPeriksa = data.tanggal_periksa || data.tanggal || '';
    const periksaBerikut =
        hasil.periksa_berikut ||
        data.periksa_berikut ||
        data.jadwal_berikutnya ||
        '';

    // Ambil kode varian/keterangan dari hasil JSON Form A.
    const warna = hasil.warna || data.warna || '';
    const kep = hasil.kep || data.kep || '';
    const keterangan = hasil.keterangan || data.keterangan || '';

    document.getElementById('report').innerHTML = `
<div class="report">

    <div class="company">PT. SURYA TOTO INDONESIA Tbk.</div>
    <div class="top-line"></div>

    <table style="width:100%;border-collapse:collapse;margin-top:3px;">
        <tr>
            <td style="width:68%;text-align:center;font-size:13px;font-weight:bold;">
                LAPORAN PEMERIKSAAN GAUGE
            </td>
            <td class="date-box">
                TANGGAL : ${indoDate(tanggalPeriksa)}
                <div style="border-bottom:1px solid #000;margin-top:2px;"></div>
            </td>
        </tr>
    </table>

    <table class="header">
        <tr>
            <td style="width:32%;">
                <table class="info">
                    <tr><td class="label">NO LAPORAN</td><td>: ${val(data.no_laporan)}</td></tr>
                    <tr><td class="label">NAMA ALAT</td><td>: ${val(namaAlat)}</td></tr>
                    <tr><td class="label">METODE</td><td>: ${val(data.metode || hasil.metode)}</td></tr>
                    <tr><td class="label">SEKSI</td><td>: ${val(seksi)}</td></tr>
                </table>
            </td>

            <td style="width:42%;">
                <table class="info">
                    <tr>
                        <td class="label">ALAT STANDAR</td>
                        <td>: GO ${val(alatStandarGo.no_alat)} - ${val(alatStandarGo.spesifikasi)}<br>NOT GO ${val(alatStandarNotGo.no_alat)} - ${val(alatStandarNotGo.spesifikasi)}</td>
                    </tr>
                    <tr>
                        <td class="label">TRACEABILITY</td>
                        <td>: ${val(alatStandar.traceability || data.traceability)}</td>
                    </tr>
                    <tr>
                        <td class="label">SUHU/HUMIDITY</td>
                        <td>: ${val(qaSuhuValue(data.suhu))} °C / ${val(qaHumidityValue(data.kelembapan))} %</td>
                    </tr>
                    <tr>
                        <td class="label">TGL PERIKSA</td>
                        <td>: ${indoDate(tanggalPeriksa)}</td>
                    </tr>
                </table>
            </td>

            <td class="approval">
                <table>
                    <tr>
                        <td><b>DIBUAT</b></td>
                        <td><b>DIPERIKSA</b></td>
                        <td><b>DISETUJUI</b></td>
                    </tr>
                    <tr class="sign-space">
                        <td>${qaSignatureName(data.operator || data.petugas)}<br>${qaApprovalDate('pembuat')}</td>
                        <td>${qaSignatureName(data.pemeriksa || data.foreman)}<br>${qaApprovalDate('pemeriksa')}</td>
                        <td>${qaSignatureName(data.penyetuju || data.supervisor)}<br>${qaApprovalDate('penyetuju')}</td>
                    </tr>
                    <tr>
                        <td><b>PETUGAS</b></td>
                        <td><b>PEMERIKSA</b></td>
                        <td><b>PENYETUJU</b></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="page-no">HAL: 1/1</div>

    <table class="main-table">
        <colgroup>
            <col style="width:3%;">
            <col style="width:7%;">
            <col style="width:12%;">
            <col style="width:7%;">
            <col style="width:8%;">
            <col style="width:6%;">
            <col style="width:7%;">
            <col style="width:7%;">
            <col style="width:6%;">
            <col style="width:6%;">
            <col style="width:6%;">
            <col style="width:6%;">
            <col style="width:6%;">
            <col style="width:6%;">
            <col style="width:6%;">
            <col style="width:7%;">
        </colgroup>

        <thead>
            <tr>
                <th rowspan="3">NO</th>
                <th colspan="2">YANG DIPERIKSA</th>
                <th colspan="2">ALAT STANDAR</th>
                <th colspan="9">HASIL PERIKSA</th>
                <th rowspan="3">WARNA</th>
                <th rowspan="3">KEP</th>
                <th rowspan="3">PERIKSA<br>BERIKUT</th>
            </tr>

            <tr>
                <th rowspan="2">NO ALAT</th>
                <th rowspan="2">SPEK</th>
                <th rowspan="2">SPEK</th>
                <th rowspan="2">NO ALAT</th>
                <th rowspan="2">POSISI<br>UKUR</th>
                <th colspan="2">STD UKURAN</th>
                <th colspan="6">HASIL UKUR</th>
            </tr>

            <tr>
                <th>OUT Ø</th>
                <th>PITCH Ø</th>
                <th colspan="3">OUT Ø</th>
                <th colspan="3">PITCH Ø</th>
            </tr>

            <tr>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th>DEP</th>
                <th>TEN</th>
                <th>BEL</th>
                <th>DEP</th>
                <th>TEN</th>
                <th>BEL</th>
                <th></th>
                <th></th>
            </tr>
        </thead>

        <tbody>
            <!-- GO -->
            <tr>
                <td rowspan="4" class="bold">1</td>
                <td rowspan="4">${val(noAlat)}</td>
                <td rowspan="4" class="left">${val(spesifikasi)}</td>

                <td rowspan="2">${val(alatStandarGo.spesifikasi)}</td>
                <td rowspan="2">${val(alatStandarGo.no_alat)}</td>

                <td class="bold">GO</td>
                <td>${G.stdOut}</td>
                <td>${G.stdPitch}</td>
                <td>${G.outDep}</td>
                <td>${G.outTen}</td>
                <td>${G.outBel}</td>
                <td>${G.pitchDep}</td>
                <td>${G.pitchTen}</td>
                <td>${G.pitchBel}</td>

                <td rowspan="4">${val(warna)}</td>
                <td rowspan="4">${val(kep)}</td>
                <td rowspan="4">${indoDate(periksaBerikut)}</td>
            </tr>

            <tr>
                <td class="tiny">TOL</td>
                <td class="tiny">${G.tolOut}<br>${G.rangeOut}</td>
                <td class="tiny">${G.tolPitch}<br>${G.rangePitch}</td>
                <td colspan="3" class="tiny">OUT Ø</td>
                <td colspan="3" class="tiny">PITCH Ø</td>
            </tr>

            <!-- NOT GO -->
            <tr>
                <td rowspan="2">${val(alatStandarNotGo.spesifikasi)}</td>
                <td rowspan="2">${val(alatStandarNotGo.no_alat)}</td>
                <td class="bold">NOT GO</td>
                <td>${NG.stdOut}</td>
                <td>${NG.stdPitch}</td>
                <td>${NG.outDep}</td>
                <td>${NG.outTen}</td>
                <td>${NG.outBel}</td>
                <td>${NG.pitchDep}</td>
                <td>${NG.pitchTen}</td>
                <td>${NG.pitchBel}</td>
            </tr>

            <tr>
                <td class="tiny">TOL</td>
                <td class="tiny">${NG.tolOut}<br>${NG.rangeOut}</td>
                <td class="tiny">${NG.tolPitch}<br>${NG.rangePitch}</td>
                <td colspan="3" class="tiny">OUT Ø</td>
                <td colspan="3" class="tiny">PITCH Ø</td>
            </tr>
        </tbody>
    </table>

    <table class="footer-table">
        <tr>
            <th style="width:14%;">KETERANGAN</th>
            <td>${qaKeterangan(keterangan)}</td>
        </tr>
    </table>

    <table class="sign-table">
        <tr>
            <td><b>DIBUAT</b><br><br><br>${qaSignatureName(data.operator || data.petugas)}<br>${qaApprovalDate('pembuat')}</td>
            <td><b>DIPERIKSA</b><br><br><br>${qaSignatureName(data.pemeriksa || data.foreman)}<br>${qaApprovalDate('pemeriksa')}</td>
            <td><b>DIKETAHUI</b><br><br><br>${qaSignatureName(data.penyetuju || data.supervisor)}<br>${qaApprovalDate('penyetuju')}</td>
        </tr>
    </table>

    <div class="bottom-line" style="margin-top:3px;"></div>
</div>
`;

        // ================= FORM A (GAUGE) - EXCEL PRINT =================
    if (String(data.format || '').toUpperCase() === 'A') {
        const hasil = Array.isArray(data.hasil_array)
            ? (data.hasil_array[0] || {})
            : (data.hasil_array || data.form_a || {});

        const go = hasil.go || {};
        const notGo = hasil.not_go || {};
        const alatStandar = hasil.alat_standar || data.alat_standar || {};

        /*
        * Format A:
        * - STANDAR UJI di header = input header "Standar Uji"
        * - SPEK + NO ALAT pada section ALAT STANDAR = input
        *   STD Spesifikasi + STD No Alat dari masing-masing GO / NOT GO
        * - STD UKURAN tetap memakai std_out / std_pitch
        */
        const alatStandarGo = {
            no_alat: pick(
                go.std_no_alat,
                go.no_alat,
                alatStandar.go && alatStandar.go.no_alat
            ),
            spesifikasi: pick(
                go.std_spesifikasi,
                go.spesifikasi,
                go.std_spek,
                go.spek,
                alatStandar.go && alatStandar.go.spesifikasi
            )
        };

        const alatStandarNotGo = {
            no_alat: pick(
                notGo.std_no_alat,
                notGo.no_alat,
                alatStandar.not_go && alatStandar.not_go.no_alat
            ),
            spesifikasi: pick(
                notGo.std_spesifikasi,
                notGo.spesifikasi,
                notGo.std_spek,
                notGo.spek,
                alatStandar.not_go && alatStandar.not_go.spesifikasi
            )
        };

        function esc(value) {
            if (value === null || value === undefined) return '';
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function val(value) {
            if (
                value === null ||
                value === undefined ||
                String(value).trim() === ''
            ) {
                return '///';
            }

            return esc(String(value).trim());
        }

        function indoDate(value) {
            if (!value || String(value).trim() === '') return '///';

            const s = String(value).trim();

            if (!/^\d{4}-\d{2}-\d{2}$/.test(s)) {
                return esc(s);
            }

            const parts = s.split('-');

            const months = [
                'Januari',
                'Februari',
                'Maret',
                'April',
                'Mei',
                'Juni',
                'Juli',
                'Agustus',
                'September',
                'Oktober',
                'November',
                'Desember'
            ];

            return (
                parts[2] +
                ' ' +
                months[parseInt(parts[1], 10) - 1] +
                ' ' +
                parts[0]
            );
        }

        function hasilUkuran(block, jenis) {
            block = block || {};

            const out = block.out || {};
            const pitch = block.pitch || {};

            return {
                /*
                * SECTION ALAT STANDAR
                * Ambil langsung dari STD Spesifikasi / STD No Alat.
                */
                stdSpesifikasi: val(
                    pick(
                        block.std_spesifikasi,
                        block.spesifikasi,
                        block.std_spek,
                        block.spek
                    )
                ),

                stdNoAlat: val(
                    pick(
                        block.std_no_alat,
                        block.no_alat,
                        block.nomor_alat,
                        block.no_part
                    )
                ),

                /*
                * SECTION STD UKURAN
                * Nilai seperti 23.789 ±0.011 harus tetap berada di sini.
                */
                stdOut: val(block.std_out),
                tolOut: val(block.tol_out),
                rangeOut: val(block.range_out),

                stdPitch: val(block.std_pitch),
                tolPitch: val(block.tol_pitch),
                rangePitch: val(block.range_pitch),

                /*
                * HASIL UKUR
                */
                outDep: val(out.dep),
                outTen: val(out.ten),
                outBel: val(out.bel),

                pitchDep: val(pitch.dep),
                pitchTen: val(pitch.ten),
                pitchBel: val(pitch.bel),

                jenis: jenis
            };
        }

        const G = hasilUkuran(go, 'GO');
        const NG = hasilUkuran(notGo, 'NOT GO');

        const noAlat =
            data.no_part ||
            data.no_alat ||
            '';

        const namaAlat =
            data.nama_part ||
            data.nama_alat ||
            '';

        const spesifikasi =
            data.spesifikasi ||
            '';

        const seksi =
            data.seksi_pemilik ||
            data.seksi ||
            '';

        const tanggalPeriksa =
            data.tanggal_periksa ||
            data.tanggal ||
            '';

        const periksaBerikut =
            hasil.periksa_berikut ||
            data.periksa_berikut ||
            data.jadwal_berikutnya ||
            '';

        const kodeVarian =
            hasil.kode_varian ||
            data.kode_varian ||
            '';

        const warna =
            hasil.warna ||
            data.warna ||
            '';

        const kep =
            hasil.kep ||
            data.kep ||
            '';

        const keterangan =
            hasil.keterangan ||
            data.keterangan ||
            '';

        /*
        * Hindari °C dan % menjadi double jika input user
        * sudah mengandung simbolnya.
        */
        const suhuRaw = String(data.suhu || '')
            .trim()
            .replace(/\s*°?\s*C\s*$/i, '');

        const kelembapanRaw = String(data.kelembapan || '')
            .trim()
            .replace(/\s*%\s*$/g, '');

        document.getElementById('report').innerHTML = `
    <div class="report">

        <div class="company">
            PT. SURYA TOTO INDONESIA Tbk.
        </div>

        <div class="top-line"></div>

        <table style="width:100%;border-collapse:collapse;margin-top:3px;">
            <tr>
                <td
                    style="
                        width:68%;
                        text-align:center;
                        font-size:13px;
                        font-weight:bold;
                    "
                >
                    LAPORAN PEMERIKSAAN GAUGE
                </td>

                <td class="date-box">
                    TANGGAL : ${indoDate(tanggalPeriksa)}

                    <div
                        style="
                            border-bottom:1px solid #000;
                            margin-top:2px;
                        "
                    ></div>
                </td>
            </tr>
        </table>

        <table class="header">
            <tr>

                <!-- ================= KIRI ================= -->
                <td style="width:32%;">
                    <table class="info">

                        <tr>
                            <td class="label">
                                NO LAPORAN
                            </td>

                            <td>
                                : ${val(data.no_laporan)}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">
                                NAMA ALAT
                            </td>

                            <td>
                                : ${val(namaAlat)}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">
                                METODE
                            </td>

                            <td>
                                : ${val(data.metode || hasil.metode)}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">
                                SEKSI
                            </td>

                            <td>
                                : ${val(seksi)}
                            </td>
                        </tr>

                    </table>
                </td>


                <!-- ================= TENGAH ================= -->
                <td style="width:42%;">
                    <table class="info">

                        <!--
                            HEADER HARUS MENGAMBIL DATA DARI INPUT
                            STANDAR UJI, BUKAN DARI GO / NOT GO.
                        -->
                        <tr>
                            <td class="label">
                                STANDAR UJI
                            </td>

                            <td>
                                : ${val(data.standar_uji)}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">
                                TRACEABILITY
                            </td>

                            <td>
                                : ${val(
                                    alatStandar.traceability ||
                                    data.traceability
                                )}
                            </td>
                        </tr>

                        <tr>
                            <td class="label">
                                SUHU/HUMIDITY
                            </td>

                            <td>
                                :
                                ${val(suhuRaw)} °C
                                /
                                ${val(kelembapanRaw)} %
                            </td>
                        </tr>

                        <tr>
                            <td class="label">
                                TGL PERIKSA
                            </td>

                            <td>
                                : ${indoDate(tanggalPeriksa)}
                            </td>
                        </tr>

                    </table>
                </td>


                <!-- ================= VALIDASI ================= -->
                <td class="approval">
                    <table>

                        <tr>
                            <td>
                                <b>DIBUAT</b>
                            </td>

                            <td>
                                <b>DIPERIKSA</b>
                            </td>

                            <td>
                                <b>DIKETAHUI</b>
                            </td>
                        </tr>

                        <tr class="sign-space">

                            <td>
                                ${qaSignatureName(
                                    data.operator ||
                                    data.petugas
                                )}
                                <br>
                                ${qaApprovalDate('pembuat')}
                            </td>

                            <td>
                                ${qaSignatureName(
                                    data.pemeriksa ||
                                    data.foreman
                                )}
                                <br>
                                ${qaApprovalDate('pemeriksa')}
                            </td>

                            <td>
                                ${qaSignatureName(
                                    data.penyetuju ||
                                    data.supervisor
                                )}
                                <br>
                                ${qaApprovalDate('penyetuju')}
                            </td>

                        </tr>

                        <tr>
                            <td>
                                <b>PETUGAS</b>
                            </td>

                            <td>
                                <b>FOREMAN</b>
                            </td>

                            <td>
                                <b>SUPERVISOR</b>
                            </td>
                        </tr>

                    </table>
                </td>

            </tr>
        </table>


        <div class="page-no">
            HAL: 1/1
        </div>


        <!-- ================= TABLE HASIL ================= -->

        <table class="main-table">

            <colgroup>
                <col style="width:3%;">
                <col style="width:7%;">
                <col style="width:12%;">
                <col style="width:7%;">
                <col style="width:8%;">
                <col style="width:6%;">
                <col style="width:7%;">
                <col style="width:7%;">
                <col style="width:6%;">
                <col style="width:6%;">
                <col style="width:6%;">
                <col style="width:6%;">
                <col style="width:6%;">
                <col style="width:6%;">
                <col style="width:7%;">
            </colgroup>

            <thead>

                <tr>

                    <th rowspan="4">
                        NO
                    </th>

                    <th colspan="2">
                        YANG DIPERIKSA
                    </th>

                    <th colspan="2">
                        ALAT STANDAR
                    </th>

                    <th colspan="7">
                        HASIL PERIKSA
                    </th>

                    <th rowspan="4">
                        WARNA
                    </th>

                    <th rowspan="4">
                        KEP
                    </th>

                    <th rowspan="4">
                        PERIKSA<br>
                        BERIKUT
                    </th>

                </tr>


                <tr>

                    <th rowspan="3">
                        NO ALAT
                    </th>

                    <th rowspan="3">
                        SPEK
                    </th>

                    <th rowspan="3">
                        SPEK
                    </th>

                    <th rowspan="3">
                        NO ALAT
                    </th>

                    <th rowspan="3">
                        POSISI<br>
                        UKUR
                    </th>

                    <th colspan="6">
                        HASIL UKUR
                    </th>

                </tr>


                <tr>

                    <th colspan="3">
                        OUT Ø
                    </th>

                    <th colspan="3">
                        PITCH Ø
                    </th>

                </tr>


                <tr>

                    <th>
                        DEP
                    </th>

                    <th>
                        TEN
                    </th>

                    <th>
                        BEL
                    </th>

                    <th>
                        DEP
                    </th>

                    <th>
                        TEN
                    </th>

                    <th>
                        BEL
                    </th>

                </tr>

            </thead>


            <tbody>

                <!-- ================= GO ================= -->

                <tr>

                    <td
                        rowspan="4"
                        class="bold"
                    >
                        1
                    </td>

                    <td rowspan="4">
                        ${val(noAlat)}
                    </td>

                    <td
                        rowspan="4"
                        class="left"
                    >
                        ${val(spesifikasi)}
                    </td>


                    <!--
                        INI SECTION ALAT STANDAR.
                        SPEK = STD Spesifikasi GO
                        NO ALAT = STD No Alat GO

                        JANGAN isi stdOut di sini.
                    -->
                    <td rowspan="2">
                        ${G.stdSpesifikasi}
                    </td>

                    <td rowspan="2">
                        ${G.stdNoAlat}
                    </td>


                    <!-- POSISI -->
                    <td class="bold">
                        GO
                    </td>


                    <!-- HASIL OUT -->
                    <td>
                        ${G.outDep}
                    </td>

                    <td>
                        ${G.outTen}
                    </td>

                    <td>
                        ${G.outBel}
                    </td>


                    <!-- HASIL PITCH -->
                    <td>
                        ${G.pitchDep}
                    </td>

                    <td>
                        ${G.pitchTen}
                    </td>

                    <td>
                        ${G.pitchBel}
                    </td>


                    <td rowspan="4">
                        ${val(warna)}
                    </td>

                    <td rowspan="4">
                        ${val(kep)}
                    </td>

                    <td rowspan="4">
                        ${indoDate(periksaBerikut)}
                    </td>

                </tr>


                <!-- ================= GO TOLERANSI ================= -->

                <tr>

                    <td class="tiny">
                        TOL
                    </td>

                    <td colspan="3" class="tiny">
                        ${G.tolOut}
                        <br>
                        ${G.rangeOut}
                    </td>

                    <td colspan="3" class="tiny">
                        ${G.tolPitch}
                        <br>
                        ${G.rangePitch}
                    </td>

                </tr>


                <!-- ================= NOT GO ================= -->

                <tr>

                    <!--
                        SECTION ALAT STANDAR NOT GO
                        juga mengambil STD Spesifikasi + STD No Alat
                    -->
                    <td rowspan="2">
                        ${NG.stdSpesifikasi}
                    </td>

                    <td rowspan="2">
                        ${NG.stdNoAlat}
                    </td>


                    <td class="bold">
                        NOT GO
                    </td>


                    


                    <!-- HASIL OUT -->
                    <td>
                        ${NG.outDep}
                    </td>

                    <td>
                        ${NG.outTen}
                    </td>

                    <td>
                        ${NG.outBel}
                    </td>


                    <!-- HASIL PITCH -->
                    <td>
                        ${NG.pitchDep}
                    </td>

                    <td>
                        ${NG.pitchTen}
                    </td>

                    <td>
                        ${NG.pitchBel}
                    </td>

                </tr>


                <!-- ================= NOT GO TOLERANSI ================= -->

                <tr>

                    <td class="tiny">
                        TOL
                    </td>

                    <td colspan="3" class="tiny">
                        ${NG.tolOut}
                        <br>
                        ${NG.rangeOut}
                    </td>

                    <td colspan="3" class="tiny">
                        ${NG.tolPitch}
                        <br>
                        ${NG.rangePitch}
                    </td>

                </tr>

            </tbody>

        </table>


        <!-- ================= FOOTER ================= -->

        <table class="footer-table">

            <tr>

                <th style="width:14%;">
                    KODE VARIAN
                </th>

                <td style="width:25%;">
                    ${val(kodeVarian)}
                </td>

                <th style="width:14%;">
                    KETERANGAN
                </th>

                <td>
                    ${qaKeterangan(keterangan)}
                </td>

            </tr>

        </table>


        <div
            class="bottom-line"
            style="margin-top:3px;"
        ></div>

    </div>
    `;

        return;
    }

    // ================= LEGACY FORMAT E/F/G/N/R + FALLBACK A =================
    // Form B-D/H-Q sudah ditangani di atas oleh renderer teman.
    // Bagian ini mempertahankan renderer lama untuk E/F/G/N/R dan format lain.
    {
                    const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                    let dRaw = String(data.tanggal_periksa || '').trim();
                    let d = dRaw ? new Date(dRaw + 'T00:00:00') : null;
                    let dValid = d && !Number.isNaN(d.getTime());
                    let dStr = dValid ? d.getDate().toString().padStart(2, '0') + ' - ' + (d.getMonth()+1).toString().padStart(2, '0') + ' - ' + d.getFullYear() : '///';
                    let dSignText = dValid ? d.getDate() + ' ' + monthNames[d.getMonth()] + ' ' + d.getFullYear() : '///';
            
                    function formatTanggalReversed(tglStr) {
                        if(!tglStr) return '-';
                        var p = tglStr.split('-');
                        if(p.length !== 3) return tglStr;
                        return p[2] + ' - ' + p[1] + ' - ' + p[0];
                    }
            
                    let dbStr = (data.jadwal_berikutnya && data.jadwal_berikutnya !== 'null' && data.jadwal_berikutnya !== '-') ? formatTanggalReversed(data.jadwal_berikutnya) : '<b>Memerlukan Kalibrasi</b>';

                    let crossSuhu = (!data.suhu || data.suhu === '') ? 'diagonal-strike' : '';
                    let crossKelembapan = (!data.kelembapan || data.kelembapan === '') ? 'diagonal-strike' : '';
                    let crossKetidakpastian = (!data.ketidakpastian || data.ketidakpastian === '') ? 'diagonal-strike' : '';
                    let crossCL = (!data.confidence_level || data.confidence_level === '') ? 'diagonal-strike' : '';

                    data.hasil_array = Array.isArray(data.hasil_array) ? data.hasil_array : [];
                     var hasBagian = data.hasil_array.some(r => r && r.bagian && String(r.bagian).replace(/[- ]/g, '') !== '');
                    var hasToleransi = data.hasil_array.some(r => r.toleransi !== undefined && r.toleransi !== null && r.toleransi !== '' && r.toleransi !== 0 && String(r.toleransi).replace(/[- ]/g, '') !== '');

                    var maxRowsPerPageFirst = 18; 
                    var maxRowsPerPageNext = 40;  
                    var rowsDataHtml = []; 
            
                    if (data.format === 'F') {
                        var fData = normalizeFData(data.hasil_array[0] || {});

                        // FORMAT F: buang row SCALA TEST yang benar-benar kosong agar
                        // tidak menghasilkan kotak/gap kosong pada hasil print.
                        var sec2Clean = (Array.isArray(fData.sec2) ? fData.sec2 : []).filter(function(row) {
                            row = row || {};
                            return [row.standar, row.hasil, row.koreksi].some(function(v) {
                                return v !== undefined && v !== null && String(v).trim() !== '';
                            });
                        });

                        var rowsSec2 = '';
                        sec2Clean.forEach(function(row, idx) {
                            row = row || {};
                            rowsSec2 += `
                            <tr>
                                <td colspan="2" style="border-right:1px solid #000;border-bottom:1px solid #000;text-align:center;vertical-align:middle;">${idx+1}</td>
                                <td colspan="4" style="border-right:1px solid #000;border-bottom:1px solid #000;text-align:center;vertical-align:middle;">${htmlCell(row.standar)}</td>
                                <td colspan="4" style="border-right:1px solid #000;border-bottom:1px solid #000;text-align:center;vertical-align:middle;">${htmlCell(row.hasil)}</td>
                                <td colspan="2" style="border-bottom:1px solid #000;text-align:center;vertical-align:middle;">${htmlCell(row.koreksi)}</td>
                            </tr>`;
                        });

                        // Satu tabel 12 kolom untuk seluruh HASIL KALIBRASI Format F.
                        // Dengan satu tabel tidak ada sambungan border, row spacer,
                        // atau kotak kosong di antara section I, II dan III.
                        rowsDataHtml.push(`
                            <table style="width:100%;border-collapse:collapse;border-spacing:0;text-align:center;table-layout:fixed;margin:0;padding:0;">
                                <colgroup>
                                    <col span="12" style="width:8.333333%;">
                                </colgroup>

                                <tr>
                                    <td colspan="12" style="border-bottom:1px solid #000;text-align:left;padding:2px 4px;vertical-align:middle;">
                                        I. KEMAMPUAN ULANG ( REPEATABILITY OF READING )
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;">STANDAR<br>( ${htmlCell(data.satuan)} )</td>
                                    <td colspan="4" style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;">HASIL<br>( ${htmlCell(data.satuan)} )</td>
                                    <td colspan="4" style="border-bottom:1px solid #000;vertical-align:middle;">DEVIASI<br>( ${htmlCell(data.satuan)} )</td>
                                </tr>
                                <tr>
                                    <td colspan="4" style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;">${htmlCell(fData.sec1.standar)}</td>
                                    <td colspan="4" style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;">${htmlCell(fData.sec1.hasil)}</td>
                                    <td colspan="4" style="border-bottom:1px solid #000;vertical-align:middle;">${htmlCell(fData.sec1.deviasi)}</td>
                                </tr>

                                <tr>
                                    <td colspan="12" style="border-bottom:1px solid #000;text-align:left;padding:2px 4px;vertical-align:middle;">
                                        II. KEMAMPUAN NILAI SCALA ( SCALA TEST )
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;">NO</td>
                                    <td colspan="4" style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;">STANDAR<br>( ${htmlCell(data.satuan)} )</td>
                                    <td colspan="4" style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;">HASIL<br>( ${htmlCell(data.satuan)} )</td>
                                    <td colspan="2" style="border-bottom:1px solid #000;vertical-align:middle;">KOREKSI<br>( ${htmlCell(data.satuan)} )</td>
                                </tr>
                                ${rowsSec2}

                                <tr>
                                    <td colspan="12" style="border-bottom:1px solid #000;text-align:left;padding:2px 4px;vertical-align:middle;">
                                        III. EFECT CENTER LOADING
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;">TENGAH<br>( ${htmlCell(data.satuan)} )</td>
                                    <td colspan="2" style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;">BELAKANG<br>( ${htmlCell(data.satuan)} )</td>
                                    <td colspan="2" style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;">KANAN<br>( ${htmlCell(data.satuan)} )</td>
                                    <td colspan="2" style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;">DEPAN<br>( ${htmlCell(data.satuan)} )</td>
                                    <td colspan="2" style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;">KIRI<br>( ${htmlCell(data.satuan)} )</td>
                                    <td colspan="2" style="border-bottom:1px solid #000;vertical-align:middle;">PERBEDAAN DARI TENGAH<br>( ${htmlCell(data.satuan)} )</td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="border-right:1px solid #000;vertical-align:middle;">${htmlCell(fData.sec3.tengah)}</td>
                                    <td colspan="2" style="border-right:1px solid #000;vertical-align:middle;">${htmlCell(fData.sec3.belakang)}</td>
                                    <td colspan="2" style="border-right:1px solid #000;vertical-align:middle;">${htmlCell(fData.sec3.kanan)}</td>
                                    <td colspan="2" style="border-right:1px solid #000;vertical-align:middle;">${htmlCell(fData.sec3.depan)}</td>
                                    <td colspan="2" style="border-right:1px solid #000;vertical-align:middle;">${htmlCell(fData.sec3.kiri)}</td>
                                    <td colspan="2" style="vertical-align:middle;">${htmlCell(fData.sec3.perbedaan)}</td>
                                </tr>
                            </table>
                        `);
                    } 
                    else if (data.format === 'E') {
                        data.hasil_array.forEach(function(row, idx) {
                            var clsT1 = (!row.t1_hasil || row.t1_hasil === '') ? 'diagonal-strike-td' : '';
                            rowsDataHtml.push(`
                            <tr>
                                <td style="border-right: 1px solid #000; border-bottom: 1px dotted #000; vertical-align:middle; text-align:center;">${idx+1}</td>
                                <td style="border-right: 1px solid #000; border-bottom: 1px dotted #000; vertical-align:middle; text-align:center;">${htmlCell(row.standar)} <span style="margin-left:5px;">±${htmlCell(row.toleransi)}</span></td>
                                <td style="border-right: 1px solid #000; border-bottom: 1px dotted #000; vertical-align:middle; text-align:center;" class="${clsT1}">${htmlCell(row.t1_hasil)}</td>
                                <td style="border-right: 1px solid #000; border-bottom: 1px dotted #000; vertical-align:middle; text-align:center;" class="${clsT1}">${htmlCell(row.t1_koreksi)}</td>
                                <td style="border-right: 1px solid #000; border-bottom: 1px dotted #000; vertical-align:middle; text-align:center;">${htmlCell(row.t2_hasil)}</td>
                                <td style="border-bottom: 1px dotted #000; vertical-align:middle; text-align:center;">${htmlCell(row.t2_koreksi)}</td>
                            </tr>`);
                        });
                    }
                    else if (data.format === 'G') {
                        data.hasil_array.forEach(function(row, idx) {
                            rowsDataHtml.push(`
                            <tr>
                                <td style="border-right: 1px solid #000; border-bottom: 1px dotted #000; vertical-align:middle; padding:5px 3px; line-height:1.35; white-space:normal; overflow-wrap:anywhere;">${idx+1}</td>
                                <td style="border-right: none; border-bottom: 1px dotted #000; text-align:right; padding:5px 15px 5px 3px; vertical-align:middle; line-height:1.35; white-space:normal; overflow-wrap:anywhere;">${htmlCell(row.standar)}</td>
                                <td style="border-left: none; border-right: 1px solid #000; border-bottom: 1px dotted #000; text-align:left; padding:5px 3px 5px 15px; vertical-align:middle; line-height:1.35; white-space:normal; overflow-wrap:anywhere;">±${htmlCell(row.toleransi)}</td>
                                <td style="border-right: 1px solid #000; border-bottom: 1px dotted #000; vertical-align:middle; padding:5px 3px; line-height:1.35; white-space:normal; overflow-wrap:anywhere;">${htmlCell(row.hasil)}</td>
                                <td style="border-bottom: 1px dotted #000; vertical-align:middle; padding:5px 3px; line-height:1.35; white-space:normal; overflow-wrap:anywhere;">${htmlCell(row.hasil_2)}</td>
                            </tr>`);
                        });
                    }
                    else if(data.format === 'N') {
                        const nRows = Array.isArray(data.hasil_array) ? data.hasil_array : [];
                        const fmtN3 = function(v) {
                            if (v === null || v === undefined || String(v).trim() === '') return '';
                            const n = Number(String(v).trim().replace(',', '.'));
                            return Number.isFinite(n) ? n.toFixed(3) : v;
                        };
                        let iN = 0;
                        while (iN < nRows.length) {
                            const tolKey = String(nRows[iN].toleransi == null ? '' : nRows[iN].toleransi).trim();
                            let spanN = 1;
                            while (iN + spanN < nRows.length && String(nRows[iN + spanN].toleransi == null ? '' : nRows[iN + spanN].toleransi).trim() === tolKey) spanN++;
                            for (let jN = 0; jN < spanN; jN++) {
                                const row = nRows[iN + jN] || {};
                                const positions = Array.isArray(row.posisi_hasil) && row.posisi_hasil.length ? row.posisi_hasil : [];
                                const cell = positions[0] || {};
                                const hasil = row.hasil !== undefined && String(row.hasil).trim() !== '' ? row.hasil : (cell.hasil !== undefined ? cell.hasil : '');
                                const koreksi = row.koreksi !== undefined && String(row.koreksi).trim() !== '' ? row.koreksi : (cell.koreksi !== undefined ? cell.koreksi : '');
                                const ket = row.status || row.keterangan || '-';
                                let tr = '<tr>';
                                tr += '<td style="border-right:1px solid #000;border-bottom:1px dotted #000;padding:3px 2px;text-align:center;">'+(iN+jN+1)+'</td>';
                                tr += '<td style="border-bottom:1px dotted #000;padding:3px 4px;text-align:left;">'+htmlCell(row.standar)+'</td>';
                                if (jN === 0) tr += '<td rowspan="'+spanN+'" style="border-right:1px solid #000;border-bottom:1px dotted #000;padding:3px 2px;text-align:center;vertical-align:middle;">'+(tolKey ? '&plusmn;'+htmlCell(tolKey) : '')+'</td>';
                                tr += '<td style="border-right:1px solid #000;border-bottom:1px dotted #000;padding:3px 2px;text-align:center;">'+htmlCell(fmtN3(hasil))+'</td>';
                                tr += '<td style="border-right:1px solid #000;border-bottom:1px dotted #000;padding:3px 2px;text-align:center;">'+htmlCell(fmtN3(koreksi))+'</td>';
                                tr += '<td style="font-weight:normal;border-bottom:1px dotted #000;padding:3px 2px;text-align:center;">'+htmlCell(ket)+'</td></tr>';
                                rowsDataHtml.push(tr);
                            }
                            iN += spanN;
                        }
                    }
                    else if(data.format === 'R') {
                        const fmtR3 = function(v) {
                            if (v === null || v === undefined || String(v).trim() === '') return '';
                            const n = Number(String(v).trim().replace(',', '.'));
                            return Number.isFinite(n) ? n.toFixed(3) : String(v).trim();
                        };
                        data.hasil_array.forEach(function(row) {
                            const stdNum = Number(String(row.standar == null ? '' : row.standar).replace(',', '.'));
                            const tolNum = Number(String(row.toleransi == null ? '' : row.toleransi).replace(',', '.'));
                            const okRange = Number.isFinite(stdNum) && Number.isFinite(tolNum);
                            const tolAbs = okRange ? Math.abs(tolNum) : null;
                            const minRange = okRange ? (stdNum - tolAbs).toFixed(3) : '';
                            const maxRange = okRange ? (stdNum + tolAbs).toFixed(3) : '';
                            const stdText = Number.isFinite(stdNum) ? stdNum.toFixed(3).replace(/\.000$/, '') : htmlCell(row.standar || '-');
                            const tolText = okRange ? tolAbs.toFixed(3) : htmlCell(row.toleransi || '');
                            rowsDataHtml.push(`
                                <tr style="height:25mm;">
                                    <td style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;">${htmlCell(row.bagian || '-')}</td>
                                    <td style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;padding:1mm 0;line-height:1.45;">
                                        <div>${stdText}${tolText ? '&nbsp;&nbsp;&nbsp;&plusmn;'+tolText : ''}</div>
                                        <div style="margin-top:4mm;">${okRange ? minRange+' ~ '+maxRange : '-'}</div>
                                    </td>
                                    <td style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;">${htmlCell(fmtR3(row.depan) || '-')}</td>
                                    <td style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;">${htmlCell(fmtR3(row.tengah) || '-')}</td>
                                    <td style="border-bottom:1px solid #000;vertical-align:middle;">${htmlCell(fmtR3(row.belakang) || '-')}</td>
                                </tr>`);
                        });
                    }
                    else {
                        data.hasil_array.forEach(function(row, idx) {
                            var tr = '<tr>';
                            tr += `<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">${idx+1}</td>`;
                            if(hasBagian) { tr += `<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">${htmlCell(row.bagian || '-')}</td>`; }
                            tr += `<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">${htmlCell(row.standar)}</td>`;
                            if(hasToleransi) { tr += `<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">± ${htmlCell(row.toleransi || '-')}</td>`; }
                            tr += `<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">${htmlCell(row.hasil)}</td>`;
                            tr += `<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">${htmlCell(row.koreksi)}</td>`;
                            tr += `<td style="font-weight:bold; border-bottom: 1px solid #000;">${htmlCell(row.status || '-')}</td>`;
                            tr += '</tr>';
                            rowsDataHtml.push(tr);
                        });
                    }

                    var totalPages = 1;
                    var pagesData = [];
            
                    if (data.format === 'F') {
                        pagesData.push(rowsDataHtml[0]); 
                    } else {
                        var currentChunk = [];
                        for (var i = 0; i < rowsDataHtml.length; i++) {
                            if (totalPages === 1 && currentChunk.length === maxRowsPerPageFirst) {
                                pagesData.push(currentChunk.join(''));
                                currentChunk = []; totalPages++;
                            } else if (totalPages > 1 && currentChunk.length === maxRowsPerPageNext) {
                                pagesData.push(currentChunk.join(''));
                                currentChunk = []; totalPages++;
                            }
                            currentChunk.push(rowsDataHtml[i]);
                        }
                        if (currentChunk.length > 0) { pagesData.push(currentChunk.join('')); }
                    }

                    var finalHtml = '';
                    var formatNeedU = !(['G','N','R','E'].includes(data.format));

                    for (var pageIndex = 0; pageIndex < pagesData.length; pageIndex++) {
                        var isLastPage = (pageIndex === pagesData.length - 1);
                        var pageNum = pageIndex + 1;
                        var dataTabelHasil = pagesData[pageIndex];

                        finalHtml += `<div class="a4-page">`;
                        finalHtml += `<div class="page-info">Halaman ${pageNum} / ${pagesData.length}</div>`;
                        finalHtml += `<div class="pt-title">PT. SURYA TOTO INDONESIA Tbk.</div>`;
                
                        finalHtml += `<table class="table-excel" style="${data.format === 'E' || data.format === 'G' ? 'margin-top:0px;' : ''}">`;
                        finalHtml += `<tr><td colspan="4" style="border-bottom: 1px solid #000; padding:10px 0;"><div class="header-title" style="margin:0;">LAPORAN ${data.format === 'R' ? 'KALIBRASI' : 'PEMERIKSAAN'}</div></td></tr>`;
                
                        if (pageIndex === 0) {
                            if (data.format === 'F' || data.format === 'E' || data.format === 'G' || data.format === 'R') {
                                finalHtml += `
                                <tr>
                                    <td width="20%">NO. LAPORAN</td><td width="30%">${val(data.no_laporan || data.nomor_pengecekan)}</td>
                                    <td width="20%">SEKSI</td><td width="30%" class="text-bold text-center">${val(data.seksi_pemilik)}</td>
                                </tr>
                                <tr>
                                    <td>${data.format === 'F' ? 'NOMOR ALAT' : 'NAMA ALAT'}</td><td>${data.format === 'F' ? data.no_part : data.nama_part}</td>
                                    <td rowspan="2">PETUGAS ${data.format === 'R' || data.format === 'F' ? 'KALIBRASI' : 'PERIKSA'}</td><td rowspan="2" class="text-center">${qaSignatureName(data.operator || data.petugas)}<br>${qaApprovalDate('pembuat')}</td>
                                </tr>
                                <tr>
                                    <td>${data.format === 'F' ? 'NAMA ALAT' : 'NO. ALAT'}</td><td>${data.format === 'F' ? data.nama_part : data.no_part}</td>
                                </tr>
                                <tr>
                                    <td>SPESIFIKASI</td><td>${val(data.spesifikasi)}</td>
                                    <td ${data.format === 'G' ? 'rowspan="2"' : ''} style="${data.format === 'R' ? 'border-bottom:none;' : ''}">TANGGAL ${data.format === 'R' || data.format === 'F' ? 'KALIBRASI' : 'PERIKSA'}</td><td ${data.format === 'G' ? 'rowspan="2"' : ''} class="text-center" style="${data.format === 'R' ? 'border-bottom:none;' : ''}">${dStr}</td>
                                </tr>
                                <tr>
                                    <td>RESOLUSI</td><td>${data.resolusi || '-'}</td>
                                    ${data.format === 'G' ? '' : (data.format === 'R' ? '<td style="border-top:none;"></td><td style="border-top:none;"></td>' : '<td></td><td></td>')}
                                </tr>`;
                            } else {
                                finalHtml += `
                                <tr>
                                    <td width="15%">NO. LAPORAN</td><td width="35%">${val(data.no_laporan || data.nomor_pengecekan)}</td>
                                    <td width="15%">SEKSI</td><td width="35%" class="text-bold text-center">${val(data.seksi_pemilik)}</td>
                                </tr>
                                <tr>
                                    <td>NAMA ALAT</td><td>${val(data.nama_part)}</td>
                                    <td rowspan="2">PETUGAS PERIKSA</td><td rowspan="2" class="text-center">${qaSignatureName(data.operator || data.petugas)}<br>${qaApprovalDate('pembuat')}</td>
                                </tr>
                                <tr><td>NO. ALAT</td><td>${val(data.no_part)}</td></tr>
                                <tr>
                                    <td>SPESIFIKASI</td><td>${val(data.spesifikasi)}</td>
                                    <td>TANGGAL PERIKSA</td><td class="text-center">${dStr}</td>
                                </tr>
                                <tr><td>RESOLUSI</td><td colspan="3">${data.resolusi || '-'}</td></tr>`;
                            }
                        } else {
                            finalHtml += `<tr><td width="20%">NO. ALAT</td><td width="30%">${val(data.no_part)}</td><td width="20%">NAMA ALAT</td><td width="30%">${val(data.nama_part)}</td></tr>`;
                        }

                        finalHtml += `<tr><td colspan="4" class="text-center" style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 0;">HASIL ${data.format === 'R' || data.format === 'F' ? 'KALIBRASI' : 'PEMERIKSAAN'} ${pageIndex > 0 ? '( Lanjutan )' : ''}</td></tr>`;
                        finalHtml += `<tr><td colspan="4" style="padding:0; border:none; line-height:0;">`;
                
                        if (data.format === 'F') {
                            finalHtml += `<div style="line-height:normal; margin:0; padding:0;">${dataTabelHasil}</div>`; 
                        } 
                        else if (data.format === 'E') {
                            finalHtml += `
                                <table style="width:100%; border-collapse: collapse; text-align:center; table-layout:fixed;">
                                    <colgroup><col style="width: 10%;"><col style="width: 30%;"><col style="width: 15%;"><col style="width: 15%;"><col style="width: 15%;"><col style="width: 15%;"></colgroup>
                                    <tr>
                                        <td rowspan="2" style="border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align:middle;">NO</td>
                                        <td rowspan="2" style="border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align:middle;">STANDAR<br>( &deg; C )</td>
                                        <td colspan="2" style="border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align:middle;">T1</td>
                                        <td colspan="2" style="border-bottom: 1px solid #000; vertical-align:middle;">T2</td>
                                    </tr>
                                    <tr>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">HASIL</td>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">KOREKSI</td>
                                        <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">HASIL</td>
                                        <td style="border-bottom: 1px solid #000;">KOREKSI</td>
                                    </tr>
                                    ${dataTabelHasil}
                                    <tr><td style="border-right: 1px solid #000; height:15px; border-bottom:none;"></td><td style="border-right: 1px solid #000; border-bottom:none;"></td><td style="border-right: 1px solid #000; border-bottom:none;"></td><td style="border-right: 1px solid #000; border-bottom:none;"></td><td style="border-right: 1px solid #000; border-bottom:none;"></td><td style="border-bottom:none;"></td></tr>
                                </table>
                            `;
                        }
                        else if (data.format === 'G') {
                            finalHtml += `
                                <table style="width:100%; border-collapse: collapse; text-align:center; table-layout:fixed;">
                                    <colgroup><col style="width: 10%;"><col style="width: 20%;"><col style="width: 20%;"><col style="width: 25%;"><col style="width: 25%;"></colgroup>
                                    <tr>
                                        <td rowspan="2" width="10%" style="border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align:middle; padding:7px 3px; line-height:1.35; white-space:normal;">NO</td>
                                        <td colspan="2" width="40%" style="border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align:middle; padding:7px 3px; line-height:1.35; white-space:normal;">NILAI STANDAR</td>
                                        <td colspan="2" width="50%" style="border-bottom: 1px solid #000; vertical-align:middle; padding:7px 3px; line-height:1.35; white-space:normal;">HASIL</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding:6px 3px; line-height:1.35; vertical-align:middle;">${val(data.satuan)}</td>
                                        <td width="25%" style="border-right: 1px solid #000; border-bottom: 1px solid #000; padding:6px 3px; line-height:1.35; vertical-align:middle;">${val(data.satuan)}</td>
                                        <td width="25%" style="border-bottom: 1px solid #000; padding:6px 3px; line-height:1.35; vertical-align:middle;">${val(data.satuan)}</td>
                                    </tr>
                                    ${dataTabelHasil}
                                </table>
                            `;
                        }
                        else if (data.format === 'N') {
                            finalHtml += `
                                <table style="width:100%;border-collapse:collapse;text-align:center;table-layout:fixed;font-size:10px;line-height:1.15;">
                                    <colgroup>
                                        <col style="width:9%;">
                                        <col style="width:18%;">
                                        <col style="width:18%;">
                                        <col style="width:27%;">
                                        <col style="width:18%;">
                                        <col style="width:10%;">
                                    </colgroup>
                                    <tr style="height:48px;">
                                        <td style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;white-space:nowrap;">NO</td>
                                        <td colspan="2" style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;white-space:nowrap;">STANDAR<br>( mm )</td>
                                        <td style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;white-space:nowrap;">HASIL<br>( mm )</td>
                                        <td style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;white-space:nowrap;">KOREKSI</td>
                                        <td style="border-bottom:1px solid #000;vertical-align:middle;white-space:nowrap;">KET</td>
                                    </tr>
                                    ${dataTabelHasil}
                                    <tr><td style="height:18px;border-right:1px solid #000;"></td><td></td><td style="border-right:1px solid #000;"></td><td style="border-right:1px solid #000;"></td><td style="border-right:1px solid #000;"></td><td></td></tr>
                                    <tr><td style="height:18px;border-right:1px solid #000;"></td><td></td><td style="border-right:1px solid #000;"></td><td style="border-right:1px solid #000;"></td><td style="border-right:1px solid #000;"></td><td></td></tr>
                                    <tr><td style="height:18px;border-right:1px solid #000;"></td><td></td><td style="border-right:1px solid #000;"></td><td style="border-right:1px solid #000;"></td><td style="border-right:1px solid #000;"></td><td></td></tr>
                                </table>`;
                        }
                        else if (data.format === 'R') {
                            finalHtml += `
                                <table style="width:100%; border-collapse:collapse; text-align:center; table-layout:fixed;">
                                    <colgroup>
                                        <col style="width:20%;"><col style="width:20%;">
                                        <col style="width:20%;"><col style="width:20%;"><col style="width:20%;">
                                    </colgroup>
                                    <tr style="height:7mm;">
                                        <td colspan="5" style="border-bottom:1px solid #000;vertical-align:middle;">HASIL KALIBRASI</td>
                                    </tr>
                                    <tr style="height:9mm;">
                                        <td rowspan="2" style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;">POSISI</td>
                                        <td rowspan="2" style="border-right:1px solid #000;border-bottom:1px solid #000;vertical-align:middle;">STANDAR</td>
                                        <td colspan="3" style="border-bottom:1px solid #000;vertical-align:middle;">HASIL</td>
                                    </tr>
                                    <tr style="height:6mm;">
                                        <td style="border-right:1px solid #000;border-bottom:1px solid #000;">DEPAN</td>
                                        <td style="border-right:1px solid #000;border-bottom:1px solid #000;">TENGAH</td>
                                        <td style="border-bottom:1px solid #000;">BELAKANG</td>
                                    </tr>
                                    ${dataTabelHasil}
                                    <tr style="height:20mm;">
                                        <td style="border-right:1px solid #000;"></td>
                                        <td style="border-right:1px solid #000;"></td>
                                        <td style="border-right:1px solid #000;"></td>
                                        <td style="border-right:1px solid #000;"></td>
                                        <td></td>
                                    </tr>
                                </table>`;
                        }
                        else {
                            colHtml = '<colgroup><col style="width: 20%;"><col style="width: 20%;"><col style="width: 15%;"><col style="width: 15%;"><col style="width: 15%;"><col style="width: 15%;"></colgroup>';

                            var headerRow1 = '<tr>';
                            if(hasBagian) headerRow1 += `<td width="18%" style="border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align:middle;">POSISI</td>`;
                            headerRow1 += `<td width="${hasBagian?'17%':'20%'}" style="border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align:middle;">STANDAR</td>`;
                            headerRow1 += `<td colspan="3" style="border-bottom: 1px solid #000;">HASIL</td>`;
                            headerRow1 += `<td rowspan="2" style="border-bottom: 1px solid #000; vertical-align:middle;">KEPUTUSAN</td>`;
                            headerRow1 += '</tr>';

                            var headerRow2 = '<tr>';
                            if(hasBagian) headerRow2 += `<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"></td>`;
                            headerRow2 += `<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"></td>`;
                            headerRow2 += `<td width="${hasBagian?'16%':'15%'}" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">DEPAN</td>`;
                            headerRow2 += `<td width="${hasBagian?'16%':'15%'}" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">TENGAH</td>`;
                            headerRow2 += `<td width="${hasBagian?'16%':'15%'}" style="border-bottom: 1px solid #000;">BELAKANG</td>`;
                            headerRow2 += '</tr>';
                    
                            var emptyRow = '<tr>';
                            if(hasBagian) emptyRow += '<td style="border-right: 1px solid #000; height:15px; border-bottom:none;"></td>';
                            emptyRow += '<td style="border-right: 1px solid #000; border-bottom:none;"></td><td style="border-right: 1px solid #000; border-bottom:none;"></td><td style="border-right: 1px solid #000; border-bottom:none;"></td><td style="border-bottom:none;"></td></tr>';

                            finalHtml += `
                                <table style="width:100%; border-collapse: collapse; text-align:center; table-layout:fixed;">
                                    ${colHtml}
                                    ${headerRow1}
                                    ${headerRow2}
                                    ${dataTabelHasil}
                                    ${emptyRow}
                                </table>
                            `;
                        }
                        finalHtml += `</td></tr>`;

                        // LOGIKA FOOTER UNIFIED (MENGHILANGKAN U & CL UNTUK G,N,R,E SECARA MUTLAK)
                        if (isLastPage) {
                            var footerHtml = '';
                            if (formatNeedU) {
                                footerHtml += `
                                <tr>
                                    <td width="20%" style="border-top: 1px solid #000;">METODE</td><td width="30%" style="border-top: 1px solid #000;">${val(data.metode)}</td>
                                    <td width="20%" style="border-top: 1px solid #000;">CONFIDENCE LEVEL</td><td width="30%" style="border-top: 1px solid #000;" class="text-center ${crossCL}">${data.confidence_level ? data.confidence_level : ''}</td>
                                </tr>
                                <tr>
                                    <td rowspan="2" style="vertical-align:middle;">STANDAR UJI</td><td rowspan="2" style="vertical-align:middle;">${(data.standar_uji||'').replace(/\n/g, "<br>")}</td>
                                    <td style="vertical-align:middle;">KETIDAKPASTIAN</td><td class="text-center ${crossKetidakpastian}" style="vertical-align:middle;">${data.ketidakpastian ? data.ketidakpastian : ''}</td>
                                </tr>
                                <tr>
                                    <td style="vertical-align:middle;">STANDAR TOLERANSI</td><td class="text-center ${data.format === 'A' ? 'diagonal-strike' : ''}" style="vertical-align:middle;">${data.format === 'A' ? '' : (data.format === 'F' ? (data.toleransi_global||'').split('|').map(v=>v.replace(/^TOL[^:]*:\s*/i, '').trim()).filter(v=>v!=='').join('<br>') : (data.toleransi_global||'').replace(/\\n/g, "<br>"))}</td>
                                </tr>
                                <tr>
                                    <td>TRACEABILITY</td><td>${val(data.traceability)}</td>
                                    <td>KEPUTUSAN</td><td class="text-center text-bold" style="font-size: 16px;">${val(data.status_final)}</td>
                                </tr>
                                <tr>
                                    <td>SUHU RUANGAN</td><td class="text-center ${crossSuhu}">${data.suhu ? data.suhu : ''}</td>
                                    <td rowspan="2" style="vertical-align:middle;">PERIKSA BERIKUTNYA</td><td rowspan="2" class="text-center" style="vertical-align:middle;">${dbStr}</td>
                                </tr>
                                <tr>
                                    <td>KELEMBABAN RUANGAN</td><td class="text-center ${crossKelembapan}">${data.kelembapan ? data.kelembapan : ''}</td>
                                </tr>
                                `;
                            } else if (data.format === 'N') {
                                footerHtml += `
                                <tr>
                                    <td width="20%" style="border-top: 1px solid #000;">METODE</td><td width="30%" style="border-top: 1px solid #000;">${val(data.metode)}</td>
                                    <td width="20%" style="border-top: 1px solid #000;">KELEMBABAN RUANGAN</td><td width="30%" style="border-top: 1px solid #000;" class="text-center ${crossKelembapan}">${data.kelembapan ? data.kelembapan : ''}</td>
                                </tr>
                                <tr>
                                    <td rowspan="2" style="vertical-align:middle;">STANDAR UJI</td><td rowspan="2" style="vertical-align:middle;">${(data.standar_uji||'').replace(/\n/g, "<br>")}</td>
                                    <td rowspan="2" style="vertical-align:middle;">STANDAR TOLERANSI</td><td rowspan="2" class="text-center" style="vertical-align:middle;">${(data.toleransi_global||'').replace(/\\n/g, "<br>")}</td>
                                </tr>
                                <tr></tr>
                                <tr>
                                    <td>TRACEABILITY</td><td>${val(data.traceability)}</td>
                                    <td rowspan="2" style="vertical-align:middle;">KEPUTUSAN</td><td rowspan="2" class="text-center text-bold" style="font-size:16px;vertical-align:middle;">${val(data.status_final)}</td>
                                </tr>
                                <tr>
                                    <td>CONFIDENCE LEVEL</td><td class="text-center ${crossCL}">${data.confidence_level ? data.confidence_level : ''}</td>
                                </tr>
                                <tr>
                                    <td>SUHU RUANGAN</td><td class="text-center ${crossSuhu}">${data.suhu ? data.suhu : ''}</td>
                                    <td>PERIKSA BERIKUTNYA</td><td class="text-center">${dbStr}</td>
                                </tr>
                                `;
                            } else {
                                footerHtml += `
                                <tr>
                                    <td width="20%" style="border-top: 1px solid #000;">METODE</td><td width="30%" style="border-top: 1px solid #000;">${val(data.metode)}</td>
                                    <td width="20%" style="border-top: 1px solid #000;">KELEMBABAN RUANGAN</td><td width="30%" style="border-top: 1px solid #000;" class="text-center ${crossKelembapan}">${data.kelembapan ? data.kelembapan : ''}</td>
                                </tr>
                                <tr>
                                    <td rowspan="2" style="vertical-align:middle;">STANDAR UJI</td><td rowspan="2" style="vertical-align:middle;">${(data.standar_uji||'').replace(/\n/g, "<br>")}</td>
                                    <td rowspan="2" style="vertical-align:middle;">STANDAR TOLERANSI</td><td rowspan="2" class="text-center ${data.format === 'A' ? 'diagonal-strike' : ''}" style="vertical-align:middle;">${data.format === 'A' ? '' : (data.format === 'F' ? (data.toleransi_global||'').split('|').map(v=>v.replace(/^TOL[^:]*:\s*/i, '').trim()).filter(v=>v!=='').join('<br>') : (data.toleransi_global||'').replace(/\\n/g, "<br>"))}</td>
                                </tr>
                                <tr></tr>
                                <tr>
                                    <td>TRACEABILITY</td><td>${val(data.traceability)}</td>
                                    <td>KEPUTUSAN</td><td class="text-center text-bold" style="font-size: 16px;">${val(data.status_final)}</td>
                                </tr>
                                <tr>
                                    <td>SUHU RUANGAN</td><td class="text-center ${crossSuhu}">${data.suhu ? data.suhu : ''}</td>
                                    <td>PERIKSA BERIKUTNYA</td><td class="text-center">${dbStr}</td>
                                </tr>
                                `;
                            }
                            finalHtml += footerHtml;

                            finalHtml += `
                            <tr>
                                <td colspan="4" style="vertical-align:top; border-top: 1px solid #000; border-bottom: 1px solid #000; text-align:left; padding-left:5px;">KETERANGAN : <br>
<span style="font-style:italic; word-wrap:break-word; word-break:break-word;">${qaKeterangan(data.keterangan)}</span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="4" style="border:none; padding-top: 10px;">
                                    <div style="width: 50%; float: right; text-align: center;">
                                        <div style="text-align:left; margin-bottom: 5px; font-weight:bold;">TANGERANG , ${['R','F','E'].includes(data.format) ? dSignText : dStr}</div>
                                        <table class="table-excel" style="width: 100%;">
                                            <tr><td width="33%">DIBUAT</td><td width="33%">DIPERIKSA</td><td width="33%">DISETUJUI</td></tr>
                                            <tr>
                                                <td style="height: 55px; border-bottom: none; vertical-align:bottom;">${qaSignatureName(data.operator || data.petugas)}<br>${qaApprovalDate('pembuat')}</td>
                                                <td style="height: 55px; border-bottom: none; vertical-align:bottom;">${qaSignatureName(data.pemeriksa || data.foreman)}<br>${qaApprovalDate('pemeriksa')}</td>
                                                <td style="height: 55px; border-bottom: none; vertical-align:bottom;">${qaSignatureName(data.penyetuju || data.supervisor)}<br>${qaApprovalDate('penyetuju')}</td>
                                            </tr>
                                            <tr>
                                                <td style="border-top: none;">PETUGAS</td><td style="border-top: none;">FOREMAN</td><td style="border-top: none;">SUPERVISOR</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div style="clear:both;"></div>
                                </td>
                            </tr>`;
                        } else {
                            finalHtml += `<tr><td colspan="4" style="border:none; height: 30px;"></td></tr>`;
                        }

                        finalHtml += `</table>`;
                        if(isLastPage) { finalHtml += `<div style="text-align: right; font-size: 11px; margin-top: 2px; font-weight: bold;">${data.format === 'N' ? 'STIS-CMFG4-05/R4' : 'STIS-CMFG4-03/R4'}</div>`; }
                        finalHtml += `</div>`; 
                    }

                    document.getElementById('report').innerHTML = finalHtml;
    }

    // Jangan langsung membuka dialog print agar user bisa mengecek tampilan dulu.
})();
</script>
</body>
</html>

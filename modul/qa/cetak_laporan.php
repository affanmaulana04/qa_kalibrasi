<?php
session_start();
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
    height: 28px;
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
.report-d {
    width:206mm; min-height:293mm; margin:2mm auto; padding:0 0 2mm;
    border:0.5mm solid #000; background:#fff; color:#000; font:9pt Arial,sans-serif;
}
.report-d * { box-sizing:border-box; }
.company-d { text-align:center; font:bold 9pt "Times New Roman",serif; height:4mm; }
.report-title-d { height:18mm; display:flex; align-items:center; justify-content:center; font:16pt "Times New Roman",serif; }
.info-d,.result-d,.detail-d { width:176mm; margin:0 auto; border-collapse:collapse; table-layout:fixed; }
.info-d td,.detail-d td { border:0.25mm solid #000; padding:0.7mm 1mm; vertical-align:middle; overflow-wrap:anywhere; }
.info-d .d-label { width:17%; } .info-d .d-value { width:33%; }
.info-d .d-label-r { width:30%; } .info-d .d-value-r { width:20%; text-align:center; }
.hasil-title-d { width:176mm; margin:0 auto; height:6mm; border:0.25mm solid #000; border-top:0; text-align:center; padding:1mm; }
.result-d th,.result-d td { border:0.25mm solid #000; text-align:center; vertical-align:middle; padding:1mm; font-weight:normal; }
.result-d th { height:10mm; } .result-d td { height:6.5mm; }
.result-d .unit-row td { height:5mm; font-size:8pt; }
.result-d .mode-col { width:9%; } .result-d .no-col { width:9%; }
.result-d .setting-col { width:22%; } .result-d .std-col { width:20%; } .result-d .hasil-col { width:20%; } .result-d .koreksi-col { width:20%; }
.detail-d .d-lbl { width:17%; } .detail-d .d-val { width:33%; } .detail-d .d-lbl-r { width:30%; } .detail-d .d-val-r { width:20%; text-align:center; }
.detail-d .ket-d { height:25mm; vertical-align:top; white-space:pre-wrap; }
.city-date-d { width:96mm; margin:0 15mm 0 auto; height:5mm; padding-top:1mm; }
.sign-d { width:96mm; margin:0 15mm 0 auto; border-collapse:collapse; table-layout:fixed; break-inside:avoid; }
.sign-d th,.sign-d td { border:0.25mm solid #000; text-align:center; padding:1mm; font-weight:normal; }
.sign-d th { height:5mm; } .sign-d .sign-space-d td { height:28mm; vertical-align:bottom; font-size:8pt; } .sign-d .roles-d td { height:8mm; }
.doc-code-d { text-align:right; font-size:7pt; margin:3mm 1mm 0; }


/* ================= FORM H - THREAD PLUG GAUGE ================= */
.report-h { width:206mm; min-height:293mm; margin:2mm auto; padding:0 0 2mm; border:0.5mm solid #000; background:#fff; color:#000; font:6.5pt Arial,sans-serif; }
.report-h * { box-sizing:border-box; }
.company-h { text-align:center; font:bold 8pt Arial,sans-serif; height:5mm; }
.title-h { text-align:center; font:bold 13pt Arial,sans-serif; padding:4mm 0 2mm; }
.header-h { width:184mm; margin:0 auto; border-collapse:collapse; table-layout:fixed; }
.header-h td { padding:0.4mm 0.8mm; vertical-align:top; }
.header-h .left-h { width:38%; } .header-h .mid-h { width:34%; } .header-h .sign-h { width:28%; padding:0; }
.sign-h-table { width:100%; border-collapse:collapse; table-layout:fixed; }
.sign-h-table td { border:0.25mm solid #000; text-align:center; padding:0.5mm; }
.sign-h-table .space-h td { height:10mm; vertical-align:bottom; font-size:6pt; }
.page-h { width:184mm; margin:1mm auto; text-align:right; font-weight:bold; }
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
.header-i { width:176mm; margin:0 auto; border-collapse:collapse; table-layout:fixed; }
.header-i td { padding:0.5mm 1mm; vertical-align:top; }
.header-i .left-i { width:48%; }
.header-i .mid-i { width:30%; }
.header-i .sign-i { width:22%; padding:0; }
.sign-head-i { width:100%; border-collapse:collapse; table-layout:fixed; }
.sign-head-i td { border:0.25mm solid #000; text-align:center; padding:0.7mm; }
.sign-head-i .space-i td { height:12mm; vertical-align:bottom; font-size:7pt; }
.page-i { width:176mm; margin:1mm auto 1mm; text-align:right; font-weight:bold; }
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
.top-q { width:196mm; margin:0 auto 1mm; display:flex; justify-content:space-between; align-items:flex-start; }
.title-q { text-align:center; font:14pt Arial,sans-serif; font-weight:bold; padding:2mm 0 1mm; flex:1; }
.datebox-q { width:62mm; font-size:6.5pt; }
.datebox-q table,.head-q table { border-collapse:collapse; width:100%; table-layout:fixed; }
.datebox-q td,.datebox-q th,.head-q td,.grid-q th,.grid-q td { border:0.25mm solid #000; padding:0.45mm 0.55mm; vertical-align:middle; }
.head-q { width:196mm; margin:0 auto 1mm; }
.grid-q { width:196mm; margin:0 auto; border-collapse:collapse; table-layout:fixed; }
.grid-q th,.grid-q td { text-align:center; }
.grid-q .left { text-align:left; }
.ket-q { width:196mm; margin:0 auto; border:0.25mm solid #000; border-top:0; min-height:14mm; padding:1mm; white-space:pre-line; }
.foot-q { width:196mm; margin:1mm auto 0; text-align:right; font-size:6.6pt; }
/* ================= FORM P - SK GAUGE ================= */
.report-p { width:206mm; min-height:293mm; margin:2mm auto; padding:0 0 2mm; border:0.5mm solid #000; background:#fff; color:#000; font:6.8pt Arial,sans-serif; }
.report-p * { box-sizing:border-box; }
.company-p { text-align:center; font-weight:bold; height:5mm; padding-top:1mm; }
.top-p { width:196mm; margin:0 auto 1mm; display:flex; justify-content:space-between; align-items:flex-start; }
.title-p { text-align:center; font:14pt Arial,sans-serif; font-weight:bold; padding:2mm 0 1mm; flex:1; }
.datebox-p { width:62mm; font-size:6.7pt; }
.datebox-p table,.head-p table,.sign-p table { border-collapse:collapse; width:100%; table-layout:fixed; }
.datebox-p td,.datebox-p th,.head-p td,.grid-p th,.grid-p td,.sign-p td,.sign-p th { border:0.25mm solid #000; padding:0.5mm 0.7mm; vertical-align:middle; }
.head-p { width:196mm; margin:0 auto 1mm; }
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
.top-o { width:196mm; margin:0 auto 1mm; display:flex; justify-content:space-between; align-items:flex-start; }
.title-o { text-align:center; font:15pt Arial,sans-serif; font-weight:bold; padding:2mm 0 1mm; flex:1; }
.datebox-o { width:50mm; font-size:7pt; }
.datebox-o table, .head-o table, .detail-o table, .sign-o table { border-collapse:collapse; width:100%; table-layout:fixed; }
.datebox-o td, .datebox-o th, .head-o td, .detail-o td, .grid-o th, .grid-o td, .sign-o td, .sign-o th { border:0.25mm solid #000; padding:0.6mm 1mm; vertical-align:middle; }
.head-o { width:196mm; margin:0 auto 1mm; }
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

/* ================= MODE MONOKROM SEMUA FORMAT ================= */
.report, .report-b, .report-c, .report-d, .report-h, .report-i, .report-j, .report-k, .report-l, .report-m, .report-o, .report-p, .report-q,
.report table, .report-b table, .report-c table, .report-d table, .report-h table, .report-i table, .report-j table, .report-k table, .report-l table, .report-m table, .report-o table, .report-p table, .report-q table,
.report th, .report td, .report-b th, .report-b td, .report-c th, .report-c td, .report-d th, .report-d td, .report-h th, .report-h td, .report-i th, .report-i td, .report-j th, .report-j td, .report-k th, .report-k td, .report-l th, .report-l td, .report-m th, .report-m td, .report-o th, .report-o td, .report-p th, .report-p td, .report-q th, .report-q td {
    background: #fff !important;
    background-color: #fff !important;
    color: #000 !important;
}
.ok, .bad, .ok-b, .bad-b, .ok-c, .bad-c {
    color: #000 !important;
}

@media screen { .report-c, .report-b, .report-d, .report-h, .report-i, .report-j, .report-k, .report-l, .report-m, .report-o, .report-p, .report-q { max-width:100%; } }
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
        .table-excel th, .table-excel td { border: 1px solid #000; padding: 4px; page-break-inside: avoid; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; } 
        
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
            body { background: #fff; padding: 0; margin: 0; } 
            .a4-page { box-shadow: none; margin: 0; padding: 10mm 15mm; border: none; } 
            .no-print { display: none !important; } 
        }
    </style>
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
    const raw = sessionStorage.getItem('cetak_data');

    if (!raw) {
        document.getElementById('report').innerHTML =
            '<div style="padding:20px;font-weight:bold;">Data cetak tidak ditemukan. Silakan kembali ke halaman pemeriksaan.</div>';
        return;
    }

    let data;
    try {
        data = JSON.parse(raw);
    } catch (e) {
        document.getElementById('report').innerHTML =
            '<div style="padding:20px;font-weight:bold;">Data cetak tidak valid.</div>';
        return;
    }


    // ================= FORM Q (SERATION / PLUG GAUGE) =================
    if (String(data.format || '').toUpperCase() === 'Q') {
        const esc = (v) => {
            if (v === null || v === undefined || String(v).trim() === '') return '///';
            return String(v).trim().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
        };
        const dateShort = (v) => {
            if (!v) return '///';
            const m=String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return m ? m[3]+' - '+m[2]+' - '+m[1] : esc(v);
        };
        const dateLong = (v) => {
            if (!v) return '///';
            const m=String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if(!m) return esc(v);
            const b=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            return m[3]+' '+b[parseInt(m[2],10)-1]+' '+m[1];
        };
        const q = Array.isArray(data.hasil_array) && data.hasil_array.length ? data.hasil_array[0] : {};
        const subtype=String(q.subtype||'SERATION').toUpperCase();
        const header=`
            <div class="company-q">PT. SURYA TOTO INDONESIA Tbk.</div>
            <div class="top-q">
                <div class="title-q">LAPORAN PEMERIKSAAN GAUGE</div>
                <div class="datebox-q"><table>
                    <tr><td colspan="3">TANGGAL : ${dateLong(data.tanggal_periksa)}</td></tr>
                    <tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr>
                    <tr style="height:15mm;"><td style="vertical-align:bottom;text-align:center;">${esc(data.petugas)}</td><td style="vertical-align:bottom;text-align:center;">${esc(data.foreman)}</td><td style="vertical-align:bottom;text-align:center;">${esc(data.supervisor)}</td></tr>
                    <tr><td style="text-align:center;">PETUGAS</td><td style="text-align:center;">FOREMAN</td><td style="text-align:center;">SUPERVISOR</td></tr>
                </table></div>
            </div>
            <div class="head-q"><table>
                <tr><td>NO LAPORAN</td><td>: ${esc(data.no_laporan)}</td><td>ALAT STANDAR</td><td>: ${esc(data.standar_uji)}</td></tr>
                <tr><td>NAMA ALAT</td><td>: ${esc(data.nama_alat)}</td><td>TRACEABILITY</td><td>: ${esc(data.traceability)}</td></tr>
                <tr><td>METODE</td><td>: ${esc(data.metode)}</td><td>SUHU / HUMIDITY</td><td>: ${esc(data.suhu)} °C / ${esc(data.kelembapan)} %</td></tr>
                <tr><td>SEKSI</td><td>: ${esc(data.seksi)}</td><td>TANGGAL PERIKSA</td><td>: ${dateLong(data.tanggal_periksa)}</td></tr>
            </table></div>
            <div style="width:196mm;margin:0 auto 1mm;text-align:right;">HAL : ${esc(data.halaman || '1 / 1')}</div>`;

        let table='';
        if(subtype==='SERATION'){
            const out=q.out||{}, pitch=q.pitch||{};
            const row=(label,r,first)=>`<tr>
                ${first?`<td rowspan="2">1</td><td rowspan="2">${esc(data.no_part)}</td><td rowspan="2">${esc(data.spesifikasi)}</td>`:''}
                <td>${esc(r.std_spesifikasi)}</td><td>${esc(r.std_no_alat)}</td>
                <td style="font-weight:bold;">Ø ${label}</td>
                <td>${esc(r.std)}<br><small>${esc(r.tol_plus)} / ${esc(r.tol_minus)}</small><br><small>(${esc(r.min)} ~ ${esc(r.max)})</small></td>
                <td>${esc((r.actual||[])[0])}</td><td>${esc((r.actual||[])[1])}</td><td>${esc((r.actual||[])[2])}</td>
                ${first?`<td rowspan="2">${esc(q.kode_warna)}</td><td rowspan="2">${esc(q.keputusan)}</td><td rowspan="2">${dateShort(data.jadwal_berikutnya)}</td>`:''}
            </tr>`;
            table=`<table class="grid-q"><thead>
                <tr><th rowspan="2" style="width:4%">NO</th><th rowspan="2" style="width:10%">NO ALAT</th><th rowspan="2" style="width:10%">SPESIFIKASI</th><th colspan="2">ALAT STANDAR YANG DIPAKAI</th><th rowspan="2" style="width:8%">POSISI</th><th rowspan="2" style="width:18%">STANDAR UKURAN</th><th colspan="3">HASIL UKUR (CROSS)</th><th rowspan="2" style="width:7%">KODE WARNA</th><th rowspan="2" style="width:6%">KEP</th><th rowspan="2" style="width:10%">TANGGAL KALIBRASI BERIKUTNYA</th></tr>
                <tr><th>SPESIFIKASI</th><th>NO ALAT</th><th>I</th><th>II</th><th>III</th></tr></thead>
                <tbody>${row('OUT',out,true)}${row('PITCH',pitch,false)}</tbody></table>`;
        } else {
            const go=q.go||{}, ng=q.notgo||{};
            const vals=(r)=>r.actual||{};
            const row=(label,r,first)=>{const a=vals(r);return `<tr>
                ${first?`<td rowspan="2">1</td><td rowspan="2">${esc(data.no_part)}</td><td rowspan="2">${esc(data.spesifikasi)}</td><td rowspan="2">${esc(q.std_spesifikasi)}</td><td rowspan="2">${esc(q.std_no_alat)}</td>`:''}
                <td style="font-weight:bold;">${label}</td><td>${esc(r.std)}<br><small>${esc(r.tol_plus)} / ${esc(r.tol_minus)}</small><br><small>(${esc(r.min)} ~ ${esc(r.max)})</small></td>
                <td>${esc(a.front1)}</td><td>${esc(a.front2)}</td><td>${esc(a.back1)}</td><td>${esc(a.back2)}</td>
                ${first?`<td rowspan="2">${esc(q.kode_warna)}</td><td rowspan="2">${esc(q.keputusan)}</td><td rowspan="2">${dateShort(data.jadwal_berikutnya)}</td><td rowspan="2">${esc(q.no_gambar)}</td>`:''}
            </tr>`;};
            table=`<table class="grid-q"><thead>
                <tr><th colspan="3">ALAT YANG DIPERIKSA</th><th colspan="2">ALAT STANDAR YANG DIPAKAI</th><th colspan="6">HASIL PERIKSA</th><th rowspan="2">KODE WARNA</th><th rowspan="2">KEP</th><th rowspan="2">TANGGAL PERIKSA BERIKUTNYA</th><th rowspan="2">NO GAMBAR</th></tr>
                <tr><th>NO</th><th>NO ALAT</th><th>SPESIFIKASI</th><th>SPESIFIKASI</th><th>NO ALAT</th><th>POSISI UKUR</th><th>STD UKURAN</th><th colspan="2">DEPAN</th><th colspan="2">BELAKANG</th></tr>
                </thead><tbody>${row('GO',go,true)}${row('NOT GO',ng,false)}</tbody></table>`;
        }

        const ket = data.keterangan === null || data.keterangan === undefined ? '' : String(data.keterangan);
        document.getElementById('report').innerHTML=`<div class="report report-q">${header}${table}<div class="ket-q"><b>KETERANGAN :</b><br>${ket.trim()?esc(ket).replace(/\n/g,'<br>'):''}</div><div class="foot-q">STIS-CMFG4-06 / R1</div></div>`;
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

        function makeItemRows(item, itemIndex) {
            const ms = Array.isArray(item.measurements) ? item.measurements : [];
            const n = Math.max(ms.length, 1);

            // hitung rowspan untuk alat standar yang berurutan sama
            const runs = {};
            let k = 0;
            while (k < ms.length) {
                const key = String(ms[k].alat_nama || '') + '||' + String(ms[k].alat_no || '');
                let end = k + 1;
                while (end < ms.length) {
                    const key2 = String(ms[end].alat_nama || '') + '||' + String(ms[end].alat_no || '');
                    if (key2 !== key) break;
                    end++;
                }
                runs[k] = end - k;
                k = end;
            }
            
            let dbStr = (data.jadwal_berikutnya && data.jadwal_berikutnya !== 'null' && data.jadwal_berikutnya !== '-') ? formatTanggalReversed(data.jadwal_berikutnya) : '<b>Memerlukan Kalibrasi</b>';

            return ms.map((m, j) => {
                const first = j === 0;
                const typeHtml = esc(item.type) + (item.ukuran ? '<br>'+esc(item.ukuran) : '') + (item.upart ? '<br>'+esc(item.upart) : '');
                const runSpan = runs[j] || 0;
                const actual2 = String(m.actual_count || '2') === '2' ? esc(m.actual2) : '///';
                let cells = '';

                if (first) {
                    cells += `<td rowspan="${n}">${itemIndex + 1}</td>`;
                    cells += `<td rowspan="${n}">${esc(item.no_alat)}</td>`;
                    cells += `<td rowspan="${n}" class="type-cell">${typeHtml}</td>`;
                }

                if (runSpan > 0) {
                    cells += `<td rowspan="${runSpan}">${esc(m.alat_nama)}</td>`;
                    cells += `<td rowspan="${runSpan}">${esc(m.alat_no)}</td>`;
                }

                cells += `<td class="std-label">${esc(m.std_label)}</td>`;
                cells += `<td>( ${esc(m.min)} ~ ${esc(m.max)} )</td>`;
                cells += `<td>${esc(m.actual1)}</td>`;
                cells += `<td>${actual2}</td>`;

                if (first) {
                    cells += `<td rowspan="${n}" style="font-weight:bold;">${esc(item.keputusan)}</td>`;
                    cells += `<td rowspan="${n}">${dateShort(data.jadwal_berikutnya)}</td>`;
                    cells += `<td rowspan="${n}">${esc(item.no_gambar)}</td>`;
                }

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
                <div class="title-p">LAPORAN PEMERIKSAAN GAUGE</div>
                <div class="datebox-p">
                    <table>
                        <tr><td colspan="3">TANGGAL : ${dateLong(data.tanggal_periksa)}</td></tr>
                        <tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr>
                        <tr style="height:15mm;">
                            <td style="vertical-align:bottom;text-align:center;">${esc(data.petugas)}</td>
                            <td style="vertical-align:bottom;text-align:center;">${esc(data.foreman)}</td>
                            <td style="vertical-align:bottom;text-align:center;">${esc(data.supervisor)}</td>
                        </tr>
                        <tr><td style="text-align:center;">PETUGAS</td><td style="text-align:center;">FOREMAN</td><td style="text-align:center;">SUPERVISOR</td></tr>
                    </table>
                </div>
            </div>

            <div class="head-p">
                <table>
                    <tr><td>NO. LAPORAN</td><td>: ${esc(data.no_laporan)}</td><td>ALAT STANDAR</td><td>: ${esc(data.standar_uji)}</td></tr>
                    <tr><td>NAMA ALAT</td><td>: ${esc(data.nama_alat)}</td><td>TRACEABILITY</td><td>: ${esc(data.traceability)}</td></tr>
                    <tr><td>METODE</td><td>: ${esc(data.metode)}</td><td>SUHU / HUMIDITY</td><td>: ${esc(data.suhu)} °C / ${esc(data.kelembapan)} %</td></tr>
                    <tr><td>SEKSI</td><td>: ${esc(data.seksi)}</td><td>TANGGAL PERIKSA</td><td>: ${dateLong(data.tanggal_periksa)}</td></tr>
                </table>
            </div>

            <div style="width:196mm;margin:0 auto 1mm;text-align:right;">HAL : ${esc(data.halaman || '1 / 1')}</div>

            <table class="grid-p">
                <thead>
                    <tr>
                        <th rowspan="2" style="width:4%;">NO</th>
                        <th rowspan="2" style="width:9%;">NO ALAT</th>
                        <th rowspan="2" style="width:12%;">TYPE DAN UKURAN</th>
                        <th colspan="2">ALAT YANG DIPAKAI</th>
                        <th colspan="4">HASIL PERIKSA</th>
                        <th rowspan="2" style="width:6%;">KEP</th>
                        <th rowspan="2" style="width:10%;">TANGGAL PERIKSA BERIKUTNYA</th>
                        <th rowspan="2" style="width:9%;">NO GAMBAR</th>
                    </tr>
                    <tr>
                        <th style="width:10%;">NAMA ALAT</th>
                        <th style="width:9%;">NO ALAT</th>
                        <th style="width:12%;">STD UKURAN</th>
                        <th style="width:12%;">RANGE</th>
                        <th style="width:7%;">AKTUAL 1</th>
                        <th style="width:7%;">AKTUAL 2</th>
                    </tr>
                </thead>
                <tbody>${body || '<tr><td colspan="12">Tidak ada data.</td></tr>'}</tbody>
            </table>

            <div class="ket-p"><b>KETERANGAN :</b><br>${ketHtml}</div>
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
        const rows = Array.isArray(data.hasil_array) ? data.hasil_array : [];
        const body = rows.map((r,i) => `
            <tr>
                <td rowspan="2">${i+1}</td>
                <td rowspan="2">${esc(r.no_alat)}</td>
                <td rowspan="2" class="spec-cell">${esc(r.spesifikasi)}<br>* ${esc(r.tol_plus)}<br>* ${esc(r.tol_minus)}</td>
                <td>GO</td>
                <td>${esc(r.go_std)}</td>
                <td>${esc(r.go_tol_near)}<br>${esc(r.go_tol_far)}</td>
                <td>${esc(r.go_min)}</td>
                <td>${esc(r.go_max)}</td>
                <td>${esc(r.go_hasil_min)}</td>
                <td>${esc(r.go_hasil_max)}</td>
                <td rowspan="2">${esc(r.kode_warna)}</td>
                <td rowspan="2">${esc(r.keputusan)}</td>
                <td rowspan="2">${dateShort(data.jadwal_berikutnya)}</td>
                <td rowspan="2">${esc(r.no_gambar)}</td>
            </tr>
            <tr>
                <td>NOT GO</td>
                <td>${esc(r.notgo_std)}</td>
                <td>±${esc(r.notgo_tol)}</td>
                <td>${esc(r.notgo_min)}</td>
                <td>${esc(r.notgo_max)}</td>
                <td>${esc(r.notgo_hasil_min)}</td>
                <td>${esc(r.notgo_hasil_max)}</td>
            </tr>
        `).join('');

        document.getElementById('report').innerHTML = `
        <div class="report report-o">
            <div class="company-o">PT. SURYA TOTO INDONESIA Tbk.</div>
            <div class="top-o">
                <div class="title-o">LAPORAN PEMERIKSAAN GAUGE</div>
                <div class="datebox-o">
                    <table>
                        <tr><td colspan="3">TANGGAL : ${dateLong(data.tanggal_periksa)}</td></tr>
                        <tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr>
                        <tr style="height:16mm;"><td style="vertical-align:bottom;text-align:center;">${esc(data.petugas)}</td><td style="vertical-align:bottom;text-align:center;">${esc(data.foreman)}</td><td style="vertical-align:bottom;text-align:center;">${esc(data.supervisor)}</td></tr>
                        <tr><td style="text-align:center;">PETUGAS</td><td style="text-align:center;">FOREMAN</td><td style="text-align:center;">SUPERVISOR</td></tr>
                    </table>
                </div>
            </div>
            <div class="head-o">
                <table>
                    <tr><td>NO LAPORAN</td><td>: ${esc(data.no_laporan)}</td><td>STANDAR UJI / NO ALAT</td><td>: ${esc(data.standar_uji)}</td></tr>
                    <tr><td>NAMA ALAT</td><td>: ${esc(data.nama_alat)}</td><td>TRACEABILITY</td><td>: ${esc(data.traceability)}</td></tr>
                    <tr><td>METODE</td><td>: ${esc(data.metode)}</td><td>SUHU / HUMIDITY</td><td>: ${esc(data.suhu)} / ${esc(data.kelembapan)} %</td></tr>
                    <tr><td>SEKSI</td><td>: ${esc(data.seksi)}</td><td>TANGGAL PERIKSA</td><td>: ${dateLong(data.tanggal_periksa)}</td></tr>
                </table>
            </div>
            <div style="width:196mm;margin:0 auto 1mm;text-align:right;">${esc(data.halaman || '1 / 1')}</div>
            <table class="grid-o">
                <thead>
                    <tr>
                        <th rowspan="4" style="width:4%;">NO</th>
                        <th rowspan="4" style="width:10%;">NO ALAT</th>
                        <th rowspan="4" style="width:8%;">SPESIFIKASI</th>
                        <th colspan="8">HASIL PERIKSA</th>
                        <th rowspan="4" style="width:6%;">KODE WARNA</th>
                        <th rowspan="4" style="width:6%;">KEPUTUSAN</th>
                        <th rowspan="4" style="width:9%;">TANGGAL PERIKSA BERIKUTNYA</th>
                        <th rowspan="4" style="width:9%;">NO GAMBAR</th>
                    </tr>
                    <tr>
                        <th colspan="5">STD UKURAN</th>
                        <th colspan="2">STANDAR HASIL</th>
                    </tr>
                    <tr>
                        <th rowspan="2" style="width:7%;">POSISI UKUR</th>
                        <th rowspan="2" style="width:7%;">TOLERANSI ALAT</th>
                        <th rowspan="2" style="width:8%;">TOLERANSI</th>
                        <th rowspan="2" style="width:7%;">MIN</th>
                        <th rowspan="2" style="width:7%;">MAX</th>
                        <th rowspan="2" style="width:6%;">MIN<br>(MASUK)</th>
                        <th rowspan="2" style="width:6%;">MAX<br>(TIDAK MASUK)</th>
                    </tr>
                    <tr></tr>
                </thead>
                <tbody>${body || '<tr><td colspan="14">Tidak ada data.</td></tr>'}</tbody>
            </table>
            <div class="ket-o"><b>KETERANGAN :</b> ${esc(data.keterangan)}</div>
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
                <td>${esc(r.std_mm)}</td>
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
                    <td>${esc(r.std_degree)} °</td>
                    <td>${hasil}</td>
                    <td>${esc(r.correction)}</td>
                </tr>
            `;
        }).join('');

        const keputusanRaw = String(data.keputusan || '').toUpperCase();
        const keputusan = (keputusanRaw === 'GOOD' || keputusanRaw === 'OK')
            ? 'OK'
            : ((keputusanRaw === 'BAD' || keputusanRaw === 'BURUK') ? 'BURUK' : esc(data.keputusan));

        const tolSudutMenit = parseFloat(data.toleransi_sudut);
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
                    <td>PETUGAS KALIBRASI</td><td>${esc(data.petugas)}</td>
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
                    <td>KELEMBABAN RUANGAN</td><td>${raw(data.kelembapan) ? esc(data.kelembapan)+' %' : '///'}</td>
                </tr>
                <tr>
                    <td>STANDAR UJI</td>
                    <td>${esc(data.standar_uji)}<br>${esc(data.standar_uji_sudut)}</td>
                    <td>KETIDAKPASTIAN</td>
                    <td>${raw(data.ketidakpastian) ? '± '+esc(data.ketidakpastian)+' mm' : '///'}</td>
                </tr>
                <tr>
                    <td>TRACEABILITY</td><td>${esc(data.traceability)}</td>
                    <td>STANDAR TOLERANSI</td>
                    <td>
                        AXIS&nbsp;&nbsp;± ${esc(data.toleransi_axis)} mm<br>
                        SUDUT&nbsp;&nbsp;± ${esc(data.toleransi_sudut)}' (${tolSudutDetikText}")
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
                        <b>KETERANGAN :</b><br>${esc(data.keterangan)}
                    </td>
                </tr>
            </table>

            <div class="city-date-m">TANGERANG, ${dateLong(data.tanggal_periksa)}</div>

            <table class="sign-m">
                <tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr>
                <tr class="sign-space-m">
                    <td>${esc(data.petugas)}</td>
                    <td>${esc(data.foreman)}</td>
                    <td>${esc(data.supervisor)}</td>
                </tr>
                <tr class="role-m">
                    <td>PETUGAS</td><td>FOREMAN</td><td>SUPERVISOR</td>
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
        const mk=(arr,w)=>arr.map((r,i)=>`<tr><td style="font-weight:bold">${i<w.length?w.charAt(i):''}</td><td>${e(r.applied_value)}</td><td>${e(r.indicated_value)}</td><td>${e(r.correction)}</td></tr>`).join('');
        const body=mk(naik,'NAIK')+mk(turun,'TURUN');
        const kr=String(data.keputusan||'').toUpperCase();
        const kep=(kr==='GOOD'||kr==='OK')?'OK':((kr==='BAD'||kr==='BURUK')?'BURUK':e(data.keputusan));

        document.getElementById('report').innerHTML=`<div class="report report-l">
            <div class="company-l">PT.SURYA TOTO INDONESIA Tbk.</div>
            <div class="title-l">LAPORAN KALIBRASI</div>
            <table class="info-l">
                <tr><td>NO. LAPORAN</td><td>${e(data.no_laporan)}</td><td>SEKSI</td><td>${e(data.seksi_pemilik||data.seksi)}</td></tr>
                <tr><td>NAMA ALAT</td><td>${e(data.nama_part||data.nama_alat)}</td><td>PETUGAS KALIBRASI</td><td>${e(data.petugas)}</td></tr>
                <tr><td>NOMOR ALAT</td><td>${e(data.no_part||data.no_alat)}</td><td></td><td></td></tr>
                <tr><td>SPESIFIKASI</td><td>${e(data.spesifikasi)}</td><td>TANGGAL KALIBRASI</td><td>${dt(data.tanggal_periksa)}</td></tr>
                <tr><td>RESOLUSI</td><td>${e(data.resolusi)}</td><td></td><td></td></tr>
            </table>
            <div class="hasil-title-l">HASIL KALIBRASI</div>
            <table class="hasil-l"><thead><tr><th>UJI<br>SKALA</th><th>APPLIED VALUE<br>( MPa )</th><th>INDICATED VALUE<br>( MPa )</th><th>CORRECTION<br>( MPa )</th></tr></thead><tbody>${body||'<tr><td colspan="4">Tidak ada data kalibrasi.</td></tr>'}</tbody></table>
            <table class="detail-l">
                <tr><td>METODE</td><td>${e(data.metode)}</td><td>KELEMBABAN RUANGAN</td><td>${raw(data.kelembapan)?e(data.kelembapan)+' %':'///'}</td></tr>
                <tr><td>STANDAR UJI</td><td>${e(data.standar_uji)}<br>${e(data.std_no_alat)}</td><td>KETIDAKPASTIAN</td><td>${raw(data.ketidakpastian)?'± '+e(data.ketidakpastian)+' MPa':'///'}</td></tr>
                <tr><td>TRACEABILITY</td><td>${e(data.traceability)}</td><td>STANDAR TOLERANSI</td><td>${raw(data.toleransi)?'± '+e(data.toleransi)+' MPa':'///'}</td></tr>
                <tr><td>CONFIDENCE LEVEL</td><td>${raw(data.confidence)?e(data.confidence)+' %':'///'}</td><td>KEPUTUSAN</td><td style="font-weight:bold;text-align:center">${kep}</td></tr>
                <tr><td>SUHU RUANGAN</td><td>${raw(data.suhu)?e(data.suhu)+' °C':'///'}</td><td>KALIBRASI BERIKUTNYA</td><td>${dt(data.jadwal_berikutnya||data.periksa_berikut)}</td></tr>
                <tr><td colspan="4" class="ket-l"><b>KETERANGAN :</b><br>${e(data.keterangan)}</td></tr>
            </table>
            <div class="city-date-l">TANGERANG, ${dl(data.tanggal_periksa)}</div>
            <table class="sign-l"><tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr><tr class="sign-space-l"><td>${e(data.petugas)}</td><td>${e(data.foreman)}</td><td>${e(data.supervisor)}</td></tr><tr class="role-l"><td>PETUGAS</td><td>FOREMAN</td><td>SUPERVISOR</td></tr></table>
            <div class="doc-code-l">STIS-CMFG4-03/R4</div>
        </div>`;return;
    }

    // ================= FORM K (VOLTAGE ULTRASONIC) =================
    if(String(data.format||'').toUpperCase()==='K'){const e=v=>(v===null||v===undefined||String(v).trim()===''?'///':String(v).trim().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;')),raw=v=>(v===null||v===undefined?'':String(v).trim()),dt=v=>{if(!v)return'///';const m=String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);return m?m[3]+'-'+m[2]+'-'+m[1]:e(v)},dl=v=>{if(!v)return'///';const m=String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);if(!m)return e(v);const b=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];return m[3]+' '+b[parseInt(m[2],10)-1]+' '+m[1]},rows=Array.isArray(data.hasil_array)?data.hasil_array:[],body=rows.map((r,i)=>`<tr><td>${i+1}</td><td>${e(r.nilai_ditentukan)}</td><td>${e(r.nilai_pengukuran)}</td><td>${e(r.selisih)}</td><td>${e(r.status)}</td></tr>`).join(''),kr=String(data.keputusan||'').toUpperCase(),kep=(kr==='GOOD'||kr==='OK')?'OK':((kr==='BAD'||kr==='BURUK')?'BURUK':e(data.keputusan));document.getElementById('report').innerHTML=`<div class="report report-k"><div class="company-k">PT. SURYA TOTO INDONESIA Tbk.</div><div class="title-k">LAPORAN PEMERIKSAAN</div><table class="info-k"><tr><td>NO LAPORAN</td><td>${e(data.no_laporan)}</td><td>SEKSI</td><td style="font-weight:bold;text-align:center">${e(data.seksi_pemilik||data.seksi)}</td></tr><tr><td>NAMA ALAT</td><td>${e(data.nama_part||data.nama_alat)}</td><td>PETUGAS PERIKSA</td><td style="text-align:center">${e(data.petugas)}</td></tr><tr><td>NOMOR MESIN</td><td>${e(data.no_part||data.no_alat)}</td><td>TANGGAL PERIKSA</td><td style="text-align:center">${dt(data.tanggal_periksa)}</td></tr><tr><td>SPESIFIKASI</td><td>${e(data.spesifikasi)}</td><td></td><td></td></tr><tr><td>RESOLUSI</td><td>${e(data.resolusi)}</td><td></td><td></td></tr></table><div class="hasil-title-k">HASIL PEMERIKSAAN</div><table class="hasil-k"><thead><tr><th>NO</th><th>NILAI YG DITENTUKAN</th><th>NILAI PENGUKURAN</th><th>SELISIH</th><th>PERTIMBANGAN KELULUSAN</th></tr></thead><tbody>${body||'<tr><td colspan="5">Tidak ada data.</td></tr>'}<tr class="blank-k"><td></td><td></td><td></td><td></td><td></td></tr></tbody></table><table class="detail-k"><tr><td>METODE</td><td>${e(data.metode)}</td><td>KELEMBABAN RUANGAN</td><td>${raw(data.kelembapan)?e(data.kelembapan)+' %':'///'}</td></tr><tr><td>STANDAR UJI</td><td>${e(data.standar_uji)}</td><td>STANDAR TOLERANSI</td><td style="text-align:center">${raw(data.toleransi)?'± '+e(data.toleransi)+' %':'///'}</td></tr><tr><td>TRACEABILITY</td><td>${e(data.traceability)}</td><td>KEPUTUSAN</td><td style="font-weight:bold;text-align:center">${kep}</td></tr><tr><td>SUHU RUANGAN</td><td>${raw(data.suhu)?e(data.suhu)+' °C':'///'}</td><td>TGL PERIKSA BERIKUTNYA</td><td style="text-align:center">${dt(data.jadwal_berikutnya||data.periksa_berikut)}</td></tr><tr><td colspan="4" class="ket-k"><b>KETERANGAN :</b><br>${e(data.keterangan)}</td></tr></table><div class="city-date-k">TANGERANG, ${dl(data.tanggal_periksa)}</div><table class="sign-k"><tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr><tr class="sign-space-k"><td>${e(data.petugas)}</td><td>${e(data.foreman)}</td><td>${e(data.supervisor)}</td></tr><tr class="role-k"><td>PETUGAS</td><td>FOREMAN</td><td>SUPERVISOR</td></tr></table><div class="doc-code-k">STIS-CMFG4-05/R3</div></div>`;return;}

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
                <td>${escJ(r.alat_verifikasi || data.alat_verifikasi)}</td>
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
                    <td class="value2-j" style="text-align:center;">${escJ(data.petugas)}</td>
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
                    <col style="width:8%;">
                    <col style="width:20%;">
                    <col style="width:23%;">
                    <col style="width:25%;">
                    <col style="width:12%;">
                    <col style="width:12%;">
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
                        ${data.keterangan ? '<br>'+escJ(data.keterangan) : ''}
                    </td>
                </tr>
            </table>

            <div class="city-date-j">TANGERANG, ${dateLongJ(data.tanggal_periksa)}</div>
            <table class="sign-j">
                <tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr>
                <tr class="sign-space-j">
                    <td>${escJ(data.petugas)}</td>
                    <td>${escJ(data.foreman)}</td>
                    <td>${escJ(data.supervisor)}</td>
                </tr>
                <tr class="role-j"><td>PETUGAS</td><td>FOREMAN</td><td>SUPERVISOR</td></tr>
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
            if (r) s += (s ? '<br>' : '') + '<span class="small-h">(' + escH(r) + ')</span>';
            return s || '///';
        };
        const firstH = rowsH[0] || {};
        const kodeWarnaH = firstH.kode_warna || '';
        const putusanH = firstH.putusan || ((String(data.keputusan || '').toUpperCase()==='GOOD') ? 'OK' : 'BAD');
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
            <div class="title-h">LAPORAN PEMERIKSAAN GAUGE</div>

            <table class="header-h">
                <tr>
                    <td class="left-h">
                        <table style="width:100%;border-collapse:collapse;">
                            <tr><td>NO LAPORAN</td><td>: ${escH(data.no_laporan)}</td></tr>
                            <tr><td>NAMA ALAT</td><td>: ${escH(data.nama_part || data.nama_alat)}</td></tr>
                            <tr><td>METODE</td><td>: ${escH(data.metode)}</td></tr>
                            <tr><td>SEKSI</td><td>: ${escH(data.seksi_pemilik || data.seksi)}</td></tr>
                        </table>
                    </td>
                    <td class="mid-h">
                        <table style="width:100%;border-collapse:collapse;">
                            <tr><td>ALAT STANDAR</td><td>: ${escH(data.standar_uji)}</td></tr>
                            <tr><td>TRACEABILITY</td><td>: ${escH(data.traceability)}</td></tr>
                            <tr><td>SUHU / HUMIDITY</td><td>: ${escH(data.suhu)} C / ${escH(data.kelembapan)} %</td></tr>
                            <tr><td>TANGGAL PERIKSA</td><td>: ${dateLongH(data.tanggal_periksa)}</td></tr>
                        </table>
                    </td>
                    <td class="sign-h">
                        <div style="text-align:center;margin-bottom:1mm;">TANGGAL : ${dateLongH(data.tanggal_periksa)}</div>
                        <table class="sign-h-table">
                            <tr><td>DIBUAT</td><td>DIPERIKSA</td><td>DISETUJUI</td></tr>
                            <tr class="space-h"><td>${escH(data.petugas)}</td><td>${escH(data.foreman)}</td><td>${escH(data.supervisor)}</td></tr>
                            <tr><td>PETUGAS</td><td>FOREMAN</td><td>SUPERVISOR</td></tr>
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

            <table class="ket-h"><tr><td><b>KETERANGAN :</b><br>${escH(data.keterangan)}</td></tr></table>
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
                <td rowspan="2">${escI(data.spesifikasi)}</td>
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
            <div class="title-i">LAPORAN PEMERIKSAAN GAUGE</div>

            <table class="header-i">
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
                            <tr><td>SUHU/HUMIDITY</td><td>: ${rawI(data.suhu) ? escI(data.suhu)+' C' : '///'} / ${rawI(data.kelembapan) ? escI(data.kelembapan)+' %' : '///'}</td></tr>
                            <tr><td>TANGGAL PERIK.</td><td>: ${dateLongI(data.tanggal_periksa)}</td></tr>
                        </table>
                    </td>
                    <td class="sign-i">
                        <div style="text-align:center;margin-bottom:1mm;">Tanggal : ${dateLongI(data.tanggal_periksa)}</div>
                        <table class="sign-head-i">
                            <tr><td>DIBUAT</td><td>DIPERIKSA</td><td>DISETUJUI</td></tr>
                            <tr class="space-i"><td>${escI(data.petugas)}</td><td>${escI(data.foreman)}</td><td>${escI(data.supervisor)}</td></tr>
                            <tr><td>PETUGAS</td><td>FOREMAN</td><td>SUPERVISOR</td></tr>
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
                <tr><td><b>KETERANGAN :</b><br>${escI(data.keterangan)}</td></tr>
            </table>
            <div class="doc-code-i">STIS-CMFG4-06/R1</div>
        </div>`;
        return;
    }

    // ================= FORM D (PEMERIKSAAN MULTIMETER) =================

    if (String(data.format || '').toUpperCase() === 'D') {
        const rowsD = Array.isArray(data.hasil_array) ? data.hasil_array : [];
        const escD = (v) => {
            if (v === null || v === undefined || String(v).trim() === '') return '///';
            return String(v).trim().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
        };
        const rawD = (v) => (v === null || v === undefined ? '' : String(v).trim());
        const dateD = (v) => {
            if (!v) return '///';
            const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return escD(v);
            return m[3]+'-'+m[2]+'-'+m[1];
        };
        const statusD = String(data.keputusan || data.status_final || '').toUpperCase();
        const keputusanD = (statusD === 'GOOD' || statusD === 'OK') ? 'OK' : ((statusD === 'BAD' || statusD === 'BURUK' || statusD === 'NG') ? 'NG' : escD(data.keputusan || data.status_final));

        let lastUnit = null;
        let noInUnit = 0;
        const bodyD = rowsD.map((r) => {
            const unit = rawD(r.unit) || '';
            let unitRow = '';
            if (unit !== lastUnit) {
                lastUnit = unit;
                noInUnit = 0;
                unitRow = `<tr class="unit-row"><td></td><td></td><td>( ${escD(unit)} )</td><td>( ${escD(unit)} )</td><td>( ${escD(unit)} )</td><td>( ${escD(unit)} )</td></tr>`;
            }
            noInUnit++;
            const mode = rawD(r.mode) === '-' ? '' : escD(r.mode);
            return unitRow + `<tr>
                <td>${noInUnit}</td>
                <td>${mode}</td>
                <td>${escD(r.setting)}</td>
                <td>${escD(r.standar)}</td>
                <td>${escD(r.hasil)}</td>
                <td>${escD(r.koreksi)}</td>
            </tr>`;
        }).join('');

        document.getElementById('report').innerHTML = `
        <div class="report report-d">
            <div class="company-d">PT. SURYA TOTO INDONESIA Tbk.</div>
            <div class="report-title-d">LAPORAN PEMERIKSAAN</div>

            <table class="info-d">
                <colgroup><col class="d-label"><col class="d-value"><col class="d-label-r"><col class="d-value-r"></colgroup>
                <tr><td>NO. LAPORAN</td><td>${escD(data.no_laporan)}</td><td>SEKSI</td><td><b>${escD(data.seksi_pemilik || data.seksi)}</b></td></tr>
                <tr><td>NAMA ALAT</td><td>${escD(data.nama_part || data.nama_alat)}</td><td rowspan="2">PETUGAS PERIKSA</td><td rowspan="2">${escD(data.petugas)}</td></tr>
                <tr><td>NO. ALAT</td><td>${escD(data.no_part || data.no_alat)}</td></tr>
                <tr><td>SPESIFIKASI</td><td>${escD(data.spesifikasi)}</td><td>TANGGAL PERIKSA</td><td>${dateD(data.tanggal_periksa)}</td></tr>
                <tr><td>RESOLUSI</td><td>${escD(data.resolusi)}</td><td></td><td></td></tr>
            </table>

            <div class="hasil-title-d">HASIL PEMERIKSAAN</div>
            <table class="result-d">
                <colgroup><col class="no-col"><col class="mode-col"><col class="setting-col"><col class="std-col"><col class="hasil-col"><col class="koreksi-col"></colgroup>
                <thead>
                    <tr><th rowspan="2">NO</th><th colspan="2">SETTING</th><th>STANDAR</th><th>HASIL</th><th>KOREKSI</th></tr>
                    <tr><th>MODE</th><th></th><th></th><th></th><th></th></tr>
                </thead>
                <tbody>${bodyD || '<tr><td colspan="6">Tidak ada data pemeriksaan.</td></tr>'}</tbody>
            </table>

            <table class="detail-d">
                <colgroup><col class="d-lbl"><col class="d-val"><col class="d-lbl-r"><col class="d-val-r"></colgroup>
                <tr><td>METODE</td><td>${escD(data.metode)}</td><td>KELEMBABAN RUANGAN</td><td>${rawD(data.kelembapan) ? escD(data.kelembapan) + ' %' : '///'}</td></tr>
                <tr><td>STANDAR UJI</td><td>${escD(data.standar_uji)}</td><td>STANDAR TOLERANSI</td><td>${escD(data.toleransi)}</td></tr>
                <tr><td>TRACEABILITY</td><td>${escD(data.traceability)}</td><td>KEPUTUSAN</td><td><b>${keputusanD}</b></td></tr>
                <tr><td>SUHU RUANGAN</td><td>${rawD(data.suhu) ? escD(data.suhu) + ' °C' : '///'}</td><td>PERIKSA BERIKUTNYA</td><td>${dateD(data.jadwal_berikutnya || data.periksa_berikut)}</td></tr>
                <tr><td colspan="4" class="ket-d"><strong>KETERANGAN :</strong><br>${escD(data.keterangan)}</td></tr>
            </table>

            <div class="city-date-d">TANGERANG, ${dateD(data.tanggal_periksa)}</div>
            <table class="sign-d">
                <tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr>
                <tr class="sign-space-d"><td>${escD(data.petugas)}</td><td>${escD(data.foreman)}</td><td>${escD(data.supervisor)}</td></tr>
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
            return '± ' + escC(t) + ' °C';
        })();
        const statusFinalC = String(data.keputusan || data.status_final || '').toUpperCase();
        const keputusanC = (statusFinalC === 'GOOD' || statusFinalC === 'OK')
            ? 'OK'
            : ((statusFinalC === 'BAD' || statusFinalC === 'BURUK' || statusFinalC === 'NG') ? 'NG' : escC(data.keputusan || data.status_final));

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
                <tr><td>NAMA ALAT</td><td>${escC(data.nama_part || data.nama_alat)}</td><td rowspan="2">PETUGAS PERIKSA</td><td rowspan="2">${escC(data.petugas)}</td></tr>
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
                <tr><td>METODE</td><td>${escC(data.metode)}</td><td>KELEMBABAN RUANGAN</td><td>${kelembapanC ? escC(kelembapanC) + ' %' : diagC}</td></tr>
                <tr><td>STANDAR UJI</td><td>${escC(data.standar_uji)}</td><td>STANDAR TOLERANSI</td><td>${toleransiTextC}</td></tr>
                <tr><td>TRACEABILITY</td><td>${escC(data.traceability)}</td><td>KEPUTUSAN</td><td class="${keputusanC === 'OK' ? 'ok-c' : 'bad-c'}">${keputusanC}</td></tr>
                <tr><td>SUHU RUANGAN</td><td>${suhuC ? escC(suhuC) + ' °C' : diagC}</td><td>TGL PERIKSA BERIKUTNYA</td><td>${dateC(data.jadwal_berikutnya || data.periksa_berikut)}</td></tr>
                <tr><td colspan="4" class="ket-c"><strong>KETERANGAN :</strong><br>${escC(data.keterangan)}</td></tr>
            </table>

            <div class="city-date-c">TANGERANG, ${dateC(data.tanggal_periksa)}</div>
            <table class="sign-c">
                <tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr>
                <tr class="sign-space-c"><td>${escC(data.petugas)}</td><td>${escC(data.foreman)}</td><td>${escC(data.supervisor)}</td></tr>
                <tr class="roles-c"><td>PETUGAS</td><td>FOREMAN</td><td>SUPERVISOR</td></tr>
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
        const keputusan = (statusFinal === 'GOOD' || statusFinal === 'OK') ? 'OK' : (statusFinal === 'BAD' || statusFinal === 'BURUK' ? 'NG' : escB(data.keputusan || data.status_final));
        const keputusanClass = keputusan === 'OK' ? 'ok-b' : 'bad-b';

        // Data yang tersedia dari Form B. Fallback ke field lama jika ada.
        const metode = data.metode || '';
        const standarUji = data.standar_uji || '';
        const traceability = data.traceability || '';
        const petugas = data.petugas || '';
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
                <tr><td>NAMA ALAT</td><td>${escB(data.nama_part)}</td><td rowspan="2">PETUGAS PERIKSA</td><td rowspan="2">${escB(petugas)}</td></tr>
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
                    <td class="d-label">KELEMBABAN RUANGAN</td><td class="d-value d-small">${escB(data.kelembapan)} %</td>
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
                    <td class="d-label">SUHU RUANGAN</td><td class="d-value">${escB(data.suhu)} °C</td>
                    <td class="d-label">TGL PERIKSA BERIKUTNYA</td><td class="d-value">${dateB(jadwal)}</td>
                </tr>
                <tr>
                    <td colspan="4" class="ket-box"><strong>KETERANGAN :</strong><br>${escB(data.keterangan)}</td>
                </tr>
            </table>

            <div class="city-date-b">TANGERANG, ${dateB(tanggal)}</div>
            <table class="sign-b">
                <tr><th>DIBUAT</th><th>DIPERIKSA</th><th>DISETUJUI</th></tr>
                <tr class="sign-space-b"><td>${escB(petugas)}</td><td>${escB(data.foreman || '')}</td><td>${escB(data.supervisor || '')}</td></tr>
                <tr class="roles-b"><td>PETUGAS</td><td>FOREMAN</td><td>SUPERVISOR</td></tr>
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
    const kodeVarian = hasil.kode_varian || data.kode_varian || '';
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
                        <td>: ${val(data.suhu)} °C / ${val(data.kelembapan)} %</td>
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
                        <td><b>DIKET</b></td>
                    </tr>
                    <tr class="sign-space">
                        <td></td><td></td><td></td>
                    </tr>
                    <tr>
                        <td><b>PETUGAS</b></td>
                        <td><b>FOREMAN</b></td>
                        <td><b>SPV</b></td>
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

                <td rowspan="2">${G.stdOut}<br><span class="tiny">${G.tolOut}</span></td>
                <td rowspan="2">${val(alatStandarGo.no_alat)}<br><span class="tiny">${val(alatStandarGo.spesifikasi)}</span></td>

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
                <td rowspan="2">${NG.stdOut}<br><span class="tiny">${NG.tolOut}</span></td>
                <td rowspan="2">${val(alatStandarNotGo.no_alat)}<br><span class="tiny">${val(alatStandarNotGo.spesifikasi)}</span></td>
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
            <th style="width:14%;">KODE VARIAN</th>
            <td style="width:25%;">${val(kodeVarian)}</td>
            <th style="width:14%;">KETERANGAN</th>
            <td>${val(keterangan)}</td>
        </tr>
    </table>

    <table class="sign-table">
        <tr>
            <td><b>DIBUAT</b><br><br><br>${val(data.petugas)}</td>
            <td><b>DIPERIKSA</b><br><br><br>FOREMAN</td>
            <td><b>DIKETAHUI</b><br><br><br>SPV</td>
        </tr>
    </table>

    <div class="bottom-line" style="margin-top:3px;"></div>
</div>
`;


    // ================= LEGACY FORMAT E/F/G/N/R + FALLBACK A =================
    // Form B-D/H-Q sudah ditangani di atas oleh renderer teman.
    // Bagian ini mempertahankan renderer lama untuk E/F/G/N/R dan format lain.
    {
                    const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                    let d = new Date(data.tanggal_periksa);
                    let dStr = d.getDate().toString().padStart(2, '0') + ' - ' + (d.getMonth()+1).toString().padStart(2, '0') + ' - ' + d.getFullYear();
                    let dSignText = d.getDate() + ' ' + monthNames[d.getMonth()] + ' ' + d.getFullYear();
            
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
                        var fData = data.hasil_array[0]; 
                        var rowsSec2 = '';
                        fData.sec2.forEach(function(row, idx) {
                            rowsSec2 += `
                            <tr>
                                <td style="border-right: 1px solid #000; border-bottom: 1px dotted #000; vertical-align:middle;">${idx+1}</td>
                                <td colspan="2" style="border-right: 1px solid #000; border-bottom: 1px dotted #000; vertical-align:middle;">${row.standar}</td>
                                <td colspan="2" style="border-right: 1px solid #000; border-bottom: 1px dotted #000; vertical-align:middle;">${row.hasil !== undefined && row.hasil !== '' ? row.hasil : ''}</td>
                                <td style="border-bottom: 1px dotted #000; vertical-align:middle;">${row.koreksi !== undefined && row.koreksi !== '' ? row.koreksi : ''}</td>
                            </tr>`;
                        });
                
                        rowsDataHtml.push(`
                            <table style="width:100%; border-collapse: collapse; text-align:center; table-layout:fixed;">
                                <colgroup><col style="width: 12%;"><col style="width: 17.6%;"><col style="width: 17.6%;"><col style="width: 17.6%;"><col style="width: 17.6%;"><col style="width: 17.6%;"></colgroup>
                                <tr><td colspan="6" style="border-bottom: 1px solid #000; text-align:left; padding-left:10px;">I. KEMAMPUAN ULANG ( REPEATABILITY OF READING )</td></tr>
                                <tr>
                                    <td colspan="2" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">STANDAR<br>( ${data.satuan} )</td>
                                    <td colspan="2" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">HASIL<br>( ${data.satuan} )</td>
                                    <td colspan="2" style="border-bottom: 1px solid #000;">DEVIASI<br>( ${data.satuan} )</td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="border-right: 1px solid #000; border-bottom: 1px solid #000; height:25px;">${fData.sec1.standar !== undefined && fData.sec1.standar !== '' ? fData.sec1.standar : ''}</td>
                                    <td colspan="2" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">${fData.sec1.hasil !== undefined && fData.sec1.hasil !== '' ? fData.sec1.hasil : ''}</td>
                                    <td colspan="2" style="border-bottom: 1px solid #000;">${fData.sec1.deviasi !== undefined && fData.sec1.deviasi !== '' ? fData.sec1.deviasi : ''}</td>
                                </tr>
                                <tr><td colspan="6" style="border-bottom: 1px solid #000; text-align:left; padding-left:10px;">II. KEMAMPUAN NILAI SCALA ( SCALA TEST )</td></tr>
                                <tr>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">NO</td>
                                    <td colspan="2" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">STANDAR<br>( ${data.satuan} )</td>
                                    <td colspan="2" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">HASIL<br>( ${data.satuan} )</td>
                                    <td style="border-bottom: 1px solid #000;">KOREKSI<br>( ${data.satuan} )</td>
                                </tr>
                                ${rowsSec2}
                                <tr><td style="border-right: 1px solid #000; height:15px;"></td><td colspan="2" style="border-right: 1px solid #000;"></td><td colspan="2" style="border-right: 1px solid #000;"></td><td></td></tr>
                                <tr><td colspan="6" style="border-top: 1px solid #000; border-bottom: 1px solid #000; text-align:left; padding-left:10px;">III. EFECT CENTER LOADING</td></tr>
                                <tr>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">TENGAH<br>( ${data.satuan} )</td>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">BELAKANG<br>( ${data.satuan} )</td>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">KANAN<br>( ${data.satuan} )</td>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">DEPAN<br>( ${data.satuan} )</td>
                                    <td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">KIRI<br>( ${data.satuan} )</td>
                                    <td style="border-bottom: 1px solid #000;">PERBEDAAN DARI TENGAH<br>( ${data.satuan} )</td>
                                </tr>
                                <tr>
                                    <td style="border-right: 1px solid #000; border-bottom: none; height:25px;">${fData.sec3.tengah !== undefined && fData.sec3.tengah !== '' ? fData.sec3.tengah : ''}</td>
                                    <td style="border-right: 1px solid #000; border-bottom: none;">${fData.sec3.belakang !== undefined && fData.sec3.belakang !== '' ? fData.sec3.belakang : ''}</td>
                                    <td style="border-right: 1px solid #000; border-bottom: none;">${fData.sec3.kanan !== undefined && fData.sec3.kanan !== '' ? fData.sec3.kanan : ''}</td>
                                    <td style="border-right: 1px solid #000; border-bottom: none;">${fData.sec3.depan !== undefined && fData.sec3.depan !== '' ? fData.sec3.depan : ''}</td>
                                    <td style="border-right: 1px solid #000; border-bottom: none;">${fData.sec3.kiri !== undefined && fData.sec3.kiri !== '' ? fData.sec3.kiri : ''}</td>
                                    <td style="border-bottom: none;">${fData.sec3.perbedaan !== undefined && fData.sec3.perbedaan !== '' ? fData.sec3.perbedaan : ''}</td>
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
                                <td style="border-right: 1px solid #000; border-bottom: 1px dotted #000; vertical-align:middle; text-align:center;">${row.standar} <span style="margin-left:5px;">±${row.toleransi}</span></td>
                                <td style="border-right: 1px solid #000; border-bottom: 1px dotted #000; vertical-align:middle; text-align:center;" class="${clsT1}">${row.t1_hasil || ''}</td>
                                <td style="border-right: 1px solid #000; border-bottom: 1px dotted #000; vertical-align:middle; text-align:center;" class="${clsT1}">${row.t1_koreksi || ''}</td>
                                <td style="border-right: 1px solid #000; border-bottom: 1px dotted #000; vertical-align:middle; text-align:center;">${row.t2_hasil || ''}</td>
                                <td style="border-bottom: 1px dotted #000; vertical-align:middle; text-align:center;">${row.t2_koreksi || ''}</td>
                            </tr>`);
                        });
                    }
                    else if (data.format === 'G') {
                        data.hasil_array.forEach(function(row, idx) {
                            rowsDataHtml.push(`
                            <tr>
                                <td style="border-right: 1px solid #000; border-bottom: 1px dotted #000; vertical-align:middle;">${idx+1}</td>
                                <td style="border-right: none; border-bottom: 1px dotted #000; text-align:right; padding-right:15px; vertical-align:middle;">${row.standar}</td>
                                <td style="border-left: none; border-right: 1px solid #000; border-bottom: 1px dotted #000; text-align:left; padding-left:15px; vertical-align:middle;">±${row.toleransi}</td>
                                <td style="border-right: 1px solid #000; border-bottom: 1px dotted #000; vertical-align:middle;">${row.hasil !== undefined && row.hasil !== '' ? row.hasil : ''}</td>
                                <td style="border-bottom: 1px dotted #000; vertical-align:middle;">${row.hasil_2 !== undefined && row.hasil_2 !== '' ? row.hasil_2 : ''}</td>
                            </tr>`);
                        });
                    }
                    else if(data.format === 'R') {
                        data.hasil_array.forEach(function(row) {
                            var std = parseFloat(row.standar);
                            var tol = parseFloat(row.toleransi);
                            var minRange = (std - Math.abs(tol)).toFixed(3);
                            var maxRange = (std + Math.abs(tol)).toFixed(3);

                            var trHtml = '<tr>';
                            if(hasBagian) {
                                trHtml += `<td style="border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align:middle;">${row.bagian || '-'}</td>`;
                            }
                            trHtml += `
                                <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; line-height:1.6; padding: 5px 0;">
                                    <table style="width:100%; border:none; margin:0; padding:0; table-layout:fixed;">
                                        <tr>
                                            <td style="border:none; text-align:center; width:50%; padding:0;">${row.standar}</td>
                                            <td style="border:none; text-align:center; width:50%; padding:0;">±${row.toleransi}</td>
                                        </tr>
                                    </table>
                                    ( ${minRange} ~ ${maxRange} )
                                </td>
                                <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align:middle;">${row.depan !== undefined && row.depan !== '' ? row.depan : '-'}</td>
                                <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align:middle;">${row.tengah !== undefined && row.tengah !== '' ? row.tengah : '-'}</td>
                                <td style="border-bottom: 1px solid #000; vertical-align:middle;">${row.belakang !== undefined && row.belakang !== '' ? row.belakang : '-'}</td>
                            </tr>`;
                            rowsDataHtml.push(trHtml);
                        });
                    }
                    else {
                        data.hasil_array.forEach(function(row, idx) {
                            var tr = '<tr>';
                            tr += `<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">${idx+1}</td>`;
                            if(hasBagian) { tr += `<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">${row.bagian || '-'}</td>`; }
                            tr += `<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">${row.standar}</td>`;
                            if(hasToleransi) { tr += `<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">± ${row.toleransi || '-'}</td>`; }
                            tr += `<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">${row.hasil}</td>`;
                            tr += `<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">${row.koreksi}</td>`;
                            tr += `<td style="font-weight:bold; border-bottom: 1px solid #000;">${row.status || '-'}</td>`;
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
                                    <td width="20%">NO. LAPORAN</td><td width="30%">-</td>
                                    <td width="20%">SEKSI</td><td width="30%" class="text-bold text-center">${data.seksi_pemilik}</td>
                                </tr>
                                <tr>
                                    <td>${data.format === 'F' ? 'NOMOR ALAT' : 'NAMA ALAT'}</td><td>${data.format === 'F' ? data.no_part : data.nama_part}</td>
                                    <td rowspan="2">PETUGAS ${data.format === 'R' || data.format === 'F' ? 'KALIBRASI' : 'PERIKSA'}</td><td rowspan="2" class="text-center">${data.petugas}</td>
                                </tr>
                                <tr>
                                    <td>${data.format === 'F' ? 'NAMA ALAT' : 'NO. ALAT'}</td><td>${data.format === 'F' ? data.nama_part : data.no_part}</td>
                                </tr>
                                <tr>
                                    <td>SPESIFIKASI</td><td>${data.spesifikasi}</td>
                                    <td ${data.format === 'G' ? 'rowspan="2"' : ''} style="${data.format === 'R' ? 'border-bottom:none;' : ''}">TANGGAL ${data.format === 'R' || data.format === 'F' ? 'KALIBRASI' : 'PERIKSA'}</td><td ${data.format === 'G' ? 'rowspan="2"' : ''} class="text-center" style="${data.format === 'R' ? 'border-bottom:none;' : ''}">${dStr}</td>
                                </tr>
                                <tr>
                                    <td>RESOLUSI</td><td>${data.resolusi || '-'}</td>
                                    ${data.format === 'G' ? '' : (data.format === 'R' ? '<td style="border-top:none;"></td><td style="border-top:none;"></td>' : '<td></td><td></td>')}
                                </tr>`;
                            } else {
                                finalHtml += `
                                <tr>
                                    <td width="15%">NO. LAPORAN</td><td width="35%">-</td>
                                    <td width="15%">SEKSI</td><td width="35%" class="text-bold text-center">${data.seksi_pemilik}</td>
                                </tr>
                                <tr>
                                    <td>NAMA ALAT</td><td>${data.nama_part}</td>
                                    <td rowspan="2">PETUGAS PERIKSA</td><td rowspan="2" class="text-center">${data.petugas}</td>
                                </tr>
                                <tr><td>NO. ALAT</td><td>${data.no_part}</td></tr>
                                <tr>
                                    <td>SPESIFIKASI</td><td>${data.spesifikasi}</td>
                                    <td>TANGGAL PERIKSA</td><td class="text-center">${dStr}</td>
                                </tr>
                                <tr><td>RESOLUSI</td><td colspan="3">${data.resolusi || '-'}</td></tr>`;
                            }
                        } else {
                            finalHtml += `<tr><td width="20%">NO. ALAT</td><td width="30%">${data.no_part}</td><td width="20%">NAMA ALAT</td><td width="30%">${data.nama_part}</td></tr>`;
                        }

                        finalHtml += `<tr><td colspan="4" class="text-center" style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 2px 0;">HASIL ${data.format === 'R' || data.format === 'F' ? 'KALIBRASI' : 'PEMERIKSAAN'} ${pageIndex > 0 ? '( Lanjutan )' : ''}</td></tr>`;
                        finalHtml += `<tr><td colspan="4" style="padding:0; border:none;">`;
                
                        if (data.format === 'F') {
                            finalHtml += dataTabelHasil; 
                        } 
                        else if (data.format === 'E') {
                            finalHtml += `
                                <table style="width:100%; border-collapse: collapse; text-align:center; table-layout:fixed;">
                                    <colgroup><col style="width: 10%;"><col style="width: 30%;"><col style="width: 15%;"><col style="width: 15%;"><col style="width: 15%;"><col style="width: 15%;"></colgroup>
                                    <tr>
                                        <td rowspan="2" style="border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align:middle;">NO</td>
                                        <td rowspan="2" style="border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align:middle;">STANDAR<br>( ${data.satuan} )</td>
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
                                        <td rowspan="2" width="10%" style="border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align:middle;">NO</td>
                                        <td colspan="2" width="40%" style="border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align:middle;">NILAI STANDAR</td>
                                        <td colspan="2" width="50%" style="border-bottom: 1px solid #000; vertical-align:middle;">HASIL</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">${data.satuan}</td>
                                        <td width="25%" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">${data.satuan}</td>
                                        <td width="25%" style="border-bottom: 1px solid #000;">${data.satuan}</td>
                                    </tr>
                                    ${dataTabelHasil}
                                    <tr><td style="border-right: 1px solid #000; height:15px; border-bottom:none;"></td><td style="border-right: none; border-bottom:none;"></td><td style="border-right: 1px solid #000; border-bottom:none;"></td><td style="border-right: 1px solid #000; border-bottom:none;"></td><td style="border-bottom:none;"></td></tr>
                                </table>
                            `;
                        }
                        else if (data.format === 'R') {
                            var colHtml = '';
                            if(hasBagian) { colHtml = '<colgroup><col style="width: 20%;"><col style="width: 20%;"><col style="width: 20%;"><col style="width: 20%;"><col style="width: 20%;"></colgroup>'; } 
                            else { colHtml = '<colgroup><col style="width: 25%;"><col style="width: 25%;"><col style="width: 25%;"><col style="width: 25%;"></colgroup>'; }

                            var headerRow1 = '<tr>';
                            if(hasBagian) headerRow1 += `<td width="20%" style="border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align:middle;">POSISI</td>`;
                            headerRow1 += `<td width="${hasBagian?'20%':'25%'}" style="border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align:middle;">STANDAR</td>`;
                            headerRow1 += `<td colspan="3" style="border-bottom: 1px solid #000;">HASIL</td>`;
                            headerRow1 += '</tr>';

                            var headerRow2 = '<tr>';
                            if(hasBagian) headerRow2 += `<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"></td>`;
                            headerRow2 += `<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;"></td>`;
                            headerRow2 += `<td width="${hasBagian?'20%':'25%'}" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">DEPAN</td>`;
                            headerRow2 += `<td width="${hasBagian?'20%':'25%'}" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">TENGAH</td>`;
                            headerRow2 += `<td width="${hasBagian?'20%':'25%'}" style="border-bottom: 1px solid #000;">BELAKANG</td>`;
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
                        else {
                            var totalCols = 4 + (hasBagian ? 1 : 0) + (hasToleransi ? 1 : 0);
                            var colWidth = (95 / (totalCols - 1)).toFixed(2); 

                            finalHtml += `
                                <table style="width:100%; border-collapse: collapse; text-align:center; table-layout:fixed;">
                                    <tr>
                                        <td style="width: 5%; border-right: 1px solid #000; border-bottom: 1px solid #000;">NO</td>
                                        ${hasBagian ? `<td style="width: ${colWidth}%; border-right: 1px solid #000; border-bottom: 1px solid #000;">BAGIAN</td>` : ''}
                                        <td style="width: ${colWidth}%; border-right: 1px solid #000; border-bottom: 1px solid #000;">STANDAR<br>( ${data.satuan} )</td>
                                        ${hasToleransi ? `<td style="width: ${colWidth}%; border-right: 1px solid #000; border-bottom: 1px solid #000;">TOLERANSI (±)</td>` : ''}
                                        <td style="width: ${colWidth}%; border-right: 1px solid #000; border-bottom: 1px solid #000;">HASIL<br>( ${data.satuan} )</td>
                                        <td style="width: ${colWidth}%; border-right: 1px solid #000; border-bottom: 1px solid #000;">KOREKSI<br>( ${data.satuan} )</td>
                                        <td style="width: ${colWidth}%; border-bottom: 1px solid #000;">STATUS</td>
                                    </tr>
                                    ${dataTabelHasil}
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
                                    <td width="20%" style="border-top: 1px solid #000;">METODE</td><td width="30%" style="border-top: 1px solid #000;">${data.metode}</td>
                                    <td width="20%" style="border-top: 1px solid #000;">CONFIDENCE LEVEL</td><td width="30%" style="border-top: 1px solid #000;" class="text-center ${crossCL}">${data.confidence_level ? data.confidence_level : ''}</td>
                                </tr>
                                <tr>
                                    <td rowspan="2" style="vertical-align:middle;">STANDAR UJI</td><td rowspan="2" style="vertical-align:middle;">${(data.standar_uji||'').replace(/\n/g, "<br>")}</td>
                                    <td style="vertical-align:middle;">KETIDAKPASTIAN</td><td class="text-center ${crossKetidakpastian}" style="vertical-align:middle;">${data.ketidakpastian ? '± '+data.ketidakpastian : ''}</td>
                                </tr>
                                <tr>
                                    <td style="vertical-align:middle;">STANDAR TOLERANSI</td><td class="text-center" style="vertical-align:middle;">${(data.toleransi_global||'').replace(/\n/g, "<br>")}</td>
                                </tr>
                                <tr>
                                    <td>TRACEABILITY</td><td>${data.traceability}</td>
                                    <td>KEPUTUSAN</td><td class="text-center text-bold" style="font-size: 16px;">${data.status_final}</td>
                                </tr>
                                <tr>
                                    <td>SUHU RUANGAN</td><td class="text-center ${crossSuhu}">${data.suhu ? data.suhu : ''}</td>
                                    <td rowspan="2" style="vertical-align:middle;">PERIKSA BERIKUTNYA</td><td rowspan="2" class="text-center" style="vertical-align:middle;">${dbStr}</td>
                                </tr>
                                <tr>
                                    <td>KELEMBABAN RUANGAN</td><td class="text-center ${crossKelembapan}">${data.kelembapan ? data.kelembapan : ''}</td>
                                </tr>
                                `;
                            } else {
                                footerHtml += `
                                <tr>
                                    <td width="20%" style="border-top: 1px solid #000;">METODE</td><td width="30%" style="border-top: 1px solid #000;">${data.metode}</td>
                                    <td width="20%" style="border-top: 1px solid #000;">KELEMBABAN RUANGAN</td><td width="30%" style="border-top: 1px solid #000;" class="text-center ${crossKelembapan}">${data.kelembapan ? data.kelembapan : ''}</td>
                                </tr>
                                <tr>
                                    <td rowspan="2" style="vertical-align:middle;">STANDAR UJI</td><td rowspan="2" style="vertical-align:middle;">${(data.standar_uji||'').replace(/\n/g, "<br>")}</td>
                                    <td rowspan="2" style="vertical-align:middle;">STANDAR TOLERANSI</td><td rowspan="2" class="text-center" style="vertical-align:middle;">${(data.toleransi_global||'').replace(/\n/g, "<br>")}</td>
                                </tr>
                                <tr></tr>
                                <tr>
                                    <td>TRACEABILITY</td><td>${data.traceability}</td>
                                    <td>KEPUTUSAN</td><td class="text-center text-bold" style="font-size: 16px;">${data.status_final}</td>
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
                                <td colspan="4" style="vertical-align:top; border-top: 1px solid #000; border-bottom: 1px solid #000;">
                                    KETERANGAN : <br>
                                    <span style="white-space: pre-wrap; font-style:italic; word-wrap:break-word; word-break:break-word;">${data.keterangan || ''}</span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="4" style="border:none; padding-top: 10px;">
                                    <div style="width: 50%; float: right; text-align: center;">
                                        <div style="text-align:left; margin-bottom: 5px; font-weight:bold;">TANGERANG , ${['R','F','E'].includes(data.format) ? dSignText : dStr}</div>
                                        <table class="table-excel" style="width: 100%;">
                                            <tr><td width="33%">DIBUAT</td><td width="33%">DIPERIKSA</td><td width="33%">DISETUJUI</td></tr>
                                            <tr>
                                                <td style="height: 55px; border-bottom: none;"></td>
                                                <td style="height: 55px; border-bottom: none;"></td>
                                                <td style="height: 55px; border-bottom: none;"></td>
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
                        if(isLastPage) { finalHtml += `<div style="text-align: right; font-size: 11px; margin-top: 2px; font-weight: bold;">STIS-CMFG4-03/R4</div>`; }
                        finalHtml += `</div>`; 
                    }

                    document.getElementById('report').innerHTML = finalHtml;
    }

    // Jangan langsung membuka dialog print agar user bisa mengecek tampilan dulu.
})();
</script>
</body>
</html>

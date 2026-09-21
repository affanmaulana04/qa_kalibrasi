<!DOCTYPE html>
<html>
<head>
    <title>Laporan Pemeriksaan Alat</title>
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
</head>
<body>

    <div class="no-print">
        <a href="cek_alat.php" style="color:#fff; font-weight:bold; text-decoration:none;">&larr; Kembali ke Pengecekan</a><br>
        <button class="btn-action" onclick="window.print()"><i class="fa fa-print"></i> Cetak Laporan</button>
    </div>

    <div id="print-container"></div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var dataStr = sessionStorage.getItem('cetak_data');
            if(!dataStr) { alert("Data tidak ditemukan! Silakan update kalibrasi melalui halaman Cek Alat terlebih dahulu."); return; }
            var data = JSON.parse(dataStr);

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

            var hasBagian = data.hasil_array.some(r => r.bagian && r.bagian.replace(/[- ]/g, '') !== '');
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

                // LOGIKA FOOTER UNIFIED (MENGHILANGKAN U & CL UNTUK G,N,R,E)
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

            document.getElementById('print-container').innerHTML = finalHtml;
        });
    </script>
</body>
</html>
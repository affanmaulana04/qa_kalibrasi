<!DOCTYPE html>
<html>
<head>
    <title>Cetak BBRAU</title>
    <link rel="icon" type="image/png" href="../../images/LogoTOTO.png?v=<?= time(); ?>"/>
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 13px; color: #000; margin: 0; padding: 20px; background: #525659; }
        
        .no-print { text-align: center; margin-bottom: 20px; font-family: Arial, sans-serif;}
        .btn-action { display: inline-block; width: 200px; padding: 12px; background: #fff; color: #333; text-align: center; text-decoration: none; font-weight: bold; border-radius: 5px; cursor: pointer; border:none; margin-top:10px; }
        .btn-action:hover { background: #ddd; }

        .a4-page { width: 210mm; min-height: 297mm; background: #fff; margin: 0 auto 20px auto; padding: 10mm 15mm; box-shadow: 0 4px 10px rgba(0,0,0,0.5); position: relative; page-break-after: always; }
        .a4-page:last-child { page-break-after: auto; margin-bottom: 0; }
        
        .table-bbrau { width: 100%; border-collapse: collapse; border: 2px solid #000; table-layout: fixed; }
        .table-bbrau th, .table-bbrau td { border: 1px solid #000; padding: 5px; page-break-inside: avoid; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; } 
        
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        
        .header-pt { font-size: 13px; font-weight: bold; text-align: center; margin-bottom: 5px;}
        .title-bbrau { font-size: 18px; font-weight: normal; text-align: center; margin: 0 0 10px 0; border-bottom: 1px solid #000; padding-bottom: 5px;}
        
        .vertical-text { writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; letter-spacing: 5px; font-weight: normal; font-style: italic; }

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
        <a href="masterdata.php" style="color:#fff; font-weight:bold; text-decoration:none;">&larr; Kembali ke Master Database</a><br>
        <button class="btn-action" onclick="window.print()">Cetak BBRAU</button>
    </div>

    <div id="print-container"></div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var dataStr = sessionStorage.getItem('cetak_bbrau');
            if(!dataStr) { alert("Data BBRAU tidak ditemukan!"); return; }
            var res = JSON.parse(dataStr);
            var alat = res.alat;
            var history = res.history;

            function formatTanggalReversed(tglStr) {
                if(!tglStr || tglStr === '0000-00-00' || tglStr === 'null') return '-';
                var p = tglStr.split('-');
                if(p.length !== 3) return tglStr;
                return p[2] + ' - ' + p[1] + ' - ' + p[0];
            }

            // Ambil data registrasi (History pertama kali yg statusnya ~ Registrasi)
            var tglReg = '-';
            var seksiReg = '-';
            var ketReg = '-';
            if(history.length > 0) {
                tglReg = formatTanggalReversed(history[0].tanggal_pengecekan);
                seksiReg = alat.seksi_pemilik;
                ketReg = history[0].keterangan || '~ Registrasi';
            }

            var trHistKalibrasi = '';
            var countKalibrasi = 1;
            // Looping dari history ke-2 (karena yg ke-1 itu registrasi)
            for(var i = 1; i < history.length; i++) {
                var h = history[i];
                var statusTxt = (h.status_kalibrasi === 'Good' || h.status_kalibrasi === 'OK') ? 'OK' : 'BURUK';
                trHistKalibrasi += `
                <tr>
                    <td class="text-center" style="border-bottom: 1px dotted #000;">${countKalibrasi}</td>
                    <td class="text-center" style="border-bottom: 1px dotted #000;">${formatTanggalReversed(h.tanggal_pengecekan)}</td>
                    <td class="text-center" style="border-bottom: 1px dotted #000;">Kalibrasi</td>
                    <td class="text-center" style="border-bottom: 1px dotted #000;">${statusTxt}</td>
                    <td class="text-center" style="border-bottom: 1px dotted #000;">${h.created_by}</td>
                    <td class="text-center" style="border-bottom: 1px dotted #000;">-</td>
                    <td class="text-center" style="border-bottom: 1px dotted #000;">INTERNAL</td>
                    <td class="text-center" style="border-bottom: 1px dotted #000;">STI</td>
                </tr>`;
                countKalibrasi++;
            }

            // Tambah baris kosong titik-titik biar full (misal target 25 baris)
            var sisaBaris = 25 - (countKalibrasi - 1);
            if (sisaBaris < 0) sisaBaris = 0;
            for(var j=0; j<sisaBaris; j++) {
                trHistKalibrasi += `
                <tr>
                    <td style="border-bottom: 1px dotted #000; height: 18px;"></td>
                    <td style="border-bottom: 1px dotted #000;"></td>
                    <td style="border-bottom: 1px dotted #000;"></td>
                    <td style="border-bottom: 1px dotted #000;"></td>
                    <td style="border-bottom: 1px dotted #000;"></td>
                    <td style="border-bottom: 1px dotted #000;"></td>
                    <td style="border-bottom: 1px dotted #000;"></td>
                    <td style="border-bottom: 1px dotted #000;"></td>
                </tr>`;
            }

            var html = `
            <div class="a4-page">
                <div class="header-pt">PT. SURYA TOTO INDONESIA Tbk.</div>
                
                <table class="table-bbrau">
                    <tr><td colspan="4" style="border:none; padding:10px 0;"><div class="title-bbrau">BUKU BESAR RIWAYAT ALAT UKUR</div></td></tr>
                    
                    <colgroup>
                        <col style="width: 15%;">
                        <col style="width: 40%;">
                        <col style="width: 15%;">
                        <col style="width: 30%;">
                    </colgroup>
                    
                    <tr>
                        <td rowspan="2" style="vertical-align:top; border-top:2px solid #000;">NAMA</td>
                        <td rowspan="2" class="text-center" style="font-size:18px; font-weight:bold; vertical-align:middle; border-top:2px solid #000;">${alat.nama_part.toUpperCase()}</td>
                        <td style="border-top:2px solid #000;">SEKSI</td>
                        <td class="text-center text-bold" style="font-size:14px; border-top:2px solid #000;">${alat.seksi_pemilik.toUpperCase()}</td>
                    </tr>
                    <tr>
                        <td>NO.ALAT</td>
                        <td class="text-center text-bold" style="font-size:16px;">${alat.no_part}</td>
                    </tr>
                    <tr>
                        <td>SPESIFIKASI</td>
                        <td>${alat.spesifikasi}</td>
                        <td>MERK</td>
                        <td class="text-center text-bold" style="font-size:14px;">-</td>
                    </tr>
                    <tr>
                        <td>FUNGSI</td>
                        <td>-</td>
                        <td>BUATAN</td>
                        <td class="text-center text-bold" style="font-size:14px;">-</td>
                    </tr>
                    <tr>
                        <td>NO ASSET</td>
                        <td>~</td>
                        <td>DIBELI TGL</td>
                        <td class="text-center text-bold" style="font-size:14px;">-</td>
                    </tr>
                    <tr>
                        <td style="border-bottom:2px solid #000;">STATUS</td>
                        <td style="border-bottom:2px solid #000;">Sudah Kalibrasi</td>
                        <td style="border-bottom:2px solid #000;">TYPE</td>
                        <td class="text-center text-bold" style="font-size:14px; border-bottom:2px solid #000;">${alat.type || '-'}</td>
                    </tr>

                    <tr>
                        <td colspan="4" style="padding:0; border:none;">
                            <table style="width:100%; border-collapse: collapse; border:none; table-layout:fixed;">
                                <colgroup>
                                    <col style="width: 5%;">
                                    <col style="width: 15%;">
                                    <col style="width: 25%;">
                                    <col style="width: 55%;">
                                </colgroup>
                                <tr>
                                    <td rowspan="7" class="vertical-text" style="border-right:1px solid #000; border-bottom:2px solid #000; border-left:none; border-top:none;">R I W A Y A T</td>
                                    <td class="text-center" style="border-bottom: 1px solid #000;">TGL</td>
                                    <td class="text-center" style="border-bottom: 1px solid #000; border-left:1px solid #000;">SEKSI</td>
                                    <td class="text-center" style="border-bottom: 1px solid #000; border-left:1px solid #000; border-right:none;">KETERANGAN</td>
                                </tr>
                                <tr>
                                    <td class="text-center" style="border-bottom: 1px dotted #000; border-left:none;">${tglReg}</td>
                                    <td class="text-center" style="border-bottom: 1px dotted #000; border-left:1px solid #000;">${seksiReg}</td>
                                    <td class="text-center" style="border-bottom: 1px dotted #000; border-left:1px solid #000; border-right:none;">${ketReg}</td>
                                </tr>
                                <tr><td style="border-bottom: 1px dotted #000; height:18px; border-left:none;"></td><td style="border-bottom: 1px dotted #000; border-left:1px solid #000;"></td><td style="border-bottom: 1px dotted #000; border-left:1px solid #000; border-right:none;"></td></tr>
                                <tr><td style="border-bottom: 1px dotted #000; height:18px; border-left:none;"></td><td style="border-bottom: 1px dotted #000; border-left:1px solid #000;"></td><td style="border-bottom: 1px dotted #000; border-left:1px solid #000; border-right:none;"></td></tr>
                                <tr><td style="border-bottom: 1px dotted #000; height:18px; border-left:none;"></td><td style="border-bottom: 1px dotted #000; border-left:1px solid #000;"></td><td style="border-bottom: 1px dotted #000; border-left:1px solid #000; border-right:none;"></td></tr>
                                <tr><td style="border-bottom: 1px dotted #000; height:18px; border-left:none;"></td><td style="border-bottom: 1px dotted #000; border-left:1px solid #000;"></td><td style="border-bottom: 1px dotted #000; border-left:1px solid #000; border-right:none;"></td></tr>
                                <tr><td style="border-bottom: 2px solid #000; height:18px; border-left:none;"></td><td style="border-bottom: 2px solid #000; border-left:1px solid #000;"></td><td style="border-bottom: 2px solid #000; border-left:1px solid #000; border-right:none;"></td></tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="4" style="padding:0; border:none;">
                            <table style="width:100%; border-collapse: collapse; border:none; table-layout:fixed;">
                                <colgroup>
                                    <col style="width: 5%;">
                                    <col style="width: 15%;">
                                    <col style="width: 15%;">
                                    <col style="width: 10%;">
                                    <col style="width: 15%;">
                                    <col style="width: 15%;">
                                    <col style="width: 15%;">
                                    <col style="width: 10%;">
                                </colgroup>
                                <tr>
                                    <td class="text-center" style="border-bottom: 1px solid #000; border-left:none; border-top:none;">NO</td>
                                    <td class="text-center" style="border-bottom: 1px solid #000; border-top:none;">TGL</td>
                                    <td class="text-center" style="border-bottom: 1px solid #000; border-top:none;">JENIS CHECK</td>
                                    <td class="text-center" style="border-bottom: 1px solid #000; border-top:none;">HASIL</td>
                                    <td class="text-center" style="border-bottom: 1px solid #000; border-top:none;">PETUGAS</td>
                                    <td class="text-center" style="border-bottom: 1px solid #000; border-top:none;">DIPERIKSA</td>
                                    <td class="text-center" style="border-bottom: 1px solid #000; border-top:none;">PELAKSANAAN</td>
                                    <td class="text-center" style="border-bottom: 1px solid #000; border-right:none; border-top:none;">KETERANGAN</td>
                                </tr>
                                ${trHistKalibrasi}
                                <tr>
                                    <td style="border-bottom:none; border-left:none; height:18px;"></td><td style="border-bottom:none;"></td><td style="border-bottom:none;"></td><td style="border-bottom:none;"></td><td style="border-bottom:none;"></td><td style="border-bottom:none;"></td><td style="border-bottom:none;"></td><td style="border-bottom:none; border-right:none;"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <div style="text-align: right; font-size: 11px; margin-top: 5px; font-weight: bold;">STIS-CMFG4-01/R4</div>
            </div>
            `;
            
            document.getElementById('print-container').innerHTML = html;
        });
    </script>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Pemeriksaan Alat</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #000; margin: 0; padding: 20px; background: #e0e0e0;}
        .a4-page { width: 210mm; min-height: 297mm; background: #fff; margin: 0 auto; padding: 15mm; box-sizing: border-box; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        
        .table-excel { width: 100%; border-collapse: collapse; border: 1px solid #000; }
        .table-excel th, .table-excel td { border: 1px solid #000; padding: 6px; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        
        .header-title { font-size: 24px; font-weight: normal; text-align: center; margin: 15px 0 20px 0; letter-spacing: 1px;}
        .pt-title { font-weight: bold; text-align: center; font-size: 14px; margin-top:-5px;}
        
        .diagonal-strike { position: relative; overflow: hidden; }
        .diagonal-strike::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to top right, transparent 49%, #000 50%, transparent 51%);
        }

        .btn-action { display: block; width: 200px; padding: 12px; margin: 20px auto; background: #1E3F66; color: white; text-align: center; text-decoration: none; font-weight: bold; border-radius: 5px; cursor: pointer;}
        
        @media print {
            body { background: #fff; padding: 0; margin: 0; }
            .a4-page { box-shadow: none; margin: 0; padding: 10mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print text-center">
        <a href="cek_alat.php" style="color:#1E3F66; font-weight:bold; text-decoration:none;">&larr; Kembali ke Pengecekan</a>
        <button class="btn-action" onclick="window.print()">Cetak Laporan</button>
    </div>

    <div class="a4-page">
        <div class="pt-title">PT. SURYA TOTO INDONESIA Tbk.</div>
        
        <table class="table-excel">
            <tr>
                <td colspan="4" style="border-bottom: 1px solid #000;">
                    <div class="header-title">LAPORAN PEMERIKSAAN</div>
                </td>
            </tr>
            
            <tr>
                <td width="15%">NO. LAPORAN</td>
                <td width="35%">-</td>
                <td width="15%">SEKSI</td>
                <td width="35%" class="text-bold text-center" id="lbl_seksi"></td>
            </tr>
            <tr>
                <td>NAMA ALAT</td>
                <td id="lbl_nama"></td>
                <td rowspan="2">PETUGAS PERIKSA</td>
                <td rowspan="2" class="text-center" id="lbl_petugas"></td>
            </tr>
            <tr>
                <td>NO. ALAT</td>
                <td id="lbl_no_alat"></td>
            </tr>
            <tr>
                <td>SPESIFIKASI</td>
                <td id="lbl_spek"></td>
                <td>TANGGAL PERIKSA</td>
                <td class="text-center" id="lbl_tgl_periksa"></td>
            </tr>
            <tr>
                <td>RESOLUSI</td>
                <td colspan="3" id="lbl_res"></td>
            </tr>

            <!-- TABEL HASIL UJI DINAMIS (KOLOM OTOMATIS DISESUAIKAN) -->
            <tr>
                <td colspan="4" class="text-center" style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 10px 0;">HASIL PEMERIKSAAN</td>
            </tr>
            <tr>
                <td colspan="4" style="padding:0; border:none;">
                    <table style="width:100%; border-collapse: collapse; text-align:center;">
                        <tbody id="tbl_hasil">
                            <!-- Diisi Otomatis Lewat JS -->
                        </tbody>
                    </table>
                </td>
            </tr>

            <!-- PARAMETER KONDISI UJI & KEPUTUSAN -->
            <tr>
                <td style="border-top: 1px solid #000;">METODE</td>
                <td style="border-top: 1px solid #000;" id="lbl_metode"></td>
                <td style="border-top: 1px solid #000;">KELEMBABAN RUANGAN</td>
                <td style="border-top: 1px solid #000;" class="text-center" id="lbl_kelembapan"></td>
            </tr>
            <tr>
                <!-- ROWSPAN DIHILANGKAN, DIBAGI JADI 2 BARIS -->
                <td>STANDAR UJI</td>
                <td id="lbl_std_uji"></td>
                <td>STANDAR TOLERANSI</td>
                <td class="text-center" id="lbl_tol_global"></td>
            </tr>
            <tr>
                <!-- KOLOM CONFIDENCE LEVEL BARU -->
                <td>CONFIDENCE LEVEL</td>
                <td id="lbl_confidence"></td>
                <!-- HURUF (U) DIHAPUS -->
                <td>KETIDAKPASTIAN</td>
                <td class="text-center" id="lbl_ketidakpastian"></td>
            </tr>
            <tr>
                <td>TRACEABILITY</td>
                <td id="lbl_trace"></td>
                <td>KEPUTUSAN</td>
                <td class="text-center text-bold" style="font-size: 16px;" id="lbl_keputusan"></td>
            </tr>
            <tr>
                <td>SUHU RUANGAN</td>
                <td class="text-center" id="lbl_suhu"></td>
                <td>PERIKSA BERIKUTNYA</td>
                <td class="text-center" id="lbl_tgl_berikutnya"></td>
            </tr>
            
            <tr>
                <td colspan="4" style="height: 60px; vertical-align:top; border-top: 1px solid #000; border-bottom: 1px solid #000;">
                    KETERANGAN : <br>
                    <span id="lbl_keterangan" style="white-space: pre-wrap; font-style:italic;"></span>
                </td>
            </tr>

            <!-- AREA TANDA TANGAN -->
            <tr>
                <td colspan="4" style="border:none; padding-top: 20px;">
                    <div style="width: 50%; float: right; text-align: center;">
                        <div style="text-align:left; margin-bottom: 5px;">TANGERANG , <span id="lbl_tgl_ttd"></span></div>
                        <table class="table-excel" style="width: 100%;">
                            <tr>
                                <td width="33%">DIBUAT</td>
                                <td width="33%">DIPERIKSA</td>
                                <td width="33%">DISETUJUI</td>
                            </tr>
                            <tr>
                                <td style="height: 70px; vertical-align:bottom; border-bottom: none;"></td>
                                <td style="height: 70px; vertical-align:bottom; border-bottom: none;"></td>
                                <td style="height: 70px; vertical-align:bottom; border-bottom: none;"></td>
                            </tr>
                            <tr>
                                <td style="border-top: none;">PETUGAS</td>
                                <td style="border-top: none;">FOREMAN</td>
                                <td style="border-top: none;">SUPERVISOR</td>
                            </tr>
                        </table>
                    </div>
                    <div style="clear:both;"></div>
                </td>
            </tr>
        </table>
        
        <div style="text-align: right; font-size: 11px; margin-top: 5px; font-weight: bold;">
            STIS-CMFG4-03/R4
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var dataStr = sessionStorage.getItem('cetak_data');
            if(!dataStr) { alert("Data tidak ditemukan! Silakan update kalibrasi melalui halaman Cek Alat terlebih dahulu."); return; }
            var data = JSON.parse(dataStr);

            document.getElementById('lbl_seksi').innerText = data.seksi_pemilik;
            document.getElementById('lbl_nama').innerText = data.nama_part;
            document.getElementById('lbl_petugas').innerText = data.petugas;
            document.getElementById('lbl_no_alat').innerText = data.no_part;
            document.getElementById('lbl_spek').innerText = data.spesifikasi;
            document.getElementById('lbl_res').innerText = data.resolusi || '-';
            document.getElementById('lbl_metode').innerText = data.metode;
            document.getElementById('lbl_std_uji').innerHTML = data.standar_uji.replace(/\n/g, "<br>");
            document.getElementById('lbl_tol_global').innerText = data.toleransi_global;
            document.getElementById('lbl_trace').innerText = data.traceability;
            document.getElementById('lbl_keputusan').innerText = data.status_final;
            document.getElementById('lbl_keterangan').innerText = data.keterangan || '-';

            // KONDISI CORET (DIAGONAL STRIKE) KALAU KOSONG
            if(!data.suhu) document.getElementById('lbl_suhu').classList.add('diagonal-strike'); 
            else document.getElementById('lbl_suhu').innerText = data.suhu + ' °C';
            
            if(!data.kelembapan) document.getElementById('lbl_kelembapan').classList.add('diagonal-strike'); 
            else document.getElementById('lbl_kelembapan').innerText = data.kelembapan + ' %';
            
            if(!data.ketidakpastian) document.getElementById('lbl_ketidakpastian').classList.add('diagonal-strike'); 
            else document.getElementById('lbl_ketidakpastian').innerText = '± ' + data.ketidakpastian;

            if(!data.confidence_level) document.getElementById('lbl_confidence').classList.add('diagonal-strike'); 
            else document.getElementById('lbl_confidence').innerText = data.confidence_level + ' %';

            // PARSING TANGGAL
            const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
            let d = new Date(data.tanggal_periksa);
            let dStr = d.getDate().toString().padStart(2, '0') + ' - ' + (d.getMonth()+1).toString().padStart(2, '0') + ' - ' + d.getFullYear();
            let dSign = d.getDate() + ' ' + monthNames[d.getMonth()] + ' ' + d.getFullYear();
            let dbStr = data.jadwal_berikutnya ? formatTanggalReversed(data.jadwal_berikutnya) : '-';

            document.getElementById('lbl_tgl_periksa').innerText = dStr;
            document.getElementById('lbl_tgl_berikutnya').innerText = dbStr;
            document.getElementById('lbl_tgl_ttd').innerText = dSign;

            function formatTanggalReversed(tglStr) {
                var p = tglStr.split('-');
                if(p.length !== 3) return tglStr;
                return p[2] + ' - ' + p[1] + ' - ' + p[0];
            }

            // AUTO-HIDE KOLOM TABEL BERDASARKAN ISINYA DAN PENAMBAHAN STATUS
            var tb = document.getElementById('tbl_hasil');
            var html = '';
            
            var hasBagian = data.hasil_array.some(r => r.bagian && r.bagian.trim() !== '' && r.bagian.trim() !== '-');
            var hasToleransi = data.hasil_array.some(r => r.toleransi !== undefined && r.toleransi !== null && r.toleransi !== '' && r.toleransi !== 0 && r.toleransi !== '-');

            html += '<tr>';
            html += '<td width="5%" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">NO</td>';
            if(hasBagian) { html += '<td width="15%" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">BAGIAN</td>'; }
            
            html += '<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">STANDAR<br>( ' + data.satuan + ' )</td>';
            
            if(hasToleransi) { html += '<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">TOLERANSI (±)</td>'; }
            
            html += '<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">HASIL<br>( ' + data.satuan + ' )</td>';
            html += '<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">KOREKSI<br>( ' + data.satuan + ' )</td>';
            html += '<td width="12%" style="border-bottom: 1px solid #000;">STATUS</td>';
            html += '</tr>';

            data.hasil_array.forEach(function(row, idx) {
                html += '<tr>';
                html += '<td style="border-right: 1px solid #000;">' + (idx+1) + '</td>';
                if(hasBagian) { html += '<td style="border-right: 1px solid #000;">' + (row.bagian || '-') + '</td>'; }
                
                html += '<td style="border-right: 1px solid #000;">' + row.standar + '</td>';
                
                if(hasToleransi) { html += '<td style="border-right: 1px solid #000;">± ' + (row.toleransi || '-') + '</td>'; }
                
                html += '<td style="border-right: 1px solid #000;">' + row.hasil + '</td>';
                html += '<td style="border-right: 1px solid #000;">' + row.koreksi + '</td>';
                html += '<td style="font-weight:bold;">' + (row.status || '-') + '</td>';
                html += '</tr>';
            });
            
            tb.innerHTML = html;
        });
    </script>
</body>
</html>
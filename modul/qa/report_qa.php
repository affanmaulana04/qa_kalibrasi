<!DOCTYPE html>
<html>
<head>
    <title>Laporan Kalibrasi Alat</title>
    <link rel="icon" type="image/png" href="../../images/LogoTOTO.png?v=<?= time(); ?>"/>
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
        .diagonal-strike::after { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(to top right, transparent 49%, #000 50%, transparent 51%); }
        .btn-action { display: block; width: 200px; padding: 12px; margin: 20px auto; background: #1E3F66; color: white; text-align: center; text-decoration: none; font-weight: bold; border-radius: 5px; cursor: pointer;}
        @media print { body { background: #fff; padding: 0; margin: 0; } .a4-page { box-shadow: none; margin: 0; padding: 10mm; } .no-print { display: none; } }
    </style>
</head>
<body>

    <div class="no-print text-center">
        <a href="cek_alat.php" style="color:#1E3F66; font-weight:bold; text-decoration:none;">&larr; Kembali ke Pengecekan</a>
        <button class="btn-action" onclick="window.print()">Cetak Laporan</button>
    </div>

    <!-- AREA KERTAS -->
    <div class="a4-page" id="area-kertas">
        <!-- Akan di render otomatis lewat Javascript berdasarkan Format nya -->
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var dataStr = sessionStorage.getItem('cetak_data');
            if(!dataStr) { alert("Data tidak ditemukan! Silakan update kalibrasi melalui halaman Cek Alat terlebih dahulu."); return; }
            var data = JSON.parse(dataStr);

            const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
            let d = new Date(data.tanggal_periksa);
            let dStr = d.getDate().toString().padStart(2, '0') + ' - ' + (d.getMonth()+1).toString().padStart(2, '0') + ' - ' + d.getFullYear();
            let dSign = d.getDate() + ' - ' + (d.getMonth()+1).toString().padStart(2, '0') + ' - ' + d.getFullYear(); // Khusus R pakai angka
            
            function formatTanggalReversed(tglStr) {
                var p = tglStr.split('-');
                if(p.length !== 3) return tglStr;
                return p[2] + ' - ' + p[1] + ' - ' + p[0];
            }
            let dbStr = data.jadwal_berikutnya ? formatTanggalReversed(data.jadwal_berikutnya) : '-';

            // HTML RENDERER
            var html = '';
            
            // ==============================================================================
            // FORMAT R (LAYOUT KHUSUS SESUAI GAMBAR)
            // ==============================================================================
            if(data.format === 'R') {
                html += `
                <div class="pt-title">PT. SURYA TOTO INDONESIA Tbk.</div>
                <table class="table-excel">
                    <tr><td colspan="4" style="border-bottom: 1px solid #000;"><div class="header-title">LAPORAN KALIBRASI</div></td></tr>
                    
                    <tr>
                        <td width="20%">NO. LAPORAN</td><td width="30%">-</td>
                        <td width="20%">SEKSI</td><td width="30%" class="text-bold text-center">${data.seksi_pemilik}</td>
                    </tr>
                    <tr>
                        <td>NAMA ALAT</td><td>${data.nama_part}</td>
                        <td>PETUGAS KALIBRASI</td><td class="text-center">${data.petugas}</td>
                    </tr>
                    <tr>
                        <td>NOMOR. ALAT</td><td>${data.no_part}</td>
                        <td>TANGGAL KALIBRASI</td><td class="text-center">${dStr}</td>
                    </tr>
                    <tr>
                        <td>SPESIFIKASI</td><td>${data.spesifikasi}</td>
                        <td style="border-bottom:none;"></td><td style="border-bottom:none;"></td>
                    </tr>
                    <tr>
                        <td>RESOLUSI</td><td>${data.resolusi || '-'}</td>
                        <td style="border-top:none;"></td><td style="border-top:none;"></td>
                    </tr>

                    <tr><td colspan="4" class="text-center" style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 5px 0;">HASIL KALIBRASI</td></tr>
                    <tr>
                        <td colspan="4" style="padding:0; border:none;">
                            <table style="width:100%; border-collapse: collapse; text-align:center;">
                                <tr>
                                    <td rowspan="2" width="20%" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">POSISI</td>
                                    <td rowspan="2" width="20%" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">STANDAR</td>
                                    <td colspan="3" style="border-bottom: 1px solid #000;">HASIL</td>
                                </tr>
                                <tr>
                                    <td width="20%" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">DEPAN</td>
                                    <td width="20%" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">TENGAH</td>
                                    <td width="20%" style="border-bottom: 1px solid #000;">BELAKANG</td>
                                </tr>
                                <tr><td colspan="5" style="height:40px; border-bottom:1px solid #000;"></td></tr>
                `;

                // Looping Data R
                data.hasil_array.forEach(function(row) {
                    var std = parseFloat(row.standar);
                    var tol = parseFloat(row.toleransi);
                    var minRange = (std - tol).toFixed(3);
                    var maxRange = (std + tol).toFixed(3);

                    html += `
                                <tr>
                                    <td style="border-right: 1px solid #000;">${row.bagian || '-'}</td>
                                    <td style="border-right: 1px solid #000; line-height:1.6; padding: 10px 0;">
                                        <span style="font-size:14px;">${std}</span> <span style="margin-left:10px;">Â±${tol}</span><br>
                                        ${minRange} ~ ${maxRange}
                                    </td>
                                    <td style="border-right: 1px solid #000;">${!isNaN(row.depan) ? row.depan : '-'}</td>
                                    <td style="border-right: 1px solid #000;">${!isNaN(row.tengah) ? row.tengah : '-'}</td>
                                    <td>${!isNaN(row.belakang) ? row.belakang : '-'}</td>
                                </tr>
                    `;
                });

                // Footer Bawah Format R
                let crossKelembapan = !data.kelembapan ? 'diagonal-strike' : '';
                html += `
                                <tr><td colspan="5" style="height:30px;"></td></tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-top: 1px solid #000;">METODE</td><td style="border-top: 1px solid #000;">${data.metode}</td>
                        <td style="border-top: 1px solid #000;">KELEMBABAN RUANGAN</td><td style="border-top: 1px solid #000;" class="text-center ${crossKelembapan}">${data.kelembapan ? data.kelembapan+' %' : ''}</td>
                    </tr>
                    <tr>
                        <td>STANDAR UJI</td><td>${data.standar_uji.replace(/\n/g, "<br>")}</td>
                        <td rowspan="2" style="vertical-align:middle;">KEPUTUSAN</td><td rowspan="2" class="text-center text-bold" style="font-size: 16px; vertical-align:middle;">${data.status_final}</td>
                    </tr>
                    <tr>
                        <td>TRACEABILITY</td><td>${data.traceability}</td>
                    </tr>
                    <tr>
                        <td>SUHU RUANGAN</td><td class="text-center ${!data.suhu ? 'diagonal-strike' : ''}">${data.suhu ? data.suhu+' Â°C' : ''}</td>
                        <td>TGL KALIBRASI BERIKUTNYA</td><td class="text-center">${dbStr}</td>
                    </tr>
                    <tr>
                        <td colspan="4" style="height: 60px; vertical-align:top; border-top: 1px solid #000; border-bottom: 1px solid #000;">
                            KETERANGAN : <br>
                            <span style="white-space: pre-wrap; font-style:italic;">${data.keterangan || '-'}</span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" style="border:none; padding-top: 20px;">
                            <div style="width: 60%; float: right; text-align: center;">
                                <div style="text-align:left; margin-bottom: 5px; font-weight:bold;">TANGERANG , ${dSign}</div>
                                <table class="table-excel" style="width: 100%;">
                                    <tr><td width="33%">DIBUAT</td><td width="33%">DIPERIKSA</td><td width="33%">DISETUJUI</td></tr>
                                    <tr>
                                        <td style="height: 70px; border-bottom: none;"></td>
                                        <td style="height: 70px; border-bottom: none;"></td>
                                        <td style="height: 70px; border-bottom: none;"></td>
                                    </tr>
                                    <tr>
                                        <td style="border-top: none;">PETUGAS</td><td style="border-top: none;">FOREMAN</td><td style="border-top: none;">SUPERVISOR</td>
                                    </tr>
                                </table>
                            </div>
                            <div style="clear:both;"></div>
                        </td>
                    </tr>
                </table>
                <div style="text-align: right; font-size: 11px; margin-top: 5px; font-weight: bold;">STIS-CMFG4-03/R4</div>
                `;
            } 
            
            // ==============================================================================
            // FORMAT A DAN N (STANDAR)
            // ==============================================================================
            else {
                let crossSuhu = !data.suhu ? 'diagonal-strike' : '';
                let crossKelembapan = !data.kelembapan ? 'diagonal-strike' : '';
                let crossKetidakpastian = !data.ketidakpastian ? 'diagonal-strike' : '';
                let crossCL = !data.confidence_level ? 'diagonal-strike' : '';

                html += `
                <div class="pt-title">PT. SURYA TOTO INDONESIA Tbk.</div>
                <table class="table-excel">
                    <tr><td colspan="4" style="border-bottom: 1px solid #000;"><div class="header-title">LAPORAN PEMERIKSAAN</div></td></tr>
                    
                    <tr>
                        <td width="15%">NO. LAPORAN</td><td width="35%">-</td>
                        <td width="15%">SEKSI</td><td width="35%" class="text-bold text-center">${data.seksi_pemilik}</td>
                    </tr>
                    <tr>
                        <td>NAMA ALAT</td><td>${data.nama_part}</td>
                        <td rowspan="2">PETUGAS PERIKSA</td><td rowspan="2" class="text-center">${data.petugas}</td>
                    </tr>
                    <tr>
                        <td>NO. ALAT</td><td>${data.no_part}</td>
                    </tr>
                    <tr>
                        <td>SPESIFIKASI</td><td>${data.spesifikasi}</td>
                        <td>TANGGAL PERIKSA</td><td class="text-center">${dStr}</td>
                    </tr>
                    <tr>
                        <td>RESOLUSI</td><td colspan="3">${data.resolusi || '-'}</td>
                    </tr>

                    <tr><td colspan="4" class="text-center" style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 10px 0;">HASIL PEMERIKSAAN</td></tr>
                    <tr>
                        <td colspan="4" style="padding:0; border:none;">
                            <table style="width:100%; border-collapse: collapse; text-align:center;">
                `;

                var hasBagian = data.hasil_array.some(r => r.bagian && r.bagian.trim() !== '' && r.bagian.trim() !== '-');
                var hasToleransi = data.hasil_array.some(r => r.toleransi !== undefined && r.toleransi !== null && r.toleransi !== '' && r.toleransi !== 0 && r.toleransi !== '-');

                html += '<tr>';
                html += '<td width="5%" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">NO</td>';
                if(hasBagian) { html += '<td width="15%" style="border-right: 1px solid #000; border-bottom: 1px solid #000;">BAGIAN</td>'; }
                html += '<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">STANDAR<br>( ' + data.satuan + ' )</td>';
                if(hasToleransi) { html += '<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">TOLERANSI (Â±)</td>'; }
                html += '<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">HASIL<br>( ' + data.satuan + ' )</td>';
                html += '<td style="border-right: 1px solid #000; border-bottom: 1px solid #000;">KOREKSI<br>( ' + data.satuan + ' )</td>';
                html += '<td width="12%" style="border-bottom: 1px solid #000;">STATUS</td>';
                html += '</tr>';

                data.hasil_array.forEach(function(row, idx) {
                    html += '<tr>';
                    html += '<td style="border-right: 1px solid #000;">' + (idx+1) + '</td>';
                    if(hasBagian) { html += '<td style="border-right: 1px solid #000;">' + (row.bagian || '-') + '</td>'; }
                    html += '<td style="border-right: 1px solid #000;">' + row.standar + '</td>';
                    if(hasToleransi) { html += '<td style="border-right: 1px solid #000;">Â± ' + (row.toleransi || '-') + '</td>'; }
                    html += '<td style="border-right: 1px solid #000;">' + row.hasil + '</td>';
                    html += '<td style="border-right: 1px solid #000;">' + row.koreksi + '</td>';
                    html += '<td style="font-weight:bold;">' + (row.status || '-') + '</td>';
                    html += '</tr>';
                });

                html += `
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-top: 1px solid #000;">METODE</td><td style="border-top: 1px solid #000;">${data.metode}</td>
                        <td style="border-top: 1px solid #000;">KELEMBABAN RUANGAN</td><td style="border-top: 1px solid #000;" class="text-center ${crossKelembapan}">${data.kelembapan ? data.kelembapan+' %' : ''}</td>
                    </tr>
                    <tr>
                        <td>STANDAR UJI</td><td>${data.standar_uji.replace(/\n/g, "<br>")}</td>
                        <td>STANDAR TOLERANSI</td><td class="text-center">${data.toleransi_global}</td>
                    </tr>
                    <tr>
                        <td>CONFIDENCE LEVEL</td><td class="${crossCL}">${data.confidence_level ? data.confidence_level+' %' : ''}</td>
                        <td>KETIDAKPASTIAN</td><td class="text-center ${crossKetidakpastian}">${data.ketidakpastian ? 'Â± '+data.ketidakpastian : ''}</td>
                    </tr>
                    <tr>
                        <td>TRACEABILITY</td><td>${data.traceability}</td>
                        <td>KEPUTUSAN</td><td class="text-center text-bold" style="font-size: 16px;">${data.status_final}</td>
                    </tr>
                    <tr>
                        <td>SUHU RUANGAN</td><td class="text-center ${crossSuhu}">${data.suhu ? data.suhu+' Â°C' : ''}</td>
                        <td>PERIKSA BERIKUTNYA</td><td class="text-center">${dbStr}</td>
                    </tr>
                    <tr>
                        <td colspan="4" style="height: 60px; vertical-align:top; border-top: 1px solid #000; border-bottom: 1px solid #000;">
                            KETERANGAN : <br>
                            <span style="white-space: pre-wrap; font-style:italic;">${data.keterangan || '-'}</span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" style="border:none; padding-top: 20px;">
                            <div style="width: 50%; float: right; text-align: center;">
                                <div style="text-align:left; margin-bottom: 5px;">TANGERANG , ${dSign}</div>
                                <table class="table-excel" style="width: 100%;">
                                    <tr><td width="33%">DIBUAT</td><td width="33%">DIPERIKSA</td><td width="33%">DISETUJUI</td></tr>
                                    <tr>
                                        <td style="height: 70px; border-bottom: none;"></td>
                                        <td style="height: 70px; border-bottom: none;"></td>
                                        <td style="height: 70px; border-bottom: none;"></td>
                                    </tr>
                                    <tr><td style="border-top: none;">PETUGAS</td><td style="border-top: none;">FOREMAN</td><td style="border-top: none;">SUPERVISOR</td></tr>
                                </table>
                            </div>
                            <div style="clear:both;"></div>
                        </td>
                    </tr>
                </table>
                <div style="text-align: right; font-size: 11px; margin-top: 5px; font-weight: bold;">STIS-CMFG4-03/R4</div>
                `;
            }
            
            document.getElementById('area-kertas').innerHTML = html;
        });
    </script>
</body>
</html>

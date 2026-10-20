/* Multi-tool printing reference: Tipe_Format.xlsx, supplied 16 October 2026.
 * A: Lembar1 A / Sheet1; H: Sheet8; I: Sheet9; J: Sheet10;
 * O: Sheet16; P: Sheet17; Q: Sheet18 top; T: Sheet18 bottom.
 * Sample readings and names from Excel are never inserted into live reports.
 */
(function () {
    'use strict';
    const p=window.qaExcelPrintData;
    if (!p) return;
    const selectors={A:'.main-table',H:'.main-h',I:'.main-i',J:'.hasil-j',O:'.grid-o',P:'.grid-p',Q:'.grid-q',T:'.grid-t'};
    const original=document.querySelector('#report '+selectors[p.format]);
    if (!original) return;
    const el=(tag,text,cls)=>{const n=document.createElement(tag);if(text!=null)n.textContent=String(text);if(cls)n.className=cls;return n;};
    const pick=(...values)=>values.find(v=>v!=null && String(v).trim()!=='') ?? '';
    const date=(value,long=false)=>{
        const m=String(value||'').match(/^(\d{4})-(\d{2})-(\d{2})/);
        if(!m)return value||'';
        const months=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        return m[3]+(long?' '+months[Number(m[2])-1]+' ':' - '+m[2]+' - ')+m[1];
    };
    function cell(tag,text,colspan=1,rowspan=1) {
        const c=el(tag,text);c.colSpan=colspan;c.rowSpan=rowspan;return c;
    }
    function headerRow(head,items) {
        const row=el('tr');
        for(const item of items)row.append(Array.isArray(item)?cell('th',...item):cell('th',item));
        head.append(row);
    }
    const report=el('div',null,'report excel-report');
    report.dataset.excelFormat=p.format;
    report.append(el('div',p.format==='J'?'PT.SURYA TOTO INDONESIA Tbk.':'PT. SURYA TOTO INDONESIA Tbk.','excel-company'));
    function signature() {
        const table=el('table',null,'excel-sign');const body=el('tbody');table.append(body);
        const labels=p.format==='A'?['DI BUAT','DI PERIKSA','DIKETAHUI']:['DIBUAT','DIPERIKSA','DISETUJUI'];
        const heading=el('tr'),names=el('tr',null,'excel-sign-names'),roles=el('tr');
        labels.forEach(t=>heading.append(cell('th',t)));
        ['pembuat','pemeriksa','penyetuju'].forEach((stage,index)=>{
            const c=cell('td','');
            // Do not place an approval name on a pending or rejected transaction.
            const approved=stage==='pembuat'?p.signature_pembuat!==false:!!p['signature_'+stage];
            if(approved) {
                c.append(document.createTextNode(index===0?pick(p.operator,p.petugas):p[stage]||''));
                if(index && p['tanggal_validasi_'+stage])c.append(el('br'),el('span',date(p['tanggal_validasi_'+stage]),'excel-sign-date'));
            }
            names.append(c);roles.append(cell('td',['PETUGAS','FOREMAN','SUPERVISOR'][index]));
        });
        body.append(heading,names,roles);return table;
    }
    function infoRow(table,label,value,label2,value2) {
        const row=el('tr');
        row.append(cell('td',label),cell('td',value),cell('td',label2),cell('td',value2));table.append(row);
    }
    const reportKind = String(p.jenis_laporan||'').trim().toLowerCase() ||
        (/\/Lap\/Kalibrasi\//i.test(String(p.no_laporan||''))?'kalibrasi':
         (/\/Lap\/(Periksa|Pemeriksaan)\//i.test(String(p.no_laporan||''))?'periksa':''));
    const excelTitle = reportKind==='kalibrasi'?'LAPORAN KALIBRASI':
        reportKind==='periksa'?'LAPORAN PEMERIKSAAN':
        p.format==='J'?'LAPORAN VERIFIKASI':'LAPORAN PEMERIKSAAN GAUGE';
    const title=el('div',excelTitle,'excel-title');
    const info=el('table',null,'excel-info');
    if(p.format==='J') {
        report.append(title,info);
        infoRow(info,'NO LAPORAN',p.no_laporan,'SEKSI',p.seksi_pemilik);
        infoRow(info,'NAMA ALAT',p.nama_part,'PETUGAS',p.operator);
        infoRow(info,'METODE',p.metode,'','');
        infoRow(info,'ALAT VERIFIKASI',pick(p.alat_verifikasi,p.standar_uji),'TANGGAL VERIFIKASI',date(p.tanggal_periksa));
        report.append(el('div','HASIL VERIFIKASI','excel-results-title'));
    } else {
        const top=el('div',null,'excel-top'),left=el('div'),right=el('div',null,'excel-approval');
        report.append(title);
        left.append(info);
        right.append(el('div','TANGGAL : '+date(p.tanggal_periksa,true),'excel-date'),signature());
        const suhu=String(p.suhu??'').replace(/\s*°?\s*C\s*$/i,'');
        const humidity=String(p.kelembapan??'').replace(/\s*%\s*$/,'');
        infoRow(info,'NO LAPORAN',': '+p.no_laporan,p.format==='O'?'STANDAR UJI / NO ALAT':'ALAT STANDAR',': '+p.standar_uji);
        infoRow(info,'NAMA ALAT',': '+p.nama_part,'TRACEABILITY',': '+p.traceability);
        infoRow(info,'METODE',': '+p.metode,'SUHU / HUMIDITY',': '+(suhu?suhu+' °C':'')+' / '+(humidity?humidity+' %':''));
        infoRow(info,'SEKSI',': '+p.seksi_pemilik,p.format==='Q'?'TANGGAL KALIBRASI':'TANGGAL PERIKSA',': '+date(p.tanggal_periksa,true));
        top.append(left,right);report.append(top);
    }
    report.append(el('div','HAL : 1 / 1','excel-page-number'));
    const table=el('table',null,original.className+' excel-grid');
    const head=el('thead');table.append(head);
    let widths;
    if(p.format==='A'||p.format==='H') {
        widths=[3,9,12,9,8,5,8,8,4,4,4,4,4,4,5,4,8];
        headerRow(head,[['NO',1,4],['ALAT YANG DIPERIKSA',2],['ALAT STANDAR'+(p.format==='H'?' YANG DIPAKAI':''),2],['HASIL PERIKSA',9],['KODE WARNA',1,4],['KEP',1,4],[p.format==='A'?'PERIKSA BERIKUTNYA':'TANGGAL KALIBRASI BERIKUTNYA',1,4]]);
        headerRow(head,[['NO ALAT',1,3],['SPESIFIKASI',1,3],['SPESIFIKASI',1,3],['NO ALAT',1,3],['STD UKURAN',3],['HASIL UKUR',6]]);
        headerRow(head,[['POSISI UKUR',1,2],['OUT Ø',1,2],['PITCH Ø',1,2],['OUT Ø',3],['PITCH Ø',3]]);
        headerRow(head,['DEPAN','TENGAH','BELAKANG','DEPAN','TENGAH','BELAKANG']);
    } else if(p.format==='I') {
        widths=[4,12,14,14,10,10,12,7,7,10];
        headerRow(head,[['ALAT YANG DIPERIKSA',3],['ALAT STANDAR YANG DIPAKAI',2],['LOKASI PERIKSA',1,2],['HASIL PERIKSA',1,2],['KODE WARNA',1,2],['KEPUTUSAN',1,2],['TANGGAL PERIKSA BERIKUTNYA',1,2]]);
        headerRow(head,['NO','NO ALAT','SPESIFIKASI','SPESIFIKASI','NO ALAT']);
    } else if(p.format==='J') {
        widths=[6,17,23,23,15,16];
        headerRow(head,[['NO',1,2],['ALAT YANG DIVERIFIKASI',2],'SPESIFIKASI','HASIL',['TGL. VERIFIKASI BERIKUT',1,2]]);
        headerRow(head,['NO ALAT','SPESIFIKASI','ALAT VERIFIKASI','VERIFIKASI']);
    } else if(p.format==='P') {
        widths=[3,10,13,12,10,23,12,4,8,7];
        headerRow(head,[['NO',1,2],['NO ALAT',1,2],['TYPE DAN UKURAN',1,2],['ALAT YANG DIPAKAI',2],['HASIL PERIKSA',2],['KEP',1,2],['TANGGAL PERIKSA BERIKUTNYA',1,2],['NO GAMBAR',1,2]]);
        headerRow(head,['NAMA ALAT','NO ALAT','STD UKURAN','AKTUAL']);
    } else if(p.format==='Q') {
        widths=[3,10,12,10,9,8,14,5,5,5,6,4,9];
        headerRow(head,[['NO',1,2],['ALAT YANG DIPERIKSA',2],['ALAT STANDAR YANG DIPAKAI',2],['STANDAR UKURAN',2,2],['HASIL UKUR ( CROSS )',3],['KODE WARNA',1,2],['KEP',1,2],['TANGGAL KALIBRASI BERIKUTNYA',1,2]]);
        headerRow(head,['NO ALAT','SPESIFIKASI','SPESIFIKASI','NO ALAT','I','II','III']);
    } else if(p.format==='O') {
        widths=[3,10,10,6,7,8,7,7,7,7,6,6,9,7];
        headerRow(head,[['NO',1,4],['NO ALAT',1,4],['SPESIFIKASI',1,4],['HASIL PERIKSA',7],['KODE WARNA',1,4],['KEPUTUSAN',1,4],['TANGGAL PERIKSA BERIKUTNYA',1,4],['NO GAMBAR',1,4]]);
        headerRow(head,[['STD UKURAN',5],['STANDAR HASIL',2]]);
        headerRow(head,[['POSISI UKUR',1,2],['TOLERANSI',2],'MIN','MAX','MIN','MAX']);
        headerRow(head,[['ALAT',2],'( mm )','( mm )','( MASUK )','( TIDAK MASUK )']);
    } else {
        // The lower gauge form on Sheet18 has the existing T result columns.
        head.replaceWith(original.tHead.cloneNode(true));
        widths=[3,9,10,9,8,6,15,4,4,4,4,5,4,8,7];
    }
    const cols=el('colgroup'),total=widths.reduce((a,b)=>a+b,0);
    widths.forEach(w=>{const c=el('col');c.style.width=(w/total*100)+'%';cols.append(c);});table.prepend(cols);
    if(p.format==='A') {
        // Excel A retains both standard dimensions and their tolerance/range.
        // The former single-tool HTML omitted these columns entirely.
        const body=el('tbody');
        for(const item of p.hasil_array) {
            const go=item.go||{},ng=item.not_go||item.notgo||{};
            [go,ng].forEach((reading,index)=>{
                const row=el('tr');
                if(!index)row.append(cell('td','1',1,2),cell('td',p.no_part,1,2),cell('td',p.spesifikasi,1,2));
                row.append(cell('td',pick(reading.std_spesifikasi,reading.alat_standar?.spesifikasi,item.alat_standar?.spesifikasi)),cell('td',pick(reading.std_no_alat,reading.alat_standar?.no_alat,item.alat_standar?.no_alat)),cell('td',index?'NOT GO':'GO'));
                ['out','pitch'].forEach(axis=>{
                    const c=cell('td',''),line=el('div',null,'excel-standard-value');
                    line.append(el('span',reading['std_'+axis]??''),el('span',reading['tol_'+axis]??''));c.append(line);
                    if(reading['range_'+axis])c.append(el('div',String(reading['range_'+axis]).replace(/^\s*\(?\s*(.*?)\s*\)?\s*$/,'( $1 )'),'excel-range'));
                    row.append(c);
                });
                ['out','pitch'].forEach(axis=>['dep','ten','bel'].forEach(pos=>row.append(cell('td',reading[axis]?.[pos]??''))));
                if(!index)row.append(cell('td',pick(item.warna,item.kode_warna,p.kode_warna),1,2),cell('td',pick(item.kep,p.keputusan),1,2),cell('td',date(pick(item.periksa_berikut,p.jadwal_berikutnya)),1,2));
                body.append(row);
            });
        }
        table.append(body);
    } else {
        for(const body of Array.from(original.tBodies))table.append(body.cloneNode(true));
    }
    // Keep the measurement values/rowspans produced by the existing normalization,
    // but use the workbook's compact sizing rather than the old fixed-width cards.
    table.querySelectorAll('tbody td,tbody tr').forEach(c=>c.removeAttribute('style'));
    if(p.format==='P') {
        table.querySelectorAll('.actual-p').forEach(c=>c.classList.add('excel-actual-p'));
        table.querySelectorAll('.std-label').forEach(c=>c.classList.add('excel-standard-p'));
    }
    report.append(table);
    const note=el('div',null,'excel-note');
    note.append(el('b','KETERANGAN :'),el('br'),document.createTextNode(pick(p.keterangan,p.hasil_array[0]?.keterangan)));
    report.append(note);
    if(p.format==='J') {
        const legend=el('div','O : OKE\nX : TIDAK OKE/BAD','excel-legend');
        const signatures=el('div',null,'excel-footer-sign');
        signatures.append(el('div','TANGERANG, '+date(p.tanggal_periksa,true),'excel-date'),signature());report.append(legend,signatures);
    }
    report.append(el('div',p.format==='J'?'STIS-CMFG4-39/R3':'STIS-CMFG4-06/R1','excel-document-code'));
    document.getElementById('report').replaceChildren(report);
    const style=el('style');style.textContent=`
        .report.excel-report{font:8pt Arial,Helvetica,sans-serif;width:206mm;min-height:293mm;margin:2mm auto;padding:5mm;border:0;background:white;color:black}
        body.print-landscape .report.excel-report{width:293mm;min-height:206mm}
        .excel-company{text-align:center;font-weight:bold;margin:0 0 3mm;font-size:9pt}
        .excel-top{display:grid;grid-template-columns:minmax(0,1fr) 65mm;gap:3mm;align-items:start}
        .excel-title{text-align:center;font-size:15pt;margin:0 0 4mm;font-weight:normal}
        .excel-date{font-size:8pt;margin-bottom:2mm}
        .excel-info{width:100%;border-collapse:collapse;table-layout:fixed;font-size:8pt;margin-bottom:2mm}
        .excel-info td{border:0;padding:0.7mm 0.5mm;vertical-align:top}
        .excel-info td:nth-child(odd){width:20%}.excel-info td:nth-child(even){width:30%}
        .excel-sign{width:100%;border-collapse:collapse;table-layout:fixed;font-size:8pt;text-align:center}
        .excel-sign td,.excel-sign th{border:0.2mm solid black;padding:1mm 0.5mm;font-weight:normal}
        .excel-sign-names td{height:17mm;vertical-align:bottom}.excel-sign-date{font-size:7pt}
        .excel-page-number{text-align:right;font-size:8pt;margin:1mm 0}
        .excel-report .excel-grid{width:100%;margin:0;border-collapse:collapse;table-layout:fixed;font-size:8pt}
        .excel-report .excel-grid td,.excel-report .excel-grid th{border:0.2mm solid black;padding:1mm 0.6mm;text-align:center;vertical-align:middle;font-size:8pt;height:auto;line-height:1.2}
        .excel-report .excel-grid th{font-weight:normal;min-height:5mm}
        .excel-report .excel-grid td{height:6mm}
        .excel-standard-value{display:flex;justify-content:space-evenly;gap:1mm}.excel-range{margin-top:1mm}
        .excel-report .excel-grid .small-h{font-size:8pt}
        .excel-report .excel-grid .excel-actual-p > div{display:inline-block;width:50%;margin:0!important}
        .excel-report .excel-grid .excel-standard-p > div{display:inline-block;width:50%;margin:0!important;font-size:8pt!important;white-space:normal!important;vertical-align:middle}
        .excel-note{min-height:12mm;padding:2mm 0;font-size:8pt;white-space:pre-line}
        .excel-document-code{text-align:right;font-size:8pt;margin-top:2mm}
        .excel-results-title{text-align:center;border:0.2mm solid black;border-bottom:0;padding:1mm;font-size:9pt}
        .excel-legend{white-space:pre-line;font-size:8pt}.excel-footer-sign{width:96mm;margin:3mm 0 0 auto}
        .excel-footer-sign .excel-sign-names td{height:22mm}
    `;document.head.append(style);
})();

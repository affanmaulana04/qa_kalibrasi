/* Reuse each existing format renderer, then combine its result rows by format. */
(function () {
    'use strict';
    const source = JSON.parse(document.getElementById('dailyData').textContent);
    const preview = document.getElementById('dailyPreview');
    if (!preview) return;
    const output = document.getElementById('dailyReports');
    const progress = document.getElementById('dailyProgress');
    const print = document.getElementById('dailyPrint');
    const choices = Array.from(document.querySelectorAll('.daily-choice:not(:disabled)'));
    const all = document.getElementById('dailyAll');
    const selectors = {A:'.main-table',H:'.main-h',I:'.main-i',J:'.hasil-j',O:'.grid-o',P:'.grid-p',Q:'.grid-q',T:'.grid-t'};
    let generation = 0;
    function invalidate() {
        generation++; print.disabled = true; output.replaceChildren(); progress.textContent = '';
        all.checked = choices.length > 0 && choices.every(c => c.checked);
        all.indeterminate = choices.some(c => c.checked) && !all.checked;
    }
    choices.forEach(c => c.addEventListener('change',invalidate));
    all.addEventListener('change',() => { choices.forEach(c => c.checked = all.checked); invalidate(); });
    print.addEventListener('click',() => { if (!print.disabled) window.print(); });
    function node(tag,text,className) {
        const el = document.createElement(tag);
        if (text !== undefined) el.textContent = text;
        if (className) el.className = className;
        return el;
    }
    // Import table markup only; never copy scripts or event attributes from history.
    function safeCopy(original) {
        if (original.nodeType === Node.TEXT_NODE) return document.createTextNode(original.textContent);
        if (original.nodeType !== Node.ELEMENT_NODE) return document.createTextNode('');
        const tag = original.tagName.toLowerCase();
        if (!['table','thead','tbody','tr','td','th','colgroup','col','span','div','br','b','strong','i','sub','sup'].includes(tag)) return document.createTextNode(original.textContent);
        const el = document.createElement(tag);
        if (original.classList.contains('diagonal-strike-td')) el.classList.add('diagonal-strike-td');
        for (const attr of ['rowspan','colspan','span']) if (/^[1-9]\d*$/.test(original.getAttribute(attr) || '')) el.setAttribute(attr,original.getAttribute(attr));
        for (const prop of ['width','textAlign','verticalAlign','fontWeight']) if (original.style[prop]) el.style[prop] = original.style[prop];
        original.childNodes.forEach(child => el.appendChild(safeCopy(child)));
        return el;
    }
    function load(record) {
        return new Promise((resolve,reject) => {
            const frame = node('iframe',undefined,'daily-render-frame');
            frame.title = 'Memuat transaksi '+record.id;
            const params = new URLSearchParams({seksi:source.section,tanggal:source.date,render:record.id,revision:record.revision,embed:'1'});
            const timer = setTimeout(() => { frame.remove(); reject(new Error('Waktu pemuatan transaksi habis. Coba kembali.')); },20000);
            frame.onload = () => {
                try {
                    const tables = Array.from(frame.contentDocument.querySelectorAll('#report '+selectors[record.payload.format]));
                    if (!tables.length) throw new Error('Rincian transaksi '+record.id+' tidak dapat ditampilkan atau sudah berubah. Tampilkan ulang daftar transaksi.');
                    const result = tables.map(safeCopy);
                    clearTimeout(timer); frame.remove(); resolve(result);
                } catch (err) { clearTimeout(timer); frame.remove(); reject(err); }
            };
            frame.src = 'laporan_hasil_kalibrasi.php?'+params;
            document.body.appendChild(frame);
        });
    }
    function metadata(record,columns) {
        const p = record.payload;
        const tr = node('tr'),td = node('td',undefined,'daily-meta'); td.colSpan = columns;
        const lines = [
            [['Transaksi',p.nomor_pengecekan || '#'+record.id],['No. laporan',p.no_laporan],['Dibuat',record.created_at],['Tanggal periksa',p.tanggal_periksa],['Status',record.status]],
            [['Nama alat',p.nama_part],['Petugas',p.operator],['Metode',p.metode],['Standar uji',p.standar_uji],['Traceability',p.traceability],['Suhu',p.suhu],['Kelembapan',p.kelembapan]],
            [['Pemeriksa',p.pemeriksa ? p.pemeriksa+' ('+p.tanggal_validasi_pemeriksa+')' : '—'],['Penyetuju',p.penyetuju ? p.penyetuju+' ('+p.tanggal_validasi_penyetuju+')' : '—'],['Keterangan',p.keterangan]]
        ];
        lines.forEach(fields => { const line=node('div'); fields.forEach(([label,value]) => { if (value !== '' && value != null) { const span=node('span');span.append(node('strong',label+': '),document.createTextNode(String(value)));line.append(span); } }); td.append(line); });
        tr.append(td); return tr;
    }
    preview.addEventListener('click',async () => {
        invalidate();
        const run = generation;
        const selected = new Set(choices.filter(c => c.checked).map(c => Number(c.value)));
        const records = source.records.filter(r => selected.has(r.id));
        if (!records.length) { progress.textContent = 'Pilih minimal satu transaksi.'; return; }
        preview.disabled = true; progress.classList.remove('daily-error');
        const groups = new Map();
        try {
            for (let i=0;i<records.length;i++) {
                const record=records[i],format=record.payload.format;
                progress.textContent = 'Memuat '+(i+1)+' / '+records.length+' transaksi…';
                const tables = await load(record);
                if (run !== generation) return;
                let group=groups.get(format);
                if (!group) {
                    const report=node('section',undefined,'daily-report');
                    report.append(node('div','PT. SURYA TOTO INDONESIA Tbk.','daily-company'),node('h3','LAPORAN HASIL KALIBRASI — FORMAT '+format),node('div',source.formats[format]+' · Seksi: '+source.section+' · Tanggal kalibrasi: '+source.date,'daily-period'));
                    const table=node('table',undefined,'daily-grid');
                    for (const child of tables[0].children) if (['COLGROUP','THEAD'].includes(child.tagName)) table.append(child.cloneNode(true));
                    const header=table.querySelector('thead');
                    if (!header) throw new Error('Header format '+format+' tidak tersedia.');
                    const columns = Math.max(...Array.from(header.rows).map(row => Array.from(row.cells).reduce((n,c)=>n+c.colSpan,0)));
                    report.append(table); group={report,table,columns,number:0}; groups.set(format,group);
                }
                const body=node('tbody'); body.append(metadata(record,group.columns));
                for (const table of tables) {
                    let remaining=0;
                    for (const oldBody of table.tBodies) for (const row of Array.from(oldBody.rows)) {
                        if (remaining === 0 && !row.textContent.trim()) continue;
                        if (remaining>0) remaining--;
                        else if (row.cells[0] && /^\d+$/.test(row.cells[0].textContent.trim())) {
                            row.cells[0].textContent=String(++group.number);remaining=row.cells[0].rowSpan-1;
                        }
                        body.append(row);
                    }
                }
                if (body.rows.length===1) throw new Error('Hasil transaksi '+record.id+' kosong.');
                group.table.append(body);
            }
            if (run !== generation) return;
            Array.from(groups.keys()).sort().forEach(key => output.append(groups.get(key).report));
            print.disabled=false;progress.textContent=records.length+' transaksi digabung dalam '+groups.size+' format.';
        } catch (err) { if (run === generation) { output.replaceChildren();progress.textContent=err.message;progress.classList.add('daily-error'); } }
        finally { preview.disabled=false; }
    });
    invalidate();
})();

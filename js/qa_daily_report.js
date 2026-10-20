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
    print.addEventListener('click',() => { if (!print.disabled && output.firstElementChild) { output.firstElementChild.contentWindow.focus(); output.firstElementChild.contentWindow.print(); } });
    function node(tag,text,className) {
        const el = document.createElement(tag);
        if (text !== undefined) el.textContent = text;
        if (className) el.className = className;
        return el;
    }
    // Copy the original report's layout, never its scripts or event handlers.
    function safeCopy(original) {
        if (original.nodeType === Node.TEXT_NODE) return document.createTextNode(original.textContent);
        if (original.nodeType !== Node.ELEMENT_NODE) return document.createTextNode('');
        const tag = original.tagName.toLowerCase();
        if (!['table','thead','tbody','tfoot','tr','td','th','colgroup','col','span','div','br','b','strong','i','sub','sup','p','small','u','em','hr'].includes(tag)) return document.createTextNode('');
        const el = document.createElement(tag);
        el.className = original.className;
        for (const attr of ['rowspan','colspan','span']) if (/^[1-9]\d*$/.test(original.getAttribute(attr) || '')) el.setAttribute(attr,original.getAttribute(attr));
        // Inline properties are template layout only. No URL-bearing CSS is copied.
        for (const prop of original.style) {
            const value = original.style.getPropertyValue(prop);
            if (!/url\s*\(|expression\s*\(/i.test(value)) el.style.setProperty(prop,value,original.style.getPropertyPriority(prop));
        }
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
                    const doc = frame.contentDocument;
                    const original = doc.querySelector('#report > .report');
                    if (!original || !original.querySelector(selectors[record.payload.format])) throw new Error('Rincian transaksi '+record.id+' tidak dapat ditampilkan atau sudah berubah. Tampilkan ulang daftar transaksi.');
                    const result = {report:safeCopy(original),styles:Array.from(doc.querySelectorAll('style'),s=>s.textContent).join('\n'),landscape:doc.body.classList.contains('print-landscape')};
                    clearTimeout(timer); frame.remove(); resolve(result);
                } catch (err) { clearTimeout(timer); frame.remove(); reject(err); }
            };
            frame.src = 'laporan_hasil_kalibrasi.php?'+params;
            document.body.appendChild(frame);
        });
    }
    function prepare(rendered,record) {
        const report=rendered.report,selector=selectors[record.payload.format],tables=Array.from(report.querySelectorAll(selector));
        if (tables.length!==1) throw new Error('Susunan tabel format '+record.payload.format+' tidak dapat digabung.');
        const table=tables[0],body=node('tbody');body.dataset.transaction=record.id;
        for (const oldBody of Array.from(table.tBodies)) {
            let remaining=0;
            for (const row of Array.from(oldBody.rows)) {
                if (!remaining && !row.textContent.trim()) continue; // Template padding, not a measurement.
                remaining=Math.max(remaining-1,...Array.from(row.cells,c=>c.rowSpan-1));
                body.append(row);
            }
            oldBody.remove();
        }
        if (!body.rows.length) throw new Error('Hasil transaksi '+record.id+' kosong.');
        table.dataset.dailyResults='';
        const fields={};
        function field(el,key,label) {
            if (!el) return;
            if (!label && /^\s*:/.test(el.textContent)) el.dataset.dailyPrefix=': ';
            fields[key]=el.textContent.replace(label || /^\s*:\s*/, '').trim();
            el.dataset.dailyField=key;el.replaceChildren();
        }
        // Only these values may be collected across tools. The rest of the original
        // header (standards, method, environment, approvals) must match exactly.
        for (const cell of report.querySelectorAll('td,th')) {
            if (cell.querySelector('table') || cell.closest('[data-daily-results]')) continue;
            const label=cell.textContent.trim().replace(/[.:]/g,'').replace(/\s+/g,' ').toUpperCase();
            if (label==='NO LAPORAN') field(cell.nextElementSibling,'report');
            if (label==='NAMA ALAT') field(cell.nextElementSibling,'name');
            if (label==='KETERANGAN') field(cell.nextElementSibling,'note');
        }
        const note=report.querySelector('.excel-note,.ket-h,.ket-i,.ket-j,.ket-o,.ket-p,.ket-q,.ket-t');
        if (note) field(note,'note',/^\s*KETERANGAN\s*:\s*/i);
        const key=record.payload.format+'|'+record.status+'|'+report.outerHTML;
        return {...rendered,key,body,fields,record};
    }
    function fillFields(report,entries,headerEntries) {
        for (const el of report.querySelectorAll('[data-daily-field]')) {
            const key=el.dataset.dailyField;
            const single=key==='name' || key==='report';
            const candidates=single?headerEntries:entries;
            const values=Array.from(new Set(candidates.map(e=>e.fields[key]).filter(Boolean)));
            if (single) values.splice(1);
            el.replaceChildren();
            if (el.dataset.dailyPrefix) el.append(document.createTextNode(el.dataset.dailyPrefix));
            if (key==='note' && (/^ket-/.test(el.className) || el.classList.contains('excel-note'))) el.append(node('b','KETERANGAN :'),node('br'));
            // One name and report number identify the combined report on every page.
            values.forEach((value,i)=>{if(i)el.append(node('br'));el.append(document.createTextNode(value));});
        }
    }
    function buildDocument(groups,styles) {
        const frame=node('iframe',undefined,'daily-print-frame');frame.title='Pratinjau format laporan kalibrasi';
        output.append(frame);
        const doc=frame.contentDocument;
        doc.open();doc.write('<!doctype html><html lang="id"><head><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="default-src \'none\'; style-src \'unsafe-inline\'"><title>Laporan Kalibrasi Multi-Alat</title></head><body></body></html>');doc.close();
        const css=doc.createElement('style');css.textContent=styles+`
            @page portraitA4 { size:A4 portrait; margin:0; }
            .daily-sheet { width:210mm; margin:0 auto 20px; background:white; display:flow-root; }
            .daily-sheet.landscape { width:297mm; }
            .daily-sheet.landscape > .report { width:293mm!important; min-height:206mm!important; margin:2mm auto!important; }
            .daily-sheet.portrait > .report { max-width:none; }
            [data-daily-results] tbody { break-inside:avoid; }
            @media print {
                .daily-sheet { margin:0; break-after:page; page:portraitA4; }
                .daily-sheet.landscape { page:landscapeA4; }
                .daily-sheet:last-child { break-after:auto; }
            }
        `;doc.head.append(css);
        for (const group of groups.values()) {
            let current;const pages=[];
            function newPage() {
                const sheet=doc.createElement('section');sheet.className='daily-sheet '+(group.landscape?'landscape':'portrait');
                const report=group.report.cloneNode(true);sheet.append(report);doc.body.append(sheet);
                current={sheet,report,entries:[]};pages.push(current);
            }
            newPage();
            for (const entry of group.entries) {
                const body=entry.body;
                current.entries.push(entry);current.report.querySelector('[data-daily-results]').append(body);
                fillFields(current.report,current.entries,group.entries);
                // Keep each transaction's rowspans together and repeat the original
                // full header/footer on continuation pages.
                const maxHeight=(group.landscape?210:297)*96/25.4;
                if (current.entries.length>1 && current.sheet.getBoundingClientRect().height>maxHeight+1) {
                    current.entries.pop();body.remove();fillFields(current.report,current.entries,group.entries);
                    newPage();current.entries.push(entry);current.report.querySelector('[data-daily-results]').append(body);fillFields(current.report,current.entries,group.entries);
                }
            }
            pages.forEach((page,index)=>{
                const walker=doc.createTreeWalker(page.report,NodeFilter.SHOW_TEXT);
                while(walker.nextNode()) {
                    const text=walker.currentNode;
                    if (/HAL\s*:\s*1\s*\/\s*1/i.test(text.textContent)) text.textContent=text.textContent.replace(/HAL\s*:\s*1\s*\/\s*1/i,'HAL : '+(index+1)+' / '+pages.length);
                }
            });
        }
        return frame;
    }
    preview.addEventListener('click',async () => {
        invalidate();
        const run = generation;
        const selected = new Set(choices.filter(c => c.checked).map(c => Number(c.value)));
        const records = source.records.filter(r => selected.has(r.id));
        if (!records.length) { progress.textContent = 'Pilih minimal satu transaksi.'; return; }
        preview.disabled = true; progress.classList.remove('daily-error');
        const groups = new Map();let styles='';
        try {
            for (let i=0;i<records.length;i++) {
                const record=records[i];
                progress.textContent = 'Memuat '+(i+1)+' / '+records.length+' transaksi...';
                const entry=prepare(await load(record),record);
                if (run !== generation) return;
                styles=entry.styles;
                let group=groups.get(entry.key);
                if (!group) { group={...entry,entries:[],number:0};groups.set(entry.key,group); }
                let remaining=0;
                for (const row of entry.body.rows) {
                    if (remaining>0) remaining--;
                    else if (row.cells[0] && /^\d+$/.test(row.cells[0].textContent.trim())) {
                        row.cells[0].textContent=String(++group.number);remaining=row.cells[0].rowSpan-1;
                    }
                }
                group.entries.push(entry);
            }
            if (run !== generation) return;
            buildDocument(groups,styles);
            print.disabled=false;progress.textContent=records.length+' transaksi ditampilkan dengan format laporan asli.';
        } catch (err) { if (run === generation) { output.replaceChildren();progress.textContent=err.message;progress.classList.add('daily-error'); } }
        finally { preview.disabled=false; }
    });
    invalidate();
})();

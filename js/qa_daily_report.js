/* Reuse each existing format renderer, then combine its result rows by format. */
(function() {
    'use strict';
    const source = JSON.parse(document.getElementById('dailyData').textContent);
    const preview = document.getElementById('dailyPreview');
    if (!preview) return;
    const output = document.getElementById('dailyReports');
    const progress = document.getElementById('dailyProgress');
    const print = document.getElementById('dailyPrint');
    const choices = Array.from(document.querySelectorAll('.daily-choice:not(:disabled)'));
    const all = document.getElementById('dailyAll');
    const selectors = {
        A: '.main-table',
        H: '.main-h',
        I: '.main-i',
        J: '.hasil-j',
        O: '.grid-o',
        P: '.grid-p',
        Q: '.grid-q',
        T: '.grid-t'
    };
    let generation = 0;

    function invalidate() {
        generation++;
        print.disabled = true;
        output.replaceChildren();
        progress.textContent = '';
        all.checked = choices.length > 0 && choices.every(c => c.checked);
        all.indeterminate = choices.some(c => c.checked) && !all.checked;
    }
    choices.forEach(c => c.addEventListener('change', invalidate));
    all.addEventListener('change', () => {
        choices.forEach(c => c.checked = all.checked);
        invalidate();
    });
    print.addEventListener('click', () => {
        if (!print.disabled && output.firstElementChild) {
            output.firstElementChild.contentWindow.focus();
            output.firstElementChild.contentWindow.print();
        }
    });

    function node(tag, text, className) {
        const el = document.createElement(tag);
        if (text !== undefined) el.textContent = text;
        if (className) el.className = className;
        return el;
    }
    function reportSuffix(value) {
        const number = String(value || '').trim().replace(/^:\s*/, '');
        const match = number.match(/^[^/]+\/(Lap\/(?:Kalibrasi|Periksa|Pemeriksaan|Verifikasi)\/[IVXLCDM]+\/\d{4})$/i);
        return match ? match[1] : '';
    }
    function fallbackReportSuffix(group) {
        const title = String(group.report.querySelector('.excel-title')?.textContent || '').toUpperCase();
        const kind = title.includes('VERIFIKASI') ? 'Verifikasi' : title.includes('KALIBRASI') ? 'Kalibrasi' : 'Periksa';
        const roman = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][Number(source.date.slice(5, 7)) - 1];
        return 'Lap/' + kind + '/' + roman + '/' + source.date.slice(0, 4);
    }
    function combinedReportNumber(group, sequence) {
        if (group.entries.length === 1)
            return String(group.entries[0].record.payload.no_laporan || '').trim().replace(/^:\s*/, '');
        const suffixes = group.entries.map(entry => reportSuffix(entry.record.payload.no_laporan));
        const originalSuffixes = Array.from(new Set(suffixes.filter(Boolean).map(value => value.toUpperCase())));
        if (originalSuffixes.length > 1 || (originalSuffixes.length && suffixes.some(value => !value)))
            throw new Error('Jenis, bulan, atau tahun pada nomor laporan asal berbeda. Pilih transaksi dengan format nomor yang sama.');
        const originalSuffix = suffixes.find(Boolean) || fallbackReportSuffix(group);
        return String(sequence).padStart(2, '0') + '/' + originalSuffix;
    }
    function combinedToolName(group) {
        const names = Array.from(new Set(group.entries.map(entry => String(entry.record.payload.nama_part || entry.fields.name || '').trim()).filter(Boolean)));
        if (names.length === 1) return names[0];
        if (group.record.payload.format === 'P') {
            const types = Array.from(new Set(group.entries.map(entry => {
                const first = entry.record.payload.hasil_array?.[0] || {};
                return String(first.type || first.type_ukuran || '').trim();
            }).filter(Boolean)));
            if (types.length === 1 && names.every(name => name.toLocaleLowerCase('id').includes(types[0].toLocaleLowerCase('id'))))
                return types[0];
        }
        return names.join(', ');
    }
    function showToolNameInResult(entry) {
        // Format P already has the workbook's TYPE DAN UKURAN and
        // ALAT YANG DIPAKAI fields; adding a name changes that layout.
        if (entry.record.payload.format === 'P') return;
        const number = String(entry.record.payload.no_part || '').trim();
        const name = String(entry.record.payload.nama_part || '').trim();
        if (!number || !name) return;
        const cell = Array.from(entry.body.querySelectorAll('td')).find(td => td.textContent.trim() === number);
        if (!cell) throw new Error('Nomor alat ' + number + ' tidak ditemukan pada hasil format ' + entry.record.payload.format + '.');
        cell.append(node('small', name, 'daily-tool-name'));
    }
    // Copy the original report's layout, never its scripts or event handlers.
    function safeCopy(original) {
        if (original.nodeType === Node.TEXT_NODE) return document.createTextNode(original.textContent);
        if (original.nodeType !== Node.ELEMENT_NODE) return document.createTextNode('');
        const tag = original.tagName.toLowerCase();
        if (!['table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'colgroup', 'col', 'span', 'div', 'br', 'b', 'strong', 'i', 'sub', 'sup', 'p', 'small', 'u', 'em', 'hr'].includes(tag)) return document.createTextNode('');
        const el = document.createElement(tag);
        el.className = original.className;
        for (const attr of ['rowspan', 'colspan', 'span'])
            if (/^[1-9]\d*$/.test(original.getAttribute(attr) || '')) el.setAttribute(attr, original.getAttribute(attr));
        // Inline properties are template layout only. No URL-bearing CSS is copied.
        for (const prop of original.style) {
            const value = original.style.getPropertyValue(prop);
            if (!/url\s*\(|expression\s*\(/i.test(value)) el.style.setProperty(prop, value, original.style.getPropertyPriority(prop));
        }
        original.childNodes.forEach(child => el.appendChild(safeCopy(child)));
        return el;
    }

    function load(record) {
        return new Promise((resolve, reject) => {
            const frame = node('iframe', undefined, 'daily-render-frame');
            frame.title = 'Memuat transaksi ' + record.id;
            const params = new URLSearchParams({
                seksi: source.section,
                tanggal: source.date,
                render: record.id,
                revision: record.revision,
                embed: '1'
            });
            const timer = setTimeout(() => {
                frame.remove();
                reject(new Error('Waktu pemuatan transaksi habis. Coba kembali.'));
            }, 20000);
            // The original renderer finishes its header cleanup in a queued
            // timer. Copying on load can capture different intermediate layouts
            // for otherwise identical tools and split them into separate pages.
            frame.onload = () => setTimeout(() => {
                try {
                    const doc = frame.contentDocument;
                    const original = doc.querySelector('#report > .report');
                    if (!original || !original.querySelector(selectors[record.payload.format])) throw new Error('Rincian transaksi ' + record.id + ' tidak dapat ditampilkan atau sudah berubah. Tampilkan ulang daftar transaksi.');
                    const result = {
                        report: safeCopy(original),
                        styles: Array.from(doc.querySelectorAll('style'), s => s.textContent).join('\n'),
                        landscape: doc.body.classList.contains('print-landscape')
                    };
                    clearTimeout(timer);
                    frame.remove();
                    resolve(result);
                } catch (err) {
                    clearTimeout(timer);
                    frame.remove();
                    reject(err);
                }
            }, 50);
            frame.src = 'laporan_hasil_kalibrasi.php?' + params;
            document.body.appendChild(frame);
        });
    }

    function prepare(rendered, record) {
        const report = rendered.report,
            selector = selectors[record.payload.format],
            tables = Array.from(report.querySelectorAll(selector));
        if (tables.length !== 1) throw new Error('Susunan tabel format ' + record.payload.format + ' tidak dapat digabung.');
        const table = tables[0],
            body = node('tbody');
        body.dataset.transaction = record.id;
        for (const oldBody of Array.from(table.tBodies)) {
            let remaining = 0;
            for (const row of Array.from(oldBody.rows)) {
                if (!remaining && !row.textContent.trim()) continue; // Template padding, not a measurement.
                remaining = Math.max(remaining - 1, ...Array.from(row.cells, c => c.rowSpan - 1));
                body.append(row);
            }
            oldBody.remove();
        }
        if (!body.rows.length) throw new Error('Hasil transaksi ' + record.id + ' kosong.');
        table.dataset.dailyResults = '';
        const fields = {};

        function field(el, key, label) {
            if (!el) return;
            if (!label && /^\s*:/.test(el.textContent)) el.dataset.dailyPrefix = ': ';
            fields[key] = el.textContent.replace(label || /^\s*:\s*/, '').trim();
            el.dataset.dailyField = key;
            el.replaceChildren();
        }
        // Collect these header values across tools; environment and approvals
        // remain part of the grouping key.
        for (const cell of report.querySelectorAll('td,th')) {
            if (cell.querySelector('table') || cell.closest('[data-daily-results]')) continue;
            const label = cell.textContent.trim().replace(/[.:]/g, '').replace(/\s+/g, ' ').toUpperCase();
            if (label === 'NO LAPORAN') field(cell.nextElementSibling, 'report');
            if (label === 'NAMA ALAT') field(cell.nextElementSibling, 'name');
            if (label === 'METODE') field(cell.nextElementSibling, 'method');
            if (label === 'ALAT STANDAR' || label === 'STANDAR UJI / NO ALAT' || label === 'ALAT VERIFIKASI')
                field(cell.nextElementSibling, 'standard');
            if (label === 'TRACEABILITY') field(cell.nextElementSibling, 'traceability');
            if (label === 'KETERANGAN') field(cell.nextElementSibling, 'note');
        }
        const note = report.querySelector('.excel-note,.ket-h,.ket-i,.ket-j,.ket-o,.ket-p,.ket-q,.ket-t');
        // H, I and J put the note inside a table. Keep that table and its cell
        // intact; replacing the table's children breaks the printed layout.
        if (note && !fields.note) {
            const noteCell = note.matches('table') ? note.querySelector('td') : note;
            field(noteCell, 'note', /^\s*KETERANGAN\s*:\s*/i);
            if (noteCell) noteCell.dataset.dailyNoteLabel = '1';
        }
        // Ignore only the emptied per-tool identity fields and result rows; other
        // metadata and approvals must match for reports to be combined.
        const key = record.payload.format + '|' + record.status + '|' + report.outerHTML;
        return {
            ...rendered,
            key,
            body,
            fields,
            record
        };
    }

    function fillFields(report, entries, combinedNumber, combinedName, headerFields) {
        for (const el of report.querySelectorAll('[data-daily-field]')) {
            const key = el.dataset.dailyField;
            // List only the tools printed on this page, so a long selection
            // cannot overflow every page's header.
            const values = key === 'report' ? [combinedNumber]
                : key === 'name' ? [combinedName]
                : ['method', 'standard', 'traceability'].includes(key) ? [headerFields[key] || '']
                : Array.from(new Set(entries.map(e => e.fields[key]).filter(Boolean)));
            el.replaceChildren();
            if (el.dataset.dailyPrefix) el.append(document.createTextNode(el.dataset.dailyPrefix));
            if (key === 'note' && el.dataset.dailyNoteLabel) el.append(node('b', 'KETERANGAN :'), node('br'));
            // Show each report number once, even when multiple tools share it.
            values.forEach((value, i) => {
                if (i) el.append(node('br'));
                el.append(document.createTextNode(value));
            });
        }
    }

    function buildDocument(groups, styles) {
        const frame = node('iframe', undefined, 'daily-print-frame');
        frame.title = 'Pratinjau format laporan kalibrasi';
        output.append(frame);
        const doc = frame.contentDocument;
        doc.open();
        doc.write('<!doctype html><html lang="id"><head><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="default-src \'none\'; style-src \'unsafe-inline\'"><title>Laporan Kalibrasi Multi-Alat</title></head><body></body></html>');
        doc.close();
        const css = doc.createElement('style');
        css.textContent = styles + `
            @page portraitA4 { size:A4 portrait; margin:0; }
            @page landscapeA4 { size:A4 landscape; margin:0; }
            html, body { margin:0!important; padding:0!important; }
            body { background:#e9ecef!important; }
            .daily-sheet { width:210mm; margin:0 auto 20px; background:white; display:flow-root; box-shadow:0 2px 8px #0002; }
            .daily-sheet.landscape { width:297mm; }
            .daily-sheet.landscape > .report { width:293mm!important; min-height:0!important; margin:2mm auto!important; }
            .daily-sheet.portrait > .report { width:206mm!important; max-width:none!important; min-height:0!important; margin:2mm auto!important; }
            .daily-sheet.landscape.is-complete > .report { min-height:206mm!important; }
            .daily-sheet.portrait.is-complete > .report { min-height:293mm!important; }
            .daily-tool-name { display:block; margin-top:1mm; font-size:.85em; font-weight:normal; overflow-wrap:anywhere; }
            [data-daily-results] tbody { break-inside:avoid; }
            @media print {
                body { background:white!important; }
                .daily-sheet { margin:0; box-shadow:none; break-after:page; page:portraitA4; }
                .daily-sheet.landscape { page:landscapeA4; }
                .daily-sheet:last-child { break-after:auto; }
            }
        `;
        doc.head.append(css);
        let combinedSequence = 0;
        for (const group of groups.values()) {
            const combinedNumber = combinedReportNumber(group, group.entries.length > 1 ? ++combinedSequence : 0);
            const combinedName = combinedToolName(group);
            const headerFields = group.entries[0].fields;
            let current;
            const pages = [];

            function newPage() {
                const sheet = doc.createElement('section');
                sheet.className = 'daily-sheet ' + (group.landscape ? 'landscape' : 'portrait');
                const report = group.report.cloneNode(true);
                sheet.append(report);
                doc.body.append(sheet);
                current = {
                    sheet,
                    report,
                    entries: []
                };
                pages.push(current);
            }
            newPage();
            for (const entry of group.entries) {
                showToolNameInResult(entry);
                const body = entry.body;
                current.entries.push(entry);
                current.report.querySelector('[data-daily-results]').append(body);
                fillFields(current.report, current.entries, combinedNumber, combinedName, headerFields);
                // Keep each transaction's rowspans together and repeat the original
                // full header/footer on continuation pages.
                const maxHeight = (group.landscape ? 210 : 297) * 96 / 25.4;
                if (current.entries.length > 1 && current.sheet.getBoundingClientRect().height > maxHeight - 8) {
                    current.entries.pop();
                    body.remove();
                    fillFields(current.report, current.entries, combinedNumber, combinedName, headerFields);
                    newPage();
                    current.entries.push(entry);
                    current.report.querySelector('[data-daily-results]').append(body);
                    fillFields(current.report, current.entries, combinedNumber, combinedName, headerFields);
                }
            }
            pages.forEach((page, index) => {
                page.sheet.classList.add('is-complete');
                const walker = doc.createTreeWalker(page.report, NodeFilter.SHOW_TEXT);
                while (walker.nextNode()) {
                    const text = walker.currentNode;
                    if (/HAL\s*:\s*1\s*\/\s*1/i.test(text.textContent)) text.textContent = text.textContent.replace(/HAL\s*:\s*1\s*\/\s*1/i, 'HAL : ' + (index + 1) + ' / ' + pages.length);
                }
            });
        }
        return frame;
    }
    preview.addEventListener('click', async () => {
        invalidate();
        const run = generation;
        const selected = new Set(choices.filter(c => c.checked).map(c => Number(c.value)));
        const records = source.records.filter(r => selected.has(r.id));
        if (!records.length) {
            progress.textContent = 'Pilih minimal satu transaksi.';
            return;
        }
        preview.disabled = true;
        progress.classList.remove('daily-error');
        const groups = new Map();
        let styles = '';
        try {
            for (let i = 0; i < records.length; i++) {
                const record = records[i];
                progress.textContent = 'Memuat ' + (i + 1) + ' / ' + records.length + ' transaksi...';
                const entry = prepare(await load(record), record);
                if (run !== generation) return;
                styles = entry.styles;
                let group = groups.get(entry.key);
                if (!group) {
                    group = {
                        ...entry,
                        entries: [],
                        number: 0
                    };
                    groups.set(entry.key, group);
                }
                let remaining = 0;
                for (const row of entry.body.rows) {
                    if (remaining > 0) remaining--;
                    else if (row.cells[0] && /^\d+$/.test(row.cells[0].textContent.trim())) {
                        row.cells[0].textContent = String(++group.number);
                        remaining = row.cells[0].rowSpan - 1;
                    }
                }
                group.entries.push(entry);
            }
            if (run !== generation) return;
            buildDocument(groups, styles);
            print.disabled = false;
            progress.textContent = records.length + ' transaksi ditampilkan dengan format laporan asli.';
        } catch (err) {
            if (run === generation) {
                output.replaceChildren();
                progress.textContent = err.message;
                progress.classList.add('daily-error');
            }
        } finally {
            preview.disabled = false;
        }
    });
    invalidate();
})();

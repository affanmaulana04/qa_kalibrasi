<?php
// Small OOXML adapter using PHP's bundled Phar, SimpleXML and zlib extensions.
// No extraction, external relationships, formulas, macros or entity expansion.
const QA_XLSX_XML_LIMIT = 33554432;
const QA_XLSX_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

function qaXlsxXml(string $xml): SimpleXMLElement {
    if (stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) {
        throw new RuntimeException('XML Excel mengandung deklarasi yang tidak diizinkan.');
    }
    $previous = libxml_use_internal_errors(true);
    try {
        $document = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
        if ($document === false) throw new RuntimeException('Struktur XML Excel tidak valid.');
        return $document;
    } finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
}
function qaXlsxTemp(): string {
    $base = tempnam(sys_get_temp_dir(), 'qa-xlsx-');
    if ($base === false) throw new RuntimeException('File sementara Excel tidak dapat dibuat.');
    $path = $base.'.zip';
    if (!rename($base, $path)) { unlink($base); throw new RuntimeException('File sementara Excel tidak tersedia.'); }
    return $path;
}
function qaXlsxColumn(int $index): string {
    $name = '';
    for ($index++; $index > 0; $index = intdiv($index - 1, 26)) $name = chr(65 + ($index - 1) % 26).$name;
    return $name;
}
function qaXlsxEscape(string $value): string {
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}
function qaXlsxWrite(array $sheets, array $validations = [], array $sheetOptions = []): string {
    $path = qaXlsxTemp();
    unlink($path); // Phar creates a new ZIP, not an empty file.
    try {
        $zip = new PharData($path, 0, null, Phar::ZIP);
        $types = '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $workbook = '<workbook xmlns="'.QA_XLSX_NS.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        $relations = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $i = 0; $hasWrap=false; $definedNames='';
        foreach ($validations as $rules) foreach ($rules as $rule) {
            if (!empty($rule['definedName']) && !empty($rule['sourceRange'])) {
                $definedNames.='<definedName name="'.qaXlsxEscape($rule['definedName']).'">'.qaXlsxEscape($rule['sourceRange']).'</definedName>';
            }
        }
        foreach ($sheets as $name => $rows) {
            $i++;
            $workbook .= '<sheet name="'.qaXlsxEscape($name).'" sheetId="'.$i.'" r:id="rId'.$i.'"/>';
            $relations .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
            $types .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $cols = count($rows[0]);
            $options=$sheetOptions[$name]??[]; $wrap=!empty($options['wrap']); $hasWrap=$hasWrap||$wrap;
            $frozen=max(0,min($cols-1,(int)($options['freezeColumns']??0)));
            $widths=$options['widths']??[]; $exampleRows=$options['exampleRows']??[];
            $xml = '<worksheet xmlns="'.QA_XLSX_NS.'"><sheetViews><sheetView workbookViewId="0"><pane '.($frozen?'xSplit="'.$frozen.'" ':'').'ySplit="1" topLeftCell="'.qaXlsxColumn($frozen).'2" activePane="'.($frozen?'bottomRight':'bottomLeft').'" state="frozen"/></sheetView></sheetViews><cols>';
            for ($col=0;$col<$cols;$col++) $xml.='<col min="'.($col+1).'" max="'.($col+1).'" width="'.(float)($widths[$col]??24).'" customWidth="1" style="1"/>';
            $xml.='</cols><sheetData>';
            foreach ($rows as $r => $cells) {
                $height='';
                if ($wrap) {
                    $lines=1;
                    foreach (array_values($cells) as $c=>$value) $lines=max($lines,(int)ceil(mb_strlen((string)$value,'UTF-8')/max(1,($widths[$c]??24)-2)));
                    $height=' ht="'.min(409,max(30,15*$lines+12)).'" customHeight="1"';
                }
                $xml .= '<row r="'.($r+1).'"'.$height.'>';
                foreach (array_values($cells) as $c => $value) {
                    $style=$r===0?2:(in_array($r+1,$exampleRows,true)?4:($wrap?3:1));
                    $xml .= '<c r="'.qaXlsxColumn($c).($r+1).'" t="inlineStr" s="'.$style.'"><is><t xml:space="preserve">'.qaXlsxEscape((string)$value).'</t></is></c>';
                }
                $xml .= '</row>';
            }
            $xml .= '</sheetData><autoFilter ref="A1:'.qaXlsxColumn($cols-1).count($rows).'"/>';
            $rules=$validations[$name]??[];
            if ($rules) {
                $xml.='<dataValidations count="'.count($rules).'">';
                foreach ($rules as $rule) {
                    $formula=$rule['formula']??('"'.implode(',',$rule['values']??[]).'"');
                    $xml.='<dataValidation type="list" allowBlank="1" showErrorMessage="1" showInputMessage="1" errorTitle="Format tidak valid" error="Pilih format yang tersedia pada daftar." promptTitle="Format laporan" prompt="'.qaXlsxEscape($rule['prompt']).'" sqref="'.qaXlsxEscape($rule['range']).'"><formula1>'.qaXlsxEscape($formula).'</formula1></dataValidation>';
                }
                $xml.='</dataValidations>';
            }
            $xml.='</worksheet>';
            $zip['xl/worksheets/sheet'.$i.'.xml'] = $xml;
        }
        $zip['[Content_Types].xml'] = $types.'</Types>';
        $zip['_rels/.rels'] = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        $zip['xl/workbook.xml'] = $workbook.'</sheets>'.($definedNames?'<definedNames>'.$definedNames.'</definedNames>':'').'</workbook>';
        $zip['xl/_rels/workbook.xml.rels'] = $relations.'<Relationship Id="styles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        $zip['xl/styles.xml'] = '<styleSheet xmlns="'.QA_XLSX_NS.'"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font></fonts><fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1E3F66"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFF1C7"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="5"><xf numFmtId="0"/><xf numFmtId="49" applyNumberFormat="1"/><xf fontId="1" fillId="2" applyFont="1" applyFill="1"><alignment wrapText="1"/></xf><xf numFmtId="49" applyNumberFormat="1"><alignment wrapText="1" vertical="top"/></xf><xf numFmtId="49" fillId="3" applyNumberFormat="1" applyFill="1"><alignment wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
        $zip->compressFiles(Phar::GZ);
        unset($zip);
        return file_get_contents($path);
    } finally { if (is_file($path)) unlink($path); }
}
function qaXlsxRead(string $source): array {
    if (!is_file($source) || filesize($source) > QA_CSV_MAX_BYTES) throw new RuntimeException('Ukuran Excel maksimal 2 MB.');
    $path = qaXlsxTemp();
    try {
        if (!copy($source, $path)) throw new RuntimeException('Excel tidak dapat dibaca.');
        try { $zip = new PharData($path); }
        catch (Throwable $e) { throw new RuntimeException('File bukan Excel .xlsx yang valid. Simpan ulang sebagai Excel Workbook (.xlsx).'); }
        if (!$zip->isFileFormat(Phar::ZIP)) throw new RuntimeException('File harus berupa Excel Workbook (.xlsx).');
        $total = 0; $entries = 0;
        foreach (new RecursiveIteratorIterator($zip) as $entry) {
            $total += $entry->getSize();
            if (++$entries > 512 || $total > QA_XLSX_XML_LIMIT) throw new RuntimeException('Isi Excel terlalu besar setelah dibuka. Maksimal 32 MB / 512 bagian.');
        }
        $read = static function($name) use ($zip) {
            if (!isset($zip[$name])) throw new RuntimeException('Bagian Excel tidak ditemukan: '.$name.'.');
            return qaXlsxXml($zip[$name]->getContent());
        };
        $book = $read('xl/workbook.xml');
        $date1904 = in_array((string)$book->workbookPr['date1904'], ['1','true'], true);
        $relationships = [];
        foreach ($read('xl/_rels/workbook.xml.rels')->Relationship as $rel) {
            if ((string)$rel['TargetMode'] === 'External') continue;
            $target = (string)$rel['Target'];
            if (strpos($target, '..') !== false || strpos($target, '\\') !== false) throw new RuntimeException('Relasi Excel tidak valid.');
            $relationships[(string)$rel['Id']] = strpos($target, '/') === 0 ? ltrim($target, '/') : 'xl/'.$target;
        }
        $shared = [];
        if (isset($zip['xl/sharedStrings.xml'])) {
            foreach ($read('xl/sharedStrings.xml')->si as $si) {
                $parts = $si->xpath('.//*[local-name()="t"]');
                $shared[] = implode('', array_map('strval', $parts));
            }
        }
        $zeroStyles = []; $exampleStyles = [];
        if (isset($zip['xl/styles.xml'])) {
            $styles = $read('xl/styles.xml'); $formats = [];
            foreach ($styles->numFmts->numFmt ?? [] as $fmt) $formats[(string)$fmt['numFmtId']] = (string)$fmt['formatCode'];
            $exampleFills = []; $fillIndex = 0;
            foreach ($styles->fills->fill ?? [] as $fill) {
                if (strtoupper((string)$fill->patternFill->fgColor['rgb']) === 'FFFFF1C7') $exampleFills[$fillIndex] = true;
                $fillIndex++;
            }
            $index = 0;
            foreach ($styles->cellXfs->xf ?? [] as $xf) {
                $code = $formats[(string)$xf['numFmtId']] ?? '';
                if (preg_match('/^0{2,50}$/', $code)) $zeroStyles[$index] = strlen($code);
                if (isset($exampleFills[(int)$xf['fillId']])) $exampleStyles[$index] = true;
                $index++;
            }
        }
        $result = [];
        foreach ($book->sheets->sheet as $sheet) {
            $name = (string)$sheet['name'];
            if (isset($result[$name])) throw new RuntimeException('Nama sheet berulang: '.$name.'.');
            $id = (string)$sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            if (!isset($relationships[$id])) throw new RuntimeException('Sheet Excel tidak tersedia: '.$name.'.');
            $xml = $read($relationships[$id]); $rows = []; $seenRows = []; $last = 0;
            foreach ($xml->sheetData->row as $row) {
                $line = (int)$row['r'];
                if ($line < 1 || $line > 100000 || isset($seenRows[$line]) || $line <= $last) throw new RuntimeException('Urutan baris Excel tidak valid.');
                $last = $line; $seenRows[$line] = true; $cells = []; $numeric = []; $example = false;
                foreach ($row->c as $cell) {
                    $ref = (string)$cell['r'];
                    if (!preg_match('/^([A-Z]{1,3})([1-9][0-9]*)$/', $ref, $m) || (int)$m[2] !== $line) throw new RuntimeException('Alamat sel Excel tidak valid.');
                    $col = 0;
                    for ($j=0;$j<strlen($m[1]);$j++) $col=$col*26+ord($m[1][$j])-64;
                    $col--;
                    if ($col > 100 || isset($cells[$col])) throw new RuntimeException('Kolom Excel berulang atau melebihi batas 101 kolom.');
                    if (isset($cell->f)) throw new RuntimeException($name.'!'.$ref.': rumus tidak didukung. Salin dan tempel sebagai nilai.');
                    if ($col === 0 && isset($exampleStyles[(int)$cell['s']])) $example = true;
                    $type = (string)$cell['t'];
                    if ($type === 'e') throw new RuntimeException($name.'!'.$ref.': sel berisi error Excel.');
                    if ($type === 's') {
                        $key = (string)$cell->v;
                        if (!ctype_digit($key) || !array_key_exists((int)$key, $shared)) throw new RuntimeException('Teks bersama Excel tidak valid.');
                        $value = $shared[(int)$key];
                    } elseif ($type === 'inlineStr') {
                        $parts = $cell->is->xpath('.//*[local-name()="t"]');
                        $value = implode('', array_map('strval', $parts));
                    } else {
                        $value = (string)$cell->v;
                        if ($type === '' || $type === 'n') {
                            $numeric[$col] = true;
                            $width = $zeroStyles[(int)$cell['s']] ?? 0;
                            if ($width && ctype_digit($value)) $value = str_pad($value, $width, '0', STR_PAD_LEFT);
                        }
                    }
                    if (strlen($value)>65535 || !preg_match('//u',$value) || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$value)) throw new RuntimeException($name.'!'.$ref.': teks terlalu panjang atau mengandung karakter tidak valid.');
                    $cells[$col] = trim($value);
                }
                if (!array_filter($cells, static function($v){return $v!=='';})) continue;
                $rows[] = ['line'=>$line, 'cells'=>$cells, 'numeric'=>$numeric, 'example'=>$example];
                if (count($rows)>($name==='Data Alat'?1001:20001)) throw new RuntimeException('Maksimal 1.000 alat dan 20.000 baris titik standar.');
            }
            $result[$name] = $rows;
        }
        return ['sheets'=>$result, 'date1904'=>$date1904];
    } finally { unset($zip); if (is_file($path)) unlink($path); }
}

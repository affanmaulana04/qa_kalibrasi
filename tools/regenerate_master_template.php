<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../inc/qa_master_workbook.php';
$target=__DIR__.'/../templates/template_master_alat.xlsx';
if (file_put_contents($target,qaMasterWorkbookTemplate())===false) {
    fwrite(STDERR,"Template gagal dibuat.\n");
    exit(1);
}
echo "Template dibuat: {$target}\n";

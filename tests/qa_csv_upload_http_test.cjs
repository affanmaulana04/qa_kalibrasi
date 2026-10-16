// Run: node tests/qa_csv_upload_http_test.cjs
// Starts a private PHP server; all database writes use connection-local temporary tables.
const fs = require('fs');
const os = require('os');
const path = require('path');
const cp = require('child_process');
const net = require('net');
const root = path.resolve(__dirname, '..');
const php = process.env.QA_TEST_PHP || 'C:/xampp/php/php.exe';
const phpString = value => "'" + value.replaceAll('\\', '/').replaceAll("'", "\\'") + "'";
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
(async () => {
    const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'qa-direct-upload-'));
    const log = path.join(dir, 'writes.jsonl');
    const router = path.join(dir, 'router.php');
    fs.writeFileSync(router, `<?php
if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) !== '/modul/tabel/upload_master_csv.php') return false;
require ${phpString(path.join(root, 'inc/inc_koneksi.php'))};
require ${phpString(path.join(root, 'inc/qa_master_csv.php'))};
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$konek->set_charset('utf8mb4');
foreach (['qa_part','qa_alat_registry','qa_seksi_master','qa_pengecekan','qa_alat_ng','qa_alat_rusak','qa_alat_discontinue','qa_alat_ditarik'] as $table) {
    $ddl=$konek->query("SHOW CREATE TABLE \`$table\`")->fetch_row()[1];
    $konek->query(preg_replace('/^CREATE TABLE/','CREATE TEMPORARY TABLE',$ddl));
}
qaCsvWrite($konek,'INSERT INTO qa_seksi_master (nama_seksi) VALUES (?)',['QA']);
if (!isset($_GET['empty'])) {
    qaCsvWrite($konek,"INSERT INTO qa_alat_registry (no_part,status_alat,sumber_tabel) VALUES (?,'AKTIF','qa_part')",['EXIST001']);
    $registryId=$konek->insert_id;
    qaCsvWrite($konek,'INSERT INTO qa_part (no_part,nama_part,seksi_pemilik,periode_kalibrasi,jadwal_kalibrasi,merk,alat_registry_id) VALUES (?,?,?,?,?,?,?)',['EXIST001','Old name','QA','180','2026-10-31','Keep brand',$registryId]);
    qaCsvWrite($konek,'UPDATE qa_alat_registry SET sumber_id=? WHERE id=?',[$konek->insert_id,$registryId]);
}
if (isset($_GET['fail'])) $konek->query('ALTER TABLE qa_part ADD UNIQUE KEY test_unique_name (nama_part)');
if ($_SERVER['REQUEST_METHOD']==='POST') register_shutdown_function(function()use($konek){
    $state=['master'=>qaCsvFetch($konek,'SELECT no_part,nama_part,merk FROM qa_part ORDER BY no_part'), 'registry'=>qaCsvFetch($konek,'SELECT no_part FROM qa_alat_registry ORDER BY no_part')];
    file_put_contents(${phpString(log)},json_encode($state).PHP_EOL,FILE_APPEND);
});
require ${phpString(path.join(root, 'modul/tabel/upload_master_csv.php'))};
return true;
`);
    const session = path.join(dir, 'session.php');
    fs.writeFileSync(session, "<?php session_save_path($argv[1]);session_id($argv[2]);session_start();$_SESSION=['login'=>1,'userid'=>'test-admin','admin'=>$argv[3],'role'=>'user'];session_write_close();");
    for (const [id, admin] of [['directadmin', 'Y'], ['directuser', 'N']]) cp.execFileSync(php, [session, dir, id, admin], { windowsHide: true });
    const port = await new Promise(resolve => { const s=net.createServer();s.listen(0,'127.0.0.1',()=>{const p=s.address().port;s.close(()=>resolve(p));}); });
    const server = cp.spawn(php, ['-d', 'session.save_path='+dir, '-S', '127.0.0.1:'+port, '-t', root, router], { windowsHide: true, stdio: 'ignore' });
    const url = 'http://127.0.0.1:'+port+'/modul/tabel/upload_master_csv.php';
    let count=0;
    function check(ok, message) { if (!ok) throw Error(message); count++; console.log('PASS: '+message); }
    const request = (options={},suffix='',id='directadmin') => fetch(url+suffix,{redirect:'manual',...options,headers:{Cookie:'PHPSESSID='+id}});
    const state = () => JSON.parse(fs.readFileSync(log,'utf8').trim().split('\n').pop());
    const tokenFrom = html => html.match(/name="csrf" value="([^"]+)"/)[1];
    const header = 'no_part,nama_part,seksi_pemilik,periode_kalibrasi,jadwal_kalibrasi';
    function upload(token, csv, suffix='') {
        const body=new FormData();body.append('csrf',token);body.append('action','upload');body.append('csv_file',new Blob([csv],{type:'text/csv'}),'tools.csv');
        return request({method:'POST',body},suffix);
    }
    try {
        for(let i=0;i<50;i++){try{await request();break;}catch{await pause(100);}}
        check((await request({},'','')).status===302,'login required');
        check((await request({},'','directuser')).status===403,'admin access required');
        let html=await(await request()).text(),token=tokenFrom(html);
        check(html.includes('Upload &amp; Simpan')&&!html.includes('Periksa &amp; Pratinjau'),'single upload button replaces preview');
        const templateResponse=await request({},'?template=1');
        const template=await templateResponse.text();
        const templateLines=template.trim().split(/\r?\n/);
        check(templateResponse.headers.get('content-type').includes('text/csv') && templateLines.length===4 && templateLines[0].endsWith(',SEKSI') && template.includes('CONTOH-001') && template.includes('CONTOH-003') && !template.includes('SEKSI:'),'download has section as last column and three example tools');
        let response=await upload(token,header+'\nGOOD1,Valid,QA,180,2026-12-01\nBAD1,<script>alert(1)</script>,UNKNOWN,0,2026-02-29');
        check(response.status===302&&response.headers.get('location').includes('#csvErrors'),'invalid rows redirect to error details');
        check(state().master.length===1&&state().master[0].nama_part==='Old name','mixed invalid batch writes nothing');
        html=await(await request()).text();
        check(html.includes('Tidak ada data dari file ini yang disimpan')&&html.includes('&lt;script&gt;')&&!html.includes('<script>alert(1)</script>'),'errors explain failure and escape CSV text');
        check(!html.includes('name="preview_id"')&&!html.includes('value="save"'),'no separate save step remains');
        const valid=header+'\nEXIST001,Updated name,QA,365,2026-12-01\nNEW001,New tool,QA,180,2026-12-01';
        response=await upload(token,valid);
        const saved=state();
        check(response.status===302&&saved.master.length===2&&saved.registry.length===2,'one upload saves new and existing tools');
        check(saved.master.find(r=>r.no_part==='EXIST001').nama_part==='Updated name'&&saved.master.find(r=>r.no_part==='EXIST001').merk==='Keep brand','update preserves omitted optional values');
        html=await(await request()).text();
        check(html.includes('Import selesai: <b>1</b> alat ditambahkan, <b>1</b> diperbarui')&&!html.includes('id="csvErrors"'),'success totals displayed and previous errors cleared');
        check((await upload(token,valid)).status===403,'replayed successful form is rejected');
        token=tokenFrom(html);
        response=await upload(token,'NAMA ALAT,SPESIFIKASI,NO. ALAT,RESOLUSI,PERIODE KALIBRASI (HARI),RENCANA KALIBRASI,SEKSI\nGauge,SK-571,00012,~,1 Tahun,16 - 09 - 2026,QA','?empty=1');
        check(response.status===302&&state().master.length===1&&state().master[0].no_part==='00012','section template imports directly into empty master');
        html=await(await request()).text();token=tokenFrom(html);
        response=await upload(token,header+'\nROLL1,Same name,QA,1,2026-12-01\nROLL2,Same name,QA,1,2026-12-01','?fail=1');
        check((await response.text()).includes('Tidak ada perubahan dari batch ini yang disimpan')&&state().master.length===1&&state().registry.length===1,'database error rolls back every write');
        response=await request({method:'POST',body:new URLSearchParams({csrf:token,action:'save',preview_id:'legacy'})});
        check((await response.text()).includes('Aksi formulir tidak dikenal'),'old staged save action cannot execute');
        console.log(count+' HTTP checks passed; only temporary tables were written.');
    } finally { server.kill(); }
})().catch(error => { console.error(error);process.exitCode=1; });

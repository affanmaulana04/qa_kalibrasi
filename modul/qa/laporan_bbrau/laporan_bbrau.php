<?php
// LAPORAN BBRAU V11 - DEDUPE REGISTRASI: REGISTRASI HANYA 1X DI RIWAYAT + TABEL UTAMA
session_start();
if (empty($_SESSION['login'])) { header('location: ../../../logout.php'); exit(); }
require_once __DIR__ . '/../../../inc/inc_koneksi.php';
require_once __DIR__ . '/../../../inc/qa_rbac.php';
qaRbacRequire($konek,'laporan_bbrau');

function h($v){ return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function dmy($v){ if(!$v||$v==='0000-00-00')return ''; $t=strtotime($v); return $t?date('d-m-Y',$t):$v; }
function normStatus($v){ $s=strtoupper(trim((string)$v)); if(in_array($s,['GOOD','OK']))return 'OK'; if(in_array($s,['BAD','NOT GOOD','NG']))return 'NG'; return $s; }
function firstNonEmpty($row,$keys,$def=''){ foreach($keys as $k){ if(isset($row[$k]) && trim((string)$row[$k])!=='') return $row[$k]; } return $def; }

$no=trim($_GET['no_alat']??''); $alat=null; $alatTable=''; $hist=[]; $riwayat=[]; $err=''; $tglBeli=''; $statusAlat='';
if($no!==''){
  $noEsc=mysqli_real_escape_string($konek,strtoupper($no));
  $tables=['qa_part','qa_alat_ng','qa_alat_rusak','qa_alat_discontinue','qa_alat_ditarik'];
  foreach($tables as $tb){
    $q=@mysqli_query($konek,"SELECT * FROM `$tb` WHERE UPPER(TRIM(no_part))='$noEsc' ORDER BY id DESC LIMIT 1");
    if($q && ($r=mysqli_fetch_assoc($q))){ $alat=$r; $alatTable=$tb; break; }
  }
  if(!$alat){ $err='Nomor alat tidak ditemukan.'; }
  else {
    $regId=(int)($alat['alat_registry_id']??0);
    $where=$regId>0 ? "(q.alat_registry_id='$regId' OR UPPER(TRIM(q.no_part))='$noEsc')" : "UPPER(TRIM(q.no_part))='$noEsc'";
    $sql="SELECT q.*,
      (SELECT v.nama_validator FROM qa_validasi v WHERE v.pengecekan_id=q.id AND v.tahap='pemeriksa' AND v.keputusan='setuju' ORDER BY v.id DESC LIMIT 1) AS nama_pemeriksa
      FROM qa_pengecekan q WHERE $where ORDER BY q.tanggal_pengecekan ASC,q.id ASC";
    $qh=@mysqli_query($konek,$sql); if($qh){ while($r=mysqli_fetch_assoc($qh)) $hist[]=$r; }
    // BBRAU V11: data legacy kadang punya lebih dari satu row registrasi untuk No. Alat/registry yang sama.
    // BBRAU hanya boleh menampilkan registrasi awal satu kali; kalibrasi/kejadian lain tetap dipertahankan.
    $histBersih=[]; $registrasiSudahAda=false;
    foreach($hist as $r){
      $isReg=(strtolower(trim((string)($r['jenis_transaksi']??'')))==='registrasi_alat');
      if($isReg){ if($registrasiSudahAda) continue; $registrasiSudahAda=true; }
      $histBersih[]=$r;
    }
    $hist=$histBersih;
    foreach($hist as $r){ if(($r['jenis_transaksi']??'')==='registrasi_alat'){ $tglBeli=$r['tanggal_pengecekan']??''; break; } }
    if($tglBeli==='') $tglBeli=substr((string)($alat['created_at']??''),0,10);
    foreach($hist as $r){
      $st=normStatus($r['status_kalibrasi']??'');
      $isReg=(($r['jenis_transaksi']??'')==='registrasi_alat');
      $isFinal=(strtolower((string)($r['status_validasi']??''))==='final_disetujui');
      if($isReg || ($isFinal && $st!=='OK')) $riwayat[]=$r;
    }
    $statusMap=['qa_alat_ng'=>'NOT GOOD','qa_alat_rusak'=>'RUSAK','qa_alat_discontinue'=>'DISCONTINUE','qa_alat_ditarik'=>'DITARIK TECHNICAL'];
    $statusAlat=$statusMap[$alatTable]??'';
    if($statusAlat===''){
      $qr=@mysqli_query($konek,"SELECT status_alat,alasan_status,hasil_terakhir FROM qa_alat_registry WHERE UPPER(TRIM(no_part))='$noEsc' LIMIT 1");
      if($qr && ($rg=mysqli_fetch_assoc($qr))){
        $rs=strtoupper(trim((string)($rg['status_alat']??'')));
        if($rs==='BLOKIR_PERMANEN'||$rs==='NONAKTIF_SEMENTARA') $statusAlat=strtoupper(trim((string)($rg['alasan_status']?:$rg['hasil_terakhir'])));
      }
    }
    if($statusAlat==='') $statusAlat='Sudah Kalibrasi';
  }
}

// Circular 26 slot: event ke-27 menimpa slot 1, ke-28 slot 2, dst.
// LAPORAN BBRAU V10 - RING BUFFER: RIWAYAT 8 SLOT + HISTORI UTAMA 26 SLOT
$miniSlots=array_fill(1,8,null); foreach($riwayat as $i=>$r){ $slot=($i%8)+1; $miniSlots[$slot]=$r; }
$slots=array_fill(1,26,null); foreach($hist as $i=>$r){ $slot=($i%26)+1; $slots[$slot]=$r; }
$nama=$alat?firstNonEmpty($alat,['nama_part','nama_alat']):'';
$spec=$alat?firstNonEmpty($alat,['spesifikasi','ukuran']):'';
$seksi=$alat?firstNonEmpty($alat,['seksi_pemilik','seksi'],'QA-LABORAT'):'';
$merk=$alat?firstNonEmpty($alat,['merk']):''; $buatan=$alat?firstNonEmpty($alat,['buatan']):'';
$fungsi=$alat?firstNonEmpty($alat,['fungsi']):''; $noAsset=$alat?firstNonEmpty($alat,['no_fa','no_asset']):'';
$type=$alat?firstNonEmpty($alat,['type','tipe']):'';
?>
<!doctype html><html lang="id"><head><link rel="icon" type="image/png" href="/qa_kalibrasi/images/favicon-toto-qa.png?v=1"><meta charset="utf-8"><title>Laporan BBRAU</title>
<style>
*{box-sizing:border-box}body{font-family:"Times New Roman",serif;margin:0;background:#eef1f4;color:#000}.no-print{max-width:980px;margin:22px auto 14px;padding:0;background:#fff;border:1px solid #d7dce1;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.07);overflow:hidden;font-family:Arial,sans-serif}.toolbar-head{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #e3e6e9;background:#fafbfc}.toolbar-title{font-size:16px;font-weight:700;color:#263238}.toolbar-sub{font-size:12px;color:#6b747c;margin-top:3px}.backbtn{display:inline-flex;align-items:center;gap:6px;padding:8px 13px;border:1px solid #c8cdd2;border-radius:6px;background:#fff;color:#333;text-decoration:none;font-size:13px;font-weight:600}.backbtn:hover{background:#f2f3f5;color:#111;text-decoration:none}.search-wrap{padding:16px 18px}.search{display:flex;gap:8px}.search input{flex:1;padding:10px 12px;font:14px Arial;border:1px solid #bfc5ca;border-radius:6px;outline:none}.search input:focus{border-color:#6d7780;box-shadow:0 0 0 2px rgba(80,90,100,.08)}.search button,.printbtn{padding:9px 16px;border:1px solid #555;border-radius:6px;background:#fff;cursor:pointer;font:600 13px Arial;color:#222}.search button[type="submit"]{background:#343a40;color:#fff;border-color:#343a40}.search button:hover,.printbtn:hover{filter:brightness(.96)}.err{color:#a52828;margin-top:10px;padding:9px 11px;border:1px solid #e4c2c2;background:#fff7f7;border-radius:5px;font:13px Arial}.sheet{width:210mm;min-height:297mm;margin:8px auto;background:#fff;padding:3mm 2mm 2mm}.company{text-align:center;font:bold 9pt Arial;border-bottom:.35mm solid #000;height:5mm}.title{text-align:center;font:bold 14pt "Times New Roman";height:13mm;padding-top:3mm}.sub{text-align:center;font:bold 10pt Arial;height:6mm}.tbl{width:100%;border-collapse:collapse;table-layout:fixed}.tbl td,.tbl th{border:.25mm solid #000;padding:.6mm 1mm;font-size:8pt;vertical-align:middle}.meta td{height:7mm}.meta .lbl{font-size:7pt;width:17%}.meta .val{font-weight:normal}.meta .strong{font-weight:bold;text-align:center}.mini{margin-top:0}.mini th{font-weight:normal;text-align:center;height:5mm}.mini td{height:5mm}.mini .riwayat-vertical{padding:0;text-align:center;vertical-align:middle;font-weight:normal;width:4%;border-top:.25mm solid #000;border-bottom:.25mm solid #000;border-left:.25mm solid #000;border-right:.25mm solid #000!important}.riwayat-vertical .vertical-text{writing-mode:vertical-rl;transform:none;letter-spacing:1.4mm;font-size:7pt;display:inline-block}.mini .riwayat-data{border-left:.25mm solid #000!important}.main{margin-top:1mm}.main th{font-weight:normal;text-align:center;height:7mm;font-size:7pt}.main td{height:6mm;text-align:center;font-size:7pt}.left{text-align:left!important}.doc{text-align:right;font:7pt Arial;margin-top:1mm}.slot-no{width:4%}.c-tgl{width:12%}.c-jenis{width:16%}.c-hasil{width:10%}.c-pet{width:14%}.c-per{width:15%}.c-pel{width:13%}.c-ket{width:14%}
/* Ukur tinggi dengan aturan cetak SEBELUM Chrome mengganti media screen menjadi print. */
html.bbrau-print-measuring .sheet{width:210mm!important;min-height:0!important;height:296mm!important;margin:0!important;padding:2mm!important;overflow:hidden!important}
html.bbrau-print-measuring .company{height:4.5mm}
html.bbrau-print-measuring .title{height:10mm;padding-top:2mm}
html.bbrau-print-measuring .sub{height:5mm}
html.bbrau-print-measuring .meta td{height:5.8mm;padding:.4mm .65mm;font-size:7.5pt}
html.bbrau-print-measuring .mini th,html.bbrau-print-measuring .mini td{height:4.4mm;padding:.3mm .65mm;font-size:7pt;line-height:1.1}
html.bbrau-print-measuring .main th{height:5.4mm;padding:.3mm .6mm;font-size:6.7pt}
html.bbrau-print-measuring .main td{height:5.15mm;padding:.2mm .4mm;font-size:6.5pt;line-height:1.07}
/* Satu lembar A4 tanpa header/footer URL & tanggal dari margin browser. */
@page{size:A4 portrait;margin:0}
@media print{
  html,body{margin:0!important;padding:0!important;background:#fff!important}
  .no-print{display:none!important}
  .sheet{margin:0!important;width:210mm!important;min-height:0!important;
    height:296mm!important;padding:2mm!important;overflow:hidden!important;
    box-shadow:none!important;break-before:avoid!important;break-after:auto!important;
    page-break-before:avoid!important;page-break-after:auto!important}
  .sheet-content{transform-origin:top left;transform:scale(var(--bbrau-fit,1));width:var(--bbrau-width,100%)}
  .company{height:4.5mm}
  .title{height:10mm;padding-top:2mm}
  .sub{height:5mm}
  .meta td{height:5.8mm;padding:.4mm .65mm;font-size:7.5pt}
  .mini th,.mini td{height:4.4mm;padding:.3mm .65mm;font-size:7pt;line-height:1.1}
  .main th{height:5.4mm;padding:.3mm .6mm;font-size:6.7pt}
  .main td{height:5.15mm;padding:.2mm .4mm;font-size:6.5pt;line-height:1.07}
}
</style></head><body>
<div class="no-print">
 <div class="toolbar-head">
  <div><div class="toolbar-title">Laporan BBRAU</div><div class="toolbar-sub">Buku Besar Riwayat Alat Ukur</div></div>
  <a class="backbtn" href="../../../media.php">&#8592; Kembali ke Media</a>
 </div>
 <div class="search-wrap">
  <form class="search" method="get"><input name="no_alat" value="<?=h($no)?>" placeholder="Masukkan Nomor Alat" autofocus><button type="submit">Cari Alat</button><?php if($alat):?><button type="button" class="printbtn" onclick="window.print()">Print BBRAU</button><?php endif;?></form>
  <?php if($err):?><div class="err"><?=h($err)?></div><?php endif;?>
 </div>
</div>
<?php if($alat): ?>
<div class="sheet">
 <div class="sheet-content">
 <div class="company">PT. SURYA TOTO INDONESIA Tbk.</div>
 <div class="title">BUKU BESAR RIWAYAT ALAT UKUR</div>
 <div class="sub"><?=h(strtoupper($nama))?></div>
 <table class="tbl meta">
  <tr><td class="lbl" rowspan="2">NAMA</td><td class="val" rowspan="2"><?=h($nama)?></td><td class="lbl">SEKSI</td><td class="strong"><?=h($seksi)?></td></tr>
  <tr><td class="lbl">NO.ALAT</td><td class="val"><?=h($no)?></td></tr>
  <tr><td class="lbl">SPESIFIKASI</td><td class="val"><?=h($spec)?></td><td class="lbl">MERK</td><td class="strong"><?=h($merk)?></td></tr>
  <tr><td class="lbl">FUNGSI</td><td class="val"><?=h($fungsi)?></td><td class="lbl">BUATAN</td><td class="strong"><?=h($buatan)?></td></tr>
  <tr><td class="lbl">NO ASSET</td><td class="val"><?=h($noAsset?:'-')?></td><td class="lbl">DIBELI TGL</td><td class="val" style="text-align:center"><?=h(dmy($tglBeli))?></td></tr>
  <tr><td class="lbl">STATUS</td><td class="val"><?=h($statusAlat)?></td><td class="lbl">TYPE</td><td class="val" style="text-align:center"><?=h($type)?></td></tr>
 </table>
 <table class="tbl mini"><thead><tr><th style="width:4%"></th><th style="width:17%">TGL</th><th style="width:28%">SEKSI</th><th>KETERANGAN</th></tr></thead><tbody>
 <?php $maxMini=8; for($i=0;$i<$maxMini;$i++): $r=$miniSlots[$i+1]??null; ?><tr><?php if($i===0): ?><td class="riwayat-vertical" rowspan="8"><span class="vertical-text">RIWAYAT</span></td><?php endif; ?><td class="riwayat-data" style="text-align:center"><?=h($r?dmy($r['tanggal_pengecekan']??''):'')?></td><td><?=h($r?$seksi:'')?></td><td><?=h($r?(($r['jenis_transaksi']??'')==='registrasi_alat'?'~ Registrasi':('~ '.normStatus($r['status_kalibrasi']??''))):'')?></td></tr><?php endfor; ?>
 </tbody></table>
 <table class="tbl main"><thead><tr><th class="slot-no">NO</th><th class="c-tgl">TGL</th><th class="c-jenis">JENIS CHECK</th><th class="c-hasil">HASIL</th><th class="c-pet">PETUGAS</th><th class="c-per">DIPERIKSA</th><th class="c-pel">PELAKSANAAN</th><th class="c-ket">KETERANGAN</th></tr></thead><tbody>
 <?php for($i=1;$i<=26;$i++):$r=$slots[$i];?><tr><td><?= $i ?></td><td><?=h($r?dmy($r['tanggal_pengecekan']??''):'')?></td><td><?=h($r?(($r['jenis_transaksi']??'')==='registrasi_alat'?'Registrasi':'Kalibrasi'):'')?></td><td><?=h($r?normStatus($r['status_kalibrasi']??''):'')?></td><td><?=h($r?($r['operator']??$r['created_by']??''):'')?></td><td><?=h($r?($r['nama_pemeriksa']??''):'')?></td><td><?= $r?'INTERNAL':'' ?></td><td><?=h($r?($r['keterangan']??''):'')?></td></tr><?php endfor;?>
 </tbody></table>
 <div class="doc">STIS-CMFG4-01/R4</div>
 </div>
</div>
<?php endif; ?>
<script>
// Saat cetak, perkecil isi HANYA bila riwayat panjang melebihi satu halaman A4.
// Tidak memotong baris dan tidak mengubah tampilan layar atau data BBRAU.
(function(){
  var sheet=document.querySelector('.sheet');
  var inner=sheet && sheet.querySelector('.sheet-content');
  if(!inner)return;
  function fitPrint(){
    inner.style.setProperty('--bbrau-fit','1');
    inner.style.setProperty('--bbrau-width','100%');
    document.documentElement.classList.add('bbrau-print-measuring');
    var cs=getComputedStyle(sheet);
    var padding=parseFloat(cs.paddingTop||0)+parseFloat(cs.paddingBottom||0);
    var available=sheet.clientHeight-padding-8;
    var required=inner.scrollHeight;
    document.documentElement.classList.remove('bbrau-print-measuring');
    if(required>available && available>0){
      var scale=Math.min(1,available/required);
      inner.style.setProperty('--bbrau-fit',String(scale));
      inner.style.setProperty('--bbrau-width',(100/scale)+'%');
    }
  }
  window.addEventListener('beforeprint',fitPrint);
  window.addEventListener('afterprint',function(){
    inner.style.removeProperty('--bbrau-fit');
    inner.style.removeProperty('--bbrau-width');
  });
})();
</script>
</body></html>

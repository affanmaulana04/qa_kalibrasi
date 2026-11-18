<?php
session_start();
if (empty($_SESSION['login'])) { header('Location: ../../index.php'); exit; }
require_once __DIR__.'/../../inc/inc_koneksi.php';
require_once __DIR__.'/../../inc/qa_rbac.php';
require_once __DIR__.'/../../inc/qa_jadwal_validasi.php';
$canCreate=qaRbacCan($konek,'pengajuan_jadwal');
$canCheck=qaRbacCan($konek,'approval_pemeriksa');
$canApprove=qaRbacCan($konek,'approval_penyetuju');
$canView=qaRbacCanAny($konek,['jadwal_bulanan_internal','jadwal_bulanan_external','jadwal_harian']);
if (!$canCreate && !$canCheck && !$canApprove && !$canView) { http_response_code(403); exit('Akses ditolak.'); }
$canPreviewAll=$canCreate||$canCheck||$canApprove;
$canPreviewInternal=$canPreviewAll||qaRbacCan($konek,'jadwal_bulanan_internal');
$canPreviewExternal=$canPreviewAll||qaRbacCan($konek,'jadwal_bulanan_external');
$canPreviewDaily=$canPreviewAll||qaRbacCan($konek,'jadwal_harian');
$initialPreview=$canPreviewInternal?'internal':($canPreviewExternal?'external':'daily');
function jh($v) { return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
$ready=qaJadwalEnsure($konek);
$year=(int)date('Y');
$months=[1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$notice=''; $error='';
if (empty($_SESSION['qa_jadwal_csrf'])) $_SESSION['qa_jadwal_csrf']=bin2hex(random_bytes(24));
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($_SESSION['qa_jadwal_csrf'],(string)($_POST['csrf']??''))) { http_response_code(403); exit('Token keamanan tidak valid.'); }
    if (!$ready) $error='Tabel validasi jadwal belum tersedia. Jalankan migrasi database.';
    else {
        $month=filter_var($_POST['bulan']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>12]]);
        $action=(string)($_POST['action']??'');
        $note=trim((string)($_POST['keterangan']??''));
        if ($month===false || $month===null || strlen($note)>500) $error='Bulan atau keterangan tidak valid.';
        else {
            $periode=sprintf('%04d-%02d',$year,$month);
            $current=qaJadwalGet($konek,$periode);
            $name=trim((string)($_SESSION['nama_lengkap']??$_SESSION['username']??$_SESSION['userid']??''));
            if ($action==='ajukan' && $canCreate) {
                if ($current && !in_array($current['status_validasi'],['ditolak_pemeriksa','ditolak_penyetuju'],true)) $error='Jadwal bulan ini sudah diajukan.';
                else {
                    $stmt=$konek->prepare("INSERT INTO qa_jadwal_validasi (periode,status_validasi,dibuat_oleh,dibuat_at,updated_at) VALUES (?,'menunggu_pemeriksa',?,NOW(),NOW()) ON DUPLICATE KEY UPDATE status_validasi='menunggu_pemeriksa',dibuat_oleh=VALUES(dibuat_oleh),dibuat_at=NOW(),diperiksa_oleh=NULL,diperiksa_at=NULL,disetujui_oleh=NULL,disetujui_at=NULL,keterangan=NULL,updated_at=NOW()");
                    if ($stmt) { $stmt->bind_param('ss',$periode,$name); $ok=$stmt->execute(); $stmt->close(); } else $ok=false;
                    $notice=$ok?'Jadwal berhasil diajukan ke Pemeriksa. Email reminder akan dikirim oleh jadwal pengiriman validasi harian.' : '';
                    if (!$ok) $error='Gagal mengajukan jadwal.';
                }
            } elseif (in_array($action,['setuju_pemeriksa','tolak_pemeriksa'],true) && $canCheck) {
                if (!$current || $current['status_validasi']!=='menunggu_pemeriksa') $error='Jadwal tidak sedang menunggu Pemeriksa.';
                elseif ($note==='') $error='Keterangan validasi wajib diisi.';
                else {
                    $status=$action==='setuju_pemeriksa'?'menunggu_penyetuju':'ditolak_pemeriksa';
                    $stmt=$konek->prepare("UPDATE qa_jadwal_validasi SET status_validasi=?,diperiksa_oleh=?,diperiksa_at=NOW(),keterangan=?,updated_at=NOW() WHERE periode=? AND status_validasi='menunggu_pemeriksa'");
                    $stmt->bind_param('ssss',$status,$name,$note,$periode);
                    $ok=$stmt->execute() && $stmt->affected_rows===1; $stmt->close();
                    if ($ok) {
                        $notice='Keputusan Pemeriksa berhasil disimpan.';
                        if ($status==='menunggu_penyetuju') $notice.=' Email reminder Penyetuju akan dikirim oleh jadwal pengiriman validasi harian.';
                    } else $error='Status jadwal berubah. Muat ulang halaman.';
                }
            } elseif (in_array($action,['setuju_penyetuju','tolak_penyetuju'],true) && $canApprove) {
                if (!$current || $current['status_validasi']!=='menunggu_penyetuju') $error='Jadwal tidak sedang menunggu Penyetuju.';
                elseif ($note==='') $error='Keterangan validasi wajib diisi.';
                else {
                    $status=$action==='setuju_penyetuju'?'final_disetujui':'ditolak_penyetuju';
                    $stmt=$konek->prepare("UPDATE qa_jadwal_validasi SET status_validasi=?,disetujui_oleh=?,disetujui_at=NOW(),keterangan=?,updated_at=NOW() WHERE periode=? AND status_validasi='menunggu_penyetuju'");
                    $stmt->bind_param('ssss',$status,$name,$note,$periode);
                    $ok=$stmt->execute() && $stmt->affected_rows===1; $stmt->close();
                    if ($ok) $notice='Keputusan Penyetuju berhasil disimpan.'; else $error='Status jadwal berubah. Muat ulang halaman.';
                }
            } else $error='Aksi tidak diizinkan.';
        }
    }
}
$rows=[];
if ($ready) {
    $stmt=$konek->prepare('SELECT * FROM qa_jadwal_validasi WHERE periode LIKE ?');
    $prefix=$year.'-%';
    $stmt->bind_param('s',$prefix); $stmt->execute();
    $result=$stmt->get_result(); while ($row=$result->fetch_assoc()) $rows[$row['periode']]=$row;
    $stmt->close();
}
$labels=['menunggu_pemeriksa'=>'Menunggu Pemeriksa','menunggu_penyetuju'=>'Menunggu Penyetuju','final_disetujui'=>'Sudah divalidasi','ditolak_pemeriksa'=>'Ditolak Pemeriksa','ditolak_penyetuju'=>'Ditolak Penyetuju'];
?><!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Parameter Validasi Jadwal</title><link rel="stylesheet" href="../../css/bootstrap.min.css"><style>body{background:#f4f6f9;padding:24px}.container{max-width:1150px}.panel{margin-top:20px}.table td,.table th{vertical-align:middle!important}.status{font-weight:bold}.modal-detail dt{color:#666}.modal-detail dd{margin-bottom:10px}.modal-footer .btn{margin-left:5px}.table-responsive{margin-bottom:0}.jadwal-modal-dialog{width:96%;max-width:1600px}.jadwal-modal-dialog .modal-body{max-height:75vh;overflow-y:auto}.jadwal-preview-controls{display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin:14px 0}.jadwal-preview-frame{display:block;width:100%;height:62vh;border:1px solid #ccc;background:#fff}.jadwal-preview-label{margin:14px 0 0}@media(max-width:767px){.jadwal-modal-dialog{width:auto}.jadwal-preview-frame{height:55vh}}</style></head><body><div class="container"><a href="../../media.php" class="btn btn-default">&larr; Kembali ke Menu</a><h2>Parameter Validasi Jadwal</h2><p>Pengajuan satu bulan berlaku untuk kotak tanda tangan jadwal bulanan dan harian pada bulan yang sama.</p>
<?php if (!$ready): ?><div class="alert alert-danger">Tabel belum tersedia. Jalankan <code>database/migrations/20261112_qa_jadwal_validasi.sql</code>.</div><?php endif; ?>
<?php if ($notice): ?><div class="alert alert-success"><?=jh($notice)?></div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?=jh($error)?></div><?php endif; ?>
<div class="panel panel-default"><div class="panel-heading"><b>Tahun <?=$year?></b></div><div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Bulan</th><th>Status Validasi</th><th>Dibuat</th><th>Diperiksa</th><th>Disetujui</th><th>Keterangan</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($months as $month=>$monthName):
    $key=sprintf('%04d-%02d',$year,$month); $r=$rows[$key]??null; $status=$r['status_validasi']??'';
    $mode='detail'; $step=''; $button='Lihat Detail';
    if ($canCheck && $status==='menunggu_pemeriksa') { $mode='validasi'; $step='pemeriksa'; $button='Detail & Validasi'; }
    elseif ($canApprove && $status==='menunggu_penyetuju') { $mode='validasi'; $step='penyetuju'; $button='Detail & Validasi'; }
    elseif ($canCreate && (!$r || strncmp($status,'ditolak_',8)===0)) { $mode='ajukan'; $button=$r?'Ajukan Ulang':'Ajukan'; }
?><tr><td><b><?=jh($monthName.' '.$year)?></b></td><td class="status"><?=jh($labels[$status]??'Belum divalidasi')?></td><td><?=jh($r['dibuat_oleh']??'-')?></td><td><?=jh($r['diperiksa_oleh']??'-')?></td><td><?=jh($r['disetujui_oleh']??'-')?></td><td><?=jh($r['keterangan']??'-')?></td><td>
<button type="button" class="btn btn-sm <?= $mode==='detail'?'btn-default':'btn-primary' ?> open-jadwal-modal"
    data-month="<?=$month?>" data-label="<?=jh($monthName.' '.$year)?>" data-mode="<?=$mode?>" data-step="<?=$step?>"
    data-status="<?=jh($labels[$status]??'Belum divalidasi')?>"
    data-created="<?=jh($r['dibuat_oleh']??'-')?>" data-created-at="<?=jh($r['dibuat_at']??'-')?>"
    data-checked="<?=jh($r['diperiksa_oleh']??'-')?>" data-checked-at="<?=jh($r['diperiksa_at']??'-')?>"
    data-approved="<?=jh($r['disetujui_oleh']??'-')?>" data-approved-at="<?=jh($r['disetujui_at']??'-')?>"
    data-note="<?=jh($r['keterangan']??'-')?>"><?=jh($button)?></button>
</td></tr><?php endforeach; ?></tbody></table></div></div></div>

<div class="modal fade" id="jadwalModal" tabindex="-1" role="dialog" aria-labelledby="jadwalModalTitle">
    <div class="modal-dialog jadwal-modal-dialog" role="document"><div class="modal-content">
        <form id="jadwalActionForm" method="post">
            <input type="hidden" name="csrf" value="<?=jh($_SESSION['qa_jadwal_csrf'])?>">
            <input type="hidden" name="bulan" id="jadwalModalMonth">
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button><h4 class="modal-title" id="jadwalModalTitle">Validasi Jadwal</h4></div>
            <div class="modal-body">
                <p id="jadwalModalIntro"></p>
                <dl class="dl-horizontal modal-detail">
                    <dt>Status</dt><dd id="jadwalModalStatus"></dd>
                    <dt>Dibuat</dt><dd id="jadwalModalCreated"></dd>
                    <dt>Diperiksa</dt><dd id="jadwalModalChecked"></dd>
                    <dt>Disetujui</dt><dd id="jadwalModalApproved"></dd>
                    <dt>Keterangan terakhir</dt><dd id="jadwalModalNote"></dd>
                </dl>
                <h4 class="jadwal-preview-label">Pratinjau layout jadwal</h4>
                <div class="jadwal-preview-controls">
                    <?php if ($canPreviewInternal): ?><button type="button" class="btn btn-default btn-sm jadwal-preview-tab" data-preview="internal">Bulanan Internal QA</button><?php endif; ?>
                    <?php if ($canPreviewExternal): ?><button type="button" class="btn btn-default btn-sm jadwal-preview-tab" data-preview="external">Bulanan Seksi</button><?php endif; ?>
                    <?php if ($canPreviewDaily): ?><button type="button" class="btn btn-default btn-sm jadwal-preview-tab" data-preview="daily">Harian</button><?php endif; ?>
                    <label id="jadwalDailyDateWrap" style="display:none;margin:0">Tanggal harian <input type="date" id="jadwalDailyDate" class="form-control input-sm" style="display:inline-block;width:auto"></label>
                </div>
                <iframe id="jadwalPreviewFrame" class="jadwal-preview-frame" title="Pratinjau layout jadwal bulanan dan harian" loading="lazy"></iframe>
                <div class="form-group" id="jadwalNoteGroup"><label for="jadwalModalInputNote">Keterangan validasi <span class="text-danger">*</span></label><textarea class="form-control" id="jadwalModalInputNote" name="keterangan" rows="3" maxlength="500" placeholder="Isi alasan persetujuan atau penolakan"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button><button type="submit" class="btn btn-primary" name="action" value="ajukan" id="jadwalSubmitApply">Ajukan Jadwal</button><button type="submit" class="btn btn-danger" name="action" value="" id="jadwalSubmitReject">Tolak</button><button type="submit" class="btn btn-success" name="action" value="" id="jadwalSubmitApprove">Setuju</button></div>
        </form>
    </div></div>
</div>
<script src="../../js/jquery.js"></script><script src="../../js/bootstrap.min.js"></script>
<script>
(function($){
    var previewMonth='', previewType='internal';
    function showPreview(type){
        previewType=type;
        $('.jadwal-preview-tab').removeClass('btn-primary').addClass('btn-default');
        $('.jadwal-preview-tab[data-preview="'+type+'"]').removeClass('btn-default').addClass('btn-primary');
        $('#jadwalDailyDateWrap').toggle(type==='daily');
        var url;
        if (type==='daily') {
            url='../qa/jadwal_harian/cetak_jadwal.php?validation_preview=1&tgl='+encodeURIComponent($('#jadwalDailyDate').val());
        } else {
            url='../qa/jadwal_bulanan/cetak_jadwal_bulanan.php?validation_preview=1&embed=1&bulan='+encodeURIComponent(previewMonth)+'&mode='+encodeURIComponent(type);
        }
        $('#jadwalPreviewFrame').attr('src',url);
    }
    $('.open-jadwal-modal').on('click',function(){
        var b=$(this), mode=b.attr('data-mode'), step=b.attr('data-step');
        previewMonth='<?=$year?>-'+('0'+b.attr('data-month')).slice(-2);
        var today=new Date(), todayString=today.getFullYear()+'-'+('0'+(today.getMonth()+1)).slice(-2)+'-'+('0'+today.getDate()).slice(-2);
        $('#jadwalDailyDate').attr('min',previewMonth+'-01').attr('max',previewMonth+'-'+new Date(Number(previewMonth.slice(0,4)),Number(previewMonth.slice(5,7)),0).getDate()).val(todayString.slice(0,7)===previewMonth?todayString:previewMonth+'-01');
        $('#jadwalModalTitle').text('Jadwal ' + b.attr('data-label'));
        $('#jadwalModalMonth').val(b.attr('data-month'));
        $('#jadwalModalStatus').text(b.attr('data-status'));
        $('#jadwalModalCreated').text(b.attr('data-created') + ' · ' + b.attr('data-created-at'));
        $('#jadwalModalChecked').text(b.attr('data-checked') + ' · ' + b.attr('data-checked-at'));
        $('#jadwalModalApproved').text(b.attr('data-approved') + ' · ' + b.attr('data-approved-at'));
        $('#jadwalModalNote').text(b.attr('data-note'));
        $('#jadwalModalInputNote').val('').prop('required',mode==='validasi');
        $('#jadwalNoteGroup').toggle(mode==='validasi');
        $('#jadwalSubmitApply').toggle(mode==='ajukan');
        $('#jadwalSubmitReject,#jadwalSubmitApprove').toggle(mode==='validasi');
        $('#jadwalSubmitReject').val('tolak_' + step);
        $('#jadwalSubmitApprove').val('setuju_' + step);
        $('#jadwalModalIntro').text(mode==='ajukan' ? 'Ajukan jadwal bulan ini untuk diperiksa dan disetujui. Satu pengajuan berlaku untuk jadwal bulanan dan harian.' : mode==='validasi' ? 'Periksa jadwal bulan ini, isi keterangan, lalu pilih keputusan.' : 'Detail pengajuan jadwal bulan ini.');
        showPreview('<?= $initialPreview ?>');
        $('#jadwalModal').modal('show');
    });
    $('.jadwal-preview-tab').on('click',function(){showPreview($(this).attr('data-preview'));});
    $('#jadwalDailyDate').on('change',function(){if(previewType==='daily')showPreview('daily');});
    $('#jadwalModal').on('hidden.bs.modal',function(){$('#jadwalPreviewFrame').attr('src','about:blank');});
    $('#jadwalActionForm').on('submit',function(e){
        if ($('#jadwalNoteGroup').is(':visible') && !$.trim($('#jadwalModalInputNote').val())) {
            e.preventDefault();
            $('#jadwalModalInputNote').focus();
            return false;
        }
    });
})(jQuery);
</script></body></html>

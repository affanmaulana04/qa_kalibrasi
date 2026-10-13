<?php
session_start();
if (empty($_SESSION['login'])) { header('location: ../../logout.php'); exit; }
include "../../inc/inc_koneksi.php";

$uid=$_SESSION['userid']??'';
$username=$_SESSION['username']??($_SESSION['nama_lengkap']??$uid);
$role=strtolower(trim((string)($_SESSION['role']??'')));
$is_admin=strtoupper((string)($_SESSION['admin']??'N'))==='Y'||$role==='admin';

function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}

// Pastikan tabel laporan validator tersedia tanpa INFORMATION_SCHEMA/PREPARE.
$cekJenis = mysqli_query($konek, "SHOW COLUMNS FROM qa_pengecekan LIKE 'jenis_transaksi'");
if ($cekJenis && mysqli_num_rows($cekJenis) === 0) { @mysqli_query($konek, "ALTER TABLE qa_pengecekan ADD COLUMN jenis_transaksi VARCHAR(40) NULL AFTER nomor_pengecekan"); }

@mysqli_query($konek,"CREATE TABLE IF NOT EXISTS qa_laporan_transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenis_transaksi VARCHAR(40) NOT NULL,
    periode_key VARCHAR(30) NOT NULL,
    periode_label VARCHAR(120) NOT NULL,
    status_validasi VARCHAR(40) NOT NULL DEFAULT 'menunggu_pemeriksa',
    created_by VARCHAR(100) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    payload_json LONGTEXT NULL,
    UNIQUE KEY uq_jenis_periode (jenis_transaksi, periode_key),
    INDEX idx_status (status_validasi), INDEX idx_periode (periode_key)
)");
@mysqli_query($konek,"CREATE TABLE IF NOT EXISTS qa_laporan_validasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    laporan_id INT NOT NULL,
    tahap ENUM('pemeriksa','penyetuju') NOT NULL,
    keputusan ENUM('setuju','tolak') NOT NULL,
    nama_validator VARCHAR(100) NOT NULL,
    keterangan VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_laporan (laporan_id)
)");

// Hak validator untuk tombol aksi.
$flags=['pembuat'=>false,'pemeriksa'=>false,'penyetuju'=>false];
$stmt=$konek->prepare("SELECT pembuat,pemeriksa,penyetuju FROM `user` WHERE uid=? OR uname=? LIMIT 1");
if($stmt){$stmt->bind_param('ss',$uid,$username);$stmt->execute();$r=$stmt->get_result()->fetch_assoc();$stmt->close();if($r){$flags['pembuat']=strtoupper((string)$r['pembuat'])==='Y';$flags['pemeriksa']=strtoupper((string)$r['pemeriksa'])==='Y';$flags['penyetuju']=strtoupper((string)$r['penyetuju'])==='Y';}}
$canPemeriksa=$is_admin||$flags['pemeriksa'];
$canPenyetuju=$is_admin||$flags['penyetuju'];
if(!$canPemeriksa && !$canPenyetuju){
    http_response_code(403);
    echo '<div style="padding:30px;font-family:Arial"><h3>Akses ditolak</h3><p>User ini bukan Pemeriksa/Penyetuju.</p></div>';
    exit;
}

// Hanya tampilkan transaksi yang BELUM selesai divalidasi.
// Urutan: tanggal paling lama / paling awal di atas.
$pendingWhere = "q.status_validasi IN ('menunggu_pemeriksa','menunggu_penyetuju')";
if(!$is_admin){
    if($canPemeriksa && !$canPenyetuju){
        $pendingWhere = "q.status_validasi='menunggu_pemeriksa'";
    } elseif($canPenyetuju && !$canPemeriksa){
        $pendingWhere = "q.status_validasi='menunggu_penyetuju'";
    }
}

$rows=[];
$q1=mysqli_query($konek,"SELECT q.id,
        COALESCE(q.jenis_transaksi,'update_hasil_kalibrasi') AS jenis_transaksi,
        q.no_part,q.operator,q.created_by,q.tanggal_pengecekan,q.status_kalibrasi,q.status_validasi,q.keterangan,
        COALESCE(p.nama_part, ar.nama_part, an.nama_part, ad.nama_part, at.nama_part) AS nama_part,
        COALESCE(p.spesifikasi, ar.spesifikasi, an.spesifikasi, ad.spesifikasi, at.spesifikasi) AS spesifikasi,
        COALESCE(p.seksi_pemilik, ar.seksi_pemilik, an.seksi_pemilik, ad.seksi_pemilik, at.seksi_pemilik) AS seksi_pemilik
    FROM qa_pengecekan q
    LEFT JOIN qa_part p ON p.id=q.alat_id
    LEFT JOIN qa_alat_rusak ar ON ar.no_part=q.no_part
    LEFT JOIN qa_alat_ng an ON an.no_part=q.no_part
    LEFT JOIN qa_alat_discontinue ad ON ad.no_part=q.no_part
    LEFT JOIN qa_alat_ditarik at ON at.no_part=q.no_part
    WHERE COALESCE(q.jenis_transaksi,'update_hasil_kalibrasi') <> 'registrasi_alat'
      AND (q.nomor_pengecekan IS NULL OR q.nomor_pengecekan NOT LIKE 'QA-REG-%')
      AND $pendingWhere
    ORDER BY q.tanggal_pengecekan ASC, q.id ASC
    LIMIT 200");
if($q1){while($r=mysqli_fetch_assoc($q1)){
    $rows[]=[
        'source'=>'qa','id'=>$r['id'],'jenis'=>$r['jenis_transaksi'] ?: 'update_hasil_kalibrasi',
        'no_part'=>$r['no_part'],'nama'=>$r['nama_part'] ?: '-', 'spesifikasi'=>$r['spesifikasi'] ?: '-',
        'seksi'=>$r['seksi_pemilik'] ?: '-', 'operator'=>$r['operator'] ?: '-',
        'created_by'=>$r['created_by'] ?: '-', 'tanggal'=>$r['tanggal_pengecekan'] ?: '-',
        'hasil'=>(function($v){$v=strtoupper(trim((string)$v)); return ['GOOD'=>'OK','OK'=>'OK','BAD'=>'NOT GOOD','NOT GOOD'=>'NOT GOOD','BURUK'=>'NOT GOOD','RUSAK'=>'RUSAK','DISCONTINUE'=>'DISCONTINUE','DITARIK'=>'DITARIK TECHNICAL','DITARIK TECHNICAL'=>'DITARIK TECHNICAL'][$v] ?? ($v ?: '-');})($r['status_kalibrasi'] ?? ''), 'status_validasi'=>$r['status_validasi'] ?: '-',
        'keterangan'=>$r['keterangan'] ?: '-'
    ];
}}

function jenisLabel($v){
    return [
        'update_hasil_kalibrasi'=>'Update Hasil Kalibrasi',
        'laporan_harian'=>'Laporan Harian',
        'laporan_bulanan'=>'Laporan Bulanan',
        'laporan_tahunan'=>'Laporan Tahunan',
        'registrasi_alat'=>'Registrasi Alat',
    ][$v] ?? ucwords(str_replace('_',' ',(string)$v));
}
function statusLabel($s){
    $map=['menunggu_pemeriksa'=>['MENUNGGU PEMERIKSA','warning'],'menunggu_penyetuju'=>['MENUNGGU PENYETUJU','info'],'final_disetujui'=>['FINAL DISETUJUI','success'],'ditolak_pemeriksa'=>['DITOLAK PEMERIKSA','danger'],'ditolak_penyetuju'=>['DITOLAK PENYETUJU','danger'],'setuju'=>['FINAL','success'],'langsung_tampil'=>['TAMPIL LANGSUNG','primary'],'-'=>['-','default']];
    return $map[$s]??[strtoupper((string)$s),'default'];
}

?>
<!DOCTYPE html><html lang="id"><head>
    <link rel="icon" type="image/png" href="/qa_kalibrasi/images/favicon-toto-qa.png?v=1">
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Transaksi Kalibrasi | QA System</title>
<link href="../../css/bootstrap.min.css" rel="stylesheet"><link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet">
<style>body{padding-top:70px;background:#f4f6f9;font-family:Arial,sans-serif}.card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:20px}.table-wrap{overflow:auto}.table>thead>tr>th{background:#e9ecef;text-align:center;vertical-align:middle}.table>tbody>tr>td{vertical-align:middle}.btn-action{margin:2px 0}.small-note{color:#666}.iframe-wrap{height:72vh}.iframe-wrap iframe{width:100%;height:100%;border:0}</style>
</head><body>
<nav class="navbar navbar-default navbar-fixed-top">
    <div class="container-fluid" style="padding:0 25px">
        <div class="navbar-header">
            <a class="navbar-brand" href="../../media.php">
                <img src="../../images/toto.png" style="height:25px" alt="Menu">
            </a>
        </div>
        <ul class="nav navbar-nav navbar-left">
            <li>
                <a href="../../media.php"><i class="fa fa-arrow-left"></i> Kembali ke Menu</a>
            </li>
        </ul>
        <ul class="nav navbar-nav navbar-right">
            <li><a href="javascript:void(0)"><i class="fa fa-user"></i> <?= h($username) ?></a></li>
        </ul>
    </div>
</nav>

<div class="card">
    <h3 style="margin-top:0"><i class="fa fa-check-square-o"></i> Transaksi Kalibrasi</h3>
<div class="card">
    <div class="table-wrap">
        <table class="table table-bordered table-striped table-hover" id="tblTransaksi">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Jenis Transaksi</th>
                    <th>No. Alat</th>
                    <th>Operator / Pembuat</th>
                    <th>Tanggal</th>
                    <th>Hasil</th>
                    <th>Status Validasi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if(!$rows):?>
                <tr><td colspan="8" class="text-center">Belum ada transaksi.</td></tr>
            <?php else: foreach($rows as $i=>$r): $sl=statusLabel($r['status_validasi']); ?>
                <tr>
                    <td class="text-center"><?= $i+1 ?></td>
                    <td><?= h(jenisLabel($r['jenis'])) ?></td>
                    <td><b><?= h($r['no_part']) ?></b></td>
                    <td><?= h($r['operator']!=='-' ? $r['operator'] : $r['created_by']) ?></td>
                    <td><?= h($r['tanggal']) ?></td>
                    <td class="text-center"><?= h($r['hasil']) ?></td>
                    <td class="text-center"><span class="label label-<?= $sl[1] ?> status-label"><?= h($sl[0]) ?></span></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-primary btn-detail btn-action"
                                data-id="<?= h($r['id']) ?>"
                                data-step="<?= $r['status_validasi']==='menunggu_pemeriksa' ? 'pemeriksa' : 'penyetuju' ?>">
                            <i class="fa fa-eye"></i> Detail &amp; Validasi
                        </button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="detailModal">
    <div class="modal-dialog modal-lg" style="width:95%;max-width:1400px">
        <div class="modal-content">
            <div class="modal-header">
                <button class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Detail &amp; Laporan Transaksi</h4>
            </div>
            <div class="modal-body">
                <div id="auditBox"></div>
                <div class="form-group" style="margin-top:15px">
                    <label for="validatorKeterangan"><b>Keterangan Validator <span style="color:#d9534f">*</span></b></label>
                    <textarea id="validatorKeterangan" class="form-control" rows="4" maxlength="500" placeholder="Wajib isi keterangan sebelum memilih Setuju atau Tolak."></textarea>
                    <small class="text-muted">Maksimal 500 karakter. Keterangan akan disimpan ke audit validasi.</small>
                </div>
                <div class="iframe-wrap">
                    <iframe id="reportFrame"></iframe>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" data-dismiss="modal">Tutup</button>
                <button class="btn btn-danger" id="btnDecisionTolak"><i class="fa fa-times"></i> Tolak</button>
                <button class="btn btn-success" id="btnDecisionSetuju"><i class="fa fa-check"></i> Setuju</button>
            </div>
        </div>
    </div>
</div>

<script src="../../js/jquery.js"></script>
<script src="../../js/bootstrap.min.js"></script>
<script src="../../js/bootstrap-dialog.js"></script>
<script>
(function(){
    var currentId=0;
    var currentStep='';
    var currentPrint='';

    function esc(v){
        return $('<div>').text(v==null?'':String(v)).html();
    }
    function notif(title,msg,type,cb){
        BootstrapDialog.alert({
            title:title,
            type:type||BootstrapDialog.TYPE_INFO,
            message:msg,
            callback:cb||function(){}
        });
    }
    function refresh(){location.reload();}
    function setDecisionButtons(disabled){
        $('#btnDecisionTolak,#btnDecisionSetuju').prop('disabled',disabled);
    }

    $('.btn-detail').on('click',function(){
        currentId=parseInt($(this).data('id'),10)||0;
        currentStep=String($(this).data('step')||'');
        currentPrint='';
        $('#validatorKeterangan').val('');
        setDecisionButtons(true);
        $('#auditBox').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat semua data transaksi...</div>');
        $('#reportFrame').attr('src','about:blank');
        $('#detailModal').modal('show');

        $.post('proses_qa.php?action=get_transaksi_detail',{pengecekan_id:currentId},function(res){
            if(!res || res.status!=='success'){
                notif('Gagal',esc(res&&res.message||'Detail gagal dimuat'),BootstrapDialog.TYPE_DANGER);
                return;
            }

            currentPrint=JSON.stringify(res.print_data||{});
            sessionStorage.setItem('cetak_data',currentPrint);

            var d=res.data||{};
            var a=res.audit||[];
            var status=String(d.status_validasi||'');
            currentStep=status==='menunggu_pemeriksa'?'pemeriksa':(status==='menunggu_penyetuju'?'penyetuju':currentStep);
            var roleText=currentStep==='pemeriksa'?'Pemeriksa':'Penyetuju';

            var html='<div class="row">'+
                '<div class="col-sm-3"><b>No. Alat:</b><br>'+esc(d.no_part||'-')+'</div>'+
                '<div class="col-sm-3"><b>Nama Alat:</b><br>'+esc(d.nama_part||'-')+'</div>'+ 
                '<div class="col-sm-3"><b>Tanggal:</b><br>'+esc(d.tanggal_pengecekan||'-')+'</div>'+ 
                '<div class="col-sm-3"><b>Hasil:</b><br>'+esc(d.status_kalibrasi||'-')+'</div>'+ 
                '</div>'+ 
                '<div class="row" style="margin-top:10px">'+
                '<div class="col-sm-3"><b>Operator:</b><br>'+esc(d.operator||'-')+'</div>'+ 
                '<div class="col-sm-3"><b>Pembuat:</b><br>'+esc(d.created_by||'-')+'</div>'+ 
                '<div class="col-sm-3"><b>Status:</b><br>'+esc(status||'-')+'</div>'+ 
                '<div class="col-sm-3"><b>Validator Saat Ini:</b><br>'+esc(roleText)+'</div>'+ 
                '</div><hr><h5><b>Riwayat Validasi</b></h5>'+ 
                '<table class="table table-bordered table-striped"><thead><tr><th>Tahap</th><th>Keputusan</th><th>Validator</th><th>Keterangan</th><th>Waktu</th></tr></thead><tbody>';

            if(a.length){
                a.forEach(function(x){
                    html+='<tr><td>'+esc(x.tahap)+'</td><td>'+esc(x.keputusan)+'</td><td>'+esc(x.nama_validator)+'</td><td>'+esc(x.keterangan||'-')+'</td><td>'+esc(x.created_at)+'</td></tr>';
                });
            } else {
                html+='<tr><td colspan="5" class="text-center">Belum ada pemeriksaan/penyetujuan.</td></tr>';
            }
            html+='</tbody></table>';
            $('#auditBox').html(html);
            $('#reportFrame').attr('src','cetak_laporan.php?embed=1&ts='+Date.now());
            setDecisionButtons(false);
        },'json').fail(function(xhr){
            notif('Request gagal',esc(xhr.responseText||xhr.statusText||'Gagal memuat transaksi'),BootstrapDialog.TYPE_DANGER);
        });
    });

    function submitDecision(decision){
        if(!currentId){
            notif('Gagal','Transaksi belum dipilih.',BootstrapDialog.TYPE_DANGER);
            return;
        }
        var keterangan=$.trim($('#validatorKeterangan').val());
        if(!keterangan){
            notif('Keterangan wajib','Isi keterangan validator terlebih dahulu sebelum memilih '+decision.toUpperCase()+'.',BootstrapDialog.TYPE_WARNING);
            $('#validatorKeterangan').focus();
            return;
        }
        if(keterangan.length>500){
            notif('Keterangan terlalu panjang','Keterangan maksimal 500 karakter.',BootstrapDialog.TYPE_WARNING);
            $('#validatorKeterangan').focus();
            return;
        }

        var role=currentStep==='pemeriksa'?'Pemeriksa':'Penyetuju';
        var type=decision==='setuju'?BootstrapDialog.TYPE_SUCCESS:BootstrapDialog.TYPE_DANGER;
        var btn=decision==='setuju'?$('#btnDecisionSetuju'):$('#btnDecisionTolak');
        var oldText=btn.html();
        setDecisionButtons(true);
        btn.html('<i class="fa fa-spinner fa-spin"></i> Memproses...');

        $.post('proses_qa.php?action=validasi_'+currentStep,{
            pengecekan_id:currentId,
            keputusan:decision,
            keterangan:keterangan
        },function(res){
            if(res&&res.status==='success'){
                notif('Berhasil','Validasi '+role+' berhasil disimpan beserta keterangannya.',type,refresh);
            }else{
                setDecisionButtons(false);
                btn.html(oldText);
                notif('Gagal',esc(res&&res.message||'Validasi gagal'),BootstrapDialog.TYPE_DANGER);
            }
        },'json').fail(function(xhr){
            setDecisionButtons(false);
            btn.html(oldText);
            notif('Gagal',esc(xhr.responseText||'Request gagal'),BootstrapDialog.TYPE_DANGER);
        });
    }

    $('#btnDecisionSetuju').on('click',function(){submitDecision('setuju');});
    $('#btnDecisionTolak').on('click',function(){submitDecision('tolak');});
})();
</script>
</body></html>

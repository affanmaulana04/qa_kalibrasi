<?php
session_start();
if (empty($_SESSION['login'])) {
    header('location: ../../logout.php');
    exit();
}

include "../../inc/inc_koneksi.php";

$nama_petugas = isset($_SESSION['nama_lengkap']) && $_SESSION['nama_lengkap'] !== ''
    ? $_SESSION['nama_lengkap'] : ($_SESSION['userid'] ?? '');
$id_pemakai = $_SESSION['userid'] ?? '';
$role = strtolower(trim((string)($_SESSION['role'] ?? '')));
$status_admin = strtoupper(trim((string)($_SESSION['admin'] ?? 'N')));
$is_admin = ($status_admin === 'Y' || $status_admin === '1' || $role === 'admin');

if (!$is_admin) {
    http_response_code(403);
    ?><!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Akses Ditolak</title>
    <link href="../../css/bootstrap.min.css" rel="stylesheet"></head><body style="padding:40px">
    <div class="alert alert-danger"><b>Akses ditolak.</b> Halaman Custom Tanggal Merah hanya dapat diakses Admin.</div>
    <a class="btn btn-default" href="../../media.php">Kembali</a></body></html><?php
    exit();
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function esc($db, $v) { return mysqli_real_escape_string($db, (string)$v); }

// Pastikan tabel tersedia. Jika akun DB tidak punya hak CREATE, admin tetap mendapat petunjuk import SQL.
$tableReady = false;
$qCheck = @mysqli_query($konek, "SELECT id FROM qa_tanggal_merah LIMIT 1");
if ($qCheck !== false) {
    $tableReady = true;
} else {
    $createSql = "CREATE TABLE IF NOT EXISTS qa_tanggal_merah (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        tanggal DATE NOT NULL,
        keterangan VARCHAR(150) NOT NULL,
        created_by VARCHAR(100) DEFAULT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_tanggal (tanggal),
        KEY idx_tanggal (tanggal)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci";
    if (@mysqli_query($konek, $createSql)) {
        $tableReady = true;
    }
}

if (empty($_SESSION['qa_tanggal_merah_csrf'])) {
    $_SESSION['qa_tanggal_merah_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['qa_tanggal_merah_csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$tableReady) {
        echo json_encode(['status'=>'error','message'=>'Tabel qa_tanggal_merah belum tersedia. Import file qa_tanggal_merah.sql terlebih dahulu.']);
        exit();
    }
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        echo json_encode(['status'=>'error','message'=>'Token keamanan tidak valid. Refresh halaman lalu coba lagi.']);
        exit();
    }

    $action = strtolower(trim((string)($_POST['action'] ?? '')));
    $tanggal = trim((string)($_POST['tanggal'] ?? ''));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || strtotime($tanggal) === false) {
        echo json_encode(['status'=>'error','message'=>'Tanggal tidak valid.']);
        exit();
    }
    $tanggalEsc = esc($konek, $tanggal);

    if ($action === 'save') {
        $keterangan = trim((string)($_POST['keterangan'] ?? ''));
        if ($keterangan === '') $keterangan = 'Tanggal Merah Perusahaan';
        if (strlen($keterangan) > 150) $keterangan = substr($keterangan, 0, 150);
        $ketEsc = esc($konek, $keterangan);
        $userEsc = esc($konek, $nama_petugas !== '' ? $nama_petugas : $id_pemakai);
        $sql = "INSERT INTO qa_tanggal_merah (tanggal,keterangan,created_by,created_at,updated_at)
                VALUES ('$tanggalEsc','$ketEsc','$userEsc',NOW(),NULL)
                ON DUPLICATE KEY UPDATE keterangan=VALUES(keterangan), updated_at=NOW()";
        if (!mysqli_query($konek, $sql)) {
            echo json_encode(['status'=>'error','message'=>'Gagal menyimpan tanggal merah: '.mysqli_error($konek)]);
            exit();
        }
        echo json_encode(['status'=>'success','message'=>'Tanggal merah berhasil disimpan.']);
        exit();
    }

    if ($action === 'delete') {
        if (!mysqli_query($konek, "DELETE FROM qa_tanggal_merah WHERE tanggal='$tanggalEsc' LIMIT 1")) {
            echo json_encode(['status'=>'error','message'=>'Gagal menghapus tanggal merah: '.mysqli_error($konek)]);
            exit();
        }
        echo json_encode(['status'=>'success','message'=>'Tanggal merah berhasil dihapus.']);
        exit();
    }

    echo json_encode(['status'=>'error','message'=>'Aksi tidak dikenali.']);
    exit();
}

$month = $_GET['bulan'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
$year = (int)substr($month,0,4);
$mon = (int)substr($month,5,2);
if ($mon < 1 || $mon > 12) { $year=(int)date('Y'); $mon=(int)date('m'); $month=date('Y-m'); }
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $mon, $year);
$firstDate = sprintf('%04d-%02d-01', $year, $mon);
$lastDate = sprintf('%04d-%02d-%02d', $year, $mon, $daysInMonth);
$firstDow = (int)date('N', strtotime($firstDate)); // 1=Senin

$holidayMap = [];
if ($tableReady) {
    $q = mysqli_query($konek, "SELECT tanggal,keterangan,created_by,created_at,updated_at FROM qa_tanggal_merah WHERE tanggal BETWEEN '".esc($konek,$firstDate)."' AND '".esc($konek,$lastDate)."' ORDER BY tanggal ASC");
    if ($q) while ($r=mysqli_fetch_assoc($q)) $holidayMap[$r['tanggal']]=$r;
}

$namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$prev = date('Y-m', strtotime($firstDate.' -1 month'));
$next = date('Y-m', strtotime($firstDate.' +1 month'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Custom Tanggal Merah | QA System</title>
    <link rel="icon" type="image/png" href="../../images/LogoTOTO.png?v=<?=time()?>">
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome-4.3.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        body{padding-top:80px;background:#f6f7f9}
        .panel-calendar{background:#fff;border:1px solid #ddd;border-radius:5px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
        .calendar{width:100%;table-layout:fixed;border-collapse:collapse;background:#fff}
        .calendar th{padding:10px 5px;border:1px solid #ddd;background:#eef0f2;text-align:center}
        .calendar td{height:112px;border:1px solid #ddd;vertical-align:top;padding:0;position:relative;background:#fff}
        .calendar td.empty{background:#f8f8f8}
        .day-btn{width:100%;height:100%;min-height:111px;border:0;background:transparent;text-align:left;padding:8px;display:block;position:relative}
        .day-btn:hover{background:#f5f8fb}
        .day-number{font-size:16px;font-weight:bold;display:inline-block}
        .weekend .day-number{color:#b02a37}
        .holiday-cell{background:rgba(220,53,69,.50)!important}
        .holiday-cell .day-btn:hover{background:rgba(220,53,69,.50)!important}
        .holiday-cell .day-number{color:#8c1520}
        .holiday-label{display:block;margin-top:8px;font-size:11px;line-height:1.3;color:#7f101b;font-weight:bold;word-break:break-word}
        .holiday-check{position:absolute;right:8px;top:8px;color:#a31521;font-size:16px}
        .today-mark{box-shadow:inset 0 0 0 2px #337ab7}
        .calendar-legend{margin-top:12px;color:#666;font-size:12px}
        .legend-box{display:inline-block;width:14px;height:14px;background:rgba(220,53,69,.50);border:1px solid rgba(220,53,69,.70);vertical-align:-2px;margin-right:5px}
        .toolbar{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:15px}
        .toolbar h3{margin:0;font-weight:bold}
        .holiday-list{margin-top:20px}
        .holiday-list table{background:#fff}
        @media(max-width:800px){.calendar td{height:80px}.day-btn{min-height:79px;padding:5px}.holiday-label{font-size:9px}.calendar th{font-size:11px;padding:6px 2px}}
    </style>
</head>
<body>
<nav class="navbar navbar-default navbar-fixed-top">
    <div class="container-fluid">
        <div class="navbar-header"><a class="navbar-brand" href="../../media.php"><img src="../../images/toto.png" style="height:25px;margin-top:-3px"></a></div>
        <ul class="nav navbar-nav navbar-left"><li><a href="../../media.php"><i class="fa fa-arrow-left"></i> Kembali</a></li></ul>
        <ul class="nav navbar-nav navbar-right"><li><a href="#"><i class="fa fa-user"></i> <?=h($nama_petugas)?></a></li></ul>
    </div>
</nav>

<div class="container-fluid" style="padding:0 25px 30px">
    <?php if(!$tableReady): ?>
        <div class="alert alert-danger">
            <b>Tabel qa_tanggal_merah belum tersedia.</b><br>
            Import file <code>qa_tanggal_merah.sql</code> ke database QA. Halaman ini tetap ditampilkan, tetapi tanggal belum bisa disimpan.
        </div>
    <?php endif; ?>

    <div class="panel-calendar">
        <div class="toolbar">
            <div>
                <h3><i class="fa fa-calendar"></i> Custom Tanggal Merah Perusahaan</h3>
                <div class="text-muted">Klik tanggal untuk menandai / mengubah / menghapus tanggal merah.</div>
            </div>
            <div class="form-inline">
                <a class="btn btn-default" href="?bulan=<?=h($prev)?>"><i class="fa fa-chevron-left"></i></a>
                <input type="month" id="pilih-bulan" class="form-control" value="<?=h($month)?>">
                <a class="btn btn-default" href="?bulan=<?=h($next)?>"><i class="fa fa-chevron-right"></i></a>
            </div>
        </div>

        <h4 class="text-center" style="font-weight:bold;margin:18px 0"><?=h(strtoupper($namaBulan[$mon].' '.$year))?></h4>
        <table class="calendar">
            <thead><tr><th>SEN</th><th>SEL</th><th>RAB</th><th>KAM</th><th>JUM</th><th>SAB</th><th>MIN</th></tr></thead>
            <tbody>
            <?php
            $cell=1; $day=1; $today=date('Y-m-d');
            while ($day <= $daysInMonth) {
                echo '<tr>';
                for ($dow=1;$dow<=7;$dow++,$cell++) {
                    if (($cell < $firstDow) || $day > $daysInMonth) {
                        echo '<td class="empty"></td>';
                        continue;
                    }
                    $date=sprintf('%04d-%02d-%02d',$year,$mon,$day);
                    $isHoliday=isset($holidayMap[$date]);
                    $isWeekend=$dow>=6;
                    $classes=[];
                    if($isHoliday)$classes[]='holiday-cell';
                    if($isWeekend)$classes[]='weekend';
                    if($date===$today)$classes[]='today-mark';
                    $ket=$isHoliday?(string)$holidayMap[$date]['keterangan']:'';
                    echo '<td class="'.h(implode(' ',$classes)).'">';
                    echo '<button type="button" class="day-btn js-day" data-date="'.h($date).'" data-keterangan="'.h($ket).'" data-holiday="'.($isHoliday?'1':'0').'">';
                    echo '<span class="day-number">'.$day.'</span>';
                    if($isHoliday){echo '<i class="fa fa-check-circle holiday-check"></i><span class="holiday-label">'.h($ket).'</span>';}
                    elseif($isWeekend){echo '<span class="holiday-label" style="font-weight:normal;color:#999">Weekend</span>';}
                    echo '</button></td>';
                    $day++;
                }
                echo '</tr>';
            }
            ?>
            </tbody>
        </table>
        <div class="calendar-legend"><span class="legend-box"></span> Tanggal merah perusahaan. Sabtu/Minggu hari libur otomatis di Jadwal, tidak perlu dipilih di halaman ini</div>
    </div>

    <div class="holiday-list panel panel-default">
        <div class="panel-heading"><b>Daftar Tanggal Merah <?=h($namaBulan[$mon].' '.$year)?></b></div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" style="margin-bottom:0">
                <thead><tr><th style="width:150px">Tanggal</th><th>Keterangan</th><th style="width:180px">Dibuat Oleh</th><th style="width:120px">Aksi</th></tr></thead>
                <tbody>
                <?php if(empty($holidayMap)): ?>
                    <tr><td colspan="4" class="text-center text-muted">Belum ada tanggal merah custom pada bulan ini.</td></tr>
                <?php else: foreach($holidayMap as $tgl=>$r): ?>
                    <tr>
                        <td><?=h(date('d-m-Y',strtotime($tgl)))?></td>
                        <td><?=h($r['keterangan'])?></td>
                        <td><?=h($r['created_by'] ?: '-')?></td>
                        <td><button type="button" class="btn btn-xs btn-primary js-day" data-date="<?=h($tgl)?>" data-keterangan="<?=h($r['keterangan'])?>" data-holiday="1"><i class="fa fa-pencil"></i> Edit</button></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="holidayModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-calendar"></i> Atur Tanggal Merah</h4>
            </div>
            <div class="modal-body">
                <div class="form-group"><label>Tanggal</label><input type="text" id="modal-tanggal-view" class="form-control" readonly></div>
                <div class="form-group"><label>Nama / Keterangan Libur</label><input type="text" id="modal-keterangan" maxlength="150" class="form-control" placeholder="Contoh: Libur Idul Fitri / Cuti Bersama Perusahaan"></div>
                <div class="alert alert-info" style="margin-bottom:0">Tanggal yang disimpan akan otomatis diberi warna merah transparan pada kolom tanggal di Jadwal.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger pull-left" id="btn-hapus"><i class="fa fa-trash"></i> Hapus Tanggal Merah</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-simpan"><i class="fa fa-save"></i> Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-trash"></i> Konfirmasi Hapus</h4>
            </div>
            <div class="modal-body">
                <p style="margin:0">Hapus <b id="delete-date-view"></b> dari daftar tanggal merah?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" id="btn-batal-hapus" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btn-konfirmasi-hapus"><i class="fa fa-trash"></i> Ya, Hapus</button>
            </div>
        </div>
    </div>
</div>

<script src="../../js/jquery.js"></script>
<script src="../../js/bootstrap.min.js"></script>
<script>
(function(){
    var selectedDate='';
    var csrf=<?=json_encode($csrf)?>;
    var tableReady=<?= $tableReady ? 'true' : 'false' ?>;

    $('#pilih-bulan').on('change',function(){ if(this.value) window.location.href='?bulan='+encodeURIComponent(this.value); });

    $(document).on('click','.js-day',function(){
        if(!tableReady){ alert('Tabel qa_tanggal_merah belum tersedia. Import qa_tanggal_merah.sql terlebih dahulu.'); return; }
        selectedDate=String($(this).data('date')||'');
        var ket=String($(this).attr('data-keterangan')||'');
        var isHoliday=String($(this).attr('data-holiday')||'0')==='1';
        $('#modal-tanggal-view').val(selectedDate.split('-').reverse().join('-'));
        $('#modal-keterangan').val(ket);
        $('#btn-hapus').toggle(isHoliday);
        $('#holidayModal').modal('show');
        setTimeout(function(){$('#modal-keterangan').focus();},300);
    });

    function send(action,keterangan){
        var btns=$('#btn-simpan,#btn-hapus').prop('disabled',true);
        $.ajax({
            url:'tanggal_merah.php?bulan='+encodeURIComponent($('#pilih-bulan').val()),
            type:'POST',dataType:'json',
            data:{action:action,tanggal:selectedDate,keterangan:keterangan||'',csrf:csrf},
            success:function(res){
                btns.prop('disabled',false);
                if(res && res.status==='success') location.reload();
                else alert((res&&res.message)||'Gagal memproses tanggal merah.');
            },
            error:function(xhr){ btns.prop('disabled',false); alert(xhr.responseText||'Request gagal.'); }
        });
    }

    $('#btn-simpan').on('click',function(){
        var ket=$('#modal-keterangan').val().trim();
        if(!ket) ket='Tanggal Merah Perusahaan';
        send('save',ket);
    });
    $('#btn-hapus').on('click',function(){
        $('#delete-date-view').text(selectedDate.split('-').reverse().join('-'));
        $('#holidayModal').modal('hide');
        $('#holidayModal').one('hidden.bs.modal', function(){
            $('#deleteConfirmModal').modal('show');
        });
    });

    $('#btn-batal-hapus').on('click',function(){
        $('#deleteConfirmModal').one('hidden.bs.modal', function(){
            $('#holidayModal').modal('show');
        });
    });

    $('#btn-konfirmasi-hapus').on('click',function(){
        $(this).prop('disabled',true);
        send('delete','');
    });

    $('#deleteConfirmModal').on('hidden.bs.modal',function(){
        $('#btn-konfirmasi-hapus').prop('disabled',false);
    });
})();
</script>
</body>
</html>

<?php
require_once __DIR__.'/../../../inc/qa_rbac.php';
require_once __DIR__.'/email_helper.php';

/** Notify the configured validators after a schedule reaches their stage. */
function qaKirimEmailValidasiJadwal($db, $jenis, $periode, $pembuat) {
    if (!in_array($jenis,['PEMERIKSAAN','PERSETUJUAN'],true)
        || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',(string)$periode)) {
        return ['success'=>false,'message'=>'Parameter email jadwal tidak valid.'];
    }
    $check=@mysqli_query($db,"SHOW TABLES LIKE 'qa_email_validasi_penerima'");
    if (!$check || mysqli_num_rows($check)===0) {
        return ['success'=>false,'message'=>'Konfigurasi penerima email belum tersedia.'];
    }
    $stmt=$db->prepare("SELECT c.tipe_penerima,c.user_uid,c.email,u.uname,u.email_pembuat,u.block
        FROM qa_email_validasi_penerima c
        INNER JOIN `user` u ON u.uid=c.user_uid
        WHERE c.jenis_validasi=? AND c.status='aktif'
        ORDER BY c.tipe_penerima DESC,c.id ASC");
    if (!$stmt) return ['success'=>false,'message'=>'Daftar penerima email gagal dibaca.'];
    $stmt->bind_param('s',$jenis);
    $stmt->execute();
    $result=$stmt->get_result();
    $to=[]; $cc=[];
    $permission=$jenis==='PEMERIKSAAN'?'approval_pemeriksa':'approval_penyetuju';
    while ($row=$result->fetch_assoc()) {
        if (strtoupper((string)$row['block'])==='Y') continue;
        $email=trim((string)($row['email'] ?: $row['email_pembuat']));
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)) continue;
        $item=['email'=>$email,'name'=>trim((string)($row['uname'] ?: $row['user_uid']))];
        if ($row['tipe_penerima']==='TUJUAN' && qaRbacCanForUser($db,$row['user_uid'],$permission)) {
            $to[strtolower($email)]=$item;
        } elseif ($row['tipe_penerima']==='MENGETAHUI') {
            $cc[strtolower($email)]=$item;
        }
    }
    $stmt->close();
    if (!$to) return ['success'=>false,'message'=>'Belum ada penerima aktif untuk '.$jenis.'.'];
    $monthNames=[1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $label=$monthNames[(int)substr($periode,5,2)].' '.substr($periode,0,4);
    $stage=$jenis==='PEMERIKSAAN'?'Pemeriksa':'Penyetuju';
    $subject='Validasi Jadwal Kalibrasi '.$label.' - Menunggu '.$stage;
    $body='<p>Yth. Validator QA,</p><p>Jadwal kalibrasi <b>'.htmlspecialchars($label,ENT_QUOTES,'UTF-8')
        .'</b> telah diajukan oleh <b>'.htmlspecialchars((string)$pembuat,ENT_QUOTES,'UTF-8')
        .'</b> dan menunggu keputusan '.$stage.'.</p>'
        .'<p>Buka menu <b>Tabel &gt; Parameter Validasi Jadwal</b> pada Sistem QA untuk melihat layout jadwal bulanan dan harian, lalu berikan keputusan.</p>';
    $sent=0; $errors=[];
    foreach ($to as $item) {
        $mailCc=array_values(array_filter($cc,function($c) use ($item) { return strcasecmp($c['email'],$item['email'])!==0; }));
        $result=qaKirimEmail($item['email'],$item['name'],$subject,$body,$mailCc);
        if ($result['success']) $sent++;
        else $errors[]=$item['email'].': '.$result['message'];
    }
    return ['success'=>$sent===count($to),'sent'=>$sent,'total'=>count($to),
        'message'=>$errors ? implode('; ',$errors) : $sent.' email terkirim.'];
}

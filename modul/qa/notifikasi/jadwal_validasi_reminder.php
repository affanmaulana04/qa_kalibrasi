<?php
/** Called by the existing daily validation reminder after its mail helpers load. */
function qaJadwalReminderRun($db, $isTest, $testEmail, $magicLoginBaseUrl) {
    $summary=['sent'=>0,'cc'=>0,'processed'=>0,'test_user_found'=>false];
    $table=@mysqli_query($db,"SHOW TABLES LIKE 'qa_jadwal_validasi'");
    if (!$table || mysqli_num_rows($table)===0) return $summary;

    $year=(int)date('Y');
    $periods=['pemeriksa'=>[],'penyetuju'=>[]];
    $stmt=$db->prepare("SELECT periode,status_validasi,dibuat_oleh FROM qa_jadwal_validasi
        WHERE periode LIKE ? AND status_validasi IN ('menunggu_pemeriksa','menunggu_penyetuju') ORDER BY periode");
    if (!$stmt) { qaDailyValidasiLog('JADWAL | gagal membaca jadwal pending.'); return $summary; }
    $prefix=$year.'-%';
    $stmt->bind_param('s',$prefix);
    $stmt->execute();
    $result=$stmt->get_result();
    while ($row=$result->fetch_assoc()) {
        $stage=$row['status_validasi']==='menunggu_pemeriksa'?'pemeriksa':'penyetuju';
        $periods[$stage][]=$row;
    }
    $stmt->close();
    qaDailyValidasiLog('JADWAL PENDING | pemeriksa='.count($periods['pemeriksa']).' | penyetuju='.count($periods['penyetuju']));

    foreach (['pemeriksa'=>'PEMERIKSAAN','penyetuju'=>'PERSETUJUAN'] as $stage=>$jenis) {
        $pending=$periods[$stage];
        if (!$pending) continue;
        $users=qaAmbilPenerimaValidasi($db,$stage,$isTest,$testEmail);
        $list=qaJadwalReminderList($pending);
        $subject=($isTest?'[TEST] ':'').'Reminder Validasi Jadwal Kalibrasi - '.date('d.m.Y');
        $sentForStage=0;
        foreach ($users as $user) {
            $summary['test_user_found']=true;
            $summary['processed']++;
            $uid=trim((string)$user['uid']);
            $email=trim((string)$user['email_pembuat']);
            $name=trim((string)($user['uname'] ?: $uid));
            $magic=qaBuatMagicLoginValidasi($db,$uid,$email,strtolower($jenis),$magicLoginBaseUrl);
            if (empty($magic['success'])) {
                qaDailyValidasiLog('JADWAL TOKEN GAGAL | uid='.$uid.' | '.$magic['message']);
                continue;
            }
            $url=$magic['url'].'&target=jadwal';
            $html='<p>Yth. '.htmlspecialchars($name,ENT_QUOTES,'UTF-8').',</p>'
                .'<p>Jadwal kalibrasi berikut masih menunggu '.($stage==='pemeriksa'?'pemeriksaan':'persetujuan').' Anda:</p>'
                .$list
                .'<p><a href="'.htmlspecialchars($url,ENT_QUOTES,'UTF-8').'" style="display:inline-block;background:#12529A;color:#fff;padding:11px 18px;text-decoration:none;border-radius:4px">Buka Validasi Jadwal</a></p>'
                .'<p>Tautan login berlaku 24 jam. Anda juga dapat membuka Tabel &gt; Parameter Validasi Jadwal setelah login.</p>';
            $mail=qaKirimEmail($email,$name,$subject,$html);
            qaJadwalReminderLog($db,$uid,$email,$jenis,count($pending),$mail,$isTest,false);
            if (!empty($mail['success'])) {
                $summary['sent']++; $sentForStage++;
                qaDailyValidasiLog('JADWAL TERKIRIM | '.$jenis.' | uid='.$uid.' | pending='.count($pending));
            } else {
                qaCabutMagicLoginValidasi($db,(int)$magic['token_id']);
                qaDailyValidasiLog('JADWAL GAGAL | '.$jenis.' | uid='.$uid.' | '.$mail['message']);
            }
        }
        if (!$isTest && $sentForStage>0) {
            $summary['cc']+=qaJadwalReminderCc($db,$jenis,$subject,$list,count($pending));
        }
    }
    return $summary;
}

function qaJadwalReminderList($pending) {
    $months=[1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $html='<table border="1" cellspacing="0" cellpadding="7" style="border-collapse:collapse"><tr><th>Bulan</th><th>Diajukan oleh</th></tr>';
    foreach ($pending as $row) {
        $period=$row['periode'];
        $label=$months[(int)substr($period,5,2)].' '.substr($period,0,4);
        $html.='<tr><td>'.htmlspecialchars($label,ENT_QUOTES,'UTF-8').'</td><td>'.htmlspecialchars((string)$row['dibuat_oleh'],ENT_QUOTES,'UTF-8').'</td></tr>';
    }
    return $html.'</table>';
}

function qaJadwalReminderCc($db,$jenis,$subject,$list,$count) {
    $stmt=$db->prepare("SELECT c.user_uid,c.email,u.uname,u.email_pembuat,u.block
        FROM qa_email_validasi_penerima c JOIN `user` u ON u.uid=c.user_uid
        WHERE c.jenis_validasi=? AND c.tipe_penerima='MENGETAHUI' AND c.status='aktif'");
    if (!$stmt) return 0;
    $stmt->bind_param('s',$jenis); $stmt->execute(); $res=$stmt->get_result();
    $sent=0; $seen=[];
    while ($row=$res->fetch_assoc()) {
        if (strtoupper((string)$row['block'])==='Y') continue;
        $email=trim((string)($row['email'] ?: $row['email_pembuat']));
        if (!filter_var($email,FILTER_VALIDATE_EMAIL) || isset($seen[strtolower($email)])) continue;
        $seen[strtolower($email)]=true;
        $name=trim((string)($row['uname'] ?: $row['user_uid']));
        $html='<p>Yth. '.htmlspecialchars($name,ENT_QUOTES,'UTF-8').',</p><p>Salinan informasi jadwal kalibrasi yang menunggu validasi:</p>'.$list;
        $mail=qaKirimEmail($email,$name,$subject.' [Informasi]',$html);
        qaJadwalReminderLog($db,$row['user_uid'],$email,$jenis,$count,$mail,false,true);
        if (!empty($mail['success'])) $sent++;
    }
    $stmt->close();
    return $sent;
}

function qaJadwalReminderLog($db,$uid,$email,$jenis,$count,$mail,$isTest,$isCc) {
    $table=@mysqli_query($db,"SHOW TABLES LIKE 'qa_validation_email_log'");
    if (!$table || mysqli_num_rows($table)===0) return;
    $pemeriksaan=$jenis==='PEMERIKSAAN'?$count:0;
    $persetujuan=$jenis==='PERSETUJUAN'?$count:0;
    $status=!empty($mail['success'])?'sent':'failed';
    $note='[JADWAL] '.($isTest?'[TEST] ':'').($isCc?'[CC] ':'').$jenis.' | '.(string)$mail['message'];
    $stmt=$db->prepare('INSERT INTO qa_validation_email_log (user_uid,email,pending_pemeriksaan,pending_persetujuan,tanggal_kirim,status,keterangan) VALUES (?,?,?,?,NOW(),?,?)');
    if ($stmt) {
        $stmt->bind_param('ssiiss',$uid,$email,$pemeriksaan,$persetujuan,$status,$note);
        $stmt->execute(); $stmt->close();
    }
}

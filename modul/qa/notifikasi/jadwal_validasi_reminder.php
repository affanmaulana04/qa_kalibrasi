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
            $html=qaJadwalReminderEmail($name,$stage,$pending,$url);
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
            $summary['cc']+=qaJadwalReminderCc($db,$jenis,$subject,$pending);
        }
    }
    return $summary;
}

function qaJadwalReminderList($pending) {
    $months=[1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $border='border:1px solid #C9D2DC;padding:9px 12px;';
    $html='<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;border:1px solid #C9D2DC;font-size:13px;color:#33475B;">'
        .'<thead><tr style="background:#E9EEF3;color:#26384C;">'
        .'<th style="'.$border.'text-align:center;width:50px;">No</th>'
        .'<th style="'.$border.'text-align:left;">Bulan Jadwal</th>'
        .'<th style="'.$border.'text-align:left;">Diajukan oleh</th></tr></thead><tbody>';
    foreach ($pending as $index=>$row) {
        $period=(string)($row['periode'] ?? '');
        $month=(int)substr($period,5,2);
        $label=isset($months[$month]) ? $months[$month].' '.substr($period,0,4) : $period;
        $html.='<tr><td style="'.$border.'text-align:center;">'.((int)$index+1).'</td>'
            .'<td style="'.$border.'">'.htmlspecialchars($label,ENT_QUOTES,'UTF-8').'</td>'
            .'<td style="'.$border.'">'.htmlspecialchars((string)($row['dibuat_oleh'] ?? ''),ENT_QUOTES,'UTF-8').'</td></tr>';
    }
    return $html.'</table>';
}

function qaJadwalReminderEmail($name,$stage,$pending,$url='', $isCc=false, $isPreview=false) {
    $esc=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
    $checking=$stage==='pemeriksa';
    $stageLabel=$checking?'PEMERIKSAAN':'PERSETUJUAN';
    $badgeBg=$checking?'#F1F6FB':'#FFF8ED';
    $badgeBorder=$checking?'#9DB9D7':'#D6B17E';
    $badgeColor=$checking?'#24598B':'#8A5712';
    $count=count($pending);
    $date=date('d-m-Y');
    $intro=$isCc
        ? 'Salinan informasi jadwal kalibrasi yang sedang menunggu '.$stageLabel.'.'
        : 'Jadwal kalibrasi berikut menunggu '.strtolower($stageLabel).' Anda.';
    $html='<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>'
        .'<body style="margin:0;padding:0;background:#F3F5F7;font-family:Arial,Helvetica,sans-serif;color:#27384A;">'
        .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#F3F5F7;padding:24px 10px;"><tr><td align="center">'
        .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:900px;background:#FFFFFF;border:1px solid #D9E0E7;">'
        .'<tr><td style="padding:0;background:#123B6D;height:6px;font-size:0;line-height:0;">&nbsp;</td></tr>'
        .'<tr><td style="padding:20px 28px 18px;border-bottom:1px solid #D9E0E7;">'
        .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr>'
        .'<td style="vertical-align:middle;"><div style="font-size:28px;line-height:30px;font-weight:800;letter-spacing:-1px;color:#12529A;">TOTO</div>'
        .'<div style="margin-top:3px;font-size:12px;color:#6B7785;">Sistem QA Kalibrasi</div></td>'
        .'<td align="right" style="vertical-align:middle;"><div style="font-size:17px;font-weight:700;color:#26384C;">Reminder Validasi Jadwal</div>'
        .'<div style="margin-top:6px;"><span style="display:inline-block;padding:5px 9px;border:1px solid '.$badgeBorder.';background:'.$badgeBg.';color:'.$badgeColor.';font-size:11px;font-weight:700;">MENUNGGU '.$stageLabel.'</span></div></td>'
        .'</tr></table></td></tr>'
        .'<tr><td style="padding:24px 28px 12px;">'
        .'<p style="margin:0 0 12px;font-size:15px;line-height:22px;font-weight:700;color:#24364B;">Yth. '.$esc($name).',</p>'
        .'<p style="margin:0;font-size:14px;line-height:22px;color:#435466;">'.$esc($intro).'</p></td></tr>'
        .'<tr><td style="padding:8px 28px 18px;">'
        .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #D9E0E7;background:#FAFBFC;"><tr>'
        .'<td width="50%" style="padding:13px 16px;border-right:1px solid #D9E0E7;"><div style="font-size:11px;color:#6E7B88;">JADWAL PENDING</div><div style="margin-top:3px;font-size:17px;font-weight:700;color:#26384C;">'.$count.' bulan</div></td>'
        .'<td width="50%" style="padding:13px 16px;"><div style="font-size:11px;color:#6E7B88;">TANGGAL REMINDER</div><div style="margin-top:3px;font-size:14px;font-weight:700;color:#26384C;">'.$date.'</div></td>'
        .'</tr></table></td></tr>'
        .'<tr><td style="padding:0 28px 20px;">'.qaJadwalReminderList($pending).'</td></tr>';
    if ($isCc) {
        $html.='<tr><td style="padding:0 28px 22px;"><div style="padding:11px 13px;border-left:3px solid #9AA7B4;background:#F7F8FA;font-size:12px;line-height:18px;color:#5D6A78;">Salinan ini hanya untuk informasi dan tidak menyertakan tautan login otomatis.</div></td></tr>';
    } else {
        $html.='<tr><td style="padding:0 28px 24px;text-align:right;"><a href="'.$esc($url).'" style="display:inline-block;background:#123B6D;color:#FFFFFF;text-decoration:none;font-size:13px;font-weight:700;padding:11px 17px;border:1px solid #123B6D;">Buka Validasi Jadwal</a></td></tr>';
    }
    $footer=$isCc?'Email ini merupakan salinan informasi. Tidak ada hak login otomatis yang diberikan melalui email ini.'
        : ($isPreview?'Ini contoh tampilan email. Tautan menuju aplikasi lokal dan meminta login biasa.'
        : 'Tautan validasi bersifat pribadi, berlaku 24 jam, dan akan masuk otomatis sebagai akun penerima. Jangan meneruskan tautan ini.');
    return $html.'<tr><td style="padding:16px 28px 20px;border-top:1px solid #D9E0E7;background:#FAFBFC;">'
        .'<p style="margin:0;font-size:12px;line-height:18px;color:#4F5F70;">Terima kasih,<br><b>Sistem QA Kalibrasi</b></p>'
        .'<p style="margin:10px 0 0;font-size:10px;line-height:15px;color:#7B8794;">'.$esc($footer).'</p>'
        .'<p style="margin:5px 0 0;font-size:10px;color:#9AA3AC;">Email dikirim otomatis oleh Sistem QA.</p>'
        .'</td></tr></table></td></tr></table></body></html>';
}

function qaJadwalReminderCc($db,$jenis,$subject,$pending) {
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
        $html=qaJadwalReminderEmail($name,$jenis==='PEMERIKSAAN'?'pemeriksa':'penyetuju',$pending,'',true);
        $mail=qaKirimEmail($email,$name,$subject.' [Informasi]',$html);
        qaJadwalReminderLog($db,$row['user_uid'],$email,$jenis,count($pending),$mail,false,true);
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

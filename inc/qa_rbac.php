<?php
/**
 * QA role-based access control. Native mysqli / MyISAM.
 * Existing accounts without an assigned custom role keep legacy access.
 * Once a role is assigned, ONLY its enabled permissions apply (except Admin QA).
 * Never trust a role name/flags sent from browser or stored in session for access.
 */
function qaRbacCatalog()
{
    return [
        'kalibrasi'         => ['QA Kalibrasi', 'Cek / daftar alat, Update Hasil, dan cetak laporan alat'],
        'approval_pemeriksa'=> ['Validasi', 'Memeriksa hasil kalibrasi (tahap Pemeriksa)'],
        'approval_penyetuju'=> ['Validasi', 'Menyetujui hasil kalibrasi (tahap Penyetuju)'],
        'masterdata'        => ['Master Data', 'Melihat master alat dan jadwal di Master Data'],
        'jadwal_harian'     => ['Report', 'Melihat / mencetak jadwal kalibrasi harian'],
        'jadwal_bulanan'    => ['Report', 'Melihat / mencetak Format 1 — Daftar Rencana Kalibrasi Bulanan'],
        'jadwal_bulanan_internal' => ['Report', 'Melihat / mencetak Jadwal Kalibrasi Bulanan INTERNAL (R dan H)'],
        'jadwal_bulanan_external' => ['Report', 'Melihat / mencetak Jadwal Kalibrasi Bulanan EXTERNAL (R dan H kosong)'],
        'laporan_bbrau'     => ['Report', 'Melihat / mencetak laporan BBRAU'],
        'laporan_hasil'     => ['Report', 'Melihat laporan hasil kalibrasi'],
        'tanggal_merah'     => ['Administrasi', 'Mengatur tanggal merah perusahaan'],
        'pengguna'          => ['Administrasi', 'Mengelola user dan role (khusus Admin QA)'],
        'konfigurasi_email' => ['Administrasi', 'Mengatur email validasi'],
        'upload_master'     => ['Administrasi', 'Mengunggah master alat CSV'],
    ];
}
function qaRbacReady($db)
{
    static $ready = null;
    if ($ready !== null) return $ready;
    if (!($db instanceof mysqli)) return false;
    $names = ['qa_roles','qa_role_permissions','qa_user_roles'];
    foreach ($names as $name) {
        $r = @mysqli_query($db, "SHOW TABLES LIKE '$name'");
        if (!$r || mysqli_num_rows($r) === 0) return $ready = false;
    }
    return $ready = true;
}
function qaRbacUser($db)
{
    static $cached = null;
    if ($cached !== null) return $cached;
    if (empty($_SESSION['login']) || empty($_SESSION['userid'])) return $cached = [];
    $uid = (string)$_SESSION['userid'];
    $stmt = $db->prepare('SELECT uid,admin,role,block,pembuat,pemeriksa,penyetuju FROM `user` WHERE uid=? LIMIT 1');
    if (!$stmt) return $cached = [];
    $stmt->bind_param('s',$uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $cached = ($row ?: []);
}
function qaRbacIsAdmin($db)
{
    $user = qaRbacUser($db);
    return !empty($user) && strtoupper((string)($user['block'] ?? 'N')) !== 'Y'
        && in_array(strtoupper((string)($user['admin'] ?? 'N')), ['Y','1'], true);
}
function qaRbacAssignment($db)
{
    static $loaded=false,$result=null;
    if ($loaded) return $result;
    $loaded=true;
    if (!qaRbacReady($db)) return null;
    $user = qaRbacUser($db);
    if (!$user) return null;
    $uid = (string)$user['uid'];
    $stmt = $db->prepare('SELECT a.role_id,r.nama_role,r.aktif FROM qa_user_roles a LEFT JOIN qa_roles r ON r.id=a.role_id WHERE a.uid=? LIMIT 1');
    if (!$stmt) return ['invalid'=>true];
    $stmt->bind_param('s',$uid);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result = ($found ?: null);
}
function qaRbacCan($db,$permission)
{
    if (!array_key_exists($permission,qaRbacCatalog())) return false;
    if ($permission==='pengguna') return qaRbacIsAdmin($db);
    $user=qaRbacUser($db);
    if (!$user || strtoupper((string)($user['block'] ?? 'N')) === 'Y') return false;
    if (qaRbacIsAdmin($db)) return true;
    $assigned=qaRbacAssignment($db);
    if ($assigned !== null) {
        if (empty($assigned['role_id']) || (int)($assigned['aktif'] ?? 0) !== 1) return false;
        static $permissions=null;
        if ($permissions === null) {
            $permissions=[];
            $id=(int)$assigned['role_id'];
            $q=@mysqli_query($db,"SELECT permission_key FROM qa_role_permissions WHERE role_id=$id");
            if ($q) while($r=mysqli_fetch_assoc($q)) $permissions[$r['permission_key']]=true;
        }
        return !empty($permissions[$permission]);
    }
    // Legacy fallback applies ONLY to users without a custom role assignment.
    $flags = [
        'pembuat' => strtoupper((string)($user['pembuat'] ?? 'N')) === 'Y',
        'pemeriksa' => strtoupper((string)($user['pemeriksa'] ?? 'N')) === 'Y',
        'penyetuju' => strtoupper((string)($user['penyetuju'] ?? 'N')) === 'Y',
    ];
    $any = $flags['pembuat'] || $flags['pemeriksa'] || $flags['penyetuju'];
    $legacy=[
        'kalibrasi'=>$any,
        'approval_pemeriksa'=>$flags['pemeriksa'],
        'approval_penyetuju'=>$flags['penyetuju'],
        'masterdata'=>$any,
        'jadwal_harian'=>$any,
        'jadwal_bulanan'=>$any,
        'jadwal_bulanan_internal'=>$any,
        'jadwal_bulanan_external'=>$any,
        'laporan_bbrau'=>$any,
        'laporan_hasil'=>$any,
    ];
    return !empty($legacy[$permission]);
}
/** Izin tersendiri untuk setiap pilihan Format Jadwal Bulanan. */
function qaRbacMonthlyPermission($format)
{
    $map = [
        '1' => 'jadwal_bulanan',
        '2' => 'jadwal_bulanan_internal',
        '3' => 'jadwal_bulanan_external',
    ];
    return $map[(string)$format] ?? null;
}
function qaRbacCanAny($db,$keys)
{
    foreach ($keys as $key) if(qaRbacCan($db,$key)) return true;
    return false;
}
function qaRbacRequire($db,$permission,$json=false)
{
    if (qaRbacCan($db,$permission)) return;
    http_response_code(empty($_SESSION['login']) ? 401 : 403);
    if ($json) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status'=>'error','message'=>'Akses ditolak: tidak punya izin '.$permission.'.']);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<h3 style="font-family:Arial;padding:25px">Akses ditolak</h3>';
        echo '<p style="font-family:Arial;padding:0 25px">Role kamu tidak memiliki izin untuk fitur ini.</p>';
    }
    exit;
}
function qaRbacRequireAny($db,$permissions,$json=false)
{
    if (qaRbacCanAny($db,$permissions)) return;
    qaRbacRequire($db,reset($permissions),$json);
}
function qaRbacActiveRoles($db)
{
    $result=[];
    if (!qaRbacReady($db)) return $result;
    $q=mysqli_query($db,'SELECT id,nama_role FROM qa_roles WHERE aktif=1 ORDER BY nama_role ASC');
    if ($q) while ($r=mysqli_fetch_assoc($q)) $result[]=$r;
    return $result;
}
function qaRbacRoleForUser($db,$uid)
{
    if (!qaRbacReady($db)) return 0;
    $stmt=$db->prepare('SELECT role_id FROM qa_user_roles WHERE uid=? LIMIT 1');
    if (!$stmt) return 0;
    $stmt->bind_param('s',$uid);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();$stmt->close();
    return (int)($row['role_id']??0);
}
function qaRbacSetUserRole($db,$uid,$roleId)
{
    if (!qaRbacReady($db)) return false;
    if (!$roleId) {
        $stmt=$db->prepare('DELETE FROM qa_user_roles WHERE uid=?');
        if (!$stmt) return false;
        $stmt->bind_param('s',$uid);$ok=$stmt->execute();$stmt->close();
        return $ok;
    }
    $id=(int)$roleId;
    $test=mysqli_query($db,"SELECT id FROM qa_roles WHERE id=$id AND aktif=1 LIMIT 1");
    if (!$test || mysqli_num_rows($test) === 0) return false;
    $stmt=$db->prepare('INSERT INTO qa_user_roles (uid,role_id,updated_at) VALUES (?,?,NOW()) ON DUPLICATE KEY UPDATE role_id=VALUES(role_id),updated_at=NOW()');
    if (!$stmt) return false;
    $stmt->bind_param('si',$uid,$id);$ok=$stmt->execute();$stmt->close();
    return $ok;
}

/** Permission lookup for email magic-login target before switching session. */
function qaRbacCanForUser($db,$uid,$permission)
{
    if (!array_key_exists($permission,qaRbacCatalog())) return false;
    if ($permission==='pengguna') return false;
    $stmt=$db->prepare('SELECT admin,block,pembuat,pemeriksa,penyetuju FROM `user` WHERE uid=? LIMIT 1');
    if (!$stmt) return false;
    $stmt->bind_param('s',$uid);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();$stmt->close();
    if (!$row || strtoupper((string)($row['block']??'N'))==='Y') return false;
    if (in_array(strtoupper((string)($row['admin']??'N')),['Y','1'],true)) return true;
    if (qaRbacReady($db)) {
        $st=$db->prepare('SELECT r.id,r.aktif FROM qa_user_roles a LEFT JOIN qa_roles r ON r.id=a.role_id WHERE a.uid=? LIMIT 1');
        if (!$st) return false;
        $st->bind_param('s',$uid);$st->execute();$assigned=$st->get_result()->fetch_assoc();$st->close();
        if ($assigned) {
            if (empty($assigned['id']) || (int)$assigned['aktif']!==1) return false;
            $rid=(int)$assigned['id'];
            $st=$db->prepare('SELECT permission_key FROM qa_role_permissions WHERE role_id=? AND permission_key=? LIMIT 1');
            if (!$st) return false;
            $st->bind_param('is',$rid,$permission);$st->execute();$allowed=$st->get_result()->num_rows>0;$st->close();
            return $allowed;
        }
    }
    $map=['approval_pemeriksa'=>'pemeriksa','approval_penyetuju'=>'penyetuju','kalibrasi'=>'pembuat'];
    return isset($map[$permission]) && strtoupper((string)($row[$map[$permission]]??'N'))==='Y';
}

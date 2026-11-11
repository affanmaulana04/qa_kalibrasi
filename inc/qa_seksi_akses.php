<?php
require_once __DIR__ . '/qa_rbac.php';

function qaSeksiAksesReady($db) {
    static $ready = null;
    if ($ready !== null)
        return $ready;
    $q = @mysqli_query($db, "SHOW TABLES LIKE 'qa_user_seksi_akses'");
    return $ready = ($q && mysqli_num_rows($q) > 0);
}
function qaSeksiAkses($db, $uid = null) {
    if (qaRbacIsAdmin($db) && $uid === null)
        return null; // null = admin, semua seksi
    $uid = $uid === null ? (string)($_SESSION['userid'] ?? '') : (string)$uid;
    if ($uid === '' || !qaSeksiAksesReady($db))
        return [];
    static $cache = [];
    if (array_key_exists($uid, $cache))
        return $cache[$uid];
    $stmt = $db->prepare('SELECT nama_seksi FROM qa_user_seksi_akses WHERE uid=?');
    if (!$stmt)
        return [];
    $stmt->bind_param('s', $uid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $cache[$uid] = array_column($rows, 'nama_seksi');
}
function qaSeksiBoleh($db, $seksi) {
    $allowed = qaSeksiAkses($db);
    if ($allowed === null)
        return true;
    foreach ($allowed as $value)
        if (strcasecmp(trim($value), trim((string)$seksi)) === 0)
            return true;
    return false;
}
function qaSeksiRequire($db, $seksi, $json = false) {
    if (qaSeksiBoleh($db, $seksi))
        return;
    http_response_code(403);
    if ($json) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Seksi alat tidak diizinkan.']);
    } else
        echo 'Akses seksi alat ditolak.';
    exit;
}
function qaSeksiSql($db, $column, $uid = null) {
    $allowed = qaSeksiAkses($db, $uid);
    if ($allowed === null)
        return '1=1';
    if (!$allowed)
        return '1=0';
    return 'UPPER(TRIM(' . $column . ')) IN (' . implode(',', array_map(function ($s) use ($db) {
        return "UPPER('" . mysqli_real_escape_string($db, trim($s)) . "')";
    }, $allowed)) . ')';
}
function qaSeksiRequireNo($db, $no, $json = true) {
    if (qaRbacIsAdmin($db))
        return;
    $no = trim((string)$no);
    if ($no === '')
        return;
    foreach (['qa_part', 'qa_alat_ng', 'qa_alat_rusak', 'qa_alat_discontinue', 'qa_alat_ditarik'] as $table) {
        $stmt = $db->prepare("SELECT seksi_pemilik FROM `$table` WHERE UPPER(TRIM(no_part))=UPPER(?) OR alat_registry_id IN (SELECT id FROM qa_alat_registry WHERE UPPER(TRIM(no_part))=UPPER(?)) LIMIT 1");
        if (!$stmt)
            continue;
        $stmt->bind_param('ss', $no, $no);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            qaSeksiRequire($db, $row['seksi_pemilik'], $json);
            return true;
        }
    }
}
function qaSeksiRequirePengecekan($db, $id) {
    if (qaRbacIsAdmin($db))
        return;
    $id = (int)$id;
    if ($id < 1)
        return;
    $q = mysqli_query($db, "SELECT no_part,hasil_json FROM qa_pengecekan WHERE id=$id LIMIT 1");
    $row = $q?mysqli_fetch_assoc($q):null;
    if ($row && !qaSeksiRequireNo($db, $row['no_part'])) {
        $snapshot = json_decode((string)$row['hasil_json'], true);
        qaSeksiRequire($db, $snapshot['header']['seksi_pemilik'] ?? '', true);
    }
}

function qaSeksiRequirePayload($db, $payload) {
    if (qaRbacIsAdmin($db))
        return;
    $found = false;
    $walk = function ($node) use ($db, &$walk, &$found) {
        if (!is_array($node))
            return;
        foreach (['seksi', 'seksi_pemilik'] as $key)
            if (isset($node[$key]) && is_string($node[$key]) && strtoupper($node[$key]) !== 'ALL') {
            qaSeksiRequire($db, $node[$key], true);
            $found = true;
        }
        if (isset($node['no_part']) && is_string($node['no_part']))
            qaSeksiRequireNo($db, $node['no_part']);
        foreach ($node as $child)
            if (is_array($child))
                $walk($child);
    };
    $walk($payload);
    if (!$found)
        qaSeksiRequire($db, '', true);
}
function qaSeksiRequireLaporan($db, $id) {
    if (qaRbacIsAdmin($db))
        return;
    $id = (int)$id;
    $q = mysqli_query($db, "SELECT payload_json FROM qa_laporan_transaksi WHERE id=$id");
    $r = $q?mysqli_fetch_assoc($q):null;
    if ($r)
        qaSeksiRequirePayload($db,json_decode($r['payload_json'],true));
}

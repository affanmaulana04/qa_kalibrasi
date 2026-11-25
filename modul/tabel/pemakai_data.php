<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesi login tidak valid.']);
    exit;
}

require_once __DIR__ . '/../../inc/inc_koneksi.php';
require_once __DIR__ . '/../../inc/qa_rbac.php';

if (!qaRbacIsAdmin($konek)) {
    http_response_code(403);
    echo json_encode(['error' => 'Hanya Admin QA yang dapat melihat daftar user.']);
    exit;
}

function pemakaiDataHtml($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$draw = max(0, (int)($_POST['draw'] ?? 0));
$start = max(0, (int)($_POST['start'] ?? 0));
$length = (int)($_POST['length'] ?? 10);
$length = $length > 0 ? min($length, 100) : 10;
$searchInput = $_POST['search'] ?? [];
$search = trim((string)(is_array($searchInput) ? ($searchInput['value'] ?? '') : ''));
$search = substr($search, 0, 100);
$orderInput = $_POST['order'][0] ?? [];
$orderIndex = (int)(is_array($orderInput) ? ($orderInput['column'] ?? 0) : 0);
$orderDirection = strtolower((string)(is_array($orderInput) ? ($orderInput['dir'] ?? 'asc') : 'asc')) === 'desc' ? 'DESC' : 'ASC';
$response = ['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []];

try {
    if (!$konek->set_charset('utf8mb4')) {
        throw new RuntimeException('Charset koneksi tidak dapat diatur.');
    }
    $hasRoles = qaRbacReady($konek);
    $joins = $hasRoles
        ? ' LEFT JOIN qa_user_roles ur ON ur.uid=u.uid LEFT JOIN qa_roles r ON r.id=ur.role_id '
        : '';
    $roleLabel = $hasRoles
        ? "CASE WHEN ur.uid IS NOT NULL THEN COALESCE(NULLIF(r.nama_role,''),'Role Tidak Aktif') WHEN u.admin='Y' THEN 'Admin QA' ELSE CONCAT('Legacy: ',COALESCE(u.role,'')) END"
        : "CASE WHEN u.admin='Y' THEN 'Admin QA' ELSE CONCAT('Legacy: ',COALESCE(u.role,'')) END";

    $totalQuery = mysqli_query($konek, 'SELECT COUNT(*) AS total FROM `user`');
    if (!$totalQuery) {
        throw new RuntimeException(mysqli_error($konek));
    }
    $response['recordsTotal'] = (int)mysqli_fetch_assoc($totalQuery)['total'];

    $where = '';
    $term = '%' . $search . '%';
    if ($search !== '') {
        $where = " WHERE u.uid LIKE ? OR u.uname LIKE ? OR u.seksi LIKE ? OR $roleLabel LIKE ?";
        $countStmt = $konek->prepare("SELECT COUNT(*) AS total FROM `user` u $joins $where");
        if (!$countStmt) {
            throw new RuntimeException(mysqli_error($konek));
        }
        $countStmt->bind_param('ssss', $term, $term, $term, $term);
        $countStmt->execute();
        $response['recordsFiltered'] = (int)$countStmt->get_result()->fetch_assoc()['total'];
        $countStmt->close();
    } else {
        $response['recordsFiltered'] = $response['recordsTotal'];
    }

    $orderColumns = [
        'u.uid', 'u.uname', 'role_label', 'u.seksi', 'u.admin',
        'u.pembuat', 'u.pemeriksa', 'u.penyetuju', 'u.block', 'u.online'
    ];
    $orderBy = $orderColumns[$orderIndex] ?? 'u.uid';
    $sql = "SELECT u.uid,u.uname,u.seksi,u.admin,u.pembuat,u.pemeriksa,"
        . "u.penyetuju,u.block,u.online,$roleLabel AS role_label "
        . "FROM `user` u $joins $where ORDER BY $orderBy $orderDirection,u.uid ASC LIMIT ?,?";
    $stmt = $konek->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException(mysqli_error($konek));
    }
    if ($search !== '') {
        $stmt->bind_param('ssssii', $term, $term, $term, $term, $start, $length);
    } else {
        $stmt->bind_param('ii', $start, $length);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $uid = (string)$row['uid'];
        $safeUid = pemakaiDataHtml($uid);
        $encodedUid = pemakaiDataHtml(rawurlencode($uid));
        $actions = '<a class="btn btn-xs btn-warning" href="pemakai_edit.php?xuid=' . $encodedUid
            . '"><i class="fa fa-pencil"></i> Edit</a> '
            . '<a class="btn btn-xs btn-primary" href="pemakai_resetpwd.php?xuid=' . $encodedUid
            . '"><i class="fa fa-refresh"></i> Reset Password</a>';
        if (strtolower($uid) !== 'admin') {
            $actions .= ' <button type="button" class="btn btn-xs btn-danger btn-hapus" data-uid="'
                . $safeUid . '"><i class="fa fa-trash"></i> Hapus</button>';
        }
        $tick = static function ($value) {
            return strtoupper((string)$value) === 'Y' ? '✓' : '';
        };
        $response['data'][] = [
            '<b>' . $safeUid . '</b>',
            pemakaiDataHtml($row['uname']),
            pemakaiDataHtml($row['role_label']),
            pemakaiDataHtml($row['seksi']),
            $tick($row['admin']),
            $tick($row['pembuat']),
            $tick($row['pemeriksa']),
            $tick($row['penyetuju']),
            $tick($row['block']),
            $tick($row['online']) === '✓' ? '●' : '',
            $actions
        ];
    }
    $stmt->close();
} catch (Throwable $error) {
    error_log('QA pemakai_data: ' . $error->getMessage());
    http_response_code(500);
    $response['error'] = 'Daftar user gagal dimuat. Hubungi admin sistem.';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

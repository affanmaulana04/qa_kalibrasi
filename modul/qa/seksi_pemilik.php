<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['status'=>'error','message'=>'Sesi login tidak valid.'], JSON_UNESCAPED_UNICODE);
    exit;
}

include "../../inc/inc_koneksi.php";

$sql = "SELECT id, kode_pemilik, nama_pemilik FROM qa_seksi_pemilik WHERE status='aktif' ORDER BY kode_pemilik ASC";
$res = $konek->query($sql);
$data = [];
while ($row = $res->fetch_assoc()) $data[] = $row;

echo json_encode(['status'=>'success','data'=>$data], JSON_UNESCAPED_UNICODE);

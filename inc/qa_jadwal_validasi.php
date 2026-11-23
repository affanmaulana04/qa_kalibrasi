<?php
/** One approval applies to monthly and daily schedule headers for a calendar month. */
function qaJadwalEnsure($db) {
    return mysqli_query($db, "CREATE TABLE IF NOT EXISTS qa_jadwal_validasi (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        periode CHAR(7) NOT NULL,
        status_validasi VARCHAR(30) NOT NULL DEFAULT 'menunggu_pemeriksa',
        dibuat_oleh VARCHAR(100) NOT NULL,
        dibuat_at DATETIME NOT NULL,
        diperiksa_oleh VARCHAR(100) NULL,
        diperiksa_at DATETIME NULL,
        disetujui_oleh VARCHAR(100) NULL,
        disetujui_at DATETIME NULL,
        keterangan VARCHAR(500) NULL,
        updated_at DATETIME NOT NULL,
        UNIQUE KEY uq_periode (periode)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function qaJadwalGet($db, $periode) {
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string)$periode)) return null;
    $stmt=$db->prepare('SELECT * FROM qa_jadwal_validasi WHERE periode=? LIMIT 1');
    if (!$stmt) return null;
    $stmt->bind_param('s',$periode);
    $stmt->execute();
    $row=$stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}
function qaJadwalTanggalTtd($value) {
    $value = trim((string)$value);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:\s|$)/', $value, $m)) return '';
    return $m[3] . '-' . $m[2] . '-' . $m[1];
}
function qaJadwalSignatures($db, $periode) {
    if (!qaJadwalEnsure($db)) return ['dibuat'=>'','dibuat_tanggal'=>'','diperiksa'=>'','diperiksa_tanggal'=>'','disetujui'=>'','disetujui_tanggal'=>''];
    $row=qaJadwalGet($db,$periode);
    $diperiksa=in_array($row['status_validasi'] ?? '', ['menunggu_penyetuju','final_disetujui','ditolak_penyetuju'],true);
    $disetujui=($row['status_validasi'] ?? '')==='final_disetujui';
    return [
        'dibuat'=>$row['dibuat_oleh'] ?? '',
        'dibuat_tanggal'=>qaJadwalTanggalTtd($row['dibuat_at'] ?? ''),
        'diperiksa'=>$diperiksa ? ($row['diperiksa_oleh'] ?? '') : '',
        'diperiksa_tanggal'=>$diperiksa ? qaJadwalTanggalTtd($row['diperiksa_at'] ?? '') : '',
        'disetujui'=>$disetujui ? ($row['disetujui_oleh'] ?? '') : '',
        'disetujui_tanggal'=>$disetujui ? qaJadwalTanggalTtd($row['disetujui_at'] ?? '') : ''
    ];
}

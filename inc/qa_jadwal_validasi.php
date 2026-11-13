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
function qaJadwalSignatures($db, $periode) {
    if (!qaJadwalEnsure($db)) return ['dibuat'=>'','diperiksa'=>'','disetujui'=>''];
    $row=qaJadwalGet($db,$periode);
    return [
        'dibuat'=>$row['dibuat_oleh'] ?? '',
        'diperiksa'=>in_array($row['status_validasi'] ?? '', ['menunggu_penyetuju','final_disetujui','ditolak_penyetuju'],true) ? ($row['diperiksa_oleh'] ?? '') : '',
        'disetujui'=>($row['status_validasi'] ?? '')==='final_disetujui' ? ($row['disetujui_oleh'] ?? '') : ''
    ];
}

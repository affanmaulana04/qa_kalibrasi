<?php
/** Tanggal jatuh tempo = tanggal aktual + periode hari; tanggal efektif digeser H+ hingga hari kerja. */
function qaJadwalEfektif($db, $tanggal, $periode = 0) {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr((string)$tanggal, 0, 10));
    if (!$date || $date->format('Y-m-d') !== substr((string)$tanggal, 0, 10))
        return null;
    $date = $date->modify('+' . max(0, (int)$periode) . ' days');
    $holidays = [];
    $q = mysqli_query($db, 'SELECT tanggal FROM qa_tanggal_merah');
    if (!$q)
        throw new RuntimeException('Kalender tanggal merah belum tersedia.');
    while ($r = mysqli_fetch_assoc($q))
        $holidays[substr((string)$r['tanggal'], 0, 10)] = true;
    // Jumlah tanggal tersimpan membatasi panjang rentang libur; weekend ditangani berulang.
    $limit = (count($holidays) + 1) * 7;
    for ($i = 0; $i < $limit; $i++) {
        $day = $date->format('Y-m-d');
        if ((int)$date->format('N') < 6 && !isset($holidays[$day]))
            return $day;
        $date = $date->modify('+1 day');
    }
    return null;
}
function qaSinkronJadwalMendatang($db) {
    $today = date('Y-m-d');
    $q = @mysqli_query($db, "SELECT id,no_part,periode_kalibrasi,jadwal_kalibrasi FROM qa_part WHERE status='aktif' AND jadwal_kalibrasi >= '$today'");
    if (!$q)
        return;
    $st = $db->prepare('UPDATE qa_part SET jadwal_kalibrasi=? WHERE id=?');
    if (!$st)
        return;
    while ($part = mysqli_fetch_assoc($q)) {
        $no = mysqli_real_escape_string($db, $part['no_part']);
        $h = @mysqli_query($db, "SELECT tanggal_pengecekan FROM qa_pengecekan WHERE no_part='$no' AND (status_validasi='final_disetujui' OR jenis_transaksi='registrasi_alat') ORDER BY tanggal_pengecekan DESC,id DESC LIMIT 1");
        $last = $h?mysqli_fetch_assoc($h):null;
        $id = (int)$part['id'];
        $a = mysqli_query($db, "SELECT tanggal_asli,tanggal_efektif FROM qa_jadwal_acuan WHERE alat_id=$id");
        $saved = $a?mysqli_fetch_assoc($a):null;
        if ($last)
            $raw = date('Y-m-d', strtotime($last['tanggal_pengecekan'] . ' +' . max(0, (int)$part['periode_kalibrasi']) . ' days'));
        elseif ($saved && $saved['tanggal_efektif'] === substr($part['jadwal_kalibrasi'], 0, 10))
            $raw = $saved['tanggal_asli'];
        else
            $raw = substr($part['jadwal_kalibrasi'], 0, 10);
        $new = qaJadwalEfektif($db, $raw);
        if ($new) {
            $save = $db->prepare('REPLACE INTO qa_jadwal_acuan (alat_id,tanggal_asli,tanggal_efektif) VALUES (?,?,?)');
            if (!$save)
                throw new RuntimeException('Import SQL jadwal dahulu.');
            $save->bind_param('iss', $id, $raw, $new);
            if (!$save->execute())
                throw new RuntimeException('Gagal menyimpan acuan jadwal.');
            $save->close();
        }
        if ($new && $new !== substr((string)$part['jadwal_kalibrasi'], 0, 10)) {
            $id = (int)$part['id'];
            $st->bind_param('si', $new, $id);
            $st->execute();
        }
    }
    $st->close();
}

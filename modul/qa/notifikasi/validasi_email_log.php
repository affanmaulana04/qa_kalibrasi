<?php

/** Catat hasil pengiriman tanpa menghentikan reminder jika database log gagal. */
function qaSimpanLogEmailValidasi($konek, $uid, $email, $jenis, $jumlahPending, $hasil, $isTest = false, $isCc = false)
{
    $stmt = null;
    try {
        $pemeriksaan = $jenis === 'PEMERIKSAAN' ? (int)$jumlahPending : 0;
        $persetujuan = $jenis === 'PERSETUJUAN' ? (int)$jumlahPending : 0;
        $status = !empty($hasil['success']) ? 'sent' : 'failed';
        $keterangan = ($isTest ? '[TEST] ' : '') . ($isCc ? '[CC] ' : '')
            . $jenis . ' | ' . (string)($hasil['message'] ?? 'Hasil pengiriman tidak tersedia');
        $stmt = $konek->prepare(
            'INSERT INTO qa_validation_email_log
             (user_uid, email, pending_pemeriksaan, pending_persetujuan, tanggal_kirim, status, keterangan)
             VALUES (?, ?, ?, ?, NOW(), ?, ?)'
        );
        if (!$stmt) {
            throw new RuntimeException($konek->error);
        }
        $stmt->bind_param('ssiiss', $uid, $email, $pemeriksaan, $persetujuan, $status, $keterangan);
        if (!$stmt->execute()) {
            throw new RuntimeException($stmt->error);
        }
        return true;
    } catch (Throwable $e) {
        qaDailyValidasiLog('GAGAL SIMPAN LOG DATABASE | ' . $e->getMessage());
        return false;
    } finally {
        if ($stmt) {
            $stmt->close();
        }
    }
}

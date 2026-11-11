-- OPTIONAL: Gunakan hanya jika akun MySQL web tidak memiliki izin CREATE TABLE.
-- Kalau permission CREATE TABLE tersedia, upload_master_csv.php membuat tabel otomatis.
-- TIDAK ada InnoDB/foreign key/transaction.
CREATE TABLE IF NOT EXISTS `qa_migrasi_alat_meta` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `no_part` varchar(50) NOT NULL,
  `alat_id` int unsigned NOT NULL,
  `tanggal_beli` date DEFAULT NULL,
  `status_standar` enum('BELUM_DIISI','DRAFT','TERKUNCI') NOT NULL DEFAULT 'BELUM_DIISI',
  `import_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qa_mig_meta_no` (`no_part`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `qa_migrasi_bbrau` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `no_part` varchar(50) NOT NULL,
  `tanggal` date NOT NULL,
  `tipe_riwayat` enum('REGISTRASI','CHECK') NOT NULL DEFAULT 'CHECK',
  `jenis_check` varchar(40) NOT NULL DEFAULT '',
  `hasil` varchar(50) NOT NULL DEFAULT '',
  `petugas` varchar(100) NOT NULL DEFAULT '',
  `diperiksa` varchar(100) NOT NULL DEFAULT '',
  `pelaksanaan` varchar(50) NOT NULL DEFAULT '',
  `keterangan` varchar(255) NOT NULL DEFAULT '',
  `fingerprint` char(64) NOT NULL,
  `import_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qa_mig_bbrau_fingerprint` (`fingerprint`),
  KEY `idx_qa_mig_bbrau_no_tanggal` (`no_part`,`tanggal`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

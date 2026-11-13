CREATE TABLE IF NOT EXISTS qa_jadwal_validasi (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

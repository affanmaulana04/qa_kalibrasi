-- ================================================================
-- QA KALIBRASI - CLEAN MIGRATION
-- Database : qa_kalibrasi
--
-- Catatan:
-- 1. Jalankan setelah memilih database qa_kalibrasi di phpMyAdmin.
-- 2. Script ini sengaja TIDAK menggunakan INFORMATION_SCHEMA.
--    Struktur flag user (pembuat/pemeriksa/penyetuju/block) sudah
--    dipastikan dari modul aplikasi menggunakan SHOW COLUMNS.
-- 3. operator/status_validasi pada qa_pengecekan juga dibuat otomatis
--    oleh modul QA saat pertama kali dipanggil.
-- ================================================================

USE `qa_kalibrasi`;

-- ---------------------------------------------------------------
-- 1) Audit validator: Pemeriksa -> Penyetuju
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qa_validasi` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `pengecekan_id` INT NOT NULL,
    `tahap` ENUM('pemeriksa','penyetuju') NOT NULL,
    `keputusan` ENUM('setuju','tolak') NOT NULL,
    `nama_validator` VARCHAR(100) NOT NULL,
    `keterangan` VARCHAR(500) NULL,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_qa_validasi_pengecekan` (`pengecekan_id`),
    KEY `idx_qa_validasi_tahap` (`tahap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- 2) Arsip alat berdasarkan hasil final
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qa_alat_rusak` LIKE `qa_part`;
CREATE TABLE IF NOT EXISTS `qa_alat_ng` LIKE `qa_part`;
CREATE TABLE IF NOT EXISTS `qa_alat_discontinue` LIKE `qa_part`;
CREATE TABLE IF NOT EXISTS `qa_alat_ditarik` LIKE `qa_part`;

-- ---------------------------------------------------------------
-- 3) User QA untuk testing workflow validator
--    Password login: qa12345
--    Kolom pembuat/pemeriksa/penyetuju/block berasal dari struktur user
--    aplikasi yang sudah dipakai modul QA.
-- ---------------------------------------------------------------
INSERT INTO `user`
(`uid`,`uname`,`pword`,`admin`,`role`,`sapaan`,`seksi`,`email_pembuat`,`pembuat`,`pemeriksa`,`penyetuju`,`block`,`createdby`,`crtdate`)
SELECT 'qa_pembuat','QA Pembuat',MD5('qa12345'),'N','QA','Bpk.','QA','', 'Y','N','N','N','admin',NOW()
WHERE NOT EXISTS (SELECT 1 FROM `user` WHERE `uid`='qa_pembuat');

INSERT INTO `user`
(`uid`,`uname`,`pword`,`admin`,`role`,`sapaan`,`seksi`,`email_pembuat`,`pembuat`,`pemeriksa`,`penyetuju`,`block`,`createdby`,`crtdate`)
SELECT 'qa_pemeriksa','QA Pemeriksa',MD5('qa12345'),'N','QA','Bpk.','QA','', 'N','Y','N','N','admin',NOW()
WHERE NOT EXISTS (SELECT 1 FROM `user` WHERE `uid`='qa_pemeriksa');

INSERT INTO `user`
(`uid`,`uname`,`pword`,`admin`,`role`,`sapaan`,`seksi`,`email_pembuat`,`pembuat`,`pemeriksa`,`penyetuju`,`block`,`createdby`,`crtdate`)
SELECT 'qa_penyetuju','QA Penyetuju',MD5('qa12345'),'N','QA','Bpk.','QA','', 'N','N','Y','N','admin',NOW()
WHERE NOT EXISTS (SELECT 1 FROM `user` WHERE `uid`='qa_penyetuju');

-- ---------------------------------------------------------------
-- 4) Verifikasi
-- ---------------------------------------------------------------
SELECT uid, uname, admin, role, pembuat, pemeriksa, penyetuju, block
FROM `user`
WHERE uid IN ('admin','qa_pembuat','qa_pemeriksa','qa_penyetuju')
ORDER BY uid;

SELECT TABLE_NAME
FROM (
    SELECT 'qa_part' AS TABLE_NAME
    UNION ALL SELECT 'qa_pengecekan'
    UNION ALL SELECT 'qa_validasi'
    UNION ALL SELECT 'qa_alat_rusak'
    UNION ALL SELECT 'qa_alat_ng'
    UNION ALL SELECT 'qa_alat_discontinue'
    UNION ALL SELECT 'qa_alat_ditarik'
) AS expected_tables;

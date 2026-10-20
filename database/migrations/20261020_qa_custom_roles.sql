-- Jalankan sekali di database qa_kalibrasi lewat phpMyAdmin sebelum membuka menu Custom Role.
-- Tabel existing `user` tidak diubah. Tidak memakai InnoDB, transaction, atau foreign key.
CREATE TABLE IF NOT EXISTS `qa_roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_role` VARCHAR(70) NOT NULL,
  `deskripsi` VARCHAR(255) NOT NULL DEFAULT '',
  `aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nama_role` (`nama_role`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `qa_role_permissions` (
  `role_id` INT UNSIGNED NOT NULL,
  `permission_key` VARCHAR(64) NOT NULL,
  PRIMARY KEY (`role_id`,`permission_key`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `qa_user_roles` (
  `uid` VARCHAR(60) NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`uid`),
  KEY `idx_qa_user_role` (`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

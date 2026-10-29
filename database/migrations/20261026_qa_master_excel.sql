-- Additive, repeatable migration for complete master registration imports.
-- MariaDB (XAMPP). No existing values or lifecycle/history records are changed.
ALTER TABLE qa_part
    ADD COLUMN IF NOT EXISTS fungsi TEXT NULL,
    ADD COLUMN IF NOT EXISTS buatan VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS tanggal_registrasi DATE NULL;

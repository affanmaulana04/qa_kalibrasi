# Struktur Modular QA

Refactor ini hanya memindahkan file ke folder fitur dan memperbarui path internal. Business logic, query, form, layout laporan, status, dan alur approval tidak diubah.

## Struktur `modul/qa`

```text
modul/qa/
├── kalibrasi/
│   ├── cek_alat.php
│   ├── proses_qa.php
│   ├── cetak_laporan.php
│   └── daftar_kalibrasi.php
├── approval/
│   ├── transaksi_kalibrasi.php
│   └── validasi_login.php
├── masterdata/
│   └── masterdata.php
├── tanggal_merah/
│   └── tanggal_merah.php
├── jadwal_bulanan/
│   ├── laporan_jadwal_bulanan.php
│   ├── cetak_jadwal_bulanan.php
│   ├── cetak_jadwal_bulanan_format1.php
│   └── jadwal_bulanan_format1_tabel.php
├── jadwal_harian/
│   └── cetak_jadwal.php
├── laporan_bbrau/
│   └── laporan_bbrau.php
├── laporan_hasil/
│   ├── laporan_hasil_kalibrasi.php
│   └── report_qa.php
└── notifikasi/
    ├── daily_validasi_reminder.php
    ├── daily_validasi_reminder_terintegrasi.php
    ├── weekly_reminder.php
    ├── email_helper.php
    ├── email_validasi_pemeriksa.php
    ├── email_validasi_penyetuju.php
    ├── validasi_email_log.php
    └── report.email.php
```

Tidak ada compatibility wrapper di `modul/qa/`. Folder tersebut hanya berisi subfolder fitur.

## Koneksi database

Setiap endpoint yang membutuhkan database tetap membuka `inc/inc_koneksi.php` pada request-nya sendiri menggunakan path berbasis `__DIR__`. `inc/inc_koneksi.php` juga memuat `fungsi_validasi.php` menggunakan `__DIR__`, sehingga tidak bergantung pada current working directory Apache/CLI.

Tidak ada perubahan schema database pada refactor modular ini.

## Entry point menu utama

- Check / Update QA Alat: `modul/qa/kalibrasi/cek_alat.php`
- Transaksi Kalibrasi: `modul/qa/approval/transaksi_kalibrasi.php`
- Custom Tanggal Merah: `modul/qa/tanggal_merah/tanggal_merah.php`
- Laporan BBRAU: `modul/qa/laporan_bbrau/laporan_bbrau.php`
- Laporan Jadwal Bulanan: `modul/qa/jadwal_bulanan/laporan_jadwal_bulanan.php`

## Scheduler

`scheduler_weekly_qa.bat` sudah diarahkan ke `modul/qa/notifikasi/weekly_reminder.php`.

# Upload master alat dari Excel

Di halaman **Upload Master Data Excel / CSV**, klik **Unduh Template Excel**. Satu file memuat 26 tab format A sampai Z, termasuk S (Gelas ukur) dan Z (Blow Timer). Nama format mengikuti daftar alat yang diberikan pengguna; nama tab disingkat agar sesuai batas Excel.

1. Buka **Mulai di Sini**, lalu pilih tab format alat.
2. Semua tab kosong. Isi satu alat per baris mulai baris 2. Judul kolom bertanda **HEADER LAPORAN**, **HEADER LAPORAN + BBRAU**, atau **FOOTER LAPORAN + BBRAU**. No. Alat, Nama Alat, Seksi, Periode Kalibrasi, dan Tanggal Registrasi wajib diisi. Rencana Kalibrasi boleh kosong; sistem menghitungnya dari tanggal registrasi + periode hari.
3. Simpan sebagai `.xlsx`, lalu upload. Alat baru masuk sebagai **Register** dan halaman kalibrasi membuka alat pertama dalam file. Isi **Titik Standar Awal** di sana. Untuk alat lain dalam file, cari No. Alat pada halaman yang sama.

Upload hanya mendaftarkan No. Alat yang belum ada; alat yang sudah terdaftar dilewati tanpa perubahan. Gunakan format sel **Text** untuk nomor dengan nol di depan. Batas file 2 MB dan 1.000 alat.

CSV lama tetap dapat diunggah untuk metadata dasar. Workbook lama dengan sheet `Data Alat` dan `Titik Standar` masih bisa diproses untuk kompatibilitas, tetapi tidak lagi menjadi template yang diunduh.

## Pemasangan database

Jalankan `database/migrations/20261026_qa_master_excel.sql` pada database QA. Migrasi MariaDB ini dapat dijalankan ulang dan hanya menambah kolom `fungsi`, `buatan`, dan `tanggal_registrasi` pada `qa_part`; tidak mengubah data alat yang ada.

Master dan registry harus memakai engine yang sama. **InnoDB** menggunakan transaksi dan membatalkan batch saat penyimpanan gagal. **MyISAM** tetap memakai table locks; kegagalan saat penulisan dapat meninggalkan sebagian batch karena MyISAM tidak menyediakan rollback. Keduanya memvalidasi seluruh file sebelum menulis dan memeriksa ulang kondisi data sebelum menyimpan.

## Verifikasi pengembang

```powershell
C:/xampp/php/php.exe tests/qa_master_workbook_test.php
C:/xampp/php/php.exe tests/qa_master_csv_test.php
node tests/qa_csv_upload_http_test.cjs
```

Fixture pengujian disimpan pada tabel sementara di koneksi pengujian, bukan tabel data alat sebenarnya.

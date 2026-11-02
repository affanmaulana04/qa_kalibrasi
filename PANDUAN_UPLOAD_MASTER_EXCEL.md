# Upload master alat dari Excel

Di halaman **Upload Master Data Excel / CSV**, klik **Unduh Template Excel**. Satu file memuat 24 tab format A?R dan T?Y. Format S dan Z menunggu form body/laporan. Nama format mengikuti daftar alat yang diberikan pengguna; nama tab disingkat agar sesuai batas Excel.

1. Buka **Mulai di Sini**, lalu pilih tab format alat.
2. Isi satu alat per baris mulai baris 3. Judul kolom bertanda **HEADER LAPORAN**, **HEADER LAPORAN + BBRAU**, atau **FOOTER LAPORAN + BBRAU**. No. Alat, Nama Alat, Seksi, Periode Kalibrasi, dan Tanggal Registrasi wajib diisi. Rencana Kalibrasi boleh kosong; sistem menghitungnya dari tanggal registrasi + periode hari.
3. Biarkan baris kuning contoh. Contoh dari masterdata asli ikut aturan No. Alat: didaftarkan bila belum ada, dilewati bila sudah ada. Contoh ilustratif selalu dilewati. Jangan menulis data alat di baris contoh. Tab lain boleh dibiarkan. Simpan sebagai `.xlsx`.
4. Upload file. Alat baru masuk sebagai **Register** dan halaman kalibrasi membuka alat pertama dalam file. Isi **Titik Standar Awal** di sana, lalu simpan. Setelah itu form kalibrasi biasa tampil. Untuk alat lain dalam file, cari No. Alat tersebut pada halaman yang sama.

Body/titik standar tidak ada dalam template baru. Upload hanya mendaftarkan No. Alat yang belum ada; alat yang sudah terdaftar dilewati tanpa perubahan. Contoh tanpa masterdata diberi label ilustrasi dan tidak pernah diimpor. Gunakan format sel **Text** untuk nomor dengan nol di depan. Batas file 2 MB dan 1.000 alat.

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

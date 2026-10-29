# Upload master alat lengkap dari Excel

Di **Upload Master Data Excel / CSV**, langsung klik **Unduh Template Excel**. Satu template universal mendukung seluruh format registrasi aplikasi: A–R dan T–Y. Tidak perlu memilih format sebelum download; satu file dapat memuat alat dengan format berbeda.

1. Buka sheet **Mulai di Sini** dan ikuti empat langkah singkat di sana.
2. Buka tab sesuai jenis alat, misalnya **A - Gauge**, **F - Timbangan**, atau **Y - Suhu Humidity**. Setiap format mempunyai tab sendiri dan hanya menampilkan kolom yang relevan.
3. Isi **No. Alat**, kolom **DATA | ...**, dan kolom **TITIK | ...** pada satu baris. Format laporan diambil otomatis dari nama tab sehingga tidak perlu diketik.
4. Jika satu alat mempunyai beberapa titik standar, tambah baris dengan **No. Alat yang sama**. Kolom DATA pada baris lanjutan boleh kosong; isi kolom TITIK untuk setiap titik. Tab yang tidak digunakan dibiarkan kosong.
4. Simpan sebagai **Excel Workbook (.xlsx)** lalu **Upload & Simpan**. Nama sheet dan judul kolom harus dipertahankan. File `.xls`, macro, password, dan rumus tidak didukung; tempel rumus sebagai nilai terlebih dahulu.

**Tanggal Registrasi** adalah tanggal acuan alat, termasuk jika melakukan backdate. Jika Rencana Kalibrasi kosong, aplikasi menghitung tanggal acuan + periode hari. Alternatifnya, isi Rencana Kalibrasi langsung. Tanggal acuan disimpan sebagai `qa_part.tanggal_registrasi`; tidak mengubah timestamp pembuatan record atau histori lama. Tanggal menerima `YYYY-MM-DD`, `DD-MM-YYYY`, `DD/MM/YYYY`, atau tanggal numerik Excel (sistem 1900/1904).

Gunakan format sel **Text** untuk nomor alat, aset, seri, dan nomor alat standar agar nol di depan tetap tersimpan. Template menggunakan format teks. Batas per file: **2 MB, 1.000 alat, dan 20.000 baris titik standar**.

No. Alat baru menambah master dan registry. No. Alat yang sudah ada memperbarui master aktif; metadata opsional kosong mempertahankan nilai lama. **Titik Standar mengganti seluruh konfigurasi body alat yang diunggah**, sehingga semua titik alat itu perlu disertakan. Data alat yang tidak ada di file tidak berubah. Seksi harus sudah terdaftar. Alat nonaktif/arsip atau sedang menunggu validasi tetap ditolak.

Upload menyiapkan master untuk **Update Hasil Kalibrasi**. Hasil ukur, nomor laporan transaksi, petugas, keputusan, dan approval tetap mengikuti proses aplikasi. Upload tidak membuat hasil kalibrasi atau histori persetujuan. Foto alat tetap melalui fitur upload foto aplikasi; gambar yang ditempel di Excel tidak diimpor.

Template CSV lama dan pilihan download per format sudah dihapus. File CSV yang sudah dimiliki tetap dapat diunggah untuk data dasar alat; CSV tidak membawa body titik standar.

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

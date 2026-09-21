# Form B: tiga standar pada registrasi, hasil kalibrasi terpisah

Revisi ini memperbaiki alur, bukan hanya susunan tabel.

## File yang harus disalin

Dari folder `qa_kalibrasi/modul/qa/` dalam ZIP, salin SEMUA empat file berikut ke `modul/qa/` proyek lokal, termasuk jika sudah memasang versi sebelumnya:

- cek_alat.php
- proses_qa.php
- form_b_helper.php
- cetak_laporan.php

Cadangkan empat file lama terlebih dahulu. Jangan menyalin folder .git, koneksi database, atau foto dari ZIP ke proyek aktif. Sesudah mengganti file, muat ulang halaman dengan Ctrl+F5.

Tidak perlu migrasi atau impor ulang database yang dilampirkan. Kolom JSON yang sudah ada tetap digunakan. Form A tidak diubah oleh revisi ini.

## Alur yang benar

1. Registrasi Format B: isi tekanan udara, handle, baut, rentang Min–Max, dan TIGA NILAI STANDAR yang ditampilkan bertumpuk. Semuanya wajib diisi. Contoh: Min 13, Max 15, standar 15 / 14.9 / 14.9.
2. Ketiga standar disimpan bersama data master alat dalam `qa_part.titik_standar_json`, sebagai `std1`, `std2`, `std3`.
3. Saat kalibrasi, tiga standar ditampilkan terkunci. Isi satu kolom HASIL secara terpisah untuk setiap titik, misalnya 14.7. Hasil tidak dihitung sebagai rata-rata tiga standar.
4. Keputusan Lulus apabila Hasil berada dalam rentang Min–Max (termasuk kedua batas). Bila ada satu titik tidak lulus, keputusan keseluruhan Bad. Validasi di browser dan server.
5. Hasil dan salinan standar saat pemeriksaan disimpan di `qa_pengecekan.hasil_json`. Cetak memperlihatkan tiga standar di bawah kelompok Standar Torsi Pengunci Ulir, serta Hasil pada kolom terpisah. Nilai input dipertahankan tanpa pembulatan otomatis.
6. Laporan versi sebelumnya tetap dicetak mengikuti data yang dahulu disimpan, tanpa mengubah histori menjadi data registrasi.

## Alat yang sudah terdaftar dari versi sebelumnya

Cari nomor alat seperti biasa. Jika tiga standar belum tersimpan, tiga kotaknya dapat diisi dan tombol **Simpan tiga standar alat lama** muncul. Isi sesuai standar alat lalu simpan. Sesudah itu standar dikunci dan hasil kalibrasi dapat diisi. Tidak perlu menghapus atau mendaftarkan ulang alat. Nilai yang sebelumnya sudah tersimpan tidak ditimpa melalui tombol ini.

## Verifikasi

Pemeriksaan sintaks JavaScript dan pengujian fungsi sebenarnya telah lulus untuk hasil terpisah dari standar, nilai di kedua batas/di luar batas, input kosong, angka nol, angka tidak valid, serta laporan lama dan baru. Bagian cetak Form A dan alur simpan Form A dibandingkan dengan paket sebelumnya dan tidak berubah.

PHP/MySQL dan tampilan browser belum dapat dijalankan di lingkungan pengerjaan ini. Sebelum digunakan operasional, uji di XAMPP:

- Daftarkan alat B percobaan; pastikan tiga standar tersimpan setelah halaman dimuat ulang.
- Masukkan Hasil 16 pada rentang 13–15: Bad meskipun ketiga standar berada dalam rentang. Masukkan 14.7: Good; hasil harus tetap 14.7.
- Pastikan isian standar/hasil kosong tidak bisa disimpan.
- Simpan, cari alat kembali, lalu cetak ulang laporan untuk memeriksa nilai dan susunan A4.
- Coba tombol pelengkapan standar pada alat lama serta satu pemeriksaan Form A.
- Pengujian helper PHP tersedia: `C:\xampp\php\php.exe tests\form_b_test.php` dari folder proyek. Jalankan lint empat file dengan `C:\xampp\php\php.exe -l modul\qa\NAMA_FILE.php`.

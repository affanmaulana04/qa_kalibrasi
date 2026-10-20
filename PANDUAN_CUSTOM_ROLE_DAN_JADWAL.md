# Update Custom Role dan Laporan Jadwal Bulanan

Basis: versi `qa_kalibrasi_modular_bersih.zip`. Stack tetap Native PHP + mysqli + MySQL MyISAM/latin1 + Bootstrap/jQuery. File di `modul/qa/` tetap dikelompokkan per fitur (tanpa compatibility wrapper).

## Instalasi

1. Cadangkan project dan **backup database** terlebih dahulu.
2. Pilih **satu** cara: ekstrak paket Full untuk pemasangan proyek lengkap; atau gabungkan isi paket Patch ke project modular yang saat ini terpasang. **Jangan menimpa `.git`**.
3. Import `database/migrations/20261020_qa_custom_roles.sql` ke database `qa_kalibrasi` melalui phpMyAdmin. Ini membuat tiga tabel baru saja: `qa_roles`, `qa_role_permissions`, dan `qa_user_roles`. Semua tabel `MyISAM`, `latin1_swedish_ci`, tanpa foreign key, tanpa transaksi.
4. Login sebagai Admin QA yang sudah memiliki `user.admin = 'Y'`. Buka **Tabel → Custom Role & Hak Akses**.
5. Buat role baru, contoh: **Management** dengan izin `jadwal_bulanan`, `laporan_bbrau`, `laporan_hasil`; atau **Kalibrator** dengan `kalibrasi` saja.
6. Buka **Tabel → Data Pemakai Aplikasi → Tambah/Edit User**, lalu pilih **Custom Role**. Role langsung berlaku pada request berikutnya; refresh halaman/menu jika perlu.
7. Uji dengan akun nonadmin dan **uji akses langsung URL serta request AJAX**, bukan cuma apakah menu terlihat.

## Aturan keamanan dan migrasi

- **Admin QA** (`user.admin = 'Y'`) selalu memiliki semua izin dan menjadi satu-satunya pengelola user/custom role. Checkbox pengelolaan user tidak didelegasikan ke nonadmin.
- User lama **tanpa pemetaan** pada `qa_user_roles` tetap memakai flag hak akses lama (`pembuat`, `pemeriksa`, `penyetuju`) sebagai transisi. User lama **tidak otomatis dipindah**.
- Begitu role dipilih untuk user, hak akses diambil **hanya dari izin role itu**, bukan lagi dari flag lama. Izin diambil dari DB pada setiap request baru.
- Validasi **Pemeriksa** dan **Penyetuju** merupakan dua izin terpisah, dicek ulang pada backend `proses_qa.php`. Role tanpa izin tidak bisa memanggil endpoint secara langsung.
- Login biasa dan magic link untuk validasi tetap didukung. Reminder validasi mempertimbangkan izin role custom untuk memilih penerima.
- Akun `block=Y` tetap ditolak, termasuk jika mempunyai role.
- Penambahan/pengeditan user menggunakan CSRF token pada form Admin; role dibuat dengan CSRF token sendiri.
- Jika tabel role belum diimport, pengguna lama tetap menggunakan perilaku legacy; halaman Custom Role menampilkan petunjuk import.

## Laporan Jadwal Bulanan

- URL: `modul/qa/jadwal_bulanan/laporan_jadwal_bulanan.php`.
- Kontrol **Seksi, Bulan, Tahun, Format tampilan & cetak, Tampilkan, Print** tetap terlihat pada Format 1, Internal (Format 2), dan External (Format 3).
- Klik **Tampilkan** untuk menerapkan filter/format dalam **tab yang sama**; tidak membuka tab baru.
- Internal/External tampil dalam area pratinjau pada halaman itu sendiri. **Print** mencetak konten pratinjau terpilih, bukan seluruh kontrol halaman.
- Format 1 dan desain laporan Internal/External dipertahankan; logika data R/H tidak diubah oleh update UI ini.
- Paragraf petunjuk panjang di bawah dropdown format dihilangkan.

## Checklist uji coba di XAMPP/server

1. Login Admin → buat role Management hanya dengan izin laporan → tetapkan pada akun uji → login sebagai akun uji. **Hanya menu Report** yang seharusnya muncul.
2. Coba akses langsung `modul/qa/kalibrasi/cek_alat.php` menggunakan akun Management: **403 Akses ditolak**.
3. Buat role Kalibrator hanya dengan `kalibrasi`: halaman Cek/Update QA dapat dibuka, tetapi layar validasi dan laporan (tanpa izin) tidak.
4. Buat role Pemeriksa hanya dengan `approval_pemeriksa`: hanya aksi validasi Pemeriksa yang boleh dijalankan. Penyetuju diuji serupa.
5. Pastikan nomor laporan otomatis, cetak Format A–W, registry alat, dan update hasil masih bekerja pada akun yang punya izin Kalibrasi.
6. Di laporan bulanan ganti Format 1 → Internal → External → Format 1 tanpa membuka tab baru; pastikan filter tetap terlihat dan **Print** mencetak format yang dipilih.
7. Cek admin user lama tanpa custom role: akses terdahulu masih tersedia selama migrasi.
8. Cek scheduler email validasi dengan `--test` sesuai prosedur server yang telah ada, sebelum mengaktifkan produksi.

## Catatan pengujian

`php -l` berhasil dijalankan pada seluruh file PHP, tetapi **tidak menggantikan** uji integrasi MySQL dan browser pada lingkungan server. Jika instalasi gagal, pulihkan backup kode/DB; tabel role baru dapat dibiarkan kosong tanpa mengubah tabel `user` lama.

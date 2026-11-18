# Email validasi jadwal

Reminder jadwal memakai pemicu harian yang sama dengan validasi hasil kalibrasi:
`modul/qa/notifikasi/daily_validasi_reminder.php`. Versi
`daily_validasi_reminder_terintegrasi.php` juga memuat reminder jadwal.

Saat pemicu berjalan, sistem mengambil jadwal tahun berjalan yang masih
`menunggu_pemeriksa` atau `menunggu_penyetuju`. Penerima utama dan penerima
informasi berasal dari menu **Tabel > Konfigurasi Email Validasi**. Penerima
utama harus memiliki hak akses Pemeriksa atau Penyetuju sesuai tahapnya.

Email utama berisi tautan login validasi yang berlaku 24 jam dan membuka
**Parameter Validasi Jadwal**. Penerima informasi mendapat ringkasan tanpa
tautan login milik validator. Hasil pengiriman dicatat di
`qa_validation_email_log` dengan penanda `[JADWAL]`.

Pada Windows, arahkan Task Scheduler harian yang sudah menjalankan reminder
hasil kalibrasi ke skrip yang sama. Jika perlu membuat tugas baru, gunakan
`scheduler_daily_validasi_qa.bat` sebagai program yang dijalankan. Pastikan
`QA_SMTP_PASSWORD` dan `QA_VALIDATION_MAGIC_URL` tersedia untuk proses PHP
yang dijalankan Task Scheduler. URL magic login harus dapat dibuka dari
komputer penerima email.

QA Kalibrasi - cek_alat local assets

1. Copy modul\qa\cek_alat.php ke:
   C:\xampp\htdocs\qa_kalibrasi\modul\qa\cek_alat.php

2. Copy images\no-image.svg ke:
   C:\xampp\htdocs\qa_kalibrasi\images\no-image.svg

3. Jalankan install_qr_assets_local.bat satu kali dengan koneksi internet.
   Script ini menyimpan:
   C:\xampp\htdocs\qa_kalibrasi\js\qrcode.min.js
   C:\xampp\htdocs\qa_kalibrasi\js\html5-qrcode.min.js

Setelah dua file JS tersimpan, cek_alat.php tidak memuat asset eksternal dari internet.

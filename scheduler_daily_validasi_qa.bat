@echo off
setlocal
set "PHP=C:\xampp\php\php.exe"
set "APP=C:\xampp\htdocs\qa_kalibrasi"

"%PHP%" "%APP%\modul\qa\notifikasi\daily_validasi_reminder.php" %*
exit /b %ERRORLEVEL%

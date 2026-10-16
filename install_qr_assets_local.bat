@echo off
setlocal
set "BASE=C:\xampp\htdocs\qa_kalibrasi"
set "JSDIR=%BASE%\js"

if not exist "%JSDIR%" mkdir "%JSDIR%"

echo [1/2] Mengambil qrcode.min.js...
powershell -NoProfile -ExecutionPolicy Bypass -Command "$ProgressPreference='SilentlyContinue'; [Net.ServicePointManager]::SecurityProtocol=[Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -UseBasicParsing 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js' -OutFile '%JSDIR%\qrcode.min.js'"
if errorlevel 1 goto :fail

echo [2/2] Mengambil html5-qrcode.min.js...
powershell -NoProfile -ExecutionPolicy Bypass -Command "$ProgressPreference='SilentlyContinue'; [Net.ServicePointManager]::SecurityProtocol=[Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -UseBasicParsing 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js' -OutFile '%JSDIR%\html5-qrcode.min.js'"
if errorlevel 1 goto :fail

echo.
echo SELESAI.
echo File lokal sudah tersedia:
echo   %JSDIR%\qrcode.min.js
echo   %JSDIR%\html5-qrcode.min.js
echo.
echo Setelah ini cek_alat.php tidak mengambil library QR dari internet lagi.
pause
exit /b 0

:fail
echo.
echo GAGAL mengambil salah satu library.
echo Pastikan komputer sedang terhubung internet saat setup satu kali ini.
pause
exit /b 1

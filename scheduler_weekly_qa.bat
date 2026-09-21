@echo off

set "PHP=C:\xampp\php\php.exe"
set "APP=C:\xampp\htdocs\qa_kalibrasi"

echo [%date% %time%] Task Scheduler mulai >> "%APP%\task_scheduler.log"

"%PHP%" "%APP%\modul\qa\weekly_reminder.php" %* >> "%APP%\modul\qa\weekly_reminder.log" 2>&1

echo [%date% %time%] Task Scheduler selesai - ERRORLEVEL=%ERRORLEVEL% >> "%APP%\task_scheduler.log"
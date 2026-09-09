@echo off
set "PHP=C:\xampp\php\php.exe"
set "APP=C:\xampp\htdocs\Toto_QA"
"%PHP%" "%APP%\modul\qa\weekly_reminder.php" >> "%APP%\modul\qa\weekly_reminder.log" 2>&1

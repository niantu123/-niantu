@echo off
chcp 65001 >nul
cd /d "%~dp0"
title Shiguang Launcher

set "PHP=C:\Users\13588\.tools\php83\php.exe"
set "URL=http://127.0.0.1:8000"
set "PMA=http://localhost/phpmyadmin"

if not exist "%PHP%" (
    echo [ERROR] PHP not found: %PHP%
    echo Install PHP or edit the PHP path in this file.
    pause
    exit /b 1
)

netstat -ano | findstr ":3306" | findstr "LISTENING" >nul
if errorlevel 1 (
    echo [ERROR] MySQL is not running.
    echo Please open XAMPP Control Panel and click Start next to MySQL.
    echo Then run this file again.
    pause
    exit /b 1
)

netstat -ano | findstr ":8000" | findstr "LISTENING" >nul
if not errorlevel 1 (
    echo Server already running. Opening browser...
    start "" "%URL%"
    ping -n 3 127.0.0.1 >nul
    exit /b 0
)

echo Starting Laravel at %URL%
echo phpMyAdmin: %PMA%   ^(user: root, password: empty^)
echo Close the minimized "Shiguang Server" window to stop it.
start "Shiguang Server" /min "%PHP%" artisan serve --host=127.0.0.1 --port=8000
ping -n 4 127.0.0.1 >nul
start "" "%URL%"
exit /b 0

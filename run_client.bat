@echo off
title Kiosk Client - SMKN 2 Pekalongan
cd /d "%~dp0"

echo =======================================================
echo    SMKN 2 Pekalongan - Kontroler Ujian Lab Komputer
echo =======================================================
echo.

:: Gunakan EXE jika sudah dikompile, fallback ke PS1
if exist "KioskClient.exe" (
    echo [MODE] Menjalankan versi EXE ^(terproteksi^)...
    echo.
    KioskClient.exe
) else (
    echo [MODE] Menjalankan versi PS1 ^(development^)...
    echo [INFO] Untuk proteksi, compile dulu: powershell -File build_exe.ps1
    echo.
    powershell -NoProfile -ExecutionPolicy Bypass -File "client_script.ps1"
)

echo.
echo =======================================================
echo     Program Selesai.
echo =======================================================
pause

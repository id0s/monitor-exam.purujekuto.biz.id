@echo off
title Server Kontroler Ujian - Dashboard (FastAPI)
echo =======================================================
echo     Menjalankan Server Kontroler Ujian (FastAPI)
echo =======================================================
echo.

:: Cek apakah python terinstall
where python >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] Python tidak ditemukan di sistem Anda!
    echo Silakan install Python dari https://www.python.org/downloads/ terlebih dahulu.
    echo Pastikan untuk mencentang opsi "Add Python to PATH" saat menginstal.
    pause
    exit /b
)

:: Buat virtualenv dan install dependensi jika belum ada
if not exist .venv (
    echo Membuat virtual environment (.venv)...
    python -m venv .venv
    if %errorlevel% neq 0 (
        echo [ERROR] Gagal membuat virtual environment.
        pause
        exit /b
    )
    
    echo Mengaktifkan virtual environment...
    call .venv\Scripts\activate.bat
    
    echo Menginstal dependensi...
    pip install -r requirements.txt
    
    echo Menginisialisasi database dan data PC...
    python seed_data.py
) else (
    echo Mengaktifkan virtual environment...
    call .venv\Scripts\activate.bat
)

echo.
echo =======================================================
echo   Server Berjalan! 
echo   - Akses lokal : http://localhost:8000
echo   - Akses LAN   : http://[IP_LAPTOP_SERVER]:8000
echo =======================================================
echo.

:: Menjalankan server uvicorn
uvicorn main:app --host 0.0.0.0 --port 8000

pause

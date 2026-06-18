# =======================================================
# build_exe.ps1 - Compile client_script.ps1 -> KioskClient.exe
# Jalankan file ini di Windows (bukan di Linux/WSL)
# =======================================================

Write-Host "============================================="
Write-Host "  SMKN 2 Pekalongan - Build Kiosk Client EXE"
Write-Host "============================================="
Write-Host ""

# 1. Install ps2exe jika belum ada
if (-not (Get-Module -ListAvailable -Name ps2exe)) {
    Write-Host "[1/3] Menginstall modul ps2exe..."
    Install-Module ps2exe -Scope CurrentUser -Force -AllowClobber -ErrorAction Stop
    Write-Host "      ps2exe berhasil diinstall."
} else {
    Write-Host "[1/3] Modul ps2exe sudah tersedia."
}

Import-Module ps2exe -ErrorAction Stop

# 2. Tentukan path file
$inputFile  = Join-Path $PSScriptRoot "client_script.ps1"
$outputFile = Join-Path $PSScriptRoot "KioskClient.exe"

if (-not (Test-Path $inputFile)) {
    Write-Error "File client_script.ps1 tidak ditemukan di: $PSScriptRoot"
    exit 1
}

Write-Host "[2/3] Mengcompile $inputFile ..."

# 3. Compile ke EXE
Invoke-ps2exe `
    -inputFile  $inputFile `
    -outputFile $outputFile `
    -requireAdmin `
    -noConsole:$false `
    -title       "Kiosk Client - Controller Ujian Lab" `
    -description "SMKN 2 Pekalongan - Sistem Pengawasan Ujian Lab Komputer" `
    -company     "SMKN 2 Pekalongan" `
    -product     "Kiosk Exam Controller" `
    -version     "1.0.0.0" `
    -copyright   "2024 SMKN 2 Pekalongan"

Write-Host ""
if (Test-Path $outputFile) {
    # Generate default config.json
    $defaultConfig = @{
        ServerUrl = "http://192.168.60.158:8000"
        ChromeMode = "kiosk"
        CheckIntervalSeconds = 10
    }
    $configPath = Join-Path $PSScriptRoot "config.json"
    $defaultConfig | ConvertTo-Json | Out-File -FilePath $configPath -Encoding utf8
    Write-Host "[3/4] File konfigurasi default dibuat di: $configPath"

    $size = [math]::Round((Get-Item $outputFile).Length / 1KB, 1)
    Write-Host "[4/4] Berhasil! File output:"
    Write-Host "      $outputFile ($size KB)"
    Write-Host ""
    Write-Host "Cara pakai (Tanpa Installer):"
    Write-Host "  - Jalankan KioskClient.exe langsung (sebagai Administrator)."
    Write-Host "  - Sesuaikan IP/Server Target di file 'config.json' yang berada di folder yang sama."
    Write-Host ""
    Write-Host "Cara membuat Single EXE Installer (Tanpa .bat):"
    Write-Host "  1. Install Inno Setup di Windows (https://jrsoftware.org/isdownload.php)"
    Write-Host "  2. Buka file 'kiosk_installer.iss' dengan Inno Setup Compiler"
    Write-Host "  3. Klik menu Build -> Compile (atau tekan Ctrl+F9)"
    Write-Host "  4. File setup tunggal 'KioskClient_Setup.exe' akan terbuat di folder ini!"
    Write-Host "  5. Saat instalasi 'KioskClient_Setup.exe', Anda bisa memasukkan IP server secara dinamis."
} else {
    Write-Error "Compile gagal. Periksa pesan error di atas."
}

Write-Host ""
pause

; =====================================================================
; kiosk_installer.iss - Inno Setup Script untuk Kiosk Client
; =====================================================================
; Cara Penggunaan:
; 1. Pastikan KioskClient.exe sudah dicompile menggunakan build_exe.ps1.
; 2. Install Inno Setup di Windows (https://jrsoftware.org/isdownload.php).
; 3. Klik kanan file ini -> "Compile" atau buka di Inno Setup dan tekan Ctrl+F9.
; 4. Output installer tunggal (KioskClient_Setup.exe) akan dibuat di folder ini.
; =====================================================================

[Setup]
AppName=Kiosk Exam Client
AppVersion=1.0.0
AppPublisher=SMKN 2 Pekalongan
AppPublisherURL=https://smkn2pekalongan.sch.id
DefaultDirName={pf}\KioskClient
DefaultGroupName=Kiosk Exam Client
OutputDir=.
OutputBaseFilename=KioskClient_Setup
SetupIconFile=
Compression=lzma
SolidCompression=yes
PrivilegesRequired=admin
DisableWelcomePage=no
DisableDirPage=no
DisableProgramGroupPage=yes

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"
Name: "indonesian"; MessagesFile: "compiler:Languages\Indonesian.isl"

[Files]
Source: "KioskClient.exe"; DestDir: "{app}"; Flags: ignoreversion
Source: "config.json"; DestDir: "{app}"; Flags: ignoreversion onlyifdoesntexist

[Registry]
; Registrasi agar KioskClient berjalan otomatis saat Windows booting (untuk semua user)
Root: HKLM; Subkey: "SOFTWARE\Microsoft\Windows\CurrentVersion\Run"; ValueType: string; ValueName: "KioskClient"; ValueData: """{app}\KioskClient.exe"""; Flags: uninsdeletevalue

[Icons]
Name: "{group}\Kiosk Exam Client"; Filename: "{app}\KioskClient.exe"
Name: "{commondesktops}\Kiosk Exam Client"; Filename: "{app}\KioskClient.exe"

[Run]
Filename: "{app}\KioskClient.exe"; Description: "Jalankan Kiosk Exam Client sekarang"; Flags: nowait postinstall runascurrentuser

[Code]
var
  ServerPage: TInputQueryWizardPage;

procedure InitializeWizard;
begin
  // Membuat halaman kustom setelah halaman pemilihan folder
  ServerPage := CreateInputQueryPage(wpSelectDir,
    'Konfigurasi Server Ujian', 
    'Masukkan IP Address atau URL Server Ujian',
    'Installer akan mengatur Kiosk Client untuk terhubung ke IP/URL server di bawah ini.' + #13#10 +
    'Pastikan URL ditulis lengkap dengan port (contoh: http://192.168.60.158:8000).');
  
  ServerPage.Add('URL/IP Server:', False);
  
  // Isi nilai default dari config.json yang ada jika bisa dibaca, atau default hardcoded
  ServerPage.Values[0] := 'http://192.168.60.158:8000';
end;

procedure CurStepChanged(CurStep: TSetupStep);
var
  ConfigFile: String;
  ConfigContent: String;
  ServerUrl: String;
begin
  if CurStep = ssPostInstall then
  begin
    ServerUrl := Trim(ServerPage.Values[0]);
    if ServerUrl = '' then
      ServerUrl := 'http://192.168.60.158:8000';

    // Normalisasi input: Tambahkan http:// jika tidak ada skema
    if (Pos('http://', LowerCase(ServerUrl)) = 0) and (Pos('https://', LowerCase(ServerUrl)) = 0) then
      ServerUrl := 'http://' + ServerUrl;

    ConfigFile := ExpandConstant('{app}\config.json');
    
    // Bentuk konten JSON baru
    ConfigContent := '{' + #13#10 +
                     '  "ServerUrl": "' + ServerUrl + '",' + #13#10 +
                     '  "ChromeMode": "kiosk",' + #13#10 +
                     '  "CheckIntervalSeconds": 10' + #13#10 +
                     '}';
    
    // Tulis ke file config.json di folder instalasi
    SaveStringToFile(ConfigFile, ConfigContent, False);
  end;
end;

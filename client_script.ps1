# =====================================================================
# CONFIGURATION & DYNAMIC LOAD
# =====================================================================
$DefaultServerUrl = "http://192.168.60.158:8000"
$DefaultChromeMode = "kiosk"
$DefaultCheckInterval = 10

# Tentukan direktori tempat skrip/EXE berjalan
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
if ([string]::IsNullOrEmpty($ScriptDir)) {
    # Fallback jika dijalankan dari ps2exe
    $ScriptDir = [System.AppDomain]::CurrentDomain.BaseDirectory
}
$ConfigFile = Join-Path $ScriptDir "config.json"

$ServerUrl = $DefaultServerUrl
$ChromeMode = $DefaultChromeMode
$CheckIntervalSeconds = $DefaultCheckInterval

if (Test-Path $ConfigFile) {
    try {
        $Config = Get-Content $ConfigFile -Raw | ConvertFrom-Json
        if ($Config.ServerUrl) {
            $ServerUrl = $Config.ServerUrl
        }
        if ($Config.ChromeMode) {
            $ChromeMode = $Config.ChromeMode
        }
        if ($Config.CheckIntervalSeconds) {
            $CheckIntervalSeconds = [int]$Config.CheckIntervalSeconds
        }
        Write-Host "Konfigurasi dimuat dari: $ConfigFile"
    } catch {
        Write-Warning "Gagal memuat konfigurasi dari $ConfigFile. Menggunakan default."
    }
} else {
    Write-Host "File konfigurasi tidak ditemukan di: $ConfigFile"
    Write-Host "Menggunakan konfigurasi default."
}
# =====================================================================

# Default fallback values
$PcName = $env:COMPUTERNAME
$IpAddress = "127.0.0.1"
$MacAddress = ""

Write-Host "--------------------------------------------------------"
Write-Host "          MEMULAI CLIENT KONTROLER UJIAN                "
Write-Host "--------------------------------------------------------"

# 1. Mendeteksi Adapter Jaringan Aktif dengan kompatibilitas tinggi (WMI)
# WMI query ini berjalan di semua versi Windows (Win 7, 8, 10, 11) dan PowerShell 2.0+
try {
    Write-Host "Mendeteksi informasi IP & MAC melalui WMI..."
    $adapters = Get-WmiObject -Class Win32_NetworkAdapterConfiguration | Where-Object { $_.IPEnabled -eq $true }
    
    # Prioritaskan adapter yang memiliki Gateway default (koneksi aktif)
    $activeAdapter = $adapters | Where-Object { $_.DefaultIPGateway -ne $null } | Select-Object -First 1
    if (-not $activeAdapter) {
        $activeAdapter = $adapters | Select-Object -First 1
    }
    
    if ($activeAdapter) {
        $IpAddress = $activeAdapter.IPAddress[0]
        $MacAddress = $activeAdapter.MACAddress
    }
} catch {
    Write-Warning "Pendeteksian WMI gagal, mencoba metode alternatif..."
    try {
        if (Get-Command Get-NetIPAddress -ErrorAction SilentlyContinue) {
            $IpAddress = (Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -notlike "127.*" -and $_.IPAddress -notlike "169.254.*" } | Select-Object -First 1).IPAddress
            $MacAddress = (Get-NetAdapter | Where-Object { $_.Status -eq 'Up' } | Select-Object -First 1).MacAddress
        }
    } catch {
        Write-Warning "Metode alternatif gagal. Menggunakan data default."
    }
}

# Rapikan format MAC Address agar menggunakan titik dua (colon)
if ($MacAddress) {
    $MacAddress = $MacAddress -replace '-', ':'
}

function Set-Startup {
    try {
        $exePath = [System.Diagnostics.Process]::GetCurrentProcess().MainModule.FileName
        if ($exePath -like "*powershell*" -or $exePath -like "*pwsh*") {
            $scriptPath = $MyInvocation.MyCommand.Path
            if ($scriptPath) {
                $exePath = "powershell.exe -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$scriptPath`""
            } else {
                return
            }
        }
        $registryPath = "HKCU:\Software\Microsoft\Windows\CurrentVersion\Run"
        $name = "KioskClient"
        Set-ItemProperty -Path $registryPath -Name $name -Value $exePath -ErrorAction SilentlyContinue
    } catch {}
}
# Jalankan registrasi startup otomatis
Set-Startup

Write-Host "Informasi PC:"
Write-Host "  - Nama PC      : $PcName"
Write-Host "  - Alamat IP    : $IpAddress"
Write-Host "  - MAC Address  : $MacAddress"
Write-Host "  - Target Server: $ServerUrl"
Write-Host "--------------------------------------------------------"

# =====================================================================
# Win32 API to check foreground window & hook keyboard
# =====================================================================
Add-Type -AssemblyName System.Windows.Forms
$win32Source = @"
using System;
using System.Runtime.InteropServices;
using System.Diagnostics;
using System.Threading;
using System.Windows.Forms;

public class Win32 {
    [DllImport("user32.dll")]
    public static extern IntPtr GetForegroundWindow();
    
    [DllImport("user32.dll")]
    public static extern uint GetWindowThreadProcessId(IntPtr hWnd, out uint lpdwProcessId);
}

public class KeyBlocker {
    private const int WH_KEYBOARD_LL = 13;
    private const int WM_KEYDOWN = 0x0100;
    private const int WM_SYSKEYDOWN = 0x0104;

    private static LowLevelKeyboardProc _proc = HookCallback;
    private static IntPtr _hookID = IntPtr.Zero;
    private static Thread _hookThread = null;
    private static bool _isRunning = false;

    // Static flag to signal lock screen request
    public static bool RequestLock = false;

    [DllImport("user32.dll", CharSet = CharSet.Auto, SetLastError = true)]
    private static extern IntPtr SetWindowsHookEx(int idHook, LowLevelKeyboardProc lpfn, IntPtr hMod, uint dwThreadId);

    [DllImport("user32.dll", CharSet = CharSet.Auto, SetLastError = true)]
    [return: MarshalAs(UnmanagedType.Bool)]
    private static extern bool UnhookWindowsHookEx(IntPtr hhk);

    [DllImport("user32.dll", CharSet = CharSet.Auto, SetLastError = true)]
    private static extern IntPtr CallNextHookEx(IntPtr hhk, int nCode, IntPtr wParam, IntPtr lParam);

    [DllImport("kernel32.dll", CharSet = CharSet.Auto, SetLastError = true)]
    private static extern IntPtr GetModuleHandle(string lpModuleName);

    [DllImport("user32.dll", CharSet = CharSet.Auto, SetLastError = true)]
    private static extern short GetKeyState(int nVirtKey);

    private delegate IntPtr LowLevelKeyboardProc(int nCode, IntPtr wParam, IntPtr lParam);

    [StructLayout(LayoutKind.Sequential)]
    private struct KBDLLHOOKSTRUCT {
        public int vkCode;
        public int scanCode;
        public int flags;
        public int time;
        public IntPtr dwExtraInfo;
    }

    public static void Start() {
        if (_isRunning) return;
        _isRunning = true;
        _hookThread = new Thread(new ThreadStart(HookLoop));
        _hookThread.SetApartmentState(ApartmentState.STA);
        _hookThread.IsBackground = true;
        _hookThread.Start();
    }

    public static void Stop() {
        if (!_isRunning) return;
        _isRunning = false;
        Application.Exit();
        if (_hookThread != null) {
            _hookThread.Join(1000);
            _hookThread = null;
        }
    }

    private static void HookLoop() {
        _hookID = SetHook(_proc);
        Application.Run();
        if (_hookID != IntPtr.Zero) {
            UnhookWindowsHookEx(_hookID);
            _hookID = IntPtr.Zero;
        }
    }

    private static IntPtr SetHook(LowLevelKeyboardProc proc) {
        using (Process curProcess = Process.GetCurrentProcess())
        using (ProcessModule curModule = curProcess.MainModule) {
            return SetWindowsHookEx(WH_KEYBOARD_LL, proc, GetModuleHandle(curModule.ModuleName), 0);
        }
    }

    private static IntPtr HookCallback(int nCode, IntPtr wParam, IntPtr lParam) {
        if (nCode >= 0) {
            KBDLLHOOKSTRUCT kbd = (KBDLLHOOKSTRUCT)Marshal.PtrToStructure(lParam, typeof(KBDLLHOOKSTRUCT));
            int vkCode = kbd.vkCode;
            int flags = kbd.flags;

            bool isAltPressed = (flags & 0x20) != 0;
            bool isCtrlPressed = (GetKeyState(0x11) & 0x8000) != 0;
            bool isShiftPressed = (GetKeyState(0x10) & 0x8000) != 0;

            // Secret key combo: Ctrl + Alt + Shift + E (E: 0x45) -> Request Lock / Exit options
            if (vkCode == 0x45 && isAltPressed && isCtrlPressed && isShiftPressed) {
                RequestLock = true;
                return (IntPtr)1;
            }

            // Block Windows Keys (LWin: 0x5B, RWin: 0x5C)
            if (vkCode == 0x5B || vkCode == 0x5C) {
                return (IntPtr)1;
            }

            // Block Alt + F4 (F4: 0x73)
            if (vkCode == 0x73 && isAltPressed) {
                return (IntPtr)1;
            }

            // Block Alt + Tab (Tab: 0x09)
            if (vkCode == 0x09 && isAltPressed) {
                return (IntPtr)1;
            }

            // Block Alt + Esc (Esc: 0x1B)
            if (vkCode == 0x1B && isAltPressed) {
                return (IntPtr)1;
            }

            // Block Ctrl + Esc (Esc: 0x1B)
            if (vkCode == 0x1B && isCtrlPressed) {
                return (IntPtr)1;
            }
        }
        return CallNextHookEx(_hookID, nCode, wParam, lParam);
    }
}
"@
if (-not ([System.Management.Automation.PSTypeName]'Win32').Type) {
    Add-Type -TypeDefinition $win32Source -ReferencedAssemblies "System.Windows.Forms"
}

function Show-LockScreen {
    # Send lock status (2) to server immediately
    try {
        $encodedPc = [uri]::EscapeDataString($PcName)
        $encodedIp = [uri]::EscapeDataString($IpAddress)
        $UrlLock = "$ServerUrl/api/check-jadwal?pc_name=$encodedPc&ip=$encodedIp&chrome_running=2"
        if ($MacAddress) {
            $UrlLock += "&mac=$([uri]::EscapeDataString($MacAddress))"
        }
        if (Get-Command Invoke-RestMethod -ErrorAction SilentlyContinue) {
            Invoke-RestMethod -Uri $UrlLock -Method Get -TimeoutSec 3 | Out-Null
        } else {
            $webClient = New-Object System.Net.WebClient
            $webClient.Headers.Add("user-agent", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)")
            $webClient.DownloadString($UrlLock) | Out-Null
        }
    } catch {}

    Add-Type -AssemblyName System.Windows.Forms
    Add-Type -AssemblyName System.Drawing
    
    # Inisialisasi variabel scope script untuk mencegah error scoping/akumulasi
    $script:LockForm = $null
    $script:LockContainer = $null
    $script:LockTextBox = $null
    $script:LockButton = $null
    $script:LockErrorLabel = $null
    $script:LockErrTimer = $null
    
    $script:LockForm = New-Object System.Windows.Forms.Form
    $script:LockForm.Text = "UJIAN TERKUNCI"
    $script:LockForm.WindowState = [System.Windows.Forms.FormWindowState]::Maximized
    $script:LockForm.FormBorderStyle = [System.Windows.Forms.FormBorderStyle]::None
    $script:LockForm.TopMost = $true
    $script:LockForm.BackColor = [System.Drawing.Color]::FromArgb(15, 23, 42) # Slate 900
    
    $script:LockContainer = New-Object System.Windows.Forms.Panel
    $script:LockContainer.Size = New-Object System.Drawing.Size(600, 430)
    $script:LockContainer.BackColor = [System.Drawing.Color]::FromArgb(30, 41, 59) # Slate 800
    
    $script:LockForm.Add_Load({
        [int]$screenWidth = [System.Windows.Forms.Screen]::PrimaryScreen.Bounds.Width
        [int]$screenHeight = [System.Windows.Forms.Screen]::PrimaryScreen.Bounds.Height
        [int]$containerWidth = $script:LockContainer.Width
        [int]$containerHeight = $script:LockContainer.Height
        $script:LockContainer.Location = New-Object System.Drawing.Point([int](($screenWidth - $containerWidth) / 2), [int](($screenHeight - $containerHeight) / 2))
        $script:LockTextBox.Focus()
    })
    $script:LockForm.Controls.Add($script:LockContainer)
    
    $title = New-Object System.Windows.Forms.Label
    $title.Text = "SMKN 2 PEKALONGAN`nKONTROLER UJIAN LAB"
    $title.Location = New-Object System.Drawing.Point(50, 30)
    $title.Width = 500
    $title.Height = 60
    $title.ForeColor = [System.Drawing.Color]::FromArgb(6, 182, 212) # Cyan 500
    $title.Font = New-Object System.Drawing.Font("Outfit", 16, [System.Drawing.FontStyle]::Bold)
    $title.TextAlign = [System.Drawing.ContentAlignment]::MiddleCenter
    $script:LockContainer.Controls.Add($title)
    
    $warning = New-Object System.Windows.Forms.Label
    $warning.Text = "PERINGATAN: TERDETEKSI UPAYA KELUAR DARI EXAM BROWSER!`n`nLayar ini telah dikunci untuk keamanan. Silakan hubungi Pengawas untuk memasukkan password pembuka kunci."
    $warning.Location = New-Object System.Drawing.Point(50, 110)
    $warning.Width = 500
    $warning.Height = 80
    $warning.ForeColor = [System.Drawing.Color]::FromArgb(239, 68, 68) # Red 500
    $warning.Font = New-Object System.Drawing.Font("Inter", 10, [System.Drawing.FontStyle]::Bold)
    $warning.TextAlign = [System.Drawing.ContentAlignment]::MiddleCenter
    $script:LockContainer.Controls.Add($warning)
    
    $labelInput = New-Object System.Windows.Forms.Label
    $labelInput.Text = "PASSWORD PENGAWAS:"
    $labelInput.Location = New-Object System.Drawing.Point(150, 215)
    $labelInput.Width = 300
    $labelInput.ForeColor = [System.Drawing.Color]::FromArgb(148, 163, 184) # Slate 400
    $labelInput.Font = New-Object System.Drawing.Font("Inter", 9, [System.Drawing.FontStyle]::Bold)
    $labelInput.TextAlign = [System.Drawing.ContentAlignment]::MiddleLeft
    $script:LockContainer.Controls.Add($labelInput)
    
    $script:LockTextBox = New-Object System.Windows.Forms.TextBox
    $script:LockTextBox.Location = New-Object System.Drawing.Point(150, 240)
    $script:LockTextBox.Width = 300
    $script:LockTextBox.Font = New-Object System.Drawing.Font("Inter", 12)
    $script:LockTextBox.UseSystemPasswordChar = $true
    $script:LockTextBox.BackColor = [System.Drawing.Color]::FromArgb(15, 23, 42)
    $script:LockTextBox.ForeColor = [System.Drawing.Color]::White
    $script:LockTextBox.BorderStyle = [System.Windows.Forms.BorderStyle]::FixedSingle
    $script:LockContainer.Controls.Add($script:LockTextBox)
    
    $script:LockButton = New-Object System.Windows.Forms.Button
    $script:LockButton.Text = "BUKA KUNCI LAYAR"
    $script:LockButton.Location = New-Object System.Drawing.Point(150, 295)
    $script:LockButton.Width = 300
    $script:LockButton.Height = 45
    $script:LockButton.ForeColor = [System.Drawing.Color]::White
    $script:LockButton.BackColor = [System.Drawing.Color]::FromArgb(37, 99, 235) # Blue 600
    $script:LockButton.Font = New-Object System.Drawing.Font("Inter", 10, [System.Drawing.FontStyle]::Bold)
    $script:LockButton.FlatStyle = [System.Windows.Forms.FlatStyle]::Flat
    $script:LockButton.FlatAppearance.BorderSize = 0
    $script:LockContainer.Controls.Add($script:LockButton)
    
    # Label error inline (tidak pakai MessageBox karena timer akan menutupnya)
    $script:LockErrorLabel = New-Object System.Windows.Forms.Label
    $script:LockErrorLabel.Text = ""
    $script:LockErrorLabel.Location = New-Object System.Drawing.Point(150, 348)
    $script:LockErrorLabel.Width = 300
    $script:LockErrorLabel.Height = 30
    $script:LockErrorLabel.ForeColor = [System.Drawing.Color]::FromArgb(239, 68, 68) # Red 500
    $script:LockErrorLabel.Font = New-Object System.Drawing.Font("Inter", 9, [System.Drawing.FontStyle]::Bold)
    $script:LockErrorLabel.TextAlign = [System.Drawing.ContentAlignment]::MiddleCenter
    $script:LockContainer.Controls.Add($script:LockErrorLabel)
    
    $script:ProctorPassword = "pekalongan2"
    
    $script:LockTextBox.Add_KeyDown({
        if ($args[1].KeyCode -eq [System.Windows.Forms.Keys]::Enter) {
            $script:LockButton.PerformClick()
        }
    })
    
    $script:LockButton.Add_Click({
        if ($script:LockTextBox.Text -eq $script:ProctorPassword) {
            # Cukup set DialogResult saja — ShowDialog() akan otomatis menutup form.
            # JANGAN panggil $form.Close() karena itu akan memicu FormClosing
            # dengan CloseReason.UserClosing dan handler kita akan membloknya!
            $script:LockForm.DialogResult = [System.Windows.Forms.DialogResult]::OK
        } elseif ($script:LockTextBox.Text -eq ($script:ProctorPassword + "exit")) {
            # Keluar dari Kiosk Client
            $script:LockForm.DialogResult = [System.Windows.Forms.DialogResult]::Yes
        } else {
            # Tampilkan error langsung di dalam form (TIDAK pakai MessageBox
            # karena timer 200ms akan menutup MessageBox sebelum terbaca)
            $script:LockErrorLabel.Text = "Password salah! Coba lagi."
            $script:LockTextBox.BackColor = [System.Drawing.Color]::FromArgb(127, 29, 29) # Red 950
            $script:LockTextBox.Clear()
            $script:LockTextBox.Focus()
            # Auto-reset tampilan error setelah 2 detik
            $script:LockErrTimer = New-Object System.Windows.Forms.Timer
            $script:LockErrTimer.Interval = 2000
            $script:LockErrTimer.Add_Tick({
                $script:LockErrorLabel.Text = ""
                $script:LockTextBox.BackColor = [System.Drawing.Color]::FromArgb(15, 23, 42)
                $script:LockErrTimer.Stop()
            })
            $script:LockErrTimer.Start()
        }
    })
    
    $script:LockForm.Add_FormClosing({
        # Hanya blokir penutupan jika DialogResult masih None
        # (artinya user paksa tutup via Alt+F4 atau tombol X window).
        # Jika DialogResult sudah di-set (OK atau Cancel), biarkan form menutup.
        if ($args[1].CloseReason -eq [System.Windows.Forms.CloseReason]::UserClosing -and
            $script:LockForm.DialogResult -eq [System.Windows.Forms.DialogResult]::None) {
            $args[1].Cancel = $true
        }
    })
    
    $timer = New-Object System.Windows.Forms.Timer
    $timer.Interval = 200
    $timer.Add_Tick({
        # Matikan Task Manager jika coba dibuka oleh siswa
        Stop-Process -Name "taskmgr" -ErrorAction SilentlyContinue
        
        # Paksa window ini tetap di depan dan aktif
        if ([Win32]::GetForegroundWindow() -ne $script:LockForm.Handle) {
            $script:LockForm.Activate()
            $script:LockTextBox.Focus()
        }
    })
    $timer.Start()

    $serverTimer = New-Object System.Windows.Forms.Timer
    $serverTimer.Interval = 5000
    $serverTimer.Add_Tick({
        try {
            $encodedPc = [uri]::EscapeDataString($PcName)
            $encodedIp = [uri]::EscapeDataString($IpAddress)
            $UrlLock = "$ServerUrl/api/check-jadwal?pc_name=$encodedPc&ip=$encodedIp&chrome_running=2"
            if ($MacAddress) {
                $UrlLock += "&mac=$([uri]::EscapeDataString($MacAddress))"
            }
            
            $status = ""
            $event = ""
            if (Get-Command Invoke-RestMethod -ErrorAction SilentlyContinue) {
                $response = Invoke-RestMethod -Uri $UrlLock -Method Get -TimeoutSec 3
                $status = $response.status
                $event = $response.event
            } else {
                $webClient = New-Object System.Net.WebClient
                $webClient.Headers.Add("user-agent", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)")
                $jsonText = $webClient.DownloadString($UrlLock)
                if ($jsonText -match '"status"\s*:\s*"([^"]+)"') { $status = $Matches[1] }
                if ($jsonText -match '"event"\s*:\s*"([^"]+)"') { $event = $Matches[1] }
            }
            
            # Jika ujian dihentikan dari server (NO_EVENT), tutup layar kunci
            # Cukup set DialogResult — ShowDialog() auto-close tanpa perlu $form.Close()
            if ($status -eq "success" -and $event -eq "NO_EVENT") {
                $script:LockForm.DialogResult = [System.Windows.Forms.DialogResult]::Cancel
            }
        } catch {
            # Abaikan kendala jaringan agar tidak crash
        }
    })
    $serverTimer.Start()
    
    $dialogResult = $script:LockForm.ShowDialog()
    $timer.Stop()
    $serverTimer.Stop()
    
    return $dialogResult.ToString()
}

# =====================================================================
# MAIN MONITORING & HEARTBEAT LOOP
# =====================================================================
$HeartbeatIntervalMs = $CheckIntervalSeconds * 1000
$LoopIntervalMs = 200
$LastHeartbeatMs = 0 # Force immediate heartbeat on startup

$chromeProcess = $null
$chromeOpenedByScript = $false
$focusLossCounter = 0
$gracePeriodRemainingMs = 0
$GracePeriodMs = 5000 # 5 seconds grace period when launching chrome
$unlockCooldownMs = 0   # Cooldown pasca-unlock: mencegah lock screen muncul lagi sesaat setelah password benar
$UnlockCooldownMs = 8000 # 8 detik cooldown

try {
    # Start Keyboard Hook
    [KeyBlocker]::Start()
    Write-Host "[INFO] Keyboard hook aktif. Windows Key & Alt+F4 diblokir."

    Write-Host "Memulai Pemantauan Kiosk & Heartbeat Real-time..."
    Write-Host "--------------------------------------------------------"

    $stopwatch = [System.Diagnostics.Stopwatch]::StartNew()

    while ($true) {
        # Check if proctor requested lock screen / exit via hotkey
        if ([KeyBlocker]::RequestLock) {
            [KeyBlocker]::RequestLock = $false
            Write-Host "Lock screen requested by hotkey (Ctrl+Alt+Shift+E)..."
            $lockResult = Show-LockScreen
            if ($lockResult -eq "Yes") {
                exit
            }
        }

        $currentMs = $stopwatch.ElapsedMilliseconds
    
    # 1. Cek status Chrome (jika seharusnya berjalan)
    # CATATAN: Chrome launcher ($chromeProcess) sering langsung exit dan spawn child processes.
    # Jadi kita cek DUA hal: proses spesifik ATAU ada chrome.exe yang berjalan di sistem.
    $chromeRunning = $false
    if ($chromeOpenedByScript) {
        # Proses launcher masih hidup?
        if ($chromeProcess -and -not $chromeProcess.HasExited) {
            $chromeRunning = $true
        } else {
            # Launcher sudah exit (wajar di Chrome), cek apakah chrome.exe lain masih jalan
            $anyChrome = Get-Process -Name "chrome" -ErrorAction SilentlyContinue
            if ($anyChrome) {
                $chromeRunning = $true
                # Update referensi ke proses chrome yang masih aktif (supaya .Kill() tetap bisa)
                if (-not $chromeProcess -or $chromeProcess.HasExited) {
                    $chromeProcess = $anyChrome | Select-Object -First 1
                }
            } else {
                $chromeRunning = $false
                $chromeProcess = $null
            }
        }
    } else {
        if ($chromeProcess -and -not $chromeProcess.HasExited) {
            $chromeRunning = $true
        } else {
            $chromeRunning = $false
            $chromeProcess = $null
        }
    }
    
    # 2. Pemantauan Keamanan (Hanya jika Chrome seharusnya aktif)
    # Jika dalam masa cooldown pasca-unlock, skip semua pemeriksaan keamanan
    if ($unlockCooldownMs -gt 0) {
        $unlockCooldownMs -= $LoopIntervalMs
    } elseif ($chromeOpenedByScript) {
        if (-not $chromeRunning) {
            # Evasion: Chrome ditutup paksa / crash!
            Write-Warning "!!! DETEKSI PROSES CHROME KELUAR / DITUTUP !!!"
            Write-Host "Mengunci layar..."
            
            $lockResult = Show-LockScreen
            
            $focusLossCounter = 0
            if ($lockResult -eq "OK") {
                # Proctor memasukkan password -> Relaunch Chrome
                Write-Host "Membuka kembali Chrome setelah pembukaan kunci..."
                $LastHeartbeatMs = 0   # Heartbeat langsung -> server tahu, Chrome akan diluncurkan ulang
                $unlockCooldownMs = $UnlockCooldownMs  # Cooldown 8 detik agar tidak langsung lock lagi
            } elseif ($lockResult -eq "Yes") {
                exit
            } else {
                # Ujian berakhir saat terkunci
                $chromeOpenedByScript = $false
                $chromeProcess = $null
            }
        } else {
            # Chrome sedang berjalan, cek focus window
            if ($gracePeriodRemainingMs -gt 0) {
                $gracePeriodRemainingMs -= $LoopIntervalMs
            } else {
                $hwnd = [Win32]::GetForegroundWindow()
                $isChromeFocused = $false
                
                if ($hwnd -ne [IntPtr]::Zero) {
                    $activePid = 0
                    [Win32]::GetWindowThreadProcessId($hwnd, [ref]$activePid)
                    
                    if ($activePid -ne 0) {
                        # Ambil proses aktif
                        $activeProcess = Get-Process -Id $activePid -ErrorAction SilentlyContinue
                        if ($activeProcess) {
                            $procName = $activeProcess.ProcessName.ToLower()
                            # Izinkan chrome, google-chrome, dan proses script ini sendiri ($PID)
                            # Blokir semua proses lain, termasuk: explorer, winlogon, notepad, dll.
                            if ($procName -eq "chrome" -or $procName -eq "google-chrome" -or $activePid -eq $PID) {
                                $isChromeFocused = $true
                            }
                            # Winlogon muncul ketika Ctrl+Alt+Del screen aktif
                            # Explorer adalah file manager / taskbar
                            # Keduanya dianggap sebagai focus loss
                        }
                    }
                }
                # Jika hwnd == 0 (Secure Desktop / Ctrl+Alt+Del sedang aktif),
                # maka $isChromeFocused tetap $false -> focus loss terhitung
                
                if (-not $isChromeFocused) {
                    $focusLossCounter++
                    # Debounce 3 loop (~600ms) -> cukup cepat untuk Ctrl+Alt+Del
                    if ($focusLossCounter -ge 3) {
                        Write-Warning "!!! DETEKSI FOCUS LOSS / Ctrl+Alt+Del! Layar akan dikunci !!!"
                        Write-Host "Mengunci layar..."
                        
                        $lockResult = Show-LockScreen
                        
                        $focusLossCounter = 0
                        if ($lockResult -eq "OK") {
                            # Proctor membuka kunci -> Berikan grace period + heartbeat langsung
                            $LastHeartbeatMs = 0   # Update server segera: chrome sudah di-unlock
                            $unlockCooldownMs = $UnlockCooldownMs  # Cooldown 8 detik
                            # Re-cek apakah Chrome masih berjalan
                            if ($chromeProcess -and -not $chromeProcess.HasExited) {
                                $gracePeriodRemainingMs = $GracePeriodMs
                            } else {
                                # Chrome tutup saat terkunci -> heartbeat akan relaunch
                                $chromeProcess = $null
                                Write-Host "Chrome tertutup saat terkunci. Akan diluncurkan ulang via heartbeat..."
                            }
                        } elseif ($lockResult -eq "Yes") {
                            exit
                        } else {
                            # Ujian berakhir saat terkunci
                            $chromeOpenedByScript = $false
                            if ($chromeProcess) {
                                $chromeProcess.Kill()
                                $chromeProcess = $null
                            }
                        }
                    }
                } else {
                    $focusLossCounter = 0
                }
            }
        }
    }
    
    # 3. Heartbeat berkala ke Server
    if (($currentMs - $LastHeartbeatMs) -ge $HeartbeatIntervalMs) {
        $LastHeartbeatMs = $currentMs
        
        try {
            $encodedPc = [uri]::EscapeDataString($PcName)
            $encodedIp = [uri]::EscapeDataString($IpAddress)
            $chromeStatusParam = if ($chromeRunning) { "1" } else { "0" }
            $Url = "$ServerUrl/api/check-jadwal?pc_name=$encodedPc&ip=$encodedIp&chrome_running=$chromeStatusParam"
            if ($MacAddress) {
                $Url += "&mac=$([uri]::EscapeDataString($MacAddress))"
            }
            
            Write-Host "[$([DateTime]::Now.ToString('HH:mm:ss'))] Heartbeat -> Server (Chrome Running: $chromeRunning)..."
            
            $status = ""
            $event = ""
            $targetUrl = ""
            $receivedMode = ""
            
            if (Get-Command Invoke-RestMethod -ErrorAction SilentlyContinue) {
                $response = Invoke-RestMethod -Uri $Url -Method Get -TimeoutSec 5
                $status = $response.status
                $event = $response.event
                $targetUrl = $response.url
                $receivedMode = $response.browser_mode
            } else {
                $webClient = New-Object System.Net.WebClient
                $webClient.Headers.Add("user-agent", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)")
                $jsonText = $webClient.DownloadString($Url)
                if ($jsonText -match '"status"\s*:\s*"([^"]+)"') { $status = $Matches[1] }
                if ($jsonText -match '"event"\s*:\s*"([^"]+)"') { $event = $Matches[1] }
                if ($jsonText -match '"url"\s*:\s*"([^"]+)"') { $targetUrl = $Matches[1] }
                if ($jsonText -match '"browser_mode"\s*:\s*"([^"]+)"') { $receivedMode = $Matches[1] }
            }
            
            if ($status -eq "success") {
                if ($event -ne "NO_EVENT" -and $targetUrl) {
                    if (-not $chromeRunning) {
                        Write-Host ">>> Event Ujian Aktif Ditemukan: $event"
                        Write-Host ">>> Membuka URL: $targetUrl"
                        
                        # Tutup chrome lama jika ada
                        Stop-Process -Name "chrome" -ErrorAction SilentlyContinue
                        Start-Sleep -Seconds 1
                        
                        # ============================================================
                        # MATIKAN SEMUA PROSES NON-SISTEM SEBELUM UJIAN DIMULAI
                        # Pendekatan whitelist: simpan hanya proses Windows kritis,
                        # sisanya (apapun itu) langsung dimatikan.
                        # ============================================================
                        Write-Host ">>> Mematikan semua proses non-sistem..."
                        
                        # PID proses kita sendiri + parent CMD agar tidak ikut dimatikan
                        $pidSendiri = $PID
                        $pidParent  = (Get-CimInstance Win32_Process -Filter "ProcessId = $PID" `
                                        -ErrorAction SilentlyContinue).ParentProcessId
                        
                        # Daftar proses sistem Windows yang WAJIB tetap hidup
                        $whitelist = @(
                            "system","idle","registry","memorycleanupworker",
                            "smss","csrss","wininit","winlogon","lsass","lsm",
                            "services","svchost",
                            "dwm","fontdrvhost",
                            "explorer","sihost","taskhostw","ctfmon",
                            "shellexperiencehost","startmenuexperiencehost",
                            "applicationframehost","textinputhost","searchhost",
                            "runtimebroker","dllhost","wmiprvse","conhost",
                            "msmpeng","nissrv","mpdefendercoreservice",
                            "securityhealthsystray","securityhealthservice",
                            "powershell","pwsh","cmd",
                            "audiodg","wlanext","spoolsv",
                            "searchindexer","tiworker","trustedinstaller",
                            "wudfhost","dashost",
                            "lockapp","logonui",
                            "nvdisplay.container","nvcontainer",
                            "igfxem","igfxhk","igfxtray","igfxcuiservice"
                        )
                        
                        $jumlahDibunuh = 0
                        Get-Process | Where-Object {
                            $namaPros = $_.Name.ToLower()
                            $pidPros  = $_.Id
                            # Jangan matikan diri sendiri atau parent CMD
                            if ($pidPros -eq $pidSendiri -or $pidPros -eq $pidParent) { return $false }
                            # Jangan matikan proses whitelist sistem
                            if ($whitelist -contains $namaPros) { return $false }
                            return $true
                        } | ForEach-Object {
                            Write-Host "   [KILL] $($_.Name) (PID: $($_.Id))"
                            Stop-Process -Id $_.Id -Force -ErrorAction SilentlyContinue
                            $jumlahDibunuh++
                        }
                        Write-Host ">>> Pembersihan selesai. $jumlahDibunuh proses dimatikan."
                        Start-Sleep -Milliseconds 500


                        
                        # Tentukan path chrome
                        $chromePaths = @(
                            "$env:ProgramFiles\Google\Chrome\Application\chrome.exe",
                            "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
                            "$env:LocalAppData\Google\Chrome\Application\chrome.exe"
                        )
                        $chromePath = "chrome.exe"
                        foreach ($path in $chromePaths) {
                            if (Test-Path $path) { $chromePath = $path; break }
                        }
                        
                        $finalMode = if ($receivedMode) { $receivedMode } else { $ChromeMode }
                        Write-Host "Menyiapkan Google Chrome ($finalMode mode)..."
                        
                        $arguments = ""
                        if ($finalMode -eq "kiosk") {
                            $tempProfile = Join-Path $env:TEMP "chrome_kiosk_profile"
                            if (-not (Test-Path $tempProfile)) {
                                New-Item -ItemType Directory -Path $tempProfile -Force | Out-Null
                            }
                            $arguments = "--new-window --start-maximized --kiosk --user-data-dir=`"$tempProfile`" --no-first-run --no-default-browser-check `"$targetUrl`""
                        } elseif ($finalMode -eq "app") {
                            $arguments = "--app=`"$targetUrl`""
                        } else {
                            $arguments = "--new-window --start-maximized `"$targetUrl`""
                        }
                        
                        Write-Host "Menjalankan Google Chrome..."
                        $chromeProcess = Start-Process -FilePath $chromePath -ArgumentList $arguments -PassThru -ErrorAction Stop
                        $chromeOpenedByScript = $true
                        $chromeRunning = $true
                        $gracePeriodRemainingMs = $GracePeriodMs
                        $focusLossCounter = 0
                    }
                } else {
                    # NO_EVENT
                    if ($chromeRunning -and $chromeOpenedByScript) {
                        Write-Host ">>> Ujian telah berakhir atau dinonaktifkan."
                        Write-Host "Menutup browser Chrome otomatis..."
                        $chromeOpenedByScript = $false
                        if ($chromeProcess) {
                            $chromeProcess.Kill()
                        } else {
                            Stop-Process -Name "chrome" -ErrorAction SilentlyContinue
                        }
                        $chromeProcess = $null
                        $chromeRunning = $false
                    }
                }
            }
        } catch {
            Write-Warning "Koneksi ke server gagal: $($_.Exception.Message)"
        }
    }
    
    Start-Sleep -Milliseconds $LoopIntervalMs
    }
} finally {
    # Stop Keyboard Hook
    [KeyBlocker]::Stop()
    Write-Host "[INFO] Keyboard hook dinonaktifkan."
}

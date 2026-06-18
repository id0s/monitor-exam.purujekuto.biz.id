#!/bin/bash

# =====================================================================
# CONFIGURATION
# =====================================================================
# IP/Port Server Controller
ServerUrl="http://127.0.0.1:8000"

# Opsi Tampilan Browser:
# "kiosk"      -> Kunci layar penuh, tanpa toolbar/tabs
# "app"        -> Jendela minimalis terpisah tanpa toolbar
# "maximized"  -> Jendela browser biasa yang dimaksimalkan (full screen)
ChromeMode="kiosk"

# Jeda waktu pengecekan ulang (detik) jika belum ada ujian aktif
CheckIntervalSeconds=10
# =====================================================================

echo "--------------------------------------------------------"
echo "      MEMULAI CLIENT KONTROLER UJIAN (LINUX SIMULATOR)  "
echo "--------------------------------------------------------"

# 1. Mendeteksi Adapter Jaringan & IP Utama
PcName=$(hostname)
IpAddress="127.0.0.1"
MacAddress=""

# Dapatkan nama interface route utama (gateway 0.0.0.0/0)
interface=$(ip route | grep '^default' | awk '{print $5}' | head -n 1)

if [ -n "$interface" ]; then
    IpAddress=$(ip -4 addr show dev "$interface" | grep -oP '(?<=inet\s)\d+(\.\d+){3}' | head -n 1)
    MacAddress=$(ip link show dev "$interface" | grep -oP '(?<=link/ether\s)[0-9a-fA-F:]{17}' | head -n 1)
fi

# Jika kosong, cari IP lokal non-loopback umum
if [ -z "$IpAddress" ]; then
    IpAddress=$(hostname -I | awk '{print $1}')
fi

echo "Informasi PC (Simulasi):"
echo "  - Nama PC      : $PcName"
echo "  - Alamat IP    : $IpAddress"
echo "  - MAC Address  : $MacAddress"
echo "  - Target Server: $ServerUrl"
echo "--------------------------------------------------------"

chromePid=""
chromeOpenedByScript=false

# =====================================================================
# MAIN MONITORING & HEARTBEAT LOOP
# =====================================================================
HeartbeatIntervalSeconds=10
LoopIntervalSeconds=0.2
LastHeartbeatTime=0

chromePid=""
chromeOpenedByScript=false
focusLossCounter=0
gracePeriodRemainingMs=0
GracePeriodMs=5000 # 5 seconds grace period when launching chrome
LoopIntervalMs=200

echo "Memulai Pemantauan Kiosk & Heartbeat Real-time..."
echo "--------------------------------------------------------"

while true; do
    currentTime=$(date +%s)
    
    # 1. Cek status Chrome (jika seharusnya berjalan)
    chromeRunning=false
    if [ -n "$chromePid" ] && kill -0 "$chromePid" 2>/dev/null; then
        chromeRunning=true
    else
        chromeRunning=false
        chromePid=""
    fi
    
    # 2. Pemantauan Keamanan (Hanya jika Chrome seharusnya aktif)
    if [ "$chromeOpenedByScript" = true ]; then
        if [ "$chromeRunning" = false ]; then
            # Evasion: Chrome ditutup paksa / crash!
            echo "!!! DETEKSI PROSES CHROME KELUAR / DITUTUP !!!"
            echo "Mengunci layar..."
            
            # Panggil layar kunci
            python3 linux_lock.py "$ServerUrl" "$PcName" "$IpAddress" "$MacAddress"
            lockResult=$?
            
            # Reset state setelah unlock
            focusLossCounter=0
            if [ $lockResult -eq 0 ]; then
                # Proctor memasukkan password -> Relaunch Chrome
                echo "Membuka kembali Chrome setelah pembukaan kunci..."
                LastHeartbeatTime=0 # Memicu heartbeat langsung
            else
                # Ujian berakhir saat terkunci
                chromeOpenedByScript=false
                chromePid=""
            fi
        else
            # Chrome sedang berjalan, cek focus window
            if [ $gracePeriodRemainingMs -gt 0 ]; then
                gracePeriodRemainingMs=$((gracePeriodRemainingMs - LoopIntervalMs))
            else
                isChromeFocused=false
                if command -v xdotool &> /dev/null; then
                    active_win_id=$(xdotool getactivewindow 2>/dev/null)
                    if [ -n "$active_win_id" ]; then
                        active_win_pid=$(xdotool getwindowpid "$active_win_id" 2>/dev/null)
                        if [ -n "$active_win_pid" ]; then
                            active_proc=$(ps -p "$active_win_pid" -o comm= 2>/dev/null | xargs | tr '[:upper:]' '[:lower:]')
                            if [[ "$active_proc" == *"chrome"* || "$active_proc" == *"chromium"* || "$active_proc" == *"python"* || "$active_win_pid" == "$$" ]]; then
                                isChromeFocused=true
                            fi
                        fi
                    else
                        # Jika active_win_id kosong, mungkin di secure screen atau desktop
                        isChromeFocused=false
                    fi
                else
                    # Jika tidak ada xdotool, asumsikan terfokus agar tidak false positive terus-menerus
                    isChromeFocused=true
                fi
                
                if [ "$isChromeFocused" = false ]; then
                    focusLossCounter=$((focusLossCounter + 1))
                    # Debounce: jika fokus hilang selama 5 loop (~1 detik), kunci layar
                    if [ $focusLossCounter -ge 5 ]; then
                        echo "!!! DETEKSI FOCUS LOSS BERULANG! Jendela aktif tidak diizinkan !!!"
                        echo "Mengunci layar..."
                        
                        python3 linux_lock.py "$ServerUrl" "$PcName" "$IpAddress" "$MacAddress"
                        lockResult=$?
                        
                        focusLossCounter=0
                        if [ $lockResult -eq 0 ]; then
                            # Proctor membuka kunci -> Berikan grace period baru
                            gracePeriodRemainingMs=$GracePeriodMs
                        else
                            # Ujian berakhir saat terkunci
                            chromeOpenedByScript=false
                            if [ -n "$chromePid" ]; then
                                kill "$chromePid" 2>/dev/null
                                chromePid=""
                            fi
                        fi
                    fi
                else
                    focusLossCounter=0
                fi
            fi
        fi
    fi
    
    # 3. Heartbeat berkala ke Server
    elapsedTime=$((currentTime - LastHeartbeatTime))
    if [ $elapsedTime -ge $HeartbeatIntervalSeconds ]; then
        LastHeartbeatTime=$currentTime
        
        echo "[$(date +%H:%M:%S)] Heartbeat -> Server (Chrome Running: $chromeRunning)..."
        
        # URL encode parameters
        encodedPc=$(python3 -c "import urllib.parse; print(urllib.parse.quote('''$PcName'''))")
        encodedIp=$(python3 -c "import urllib.parse; print(urllib.parse.quote('''$IpAddress'''))")
        encodedMac=$(python3 -c "import urllib.parse; print(urllib.parse.quote('''$MacAddress'''))")
        
        chromeStatusParam="0"
        if [ "$chromeRunning" = true ]; then
            chromeStatusParam="1"
        fi
        
        Url="$ServerUrl/api/check-jadwal?pc_name=$encodedPc&ip=$encodedIp&chrome_running=$chromeStatusParam"
        if [ -n "$MacAddress" ]; then
            Url="$Url&mac=$encodedMac"
        fi
        
        response=$(curl -s --max-time 5 "$Url")
        curl_status=$?
        
        if [ $curl_status -eq 0 ]; then
            status=$(echo "$response" | grep -oP '(?<="status":")[^"]+' | head -n 1)
            event=$(echo "$response" | grep -oP '(?<="event":")[^"]+' | head -n 1)
            targetUrl=$(echo "$response" | grep -oP '(?<="url":")[^"]+' | head -n 1)
            receivedMode=$(echo "$response" | grep -oP '(?<="browser_mode":")[^"]+' | head -n 1)
            
            if [ "$status" = "success" ]; then
                if [ "$event" != "NO_EVENT" ] && [ -n "$targetUrl" ]; then
                    if [ "$chromeRunning" = false ]; then
                        echo ">>> Event Ujian Aktif Ditemukan: $event"
                        echo ">>> Membuka URL: $targetUrl"
                        
                        chromeCmd=""
                        if command -v google-chrome &> /dev/null; then
                            chromeCmd="google-chrome"
                        elif command -v google-chrome-stable &> /dev/null; then
                            chromeCmd="google-chrome-stable"
                        elif command -v chromium &> /dev/null; then
                            chromeCmd="chromium"
                        elif command -v chromium-browser &> /dev/null; then
                            chromeCmd="chromium-browser"
                        fi
                        
                        if [ -n "$chromeCmd" ]; then
                            finalMode=${receivedMode:-$ChromeMode}
                            echo "Menyiapkan $chromeCmd ($finalMode mode)..."
                            
                            killall "$chromeCmd" 2>/dev/null
                            sleep 1
                            
                            if [ "$finalMode" = "kiosk" ]; then
                                tempProfile="/tmp/chrome_kiosk_profile"
                                mkdir -p "$tempProfile"
                                $chromeCmd --new-window --start-maximized --kiosk --user-data-dir="$tempProfile" --no-first-run --no-default-browser-check "$targetUrl" &
                                chromePid=$!
                            elif [ "$finalMode" = "app" ]; then
                                $chromeCmd --app="$targetUrl" &
                                chromePid=$!
                            else
                                $chromeCmd --new-window --start-maximized "$targetUrl" &
                                chromePid=$!
                            fi
                            chromeOpenedByScript=true
                            chromeRunning=true
                            gracePeriodRemainingMs=$GracePeriodMs
                            focusLossCounter=0
                        else
                            echo "Peringatan: Google Chrome tidak ditemukan. Membuka xdg-open..."
                            xdg-open "$targetUrl" &
                            chromePid=$!
                            chromeOpenedByScript=true
                            chromeRunning=true
                            gracePeriodRemainingMs=$GracePeriodMs
                            focusLossCounter=0
                        fi
                    fi
                else
                    # NO_EVENT
                    if [ "$chromeRunning" = true ] && [ "$chromeOpenedByScript" = true ]; then
                        echo ">>> Ujian telah berakhir atau dinonaktifkan oleh server."
                        echo "Menutup browser otomatis..."
                        chromeOpenedByScript=false
                        if [ -n "$chromePid" ]; then
                            kill "$chromePid" 2>/dev/null
                        fi
                        chromePid=""
                        chromeRunning=false
                    fi
                fi
            fi
        else
            echo "Gagal menghubungi server. Mencoba kembali..."
        fi
    fi
    
    sleep $LoopIntervalSeconds
done

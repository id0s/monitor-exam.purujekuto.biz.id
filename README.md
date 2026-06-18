# SMKN 2 Pekalongan - Exam Controller & Live Monitor

Sistem kontroler dan pemantauan ujian berbasis LAN (Local Area Network) untuk mengunci layar komputer klien (kiosk mode) dan memantau status pengerjaan ujian secara real-time dari komputer pengawas (server).

Sistem ini memiliki dua komponen utama:
1. **Server Dashboard & API** (berbasis **CodeIgniter 4** + **SQLite3**).
2. **Client Script** (berbasis **Shell script / PowerShell** di sisi komputer laboratorium) dan **Android APK** (untuk browser ujian klien mobile).

---

## Fitur Utama

- **Live Monitoring Grid**: Memantau status komputer klien berdasarkan warna indikator secara real-time (Online, Offline, Ready, Terkunci).
- **Exam Lock Screen**: Layar klien akan terkunci secara otomatis jika mereka mencoba menutup browser ujian atau kehilangan fokus jendela. Buka kunci memerlukan kata sandi pengawas.
- **Wake-on-LAN (WOL)**: Mengirimkan sinyal bangun ke komputer klien secara massal per Laboratorium (VLAN) atau secara individu melalui MAC Address.
- **VLAN & Subnet Autodetect**: Klasifikasi otomatis komputer ke dalam Lab 1 - Lab 7 berdasarkan prefix IP Address atau pola nama host PC.
- **Samba File Sharing**: Kemudahan pemetaan folder kode server sebagai Network Drive untuk pembaruan cepat dari Windows.

---

## Persyaratan Sistem Server (Debian 12)

- **Sistem Operasi**: Debian 12 (Bookworm)
- **Web Server**: Nginx
- **PHP**: Versi 8.2 atau lebih tinggi
- **PHP Extensions**: `fpm`, `sqlite3`, `mbstring`, `xml`, `intl`, `curl`, `zip`
- **Database**: SQLite3 (tersimpan di `writable/database.db`)
- **Protokol File Sharing**: Samba (SMB)

---

## Langkah Setup Server (Debian 12)

### 1. Instalasi Paket yang Diperlukan
Jalankan perintah berikut di server Debian 12 Anda untuk menginstal semua ketergantungan:
```bash
sudo apt-get update
sudo apt-get install -y nginx php-fpm php-sqlite3 php-mbstring php-xml php-intl php-curl php-zip samba sqlite3 rsync
```

### 2. Pendeployan Berkas Aplikasi
Tempatkan seluruh folder repositori ini di `/var/www/html/UT/` pada server.

### 3. Konfigurasi Environment (`.env`)
Salin berkas `env` menjadi `.env` di server dan sesuaikan konfigurasinya:
```ini
CI_ENVIRONMENT = production
database.default.database = /var/www/html/UT/writable/database.db
database.default.DBDriver = SQLite3
```

### 4. Pengaturan Hak Akses Folder Database
Agar web server (Nginx/PHP-FPM) dapat menulis ke database SQLite, setel kepemilikan berkas ke user `www-data`:
```bash
sudo chown -R www-data:www-data /var/www/html/UT
sudo chmod -R 775 /var/www/html/UT/writable
sudo chmod 664 /var/www/html/UT/writable/database.db
```

### 5. Konfigurasi Nginx
Buat berkas konfigurasi virtual host di `/etc/nginx/sites-available/ut`:
```nginx
server {
    listen 80;
    listen 8000; # Port yang dihubungi oleh skrip klien PC
    server_name _;

    root /var/www/html/UT/public;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```
Aktifkan konfigurasi dan muat ulang Nginx:
```bash
sudo rm -f /etc/nginx/sites-enabled/default
sudo ln -sf /etc/nginx/sites-available/ut /etc/nginx/sites-enabled/ut
sudo nginx -t && sudo systemctl restart nginx
```

### 6. Konfigurasi Samba (File Sharing)
Tambahkan blok konfigurasi share di bagian bawah `/etc/samba/smb.conf`:
```ini
[UT]
   comment = SMKN 2 Pekalongan Exam Controller Share
   path = /var/www/html/UT
   browseable = yes
   read only = no
   guest ok = no
   create mask = 0775
   directory mask = 0775
   force user = www-data
   force group = www-data
```
Muat ulang layanan Samba dan tambahkan kata sandi akses untuk user `root` atau `dos`:
```bash
sudo systemctl restart smbd nmbd
sudo smbpasswd -a root
sudo smbpasswd -a dos
```

---

## Panduan Penggunaan & Pemetaan Samba di Windows

Guna memperbarui kode atau file aplikasi langsung dari Windows Explorer tanpa SSH:
1. Buka **File Explorer** di Windows Anda.
2. Klik kanan pada **This PC** -> pilih **Map network drive...**.
3. Pilih huruf drive (misal `Z:`).
4. Pada kolom **Folder**, masukkan alamat IP server Anda:
   ```text
   \\192.168.11.7\UT
   ```
5. Centang **Connect using different credentials**, lalu klik **Finish**.
6. Masukkan kredensial Anda (Username: `root` atau `dos`, Password sesuai yang Anda buat pada langkah Samba).
7. Sekarang, berkas server langsung terpetakan di Windows Anda dan siap diedit!

---

## Konfigurasi Klien (Client PC)

### A. Untuk Klien OS Linux
Ubah variabel `ServerUrl` di file `client_script.sh` agar mengarah ke IP server Anda:
```bash
ServerUrl="http://192.168.11.7:8000"
```
Jalankan skrip pemantauan di komputer laboratorium:
```bash
bash client_script.sh
```

### B. Untuk Klien OS Windows
Ubah variabel `$serverUrl` di file `client_script.ps1` agar mengarah ke IP server Anda:
```powershell
$serverUrl = "http://192.168.11.7:8000"
```
Jalankan skrip via PowerShell:
```powershell
./client_script.ps1
```

### C. Untuk Klien Android (Aplikasi APK)
Instal file `KioskMonitor.apk` pada ponsel/tablet siswa. Aplikasi ini secara otomatis mendeteksi ujian aktif dari server jika diakses lewat jaringan Wi-Fi sekolah.

---

## Troubleshooting & Pemecahan Masalah

### Error: `Unable to prepare statement: no such column...`
Hal ini terjadi karena skema database SQLite lama (dari Python/FastAPI) menimpa skema database CodeIgniter. 
* **Solusi**: Sistem ini telah dilengkapi modul auto-repair database. Cukup refresh halaman dashboard utama Anda di browser sekali, maka sistem akan mendeteksi perbedaan skema, menghapus tabel lama, dan menyusun ulang struktur tabel yang benar secara otomatis.

### Error: `Permission denied` saat menulis data ping klien
* **Solusi**: Pastikan folder `writable/` dan file database di dalam server dimiliki oleh user Nginx (`www-data`). Jalankan:
  ```bash
  sudo chown -R www-data:www-data /var/www/html/UT/writable
  ```

# Kiosk Monitor - Aplikasi Pemantau Ujian Android (APK)

Aplikasi native Android ini dirancang khusus untuk memudahkan pengawas atau proktor dalam memantau status PC lab sekolah (apakah Chrome terbuka, terkunci, dll.) langsung dari HP Android secara real-time.

Aplikasi ini menggunakan `WebView` yang responsif, dilengkapi fitur **Swipe-to-Refresh** (tarik layar ke bawah untuk memuat ulang) dan **Halaman Pengaturan IP** yang menyimpan URL server secara permanen di memori HP.

## Fitur Utama
1. **Konfigurasi IP Dinamis:** Memungkinkan Anda mengatur IP address laptop server secara fleksibel (contoh: `http://192.168.60.158:8000`).
2. **Swipe to Refresh:** Cukup tarik layar ke bawah untuk memperbarui status PC terkini.
3. **Penanganan Koneksi Terputus:** Menampilkan layar error kustom dengan tombol "Coba Lagi" jika jaringan terputus atau server mati.
4. **Navigasi Back Cerdas:** Menekan tombol kembali (back) di HP akan mundur ke halaman sebelumnya di dashboard web, bukan langsung keluar dari aplikasi.

---

## Cara Kompilasi Menggunakan Android Studio (Menjadi APK)

Untuk mengubah source code ini menjadi file `.apk` siap install:

1. **Persiapan:**
   - Install **Android Studio** di komputer Anda (versi Giraffe ke atas direkomendasikan).
   - Pastikan komputer Anda terhubung ke internet untuk mendownload dependensi Gradle pertama kali.

2. **Membuka Proyek:**
   - Buka Android Studio.
   - Pilih menu **File** -> **Open...**
   - Arahkan ke folder proyek ini (`android-monitor`) lalu klik **OK**.
   - Tunggu proses *Gradle Sync* selesai (biasanya memakan waktu 1-3 menit tergantung kecepatan internet Anda).

3. **Membuat File APK (Build APK):**
   - Pada menu bar atas Android Studio, pilih **Build** -> **Build Bundle(s) / APK(s)** -> **Build APK(s)**.
   - Tunggu proses kompilasi selesai.
   - Setelah selesai, akan muncul notifikasi di pojok kanan bawah dengan tulisan: *APK(s) generated successfully.*
   - Klik tulisan **locate** pada notifikasi tersebut untuk langsung membuka folder tempat file `.apk` berada (biasanya di `app/build/outputs/apk/debug/app-debug.apk`).

4. **Instalasi di HP Android:**
   - Kirim file `app-debug.apk` tersebut ke HP Android Anda (bisa lewat WhatsApp, Google Drive, atau kabel USB).
   - Buka file APK tersebut di HP Android Anda untuk menginstalnya.
   - *Catatan:* Jika muncul peringatan "Install dari sumber tidak dikenal" (Unknown Sources), aktifkan izin tersebut untuk melanjutkan instalasi.

---

## Cara Penggunaan
1. Hubungkan HP Android Anda ke jaringan Wi-Fi sekolah yang satu subnet / terhubung dengan server ujian.
2. Buka aplikasi **Kiosk Monitor**.
3. Saat pertama kali dibuka, aplikasi akan meminta Anda memasukkan IP address server. Masukkan URL lengkap server Anda (contoh: `http://192.168.60.158:8000`).
4. Klik **Simpan Konfigurasi**.
5. Dashboard pemantauan ujian SMKN 2 Pekalongan akan termuat secara penuh di HP Anda.
6. Untuk merubah IP server di kemudian hari, klik ikon **Gigi Roda/Titik Tiga (Pengaturan)** di bagian kanan atas toolbar.

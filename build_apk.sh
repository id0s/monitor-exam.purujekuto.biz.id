#!/bin/bash
set -e

# =====================================================================
# build_apk.sh - Script untuk mengunduh build tools & kompilasi APK
# =====================================================================

PROJECT_DIR="/home/dosq/Unduhan/UT"
TOOLS_DIR="$PROJECT_DIR/android_build_tools"
SDK_DIR="$TOOLS_DIR/sdk"
GRADLE_DIR="$TOOLS_DIR/gradle"
JDK_DIR="$TOOLS_DIR/jdk17"

# Buat folder build tools jika belum ada
mkdir -p "$TOOLS_DIR"

echo "=== [1/6] Mengunduh dan Mengekstrak JDK 17 (Temurin) ==="
if [ ! -d "$JDK_DIR" ]; then
    echo "Mengunduh JDK 17..."
    wget -q --show-progress -O "$TOOLS_DIR/openjdk17.tar.gz" "https://api.adoptium.net/v3/binary/latest/17/ga/linux/x64/jdk/hotspot/normal/eclipse"
    echo "Mengekstrak JDK 17..."
    mkdir -p "$JDK_DIR"
    tar -xzf "$TOOLS_DIR/openjdk17.tar.gz" -C "$JDK_DIR" --strip-components=1
    rm "$TOOLS_DIR/openjdk17.tar.gz"
    echo "JDK 17 siap."
else
    echo "JDK 17 sudah terinstal."
fi

# Set Environment Variables untuk proses berikutnya
export JAVA_HOME="$JDK_DIR"
export ANDROID_HOME="$SDK_DIR"
export PATH="$GRADLE_DIR/bin:$SDK_DIR/cmdline-tools/latest/bin:$JAVA_HOME/bin:$PATH"

echo "=== [2/6] Mengunduh dan Mengekstrak Gradle 8.5 ==="
if [ ! -d "$GRADLE_DIR" ]; then
    echo "Mengunduh Gradle 8.5..."
    wget -q --show-progress -O "$TOOLS_DIR/gradle.zip" "https://services.gradle.org/distributions/gradle-8.5-bin.zip"
    echo "Mengekstrak Gradle..."
    mkdir -p "$TOOLS_DIR/gradle_temp"
    unzip -q "$TOOLS_DIR/gradle.zip" -d "$TOOLS_DIR/gradle_temp"
    mv "$TOOLS_DIR/gradle_temp"/gradle-* "$GRADLE_DIR"
    rm -rf "$TOOLS_DIR/gradle_temp" "$TOOLS_DIR/gradle.zip"
    echo "Gradle siap."
else
    echo "Gradle sudah terinstal."
fi

echo "=== [3/6] Mengunduh dan Mengekstrak Android Command Line Tools ==="
if [ ! -d "$SDK_DIR/cmdline-tools/latest" ]; then
    echo "Mengunduh Command Line Tools..."
    wget -q --show-progress -O "$TOOLS_DIR/cmdline.zip" "https://dl.google.com/android/repository/commandlinetools-linux-11076708_latest.zip"
    echo "Mengekstrak Command Line Tools..."
    mkdir -p "$TOOLS_DIR/cmdline_temp"
    unzip -q "$TOOLS_DIR/cmdline.zip" -d "$TOOLS_DIR/cmdline_temp"
    mkdir -p "$SDK_DIR/cmdline-tools/latest"
    mv "$TOOLS_DIR/cmdline_temp/cmdline-tools"/* "$SDK_DIR/cmdline-tools/latest/"
    rm -rf "$TOOLS_DIR/cmdline_temp" "$TOOLS_DIR/cmdline.zip"
    echo "Command Line Tools siap."
else
    echo "Command Line Tools sudah terinstal."
fi

echo "=== [4/6] Menginstal Android Platform SDK & Build Tools ==="
# Menyetujui lisensi SDK otomatis
echo "Menyetujui lisensi SDK..."
yes | sdkmanager --licenses > /dev/null

# Menginstal platform 34 dan build tools 34.0.0
echo "Menginstal platform-34 & build-tools-34.0.0..."
sdkmanager --install "platforms;android-34" "build-tools;34.0.0"

echo "=== [5/6] Memulai Kompilasi APK (Gradle Build) ==="
cd "$PROJECT_DIR/android-monitor"

# Jalankan clean & build gradle
gradle clean assembleDebug --no-daemon

echo "=== [6/6] Selesai! Menyalin APK Output ==="
APK_OUTPUT="app/build/outputs/apk/debug/app-debug.apk"
if [ -f "$APK_OUTPUT" ]; then
    cp "$APK_OUTPUT" "$PROJECT_DIR/KioskMonitor.apk"
    echo "======================================================="
    echo "  SUKSES! Aplikasi berhasil dikompilasi."
    echo "  File APK siap dipasang: $PROJECT_DIR/KioskMonitor.apk"
    echo "======================================================="
else
    echo "ERROR: File APK tidak ditemukan di: $APK_OUTPUT"
    exit 1
fi

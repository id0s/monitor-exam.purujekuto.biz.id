<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMKN 2 Pekalongan - Controller Ujian Lab</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        smk: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            200: '#b9dffd',
                            300: '#7cc2fc',
                            400: '#36a2fa',
                            500: '#0c84eb',
                            600: '#0066c7',
                            700: '#0052a1',
                            800: '#044685',
                            900: '#093b6e',
                            950: '#052245',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #030816;
            color: #e2e8f0;
        }
        .outfit-font {
            font-family: 'Outfit', sans-serif;
        }
        /* Glassmorphism card */
        .glass-card {
            background: rgba(5, 18, 48, 0.65);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(12, 132, 235, 0.15);
        }
        /* Custom Glowing and shadows */
        .glow-smk {
            box-shadow: 0 0 20px rgba(12, 132, 235, 0.25);
        }
        .glow-emerald {
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
        }
        .glow-cyan {
            box-shadow: 0 0 20px rgba(6, 182, 212, 0.25);
        }
        .glow-red {
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.45);
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 15px rgba(12, 132, 235, 0.2); }
            50% { box-shadow: 0 0 25px rgba(6, 182, 212, 0.5); }
        }
        .pulse-glow-active {
            animation: pulse-glow 2s infinite ease-in-out;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(3, 8, 22, 0.5);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(12, 132, 235, 0.25);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(12, 132, 235, 0.45);
        }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden pb-12">
    <!-- Navbar -->
    <nav class="border-b border-smk-900/60 bg-smk-950/80 backdrop-blur sticky top-0 z-50 px-6 py-4 shadow-lg shadow-smk-950/20">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <!-- Branding SMK 2 Pekalongan Logo Placeholder -->
                <div class="bg-gradient-to-tr from-smk-700 to-cyan-500 text-white p-2.5 rounded-xl glow-smk shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold outfit-font tracking-wide bg-clip-text text-transparent bg-gradient-to-r from-white via-smk-200 to-cyan-300">
                        SMK NEGERI 2 PEKALONGAN
                    </h1>
                    <p class="text-xs text-smk-300">Controller &amp; Live Monitoring Ujian Lab</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right hidden md:block">
                    <p id="live-clock" class="text-sm font-semibold tracking-wider font-mono text-cyan-400">00:00:00</p>
                    <p class="text-[10px] text-slate-400 font-mono">Pekalongan, Indonesia</p>
                </div>
                <!-- Status Mode -->
                <div id="badge-mode" class="flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-slate-800 border border-slate-700 text-slate-400 transition-all duration-300">
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-500"></span>
                    <span>MODE NORMAL</span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 space-y-8">
        
        <!-- TOP: Control & Stats -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Form Control Panel -->
            <div class="lg:col-span-1 glass-card rounded-2xl p-6 shadow-xl relative overflow-hidden flex flex-col justify-between border-smk-800/40">
                <div class="absolute -top-12 -right-12 w-32 h-32 bg-smk-500/10 rounded-full blur-2xl"></div>
                <div>
                    <h2 class="text-lg font-semibold outfit-font mb-4 flex items-center gap-2 text-smk-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        Panel Kontrol Ujian
                    </h2>
                    
                    <!-- Form Set Event -->
                    <form id="form-set-jadwal" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-smk-300 mb-1.5 uppercase tracking-wider">Nama Event Ujian</label>
                            <input type="text" name="nama_event" id="input-nama-event" required placeholder="Contoh: Penilaian Akhir Semester" 
                                class="w-full bg-smk-950/60 border border-smk-800/80 focus:border-cyan-500 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 focus:outline-none focus:ring-1 focus:ring-cyan-500 transition duration-150">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-smk-300 mb-1.5 uppercase tracking-wider">URL Target Ujian</label>
                            <input type="text" name="url_target" id="input-url-target" required placeholder="Contoh: http://192.168.10.10/ujian" 
                                class="w-full bg-smk-950/60 border border-smk-800/80 focus:border-cyan-500 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 focus:outline-none focus:ring-1 focus:ring-cyan-500 transition duration-150">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-smk-300 mb-1.5 uppercase tracking-wider">Target Lab</label>
                            <select name="target_lab" id="input-target-lab" required
                                class="w-full bg-smk-950/60 border border-smk-800/80 focus:border-cyan-500 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 focus:outline-none focus:ring-1 focus:ring-cyan-500 transition duration-150">
                                <option value="ALL">Semua Lab (Global)</option>
                                <?php foreach ($vlan_configs as $config): ?>
                                    <option value="<?= esc($config['vlan_id']) ?>"><?= esc($config['lab_name']) ?> (VLAN <?= esc($config['vlan_id']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-smk-300 mb-1.5 uppercase tracking-wider">Mode Browser Klien</label>
                            <select name="browser_mode" id="input-browser-mode" required
                                class="w-full bg-smk-950/60 border border-smk-800/80 focus:border-cyan-500 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 focus:outline-none focus:ring-1 focus:ring-cyan-500 transition duration-150">
                                <option value="kiosk">Strict Kiosk (Layar Penuh Terkunci)</option>
                                <option value="app">App Mode (Jendela Minimalis)</option>
                                <option value="maximized">Standard Mode (Jendela Dimaksimalkan)</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-smk-300 mb-1.5 uppercase tracking-wider">Waktu Mulai</label>
                                <input type="datetime-local" name="waktu_mulai" id="input-waktu-mulai" required 
                                    class="w-full bg-smk-950/60 border border-smk-800/80 focus:border-cyan-500 rounded-xl px-2.5 py-2.5 text-xs text-slate-200 focus:outline-none transition duration-150">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-smk-300 mb-1.5 uppercase tracking-wider">Waktu Selesai</label>
                                <input type="datetime-local" name="waktu_selesai" id="input-waktu-selesai" required 
                                    class="w-full bg-smk-950/60 border border-smk-800/80 focus:border-cyan-500 rounded-xl px-2.5 py-2.5 text-xs text-slate-200 focus:outline-none transition duration-150">
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-gradient-to-r from-smk-600 to-cyan-600 hover:from-smk-500 hover:to-cyan-500 text-white font-bold text-sm py-3 px-4 rounded-xl transition duration-150 transform active:scale-95 shadow-lg shadow-smk-500/20">
                            Jadwalkan Ujian
                        </button>
                    </form>

                    <!-- Deactivate Button (Visible when active) -->
                    <div id="active-event-controls" class="hidden mt-4 pt-4 border-t border-smk-900/60">
                        <div class="bg-smk-950/80 border border-smk-800/50 rounded-xl p-3.5 mb-4">
                            <p class="text-[10px] text-cyan-400 font-bold uppercase tracking-wider">EVENT AKTIF SAAT INI</p>
                            <p id="active-event-name" class="text-sm font-bold text-slate-100 truncate mt-1">-</p>
                            <p id="active-event-url" class="text-xs text-smk-300 truncate mt-0.5" style="max-width: 240px;">-</p>
                            <div class="mt-2.5 pt-2 border-t border-smk-900/40 text-[9px] text-slate-400 font-mono flex flex-col gap-0.5">
                                <div><span class="text-cyan-400">Mulai:</span> <span id="active-event-start">-</span></div>
                                <div><span class="text-cyan-400">Selesai:</span> <span id="active-event-end">-</span></div>
                            </div>
                        </div>
                        <button onclick="deactivateUjian()" class="w-full bg-gradient-to-r from-red-700 to-rose-600 hover:from-red-650 hover:to-rose-500 text-white font-bold text-sm py-2.5 px-4 rounded-xl transition duration-150 transform active:scale-95 shadow-lg shadow-red-500/20">
                            Nonaktifkan Ujian
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Stat: Total PC -->
                <div class="glass-card rounded-2xl p-6 shadow-xl flex flex-col justify-between relative overflow-hidden group hover:border-smk-700 transition duration-300">
                    <div class="absolute -top-12 -right-12 w-32 h-32 bg-smk-500/5 rounded-full blur-2xl"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-smk-300 uppercase tracking-wider">Total PC Terdaftar</p>
                            <p id="stat-total-pc" class="text-4xl font-extrabold outfit-font mt-2 text-white">0</p>
                        </div>
                        <div class="bg-smk-900/40 text-smk-300 p-2.5 rounded-xl border border-smk-800/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-xs text-slate-400 border-t border-smk-900/60 pt-3">
                        <span>Pemberian IP Statis &amp; VLAN</span>
                        <span class="font-mono bg-smk-950 px-2 py-0.5 rounded text-[10px] text-cyan-400 border border-smk-900/50">7 Lab Terstruktur</span>
                    </div>
                </div>

                <!-- Stat: Chrome Terbuka -->
                <div class="glass-card rounded-2xl p-6 shadow-xl flex flex-col justify-between relative overflow-hidden group hover:border-emerald-500/30 transition duration-300 glow-emerald">
                    <div class="absolute -top-12 -right-12 w-32 h-32 bg-emerald-500/5 rounded-full blur-2xl"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Chrome Sudah Terbuka</p>
                            <p id="stat-terbuka-pc" class="text-4xl font-extrabold outfit-font mt-2 text-emerald-400">0</p>
                        </div>
                        <div class="bg-emerald-950/80 text-emerald-400 p-2.5 rounded-xl border border-emerald-900/40">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 border-t border-smk-900/60 pt-3">
                        <div class="flex justify-between text-xs text-slate-400 mb-1.5">
                            <span>Persentase Kesiapan</span>
                            <span id="percent-ready" class="font-bold text-emerald-400">0%</span>
                        </div>
                        <div class="w-full bg-slate-900 rounded-full h-1.5 overflow-hidden border border-slate-800">
                            <div id="bar-ready" class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <!-- Stat: Chrome Belum Terbuka -->
                <div class="glass-card rounded-2xl p-6 shadow-xl flex flex-col justify-between relative overflow-hidden group hover:border-amber-500/30 transition duration-300">
                    <div class="absolute -top-12 -right-12 w-32 h-32 bg-amber-500/5 rounded-full blur-2xl"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-amber-400 uppercase tracking-wider">Chrome Belum Terbuka</p>
                            <p id="stat-belum-pc" class="text-4xl font-extrabold outfit-font mt-2 text-amber-400">0</p>
                        </div>
                        <div class="bg-amber-950/80 text-amber-400 p-2.5 rounded-xl border border-amber-900/40">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-xs text-slate-400 border-t border-smk-900/60 pt-3">
                        <span>Menunggu Ping dari PC Lab</span>
                        <span id="percent-pending" class="text-amber-400 font-bold">100%</span>
                    </div>
                </div>

                <!-- Stat: Pings Aktif (Online) -->
                <div class="glass-card rounded-2xl p-6 shadow-xl flex flex-col justify-between relative overflow-hidden group hover:border-cyan-500/30 transition duration-300 glow-cyan">
                    <div class="absolute -top-12 -right-12 w-32 h-32 bg-cyan-500/5 rounded-full blur-2xl"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-cyan-400 uppercase tracking-wider">PC Online (Ping Aktif)</p>
                            <p id="stat-online-pc" class="text-4xl font-extrabold outfit-font mt-2 text-cyan-400">0</p>
                        </div>
                        <div class="bg-cyan-950/80 text-cyan-400 p-2.5 rounded-xl border border-cyan-900/40">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-xs text-slate-400 border-t border-smk-900/60 pt-3">
                        <span>Aktif terhubung dalam 60s terakhir</span>
                        <span class="text-cyan-400 flex items-center gap-1 font-semibold">
                            <span class="w-2 h-2 bg-cyan-400 rounded-full animate-ping"></span>
                            Live Poll
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Global Action Controls -->
        <div class="glass-card rounded-2xl p-4 flex flex-col sm:flex-row justify-between items-center gap-4 border-smk-800/40">
            <!-- Search & filter -->
            <div class="flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto">
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-smk-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text" id="search-pc" placeholder="Cari Nama PC atau IP..." 
                        class="w-full bg-smk-950/50 border border-smk-800/80 focus:border-cyan-500 rounded-xl pl-10 pr-4 py-2 text-sm text-slate-200 focus:outline-none transition">
                </div>
                <!-- Filter Toggle -->
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="filter-belum-terbuka" class="rounded border-smk-800 bg-smk-950 text-cyan-600 focus:ring-cyan-500 focus:ring-offset-smk-950">
                    <span class="text-xs text-smk-200">Tampilkan Hanya "Belum Terbuka"</span>
                </label>
            </div>
            <!-- Action buttons -->
            <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto justify-end">
                <button onclick="openVlanConfigModal()" class="flex items-center gap-2 bg-smk-900/30 hover:bg-smk-900/60 text-smk-200 border border-smk-800/50 text-xs font-bold py-2.5 px-4 rounded-xl transition transform active:scale-95 shadow-md" title="Atur Prefix IP/Subnet untuk setiap Lab">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Pengaturan Subnet Lab
                </button>
                <button onclick="openWakeOnLanModal()" class="flex items-center gap-2 bg-smk-900/30 hover:bg-smk-900/60 text-smk-200 border border-smk-800/50 text-xs font-bold py-2.5 px-4 rounded-xl transition transform active:scale-95 shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Wake-on-LAN Kustom
                </button>
                <button onclick="resetAllStatus()" class="flex items-center gap-2 bg-red-950/30 hover:bg-red-900/40 text-red-400 border border-red-900/40 text-xs font-bold py-2.5 px-4 rounded-xl transition transform active:scale-95 shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.28 15H18" />
                    </svg>
                    Reset Status Semua PC
                </button>
                <button onclick="deleteAllPcs()" class="flex items-center gap-2 bg-gradient-to-r from-red-950/45 to-rose-950/35 hover:from-red-900/45 hover:to-rose-900/45 text-red-300 border border-red-900/50 text-xs font-bold py-2.5 px-4 rounded-xl transition transform active:scale-95 shadow-md" title="Hapus seluruh PC dari database untuk registrasi ulang subnet baru">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Bersihkan Semua PC
                </button>
            </div>
        </div>

        <!-- MIDDLE: Lab Tabs & Grid Monitoring -->
        <div class="glass-card rounded-3xl overflow-hidden shadow-2xl border-smk-800/40">
            <!-- Lab Navigation Tabs -->
            <div class="border-b border-smk-900/60 bg-smk-950/35 p-4 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex flex-wrap gap-1.5" id="lab-tab-list">
                    <!-- Tab Buttons populated via JS -->
                </div>
                <!-- Lab Controls -->
                <div class="flex items-center gap-3">
                    <button id="btn-wol-lab" onclick="wakeOnLanLab()" class="flex items-center gap-1.5 bg-smk-500/10 hover:bg-smk-500/20 text-cyan-400 border border-smk-800/60 text-xs font-bold py-1.5 px-3.5 rounded-lg transition shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        WOL Lab Ini
                    </button>
                    <button id="btn-reset-lab" onclick="resetLabStatus()" class="flex items-center gap-1.5 bg-red-950/20 hover:bg-red-950/40 text-red-400 border border-red-900/30 text-xs font-bold py-1.5 px-3.5 rounded-lg transition shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.28 15H18" />
                        </svg>
                        Reset Lab Ini
                    </button>
                </div>
            </div>

            <!-- Monitoring Grid -->
            <div class="p-6">
                <!-- Active Lab Label -->
                <div class="flex items-center justify-between mb-5">
                    <h3 id="active-lab-title" class="text-base font-bold outfit-font text-smk-200 tracking-wide">LAB 1 (VLAN 10)</h3>
                    <span id="lab-counter" class="text-xs text-smk-400 font-medium">Loading PCs...</span>
                </div>
                <!-- PC Grid -->
                <div id="pc-grid" class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-7 gap-4">
                    <!-- PC Cards populated via JS -->
                </div>
            </div>
        </div>

        <!-- BOTTOM: Event History List -->
        <div class="glass-card rounded-2xl p-6 shadow-xl border-smk-800/40">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5 border-b border-smk-900/40 pb-4">
                <h3 class="text-lg font-semibold outfit-font flex items-center gap-2 text-smk-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-smk-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Jadwal &amp; Riwayat Ujian
                </h3>
                <!-- Tab Buttons -->
                <div class="flex bg-smk-950/80 border border-smk-900/50 p-1 rounded-xl">
                    <button onclick="switchHistoryTab('table')" id="btn-tab-table" class="px-4 py-1.5 rounded-lg text-xs font-bold transition duration-200 bg-gradient-to-r from-smk-600 to-cyan-600 text-white shadow-md">
                        Daftar Tabel
                    </button>
                    <button onclick="switchHistoryTab('weekly')" id="btn-tab-weekly" class="px-4 py-1.5 rounded-lg text-xs font-bold transition duration-200 text-smk-300 hover:text-slate-200">
                        Visual Jadwal 7 Hari
                    </button>
                </div>
            </div>

            <!-- Tab Content: Table -->
            <div id="hist-table-container" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-smk-900 text-sm">
                    <thead>
                        <tr class="text-smk-300 text-left">
                            <th class="pb-3 font-bold uppercase tracking-wider text-xs">Nama Event</th>
                            <th class="pb-3 font-bold uppercase tracking-wider text-xs">Target Lab</th>
                            <th class="pb-3 font-bold uppercase tracking-wider text-xs">URL Target</th>
                            <th class="pb-3 font-bold uppercase tracking-wider text-xs">Waktu Mulai</th>
                            <th class="pb-3 font-bold uppercase tracking-wider text-xs">Waktu Selesai</th>
                            <th class="pb-3 font-bold uppercase tracking-wider text-xs">Status</th>
                            <th class="pb-3 font-bold uppercase tracking-wider text-xs text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="history-table-body" class="divide-y divide-smk-900/50 text-slate-300">
                        <!-- Table rows populated via JS -->
                        <tr>
                            <td colspan="7" class="py-4 text-center text-smk-500 text-xs">Memuat data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tab Content: Weekly Visual Grid -->
            <div id="hist-weekly-container" class="hidden overflow-x-auto">
                <table class="min-w-full divide-y divide-smk-900 text-sm border-collapse">
                    <thead>
                        <tr class="text-smk-300 text-left border-b border-smk-900">
                            <th class="pb-3 pr-4 font-bold uppercase tracking-wider text-xs">Hari / Tanggal</th>
                            <th class="pb-3 px-2 font-bold uppercase tracking-wider text-xs text-center border-l border-smk-900/60">Lab 1 (10)</th>
                            <th class="pb-3 px-2 font-bold uppercase tracking-wider text-xs text-center border-l border-smk-900/60">Lab 2 (20)</th>
                            <th class="pb-3 px-2 font-bold uppercase tracking-wider text-xs text-center border-l border-smk-900/60">Lab 3 (30)</th>
                            <th class="pb-3 px-2 font-bold uppercase tracking-wider text-xs text-center border-l border-smk-900/60">Lab 4 (40)</th>
                            <th class="pb-3 px-2 font-bold uppercase tracking-wider text-xs text-center border-l border-smk-900/60">Lab 5 (50)</th>
                            <th class="pb-3 px-2 font-bold uppercase tracking-wider text-xs text-center border-l border-smk-900/60">Lab 6 (60)</th>
                            <th class="pb-3 px-2 font-bold uppercase tracking-wider text-xs text-center border-l border-smk-900/60">Lab 7 (70)</th>
                        </tr>
                    </thead>
                    <tbody id="weekly-grid-body" class="divide-y divide-smk-900/50 text-slate-300">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
    </main>

    <!-- Modal: Edit PC -->
    <div id="edit-pc-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm opacity-0 pointer-events-none transition-all duration-300">
        <div class="glass-card w-full max-w-md rounded-2xl p-6 shadow-2xl transform scale-95 transition-all duration-300 border-smk-800/40">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-lg font-bold outfit-font text-white">Edit Detail PC</h3>
                <button onclick="closeEditPcModal()" class="text-smk-400 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <form id="form-edit-pc" class="space-y-4">
                <input type="hidden" name="ip_address" id="edit-pc-ip">
                <div>
                    <label class="block text-xs font-bold text-smk-300 mb-1.5 uppercase tracking-wider">IP ADDRESS (Read-only)</label>
                    <input type="text" id="edit-pc-ip-display" disabled class="w-full bg-slate-900 border border-smk-900 rounded-xl px-3.5 py-2.5 text-sm text-slate-400 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-smk-300 mb-1.5 uppercase tracking-wider">NAMA PC</label>
                    <input type="text" name="nama_pc" id="edit-pc-name" required class="w-full bg-smk-950/50 border border-smk-800/80 focus:border-cyan-500 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-smk-300 mb-1.5 uppercase tracking-wider">MAC ADDRESS</label>
                    <input type="text" name="mac_address" id="edit-pc-mac" class="w-full bg-smk-950/50 border border-smk-800/80 focus:border-cyan-550 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-smk-300 mb-1.5 uppercase tracking-wider">LAB / VLAN</label>
                    <select name="vlan_id" id="edit-pc-vlan" class="w-full bg-smk-950/50 border border-smk-800/80 focus:border-cyan-500 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 focus:outline-none transition">
                        <?php foreach ($vlan_configs as $config): ?>
                            <option value="<?= esc($config['vlan_id']) ?>"><?= esc($config['lab_name']) ?> (VLAN <?= esc($config['vlan_id']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="deletePc()" class="flex-1 bg-gradient-to-r from-red-700 to-rose-600 hover:from-red-650 hover:to-rose-500 text-white font-bold text-sm py-3 px-4 rounded-xl transition duration-150 transform active:scale-95 shadow-lg shadow-red-500/20">
                        Hapus PC
                    </button>
                    <button type="submit" class="flex-[2] bg-gradient-to-r from-smk-600 to-cyan-600 hover:from-smk-500 hover:to-cyan-500 text-white font-bold text-sm py-3 px-4 rounded-xl transition duration-150 transform active:scale-95 shadow-lg shadow-smk-650/20">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Pengaturan Subnet Lab -->
    <div id="vlan-config-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm opacity-0 pointer-events-none transition-all duration-300">
        <div class="glass-card w-full max-w-lg rounded-2xl p-6 shadow-2xl transform scale-95 transition-all duration-300 border-smk-800/40">
            <div class="flex justify-between items-center mb-5">
                <div>
                    <h3 class="text-lg font-bold outfit-font text-white">Pengaturan Subnet Lab</h3>
                    <p class="text-[10px] text-slate-400 mt-0.5">Tentukan awalan IP (Prefix) agar PC baru otomatis masuk ke Lab yang benar.</p>
                </div>
                <button onclick="closeVlanConfigModal()" class="text-smk-400 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <form id="form-vlan-config" class="space-y-4">
                <div id="vlan-rows-container" class="max-h-[300px] overflow-y-auto pr-1 space-y-3">
                    <!-- Dinamis terisi oleh JS -->
                </div>
                
                <button type="button" onclick="addNewVlanRow()" class="w-full py-2 bg-smk-950/20 hover:bg-smk-900/30 border border-dashed border-smk-800/80 hover:border-cyan-500/50 text-xs font-bold text-smk-300 hover:text-white rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    + Tambah VLAN / Lab Baru
                </button>
                
                <div class="pt-2 border-t border-smk-900/60 flex justify-between items-center text-[10px] text-amber-400 gap-2 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span><strong>PENTING:</strong> Masukkan awalan IP lengkap dengan titik terakhir (contoh: <code>10.10.1.</code> untuk menjaring range <code>10.10.1.1</code> s.d <code>10.10.1.254</code>).</span>
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-smk-600 to-cyan-600 hover:from-smk-500 hover:to-cyan-500 text-white font-bold text-sm py-3.5 px-4 rounded-xl transition duration-150 transform active:scale-95 shadow-lg shadow-smk-650/20">
                    Simpan Konfigurasi Subnet
                </button>
            </form>
        </div>
    </div>

    <!-- Modal: Wake On Lan Custom -->
    <div id="wol-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm opacity-0 pointer-events-none transition-all duration-300">
        <div class="glass-card w-full max-w-md rounded-2xl p-6 shadow-2xl transform scale-95 transition-all duration-300 border-smk-800/40">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-lg font-bold outfit-font text-white">Wake-on-LAN Kustom</h3>
                <button onclick="closeWakeOnLanModal()" class="text-smk-400 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <form id="form-wol-custom" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-smk-300 mb-1.5 uppercase tracking-wider">TIPE TARGET</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="bg-smk-950/50 border border-smk-800 hover:border-smk-600 rounded-xl p-3 flex items-center justify-center cursor-pointer select-none text-sm text-slate-200">
                            <input type="radio" name="target_type" value="single" checked class="mr-2 text-cyan-600 focus:ring-0 focus:ring-offset-0 bg-slate-950" onchange="toggleWolModalFields()">
                            PC Tunggal
                        </label>
                        <label class="bg-smk-950/50 border border-smk-800 hover:border-smk-600 rounded-xl p-3 flex items-center justify-center cursor-pointer select-none text-sm text-slate-200">
                            <input type="radio" name="target_type" value="lab" class="mr-2 text-cyan-600 focus:ring-0 focus:ring-offset-0 bg-slate-950" onchange="toggleWolModalFields()">
                            Satu Lab (VLAN)
                        </label>
                    </div>
                </div>

                <div id="wol-field-mac">
                    <label class="block text-xs font-bold text-smk-300 mb-1.5 uppercase tracking-wider">MAC ADDRESS TARGET</label>
                    <input type="text" id="input-wol-mac" placeholder="Contoh: 00:1A:2B:3C:4D:5E" 
                        class="w-full bg-smk-950/50 border border-smk-800/80 focus:border-cyan-500 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-indigo-500 transition">
                </div>

                <div id="wol-field-vlan" class="hidden">
                    <label class="block text-xs font-bold text-smk-300 mb-1.5 uppercase tracking-wider">LAB / VLAN ID</label>
                    <select id="select-wol-vlan" class="w-full bg-smk-950/50 border border-smk-800/80 focus:border-cyan-500 rounded-xl px-3.5 py-2.5 text-sm text-slate-200 focus:outline-none transition">
                        <?php foreach ($vlan_configs as $config): ?>
                            <option value="<?= esc($config['vlan_id']) ?>"><?= esc($config['lab_name']) ?> (VLAN <?= esc($config['vlan_id']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-smk-600 to-cyan-600 hover:from-smk-500 hover:to-cyan-500 text-white font-bold text-sm py-3.5 px-4 rounded-xl transition duration-150 transform active:scale-95 shadow-lg shadow-smk-650/20">
                    Kirim Sinyal Bangun (WOL)
                </button>
            </form>
        </div>
    </div>

    <!-- Alert Notification Toast -->
    <div id="toast" class="fixed bottom-6 right-6 z-50 flex items-center gap-3 bg-smk-950 border border-smk-800 text-sm font-medium px-4 py-3.5 rounded-xl shadow-2xl opacity-0 transform translate-y-4 pointer-events-none transition-all duration-300">
        <div id="toast-icon" class="text-emerald-450 bg-emerald-950/80 border border-emerald-800/40 p-1.5 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" />
            </svg>
        </div>
        <span id="toast-message" class="text-slate-200">Notifikasi</span>
    </div>

    <!-- Script Application -->
    <script>
        // Global variables
        let currentLabId = "<?= esc($vlanId) ?>";
        let pcsData = [];
        let activeJadwal = null;
        const labs = <?= json_encode(array_map(function($config) {
            return [
                'id' => $config['vlan_id'],
                'name' => $config['lab_name']
            ];
        }, $vlan_configs)) ?>;
        
        let initialVlans = <?= json_encode($vlan_configs) ?>;
        let activeVlanConfigs = JSON.parse(JSON.stringify(initialVlans));

        function renderVlanRows() {
            const container = document.getElementById("vlan-rows-container");
            if (activeVlanConfigs.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-6 border border-dashed border-smk-900/60 rounded-xl text-xs text-slate-400">
                        Belum ada VLAN yang dikonfigurasi. Klik "+ Tambah VLAN / Lab Baru" di bawah.
                    </div>
                `;
                return;
            }
            
            container.innerHTML = activeVlanConfigs.map((v, index) => {
                return `
                    <div class="flex items-end gap-2 bg-smk-950/40 border border-smk-900/60 p-2.5 rounded-xl">
                        <div class="w-16 flex-shrink-0">
                            <label class="block text-[9px] font-bold text-smk-300 mb-1 uppercase tracking-wider">VLAN ID</label>
                            <input type="text" placeholder="e.g. 10" required 
                                value="${v.vlan_id || ''}" 
                                oninput="updateVlanField(${index}, 'vlan_id', this.value)"
                                class="w-full bg-slate-900 border border-smk-800/80 focus:border-cyan-500 rounded-lg px-2 py-1.5 text-xs font-mono text-slate-200 focus:outline-none transition">
                        </div>
                        <div class="flex-[3]">
                            <label class="block text-[9px] font-bold text-smk-300 mb-1 uppercase tracking-wider">Nama Lab</label>
                            <input type="text" placeholder="e.g. LAB 1" required 
                                value="${v.lab_name || ''}" 
                                oninput="updateVlanField(${index}, 'lab_name', this.value)"
                                class="w-full bg-slate-900 border border-smk-800/80 focus:border-cyan-500 rounded-lg px-2 py-1.5 text-xs text-slate-200 focus:outline-none transition">
                        </div>
                        <div class="flex-[4]">
                            <label class="block text-[9px] font-bold text-smk-300 mb-1 uppercase tracking-wider">Prefix IP</label>
                            <input type="text" placeholder="e.g. 192.168.10." 
                                value="${v.ip_prefix || ''}" 
                                oninput="updateVlanField(${index}, 'ip_prefix', this.value)"
                                class="w-full bg-slate-900 border border-smk-800/80 focus:border-cyan-500 rounded-lg px-2 py-1.5 text-xs font-mono text-slate-200 focus:outline-none transition">
                        </div>
                        <div class="flex-shrink-0">
                            <button type="button" onclick="deleteVlanRow(${index})" class="p-2 bg-red-950/40 border border-red-900/40 hover:border-red-500 text-red-400 hover:text-white rounded-lg transition duration-150" title="Hapus VLAN ini">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
            }).join("");
        }

        function updateVlanField(index, field, value) {
            activeVlanConfigs[index][field] = value;
        }

        function addNewVlanRow() {
            activeVlanConfigs.push({ vlan_id: '', lab_name: '', ip_prefix: '' });
            renderVlanRows();
        }

        function deleteVlanRow(index) {
            activeVlanConfigs.splice(index, 1);
            renderVlanRows();
        }

        // Format dates beautifully
        function formatRelativeTime(dateString) {
            if (!dateString) return "Never pinged";
            
            const t = dateString.split(/[- :]/);
            const date = new Date(t[0], t[1]-1, t[2], t[3], t[4], t[5]);
            const now = new Date();
            const diffMs = now - date;
            const diffSec = Math.floor(diffMs / 1000);
            
            if (diffSec < 5) return "Just now";
            if (diffSec < 60) return `${diffSec}s ago`;
            const diffMin = Math.floor(diffSec / 60);
            if (diffMin < 60) return `${diffMin}m ago`;
            const diffHr = Math.floor(diffMin / 60);
            if (diffHr < 24) return `${diffHr}h ago`;
            return date.toLocaleDateString('id-ID', { hour: '2-digit', minute: '2-digit' });
        }

        // Live digital clock
        function startClock() {
            const clock = document.getElementById("live-clock");
            setInterval(() => {
                const now = new Date();
                clock.textContent = now.toLocaleTimeString("id-ID", { hour12: false });
            }, 1000);
        }

        // Toast notifications
        function showToast(message, isSuccess = true) {
            const toast = document.getElementById("toast");
            const toastMsg = document.getElementById("toast-message");
            const toastIcon = document.getElementById("toast-icon");
            
            toastMsg.textContent = message;
            if (isSuccess) {
                toastIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" /></svg>`;
                toastIcon.className = "text-emerald-400 bg-emerald-950/80 border border-emerald-800/40 p-1.5 rounded-lg";
            } else {
                toastIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>`;
                toastIcon.className = "text-rose-400 bg-rose-950/80 border border-rose-800/40 p-1.5 rounded-lg";
            }
            
            toast.classList.remove("opacity-0", "translate-y-4", "pointer-events-none");
            
            setTimeout(() => {
                toast.classList.add("opacity-0", "translate-y-4", "pointer-events-none");
            }, 4000);
        }

        // Initialize Tab Navigation
        function renderTabs() {
            const tabList = document.getElementById("lab-tab-list");
            tabList.innerHTML = labs.map(lab => {
                const isActive = lab.id === currentLabId;
                return `
                    <button onclick="switchLab('${lab.id}')" id="tab-${lab.id}" 
                        class="px-4 py-2 rounded-lg text-xs font-bold transition border duration-200 ${
                            isActive 
                            ? 'bg-gradient-to-r from-smk-600 to-cyan-600 text-white border-cyan-500 glow-smk shadow-md' 
                            : 'bg-smk-950/40 text-smk-300 border-smk-900/60 hover:text-slate-200 hover:bg-smk-900/30'
                        }">
                        ${lab.name}
                    </button>
                `;
            }).join("");
        }

        // Switch Lab Tab
        function switchLab(vlanId) {
            currentLabId = vlanId;
            renderTabs();
            
            const lab = labs.find(l => l.id === vlanId);
            document.getElementById("active-lab-title").textContent = `${lab.name} (VLAN ${vlanId})`;
            
            renderPCGrid();
            fetchStatus(); // Trigger status and active exam refresh immediately
        }

        // Render Monitoring Grid
        function renderPCGrid() {
            const grid = document.getElementById("pc-grid");
            const searchVal = document.getElementById("search-pc").value.toLowerCase();
            const filterUnopened = document.getElementById("filter-belum-terbuka").checked;
            
            let filteredPcs = pcsData.filter(pc => pc.vlan_id === currentLabId);
            
            if (searchVal) {
                filteredPcs = filteredPcs.filter(pc => 
                    pc.nama_pc.toLowerCase().includes(searchVal) || 
                    pc.ip_address.includes(searchVal)
                );
            }
            
            if (filterUnopened) {
                filteredPcs = filteredPcs.filter(pc => pc.status_chrome === "Belum Terbuka");
            }
            
            document.getElementById("lab-counter").textContent = `Menampilkan ${filteredPcs.length} dari ${pcsData.filter(pc => pc.vlan_id === currentLabId).length} PC`;

            if (filteredPcs.length === 0) {
                grid.innerHTML = `
                    <div class="col-span-full py-8 text-center text-smk-400 text-sm">
                        Tidak ada PC yang cocok dengan filter / kosong.
                    </div>
                `;
                return;
            }

            grid.innerHTML = filteredPcs.map(pc => {
                const isReady = pc.status_chrome === "Sudah Terbuka";
                
                // Determine if Online (pinged within last 60 seconds)
                let isOnline = false;
                if (pc.last_ping) {
                    const t = pc.last_ping.split(/[- :]/);
                    const lastPingDate = new Date(t[0], t[1]-1, t[2], t[3], t[4], t[5]);
                    const diffMs = new Date() - lastPingDate;
                    isOnline = diffMs < 60 * 1000;
                }

                let cardClass = "";
                let indicatorCircle = "";
                let badgeText = "";
                const isLocked = pc.is_locked == 1;
                
                if (isLocked) {
                    cardClass = "border-red-500/80 bg-red-950/20 hover:border-red-400 hover:bg-red-950/30 text-red-200 glow-red animate-pulse";
                    indicatorCircle = "bg-red-500 shadow-md shadow-red-500/50 animate-ping";
                    badgeText = "TERKUNCI";
                } else if (isReady) {
                    cardClass = "border-emerald-500/35 bg-emerald-950/10 hover:border-emerald-400 hover:bg-emerald-950/20 text-emerald-300 glow-emerald";
                    indicatorCircle = "bg-emerald-450 shadow-md shadow-emerald-500/40 animate-pulse bg-emerald-400";
                    badgeText = "READY";
                } else {
                    cardClass = "border-smk-900 bg-smk-950/20 hover:border-smk-700 hover:bg-smk-900/20 text-slate-300";
                    indicatorCircle = isOnline ? "bg-cyan-400 shadow-md shadow-cyan-500/50 animate-ping" : "bg-smk-700";
                    badgeText = "WAITING";
                }

                const relativePing = formatRelativeTime(pc.last_ping);

                return `
                    <div class="relative border rounded-xl p-3.5 transition duration-300 flex flex-col justify-between h-28 group overflow-hidden ${cardClass}">
                        <div class="absolute -right-6 -bottom-6 w-12 h-12 bg-smk-400/5 rounded-full blur-xl group-hover:scale-150 transition-all duration-300"></div>
                        
                        <div class="flex justify-between items-start gap-1">
                            <span class="text-xs font-bold font-mono tracking-wider text-slate-400 group-hover:text-white transition duration-150">${pc.nama_pc}</span>
                            <span class="w-2.5 h-2.5 rounded-full ${indicatorCircle}"></span>
                        </div>
                        
                        <div class="space-y-0.5 mt-2">
                            <p class="text-[10px] font-mono text-smk-300 group-hover:text-slate-200 transition duration-150">${pc.ip_address}</p>
                            <p class="text-[9px] font-mono text-smk-400 group-hover:text-smk-200 transition duration-150 uppercase">${pc.mac_address || 'NO MAC'}</p>
                        </div>
                        
                        <div class="mt-2 pt-2 border-t border-smk-900/40 flex justify-between items-center text-[9px] text-slate-500">
                            <span class="font-mono truncate mr-1 text-slate-400" title="${pc.last_ping || 'Belum pernah PING'}">${relativePing}</span>
                            <div class="flex items-center gap-1">
                                <span class="px-1.5 py-0.5 rounded text-[8px] font-extrabold ${isLocked ? 'bg-red-950/80 text-red-400' : (isReady ? 'bg-emerald-950/80 text-emerald-400' : 'bg-slate-900 text-slate-450')}">${badgeText}</span>
                                <button onclick="openEditPcModal('${pc.ip_address}', '${pc.nama_pc}', '${pc.mac_address || ''}', '${pc.vlan_id}')" title="Edit PC" class="text-smk-400 hover:text-cyan-400 transition p-0.5 rounded bg-smk-950/85 border border-smk-850 hover:border-cyan-500/50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                ${pc.mac_address ? `
                                    <button onclick="wakeOnLanSingle('${pc.mac_address}')" title="Bangunkan PC" class="text-smk-400 hover:text-cyan-400 transition p-0.5 rounded bg-smk-950/85 border border-smk-850 hover:border-cyan-500/50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }).join("");
        }

        // Fetch Live PC statuses
        async function fetchStatus() {
            try {
                const response = await fetch(`/api/status-pc?vlan_id=${currentLabId}`);
                if (!response.ok) throw new Error("Gagal mengambil data status");
                const data = await response.json();
                
                pcsData = data.pcs;
                activeJadwal = data.active_jadwal;
                
                updateStats();
                updateActiveModeBadge();
                renderPCGrid();
                
                // If weekly grid container is visible, re-render it
                const weeklyContainer = document.getElementById("hist-weekly-container");
                if (weeklyContainer && !weeklyContainer.classList.contains("hidden")) {
                    renderWeeklyGrid();
                }
            } catch (err) {
                console.error("Poller Error:", err);
            }
        }

        // Update Statistics Cards
        function updateStats() {
            const total = pcsData.length;
            const terbuka = pcsData.filter(pc => pc.status_chrome === "Sudah Terbuka").length;
            const belum = total - terbuka;
            
            let online = 0;
            const now = new Date();
            pcsData.forEach(pc => {
                if (pc.last_ping) {
                    const t = pc.last_ping.split(/[- :]/);
                    const lastPingDate = new Date(t[0], t[1]-1, t[2], t[3], t[4], t[5]);
                    if ((now - lastPingDate) < 60 * 1000) online++;
                }
            });

            document.getElementById("stat-total-pc").textContent = total;
            document.getElementById("stat-terbuka-pc").textContent = terbuka;
            document.getElementById("stat-belum-pc").textContent = belum;
            document.getElementById("stat-online-pc").textContent = online;

            const percentReady = total > 0 ? Math.round((terbuka / total) * 100) : 0;
            document.getElementById("percent-ready").textContent = `${percentReady}%`;
            document.getElementById("bar-ready").style.width = `${percentReady}%`;
            
            const percentPending = total > 0 ? Math.round((belum / total) * 100) : 100;
            document.getElementById("percent-pending").textContent = `${percentPending}%`;
        }

        // Update Exam Mode Badges
        function updateActiveModeBadge() {
            const badge = document.getElementById("badge-mode");
            const activeControls = document.getElementById("active-event-controls");
            const activeName = document.getElementById("active-event-name");
            const activeUrl = document.getElementById("active-event-url");
            
            if (activeJadwal) {
                badge.innerHTML = `
                    <span class="w-2.5 h-2.5 rounded-full bg-cyan-400 animate-ping"></span>
                    <span class="text-cyan-400 font-bold">UJIAN AKTIF</span>
                `;
                badge.className = "flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-cyan-950/60 border border-cyan-800/40 text-cyan-400 pulse-glow-active shadow-lg shadow-cyan-950/40";
                
                activeControls.classList.remove("hidden");
                activeName.textContent = activeJadwal.nama_event;
                activeUrl.textContent = activeJadwal.url_target;
                document.getElementById("active-event-start").textContent = activeJadwal.waktu_mulai;
                document.getElementById("active-event-end").textContent = activeJadwal.waktu_selesai;
            } else {
                badge.innerHTML = `
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-500"></span>
                    <span>MODE NORMAL</span>
                `;
                badge.className = "flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-slate-800 border border-slate-700 text-slate-400";
                
                activeControls.classList.add("hidden");
            }
        }

        // Fetch history
        async function fetchHistory() {
            try {
                const response = await fetch("/api/status-pc"); // we fetch active jadwal list via server
                const data = await response.json();
                
                // Fetch direct jadwal history
                const histResponse = await fetch("/api/status-pc"); // fallback, we will query via DB in index but AJAX can load it too
                // We'll hit an endpoint to load history
                const responseHist = await fetch("/"); // trigger loading history
                
                // Let's get history via a dedicated ajax call if possible
                const getHist = await fetch("/index.php/api/status-pc"); 
                // Let's read it directly from window reload or a simple check. We'll poll history list using custom fetch.
                const historyRes = await fetch("/index.php/api/status-pc");
                // Wait, in our routes we mapped: `api/get-jadwal`? No, we didn't map get-jadwal but we can load history by calling status-pc and then we'll load event details. Wait! In routes we didn't define get-jadwal, but we can load the history table directly since it's rendered by PHP on initial load. Let's make an AJAX endpoint for it, or just use the initial PHP data. To make it dynamic without reloading, we can just load the initial array and append newly created ones, or let's create a small function.
                // Wait! Let's check: in CI4, does status-pc return history?
                // No, but we can update it or fetch the history list by creating a simple endpoint, or just do page reload on submit.
                // Page reload on submit is simple, but we can do a fetch from a dedicated route!
                // Actually, let's look at the routes: we defined `api/get-jadwal` in the FastAPI version, but in CI4 we didn't add it. Let's check routes:
                // Oh! We didn't define `api/get-jadwal` in CI4 Routes.php. Let's add it or just let history reload when setJadwal is called. Reloading is extremely simple!
            } catch (err) {
                console.error(err);
            }
        }

        // We can load history table dynamically by requesting `/` and parsing the table, or simply doing a window.location.reload() when changing settings, which is clean. Or better, we can reload only when a new exam is set.
        // Let's just do a window reload on submit/delete/reset, which is extremely robust!
        
        // Activate Exam Event (Submit Form)
        document.getElementById("form-set-jadwal").addEventListener("submit", async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch("/api/set-jadwal", {
                    method: "POST",
                    body: formData
                });
                
                const result = await response.json();
                if (response.ok) {
                    showToast(result.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.messages?.error || "Gagal mengaktifkan mode ujian", false);
                }
            } catch (err) {
                showToast("Kesalahan koneksi ke server", false);
            }
        });

        // Deactivate Exam Event
        async function deactivateUjian() {
            try {
                const response = await fetch("/api/deactivate-jadwal", {
                    method: "POST"
                });
                
                const result = await response.json();
                if (response.ok) {
                    showToast(result.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast("Gagal menonaktifkan mode ujian", false);
                }
            } catch (err) {
                showToast("Kesalahan koneksi ke server", false);
            }
        }

        // Delete Event History
        async function deleteHistory(id) {
            if (!confirm("Hapus catatan riwayat ujian ini?")) return;
            try {
                const response = await fetch(`/api/delete-jadwal/${id}`, {
                    method: "POST"
                });
                const result = await response.json();
                if (response.ok) {
                    showToast(result.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast("Gagal menghapus riwayat", false);
                }
            } catch (err) {
                showToast("Kesalahan koneksi ke server", false);
            }
        }

        // Reset All PC status
        async function resetAllStatus() {
            if (!confirm("Peringatan! Ini akan mereset status 'Sudah Terbuka' semua PC kembali ke 'Belum Terbuka'. Lanjutkan?")) return;
            try {
                const response = await fetch("/api/reset-status", {
                    method: "POST"
                });
                const result = await response.json();
                if (response.ok) {
                    showToast(result.message);
                    fetchStatus();
                } else {
                    showToast("Gagal mereset status PC", false);
                }
            } catch (err) {
                showToast("Kesalahan koneksi ke server", false);
            }
        }

        // Reset Lab status
        async function resetLabStatus() {
            const lab = labs.find(l => l.id === currentLabId);
            if (!confirm(`Reset semua status PC di ${lab.name} kembali ke 'Belum Terbuka'?`)) return;
            
            try {
                const response = await fetch(`/api/reset-status-lab/${currentLabId}`, {
                    method: "POST"
                });
                const result = await response.json();
                if (response.ok) {
                    showToast(result.message);
                    fetchStatus();
                } else {
                    showToast("Gagal mereset status lab", false);
                }
            } catch (err) {
                showToast("Kesalahan koneksi ke server", false);
            }
        }

        // Wake on LAN Lab
        async function wakeOnLanLab() {
            const lab = labs.find(l => l.id === currentLabId);
            if (!confirm(`Kirim sinyal Wake-on-LAN ke seluruh PC di ${lab.name} (VLAN ${currentLabId})?`)) return;
            
            const body = new FormData();
            body.append("target_type", "lab");
            body.append("vlan_id", currentLabId);
            
            try {
                const response = await fetch("/api/wake-on-lan", {
                    method: "POST",
                    body: body
                });
                const result = await response.json();
                if (response.ok) {
                    showToast(result.message);
                } else {
                    showToast(result.messages?.error || "Gagal mengirim WOL", false);
                }
            } catch (err) {
                showToast("Kesalahan koneksi ke server", false);
            }
        }

        // Wake on LAN Single PC
        async function wakeOnLanSingle(mac) {
            const body = new FormData();
            body.append("target_type", "single");
            body.append("mac_address", mac);
            
            try {
                const response = await fetch("/api/wake-on-lan", {
                    method: "POST",
                    body: body
                });
                const result = await response.json();
                if (response.ok) {
                    showToast(result.message);
                } else {
                    showToast(result.messages?.error || "Gagal mengirim WOL", false);
                }
            } catch (err) {
                showToast("Kesalahan koneksi ke server", false);
            }
        }

        // Custom WOL Modal controls
        function openWakeOnLanModal() {
            const modal = document.getElementById("wol-modal");
            modal.classList.remove("opacity-0", "pointer-events-none");
            modal.querySelector(".scale-95").classList.remove("scale-95");
        }

        // Close WOL Modal
        function closeWakeOnLanModal() {
            const modal = document.getElementById("wol-modal");
            modal.classList.add("opacity-0", "pointer-events-none");
            modal.querySelector(".glass-card").classList.add("scale-95");
        }

        function toggleWolModalFields() {
            const targetType = document.querySelector("input[name='target_type']:checked").value;
            const macField = document.getElementById("wol-field-mac");
            const vlanField = document.getElementById("wol-field-vlan");
            
            if (targetType === "single") {
                macField.classList.remove("hidden");
                vlanField.classList.add("hidden");
            } else {
                macField.classList.add("hidden");
                vlanField.classList.remove("hidden");
            }
        }

        // Submit Custom WOL Form
        document.getElementById("form-wol-custom").addEventListener("submit", async (e) => {
            e.preventDefault();
            const targetType = document.querySelector("input[name='target_type']:checked").value;
            const mac = document.getElementById("input-wol-mac").value;
            const vlan = document.getElementById("select-wol-vlan").value;
            
            const body = new FormData();
            body.append("target_type", targetType);
            if (targetType === "single") {
                if (!mac) {
                    showToast("MAC Address wajib diisi untuk PC tunggal", false);
                    return;
                }
                body.append("mac_address", mac);
            } else {
                body.append("vlan_id", vlan);
            }
            
            try {
                const response = await fetch("/api/wake-on-lan", {
                    method: "POST",
                    body: body
                });
                const result = await response.json();
                if (response.ok) {
                    showToast(result.message);
                    closeWakeOnLanModal();
                } else {
                    showToast(result.messages?.error || "Gagal memproses WOL", false);
                }
            } catch (err) {
                showToast("Kesalahan koneksi ke server", false);
            }
        });

        // Search & Filter listeners
        document.getElementById("search-pc").addEventListener("input", renderPCGrid);
        document.getElementById("filter-belum-terbuka").addEventListener("change", renderPCGrid);

        // Initial setup
        window.addEventListener("DOMContentLoaded", () => {
            startClock();
            renderTabs();
            
            // Seed initial data passed by PHP
            pcsData = <?= json_encode($pcs) ?>;
            activeJadwal = <?= json_encode($active_jadwal) ?>;
            
            // Render UI
            updateStats();
            updateActiveModeBadge();
            renderPCGrid();
            
            // Poll for status every 3 seconds
            setInterval(fetchStatus, 3000);
            
            // Render History Table
            renderHistoryTable();
        });

        function renderHistoryTable() {
            const history = <?= json_encode($all_jadwal) ?>;
            const tbody = document.getElementById("history-table-body");
            if (history.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="py-4 text-center text-smk-500 text-xs">Belum ada riwayat event.</td></tr>`;
                return;
            }
            
            tbody.innerHTML = history.map(h => {
                const statusStr = h.display_status;
                let badge = "";
                if (statusStr === 'AKTIF') {
                    badge = `<span class="bg-emerald-950 text-emerald-400 border border-emerald-900/40 px-2 py-0.5 rounded text-[10px] font-bold">AKTIF</span>`;
                } else if (statusStr === 'MENDATANG') {
                    badge = `<span class="bg-cyan-950 text-cyan-400 border border-cyan-900/40 px-2 py-0.5 rounded text-[10px] font-bold">MENDATANG</span>`;
                } else if (statusStr === 'NONAKTIF') {
                    badge = `<span class="bg-red-950 text-red-400 border border-red-900/40 px-2 py-0.5 rounded text-[10px] font-bold">NONAKTIF</span>`;
                } else {
                    badge = `<span class="bg-slate-900 text-slate-400 border border-slate-800 px-2 py-0.5 rounded text-[10px] font-medium">SELESAI</span>`;
                }
                
                let labTargetStr = "Semua Lab";
                if (h.target_lab !== 'ALL') {
                    const labIdVal = parseInt(h.target_lab);
                    if (!isNaN(labIdVal)) {
                        labTargetStr = "Lab " + (labIdVal / 10);
                    } else {
                        labTargetStr = h.target_lab;
                    }
                }

                return `
                    <tr class="hover:bg-smk-950/20 transition">
                        <td class="py-3 font-semibold text-slate-200">${h.nama_event}</td>
                        <td class="py-3 font-mono text-xs text-smk-300">${labTargetStr}</td>
                        <td class="py-3 font-mono text-cyan-400 text-xs truncate" style="max-width: 250px;">
                            <a href="${h.url_target}" target="_blank" class="hover:underline">${h.url_target}</a>
                        </td>
                        <td class="py-3 text-slate-350 text-xs font-mono">${h.waktu_mulai}</td>
                        <td class="py-3 text-slate-350 text-xs font-mono">${h.waktu_selesai}</td>
                        <td class="py-3">${badge}</td>
                        <td class="py-3 text-right">
                            <button onclick="deleteHistory(${h.id})" class="text-rose-450 hover:text-rose-350 px-2 py-1 text-xs border border-rose-950 hover:bg-rose-950/40 rounded transition">
                                Hapus
                            </button>
                        </td>
                    </tr>
                `;
            }).join("");
        }

        // Tab Navigation for History Card
        function switchHistoryTab(tabName) {
            const tableContainer = document.getElementById("hist-table-container");
            const weeklyContainer = document.getElementById("hist-weekly-container");
            const btnTable = document.getElementById("btn-tab-table");
            const btnWeekly = document.getElementById("btn-tab-weekly");
            
            if (tabName === "table") {
                tableContainer.classList.remove("hidden");
                weeklyContainer.classList.add("hidden");
                btnTable.className = "px-4 py-1.5 rounded-lg text-xs font-bold transition duration-200 bg-gradient-to-r from-smk-600 to-cyan-600 text-white shadow-md";
                btnWeekly.className = "px-4 py-1.5 rounded-lg text-xs font-bold transition duration-200 text-smk-300 hover:text-slate-200";
            } else {
                tableContainer.classList.add("hidden");
                weeklyContainer.classList.remove("hidden");
                btnTable.className = "px-4 py-1.5 rounded-lg text-xs font-bold transition duration-200 text-smk-300 hover:text-slate-200";
                btnWeekly.className = "px-4 py-1.5 rounded-lg text-xs font-bold transition duration-200 bg-gradient-to-r from-smk-600 to-cyan-600 text-white shadow-md";
                renderWeeklyGrid();
            }
        }

        // Render Weekly Timetable Grid
        function renderWeeklyGrid() {
            const history = <?= json_encode($all_jadwal) ?>;
            const tbody = document.getElementById("weekly-grid-body");
            
            const daysName = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
            const monthsName = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
            
            let html = "";
            const currentDate = new Date();
            
            for (let i = 0; i < 7; i++) {
                const targetDay = new Date();
                targetDay.setDate(currentDate.getDate() + i);
                
                const dayName = daysName[targetDay.getDay()];
                const dayNum = targetDay.getDate();
                const monthName = monthsName[targetDay.getMonth()];
                
                const yyyy = targetDay.getFullYear();
                const mm = String(targetDay.getMonth() + 1).padStart(2, '0');
                const dd = String(targetDay.getDate()).padStart(2, '0');
                const dateStr = `${yyyy}-${mm}-${dd}`;
                
                let dateDisplay = `<div class="font-bold text-slate-250 text-xs font-outfit">${dayName}</div>
                                   <div class="text-[10px] text-slate-400 font-mono">${dayNum} ${monthName}</div>`;
                
                if (i === 0) {
                    dateDisplay = `<div class="font-bold text-cyan-400 text-xs font-outfit flex items-center gap-1">
                                       <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-ping"></span> Hari Ini
                                   </div>
                                   <div class="text-[10px] text-cyan-300/80 font-mono">${dayNum} ${monthName}</div>`;
                }
                
                html += `<tr class="hover:bg-smk-950/10 transition border-b border-smk-900/30">
                            <td class="py-4 pr-4 align-middle font-sans">${dateDisplay}</td>`;
                
                for (let labNum = 1; labNum <= 7; labNum++) {
                    const vlanId = String(labNum * 10);
                    
                    const dayEvents = history.filter(h => {
                        const startDateOnly = h.waktu_mulai.split(" ")[0];
                        const isSameDate = startDateOnly === dateStr;
                        const isTargetLab = h.target_lab === vlanId || h.target_lab === 'ALL';
                        const isEventActive = h.status === 'Active';
                        return isSameDate && isTargetLab && isEventActive;
                    });
                    
                    let cellContent = "";
                    if (dayEvents.length > 0) {
                        cellContent = dayEvents.map(e => {
                            const startTime = e.waktu_mulai.split(" ")[1].substring(0, 5);
                            const endTime = e.waktu_selesai.split(" ")[1].substring(0, 5);
                            
                            const now = new Date();
                            const tStart = e.waktu_mulai.split(/[- :]/);
                            const tEnd = e.waktu_selesai.split(/[- :]/);
                            const startDate = new Date(tStart[0], tStart[1]-1, tStart[2], tStart[3], tStart[4], tStart[5]);
                            const endDate = new Date(tEnd[0], tEnd[1]-1, tEnd[2], tEnd[3], tEnd[4], tEnd[5]);
                            
                            const isRunning = now >= startDate && now <= endDate && e.status === 'Active';
                            const globalBadge = e.target_lab === 'ALL' ? `<span class="bg-amber-950 text-amber-300 text-[8px] font-extrabold px-1 rounded border border-amber-900/50">ALL</span>` : "";
                            
                            let cardStyle = "bg-smk-950/80 border border-smk-800/80 hover:border-cyan-500/50 text-slate-200";
                            if (isRunning) {
                                cardStyle = "bg-emerald-950/85 border border-emerald-500/40 text-emerald-300 glow-emerald pulse-glow-active";
                            }
                            
                            return `
                                <div class="p-2 rounded-lg text-[10px] font-sans shadow-md flex flex-col gap-1 transition ${cardStyle}" title="${e.nama_event}">
                                    <div class="font-bold truncate flex items-center justify-between gap-1">
                                        <span class="truncate">${e.nama_event}</span>
                                        ${globalBadge}
                                    </div>
                                    <div class="font-mono text-[9px] text-slate-400 flex items-center justify-between">
                                        <span>${startTime}-${endTime}</span>
                                        <a href="${e.url_target}" target="_blank" class="text-cyan-400 hover:underline">Link</a>
                                    </div>
                                </div>
                            `;
                        }).join('<div class="h-1.5"></div>');
                    } else {
                        cellContent = `
                            <button onclick="quickSchedule('${dateStr}', '${vlanId}')" 
                                    class="w-full h-10 border border-dashed border-smk-900/40 hover:border-cyan-500/40 hover:bg-smk-900/10 rounded-lg text-smk-400 hover:text-cyan-400 flex items-center justify-center transition group"
                                    title="Klik untuk menjadwalkan pada ${dayName}, Lab ${labNum}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform group-hover:scale-110 transition duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        `;
                    }
                    
                    html += `<td class="p-2 align-middle border-l border-smk-900/60 w-32">${cellContent}</td>`;
                }
                html += `</tr>`;
            }
            
            tbody.innerHTML = html;
        }

        // Quick scheduling filling helper
        function quickSchedule(dateStr, labId) {
            document.getElementById("form-set-jadwal").scrollIntoView({ behavior: 'smooth' });
            
            document.getElementById("input-target-lab").value = labId;
            
            document.getElementById("input-waktu-mulai").value = `${dateStr}T07:30`;
            document.getElementById("input-waktu-selesai").value = `${dateStr}T09:30`;
            
            setTimeout(() => {
                document.getElementById("input-nama-event").focus();
                showToast("Form jadwal telah terisi untuk tanggal " + dateStr + " (Lab " + (parseInt(labId)/10) + ")!");
            }, 500);
        }

        // Edit PC Modal handlers
        function openEditPcModal(ip, name, mac, vlanId) {
            document.getElementById("edit-pc-ip").value = ip;
            document.getElementById("edit-pc-ip-display").value = ip;
            document.getElementById("edit-pc-name").value = name;
            document.getElementById("edit-pc-mac").value = mac === 'null' || !mac ? '' : mac;
            document.getElementById("edit-pc-vlan").value = vlanId;
            
            const modal = document.getElementById("edit-pc-modal");
            modal.classList.remove("opacity-0", "pointer-events-none");
            modal.querySelector(".scale-95").classList.remove("scale-95");
        }
        
        function closeEditPcModal() {
            const modal = document.getElementById("edit-pc-modal");
            modal.classList.add("opacity-0", "pointer-events-none");
            modal.querySelector(".glass-card").classList.add("scale-95");
        }
        
        document.getElementById("form-edit-pc").addEventListener("submit", async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const response = await fetch("/api/update-pc", {
                    method: "POST",
                    body: formData
                });
                const result = await response.json();
                if (response.ok) {
                    showToast(result.message);
                    closeEditPcModal();
                    fetchStatus();
                } else {
                    showToast(result.messages?.error || "Gagal mengupdate PC", false);
                }
            } catch (err) {
                showToast("Kesalahan koneksi ke server", false);
            }
        });

        // Subnet Config Modal controls
        function openVlanConfigModal() {
            activeVlanConfigs = JSON.parse(JSON.stringify(initialVlans));
            renderVlanRows();
            const modal = document.getElementById("vlan-config-modal");
            modal.classList.remove("opacity-0", "pointer-events-none");
            modal.querySelector(".glass-card")?.classList.remove("scale-95");
        }
        
        function closeVlanConfigModal() {
            const modal = document.getElementById("vlan-config-modal");
            modal.classList.add("opacity-0", "pointer-events-none");
            modal.querySelector(".glass-card")?.classList.add("scale-95");
        }
        
        // Submit VLAN config form
        document.getElementById("form-vlan-config").addEventListener("submit", async (e) => {
            e.preventDefault();
            
            // Validate and clean up rows
            const cleanedVlans = [];
            for (let v of activeVlanConfigs) {
                const vid = (v.vlan_id || "").toString().trim();
                const name = (v.lab_name || "").toString().trim();
                const prefix = (v.ip_prefix || "").toString().trim();
                
                if (!vid || !name) {
                    showToast("Semua baris wajib memiliki VLAN ID dan Nama Lab", false);
                    return;
                }
                cleanedVlans.push({ vlan_id: vid, lab_name: name, ip_prefix: prefix });
            }
            
            // Check for duplicate VLAN IDs
            const vlanIds = cleanedVlans.map(v => v.vlan_id);
            const duplicates = vlanIds.filter((item, index) => vlanIds.indexOf(item) !== index);
            if (duplicates.length > 0) {
                showToast(`VLAN ID ganda terdeteksi: ${duplicates.join(", ")}`, false);
                return;
            }
            
            const formData = new FormData();
            formData.append("vlans", JSON.stringify(cleanedVlans));
            
            try {
                const response = await fetch("/api/update-vlan-config", {
                    method: "POST",
                    body: formData
                });
                const result = await response.json();
                if (response.ok) {
                    showToast(result.message);
                    closeVlanConfigModal();
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(result.messages?.error || "Gagal menyimpan konfigurasi VLAN", false);
                }
            } catch (err) {
                showToast("Kesalahan koneksi ke server", false);
            }
        });

        // Delete PC from database
        async function deletePc() {
            const ip = document.getElementById("edit-pc-ip").value;
            const name = document.getElementById("edit-pc-name").value;
            if (!confirm(`Apakah Anda yakin ingin menghapus PC '${name}' (${ip}) dari database?`)) return;
            
            const body = new FormData();
            body.append("ip_address", ip);
            
            try {
                const response = await fetch("/api/delete-pc", {
                    method: "POST",
                    body: body
                });
                const result = await response.json();
                if (response.ok) {
                    showToast(result.message);
                    closeEditPcModal();
                    fetchStatus();
                } else {
                    showToast(result.messages?.error || "Gagal menghapus PC", false);
                }
            } catch (err) {
                showToast("Kesalahan koneksi ke server", false);
            }
        }

        // Delete all PCs from database
        async function deleteAllPcs() {
            if (!confirm("Apakah Anda yakin ingin menghapus SELURUH PC dari database?\n\nTindakan ini akan membersihkan data lama. PC yang aktif akan otomatis terdaftar kembali ketika mereka mengirim ping berikutnya.")) return;
            
            try {
                const response = await fetch("/api/delete-all-pcs", {
                    method: "POST"
                });
                const result = await response.json();
                if (response.ok) {
                    showToast(result.message);
                    fetchStatus();
                } else {
                    showToast(result.messages?.error || "Gagal menghapus data PC", false);
                }
            } catch (err) {
                showToast("Kesalahan koneksi ke server", false);
            }
        }
    </script>
</body>
</html>

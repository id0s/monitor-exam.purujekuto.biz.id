<?php

namespace App\Controllers;

use App\Models\JadwalModel;
use App\Models\PcModel;
use CodeIgniter\API\ResponseTrait;

class Dashboard extends BaseController
{
    use ResponseTrait;

    protected $jadwalModel;
    protected $pcModel;

    public function __construct()
    {
        $this->jadwalModel = new JadwalModel();
        $this->pcModel = new PcModel();
        $this->setupDatabase();
    }

    /**
     * Dashboard view index.
     * Triggers database auto-setup if tables do not exist.
     */
    public function index()
    {
        $this->setupDatabase();

        $db = \Config\Database::connect();
        $vlanConfigs = $db->query("SELECT * FROM vlan_config ORDER BY CAST(vlan_id AS INTEGER) ASC, vlan_id ASC")->getResultArray();

        $defaultVlan = !empty($vlanConfigs) ? $vlanConfigs[0]['vlan_id'] : '10';
        $vlanId = $this->request->getGet('vlan_id') ?? $defaultVlan;

        $now = date('Y-m-d H:i:s');
        $activeJadwal = $this->jadwalModel->where('status', 'Active')
                                         ->where('waktu_mulai <=', $now)
                                         ->where('waktu_selesai >=', $now)
                                         ->groupStart()
                                             ->where('target_lab', $vlanId)
                                             ->orWhere('target_lab', 'ALL')
                                         ->groupEnd()
                                         ->orderBy("CASE WHEN target_lab = 'ALL' THEN 1 ELSE 0 END", "ASC")
                                         ->first();

        $allJadwal = $this->jadwalModel->orderBy('id', 'DESC')->findAll();
        foreach ($allJadwal as &$j) {
            if ($j['status'] === 'Inactive') {
                $j['display_status'] = 'NONAKTIF';
            } elseif ($now < $j['waktu_mulai']) {
                $j['display_status'] = 'MENDATANG';
            } elseif ($now > $j['waktu_selesai']) {
                $j['display_status'] = 'SELESAI';
            } else {
                $j['display_status'] = 'AKTIF';
            }
        }

        $pcs = $this->pcModel->orderBy('vlan_id', 'ASC')->orderBy('nama_pc', 'ASC')->findAll();

        return view('dashboard', [
            'active_jadwal' => $activeJadwal,
            'all_jadwal'    => $allJadwal,
            'pcs'           => $pcs,
            'vlan_configs'  => $vlanConfigs,
            'vlanId'        => $vlanId
        ]);
    }

    /**
     * Endpoint for Client PCs: GET /api/check-jadwal
     */
    public function checkJadwal()
    {
        $pcName = $this->request->getGet('pc_name');
        $ip     = $this->request->getGet('ip');
        $mac    = $this->request->getGet('mac');

        if (empty($pcName) || empty($ip)) {
            return $this->fail('Parameter pc_name dan ip wajib diisi.', 400);
        }

        try {
            $nowStr = date('Y-m-d H:i:s');
            
            // Check if PC exists in database first to get manual vlan override
            $pc = $this->pcModel->find($ip);
            $vlanId = null;
            $vlanUpdated = false;
            
            if ($pc) {
                // Verify if the PC's vlan_id is still a valid configured VLAN in database
                $db = \Config\Database::connect();
                $vlanExists = $db->query("SELECT 1 FROM vlan_config WHERE vlan_id = ?", [$pc['vlan_id']])->getRow();
                if ($vlanExists) {
                    $vlanId = $pc['vlan_id'];
                }
            }
            
            if (!$vlanId) {
                $vlanId = $this->autoDetectVlan($ip, $pcName);
                $vlanUpdated = true;
            }
            
            // Check active exam event based on time and target lab (priority: specific lab > global ALL)
            $activeEvent = $this->jadwalModel->where('status', 'Active')
                                             ->where('waktu_mulai <=', $nowStr)
                                             ->where('waktu_selesai >=', $nowStr)
                                             ->groupStart()
                                                 ->where('target_lab', $vlanId)
                                                 ->orWhere('target_lab', 'ALL')
                                             ->groupEnd()
                                             ->orderBy("CASE WHEN target_lab = 'ALL' THEN 1 ELSE 0 END", "ASC")
                                             ->first();
            
            // Determine status based on client feedback if present, otherwise fallback to activeEvent existence
            $chromeRunning = $this->request->getGet('chrome_running');
            $isLocked = 0;
            if ($chromeRunning !== null) {
                if ($chromeRunning == '2') {
                    $statusChrome = 'Belum Terbuka';
                    $isLocked = 1;
                } else {
                    $statusChrome = ($chromeRunning == '1') ? 'Sudah Terbuka' : 'Belum Terbuka';
                    $isLocked = 0;
                }
            } else {
                $statusChrome = $activeEvent ? 'Sudah Terbuka' : 'Belum Terbuka';
                $isLocked = 0;
            }

            if ($pc) {
                // Update existing PC status
                $data = [
                    'nama_pc'       => $pcName,
                    'status_chrome' => $statusChrome,
                    'is_locked'     => $isLocked,
                    'last_ping'     => $nowStr
                ];
                if ($vlanUpdated) {
                    $data['vlan_id'] = $vlanId;
                }
                if (!empty($mac)) {
                    $data['mac_address'] = $mac;
                }
                $this->pcModel->update($ip, $data);
            } else {
                // Auto registration of new PC
                $this->pcModel->insert([
                    'ip_address'    => $ip,
                    'mac_address'   => $mac ?? '',
                    'nama_pc'       => $pcName,
                    'vlan_id'       => $vlanId,
                    'status_chrome' => $statusChrome,
                    'is_locked'     => $isLocked,
                    'last_ping'     => $nowStr
                ]);
            }

            if ($activeEvent) {
                return $this->respond([
                    'status'       => 'success',
                    'event'        => $activeEvent['nama_event'],
                    'url'          => $activeEvent['url_target'],
                    'browser_mode' => $activeEvent['browser_mode'] ?? 'kiosk'
                ]);
            }

            return $this->respond([
                'status' => 'success',
                'event'  => 'NO_EVENT',
                'url'    => ''
            ]);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * API for frontend AJAX polling: GET /api/status-pc
     */
    public function statusPc()
    {
        try {
            $now = date('Y-m-d H:i:s');
            
            $db = \Config\Database::connect();
            $vlanConfigs = $db->query("SELECT * FROM vlan_config ORDER BY CAST(vlan_id AS INTEGER) ASC, vlan_id ASC")->getResultArray();
            $defaultVlan = !empty($vlanConfigs) ? $vlanConfigs[0]['vlan_id'] : '10';
            
            $vlanId = $this->request->getGet('vlan_id') ?? $defaultVlan;
            
            $pcs = $this->pcModel->orderBy('vlan_id', 'ASC')->orderBy('nama_pc', 'ASC')->findAll();
            
            $activeJadwal = $this->jadwalModel->where('status', 'Active')
                                             ->where('waktu_mulai <=', $now)
                                             ->where('waktu_selesai >=', $now)
                                             ->groupStart()
                                                 ->where('target_lab', $vlanId)
                                                 ->orWhere('target_lab', 'ALL')
                                             ->groupEnd()
                                             ->orderBy("CASE WHEN target_lab = 'ALL' THEN 1 ELSE 0 END", "ASC")
                                             ->first();

            return $this->respond([
                'pcs'           => $pcs,
                'active_jadwal' => $activeJadwal,
                'server_time'   => $now
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * API to start/activate an exam schedule: POST /api/set-jadwal
     */
    public function setJadwal()
    {
        $namaEvent    = $this->request->getPost('nama_event');
        $urlTarget    = $this->request->getPost('url_target');
        $waktuMulai   = $this->request->getPost('waktu_mulai');
        $waktuSelesai = $this->request->getPost('waktu_selesai');
        $targetLab    = $this->request->getPost('target_lab') ?? 'ALL';

        if (empty($namaEvent) || empty($urlTarget) || empty($waktuMulai) || empty($waktuSelesai)) {
            return $this->fail('Semua field (Nama, URL, Waktu Mulai, Waktu Selesai) wajib diisi.', 400);
        }

        // Convert HTML5 datetime-local format (YYYY-MM-DDTHH:MM) to SQL format (YYYY-MM-DD HH:MM:00)
        $waktuMulaiSql   = str_replace('T', ' ', $waktuMulai) . ':00';
        $waktuSelesaiSql = str_replace('T', ' ', $waktuSelesai) . ':00';

        if ($waktuSelesaiSql <= $waktuMulaiSql) {
            return $this->fail('Waktu Selesai harus setelah Waktu Mulai.', 400);
        }

        if (strpos($urlTarget, 'http://') !== 0 && strpos($urlTarget, 'https://') !== 0) {
            $urlTarget = 'https://' . $urlTarget;
        }

        try {
            $browserMode = $this->request->getPost('browser_mode') ?? 'kiosk';
            
            // Insert new Active/Scheduled schedule
            $this->jadwalModel->insert([
                'nama_event'    => $namaEvent,
                'url_target'    => $urlTarget,
                'waktu_mulai'   => $waktuMulaiSql,
                'waktu_selesai' => $waktuSelesaiSql,
                'target_lab'    => $targetLab,
                'browser_mode'  => $browserMode,
                'status'        => 'Active',
                'created_at'    => date('Y-m-d H:i:s')
            ]);

            return $this->respond([
                'status'  => 'success',
                'message' => "Jadwal ujian '{$namaEvent}' berhasil dijadwalkan."
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * API to turn off exam mode: POST /api/deactivate-jadwal
     */
    public function deactivateJadwal()
    {
        try {
            $this->jadwalModel->builder()->update(['status' => 'Inactive']);
            return $this->respond([
                'status'  => 'success',
                'message' => 'Mode ujian berhasil dinonaktifkan.'
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * API to delete an event from history: POST /api/delete-jadwal/{id}
     */
    public function deleteJadwal($id)
    {
        try {
            $this->jadwalModel->delete($id);
            return $this->respond([
                'status'  => 'success',
                'message' => 'Jadwal berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * API to reset all PCs: POST /api/reset-status
     */
    public function resetStatus()
    {
        try {
            $this->pcModel->builder()->update([
                'status_chrome' => 'Belum Terbuka',
                'last_ping'     => null
            ]);
            return $this->respond([
                'status'  => 'success',
                'message' => "Status semua PC berhasil di-reset ke 'Belum Terbuka'."
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * API to reset a specific lab/vlan: POST /api/reset-status-lab/{vlan_id}
     */
    public function resetStatusLab($vlanId)
    {
        try {
            $this->pcModel->where('vlan_id', $vlanId)->builder()->update([
                'status_chrome' => 'Belum Terbuka',
                'last_ping'     => null
            ]);
            return $this->respond([
                'status'  => 'success',
                'message' => "Status PC di Lab dengan VLAN {$vlanId} berhasil di-reset."
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * API to trigger Wake-on-LAN: POST /api/wake-on-lan
     */
    public function wakeOnLan()
    {
        $targetType = $this->request->getPost('target_type');
        $macAddress = $this->request->getPost('mac_address');
        $vlanId     = $this->request->getPost('vlan_id');

        try {
            if ($targetType === 'single') {
                if (empty($macAddress)) {
                    return $this->fail('MAC address wajib diisi.', 400);
                }
                
                $success = $this->sendMagicPacket($macAddress);
                if ($success) {
                    return $this->respond([
                        'status'  => 'success',
                        'message' => "Sinyal WOL berhasil dikirim ke {$macAddress}."
                    ]);
                }
                return $this->fail('Gagal mengirim sinyal WOL. Periksa format MAC.', 500);
            } elseif ($targetType === 'lab') {
                if (empty($vlanId)) {
                    return $this->fail('VLAN/Lab ID wajib diisi.', 400);
                }

                $pcs = $this->pcModel->where('vlan_id', $vlanId)->findAll();
                if (empty($pcs)) {
                    return $this->fail("Tidak ada PC terdaftar dengan VLAN {$vlanId}.", 404);
                }

                $sentCount = 0;
                foreach ($pcs as $pc) {
                    if (!empty($pc['mac_address'])) {
                        if ($this->sendMagicPacket($pc['mac_address'])) {
                            $sentCount++;
                        }
                    }
                }

                return $this->respond([
                    'status'  => 'success',
                    'message' => "Sinyal WOL dikirim ke {$sentCount} PC di Lab (VLAN {$vlanId})."
                ]);
            }

            return $this->fail('Target type tidak dikenal.', 400);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    # --- HELPERS ---

    /**
     * Sends WOL Magic Packet using sockets (UDP Broadcast)
     */
    private function sendMagicPacket($macAddress)
    {
        $cleanMac = str_replace([':', '-', '.'], '', $macAddress);
        if (strlen($cleanMac) !== 12) {
            return false;
        }

        $macBinary = pack('H*', $cleanMac);
        $magicPacket = str_repeat(chr(255), 6) . str_repeat($macBinary, 16);

        // Method 1: PHP Socket extension
        if (function_exists('socket_create')) {
            $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if ($socket) {
                @socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
                @socket_sendto($socket, $magicPacket, strlen($magicPacket), 0, '255.255.255.255', 9);
                @socket_close($socket);
                return true;
            }
        }

        // Method 2: Standard UDP stream socket fallback
        $fp = @fsockopen('udp://255.255.255.255', 9, $errno, $errstr, 2);
        if ($fp) {
            @fwrite($fp, $magicPacket);
            @fclose($fp);
            return true;
        }

        return false;
    }

    /**
     * Auto-detect VLAN from IP address segment
     */
    private function autoDetectVlan($ip, $pcName)
    {
        $db = \Config\Database::connect();
        
        // Match against database configured IP prefixes
        if ($db->tableExists('vlan_config')) {
            $configs = $db->query("SELECT vlan_id, ip_prefix FROM vlan_config")->getResultArray();
            foreach ($configs as $config) {
                $prefix = trim($config['ip_prefix']);
                if (empty($prefix)) continue;

                // Normalize: if prefix ends with ".0" (e.g. 192.168.10.0), strip the "0" to make "192.168.10."
                if (substr($prefix, -2) === '.0') {
                    $prefix = substr($prefix, 0, -1);
                }
                
                // Ensure there is a trailing dot if it's a 3-octet subnet (e.g. "192.168.10" -> "192.168.10.")
                if (preg_match('/^\d+\.\d+\.\d+$/', $prefix)) {
                    $prefix .= '.';
                }

                if (strpos($ip, $prefix) === 0) {
                    return $config['vlan_id'];
                }
            }
        }

        // Fallback 1: PC name standard regex
        if (!empty($pcName) && preg_match('/LAB\s*(\d+)/i', $pcName, $matches)) {
            return (string)((int)$matches[1] * 10);
        }

        // Fallback 2: IP third octet
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[2];
        }

        return '10';
    }

    /**
     * Automated database table creation and initial seeding of 245 PCs
     */
    private function setupDatabase()
    {
        $db = \Config\Database::connect();

        // Create Table: vlan_config
        $db->query("CREATE TABLE IF NOT EXISTS vlan_config (
            vlan_id TEXT PRIMARY KEY,
            lab_name TEXT NOT NULL,
            ip_prefix TEXT NOT NULL
        )");

        // Seed vlan_config if empty
        $countConfig = $db->query("SELECT COUNT(*) as cnt FROM vlan_config")->getRow()->cnt;
        if ($countConfig == 0) {
            for ($labNum = 1; $labNum <= 7; $labNum++) {
                $vlanId = (string)($labNum * 10);
                $labName = "LAB " . $labNum;
                $defaultPrefix = "192.168." . ($labNum * 10) . ".";
                $db->query("INSERT INTO vlan_config (vlan_id, lab_name, ip_prefix) VALUES (?, ?, ?)", [$vlanId, $labName, $defaultPrefix]);
            }
        }

        // Check and drop jadwal_ujian if missing columns (migration fallback)
        if ($db->tableExists('jadwal_ujian')) {
            $fields = $db->getFieldNames('jadwal_ujian');
            if ($fields && (!in_array('target_lab', $fields) || !in_array('waktu_mulai', $fields) || !in_array('waktu_selesai', $fields) || !in_array('browser_mode', $fields))) {
                $db->query("DROP TABLE IF EXISTS jadwal_ujian");
            }
        }

        // Create Table: jadwal_ujian
        $db->query("CREATE TABLE IF NOT EXISTS jadwal_ujian (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nama_event TEXT NOT NULL,
            url_target TEXT NOT NULL,
            waktu_mulai TEXT NOT NULL,
            waktu_selesai TEXT NOT NULL,
            target_lab TEXT NOT NULL,
            status TEXT NOT NULL CHECK(status IN ('Active', 'Inactive')),
            browser_mode TEXT DEFAULT 'kiosk',
            created_at TEXT NOT NULL
        )");

        // Check and drop status_pc if missing columns (migration fallback)
        if ($db->tableExists('status_pc')) {
            $fields = $db->getFieldNames('status_pc');
            if ($fields && (!in_array('is_locked', $fields) || !in_array('vlan_id', $fields) || !in_array('mac_address', $fields) || !in_array('status_chrome', $fields))) {
                $db->query("DROP TABLE IF EXISTS status_pc");
            }
        }

        // Create Table: status_pc
        $db->query("CREATE TABLE IF NOT EXISTS status_pc (
            ip_address TEXT PRIMARY KEY,
            mac_address TEXT,
            nama_pc TEXT NOT NULL,
            vlan_id TEXT NOT NULL,
            status_chrome TEXT NOT NULL CHECK(status_chrome IN ('Belum Terbuka', 'Sudah Terbuka')),
            is_locked INTEGER DEFAULT 0,
            last_ping TEXT
        )");

        // Migrate column if table already exists
        if ($db->tableExists('status_pc')) {
            $fields = $db->getFieldNames('status_pc');
            if ($fields && !in_array('is_locked', $fields)) {
                $db->query("ALTER TABLE status_pc ADD COLUMN is_locked INTEGER DEFAULT 0");
            }
        }

        // Seed if empty
        $count = $db->query("SELECT COUNT(*) as cnt FROM status_pc")->getRow()->cnt;
        if ($count == 0) {
            $db->transStart();
            for ($labNum = 1; $labNum <= 7; $labNum++) {
                $vlanId = (string)($labNum * 10);
                $labName = "LAB" . $labNum;
                for ($pcNum = 1; $pcNum <= 35; $pcNum++) {
                    $pcName = sprintf("%s-PC%02d", $labName, $pcNum);
                    $ip = sprintf("192.168.%d.%d", $labNum * 10, 100 + $pcNum);
                    $mac = sprintf("00:1A:2B:3C:%02d:%02d", $labNum * 10, $pcNum);

                    $db->query("INSERT INTO status_pc (ip_address, mac_address, nama_pc, vlan_id, status_chrome, last_ping) 
                                VALUES (?, ?, ?, ?, 'Belum Terbuka', NULL)", [$ip, $mac, $pcName, $vlanId]);
                }
            }
            $db->transComplete();
        }
    }

    /**
     * API to update PC details manually: POST /api/update-pc
     */
    public function updatePc()
    {
        $ip     = $this->request->getPost('ip_address');
        $namaPc = $this->request->getPost('nama_pc');
        $mac    = $this->request->getPost('mac_address');
        $vlanId = $this->request->getPost('vlan_id');

        if (empty($ip) || empty($namaPc) || empty($vlanId)) {
            return $this->fail('IP Address, Nama PC, dan Lab wajib diisi.', 400);
        }

        try {
            $this->pcModel->update($ip, [
                'nama_pc'     => $namaPc,
                'mac_address' => $mac ?? '',
                'vlan_id'     => $vlanId
            ]);

            return $this->respond([
                'status'  => 'success',
                'message' => "PC '{$namaPc}' berhasil diperbarui."
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * API to update VLAN configuration dynamically: POST /api/update-vlan-config
     */
    public function updateVlanConfig()
    {
        $db = \Config\Database::connect();
        $vlansJson = $this->request->getPost('vlans');

        if (empty($vlansJson)) {
            return $this->fail('Data VLAN wajib diisi.', 400);
        }

        $vlans = json_decode($vlansJson, true);
        if (!is_array($vlans)) {
            return $this->fail('Format data VLAN tidak valid.', 400);
        }

        // Validate items
        foreach ($vlans as $v) {
            if (empty($v['vlan_id']) || empty($v['lab_name'])) {
                return $this->fail('Setiap VLAN wajib memiliki ID dan Nama Lab.', 400);
            }
        }

        try {
            $db->transStart();
            
            // Delete all existing vlan config first
            $db->query("DELETE FROM vlan_config");

            // Insert new configurations
            foreach ($vlans as $v) {
                $vlanId   = trim((string)$v['vlan_id']);
                $labName  = trim((string)$v['lab_name']);
                $ipPrefix = trim((string)($v['ip_prefix'] ?? ''));

                $db->query("INSERT INTO vlan_config (vlan_id, lab_name, ip_prefix) VALUES (?, ?, ?)", [$vlanId, $labName, $ipPrefix]);
            }

            $db->transComplete();
            
            return $this->respond([
                'status'  => 'success',
                'message' => 'Konfigurasi VLAN & Subnet Lab berhasil diperbarui.'
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * API to delete PC from database: POST /api/delete-pc
     */
    public function deletePc()
    {
        $ip = $this->request->getPost('ip_address');

        if (empty($ip)) {
            return $this->fail('IP Address wajib diisi.', 400);
        }

        try {
            $this->pcModel->delete($ip);
            return $this->respond([
                'status'  => 'success',
                'message' => 'PC berhasil dihapus dari database.'
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * API to delete all PCs from database: POST /api/delete-all-pcs
     */
    public function deleteAllPcs()
    {
        try {
            $this->pcModel->truncate();
            return $this->respond([
                'status'  => 'success',
                'message' => 'Semua data PC berhasil dihapus. PC yang aktif akan terdaftar kembali secara otomatis saat mengirim ping berikutnya.'
            ]);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }
}

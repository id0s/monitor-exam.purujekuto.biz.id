import sqlite3
import datetime
import os

DB_FILE = os.path.join(os.path.dirname(__file__), "database.db")

def get_db_connection():
    conn = sqlite3.connect(DB_FILE)
    conn.row_factory = sqlite3.Row
    return conn

def init_db():
    conn = get_db_connection()
    cursor = conn.cursor()
    
    # Create Table: jadwal_ujian
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS jadwal_ujian (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_event TEXT NOT NULL,
        url_target TEXT NOT NULL,
        status TEXT NOT NULL CHECK(status IN ('Active', 'Inactive')),
        created_at TEXT NOT NULL
    )
    """)
    
    # Create Table: status_pc
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS status_pc (
        ip_address TEXT PRIMARY KEY,
        mac_address TEXT,
        nama_pc TEXT NOT NULL,
        vlan_id TEXT NOT NULL,
        status_chrome TEXT NOT NULL CHECK(status_chrome IN ('Belum Terbuka', 'Sudah Terbuka')),
        is_locked INTEGER DEFAULT 0,
        last_ping TEXT
    )
    """)
    
    # Check if is_locked column exists (migration fallback for existing databases)
    cursor.execute("PRAGMA table_info(status_pc)")
    columns = [row[1] for row in cursor.fetchall()]
    if "is_locked" not in columns:
        cursor.execute("ALTER TABLE status_pc ADD COLUMN is_locked INTEGER DEFAULT 0")
        
    conn.commit()
    conn.close()

# --- JADWAL UJIAN OPERATIONS ---

def get_active_jadwal():
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM jadwal_ujian WHERE status = 'Active' LIMIT 1")
    row = cursor.fetchone()
    conn.close()
    if row:
        return dict(row)
    return None

def get_all_jadwal():
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM jadwal_ujian ORDER BY id DESC")
    rows = cursor.fetchall()
    conn.close()
    return [dict(r) for r in rows]

def set_active_jadwal(nama_event: str, url_target: str):
    conn = get_db_connection()
    cursor = conn.cursor()
    
    # Set all existing schedules to Inactive
    cursor.execute("UPDATE jadwal_ujian SET status = 'Inactive'")
    
    # Insert new active schedule
    created_at = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    cursor.execute(
        "INSERT INTO jadwal_ujian (nama_event, url_target, status, created_at) VALUES (?, ?, 'Active', ?)",
        (nama_event, url_target, created_at)
    )
    
    conn.commit()
    conn.close()

def deactivate_all_jadwal():
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("UPDATE jadwal_ujian SET status = 'Inactive'")
    conn.commit()
    conn.close()

def delete_jadwal(jadwal_id: int):
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("DELETE FROM jadwal_ujian WHERE id = ?", (jadwal_id,))
    conn.commit()
    conn.close()


# --- PC STATUS OPERATIONS ---

def get_all_pc_status():
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM status_pc ORDER BY vlan_id ASC, nama_pc ASC")
    rows = cursor.fetchall()
    conn.close()
    return [dict(r) for r in rows]

def register_or_update_pc(ip_address: str, mac_address: str, nama_pc: str, vlan_id: str, status_chrome: str = "Belum Terbuka"):
    conn = get_db_connection()
    cursor = conn.cursor()
    
    # UPSERT pattern for SQLite
    cursor.execute("""
    INSERT INTO status_pc (ip_address, mac_address, nama_pc, vlan_id, status_chrome, last_ping)
    VALUES (?, ?, ?, ?, ?, NULL)
    ON CONFLICT(ip_address) DO UPDATE SET
        mac_address = COALESCE(excluded.mac_address, status_pc.mac_address),
        nama_pc = excluded.nama_pc,
        vlan_id = excluded.vlan_id
    """, (ip_address, mac_address, nama_pc, vlan_id, status_chrome))
    
    conn.commit()
    conn.close()

def auto_detect_vlan(ip_address: str, pc_name: str) -> str:
    # Logic to infer VLAN ID
    # e.g., if IP is 192.168.10.x, VLAN is 10 (Lab 1)
    # or if PC name has LAB3, VLAN is 30 (Lab 3)
    try:
        if pc_name:
            import re
            match = re.search(r'LAB\s*(\d+)', pc_name, re.IGNORECASE)
            if match:
                return str(match.group(1))
        
        parts = ip_address.split('.')
        if len(parts) == 4:
            third_octet = parts[2]
            return third_octet
    except Exception:
        pass
    return "1"  # Default VLAN

def update_pc_status_by_ping(ip_address: str, nama_pc: str, mac_address: str = None, chrome_running: str = None) -> tuple[str, str]:
    """
    Called when PC pings the controller.
    Returns: (event_name, target_url) or ('NO_EVENT', '')
    """
    conn = get_db_connection()
    cursor = conn.cursor()
    
    # 1. Check if active schedule exists
    cursor.execute("SELECT * FROM jadwal_ujian WHERE status = 'Active' LIMIT 1")
    active_event = cursor.fetchone()
    
    now_str = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    
    # Process chrome_running status
    is_locked = 0
    if chrome_running is not None:
        if chrome_running == '2':
            status_chrome = "Belum Terbuka"
            is_locked = 1
        elif chrome_running == '1':
            status_chrome = "Sudah Terbuka"
            is_locked = 0
        else:
            status_chrome = "Belum Terbuka"
            is_locked = 0
    else:
        status_chrome = "Sudah Terbuka" if active_event else "Belum Terbuka"
        is_locked = 0
    
    # 2. Check if PC already exists in DB
    cursor.execute("SELECT * FROM status_pc WHERE ip_address = ?", (ip_address,))
    pc = cursor.fetchone()
    
    if pc:
        # Update PC status and last_ping
        cursor.execute("""
        UPDATE status_pc 
        SET nama_pc = ?, 
            status_chrome = ?, 
            is_locked = ?,
            last_ping = ?,
            mac_address = COALESCE(?, mac_address)
        WHERE ip_address = ?
        """, (nama_pc, status_chrome, is_locked, now_str, mac_address, ip_address))
    else:
        # Auto-registration
        vlan_id = auto_detect_vlan(ip_address, nama_pc)
        # Default MAC if not provided
        mac = mac_address if mac_address else ""
        cursor.execute("""
        INSERT INTO status_pc (ip_address, mac_address, nama_pc, vlan_id, status_chrome, is_locked, last_ping)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        """, (ip_address, mac, nama_pc, vlan_id, status_chrome, is_locked, now_str))
        
    conn.commit()
    conn.close()
    
    if active_event:
        return active_event["nama_event"], active_event["url_target"]
    return "NO_EVENT", ""

def reset_all_pc_status():
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("UPDATE status_pc SET status_chrome = 'Belum Terbuka', last_ping = NULL")
    conn.commit()
    conn.close()

def reset_pc_status_by_lab(vlan_id: str):
    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("UPDATE status_pc SET status_chrome = 'Belum Terbuka', last_ping = NULL WHERE vlan_id = ?", (vlan_id,))
    conn.commit()
    conn.close()

# Initialize DB on import if it doesn't exist
if not os.path.exists(DB_FILE):
    init_db()

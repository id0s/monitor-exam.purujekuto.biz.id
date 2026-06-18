import urllib.request
import urllib.parse
import json
import random
import time
import sys

BASE_URL = "http://127.0.0.1:8000"

def ping_pc(pc_name, ip, mac=None):
    params = {
        "pc_name": pc_name,
        "ip": ip
    }
    if mac:
        params["mac"] = mac
        
    url = f"{BASE_URL}/api/check-jadwal?{urllib.parse.urlencode(params)}"
    try:
        req = urllib.request.Request(url, method="GET")
        with urllib.request.urlopen(req) as response:
            res_data = json.loads(response.read().decode('utf-8'))
            return True, res_data
    except Exception as e:
        return False, str(e)

def run_simulation(mode="random", count=10):
    print(f"Starting API simulation. Mode: {mode}, Count: {count}")
    print(f"Targeting: {BASE_URL}")
    print("-" * 50)
    
    # Pre-calculated details matching the seeder
    if mode == "all":
        # Simulating all 245 PCs
        for lab_num in range(1, 8):
            print(f"Simulating ping checks for LAB {lab_num}...")
            for pc_num in range(1, 36):
                pc_name = f"LAB{lab_num}-PC{pc_num:02d}"
                ip = f"192.168.{lab_num*10}.{100+pc_num}"
                mac = f"00:1A:2B:3C:{lab_num*10:02d}:{pc_num:02d}"
                
                success, data = ping_pc(pc_name, ip, mac)
                if success:
                    # e.g., if there's an exam, it will return the URL
                    status_text = "Chrome Opened" if data.get("event") != "NO_EVENT" else "Normal Ping"
                    print(f"  [+] {pc_name} ({ip}) - {status_text} | Event: {data.get('event')}")
                else:
                    print(f"  [-] Failed {pc_name}: {data}")
                time.sleep(0.02) # Quick pause to let server handle requests
            print("-" * 50)
            
    elif mode == "random":
        # Simulating random set of PCs
        for _ in range(count):
            lab_num = random.randint(1, 7)
            pc_num = random.randint(1, 35)
            pc_name = f"LAB{lab_num}-PC{pc_num:02d}"
            ip = f"192.168.{lab_num*10}.{100+pc_num}"
            mac = f"00:1A:2B:3C:{lab_num*10:02d}:{pc_num:02d}"
            
            success, data = ping_pc(pc_name, ip, mac)
            if success:
                status_text = "Chrome Opened" if data.get("event") != "NO_EVENT" else "Normal Ping"
                print(f"[+] Pinged {pc_name} | Response: {data.get('event')} | URL: {data.get('url', '-')}")
            else:
                print(f"[-] Failed {pc_name}: {data}")
            time.sleep(0.3)
            
    elif mode == "new":
        # Simulating auto-registration of a brand new PC not in the 245 list
        pc_name = f"LAB-NEW-PC99"
        ip = "192.168.99.99"
        mac = "00:AA:BB:CC:DD:EE"
        print(f"Simulating registration of a new/unknown PC: {pc_name} ({ip})...")
        success, data = ping_pc(pc_name, ip, mac)
        if success:
            print(f"[+] Success! Response: {data}")
        else:
            print(f"[-] Failed: {data}")

if __name__ == "__main__":
    mode = "random"
    count = 10
    
    if len(sys.argv) > 1:
        mode = sys.argv[1]
    if len(sys.argv) > 2:
        count = int(sys.argv[2])
        
    run_simulation(mode, count)

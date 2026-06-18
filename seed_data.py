from database import register_or_update_pc, init_db
import sqlite3
import os

def seed():
    print("Initializing Database...")
    init_db()
    
    print("Seeding PC status table...")
    # Generate labs: 1 to 7
    total_inserted = 0
    for lab_num in range(1, 8):
        vlan_id = str(lab_num * 10)  # VLAN 10, 20, 30, ..., 70
        lab_name = f"LAB{lab_num}"
        
        # 35 PCs per lab
        for pc_num in range(1, 36):
            pc_name = f"{lab_name}-PC{pc_num:02d}"
            # IP allocation: 192.168.(lab_num*10).(100 + pc_num)
            # E.g. LAB1-PC01: 192.168.10.101
            ip_address = f"192.168.{lab_num * 10}.{100 + pc_num}"
            
            # MAC Address allocation: 00:1A:2B:3C:vlan_hex:pc_hex
            mac_address = f"00:1A:2B:3C:{lab_num * 10:02d}:{pc_num:02d}"
            
            register_or_update_pc(
                ip_address=ip_address,
                mac_address=mac_address,
                nama_pc=pc_name,
                vlan_id=vlan_id,
                status_chrome="Belum Terbuka"
            )
            total_inserted += 1
            
    print(f"Successfully seeded {total_inserted} PCs into status_pc table.")

if __name__ == "__main__":
    seed()

from fastapi import FastAPI, Request, Query, Form, HTTPException, BackgroundTasks
from fastapi.responses import HTMLResponse, JSONResponse, RedirectResponse
from fastapi.staticfiles import StaticFiles
from fastapi.templating import Jinja2Templates
import os
import socket
import struct
import logging

import database

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("exam_controller")

app = FastAPI(title="Exam Controller Dashboard")

# Templates directory setup
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
templates = Jinja2Templates(directory=os.path.join(BASE_DIR, "templates"))

# Initialize DB on start
database.init_db()

# --- Wake-on-LAN Helper ---
def send_magic_packet(mac_address: str):
    """
    Sends WOL magic packet to the broadcast address for the given MAC.
    """
    try:
        clean_mac = mac_address.replace(":", "").replace("-", "").replace(".", "")
        if len(clean_mac) != 12:
            logger.warning(f"Skipping invalid MAC format: {mac_address}")
            return False
        
        # Pack hex characters
        mac_bytes = struct.pack('!BBBBBB', *[int(clean_mac[i:i+2], 16) for i in range(0, 12, 2)])
        magic_packet = b'\xff' * 6 + mac_bytes * 16
        
        # Send UDP broadcast
        with socket.socket(socket.AF_INET, socket.SOCK_DGRAM) as sock:
            sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
            # Send to generic broadcast port 9
            sock.sendto(magic_packet, ('255.255.255.255', 9))
            logger.info(f"Sent WOL Magic Packet to {mac_address}")
        return True
    except Exception as e:
        logger.error(f"Error sending WOL to {mac_address}: {e}")
        return False

# --- Web UI ---
@app.get("/", response_class=HTMLResponse)
async def index(request: Request):
    user_agent = request.headers.get("user-agent", "").lower()
    is_mobile = "android" in user_agent or "iphone" in user_agent or "ipad" in user_agent
    is_official_apk = "smk2pekalonganexambrowser" in user_agent

    if is_mobile:
        if is_official_apk:
            active_jadwal = database.get_active_jadwal()
            if active_jadwal:
                return RedirectResponse(url=active_jadwal["url_target"])
            else:
                return HTMLResponse("<html><body><h2 style='text-align:center; margin-top:20%; color:#0f172a; font-family:sans-serif;'>Belum ada ujian aktif. Silakan hubungi pengawas.</h2></body></html>")
        else:
            return HTMLResponse("<html><body><h2 style='text-align:center; margin-top:20%; color:#ef4444; font-family:sans-serif;'>Akses Ditolak! Harap gunakan Aplikasi APK Resmi Sekolah untuk mengerjakan ujian.</h2></body></html>")

    active_jadwal = database.get_active_jadwal()
    all_jadwal = database.get_all_jadwal()
    pcs = database.get_all_pc_status()
    
    return templates.TemplateResponse(
        request=request,
        name="index.html",
        context={
            "active_jadwal": active_jadwal,
            "all_jadwal": all_jadwal,
            "pcs": pcs
        }
    )

# --- Client PC APIs ---
@app.get("/api/check-jadwal")
async def check_jadwal(
    pc_name: str = Query(..., description="Nama PC Lab"),
    ip: str = Query(..., description="IP Address PC Lab"),
    mac: str = Query(None, description="MAC Address PC Lab (optional)"),
    chrome_running: str = Query(None, description="Status Chrome/Exam (optional)")
):
    """
    Endpoint for client PCs/devices.
    Updates the status_chrome/is_locked in DB and returns the target URL or 'NO_EVENT'.
    """
    try:
        event_name, target_url = database.update_pc_status_by_ping(
            ip_address=ip, 
            nama_pc=pc_name, 
            mac_address=mac,
            chrome_running=chrome_running
        )
        return {
            "status": "success",
            "event": event_name,
            "url": target_url
        }
    except Exception as e:
        logger.error(f"Error checking exam status for {pc_name} ({ip}): {e}")
        raise HTTPException(status_code=500, detail=str(e))

# --- Dashboard API Controls ---

@app.get("/api/status-pc")
async def get_status_pc():
    """
    Returns the live status of all PCs and the active exam schedule.
    Used for frontend AJAX polling.
    """
    try:
        import datetime
        pcs = database.get_all_pc_status()
        active_jadwal = database.get_active_jadwal()
        server_time = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        return {
            "pcs": pcs,
            "active_jadwal": active_jadwal,
            "server_time": server_time
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/set-jadwal")
async def set_jadwal(
    nama_event: str = Form(...),
    url_target: str = Form(...)
):
    """
    Activates a new exam event and updates the target URL.
    """
    try:
        if not url_target.startswith(("http://", "https://")):
            url_target = "https://" + url_target
        database.set_active_jadwal(nama_event, url_target)
        return JSONResponse(content={"status": "success", "message": f"Mode ujian '{nama_event}' berhasil diaktifkan."})
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/deactivate-jadwal")
async def deactivate_jadwal():
    """
    Turns off the exam mode (sets all schedules to Inactive).
    """
    try:
        database.deactivate_all_jadwal()
        return JSONResponse(content={"status": "success", "message": "Mode ujian berhasil dinonaktifkan."})
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/delete-jadwal/{jadwal_id}")
async def delete_jadwal(jadwal_id: int):
    """
    Deletes a specific schedule history record.
    """
    try:
        database.delete_jadwal(jadwal_id)
        return JSONResponse(content={"status": "success", "message": "Jadwal berhasil dihapus."})
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/api/get-jadwal")
async def get_jadwal_history():
    """
    Returns history of all exam events.
    """
    return database.get_all_jadwal()

@app.post("/api/reset-status")
async def reset_status():
    """
    Resets the state of all PCs to 'Belum Terbuka'.
    """
    try:
        database.reset_all_pc_status()
        return JSONResponse(content={"status": "success", "message": "Status semua PC berhasil di-reset ke 'Belum Terbuka'."})
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/reset-status-lab/{vlan_id}")
async def reset_status_lab(vlan_id: str):
    """
    Resets the state of all PCs in a specific VLAN/Lab.
    """
    try:
        database.reset_pc_status_by_lab(vlan_id)
        return JSONResponse(content={"status": "success", "message": f"Status PC di Lab dengan VLAN {vlan_id} berhasil di-reset."})
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/wake-on-lan")
async def wake_on_lan(
    target_type: str = Form(...),  # 'single' or 'lab'
    vlan_id: str = Form(None),
    mac_address: str = Form(None),
    background_tasks: BackgroundTasks = BackgroundTasks()
):
    """
    Triggers WOL. If target_type is 'single', sends to a single MAC address.
    If 'lab', fetches all PCs in the lab (VLAN) and broadcasts to all MAC addresses.
    """
    try:
        if target_type == "single":
            if not mac_address:
                return JSONResponse(content={"status": "error", "message": "MAC address wajib diisi."}, status_code=400)
            
            # Send Magic Packet
            background_tasks.add_task(send_magic_packet, mac_address)
            return JSONResponse(content={"status": "success", "message": f"Sinyal WOL dikirim ke {mac_address}."})
            
        elif target_type == "lab":
            if not vlan_id:
                return JSONResponse(content={"status": "error", "message": "VLAN/Lab ID wajib diisi."}, status_code=400)
            
            # Fetch all PCs in this lab
            all_pcs = database.get_all_pc_status()
            lab_pcs = [pc for pc in all_pcs if pc["vlan_id"] == vlan_id]
            
            if not lab_pcs:
                return JSONResponse(content={"status": "error", "message": f"Tidak ada PC terdaftar dengan VLAN {vlan_id}."}, status_code=404)
            
            # Queue WOL packets in the background
            sent_count = 0
            for pc in lab_pcs:
                mac = pc["mac_address"]
                if mac:
                    background_tasks.add_task(send_magic_packet, mac)
                    sent_count += 1
            
            return JSONResponse(content={"status": "success", "message": f"Sinyal WOL sedang dikirim ke {sent_count} PC di Lab (VLAN {vlan_id})."})
        else:
            return JSONResponse(content={"status": "error", "message": "Target type tidak dikenal."}, status_code=400)
            
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

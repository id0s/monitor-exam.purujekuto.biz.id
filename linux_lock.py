import tkinter as tk
from tkinter import messagebox
import urllib.request
import urllib.parse
import json
import sys
import os

# Arguments: server_url pc_name ip mac
server_url = sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8000"
pc_name = sys.argv[2] if len(sys.argv) > 2 else "Linux-PC"
ip_addr = sys.argv[3] if len(sys.argv) > 3 else "127.0.0.1"
mac_addr = sys.argv[4] if len(sys.argv) > 4 else ""

class LockScreen:
    def __init__(self, root):
        self.root = root
        self.root.title("UJIAN TERKUNCI")
        self.root.attributes("-fullscreen", True)
        self.root.attributes("-topmost", True)
        self.root.configure(bg="#0f172a") # Slate 900
        
        # Mencegah penutupan lewat window manager
        self.root.protocol("WM_DELETE_WINDOW", self.on_closing)
        
        # Frame Kontainer Tengah
        self.frame = tk.Frame(self.root, bg="#1e293b", bd=0)
        self.frame.place(relx=0.5, rely=0.5, anchor="center", width=600, height=400)
        
        # Judul
        self.title_label = tk.Label(
            self.frame, 
            text="SMKN 2 PEKALONGAN\nKONTROLER UJIAN LAB", 
            font=("Arial", 16, "bold"), 
            fg="#06b6d4", # Cyan 500
            bg="#1e293b"
        )
        self.title_label.pack(pady=(30, 20))
        
        # Peringatan
        self.warn_label = tk.Label(
            self.frame,
            text="PERINGATAN: TERDETEKSI UPAYA KELUAR DARI EXAM BROWSER!\n\nLayar ini telah dikunci untuk keamanan. Silakan hubungi Pengawas.",
            font=("Arial", 10, "bold"),
            fg="#ef4444", # Red 500
            bg="#1e293b",
            wraplength=500
        )
        self.warn_label.pack(pady=(0, 20))
        
        # Label Input
        self.pwd_label = tk.Label(
            self.frame,
            text="PASSWORD PENGAWAS:",
            font=("Arial", 9, "bold"),
            fg="#94a6b8", # Slate 400
            bg="#1e293b"
        )
        self.pwd_label.pack(anchor="w", padx=150)
        
        # Input Password
        self.entry = tk.Entry(
            self.frame, 
            show="*", 
            font=("Arial", 12),
            bg="#0f172a",
            fg="white",
            insertbackground="white",
            bd=1,
            relief="solid"
        )
        self.entry.pack(fill="x", padx=150, pady=(5, 20))
        self.entry.focus_set()
        self.entry.bind("<Return>", lambda e: self.check_password())
        
        # Tombol Buka Kunci
        self.btn = tk.Button(
            self.frame,
            text="BUKA KUNCI LAYAR",
            font=("Arial", 10, "bold"),
            fg="white",
            bg="#2563eb", # Blue 600
            activebackground="#1d4ed8",
            activeforeground="white",
            bd=0,
            cursor="hand2",
            command=self.check_password
        )
        self.btn.pack(fill="x", padx=150, ipady=10)
        
        # Polling status server (setiap 5 detik)
        self.poll_server()
        
        # Jaga fokus agar tidak bisa klik window lain (setiap 200ms)
        self.keep_focus()

    def on_closing(self):
        pass # Blokir tombol close
        
    def keep_focus(self):
        self.root.focus_force()
        self.entry.focus_set()
        self.root.attributes("-topmost", True)
        self.root.after(200, self.keep_focus)
        
    def check_password(self):
        if self.entry.get() == "pekalongan2":
            self.root.destroy()
            sys.exit(0) # Exit code 0 untuk OK (manual unlock)
        else:
            messagebox.showerror("Akses Ditolak", "Password Pengawas Salah!")
            self.entry.delete(0, tk.END)
            self.entry.focus_set()
            
    def poll_server(self):
        try:
            params = {
                "pc_name": pc_name,
                "ip": ip_addr,
                "chrome_running": "2"
            }
            if mac_addr:
                params["mac"] = mac_addr
            
            url = f"{server_url}/api/check-jadwal?{urllib.parse.urlencode(params)}"
            req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
            with urllib.request.urlopen(req, timeout=3) as response:
                data = json.loads(response.read().decode())
                if data.get("status") == "success" and data.get("event") == "NO_EVENT":
                    self.root.destroy()
                    sys.exit(1) # Exit code 1 untuk Cancel (ujian selesai)
        except Exception:
            pass
        self.root.after(5000, self.poll_server)

if __name__ == "__main__":
    root = tk.Tk()
    app = LockScreen(root)
    root.mainloop()

# Domain Management Guide v4.3 (pjb.my.id)

Dokumen ini berisi instruksi praktis untuk mengelola Reverse Proxy dan pendaftaran subdomain pada ekosistem **ERP-PJBM** melalui skrip otomatis `wghub.ps1`.

## 🛡️ Skrip Utama: `wghub.ps1`

Skrip ini menghubungkan mesin lokal Anda langsung ke **WGHUB Oracle (10.40.0.1)** via WireGuard menggunakan kunci privat OpenSSH.

- **Lokasi**: `D:\erp-pjbm\wghub.ps1`
- **Metode**: Eksekusi langsung (Direct) 🚀

---

## 🛰️ Daftar Perintah Cepat

### 1. Melihat Daftar Domain Aktif
Gunakan perintah ini untuk memantau rute domain yang sudah terdaftar ke IP lokal (VM 116/117/dll).
```powershell
.\wghub.ps1 "sudo domain list"
```

### 2. Mendaftarkan Subdomain Baru
Daftarkan modul baru di bawah domain `pjb.my.id` dengan target IP server tertentu.
```powershell
.\wghub.ps1 "sudo domain add [SUBDOMAIN].pjb.my.id [IP_LOKAL]"
```
*Contoh*: `.\wghub.ps1 "sudo domain add absensi.pjb.my.id 192.168.100.16"`

### 3. Menghapus Subdomain
Hapus konfigurasi rute di Reverse Proxy jika modul tidak lagi digunakan.
```powershell
.\wghub.ps1 "sudo domain delete [DOMAIN_LENGKAP]"
```

### 4. Perintah Diagnostik (Status Server)
Gunakan untuk memastikan asisten AI dan mesin lokal Anda terhubung ke WGHUB.
```powershell
.\wghub.ps1 "uptime"
```

---

## 🔒 Aturan Keamanan (AI Guard)

Setiap pendaftaran domain baru **WAJIB** mengikuti panduan di `.agent/skills/erp-migration-guard/SKILL.md` guna memastikan:
1.  **SSL Termination** di WGHUB aktif (biasanya otomatis via script `domain`). ✅
2.  **IP Target** harus berada di subnet kluster Proxmox lokal. 🛡️
3.  **Audit Log** diperbarui di `docs/AUDIT_MAINTENANCE_LOG.md`. 📊

---
*Status: Ekosistem Siap Operasional.*

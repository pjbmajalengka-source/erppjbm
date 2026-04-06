# PJB-Infrastructure Environment Context
# ---------------------------------------------------------
# Dokumen ini adalah "Peta Navigasi" utama untuk AI Agent 
# yang mengelola ekosistem ERP-PJBM.
# ---------------------------------------------------------

## 🕸️ Cluster PC1 (Web & Utility Hub)
**Host**: 192.168.100.10 (Lenovo TS150)
- **VM 116 (Hestia Prod)**: 192.168.100.16 | Panel: `hprod.pjb.my.id` | Admin: `admin.pjb.my.id` ✅
- **VM 117 (Hestia Stag)**: 192.168.100.17 | Panel: `hstag.pjb.my.id` | Admin: `testadmin.pjb.my.id` 🛡️
- **VM 111 (Zabbix Server)**: 192.168.100.11
- **VM 112 (n8n Integration)**: 192.168.100.12

## 🔥 Cluster PC5 (AI & Storage Hub)
**Host**: 192.168.100.5 (WDC NVMe + SSD Dahua + SSD Kyo)
- **VM 551 (AI-Core)**: 192.168.100.51 | Llama/DeepSeek-R1 Engine ✅
- **VM 552 (Agent-Box)**: 192.168.100.52 | Box Eksekusi Agent 🛡️
- **VM 553 (TrueNAS)**: 192.168.100.53 | Storage SMB/NFS 📊

## 🛡️ Protokol Akses & Keamanan (Agreement 1-6)
1. **Mandatory Script**: Gunakan `.\pjb.ps1 <target> "<command>"` untuk SSH `.16` (Prod) dan `.17` (Stag).
2. **Subdomain Management**: Antigravity diizinkan membuat subdomain via `wghub.ps1` menggunakan perintah:
   `ssh root@10.40.0.1 /usr/local/bin/domain` (Wildcard SSL `*.pjb.my.id`, tipe `laravel`).
3. **Deployment Workflow**: Proses deploy dari Antigravity dilakukan ke **STAGING** (`.17`) atau ke **GitHub** jika diminta.
4. **Access Key**: Otorisasi menggunakan `PC Proxmox key All`.
5. **Audit Log**: Setiap perubahan teknis WAJIB dicatat di `docs/AUDIT_MAINTENANCE_LOG.md`.

## 🤖 AI Local Workflows
Proyek ini mendukung AI lokal via MCP Ollama (VM 551):
- Trigger `/refactor-ollama [file]` untuk optimasi kode.
- Trigger `/build-ollama [task]` untuk rancang bangun fitur besar. ✨ 

*Update Terakhir: 2026-04-03 oleh Antigravity (Berdasarkan Kesepakatan 6 Poin).*

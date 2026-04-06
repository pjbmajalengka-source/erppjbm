# Audit & Maintenance Log: ERP-PJBM Project

Dokumen ini berfungsi sebagai catatan audit teknis dan panduan pemeliharaan untuk proyek ERP Puncak Jaya Baja.

## 1. Setup Environment & Alignment (2026-04-03)
**Perubahan**: Finalisasi "Source of Truth" Infrastruktur & Visi Proyek.
- **Tindakan**: 
  - Sinkronisasi IP: VM 116 (Prod: `.16`), VM 117 (Stag: `.17`).
  - Upgrade Stack: PHP 8.4, Laravel 13, PostgreSQL 16.
  - Setup Local: Windows 11 + Laragon 6 (Apache & Redis 5).
 | 2026-04-05 | 09:15 | WGHUB (Oracle) | Provisioning `admin.pjb.my.id` (.16) & `testadmin.pjb.my.id` (.17) via revised `domain` script. SSL Wildcard fallback path fixed and verified. | Antigravity |
| 2026-04-06 | 13:14 | WGHUB (Oracle) | Bulk Provisioning 12 Subdomains (6 Prod, 6 Stag) using `IP:80` syntax. All project subdomains now active and synced with HestiaCP. | Antigravity |
| 2026-04-06 | 13:54 | D:\ERP-PJBM | Git Integration & SSH Setup. Local repo initialized, .gitignore optimized, and project-specific SSH key generated. | Antigravity |
  - Peta Subdomain: Membedakan `admin.pjb.my.id` (Prod) dan `testadmin.pjb.my.id` (Stag).
- **Maintenance**: Pastikan setiap deploy baru melawati blok Staging `.17`. Gunakan `./pjb.ps1` untuk seluruh akses SSH server internal.

### 2026-04-03 21:30 - [SYNC] Business Logic Alignment
- **Event**: Finalizing Payroll & Wallet logic core.
- **Changes**: 
  - Updated `erp-payroll-calc/SKILL.md`: Allowed negative wallet balance (debt support).
  - Updated `.agent/rules/source_of_truth.md`: Consolidated Laragon/IP/Deploy rules.

### 2026-04-03 21:40 - [REFAC] UI Foundation & Mobile Responsive
- **Event**: Implementation of Mobile-First responsive system.
- **Tech**: Tailwind v4, HSL Design Tokens, Master Layout Component.
- **Changes**:
  - `resources/css/app.css`: Added custom HSL theme and premium glassmorphism utilities.
  - `resources/views/layouts/app.blade.php`: Created master responsive layout.
  - `resources/views/welcome.blade.php`: Refactored home page to use new layout (Brain: Qwen2.5-Coder via Ollama).
- **Status**: Verified (Syntax OK, Responsive Ready).

### 2026-04-04 05:15 - [STRAT] AI Strategy Realignment
- **Event**: Formalizing Dual-AI Workflow (Gemini 3 Flash + MiniMax 2.7).
- **Tooling**: Fixed `ask_openrouter.py` dependency (Installed `requests`).
- **Changes**: Updated `.agent/rules/developer_identity.md` with the new development lifecycle.
- **Maintenance**: Rule and Skill refresh conducted.

### 2026-04-04 07:35 - [INFRA] Admin Subdomain Provisioning
- **Event**: Creating Admin subdomains for Production (.16) and Staging (.17).
- **Tooling**: Used `/usr/local/bin/domain` via WGHUB (10.40.0.1).
- **Changes**: 
  - `admin.pjb.my.id` ➔ Pointed to `192.168.100.16` (Production).
  - `testadmin.pjb.my.id` ➔ Pointed to `192.168.100.17` (Staging).
- **Maintenance**: Manually corrected Nginx variable error (`backend_proto`) on Gateway to restore service.
- **Status**: Verified (DNS Active, Nginx OK).

---
*Log terakhir diperbarui: 2026-04-03 oleh Antigravity.*

# ERP Puncak JB — Index Dokumentasi
> Laravel 13 + PHP 8.4 | Versi 4.1 | 2026-03-23

## Stack Final
| | |
|---|---|
| Framework | Laravel 13 + PHP 8.4 |
| Database | PostgreSQL 16 — `payroll_pjbm` |
| Server Panel | HestiaCP (native, tanpa Docker) |
| Cache/Queue | Redis 7 |
| Tunnel | WireGuard WGHUB → Mikrotik → VM 192.168.100.15 |
| SSL | Terminate di WGHUB (Certbot wildcard *.pjb.my.id) |

## Dokumen

| File | Isi | Update Saat |
|------|-----|-------------|
| `00-PROTOKOL-BERSAMA.md` | **Kontrak lintas modul** — wajib dipatuhi semua pihak | Konsensus semua stakeholder |
| `01-PANEL-SUPERADMIN.md` | Fitur admin: user, cabang, master data, analitik | Ada fitur admin baru |
| `02-PANEL-HR-PAYROLL.md` | Absensi 4-scan, lembur, payroll, kasbon | Ada perubahan aturan HR |
| `03-PANEL-CS.md` | Leaderboard, komisi, KOMCS | Ada perubahan komisi |
| `04-PANEL-KASIR.md` | Kas ledger, rekonsiliasi, tutup buku | Ada perubahan akuntansi |
| `05-PANEL-GUDANG.md` | Delivery order, blind note, tracking | Ada fitur logistik baru |
| `06-PANEL-ME.md` | Self-service: wallet, slip gaji, kasbon | Ada fitur self-service baru |
| `07-TOPOLOGI-SERVER.md` | WireGuard, HestiaCP, Nginx, SSL | Ada perubahan infrastruktur |
| `08-SPEK-DEPENDENSI.md` | Schema DB, composer.json, package.json | Ada package/schema baru |
| `09-LOGIKA-PAYROLL.md` | Formula bisnis payroll (kontrak bisnis) | Perubahan aturan bisnis |
| `10-API-ENDPOINT.md` | Contract API untuk n8n & integrasi | Ada endpoint baru |

## Antigravity Agent Files
| File | Fungsi |
|------|--------|
| `.agent/rules/GEMINI.md` | Rules workspace — selalu aktif |
| `.agent/workflows/new-module.md` | `/new-module` — scaffold modul baru |
| `.agent/workflows/db-migrate.md` | `/db-migrate` — migrasi database aman |
| `.agent/workflows/deploy.md` | `/deploy` — deploy ke HestiaCP |
| `.agent/workflows/payroll-calc.md` | `/payroll-calc` — debug/implementasi kalkulasi |
| `.agent/skills/erp-hestia-setup/` | Auto-load saat ada pertanyaan HestiaCP |
| `.agent/skills/erp-payroll-calc/` | Auto-load saat ada kalkulasi payroll |
| `.agent/skills/erp-migration-guard/` | Auto-load saat ada perubahan schema |

## Referensi
- Laravel 13: https://laravel.com/docs/13.x
- HestiaCP: https://hestiacp.com/docs/introduction/getting-started.html
- Spatie Permission: https://spatie.be/docs/laravel-permission
- Inertia v2: https://inertiajs.com/upgrade-guide
- PHP 8.4: https://www.php.net/releases/8.4

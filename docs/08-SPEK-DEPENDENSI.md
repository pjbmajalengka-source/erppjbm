# 08 — Spesifikasi & Dependensi
> ERP Puncak JB | Versi 3.0 | Terakhir diupdate: 2026-03-23

---

## Daftar Isi

- [Spesifikasi Hardware](#1-spesifikasi-hardware)
- [Tech Stack Lengkap](#2-tech-stack-lengkap)
- [Dependensi Laravel (composer.json)](#3-dependensi-laravel-composerjson)
- [Dependensi Frontend (package.json)](#4-dependensi-frontend-packagejson)
- [Laravel `.env` Referensi](#5-laravel-env-referensi)
- [Database Schema Lengkap](#6-database-schema-lengkap--payroll_pjbm)
- [Struktur Folder Laravel](#7-struktur-folder-laravel)
- [Versi Minimum Software](#8-versi-minimum-software)
- [Spesifikasi AI Node T7810](#9-spesifikasi-ai-node-t7810)
- [Checklist Dependensi](#10-checklist-dependensi)

---

## 1. Spesifikasi Hardware

### Node 1: WGHUB — Gateway Publik

| Komponen | Spesifikasi |
|----------|-------------|
| **OS** | Ubuntu 22.04 LTS |
| **RAM** | Minimal 1 GB (rekomendasi 2 GB) |
| **vCPU** | 1–2 core |
| **Storage** | 20 GB SSD |
| **IP** | IP Publik statis |
| **Peran** | Nginx reverse proxy, SSL termination, WireGuard server |
| **Software** | Nginx, Certbot, WireGuard, UFW |

### Node 2: VM Produksi (Proxmox) — Core Server

| Komponen | Minimum | **Rekomendasi** |
|----------|---------|-----------------|
| **RAM** | 4 GB | **8–16 GB** |
| **vCPU** | 2 core | **4 core** |
| **Storage** | 40 GB | **100 GB SSD (VirtIO)** |
| **OS** | Ubuntu 22.04 LTS | **Ubuntu 22.04 LTS** |
| **IP** | 192.168.100.15 | Static via Netplan |
| **Peran** | HestiaCP, Laravel, PostgreSQL 16, Redis 7, n8n |

### Node 3: T7810 — Intelligence Node

| Komponen | Spesifikasi |
|----------|-------------|
| **RAM** | 64 GB |
| **GPU** | NVIDIA RTX 3060 12GB VRAM |
| **OS** | Ubuntu 22.04 LTS + CUDA 12.x |
| **IP** | 192.168.100.x (LAN, 1 subnet dengan VM) |
| **Peran** | Ollama LLM, RAG Service, Metabase (read-only), OCR |

> **T7810 tidak perlu akses publik.** Hanya berkomunikasi via LAN internal ke VM dan terima request dari n8n.

---

## 2. Tech Stack Lengkap

### VM Produksi (192.168.100.15)

| Teknologi | Versi | Instalasi |
|-----------|-------|-----------|
| Ubuntu | 22.04 LTS | Base OS |
| HestiaCP | Latest stable | Panel manajemen |
| Nginx | 1.24+ (HestiaCP bundle) | Web server internal |
| **PHP** | **8.3** | Via HestiaCP multi-PHP |
| **PostgreSQL** | **16** | Install dari repo PGDG |
| **Redis** | **7** | Install dari repo Redis |
| **Laravel** | **11.x** | App framework |
| Node.js | 20 LTS | Build frontend (Vite) |
| Composer | 2.x | PHP dependency manager |
| Supervisor | 4.x | Process manager queue + scheduler |
| n8n | Latest | Automation engine |

### WGHUB

| Teknologi | Versi | Fungsi |
|-----------|-------|--------|
| Nginx | 1.24+ | Reverse proxy + SSL termination |
| Certbot | Latest | Wildcard SSL via Cloudflare DNS |
| WireGuard | Latest | VPN tunnel ke Mikrotik |
| UFW | Latest | Firewall |

### Frontend (build di VM)

| Teknologi | Versi | Fungsi |
|-----------|-------|--------|
| Vue.js | 3.x | UI framework |
| Inertia.js | 1.x | Jembatan Laravel ↔ Vue |
| Tailwind CSS | 3.x | Styling + theming per panel |
| Pinia | 2.x | State management |
| Laravel Echo | 1.x | WebSocket client |
| Vite | 5.x | Build tool |

### AI Node T7810

| Teknologi | Versi | Fungsi |
|-----------|-------|--------|
| Ollama | Latest | LLM runtime |
| Llama 3.1 8B (Q4_K_M) | — | Chatbot + RAG (~4.5GB VRAM) |
| nomic-embed-text | — | Embedding (~300MB VRAM) |
| ChromaDB | 0.5+ | Vector database |
| FastAPI | 0.110+ | RAG service API |
| Metabase | 0.49+ | Dashboard analytics |
| PaddleOCR | 2.7+ | OCR dokumen |
| Python | 3.11+ | Runtime AI |

---

## 3. Dependensi Laravel (`composer.json`)

```json
{
    "require": {
        "php": "^8.3",
        "laravel/framework": "^11.0",
        "laravel/sanctum": "^4.0",
        "laravel/horizon": "^5.24",
        "laravel/telescope": "^5.0",

        "spatie/laravel-permission": "^6.7",
        "spatie/laravel-activitylog": "^4.7",
        "spatie/laravel-backup": "^9.0",
        "spatie/laravel-query-builder": "^5.6",
        "spatie/laravel-medialibrary": "^11.4",

        "maatwebsite/excel": "^3.1",
        "barryvdh/laravel-dompdf": "^2.2",

        "predis/predis": "^2.2",
        "pusher/pusher-php-server": "^7.2",

        "guzzlehttp/guzzle": "^7.8",
        "inertiajs/inertia-laravel": "^1.3",
        "tightenco/ziggy": "^2.0"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/pint": "^1.13",
        "laravel/sail": "^1.29",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.1",
        "phpunit/phpunit": "^11.0",
        "larastan/larastan": "^2.9"
    }
}
```

### Ekstensi PHP 8.3 yang Wajib Diinstall

```bash
apt install \
  php8.3-fpm \
  php8.3-cli \
  php8.3-common \
  php8.3-pgsql \      # PostgreSQL driver
  php8.3-redis \      # Redis driver
  php8.3-gd \         # Image processing
  php8.3-zip \        # ZIP untuk Excel export
  php8.3-mbstring \   # String multibyte
  php8.3-xml \        # XML processing
  php8.3-curl \       # HTTP client (Guzzle)
  php8.3-bcmath \     # Kalkulasi presisi tinggi (payroll)
  php8.3-intl \       # Internasionalisasi
  php8.3-pcov \       # Code coverage (dev)
  php8.3-opcache \    # Performance cache
  -y
```

### Package Kunci & Kegunaannya

| Package | Kegunaan di ERP |
|---------|-----------------|
| `spatie/laravel-permission` | RBAC 7 role: superadmin, hrd, cs, supir, kasir, gudang, karyawan |
| `spatie/laravel-activitylog` | Audit trail: override absensi manual, approve payroll, tutup buku |
| `spatie/laravel-backup` | Backup `payroll_pjbm` otomatis jam 02:00 |
| `maatwebsite/excel` | Export rekap kehadiran, laporan payroll ke `.xlsx` |
| `barryvdh/laravel-dompdf` | Generate slip gaji PDF per karyawan |
| `laravel/horizon` | Monitor queue (ProcessPayrollJob, SyncFingerspotJob) di UI |
| `pusher/pusher-php-server` | Broadcast `LeaderboardUpdated` event via Redis |
| `inertiajs/inertia-laravel` | Full-stack SPA tanpa API route terpisah |
| `predis/predis` | Redis client PHP (session, cache, queue) |

---

## 4. Dependensi Frontend (`package.json`)

```json
{
    "dependencies": {
        "@inertiajs/vue3": "^1.0",
        "vue": "^3.4",
        "pinia": "^2.1",
        "@vueuse/core": "^10.9",

        "tailwindcss": "^3.4",
        "@tailwindcss/forms": "^0.5",
        "@tailwindcss/typography": "^0.5",

        "laravel-echo": "^1.16",
        "pusher-js": "^8.4",

        "chart.js": "^4.4",
        "vue-chartjs": "^5.3",
        "date-fns": "^3.6",
        "axios": "^1.7",

        "lucide-vue-next": "^0.378",
        "@headlessui/vue": "^1.7"
    },
    "devDependencies": {
        "vite": "^5.0",
        "@vitejs/plugin-vue": "^5.0",
        "laravel-vite-plugin": "^1.0",
        "autoprefixer": "^10.4",
        "postcss": "^8.4",
        "typescript": "^5.4",
        "vue-tsc": "^2.0"
    }
}
```

---

## 5. Laravel `.env` Referensi

```env
APP_NAME="ERP Puncak JB"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://admin.pjb.my.id
APP_KEY=                              # php artisan key:generate

# ─── Database PostgreSQL 16 ─────────────────────────────
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1                     # localhost di VM
DB_PORT=5432
DB_DATABASE=payroll_pjbm
DB_USERNAME=erp_user
DB_PASSWORD=[STRONG_PASSWORD]

# ─── KOMCS (aktifkan Phase 7) ───────────────────────────
# KOMCS_DB_CONNECTION=mysql
# KOMCS_DB_HOST=[HOST_KOMCS]
# KOMCS_DB_PORT=3306
# KOMCS_DB_DATABASE=[DB_KOMCS]
# KOMCS_DB_USERNAME=[USER]
# KOMCS_DB_PASSWORD=[PASS]

# ─── Redis 7 ────────────────────────────────────────────
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1                  # localhost di VM
REDIS_PASSWORD=[REDIS_PASSWORD]
REDIS_PORT=6379

# ─── Session — KRITIS untuk subdomain ───────────────────
SESSION_DRIVER=redis
SESSION_LIFETIME=480                  # 8 jam
SESSION_DOMAIN=.pjb.my.id           # DOT di depan — WAJIB!
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# ─── Cache & Queue ──────────────────────────────────────
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# ─── Broadcasting (WebSocket) ───────────────────────────
BROADCAST_DRIVER=redis
PUSHER_APP_ID=puncakjb-erp
PUSHER_APP_KEY=[RANDOM_KEY]
PUSHER_APP_SECRET=[RANDOM_SECRET]
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

# ─── Trust Proxy (WAJIB karena SSL di WGHUB) ────────────
TRUSTED_PROXIES=*
# Atau spesifik IP WGHUB jika diketahui:
# TRUSTED_PROXIES=10.40.0.1

# ─── Sanctum ────────────────────────────────────────────
SANCTUM_STATEFUL_DOMAINS=admin.pjb.my.id,gaji.pjb.my.id,cs.pjb.my.id,gudang.pjb.my.id,kasir.pjb.my.id,me.pjb.my.id

# ─── Fingerspot ─────────────────────────────────────────
FINGERSPOT_API_URL=[ENDPOINT_FINGERSPOT]
FINGERSPOT_API_KEY=[API_KEY]
LATE_TOLERANCE_MINUTES=10

# ─── WhatsApp API ───────────────────────────────────────
WHATSAPP_API_URL=[ENDPOINT]
WHATSAPP_API_TOKEN=[TOKEN]

# ─── AI Services T7810 ──────────────────────────────────
OLLAMA_URL=http://[IP_T7810]:11434
RAG_SERVICE_URL=http://[IP_T7810]:8080

# ─── n8n ────────────────────────────────────────────────
N8N_ENCRYPTION_KEY=[RANDOM_32_CHARS]

# ─── Mail (opsional) ────────────────────────────────────
MAIL_MAILER=smtp
MAIL_HOST=[SMTP_HOST]
MAIL_PORT=587
MAIL_USERNAME=[EMAIL]
MAIL_PASSWORD=[PASSWORD]
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=erp@pjb.my.id
MAIL_FROM_NAME="ERP Puncak JB"
```

---

## 6. Database Schema Lengkap — `payroll_pjbm`

### Urutan Migration

```
001_create_branches_table
002_create_departments_table
003_create_positions_table               ← time_type: fixed / flexible
004_create_shifts_table
005_add_payroll_fields_to_users_table    ← fingerspot_id, role, BPJS, dll
006_create_assignments_table             ← daily_wage historis per karyawan
007_create_attendances_table             ← 4-scan system
008_create_overtimes_table               ← bongkar / non_bongkar
009_create_wallet_transactions_table     ← dual ledger: salary & bonus
010_create_kasbons_table
011_create_payroll_records_table
012_create_sales_transactions_table      ← sync dari iPOS via n8n
013_create_commission_configs_table
014_create_cash_ledgers_table
015_create_operational_expenses_table
016_create_daily_closings_table          ← immutable setelah locked
017_create_delivery_orders_table
018_create_delivery_items_table          ← blind note (tanpa harga)
019_create_delivery_trackings_table
020_create_ipos_sync_logs_table
021_create_fingerspot_sync_logs_table
022_create_komcs_users_sim_table         ← KOMCS simulasi Phase 3
```

### Schema Detail Per Tabel

#### `branches`
```php
$table->id();
$table->string('name');
$table->string('code', 10)->unique();
$table->text('address')->nullable();
$table->string('phone')->nullable();
$table->boolean('is_active')->default(true);
$table->timestamps();
$table->softDeletes();
```

#### `positions`
```php
$table->id();
$table->foreignId('department_id')->constrained();
$table->string('name');
$table->enum('time_type', ['fixed', 'flexible'])->default('fixed');
// fixed    = dievaluasi keterlambatan (toleransi 10 menit)
// flexible = bebas, tidak ada penalti keterlambatan
$table->timestamps();
```

#### `users` (tambahan ke default Laravel)
```php
$table->string('nik')->unique()->nullable();
$table->string('fingerspot_id')->nullable()->index();
$table->string('phone', 20)->nullable();
$table->date('hire_date')->nullable();
$table->date('birth_date')->nullable();
$table->enum('role', ['superadmin','hrd','cs','supir','kasir','gudang','karyawan'])
      ->default('karyawan');
$table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
$table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
$table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
$table->string('salesman_id', 50)->nullable()->index(); // mapping ke iPOS
$table->decimal('daily_base_salary', 15, 2)->default(0); // referensi HR
$table->enum('salary_type', ['harian','bulanan'])->default('harian');
$table->string('payout_preference')->nullable();
$table->boolean('use_bpjs_health')->default(false);
$table->boolean('use_bpjs_employment')->default(false);
$table->decimal('opening_salary_balance', 15, 2)->default(0); // migrasi
$table->decimal('opening_bonus_balance', 15, 2)->default(0);  // migrasi
$table->boolean('is_active')->default(true);
$table->softDeletes();
```

#### `assignments`
```php
$table->id();
$table->foreignId('user_id')->constrained();
$table->decimal('daily_wage', 15, 2);   // upah harian aktif untuk kalkulasi
$table->date('start_date');             // berlaku mulai tanggal ini
$table->string('notes')->nullable();
$table->foreignId('created_by')->constrained('users');
$table->timestamps();
// Assignment aktif = start_date paling baru ≤ tanggal absensi
```

#### `attendances`
```php
$table->id();
$table->foreignId('user_id')->constrained();
$table->foreignId('branch_id')->constrained();
$table->foreignId('shift_id')->nullable()->constrained();
$table->date('work_date');
// 4-scan
$table->timestamp('clock_in')->nullable();
$table->timestamp('break_start')->nullable();
$table->timestamp('break_end')->nullable();
$table->timestamp('clock_out')->nullable();
// Evaluasi
$table->enum('status', ['present','late','half_day','absent'])->default('absent');
$table->decimal('fingerprint_presence', 3, 1)->default(0.0);
$table->decimal('manual_presence', 3, 1)->nullable();  // override HRD
$table->decimal('final_presence', 3, 1)->default(0.0); // manual ?? fingerprint
$table->decimal('worked_hours', 5, 2)->default(0.00);
$table->enum('source', ['fingerspot','manual'])->default('fingerspot');
$table->string('manual_note')->nullable();
$table->foreignId('manual_by')->nullable()->constrained('users');
$table->timestamps();
$table->unique(['user_id', 'work_date']);
```

#### `overtimes`
```php
$table->id();
$table->foreignId('user_id')->constrained();
$table->foreignId('branch_id')->constrained();
$table->date('work_date');
$table->enum('type', ['bongkar','non_bongkar']);
$table->timestamp('start_time');
$table->timestamp('end_time');                          // addDay() jika overnight
$table->boolean('is_break_taken')->default(false);      // khusus bongkar
$table->decimal('gross_hours', 5, 2)->default(0);
$table->decimal('net_hours', 5, 2)->default(0);
$table->decimal('meal_allowance', 15, 2)->default(0);   // bongkar: Rp10.000 jika >2 jam
$table->decimal('earned_amount', 15, 2)->default(0);
$table->string('notes')->nullable();
$table->foreignId('created_by')->constrained('users');
$table->timestamps();
```

#### `wallet_transactions`
```php
$table->id();
$table->foreignId('user_id')->constrained();
$table->enum('ledger', ['salary','bonus']);
$table->enum('type', ['earning','payout','adjustment']);
$table->decimal('amount', 15, 2);           // selalu positif
$table->string('description');
$table->string('reference_id')->nullable();  // FK ke payroll_record, kasbon, dll
$table->string('reference_type')->nullable();
$table->foreignId('created_by')->constrained('users');
$table->timestamps();
// Saldo = opening_balance + SUM(earning) - SUM(payout + adjustment)
// Saldo minimum = 0 (tidak bisa negatif)
```

#### `payroll_records`
```php
$table->id();
$table->foreignId('user_id')->constrained();
$table->foreignId('branch_id')->constrained();
$table->string('period', 7);               // "2025-06"
$table->integer('days_present')->default(0);
$table->integer('days_late')->default(0);
$table->integer('days_half')->default(0);
$table->integer('days_absent')->default(0);
$table->decimal('daily_wage_snapshot', 15, 2);
$table->decimal('base_earning', 15, 2)->default(0);
$table->decimal('overtime_earning', 15, 2)->default(0);
$table->decimal('bonus_other', 15, 2)->default(0);
$table->decimal('deduct_bpjs_health', 15, 2)->default(0);
$table->decimal('deduct_bpjs_employment', 15, 2)->default(0);
$table->decimal('deduct_kasbon', 15, 2)->default(0);
$table->decimal('deduct_other', 15, 2)->default(0);
$table->decimal('gross_salary', 15, 2)->default(0);
$table->decimal('net_salary', 15, 2)->default(0);
$table->enum('status', ['draft','approved','paid'])->default('draft');
$table->string('notes')->nullable();
$table->foreignId('calculated_by')->nullable()->constrained('users');
$table->foreignId('approved_by')->nullable()->constrained('users');
$table->timestamp('approved_at')->nullable();
$table->timestamp('paid_at')->nullable();
$table->timestamps();
$table->unique(['user_id', 'period']);
```

#### `daily_closings` ⚠️ Immutable
```php
$table->id();
$table->foreignId('branch_id')->constrained();
$table->date('closing_date');
$table->decimal('opening_balance', 15, 2);
$table->decimal('total_revenue_ipos', 15, 2);
$table->decimal('total_cash_deposit', 15, 2);
$table->decimal('total_expenses', 15, 2);
$table->decimal('total_payroll', 15, 2)->default(0);
$table->decimal('closing_balance', 15, 2);
$table->decimal('discrepancy', 15, 2)->default(0);
$table->string('notes')->nullable();
$table->boolean('is_locked')->default(false);
$table->foreignId('locked_by')->nullable()->constrained('users');
$table->timestamp('locked_at')->nullable();
$table->timestamps();
$table->unique(['branch_id', 'closing_date']);
```

**PostgreSQL Trigger Immutable:**
```sql
CREATE OR REPLACE FUNCTION prevent_closing_edit()
RETURNS TRIGGER AS $$
BEGIN
    IF OLD.is_locked = TRUE THEN
        RAISE EXCEPTION 'Daily closing sudah dikunci, tidak dapat diubah.';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_prevent_closing_edit
BEFORE UPDATE OR DELETE ON daily_closings
FOR EACH ROW EXECUTE FUNCTION prevent_closing_edit();
```

#### `delivery_items` (Blind Note — no price)
```php
$table->id();
$table->foreignId('delivery_order_id')->constrained()->cascadeOnDelete();
$table->string('item_code')->nullable();
$table->string('item_name');
$table->string('unit');
$table->decimal('quantity', 10, 2);
// ⚠️ TIDAK ADA KOLOM HARGA — Blind Note
$table->text('notes')->nullable();
$table->timestamps();
```

---

## 7. Struktur Folder Laravel

```
/home/erpuser/web/pjb.my.id/
├── app/                         ← Laravel root
│   ├── Console/Commands/
│   │   ├── FingerspotSyncCommand.php
│   │   ├── PayrollCalculateCommand.php
│   │   ├── WalletRecalculateCommand.php
│   │   └── CleanupOldPayslipsCommand.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── SuperAdmin/
│   │   │   ├── HR/
│   │   │   ├── CS/
│   │   │   ├── Accounting/
│   │   │   ├── Warehouse/
│   │   │   ├── Employee/
│   │   │   └── Sync/            ← endpoint untuk n8n
│   │   │
│   │   └── Middleware/
│   │       ├── DetectSubdomain.php
│   │       ├── EnforcePanelRole.php
│   │       ├── RequireSubdomain.php
│   │       └── TrustProxies.php  ← WAJIB untuk topologi ini
│   │
│   ├── Models/                  ← 20 model
│   └── Services/                ← 9 service classes
│
├── public/                      ← web root HestiaCP (symlink)
│   └── index.php
│
├── storage/
│   └── app/public/              ← PDF slip gaji, foto bukti
│
├── .env
├── composer.json
└── package.json

public_html/                     ← symlink ke app/public/
logs/
├── nginx.access.log
├── nginx.error.log
├── queue-critical.log
├── queue-default.log
├── scheduler.log
└── horizon.log
```

---

## 8. Versi Minimum Software

| Software | Minimum | **Target** | Catatan |
|----------|---------|-----------|---------|
| PHP | 8.2 | **8.3** | Install via HestiaCP multi-PHP |
| Laravel | 11.0 | **11.x latest** | |
| PostgreSQL | 15 | **16** | Install dari repo PGDG |
| Redis | 6.2 | **7.x** | Install dari repo Redis |
| Node.js | 18 LTS | **20 LTS** | Untuk build Vite |
| Nginx | 1.22 | **1.24+** | HestiaCP bundle |
| Ubuntu | 20.04 | **22.04 LTS** | OS VM |
| HestiaCP | 1.8+ | **Latest stable** | |
| Python (T7810) | 3.10 | **3.11** | |
| CUDA (T7810) | 11.8 | **12.x** | |
| WireGuard | Latest | Latest | Kernel module Ubuntu |

---

## 9. Spesifikasi AI Node T7810

### Model LLM

| Model | VRAM | Kegunaan |
|-------|------|----------|
| `llama3.1:8b` (Q4_K_M) | ~4.5 GB | CS Chatbot + RAG |
| `nomic-embed-text` | ~300 MB | Embedding dokumen |
| **Total** | **~5 GB** | dari 12GB tersedia |

### RAG Pipeline

```
Vector Store:    ChromaDB
Embedding Dim:   768
Chunk Size:      512 token
Chunk Overlap:   64 token
Top-K:           5 dokumen
Min Similarity:  0.70
```

### Koneksi ke PostgreSQL VM

```
Host:     192.168.100.15
Port:     5432
Database: payroll_pjbm
User:     analytics_reader  ← READ-ONLY
SSL:      prefer
```

```sql
-- Setup di PostgreSQL VM:
CREATE USER analytics_reader WITH PASSWORD '[strong]';
GRANT CONNECT ON DATABASE payroll_pjbm TO analytics_reader;
GRANT USAGE ON SCHEMA public TO analytics_reader;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO analytics_reader;
ALTER DEFAULT PRIVILEGES IN SCHEMA public
    GRANT SELECT ON TABLES TO analytics_reader;

-- Izinkan koneksi dari IP T7810
-- /etc/postgresql/16/main/pg_hba.conf:
-- host payroll_pjbm analytics_reader [IP_T7810]/32 scram-sha-256
```

---

## 10. Checklist Dependensi

### VM Produksi — Install Sequence

```bash
# 1. Update sistem
apt update && apt upgrade -y

# 2. Install HestiaCP (lihat 02-TOPOLOGI-DAN-SERVER.md)
bash hst-install.sh [flags...]

# 3. Upgrade PostgreSQL ke 16
# (lihat langkah lengkap di 02-TOPOLOGI-DAN-SERVER.md)

# 4. Install PHP 8.3 + ekstensi
apt install php8.3-fpm php8.3-cli php8.3-pgsql php8.3-redis \
    php8.3-gd php8.3-zip php8.3-mbstring php8.3-xml \
    php8.3-curl php8.3-bcmath php8.3-intl php8.3-opcache -y

# 5. Install Redis 7
# (lihat langkah lengkap di 02-TOPOLOGI-DAN-SERVER.md)

# 6. Install Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install nodejs -y

# 7. Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# 8. Install Supervisor
apt install supervisor -y

# 9. Install n8n
npm install -g n8n

# 10. Verifikasi semua versi
php --version          # PHP 8.3.x
psql --version         # PostgreSQL 16.x
redis-server --version # Redis 7.x
node --version         # v20.x.x
composer --version     # Composer 2.x
```

### Verifikasi PHP Extensions

```bash
php -m | grep -E "pgsql|redis|gd|zip|mbstring|xml|curl|bcmath|intl|opcache"
# Semua harus muncul di output
```

### Verifikasi Database

```bash
# Test koneksi PostgreSQL
psql -U erp_user -d payroll_pjbm -h 127.0.0.1 -c "SELECT version();"

# Test Redis
redis-cli -a [PASSWORD] ping
# PONG

# Test dari Laravel
php artisan tinker
>>> DB::connection()->getPdo()
>>> Cache::store('redis')->put('test', 'ok', 10)
>>> Cache::store('redis')->get('test')
# "ok"
```

### Verifikasi Topologi

```bash
# Di WGHUB — pastikan tunnel aktif
wg show
ping 192.168.100.15 -c 3
curl -I http://192.168.100.15/

# Di VM — pastikan terima request dengan header yang benar
# Cek X-Forwarded-Proto di Laravel log
tail -f /home/erpuser/web/pjb.my.id/logs/nginx.access.log
```

---

*Dokumen ini bagian dari: ERP Puncak JB Technical Blueprint v3.0*
*Disiapkan oleh: Google Antigravity*

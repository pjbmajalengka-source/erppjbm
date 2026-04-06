# 00 — PROTOKOL BERSAMA
> ERP Puncak JB | Kontrak Lintas Modul | Laravel 13 + PHP 8.4
> Versi: 1.0 | 2026-03-23

---

> 🔴 **DOKUMEN INI ADALAH KONTRAK.**
> Semua modul, semua developer, semua AI agent WAJIB mematuhi protokol ini.
> Tidak ada pengecualian tanpa persetujuan semua pihak.

---

## Daftar Isi

1. [Protokol Database](#1-protokol-database)
2. [Protokol Subdomain & Session](#2-protokol-subdomain--session)
3. [Protokol Payroll & Wallet](#3-protokol-payroll--wallet)
4. [Protokol Autentikasi & RBAC](#4-protokol-autentikasi--rbac)
5. [Protokol Queue & Job](#5-protokol-queue--job)
6. [Protokol Event & Broadcast](#6-protokol-event--broadcast)
7. [Protokol Integrasi Eksternal](#7-protokol-integrasi-eksternal)
8. [Protokol Kode](#8-protokol-kode)
9. [Protokol Docs](#9-protokol-docs)
10. [Daftar Konstanta Global](#10-daftar-konstanta-global)
11. [Protokol Git & Sinkronisasi](#11-protokol-git--sinkronisasi)

---

## 1. Protokol Database

### P-DB-01 · Satu Database untuk Semua Modul

```
Database tunggal: payroll_pjbm (PostgreSQL 16)
Host: 127.0.0.1:5432 (localhost VM)

Pengecualian:
  - KOMCS: DB terpisah, read-only via koneksi 'komcs' (Phase 7)
  - n8n: DB terpisah n8n_db, dikelola n8n sendiri
  - T7810 Metabase: koneksi read-only via user analytics_reader
```

### P-DB-02 · Tidak Ada Write dari T7810

```
T7810 (Metabase, RAG, OCR) HANYA membaca dari payroll_pjbm.
User: analytics_reader — SELECT only.
DILARANG membuat koneksi write dari T7810 ke payroll_pjbm.
```

### P-DB-03 · Migration Hanya Tambah, Tidak Edit

```
Migration yang sudah dijalankan = BEKU.
Perubahan schema = buat migration baru.
Trigger PostgreSQL pada daily_closings = TIDAK BOLEH DIUBAH.
```

### P-DB-04 · Soft Delete untuk Data Karyawan

```
Data karyawan (users) menggunakan softDeletes().
Nonaktif karyawan = set is_active = false + soft delete.
JANGAN hard delete data karyawan yang pernah punya transaksi.
```

### P-DB-05 · Branch Scoping Wajib

```
Semua tabel yang punya branch_id WAJIB punya Global Scope
yang filter berdasarkan auth()->user()->branch_id.

Pengecualian: role superadmin tidak kena filter.

Implementasi:
  protected static function booted(): void {
      static::addGlobalScope('branch', function (Builder $q) {
          if (auth()->check() && !auth()->user()->hasRole('superadmin')) {
              $q->where('branch_id', auth()->user()->branch_id);
          }
      });
  }
```

---

## 2. Protokol Subdomain & Session

### P-SUB-01 · Peta Subdomain Final

| Layanan | Production (`.16`) | Staging (`.17`) | Role Terkait |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin.pjb.my.id` | `testadmin.pjb.my.id` | superadmin |
| **Payroll** | `gaji.pjb.my.id` | `testgaji.pjb.my.id` | hrd, superadmin |
| **CS** | `cs.pjb.my.id` | `testcs.pjb.my.id` | cs, superadmin |
| **Gudang** | `gudang.pjb.my.id` | `testgudang.pjb.my.id` | gudang, supir, superadmin |
| **Kasir** | `kasir.pjb.my.id` | `testkasir.pjb.my.id` | kasir, superadmin |
| **Self-Service**| `me.pjb.my.id` | `testme.pjb.my.id` | semua role |
| **API** | `api.pjb.my.id` | `testapi.pjb.my.id` | Sanctum token |
| **Automation** | `n8n.pjb.my.id` | - | akses terbatas IP |

### P-SUB-02 · Session Domain Wajib

```
SESSION_DOMAIN=.pjb.my.id  ← dot di depan WAJIB
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true

Tanpa dot di depan = session tidak dishare antar subdomain.
```

### P-SUB-03 · TrustProxies Wajib

```
SSL terminate di WGHUB. VM menerima HTTP biasa.
TrustProxies WAJIB dikonfigurasi:
  protected $proxies = '*';

Tanpa ini: url() generate http://, asset() salah, secure cookie gagal.
Ref: https://laravel.com/docs/13.x/requests#configuring-trusted-proxies
```

### P-SUB-04 · Redirect Role Salah Subdomain

```
Jika user login dengan role yang tidak cocok dengan subdomain:
  → EnforcePanelRole::findCorrectSubdomain()
  → redirect ke subdomain yang benar
  → JANGAN tampilkan 403
```

---

## 3. Protokol Payroll & Wallet

### P-PAY-01 · Formula Tidak Boleh Berubah Tanpa Update Docs

```
Setiap perubahan formula kalkulasi (earning, lembur, saldo) WAJIB:
  1. Update docs/04-LOGIKA-PAYROLL.md DULU
  2. Update config/payroll.php jika ada nilai baru
  3. Baru update kode
Tidak boleh terbalik.
```

### P-PAY-02 · Nilai Bisnis dari Config

```
Semua nilai numerik bisnis WAJIB dari config/payroll.php.
DILARANG hardcode di Service, Controller, atau View.

Daftar nilai yang harus dari config:
  config('payroll.late_tolerance_minutes')
  config('payroll.overtime.rate_bongkar')
  config('payroll.overtime.rate_non_bongkar')
  config('payroll.overtime.meal_allowance')
  config('payroll.overtime.meal_threshold')
  config('payroll.overtime.break_minutes')
  config('payroll.wallet.minimum_balance')
  config('payroll.bpjs.health_employee_rate')
  config('payroll.bpjs.employment_jht_rate')
  config('payroll.bpjs.employment_jp_rate')
  config('payroll.driver_bonus.flat_per_delivery')
  config('payroll.driver_bonus.omzet_threshold')
```

### P-PAY-03 · Manual Presence Selalu Menang

```
final_presence = manual_presence ?? fingerprint_presence

Jika HRD mengisi manual_presence, nilai fingerspot DIABAIKAN.
Saat upsert attendance dari fingerspot:
  JANGAN overwrite manual_presence yang sudah ada.
```

### P-PAY-04 · Saldo Wallet Tidak Bisa Negatif

```
Saldo wallet minimum = config('payroll.wallet.minimum_balance') = 0.
Gunakan max(0, saldo_kalkulasi).
JANGAN izinkan payout yang melebihi saldo.
```

### P-PAY-05 · Dual Ledger Selalu Dipisah

```
Ledger 'salary' dan 'bonus' adalah dua entitas TERPISAH.
JANGAN pernah campur transaksi salary ke bonus atau sebaliknya.

salary ← gaji harian dari payroll approval
bonus  ← komisi CS, bonus supir, insentif manual
```

---

## 4. Protokol Autentikasi & RBAC

### P-AUTH-01 · Sanctum untuk API, Session untuk Web

```
Web panel (semua subdomain): Laravel Sanctum stateful (session-based)
API endpoint (api.pjb.my.id): Sanctum token-based

Ref: https://laravel.com/docs/13.x/sanctum
```

### P-AUTH-02 · Spatie Permission untuk RBAC

```
Package: spatie/laravel-permission
7 role: superadmin, hrd, cs, supir, kasir, gudang, karyawan

JANGAN gunakan custom role checking (if $user->role === 'hrd').
Selalu gunakan: $user->hasRole('hrd') atau middleware 'role:hrd'.
```

### P-AUTH-03 · Permission Granular untuk Operasi Kritis

```
Operasi kritis butuh permission eksplisit (bukan cuma role):
  payroll.approve     → siapa yang boleh approve payroll
  closing.execute     → siapa yang boleh tutup buku
  kasbon.approve      → siapa yang boleh approve kasbon
  attendance.override → siapa yang boleh override fingerspot

Assign permission ke role di RolePermissionSeeder.
```

---

## 5. Protokol Queue & Job

### P-QUEUE-01 · Prioritas Queue

```
critical → PayrollApproved, SendWhatsAppNotificationJob, SendClosingNotificationJob
default  → SyncFingerspotJob, UpdateLeaderboardCacheJob, SyncIposTransactionsJob
low      → GeneratePayslipPDFJob, SendEmailJob
```

### P-QUEUE-02 · Job Idempotent

```
Semua Job WAJIB idempotent (aman dijalankan berkali-kali).
Gunakan upsert, bukan insert, untuk data yang bisa duplikat.
Job fingerspot sync WAJIB pakai updateOrCreate untuk attendance.
```

### P-QUEUE-03 · Failed Job Handling

```
Setiap Job WAJIB punya:
  - $tries = 3
  - $backoff = [30, 60, 120]  (exponential backoff)
  - failed() method yang log error ke database

Ref: https://laravel.com/docs/13.x/queues#dealing-with-failed-jobs
```

---

## 6. Protokol Event & Broadcast

### P-EVENT-01 · Daftar Event Resmi

```
LeaderboardUpdated      → broadcast ke channel 'cs-leaderboard'
PayrollApproved         → trigger WalletService + notif WA
DeliveryStatusUpdated   → trigger WA ke customer (jika terkirim)
```

### P-EVENT-02 · Broadcast Channel Auth

```
WebSocket channel harus di-auth:
  Broadcast::channel('cs-leaderboard', fn() => true);  ← publik
  Broadcast::channel('user.{id}', fn($user, $id) => $user->id === $id);

Ref: https://laravel.com/docs/13.x/broadcasting#authorizing-channels
```

---

## 7. Protokol Integrasi Eksternal

### P-EXT-01 · n8n sebagai Integration Bus, Bukan Logic Engine

```
n8n bertugas: KAPAN dan BAGAIMANA data berpindah.
n8n BUKAN tempat logika bisnis.
Kalkulasi, validasi, transformasi bisnis = di Laravel Service.
n8n hanya: trigger, transform format, kirim ke endpoint.
```

### P-EXT-02 · API Endpoint untuk n8n Wajib Sanctum Token

```
Semua endpoint di api.pjb.my.id yang dikonsumsi n8n:
  - Wajib pakai Sanctum token auth
  - Token dibuat khusus untuk n8n (jangan pakai token user)
  - Rotasi token setiap 90 hari
```

### P-EXT-03 · Fingerspot via Artisan, Bukan n8n

```
Sync absensi fingerspot = php artisan fingerspot:sync
Dijadwal via Laravel Scheduler setiap 15 menit.
n8n TIDAK mengelola fingerspot sync.
```

### P-EXT-04 · KOMCS Mode Simulasi Default

```
CommissionService default ke mode simulasi (komcs_users_sim).
Mode production (DB::connection('komcs')) diaktifkan di Phase 7.
Selalu log error KOMCS ke Laravel Log dengan prefix 'KOMCS Commission Error'.
```

---

## 8. Protokol Kode

### P-CODE-01 · Arsitektur Layer

```
Request → FormRequest (validasi) → Controller → Service → Repository/Model → DB

Controller: tipis, hanya orkestrasi
Service:    semua logika bisnis
Model:      relasi, scope, cast
```

### P-CODE-02 · Dependency Injection, Bukan Facade (untuk Service)

```
// ✅ BENAR
public function __construct(
    private readonly PayrollCalculatorService $payrollService,
    private readonly WalletService $walletService,
) {}

// ❌ HINDARI di Service (boleh di Controller)
$payroll = app(PayrollCalculatorService::class);
```

### P-CODE-03 · Custom Exception

```
Setiap kondisi error bisnis punya custom exception:
  ClosingAlreadyLockedException
  InsufficientKasbonException
  UnauthorizedPanelException
  AssignmentNotFoundException

Lokasi: app/Exceptions/
```

### P-CODE-04 · Tidak Ada N+1 Query

```
Selalu eager load relasi yang dibutuhkan.
Gunakan with() atau load() sebelum iterasi collection.
Gunakan Laravel Telescope di development untuk deteksi N+1.
```

---

## 9. Protokol Docs

### P-DOCS-01 · Update Docs = Update Kode

```
Docs dan kode HARUS selalu sinkron.
Jika kode berubah → update docs yang relevan di sesi yang sama.
Tidak boleh ada "nanti diupdate docs-nya".
```

### P-DOCS-02 · Dokumen yang Ada

```
docs/README.md              → arsitektur, keputusan, roadmap
docs/00-PROTOKOL-BERSAMA.md → kontrak ini (dokumen ini)
docs/01-PANEL-SUPERADMIN.md → fitur panel super admin
docs/02-PANEL-HR-PAYROLL.md → fitur HR, absensi, payroll
docs/03-PANEL-CS.md         → fitur CS & komisi
docs/04-PANEL-KASIR.md      → fitur akuntansi & tutup buku
docs/05-PANEL-GUDANG.md     → fitur warehouse & logistik
docs/06-PANEL-ME.md         → fitur self-service karyawan
docs/07-TOPOLOGI-SERVER.md  → infrastruktur & konfigurasi
docs/08-SPEK-DEPENDENSI.md  → stack, schema, package
docs/09-LOGIKA-PAYROLL.md   → formula bisnis payroll
docs/11-GIT-WORKFLOW.md   → API contract untuk n8n & mobile
```

### P-DOCS-03 · Dokumen yang Tidak Boleh Diubah Sembarangan

```
docs/00-PROTOKOL-BERSAMA.md  → butuh persetujuan semua pihak
docs/09-LOGIKA-PAYROLL.md    → butuh persetujuan HRD/owner
```

---

## 10. Daftar Konstanta Global

> Referensi cepat. Detail ada di `config/payroll.php`.

| Konstanta | Nilai | Sumber |
|-----------|-------|--------|
| Toleransi terlambat | 10 menit | `config('payroll.late_tolerance_minutes')` |
| Rate lembur bongkar | Rp 10.000/jam | `config('payroll.overtime.rate_bongkar')` |
| Rate lembur non-bongkar | Rp 7.500/jam | `config('payroll.overtime.rate_non_bongkar')` |
| Uang makan lembur | Rp 10.000 flat | `config('payroll.overtime.meal_allowance')` |
| Threshold uang makan | 2 jam | `config('payroll.overtime.meal_threshold')` |
| Potongan istirahat bongkar | 30 menit | `config('payroll.overtime.break_minutes')` |
| Saldo wallet minimum | 0 | `config('payroll.wallet.minimum_balance')` |
| BPJS Kesehatan karyawan | 1% | `config('payroll.bpjs.health_employee_rate')` |
| BPJS JHT karyawan | 2% | `config('payroll.bpjs.employment_jht_rate')` |
| BPJS JP karyawan | 1% | `config('payroll.bpjs.employment_jp_rate')` |
| Bonus supir flat | Rp 5.000/DO | `config('payroll.driver_bonus.flat_per_delivery')` |
| Threshold omzet bonus supir | Rp 3.000.000 | `config('payroll.driver_bonus.omzet_threshold')` |
| HestiaCP panel port | 8083 | `config server` |
| Session domain | `.pjb.my.id` | `.env SESSION_DOMAIN` |
| DB name | `payroll_pjbm` | `.env DB_DATABASE` |

---

## 11. Protokol Git & Sinkronisasi

### P-GIT-01 · SSH Mandatory untuk Semua Operasi
```
Selalu gunakan kunci SSH khusus proyek:
  C:\Users\HP\.ssh\id_ed25519_pjbm
Koneksi via HTTPS DILARANG untuk agent AI (Antigravity).
```

### P-GIT-02 · Format Commit Wajib
```
Format: [type]: [deskripsi]
Type: feat, fix, infra, db, docs, refactor, config.
Deskripsi: Gunakan Bahasa Indonesia yang lugas.
```

### P-GIT-03 · .gitignore adalah Kontrak Keamanan
```
DILARANG melakukan --force add pada file yang ada di .gitignore.
Setiap penambahan folder vendor baru atau file sensitif (.env.*)
WAJIB dipastikan sudah masuk .gitignore sebelum push.
```

### P-GIT-04 · Workflow Staging (.17)
```
Setiap perubahan besar WAJIB diuji di Staging (test*.pjb.my.id)
sebelum dilakukan deployment ke Produksi (.16).
```

---

*Versi: 1.1 | ERP Puncak JB | Laravel 13 + PHP 8.4*
*Update: 2026-04-06 | Penambahan Protokol Git & Sinkronisasi.*

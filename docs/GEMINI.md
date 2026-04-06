# ERP Puncak JB — Workspace Rules
# Lokasi: .agent/rules/GEMINI.md
# Scope: Workspace — berlaku untuk semua sesi di project ini
# Ref: https://antigravity.google/docs (Rules & Scopes)

---

## IDENTITAS PROYEK

Proyek ini adalah **ERP Puncak JB**, sistem manajemen operasional internal berbasis
**Laravel 13 + PHP 8.4**, di-deploy via **HestiaCP** di VM Proxmox, dengan jalur
akses publik melalui **WireGuard tunnel → WGHUB → Cloudflare**.

Stack lengkap dan seluruh keputusan arsitektur ada di `docs/`. Baca sebelum kerjakan apapun.

---

## RULE-01 · BACA DOCS DULU, KODE KEMUDIAN

```
Sebelum menulis SATU BARIS kode pun, baca docs/ yang relevan.
Urutan baca:
  1. docs/README.md         → arsitektur + keputusan final
  2. docs/[modul terkait]   → spesifikasi fitur / schema
  3. docs/04-LOGIKA-PAYROLL → jika ada kalkulasi payroll

STOP jika ada kontradiksi antara instruksi dan docs/.
Lapor ke user, jangan ambil keputusan sendiri.
```

## RULE-02 · REFERENSI RESMI WAJIB

```
Untuk setiap API Laravel yang digunakan, WAJIB cek:
  https://laravel.com/docs/13.x

Untuk upgrade/breaking changes:
  https://laravel.com/docs/13.x/upgrade

Untuk package Spatie:
  https://spatie.be/docs/laravel-permission (RBAC)
  https://spatie.be/docs/laravel-activitylog (audit)

Untuk Inertia v2:
  https://inertiajs.com/upgrade-guide

DILARANG menulis kode berdasarkan ingatan versi lama Laravel.
```

## RULE-03 · DILARANG HARDCODE NILAI BISNIS

```
Semua konstanta bisnis ada di config/payroll.php.
DILARANG menulis angka langsung di kode:

  ❌  if ($hours > 2) { $meal = 10000; }
  ✅  if ($hours > config('payroll.overtime.meal_threshold')) {
          $meal = config('payroll.overtime.meal_allowance');
      }

Daftar konstanta yang HARUS dari config:
  - Toleransi keterlambatan
  - Rate lembur bongkar/non-bongkar
  - Uang makan lembur
  - Threshold uang makan
  - Potongan istirahat lembur
  - Persentase BPJS
  - Minimum saldo wallet
  - Bonus supir
```

## RULE-04 · CONTROLLER TIPIS, SERVICE GEMUK

```
Controller hanya boleh:
  1. Terima request
  2. Panggil FormRequest untuk validasi
  3. Panggil Service
  4. Return response / Inertia render

DILARANG ada logika bisnis di Controller:
  ❌  $earning = $attendance->daily_wage * $attendance->final_presence;
  ✅  $earning = $this->payrollService->calculateDailyEarning($attendance);

Semua kalkulasi ada di app/Services/
```

## RULE-05 · WAJIB FORM REQUEST

```
Setiap endpoint yang terima input dari user WAJIB pakai FormRequest.

  ❌  $request->validate([...])  di dalam Controller
  ✅  StorePayrollRequest extends FormRequest

Lokasi: app/Http/Requests/[Modul]/[Action]Request.php
```

## RULE-06 · SATU TUGAS = SATU PERUBAHAN

```
Kerjakan tepat SATU hal per sesi.
Jika task besar, pecah jadi subtask dan kerjakan satu per satu.

Format deklarasi sebelum mulai kerjakan:
  "Saya akan mengerjakan: [X]"
  "File yang akan dibuat/diubah: [list]"
  "Dependensi yang terpengaruh: [list]"
  "Docs yang akan diupdate: [list]"
```

## RULE-07 · SETIAP KODE = UPDATE DOCS

```
Urutan wajib setiap ada perubahan:
  1. Baca docs/ yang relevan
  2. Tulis kode
  3. Update docs/ yang terpengaruh
  4. Laporan perubahan dengan format standar

DILARANG: kode berubah tapi docs/ tidak diupdate.
```

## RULE-08 · CEGAH PUTUS DEPENDENSI

```
Sebelum mengubah apapun, mental checklist:
  □ Ada Model lain yang relasi ke file ini?
  □ Ada Service yang inject class ini?
  □ Ada Route yang mengarah ke method ini?
  □ Ada Job/Event/Listener yang bergantung pada ini?
  □ Ada migration yang FK ke tabel ini?
  □ Ada config/payroll.php yang dirujuk kode ini?

Jika ADA → sebutkan semua dependensi sebelum lanjut.
```

## RULE-09 · MIGRATION BARU, BUKAN EDIT LAMA

```
DILARANG edit migration yang sudah pernah dijalankan.

  ❌  Edit 007_create_attendances_table.php
  ✅  Buat 023_add_notes_to_attendances_table.php

Nama migration: YYYY_MM_DD_NNNNNN_[action]_[target].php
```

## RULE-10 · VERSI STACK FINAL (TIDAK BOLEH DITURUNKAN)

```
PHP        : 8.4
Laravel    : 13.x
PostgreSQL : 16
Redis      : 7.x
Node.js    : 22 LTS
Vite       : 6.x
Tailwind   : 4.x (pakai @tailwindcss/vite, BUKAN tailwind.config.js)
Inertia    : 2.x
Vue        : 3.5+

DILARANG menulis sintaks yang deprecated di versi ini.
Cek https://laravel.com/docs/13.x/upgrade sebelum pakai API lama.
```

## RULE-11 · HESTIACP NATIVE (TANPA DOCKER)

```
Deploy menggunakan HestiaCP native, BUKAN Docker.
Laravel jalan di PHP 8.4-FPM + Nginx HestiaCP.

Path web root HestiaCP:
  /home/erpuser/web/pjb.my.id/app/         ← Laravel root
  /home/erpuser/web/pjb.my.id/public_html/ ← symlink ke app/public/

Process manager: Supervisor (bukan Docker Compose)
Queue workers, scheduler, Horizon — semua via Supervisor.
```

## RULE-12 · TRUST PROXY WAJIB

```
SSL terminate di WGHUB, bukan di VM.
Middleware TrustProxies WAJIB dikonfigurasi:
  protected $proxies = '*';

Tanpa ini: url() generate http://, session secure cookie tidak jalan.
Ref: https://laravel.com/docs/13.x/requests#configuring-trusted-proxies
```

## RULE-13 · SESSION LINTAS SUBDOMAIN

```
Session config wajib:
  SESSION_DRIVER=redis
  SESSION_DOMAIN=.pjb.my.id   ← DOT DI DEPAN, WAJIB
  SESSION_SECURE_COOKIE=true

Tanpa dot di depan, session tidak dishare antar subdomain.
```

## RULE-14 · DATABASE IMMUTABLE CLOSING

```
Tabel daily_closings WAJIB punya PostgreSQL trigger yang
mencegah UPDATE/DELETE setelah is_locked = true.

Ini bukan validasi PHP — ini constraint di level database.
Jangan hapus trigger ini dengan alasan apapun.
```

## FORMAT LAPORAN WAJIB SETIAP SELESAI TUGAS

```markdown
## ✅ Selesai: [Nama Tugas]

### File Baru
- `app/Services/NamaService.php`

### File Diubah
- `app/Http/Controllers/HR/PayrollController.php`
  → [deskripsi perubahan]
- `docs/04-LOGIKA-PAYROLL.md`
  → [section yang diupdate]

### Dependensi yang Terpengaruh
- [daftar dependensi]

### Tidak Ada yang Dihapus
✅ Semua fitur sebelumnya tetap berfungsi

### Langkah Selanjutnya (Disarankan)
- [next step]
```

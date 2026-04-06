# 09 — Logika Bisnis Payroll
> Standar PJBM | Laravel 13 + PHP 8.4 | v4.1 | 2026-03-23

> 📌 Kontrak bisnis. Perubahan formula WAJIB disetujui stakeholder
> dan update docs ini SEBELUM kode diubah. (Protokol P-PAY-01)

## 1. Konstanta — SEMUA dari `config/payroll.php`
```
late_tolerance_minutes      = 10
overtime.rate_bongkar        = 10000  Rp/jam
overtime.rate_non_bongkar    = 7500   Rp/jam
overtime.meal_allowance      = 10000  Rp flat
overtime.meal_threshold      = 2      jam
overtime.break_minutes       = 30     menit
wallet.minimum_balance       = 0
bpjs.health_employee_rate    = 0.01   (1%)
bpjs.employment_jht_rate     = 0.02   (2%)
bpjs.employment_jp_rate      = 0.01   (1%)
driver_bonus.flat_per_delivery   = 5000    Rp (Phase 7)
driver_bonus.omzet_threshold     = 3000000 Rp (Phase 7)
```

## 2. Absensi 4-Scan

### Urutan Scan
```
clock_in → break_start → break_end → clock_out
```

### Status Evaluasi
| Kondisi | Status | `fingerprint_presence` |
|---------|--------|----------------------|
| Tidak ada clock_in | absent | 0.0 |
| Terlambat ≤ toleransi | present | 1.0 |
| Terlambat > toleransi | late | 1.0 (tetap penuh) |
| Scan < 4 atau tanpa clock_out | half_day | 0.5 |

> `position.time_type = 'flexible'` → skip evaluasi

### Override HRD
```php
$attendance->final_presence = $attendance->manual_presence ?? $attendance->fingerprint_presence;
// Fingerspot upsert: JANGAN overwrite manual_presence yang ada
```

### Jam Kerja Bersih
```php
$break = $att->break_start ? $att->break_start->diffInMinutes($att->break_end) : 0;
$att->worked_hours = round(($att->clock_in->diffInMinutes($att->clock_out) - $break) / 60, 2);
```

## 3. Assignment & Earning Harian
```php
// Assignment aktif untuk tanggal X
$assignment = Assignment::where('user_id', $uid)
    ->where('start_date', '<=', $workDate)
    ->orderBy('start_date','desc')->firstOrFail();

$earningPerDay = $assignment->daily_wage * $attendance->final_presence;
```

## 4. Lembur Bongkar
```php
if ($ot->end_time->lt($ot->start_time)) $ot->end_time = $ot->end_time->addDay();

$gross   = $ot->start_time->diffInMinutes($ot->end_time);
$net     = $ot->is_break_taken ? $gross - config('payroll.overtime.break_minutes') : $gross;
$netH    = $net / 60;
$meal    = $netH > config('payroll.overtime.meal_threshold')
           ? config('payroll.overtime.meal_allowance') : 0;
$earned  = $netH * config('payroll.overtime.rate_bongkar') + $meal;
```

## 5. Lembur Non-Bongkar
```php
if ($ot->end_time->lt($ot->start_time)) $ot->end_time = $ot->end_time->addDay();
$earned = ($ot->start_time->diffInMinutes($ot->end_time) / 60)
        * config('payroll.overtime.rate_non_bongkar');
// Tidak ada potongan, tidak ada uang makan
```

## 6. Dual Ledger Wallet
```
Ledger 'salary' ← gaji dari payroll approval
Ledger 'bonus'  ← komisi CS, bonus supir, insentif
JANGAN campur antar ledger.

Saldo = max(0, opening_balance + SUM(earning) - SUM(payout + adjustment))
```

## 7. Payroll Bulanan
```
gross = SUM(daily_wage × final_presence) + SUM(overtime.earned_amount) + bonus_other
net   = gross - bpjs_health - bpjs_employment - kasbon - deduct_other
→ INSERT wallet_transactions(ledger=salary, type=earning, amount=net)
```

### Status Workflow
```
draft → [HRD approve] → approved → wallet INSERT
      → [Kasir confirm] → paid → CashLedger record
```

## 8. BPJS (Bagian Karyawan)
```php
$health     = $user->use_bpjs_health ? $gross * config('payroll.bpjs.health_employee_rate') : 0;
$employment = $user->use_bpjs_employment
    ? $gross * (config('payroll.bpjs.employment_jht_rate') + config('payroll.bpjs.employment_jp_rate'))
    : 0;
// Bagian perusahaan → cash_ledgers (pengeluaran), bukan potongan karyawan
```

## 9. Fingerspot Sync
```bash
php artisan fingerspot:sync  # Scheduler 15 menit
```
```
FingerspotService::fetchRawLogs()
  → FingerspotBridge::mapToAttendance() (match fingerspot_id → users)
  → AttendanceEvaluationService::evaluate()
  → Attendance::updateOrCreate() — JANGAN overwrite manual_presence
  → FingerspotSyncLog::record()
```

## 10. KOMCS Komisi CS

### Simulasi (Phase 3)
```php
DB::table('komcs_users_sim')->where('email',$user->email)->where('period',$period)->first();
// Error: Log::error('KOMCS Commission Error (SIM): ...')
```

### Production (Phase 7)
```php
DB::connection('komcs')->table('commission_mutations')
    ->where('salesman_email',$user->email)
    ->whereBetween('created_at',[$start,$end])->sum('amount');
```

## 11. Bonus Supir (Phase 7 Backlog)
```
Opsi A: config('payroll.driver_bonus.flat_per_delivery') × DO terkirim hari ini
Opsi B: Bonus jika omzet ≥ config('payroll.driver_bonus.omzet_threshold')
→ WalletService::credit($driver, 'bonus', 'earning', $bonus)
```

## 12. Services & Commands
| Class/Command | Fungsi |
|---|---|
| `AttendanceEvaluationService` | Evaluasi 4-scan → status + multiplier |
| `FingerspotService` + `FingerspotBridge` | Fetch + map raw scan |
| `OvertimeCalculatorService` | Kalkulasi bongkar & non-bongkar |
| `PayrollCalculatorService` | Kalkulasi payroll bulanan |
| `WalletService` | CRUD dual ledger, hitung saldo |
| `CommissionService` | KOMCS simulasi & production |
| `DailyClosingService` | Tutup buku + immutable lock |
| `php artisan fingerspot:sync` | Scheduler 15 menit |
| `php artisan payroll:calculate` | Batch hitung gaji |

*Dokumen ini adalah kontrak bisnis — perubahan butuh persetujuan stakeholder*

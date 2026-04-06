# 02 — Panel HR & Payroll
> `gaji.pjb.my.id` | Role: `hrd`, `superadmin` | Laravel 13 + PHP 8.4 | v4.1

## Route Group
```php
Route::middleware(['auth','subdomain:gaji','role:hrd|superadmin'])->group(function () {
    Route::get('/',                          [HRDashboardController::class,  'index']);
    Route::get('attendance',                 [AttendanceController::class,   'index']);
    Route::patch('attendance/{a}/override',  [AttendanceController::class,   'override']);
    Route::post('attendance/sync-now',       [AttendanceController::class,   'syncNow']);
    Route::resource('overtime',              OvertimeController::class)->except(['show']);
    Route::get('payroll',                    [PayrollController::class,      'index']);
    Route::post('payroll/calculate',         [PayrollController::class,      'calculate']);
    Route::patch('payroll/{p}/approve',      [PayrollController::class,      'approve'])->middleware('permission:payroll.approve');
    Route::resource('kasbon',                KasbonController::class)->except(['edit','update','destroy']);
    Route::patch('kasbon/{k}/approve',       [KasbonController::class,       'approve'])->middleware('permission:kasbon.approve');
    Route::patch('kasbon/{k}/reject',        [KasbonController::class,       'reject']);
    Route::get('reports/attendance/export',  [HRReportController::class,     'exportAttendance']);
    Route::get('reports/overtime/export',    [HRReportController::class,     'exportOvertime']);
});
```

## 1. Absensi 4-Scan

### Sumber & Prioritas
| Jalur | Mekanisme | Prioritas |
|-------|-----------|-----------|
| Fingerspot | `php artisan fingerspot:sync` (Scheduler, 15 menit) | Sekunder |
| Manual HRD | Form override, field `manual_presence` | **Primer — selalu menang** |

### Urutan Scan
```
clock_in → break_start → break_end → clock_out
```

### Status Evaluasi (`AttendanceEvaluationService`)
| Kondisi | Status | `fingerprint_presence` |
|---------|--------|----------------------|
| Tidak ada `clock_in` | `absent` | 0.0 |
| Terlambat ≤ `config('payroll.late_tolerance_minutes')` | `present` | 1.0 |
| Terlambat > toleransi | `late` | 1.0 |
| Scan < 4 atau tanpa `clock_out` | `half_day` | 0.5 |

> `position.time_type = 'flexible'` → skip evaluasi keterlambatan

### Final Presence
```php
$attendance->final_presence = $attendance->manual_presence ?? $attendance->fingerprint_presence;
// Saat upsert fingerspot: JANGAN overwrite manual_presence yang sudah ada
```

### Jam Kerja Bersih
```php
$breakMinutes = $att->break_start ? $att->break_start->diffInMinutes($att->break_end) : 0;
$att->worked_hours = round(($att->clock_in->diffInMinutes($att->clock_out) - $breakMinutes) / 60, 2);
```

## 2. Lembur

### Bongkar
```php
// SEMUA nilai dari config() — JANGAN hardcode
if ($ot->end_time->lt($ot->start_time)) $ot->end_time = $ot->end_time->addDay(); // overnight
$netHours     = ($ot->start_time->diffInMinutes($ot->end_time)
                - ($ot->is_break_taken ? config('payroll.overtime.break_minutes') : 0)) / 60;
$meal         = $netHours > config('payroll.overtime.meal_threshold') ? config('payroll.overtime.meal_allowance') : 0;
$earned       = $netHours * config('payroll.overtime.rate_bongkar') + $meal;
```

### Non-Bongkar
```php
if ($ot->end_time->lt($ot->start_time)) $ot->end_time = $ot->end_time->addDay();
$earned = ($ot->start_time->diffInMinutes($ot->end_time) / 60) * config('payroll.overtime.rate_non_bongkar');
```

## 3. Payroll Calculator

### Assignment Aktif
```php
Assignment::where('user_id', $userId)->where('start_date','<=',$workDate)
          ->orderBy('start_date','desc')->firstOrFail();
```

### Earning Harian
```php
$earningPerDay = $assignment->daily_wage * $attendance->final_presence;
```

### Payroll Bulanan
```
gross = SUM(earning_per_day) + SUM(overtime.earned_amount) + bonus_other
net   = gross − bpjs_health − bpjs_employment − kasbon − deduct_other
```

### Status Workflow
```
draft → (HRD approve) → approved → WalletService::credit(salary,earning,net)
      → (Kasir confirm) → paid → CashLedger record
```

## 4. Kasbon
```
Karyawan ajukan → pending → HRD approve → approved
  → WalletService::debit(salary, adjustment, amount)
  → KasbonWallet.balance += amount
```

## 5. Laporan
- Export rekap kehadiran & lembur via Maatwebsite Excel
- Slip gaji batch PDF via DomPDF

## Vue Pages
```
resources/js/Pages/HR/
├── Dashboard.vue
├── Attendance/{Index,Show}.vue
├── Overtime/{Index,Create,Edit}.vue
├── Payroll/{Index,Calculate,Show,BatchApprove}.vue
├── Kasbon/{Index,Show}.vue
└── Reports/{Attendance,Overtime}.vue
```

## Protokol: P-PAY-01 s/d P-PAY-05, P-QUEUE-02, P-QUEUE-03

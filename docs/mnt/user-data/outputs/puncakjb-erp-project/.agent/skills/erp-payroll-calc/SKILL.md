---
name: erp-payroll-calc
description: >
  Gunakan skill ini untuk semua yang berkaitan dengan kalkulasi payroll ERP Puncak JB:
  earning harian, lembur bongkar/non-bongkar, saldo wallet dual-ledger, kalkulasi BPJS,
  dan potongan kasbon. Skill ini memastikan formula yang digunakan selalu sesuai dengan
  docs/04-LOGIKA-PAYROLL.md dan mengambil nilai dari config/payroll.php.
---

# ERP Payroll Calculator Skill

## Tujuan

Memastikan semua kalkulasi payroll menggunakan formula yang benar
sesuai standar PJBM, dengan nilai dari `config/payroll.php`.

---

## Formula Referensi

### 1. Presence Multiplier

| Status | Multiplier |
|--------|-----------|
| `present` | 1.0 |
| `late` | 1.0 |
| `half_day` | 0.5 |
| `absent` | 0.0 |

```php
$finalPresence = $attendance->manual_presence ?? $attendance->fingerprint_presence;
```

### 2. Assignment Aktif

```php
$assignment = Assignment::where('user_id', $userId)
    ->where('start_date', '<=', $workDate)
    ->orderBy('start_date', 'desc')
    ->first();
```

### 3. Earning Per Hari

```php
$earningPerDay = $assignment->daily_wage * $attendance->final_presence;
```

### 4. Lembur Bongkar

```php
// Semua nilai dari config — JANGAN hardcode
$grossMinutes = $overtime->start_time->diffInMinutes($overtime->end_time);

$netMinutes = $overtime->is_break_taken
    ? $grossMinutes - (config('payroll.overtime.break_minutes'))
    : $grossMinutes;

$netHours = $netMinutes / 60;

$mealAllowance = ($netHours > config('payroll.overtime.meal_threshold'))
    ? config('payroll.overtime.meal_allowance')
    : 0;

$earned = ($netHours * config('payroll.overtime.rate_bongkar')) + $mealAllowance;
```

### 5. Lembur Non-Bongkar

```php
$grossMinutes = $overtime->start_time->diffInMinutes($overtime->end_time);
$netHours = $grossMinutes / 60;
$earned = $netHours * config('payroll.overtime.rate_non_bongkar');
```

### 6. Handle Overnight

```php
if ($overtime->end_time->lt($overtime->start_time)) {
    $overtime->end_time = $overtime->end_time->addDay();
}
```

### 7. Saldo Wallet

```php
$openingBalance = match($ledger) {
    'salary' => $user->opening_salary_balance ?? 0,
    'bonus'  => $user->opening_bonus_balance ?? 0,
};

$totalEarning = WalletTransaction::where('user_id', $userId)
    ->where('ledger', $ledger)
    ->where('type', 'earning')
    ->sum('amount');

$totalDebit = WalletTransaction::where('user_id', $userId)
    ->where('ledger', $ledger)
    ->whereIn('type', ['payout', 'adjustment'])
    ->sum('amount');

$saldo = max(
    config('payroll.wallet.minimum_balance'),
    $openingBalance + $totalEarning - $totalDebit
);
```

### 8. Payroll Bulanan

```php
$grossSalary = $baseEarning + $overtimeEarning + $bonusOther;

$deductBpjsHealth = $user->use_bpjs_health
    ? $grossSalary * config('payroll.bpjs.health_employee_rate')
    : 0;

$deductBpjsEmployment = $user->use_bpjs_employment
    ? $grossSalary * (config('payroll.bpjs.employment_jht_rate') + config('payroll.bpjs.employment_jp_rate'))
    : 0;

$netSalary = $grossSalary - $deductBpjsHealth - $deductBpjsEmployment
           - $deductKasbon - $deductOther;
```

---

## Lokasi Kode

```
app/Services/PayrollCalculatorService.php
app/Services/OvertimeCalculatorService.php
app/Services/WalletService.php
app/Services/AttendanceEvaluationService.php
config/payroll.php
```

---

## Constraints

- JANGAN pernah hardcode angka bisnis (rate, threshold, persentase)
- Selalu gunakan `config('payroll.*')` untuk nilai bisnis
- Saldo wallet minimum adalah `config('payroll.wallet.minimum_balance')` = 0
- Override HRD (`manual_presence`) selalu menang atas fingerspot
- Lembur overnight selalu butuh `addDay()` handling

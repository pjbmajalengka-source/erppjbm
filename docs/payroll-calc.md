# Workflow: Kalkulasi Payroll
# Lokasi: .agent/workflows/payroll-calc.md
# Trigger: /payroll-calc
# Ref: docs/04-LOGIKA-PAYROLL.md

---

## Tujuan

Membantu debug atau implementasi kalkulasi payroll
dengan memastikan semua formula sesuai docs/04-LOGIKA-PAYROLL.md.

## Instruksi

### Langkah 1 — Baca Docs Payroll

WAJIB baca terlebih dahulu:
- `docs/04-LOGIKA-PAYROLL.md` section yang relevan
- `config/payroll.php` untuk semua konstanta

### Langkah 2 — Identifikasi Komponen

Tanyakan ke user atau identifikasi dari konteks:
- Kalkulasi apa? (earning harian, lembur, payroll bulanan, wallet)
- User/periode mana?
- Ada override manual presence?

### Langkah 3 — Verifikasi Formula

Gunakan formula PERSIS dari docs/04-LOGIKA-PAYROLL.md:

**Earning Harian:**
```
assignment_aktif = assignments WHERE user_id = X
                   AND start_date <= work_date
                   ORDER BY start_date DESC LIMIT 1
earning_per_day  = assignment.daily_wage × attendance.final_presence
final_presence   = manual_presence ?? fingerprint_presence
```

**Lembur Bongkar:**
```
net_hours      = gross_hours - (break_minutes/60 jika is_break_taken)
meal_allowance = meal_allowance JIKA net_hours > meal_threshold ELSE 0
earned         = (net_hours × rate_bongkar) + meal_allowance
```

**Lembur Non-Bongkar:**
```
earned = net_hours × rate_non_bongkar
```

**Wallet Saldo:**
```
saldo = max(0, opening_balance + SUM(earning) - SUM(payout + adjustment))
```

### Langkah 4 — Cek Semua Nilai dari Config

Verifikasi tidak ada hardcode:
- `config('payroll.late_tolerance_minutes')`
- `config('payroll.overtime.rate_bongkar')`
- `config('payroll.overtime.rate_non_bongkar')`
- `config('payroll.overtime.meal_allowance')`
- `config('payroll.overtime.meal_threshold')`
- `config('payroll.overtime.break_minutes')`

### Langkah 5 — Tulis/Debug Kode

Implementasi ada di:
- `app/Services/PayrollCalculatorService.php`
- `app/Services/OvertimeCalculatorService.php`
- `app/Services/WalletService.php`

### Langkah 6 — Update Docs Jika Ada Perubahan Formula

Jika ada perubahan aturan bisnis:
- Update `docs/04-LOGIKA-PAYROLL.md` DULU
- Lalu update kode
- Bukan sebaliknya

### Langkah 7 — Laporan

Gunakan format laporan standar dari `.agent/rules/GEMINI.md`

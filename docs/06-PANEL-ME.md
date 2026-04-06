# 06 — Portal Self-Service Karyawan
> `me.pjb.my.id` | Role: Semua | Data: scope milik sendiri | v4.1

## Route Group
```php
// Semua role bisa akses, data dibatasi ke auth()->user()->id
Route::middleware(['auth','subdomain:me'])->group(function () {
    Route::get('/',              [SelfServiceController::class, 'index'])->name('me.dashboard');
    Route::get('payslip',        [SelfServiceController::class, 'payslip'])->name('me.payslip');
    Route::get('payslip/{period}/pdf', [SelfServiceController::class, 'downloadPdf']);
    Route::get('attendance',     [SelfServiceController::class, 'attendance'])->name('me.attendance');
    Route::get('mutasi',         [SelfServiceController::class, 'mutasi'])->name('me.mutasi');
    Route::get('kasbon',         [SelfServiceController::class, 'kasbon'])->name('me.kasbon');
    Route::post('kasbon',        [SelfServiceController::class, 'storeKasbon'])->name('me.kasbon.store');
});
```

## 1. Dashboard Pribadi

### Inertia Props
```php
return Inertia::render('Employee/Dashboard', [
    'wallet_salary'    => $walletService->getBalance($user->id, 'salary'),
    'wallet_bonus'     => $walletService->getBalance($user->id, 'bonus'),
    'commission'       => $user->hasRole('cs')
                          ? $commissionService->getCommission($user, now()->format('Y-m'))
                          : null,
    'today_attendance' => Attendance::where('user_id', $user->id)
                          ->where('work_date', today())->first(),
    'shift'            => $user->shift,
    'recent_mutasi'    => WalletTransaction::where('user_id', $user->id)
                          ->latest()->limit(10)->get(),
]);
```

### Card yang Ditampilkan
| Card | Isi | Role |
|------|-----|------|
| Dompet Gaji | Saldo ledger salary | Semua |
| Dompet Bonus | Saldo ledger bonus | Semua |
| Komisi CS | Komisi bulan ini (KOMCS) | Hanya `cs` |
| Kehadiran Hari Ini | Status + jam masuk/keluar | Semua |

## 2. Mutasi Dompet — `/mutasi`

### Filter
- **Ledger**: Semua / Gaji / Bonus
- **Tipe**: Semua / Masuk (earning) / Pencairan (payout) / Koreksi (adjustment)
- **Bulan**: Month picker

### Tabel Kolom
| Kolom | Sumber |
|-------|--------|
| Keterangan | `wallet_transactions.description` |
| Ledger | `wallet_transactions.ledger` (badge: Gaji / Bonus) |
| Tanggal | `wallet_transactions.created_at` |
| Oleh | `created_by` → `users.name` |
| Nominal | `wallet_transactions.amount` |
| Tipe | `wallet_transactions.type` (warna: hijau/merah/kuning) |

## 3. Slip Gaji Digital

### Tampilan PDF (DomPDF)
```
Header: Logo Puncak JB + periode
Karyawan: nama, NIK, jabatan, cabang
Kehadiran: hadir, terlambat, setengah hari, absen
Lembur: detail jam + tipe + earned
Pendapatan: gaji pokok + lembur + bonus
Potongan: BPJS Kesehatan + Ketenagakerjaan + Kasbon
Gaji Bersih: [NET SALARY]
TTD: Superadmin + cap perusahaan
```

## 4. Rekap Kehadiran Bulan Ini

| Kolom | Sumber |
|-------|--------|
| Tanggal | `work_date` |
| Hari | nama hari (Carbon) |
| Jam Masuk | `clock_in` |
| Jam Keluar | `clock_out` |
| Status | `status` (badge warna) |
| Multiplier | `final_presence` |
| Earning | `assignment.daily_wage × final_presence` |

## 5. Kasbon Self-Service

### Ajukan Kasbon
```php
// Hanya input: amount + description
// Tidak bisa ajukan jika masih ada kasbon pending
// Tidak bisa ajukan melebihi config limit (jika ada)
```

### Tampilan Status
- 🟡 Pending — menunggu persetujuan HRD
- 🟢 Disetujui — sudah dipotong dari gaji berikutnya
- 🔴 Ditolak — lihat catatan penolakan

## Vue Pages
```
resources/js/Pages/Employee/
├── Dashboard.vue        ← 4 card + 10 mutasi terbaru
├── Payslip/
│   ├── Index.vue        ← daftar slip per periode
│   └── Show.vue         ← detail + tombol download PDF
├── Attendance/
│   └── Index.vue        ← kalender + tabel bulan ini
├── Mutasi/
│   └── Index.vue        ← tabel paginasi + filter
└── Kasbon/
    ├── Index.vue        ← saldo + daftar pengajuan
    └── Create.vue       ← form ajukan kasbon
```

## Protokol: P-PAY-04 (saldo tidak negatif), P-PAY-05 (dual ledger terpisah)

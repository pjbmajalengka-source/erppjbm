# 01 — Panel Super Admin
> `admin.pjb.my.id` | Role: `superadmin` | Laravel 13 + PHP 8.4 | v4.1 | 2026-03-23

## Route Group
```php
Route::middleware(['auth','subdomain:admin','role:superadmin'])->group(function () {
    Route::get('/',                           [DashboardController::class,  'index'])->name('admin.dashboard');
    Route::resource('users',                  UserController::class);
    Route::patch('users/{user}/toggle-active',[UserController::class,       'toggleActive']);
    Route::resource('branches',               BranchController::class);
    Route::resource('departments',            DepartmentController::class);
    Route::resource('positions',              PositionController::class);
    Route::resource('shifts',                 ShiftController::class);
    Route::resource('assignments',            AssignmentController::class);
    Route::get('assignments/user/{user}',     [AssignmentController::class, 'byUser']);
    Route::get('analytics',                   [AnalyticsController::class,  'index']);
    Route::get('analytics/export',            [AnalyticsController::class,  'export']);
});
```

## 1. Dashboard HQ

**Inertia props:**
```php
return Inertia::render('SuperAdmin/Dashboard', [
    'stats' => [
        'active_employees'  => User::active()->count(),
        'attendance_today'  => Attendance::today()->count(),
        'kasbon_outstanding'=> KasbonWallet::sum('balance'),
        'payroll_drafts'    => PayrollRecord::draft()->count(),
        'kasbon_pending'    => Kasbon::pending()->count(),
    ],
    'recent_attendances' => Attendance::today()
        ->with(['user:id,name','branch:id,name'])->latest()->limit(10)->get(),
    'branches' => Branch::active()->withCount('activeUsers')->get(),
]);
```

**Widget yang ditampilkan:**

| Widget | Sumber | Refresh |
|--------|--------|---------|
| Total karyawan aktif per cabang | `users WHERE is_active=true GROUP BY branch_id` | On load |
| Total absensi hari ini | `attendances WHERE work_date=today` | On load |
| 10 absensi terbaru | `attendances ORDER BY created_at DESC LIMIT 10` | Polling 30 detik |
| Total kasbon beredar | `SUM(kasbon_wallets.balance)` | On load |
| Antrian kasbon pending | `kasbons WHERE status=pending COUNT` | On load |
| Antrian payroll draft | `payroll_records WHERE status=draft COUNT` | On load |

## 2. Manajemen Karyawan

**Field kunci yang dikelola Admin:**

| Field | Tipe | Catatan |
|-------|------|---------|
| `fingerspot_id` | string | Mapping ke mesin absen |
| `salesman_id` | string | Mapping ke iPOS/KOMCS |
| `role` | enum | 7 nilai: superadmin,hrd,cs,supir,kasir,gudang,karyawan |
| `branch_id` | FK | Cabang tempat bertugas |
| `position_id` | FK | Jabatan (time_type: fixed/flexible) |
| `shift_id` | FK | Shift default |
| `use_bpjs_health` | boolean | Flag potongan BPJS Kesehatan |
| `use_bpjs_employment` | boolean | Flag potongan BPJS Ketenagakerjaan |
| `opening_salary_balance` | decimal | Saldo awal migrasi — isi sekali saja |
| `opening_bonus_balance` | decimal | Saldo awal migrasi — isi sekali saja |
| `is_active` | boolean | Nonaktif = false, bukan hard delete |

**Aturan:**
- Nonaktif = `is_active = false` + `softDelete()` — **JANGAN hard delete** jika ada riwayat transaksi
- Assign role via Spatie: `$user->syncRoles([$role])`
- `opening_*_balance` hanya diisi saat migrasi dari sistem lama

**FormRequests:**
```
app/Http/Requests/SuperAdmin/StoreUserRequest.php
app/Http/Requests/SuperAdmin/UpdateUserRequest.php
```

## 3. Master Data

### Positions
```
time_type:
  fixed    → dievaluasi keterlambatan oleh AttendanceEvaluationService
  flexible → dikecualikan dari evaluasi keterlambatan
```

### Shifts
```
Fields: name, clock_in (time), clock_out (time)
Toleransi terlambat: config('payroll.late_tolerance_minutes') = 10 menit
```

## 4. Penugasan Gaji (Assignments)

```
Assignment aktif untuk tanggal X:
  WHERE user_id = X AND start_date <= X
  ORDER BY start_date DESC LIMIT 1

Upah berubah = buat assignment BARU (jangan edit yang lama)
Riwayat assignment tampil chronological per karyawan
```

**Service:** `PayrollCalculatorService::getActiveAssignment()`

## 5. Audit Log & Analitik

**Events yang otomatis dicatat (Spatie ActivityLog):**
- User created/updated/deleted
- Assignment created
- Payroll approved/rejected
- DailyClosing executed
- Manual presence override
- Kasbon approved/rejected

**Grafik:**

| Grafik | Data | Periode |
|--------|------|---------|
| Omzet per CS | `sales_transactions GROUP BY salesman_id` | Harian/Mingguan/Bulanan |
| Tren pengeluaran | `operational_expenses GROUP BY category,date` | Bulanan |
| Rasio kehadiran | `attendances GROUP BY status` | Mingguan/Bulanan |
| Total payroll | `payroll_records WHERE status=paid SUM(net_salary)` | Bulanan |

**Export:** Maatwebsite Excel untuk rekap kehadiran + payroll summary

## Vue Pages
```
resources/js/Pages/SuperAdmin/
├── Dashboard.vue
├── Users/{Index,Create,Edit}.vue
├── Branches/Form.vue
├── Departments/Index.vue
├── Positions/Index.vue
├── Shifts/Index.vue
├── Assignments/{Index,Create}.vue
└── Analytics/Index.vue
```

## Protokol yang Berlaku
| Kode | Deskripsi |
|------|-----------|
| P-DB-04 | Soft delete — tidak hard delete karyawan beriwayat |
| P-DB-05 | Branch scoping (superadmin bypass) |
| P-AUTH-02 | Spatie Permission untuk role check |
| P-AUTH-03 | Permission granular (payroll.approve, kasbon.approve, dll) |

*Bagian dari: ERP Puncak JB docs | Laravel 13 + PHP 8.4*

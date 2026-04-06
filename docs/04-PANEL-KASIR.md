# 04 — Panel Kasir / Akuntansi
> `kasir.pjb.my.id` | Role: `kasir`, `superadmin` | Laravel 13 + PHP 8.4 | v4.1

## Route Group
```php
Route::middleware(['auth','subdomain:kasir','role:kasir|superadmin'])->group(function () {
    Route::get('/',                          [AccountingDashboardController::class, 'index']);
    Route::resource('expenses',              ExpenseController::class)->except(['show']);
    Route::get('cash-ledger',                [CashLedgerController::class,          'index']);
    Route::get('reconciliation',             [ReconciliationController::class,       'index']);
    Route::post('reconciliation/confirm',    [ReconciliationController::class,       'confirm']);
    Route::post('daily-closing',             [DailyClosingController::class,         'execute'])
         ->middleware('permission:closing.execute');
    Route::get('daily-closing/history',      [DailyClosingController::class,         'history']);
    Route::get('payroll-transfers',          [PayrollTransferController::class,       'index']);
    Route::patch('payroll-transfers/{p}/confirm', [PayrollTransferController::class, 'confirm']);
    Route::get('reports/cashbook',           [AccountingReportController::class,      'cashbook']);
    Route::get('reports/cashbook/export',    [AccountingReportController::class,      'export']);
});
```

## 1. Input Pengeluaran Operasional

### FormRequest: `StoreExpenseRequest`
```php
// Validasi wajib
'category' => 'required|in:bahan_baku,operasional,transportasi,gaji_harian,utilitas,lain_lain',
'amount'   => 'required|numeric|min:1',
'description' => 'required|string|max:255',
'expense_date' => 'required|date',
'receipt_photo' => 'nullable|image|max:2048', // via Spatie MediaLibrary
```

### Penguncian Otomatis
```php
// operational_expenses.is_locked = true setelah DailyClosing dieksekusi
// TIDAK BISA diedit setelah itu
// Middleware EnsureClosingNotLocked::class pada route edit/update
```

## 2. Rekonsiliasi Kas
```
Omzet iPOS      = SUM(sales_transactions.net_amount) hari ini
Setoran Tunai   = input manual kasir
Selisih         = Omzet iPOS - Setoran Tunai

Status: belum_direkonsiliasi → dikonfirmasi
Konfirmasi WAJIB sebelum bisa tutup buku
```

## 3. Payroll Transfer
```
Saat HR approve payroll:
  → Event PayrollApproved
  → Notifikasi muncul di panel kasir
Kasir konfirmasi:
  → payroll_records.status = 'paid'
  → CashLedger INSERT (type='payroll', amount=net_salary)
```

## 4. Tutup Buku Harian — `DailyClosingService`

### Prasyarat (dicek sebelum execute)
- [ ] Rekonsiliasi kas sudah dikonfirmasi
- [ ] Tidak ada pending payroll transfer

### Proses Eksekusi
```php
// DailyClosingService::execute(Branch $branch, Carbon $date)

// 1. Cek rekonsiliasi sudah dikonfirmasi
// 2. Hitung komponen
$closingBalance = $openingBalance
                + $totalRevenueIpos
                - $totalExpenses
                - $totalPayroll;

// 3. INSERT daily_closings
DailyClosing::create([
    'branch_id'          => $branch->id,
    'closing_date'       => $date,
    'opening_balance'    => $openingBalance,
    'total_revenue_ipos' => $totalRevenueIpos,
    'total_cash_deposit' => $totalCashDeposit,
    'total_expenses'     => $totalExpenses,
    'total_payroll'      => $totalPayroll,
    'closing_balance'    => $closingBalance,
    'discrepancy'        => $totalRevenueIpos - $totalCashDeposit,
    'is_locked'          => true,
    'locked_by'          => auth()->id(),
    'locked_at'          => now(),
]);

// 4. Lock semua operational_expenses hari ini
OperationalExpense::where('branch_id', $branch->id)
    ->where('expense_date', $date)
    ->update(['is_locked' => true]);

// 5. Dispatch notifikasi
SendClosingNotificationJob::dispatch($branch, $date)->onQueue('critical');
```

### ⚠️ IMMUTABLE — PostgreSQL Trigger
```sql
-- TIDAK BOLEH DIHAPUS ATAU DIUBAH
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

## 5. Laporan Kas Besar
- Buku kas harian: kronologi semua transaksi masuk/keluar
- Rekap bulanan: pemasukan, pengeluaran per kategori, net cash flow
- Export Excel via Maatwebsite, PDF via DomPDF

## Vue Pages
```
resources/js/Pages/Accounting/
├── Dashboard.vue
├── Expenses/{Index,Create,Edit}.vue
├── CashLedger/Index.vue
├── Reconciliation/Index.vue
├── DailyClosing/
│   ├── Execute.vue     ← tombol tutup buku + prasyarat checklist
│   └── History.vue
├── PayrollTransfers/Index.vue
└── Reports/Cashbook.vue
```

## Protokol: P-DB-03 (trigger immutable), P-QUEUE-01 (critical queue)

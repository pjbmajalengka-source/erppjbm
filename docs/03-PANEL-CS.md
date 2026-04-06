# 03 — Panel CS & Komisi
> `cs.pjb.my.id` | Role: `cs`, `superadmin` | Laravel 13 + PHP 8.4 | v4.1

## Route Group
```php
Route::middleware(['auth','subdomain:cs','role:cs|superadmin'])->group(function () {
    Route::get('/',                 [CSDashboardController::class, 'index']);
    Route::get('leaderboard',       [CommissionController::class,  'leaderboard'])->name('cs.leaderboard');
    Route::get('my-commission',     [CommissionController::class,  'myCommission'])->name('cs.my-commission');
    Route::get('commission/history',[CommissionController::class,  'history']);
    Route::get('wallet/bonus',      [WalletController::class,      'bonus'])->name('cs.wallet.bonus');
    Route::get('wallet/mutasi',     [WalletController::class,      'mutasi']);
});
```

## 1. Live Leaderboard

### Mekanisme Real-Time
```php
// Event: LeaderboardUpdated
// Channel: cs-leaderboard (public)
// Broadcast: Redis pub/sub via Laravel Echo
// Trigger: UpdateLeaderboardCacheJob (Scheduler 5 menit)
//          + setiap kali n8n WF-SYNC-01 push transaksi baru
```

### Data yang Ditampilkan
| Kolom | Sumber |
|-------|--------|
| Rank | Urutan berdasar omzet |
| Nama CS | `users.name` via `salesman_id` → `users.salesman_id` |
| Total Omzet Hari Ini | `SUM(sales_transactions.net_amount)` |
| Jumlah Transaksi | `COUNT(sales_transactions)` |
| % Target | `(omzet / target_harian) * 100` — jika target dikonfigurasi |

### Filter
- Hari ini / Minggu ini / Bulan ini

### Vue Component
```vue
<!-- Pages/CS/Leaderboard.vue -->
<!-- Subscribe via Laravel Echo -->
onMounted(() => {
    window.Echo.channel('cs-leaderboard')
        .listen('LeaderboardUpdated', (data) => {
            rankings.value = data.leaderboard
        })
})
```

## 2. Komisi Saya

### Data Scope
```php
// Hanya data milik sendiri via salesman_id
$user = auth()->user();
$transactions = SalesTransaction::where('salesman_id', $user->salesman_id)
    ->whereMonth('transaction_at', $period)->get();
```

### Wallet Bonus
- Komisi masuk ke `wallet_transactions` (ledger=bonus, type=earning)
- Setelah KOMCS Phase 7 aktif: `CommissionService::getCommissionProduction()`

## 3. KOMCS Integration

### Phase 3 — Simulasi (sekarang)
```php
// CommissionService::getCommission()
try {
    $data = DB::table('komcs_users_sim')
        ->where('email', $user->email)
        ->where('period', $period)->first();
    return $data ? (float) $data->commission_amount : 0.0;
} catch (\Throwable $e) {
    Log::error('KOMCS Commission Error (SIM): ' . $e->getMessage(), [
        'user_id' => $user->id, 'period' => $period,
    ]);
    return 0.0;
}
```

### Phase 7 — Production
```php
// Aktifkan dengan menambahkan koneksi 'komcs' di config/database.php
// dan KOMCS_DB_* di .env
DB::connection('komcs')->table('commission_mutations')
    ->where('salesman_email', $user->email)
    ->whereBetween('created_at', [$start, $end])->sum('amount');
```

## 4. Dompet Bonus
- Saldo bonus tampil di dashboard
- Mutasi: riwayat komisi masuk + pencairan
- Pencairan sesuai `payout_preference` karyawan

## Vue Pages
```
resources/js/Pages/CS/
├── Dashboard.vue       ← saldo bonus + ringkasan komisi
├── Leaderboard.vue     ← live ranking (WebSocket)
├── Commission/
│   ├── MyCommission.vue ← detail komisi saya
│   └── History.vue
└── Wallet/
    └── Bonus.vue       ← mutasi dompet bonus
```

## Protokol: P-EVENT-01, P-EVENT-02, P-EXT-04

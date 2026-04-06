# 05 — Panel Warehouse & Logistik
> `gudang.pjb.my.id` | Role: `gudang`, `supir`, `superadmin` | Laravel 13 + PHP 8.4 | v4.1

## Route Group
```php
Route::middleware(['auth','subdomain:gudang','role:gudang|supir|superadmin'])->group(function () {
    Route::get('/',                              [WarehouseDashboardController::class, 'index']);

    // Delivery Order — gudang + superadmin saja (supir hanya update status)
    Route::resource('delivery', DeliveryOrderController::class)
         ->except(['destroy'])
         ->middleware('role:gudang|superadmin');

    Route::get('delivery/{delivery}/blind-note', [DeliveryOrderController::class, 'blindNote'])
         ->name('delivery.blind-note');
    Route::post('delivery/{delivery}/assign-driver', [DeliveryOrderController::class, 'assignDriver'])
         ->name('delivery.assign-driver')
         ->middleware('role:gudang|superadmin');

    // Tracking — semua role (supir bisa update status milik sendiri)
    Route::patch('delivery/{delivery}/status',   [TrackingController::class, 'updateStatus'])
         ->name('delivery.status');
    Route::get('my-deliveries',                  [TrackingController::class, 'myDeliveries'])
         ->name('driver.my-deliveries');
});
```

## 1. Delivery Order

### Field Kunci
```
delivery_no        : nomor surat jalan (unique)
ipos_invoice_no    : link ke nota iPOS (dari sync)
customer_name      : nama customer
customer_address   : alamat pengiriman
customer_phone     : untuk WA notifikasi
driver_id          : FK ke users (role=supir)
delivery_date      : tanggal pengiriman
dispatched_at      : timestamp saat status → dikirim
delivered_at       : timestamp saat status → terkirim
status             : pending|dikirim|terkirim|titip_barang
```

### Status Flow
```
pending
  ↓ (gudang assign supir, klik Kirim)
dikirim         → dispatched_at dicatat otomatis
  ↓ (supir klik Terkirim)
terkirim        → delivered_at dicatat otomatis
                → Event DeliveryStatusUpdated
                → SendDeliveryWhatsAppJob → n8n → WA customer
  ATAU
titip_barang    → barang dititipkan (misal penerima tidak ada)
```

## 2. Blind Note — Nota Tanpa Harga

```
Tampilan untuk role gudang dan supir:
  ✅ Nama item
  ✅ Satuan
  ✅ Kuantitas
  ❌ Harga (TIDAK ditampilkan)

Superadmin bisa melihat harga (toggle)

Schema delivery_items:
  TIDAK ADA kolom harga → by design, bukan bug
  item_code, item_name, unit, quantity, notes
```

## 3. Alokasi Supir

```php
// DeliveryOrderController::assignDriver()
$delivery->update(['driver_id' => $request->driver_id]);
// Satu supir bisa bawa beberapa DO sekaligus
// Saat status → dikirim: dispatched_at = now()
```

## 4. Tracking — Update Status Supir

```php
// TrackingController::updateStatus()
// Mobile-friendly (PWA) — supir update dari smartphone

DB::transaction(function () use ($delivery, $status) {
    $delivery->update([
        'status'       => $status,
        'delivered_at' => $status === 'terkirim' ? now() : null,
    ]);

    DeliveryTracking::create([
        'delivery_order_id' => $delivery->id,
        'status'            => $status,
        'updated_by'        => auth()->id(),
    ]);

    if ($status === 'terkirim') {
        DeliveryStatusUpdated::dispatch($delivery);
        // Listener → SendDeliveryWhatsAppJob (queue: critical)
        // → POST ke n8n WF-NOTIF-01
        // → n8n kirim WA ke customer.phone
    }
});
```

## 5. Bonus Supir *(Backlog — Phase 7)*

### Dua Opsi (Menunggu Keputusan Bisnis)

**Opsi A — Flat per Delivery:**
```php
$bonus = config('payroll.driver_bonus.flat_per_delivery') // Rp 5.000
       * $doTerkirimHariIni;
```

**Opsi B — Per Omzet:**
```php
if ($omzetPengiriman >= config('payroll.driver_bonus.omzet_threshold')) { // Rp 3.000.000
    $bonus = $nilaiBonus; // nilai disepakati
}
```

**Alur (kedua opsi):**
```
DeliveryStatusUpdated event
  → CalculateBonusSupirJob (queue: default)
  → BonusSupirService::calculate()
  → WalletService::credit($driver, 'bonus', 'earning', $bonus)
```

## Vue Pages
```
resources/js/Pages/Warehouse/
├── Dashboard.vue
├── Delivery/
│   ├── Index.vue        ← daftar DO + filter status/supir/tanggal
│   ├── Create.vue
│   ├── Edit.vue
│   ├── BlindNote.vue    ← tampilan tanpa harga, print-friendly
│   └── AssignDriver.vue
├── Tracking/
│   ├── MyDeliveries.vue ← khusus supir, update status
│   └── History.vue
└── Dashboard.vue
```

## Protokol: P-EVENT-01 (DeliveryStatusUpdated), P-EXT-01 (n8n integration bus)

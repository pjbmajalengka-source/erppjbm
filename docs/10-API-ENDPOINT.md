# 10 — API Endpoint Contract
> `api.pjb.my.id` | Auth: Sanctum Token | Laravel 13 + PHP 8.4 | v4.1

## Auth

```
Authorization: Bearer [SANCTUM_TOKEN]
Content-Type:  application/json
```

Token dibuat khusus per integrasi (n8n, mobile).
Rotasi token setiap 90 hari.
Ref: https://laravel.com/docs/13.x/sanctum#api-token-authentication

## Rate Limiting

```
Default: 60 req/menit per IP (Cloudflare Firewall Rule)
Burst:   20 req
Endpoint berat (batch): 10 req/menit per token
```

---

## Endpoint untuk n8n

### POST `/sync/ipos-transactions`
Terima data transaksi penjualan dari n8n (iPOS sync).

**Request:**
```json
{
  "transactions": [
    {
      "ipos_invoice_no": "INV-20260323-001",
      "branch_code": "HQ",
      "salesman_id": "CS001",
      "total_amount": 500000,
      "discount": 0,
      "net_amount": 500000,
      "customer_name": "Budi Santoso",
      "customer_phone": "08123456789",
      "transaction_at": "2026-03-23T10:30:00+07:00"
    }
  ]
}
```

**Response:**
```json
{
  "status": "ok",
  "inserted": 1,
  "updated": 0,
  "skipped": 0
}
```

**Behavior:**
- Upsert berdasarkan `ipos_invoice_no`
- Dispatch `UpdateLeaderboardCacheJob` setelah upsert
- Log ke `ipos_sync_logs`

---

### POST `/sync/delivery-webhook`
Trigger notifikasi WA ke customer saat DO berstatus terkirim.

**Request:**
```json
{
  "delivery_order_id": 42,
  "status": "terkirim",
  "driver_id": 15,
  "delivered_at": "2026-03-23T14:00:00+07:00"
}
```

**Response:**
```json
{
  "status": "ok",
  "whatsapp_queued": true
}
```

**Behavior:**
- Update `delivery_orders.status` + `delivered_at`
- Insert `delivery_trackings`
- Dispatch `SendWhatsAppNotificationJob` (queue: critical)

---

### POST `/sync/attendance-fingerprint`
Fallback endpoint jika Artisan command tidak bisa langsung akses mesin.

**Request:**
```json
{
  "logs": [
    {
      "fingerspot_id": "FP001",
      "timestamp": "2026-03-23T07:02:00+07:00",
      "scan_type": "clock_in"
    }
  ]
}
```

**Response:**
```json
{
  "status": "ok",
  "processed": 1,
  "skipped": 0
}
```

---

### GET `/sync/payroll-summary/{period}`
Ambil ringkasan payroll untuk dikirim via WA notifikasi.

**Parameter:** `period` = format `2026-03`

**Response:**
```json
{
  "period": "2026-03",
  "total_employees": 45,
  "total_approved": 40,
  "total_draft": 5,
  "total_net_salary": 75000000,
  "approved_at": "2026-03-25T21:00:00+07:00"
}
```

---

## Endpoint Publik (Leaderboard)

### GET `/v1/leaderboard`
Data leaderboard CS real-time (dari Redis cache).

**Query params:** `period` (today|week|month), `branch_id` (opsional)

**Response:**
```json
{
  "period": "today",
  "updated_at": "2026-03-23T14:05:00+07:00",
  "rankings": [
    {
      "rank": 1,
      "salesman_id": "CS001",
      "name": "Andi Pratama",
      "total_amount": 12500000,
      "transaction_count": 8
    }
  ]
}
```

---

## Endpoint Health Check

### GET `/health`
Status kesehatan sistem (tidak perlu auth).

**Response:**
```json
{
  "status": "ok",
  "database": "ok",
  "redis": "ok",
  "timestamp": "2026-03-23T14:05:00+07:00"
}
```

Dipakai oleh:
- n8n WF-HEALTH (cron 5 menit)
- Cloudflare Health Check (opsional)

---

## Error Response Format

Semua error menggunakan format konsisten:

```json
{
  "status": "error",
  "message": "Deskripsi error",
  "code": "ERROR_CODE",
  "errors": {
    "field": ["validation message"]
  }
}
```

### HTTP Status Codes

| Code | Kondisi |
|------|---------|
| 200 | Sukses |
| 201 | Created |
| 400 | Bad Request / Validasi gagal |
| 401 | Token tidak valid / tidak ada |
| 403 | Tidak punya permission |
| 404 | Resource tidak ditemukan |
| 422 | Unprocessable Entity |
| 429 | Rate limit exceeded |
| 500 | Server error |

---

## Endpoint Mobile (Future)

> Belum diimplementasi — placeholder untuk Phase 7+

```
POST /v1/kasbon/request     → Ajukan kasbon via mobile
GET  /v1/me/payslip/{period} → Download slip gaji
GET  /v1/me/attendance       → Rekap kehadiran
POST /v1/attendance/clock-in → Clock-in via mobile (fallback fingerspot)
```

---

## Route Definition

```php
// routes/api.php
// Ref: https://laravel.com/docs/13.x/sanctum#api-token-authentication

Route::middleware('auth:sanctum')->group(function () {

    // Sync endpoints untuk n8n
    Route::prefix('sync')->group(function () {
        Route::post('ipos-transactions',       [SyncController::class, 'ingestTransactions']);
        Route::post('delivery-webhook',         [SyncController::class, 'deliveryWebhook']);
        Route::post('attendance-fingerprint',   [SyncController::class, 'fingerspotFallback']);
        Route::get('payroll-summary/{period}',  [SyncController::class, 'payrollSummary']);
    });

    // Public data
    Route::get('v1/leaderboard', [LeaderboardController::class, 'index']);
});

// Health check — no auth
Route::get('health', [HealthController::class, 'check']);
```

## Protokol: P-EXT-01 (n8n integration bus), P-EXT-02 (Sanctum token untuk n8n)

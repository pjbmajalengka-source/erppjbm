# Workflow: Buat Modul Baru ERP
# Lokasi: .agent/workflows/new-module.md
# Trigger: /new-module
# Ref: https://antigravity.google/docs (Workflows)

---

## Tujuan

Scaffold modul ERP baru secara lengkap dan konsisten:
Controller, Service, Model, Migration, FormRequest, Route, Vue Page.

## Instruksi

### Langkah 1 — Kumpulkan Informasi

Tanyakan ke user (jika belum ada di instruksi):
- Nama modul? (contoh: `Overtime`, `Kasbon`)
- Panel subdomain yang terkait? (`gaji`, `admin`, dll)
- Role yang boleh akses?
- Tabel database yang dibutuhkan?

### Langkah 2 — Baca Docs

Baca file berikut sebelum membuat apapun:
- `docs/README.md` → keputusan arsitektur
- `docs/01-FITUR-APLIKASI.md` → spesifikasi fitur yang terkait
- `docs/03-SPEK-DAN-DEPENDENSI.md` → struktur folder dan schema

### Langkah 3 — Buat Migration

Format nama: `YYYY_MM_DD_NNNNNN_create_[tabel]_table.php`

Isi migration:
- Semua kolom sesuai spesifikasi docs/
- Foreign key yang tepat dengan `constrained()`
- `timestamps()` dan `softDeletes()` jika perlu
- Jangan lupa index untuk kolom yang sering di-query

### Langkah 4 — Buat Model

Lokasi: `app/Models/[NamaModel].php`

Wajib ada:
- `$fillable` atau `$guarded`
- Relasi Eloquent yang sesuai
- Global scope `branch_id` jika tabel punya branch_id
- Cast yang tepat (decimal, boolean, enum)

### Langkah 5 — Buat Service

Lokasi: `app/Services/[NamaModul]Service.php`

Wajib:
- Constructor injection untuk dependensi
- Semua logika bisnis di sini, BUKAN di controller
- Gunakan `config('payroll.*')` untuk nilai bisnis
- Throw custom exception yang tepat

### Langkah 6 — Buat FormRequest(s)

Lokasi: `app/Http/Requests/[Panel]/[Action][NamaModul]Request.php`

Contoh:
- `app/Http/Requests/HR/StoreOvertimeRequest.php`
- `app/Http/Requests/HR/UpdateOvertimeRequest.php`

### Langkah 7 — Buat Controller

Lokasi: `app/Http/Controllers/[Panel]/[NamaModul]Controller.php`

Controller HANYA boleh:
- Terima `$request` (FormRequest)
- Panggil Service
- Return `Inertia::render()` atau `response()->json()`

### Langkah 8 — Daftarkan Route

Tambahkan ke `routes/web.php` dalam group subdomain yang sesuai:
```php
Route::middleware(['auth', 'subdomain:[panel]', 'role:[roles]'])
    ->group(function () {
        Route::resource('[endpoint]', [NamaModul]Controller::class);
    });
```

### Langkah 9 — Buat Vue Page(s)

Lokasi: `resources/js/Pages/[Panel]/[NamaModul]/`

File yang dibuat:
- `Index.vue` — daftar / tabel
- `Create.vue` — form tambah (jika perlu)
- `Edit.vue` — form edit (jika perlu)

Gunakan komponen shared dari `resources/js/Components/Shared/`

### Langkah 10 — Update Docs

Update file docs/ yang terpengaruh:
- `docs/01-FITUR-APLIKASI.md` → tambahkan spesifikasi fitur baru
- `docs/03-SPEK-DAN-DEPENDENSI.md` → tambahkan schema tabel baru

### Langkah 11 — Laporan

Gunakan format laporan standar dari `.agent/rules/GEMINI.md`

---
name: erp-migration-guard
description: >
  Gunakan skill ini setiap kali ada perubahan schema database: tambah/hapus/ubah kolom,
  buat tabel baru, atau modifikasi relasi. Skill ini memastikan migration dibuat dengan
  benar (tidak edit yang lama), dependensi tidak putus, dan docs diupdate.
---

# ERP Migration Guard Skill

## Tujuan

Melindungi integritas database ERP dari perubahan yang tidak aman.

---

## Instruksi

### Sebelum Membuat Migration

Scan codebase untuk dependensi dari tabel/kolom yang akan diubah:

```bash
# Cari semua referensi kolom di seluruh project
grep -r "kolom_yang_diubah" app/ database/ --include="*.php"

# Cari foreign key ke tabel ini
grep -r "constrained('[nama_tabel]')" database/migrations/

# Cari query yang menggunakan kolom ini
grep -r "'kolom_yang_diubah'" app/Services/ app/Http/
```

### Aturan Penamaan Migration

```
Format: YYYY_MM_DD_NNNNNN_[action]_[target]_[preposition]_[tabel]_table.php

Actions yang valid:
  create    → tabel baru
  add       → tambah kolom
  remove    → hapus kolom
  modify    → ubah tipe/default kolom
  rename    → rename kolom
  drop      → hapus tabel (SANGAT JARANG, butuh persetujuan eksplisit)

Contoh nama:
  2026_03_23_000023_add_manual_note_to_attendances_table.php
  2026_03_23_000024_remove_old_column_from_users_table.php
  2026_03_23_000025_modify_amount_column_in_wallet_transactions_table.php
```

### Template Migration Tambah Kolom

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('[nama_tabel]', function (Blueprint $table) {
            $table->string('[kolom_baru]')->nullable()->after('[kolom_sebelumnya]');
        });
    }

    public function down(): void
    {
        Schema::table('[nama_tabel]', function (Blueprint $table) {
            $table->dropColumn('[kolom_baru]');
        });
    }
};
```

### Tabel yang Punya Constraint Khusus

```
daily_closings → JANGAN hapus kolom is_locked, locked_by, locked_at
                 JANGAN ubah PostgreSQL trigger prevent_closing_edit()
                 Ini adalah immutable lock di level database

wallet_transactions → JANGAN hapus kolom ledger, type, amount
                      Saldo dihitung dari akumulasi baris ini

attendances → JANGAN hapus manual_presence
              manual_presence ?? fingerprint_presence = final_presence
              Jika manual_presence dihapus, override HRD rusak
```

### Setelah Migration Dibuat

1. Preview: `php artisan migrate --pretend`
2. Jika aman: `php artisan migrate`
3. Update `docs/03-SPEK-DAN-DEPENDENSI.md` → schema tabel terkait
4. Update nomor urut migration di docs jika ada migration baru

---

## Constraints

- DILARANG edit file migration yang sudah pernah di-run (`php artisan migrate:status`)
- DILARANG hapus tabel tanpa persetujuan eksplisit user
- SELALU sertakan `down()` method yang bisa rollback
- SELALU tambahkan kolom dengan `->nullable()` atau default value agar tidak break data existing

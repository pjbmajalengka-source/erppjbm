# Workflow: Database Migration Aman
# Lokasi: .agent/workflows/db-migrate.md
# Trigger: /db-migrate
# Ref: https://antigravity.google/docs (Workflows)

---

## Tujuan

Menjalankan atau membuat migration database dengan aman,
memastikan tidak ada data yang rusak dan dependensi tidak putus.

## Instruksi

### Langkah 1 — Tentukan Jenis Tugas

Tanya ke user:
- Buat migration baru, atau jalankan migration yang ada?
- Jika baru: apa yang perlu diubah di schema?

### Langkah 2 — Cek Dependensi (WAJIB sebelum buat migration baru)

Sebelum membuat migration, scan codebase untuk:
- Model yang pakai tabel ini → `app/Models/`
- Foreign key yang menunjuk ke tabel ini → cari `constrained('[tabel]')`
- Query yang hardcode nama kolom → cari di `app/Services/` dan `app/Http/`
- Seeder yang populate tabel ini → `database/seeders/`

Laporkan semua yang ditemukan ke user sebelum lanjut.

### Langkah 3 — DILARANG Edit Migration Lama

Jika schema perlu diubah:
- BUAT migration baru dengan nama deskriptif
- Format: `YYYY_MM_DD_NNNNNN_[add|remove|modify|rename]_[target]_[to|from|in]_[tabel]_table.php`

Contoh nama yang benar:
- `2026_03_23_000023_add_manual_note_to_attendances_table.php`
- `2026_03_23_000024_modify_overtime_rate_column_in_overtimes_table.php`

### Langkah 4 — Isi Migration dengan Aman

Untuk tambah kolom:
```php
Schema::table('tabel', function (Blueprint $table) {
    $table->string('kolom_baru')->nullable()->after('kolom_sebelumnya');
});
```

Untuk hapus kolom (HARUS ada down() yang balik):
```php
public function up(): void {
    Schema::table('tabel', function (Blueprint $table) {
        $table->dropColumn('kolom_lama');
    });
}

public function down(): void {
    Schema::table('tabel', function (Blueprint $table) {
        $table->string('kolom_lama')->nullable();
    });
}
```

### Langkah 5 — Jalankan dengan Preview Dulu

```bash
# Preview dulu sebelum jalankan
php artisan migrate --pretend

# Jika aman, jalankan
php artisan migrate

# Jika ada masalah, rollback
php artisan migrate:rollback
```

### Langkah 6 — Update Schema di Docs

Update `docs/03-SPEK-DAN-DEPENDENSI.md`:
- Tambahkan kolom baru di schema tabel terkait
- Update urutan migration jika ada migration baru
- Catat alasan perubahan di commit message

### Langkah 7 — Laporan

Gunakan format laporan standar dari `.agent/rules/GEMINI.md`

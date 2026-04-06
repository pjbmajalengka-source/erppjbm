# Workflow: Deploy ke HestiaCP
# Lokasi: .agent/workflows/deploy.md
# Trigger: /deploy
# Ref: https://antigravity.google/docs (Workflows)
# Ref: https://hestiacp.com/docs/introduction/getting-started.html

---

## Tujuan

Deploy atau update Laravel 13 ke VM produksi (192.168.100.15)
yang menggunakan HestiaCP dengan benar dan aman.

## Instruksi

### Langkah 1 — Tentukan Jenis Deploy

Tanya ke user:
- Deploy pertama kali, atau update/redeploy?
- Ada migration baru?
- Ada perubahan config/.env?

### Langkah 2 — Pre-Deploy Checklist

Sebelum deploy apapun, verifikasi:

```bash
# 1. Pastikan tidak ada error PHP
php artisan about

# 2. Pastikan semua test lulus (jika ada)
php artisan test

# 3. Pastikan tidak ada migration yang pending
php artisan migrate:status

# 4. Cek koneksi DB dan Redis
php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';"
php artisan tinker --execute="echo Cache::store('redis')->ping() ? 'Redis OK' : 'Redis FAIL';"
```

### Langkah 3 — Pull Kode Terbaru

```bash
cd /home/erpuser/web/pjb.my.id/app

# Pull kode
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Install/build frontend
npm ci
npm run build
```

### Langkah 4 — Jalankan Migration (jika ada)

```bash
# Preview dulu
php artisan migrate --pretend

# Jika aman
php artisan migrate --force
```

### Langkah 5 — Cache Produksi

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Langkah 6 — Restart Services

```bash
# Restart PHP-FPM
systemctl restart php8.4-fpm

# Restart queue workers (via Supervisor)
supervisorctl restart erp-queue-critical:*
supervisorctl restart erp-queue-default:*

# Restart Horizon
supervisorctl restart erp-horizon

# Reload Nginx HestiaCP (tidak perlu restart penuh)
nginx -t && systemctl reload nginx
```

### Langkah 7 — Verifikasi

```bash
# Cek health endpoint
curl -s https://admin.pjb.my.id/health | python3 -m json.tool

# Cek Supervisor status
supervisorctl status

# Cek queue Horizon
php artisan horizon:status
```

### Langkah 8 — Rollback (jika ada masalah)

```bash
# Rollback kode
git revert HEAD --no-edit
git push origin main

# Rollback migration (jika ada)
php artisan migrate:rollback

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Langkah 9 — Laporan

Gunakan format laporan standar dari `.agent/rules/GEMINI.md`
Sertakan: versi yang di-deploy, migration yang dijalankan, downtime (jika ada)

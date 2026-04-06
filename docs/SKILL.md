---
name: erp-hestia-setup
description: >
  Gunakan skill ini ketika user meminta setup, konfigurasi, atau troubleshoot
  HestiaCP untuk proyek ERP Puncak JB. Mencakup: install HestiaCP, setup PHP 8.4,
  upgrade PostgreSQL 16, konfigurasi Nginx internal, setup domain, SSL lokal,
  dan integrasi dengan WireGuard tunnel.
---

# ERP HestiaCP Setup Skill

## Tujuan

Memandu konfigurasi HestiaCP yang sesuai dengan kebutuhan spesifik
ERP Puncak JB: Nginx-only, PHP 8.4, PostgreSQL 16, tanpa mail server,
dengan Supervisor untuk queue Laravel.

---

## Konteks Infrastruktur

```
VM: 192.168.100.15 (Proxmox, Ubuntu 22.04 LTS)
Panel: HestiaCP (latest stable, v1.9+)
Akses publik: via WireGuard WGHUB → Mikrotik DST-NAT → VM :80
SSL: terminate di WGHUB (Nginx), VM hanya terima HTTP
PHP: 8.4-FPM (via ondrej/php PPA)
DB: PostgreSQL 16 (via PGDG repo)
Cache: Redis 7 (via Redis repo)
```

---

## Instruksi Install HestiaCP

### Prasyarat

```bash
# HestiaCP WAJIB diinstall di OS fresh (Ubuntu 22.04 LTS)
# Ref: https://hestiacp.com/docs/introduction/getting-started.html#requirements

# OS yang didukung (per docs resmi HestiaCP v1.9):
# - Debian 11 atau 12
# - Ubuntu 22.04 atau 24.04 LTS

# Jalankan sebagai root
whoami  # harus: root
```

### Command Install untuk ERP

```bash
wget https://raw.githubusercontent.com/hestiacp/hestiacp/release/install/hst-install.sh

# Flag yang sesuai kebutuhan ERP:
# --apache no       → pakai Nginx saja (lebih ringan)
# --phpfpm yes      → PHP-FPM wajib
# --multiphp 8.3,8.4 → install PHP 8.3 dan 8.4 (tip dari docs: comma-separated list)
# --postgresql yes  → install PostgreSQL (akan diupgrade ke v16 manual)
# --mysql no        → tidak butuh MySQL
# --exim no         → tidak butuh mail server
# --dovecot no      → tidak butuh IMAP
# --clamav no       → hemat RAM
# --spamassassin no → hemat RAM
# --named no        → DNS dihandle Cloudflare
# --vsftpd no       → gunakan SFTP via SSH saja
# --iptables yes    → firewall
# --fail2ban yes    → proteksi brute force
# --interactive no  → unattended install
# --port 8083       → port panel HestiaCP

bash hst-install.sh \
  --apache no \
  --phpfpm yes \
  --multiphp '8.3,8.4' \
  --postgresql yes \
  --mysql no \
  --exim no \
  --dovecot no \
  --clamav no \
  --spamassassin no \
  --named no \
  --vsftpd no \
  --iptables yes \
  --fail2ban yes \
  --interactive no \
  --port 8083 \
  --hostname erp.pjb.my.id \
  --email admin@pjb.my.id \
  --password [STRONG_ADMIN_PASSWORD]
```

> CATATAN: `--multiphp '8.3,8.4'` install kedua versi sekaligus
> sesuai tip di dokumentasi HestiaCP v1.9

### Setelah Install Selesai

```bash
# Akses panel HestiaCP
# https://192.168.100.15:8083
# Username: admin
# Password: [yang diset saat install]
```

---

## Setup User & Domain ERP

```bash
# Buat user khusus ERP
v-add-user erpuser [password] admin@pjb.my.id

# Tambah domain utama
v-add-domain erpuser pjb.my.id

# Set PHP 8.4 untuk domain ini
v-change-web-domain-backend-tpl erpuser pjb.my.id PHP-8_4

# Verifikasi
v-list-web-domains erpuser
```

### Struktur Direktori yang Dibuat HestiaCP

```
/home/erpuser/web/pjb.my.id/
├── public_html/     ← web root Nginx (akan di-replace dengan symlink)
├── public_shtml/    ← SSL (tidak dipakai, SSL di WGHUB)
├── private/         ← area privat
├── cgi-bin/         ← tidak dipakai
├── document_errors/ ← custom error pages
├── stats/           ← awstats
└── logs/
    ├── nginx.access.log
    └── nginx.error.log
```

### Deploy Laravel & Setup Symlink

```bash
# Clone repo ke direktori app
cd /home/erpuser/web/pjb.my.id/
git clone [REPO_URL] app

# Symlink public_html → app/public (web root)
rm -rf public_html
ln -s /home/erpuser/web/pjb.my.id/app/public \
      /home/erpuser/web/pjb.my.id/public_html

# Permission
chown -R erpuser:www-data /home/erpuser/web/pjb.my.id/app
chmod -R 775 app/storage app/bootstrap/cache

# Storage link Laravel
cd app && php artisan storage:link
```

---

## Upgrade PostgreSQL ke v16

> HestiaCP v1.9 install PostgreSQL versi default repo Ubuntu (14 atau 15).
> ERP butuh PostgreSQL 16. Lakukan upgrade setelah HestiaCP install.

```bash
# 1. Tambah repo PGDG
curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc \
  | gpg --dearmor -o /usr/share/keyrings/postgresql.gpg

echo "deb [signed-by=/usr/share/keyrings/postgresql.gpg] \
  https://apt.postgresql.org/pub/repos/apt \
  $(lsb_release -cs)-pgdg main" \
  > /etc/apt/sources.list.d/pgdg.list

apt update
apt install postgresql-16 -y

# 2. Cek versi yang terinstall
pg_lsclusters
# Contoh output:
# Ver Cluster Port Status Owner    Data directory
# 14  main    5432 online postgres /var/lib/postgresql/14/main
# 16  main    5433 online postgres /var/lib/postgresql/16/main

# 3. Stop PG versi lama
pg_ctlcluster 14 main stop

# 4. Upgrade cluster
pg_upgradecluster 14 main

# 5. Verifikasi v16 di port 5432
psql -U postgres -c "SELECT version();"

# 6. Hapus versi lama (setelah yakin v16 berjalan)
pg_dropcluster 14 main --stop
apt remove postgresql-14 -y
```

---

## Setup PHP 8.4 Extensions untuk Laravel 13

```bash
# Install ekstensi yang dibutuhkan
apt install \
  php8.4-fpm php8.4-cli \
  php8.4-pgsql \
  php8.4-redis \
  php8.4-gd \
  php8.4-zip \
  php8.4-mbstring \
  php8.4-xml \
  php8.4-curl \
  php8.4-bcmath \
  php8.4-intl \
  php8.4-opcache \
  php8.4-pcntl \
  -y

# Verifikasi
php8.4 -m | grep -E "pgsql|redis|gd|zip|mbstring|xml|curl|bcmath|intl|opcache|pcntl"
```

---

## Konfigurasi Nginx HestiaCP untuk Laravel

HestiaCP auto-generate Nginx config. Kita perlu kustomisasi
untuk menangani semua subdomain `*.pjb.my.id` dan Laravel routing.

```bash
# Lokasi config yang di-generate HestiaCP:
# /etc/nginx/conf.d/domains/pjb.my.id.conf

# Tambahkan custom config:
cat > /etc/nginx/conf.d/domains/pjb.my.id.conf << 'NGINX_CONF'
server {
    listen      80;
    server_name *.pjb.my.id pjb.my.id;

    root  /home/erpuser/web/pjb.my.id/public_html;
    index index.php;

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP 8.4 FPM
    location ~ \.php$ {
        fastcgi_pass  unix:/var/run/php/php8.4-fpm-erpuser.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include       fastcgi_params;
    }

    # Static assets cache
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|svg)$ {
        expires    30d;
        add_header Cache-Control "public, no-transform";
    }

    # Block dotfiles
    location ~ /\.(?!well-known) { deny all; }

    access_log /home/erpuser/web/pjb.my.id/logs/nginx.access.log;
    error_log  /home/erpuser/web/pjb.my.id/logs/nginx.error.log;
}
NGINX_CONF

# Test dan reload
nginx -t && systemctl reload nginx
```

---

## Batasan & Hal yang Perlu Diperhatikan

- HestiaCP v1.9 support Ubuntu 22.04 dan 24.04 LTS
- WAJIB install di OS fresh — jangan install di sistem yang sudah ada aplikasi lain
- HestiaCP tidak support non-LTS Ubuntu
- Jangan gunakan Apache (flag `--apache no`) karena kita pakai Nginx saja
- SSL di VM tidak diperlukan karena sudah terminate di WGHUB
- Jika HestiaCP panel tidak bisa diakses: cek UFW port 8083

---

## Troubleshooting Umum

```bash
# Panel tidak bisa diakses
systemctl status hestia
ufw status | grep 8083

# PHP-FPM tidak jalan
systemctl status php8.4-fpm
ls /var/run/php/php8.4-fpm-erpuser.sock

# PostgreSQL koneksi gagal
systemctl status postgresql@16-main
sudo -u postgres psql -c "\l"

# Nginx error
nginx -t
tail -50 /var/log/nginx/error.log
tail -50 /home/erpuser/web/pjb.my.id/logs/nginx.error.log
```

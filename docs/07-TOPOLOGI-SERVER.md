# 07 — Topologi Jaringan & Konfigurasi Server
> ERP Puncak JB | Versi 3.0 | Terakhir diupdate: 2026-03-23

---

## Daftar Isi

- [Topologi Lengkap](#1-topologi-lengkap)
- [Layer 1 — Cloudflare](#2-layer-1--cloudflare)
- [Layer 2 — WGHUB (Gateway Publik)](#3-layer-2--wghub-gateway-publik)
- [Layer 3 — WireGuard Tunnel](#4-layer-3--wireguard-tunnel)
- [Layer 4 — Mikrotik (NAT & Routing)](#5-layer-4--mikrotik-nat--routing)
- [Layer 5 — Proxmox & VM Produksi](#6-layer-5--proxmox--vm-produksi)
- [Layer 6 — HestiaCP & Laravel](#7-layer-6--hestiacp--laravel)
- [Konfigurasi Nginx WGHUB](#8-konfigurasi-nginx-wghub--ssl-termination)
- [Konfigurasi Nginx HestiaCP](#9-konfigurasi-nginx-hestiacp--internal)
- [SSL — Certbot di WGHUB](#10-ssl--certbot-wildcard-di-wghub)
- [n8n Automation](#11-n8n-automation-workflows)
- [Laravel Scheduler & Queue](#12-laravel-scheduler--queue)
- [Backup Strategy](#13-backup-strategy)
- [Monitoring & Health Check](#14-monitoring--health-check)
- [Checklist Setup Infrastruktur](#15-checklist-setup-infrastruktur)

---

## 1. Topologi Lengkap

```
╔═══════════════════════════════════════════════════════════════╗
║                        INTERNET                               ║
╚═══════════════════════╤═══════════════════════════════════════╝
                        │ HTTPS :443 / :80
                        ▼
╔═══════════════════════════════════════════════════════════════╗
║  CLOUDFLARE                                                   ║
║  ├── DNS Proxy (orange cloud ON)  *.pjb.my.id               ║
║  ├── DDoS Protection + WAF                                    ║
║  ├── SSL Mode: Full (strict)                                  ║
║  └── Origin cert → WGHUB                                      ║
╚═══════════════════════╤═══════════════════════════════════════╝
                        │ HTTPS :443 (CF → WGHUB)
                        ▼
╔═══════════════════════════════════════════════════════════════╗
║  WGHUB — Ubuntu Server (IP Publik)                            ║
║  WireGuard Server IP: 10.40.0.1                               ║
║                                                               ║
║  Nginx Reverse Proxy                                          ║
║  ├── SSL Termination (wildcard *.pjb.my.id)                 ║
║  ├── Certbot + CF DNS Challenge                               ║
║  └── proxy_pass → 10.40.0.11:80 (via WG tunnel)               ║
║                                                               ║
║  n8n (opsional di WGHUB atau di VM)                           ║
╚═══════════════════════╤═══════════════════════════════════════╝
                        │ WireGuard Tunnel (UDP, encrypted)
                        │ 10.40.0.1  ←──────────────────────────┐
                        ▼                                       │
╔══════════════════════════════════════╗                        │
║  MIKROTIK                            ║                        │
║  WireGuard Client IP: 10.40.0.11     ║ ◄──────────────────────┘
║                                      ║
║  ├── WG Interface: 10.40.0.11        ║
║  └── LAN: 192.168.100.0/24           ║
╚══════════════════════╤═══════════════╝
                       │ LAN 192.168.100.x
                       ▼
╔══════════════════════════════════════╗
║  PROXMOX HOST                        ║
║  ├── VM 116 (Prod): 192.168.100.16   ║
║  │    HestiaCP + Laravel 13          ║
║  └── VM 117 (Stag): 192.168.100.17   ║
║       HestiaCP + Laravel 13          ║
╚══════════════════════════════════════╝

                    ┌──────────────────────────────────┐
                    │  T7810 (LAN 192.168.100.x)        │
                    │  ├── Ollama LLM :11434            │
                    │  ├── RAG Service :8080            │
                    │  ├── ChromaDB :8001               │
                    │  ├── Metabase :3001               │
                    │  └── OCR Service :8090            │
                    └──────────────────────────────────┘
                         ↑ read-only ke PostgreSQL VM
```

---

## 2. Layer 1 — Cloudflare

### Konfigurasi DNS

| Type | Name | Value | Proxy |
|------|------|-------|-------|
| A | `pjb.my.id` | `[IP_PUBLIK_WGHUB]` | ✅ Proxied |
| A | `*.pjb.my.id` | `[IP_PUBLIK_WGHUB]` | ✅ Proxied |

### SSL/TLS Settings di Cloudflare Dashboard

```
SSL/TLS → Overview:
  Encryption Mode: Full (strict)
  ← Wajib karena WGHUB pakai cert valid (Certbot)

SSL/TLS → Edge Certificates:
  Always Use HTTPS: ON
  Minimum TLS Version: TLS 1.2
  Opportunistic Encryption: ON
  TLS 1.3: ON

SSL/TLS → Origin Server:
  Authenticated Origin Pulls: ON (opsional, tambah keamanan)
```

### Cloudflare Firewall Rules (Rekomendasi)

```
Rule 1 — Block non-ID traffic (opsional, sesuaikan):
  IF ip.geoip.country != "ID"
  AND not cf.client.bot
  THEN Block

Rule 2 — Rate limit API endpoint:
  IF http.request.uri.path starts_with "/api/"
  AND rate > 100 req/menit per IP
  THEN Block

Rule 3 — Protect n8n:
  IF http.host == "n8n.pjb.my.id"
  AND not ip.src in {[IP_OFFICE] [IP_T7810]}
  THEN Block
```

---

## 3. Layer 2 — WGHUB (Gateway Publik)

### Spesifikasi WGHUB

| Komponen | Rekomendasi |
|----------|-------------|
| **OS** | Ubuntu 22.04 LTS |
| **RAM** | Minimal 1 GB (2 GB lebih nyaman) |
| **CPU** | 1–2 vCPU |
| **Storage** | 20 GB SSD |
| **Peran** | Gateway publik, SSL termination, WireGuard server |

### Software yang Diinstall di WGHUB

```bash
# Update sistem
apt update && apt upgrade -y

# Nginx sebagai reverse proxy
apt install nginx -y

# Certbot + Cloudflare DNS plugin untuk wildcard SSL
apt install certbot python3-certbot-dns-cloudflare -y

# WireGuard
apt install wireguard -y

# UFW Firewall
apt install ufw -y

# (Opsional) n8n jika dijalankan di WGHUB
# apt install nodejs npm -y
```

### UFW Rules WGHUB

```bash
ufw default deny incoming
ufw default allow outgoing

# SSH (ganti port default jika perlu)
ufw allow 22/tcp

# Web traffic dari Cloudflare saja
# Cloudflare IP ranges: https://www.cloudflare.com/ips/
ufw allow from 103.21.244.0/22 to any port 443
ufw allow from 103.22.200.0/22 to any port 443
ufw allow from 103.31.4.0/22 to any port 443
ufw allow from 104.16.0.0/13 to any port 443
ufw allow from 104.24.0.0/14 to any port 443
ufw allow from 108.162.192.0/18 to any port 443
ufw allow from 131.0.72.0/22 to any port 443
ufw allow from 141.101.64.0/18 to any port 443
ufw allow from 162.158.0.0/15 to any port 443
ufw allow from 172.64.0.0/13 to any port 443
ufw allow from 173.245.48.0/20 to any port 443
ufw allow from 188.114.96.0/20 to any port 443
ufw allow from 190.93.240.0/20 to any port 443
ufw allow from 197.234.240.0/22 to any port 443
ufw allow from 198.41.128.0/17 to any port 443
# IPv6 CF ranges juga perlu ditambahkan

# WireGuard
ufw allow 51820/udp

# Subdomain management tool (Agreement 1-6)
# Usage: /usr/local/bin/domain <subdomain> <target_ip> <type>
# Example: /usr/local/bin/domain testadmin 192.168.100.17 laravel

ufw enable
```

---

## 4. Layer 3 — WireGuard Tunnel

### WGHUB — WireGuard Server Config

```ini
# /etc/wireguard/wg0.conf — di WGHUB

[Interface]
Address    = 10.40.0.1/24
ListenPort = 51820
PrivateKey = [WGHUB_PRIVATE_KEY]

# IP forwarding agar bisa forward ke peer
PostUp   = iptables -A FORWARD -i wg0 -j ACCEPT; \
           iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE
PostDown = iptables -D FORWARD -i wg0 -j ACCEPT; \
           iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE

# Mikrotik sebagai peer (client)
[Peer]
PublicKey  = [MIKROTIK_WG_PUBLIC_KEY]
AllowedIPs = 10.40.0.11/32, 192.168.100.0/24
# AllowedIPs mencakup subnet LAN di balik Mikrotik
# sehingga WGHUB bisa route ke 192.168.100.15 langsung
```

```bash
# Enable & start WireGuard di WGHUB
systemctl enable wg-quick@wg0
systemctl start wg-quick@wg0

# Enable IP forwarding
echo "net.ipv4.ip_forward=1" >> /etc/sysctl.conf
sysctl -p
```

### Mikrotik — WireGuard Client Config

```
# Di Mikrotik RouterOS (via Winbox atau terminal)

# 1. Tambah WireGuard interface
/interface wireguard
add name=wg-tunnel listen-port=51820 private-key=[MIKROTIK_PRIVATE_KEY]

# 2. Assign IP ke interface WG
/ip address
add address=10.40.0.11/24 interface=wg-tunnel

# 3. Tambah peer (WGHUB)
/interface wireguard peers
add interface=wg-tunnel \
    public-key=[WGHUB_PUBLIC_KEY] \
    endpoint-address=[IP_PUBLIK_WGHUB] \
    endpoint-port=51820 \
    allowed-address=10.40.0.1/32 \
    persistent-keepalive=25

# 4. Route: traffic dari WGHUB ke 192.168.100.0/24
/ip route
add dst-address=0.0.0.0/0 gateway=10.40.0.1
# Atau lebih spesifik:
add dst-address=10.50.0.0/24 gateway=wg-tunnel

# 5. NAT/DST-NAT: forward port 80 dari WG interface ke VM
/ip firewall nat
add chain=dstnat \
    in-interface=wg-tunnel \
    protocol=tcp \
    dst-port=80 \
    action=dst-nat \
    to-addresses=192.168.100.15 \
    to-ports=80

# 6. Masquerade untuk traffic dari WG ke LAN
add chain=srcnat \
    out-interface=bridge-local \
    action=masquerade
```

### Verifikasi Tunnel

```bash
# Di WGHUB — cek status tunnel
wg show

# Ping ke VM lewat tunnel
ping 192.168.100.15

# Test akses HTTP ke VM
curl -v http://10.40.0.11/
```

---

## 5. Layer 4 — Mikrotik (NAT & Routing)

### Alur Paket Lengkap

```
Request dari CF → WGHUB :443
    ↓ Nginx WGHUB decrypt SSL
    ↓ proxy_pass http://10.40.0.11:80
    ↓ WireGuard tunnel ke Mikrotik 10.40.0.11
    ↓ Mikrotik DST-NAT :80 → 192.168.100.15:80
    ↓ Nginx HestiaCP di VM terima request HTTP
    ↓ PHP-FPM proses Laravel
    ↓ Response balik via jalur sama
```

### Catatan Mikrotik Penting

- `AllowedIPs = 10.40.0.11/32, 192.168.100.0/24` di WireGuard WGHUB memungkinkan WGHUB langsung reach `192.168.100.15`
- Alternatif lebih simple: `proxy_pass http://192.168.100.15:80` langsung dari WGHUB (jika routing sudah benar)
- Pastikan Mikrotik tidak memblok traffic WireGuard UDP 51820

---

## 6. Layer 5 — Proxmox & VM Produksi

### Spesifikasi VM yang Direkomendasikan

| Komponen | Minimum | **Rekomendasi** |
|----------|---------|-----------------|
| **RAM** | 4 GB | **8–16 GB** (dari kapasitas TS150 48GB) |
| **vCPU** | 2 core | **4 core** |
| **Storage** | 40 GB | **100 GB SSD (virtio)** |
| **OS** | Ubuntu 22.04 LTS | **Ubuntu 22.04 LTS** |
| **Network** | VirtIO bridge | VirtIO bridge ke LAN |
| **IP** | 192.168.100.15 | Static IP, set di VM |

### Konfigurasi Proxmox VM

```bash
# Di Proxmox, buat VM dengan:
# - Machine type: q35
# - BIOS: OVMF (UEFI) atau SeaBIOS
# - Disk: VirtIO SCSI, SSD emulation ON
# - Network: VirtIO, bridge vmbr0

# Set static IP di VM (Ubuntu Netplan)
# /etc/netplan/00-installer-config.yaml
network:
  version: 2
  ethernets:
    ens18:
      addresses:
        - 192.168.100.15/24
      gateway4: 192.168.100.1    # gateway = IP Mikrotik LAN
      nameservers:
        addresses:
          - 1.1.1.1
          - 8.8.8.8

# Apply
netplan apply
```

---

## 7. Layer 6 — HestiaCP & Laravel

### Kenapa HestiaCP (Bukan Docker)?

| Aspek | HestiaCP Native | Docker |
|-------|----------------|--------|
| Konsumsi RAM | Lebih ringan | Overhead container |
| Kelola PHP version | Multi-PHP via panel | Dockerfile |
| PostgreSQL | Built-in (perlu upgrade ke v16) | Image terpisah |
| SSL management | Via panel (Let's Encrypt) | Manual / Certbot |
| Deployment Laravel | Git + Composer via SSH | CI/CD pipeline |
| Cocok untuk | Tim kecil, 1 server dedicated | Multi-service, microservice |

### Install HestiaCP di VM

```bash
# Di VM 192.168.100.15 — jalankan sebagai root

# Download installer
wget https://raw.githubusercontent.com/hestiacp/hestiacp/release/install/hst-install.sh

# Install dengan konfigurasi yang sesuai
# --nginx          : pakai Nginx (bukan Apache)
# --phpfpm         : PHP-FPM
# --multiphp       : support multi versi PHP
# --postgresql     : install PostgreSQL
# --no-mail        : skip mail server (tidak perlu)
# --no-dns         : skip DNS server (CF yang handle)
# --no-exim        : skip Exim
# --lang id        : bahasa Indonesia

bash hst-install.sh \
  --nginx yes \
  --phpfpm yes \
  --multiphp yes \
  --postgresql yes \
  --mysql no \
  --exim no \
  --dovecot no \
  --clamav no \
  --spamassassin no \
  --named no \
  --vsftpd no \
  --proftpd no \
  --lang en \
  --port 8083 \
  --hostname erp.pjb.my.id \
  --email admin@pjb.my.id \
  --password [STRONG_ADMIN_PASSWORD]
```

### Upgrade PostgreSQL ke Versi 16

HestiaCP biasanya install PostgreSQL 14/15. Upgrade ke 16:

```bash
# Install PostgreSQL 16 dari repo resmi
curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc \
  | gpg --dearmor -o /usr/share/keyrings/postgresql.gpg

echo "deb [signed-by=/usr/share/keyrings/postgresql.gpg] \
  https://apt.postgresql.org/pub/repos/apt \
  $(lsb_release -cs)-pgdg main" \
  > /etc/apt/sources.list.d/pgdg.list

apt update
apt install postgresql-16 -y

# Stop PostgreSQL lama (misal versi 14)
systemctl stop postgresql@14-main

# Upgrade cluster
pg_upgradecluster 14 main

# Verifikasi
psql --version
# PostgreSQL 16.x

# Set port 5432 untuk versi 16
# Edit /etc/postgresql/16/main/postgresql.conf
# port = 5432

systemctl restart postgresql@16-main
systemctl disable postgresql@14-main  # disable versi lama
```

### Install PHP 8.3 di HestiaCP

```bash
# HestiaCP sudah support multi-PHP
# Tambah PHP 8.3 via HestiaCP CLI

# Install PHP 8.3
apt install php8.3-fpm php8.3-cli php8.3-common \
    php8.3-pgsql php8.3-redis php8.3-gd php8.3-zip \
    php8.3-mbstring php8.3-xml php8.3-curl php8.3-bcmath \
    php8.3-intl php8.3-pcov -y

# Set PHP 8.3 sebagai default untuk domain ERP
# Via HestiaCP panel → Web → Edit domain → PHP Version: 8.3

# Atau via CLI HestiaCP:
v-change-web-domain-backend-tpl admin [domain] PHP-8_3
```

### Install Redis 7

```bash
# Redis dari repo resmi
curl -fsSL https://packages.redis.io/gpg \
  | gpg --dearmor -o /usr/share/keyrings/redis-archive-keyring.gpg

echo "deb [signed-by=/usr/share/keyrings/redis-archive-keyring.gpg] \
  https://packages.redis.io/deb $(lsb_release -cs) main" \
  | tee /etc/apt/sources.list.d/redis.list

apt update
apt install redis -y

# Set password Redis
# Edit /etc/redis/redis.conf
# requirepass [STRONG_REDIS_PASSWORD]
# maxmemory 2gb
# maxmemory-policy allkeys-lru
# bind 127.0.0.1  ← hanya localhost

systemctl enable redis-server
systemctl restart redis-server

# Verifikasi
redis-cli ping
# PONG
```

### Setup Domain di HestiaCP

```bash
# Tambah user HestiaCP untuk ERP
v-add-user erpuser [password] admin@pjb.my.id

# Tambah domain utama (dipakai untuk semua subdomain via Nginx)
v-add-domain erpuser pjb.my.id

# HestiaCP akan membuat:
# /home/erpuser/web/pjb.my.id/public_html/  ← web root
# /home/erpuser/web/pjb.my.id/logs/         ← access + error logs

# Symlink atau setup Laravel di path ini
# (lihat bagian setup Laravel di bawah)
```

### Setup Laravel di HestiaCP

```bash
# SSH ke VM sebagai root atau user erpuser

# Direktori web HestiaCP
cd /home/erpuser/web/pjb.my.id/

# Clone repository ke folder app (bukan public_html langsung)
git clone [REPO_URL] app
cd app

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Setup .env
cp .env.example .env
php artisan key:generate

# Link public_html ke Laravel public/
# HestiaCP web root = public_html
# Laravel public = app/public

# Opsi: symlink
rm -rf /home/erpuser/web/pjb.my.id/public_html
ln -s /home/erpuser/web/pjb.my.id/app/public \
      /home/erpuser/web/pjb.my.id/public_html

# Storage link
php artisan storage:link

# Permission
chown -R erpuser:www-data /home/erpuser/web/pjb.my.id/app
chmod -R 775 storage bootstrap/cache

# Run migrations
php artisan migrate --seed

# Cache produksi
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Setup Database PostgreSQL 16

```bash
# Masuk sebagai postgres user
sudo -u postgres psql

-- Buat database dan user
CREATE DATABASE payroll_pjbm;
CREATE USER erp_user WITH PASSWORD '[STRONG_PASSWORD]';
GRANT ALL PRIVILEGES ON DATABASE payroll_pjbm TO erp_user;
ALTER DATABASE payroll_pjbm OWNER TO erp_user;

-- Buat user read-only untuk T7810 / Metabase
CREATE USER analytics_reader WITH PASSWORD '[STRONG_PASSWORD]';
GRANT CONNECT ON DATABASE payroll_pjbm TO analytics_reader;
GRANT USAGE ON SCHEMA public TO analytics_reader;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO analytics_reader;
ALTER DEFAULT PRIVILEGES IN SCHEMA public
    GRANT SELECT ON TABLES TO analytics_reader;

\q

# Konfigurasi pg_hba.conf untuk terima koneksi dari T7810
# /etc/postgresql/16/main/pg_hba.conf
# Tambahkan:
# host  payroll_pjbm  analytics_reader  [IP_T7810]/32  scram-sha-256
# host  payroll_pjbm  erp_user          127.0.0.1/32   scram-sha-256

systemctl restart postgresql@16-main
```

---

## 8. Konfigurasi Nginx WGHUB — SSL Termination

### Struktur Config

```
/etc/nginx/
├── nginx.conf
├── conf.d/
│   └── puncakjb.conf       ← semua subdomain di sini
└── snippets/
    └── ssl-puncakjb.conf   ← shared SSL config
```

### `/etc/nginx/snippets/ssl-puncakjb.conf`

```nginx
ssl_certificate     /etc/letsencrypt/live/pjb.my.id/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/pjb.my.id/privkey.pem;
ssl_protocols       TLSv1.2 TLSv1.3;
ssl_ciphers         ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
ssl_prefer_server_ciphers off;
ssl_session_cache   shared:SSL:10m;
ssl_session_timeout 1d;

# HSTS (optional, aktifkan jika sudah stabil)
# add_header Strict-Transport-Security "max-age=63072000" always;
```

### `/etc/nginx/conf.d/puncakjb.conf`

```nginx
# ─── HTTP → HTTPS redirect ──────────────────────────────
server {
    listen 80;
    server_name *.pjb.my.id pjb.my.id;
    return 301 https://$host$request_uri;
}

# ─── Upstream VM (via WireGuard tunnel) ─────────────────
upstream erp_vm {
    server 192.168.100.15:80;
    # Alternatif lewat WG IP Mikrotik:
    # server 10.40.0.11:80;
    keepalive 32;
}

# ─── Shared proxy settings ──────────────────────────────
# (dipakai ulang di semua server block)
# proxy_set_header Host              $host;
# proxy_set_header X-Real-IP         $remote_addr;
# proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
# proxy_set_header X-Forwarded-Proto $scheme;
# proxy_read_timeout 60;
# proxy_connect_timeout 60;

# ─── ADMIN ──────────────────────────────────────────────
server {
    listen 443 ssl http2;
    server_name admin.pjb.my.id;
    include snippets/ssl-puncakjb.conf;
    client_max_body_size 20M;

    location / {
        proxy_pass         http://erp_vm;
        proxy_set_header   Host $host;
        proxy_set_header   X-Real-IP $remote_addr;
        proxy_set_header   X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto https;
        proxy_read_timeout 60;
    }

    # WebSocket untuk Laravel Echo
    location /app/ {
        proxy_pass         http://erp_vm;
        proxy_http_version 1.1;
        proxy_set_header   Upgrade $http_upgrade;
        proxy_set_header   Connection "upgrade";
        proxy_set_header   Host $host;
    }
}

# ─── GAJI ───────────────────────────────────────────────
server {
    listen 443 ssl http2;
    server_name gaji.pjb.my.id;
    include snippets/ssl-puncakjb.conf;
    client_max_body_size 20M;
    location / {
        proxy_pass       http://erp_vm;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
    }
    location /app/ {
        proxy_pass         http://erp_vm;
        proxy_http_version 1.1;
        proxy_set_header   Upgrade $http_upgrade;
        proxy_set_header   Connection "upgrade";
        proxy_set_header   Host $host;
    }
}

# ─── CS ─────────────────────────────────────────────────
server {
    listen 443 ssl http2;
    server_name cs.pjb.my.id;
    include snippets/ssl-puncakjb.conf;
    location / {
        proxy_pass       http://erp_vm;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
    }
    location /app/ {
        proxy_pass         http://erp_vm;
        proxy_http_version 1.1;
        proxy_set_header   Upgrade $http_upgrade;
        proxy_set_header   Connection "upgrade";
        proxy_set_header   Host $host;
    }
}

# ─── GUDANG ─────────────────────────────────────────────
server {
    listen 443 ssl http2;
    server_name gudang.pjb.my.id;
    include snippets/ssl-puncakjb.conf;
    client_max_body_size 10M;
    location / {
        proxy_pass       http://erp_vm;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
    }
}

# ─── KASIR ──────────────────────────────────────────────
server {
    listen 443 ssl http2;
    server_name kasir.pjb.my.id;
    include snippets/ssl-puncakjb.conf;
    client_max_body_size 10M;
    location / {
        proxy_pass       http://erp_vm;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
    }
}

# ─── ME (Self-Service) ──────────────────────────────────
server {
    listen 443 ssl http2;
    server_name me.pjb.my.id;
    include snippets/ssl-puncakjb.conf;
    location / {
        proxy_pass       http://erp_vm;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
    }
}

# ─── API ────────────────────────────────────────────────
server {
    listen 443 ssl http2;
    server_name api.pjb.my.id;
    include snippets/ssl-puncakjb.conf;
    client_max_body_size 50M;

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api_limit:10m rate=60r/m;
    limit_req zone=api_limit burst=20 nodelay;

    location / {
        proxy_pass       http://erp_vm;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
    }
}

# ─── N8N (akses terbatas) ───────────────────────────────
server {
    listen 443 ssl http2;
    server_name n8n.pjb.my.id;
    include snippets/ssl-puncakjb.conf;

    # Hanya izinkan akses dari IP tertentu
    allow [IP_KANTOR];
    allow [IP_T7810_PUBLIC_atau_WG];
    deny  all;

    location / {
        proxy_pass         http://erp_vm:5678;
        # n8n di VM port 5678
        proxy_http_version 1.1;
        proxy_set_header   Upgrade $http_upgrade;
        proxy_set_header   Connection "upgrade";
        proxy_set_header   Host $host;
        proxy_set_header   X-Real-IP $remote_addr;
    }
}
```

### Penting: `X-Forwarded-Proto` untuk Laravel

Karena SSL terminate di WGHUB dan VM menerima HTTP biasa, Laravel harus tahu bahwa request aslinya HTTPS. Tambahkan di `app/Http/Middleware/TrustProxies.php`:

```php
// app/Http/Middleware/TrustProxies.php
class TrustProxies extends Middleware
{
    // Trust semua proxy (karena lewat WireGuard)
    // Atau spesifikkan IP WGHUB:
    protected $proxies = '*';

    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
```

---

## 9. Konfigurasi Nginx HestiaCP — Internal

HestiaCP otomatis generate config Nginx untuk setiap domain. Kita perlu kustomisasi untuk menangani **semua subdomain** dalam satu Laravel app.

### Template Nginx HestiaCP untuk ERP

```bash
# Lokasi template custom HestiaCP:
# /etc/nginx/conf.d/domains/[domain].conf
# HestiaCP generate ini otomatis, tapi kita tambah include custom

# Buat file custom untuk subdomain handling:
# /etc/nginx/conf.d/domains/pjb.my.id.conf (auto-generated)
# Tambah /etc/nginx/conf.d/domains/pjb.my.id.ssl.conf jika ada SSL lokal
```

Karena SSL sudah di-terminate di WGHUB, config Nginx di VM cukup sederhana — terima HTTP dan arahkan ke Laravel:

```nginx
# /etc/nginx/conf.d/domains/pjb.my.id.conf
# (atau generate via HestiaCP panel, lalu tambah customization)

server {
    listen      80;
    # Terima semua subdomain *.pjb.my.id yang diteruskan WGHUB
    server_name *.pjb.my.id pjb.my.id;

    root        /home/erpuser/web/pjb.my.id/public_html;
    index       index.php;

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass   unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include        fastcgi_params;

        # Forward real IP dan proto dari WGHUB
        fastcgi_param  HTTP_X_FORWARDED_PROTO $http_x_forwarded_proto;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2)$ {
        expires     30d;
        add_header  Cache-Control "public, no-transform";
    }

    # Larangan akses ke file sensitif
    location ~ /\.(?!well-known) {
        deny all;
    }

    access_log  /home/erpuser/web/pjb.my.id/logs/nginx.access.log;
    error_log   /home/erpuser/web/pjb.my.id/logs/nginx.error.log;
}
```

---

## 10. SSL — Certbot Wildcard di WGHUB

```bash
# Install di WGHUB
apt install certbot python3-certbot-dns-cloudflare -y

# Cloudflare API credentials
mkdir -p /root/.cloudflare
cat > /root/.cloudflare/credentials.ini << 'EOF'
dns_cloudflare_api_token = [CLOUDFLARE_API_TOKEN]
EOF
chmod 600 /root/.cloudflare/credentials.ini

# Request wildcard certificate
certbot certonly \
  --dns-cloudflare \
  --dns-cloudflare-credentials /root/.cloudflare/credentials.ini \
  --dns-cloudflare-propagation-seconds 30 \
  -d "pjb.my.id" \
  -d "*.pjb.my.id" \
  --agree-tos \
  --email admin@pjb.my.id

# Verifikasi cert
ls /etc/letsencrypt/live/pjb.my.id/
# cert.pem  chain.pem  fullchain.pem  privkey.pem

# Auto-renew sudah dihandle systemd timer certbot
# Verifikasi:
systemctl status certbot.timer

# Reload Nginx setelah renew
# Tambah post-hook:
cat > /etc/letsencrypt/renewal-hooks/post/reload-nginx.sh << 'EOF'
#!/bin/bash
systemctl reload nginx
EOF
chmod +x /etc/letsencrypt/renewal-hooks/post/reload-nginx.sh
```

---

## 11. n8n Automation Workflows

### Opsi Penempatan n8n

| Opsi | Lokasi | Pro | Con |
|------|--------|-----|-----|
| **A** | VM 192.168.100.15 (bersama Laravel) | Simple, 1 server | Berbagi resource |
| **B** | WGHUB | Terpisah, tidak bebani VM | RAM WGHUB terbatas |
| **C** | VM terpisah di Proxmox | Isolasi penuh | Perlu VM baru |

**Rekomendasi: Opsi A** — Install n8n di VM yang sama, port 5678, proxy via Nginx WGHUB ke `n8n.pjb.my.id`.

### Install n8n di VM

```bash
# Install Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install nodejs -y

# Install n8n global
npm install -g n8n

# Setup sebagai systemd service
cat > /etc/systemd/system/n8n.service << 'EOF'
[Unit]
Description=n8n Automation
After=network.target postgresql.service

[Service]
Type=simple
User=erpuser
Environment=N8N_HOST=n8n.pjb.my.id
Environment=N8N_PORT=5678
Environment=N8N_PROTOCOL=https
Environment=WEBHOOK_URL=https://n8n.pjb.my.id/
Environment=DB_TYPE=postgresdb
Environment=DB_POSTGRESDB_HOST=localhost
Environment=DB_POSTGRESDB_PORT=5432
Environment=DB_POSTGRESDB_DATABASE=n8n_db
Environment=DB_POSTGRESDB_USER=erp_user
Environment=DB_POSTGRESDB_PASSWORD=[PASSWORD]
Environment=N8N_ENCRYPTION_KEY=[RANDOM_32_CHARS]
ExecStart=/usr/bin/n8n start
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

# Buat database n8n
sudo -u postgres psql -c "CREATE DATABASE n8n_db OWNER erp_user;"

systemctl daemon-reload
systemctl enable n8n
systemctl start n8n
```

### Workflows

| Workflow | Trigger | Aksi |
|----------|---------|------|
| WF-SYNC-01 | Cron 15 menit | Tarik transaksi iPOS → POST ke api.pjb.my.id |
| WF-NOTIF-01 | Webhook dari Laravel | Kirim WA ke customer saat delivery terkirim |
| WF-NOTIF-02 | Webhook dari Laravel | Kirim WA ke karyawan saat payroll approved |
| WF-BOT-01 | Incoming WA | RAG chatbot CS via T7810 Ollama |
| WF-CLOSING | Cron 22:30 | Reminder tutup buku ke kasir & superadmin |
| WF-HEALTH | Cron 5 menit | Monitor health check, alert WA jika down |

---

## 12. Laravel Scheduler & Queue

### Setup Supervisor di VM

```bash
apt install supervisor -y

# Worker untuk queue critical (payroll, WA notif)
cat > /etc/supervisor/conf.d/erp-queue-critical.conf << 'EOF'
[program:erp-queue-critical]
process_name=%(program_name)s_%(process_num)02d
command=php /home/erpuser/web/pjb.my.id/app/artisan queue:work redis --queue=critical --sleep=1 --tries=3 --max-time=3600
directory=/home/erpuser/web/pjb.my.id/app
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=erpuser
numprocs=2
redirect_stderr=true
stdout_logfile=/home/erpuser/web/pjb.my.id/logs/queue-critical.log
EOF

# Worker untuk queue default (sync, leaderboard)
cat > /etc/supervisor/conf.d/erp-queue-default.conf << 'EOF'
[program:erp-queue-default]
process_name=%(program_name)s_%(process_num)02d
command=php /home/erpuser/web/pjb.my.id/app/artisan queue:work redis --queue=default,low --sleep=3 --tries=3 --max-time=3600
directory=/home/erpuser/web/pjb.my.id/app
autostart=true
autorestart=true
user=erpuser
numprocs=1
redirect_stderr=true
stdout_logfile=/home/erpuser/web/pjb.my.id/logs/queue-default.log
EOF

# Scheduler (cron Laravel)
cat > /etc/supervisor/conf.d/erp-scheduler.conf << 'EOF'
[program:erp-scheduler]
command=php /home/erpuser/web/pjb.my.id/app/artisan schedule:work
directory=/home/erpuser/web/pjb.my.id/app
autostart=true
autorestart=true
user=erpuser
numprocs=1
redirect_stderr=true
stdout_logfile=/home/erpuser/web/pjb.my.id/logs/scheduler.log
EOF

# Laravel Horizon (queue monitoring)
cat > /etc/supervisor/conf.d/erp-horizon.conf << 'EOF'
[program:erp-horizon]
command=php /home/erpuser/web/pjb.my.id/app/artisan horizon
directory=/home/erpuser/web/pjb.my.id/app
autostart=true
autorestart=true
user=erpuser
numprocs=1
redirect_stderr=true
stdout_logfile=/home/erpuser/web/pjb.my.id/logs/horizon.log
EOF

supervisorctl reread
supervisorctl update
supervisorctl start all
```

### `routes/console.php`

```php
use Illuminate\Support\Facades\Schedule;

// Fingerspot sync setiap 15 menit
Schedule::command('fingerspot:sync')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Update leaderboard CS setiap 5 menit
Schedule::job(new UpdateLeaderboardCacheJob)
    ->everyFiveMinutes()
    ->onOneServer();

// Backup database jam 02:00
Schedule::command('backup:run --only-db')
    ->dailyAt('02:00')
    ->onOneServer();

// Prune Telescope log (simpan 48 jam)
Schedule::command('telescope:prune --hours=48')
    ->daily();

// Cleanup PDF slip gaji lama (> 6 bulan)
Schedule::command('storage:cleanup-old-payslips')
    ->monthly();
```

---

## 13. Backup Strategy

| Data | Frekuensi | Tujuan | Retensi |
|------|-----------|--------|---------|
| PostgreSQL `payroll_pjbm` dump | Harian 02:00 | `/backup/` di VM + NAS | 30 hari |
| Redis RDB snapshot | Setiap jam | `/var/lib/redis/` | 7 hari |
| Storage Laravel (PDF, foto) | Harian | NAS lokal | 90 hari |
| n8n workflows export | Mingguan | Git repo private | Selamanya |
| `.env` & Nginx config | Manual saat berubah | Git private + NAS | Selamanya |

```bash
# Script backup di VM — /scripts/backup-db.sh
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR=/backup/postgres

mkdir -p $BACKUP_DIR

pg_dump -U erp_user payroll_pjbm \
  | gzip > $BACKUP_DIR/payroll_pjbm_$DATE.sql.gz

# Hapus backup > 30 hari
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

echo "Backup selesai: $DATE"
```

---

## 14. Monitoring & Health Check

### Endpoint Health di Laravel

```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status'    => 'ok',
        'database'  => DB::connection()->getPdo() ? 'ok' : 'error',
        'redis'     => Cache::store('redis')->ping() ? 'ok' : 'error',
        'timestamp' => now()->toIso8601String(),
    ]);
});
```

### n8n WF-HEALTH — Alert WA

```
Cron setiap 5 menit
    → GET https://admin.pjb.my.id/health
    → IF status != 200 (retry 3x dengan jeda 1 menit)
    → Kirim WA ke superadmin:
      "⚠️ ALERT [timestamp]: ERP tidak merespons!
       Cek VM 192.168.100.15 atau tunnel WireGuard."
```

### Cek Status WireGuard (di WGHUB)

```bash
# Cek status tunnel
wg show

# Cek konektivitas ke VM
ping -c 3 192.168.100.15

# Cek apakah Nginx bisa reach VM
curl -I http://192.168.100.15/health
```

---

## 15. Checklist Setup Infrastruktur

### WGHUB — Setup Awal

- [ ] Install Ubuntu 22.04 LTS, update sistem
- [ ] Install: Nginx, WireGuard, Certbot + CF plugin, UFW
- [ ] Generate WireGuard keypair: `wg genkey | tee private.key | wg pubkey > public.key`
- [ ] Setup `/etc/wireguard/wg0.conf` dengan peer Mikrotik
- [ ] Enable IP forwarding: `net.ipv4.ip_forward=1`
- [ ] `systemctl enable --now wg-quick@wg0`
- [ ] Setup UFW: allow CF IPs only on :443, allow WG :51820/udp
- [ ] Request wildcard SSL: `certbot certonly --dns-cloudflare ...`
- [ ] Setup Nginx `/etc/nginx/conf.d/puncakjb.conf` (semua subdomain)
- [ ] Tambah `TrustProxies` middleware di Laravel
- [ ] `nginx -t && systemctl reload nginx`

### Mikrotik — Setup WireGuard & NAT

- [ ] Tambah WireGuard interface dengan private key
- [ ] Assign IP `10.40.0.11/24` ke WG interface
- [ ] Tambah peer WGHUB (public key, endpoint, allowed-address)
- [ ] Set persistent-keepalive=25
- [ ] Tambah DST-NAT rule: WG interface :80 → 192.168.100.15:80
- [ ] Tambah masquerade untuk traffic WG ke LAN
- [ ] Test ping dari WGHUB ke VM: `ping 192.168.100.15`

### Proxmox VM — Setup

- [ ] Buat VM: 4 vCPU, 8–16GB RAM, 100GB SSD, Ubuntu 22.04
- [ ] Set static IP 192.168.100.15, gateway 192.168.100.1
- [ ] Install HestiaCP (dengan flag yang sudah ditentukan)
- [ ] Upgrade PostgreSQL ke versi 16
- [ ] Install PHP 8.3 + ekstensi yang dibutuhkan
- [ ] Install Redis 7
- [ ] Setup domain `pjb.my.id` di HestiaCP
- [ ] Clone repo Laravel + `composer install`
- [ ] Setup `.env` (DB, Redis, session domain, fingerspot, WA API)
- [ ] `php artisan migrate --seed`
- [ ] Setup Supervisor (queue, scheduler, horizon)
- [ ] Setup `analytics_reader` PostgreSQL user
- [ ] Install n8n sebagai systemd service
- [ ] Setup Nginx HestiaCP untuk `*.pjb.my.id`

### Cloudflare — DNS Setup

- [ ] A record `pjb.my.id` → IP Publik WGHUB (Proxied ✅)
- [ ] A record `*.pjb.my.id` → IP Publik WGHUB (Proxied ✅)
- [ ] SSL mode: Full (strict)
- [ ] Always Use HTTPS: ON
- [ ] Setup Cloudflare API Token (Zone:DNS:Edit) untuk Certbot

### End-to-End Verification

- [ ] `https://admin.pjb.my.id` → tampil halaman login
- [ ] `https://me.pjb.my.id` → tampil halaman login (UI berbeda)
- [ ] Login superadmin → masuk dashboard
- [ ] Login cs → redirect ke cs.pjb.my.id otomatis
- [ ] Test fingerspot sync: `php artisan fingerspot:sync`
- [ ] Test leaderboard WebSocket real-time
- [ ] Test notifikasi WhatsApp dari n8n
- [ ] Test tutup buku → verifikasi immutable trigger
- [ ] Cek logs Supervisor berjalan normal

---

*Dokumen ini bagian dari: ERP Puncak JB Technical Blueprint v3.0*
*Disiapkan oleh: Google Antigravity*

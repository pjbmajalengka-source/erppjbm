# Git Integration & Workflow Guide (ERP-PJBM)

Panduan ini menjelaskan cara menggunakan Git untuk mengelola kode aplikasi ERP-PJBM dan mensinkronisasikannya ke GitHub organisasi `pjbmajalengka-source`.

---

## 1. Aktivasi Akses (HANYA SEKALI)

Agar komputer lokal bisa mengirim kode ke GitHub tanpa menanyakan password, Anda harus mendaftarkan **SSH Public Key** yang telah saya buatkan.

### Langkah-Langkah:
1.  Salin (Copy) kode Public Key di bawah ini secara utuh:
    ```text
    ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIP7WuDBXri3nPe8Tp8v5sSQKjpBWyc3HJoY1rEkDqCBU pjbmajalengka@github
    ```
2.  Buka web browser dan masuk ke [GitHub Key Settings](https://github.com/settings/ssh/new).
3.  Isi **Title** dengan nama komputer Anda (misal: `HP-Desktop-PJBM`).
4.  Tempel (Paste) kode yang sudah disalin ke kolom **Key**.
5.  Klik tombol **Add SSH Key**.

---

## 2. Push Pertama Kali (Setelah Tambah Key)

Setelah kunci ditambahkan di GitHub, jalankan perintah berikut di terminal (PowerShell) dalam folder `D:\ERP-PJBM`:

```powershell
# Mengetes koneksi ke GitHub
ssh -T git@github.com

# Melakukan push awal ke cabang main
git push -u origin main
```

---

## 3. Aliran Kerja Harian (Daily Workflow)

Gunakan pola **Add -> Commit -> Push** setiap kali Anda selesai melakukan perubahan fitur atau perbaikan bug.

### Langkah 1: Tambahkan Perubahan
```powershell
git add .
```

### Langkah 2: Berikan Catatan Perubahan
```powershell
git commit -m "fitur: menambahkan modul absensi dasar"
```

### Langkah 3: Kirim ke GitHub
```powershell
git push
```

---

## 4. Aturan Emas Git (Golden Rules)

1.  **Selalu Pull Sebelum Push**: Sebelum mulai ngode atau sebelum push, jalankan `git pull` agar lokal Anda selalu sinkron dengan tim lain.
2.  **Gunakan Commit Message yang Jelas**: Hindari pesan seperti "test" atau "fix". Gunakan "fix: perbaikan bug login" atau "feat: modul kasir".
3.  **Jangan Upload File Sensitif**: Jangan pernah menghapus `.env` dari `.gitignore`. File ini berisi password database yang tidak boleh masuk ke GitHub.

---

## 5. Akses Server Staging (Deployment)

Server Staging (VM 117) menggunakan **Deploy Key** tersendiri untuk melakukan `git pull`. 

### Langkah Aktivasi Server:
1.  Salin Public Key khusus server ini:
    ```text
    ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIKpc7y3lUO3vS2p51VDq9f90Evr/BWdRQzgRbzAmRt0H root@hstag.pjb.my.id
    ```
2.  Masuk ke **GitHub Repo > Settings > Deploy Keys**.
3.  Klik **Add deploy key**.
4.  Beri nama `ERP-PJBM-Staging-Server`.
5.  Tempel kuncinya dan klik **Add key**.

---

> [!TIP]
> Jika Anda mengalami masalah `Permission denied (publickey)`, pastikan Anda sudah menambahkan key di langkah nomor 1 (untuk lokal) atau nomor 5 (untuk server).

# Panduan Deploy Culture Monitoring System ke Render

## Prasyarat
1. Akun di [Render](https://render.com).
2. Project Database PostgreSQL di [Neon](https://neon.tech).
3. Repositori GitHub untuk proyek ini.

## Langkah 1: Push Repositori ke GitHub
Jalankan di terminal lokal Anda:
```bash
git remote add origin https://github.com/<username-anda>/<nama-repo>.git
git branch -M main
git push -u origin main
```

## Langkah 2: Deploy Menggunakan Render Blueprint
1. Buka [Dashboard Render](https://dashboard.render.com).
2. Klik tombol **New +** di pojok kanan atas, lalu pilih **Blueprint**.
3. Hubungkan repositori GitHub Anda.
4. Render akan membaca berkas `render.yaml` dan menampilkan 2 service:
   * `culture-monitoring-api` (Web Service)
   * `culture-monitoring-web` (Static Site)
5. Isi variabel environment yang diminta (dari kredensial database Neon Anda):
   * `DB_HOST` (contoh: `ep-xyz.ap-southeast-1.aws.neon.tech`)
   * `DB_DATABASE` (contoh: `neondb`)
   * `DB_USERNAME` (contoh: `neondb_owner`)
   * `DB_PASSWORD` (password project Neon Anda)
   * `VITE_API_BASE_URL` (format: `https://culture-monitoring-api.onrender.com/api`)
6. Klik **Apply**.

## Langkah 3: Verifikasi
* Backend: Buka `https://culture-monitoring-api.onrender.com/up`. Pastikan muncul respons status OK.
* Frontend: Buka URL `https://culture-monitoring-web.onrender.com`. Coba login dengan akun awal dari seeder.

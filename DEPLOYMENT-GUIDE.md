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
   * `APP_URL` (format: `https://culture-monitoring-api.onrender.com`, URL publik backend service Anda)
   * `VITE_API_BASE_URL` (format: `https://culture-monitoring-api.onrender.com/api`)
6. Klik **Apply**.

> **Catatan Penting Build & Environment:**
> - **`VITE_API_BASE_URL`**: Variabel ini di-inject pada saat *build-time* oleh Vite saat membuat bundle Static Site. Jika URL backend berubah atau baru ditentukan setelah deploy pertama, perbarui environment variable lalu pilih **"Clear build cache & deploy"** pada service Static Site agar frontend di-build ulang dengan URL baru.
> - **`RUN_SEEDERS`**: Secara default diset ke `"true"` pada `render.yaml` agar database otomatis di-seed saat deployment pertama. Setelah deployment awal berhasil dan akun default dibuat, Anda dapat mengubah nilai `RUN_SEEDERS` menjadi `"false"` di tab Environment service `culture-monitoring-api` untuk mencegah seeder dijalankan ulang pada deployment berikutnya.

## Langkah 3: Verifikasi
* Backend: Buka `https://culture-monitoring-api.onrender.com/up`. Pastikan muncul respons status OK.
* Frontend: Buka URL `https://culture-monitoring-web.onrender.com`. Coba login dengan akun awal dari seeder.

# Design Document: Deploying Culture Monitoring System to Render (Monorepo)

- **Date**: 2026-10-12
- **Status**: Approved
- **Author**: Antigravity & User

---

## 1. Overview & Context

Aplikasi **Culture Monitoring System** terdiri dari dua komponen utama yang terletak dalam satu direktori kerja (monorepo):
1. **Frontend**: Aplikasi Single Page Application (SPA) berbasis Vue 3, Vite, dan TypeScript di dalam direktori `frontend/`.
2. **Backend**: RESTful API berbasis Laravel 12 (PHP 8.3) dengan Laravel Sanctum (Bearer Token) dan Spatie Permission di dalam direktori `backend/`.
3. **Database**: PostgreSQL serverless di **Neon** (`neon.tech`) dengan koneksi SSL aktif.

Tujuan desain ini adalah mendefinisikan arsitektur dan konfigurasi deployment terpisah ke platform **Render** (Free Tier) menggunakan satu repositori Git (Monorepo) yang dideklarasikan secara otomatis melalui **Render Blueprint (`render.yaml`)**.

---

## 2. Architecture & Service Topology

```mermaid
flowchart TD
    User([Pengguna / Browser])

    subgraph Render["Platform Render (Free Tier)"]
        subgraph StaticSite["Frontend (Static Site)"]
            CDN["Global CDN Render\n(Vue 3 + Vite SPA)"]
        end

        subgraph WebService["Backend (Web Service)"]
            Docker["Container Docker\n(PHP 8.3-FPM + Nginx)\nListening on $PORT"]
            Laravel["Laravel API Application\n(/api/*)"]
            Docker --> Laravel
        end
    end

    subgraph External["External Cloud"]
        Neon[("Neon Serverless\nPostgreSQL (SSL Mode Required)")]
    end

    User -->|Mengakses Halaman Web| CDN
    CDN -->|Panggilan API (VITE_API_BASE_URL)| Docker
    Laravel -->|Koneksi DB (pdo_pgsql, SSL)| Neon
```

### Karakteristik Masing-masing Layanan:
| Komponen | Layanan Render | Runtime | Root Directory | Build / Startup Command |
|---|---|---|---|---|
| **Frontend** | Static Site | Node.js (Vite) | `frontend` | `npm run build` $\rightarrow$ publish `dist` |
| **Backend** | Web Service | Docker | `backend` | Build Dockerfile $\rightarrow$ `entrypoint.sh` |
| **Database** | Eksternal (Neon) | PostgreSQL | N/A | Dikelola di Neon Console |

---

## 3. Detailed Component Specifications

### 3.1. Frontend Service (Render Static Site)
* **Root Directory**: `frontend`
* **Build Command**: `npm run build`
* **Publish Directory**: `dist`
* **SPA Rewrite Rules**:
  * Source: `/*`
  * Destination: `/index.html`
  * Action: `rewrite` (mencegah error 404 saat pengguna melakukan refresh atau bookmark deep link Vue Router).
* **Environment Variables**:
  * `VITE_API_BASE_URL`: URL publik dari Backend Web Service Render (misal: `https://culture-monitoring-api.onrender.com/api`).

### 3.2. Backend Service (Render Web Service via Docker)
* **Root Directory**: `backend`
* **Dockerfile**:
  * Base Image: `php:8.3-fpm-alpine`
  * Web Server: Nginx (terpasang di container yang sama untuk menyajikan file statis `public/` dan meneruskan PHP ke `127.0.0.1:9000`).
  * Ekstensi PHP: `pdo_pgsql`, `pgsql`, `zip`, `gd`, `intl`, `bcmath`, `opcache`.
  * Dependency Manager: Composer v2 (menginstall paket dengan opsi `--no-dev --optimize-autoloader`).
* **Nginx Configuration (`backend/nginx.conf`)**:
  * Mendengarkan port dinamis dari environment variable Render (`$PORT`, default fallback 10000).
  * Root direktori diarahkan ke `/var/www/html/public`.
  * Rule `try_files $uri $uri/ /index.php?$query_string;`.
* **Entrypoint Script (`backend/docker-entrypoint.sh`)**:
  1. Melakukan substitusi nilai `$PORT` pada konfigurasi Nginx.
  2. Menjalankan perintah caching Laravel:
     * `php artisan config:cache`
     * `php artisan route:cache`
     * `php artisan view:cache`
  3. Menjalankan `php artisan storage:link`.
  4. Menjalankan migrasi database: `php artisan migrate --force`.
  5. Menjalankan seeder awal secara aman: `php artisan db:seed --force`.
  6. Menjalankan PHP-FPM di background (`php-fpm -D`) dan Nginx di foreground (`nginx -g "daemon off;"`).
* **Environment Variables**:
  * `APP_NAME`: `Culture Monitoring System`
  * `APP_ENV`: `production`
  * `APP_DEBUG`: `false`
  * `APP_KEY`: *Generated secure key*
  * `APP_URL`: URL publik backend Render
  * `DB_CONNECTION`: `pgsql`
  * `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: Kredensial Neon
  * `DB_SSLMODE`: `require`
  * `SESSION_DRIVER`: `cookie` / `database`
  * `QUEUE_CONNECTION`: `sync` / `database`

### 3.3. Blueprint Specification (`render.yaml`)
Satu file `render.yaml` di root direktori akan mendeklarasikan kedua service:
```yaml
services:
  - type: web
    name: culture-monitoring-api
    runtime: docker
    rootDir: backend
    plan: free
    region: singapore
    dockerfilePath: Dockerfile
    envVars:
      - key: APP_ENV
        value: production
      - key: APP_DEBUG
        value: "false"
      - key: APP_KEY
        generateValue: true
      - key: DB_CONNECTION
        value: pgsql
      - key: DB_SSLMODE
        value: require
      - key: DB_HOST
        sync: false
      - key: DB_PORT
        value: "5432"
      - key: DB_DATABASE
        sync: false
      - key: DB_USERNAME
        sync: false
      - key: DB_PASSWORD
        sync: false

  - type: web
    name: culture-monitoring-web
    runtime: static
    rootDir: frontend
    plan: free
    region: singapore
    buildCommand: npm run build
    staticPublishPath: dist
    routes:
      - type: rewrite
        source: /*
        destination: /index.html
    envVars:
      - key: VITE_API_BASE_URL
        sync: false
```

---

## 4. Cross-Cutting Concerns

### 4.1. CORS (Cross-Origin Resource Sharing)
* Frontend dan backend berjalan di origin berbeda (`https://<frontend>.onrender.com` vs `https://<backend>.onrender.com`).
* Backend Laravel mengizinkan origin dari frontend dan meneruskan header `Authorization`.
* Autentikasi berbasis Sanctum Bearer Token (`localStorage`), sehingga tidak terkendala kebijakan third-party cookie browser.

### 4.2. File Storage & Ephemeral Filesystem
* Gambar laporan yang diunggah pengguna disimpan di disk lokal `storage/app/public/laporan`.
* Pada Render Free Tier, filesystem container bersifat *ephemeral*. Selama instans berjalan, gambar tetap dapat diakses. Jika server restart atau idle spin-down, data file lokal akan tereset.
* Untuk fase testing/eksperimen saat ini, keterbatasan ini diterima. Jika diperlukan di masa depan, Laravel dapat diarahkan ke S3-compatible storage (seperti Supabase Storage atau Cloudinary).

### 4.3. Render Free Tier Cold Starts
* Web Service gratis Render akan tidur (*sleep*) setelah 15 menit tanpa aktivitas.
* Permintaan pertama setelah masa tidur akan membutuhkan waktu sekitar 50 detik (*cold start*). Ini adalah perilaku bawaan free tier Render.

---

## 5. Verification & Testing Plan

1. **Pre-flight Local Verification**:
   * Menjalankan `npm run build` di folder `frontend` untuk memastikan seluruh tipe TypeScript dan bundling Vite valid tanpa error.
   * Membersihkan file vendor & composer yang tidak sengaja terbentuk di root folder.
   * Menyiapkan `.gitignore` di root folder.
2. **Post-Deployment Verification di Render**:
   * Membuka URL backend pada endpoint health check: `https://<backend-url>/up` $\rightarrow$ respon 200 OK.
   * Membuka URL frontend di browser dan melakukan navigasi antar-halaman.
   * Melakukan login dengan akun default hasil seeder.
   * Memverifikasi request API di Network Tab berjalan lancar dengan status 200/201 dan tanpa error CORS.

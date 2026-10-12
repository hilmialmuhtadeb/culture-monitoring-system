# Render Monorepo Deployment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menyiapkan konfigurasi deployment monorepo terpisah untuk frontend (Vue 3 Static Site) dan backend (Laravel 12 Docker Web Service) di platform Render dengan database Neon PostgreSQL.

**Architecture:** Monorepo dideklarasikan via `render.yaml` (Blueprint). Backend dikemas menggunakan Dockerfile multi-service (PHP 8.3-FPM + Nginx) dengan entrypoint yang menangani migrasi otomatis ke Neon. Frontend dibuild sebagai Static Site dengan SPA route rewrite ke `/index.html` dan memanggil backend melalui `VITE_API_BASE_URL`.

**Tech Stack:** Render Blueprint (`render.yaml`), Docker (Alpine Linux, PHP 8.3, Nginx), Laravel 12, PostgreSQL (Neon), Vue 3, Vite, TypeScript.

## Global Constraints
- Platform: Render Free Tier
- Database: Neon Serverless PostgreSQL (`DB_SSLMODE=require`)
- Repository Type: Monorepo (`frontend/` dan `backend/` dalam 1 repositori Git)
- Port: Backend harus mendengarkan variabel `$PORT` yang disuplai oleh Render
- Auth: Sanctum Bearer Token via `Authorization: Bearer <token>`
- Routing Frontend: SPA rewrite rule `/* -> /index.html`

---

### Task 1: Clean Up Root Directory & Setup Root `.gitignore`

**Files:**
- Delete: `composer.json`, `composer.lock`, `vendor/` (di root proyek)
- Create: `.gitignore` (di root proyek)

**Interfaces:**
- Consumes: Struktur repositori monorepo saat ini
- Produces: Repositori yang bersih tanpa file `vendor/` root yang tak sengaja terbuat, dengan `.gitignore` yang memblokir file rahasia & build cache

- [ ] **Step 1: Hapus file vendor dan composer yang tidak sengaja terbuat di root**

Run in PowerShell:
```powershell
Remove-Item -Force composer.json, composer.lock
Remove-Item -Recurse -Force vendor
```

- [ ] **Step 2: Buat root `.gitignore`**

File: `C:\Users\KP24.10516\dev\culture-monitoring-system\.gitignore`
```gitignore
# OS Files
.DS_Store
Thumbs.db

# Node
node_modules/
npm-debug.log*
yarn-debug.log*
yarn-error.log*
.pnpm-debug.log*

# PHP & Composer
vendor/
.phpunit.result.cache

# Environment & Secrets
.env
.env.backup
.env.production
.env.local

# IDE & Editor
.vscode/*
!.vscode/extensions.json
.idea/
*.suo
*.ntvs*
*.njsproj
*.sln
*.sw?

# Build outputs
frontend/dist/
backend/storage/*.key
backend/storage/framework/cache/*
backend/storage/framework/sessions/*
backend/storage/framework/views/*
backend/storage/logs/*
!backend/storage/framework/cache/.gitignore
!backend/storage/framework/sessions/.gitignore
!backend/storage/framework/views/.gitignore
!backend/storage/logs/.gitignore
```

- [ ] **Step 3: Verifikasi status git**

Run in PowerShell:
```powershell
git status
```
Expected: Hanya file yang relevan muncul sebagai untracked, tidak ada folder `vendor/` di root.

- [ ] **Step 4: Commit perubahan Task 1**

Run in PowerShell:
```powershell
git add .gitignore
git commit -m "chore: setup root gitignore and remove stray root composer files"
```

---

### Task 2: Backend Docker Infrastructure (Nginx, Entrypoint, Dockerfile)

**Files:**
- Create: `backend/nginx.conf`
- Create: `backend/docker-entrypoint.sh`
- Create: `backend/Dockerfile`
- Create: `backend/.dockerignore`

**Interfaces:**
- Consumes: Aplikasi Laravel di `backend/`
- Produces: Container image yang mengekspos web server Nginx pada `$PORT` Render, menjalankan PHP 8.3 FPM, dan otomatis menjalankan migrasi & seeder

- [ ] **Step 1: Buat konfigurasi Nginx (`backend/nginx.conf`)**

File: `C:\Users\KP24.10516\dev\culture-monitoring-system\backend\nginx.conf`
```nginx
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';
    access_log /var/log/nginx/access.log main;
    sendfile on;
    keepalive_timeout 65;
    client_max_body_size 30M;

    server {
        listen __PORT__;
        server_name _;
        root /var/www/html/public;

        index index.php index.html;
        charset utf-8;

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        location = /favicon.ico { access_log off; log_not_found off; }
        location = /robots.txt  { access_log off; log_not_found off; }

        location ~ \.php$ {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            include fastcgi_params;
            fastcgi_buffers 16 16k;
            fastcgi_buffer_size 32k;
            fastcgi_read_timeout 180s;
        }

        location ~ /\.(?!well-known).* {
            deny all;
        }
    }
}
```

- [ ] **Step 2: Buat entrypoint script (`backend/docker-entrypoint.sh`)**

File: `C:\Users\KP24.10516\dev\culture-monitoring-system\backend\docker-entrypoint.sh`
```bash
#!/bin/sh
set -e

# Default port Render adalah 10000 jika $PORT tidak didefinisikan
PORT="${PORT:-10000}"
echo "Configuring Nginx to listen on port: $PORT"
sed -i "s/__PORT__/$PORT/g" /etc/nginx/nginx.conf

echo "Optimizing Laravel cache..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "Linking storage..."
php artisan storage:link || true

echo "Running database migrations..."
php artisan migrate --force

echo "Running initial database seeders..."
php artisan db:seed --force || true

echo "Starting PHP-FPM..."
php-fpm -D

echo "Starting Nginx on port $PORT..."
exec nginx -g "daemon off;"
```

- [ ] **Step 3: Buat `backend/.dockerignore`**

File: `C:\Users\KP24.10516\dev\culture-monitoring-system\backend\.dockerignore`
```dockerignore
vendor/
node_modules/
.env
.git
.github
tests/
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
```

- [ ] **Step 4: Buat `backend/Dockerfile`**

File: `C:\Users\KP24.10516\dev\culture-monitoring-system\backend\Dockerfile`
```dockerfile
FROM php:8.3-fpm-alpine

# Install system dependencies & build tools
RUN apk update && apk add --no-cache \
    nginx \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    postgresql-dev \
    icu-dev \
    oniguruma-dev \
    dos2unix

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        intl \
        bcmath \
        opcache

# Copy Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Convert line endings and grant execution permission
RUN dos2unix docker-entrypoint.sh \
    && chmod +x docker-entrypoint.sh \
    && cp nginx.conf /etc/nginx/nginx.conf

# Install composer dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Setup storage and bootstrap permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 10000

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
```

- [ ] **Step 5: Verifikasi sintaks berkas Docker & Nginx**

Run in PowerShell:
```powershell
Get-Content backend\Dockerfile | Select-Object -First 10
Get-Content backend\nginx.conf | Select-String "__PORT__"
```
Expected: File terbaca dengan baik dan `__PORT__` placeholder ada di `nginx.conf`.

- [ ] **Step 6: Commit perubahan Task 2**

Run in PowerShell:
```powershell
git add backend/Dockerfile backend/nginx.conf backend/docker-entrypoint.sh backend/.dockerignore
git commit -m "feat(backend): add dockerfile, nginx config, and startup entrypoint for render"
```

---

### Task 3: Backend CORS & PostgreSQL Configuration Verification

**Files:**
- Create: `backend/config/cors.php`
- Modify: `backend/.env.example`

**Interfaces:**
- Consumes: Request dari domain Frontend Render
- Produces: Respon API dengan header CORS yang mengizinkan preflight dan auth Bearer token

- [ ] **Step 1: Buat konfigurasi CORS (`backend/config/cors.php`)**

File: `C:\Users\KP24.10516\dev\culture-monitoring-system\backend\config\cors.php`
```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'up'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
```

- [ ] **Step 2: Update `backend/.env.example` dengan contoh konfigurasi Neon PostgreSQL**

File: `C:\Users\KP24.10516\dev\culture-monitoring-system\backend\.env.example`
Tambahkan / sesuaikan variabel database:
```env
DB_CONNECTION=pgsql
DB_HOST=ep-example.ap-southeast-1.aws.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=neondb_owner
DB_PASSWORD=secret_password
DB_SSLMODE=require
```

- [ ] **Step 3: Commit perubahan Task 3**

Run in PowerShell:
```powershell
git add backend/config/cors.php backend/.env.example
git commit -m "feat(backend): configure cors policy and document neon postgresql env"
```

---

### Task 4: Frontend Production Build & SPA Verification

**Files:**
- Inspect: `frontend/src/services/api.ts`
- Test: `frontend/package.json`

**Interfaces:**
- Consumes: `import.meta.env.VITE_API_BASE_URL`
- Produces: Bundle file statis di `frontend/dist` tanpa error TypeScript

- [ ] **Step 1: Jalankan type-check dan build frontend secara lokal**

Run in PowerShell:
```powershell
cd frontend
npm run build
cd ..
```
Expected: Build sukses menghasilkan folder `frontend/dist/`, exit code 0.

- [ ] **Step 2: Verifikasi output build dan hapus folder `dist` lokal (agar tidak masuk git)**

Run in PowerShell:
```powershell
Test-Path frontend\dist\index.html
```
Expected: `True`. Folder `frontend/dist` otomatis di-ignore oleh `.gitignore`.

- [ ] **Step 3: Commit perubahan Task 4 jika ada**

Run in PowerShell:
```powershell
git status -s
```
Expected: Working tree clean.

---

### Task 5: Render Blueprint (`render.yaml`) & Deployment Guide

**Files:**
- Create: `render.yaml`
- Create: `DEPLOYMENT-GUIDE.md`

**Interfaces:**
- Consumes: Service Frontend dan Backend monorepo
- Produces: Blueprint Render yang siap dideploy sekali klik serta panduan operasional pengguna

- [ ] **Step 1: Buat file `render.yaml` di root proyek**

File: `C:\Users\KP24.10516\dev\culture-monitoring-system\render.yaml`
```yaml
services:
  # =========================================================================
  # 1. BACKEND API SERVICE (Laravel Docker)
  # =========================================================================
  - type: web
    name: culture-monitoring-api
    runtime: docker
    rootDir: backend
    plan: free
    region: singapore
    dockerfilePath: Dockerfile
    envVars:
      - key: APP_NAME
        value: "Culture Monitoring System"
      - key: APP_ENV
        value: production
      - key: APP_DEBUG
        value: "false"
      - key: APP_KEY
        generateValue: true
      - key: LOG_CHANNEL
        value: stderr
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
      - key: SESSION_DRIVER
        value: cookie
      - key: CACHE_STORE
        value: array
      - key: QUEUE_CONNECTION
        value: sync

  # =========================================================================
  # 2. FRONTEND STATIC SITE (Vue 3 + Vite)
  # =========================================================================
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

- [ ] **Step 2: Buat panduan `DEPLOYMENT-GUIDE.md`**

File: `C:\Users\KP24.10516\dev\culture-monitoring-system\DEPLOYMENT-GUIDE.md`
```markdown
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
   * `VITE_API_BASE_URL` (bisa diisi sementara atau diupdate setelah backend selesai dibuat, format: `https://culture-monitoring-api.onrender.com/api`)
6. Klik **Apply**.

## Langkah 3: Verifikasi
* Backend: Buka `https://culture-monitoring-api.onrender.com/up`. Pastikan muncul respons status OK.
* Frontend: Buka URL `https://culture-monitoring-web.onrender.com`. Coba login dengan akun awal dari seeder.
```

- [ ] **Step 3: Commit perubahan Task 5**

Run in PowerShell:
```powershell
git add render.yaml DEPLOYMENT-GUIDE.md
git commit -m "feat: add render blueprint specification and deployment guide"
```

- [ ] **Step 4: Verifikasi git log**

Run in PowerShell:
```powershell
git log --oneline
```
Expected: Menampilkan riwayat commit yang rapi dan terstruktur.

# VCF System API - Backend

Sistem API untuk Vehicle Control Form (VCF) — digitized vehicle inspection system untuk PT. Industri Nabati Lestari.

## Tech Stack

- **Framework**: Laravel 8.x
- **PHP**: 7.3+ / 8.0+
- **Database**: PostgreSQL (production via Render) / MySQL (lokal)
- **Authentication**: Laravel Sanctum (token-based)
- **Containerization**: Docker + Apache
- **Web Server**: Apache (php:8.2-apache)

## Quick Start (Local Development with Docker)

### Prerequisites
- Docker Desktop (Windows/Mac) atau Docker Engine (Linux)
- Git

### Setup Lokal

#### Windows
```bash
git clone <repo-url>
cd be
setup-docker.bat
```

#### Linux/Mac
```bash
git clone <repo-url>
cd be
chmod +x setup-docker.sh
./setup-docker.sh
```

### Akses Aplikasi
- **API**: http://localhost:8080
- **PhpMyAdmin**: http://localhost:8081 (username: vcf_user, password: vcf_password)

### Docker Commands

```bash
# View logs
docker-compose logs -f app

# Stop services
docker-compose down

# Run artisan commands
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan db:seed --class=MasterDataSeeder
docker-compose exec app php artisan tinker

# Rebuild from scratch
docker-compose down -v
docker image rm vcf-app
docker-compose build --no-cache
docker-compose up -d
```

---

## Deployment ke Render.com

Proyek ini dikonfigurasi untuk deploy ke Render menggunakan `render.yaml` dengan **PostgreSQL**.

### Step 1: Setup Render Account
1. Buka https://render.com → Sign up dengan GitHub
2. Authorize Render untuk akses repository

### Step 2: Deploy via render.yaml

Push `render.yaml` ke repository, lalu di Render dashboard:
1. **New** → **Blueprint** → pilih repository ini
2. Render akan otomatis membuat Web Service (`vcf-api`) + PostgreSQL database (`vcf-db`)

### Step 3: Configure Environment Variables

Di Web Service settings → **Environment**, tambahkan variabel berikut (selain yang sudah otomatis dari `render.yaml`):

```env
APP_KEY=base64:xxxx...      # Wajib diisi manual
SANCTUM_STATEFUL_DOMAINS=your-frontend-domain.com
SESSION_DOMAIN=.onrender.com
```

**Cara generate APP_KEY:**
```bash
# Lokal (tanpa Docker):
php artisan key:generate --show

# Dengan Docker:
docker-compose exec app php artisan key:generate --show
```
Copy output `base64:...` ke env var `APP_KEY` di Render.

### Step 4: Run Initial Seeder (Opsional)

Setelah deployment pertama berhasil, jalankan seeder data master via Shell:

```bash
# Di Render Shell
php artisan db:seed --class=MasterDataSeeder
php artisan db:seed --class=SettingsSeeder
```

> **Penting**: Jangan jalankan `migrate:fresh --seed` di production — akan menghapus semua data.

### Step 5: Verify Deployment

```bash
curl https://your-domain.onrender.com/api/health
```

---

## Project Structure

```
be/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── API/
│   │   │       ├── AuthController.php
│   │   │       ├── DashboardController.php
│   │   │       ├── SettingController.php
│   │   │       ├── Master/
│   │   │       │   ├── DriverController.php
│   │   │       │   ├── ItemKelengkapanSupirController.php
│   │   │       │   ├── ItemMuatanController.php
│   │   │       │   ├── ItemPemeriksaanKeluarController.php
│   │   │       │   ├── ItemPemeriksaanMasukController.php
│   │   │       │   ├── JenisKendaraanController.php
│   │   │       │   ├── LogistikController.php
│   │   │       │   ├── ProdukController.php
│   │   │       │   ├── TransporterController.php
│   │   │       │   ├── UserController.php
│   │   │       │   └── ViolationController.php
│   │   │       └── VCF/
│   │   │           ├── VcfBagian1Controller.php
│   │   │           ├── VcfBagian2Controller.php
│   │   │           ├── VcfBagian3Controller.php
│   │   │           └── VcfBagian4Controller.php
│   │   └── Middleware/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Vcf.php
│   │   ├── Driver.php
│   │   ├── Transporter.php
│   │   ├── JenisKendaraan.php
│   │   ├── Produk.php
│   │   ├── Logistik.php
│   │   ├── Violation.php
│   │   ├── Setting.php
│   │   ├── ItemKelengkapanSupir.php
│   │   ├── ItemMuatan.php
│   │   ├── ItemPemeriksaanMasuk.php
│   │   ├── ItemPemeriksaanKeluar.php
│   │   ├── VcfKelengkapanSupir.php
│   │   ├── VcfMuatanDibawa.php
│   │   ├── VcfMuatanDiisi.php
│   │   ├── VcfPemeriksaanMasuk.php
│   │   ├── VcfPemeriksaanKeluar.php
│   │   ├── VcfBebanTambahanMasuk.php
│   │   ├── VcfBebanTambahanKeluar.php
│   │   ├── VcfSegelMasuk.php
│   │   ├── VcfSegelKeluar.php
│   │   ├── VcfNomorSegelMasuk.php
│   │   ├── VcfNomorSegelKeluar.php
│   │   └── VcfKeluar.php
│   └── Providers/
├── database/
│   ├── migrations/          # 35 migration files
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── MasterDataSeeder.php
│       ├── SettingsSeeder.php
│       └── VcfTransactionSeeder.php
├── routes/
│   └── api.php
├── Dockerfile               # Apache-based production image
├── docker-compose.yml       # Local dev environment
├── render.yaml              # Render deployment config
├── start.sh                 # Container startup script
└── nginx.conf               # Nginx config (alternatif)
```

---

## Authentication

### Login
```bash
POST /api/login
{
  "username": "admin",
  "password": "password"
}
```

Response:
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "bearer-token-here",
    "user": { "id": 1, "nama": "Admin", "role": "admin" }
  }
}
```

> Login menggunakan **username** (bukan email).

### Protected Routes
```
Authorization: Bearer {token}
```

---

## API Endpoints

### Authentication
- `POST /api/login` — Login, dapatkan token
- `POST /api/logout` — Logout
- `GET /api/me` — Get current user info

### Dashboard
- `GET /api/dashboard/stats` — Statistik VCF hari ini

### Master Data
- `GET|POST /api/master/users` — Manajemen user
- `GET|POST /api/master/transporters` — Perusahaan transporter
- `GET|POST /api/master/drivers` — Data driver
- `GET|POST /api/master/jenis-kendaraan` — Jenis kendaraan
- `GET|POST /api/master/produk` — Produk
- `GET|POST /api/master/logistik` — Data logistik
- `GET|POST /api/master/item-kelengkapan-supir` — Item checklist supir
- `GET|POST /api/master/item-muatan` — Item muatan
- `GET|POST /api/master/item-pemeriksaan-masuk` — Item pemeriksaan masuk
- `GET|POST /api/master/item-pemeriksaan-keluar` — Item pemeriksaan keluar
- `GET|POST /api/master/violations` — Riwayat pelanggaran driver

### VCF Transactions
- `GET /api/vcf/next-number` — Next nomor urut VCF
- `GET /api/vcf` — List VCF
- `POST /api/vcf` — Buat VCF baru (Bagian 1)
- `GET /api/vcf/{id}` — Detail VCF
- `PUT /api/vcf/{id}` — Update VCF (Bagian 1)
- `POST /api/vcf/{id}/reject` — Reject VCF
- `GET|POST|PUT /api/vcf/{id}/bagian2` — Pemeriksaan masuk (Bagian 2)
- `POST /api/vcf/{id}/bagian2/reject` — Reject Bagian 2
- `GET|POST|PUT /api/vcf/{id}/bagian3` — Pemeriksaan keluar (Bagian 3)
- `POST /api/vcf/{id}/bagian3/reject` — Reject Bagian 3
- `GET|POST|PUT /api/vcf/{id}/bagian4` — Main gate keluar (Bagian 4)
- `POST /api/vcf/{id}/finalize` — Finalisasi VCF

### Settings
- `GET /api/settings` — Semua settings
- `GET /api/settings/print` — Print settings
- `PUT /api/settings/{key}` — Update setting (Admin only)
- `PUT /api/settings/batch` — Update multiple settings (Admin only)

> Lihat `routes.txt` untuk daftar lengkap semua endpoint, atau import `VCF System API — PT. Industri Nabati Lestari.postman_collection.json` ke Postman.

---

## Database Schema

ERD tersedia di `vcf_erd_database_schema (1).html` — buka di browser untuk diagram visual.

### Tabel Transaksi VCF

| Tabel | Deskripsi |
|-------|-----------|
| `vcfs` | Header transaksi VCF |
| `vcf_kelengkapan_supirs` | Kelengkapan supir per VCF |
| `vcf_muatan_dibawas` | Muatan yang dibawa |
| `vcf_muatan_diisis` | Muatan yang diisi |
| `vcf_pemeriksaan_masuks` | Pemeriksaan masuk per item |
| `vcf_beban_tambahan_masuks` | Beban tambahan masuk |
| `vcf_segel_masuks` | Data segel masuk |
| `vcf_nomor_segel_masuks` | Nomor segel masuk |
| `vcf_pemeriksaan_keluars` | Pemeriksaan keluar per item |
| `vcf_beban_tambahan_keluars` | Beban tambahan keluar |
| `vcf_segel_keluars` | Data segel keluar |
| `vcf_nomor_segel_keluars` | Nomor segel keluar |
| `vcf_keluars` | Data main gate keluar (Bagian 4) |

### Tabel Master

| Tabel | Deskripsi |
|-------|-----------|
| `users` | Akun user (admin/petugas) |
| `transporters` | Data perusahaan transporter |
| `drivers` | Data driver |
| `jenis_kendaraans` | Jenis/tipe kendaraan |
| `produks` | Produk yang diangkut |
| `logistiks` | Data logistik |
| `violations` | Riwayat pelanggaran driver |
| `item_kelengkapan_supirs` | Master item checklist supir |
| `item_muatans` | Master item muatan |
| `item_pemeriksaan_masuks` | Master item pemeriksaan masuk |
| `item_pemeriksaan_keluars` | Master item pemeriksaan keluar |
| `settings` | Konfigurasi sistem |

---

## Seeders

| Seeder | Deskripsi |
|--------|-----------|
| `MasterDataSeeder` | Data master awal (driver, transporter, jenis kendaraan, produk, item pemeriksaan, dll) |
| `SettingsSeeder` | Konfigurasi default sistem (nama perusahaan, print settings, dll) |
| `VcfTransactionSeeder` | Data transaksi VCF contoh (untuk development) |

```bash
# Jalankan semua seeder
php artisan db:seed

# Jalankan seeder tertentu
php artisan db:seed --class=MasterDataSeeder
php artisan db:seed --class=SettingsSeeder
```

---

## Artisan Commands

```bash
# Cache optimization (production)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Database
php artisan migrate
php artisan migrate:rollback
php artisan db:seed --class=MasterDataSeeder

# REPL
php artisan tinker
```

---

## Troubleshooting

### Docker Build Error
```bash
docker-compose down -v
docker image prune -a
docker-compose build --no-cache
docker-compose up -d
```

### Permission Issues (Linux/Mac)
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Render — 502 Bad Gateway
- Check logs: Dashboard → Logs
- Pastikan `APP_KEY` sudah di-set di environment variables
- Restart: Dashboard → Manual Deploy

### Render — Database Connection Error
- Pastikan env variables `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD` sudah terisi dari database config
- Render auto-inject variabel DB jika menggunakan `fromDatabase` di `render.yaml`

### APP_KEY Error
- Generate: `php artisan key:generate --show`
- Set nilai `base64:...` ke env var `APP_KEY` di Render dashboard

---

## Default Credentials (Development)

Dibuat oleh `MasterDataSeeder`:

- **Admin**: username `admin`, password `password`
- **Petugas**: username `petugas`, password `password`

> Ganti password default sebelum deploy ke production.

---

## Security Notes

- Jangan commit file `.env` ke version control
- Set `APP_DEBUG=false` di production
- Gunakan HTTPS di production
- Ganti password default sebelum go-live

---

## License

Proprietary - PT. Industri Nabati Lestari

**Last Updated**: May 2026

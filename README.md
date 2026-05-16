# VCF System API - Backend

Sistem API untuk Vehicle Control Form (VCF) - Digitized vehicle inspection system untuk PT. Industri Nabati Lestari.

## 🏗️ Tech Stack

- **Framework**: Laravel 8.x
- **PHP**: 8.1+
- **Database**: MySQL 8.0+
- **Authentication**: Laravel Sanctum (token-based)
- **Containerization**: Docker + Alpine Linux
- **Web Server**: Nginx

## 🚀 Quick Start (Local Development with Docker)

### Prerequisites
- Docker Desktop (Windows/Mac) atau Docker Engine (Linux)
- Git

### Setup Lokal

#### Windows
```bash
# 1. Clone project
git clone <repo-url>
cd be

# 2. Run setup script
setup-docker.bat
```

#### Linux/Mac
```bash
# 1. Clone project
git clone <repo-url>
cd be

# 2. Run setup script
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
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan tinker

# Rebuild from scratch
docker-compose down -v
docker image rm vcf-app
docker-compose build --no-cache
docker-compose up -d
```

---

## 📦 Deployment ke Render.com

### Step 1: Setup Render Account
1. Buka https://render.com
2. Sign up dengan GitHub account
3. Authorize Render untuk akses repository

### Step 2: Deploy MySQL Database

1. Dashboard → **New** → **MySQL**
2. Isi konfigurasi:
   - **Name**: `vcf-db`
   - **Database Name**: `vcf_production`
   - **Username**: `vcf_user`
   - **Region**: Singapore (atau terdekat)
   - **Plan**: Free
3. Click **Create Database**
4. **Catat**: Host, Username, Password dari notification email

### Step 3: Deploy Web Service

1. Dashboard → **New** → **Web Service**
2. Select repository: vcf-github-rinko/be
3. Isi konfigurasi:
   - **Name**: `vcf-api`
   - **Environment**: Docker
   - **Region**: Singapore
   - **Plan**: Free
4. Click **Create Web Service** (tunggu ~5-10 menit)

### Step 4: Configure Environment Variables

Di Web Service settings → **Environment**, tambahkan:

```env
APP_ENV=production
APP_DEBUG=false
LOG_CHANNEL=errorlog
DB_CONNECTION=mysql
DB_HOST=[database-host-dari-mysql-setup]
DB_PORT=3306
DB_DATABASE=vcf_production
DB_USERNAME=vcf_user
DB_PASSWORD=[password-dari-mysql-setup]
APP_KEY=[generate-dengan-php-artisan-key-generate]
SANCTUM_STATEFUL_DOMAINS=your-domain.onrender.com
SESSION_DOMAIN=.onrender.com
```

**Cara generate APP_KEY lokal:**
```bash
docker-compose exec app php artisan key:generate
# Copy output: base64:xxxxx
```

Klik **Save** → Service akan auto re-deploy.

### Step 5: Run Database Setup

Setelah deployment success:

1. Buka Web Service → **Shell**
2. Jalankan:
```bash
php artisan migrate --force
php artisan db:seed
php artisan config:cache
php artisan route:cache
```

### Step 6: Verify Deployment

```bash
# Test endpoint
curl https://your-domain.onrender.com/

# Check health
curl https://your-domain.onrender.com/health
```

---

## 📋 Project Structure

```
app/
├── Console/              # Artisan commands
├── Http/
│   ├── Controllers/
│   │   ├── API/Auth/     # Authentication
│   │   ├── API/Master/   # Master data CRUD
│   │   └── API/VCF/      # VCF workflow (Bagian1-4)
│   ├── Kernel.php
│   └── Middleware/       # Auth & Access control
├── Models/               # Eloquent models
└── Providers/

database/
├── migrations/           # Schema definitions
├── seeders/             # Data seeders
└── factories/           # Test factories

routes/
├── api.php              # API routes (main)
└── web.php

tests/
├── Feature/             # Integration tests
└── Unit/                # Unit tests
```

---

## 🔐 Authentication

### Login
```bash
POST /api/login
{
  "email": "user@example.com",
  "password": "password"
}
```

Response:
```json
{
  "message": "Login successful",
  "data": {
    "token": "bearer-token-here",
    "user": {...}
  },
  "success": true
}
```

### Protected Routes
Tambahkan header:
```
Authorization: Bearer {token}
```

---

## 📚 API Endpoints

### Master Data
- `GET/POST /api/transporters` - Perusahaan pengangkut
- `GET/POST /api/drivers` - Data sopir
- `GET/POST /api/jenis-kendaraan` - Tipe kendaraan
- `GET/POST /api/produk` - Data produk
- `GET/POST /api/logistik` - Logistik

### VCF Workflow
- `POST /api/vcf/bagian1` - Gate masuk
- `POST /api/vcf/bagian2` - Weighbridge masuk
- `POST /api/vcf/bagian3` - Weighbridge keluar
- `POST /api/vcf/bagian4` - Gate keluar

**Full API Documentation** tersedia di: `VCF System API — PT. Industri Nabati Lestari.postman_collection.json`

---

## 🗄️ Database

### Tables

| Table | Purpose |
|-------|---------|
| `vcfs` | Main VCF records |
| `users` | User accounts |
| `transporters` | Transport companies |
| `drivers` | Driver data |
| `jenis_kendaraans` | Vehicle types |
| `produks` | Products |
| `logistiks` | Logistics |
| `vcf_pemeriksaan_masuk` | Gate entry inspection |
| `vcf_muatan_dibawa` | Incoming cargo |
| `vcf_muatan_diisi` | Cargo loaded |
| `vcf_pemeriksaan_keluar` | Gate exit inspection |

### Migrations
```bash
# Run migrations
php artisan migrate

# Rollback last batch
php artisan migrate:rollback

# Reset semua
php artisan migrate:fresh --seed
```

---

## 🧪 Testing

```bash
# Run all tests
./vendor/bin/phpunit

# Feature tests only
./vendor/bin/phpunit tests/Feature

# Unit tests only
./vendor/bin/phpunit tests/Unit

# Specific test
./vendor/bin/phpunit tests/Feature/VcfBagian1ControllerTest
```

---

## 🔧 Artisan Commands

```bash
# Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Database
php artisan migrate
php artisan db:seed
php artisan db:seed --class=MasterDataSeeder

# Tinker (REPL)
php artisan tinker

# Generate resources
php artisan make:model ModelName
php artisan make:controller ControllerName
php artisan make:migration migration_name
```

---

## 🐛 Troubleshooting

### Docker Build Error
```bash
# Clean rebuild
docker-compose down -v
docker image prune -a
docker-compose build --no-cache
docker-compose up -d
```

### Database Connection Failed
```bash
# Check database health
docker-compose logs db

# Verify credentials in .env
cat .env | grep DB_

# Restart database
docker-compose restart db
```

### Render Deployment Issues

**502 Bad Gateway**
- Check logs: Dashboard → Logs
- Restart service: Dashboard → Manual Deploy

**Database Connection Error**
- Verify env variables are correct
- Check database credentials
- Run migrations in Shell

**APP_KEY Error**
- Generate new: `docker-compose exec app php artisan key:generate`
- Copy base64 value to Render env var

---

## 📞 Support Resources

- [Laravel Documentation](https://laravel.com/docs/8.x)
- [Render Documentation](https://render.com/docs)
- [Docker Documentation](https://docs.docker.com/)
- [Postman Collection](./VCF%20System%20API%20—%20PT.%20Industri%20Nabati%20Lestari.postman_collection.json)
- [AGENTS.md](./AGENTS.md) - Developer guide

---

## 📄 License

MIT License - PT. Industri Nabati Lestari

---

**Last Updated**: May 2026  
**Status**: Production Ready ✅
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vcf_db
DB_USERNAME=root
DB_PASSWORD=your_password
```

Generate application key:

```bash
php artisan key:generate
```

### 4. Setup Database

Buat database MySQL baru dengan nama sesuai konfigurasi di `.env`, lalu jalankan migrasi:

```bash
php artisan migrate
```

Untuk mengisi data awal (seeders):

```bash
php artisan db:seed
```

### 5. Create Default Users

Jika seeder belum membuat user default, buat manual via tinker:

```bash
php artisan tinker
```

```php
use App\Models\User;

// Admin user
User::create([
    'nama' => 'Admin',
    'username' => 'admin',
    'password' => bcrypt('password'),
    'role' => 'admin',
    'is_active' => true
]);

// Petugas user
User::create([
    'nama' => 'Petugas',
    'username' => 'petugas',
    'password' => bcrypt('password'),
    'role' => 'petugas',
    'is_active' => true
]);
```

## Running the Application

### Development Server

Jalankan server development:

```bash
php artisan serve
```

API akan tersedia di `http://localhost:8000`

### Production

Untuk production, pastikan:

1. Set `APP_ENV=production` dan `APP_DEBUG=false` di `.env`
2. Optimize autoloader: `composer install --optimize-autoloader --no-dev`
3. Cache config: `php artisan config:cache`
4. Cache routes: `php artisan route:cache`
5. Optimize views: `php artisan view:cache`

## API Documentation

API documentation tersedia dalam format Postman Collection di file:
- `VCF System API — PT. Industri Nabati Lestari.postman_collection.json`

Import file ini ke Postman untuk testing semua endpoint.

### Default Credentials

- **Admin**: username `admin`, password `password`
- **Petugas**: username `petugas`, password `password`

### Main Endpoints

#### Authentication
- `POST /api/login` - Login dan dapatkan token
- `POST /api/logout` - Logout
- `GET /api/me` - Get current user info
- `GET /api/dashboard/stats` - Dashboard statistics

#### Master Data
- `GET /api/master/users` - List users
- `GET /api/master/transporters` - List transporters
- `GET /api/master/drivers` - List drivers
- `GET /api/master/jenis-kendaraan` - List jenis kendaraan
- `GET /api/master/produk` - List produk
- `GET /api/master/item-pemeriksaan-masuk` - List item pemeriksaan masuk
- `GET /api/master/item-pemeriksaan-keluar` - List item pemeriksaan keluar
- `GET /api/master/item-muatan` - List item muatan
- `GET /api/master/item-kelengkapan-supir` - List item kelengkapan supir

#### VCF Transactions
- `GET /api/vcf/next-number` - Get next nomor urut
- `GET /api/vcf` - List VCF
- `POST /api/vcf` - Create VCF (Bagian 1)
- `GET /api/vcf/{id}` - Get VCF detail
- `PUT /api/vcf/{id}` - Update VCF (Bagian 1)
- `POST /api/vcf/{id}/reject` - Reject VCF
- `GET /api/vcf/{vcfId}/bagian2` - Get Bagian 2
- `POST /api/vcf/{vcfId}/bagian2` - Submit Bagian 2
- `PUT /api/vcf/{vcfId}/bagian2` - Update Bagian 2
- `POST /api/vcf/{vcfId}/bagian2/reject` - Reject Bagian 2
- `GET /api/vcf/{vcfId}/bagian3` - Get Bagian 3
- `POST /api/vcf/{vcfId}/bagian3` - Submit Bagian 3
- `PUT /api/vcf/{vcfId}/bagian3` - Update Bagian 3
- `POST /api/vcf/{vcfId}/bagian3/reject` - Reject Bagian 3
- `GET /api/vcf/{vcfId}/bagian4` - Get Bagian 4
- `POST /api/vcf/{vcfId}/bagian4` - Submit Bagian 4
- `PUT /api/vcf/{vcfId}/bagian4` - Update Bagian 4
- `POST /api/vcf/{vcfId}/finalize` - Finalize VCF

#### Settings
- `GET /api/settings` - Get all settings
- `GET /api/settings/public` - Get public settings
- `GET /api/settings/vcf` - Get VCF settings
- `GET /api/settings/print` - Get print settings
- `PUT /api/settings/{key}` - Update setting (Admin only)
- `PUT /api/settings/batch` - Update multiple settings (Admin only)

Lihat `routes.txt` untuk daftar lengkap semua endpoint.

## Database Schema

ERD tersedia di file `vcf_erd_database_schema (1).html`. Buka file ini di browser untuk melihat diagram database secara visual.

### Main Tables

- **users** - User authentication & authorization
- **transporters** - Data perusahaan transportir
- **drivers** - Data supir
- **jenis_kendaraans** - Tipe kendaraan
- **produks** - Produk yang diangkut
- **vcfs** - Header transaksi VCF
- **vcf_kelengkapan_supirs** - Kelengkapan supir per VCF
- **vcf_muatan_dibawas** - Muatan yang dibawa per VCF
- **vcf_muatan_diisis** - Muatan yang diisi per VCF
- **vcf_pemeriksaan_masuks** - Pemeriksaan weighbridge masuk
- **vcf_beban_tambahan_masuks** - Beban tambahan masuk
- **vcf_segel_masuks** - Data segel masuk
- **vcf_nomor_segel_masuks** - Nomor segel masuk
- **vcf_pemeriksaan_keluars** - Pemeriksaan weighbridge keluar
- **vcf_beban_tambahan_keluars** - Beban tambahan keluar
- **vcf_segel_keluars** - Data segel keluar
- **vcf_nomor_segel_keluars** - Nomor segel keluar
- **vcf_keluars** - Data keluar (Bagian 4)
- **settings** - Konfigurasi sistem

### Master Tables

- **item_kelengkapan_supirs** - Master item kelengkapan supir
- **item_muatans** - Master item muatan
- **item_pemeriksaan_masuks** - Master item pemeriksaan masuk
- **item_pemeriksaan_keluars** - Master item pemeriksaan keluar

## Project Structure

```
be/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── API/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── SettingController.php
│   │   │   │   ├── Master/
│   │   │   │   │   ├── DriverController.php
│   │   │   │   │   ├── JenisKendaraanController.php
│   │   │   │   │   ├── LogistikController.php
│   │   │   │   │   ├── ProdukController.php
│   │   │   │   │   ├── TransporterController.php
│   │   │   │   │   └── UserController.php
│   │   │   │   └── VCF/
│   │   │   │       ├── VcfBagian1Controller.php
│   │   │   │       ├── VcfBagian2Controller.php
│   │   │   │       ├── VcfBagian3Controller.php
│   │   │   │       └── VcfBagian4Controller.php
│   │   │   └── Middleware/
│   │   │       ├── AdminMiddleware.php
│   │   │       └── PetugasMiddleware.php
│   │   └── Requests/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Transporter.php
│   │   ├── Driver.php
│   │   ├── JenisKendaraan.php
│   │   ├── Produk.php
│   │   ├── Vcf.php
│   │   └── ...
│   └── ...
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
├── tests/
└── ...
```

## Testing

Jalankan test suite:

```bash
php artisan test
```

Untuk test spesifik:

```bash
php artisan test --filter TestClassName
```

## Troubleshooting

### Permission Issues

Jika ada masalah permission di Linux/Mac:

```bash
chmod -R 775 storage bootstrap/cache
```

### Cache Issues

Clear cache jika ada masalah:

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Database Connection

Pastikan service MySQL/MariaDB sudah running dan credential di `.env` sudah benar.

## Security Notes

- Jangan pernah commit file `.env` ke version control
- Ganti password default di production
- Gunakan HTTPS di production
- Set `APP_DEBUG=false` di production
- Rate limiting sudah diimplementasikan untuk API endpoints

## License

Proprietary - PT. Industri Nabati Lestari

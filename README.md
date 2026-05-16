# VCF System - Backend API

Backend API untuk sistem Vehicle Control Form (VCF) PT. Industri Nabati Lestari. Sistem ini mendigitalkan proses pemeriksaan kendaraan yang masuk dan keluar area perusahaan.

## Tech Stack

- **Framework**: Laravel 11
- **PHP**: 8.2+
- **Database**: MySQL/MariaDB
- **Authentication**: Laravel Sanctum
- **API Documentation**: Postman Collection

## Prerequisites

Sebelum memulai, pastikan sudah terinstall:

- PHP 8.2 atau lebih tinggi
- Composer
- MySQL/MariaDB 8.0+
- Node.js & NPM (untuk build assets jika diperlukan)
- Git

## Installation

### 1. Clone Repository

```bash
git clone <repository-url>
cd vcf/be
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Setup Environment

Copy file `.env.example` ke `.env`:

```bash
cp .env.example .env
```

Edit file `.env` dan sesuaikan konfigurasi database:

```env
APP_NAME="VCF System"
APP_ENV=local
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

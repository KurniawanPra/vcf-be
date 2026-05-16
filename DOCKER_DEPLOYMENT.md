# Panduan Deployment VCF API ke Render.com dengan Docker

## Daftar File yang Telah Dibuat:

1. **Dockerfile** - Multi-stage build untuk production
2. **.dockerignore** - File yang dikecualikan saat build
3. **docker-compose.yml** - Untuk development/testing lokal
4. **nginx.conf** - Konfigurasi Nginx untuk production
5. **start.sh** - Script startup untuk aplikasi
6. **render.yaml** - Konfigurasi deployment Render

---

## 📝 Langkah-Langkah Deployment

### 1. Persiapan Lokal

```bash
# Clone/pastikan project ada
cd d:\SMKAW02PDN\Laporan\ PKL\Project\vcf\vcf-github-rinko\be

# Generate APP_KEY jika belum ada
php artisan key:generate

# Copy .env.example ke .env untuk testing lokal
cp .env.example .env

# Update APP_KEY di .env dengan key yang dihasilkan
```

### 2. Test Lokal dengan Docker Compose

```bash
# Build dan jalankan container
docker-compose up -d

# Jalankan migrasi
docker-compose exec app php artisan migrate --force

# Seed database (jika ada)
docker-compose exec app php artisan db:seed

# Akses aplikasi
# API: http://localhost:8080
# PhpMyAdmin: http://localhost:8081
# Username: vcf_user | Password: vcf_password

# Lihat logs
docker-compose logs -f app

# Stop services
docker-compose down
```

### 3. Push ke GitHub

```bash
# Jika belum ada Git repository
git init
git add .
git commit -m "Add Docker configuration for Render deployment"

# Jika sudah ada
git add Dockerfile .dockerignore docker-compose.yml nginx.conf start.sh render.yaml
git commit -m "Add Docker configuration for Render deployment"
git push origin main
```

### 4. Setup di Render.com

#### A. Buat Akun & Hubungkan GitHub
1. Buka https://render.com
2. Sign up dengan GitHub account
3. Authorize Render untuk akses repository

#### B. Deploy Database (MySQL)
1. Dashboard → New → MySQL
2. Isi konfigurasi:
   - **Name**: `vcf-db`
   - **Database Name**: `vcf_production`
   - **Username**: `vcf_user`
   - **Region**: Singapore (pilih yang terdekat)
   - **Plan**: Free (atau Premium sesuai kebutuhan)
3. Klik "Create Database"
4. Tunggu sampai selesai, catat credentials-nya

#### C. Deploy Web Service
1. Dashboard → New → Web Service
2. Pilih repository VCF
3. Isi konfigurasi:
   - **Name**: `vcf-api`
   - **Environment**: Docker
   - **Region**: Singapore
   - **Plan**: Free (atau Premium)
   - **Build Command**: (biarkan kosong, gunakan Dockerfile)
   - **Start Command**: (biarkan kosong, gunakan start.sh dari Dockerfile)

4. **Environment Variables**:
   ```
   APP_ENV=production
   APP_DEBUG=false
   LOG_CHANNEL=errorlog
   DB_CONNECTION=mysql
   DB_HOST=<database-host-dari-mysql>
   DB_PORT=3306
   DB_DATABASE=vcf_production
   DB_USERNAME=vcf_user
   DB_PASSWORD=<password-dari-mysql>
   APP_KEY=base64:xxxxx (dari php artisan key:generate)
   SANCTUM_STATEFUL_DOMAINS=your-domain.onrender.com
   SESSION_DOMAIN=.your-domain.onrender.com
   ```

5. Klik "Create Web Service"
6. Tunggu deployment selesai (~5-10 menit)

#### D. Konfigurasi Domain (Optional)
1. Buka setting Web Service yang telah dibuat
2. Cari "Custom Domain"
3. Tambahkan domain Anda atau gunakan domain Render yang gratis

### 5. Database Setup di Production

Setelah deploy berhasil:

```bash
# SSH ke server (atau gunakan Render Shell)
# Migrasi database
render exec vcf-api -- php artisan migrate --force

# Seed database jika diperlukan
render exec vcf-api -- php artisan db:seed

# Cache configuration
render exec vcf-api -- php artisan config:cache
render exec vcf-api -- php artisan route:cache
```

Atau melalui Render Dashboard:
1. Buka Web Service → Shell
2. Jalankan command di atas

---

## 🔧 Environment Variables untuk Render

| Variable | Value | Keterangan |
|----------|-------|-----------|
| APP_ENV | production | Set ke production |
| APP_DEBUG | false | Jangan debug di production |
| LOG_CHANNEL | errorlog | Render logs ke stderr |
| DB_CONNECTION | mysql | Koneksi MySQL |
| DB_HOST | Database host | Dari MySQL instance |
| DB_PORT | 3306 | Default MySQL port |
| DB_DATABASE | vcf_production | Database name |
| DB_USERNAME | vcf_user | Database user |
| DB_PASSWORD | *** | Database password |
| APP_KEY | base64:xxxx | Generate dari `php artisan key:generate` |

---

## 🐛 Troubleshooting

### Deployment Failed
- Cek **Logs** di Render Dashboard
- Pastikan Dockerfile dan start.sh ada
- Verifikasi struktur file project

### Database Connection Error
- Verify DB_HOST, DB_USERNAME, DB_PASSWORD
- Pastikan database sudah created di Render
- Cek firewall/security rules

### 502 Bad Gateway
- Cek PHP-FPM connection
- Lihat logs: `tail -f /app/storage/logs/laravel.log`
- Restart service dari Dashboard

### APP_KEY Empty
- Generate baru: `php artisan key:generate`
- Copy value dari `.env`
- Update environment variable di Render

---

## 📊 Monitoring & Maintenance

### Logs di Render
- Dashboard → Web Service → Logs

### Backup Database
1. Dashboard → MySQL → Backup tab
2. Download backup file

### Update Aplikasi
```bash
# Push ke GitHub
git push origin main

# Render akan auto-deploy jika setting "Auto-Deploy" aktif
# Atau manual trigger di Dashboard → Deploys → Trigger Deploy
```

---

## 💡 Tips & Best Practices

✅ **Recommended:**
- Set `APP_DEBUG=false` di production
- Gunakan strong database password
- Enable HTTPS (Render auto-enable)
- Setup error monitoring (Sentry, Rollbar)
- Regular database backups
- Monitor resource usage

❌ **Avoid:**
- Commit `.env` ke repository
- Hardcode sensitive data
- Disable HTTPS
- Neglect logs monitoring

---

## 📞 Support & Resources

- **Render Docs**: https://render.com/docs
- **Laravel Docs**: https://laravel.com/docs/8.x
- **Docker Docs**: https://docs.docker.com/
- **Render Support**: support@render.com

---

## ✨ Testing Deployment

Setelah live, test dengan:

```bash
# Health check
curl https://your-domain.onrender.com/api/health

# API test
curl https://your-domain.onrender.com/api/your-endpoint

# Check status
curl https://your-domain.onrender.com/
```

---

**Status**: ✅ Ready for Production
**Last Updated**: May 2026

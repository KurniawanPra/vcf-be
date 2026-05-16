# Docker Build Fixed - Alpine Linux

## Perbaikan yang Dilakukan:

### 1. Base Image
- ❌ `php:8.1-fpm` + `debian` (apt-get) → 🔧 `php:8.1-fpm-alpine`
- Alpine Linux lebih lightweight, lebih cepat build, dan paket dependencies lebih stabil
- Mengatasi error saat install runtime dependencies

### 2. Package Manager
- Ganti dari `apt-get` → `apk add --no-cache`
- Nama package disesuaikan untuk Alpine:
  - `libpng6` → `libpng`
  - `libjpeg62-turbo` → `jpeg`
  - `libonig5` → `oniguruma`

### 3. Nginx Configuration Path
- Ganti dari `/etc/nginx/sites-available/default` → `/etc/nginx/http.d/default.conf`
- Alpine menggunakan struktur direktori berbeda

### 4. Start Script
- Update untuk Alpine (berbeda dari Debian)
- Tambah signal handling untuk graceful shutdown
- Tambah retry logic untuk command yang bisa fail

### 5. Docker Compose
- Tambah `condition: service_healthy` untuk dependency
- Optimize healthcheck untuk database

---

## 🚀 Cara Rebuild dan Test:

### Clean Rebuild (Delete Cache)
```bash
# Stop running containers
docker-compose down

# Remove old images
docker image rm vcf-app vcf-api
# atau
docker system prune -a

# Clean rebuild
docker-compose build --no-cache
docker-compose up -d

# Check logs
docker-compose logs -f app
```

### Quick Rebuild (With Cache)
```bash
docker-compose build
docker-compose up -d
```

### Run Setup
```bash
# Setup database
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan db:seed

# Or bisa langsung jalan otomatis via start.sh
```

### Verify Running
```bash
# Check containers
docker ps

# Check logs
docker-compose logs app

# Test API
curl http://localhost:8080/

# PhpMyAdmin
http://localhost:8081
```

---

## 📝 Size Comparison

| Base Image | Size |
|-----------|------|
| debian-based | ~600MB |
| alpine-based | ~150MB |
| Final image | ~300MB |

Alpine lebih kecil, lebih cepat deploy, terutama untuk Render!

---

## ✅ Troubleshooting

### Error: "operation not permitted"
```bash
# Windows fix
docker-compose exec app chmod +x start.sh
```

### Nginx not starting
```bash
# Check nginx config
docker-compose exec app nginx -t
```

### Database connection timeout
```bash
# Check database health
docker-compose logs db

# Rebuild with more wait time
# Edit start.sh, ubah loop dari 60 ke 90
```

---

**Status**: ✅ Ready to Deploy
**Last Updated**: May 2026
**Image**: php:8.1-fpm-alpine

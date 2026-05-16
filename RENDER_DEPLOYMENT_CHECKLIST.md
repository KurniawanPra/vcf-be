# VCF API - Deployment Checklist untuk Render.com

## ✅ Pre-Deployment Checklist (Local)

### Setup Lokal
- [ ] Clone/access project
- [ ] `php artisan key:generate` (jika belum ada APP_KEY)
- [ ] `cp .env.example .env`
- [ ] Update `.env` dengan APP_KEY dari step sebelumnya

### Test Docker Lokal
- [ ] `docker-compose up -d` (mulai containers)
- [ ] `docker-compose exec app php artisan migrate --force`
- [ ] Test API di http://localhost:8080
- [ ] `docker-compose down` (stop containers)

### GitHub Preparation
- [ ] Update `.gitignore` (pastikan `.env` tidak di-commit)
- [ ] `git add Dockerfile .dockerignore docker-compose.yml nginx.conf start.sh render.yaml DOCKER_DEPLOYMENT.md`
- [ ] `git commit -m "Add Docker configuration for Render deployment"`
- [ ] `git push origin main`

---

## 🚀 Deployment Steps (Render.com)

### Step 1: Setup Render Account
- [ ] Buka https://render.com
- [ ] Sign up dengan GitHub
- [ ] Authorize Render access

### Step 2: Deploy Database (MySQL)
- [ ] Dashboard → New → MySQL
- [ ] **Name**: `vcf-db`
- [ ] **Database Name**: `vcf_production`
- [ ] **Username**: `vcf_user`
- [ ] **Region**: Singapore (atau terdekat)
- [ ] **Plan**: Free (atau sesuai kebutuhan)
- [ ] Click "Create Database"
- [ ] Tunggu deployment selesai
- [ ] **CATAT**: Host, Username, Password dari notification email

### Step 3: Deploy Web Service
- [ ] Dashboard → New → Web Service
- [ ] Select repository: vcf-github-rinko/be
- [ ] **Name**: `vcf-api`
- [ ] **Environment**: Docker
- [ ] **Region**: Singapore
- [ ] **Plan**: Free (atau sesuai kebutuhan)
- [ ] Click "Create Web Service"
- [ ] Tunggu build selesai (~5-10 menit)

### Step 4: Configure Environment Variables
Di Web Service settings → Environment:

```
APP_ENV=production
APP_DEBUG=false
LOG_CHANNEL=errorlog
DB_CONNECTION=mysql
DB_HOST=[dari MySQL notification]
DB_PORT=3306
DB_DATABASE=vcf_production
DB_USERNAME=vcf_user
DB_PASSWORD=[dari MySQL notification]
APP_KEY=base64:[generate dengan php artisan key:generate]
SANCTUM_STATEFUL_DOMAINS=vcf-api.onrender.com
SESSION_DOMAIN=.onrender.com
```

- [ ] Update setiap variable
- [ ] Click "Save"
- [ ] Wait untuk re-deploy dengan env baru

### Step 5: Run Initial Setup
- [ ] Buka Web Service → Shell
- [ ] Jalankan: `php artisan migrate --force`
- [ ] Jalankan: `php artisan db:seed` (jika ada)
- [ ] Jalankan: `php artisan config:cache`
- [ ] Jalankan: `php artisan route:cache`

---

## 🔍 Post-Deployment Verification

### Check Deployment Status
- [ ] Dashboard → Web Service → Logs
- [ ] Lihat ada error atau tidak

### Test API Endpoints
- [ ] `curl https://vcf-api.onrender.com/` (main page)
- [ ] `curl https://vcf-api.onrender.com/api/` (API endpoint)
- [ ] Test endpoint tertentu sesuai kebutuhan

### Database Connection
- [ ] Verify database connection di logs
- [ ] Check migrations ran successfully
- [ ] Query database dari Render dashboard

### SSL/HTTPS
- [ ] Verify HTTPS is enabled (Render default)
- [ ] Test dengan HTTPS URL

---

## 🛡️ Security Checklist

### Credentials
- [ ] APP_KEY tidak di-commit ke Git
- [ ] Database password aman di environment variable
- [ ] Tidak ada sensitive data di logs

### Application Security
- [ ] `APP_DEBUG=false` di production
- [ ] CORS configured correctly di `.env`
- [ ] `SANCTUM_STATEFUL_DOMAINS` sesuai dengan domain

### Database Security
- [ ] Strong password untuk database user
- [ ] Backup database enabled
- [ ] Limited database access (Render handle ini)

---

## 📊 Monitoring & Maintenance

### Daily Checks
- [ ] Monitor logs untuk errors
- [ ] Check API response time
- [ ] Database disk usage

### Weekly Maintenance
- [ ] Review error logs
- [ ] Check for Laravel updates
- [ ] Backup database

### Monthly Tasks
- [ ] Update dependencies
- [ ] Performance review
- [ ] Security updates

---

## 🆘 Troubleshooting Quick Fixes

| Problem | Solution |
|---------|----------|
| **502 Bad Gateway** | Restart Web Service atau check logs |
| **Database Connection Failed** | Verify DB credentials di env vars |
| **APP_KEY Empty** | Generate baru & update env var |
| **Migrations Failed** | Check logs, verify DB permissions |
| **Slow Response** | Upgrade plan atau optimize code |

---

## 📞 Emergency Contacts

- **Render Support**: support@render.com
- **Laravel Documentation**: https://laravel.com/docs/8.x
- **Render Docs**: https://render.com/docs/

---

## 📋 Quick Reference URLs

- **Render Dashboard**: https://dashboard.render.com
- **Web Service URL**: https://vcf-api.onrender.com
- **GitHub Repository**: https://github.com/[your-org]/vcf-github-rinko
- **API Documentation**: [setup your docs]

---

**Status**: Ready to Deploy ✅
**Last Updated**: May 2026
**Laravel Version**: 8.x
**PHP Version**: 8.1
**Database**: MySQL 8.0

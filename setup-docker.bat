@echo off
REM Colors and setup for Windows batch
REM VCF API - Docker Local Setup Script

echo.
echo ========================================
echo  VCF API - Docker Local Setup
echo ========================================
echo.

REM Check Docker installation
docker --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Docker is not installed. Please install Docker Desktop first.
    pause
    exit /b 1
)
echo [OK] Docker is installed

REM Check Docker Compose installation
docker-compose --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Docker Compose is not installed.
    pause
    exit /b 1
)
echo [OK] Docker Compose is installed
echo.

REM Create .env file if not exists
if not exist .env (
    echo [INFO] Creating .env file from .env.example...
    copy .env.example .env
    echo [OK] .env file created
    echo.
)

REM Build and start containers
echo [INFO] Building and starting Docker containers...
docker-compose up -d

if errorlevel 1 (
    echo [ERROR] Failed to start containers
    pause
    exit /b 1
)
echo [OK] Containers started
echo.

REM Wait for database
echo [INFO] Waiting for database to be ready...
timeout /t 10 /nobreak

REM Install PHP dependencies
echo [INFO] Installing PHP dependencies...
docker-compose exec -T app composer install

if errorlevel 1 (
    echo [WARNING] Composer install might have issues
)
echo.

REM Generate APP_KEY
echo [INFO] Generating APP_KEY...
docker-compose exec -T app php artisan key:generate
echo [OK] APP_KEY generated
echo.

REM Run migrations
echo [INFO] Running database migrations...
docker-compose exec -T app php artisan migrate --force
echo [OK] Migrations completed
echo.

REM Display information
echo.
echo ========================================
echo  SETUP COMPLETED SUCCESSFULLY!
echo ========================================
echo.
echo Service URLs:
echo   API:         http://localhost:8080
echo   PhpMyAdmin:  http://localhost:8081
echo.
echo Database Credentials:
echo   Host:     db
echo   Port:     3306
echo   Username: vcf_user
echo   Password: vcf_password
echo.
echo Useful Commands:
echo   View logs:       docker-compose logs -f app
echo   Run artisan:     docker-compose exec app php artisan [command]
echo   Stop services:   docker-compose down
echo   Restart:         docker-compose restart
echo.
echo ========================================
echo.
pause

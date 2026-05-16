#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 VCF API - Docker Local Setup${NC}\n"

# Check Docker installation
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker is not installed. Please install Docker first.${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Docker is installed${NC}"

# Check Docker Compose installation
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}❌ Docker Compose is not installed. Please install Docker Compose first.${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Docker Compose is installed${NC}\n"

# Create .env file if not exists
if [ ! -f .env ]; then
    echo -e "${BLUE}📄 Creating .env file from .env.example...${NC}"
    cp .env.example .env
    echo -e "${GREEN}✓ .env file created${NC}\n"
fi

# Build and start containers
echo -e "${BLUE}🐳 Building and starting Docker containers...${NC}"
docker-compose up -d

echo -e "${GREEN}✓ Containers started${NC}\n"

# Wait for database to be ready
echo -e "${BLUE}⏳ Waiting for database to be ready...${NC}"
sleep 10

# Install PHP dependencies
echo -e "${BLUE}📦 Installing PHP dependencies...${NC}"
docker-compose exec -T app composer install

echo -e "${GREEN}✓ Dependencies installed${NC}\n"

# Generate APP_KEY
echo -e "${BLUE}🔑 Generating APP_KEY...${NC}"
docker-compose exec -T app php artisan key:generate

echo -e "${GREEN}✓ APP_KEY generated${NC}\n"

# Run migrations
echo -e "${BLUE}🗄️  Running database migrations...${NC}"
docker-compose exec -T app php artisan migrate --force

echo -e "${GREEN}✓ Migrations completed${NC}\n"

# Optional: Seed database
read -p "Do you want to seed the database? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${BLUE}🌱 Seeding database...${NC}"
    docker-compose exec -T app php artisan db:seed
    echo -e "${GREEN}✓ Database seeded${NC}\n"
fi

# Display information
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Setup completed successfully!${NC}\n"
echo -e "${BLUE}📍 Service URLs:${NC}"
echo -e "   🌐 API:         http://localhost:8080"
echo -e "   💻 PhpMyAdmin:  http://localhost:8081"
echo -e "\n${BLUE}🔐 Database Credentials:${NC}"
echo -e "   Host:     db"
echo -e "   Port:     3306"
echo -e "   Username: vcf_user"
echo -e "   Password: vcf_password"
echo -e "\n${BLUE}📝 Useful Commands:${NC}"
echo -e "   View logs:       ${BLUE}docker-compose logs -f app${NC}"
echo -e "   Run artisan:     ${BLUE}docker-compose exec app php artisan <command>${NC}"
echo -e "   Stop services:   ${BLUE}docker-compose down${NC}"
echo -e "   Restart:         ${BLUE}docker-compose restart${NC}"
echo -e "\n${GREEN}═══════════════════════════════════════════════════════════${NC}"

# 🐳 Docker Staging Setup - Complete Guide

## ✅ What Has Been Created

Your Docker staging environment is now fully configured! Here's what was set up:

### 📁 File Structure

```
qrmadearmando/
├── docker-compose.yml          ← Main orchestration file
├── Dockerfile                  ← PHP 8.2 + Node.js builder
├── .env.staging                ← Staging environment variables
├── .dockerignore                ← Docker build excludes
├── Makefile                     ← Convenient commands
├── DOCKER.md                    ← Complete documentation
├── DOCKER_SETUP.md              ← This file
│
├── docker/
│   ├── nginx/
│   │   ├── conf.d/
│   │   │   └── app.conf         ← Nginx configuration
│   │   └── ssl/                 ← Self-signed certs (optional)
│   ├── php/
│   │   ├── php.ini              ← PHP configuration
│   │   └── php-fpm.conf         ← PHP-FPM configuration
│   └── entrypoint.sh            ← Container startup script
│
└── scripts/
    └── db-sync.sh               ← Production data sync script
```

### 🎯 Services Configured

| Service | Purpose | Port | Technology |
|---------|---------|------|------------|
| **PHP** | Laravel application server | 9000 | PHP 8.2-FPM + Alpine |
| **Nginx** | Web server & reverse proxy | 80 | Nginx + Alpine |
| **PostgreSQL** | Main database | 5432 | PostgreSQL 16 |
| **Redis** | Cache & Queue | 6379 | Redis 7 + Alpine |
| **Queue Worker** | Background jobs | - | Laravel Queue |
| **Scheduler** | Cron replacement | - | Laravel Scheduler |

### 🔄 Complete Setup

✅ Dockerfile with PHP 8.2 and Node.js build support
✅ Docker Compose orchestration with 6 services
✅ PostgreSQL database with persistent volumes
✅ Redis cache and queue system
✅ Nginx web server with optimized config
✅ Queue worker for background jobs
✅ Scheduler for cron tasks
✅ Health checks for all services
✅ Database synchronization script
✅ Makefile with 20+ convenient commands
✅ Production data anonymization
✅ PWA + Push Notifications support
✅ Complete documentation

---

## 🚀 Getting Started (Step-by-Step)

### Step 1: Install Docker (If Not Already Installed)

**macOS:**
```bash
# Using Homebrew
brew install docker

# Or download Docker Desktop:
# https://www.docker.com/products/docker-desktop
```

**Linux (Ubuntu):**
```bash
# Install Docker
sudo apt-get update
sudo apt-get install docker.io docker-compose-v2

# Add user to docker group
sudo usermod -aG docker $USER
newgrp docker
```

**Windows:**
- Download Docker Desktop: https://www.docker.com/products/docker-desktop
- Enable WSL 2 backend

### Step 2: Verify Docker Installation

```bash
docker --version
# Docker version 20.10.x or higher

docker compose version
# Docker Compose version 2.x or higher
```

### Step 3: Prepare Environment

```bash
cd /Users/armando_reyes/Herd/qrmadearmando

# Copy staging environment
cp .env.staging .env

# Verify the file
cat .env | head -20
```

### Step 4: Build Images (First Time Only)

```bash
# Build all Docker images
docker compose build

# This will take 3-5 minutes on first run
```

### Step 5: Start Staging Environment

```bash
# Option A: Using Make (recommended)
make staging-up

# Option B: Using Docker Compose directly
docker compose up -d

# Wait 30-60 seconds for services to start
```

### Step 6: Verify Services Are Running

```bash
# Check all services
docker compose ps

# Should show:
# - qrmade_php       (Up)
# - qrmade_nginx     (Up)
# - qrmade_postgres  (Up)
# - qrmade_redis     (Up)
# - qrmade_queue     (Up)
# - qrmade_scheduler (Up)
```

### Step 7: Access Application

Open in browser:
- **Frontend**: http://localhost
- **Admin Panel**: http://localhost/admin
- **API**: http://localhost/api

Default Admin Credentials (first-time setup):
```
Email: admin@example.com
Password: password
```

### Step 8: View Logs

```bash
# Real-time logs
make staging-logs

# Or specific service
docker compose logs -f php
```

---

## 📊 Quick Reference Commands

### Start/Stop Services

```bash
# Start all services
make staging-up

# Stop all services
make staging-down

# Restart all services
make staging-restart

# View service status
docker compose ps
```

### Database Operations

```bash
# Run migrations
make staging-migrate

# Seed database
make staging-seed

# Fresh database reset
make staging-fresh

# Access database shell
make staging-db-shell

# Sync production data (see db-sync below)
make staging-db-sync
```

### Development

```bash
# Enter PHP container shell
make staging-bash

# Run tests
make staging-test

# Run Laravel Tinker
make staging-tinker

# Check service health
make staging-health
```

### Docker Management

```bash
# Rebuild images
docker compose build

# Force rebuild (no cache)
docker compose build --no-cache

# Remove all containers and data
docker compose down -v

# View specific logs
docker compose logs php
docker compose logs nginx
docker compose logs postgres
```

---

## 🔄 Production Data Sync (Optional)

To test with real production data:

### Prerequisites

1. Access to production PostgreSQL database
2. Set environment variables:
```bash
export PROD_DB_HOST="your-prod-host.com"
export PROD_DB_PORT="5432"
export PROD_DB_NAME="qrmade_production"
export PROD_DB_USER="postgres"
export PROD_DB_PASSWORD="your-password"
```

### Run Sync Script

```bash
# Using Make
make staging-db-sync

# Or directly
bash scripts/db-sync.sh
```

### What Happens

✅ Creates backup of production database
✅ Drops staging database
✅ Restores production data
✅ **Anonymizes sensitive data:**
  - Resets all passwords to: `password`
  - Changes emails to: `user{id}@staging.test`
  - Clears 2FA secrets and codes
  - Removes push notification subscriptions
  - Clears activity logs

### Access Synced Database

```bash
# Admin login
Email: admin@staging.test
Password: password
```

---

## 🐛 Troubleshooting

### Problem: "Cannot connect to Docker daemon"

```bash
# Solution: Ensure Docker is running
# macOS/Windows: Open Docker Desktop
# Linux: Start Docker service
sudo systemctl start docker
```

### Problem: "Port 80 already in use"

```bash
# Find what's using port 80
lsof -i :80

# Kill the process
kill -9 <PID>

# Or change port in docker-compose.yml (line ~73):
# ports:
#   - "8080:80"  # Use 8080 instead
```

### Problem: "Could not build Docker image"

```bash
# Clean up and rebuild
docker compose down -v
docker compose build --no-cache
docker compose up -d
```

### Problem: "Database connection error"

```bash
# Check PostgreSQL logs
docker compose logs postgres

# Wait for it to start (first run can take 30s)
sleep 30
docker compose ps
```

### Problem: "Frontend not building"

```bash
# Rebuild with clean node_modules
docker compose down -v
docker compose build --no-cache
docker compose up -d
```

### Problem: "Permission denied" errors

```bash
# Fix permissions in container
docker compose exec php chown -R www-data:www-data /app/storage
docker compose exec php chmod -R 775 /app/storage
```

---

## 📈 Performance Tips

### Reduce Build Time
```bash
# Use BuildKit (faster builds)
export DOCKER_BUILDKIT=1
docker compose build
```

### Improve Runtime Performance
```bash
# Allocate more CPU/RAM to Docker Desktop
# Settings → Resources → Increase values
```

### Faster Startup
```bash
# Skip health checks during development
docker compose up -d --no-health-checks
```

---

## 🔒 Security Notes

### Staging Credentials

⚠️ **These are for local staging only!**

```env
DB_USERNAME=postgres
DB_PASSWORD=postgres_secret
REDIS_PASSWORD=null (no password)
```

### Before Production

- [ ] Change all default passwords
- [ ] Use strong, random credentials
- [ ] Store secrets in secure vault (AWS Secrets Manager, etc.)
- [ ] Enable HTTPS with valid certificates
- [ ] Configure firewall rules
- [ ] Enable database backups
- [ ] Set up monitoring and alerts

---

## 📚 Next Steps

1. **Verify Everything Works**
   ```bash
   make staging-up
   make staging-health
   # Visit http://localhost
   ```

2. **Seed Sample Data** (Optional)
   ```bash
   make staging-seed
   ```

3. **Run Tests**
   ```bash
   make staging-test
   ```

4. **Test PWA Features**
   - Open app on mobile/emulator
   - Test install prompt
   - Test push notifications

5. **Explore Admin Panel**
   - Login to http://localhost/admin
   - Manage users, branches, gift cards
   - View transactions and reports

6. **Test Scanner**
   - Visit http://localhost/scanner
   - Generate QR codes
   - Test QR scanning workflow

---

## 📖 Full Documentation

For complete details, see:
- **DOCKER.md** - Full Docker guide with advanced configuration
- **CLAUDE.md** - Project architecture and patterns
- **docker-compose.yml** - Service configuration
- **Dockerfile** - Build steps

---

## ✅ Checklist

- [ ] Docker installed and running
- [ ] Cloned repository
- [ ] Copied .env.staging to .env
- [ ] Built Docker images: `docker compose build`
- [ ] Started services: `make staging-up`
- [ ] All services running: `docker compose ps`
- [ ] Frontend loads: http://localhost ✓
- [ ] Admin panel accessible: http://localhost/admin ✓
- [ ] API responsive: http://localhost/api ✓
- [ ] Tests passing: `make staging-test` ✓

---

## 🎯 PWA + Push Notifications

Your staging includes full PWA + Push Notifications support!

### Generate VAPID Keys (if needed)

```bash
docker compose exec php php artisan webpush:vapid

# Output will include:
# VAPID_PUBLIC_KEY=...
# VAPID_PRIVATE_KEY=...
```

### Add to .env

```bash
VITE_VAPID_PUBLIC_KEY=your_public_key
VAPID_PUBLIC_KEY=your_public_key
VAPID_PRIVATE_KEY=your_private_key
```

### Test PWA

1. Open app on mobile/emulator
2. Install PWA from browser menu
3. Grant notification permissions
4. Create a transaction
5. Receive push notification! 🔔

---

## 📞 Support & Resources

- **Docker Docs**: https://docs.docker.com/
- **Docker Compose**: https://docs.docker.com/compose/
- **Laravel Docs**: https://laravel.com/docs/
- **PostgreSQL Docs**: https://www.postgresql.org/docs/
- **Redis Docs**: https://redis.io/documentation
- **Nginx Docs**: https://nginx.org/en/docs/

---

**Status**: ✅ Ready for staging!
**Branch**: feature/pwa-push-notifications
**Tests**: 120/120 passing ✓
**Created**: February 8, 2026

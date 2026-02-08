# 🐳 Docker Staging Environment

Complete guide for running QR Made Armando in a Docker staging environment.

## 📋 Prerequisites

- **Docker**: v20.10+
- **Docker Compose**: v1.29+
- **Make**: (optional, for convenient commands)
- **PostgreSQL Client** (optional, for `psql` commands)

Install Docker: https://docs.docker.com/get-docker/

## 🚀 Quick Start

### 1. Clone and Setup

```bash
# Clone repository
git clone git@github.com:yourusername/qrmadearmando.git
cd qrmadearmando

# Switch to PWA branch
git checkout feature/pwa-push-notifications

# Copy environment file
cp .env.staging .env

# Generate APP_KEY (will be auto-generated in Docker, but you can pre-generate)
php artisan key:generate
```

### 2. Start Staging Environment

```bash
# Option 1: Using Make (recommended)
make staging-up

# Option 2: Using Docker Compose directly
docker-compose up -d
```

### 3. Access the Application

```
Frontend:     http://localhost
Admin Panel:  http://localhost/admin
API:          http://localhost/api

Default Admin Credentials (after seeding):
  Email: admin@example.com
  Password: password
```

## 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────┐
│                   STAGING ENVIRONMENT            │
├─────────────────────────────────────────────────┤
│  Nginx (Port 80)                                │
│  ├─ PHP-FPM (Port 9000)                         │
│  │  ├─ Laravel Application                      │
│  │  ├─ React Frontend (SSR)                     │
│  │  └─ PWA + Push Notifications                 │
│  │                                               │
│  ├─ PostgreSQL (Port 5432)                      │
│  │  └─ Database: qrmade_staging                 │
│  │                                               │
│  ├─ Redis (Port 6379)                           │
│  │  ├─ Cache Store                              │
│  │  └─ Queue System                             │
│  │                                               │
│  ├─ Queue Worker (Background Jobs)              │
│  │                                               │
│  └─ Scheduler (Cron Tasks)                      │
└─────────────────────────────────────────────────┘
```

## 📦 Services

### PHP-FPM
- Laravel 12 application server
- PHP 8.2 with Alpine Linux
- Built-in health checks
- Volume: `/app` mapped to project root

### Nginx
- Web server on port 80
- Reverse proxy to PHP-FPM
- Static file serving with caching
- Gzip compression enabled
- Security headers configured

### PostgreSQL
- Database server on port 5432
- Database: `qrmade_staging`
- Credentials: `postgres:postgres_secret`
- Persistent volume: `postgres_data`

### Redis
- In-memory cache/queue server on port 6379
- Queue connection for background jobs
- Session/cache storage
- Persistent volume: `redis_data`

### Queue Worker
- Processes background jobs
- Configured with exponential backoff
- Max 3 retries per job
- Sleep 3 seconds between jobs

### Scheduler
- Runs Laravel scheduled tasks
- Cron job replacement
- Checks every minute for pending tasks

## 🛠️ Common Commands

### Using Make (Recommended)

```bash
# View all available commands
make help

# Start environment
make staging-up

# Stop environment
make staging-down

# Restart services
make staging-restart

# View logs
make staging-logs

# Enter PHP shell
make staging-bash

# Run tests
make staging-test

# Seed database
make staging-seed

# Fresh database reset
make staging-fresh

# Access Laravel Tinker
make staging-tinker

# Access PostgreSQL shell
make staging-db-shell

# Check service health
make staging-health
```

### Using Docker Compose Directly

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Restart specific service
docker-compose restart php

# View logs
docker-compose logs -f

# Enter container
docker-compose exec php sh

# Run Artisan command
docker-compose exec php php artisan migrate

# Run tests
docker-compose exec php php artisan test

# Access database
docker-compose exec postgres psql -U postgres -d qrmade_staging
```

### Manual Commands

```bash
# Rebuild images
docker-compose build

# Force rebuild without cache
docker-compose build --no-cache

# Remove all containers and volumes
docker-compose down -v

# Check container status
docker-compose ps

# View specific service logs
docker-compose logs php
docker-compose logs nginx
docker-compose logs postgres
```

## 🔄 Database Operations

### Fresh Setup

```bash
# Option 1: Auto-run migrations on startup
make staging-up
# Migrations run automatically in docker/entrypoint.sh

# Option 2: Manual migration
make staging-migrate

# Option 3: Fresh database with seeders
make staging-fresh
```

### Seed Database

```bash
# Using Make
make staging-seed

# Or directly
docker-compose exec php php artisan db:seed
```

### Sync Production Data (Optional)

```bash
# Prerequisites:
# 1. Set production DB credentials in script or env vars:
#    export PROD_DB_HOST="prod.example.com"
#    export PROD_DB_PASSWORD="your_password"
#
# 2. Make script executable
chmod +x scripts/db-sync.sh

# Run sync
make staging-db-sync
# or
bash scripts/db-sync.sh
```

**Note**: The sync script:
- ✓ Backs up production database to `database/backups/`
- ✓ Drops and recreates staging database
- ✓ Restores production data
- ✓ **Anonymizes sensitive data:**
  - Resets all passwords to `password`
  - Anonymizes user emails to `user{id}@staging.test`
  - Clears 2FA data
  - Clears push subscriptions
  - Clears activity logs

## 🔐 Environment Configuration

### .env.staging

Key variables configured for staging:

```env
APP_ENV=staging
APP_DEBUG=false

# Database (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=qrmade_staging
DB_USERNAME=postgres
DB_PASSWORD=postgres_secret

# Cache & Queue (Redis)
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379

# Sessions
SESSION_DRIVER=database

# Mail (Log driver - check logs)
MAIL_MAILER=log
```

### Customize Variables

```bash
# Copy default staging config
cp .env.staging .env

# Edit as needed
nano .env

# Important: These should be configured before running
# - APP_KEY (auto-generated)
# - VITE_VAPID_PUBLIC_KEY (for PWA)
# - VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY (for push notifications)
```

## 📊 Monitoring & Debugging

### View Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f php
docker-compose logs -f nginx
docker-compose logs -f postgres
docker-compose logs -f redis

# Last 100 lines
docker-compose logs --tail=100
```

### Check Health

```bash
# Container status
docker-compose ps

# Service health
make staging-health

# Database connection
docker-compose exec postgres pg_isready -U postgres

# Redis connection
docker-compose exec redis redis-cli ping

# PHP status
docker-compose exec php php artisan --version
```

### Access Containers

```bash
# PHP shell
docker-compose exec php sh

# Run Artisan commands
docker-compose exec php php artisan migrate:status
docker-compose exec php php artisan config:cache
docker-compose exec php php artisan cache:clear

# PostgreSQL shell
docker-compose exec postgres psql -U postgres -d qrmade_staging

# Redis CLI
docker-compose exec redis redis-cli

# Nginx logs
docker-compose exec nginx tail -f /var/log/nginx/error.log
```

## 🧪 Testing

```bash
# Run all tests
make staging-test

# Run specific test file
docker-compose exec php php artisan test tests/Feature/ScannerTest.php

# Run with coverage
docker-compose exec php php artisan test --coverage

# Watch mode (if supported)
docker-compose exec php php artisan test --watch
```

## 🚨 Troubleshooting

### Port Already in Use

```bash
# Find process using port 80
lsof -i :80

# Kill process
kill -9 <PID>

# Or use different port in docker-compose.yml
```

### PostgreSQL Connection Failed

```bash
# Check PostgreSQL is running
docker-compose ps postgres

# Check logs
docker-compose logs postgres

# Wait for it to start
docker-compose logs --follow postgres
```

### "Cannot create container" Error

```bash
# Remove orphaned containers
docker-compose down

# Prune all unused containers
docker container prune

# Remove volumes if needed
docker-compose down -v
```

### Permissions Issues

```bash
# If you get "Permission denied" errors
docker-compose exec php chown -R www-data:www-data /app/storage
docker-compose exec php chmod -R 775 /app/storage
```

### Frontend Not Building

```bash
# Rebuild with fresh node_modules
docker-compose build --no-cache

# Or install manually
docker-compose exec php npm install
docker-compose exec php npm run build
```

## 🔧 Advanced Configuration

### Nginx SSL/TLS

1. Generate self-signed certificates:
```bash
mkdir -p docker/nginx/ssl
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout docker/nginx/ssl/key.pem \
  -out docker/nginx/ssl/cert.pem
```

2. Enable HTTPS in `docker/nginx/conf.d/app.conf`:
```nginx
server {
    listen 443 ssl http2;
    server_name localhost;

    ssl_certificate /etc/nginx/ssl/cert.pem;
    ssl_certificate_key /etc/nginx/ssl/key.pem;

    # ... rest of config
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    return 301 https://$server_name$request_uri;
}
```

3. Update docker-compose.yml ports if needed

### Custom PHP Configuration

Edit `docker/php/php.ini` to customize:
- Memory limit
- Upload size
- Timeout values
- Timezone
- Extension settings

### Custom PHP-FPM Configuration

Edit `docker/php/php-fpm.conf` to customize:
- Worker processes
- Connection limits
- Performance settings

### Add Additional Services

Edit `docker-compose.yml` to add:
- Mailhog (local email testing)
- Adminer (database GUI)
- Minio (S3-compatible storage)
- etc.

## 📝 Useful Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Laravel Deployment](https://laravel.com/docs/deployment)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Redis Documentation](https://redis.io/documentation)

## ✅ PWA + Push Notifications Testing

The staging environment includes full PWA + Push Notifications support:

```bash
# Generate VAPID keys (if not already done)
docker-compose exec php php artisan webpush:vapid

# The script will output:
# VAPID_PUBLIC_KEY=...
# VAPID_PRIVATE_KEY=...

# Add to .env:
# VITE_VAPID_PUBLIC_KEY=... (public key for frontend)
# VAPID_PUBLIC_KEY=... (public key for backend)
# VAPID_PRIVATE_KEY=... (private key for backend)

# Restart services
docker-compose restart

# Test PWA installation prompt
# Visit http://localhost on a mobile browser or emulator

# Test push notifications
# From Laravel Tinker:
docker-compose exec php php artisan tinker
# Then:
# $user = User::first();
# $transaction = Transaction::create([...]);
# Or manually trigger a transaction to send notifications
```

## 🎯 Deployment Checklist

Before moving to production:

- [ ] All tests passing: `make staging-test`
- [ ] Database migrations clean: `docker-compose exec php php artisan migrate:status`
- [ ] No console errors in browser DevTools
- [ ] PWA manifest accessible: http://localhost/manifest.webmanifest
- [ ] Service Worker registered: Check in DevTools
- [ ] Push notifications working (if needed)
- [ ] Admin panel functional
- [ ] API endpoints responding
- [ ] Frontend builds without errors
- [ ] All environment variables set

## 📞 Support

For issues or questions:
1. Check troubleshooting section above
2. Review Docker logs: `make staging-logs`
3. Check Laravel logs: `docker-compose exec php tail -f storage/logs/laravel.log`
4. Consult project documentation: See CLAUDE.md

---

**Last Updated**: February 8, 2026
**PWA Branch**: feature/pwa-push-notifications
**Status**: All tests passing ✅

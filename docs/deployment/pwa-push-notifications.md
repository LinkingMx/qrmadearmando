# PWA + Push Notifications Deployment Guide

**Version**: 1.0
**Last Updated**: 2026-02-08
**Status**: Production Ready

## Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [VAPID Key Management](#vapid-key-management)
3. [Environment Configuration](#environment-configuration)
4. [Service Worker Setup](#service-worker-setup)
5. [Queue Worker Configuration](#queue-worker-configuration)
6. [Monitoring & Logging](#monitoring--logging)
7. [Performance Optimization](#performance-optimization)
8. [Rollback Procedure](#rollback-procedure)
9. [Post-Deployment Verification](#post-deployment-verification)

---

## Pre-Deployment Checklist

**Complete before deploying to production:**

### Backend Setup

- [ ] **Dependencies installed**: `composer install` completed
- [ ] **Migrations run**: `php artisan migrate` on production database
- [ ] **Queue worker configured**: Supervisor/systemd running `php artisan queue:work`
- [ ] **VAPID keys generated**: `php artisan webpush:vapid` executed locally
- [ ] **VAPID keys in secrets**: Keys added to production environment secrets manager (NOT `.env` file)
- [ ] **Email verified requirement**: `verified` middleware on push subscription endpoints
- [ ] **HTTPS enforced**: Production server redirects HTTP to HTTPS

### Frontend Setup

- [ ] **Build successful**: `npm run build` completes without errors
- [ ] **Service Worker generated**: `/dist/sw.js` exists after build
- [ ] **Manifest generated**: `/dist/manifest.webmanifest` exists after build
- [ ] **Icons deployed**: `/public/icons/icon-*.png` files uploaded
- [ ] **VITE_VAPID_PUBLIC_KEY set**: Frontend environment variable configured
- [ ] **HTTPS certificate valid**: SSL/TLS certificate not expired

### Testing

- [ ] **API endpoints tested**: Push subscribe/unsubscribe working
- [ ] **Push notifications sent**: Test transaction triggers notification
- [ ] **Service Worker registered**: Browser DevTools shows registered SW
- [ ] **Lighthouse PWA audit**: Score 90+ on staging
- [ ] **Real device testing**: Notifications work on iOS and Android

### Documentation

- [ ] **Rollback plan created**: Document prepared if things go wrong
- [ ] **Monitoring alerts configured**: Team notified of push failures
- [ ] **Support documentation ready**: Team can help users with issues
- [ ] **User communications sent**: Users informed of new feature

---

## VAPID Key Management

### Generating VAPID Keys

**One-time setup** (do this on a secure machine):

```bash
# Generate keys (creates new VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY)
php artisan webpush:vapid

# Output:
# VAPID_PUBLIC_KEY=BFp4+SJq7ythmVNxE...
# VAPID_PRIVATE_KEY=I7TKRj3l5xQzV...
# VAPID_SUBJECT=mailto:admin@selatravel.com
```

### Storing VAPID Keys

**CRITICAL**: Never commit VAPID keys to version control.

**Development** (`.env` file):

```env
VAPID_PUBLIC_KEY=BFp4+SJq7ythmVNxE...
VAPID_PRIVATE_KEY=I7TKRj3l5xQzV...
VAPID_SUBJECT=mailto:admin@selatravel.com
```

**Production** (Environment Secrets Manager):

```
AWS Secrets Manager:
  /qrmade/production/vapid_public_key
  /qrmade/production/vapid_private_key
  /qrmade/production/vapid_subject

Or in deployment platform:
  VAPID_PUBLIC_KEY=...
  VAPID_PRIVATE_KEY=...
  VAPID_SUBJECT=...
```

**Accessing in Production**:

```bash
# Via AWS Secrets Manager
aws secretsmanager get-secret-value --secret-id qrmade/production/vapid

# Via environment variables (platform-managed)
# Configured in CI/CD pipeline or deployment config
```

### VAPID Key Rotation

**Schedule**: Every 3-6 months (or if compromised)

#### Safe Rotation Procedure

```bash
# Step 1: Generate new keys on secure machine
php artisan webpush:vapid

# Step 2: Update production secrets to NEW keys
# (Don't commit to git)
aws secretsmanager update-secret \
  --secret-id qrmade/production/vapid \
  --secret-string '{"public":"BFp4...","private":"I7TK..."}'

# Step 3: Deploy application with new keys
# (No code changes, just environment update)
./deploy.sh production

# Step 4: Test push notifications work
php artisan tinker
>>> $user = User::first()
>>> $user->notify(new TestPushNotification())

# Step 5: Monitor for 24 hours
# Check logs for failures, verify notifications deliver

# Step 6: Archive old keys (keep for 30 days in case of emergency)
# Store in secure backup location
```

**What Happens**:

- Existing subscriptions still work with old keys (push service doesn't validate keys)
- New subscriptions created with new keys
- Old keys can be safely retired after 30 days
- No user action required

### Key Compromise Response

If VAPID keys are exposed:

```bash
# 1. IMMEDIATELY regenerate keys
php artisan webpush:vapid --force

# 2. Update production secrets
aws secretsmanager update-secret \
  --secret-id qrmade/production/vapid \
  --secret-string '{"public":"NEW...","private":"NEW..."}'

# 3. Deploy immediately
./deploy.sh production --force

# 4. Monitor for suspicious activity
tail -f storage/logs/laravel.log | grep "push"

# 5. Notify security team
# Create incident report
```

---

## Environment Configuration

### Required Environment Variables

**Backend** (`.env` or secrets manager):

```env
# Database
DB_CONNECTION=mysql
DB_HOST=db.example.com
DB_DATABASE=qrmade_prod
DB_USERNAME=qrmade_user
DB_PASSWORD=*** (use secrets manager)

# Queue
QUEUE_CONNECTION=database
QUEUE_MAX_ATTEMPTS=3
QUEUE_TIMEOUT=90

# VAPID Keys (REQUIRED)
VAPID_PUBLIC_KEY=BFp4+SJq7ythmVNxE...
VAPID_PRIVATE_KEY=I7TKRj3l5xQzV...
VAPID_SUBJECT=mailto:admin@selatravel.com

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=warning

# Security
APP_ENV=production
APP_DEBUG=false
FORCE_HTTPS=true
```

**Frontend** (`.env` or build-time):

```env
# VAPID Public Key (SAFE to expose)
VITE_VAPID_PUBLIC_KEY=BFp4+SJq7ythmVNxE...

# App Settings
VITE_APP_NAME="QR Made"
VITE_APP_URL=https://qrmade.example.com
```

### Validation

```bash
# Verify environment variables are set
php artisan config:show webpush

# Output should show:
# webpush.vapid.subject: mailto:admin@selatravel.com
# webpush.vapid.public_key: BFp4+...
# webpush.vapid.private_key: *** (hidden)
```

---

## Service Worker Setup

### Build Configuration

**Vite Config** (`vite.config.ts`):

```typescript
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [
    VitePWA({
      registerType: 'autoUpdate',  // Auto-update Service Worker

      // Workbox caching strategies
      workbox: {
        globPatterns: ['**/*.{js,css,html,png,svg,woff2,webp}'],

        runtimeCaching: [
          // Inertia pages: NetworkFirst (always try network)
          {
            urlPattern: ({ request }) => request.headers.get('x-inertia') === 'true',
            handler: 'NetworkFirst',
            options: {
              cacheName: 'inertia-pages',
              networkTimeoutSeconds: 10,
              expiration: {
                maxEntries: 20,
                maxAgeSeconds: 7 * 24 * 60 * 60,  // 7 days
              },
            },
          },

          // Images: CacheFirst (use cached, fallback to network)
          {
            urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp)$/,
            handler: 'CacheFirst',
            options: {
              cacheName: 'images',
              expiration: {
                maxEntries: 50,
                maxAgeSeconds: 30 * 24 * 60 * 60,  // 30 days
              },
            },
          },

          // API calls: NetworkFirst
          {
            urlPattern: /^https:\/\/[^/]+\/api\//,
            handler: 'NetworkFirst',
            options: {
              cacheName: 'api-calls',
              networkTimeoutSeconds: 5,
              expiration: {
                maxEntries: 20,
                maxAgeSeconds: 60,  // 1 minute
              },
            },
          },
        ],
      },

      // Production only
      devOptions: {
        enabled: process.env.NODE_ENV === 'production',
      },
    }),
  ],
})
```

### Deployment

```bash
# Build frontend with Service Worker
npm run build

# Verify Service Worker was created
ls -la dist/sw.js
ls -la dist/manifest.webmanifest

# Upload to production
rsync -av dist/ prod-server:/var/www/html/public/
```

### Cache Busting

Workbox automatically handles cache versioning via `precache` manifest. When you rebuild:

1. New assets get new hashes in filename
2. Old assets are automatically cleaned up after 24 hours
3. Users get latest code without manual cache clearing

---

## Queue Worker Configuration

### Systemd Service (Linux)

**File**: `/etc/systemd/system/qrmade-queue-worker.service`

```ini
[Unit]
Description=QR Made Queue Worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/html
Environment="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin"
ExecStart=/usr/bin/php /var/www/html/artisan queue:work database \
  --queue=default \
  --tries=3 \
  --timeout=90 \
  --memory=256

Restart=always
RestartSec=5

# Logging
StandardOutput=journal
StandardError=journal
SyslogIdentifier=qrmade-queue

[Install]
WantedBy=multi-user.target
```

**Enable and start**:

```bash
sudo systemctl enable qrmade-queue-worker
sudo systemctl start qrmade-queue-worker

# Check status
sudo systemctl status qrmade-queue-worker

# View logs
journalctl -u qrmade-queue-worker -f
```

### Supervisor (Alternative)

**File**: `/etc/supervisor/conf.d/qrmade-queue.conf`

```ini
[program:qrmade-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work database \
  --queue=default \
  --tries=3 \
  --timeout=90 \
  --memory=256
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/qrmade-queue.log
stopwaitsecs=60
```

**Activate**:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start qrmade-queue-worker:*

# Check status
sudo supervisorctl status qrmade-queue-worker:*
```

### Health Check

```bash
# Check if queue worker is running
ps aux | grep "queue:work" | grep -v grep

# Check failed jobs
php artisan tinker
>>> DB::table('failed_jobs')->count()

# Retry failed jobs
php artisan queue:retry all

# Or retry specific job
php artisan queue:retry {id}
```

---

## Monitoring & Logging

### Setup Application Logging

**File**: `config/logging.php`

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'syslog'],
        'ignore_exceptions' => false,
    ],

    'syslog' => [
        'driver' => 'syslog',
        'level' => env('LOG_LEVEL', 'warning'),
        'facility' => LOG_USER,
    ],
],
```

### Monitor Push Notification Delivery

```bash
# In production, log every push notification
# File: app/Listeners/SendTransactionPushNotification.php

// At top of listener
Log::info('Push notification event received', [
    'transaction_id' => $transaction->id,
    'user_id' => $user->id,
    'amount' => $transaction->amount,
]);

// If error
Log::error('Failed to queue push notification', [
    'user_id' => $user->id,
    'error' => $e->getMessage(),
]);
```

### Check Logs

```bash
# Real-time logs
tail -f storage/logs/laravel.log

# Filter for push notifications
grep "Push notification" storage/logs/laravel.log

# Count successful deliveries
grep -c "notification queued" storage/logs/laravel.log

# Count failures
grep -c "Failed to queue" storage/logs/laravel.log
```

### Setup Alerts

**Sentry (Error Tracking)**:

```bash
# Install
composer require sentry/sentry-laravel

# Configure in .env
SENTRY_LARAVEL_DSN=https://...@sentry.io/...
```

**CloudWatch Alarms (AWS)**:

```bash
# Alert if more than 10 failed jobs in 5 minutes
aws cloudwatch put-metric-alarm \
  --alarm-name qrmade-queue-failures \
  --alarm-description "Alert if queue failures exceed threshold" \
  --metric-name FailedJobsCount \
  --namespace QRMadeMetrics \
  --statistic Sum \
  --period 300 \
  --threshold 10 \
  --comparison-operator GreaterThanThreshold \
  --evaluation-periods 1 \
  --alarm-actions arn:aws:sns:us-east-1:123456:alert
```

---

## Performance Optimization

### Frontend Bundle Size

**Verify PWA bundle impact**:

```bash
npm run build

# Check bundle size
npm run build -- --report

# Expected:
# - vite-plugin-pwa: +35KB gzipped
# - Service Worker: ~51KB (cached)
# - Total impact: <100KB
```

### Workbox Cache Optimization

**Current Settings** (proven production values):

```javascript
// Inertia Pages (NetworkFirst)
networkTimeoutSeconds: 10,           // Wait 10s before cache
maxEntries: 20,                      // Keep 20 pages
maxAgeSeconds: 7 * 24 * 60 * 60,    // Cache 7 days

// Images (CacheFirst)
maxEntries: 50,                      // Keep 50 images
maxAgeSeconds: 30 * 24 * 60 * 60,   // Cache 30 days

// API (NetworkFirst)
networkTimeoutSeconds: 5,            // Wait 5s before cache
maxEntries: 20,                      // Keep 20 responses
maxAgeSeconds: 60,                   // Cache 1 minute
```

**Tuning for Your Needs**:

```javascript
// If users have slow networks:
networkTimeoutSeconds: 20,  // Wait longer before falling back to cache

// If you deploy frequently:
maxAgeSeconds: 24 * 60 * 60,  // Cache only 24 hours

// If you have limited storage:
maxEntries: 10,  // Store fewer cached items
```

### Database Query Optimization

**Minimize queries when processing transactions**:

```php
// Eager load relationships to avoid N+1 queries
$transaction = Transaction::with('giftCard.user')
    ->find($id);

// In listener
$user = $transaction->giftCard->user;  // Already loaded, no extra query
```

---

## Rollback Procedure

If something goes wrong after deployment:

### Quick Rollback (if code is the issue)

```bash
# 1. Check what went wrong
tail -f storage/logs/laravel.log | grep error

# 2. Revert to previous commit
git checkout main
git reset --hard <previous-commit-sha>

# 3. Redeploy
./deploy.sh production

# 4. Verify
php artisan queue:work --help  # Should work
```

### Rollback Queue Worker Only

If push notifications are failing but rest of app is fine:

```bash
# 1. Stop queue worker temporarily
sudo systemctl stop qrmade-queue-worker

# 2. Clear failed jobs queue
php artisan queue:flush

# 3. Fix the issue (check logs, update code)
# ...

# 4. Restart queue worker
sudo systemctl start qrmade-queue-worker

# 5. Verify it's running
sudo systemctl status qrmade-queue-worker
```

### Rollback VAPID Keys

If keys were accidentally exposed:

```bash
# 1. Generate new keys
php artisan webpush:vapid --force

# 2. Update secrets immediately
aws secretsmanager update-secret \
  --secret-id qrmade/production/vapid \
  --secret-string '{"public":"NEW...","private":"NEW..."}'

# 3. Redeploy with new keys
./deploy.sh production

# 4. Test notifications work
# New subscriptions will use new keys
# Old subscriptions continue working (push service doesn't validate)
```

---

## Post-Deployment Verification

### Automated Checks

```bash
# 1. Verify environment variables
php artisan config:show webpush

# 2. Test API endpoints
curl -X POST https://qrmade.example.com/api/push-subscriptions \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"endpoint":"https://...", "publicKey":"...", "authToken":"..."}'

# Expected: 201 Created response

# 3. Check queue worker
ps aux | grep "queue:work" | grep -v grep

# 4. Check database migrations
php artisan migrate:status

# 5. Verify Service Worker
curl https://qrmade.example.com/sw.js
curl https://qrmade.example.com/manifest.webmanifest
```

### Manual Testing

```bash
# 1. Open app in browser
# Go to https://qrmade.example.com/dashboard

# 2. Click notification bell (top right)
# Should show "Notificaciones desactivadas" and red badge

# 3. Click to subscribe
# Browser will request notification permission
# Click "Allow"

# 4. Bell should now show green badge
# Message: "Notificaciones activadas"

# 5. Create a test transaction
# Backend logs should show notification queued
# Notification should appear on device

# 6. Test on multiple devices
# Desktop: Chrome, Firefox, Safari
# Mobile: iOS (Safari), Android (Chrome)
```

### Performance Audit

```bash
# Run Lighthouse PWA audit
# Chrome DevTools → Lighthouse → PWA

# Expected scores:
# - Performance: 80+
# - Accessibility: 90+
# - Best Practices: 90+
# - SEO: 90+
# - PWA: 90+

# Check Service Worker registration
# DevTools → Application → Service Workers
# Should show "active and running"

# Check Manifest
# DevTools → Application → Manifest
# Should load without errors
```

### User Testing

Have a few team members test on their devices:

```
Checklist:
☐ Install app on phone
☐ Grant notification permission
☐ Create a test transaction
☐ Receive notification
☐ Click notification (should open dashboard)
☐ Test on 2+ devices
☐ Test after clearing app cache
☐ Test after restarting device
```

### 24-Hour Post-Deployment Monitoring

```bash
# Day 1: Monitor metrics every 4 hours
# Check:
# - Queue job success rate (should be >99%)
# - Push delivery success rate
# - Failed job count (should be minimal)
# - Error logs (no new patterns)
# - Performance metrics (no degradation)

# Monitor command
watch -n 5 'php artisan tinker -e "echo DB::table(\"jobs\")->count() . \" queued, \" . DB::table(\"failed_jobs\")->count() . \" failed\""'

# If any issues:
# 1. Check logs: tail -f storage/logs/laravel.log
# 2. Review recent changes
# 3. Rollback if needed
# 4. Create incident report
```

---

**Document Version**: 1.0
**Last Updated**: 2026-02-08
**Author**: Documentation Specialist
**Next**: See [User Guide](../user/push-notifications-guide.md)

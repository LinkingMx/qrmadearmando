# Troubleshooting Guide - Push Notifications

**Version**: 1.0
**Last Updated**: 2026-02-08
**Status**: Production Ready

## Table of Contents

1. [User Issues](#user-issues)
2. [Admin/Operator Issues](#adminoperator-issues)
3. [Developer Issues](#developer-issues)
4. [Solutions & Debugging](#solutions--debugging)
5. [Getting Help](#getting-help)

---

## User Issues

### "I'm not receiving notifications"

**Symptoms**:
- Notification bell is green, but no notifications appear
- Transactions happen but device doesn't alert

**Root Cause Checklist**:

```
□ Notifications disabled in browser
□ Notifications disabled on device
□ Browser not supported
□ Network connection issue
□ App not installed correctly
□ Permission revoked after initial grant
```

**Troubleshooting Steps**:

**Step 1: Check Browser Permission**

1. Open QR Made app
2. Look for notification bell 🔔 in top right
3. Check the color:
   - ✅ Green = Notifications ENABLED
   - ❌ Red = Notifications DISABLED
4. If red, click to enable

**Step 2: Check Device Settings**

**Android (Chrome)**:
1. Open Android Settings
2. Go to Apps → Chrome
3. Go to Notifications
4. Toggle ON "Show notifications"
5. Check volume is not muted

**iPhone (Safari)**:
1. Open iPhone Settings
2. Go to Notifications
3. Find QR Made or Safari
4. Toggle "Allow Notifications" ON
5. Check "Sounds" is enabled

**Step 3: Check Browser Support**

Supported:
- ✅ Chrome 50+
- ✅ Firefox 48+
- ✅ Safari 16+ (iOS 16+)
- ✅ Edge 79+

Not Supported:
- ❌ Internet Explorer
- ❌ Older Safari versions
- ❌ Opera < 37

Try different browser if current not supported.

**Step 4: Clear Browser Cache**

1. Open browser Settings
2. Find "Clear browsing data" or "Clear site data"
3. Select:
   - ☑ Cookies
   - ☑ Cached files
   - ☑ Service Worker
4. Click Clear
5. Refresh QR Made page
6. Re-enable notifications

**Step 5: Create Test Transaction**

If possible, ask a manager to create a small test transaction on your gift card. Check if notification appears.

**Still not working?** → Go to [Getting Help](#getting-help)

---

### "Permission denied when enabling notifications"

**Symptoms**:
- Browser shows permission dialog
- You click "Block" or "Don't allow"
- Now notifications don't work

**Solution**:

**For Chrome**:
1. Go to chrome://settings/content/notifications
2. Find the QR Made entry
3. Click the trash icon to delete
4. Reload QR Made page
5. Browser will ask permission again
6. This time click "Allow"

**For Firefox**:
1. Click the lock icon next to the URL
2. Go to "Permissions"
3. Find "Notifications"
4. Click "X" to remove
5. Reload page
6. Click "Allow" when asked

**For Safari**:
1. Open Safari Preferences
2. Go to "Websites" tab
3. Select "Notifications" from left sidebar
4. Find QR Made entry
5. Change from "Deny" to "Allow"
6. Reload page

---

### "The install button doesn't appear"

**Symptoms**:
- No banner suggesting to install app
- Can't find install option

**Possible Reasons**:

1. **Already Installed**
   - Check your home screen
   - QR Made app already there?
   - Try opening the app

2. **Desktop Browser**
   - Install prompt only appears on mobile
   - Desktop users can't install PWA

3. **Browser Not Supported**
   - Android: Use Chrome
   - iPhone: Use Safari
   - Other browsers may not support

4. **Dismissed for 14 Days**
   - You clicked "Now" recently
   - Banner reappears after 14 days
   - Or click the bell and re-enable

5. **Not HTTPS**
   - PWA requires HTTPS
   - Check URL starts with `https://`

---

### "Notifications not showing on locked phone"

**Symptoms**:
- Phone is locked
- Notifications don't appear
- Work when screen is on

**Solution**:

**Android**:
1. Open Settings → Notifications
2. Find Chrome or QR Made
3. Ensure notifications show on lock screen

**iPhone**:
1. Open Settings → Notifications
2. Find Safari or QR Made
3. Toggle "Show on Lock Screen" ON
4. Toggle "Show in Notification Center" ON

---

## Admin/Operator Issues

### "Queue worker not running"

**Symptoms**:
- Transactions created but notifications don't arrive
- No errors in browser
- Email not sending either

**Check Status**:

```bash
# Check if process running
ps aux | grep "queue:work" | grep -v grep

# Should see:
# php /var/www/html/artisan queue:work database --queue=default

# If not running:
ps aux | grep supervisor | grep -v grep

# If supervisor not running either:
sudo service supervisor status
```

**Restart Queue Worker**:

**Using Systemd**:
```bash
sudo systemctl status qrmade-queue-worker
sudo systemctl restart qrmade-queue-worker
sudo systemctl start qrmade-queue-worker
```

**Using Supervisor**:
```bash
sudo supervisorctl status qrmade-queue:*
sudo supervisorctl restart qrmade-queue:*
```

**Manual Start** (testing):
```bash
cd /var/www/html
php artisan queue:work database --queue=default
```

**Check Logs**:
```bash
# systemd
journalctl -u qrmade-queue-worker -f

# supervisor
tail -f /var/log/qrmade-queue.log

# Laravel
tail -f storage/logs/laravel.log | grep queue
```

---

### "WebPush channel error: Invalid endpoint"

**Symptoms**:
- Error when sending notification
- Queue job fails repeatedly
- Logs show "Invalid endpoint"

**Root Causes**:

1. **Endpoint URL Invalid**
   ```
   Problem: HTTP endpoint (not HTTPS)
   Solution: Only HTTPS endpoints allowed
   ```

2. **Endpoint URL Malformed**
   ```
   Problem: Missing / or extra characters
   Solution: Re-subscribe (clear invalid endpoint)
   ```

3. **Push Service Unknown**
   ```
   Problem: Endpoint from uncertified service
   Solution: Add service to whitelist in controller
   ```

**Debug**:

```bash
php artisan tinker

# Check endpoint
>>> $sub = User::first()->pushSubscriptions()->first()
>>> $sub->endpoint
# Should output: https://fcm.googleapis.com/fcm/send/...

# Check if valid
>>> filter_var($sub->endpoint, FILTER_VALIDATE_URL)
# Should return the URL if valid, false if not

# Delete invalid subscription
>>> $sub->delete()
```

---

### "Rate limit error (429)"

**Symptoms**:
- User gets "Too Many Requests" error
- "Wait X seconds before retrying"

**Root Cause**:

```
User tried to subscribe/unsubscribe more than 5 times in 1 minute
```

**Solution**:

1. **Wait**: User should wait the time shown in error
2. **Check**: Verify subscription is actually enabled
3. **Retry**: Try once more after waiting

**Administrator Action**:

If legitimate user is rate-limited:

```bash
# Check rate limit table
php artisan tinker
>>> DB::table('cache')->where('key', 'like', 'rate-limit:%')->get()

# Clear rate limit for user (be careful!)
>>> Cache::forget('rate-limit:' . auth()->id())

# Then user can retry
```

---

### "Failed jobs piling up"

**Symptoms**:
- `failed_jobs` table growing
- Notifications not being delivered
- Repeated failures

**Check Failed Jobs**:

```bash
# Count failed jobs
php artisan tinker
>>> DB::table('failed_jobs')->count()

# See recent failures
>>> DB::table('failed_jobs')->latest()->take(10)->get()

# Check error message
>>> $job = DB::table('failed_jobs')->latest()->first()
>>> json_decode($job->payload, true)['data']['command']
```

**Investigate Error**:

```bash
# Search logs for error type
grep "LowBalance\|WebPush\|Notification" storage/logs/laravel.log | tail -20

# Common errors:
# - "VAPID keys invalid" → Check .env
# - "Push service unreachable" → Network issue
# - "Endpoint expired" → User unsubscribed
```

**Retry Failed Jobs**:

```bash
# Retry all failed jobs
php artisan queue:retry all

# Or retry specific job
php artisan queue:retry {job_id}

# Or flush all (careful!)
php artisan queue:flush
```

**Prevent Recurrence**:

```php
// In config/queue.php
'failed' => [
    'driver' => 'database',
    'database' => 'sqlite',
    'table' => 'failed_jobs',
],

// In app/Listeners/SendTransactionPushNotification.php
// Add error handling:
try {
    Notification::send($user, new TransactionNotification($transaction));
} catch (\Exception $e) {
    Log::error('Notification failed', ['error' => $e->getMessage()]);
    // Don't re-throw - let it fail silently
}
```

---

## Developer Issues

### TypeScript errors after changes

**Symptoms**:
- "Property 'subscribe' does not exist"
- "Type 'unknown' is not assignable"
- Import errors in component

**Solution**:

**Step 1: Rebuild types**

```bash
npm run types
# or
npx tsc --noEmit
```

**Step 2: Check import paths**

```typescript
// ❌ Wrong (without @ alias)
import { usePushNotifications } from '../hooks/use-push-notifications'

// ✅ Correct (with @ alias)
import { usePushNotifications } from '@/hooks/use-push-notifications'
```

**Step 3: Verify export**

```typescript
// In use-push-notifications.ts
export function usePushNotifications(): UsePushNotificationsReturn {
  //  ↑ Must be exported
}

// ✓ If missing export, add it
```

**Step 4: Restart dev server**

```bash
npm run dev
# Ctrl+C to stop, restart
```

---

### "ServiceWorker registration failed"

**Symptoms**:
- DevTools shows no Service Worker
- `navigator.serviceWorker` undefined
- "Failed to register a ServiceWorker"

**Debugging**:

```javascript
// In browser console
navigator.serviceWorker.ready
  .then(reg => console.log('SW registered:', reg))
  .catch(err => console.error('SW registration failed:', err))
```

**Common Causes**:

1. **HTTPS not enabled**
   ```
   Service Workers require HTTPS (or localhost)
   Check: URL must start with https://
   ```

2. **Service Worker file not found**
   ```bash
   # After build, verify file exists
   ls -la dist/sw.js

   # Build again if missing
   npm run build
   ```

3. **vite.config.ts not configured correctly**
   ```typescript
   // Check VitePWA plugin is installed
   import { VitePWA } from 'vite-plugin-pwa'

   // Check plugin is in plugins array
   plugins: [
     VitePWA({
       registerType: 'autoUpdate',
       // ... config
     }),
   ]
   ```

4. **Browser security policy**
   ```
   Clear browser cache and cookies
   Try incognito/private window
   Try different browser
   ```

---

### "VAPID key errors in logs"

**Symptoms**:
- Error: "Invalid VAPID keys"
- Error: "VAPID_PUBLIC_KEY is not set"

**Solution**:

**Step 1: Verify keys exist**

```bash
# Check .env file
grep VAPID .env

# Should show:
# VAPID_PUBLIC_KEY=BFp4...
# VAPID_PRIVATE_KEY=I7TK...
# VAPID_SUBJECT=mailto:...
```

**Step 2: Generate if missing**

```bash
# Generate new keys
php artisan webpush:vapid

# Add to .env manually if needed
echo "VAPID_PUBLIC_KEY=..." >> .env
echo "VAPID_PRIVATE_KEY=..." >> .env
echo "VAPID_SUBJECT=mailto:admin@example.com" >> .env
```

**Step 3: Clear config cache**

```bash
# Laravel caches config values
php artisan config:clear

# Or restart dev server
npm run dev
```

**Step 4: Verify in app**

```bash
php artisan tinker
>>> config('webpush.vapid.subject')
# Should output: mailto:admin@example.com

>>> config('webpush.vapid.public_key')
# Should output: BFp4...
```

---

### "Manifest.json not found"

**Symptoms**:
- DevTools → Application → Manifest shows error
- Browser can't load /manifest.webmanifest
- PWA not installing

**Solution**:

**Step 1: Verify vite build**

```bash
# Check if manifest generated
ls -la dist/manifest.webmanifest

# If missing, rebuild
npm run build

# Check build output
npm run build -- --report
# Look for "manifest.webmanifest" in output
```

**Step 2: Check public path**

In `vite.config.ts`:

```typescript
export default defineConfig({
  base: '/',  // Make sure this is correct

  plugins: [
    VitePWA({
      manifest: {
        // Make sure manifest is defined
        name: 'QR Made',
        // ...
      },
    }),
  ],
})
```

**Step 3: Verify icons exist**

```bash
# Check icon files
ls -la public/icons/

# Should have:
# icon-192x192.png
# icon-512x512.png
# icon-maskable.png
```

**Step 4: Clear cache and rebuild**

```bash
# Remove old build
rm -rf dist/

# Clear npm cache
npm cache clean --force

# Rebuild
npm run build

# Restart dev server
npm run dev
```

---

## Solutions & Debugging

### Generic Debugging Steps

```
1. Check browser console for JavaScript errors
   DevTools → Console tab

2. Check network requests for API errors
   DevTools → Network tab
   Look for /api/push-subscriptions requests
   Check response status and error message

3. Check Service Worker registration
   DevTools → Application → Service Workers
   Should show "active and running"

4. Check Manifest
   DevTools → Application → Manifest
   Should load without errors

5. Check Application Cache
   DevTools → Application → Cache Storage
   Look for "inertia-pages", "images", "api-calls"

6. Check localStorage
   DevTools → Application → Local Storage
   Look for keys starting with "pwa:"

7. Check server logs
   tail -f storage/logs/laravel.log

8. Check queue status
   php artisan queue:failed

9. Test with curl
   curl -X POST https://app/api/push-subscriptions \
     -H "Authorization: Bearer TOKEN" \
     -H "Content-Type: application/json" \
     -d '{...}'

10. Create minimal test case
    Try simplest scenario possible
    Eliminate variables
    Isolate the problem
```

### Log Locations

**Browser Logs**:
- DevTools → Console tab
- Real-time errors and warnings

**Service Worker Logs**:
- DevTools → Application → Service Workers
- "Offline" = SW not running
- Click the registration to debug

**Server Logs**:
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Queue worker logs
journalctl -u qrmade-queue-worker -f  # systemd
tail -f /var/log/qrmade-queue.log    # supervisor

# System logs
tail -f /var/log/syslog              # Ubuntu/Debian
tail -f /var/log/messages            # CentOS/RHEL
```

### Debug Mode

**Enable verbose logging**:

```php
// In config/logging.php
'channels' => [
    'stack' => [
        'channels' => ['single', 'syslog'],
        'level' => 'debug',  // More detailed logs
    ],
],
```

**Enable browser debugging**:

```javascript
// In resources/js/hooks/use-push-notifications.ts
const DEBUG = true

if (DEBUG) {
  console.log('[usePushNotifications] isSupported:', isSupported)
  console.log('[usePushNotifications] permission:', permission)
  console.log('[usePushNotifications] isSubscribed:', isSubscribed)
}
```

---

## Getting Help

### Self-Service Resources

1. **This Guide**: You're reading it!
2. **API Reference**: `/docs/api/push-notifications.md`
3. **Backend Guide**: `/docs/backend/push-notification-integration.md`
4. **Frontend Guide**: `/docs/frontend/pwa-push-notifications.md`
5. **Deployment Guide**: `/docs/deployment/pwa-push-notifications.md`
6. **User Guide**: `/docs/user/push-notifications-guide.md`

### Contact Support

**Email**: support@selatravel.com
**Phone**: +1 (555) 123-4567
**Hours**: 9 AM - 5 PM EST, Monday - Friday

**Include in support request**:

For **Users**:
- Your username
- Device type (iPhone/Android/Desktop)
- Browser used
- What you were trying to do
- What happened instead
- Screenshots if possible

For **Administrators**:
- Server logs (last 100 lines)
- Queue status (`php artisan queue:failed`)
- Number of affected users
- When problem started

For **Developers**:
- Stack trace from error
- Reproduction steps
- Expected vs. actual behavior
- Environment (dev/staging/prod)
- Recent changes made

### Emergency Contact

**Critical Issues** (outage, security):
- Email: security@selatravel.com
- Phone: +1 (555) 999-9999 (24/7)

---

**Document Version**: 1.0
**Last Updated**: 2026-02-08
**Support**: support@selatravel.com

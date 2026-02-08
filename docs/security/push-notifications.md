# Security & Compliance - Push Notifications

**Version**: 1.0
**Last Updated**: 2026-02-08
**Status**: Production Ready
**Compliance**: GDPR, CCPA, SOC 2 Ready

## Table of Contents

1. [Security Overview](#security-overview)
2. [VAPID Key Security](#vapid-key-security)
3. [Authentication & Authorization](#authentication--authorization)
4. [Data Protection](#data-protection)
5. [Compliance Requirements](#compliance-requirements)
6. [Threat Model](#threat-model)
7. [Incident Response](#incident-response)
8. [Security Checklist](#security-checklist)

---

## Security Overview

### Security Architecture

```
┌────────────────────────────────────────────────────┐
│         AUTHENTICATION & AUTHORIZATION              │
├────────────────────────────────────────────────────┤
│ • User must be authenticated (logged in)           │
│ • User must have verified email (2FA confirmation) │
│ • No API access without auth token or session      │
└────────────────────────────────────────────────────┘
                         │
                         ▼
┌────────────────────────────────────────────────────┐
│      ENDPOINT VALIDATION & RATE LIMITING            │
├────────────────────────────────────────────────────┤
│ • Endpoint must be HTTPS only                      │
│ • Endpoint must be from known push service         │
│ • Unique constraint: one subscription per endpoint │
│ • Rate limit: 5 requests per minute per user       │
└────────────────────────────────────────────────────┘
                         │
                         ▼
┌────────────────────────────────────────────────────┐
│        ENCRYPTION & SECURE TRANSPORT                │
├────────────────────────────────────────────────────┤
│ • HTTPS enforced (TLS 1.2+)                        │
│ • VAPID keys used for signing                      │
│ • Database stores plaintext keys (standard)        │
│ • Push service handles encryption to device       │
└────────────────────────────────────────────────────┘
                         │
                         ▼
┌────────────────────────────────────────────────────┐
│       ACTIVITY LOGGING & AUDIT TRAIL                │
├────────────────────────────────────────────────────┤
│ • Every subscribe/unsubscribe action logged        │
│ • Logs tied to user and push service               │
│ • Retention: 90 days (configurable)                │
│ • Queries available for compliance audits          │
└────────────────────────────────────────────────────┘
```

### Principles

1. **Principle of Least Privilege**: Users can only manage their own subscriptions
2. **Defense in Depth**: Multiple layers of security (auth, validation, encryption)
3. **Secure by Default**: HTTPS enforced, unknown providers blocked
4. **Transparency**: Users informed when notifications are active
5. **User Control**: Easy to disable notifications anytime

---

## VAPID Key Security

### What are VAPID Keys?

VAPID = Voluntary Application Server Identification

- **Public Key**: Safe to expose to frontend (like a lock)
- **Private Key**: MUST be protected (like the key to the lock)
- **Subject**: Contact email for push service

### Key Generation

```bash
# One-time setup (do this locally, never in production)
php artisan webpush:vapid

# Output:
# VAPID_PUBLIC_KEY=BFp4+SJq7ythmVNxE...
# VAPID_PRIVATE_KEY=I7TKRj3l5xQzV...
```

### Key Storage

**Development** (local machine only):

```env
# .env (git ignored)
VAPID_PUBLIC_KEY=BFp4+SJq7ythmVNxE...
VAPID_PRIVATE_KEY=I7TKRj3l5xQzV...
VAPID_SUBJECT=mailto:admin@selatravel.com
```

**Production** (secrets manager, NOT in code):

```
✅ AWS Secrets Manager
✅ HashiCorp Vault
✅ Azure Key Vault
✅ Google Cloud Secret Manager
✅ Deployment platform secrets (Heroku, etc.)

❌ NEVER: Commit to git
❌ NEVER: Store in .env file in production
❌ NEVER: Expose in logs or error messages
```

### Key Protection

**Protecting the Private Key**:

1. ✅ **Store encrypted**: Use secrets manager with encryption at rest
2. ✅ **Limit access**: Only application servers have access
3. ✅ **Audit logging**: Log all access to secrets
4. ✅ **Rotate regularly**: Every 3-6 months
5. ✅ **Monitor usage**: Alert on unusual access patterns

**Access Control**:

```
DevOps: Can generate and manage keys
Infrastructure: Can deploy keys to production
Backend Code: Can READ public + private keys (at runtime)
Frontend Code: Can ONLY READ public key

Database: Never stores keys (only push subscriptions)
Logs: Never log key values
Error Messages: Never include key details
```

### Key Rotation

**Safe Rotation Process**:

```bash
# Step 1: Generate new keys locally
php artisan webpush:vapid

# Step 2: Update production secrets
aws secretsmanager update-secret \
  --secret-id qrmade/prod/vapid \
  --secret-string '{
    "public_key": "BFp4...",
    "private_key": "I7TK...",
    "subject": "mailto:admin@selatravel.com"
  }'

# Step 3: Deploy application (no code changes, just env vars)
./deploy.sh production

# Step 4: Test notifications work
php artisan queue:work --once

# Step 5: Monitor for 24 hours
# Check for failures, verify deliveries work

# Step 6: Archive old keys
# Keep in secure backup for 30 days
```

**What Happens During Rotation**:

- Existing subscriptions continue working (push service doesn't validate keys)
- New subscriptions created with new keys
- No user action required
- No downtime
- Can be done anytime (even during business hours)

### Key Compromise Response

If VAPID keys are exposed (leaked to GitHub, etc.):

```bash
# CRITICAL - DO IMMEDIATELY

# 1. Regenerate keys
php artisan webpush:vapid --force

# 2. Update secrets
aws secretsmanager update-secret \
  --secret-id qrmade/prod/vapid \
  --secret-string '{...new keys...}'

# 3. Deploy immediately
./deploy.sh production --force

# 4. Notify security team
# Create incident report

# 5. Revoke exposed keys from push services
# Contact Firebase, Mozilla, Apple if necessary

# 6. Review logs for unauthorized access
grep "vapid" storage/logs/laravel.log | grep -v "expected_user"

# 7. Monitor for suspicious activity
# Alert on unusual subscription patterns
```

---

## Authentication & Authorization

### API Endpoint Security

```php
// app/Http/Controllers/PushSubscriptionController.php

// MIDDLEWARE: auth + verified
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('api/push-subscriptions', 'store');      // Subscribe
    Route::delete('api/push-subscriptions', 'destroy');   // Unsubscribe
});
```

**Authentication Layers**:

1. **HTTP Layer**: Request must have valid session or bearer token
2. **Middleware Layer**: `auth` and `verified` middleware checks
3. **Controller Layer**: `$request->user()` returns authenticated user
4. **Model Layer**: Subscriptions scoped to user (`user_id` foreign key)

### Authorization Checks

**Subscribe Endpoint** (POST /api/push-subscriptions):

```php
public function store(Request $request)
{
    // Implicit checks:
    // 1. Must be authenticated (auth middleware)
    // 2. Must be verified (verified middleware)

    // Create subscription for THIS user
    $subscription = $request->user()->pushSubscriptions()
        ->firstOrCreate(
            ['endpoint' => $validated['endpoint']],
            [...]
        );

    // User can ONLY manage their own subscriptions
    // Because we use $request->user()->pushSubscriptions()
}
```

**Unsubscribe Endpoint** (DELETE /api/push-subscriptions):

```php
public function destroy(Request $request)
{
    $subscription = $request->user()->pushSubscriptions()
        ->where('endpoint', $validated['endpoint'])
        ->first();

    // Double-check authorization
    if ($subscription->user_id !== $request->user()->id) {
        return response()->json(['message' => 'No autorizado'], 403);
    }

    $subscription->delete();
}
```

### Permission Verification

Users can only:
- ✅ Create subscriptions for themselves
- ✅ View their own subscriptions
- ✅ Delete their own subscriptions

Users cannot:
- ❌ Access other users' subscriptions
- ❌ Create subscriptions for other users
- ❌ Delete other users' subscriptions

---

## Data Protection

### What Data is Stored

**push_subscriptions Table**:

```sql
CREATE TABLE push_subscriptions (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,                          -- Links to user
    endpoint VARCHAR(2048),                  -- Push service URL
    public_key VARCHAR(500),                 -- Encryption key (p256dh)
    auth_token VARCHAR(500),                 -- HMAC key (auth)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_endpoint (user_id, endpoint)
);
```

**Data Classification**:

| Field | Type | Sensitivity | Protection |
|-------|------|-------------|-----------|
| `user_id` | Identifier | Low | Indexed for queries |
| `endpoint` | URL | Medium | Unique constraint |
| `public_key` | Encryption Key | High | Part of HTTPS transport |
| `auth_token` | Secret | High | Part of HTTPS transport |

### Encryption Strategy

**Data in Transit**:

```
✅ HTTPS/TLS 1.2+ encrypts all data
✅ Push service API calls use HTTPS
✅ All API responses encrypted
❌ HTTP NOT allowed (production must be HTTPS)
```

**Data at Rest**:

```
✅ Database stored as plaintext (standard practice)
✅ Considered safe because:
   - Keys are app-specific (not user passwords)
   - WebPush spec designed for plaintext storage
   - Keys only valuable if someone has database access
   - If someone can access DB, they likely have app access too

Future Enhancement (Phase 4+):
✓ Database-level encryption with Laravel Encryption
✓ Transparent to application code
✓ HSM storage for VAPID keys
```

### Subscription Lifecycle

| Event | Action | Data | Duration |
|-------|--------|------|----------|
| **User subscribes** | Insert into DB | endpoint, keys, user_id | Until explicit delete |
| **User unsubscribes** | DELETE from DB | Record removed | Immediate |
| **User deletes account** | CASCADE DELETE | All subscriptions removed | Automatic |
| **Push service expires** | Cleanup job | Failed deliveries logged | Optional cron job |

### Data Retention

**Active Subscriptions**: Kept indefinitely while user account exists

**Deleted Subscriptions**: Removed immediately from database

**Activity Logs**: Kept for 90 days (configurable)

**Push Failures**: Logged in Laravel logs for 30 days, then archived

**Compliance**: GDPR Right to Erasure honored immediately (cascade delete)

---

## Compliance Requirements

### GDPR (General Data Protection Regulation)

**Requirement**: Must comply if users are in EU.

| Requirement | Implementation |
|-------------|-----------------|
| **Consent** | User grants permission via browser prompt |
| **Transparency** | Privacy policy explains push notifications |
| **Access Right** | User can see their subscriptions via API |
| **Deletion Right** | User can delete account → auto-deletes subscriptions |
| **Data Minimization** | Only store endpoint, keys (not PII) |
| **DPA** | Data Processing Agreement with push service providers |

**Privacy Policy Clause**:

```
Push Notifications

We use push notifications to inform you of balance changes.

Data collected:
- Notification subscription endpoint (from your browser)
- Encryption keys (generated by your browser)
- Your user ID (to link notification to your account)

Data sharing:
- Shared with push service (Google FCM, Mozilla, Apple)
- They relay to your device
- Never shared with third parties

Your rights:
- You can disable notifications anytime
- You can delete your account (auto-deletes subscriptions)
- Data retained only while subscribed
- Right to access, export, or delete anytime
```

### CCPA (California Consumer Privacy Act)

**Requirement**: Must comply if users are in California.

| Requirement | Implementation |
|-------------|-----------------|
| **Disclosure** | Privacy policy explains data collection |
| **Consumer Rights** | Users can request, delete data via CCPA form |
| **Opt-Out** | Easy unsubscribe button (red bell icon) |
| **No Discrimination** | Same service even if notifications disabled |

### SOC 2 Type II

**Requirement**: Demonstrates security & compliance controls.

| Control | Implementation |
|---------|-----------------|
| **Access Control** | Auth + verified email required |
| **Audit Logging** | All subscribe/unsubscribe logged |
| **Data Security** | HTTPS + database encryption |
| **Monitoring** | Alert on failed deliveries |
| **Incident Response** | Document procedures |

---

## Threat Model

### Identified Threats

**Threat 1: Unauthorized Subscription**

```
Attack: Attacker subscribes victim to notifications
Risk: Spam notifications, denial of service
Mitigation:
  ✅ Authentication required (must be logged in as user)
  ✅ Verified email required (2FA prevents hijacking)
  ✅ Rate limiting (5 req/min prevents rapid attacks)
Residual Risk: LOW
```

**Threat 2: VAPID Key Exposure**

```
Attack: Private key leaked to public (GitHub, logs, etc.)
Risk: Attacker could sign push notifications (impersonation)
Mitigation:
  ✅ Never commit keys to git (in .gitignore)
  ✅ Stored in secrets manager (AWS, not code)
  ✅ Not logged (no key values in logs)
  ✅ Rotate every 3-6 months
  ✅ Key compromise procedure in place
Residual Risk: MEDIUM (if exposure not detected quickly)
Response: Rotate keys within 1 hour
```

**Threat 3: Endpoint Validation Bypass**

```
Attack: Attacker provides fake push service endpoint
Risk: Subscriptions point to attacker's server
Mitigation:
  ✅ Whitelist known push services (FCM, Mozilla, Apple)
  ✅ HTTPS-only endpoints
  ✅ Max URL length (prevent resource exhaustion)
  ✅ Validation on both frontend and backend
Residual Risk: LOW
```

**Threat 4: Database Breach**

```
Attack: Attacker gains database access
Risk: Push subscriptions exposed (not secrets, but private data)
Mitigation:
  ✅ Strong database password (random, 32+ chars)
  ✅ Network isolation (DB not publicly accessible)
  ✅ Database backups encrypted
  ✅ Access logging (who accessed what)
  ✅ Regular backups stored securely
Future: Database-level encryption (Phase 4)
Residual Risk: MEDIUM (standard database security)
```

**Threat 5: Man-in-the-Middle (MITM) Attack**

```
Attack: Attacker intercepts API traffic
Risk: Session hijacking, endpoint theft
Mitigation:
  ✅ HTTPS enforced (TLS 1.2+)
  ✅ HSTS headers (browser enforces HTTPS)
  ✅ Certificate pinning (optional, high security)
  ✅ Valid SSL certificate (not self-signed)
Residual Risk: LOW
```

**Threat 6: Subscription Enumeration**

```
Attack: Attacker tries to guess valid endpoints
Risk: Discover who has subscriptions enabled
Mitigation:
  ✅ Authentication required (can't enumerate without login)
  ✅ No endpoint disclosure in responses (except for own subscriptions)
  ✅ Rate limiting (prevents brute force)
Residual Risk: LOW
```

**Threat 7: Notification Spoofing**

```
Attack: Attacker sends fake notifications to user's device
Risk: Phishing, social engineering
Mitigation:
  ✅ VAPID signature validation (browser verifies signature)
  ✅ Only legitimate server can sign (has private key)
  ✅ Service Worker validates notification origin
Residual Risk: LOW
```

**Threat 8: Privilege Escalation**

```
Attack: Low-privileged user accesses admin notifications
Risk: See other users' transaction notifications
Mitigation:
  ✅ User-scoped subscriptions (own subscriptions only)
  ✅ Authorization checks in controller (verify user_id)
  ✅ No admin panel for subscriptions (view in API)
Residual Risk: LOW
```

### Threat Matrix

| Threat | Likelihood | Impact | Risk | Mitigation |
|--------|-----------|--------|------|-----------|
| Unauthorized subscription | Low | Medium | **LOW** | Auth + 2FA |
| VAPID key exposure | Low | High | **MEDIUM** | Secrets manager + rotation |
| Endpoint validation bypass | Very Low | Low | **LOW** | Whitelist |
| Database breach | Very Low | High | **MEDIUM** | DB security best practices |
| MITM attack | Very Low | Medium | **LOW** | HTTPS |
| Subscription enumeration | Low | Low | **LOW** | Rate limiting |
| Notification spoofing | Very Low | Medium | **LOW** | VAPID signature |
| Privilege escalation | Very Low | High | **VERY LOW** | Authorization checks |

---

## Incident Response

### Incident Response Plan

**Incident Categories**:

1. **Critical** (0-1 hour response): VAPID key exposure, DB breach
2. **High** (1-4 hour response): Unauthorized access, notification spam
3. **Medium** (4-24 hour response): Failed deliveries, performance issue
4. **Low** (1-7 day response): User report, feature request

### Critical Incident: VAPID Key Exposure

**Detection**:

```
Triggers:
- GitHub detects exposed secrets
- Monitoring tool alerts on unusual key usage
- Security team reports compromise
- Customers report suspicious notifications
```

**Immediate Response** (0-15 minutes):

```bash
# 1. Verify the exposure
git log --all --full-history -- .env | grep VAPID

# 2. Assess impact
# Check when keys were exposed
# Check push failure logs for unusual patterns

# 3. Alert team
# Slack: @security @devops CRITICAL: VAPID keys exposed
# Email: security@company.com

# 4. Prepare new keys
php artisan webpush:vapid --force
# Save new keys in secrets manager (don't deploy yet)
```

**Response** (15-60 minutes):

```bash
# 1. Update secrets
aws secretsmanager update-secret \
  --secret-id qrmade/prod/vapid \
  --secret-string '{...new keys...}'

# 2. Deploy immediately
./deploy.sh production --force

# 3. Verify deployment
php artisan config:show webpush
curl https://app/api/health

# 4. Monitor logs
tail -f storage/logs/laravel.log | grep -i vapid
```

**Post-Incident** (1-24 hours):

```
1. Notify users (if unusual activity detected)
2. Review security processes
3. Implement secrets scanning (prevent future)
4. Document incident in log
5. Update security procedures
```

### High Incident: Unauthorized Subscription Access

**Detection**:

```
Triggers:
- User reports receiving notifications they didn't enable
- Logging shows subscriptions from unusual locations
- Rate limiting triggered multiple times from same IP
```

**Response**:

```bash
# 1. Identify affected users
php artisan tinker
>>> $subscriptions = DB::table('push_subscriptions')
    ->where('created_at', '>', now()->subHours(1))
    ->get()

# 2. Review logs
tail -f storage/logs/laravel.log | grep "subscribe"

# 3. Disable suspicious subscriptions
>>> DB::table('push_subscriptions')
    ->where('id', $suspicious_id)
    ->delete()

# 4. Notify users
// Send email: "We detected unusual activity..."

# 5. Review rate limiting rules
// Consider more aggressive limits if attack pattern
```

---

## Security Checklist

**Pre-Deployment**:

- [ ] VAPID keys generated and stored in secrets manager
- [ ] .env file NOT committed to git (check .gitignore)
- [ ] HTTPS certificate installed and valid
- [ ] Rate limiting configured (5 req/min)
- [ ] Auth and verified middleware on endpoints
- [ ] No debug info in error messages
- [ ] Logs not storing sensitive data
- [ ] CORS policy configured correctly
- [ ] Security headers configured (HSTS, X-Frame-Options, etc.)

**Post-Deployment**:

- [ ] Verify HTTPS working (no mixed content)
- [ ] Check rate limiting working
- [ ] Test invalid keys/endpoints rejected
- [ ] Review access logs for unusual patterns
- [ ] Verify audit logging working
- [ ] Test GDPR deletion (account delete removes subscriptions)
- [ ] Verify push notifications deliver (end-to-end test)

**Ongoing**:

- [ ] Weekly: Review error logs for security issues
- [ ] Monthly: Review access logs for suspicious patterns
- [ ] Quarterly: VAPID key rotation
- [ ] Quarterly: Security assessment (code review, penetration testing)
- [ ] Annually: Compliance audit (GDPR, SOC 2, etc.)

---

**Document Version**: 1.0
**Last Updated**: 2026-02-08
**Author**: Documentation Specialist
**Security Contact**: security@selatravel.com
**Next**: See [Troubleshooting Guide](../troubleshooting/push-notifications.md)

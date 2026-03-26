# OneSignal Integration Plan - PWA Push Notifications (iOS + Android)

**Status**: 📋 Plan Document
**Priority**: High (iOS Support)
**Effort**: Medium (4-6 hours)
**Date Created**: 2025-02-08

---

## 📋 Overview

Current implementation uses **Web Push API** (VAPID) which works on Android/Chrome/Firefox but NOT on iOS PWA.

**OneSignal** is a unified push platform that:
- ✅ Sends push to iOS PWA (via APNs)
- ✅ Sends push to Android (via FCM)
- ✅ Sends push to Web (via Web Push)
- ✅ Manages all push infrastructure
- ✅ Provides analytics & segmentation
- ✅ Free tier: 30,000 monthly active users

**Decision**: Replace Web Push API with OneSignal SDK for unified cross-platform support.

---

## 🎯 Goals

1. **Enable iOS push notifications** via OneSignal APNs integration
2. **Maintain Android push notifications** functionality
3. **Keep Web push working** via OneSignal
4. **Simplify push management** (one service instead of custom Web Push)
5. **Add analytics & segmentation** (bonus from OneSignal)

---

## 📊 Phase Breakdown

### Phase 1: Setup & Integration (2-3 hours)

**Tasks:**

1. **Create OneSignal Account**
   - Sign up at https://onesignal.com
   - Create new "QR Made Armando" app
   - Select platform: "Web Push"
   - Generate OneSignal App ID

2. **Install OneSignal Web SDK**
   ```bash
   npm install onesignal-web
   ```
   - Add to package.json dependencies
   - Update package-lock.json

3. **Configure OneSignal in Frontend**
   - **File**: `resources/js/app.tsx`
   - Initialize OneSignal SDK after app loads
   - Set user ID (auth.user?.id)
   - Request notification permission
   - Handle service worker registration

   **Code pattern:**
   ```typescript
   import OneSignal from 'onesignal-web'

   OneSignal.init({
     appId: import.meta.env.VITE_ONESIGNAL_APP_ID,
     autoResubscribe: true,
     notificationClickHandlerMatch: 'origin',
     serviceWorkerPath: '/sw.js',
   })

   OneSignal.User.PushSubscription.optIn()
   if (auth.user) {
     OneSignal.login(auth.user.id)
   }
   ```

4. **Update Environment Variables**
   - **Add to .env**:
     ```
     VITE_ONESIGNAL_APP_ID=your-app-id-here
     ONESIGNAL_API_KEY=your-rest-api-key
     ```
   - **Add to .env.example**:
     ```
     VITE_ONESIGNAL_APP_ID=
     ONESIGNAL_API_KEY=
     ```

5. **Register Service Worker with OneSignal**
   - Update `resources/js/sw-custom.ts` to work with OneSignal
   - OneSignal manages push event handling automatically
   - Remove custom push event listener (OneSignal handles it)

6. **Update Vite Config**
   - Ensure service worker is served at `/sw.js` (OneSignal expects this location)
   - Test that manifest.webmanifest is accessible

---

### Phase 2: Backend Integration (2 hours)

**Tasks:**

1. **Install OneSignal PHP SDK**
   ```bash
   composer require onesignal/php-sdk
   ```

2. **Create OneSignal Service**
   - **File**: `app/Services/OneSignalService.php`
   - Methods:
     - `sendNotification($userId, $title, $body, $data)` - Send to user
     - `sendToAllUsers($title, $body)` - Broadcast
     - `sendToSegment($segment, $title, $body)` - Send to segment

   **Pattern:**
   ```php
   namespace App\Services;

   use OneSignal\OneSignalClient;

   class OneSignalService
   {
       protected OneSignalClient $client;

       public function __construct()
       {
           $this->client = new OneSignalClient(
               config('services.onesignal.app_id'),
               config('services.onesignal.api_key')
           );
       }

       public function sendNotification($userId, $title, $body, $data = [])
       {
           return $this->client->notifications->add([
               'include_external_user_ids' => [(string)$userId],
               'headings' => ['en' => $title],
               'contents' => ['en' => $body],
               'data' => $data,
           ]);
       }
   }
   ```

3. **Update TransactionNotification**
   - **File**: `app/Notifications/TransactionNotification.php`
   - Replace WebPushChannel with OneSignal service call
   - Send transaction notification via OneSignal instead of Web Push

4. **Create OneSignal Configuration**
   - **File**: `config/services.php`
   - Add OneSignal credentials:
     ```php
     'onesignal' => [
         'app_id' => env('ONESIGNAL_APP_ID'),
         'api_key' => env('ONESIGNAL_API_KEY'),
     ],
     ```

5. **Update Event Listener**
   - **File**: `app/Listeners/SendTransactionPushNotification.php`
   - Replace WebPush logic with OneSignal service call
   - Keep same notification content format

---

### Phase 3: iOS Configuration (1-2 hours)

**Tasks:**

1. **Create iOS App in OneSignal Dashboard**
   - Platform: iOS
   - Bundle ID: `com.qrmadearmando.ios` (or your app ID)
   - Team ID: Get from Apple Developer account

2. **Generate APNs Certificates**
   - Login to Apple Developer Account
   - Create "Push Notification" certificate
   - Download .p8 key file
   - Upload to OneSignal dashboard

3. **Register OneSignal App for iOS**
   - Add iOS platform in OneSignal dashboard
   - Upload APNs key
   - Set team ID & bundle ID
   - Verify connection

4. **Test iOS Push**
   - OneSignal dashboard has "Send Test Notification"
   - Use this to verify APNs working

---

### Phase 4: Testing & Cleanup (1-2 hours)

**Tasks:**

1. **Frontend Testing**
   - Test notification subscription on Chrome/Firefox
   - Test notification on Android device
   - Test notification on iOS simulator/device
   - Verify permission requests

2. **Backend Testing**
   - Create transaction and verify OneSignal receives notification
   - Check OneSignal dashboard for delivery status
   - Verify notification content is correct
   - Test error handling

3. **Remove Old Code**
   - Delete `PushSubscriptionController.php` (no longer needed)
   - Delete `app/Models/PushSubscription.php` (OneSignal manages subscriptions)
   - Remove VAPID configuration (keep for reference)
   - Delete old push event listener from service worker
   - Update routes to remove push subscription endpoints

4. **Update Documentation**
   - Document OneSignal setup in README
   - Add environment variables to docs
   - Add troubleshooting guide

5. **Update Tests**
   - Remove `PushSubscriptionControllerTest.php` tests
   - Update `TransactionNotificationTest.php` to use OneSignal
   - Mock OneSignal API calls
   - Test notification delivery

---

## 🛠️ Implementation Details

### Files to Create/Modify

| File | Action | Details |
|------|--------|---------|
| `app/Services/OneSignalService.php` | Create | OneSignal wrapper service |
| `config/services.php` | Modify | Add OneSignal config |
| `resources/js/app.tsx` | Modify | Initialize OneSignal SDK |
| `resources/js/sw-custom.ts` | Modify | Remove custom push handler |
| `app/Notifications/TransactionNotification.php` | Modify | Use OneSignal instead of WebPush |
| `app/Listeners/SendTransactionPushNotification.php` | Modify | Call OneSignal service |
| `.env` | Modify | Add ONESIGNAL credentials |
| `.env.example` | Modify | Add ONESIGNAL placeholders |
| `composer.json` | Modify | Add onesignal/php-sdk |
| `package.json` | Modify | Add onesignal-web |

### Files to Delete

- `app/Models/PushSubscription.php`
- `app/Http/Controllers/PushSubscriptionController.php`
- `app/Channels/WebPushChannel.php`
- `database/migrations/*_create_push_subscriptions_table.php`
- Tests: `PushSubscriptionControllerTest.php`

---

## 📱 Push Notification Content

**Transaction Debit** (Cargo):
```
Title: "Cargo Realizado"
Body: "Se realizó un cargo de $200.00 MXN. Saldo: $800.00 MXN"
Data: {
  type: "transaction",
  transaction_id: 123,
  amount: "200.00",
  new_balance: "800.00",
  url: "/dashboard"
}
```

**Transaction Credit** (Abono):
```
Title: "Abono Realizado"
Body: "Se abonaron $500.00 MXN. Saldo: $1,300.00 MXN"
```

**Transaction Adjustment** (Ajuste):
```
Title: "Ajuste de Saldo"
Body: "Se realizó un ajuste de $100.00 MXN. Saldo: $900.00 MXN"
```

---

## 🔐 Security Considerations

1. **API Key Protection**
   - Store `ONESIGNAL_API_KEY` only in `.env` (server-side)
   - Never expose in frontend code
   - Use app ID in frontend (safe to expose)

2. **User ID Mapping**
   - Map Laravel User ID to OneSignal external_user_id
   - Ensure user is authenticated before sending notifications
   - Validate user ownership of notifications

3. **Notification Authorization**
   - Request permission explicitly (browser requirement)
   - Respect user's notification preferences
   - Allow opt-out for individual notifications

4. **Data Privacy**
   - Don't send sensitive data in notifications
   - Only send: amount, type, new balance
   - Include URL to dashboard for details

---

## 📊 Comparison: Before vs After

| Aspect | Before (Web Push) | After (OneSignal) |
|--------|-------------------|-------------------|
| **Platforms** | Android, Chrome, Firefox | Android, iOS, Chrome, Firefox, Safari |
| **Setup Complexity** | Medium (custom VAPID) | Low (OneSignal) |
| **iOS Support** | ❌ No | ✅ Yes |
| **Analytics** | ❌ None | ✅ Built-in |
| **Infrastructure** | ✅ Own (simpler) | ☁️ Managed (OneSignal) |
| **Maintenance** | Medium | Low |
| **Cost** | Free (infrastructure) | Free (30k MAU) → Paid (scale) |

---

## 📝 Environment Variables

```bash
# OneSignal Setup
VITE_ONESIGNAL_APP_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
ONESIGNAL_API_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# Legacy (can keep for reference, not used)
VAPID_PUBLIC_KEY=xxx...
VAPID_PRIVATE_KEY=xxx...
```

---

## ✅ Success Criteria

- [ ] OneSignal account created & configured
- [ ] Web SDK installed & initialized
- [ ] PHP SDK installed & configured
- [ ] Push notifications sent to Android ✅
- [ ] Push notifications sent to iOS ✅
- [ ] Push notifications sent to Web browsers ✅
- [ ] OneSignal dashboard shows delivery status
- [ ] Tests pass for new implementation
- [ ] Old PushSubscription code removed
- [ ] Documentation updated
- [ ] No breaking changes to existing features

---

## 🚀 Rollout Plan

1. **Day 1-2**: Setup OneSignal, install SDKs
2. **Day 3-4**: Implement backend & frontend integration
3. **Day 5**: Configure iOS, test all platforms
4. **Day 6**: Cleanup old code, testing
5. **Day 7**: Deploy, monitor

---

## 📚 References

- OneSignal Docs: https://documentation.onesignal.com
- Web SDK: https://documentation.onesignal.com/docs/web-push-setup
- PHP SDK: https://github.com/OneSignal/OneSignal-PHP-SDK
- iOS Setup: https://documentation.onesignal.com/docs/ios-setup

---

## 🎓 Learning Resources

- Web Push API: https://developer.mozilla.org/en-US/docs/Web/API/Push_API
- APNs Overview: https://developer.apple.com/documentation/usernotifications/setting_up_a_remote_notification_server
- Service Workers: https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API

---

**Next Step**: When ready, start with Phase 1 setup. Will take ~30 mins to create OneSignal account and install SDKs.

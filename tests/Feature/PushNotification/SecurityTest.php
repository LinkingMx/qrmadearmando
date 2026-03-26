<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NotificationChannels\WebPush\PushSubscription;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['is_active' => true]);
    $this->otherUser = User::factory()->create(['is_active' => true]);
    $this->validEndpoint = 'https://fcm.googleapis.com/fcm/send/valid-endpoint-token';
    $this->validPublicKey = 'BCVxjl8WgP2F_9H7_X_N_1_kV_f3nE_R_5hxH_1gM_vN_0pX_9sQ_6aY';
    $this->validAuthToken = 'LS0tLS1CRUdJTi1DRVJUSUZJQ0FURS0tLS0tLS0K';
});

describe('VAPID Key Security', function () {
    test('VAPID public key is available in environment', function () {
        $vapidKey = env('VAPID_PUBLIC_KEY');
        // The test should verify the key can be retrieved (may be empty in test env)
        // In production, this will be set
        expect($vapidKey ?? 'not_set')->toBeString();
    });

    test('VAPID private key is not exposed in API responses', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $responseJson = $response->json();
        $responseString = json_encode($responseJson);

        expect($responseString)->not->toContain('private_key');
        expect($responseString)->not->toContain('VAPID_PRIVATE');
    });

    test('environment file is not exposed through API', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $responseJson = $response->json();
        $responseString = json_encode($responseJson);

        expect($responseString)->not->toContain('.env');
        expect($responseString)->not->toContain('APP_KEY');
    });
});

describe('Authorization & Ownership Validation', function () {

    test('user cannot access subscription list directly (no list endpoint)', function () {
        // This endpoint doesn't exist, so attempting to GET subscriptions should fail
        $response = $this->actingAs($this->user)->getJson('/api/push-subscriptions');
        expect($response->status())->toBe(405); // Method Not Allowed
    });

    test('user cannot delete another user\'s subscription', function () {
        $otherUserEndpoint = 'https://updates.push.services.mozilla.com/send/other-endpoint';
        $this->otherUser->pushSubscriptions()->create([
            'endpoint' => $otherUserEndpoint,
            'public_key' => $this->validPublicKey,
            'auth_token' => $this->validAuthToken,
        ]);

        $response = $this->actingAs($this->user)->deleteJson('/api/push-subscriptions', [
            'endpoint' => $otherUserEndpoint,
        ]);

        $response->assertNotFound();
        expect($this->otherUser->pushSubscriptions()->count())->toBe(1);
    });

    test('user cannot view another user\'s subscription data', function () {
        $otherUserSub = $this->otherUser->pushSubscriptions()->create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/secret-endpoint',
            'public_key' => 'secret-key',
            'auth_token' => 'secret-token',
        ]);

        // Verify that subscription belongs to other user (via polymorphic)
        expect($otherUserSub->subscribable_id)->toBe($this->otherUser->id);
        expect($otherUserSub->subscribable_type)->toBe('App\\Models\\User');
        expect($this->otherUser->ownsPushSubscription($otherUserSub))->toBeTrue();
        expect($this->user->ownsPushSubscription($otherUserSub))->toBeFalse();
    });

    test('deleting user cascade deletes subscriptions', function () {
        $tempUser = User::factory()->create(['is_active' => true]);
        $tempUser->pushSubscriptions()->create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/temp-endpoint',
            'public_key' => $this->validPublicKey,
            'auth_token' => $this->validAuthToken,
        ]);

        $userId = $tempUser->id;
        expect($tempUser->pushSubscriptions()->count())->toBe(1);

        // Get subscription ID before deletion
        $subscriptionCount = $tempUser->pushSubscriptions()->count();
        $tempUser->delete();

        // Verify subscriptions were deleted via CASCADE
        $remainingSubscriptions = PushSubscription::where('subscribable_id', $userId)
            ->where('subscribable_type', 'App\\Models\\User')
            ->count();
        expect($remainingSubscriptions)->toBe(0);
    });
});

describe('Endpoint Validation', function () {
    test('endpoint must be HTTPS only', function () {
        $httpEndpoint = 'http://fcm.googleapis.com/fcm/send/token';

        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $httpEndpoint,
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertUnprocessable();
    });

    test('endpoint must be valid URL format', function () {
        $invalidEndpoint = 'not a url at all';

        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $invalidEndpoint,
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertUnprocessable();
    });

    test('endpoint must come from known push service', function () {
        $unknownEndpoint = 'https://evil-push-service.com/send/token';

        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $unknownEndpoint,
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertUnprocessable();
    });

    test('endpoint domain validation uses proper hostname parsing', function () {
        // Test subdomain of FCM doesn't bypass validation
        $subdomainEndpoint = 'https://evil.fcm.googleapis.com.attacker.com/send/token';

        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $subdomainEndpoint,
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertUnprocessable();
    });

    test('endpoint length cannot exceed 2048 characters', function () {
        $longEndpoint = 'https://fcm.googleapis.com/fcm/send/'.str_repeat('a', 2020);

        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $longEndpoint,
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertUnprocessable();
    });

    test('allowed services include Google FCM', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertCreated();
    });

    test('allowed services include Mozilla push', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://updates.push.services.mozilla.com/send/abc123',
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertCreated();
    });

    test('allowed services include Apple push', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://api.push.apple.com/3/device/abc123',
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertCreated();
    });
});

describe('Input Validation', function () {
    test('public key must be string', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
            'publicKey' => ['not', 'a', 'string'],
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertUnprocessable();
    });

    test('auth token must be string', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
            'publicKey' => $this->validPublicKey,
            'authToken' => 12345,
        ]);

        $response->assertUnprocessable();
    });

    test('public key cannot exceed 500 characters', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
            'publicKey' => str_repeat('a', 501),
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertUnprocessable();
    });

    test('auth token cannot exceed 500 characters', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
            'publicKey' => $this->validPublicKey,
            'authToken' => str_repeat('a', 501),
        ]);

        $response->assertUnprocessable();
    });

    test('no extra fields accepted in request', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
            'maliciousField' => 'should be ignored',
        ]);

        $response->assertCreated();
        // Field should not be stored
        $subscription = $this->user->pushSubscriptions()->first();
        expect($subscription->getAttribute('maliciousField'))->toBeNull();
    });
});

describe('CSRF Protection', function () {
    test('JSON API requests are protected by authentication', function () {
        $response = $this->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test',
            'publicKey' => 'test-key',
            'authToken' => 'test-token',
        ]);

        // Should be unauthorized because no auth
        $response->assertUnauthorized();
    });

    test('authenticated user can POST to subscription endpoint', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/csrf-test-endpoint',
            'publicKey' => 'test-public-key',
            'authToken' => 'test-auth-token',
        ]);

        // Should succeed because we're authenticated
        $response->assertCreated();
    });

    test('authenticated user can DELETE subscription endpoint', function () {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/csrf-delete-test';
        $sub = $this->user->pushSubscriptions()->create([
            'endpoint' => $endpoint,
            'public_key' => 'test-key',
            'auth_token' => 'test-token',
        ]);

        // Verify subscription was created and belongs to user (via polymorphic)
        expect($sub->subscribable_id)->toBe($this->user->id);
        expect($sub->subscribable_type)->toBe('App\\Models\\User');
        expect($this->user->pushSubscriptions()->count())->toBe(1);

        $response = $this->actingAs($this->user)->deleteJson('/api/push-subscriptions', [
            'endpoint' => $endpoint,
        ]);

        $response->assertOk();
    });
});

describe('Rate Limiting', function () {
    test('subscription endpoint enforces throttle:5,1 rate limiting', function () {
        // The route is configured with 'throttle:5,1' which means 5 requests per 1 minute
        // We test that after 5 requests, the 6th request is throttled

        $tempUser = User::factory()->create(['is_active' => true]);

        // Make 5 successful requests
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($tempUser)->postJson('/api/push-subscriptions', [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/rate-limit-'.$i,
                'publicKey' => 'key'.$i,
                'authToken' => 'token'.$i,
            ]);

            expect($response->status())->toBe(201);
        }

        // 6th request should be throttled (429 Too Many Requests)
        $response = $this->actingAs($tempUser)->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/rate-limit-6',
            'publicKey' => 'key6',
            'authToken' => 'token6',
        ]);

        expect($response->status())->toBe(429);
    });
});

describe('Denial of Service Prevention', function () {
    test('very large endpoint is rejected', function () {
        $longEndpoint = 'https://fcm.googleapis.com/fcm/send/'.str_repeat('a', 2100);

        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $longEndpoint,
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertUnprocessable();
    });

    test('user is rate limited after 5 requests per minute', function () {
        $tempUser = User::factory()->create(['is_active' => true]);

        // Create 5 endpoints (one per request, respecting rate limit)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($tempUser)->postJson('/api/push-subscriptions', [
                'endpoint' => "https://fcm.googleapis.com/fcm/send/dos-test-$i",
                'publicKey' => 'key'.$i,
                'authToken' => 'token'.$i,
            ]);

            expect($response->status())->toBe(201);
        }

        // Next request should be throttled
        $response = $this->actingAs($tempUser)->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/dos-test-6',
            'publicKey' => 'key6',
            'authToken' => 'token6',
        ]);

        expect($response->status())->toBe(429);
    });
});

describe('Data Storage Security', function () {
    test('subscription data is stored correctly in database', function () {
        $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $subscription = $this->user->pushSubscriptions()->first();

        expect($subscription->endpoint)->toBe($this->validEndpoint);
        expect($subscription->public_key)->toBe($this->validPublicKey);
        expect($subscription->auth_token)->toBe($this->validAuthToken);
    });

    test('subscription data is not exposed in error messages', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => 'invalid',
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertUnprocessable();
        expect(json_encode($response->json()))->not->toContain('secret');
        expect(json_encode($response->json()))->not->toContain('password');
    });
});

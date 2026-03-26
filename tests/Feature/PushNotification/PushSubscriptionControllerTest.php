<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['is_active' => true]);
    $this->otherUser = User::factory()->create(['is_active' => true]);
    $this->validEndpoint = 'https://fcm.googleapis.com/fcm/send/valid-endpoint-token';
    $this->validPublicKey = 'BCVxjl8WgP2F_9H7_X_N_1_kV_f3nE_R_5hxH_1gM_vN_0pX_9sQ_6aY';
    $this->validAuthToken = 'LS0tLS1CRUdJTi1DRVJUSUZJQ0FURS0tLS0tLS0K';
});

describe('PushSubscriptionController - Store', function () {
    test('authenticated user can subscribe to push notifications', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'user_id', 'endpoint', 'created_at'],
                'message',
            ]);

        expect($response->json('data.user_id'))->toBe($this->user->id);
        expect($response->json('data.endpoint'))->toBe($this->validEndpoint);
        expect($this->user->pushSubscriptions()->count())->toBe(1);
    });

    test('unauthenticated user cannot subscribe', function () {
        $response = $this->postJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertUnauthorized();
    });

    test('endpoint must be required', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');
    });

    test('endpoint must be valid HTTPS URL', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => 'not-a-url',
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');
    });

    test('endpoint must use HTTPS protocol', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => 'http://fcm.googleapis.com/fcm/send/token',
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');
    });

    test('endpoint must be from known push service', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://unknown-push-service.com/send/token',
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');
    });

    test('endpoint accepts Google FCM endpoints', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123xyz',
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertCreated();
    });

    test('endpoint accepts Mozilla push service endpoints', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://updates.push.services.mozilla.com/send/abc123xyz',
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertCreated();
    });

    test('endpoint accepts Apple push service endpoints', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://api.push.apple.com/3/device/abc123xyz',
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertCreated();
    });

    test('endpoint cannot exceed 2048 characters', function () {
        $longEndpoint = 'https://fcm.googleapis.com/fcm/send/'.str_repeat('a', 2050);

        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $longEndpoint,
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');
    });

    test('publicKey is required', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('publicKey');
    });

    test('publicKey cannot exceed 500 characters', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
            'publicKey' => str_repeat('a', 501),
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('publicKey');
    });

    test('authToken is required', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
            'publicKey' => $this->validPublicKey,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('authToken');
    });

    test('authToken cannot exceed 500 characters', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
            'publicKey' => $this->validPublicKey,
            'authToken' => str_repeat('a', 501),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('authToken');
    });

    test('duplicate endpoint updates keys instead of creating new subscription', function () {
        // First subscription
        $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
            'publicKey' => 'key-v1',
            'authToken' => 'token-v1',
        ]);

        // Second attempt with same endpoint
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
            'publicKey' => 'key-v2',
            'authToken' => 'token-v2',
        ]);

        $response->assertCreated();
        expect($this->user->pushSubscriptions()->count())->toBe(1);
    });

    test('user can have multiple subscriptions from different services', function () {
        $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/endpoint-1',
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => 'https://updates.push.services.mozilla.com/send/endpoint-2',
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        $response->assertCreated();
        expect($this->user->pushSubscriptions()->count())->toBe(2);
    });

    test('response includes success message in Spanish', function () {
        $response = $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        expect($response->json('message'))->toBe('Notificaciones activadas');
    });

    test('subscription is logged to activity log', function () {
        $this->actingAs($this->user)->postJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
            'publicKey' => $this->validPublicKey,
            'authToken' => $this->validAuthToken,
        ]);

        // Check that activity was logged
        $activity = Activity::where('causer_id', $this->user->id)
            ->where('description', 'Push notification subscribed')
            ->first();

        expect($activity)->not->toBeNull();
        expect($activity->properties['service'])->toBe('fcm.googleapis.com');
    });
});

describe('PushSubscriptionController - Destroy', function () {
    test('authenticated user can unsubscribe from push notifications', function () {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/destroy-test-1';
        $this->user->pushSubscriptions()->create([
            'endpoint' => $endpoint,
            'public_key' => $this->validPublicKey,
            'auth_token' => $this->validAuthToken,
        ]);

        expect($this->user->pushSubscriptions()->count())->toBe(1);

        $response = $this->actingAs($this->user)->deleteJson('/api/push-subscriptions', [
            'endpoint' => $endpoint,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message']);

        expect($this->user->pushSubscriptions()->count())->toBe(0);
    });

    test('unauthenticated user cannot unsubscribe', function () {
        $response = $this->deleteJson('/api/push-subscriptions', [
            'endpoint' => $this->validEndpoint,
        ]);

        $response->assertUnauthorized();
    });

    test('endpoint is required for deletion', function () {
        $response = $this->actingAs($this->user)->deleteJson('/api/push-subscriptions', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('endpoint');
    });

    test('returns 404 when subscription not found', function () {
        $response = $this->actingAs($this->user)->deleteJson('/api/push-subscriptions', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/nonexistent',
        ]);

        $response->assertNotFound()
            ->assertJson(['message' => 'Suscripción no encontrada']);
    });

    test('user cannot delete another user\'s subscription', function () {
        $otherUserSubscription = $this->otherUser->pushSubscriptions()->create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/other-endpoint',
            'public_key' => $this->validPublicKey,
            'auth_token' => $this->validAuthToken,
        ]);

        $response = $this->actingAs($this->user)->deleteJson('/api/push-subscriptions', [
            'endpoint' => $otherUserSubscription->endpoint,
        ]);

        $response->assertNotFound();
        expect($this->otherUser->pushSubscriptions()->count())->toBe(1);
    });

    test('response includes success message in Spanish', function () {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/destroy-spanish-msg';
        $this->user->pushSubscriptions()->create([
            'endpoint' => $endpoint,
            'public_key' => $this->validPublicKey,
            'auth_token' => $this->validAuthToken,
        ]);

        $response = $this->actingAs($this->user)->deleteJson('/api/push-subscriptions', [
            'endpoint' => $endpoint,
        ]);

        expect($response->json('message'))->toBe('Notificaciones desactivadas');
    });

    test('unsubscription is logged to activity log', function () {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/destroy-activity-log';
        $this->user->pushSubscriptions()->create([
            'endpoint' => $endpoint,
            'public_key' => $this->validPublicKey,
            'auth_token' => $this->validAuthToken,
        ]);

        $this->actingAs($this->user)->deleteJson('/api/push-subscriptions', [
            'endpoint' => $endpoint,
        ]);

        $activity = Activity::where('causer_id', $this->user->id)
            ->where('description', 'Push notification unsubscribed')
            ->latest()
            ->first();

        expect($activity)->not->toBeNull();
    });

    test('can delete multiple subscriptions independently', function () {
        $endpoint1 = 'https://fcm.googleapis.com/fcm/send/destroy-multi-1';
        $endpoint2 = 'https://updates.push.services.mozilla.com/send/destroy-multi-2';

        $this->user->pushSubscriptions()->create([
            'endpoint' => $endpoint1,
            'public_key' => $this->validPublicKey,
            'auth_token' => $this->validAuthToken,
        ]);

        $this->user->pushSubscriptions()->create([
            'endpoint' => $endpoint2,
            'public_key' => $this->validPublicKey,
            'auth_token' => $this->validAuthToken,
        ]);

        expect($this->user->pushSubscriptions()->count())->toBe(2);

        $this->actingAs($this->user)->deleteJson('/api/push-subscriptions', [
            'endpoint' => $endpoint1,
        ]);

        expect($this->user->pushSubscriptions()->count())->toBe(1);
        expect($this->user->pushSubscriptions()->first()->endpoint)
            ->toBe($endpoint2);
    });
});

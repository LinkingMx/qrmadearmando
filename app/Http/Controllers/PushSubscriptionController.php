<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => [
                'required',
                'string',
                'url',
                'regex:/^https:\/\//',
                'max:2048',
                function ($attribute, $value, $fail) {
                    $allowedDomains = [
                        'fcm.googleapis.com',
                        'updates.push.services.mozilla.com',
                        'api.push.apple.com',
                    ];

                    $parsed = parse_url($value);
                    $host = $parsed['host'] ?? '';

                    $isValid = collect($allowedDomains)->some(
                        fn ($domain) => str_ends_with($host, $domain)
                    );

                    if (! $isValid) {
                        $fail('El endpoint debe ser de un servicio push conocido');
                    }
                },
            ],
            'publicKey' => 'required|string|max:500',
            'authToken' => 'required|string|max:500',
        ]);

        $subscription = $request->user()->pushSubscriptions()
            ->firstOrCreate(
                ['endpoint' => $validated['endpoint']],
                [
                    'public_key' => $validated['publicKey'],
                    'auth_token' => $validated['authToken'],
                ]
            );

        activity()
            ->causedBy($request->user())
            ->withProperties([
                'service' => parse_url($validated['endpoint'], PHP_URL_HOST),
                'subscription_id' => $subscription->id,
            ])
            ->log('Push notification subscribed');

        return response()->json([
            'data' => [
                'id' => $subscription->id,
                'user_id' => $subscription->subscribable_id,
                'endpoint' => $subscription->endpoint,
                'created_at' => $subscription->created_at,
            ],
            'message' => 'Notificaciones activadas',
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|url',
        ]);

        $subscription = $request->user()->pushSubscriptions()
            ->where('endpoint', $validated['endpoint'])
            ->first();

        if (! $subscription) {
            return response()->json([
                'message' => 'Suscripción no encontrada',
            ], 404);
        }

        if (! $request->user()->ownsPushSubscription($subscription)) {
            return response()->json([
                'message' => 'No autorizado',
            ], 403);
        }

        activity()
            ->causedBy($request->user())
            ->withProperties([
                'service' => parse_url($subscription->endpoint, PHP_URL_HOST),
                'subscription_id' => $subscription->id,
            ])
            ->log('Push notification unsubscribed');

        $subscription->delete();

        return response()->json([
            'message' => 'Notificaciones desactivadas',
        ]);
    }
}

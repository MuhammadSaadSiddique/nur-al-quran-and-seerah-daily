<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Register or update a device token.
     */
    public static function registerToken(string $token, string $platform = 'android', ?string $deviceName = null, ?int $userId = null, ?string $appVersion = null): DeviceToken
    {
        $existing = DeviceToken::where('token', $token)->first();

        $attributes = [
            'platform' => $platform,
            'device_name' => $deviceName,
            'app_version' => $appVersion,
            'is_active' => true,
            'last_active_at' => now(),
        ];

        // Never unbind an existing user_id via anonymous registration; only set when authenticated.
        if ($userId !== null) {
            $attributes['user_id'] = $userId;
        } elseif (!$existing) {
            $attributes['user_id'] = null;
        }

        return DeviceToken::updateOrCreate(
            ['token' => $token],
            $attributes
        );
    }

    /**
     * Send push notification to all active devices.
     * Supports FCM HTTP v1 API (Service Account JSON) and falls back to Legacy HTTP API or in-app worker sync.
     */
    public static function broadcast(AppNotification $notification): int
    {
        $tokens = DeviceToken::where('is_active', true)->pluck('token')->toArray();

        if (empty($tokens)) {
            Log::info("PushNotificationService: No registered device tokens found. Notification ID {$notification->id} saved for in-app sync.");
            return 0;
        }

        // 1. Try FCM HTTP v1 API using Service Account JSON if configured
        $serviceAccount = self::loadServiceAccountCredentials();
        if ($serviceAccount) {
            $accessToken = self::fetchGoogleAccessToken($serviceAccount);
            $projectId = $serviceAccount['project_id'] ?? config('services.firebase.project_id');

            if ($accessToken && $projectId) {
                return self::broadcastFcmV1($notification, $tokens, $projectId, $accessToken);
            }
        }

        // 2. Fallback to Legacy FCM Server Key if present
        $serverKey = config('services.firebase.server_key');
        if (empty($serverKey)) {
            Log::info("PushNotificationService: FCM credentials not configured. Notification ID {$notification->id} saved for polling & background worker sync.");
            $notification->update(['delivered_devices_count' => count($tokens)]);
            return count($tokens);
        }

        $sentCount = 0;
        $chunks = array_chunk($tokens, 100);

        foreach ($chunks as $chunk) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'key=' . $serverKey,
                    'Content-Type' => 'application/json',
                ])->timeout(10)->post('https://fcm.googleapis.com/fcm/send', [
                    'registration_ids' => $chunk,
                    'data' => [
                        'notification_id' => (string)$notification->id,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'type' => (string)$notification->type,
                        'action_url' => (string)($notification->action_url ?? ''),
                        'created_at' => $notification->created_at ? $notification->created_at->toIso8601String() : '',
                    ],
                    'priority' => 'high',
                ]);

                if ($response->successful()) {
                    $resJson = $response->json();
                    $success = $resJson['success'] ?? 0;
                    $sentCount += $success;

                    if (!empty($resJson['results'])) {
                        foreach ($resJson['results'] as $idx => $result) {
                            if (isset($result['error']) && in_array($result['error'], ['NotRegistered', 'InvalidRegistration'])) {
                                $staleToken = $chunk[$idx] ?? null;
                                if ($staleToken) {
                                    DeviceToken::where('token', $staleToken)->update(['is_active' => false]);
                                }
                            }
                        }
                    }
                } else {
                    Log::warning("FCM send failed with status {$response->status()}: " . $response->body());
                }
            } catch (\Exception $e) {
                Log::error("FCM Exception: " . $e->getMessage());
            }
        }

        $notification->update(['delivered_devices_count' => $sentCount]);
        return $sentCount;
    }

    /**
     * Broadcast using FCM HTTP v1 API.
     */
    protected static function broadcastFcmV1(AppNotification $notification, array $tokens, string $projectId, string $accessToken): int
    {
        $sentCount = 0;
        $endpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        foreach ($tokens as $token) {
            try {
                $response = Http::withToken($accessToken)
                    ->timeout(10)
                    ->post($endpoint, [
                        'message' => [
                            'token' => $token,
                            'data' => [
                                'notification_id' => (string)$notification->id,
                                'title' => $notification->title,
                                'message' => $notification->message,
                                'type' => (string)$notification->type,
                                'action_url' => (string)($notification->action_url ?? ''),
                                'created_at' => $notification->created_at ? $notification->created_at->toIso8601String() : '',
                            ],
                            'android' => [
                                'priority' => 'HIGH',
                            ],
                        ],
                    ]);

                if ($response->successful()) {
                    $sentCount++;
                } else {
                    $errorBody = $response->json();
                    $errorCode = $errorBody['error']['details'][0]['errorCode']
                        ?? $errorBody['error']['status']
                        ?? '';

                    if (in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND'])) {
                        DeviceToken::where('token', $token)->update(['is_active' => false]);
                    } else {
                        Log::warning("FCM v1 send failed ({$response->status()}): " . $response->body());
                    }
                }
            } catch (\Exception $e) {
                Log::error("FCM v1 Exception: " . $e->getMessage());
            }
        }

        $notification->update(['delivered_devices_count' => $sentCount]);
        return $sentCount;
    }

    /**
     * Load Firebase Service Account credentials from .env variables or JSON file in storage/app/.
     */
    protected static function loadServiceAccountCredentials(): ?array
    {
        // Option 1: Config / .env variables (no separate JSON file needed on server)
        $clientEmail = config('services.firebase.client_email');
        $privateKey = config('services.firebase.private_key');
        if (!empty($clientEmail) && !empty($privateKey)) {
            return [
                'project_id' => config('services.firebase.project_id'),
                'client_email' => $clientEmail,
                'private_key' => str_replace('\n', "\n", $privateKey),
                'token_uri' => 'https://oauth2.googleapis.com/token',
            ];
        }

        // Option 2: Service Account JSON file in storage/app/ (outside public/ web root)
        $configuredPath = config('services.firebase.credentials');
        $candidates = array_filter([
            $configuredPath,
            $configuredPath ? base_path($configuredPath) : null,
            storage_path('app/firebase-service-account.json'),
            storage_path('app/firebase-service.json'),
        ]);

        foreach ($candidates as $path) {
            if (is_string($path) && file_exists($path)) {
                $decoded = json_decode((string)file_get_contents($path), true);
                if (is_array($decoded) && !empty($decoded['client_email']) && !empty($decoded['private_key'])) {
                    return $decoded;
                }
            }
        }

        return null;
    }

    /**
     * Mint a short-lived Google OAuth2 access token for FCM HTTP v1 using the Service Account private key.
     */
    protected static function fetchGoogleAccessToken(array $serviceAccount): ?string
    {
        try {
            $now = time();
            $header = self::base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claimSet = self::base64UrlEncode(json_encode([
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $serviceAccount['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $unsignedJwt = "{$header}.{$claimSet}";
            $signature = '';
            $signed = openssl_sign($unsignedJwt, $signature, $serviceAccount['private_key'], OPENSSL_ALGO_SHA256);
            if (!$signed) {
                return null;
            }

            $jwt = "{$unsignedJwt}." . self::base64UrlEncode($signature);

            $response = Http::asForm()->timeout(10)->post(
                $serviceAccount['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]
            );

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::warning("Failed to fetch FCM OAuth2 access token: " . $response->body());
        } catch (\Exception $e) {
            Log::error("FCM OAuth2 token exception: " . $e->getMessage());
        }

        return null;
    }

    protected static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

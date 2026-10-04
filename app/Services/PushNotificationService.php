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
        return DeviceToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $userId,
                'platform' => $platform,
                'device_name' => $deviceName,
                'app_version' => $appVersion,
                'is_active' => true,
                'last_active_at' => now(),
            ]
        );
    }

    /**
     * Send push notification to all active devices.
     */
    public static function broadcast(AppNotification $notification): int
    {
        $tokens = DeviceToken::where('is_active', true)->pluck('token')->toArray();

        if (empty($tokens)) {
            Log::info("PushNotificationService: No registered device tokens found. Notification ID {$notification->id} saved for in-app sync.");
            return 0;
        }

        $serverKey = env('FIREBASE_SERVER_KEY') ?: env('FCM_SERVER_KEY');
        if (empty($serverKey)) {
            Log::info("PushNotificationService: FCM Server Key not configured in .env. Notification ID {$notification->id} saved for polling & background worker sync.");
            // Even if FCM is not configured, the device worker will fetch and alert the user.
            $notification->update(['delivered_devices_count' => count($tokens)]);
            return count($tokens);
        }

        $sentCount = 0;
        // Batch into chunks of 100 for FCM legacy endpoint or individual calls
        $chunks = array_chunk($tokens, 100);

        foreach ($chunks as $chunk) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'key=' . $serverKey,
                    'Content-Type' => 'application/json',
                ])->timeout(10)->post('https://fcm.googleapis.com/fcm/send', [
                    'registration_ids' => $chunk,
                    'notification' => [
                        'title' => $notification->title,
                        'body' => $notification->message,
                        'sound' => 'default',
                        'click_action' => 'OPEN_MAIN_ACTIVITY',
                    ],
                    'data' => [
                        'notification_id' => (string)$notification->id,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'type' => $notification->type,
                        'created_at' => $notification->created_at->toIso8601String(),
                    ],
                    'priority' => 'high',
                ]);

                if ($response->successful()) {
                    $resJson = $response->json();
                    $success = $resJson['success'] ?? 0;
                    $sentCount += $success;

                    // Clean up invalid tokens if FCM reported failures
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
}

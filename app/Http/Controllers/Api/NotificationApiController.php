<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    /**
     * Retrieve notifications for mobile devices.
     */
    public function index(Request $request)
    {
        $sinceId = $request->query('since_id');
        $limit = min((int)$request->query('limit', 20), 50);

        $query = AppNotification::where('send_push', true);

        if (!empty($sinceId) && is_numeric($sinceId)) {
            $query->where('id', '>', (int)$sinceId);
        }

        $notifications = $query->latest('id')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'message' => $item->message,
                    'type' => $item->type,
                    'action_url' => $item->action_url,
                    'created_at' => $item->created_at ? $item->created_at->toIso8601String() : null,
                ];
            });

        return response()->json([
            'status' => 'success',
            'count' => $notifications->count(),
            'data' => $notifications,
        ]);
    }

    /**
     * Register or update a device token for push notifications.
     */
    public function registerToken(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string|max:500',
            'platform' => 'nullable|string|max:50',
            'device_name' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50',
        ]);

        $userId = auth('sanctum')->id() ?? $request->input('user_id');

        $deviceToken = PushNotificationService::registerToken(
            $validated['token'],
            $validated['platform'] ?? 'android',
            $validated['device_name'] ?? null,
            $userId ? (int)$userId : null,
            $validated['app_version'] ?? null
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Device token successfully registered',
            'data' => [
                'id' => $deviceToken->id,
                'platform' => $deviceToken->platform,
                'is_active' => $deviceToken->is_active,
            ],
        ]);
    }
}

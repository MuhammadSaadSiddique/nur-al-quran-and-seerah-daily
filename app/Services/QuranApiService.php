<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuranApiService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.quran.api_url') . '/auth/v1';
    }

    /**
     * Call a User API endpoint on Quran Foundation using the user's access token
     */
    public function getUserData(string $endpoint, string $accessToken): ?array
    {
        try {
            $response = Http::withHeaders([
                'x-auth-token' => $accessToken,
                'x-client-id' => config('services.quran.client_id'),
            ])->get("{$this->baseUrl}{$endpoint}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Quran Api Service Error - {$endpoint}", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Quran Api Service Exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Refresh the user's OAuth access token.
     */
    public function refreshToken(User $user): bool
    {
        if (empty($user->quran_refresh_token)) {
            Log::warning('Cannot refresh Quran API token: No refresh token available', ['user_id' => $user->id]);
            return false;
        }

        try {
            $authUrl = config('services.quran.auth_url');
            $response = Http::asForm()->post("{$authUrl}/oauth2/token", [
                'grant_type' => 'refresh_token',
                'client_id' => config('services.quran.client_id'),
                'client_secret' => config('services.quran.client_secret'),
                'refresh_token' => $user->quran_refresh_token,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $user->quran_access_token = $data['access_token'] ?? $user->quran_access_token;
                if (!empty($data['refresh_token'])) {
                    $user->quran_refresh_token = $data['refresh_token'];
                }
                $user->save();
                return true;
            }

            Log::error('Failed to refresh Quran API token', [
                'user_id' => $user->id,
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Quran Api Service Exception during refresh', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Post data to the Quran Foundation API
     */
    public function postUserData(string $endpoint, array $data, User $user, bool $retry = true): ?array
    {
        if (empty($user->quran_access_token)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'x-auth-token' => $user->quran_access_token,
                'x-client-id' => config('services.quran.client_id'),
            ])->post("{$this->baseUrl}{$endpoint}", $data);

            if ($response->successful()) {
                return $response->json();
            }

            // Handle unauthorized - try token refresh
            if ($response->status() === 401 && $retry) {
                Log::info('Quran API returned 401 on POST, attempting token refresh', ['user_id' => $user->id]);
                if ($this->refreshToken($user)) {
                    // Retry once
                    return $this->postUserData($endpoint, $data, $user, false);
                }
            }

            Log::error("Quran Api Service POST Error - {$endpoint}", [
                'status' => $response->status(),
                'body' => $response->body(),
                'data' => $data
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Quran Api Service POST Exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get user bookmarks from Quran Foundation
     */
    public function getQuranBookmarks(User $user): ?array
    {
        if (empty($user->quran_access_token)) {
            return null;
        }

        $data = $this->getUserData('/bookmarks', $user->quran_access_token);
        return $data['data'] ?? [];
    }

    /**
     * Get user current streak from Quran Foundation
     */
    public function getUserStreak(User $user): ?int
    {
        if (empty($user->quran_access_token)) {
            return null;
        }

        // Attempting to get streaks
        $data = $this->getUserData('/streaks', $user->quran_access_token);

        if (isset($data['data']) && is_array($data['data'])) {
            // Find active streak
            foreach ($data['data'] as $streak) {
                if (($streak['status'] ?? '') === 'ACTIVE') {
                    return $streak['days'] ?? 0;
                }
            }
            return 0; // No active streak
        }
        return null;
    }

    /**
     * Post activity day to Quran Foundation
     */
    public function postActivityDay(User $user): bool
    {
        if (empty($user->quran_access_token)) {
            return false;
        }

        // Activity day usually requires just posting to /activity-days or /reading-sessions
        $response = $this->postUserData('/activity-days', [], $user);

        return $response !== null;
    }
}

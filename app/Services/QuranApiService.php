<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuranApiService
{
    protected string $baseUrl = 'https://apis.quran.foundation/auth/v1';

    /**
     * Call a User API endpoint on Quran Foundation using the user's access token
     * 
     * @param string $endpoint e.g. "/bookmarks"
     * @param string $accessToken The exact quran_access_token from the User model
     * @return array|null The JSON decoded response, or null if it failed
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
}

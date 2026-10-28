<?php

namespace App\Services;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class MobileApiAuthService
{
    public const TOKEN_NAME = 'mobile';

    /** Default mobile token lifetime (minutes). Overridden by config('sanctum.expiration'). */
    public const DEFAULT_EXPIRATION_MINUTES = 43200; // 30 days

    public static function issueToken(User $user): string
    {
        $user->tokens()->where('name', self::TOKEN_NAME)->delete();

        $minutes = (int) (config('sanctum.expiration') ?: self::DEFAULT_EXPIRATION_MINUTES);
        $expiresAt = now()->addMinutes(max(60, $minutes));

        return $user->createToken(self::TOKEN_NAME, ['*'], $expiresAt)->plainTextToken;
    }

    public static function userFromRequest(): ?User
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        if ($header === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $key => $value) {
                if (strcasecmp((string) $key, 'Authorization') === 0) {
                    $header = (string) $value;
                    break;
                }
            }
        }

        if (!preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($matches[1]);
        if (!$accessToken || !$accessToken->tokenable instanceof User) {
            return null;
        }

        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            $accessToken->delete();

            return null;
        }

        $expirationMinutes = config('sanctum.expiration');
        if ($expirationMinutes && $accessToken->created_at->lte(now()->subMinutes((int) $expirationMinutes))) {
            $accessToken->delete();

            return null;
        }

        return $accessToken->tokenable;
    }

    /**
     * Require a valid Sanctum mobile token. Echoes JSON 401 and exits on failure.
     */
    public static function requireUser(): User
    {
        $user = self::userFromRequest();
        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Unauthorized. Please log in again.',
            ]);
            exit;
        }

        return $user;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class OtpRateLimiter
{
    public static function tooManyRequests(string $email, string $action = 'request'): bool
    {
        $email = strtolower(trim($email));
        $ip = request()->ip() ?: 'unknown';

        if ($action === 'request') {
            $emailKey = "otp:request:email:{$email}";
            $ipKey = "otp:request:ip:{$ip}";
            $emailLimit = 5;
            $ipLimit = 20;
            $decay = 3600;
        } else {
            $emailKey = "otp:verify:email:{$email}";
            $ipKey = "otp:verify:ip:{$ip}";
            $emailLimit = 10;
            $ipLimit = 40;
            $decay = 900;
        }

        $emailCount = (int) Cache::get($emailKey, 0);
        $ipCount = (int) Cache::get($ipKey, 0);

        return $emailCount >= $emailLimit || $ipCount >= $ipLimit;
    }

    public static function hit(string $email, string $action = 'request'): void
    {
        $email = strtolower(trim($email));
        $ip = request()->ip() ?: 'unknown';

        if ($action === 'request') {
            $emailKey = "otp:request:email:{$email}";
            $ipKey = "otp:request:ip:{$ip}";
            $decay = 3600;
        } else {
            $emailKey = "otp:verify:email:{$email}";
            $ipKey = "otp:verify:ip:{$ip}";
            $decay = 900;
        }

        if (!Cache::has($emailKey)) {
            Cache::put($emailKey, 1, $decay);
        } else {
            Cache::increment($emailKey);
        }

        if (!Cache::has($ipKey)) {
            Cache::put($ipKey, 1, $decay);
        } else {
            Cache::increment($ipKey);
        }
    }

    public static function clearVerifyAttempts(string $email): void
    {
        $email = strtolower(trim($email));
        Cache::forget("otp:verify:email:{$email}");
    }
}

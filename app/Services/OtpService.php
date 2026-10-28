<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public static function issue(string $email, int $ttlMinutes = 5): string
    {
        $email = strtolower(trim($email));
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::where('email', $email)->delete();

        OtpCode::create([
            'email' => $email,
            'otp' => Hash::make($otp),
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        return $otp;
    }

    /**
     * Issue an OTP and email it. Rolls back the DB record if mail fails.
     *
     * @throws \RuntimeException when the email cannot be delivered
     */
    public static function issueAndSend(string $email, int $ttlMinutes = 5): void
    {
        $email = strtolower(trim($email));
        $otp = self::issue($email, $ttlMinutes);

        try {
            Mail::to($email)->send(new OtpMail($otp));
        } catch (\Throwable $e) {
            OtpCode::where('email', $email)->delete();
            Log::error('Failed to send OTP email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Unable to send verification email. Please try again later.', 0, $e);
        }
    }

    public static function verify(string $email, string $otp): bool
    {
        $email = strtolower(trim($email));
        $otp = trim($otp);
        $record = OtpCode::where('email', $email)->latest()->first();

        if (!$record) {
            return false;
        }

        if ($record->isExpired()) {
            $record->delete();
            return false;
        }

        $stored = (string) $record->otp;
        $valid = self::matches($stored, $otp);

        if ($valid) {
            $record->delete();
        }

        return $valid;
    }

    public static function matches(string $stored, string $otp): bool
    {
        if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2a$') || str_starts_with($stored, '$argon')) {
            return Hash::check($otp, $stored);
        }

        // Legacy plaintext OTPs (pre-hash migration); still expire via isExpired().
        return hash_equals($stored, $otp);
    }
}

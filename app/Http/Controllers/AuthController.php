<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GeminiService;
use App\Services\OtpRateLimiter;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('auth.login');
    }

    public function requestOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = strtolower(trim($request->email));

        if (OtpRateLimiter::tooManyRequests($email, 'request')) {
            return response()->json([
                'success' => false,
                'error' => 'Too many verification requests. Please try again later.',
            ], 429);
        }

        try {
            OtpService::issueAndSend($email, 5);
            OtpRateLimiter::hit($email, 'request');
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 503);
        }

        try {
            $gemini = app(GeminiService::class);
            $welcome = $gemini->generateSpiritualWelcome($email);
        } catch (\Exception $e) {
            Log::warning('Gemini API failed for welcome message', ['error' => $e->getMessage()]);
            $welcome = null;
        }

        if (empty($welcome)) {
            $welcome = 'Assalamu Alaikum! Welcome to your spiritual knowledge journey. May this path bring you closer to understanding the Quran and the beautiful Seerah of Prophet Muhammad ﷺ.';
        }

        return response()->json([
            'success' => true,
            'message' => "Verification code sent to {$email}",
            'welcome' => $welcome,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
        ]);

        $email = strtolower(trim($request->email));

        if (OtpRateLimiter::tooManyRequests($email, 'verify')) {
            return response()->json(['error' => 'Too many verification attempts. Please try again later.'], 429);
        }

        if (!OtpService::verify($email, $request->otp)) {
            OtpRateLimiter::hit($email, 'verify');
            return response()->json(['error' => 'Invalid or expired OTP. Please request a new one.'], 400);
        }

        OtpRateLimiter::clearVerifyAttempts($email);

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => explode('@', $email)[0]]
        );

        Auth::login($user, true);
        $user->checkInStreak();
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'redirect' => route('home'),
            'has_password' => !empty($user->password),
            'csrf_token' => csrf_token(),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function redirectToQuran(Request $request)
    {
        $state = \Illuminate\Support\Str::random(40);
        $nonce = \Illuminate\Support\Str::random(40);
        $codeVerifier = \Illuminate\Support\Str::random(128);
        $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

        $request->session()->put('quran_oauth_state', $state);
        $request->session()->put('quran_oauth_nonce', $nonce);
        $request->session()->put('quran_oauth_code_verifier', $codeVerifier);

        $query = http_build_query([
            'client_id' => config('services.quran.client_id'),
            'redirect_uri' => config('services.quran.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'openid user bookmark collection reading_session preference streak offline_access',
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        $authUrl = rtrim((string) config('services.quran.auth_url'), '/');
        return redirect("{$authUrl}/oauth2/auth?" . $query);
    }

    public function handleQuranCallback(Request $request)
    {
        $state = $request->session()->pull('quran_oauth_state');
        $codeVerifier = $request->session()->pull('quran_oauth_code_verifier');
        $request->session()->forget('quran_oauth_nonce');

        if (empty($state) || !hash_equals((string) $state, (string) $request->state)) {
            return redirect()->route('login')->withErrors(['email' => 'Invalid state in OAuth callback']);
        }

        if (empty($request->code) || empty($codeVerifier)) {
            return redirect()->route('login')->withErrors(['email' => 'Authentication failed with Quran.com']);
        }

        $authUrl = rtrim((string) config('services.quran.auth_url'), '/');
        $response = Http::asForm()->post("{$authUrl}/oauth2/token", [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.quran.client_id'),
            'client_secret' => config('services.quran.client_secret'),
            'code' => $request->code,
            'redirect_uri' => config('services.quran.redirect_uri'),
            'code_verifier' => $codeVerifier,
        ]);

        if ($response->failed()) {
            Log::error('Quran.com token exchange failed', ['error' => $response->body()]);
            return redirect()->route('login')->withErrors(['email' => 'Authentication failed with Quran.com']);
        }

        $tokens = $response->json();
        $accessToken = $tokens['access_token'] ?? null;
        if (empty($accessToken)) {
            return redirect()->route('login')->withErrors(['email' => 'Authentication failed with Quran.com']);
        }

        // Prefer OIDC userinfo (server-validated) over decoding the id_token locally.
        $userInfoResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->get("{$authUrl}/userinfo");

        if ($userInfoResponse->failed()) {
            Log::error('Quran.com userinfo failed', ['error' => $userInfoResponse->body()]);
            return redirect()->route('login')->withErrors(['email' => 'Could not determine user identity from Quran.com']);
        }

        $payload = $userInfoResponse->json() ?: [];
        $quranUserId = $payload['sub'] ?? null;
        $email = isset($payload['email']) ? strtolower(trim((string) $payload['email'])) : null;
        $name = trim(($payload['name'] ?? '') ?: trim(($payload['first_name'] ?? '') . ' ' . ($payload['last_name'] ?? '')));

        if (!$quranUserId) {
            return redirect()->route('login')->withErrors(['email' => 'Could not determine user identity from Quran.com']);
        }

        $user = User::where('quran_user_id', $quranUserId)->first();

        // Only auto-link by email when the signed-in session already owns that account
        // (prevents email-based account takeover via OAuth).
        if (!$user && $email && Auth::check() && strcasecmp((string) Auth::user()->email, $email) === 0) {
            $user = Auth::user();
            if (!$user->quran_user_id) {
                $user->quran_user_id = $quranUserId;
            }
        }

        if (!$user && $email) {
            $existingByEmail = User::where('email', $email)->first();
            if ($existingByEmail && empty($existingByEmail->quran_user_id)) {
                // Require explicit login first before linking an existing local account.
                return redirect()->route('login')->withErrors([
                    'email' => 'An account with this email already exists. Please log in first, then connect Quran.com from your profile.',
                ]);
            }
        }

        if (!$user) {
            $user = new User();
            $user->email = $email ?: "quran_{$quranUserId}@example.com";
            $user->name = $name !== '' ? $name : (explode('@', (string) $email)[0] ?: 'Quran User');
            $user->quran_user_id = $quranUserId;
        } else {
            if (!$user->quran_user_id) {
                $user->quran_user_id = $quranUserId;
            }
            if ($name !== '' && empty($user->name)) {
                $user->name = $name;
            }
        }

        $user->quran_access_token = $accessToken;
        if (!empty($tokens['refresh_token'])) {
            $user->quran_refresh_token = $tokens['refresh_token'];
        }
        $user->save();

        Auth::login($user, true);
        $user->checkInStreak();
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function loginWithPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if ($user && !empty($user->password)
            && \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            Auth::login($user, true);
            $user->checkInStreak();
            $request->session()->regenerate();

            return response()->json([
                'success' => true,
                'redirect' => route('home'),
            ]);
        }

        // Uniform error — do not reveal OTP-only vs missing accounts.
        return response()->json([
            'success' => false,
            'error' => 'Invalid email or password.',
        ], 400);
    }
}

<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

        // Delete any existing OTPs for this email
        OtpCode::where('email', $email)->delete();

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'email' => $email,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(5),
        ]);

        // Send OTP via mail
        try {
            Mail::to($email)->send(new OtpMail($otp));
        } catch (\Exception $e) {
            Log::error('Failed to send OTP email', ['error' => $e->getMessage()]);
        }

        Log::info("OTP for {$email}: {$otp}");

        // Generate spiritual welcome (with fallback)
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
        $record = OtpCode::where('email', $email)->latest()->first();

        if (!$record) {
            return response()->json(['error' => 'OTP not found. Please request a new one.'], 400);
        }

        if ($record->isExpired()) {
            $record->delete();
            return response()->json(['error' => 'OTP expired. Please request a new one.'], 400);
        }

        if ($record->otp !== $request->otp) {
            return response()->json(['error' => 'Invalid OTP. Please try again.'], 400);
        }

        // OTP valid — clean up
        $record->delete();

        // Find or create user
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => explode('@', $email)[0]]
        );

        Auth::login($user, true);

        return response()->json(['success' => true, 'redirect' => route('home')]);
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

        return redirect('https://auth.quran.foundation/oauth2/auth?' . $query);
    }

    public function handleQuranCallback(Request $request)
    {
        $state = $request->session()->pull('quran_oauth_state');
        $codeVerifier = $request->session()->pull('quran_oauth_code_verifier');

        if (strlen((string) $state) > 0 && $state !== $request->state) {
            return redirect()->route('login')->withErrors(['email' => 'Invalid state in OAuth callback']);
        }

        $response = \Illuminate\Support\Facades\Http::asForm()->post('https://auth.quran.foundation/oauth2/token', [
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
        $idTokenParts = explode('.', $tokens['id_token']);
        // The id_token is a JWT: header.payload.signature
        $jwtPayload = $idTokenParts[1] ?? '';
        // Add padding if necessary
        $pad = strlen($jwtPayload) % 4;
        if ($pad) $jwtPayload .= str_repeat('=', 4 - $pad);
        $payload = json_decode(base64_decode(strtr($jwtPayload, '-_', '+/')), true);
        
        $quranUserId = $payload['sub'] ?? null;
        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? null;
        
        if (!$quranUserId) {
            return redirect()->route('login')->withErrors(['email' => 'Could not determine user identity from Quran.com']);
        }

        $user = User::where('quran_user_id', $quranUserId)->first();
        
        if (!$user && $email) {
            $user = User::where('email', $email)->first();
        }
        
        if (!$user) {
            $user = User::create([
                'email' => $email ?? "quran_{$quranUserId}@example.com",
                'name' => $name ?? explode('@', $email)[0] ?? 'Quran User',
                'quran_user_id' => $quranUserId,
            ]);
        } else {
            // Ensure if logging in effectively links account if not already linked
            if (!$user->quran_user_id) {
                $user->quran_user_id = $quranUserId;
            }
        }
        
        $user->quran_access_token = $tokens['access_token'] ?? null;
        $user->quran_refresh_token = $tokens['refresh_token'] ?? $user->quran_refresh_token; // keep old if null
        $user->save();

        Auth::login($user, true);

        return redirect()->route('home');
    }
}

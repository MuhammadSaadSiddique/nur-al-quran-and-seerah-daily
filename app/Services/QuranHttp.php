<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class QuranHttp
{
    /**
     * Windows PHP often has no CA bundle configured. Use the system bundle,
     * then a local Git bundle, so Quran.com can be verified.
     *
     * @return array<string, mixed>
     */
    public static function options(): array
    {
        $configured = (string) config('services.quran_translations.ca_bundle');
        $candidates = array_filter([
            $configured,
            (string) ini_get('curl.cainfo'),
            (string) ini_get('openssl.cafile'),
            'C:\\Program Files\\Git\\mingw64\\etc\\ssl\\certs\\ca-bundle.crt',
            'C:\\Program Files\\Git\\usr\\ssl\\certs\\ca-bundle.crt',
        ]);

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return ['verify' => $path];
            }
        }

        return [];
    }

    public static function pending(int $timeout = 20): PendingRequest
    {
        return Http::withOptions(self::options())
            ->connectTimeout(8)
            ->timeout($timeout)
            ->acceptJson();
    }
}

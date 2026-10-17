<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuranTranslationService
{
    /**
     * Published translations served by AlQuran Cloud. Display names stay in English
     * so the reader can switch between them without decoding the edition id.
     */
    public const EDITIONS = [
        'en.sahih' => ['name' => 'Saheeh International', 'language' => 'en', 'direction' => 'ltr'],
        'en.pickthall' => ['name' => 'Pickthall', 'language' => 'en', 'direction' => 'ltr'],
        'en.yusufali' => ['name' => 'Yusuf Ali', 'language' => 'en', 'direction' => 'ltr'],
        'en.hilali' => ['name' => 'Hilali & Khan', 'language' => 'en', 'direction' => 'ltr'],
        'ur.jalandhry' => ['name' => 'Jalandhry (Urdu)', 'language' => 'ur', 'direction' => 'rtl'],
        'ur.maududi' => ['name' => 'Maududi (Urdu)', 'language' => 'ur', 'direction' => 'rtl'],
    ];

    /**
     * @return list<array{id: string, name: string, language: string, direction: string}>
     */
    public function editions(): array
    {
        $editions = [];
        foreach (self::EDITIONS as $id => $meta) {
            $editions[] = [
                'id' => $id,
                'name' => $meta['name'],
                'language' => $meta['language'],
                'direction' => $meta['direction'],
            ];
        }

        return $editions;
    }

    /**
     * Translations for every ayah in a surah, keyed by verse number.
     *
     * @return array<int, list<array{id: string, name: string, language: string, direction: string, text: string}>>
     */
    public function forSurah(int $surahNumber): array
    {
        if ($surahNumber < 1 || $surahNumber > 114) {
            return [];
        }

        $cacheKey = $this->cacheKey($surahNumber);
        $cached = $this->cacheGet($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $fetched = $this->fetchSurah($surahNumber);
        $this->cachePut(
            $cacheKey,
            $fetched,
            $fetched === [] ? now()->addMinutes(10) : now()->addDays(30)
        );

        return $fetched;
    }

    /**
     * @return list<array{id: string, name: string, language: string, direction: string, text: string}>
     */
    public function forVerse(int $surahNumber, int $verseNumber): array
    {
        if ($verseNumber < 1) {
            return [];
        }

        $byVerse = $this->forSurah($surahNumber);

        return $byVerse[$verseNumber] ?? [];
    }

    /**
     * Catalog entries that actually came back for this surah, in display order.
     *
     * @param  array<int, list<array{id: string, name: string, language: string, direction: string, text: string}>>  $byVerse
     * @return list<array{id: string, name: string, language: string, direction: string}>
     */
    public function editionsPresent(array $byVerse): array
    {
        $present = [];
        foreach ($byVerse as $items) {
            foreach ($items as $item) {
                $present[$item['id']] = true;
            }
        }

        return array_values(array_filter(
            $this->editions(),
            fn (array $edition) => isset($present[$edition['id']])
        ));
    }

    /**
     * Keep stored translations, then append web translations that are not already shown.
     *
     * @param  list<array{author_name?: string, text?: string, language?: string, direction?: string}>  $local
     * @param  list<array{id: string, name: string, language: string, direction: string, text: string}>  $remote
     * @return list<array{author_name: string, text: string, language: string, direction: string}>
     */
    public function mergeWithLocal(array $local, array $remote): array
    {
        $merged = [];
        foreach ($local as $item) {
            $text = $this->cleanText((string) ($item['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $language = (string) ($item['language'] ?? 'en');
            $merged[] = [
                'author_name' => (string) ($item['author_name'] ?? 'Translation'),
                'text' => $text,
                'language' => $language,
                'direction' => (string) ($item['direction'] ?? $this->directionFor($language)),
            ];
        }

        foreach ($remote as $item) {
            $text = $this->cleanText((string) ($item['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $duplicate = false;
            foreach ($merged as $existing) {
                if ($existing['text'] === $text || mb_strtolower($existing['author_name']) === mb_strtolower((string) $item['name'])) {
                    $duplicate = true;
                    break;
                }
            }
            if ($duplicate) {
                continue;
            }

            $merged[] = [
                'author_name' => (string) $item['name'],
                'text' => $text,
                'language' => (string) $item['language'],
                'direction' => (string) $item['direction'],
            ];
        }

        if ($merged === []) {
            $merged[] = [
                'author_name' => 'Translation',
                'text' => '',
                'language' => 'en',
                'direction' => 'ltr',
            ];
        }

        return $merged;
    }

    /**
     * @return array<int, list<array{id: string, name: string, language: string, direction: string, text: string}>>
     */
    private function fetchSurah(int $surahNumber): array
    {
        $editionIds = implode(',', array_keys(self::EDITIONS));
        $url = rtrim((string) config('services.quran_translations.base_url'), '/')
            . "/surah/{$surahNumber}/editions/{$editionIds}";

        try {
            $response = Http::withOptions($this->httpOptions())
                ->connectTimeout(5)
                ->timeout(25)
                ->acceptJson()
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning('Quran translation request failed', [
                'surah' => $surahNumber,
                'message' => $e->getMessage(),
            ]);

            return [];
        }

        if (!$response->successful() || (int) $response->json('code') !== 200) {
            Log::warning('Quran translation request returned an error', [
                'surah' => $surahNumber,
                'status' => $response->status(),
            ]);

            return [];
        }

        $blocks = $response->json('data');
        if (!is_array($blocks)) {
            return [];
        }

        $byVerse = [];
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }

            $identifier = (string) ($block['edition']['identifier'] ?? '');
            if (!isset(self::EDITIONS[$identifier])) {
                continue;
            }

            $meta = self::EDITIONS[$identifier];
            foreach ($block['ayahs'] ?? [] as $ayah) {
                if (!is_array($ayah)) {
                    continue;
                }

                $number = (int) ($ayah['numberInSurah'] ?? 0);
                $text = $this->cleanText((string) ($ayah['text'] ?? ''));
                if ($number < 1 || $text === '') {
                    continue;
                }

                $byVerse[$number][] = [
                    'id' => $identifier,
                    'name' => $meta['name'],
                    'language' => $meta['language'],
                    'direction' => $meta['direction'],
                    'text' => $text,
                ];
            }
        }

        $order = array_flip(array_keys(self::EDITIONS));
        foreach ($byVerse as $number => $items) {
            usort($items, function (array $a, array $b) use ($order) {
                return ($order[$a['id']] ?? 99) <=> ($order[$b['id']] ?? 99);
            });
            $byVerse[$number] = $items;
        }

        ksort($byVerse);

        return $byVerse;
    }

    private function cacheKey(int $surahNumber): string
    {
        $editionKey = md5(implode(',', array_keys(self::EDITIONS)));

        return "quran.translations.v2.surah.{$surahNumber}.{$editionKey}";
    }

    /**
     * Windows PHP often has no CA bundle configured. Use the system bundle,
     * then a local Git bundle, so the public translation API can be verified.
     *
     * @return array<string, mixed>
     */
    private function httpOptions(): array
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

    private function cacheGet(string $key): mixed
    {
        try {
            return Cache::get($key);
        } catch (\Throwable $e) {
            Log::warning('Quran translation cache could not be read', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function cachePut(string $key, mixed $value, \DateTimeInterface $ttl): void
    {
        try {
            Cache::put($key, $value, $ttl);
        } catch (\Throwable $e) {
            Log::warning('Quran translation cache could not be written', ['message' => $e->getMessage()]);
        }
    }

    private function cleanText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function directionFor(string $language): string
    {
        return in_array($language, ['ur', 'ar', 'fa'], true) ? 'rtl' : 'ltr';
    }
}

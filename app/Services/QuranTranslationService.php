<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class QuranTranslationService
{
    /**
     * Published translations on Quran.com. Display names stay in English
     * so the reader can switch between them without decoding the resource id.
     *
     * @var array<string, array{name: string, language: string, direction: string, resource_id: int}>
     */
    public const EDITIONS = [
        'en.sahih' => ['name' => 'Saheeh International', 'language' => 'en', 'direction' => 'ltr', 'resource_id' => 20],
        'en.pickthall' => ['name' => 'Pickthall', 'language' => 'en', 'direction' => 'ltr', 'resource_id' => 19],
        'en.yusufali' => ['name' => 'Yusuf Ali', 'language' => 'en', 'direction' => 'ltr', 'resource_id' => 22],
        'en.hilali' => ['name' => 'Hilali & Khan', 'language' => 'en', 'direction' => 'ltr', 'resource_id' => 203],
        'ur.jalandhry' => ['name' => 'Jalandhry (Urdu)', 'language' => 'ur', 'direction' => 'rtl', 'resource_id' => 234],
        'ur.maududi' => ['name' => 'Maududi (Urdu)', 'language' => 'ur', 'direction' => 'rtl', 'resource_id' => 97],
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
        $byResource = [];
        foreach (self::EDITIONS as $id => $meta) {
            $byResource[(int) $meta['resource_id']] = $id;
        }

        $url = rtrim((string) config('services.quran_translations.base_url'), '/')
            . "/verses/by_chapter/{$surahNumber}";

        try {
            $response = QuranHttp::pending(25)->get($url, [
                'translations' => implode(',', array_keys($byResource)),
                'per_page' => 286,
                'fields' => 'verse_number',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Quran translation request failed', [
                'surah' => $surahNumber,
                'message' => $e->getMessage(),
            ]);

            return [];
        }

        if (!$response->successful()) {
            Log::warning('Quran translation request returned an error', [
                'surah' => $surahNumber,
                'status' => $response->status(),
            ]);

            return [];
        }

        $verses = $response->json('verses');
        if (!is_array($verses)) {
            return [];
        }

        $byVerse = [];
        foreach ($verses as $verse) {
            if (!is_array($verse)) {
                continue;
            }

            $number = (int) ($verse['verse_number'] ?? 0);
            if ($number < 1) {
                continue;
            }

            foreach ($verse['translations'] ?? [] as $translation) {
                if (!is_array($translation)) {
                    continue;
                }

                $identifier = $byResource[(int) ($translation['resource_id'] ?? 0)] ?? null;
                if ($identifier === null) {
                    continue;
                }

                $text = $this->cleanText((string) ($translation['text'] ?? ''));
                if ($text === '') {
                    continue;
                }

                $meta = self::EDITIONS[$identifier];
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

        return "quran.translations.v3.surah.{$surahNumber}.{$editionKey}";
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
        $text = preg_replace('/<sup\b[^>]*>.*?<\/sup>/isu', '', $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\x{00B9}\x{00B2}\x{00B3}\x{2070}-\x{2079}]/u', '', $text) ?? $text;
        $text = preg_replace('/\s*\[\d{1,3}\]|\s*\(\d{1,2}\)/u', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        // Plain footnote numbers left behind after tags are stripped, e.g. Say,1 "O My servants".
        $text = preg_replace('/(?<=[,.;:!?])\s*\d{1,2}(?=\s*["“”«»]|\s|$)/u', '', $text) ?? $text;
        $text = preg_replace('/,(?=["“”«»])/u', ', ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function directionFor(string $language): string
    {
        return in_array($language, ['ur', 'ar', 'fa'], true) ? 'rtl' : 'ltr';
    }
}

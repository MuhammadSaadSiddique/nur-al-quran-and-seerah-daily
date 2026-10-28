<?php

namespace App\Support;

class LensVerseSeo
{
    public static function title(
        string $surahName,
        int $surahNumber,
        int $verse,
        string $translation,
        array $scienceFields = []
    ): string {
        $prefix = "Surah {$surahName} {$surahNumber}:{$verse}";
        $meaning = self::clip(self::plainMeaning($translation), 80);

        if ($meaning !== '') {
            $room = 65 - mb_strlen($prefix) - 3;
            if ($room >= 18) {
                return $prefix.' — '.rtrim(self::clip($meaning, $room), ' ,;:-');
            }
        }

        if ($scienceFields !== []) {
            $fields = implode(', ', array_slice($scienceFields, 0, 2));

            return self::clip("{$prefix} — {$fields}", 70);
        }

        return self::clip("{$prefix} — Meaning & Analysis", 70);
    }

    public static function description(
        string $surahName,
        string $surahEnglish,
        int $surahNumber,
        int $verse,
        string $arabic,
        string $translation,
        array $scienceFields = []
    ): string {
        $where = 'Surah '.$surahName;
        if ($surahEnglish !== '') {
            $where .= " ({$surahEnglish})";
        }
        $where .= " {$surahNumber}:{$verse}.";

        $arabicBit = self::arabicExcerpt($arabic, 32);
        $suffix = $arabicBit !== '' ? "Arabic: {$arabicBit}. {$where}" : $where;
        if ($scienceFields !== []) {
            $fields = implode(', ', array_slice($scienceFields, 0, 2));
            $suffix = rtrim($suffix, '.').". Science: {$fields}.";
        }

        $room = 160 - mb_strlen($suffix) - 1;
        if ($room < 24) {
            $suffix = $scienceFields !== []
                ? rtrim($where, '.').'. Science: '.implode(', ', array_slice($scienceFields, 0, 2)).'.'
                : $where;
            $room = 160 - mb_strlen($suffix) - 1;
        }

        $meaning = $room >= 25 ? self::clip(self::plainMeaning($translation), $room - 1) : '';
        if ($meaning === '') {
            return self::clip($suffix, 160);
        }

        return rtrim($meaning, '.').'. '.$suffix;
    }

    /**
     * @param  list<string>  $scienceFields
     * @param  list<string>  $scienceTitles
     * @return array<string, mixed>
     */
    public static function article(
        string $headline,
        string $description,
        string $url,
        string $logoUrl,
        string $arabic,
        array $scienceFields = [],
        array $scienceTitles = []
    ): array {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $headline,
            'description' => $description,
            'mainEntityOfPage' => $url,
            'inLanguage' => 'en',
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'The Eternal Echo',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $logoUrl,
                ],
            ],
        ];

        if ($scienceFields !== []) {
            $data['about'] = array_map(
                fn (string $field) => ['@type' => 'Thing', 'name' => $field],
                array_values($scienceFields)
            );
            $mentions = [];
            foreach (array_slice($scienceTitles, 0, 5) as $title) {
                $mentions[] = ['@type' => 'CreativeWork', 'name' => $title];
            }
            if ($mentions !== []) {
                $data['mentions'] = $mentions;
            }
            if (trim($arabic) !== '') {
                $data['citation'] = [
                    '@type' => 'CreativeWork',
                    'inLanguage' => 'ar',
                    'text' => trim($arabic),
                ];
            }

            return $data;
        }

        if (trim($arabic) !== '') {
            $data['about'] = [
                '@type' => 'CreativeWork',
                'inLanguage' => 'ar',
                'text' => trim($arabic),
            ];
        }

        return $data;
    }

    /**
     * @param  list<array{name: string, item: string}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumb(array $items): array
    {
        $list = [];
        foreach (array_values($items) as $index => $item) {
            $list[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['item'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }

    private static function plainMeaning(string $translation): string
    {
        $text = html_entity_decode(strip_tags($translation), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/["“”«»„‟]/u', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text, " \t\n\r\0\x0B,;:-");
    }

    private static function arabicExcerpt(string $arabic, int $max): string
    {
        $arabic = trim(preg_replace('/\s+/u', ' ', $arabic) ?? $arabic);
        if ($arabic === '' || mb_strlen($arabic) <= $max) {
            return $arabic;
        }

        return self::clip($arabic, $max);
    }

    private static function clip(string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if ($text === '' || mb_strlen($text) <= $max) {
            return $text;
        }

        $slice = mb_substr($text, 0, $max);
        $space = mb_strrpos($slice, ' ');
        if ($space !== false && $space > (int) ($max * 0.6)) {
            $slice = mb_substr($slice, 0, $space);
        }

        return rtrim($slice, " \t,;:-—.");
    }
}

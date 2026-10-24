<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class AyatAlKursiRing
{
    public const CHAPTER = 2;

    public const VERSE = 255;

    public const ARABIC = 'الله لا إله إلا هو الحي القيوم لا تأخذه سنة ولا نوم له ما في السماوات وما في الأرض من ذا الذي يشفع عنده إلا بإذنه يعلم ما بين أيديهم وما خلفهم ولا يحيطون بشيء من علمه إلا بما شاء وسع كرسيه السماوات والأرض ولا يئوده حفظهما وهو العلي العظيم';

    public const TRANSLATION = 'Allah — there is no god except Him, the Ever-Living, the Self-Sustaining. Neither drowsiness overtakes Him nor sleep. To Him belongs whatever is in the heavens and whatever is on the earth. Who is it that can intercede with Him except by His permission? He knows what is before them and what will be after them, and they encompass not a thing of His knowledge except for what He wills. His Throne extends over the heavens and the earth, and their preservation does not tire Him. And He is the Most High, the Most Great.';

    public static function matchesQuery(string $query): bool
    {
        $key = mb_strtolower(trim($query));
        $key = preg_replace('/[^a-z0-9]+/u', ' ', $key) ?? '';
        $key = trim(preg_replace('/\s+/', ' ', $key) ?? '');

        if (in_array($key, [
            'ayat ul kursi',
            'ayat al kursi',
            'ayatul kursi',
            'ayatulkursi',
            'verse of the throne',
            'throne verse',
        ], true)) {
            return true;
        }

        return str_contains($key, 'ayat') && str_contains($key, 'kursi');
    }

    public static function ensureStored(): void
    {
        $surahId = DB::table('surahs')->where('number', self::CHAPTER)->value('id');
        if (!$surahId) {
            $surahId = DB::table('surahs')->insertGetId([
                'number' => self::CHAPTER,
                'name_arabic' => 'البقرة',
                'name_transliteration' => 'Al-Baqarah',
                'name_english' => 'The Cow',
                'revelation_type' => 'Medinan',
                'verse_count' => 286,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $verseId = DB::table('verses')
            ->where('surah_id', $surahId)
            ->where('verse_number', self::VERSE)
            ->value('id');

        if (!$verseId) {
            $verseId = DB::table('verses')->insertGetId([
                'surah_id' => $surahId,
                'verse_number' => self::VERSE,
                'text_arabic' => self::ARABIC,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $hasTranslation = DB::table('translations')
            ->where('verse_id', $verseId)
            ->where('language', 'en')
            ->exists();

        if (!$hasTranslation) {
            DB::table('translations')->insert([
                'verse_id' => $verseId,
                'language' => 'en',
                'translator' => 'Ayat al-Kursi',
                'text' => self::TRANSLATION,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @return array<int, array{n: int, text: string, pair: int|null, center: bool}>
     */
    public static function parts(): array
    {
        return [
            ['n' => 1, 'pair' => 9, 'center' => false, 'text' => 'Allah — there is no god except Him, the Ever-Living, the Self-Sustaining.'],
            ['n' => 2, 'pair' => 8, 'center' => false, 'text' => 'Neither drowsiness nor sleep overtakes Him.'],
            ['n' => 3, 'pair' => 7, 'center' => false, 'text' => 'To Him belongs whatever is in the heavens and the earth.'],
            ['n' => 4, 'pair' => 6, 'center' => false, 'text' => 'Who can intercede with Him except by His permission?'],
            ['n' => 5, 'pair' => null, 'center' => true, 'text' => 'He knows what is before them and what will be after them.'],
            ['n' => 6, 'pair' => 4, 'center' => false, 'text' => 'They encompass nothing of His knowledge except what He wills.'],
            ['n' => 7, 'pair' => 3, 'center' => false, 'text' => 'His Throne extends over the heavens and the earth.'],
            ['n' => 8, 'pair' => 2, 'center' => false, 'text' => 'Their preservation does not tire Him.'],
            ['n' => 9, 'pair' => 1, 'center' => false, 'text' => 'And He is the Most High, the Most Great.'],
        ];
    }
}

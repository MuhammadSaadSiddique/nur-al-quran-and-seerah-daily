<?php

namespace App\Services;

use App\Exceptions\PatternGenerationLimited;
use App\Models\QuranPatternCard;
use App\Support\AyatAlKursiRing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class QuranPatternService
{
    public const DISCLAIMER = 'Counts come from the stored text. Notes are study observations.';

    public const TOPICS = ['Biology', 'Cosmology', 'Physics', 'Archeology', 'Climatology'];

    public const NO_RING = 'No clear ring is established for this passage from the text provided.';

    public function __construct(private GeminiService $gemini) {}

    /**
     * @return array{hits: array<int, array<string, mixed>>, suggested: bool, direct: bool, message: string}
     */
    public function search(string $query): array
    {
        $query = trim($query);
        if ($query === '' || mb_strlen($query) > 120) {
            return $this->emptySearch($query === '' ? '' : 'Enter a shorter search.');
        }

        if (AyatAlKursiRing::matchesQuery($query)) {
            AyatAlKursiRing::ensureStored();
            $hit = $this->hitForChapterVerse(AyatAlKursiRing::CHAPTER, AyatAlKursiRing::VERSE);

            return [
                'hits' => $hit ? [$hit] : [],
                'suggested' => false,
                'direct' => $hit !== null,
                'message' => $hit ? '' : 'That verse was not found.',
            ];
        }

        if (preg_match('/^(\d{1,3})\s*[:.]\s*(\d{1,3})$/', $query, $matches)) {
            if ((int) $matches[1] === AyatAlKursiRing::CHAPTER && (int) $matches[2] === AyatAlKursiRing::VERSE) {
                AyatAlKursiRing::ensureStored();
            }
            $hit = $this->hitForChapterVerse((int) $matches[1], (int) $matches[2]);
            if ($hit === null) {
                return $this->emptySearch('That verse was not found.');
            }

            return [
                'hits' => [$hit],
                'suggested' => false,
                'direct' => true,
                'message' => '',
            ];
        }

        $hits = $this->literalHits($query);
        if ($hits !== []) {
            return [
                'hits' => $hits,
                'suggested' => false,
                'direct' => count($hits) === 1,
                'message' => '',
            ];
        }

        $suggested = $this->suggestedHits($query);
        if ($suggested === []) {
            return $this->emptySearch('No verse matched that search.');
        }

        return [
            'hits' => $suggested,
            'suggested' => true,
            'direct' => count($suggested) === 1,
            'message' => '',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function cardFor(int $chapter, int $verse, ?string $focus = null): ?array
    {
        if ($chapter < 1 || $chapter > 114 || $verse < 1) {
            return null;
        }

        if ($chapter === AyatAlKursiRing::CHAPTER && $verse === AyatAlKursiRing::VERSE) {
            AyatAlKursiRing::ensureStored();
        }

        $row = $this->verseRow($chapter, $verse);
        if ($row === null) {
            return null;
        }

        $arabic = (string) ($row->text_arabic ?? '');
        $focusToken = $this->focusToken($arabic, $focus);
        if ((int) $row->chapter === AyatAlKursiRing::CHAPTER && (int) $row->verse_number === AyatAlKursiRing::VERSE) {
            return $this->ayatAlKursiCard($row, $focusToken);
        }

        $cached = QuranPatternCard::query()
            ->where('verse_id', $row->verse_id)
            ->where('focus_token', $focusToken)
            ->first();

        if ($cached) {
            return $this->present($row, $cached->gloss, $cached->arabic_term, $cached->ring_note, $cached->linguistic_note, $cached->topics ?? [], $cached->math ?? []);
        }

        $math = $this->mathFor($row, $focusToken);
        $notes = $this->generateNotes($row, $focus, $math);
        $arabicTerm = $this->termInVerse($arabic, (string) ($notes['arabic_term'] ?? ''));
        $gloss = trim((string) ($notes['gloss'] ?? ''));
        if ($gloss === '') {
            $gloss = $this->openingGloss((string) ($row->translation ?? ''));
        }
        $ring = trim((string) ($notes['ring_note'] ?? ''));
        if ($ring === '') {
            $ring = self::NO_RING;
        }
        $linguistic = trim((string) ($notes['linguistic_note'] ?? ''));
        if ($arabicTerm !== '' && !str_contains($linguistic, $arabicTerm)) {
            $linguistic = trim($linguistic.' '.$arabicTerm);
        }
        $topics = $this->topics(is_array($notes['topics'] ?? null) ? $notes['topics'] : []);

        QuranPatternCard::query()->updateOrCreate(
            ['verse_id' => $row->verse_id, 'focus_token' => $focusToken],
            [
                'gloss' => $gloss,
                'arabic_term' => $arabicTerm,
                'ring_note' => $ring,
                'linguistic_note' => $linguistic,
                'topics' => $topics,
                'math' => $math,
            ]
        );

        return $this->present($row, $gloss, $arabicTerm, $ring, $linguistic, $topics, $math);
    }

    /**
     * @param  array<int, string>  $surahArabic
     * @return array<string, int|string|null>
     */
    public function countsFor(string $arabic, int $ayatInSurah, ?string $focusArabic, array $surahArabic): array
    {
        $words = $this->words($arabic);
        $position = null;
        $focusCount = null;
        $normFocus = $focusArabic ? $this->normalizeArabic($focusArabic) : '';
        if ($normFocus !== '') {
            $focusCount = 0;
            foreach ($words as $index => $word) {
                if ($this->normalizeArabic($word) === $normFocus) {
                    $position = $position ?? ($index + 1);
                }
            }
            foreach ($surahArabic as $verseArabic) {
                foreach ($this->words($verseArabic) as $word) {
                    if ($this->normalizeArabic($word) === $normFocus) {
                        $focusCount++;
                    }
                }
            }
        }

        $math = [
            'ayat_in_surah' => $ayatInSurah,
            'words_in_verse' => count($words),
            'letters_in_verse' => $this->letterCount($arabic),
            'focus_position' => $position,
            'focus_count_in_surah' => $focusCount,
        ];
        $math['summary'] = $this->summary($math);

        return $math;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptySearch(string $message): array
    {
        return [
            'hits' => [],
            'suggested' => false,
            'direct' => false,
            'message' => $message,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function literalHits(string $query): array
    {
        $like = '%'.addcslashes($query, '%_\\').'%';
        $builder = DB::table('verses')
            ->join('surahs', 'verses.surah_id', '=', 'surahs.id')
            ->select(
                'verses.id as verse_id',
                'verses.verse_number',
                'verses.text_arabic',
                'surahs.number as chapter',
                'surahs.name_transliteration as surah_name',
                'surahs.verse_count'
            )
            ->where(function ($q) use ($like) {
                $q->where('verses.text_arabic', 'like', $like)
                    ->orWhere('verses.text_transliteration', 'like', $like)
                    ->orWhere('surahs.name_transliteration', 'like', $like)
                    ->orWhere('surahs.name_english', 'like', $like)
                    ->orWhereExists(function ($sub) use ($like) {
                        $sub->select(DB::raw('1'))
                            ->from('translations')
                            ->whereColumn('translations.verse_id', 'verses.id')
                            ->where('translations.language', 'en')
                            ->where('translations.text', 'like', $like);
                    });
            })
            ->orderBy('surahs.number')
            ->orderBy('verses.verse_number')
            ->limit(8);

        return $this->mapHits($builder->get());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function suggestedHits(string $query): array
    {
        $cacheKey = 'quran_pattern_search:'.md5(mb_strtolower($query));
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $this->acquireGenerationSlot();
        $keys = $this->gemini->suggestPatternVerses(mb_substr($query, 0, 200));
        $hits = [];
        foreach ($keys as $key) {
            if (!preg_match('/(\d{1,3})\s*:\s*(\d{1,3})/', (string) $key, $matches)) {
                continue;
            }
            $hit = $this->hitForChapterVerse((int) $matches[1], (int) $matches[2]);
            if ($hit === null) {
                continue;
            }
            $id = $hit['chapter'].':'.$hit['verse'];
            $hits[$id] = $hit;
            if (count($hits) >= 3) {
                break;
            }
        }

        $hits = array_values($hits);
        Cache::put($cacheKey, $hits, now()->addDay());

        return $hits;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function hitForChapterVerse(int $chapter, int $verse): ?array
    {
        $row = $this->verseRow($chapter, $verse);
        if ($row === null) {
            return null;
        }

        return $this->hitFromRow($row);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function mapHits($rows): array
    {
        $hits = [];
        foreach ($rows as $row) {
            $row->translation = $this->translationFor((int) $row->verse_id);
            $hits[] = $this->hitFromRow($row);
        }

        return $hits;
    }

    /**
     * @return array<string, mixed>
     */
    private function hitFromRow(object $row): array
    {
        $chapter = (int) $row->chapter;
        $verse = (int) $row->verse_number;

        return [
            'chapter' => $chapter,
            'verse' => $verse,
            'verse_key' => $chapter.':'.$verse,
            'surah_name' => (string) ($row->surah_name ?? ''),
            'arabic' => (string) ($row->text_arabic ?? ''),
            'translation' => (string) ($row->translation ?? ''),
        ];
    }

    private function verseRow(int $chapter, int $verse): ?object
    {
        $row = DB::table('verses')
            ->join('surahs', 'verses.surah_id', '=', 'surahs.id')
            ->where('surahs.number', $chapter)
            ->where('verses.verse_number', $verse)
            ->select(
                'verses.id as verse_id',
                'verses.surah_id',
                'verses.verse_number',
                'verses.text_arabic',
                'surahs.number as chapter',
                'surahs.name_transliteration as surah_name',
                'surahs.verse_count'
            )
            ->first();

        if (!$row) {
            return null;
        }

        $row->translation = $this->translationFor((int) $row->verse_id);

        return $row;
    }

    private function translationFor(int $verseId): string
    {
        $text = DB::table('translations')
            ->where('verse_id', $verseId)
            ->where('language', 'en')
            ->value('text');

        return trim(strip_tags((string) $text));
    }

    private function focusToken(string $arabic, ?string $focus): string
    {
        $focus = trim((string) $focus);
        if ($focus === '' || preg_match('/^\d{1,3}\s*[:.]\s*\d{1,3}$/', $focus)) {
            return '';
        }

        $norm = $this->normalizeArabic($focus);
        foreach ($this->words($arabic) as $word) {
            if ($norm !== '' && $this->normalizeArabic($word) === $norm) {
                return $this->normalizeArabic($word);
            }
        }

        return mb_strtolower(mb_substr($focus, 0, 80));
    }

    /**
     * @return array<string, int|string|null>
     */
    private function mathFor(object $row, string $focusToken): array
    {
        $surahVerses = DB::table('verses')
            ->where('surah_id', $row->surah_id)
            ->orderBy('verse_number')
            ->pluck('text_arabic')
            ->map(fn ($text) => (string) $text)
            ->all();

        $focusArabic = null;
        if ($focusToken !== '') {
            foreach ($this->words((string) $row->text_arabic) as $word) {
                if ($this->normalizeArabic($word) === $focusToken) {
                    $focusArabic = $word;
                    break;
                }
            }
        }

        return $this->countsFor(
            (string) $row->text_arabic,
            (int) ($row->verse_count ?? 0),
            $focusArabic,
            $surahVerses
        );
    }

    /**
     * @param  array<string, int|string|null>  $math
     * @return array<string, mixed>
     */
    private function generateNotes(object $row, ?string $focus, array $math): array
    {
        $this->acquireGenerationSlot();
        $notes = $this->gemini->generatePatternNotes([
            'surah_name' => (string) ($row->surah_name ?? ''),
            'verse_key' => $row->chapter.':'.$row->verse_number,
            'focus' => trim((string) $focus),
            'arabic' => (string) $row->text_arabic,
            'translation' => (string) ($row->translation ?? ''),
            'passage' => $this->passage($row),
            'math_summary' => $math['summary'] ?? '',
        ]);

        return is_array($notes) ? $notes : [];
    }

    private function passage(object $row): string
    {
        $verseCount = (int) ($row->verse_count ?? 0);
        $current = (int) $row->verse_number;
        $query = DB::table('verses')->where('surah_id', $row->surah_id);
        if ($verseCount > 20) {
            $query->whereBetween('verse_number', [max(1, $current - 3), $current + 3]);
        }
        $verses = $query->orderBy('verse_number')->get(['id', 'verse_number', 'text_arabic']);
        $ids = $verses->pluck('id')->all();
        $translations = [];
        if ($ids !== []) {
            $translations = DB::table('translations')
                ->whereIn('verse_id', $ids)
                ->where('language', 'en')
                ->pluck('text', 'verse_id')
                ->all();
        }

        $lines = [];
        foreach ($verses as $verse) {
            $english = trim(strip_tags((string) ($translations[$verse->id] ?? '')));
            $lines[] = $verse->verse_number.'. '.$verse->text_arabic.($english !== '' ? "\n".$english : '');
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    private function ayatAlKursiCard(object $row, string $focusToken): array
    {
        $math = $this->mathFor($row, $focusToken);
        $gloss = 'Ayat al-Kursi, the Verse of the Throne';
        $arabicTerm = 'كرسيه';
        $ring = 'Nine phrases fold around the center: He knows what is before them and what will be after them.';
        $linguistic = 'كرسيه names His Throne, which extends over the heavens and the earth.';

        QuranPatternCard::query()->updateOrCreate(
            ['verse_id' => $row->verse_id, 'focus_token' => $focusToken],
            [
                'gloss' => $gloss,
                'arabic_term' => $arabicTerm,
                'ring_note' => $ring,
                'linguistic_note' => $linguistic,
                'topics' => [],
                'math' => $math,
            ]
        );

        return $this->present($row, $gloss, $arabicTerm, $ring, $linguistic, [], $math);
    }

    /**
     * @param  array<string, mixed>  $math
     * @param  array<int, string>  $topics
     * @return array<string, mixed>
     */
    private function present(object $row, ?string $gloss, ?string $arabicTerm, ?string $ring, ?string $linguistic, array $topics, array $math): array
    {
        $chapter = (int) $row->chapter;
        $verse = (int) $row->verse_number;

        return [
            'chapter' => $chapter,
            'verse' => $verse,
            'verse_key' => $chapter.':'.$verse,
            'surah_name' => (string) ($row->surah_name ?? ''),
            'arabic_text' => (string) ($row->text_arabic ?? ''),
            'translation' => (string) ($row->translation ?? ''),
            'gloss' => (string) $gloss,
            'arabic_term' => (string) $arabicTerm,
            'ring_note' => (string) $ring,
            'linguistic_note' => (string) $linguistic,
            'topics' => array_values($topics),
            'math' => $math,
            'ring_parts' => ((int) $row->chapter === AyatAlKursiRing::CHAPTER && (int) $row->verse_number === AyatAlKursiRing::VERSE)
                ? AyatAlKursiRing::parts()
                : [],
            'resources' => [
                'verse_url' => route('lens.verse', [$chapter, $verse]),
                'science' => $this->scienceLinks((int) $row->verse_id),
            ],
            'disclaimer' => self::DISCLAIMER,
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function scienceLinks(int $verseId): array
    {
        try {
            $rows = DB::table('quran_science_links')
                ->join('science_facts', 'quran_science_links.science_fact_id', '=', 'science_facts.id')
                ->where('quran_science_links.verse_id', $verseId)
                ->where('quran_science_links.status', 'approved')
                ->select('science_facts.title', 'science_facts.description', 'science_facts.field')
                ->limit(5)
                ->get();
        } catch (\Throwable $e) {
            return [];
        }

        return $rows->map(function ($row) {
            return [
                'title' => (string) ($row->title ?? ''),
                'description' => (string) ($row->description ?? ''),
                'field' => (string) ($row->field ?? ''),
            ];
        })->all();
    }

    /**
     * @param  array<int, mixed>  $raw
     * @return array<int, string>
     */
    private function topics(array $raw): array
    {
        $map = [];
        foreach (self::TOPICS as $name) {
            $map[mb_strtolower($name)] = $name;
        }

        $topics = [];
        foreach ($raw as $topic) {
            $key = mb_strtolower(trim((string) $topic));
            if (!isset($map[$key]) || in_array($map[$key], $topics, true)) {
                continue;
            }
            $topics[] = $map[$key];
            if (count($topics) === 2) {
                break;
            }
        }

        return $topics;
    }

    private function termInVerse(string $arabic, string $candidate): string
    {
        $words = $this->words($arabic);
        $norm = $this->normalizeArabic($candidate);
        foreach ($words as $word) {
            if ($norm !== '' && $this->normalizeArabic($word) === $norm) {
                return $word;
            }
        }
        foreach ($words as $word) {
            $wordNorm = $this->normalizeArabic($word);
            if ($norm !== '' && mb_strlen($norm) >= 2 && (str_contains($wordNorm, $norm) || str_contains($norm, $wordNorm))) {
                return $word;
            }
        }
        if ($norm !== '' && str_contains($this->normalizeArabic($arabic), $norm)) {
            return $candidate;
        }

        return $words[0] ?? $arabic;
    }

    private function openingGloss(string $translation): string
    {
        $translation = trim($translation);
        if ($translation === '') {
            return 'This verse';
        }
        $cut = preg_split('/[.!?]/u', $translation) ?: [];
        $opening = trim((string) ($cut[0] ?? $translation));

        return mb_substr($opening, 0, 80);
    }

    /**
     * @param  array<string, int|string|null>  $math
     */
    private function summary(array $math): string
    {
        $summary = $math['ayat_in_surah'].' ayat, '.$math['words_in_verse'].' words';
        $position = $math['focus_position'] ?? null;
        if (is_int($position) && $position > 0) {
            $summary .= ', focus word is '.$this->ordinal($position);
        }

        return $summary;
    }

    private function ordinal(int $position): string
    {
        return match ($position) {
            1 => 'first',
            2 => 'second',
            3 => 'third',
            default => 'word '.$position,
        };
    }

    /**
     * @return array<int, string>
     */
    private function words(string $arabic): array
    {
        $parts = preg_split('/\s+/u', trim($arabic), -1, PREG_SPLIT_NO_EMPTY);

        return $parts ?: [];
    }

    private function letterCount(string $arabic): int
    {
        $stripped = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u', '', $arabic) ?? '';
        preg_match_all('/\p{Arabic}/u', $stripped, $matches);

        return count($matches[0]);
    }

    private function normalizeArabic(string $text): string
    {
        $text = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u', '', $text) ?? '';
        $text = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $text);
        $text = str_replace('ى', 'ي', $text);

        return str_replace('ة', 'ه', $text);
    }

    private function acquireGenerationSlot(): void
    {
        $key = 'quran-patterns:'.(request()->ip() ?: 'cli');
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw new PatternGenerationLimited('Too many pattern notes. Please try again in a minute.');
        }
        RateLimiter::hit($key, 60);
    }
}

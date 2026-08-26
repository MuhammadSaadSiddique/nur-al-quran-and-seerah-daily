<?php

namespace App\Http\Controllers;

use App\Models\QuranicLensAnalysis;
use App\Models\QuranicLensWordTag;
use App\Models\QuranicLensVerseTag;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QuranicLensController extends Controller
{
    /**
     * Display a listing of all 114 Surahs.
     */
    /**
     * Display a listing of all 114 Surahs and connection-filtered Ayahs.
     */
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'surahs');
        $search = $request->input('search');

        try {
            $chapters = \Illuminate\Support\Facades\DB::table('surahs')
                ->orderBy('number')
                ->get()
                ->map(function ($s) {
                    return [
                        'id' => $s->number,
                        'name_simple' => $s->name_transliteration,
                        'name_arabic' => $s->name_arabic,
                        'verses_count' => $s->verse_count,
                        'revelation_place' => strtolower($s->revelation_type) === 'meccan' ? 'makkah' : 'madinah',
                        'translated_name' => [
                            'name' => $s->name_english
                        ]
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Local surahs fetch failed, using fallback', ['error' => $e->getMessage()]);
            $chapters = $this->getStaticChaptersFallback();
        }

        $paginatedData = null;
        $searchResults = null;

        if ($request->filled('search')) {
            try {
                $query = \Illuminate\Support\Facades\DB::table('verses')
                    ->join('surahs', 'verses.surah_id', '=', 'surahs.id')
                    ->join('translations', 'verses.id', '=', 'translations.verse_id')
                    ->where('translations.language', 'en');

                // Check if it's a direct verse lookup like "2:255" or "18:10"
                if (preg_match('/^(\d+):(\d+)$/', trim($search), $matches)) {
                    $query->where('surahs.number', $matches[1])
                        ->where('verses.verse_number', $matches[2]);
                } else {
                    $query->where(function ($q) use ($search) {
                        $q->where('verses.text_arabic', 'LIKE', '%' . $search . '%')
                            ->orWhere('verses.text_transliteration', 'LIKE', '%' . $search . '%')
                            ->orWhere('translations.text', 'LIKE', '%' . $search . '%')
                            ->orWhere('surahs.name_transliteration', 'LIKE', '%' . $search . '%');
                    });
                }

                $paginated = $query->select(
                    'verses.*',
                    'surahs.name_transliteration as surah_name',
                    'surahs.number as surah_number',
                    'translations.text as translation_text'
                )
                    ->paginate(15)
                    ->withQueryString();

                $items = collect($paginated->items())->map(function ($item) {
                    $item->translation = strip_tags($item->translation_text);

                    // Fetch relational mapping indicators
                    $item->has_hadith = \Illuminate\Support\Facades\DB::table('quran_hadith_links')->where('verse_id', $item->id)->where('status', 'approved')->exists();
                    $item->has_seerat = \Illuminate\Support\Facades\DB::table('quran_seerat_links')->where('verse_id', $item->id)->where('status', 'approved')->exists();
                    $item->has_science = \Illuminate\Support\Facades\DB::table('quran_science_links')->where('verse_id', $item->id)->where('status', 'approved')->exists();
                    $item->has_history = \Illuminate\Support\Facades\DB::table('quran_history_links')->where('verse_id', $item->id)->where('status', 'approved')->exists();
                    $item->has_bible = \Illuminate\Support\Facades\DB::table('quran_scripture_links')->where('verse_id', $item->id)->whereNotNull('bible_verse_id')->where('status', 'approved')->exists();
                    $item->has_torah = \Illuminate\Support\Facades\DB::table('quran_scripture_links')->where('verse_id', $item->id)->whereNotNull('torah_section_id')->where('status', 'approved')->exists();

                    return $item;
                });

                $searchResults = new \Illuminate\Pagination\LengthAwarePaginator(
                    $items,
                    $paginated->total(),
                    $paginated->perPage(),
                    $paginated->currentPage(),
                    ['path' => $request->url(), 'query' => $request->query()]
                );
            } catch (\Exception $e) {
                Log::error("Failed to execute global search for '{$search}'", ['error' => $e->getMessage()]);
                $searchResults = new \Illuminate\Pagination\LengthAwarePaginator(
                    collect(),
                    0,
                    15,
                    1,
                    ['path' => $request->url(), 'query' => $request->query()]
                );
            }
        } elseif ($tab !== 'surahs' && !in_array($tab, ['biology', 'maths'])) {
            try {
                $query = \Illuminate\Support\Facades\DB::table('verses')
                    ->join('surahs', 'verses.surah_id', '=', 'surahs.id');

                if ($tab === 'science') {
                    $query->join('quran_science_links', 'verses.id', '=', 'quran_science_links.verse_id')
                        ->join('science_facts', 'quran_science_links.science_fact_id', '=', 'science_facts.id')
                        ->where('quran_science_links.status', 'approved');

                    $scienceCategories = $this->getScienceCategories();
                    if ($request->filled('science_field') && isset($scienceCategories[$request->science_field])) {
                        $query->whereIn('science_facts.field', $scienceCategories[$request->science_field]['fields']);
                    }

                    $query->select(
                        'verses.*',
                        'surahs.name_transliteration as surah_name',
                        'surahs.number as surah_number',
                        'science_facts.title as link_title',
                        'science_facts.description as link_content',
                        'science_facts.field as link_extra'
                    );
                } elseif ($tab === 'seerat') {
                    $query->join('quran_seerat_links', 'verses.id', '=', 'quran_seerat_links.verse_id')
                        ->join('seerat_events', 'quran_seerat_links.seerat_event_id', '=', 'seerat_events.id')
                        ->where('quran_seerat_links.status', 'approved')
                        ->select(
                            'verses.*',
                            'surahs.name_transliteration as surah_name',
                            'surahs.number as surah_number',
                            'seerat_events.title as link_title',
                            'seerat_events.description as link_content',
                            'seerat_events.category as link_extra'
                        );
                } elseif ($tab === 'hadith') {
                    $query->join('quran_hadith_links', 'verses.id', '=', 'quran_hadith_links.verse_id')
                        ->join('ahadith', 'quran_hadith_links.hadith_id', '=', 'ahadith.id')
                        ->leftJoin('hadith_collections', 'ahadith.collection_id', '=', 'hadith_collections.id')
                        ->where('quran_hadith_links.status', 'approved')
                        ->select(
                            'verses.*',
                            'surahs.name_transliteration as surah_name',
                            'surahs.number as surah_number',
                            'ahadith.hadith_number as link_title',
                            'ahadith.text_english as link_content',
                            'hadith_collections.name_english as link_extra'
                        );
                } elseif ($tab === 'history') {
                    $query->join('quran_history_links', 'verses.id', '=', 'quran_history_links.verse_id')
                        ->join('historical_events', 'quran_history_links.historical_event_id', '=', 'historical_events.id')
                        ->where('quran_history_links.status', 'approved')
                        ->select(
                            'verses.*',
                            'surahs.name_transliteration as surah_name',
                            'surahs.number as surah_number',
                            'historical_events.title as link_title',
                            'historical_events.description as link_content',
                            'historical_events.civilization as link_extra'
                        );
                } elseif ($tab === 'bible') {
                    $query->join('quran_scripture_links', 'verses.id', '=', 'quran_scripture_links.verse_id')
                        ->join('bible_verses', 'quran_scripture_links.bible_verse_id', '=', 'bible_verses.id')
                        ->where('quran_scripture_links.status', 'approved')
                        ->select(
                            'verses.*',
                            'surahs.name_transliteration as surah_name',
                            'surahs.number as surah_number',
                            \Illuminate\Support\Facades\DB::raw("CONCAT(bible_verses.book, ' ', bible_verses.chapter, ':', bible_verses.verse_number) as link_title"),
                            'bible_verses.text_niv as link_content',
                            'bible_verses.testament as link_extra'
                        );
                } elseif ($tab === 'torah') {
                    $query->join('quran_scripture_links', 'verses.id', '=', 'quran_scripture_links.verse_id')
                        ->join('torah_sections', 'quran_scripture_links.torah_section_id', '=', 'torah_sections.id')
                        ->where('quran_scripture_links.status', 'approved')
                        ->select(
                            'verses.*',
                            'surahs.name_transliteration as surah_name',
                            'surahs.number as surah_number',
                            \Illuminate\Support\Facades\DB::raw("CONCAT(torah_sections.book, ' ', torah_sections.chapter, ':', torah_sections.verse_number) as link_title"),
                            'torah_sections.text_english as link_content',
                            \Illuminate\Support\Facades\DB::raw("NULL as link_extra")
                        );
                }

                $paginated = $query->paginate(15)->withQueryString();

                $items = collect($paginated->items())->map(function ($item) {
                    $trans = \Illuminate\Support\Facades\DB::table('translations')
                        ->where('verse_id', $item->id)
                        ->where('language', 'en')
                        ->first();
                    $item->translation = $trans ? strip_tags($trans->text) : '';
                    return $item;
                });

                $paginatedData = new \Illuminate\Pagination\LengthAwarePaginator(
                    $items,
                    $paginated->total(),
                    $paginated->perPage(),
                    $paginated->currentPage(),
                    ['path' => $request->url(), 'query' => $request->query()]
                );
            } catch (\Exception $e) {
                Log::error("Failed to query tab {$tab} in QuranicLensController", ['error' => $e->getMessage()]);
                $paginatedData = new \Illuminate\Pagination\LengthAwarePaginator(
                    collect(),
                    0,
                    15,
                    1,
                    ['path' => $request->url(), 'query' => $request->query()]
                );
            }
        }

        $scienceCategories = $this->getScienceCategories();

        return view('quranic-lens.index', compact('chapters', 'tab', 'paginatedData', 'searchResults', 'search', 'scienceCategories'));
    }

    /**
     * Display verses list of a specific Surah.
     */
    public function surah($chapter)
    {
        $chapter = (int) $chapter;
        if ($chapter < 1 || $chapter > 114) {
            return redirect()->route('lens.index')->with('error', 'Invalid Surah number.');
        }

        // Fetch local Surah
        try {
            $localSurah = \Illuminate\Support\Facades\DB::table('surahs')->where('number', $chapter)->first();
        } catch (\Illuminate\Database\QueryException $e) {
            $localSurah = null;
        }

        if (!$localSurah) {
            return redirect()->route('lens.index')->with('error', 'Could not load Surah information from local database.');
        }

        $surahInfo = [
            'id' => $localSurah->number,
            'name_simple' => $localSurah->name_transliteration,
            'name_arabic' => $localSurah->name_arabic,
            'verses_count' => $localSurah->verse_count,
            'revelation_place' => strtolower($localSurah->revelation_type) === 'meccan' ? 'makkah' : 'madinah',
            'translated_name' => [
                'name' => $localSurah->name_english
            ]
        ];

        // Fetch Verses locally
        try {
            $rawVerses = \Illuminate\Support\Facades\DB::table('verses')
                ->where('surah_id', $localSurah->id)
                ->orderBy('verse_number')
                ->get();

            $verseIds = $rawVerses->pluck('id')->toArray();

            $hasHadith = \Illuminate\Support\Facades\DB::table('quran_hadith_links')
                ->whereIn('verse_id', $verseIds)
                ->where('status', 'approved')
                ->pluck('verse_id')
                ->unique()
                ->toArray();

            $hasSeerat = \Illuminate\Support\Facades\DB::table('quran_seerat_links')
                ->whereIn('verse_id', $verseIds)
                ->where('status', 'approved')
                ->pluck('verse_id')
                ->unique()
                ->toArray();

            $hasScience = \Illuminate\Support\Facades\DB::table('quran_science_links')
                ->whereIn('verse_id', $verseIds)
                ->where('status', 'approved')
                ->pluck('verse_id')
                ->unique()
                ->toArray();

            $hasHistory = \Illuminate\Support\Facades\DB::table('quran_history_links')
                ->whereIn('verse_id', $verseIds)
                ->where('status', 'approved')
                ->pluck('verse_id')
                ->unique()
                ->toArray();

            $hasBible = \Illuminate\Support\Facades\DB::table('quran_scripture_links')
                ->whereIn('verse_id', $verseIds)
                ->whereNotNull('bible_verse_id')
                ->where('status', 'approved')
                ->pluck('verse_id')
                ->unique()
                ->toArray();

            $hasTorah = \Illuminate\Support\Facades\DB::table('quran_scripture_links')
                ->whereIn('verse_id', $verseIds)
                ->whereNotNull('torah_section_id')
                ->where('status', 'approved')
                ->pluck('verse_id')
                ->unique()
                ->toArray();

            $verses = $rawVerses->map(function ($v) use ($hasHadith, $hasSeerat, $hasScience, $hasHistory, $hasBible, $hasTorah) {
                $translation = \Illuminate\Support\Facades\DB::table('translations')
                    ->where('verse_id', $v->id)
                    ->where('language', 'en')
                    ->first();

                return [
                    'id' => $v->id,
                    'verse_number' => $v->verse_number,
                    'verse_key' => $v->surah_id . ':' . $v->verse_number,
                    'text_uthmani' => $v->text_arabic,
                    'translation' => $translation ? strip_tags($translation->text) : '',
                    'juz' => $v->juz,
                    'has_hadith' => in_array($v->id, $hasHadith),
                    'has_seerat' => in_array($v->id, $hasSeerat),
                    'has_science' => in_array($v->id, $hasScience),
                    'has_history' => in_array($v->id, $hasHistory),
                    'has_bible' => in_array($v->id, $hasBible),
                    'has_torah' => in_array($v->id, $hasTorah),
                ];
            })->toArray();
        } catch (\Illuminate\Database\QueryException $e) {
            $verses = [];
        }

        return view('quranic-lens.surah', compact('surahInfo', 'verses'));
    }

    /**
     * Deep analysis interface of a specific verse.
     */
    public function verse($chapter, $verse)
    {
        $chapter = (int) $chapter;
        $verse = (int) $verse;

        if ($chapter < 1 || $chapter > 114 || $verse < 1) {
            return redirect()->route('lens.index')->with('error', 'Invalid parameters.');
        }

        // Fetch local Surah
        try {
            $localSurah = \Illuminate\Support\Facades\DB::table('surahs')->where('number', $chapter)->first();
        } catch (\Illuminate\Database\QueryException $e) {
            $localSurah = null;
        }

        if (!$localSurah) {
            return redirect()->route('lens.index')->with('error', 'Could not load Surah information.');
        }

        $surahInfo = [
            'id' => $localSurah->number,
            'name_simple' => $localSurah->name_transliteration,
            'name_arabic' => $localSurah->name_arabic,
            'verses_count' => $localSurah->verse_count,
            'revelation_place' => strtolower($localSurah->revelation_type) === 'meccan' ? 'makkah' : 'madinah',
            'translated_name' => [
                'name' => $localSurah->name_english
            ]
        ];

        if ($verse > $localSurah->verse_count) {
            return redirect()->route('lens.surah', $chapter)->with('error', 'Invalid verse number for this Surah.');
        }

        // Fetch local Verse
        try {
            $localVerse = \Illuminate\Support\Facades\DB::table('verses')
                ->where('surah_id', $localSurah->id)
                ->where('verse_number', $verse)
                ->first();
        } catch (\Illuminate\Database\QueryException $e) {
            $localVerse = null;
        }

        if (!$localVerse) {
            return redirect()->route('lens.surah', $chapter)->with('error', 'Could not load verse details.');
        }

        // Get translations
        try {
            $localTranslations = \Illuminate\Support\Facades\DB::table('translations')
                ->where('verse_id', $localVerse->id)
                // ->where('language', 'en')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            $localTranslations = collect();
        }

        $translationsList = [];
        foreach ($localTranslations as $trans) {
            $translationsList[] = [
                'author_name' => $trans->translator ?: 'Unknown Translator',
                'text' => $trans->text
            ];
        }

        // Default fallback if no translations found
        if (empty($translationsList)) {
            $translationsList[] = [
                'author_name' => 'Default Translator',
                'text' => ''
            ];
        }

        // Build word list by splitting Arabic text and transliteration
        $arabicWords = preg_split('/\s+/u', trim($localVerse->text_arabic));
        $transliterationWords = preg_split('/\s+/u', trim($localVerse->text_transliteration ?? ''));

        $words = [];
        foreach ($arabicWords as $index => $arWord) {
            //  ۚ  ۖ 
            $words[] = [
                'position' => $index + 1,
                'text_uthmani' => $arWord,
                'transliteration' => [
                    'text' => ''
                ],
                'translation' => [
                    'text' => ''
                ]
            ];
        }

        $verseDetail = [
            'text_uthmani' => $localVerse->text_arabic,
            'translations' => $translationsList,
            'words' => $words
        ];

        // Load Approved Analyses, Word Tags, and Verse Tags
        $analyses = QuranicLensAnalysis::where('chapter_number', $chapter)
            ->where('verse_number', $verse)
            ->approved()
            ->with(['user', 'theme', 'moderator'])
            ->get();

        $wordTags = QuranicLensWordTag::where('chapter_number', $chapter)
            ->where('verse_number', $verse)
            ->approved()
            ->with('user')
            ->get()
            ->groupBy('word_position');

        $verseTags = QuranicLensVerseTag::where('chapter_number', $chapter)
            ->where('verse_number', $verse)
            ->approved()
            ->with('user')
            ->get();

        // Query pre-populated local database tables for this verse (Para, Tafsir, Seerah, Hadith, History, Science, Scripture)
        $juz = $localVerse->juz;
        $localTafsir = collect();
        $localSeerat = collect();
        $localHadith = collect();
        $localHistory = collect();
        $localScience = collect();
        $localBible = collect();
        $localTorah = collect();

        try {
            // Tafsir data
            $localTafsir = \Illuminate\Support\Facades\DB::table('tafsirs')
                ->where('verse_id', $localVerse->id)
                ->get();

            // Seerat events
            $localSeerat = \Illuminate\Support\Facades\DB::table('seerat_events')
                ->join('quran_seerat_links', 'seerat_events.id', '=', 'quran_seerat_links.seerat_event_id')
                ->where('quran_seerat_links.verse_id', $localVerse->id)
                ->where('quran_seerat_links.status', 'approved')
                ->select('seerat_events.*', 'quran_seerat_links.description as link_description', 'quran_seerat_links.context_type')
                ->get();

            // Hadith items
            $localHadith = \Illuminate\Support\Facades\DB::table('ahadith')
                ->join('quran_hadith_links', 'ahadith.id', '=', 'quran_hadith_links.hadith_id')
                ->leftJoin('hadith_collections', 'ahadith.collection_id', '=', 'hadith_collections.id')
                ->leftJoin('hadith_books', 'ahadith.book_id', '=', 'hadith_books.id')
                ->where('quran_hadith_links.verse_id', $localVerse->id)
                ->where('quran_hadith_links.status', 'approved')
                ->select('ahadith.*', 'quran_hadith_links.description as link_description', 'quran_hadith_links.relevance_type', 'hadith_collections.name_english as collection_name', 'hadith_books.name_english as book_name')
                ->get();

            // Historical events
            $localHistory = \Illuminate\Support\Facades\DB::table('historical_events')
                ->join('quran_history_links', 'historical_events.id', '=', 'quran_history_links.historical_event_id')
                ->where('quran_history_links.verse_id', $localVerse->id)
                ->where('quran_history_links.status', 'approved')
                ->select('historical_events.*', 'quran_history_links.description as link_description')
                ->get();

            // Science facts
            $localScience = \Illuminate\Support\Facades\DB::table('science_facts')
                ->join('quran_science_links', 'science_facts.id', '=', 'quran_science_links.science_fact_id')
                ->where('quran_science_links.verse_id', $localVerse->id)
                ->where('quran_science_links.status', 'approved')
                ->select('science_facts.*', 'quran_science_links.relevance_description as link_description')
                ->get();

            // Scripture links (Bible)
            $localBible = \Illuminate\Support\Facades\DB::table('bible_verses')
                ->join('quran_scripture_links', 'bible_verses.id', '=', 'quran_scripture_links.bible_verse_id')
                ->where('quran_scripture_links.verse_id', $localVerse->id)
                ->where('quran_scripture_links.status', 'approved')
                ->select('bible_verses.*', 'quran_scripture_links.description as link_description', 'quran_scripture_links.relationship_type')
                ->get();

            // Scripture links (Torah)
            $localTorah = \Illuminate\Support\Facades\DB::table('torah_sections')
                ->join('quran_scripture_links', 'torah_sections.id', '=', 'quran_scripture_links.torah_section_id')
                ->where('quran_scripture_links.verse_id', $localVerse->id)
                ->where('quran_scripture_links.status', 'approved')
                ->select('torah_sections.*', 'quran_scripture_links.description as link_description', 'quran_scripture_links.relationship_type')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            Log::warning('Local link tables are missing', ['error' => $e->getMessage()]);
        }

        $themes = \App\Models\Theme::where('is_active', true)->orderBy('name')->get();
        $scienceCategories = $this->getScienceCategories();

        return view('quranic-lens.verse', compact(
            'surahInfo',
            'verseDetail',
            'analyses',
            'wordTags',
            'verseTags',
            'chapter',
            'verse',
            'juz',
            'localTafsir',
            'localSeerat',
            'localHadith',
            'localHistory',
            'localScience',
            'localBible',
            'localTorah',
            'themes',
            'scienceCategories'
        ));
    }

    /**
     * Submit a new analysis under a specific lens.
     */
    public function storeAnalysis(Request $request)
    {
        $validLensTypes = ['tafsir', 'hadith', 'seerat', 'science', 'history', 'bible', 'torah'];
        try {
            $dbSlugs = \App\Models\ScienceCategory::pluck('slug')->toArray();
            $validLensTypes = array_merge($validLensTypes, $dbSlugs);
        } catch (\Exception $e) {
        }

        $request->validate([
            'chapter_number' => 'required|integer|min:1|max:114',
            'verse_number' => 'required|integer|min:1',
            'lens_type' => 'required|string|in:' . implode(',', array_unique($validLensTypes)),
            'science_category' => 'nullable|string|in:' . implode(',', array_unique($dbSlugs ?? [])),
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:10',
            'theme_id' => 'nullable|integer|exists:themes,id',
        ]);

        $lensType = $request->lens_type;
        if ($lensType === 'science' && $request->filled('science_category')) {
            $lensType = $request->science_category;
        }

        QuranicLensAnalysis::create([
            'user_id' => Auth::id(),
            'chapter_number' => $request->chapter_number,
            'verse_number' => $request->verse_number,
            'lens_type' => $lensType,
            'title' => $request->title,
            'content' => $request->content,
            'theme_id' => $request->theme_id,
            'status' => (Auth::user() && Auth::user()->is_researcher) ? 'approved' : 'pending',
        ]);

        $isResearcher = Auth::user() && Auth::user()->is_researcher;
        $message = $isResearcher ? 'Your lens analysis has been published successfully.' : 'Your lens analysis has been submitted and is currently pending review by a researcher.';
        return back()->with('success', $message);
    }

    /**
     * Submit a tag for a specific word.
     */
    public function storeWordTag(Request $request)
    {
        $request->validate([
            'chapter_number' => 'required|integer|min:1|max:114',
            'verse_number' => 'required|integer|min:1',
            'word_position' => 'required|integer|min:1',
            'word_text' => 'required|string|max:255',
            'tag_type' => 'required|string|in:grammar,root_word,thematic,custom,science',
            'tag_value' => 'required|string|max:100',
            'explanation' => 'nullable|string|max:2000',
        ]);

        QuranicLensWordTag::create([
            'user_id' => Auth::id(),
            'chapter_number' => $request->chapter_number,
            'verse_number' => $request->verse_number,
            'word_position' => $request->word_position,
            'word_text' => $request->word_text,
            'tag_type' => $request->tag_type,
            'tag_value' => $request->tag_value,
            'explanation' => $request->explanation,
            'status' => (Auth::user() && Auth::user()->is_researcher) ? 'approved' : 'pending',
        ]);

        $isResearcher = Auth::user() && Auth::user()->is_researcher;
        $message = $isResearcher ? 'Your word tag has been added successfully.' : 'Your word tag has been submitted for review.';
        return back()->with('success', $message);
    }

    /**
     * Submit a tag for a specific verse.
     */
    public function storeVerseTag(Request $request)
    {
        $request->validate([
            'chapter_number' => 'required|integer|min:1|max:114',
            'verse_number' => 'required|integer|min:1',
            'tag_type' => 'required|string|in:theme,law,theology,prophecy,custom,science',
            'tag_value' => 'required|string|max:100',
            'explanation' => 'nullable|string|max:2000',
        ]);

        QuranicLensVerseTag::create([
            'user_id' => Auth::id(),
            'chapter_number' => $request->chapter_number,
            'verse_number' => $request->verse_number,
            'tag_type' => $request->tag_type,
            'tag_value' => $request->tag_value,
            'explanation' => $request->explanation,
            'status' => (Auth::user() && Auth::user()->is_researcher) ? 'approved' : 'pending',
        ]);

        $isResearcher = Auth::user() && Auth::user()->is_researcher;
        $message = $isResearcher ? 'Your verse tag has been added successfully.' : 'Your verse tag has been submitted for review.';
        return back()->with('success', $message);
    }

    /**
     * Call Gemini to generate a draft lens analysis.
     */
    public function generateAiAnalysis(Request $request, GeminiService $gemini)
    {
        $request->validate([
            'chapter_number' => 'required|integer|min:1|max:114',
            'verse_number' => 'required|integer|min:1',
            'arabic' => 'required|string',
            'translation' => 'required|string',
            'lens_type' => 'required|string|in:tafsir,hadith,seerat,science,biology,maths,history,bible,torah,psychology',
        ]);

        try {
            $analysis = $gemini->generateVerseLensAnalysis(
                $request->chapter_number,
                $request->verse_number,
                $request->arabic,
                $request->translation,
                $request->lens_type
            );

            if ($analysis) {
                return response()->json([
                    'success' => true,
                    'analysis' => trim($analysis)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('AI Analysis generation failed', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to generate AI analysis. Please try writing manually.'
        ], 500);
    }

    /**
     * Hardcoded fallback details of the 114 Surahs.
     */
    private function getStaticChaptersFallback()
    {
        return [
            ['id' => 1, 'name_simple' => 'Al-Fatihah', 'name_arabic' => 'الفاتحة', 'translated_name' => ['name' => 'The Opening'], 'verses_count' => 7, 'revelation_place' => 'makkah'],
            ['id' => 2, 'name_simple' => 'Al-Baqarah', 'name_arabic' => 'البقرة', 'translated_name' => ['name' => 'The Cow'], 'verses_count' => 286, 'revelation_place' => 'madinah'],
            ['id' => 3, 'name_simple' => 'Ali \'Imran', 'name_arabic' => 'آل عمران', 'translated_name' => ['name' => 'Family of Imran'], 'verses_count' => 200, 'revelation_place' => 'madinah'],
            ['id' => 4, 'name_simple' => 'An-Nisa', 'name_arabic' => 'النساء', 'translated_name' => ['name' => 'The Women'], 'verses_count' => 176, 'revelation_place' => 'madinah'],
            ['id' => 5, 'name_simple' => 'Al-Ma\'idah', 'name_arabic' => 'المائدة', 'translated_name' => ['name' => 'The Table Spread'], 'verses_count' => 120, 'revelation_place' => 'madinah'],
            ['id' => 6, 'name_simple' => 'Al-An\'am', 'name_arabic' => 'الأنعام', 'translated_name' => ['name' => 'The Cattle'], 'verses_count' => 165, 'revelation_place' => 'makkah'],
            ['id' => 7, 'name_simple' => 'Al-A\'raf', 'name_arabic' => 'الأعراف', 'translated_name' => ['name' => 'The Heights'], 'verses_count' => 206, 'revelation_place' => 'makkah'],
            ['id' => 8, 'name_simple' => 'Al-Anfal', 'name_arabic' => 'الأنفال', 'translated_name' => ['name' => 'The Spoils of War'], 'verses_count' => 75, 'revelation_place' => 'madinah'],
            ['id' => 9, 'name_simple' => 'At-Tawbah', 'name_arabic' => 'التوبة', 'translated_name' => ['name' => 'The Repentance'], 'verses_count' => 129, 'revelation_place' => 'madinah'],
            ['id' => 10, 'name_simple' => 'Yunus', 'name_arabic' => 'يونس', 'translated_name' => ['name' => 'Jonah'], 'verses_count' => 109, 'revelation_place' => 'makkah'],
            ['id' => 11, 'name_simple' => 'Hud', 'name_arabic' => 'هود', 'translated_name' => ['name' => 'Hud'], 'verses_count' => 123, 'revelation_place' => 'makkah'],
            ['id' => 12, 'name_simple' => 'Yusuf', 'name_arabic' => 'يوسف', 'translated_name' => ['name' => 'Joseph'], 'verses_count' => 111, 'revelation_place' => 'makkah'],
            ['id' => 13, 'name_simple' => 'Ar-Ra\'d', 'name_arabic' => 'الرعد', 'translated_name' => ['name' => 'The Thunder'], 'verses_count' => 43, 'revelation_place' => 'madinah'],
            ['id' => 14, 'name_simple' => 'Ibrahim', 'name_arabic' => 'إبراهيم', 'translated_name' => ['name' => 'Abraham'], 'verses_count' => 52, 'revelation_place' => 'makkah'],
            ['id' => 15, 'name_simple' => 'Al-Hijr', 'name_arabic' => 'الحجر', 'translated_name' => ['name' => 'The Rocky Tract'], 'verses_count' => 99, 'revelation_place' => 'makkah'],
            ['id' => 16, 'name_simple' => 'An-Nahl', 'name_arabic' => 'النحل', 'translated_name' => ['name' => 'The Bee'], 'verses_count' => 128, 'revelation_place' => 'makkah'],
            ['id' => 17, 'name_simple' => 'Al-Isra', 'name_arabic' => 'الإسراء', 'translated_name' => ['name' => 'The Night Journey'], 'verses_count' => 111, 'revelation_place' => 'makkah'],
            ['id' => 18, 'name_simple' => 'Al-Kahf', 'name_arabic' => 'الكهف', 'translated_name' => ['name' => 'The Cave'], 'verses_count' => 110, 'revelation_place' => 'makkah'],
            ['id' => 19, 'name_simple' => 'Maryam', 'name_arabic' => 'مريم', 'translated_name' => ['name' => 'Mary'], 'verses_count' => 98, 'revelation_place' => 'makkah'],
            ['id' => 20, 'name_simple' => 'Taha', 'name_arabic' => 'طه', 'translated_name' => ['name' => 'Ta-Ha'], 'verses_count' => 135, 'revelation_place' => 'makkah'],
            ['id' => 21, 'name_simple' => 'Al-Anbya', 'name_arabic' => 'الأنبياء', 'translated_name' => ['name' => 'The Prophets'], 'verses_count' => 112, 'revelation_place' => 'makkah'],
            ['id' => 22, 'name_simple' => 'Al-Hajj', 'name_arabic' => 'الحج', 'translated_name' => ['name' => 'The Pilgrimage'], 'verses_count' => 78, 'revelation_place' => 'madinah'],
            ['id' => 23, 'name_simple' => 'Al-Mu\'minun', 'name_arabic' => 'المؤمنون', 'translated_name' => ['name' => 'The Believers'], 'verses_count' => 118, 'revelation_place' => 'makkah'],
            ['id' => 24, 'name_simple' => 'An-Nur', 'name_arabic' => 'النور', 'translated_name' => ['name' => 'The Light'], 'verses_count' => 64, 'revelation_place' => 'madinah'],
            ['id' => 25, 'name_simple' => 'Al-Furqan', 'name_arabic' => 'الفرقان', 'translated_name' => ['name' => 'The Criterion'], 'verses_count' => 77, 'revelation_place' => 'makkah'],
            ['id' => 26, 'name_simple' => 'Ash-Shu\'ara', 'name_arabic' => 'الشعراء', 'translated_name' => ['name' => 'The Poets'], 'verses_count' => 227, 'revelation_place' => 'makkah'],
            ['id' => 27, 'name_simple' => 'An-Naml', 'name_arabic' => 'النمل', 'translated_name' => ['name' => 'The Ant'], 'verses_count' => 93, 'revelation_place' => 'makkah'],
            ['id' => 28, 'name_simple' => 'Al-Qasas', 'name_arabic' => 'القصص', 'translated_name' => ['name' => 'The Stories'], 'verses_count' => 88, 'revelation_place' => 'makkah'],
            ['id' => 29, 'name_simple' => 'Al-\'Ankabut', 'name_arabic' => 'العنكبوت', 'translated_name' => ['name' => 'The Spider'], 'verses_count' => 69, 'revelation_place' => 'makkah'],
            ['id' => 30, 'name_simple' => 'Ar-Rum', 'name_arabic' => 'الروم', 'translated_name' => ['name' => 'The Romans'], 'verses_count' => 60, 'revelation_place' => 'makkah'],
            ['id' => 31, 'name_simple' => 'Luqman', 'name_arabic' => 'لقمان', 'translated_name' => ['name' => 'Luqman'], 'verses_count' => 34, 'revelation_place' => 'makkah'],
            ['id' => 32, 'name_simple' => 'As-Sajdah', 'name_arabic' => 'السجدة', 'translated_name' => ['name' => 'The Prostration'], 'verses_count' => 30, 'revelation_place' => 'makkah'],
            ['id' => 33, 'name_simple' => 'Al-Ahzab', 'name_arabic' => 'الأحزاب', 'translated_name' => ['name' => 'The Combined Forces'], 'verses_count' => 73, 'revelation_place' => 'madinah'],
            ['id' => 34, 'name_simple' => 'Saba', 'name_arabic' => 'سبأ', 'translated_name' => ['name' => 'Sheba'], 'verses_count' => 54, 'revelation_place' => 'makkah'],
            ['id' => 35, 'name_simple' => 'Fatir', 'name_arabic' => 'فاطر', 'translated_name' => ['name' => 'Originator'], 'verses_count' => 45, 'revelation_place' => 'makkah'],
            ['id' => 36, 'name_simple' => 'Ya-Sin', 'name_arabic' => 'يس', 'translated_name' => ['name' => 'Ya Sin'], 'verses_count' => 83, 'revelation_place' => 'makkah'],
            ['id' => 37, 'name_simple' => 'As-Saffat', 'name_arabic' => 'الصافات', 'translated_name' => ['name' => 'Those who set the Ranks'], 'verses_count' => 182, 'revelation_place' => 'makkah'],
            ['id' => 38, 'name_simple' => 'Sad', 'name_arabic' => 'ص', 'translated_name' => ['name' => 'The Letter Sad'], 'verses_count' => 88, 'revelation_place' => 'makkah'],
            ['id' => 39, 'name_simple' => 'Az-Zumar', 'name_arabic' => 'الزمر', 'translated_name' => ['name' => 'The Troops'], 'verses_count' => 75, 'revelation_place' => 'makkah'],
            ['id' => 40, 'name_simple' => 'Ghafir', 'name_arabic' => 'غافر', 'translated_name' => ['name' => 'The Forgiver'], 'verses_count' => 85, 'revelation_place' => 'makkah'],
            ['id' => 41, 'name_simple' => 'Fussilat', 'name_arabic' => 'فصلت', 'translated_name' => ['name' => 'Explained in Detail'], 'verses_count' => 54, 'revelation_place' => 'makkah'],
            ['id' => 42, 'name_simple' => 'Ash-Shura', 'name_arabic' => 'الشورى', 'translated_name' => ['name' => 'The Consultation'], 'verses_count' => 53, 'revelation_place' => 'makkah'],
            ['id' => 43, 'name_simple' => 'Az-Zukhruf', 'name_arabic' => 'الزخرف', 'translated_name' => ['name' => 'The Ornaments of Gold'], 'verses_count' => 89, 'revelation_place' => 'makkah'],
            ['id' => 44, 'name_simple' => 'Ad-Dukhan', 'name_arabic' => 'الدخان', 'translated_name' => ['name' => 'The Smoke'], 'verses_count' => 59, 'revelation_place' => 'makkah'],
            ['id' => 45, 'name_simple' => 'Al-Jathiyah', 'name_arabic' => 'الجاثية', 'translated_name' => ['name' => 'The Crouching'], 'verses_count' => 37, 'revelation_place' => 'makkah'],
            ['id' => 46, 'name_simple' => 'Al-Ahqaf', 'name_arabic' => 'الأحقاف', 'translated_name' => ['name' => 'The Wind-Curved Sandhills'], 'verses_count' => 35, 'revelation_place' => 'makkah'],
            ['id' => 47, 'name_simple' => 'Muhammad', 'name_arabic' => 'محمد', 'translated_name' => ['name' => 'Muhammad'], 'verses_count' => 38, 'revelation_place' => 'madinah'],
            ['id' => 48, 'name_simple' => 'Al-Fath', 'name_arabic' => 'الفتح', 'translated_name' => ['name' => 'The Victory'], 'verses_count' => 29, 'revelation_place' => 'madinah'],
            ['id' => 49, 'name_simple' => 'Al-Hujurat', 'name_arabic' => 'الحجرات', 'translated_name' => ['name' => 'The Dwellings'], 'verses_count' => 18, 'revelation_place' => 'madinah'],
            ['id' => 50, 'name_simple' => 'Qaf', 'name_arabic' => 'ق', 'translated_name' => ['name' => 'The Letter Qaf'], 'verses_count' => 45, 'revelation_place' => 'makkah'],
            ['id' => 51, 'name_simple' => 'Adh-Dhariyat', 'name_arabic' => 'الذاريات', 'translated_name' => ['name' => 'The Winnowing Winds'], 'verses_count' => 60, 'revelation_place' => 'makkah'],
            ['id' => 52, 'name_simple' => 'At-Tur', 'name_arabic' => 'الطور', 'translated_name' => ['name' => 'The Mount'], 'verses_count' => 49, 'revelation_place' => 'makkah'],
            ['id' => 53, 'name_simple' => 'An-Najm', 'name_arabic' => 'النجم', 'translated_name' => ['name' => 'The Star'], 'verses_count' => 62, 'revelation_place' => 'makkah'],
            ['id' => 54, 'name_simple' => 'Al-Qamar', 'name_arabic' => 'القمر', 'translated_name' => ['name' => 'The Moon'], 'verses_count' => 55, 'revelation_place' => 'makkah'],
            ['id' => 55, 'name_simple' => 'Ar-Rahman', 'name_arabic' => 'الرحمن', 'translated_name' => ['name' => 'The Beneficent'], 'verses_count' => 78, 'revelation_place' => 'madinah'],
            ['id' => 56, 'name_simple' => 'Al-Waqi\'ah', 'name_arabic' => 'الواقعة', 'translated_name' => ['name' => 'The Inevitable'], 'verses_count' => 96, 'revelation_place' => 'makkah'],
            ['id' => 57, 'name_simple' => 'Al-Hadid', 'name_arabic' => 'الحديد', 'translated_name' => ['name' => 'The Iron'], 'verses_count' => 29, 'revelation_place' => 'madinah'],
            ['id' => 58, 'name_simple' => 'Al-Mujadilah', 'name_arabic' => 'المجادلة', 'translated_name' => ['name' => 'The Pleading Woman'], 'verses_count' => 22, 'revelation_place' => 'madinah'],
            ['id' => 59, 'name_simple' => 'Al-Hashr', 'name_arabic' => 'الحشر', 'translated_name' => ['name' => 'The Exile'], 'verses_count' => 24, 'revelation_place' => 'madinah'],
            ['id' => 60, 'name_simple' => 'Al-Mumtahanah', 'name_arabic' => 'الممتحنة', 'translated_name' => ['name' => 'She that is to be examined'], 'verses_count' => 13, 'revelation_place' => 'madinah'],
            ['id' => 61, 'name_simple' => 'As-Saff', 'name_arabic' => 'الصف', 'translated_name' => ['name' => 'The Ranks'], 'verses_count' => 14, 'revelation_place' => 'madinah'],
            ['id' => 62, 'name_simple' => 'Al-Jumu\'ah', 'name_arabic' => 'الجمعة', 'translated_name' => ['name' => 'The Congregation'], 'verses_count' => 11, 'revelation_place' => 'madinah'],
            ['id' => 63, 'name_simple' => 'Al-Munafiqun', 'name_arabic' => 'المنافقون', 'translated_name' => ['name' => 'The Hypocrites'], 'verses_count' => 11, 'revelation_place' => 'madinah'],
            ['id' => 64, 'name_simple' => 'At-Taghabun', 'name_arabic' => 'التغابن', 'translated_name' => ['name' => 'The Mutual Disillusion'], 'verses_count' => 18, 'revelation_place' => 'madinah'],
            ['id' => 65, 'name_simple' => 'At-Talaq', 'name_arabic' => 'الطلاق', 'translated_name' => ['name' => 'The Divorce'], 'verses_count' => 12, 'revelation_place' => 'madinah'],
            ['id' => 66, 'name_simple' => 'At-Tahrim', 'name_arabic' => 'التحريم', 'translated_name' => ['name' => 'The Prohibition'], 'verses_count' => 12, 'revelation_place' => 'madinah'],
            ['id' => 67, 'name_simple' => 'Al-Mulk', 'name_arabic' => 'الملك', 'translated_name' => ['name' => 'The Sovereignty'], 'verses_count' => 30, 'revelation_place' => 'makkah'],
            ['id' => 68, 'name_simple' => 'Al-Qalam', 'name_arabic' => 'القلم', 'translated_name' => ['name' => 'The Pen'], 'verses_count' => 52, 'revelation_place' => 'makkah'],
            ['id' => 69, 'name_simple' => 'Al-Haqqah', 'name_arabic' => 'الحاقة', 'translated_name' => ['name' => 'The Reality'], 'verses_count' => 52, 'revelation_place' => 'makkah'],
            ['id' => 70, 'name_simple' => 'Al-Ma\'arij', 'name_arabic' => 'المعارج', 'translated_name' => ['name' => 'The Ascending Stairways'], 'verses_count' => 44, 'revelation_place' => 'makkah'],
            ['id' => 71, 'name_simple' => 'Nuh', 'name_arabic' => 'نوح', 'translated_name' => ['name' => 'Noah'], 'verses_count' => 28, 'revelation_place' => 'makkah'],
            ['id' => 72, 'name_simple' => 'Al-Jinn', 'name_arabic' => 'الجن', 'translated_name' => ['name' => 'The Jinn'], 'verses_count' => 28, 'revelation_place' => 'makkah'],
            ['id' => 73, 'name_simple' => 'Al-Muzzammil', 'name_arabic' => 'المزمل', 'translated_name' => ['name' => 'The Enshrouded One'], 'verses_count' => 20, 'revelation_place' => 'makkah'],
            ['id' => 74, 'name_simple' => 'Al-Muddaththir', 'name_arabic' => 'المدثر', 'translated_name' => ['name' => 'The Cloaked One'], 'verses_count' => 56, 'revelation_place' => 'makkah'],
            ['id' => 75, 'name_simple' => 'Al-Qiyamah', 'name_arabic' => 'القيامة', 'translated_name' => ['name' => 'The Resurrection'], 'verses_count' => 40, 'revelation_place' => 'makkah'],
            ['id' => 76, 'name_simple' => 'Al-Insan', 'name_arabic' => 'الإنسان', 'translated_name' => ['name' => 'The Man'], 'verses_count' => 31, 'revelation_place' => 'madinah'],
            ['id' => 77, 'name_simple' => 'Al-Mursalat', 'name_arabic' => 'المرسلات', 'translated_name' => ['name' => 'The Emissaries'], 'verses_count' => 50, 'revelation_place' => 'makkah'],
            ['id' => 78, 'name_simple' => 'An-Naba', 'name_arabic' => 'النبأ', 'translated_name' => ['name' => 'The Tidings'], 'verses_count' => 40, 'revelation_place' => 'makkah'],
            ['id' => 79, 'name_simple' => 'An-Nazi\'at', 'name_arabic' => 'النازعات', 'translated_name' => ['name' => 'Those who drag forth'], 'verses_count' => 46, 'revelation_place' => 'makkah'],
            ['id' => 80, 'name_simple' => '\'Abasa', 'name_arabic' => 'عبس', 'translated_name' => ['name' => 'He Frowned'], 'verses_count' => 42, 'revelation_place' => 'makkah'],
            ['id' => 81, 'name_simple' => 'At-Takwir', 'name_arabic' => 'التكوير', 'translated_name' => ['name' => 'The Overthrowing'], 'verses_count' => 29, 'revelation_place' => 'makkah'],
            ['id' => 82, 'name_simple' => 'Al-Infitar', 'name_arabic' => 'الانفطار', 'translated_name' => ['name' => 'The Cleaving'], 'verses_count' => 19, 'revelation_place' => 'makkah'],
            ['id' => 83, 'name_simple' => 'Al-Mutaffifin', 'name_arabic' => 'المطففين', 'translated_name' => ['name' => 'The Defrauding'], 'verses_count' => 36, 'revelation_place' => 'makkah'],
            ['id' => 84, 'name_simple' => 'Al-Inshiqaq', 'name_arabic' => 'الانشقاق', 'translated_name' => ['name' => 'The Sundering'], 'verses_count' => 25, 'revelation_place' => 'makkah'],
            ['id' => 85, 'name_simple' => 'Al-Buruj', 'name_arabic' => 'البروج', 'translated_name' => ['name' => 'The Mansions of the Stars'], 'verses_count' => 22, 'revelation_place' => 'makkah'],
            ['id' => 86, 'name_simple' => 'At-Tariq', 'name_arabic' => 'الطارق', 'translated_name' => ['name' => 'The Morning Star'], 'verses_count' => 17, 'revelation_place' => 'makkah'],
            ['id' => 87, 'name_simple' => 'Al-A\'la', 'name_arabic' => 'الأعلى', 'translated_name' => ['name' => 'The Most High'], 'verses_count' => 19, 'revelation_place' => 'makkah'],
            ['id' => 88, 'name_simple' => 'Al-Ghashiyah', 'name_arabic' => 'الغاشية', 'translated_name' => ['name' => 'The Overwhelming'], 'verses_count' => 26, 'revelation_place' => 'makkah'],
            ['id' => 89, 'name_simple' => 'Al-Fajr', 'name_arabic' => 'الفجر', 'translated_name' => ['name' => 'The Dawn'], 'verses_count' => 30, 'revelation_place' => 'makkah'],
            ['id' => 90, 'name_simple' => 'Al-Balad', 'name_arabic' => 'البلد', 'translated_name' => ['name' => 'The City'], 'verses_count' => 20, 'revelation_place' => 'makkah'],
            ['id' => 91, 'name_simple' => 'Ash-Shams', 'name_arabic' => 'الشمس', 'translated_name' => ['name' => 'The Sun'], 'verses_count' => 15, 'revelation_place' => 'makkah'],
            ['id' => 92, 'name_simple' => 'Al-Lail', 'name_arabic' => 'الليل', 'translated_name' => ['name' => 'The Night'], 'verses_count' => 21, 'revelation_place' => 'makkah'],
            ['id' => 93, 'name_simple' => 'Ad-Duha', 'name_arabic' => 'الضحى', 'translated_name' => ['name' => 'The Morning Hours'], 'verses_count' => 11, 'revelation_place' => 'makkah'],
            ['id' => 94, 'name_simple' => 'Ash-Sharh', 'name_arabic' => 'الشرح', 'translated_name' => ['name' => 'The Relief'], 'verses_count' => 8, 'revelation_place' => 'makkah'],
            ['id' => 95, 'name_simple' => 'At-Tin', 'name_arabic' => 'التين', 'translated_name' => ['name' => 'The Fig'], 'verses_count' => 8, 'revelation_place' => 'makkah'],
            ['id' => 96, 'name_simple' => 'Al-\'Alaq', 'name_arabic' => 'العلق', 'translated_name' => ['name' => 'The Clot'], 'verses_count' => 19, 'revelation_place' => 'makkah'],
            ['id' => 97, 'name_simple' => 'Al-Qadr', 'name_arabic' => 'القدر', 'translated_name' => ['name' => 'The Power'], 'verses_count' => 5, 'revelation_place' => 'makkah'],
            ['id' => 98, 'name_simple' => 'Al-Bayyinah', 'name_arabic' => 'البينة', 'translated_name' => ['name' => 'The Clear Proof'], 'verses_count' => 8, 'revelation_place' => 'madinah'],
            ['id' => 99, 'name_simple' => 'Az-Zalzalah', 'name_arabic' => 'الزلزلة', 'translated_name' => ['name' => 'The Earthquake'], 'verses_count' => 8, 'revelation_place' => 'madinah'],
            ['id' => 100, 'name_simple' => 'Al-\'Adiyat', 'name_arabic' => 'العاديات', 'translated_name' => ['name' => 'The Courser'], 'verses_count' => 11, 'revelation_place' => 'makkah'],
            ['id' => 101, 'name_simple' => 'Al-Qari\'ah', 'name_arabic' => 'القارعة', 'translated_name' => ['name' => 'The Calamity'], 'verses_count' => 11, 'revelation_place' => 'makkah'],
            ['id' => 102, 'name_simple' => 'At-Takathur', 'name_arabic' => 'التكاثر', 'translated_name' => ['name' => 'The Rivalry in World Increase'], 'verses_count' => 8, 'revelation_place' => 'makkah'],
            ['id' => 103, 'name_simple' => 'Al-\'Asr', 'name_arabic' => 'العصر', 'translated_name' => ['name' => 'The Declining Day'], 'verses_count' => 3, 'revelation_place' => 'makkah'],
            ['id' => 104, 'name_simple' => 'Al-Humazah', 'name_arabic' => 'الهمزة', 'translated_name' => ['name' => 'The Traducer'], 'verses_count' => 9, 'revelation_place' => 'makkah'],
            ['id' => 105, 'name_simple' => 'Al-Fil', 'name_arabic' => 'الفيل', 'translated_name' => ['name' => 'The Elephant'], 'verses_count' => 5, 'revelation_place' => 'makkah'],
            ['id' => 106, 'name_simple' => 'Quraysh', 'name_arabic' => 'قريش', 'translated_name' => ['name' => 'Quraysh'], 'verses_count' => 4, 'revelation_place' => 'makkah'],
            ['id' => 107, 'name_simple' => 'Al-Ma\'un', 'name_arabic' => 'الماعون', 'translated_name' => ['name' => 'The Small Kindnesses'], 'verses_count' => 7, 'revelation_place' => 'makkah'],
            ['id' => 108, 'name_simple' => 'Al-Kawthar', 'name_arabic' => 'الكوثر', 'translated_name' => ['name' => 'The Abundance'], 'verses_count' => 3, 'revelation_place' => 'makkah'],
            ['id' => 109, 'name_simple' => 'Al-Kafirun', 'name_arabic' => 'الكافرون', 'translated_name' => ['name' => 'The Disbelievers'], 'verses_count' => 6, 'revelation_place' => 'makkah'],
            ['id' => 110, 'name_simple' => 'An-Nasr', 'name_arabic' => 'النصر', 'translated_name' => ['name' => 'The Divine Support'], 'verses_count' => 3, 'revelation_place' => 'madinah'],
            ['id' => 111, 'name_simple' => 'Al-Masad', 'name_arabic' => 'المسد', 'translated_name' => ['name' => 'The Palm Fiber'], 'verses_count' => 5, 'revelation_place' => 'makkah'],
            ['id' => 112, 'name_simple' => 'Al-Ikhlas', 'name_arabic' => 'الإخلاص', 'translated_name' => ['name' => 'The Sincerity'], 'verses_count' => 4, 'revelation_place' => 'makkah'],
            ['id' => 113, 'name_simple' => 'Al-Falaq', 'name_arabic' => 'الفلق', 'translated_name' => ['name' => 'The Daybreak'], 'verses_count' => 5, 'revelation_place' => 'makkah'],
            ['id' => 114, 'name_simple' => 'An-Nas', 'name_arabic' => 'الناس', 'translated_name' => ['name' => 'Mankind'], 'verses_count' => 6, 'revelation_place' => 'makkah'],
        ];
    }

    /**
     * Get the mapped high-level science categories.
     */
    public function getScienceCategories()
    {
        try {
            $categories = \App\Models\ScienceCategory::all();
            if ($categories->count() > 0) {
                $mapped = [];
                foreach ($categories as $cat) {
                    $mapped[$cat->slug] = [
                        'label' => $cat->name,
                        'emoji' => $cat->emoji,
                        'fields' => $cat->fields,
                    ];
                }
                return $mapped;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to load science categories from DB', ['error' => $e->getMessage()]);
        }

        return [
            'neuroscience_psychology' => ['label' => 'Neuroscience / Psychology', 'emoji' => '🧠', 'fields' => ['neuroscience', 'psychology']],
            'astronomy_cosmology' => ['label' => 'Astronomy / Cosmology', 'emoji' => '🪐', 'fields' => ['astronomy', 'cosmology']],
            'geology' => ['label' => 'Geology', 'emoji' => '🪨', 'fields' => ['geology']],
            'biology' => ['label' => 'Biology', 'emoji' => '🧬', 'fields' => ['biology']],
            'embryology' => ['label' => 'Embryology', 'emoji' => '🍼', 'fields' => ['embryology']],
            'oceanography' => ['label' => 'Oceanography', 'emoji' => '🌊', 'fields' => ['oceanography']],
            'hydrology' => ['label' => 'Hydrology', 'emoji' => '💧', 'fields' => ['hydrology']],
            'meteorology' => ['label' => 'Meteorology', 'emoji' => '🌀', 'fields' => ['meteorology']],
            'physics' => ['label' => 'Physics', 'emoji' => '⚡', 'fields' => ['physics']],
            'general' => ['label' => 'General Science', 'emoji' => '🔬', 'fields' => ['general']],
        ];
    }

    /**
     * Display the Quranic Research Landing Page (SEO optimized).
     */
    public function landing()
    {
        $stats = [
            'science' => 0,
            'seerah' => 0,
            'hadith' => 0,
            'history' => 0,
            'scripture' => 0,
            'researchers' => 0,
        ];

        try {
            $stats['science'] = \Illuminate\Support\Facades\DB::table('quran_science_links')->where('status', 'approved')->count();
        } catch (\Exception $e) {
        }

        try {
            $stats['seerah'] = \Illuminate\Support\Facades\DB::table('quran_seerat_links')->where('status', 'approved')->count();
        } catch (\Exception $e) {
        }

        try {
            $stats['hadith'] = \Illuminate\Support\Facades\DB::table('quran_hadith_links')->where('status', 'approved')->count();
        } catch (\Exception $e) {
        }

        try {
            $stats['history'] = \Illuminate\Support\Facades\DB::table('quran_history_links')->where('status', 'approved')->count();
        } catch (\Exception $e) {
        }

        try {
            $stats['scripture'] = \Illuminate\Support\Facades\DB::table('quran_scripture_links')->where('status', 'approved')->count();
        } catch (\Exception $e) {
        }

        try {
            $stats['researchers'] = \App\Models\User::where('is_researcher', true)->orWhere('is_admin', true)->count();
        } catch (\Exception $e) {
        }

        $scienceCategories = $this->getScienceCategories();

        return view('quranic-lens.landing', compact('stats', 'scienceCategories'));
    }
}

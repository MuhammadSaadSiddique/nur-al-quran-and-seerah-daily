<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuranicLensAnalysis;
use App\Models\QuranicLensWordTag;
use App\Models\QuranicLensVerseTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuranicLensApprovalController extends Controller
{
    /**
     * Display the researcher approval queue dashboard.
     */
    public function index(Request $request)
    {
        $chapter = $request->input('chapter_number');
        $verse = $request->input('verse_number');
        $connType = $request->input('connection_type', 'all');

        $hadithTextCol = 'text_english';
        $hadithCollCol = 'name_english';
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('ahadith', 'text_english')) {
                $hadithTextCol = 'hadith_translation';
            }
            if (!\Illuminate\Support\Facades\Schema::hasColumn('hadith_collections', 'name_english')) {
                $hadithCollCol = 'name';
            }
        }

        $bibleTitleSelect = "CONCAT(bible_verses.book, ' ', bible_verses.chapter, ':', bible_verses.verse_number)";
        $bibleContentCol = "bible_verses.text_niv";
        $bibleExtraCol = "bible_verses.testament";

        $torahTitleSelect = "CONCAT(torah_sections.book, ' ', torah_sections.chapter, ':', torah_sections.verse_number)";
        $torahContentCol = "torah_sections.text_english";

        if ($driver === 'sqlite') {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('bible_verses', 'book')) {
                $bibleTitleSelect = "bible_verses.verse_reference";
                $bibleContentCol = "bible_verses.text";
                $bibleExtraCol = \Illuminate\Support\Facades\DB::raw("'Bible' as extra_info");
            }
            if (!\Illuminate\Support\Facades\Schema::hasColumn('torah_sections', 'book')) {
                $torahTitleSelect = "torah_sections.section_reference";
                $torahContentCol = "torah_sections.text";
            }
        }

        $historyTable = 'historical_events';
        $historyForeignKey = 'historical_event_id';
        $historyExtraCol = 'civilization';
        if (!\Illuminate\Support\Facades\Schema::hasTable('historical_events')) {
            $historyTable = 'history_contexts';
            $historyForeignKey = 'history_context_id';
            $historyExtraCol = 'historical_period';
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        $isRestricted = $user && $user->is_researcher && !$user->is_admin && $user->expert_category_id;
        $restrictedCategory = $isRestricted ? \App\Models\ScienceCategory::find($user->expert_category_id) : null;

        $filterCategoryId = $request->input('category_id');
        $filterCategory = $filterCategoryId ? \App\Models\ScienceCategory::find($filterCategoryId) : null;

        $activeCategory = $restrictedCategory ?: $filterCategory;

        $analysesQuery = QuranicLensAnalysis::pending()->with(['user', 'theme'])->latest();
        $wordTagsQuery = QuranicLensWordTag::pending()->with('user')->latest();
        $verseTagsQuery = QuranicLensVerseTag::pending()->with('user')->latest();
        $approvedAnalysesQuery = QuranicLensAnalysis::approved()->with(['user', 'theme', 'moderator'])->latest();

        if ($activeCategory) {
            $categoryFields = array_merge([$activeCategory->slug], $activeCategory->fields);
            $analysesQuery->whereIn('lens_type', $categoryFields);
            $approvedAnalysesQuery->whereIn('lens_type', $categoryFields);
            $wordTagsQuery->where('tag_type', 'science')->where('tag_value', $activeCategory->name);
            $verseTagsQuery->where('tag_type', 'science')->where('tag_value', $activeCategory->name);
        }

        if ($chapter) {
            $analysesQuery->where('chapter_number', $chapter);
            $wordTagsQuery->where('chapter_number', $chapter);
            $verseTagsQuery->where('chapter_number', $chapter);
            $approvedAnalysesQuery->where('chapter_number', $chapter);
        }
        if ($verse) {
            $analysesQuery->where('verse_number', $verse);
            $wordTagsQuery->where('verse_number', $verse);
            $verseTagsQuery->where('verse_number', $verse);
            $approvedAnalysesQuery->where('verse_number', $verse);
        }

        $pendingAnalyses = $analysesQuery->paginate(15, ['*'], 'analyses_page')->appends($request->query())->appends(['tab' => 'analyses']);
        $pendingWordTags = $wordTagsQuery->paginate(15, ['*'], 'word_tags_page')->appends($request->query())->appends(['tab' => 'words']);
        $pendingVerseTags = $verseTagsQuery->paginate(15, ['*'], 'verse_tags_page')->appends($request->query())->appends(['tab' => 'verses']);
        $approvedAnalyses = $approvedAnalysesQuery->paginate(15, ['*'], 'approved_page')->appends($request->query())->appends(['tab' => 'approved']);

        $themes = \App\Models\Theme::where('is_active', true)->orderBy('name')->get();
        $scienceCategories = \App\Models\ScienceCategory::orderBy('name')->get();

        // Union Connections
        $queries = [];

        // 1. Science Links
        if ($connType === 'all' || $connType === 'science') {
            $sciQuery = \Illuminate\Support\Facades\DB::table('quran_science_links')
                ->join('verses', 'quran_science_links.verse_id', '=', 'verses.id')
                ->join('surahs', 'verses.surah_id', '=', 'surahs.id')
                ->join('science_facts', 'quran_science_links.science_fact_id', '=', 'science_facts.id')
                ->where('quran_science_links.status', 'pending');

            if ($activeCategory) {
                $sciQuery->whereIn('science_facts.field', $activeCategory->fields);
            }

            $sciQuery->select(
                    'quran_science_links.id as id',
                    'verses.verse_number as verse_number',
                    'surahs.name_transliteration as surah_name',
                    'surahs.number as surah_number',
                    'science_facts.title as title',
                    'science_facts.description as content',
                    'science_facts.field as extra_info',
                    \Illuminate\Support\Facades\DB::raw("'science' as type"),
                    \Illuminate\Support\Facades\DB::raw("'quran_science_links' as `table`")
                );
            if ($chapter) $sciQuery->where('surahs.number', $chapter);
            if ($verse) $sciQuery->where('verses.verse_number', $verse);
            $queries[] = $sciQuery;
        }

        // 2. Seerat Links
        if (!$activeCategory && ($connType === 'all' || $connType === 'seerat')) {
            $seerQuery = \Illuminate\Support\Facades\DB::table('quran_seerat_links')
                ->join('verses', 'quran_seerat_links.verse_id', '=', 'verses.id')
                ->join('surahs', 'verses.surah_id', '=', 'surahs.id')
                ->join('seerat_events', 'quran_seerat_links.seerat_event_id', '=', 'seerat_events.id')
                ->where('quran_seerat_links.status', 'pending')
                ->select(
                    'quran_seerat_links.id as id',
                    'verses.verse_number as verse_number',
                    'surahs.name_transliteration as surah_name',
                    'surahs.number as surah_number',
                    'seerat_events.title as title',
                    'seerat_events.description as content',
                    'seerat_events.category as extra_info',
                    \Illuminate\Support\Facades\DB::raw("'seerat' as type"),
                    \Illuminate\Support\Facades\DB::raw("'quran_seerat_links' as `table`")
                );
            if ($chapter) $seerQuery->where('surahs.number', $chapter);
            if ($verse) $seerQuery->where('verses.verse_number', $verse);
            $queries[] = $seerQuery;
        }

        // 3. Hadith Links
        if (!$activeCategory && ($connType === 'all' || $connType === 'hadith')) {
            $hadQuery = \Illuminate\Support\Facades\DB::table('quran_hadith_links')
                ->join('verses', 'quran_hadith_links.verse_id', '=', 'verses.id')
                ->join('surahs', 'verses.surah_id', '=', 'surahs.id')
                ->join('ahadith', 'quran_hadith_links.hadith_id', '=', 'ahadith.id')
                ->leftJoin('hadith_collections', 'ahadith.collection_id', '=', 'hadith_collections.id')
                ->where('quran_hadith_links.status', 'pending')
                ->select(
                    'quran_hadith_links.id as id',
                    'verses.verse_number as verse_number',
                    'surahs.name_transliteration as surah_name',
                    'surahs.number as surah_number',
                    'ahadith.hadith_number as title',
                    "ahadith.{$hadithTextCol} as content",
                    "hadith_collections.{$hadithCollCol} as extra_info",
                    \Illuminate\Support\Facades\DB::raw("'hadith' as type"),
                    \Illuminate\Support\Facades\DB::raw("'quran_hadith_links' as `table`")
                );
            if ($chapter) $hadQuery->where('surahs.number', $chapter);
            if ($verse) $hadQuery->where('verses.verse_number', $verse);
            $queries[] = $hadQuery;
        }

        // 4. History Links
        if (!$activeCategory && ($connType === 'all' || $connType === 'history')) {
            $histQuery = \Illuminate\Support\Facades\DB::table('quran_history_links')
                ->join('verses', 'quran_history_links.verse_id', '=', 'verses.id')
                ->join('surahs', 'verses.surah_id', '=', 'surahs.id')
                ->join($historyTable, "quran_history_links.{$historyForeignKey}", '=', "{$historyTable}.id")
                ->where('quran_history_links.status', 'pending')
                ->select(
                    'quran_history_links.id as id',
                    'verses.verse_number as verse_number',
                    'surahs.name_transliteration as surah_name',
                    'surahs.number as surah_number',
                    "{$historyTable}.title as title",
                    "{$historyTable}.description as content",
                    "{$historyTable}.{$historyExtraCol} as extra_info",
                    \Illuminate\Support\Facades\DB::raw("'history' as type"),
                    \Illuminate\Support\Facades\DB::raw("'quran_history_links' as `table`")
                );
            if ($chapter) $histQuery->where('surahs.number', $chapter);
            if ($verse) $histQuery->where('verses.verse_number', $verse);
            $queries[] = $histQuery;
        }

        // 5. Scripture Links
        if (!$activeCategory && ($connType === 'all' || $connType === 'scripture')) {
            $scriptQuery = \Illuminate\Support\Facades\DB::table('quran_scripture_links')
                ->join('verses', 'quran_scripture_links.verse_id', '=', 'verses.id')
                ->join('surahs', 'verses.surah_id', '=', 'surahs.id')
                ->leftJoin('bible_verses', 'quran_scripture_links.bible_verse_id', '=', 'bible_verses.id')
                ->leftJoin('torah_sections', 'quran_scripture_links.torah_section_id', '=', 'torah_sections.id')
                ->where('quran_scripture_links.status', 'pending')
                ->select(
                    'quran_scripture_links.id as id',
                    'verses.verse_number as verse_number',
                    'surahs.name_transliteration as surah_name',
                    'surahs.number as surah_number',
                    \Illuminate\Support\Facades\DB::raw("CASE WHEN quran_scripture_links.bible_verse_id IS NOT NULL THEN {$bibleTitleSelect} ELSE {$torahTitleSelect} END as title"),
                    \Illuminate\Support\Facades\DB::raw("CASE WHEN quran_scripture_links.bible_verse_id IS NOT NULL THEN {$bibleContentCol} ELSE {$torahContentCol} END as content"),
                    \Illuminate\Support\Facades\DB::raw("CASE WHEN quran_scripture_links.bible_verse_id IS NOT NULL THEN 'Bible' ELSE 'Torah' END as extra_info"),
                    \Illuminate\Support\Facades\DB::raw("'scripture' as type"),
                    \Illuminate\Support\Facades\DB::raw("'quran_scripture_links' as `table`")
                );
            if ($chapter) $scriptQuery->where('surahs.number', $chapter);
            if ($verse) $scriptQuery->where('verses.verse_number', $verse);
            $queries[] = $scriptQuery;
        }

        $unionQuery = null;
        foreach ($queries as $q) {
            if (!$unionQuery) {
                $unionQuery = $q;
            } else {
                $unionQuery = $unionQuery->union($q);
            }
        }

        if ($unionQuery) {
            $pendingConnections = \Illuminate\Support\Facades\DB::table(\Illuminate\Support\Facades\DB::raw("({$unionQuery->toSql()}) as conn_union"))
                ->mergeBindings($unionQuery)
                ->paginate(15, ['*'], 'connections_page')
                ->appends($request->query())
                ->appends(['tab' => 'connections']);
        } else {
            $pendingConnections = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, null, [
                'path' => $request->url(),
                'query' => array_merge($request->query(), ['tab' => 'connections'])
            ]);
        }

        return view('admin.lens.approvals', compact(
            'pendingAnalyses',
            'pendingWordTags',
            'pendingVerseTags',
            'themes',
            'approvedAnalyses',
            'scienceCategories',
            'pendingConnections'
        ));
    }

    /**
     * Approve a pending analysis, word tag, or verse tag.
     */
    public function approve(Request $request, $type, $id)
    {
        $id = (int) $id;

        switch ($type) {
            case 'analysis':
                $item = QuranicLensAnalysis::findOrFail($id);
                if ($request->has('theme_id')) {
                    $item->theme_id = $request->theme_id ?: null;
                    $item->save();
                }
                break;
            case 'word-tag':
                $item = QuranicLensWordTag::findOrFail($id);
                break;
            case 'verse-tag':
                $item = QuranicLensVerseTag::findOrFail($id);
                break;
            default:
                return back()->with('error', 'Invalid approval type.');
        }

        $item->update([
            'status' => 'approved',
            'moderated_by' => Auth::id(),
            'moderated_at' => now(),
        ]);

        return back()->with('success', 'Item has been successfully approved and published.');
    }

    /**
     * Reject a pending analysis, word tag, or verse tag.
     */
    public function reject(Request $request, $type, $id)
    {
        $id = (int) $id;
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        switch ($type) {
            case 'analysis':
                $item = QuranicLensAnalysis::findOrFail($id);
                $item->rejection_reason = $request->rejection_reason;
                break;
            case 'word-tag':
                $item = QuranicLensWordTag::findOrFail($id);
                break;
            case 'verse-tag':
                $item = QuranicLensVerseTag::findOrFail($id);
                break;
            default:
                return back()->with('error', 'Invalid rejection type.');
        }

        $item->status = 'rejected';
        $item->moderated_by = Auth::id();
        $item->moderated_at = now();
        $item->save();
        $item->delete();

        return back()->with('success', 'Item has been rejected.');
    }

    /**
     * Create a direct connection link for a verse (Science, Hadith, Seerah, History, Bible, Torah).
     */
    public function createConnectionLink(Request $request)
    {
        $request->validate([
            'chapter_number' => 'required|integer|min:1|max:114',
            'verse_number' => 'required|integer|min:1',
            'link_type' => 'required|string|in:science,seerat,hadith,history,bible,torah',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'extra_info' => 'nullable|string|max:255', // Field, Category, Collection/HadithNo, Period, Reference
        ]);

        // Find the verse ID
        $verse = \Illuminate\Support\Facades\DB::table('verses')
            ->where('surah_id', $request->chapter_number)
            ->where('verse_number', $request->verse_number)
            ->first();

        if (!$verse) {
            return back()->with('error', "Verse {$request->chapter_number}:{$request->verse_number} does not exist in the database.");
        }

        try {
            switch ($request->link_type) {
                case 'science':
                    // Check if science fact exists by title
                    $fact = \Illuminate\Support\Facades\DB::table('science_facts')
                        ->where('title', $request->title)
                        ->first();

                    if (!$fact) {
                        $factId = \Illuminate\Support\Facades\DB::table('science_facts')->insertGetId([
                            'title' => $request->title,
                            'description' => $request->content,
                            'field' => $request->extra_info ?: 'General Science',
                        ]);
                    } else {
                        $factId = $fact->id;
                    }

                    // Check for duplicate pivot link mapping
                    $exists = \Illuminate\Support\Facades\DB::table('quran_science_links')
                        ->where('verse_id', $verse->id)
                        ->where('science_fact_id', $factId)
                        ->exists();

                    if ($exists) {
                        return back()->with('error', 'This Science connection already exists for this verse. Data duplication was prevented.');
                    }

                    \Illuminate\Support\Facades\DB::table('quran_science_links')->insert([
                        'verse_id' => $verse->id,
                        'science_fact_id' => $factId,
                        'status' => 'approved',
                    ]);
                    break;

                case 'seerat':
                    // Check if event exists by title
                    $event = \Illuminate\Support\Facades\DB::table('seerat_events')
                        ->where('title', $request->title)
                        ->first();

                    if (!$event) {
                        $eventId = \Illuminate\Support\Facades\DB::table('seerat_events')->insertGetId([
                            'title' => $request->title,
                            'description' => $request->content,
                            'category' => $request->extra_info ?: 'General',
                        ]);
                    } else {
                        $eventId = $event->id;
                    }

                    // Check for duplicate pivot link mapping
                    $exists = \Illuminate\Support\Facades\DB::table('quran_seerat_links')
                        ->where('verse_id', $verse->id)
                        ->where('seerat_event_id', $eventId)
                        ->exists();

                    if ($exists) {
                        return back()->with('error', 'This Seerah connection already exists for this verse. Data duplication was prevented.');
                    }

                    \Illuminate\Support\Facades\DB::table('quran_seerat_links')->insert([
                        'verse_id' => $verse->id,
                        'seerat_event_id' => $eventId,
                        'status' => 'approved',
                    ]);
                    break;

                case 'hadith':
                    // Check if hadith exists by number
                    $hadithNum = $request->extra_info ?: 'Custom';
                    $hadith = \Illuminate\Support\Facades\DB::table('ahadith')
                        ->where('hadith_number', $hadithNum)
                        ->first();

                    if (!$hadith) {
                        $hadithId = \Illuminate\Support\Facades\DB::table('ahadith')->insertGetId([
                            'hadith_number' => $hadithNum,
                            'hadith_text' => $request->title,
                            'hadith_translation' => $request->content,
                            'collection_id' => 1, // Default collection
                        ]);
                    } else {
                        $hadithId = $hadith->id;
                    }

                    // Check for duplicate pivot link mapping
                    $exists = \Illuminate\Support\Facades\DB::table('quran_hadith_links')
                        ->where('verse_id', $verse->id)
                        ->where('hadith_id', $hadithId)
                        ->exists();

                    if ($exists) {
                        return back()->with('error', 'This Hadith connection already exists for this verse. Data duplication was prevented.');
                    }

                    \Illuminate\Support\Facades\DB::table('quran_hadith_links')->insert([
                        'verse_id' => $verse->id,
                        'hadith_id' => $hadithId,
                        'status' => 'approved',
                    ]);
                    break;

                case 'history':
                    $histTable = 'historical_events';
                    $histForeignKey = 'historical_event_id';
                    $histPeriodCol = 'date_range';

                    if (!\Illuminate\Support\Facades\Schema::hasTable('historical_events')) {
                        $histTable = 'history_contexts';
                        $histForeignKey = 'history_context_id';
                        $histPeriodCol = 'historical_period';
                    }

                    $hist = \Illuminate\Support\Facades\DB::table($histTable)
                        ->where('title', $request->title)
                        ->first();

                    if (!$hist) {
                        $insertData = [
                            'title' => $request->title,
                            'description' => $request->content,
                        ];
                        $insertData[$histPeriodCol] = $request->extra_info ?: 'Ancient';
                        $histId = \Illuminate\Support\Facades\DB::table($histTable)->insertGetId($insertData);
                    } else {
                        $histId = $hist->id;
                    }

                    // Check for duplicate pivot link mapping
                    $exists = \Illuminate\Support\Facades\DB::table('quran_history_links')
                        ->where('verse_id', $verse->id)
                        ->where($histForeignKey, $histId)
                        ->exists();

                    if ($exists) {
                        return back()->with('error', 'This History connection already exists for this verse. Data duplication was prevented.');
                    }

                    \Illuminate\Support\Facades\DB::table('quran_history_links')->insert([
                        'verse_id' => $verse->id,
                        $histForeignKey => $histId,
                        'status' => 'approved',
                    ]);
                    break;

                case 'bible':
                    if (\Illuminate\Support\Facades\Schema::hasColumn('bible_verses', 'verse_reference')) {
                        $bible = \Illuminate\Support\Facades\DB::table('bible_verses')
                            ->where('verse_reference', $request->title)
                            ->first();

                        if (!$bible) {
                            $bibleId = \Illuminate\Support\Facades\DB::table('bible_verses')->insertGetId([
                                'verse_reference' => $request->title,
                                'text' => $request->content,
                            ]);
                        } else {
                            $bibleId = $bible->id;
                        }
                    } else {
                        // Parse book, chapter, verse
                        $bibleBook = 'Genesis';
                        $bibleChapter = 1;
                        $bibleVerseNum = 1;
                        if (preg_match('/^(.*?)\s+(\d+):(\d+)$/', trim($request->title), $matches)) {
                            $bibleBook = $matches[1];
                            $bibleChapter = (int) $matches[2];
                            $bibleVerseNum = (int) $matches[3];
                        }

                        $bible = \Illuminate\Support\Facades\DB::table('bible_verses')
                            ->where('book', $bibleBook)
                            ->where('chapter', $bibleChapter)
                            ->where('verse_number', $bibleVerseNum)
                            ->first();

                        if (!$bible) {
                            $bibleId = \Illuminate\Support\Facades\DB::table('bible_verses')->insertGetId([
                                'book' => $bibleBook,
                                'chapter' => $bibleChapter,
                                'verse_number' => $bibleVerseNum,
                                'text_niv' => $request->content,
                                'text_kjv' => $request->content,
                                'testament' => in_array(strtolower($bibleBook), ['matthew', 'mark', 'lukas', 'luke', 'john', 'acts', 'romans', 'corinthians', 'galatians', 'ephesians', 'philippians', 'colossians', 'thessalonians', 'timothy', 'titus', 'philemon', 'hebrews', 'james', 'peter', 'jude', 'revelation']) ? 'NT' : 'OT',
                            ]);
                        } else {
                            $bibleId = $bible->id;
                        }
                    }

                    // Check for duplicate pivot link mapping
                    $exists = \Illuminate\Support\Facades\DB::table('quran_scripture_links')
                        ->where('verse_id', $verse->id)
                        ->where('bible_verse_id', $bibleId)
                        ->exists();

                    if ($exists) {
                        return back()->with('error', 'This Bible connection already exists for this verse. Data duplication was prevented.');
                    }

                    \Illuminate\Support\Facades\DB::table('quran_scripture_links')->insert([
                        'verse_id' => $verse->id,
                        'bible_verse_id' => $bibleId,
                        'torah_section_id' => null,
                        'status' => 'approved',
                    ]);
                    break;

                case 'torah':
                    if (\Illuminate\Support\Facades\Schema::hasColumn('torah_sections', 'section_reference')) {
                        $torah = \Illuminate\Support\Facades\DB::table('torah_sections')
                            ->where('section_reference', $request->title)
                            ->first();

                        if (!$torah) {
                            $torahId = \Illuminate\Support\Facades\DB::table('torah_sections')->insertGetId([
                                'section_reference' => $request->title,
                                'text' => $request->content,
                            ]);
                        } else {
                            $torahId = $torah->id;
                        }
                    } else {
                        // Parse book, chapter, verse
                        $torahBook = 'Genesis';
                        $torahChapter = 1;
                        $torahVerseNum = 1;
                        if (preg_match('/^(.*?)\s+(\d+):(\d+)$/', trim($request->title), $matches)) {
                            $torahBook = $matches[1];
                            $torahChapter = (int) $matches[2];
                            $torahVerseNum = (int) $matches[3];
                        }

                        $torah = \Illuminate\Support\Facades\DB::table('torah_sections')
                            ->where('book', $torahBook)
                            ->where('chapter', $torahChapter)
                            ->where('verse_number', $torahVerseNum)
                            ->first();

                        if (!$torah) {
                            $torahId = \Illuminate\Support\Facades\DB::table('torah_sections')->insertGetId([
                                'book' => $torahBook,
                                'chapter' => $torahChapter,
                                'verse_number' => $torahVerseNum,
                                'text_english' => $request->content,
                            ]);
                        } else {
                            $torahId = $torah->id;
                        }
                    }

                    // Check for duplicate pivot link mapping
                    $exists = \Illuminate\Support\Facades\DB::table('quran_scripture_links')
                        ->where('verse_id', $verse->id)
                        ->where('torah_section_id', $torahId)
                        ->exists();

                    if ($exists) {
                        return back()->with('error', 'This Torah connection already exists for this verse. Data duplication was prevented.');
                    }

                    \Illuminate\Support\Facades\DB::table('quran_scripture_links')->insert([
                        'verse_id' => $verse->id,
                        'bible_verse_id' => null,
                        'torah_section_id' => $torahId,
                        'status' => 'approved',
                    ]);
                    break;
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to map connection link: ' . $e->getMessage());
        }

        return back()->with('success', 'New connection link was successfully mapped. Data duplication check verified.');
    }

    /**
     * Delete/retract an approved or pending analysis.
     */
    public function destroyAnalysis($id)
    {
        $analysis = QuranicLensAnalysis::findOrFail((int) $id);
        $analysis->delete();

        return back()->with('success', 'Analysis record has been deleted successfully.');
    }

    /**
     * Store a new science category.
     */
    public function storeScienceCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|unique:science_categories,slug|max:100',
            'emoji' => 'nullable|string|max:10',
            'mapped_fields' => 'required|string|max:500',
        ]);

        \App\Models\ScienceCategory::create([
            'name' => $request->name,
            'slug' => \Illuminate\Support\Str::slug($request->slug, '_'),
            'emoji' => $request->emoji,
            'mapped_fields' => $request->mapped_fields,
        ]);

        return back()->with('success', 'Science category created successfully.');
    }

    /**
     * Update an existing science category.
     */
    public function updateScienceCategory(Request $request, $id)
    {
        $cat = \App\Models\ScienceCategory::findOrFail((int) $id);
        $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:science_categories,slug,' . $cat->id,
            'emoji' => 'nullable|string|max:10',
            'mapped_fields' => 'required|string|max:500',
        ]);

        $cat->update([
            'name' => $request->name,
            'slug' => \Illuminate\Support\Str::slug($request->slug, '_'),
            'emoji' => $request->emoji,
            'mapped_fields' => $request->mapped_fields,
        ]);

        return back()->with('success', 'Science category updated successfully.');
    }

    /**
     * Delete a science category.
     */
    public function destroyScienceCategory($id)
    {
        $cat = \App\Models\ScienceCategory::findOrFail((int) $id);
        $cat->delete();

        return back()->with('success', 'Science category deleted successfully.');
    }

    /**
     * Approve a pending connection link.
     */
    public function approveConnection(Request $request, $table, $id)
    {
        $id = (int) $id;
        $validTables = ['quran_science_links', 'quran_seerat_links', 'quran_hadith_links', 'quran_history_links', 'quran_scripture_links'];

        if (!in_array($table, $validTables)) {
            return back()->with('error', 'Invalid connection link table.');
        }

        \Illuminate\Support\Facades\DB::table($table)
            ->where('id', $id)
            ->update([
                'status' => 'approved'
            ]);

        return back()->with('success', 'Connection link has been approved and published.');
    }

    /**
     * Reject/Delete a pending connection link.
     */
    public function rejectConnection(Request $request, $table, $id)
    {
        $id = (int) $id;
        $validTables = ['quran_science_links', 'quran_seerat_links', 'quran_hadith_links', 'quran_history_links', 'quran_scripture_links'];

        if (!in_array($table, $validTables)) {
            return back()->with('error', 'Invalid connection link table.');
        }

        \Illuminate\Support\Facades\DB::table($table)
            ->where('id', $id)
            ->delete();

        return back()->with('success', 'Connection link has been rejected and deleted.');
    }
}

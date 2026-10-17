<?php

namespace App\Http\Controllers;

use App\Models\QuranResearchContribution;
use App\Models\QuranResearchReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuranResearchContributionController extends Controller
{
    /**
     * Normalize Surah & Verse scope inputs (single verse, verse range, full surah, surah range, or multiple surahs).
     */
    protected function extractScopeData(Request $request): array
    {
        $scopeMode = $request->input('scope_mode', 'single'); // 'single', 'verse_range', 'full_surah', 'surah_range', 'multiple_surahs'
        $surahNumber = (int) ($request->input('surah_number') ?: 1);
        $endSurahNumber = $request->filled('end_surah_number') ? (int) $request->input('end_surah_number') : null;
        $verseNumber = (int) ($request->input('verse_number') ?: 1);
        $endVerseNumber = $request->filled('end_verse_number') ? (int) $request->input('end_verse_number') : null;
        $isFullSurah = $request->boolean('is_full_surah') || $scopeMode === 'full_surah';

        // Parse multiple surahs from array or comma/hyphen separated string (e.g., "1, 2, 18" or "112-114")
        $surahNumbers = [];
        if ($request->has('surah_numbers')) {
            $raw = $request->input('surah_numbers');
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $raw = $decoded;
                } else {
                    $raw = preg_split('/[\s,;]+/', trim($raw));
                }
            }
            if (is_array($raw)) {
                foreach ($raw as $part) {
                    $part = trim((string) $part);
                    if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $part, $m)) {
                        $s = max(1, min(114, (int) $m[1]));
                        $e = max(1, min(114, (int) $m[2]));
                        for ($i = min($s, $e); $i <= max($s, $e); $i++) {
                            $surahNumbers[] = $i;
                        }
                    } elseif (is_numeric($part)) {
                        $num = (int) $part;
                        if ($num >= 1 && $num <= 114) {
                            $surahNumbers[] = $num;
                        }
                    }
                }
            }
        }

        $surahNumbers = array_values(array_unique($surahNumbers));
        sort($surahNumbers);

        if ($scopeMode === 'multiple_surahs' && !empty($surahNumbers)) {
            $surahNumber = $surahNumbers[0];
            $isFullSurah = true;
            $verseNumber = 1;
            $endVerseNumber = null;
        } elseif ($scopeMode === 'surah_range') {
            if ($endSurahNumber && $endSurahNumber < $surahNumber) {
                [$surahNumber, $endSurahNumber] = [$endSurahNumber, $surahNumber];
            }
            if ($endSurahNumber && $endSurahNumber > $surahNumber) {
                $surahNumbers = range($surahNumber, $endSurahNumber);
            }
            if ($isFullSurah) {
                $verseNumber = 1;
                $endVerseNumber = null;
            }
        } elseif ($isFullSurah) {
            $verseNumber = 1;
            // If end_verse_number wasn't explicitly set, look up the surah's total verse count if available
            if (!$endVerseNumber) {
                $vCount = DB::table('surahs')->where('number', $surahNumber)->value('verse_count');
                if ($vCount) {
                    $endVerseNumber = (int) $vCount;
                }
            }
        } elseif ($scopeMode === 'verse_range' && $endVerseNumber && $endVerseNumber < $verseNumber) {
            [$verseNumber, $endVerseNumber] = [$endVerseNumber, $verseNumber];
        }

        if (empty($surahNumbers)) {
            if ($endSurahNumber && $endSurahNumber > $surahNumber) {
                $surahNumbers = range($surahNumber, $endSurahNumber);
            } else {
                $surahNumbers = [$surahNumber];
            }
        }

        return [
            'surah_number' => max(1, min(114, $surahNumber)),
            'end_surah_number' => ($endSurahNumber && $endSurahNumber > $surahNumber) ? min(114, $endSurahNumber) : null,
            'surah_numbers' => $surahNumbers,
            'verse_number' => max(1, $verseNumber),
            'end_verse_number' => ($endVerseNumber && $endVerseNumber >= $verseNumber) ? $endVerseNumber : null,
            'is_full_surah' => (bool) $isFullSurah,
        ];
    }

    /**
     * Display a listing of research contributions (videos, etc.).
     */
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'published'); // 'published', 'pending'
        $category = $request->input('category', 'all');
        $surahNumber = $request->input('surah_number');
        $search = $request->input('search');

        $query = QuranResearchContribution::with(['user', 'reviews.user'])
            ->withCount([
                'reviews as approvals_count' => function ($q) {
                    $q->where('vote', 'approve');
                },
                'reviews as downvotes_count' => function ($q) {
                    $q->where('vote', 'downvote');
                }
            ]);

        if ($tab === 'pending') {
            if (!Auth::check()) {
                return redirect()->route('login')->with('info', 'Please log in to inspect and verify pending contributions.');
            }
            $query->pending();
        } else {
            $query->published();
        }

        if ($category !== 'all' && !empty($category)) {
            $query->where('category', $category);
        }

        if ($surahNumber && is_numeric($surahNumber)) {
            $query->forSurah((int) $surahNumber);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $contributions = $query->latest()->paginate(12)->withQueryString();
        $surahs = DB::table('surahs')->orderBy('number')->get();

        $userReviews = [];
        if (Auth::check()) {
            $userReviews = QuranResearchReview::where('user_id', Auth::id())
                ->whereIn('contribution_id', $contributions->pluck('id'))
                ->get()
                ->keyBy('contribution_id');
        }

        $pendingCount = Auth::check() ? QuranResearchContribution::pending()->count() : 0;
        $publishedCount = QuranResearchContribution::published()->count();
        $researchCategories = \App\Models\Category::getQuranResearchCategories();

        return view('quran-research.index', compact('contributions', 'surahs', 'tab', 'category', 'surahNumber', 'search', 'userReviews', 'pendingCount', 'publishedCount', 'researchCategories'));
    }

    /**
     * Show form to submit research contribution (video or paper/insight).
     */
    public function create()
    {
        $surahs = DB::table('surahs')->orderBy('number')->get();
        $researchCategories = \App\Models\Category::getQuranResearchCategories();
        return view('quran-research.create', compact('surahs', 'researchCategories'));
    }

    /**
     * Store a newly created research contribution.
     */
    public function store(Request $request)
    {
        if ($request->filled('custom_category')) {
            $request->merge(['category' => trim($request->input('custom_category'))]);
        } elseif ($request->input('category') === '__new__' && $request->filled('new_category')) {
            $request->merge(['category' => trim($request->input('new_category'))]);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'surah_number' => 'nullable|integer|min:1|max:114',
            'end_surah_number' => 'nullable|integer|min:1|max:114',
            'surah_numbers' => 'nullable',
            'verse_number' => 'nullable|integer|min:1',
            'end_verse_number' => 'nullable|integer|min:1',
            'is_full_surah' => 'nullable|boolean',
            'category' => 'required|string|max:100',
            'video_url' => 'nullable|url|max:1000',
            'description' => 'nullable|string|max:5000',
            'submitter_name' => 'nullable|string|max:150',
            'submitter_email' => 'nullable|email|max:150',
        ]);

        $submitterName = Auth::check() ? Auth::user()->name : ($request->submitter_name ?: 'Community Contributor');
        $catObj = \App\Models\Category::ensureCategory(
            $request->category,
            \App\Models\Category::TYPE_QURAN_RESEARCH,
            $request->input('category_emoji'),
            null,
            Auth::id(),
            $submitterName
        );
        $resolvedCategory = $catObj ? $catObj->slug : \Illuminate\Support\Str::slug($request->category);

        if ($resolvedCategory === 'video' && empty($request->video_url)) {
            return back()->withInput()->withErrors(['video_url' => 'A valid video URL is required for video contributions.']);
        }

        $scopeData = $this->extractScopeData($request);
        $parsed = QuranResearchContribution::parseVideoUrl($request->video_url);

        $contribution = QuranResearchContribution::create(array_merge($scopeData, [
            'user_id' => Auth::id(),
            'submitter_name' => $submitterName,
            'submitter_email' => Auth::check() ? Auth::user()->email : $request->submitter_email,
            'category' => $resolvedCategory,
            'title' => $request->title,
            'description' => $request->description,
            'video_url' => $request->video_url,
            'video_platform' => $parsed['platform'],
            'video_id' => $parsed['id'],
            'status' => 'pending',
            'approvals_count' => 0,
            'downvotes_count' => 0,
        ]));

        return redirect()->route('welcome', ['tab' => Auth::check() ? 'pending' : 'videos'])
            ->with('success', 'Alhamdulillah! Your contribution has been submitted for peer verification. 5 community approvals will publish it!');
    }

    /**
     * Review / Verify / Vote on a contribution.
     */
    public function review(Request $request, $id)
    {
        $request->validate([
            'vote' => 'required|in:approve,downvote',
            'feedback' => 'nullable|string|max:2000',
        ]);

        $contribution = QuranResearchContribution::findOrFail($id);

        if ((int) $contribution->user_id === (int) Auth::id()) {
            return back()->withErrors(['vote' => 'You cannot vote on your own contribution.']);
        }

        QuranResearchReview::updateOrCreate(
            [
                'contribution_id' => $contribution->id,
                'user_id' => Auth::id(),
            ],
            [
                'vote' => $request->vote,
                'feedback' => $request->feedback,
            ]
        );

        $contribution->recalculateStatus();

        $actionWord = $request->vote === 'approve' ? 'approved' : 'downvoted';
        $statusMessage = $contribution->status === 'published'
            ? 'Item has reached 5 approvals and is now PUBLISHED!'
            : "Feedback recorded. (Current approvals: {$contribution->approvals_count}, Downvotes: {$contribution->downvotes_count})";

        return back()->with('success', "You successfully {$actionWord} this contribution. {$statusMessage}");
    }

    /**
     * API: List published or pending research contributions.
     */
    public function apiIndex(Request $request)
    {
        $tab = $request->input('tab', 'published');
        $category = $request->input('category', 'all');
        $surahNumber = $request->input('surah_number');
        $search = $request->input('search');

        $query = QuranResearchContribution::with(['user:id,name,display_name', 'reviews.user:id,name,display_name'])
            ->withCount([
                'reviews as approvals_count' => function ($q) {
                    $q->where('vote', 'approve');
                },
                'reviews as downvotes_count' => function ($q) {
                    $q->where('vote', 'downvote');
                }
            ]);

        $user = Auth::guard('sanctum')->user() ?: Auth::user();

        if ($tab === 'pending') {
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated. Log in to view pending contributions.'
                ], 401);
            }
            $query->pending();
        } else {
            $query->published();
        }

        if ($category !== 'all' && !empty($category)) {
            $query->where('category', $category);
        }

        if ($surahNumber && is_numeric($surahNumber)) {
            $query->forSurah((int) $surahNumber);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 50);
        $contributions = $query->latest()->paginate($perPage);

        $items = collect($contributions->items())->map(function ($item) {
            $item->makeHidden(['submitter_email']);
            return $item;
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => $items,
            'current_page' => $contributions->currentPage(),
            'last_page' => $contributions->lastPage(),
            'total' => $contributions->total(),
        ]);
    }

    /**
     * API: Submit a research contribution.
     */
    public function apiStore(Request $request)
    {
        $user = Auth::guard('sanctum')->user() ?: Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Please log in to submit a contribution.',
            ], 401);
        }

        if ($request->filled('custom_category')) {
            $request->merge(['category' => trim($request->input('custom_category'))]);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'surah_number' => 'nullable|integer|min:1|max:114',
            'end_surah_number' => 'nullable|integer|min:1|max:114',
            'surah_numbers' => 'nullable',
            'verse_number' => 'nullable|integer|min:1',
            'end_verse_number' => 'nullable|integer|min:1',
            'is_full_surah' => 'nullable|boolean',
            'category' => 'required|string|max:100',
            'video_url' => 'nullable|url|max:1000',
            'description' => 'nullable|string|max:5000',
            'submitter_name' => 'nullable|string|max:150',
            'submitter_email' => 'nullable|email|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $submitterName = $user->name;
        $catObj = \App\Models\Category::ensureCategory(
            $request->category,
            \App\Models\Category::TYPE_QURAN_RESEARCH,
            $request->input('category_emoji'),
            null,
            $user->id,
            $submitterName
        );
        $resolvedCategory = $catObj ? $catObj->slug : \Illuminate\Support\Str::slug($request->category);

        if ($resolvedCategory === 'video' && empty($request->video_url)) {
            return response()->json(['status' => 'error', 'message' => 'Video URL is required for video contributions.'], 422);
        }

        $scopeData = $this->extractScopeData($request);
        $parsed = QuranResearchContribution::parseVideoUrl($request->video_url);

        $contribution = QuranResearchContribution::create(array_merge($scopeData, [
            'user_id' => $user->id,
            'submitter_name' => $submitterName,
            'submitter_email' => $user->email,
            'category' => $resolvedCategory,
            'title' => $request->title,
            'description' => $request->description,
            'video_url' => $request->video_url,
            'video_platform' => $parsed['platform'],
            'video_id' => $parsed['id'],
            'status' => 'pending',
            'approvals_count' => 0,
            'downvotes_count' => 0,
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'JazakAllah Khair! Research contribution submitted. 5 peer approvals will publish it.',
            'data' => $contribution
        ], 201);
    }

    /**
     * API: Review / Verify / Vote on a contribution.
     */
    public function apiReview(Request $request, $id)
    {
        $user = Auth::guard('sanctum')->user() ?: Auth::user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'vote' => 'required|in:approve,downvote',
            'feedback' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $contribution = QuranResearchContribution::findOrFail($id);

        if ((int) $contribution->user_id === (int) $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot vote on your own contribution.',
            ], 422);
        }

        QuranResearchReview::updateOrCreate(
            [
                'contribution_id' => $contribution->id,
                'user_id' => $user->id,
            ],
            [
                'vote' => $request->vote,
                'feedback' => $request->feedback,
            ]
        );

        $contribution->recalculateStatus();

        return response()->json([
            'status' => 'success',
            'message' => "Vote recorded. Current status: {$contribution->status}",
            'data' => [
                'id' => $contribution->id,
                'status' => $contribution->status,
                'approvals_count' => $contribution->approvals_count,
                'downvotes_count' => $contribution->downvotes_count,
            ]
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\QuranResearchContribution;
use App\Models\QuranResearchReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    /**
     * Display a listing of video & research contributions with review queues, approval filters, and search.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        $search = $request->input('search');
        $category = $request->input('category');
        $surahNumber = $request->input('surah_number');

        // Summary counts
        $pendingCount = QuranResearchContribution::pending()->count();
        $publishedCount = QuranResearchContribution::published()->count();
        $rejectedCount = QuranResearchContribution::rejected()->count();
        $allCount = QuranResearchContribution::count();

        // If no status specified and there are pending items, default to pending for admin triage
        if (!$request->has('status') && $pendingCount > 0) {
            $status = 'pending';
        }

        $query = QuranResearchContribution::with(['user', 'reviews.user'])->latest();

        if ($status !== 'all' && in_array($status, ['pending', 'published', 'rejected'])) {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->search($search);
        }

        if (!empty($category) && $category !== 'all') {
            $query->where('category', $category);
        }

        if (!empty($surahNumber) && is_numeric($surahNumber)) {
            $query->forSurah((int) $surahNumber);
        }

        $videos = $query->paginate(15)->withQueryString();

        $categories = Category::getQuranResearchCategories();
        $surahs = DB::table('surahs')->orderBy('number')->get();

        return view('admin.videos.index', compact(
            'videos',
            'status',
            'search',
            'category',
            'surahNumber',
            'categories',
            'surahs',
            'pendingCount',
            'publishedCount',
            'rejectedCount',
            'allCount'
        ));
    }

    /**
     * Show form to add a video contribution directly from the admin panel.
     */
    public function create()
    {
        $categories = Category::getQuranResearchCategories();
        $surahs = DB::table('surahs')->orderBy('number')->get();

        return view('admin.videos.create', compact('categories', 'surahs'));
    }

    /**
     * Store a newly created video contribution directly as an admin (default published).
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
            'scope_mode' => 'nullable|string|in:single,verse_range,full_surah,surah_range,multiple_surahs',
            'category' => 'required|string|max:100',
            'video_url' => 'required|url|max:1000',
            'description' => 'nullable|string|max:5000',
            'submitter_name' => 'nullable|string|max:150',
            'status' => 'required|in:published,pending,rejected',
        ]);

        $submitterName = $request->filled('submitter_name')
            ? $request->input('submitter_name')
            : (Auth::check() ? Auth::user()->name : 'Administrator');

        $catObj = Category::ensureCategory(
            $request->category,
            Category::TYPE_QURAN_RESEARCH,
            $request->input('category_emoji'),
            null,
            Auth::id(),
            $submitterName
        );
        $resolvedCategory = $catObj ? $catObj->slug : Str::slug($request->category);

        $scopeData = $this->extractScopeData($request);
        $parsed = QuranResearchContribution::parseVideoUrl($request->video_url);

        $status = $request->input('status', 'published');
        $isPublished = $status === 'published';

        $contribution = QuranResearchContribution::create(array_merge($scopeData, [
            'user_id' => Auth::id(),
            'submitter_name' => $submitterName,
            'submitter_email' => Auth::check() ? Auth::user()->email : null,
            'category' => $resolvedCategory,
            'title' => $request->title,
            'description' => $request->description,
            'video_url' => $request->video_url,
            'video_platform' => $parsed['platform'],
            'video_id' => $parsed['id'],
            'status' => $status,
            'approvals_count' => $isPublished ? 5 : 0,
            'downvotes_count' => 0,
            'published_at' => $isPublished ? now() : null,
        ]));

        if ($isPublished && Auth::check()) {
            QuranResearchReview::updateOrCreate(
                [
                    'contribution_id' => $contribution->id,
                    'user_id' => Auth::id(),
                ],
                [
                    'vote' => 'approve',
                    'feedback' => 'Directly published by administrator.',
                ]
            );
        }

        return redirect()->route('admin.videos.index', ['status' => $status])
            ->with('success', "Video '{$contribution->title}' has been successfully added " . ($isPublished ? 'and PUBLISHED!' : 'as pending review.'));
    }

    /**
     * Show form to edit an existing video contribution.
     */
    public function edit(int $id)
    {
        $video = QuranResearchContribution::with(['reviews.user'])->findOrFail($id);
        $categories = Category::getQuranResearchCategories();
        $surahs = DB::table('surahs')->orderBy('number')->get();

        return view('admin.videos.edit', compact('video', 'categories', 'surahs'));
    }

    /**
     * Update an existing video contribution.
     */
    public function update(Request $request, int $id)
    {
        $video = QuranResearchContribution::findOrFail($id);

        if ($request->filled('custom_category')) {
            $request->merge(['category' => trim($request->input('custom_category'))]);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'surah_number' => 'nullable|integer|min:1|max:114',
            'end_surah_number' => 'nullable|integer|min:1|max:114',
            'surah_numbers' => 'nullable',
            'verse_number' => 'nullable|integer|min:1',
            'end_verse_number' => 'nullable|integer|min:1',
            'is_full_surah' => 'nullable|boolean',
            'scope_mode' => 'nullable|string|in:single,verse_range,full_surah,surah_range,multiple_surahs',
            'category' => 'required|string|max:100',
            'video_url' => 'required|url|max:1000',
            'description' => 'nullable|string|max:5000',
            'submitter_name' => 'nullable|string|max:150',
            'status' => 'required|in:published,pending,rejected',
        ]);

        $catObj = Category::ensureCategory(
            $request->category,
            Category::TYPE_QURAN_RESEARCH,
            $request->input('category_emoji'),
            null,
            Auth::id(),
            $request->input('submitter_name', $video->submitter_name)
        );
        $resolvedCategory = $catObj ? $catObj->slug : Str::slug($request->category);

        $scopeData = $this->extractScopeData($request);
        $parsed = QuranResearchContribution::parseVideoUrl($request->video_url);

        $prevStatus = $video->status;
        $newStatus = $request->input('status', 'published');

        $video->fill(array_merge($scopeData, [
            'title' => $request->title,
            'category' => $resolvedCategory,
            'description' => $request->description,
            'video_url' => $request->video_url,
            'video_platform' => $parsed['platform'],
            'video_id' => $parsed['id'],
            'status' => $newStatus,
            'submitter_name' => $request->input('submitter_name', $video->submitter_name),
        ]));

        if ($newStatus === 'published') {
            if (empty($video->published_at)) {
                $video->published_at = now();
            }
            if ($video->approvals_count < 5) {
                $video->approvals_count = max(5, $video->approvals_count);
            }
        } elseif ($newStatus === 'pending' && $prevStatus === 'published') {
            $video->published_at = null;
        }

        $video->save();

        return redirect()->route('admin.videos.index', ['status' => $newStatus])
            ->with('success', "Video '{$video->title}' was successfully updated.");
    }

    /**
     * Instantly approve and publish a video contribution from the backend.
     */
    public function approve(Request $request, int $id)
    {
        $video = QuranResearchContribution::findOrFail($id);
        $video->status = 'published';
        if (empty($video->published_at)) {
            $video->published_at = now();
        }
        if ($video->approvals_count < 5) {
            $video->approvals_count = max(5, $video->approvals_count);
        }
        $video->save();

        // Record admin approval review
        QuranResearchReview::updateOrCreate(
            [
                'contribution_id' => $video->id,
                'user_id' => Auth::id(),
            ],
            [
                'vote' => 'approve',
                'feedback' => $request->input('feedback', 'Verified and approved by administrator from backend.'),
            ]
        );

        return back()->with('success', "Video '{$video->title}' has been APPROVED and is now live on the website and app!");
    }

    /**
     * Reject a video contribution from the backend.
     */
    public function reject(Request $request, int $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $video = QuranResearchContribution::findOrFail($id);
        $video->status = 'rejected';
        $video->save();

        // Record admin rejection review
        QuranResearchReview::updateOrCreate(
            [
                'contribution_id' => $video->id,
                'user_id' => Auth::id(),
            ],
            [
                'vote' => 'downvote',
                'feedback' => $request->input('reason', 'Submission does not meet authenticity or quality guidelines.'),
            ]
        );

        return back()->with('info', "Video '{$video->title}' has been marked as REJECTED.");
    }

    /**
     * Delete a video contribution permanently.
     */
    public function destroy(int $id)
    {
        $video = QuranResearchContribution::findOrFail($id);
        $title = $video->title;
        $video->delete();

        return back()->with('success', "Video '{$title}' was successfully deleted.");
    }

    /**
     * Normalize Surah & Verse scope inputs.
     */
    protected function extractScopeData(Request $request): array
    {
        $scopeMode = $request->input('scope_mode', 'single');
        $surahNumber = (int) ($request->input('surah_number') ?: 1);
        $endSurahNumber = $request->filled('end_surah_number') ? (int) $request->input('end_surah_number') : null;
        $verseNumber = (int) ($request->input('verse_number') ?: 1);
        $endVerseNumber = $request->filled('end_verse_number') ? (int) $request->input('end_verse_number') : null;
        $isFullSurah = $request->boolean('is_full_surah') || $scopeMode === 'full_surah';

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
}

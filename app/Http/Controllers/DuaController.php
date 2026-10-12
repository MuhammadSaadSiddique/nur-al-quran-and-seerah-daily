<?php

namespace App\Http\Controllers;

use App\Models\Dua;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DuaController extends Controller
{
    /**
     * Display a listing of authentic approved Duas.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $category = $request->input('category');
        $sourceType = $request->input('source_type');

        $query = Dua::query()
            ->approved()
            ->search($search)
            ->category($category)
            ->sourceType($sourceType)
            ->orderBy('order')
            ->orderBy('id');

        $duas = $query->paginate(12)->withQueryString();

        // Retrieve categories with counts for approved Duas, merged with active Dua categories
        $duaCounts = Dua::approved()
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        $allCatNames = \App\Models\Category::getDuaCategoryNames();
        foreach (array_keys($duaCounts) as $cName) {
            if (!$allCatNames->contains($cName)) {
                $allCatNames->push($cName);
            }
        }

        $categories = $allCatNames->sort()->values()->map(function ($catName) use ($duaCounts) {
            return (object) [
                'category' => $catName,
                'count' => (int) ($duaCounts[$catName] ?? 0),
            ];
        });

        $totalCount = Dua::approved()->count();
        $quranCount = Dua::approved()->whereIn('source_type', ['quran', 'both'])->count();
        $hadithCount = Dua::approved()->whereIn('source_type', ['hadith', 'both'])->count();

        // Featured Dua (Today's recommended reflection)
        $featuredDua = Dua::approved()
            ->where('is_featured', true)
            ->orderBy('order')
            ->first();

        if ($request->ajax() && $request->has('view_only')) {
            return response()->json([
                'html' => view('duas.partials.cards', compact('duas'))->render(),
                'pagination' => $duas->links()->render(),
                'total' => $duas->total(),
            ]);
        }

        return view('duas.index', compact(
            'duas',
            'categories',
            'totalCount',
            'quranCount',
            'hadithCount',
            'featuredDua',
            'search',
            'category',
            'sourceType'
        ));
    }

    /**
     * Show the form for users to contribute a new Dua.
     */
    public function create()
    {
        $categories = \App\Models\Category::getDuaCategoryNames();
        return view('duas.create', compact('categories'));
    }

    /**
     * Store a user-contributed Dua for admin review.
     */
    public function store(Request $request)
    {
        if ($request->filled('custom_category')) {
            $request->merge(['category' => trim($request->input('custom_category'))]);
        }

        $validated = $this->validateSubmission($request);

        $wordByWord = $this->parseWordByWord($request->input('word_by_word'));

        $dua = new Dua($validated);
        $dua->word_by_word = $wordByWord;
        $dua->status = 'pending';
        $dua->is_featured = false;
        $dua->order = 999;

        if (Auth::check()) {
            $dua->submitted_by_user_id = Auth::id();
            $dua->submitter_name = Auth::user()->name;
            $dua->submitter_email = Auth::user()->email;
        } else {
            $dua->submitter_name = $request->input('submitter_name');
            $dua->submitter_email = $request->input('submitter_email');
        }

        $dua->save();

        \App\Models\Category::ensureCategory(
            $dua->category,
            \App\Models\Category::TYPE_DUA,
            null,
            null,
            $dua->submitted_by_user_id,
            $dua->submitter_name
        );

        return redirect()->route('duas.index')
            ->with('success', 'JazakAllah Khair! Your Dua has been submitted for administrative review. It will become visible once verified by an admin.');
    }

    /**
     * Display the specified Dua for detailed contemplation and meaning breakdown.
     */
    public function show(string $slug)
    {
        // Admins can preview pending/rejected Duas, regular users only see approved
        $query = Dua::where('slug', $slug);
        if (!Auth::check() || !Auth::user()->is_admin) {
            $query->approved();
        }
        $dua = $query->firstOrFail();

        // Related duas in the same category
        $relatedDuas = Dua::approved()
            ->where('category', $dua->category)
            ->where('id', '!=', $dua->id)
            ->inRandomOrder()
            ->take(3)
            ->get();

        // Next & previous duas
        $prevDua = Dua::approved()->where('id', '<', $dua->id)->orderBy('id', 'desc')->first();
        $nextDua = Dua::approved()->where('id', '>', $dua->id)->orderBy('id', 'asc')->first();

        return view('duas.show', compact('dua', 'relatedDuas', 'prevDua', 'nextDua'));
    }

    /**
     * Daily featured Dua redirection / quick view.
     */
    public function daily()
    {
        $count = Dua::approved()->count();
        if ($count === 0) {
            return redirect()->route('duas.index');
        }

        // Deterministic daily index based on day of year
        $dayOfYear = (int) date('z');
        $offset = $dayOfYear % $count;

        $dailyDua = Dua::approved()->orderBy('order')->orderBy('id')->skip($offset)->first();

        if (!$dailyDua) {
            $dailyDua = Dua::approved()->first();
        }

        return redirect()->route('duas.show', $dailyDua->slug);
    }

    /**
     * API: List approved Duas for mobile app and external clients.
     */
    public function apiIndex(Request $request)
    {
        $search = $request->input('search');
        $category = $request->input('category');
        $sourceType = $request->input('source_type');

        $duas = Dua::query()
            ->approved()
            ->search($search)
            ->category($category)
            ->sourceType($sourceType)
            ->orderBy('order')
            ->orderBy('id')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => $duas,
        ]);
    }

    /**
     * API: Single Dua details (approved only).
     */
    public function apiShow(string $idOrSlug)
    {
        $query = is_numeric($idOrSlug) ? Dua::where('id', $idOrSlug) : Dua::where('slug', $idOrSlug);
        $dua = $query->approved()->first();

        if (!$dua) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dua not found or pending verification',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $dua,
        ]);
    }

    /**
     * API: Submit a Dua from mobile app / API client.
     */
    public function apiStore(Request $request)
    {
        if ($request->filled('custom_category')) {
            $request->merge(['category' => trim($request->input('custom_category'))]);
        }

        $validated = $this->validateSubmission($request);

        $wordByWord = $this->parseWordByWord($request->input('word_by_word'));

        $dua = new Dua($validated);
        $dua->word_by_word = $wordByWord;
        $dua->status = 'pending';
        $dua->is_featured = false;
        $dua->order = 999;

        if (Auth::check()) {
            $dua->submitted_by_user_id = Auth::id();
            $dua->submitter_name = Auth::user()->name;
            $dua->submitter_email = Auth::user()->email;
        } else {
            $dua->submitter_name = $request->input('submitter_name', 'Mobile App Contributor');
            $dua->submitter_email = $request->input('submitter_email');
        }

        $dua->save();

        \App\Models\Category::ensureCategory(
            $dua->category,
            \App\Models\Category::TYPE_DUA,
            null,
            null,
            $dua->submitted_by_user_id,
            $dua->submitter_name
        );

        return response()->json([
            'status' => 'success',
            'message' => 'JazakAllah Khair! Dua submitted successfully and is queued for administrative review.',
            'data' => [
                'id' => $dua->id,
                'title' => $dua->title,
                'status' => $dua->status,
            ],
        ], 201);
    }

    /**
     * Shared validation for user submissions.
     */
    protected function validateSubmission(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'arabic_text' => 'required|string',
            'transliteration' => 'nullable|string',
            'translation_en' => 'required|string',
            'translation_ur' => 'nullable|string',
            'source_type' => 'required|in:quran,hadith,both',
            'quran_reference' => 'nullable|string|max:255',
            'surah_number' => 'nullable|integer|min:1|max:114',
            'verse_number' => 'nullable|integer|min:1|max:286',
            'hadith_reference' => 'nullable|string|max:255',
            'hadith_book' => 'nullable|string|max:100',
            'hadith_number' => 'nullable|string|max:50',
            'hadith_grading' => 'nullable|string|max:50',
            'meaning_explanation' => 'nullable|string',
            'benefits_and_virtues' => 'nullable|string',
            'when_to_recite' => 'nullable|string|max:255',
            'repeat_count' => 'nullable|integer|min:1|max:1000',
            'submitter_name' => 'nullable|string|max:100',
            'submitter_email' => 'nullable|email|max:150',
        ]);
    }

    /**
     * Parse word-by-word rows.
     */
    protected function parseWordByWord($input): ?array
    {
        if (empty($input)) {
            return null;
        }

        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $input = $decoded;
            } else {
                return null;
            }
        }

        if (is_array($input)) {
            $filtered = [];
            foreach ($input as $row) {
                if (is_array($row) && (!empty($row['arabic']) || !empty($row['meaning_en']))) {
                    $filtered[] = [
                        'arabic' => trim($row['arabic'] ?? ''),
                        'transliteration' => trim($row['transliteration'] ?? ''),
                        'meaning_en' => trim($row['meaning_en'] ?? ''),
                        'meaning_ur' => trim($row['meaning_ur'] ?? ''),
                    ];
                }
            }
            return !empty($filtered) ? $filtered : null;
        }

        return null;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dua;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DuaController extends Controller
{
    /**
     * Display a listing of Duas with review queues, approval filters, and search.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        $search = $request->input('search');
        $category = $request->input('category');
        $sourceType = $request->input('source_type');

        // Summary counts
        $pendingCount = Dua::pending()->count();
        $approvedCount = Dua::approved()->count();
        $rejectedCount = Dua::rejected()->count();
        $allCount = Dua::count();

        // If no status specified and there are pending items, default to pending for admin convenience
        if (!$request->has('status') && $pendingCount > 0) {
            $status = 'pending';
        }

        $query = Dua::with(['submitter', 'reviewer'])->latest();

        if ($status !== 'all' && in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->search($search);
        }

        if (!empty($category) && $category !== 'all') {
            $query->where('category', $category);
        }

        if (!empty($sourceType) && $sourceType !== 'all') {
            $query->sourceType($sourceType);
        }

        $duas = $query->paginate(15)->withQueryString();

        $categories = \App\Models\Category::getDuaCategoryNames()
            ->merge(Dua::distinct()->whereNotNull('category')->pluck('category'))
            ->unique()->sort()->values();

        return view('admin.duas.index', compact(
            'duas',
            'status',
            'search',
            'category',
            'sourceType',
            'categories',
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'allCount'
        ));
    }

    /**
     * Show form to create an authentic Dua directly as an admin.
     */
    public function create()
    {
        $categories = \App\Models\Category::getDuaCategoryNames()
            ->merge(Dua::distinct()->whereNotNull('category')->pluck('category'))
            ->unique()->sort()->values();
        return view('admin.duas.create', compact('categories'));
    }

    /**
     * Store a newly created Dua directly with approved status.
     */
    public function store(Request $request)
    {
        if ($request->filled('custom_category')) {
            $request->merge(['category' => trim($request->input('custom_category'))]);
        }

        $validated = $this->validateDuaRequest($request);

        $wordByWord = $this->parseWordByWord($request->input('word_by_word'));

        $dua = new Dua($validated);
        $dua->word_by_word = $wordByWord;
        $dua->status = $request->input('status', 'approved');
        $dua->is_featured = $request->boolean('is_featured', false);
        $dua->repeat_count = (int) $request->input('repeat_count', 1);
        $dua->order = (int) $request->input('order', 0);
        $dua->reviewed_by = Auth::id();
        $dua->reviewed_at = now();
        $dua->save();

        \App\Models\Category::ensureCategory(
            $dua->category,
            \App\Models\Category::TYPE_DUA,
            null,
            null,
            Auth::id(),
            optional(Auth::user())->name ?: 'Admin'
        );

        return redirect()->route('admin.duas.index')
            ->with('success', "Dua '{$dua->title}' was successfully created and published.");
    }

    /**
     * Show form to edit / review a Dua.
     */
    public function edit(int $id)
    {
        $dua = Dua::with(['submitter', 'reviewer'])->findOrFail($id);
        $categories = \App\Models\Category::getDuaCategoryNames()
            ->merge(Dua::distinct()->whereNotNull('category')->pluck('category'))
            ->unique()->sort()->values();

        return view('admin.duas.edit', compact('dua', 'categories'));
    }

    /**
     * Update an existing Dua and optionally adjust its approval status.
     */
    public function update(Request $request, int $id)
    {
        if ($request->filled('custom_category')) {
            $request->merge(['category' => trim($request->input('custom_category'))]);
        }

        $dua = Dua::findOrFail($id);
        $validated = $this->validateDuaRequest($request, $dua->id);

        $wordByWord = $this->parseWordByWord($request->input('word_by_word'));

        $oldStatus = $dua->status;
        $dua->fill($validated);
        $dua->word_by_word = $wordByWord;
        $dua->is_featured = $request->boolean('is_featured', false);
        $dua->repeat_count = (int) $request->input('repeat_count', 1);
        $dua->order = (int) $request->input('order', 0);
        $dua->admin_notes = $request->input('admin_notes');

        if ($request->has('status')) {
            $dua->status = $request->input('status');
            if ($oldStatus !== $dua->status) {
                $dua->reviewed_by = Auth::id();
                $dua->reviewed_at = now();
            }
        }

        $dua->save();

        \App\Models\Category::ensureCategory(
            $dua->category,
            \App\Models\Category::TYPE_DUA,
            null,
            null,
            Auth::id(),
            optional(Auth::user())->name ?: 'Admin'
        );

        return redirect()->route('admin.duas.index')
            ->with('success', "Dua '{$dua->title}' was updated successfully.");
    }

    /**
     * Quick-approve a pending or rejected Dua.
     */
    public function approve(Request $request, int $id)
    {
        $dua = Dua::findOrFail($id);
        $dua->status = 'approved';
        $dua->reviewed_by = Auth::id();
        $dua->reviewed_at = now();
        if ($request->filled('admin_notes')) {
            $dua->admin_notes = $request->input('admin_notes');
        }
        $dua->save();

        return back()->with('success', "Dua '{$dua->title}' has been APPROVED and is now live on the website and mobile app.");
    }

    /**
     * Reject a submitted Dua with optional admin feedback.
     */
    public function reject(Request $request, int $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $dua = Dua::findOrFail($id);
        $dua->status = 'rejected';
        $dua->reviewed_by = Auth::id();
        $dua->reviewed_at = now();
        $dua->admin_notes = $request->input('reason', 'Submission did not meet authenticity or citation guidelines.');
        $dua->save();

        return back()->with('info', "Dua '{$dua->title}' has been marked as REJECTED.");
    }

    /**
     * Delete a Dua permanently.
     */
    public function destroy(int $id)
    {
        $dua = Dua::findOrFail($id);
        $title = $dua->title;
        $dua->delete();

        return back()->with('success', "Dua '{$title}' was permanently deleted.");
    }

    /**
     * Validation rules for Dua creation and updating.
     */
    protected function validateDuaRequest(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'arabic_text' => 'required|string',
            'transliteration' => 'nullable|string',
            'translation_en' => 'required|string',
            'translation_ur' => 'nullable|string',
            'meaning_explanation' => 'nullable|string',
            'benefits_and_virtues' => 'nullable|string',
            'when_to_recite' => 'nullable|string|max:255',
            'repeat_count' => 'nullable|integer|min:1|max:1000',
            'source_type' => 'required|in:quran,hadith,both',
            'quran_reference' => 'nullable|string|max:255',
            'surah_number' => 'nullable|integer|min:1|max:114',
            'verse_number' => 'nullable|integer|min:1|max:286',
            'hadith_reference' => 'nullable|string|max:255',
            'hadith_book' => 'nullable|string|max:100',
            'hadith_number' => 'nullable|string|max:50',
            'hadith_grading' => 'nullable|string|max:50',
            'audio_url' => 'nullable|url|max:255',
            'is_featured' => 'nullable|boolean',
            'order' => 'nullable|integer',
            'status' => 'nullable|in:pending,approved,rejected',
            'admin_notes' => 'nullable|string|max:1000',
        ]);
    }

    /**
     * Parse word-by-word inputs from JSON string or array structure.
     */
    protected function parseWordByWord($input): ?array
    {
        if (empty($input)) {
            return null;
        }

        if (is_array($input)) {
            return array_values(array_filter($input, function ($item) {
                return !empty($item['arabic']) || !empty($item['meaning_en']);
            }));
        }

        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}

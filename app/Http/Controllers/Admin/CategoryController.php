<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Dua;
use App\Models\QuranResearchContribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of all Dua and Quran Research categories for Admin management.
     */
    public function index(Request $request)
    {
        Category::ensureDefaultsSynced();

        $type = $request->input('type', 'all'); // 'all', 'dua', 'quran_research'
        $status = $request->input('status', $request->input('active', 'all')) ?: 'all'; // 'all', 'active', 'inactive'
        $search = $request->input('search');

        $query = Category::with('creator')->orderBy('type')->orderBy('name');

        if (in_array($type, [Category::TYPE_DUA, Category::TYPE_QURAN_RESEARCH])) {
            $query->where('type', $type);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if (!empty($search)) {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                  ->orWhere('slug', 'LIKE', "%{$term}%")
                  ->orWhere('description', 'LIKE', "%{$term}%")
                  ->orWhere('submitter_name', 'LIKE', "%{$term}%");
            });
        }

        $categories = $query->paginate(20)->withQueryString();

        // Compute usage counts for the paginated items
        $duaCounts = [];
        if (Schema::hasTable('duas')) {
            $duaCounts = Dua::select('category', DB::raw('COUNT(*) as total'))
                ->whereNotNull('category')
                ->groupBy('category')
                ->pluck('total', 'category')
                ->toArray();
        }

        $researchCounts = [];
        if (Schema::hasTable('quran_research_contributions')) {
            $researchCounts = QuranResearchContribution::select('category', DB::raw('COUNT(*) as total'))
                ->whereNotNull('category')
                ->groupBy('category')
                ->pluck('total', 'category')
                ->toArray();
        }

        $categories->getCollection()->transform(function (Category $cat) use ($duaCounts, $researchCounts) {
            if ($cat->type === Category::TYPE_DUA) {
                $cat->usage_count = (int) ($duaCounts[$cat->name] ?? 0);
            } else {
                $bySlug = (int) ($researchCounts[$cat->slug] ?? 0);
                $byName = ($cat->name !== $cat->slug) ? (int) ($researchCounts[$cat->name] ?? 0) : 0;
                $cat->usage_count = $bySlug + $byName;
            }
            return $cat;
        });

        // Summary stats
        $totalCount = Category::count();
        $duaCount = Category::where('type', Category::TYPE_DUA)->count();
        $researchCount = Category::where('type', Category::TYPE_QURAN_RESEARCH)->count();
        $activeCount = Category::where('is_active', true)->count();
        $duaCategoriesCount = $duaCount;
        $researchCategoriesCount = $researchCount;
        $inactiveCount = max(0, $totalCount - $activeCount);
        $activeFilter = $request->input('active', $status === 'all' ? '' : $status);
        $duaUsageCounts = $duaCounts;
        $researchUsageCounts = $researchCounts;

        $allCategories = Category::orderBy('type')->orderBy('name')->get();

        return view('admin.categories.index', compact(
            'categories',
            'allCategories',
            'type',
            'status',
            'search',
            'totalCount',
            'duaCount',
            'researchCount',
            'activeCount',
            'duaCategoriesCount',
            'researchCategoriesCount',
            'inactiveCount',
            'activeFilter',
            'duaUsageCounts',
            'researchUsageCounts'
        ));
    }

    /**
     * Store a newly created category by Admin.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:dua,quran_research',
            'slug' => 'nullable|string|max:100',
            'emoji' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $slug = !empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        if (empty($slug)) {
            $slug = 'category-' . time();
        }

        // Check if already exists for this type
        $existing = Category::where('type', $validated['type'])
            ->where(function ($q) use ($validated, $slug) {
                $q->whereRaw('LOWER(name) = ?', [strtolower(trim($validated['name']))])
                  ->orWhere('slug', $slug);
            })
            ->first();

        if ($existing) {
            $existing->update([
                'name' => trim($validated['name']),
                'emoji' => $validated['emoji'] ?? $existing->emoji,
                'description' => $validated['description'] ?? $existing->description,
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
                'status' => 'approved',
            ]);

            return redirect()->back()->with('success', "Category '{$existing->name}' updated and activated.");
        }

        $category = Category::create([
            'name' => trim($validated['name']),
            'slug' => $slug,
            'type' => $validated['type'],
            'emoji' => $validated['emoji'] ?: ($validated['type'] === Category::TYPE_DUA ? '🤲' : '🔬'),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'status' => 'approved',
            'created_by_user_id' => Auth::id(),
            'submitter_name' => Auth::user() ? Auth::user()->name : 'Admin',
        ]);

        $typeLabel = $category->type === Category::TYPE_DUA ? 'Dua' : 'Quran Research';

        return redirect()->back()->with('success', "{$typeLabel} category '{$category->name}' created successfully.");
    }

    /**
     * Update an existing category and sync associated Duas or Research Contributions if renamed.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:dua,quran_research',
            'slug' => 'nullable|string|max:100',
            'emoji' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'is_active' => 'required|boolean',
        ]);

        $oldName = $category->name;
        $oldSlug = $category->slug;
        $oldType = $category->type;

        $newName = trim($validated['name']);
        $newSlug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($newName);
        if (empty($newSlug)) {
            $newSlug = $oldSlug;
        }

        // Ensure unique slug within the target type
        $conflict = Category::where('type', $validated['type'])
            ->where('slug', $newSlug)
            ->where('id', '!=', $category->id)
            ->exists();

        if ($conflict) {
            return redirect()->back()->withErrors(['slug' => "Another {$validated['type']} category with slug '{$newSlug}' already exists."]);
        }

        $category->update([
            'name' => $newName,
            'slug' => $newSlug,
            'type' => $validated['type'],
            'emoji' => $validated['emoji'] ?: $category->emoji,
            'description' => $validated['description'],
            'is_active' => (bool) $validated['is_active'],
            'status' => $validated['is_active'] ? 'approved' : $category->status,
        ]);

        // Cascade rename to existing Duas or Quran Research Contributions
        if ($oldType === Category::TYPE_DUA && $oldName !== $newName && Schema::hasTable('duas')) {
            Dua::where('category', $oldName)->update(['category' => $newName]);
        } elseif ($oldType === Category::TYPE_QURAN_RESEARCH && Schema::hasTable('quran_research_contributions')) {
            if ($oldSlug !== $newSlug) {
                QuranResearchContribution::where('category', $oldSlug)->update(['category' => $newSlug]);
            }
            if ($oldName !== $newName) {
                QuranResearchContribution::where('category', $oldName)->update(['category' => $newSlug]);
            }
        }

        return redirect()->back()->with('success', "Category '{$category->name}' updated successfully.");
    }

    /**
     * Toggle active status of a category.
     */
    public function toggleActive(Category $category)
    {
        $category->is_active = !$category->is_active;
        if ($category->is_active) {
            $category->status = 'approved';
        }
        $category->save();

        $state = $category->is_active ? 'activated' : 'deactivated';

        return redirect()->back()->with('success', "Category '{$category->name}' has been {$state}.");
    }

    /**
     * Merge duplicate categories into a primary category.
     */
    public function merge(Request $request)
    {
        $keepId = $request->input('target_id', $request->input('keep_id'));
        $mergeIds = $request->input('merge_ids');
        if (empty($mergeIds) && $request->filled('source_id')) {
            $mergeIds = [$request->input('source_id')];
        }

        if (empty($keepId) || empty($mergeIds) || !is_array($mergeIds)) {
            return redirect()->back()->withErrors(['merge' => 'Please select both source and target categories to merge.']);
        }

        $keepCategory = Category::findOrFail($keepId);
        $mergeCategories = Category::whereIn('id', $mergeIds)
            ->where('id', '!=', $keepCategory->id)
            ->where('type', $keepCategory->type)
            ->get();

        $deleteSource = $request->boolean('delete_source', true);

        foreach ($mergeCategories as $cat) {
            if ($keepCategory->type === Category::TYPE_DUA && Schema::hasTable('duas')) {
                Dua::where('category', $cat->name)->update(['category' => $keepCategory->name]);
            } elseif ($keepCategory->type === Category::TYPE_QURAN_RESEARCH && Schema::hasTable('quran_research_contributions')) {
                QuranResearchContribution::whereIn('category', [$cat->slug, $cat->name])
                    ->update(['category' => $keepCategory->slug]);
            }
            if ($deleteSource) {
                $cat->delete();
            }
        }

        return redirect()->back()->with('success', count($mergeCategories) . " categories merged into '{$keepCategory->name}'.");
    }

    /**
     * Delete a category.
     */
    public function destroy(Category $category)
    {
        $name = $category->name;
        $category->delete();

        return redirect()->back()->with('success', "Category '{$name}' deleted successfully.");
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Store a newly added Dua or Quran Research category (by User or Admin).
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Please log in to add a category.',
                ], 401);
            }
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string|in:dua,quran_research',
            'emoji' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'submitter_name' => 'nullable|string|max:100',
        ]);

        $userId = Auth::id();
        $submitterName = Auth::user()->name;

        $category = Category::ensureCategory(
            $validated['name'],
            $validated['type'],
            $validated['emoji'] ?? null,
            $validated['description'] ?? null,
            $userId,
            $submitterName
        );

        // Only admins auto-approve; community suggestions stay pending for moderation.
        if ($category && Auth::user()->is_admin && !$category->is_active) {
            $category->is_active = true;
            $category->status = 'approved';
            $category->save();
        } elseif ($category && !Auth::user()->is_admin) {
            $category->is_active = false;
            $category->status = 'pending';
            $category->save();
        }

        $typeLabel = $validated['type'] === Category::TYPE_DUA ? 'Dua' : 'Quran Research';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => "{$typeLabel} category '{$category->name}' added successfully!",
                'data' => $category,
            ], 201);
        }

        return back()->with('success', "Alhamdulillah! {$typeLabel} category '{$category->name}' has been added and is now available.");
    }

    /**
     * API: List active categories (optionally filtered by type=dua or type=quran_research).
     */
    public function apiIndex(Request $request)
    {
        Category::ensureDefaultsSynced();

        $type = $request->input('type');
        $query = Category::active();

        if (in_array($type, [Category::TYPE_DUA, Category::TYPE_QURAN_RESEARCH])) {
            $query->where('type', $type);
        }

        $categories = $query->orderBy('type')->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    /**
     * API: Add a Dua or Quran Research category (User or Admin).
     */
    public function apiStore(Request $request)
    {
        $user = Auth::guard('sanctum')->user() ?: Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Please log in to add a category.',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'type' => 'required|string|in:dua,quran_research',
            'emoji' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'submitter_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $category = Category::ensureCategory(
            $request->input('name'),
            $request->input('type'),
            $request->input('emoji'),
            $request->input('description'),
            $user->id,
            $user->name
        );

        if ($category && $user->is_admin) {
            $category->is_active = true;
            $category->status = 'approved';
            $category->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => $user->is_admin
                ? "Category '{$category->name}' added successfully."
                : "Category '{$category->name}' submitted for review.",
            'data' => $category,
        ], 201);
    }
}

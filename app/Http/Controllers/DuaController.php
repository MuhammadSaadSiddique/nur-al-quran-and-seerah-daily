<?php

namespace App\Http\Controllers;

use App\Models\Dua;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DuaController extends Controller
{
    /**
     * Display a listing of authentic Duas.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $category = $request->input('category');
        $sourceType = $request->input('source_type');

        $query = Dua::query()
            ->search($search)
            ->category($category)
            ->sourceType($sourceType)
            ->orderBy('order')
            ->orderBy('id');

        $duas = $query->paginate(12)->withQueryString();

        // Retrieve categories with counts
        $categories = Dua::select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        $totalCount = Dua::count();
        $quranCount = Dua::whereIn('source_type', ['quran', 'both'])->count();
        $hadithCount = Dua::whereIn('source_type', ['hadith', 'both'])->count();

        // Featured Dua (Today's recommended reflection)
        $featuredDua = Dua::where('is_featured', true)
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
     * Display the specified Dua for detailed contemplation and meaning breakdown.
     */
    public function show(string $slug)
    {
        $dua = Dua::where('slug', $slug)->firstOrFail();

        // Related duas in the same category
        $relatedDuas = Dua::where('category', $dua->category)
            ->where('id', '!=', $dua->id)
            ->inRandomOrder()
            ->take(3)
            ->get();

        // Next & previous duas
        $prevDua = Dua::where('id', '<', $dua->id)->orderBy('id', 'desc')->first();
        $nextDua = Dua::where('id', '>', $dua->id)->orderBy('id', 'asc')->first();

        return view('duas.show', compact('dua', 'relatedDuas', 'prevDua', 'nextDua'));
    }

    /**
     * Daily featured Dua redirection / quick view.
     */
    public function daily()
    {
        $count = Dua::count();
        if ($count === 0) {
            return redirect()->route('duas.index');
        }

        // Deterministic daily index based on day of year
        $dayOfYear = (int) date('z');
        $offset = $dayOfYear % $count;

        $dailyDua = Dua::orderBy('order')->orderBy('id')->skip($offset)->first();

        if (!$dailyDua) {
            $dailyDua = Dua::first();
        }

        return redirect()->route('duas.show', $dailyDua->slug);
    }

    /**
     * API: List Duas for mobile app and external clients.
     */
    public function apiIndex(Request $request)
    {
        $search = $request->input('search');
        $category = $request->input('category');
        $sourceType = $request->input('source_type');

        $duas = Dua::query()
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
     * API: Single Dua details.
     */
    public function apiShow(string $idOrSlug)
    {
        if (is_numeric($idOrSlug)) {
            $dua = Dua::where('id', $idOrSlug)->first();
        } else {
            $dua = Dua::where('slug', $idOrSlug)->first();
        }

        if (!$dua) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dua not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $dua,
        ]);
    }
}

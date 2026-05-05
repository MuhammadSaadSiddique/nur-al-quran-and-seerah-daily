<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use Illuminate\Http\Request;

class ThemeShowcaseController extends Controller
{
    /**
     * Display a listing of all active themes.
     */
    public function index()
    {
        $quranThemes = Theme::where('is_active', true)
            ->where('type', 'PARA')
            ->withCount('questions')
            ->orderBy('name')
            ->get();

        $seerahThemes = Theme::where('is_active', true)
            ->where('type', 'SEERAH')
            ->withCount('questions')
            ->orderBy('name')
            ->get();

        return view('themes.index', compact('quranThemes', 'seerahThemes'));
    }

    /**
     * Display the specified theme and its questions.
     */
    public function show(Theme $theme)
    {
        if (!$theme->is_active) {
            abort(404);
        }

        $questions = $theme->questions()->latest()->paginate(12);
        
        return view('themes.show', compact('theme', 'questions'));
    }
}

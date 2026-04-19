<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Theme;

class ThemeController extends Controller
{
    public function index()
    {
        $quranThemes = Theme::where('type', 'PARA')->orderBy('name')->get();
        $seerahThemes = Theme::where('type', 'SEERAH')->orderBy('name')->get();
        return view('admin.themes.index', compact('quranThemes', 'seerahThemes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:PARA,SEERAH',
        ]);

        Theme::create($validated);

        return redirect()->back()->with('success', 'Theme created successfully.');
    }

    public function update(Request $request, Theme $theme)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        $theme->update($validated);

        return redirect()->back()->with('success', 'Theme updated successfully.');
    }

    public function destroy(Theme $theme)
    {
        $theme->delete();
        return redirect()->back()->with('success', 'Theme deleted successfully.');
    }
}

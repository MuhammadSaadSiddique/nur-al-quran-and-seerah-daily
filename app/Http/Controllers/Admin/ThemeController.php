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
            'slug' => 'nullable|string|max:255|unique:themes,slug',
            'description' => 'nullable|string',
            'type' => 'required|in:PARA,SEERAH',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        }

        Theme::create($validated);

        return redirect()->back()->with('success', 'Theme created successfully.');
    }

    public function update(Request $request, Theme $theme)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:themes,slug,' . $theme->id,
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $theme->update($validated);

        return redirect()->back()->with('success', 'Theme updated successfully.');
    }

    public function merge(Request $request)
    {
        $request->validate([
            'keep_id' => 'required|exists:themes,id',
            'merge_ids' => 'required|array',
            'merge_ids.*' => 'exists:themes,id',
        ]);

        $keepTheme = Theme::find($request->keep_id);
        $mergeThemes = Theme::whereIn('id', $request->merge_ids)->where('id', '!=', $keepTheme->id)->get();

        foreach ($mergeThemes as $theme) {
            \App\Models\GeneratedQuestion::where('theme_id', $theme->id)
                ->update([
                    'theme_id' => $keepTheme->id,
                    'theme' => $keepTheme->name
                ]);
            
            $theme->delete();
        }

        return redirect()->back()->with('success', count($mergeThemes) . ' themes merged into ' . $keepTheme->name);
    }
}

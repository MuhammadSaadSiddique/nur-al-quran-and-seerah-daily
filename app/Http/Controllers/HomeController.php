<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\GeneratedQuestion;

class HomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $quranThemes = \App\Models\Theme::where('type', 'PARA')
            ->where('is_active', true)
            ->pluck('name')
            ->sort()
            ->values();

        $seerahThemes = \App\Models\Theme::where('type', 'SEERAH')
            ->where('is_active', true)
            ->pluck('name')
            ->sort()
            ->values();

        return view('home', compact('user', 'quranThemes', 'seerahThemes'));
    }
}

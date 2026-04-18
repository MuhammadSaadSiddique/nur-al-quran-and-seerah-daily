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

        $quranThemes = GeneratedQuestion::where('type', 'PARA')
            ->whereNotNull('theme')
            ->distinct()
            ->pluck('theme')
            ->sort()
            ->values();

        $seerahThemes = GeneratedQuestion::where('type', 'SEERAH')
            ->whereNotNull('theme')
            ->distinct()
            ->pluck('theme')
            ->sort()
            ->values();

        return view('home', compact('user', 'quranThemes', 'seerahThemes'));
    }
}

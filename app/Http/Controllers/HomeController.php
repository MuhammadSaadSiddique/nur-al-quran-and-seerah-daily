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
            ->has('questions', '>=', 5)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $seerahThemes = \App\Models\Theme::where('type', 'SEERAH')
            ->where('is_active', true)
            ->has('questions', '>=', 5)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('home', compact('user', 'quranThemes', 'seerahThemes'));
    }
}

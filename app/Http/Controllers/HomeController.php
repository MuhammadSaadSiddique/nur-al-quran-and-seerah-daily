<?php

namespace App\Http\Controllers;

use App\Services\DailyEngagementService;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct(
        private DailyEngagementService $engagementService
    ) {
    }

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

        $engagement = $this->engagementService->todayStatus($user);

        return view('home', compact('user', 'quranThemes', 'seerahThemes', 'engagement'));
    }
}

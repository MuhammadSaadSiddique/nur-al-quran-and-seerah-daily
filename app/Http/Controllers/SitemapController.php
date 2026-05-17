<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate the dynamic sitemap.xml.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(): Response
    {
        $urls = [];

        // 1. Homepage
        $urls[] = [
            'loc' => route('welcome'),
            'lastmod' => now()->startOfDay()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        // 2. Themes index page (Public showcase)
        $urls[] = [
            'loc' => route('themes.index'),
            'lastmod' => now()->startOfDay()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '0.9',
        ];

        // 3. Dynamic Themes (Active ones only with 5 or more questions)
        $themes = Theme::where('is_active', true)->has('questions', '>=', 5)->get();
        foreach ($themes as $theme) {
            $urls[] = [
                'loc' => route('themes.show', $theme->slug),
                'lastmod' => ($theme->updated_at ?? $theme->created_at ?? now())->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        // 4. Leaderboard page
        $urls[] = [
            'loc' => route('leaderboard'),
            'lastmod' => now()->startOfDay()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '0.8',
        ];

        // 5. Login page
        $urls[] = [
            'loc' => route('login'),
            'lastmod' => now()->startOfMonth()->toAtomString(),
            'changefreq' => 'monthly',
            'priority' => '0.5',
        ];

        // 6. Privacy Policy
        $urls[] = [
            'loc' => route('privacy'),
            'lastmod' => now()->startOfMonth()->toAtomString(),
            'changefreq' => 'monthly',
            'priority' => '0.3',
        ];

        // 7. Terms of Service
        $urls[] = [
            'loc' => route('terms'),
            'lastmod' => now()->startOfMonth()->toAtomString(),
            'changefreq' => 'monthly',
            'priority' => '0.3',
        ];

        return response()->view('sitemap', compact('urls'))
            ->header('Content-Type', 'text/xml');
    }
}

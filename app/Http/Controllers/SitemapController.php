<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Dua;
use App\Models\Theme;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

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

        // 1. Homepage (Quranic Research Landing)
        $urls[] = [
            'loc' => route('lens.landing'),
            'lastmod' => now()->startOfDay()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        // 2. Quranic Research Cockpit
        $urls[] = [
            'loc' => route('welcome'),
            'lastmod' => now()->startOfDay()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '0.9',
        ];

        // 3. Themes index page (Public showcase)
        $urls[] = [
            'loc' => route('themes.index'),
            'lastmod' => now()->startOfDay()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '0.9',
        ];

       

        // 5. Sadaqah Jariyah & Support
        if (Route::has('donate')) {
            $urls[] = [
                'loc' => route('donate'),
                'lastmod' => now()->startOfDay()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        // 6. Authentic Duas & Supplications
        if (Route::has('duas.index')) {
            $urls[] = [
                'loc' => route('duas.index'),
                'lastmod' => now()->startOfDay()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.9',
            ];
        }

        // 7. Daily Dua
        if (Route::has('daily.dua')) {
            $urls[] = [
                'loc' => route('daily.dua'),
                'lastmod' => now()->startOfDay()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ];
        }

        // 8. Dynamic Approved Duas
        if (Route::has('duas.show')) {
            $duas = Dua::where('status', 'approved')->get();
            foreach ($duas as $dua) {
                $urls[] = [
                    'loc' => route('duas.show', $dua->slug),
                    'lastmod' => ($dua->updated_at ?? $dua->created_at ?? now())->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            }
        }

        // 9. Community Quran Research Contributions
        if (Route::has('research.contributions.index')) {
            $urls[] = [
                'loc' => route('research.contributions.index'),
                'lastmod' => now()->startOfDay()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ];
        }

        // 10. Researchers Directory
        if (Route::has('researchers.index')) {
            $urls[] = [
                'loc' => route('researchers.index'),
                'lastmod' => now()->startOfDay()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        // 11. Interactive Quiz Learning
        if (Route::has('quiz.learning')) {
            $urls[] = [
                'loc' => route('quiz.learning'),
                'lastmod' => now()->startOfDay()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        // 12. Seerah Insights
        if (Route::has('seerah')) {
            $urls[] = [
                'loc' => route('seerah'),
                'lastmod' => now()->startOfDay()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        // 13. Quran History Insights
        if (Route::has('quran.history')) {
            $urls[] = [
                'loc' => route('quran.history'),
                'lastmod' => now()->startOfDay()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        // 14. Quran Surahs (1 to 114)
        if (Route::has('lens.surah')) {
            try {
                $surahs = \Illuminate\Support\Facades\DB::table('surahs')->select('number', 'updated_at', 'created_at')->orderBy('number')->get();
                foreach ($surahs as $surah) {
                    $modTime = $surah->updated_at ?? $surah->created_at;
                    $urls[] = [
                        'loc' => route('lens.surah', $surah->number),
                        'lastmod' => $modTime ? \Carbon\Carbon::parse($modTime)->toAtomString() : now()->startOfWeek()->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                    ];
                }
            } catch (\Exception $e) {
                for ($i = 1; $i <= 114; $i++) {
                    $urls[] = [
                        'loc' => route('lens.surah', $i),
                        'lastmod' => now()->startOfWeek()->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                    ];
                }
            }
        }

        // 15. Quran Verses with Verified Scientific Discoveries
        if (Route::has('lens.verse')) {
            try {
                $scienceVerses = \Illuminate\Support\Facades\DB::table('quran_science_links')
                    ->join('verses', 'quran_science_links.verse_id', '=', 'verses.id')
                    ->join('surahs', 'verses.surah_id', '=', 'surahs.id')
                    ->where('quran_science_links.status', 'approved')
                    ->select('surahs.number as surah_num', 'verses.verse_number')
                    ->distinct()
                    ->get();

                foreach ($scienceVerses as $sv) {
                    $urls[] = [
                        'loc' => route('lens.verse', [$sv->surah_num, $sv->verse_number]),
                        'lastmod' => now()->startOfWeek()->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.9',
                    ];
                }
            } catch (\Exception $e) {}
        }
 // 4. Dynamic Themes (Active ones only with 5 or more questions)
        $themes = Theme::where('is_active', true)->has('questions', '>=', 5)->get();
        foreach ($themes as $theme) {
            $urls[] = [
                'loc' => route('themes.show', $theme->slug),
                'lastmod' => ($theme->updated_at ?? $theme->created_at ?? now())->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }
        if (Route::has('blog.index') && Schema::hasTable('blog_posts')) {
            $urls[] = [
                'loc' => route('blog.index'),
                'lastmod' => now()->startOfDay()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];

            foreach (BlogPost::published()->get() as $post) {
                $urls[] = [
                    'loc' => route('blog.show', $post),
                    'lastmod' => ($post->updated_at ?? $post->published_at ?? now())->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            }
        }

        // 16. Leaderboard page
        $urls[] = [
            'loc' => route('leaderboard'),
            'lastmod' => now()->startOfDay()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '0.8',
        ];

        // 16. Privacy Policy
        $urls[] = [
            'loc' => route('privacy'),
            'lastmod' => now()->startOfMonth()->toAtomString(),
            'changefreq' => 'monthly',
            'priority' => '0.3',
        ];

        // 17. Terms of Service
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

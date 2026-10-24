<?php

namespace App\Http\Controllers;

use App\Exceptions\PatternGenerationLimited;
use App\Services\QuranPatternService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class QuranPatternController extends Controller
{
    public function __construct(private QuranPatternService $patterns) {}

    public function index(Request $request): Response|View
    {
        $query = trim((string) $request->query('q', ''));
        $hits = [];
        $suggested = false;
        $message = '';
        $status = 200;

        if ($query !== '') {
            try {
                $result = $this->patterns->search($query);
                if ($result['direct'] && isset($result['hits'][0])) {
                    $params = [
                        'chapter' => $result['hits'][0]['chapter'],
                        'verse' => $result['hits'][0]['verse'],
                    ];
                    if (!preg_match('/^\d{1,3}\s*[:.]\s*\d{1,3}$/', $query)) {
                        $params['focus'] = $query;
                    }

                    return redirect()->route('patterns.show', $params);
                }
                $hits = $result['hits'];
                $suggested = $result['suggested'];
                $message = $result['message'];
            } catch (PatternGenerationLimited $e) {
                $message = $e->getMessage();
                $status = 429;
            }
        }

        return response()->view('quran-patterns.index', [
            'query' => $query,
            'hits' => $hits,
            'suggested' => $suggested,
            'message' => $message,
            'card' => null,
        ], $status);
    }

    public function show(Request $request, int $chapter, int $verse): Response|View
    {
        if ($chapter < 1 || $chapter > 114 || $verse < 1) {
            abort(404);
        }

        $focus = $request->query('focus');
        $focus = is_string($focus) ? $focus : null;

        try {
            $card = $this->patterns->cardFor($chapter, $verse, $focus);
        } catch (PatternGenerationLimited $e) {
            return response()->view('quran-patterns.index', [
                'query' => trim((string) $request->query('q', $chapter.':'.$verse)),
                'hits' => [],
                'suggested' => false,
                'message' => $e->getMessage(),
                'card' => null,
            ], 429);
        }

        if ($card === null) {
            abort(404);
        }

        return view('quran-patterns.index', [
            'query' => trim((string) $request->query('q', $focus ?: $card['verse_key'])),
            'hits' => [[
                'chapter' => $card['chapter'],
                'verse' => $card['verse'],
                'verse_key' => $card['verse_key'],
                'surah_name' => $card['surah_name'],
                'arabic' => $card['arabic_text'],
                'translation' => $card['translation'],
            ]],
            'suggested' => false,
            'message' => '',
            'card' => $card,
        ]);
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $model = 'gemini-1.5-flash';
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
    }

    protected function callGemini(string $prompt, ?array $responseSchema = null): ?string
    {
        $config = [];
        if ($responseSchema) {
            $config['responseMimeType'] = 'application/json';
            $config['responseSchema'] = $responseSchema;
        }

        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
        ];

        if (!empty($config)) {
            $payload['generationConfig'] = $config;
        }

        try {
            $response = Http::timeout(60)
                ->withoutVerifying()
                ->post(
                    "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}",
                    $payload
                );

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            Log::error('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Gemini API exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    protected function getDifficultyPrompt(string $difficulty): string
    {
        return match ($difficulty) {
            'Easy' => 'Focus on basic facts, names, and direct recall. Keep it simple and straightforward.',
            'Medium' => 'Focus on understanding the wisdom behind specific events and roles of key figures. Require some level of thought beyond simple recall.',
            'Hard' => 'Focus on deep historical/theological context and nuances. Questions should be challenging and thought-provoking.',
            default => '',
        };
    }

    public function generateSpiritualWelcome(string $email): string
    {
        $prompt = "Generate a very short, one-sentence spiritual welcome message for a user with the email {$email} who is logging into a Quranic learning app. It should be warm and encouraging.";
        $result = $this->callGemini($prompt);
        return $result ? trim($result) : 'Peace be upon you and welcome to your journey.';
    }

    public function generateParaQuestions(int $paraNumber, string $difficulty, int $count = 20): array
    {
        $difficultyContext = $this->getDifficultyPrompt($difficulty);
        
        $activeThemes = \App\Models\Theme::where('type', 'PARA')
            ->where('is_active', true)
            ->pluck('name')
            ->toArray();
        $themeList = !empty($activeThemes) ? "'" . implode("', '", $activeThemes) . "'" : "'Belief in Allah', 'Stories of Prophets', 'Guidance for Daily Life', 'Hereafter'";

        $prompt = "Generate {$count} high-quality multiple-choice questions about Para {$paraNumber} of the Holy Quran at a {$difficulty} difficulty level.
{$difficultyContext}
Mix questions from themes: {$themeList}.
Ensure each question has exactly 4 options.

IMPORTANT: The correct answer (correctAnswerIndex) must be randomly and evenly distributed across all 4 possible indices (0, 1, 2, and 3). Do NOT always pick the middle options (1 or 2).

For each question, assign one of these themes: {$themeList}.
CRITICAL: For each answer, provide a concise explanation and include the specific Quranic verse (Surah and Ayat number).

Respond with a JSON array of objects with keys: text, options (array of 4 strings), correctAnswerIndex (0-3), explanation, theme, reference.";

        $schema = [
            'type' => 'ARRAY',
            'items' => [
                'type' => 'OBJECT',
                'properties' => [
                    'text' => ['type' => 'STRING'],
                    'options' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                    'correctAnswerIndex' => ['type' => 'INTEGER'],
                    'explanation' => ['type' => 'STRING'],
                    'theme' => ['type' => 'STRING'],
                    'reference' => ['type' => 'STRING'],
                ],
                'required' => ['text', 'options', 'correctAnswerIndex', 'explanation', 'theme', 'reference'],
            ],
        ];

        $result = $this->callGemini($prompt, $schema);

        if ($result) {
            $questions = json_decode($result, true);
            if (is_array($questions)) {
                foreach ($questions as $idx => &$q) {
                    $q['id'] = "q-para-{$paraNumber}-{$difficulty}-{$idx}-" . time();
                    $q['difficulty'] = $difficulty;
                }
                return $questions;
            }
        }

        return [];
    }

    public function generateSeerahQuizQuestions(string $difficulty, int $count = 20): array
    {
        $difficultyContext = $this->getDifficultyPrompt($difficulty);
        
        $activeThemes = \App\Models\Theme::where('type', 'SEERAH')
            ->where('is_active', true)
            ->pluck('name')
            ->toArray();
        $themeList = !empty($activeThemes) ? "'" . implode("', '", $activeThemes) . "'" : "'Prophet Muhammad\'s Early Life', 'The Revelation', 'Persecution in Makkah', 'The Hijrah', 'Life in Madinah'";

        $prompt = "Generate {$count} high-quality multiple-choice questions about the Seerah (life) of Prophet Muhammad (SAWW) at a {$difficulty} difficulty level.
{$difficultyContext}
Mix questions from themes: {$themeList}.
Ensure each question has exactly 4 options.

IMPORTANT: The correct answer (correctAnswerIndex) must be randomly and evenly distributed across all 4 possible indices (0, 1, 2, and 3). Avoid patterns like always choosing B or C.

For each question, assign one of these themes: {$themeList}.
CRITICAL: For each answer, provide a concise explanation and include the specific historical event reference.

Respond with a JSON array of objects with keys: text, options (array of 4 strings), correctAnswerIndex (0-3), explanation, theme, reference.";

        $schema = [
            'type' => 'ARRAY',
            'items' => [
                'type' => 'OBJECT',
                'properties' => [
                    'text' => ['type' => 'STRING'],
                    'options' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                    'correctAnswerIndex' => ['type' => 'INTEGER'],
                    'explanation' => ['type' => 'STRING'],
                    'theme' => ['type' => 'STRING'],
                    'reference' => ['type' => 'STRING'],
                ],
                'required' => ['text', 'options', 'correctAnswerIndex', 'explanation', 'theme', 'reference'],
            ],
        ];

        $result = $this->callGemini($prompt, $schema);

        if ($result) {
            $questions = json_decode($result, true);
            if (is_array($questions)) {
                foreach ($questions as $idx => &$q) {
                    $q['id'] = "q-seerah-quiz-{$difficulty}-" . time() . "-{$idx}";
                    $q['difficulty'] = $difficulty;
                }
                return $questions;
            }
        }

        return [];
    }

    public function generateThemeQuestions(string $type, string $theme, string $difficulty, int $count = 20): array
    {
        $difficultyContext = $this->getDifficultyPrompt($difficulty);
        $sourceName = $type === 'PARA' ? 'the Holy Quran' : 'the Seerah (life) of Prophet Muhammad (SAWW)';
        $referenceRequirement = $type === 'PARA' 
            ? 'include the specific Quranic verse (Surah and Ayat number)' 
            : 'include the specific historical event reference';

        $prompt = "Generate {$count} high-quality multiple-choice questions about {$sourceName} specifically focused on the theme: '{$theme}' at a {$difficulty} difficulty level.
{$difficultyContext}
" . ($type === 'PARA' ? "Questions should span the entire Quran, not limited to any specific Para." : "") . "
Ensure each question has exactly 4 options.

IMPORTANT: The correct answer (correctAnswerIndex) must be randomly and evenly distributed across all 4 possible indices (0, 1, 2, and 3).

CRITICAL: For each answer, provide a concise explanation and {$referenceRequirement}.

Respond with a JSON array of objects with keys: text, options (array of 4 strings), correctAnswerIndex (0-3), explanation, theme, reference.";

        $schema = [
            'type' => 'ARRAY',
            'items' => [
                'type' => 'OBJECT',
                'properties' => [
                    'text' => ['type' => 'STRING'],
                    'options' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                    'correctAnswerIndex' => ['type' => 'INTEGER'],
                    'explanation' => ['type' => 'STRING'],
                    'theme' => ['type' => 'STRING'],
                    'reference' => ['type' => 'STRING'],
                ],
                'required' => ['text', 'options', 'correctAnswerIndex', 'explanation', 'theme', 'reference'],
            ],
        ];

        $result = $this->callGemini($prompt, $schema);

        if ($result) {
            $questions = json_decode($result, true);
            if (is_array($questions)) {
                foreach ($questions as $idx => &$q) {
                    $q['id'] = "q-theme-{$type}-" . time() . "-{$idx}";
                    $q['difficulty'] = $difficulty;
                    $q['theme'] = $theme;
                }
                return $questions;
            }
        }

        return [];
    }

    public function generateSeerahInsight(string $difficulty = 'Medium'): ?array
    {
        $prompt = "Provide an inspiring insight from the Seerah at {$difficulty} difficulty.
Include a multiple-choice question with theme categorization.
Themes: 'Prophet Muhammad\\'s Early Life', 'The Revelation', 'Persecution in Makkah', 'The Hijrah', 'Life in Madinah'.
RANDOMIZE the correct answer index (0-3).

Respond with a JSON object with keys: title (string), content (string), question (object with text, options array, correctAnswerIndex 0-3, explanation, theme, reference).";

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'title' => ['type' => 'STRING'],
                'content' => ['type' => 'STRING'],
                'question' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'text' => ['type' => 'STRING'],
                        'options' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                        'correctAnswerIndex' => ['type' => 'INTEGER'],
                        'explanation' => ['type' => 'STRING'],
                        'theme' => ['type' => 'STRING'],
                        'reference' => ['type' => 'STRING'],
                    ],
                ],
            ],
        ];

        $result = $this->callGemini($prompt, $schema);

        if ($result) {
            $parsed = json_decode($result, true);
            if (is_array($parsed) && isset($parsed['question'])) {
                $parsed['question']['difficulty'] = $difficulty;
                $parsed['question']['id'] = 'q-seerah-insight-' . time();
                return $parsed;
            }
        }

        return null;
    }

    public function generateQuranHistoryInsight(string $difficulty = 'Medium'): ?array
    {
        $prompt = "Provide an educational insight about the history of the Quran at {$difficulty} difficulty.
Followed by one multiple-choice question. Categorize theme as 'Quranic History'.
RANDOMIZE the correct answer index (0-3).

Respond with a JSON object with keys: title (string), content (string), question (object with text, options array, correctAnswerIndex 0-3, explanation, theme, reference).";

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'title' => ['type' => 'STRING'],
                'content' => ['type' => 'STRING'],
                'question' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'text' => ['type' => 'STRING'],
                        'options' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                        'correctAnswerIndex' => ['type' => 'INTEGER'],
                        'explanation' => ['type' => 'STRING'],
                        'theme' => ['type' => 'STRING'],
                        'reference' => ['type' => 'STRING'],
                    ],
                ],
            ],
        ];

        $result = $this->callGemini($prompt, $schema);

        if ($result) {
            $parsed = json_decode($result, true);
            if (is_array($parsed) && isset($parsed['question'])) {
                $parsed['question']['difficulty'] = $difficulty;
                $parsed['question']['theme'] = 'Quranic History';
                $parsed['question']['id'] = 'q-quran-history-' . time();
                return $parsed;
            }
        }

        return null;
    }

    public function generateVerseLensAnalysis(int $chapter, int $verse, string $arabic, string $translation, string $lensType): ?string
    {
        $prompt = "Provide an academic and educational analysis of Surah {$chapter}, Ayat {$verse} from the perspective of the '{$lensType}' lens.
Arabic Text: '{$arabic}'
Translation: '{$translation}'

Requirements:
- Structure the analysis professionally.
- Highlight classical commentary or scientific/historical parallels depending on the lens.
- Keep the response clear, structured, and insightful (2-3 paragraphs max).
- Do not use markdown headers like # or ##. Use bold text for sections if needed.";

        return $this->callGemini($prompt);
    }
}

<?php

namespace App\Services;

use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Single source of truth for the "Help shape Eternal Echo" requirements survey.
 *
 * The survey page, server validation, the admin results page and the CSV export
 * all read from here, so editing a question or feature card is a one-file change.
 * Option keys are stored in the database — rename labels freely, but keep keys stable
 * (or bump KEY to start a fresh round of responses).
 */
class RequirementSurvey
{
    public const KEY = 'requirements-2026';

    public const PLAY_STORE_URL = 'https://play.google.com/store/apps/details?id=com.asloobulhayat.eternalecho';

    public const REACTIONS = [
        'love' => ['label' => 'Love it', 'emoji' => '😍'],
        'try' => ['label' => 'Want to try', 'emoji' => '👀'],
        'meh' => ['label' => 'Not for me', 'emoji' => '🙅'],
    ];

    /**
     * Features already in the app, shown as a swipeable deck.
     * `route` is a named web route; `play` sends people to the Android listing instead.
     * `app` names the matching screen in the Android app, which opens it in place of the web page.
     */
    public static function features(): array
    {
        return [
            'para_quizzes' => [
                'emoji' => '🧠',
                'title' => 'Para-by-para quizzes',
                'body' => 'All 30 juz with easy, medium and hard questions, plus an explanation after every answer.',
                'tag' => 'Web & Android',
                'route' => 'quiz.learning',
                'app' => 'quiz',
                'gradient' => 'from-emerald-500/30 to-teal-500/10',
            ],
            'quranic_lens' => [
                'emoji' => '🔭',
                'title' => 'The Quranic Lens',
                'body' => 'Open any verse and see its links to Hadith, Seerah, history, science and earlier scripture.',
                'tag' => 'Web & Android',
                'route' => 'welcome',
                'app' => 'quran',
                'gradient' => 'from-sky-500/30 to-indigo-500/10',
            ],
            'seerah' => [
                'emoji' => '⏳',
                'title' => 'Seerah timeline',
                'body' => 'The life of the Prophet ﷺ in order, from the early years in Makkah to Madinah, with short reviews.',
                'tag' => 'Web & Android',
                'route' => 'seerah',
                'app' => 'seerah',
                'gradient' => 'from-amber-500/30 to-orange-500/10',
            ],
            'duas' => [
                'emoji' => '🤲',
                'title' => 'Duas with meaning',
                'body' => 'Authentic duas with word-by-word meaning, translation, references and a dua for each day.',
                'tag' => 'Web & Android',
                'route' => 'duas.index',
                'app' => 'duas',
                'gradient' => 'from-rose-500/30 to-pink-500/10',
            ],
            'streaks' => [
                'emoji' => '🔥',
                'title' => 'Daily streak & checklist',
                'body' => 'Read, reflect, make dua and tick it off. A small daily habit that keeps your streak alive.',
                'tag' => 'Web & Android',
                'route' => 'home',
                'app' => 'home',
                'gradient' => 'from-orange-500/30 to-red-500/10',
            ],
            'salah' => [
                'emoji' => '🕌',
                'title' => 'Salah times & nearby mosques',
                'body' => 'Prayer times for where you are, plus jama\'ah times for nearby mosques shared by the community.',
                'tag' => 'Android app',
                'play' => true,
                'app' => 'salah',
                'gradient' => 'from-emerald-400/30 to-lime-500/10',
            ],
            'leaderboard' => [
                'emoji' => '🏆',
                'title' => 'Leaderboard & progress',
                'body' => 'Track your accuracy and completed paras, and see how you rank with learners worldwide.',
                'tag' => 'Web & Android',
                'route' => 'leaderboard',
                'app' => 'leaderboard',
                'gradient' => 'from-yellow-500/30 to-amber-500/10',
            ],
            'research_community' => [
                'emoji' => '🧑‍🔬',
                'title' => 'Community research',
                'body' => 'Researchers share analyses and videos on verses, and the community reviews them before they go live.',
                'tag' => 'Web & Android',
                'route' => 'research.contributions.index',
                'app' => 'research',
                'gradient' => 'from-violet-500/30 to-fuchsia-500/10',
            ],
        ];
    }

    /**
     * Choice questions, in the order they are asked.
     * type: single (auto-advances) | multi (max = pick limit).
     */
    public static function questions(): array
    {
        return [
            'goals' => [
                'type' => 'multi',
                'max' => 7,
                'eyebrow' => 'Your intention',
                'title' => 'What brings you to Eternal Echo?',
                'hint' => 'Pick up to 3',
                'options' => [
                    'quran_understanding' => ['📖', 'Understand the Quran'],
                    'seerah' => ['🌙', 'Learn the Seerah ﷺ'],
                    'quizzes' => ['🧠', 'Test & revise what I know'],
                    'duas' => ['🤲', 'Daily duas & adhkar'],
                    'research' => ['🔬', 'Research & deeper study'],
                    'salah' => ['🕌', 'Salah times & mosques'],
                    'family' => ['👨‍👩‍👧', 'Teach my family & kids'],
                    'habit' => ['🔥', 'Build a daily habit'],
                ],
            ],
            'role' => [
                'type' => 'single',
                'eyebrow' => 'About you',
                'title' => 'Which best describes you?',
                'hint' => 'So we build for the right people',
                'options' => [
                    'student' => ['🎓', 'Student'],
                    'parent' => ['🏡', 'Parent'],
                    'teacher' => ['🧑‍🏫', 'Teacher or Imam'],
                    'scholar' => ['📚', 'Researcher or scholar'],
                    'revert' => ['🌱', 'New to Islam'],
                    'professional' => ['💼', 'Working professional'],
                ],
            ],
            'daily_time' => [
                'type' => 'single',
                'eyebrow' => 'Your rhythm',
                'title' => 'How much time can you give each day?',
                'hint' => 'Be honest, little and often counts',
                'options' => [
                    '2min' => ['⚡', 'A quick 2 minutes'],
                    '10min' => ['☕', '5 to 10 minutes'],
                    '30min' => ['📿', '15 to 30 minutes'],
                    '60min' => ['🌌', '30 minutes or more'],
                ],
            ],
            'wishlist' => [
                'type' => 'multi',
                'max' => 3,
                'eyebrow' => 'Build next',
                'title' => 'Which 3 should we build next?',
                'hint' => 'Your top 3 picks decide our roadmap',
                'options' => [
                    'audio_recitation' => ['🎧', 'Audio recitation that follows along'],
                    'tafsir' => ['📜', 'Simple tafsir in my language'],
                    'hifz' => ['🧩', 'Hifz (memorisation) tracker'],
                    'prayer_alerts' => ['🔔', 'Prayer time reminders'],
                    'kids_mode' => ['🧒', 'Kids & family mode'],
                    'group_challenges' => ['👥', 'Quiz challenges with friends'],
                    'ramadan' => ['🌙', 'Ramadan & Hajj planners'],
                    'offline' => ['📴', 'Works fully offline'],
                    'hadith_daily' => ['📿', 'A hadith every day'],
                    'ask_ai' => ['💬', 'Ask about a verse, with sources'],
                ],
            ],
            'languages' => [
                'type' => 'multi',
                'max' => 4,
                'eyebrow' => 'Your language',
                'title' => 'Which languages should the app speak?',
                'hint' => 'Pick all that you would use',
                // A letter from each script rather than a flag: languages aren't countries,
                // and Windows shows flag emoji as plain letters.
                'options' => [
                    'english' => ['Aa', 'English'],
                    'urdu' => ['ے', 'Urdu · اردو'],
                    'arabic' => ['ع', 'Arabic · العربية'],
                    'hindi' => ['अ', 'Hindi · हिन्दी'],
                    'bengali' => ['অ', 'Bengali · বাংলা'],
                    'indonesian' => ['Id', 'Bahasa Indonesia / Melayu'],
                    'turkish' => ['Ç', 'Turkish · Türkçe'],
                    'french' => ['É', 'French · Français'],
                ],
            ],
            'platform' => [
                'type' => 'single',
                'eyebrow' => 'Your device',
                'title' => 'Where do you use Eternal Echo most?',
                'hint' => 'Helps us decide where to build first',
                'options' => [
                    'android' => ['🤖', 'Android app'],
                    'web' => ['💻', 'Website'],
                    'both' => ['🔁', 'Both equally'],
                    'iphone' => ['🍏', 'I\'m on iPhone and want an app'],
                    'new' => ['👋', 'I\'m new here'],
                ],
            ],
        ];
    }

    /** Suggestion chips for the open "one wish" question. */
    public static function wishIdeas(): array
    {
        return [
            'Word-by-word Urdu translation',
            'Reminders after Fajr',
            'Lessons for my children',
            'Share quiz results with friends',
            'Dark mode for night reading',
        ];
    }

    /** Everything the survey page needs, as one JSON-ready payload. */
    public static function clientConfig(): array
    {
        $features = [];
        foreach (self::features() as $key => $feature) {
            $features[] = [
                'key' => $key,
                'emoji' => $feature['emoji'],
                'title' => $feature['title'],
                'body' => $feature['body'],
                'tag' => $feature['tag'],
                'gradient' => $feature['gradient'],
                'url' => !empty($feature['play']) ? self::PLAY_STORE_URL : route($feature['route']),
                'app' => $feature['app'] ?? null,
            ];
        }

        $questions = [];
        foreach (self::questions() as $key => $question) {
            $options = [];
            foreach ($question['options'] as $value => [$emoji, $label]) {
                $options[] = ['value' => $value, 'emoji' => $emoji, 'label' => $label];
            }
            $questions[$key] = array_merge($question, ['key' => $key, 'options' => $options]);
        }

        return [
            'key' => self::KEY,
            // Explicit order for clients whose JSON objects don't keep key order (the Android app).
            'order' => array_keys($questions),
            'questions' => $questions,
            'features' => $features,
            'reactions' => self::REACTIONS,
            'wishIdeas' => self::wishIdeas(),
        ];
    }

    public static function rules(): array
    {
        $rules = [
            // Every card can be skipped, so an empty reaction map is valid.
            'features' => ['present', 'array'],
            'nps' => ['required', 'integer', 'between:0,10'],
            'wish' => ['nullable', 'string', 'max:1000'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'whatsapp' => ['nullable', 'string', 'regex:/^\+?[0-9][0-9\s\-()]{6,19}$/'],
            'notify' => ['nullable', 'boolean'],
            'source' => ['nullable', 'string', 'alpha_dash', 'max:30'],
            'duration_seconds' => ['nullable', 'integer', 'between:0,86400'],
        ];

        foreach (self::questions() as $key => $question) {
            $allowed = array_keys($question['options']);
            if ($question['type'] === 'single') {
                $rules[$key] = ['required', 'string', Rule::in($allowed)];
            } else {
                $rules[$key] = ['required', 'array', 'min:1', 'max:' . $question['max']];
                $rules[$key . '.*'] = ['string', 'distinct', Rule::in($allowed)];
            }
        }

        foreach (array_keys(self::features()) as $featureKey) {
            $rules['features.' . $featureKey] = ['nullable', 'string', Rule::in(array_keys(self::REACTIONS))];
        }

        return $rules;
    }

    /**
     * Validate and save one completed survey, from the web page or the Android app.
     * Signed-in people keep one response per round (answering again updates it); guests add a new one.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function record(array $input, ?User $user, string $defaultSource = 'web'): SurveyResponse
    {
        // Signed-in people (and older app versions that send blanks) fall back to their account details.
        if ($user) {
            $input['name'] = filled($input['name'] ?? null) ? $input['name'] : ($user->display_name ?: $user->name);
            $input['email'] = filled($input['email'] ?? null) ? $input['email'] : $user->email;
        }

        $validated = Validator::make($input, self::rules(), [
            'name.required' => 'Please add your name.',
            'email.required' => 'Please add your email.',
            'whatsapp.regex' => 'That WhatsApp number doesn\'t look right. Include your country code, e.g. +92 300 1234567.',
        ])->validate();

        $attributes = [
            'answers' => self::answersFrom($validated),
            'nps' => $validated['nps'],
            'wish' => filled($validated['wish'] ?? null) ? trim($validated['wish']) : null,
            'name' => trim($validated['name']),
            'email' => trim($validated['email']),
            'whatsapp' => self::normalizePhone($validated['whatsapp'] ?? null),
            'notify' => (bool) ($validated['notify'] ?? false),
            'source' => $validated['source'] ?? $defaultSource,
            'duration_seconds' => $validated['duration_seconds'] ?? null,
        ];

        if ($user) {
            return SurveyResponse::updateOrCreate(['survey_key' => self::KEY, 'user_id' => $user->id], $attributes);
        }

        return SurveyResponse::create($attributes + ['survey_key' => self::KEY]);
    }

    /** A signed-in person's saved answers for this round, so the app can prefill an update. */
    public static function previousFor(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        $response = SurveyResponse::where('survey_key', self::KEY)->where('user_id', $user->id)->first();
        if (!$response) {
            return null;
        }

        return array_merge($response->answers, [
            'nps' => $response->nps,
            'wish' => $response->wish,
            'name' => $response->name,
            'email' => $response->email,
            'whatsapp' => $response->whatsapp,
            'notify' => $response->notify,
        ]);
    }

    /** Keeps a leading "+" and the digits, so "+92 300-123 4567" is stored as "+923001234567". */
    public static function normalizePhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $phone);

        return (str_starts_with(trim($phone), '+') ? '+' : '') . $digits;
    }

    /** Pull the answer payload (everything except contact details) out of validated input. */
    public static function answersFrom(array $validated): array
    {
        $answers = [];
        foreach (array_keys(self::questions()) as $key) {
            $answers[$key] = $validated[$key];
        }

        // Keep only known feature keys with a reaction, in deck order.
        $answers['features'] = collect(self::features())
            ->keys()
            ->mapWithKeys(fn ($key) => [$key => $validated['features'][$key] ?? null])
            ->filter()
            ->all();

        return $answers;
    }

    /**
     * Aggregate responses for the admin results page.
     *
     * @param Collection<int, \App\Models\SurveyResponse> $responses
     */
    public static function summarize(Collection $responses): array
    {
        $total = $responses->count();

        $questions = [];
        foreach (self::questions() as $key => $question) {
            $counts = array_fill_keys(array_keys($question['options']), 0);
            foreach ($responses as $response) {
                foreach ((array) ($response->answers[$key] ?? []) as $value) {
                    if (array_key_exists($value, $counts)) {
                        $counts[$value]++;
                    }
                }
            }
            arsort($counts);

            $rows = [];
            foreach ($counts as $value => $count) {
                [$emoji, $label] = $question['options'][$value];
                $rows[] = [
                    'value' => $value,
                    'emoji' => $emoji,
                    'label' => $label,
                    'count' => $count,
                    'percent' => $total > 0 ? (int) round($count / $total * 100) : 0,
                ];
            }

            $questions[$key] = [
                'title' => $question['title'],
                'type' => $question['type'],
                'rows' => $rows,
            ];
        }

        $features = [];
        foreach (self::features() as $key => $feature) {
            $tally = array_fill_keys(array_keys(self::REACTIONS), 0);
            foreach ($responses as $response) {
                $reaction = $response->answers['features'][$key] ?? null;
                if ($reaction && array_key_exists($reaction, $tally)) {
                    $tally[$reaction]++;
                }
            }
            $reacted = array_sum($tally);
            $features[] = [
                'key' => $key,
                'emoji' => $feature['emoji'],
                'title' => $feature['title'],
                'counts' => $tally,
                'reacted' => $reacted,
                'love_rate' => $reacted > 0 ? (int) round($tally['love'] / $reacted * 100) : 0,
            ];
        }
        usort($features, fn ($a, $b) => [$b['love_rate'], $b['counts']['love']] <=> [$a['love_rate'], $a['counts']['love']]);

        $scores = $responses->pluck('nps')->filter(fn ($v) => $v !== null);
        $promoters = $scores->filter(fn ($v) => $v >= 9)->count();
        $passives = $scores->filter(fn ($v) => $v >= 7 && $v <= 8)->count();
        $detractors = $scores->filter(fn ($v) => $v <= 6)->count();
        $scored = $scores->count();
        $distribution = array_fill(0, 11, 0);
        foreach ($scores as $score) {
            $distribution[$score]++;
        }

        return [
            'total' => $total,
            'last_7_days' => $responses->filter(fn ($r) => $r->created_at && $r->created_at->gte(now()->subDays(7)))->count(),
            'with_wish' => $responses->filter(fn ($r) => filled($r->wish))->count(),
            'want_updates' => $responses->where('notify', true)->count(),
            'nps' => [
                'score' => $scored > 0 ? (int) round(($promoters - $detractors) / $scored * 100) : null,
                'average' => $scored > 0 ? round($scores->avg(), 1) : null,
                'promoters' => $promoters,
                'passives' => $passives,
                'detractors' => $detractors,
                'scored' => $scored,
                'distribution' => $distribution,
            ],
            'questions' => $questions,
            'features' => $features,
        ];
    }
}

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
                $parsed['question']['id'] = 'q-seerah-insight-' . uniqid();
                return $parsed;
            }
        }

        return $this->getFallbackSeerahInsight($difficulty);
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
                $parsed['question']['id'] = 'q-quran-history-' . uniqid();
                return $parsed;
            }
        }

        return $this->getFallbackQuranHistoryInsight($difficulty);
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

        $result = $this->callGemini($prompt);
        if ($result) {
            return $result;
        }

        $lensLabel = ucfirst($lensType);
        return "**{$lensLabel} Perspective on Surah {$chapter}:{$verse}**\n\n"
            . "This verse (\"{$translation}\") invites deep reflection through the {$lensLabel} lens. Classical exegetes emphasize the precision of the Arabic phrasing and its harmony with the broader thematic structure of Surah {$chapter}.\n\n"
            . "**Key Analytical Takeaways**\n\n"
            . "When examined alongside authentic traditions and contextual evidence, Surah {$chapter}:{$verse} demonstrates how Quranic guidance bridges spiritual principles with observable reality and historical continuity.";
    }

    public function getCuratedQuranHistoryInsights(): array
    {
        return [
            [
                'difficulty' => 'Easy',
                'title' => 'The First Revelation in Cave Hira',
                'content' => 'The revelation of the Holy Quran began in 610 CE during the month of Ramadan in the Cave of Hira on Jabal al-Nour near Makkah. Archangel Jibril (Gabriel) appeared to Prophet Muhammad ﷺ with the opening five verses of Surah Al-\'Alaq (96:1-5), commanding him to "Read in the name of your Lord who created." This marked the beginning of a 23-year period of gradual revelation.',
                'question' => [
                    'id' => 'q-qh-fallback-easy-1',
                    'difficulty' => 'Easy',
                    'theme' => 'Quranic History',
                    'text' => 'Which Surah\'s opening verses were the very first revelation of the Holy Quran?',
                    'options' => [
                        'Surah Al-Fatihah',
                        'Surah Al-\'Alaq',
                        'Surah Al-Baqarah',
                        'Surah Al-Muddaththir',
                    ],
                    'correctAnswerIndex' => 1,
                    'explanation' => 'The first five verses of Surah Al-\'Alaq (96:1-5) were revealed to Prophet Muhammad ﷺ in the Cave of Hira through Archangel Jibril.',
                    'reference' => 'Sahih al-Bukhari 3, Surah Al-\'Alaq 96:1-5',
                ],
            ],
            [
                'difficulty' => 'Easy',
                'title' => 'The Scribes of Revelation (Kuttab al-Wahy)',
                'content' => 'Whenever verses of the Quran were revealed, Prophet Muhammad ﷺ not only memorized them and taught them to the Companions orally, but also appointed dedicated scribes (Kuttab al-Wahy) to write them down immediately on parchment, leather, palm stalks, and flat stones. Among the foremost scribes was Zayd ibn Thabit (RA), who later led the official compilation committees.',
                'question' => [
                    'id' => 'q-qh-fallback-easy-2',
                    'difficulty' => 'Easy',
                    'theme' => 'Quranic History',
                    'text' => 'Which Companion was the chief scribe appointed to lead the compilation of the Quran into a single volume?',
                    'options' => [
                        'Zayd ibn Thabit (RA)',
                        'Bilal ibn Rabah (RA)',
                        'Khalid ibn al-Walid (RA)',
                        'Salman al-Farsi (RA)',
                    ],
                    'correctAnswerIndex' => 0,
                    'explanation' => 'Zayd ibn Thabit (RA) was one of the primary scribes of revelation for the Prophet ﷺ and was entrusted by both Abu Bakr (RA) and Uthman (RA) to head the compilation of the Mushaf.',
                    'reference' => 'Sahih al-Bukhari 4986',
                ],
            ],
            [
                'difficulty' => 'Easy',
                'title' => 'Duration of the Quranic Revelation',
                'content' => 'Unlike previous scriptures that were revealed all at once, the Holy Quran was revealed gradually over approximately 23 years—about 13 years in Makkah and 10 years in Madinah. This gradual revelation (Tanjim) strengthened the hearts of the believers, addressed real-time events and questions, and facilitated complete memorization and practical implementation.',
                'question' => [
                    'id' => 'q-qh-fallback-easy-3',
                    'difficulty' => 'Easy',
                    'theme' => 'Quranic History',
                    'text' => 'Over approximately how many years was the Holy Quran revealed to Prophet Muhammad ﷺ?',
                    'options' => [
                        '10 years',
                        '15 years',
                        '23 years',
                        '40 years',
                    ],
                    'correctAnswerIndex' => 2,
                    'explanation' => 'The Quran was revealed over 23 years (13 years in Makkah and 10 years in Madinah), as referenced in Surah Al-Isra (17:106) and Surah Al-Furqan (25:32).',
                    'reference' => 'Surah Al-Isra 17:106, Sahih al-Bukhari 4980',
                ],
            ],
            [
                'difficulty' => 'Medium',
                'title' => 'First Official Compilation Under Caliph Abu Bakr (RA)',
                'content' => 'Following the Battle of Yamamah (11 AH / 632 CE), in which many Huffaz (memorizers of the Quran) were martyred, Umar ibn al-Khattab (RA) advised Caliph Abu Bakr as-Siddiq (RA) to compile all written fragments of the Quran into a single master volume (Suhuf). Zayd ibn Thabit (RA) required every verse to be verified by both collective memorization and at least two written witnesses recorded in the presence of the Prophet ﷺ.',
                'question' => [
                    'id' => 'q-qh-fallback-medium-1',
                    'difficulty' => 'Medium',
                    'theme' => 'Quranic History',
                    'text' => 'Which historical event prompted Umar ibn al-Khattab (RA) to propose compiling the Quran into a single volume during Abu Bakr\'s (RA) caliphate?',
                    'options' => [
                        'The Battle of Badr',
                        'The Treaty of Hudaybiyyah',
                        'The Battle of Yamamah',
                        'The Conquest of Jerusalem',
                    ],
                    'correctAnswerIndex' => 2,
                    'explanation' => 'In the Battle of Yamamah, a large number of Qurra\' (memorizers of the Quran) were martyred, leading Umar (RA) to urge Abu Bakr (RA) to gather the written manuscripts into the Suhuf.',
                    'reference' => 'Sahih al-Bukhari 4986',
                ],
            ],
            [
                'difficulty' => 'Medium',
                'title' => 'The Annual Review (Al-\'Ardah Al-Akhir) in Ramadan',
                'content' => 'Every Ramadan, Archangel Jibril would review the entire Quran revealed up to that point with Prophet Muhammad ﷺ. In the final Ramadan before the Prophet\'s ﷺ passing (10 AH), Jibril reviewed the complete Quran with him twice—an event known as Al-\'Ardah Al-Akhir (The Final Review). Zayd ibn Thabit (RA) was present during this final review, cementing the canonical order of verses and Surahs.',
                'question' => [
                    'id' => 'q-qh-fallback-medium-2',
                    'difficulty' => 'Medium',
                    'theme' => 'Quranic History',
                    'text' => 'What is the name of the final double-review of the entire Quran between Archangel Jibril and Prophet Muhammad ﷺ in the last Ramadan of his life?',
                    'options' => [
                        'Al-Jam\' Al-Awwal',
                        'Al-\'Ardah Al-Akhir',
                        'Asbab Al-Nuzul',
                        'Al-Rasm Al-Uthmani',
                    ],
                    'correctAnswerIndex' => 1,
                    'explanation' => 'Al-\'Ardah Al-Akhir refers to the final presentation/review in which Jibril recited and reviewed the complete Quran twice with the Prophet ﷺ during the last Ramadan before his passing.',
                    'reference' => 'Sahih al-Bukhari 4997, 4998',
                ],
            ],
            [
                'difficulty' => 'Medium',
                'title' => 'Standardization of the Mushaf Under Caliph Uthman (RA)',
                'content' => 'During the caliphate of Uthman ibn Affan (RA) around 25 AH (646 CE), companion Hudhayfah ibn al-Yaman (RA) noticed regional pronunciation differences among new Muslims in the frontier provinces of Armenia and Azerbaijan. Caliph Uthman (RA) formed a committee of four leading scholars—headed by Zayd ibn Thabit (RA)—to produce official master copies from the Suhuf kept with Hafsah bint Umar (RA) and distribute them to the major Islamic centers along with trained reciters.',
                'question' => [
                    'id' => 'q-qh-fallback-medium-3',
                    'difficulty' => 'Medium',
                    'theme' => 'Quranic History',
                    'text' => 'Which Companion alerted Caliph Uthman ibn Affan (RA) to regional recitation disputes during the campaigns in Armenia and Azerbaijan?',
                    'options' => [
                        'Hudhayfah ibn al-Yaman (RA)',
                        'Mu\'adh ibn Jabal (RA)',
                        'Abu Musa al-Ash\'ari (RA)',
                        'Amr ibn al-\'As (RA)',
                    ],
                    'correctAnswerIndex' => 0,
                    'explanation' => 'Hudhayfah ibn al-Yaman (RA) traveled directly from the northern frontier to Madinah and urged Caliph Uthman (RA) to unite the Ummah upon standardized master copies (Al-Masahif Al-Uthmaniyyah).',
                    'reference' => 'Sahih al-Bukhari 4987',
                ],
            ],
            [
                'difficulty' => 'Hard',
                'title' => 'Rasm al-Uthmani and Diacritical Refinements',
                'content' => 'The Uthmanic manuscripts were intentionally written in an unpointed consonantal script (Rasm al-Uthmani) that accommodated the authentic mutawatir Qira\'at while relying on oral transmission alongside the text. As non-Arab populations entered Islam, scholars such as Abu al-Aswad al-Du\'ali, Nasr ibn \'Asim, Yahya ibn Ya\'mar, and later Al-Khalil ibn Ahmad al-Farahidi introduced vowel markings (Tashkil) and consonantal dots (I\'jam) in distinct ink colors without altering a single letter of the original Uthmanic Rasm.',
                'question' => [
                    'id' => 'q-qh-fallback-hard-1',
                    'difficulty' => 'Hard',
                    'theme' => 'Quranic History',
                    'text' => 'Which early scholar is historically credited with pioneering the system of vocalization marks (Nuqat al-I\'rab) in the Mushaf to prevent grammatical errors?',
                    'options' => [
                        'Imam al-Shafi\'i',
                        'Abu al-Aswad al-Du\'ali',
                        'Ibn Jarir al-Tabari',
                        'Sibawayh',
                    ],
                    'correctAnswerIndex' => 1,
                    'explanation' => 'Abu al-Aswad al-Du\'ali (d. 69 AH) introduced colored dots to indicate case endings (fathah, dammah, kasrah), which were later refined into the modern harakat system by Al-Khalil ibn Ahmad al-Farahidi.',
                    'reference' => 'Kitab al-Masahif by Ibn Abi Dawud; Al-Itqan fi Ulum al-Quran by Al-Suyuti',
                ],
            ],
            [
                'difficulty' => 'Hard',
                'title' => 'Early Carbon-Dated Quranic Manuscripts (Birmingham & Sana\'a)',
                'content' => 'Modern radiocarbon (C-14) testing of early Hijazi-script Quranic manuscripts—such as the Birmingham Quran Manuscript (Mingana 1572a) at the University of Birmingham—dated the parchment to between 568 CE and 645 CE with 95.4% accuracy, placing it directly within or immediately after the lifetime of Prophet Muhammad ﷺ. The text of Surahs 18, 19, and 20 on these folios matches the standard Quranic text read worldwide today.',
                'question' => [
                    'id' => 'q-qh-fallback-hard-2',
                    'difficulty' => 'Hard',
                    'theme' => 'Quranic History',
                    'text' => 'Which Surahs are preserved on the radiocarbon-dated Birmingham Quran Manuscript folios (dated 568–645 CE)?',
                    'options' => [
                        'Surahs 1, 2, and 3 (Al-Fatihah, Al-Baqarah, Ali \'Imran)',
                        'Surahs 18, 19, and 20 (Al-Kahf, Maryam, Ta-Ha)',
                        'Surahs 36, 55, and 67 (Ya-Sin, Ar-Rahman, Al-Mulk)',
                        'Surahs 112, 113, and 114 (Al-Ikhlas, Al-Falaq, An-Nas)',
                    ],
                    'correctAnswerIndex' => 1,
                    'explanation' => 'The two parchment leaves of the Birmingham Quran Manuscript contain verses from Surah Al-Kahf (18), Surah Maryam (19), and Surah Ta-Ha (20) written in early Hijazi script.',
                    'reference' => 'University of Birmingham Mingana Collection (Islamic Arabic 1572a)',
                ],
            ],
            [
                'difficulty' => 'Hard',
                'title' => 'Tawatur and the Ten Canonical Qira\'at',
                'content' => 'In Quranic sciences, a valid recitation (Qira\'ah) must satisfy three strict pillars established by scholars such as Ibn Mujahid and Ibn al-Jazari: (1) unbroken mass transmission (Tawatur / Sahih Isnad) back to the Prophet ﷺ, (2) conformity with Arabic grammatical rules, and (3) agreement with the consonantal skeleton (Rasm) of at least one of the official Uthmanic Masahif.',
                'question' => [
                    'id' => 'q-qh-fallback-hard-3',
                    'difficulty' => 'Hard',
                    'theme' => 'Quranic History',
                    'text' => 'Which of the following is NOT one of the three classical conditions required by Ibn al-Jazari for an authentic Quranic Qira\'ah?',
                    'options' => [
                        'Authentic, continuous chain of transmission back to the Prophet ﷺ',
                        'Conformity with the consonantal script (Rasm) of an Uthmanic Mushaf',
                        'Agreement with established rules of the Arabic language',
                        'Origination exclusively in Kufa during the Abbasid era',
                    ],
                    'correctAnswerIndex' => 3,
                    'explanation' => 'The three pillars of an accepted Qira\'ah are authentic transmission, agreement with the Uthmanic Rasm, and soundness in Arabic grammar—recitations came from Makkah, Madinah, Basra, Damascus, and Kufa.',
                    'reference' => 'Al-Nashr fi al-Qira\'at al-\'Ashr by Ibn al-Jazari',
                ],
            ],
        ];
    }

    public function getCuratedSeerahInsights(): array
    {
        return [
            [
                'difficulty' => 'Easy',
                'title' => 'Al-Sadiq Al-Amin: The Rebuilding of the Ka\'bah',
                'content' => 'Five years before prophethood, when the Quraysh rebuilt the Ka\'bah after a flood, a fierce dispute broke out among the tribes over who would have the honor of placing the Black Stone (Al-Hajar Al-Aswad) back in its corner. They agreed to accept the judgment of the next person to enter the sanctuary—who was Muhammad ﷺ, known to all as Al-Amin (The Trustworthy). He placed the stone on a cloak and asked a representative from each tribe to hold an edge and lift it together, preventing bloodshed through wisdom and unity.',
                'question' => [
                    'id' => 'q-seerah-fallback-easy-1',
                    'difficulty' => 'Easy',
                    'theme' => 'Prophet Muhammad\'s Early Life',
                    'text' => 'How did Prophet Muhammad ﷺ resolve the dispute among the tribes of Quraysh over placing the Black Stone (Al-Hajar Al-Aswad)?',
                    'options' => [
                        'He placed the stone on a cloak and had leaders of each tribe lift it together',
                        'He drew lots among the tribes to pick a single tribe',
                        'He postponed the placement for a year',
                        'He delegated the decision to the elders of Ta\'if',
                    ],
                    'correctAnswerIndex' => 0,
                    'explanation' => 'By placing the Black Stone on a cloak and inviting every clan leader to lift a corner together before setting it in place with his own hands, he honored every tribe equally and prevented war.',
                    'reference' => 'Sirat Ibn Hisham; Ar-Raheeq Al-Makhtum',
                ],
            ],
            [
                'difficulty' => 'Medium',
                'title' => 'The Charter of Madinah (Sahifat al-Madinah)',
                'content' => 'Upon arriving in Madinah after the Hijrah (1 AH / 622 CE), Prophet Muhammad ﷺ established brotherhood (Mu\'akhah) between the Muhajirun and Ansar and drafted the Charter of Madinah (Sahifat al-Madinah). Recognized by historians as one of the earliest written constitutions in world history, it defined a pluralistic civic community (Ummah) guaranteeing religious freedom, mutual defense, justice, and legal rights for Muslims and allied Jewish and Arab tribes.',
                'question' => [
                    'id' => 'q-seerah-fallback-medium-1',
                    'difficulty' => 'Medium',
                    'theme' => 'Life in Madinah',
                    'text' => 'What was the primary purpose of the Charter of Madinah (Sahifat al-Madinah) drafted shortly after the Hijrah?',
                    'options' => [
                        'To establish a constitutional framework of mutual rights, religious freedom, and collective defense in Madinah',
                        'To regulate trade tariffs with the Byzantine Empire',
                        'To record agricultural land boundaries in Khaybar',
                        'To appoint governors for Yemen and Bahrain',
                    ],
                    'correctAnswerIndex' => 0,
                    'explanation' => 'Sahifat al-Madinah established a formal civic covenant uniting the Muhajirun, Ansar, and resident tribes of Madinah under shared justice, religious autonomy, and mutual defense.',
                    'reference' => 'Sirat Ibn Ishaq / Ibn Hisham',
                ],
            ],
            [
                'difficulty' => 'Hard',
                'title' => 'The Treaty of Hudaybiyyah: The Clear Victory (Fath Mubin)',
                'content' => 'In 6 AH (628 CE), Prophet Muhammad ﷺ led 1,400 unarmed pilgrims toward Makkah for \'Umrah. Stopped at Hudaybiyyah, he negotiated a ten-year peace treaty with Quraysh envoy Suhayl ibn \'Amr. Although some clauses initially appeared unfavorable to the companions, Surah Al-Fath (48:1) declared it a "Clear Victory" (Fathan Mubina). The peace enabled open dialogue across Arabia, resulting in more people embracing Islam in the two years following Hudaybiyyah than in the entire preceding 19 years combined.',
                'question' => [
                    'id' => 'q-seerah-fallback-hard-1',
                    'difficulty' => 'Hard',
                    'theme' => 'Life in Madinah',
                    'text' => 'Which Surah was revealed during the return from Hudaybiyyah, describing the peace treaty as a "Clear Victory" (Fathan Mubina)?',
                    'options' => [
                        'Surah Al-Anfal',
                        'Surah Al-Fath',
                        'Surah At-Tawbah',
                        'Surah An-Nasr',
                    ],
                    'correctAnswerIndex' => 1,
                    'explanation' => 'Surah Al-Fath (Chapter 48) was revealed following the Treaty of Hudaybiyyah in 6 AH, affirming that the truce was a decisive strategic and spiritual victory.',
                    'reference' => 'Sahih al-Bukhari 4172, Surah Al-Fath 48:1',
                ],
            ],
        ];
    }

    protected function getFallbackQuranHistoryInsight(string $difficulty = 'Medium'): array
    {
        $all = collect($this->getCuratedQuranHistoryInsights());
        $filtered = $all->where('difficulty', $difficulty)->values();
        if ($filtered->isEmpty()) {
            $filtered = $all;
        }
        return $filtered->random();
    }

    protected function getFallbackSeerahInsight(string $difficulty = 'Medium'): array
    {
        $all = collect($this->getCuratedSeerahInsights());
        $filtered = $all->where('difficulty', $difficulty)->values();
        if ($filtered->isEmpty()) {
            $filtered = $all;
        }
        return $filtered->random();
    }

    /**
     * Suggest up to three chapter:verse keys related to a search that did not match the stored text.
     *
     * @return array<int, string>
     */
    public function suggestPatternVerses(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $prompt = "A reader searched the Quran for: {$query}\n"
            . "Suggest up to 3 verse keys whose wording may relate to that search.\n"
            . "Return JSON with key verses, an array of strings like \"86:1\".\n"
            . "Use only real chapter:verse keys. Do not explain. If you are unsure, return an empty array.\n"
            . "Do not invent scientific claims.";

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'verses' => [
                    'type' => 'ARRAY',
                    'items' => ['type' => 'STRING'],
                ],
            ],
            'required' => ['verses'],
        ];

        $result = $this->callGemini($prompt, $schema);
        if (!$result) {
            return [];
        }

        $decoded = json_decode($result, true);
        if (!is_array($decoded) || !isset($decoded['verses']) || !is_array($decoded['verses'])) {
            return [];
        }

        $keys = [];
        foreach ($decoded['verses'] as $verse) {
            if (is_string($verse) && $verse !== '') {
                $keys[] = $verse;
            }
            if (count($keys) >= 3) {
                break;
            }
        }

        return $keys;
    }

    /**
     * Study notes for one verse. Counts are computed elsewhere and must not be invented here.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>|null
     */
    public function generatePatternNotes(array $context): ?array
    {
        $topics = 'Biology, Cosmology, Physics, Archeology, Climatology';
        $passage = (string) ($context['passage'] ?? '');
        $focus = (string) ($context['focus'] ?? '');
        $prompt = "You write a short study note for one Quran verse. Use only the passage below.\n"
            . "Do not invent counts, scientific equivalences, chromosome numbers, land-sea ratios, or distances in light years.\n\n"
            . "Surah {$context['surah_name']} ({$context['verse_key']}).\n"
            . "Focus (may be empty): {$focus}\n"
            . "Verse Arabic: {$context['arabic']}\n"
            . "Verse translation: {$context['translation']}\n\n"
            . "Passage:\n{$passage}\n\n"
            . "Return JSON with:\n"
            . "- gloss: a short English sense of the focus, taken from the translation. If there is no focus, gloss the opening of the translation in a few words.\n"
            . "- arabic_term: an Arabic word that appears in the verse. Prefer the focus when it appears in the verse.\n"
            . "- ring_note: one or two sentences on ring or symmetry visible in the passage. If none is visible, say that no clear ring is established for this passage from the text provided.\n"
            . "- linguistic_note: one or two sentences that quote arabic_term and comment on its sound or meaning using the translation. Do not add scientific claims.\n"
            . "- topics: an array of at most 2 values, each one of: {$topics}. Use an empty array when none fit.";

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'gloss' => ['type' => 'STRING'],
                'arabic_term' => ['type' => 'STRING'],
                'ring_note' => ['type' => 'STRING'],
                'linguistic_note' => ['type' => 'STRING'],
                'topics' => [
                    'type' => 'ARRAY',
                    'items' => ['type' => 'STRING'],
                ],
            ],
            'required' => ['gloss', 'arabic_term', 'ring_note', 'linguistic_note', 'topics'],
        ];

        $result = $this->callGemini($prompt, $schema);
        if (!$result) {
            return null;
        }

        $decoded = json_decode($result, true);
        if (!is_array($decoded)) {
            return null;
        }

        return $decoded;
    }
}


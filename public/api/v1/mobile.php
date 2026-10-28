<?php

@header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// CORS: native clients typically omit Origin. Browser callers must match MOBILE_CORS_ORIGINS.
$corsOrigins = array_values(array_filter(array_map('trim', explode(',', (string) env('MOBILE_CORS_ORIGINS', '')))));
$requestOrigin = isset($_SERVER['HTTP_ORIGIN']) ? (string) $_SERVER['HTTP_ORIGIN'] : '';
if ($requestOrigin !== '') {
    if (in_array('*', $corsOrigins, true) || in_array($requestOrigin, $corsOrigins, true)) {
        $allowOrigin = in_array('*', $corsOrigins, true) ? '*' : $requestOrigin;
        @header('Access-Control-Allow-Origin: ' . $allowOrigin);
        @header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        @header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');
        @header('Vary: Origin');
    }
}
if (isset($_SERVER['REQUEST_METHOD']) && strtoupper((string) $_SERVER['REQUEST_METHOD']) === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!function_exists('check_rate_limit')) {
    function check_rate_limit()
    {
        if (php_sapi_name() === 'cli') {
            return;
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        $cacheDir = __DIR__ . '/../../../storage/framework/cache/rate_limit';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $ipHash = md5($ip);
        $cacheFile = $cacheDir . '/' . $ipHash . '.json';

        $currentTime = time();
        $limit = 100; // max 100 requests
        $period = 60; // per 60 seconds

        if (file_exists($cacheFile)) {
            $data = json_decode(@file_get_contents($cacheFile), true);
            if (is_array($data)) {
                $resetTime = $data['start_time'] + $period;
                if ($currentTime < $resetTime) {
                    if ($data['count'] >= $limit) {
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'Too many requests. Please try again later.'
                        ]);
                        exit;
                    }
                    $data['count']++;
                } else {
                    $data = [
                        'start_time' => $currentTime,
                        'count' => 1
                    ];
                }
            } else {
                $data = [
                    'start_time' => $currentTime,
                    'count' => 1
                ];
            }
        } else {
            $data = [
                'start_time' => $currentTime,
                'count' => 1
            ];
        }

        @file_put_contents($cacheFile, json_encode($data));
    }
}

check_rate_limit();

if (!function_exists('get_db_connection')) {
    function get_db_connection()
    {
        $envPath = __DIR__ . '/../../../.env';
        $config = [
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_DATABASE' => '',
            'DB_USERNAME' => '',
            'DB_PASSWORD' => ''
        ];

        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0)
                    continue;

                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $value = trim($parts[1]);
                    // Strip quotes
                    if (
                        (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                        (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)
                    ) {
                        $value = substr($value, 1, -1);
                    }
                    if (array_key_exists($key, $config)) {
                        $config[$key] = $value;
                    }
                }
            }
        }

        if ($config['DB_CONNECTION'] === 'sqlite') {
            $dbPath = $config['DB_DATABASE'];
            if (!file_exists($dbPath)) {
                $relativeDir = __DIR__ . '/../../../';
                if (file_exists($relativeDir . $dbPath)) {
                    $dbPath = $relativeDir . $dbPath;
                }
            }
            $dsn = "sqlite:{$dbPath}";
            return new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } else {
            try {
                $dsn = "mysql:host={$config['DB_HOST']};port={$config['DB_PORT']};dbname={$config['DB_DATABASE']};charset=utf8mb4";
                return new PDO($dsn, $config['DB_USERNAME'], $config['DB_PASSWORD'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 2,
                ]);
            } catch (Exception $e) {
                $sqliteFile = __DIR__ . '/../../../database/database.sqlite';
                if (file_exists($sqliteFile)) {
                    return new PDO("sqlite:{$sqliteFile}", null, null, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                }
                throw $e;
            }
        }
    }
}

try {
    $pdo = get_db_connection();

    // Determine database driver
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $isSqlite = ($driver === 'sqlite');

    // 1. Detect dynamic columns for surahs table
    $nameEnglishCol = 'name_english';
    $revelationCol = 'revelation_type';
    $verseCountCol = 'verse_count';

    if ($isSqlite) {
        $stmtCols = $pdo->query("PRAGMA table_info(surahs)");
        $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('name_english', $cols)) {
            $nameEnglishCol = 'name_translated';
        }
        if (!in_array('revelation_type', $cols)) {
            $revelationCol = 'revelation_place';
        }
        if (!in_array('verse_count', $cols)) {
            $verseCountCol = 'verses_count';
        }
    } else {
        try {
            $stmtCols = $pdo->query("SHOW COLUMNS FROM surahs LIKE 'name_english'");
            if (!$stmtCols->fetch()) {
                $nameEnglishCol = 'name_translated';
            }
            $stmtRev = $pdo->query("SHOW COLUMNS FROM surahs LIKE 'revelation_type'");
            if (!$stmtRev->fetch()) {
                $revelationCol = 'revelation_place';
            }
            $stmtCount = $pdo->query("SHOW COLUMNS FROM surahs LIKE 'verse_count'");
            if (!$stmtCount->fetch()) {
                $verseCountCol = 'verses_count';
            }
        } catch (Exception $e) {
            $nameEnglishCol = 'name_translated';
            $revelationCol = 'revelation_place';
            $verseCountCol = 'verses_count';
        }
    }

    // 2. Detect dynamic columns for verses table
    $juzCol = 'juz';
    $hasVerseKey = false;

    if ($isSqlite) {
        $stmtCols = $pdo->query("PRAGMA table_info(verses)");
        $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('juz', $cols)) {
            $juzCol = 'juz_number';
        }
        $hasVerseKey = in_array('verse_key', $cols);
    } else {
        try {
            $stmtCols = $pdo->query("SHOW COLUMNS FROM verses LIKE 'juz'");
            if (!$stmtCols->fetch()) {
                $juzCol = 'juz_number';
            }
            $stmtKey = $pdo->query("SHOW COLUMNS FROM verses LIKE 'verse_key'");
            $hasVerseKey = (bool) $stmtKey->fetch();
        } catch (Exception $e) {
            $juzCol = 'juz_number';
            $hasVerseKey = false;
        }
    }

    if ($hasVerseKey) {
        $verseKeySelect = "v.verse_key";
    } else {
        if ($isSqlite) {
            $verseKeySelect = "s.number || ':' || v.verse_number";
        } else {
            $verseKeySelect = "CONCAT(s.number, ':', v.verse_number)";
        }
    }

    // 3. Detect dynamic columns/tables for history table
    $historyTable = 'historical_events';
    $historyForeignKey = 'historical_event_id';
    $historyExtraCol = 'civilization';
    $hasHistEvents = false;
    if ($isSqlite) {
        $stmtTabs = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='historical_events'");
        $hasHistEvents = (bool) $stmtTabs->fetch();
    } else {
        try {
            $stmtTabs = $pdo->query("SHOW TABLES LIKE 'historical_events'");
            $hasHistEvents = (bool) $stmtTabs->fetch();
        } catch (Exception $e) {
            $hasHistEvents = false;
        }
    }
    if (!$hasHistEvents) {
        $historyTable = 'history_contexts';
        $historyForeignKey = 'history_context_id';
        $historyExtraCol = 'historical_period';
    }

    // Unify input data from GET, POST, and raw JSON input stream
    $rawInput = @file_get_contents('php://input');
    $jsonInput = !empty($rawInput) ? @json_decode($rawInput, true) : [];
    if (!is_array($jsonInput)) {
        $jsonInput = [];
    }
    $requestData = array_merge($_GET, $_POST, $jsonInput);

    // Helper function for user streak tracking
    if (!function_exists('calculate_user_streak')) {
        function calculate_user_streak($pdo, $userId, $shouldCheckIn = false) {
            try {
                $stmt = $pdo->prepare("SELECT id, streak_count, longest_streak, last_streak_date, streak_history FROM users WHERE id = :id");
                $stmt->execute(['id' => $userId]);
                $user = $stmt->fetch();
            } catch (Exception $e) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id");
                $stmt->execute(['id' => $userId]);
                $user = $stmt->fetch();
            }
            if (!$user) {
                return null;
            }

            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $lastDate = !empty($user['last_streak_date']) ? date('Y-m-d', strtotime($user['last_streak_date'])) : null;
            $history = json_decode($user['streak_history'] ?? '[]', true) ?: [];

            $isActiveToday = ($lastDate === $today);
            $currentStreak = (int)($user['streak_count'] ?? 0);
            $longestStreak = (int)($user['longest_streak'] ?? 0);

            if ($shouldCheckIn) {
                if (!$isActiveToday) {
                    if ($lastDate === $yesterday) {
                        $currentStreak++;
                    } else {
                        $currentStreak = 1;
                    }

                    if ($currentStreak > $longestStreak) {
                        $longestStreak = $currentStreak;
                    }

                    $lastDate = $today;
                    if (!in_array($today, $history)) {
                        $history[] = $today;
                        if (count($history) > 60) {
                            $history = array_slice($history, -60);
                        }
                    }

                    try {
                        $stmt = $pdo->prepare("UPDATE users SET streak_count = :sc, longest_streak = :ls, last_streak_date = :lsd, streak_history = :sh WHERE id = :id");
                        $stmt->execute([
                            'sc' => $currentStreak,
                            'ls' => $longestStreak,
                            'lsd' => $today,
                            'sh' => json_encode($history),
                            'id' => $userId
                        ]);
                    } catch (Exception $e) {
                        // Gracefully ignore column errors if schema differs
                    }
                    $isActiveToday = true;
                }
            } else {
                if ($lastDate && $lastDate < $yesterday && !$isActiveToday) {
                    $currentStreak = 0;
                }
            }

            return [
                'current_streak' => $currentStreak,
                'longest_streak' => $longestStreak,
                'is_active_today' => $isActiveToday,
                'last_streak_date' => $lastDate,
                'streak_history' => $history
            ];
        }
    }

    // Daily engagement helpers (Laravel Eloquent service — source of truth)
    if (!function_exists('mobile_engagement_status')) {
        function mobile_engagement_status(int $userId): ?array
        {
            $user = \App\Models\User::find($userId);
            if (!$user) {
                return null;
            }
            return app(\App\Services\DailyEngagementService::class)->todayStatus($user);
        }
    }

    if (!function_exists('mobile_engagement_record')) {
        function mobile_engagement_record(int $userId, string $activity): ?array
        {
            $user = \App\Models\User::find($userId);
            if (!$user) {
                return null;
            }
            return app(\App\Services\DailyEngagementService::class)->record($user, $activity);
        }
    }

    $action = isset($requestData['action']) ? trim((string)$requestData['action']) : '';

    $authRequiredActions = [
        'set_password',
        'change_password',
        'delete_account',
        'submit_quiz',
        'check_answer',
        'daily_engagement',
        'mark_engagement',
        'toggle_surah',
        'toggle_para',
        'mark_insight',
        'daily_streak',
        'streak_check_in',
        'streak_status',
        'user_stats',
        'submit_dua',
        'submit_research_contribution',
        'add_category',
        'review_research_contribution',
        'logout',
    ];
    $authUser = null;
    if (in_array($action, $authRequiredActions, true)) {
        $authUser = \App\Services\MobileApiAuthService::requireUser();
    }

    switch ($action) {
        case 'surahs':
            $stmt = $pdo->query("SELECT id, number, name_arabic, name_transliteration, {$nameEnglishCol} as name_translated, {$revelationCol} as revelation_place, {$verseCountCol} as verses_count FROM surahs ORDER BY number ASC");
            $surahs = $stmt->fetchAll();
            echo json_encode([
                'status' => 'success',
                'data' => $surahs
            ]);
            break;

        case 'verses':
            $surahNumber = isset($_GET['surah_number']) ? (int) $_GET['surah_number'] : 1;

            $stmt = $pdo->prepare("SELECT v.id, v.verse_number, {$verseKeySelect} as verse_key, v.{$juzCol} as juz_number, v.text_arabic, v.text_transliteration 
                                   FROM verses v
                                   JOIN surahs s ON v.surah_id = s.id
                                   WHERE s.number = ? 
                                   ORDER BY v.verse_number ASC");
            $stmt->execute([$surahNumber]);
            $verses = $stmt->fetchAll();

            $editions = [];
            try {
                $translationService = app(\App\Services\QuranTranslationService::class);
                $byVerse = $translationService->forSurah($surahNumber);
                $editions = $translationService->editionsPresent($byVerse);
                foreach ($verses as &$verse) {
                    $verseNumber = (int) ($verse['verse_number'] ?? 0);
                    $verse['translations'] = $byVerse[$verseNumber] ?? [];
                }
                unset($verse);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Unable to attach web translations to mobile verses', [
                    'surah' => $surahNumber,
                    'message' => $e->getMessage(),
                ]);
                foreach ($verses as &$verse) {
                    $verse['translations'] = [];
                }
                unset($verse);
            }

            echo json_encode([
                'status' => 'success',
                'surah_number' => $surahNumber,
                'editions' => $editions,
                'data' => $verses
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'verse_translations':
            $surahNumber = isset($_GET['surah_number']) ? (int) $_GET['surah_number'] : 0;
            $verseNumber = isset($_GET['verse_number']) ? (int) $_GET['verse_number'] : 0;
            if ($surahNumber < 1 || $verseNumber < 1) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Surah and verse are required.'
                ]);
                break;
            }

            $local = [];
            try {
                $verseStmt = $pdo->prepare("SELECT v.id FROM verses v JOIN surahs s ON v.surah_id = s.id WHERE s.number = ? AND v.verse_number = ? LIMIT 1");
                $verseStmt->execute([$surahNumber, $verseNumber]);
                $verseRow = $verseStmt->fetch();
                if ($verseRow) {
                    $transStmt = $pdo->prepare("SELECT translator, text, language FROM translations WHERE verse_id = ?");
                    $transStmt->execute([(int) $verseRow['id']]);
                    foreach ($transStmt->fetchAll() as $row) {
                        $local[] = [
                            'author_name' => trim((string) ($row['translator'] ?? '')) ?: 'Translation',
                            'text' => (string) ($row['text'] ?? ''),
                            'language' => (string) ($row['language'] ?? 'en'),
                        ];
                    }
                }
            } catch (\Throwable $e) {
                $local = [];
            }

            $remote = [];
            try {
                $remote = app(\App\Services\QuranTranslationService::class)->forVerse($surahNumber, $verseNumber);
            } catch (\Throwable $e) {
                $remote = [];
            }

            $merged = [];
            try {
                $merged = app(\App\Services\QuranTranslationService::class)->mergeWithLocal($local, $remote);
            } catch (\Throwable $e) {
                $merged = $remote;
            }

            $knownIds = [
                'Saheeh International' => 'en.sahih',
                'Pickthall' => 'en.pickthall',
                'Yusuf Ali' => 'en.yusufali',
                'Hilali & Khan' => 'en.hilali',
                'Jalandhry (Urdu)' => 'ur.jalandhry',
                'Maududi (Urdu)' => 'ur.maududi',
            ];
            $data = [];
            $usedIds = [];
            foreach ($merged as $index => $item) {
                $text = trim((string) ($item['text'] ?? ''));
                if ($text === '') {
                    continue;
                }
                $name = trim((string) ($item['author_name'] ?? 'Translation')) ?: 'Translation';
                $id = $knownIds[$name] ?? '';
                if ($id === '') {
                    $id = strtolower((string) preg_replace('/[^a-z0-9]+/i', '.', $name));
                    $id = trim($id, '.');
                }
                if ($id === '') {
                    $id = 'translation.' . $index;
                }
                if (isset($usedIds[$id])) {
                    $id .= '.' . $index;
                }
                $usedIds[$id] = true;
                $data[] = [
                    'id' => $id,
                    'name' => $name,
                    'language' => (string) ($item['language'] ?? 'en'),
                    'direction' => (string) ($item['direction'] ?? 'ltr'),
                    'text' => $text,
                ];
            }

            echo json_encode([
                'status' => 'success',
                'surah_number' => $surahNumber,
                'verse_number' => $verseNumber,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'connections':
            $surahNumber = isset($_GET['surah_number']) ? (int) $_GET['surah_number'] : 1;
            $verseNumber = isset($_GET['verse_number']) ? (int) $_GET['verse_number'] : 1;

            // 1. Science Links
            $stmtSci = $pdo->prepare("SELECT f.title, f.description, f.field, l.relevance_description, 
                                             f.source_name, f.credibility_score, f.source_url, l.status
                                       FROM quran_science_links l
                                       JOIN verses v ON l.verse_id = v.id
                                       JOIN surahs s ON v.surah_id = s.id
                                       JOIN science_facts f ON l.science_fact_id = f.id
                                       WHERE s.number = ? AND v.verse_number = ? AND l.status = 'approved'");
            $stmtSci->execute([$surahNumber, $verseNumber]);
            $scienceLinks = $stmtSci->fetchAll();

            // 2. Seerat Links
            $stmtSeer = $pdo->prepare("SELECT e.title, e.description, e.category, e.date_hijri, e.date_ce, 
                                              e.location, e.source_book, e.source_reference, 
                                              l.description as link_description, l.context_type, l.status
                                       FROM quran_seerat_links l
                                       JOIN verses v ON l.verse_id = v.id
                                       JOIN surahs s ON v.surah_id = s.id
                                       JOIN seerat_events e ON l.seerat_event_id = e.id
                                       WHERE s.number = ? AND v.verse_number = ? AND l.status = 'approved'");
            $stmtSeer->execute([$surahNumber, $verseNumber]);
            $seeratLinks = $stmtSeer->fetchAll();

            // 3. Hadith Links
            $hadithTextCol = 'text_english';
            $hadithCollCol = 'name_english';
            if ($isSqlite) {
                $stmtCols = $pdo->query("PRAGMA table_info(ahadith)");
                $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 1);
                if (!in_array('text_english', $cols)) {
                    $hadithTextCol = 'hadith_translation';
                }
                $stmtCollCols = $pdo->query("PRAGMA table_info(hadith_collections)");
                $collCols = $stmtCollCols->fetchAll(PDO::FETCH_COLUMN, 1);
                if (!in_array('name_english', $collCols)) {
                    $hadithCollCol = 'name';
                }
            }
            $stmtHad = $pdo->prepare("SELECT a.hadith_number, a.{$hadithTextCol} as text, a.text_arabic, 
                                             a.narrator_chain, a.grading, c.{$hadithCollCol} as collection_name, 
                                             l.description as link_description, l.status
                                       FROM quran_hadith_links l
                                       JOIN verses v ON l.verse_id = v.id
                                       JOIN surahs s ON v.surah_id = s.id
                                       JOIN ahadith a ON l.hadith_id = a.id
                                       LEFT JOIN hadith_collections c ON a.collection_id = c.id
                                       WHERE s.number = ? AND v.verse_number = ? AND l.status = 'approved'");
            $stmtHad->execute([$surahNumber, $verseNumber]);
            $hadithLinks = $stmtHad->fetchAll();

            // 4. History Links
            $dateRangeCol = $hasHistEvents ? "h.date_range" : "'' as date_range";
            $regionCol = $hasHistEvents ? "h.region" : "'' as region";
            $civCol = $hasHistEvents ? "h.civilization" : "h.{$historyExtraCol} as civilization";
            $stmtHist = $pdo->prepare("SELECT h.title, h.description, {$civCol}, {$regionCol}, {$dateRangeCol}, 
                                              h.{$historyExtraCol} as extra_info, 
                                              l.description as link_description, l.status
                                       FROM quran_history_links l
                                       JOIN verses v ON l.verse_id = v.id
                                       JOIN surahs s ON v.surah_id = s.id
                                       JOIN {$historyTable} h ON l.{$historyForeignKey} = h.id
                                       WHERE s.number = ? AND v.verse_number = ? AND l.status = 'approved'");
            $stmtHist->execute([$surahNumber, $verseNumber]);
            $historyLinks = $stmtHist->fetchAll();

            // 5. Scripture Links
            $bibleTitleSelect = "CONCAT(b.book, ' ', b.chapter, ':', b.verse_number)";
            $bibleContentCol = "b.text_niv";
            $bibleExtraCol = "b.testament";
            $torahTitleSelect = "CONCAT(t.book, ' ', t.chapter, ':', t.verse_number)";
            $torahContentCol = "t.text_english";
            $hasBibleBook = false;
            if ($isSqlite) {
                $stmtCols = $pdo->query("PRAGMA table_info(bible_verses)");
                $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 1);
                $hasBibleBook = in_array('book', $cols);
            } else {
                try {
                    $stmtCols = $pdo->query("SHOW COLUMNS FROM bible_verses LIKE 'book'");
                    $hasBibleBook = (bool) $stmtCols->fetch();
                } catch (Exception $e) {
                    $hasBibleBook = false;
                }
            }
            if ($isSqlite) {
                if ($hasBibleBook) {
                    $bibleTitleSelect = "b.book || ' ' || b.chapter || ':' || b.verse_number";
                    $bibleContentCol = "b.text_niv";
                    $bibleExtraCol = "b.testament";
                    $torahTitleSelect = "t.book || ' ' || t.chapter || ':' || t.verse_number";
                    $torahContentCol = "t.text_english";
                } else {
                    $bibleTitleSelect = "b.verse_reference";
                    $bibleContentCol = "b.text";
                    $bibleExtraCol = "'Bible'";
                    $torahTitleSelect = "t.section_reference";
                    $torahContentCol = "t.text";
                }
            } else {
                if (!$hasBibleBook) {
                    $bibleTitleSelect = "b.verse_reference";
                    $bibleContentCol = "b.text";
                    $bibleExtraCol = "'Bible'";
                    $torahTitleSelect = "t.section_reference";
                    $torahContentCol = "t.text";
                }
            }
            $stmtScrip = $pdo->prepare("SELECT 
                                           l.id,
                                           l.relationship_type,
                                           l.description as link_description,
                                           l.status,
                                           CASE WHEN l.bible_verse_id IS NOT NULL THEN 'Bible' ELSE 'Torah' END as scripture_type,
                                           CASE WHEN l.bible_verse_id IS NOT NULL THEN {$bibleTitleSelect} ELSE {$torahTitleSelect} END as title,
                                           CASE WHEN l.bible_verse_id IS NOT NULL THEN {$bibleContentCol} ELSE {$torahContentCol} END as text,
                                           CASE WHEN l.bible_verse_id IS NOT NULL THEN {$bibleExtraCol} ELSE 'Torah' END as extra_info
                                       FROM quran_scripture_links l
                                       JOIN verses v ON l.verse_id = v.id
                                       JOIN surahs s ON v.surah_id = s.id
                                       LEFT JOIN bible_verses b ON l.bible_verse_id = b.id
                                       LEFT JOIN torah_sections t ON l.torah_section_id = t.id
                                       WHERE s.number = ? AND v.verse_number = ? AND l.status = 'approved'");
            $stmtScrip->execute([$surahNumber, $verseNumber]);
            $scriptureLinks = $stmtScrip->fetchAll();

            echo json_encode([
                'status' => 'success',
                'surah_number' => $surahNumber,
                'verse_number' => $verseNumber,
                'data' => [
                    'science' => $scienceLinks,
                    'seerah' => $seeratLinks,
                    'hadith' => $hadithLinks,
                    'history' => $historyLinks,
                    'scripture' => $scriptureLinks,
                ]
            ]);
            break;

        case 'all_connections':
            $category = isset($_GET['category']) ? strtolower(trim((string)$_GET['category'])) : 'all';
            $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
            $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 50) : 20;
            $offset = ($page - 1) * $limit;
            $surahFilter = isset($_GET['surah_number']) && is_numeric($_GET['surah_number']) ? (int)$_GET['surah_number'] : null;

            // Stats across all categories
            $sciCount = (int)$pdo->query("SELECT COUNT(*) FROM quran_science_links WHERE status = 'approved'")->fetchColumn();
            $seerCount = (int)$pdo->query("SELECT COUNT(*) FROM quran_seerat_links WHERE status = 'approved'")->fetchColumn();
            $hadCount = (int)$pdo->query("SELECT COUNT(*) FROM quran_hadith_links WHERE status = 'approved'")->fetchColumn();
            $histCount = (int)$pdo->query("SELECT COUNT(*) FROM quran_history_links WHERE status = 'approved'")->fetchColumn();
            $scripCount = (int)$pdo->query("SELECT COUNT(*) FROM quran_scripture_links WHERE status = 'approved'")->fetchColumn();
            $totalCount = $sciCount + $seerCount + $hadCount + $histCount + $scripCount;

            $stats = [
                'surahs_count' => 114,
                'science_count' => $sciCount,
                'seerah_count' => $seerCount,
                'hadith_count' => $hadCount,
                'history_count' => $histCount,
                'scripture_count' => $scripCount,
                'total_count' => $totalCount,
            ];

            $hadithTextCol = 'text_english';
            $hadithCollCol = 'name_english';
            if ($isSqlite) {
                $stmtCols = $pdo->query("PRAGMA table_info(ahadith)");
                $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN, 1);
                if (!in_array('text_english', $cols)) {
                    $hadithTextCol = 'hadith_translation';
                }
                $stmtCollCols = $pdo->query("PRAGMA table_info(hadith_collections)");
                $collCols = $stmtCollCols->fetchAll(PDO::FETCH_COLUMN, 1);
                if (!in_array('name_english', $collCols)) {
                    $hadithCollCol = 'name';
                }
            }

            // String concatenation expressions for sqlite vs mysql
            $seerSource = $isSqlite ? "(COALESCE(e.source_book, '') || ' ' || COALESCE(e.source_reference, ''))" : "CONCAT(COALESCE(e.source_book, ''), ' ', COALESCE(e.source_reference, ''))";
            $seerDate = $isSqlite ? "(COALESCE(e.date_hijri, '') || ' AH / ' || COALESCE(e.date_ce, '') || ' CE')" : "CONCAT(COALESCE(e.date_hijri, ''), ' AH / ', COALESCE(e.date_ce, ''), ' CE')";
            $scripBibleTitle = $isSqlite ? "(b.book || ' ' || b.chapter || ':' || b.verse_number)" : "CONCAT(b.book, ' ', b.chapter, ':', b.verse_number)";
            $scripTorahTitle = $isSqlite ? "(t.book || ' ' || t.chapter || ':' || t.verse_number)" : "CONCAT(t.book, ' ', t.chapter, ':', t.verse_number)";
            $hadithTitle = $isSqlite ? "(c.{$hadithCollCol} || ' (#' || a.hadith_number || ')')" : "CONCAT(c.{$hadithCollCol}, ' (#', a.hadith_number, ')')";

            $subqueries = [];

            if ($category === 'all' || $category === 'science') {
                $subqueries[] = "
                    SELECT 
                        l.id as id,
                        'science' as category,
                        s.number as surah_number,
                        s.name_transliteration as surah_name,
                        v.verse_number as verse_number,
                        v.juz as juz_number,
                        v.text_arabic as verse_arabic,
                        v.text_transliteration as verse_transliteration,
                        f.title as title,
                        f.description as description,
                        f.field as extra_info,
                        COALESCE(l.relevance_description, '') as relevance_description,
                        COALESCE(f.field, '') as field,
                        COALESCE(f.source_name, '') as source_name,
                        COALESCE(f.credibility_score, '') as credibility_score,
                        '' as date_info,
                        '' as location,
                        '' as arabic_text,
                        '' as narrator_chain,
                        '' as grading,
                        '' as collection_name,
                        '' as hadith_number,
                        '' as scripture_type,
                        '' as relationship_type
                    FROM quran_science_links l
                    JOIN verses v ON l.verse_id = v.id
                    JOIN surahs s ON v.surah_id = s.id
                    JOIN science_facts f ON l.science_fact_id = f.id
                    WHERE l.status = 'approved'
                ";
            }

            if ($category === 'all' || $category === 'seerah') {
                $subqueries[] = "
                    SELECT 
                        l.id as id,
                        'seerah' as category,
                        s.number as surah_number,
                        s.name_transliteration as surah_name,
                        v.verse_number as verse_number,
                        v.juz as juz_number,
                        v.text_arabic as verse_arabic,
                        v.text_transliteration as verse_transliteration,
                        e.title as title,
                        e.description as description,
                        COALESCE(e.category, '') as extra_info,
                        COALESCE(l.description, '') as relevance_description,
                        '' as field,
                        {$seerSource} as source_name,
                        '' as credibility_score,
                        {$seerDate} as date_info,
                        COALESCE(e.location, '') as location,
                        '' as arabic_text,
                        '' as narrator_chain,
                        '' as grading,
                        '' as collection_name,
                        '' as hadith_number,
                        '' as scripture_type,
                        '' as relationship_type
                    FROM quran_seerat_links l
                    JOIN verses v ON l.verse_id = v.id
                    JOIN surahs s ON v.surah_id = s.id
                    JOIN seerat_events e ON l.seerat_event_id = e.id
                    WHERE l.status = 'approved'
                ";
            }

            if ($category === 'all' || $category === 'hadith') {
                $subqueries[] = "
                    SELECT 
                        l.id as id,
                        'hadith' as category,
                        s.number as surah_number,
                        s.name_transliteration as surah_name,
                        v.verse_number as verse_number,
                        v.juz as juz_number,
                        v.text_arabic as verse_arabic,
                        v.text_transliteration as verse_transliteration,
                        {$hadithTitle} as title,
                        COALESCE(a.{$hadithTextCol}, '') as description,
                        COALESCE(c.{$hadithCollCol}, 'Hadith') as extra_info,
                        COALESCE(l.description, '') as relevance_description,
                        '' as field,
                        COALESCE(c.{$hadithCollCol}, '') as source_name,
                        '' as credibility_score,
                        '' as date_info,
                        '' as location,
                        COALESCE(a.text_arabic, '') as arabic_text,
                        COALESCE(a.narrator_chain, '') as narrator_chain,
                        COALESCE(a.grading, '') as grading,
                        COALESCE(c.{$hadithCollCol}, '') as collection_name,
                        COALESCE(a.hadith_number, '') as hadith_number,
                        '' as scripture_type,
                        '' as relationship_type
                    FROM quran_hadith_links l
                    JOIN verses v ON l.verse_id = v.id
                    JOIN surahs s ON v.surah_id = s.id
                    JOIN ahadith a ON l.hadith_id = a.id
                    LEFT JOIN hadith_collections c ON a.collection_id = c.id
                    WHERE l.status = 'approved'
                ";
            }

            if ($category === 'all' || $category === 'history') {
                $subqueries[] = "
                    SELECT 
                        l.id as id,
                        'history' as category,
                        s.number as surah_number,
                        s.name_transliteration as surah_name,
                        v.verse_number as verse_number,
                        v.juz as juz_number,
                        v.text_arabic as verse_arabic,
                        v.text_transliteration as verse_transliteration,
                        h.title as title,
                        h.description as description,
                        COALESCE(h.civilization, '') as extra_info,
                        COALESCE(l.description, '') as relevance_description,
                        '' as field,
                        '' as source_name,
                        '' as credibility_score,
                        COALESCE(h.date_range, '') as date_info,
                        COALESCE(h.region, '') as location,
                        '' as arabic_text,
                        '' as narrator_chain,
                        '' as grading,
                        '' as collection_name,
                        '' as hadith_number,
                        COALESCE(h.civilization, '') as scripture_type,
                        '' as relationship_type
                    FROM quran_history_links l
                    JOIN verses v ON l.verse_id = v.id
                    JOIN surahs s ON v.surah_id = s.id
                    JOIN {$historyTable} h ON l.{$historyForeignKey} = h.id
                    WHERE l.status = 'approved'
                ";
            }

            if ($category === 'all' || $category === 'scripture') {
                $subqueries[] = "
                    SELECT 
                        l.id as id,
                        'scripture' as category,
                        s.number as surah_number,
                        s.name_transliteration as surah_name,
                        v.verse_number as verse_number,
                        v.juz as juz_number,
                        v.text_arabic as verse_arabic,
                        v.text_transliteration as verse_transliteration,
                        CASE WHEN l.bible_verse_id IS NOT NULL THEN {$scripBibleTitle} ELSE {$scripTorahTitle} END as title,
                        CASE WHEN l.bible_verse_id IS NOT NULL THEN COALESCE(b.text_niv, '') ELSE COALESCE(t.text_english, '') END as description,
                        CASE WHEN l.bible_verse_id IS NOT NULL THEN COALESCE(b.testament, 'Bible') ELSE 'Torah' END as extra_info,
                        COALESCE(l.description, '') as relevance_description,
                        '' as field,
                        '' as source_name,
                        '' as credibility_score,
                        '' as date_info,
                        '' as location,
                        '' as arabic_text,
                        '' as narrator_chain,
                        '' as grading,
                        '' as collection_name,
                        '' as hadith_number,
                        CASE WHEN l.bible_verse_id IS NOT NULL THEN 'Bible' ELSE 'Torah' END as scripture_type,
                        COALESCE(l.relationship_type, '') as relationship_type
                    FROM quran_scripture_links l
                    JOIN verses v ON l.verse_id = v.id
                    JOIN surahs s ON v.surah_id = s.id
                    LEFT JOIN bible_verses b ON l.bible_verse_id = b.id
                    LEFT JOIN torah_sections t ON l.torah_section_id = t.id
                    WHERE l.status = 'approved'
                ";
            }

            $unionSql = implode(" UNION ALL ", $subqueries);

            $whereClauses = [];
            $queryParams = [];

            if ($surahFilter !== null && $surahFilter > 0) {
                $whereClauses[] = "surah_number = :surah_filter";
                $queryParams[':surah_filter'] = $surahFilter;
            }

            if (!empty($search)) {
                $whereClauses[] = "(
                    title LIKE :search 
                    OR description LIKE :search 
                    OR relevance_description LIKE :search 
                    OR extra_info LIKE :search 
                    OR surah_name LIKE :search 
                    OR location LIKE :search 
                    OR source_name LIKE :search
                )";
                $queryParams[':search'] = '%' . $search . '%';
            }

            $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

            // Total count for current filter
            $countSql = "SELECT COUNT(*) FROM ({$unionSql}) as u {$whereSql}";
            $stmtCount = $pdo->prepare($countSql);
            foreach ($queryParams as $k => $v) {
                $stmtCount->bindValue($k, $v);
            }
            $stmtCount->execute();
            $filteredTotal = (int)$stmtCount->fetchColumn();
            $totalPages = max(1, (int)ceil($filteredTotal / $limit));

            // Paginated items
            $itemsSql = "SELECT * FROM ({$unionSql}) as u {$whereSql} ORDER BY surah_number ASC, verse_number ASC, id ASC LIMIT :limit OFFSET :offset";
            $stmtItems = $pdo->prepare($itemsSql);
            foreach ($queryParams as $k => $v) {
                $stmtItems->bindValue($k, $v);
            }
            $stmtItems->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmtItems->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmtItems->execute();
            $items = $stmtItems->fetchAll();

            // Format items to ensure credibilityScore is cleaned up and fields match
            foreach ($items as &$item) {
                if (!empty($item['credibility_score']) && $item['credibility_score'] !== '0' && $item['credibility_score'] !== 'null') {
                    if (!str_contains($item['credibility_score'], '/')) {
                        $item['credibility_score'] = $item['credibility_score'] . '/10';
                    }
                } else {
                    $item['credibility_score'] = '';
                }
            }
            unset($item);

            echo json_encode([
                'status' => 'success',
                'stats' => $stats,
                'category' => $category,
                'page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $filteredTotal,
                'data' => $items
            ]);
            break;

        case 'insights':
            // Web-parity: one daily reflection at a time (limit=1) from GeneratedQuestion
            // SEERAH_INSIGHT / QURAN_HISTORY — same model as /seerah and /quran-history.
            $seerahPageRaw = isset($_GET['seerah_page']) ? trim((string)$_GET['seerah_page']) : 'daily';
            $historyPageRaw = isset($_GET['history_page']) ? trim((string)$_GET['history_page']) : 'daily';
            $limit = isset($_GET['limit']) ? max(1, min((int)$_GET['limit'], 20)) : 1;

            $seerahCategory = isset($_GET['seerah_category']) && $_GET['seerah_category'] !== '' ? trim((string)$_GET['seerah_category']) : null;
            $historyCategory = isset($_GET['history_category']) && $_GET['history_category'] !== '' ? trim((string)$_GET['history_category']) : null;
            if ($seerahCategory === 'All') {
                $seerahCategory = null;
            }
            if ($historyCategory === 'All') {
                $historyCategory = null;
            }

            $insightUser = \App\Services\MobileApiAuthService::userFromRequest();

            $resolveInsightPage = static function (string $raw, int $total): int {
                if ($total <= 0) {
                    return 1;
                }
                if ($raw === '' || strtolower($raw) === 'daily' || $raw === '0') {
                    return (((int) date('z')) % $total) + 1;
                }
                return max(1, min((int) $raw, $total));
            };

            $mapReflectionRows = static function (array $rows, string $markType, $user): array {
                $events = [];
                foreach ($rows as $row) {
                    $options = $row['options'] ?? [];
                    if (is_string($options)) {
                        $options = json_decode($options, true) ?: [];
                    }
                    $questionId = (string) ($row['question_id'] ?? '');
                    $title = trim((string) ($row['insight_title'] ?? ''));
                    if ($title === '') {
                        $title = trim((string) ($row['theme'] ?? 'Reflection'));
                    }
                    $description = trim((string) ($row['insight_content'] ?? ''));
                    if ($description === '') {
                        $description = 'Reflection context is not available for this older question.';
                    }
                    $events[] = [
                        'id' => (int) ($row['id'] ?? 0),
                        'title' => $title,
                        'description' => $description,
                        'category' => (string) ($row['difficulty'] ?? ''),
                        'question_id' => $questionId,
                        'question_text' => (string) ($row['text'] ?? ''),
                        'options' => array_values($options),
                        'correct_answer_index' => -1,
                        'explanation' => '',
                        'is_read' => ($user && $questionId !== '')
                            ? $user->isInsightRead($markType, $questionId)
                            : false,
                    ];
                }
                return $events;
            };

            $loadReflections = static function (string $type, string $pageRaw, ?string $difficulty, int $limit) use ($pdo, $mapReflectionRows, $insightUser, $resolveInsightPage): array {
                $where = 'WHERE type = :type';
                $params = ['type' => $type];
                if (in_array($difficulty, ['Easy', 'Medium', 'Hard'], true)) {
                    $where .= ' AND difficulty = :difficulty';
                    $params['difficulty'] = $difficulty;
                }

                $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM generated_questions $where");
                $stmtCount->execute($params);
                $total = (int) $stmtCount->fetchColumn();
                $totalPages = max(1, (int) ceil(max(1, $total) / $limit));
                // For limit=1, total pages == total reflections.
                if ($limit === 1) {
                    $totalPages = max(1, $total);
                }
                $page = $resolveInsightPage($pageRaw, $totalPages);
                $offset = ($page - 1) * $limit;

                $stmt = $pdo->prepare(
                    "SELECT id, question_id, text, options, difficulty, theme, insight_title, insight_content
                     FROM generated_questions $where
                     ORDER BY updated_at DESC, id DESC
                     LIMIT :limit OFFSET :offset"
                );
                foreach ($params as $k => $v) {
                    $stmt->bindValue($k, $v);
                }
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $rows = $stmt->fetchAll() ?: [];

                $markType = $type === 'QURAN_HISTORY' ? 'QURAN_HISTORY' : 'SEERAH';
                $events = $mapReflectionRows($rows, $markType, $insightUser);

                $catStmt = $pdo->prepare("SELECT DISTINCT difficulty FROM generated_questions WHERE type = :type AND difficulty IS NOT NULL AND difficulty != '' ORDER BY difficulty ASC");
                $catStmt->execute(['type' => $type]);
                $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN) ?: ['Easy', 'Medium', 'Hard'];

                return [
                    'events' => $events,
                    'page' => $page,
                    'total_pages' => $totalPages,
                    'categories' => array_values($categories),
                    'total' => $total,
                ];
            };

            $seerahBundle = $loadReflections('SEERAH_INSIGHT', $seerahPageRaw, $seerahCategory, $limit);
            $historyBundle = $loadReflections('QURAN_HISTORY', $historyPageRaw, $historyCategory, $limit);

            // Fallback to catalog events only if no web-style reflections exist yet.
            if (($seerahBundle['total'] ?? 0) === 0 && ($historyBundle['total'] ?? 0) === 0) {
                $seerahOffset = ($seerahPage - 1) * $limit;
                $historyOffset = ($historyPage - 1) * $limit;

                $seerahCategories = $pdo->query("SELECT DISTINCT category FROM seerat_events WHERE category IS NOT NULL AND category != '' ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
                $historyCategories = $pdo->query("SELECT DISTINCT {$historyExtraCol} FROM {$historyTable} WHERE {$historyExtraCol} IS NOT NULL AND {$historyExtraCol} != '' ORDER BY {$historyExtraCol} ASC")->fetchAll(PDO::FETCH_COLUMN);

                $seerahWhere = "";
                $seerahParams = [];
                if ($seerahCategory !== null) {
                    $seerahWhere = "WHERE category = :category";
                    $seerahParams['category'] = $seerahCategory;
                }
                $stmtCountSeer = $pdo->prepare("SELECT COUNT(*) FROM seerat_events $seerahWhere");
                foreach ($seerahParams as $k => $v) {
                    $stmtCountSeer->bindValue($k, $v);
                }
                $stmtCountSeer->execute();
                $totalSeer = $stmtCountSeer->fetchColumn();
                $seerahTotalPages = max(1, (int)ceil($totalSeer / $limit));

                $stmtSeer = $pdo->prepare("SELECT id, title, description, category FROM seerat_events $seerahWhere ORDER BY id ASC LIMIT :limit OFFSET :offset");
                foreach ($seerahParams as $k => $v) {
                    $stmtSeer->bindValue($k, $v);
                }
                $stmtSeer->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmtSeer->bindValue(':offset', $seerahOffset, PDO::PARAM_INT);
                $stmtSeer->execute();
                $seeratEvents = $stmtSeer->fetchAll();

                $historyWhere = "";
                $historyParams = [];
                if ($historyCategory !== null) {
                    $historyWhere = "WHERE {$historyExtraCol} = :category";
                    $historyParams['category'] = $historyCategory;
                }
                $stmtCountHist = $pdo->prepare("SELECT COUNT(*) FROM {$historyTable} $historyWhere");
                foreach ($historyParams as $k => $v) {
                    $stmtCountHist->bindValue($k, $v);
                }
                $stmtCountHist->execute();
                $totalHist = $stmtCountHist->fetchColumn();
                $historyTotalPages = max(1, (int)ceil($totalHist / $limit));

                $stmtHist = $pdo->prepare("SELECT id, title, description, {$historyExtraCol} as category FROM {$historyTable} $historyWhere ORDER BY id ASC LIMIT :limit OFFSET :offset");
                foreach ($historyParams as $k => $v) {
                    $stmtHist->bindValue($k, $v);
                }
                $stmtHist->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmtHist->bindValue(':offset', $historyOffset, PDO::PARAM_INT);
                $stmtHist->execute();
                $historyEvents = $stmtHist->fetchAll();

                $randFunc = $isSqlite ? 'RANDOM()' : 'RAND()';
                $attachInsightQuestions = static function (array $events, string $questionType) use ($pdo, $randFunc, $insightUser): array {
                    if ($events === []) {
                        return $events;
                    }
                    $needed = count($events);
                    $stmt = $pdo->prepare("SELECT question_id, text, options FROM generated_questions WHERE type = :type ORDER BY $randFunc LIMIT :limit");
                    $stmt->bindValue(':type', $questionType, PDO::PARAM_STR);
                    $stmt->bindValue(':limit', $needed, PDO::PARAM_INT);
                    $stmt->execute();
                    $pool = $stmt->fetchAll() ?: [];
                    if ($pool === []) {
                        return $events;
                    }
                    $markType = $questionType === 'QURAN_HISTORY' ? 'QURAN_HISTORY' : 'SEERAH';
                    foreach ($events as $i => $event) {
                        $row = $pool[$i % count($pool)];
                        $options = $row['options'] ?? [];
                        if (is_string($options)) {
                            $options = json_decode($options, true) ?: [];
                        }
                        $qid = (string) ($row['question_id'] ?? '');
                        $events[$i]['question_id'] = $qid;
                        $events[$i]['question_text'] = (string) ($row['text'] ?? '');
                        $events[$i]['options'] = array_values($options);
                        $events[$i]['correct_answer_index'] = -1;
                        $events[$i]['explanation'] = '';
                        $events[$i]['is_read'] = ($insightUser && $qid !== '')
                            ? $insightUser->isInsightRead($markType, $qid)
                            : false;
                    }
                    return $events;
                };

                $seeratEvents = $attachInsightQuestions($seeratEvents, 'SEERAH_INSIGHT');
                $historyEvents = $attachInsightQuestions($historyEvents, 'QURAN_HISTORY');

                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'seerah_events' => $seeratEvents,
                        'history_events' => $historyEvents,
                        'seerah_page' => $seerahPage,
                        'seerah_total_pages' => $seerahTotalPages,
                        'history_page' => $historyPage,
                        'history_total_pages' => $historyTotalPages,
                        'seerah_categories' => $seerahCategories,
                        'history_categories' => $historyCategories,
                        'mode' => 'catalog_fallback',
                    ]
                ]);
                break;
            }

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'seerah_events' => $seerahBundle['events'],
                    'history_events' => $historyBundle['events'],
                    'seerah_page' => $seerahBundle['page'],
                    'seerah_total_pages' => $seerahBundle['total_pages'],
                    'history_page' => $historyBundle['page'],
                    'history_total_pages' => $historyBundle['total_pages'],
                    'seerah_categories' => $seerahBundle['categories'],
                    'history_categories' => $historyBundle['categories'],
                    'mode' => 'web_reflections',
                ]
            ]);
            break;

        case 'themes':
            $themes = \App\Models\Theme::where('is_active', true)
                ->has('questions', '>=', 5)
                ->select('id', 'name', 'type', 'description')
                ->orderBy('name', 'ASC')
                ->get();
            echo json_encode([
                'status' => 'success',
                'data' => $themes
            ]);
            break;

        case 'theme_quiz':
            $themeId = isset($_GET['theme_id']) ? (int) $_GET['theme_id'] : 0;
            $difficulty = isset($_GET['difficulty']) ? trim((string)$_GET['difficulty']) : 'Medium';
            if (!in_array($difficulty, ['Easy', 'Medium', 'Hard'], true)) {
                $difficulty = 'Medium';
            }
            $limit = isset($_GET['quantity']) ? (int) $_GET['quantity'] : (isset($_GET['limit']) ? (int) $_GET['limit'] : 20);
            if ($limit <= 0) $limit = 20;
            $limit = min($limit, 50);

            $randFunc = $isSqlite ? 'RANDOM()' : 'RAND()';
            // Do not leak answer keys before the client submits.
            $stmt = $pdo->prepare("SELECT id, question_id, text, options, difficulty, reference, source_info 
                                   FROM generated_questions 
                                   WHERE theme_id = :theme_id AND difficulty = :difficulty 
                                   ORDER BY $randFunc LIMIT :limit");
            $stmt->bindValue(':theme_id', $themeId, PDO::PARAM_INT);
            $stmt->bindValue(':difficulty', $difficulty, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $questionsRaw = $stmt->fetchAll();

            if (count($questionsRaw) < $limit) {
                $existingIds = array_column($questionsRaw, 'id');
                $needed = $limit - count($questionsRaw);
                $inClause = !empty($existingIds) ? 'AND id NOT IN (' . implode(',', array_map('intval', $existingIds)) . ')' : '';
                $stmt = $pdo->prepare("SELECT id, question_id, text, options, difficulty, reference, source_info 
                                       FROM generated_questions 
                                       WHERE theme_id = :theme_id $inClause 
                                       ORDER BY $randFunc LIMIT :limit");
                $stmt->bindValue(':theme_id', $themeId, PDO::PARAM_INT);
                $stmt->bindValue(':limit', $needed, PDO::PARAM_INT);
                $stmt->execute();
                $fallbackRaw = $stmt->fetchAll();
                $questionsRaw = array_merge($questionsRaw, $fallbackRaw);
            }

            $questions = [];
            foreach ($questionsRaw as $row) {
                $row['options'] = json_decode($row['options'], true) ?: [];
                // Placeholder for older clients; never the real answer.
                $row['correct_answer_index'] = -1;
                $row['explanation'] = '';
                $questions[] = $row;
            }

            $attemptUser = \App\Services\MobileApiAuthService::userFromRequest();
            $tokenQuestions = array_map(static function ($row) {
                return ['id' => (string) (($row['question_id'] ?? '') !== '' ? $row['question_id'] : $row['id'])];
            }, $questions);
            $attemptToken = $attemptUser
                ? \App\Services\QuizCompletionService::issueAttempt((int) $attemptUser->id, $tokenQuestions)
                : null;

            echo json_encode([
                'status' => 'success',
                'theme_id' => $themeId,
                'difficulty' => $difficulty,
                'quantity' => count($questions),
                'attempt_token' => $attemptToken,
                'data' => $questions
            ]);
            break;

        case 'grand_quiz':
            $quizType = isset($_GET['quiz_type']) ? strtoupper(trim((string)$_GET['quiz_type'])) : 'QURAN';
            $difficulty = isset($_GET['difficulty']) ? ucfirst(strtolower(trim((string)$_GET['difficulty']))) : 'Medium';
            if (!in_array($difficulty, ['Easy', 'Medium', 'Hard'], true)) {
                $difficulty = 'Medium';
            }
            $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : (isset($_GET['limit']) ? (int)$_GET['limit'] : 20);
            if ($quantity <= 0) $quantity = 20;
            $quantity = min($quantity, 50);

            $type = ($quizType === 'SEERAH' || $quizType === 'GRAND_SEERAH') ? 'SEERAH' : 'PARA';
            $randFunc = $isSqlite ? 'RANDOM()' : 'RAND()';

            $stmt = $pdo->prepare("SELECT id, question_id, text, options, difficulty, reference, source_info 
                                   FROM generated_questions 
                                   WHERE type = :type AND difficulty = :difficulty 
                                   ORDER BY $randFunc LIMIT :limit");
            $stmt->bindValue(':type', $type, PDO::PARAM_STR);
            $stmt->bindValue(':difficulty', $difficulty, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $quantity, PDO::PARAM_INT);
            $stmt->execute();
            $questionsRaw = $stmt->fetchAll();

            if (count($questionsRaw) < $quantity) {
                $existingIds = array_column($questionsRaw, 'id');
                $needed = $quantity - count($questionsRaw);
                $inClause = !empty($existingIds) ? 'AND id NOT IN (' . implode(',', array_map('intval', $existingIds)) . ')' : '';
                $stmt = $pdo->prepare("SELECT id, question_id, text, options, difficulty, reference, source_info 
                                       FROM generated_questions 
                                       WHERE type = :type $inClause 
                                       ORDER BY $randFunc LIMIT :limit");
                $stmt->bindValue(':type', $type, PDO::PARAM_STR);
                $stmt->bindValue(':limit', $needed, PDO::PARAM_INT);
                $stmt->execute();
                $fallbackRaw = $stmt->fetchAll();
                $questionsRaw = array_merge($questionsRaw, $fallbackRaw);
            }

            $questions = [];
            foreach ($questionsRaw as $row) {
                $row['options'] = json_decode($row['options'], true) ?: [];
                $row['correct_answer_index'] = -1;
                $row['explanation'] = '';
                $questions[] = $row;
            }

            $attemptUser = \App\Services\MobileApiAuthService::userFromRequest();
            $tokenQuestions = array_map(static function ($row) {
                return ['id' => (string) (($row['question_id'] ?? '') !== '' ? $row['question_id'] : $row['id'])];
            }, $questions);
            $attemptToken = $attemptUser
                ? \App\Services\QuizCompletionService::issueAttempt((int) $attemptUser->id, $tokenQuestions)
                : null;

            echo json_encode([
                'status' => 'success',
                'quiz_type' => $quizType,
                'attempt_token' => $attemptToken,
                'difficulty' => $difficulty,
                'quantity' => count($questions),
                'data' => $questions
            ]);
            break;

        case 'login':
            $email = isset($requestData['email']) ? strtolower(trim((string)$requestData['email'])) : '';
            $password = isset($requestData['password']) ? (string)$requestData['password'] : '';

            if (empty($email) || empty($password)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Email and password are required.'
                ]);
                break;
            }

            $userModel = \App\Models\User::where('email', $email)->first();

            if ($userModel && !empty($userModel->password)
                && \Illuminate\Support\Facades\Hash::check($password, $userModel->password)) {
                $streak = calculate_user_streak($pdo, $userModel->id, true);
                $token = \App\Services\MobileApiAuthService::issueToken($userModel);
                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'user_id' => (int)$userModel->id,
                        'name' => $userModel->name ?: $userModel->display_name ?: 'User',
                        'email' => $userModel->email,
                        'total_score' => (int)$userModel->total_score,
                        'token' => $token,
                        'streak' => $streak
                    ]
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid email or password.'
                ]);
            }
            break;

        case 'register':
            echo json_encode([
                'status' => 'error',
                'message' => 'Registration must be completed using OTP.'
            ]);
            break;

        case 'request_otp':
            $email = isset($requestData['email']) ? strtolower(trim((string)$requestData['email'])) : '';
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'A valid email is required.'
                ]);
                break;
            }

            if (\App\Services\OtpRateLimiter::tooManyRequests($email, 'request')) {
                http_response_code(429);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Too many verification requests. Please try again later.'
                ]);
                break;
            }

            try {
                \App\Services\OtpService::issueAndSend($email, 5);
                \App\Services\OtpRateLimiter::hit($email, 'request');
            } catch (\RuntimeException $e) {
                http_response_code(503);
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
                break;
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'Verification code sent successfully.'
            ]);
            break;

        case 'verify_otp':
            $email = isset($requestData['email']) ? strtolower(trim((string)$requestData['email'])) : '';
            $otp = isset($requestData['otp']) ? trim((string)$requestData['otp']) : '';

            if (empty($email) || empty($otp)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Email and verification code are required.'
                ]);
                break;
            }

            if (\App\Services\OtpRateLimiter::tooManyRequests($email, 'verify')) {
                http_response_code(429);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Too many verification attempts. Please try again later.'
                ]);
                break;
            }

            if (!\App\Services\OtpService::verify($email, $otp)) {
                \App\Services\OtpRateLimiter::hit($email, 'verify');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid or expired verification code.'
                ]);
                break;
            }

            \App\Services\OtpRateLimiter::clearVerifyAttempts($email);

            $user = \App\Models\User::firstOrCreate(
                ['email' => $email],
                ['name' => explode('@', $email)[0]]
            );

            $streak = calculate_user_streak($pdo, $user->id, true);
            $token = \App\Services\MobileApiAuthService::issueToken($user);

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'user_id' => (int)$user->id,
                    'name' => $user->name ?: $user->display_name ?: 'User',
                    'email' => $user->email,
                    'total_score' => (int)$user->total_score,
                    'has_password' => !empty($user->password),
                    'token' => $token,
                    'streak' => $streak
                ]
            ]);
            break;

        case 'set_password':
            $password = isset($requestData['password']) ? (string)$requestData['password'] : '';

            if (strlen($password) < 6) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Password must be at least 6 characters.'
                ]);
                break;
            }

            // First-time set only — if a password already exists, require change_password + current_password.
            if (!empty($authUser->password)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Password already set. Use change_password with your current password.'
                ]);
                break;
            }

            $authUser->password = \Illuminate\Support\Facades\Hash::make($password);
            $authUser->save();

            echo json_encode([
                'status' => 'success',
                'message' => 'Password set successfully.'
            ]);
            break;

        case 'change_password':
            $password = isset($requestData['password']) ? (string)$requestData['password'] : '';
            $currentPassword = isset($requestData['current_password']) ? (string)$requestData['current_password'] : '';

            if (strlen($password) < 6) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Password must be at least 6 characters.'
                ]);
                break;
            }

            if (!empty($authUser->password)) {
                if ($currentPassword === '' || !\Illuminate\Support\Facades\Hash::check($currentPassword, $authUser->password)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Current password is incorrect.'
                    ]);
                    break;
                }
            }

            $authUser->password = \Illuminate\Support\Facades\Hash::make($password);
            $authUser->save();

            echo json_encode([
                'status' => 'success',
                'message' => 'Password updated successfully.'
            ]);
            break;

        case 'delete_account':
            $currentPassword = isset($requestData['current_password']) ? (string)$requestData['current_password'] : '';
            $confirmDeletion = isset($requestData['confirm_deletion']) ? (string)$requestData['confirm_deletion'] : '';

            if (!empty($authUser->password)) {
                if ($currentPassword === '' || !\Illuminate\Support\Facades\Hash::check($currentPassword, $authUser->password)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Current password is required to delete your account.'
                    ]);
                    break;
                }
            } elseif (!in_array($confirmDeletion, ['1', 'true', 'on'], true)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Please confirm account deletion.'
                ]);
                break;
            }

            \App\Http\Controllers\AccountDeletionController::performAccountDeletion($authUser);

            echo json_encode([
                'status' => 'success',
                'message' => 'Account deleted successfully.'
            ]);
            break;

        case 'check_answer':
            $questionKey = isset($requestData['question_id'])
                ? trim((string)$requestData['question_id'])
                : (isset($requestData['id']) ? trim((string)$requestData['id']) : '');
            $selected = isset($requestData['selected_index']) ? (int)$requestData['selected_index'] : -1;

            if ($questionKey === '' || $selected < 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'question_id and selected_index are required.'
                ]);
                break;
            }

            $dbQuestion = \App\Services\QuizScoringService::resolveQuestion([
                'id' => $questionKey,
                'options' => isset($requestData['options']) ? (is_array($requestData['options']) ? $requestData['options'] : json_decode((string)$requestData['options'], true)) : null,
            ]);
            if (!$dbQuestion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Question not found.'
                ]);
                break;
            }

            $payload = [
                'id' => $questionKey,
                'options' => isset($requestData['options'])
                    ? (is_array($requestData['options']) ? $requestData['options'] : (json_decode((string)$requestData['options'], true) ?: null))
                    : null,
            ];
            $isCorrect = \App\Services\QuizScoringService::isCorrect($dbQuestion, $payload, $selected);

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'correct' => $isCorrect,
                    'correct_answer_index' => (int)$dbQuestion->correct_answer_index,
                    'explanation' => (string)($dbQuestion->explanation ?? ''),
                    'question_id' => (string)$dbQuestion->question_id,
                ],
            ]);
            break;

        case 'submit_quiz':
            $userId = (int) $authUser->id;
            $type = isset($requestData['type']) ? (string)$requestData['type'] : 'THEME';
            $title = isset($requestData['title']) ? (string)$requestData['title'] : '';
            $difficulty = isset($requestData['difficulty']) ? (string)$requestData['difficulty'] : 'Medium';
            $questionsJson = isset($requestData['questions']) ? (string)$requestData['questions'] : '[]';
            $userAnswersJson = isset($requestData['user_answers']) ? (string)$requestData['user_answers'] : '[]';
            $attemptToken = isset($requestData['attempt_token']) ? trim((string)$requestData['attempt_token']) : '';

            // Soft rate-limit quiz submissions to slow leaderboard farming.
            $quizRateKey = 'mobile:quiz_submit:' . $userId . ':' . date('YmdH');
            $quizSubmitCount = (int) \Illuminate\Support\Facades\Cache::get($quizRateKey, 0);
            if ($quizSubmitCount >= 40) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Too many quiz submissions. Please try again later.'
                ]);
                break;
            }

            $questionsArr = json_decode($questionsJson, true) ?: [];
            $answersArr = json_decode($userAnswersJson, true) ?: [];
            $computed = \App\Services\QuizScoringService::recompute($questionsArr, $answersArr, $difficulty);
            $score = (int) $computed['score'];
            $totalQuestions = max(0, min((int) $computed['total_questions'], 100));

            if ($totalQuestions === 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No valid quiz questions found to score.'
                ]);
                break;
            }

            if (!\App\Services\QuizCompletionService::consumeAttempt($userId, $attemptToken, $questionsArr)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid or expired quiz attempt. Please start a new quiz.'
                ]);
                break;
            }

            if (!\App\Services\QuizCompletionService::claimAttempt($userId, $questionsArr)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'This quiz set was already submitted recently. Try a new quiz.'
                ]);
                break;
            }

            \Illuminate\Support\Facades\Cache::put($quizRateKey, $quizSubmitCount + 1, 3600);

            $eloquentUser = $authUser;
            if (!$eloquentUser) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User not found.'
                ]);
                break;
            }

            // Insert into quizzes table
            $quizId = uniqid('quiz_');
            $details = json_encode([
                'questions' => $questionsArr,
                'userAnswers' => $answersArr,
            ]);

            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO quizzes (id, user_id, type, title, score, total_questions, difficulty, details, created_at, updated_at) 
                                   VALUES (:id, :user_id, :type, :title, :score, :total_questions, :difficulty, :details, :created_at, :updated_at)");
            $stmt->execute([
                'id' => $quizId,
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'score' => $score,
                'total_questions' => $totalQuestions,
                'difficulty' => $difficulty,
                'details' => $details,
                'created_at' => $now,
                'updated_at' => $now
            ]);

            // Update user totals + shared side-effects (difficulty_stats, times_*, para/seerah, engagement)
            $newScore = (int) $eloquentUser->total_score + $score;
            $newTotalQuestions = (int) $eloquentUser->total_questions + $totalQuestions;
            $eloquentUser->total_score = $newScore;
            $eloquentUser->total_questions = $newTotalQuestions;
            $eloquentUser->save();

            $paraNumber = isset($requestData['para_number']) ? (int) $requestData['para_number'] : null;
            $typeUpper = strtoupper($type);
            // Theme/Grand still update difficulty_stats + times_*; para/seerah counters only for PARA/SEERAH.
            $progressType = in_array($typeUpper, ['PARA', 'SEERAH'], true) ? $typeUpper : 'THEME';
            $engagement = \App\Services\QuizCompletionService::applyProgress(
                $eloquentUser->fresh(),
                $questionsArr,
                $answersArr,
                $progressType,
                ($progressType === 'PARA' && $paraNumber) ? $paraNumber : null,
                $difficulty
            );
            $streakData = $engagement['streak'] ?? calculate_user_streak($pdo, $userId, true);

            echo json_encode([
                'status' => 'success',
                'message' => 'Quiz attempt submitted successfully.',
                'data' => [
                    'quiz_id' => $quizId,
                    'new_total_score' => $newScore,
                    'streak' => $streakData,
                    'engagement' => $engagement,
                ]
            ]);
            break;

        case 'daily_engagement':
            $userId = (int) $authUser->id;
            $engagement = mobile_engagement_status($userId);
            if (!$engagement) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User not found.'
                ]);
                break;
            }
            echo json_encode([
                'status' => 'success',
                'data' => $engagement
            ]);
            break;

        case 'mark_engagement':
            $userId = (int) $authUser->id;
            $activity = isset($requestData['activity']) ? strtolower(trim((string)$requestData['activity'])) : '';
            // Self-mark only for dua. Quiz/read/insight require real actions (submit_quiz, toggle_*, mark_insight).
            if ($activity !== \App\Models\UserDailyEngagement::ACTIVITY_DUA) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid activity. Only dua can be self-marked. Use quiz submit, reading toggles, or mark_insight for others.'
                ]);
                break;
            }
            $engagement = mobile_engagement_record($userId, $activity);
            if (!$engagement) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User not found.'
                ]);
                break;
            }
            echo json_encode([
                'status' => 'success',
                'message' => 'Engagement recorded successfully.',
                'data' => $engagement
            ]);
            break;

        case 'toggle_surah':
            $userId = (int) $authUser->id;
            $surah = isset($requestData['surah']) ? (int)$requestData['surah'] : 0;
            if ($surah < 1 || $surah > 114) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Valid surah (1-114) is required.'
                ]);
                break;
            }
            $eloquentUser = $authUser;
            if (!$eloquentUser) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User not found.'
                ]);
                break;
            }
            $result = $eloquentUser->toggleCompletedSurah($surah);
            $eloquentUser->refresh();
            $engagement = mobile_engagement_status($userId);
            echo json_encode([
                'status' => 'success',
                'message' => $result['is_read'] ? "Masha'Allah! Surah marked as read." : 'Surah marked as unread.',
                'data' => array_merge($result, [
                    'streak' => $engagement['streak'] ?? null,
                    'engagement' => $engagement,
                ])
            ]);
            break;

        case 'toggle_para':
            $userId = (int) $authUser->id;
            $para = isset($requestData['para']) ? (int)$requestData['para'] : 0;
            if ($para < 1 || $para > 30) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Valid para (1-30) is required.'
                ]);
                break;
            }
            $eloquentUser = $authUser;
            if (!$eloquentUser) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User not found.'
                ]);
                break;
            }
            $result = $eloquentUser->toggleCompletedPara($para);
            $eloquentUser->refresh();
            $engagement = mobile_engagement_status($userId);
            echo json_encode([
                'status' => 'success',
                'message' => $result['is_completed'] ? "Masha'Allah! Para marked as completed." : 'Para marked as incomplete.',
                'data' => array_merge($result, [
                    'streak' => $engagement['streak'] ?? null,
                    'engagement' => $engagement,
                ])
            ]);
            break;

        case 'mark_insight':
            $userId = (int) $authUser->id;
            $type = isset($requestData['type']) ? strtoupper(trim((string)$requestData['type'])) : '';
            $insightId = isset($requestData['insight_id']) ? trim((string)$requestData['insight_id']) : '';
            if (!in_array($type, ['SEERAH', 'QURAN_HISTORY'], true)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Valid type (SEERAH or QURAN_HISTORY) is required.'
                ]);
                break;
            }
            if ($insightId === '') {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'insight_id is required.'
                ]);
                break;
            }
            $eloquentUser = $authUser;
            $result = $eloquentUser->markInsightRead($type, $insightId);
            if (!($result['success'] ?? false)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $result['error'] ?? 'Unable to mark insight as read.'
                ]);
                break;
            }
            $eloquentUser->refresh();
            $engagement = mobile_engagement_status($userId);
            echo json_encode([
                'status' => 'success',
                'message' => $result['already_read'] ? 'Already marked as read.' : "Masha'Allah! Insight completed.",
                'data' => array_merge($result, [
                    'streak' => $engagement['streak'] ?? null,
                    'engagement' => $engagement,
                ])
            ]);
            break;

        case 'daily_streak':
        case 'streak_check_in':
            $userId = (int) $authUser->id;
            $streak = calculate_user_streak($pdo, $userId, true);
            if (!$streak) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User not found.'
                ]);
                break;
            }
            echo json_encode([
                'status' => 'success',
                'message' => 'Daily streak recorded successfully!',
                'data' => $streak
            ]);
            break;

        case 'streak_status':
            $userId = (int) $authUser->id;
            $streak = calculate_user_streak($pdo, $userId, false);
            if (!$streak) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User not found.'
                ]);
                break;
            }
            echo json_encode([
                'status' => 'success',
                'data' => $streak
            ]);
            break;

        case 'user_stats':
            $userId = (int) $authUser->id;

            try {
                $stmt = $pdo->prepare("SELECT id, name, display_name, email, password, total_score, total_questions, seerah_read_count, quran_history_read_count, completed_paras, para_mastery, difficulty_stats, bookmarked_questions FROM users WHERE id = :id");
                $stmt->execute(['id' => $userId]);
                $user = $stmt->fetch();
            } catch (Exception $e) {
                $user = null;
            }

            if (!$user) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User not found.'
                ]);
                break;
            }

            $streak = calculate_user_streak($pdo, $userId, false);

            $completedParas = json_decode($user['completed_paras'] ?? '[]', true) ?: [];
            $paraMastery = json_decode($user['para_mastery'] ?? '{}', true) ?: [];
            $completedSurahs = $paraMastery['completed_surahs'] ?? [];
            $difficultyStats = json_decode($user['difficulty_stats'] ?? '{}', true) ?: [];
            $bookmarkedQuestions = json_decode($user['bookmarked_questions'] ?? '[]', true) ?: [];

            $totalScore = (int)($user['total_score'] ?? 0);
            $totalQuestions = (int)($user['total_questions'] ?? 0);

            // Compute max possible score & accuracy exactly like the User model
            $easyTotal = (int)($difficultyStats['total']['Easy'] ?? 0);
            $mediumTotal = (int)($difficultyStats['total']['Medium'] ?? 0);
            $hardTotal = (int)($difficultyStats['total']['Hard'] ?? 0);
            $insightTotal = max(0, $totalQuestions - ($easyTotal + $mediumTotal + $hardTotal));
            $maxPossible = ($easyTotal * 5) + ($mediumTotal * 10) + ($hardTotal * 15) + $insightTotal;
            if ($maxPossible <= 0 && $totalQuestions > 0) {
                $maxPossible = $totalQuestions * 5;
            }

            $accuracy = $maxPossible > 0 ? min(100, max(0, (int)round(($totalScore / $maxPossible) * 100))) : 0;
            $spiritualLevel = $totalQuestions > 50 ? 'Knowledge Seeker' : ($totalQuestions > 10 ? 'Aspirant' : 'Novice');

            $diffSummary = [
                'easy' => [
                    'total' => $easyTotal,
                    'correct' => (int)($difficultyStats['correct']['Easy'] ?? 0),
                    'accuracy' => $easyTotal > 0 ? (int)round((( (int)($difficultyStats['correct']['Easy'] ?? 0) ) / $easyTotal) * 100) : 0,
                ],
                'medium' => [
                    'total' => $mediumTotal,
                    'correct' => (int)($difficultyStats['correct']['Medium'] ?? 0),
                    'accuracy' => $mediumTotal > 0 ? (int)round((( (int)($difficultyStats['correct']['Medium'] ?? 0) ) / $mediumTotal) * 100) : 0,
                ],
                'hard' => [
                    'total' => $hardTotal,
                    'correct' => (int)($difficultyStats['correct']['Hard'] ?? 0),
                    'accuracy' => $hardTotal > 0 ? (int)round((( (int)($difficultyStats['correct']['Hard'] ?? 0) ) / $hardTotal) * 100) : 0,
                ],
            ];

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'user_id' => (int)$user['id'],
                    'name' => $user['display_name'] ?: $user['name'] ?: 'User',
                    'email' => $user['email'] ?? '',
                    'spiritual_level' => $spiritualLevel,
                    'total_score' => $totalScore,
                    'max_possible_score' => $maxPossible,
                    'total_questions' => $totalQuestions,
                    'accuracy' => $accuracy,
                    'surahs_completed_count' => count($completedSurahs),
                    'paras_completed_count' => count($completedParas),
                    'seerah_read_count' => (int)($user['seerah_read_count'] ?? 0),
                    'quran_history_read_count' => (int)($user['quran_history_read_count'] ?? 0),
                    'bookmarks_count' => count($bookmarkedQuestions),
                    'streak' => $streak ?: [
                        'current_streak' => 0,
                        'longest_streak' => 0,
                        'is_active_today' => false,
                        'last_streak_date' => null,
                        'streak_history' => []
                    ],
                    'engagement' => mobile_engagement_status($userId),
                    'difficulty' => $diffSummary,
                    'has_password' => !empty($user['password']),
                ]
            ]);
            break;

        case 'leaderboard':
            $stmt = $pdo->query("SELECT id, name, display_name, total_score, total_questions, seerah_read_count 
                                 FROM users 
                                 ORDER BY total_score DESC, total_questions DESC 
                                 LIMIT 50");
            $leaderboard = $stmt->fetchAll();
            echo json_encode([
                'status' => 'success',
                'data' => $leaderboard
            ]);
            break;

        case 'notifications':
            $sinceId = isset($_GET['since_id']) && is_numeric($_GET['since_id']) ? (int)$_GET['since_id'] : 0;
            $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min((int)$_GET['limit'], 50) : 20;

            if ($sinceId > 0) {
                $stmt = $pdo->prepare("SELECT id, title, message, type, action_url, created_at 
                                     FROM app_notifications 
                                     WHERE send_push = 1 AND id > :since_id 
                                     ORDER BY id DESC LIMIT :limit");
                $stmt->bindValue(':since_id', $sinceId, PDO::PARAM_INT);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                $stmt = $pdo->prepare("SELECT id, title, message, type, action_url, created_at 
                                     FROM app_notifications 
                                     WHERE send_push = 1 
                                     ORDER BY id DESC LIMIT :limit");
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
            }
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode([
                'status' => 'success',
                'count' => count($items),
                'data' => $items
            ]);
            break;

        case 'register_device_token':
            $token = $requestData['token'] ?? '';
            $platform = $requestData['platform'] ?? 'android';
            $deviceName = $requestData['device_name'] ?? null;
            // Optional auth: allow anonymous push registration; bind user_id only from Bearer token.
            $authForDevice = \App\Services\MobileApiAuthService::userFromRequest();
            $userId = $authForDevice ? (int) $authForDevice->id : null;
            $appVersion = $requestData['app_version'] ?? null;

            if (empty($token)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Token is required.'
                ]);
                break;
            }

            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO device_tokens (token, platform, device_name, user_id, app_version, is_active, last_active_at, created_at, updated_at) 
                                 VALUES (:token, :platform, :device_name, :user_id, :app_version, 1, :last_active_at, :created_at, :updated_at)
                                 ON DUPLICATE KEY UPDATE 
                                    platform = VALUES(platform),
                                    device_name = VALUES(device_name),
                                    user_id = COALESCE(VALUES(user_id), user_id),
                                    app_version = VALUES(app_version),
                                    is_active = 1,
                                    last_active_at = VALUES(last_active_at),
                                    updated_at = VALUES(updated_at)");
            $stmt->execute([
                ':token' => $token,
                ':platform' => $platform,
                ':device_name' => $deviceName,
                ':user_id' => $userId,
                ':app_version' => $appVersion,
                ':last_active_at' => $now,
                ':created_at' => $now,
                ':updated_at' => $now
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Device token registered successfully.',
            ]);
            break;

        case 'duas':
            $category = isset($requestData['category']) ? trim((string)$requestData['category']) : '';
            $search = isset($requestData['search']) ? trim((string)$requestData['search']) : '';
            $sourceType = isset($requestData['source_type']) ? trim((string)$requestData['source_type']) : '';

            $sql = "SELECT id, title, slug, category, description, arabic_text, transliteration, 
                           translation_en, translation_ur, word_by_word, meaning_explanation, 
                           benefits_and_virtues, when_to_recite, repeat_count, source_type, 
                           quran_reference, surah_number, verse_number, hadith_reference, 
                           hadith_book, hadith_number, hadith_grading, audio_url, is_featured, `order`, status
                    FROM duas
                    WHERE (status = 'approved' OR status IS NULL)";
            $params = [];

            if (!empty($category) && $category !== 'all') {
                $sql .= " AND category = :category";
                $params[':category'] = $category;
            }

            if (!empty($sourceType) && in_array($sourceType, ['quran', 'hadith', 'both'])) {
                if ($sourceType === 'quran') {
                    $sql .= " AND source_type IN ('quran', 'both')";
                } elseif ($sourceType === 'hadith') {
                    $sql .= " AND source_type IN ('hadith', 'both')";
                } else {
                    $sql .= " AND source_type = :source_type";
                    $params[':source_type'] = $sourceType;
                }
            }

            if (!empty($search)) {
                $sql .= " AND (title LIKE :s1 OR description LIKE :s2 OR arabic_text LIKE :s3 OR transliteration LIKE :s4 OR translation_en LIKE :s5 OR translation_ur LIKE :s6 OR quran_reference LIKE :s7 OR hadith_reference LIKE :s8)";
                $searchWild = '%' . $search . '%';
                $params[':s1'] = $searchWild;
                $params[':s2'] = $searchWild;
                $params[':s3'] = $searchWild;
                $params[':s4'] = $searchWild;
                $params[':s5'] = $searchWild;
                $params[':s6'] = $searchWild;
                $params[':s7'] = $searchWild;
                $params[':s8'] = $searchWild;
            }

            $sql .= " ORDER BY `order` ASC, id ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $duas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format JSON fields and booleans
            foreach ($duas as &$d) {
                $d['id'] = (int)$d['id'];
                $d['is_featured'] = (bool)$d['is_featured'];
                $d['repeat_count'] = (int)($d['repeat_count'] ?? 1);
                $d['order'] = (int)($d['order'] ?? 0);
                if (!empty($d['surah_number'])) $d['surah_number'] = (int)$d['surah_number'];
                if (!empty($d['verse_number'])) $d['verse_number'] = (int)$d['verse_number'];
                if (!empty($d['word_by_word']) && is_string($d['word_by_word'])) {
                    $d['word_by_word'] = json_decode($d['word_by_word'], true);
                }
            }
            unset($d);

            echo json_encode([
                'status' => 'success',
                'data' => $duas
            ]);
            break;

        case 'dua':
            $id = isset($requestData['id']) ? (int)$requestData['id'] : 0;
            $slug = isset($requestData['slug']) ? trim((string)$requestData['slug']) : '';

            $sql = "SELECT id, title, slug, category, description, arabic_text, transliteration, 
                           translation_en, translation_ur, word_by_word, meaning_explanation, 
                           benefits_and_virtues, when_to_recite, repeat_count, source_type, 
                           quran_reference, surah_number, verse_number, hadith_reference, 
                           hadith_book, hadith_number, hadith_grading, audio_url, is_featured, `order`, status
                    FROM duas
                    WHERE (status = 'approved' OR status IS NULL)";
            $params = [];

            if ($id > 0) {
                $sql .= " AND id = :id";
                $params[':id'] = $id;
            } elseif (!empty($slug)) {
                $sql .= " AND slug = :slug";
                $params[':slug'] = $slug;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Dua ID or slug required']);
                break;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $dua = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dua) {
                echo json_encode(['status' => 'error', 'message' => 'Dua not found']);
                break;
            }

            $dua['id'] = (int)$dua['id'];
            $dua['is_featured'] = (bool)$dua['is_featured'];
            $dua['repeat_count'] = (int)($dua['repeat_count'] ?? 1);
            $dua['order'] = (int)($dua['order'] ?? 0);
            if (!empty($dua['surah_number'])) $dua['surah_number'] = (int)$dua['surah_number'];
            if (!empty($dua['verse_number'])) $dua['verse_number'] = (int)$dua['verse_number'];
            if (!empty($dua['word_by_word']) && is_string($dua['word_by_word'])) {
                $dua['word_by_word'] = json_decode($dua['word_by_word'], true);
            }

            echo json_encode([
                'status' => 'success',
                'data' => $dua
            ]);
            break;

        case 'submit_dua':
            $title = trim($requestData['title'] ?? '');
            $category = trim($requestData['category'] ?? 'General');
            $arabicText = trim($requestData['arabic_text'] ?? '');
            $translationEn = trim($requestData['translation_en'] ?? '');

            if (empty($title) || empty($arabicText) || empty($translationEn)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Title, Arabic text, and English translation are required.'
                ]);
                break;
            }

            $description = trim($requestData['description'] ?? '');
            $transliteration = trim($requestData['transliteration'] ?? '');
            $translationUr = trim($requestData['translation_ur'] ?? '');
            $sourceType = trim($requestData['source_type'] ?? 'hadith');
            if (!in_array($sourceType, ['quran', 'hadith', 'both'])) {
                $sourceType = 'hadith';
            }

            $quranRef = trim($requestData['quran_reference'] ?? '');
            $surahNum = isset($requestData['surah_number']) && is_numeric($requestData['surah_number']) ? (int)$requestData['surah_number'] : null;
            $verseNum = isset($requestData['verse_number']) && is_numeric($requestData['verse_number']) ? (int)$requestData['verse_number'] : null;

            $hadithRef = trim($requestData['hadith_reference'] ?? '');
            $hadithBook = trim($requestData['hadith_book'] ?? '');
            $hadithNum = trim($requestData['hadith_number'] ?? '');
            $hadithGrading = trim($requestData['hadith_grading'] ?? '');

            $meaningExplanation = trim($requestData['meaning_explanation'] ?? '');
            $benefitsAndVirtues = trim($requestData['benefits_and_virtues'] ?? '');
            $whenToRecite = trim($requestData['when_to_recite'] ?? '');
            $repeatCount = isset($requestData['repeat_count']) && is_numeric($requestData['repeat_count']) ? (int)$requestData['repeat_count'] : 1;

            $userId = (int) $authUser->id;
            $submitterName = $authUser->name ?: trim($requestData['submitter_name'] ?? 'Mobile Contributor');
            $submitterEmail = $authUser->email ?: trim($requestData['submitter_email'] ?? '');

            $wordByWord = null;
            if (!empty($requestData['word_by_word'])) {
                $wbwRaw = $requestData['word_by_word'];
                if (is_array($wbwRaw)) {
                    $wordByWord = json_encode(array_values($wbwRaw));
                } elseif (is_string($wbwRaw)) {
                    $wordByWord = $wbwRaw;
                }
            }

            // Generate unique slug
            $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
            if (empty($baseSlug)) {
                $baseSlug = 'dua';
            }
            $slug = $baseSlug;
            $slugCheck = $pdo->prepare("SELECT id FROM duas WHERE slug = :slug");
            $counter = 1;
            while (true) {
                $slugCheck->execute([':slug' => $slug]);
                if (!$slugCheck->fetch()) {
                    break;
                }
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            $now = date('Y-m-d H:i:s');
            $insertSql = "INSERT INTO duas (
                title, slug, category, description, arabic_text, transliteration, 
                translation_en, translation_ur, word_by_word, meaning_explanation, 
                benefits_and_virtues, when_to_recite, repeat_count, source_type, 
                quran_reference, surah_number, verse_number, hadith_reference, 
                hadith_book, hadith_number, hadith_grading, status, 
                submitted_by_user_id, submitter_name, submitter_email, `order`, is_featured, created_at, updated_at
            ) VALUES (
                :title, :slug, :category, :description, :arabic_text, :transliteration, 
                :translation_en, :translation_ur, :word_by_word, :meaning_explanation, 
                :benefits_and_virtues, :when_to_recite, :repeat_count, :source_type, 
                :quran_reference, :surah_number, :verse_number, :hadith_reference, 
                :hadith_book, :hadith_number, :hadith_grading, 'pending', 
                :submitted_by_user_id, :submitter_name, :submitter_email, 999, 0, :created_at, :updated_at
            )";

            $stmtInsert = $pdo->prepare($insertSql);
            $stmtInsert->execute([
                ':title' => $title,
                ':slug' => $slug,
                ':category' => $category,
                ':description' => $description ?: null,
                ':arabic_text' => $arabicText,
                ':transliteration' => $transliteration ?: null,
                ':translation_en' => $translationEn,
                ':translation_ur' => $translationUr ?: null,
                ':word_by_word' => $wordByWord,
                ':meaning_explanation' => $meaningExplanation ?: null,
                ':benefits_and_virtues' => $benefitsAndVirtues ?: null,
                ':when_to_recite' => $whenToRecite ?: null,
                ':repeat_count' => $repeatCount,
                ':source_type' => $sourceType,
                ':quran_reference' => $quranRef ?: null,
                ':surah_number' => $surahNum,
                ':verse_number' => $verseNum,
                ':hadith_reference' => $hadithRef ?: null,
                ':hadith_book' => $hadithBook ?: null,
                ':hadith_number' => $hadithNum ?: null,
                ':hadith_grading' => $hadithGrading ?: null,
                ':submitted_by_user_id' => $userId,
                ':submitter_name' => $submitterName ?: null,
                ':submitter_email' => $submitterEmail ?: null,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);

            $newId = (int)$pdo->lastInsertId();
            \App\Models\Category::ensureCategory($category, \App\Models\Category::TYPE_DUA, null, null, $userId, $submitterName);

            echo json_encode([
                'status' => 'success',
                'message' => 'JazakAllah Khair! Dua submitted successfully and is queued for administrative review.',
                'data' => [
                    'id' => $newId,
                    'title' => $title,
                    'category' => $category,
                    'status' => 'pending'
                ]
            ]);
            break;

        case 'research_contributions':
            $tab = isset($_GET['tab']) ? strtolower(trim((string)$_GET['tab'])) : 'published';
            $category = isset($_GET['category']) ? strtolower(trim((string)$_GET['category'])) : 'all';
            $surahNumber = isset($_GET['surah_number']) && is_numeric($_GET['surah_number']) ? (int)$_GET['surah_number'] : null;
            $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
            $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 50) : 20;
            $offset = ($page - 1) * $limit;
            $authForResearch = \App\Services\MobileApiAuthService::userFromRequest();
            $userId = $authForResearch ? (int) $authForResearch->id : null;

            if ($tab === 'pending' && empty($userId)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Login is required to view pending items.'
                ]);
                break;
            }

            $whereClauses = ["c.deleted_at IS NULL"];
            $params = [];

            if ($tab === 'pending') {
                $whereClauses[] = "c.status = 'pending'";
            } else {
                $whereClauses[] = "c.status = 'published'";
            }

            if ($category !== 'all' && !empty($category)) {
                $whereClauses[] = "c.category = :category";
                $params[':category'] = $category;
            }

            if ($surahNumber) {
                $whereClauses[] = "(c.surah_number = :surah_number OR (c.end_surah_number IS NOT NULL AND c.surah_number <= :surah_number_s AND c.end_surah_number >= :surah_number_e) OR c.surah_numbers LIKE :surah_json1 OR c.surah_numbers LIKE :surah_json2 OR c.surah_numbers LIKE :surah_json3)";
                $params[':surah_number'] = $surahNumber;
                $params[':surah_number_s'] = $surahNumber;
                $params[':surah_number_e'] = $surahNumber;
                $params[':surah_json1'] = "%[{$surahNumber},%";
                $params[':surah_json2'] = "%,{$surahNumber},%";
                $params[':surah_json3'] = "%{$surahNumber}]%";
            }

            if (!empty($search)) {
                $whereClauses[] = "(c.title LIKE :search1 OR c.description LIKE :search2)";
                $params[':search1'] = "%{$search}%";
                $params[':search2'] = "%{$search}%";
            }

            $whereSql = implode(' AND ', $whereClauses);

            // Count total
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM quran_research_contributions c WHERE {$whereSql}");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            // Fetch items with user's vote if logged in
            $userVoteSelect = $userId ? ", (SELECT vote FROM quran_research_reviews r WHERE r.contribution_id = c.id AND r.user_id = {$userId} LIMIT 1) as my_vote, (SELECT feedback FROM quran_research_reviews r WHERE r.contribution_id = c.id AND r.user_id = {$userId} LIMIT 1) as my_feedback" : ", NULL as my_vote, NULL as my_feedback";

            $sql = "SELECT c.id, c.user_id, c.submitter_name, c.surah_number, c.end_surah_number,
                           c.surah_numbers, c.verse_number, c.end_verse_number, c.is_full_surah,
                           c.category, c.title, c.description, c.video_url, c.video_platform, c.video_id, 
                           c.status, c.approvals_count, c.downvotes_count, c.published_at, c.created_at,
                           u.name as author_name, s.name_transliteration as surah_name, s.name_arabic as surah_arabic
                           {$userVoteSelect}
                    FROM quran_research_contributions c
                    LEFT JOIN users u ON c.user_id = u.id
                    LEFT JOIN surahs s ON c.surah_number = s.number
                    WHERE {$whereSql}
                    ORDER BY c.created_at DESC
                    LIMIT {$limit} OFFSET {$offset}";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll();

            foreach ($items as &$row) {
                $surahNums = !empty($row['surah_numbers']) ? json_decode($row['surah_numbers'], true) : [];
                $row['surah_numbers'] = is_array($surahNums) ? array_values(array_map('intval', $surahNums)) : [];
                $row['is_full_surah'] = (bool)($row['is_full_surah'] ?? false);
                $sNum = (int)$row['surah_number'];
                $eSurah = !empty($row['end_surah_number']) ? (int)$row['end_surah_number'] : null;
                $vNum = (int)($row['verse_number'] ?: 1);
                $eVerse = !empty($row['end_verse_number']) ? (int)$row['end_verse_number'] : null;

                if (count($row['surah_numbers']) > 1 && (!$eSurah || count($row['surah_numbers']) !== ($eSurah - $sNum + 1))) {
                    $row['scope_label'] = 'Surahs ' . implode(', ', $row['surah_numbers']);
                } elseif ($eSurah && $eSurah > $sNum) {
                    $row['scope_label'] = $row['is_full_surah'] ? "Surahs {$sNum}–{$eSurah} (Full)" : "Surahs {$sNum}–{$eSurah}";
                } elseif ($row['is_full_surah']) {
                    $row['scope_label'] = $eVerse ? "Surah {$sNum} (1–{$eVerse} Full)" : "Surah {$sNum} (Full Surah)";
                } elseif ($eVerse && $eVerse > $vNum) {
                    $row['scope_label'] = "Surah {$sNum}:{$vNum}–{$eVerse}";
                } else {
                    $row['scope_label'] = "Surah {$sNum}:{$vNum}";
                }
            }
            unset($row);

            echo json_encode([
                'status' => 'success',
                'data' => $items,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]);
            break;

        case 'submit_research_contribution':
            $title = trim($requestData['title'] ?? '');
            $categoryInput = trim($requestData['custom_category'] ?? ($requestData['category'] ?? 'video'));
            $surahNumber = isset($requestData['surah_number']) && is_numeric($requestData['surah_number']) ? max(1, min(114, (int)$requestData['surah_number'])) : 1;
            $endSurahNumber = isset($requestData['end_surah_number']) && is_numeric($requestData['end_surah_number']) ? max(1, min(114, (int)$requestData['end_surah_number'])) : null;
            $verseNumber = isset($requestData['verse_number']) && is_numeric($requestData['verse_number']) ? max(1, (int)$requestData['verse_number']) : 1;
            $endVerseNumber = isset($requestData['end_verse_number']) && is_numeric($requestData['end_verse_number']) ? max(1, (int)$requestData['end_verse_number']) : null;
            $isFullSurah = !empty($requestData['is_full_surah']) && ($requestData['is_full_surah'] === true || $requestData['is_full_surah'] === 'true' || $requestData['is_full_surah'] === 1 || $requestData['is_full_surah'] === '1');
            $surahNumbersRaw = $requestData['surah_numbers'] ?? null;
            $videoUrl = trim($requestData['video_url'] ?? '');
            $description = trim($requestData['description'] ?? '');
            $userId = (int) $authUser->id;
            $submitterName = $authUser->name ?: trim($requestData['submitter_name'] ?? 'Mobile Contributor');
            $submitterEmail = $authUser->email ?: trim($requestData['submitter_email'] ?? '');

            $catRecord = \App\Models\Category::ensureCategory(
                $categoryInput ?: 'other',
                \App\Models\Category::TYPE_QURAN_RESEARCH,
                null,
                null,
                $userId,
                $submitterName
            );
            $category = $catRecord ? $catRecord->slug : strtolower($categoryInput ?: 'other');

            if (empty($title)) {
                echo json_encode(['status' => 'error', 'message' => 'Contribution title is required.']);
                break;
            }

            if ($category === 'video' && empty($videoUrl)) {
                echo json_encode(['status' => 'error', 'message' => 'Video URL is required for video contributions.']);
                break;
            }

            // Parse surah_numbers (array or comma/range string like "1, 18, 36" or "112-114")
            $surahNumbers = [];
            if (!empty($surahNumbersRaw)) {
                $parts = is_array($surahNumbersRaw) ? $surahNumbersRaw : preg_split('/[\s,;]+/', trim((string)$surahNumbersRaw));
                foreach ($parts as $part) {
                    $part = trim((string)$part);
                    if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $part, $m)) {
                        $s = max(1, min(114, (int)$m[1]));
                        $e = max(1, min(114, (int)$m[2]));
                        for ($i = min($s, $e); $i <= max($s, $e); $i++) {
                            $surahNumbers[] = $i;
                        }
                    } elseif (is_numeric($part)) {
                        $n = (int)$part;
                        if ($n >= 1 && $n <= 114) {
                            $surahNumbers[] = $n;
                        }
                    }
                }
            }
            $surahNumbers = array_values(array_unique($surahNumbers));
            sort($surahNumbers);

            if (count($surahNumbers) > 1) {
                $surahNumber = $surahNumbers[0];
                $isFullSurah = true;
            } elseif ($endSurahNumber && $endSurahNumber > $surahNumber) {
                $surahNumbers = range($surahNumber, $endSurahNumber);
            } else {
                $surahNumbers = [$surahNumber];
            }

            if ($isFullSurah) {
                $verseNumber = 1;
            }

            // Parse video URL for any platform
            $parsed = \App\Models\QuranResearchContribution::parseVideoUrl($videoUrl);
            $platform = $parsed['platform'];
            $videoId = $parsed['id'];

            $now = date('Y-m-d H:i:s');
            $insertSql = "INSERT INTO quran_research_contributions (
                user_id, submitter_name, submitter_email, surah_number, end_surah_number, surah_numbers,
                verse_number, end_verse_number, is_full_surah,
                category, title, description, video_url, video_platform, video_id,
                status, approvals_count, downvotes_count, created_at, updated_at
            ) VALUES (
                :user_id, :submitter_name, :submitter_email, :surah_number, :end_surah_number, :surah_numbers,
                :verse_number, :end_verse_number, :is_full_surah,
                :category, :title, :description, :video_url, :video_platform, :video_id,
                'pending', 0, 0, :created_at, :updated_at
            )";

            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                ':user_id' => $userId,
                ':submitter_name' => $submitterName,
                ':submitter_email' => $submitterEmail ?: null,
                ':surah_number' => $surahNumber,
                ':end_surah_number' => ($endSurahNumber && $endSurahNumber > $surahNumber) ? $endSurahNumber : null,
                ':surah_numbers' => json_encode($surahNumbers),
                ':verse_number' => $verseNumber,
                ':end_verse_number' => ($endVerseNumber && $endVerseNumber >= $verseNumber) ? $endVerseNumber : null,
                ':is_full_surah' => $isFullSurah ? 1 : 0,
                ':category' => $category,
                ':title' => $title,
                ':description' => $description ?: null,
                ':video_url' => $videoUrl ?: null,
                ':video_platform' => $platform,
                ':video_id' => $videoId,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);

            $newContribId = (int)$pdo->lastInsertId();

            echo json_encode([
                'status' => 'success',
                'message' => 'Alhamdulillah! Contribution submitted for peer verification. 5 approvals will publish it.',
                'data' => [
                    'id' => $newContribId,
                    'title' => $title,
                    'category' => $category,
                    'status' => 'pending'
                ]
            ]);
            break;

        case 'get_categories':
            \App\Models\Category::ensureDefaultsSynced();
            $catType = isset($_GET['type']) ? strtolower(trim((string)$_GET['type'])) : 'all';
            $catQuery = \App\Models\Category::active()->orderBy('type')->orderBy('name');
            if (in_array($catType, [\App\Models\Category::TYPE_DUA, \App\Models\Category::TYPE_QURAN_RESEARCH])) {
                $catQuery->where('type', $catType);
            }
            $cats = $catQuery->get(['id', 'name', 'slug', 'type', 'emoji', 'description']);
            echo json_encode([
                'status' => 'success',
                'data' => $cats,
                'dua_categories' => $cats->where('type', \App\Models\Category::TYPE_DUA)->values(),
                'quran_research_categories' => $cats->where('type', \App\Models\Category::TYPE_QURAN_RESEARCH)->values(),
            ]);
            break;

        case 'add_category':
            $catName = trim($requestData['name'] ?? '');
            $catType = strtolower(trim($requestData['type'] ?? 'dua'));
            $catEmoji = trim($requestData['emoji'] ?? '');
            $catDesc = trim($requestData['description'] ?? '');
            $userId = (int) $authUser->id;
            $submitterName = $authUser->name ?: trim($requestData['submitter_name'] ?? 'Mobile Contributor');

            if (empty($catName)) {
                echo json_encode(['status' => 'error', 'message' => 'Category name is required.']);
                break;
            }
            if (!in_array($catType, [\App\Models\Category::TYPE_DUA, \App\Models\Category::TYPE_QURAN_RESEARCH])) {
                echo json_encode(['status' => 'error', 'message' => 'Category type must be "dua" or "quran_research".']);
                break;
            }

            $createdCat = \App\Models\Category::ensureCategory(
                $catName,
                $catType,
                $catEmoji ?: null,
                $catDesc ?: null,
                $userId,
                $submitterName
            );

            // Non-admins cannot self-approve categories (ensureCategory defaults to pending).
            if ($createdCat && $authUser->is_admin) {
                $createdCat->is_active = true;
                $createdCat->status = 'approved';
                $createdCat->save();
            }

            echo json_encode([
                'status' => 'success',
                'message' => $authUser->is_admin
                    ? 'Category added successfully.'
                    : 'Category submitted for admin approval.',
                'data' => $createdCat,
            ]);
            break;

        case 'review_research_contribution':
            $contributionId = isset($requestData['contribution_id']) && is_numeric($requestData['contribution_id']) ? (int)$requestData['contribution_id'] : 0;
            $userId = (int) $authUser->id;
            $vote = trim($requestData['vote'] ?? '');
            $feedback = trim($requestData['feedback'] ?? '');

            if (!in_array($vote, ['approve', 'downvote'], true)) {
                echo json_encode(['status' => 'error', 'message' => 'Vote must be either "approve" or "downvote".']);
                break;
            }

            // Reuse web controller rules (eligibility, sticky publish, vote lock).
            $ctrl = app(\App\Http\Controllers\QuranResearchContributionController::class);
            $req = \Illuminate\Http\Request::create('/api/research-contributions/'.$contributionId.'/review', 'POST', [
                'vote' => $vote,
                'feedback' => $feedback,
            ]);
            $req->setUserResolver(static fn () => $authUser);
            \Illuminate\Support\Facades\Auth::guard('sanctum')->setUser($authUser);
            $response = $ctrl->apiReview($req, $contributionId);
            $payload = $response->getData(true);
            if (($payload['status'] ?? '') !== 'success') {
                echo json_encode([
                    'status' => 'error',
                    'message' => $payload['message'] ?? 'Unable to record vote.',
                ]);
                break;
            }
            echo json_encode([
                'status' => 'success',
                'message' => $payload['message'] ?? 'Vote recorded.',
                'data' => $payload['data'] ?? null,
            ]);
            break;

        case 'submit_app_feedback':
            // Ensure app_feedbacks table exists
            try {
                if ($isSqlite) {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS app_feedbacks (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        user_id INTEGER NULL,
                        rating INTEGER NOT NULL,
                        category VARCHAR(50) DEFAULT 'app_experience',
                        message TEXT NULL,
                        platform VARCHAR(50) DEFAULT 'android',
                        user_name VARCHAR(100) NULL,
                        user_email VARCHAR(150) NULL,
                        device_info VARCHAR(255) NULL,
                        status VARCHAR(20) DEFAULT 'pending',
                        admin_notes TEXT NULL,
                        created_at DATETIME NULL,
                        updated_at DATETIME NULL
                    )");
                } else {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS app_feedbacks (
                        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        user_id BIGINT UNSIGNED NULL,
                        rating TINYINT UNSIGNED NOT NULL,
                        category VARCHAR(50) NOT NULL DEFAULT 'app_experience',
                        message TEXT NULL,
                        platform VARCHAR(50) NOT NULL DEFAULT 'android',
                        user_name VARCHAR(100) NULL,
                        user_email VARCHAR(150) NULL,
                        device_info VARCHAR(255) NULL,
                        status VARCHAR(20) NOT NULL DEFAULT 'pending',
                        admin_notes TEXT NULL,
                        created_at TIMESTAMP NULL,
                        updated_at TIMESTAMP NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                }
            } catch (Exception $tableEx) {
                // Ignore if already created
            }

            $rating = isset($requestData['rating']) && is_numeric($requestData['rating']) ? (int)$requestData['rating'] : 5;
            $rating = max(1, min(5, $rating));
            $category = trim($requestData['category'] ?? 'app_experience');
            $message = trim($requestData['message'] ?? '');
            $platform = trim($requestData['platform'] ?? 'android');
            $authForFeedback = \App\Services\MobileApiAuthService::userFromRequest();
            $userId = $authForFeedback ? (int) $authForFeedback->id : null;
            $userName = $authForFeedback
                ? ($authForFeedback->name ?: trim($requestData['user_name'] ?? ''))
                : trim($requestData['user_name'] ?? '');
            $userEmail = $authForFeedback
                ? ($authForFeedback->email ?: trim($requestData['user_email'] ?? ''))
                : trim($requestData['user_email'] ?? '');
            $deviceInfo = trim($requestData['device_info'] ?? '');
            $now = date('Y-m-d H:i:s');

            $stmt = $pdo->prepare("INSERT INTO app_feedbacks (user_id, rating, category, message, platform, user_name, user_email, device_info, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)");
            $stmt->execute([
                $userId ?: null,
                $rating,
                $category ?: 'app_experience',
                $message ?: null,
                $platform ?: 'android',
                $userName ?: null,
                $userEmail ?: null,
                $deviceInfo ?: null,
                $now,
                $now
            ]);
            $feedbackId = (int)$pdo->lastInsertId();

            echo json_encode([
                'status' => 'success',
                'message' => 'JazakAllahu Khairan! Thank you for sharing your feedback.',
                'data' => [
                    'id' => $feedbackId,
                    'rating' => $rating,
                    'category' => $category
                ]
            ]);
            break;

        case 'submit_donation_notice':
        case 'donation_notice':
            // Ensure donation_records table exists
            try {
                if ($isSqlite) {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS donation_records (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        user_id INTEGER NULL,
                        donor_name VARCHAR(255) NULL,
                        is_anonymous INTEGER NOT NULL DEFAULT 0,
                        donor_email VARCHAR(255) NULL,
                        amount REAL NULL,
                        currency VARCHAR(10) DEFAULT 'USD',
                        payment_method VARCHAR(255) NULL,
                        transaction_reference VARCHAR(255) NULL,
                        message TEXT NULL,
                        status VARCHAR(50) DEFAULT 'pending',
                        created_at DATETIME NULL,
                        updated_at DATETIME NULL
                    )");
                } else {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS donation_records (
                        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        user_id BIGINT UNSIGNED NULL,
                        donor_name VARCHAR(255) NULL,
                        is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
                        donor_email VARCHAR(255) NULL,
                        amount DECIMAL(10,2) NULL,
                        currency VARCHAR(10) NOT NULL DEFAULT 'USD',
                        payment_method VARCHAR(255) NULL,
                        transaction_reference VARCHAR(255) NULL,
                        message TEXT NULL,
                        status VARCHAR(50) NOT NULL DEFAULT 'pending',
                        created_at TIMESTAMP NULL,
                        updated_at TIMESTAMP NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                    // Ensure is_anonymous column exists
                    try {
                        $colCheck = $pdo->query("SHOW COLUMNS FROM donation_records LIKE 'is_anonymous'");
                        if (!$colCheck->fetch()) {
                            $pdo->exec("ALTER TABLE donation_records ADD COLUMN is_anonymous TINYINT(1) NOT NULL DEFAULT 0 AFTER donor_name");
                        }
                    } catch (Exception $colEx) {}
                }
            } catch (Exception $tableEx) {
                // Ignore if already created
            }

            $authForDonation = \App\Services\MobileApiAuthService::userFromRequest();
            $userId = $authForDonation ? (int) $authForDonation->id : null;
            $donorName = trim($requestData['donor_name'] ?? '');
            $isAnonymous = !empty($requestData['is_anonymous']) && ($requestData['is_anonymous'] === true || $requestData['is_anonymous'] === '1' || $requestData['is_anonymous'] === 1) ? 1 : 0;
            $donorEmail = trim($requestData['donor_email'] ?? '');
            if ($authForDonation) {
                $donorName = $donorName !== '' ? $donorName : ($authForDonation->name ?: '');
                $donorEmail = $donorEmail !== '' ? $donorEmail : ($authForDonation->email ?: '');
            }
            $amount = isset($requestData['amount']) && is_numeric($requestData['amount']) ? (float)$requestData['amount'] : null;
            $currency = strtoupper(trim($requestData['currency'] ?? 'USD'));
            $paymentMethod = trim($requestData['payment_method'] ?? 'Manual Transfer');
            $transactionRef = trim($requestData['transaction_reference'] ?? '');
            $message = trim($requestData['message'] ?? '');
            $now = date('Y-m-d H:i:s');

            if (!$amount || $amount < 0.5) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Please provide a valid donation amount (minimum 0.50).'
                ]);
                break;
            }

            $stmt = $pdo->prepare("INSERT INTO donation_records (user_id, donor_name, is_anonymous, donor_email, amount, currency, payment_method, transaction_reference, message, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)");
            $stmt->execute([
                $userId ?: null,
                $donorName ?: null,
                $isAnonymous,
                $donorEmail ?: null,
                $amount,
                $currency ?: 'USD',
                $paymentMethod ?: 'Manual Transfer',
                $transactionRef ?: null,
                $message ?: null,
                $now,
                $now
            ]);
            $donationId = (int)$pdo->lastInsertId();

            echo json_encode([
                'status' => 'success',
                'message' => 'JazakAllahu Khairan! Your donation notification has been submitted.',
                'data' => [
                    'id' => $donationId,
                    'is_anonymous' => (bool)$isAnonymous,
                    'amount' => $amount,
                    'currency' => $currency
                ]
            ]);
            break;

        case 'logout':
            $authUser->tokens()->where('name', \App\Services\MobileApiAuthService::TOKEN_NAME)->delete();
            echo json_encode([
                'status' => 'success',
                'message' => 'Logged out successfully.',
            ]);
            break;

        default:
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid action parameter specified.'
            ]);
            break;
    }
} catch (Exception $e) {
    \Illuminate\Support\Facades\Log::error('Mobile API exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    echo json_encode([
        'status' => 'error',
        'message' => 'An unexpected error occurred. Please try again.'
    ]);
}

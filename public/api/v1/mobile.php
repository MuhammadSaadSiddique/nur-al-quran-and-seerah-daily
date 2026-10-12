<?php

@header('Content-Type: application/json; charset=utf-8');
@header('Access-Control-Allow-Origin: *');
@header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

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

    $action = isset($requestData['action']) ? trim((string)$requestData['action']) : '';

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
            echo json_encode([
                'status' => 'success',
                'surah_number' => $surahNumber,
                'data' => $verses
            ]);
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
            $seerahPage = isset($_GET['seerah_page']) ? (int)$_GET['seerah_page'] : 1;
            $historyPage = isset($_GET['history_page']) ? (int)$_GET['history_page'] : 1;
            $limit = 5;

            $seerahOffset = ($seerahPage - 1) * $limit;
            $historyOffset = ($historyPage - 1) * $limit;

            $seerahCategory = isset($_GET['seerah_category']) && $_GET['seerah_category'] !== '' ? $_GET['seerah_category'] : null;
            $historyCategory = isset($_GET['history_category']) && $_GET['history_category'] !== '' ? $_GET['history_category'] : null;

            // Get categories
            $seerahCategories = $pdo->query("SELECT DISTINCT category FROM seerat_events WHERE category IS NOT NULL AND category != '' ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
            $historyCategories = $pdo->query("SELECT DISTINCT {$historyExtraCol} FROM {$historyTable} WHERE {$historyExtraCol} IS NOT NULL AND {$historyExtraCol} != '' ORDER BY {$historyExtraCol} ASC")->fetchAll(PDO::FETCH_COLUMN);

            // Seerah where clause
            $seerahWhere = "";
            $seerahParams = [];
            if ($seerahCategory !== null) {
                $seerahWhere = "WHERE category = :category";
                $seerahParams['category'] = $seerahCategory;
            }

            // Seerah count
            $stmtCountSeer = $pdo->prepare("SELECT COUNT(*) FROM seerat_events $seerahWhere");
            foreach ($seerahParams as $k => $v) {
                $stmtCountSeer->bindValue($k, $v);
            }
            $stmtCountSeer->execute();
            $totalSeer = $stmtCountSeer->fetchColumn();
            $seerahTotalPages = max(1, (int)ceil($totalSeer / $limit));

            // Fetch Seerah
            $stmtSeer = $pdo->prepare("SELECT id, title, description, category FROM seerat_events $seerahWhere ORDER BY id ASC LIMIT :limit OFFSET :offset");
            foreach ($seerahParams as $k => $v) {
                $stmtSeer->bindValue($k, $v);
            }
            $stmtSeer->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmtSeer->bindValue(':offset', $seerahOffset, PDO::PARAM_INT);
            $stmtSeer->execute();
            $seeratEvents = $stmtSeer->fetchAll();

            // History where clause
            $historyWhere = "";
            $historyParams = [];
            if ($historyCategory !== null) {
                $historyWhere = "WHERE {$historyExtraCol} = :category";
                $historyParams['category'] = $historyCategory;
            }

            // History count
            $stmtCountHist = $pdo->prepare("SELECT COUNT(*) FROM {$historyTable} $historyWhere");
            foreach ($historyParams as $k => $v) {
                $stmtCountHist->bindValue($k, $v);
            }
            $stmtCountHist->execute();
            $totalHist = $stmtCountHist->fetchColumn();
            $historyTotalPages = max(1, (int)ceil($totalHist / $limit));

            // Fetch History
            $stmtHist = $pdo->prepare("SELECT id, title, description, {$historyExtraCol} as category FROM {$historyTable} $historyWhere ORDER BY id ASC LIMIT :limit OFFSET :offset");
            foreach ($historyParams as $k => $v) {
                $stmtHist->bindValue($k, $v);
            }
            $stmtHist->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmtHist->bindValue(':offset', $historyOffset, PDO::PARAM_INT);
            $stmtHist->execute();
            $historyEvents = $stmtHist->fetchAll();

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
            $limit = isset($_GET['quantity']) ? (int) $_GET['quantity'] : (isset($_GET['limit']) ? (int) $_GET['limit'] : 20);
            if ($limit <= 0) $limit = 20;

            $randFunc = $isSqlite ? 'RANDOM()' : 'RAND()';
            $stmt = $pdo->prepare("SELECT id, question_id, text, options, correct_answer_index, explanation, difficulty, reference, source_info 
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
                $stmt = $pdo->prepare("SELECT id, question_id, text, options, correct_answer_index, explanation, difficulty, reference, source_info 
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
                $questions[] = $row;
            }

            echo json_encode([
                'status' => 'success',
                'theme_id' => $themeId,
                'difficulty' => $difficulty,
                'quantity' => count($questions),
                'data' => $questions
            ]);
            break;

        case 'grand_quiz':
            $quizType = isset($_GET['quiz_type']) ? strtoupper(trim((string)$_GET['quiz_type'])) : 'QURAN';
            $difficulty = isset($_GET['difficulty']) ? ucfirst(strtolower(trim((string)$_GET['difficulty']))) : 'Medium';
            $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : (isset($_GET['limit']) ? (int)$_GET['limit'] : 20);
            if ($quantity <= 0) $quantity = 20;

            $type = ($quizType === 'SEERAH' || $quizType === 'GRAND_SEERAH') ? 'SEERAH' : 'PARA';
            $randFunc = $isSqlite ? 'RANDOM()' : 'RAND()';

            $stmt = $pdo->prepare("SELECT id, question_id, text, options, correct_answer_index, explanation, difficulty, reference, source_info 
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
                $stmt = $pdo->prepare("SELECT id, question_id, text, options, correct_answer_index, explanation, difficulty, reference, source_info 
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
                $questions[] = $row;
            }

            echo json_encode([
                'status' => 'success',
                'quiz_type' => $quizType,
                'difficulty' => $difficulty,
                'quantity' => count($questions),
                'data' => $questions
            ]);
            break;

        case 'login':
            $email = isset($requestData['email']) ? trim((string)$requestData['email']) : '';
            $password = isset($requestData['password']) ? (string)$requestData['password'] : '';

            if (empty($email) || empty($password)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Email and password are required.'
                ]);
                break;
            }

            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                if (empty($user['password'])) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'You registered via OTP and do not have a password set yet. Please log in via OTP first.'
                    ]);
                    break;
                }
                
                if (password_verify($password, $user['password'])) {
                    $streak = calculate_user_streak($pdo, $user['id'], true);
                    echo json_encode([
                        'status' => 'success',
                        'data' => [
                            'user_id' => (int)$user['id'],
                            'name' => $user['name'] ?: $user['display_name'] ?: 'User',
                            'email' => $user['email'],
                            'total_score' => (int)$user['total_score'],
                            'streak' => $streak
                        ]
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Invalid email or password.'
                    ]);
                }
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
            if (empty($email)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Email is required.'
                ]);
                break;
            }

            // Generate OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Delete old OTPs and create new
            \App\Models\OtpCode::where('email', $email)->delete();
            \App\Models\OtpCode::create([
                'email' => $email,
                'otp' => $otp,
                'expires_at' => now()->addMinutes(5),
            ]);

            // Send OTP email
            try {
                \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\OtpMail($otp));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Mobile: Failed to send OTP email', ['error' => $e->getMessage()]);
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

            $record = \App\Models\OtpCode::where('email', $email)->latest()->first();

            if (!$record) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Verification code not found. Please request a new one.'
                ]);
                break;
            }

            if ($record->isExpired()) {
                $record->delete();
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Verification code expired. Please request a new one.'
                ]);
                break;
            }

            if ($record->otp !== $otp) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid verification code.'
                ]);
                break;
            }

            // Valid - clean up
            $record->delete();

            // Find or create user
            $user = \App\Models\User::firstOrCreate(
                ['email' => $email],
                ['name' => explode('@', $email)[0]]
            );

            $streak = calculate_user_streak($pdo, $user->id, true);

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'user_id' => (int)$user->id,
                    'name' => $user->name ?: $user->display_name ?: 'User',
                    'email' => $user->email,
                    'total_score' => (int)$user->total_score,
                    'has_password' => !empty($user->password),
                    'streak' => $streak
                ]
            ]);
            break;

        case 'set_password':
            $userId = isset($requestData['user_id']) ? (int)$requestData['user_id'] : 0;
            $password = isset($requestData['password']) ? (string)$requestData['password'] : '';

            if ($userId <= 0 || empty($password)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User ID and password are required.'
                ]);
                break;
            }

            $user = \App\Models\User::find($userId);
            if (!$user) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User not found.'
                ]);
                break;
            }

            $user->password = \Illuminate\Support\Facades\Hash::make($password);
            $user->save();

            echo json_encode([
                'status' => 'success',
                'message' => 'Password set successfully.'
            ]);
            break;

        case 'change_password':
            $userId = isset($requestData['user_id']) ? (int)$requestData['user_id'] : 0;
            $password = isset($requestData['password']) ? (string)$requestData['password'] : '';

            if ($userId <= 0 || empty($password)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User ID and password are required.'
                ]);
                break;
            }

            $user = \App\Models\User::find($userId);
            if (!$user) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User not found.'
                ]);
                break;
            }

            $user->password = \Illuminate\Support\Facades\Hash::make($password);
            $user->save();

            echo json_encode([
                'status' => 'success',
                'message' => 'Password updated successfully.'
            ]);
            break;

        case 'delete_account':
            $userId = isset($requestData['user_id']) ? (int)$requestData['user_id'] : 0;

            if ($userId <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User ID is required.'
                ]);
                break;
            }

            $user = \App\Models\User::find($userId);
            if (!$user) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User not found.'
                ]);
                break;
            }

            \App\Http\Controllers\AccountDeletionController::performAccountDeletion($user);

            echo json_encode([
                'status' => 'success',
                'message' => 'Account deleted successfully.'
            ]);
            break;

        case 'submit_quiz':
            $userId = isset($requestData['user_id']) ? (int)$requestData['user_id'] : 0;
            $type = isset($requestData['type']) ? (string)$requestData['type'] : 'THEME';
            $title = isset($requestData['title']) ? (string)$requestData['title'] : '';
            $score = isset($requestData['score']) ? (int)$requestData['score'] : 0;
            $totalQuestions = isset($requestData['total_questions']) ? (int)$requestData['total_questions'] : 0;
            $difficulty = isset($requestData['difficulty']) ? (string)$requestData['difficulty'] : 'Medium';
            $questionsJson = isset($requestData['questions']) ? (string)$requestData['questions'] : '[]';
            $userAnswersJson = isset($requestData['user_answers']) ? (string)$requestData['user_answers'] : '[]';

            if ($userId <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User ID is required.'
                ]);
                break;
            }

            // Verify user exists
            $stmt = $pdo->prepare("SELECT id, total_score, total_questions FROM users WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch();
            if (!$user) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User not found.'
                ]);
                break;
            }

            // Insert into quizzes table
            $quizId = uniqid('quiz_');
            $details = json_encode([
                'questions' => json_decode($questionsJson, true),
                'userAnswers' => json_decode($userAnswersJson, true),
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

            // Update user stats
            $newScore = $user['total_score'] + $score;
            $newTotalQuestions = $user['total_questions'] + $totalQuestions;
            $stmt = $pdo->prepare("UPDATE users SET total_score = :total_score, total_questions = :total_questions, updated_at = :updated_at WHERE id = :id");
            $stmt->execute([
                'total_score' => $newScore,
                'total_questions' => $newTotalQuestions,
                'updated_at' => $now,
                'id' => $userId
            ]);

            $streakData = calculate_user_streak($pdo, $userId, true);

            echo json_encode([
                'status' => 'success',
                'message' => 'Quiz attempt submitted successfully.',
                'data' => [
                    'quiz_id' => $quizId,
                    'new_total_score' => $newScore,
                    'streak' => $streakData
                ]
            ]);
            break;

        case 'daily_streak':
        case 'streak_check_in':
            $userId = isset($requestData['user_id']) ? (int)$requestData['user_id'] : (isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0);
            if ($userId <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User ID is required.'
                ]);
                break;
            }
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
            $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (isset($requestData['user_id']) ? (int)$requestData['user_id'] : 0);
            if ($userId <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User ID is required.'
                ]);
                break;
            }
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

        case 'leaderboard':
            $stmt = $pdo->query("SELECT id, name, display_name, email, total_score, total_questions, seerah_read_count 
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
            $userId = isset($requestData['user_id']) && is_numeric($requestData['user_id']) ? (int)$requestData['user_id'] : null;
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

            $submitterName = trim($requestData['submitter_name'] ?? 'Mobile Contributor');
            $submitterEmail = trim($requestData['submitter_email'] ?? '');
            $userId = isset($requestData['user_id']) && is_numeric($requestData['user_id']) ? (int)$requestData['user_id'] : null;

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
            \App\Models\Category::ensureCategory($category, \App\Models\Category::TYPE_DUA, $userId, $submitterName);

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
            $userId = isset($_GET['user_id']) && is_numeric($_GET['user_id']) ? (int)$_GET['user_id'] : null;

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

            $sql = "SELECT c.id, c.user_id, c.submitter_name, c.submitter_email, c.surah_number, c.end_surah_number,
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
            $submitterName = trim($requestData['submitter_name'] ?? 'Mobile Contributor');
            $submitterEmail = trim($requestData['submitter_email'] ?? '');
            $userId = isset($requestData['user_id']) && is_numeric($requestData['user_id']) ? (int)$requestData['user_id'] : null;

            $catRecord = \App\Models\Category::ensureCategory(
                $categoryInput ?: 'other',
                \App\Models\Category::TYPE_QURAN_RESEARCH,
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
            $submitterName = trim($requestData['submitter_name'] ?? 'Mobile Contributor');
            $userId = isset($requestData['user_id']) && is_numeric($requestData['user_id']) ? (int)$requestData['user_id'] : null;

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
                $userId,
                $submitterName,
                $catEmoji ?: null,
                $catDesc ?: null
            );

            echo json_encode([
                'status' => 'success',
                'message' => 'Category added successfully.',
                'data' => $createdCat,
            ]);
            break;

        case 'review_research_contribution':
            $contributionId = isset($requestData['contribution_id']) && is_numeric($requestData['contribution_id']) ? (int)$requestData['contribution_id'] : 0;
            $userId = isset($requestData['user_id']) && is_numeric($requestData['user_id']) ? (int)$requestData['user_id'] : null;
            $vote = trim($requestData['vote'] ?? '');
            $feedback = trim($requestData['feedback'] ?? '');

            if (!$userId) {
                echo json_encode(['status' => 'error', 'message' => 'You must be logged in to verify or vote on contributions.']);
                break;
            }

            if (!in_array($vote, ['approve', 'downvote'])) {
                echo json_encode(['status' => 'error', 'message' => 'Vote must be either "approve" or "downvote".']);
                break;
            }

            $checkStmt = $pdo->prepare("SELECT id FROM quran_research_contributions WHERE id = ? AND deleted_at IS NULL");
            $checkStmt->execute([$contributionId]);
            if (!$checkStmt->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Contribution not found.']);
                break;
            }

            $now = date('Y-m-d H:i:s');
            // Insert or update review
            $reviewCheck = $pdo->prepare("SELECT id FROM quran_research_reviews WHERE contribution_id = ? AND user_id = ?");
            $reviewCheck->execute([$contributionId, $userId]);
            $existingReview = $reviewCheck->fetch();

            if ($existingReview) {
                $updateReview = $pdo->prepare("UPDATE quran_research_reviews SET vote = ?, feedback = ?, updated_at = ? WHERE id = ?");
                $updateReview->execute([$vote, $feedback ?: null, $now, $existingReview['id']]);
            } else {
                $insertReview = $pdo->prepare("INSERT INTO quran_research_reviews (contribution_id, user_id, vote, feedback, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
                $insertReview->execute([$contributionId, $userId, $vote, $feedback ?: null, $now, $now]);
            }

            // Recalculate
            $countApprove = (int)$pdo->query("SELECT COUNT(*) FROM quran_research_reviews WHERE contribution_id = {$contributionId} AND vote = 'approve'")->fetchColumn();
            $countDownvote = (int)$pdo->query("SELECT COUNT(*) FROM quran_research_reviews WHERE contribution_id = {$contributionId} AND vote = 'downvote'")->fetchColumn();

            $newStatus = 'pending';
            $publishedAt = null;
            if ($countApprove >= 5 && $countDownvote < $countApprove) {
                $newStatus = 'published';
                $publishedAt = $now;
            }

            if ($newStatus === 'published') {
                $updateContrib = $pdo->prepare("UPDATE quran_research_contributions SET status = 'published', published_at = COALESCE(published_at, ?), approvals_count = ?, downvotes_count = ?, updated_at = ? WHERE id = ?");
                $updateContrib->execute([$now, $countApprove, $countDownvote, $now, $contributionId]);
            } else {
                $updateContrib = $pdo->prepare("UPDATE quran_research_contributions SET status = 'pending', approvals_count = ?, downvotes_count = ?, updated_at = ? WHERE id = ?");
                $updateContrib->execute([$countApprove, $countDownvote, $now, $contributionId]);
            }

            echo json_encode([
                'status' => 'success',
                'message' => $newStatus === 'published' ? 'Review saved. Item has reached 5 approvals and is now PUBLISHED!' : "Review saved. Status: {$newStatus} (Approvals: {$countApprove}, Downvotes: {$countDownvote})",
                'data' => [
                    'id' => $contributionId,
                    'status' => $newStatus,
                    'approvals_count' => $countApprove,
                    'downvotes_count' => $countDownvote
                ]
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

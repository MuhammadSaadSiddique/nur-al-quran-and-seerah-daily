<?php

@header('Content-Type: application/json; charset=utf-8');
@header('Access-Control-Allow-Origin: *');
@header('Access-Control-Allow-Methods: GET');

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
                        @http_response_code(429);
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
            $dsn = "mysql:host={$config['DB_HOST']};port={$config['DB_PORT']};dbname={$config['DB_DATABASE']};charset=utf8mb4";
            return new PDO($dsn, $config['DB_USERNAME'], $config['DB_PASSWORD'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
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

    $action = isset($_GET['action']) ? $_GET['action'] : '';

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
            $stmtSci = $pdo->prepare("SELECT f.title, f.description, f.field, l.relevance_description, l.status
                                       FROM quran_science_links l
                                       JOIN verses v ON l.verse_id = v.id
                                       JOIN surahs s ON v.surah_id = s.id
                                       JOIN science_facts f ON l.science_fact_id = f.id
                                       WHERE s.number = ? AND v.verse_number = ? AND l.status = 'approved'");
            $stmtSci->execute([$surahNumber, $verseNumber]);
            $scienceLinks = $stmtSci->fetchAll();

            // 2. Seerat Links
            $stmtSeer = $pdo->prepare("SELECT e.title, e.description, e.category, l.description as link_description, l.status
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
            $stmtHad = $pdo->prepare("SELECT a.hadith_number, a.{$hadithTextCol} as text, c.{$hadithCollCol} as collection_name, l.description as link_description, l.status
                                       FROM quran_hadith_links l
                                       JOIN verses v ON l.verse_id = v.id
                                       JOIN surahs s ON v.surah_id = s.id
                                       JOIN ahadith a ON l.hadith_id = a.id
                                       LEFT JOIN hadith_collections c ON a.collection_id = c.id
                                       WHERE s.number = ? AND v.verse_number = ? AND l.status = 'approved'");
            $stmtHad->execute([$surahNumber, $verseNumber]);
            $hadithLinks = $stmtHad->fetchAll();

            // 4. History Links
            $stmtHist = $pdo->prepare("SELECT h.title, h.description, h.{$historyExtraCol} as extra_info, l.status
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
            $stmt = $pdo->query("SELECT id, name, type, description FROM themes ORDER BY name ASC");
            $themes = $stmt->fetchAll();
            echo json_encode([
                'status' => 'success',
                'data' => $themes
            ]);
            break;

        case 'theme_quiz':
            $themeId = isset($_GET['theme_id']) ? (int) $_GET['theme_id'] : 0;
            $difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : 'Medium';
            $limit = 10;

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

            if (empty($questionsRaw)) {
                $stmt = $pdo->prepare("SELECT id, question_id, text, options, correct_answer_index, explanation, difficulty, reference, source_info 
                                       FROM generated_questions 
                                       WHERE theme_id = :theme_id 
                                       ORDER BY $randFunc LIMIT :limit");
                $stmt->bindValue(':theme_id', $themeId, PDO::PARAM_INT);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $questionsRaw = $stmt->fetchAll();
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
                'data' => $questions
            ]);
            break;

        case 'login':
            $email = isset($_POST['email']) ? $_POST['email'] : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            if (empty($email) || empty($password)) {
                @http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Email and password are required.'
                ]);
                break;
            }

            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'user_id' => (int)$user['id'],
                        'name' => $user['name'] ?: $user['display_name'] ?: 'User',
                        'email' => $user['email'],
                        'total_score' => (int)$user['total_score']
                    ]
                ]);
            } else {
                @http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid email or password.'
                ]);
            }
            break;

        case 'register':
            $name = isset($_POST['name']) ? $_POST['name'] : '';
            $email = isset($_POST['email']) ? $_POST['email'] : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            if (empty($name) || empty($email) || empty($password)) {
                @http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Name, email, and password are required.'
                ]);
                break;
            }

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                @http_response_code(409);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User with this email already exists.'
                ]);
                break;
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, total_score, total_questions, seerah_read_count, created_at, updated_at) 
                                   VALUES (:name, :email, :password, 0, 0, 0, :created_at, :updated_at)");
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
                'created_at' => $now,
                'updated_at' => $now
            ]);

            $userId = $pdo->lastInsertId();

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'user_id' => (int)$userId,
                    'name' => $name,
                    'email' => $email,
                    'total_score' => 0
                ]
            ]);
            break;

        case 'submit_quiz':
            $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
            $type = isset($_POST['type']) ? $_POST['type'] : 'THEME';
            $title = isset($_POST['title']) ? $_POST['title'] : '';
            $score = isset($_POST['score']) ? (int)$_POST['score'] : 0;
            $totalQuestions = isset($_POST['total_questions']) ? (int)$_POST['total_questions'] : 0;
            $difficulty = isset($_POST['difficulty']) ? $_POST['difficulty'] : 'Medium';
            $questionsJson = isset($_POST['questions']) ? $_POST['questions'] : '[]';
            $userAnswersJson = isset($_POST['user_answers']) ? $_POST['user_answers'] : '[]';

            if ($userId <= 0) {
                @http_response_code(400);
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
                @http_response_code(404);
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

            echo json_encode([
                'status' => 'success',
                'message' => 'Quiz attempt submitted successfully.',
                'data' => [
                    'quiz_id' => $quizId,
                    'new_total_score' => $newScore
                ]
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

        default:
            @http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid action parameter specified.'
            ]);
            break;
    }
} catch (Exception $e) {
    @http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
}

<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\GeneratedQuestion;
use App\Models\Theme;

$total = GeneratedQuestion::count();
$linked = 0;
$mismatched = 0;

echo "Processing $total questions...\n";

// Get all themes for lookup
$themes = Theme::all()->groupBy('type')->map(function ($items) {
    return $items->pluck('id', 'name');
});

GeneratedQuestion::chunk(500, function ($questions) use (&$linked, &$mismatched, $themes) {
    foreach ($questions as $q) {
        if (!$q->theme)
            continue;

        $themeId = $themes[$q->type][$q->theme] ?? null;

        if ($themeId) {
            $q->update(['theme_id' => $themeId]);
            $linked++;
        } else {
            $mismatched++;
        }
    }
    echo "Progress: $linked linked...\n";
});

echo "\nData Migration Complete!\n";
echo "Total processed: $total\n";
echo "Successfully linked: $linked\n";
echo "Mismatched (not found in themes table): $mismatched\n";

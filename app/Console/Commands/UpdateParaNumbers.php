<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GeneratedQuestion;

class UpdateParaNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-para-numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Para numbers for Quran questions based on Surah and Ayat references';

    /**
     * Para start points: [para_number, surah_number, ayat_number]
     */
    protected $paraStarts = [
        [1, 1, 1],     [2, 2, 142],   [3, 2, 253],   [4, 3, 93],    [5, 4, 24],
        [6, 4, 148],   [7, 5, 82],    [8, 6, 111],   [9, 7, 88],    [10, 8, 41],
        [11, 9, 93],   [12, 11, 6],   [13, 12, 53],  [14, 15, 1],   [15, 17, 1],
        [16, 18, 75],  [17, 21, 1],   [18, 23, 1],   [19, 25, 21],  [20, 27, 56],
        [21, 29, 46],  [22, 33, 31],  [23, 36, 28],  [24, 39, 32],  [25, 41, 47],
        [26, 46, 1],   [27, 51, 31],  [28, 58, 1],   [29, 67, 1],   [30, 78, 1]
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $questions = GeneratedQuestion::where('type', 'PARA')->get();
        $updatedCount = 0;
        $skippedCount = 0;

        $this->info("Processing " . $questions->count() . " questions...");

        foreach ($questions as $q) {
            $searchString = ($q->source_info ?? '') . ' ' . ($q->reference ?? '');
            
            // Regex to find Surah:Ayat (e.g., 2:142)
            if (preg_match('/(\d+):(\d+)/', $searchString, $matches)) {
                $surah = (int)$matches[1];
                $ayat = (int)$matches[2];
                $para = $this->calculatePara($surah, $ayat);

                $newSourceInfo = $this->formatContent($para, $q->source_info);
                $newReference = $this->formatContent($para, $q->reference);

                $q->source_info = $newSourceInfo;
                $q->reference = $newReference;
                $q->save();
                
                $updatedCount++;
            } else {
                $skippedCount++;
            }
        }

        $this->info("Successfully updated $updatedCount questions.");
        if ($skippedCount > 0) {
            $this->warn("Skipped $skippedCount questions (no Surah:Ayat reference found in source_info or reference).");
        }
    }

    /**
     * Calculate Para number from Surah and Ayat.
     */
    private function calculatePara($surah, $ayat)
    {
        $foundPara = 1;
        foreach ($this->paraStarts as $start) {
            $startPara = $start[0];
            $startSurah = $start[1];
            $startAyat = $start[2];

            if ($surah > $startSurah || ($surah == $startSurah && $ayat >= $startAyat)) {
                $foundPara = $startPara;
            } else {
                break;
            }
        }
        return $foundPara;
    }

    /**
     * Format the content to include the Para number as a prefix.
     */
    private function formatContent($para, $original)
    {
        if (empty($original)) return "Para $para";

        // Remove existing "Para X: " or "Para X" prefixes to avoid duplication or correction
        $cleaned = preg_replace('/^Para\s+\d+:?\s*/i', '', $original);
        
        return "Para $para: " . $cleaned;
    }
}

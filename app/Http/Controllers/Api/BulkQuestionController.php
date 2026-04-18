<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneratedQuestion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BulkQuestionController extends Controller
{
    /**
     * Normalize question text for duplicate detection.
     * Strips punctuation, lowercases, removes common filler words.
     */
    private function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text));
        // Remove punctuation
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        // Remove common filler words
        $fillers = ['what', 'is', 'the', 'of', 'was', 'a', 'an', 'in', 'to', 'and', 'for', 'that', 'which', 'who', 'whom', 'how', 'did', 'does', 'do', 'are', 'were', 'has', 'have', 'had', 'be', 'been', 'being', 'its', 'it', 'this', 'their', 'from', 'with', 'by', 'on', 'at', 'as', 'or', 'not', 'but', 'about', 'name', 'following', 'among', 'during', 'after', 'before'];
        $words = preg_split('/\s+/', $text);
        $words = array_diff($words, $fillers);
        sort($words);
        return implode(' ', $words);
    }
    public function store(Request $request)
    {
        // Must be an array of objects
        if (!$request->isJson() || !is_array($request->json()->all())) {
            return response()->json(['error' => 'Payload must be a JSON array of questions.'], 400);
        }

        $questions = $request->json()->all();
        $successful = 0;
        $skipped = 0;
        $errors = [];

        foreach ($questions as $index => $q) {
            $validator = Validator::make($q, [
                'type' => 'required|in:PARA,SEERAH',
                'difficulty' => 'required|in:Easy,Medium,Hard',
                'theme' => 'nullable|string',
                'text' => 'required|string',
                'options' => 'required|array|min:2|max:4',
                'correct_answer_index' => 'required|integer|between:0,3',
                'explanation' => 'required|string',
                'reference' => 'nullable|string',
                'source_info' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                $errors[] = [
                    'index' => $index,
                    'errors' => $validator->errors()->all()
                ];
                continue;
            }

            // Smart duplicate detection: normalize text and compare
            $normalizedNew = $this->normalizeText($q['text']);
            $isDuplicate = false;

            // Check against questions with same correct answer option text
            $correctOptionText = $q['options'][$q['correct_answer_index']] ?? '';
            $candidates = GeneratedQuestion::where('type', $q['type'])
                ->where('difficulty', $q['difficulty'])
                ->get();

            foreach ($candidates as $existing) {
                $normalizedExisting = $this->normalizeText($existing->text);
                // Exact normalized match
                if ($normalizedNew === $normalizedExisting) {
                    $isDuplicate = true;
                    break;
                }
                // Same correct answer + high word overlap (>70%)
                $existingCorrect = $existing->options[$existing->correct_answer_index] ?? '';
                if (mb_strtolower(trim($correctOptionText)) === mb_strtolower(trim($existingCorrect))) {
                    $newWords = explode(' ', $normalizedNew);
                    $existWords = explode(' ', $normalizedExisting);
                    $common = array_intersect($newWords, $existWords);
                    $maxLen = max(count($newWords), count($existWords), 1);
                    if (count($common) / $maxLen > 0.7) {
                        $isDuplicate = true;
                        break;
                    }
                }
            }

            if ($isDuplicate) {
                $skipped++;
                $errors[] = [
                    'index' => $index,
                    'errors' => ['Skipped: A similar question already exists in the database.']
                ];
                continue;
            }

            try {
                GeneratedQuestion::create([
                    'question_id' => 'bulk-' . uniqid() . '-' . time(),
                    'type' => $q['type'],
                    'difficulty' => $q['difficulty'],
                    'theme' => $q['theme'] ?? null,
                    'text' => $q['text'],
                    'options' => $q['options'],
                    'correct_answer_index' => $q['correct_answer_index'],
                    'explanation' => $q['explanation'],
                    'reference' => $q['reference'] ?? null,
                    'source_info' => $q['source_info'] ?? 'Bulk API Upload',
                    'times_answered' => 0,
                    'times_correct' => 0,
                ]);
                $successful++;
            } catch (\Exception $e) {
                Log::error("Bulk Upload Error at index $index", ['error' => $e->getMessage()]);
                $errors[] = [
                    'index' => $index,
                    'errors' => ['Database error: ' . $e->getMessage()]
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Bulk import complete. $successful questions inserted. $skipped skipped.",
            'successful_count' => $successful,
            'skipped_count' => $skipped,
            'failed_count' => count($errors) - $skipped,
            'errors' => $errors
        ]);
    }
}

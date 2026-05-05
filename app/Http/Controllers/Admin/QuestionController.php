<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneratedQuestion;
use App\Models\Feedback;
use App\Models\Theme;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $query = GeneratedQuestion::latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }
        if ($request->filled('theme_id')) {
            $query->where('theme_id', $request->theme_id);
        }
        if ($request->filled('search')) {
            $query->where('text', 'LIKE', '%' . $request->search . '%');
        }

        $questions = $query->with('themeRecord')->paginate(20)->withQueryString();
        $themes = Theme::orderBy('name')->get();

        return view('admin.questions.index', compact('questions', 'themes'));
    }

    public function create()
    {
        $themes = Theme::orderBy('name')->get();
        return view('admin.questions.create', compact('themes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:PARA,SEERAH',
            'difficulty' => 'required|in:Easy,Medium,Hard',
            'theme_id' => 'nullable|exists:themes,id',
            'text' => 'required|string',
            'option_0' => 'required|string',
            'option_1' => 'required|string',
            'option_2' => 'required|string',
            'option_3' => 'required|string',
            'correct_answer_index' => 'required|integer|between:0,3',
            'explanation' => 'required|string',
            'reference' => 'nullable|string',
            'source_info' => 'nullable|string',
        ]);

        $theme = $validated['theme_id'] ? Theme::find($validated['theme_id']) : null;

        $options = [
            $validated['option_0'],
            $validated['option_1'],
            $validated['option_2'],
            $validated['option_3'],
        ];

        GeneratedQuestion::create([
            'question_id' => 'manual-' . time(),
            'type' => $validated['type'],
            'difficulty' => $validated['difficulty'],
            'theme' => $theme?->name,
            'theme_id' => $validated['theme_id'],
            'text' => $validated['text'],
            'options' => $options,
            'correct_answer_index' => $validated['correct_answer_index'],
            'explanation' => $validated['explanation'],
            'reference' => $validated['reference'],
            'source_info' => $validated['source_info'] ?? 'Manual Entry',
            'times_answered' => 0,
            'times_correct' => 0,
        ]);

        return redirect()->route('admin.questions.index')->with('success', 'Question added successfully!');
    }

    public function edit(GeneratedQuestion $question)
    {
        $themes = Theme::orderBy('name')->get();
        return view('admin.questions.edit', compact('question', 'themes'));
    }

    public function update(Request $request, GeneratedQuestion $question)
    {
        $validated = $request->validate([
            'type' => 'required|in:PARA,SEERAH',
            'difficulty' => 'required|in:Easy,Medium,Hard',
            'theme_id' => 'nullable|exists:themes,id',
            'text' => 'required|string',
            'option_0' => 'required|string',
            'option_1' => 'required|string',
            'option_2' => 'required|string',
            'option_3' => 'required|string',
            'correct_answer_index' => 'required|integer|between:0,3',
            'explanation' => 'required|string',
            'reference' => 'nullable|string',
            'source_info' => 'nullable|string',
        ]);

        $theme = $validated['theme_id'] ? Theme::find($validated['theme_id']) : null;

        $question->update([
            'type' => $validated['type'],
            'difficulty' => $validated['difficulty'],
            'theme' => $theme?->name,
            'theme_id' => $validated['theme_id'],
            'text' => $validated['text'],
            'options' => [
                $validated['option_0'],
                $validated['option_1'],
                $validated['option_2'],
                $validated['option_3'],
            ],
            'correct_answer_index' => $validated['correct_answer_index'],
            'explanation' => $validated['explanation'],
            'reference' => $validated['reference'],
            'source_info' => $validated['source_info'],
        ]);

        return redirect()->route('admin.questions.index')->with('success', 'Question updated successfully!');
    }

    public function destroy(GeneratedQuestion $question)
    {
        $question->delete();
        return redirect()->route('admin.questions.index')->with('success', 'Question deleted successfully!');
    }

    // --- Feedback Management ---

    public function feedbackIndex()
    {
        $feedback = Feedback::with(['user', 'question'])->latest()->paginate(20);
        return view('admin.feedback.index', compact('feedback'));
    }

    public function feedbackUpdateStatus(Request $request, Feedback $feedback)
    {
        $request->validate(['status' => 'required|in:pending,reviewed,resolved']);
        $feedback->update(['status' => $request->status]);
        return back()->with('success', 'Feedback status updated.');
    }

    // --- Duplicate Detection ---

    /**
     * Normalize text: lowercase, strip punctuation, remove filler words, sort.
     */
    private function normalizeText(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        $fillers = ['what', 'is', 'the', 'of', 'was', 'a', 'an', 'in', 'to', 'and', 'for', 'that', 'which', 'who', 'whom', 'how', 'did', 'does', 'do', 'are', 'were', 'has', 'have', 'had', 'be', 'been', 'being', 'its', 'it', 'this', 'their', 'from', 'with', 'by', 'on', 'at', 'as', 'or', 'not', 'but', 'about', 'name', 'following', 'among', 'during', 'after', 'before'];
        $words = preg_split('/\s+/', $text);
        $words = array_diff($words, $fillers);
        sort($words);
        return implode(' ', $words);
    }

    public function duplicates()
    {
        $allQuestions = GeneratedQuestion::all();
        $textGroups = [];
        $answerGroups = [];

        foreach ($allQuestions as $q) {
            // Group by normalized text
            $textKey = $this->normalizeText($q->text);
            $textGroups[$textKey][] = $q;

            // Group by options (normalized/sorted)
            $options = $q->options;
            if (is_array($options)) {
                $sortedOptions = $options;
                sort($sortedOptions);
                $answerKey = json_encode($sortedOptions);
                $answerGroups[$answerKey][] = $q;
            }
        }

        // Filter text duplicates
        $duplicateTextGroups = collect($textGroups)
            ->filter(fn($group) => count($group) > 1)
            ->sortByDesc(fn($group) => count($group))
            ->values();

        // Filter answer duplicates (only if many questions share exactly the same answers, which is suspicious)
        $duplicateAnswerGroups = collect($answerGroups)
            ->filter(fn($group) => count($group) > 1)
            ->sortByDesc(fn($group) => count($group))
            ->values();

        // Theme Duplicates
        $allThemes = Theme::all();
        $themeGroups = [];
        foreach ($allThemes as $theme) {
            $norm = $this->normalizeText($theme->name);
            $themeGroups[$norm][] = $theme;
        }

        $duplicateThemeGroups = collect($themeGroups)
            ->filter(fn($group) => count($group) > 1)
            ->sortByDesc(fn($group) => count($group))
            ->values();

        $totalTextDuplicates = $duplicateTextGroups->sum(fn($group) => count($group) - 1);
        $totalThemeDuplicates = $duplicateThemeGroups->sum(fn($group) => count($group) - 1);

        return view('admin.questions.duplicates', compact(
            'duplicateTextGroups', 
            'duplicateAnswerGroups', 
            'duplicateThemeGroups',
            'totalTextDuplicates',
            'totalThemeDuplicates',
            'allThemes'
        ));
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        $count = GeneratedQuestion::whereIn('id', $request->ids)->delete();
        return back()->with('success', "$count duplicate questions deleted.");
    }
}

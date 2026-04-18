<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneratedQuestion;
use App\Models\Feedback;

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
        if ($request->filled('theme')) {
            $query->where('theme', $request->theme);
        }
        if ($request->filled('search')) {
            $query->where('text', 'LIKE', '%' . $request->search . '%');
        }

        $questions = $query->paginate(20)->withQueryString();
        $themes = GeneratedQuestion::whereNotNull('theme')->distinct()->pluck('theme');

        return view('admin.questions.index', compact('questions', 'themes'));
    }

    public function create()
    {
        $themes = GeneratedQuestion::whereNotNull('theme')->distinct()->pluck('theme');
        return view('admin.questions.create', compact('themes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:PARA,SEERAH',
            'difficulty' => 'required|in:Easy,Medium,Hard',
            'theme' => 'nullable|string',
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
            'theme' => $validated['theme'],
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
        $themes = GeneratedQuestion::whereNotNull('theme')->distinct()->pluck('theme');
        return view('admin.questions.edit', compact('question', 'themes'));
    }

    public function update(Request $request, GeneratedQuestion $question)
    {
        $validated = $request->validate([
            'type' => 'required|in:PARA,SEERAH',
            'difficulty' => 'required|in:Easy,Medium,Hard',
            'theme' => 'nullable|string',
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

        $question->update([
            'type' => $validated['type'],
            'difficulty' => $validated['difficulty'],
            'theme' => $validated['theme'],
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
        $groups = [];

        foreach ($allQuestions as $q) {
            $key = $this->normalizeText($q->text);
            $groups[$key][] = $q;
        }

        // Only keep groups with more than 1 question (actual duplicates)
        $duplicateGroups = collect($groups)
            ->filter(fn($group) => count($group) > 1)
            ->sortByDesc(fn($group) => count($group))
            ->values();

        $totalDuplicates = $duplicateGroups->sum(fn($group) => count($group) - 1);

        return view('admin.questions.duplicates', compact('duplicateGroups', 'totalDuplicates'));
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        $count = GeneratedQuestion::whereIn('id', $request->ids)->delete();
        return back()->with('success', "$count duplicate questions deleted.");
    }
}

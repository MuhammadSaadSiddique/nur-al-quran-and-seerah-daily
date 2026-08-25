<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Testimonial;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        return view('profile', compact('user'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'display_name' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6|max:100',
        ]);

        $user = Auth::user();
        if ($request->filled('display_name')) {
            $user->display_name = $request->display_name;
        }
        if ($request->filled('password')) {
            $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        }
        $user->save();

        return back()->with('success', 'Profile updated successfully!');
    }

    public function stats()
    {
        $user = Auth::user();
        $quizzes = $user->quizzes()->get();
        return view('stats', compact('user', 'quizzes'));
    }

    public function bookmarks(\App\Services\QuranApiService $quranApiService)
    {
        $user = Auth::user();
        $bookmarks = $user->bookmarked_questions ?? [];
        
        $quranBookmarks = [];
        if ($user->quran_access_token) {
            $quranBookmarks = $quranApiService->getQuranBookmarks($user) ?? [];
        }

        return view('bookmarks', compact('user', 'bookmarks', 'quranBookmarks'));
    }

    public function toggleBookmark(Request $request)
    {
        $request->validate([
            'question' => 'required',
            'sourceInfo' => 'required|string',
        ]);

        $user = Auth::user();
        $bookmarks = $user->bookmarked_questions ?? [];
        $question = is_string($request->question) ? json_decode($request->question, true) : $request->question;
        $questionId = $question['id'] ?? '';

        $existingIndex = null;
        foreach ($bookmarks as $i => $b) {
            if (($b['id'] ?? '') === $questionId) {
                $existingIndex = $i;
                break;
            }
        }

        if ($existingIndex !== null) {
            array_splice($bookmarks, $existingIndex, 1);
        } else {
            $bookmarks[] = [
                'id' => $questionId,
                'question' => $question,
                'sourceInfo' => $request->sourceInfo,
                'timestamp' => now()->timestamp * 1000,
            ];
        }

        $user->bookmarked_questions = array_values($bookmarks);
        $user->save();

        return response()->json(['success' => true, 'bookmarked' => $existingIndex === null]);
    }

    public function submitTestimonial(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'feedback' => 'required|string|max:1000',
        ]);

        Testimonial::create([
            'name' => $request->name,
            'feedback' => $request->feedback,
            'is_active' => false,
        ]);

        return back()->with('success', 'Thank you for your feedback! It will be reviewed by our team.');
    }
}

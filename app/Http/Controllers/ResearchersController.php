<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResearchersController extends Controller
{
    /**
     * Display the researchers directory.
     */
    public function index()
    {
        $researchers = User::where('is_researcher', true)
            ->orWhere('is_admin', true)
            ->orderBy('id')
            ->get();

        $scienceCategories = \App\Models\ScienceCategory::all();

        return view('researchers.index', compact('researchers', 'scienceCategories'));
    }

    /**
     * Request to join the research team (does not self-grant privileges).
     */
    public function join(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->is_researcher || $user->is_admin) {
            return redirect()->route('researchers.index')
                ->with('success', 'You are already a member of the research team.');
        }

        // Privilege elevation is admin-only via Admin\UserController::toggleResearcher.
        return redirect()->route('researchers.index')
            ->with('success', 'Thank you for your interest. An administrator must grant researcher access before you can approve analyses and tags.');
    }

    /**
     * Update expert category for a researcher.
     */
    public function updateExpert(Request $request, $id)
    {
        if (!Auth::check() || !Auth::user()->is_admin) {
            abort(403);
        }

        $user = User::findOrFail((int) $id);
        $user->expert_category_id = $request->input('expert_category_id') ?: null;
        $user->save();

        return back()->with('success', 'Expert category updated successfully.');
    }
}

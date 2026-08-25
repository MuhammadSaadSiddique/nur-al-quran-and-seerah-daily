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
     * Join the research team.
     */
    public function join(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $user->is_researcher = true;
        $user->save();

        return redirect()->route('researchers.index')
            ->with('success', 'Welcome to the Research Team! You now have researcher permissions to approve analyses and tags.');
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

<?php

namespace App\Http\Controllers;

use App\Models\ResearcherOnboarding;
use App\Models\ScienceCategory;
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
        $researchers = User::with(['expertCategory', 'researcherOnboarding'])
            ->where('is_researcher', true)
            ->orWhere('is_admin', true)
            ->orderBy('id')
            ->get();

        $scienceCategories = ScienceCategory::orderBy('name')->get();

        $applications = collect();
        if (Auth::check() && Auth::user()->is_admin) {
            $applications = ResearcherOnboarding::with(['user', 'requestedExpertCategory'])
                ->whereHas('user', function ($query) {
                    $query->where('is_researcher', false)->where('is_admin', false);
                })
                ->latest('pledged_at')
                ->get();
        }

        $onboarding = Auth::check() ? Auth::user()->researcherOnboarding : null;

        return view('researchers.index', compact('researchers', 'scienceCategories', 'applications', 'onboarding'));
    }

    /**
     * Researcher onboarding: details plus the authenticity pledge.
     * Completing this form does not grant researcher privileges.
     */
    public function onboarding()
    {
        $user = Auth::user();
        $onboarding = $user->researcherOnboarding;
        $scienceCategories = ScienceCategory::orderBy('name')->get();

        return view('researchers.onboarding', compact('user', 'onboarding', 'scienceCategories'));
    }

    /**
     * Store researcher details and the pledge not to approve unresearched or unauthentic content.
     */
    public function storeOnboarding(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'credentials' => ['required', 'string', 'max:2000'],
            'expertise' => ['required', 'string', 'max:255'],
            'background' => ['required', 'string', 'max:3000'],
            'languages' => ['nullable', 'string', 'max:255'],
            'sources' => ['required', 'string', 'max:2000'],
            'requested_expert_category_id' => ['nullable', 'integer', 'exists:science_categories,id'],
            'authenticity_pledge' => ['accepted'],
        ], [
            'authenticity_pledge.accepted' => 'You must confirm that you will not approve any unresearched or unauthentic content.',
        ]);

        ResearcherOnboarding::updateOrCreate(
            ['user_id' => $user->id],
            [
                'full_name' => $data['full_name'],
                'credentials' => $data['credentials'],
                'expertise' => $data['expertise'],
                'background' => $data['background'],
                'languages' => $data['languages'] ?? null,
                'sources' => $data['sources'],
                'requested_expert_category_id' => $data['requested_expert_category_id'] ?? null,
                'pledge_statement' => ResearcherOnboarding::PLEDGE,
                'pledged_at' => now(),
            ]
        );

        $message = ($user->is_researcher || $user->is_admin)
            ? 'Your researcher details and authenticity pledge are recorded. Approve content only after you have researched it.'
            : 'Your details and authenticity pledge are recorded. An administrator must still grant researcher access before you can approve content.';

        return redirect()->route('researchers.index')->with('success', $message);
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
        return redirect()->route('researchers.onboarding')
            ->with('success', 'Complete your details and confirm the authenticity pledge. An administrator must still grant researcher access before you can approve content.');
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

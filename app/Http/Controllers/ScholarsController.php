<?php

namespace App\Http\Controllers;

use App\Models\Scholar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScholarsController extends Controller
{
    /**
     * Public directory of published scholars.
     */
    public function index()
    {
        $scholars = Scholar::query()
            ->where('status', Scholar::STATUS_PUBLISHED)
            ->orderBy('full_name')
            ->get();

        $mine = Auth::check() && !Auth::user()->is_admin
            ? Scholar::query()->where('user_id', Auth::id())->first()
            : null;

        $pendingCount = Auth::check() && Auth::user()->is_admin
            ? Scholar::query()->where('status', Scholar::STATUS_PENDING)->count()
            : 0;

        return view('scholars.index', compact('scholars', 'mine', 'pendingCount'));
    }

    /**
     * A single scholar profile. Pending profiles stay private except to the applicant and admins.
     */
    public function show(Scholar $scholar)
    {
        $viewer = Auth::user();
        $canPreview = $viewer && ($viewer->is_admin || (int) $viewer->id === (int) $scholar->user_id);

        if (!$scholar->isPublished() && !$canPreview) {
            abort(404);
        }

        return view('scholars.show', compact('scholar'));
    }

    /**
     * Onboarding form. Admins use it to add another scholar each time they submit.
     * Other signed-in people use it to submit or update their own profile.
     */
    public function onboarding()
    {
        $user = Auth::user();
        $scholar = $user->is_admin ? null : Scholar::query()->where('user_id', $user->id)->first();
        $recentScholars = $user->is_admin
            ? Scholar::query()->latest()->limit(8)->get()
            : collect();

        return view('scholars.onboarding', [
            'scholar' => $scholar,
            'mode' => $user->is_admin ? 'add' : 'apply',
            'recentScholars' => $recentScholars,
            'formAction' => route('scholars.onboarding.store'),
            'formMethod' => 'POST',
        ]);
    }

    public function storeOnboarding(Request $request)
    {
        $user = Auth::user();
        $data = Scholar::validateSubmission($request);

        if ($user->is_admin) {
            $scholar = new Scholar(Scholar::attributesFrom($data));
            $scholar->slug = Scholar::makeSlug($data['full_name']);
            $scholar->added_by = $user->id;
            $scholar->status = Scholar::STATUS_PUBLISHED;
            $scholar->save();

            return redirect()
                ->route('scholars.onboarding')
                ->with('success', $scholar->displayName().' is now in the scholars directory. Add another scholar below.');
        }

        $scholar = Scholar::query()->firstOrNew(['user_id' => $user->id]);
        $isNew = !$scholar->exists;
        $previousName = $scholar->full_name;

        $scholar->fill(Scholar::attributesFrom($data));
        $scholar->user_id = $user->id;
        $scholar->added_by = $scholar->added_by ?: $user->id;
        if ($isNew) {
            $scholar->status = Scholar::STATUS_PENDING;
        }
        if ($isNew || $previousName !== $data['full_name']) {
            $scholar->slug = Scholar::makeSlug($data['full_name'], $scholar->id);
        }
        $scholar->save();

        $message = $isNew
            ? 'Your scholar profile is recorded. An administrator will review it before it appears in the directory.'
            : 'Your scholar profile was updated.';

        return redirect()->route('scholars.index')->with('success', $message);
    }
}

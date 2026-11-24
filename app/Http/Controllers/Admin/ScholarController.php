<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Scholar;
use Illuminate\Http\Request;

class ScholarController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), [Scholar::STATUS_PUBLISHED, Scholar::STATUS_PENDING], true)
            ? $request->query('status')
            : 'all';

        $scholars = Scholar::query()
            ->with('user:id,name,email')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.addcslashes($request->string('q')->trim()->toString(), '%_\\').'%';
                $query->where(fn ($query) => $query
                    ->where('full_name', 'like', $term)
                    ->orWhere('title', 'like', $term)
                    ->orWhere('expertise', 'like', $term)
                    ->orWhere('location', 'like', $term));
            })
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.scholars.index', [
            'scholars' => $scholars,
            'status' => $status,
            'stats' => [
                'published' => Scholar::query()->where('status', Scholar::STATUS_PUBLISHED)->count(),
                'pending' => Scholar::query()->where('status', Scholar::STATUS_PENDING)->count(),
            ],
        ]);
    }

    public function edit(Scholar $scholar)
    {
        return view('scholars.onboarding', [
            'scholar' => $scholar,
            'mode' => 'edit',
            'recentScholars' => collect(),
            'formAction' => route('admin.scholars.update', $scholar),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(Request $request, Scholar $scholar)
    {
        $data = Scholar::validateSubmission($request);
        $previousName = $scholar->full_name;

        $scholar->fill(Scholar::attributesFrom($data));
        if ($previousName !== $data['full_name']) {
            $scholar->slug = Scholar::makeSlug($data['full_name'], $scholar->id);
        }
        $scholar->save();

        return redirect()
            ->route('admin.scholars.index')
            ->with('success', $scholar->displayName().' was updated.');
    }

    public function publish(Scholar $scholar)
    {
        $scholar->status = $scholar->isPublished()
            ? Scholar::STATUS_PENDING
            : Scholar::STATUS_PUBLISHED;
        $scholar->save();

        $message = $scholar->isPublished()
            ? $scholar->displayName().' is now visible in the scholars directory.'
            : $scholar->displayName().' was hidden from the public directory.';

        return back()->with('success', $message);
    }

    public function destroy(Scholar $scholar)
    {
        $name = $scholar->displayName();
        $scholar->delete();

        return back()->with('success', $name.' was removed.');
    }
}

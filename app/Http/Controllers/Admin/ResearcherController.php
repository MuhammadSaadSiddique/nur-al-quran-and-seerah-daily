<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScienceCategory;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Services\RequirementSurvey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin → Researchers: everyone on the research team plus everyone who applied
 * (completed researcher onboarding), with their details and research activity.
 */
class ResearcherController extends Controller
{
    /** Tables holding Quranic Lens submissions; each has user_id and moderated_by. */
    private const LENS_TABLES = ['quranic_lens_analyses', 'quranic_lens_word_tags', 'quranic_lens_verse_tags'];

    public function index(Request $request)
    {
        $status = in_array($request->query('status'), ['researchers', 'applicants'], true) ? $request->query('status') : 'all';

        $team = fn (Builder $q) => $q->where('is_researcher', true);
        $applied = fn (Builder $q) => $q->where('is_researcher', false)->whereHas('researcherOnboarding');

        $query = User::query()
            ->with(['researcherOnboarding.requestedExpertCategory', 'expertCategory'])
            ->when($status === 'researchers', $team)
            ->when($status === 'applicants', $applied)
            ->when($status === 'all', fn ($q) => $q->where(fn ($q) => $q->where('is_researcher', true)->orWhereHas('researcherOnboarding')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . addcslashes($request->string('q')->trim()->toString(), '%_\\') . '%';
                $q->where(fn ($q) => $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhereHas('researcherOnboarding', fn ($o) => $o->where('full_name', 'like', $term)->orWhere('expertise', 'like', $term)));
            })
            // Applicants waiting for a decision first, then the team; newest pledge first within each.
            ->orderBy('is_researcher')
            ->orderByDesc(
                DB::table('researcher_onboardings')->select('pledged_at')->whereColumn('researcher_onboardings.user_id', 'users.id')->limit(1)
            )
            ->orderBy('id');

        $researchers = $query->paginate(15)->withQueryString();
        $activity = $this->activityFor($researchers->pluck('id')->all());

        $scholarRole = SurveyResponse::where('survey_key', RequirementSurvey::KEY)->where('answers->role', 'scholar');

        return view('admin.researchers.index', [
            'researchers' => $researchers,
            'activity' => $activity,
            'status' => $status,
            'scienceCategories' => ScienceCategory::orderBy('name')->get(['id', 'name']),
            'stats' => [
                'team' => User::where('is_researcher', true)->count(),
                'pledged' => User::where('is_researcher', true)->whereHas('researcherOnboarding', fn ($o) => $o->whereNotNull('pledged_at'))->count(),
                'applicants' => User::query()->where($applied)->count(),
                'survey_scholars' => (clone $scholarRole)->count(),
            ],
            // Survey participants who said they are researchers or scholars: possible recruits.
            'surveyScholars' => $scholarRole->with('user:id,name,email,is_researcher')->latest()->limit(8)->get(),
        ]);
    }

    /**
     * Research activity per user: contributions and reviews on Quran Research, and
     * Quranic Lens items submitted and moderated.
     *
     * @param  array<int>  $userIds
     * @return array<int, array<string, int>>
     */
    private function activityFor(array $userIds): array
    {
        $blank = ['contributions' => 0, 'published' => 0, 'reviews' => 0, 'lens_submitted' => 0, 'lens_moderated' => 0];
        $activity = array_fill_keys($userIds, $blank);
        if ($userIds === []) {
            return $activity;
        }

        $add = function (string $metric, $rows) use (&$activity) {
            foreach ($rows as $userId => $count) {
                if (isset($activity[$userId])) {
                    $activity[$userId][$metric] += (int) $count;
                }
            }
        };
        $countBy = fn (string $table, string $column, ?callable $scope = null) => DB::table($table)
            ->whereIn($column, $userIds)
            ->when($scope, $scope)
            ->groupBy($column)
            ->pluck(DB::raw('count(*)'), $column);

        if (Schema::hasTable('quran_research_contributions')) {
            $add('contributions', $countBy('quran_research_contributions', 'user_id', fn ($q) => $q->whereNull('deleted_at')));
            $add('published', $countBy('quran_research_contributions', 'user_id', fn ($q) => $q->whereNull('deleted_at')->where('status', 'published')));
        }
        if (Schema::hasTable('quran_research_reviews')) {
            $add('reviews', $countBy('quran_research_reviews', 'user_id'));
        }
        foreach (self::LENS_TABLES as $table) {
            if (Schema::hasTable($table)) {
                $add('lens_submitted', $countBy($table, 'user_id'));
                $add('lens_moderated', $countBy($table, 'moderated_by'));
            }
        }

        return $activity;
    }
}

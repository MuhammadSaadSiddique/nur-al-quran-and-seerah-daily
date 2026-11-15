<?php

namespace App\Http\Controllers;

use App\Models\SurveyResponse;
use App\Services\RequirementSurvey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SurveyController extends Controller
{
    public function show(Request $request)
    {
        $user = Auth::user();

        $previous = $user
            ? SurveyResponse::where('survey_key', RequirementSurvey::KEY)->where('user_id', $user->id)->exists()
            : false;

        return view('survey.show', [
            'config' => RequirementSurvey::clientConfig(),
            'user' => $user ? [
                'name' => $user->display_name ?: $user->name,
                'email' => $user->email,
            ] : null,
            'alreadyAnswered' => $previous,
            // ?from=android (etc.) tags where people opened the survey from.
            'source' => preg_match('/^[A-Za-z0-9_-]{1,30}$/', (string) $request->query('from')) ? $request->query('from') : 'web',
        ]);
    }

    /**
     * Accepts the finished survey as JSON. Signed-in people keep one response per
     * survey round (answering again updates it); guests may submit freely (throttled).
     */
    public function store(Request $request)
    {
        $user = Auth::guard('sanctum')->user() ?: Auth::user();
        $response = RequirementSurvey::record($request->all(), $user);

        return response()->json([
            'status' => 'success',
            'message' => 'JazakAllahu Khairan! Your answers will shape what we build next.',
            'data' => ['id' => $response->id],
        ]);
    }

    public function adminIndex(Request $request)
    {
        $base = SurveyResponse::where('survey_key', RequirementSurvey::KEY);

        if ($request->filled('role')) {
            $base->where('answers->role', $request->string('role')->toString());
        }

        $summary = RequirementSurvey::summarize(
            (clone $base)->get(['id', 'answers', 'nps', 'wish', 'notify', 'created_at'])
        );

        $wishes = (clone $base)
            ->with('user:id,name')
            ->whereNotNull('wish')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Everyone who answered, newest first, searchable by name or email.
        $participants = (clone $base)
            ->with('user:id,name,email,is_researcher')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . addcslashes($request->string('q')->trim()->toString(), '%_\\') . '%';
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term)));
            })
            ->latest()
            ->paginate(20, ['*'], 'participants_page')
            ->withQueryString();

        return view('admin.survey.index', [
            'summary' => $summary,
            'wishes' => $wishes,
            'participants' => $participants,
            'questions' => RequirementSurvey::questions(),
            'features' => RequirementSurvey::features(),
            'roles' => RequirementSurvey::questions()['role']['options'],
            'surveyUrl' => route('survey.show'),
        ]);
    }

    public function export(): StreamedResponse
    {
        $questions = RequirementSurvey::questions();
        $features = RequirementSurvey::features();
        $filename = 'survey-' . RequirementSurvey::KEY . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($questions, $features) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(
                ['id', 'submitted_at', 'source', 'signed_in'],
                array_keys($questions),
                array_map(fn ($key) => 'feature_' . $key, array_keys($features)),
                ['nps', 'wish', 'name', 'email', 'notify', 'duration_seconds']
            ));

            SurveyResponse::where('survey_key', RequirementSurvey::KEY)
                ->orderBy('id')
                ->chunk(500, function ($responses) use ($out, $questions, $features) {
                    foreach ($responses as $response) {
                        $row = [
                            $response->id,
                            $response->created_at?->toDateTimeString(),
                            $response->source,
                            $response->user_id ? 'yes' : 'no',
                        ];
                        foreach (array_keys($questions) as $key) {
                            $row[] = implode('|', (array) ($response->answers[$key] ?? []));
                        }
                        foreach (array_keys($features) as $key) {
                            $row[] = $response->answers['features'][$key] ?? '';
                        }
                        array_push($row, $response->nps, $response->wish, $response->name, $response->email, $response->notify ? 'yes' : 'no', $response->duration_seconds);
                        fputcsv($out, array_map([self::class, 'csvSafe'], $row));
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Stop spreadsheet apps from treating user text as a formula. */
    private static function csvSafe($value)
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }
}

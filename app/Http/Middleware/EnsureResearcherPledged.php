<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureResearcherPledged
{
    /**
     * Researchers must finish onboarding and accept the authenticity pledge
     * before they can approve, reject, or publish lens content.
     * Administrators are not blocked here.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_researcher && !$user->is_admin && !$user->hasAcceptedResearcherPledge()) {
            $message = 'Complete researcher onboarding and confirm that you will not approve any unresearched or unauthentic content before using approvals.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return redirect()
                ->route('researchers.onboarding')
                ->with('error', $message);
        }

        return $next($request);
    }
}

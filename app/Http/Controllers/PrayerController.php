<?php

namespace App\Http\Controllers;

use App\Models\UserDailyEngagement;
use App\Services\DailyEngagementService;
use App\Services\PrayerTimes;
use Illuminate\Http\Request;

class PrayerController extends Controller
{
    public function index(Request $request, PrayerTimes $times, DailyEngagementService $engagement)
    {
        $points = 0;
        if ($user = $request->user()) {
            $before = (int) $user->total_score;
            $engagement->record($user, UserDailyEngagement::ACTIVITY_PRAYER);
            $user->refresh();
            $points = max(0, (int) $user->total_score - $before);
        }

        $lat = $request->query('latitude');
        $lng = $request->query('longitude');
        $tz = $request->query('tz');
        $schedule = null;
        $localDate = null;
        $isFriday = false;

        if (is_numeric($lat) && is_numeric($lng)) {
            $offset = is_numeric($tz) ? (float) $tz : (now()->utcOffset() / 60);
            $local = now('UTC')->addMinutes((int) round($offset * 60));
            $localDate = $local->toDateString();
            $isFriday = $local->dayOfWeek === 5;
            $schedule = $times->day(
                (int) $local->year,
                (int) $local->month,
                (int) $local->day,
                (float) $lat,
                (float) $lng,
                $offset
            );
        }

        return view('prayer', [
            'schedule' => $schedule,
            'points' => $points,
            'localDate' => $localDate,
            'isFriday' => $isFriday,
            'latitude' => is_numeric($lat) ? (float) $lat : null,
            'longitude' => is_numeric($lng) ? (float) $lng : null,
        ]);
    }
}

<?php

namespace App\Services;

/**
 * Adhan times from the same sun-position formula the mobile salah screen uses.
 * Fajr and Isha follow the University of Islamic Sciences, Karachi angles,
 * and Asr uses the Hanafi shadow length.
 */
class PrayerTimes
{
    private const SUNRISE_SUNSET_ANGLE = 0.833;
    private const FAJR_ANGLE = 18.0;
    private const ISHA_ANGLE = 18.0;
    private const ASR_FACTOR = 2;

    /**
     * @return array<string, string> prayer key => HH:MM
     */
    public function day(int $year, int $month, int $day, float $latitude, float $longitude, float $timezoneHours): array
    {
        $latitude = max(-90, min(90, $latitude));
        $longitude = max(-180, min(180, $longitude));

        $jDate = $this->julianDate($year, $month, $day) - $longitude / (15.0 * 24.0);
        $solar = new PrayerSolar($jDate, $latitude);

        $fajr = $solar->sunAngleTime(self::FAJR_ANGLE, 5.0 / 24, true);
        $sunrise = $solar->sunAngleTime(self::SUNRISE_SUNSET_ANGLE, 6.0 / 24, true);
        $dhuhr = $solar->midDay(12.0 / 24);
        $asr = $solar->asrTime(self::ASR_FACTOR, 13.0 / 24);
        $sunset = $solar->sunAngleTime(self::SUNRISE_SUNSET_ANGLE, 18.0 / 24, false);
        $ishaByAngle = $solar->sunAngleTime(self::ISHA_ANGLE, 18.0 / 24, false);

        $shift = $timezoneHours - $longitude / 15.0;
        $fajrLocal = $fajr + $shift;
        $sunriseLocal = $sunrise + $shift;
        $dhuhrLocal = $dhuhr + $shift;
        $asrLocal = $asr + $shift;
        $sunsetLocal = $sunset + $shift;
        $maghribLocal = $sunsetLocal;

        $night = $this->timeDiff($sunsetLocal, $sunriseLocal);
        $fajrLocal = $this->adjustHighLatitude($fajrLocal, $sunriseLocal, self::FAJR_ANGLE, $night, true);
        $ishaLocal = $this->adjustHighLatitude($ishaByAngle + $shift, $sunsetLocal, self::ISHA_ANGLE, $night, false);

        return [
            'fajr' => $this->format($fajrLocal),
            'sunrise' => $this->format($sunriseLocal),
            'dhuhr' => $this->format($dhuhrLocal),
            'asr' => $this->format($asrLocal),
            'maghrib' => $this->format($maghribLocal),
            'isha' => $this->format($ishaLocal),
        ];
    }

    private function adjustHighLatitude(float $time, float $base, float $angle, float $night, bool $counterClockwise): float
    {
        $portion = $angle / 60.0 * $night;
        $diff = $counterClockwise ? $this->timeDiff($time, $base) : $this->timeDiff($base, $time);
        if (is_nan($time) || $diff > $portion) {
            return $base + ($counterClockwise ? -$portion : $portion);
        }

        return $time;
    }

    private function julianDate(int $year, int $month, int $day): float
    {
        $y = $year;
        $m = $month;
        if ($m <= 2) {
            $y -= 1;
            $m += 12;
        }
        $a = floor($y / 100.0);
        $b = 2 - $a + floor($a / 4.0);

        return floor(365.25 * ($y + 4716)) + floor(30.6001 * ($m + 1)) + $day + $b - 1524.5;
    }

    private function format(float $hours): string
    {
        if (is_nan($hours)) {
            return '—';
        }
        $minutes = (int) floor($this->fixHour($hours + 0.5 / 60.0) * 60);
        $minutes = max(0, min(24 * 60 - 1, $minutes));

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function timeDiff(float $from, float $to): float
    {
        return $this->fixHour($to - $from);
    }

    private function fixHour(float $hours): float
    {
        $wrapped = $hours - 24.0 * floor($hours / 24.0);

        return $wrapped < 0 ? $wrapped + 24.0 : $wrapped;
    }
}

/**
 * Sun position for one day. Kept beside the calculator so the formula stays together.
 */
class PrayerSolar
{
    public function __construct(private float $jDate, private float $latitude)
    {
    }

    public function midDay(float $time): float
    {
        $eqt = $this->sunPosition($this->jDate + $time)['equation'];

        return $this->fixHour(12 - $eqt);
    }

    public function sunAngleTime(float $angle, float $time, bool $counterClockwise): float
    {
        $decl = $this->sunPosition($this->jDate + $time)['declination'];
        $noon = $this->midDay($time);
        $cosT = (-$this->dSin($angle) - $this->dSin($decl) * $this->dSin($this->latitude)) / ($this->dCos($decl) * $this->dCos($this->latitude));
        if ($cosT < -1.0 || $cosT > 1.0) {
            return NAN;
        }
        $t = $this->dAcos($cosT) / 15.0;

        return $noon + ($counterClockwise ? -$t : $t);
    }

    public function asrTime(int $factor, float $time): float
    {
        $decl = $this->sunPosition($this->jDate + $time)['declination'];
        $angle = -$this->dAcot($factor + $this->dTan(abs($this->latitude - $decl)));

        return $this->sunAngleTime($angle, $time, false);
    }

    /**
     * @return array{declination: float, equation: float}
     */
    private function sunPosition(float $jd): array
    {
        $d = $jd - 2451545.0;
        $g = $this->fixAngle(357.529 + 0.98560028 * $d);
        $q = $this->fixAngle(280.459 + 0.98564736 * $d);
        $l = $this->fixAngle($q + 1.915 * $this->dSin($g) + 0.020 * $this->dSin(2 * $g));
        $e = 23.439 - 0.00000036 * $d;
        $ra = $this->dAtan2($this->dCos($e) * $this->dSin($l), $this->dCos($l)) / 15.0;

        return [
            'equation' => $q / 15.0 - $this->fixHour($ra),
            'declination' => $this->dAsin($this->dSin($e) * $this->dSin($l)),
        ];
    }

    private function fixAngle(float $angle): float
    {
        $wrapped = $angle - 360.0 * floor($angle / 360.0);

        return $wrapped < 0 ? $wrapped + 360.0 : $wrapped;
    }

    private function fixHour(float $hours): float
    {
        $wrapped = $hours - 24.0 * floor($hours / 24.0);

        return $wrapped < 0 ? $wrapped + 24.0 : $wrapped;
    }

    private function dSin(float $degrees): float
    {
        return sin(deg2rad($degrees));
    }

    private function dCos(float $degrees): float
    {
        return cos(deg2rad($degrees));
    }

    private function dTan(float $degrees): float
    {
        return tan(deg2rad($degrees));
    }

    private function dAsin(float $value): float
    {
        return rad2deg(asin($value));
    }

    private function dAcos(float $value): float
    {
        return rad2deg(acos($value));
    }

    private function dAtan2(float $y, float $x): float
    {
        return rad2deg(atan2($y, $x));
    }

    private function dAcot(float $value): float
    {
        return rad2deg(atan(1.0 / $value));
    }
}

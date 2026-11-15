package com.asloobulhayat.eternalecho.data

import java.util.Calendar
import java.util.TimeZone
import kotlin.math.abs
import kotlin.math.acos
import kotlin.math.asin
import kotlin.math.atan
import kotlin.math.atan2
import kotlin.math.cos
import kotlin.math.floor
import kotlin.math.sin
import kotlin.math.tan

/**
 * Astronomical adhan times computed on the device, so the screen works offline
 * and in places where no mosque has been added yet.
 *
 * Follows the widely used PrayTimes.org formulation (sun position from the
 * Astronomical Almanac approximation). Fajr and Isha at high latitudes, where
 * twilight never fully ends in summer, use the angle-based night portion rule.
 */
object PrayerTimesCalculator {
    private const val SUNRISE_SUNSET_ANGLE = 0.833

    fun calculate(
        calendar: Calendar,
        latitude: Double,
        longitude: Double,
        method: CalculationMethod,
        asrMethod: AsrMethod,
        timeZone: TimeZone = TimeZone.getDefault()
    ): PrayerTimesDay {
        val year = calendar.get(Calendar.YEAR)
        val month = calendar.get(Calendar.MONTH) + 1
        val day = calendar.get(Calendar.DAY_OF_MONTH)

        // Offset at local noon so the day's DST state is used, not midnight's.
        val noon = Calendar.getInstance(timeZone).apply {
            clear()
            set(year, month - 1, day, 12, 0, 0)
        }
        val offsetHours = timeZone.getOffset(noon.timeInMillis) / 3_600_000.0

        return calculate(year, month, day, latitude, longitude, offsetHours, method, asrMethod)
    }

    fun calculate(
        year: Int,
        month: Int,
        day: Int,
        latitude: Double,
        longitude: Double,
        timezoneHours: Double,
        method: CalculationMethod,
        asrMethod: AsrMethod
    ): PrayerTimesDay {
        val jDate = julianDate(year, month, day) - longitude / (15.0 * 24.0)
        val solar = Solar(jDate, latitude)

        // One refinement pass from rough guesses, as in the reference algorithm.
        val fajr = solar.sunAngleTime(method.fajrAngle, 5.0 / 24, counterClockwise = true)
        val sunrise = solar.sunAngleTime(SUNRISE_SUNSET_ANGLE, 6.0 / 24, counterClockwise = true)
        val dhuhr = solar.midDay(12.0 / 24)
        val asr = solar.asrTime(asrMethod.shadowFactor, 13.0 / 24)
        val sunset = solar.sunAngleTime(SUNRISE_SUNSET_ANGLE, 18.0 / 24, counterClockwise = false)
        val ishaByAngle = method.ishaAngle?.let { solar.sunAngleTime(it, 18.0 / 24, counterClockwise = false) }

        val shift = timezoneHours - longitude / 15.0
        var fajrLocal = fajr + shift
        val sunriseLocal = sunrise + shift
        val dhuhrLocal = dhuhr + shift
        val asrLocal = asr + shift
        val sunsetLocal = sunset + shift
        val maghribLocal = sunsetLocal

        val night = timeDiff(sunsetLocal, sunriseLocal)
        fajrLocal = adjustHighLatitude(fajrLocal, sunriseLocal, method.fajrAngle, night, counterClockwise = true)

        val ishaLocal = if (method.ishaMinutesAfterMaghrib != null) {
            maghribLocal + method.ishaMinutesAfterMaghrib / 60.0
        } else {
            adjustHighLatitude(ishaByAngle!! + shift, sunsetLocal, method.ishaAngle!!, night, counterClockwise = false)
        }

        return PrayerTimesDay(
            fajr = toMinutes(fajrLocal),
            sunrise = toMinutes(sunriseLocal),
            dhuhr = toMinutes(dhuhrLocal),
            asr = toMinutes(asrLocal),
            maghrib = toMinutes(maghribLocal),
            isha = toMinutes(ishaLocal)
        )
    }

    /**
     * Country-appropriate defaults for a first run, before the worshipper picks
     * a method: what most local mosques use.
     */
    fun defaultsForCountry(countryCode: String): Pair<CalculationMethod, AsrMethod> =
        when (countryCode.uppercase()) {
            "PK", "IN", "BD", "AF" -> CalculationMethod.KARACHI to AsrMethod.HANAFI
            "US", "CA" -> CalculationMethod.ISNA to AsrMethod.STANDARD
            "SA", "YE" -> CalculationMethod.MAKKAH to AsrMethod.STANDARD
            "EG", "SD", "LY", "SY", "LB", "IQ", "JO", "PS" -> CalculationMethod.EGYPT to AsrMethod.STANDARD
            "TR" -> CalculationMethod.MWL to AsrMethod.HANAFI
            else -> CalculationMethod.MWL to AsrMethod.STANDARD
        }

    private class Solar(private val jDate: Double, private val latitude: Double) {
        fun midDay(time: Double): Double {
            val eqt = sunPosition(jDate + time).equation
            return fixHour(12 - eqt)
        }

        fun sunAngleTime(angle: Double, time: Double, counterClockwise: Boolean): Double {
            val decl = sunPosition(jDate + time).declination
            val noon = midDay(time)
            val cosT = (-dSin(angle) - dSin(decl) * dSin(latitude)) / (dCos(decl) * dCos(latitude))
            // Outside [-1, 1] the sun never reaches this angle today (polar summer/winter).
            if (cosT < -1.0 || cosT > 1.0) return Double.NaN
            val t = dAcos(cosT) / 15.0
            return noon + if (counterClockwise) -t else t
        }

        fun asrTime(factor: Int, time: Double): Double {
            val decl = sunPosition(jDate + time).declination
            val angle = -dAcot(factor + dTan(abs(latitude - decl)))
            return sunAngleTime(angle, time, counterClockwise = false)
        }
    }

    private data class SunPosition(val declination: Double, val equation: Double)

    private fun sunPosition(jd: Double): SunPosition {
        val d = jd - 2451545.0
        val g = fixAngle(357.529 + 0.98560028 * d)
        val q = fixAngle(280.459 + 0.98564736 * d)
        val l = fixAngle(q + 1.915 * dSin(g) + 0.020 * dSin(2 * g))
        val e = 23.439 - 0.00000036 * d
        val ra = dAtan2(dCos(e) * dSin(l), dCos(l)) / 15.0
        val eqt = q / 15.0 - fixHour(ra)
        val decl = dAsin(dSin(e) * dSin(l))
        return SunPosition(decl, eqt)
    }

    private fun adjustHighLatitude(time: Double, base: Double, angle: Double, night: Double, counterClockwise: Boolean): Double {
        val portion = angle / 60.0 * night
        val diff = if (counterClockwise) timeDiff(time, base) else timeDiff(base, time)
        return if (time.isNaN() || diff > portion) {
            base + if (counterClockwise) -portion else portion
        } else {
            time
        }
    }

    private fun julianDate(year: Int, month: Int, day: Int): Double {
        var y = year
        var m = month
        if (m <= 2) {
            y -= 1
            m += 12
        }
        val a = floor(y / 100.0)
        val b = 2 - a + floor(a / 4.0)
        return floor(365.25 * (y + 4716)) + floor(30.6001 * (m + 1)) + day + b - 1524.5
    }

    private fun toMinutes(hours: Double): Int {
        if (hours.isNaN()) return -1
        val rounded = fixHour(hours + 0.5 / 60.0)
        return floor(rounded * 60).toInt().coerceIn(0, 24 * 60 - 1)
    }

    private fun timeDiff(from: Double, to: Double): Double = fixHour(to - from)
    private fun fixAngle(a: Double): Double = fix(a, 360.0)
    private fun fixHour(h: Double): Double = fix(h, 24.0)
    private fun fix(a: Double, b: Double): Double {
        val r = a - b * floor(a / b)
        return if (r < 0) r + b else r
    }

    private fun dSin(d: Double) = sin(Math.toRadians(d))
    private fun dCos(d: Double) = cos(Math.toRadians(d))
    private fun dTan(d: Double) = tan(Math.toRadians(d))
    private fun dAsin(x: Double) = Math.toDegrees(asin(x))
    private fun dAcos(x: Double) = Math.toDegrees(acos(x))
    private fun dAtan2(y: Double, x: Double) = Math.toDegrees(atan2(y, x))
    private fun dAcot(x: Double) = Math.toDegrees(atan(1.0 / x))
}

private fun dailyOrder(day: PrayerTimesDay, isFriday: Boolean) = listOf(
    SalahPrayer.FAJR to day.fajr,
    (if (isFriday) SalahPrayer.JUMUAH else SalahPrayer.DHUHR) to day.dhuhr,
    SalahPrayer.ASR to day.asr,
    SalahPrayer.MAGHRIB to day.maghrib,
    SalahPrayer.ISHA to day.isha
)

/** Which salah's adhan is next from [nowMinutes], wrapping to tomorrow's Fajr after Isha. */
fun nextPrayerAfter(day: PrayerTimesDay, nowMinutes: Int, isFriday: Boolean): Pair<SalahPrayer, Int> =
    dailyOrder(day, isFriday).firstOrNull { it.second > nowMinutes } ?: (SalahPrayer.FAJR to day.fajr)

data class UpcomingSalah(
    val prayer: SalahPrayer,
    val adhan: Int,
    val jamaah: Int?,
    val mosque: Mosque?
)

/**
 * The salah to show as "next". Between a salah's adhan and its jama'ah at the
 * worshipper's chosen mosque, that jama'ah is still the next thing to go to.
 */
fun upcomingSalah(
    day: PrayerTimesDay,
    nowMinutes: Int,
    isFriday: Boolean,
    favorites: Map<SalahPrayer, Mosque>
): UpcomingSalah {
    dailyOrder(day, isFriday).forEach { (prayer, adhan) ->
        val mosque = favorites[prayer]
        val jamaah = mosque?.jamaahMinutes(prayer, day)
        if (adhan <= nowMinutes && jamaah != null && jamaah > nowMinutes) {
            return UpcomingSalah(prayer, adhan, jamaah, mosque)
        }
    }
    val (prayer, adhan) = nextPrayerAfter(day, nowMinutes, isFriday)
    val mosque = favorites[prayer]
    return UpcomingSalah(prayer, adhan, mosque?.jamaahMinutes(prayer, day), mosque)
}

/** Minutes from [now] to [target], wrapping past midnight. */
fun minutesUntil(now: Int, target: Int): Int = ((target - now) % 1440 + 1440) % 1440

data class PrayerReminder(
    val prayer: SalahPrayer,
    /** Minutes after local midnight on the reminder's day. */
    val adhanMinutes: Int,
    val adhanAtMillis: Long,
    val notifyAtMillis: Long
)

/**
 * The first enabled adhan whose notification time is strictly after [nowMillis].
 * Looks a few days ahead so a single enabled prayer, or a polar day with no
 * Isha, still finds the next one.
 */
fun nextPrayerReminder(
    nowMillis: Long,
    latitude: Double,
    longitude: Double,
    settings: SalahSettings,
    reminders: SalahReminders,
    timeZone: TimeZone = TimeZone.getDefault()
): PrayerReminder? {
    if (!reminders.enabled || reminders.prayers.isEmpty()) return null
    val lead = reminders.minutesBefore * 60_000L
    for (offset in 0..2) {
        val day = Calendar.getInstance(timeZone).apply {
            timeInMillis = nowMillis
            add(Calendar.DAY_OF_YEAR, offset)
        }
        val times = PrayerTimesCalculator.calculate(day, latitude, longitude, settings.method, settings.asrMethod, timeZone)
        val isFriday = day.get(Calendar.DAY_OF_WEEK) == Calendar.FRIDAY
        for ((prayer, minutes) in dailyOrder(times, isFriday)) {
            if (minutes < 0 || !reminders.covers(prayer)) continue
            val adhanAt = (day.clone() as Calendar).apply {
                set(Calendar.HOUR_OF_DAY, minutes / 60)
                set(Calendar.MINUTE, minutes % 60)
                set(Calendar.SECOND, 0)
                set(Calendar.MILLISECOND, 0)
            }.timeInMillis
            val notifyAt = adhanAt - lead
            if (notifyAt > nowMillis) return PrayerReminder(prayer, minutes, adhanAt, notifyAt)
        }
    }
    return null
}

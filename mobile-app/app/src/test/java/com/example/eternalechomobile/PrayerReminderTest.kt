package com.asloobulhayat.eternalecho

import com.asloobulhayat.eternalecho.data.AsrMethod
import com.asloobulhayat.eternalecho.data.CalculationMethod
import com.asloobulhayat.eternalecho.data.PrayerTimesCalculator
import com.asloobulhayat.eternalecho.data.SalahPrayer
import com.asloobulhayat.eternalecho.data.SalahReminders
import com.asloobulhayat.eternalecho.data.SalahSettings
import com.asloobulhayat.eternalecho.data.nextPrayerReminder
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test
import java.util.Calendar
import java.util.TimeZone

class PrayerReminderTest {
    private val karachi = TimeZone.getTimeZone("Asia/Karachi")
    private val lat = 24.8607
    private val lng = 67.0011
    private val settings = SalahSettings(CalculationMethod.KARACHI, AsrMethod.HANAFI, chosenByUser = true)
    private val allOn = SalahReminders(enabled = true)

    private fun at(day: Int, hour: Int, minute: Int = 0): Long = Calendar.getInstance(karachi).apply {
        clear()
        set(2026, Calendar.NOVEMBER, day, hour, minute, 0)
    }.timeInMillis

    private fun next(now: Long, reminders: SalahReminders = allOn) =
        nextPrayerReminder(now, lat, lng, settings, reminders, karachi)

    private fun dayOf(millis: Long): Int = Calendar.getInstance(karachi).apply { timeInMillis = millis }.get(Calendar.DAY_OF_MONTH)

    @Test
    fun nothingScheduledWhenOffOrEmpty() {
        assertNull(next(at(12, 10), SalahReminders(enabled = false)))
        assertNull(next(at(12, 10), SalahReminders(enabled = true, prayers = emptySet())))
    }

    @Test
    fun picksTheNextAdhanToday() {
        // Thursday 12 Nov, mid-morning: Dhuhr is next.
        val r = next(at(12, 10))!!
        assertEquals(SalahPrayer.DHUHR, r.prayer)
        assertEquals(12, dayOf(r.adhanAtMillis))
        val expected = PrayerTimesCalculator.calculate(2026, 11, 12, lat, lng, 5.0, settings.method, settings.asrMethod).dhuhr
        assertEquals(expected, r.adhanMinutes)
        assertEquals(r.adhanAtMillis, r.notifyAtMillis)
    }

    @Test
    fun wrapsToTomorrowWhenOnlyAnEarlierPrayerIsOn() {
        val r = next(at(12, 10), SalahReminders(enabled = true, prayers = setOf(SalahPrayer.FAJR)))!!
        assertEquals(SalahPrayer.FAJR, r.prayer)
        assertEquals(13, dayOf(r.adhanAtMillis))
    }

    @Test
    fun afterIshaTheNextIsTomorrowsFajr() {
        val r = next(at(12, 23, 30))!!
        assertEquals(SalahPrayer.FAJR, r.prayer)
        assertEquals(13, dayOf(r.adhanAtMillis))
    }

    @Test
    fun dhuhrBecomesJumuahOnFriday() {
        val r = next(at(13, 10), SalahReminders(enabled = true, prayers = setOf(SalahPrayer.DHUHR)))!!
        assertEquals(SalahPrayer.JUMUAH, r.prayer)
        assertTrue(SalahReminders(enabled = true, prayers = setOf(SalahPrayer.DHUHR)).covers(SalahPrayer.JUMUAH))
        assertFalse(SalahReminders(enabled = true, prayers = setOf(SalahPrayer.ASR)).covers(SalahPrayer.JUMUAH))
    }

    @Test
    fun leadTimeMovesTheNotificationEarlier() {
        val r = next(at(12, 10), allOn.copy(minutesBefore = 10))!!
        assertEquals(r.adhanAtMillis - 10 * 60_000L, r.notifyAtMillis)

        // Inside the lead window the reminder for that salah has already gone; move on to Asr.
        val after = next(r.notifyAtMillis + 60_000L, allOn.copy(minutesBefore = 10))
        assertNotNull(after)
        assertEquals(SalahPrayer.ASR, after!!.prayer)
    }

    @Test
    fun firingTimeItselfAdvancesToTheFollowingSalah() {
        val first = next(at(12, 10))!!
        val second = next(first.notifyAtMillis)!!
        assertEquals(SalahPrayer.ASR, second.prayer)
    }
}

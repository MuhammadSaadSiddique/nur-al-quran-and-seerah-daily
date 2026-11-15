package com.asloobulhayat.eternalecho

import com.asloobulhayat.eternalecho.data.AsrMethod
import com.asloobulhayat.eternalecho.data.CalculationMethod
import com.asloobulhayat.eternalecho.data.EidTimes
import com.asloobulhayat.eternalecho.data.Mosque
import com.asloobulhayat.eternalecho.data.MosqueApi
import com.asloobulhayat.eternalecho.data.MosqueDetailsInput
import com.asloobulhayat.eternalecho.data.MosqueFacility
import com.asloobulhayat.eternalecho.data.MosqueSect
import com.asloobulhayat.eternalecho.data.PlaceType
import com.asloobulhayat.eternalecho.data.defaultEidYear
import com.asloobulhayat.eternalecho.ui.salah.DetailsDraft
import com.asloobulhayat.eternalecho.data.PrayerTimesCalculator
import com.asloobulhayat.eternalecho.data.PrayerTimesDay
import com.asloobulhayat.eternalecho.data.SalahPrayer
import com.asloobulhayat.eternalecho.data.formatClockTime
import com.asloobulhayat.eternalecho.data.parseClockTime
import com.asloobulhayat.eternalecho.data.upcomingSalah
import com.asloobulhayat.eternalecho.ui.salah.displayTime
import org.json.JSONObject
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test
import kotlin.math.abs

class SalahTimesTest {

    private fun assertNear(label: String, expected: String, actualMinutes: Int, toleranceMinutes: Int = 2) {
        val expectedMinutes = parseClockTime(expected)!!
        val diff = abs(expectedMinutes - actualMinutes)
        assertTrue(
            "$label expected $expected but was ${formatClockTime(actualMinutes)}",
            diff <= toleranceMinutes
        )
    }

    // Reference values from api.aladhan.com (method 1, Hanafi, angle-based) for 12 Nov 2026.
    @Test
    fun karachiHanafiMatchesPublishedTimes() {
        val day = PrayerTimesCalculator.calculate(
            2026, 11, 12, 24.8607, 67.0011, 5.0,
            CalculationMethod.KARACHI, AsrMethod.HANAFI
        )
        assertNear("Fajr", "05:28", day.fajr)
        assertNear("Sunrise", "06:46", day.sunrise)
        assertNear("Dhuhr", "12:16", day.dhuhr)
        assertNear("Asr", "16:10", day.asr)
        assertNear("Maghrib", "17:46", day.maghrib)
        assertNear("Isha", "19:04", day.isha)
    }

    // London at midsummer: twilight never ends, so Fajr/Isha come from the high-latitude rule.
    @Test
    fun londonMidsummerUsesHighLatitudeRule() {
        val day = PrayerTimesCalculator.calculate(
            2026, 6, 21, 51.5074, -0.1278, 1.0,
            CalculationMethod.MWL, AsrMethod.STANDARD
        )
        assertNear("Fajr", "02:31", day.fajr)
        assertNear("Sunrise", "04:43", day.sunrise)
        assertNear("Dhuhr", "13:02", day.dhuhr)
        assertNear("Asr", "17:25", day.asr)
        assertNear("Maghrib", "21:22", day.maghrib)
        assertNear("Isha", "23:27", day.isha)
    }

    @Test
    fun hanafiAsrIsLaterThanStandard() {
        val standard = PrayerTimesCalculator.calculate(2026, 11, 12, 24.8607, 67.0011, 5.0, CalculationMethod.KARACHI, AsrMethod.STANDARD)
        val hanafi = PrayerTimesCalculator.calculate(2026, 11, 12, 24.8607, 67.0011, 5.0, CalculationMethod.KARACHI, AsrMethod.HANAFI)
        assertTrue(hanafi.asr - standard.asr in 30..90)
    }

    @Test
    fun ummAlQuraIshaIsNinetyMinutesAfterMaghrib() {
        val day = PrayerTimesCalculator.calculate(2026, 11, 12, 21.4225, 39.8262, 3.0, CalculationMethod.MAKKAH, AsrMethod.STANDARD)
        assertEquals(90, day.isha - day.maghrib)
    }

    @Test
    fun countryDefaultsFollowLocalConvention() {
        assertEquals(CalculationMethod.KARACHI to AsrMethod.HANAFI, PrayerTimesCalculator.defaultsForCountry("pk"))
        assertEquals(CalculationMethod.ISNA to AsrMethod.STANDARD, PrayerTimesCalculator.defaultsForCountry("US"))
        assertEquals(CalculationMethod.MWL to AsrMethod.STANDARD, PrayerTimesCalculator.defaultsForCountry("GB"))
    }

    private val sampleDay = PrayerTimesDay(
        fajr = 5 * 60 + 28, sunrise = 6 * 60 + 46, dhuhr = 12 * 60 + 16,
        asr = 16 * 60 + 10, maghrib = 17 * 60 + 46, isha = 19 * 60 + 4
    )

    private fun mosque(times: Map<SalahPrayer, String>, offset: Int? = null) = Mosque(
        id = 1, name = "Masjid Al-Noor", latitude = 24.9, longitude = 67.0,
        times = times, maghribOffsetMinutes = offset
    )

    @Test
    fun maghribGapResolvesAgainstTodaysAdhan() {
        val m = mosque(emptyMap(), offset = 7)
        assertEquals(17 * 60 + 53, m.jamaahMinutes(SalahPrayer.MAGHRIB, sampleDay))
        assertNull(m.jamaahMinutes(SalahPrayer.MAGHRIB, null))
        assertTrue(m.hasAnyTime)
    }

    @Test
    fun pendingJamaahStaysNextAfterItsAdhan() {
        // 16:20: Asr adhan (16:10) has passed but the mosque's jama'ah at 16:30 has not.
        val favorites = mapOf(SalahPrayer.ASR to mosque(mapOf(SalahPrayer.ASR to "16:30")))
        val next = upcomingSalah(sampleDay, 16 * 60 + 20, isFriday = false, favorites = favorites)
        assertEquals(SalahPrayer.ASR, next.prayer)
        assertEquals(16 * 60 + 30, next.jamaah)

        // Without a pending jama'ah, Maghrib is next.
        assertEquals(SalahPrayer.MAGHRIB, upcomingSalah(sampleDay, 16 * 60 + 20, false, emptyMap()).prayer)
    }

    @Test
    fun fridayDhuhrIsJumuahAndIshaWrapsToFajr() {
        assertEquals(SalahPrayer.JUMUAH, upcomingSalah(sampleDay, 9 * 60, isFriday = true, favorites = emptyMap()).prayer)
        assertEquals(SalahPrayer.FAJR, upcomingSalah(sampleDay, 22 * 60, isFriday = false, favorites = emptyMap()).prayer)
    }

    @Test
    fun parsesServerMosqueWithNullsAndRoundTripsCache() {
        val json = JSONObject(
            """
            {"id":7,"name":"Masjid Bilal","address":null,"locality":"Block 13","city":"Karachi","country":null,
             "latitude":24.9204,"longitude":67.0932,
             "times":{"fajr":"05:45","dhuhr":"13:30","asr":null,"maghrib":null,"isha":"20:15","jumuah":"13:15"},
             "maghrib_offset_minutes":5,"has_women_area":null,"is_verified":true,"confirmations_count":3,
             "regulars_count":12,"times_updated_at":"2026-11-10T10:00:00+05:00","days_since_update":2,
             "is_stale":false,"distance_km":0.42}
            """.trimIndent()
        )
        val m = MosqueApi.parseMosque(json)
        assertEquals("", m.address) // JSON null must not become the text "null"
        assertEquals("Block 13, Karachi", m.areaLine)
        assertEquals(setOf(SalahPrayer.FAJR, SalahPrayer.DHUHR, SalahPrayer.ISHA, SalahPrayer.JUMUAH), m.times.keys)
        assertEquals(5, m.maghribOffsetMinutes)
        assertNull(m.hasWomenArea)
        assertTrue(m.isVerified)
        assertFalse(m.isStale)
        assertEquals(0.42, m.distanceKm!!, 1e-9)

        val roundTrip = MosqueApi.parseMosque(MosqueApi.mosqueToJson(m))
        assertEquals(m, roundTrip)
    }

    @Test
    fun parsesProfileFacilitiesAndRamadanDetails() {
        val json = JSONObject(
            """
            {"id":9,"name":"Musalla Saddar","latitude":24.9,"longitude":67.0,
             "place_type":"musalla","sect":"shia_ithna_ashari","sect_change_pending":true,"asr_method":"standard",
             "times":{"jumuah":"13:15"},"jumuah_second_time":"14:00",
             "facilities":{"wheelchair_entrance":true,"car_parking":false,"air_conditioning":null,"helipad":true},
             "khutbah_languages":["ur","ar","xx"],
             "taraweeh":{"rakats":20,"khatm":null},
             "eid_times":{"fitr":{"year":2027,"times":["07:00","08:30"]}}}
            """.trimIndent()
        )
        val m = MosqueApi.parseMosque(json)
        assertEquals(PlaceType.MUSALLA, m.placeType)
        assertEquals(MosqueSect.SHIA_ITHNA_ASHARI, m.sect)
        assertTrue(m.sectChangePending)
        assertEquals(AsrMethod.STANDARD, m.asrMethod)
        assertEquals("14:00", m.jumuahSecondTime)
        assertEquals(mapOf(MosqueFacility.WHEELCHAIR_ENTRANCE to true, MosqueFacility.CAR_PARKING to false), m.facilities)
        assertTrue(m.has(MosqueFacility.WHEELCHAIR_ENTRANCE))
        assertFalse(m.has(MosqueFacility.CAR_PARKING))
        assertEquals(listOf("ur", "ar"), m.khutbahLanguages)
        assertEquals(20, m.taraweehRakats)
        assertNull(m.taraweehKhatm)
        assertEquals(EidTimes(2027, listOf("07:00", "08:30")), m.eidFitr)
        assertNull(m.eidAdha)

        assertEquals(m, MosqueApi.parseMosque(MosqueApi.mosqueToJson(m)))
    }

    @Test
    fun keepsOpenStreetMapAttributionAndRejectsForeignLinks() {
        val osm = MosqueApi.parseMosque(JSONObject(
            """{"id":3,"name":"Masjid Tooba","latitude":24.8,"longitude":67.0,
               "source":"osm","attribution":"© OpenStreetMap contributors",
               "osm_url":"https://www.openstreetmap.org/way/123"}"""
        ))
        assertTrue(osm.fromOpenStreetMap)
        assertEquals("https://www.openstreetmap.org/way/123", osm.osmUrl)
        assertEquals(osm, MosqueApi.parseMosque(MosqueApi.mosqueToJson(osm)))

        val community = MosqueApi.parseMosque(JSONObject(
            """{"id":4,"name":"Masjid Bilal","latitude":24.8,"longitude":67.0,
               "attribution":null,"osm_url":"https://evil.example/phish"}"""
        ))
        assertFalse(community.fromOpenStreetMap)
        assertNull(community.osmUrl)
    }

    @Test
    fun detailParamsAreFormEncodedForPhp() {
        val params = MosqueApi.detailParams(
            MosqueDetailsInput(
                placeType = PlaceType.MASJID,
                sect = null,
                asrMethod = AsrMethod.HANAFI,
                hasWomenArea = false,
                facilities = mapOf(MosqueFacility.PRAYER_CHAIRS to true, MosqueFacility.TOILETS to false),
                khutbahLanguages = listOf("ar", "ur"),
                eidYear = 2027,
                eidFitrTimes = listOf("07:00", "08:30")
            )
        )
        assertEquals("", params["sect"])
        assertEquals("hanafi", params["asr_method"])
        assertEquals("0", params["has_women_area"])
        assertEquals("1", params["facilities[prayer_chairs]"])
        assertEquals("0", params["facilities[toilets]"])
        assertEquals("", params["facilities[wheelchair_entrance]"])
        assertEquals("ar,ur", params["khutbah_languages"])
        assertEquals("07:00,08:30", params["eid_fitr_times"])
        assertEquals("", params["eid_adha_times"])
    }

    @Test
    fun untickingAnAmenityKeepsAnEarlierNo() {
        val stored = Mosque(
            id = 1, name = "M", latitude = 0.0, longitude = 0.0,
            facilities = mapOf(MosqueFacility.CAR_PARKING to false, MosqueFacility.TOILETS to true)
        )
        val draft = DetailsDraft(stored)

        draft.toggleAmenity(MosqueFacility.CAR_PARKING) // someone found parking
        assertEquals(true, draft.facilities[MosqueFacility.CAR_PARKING])
        draft.toggleAmenity(MosqueFacility.CAR_PARKING) // changed their mind: back to the stored "no"
        assertEquals(false, draft.facilities[MosqueFacility.CAR_PARKING])

        draft.toggleAmenity(MosqueFacility.TOILETS) // unticking a "yes" means unknown
        assertNull(draft.facilities[MosqueFacility.TOILETS])
    }

    @Test
    fun eidYearRollsOverMidYear() {
        val march = java.util.Calendar.getInstance().apply { set(2027, java.util.Calendar.MARCH, 1) }
        val november = java.util.Calendar.getInstance().apply { set(2026, java.util.Calendar.NOVEMBER, 12) }
        assertEquals(2027, defaultEidYear(march))
        assertEquals(2027, defaultEidYear(november))
    }

    @Test
    fun clockFormattingUsesAsciiDigitsAndBothClockStyles() {
        assertEquals("05:05", formatClockTime(305))
        assertNull(parseClockTime("25:00"))
        assertEquals("4:30 PM", displayTime(16 * 60 + 30, is24Hour = false))
        assertEquals("12:05 AM", displayTime(5, is24Hour = false))
        assertEquals("16:30", displayTime(16 * 60 + 30, is24Hour = true))
    }
}

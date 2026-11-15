package com.asloobulhayat.eternalecho.data

/** The salahs a worshipper can attach a mosque to, in daily order. */
enum class SalahPrayer(val key: String, val label: String) {
    FAJR("fajr", "Fajr"),
    DHUHR("dhuhr", "Dhuhr"),
    ASR("asr", "Asr"),
    MAGHRIB("maghrib", "Maghrib"),
    ISHA("isha", "Isha"),
    JUMUAH("jumuah", "Jumu'ah");

    companion object {
        val DAILY = listOf(FAJR, DHUHR, ASR, MAGHRIB, ISHA)
        fun fromKey(key: String?): SalahPrayer? = entries.firstOrNull { it.key == key?.trim()?.lowercase() }
    }
}

/** Fajr and Isha twilight angles used by the major calculation authorities. */
enum class CalculationMethod(
    val label: String,
    val fajrAngle: Double,
    val ishaAngle: Double?,
    val ishaMinutesAfterMaghrib: Int?
) {
    KARACHI("University of Islamic Sciences, Karachi", 18.0, 18.0, null),
    MWL("Muslim World League", 18.0, 17.0, null),
    ISNA("Islamic Society of North America", 15.0, 15.0, null),
    EGYPT("Egyptian General Authority of Survey", 19.5, 17.5, null),
    MAKKAH("Umm al-Qura, Makkah", 18.5, null, 90);

    companion object {
        fun fromName(name: String?): CalculationMethod? = entries.firstOrNull { it.name == name }
    }
}

enum class AsrMethod(val label: String, val shadowFactor: Int) {
    STANDARD("Standard (Shafi'i, Maliki, Hanbali)", 1),
    HANAFI("Hanafi", 2);

    /** Value used by the server ("standard" / "hanafi"). */
    val key: String get() = name.lowercase()

    companion object {
        fun fromName(name: String?): AsrMethod? = entries.firstOrNull { it.name == name }
        fun fromKey(key: String?): AsrMethod? = entries.firstOrNull { it.key == key?.trim()?.lowercase() }
    }
}

enum class PlaceType(val key: String, val label: String) {
    MASJID("masjid", "Masjid"),
    MUSALLA("musalla", "Musalla / prayer room");

    companion object {
        fun fromKey(key: String?): PlaceType = entries.firstOrNull { it.key == key } ?: MASJID
    }
}

/** Fixed list, named as communities name themselves. Null means not specified. */
enum class MosqueSect(val key: String, val label: String, val shortLabel: String, val tradition: String) {
    DEOBANDI("deobandi", "Deobandi", "Deobandi", "Sunni"),
    BARELVI("barelvi", "Barelvi (Ahl-e-Sunnat)", "Barelvi", "Sunni"),
    AHL_E_HADITH("ahl_e_hadith", "Ahl-e-Hadith", "Ahl-e-Hadith", "Sunni"),
    SUNNI_OTHER("sunni_other", "Other Sunni", "Sunni", "Sunni"),
    SHIA_ITHNA_ASHARI("shia_ithna_ashari", "Ithna Ashari (Ja'fari)", "Shia Ithna Ashari", "Shia"),
    ISMAILI("ismaili", "Ismaili", "Ismaili", "Shia"),
    BOHRA("bohra", "Bohra", "Bohra", "Shia"),
    SHIA_OTHER("shia_other", "Other Shia", "Shia", "Shia");

    companion object {
        fun fromKey(key: String?): MosqueSect? = entries.firstOrNull { it.key == key }
    }
}

enum class FacilityGroup { ACCESSIBILITY, WOMEN, AMENITY }

/** Stored as yes / no / unknown on the server. */
enum class MosqueFacility(val key: String, val label: String, val group: FacilityGroup) {
    WHEELCHAIR_ENTRANCE("wheelchair_entrance", "Step-free entrance", FacilityGroup.ACCESSIBILITY),
    ACCESSIBLE_WUDU("accessible_wudu", "Accessible wudu & toilet", FacilityGroup.ACCESSIBILITY),
    PRAYER_CHAIRS("prayer_chairs", "Chairs for seated prayer", FacilityGroup.ACCESSIBILITY),
    WOMEN_WUDU("women_wudu", "Women's wudu area", FacilityGroup.WOMEN),
    WUDU_AREA("wudu_area", "Wudu area", FacilityGroup.AMENITY),
    TOILETS("toilets", "Toilets", FacilityGroup.AMENITY),
    CAR_PARKING("car_parking", "Car parking", FacilityGroup.AMENITY),
    BIKE_PARKING("bike_parking", "Motorbike parking", FacilityGroup.AMENITY),
    AIR_CONDITIONING("air_conditioning", "Air-conditioned", FacilityGroup.AMENITY);

    companion object {
        fun fromKey(key: String?): MosqueFacility? = entries.firstOrNull { it.key == key }
    }
}

/** Nearby-list filters the server understands. */
enum class MosqueFilter(val key: String, val label: String) {
    WHEELCHAIR("wheelchair", "Wheelchair access"),
    CHAIRS("chairs", "Prayer chairs"),
    WOMEN("women", "Women's area"),
    JUMUAH("jumuah", "Jumu'ah"),
    PARKING("parking", "Parking")
}

val KHUTBAH_LANGUAGES: List<Pair<String, String>> = listOf(
    "ar" to "Arabic", "ur" to "Urdu", "en" to "English", "bn" to "Bengali", "pa" to "Punjabi",
    "ps" to "Pashto", "sd" to "Sindhi", "fa" to "Persian", "tr" to "Turkish", "ms" to "Malay",
    "id" to "Indonesian", "fr" to "French", "so" to "Somali"
)

fun khutbahLanguageLabel(code: String): String =
    KHUTBAH_LANGUAGES.firstOrNull { it.first == code }?.second ?: code.uppercase()

const val OSM_COPYRIGHT_URL = "https://www.openstreetmap.org/copyright"

const val MAX_KHUTBAH_LANGUAGES = 4
const val MAX_EID_TIMES = 4

data class EidTimes(val year: Int, val times: List<String>)

data class SalahSettings(
    val method: CalculationMethod = CalculationMethod.MWL,
    val asrMethod: AsrMethod = AsrMethod.STANDARD,
    /** False until the worshipper picks a method; until then the default follows their country. */
    val chosenByUser: Boolean = false
)

/** Adhan (start) times for one day, as minutes after local midnight. */
data class PrayerTimesDay(
    val fajr: Int,
    val sunrise: Int,
    val dhuhr: Int,
    val asr: Int,
    val maghrib: Int,
    val isha: Int
) {
    fun startOf(prayer: SalahPrayer): Int = when (prayer) {
        SalahPrayer.FAJR -> fajr
        SalahPrayer.DHUHR, SalahPrayer.JUMUAH -> dhuhr
        SalahPrayer.ASR -> asr
        SalahPrayer.MAGHRIB -> maghrib
        SalahPrayer.ISHA -> isha
    }
}

data class SavedLocation(
    val latitude: Double,
    val longitude: Double,
    val label: String = "",
    val countryCode: String = "",
    val accuracyMeters: Float? = null,
    val savedAtMillis: Long = System.currentTimeMillis()
)

data class Mosque(
    val id: Int,
    val name: String,
    val address: String = "",
    val locality: String = "",
    val city: String = "",
    val country: String = "",
    val latitude: Double,
    val longitude: Double,
    val placeType: PlaceType = PlaceType.MASJID,
    val sect: MosqueSect? = null,
    val sectChangePending: Boolean = false,
    /** How this mosque times Asr, which may differ from the worshipper's own setting. */
    val asrMethod: AsrMethod? = null,
    /** Jama'ah times as "HH:MM" (24h), keyed by [SalahPrayer]. Missing means not known. */
    val times: Map<SalahPrayer, String> = emptyMap(),
    val jumuahSecondTime: String? = null,
    val maghribOffsetMinutes: Int? = null,
    val hasWomenArea: Boolean? = null,
    /** Only facilities someone has answered; absent means unknown. */
    val facilities: Map<MosqueFacility, Boolean> = emptyMap(),
    val khutbahLanguages: List<String> = emptyList(),
    val taraweehRakats: Int? = null,
    val taraweehKhatm: Boolean? = null,
    val eidFitr: EidTimes? = null,
    val eidAdha: EidTimes? = null,
    val isVerified: Boolean = false,
    val confirmationsCount: Int = 0,
    val regularsCount: Int = 0,
    val timesUpdatedAt: String? = null,
    val daysSinceUpdate: Int? = null,
    val isStale: Boolean = true,
    val distanceKm: Double? = null,
    /** Set for OpenStreetMap-derived entries, which must show it (ODbL). */
    val attribution: String? = null,
    val osmUrl: String? = null
) {
    val fromOpenStreetMap: Boolean get() = attribution != null
    val areaLine: String
        get() = listOf(locality, city).filter { it.isNotBlank() }.distinct().joinToString(", ")
            .ifBlank { address }

    val hasAnyTime: Boolean
        get() = times.isNotEmpty() || maghribOffsetMinutes != null || jumuahSecondTime != null

    fun has(facility: MosqueFacility): Boolean = facilities[facility] == true

    val hasRamadanInfo: Boolean
        get() = taraweehRakats != null || taraweehKhatm != null || eidFitr != null || eidAdha != null

    /**
     * Jama'ah time in minutes after midnight. Maghrib may be stored as a gap after
     * the adhan, so the day's calculated Maghrib is needed to resolve it.
     */
    fun jamaahMinutes(prayer: SalahPrayer, adhan: PrayerTimesDay?): Int? {
        if (prayer == SalahPrayer.MAGHRIB && maghribOffsetMinutes != null) {
            return adhan?.let { (it.maghrib + maghribOffsetMinutes) % (24 * 60) }
        }
        return times[prayer]?.let { parseClockTime(it) }
    }
}

data class MosqueContributionSummary(
    val mosquesAdded: Int = 0,
    val timesUpdated: Int = 0,
    val confirmations: Int = 0,
    val mosquesHelped: Int = 0,
    val worshippersBenefiting: Int = 0
)

/** What a worshipper typed in the add/update forms. Times are "HH:MM" or null. */
data class MosqueTimesInput(
    val times: Map<SalahPrayer, String?> = emptyMap(),
    val jumuahSecondTime: String? = null,
    val maghribOffsetMinutes: Int? = null
) {
    val isEmpty: Boolean
        get() = times.values.all { it.isNullOrBlank() } && jumuahSecondTime.isNullOrBlank() && maghribOffsetMinutes == null
}

/** Everything about a mosque besides its daily times, as the forms send it. */
data class MosqueDetailsInput(
    val placeType: PlaceType = PlaceType.MASJID,
    val sect: MosqueSect? = null,
    val asrMethod: AsrMethod? = null,
    val hasWomenArea: Boolean? = null,
    val facilities: Map<MosqueFacility, Boolean?> = emptyMap(),
    val khutbahLanguages: List<String> = emptyList(),
    val taraweehRakats: Int? = null,
    val taraweehKhatm: Boolean? = null,
    val eidYear: Int,
    val eidFitrTimes: List<String> = emptyList(),
    val eidAdhaTimes: List<String> = emptyList()
)

/**
 * The Gregorian year Eid times are most likely being entered for. Both Eids
 * fall in the first half of the year until the early 2030s, so from June on
 * people are preparing for next year's.
 */
fun defaultEidYear(calendar: java.util.Calendar = java.util.Calendar.getInstance()): Int {
    val year = calendar.get(java.util.Calendar.YEAR)
    return if (calendar.get(java.util.Calendar.MONTH) >= java.util.Calendar.JUNE) year + 1 else year
}

/** Result of adding a mosque: either a new entry, or the one already listed at that spot. */
data class AddMosqueResult(
    val mosque: Mosque,
    val duplicate: Boolean,
    val message: String
)

fun parseClockTime(raw: String): Int? {
    val parts = raw.trim().split(':')
    if (parts.size != 2) return null
    val h = parts[0].toIntOrNull() ?: return null
    val m = parts[1].toIntOrNull() ?: return null
    if (h !in 0..23 || m !in 0..59) return null
    return h * 60 + m
}

fun formatClockTime(minutes: Int): String {
    val m = ((minutes % 1440) + 1440) % 1440
    // Locale.US keeps ASCII digits: this string is also what gets sent to the server.
    return String.format(java.util.Locale.US, "%02d:%02d", m / 60, m % 60)
}

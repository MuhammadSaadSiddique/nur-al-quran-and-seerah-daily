package com.asloobulhayat.eternalecho.data

import android.content.Context
import com.asloobulhayat.eternalecho.security.SecurePreferences
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import org.json.JSONObject

/**
 * On-device Salah state: last known location, calculation settings, and the
 * mosque chosen for each salah. Favourites live here first so guests can use
 * them; signed-in worshippers also sync them to the server.
 */
object SalahStore {
    private const val KEY_LOCATION = "salah_location_v1"
    private const val KEY_SETTINGS = "salah_settings_v1"
    private const val KEY_FAVORITES = "salah_favorites_v1"
    private const val KEY_REMINDERS = "salah_reminders_v1"

    @Volatile
    private var prefs: SecurePreferences? = null

    private val _location = MutableStateFlow<SavedLocation?>(null)
    val location: StateFlow<SavedLocation?> = _location.asStateFlow()

    private val _settings = MutableStateFlow(SalahSettings())
    val settings: StateFlow<SalahSettings> = _settings.asStateFlow()

    private val _favorites = MutableStateFlow<Map<SalahPrayer, Mosque>>(emptyMap())
    val favorites: StateFlow<Map<SalahPrayer, Mosque>> = _favorites.asStateFlow()

    private val _reminders = MutableStateFlow(SalahReminders())
    val reminders: StateFlow<SalahReminders> = _reminders.asStateFlow()

    fun init(context: Context) {
        if (prefs != null) return
        val p = SecurePreferences.getInstance(context.applicationContext)
        prefs = p
        _location.value = decodeLocation(p.getString(KEY_LOCATION, null))
        _settings.value = decodeSettings(p.getString(KEY_SETTINGS, null))
        _favorites.value = decodeFavorites(p.getString(KEY_FAVORITES, null))
        _reminders.value = decodeReminders(p.getString(KEY_REMINDERS, null))
    }

    fun setReminders(reminders: SalahReminders) {
        _reminders.value = reminders
        prefs?.putString(
            KEY_REMINDERS,
            JSONObject()
                .put("on", reminders.enabled)
                .put("prayers", reminders.prayers.joinToString(",") { it.key })
                .put("before", reminders.minutesBefore)
                .toString()
        )
    }

    fun setLocation(location: SavedLocation) {
        _location.value = location
        prefs?.putString(
            KEY_LOCATION,
            JSONObject()
                .put("lat", location.latitude)
                .put("lng", location.longitude)
                .put("label", location.label)
                .put("cc", location.countryCode)
                .put("acc", location.accuracyMeters?.toDouble() ?: JSONObject.NULL)
                .put("at", location.savedAtMillis)
                .toString()
        )
    }

    fun setSettings(settings: SalahSettings) {
        _settings.value = settings
        prefs?.putString(
            KEY_SETTINGS,
            JSONObject()
                .put("method", settings.method.name)
                .put("asr", settings.asrMethod.name)
                .put("chosen", settings.chosenByUser)
                .toString()
        )
    }

    /**
     * Settings actually used for calculation. Until the worshipper chooses, follow
     * the convention of the country they are in.
     */
    fun effectiveSettings(
        settings: SalahSettings = _settings.value,
        location: SavedLocation? = _location.value
    ): SalahSettings {
        if (settings.chosenByUser) return settings
        val cc = location?.countryCode.orEmpty()
        if (cc.isBlank()) return settings
        val (method, asr) = PrayerTimesCalculator.defaultsForCountry(cc)
        return SalahSettings(method, asr, chosenByUser = false)
    }

    fun setFavorite(prayer: SalahPrayer, mosque: Mosque?) {
        val next = _favorites.value.toMutableMap()
        if (mosque == null) next.remove(prayer) else next[prayer] = mosque
        replaceFavorites(next)
    }

    fun replaceFavorites(favorites: Map<SalahPrayer, Mosque>) {
        _favorites.value = favorites
        val json = JSONObject()
        favorites.forEach { (prayer, mosque) -> json.put(prayer.key, MosqueApi.mosqueToJson(mosque)) }
        prefs?.putString(KEY_FAVORITES, json.toString())
    }

    /** Swap in fresh server data for cached favourites; drop ones no longer listed. */
    fun refreshFavoriteMosques(fresh: List<Mosque>) {
        val byId = fresh.associateBy { it.id }
        val next = _favorites.value.mapNotNull { (prayer, cached) ->
            byId[cached.id]?.let { prayer to it }
        }.toMap()
        replaceFavorites(next)
    }

    /** Keep a just-edited mosque's cached copy in sync with what the server returned. */
    fun updateCachedMosque(mosque: Mosque) {
        val current = _favorites.value
        if (current.values.none { it.id == mosque.id }) return
        replaceFavorites(current.mapValues { (_, m) -> if (m.id == mosque.id) mosque.copy(distanceKm = mosque.distanceKm ?: m.distanceKm) else m })
    }

    private fun decodeLocation(raw: String?): SavedLocation? = try {
        raw?.let {
            val o = JSONObject(it)
            SavedLocation(
                latitude = o.getDouble("lat"),
                longitude = o.getDouble("lng"),
                label = o.optString("label", ""),
                countryCode = o.optString("cc", ""),
                accuracyMeters = if (o.isNull("acc")) null else o.optDouble("acc").toFloat(),
                savedAtMillis = o.optLong("at", 0L)
            )
        }
    } catch (_: Exception) {
        null
    }

    private fun decodeSettings(raw: String?): SalahSettings = try {
        raw?.let {
            val o = JSONObject(it)
            SalahSettings(
                method = CalculationMethod.fromName(o.optString("method")) ?: CalculationMethod.MWL,
                asrMethod = AsrMethod.fromName(o.optString("asr")) ?: AsrMethod.STANDARD,
                chosenByUser = o.optBoolean("chosen", false)
            )
        } ?: SalahSettings()
    } catch (_: Exception) {
        SalahSettings()
    }

    private fun decodeReminders(raw: String?): SalahReminders = try {
        raw?.let {
            val o = JSONObject(it)
            SalahReminders(
                enabled = o.optBoolean("on", false),
                prayers = o.optString("prayers").split(',').mapNotNull { key -> SalahPrayer.fromKey(key) }.toSet(),
                minutesBefore = o.optInt("before", 0).coerceIn(0, 60)
            )
        } ?: SalahReminders()
    } catch (_: Exception) {
        SalahReminders()
    }

    private fun decodeFavorites(raw: String?): Map<SalahPrayer, Mosque> = try {
        raw?.let { MosqueApi.parseFavorites(JSONObject(it)) } ?: emptyMap()
    } catch (_: Exception) {
        emptyMap()
    }
}

package com.asloobulhayat.eternalecho.ui.salah

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import com.asloobulhayat.eternalecho.data.AddMosqueResult
import com.asloobulhayat.eternalecho.data.ApiClient
import com.asloobulhayat.eternalecho.data.DailyEngagementStore
import com.asloobulhayat.eternalecho.data.EngagementBus
import com.asloobulhayat.eternalecho.data.Mosque
import com.asloobulhayat.eternalecho.data.MosqueApi
import com.asloobulhayat.eternalecho.data.MosqueContributionSummary
import com.asloobulhayat.eternalecho.data.MosqueDetailsInput
import com.asloobulhayat.eternalecho.data.MosqueFilter
import com.asloobulhayat.eternalecho.data.MosqueSect
import com.asloobulhayat.eternalecho.data.MosqueTimesInput
import com.asloobulhayat.eternalecho.data.SalahPrayer
import com.asloobulhayat.eternalecho.data.SalahReminders
import com.asloobulhayat.eternalecho.data.SalahSettings
import com.asloobulhayat.eternalecho.data.SalahStore
import com.asloobulhayat.eternalecho.data.SavedLocation
import com.asloobulhayat.eternalecho.notifications.PrayerReminderScheduler
import com.asloobulhayat.eternalecho.security.SecurePreferences
import com.asloobulhayat.eternalecho.util.LocationHelper
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class SalahUiState(
    val locating: Boolean = false,
    val locationError: String? = null,
    val loadingNearby: Boolean = false,
    val nearby: List<Mosque> = emptyList(),
    val nearbyError: String? = null,
    val hasSearched: Boolean = false,
    val searchQuery: String = "",
    val radiusKm: Double = 5.0,
    val filters: Set<MosqueFilter> = emptySet(),
    /** Off by default: the list is never narrowed by sect unless the worshipper asks. */
    val sectFilter: MosqueSect? = null,
    val busyMosqueId: Int? = null,
    val submitting: Boolean = false,
    val summary: MosqueContributionSummary? = null,
    val message: String? = null
)

class SalahViewModel(application: Application) : AndroidViewModel(application) {
    private val prefs = SecurePreferences.getInstance(application)

    private val _state = MutableStateFlow(SalahUiState())
    val state: StateFlow<SalahUiState> = _state.asStateFlow()

    val location: StateFlow<SavedLocation?> = SalahStore.location
    val settings: StateFlow<SalahSettings> = SalahStore.settings
    val favorites: StateFlow<Map<SalahPrayer, Mosque>> = SalahStore.favorites
    val reminders: StateFlow<SalahReminders> = SalahStore.reminders

    private var nearbyJob: Job? = null
    private var started = false

    init {
        SalahStore.init(application)
    }

    val isLoggedIn: Boolean
        get() = prefs.getInt("user_id", -1) > 0 && !prefs.getString("api_token", null).isNullOrBlank()

    /** First open: use the saved spot straight away, then refresh in the background. */
    fun start() {
        if (started) return
        started = true
        if (location.value != null) loadNearby()
        syncFavorites()
        loadSummary()
        if (LocationHelper.hasPermission(getApplication())) refreshLocation()
    }

    fun refreshLocation() {
        val app = getApplication<Application>()
        if (!LocationHelper.hasPermission(app)) {
            _state.update { it.copy(locationError = "Allow location access to find mosques near you.") }
            return
        }
        if (!LocationHelper.isLocationEnabled(app)) {
            _state.update { it.copy(locationError = "Turn on location in your phone settings to find nearby mosques.") }
            return
        }
        viewModelScope.launch {
            _state.update { it.copy(locating = true, locationError = null) }
            val fix = LocationHelper.currentLocation(app)
            if (fix == null) {
                _state.update {
                    it.copy(
                        locating = false,
                        locationError = if (location.value == null) "Couldn't get your location. Step outside or try again." else null
                    )
                }
                return@launch
            }
            val previous = location.value
            val place = LocationHelper.describe(app, fix.latitude, fix.longitude)
            SalahStore.setLocation(
                SavedLocation(
                    latitude = fix.latitude,
                    longitude = fix.longitude,
                    label = place?.label ?: previous?.label.orEmpty(),
                    countryCode = place?.countryCode ?: previous?.countryCode.orEmpty(),
                    accuracyMeters = if (fix.hasAccuracy()) fix.accuracy else null
                )
            )
            PrayerReminderScheduler.reschedule(app)
            _state.update { it.copy(locating = false) }
            loadNearby()
        }
    }

    fun onSearchChange(query: String) {
        _state.update { it.copy(searchQuery = query) }
        if (query.isBlank()) loadNearby()
    }

    fun setRadius(km: Double) {
        _state.update { it.copy(radiusKm = km) }
        loadNearby()
    }

    fun toggleFilter(filter: MosqueFilter) {
        _state.update { s -> s.copy(filters = if (filter in s.filters) s.filters - filter else s.filters + filter) }
        loadNearby()
    }

    fun clearFilters() {
        _state.update { it.copy(filters = emptySet(), sectFilter = null) }
        loadNearby()
    }

    fun setSectFilter(sect: MosqueSect?) {
        _state.update { it.copy(sectFilter = sect) }
        loadNearby()
    }

    fun loadNearby() {
        val loc = location.value
        val query = _state.value.searchQuery.trim()
        if (loc == null && query.length < 2) return

        nearbyJob?.cancel()
        nearbyJob = viewModelScope.launch {
            _state.update { it.copy(loadingNearby = true, nearbyError = null) }
            val s = _state.value
            MosqueApi.nearby(loc?.latitude, loc?.longitude, s.radiusKm, query, s.filters, s.sectFilter)
                .onSuccess { list ->
                    _state.update { it.copy(loadingNearby = false, nearby = list, hasSearched = true) }
                    // Nearby results carry fresh distances and times for any favourites among them.
                    list.forEach { SalahStore.updateCachedMosque(it) }
                }
                .onFailure { e ->
                    _state.update { it.copy(loadingNearby = false, nearbyError = e.message, hasSearched = true) }
                }
        }
    }

    /**
     * Signed in: the server copy wins, except a guest's favourites are uploaded
     * the first time they sign in. Guest: refresh cached mosques from the server.
     */
    fun syncFavorites() {
        val loc = location.value
        viewModelScope.launch {
            if (isLoggedIn) {
                ApiClient.authToken = prefs.getString("api_token", null)
                val server = MosqueApi.favorites(loc?.latitude, loc?.longitude).getOrNull() ?: return@launch
                val local = favorites.value
                if (server.isEmpty() && local.isNotEmpty()) {
                    var latest = server
                    local.forEach { (prayer, mosque) ->
                        MosqueApi.setFavorite(prayer, mosque.id).onSuccess { latest = it }
                    }
                    SalahStore.replaceFavorites(latest.ifEmpty { local })
                } else {
                    SalahStore.replaceFavorites(server)
                }
            } else {
                val ids = favorites.value.values.map { it.id }
                if (ids.isEmpty()) return@launch
                MosqueApi.byIds(ids, loc?.latitude, loc?.longitude).onSuccess { SalahStore.refreshFavoriteMosques(it) }
            }
        }
    }

    /** Make [mosque] the place for exactly the [prayers] chosen; other prayers keep their mosque. */
    fun setFavoritePrayers(mosque: Mosque, prayers: Set<SalahPrayer>) {
        val current = favorites.value
        val changes = SalahPrayer.entries.mapNotNull { prayer ->
            val isHere = current[prayer]?.id == mosque.id
            when {
                prayer in prayers && !isHere -> prayer to mosque
                prayer !in prayers && isHere -> prayer to null
                else -> null
            }
        }
        if (changes.isEmpty()) return

        changes.forEach { (prayer, m) -> SalahStore.setFavorite(prayer, m) }
        val added = changes.count { it.second != null }
        _state.update {
            it.copy(
                message = if (added > 0) "${mosque.name} saved for ${prayers.joinToString { p -> p.label }}."
                else "Removed ${mosque.name} from your salah list."
            )
        }
        pushFavoriteChanges(changes)
    }

    fun clearFavorite(prayer: SalahPrayer) {
        SalahStore.setFavorite(prayer, null)
        pushFavoriteChanges(listOf(prayer to null))
    }

    fun chooseFavorite(prayer: SalahPrayer, mosque: Mosque) {
        SalahStore.setFavorite(prayer, mosque)
        _state.update { it.copy(message = "${prayer.label}: ${mosque.name}") }
        pushFavoriteChanges(listOf(prayer to mosque))
    }

    private fun pushFavoriteChanges(changes: List<Pair<SalahPrayer, Mosque?>>) {
        if (!isLoggedIn) return
        viewModelScope.launch {
            ApiClient.authToken = prefs.getString("api_token", null)
            changes.forEach { (prayer, m) ->
                MosqueApi.setFavorite(prayer, m?.id).onFailure { e ->
                    _state.update { it.copy(message = e.message ?: "Couldn't sync your favourite. It is saved on this phone.") }
                }
            }
        }
    }

    fun addMosque(
        name: String,
        locality: String,
        address: String,
        city: String,
        country: String,
        latitude: Double,
        longitude: Double,
        times: MosqueTimesInput,
        details: MosqueDetailsInput,
        onDone: (AddMosqueResult?) -> Unit
    ) {
        viewModelScope.launch {
            _state.update { it.copy(submitting = true) }
            ApiClient.authToken = prefs.getString("api_token", null)
            MosqueApi.add(name, locality, address, city, country, latitude, longitude, times, details)
                .onSuccess { result ->
                    _state.update { it.copy(submitting = false, message = result.message) }
                    if (!result.duplicate) onContributed()
                    loadNearby()
                    onDone(result)
                }
                .onFailure { e ->
                    _state.update { it.copy(submitting = false, message = e.message ?: "Couldn't add the mosque.") }
                    onDone(null)
                }
        }
    }

    fun updateTimes(mosque: Mosque, times: MosqueTimesInput, onDone: (Boolean) -> Unit) {
        viewModelScope.launch {
            _state.update { it.copy(submitting = true) }
            ApiClient.authToken = prefs.getString("api_token", null)
            MosqueApi.updateTimes(mosque.id, times)
                .onSuccess { (updated, message) ->
                    replaceInList(updated.copy(distanceKm = mosque.distanceKm))
                    _state.update { it.copy(submitting = false, message = message) }
                    onContributed()
                    onDone(true)
                }
                .onFailure { e ->
                    _state.update { it.copy(submitting = false, message = e.message ?: "Couldn't save the times.") }
                    onDone(false)
                }
        }
    }

    fun updateDetails(mosque: Mosque, details: MosqueDetailsInput, onDone: (Boolean) -> Unit) {
        viewModelScope.launch {
            _state.update { it.copy(submitting = true) }
            ApiClient.authToken = prefs.getString("api_token", null)
            MosqueApi.updateDetails(mosque.id, details)
                .onSuccess { (updated, message) ->
                    replaceInList(updated.copy(distanceKm = mosque.distanceKm))
                    _state.update { it.copy(submitting = false, message = message) }
                    onContributed()
                    onDone(true)
                }
                .onFailure { e ->
                    _state.update { it.copy(submitting = false, message = e.message ?: "Couldn't save the details.") }
                    onDone(false)
                }
        }
    }

    fun confirmTimes(mosque: Mosque) {
        viewModelScope.launch {
            _state.update { it.copy(busyMosqueId = mosque.id) }
            ApiClient.authToken = prefs.getString("api_token", null)
            MosqueApi.confirmTimes(mosque.id)
                .onSuccess { (updated, message) ->
                    replaceInList(updated.copy(distanceKm = mosque.distanceKm))
                    _state.update { it.copy(busyMosqueId = null, message = message) }
                    loadSummary()
                }
                .onFailure { e -> _state.update { it.copy(busyMosqueId = null, message = e.message) } }
        }
    }

    fun report(mosque: Mosque, reason: String, note: String) {
        viewModelScope.launch {
            ApiClient.authToken = prefs.getString("api_token", null)
            MosqueApi.report(mosque.id, reason, note)
                .onSuccess { message -> _state.update { it.copy(message = message) } }
                .onFailure { e -> _state.update { it.copy(message = e.message) } }
        }
    }

    fun loadSummary() {
        if (!isLoggedIn) return
        viewModelScope.launch {
            ApiClient.authToken = prefs.getString("api_token", null)
            MosqueApi.contributionSummary().onSuccess { s -> _state.update { it.copy(summary = s) } }
        }
    }

    fun updateSettings(settings: SalahSettings) {
        SalahStore.setSettings(settings.copy(chosenByUser = true))
        PrayerReminderScheduler.reschedule(getApplication())
    }

    fun updateReminders(reminders: SalahReminders) {
        SalahStore.setReminders(reminders)
        PrayerReminderScheduler.reschedule(getApplication())
        val message = when {
            !reminders.enabled -> "Salah notifications turned off."
            reminders.prayers.isEmpty() -> "Choose at least one salah to be notified for."
            location.value == null -> "Notifications on. Share your location so we can work out the times."
            else -> null
        }
        message?.let { m -> _state.update { it.copy(message = m) } }
    }

    fun showMessage(message: String) {
        _state.update { it.copy(message = message) }
    }

    fun consumeMessage() {
        _state.update { it.copy(message = null) }
    }

    private fun replaceInList(updated: Mosque) {
        _state.update { s -> s.copy(nearby = s.nearby.map { if (it.id == updated.id) updated else it }) }
        SalahStore.updateCachedMosque(updated)
    }

    /** Adding or updating a mosque ticks today's "contribute" habit on the server. */
    private fun onContributed() {
        DailyEngagementStore.markLocal("contribute")
        EngagementBus.requestRefresh()
        loadSummary()
    }
}

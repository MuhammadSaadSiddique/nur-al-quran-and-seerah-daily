@file:OptIn(ExperimentalMaterial3Api::class, ExperimentalLayoutApi::class)

package com.asloobulhayat.eternalecho.ui.salah

import android.app.Application
import android.content.Intent
import android.net.Uri
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.FlowRow
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.ChevronRight
import androidx.compose.material.icons.filled.Directions
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.MoreVert
import androidx.compose.material.icons.filled.MyLocation
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.Tune
import androidx.compose.material.icons.filled.Verified
import androidx.compose.material.icons.outlined.StarOutline
import androidx.compose.material3.AssistChip
import androidx.compose.material3.AssistChipDefaults
import androidx.compose.material3.Checkbox
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExtendedFloatingActionButton
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalSoftwareKeyboardController
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.compose.material.icons.filled.AcUnit
import androidx.compose.material.icons.filled.Accessible
import androidx.compose.material.icons.filled.Chair
import androidx.compose.material.icons.filled.LocalParking
import androidx.compose.material.icons.filled.WaterDrop
import androidx.compose.material.icons.filled.Wc
import com.asloobulhayat.eternalecho.data.AsrMethod
import com.asloobulhayat.eternalecho.data.Mosque
import com.asloobulhayat.eternalecho.data.MosqueFacility
import com.asloobulhayat.eternalecho.data.MosqueFilter
import com.asloobulhayat.eternalecho.data.MosqueSect
import com.asloobulhayat.eternalecho.data.OSM_COPYRIGHT_URL
import com.asloobulhayat.eternalecho.data.PlaceType
import com.asloobulhayat.eternalecho.data.khutbahLanguageLabel
import com.asloobulhayat.eternalecho.data.parseClockTime
import com.asloobulhayat.eternalecho.data.PrayerTimesCalculator
import com.asloobulhayat.eternalecho.data.PrayerTimesDay
import com.asloobulhayat.eternalecho.data.SalahPrayer
import com.asloobulhayat.eternalecho.data.SalahStore
import com.asloobulhayat.eternalecho.data.SavedLocation
import com.asloobulhayat.eternalecho.data.minutesUntil
import com.asloobulhayat.eternalecho.data.upcomingSalah
import com.asloobulhayat.eternalecho.ui.components.EchoCard
import com.asloobulhayat.eternalecho.ui.components.EchoChip
import com.asloobulhayat.eternalecho.ui.components.EchoPrimaryButton
import com.asloobulhayat.eternalecho.ui.components.EchoScreenBackground
import com.asloobulhayat.eternalecho.ui.components.EchoSecondaryButton
import com.asloobulhayat.eternalecho.ui.components.EchoSectionLabel
import com.asloobulhayat.eternalecho.ui.components.EchoTopBar
import com.asloobulhayat.eternalecho.util.LocationHelper
import kotlinx.coroutines.delay
import java.util.Calendar
import java.util.Locale

private val RADIUS_OPTIONS = listOf(2.0, 5.0, 10.0, 25.0)

@Composable
fun SalahScreen(
    onBackClick: () -> Unit,
    onAuthClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    val app = context.applicationContext as Application
    val vm: SalahViewModel = viewModel(key = "salah") { SalahViewModel(app) }

    val state by vm.state.collectAsState()
    val location by vm.location.collectAsState()
    val settings by vm.settings.collectAsState()
    val favorites by vm.favorites.collectAsState()
    val effective = SalahStore.effectiveSettings(settings, location)
    val is24 = rememberIs24Hour()

    var now by remember { mutableStateOf(Calendar.getInstance()) }
    LaunchedEffect(Unit) {
        while (true) {
            now = Calendar.getInstance()
            delay(30_000)
        }
    }
    val dayKey = now.get(Calendar.YEAR) * 1000 + now.get(Calendar.DAY_OF_YEAR)
    val today: PrayerTimesDay? = remember(location?.latitude, location?.longitude, effective, dayKey) {
        location?.let {
            PrayerTimesCalculator.calculate(now, it.latitude, it.longitude, effective.method, effective.asrMethod)
        }
    }
    val nowMinutes = now.get(Calendar.HOUR_OF_DAY) * 60 + now.get(Calendar.MINUTE)
    val isFriday = now.get(Calendar.DAY_OF_WEEK) == Calendar.FRIDAY

    /** Today's adhan times as a particular mosque would time Asr. */
    val adhanFor: (AsrMethod?) -> PrayerTimesDay? = { asr ->
        if (asr == null || asr == effective.asrMethod) today
        else location?.let { PrayerTimesCalculator.calculate(now, it.latitude, it.longitude, effective.method, asr) }
    }

    val permissionLauncher = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) {
        // Without a grant this shows the "allow location access" hint instead.
        vm.refreshLocation()
    }
    fun useMyLocation() {
        if (LocationHelper.hasPermission(context)) vm.refreshLocation()
        else permissionLauncher.launch(LocationHelper.PERMISSIONS)
    }

    LaunchedEffect(Unit) { vm.start() }

    val snackbar = remember { SnackbarHostState() }
    LaunchedEffect(state.message) {
        state.message?.let {
            snackbar.showSnackbar(it)
            vm.consumeMessage()
        }
    }

    var pickFor by remember { mutableStateOf<SalahPrayer?>(null) }
    var favoriteSheetFor by remember { mutableStateOf<Mosque?>(null) }
    var editTimesFor by remember { mutableStateOf<Mosque?>(null) }
    var editDetailsFor by remember { mutableStateOf<Mosque?>(null) }
    var reportFor by remember { mutableStateOf<Mosque?>(null) }
    var showAdd by remember { mutableStateOf(false) }
    var showSettings by remember { mutableStateOf(false) }

    fun requireLogin(action: () -> Unit) {
        if (vm.isLoggedIn) action() else onAuthClick()
    }

    Scaffold(
        modifier = modifier.fillMaxSize(),
        containerColor = Color.Transparent,
        topBar = {
            EchoTopBar(
                title = "Salah Times",
                onBackClick = onBackClick,
                navigationIcon = Icons.AutoMirrored.Filled.ArrowBack,
                actions = {
                    IconButton(onClick = { useMyLocation() }) {
                        Icon(Icons.Default.MyLocation, contentDescription = "Update my location")
                    }
                    IconButton(onClick = { showSettings = true }) {
                        Icon(Icons.Default.Tune, contentDescription = "Calculation settings")
                    }
                }
            )
        },
        snackbarHost = { SnackbarHost(snackbar) },
        floatingActionButton = {
            ExtendedFloatingActionButton(
                onClick = { requireLogin { showAdd = true } },
                icon = { Icon(Icons.Default.Add, contentDescription = null) },
                text = { Text("Add mosque") }
            )
        }
    ) { padding ->
        EchoScreenBackground(modifier = Modifier.padding(padding)) {
            LazyColumn(
                contentPadding = PaddingValues(start = 16.dp, end = 16.dp, top = 12.dp, bottom = 96.dp),
                verticalArrangement = Arrangement.spacedBy(14.dp),
                modifier = Modifier.fillMaxSize()
            ) {
                item(key = "next") {
                    NextPrayerCard(
                        location = location,
                        today = today,
                        nowMinutes = nowMinutes,
                        isFriday = isFriday,
                        favorites = favorites,
                        methodLabel = "${effective.method.label} · Asr ${effective.asrMethod.name.lowercase().replaceFirstChar { it.uppercase() }}",
                        locating = state.locating,
                        locationError = state.locationError,
                        is24 = is24,
                        onUseLocation = { useMyLocation() }
                    )
                }

                item(key = "mine_label") { EchoSectionLabel("Your mosque for each salah") }
                item(key = "mine") {
                    MySalahCard(
                        today = today,
                        favorites = favorites,
                        nowMinutes = nowMinutes,
                        isFriday = isFriday,
                        userAsr = effective.asrMethod,
                        is24 = is24,
                        onRowClick = { pickFor = it }
                    )
                }

                item(key = "sadaqah") {
                    SadaqahJariyahCard(
                        isLoggedIn = vm.isLoggedIn,
                        summary = state.summary,
                        onAdd = { requireLogin { showAdd = true } }
                    )
                }

                item(key = "nearby_header") {
                    NearbyHeader(
                        query = state.searchQuery,
                        radiusKm = state.radiusKm,
                        hasLocation = location != null,
                        filters = state.filters,
                        sectFilter = state.sectFilter,
                        onQueryChange = vm::onSearchChange,
                        onSearch = vm::loadNearby,
                        onRadius = vm::setRadius,
                        onToggleFilter = vm::toggleFilter,
                        onSectFilter = vm::setSectFilter
                    )
                }

                when {
                    state.loadingNearby && state.nearby.isEmpty() -> item(key = "loading") {
                        Box(Modifier
                            .fillMaxWidth()
                            .padding(24.dp), contentAlignment = Alignment.Center) {
                            CircularProgressIndicator()
                        }
                    }
                    state.nearbyError != null && state.nearby.isEmpty() -> item(key = "error") {
                        EchoCard {
                            Text(state.nearbyError ?: "", color = MaterialTheme.colorScheme.error)
                            TextButton(onClick = vm::loadNearby) { Text("Try again") }
                        }
                    }
                    location == null && state.searchQuery.isBlank() -> item(key = "need_location") {
                        EchoCard {
                            Text("Share your location to see mosques around you, or search by name or area above.")
                            Spacer(Modifier.height(10.dp))
                            EchoSecondaryButton(text = "Use my location", onClick = { useMyLocation() })
                        }
                    }
                    state.hasSearched && state.nearby.isEmpty() -> item(key = "empty") {
                        if (state.filters.isNotEmpty() || state.sectFilter != null) {
                            EchoCard {
                                Text("No mosques nearby match these filters yet.", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
                                Spacer(Modifier.height(4.dp))
                                Text(
                                    "Many mosques don't have their facilities filled in yet. If you know one, add its details for others.",
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                                TextButton(onClick = vm::clearFilters) { Text("Clear filters") }
                            }
                        } else {
                            EmptyNearbyCard(
                                searching = state.searchQuery.isNotBlank(),
                                radiusKm = state.radiusKm,
                                onAdd = { requireLogin { showAdd = true } },
                                onWiden = { vm.setRadius(25.0) }
                            )
                        }
                    }
                    else -> items(state.nearby, key = { "m_${it.id}" }) { mosque ->
                        MosqueCard(
                            mosque = mosque,
                            today = today,
                            favoriteFor = favorites.filterValues { it.id == mosque.id }.keys,
                            busy = state.busyMosqueId == mosque.id,
                            is24 = is24,
                            onFavorite = { favoriteSheetFor = mosque },
                            onConfirm = { requireLogin { vm.confirmTimes(mosque) } },
                            onEdit = { requireLogin { editTimesFor = mosque } },
                            onEditDetails = { requireLogin { editDetailsFor = mosque } },
                            onReport = { requireLogin { reportFor = mosque } },
                            onDirections = { openDirections(context, mosque) }
                        )
                    }
                }

                // ODbL: credit OpenStreetMap wherever its data is shown.
                if (state.nearby.any { it.fromOpenStreetMap }) {
                    item(key = "osm_attribution") {
                        Text(
                            "Some mosque locations © OpenStreetMap contributors (openstreetmap.org/copyright)",
                            style = MaterialTheme.typography.labelSmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable(role = Role.Button, onClickLabel = "Open OpenStreetMap copyright page") {
                                    try {
                                        context.startActivity(
                                            Intent(Intent.ACTION_VIEW, Uri.parse(OSM_COPYRIGHT_URL)).addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                                        )
                                    } catch (_: Exception) {
                                    }
                                }
                                .padding(vertical = 8.dp)
                        )
                    }
                }
            }
        }
    }

    pickFor?.let { prayer ->
        ChooseMosqueSheet(
            prayer = prayer,
            current = favorites[prayer],
            candidates = state.nearby,
            today = today,
            is24 = is24,
            onDismiss = { pickFor = null },
            onChoose = {
                vm.chooseFavorite(prayer, it)
                pickFor = null
            },
            onClear = {
                vm.clearFavorite(prayer)
                pickFor = null
            },
            onAdd = {
                pickFor = null
                requireLogin { showAdd = true }
            }
        )
    }

    favoriteSheetFor?.let { mosque ->
        FavoritePrayersSheet(
            mosque = mosque,
            today = today,
            currentlyHere = favorites.filterValues { it.id == mosque.id }.keys,
            otherMosques = favorites.filterValues { it.id != mosque.id },
            is24 = is24,
            onDismiss = { favoriteSheetFor = null },
            onSave = { prayers ->
                vm.setFavoritePrayers(mosque, prayers)
                favoriteSheetFor = null
            }
        )
    }

    editDetailsFor?.let { mosque ->
        EditDetailsSheet(
            mosque = mosque,
            sunrise = today?.sunrise,
            submitting = state.submitting,
            onDismiss = { editDetailsFor = null },
            onSubmit = { details -> vm.updateDetails(mosque, details) { ok -> if (ok) editDetailsFor = null } }
        )
    }

    editTimesFor?.let { mosque ->
        UpdateTimesSheet(
            mosque = mosque,
            adhan = adhanFor(mosque.asrMethod),
            submitting = state.submitting,
            onDismiss = { editTimesFor = null },
            onSubmit = { input -> vm.updateTimes(mosque, input) { ok -> if (ok) editTimesFor = null } }
        )
    }

    reportFor?.let { mosque ->
        ReportMosqueDialog(
            mosque = mosque,
            onDismiss = { reportFor = null },
            onSubmit = { reason, note ->
                vm.report(mosque, reason, note)
                reportFor = null
            }
        )
    }

    if (showAdd) {
        AddMosqueSheet(
            adhanFor = adhanFor,
            submitting = state.submitting,
            onDismiss = { showAdd = false },
            onSubmit = { name, locality, address, city, country, lat, lng, times, details ->
                vm.addMosque(name, locality, address, city, country, lat, lng, times, details) { result ->
                    if (result != null) {
                        showAdd = false
                        // Already listed: take them straight to updating that mosque instead.
                        if (result.duplicate) editTimesFor = result.mosque
                    }
                }
            }
        )
    }

    if (showSettings) {
        SalahSettingsSheet(
            current = effective,
            followingCountry = !settings.chosenByUser && !location?.countryCode.isNullOrBlank(),
            onDismiss = { showSettings = false },
            onSave = {
                vm.updateSettings(it)
                showSettings = false
            }
        )
    }
}

@Composable
private fun NextPrayerCard(
    location: SavedLocation?,
    today: PrayerTimesDay?,
    nowMinutes: Int,
    isFriday: Boolean,
    favorites: Map<SalahPrayer, Mosque>,
    methodLabel: String,
    locating: Boolean,
    locationError: String?,
    is24: Boolean,
    onUseLocation: () -> Unit
) {
    EchoCard(highlighted = true) {
        if (location == null || today == null) {
            Text("Salah times near you", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
            Spacer(Modifier.height(6.dp))
            Text(
                "Share your location to see today's adhan times and the mosques closest to you. Your location stays on this phone; only the mosque search uses it.",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            locationError?.let {
                Spacer(Modifier.height(6.dp))
                Text(it, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.error)
            }
            Spacer(Modifier.height(12.dp))
            EchoPrimaryButton(
                text = if (locating) "Finding you…" else "Use my location",
                enabled = !locating,
                onClick = onUseLocation
            )
            return@EchoCard
        }

        val next = upcomingSalah(today, nowMinutes, isFriday, favorites)
        val prayer = next.prayer
        val mosque = next.mosque
        val jamaah = next.jamaah
        val adhanAhead = next.adhan > nowMinutes || prayer == SalahPrayer.FAJR && nowMinutes > today.isha

        Row(verticalAlignment = Alignment.CenterVertically) {
            Icon(Icons.Default.LocationOn, contentDescription = null, tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(16.dp))
            Text(
                location.label.ifBlank { String.format(Locale.US, "%.3f, %.3f", location.latitude, location.longitude) },
                style = MaterialTheme.typography.labelMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier
                    .padding(start = 4.dp)
                    .weight(1f),
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )
            if (locating) CircularProgressIndicator(modifier = Modifier.size(14.dp), strokeWidth = 2.dp)
        }
        Spacer(Modifier.height(8.dp))
        Text("Next: ${prayer.label}", style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold)
        Text(
            if (jamaah != null) "Jama'ah in ${formatCountdown(minutesUntil(nowMinutes, jamaah))}"
            else "Adhan in ${formatCountdown(minutesUntil(nowMinutes, next.adhan))}",
            style = MaterialTheme.typography.titleMedium,
            color = MaterialTheme.colorScheme.primary,
            fontWeight = FontWeight.SemiBold
        )
        Spacer(Modifier.height(6.dp))
        Text(
            (if (adhanAhead) "Adhan " else "Adhan was ") + displayTime(next.adhan, is24),
            style = MaterialTheme.typography.bodyMedium
        )
        if (mosque != null) {
            Text(
                if (jamaah != null) "Jama'ah ${displayTime(jamaah, is24)} at ${mosque.name}" else "${mosque.name} · jama'ah time not added yet",
                style = MaterialTheme.typography.bodyMedium,
                fontWeight = FontWeight.SemiBold
            )
        } else {
            Text(
                "Choose a mosque for ${prayer.label} below to see its jama'ah time.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        Spacer(Modifier.height(6.dp))
        Text(methodLabel, style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
    }
}

@Composable
private fun MySalahCard(
    today: PrayerTimesDay?,
    favorites: Map<SalahPrayer, Mosque>,
    nowMinutes: Int,
    isFriday: Boolean,
    userAsr: AsrMethod,
    is24: Boolean,
    onRowClick: (SalahPrayer) -> Unit
) {
    val next = today?.let { upcomingSalah(it, nowMinutes, isFriday, favorites).prayer }
    EchoCard(contentPadding = PaddingValues(vertical = 6.dp)) {
        val rows = listOf(SalahPrayer.FAJR, null, SalahPrayer.DHUHR, SalahPrayer.ASR, SalahPrayer.MAGHRIB, SalahPrayer.ISHA, SalahPrayer.JUMUAH)
        rows.forEachIndexed { index, prayer ->
            if (index > 0) HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.4f), modifier = Modifier.padding(horizontal = 16.dp))
            if (prayer == null) {
                // Sunrise marks the end of Fajr time; no jama'ah.
                Row(Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp)) {
                    Text("Sunrise", style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.weight(1f))
                    Text(today?.let { displayTime(it.sunrise, is24) } ?: "—", style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
            } else {
                val mosque = favorites[prayer]
                // A Hanafi worshipper at a Standard-Asr mosque (or the reverse) should know why times differ.
                val asrNote = mosque?.asrMethod
                    ?.takeIf { prayer == SalahPrayer.ASR && it != userAsr }
                    ?.let { if (it == AsrMethod.HANAFI) "Mosque follows Hanafi Asr" else "Mosque follows Standard Asr" }
                val jumuahNote = mosque?.jumuahSecondTime
                    ?.takeIf { prayer == SalahPrayer.JUMUAH }
                    ?.let { parseClockTime(it) }
                    ?.let { "Also ${displayTime(it, is24)}" }
                SalahRow(
                    prayer = prayer,
                    adhan = today?.startOf(prayer),
                    mosque = mosque,
                    today = today,
                    isNext = prayer == next,
                    note = asrNote ?: jumuahNote,
                    is24 = is24,
                    onClick = { onRowClick(prayer) }
                )
            }
        }
    }
}

@Composable
private fun SalahRow(
    prayer: SalahPrayer,
    adhan: Int?,
    mosque: Mosque?,
    today: PrayerTimesDay?,
    isNext: Boolean,
    note: String?,
    is24: Boolean,
    onClick: () -> Unit
) {
    val scheme = MaterialTheme.colorScheme
    val jamaah = mosque?.jamaahMinutes(prayer, today)
    Row(
        verticalAlignment = Alignment.CenterVertically,
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClickLabel = "Choose mosque for ${prayer.label}", role = Role.Button, onClick = onClick)
            .padding(horizontal = 16.dp, vertical = 10.dp)
    ) {
        Column(modifier = Modifier.width(92.dp)) {
            Text(
                prayer.label,
                fontWeight = if (isNext) FontWeight.ExtraBold else FontWeight.SemiBold,
                color = if (isNext) scheme.primary else scheme.onSurface,
                style = MaterialTheme.typography.bodyLarge
            )
            Text(
                when {
                    prayer == SalahPrayer.JUMUAH -> "Fridays"
                    adhan != null -> displayTime(adhan, is24)
                    else -> "—"
                },
                style = MaterialTheme.typography.bodySmall,
                color = scheme.onSurfaceVariant
            )
        }
        Column(modifier = Modifier.weight(1f)) {
            if (mosque == null) {
                Text("Choose a mosque", style = MaterialTheme.typography.bodyMedium, color = scheme.primary)
            } else {
                Text(
                    jamaah?.let { "Jama'ah ${displayTime(it, is24)}" } ?: "Jama'ah time unknown",
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.SemiBold,
                    color = if (jamaah == null) scheme.onSurfaceVariant else scheme.onSurface
                )
                Text(
                    mosque.name + (mosque.distanceKm?.let { " · ${formatDistance(it)}" } ?: ""),
                    style = MaterialTheme.typography.bodySmall,
                    color = scheme.onSurfaceVariant,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
                note?.let {
                    Text(it, style = MaterialTheme.typography.labelSmall, color = scheme.tertiary)
                }
            }
        }
        Icon(Icons.Default.ChevronRight, contentDescription = null, tint = scheme.onSurfaceVariant)
    }
}

@Composable
private fun SadaqahJariyahCard(
    isLoggedIn: Boolean,
    summary: com.asloobulhayat.eternalecho.data.MosqueContributionSummary?,
    onAdd: () -> Unit
) {
    EchoCard {
        Text("Sadaqah Jariyah", style = MaterialTheme.typography.labelLarge, color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.Bold)
        Text(
            "Help your community reach the jama'ah",
            style = MaterialTheme.typography.titleMedium,
            fontWeight = FontWeight.Bold,
            modifier = Modifier.padding(top = 2.dp)
        )
        Spacer(Modifier.height(6.dp))
        Text(
            "Add your mosque and keep its times right. Every worshipper who prays in jama'ah because of what you shared is, in sha Allah, continuing reward for you.",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        Spacer(Modifier.height(10.dp))
        Surface(
            shape = RoundedCornerShape(12.dp),
            color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.35f),
            modifier = Modifier.fillMaxWidth()
        ) {
            Column(Modifier.padding(12.dp)) {
                Text(
                    "“Whoever guides someone to goodness will have a reward like the one who does it.”",
                    style = MaterialTheme.typography.bodyMedium,
                    fontStyle = FontStyle.Italic
                )
                Text(
                    "Sahih Muslim 1893",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.padding(top = 4.dp)
                )
            }
        }
        if (isLoggedIn && summary != null && (summary.mosquesHelped > 0 || summary.timesUpdated > 0)) {
            Spacer(Modifier.height(12.dp))
            Row(horizontalArrangement = Arrangement.SpaceEvenly, modifier = Modifier.fillMaxWidth()) {
                SummaryStat(summary.mosquesHelped, "mosques helped")
                SummaryStat(summary.timesUpdated + summary.mosquesAdded, "updates")
                SummaryStat(summary.worshippersBenefiting, "worshippers use them")
            }
        }
        Spacer(Modifier.height(12.dp))
        EchoPrimaryButton(text = if (isLoggedIn) "Add a mosque" else "Sign in to contribute", onClick = onAdd)
    }
}

@Composable
private fun SummaryStat(value: Int, label: String) {
    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        Text(value.toString(), style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
        Text(label, style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
    }
}

@Composable
private fun NearbyHeader(
    query: String,
    radiusKm: Double,
    hasLocation: Boolean,
    filters: Set<MosqueFilter>,
    sectFilter: MosqueSect?,
    onQueryChange: (String) -> Unit,
    onSearch: () -> Unit,
    onRadius: (Double) -> Unit,
    onToggleFilter: (MosqueFilter) -> Unit,
    onSectFilter: (MosqueSect?) -> Unit
) {
    val keyboard = LocalSoftwareKeyboardController.current
    var sectMenuOpen by remember { mutableStateOf(false) }
    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        EchoSectionLabel("Mosques near you")
        OutlinedTextField(
            value = query,
            onValueChange = onQueryChange,
            placeholder = { Text("Search by mosque name or area") },
            leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
            singleLine = true,
            shape = RoundedCornerShape(14.dp),
            keyboardOptions = KeyboardOptions(imeAction = ImeAction.Search),
            keyboardActions = KeyboardActions(onSearch = {
                keyboard?.hide()
                onSearch()
            }),
            modifier = Modifier.fillMaxWidth()
        )
        if (hasLocation && query.isBlank()) {
            FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                RADIUS_OPTIONS.forEach { km ->
                    EchoChip("${km.toInt()} km", selected = radiusKm == km, onClick = { onRadius(km) })
                }
            }
        }
        FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
            MosqueFilter.entries.forEach { filter ->
                EchoChip(filter.label, selected = filter in filters, onClick = { onToggleFilter(filter) })
            }
            // Never applied unless the worshipper picks one.
            Box {
                EchoChip(
                    label = sectFilter?.let { "School: ${it.shortLabel}" } ?: "School of thought: any",
                    selected = sectFilter != null,
                    onClick = { sectMenuOpen = true }
                )
                DropdownMenu(expanded = sectMenuOpen, onDismissRequest = { sectMenuOpen = false }) {
                    DropdownMenuItem(text = { Text("Any") }, onClick = {
                        sectMenuOpen = false
                        onSectFilter(null)
                    })
                    MosqueSect.entries.forEach { sect ->
                        DropdownMenuItem(text = { Text("${sect.tradition} · ${sect.label}") }, onClick = {
                            sectMenuOpen = false
                            onSectFilter(sect)
                        })
                    }
                }
            }
        }
    }
}

@Composable
private fun EmptyNearbyCard(searching: Boolean, radiusKm: Double, onAdd: () -> Unit, onWiden: () -> Unit) {
    EchoCard {
        Text(
            if (searching) "No mosque matches that search yet." else "No mosques added within ${radiusKm.toInt()} km yet.",
            style = MaterialTheme.typography.titleSmall,
            fontWeight = FontWeight.Bold
        )
        Spacer(Modifier.height(4.dp))
        Text(
            "Be the first to add one. It takes a minute, and everyone nearby benefits.",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        Spacer(Modifier.height(12.dp))
        EchoPrimaryButton(text = "Add a mosque", onClick = onAdd)
        if (!searching && radiusKm < 25.0) {
            TextButton(onClick = onWiden, modifier = Modifier.align(Alignment.CenterHorizontally)) { Text("Search 25 km instead") }
        }
    }
}

@Composable
private fun MosqueCard(
    mosque: Mosque,
    today: PrayerTimesDay?,
    favoriteFor: Set<SalahPrayer>,
    busy: Boolean,
    is24: Boolean,
    onFavorite: () -> Unit,
    onConfirm: () -> Unit,
    onEdit: () -> Unit,
    onEditDetails: () -> Unit,
    onReport: () -> Unit,
    onDirections: () -> Unit
) {
    val scheme = MaterialTheme.colorScheme
    var menuOpen by remember { mutableStateOf(false) }
    val currentYear = remember { Calendar.getInstance().get(Calendar.YEAR) }

    EchoCard(highlighted = favoriteFor.isNotEmpty()) {
        Row(verticalAlignment = Alignment.Top) {
            Column(Modifier.weight(1f)) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(
                        mosque.name,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis,
                        modifier = Modifier.weight(1f, fill = false)
                    )
                    if (mosque.isVerified) {
                        Icon(
                            Icons.Default.Verified,
                            contentDescription = "Verified mosque",
                            tint = scheme.primary,
                            modifier = Modifier
                                .padding(start = 4.dp)
                                .size(18.dp)
                        )
                    }
                }
                if (mosque.areaLine.isNotBlank()) {
                    Text(mosque.areaLine, style = MaterialTheme.typography.bodySmall, color = scheme.onSurfaceVariant, maxLines = 1, overflow = TextOverflow.Ellipsis)
                }
                val profile = listOfNotNull(
                    "Musalla".takeIf { mosque.placeType == PlaceType.MUSALLA },
                    mosque.sect?.shortLabel,
                    mosque.asrMethod?.let { if (it == AsrMethod.HANAFI) "Hanafi Asr" else "Standard Asr" }
                )
                if (profile.isNotEmpty()) {
                    Text(profile.joinToString(" · "), style = MaterialTheme.typography.labelMedium, color = scheme.onSurfaceVariant)
                }
            }
            mosque.distanceKm?.let {
                Text(formatDistance(it), style = MaterialTheme.typography.labelLarge, color = scheme.primary, fontWeight = FontWeight.Bold)
            }
            Box {
                IconButton(onClick = { menuOpen = true }, modifier = Modifier.size(32.dp)) {
                    Icon(Icons.Default.MoreVert, contentDescription = "More options")
                }
                DropdownMenu(expanded = menuOpen, onDismissRequest = { menuOpen = false }) {
                    DropdownMenuItem(text = { Text("Edit facilities & details") }, onClick = {
                        menuOpen = false
                        onEditDetails()
                    })
                    DropdownMenuItem(text = { Text("Report a problem") }, onClick = {
                        menuOpen = false
                        onReport()
                    })
                }
            }
        }

        Spacer(Modifier.height(10.dp))
        if (mosque.hasAnyTime) {
            FlowRow(horizontalArrangement = Arrangement.spacedBy(6.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                SalahPrayer.entries.forEach { prayer ->
                    val minutes = mosque.jamaahMinutes(prayer, today)
                    val second = mosque.jumuahSecondTime?.let { parseClockTime(it) }?.takeIf { prayer == SalahPrayer.JUMUAH }
                    val label = when {
                        prayer == SalahPrayer.MAGHRIB && mosque.maghribOffsetMinutes != null && minutes == null ->
                            "Maghrib +${mosque.maghribOffsetMinutes}m"
                        minutes != null && second != null -> "${prayer.label} ${displayTime(minutes, is24)} & ${displayTime(second, is24)}"
                        minutes != null -> "${prayer.label} ${displayTime(minutes, is24)}"
                        else -> "${prayer.label} —"
                    }
                    TimePill(label, highlighted = prayer in favoriteFor, dim = minutes == null && !(prayer == SalahPrayer.MAGHRIB && mosque.maghribOffsetMinutes != null))
                }
            }
        } else {
            Text("No jama'ah times yet. Know them? Add them for everyone.", style = MaterialTheme.typography.bodySmall, color = scheme.onSurfaceVariant)
        }

        FacilityBadges(mosque)

        if (mosque.khutbahLanguages.isNotEmpty()) {
            Text(
                "Khutbah: ${mosque.khutbahLanguages.joinToString { khutbahLanguageLabel(it) }}",
                style = MaterialTheme.typography.labelMedium,
                color = scheme.onSurfaceVariant,
                modifier = Modifier.padding(top = 6.dp)
            )
        }

        if (mosque.hasRamadanInfo) {
            val ramadan = buildList {
                mosque.taraweehRakats?.let { add("Taraweeh $it rakats") }
                if (mosque.taraweehKhatm == true) add("full Quran")
                listOf("Eid ul-Fitr" to mosque.eidFitr, "Eid ul-Adha" to mosque.eidAdha).forEach { (label, eid) ->
                    if (eid != null && eid.year >= currentYear) {
                        add("$label ${eid.year}: " + eid.times.mapNotNull(::parseClockTime).joinToString { displayTime(it, is24) })
                    }
                }
            }
            if (ramadan.isNotEmpty()) {
                Text(
                    ramadan.joinToString(" · "),
                    style = MaterialTheme.typography.labelMedium,
                    color = scheme.onSurfaceVariant,
                    modifier = Modifier.padding(top = 4.dp)
                )
            }
        }

        Spacer(Modifier.height(8.dp))
        val meta = buildList {
            mosque.daysSinceUpdate?.let { add(if (it == 0) "Updated today" else "Updated ${it}d ago") }
            if (mosque.confirmationsCount > 0) add("${mosque.confirmationsCount} confirmed")
            if (mosque.regularsCount > 0) add("${mosque.regularsCount} pray here")
            if (mosque.fromOpenStreetMap) add("From OpenStreetMap")
        }
        if (meta.isNotEmpty()) {
            Text(meta.joinToString(" · "), style = MaterialTheme.typography.labelSmall, color = scheme.onSurfaceVariant)
        }
        if (mosque.hasAnyTime && mosque.isStale) {
            Text(
                "Times may be out of date. If you pray here, please confirm or update them.",
                style = MaterialTheme.typography.labelSmall,
                color = scheme.tertiary,
                modifier = Modifier.padding(top = 2.dp)
            )
        }
        if (mosque.sectChangePending) {
            Text(
                "A school-of-thought change is waiting for review.",
                style = MaterialTheme.typography.labelSmall,
                color = scheme.onSurfaceVariant,
                modifier = Modifier.padding(top = 2.dp)
            )
        }
        if (favoriteFor.isNotEmpty()) {
            Text(
                "Your mosque for ${SalahPrayer.entries.filter { it in favoriteFor }.joinToString { it.label }}",
                style = MaterialTheme.typography.labelMedium,
                color = scheme.primary,
                fontWeight = FontWeight.SemiBold,
                modifier = Modifier.padding(top = 4.dp)
            )
        }

        Spacer(Modifier.height(10.dp))
        FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
            ActionChip(
                label = if (favoriteFor.isEmpty()) "I pray here" else "Edit salahs",
                icon = { Icon(if (favoriteFor.isEmpty()) Icons.Outlined.StarOutline else Icons.Default.Star, contentDescription = null, modifier = Modifier.size(18.dp)) },
                onClick = onFavorite
            )
            if (mosque.hasAnyTime) {
                ActionChip(
                    label = if (busy) "Confirming…" else "Times correct",
                    icon = { Icon(Icons.Default.CheckCircle, contentDescription = null, modifier = Modifier.size(18.dp)) },
                    onClick = { if (!busy) onConfirm() }
                )
            }
            ActionChip(
                label = if (mosque.hasAnyTime) "Update" else "Add times",
                icon = { Icon(Icons.Default.Edit, contentDescription = null, modifier = Modifier.size(18.dp)) },
                onClick = onEdit
            )
            ActionChip(
                label = "Details",
                icon = { Icon(Icons.Default.Accessible, contentDescription = null, modifier = Modifier.size(18.dp)) },
                onClick = onEditDetails
            )
            ActionChip(
                label = "Directions",
                icon = { Icon(Icons.Default.Directions, contentDescription = null, modifier = Modifier.size(18.dp)) },
                onClick = onDirections
            )
        }
    }
}

/** Only facilities someone confirmed are shown; unknown and "no" stay quiet on the card. */
@Composable
private fun FacilityBadges(mosque: Mosque) {
    val badges = buildList<Pair<androidx.compose.ui.graphics.vector.ImageVector?, String>> {
        if (mosque.has(MosqueFacility.WHEELCHAIR_ENTRANCE)) add(Icons.Default.Accessible to "Step-free")
        if (mosque.has(MosqueFacility.ACCESSIBLE_WUDU)) add(Icons.Default.Wc to "Accessible wudu")
        if (mosque.has(MosqueFacility.PRAYER_CHAIRS)) add(Icons.Default.Chair to "Chairs")
        if (mosque.hasWomenArea == true) add(null to "Women's area")
        if (mosque.has(MosqueFacility.WOMEN_WUDU)) add(null to "Women's wudu")
        if (mosque.has(MosqueFacility.WUDU_AREA)) add(Icons.Default.WaterDrop to "Wudu")
        if (mosque.has(MosqueFacility.TOILETS)) add(Icons.Default.Wc to "Toilets")
        if (mosque.has(MosqueFacility.CAR_PARKING) || mosque.has(MosqueFacility.BIKE_PARKING)) {
            val kinds = listOfNotNull(
                "car".takeIf { mosque.has(MosqueFacility.CAR_PARKING) },
                "bike".takeIf { mosque.has(MosqueFacility.BIKE_PARKING) }
            )
            add(Icons.Default.LocalParking to "Parking (${kinds.joinToString(" & ")})")
        }
        if (mosque.has(MosqueFacility.AIR_CONDITIONING)) add(Icons.Default.AcUnit to "A/C")
    }
    if (badges.isEmpty()) return

    val scheme = MaterialTheme.colorScheme
    FlowRow(
        horizontalArrangement = Arrangement.spacedBy(6.dp),
        verticalArrangement = Arrangement.spacedBy(6.dp),
        modifier = Modifier.padding(top = 8.dp)
    ) {
        badges.forEach { (icon, label) ->
            Surface(shape = RoundedCornerShape(8.dp), color = scheme.secondaryContainer.copy(alpha = 0.6f)) {
                Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)) {
                    if (icon != null) {
                        Icon(icon, contentDescription = null, modifier = Modifier.size(14.dp), tint = scheme.onSecondaryContainer)
                        Spacer(Modifier.width(4.dp))
                    }
                    Text(label, style = MaterialTheme.typography.labelSmall, color = scheme.onSecondaryContainer)
                }
            }
        }
    }
}

@Composable
private fun ActionChip(label: String, icon: @Composable () -> Unit, onClick: () -> Unit) {
    AssistChip(
        onClick = onClick,
        label = { Text(label) },
        leadingIcon = icon,
        shape = RoundedCornerShape(10.dp),
        colors = AssistChipDefaults.assistChipColors(leadingIconContentColor = MaterialTheme.colorScheme.primary)
    )
}

@Composable
private fun TimePill(label: String, highlighted: Boolean, dim: Boolean) {
    val scheme = MaterialTheme.colorScheme
    Surface(
        shape = RoundedCornerShape(8.dp),
        color = if (highlighted) scheme.primaryContainer else scheme.surfaceVariant.copy(alpha = 0.6f),
        border = if (highlighted) BorderStroke(1.dp, scheme.primary) else null
    ) {
        Text(
            label,
            style = MaterialTheme.typography.labelMedium,
            fontWeight = if (highlighted) FontWeight.Bold else FontWeight.Medium,
            color = when {
                highlighted -> scheme.onPrimaryContainer
                dim -> scheme.onSurfaceVariant.copy(alpha = 0.6f)
                else -> scheme.onSurface
            },
            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
        )
    }
}

/** Pick the mosque for one salah from those loaded nearby. */
@Composable
private fun ChooseMosqueSheet(
    prayer: SalahPrayer,
    current: Mosque?,
    candidates: List<Mosque>,
    today: PrayerTimesDay?,
    is24: Boolean,
    onDismiss: () -> Unit,
    onChoose: (Mosque) -> Unit,
    onClear: () -> Unit,
    onAdd: () -> Unit
) {
    ModalBottomSheet(onDismissRequest = onDismiss) {
        LazyColumn(
            contentPadding = PaddingValues(start = 20.dp, end = 20.dp, bottom = 24.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp),
            modifier = Modifier.navigationBarsPadding()
        ) {
            item {
                Text("Mosque for ${prayer.label}", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                Text(
                    "You can pray each salah at a different mosque, e.g. Fajr near home and Dhuhr near work.",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.padding(top = 4.dp, bottom = 8.dp)
                )
            }
            if (current != null) {
                item {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text("Current: ${current.name}", modifier = Modifier.weight(1f), fontWeight = FontWeight.SemiBold)
                        TextButton(onClick = onClear) { Text("Remove") }
                    }
                }
            }
            if (candidates.isEmpty()) {
                item {
                    Text(
                        "No mosques loaded yet. Use your location or search on the Salah screen, or add your mosque.",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }
            items(candidates, key = { it.id }) { mosque ->
                val minutes = mosque.jamaahMinutes(prayer, today)
                Surface(
                    onClick = { onChoose(mosque) },
                    shape = RoundedCornerShape(14.dp),
                    color = if (mosque.id == current?.id) MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.5f)
                    else MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.45f),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Row(Modifier.padding(14.dp), verticalAlignment = Alignment.CenterVertically) {
                        Column(Modifier.weight(1f)) {
                            Text(mosque.name, fontWeight = FontWeight.SemiBold, maxLines = 1, overflow = TextOverflow.Ellipsis)
                            Text(
                                listOfNotNull(mosque.distanceKm?.let(::formatDistance), mosque.areaLine.ifBlank { null }).joinToString(" · "),
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis
                            )
                        }
                        Text(
                            minutes?.let { displayTime(it, is24) } ?: "time unknown",
                            fontWeight = FontWeight.Bold,
                            color = if (minutes != null) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
            }
            item {
                TextButton(onClick = onAdd, modifier = Modifier.fillMaxWidth()) { Text("My mosque isn't listed — add it") }
            }
        }
    }
}

/** Tick which salahs are prayed at this mosque. */
@Composable
private fun FavoritePrayersSheet(
    mosque: Mosque,
    today: PrayerTimesDay?,
    currentlyHere: Set<SalahPrayer>,
    otherMosques: Map<SalahPrayer, Mosque>,
    is24: Boolean,
    onDismiss: () -> Unit,
    onSave: (Set<SalahPrayer>) -> Unit
) {
    var selected by remember(mosque.id) {
        mutableStateOf(currentlyHere.ifEmpty { SalahPrayer.entries.filter { it !in otherMosques }.toSet() })
    }

    ModalBottomSheet(onDismissRequest = onDismiss) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 20.dp)
                .padding(bottom = 24.dp)
                .navigationBarsPadding()
        ) {
            Text("I pray at ${mosque.name} for…", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
            Spacer(Modifier.height(8.dp))
            SalahPrayer.entries.forEach { prayer ->
                val minutes = mosque.jamaahMinutes(prayer, today)
                val elsewhere = otherMosques[prayer]
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier
                        .fillMaxWidth()
                        .clickable(role = Role.Checkbox) {
                            selected = if (prayer in selected) selected - prayer else selected + prayer
                        }
                        .padding(vertical = 4.dp)
                ) {
                    Checkbox(checked = prayer in selected, onCheckedChange = null)
                    Column(Modifier
                        .weight(1f)
                        .padding(start = 8.dp)) {
                        Text(prayer.label, fontWeight = FontWeight.SemiBold)
                        if (elsewhere != null && prayer in selected) {
                            Text("Replaces ${elsewhere.name}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.tertiary)
                        } else if (elsewhere != null) {
                            Text("Now: ${elsewhere.name}", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    }
                    Text(
                        minutes?.let { displayTime(it, is24) } ?: "—",
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }
            Spacer(Modifier.height(12.dp))
            EchoPrimaryButton(text = "Save", onClick = { onSave(selected) })
        }
    }
}

fun formatCountdown(minutes: Int): String {
    val h = minutes / 60
    val m = minutes % 60
    return when {
        h == 0 -> "$m min"
        m == 0 -> "${h}h"
        else -> "${h}h ${m}m"
    }
}

fun formatDistance(km: Double): String =
    if (km < 1.0) "${(km * 1000).toInt().coerceAtLeast(10) / 10 * 10} m"
    else String.format(Locale.US, "%.1f km", km)

private fun openDirections(context: android.content.Context, mosque: Mosque) {
    val coords = String.format(Locale.US, "%.6f,%.6f", mosque.latitude, mosque.longitude)
    val geo = Uri.parse("geo:$coords?q=$coords(${Uri.encode(mosque.name)})")
    val web = Uri.parse("https://www.google.com/maps/dir/?api=1&destination=$coords")
    try {
        context.startActivity(Intent(Intent.ACTION_VIEW, geo).addFlags(Intent.FLAG_ACTIVITY_NEW_TASK))
    } catch (_: Exception) {
        try {
            context.startActivity(Intent(Intent.ACTION_VIEW, web).addFlags(Intent.FLAG_ACTIVITY_NEW_TASK))
        } catch (_: Exception) {
        }
    }
}

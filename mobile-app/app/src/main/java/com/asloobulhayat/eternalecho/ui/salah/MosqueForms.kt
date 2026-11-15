@file:OptIn(ExperimentalMaterial3Api::class, ExperimentalLayoutApi::class)

package com.asloobulhayat.eternalecho.ui.salah

import android.text.format.DateFormat
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.FlowRow
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.selection.selectable
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.ExpandLess
import androidx.compose.material.icons.filled.ExpandMore
import androidx.compose.material.icons.filled.MyLocation
import androidx.compose.material.icons.filled.Remove
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilledTonalIconButton
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.InputChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.RadioButton
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TimePicker
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.material3.rememberTimePickerState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.Stable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateListOf
import androidx.compose.runtime.mutableStateMapOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardCapitalization
import androidx.compose.ui.unit.dp
import com.asloobulhayat.eternalecho.data.AsrMethod
import com.asloobulhayat.eternalecho.data.CalculationMethod
import com.asloobulhayat.eternalecho.data.FacilityGroup
import com.asloobulhayat.eternalecho.data.KHUTBAH_LANGUAGES
import com.asloobulhayat.eternalecho.data.MAX_EID_TIMES
import com.asloobulhayat.eternalecho.data.MAX_KHUTBAH_LANGUAGES
import com.asloobulhayat.eternalecho.data.Mosque
import com.asloobulhayat.eternalecho.data.MosqueDetailsInput
import com.asloobulhayat.eternalecho.data.MosqueFacility
import com.asloobulhayat.eternalecho.data.MosqueSect
import com.asloobulhayat.eternalecho.data.MosqueTimesInput
import com.asloobulhayat.eternalecho.data.PlaceType
import com.asloobulhayat.eternalecho.data.PrayerTimesDay
import com.asloobulhayat.eternalecho.data.SalahPrayer
import com.asloobulhayat.eternalecho.data.SalahSettings
import com.asloobulhayat.eternalecho.data.defaultEidYear
import com.asloobulhayat.eternalecho.data.formatClockTime
import com.asloobulhayat.eternalecho.data.parseClockTime
import com.asloobulhayat.eternalecho.ui.components.EchoChip
import com.asloobulhayat.eternalecho.ui.components.EchoPrimaryButton
import com.asloobulhayat.eternalecho.ui.components.EchoSectionLabel
import com.asloobulhayat.eternalecho.util.LocationHelper
import com.asloobulhayat.eternalecho.util.PlaceInfo
import kotlinx.coroutines.launch

// ---------------------------------------------------------------------------
// Jama'ah times
// ---------------------------------------------------------------------------

/** Editable jama'ah times shared by the add and update forms. */
@Stable
class TimesDraft(initial: Mosque?) {
    val times = mutableStateMapOf<SalahPrayer, Int>().apply {
        initial?.times?.forEach { (prayer, raw) -> parseClockTime(raw)?.let { put(prayer, it) } }
    }
    var jumuahSecond by mutableStateOf(initial?.jumuahSecondTime?.let(::parseClockTime))
    var maghribUsesOffset by mutableStateOf(initial?.maghribOffsetMinutes != null)
    var maghribOffset by mutableIntStateOf(initial?.maghribOffsetMinutes ?: 5)

    fun toInput(): MosqueTimesInput = MosqueTimesInput(
        times = SalahPrayer.entries.associateWith { prayer ->
            if (prayer == SalahPrayer.MAGHRIB && maghribUsesOffset) null else times[prayer]?.let(::formatClockTime)
        },
        jumuahSecondTime = jumuahSecond?.let(::formatClockTime),
        maghribOffsetMinutes = if (maghribUsesOffset) maghribOffset else null
    )
}

@Composable
fun rememberIs24Hour(): Boolean {
    val context = LocalContext.current
    return remember { DateFormat.is24HourFormat(context) }
}

/** "4:30 PM" or "16:30" following the phone's clock setting. */
fun displayTime(minutes: Int, is24Hour: Boolean): String {
    if (minutes < 0) return "—"
    val m = ((minutes % 1440) + 1440) % 1440
    val h = m / 60
    val min = m % 60
    if (is24Hour) return String.format(java.util.Locale.US, "%02d:%02d", h, min)
    val h12 = if (h % 12 == 0) 12 else h % 12
    return String.format(java.util.Locale.US, "%d:%02d %s", h12, min, if (h < 12) "AM" else "PM")
}

private const val JUMUAH_SECOND_KEY = "jumuah2"

@Composable
fun TimesEditor(draft: TimesDraft, adhan: PrayerTimesDay?, modifier: Modifier = Modifier) {
    val is24 = rememberIs24Hour()
    var pickerFor by remember { mutableStateOf<String?>(null) }

    Column(modifier = modifier.fillMaxWidth(), verticalArrangement = Arrangement.spacedBy(10.dp)) {
        SalahPrayer.entries.forEach { prayer ->
            val adhanStart = adhan?.startOf(prayer)
            Column {
                val hint = when {
                    prayer == SalahPrayer.JUMUAH -> "Friday khutbah / salah"
                    adhanStart != null && adhanStart >= 0 -> "Adhan today ${displayTime(adhanStart, is24)}"
                    else -> ""
                }
                if (prayer == SalahPrayer.MAGHRIB && draft.maghribUsesOffset) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        RowLabel(prayer.label, hint, Modifier.weight(1f))
                        OffsetStepper(minutes = draft.maghribOffset, onChange = { draft.maghribOffset = it.coerceIn(0, 90) })
                    }
                } else {
                    TimeRow(
                        label = prayer.label,
                        hint = hint,
                        value = draft.times[prayer],
                        is24 = is24,
                        onPick = { pickerFor = prayer.key },
                        onClear = { draft.times.remove(prayer) }
                    )
                }

                if (prayer == SalahPrayer.MAGHRIB) {
                    Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.padding(top = 6.dp)) {
                        EchoChip("Fixed time", selected = !draft.maghribUsesOffset, onClick = { draft.maghribUsesOffset = false })
                        EchoChip("Minutes after adhan", selected = draft.maghribUsesOffset, onClick = { draft.maghribUsesOffset = true })
                    }
                    if (draft.maghribUsesOffset) {
                        HintText("Best for Maghrib: it follows sunset, so it stays right all year.")
                    }
                }

                if (prayer == SalahPrayer.JUMUAH && (draft.times[SalahPrayer.JUMUAH] != null || draft.jumuahSecond != null)) {
                    Spacer(Modifier.height(8.dp))
                    TimeRow(
                        label = "Second Jumu'ah",
                        hint = "If there is a later jama'ah",
                        value = draft.jumuahSecond,
                        is24 = is24,
                        onPick = { pickerFor = JUMUAH_SECOND_KEY },
                        onClear = { draft.jumuahSecond = null }
                    )
                }

                val chosen = draft.times[prayer]
                val looksEarly = chosen != null && adhanStart != null && adhanStart >= 0 &&
                    prayer != SalahPrayer.JUMUAH && chosen < adhanStart &&
                    !(prayer == SalahPrayer.ISHA && chosen < 3 * 60)
                if (looksEarly) {
                    Text(
                        "This is before today's adhan. Please double-check.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.error,
                        modifier = Modifier.padding(top = 2.dp)
                    )
                }
            }
        }
    }

    pickerFor?.let { key ->
        val isSecond = key == JUMUAH_SECOND_KEY
        val prayer = if (isSecond) SalahPrayer.JUMUAH else SalahPrayer.fromKey(key) ?: SalahPrayer.FAJR
        val current = if (isSecond) draft.jumuahSecond else draft.times[prayer]
        val initial = current
            ?: (if (isSecond) draft.times[SalahPrayer.JUMUAH]?.let { it + 45 } else null)
            ?: adhan?.startOf(prayer)?.takeIf { it >= 0 }?.let { roundUpToFive(it + 10) }
            ?: defaultGuess(prayer)
        JamaahTimePickerDialog(
            title = if (isSecond) "Second Jumu'ah" else "${prayer.label} jama'ah",
            initialMinutes = initial % 1440,
            is24Hour = is24,
            onDismiss = { pickerFor = null },
            onConfirm = { minutes ->
                if (isSecond) draft.jumuahSecond = minutes else draft.times[prayer] = minutes
                pickerFor = null
            }
        )
    }
}

@Composable
private fun RowLabel(label: String, hint: String, modifier: Modifier = Modifier) {
    Column(modifier = modifier) {
        Text(label, fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.bodyLarge)
        if (hint.isNotEmpty()) {
            Text(hint, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        }
    }
}

@Composable
private fun TimeRow(
    label: String,
    hint: String,
    value: Int?,
    is24: Boolean,
    onPick: () -> Unit,
    onClear: () -> Unit
) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        RowLabel(label, hint, Modifier.weight(1f))
        OutlinedButton(onClick = onPick, shape = RoundedCornerShape(12.dp)) {
            Text(if (value != null) displayTime(value, is24) else "Set time")
        }
        if (value != null) {
            IconButton(onClick = onClear) {
                Icon(Icons.Default.Close, contentDescription = "Clear $label time")
            }
        } else {
            Spacer(Modifier.width(48.dp))
        }
    }
}

@Composable
private fun HintText(text: String) {
    Text(
        text,
        style = MaterialTheme.typography.bodySmall,
        color = MaterialTheme.colorScheme.onSurfaceVariant,
        modifier = Modifier.padding(top = 4.dp)
    )
}

@Composable
private fun OffsetStepper(minutes: Int, onChange: (Int) -> Unit) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        FilledTonalIconButton(onClick = { onChange(minutes - 1) }, modifier = Modifier.size(36.dp)) {
            Icon(Icons.Default.Remove, contentDescription = "One minute less")
        }
        Text(
            "+$minutes min",
            fontWeight = FontWeight.SemiBold,
            modifier = Modifier.padding(horizontal = 10.dp)
        )
        FilledTonalIconButton(onClick = { onChange(minutes + 1) }, modifier = Modifier.size(36.dp)) {
            Icon(Icons.Default.Add, contentDescription = "One minute more")
        }
    }
}

@Composable
fun JamaahTimePickerDialog(
    title: String,
    initialMinutes: Int,
    is24Hour: Boolean,
    onDismiss: () -> Unit,
    onConfirm: (Int) -> Unit
) {
    val state = rememberTimePickerState(
        initialHour = (initialMinutes / 60).coerceIn(0, 23),
        initialMinute = (initialMinutes % 60).coerceIn(0, 59),
        is24Hour = is24Hour
    )
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(title) },
        text = { TimePicker(state = state) },
        confirmButton = {
            TextButton(onClick = { onConfirm(state.hour * 60 + state.minute) }) { Text("Save") }
        },
        dismissButton = { TextButton(onClick = onDismiss) { Text("Cancel") } }
    )
}

private fun roundUpToFive(minutes: Int): Int = ((minutes + 4) / 5 * 5) % 1440

private fun defaultGuess(prayer: SalahPrayer): Int = when (prayer) {
    SalahPrayer.FAJR -> 5 * 60 + 30
    SalahPrayer.DHUHR -> 13 * 60 + 30
    SalahPrayer.ASR -> 16 * 60 + 30
    SalahPrayer.MAGHRIB -> 18 * 60
    SalahPrayer.ISHA -> 20 * 60
    SalahPrayer.JUMUAH -> 13 * 60 + 30
}

// ---------------------------------------------------------------------------
// Mosque details: place, school of thought, accessibility, facilities, Ramadan
// ---------------------------------------------------------------------------

@Stable
class DetailsDraft(initial: Mosque?) {
    var placeType by mutableStateOf(initial?.placeType ?: PlaceType.MASJID)
    var sect by mutableStateOf(initial?.sect)
    var asrMethod by mutableStateOf(initial?.asrMethod)
    var hasWomenArea by mutableStateOf(initial?.hasWomenArea)

    /** What was stored, so unticking an amenity restores an earlier "no" rather than erasing it. */
    private val originalFacilities: Map<MosqueFacility, Boolean> = initial?.facilities.orEmpty()
    val facilities = mutableStateMapOf<MosqueFacility, Boolean?>().apply {
        initial?.facilities?.forEach { (k, v) -> put(k, v) }
    }
    val languages = mutableStateListOf<String>().apply { addAll(initial?.khutbahLanguages.orEmpty()) }
    var taraweehRakats by mutableStateOf(initial?.taraweehRakats)
    var taraweehKhatm by mutableStateOf(initial?.taraweehKhatm)

    var eidYear by mutableIntStateOf(defaultEidYear())
    val eidFitr = mutableStateListOf<Int>()
    val eidAdha = mutableStateListOf<Int>()

    init {
        // Only carry over Eid times that are still for the coming Eid; older ones are dropped on save.
        val upcoming = defaultEidYear()
        initial?.eidFitr?.takeIf { it.year >= upcoming }?.let { eid ->
            eidYear = eid.year
            eidFitr.addAll(eid.times.mapNotNull(::parseClockTime))
        }
        initial?.eidAdha?.takeIf { it.year >= upcoming }?.let { eid ->
            eidYear = eid.year
            eidAdha.addAll(eid.times.mapNotNull(::parseClockTime))
        }
    }

    fun toggleAmenity(facility: MosqueFacility) {
        facilities[facility] = if (facilities[facility] == true) originalFacilities[facility]?.takeIf { !it } else true
    }

    fun toggleLanguage(code: String) {
        if (code in languages) languages.remove(code) else if (languages.size < MAX_KHUTBAH_LANGUAGES) languages.add(code)
    }

    fun toInput(): MosqueDetailsInput = MosqueDetailsInput(
        placeType = placeType,
        sect = sect,
        asrMethod = asrMethod,
        hasWomenArea = hasWomenArea,
        facilities = MosqueFacility.entries.associateWith { facilities[it] },
        khutbahLanguages = KHUTBAH_LANGUAGES.map { it.first }.filter { it in languages },
        taraweehRakats = taraweehRakats,
        taraweehKhatm = taraweehKhatm,
        eidYear = eidYear,
        eidFitrTimes = eidFitr.sorted().map(::formatClockTime),
        eidAdhaTimes = eidAdha.sorted().map(::formatClockTime)
    )
}

@Composable
fun CollapsibleSection(
    title: String,
    summary: String = "",
    initiallyExpanded: Boolean = false,
    content: @Composable () -> Unit
) {
    var expanded by rememberSaveable(title) { mutableStateOf(initiallyExpanded) }
    Column(Modifier.fillMaxWidth()) {
        Row(
            verticalAlignment = Alignment.CenterVertically,
            modifier = Modifier
                .fillMaxWidth()
                .clickable(role = Role.Button, onClickLabel = if (expanded) "Collapse $title" else "Expand $title") { expanded = !expanded }
                .padding(vertical = 10.dp)
        ) {
            Column(Modifier.weight(1f)) {
                Text(title, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold, color = MaterialTheme.colorScheme.primary)
                if (summary.isNotBlank() && !expanded) {
                    Text(summary, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
            }
            Icon(if (expanded) Icons.Default.ExpandLess else Icons.Default.ExpandMore, contentDescription = null)
        }
        AnimatedVisibility(visible = expanded) {
            Column(verticalArrangement = Arrangement.spacedBy(10.dp), modifier = Modifier.padding(bottom = 8.dp)) {
                content()
            }
        }
        HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))
    }
}

@Composable
private fun TriStateRow(label: String, value: Boolean?, onChange: (Boolean?) -> Unit) {
    Column {
        Text(label, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.SemiBold)
        FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.padding(top = 4.dp)) {
            EchoChip("Yes", selected = value == true, onClick = { onChange(true) })
            EchoChip("No", selected = value == false, onClick = { onChange(false) })
            EchoChip("Not sure", selected = value == null, onClick = { onChange(null) })
        }
    }
}

/**
 * All the "before you walk in" details. Used in full when adding a mosque and
 * on its own from a mosque card.
 */
@Composable
fun MosqueDetailsEditor(
    draft: DetailsDraft,
    isExisting: Boolean,
    sectChangePending: Boolean,
    sunrise: Int?,
    expandAll: Boolean
) {
    val is24 = rememberIs24Hour()
    var eidPicker by remember { mutableStateOf<String?>(null) }

    CollapsibleSection(
        title = "Place & school of thought",
        summary = listOfNotNull(draft.placeType.label, draft.sect?.shortLabel, draft.asrMethod?.let { "Asr ${it.key}" }).joinToString(" · "),
        initiallyExpanded = expandAll
    ) {
        EchoSectionLabel("Type of place")
        FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            PlaceType.entries.forEach { type ->
                EchoChip(type.label, selected = draft.placeType == type, onClick = { draft.placeType = type })
            }
        }
        if (draft.placeType == PlaceType.MUSALLA) {
            HintText("A prayer room in a market, office, mall or station. Leave out prayers it doesn't hold.")
        }

        EchoSectionLabel("School of thought (optional)")
        EchoChip("Not specified", selected = draft.sect == null, onClick = { draft.sect = null })
        listOf("Sunni", "Shia").forEach { tradition ->
            Text(tradition, style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
            FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                MosqueSect.entries.filter { it.tradition == tradition }.forEach { sect ->
                    EchoChip(sect.label, selected = draft.sect == sect, onClick = { draft.sect = sect })
                }
            }
        }
        if (isExisting) {
            HintText(
                if (sectChangePending) "A change is already waiting for review. Our team checks every school-of-thought change before it appears."
                else "Our team checks every school-of-thought change before it appears."
            )
        }

        EchoSectionLabel("How this mosque times Asr")
        FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            EchoChip("Hanafi (later)", selected = draft.asrMethod == AsrMethod.HANAFI, onClick = { draft.asrMethod = AsrMethod.HANAFI })
            EchoChip("Standard", selected = draft.asrMethod == AsrMethod.STANDARD, onClick = { draft.asrMethod = AsrMethod.STANDARD })
            EchoChip("Not sure", selected = draft.asrMethod == null, onClick = { draft.asrMethod = null })
        }
    }

    val accessibility = MosqueFacility.entries.filter { it.group == FacilityGroup.ACCESSIBILITY }
    CollapsibleSection(
        title = "Accessibility",
        summary = accessibility.filter { draft.facilities[it] == true }.joinToString { it.label }.ifBlank { "Wheelchair access, accessible wudu, prayer chairs" },
        initiallyExpanded = expandAll
    ) {
        HintText("A clear \"No\" helps too: it saves a wasted trip.")
        accessibility.forEach { facility ->
            TriStateRow(facility.label, draft.facilities[facility]) { draft.facilities[facility] = it }
        }
    }

    CollapsibleSection(
        title = "For women",
        summary = listOfNotNull(
            "Prayer area".takeIf { draft.hasWomenArea == true },
            "Wudu area".takeIf { draft.facilities[MosqueFacility.WOMEN_WUDU] == true }
        ).joinToString(" · "),
        initiallyExpanded = expandAll
    ) {
        TriStateRow("Prayer area for women", draft.hasWomenArea) { draft.hasWomenArea = it }
        TriStateRow(MosqueFacility.WOMEN_WUDU.label, draft.facilities[MosqueFacility.WOMEN_WUDU]) {
            draft.facilities[MosqueFacility.WOMEN_WUDU] = it
        }
    }

    val amenities = MosqueFacility.entries.filter { it.group == FacilityGroup.AMENITY }
    CollapsibleSection(
        title = "Facilities",
        summary = amenities.filter { draft.facilities[it] == true }.joinToString { it.label },
        initiallyExpanded = expandAll
    ) {
        HintText("Tick what this mosque has.")
        FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
            amenities.forEach { facility ->
                EchoChip(facility.label, selected = draft.facilities[facility] == true, onClick = { draft.toggleAmenity(facility) })
            }
        }
    }

    CollapsibleSection(
        title = "Jumu'ah khutbah language",
        summary = KHUTBAH_LANGUAGES.filter { it.first in draft.languages }.joinToString { it.second },
        initiallyExpanded = expandAll
    ) {
        HintText("Pick up to $MAX_KHUTBAH_LANGUAGES.")
        FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
            KHUTBAH_LANGUAGES.forEach { (code, label) ->
                EchoChip(label, selected = code in draft.languages, onClick = { draft.toggleLanguage(code) })
            }
        }
    }

    CollapsibleSection(
        title = "Ramadan & Eid",
        summary = listOfNotNull(
            draft.taraweehRakats?.let { "Taraweeh $it rakats" },
            "Eid times added".takeIf { draft.eidFitr.isNotEmpty() || draft.eidAdha.isNotEmpty() }
        ).joinToString(" · "),
        initiallyExpanded = expandAll
    ) {
        EchoSectionLabel("Taraweeh")
        FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            EchoChip("8 rakats", selected = draft.taraweehRakats == 8, onClick = { draft.taraweehRakats = 8 })
            EchoChip("20 rakats", selected = draft.taraweehRakats == 20, onClick = { draft.taraweehRakats = 20 })
            EchoChip("Not sure / none", selected = draft.taraweehRakats == null, onClick = { draft.taraweehRakats = null })
        }
        TriStateRow("Whole Quran completed in Taraweeh", draft.taraweehKhatm) { draft.taraweehKhatm = it }

        EchoSectionLabel("Eid prayer times")
        val thisYear = java.util.Calendar.getInstance().get(java.util.Calendar.YEAR)
        FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            listOf(thisYear, thisYear + 1).forEach { year ->
                EchoChip(year.toString(), selected = draft.eidYear == year, onClick = { draft.eidYear = year })
            }
        }
        EidTimesRow("Eid ul-Fitr", draft.eidFitr, is24, onAdd = { eidPicker = "fitr" })
        EidTimesRow("Eid ul-Adha", draft.eidAdha, is24, onAdd = { eidPicker = "adha" })
    }

    eidPicker?.let { kind ->
        val list = if (kind == "fitr") draft.eidFitr else draft.eidAdha
        val initial = list.maxOrNull()?.let { it + 60 } ?: sunrise?.takeIf { it >= 0 }?.let { roundUpToFive(it + 30) } ?: (7 * 60 + 30)
        JamaahTimePickerDialog(
            title = if (kind == "fitr") "Eid ul-Fitr jama'ah" else "Eid ul-Adha jama'ah",
            initialMinutes = initial % 1440,
            is24Hour = is24,
            onDismiss = { eidPicker = null },
            onConfirm = { minutes ->
                if (minutes !in list && list.size < MAX_EID_TIMES) list.add(minutes)
                eidPicker = null
            }
        )
    }
}

@Composable
private fun EidTimesRow(label: String, times: MutableList<Int>, is24: Boolean, onAdd: () -> Unit) {
    Column {
        Text(label, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.SemiBold)
        FlowRow(
            horizontalArrangement = Arrangement.spacedBy(8.dp),
            verticalArrangement = Arrangement.spacedBy(6.dp),
            modifier = Modifier.padding(top = 4.dp)
        ) {
            times.sorted().forEach { minutes ->
                InputChip(
                    selected = true,
                    onClick = { times.remove(minutes) },
                    label = { Text(displayTime(minutes, is24)) },
                    trailingIcon = { Icon(Icons.Default.Close, contentDescription = "Remove ${displayTime(minutes, is24)}", modifier = Modifier.size(16.dp)) }
                )
            }
            if (times.size < MAX_EID_TIMES) {
                InputChip(
                    selected = false,
                    onClick = onAdd,
                    label = { Text(if (times.isEmpty()) "Add time" else "Another jama'ah") },
                    leadingIcon = { Icon(Icons.Default.Add, contentDescription = null, modifier = Modifier.size(16.dp)) }
                )
            }
        }
    }
}

// ---------------------------------------------------------------------------
// Sheets and dialogs
// ---------------------------------------------------------------------------

/**
 * Add a mosque at the worshipper's current spot. A fresh GPS fix is taken when
 * the sheet opens so the pin lands on the building.
 */
@Composable
fun AddMosqueSheet(
    adhanFor: (AsrMethod?) -> PrayerTimesDay?,
    submitting: Boolean,
    onDismiss: () -> Unit,
    onSubmit: (
        name: String, locality: String, address: String, city: String, country: String,
        latitude: Double, longitude: Double, times: MosqueTimesInput, details: MosqueDetailsInput
    ) -> Unit
) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)

    var name by remember { mutableStateOf("") }
    var locality by remember { mutableStateOf("") }
    var address by remember { mutableStateOf("") }
    var city by remember { mutableStateOf("") }
    var country by remember { mutableStateOf("") }
    var lat by remember { mutableStateOf<Double?>(null) }
    var lng by remember { mutableStateOf<Double?>(null) }
    var accuracy by remember { mutableStateOf<Float?>(null) }
    var locating by remember { mutableStateOf(false) }
    var locateError by remember { mutableStateOf<String?>(null) }
    val timesDraft = remember { TimesDraft(null) }
    val detailsDraft = remember { DetailsDraft(null) }
    val adhan = remember(detailsDraft.asrMethod) { adhanFor(detailsDraft.asrMethod) }

    fun applyPlace(place: PlaceInfo?) {
        if (place == null) return
        if (locality.isBlank()) locality = place.locality
        if (city.isBlank()) city = place.city
        if (address.isBlank()) address = place.addressLine
        country = place.country
    }

    fun locate() {
        scope.launch {
            locating = true
            locateError = null
            val fix = LocationHelper.currentLocation(context, requireFresh = true, timeoutMs = 20_000)
            if (fix == null) {
                locateError = "Couldn't get a GPS fix. Make sure location is on and try again."
            } else {
                lat = fix.latitude
                lng = fix.longitude
                accuracy = if (fix.hasAccuracy()) fix.accuracy else null
                applyPlace(LocationHelper.describe(context, fix.latitude, fix.longitude))
            }
            locating = false
        }
    }

    LaunchedEffect(Unit) { locate() }

    ModalBottomSheet(onDismissRequest = onDismiss, sheetState = sheetState) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .verticalScroll(rememberScrollState())
                .padding(horizontal = 20.dp)
                .padding(bottom = 24.dp)
                .navigationBarsPadding(),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text("Add a mosque", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
            Text(
                "Stand at or inside the mosque so its pin is accurate. Every worshipper who finds the jama'ah through it is, in sha Allah, ongoing reward for you.",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )

            Surface(
                shape = RoundedCornerShape(14.dp),
                color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.6f),
                modifier = Modifier.fillMaxWidth()
            ) {
                Row(modifier = Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
                    Icon(Icons.Default.MyLocation, contentDescription = null, tint = MaterialTheme.colorScheme.primary)
                    Column(
                        modifier = Modifier
                            .weight(1f)
                            .padding(horizontal = 10.dp)
                    ) {
                        when {
                            locating -> Text("Getting a precise location…", style = MaterialTheme.typography.bodyMedium)
                            lat != null && lng != null -> {
                                Text(
                                    String.format(java.util.Locale.US, "%.5f, %.5f", lat, lng),
                                    style = MaterialTheme.typography.bodyMedium,
                                    fontWeight = FontWeight.SemiBold
                                )
                                accuracy?.let {
                                    val note = if (it > 50) " · weak signal, move outside if you can" else ""
                                    Text("Accurate to about ${it.toInt()} m$note", style = MaterialTheme.typography.bodySmall)
                                }
                            }
                            else -> Text(locateError ?: "Location needed", style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.error)
                        }
                    }
                    if (locating) {
                        CircularProgressIndicator(modifier = Modifier.size(22.dp), strokeWidth = 2.dp)
                    } else {
                        TextButton(onClick = { locate() }) { Text("Refresh") }
                    }
                }
            }

            OutlinedTextField(
                value = name,
                onValueChange = { name = it.take(150) },
                label = { Text("Mosque name *") },
                singleLine = true,
                keyboardOptions = KeyboardOptions(capitalization = KeyboardCapitalization.Words),
                modifier = Modifier.fillMaxWidth()
            )
            OutlinedTextField(
                value = locality,
                onValueChange = { locality = it.take(120) },
                label = { Text("Area / neighbourhood") },
                singleLine = true,
                keyboardOptions = KeyboardOptions(capitalization = KeyboardCapitalization.Words),
                modifier = Modifier.fillMaxWidth()
            )
            OutlinedTextField(
                value = address,
                onValueChange = { address = it.take(255) },
                label = { Text("Street address or landmark") },
                modifier = Modifier.fillMaxWidth()
            )
            OutlinedTextField(
                value = city,
                onValueChange = { city = it.take(120) },
                label = { Text("City") },
                singleLine = true,
                keyboardOptions = KeyboardOptions(capitalization = KeyboardCapitalization.Words),
                modifier = Modifier.fillMaxWidth()
            )

            EchoSectionLabel("Jama'ah times (add the ones you know)")
            TimesEditor(draft = timesDraft, adhan = adhan)

            Spacer(Modifier.height(4.dp))
            Text(
                "More about this mosque (optional)",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold
            )
            Column {
                MosqueDetailsEditor(
                    draft = detailsDraft,
                    isExisting = false,
                    sectChangePending = false,
                    sunrise = adhan?.sunrise,
                    expandAll = false
                )
            }

            Spacer(Modifier.height(4.dp))
            val canSubmit = name.trim().length >= 3 && lat != null && lng != null && !locating && !submitting
            EchoPrimaryButton(
                text = if (submitting) "Adding…" else "Add mosque",
                enabled = canSubmit,
                onClick = {
                    onSubmit(
                        name.trim(), locality.trim(), address.trim(), city.trim(), country.trim(),
                        lat!!, lng!!, timesDraft.toInput(), detailsDraft.toInput()
                    )
                }
            )
        }
    }
}

@Composable
fun UpdateTimesSheet(
    mosque: Mosque,
    adhan: PrayerTimesDay?,
    submitting: Boolean,
    onDismiss: () -> Unit,
    onSubmit: (MosqueTimesInput) -> Unit
) {
    val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)
    val draft = remember(mosque.id) { TimesDraft(mosque) }

    ModalBottomSheet(onDismissRequest = onDismiss, sheetState = sheetState) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .verticalScroll(rememberScrollState())
                .padding(horizontal = 20.dp)
                .padding(bottom = 24.dp)
                .navigationBarsPadding(),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text("Update jama'ah times", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
            Text(mosque.name, style = MaterialTheme.typography.titleMedium, color = MaterialTheme.colorScheme.primary)
            Text(
                "Times change with the seasons. Keeping them right helps everyone who prays here reach the jama'ah.",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            TimesEditor(draft = draft, adhan = adhan)
            val input = draft.toInput()
            EchoPrimaryButton(
                text = if (submitting) "Saving…" else "Save times",
                enabled = !submitting && !input.isEmpty,
                onClick = { onSubmit(input) }
            )
        }
    }
}

/** Facilities, school of thought, Jumu'ah and Ramadan details for an existing mosque. */
@Composable
fun EditDetailsSheet(
    mosque: Mosque,
    sunrise: Int?,
    submitting: Boolean,
    onDismiss: () -> Unit,
    onSubmit: (MosqueDetailsInput) -> Unit
) {
    val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)
    val draft = remember(mosque.id) { DetailsDraft(mosque) }

    ModalBottomSheet(onDismissRequest = onDismiss, sheetState = sheetState) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .verticalScroll(rememberScrollState())
                .padding(horizontal = 20.dp)
                .padding(bottom = 24.dp)
                .navigationBarsPadding(),
            verticalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            Text("Mosque details", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
            Text(mosque.name, style = MaterialTheme.typography.titleMedium, color = MaterialTheme.colorScheme.primary)
            Text(
                "Only answer what you know. Wheelchair users, elderly worshippers and visitors plan around these details.",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            MosqueDetailsEditor(
                draft = draft,
                isExisting = true,
                sectChangePending = mosque.sectChangePending,
                sunrise = sunrise,
                expandAll = true
            )
            Spacer(Modifier.height(8.dp))
            EchoPrimaryButton(
                text = if (submitting) "Saving…" else "Save details",
                enabled = !submitting,
                onClick = { onSubmit(draft.toInput()) }
            )
        }
    }
}

private val REPORT_REASONS = listOf(
    "wrong_times" to "Times are wrong",
    "wrong_location" to "Pin is in the wrong place",
    "wrong_sect" to "School of thought is wrong",
    "duplicate" to "Listed twice",
    "closed" to "Closed or not a mosque",
    "inappropriate" to "Inappropriate name or details",
    "other" to "Something else"
)

@Composable
fun ReportMosqueDialog(mosque: Mosque, onDismiss: () -> Unit, onSubmit: (reason: String, note: String) -> Unit) {
    var reason by remember { mutableStateOf<String?>(null) }
    var note by remember { mutableStateOf("") }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Report ${mosque.name}") },
        text = {
            Column(modifier = Modifier.verticalScroll(rememberScrollState())) {
                Text(
                    "If only the times or facilities are wrong, you can fix them yourself.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                Spacer(Modifier.height(8.dp))
                REPORT_REASONS.forEach { (key, label) ->
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        modifier = Modifier
                            .fillMaxWidth()
                            .selectable(selected = reason == key, onClick = { reason = key }, role = Role.RadioButton)
                            .padding(vertical = 2.dp)
                    ) {
                        RadioButton(selected = reason == key, onClick = null)
                        Text(label, modifier = Modifier.padding(start = 8.dp))
                    }
                }
                OutlinedTextField(
                    value = note,
                    onValueChange = { note = it.take(500) },
                    label = { Text("Details (optional)") },
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(top = 8.dp)
                )
            }
        },
        confirmButton = {
            TextButton(enabled = reason != null, onClick = { onSubmit(reason!!, note.trim()) }) { Text("Send report") }
        },
        dismissButton = { TextButton(onClick = onDismiss) { Text("Cancel") } }
    )
}

@Composable
fun SalahSettingsSheet(
    current: SalahSettings,
    followingCountry: Boolean,
    onDismiss: () -> Unit,
    onSave: (SalahSettings) -> Unit
) {
    var method by remember { mutableStateOf(current.method) }
    var asr by remember { mutableStateOf(current.asrMethod) }

    ModalBottomSheet(onDismissRequest = onDismiss) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .verticalScroll(rememberScrollState())
                .padding(horizontal = 20.dp)
                .padding(bottom = 24.dp)
                .navigationBarsPadding()
        ) {
            Text("Adhan time calculation", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
            Text(
                if (followingCountry) "Currently using the usual method for your country. Pick the one your local mosques follow."
                else "Pick the method your local mosques follow.",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.padding(top = 4.dp, bottom = 12.dp)
            )
            EchoSectionLabel("Fajr & Isha")
            CalculationMethod.entries.forEach { m ->
                SettingsRadioRow(label = m.label, selected = method == m, onClick = { method = m })
            }
            Spacer(Modifier.height(12.dp))
            EchoSectionLabel("Asr")
            AsrMethod.entries.forEach { a ->
                SettingsRadioRow(label = a.label, selected = asr == a, onClick = { asr = a })
            }
            Spacer(Modifier.height(16.dp))
            EchoPrimaryButton(text = "Save", onClick = { onSave(SalahSettings(method, asr, chosenByUser = true)) })
        }
    }
}

@Composable
private fun SettingsRadioRow(label: String, selected: Boolean, onClick: () -> Unit) {
    Row(
        verticalAlignment = Alignment.CenterVertically,
        modifier = Modifier
            .fillMaxWidth()
            .selectable(selected = selected, onClick = onClick, role = Role.RadioButton)
            .padding(vertical = 4.dp)
    ) {
        RadioButton(selected = selected, onClick = null)
        Text(label, modifier = Modifier.padding(start = 8.dp))
    }
}

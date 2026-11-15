package com.asloobulhayat.eternalecho.ui.salah

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowForward
import androidx.compose.material.icons.filled.Mosque
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.asloobulhayat.eternalecho.data.PrayerTimesCalculator
import com.asloobulhayat.eternalecho.data.SalahStore
import com.asloobulhayat.eternalecho.data.minutesUntil
import com.asloobulhayat.eternalecho.data.upcomingSalah
import com.asloobulhayat.eternalecho.ui.components.PressableScale
import com.asloobulhayat.eternalecho.ui.home.HomeThemePalette
import kotlinx.coroutines.delay
import java.util.Calendar

/** Home shortcut: the next salah and its jama'ah at the worshipper's mosque. */
@Composable
fun SalahHomeCard(palette: HomeThemePalette, onClick: () -> Unit, modifier: Modifier = Modifier) {
    val context = LocalContext.current
    remember { SalahStore.init(context) }
    val location by SalahStore.location.collectAsState()
    val settings by SalahStore.settings.collectAsState()
    val favorites by SalahStore.favorites.collectAsState()
    val is24 = rememberIs24Hour()

    var now by remember { mutableStateOf(Calendar.getInstance()) }
    LaunchedEffect(Unit) {
        while (true) {
            now = Calendar.getInstance()
            delay(30_000)
        }
    }

    val effective = SalahStore.effectiveSettings(settings, location)
    val dayKey = now.get(Calendar.YEAR) * 1000 + now.get(Calendar.DAY_OF_YEAR)
    val today = remember(location?.latitude, location?.longitude, effective, dayKey) {
        location?.let { PrayerTimesCalculator.calculate(now, it.latitude, it.longitude, effective.method, effective.asrMethod) }
    }
    val nowMinutes = now.get(Calendar.HOUR_OF_DAY) * 60 + now.get(Calendar.MINUTE)
    val isFriday = now.get(Calendar.DAY_OF_WEEK) == Calendar.FRIDAY

    val (title, line1, line2) = if (today == null) {
        Triple("Salah Times", "Find the nearest mosque and its jama'ah times", "Add your mosque as sadaqah jariyah")
    } else {
        val next = upcomingSalah(today, nowMinutes, isFriday, favorites)
        val target = next.jamaah ?: next.adhan
        val what = if (next.jamaah != null) "Jama'ah" else "Adhan"
        Triple(
            "Next: ${next.prayer.label}",
            "$what ${displayTime(target, is24)} · in ${formatCountdown(minutesUntil(nowMinutes, target))}",
            next.mosque?.name ?: "Tap to choose your mosque for each salah"
        )
    }

    PressableScale(onClick = onClick, modifier = modifier.fillMaxWidth()) {
        Card(
            shape = RoundedCornerShape(18.dp),
            colors = CardDefaults.cardColors(containerColor = palette.cardBg),
            border = BorderStroke(1.dp, palette.cardBorder),
            modifier = Modifier.fillMaxWidth()
        ) {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                modifier = Modifier.padding(horizontal = 14.dp, vertical = 14.dp)
            ) {
                Box(
                    contentAlignment = Alignment.Center,
                    modifier = Modifier
                        .size(44.dp)
                        .clip(CircleShape)
                        .background(palette.actionButtonBg)
                ) {
                    Icon(Icons.Default.Mosque, contentDescription = null, tint = palette.primaryAccent)
                }
                Column(
                    modifier = Modifier
                        .weight(1f)
                        .padding(horizontal = 12.dp)
                ) {
                    Text(title, color = palette.textPrimary, fontWeight = FontWeight.Bold, fontSize = 15.sp)
                    Text(line1, color = palette.textSecondary, fontSize = 12.sp, maxLines = 1, overflow = TextOverflow.Ellipsis)
                    Text(line2, color = palette.textMuted, fontSize = 11.sp, maxLines = 1, overflow = TextOverflow.Ellipsis)
                }
                Icon(Icons.AutoMirrored.Filled.ArrowForward, contentDescription = "Open Salah Times", tint = palette.textMuted)
            }
        }
    }
}

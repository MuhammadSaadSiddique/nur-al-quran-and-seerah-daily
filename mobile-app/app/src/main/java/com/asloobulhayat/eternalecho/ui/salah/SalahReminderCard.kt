@file:OptIn(ExperimentalMaterial3Api::class, ExperimentalLayoutApi::class)

package com.asloobulhayat.eternalecho.ui.salah

import android.Manifest
import android.content.Intent
import android.net.Uri
import android.os.Build
import android.provider.Settings
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
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
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.NotificationsActive
import androidx.compose.material.icons.outlined.NotificationsOff
import androidx.compose.material3.Checkbox
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.LifecycleResumeEffect
import com.asloobulhayat.eternalecho.data.SalahPrayer
import com.asloobulhayat.eternalecho.data.SalahReminders
import com.asloobulhayat.eternalecho.notifications.NotificationHelper
import com.asloobulhayat.eternalecho.notifications.PrayerReminderScheduler
import com.asloobulhayat.eternalecho.ui.components.EchoCard
import com.asloobulhayat.eternalecho.ui.components.EchoChip
import com.asloobulhayat.eternalecho.ui.components.EchoPrimaryButton

@Composable
fun SalahReminderCard(
    reminders: SalahReminders,
    hasLocation: Boolean,
    onChange: (SalahReminders) -> Unit,
    onMessage: (String) -> Unit
) {
    val context = LocalContext.current
    // Both grants live in system settings, so re-read them whenever the screen comes back.
    var resumeTick by remember { mutableIntStateOf(0) }
    LifecycleResumeEffect(Unit) {
        resumeTick++
        onPauseOrDispose { }
    }
    val canPost = remember(resumeTick) { NotificationHelper.hasNotificationPermission(context) }
    val onTime = remember(resumeTick) { PrayerReminderScheduler.canScheduleExact(context) }
    var showSheet by remember { mutableStateOf(false) }

    val permissionLauncher = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission()) { granted ->
        if (granted) {
            resumeTick++
            onChange(reminders.copy(enabled = true))
        } else {
            onMessage("Allow notifications for this app to get salah reminders.")
        }
    }

    fun setEnabled(on: Boolean) {
        if (on && !canPost && Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            permissionLauncher.launch(Manifest.permission.POST_NOTIFICATIONS)
        } else {
            onChange(reminders.copy(enabled = on))
        }
    }

    EchoCard {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Icon(
                if (reminders.enabled) Icons.Default.NotificationsActive else Icons.Outlined.NotificationsOff,
                contentDescription = null,
                tint = if (reminders.enabled) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.size(24.dp)
            )
            Column(
                Modifier
                    .weight(1f)
                    .padding(horizontal = 12.dp)
            ) {
                Text("Salah notifications", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                Text(
                    if (reminders.enabled) reminderSummary(reminders) else "Get a notification when each adhan time begins.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            Switch(checked = reminders.enabled, onCheckedChange = { setEnabled(it) })
        }

        if (reminders.enabled) {
            when {
                !canPost -> ReminderWarning(
                    text = "Notifications are turned off for this app, so reminders can't be shown.",
                    action = "Open settings",
                    onAction = { openAppNotificationSettings(context) }
                )
                !hasLocation -> ReminderWarning(
                    text = "Share your location above so the adhan times can be worked out.",
                    action = null,
                    onAction = {}
                )
                !onTime -> ReminderWarning(
                    text = "Your phone may delay reminders by a few minutes. Allow alarms for this app to get them on time.",
                    action = "Allow on-time alarms",
                    onAction = {
                        PrayerReminderScheduler.exactAlarmSettingsIntent(context)?.let {
                            try {
                                context.startActivity(it)
                            } catch (_: Exception) {
                            }
                        }
                    }
                )
            }
            TextButton(onClick = { showSheet = true }) { Text("Choose salahs & timing") }
        }
    }

    if (showSheet) {
        SalahReminderSheet(
            current = reminders,
            onDismiss = { showSheet = false },
            onSave = {
                onChange(it)
                showSheet = false
            }
        )
    }
}

@Composable
private fun ReminderWarning(text: String, action: String?, onAction: () -> Unit) {
    Spacer(Modifier.height(8.dp))
    Text(text, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.tertiary)
    if (action != null) {
        TextButton(onClick = onAction) { Text(action) }
    }
}

@Composable
private fun SalahReminderSheet(
    current: SalahReminders,
    onDismiss: () -> Unit,
    onSave: (SalahReminders) -> Unit
) {
    var prayers by remember { mutableStateOf(current.prayers) }
    var before by remember { mutableIntStateOf(current.minutesBefore) }

    ModalBottomSheet(onDismissRequest = onDismiss) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 20.dp)
                .padding(bottom = 24.dp)
                .navigationBarsPadding()
        ) {
            Text("Salah notifications", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
            Text(
                "On Fridays the Dhuhr reminder is for Jumu'ah.",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.padding(top = 4.dp, bottom = 8.dp)
            )
            SalahPrayer.DAILY.forEach { prayer ->
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier
                        .fillMaxWidth()
                        .clickable(role = Role.Checkbox) {
                            prayers = if (prayer in prayers) prayers - prayer else prayers + prayer
                        }
                        .padding(vertical = 2.dp)
                ) {
                    Checkbox(checked = prayer in prayers, onCheckedChange = null)
                    Text(prayer.label, modifier = Modifier.padding(start = 8.dp), fontWeight = FontWeight.SemiBold)
                }
            }
            Spacer(Modifier.height(12.dp))
            Text("When", style = MaterialTheme.typography.labelLarge, fontWeight = FontWeight.Bold)
            Spacer(Modifier.height(6.dp))
            FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                SalahReminders.LEAD_OPTIONS.forEach { minutes ->
                    EchoChip(
                        label = if (minutes == 0) "At adhan" else "$minutes min before",
                        selected = before == minutes,
                        onClick = { before = minutes }
                    )
                }
            }
            Spacer(Modifier.height(16.dp))
            EchoPrimaryButton(
                text = "Save",
                enabled = prayers.isNotEmpty(),
                onClick = { onSave(current.copy(prayers = prayers, minutesBefore = before)) }
            )
        }
    }
}

private fun reminderSummary(reminders: SalahReminders): String {
    val which = when {
        reminders.prayers.isEmpty() -> return "No salah chosen yet."
        reminders.prayers.containsAll(SalahPrayer.DAILY) -> "All five salahs"
        else -> SalahPrayer.DAILY.filter { it in reminders.prayers }.joinToString { it.label }
    }
    val whenText = if (reminders.minutesBefore == 0) "at adhan time" else "${reminders.minutesBefore} min before adhan"
    return "$which · $whenText"
}

private fun openAppNotificationSettings(context: android.content.Context) {
    val intent = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
        Intent(Settings.ACTION_APP_NOTIFICATION_SETTINGS).putExtra(Settings.EXTRA_APP_PACKAGE, context.packageName)
    } else {
        Intent(Settings.ACTION_APPLICATION_DETAILS_SETTINGS, Uri.parse("package:${context.packageName}"))
    }
    try {
        context.startActivity(intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK))
    } catch (_: Exception) {
    }
}

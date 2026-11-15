package com.asloobulhayat.eternalecho.notifications

import android.app.AlarmManager
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Build
import android.provider.Settings
import android.text.format.DateFormat
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import com.asloobulhayat.eternalecho.MainActivity
import com.asloobulhayat.eternalecho.R
import com.asloobulhayat.eternalecho.data.PrayerTimesCalculator
import com.asloobulhayat.eternalecho.data.SalahPrayer
import com.asloobulhayat.eternalecho.data.SalahStore
import com.asloobulhayat.eternalecho.data.nextPrayerReminder
import com.asloobulhayat.eternalecho.util.DeepLinkHelper
import java.util.Calendar
import java.util.Date

/**
 * Adhan notifications computed on the device. Only the next one is ever
 * scheduled; when it fires, [PrayerReminderReceiver] schedules the one after.
 */
object PrayerReminderScheduler {
    const val CHANNEL_ID = "salah_adhan_reminders"
    private const val CHANNEL_NAME = "Salah times"
    private const val CHANNEL_DESC = "A reminder when the adhan time for each salah begins"

    const val ACTION_REMIND = "com.asloobulhayat.eternalecho.action.SALAH_REMINDER"
    const val EXTRA_PRAYER = "salah_prayer"
    const val EXTRA_ADHAN_AT = "salah_adhan_at"
    const val EXTRA_NOTIFY_AT = "salah_notify_at"

    private const val ALARM_REQUEST_CODE = 0x5A1A
    private const val NOTIFICATION_ID_BASE = 0x5A1A00

    /** A late alarm (phone off, deep sleep) for a salah that began long ago is noise, not a reminder. */
    private const val STALE_AFTER_MILLIS = 30 * 60_000L
    private const val DISMISS_AFTER_MILLIS = 90 * 60_000L

    fun createChannel(context: Context) {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return
        val channel = NotificationChannel(CHANNEL_ID, CHANNEL_NAME, NotificationManager.IMPORTANCE_HIGH).apply {
            description = CHANNEL_DESC
            enableVibration(true)
            setShowBadge(false)
        }
        (context.getSystemService(Context.NOTIFICATION_SERVICE) as? NotificationManager)?.createNotificationChannel(channel)
    }

    fun canScheduleExact(context: Context): Boolean {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.S) return true
        val alarms = context.getSystemService(AlarmManager::class.java) ?: return false
        return alarms.canScheduleExactAlarms()
    }

    /** System page where the worshipper can allow on-time alarms; null where no grant is needed. */
    fun exactAlarmSettingsIntent(context: Context): Intent? {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.S) return null
        return Intent(Settings.ACTION_REQUEST_SCHEDULE_EXACT_ALARM, Uri.parse("package:${context.packageName}"))
            .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
    }

    fun reschedule(context: Context, nowMillis: Long = System.currentTimeMillis()) {
        val app = context.applicationContext
        SalahStore.init(app)
        val alarms = app.getSystemService(AlarmManager::class.java) ?: return
        val location = SalahStore.location.value
        val next = location?.let {
            nextPrayerReminder(nowMillis, it.latitude, it.longitude, SalahStore.effectiveSettings(), SalahStore.reminders.value)
        }

        if (next == null) {
            existingAlarm(app)?.let {
                alarms.cancel(it)
                it.cancel()
            }
            return
        }

        val intent = Intent(app, PrayerReminderReceiver::class.java).apply {
            action = ACTION_REMIND
            putExtra(EXTRA_PRAYER, next.prayer.key)
            putExtra(EXTRA_ADHAN_AT, next.adhanAtMillis)
            putExtra(EXTRA_NOTIFY_AT, next.notifyAtMillis)
        }
        val pending = PendingIntent.getBroadcast(
            app,
            ALARM_REQUEST_CODE,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )
        try {
            if (canScheduleExact(app)) {
                alarms.setExactAndAllowWhileIdle(AlarmManager.RTC_WAKEUP, next.notifyAtMillis, pending)
            } else {
                alarms.setAndAllowWhileIdle(AlarmManager.RTC_WAKEUP, next.notifyAtMillis, pending)
            }
        } catch (e: SecurityException) {
            // Exact-alarm access can be revoked between the check and the call.
            alarms.setAndAllowWhileIdle(AlarmManager.RTC_WAKEUP, next.notifyAtMillis, pending)
        }
    }

    fun show(context: Context, prayer: SalahPrayer, adhanAtMillis: Long, nowMillis: Long = System.currentTimeMillis()) {
        if (nowMillis - adhanAtMillis > STALE_AFTER_MILLIS) return
        if (!NotificationHelper.hasNotificationPermission(context)) return
        createChannel(context)

        val timeFormat = DateFormat.getTimeFormat(context)
        val minutesLeft = ((adhanAtMillis - nowMillis + 30_000L) / 60_000L).toInt()
        val title = if (minutesLeft >= 1) "${prayer.label} in $minutesLeft min" else "It's time for ${prayer.label}"

        val lines = mutableListOf("Adhan ${timeFormat.format(Date(adhanAtMillis))}")
        SalahStore.favorites.value[prayer]?.let { mosque ->
            val day = Calendar.getInstance().apply { timeInMillis = adhanAtMillis }
            val location = SalahStore.location.value
            val settings = SalahStore.effectiveSettings()
            val times = location?.let {
                PrayerTimesCalculator.calculate(day, it.latitude, it.longitude, settings.method, settings.asrMethod)
            }
            val jamaah = mosque.jamaahMinutes(prayer, times)
            if (jamaah != null) {
                day.set(Calendar.HOUR_OF_DAY, jamaah / 60)
                day.set(Calendar.MINUTE, jamaah % 60)
                lines += "Jama'ah ${timeFormat.format(day.time)} at ${mosque.name}"
            } else {
                lines += mosque.name
            }
        }
        val text = lines.joinToString(" · ")

        val openSalah = Intent(context, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP or Intent.FLAG_ACTIVITY_SINGLE_TOP
            action = Intent.ACTION_VIEW
            data = Uri.parse(DeepLinkHelper.getAppSchemeUrl("salah"))
        }
        val contentIntent = PendingIntent.getActivity(
            context,
            NOTIFICATION_ID_BASE + prayer.ordinal,
            openSalah,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val notification = NotificationCompat.Builder(context, CHANNEL_ID)
            .setSmallIcon(R.drawable.eternal)
            .setContentTitle(title)
            .setContentText(text)
            .setStyle(NotificationCompat.BigTextStyle().bigText(lines.joinToString("\n")))
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setCategory(NotificationCompat.CATEGORY_REMINDER)
            .setWhen(adhanAtMillis)
            .setShowWhen(true)
            .setAutoCancel(true)
            .setTimeoutAfter(adhanAtMillis + DISMISS_AFTER_MILLIS - nowMillis)
            .setContentIntent(contentIntent)
            .setDefaults(NotificationCompat.DEFAULT_ALL)
            .build()

        try {
            NotificationManagerCompat.from(context).notify(NOTIFICATION_ID_BASE + prayer.ordinal, notification)
        } catch (e: SecurityException) {
            android.util.Log.e("PrayerReminder", "Couldn't post salah reminder: ${e.message}", e)
        }
    }

    private fun existingAlarm(context: Context): PendingIntent? = PendingIntent.getBroadcast(
        context,
        ALARM_REQUEST_CODE,
        Intent(context, PrayerReminderReceiver::class.java).setAction(ACTION_REMIND),
        PendingIntent.FLAG_NO_CREATE or PendingIntent.FLAG_IMMUTABLE
    )
}

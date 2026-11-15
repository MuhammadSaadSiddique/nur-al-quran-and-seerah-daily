package com.asloobulhayat.eternalecho.notifications

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import com.asloobulhayat.eternalecho.data.SalahPrayer
import com.asloobulhayat.eternalecho.data.SalahStore

/**
 * Posts a due adhan notification and queues the next one. Also re-arms the
 * schedule after a reboot, an app update, or a clock or time zone change,
 * since alarms don't survive those or would fire at the wrong time.
 */
class PrayerReminderReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        SalahStore.init(context.applicationContext)
        val now = System.currentTimeMillis()

        if (intent.action == PrayerReminderScheduler.ACTION_REMIND) {
            val prayer = SalahPrayer.fromKey(intent.getStringExtra(PrayerReminderScheduler.EXTRA_PRAYER))
            val adhanAt = intent.getLongExtra(PrayerReminderScheduler.EXTRA_ADHAN_AT, 0L)
            val notifyAt = intent.getLongExtra(PrayerReminderScheduler.EXTRA_NOTIFY_AT, now)
            if (prayer != null && adhanAt > 0 && SalahStore.reminders.value.covers(prayer)) {
                PrayerReminderScheduler.show(context, prayer, adhanAt, now)
            }
            PrayerReminderScheduler.reschedule(context, maxOf(now, notifyAt))
            return
        }

        PrayerReminderScheduler.reschedule(context, now)
    }
}

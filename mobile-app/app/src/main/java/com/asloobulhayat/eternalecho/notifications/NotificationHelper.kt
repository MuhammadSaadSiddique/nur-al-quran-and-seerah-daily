package com.asloobulhayat.eternalecho.notifications

import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import com.asloobulhayat.eternalecho.MainActivity
import com.asloobulhayat.eternalecho.R

object NotificationHelper {
    const val CHANNEL_ID = "eternal_echo_announcements"
    const val CHANNEL_NAME = "Eternal Echo Announcements"
    const val CHANNEL_DESC = "Notifications and announcements dispatched from The Eternal Echo platform"

    fun createNotificationChannel(context: Context) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val importance = NotificationManager.IMPORTANCE_HIGH
            val channel = NotificationChannel(CHANNEL_ID, CHANNEL_NAME, importance).apply {
                description = CHANNEL_DESC
                enableLights(true)
                enableVibration(true)
                setShowBadge(true)
            }
            val notificationManager = context.getSystemService(Context.NOTIFICATION_SERVICE) as? NotificationManager
            notificationManager?.createNotificationChannel(channel)
        }
    }

    fun hasNotificationPermission(context: Context): Boolean {
        return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            ContextCompat.checkSelfPermission(
                context,
                android.Manifest.permission.POST_NOTIFICATIONS
            ) == PackageManager.PERMISSION_GRANTED
        } else {
            true
        }
    }

    fun showSystemNotification(
        context: Context,
        notificationId: Long,
        title: String,
        message: String,
        actionUrl: String? = null
    ) {
        if (!hasNotificationPermission(context)) {
            android.util.Log.w("NotificationHelper", "Notification permission not granted. Skipping system tray alert.")
            return
        }

        createNotificationChannel(context)

        val intent = Intent(context, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
            actionUrl?.let { putExtra("EXTRA_ACTION_URL", it) }
            putExtra("EXTRA_NOTIFICATION_ID", notificationId)
        }

        val pendingIntent = PendingIntent.getActivity(
            context,
            notificationId.toInt(),
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val builder = NotificationCompat.Builder(context, CHANNEL_ID)
            .setSmallIcon(R.drawable.eternal)
            .setContentTitle(title)
            .setContentText(message)
            .setStyle(NotificationCompat.BigTextStyle().bigText(message))
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setCategory(NotificationCompat.CATEGORY_MESSAGE)
            .setAutoCancel(true)
            .setContentIntent(pendingIntent)
            .setDefaults(NotificationCompat.DEFAULT_ALL)

        try {
            NotificationManagerCompat.from(context).notify(notificationId.toInt(), builder.build())
        } catch (e: SecurityException) {
            android.util.Log.e("NotificationHelper", "SecurityException posting notification: ${e.message}", e)
        } catch (e: Exception) {
            android.util.Log.e("NotificationHelper", "Failed to post notification: ${e.message}", e)
        }
    }
}

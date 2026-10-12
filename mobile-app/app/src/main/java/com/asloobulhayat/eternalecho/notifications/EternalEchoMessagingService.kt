package com.asloobulhayat.eternalecho.notifications

import android.content.Context
import android.os.Build
import com.asloobulhayat.eternalecho.data.ApiClient
import com.asloobulhayat.eternalecho.security.SecurePreferences
import com.google.firebase.messaging.FirebaseMessaging
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

class EternalEchoMessagingService : FirebaseMessagingService() {

    override fun onNewToken(token: String) {
        super.onNewToken(token)
        sendTokenToServer(applicationContext, token)
    }

    override fun onMessageReceived(message: RemoteMessage) {
        super.onMessageReceived(message)

        val notificationId = message.data["notification_id"]?.toLongOrNull()
            ?: (System.currentTimeMillis() % Int.MAX_VALUE)
        val title = message.notification?.title
            ?: message.data["title"]
            ?: "Announcement"
        val body = message.notification?.body
            ?: message.data["message"]
            ?: ""
        val actionUrl = message.data["action_url"]?.takeIf { it.isNotBlank() && it != "null" }

        if (body.isNotBlank()) {
            NotificationHelper.showSystemNotification(
                context = applicationContext,
                notificationId = notificationId,
                title = title,
                message = body,
                actionUrl = actionUrl
            )
        }

        // Sync repository quietly so badge count & announcements list stay up to date without duplicate alerts
        CoroutineScope(Dispatchers.IO).launch {
            NotificationRepository.getInstance(applicationContext)
                .syncNotifications(showAlerts = false)
        }
    }

    companion object {
        private const val APP_VERSION = "2.0"

        fun registerCurrentFcmToken(context: Context) {
            try {
                FirebaseMessaging.getInstance().token.addOnCompleteListener { task ->
                    if (!task.isSuccessful) {
                        android.util.Log.w(
                            "EternalEchoFCM",
                            "Fetching FCM registration token failed",
                            task.exception
                        )
                        return@addOnCompleteListener
                    }

                    val token = task.result
                    if (!token.isNullOrBlank()) {
                        sendTokenToServer(context.applicationContext, token)
                    }
                }
            } catch (e: Exception) {
                android.util.Log.w("EternalEchoFCM", "FCM not initialized or unavailable: ${e.message}")
            }
        }

        private fun sendTokenToServer(appContext: Context, token: String) {
            CoroutineScope(Dispatchers.IO).launch {
                try {
                    val prefs = SecurePreferences.getInstance(appContext)
                    val userId = prefs.getInt("user_id", -1).takeIf { it > 0 }
                    val deviceName = "${Build.MANUFACTURER} ${Build.MODEL}".trim()

                    ApiClient.registerDeviceToken(
                        token = token,
                        deviceName = deviceName.ifBlank { "Android Device" },
                        appVersion = APP_VERSION,
                        userId = userId
                    )
                } catch (e: Exception) {
                    android.util.Log.e("EternalEchoFCM", "Failed to sync FCM token: ${e.message}", e)
                }
            }
        }
    }
}

package com.asloobulhayat.eternalecho.notifications

import android.content.Context
import com.asloobulhayat.eternalecho.data.ApiClient
import com.asloobulhayat.eternalecho.data.NotificationItem
import com.asloobulhayat.eternalecho.security.SecurePreferences
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock

class NotificationRepository private constructor(context: Context) {
    private val appContext = context.applicationContext
    private val prefs = SecurePreferences.getInstance(appContext)
    private val mutex = Mutex()

    private val _notifications = MutableStateFlow<List<NotificationItem>>(emptyList())
    val notifications: StateFlow<List<NotificationItem>> = _notifications.asStateFlow()

    private val _unreadCount = MutableStateFlow(0)
    val unreadCount: StateFlow<Int> = _unreadCount.asStateFlow()

    companion object {
        private const val PREF_LAST_SEEN_NOTIFICATION_ID = "pref_last_seen_notification_id"
        private const val PREF_READ_NOTIFICATION_IDS = "pref_read_notification_ids"

        @Volatile
        private var INSTANCE: NotificationRepository? = null

        fun getInstance(context: Context): NotificationRepository {
            return INSTANCE ?: synchronized(this) {
                INSTANCE ?: NotificationRepository(context).also { INSTANCE = it }
            }
        }
    }

    private fun getReadIds(): Set<Long> {
        val raw = prefs.getString(PREF_READ_NOTIFICATION_IDS, "") ?: ""
        if (raw.isBlank()) return emptySet()
        return raw.split(",").mapNotNull { it.trim().toLongOrNull() }.toSet()
    }

    private fun saveReadIds(ids: Set<Long>) {
        val serialized = ids.joinToString(",")
        prefs.putString(PREF_READ_NOTIFICATION_IDS, serialized)
    }

    fun getLastSeenId(): Long {
        return prefs.getLong(PREF_LAST_SEEN_NOTIFICATION_ID, 0L)
    }

    private fun saveLastSeenId(id: Long) {
        prefs.putLong(PREF_LAST_SEEN_NOTIFICATION_ID, id)
    }

    suspend fun syncNotifications(showAlerts: Boolean = true): List<NotificationItem> = mutex.withLock {
        val lastSeenId = getLastSeenId()
        val fetched = ApiClient.fetchNotifications(limit = 30)

        val readIds = getReadIds().toMutableSet()
        var highestId = lastSeenId

        // Alert for new notifications if requested
        val processed = fetched.map { item ->
            if (item.id > highestId) {
                highestId = item.id
            }

            if (showAlerts && item.id > lastSeenId && lastSeenId > 0) {
                NotificationHelper.showSystemNotification(
                    context = appContext,
                    notificationId = item.id,
                    title = item.title,
                    message = item.message,
                    actionUrl = item.actionUrl
                )
            }

            item.copy(isRead = readIds.contains(item.id))
        }

        // If this was first launch (lastSeenId == 0), set lastSeenId to highest fetched so we don't spam past announcements
        if (highestId > lastSeenId) {
            saveLastSeenId(highestId)
        }

        _notifications.value = processed
        _unreadCount.value = processed.count { !it.isRead }
        return processed
    }

    fun markAsRead(notificationId: Long) {
        val readIds = getReadIds().toMutableSet()
        readIds.add(notificationId)
        saveReadIds(readIds)

        _notifications.value = _notifications.value.map {
            if (it.id == notificationId) it.copy(isRead = true) else it
        }
        _unreadCount.value = _notifications.value.count { !it.isRead }
    }

    fun markAllAsRead() {
        val allIds = _notifications.value.map { it.id }.toSet()
        val readIds = getReadIds().toMutableSet()
        readIds.addAll(allIds)
        saveReadIds(readIds)

        _notifications.value = _notifications.value.map { it.copy(isRead = true) }
        _unreadCount.value = 0
    }
}

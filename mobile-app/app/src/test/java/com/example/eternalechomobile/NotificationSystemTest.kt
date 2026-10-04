package com.asloobulhayat.eternalecho

import com.asloobulhayat.eternalecho.data.NotificationItem
import com.asloobulhayat.eternalecho.notifications.NotificationHelper
import org.json.JSONArray
import org.json.JSONObject
import org.junit.Assert.*
import org.junit.Test

class NotificationSystemTest {

    @Test
    fun testNotificationItemModelProperties() {
        val item = NotificationItem(
            id = 42L,
            title = "Special Announcement",
            message = "New Quranic Lens insights published.",
            type = "announcement",
            actionUrl = "https://theeternalecho.asloobulhayat.com/lens",
            createdAt = "2026-09-27T12:00:00Z",
            isRead = false
        )

        assertEquals(42L, item.id)
        assertEquals("Special Announcement", item.title)
        assertEquals("New Quranic Lens insights published.", item.message)
        assertEquals("announcement", item.type)
        assertEquals("https://theeternalecho.asloobulhayat.com/lens", item.actionUrl)
        assertFalse(item.isRead)

        val readItem = item.copy(isRead = true)
        assertTrue(readItem.isRead)
    }

    @Test
    fun testNotificationChannelConstants() {
        assertEquals("eternal_echo_announcements", NotificationHelper.CHANNEL_ID)
        assertEquals("Eternal Echo Announcements", NotificationHelper.CHANNEL_NAME)
        assertTrue(NotificationHelper.CHANNEL_DESC.contains("Eternal Echo"))
    }

    @Test
    fun testNotificationJsonParsing() {
        val jsonString = """
            {
                "status": "success",
                "count": 2,
                "data": [
                    {
                        "id": 101,
                        "title": "Welcome Update",
                        "message": "Welcome to the latest app release!",
                        "type": "announcement",
                        "action_url": null,
                        "created_at": "2026-09-27T08:30:00Z"
                    },
                    {
                        "id": 102,
                        "title": "Daily Dua Reminder",
                        "message": "Recite Ayat al-Kursi for protection.",
                        "type": "dua_reminder",
                        "action_url": "/duas/ayat-al-kursi",
                        "created_at": "2026-09-27T09:00:00Z"
                    }
                ]
            }
        """.trimIndent()

        val root = JSONObject(jsonString)
        assertEquals("success", root.getString("status"))

        val dataArr = root.getJSONArray("data")
        assertEquals(2, dataArr.length())

        val list = mutableListOf<NotificationItem>()
        for (i in 0 until dataArr.length()) {
            val item = dataArr.getJSONObject(i)
            list.add(
                NotificationItem(
                    id = item.getLong("id"),
                    title = item.getString("title"),
                    message = item.getString("message"),
                    type = item.optString("type", "announcement"),
                    actionUrl = item.optString("action_url").takeIf { it.isNotEmpty() && it != "null" },
                    createdAt = item.optString("created_at").takeIf { it.isNotEmpty() && it != "null" },
                    isRead = false
                )
            )
        }

        assertEquals(2, list.size)
        assertEquals(101L, list[0].id)
        assertEquals("Welcome Update", list[0].title)
        assertNull(list[0].actionUrl)

        assertEquals(102L, list[1].id)
        assertEquals("/duas/ayat-al-kursi", list[1].actionUrl)
    }

    @Test
    fun testUnreadCountLogic() {
        val notifications = listOf(
            NotificationItem(1L, "T1", "M1", isRead = false),
            NotificationItem(2L, "T2", "M2", isRead = true),
            NotificationItem(3L, "T3", "M3", isRead = false)
        )

        val unreadCount = notifications.count { !it.isRead }
        assertEquals(2, unreadCount)

        val allRead = notifications.map { it.copy(isRead = true) }
        assertEquals(0, allRead.count { !it.isRead })
    }
}

package com.asloobulhayat.eternalecho.data

import android.content.Context
import com.asloobulhayat.eternalecho.security.SecurePreferences
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import org.json.JSONArray
import org.json.JSONObject
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.TimeZone

/**
 * Offline-first daily devotion state.
 * Optimistic local marks update Home immediately; server payloads merge without
 * clearing activities already completed today on-device.
 */
object DailyEngagementStore {
    private const val KEY_CACHE = "daily_engagement_cache_v1"
    private const val KEY_PENDING = "daily_engagement_pending_v1"

    private val activityKeys = listOf("quiz", "read", "insight", "dua")
    private val defaultLabels = mapOf(
        "quiz" to "Complete a quiz",
        "read" to "Read a Surah or Para",
        "insight" to "Read a Seerah or Quran insight",
        "dua" to "Open today's dua"
    )

    @Volatile
    private var prefs: SecurePreferences? = null

    private val _engagement = MutableStateFlow<DailyEngagement?>(null)
    val engagement: StateFlow<DailyEngagement?> = _engagement.asStateFlow()

    private val _syncTick = MutableStateFlow(0)
    val syncTick: StateFlow<Int> = _syncTick.asStateFlow()

    fun init(context: Context) {
        val p = SecurePreferences.getInstance(context.applicationContext)
        prefs = p
        val cached = loadCache(p)
        _engagement.value = when {
            cached == null -> emptyForToday(p)
            cached.date != todayDate() -> emptyForToday(p).also { persist(p, it) }
            else -> cached
        }
    }

    fun clear() {
        val p = prefs ?: return
        p.remove(KEY_CACHE)
        p.remove(KEY_PENDING)
        _engagement.value = emptyForToday(p)
    }

    fun requestSync() {
        _syncTick.update { it + 1 }
    }

    fun snapshot(): DailyEngagement {
        ensureReady()
        return ensureToday(_engagement.value)
    }

    /**
     * Optimistically mark a habit done locally (and persist). Safe offline.
     */
    @Synchronized
    fun markLocal(activityKey: String): DailyEngagement {
        ensureReady()
        val key = normalizeKey(activityKey) ?: return snapshot()
        val updated = applyMark(ensureToday(_engagement.value), key)
        persistAndEmit(updated)
        addPending(key)
        return updated
    }

    /**
     * Merge a server (or published) payload with local completions for today.
     */
    @Synchronized
    fun applyServer(remote: DailyEngagement): DailyEngagement {
        ensureReady()
        val merged = merge(ensureToday(_engagement.value), remote)
        clearPendingConfirmed(merged)
        persistAndEmit(merged)
        return merged
    }

    /** Compatibility alias used by nested screens. */
    fun publish(remote: DailyEngagement) = applyServer(remote)

    @Synchronized
    fun setStreak(streak: StreakData): DailyEngagement {
        ensureReady()
        val updated = ensureToday(_engagement.value).copy(streak = streak)
        persistAndEmit(updated)
        return updated
    }

    private fun ensureReady() {
        check(prefs != null) { "DailyEngagementStore.init(context) must be called first" }
    }

    private fun todayDate(): String {
        val fmt = SimpleDateFormat("yyyy-MM-dd", Locale.US)
        fmt.timeZone = TimeZone.getDefault()
        return fmt.format(Date())
    }

    private fun normalizeKey(raw: String): String? {
        val k = raw.trim().lowercase(Locale.US)
        return when (k) {
            "quiz", "read", "insight", "dua" -> k
            "seerah", "history", "quran_history", "seerah_insight" -> "insight"
            "surah", "para", "reading" -> "read"
            else -> if (k in activityKeys) k else null
        }
    }

    private fun defaultActivities(doneMap: Map<String, Boolean> = emptyMap()): List<EngagementActivity> {
        return activityKeys.map { key ->
            EngagementActivity(
                key = key,
                done = doneMap[key] == true,
                label = defaultLabels[key] ?: key,
                href = ""
            )
        }
    }

    fun emptyForToday(p: SecurePreferences? = prefs): DailyEngagement {
        val prefsOrThrow = p ?: error("prefs missing")
        return DailyEngagement(
            date = todayDate(),
            activities = defaultActivities(),
            completedCount = 0,
            total = 4,
            progressPercent = 0,
            streak = StreakData(
                currentStreak = prefsOrThrow.getInt("current_streak", 0),
                longestStreak = prefsOrThrow.getInt("longest_streak", 0),
                isActiveToday = prefsOrThrow.getBoolean("is_streak_active_today", false)
            )
        )
    }

    private fun ensureToday(current: DailyEngagement?): DailyEngagement {
        val p = prefs ?: return emptyForToday()
        if (current == null || current.date != todayDate()) {
            return emptyForToday(p)
        }
        // Ensure all four keys exist
        if (current.activities.size < 4 || activityKeys.any { k -> current.activities.none { it.key == k } }) {
            val doneMap = current.activities.associate { it.key to it.done }
            return recompute(current.copy(activities = defaultActivities(doneMap)), current.streak)
        }
        return current
    }

    private fun applyMark(current: DailyEngagement, key: String): DailyEngagement {
        val activities = activityKeys.map { k ->
            val existing = current.activities.find { it.key == k }
            EngagementActivity(
                key = k,
                done = if (k == key) true else (existing?.done == true),
                label = existing?.label?.ifBlank { null } ?: defaultLabels[k] ?: k,
                href = existing?.href.orEmpty()
            )
        }
        val wasInactive = !current.streak.isActiveToday
        val streak = if (wasInactive) {
            val next = (current.streak.currentStreak + 1).coerceAtLeast(1)
            current.streak.copy(
                currentStreak = next,
                longestStreak = maxOf(current.streak.longestStreak, next),
                isActiveToday = true
            )
        } else {
            current.streak.copy(isActiveToday = true)
        }
        return recompute(current.copy(activities = activities, streak = streak), streak)
    }

    private fun recompute(base: DailyEngagement, streak: StreakData): DailyEngagement {
        val completed = base.activities.count { it.done }
        val total = 4
        return base.copy(
            completedCount = completed,
            total = total,
            progressPercent = if (total == 0) 0 else ((completed * 100) / total),
            streak = streak
        )
    }

    private fun merge(local: DailyEngagement, remote: DailyEngagement): DailyEngagement {
        val today = todayDate()
        // Prefer today's local progress if server date is stale/wrong.
        if (local.date == today && remote.date.isNotBlank() && remote.date != today) {
            return local
        }
        val baseDate = remote.date.ifBlank { today }
        val remoteByKey = remote.activities.associateBy { it.key }
        val localByKey = local.activities.associateBy { it.key }
        val pending = pendingKeys()

        val activities = activityKeys.map { key ->
            val r = remoteByKey[key]
            val l = localByKey[key]
            val done = (r?.done == true) || (l?.done == true) || pending.contains(key)
            EngagementActivity(
                key = key,
                done = done,
                label = r?.label?.takeIf { it.isNotBlank() }
                    ?: l?.label?.takeIf { it.isNotBlank() }
                    ?: defaultLabels[key]
                    ?: key,
                href = r?.href?.takeIf { it.isNotBlank() } ?: l?.href.orEmpty()
            )
        }

        val streak = mergeStreak(local.streak, remote.streak, activities.any { it.done })
        return recompute(
            DailyEngagement(
                date = if (baseDate == today || local.date == today) today else baseDate,
                activities = activities,
                streak = streak
            ),
            streak
        )
    }

    private fun mergeStreak(local: StreakData, remote: StreakData, anyDoneToday: Boolean): StreakData {
        val current = maxOf(local.currentStreak, remote.currentStreak)
        val longest = maxOf(local.longestStreak, remote.longestStreak, current)
        val active = local.isActiveToday || remote.isActiveToday || anyDoneToday
        return StreakData(
            currentStreak = if (active && current == 0) 1 else current,
            longestStreak = longest.coerceAtLeast(if (active && current == 0) 1 else current),
            isActiveToday = active,
            lastStreakDate = remote.lastStreakDate ?: local.lastStreakDate,
            streakHistory = if (remote.streakHistory.isNotEmpty()) remote.streakHistory else local.streakHistory
        )
    }

    private fun persistAndEmit(value: DailyEngagement) {
        val p = prefs ?: return
        persist(p, value)
        p.putInt("current_streak", value.streak.currentStreak)
        p.putInt("longest_streak", value.streak.longestStreak)
        p.putBoolean("is_streak_active_today", value.streak.isActiveToday)
        _engagement.value = value
    }

    private fun persist(p: SecurePreferences, value: DailyEngagement) {
        val obj = JSONObject()
        obj.put("date", value.date)
        obj.put("completed_count", value.completedCount)
        obj.put("total", value.total)
        obj.put("progress_percent", value.progressPercent)
        val acts = JSONObject()
        value.activities.forEach { a ->
            acts.put(
                a.key,
                JSONObject()
                    .put("done", a.done)
                    .put("label", a.label)
                    .put("href", a.href)
            )
        }
        obj.put("activities", acts)
        val streak = JSONObject()
            .put("current_streak", value.streak.currentStreak)
            .put("longest_streak", value.streak.longestStreak)
            .put("is_active_today", value.streak.isActiveToday)
            .put("last_streak_date", value.streak.lastStreakDate ?: "")
        val hist = JSONArray()
        value.streak.streakHistory.forEach { hist.put(it) }
        streak.put("streak_history", hist)
        obj.put("streak", streak)
        p.putString(KEY_CACHE, obj.toString())
    }

    private fun loadCache(p: SecurePreferences): DailyEngagement? {
        val raw = p.getString(KEY_CACHE, null) ?: return null
        return try {
            val data = JSONObject(raw)
            val streakObj = data.optJSONObject("streak")
            val history = mutableListOf<String>()
            streakObj?.optJSONArray("streak_history")?.let { arr ->
                for (i in 0 until arr.length()) history.add(arr.getString(i))
            }
            val streak = StreakData(
                currentStreak = streakObj?.optInt("current_streak", 0) ?: 0,
                longestStreak = streakObj?.optInt("longest_streak", 0) ?: 0,
                isActiveToday = streakObj?.optBoolean("is_active_today", false) ?: false,
                lastStreakDate = streakObj?.optString("last_streak_date")?.takeIf { it.isNotBlank() },
                streakHistory = history
            )
            val activitiesObj = data.optJSONObject("activities")
            val doneMap = mutableMapOf<String, Boolean>()
            val labelMap = mutableMapOf<String, String>()
            if (activitiesObj != null) {
                activityKeys.forEach { key ->
                    val act = activitiesObj.optJSONObject(key) ?: return@forEach
                    doneMap[key] = act.optBoolean("done", false)
                    labelMap[key] = act.optString("label", defaultLabels[key] ?: key)
                }
            }
            val activities = activityKeys.map { key ->
                EngagementActivity(
                    key = key,
                    done = doneMap[key] == true,
                    label = labelMap[key] ?: defaultLabels[key] ?: key,
                    href = activitiesObj?.optJSONObject(key)?.optString("href", "") ?: ""
                )
            }
            val completed = activities.count { it.done }
            DailyEngagement(
                date = data.optString("date", todayDate()),
                activities = activities,
                completedCount = data.optInt("completed_count", completed),
                total = data.optInt("total", 4),
                progressPercent = data.optInt("progress_percent", if (completed == 0) 0 else (completed * 100) / 4),
                streak = streak
            )
        } catch (_: Exception) {
            null
        }
    }

    private fun pendingKeys(): Set<String> {
        val raw = prefs?.getString(KEY_PENDING, null) ?: return emptySet()
        return raw.split(',').map { it.trim() }.filter { it in activityKeys }.toSet()
    }

    private fun addPending(key: String) {
        val p = prefs ?: return
        val next = pendingKeys().toMutableSet().apply { add(key) }
        p.putString(KEY_PENDING, next.joinToString(","))
    }

    private fun clearPendingConfirmed(merged: DailyEngagement) {
        val p = prefs ?: return
        val still = pendingKeys().filter { key ->
            merged.activities.find { it.key == key }?.done != true
        }
        if (still.isEmpty()) p.remove(KEY_PENDING)
        else p.putString(KEY_PENDING, still.joinToString(","))
    }
}

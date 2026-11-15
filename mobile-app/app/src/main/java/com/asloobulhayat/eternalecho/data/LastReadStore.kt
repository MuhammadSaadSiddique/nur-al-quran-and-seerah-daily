package com.asloobulhayat.eternalecho.data

import android.content.Context
import com.asloobulhayat.eternalecho.security.SecurePreferences
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

data class LastReadPosition(
    val surahNumber: Int,
    val verseNumber: Int,
    val juzNumber: Int
)

/**
 * The reader's own "continue where you left off" position, saved on-device
 * as they scroll a Surah. Home reads it instead of a fixed placeholder.
 */
object LastReadStore {
    private const val KEY = "last_read_position_v1"

    @Volatile
    private var prefs: SecurePreferences? = null

    private val _position = MutableStateFlow<LastReadPosition?>(null)
    val position: StateFlow<LastReadPosition?> = _position.asStateFlow()

    fun init(context: Context) {
        val p = SecurePreferences.getInstance(context.applicationContext)
        prefs = p
        _position.value = decode(p.getString(KEY, null))
    }

    fun update(surahNumber: Int, verseNumber: Int, juzNumber: Int) {
        if (surahNumber !in 1..114 || verseNumber < 1) return
        val next = LastReadPosition(surahNumber, verseNumber, juzNumber.coerceIn(1, 30))
        if (_position.value == next) return
        _position.value = next
        prefs?.putString(KEY, "${next.surahNumber}:${next.verseNumber}:${next.juzNumber}")
    }

    fun clear() {
        prefs?.remove(KEY)
        _position.value = null
    }

    private fun decode(raw: String?): LastReadPosition? {
        val parts = raw?.split(':')?.mapNotNull { it.toIntOrNull() } ?: return null
        if (parts.size != 3) return null
        return LastReadPosition(parts[0], parts[1], parts[2])
    }
}

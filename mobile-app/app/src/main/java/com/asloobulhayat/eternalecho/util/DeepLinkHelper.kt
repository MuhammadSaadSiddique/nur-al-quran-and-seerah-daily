package com.asloobulhayat.eternalecho.util

import android.net.Uri

/**
 * Destinations reachable via deep links.
 */
sealed interface DeepLinkDestination {
    data class Surah(val surahNumber: Int, val verseNumber: Int? = null) : DeepLinkDestination
    data object Duas : DeepLinkDestination
    data class DuaDetail(val idOrSlug: String) : DeepLinkDestination
    data object Seerah : DeepLinkDestination
    data object History : DeepLinkDestination
    data object Quiz : DeepLinkDestination
    data object Leaderboard : DeepLinkDestination
    data object Home : DeepLinkDestination
}

/**
 * Helper to generate and parse deep links across both web domain
 * (theeternalecho.asloobulhayat.com) and custom app scheme (eternalecho://).
 */
object DeepLinkHelper {

    const val WEB_HOST = "theeternalecho.asloobulhayat.com"
    const val BASE_WEB_URL = "https://$WEB_HOST"
    const val SCHEME_APP = "eternalecho"

    fun getHomeUrl(): String = "$BASE_WEB_URL/"

    fun getVerseUrl(surahNumber: Int, verseNumber: Int? = null): String {
        return if (verseNumber != null && verseNumber > 0) {
            "$BASE_WEB_URL/lens/$surahNumber/$verseNumber"
        } else {
            "$BASE_WEB_URL/lens/$surahNumber"
        }
    }

    fun getDuasUrl(): String = "$BASE_WEB_URL/duas"

    fun getDuaDetailUrl(idOrSlug: String): String = "$BASE_WEB_URL/duas/$idOrSlug"

    fun getSeerahUrl(): String = "$BASE_WEB_URL/seerah"

    fun getHistoryUrl(): String = "$BASE_WEB_URL/quran-history"

    fun getQuizUrl(): String = "$BASE_WEB_URL/quiz-learning"

    fun getLeaderboardUrl(): String = "$BASE_WEB_URL/leaderboard"

    fun getAppSchemeUrl(path: String): String {
        val cleanPath = path.trim().trimStart('/')
        return "$SCHEME_APP://$cleanPath"
    }

    /**
     * Parses an Android Uri (from Intent.data).
     */
    fun parse(uri: Uri?): DeepLinkDestination? {
        if (uri == null) return null
        return parse(uri.toString())
    }

    fun parseUri(uri: Uri?): DeepLinkDestination? = parse(uri)

    /**
     * Turns site-relative paths (`/duas/ayat-al-kursi`, `lens/2/255`) into absolute https URLs.
     */
    fun toAbsoluteUrl(urlString: String): String {
        val trimmed = urlString.trim()
        if (trimmed.contains("://")) return trimmed
        if (trimmed.startsWith("//")) return "https:$trimmed"

        val hostPrefix = trimmed.substringBefore('/').substringBefore('?').substringBefore('#')
        if (hostPrefix.equals(WEB_HOST, ignoreCase = true) ||
            hostPrefix.equals("www.$WEB_HOST", ignoreCase = true)
        ) {
            return "https://$trimmed"
        }

        return "$BASE_WEB_URL/${trimmed.trimStart('/')}"
    }

    /**
     * An http(s) address that does not map to an in-app screen.
     * Returns null for app paths such as /duas or /lens/2/255.
     */
    fun externalWebUrl(urlString: String?): String? {
        if (urlString.isNullOrBlank()) return null
        if (parse(urlString) != null) return null

        val absolute = toAbsoluteUrl(urlString)
        val lower = absolute.lowercase()
        if (!lower.startsWith("https://") && !lower.startsWith("http://")) return null
        return absolute
    }

    fun parse(urlString: String?): DeepLinkDestination? {
        if (urlString.isNullOrBlank()) return null

        val trimmed = toAbsoluteUrl(urlString)
        val withoutQuery = trimmed.substringBefore('?').substringBefore('#')

        val (isSupported, pathString) = when {
            withoutQuery.startsWith("$SCHEME_APP://", ignoreCase = true) -> {
                true to withoutQuery.substring("$SCHEME_APP://".length)
            }
            withoutQuery.startsWith("https://$WEB_HOST", ignoreCase = true) -> {
                true to withoutQuery.substring("https://$WEB_HOST".length)
            }
            withoutQuery.startsWith("http://$WEB_HOST", ignoreCase = true) -> {
                true to withoutQuery.substring("http://$WEB_HOST".length)
            }
            withoutQuery.startsWith("https://www.$WEB_HOST", ignoreCase = true) -> {
                true to withoutQuery.substring("https://www.$WEB_HOST".length)
            }
            withoutQuery.startsWith("http://www.$WEB_HOST", ignoreCase = true) -> {
                true to withoutQuery.substring("http://www.$WEB_HOST".length)
            }
            else -> false to ""
        }

        if (!isSupported) return null

        val segments = pathString.split('/')
            .map { it.trim() }
            .filter { it.isNotEmpty() }

        if (segments.isEmpty()) {
            return DeepLinkDestination.Home
        }

        val first = segments[0].lowercase()
        return when (first) {
            "lens", "surah", "quran" -> {
                val surahNumber = segments.getOrNull(1)?.toIntOrNull()
                if (surahNumber != null && surahNumber in 1..114) {
                    val verseNumber = segments.getOrNull(2)?.toIntOrNull()
                    DeepLinkDestination.Surah(surahNumber, verseNumber)
                } else {
                    DeepLinkDestination.Home
                }
            }
            "duas", "dua", "daily-dua" -> {
                val sub = segments.getOrNull(1)
                if (!sub.isNullOrBlank()) {
                    DeepLinkDestination.DuaDetail(sub)
                } else {
                    DeepLinkDestination.Duas
                }
            }
            "seerah", "seerah-daily" -> DeepLinkDestination.Seerah
            "quran-history", "history" -> DeepLinkDestination.History
            "quiz-learning", "themes", "quiz", "theme-quiz" -> DeepLinkDestination.Quiz
            "leaderboard" -> DeepLinkDestination.Leaderboard
            "home", "app" -> DeepLinkDestination.Home
            else -> null
        }
    }
}

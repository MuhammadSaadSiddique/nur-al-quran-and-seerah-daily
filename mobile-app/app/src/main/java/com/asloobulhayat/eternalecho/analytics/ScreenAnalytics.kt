package com.asloobulhayat.eternalecho.analytics

import android.content.Context
import android.os.Bundle
import androidx.navigation3.runtime.NavKey
import com.asloobulhayat.eternalecho.AuthRoute
import com.asloobulhayat.eternalecho.DuaDetailRoute
import com.asloobulhayat.eternalecho.DuasRoute
import com.asloobulhayat.eternalecho.InsightQuizRoute
import com.asloobulhayat.eternalecho.Main
import com.asloobulhayat.eternalecho.OnboardingRoute
import com.asloobulhayat.eternalecho.PlayThemeQuiz
import com.asloobulhayat.eternalecho.QuranResearchRoute
import com.asloobulhayat.eternalecho.ThemeQuizSelection
import com.asloobulhayat.eternalecho.data.SurahNavKey
import com.google.firebase.analytics.FirebaseAnalytics

/**
 * Firebase automatic screen reporting only sees Android activities.
 * This app's real navigation is Compose routes and bottom tabs, so those
 * are logged here as screen_view events.
 */
object ScreenAnalytics {
    fun logVisibleScreen(context: Context, route: NavKey?, mainTab: Int) {
        when (route) {
            null, is Main -> {
                val (name, clazz) = mainTabScreen(mainTab)
                log(context, name, clazz)
            }
            is OnboardingRoute -> log(context, "Onboarding", "OnboardingScreen")
            is DuasRoute -> log(context, "Duas", "DuasScreen")
            is DuaDetailRoute -> log(context, "Dua Detail", "DuaDetailScreen")
            is QuranResearchRoute -> log(context, "Quran Research", "QuranResearchScreen")
            is SurahNavKey -> log(
                context,
                "Surah",
                "SurahScreen",
                mapOf("surah_number" to route.surahNumber.toString()),
            )
            is ThemeQuizSelection -> Unit
            is PlayThemeQuiz -> log(
                context,
                "Quiz Play",
                "PlayThemeQuizScreen",
                mapOf(
                    "quiz_type" to route.quizType,
                    "theme_name" to route.themeName,
                ),
            )
            is AuthRoute -> log(context, "Auth", "AuthScreen")
            is InsightQuizRoute -> log(
                context,
                "Insight Quiz",
                "InsightQuizScreen",
                mapOf("mark_type" to route.markType),
            )
            else -> {
                val name = route::class.simpleName ?: "Unknown"
                log(context, name, name)
            }
        }
    }

    fun logQuizThemeTab(context: Context, tab: Int) {
        val name = when (tab) {
            0 -> "Quiz · Quran Themes"
            1 -> "Quiz · Seerah Themes"
            2 -> "Quiz · Grand Quiz"
            else -> "Quiz · Themes"
        }
        log(context, name, "ThemeSelectionScreen")
    }

    private fun mainTabScreen(tab: Int): Pair<String, String> = when (tab) {
        0 -> "Home" to "HomeTab"
        10 -> "Surahs" to "SurahsTab"
        5 -> "Lens" to "QuranicLensTab"
        2 -> "Seerah" to "SeerahTab"
        3 -> "History" to "HistoryTab"
        1 -> "Rank" to "LeaderboardTab"
        4 -> "Duas" to "DuasTab"
        else -> "Home" to "MainScreen"
    }

    private fun log(
        context: Context,
        screenName: String,
        screenClass: String,
        extras: Map<String, String> = emptyMap(),
    ) {
        try {
            val bundle = Bundle().apply {
                putString(FirebaseAnalytics.Param.SCREEN_NAME, screenName.take(100))
                putString(FirebaseAnalytics.Param.SCREEN_CLASS, screenClass.take(100))
                extras.forEach { (key, value) ->
                    putString(key.take(40), value.take(100))
                }
            }
            FirebaseAnalytics.getInstance(context.applicationContext)
                .logEvent(FirebaseAnalytics.Event.SCREEN_VIEW, bundle)
        } catch (_: Exception) {
            // Screen tracking must never interrupt navigation.
        }
    }
}

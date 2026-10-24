package com.asloobulhayat.eternalecho

import android.content.Context
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.safeDrawingPadding
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.navigation3.runtime.entryProvider
import androidx.navigation3.runtime.rememberNavBackStack
import androidx.navigation3.ui.NavDisplay
import com.asloobulhayat.eternalecho.ui.main.MainScreen
import com.asloobulhayat.eternalecho.ui.surah.SurahScreen
import com.asloobulhayat.eternalecho.ui.quiz.*
import com.asloobulhayat.eternalecho.ui.auth.*
import com.asloobulhayat.eternalecho.ui.onboarding.OnboardingScreen
import com.asloobulhayat.eternalecho.ui.duas.DuasScreen
import com.asloobulhayat.eternalecho.ui.duas.DuaDetailScreen
import com.asloobulhayat.eternalecho.ui.patterns.PatternsScreen
import com.asloobulhayat.eternalecho.ui.insights.InsightQuizScreen
import com.asloobulhayat.eternalecho.data.SurahNavKey
import com.asloobulhayat.eternalecho.security.SecurePreferences

import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.setValue
import com.asloobulhayat.eternalecho.analytics.ScreenAnalytics
import com.asloobulhayat.eternalecho.util.DeepLinkDestination

@Composable
fun MainNavigation(
    deepLinkDestination: DeepLinkDestination? = null,
    onDeepLinkHandled: () -> Unit = {},
    announcementsRequest: Int = 0
) {
  val context = LocalContext.current
  val prefs = remember { SecurePreferences.getInstance(context) }
  val hasCompletedOnboarding = remember { prefs.getBoolean("has_completed_onboarding", false) }

  val initialKey = if (hasCompletedOnboarding) Main else OnboardingRoute
  val backStack = rememberNavBackStack(initialKey)
  var mainTab by remember { mutableIntStateOf(0) }
  var tabRequestId by remember { mutableIntStateOf(0) }

  fun ensureMainUnderneath() {
    if (backStack.isEmpty() || backStack.lastOrNull() == OnboardingRoute) {
      backStack.clear()
      backStack.add(Main)
    }
  }

  fun showMainTab(tab: Int) {
    if (backStack.isEmpty() || backStack.lastOrNull() == OnboardingRoute) {
      backStack.clear()
      backStack.add(Main)
    } else {
      while (backStack.size > 1) {
        backStack.removeLastOrNull()
      }
    }
    mainTab = tab
    tabRequestId += 1
  }

  LaunchedEffect(deepLinkDestination) {
    deepLinkDestination?.let { destination ->
      when (destination) {
        is DeepLinkDestination.Surah -> {
          ensureMainUnderneath()
          backStack.add(SurahNavKey(destination.surahNumber, destination.verseNumber))
        }
        is DeepLinkDestination.Duas -> {
          ensureMainUnderneath()
          backStack.add(DuasRoute)
        }
        is DeepLinkDestination.DuaDetail -> {
          ensureMainUnderneath()
          val numId = destination.idOrSlug.toIntOrNull() ?: 0
          val slug = if (numId == 0) destination.idOrSlug else ""
          backStack.add(DuaDetailRoute(numId, slug))
        }
        is DeepLinkDestination.Quiz -> {
          ensureMainUnderneath()
          backStack.add(ThemeQuizSelection)
        }
        is DeepLinkDestination.Seerah -> showMainTab(2)
        is DeepLinkDestination.History -> showMainTab(3)
        is DeepLinkDestination.Leaderboard -> showMainTab(1)
        is DeepLinkDestination.Home -> showMainTab(0)
      }
      onDeepLinkHandled()
    }
  }

  // When returning to Main from Surah / Quiz / Insight / Dua, force home stats to reload.
  val topRoute = backStack.lastOrNull()
  LaunchedEffect(topRoute) {
    if (topRoute == Main) {
      com.asloobulhayat.eternalecho.data.EngagementBus.requestRefresh()
    }
  }

  LaunchedEffect(topRoute, mainTab) {
    ScreenAnalytics.logVisibleScreen(context, topRoute, mainTab)
  }

  NavDisplay(
    backStack = backStack,
    onBack = { backStack.removeLastOrNull() },
    entryProvider =
      entryProvider {
        entry<OnboardingRoute> {
          OnboardingScreen(
            onFinished = {
              backStack.clear()
              backStack.add(Main)
            }
          )
        }
        entry<Main> {
          MainScreen(
            onItemClick = { navKey -> backStack.add(navKey) },
            initialTab = mainTab,
            tabRequestId = tabRequestId,
            onTabSelected = { mainTab = it },
            announcementsRequest = announcementsRequest,
            modifier = Modifier
          )
        }
        entry<DuasRoute> {
          DuasScreen(
            onBackClick = { backStack.removeLastOrNull() },
            onDuaClick = { dua -> backStack.add(DuaDetailRoute(dua.id, dua.slug)) },
            modifier = Modifier.safeDrawingPadding().padding(4.dp)
          )
        }
        entry<DuaDetailRoute> { routeKey ->
          DuaDetailScreen(
            duaId = routeKey.duaId,
            duaSlug = routeKey.duaSlug,
            onBackClick = { backStack.removeLastOrNull() },
            onNavigateToDua = { nextId, nextSlug ->
              backStack.removeLastOrNull()
              backStack.add(DuaDetailRoute(nextId, nextSlug))
            },
            onSurahClick = { surah, verse ->
              backStack.add(SurahNavKey(surah, verse))
            },
            modifier = Modifier.safeDrawingPadding().padding(4.dp)
          )
        }
        entry<PatternsRoute> {
          PatternsScreen(
            onBackClick = { backStack.removeLastOrNull() },
            onOpenVerse = { chapter, verse -> backStack.add(SurahNavKey(chapter, verse)) },
            modifier = Modifier.safeDrawingPadding()
          )
        }
        entry<QuranResearchRoute> {
          com.asloobulhayat.eternalecho.ui.research.QuranResearchScreen(
            onBackClick = { backStack.removeLastOrNull() },
            onAuthClick = { backStack.add(AuthRoute) },
            modifier = Modifier.safeDrawingPadding().padding(4.dp)
          )
        }
        entry<SurahNavKey> { surahKey ->
          val surahNumber = surahKey.surahNumber
          val verseNumber = surahKey.verseNumber
          SurahScreen(
            surahNumber = surahNumber,
            initialVerseNumber = verseNumber,
            onBackClick = { backStack.removeLastOrNull() },
            onAuthClick = { backStack.add(AuthRoute) },
            modifier = Modifier.safeDrawingPadding().padding(4.dp)
          )
        }
        entry<ThemeQuizSelection> {
          ThemeSelectionScreen(
            onBackClick = { backStack.removeLastOrNull() },
            onThemeSelect = { theme, difficulty, quantity ->
                backStack.add(PlayThemeQuiz(theme.id, theme.name, difficulty, quantity, "THEME", System.currentTimeMillis()))
            },
            onGrandQuizLaunch = { quizType, title, difficulty, quantity ->
                backStack.add(PlayThemeQuiz(0, title, difficulty, quantity, quizType, System.currentTimeMillis()))
            },
            onAuthClick = { backStack.add(AuthRoute) },
            modifier = Modifier.safeDrawingPadding().padding(16.dp)
          )
        }
        entry<PlayThemeQuiz> { key ->
          PlayThemeQuizScreen(
            themeId = key.themeId,
            themeName = key.themeName,
            difficulty = key.difficulty,
            quantity = key.quantity,
            quizType = key.quizType,
            sessionId = key.sessionId,
            onBackClick = { backStack.removeLastOrNull() },
            onAuthClick = { backStack.add(AuthRoute) },
            modifier = Modifier.fillMaxSize()
          )
        }
        entry<AuthRoute> {
          AuthScreen(
            onBackClick = { backStack.removeLastOrNull() },
            modifier = Modifier.safeDrawingPadding().padding(16.dp)
          )
        }
        entry<InsightQuizRoute> { key ->
          InsightQuizScreen(
            markType = key.markType,
            questionId = key.questionId,
            insightTitle = key.insightTitle,
            questionText = key.questionText,
            options = key.options,
            onBackClick = { backStack.removeLastOrNull() },
            onAuthClick = { backStack.add(AuthRoute) },
            modifier = Modifier.safeDrawingPadding()
          )
        }
      },
  )
}


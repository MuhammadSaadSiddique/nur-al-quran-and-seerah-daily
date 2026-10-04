package com.asloobulhayat.eternalecho

import android.content.Context
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
import com.asloobulhayat.eternalecho.data.SurahNavKey
import com.asloobulhayat.eternalecho.security.SecurePreferences

@Composable
fun MainNavigation() {
  val context = LocalContext.current
  val prefs = remember { SecurePreferences.getInstance(context) }
  val hasCompletedOnboarding = remember { prefs.getBoolean("has_completed_onboarding", false) }

  val initialKey = if (hasCompletedOnboarding) Main else OnboardingRoute
  val backStack = rememberNavBackStack(initialKey)

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
          MainScreen(onItemClick = { navKey -> backStack.add(navKey) }, modifier = Modifier)
        }
        entry<DuasRoute> {
          DuasScreen(
            onBackClick = { backStack.removeLastOrNull() },
            modifier = Modifier.safeDrawingPadding().padding(4.dp)
          )
        }
        entry<SurahNavKey> { surahKey ->
          val surahNumber = surahKey.surahNumber
          SurahScreen(surahNumber = surahNumber, onBackClick = { backStack.removeLastOrNull() }, modifier = Modifier.safeDrawingPadding().padding(4.dp))
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
            modifier = Modifier.safeDrawingPadding().padding(16.dp)
          )
        }
        entry<AuthRoute> {
          AuthScreen(
            onBackClick = { backStack.removeLastOrNull() },
            modifier = Modifier.safeDrawingPadding().padding(16.dp)
          )
        }
      },
  )
}


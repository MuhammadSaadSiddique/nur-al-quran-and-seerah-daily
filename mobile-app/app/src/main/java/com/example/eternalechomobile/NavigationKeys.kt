package com.example.eternalechomobile

import androidx.navigation3.runtime.NavKey
import kotlinx.serialization.Serializable

@Serializable data object Main : NavKey
@Serializable data object ThemeQuizSelection : NavKey
@Serializable data class PlayThemeQuiz(val themeId: Int, val themeName: String, val difficulty: String,val sessionId: Long = System.currentTimeMillis()) : NavKey
@Serializable data object AuthRoute : NavKey

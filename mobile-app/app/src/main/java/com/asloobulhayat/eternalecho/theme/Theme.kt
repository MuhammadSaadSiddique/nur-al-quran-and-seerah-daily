package com.asloobulhayat.eternalecho.theme

import android.content.Context
import android.os.Build
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.dynamicDarkColorScheme
import androidx.compose.material3.dynamicLightColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import com.asloobulhayat.eternalecho.security.SecurePreferences
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

/**
 * Supported Theme Modes:
 * - APP_THEME: Signature Dark Green Islamic aesthetic.
 * - SYSTEM: Automatically follows device system settings (light/dark & dynamic colors on Android 12+).
 */
enum class ThemeMode(
    val storageKey: String,
    val title: String,
    val description: String
) {
    APP_THEME("app_theme", "App Theme (Dark Green)", "Signature deep Islamic emerald & forest green theme"),
    LIGHT("light", "Light Theme", "Clean, bright light emerald & slate theme"),
    SYSTEM("system", "System Theme", "Adapts automatically to your device system theme")
}

/**
 * Reactive Theme Manager for persisting and observing the active theme setting.
 */
object ThemeManager {
    private const val PREF_THEME_MODE = "setting_theme_mode"

    // Default to APP_THEME (Dark Green)
    private val _themeMode = MutableStateFlow(ThemeMode.APP_THEME)
    val themeMode: StateFlow<ThemeMode> = _themeMode.asStateFlow()

    fun init(context: Context) {
        val prefs = SecurePreferences.getInstance(context)
        val saved = prefs.getString(PREF_THEME_MODE, ThemeMode.APP_THEME.storageKey)
        val mode = ThemeMode.entries.find { it.storageKey == saved } ?: ThemeMode.APP_THEME
        _themeMode.value = mode
    }

    fun setThemeMode(context: Context, mode: ThemeMode) {
        val prefs = SecurePreferences.getInstance(context)
        prefs.putString(PREF_THEME_MODE, mode.storageKey)
        _themeMode.value = mode
    }

    fun getThemeMode(context: Context): ThemeMode {
        val prefs = SecurePreferences.getInstance(context)
        val saved = prefs.getString(PREF_THEME_MODE, ThemeMode.APP_THEME.storageKey)
        return ThemeMode.entries.find { it.storageKey == saved } ?: ThemeMode.APP_THEME
    }
}

/**
 * Rich Dark Green Color Scheme matching Islamic heritage design.
 */
val DarkGreenColorScheme = darkColorScheme(
    primary = DarkGreenPrimary,
    onPrimary = DarkGreenOnPrimary,
    primaryContainer = DarkGreenPrimaryContainer,
    onPrimaryContainer = DarkGreenOnPrimaryContainer,
    secondary = DarkGreenSecondary,
    onSecondary = DarkGreenOnSecondary,
    secondaryContainer = DarkGreenSecondaryContainer,
    onSecondaryContainer = DarkGreenOnSecondaryContainer,
    tertiary = DarkGreenTertiary,
    onTertiary = DarkGreenOnTertiary,
    tertiaryContainer = DarkGreenTertiaryContainer,
    onTertiaryContainer = DarkGreenOnTertiaryContainer,
    background = DarkGreenBackground,
    onBackground = DarkGreenOnBackground,
    surface = DarkGreenSurface,
    onSurface = DarkGreenOnSurface,
    surfaceVariant = DarkGreenSurfaceVariant,
    onSurfaceVariant = DarkGreenOnSurfaceVariant,
    outline = DarkGreenOutline,
    outlineVariant = DarkGreenOutlineVariant
)

/**
 * Standard Light Color Scheme for system mode.
 */
val LightColorScheme = lightColorScheme(
    primary = Emerald600,
    onPrimary = Color.White,
    primaryContainer = LightEmerald,
    onPrimaryContainer = TextEmerald,
    secondary = Slate600,
    onSecondary = Color.White,
    secondaryContainer = Slate200,
    onSecondaryContainer = Slate800,
    tertiary = Color(0xFF0D9488),
    onTertiary = Color.White,
    tertiaryContainer = Color(0xFFCCFBF1),
    onTertiaryContainer = Color(0xFF115E59),
    background = Slate50,
    onBackground = Slate900,
    surface = Color.White,
    onSurface = Slate900,
    surfaceVariant = Slate200,
    onSurfaceVariant = Slate600,
    outline = Slate500,
    outlineVariant = Slate200
)

@Composable
fun EternalEchoMobileTheme(
    forceThemeMode: ThemeMode? = null,
    dynamicColor: Boolean = true,
    content: @Composable () -> Unit,
) {
    val context = LocalContext.current
    LaunchedEffect(Unit) {
        ThemeManager.init(context)
    }

    val currentMode by ThemeManager.themeMode.collectAsState()
    val activeMode = forceThemeMode ?: currentMode
    val systemInDark = isSystemInDarkTheme()

    val colorScheme = when (activeMode) {
        ThemeMode.APP_THEME -> DarkGreenColorScheme
        ThemeMode.LIGHT -> LightColorScheme
        ThemeMode.SYSTEM -> {
            when {
                dynamicColor && Build.VERSION.SDK_INT >= Build.VERSION_CODES.S -> {
                    if (systemInDark) dynamicDarkColorScheme(context) else dynamicLightColorScheme(context)
                }
                systemInDark -> DarkGreenColorScheme
                else -> LightColorScheme
            }
        }
    }

    MaterialTheme(colorScheme = colorScheme, typography = Typography, content = content)
}

package com.asloobulhayat.eternalecho

import com.asloobulhayat.eternalecho.theme.DarkGreenBackground
import com.asloobulhayat.eternalecho.theme.DarkGreenColorScheme
import com.asloobulhayat.eternalecho.theme.DarkGreenPrimary
import com.asloobulhayat.eternalecho.theme.DarkGreenSurface
import com.asloobulhayat.eternalecho.theme.ThemeManager
import com.asloobulhayat.eternalecho.theme.ThemeMode
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
import org.junit.Test

class ThemeManagerTest {

    @Test
    fun testThemeModeEnumValues() {
        assertEquals("app_theme", ThemeMode.APP_THEME.storageKey)
        assertEquals("App Theme (Dark Green)", ThemeMode.APP_THEME.title)

        assertEquals("light", ThemeMode.LIGHT.storageKey)
        assertEquals("Light Theme", ThemeMode.LIGHT.title)

        assertEquals("system", ThemeMode.SYSTEM.storageKey)
        assertEquals("System Theme", ThemeMode.SYSTEM.title)
    }

    @Test
    fun testDarkGreenColorSchemeAttributes() {
        assertEquals(DarkGreenPrimary, DarkGreenColorScheme.primary)
        assertEquals(DarkGreenBackground, DarkGreenColorScheme.background)
        assertEquals(DarkGreenSurface, DarkGreenColorScheme.surface)
        assertNotNull(DarkGreenColorScheme.tertiary)
        assertNotNull(DarkGreenColorScheme.onPrimary)
        assertNotNull(DarkGreenColorScheme.onSurface)
    }

    @Test
    fun testDefaultThemeModeIsAppThemeDarkGreen() {
        // App Theme (Dark Green) should be default active theme
        assertNotNull(ThemeManager.themeMode.value)
    }
}

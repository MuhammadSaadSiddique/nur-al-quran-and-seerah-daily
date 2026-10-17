package com.asloobulhayat.eternalecho.ui.preview

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import com.asloobulhayat.eternalecho.theme.EternalEchoMobileTheme
import com.asloobulhayat.eternalecho.theme.ThemeMode
import com.asloobulhayat.eternalecho.ui.components.EchoCard
import com.asloobulhayat.eternalecho.ui.components.EchoChip
import com.asloobulhayat.eternalecho.ui.components.EchoPrimaryButton
import com.asloobulhayat.eternalecho.ui.components.EchoScreenBackground
import com.asloobulhayat.eternalecho.ui.components.EchoSecondaryButton
import com.asloobulhayat.eternalecho.ui.components.EchoSectionLabel
import com.asloobulhayat.eternalecho.ui.components.EchoTopBar
import com.asloobulhayat.eternalecho.ui.home.ContinueReadingCard
import com.asloobulhayat.eternalecho.ui.home.DailyVerseCard
import com.asloobulhayat.eternalecho.ui.home.FeatureCardsGrid
import com.asloobulhayat.eternalecho.ui.home.HomeHeaderSection
import com.asloobulhayat.eternalecho.ui.home.HomeScreen
import com.asloobulhayat.eternalecho.ui.home.rememberHomeThemePalette

/**
 * Compose Preview gallery for the refreshed Eternal Echo design system.
 * Open this file in Android Studio and use the Preview pane.
 */
@Preview(
    name = "Home · Dark Green",
    showBackground = true,
    backgroundColor = 0xFF051B14,
    widthDp = 390,
    heightDp = 844
)
@Composable
fun PreviewHomeDarkGreen() {
    EternalEchoMobileTheme(forceThemeMode = ThemeMode.APP_THEME, dynamicColor = false) {
        HomeScreen(
            userName = "Aisha",
            streakDays = 12,
            longestStreak = 21,
            isStreakActiveToday = true,
            currentJuz = 5,
            readPages = 18,
            readingProgressPercent = 42,
            showBottomBar = true
        )
    }
}

@Preview(
    name = "Home · Light",
    showBackground = true,
    backgroundColor = 0xFFF8FAFC,
    widthDp = 390,
    heightDp = 844
)
@Composable
fun PreviewHomeLight() {
    EternalEchoMobileTheme(forceThemeMode = ThemeMode.LIGHT, dynamicColor = false) {
        HomeScreen(
            userName = "Yusuf",
            streakDays = 3,
            longestStreak = 10,
            isStreakActiveToday = false,
            currentJuz = 2,
            readPages = 6,
            readingProgressPercent = 18,
            showBottomBar = true
        )
    }
}

@Preview(
    name = "Design System · Components",
    showBackground = true,
    widthDp = 390,
    heightDp = 900
)
@Composable
fun PreviewDesignSystemComponents() {
    EternalEchoMobileTheme(forceThemeMode = ThemeMode.APP_THEME, dynamicColor = false) {
        Scaffold(
            containerColor = Color.Transparent,
            topBar = {
                EchoTopBar(
                    title = "Design system",
                    onBackClick = {},
                    navigationIcon = Icons.AutoMirrored.Filled.ArrowBack
                )
            }
        ) { padding ->
            EchoScreenBackground {
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(padding)
                        .padding(16.dp)
                        .verticalScroll(rememberScrollState()),
                    verticalArrangement = Arrangement.spacedBy(14.dp)
                ) {
                    EchoSectionLabel(text = "Chips")
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        EchoChip(
                            label = "Easy",
                            selected = true,
                            onClick = {},
                            modifier = Modifier.weight(1f)
                        )
                        EchoChip(
                            label = "Medium",
                            selected = false,
                            onClick = {},
                            modifier = Modifier.weight(1f)
                        )
                        EchoChip(
                            label = "Hard",
                            selected = false,
                            onClick = {},
                            modifier = Modifier.weight(1f)
                        )
                    }

                    EchoSectionLabel(text = "Cards")
                    EchoCard {
                        Text(
                            text = "Calm surface card",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold
                        )
                        Text(
                            text = "Shared radius, soft border, no heavy elevation.",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier.padding(top = 4.dp)
                        )
                    }
                    EchoCard(highlighted = true) {
                        Text(
                            text = "Highlighted card",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold
                        )
                        Text(
                            text = "Used for selected themes and active states.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }

                    EchoSectionLabel(text = "Actions")
                    EchoPrimaryButton(text = "Continue reading", onClick = {})
                    EchoSecondaryButton(text = "Maybe later", onClick = {})
                    // Keep preview height stable for tooling
                    Row(modifier = Modifier.height(8.dp)) {}
                }
            }
        }
    }
}

@Preview(
    name = "Home Sections · Strip",
    showBackground = true,
    backgroundColor = 0xFF051B14,
    widthDp = 390,
    heightDp = 1100
)
@Composable
fun PreviewHomeSectionsStrip() {
    EternalEchoMobileTheme(forceThemeMode = ThemeMode.APP_THEME, dynamicColor = false) {
        val palette = rememberHomeThemePalette()
        EchoScreenBackground {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(rememberScrollState())
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(14.dp)
            ) {
                HomeHeaderSection(
                    userName = "Aisha",
                    streakDays = 12,
                    currentJuz = 5,
                    readPages = 18,
                    readingProgressPercent = 42,
                    palette = palette,
                    onReadingProgressClick = {},
                    onStreakClick = {},
                    onThemeClick = {}
                )
                ContinueReadingCard(
                    currentJuz = 5,
                    readPages = 18,
                    readingProgressPercent = 42,
                    palette = palette,
                    onContinueClick = {}
                )
                DailyVerseCard(palette = palette, onReflectClick = {})
                FeatureCardsGrid(
                    palette = palette,
                    onStartQuizClick = {},
                    onExploreHistoryClick = {},
                    onDeepDiveClick = {},
                    onReadDuasClick = {}
                )
            }
        }
    }
}

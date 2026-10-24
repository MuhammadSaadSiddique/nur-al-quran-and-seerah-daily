package com.asloobulhayat.eternalecho.ui.home

import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.animation.core.tween
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowForward
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.outlined.Star
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.platform.LocalContext
import com.asloobulhayat.eternalecho.data.DailyEngagement
import com.asloobulhayat.eternalecho.data.DefaultDataRepository
import com.asloobulhayat.eternalecho.data.EngagementActivity
import com.asloobulhayat.eternalecho.security.SecurePreferences
import kotlinx.coroutines.launch
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.geometry.CornerRadius
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.drawscope.Fill
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.graphics.luminance
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.asloobulhayat.eternalecho.theme.ArabicVerseFont
import com.asloobulhayat.eternalecho.theme.DarkGreenBackground
import com.asloobulhayat.eternalecho.theme.ThemeManager
import com.asloobulhayat.eternalecho.theme.ThemeSettingDialog
import com.asloobulhayat.eternalecho.ui.components.FadeSlideIn
import com.asloobulhayat.eternalecho.ui.components.PressableScale

/**
 * Adaptive Home Screen color palette derived from the active [MaterialTheme.colorScheme].
 * Automatically adapts to:
 * - Signature Dark Green App Theme
 * - Light Theme
 * - System Light / Dark & Material You Dynamic Colors
 */
@Immutable
data class HomeThemePalette(
    val isDark: Boolean,
    val bgTop: Color,
    val bgBottom: Color,
    val cardBg: Color,
    val cardBorder: Color,
    val textPrimary: Color,
    val textSecondary: Color,
    val textMuted: Color,
    val arabicGreeting: Color,
    val primaryAccent: Color,
    val primaryButtonBg: Color,
    val primaryButtonContent: Color,
    val actionButtonBg: Color,
    val actionButtonBorder: Color,
    val actionButtonContent: Color,
    val ribbonColor: Color,
    val goldAccent: Color,
    val streakAccent: Color,
    val streakBadgeBg: Color,
    val streakBadgeBorder: Color,
    val sheetBg: Color,
    val sheetCardBg: Color,
    val quoteCardBg: Color,
    val navBarBg: Color,
    val navBarIndicator: Color
)

@Composable
fun rememberHomeThemePalette(): HomeThemePalette {
    val scheme = MaterialTheme.colorScheme
    val isDark = scheme.background.luminance() < 0.5f
    val isSignatureDarkGreen = scheme.background == DarkGreenBackground

    return remember(scheme, isDark, isSignatureDarkGreen) {
        when {
            isSignatureDarkGreen -> HomeThemePalette(
                isDark = true,
                bgTop = Color(0xFF0D221D),
                bgBottom = Color(0xFF071210),
                cardBg = Color(0xFF102521),
                cardBorder = Color(0xFF1B433A),
                textPrimary = Color.White,
                textSecondary = Color(0xFFE2E8F0),
                textMuted = Color(0xFF94A3B8),
                arabicGreeting = Color(0xFF6EE7B7),
                primaryAccent = Color(0xFF10B981),
                primaryButtonBg = Color(0xFF059669),
                primaryButtonContent = Color.White,
                actionButtonBg = Color(0xFF0F2621),
                actionButtonBorder = Color(0xFF1E5246),
                actionButtonContent = Color.White,
                ribbonColor = Color(0xFF059669),
                goldAccent = Color(0xFFFBBF24),
                streakAccent = Color(0xFFFB923C),
                streakBadgeBg = Color(0x25FB923C),
                streakBadgeBorder = Color(0x40FB923C),
                sheetBg = Color(0xFF0F2621),
                sheetCardBg = Color(0xFF132F28),
                quoteCardBg = Color(0xFF0B1E1A),
                navBarBg = Color(0xFF091412),
                navBarIndicator = Color(0xFF102E26)
            )
            isDark -> HomeThemePalette(
                isDark = true,
                bgTop = scheme.background,
                bgBottom = scheme.surface,
                cardBg = scheme.surfaceVariant.copy(alpha = 0.55f),
                cardBorder = scheme.outlineVariant,
                textPrimary = scheme.onBackground,
                textSecondary = scheme.onSurface,
                textMuted = scheme.onSurfaceVariant,
                arabicGreeting = scheme.primary,
                primaryAccent = scheme.primary,
                primaryButtonBg = scheme.primary,
                primaryButtonContent = scheme.onPrimary,
                actionButtonBg = scheme.primaryContainer.copy(alpha = 0.45f),
                actionButtonBorder = scheme.primary.copy(alpha = 0.5f),
                actionButtonContent = scheme.onSurface,
                ribbonColor = scheme.primary,
                goldAccent = Color(0xFFFBBF24),
                streakAccent = Color(0xFFFB923C),
                streakBadgeBg = Color(0x25FB923C),
                streakBadgeBorder = Color(0x40FB923C),
                sheetBg = scheme.surface,
                sheetCardBg = scheme.surfaceVariant,
                quoteCardBg = scheme.surfaceVariant.copy(alpha = 0.7f),
                navBarBg = scheme.surface,
                navBarIndicator = scheme.secondaryContainer
            )
            else -> HomeThemePalette(
                isDark = false,
                bgTop = scheme.background,
                bgBottom = scheme.primaryContainer.copy(alpha = 0.22f),
                cardBg = scheme.surface,
                cardBorder = scheme.outlineVariant,
                textPrimary = scheme.onSurface,
                textSecondary = scheme.onSurfaceVariant,
                textMuted = scheme.onSurfaceVariant.copy(alpha = 0.85f),
                arabicGreeting = scheme.primary,
                primaryAccent = scheme.primary,
                primaryButtonBg = scheme.primary,
                primaryButtonContent = scheme.onPrimary,
                actionButtonBg = scheme.primaryContainer.copy(alpha = 0.45f),
                actionButtonBorder = scheme.primary.copy(alpha = 0.35f),
                actionButtonContent = scheme.onPrimaryContainer,
                ribbonColor = scheme.primary,
                goldAccent = Color(0xFFD97706),
                streakAccent = Color(0xFFEA580C),
                streakBadgeBg = Color(0xFFFFF7ED),
                streakBadgeBorder = Color(0xFFFED7AA),
                sheetBg = scheme.surface,
                sheetCardBg = scheme.primaryContainer.copy(alpha = 0.3f),
                quoteCardBg = scheme.surfaceVariant.copy(alpha = 0.55f),
                navBarBg = scheme.surface,
                navBarIndicator = scheme.primaryContainer
            )
        }
    }
}

/**
 * Main Home Dashboard adaptable to the user's selected theme.
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScreen(
    userName: String = "Wisdom Seeker",
    streakDays: Int = 7,
    longestStreak: Int = 7,
    isStreakActiveToday: Boolean = false,
    currentJuz: Int = 2,
    readPages: Int = 5,
    readingProgressPercent: Int = 0,
    dailyEngagement: DailyEngagement? = null,
    onStartQuizClick: () -> Unit = {},
    onExploreHistoryClick: () -> Unit = {},
    onDeepDiveClick: () -> Unit = {},
    onReadDuasClick: () -> Unit = {},
    onQuranPatternsClick: () -> Unit = {},
    onReflectClick: () -> Unit = {},
    onReadingProgressClick: () -> Unit = {},
    onClaimStreakClick: () -> Unit = {},
    onEngagementActivityClick: (String) -> Unit = {},
    onThemeSettingsClick: (() -> Unit)? = null,
    onSubmitFeedback: (suspend (rating: Int, category: String, message: String) -> Result<String>)? = null,
    selectedBottomNavIndex: Int = 0,
    onBottomNavSelected: (Int) -> Unit = {},
    showBottomBar: Boolean = true,
    modifier: Modifier = Modifier
) {
    val progressPercent = dailyEngagement?.progressPercent ?: readingProgressPercent
    val context = LocalContext.current
    val prefs = remember { SecurePreferences.getInstance(context) }
    val scope = rememberCoroutineScope()
    val repository = remember { DefaultDataRepository() }

    val palette = rememberHomeThemePalette()
    var showReflectionSheet by remember { mutableStateOf(false) }
    var showStreakDialog by remember { mutableStateOf(false) }
    var showLocalThemeDialog by remember { mutableStateOf(false) }
    var showFeedbackSheet by remember { mutableStateOf(false) }

    val scrollState = rememberScrollState()

    if (showLocalThemeDialog) {
        ThemeSettingDialog(
            onDismiss = { showLocalThemeDialog = false }
        )
    }

    if (showStreakDialog) {
        DailyStreakDialog(
            currentStreak = streakDays,
            longestStreak = longestStreak,
            isStreakActiveToday = isStreakActiveToday,
            palette = palette,
            onDismiss = { showStreakDialog = false },
            onClaimStreak = {
                onClaimStreakClick()
            }
        )
    }

    if (showFeedbackSheet) {
        AppFeedbackBottomSheet(
            palette = palette,
            onDismiss = {
                showFeedbackSheet = false
                prefs.putLong("feedback_dismissed_timestamp", System.currentTimeMillis())
            },
            onSubmitFeedback = { rating, category, message, onSuccess, onError ->
                scope.launch {
                    val result = if (onSubmitFeedback != null) {
                        onSubmitFeedback(rating, category, message)
                    } else {
                        val userId = prefs.getInt("user_id", -1)
                        val storedUserName = prefs.getString("user_name", "") ?: ""
                        val storedUserEmail = prefs.getString("user_email", "") ?: ""
                        repository.submitAppFeedback(
                            rating = rating,
                            category = category,
                            message = message,
                            userId = if (userId > 0) userId else null,
                            userName = storedUserName.ifBlank { null },
                            userEmail = storedUserEmail.ifBlank { null },
                            deviceInfo = "${android.os.Build.MANUFACTURER} ${android.os.Build.MODEL} (Android ${android.os.Build.VERSION.RELEASE})"
                        )
                    }

                    if (result.isSuccess) {
                        prefs.putBoolean("feedback_submitted", true)
                        onSuccess()
                    } else {
                        onError(result.exceptionOrNull()?.message ?: "Unable to submit feedback.")
                    }
                }
            }
        )
    }

    if (showReflectionSheet) {
        ModalBottomSheet(
            onDismissRequest = { showReflectionSheet = false },
            containerColor = palette.sheetBg,
            contentColor = palette.textPrimary
        ) {
            ReflectionSheetContent(
                palette = palette,
                onClose = { showReflectionSheet = false },
                onOpenFullVerse = {
                    showReflectionSheet = false
                    onReflectClick()
                }
            )
        }
    }

    Scaffold(
        modifier = modifier.fillMaxSize(),
        containerColor = Color.Transparent,
        bottomBar = {
            if (showBottomBar) {
                HomeBottomNavigationBar(
                    selectedIndex = selectedBottomNavIndex,
                    palette = palette,
                    onItemSelected = onBottomNavSelected
                )
            }
        }
    ) { _ ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(
                    brush = Brush.verticalGradient(
                        colors = listOf(palette.bgTop, palette.bgBottom)
                    )
                )
        ) {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(scrollState)
                    .padding(horizontal = 16.dp, vertical = 12.dp)
                    .padding(bottom = if (showBottomBar) 8.dp else 0.dp)
            ) {
                FadeSlideIn(delayMillis = 0) {
                    HomeHeaderSection(
                        userName = userName,
                        streakDays = streakDays,
                        currentJuz = currentJuz,
                        readPages = readPages,
                        readingProgressPercent = progressPercent,
                        palette = palette,
                        onReadingProgressClick = onReadingProgressClick,
                        onStreakClick = { showStreakDialog = true },
                        onThemeClick = {
                            if (onThemeSettingsClick != null) {
                                onThemeSettingsClick()
                            } else {
                                showLocalThemeDialog = true
                            }
                        }
                    )
                }

                Spacer(modifier = Modifier.height(14.dp))

                FadeSlideIn(delayMillis = 40) {
                    DailyEngagementChecklistCard(
                        engagement = dailyEngagement,
                        palette = palette,
                        onActivityClick = onEngagementActivityClick
                    )
                }

                Spacer(modifier = Modifier.height(14.dp))

                FadeSlideIn(delayMillis = 60) {
                    ContinueReadingCard(
                        currentJuz = currentJuz,
                        readPages = readPages,
                        readingProgressPercent = progressPercent,
                        palette = palette,
                        onContinueClick = onReadingProgressClick
                    )
                }

                Spacer(modifier = Modifier.height(14.dp))

                FadeSlideIn(delayMillis = 120) {
                    DailyVerseCard(
                        palette = palette,
                        onReflectClick = { showReflectionSheet = true }
                    )
                }

                Spacer(modifier = Modifier.height(14.dp))

                FadeSlideIn(delayMillis = 180) {
                    FeatureCardsGrid(
                        palette = palette,
                        onStartQuizClick = onStartQuizClick,
                        onExploreHistoryClick = onExploreHistoryClick,
                        onDeepDiveClick = onDeepDiveClick,
                        onReadDuasClick = onReadDuasClick,
                        onQuranPatternsClick = onQuranPatternsClick
                    )
                }

                Spacer(modifier = Modifier.height(14.dp))

                FadeSlideIn(delayMillis = 240) {
                    HomeFeedbackCard(
                        palette = palette,
                        onOpenFeedback = { showFeedbackSheet = true }
                    )
                }

                Spacer(modifier = Modifier.height(24.dp))
            }
        }
    }
}

/**
 * Today's Devotion checklist with progress ring.
 */
@Composable
fun DailyEngagementChecklistCard(
    engagement: DailyEngagement?,
    palette: HomeThemePalette,
    onActivityClick: (String) -> Unit = {}
) {
    val activities = engagement?.activities ?: listOf(
        EngagementActivity("quiz", false, "Complete a quiz"),
        EngagementActivity("read", false, "Read a Surah or Para"),
        EngagementActivity("insight", false, "Read a Seerah or Quran insight"),
        EngagementActivity("dua", false, "Open today's dua")
    )
    val progress = (engagement?.progressPercent ?: 0).coerceIn(0, 100)
    val completed = engagement?.completedCount ?: 0
    val total = engagement?.total ?: 4
    val animatedProgress by animateFloatAsState(
        targetValue = progress / 100f,
        animationSpec = tween(700),
        label = "engagementProgress"
    )

    Card(
        modifier = Modifier
            .fillMaxWidth()
            .testTag("daily_engagement_card"),
        shape = RoundedCornerShape(24.dp),
        colors = CardDefaults.cardColors(containerColor = palette.cardBg),
        border = BorderStroke(1.dp, palette.cardBorder)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Box(
                contentAlignment = Alignment.Center,
                modifier = Modifier.size(72.dp)
            ) {
                Canvas(modifier = Modifier.fillMaxSize()) {
                    val stroke = 8.dp.toPx()
                    drawArc(
                        color = palette.cardBorder,
                        startAngle = -90f,
                        sweepAngle = 360f,
                        useCenter = false,
                        style = Stroke(width = stroke, cap = StrokeCap.Round)
                    )
                    drawArc(
                        color = palette.primaryAccent,
                        startAngle = -90f,
                        sweepAngle = 360f * animatedProgress,
                        useCenter = false,
                        style = Stroke(width = stroke, cap = StrokeCap.Round)
                    )
                }
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text(
                        text = "$progress%",
                        color = palette.textPrimary,
                        fontWeight = FontWeight.Black,
                        fontSize = 14.sp
                    )
                    Text(
                        text = "$completed/$total",
                        color = palette.textMuted,
                        fontSize = 9.sp,
                        fontWeight = FontWeight.Bold
                    )
                }
            }

            Spacer(modifier = Modifier.width(14.dp))

            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = "Today's Devotion",
                    color = palette.textPrimary,
                    fontWeight = FontWeight.Black,
                    fontSize = 15.sp
                )
                Text(
                    text = if (engagement == null) {
                        "Sign in to sync today's devotion checklist"
                    } else {
                        "Complete any habit to keep your streak"
                    },
                    color = palette.textSecondary,
                    fontSize = 11.sp,
                    modifier = Modifier.padding(bottom = 8.dp)
                )
                activities.forEach { activity ->
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clip(RoundedCornerShape(10.dp))
                            .clickable { onActivityClick(activity.key) }
                            .padding(vertical = 4.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text(
                            text = if (activity.done) "✓" else "○",
                            color = if (activity.done) palette.primaryAccent else palette.textMuted,
                            fontWeight = FontWeight.Bold,
                            fontSize = 13.sp,
                            modifier = Modifier.width(18.dp)
                        )
                        Text(
                            text = activity.label,
                            color = if (activity.done) palette.textMuted else palette.textPrimary,
                            fontSize = 12.sp,
                            fontWeight = FontWeight.SemiBold
                        )
                    }
                }
            }
        }
    }
}

/**
 * Top Header with Greeting, Profile Name, Streak, and Theme Selector button.
 */
@Composable
fun HomeHeaderSection(
    userName: String,
    streakDays: Int,
    currentJuz: Int,
    readPages: Int,
    readingProgressPercent: Int,
    palette: HomeThemePalette = rememberHomeThemePalette(),
    onReadingProgressClick: () -> Unit,
    onStreakClick: () -> Unit = {},
    onThemeClick: () -> Unit = {}
) {
    val currentThemeMode by ThemeManager.themeMode.collectAsState()

    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically
    ) {
        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = "السَّلَامُ عَلَيْكُمْ",
                color = palette.arabicGreeting,
                fontSize = 15.sp,
                fontWeight = FontWeight.SemiBold,
                fontFamily = ArabicVerseFont
            )

            Spacer(modifier = Modifier.height(2.dp))

            Text(
                text = userName.ifBlank { "Wisdom Seeker" },
                color = palette.textPrimary,
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold
            )

            Spacer(modifier = Modifier.height(8.dp))

            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                Surface(
                    shape = RoundedCornerShape(20.dp),
                    color = palette.streakBadgeBg,
                    border = BorderStroke(1.dp, palette.streakBadgeBorder),
                    modifier = Modifier
                        .clip(RoundedCornerShape(20.dp))
                        .clickable { onStreakClick() }
                        .testTag("daily_streak_badge")
                ) {
                    Text(
                        text = "$streakDays day streak",
                        color = palette.streakAccent,
                        fontSize = 12.sp,
                        fontWeight = FontWeight.SemiBold,
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 5.dp)
                    )
                }

                Surface(
                    shape = RoundedCornerShape(20.dp),
                    color = palette.cardBg,
                    border = BorderStroke(1.dp, palette.cardBorder),
                    modifier = Modifier
                        .clip(RoundedCornerShape(20.dp))
                        .clickable { onThemeClick() }
                        .testTag("home_theme_badge")
                ) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(4.dp),
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 5.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.Settings,
                            contentDescription = "Change Theme",
                            tint = palette.primaryAccent,
                            modifier = Modifier.size(13.dp)
                        )
                        Text(
                            text = when (currentThemeMode) {
                                com.asloobulhayat.eternalecho.theme.ThemeMode.APP_THEME -> "Dark Green"
                                com.asloobulhayat.eternalecho.theme.ThemeMode.LIGHT -> "Light"
                                com.asloobulhayat.eternalecho.theme.ThemeMode.SYSTEM -> "System"
                            },
                            color = palette.textSecondary,
                            fontSize = 12.sp,
                            fontWeight = FontWeight.Medium
                        )
                    }
                }
            }
        }

        Spacer(modifier = Modifier.width(12.dp))

        ReadingProgressRing(
            percent = readingProgressPercent.coerceIn(0, 100),
            currentJuz = currentJuz,
            palette = palette,
            onClick = onReadingProgressClick
        )
    }
}

/**
 * Compact circular reading progress — one tap back into the Quran.
 */
@Composable
fun ReadingProgressRing(
    percent: Int,
    currentJuz: Int,
    palette: HomeThemePalette,
    onClick: () -> Unit
) {
    val animatedProgress by animateFloatAsState(
        targetValue = percent / 100f,
        animationSpec = tween(900),
        label = "reading_progress"
    )

    Box(
        modifier = Modifier
            .size(76.dp)
            .clip(CircleShape)
            .clickable(onClick = onClick)
            .testTag("reading_progress_ring"),
        contentAlignment = Alignment.Center
    ) {
        Canvas(modifier = Modifier.fillMaxSize().padding(3.dp)) {
            val stroke = 5.dp.toPx()
            drawCircle(
                color = palette.cardBorder,
                style = Stroke(width = stroke)
            )
            drawArc(
                color = palette.primaryAccent,
                startAngle = -90f,
                sweepAngle = 360f * animatedProgress,
                useCenter = false,
                style = Stroke(width = stroke, cap = StrokeCap.Round)
            )
        }
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            Text(
                text = "$percent%",
                color = palette.textPrimary,
                fontWeight = FontWeight.Bold,
                fontSize = 15.sp
            )
            Text(
                text = "Juz $currentJuz",
                color = palette.textMuted,
                fontSize = 10.sp,
                fontWeight = FontWeight.Medium
            )
        }
    }
}

/**
 * Primary “continue where you left off” action — reduces decision fatigue.
 */
@Composable
fun ContinueReadingCard(
    currentJuz: Int,
    readPages: Int,
    readingProgressPercent: Int,
    palette: HomeThemePalette,
    onContinueClick: () -> Unit
) {
    PressableScale(onClick = onContinueClick) {
        Card(
            shape = RoundedCornerShape(18.dp),
            colors = CardDefaults.cardColors(containerColor = Color.Transparent),
            border = BorderStroke(1.dp, palette.primaryAccent.copy(alpha = 0.35f)),
            modifier = Modifier
                .fillMaxWidth()
                .testTag("continue_reading_card")
        ) {
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .background(
                        brush = Brush.horizontalGradient(
                            colors = listOf(
                                palette.primaryAccent.copy(alpha = 0.22f),
                                palette.cardBg
                            )
                        )
                    )
                    .padding(horizontal = 16.dp, vertical = 14.dp)
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Column(modifier = Modifier.weight(1f)) {
                        Text(
                            text = "Continue reading",
                            color = palette.textPrimary,
                            fontWeight = FontWeight.Bold,
                            fontSize = 15.sp
                        )
                        Spacer(modifier = Modifier.height(2.dp))
                        Text(
                            text = "Juz $currentJuz · $readPages pages · $readingProgressPercent% of journey",
                            color = palette.textMuted,
                            fontSize = 12.sp
                        )
                    }
                    Surface(
                        shape = RoundedCornerShape(12.dp),
                        color = palette.primaryButtonBg
                    ) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier.padding(horizontal = 12.dp, vertical = 8.dp)
                        ) {
                            Text(
                                text = "Open",
                                color = palette.primaryButtonContent,
                                fontWeight = FontWeight.Bold,
                                fontSize = 12.sp
                            )
                            Spacer(modifier = Modifier.width(4.dp))
                            Icon(
                                imageVector = Icons.AutoMirrored.Filled.ArrowForward,
                                contentDescription = null,
                                tint = palette.primaryButtonContent,
                                modifier = Modifier.size(14.dp)
                            )
                        }
                    }
                }
            }
        }
    }
}

/**
 * Daily Verse & Reflection Card with Arabic ayah, English translation, and Reflect button.
 */
@Composable
fun DailyVerseCard(
    palette: HomeThemePalette = rememberHomeThemePalette(),
    onReflectClick: () -> Unit
) {
    Card(
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(containerColor = palette.quoteCardBg),
        border = BorderStroke(1.dp, palette.cardBorder),
        modifier = Modifier.fillMaxWidth()
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(20.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(
                text = "Today’s verse",
                style = MaterialTheme.typography.labelMedium,
                fontWeight = FontWeight.Bold,
                color = palette.arabicGreeting,
                modifier = Modifier.fillMaxWidth()
            )

            Spacer(modifier = Modifier.height(14.dp))

            Text(
                text = "رَبَّنَا لَا تُؤَاخِذْنَا إِن نَّسِينَا أَوْ أَخْطَأْنَا ۚ",
                color = palette.textPrimary,
                fontSize = 22.sp,
                fontWeight = FontWeight.Medium,
                fontFamily = ArabicVerseFont,
                textAlign = TextAlign.Center,
                lineHeight = 40.sp,
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 4.dp)
            )

            Spacer(modifier = Modifier.height(12.dp))

            Text(
                text = "Our Lord, do not take us to task if we forget or make mistakes. — Quran 2:286",
                color = palette.textSecondary,
                fontSize = 13.sp,
                textAlign = TextAlign.Center,
                lineHeight = 19.sp,
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 6.dp)
            )

            Spacer(modifier = Modifier.height(16.dp))

            Button(
                onClick = onReflectClick,
                colors = ButtonDefaults.buttonColors(
                    containerColor = palette.primaryButtonBg,
                    contentColor = palette.primaryButtonContent
                ),
                shape = RoundedCornerShape(12.dp),
                contentPadding = PaddingValues(horizontal = 28.dp, vertical = 10.dp),
                modifier = Modifier
                    .fillMaxWidth()
                    .testTag("reflect_button")
            ) {
                Text(
                    text = "Reflect on this ayah",
                    color = palette.primaryButtonContent,
                    fontWeight = FontWeight.SemiBold,
                    fontSize = 14.sp
                )
            }
        }
    }
}

/**
 * 2x2 Feature Cards Grid: Para Quizzes, Seerah Timeline, Quranic Lens, Daily Duas.
 */
@Composable
fun FeatureCardsGrid(
    palette: HomeThemePalette = rememberHomeThemePalette(),
    onStartQuizClick: () -> Unit,
    onExploreHistoryClick: () -> Unit,
    onDeepDiveClick: () -> Unit,
    onReadDuasClick: () -> Unit,
    onQuranPatternsClick: () -> Unit = {}
) {
    Column(
        modifier = Modifier.fillMaxWidth(),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        // Row 1: Para Quizzes & Seerah Timeline
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Box(modifier = Modifier.weight(1f)) {
                HomeFeatureCard(
                    title = "Para Quizzes",
                    subtitle = "Juz 1–30",
                    buttonText = "Start",
                    palette = palette,
                    onButtonClick = onStartQuizClick,
                    illustration = { QuizIllustration(palette) }
                )
            }
            Box(modifier = Modifier.weight(1f)) {
                HomeFeatureCard(
                    title = "Seerah",
                    subtitle = "Prophetic timeline",
                    buttonText = "Explore",
                    palette = palette,
                    onButtonClick = onExploreHistoryClick,
                    illustration = { ScrollIllustration(palette) }
                )
            }
        }

        // Row 2: Quranic Lens & Daily Duas
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Box(modifier = Modifier.weight(1f)) {
                HomeFeatureCard(
                    title = "Quranic Lens",
                    subtitle = "Ayah connections",
                    buttonText = "Explore",
                    palette = palette,
                    onButtonClick = onDeepDiveClick,
                    illustration = { QuranLensIllustration(palette) }
                )
            }
            Box(modifier = Modifier.weight(1f)) {
                HomeFeatureCard(
                    title = "Daily Duas",
                    subtitle = "Word by word",
                    buttonText = "Read",
                    palette = palette,
                    onButtonClick = onReadDuasClick,
                    illustration = { DuasIllustration(palette) }
                )
            }
        }

        HomeFeatureCard(
            title = "Quran Patterns",
            subtitle = "Ring, math, language",
            buttonText = "Search",
            palette = palette,
            onButtonClick = onQuranPatternsClick,
            illustration = { PatternsIllustration(palette) }
        )
    }
}

/**
 * Individual Feature Card with Bookmark Ribbon, Illustration, Title, and Action Button.
 */
@Composable
fun HomeFeatureCard(
    title: String,
    subtitle: String,
    buttonText: String,
    palette: HomeThemePalette = rememberHomeThemePalette(),
    onButtonClick: () -> Unit,
    illustration: @Composable () -> Unit,
    modifier: Modifier = Modifier
) {
    PressableScale(onClick = onButtonClick, modifier = modifier.fillMaxWidth()) {
        Card(
            shape = RoundedCornerShape(18.dp),
            colors = CardDefaults.cardColors(containerColor = palette.cardBg),
            border = BorderStroke(1.dp, palette.cardBorder),
            modifier = Modifier.fillMaxWidth()
        ) {
            Box(modifier = Modifier.fillMaxWidth()) {
                BookmarkRibbon(
                    color = palette.ribbonColor,
                    modifier = Modifier
                        .align(Alignment.TopEnd)
                        .padding(end = 12.dp)
                )

                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 12.dp, vertical = 14.dp),
                    horizontalAlignment = Alignment.Start
                ) {
                    Text(
                        text = title,
                        color = palette.textPrimary,
                        fontWeight = FontWeight.Bold,
                        fontSize = 14.sp,
                        lineHeight = 18.sp
                    )
                    if (subtitle.isNotEmpty()) {
                        Text(
                            text = subtitle,
                            color = palette.textMuted,
                            fontSize = 11.sp
                        )
                    } else {
                        Spacer(modifier = Modifier.height(14.dp))
                    }

                    Spacer(modifier = Modifier.height(4.dp))

                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(60.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        illustration()
                    }

                    Spacer(modifier = Modifier.height(10.dp))

                    Surface(
                        shape = RoundedCornerShape(10.dp),
                        color = palette.actionButtonBg,
                        border = BorderStroke(1.dp, palette.actionButtonBorder),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text(
                            text = buttonText,
                            fontSize = 12.sp,
                            fontWeight = FontWeight.SemiBold,
                            color = palette.actionButtonContent,
                            textAlign = TextAlign.Center,
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(vertical = 8.dp)
                        )
                    }
                }
            }
        }
    }
}

/**
 * Bookmark Ribbon indicator rendered in the top-right corner, tinted with the active theme's primary color.
 */
@Composable
fun BookmarkRibbon(
    color: Color = MaterialTheme.colorScheme.primary,
    modifier: Modifier = Modifier
) {
    Canvas(modifier = modifier.size(width = 16.dp, height = 22.dp)) {
        val path = Path().apply {
            moveTo(0f, 0f)
            lineTo(size.width, 0f)
            lineTo(size.width, size.height)
            lineTo(size.width / 2f, size.height - 6.dp.toPx())
            lineTo(0f, size.height)
            close()
        }
        drawPath(path = path, color = color)
    }
}

// ----------------- ILLUSTRATIONS -----------------

/**
 * Para Quizzes: Speech bubble with question mark and golden stars.
 */
@Composable
fun QuizIllustration(palette: HomeThemePalette = rememberHomeThemePalette()) {
    Canvas(modifier = Modifier.size(54.dp, 46.dp)) {
        val bubblePath = Path().apply {
            moveTo(8.dp.toPx(), 4.dp.toPx())
            lineTo(size.width - 12.dp.toPx(), 4.dp.toPx())
            quadraticTo(size.width - 6.dp.toPx(), 4.dp.toPx(), size.width - 6.dp.toPx(), 10.dp.toPx())
            lineTo(size.width - 6.dp.toPx(), 30.dp.toPx())
            quadraticTo(size.width - 6.dp.toPx(), 36.dp.toPx(), size.width - 12.dp.toPx(), 36.dp.toPx())
            lineTo(22.dp.toPx(), 36.dp.toPx())
            lineTo(14.dp.toPx(), 44.dp.toPx())
            lineTo(16.dp.toPx(), 36.dp.toPx())
            lineTo(8.dp.toPx(), 36.dp.toPx())
            quadraticTo(2.dp.toPx(), 36.dp.toPx(), 2.dp.toPx(), 30.dp.toPx())
            lineTo(2.dp.toPx(), 10.dp.toPx())
            quadraticTo(2.dp.toPx(), 4.dp.toPx(), 8.dp.toPx(), 4.dp.toPx())
            close()
        }
        drawPath(bubblePath, color = palette.primaryAccent)

        drawCircle(
            color = Color(0xFF0F766E),
            radius = 7.dp.toPx(),
            center = Offset(size.width - 10.dp.toPx(), size.height - 12.dp.toPx())
        )

        drawCircle(color = palette.goldAccent, radius = 2.dp.toPx(), center = Offset(size.width - 4.dp.toPx(), 8.dp.toPx()))
        drawCircle(color = palette.goldAccent, radius = 1.5.dp.toPx(), center = Offset(6.dp.toPx(), size.height - 6.dp.toPx()))
    }
}

/**
 * Seerah Timeline: Rolled parchment scroll with timeline nodes.
 */
@Composable
fun ScrollIllustration(palette: HomeThemePalette = rememberHomeThemePalette()) {
    Canvas(modifier = Modifier.size(54.dp, 44.dp)) {
        val scrollColor = Color(0xFFE2C89A)
        val rollColor = Color(0xFFC7A875)

        drawRoundRect(
            color = rollColor,
            topLeft = Offset(4.dp.toPx(), 4.dp.toPx()),
            size = Size(size.width - 8.dp.toPx(), 7.dp.toPx()),
            cornerRadius = CornerRadius(3.5.dp.toPx(), 3.5.dp.toPx())
        )

        drawRect(
            color = scrollColor,
            topLeft = Offset(7.dp.toPx(), 8.dp.toPx()),
            size = Size(size.width - 14.dp.toPx(), 22.dp.toPx())
        )

        drawRoundRect(
            color = rollColor,
            topLeft = Offset(4.dp.toPx(), 27.dp.toPx()),
            size = Size(size.width - 8.dp.toPx(), 7.dp.toPx()),
            cornerRadius = CornerRadius(3.5.dp.toPx(), 3.5.dp.toPx())
        )

        val timelineY = 20.dp.toPx()
        drawLine(
            color = palette.primaryAccent,
            start = Offset(14.dp.toPx(), timelineY),
            end = Offset(size.width - 14.dp.toPx(), timelineY),
            strokeWidth = 2.dp.toPx()
        )
        drawCircle(color = Color(0xFF0D9488), radius = 3.dp.toPx(), center = Offset(18.dp.toPx(), timelineY))
        drawCircle(color = palette.goldAccent, radius = 3.dp.toPx(), center = Offset(27.dp.toPx(), timelineY))
        drawCircle(color = palette.primaryAccent, radius = 3.dp.toPx(), center = Offset(36.dp.toPx(), timelineY))
    }
}

/**
 * Quran Patterns: three short lines for ring, math, and language.
 */
@Composable
fun PatternsIllustration(palette: HomeThemePalette = rememberHomeThemePalette()) {
    Canvas(modifier = Modifier.size(54.dp, 44.dp)) {
        val bars = listOf(palette.primaryAccent, palette.goldAccent, Color(0xFF34D399))
        bars.forEachIndexed { index, color ->
            val top = (8 + index * 12).dp.toPx()
            drawRoundRect(
                color = color,
                topLeft = Offset(8.dp.toPx(), top),
                size = Size((28 + index * 4).dp.toPx(), 6.dp.toPx()),
                cornerRadius = CornerRadius(3.dp.toPx(), 3.dp.toPx())
            )
        }
    }
}

/**
 * Quranic Lens: Open book with magnifying glass.
 */
@Composable
fun QuranLensIllustration(palette: HomeThemePalette = rememberHomeThemePalette()) {
    Canvas(modifier = Modifier.size(54.dp, 44.dp)) {
        val leftPage = Path().apply {
            moveTo(6.dp.toPx(), 12.dp.toPx())
            lineTo(25.dp.toPx(), 8.dp.toPx())
            lineTo(25.dp.toPx(), 32.dp.toPx())
            lineTo(6.dp.toPx(), 34.dp.toPx())
            close()
        }
        val rightPage = Path().apply {
            moveTo(25.dp.toPx(), 8.dp.toPx())
            lineTo(44.dp.toPx(), 12.dp.toPx())
            lineTo(44.dp.toPx(), 34.dp.toPx())
            lineTo(25.dp.toPx(), 32.dp.toPx())
            close()
        }
        drawPath(leftPage, color = Color(0xFF047857))
        drawPath(rightPage, color = palette.primaryAccent)

        drawRect(
            color = Color(0xFFFEF3C7),
            topLeft = Offset(8.dp.toPx(), 13.dp.toPx()),
            size = Size(15.dp.toPx(), 18.dp.toPx())
        )
        drawRect(
            color = Color(0xFFFFFBEB),
            topLeft = Offset(27.dp.toPx(), 11.dp.toPx()),
            size = Size(15.dp.toPx(), 18.dp.toPx())
        )

        val lensCenter = Offset(32.dp.toPx(), 22.dp.toPx())
        drawCircle(color = Color(0xFF38BDF8), radius = 8.dp.toPx(), center = lensCenter, style = Stroke(width = 2.5.dp.toPx()))
        drawCircle(color = Color(0x6638BDF8), radius = 7.dp.toPx(), center = lensCenter, style = Fill)
        drawLine(
            color = Color(0xFFB45309),
            start = Offset(37.dp.toPx(), 27.dp.toPx()),
            end = Offset(47.dp.toPx(), 37.dp.toPx()),
            strokeWidth = 3.dp.toPx()
        )
    }
}

/**
 * Daily Duas: Cupped praying hands with sparkles and stars.
 */
@Composable
fun DuasIllustration(palette: HomeThemePalette = rememberHomeThemePalette()) {
    Canvas(modifier = Modifier.size(54.dp, 44.dp)) {
        val skinColor = Color(0xFFE8B896)
        val shadowColor = Color(0xFFC99672)

        val leftHand = Path().apply {
            moveTo(14.dp.toPx(), 36.dp.toPx())
            lineTo(18.dp.toPx(), 16.dp.toPx())
            quadraticTo(22.dp.toPx(), 14.dp.toPx(), 25.dp.toPx(), 20.dp.toPx())
            lineTo(25.dp.toPx(), 34.dp.toPx())
            close()
        }
        val rightHand = Path().apply {
            moveTo(38.dp.toPx(), 36.dp.toPx())
            lineTo(34.dp.toPx(), 16.dp.toPx())
            quadraticTo(30.dp.toPx(), 14.dp.toPx(), 27.dp.toPx(), 20.dp.toPx())
            lineTo(27.dp.toPx(), 34.dp.toPx())
            close()
        }

        drawPath(leftHand, color = skinColor)
        drawPath(rightHand, color = skinColor)
        drawLine(color = shadowColor, start = Offset(26.dp.toPx(), 18.dp.toPx()), end = Offset(26.dp.toPx(), 36.dp.toPx()), strokeWidth = 1.5.dp.toPx())

        drawCircle(color = palette.goldAccent, radius = 2.5.dp.toPx(), center = Offset(26.dp.toPx(), 8.dp.toPx()))
        drawCircle(color = palette.goldAccent, radius = 1.5.dp.toPx(), center = Offset(16.dp.toPx(), 10.dp.toPx()))
        drawCircle(color = palette.goldAccent, radius = 1.5.dp.toPx(), center = Offset(36.dp.toPx(), 10.dp.toPx()))
    }
}

// ----------------- BOTTOM NAVIGATION BAR -----------------

/**
 * 4-item Bottom Navigation Bar: Home, Learn, Quiz, Profile.
 */
@Composable
fun HomeBottomNavigationBar(
    selectedIndex: Int,
    palette: HomeThemePalette = rememberHomeThemePalette(),
    onItemSelected: (Int) -> Unit
) {
    NavigationBar(
        containerColor = palette.navBarBg,
        contentColor = palette.textPrimary,
        tonalElevation = 0.dp,
        modifier = Modifier
    ) {
        // 1. Home
        NavigationBarItem(
            selected = selectedIndex == 0,
            onClick = { onItemSelected(0) },
            icon = {
                Icon(
                    imageVector = Icons.Default.Home,
                    contentDescription = "Home",
                    tint = if (selectedIndex == 0) palette.primaryAccent else palette.textMuted
                )
            },
            label = {
                Text(
                    text = "Home",
                    color = if (selectedIndex == 0) palette.primaryAccent else palette.textMuted,
                    fontSize = 11.sp,
                    fontWeight = if (selectedIndex == 0) FontWeight.Bold else FontWeight.Normal
                )
            },
            colors = NavigationBarItemDefaults.colors(
                indicatorColor = palette.navBarIndicator
            )
        )

        // 2. Learn
        NavigationBarItem(
            selected = selectedIndex == 1,
            onClick = { onItemSelected(1) },
            icon = {
                LearnNavIcon(tint = if (selectedIndex == 1) palette.primaryAccent else palette.textMuted)
            },
            label = {
                Text(
                    text = "Learn",
                    color = if (selectedIndex == 1) palette.primaryAccent else palette.textMuted,
                    fontSize = 11.sp,
                    fontWeight = if (selectedIndex == 1) FontWeight.Bold else FontWeight.Normal
                )
            },
            colors = NavigationBarItemDefaults.colors(
                indicatorColor = palette.navBarIndicator
            )
        )

        // 3. Quiz
        NavigationBarItem(
            selected = selectedIndex == 2,
            onClick = { onItemSelected(2) },
            icon = {
                QuizNavIcon(tint = if (selectedIndex == 2) palette.primaryAccent else palette.textMuted)
            },
            label = {
                Text(
                    text = "Quiz",
                    color = if (selectedIndex == 2) palette.primaryAccent else palette.textMuted,
                    fontSize = 11.sp,
                    fontWeight = if (selectedIndex == 2) FontWeight.Bold else FontWeight.Normal
                )
            },
            colors = NavigationBarItemDefaults.colors(
                indicatorColor = palette.navBarIndicator
            )
        )

        // 4. Profile
        NavigationBarItem(
            selected = selectedIndex == 3,
            onClick = { onItemSelected(3) },
            icon = {
                Icon(
                    imageVector = Icons.Default.Person,
                    contentDescription = "Profile",
                    tint = if (selectedIndex == 3) palette.primaryAccent else palette.textMuted
                )
            },
            label = {
                Text(
                    text = "Profile",
                    color = if (selectedIndex == 3) palette.primaryAccent else palette.textMuted,
                    fontSize = 11.sp,
                    fontWeight = if (selectedIndex == 3) FontWeight.Bold else FontWeight.Normal
                )
            },
            colors = NavigationBarItemDefaults.colors(
                indicatorColor = palette.navBarIndicator
            )
        )
    }
}

@Composable
fun LearnNavIcon(tint: Color) {
    Canvas(modifier = Modifier.size(22.dp)) {
        val path = Path().apply {
            moveTo(2.dp.toPx(), 5.dp.toPx())
            lineTo(11.dp.toPx(), 7.dp.toPx())
            lineTo(20.dp.toPx(), 5.dp.toPx())
            lineTo(20.dp.toPx(), 18.dp.toPx())
            lineTo(11.dp.toPx(), 19.5.dp.toPx())
            lineTo(2.dp.toPx(), 18.dp.toPx())
            close()
        }
        drawPath(path, color = tint, style = Stroke(width = 1.8.dp.toPx()))
        drawLine(
            color = tint,
            start = Offset(11.dp.toPx(), 7.dp.toPx()),
            end = Offset(11.dp.toPx(), 19.5.dp.toPx()),
            strokeWidth = 1.8.dp.toPx()
        )
    }
}

@Composable
fun QuizNavIcon(tint: Color) {
    Canvas(modifier = Modifier.size(22.dp)) {
        drawCircle(color = tint, radius = 9.dp.toPx(), center = center, style = Stroke(width = 1.8.dp.toPx()))
        drawCircle(color = tint, radius = 1.2.dp.toPx(), center = Offset(center.x, center.y + 4.5.dp.toPx()))
        val qPath = Path().apply {
            moveTo(center.x - 3.dp.toPx(), center.y - 4.dp.toPx())
            quadraticTo(center.x, center.y - 7.dp.toPx(), center.x + 3.dp.toPx(), center.y - 4.dp.toPx())
            quadraticTo(center.x + 2.dp.toPx(), center.y - 1.dp.toPx(), center.x, center.y + 1.dp.toPx())
        }
        drawPath(qPath, color = tint, style = Stroke(width = 1.8.dp.toPx()))
    }
}

// ----------------- REFLECTION SHEET -----------------

@Composable
fun ReflectionSheetContent(
    palette: HomeThemePalette = rememberHomeThemePalette(),
    onClose: () -> Unit,
    onOpenFullVerse: () -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 20.dp, vertical = 12.dp)
            .navigationBarsPadding()
    ) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                text = "Verse Reflection • Surah Al-Baqarah",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = palette.textPrimary
            )
            IconButton(onClick = onClose) {
                Icon(Icons.Default.Close, contentDescription = "Close", tint = palette.textPrimary)
            }
        }

        Spacer(modifier = Modifier.height(12.dp))

        // Arabic Verse
        Card(
            shape = RoundedCornerShape(12.dp),
            colors = CardDefaults.cardColors(containerColor = palette.sheetCardBg),
            modifier = Modifier.fillMaxWidth()
        ) {
            Column(modifier = Modifier.padding(14.dp)) {
                Text(
                    text = "رَبَّنَا لَا تُؤَاخِذْنَا إِن نَّسِينَا أَوْ أَخْطَأْنَا ۚ",
                    color = palette.textPrimary,
                    fontSize = 20.sp,
                    fontWeight = FontWeight.Bold,
                    textAlign = TextAlign.Center,
                    lineHeight = 36.sp,
                    modifier = Modifier.fillMaxWidth()
                )
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    text = "\"Our Lord, do not take us to task if we forget or make mistakes.\" (Quran 2:286)",
                    color = palette.textSecondary,
                    fontSize = 13.sp,
                    textAlign = TextAlign.Center,
                    modifier = Modifier.fillMaxWidth()
                )
            }
        }

        Spacer(modifier = Modifier.height(16.dp))

        Text(
            text = "✨ Spiritual Reflection & Lesson",
            fontWeight = FontWeight.Bold,
            color = palette.arabicGreeting,
            fontSize = 15.sp
        )
        Spacer(modifier = Modifier.height(6.dp))
        Text(
            text = "This closing supplication of Surah Al-Baqarah asks Allah for leniency regarding human forgetfulness and unintended errors. In a Hadith Qudsi, Allah responds to this prayer saying: 'I have done so.'",
            color = palette.textSecondary,
            fontSize = 13.sp,
            lineHeight = 19.sp
        )

        Spacer(modifier = Modifier.height(12.dp))

        Text(
            text = "📖 Prophetic Virtue",
            fontWeight = FontWeight.Bold,
            color = palette.goldAccent,
            fontSize = 14.sp
        )
        Spacer(modifier = Modifier.height(4.dp))
        Text(
            text = "The Prophet Muhammad (ﷺ) said: \"Whoever recites the last two verses of Surah Al-Baqarah at night, that will be sufficient for him.\" (Sahih al-Bukhari 5009)",
            color = palette.textMuted,
            fontSize = 12.sp,
            lineHeight = 18.sp
        )

        Spacer(modifier = Modifier.height(20.dp))

        Button(
            onClick = onOpenFullVerse,
            colors = ButtonDefaults.buttonColors(
                containerColor = palette.primaryButtonBg,
                contentColor = palette.primaryButtonContent
            ),
            shape = RoundedCornerShape(10.dp),
            modifier = Modifier.fillMaxWidth()
        ) {
            Text(
                "Open Surah 2:286 in Quran Reader",
                color = palette.primaryButtonContent,
                fontWeight = FontWeight.Bold
            )
            Spacer(modifier = Modifier.width(6.dp))
            Icon(
                imageVector = Icons.AutoMirrored.Filled.ArrowForward,
                contentDescription = null,
                tint = palette.primaryButtonContent,
                modifier = Modifier.size(16.dp)
            )
        }
    }
}

// ----------------- DAILY STREAK DIALOG -----------------

@Composable
fun DailyStreakDialog(
    currentStreak: Int,
    longestStreak: Int,
    isStreakActiveToday: Boolean,
    palette: HomeThemePalette = rememberHomeThemePalette(),
    onDismiss: () -> Unit,
    onClaimStreak: () -> Unit
) {
    AlertDialog(
        onDismissRequest = onDismiss,
        containerColor = palette.sheetBg,
        title = {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                Text(text = "🔥", fontSize = 24.sp)
                Text(
                    text = "Daily Spiritual Streak",
                    fontWeight = FontWeight.Bold,
                    color = palette.textPrimary
                )
            }
        },
        text = {
            Column(
                modifier = Modifier.fillMaxWidth(),
                verticalArrangement = Arrangement.spacedBy(14.dp)
            ) {
                // Main Streak Highlight Card
                Card(
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = palette.sheetCardBg),
                    border = BorderStroke(1.dp, palette.cardBorder),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Text(
                            text = "$currentStreak",
                            fontSize = 44.sp,
                            fontWeight = FontWeight.ExtraBold,
                            color = palette.streakAccent
                        )
                        Text(
                            text = if (currentStreak == 1) "Day of Consistent Devotion" else "Consecutive Days of Learning",
                            color = palette.textPrimary,
                            fontSize = 13.sp,
                            fontWeight = FontWeight.Medium
                        )
                        Spacer(modifier = Modifier.height(6.dp))
                        Surface(
                            shape = RoundedCornerShape(10.dp),
                            color = palette.actionButtonBg
                        ) {
                            Text(
                                text = "🏆 Longest Record: $longestStreak Days",
                                color = palette.arabicGreeting,
                                fontSize = 11.sp,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier.padding(horizontal = 10.dp, vertical = 4.dp)
                            )
                        }
                    }
                }

                // 7-day Weekly Visual Tracker
                Column(
                    modifier = Modifier.fillMaxWidth(),
                    verticalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    Text(
                        text = "Weekly Activity",
                        color = palette.textMuted,
                        fontSize = 11.sp,
                        fontWeight = FontWeight.Bold
                    )
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        val days = listOf("M", "T", "W", "T", "F", "S", "S")
                        val activeDaysCount = if (currentStreak <= 0) 0 else ((currentStreak - 1) % 7 + 1)
                        for (i in 0..6) {
                            val isCompleted = i < activeDaysCount
                            Column(
                                horizontalAlignment = Alignment.CenterHorizontally,
                                verticalArrangement = Arrangement.spacedBy(4.dp)
                            ) {
                                Box(
                                    modifier = Modifier
                                        .size(32.dp)
                                        .background(
                                            color = if (isCompleted) palette.primaryAccent else palette.sheetCardBg,
                                            shape = CircleShape
                                        ),
                                    contentAlignment = Alignment.Center
                                ) {
                                    if (isCompleted) {
                                        Text(text = "✓", color = Color.White, fontSize = 14.sp, fontWeight = FontWeight.Bold)
                                    } else {
                                        Box(
                                            modifier = Modifier
                                                .size(6.dp)
                                                .background(palette.textMuted, CircleShape)
                                        )
                                    }
                                }
                                Text(text = days[i], color = palette.textMuted, fontSize = 10.sp, fontWeight = FontWeight.SemiBold)
                            }
                        }
                    }
                }

                // Hadith / Spiritual Quote
                Card(
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = palette.quoteCardBg)
                ) {
                    Text(
                        text = "\"The most beloved of deeds to Allah are those that are most consistent, even if they are few.\" — Sahih al-Bukhari",
                        color = palette.textSecondary,
                        fontSize = 11.sp,
                        lineHeight = 16.sp,
                        fontStyle = androidx.compose.ui.text.font.FontStyle.Italic,
                        modifier = Modifier.padding(12.dp)
                    )
                }

                // Check-in action button
                if (!isStreakActiveToday) {
                    Button(
                        onClick = {
                            onClaimStreak()
                            onDismiss()
                        },
                        colors = ButtonDefaults.buttonColors(containerColor = palette.streakAccent),
                        shape = RoundedCornerShape(12.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text(
                            text = "⚡ Claim Today's Streak",
                            color = Color.White,
                            fontWeight = FontWeight.Bold,
                            fontSize = 14.sp
                        )
                    }
                } else {
                    Surface(
                        shape = RoundedCornerShape(12.dp),
                        color = palette.actionButtonBg,
                        border = BorderStroke(1.dp, palette.actionButtonBorder),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(12.dp),
                            horizontalArrangement = Arrangement.Center,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(
                                text = "✅ Streak Maintained for Today!",
                                color = palette.arabicGreeting,
                                fontWeight = FontWeight.Bold,
                                fontSize = 13.sp
                            )
                        }
                    }
                }
            }
        },
        confirmButton = {},
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Close", color = palette.textMuted)
            }
        }
    )
}

// ----------------- APP EXPERIENCE FEEDBACK -----------------

/**
 * In-Page Feedback Card displayed at the bottom of the home front page.
 */
@Composable
fun HomeFeedbackCard(
    palette: HomeThemePalette,
    onOpenFeedback: () -> Unit
) {
    Card(
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = palette.cardBg),
        border = BorderStroke(1.dp, palette.cardBorder),
        modifier = Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(16.dp))
            .clickable { onOpenFeedback() }
            .testTag("home_feedback_card")
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 14.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(12.dp),
                modifier = Modifier.weight(1f)
            ) {
                Surface(
                    shape = RoundedCornerShape(12.dp),
                    color = palette.actionButtonBg,
                    border = BorderStroke(1.dp, palette.actionButtonBorder),
                    modifier = Modifier.size(40.dp)
                ) {
                    Box(contentAlignment = Alignment.Center) {
                        Icon(
                            imageVector = Icons.Outlined.Star,
                            contentDescription = null,
                            tint = palette.goldAccent,
                            modifier = Modifier.size(20.dp)
                        )
                    }
                }
                Column {
                    Text(
                        text = "How are we doing?",
                        color = palette.textPrimary,
                        fontSize = 13.sp,
                        fontWeight = FontWeight.Bold
                    )
                    Text(
                        text = "A short note helps us improve the journey",
                        color = palette.textMuted,
                        fontSize = 11.sp
                    )
                }
            }

            Surface(
                shape = RoundedCornerShape(8.dp),
                color = palette.primaryButtonBg
            ) {
                Text(
                    text = "Feedback",
                    color = palette.primaryButtonContent,
                    fontSize = 11.sp,
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp)
                )
            }
        }
    }
}

/**
 * Modal Bottom Sheet asking for user experience feedback and rating.
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AppFeedbackBottomSheet(
    palette: HomeThemePalette,
    onDismiss: () -> Unit,
    onSubmitFeedback: (rating: Int, category: String, message: String, onSuccess: () -> Unit, onError: (String) -> Unit) -> Unit
) {
    var rating by remember { mutableIntStateOf(5) }
    var selectedCategory by remember { mutableStateOf("app_experience") }
    var message by remember { mutableStateOf("") }
    var isSubmitting by remember { mutableStateOf(false) }
    var isSuccess by remember { mutableStateOf(false) }
    var errorMessage by remember { mutableStateOf<String?>(null) }

    val categories = listOf(
        "app_experience" to "🌟 Overall App",
        "content" to "📖 Quran & Duas",
        "feature_request" to "💡 Feature Idea",
        "bug" to "🐞 Report Issue"
    )

    ModalBottomSheet(
        onDismissRequest = onDismiss,
        containerColor = palette.sheetBg,
        contentColor = palette.textPrimary
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 24.dp, vertical = 12.dp)
                .navigationBarsPadding(),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            if (isSuccess) {
                Spacer(modifier = Modifier.height(16.dp))
                Text(text = "✨", fontSize = 42.sp)
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    text = "JazakAllahu Khairan!",
                    fontSize = 22.sp,
                    fontWeight = FontWeight.Bold,
                    color = palette.textPrimary
                )
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    text = "Thank you for your valuable feedback. May Allah bless you in your spiritual and educational journey.",
                    fontSize = 13.sp,
                    color = palette.textSecondary,
                    textAlign = TextAlign.Center,
                    lineHeight = 18.sp
                )
                Spacer(modifier = Modifier.height(24.dp))
                Button(
                    onClick = onDismiss,
                    colors = ButtonDefaults.buttonColors(containerColor = palette.primaryButtonBg),
                    shape = RoundedCornerShape(12.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text("Close", color = palette.primaryButtonContent, fontWeight = FontWeight.Bold)
                }
                Spacer(modifier = Modifier.height(16.dp))
            } else {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Text(text = "⭐", fontSize = 20.sp)
                        Text(
                            text = "How is your experience?",
                            fontSize = 18.sp,
                            fontWeight = FontWeight.Bold,
                            color = palette.textPrimary
                        )
                    }
                    IconButton(onClick = onDismiss) {
                        Icon(Icons.Default.Close, contentDescription = "Close", tint = palette.textMuted)
                    }
                }

                Text(
                    text = "Help us improve The Eternal Echo with your ratings & suggestions.",
                    fontSize = 12.sp,
                    color = palette.textMuted,
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(top = 2.dp, bottom = 14.dp)
                )

                // 5 Star Rating Selector
                Surface(
                    shape = RoundedCornerShape(14.dp),
                    color = palette.sheetCardBg,
                    border = BorderStroke(1.dp, palette.cardBorder),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 12.dp, horizontal = 8.dp),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Row(
                            horizontalArrangement = Arrangement.Center,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            for (i in 1..5) {
                                val isSelected = i <= rating
                                IconButton(
                                    onClick = { rating = i },
                                    modifier = Modifier.size(42.dp)
                                ) {
                                    Icon(
                                        imageVector = if (isSelected) Icons.Filled.Star else Icons.Outlined.Star,
                                        contentDescription = "Star $i",
                                        tint = if (isSelected) palette.goldAccent else palette.textMuted,
                                        modifier = Modifier.size(30.dp)
                                    )
                                }
                            }
                        }
                        Text(
                            text = when (rating) {
                                5 -> "Exceptional! ⭐ 5/5"
                                4 -> "Great! 👍 4/5"
                                3 -> "Good 🙂 3/5"
                                2 -> "Needs Improvement 🛠️ 2/5"
                                else -> "Poor ⚠️ 1/5"
                            },
                            fontSize = 12.sp,
                            fontWeight = FontWeight.Bold,
                            color = palette.goldAccent,
                            modifier = Modifier.padding(top = 2.dp)
                        )
                    }
                }

                Spacer(modifier = Modifier.height(14.dp))

                // Category selector chips
                Text(
                    text = "Category",
                    fontSize = 11.sp,
                    fontWeight = FontWeight.Bold,
                    color = palette.textMuted,
                    modifier = Modifier.fillMaxWidth()
                )
                Spacer(modifier = Modifier.height(6.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    categories.take(2).forEach { (catKey, catLabel) ->
                        val isCatSelected = selectedCategory == catKey
                        Surface(
                            shape = RoundedCornerShape(10.dp),
                            color = if (isCatSelected) palette.primaryButtonBg else palette.sheetCardBg,
                            border = BorderStroke(1.dp, if (isCatSelected) palette.primaryAccent else palette.cardBorder),
                            modifier = Modifier
                                .weight(1f)
                                .clip(RoundedCornerShape(10.dp))
                                .clickable { selectedCategory = catKey }
                        ) {
                            Text(
                                text = catLabel,
                                fontSize = 11.sp,
                                fontWeight = if (isCatSelected) FontWeight.Bold else FontWeight.Medium,
                                color = if (isCatSelected) palette.primaryButtonContent else palette.textSecondary,
                                textAlign = TextAlign.Center,
                                modifier = Modifier.padding(vertical = 8.dp)
                            )
                        }
                    }
                }
                Spacer(modifier = Modifier.height(6.dp))
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    categories.drop(2).forEach { (catKey, catLabel) ->
                        val isCatSelected = selectedCategory == catKey
                        Surface(
                            shape = RoundedCornerShape(10.dp),
                            color = if (isCatSelected) palette.primaryButtonBg else palette.sheetCardBg,
                            border = BorderStroke(1.dp, if (isCatSelected) palette.primaryAccent else palette.cardBorder),
                            modifier = Modifier
                                .weight(1f)
                                .clip(RoundedCornerShape(10.dp))
                                .clickable { selectedCategory = catKey }
                        ) {
                            Text(
                                text = catLabel,
                                fontSize = 11.sp,
                                fontWeight = if (isCatSelected) FontWeight.Bold else FontWeight.Medium,
                                color = if (isCatSelected) palette.primaryButtonContent else palette.textSecondary,
                                textAlign = TextAlign.Center,
                                modifier = Modifier.padding(vertical = 8.dp)
                            )
                        }
                    }
                }

                Spacer(modifier = Modifier.height(14.dp))

                // Comments input
                OutlinedTextField(
                    value = message,
                    onValueChange = { if (it.length <= 1000) message = it },
                    placeholder = {
                        Text(
                            "Share your thoughts, suggestions, or ideas (Optional)...",
                            fontSize = 12.sp,
                            color = palette.textMuted
                        )
                    },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        focusedBorderColor = palette.primaryAccent,
                        unfocusedBorderColor = palette.cardBorder,
                        focusedTextColor = palette.textPrimary,
                        unfocusedTextColor = palette.textPrimary,
                        cursorColor = palette.primaryAccent
                    ),
                    maxLines = 4,
                    minLines = 3
                )

                if (errorMessage != null) {
                    Text(
                        text = errorMessage ?: "",
                        color = MaterialTheme.colorScheme.error,
                        fontSize = 11.sp,
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 4.dp)
                    )
                }

                Spacer(modifier = Modifier.height(18.dp))

                // Buttons
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(10.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    OutlinedButton(
                        onClick = onDismiss,
                        shape = RoundedCornerShape(12.dp),
                        border = BorderStroke(1.dp, palette.cardBorder),
                        modifier = Modifier.weight(1f)
                    ) {
                        Text("Maybe Later", color = palette.textMuted, fontSize = 12.sp)
                    }

                    Button(
                        onClick = {
                            isSubmitting = true
                            errorMessage = null
                            onSubmitFeedback(
                                rating,
                                selectedCategory,
                                message,
                                {
                                    isSubmitting = false
                                    isSuccess = true
                                },
                                { err ->
                                    isSubmitting = false
                                    errorMessage = err
                                }
                            )
                        },
                        enabled = !isSubmitting,
                        shape = RoundedCornerShape(12.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = palette.primaryButtonBg),
                        modifier = Modifier.weight(1.4f)
                    ) {
                        if (isSubmitting) {
                            CircularProgressIndicator(
                                modifier = Modifier.size(16.dp),
                                color = palette.primaryButtonContent,
                                strokeWidth = 2.dp
                            )
                            Spacer(modifier = Modifier.width(6.dp))
                        }
                        Text(
                            text = if (isSubmitting) "Submitting..." else "Submit Feedback",
                            color = palette.primaryButtonContent,
                            fontWeight = FontWeight.Bold,
                            fontSize = 12.sp
                        )
                    }
                }

                Spacer(modifier = Modifier.height(14.dp))
            }
        }
    }
}


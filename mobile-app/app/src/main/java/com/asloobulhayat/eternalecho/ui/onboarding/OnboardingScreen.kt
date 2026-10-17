package com.asloobulhayat.eternalecho.ui.onboarding

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.animateColorAsState
import androidx.compose.animation.core.animateDpAsState
import androidx.compose.animation.core.tween
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.pager.HorizontalPager
import androidx.compose.foundation.pager.PagerDefaults
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.ArrowForward
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch
import kotlin.math.absoluteValue

data class OnboardingStep(
    val title: String,
    val subtitle: String,
    val description: String,
    val icon: ImageVector,
    val badges: List<String>,
    val highlightColor: Color
)

@Composable
fun OnboardingScreen(
    onFinished: () -> Unit,
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    val prefs = remember { com.asloobulhayat.eternalecho.security.SecurePreferences.getInstance(context) }
    val coroutineScope = rememberCoroutineScope()

    val steps = remember {
        listOf(
            OnboardingStep(
                title = "Welcome to The Eternal Echo",
                subtitle = "AI-Powered Islamic Learning & Research",
                description = "Embark on an enriching journey of contemplation, study, and spiritual growth. Bridge timeless Quranic wisdom with verified classical sources and modern research.",
                icon = Icons.Default.Star,
                badges = listOf("AI Learning", "Collaborative Research", "Authentic Sources"),
                highlightColor = Color(0xFF10B981) // Emerald
            ),
            OnboardingStep(
                title = "Quranic Lens & Connections",
                subtitle = "Multi-Dimensional Ayah Mapping",
                description = "Explore deep harmonies between the Holy Quran and modern science, authenticated Hadith, chronological Seerah events, and historical discoveries.",
                icon = Icons.Default.Search,
                badges = listOf("114 Surahs", "Science Links", "Seerah Timeline"),
                highlightColor = Color(0xFF0D9488) // Teal
            ),
            OnboardingStep(
                title = "Knowledge Quizzes & Leaderboards",
                subtitle = "Para-by-Para & Thematic Challenges",
                description = "Assess your Quran and Seerah mastery with 3 cognitive difficulty levels. Track your spiritual progress, earn streaks, and see where you stand on the global rankings.",
                icon = Icons.Default.CheckCircle,
                badges = listOf("30 Paras", "Seerah Eras", "Global Rank"),
                highlightColor = Color(0xFF10B981) // Amber
            ),
            OnboardingStep(
                title = "Sacred Duas & Meanings",
                subtitle = "Word-by-Word Contemplation & Sunnah Tasbih",
                description = "Immerse in authentic supplications from the Quran and Sunnah with word-by-word translations, spiritual commentary, and a digital Sunnah repetition counter.",
                icon = Icons.Default.Favorite,
                badges = listOf("Word-by-Word", "Sunnah Tasbih", "Quran & Hadith Citations"),
                highlightColor = Color(0xFF059669) // Deep Emerald
            )
        )
    }

    val pagerState = rememberPagerState(
        initialPage = 0,
        pageCount = { steps.size }
    )

    val completeOnboarding = {
        prefs.putBoolean("has_completed_onboarding", true)
        onFinished()
    }

    val currentStep = pagerState.currentPage
    val activeColor = steps[currentStep].highlightColor

    Surface(
        modifier = modifier.fillMaxSize(),
        color = MaterialTheme.colorScheme.background
    ) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(horizontal = 20.dp, vertical = 16.dp)
                .statusBarsPadding()
                .navigationBarsPadding(),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.SpaceBetween
        ) {
            // Top Bar: Step Indicator & Skip Button
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 6.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "Step ${currentStep + 1} of ${steps.size}",
                    style = MaterialTheme.typography.labelMedium,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                if (currentStep < steps.size - 1) {
                    TextButton(onClick = completeOnboarding) {
                        Text(
                            text = "Skip",
                            style = MaterialTheme.typography.labelLarge,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.primary
                        )
                    }
                } else {
                    Spacer(modifier = Modifier.width(48.dp))
                }
            }

            // Central Interactive Pager with Side Movement Affordances & Parallax
            Box(
                modifier = Modifier
                    .weight(1f)
                    .fillMaxWidth(),
                contentAlignment = Alignment.Center
            ) {
                HorizontalPager(
                    state = pagerState,
                    beyondViewportPageCount = 1,
                    flingBehavior = PagerDefaults.flingBehavior(state = pagerState),
                    modifier = Modifier.fillMaxSize()
                ) { pageIndex ->
                    val step = steps[pageIndex]
                    val pageScrollState = rememberScrollState()

                    // Relative scroll offset between this page and current view
                    val pageOffset = ((pagerState.currentPage - pageIndex) + pagerState.currentPageOffsetFraction)
                    val absOffset = pageOffset.absoluteValue.coerceIn(0f, 1f)

                    Column(
                        modifier = Modifier
                            .fillMaxSize()
                            .graphicsLayer {
                                val scale = 1f - (absOffset * 0.12f)
                                scaleX = scale
                                scaleY = scale
                                alpha = 1f - (absOffset * 0.45f)
                            }
                            .verticalScroll(pageScrollState)
                            .padding(horizontal = 8.dp, vertical = 12.dp),
                        horizontalAlignment = Alignment.CenterHorizontally,
                        verticalArrangement = Arrangement.Center
                    ) {
                        // Visual Glowing Icon Circle with subtle parallax translation
                        Box(
                            contentAlignment = Alignment.Center,
                            modifier = Modifier
                                .graphicsLayer {
                                    translationX = pageOffset * -50.dp.toPx()
                                }
                                .size(120.dp)
                                .clip(CircleShape)
                                .background(
                                    Brush.radialGradient(
                                        colors = listOf(
                                            step.highlightColor.copy(alpha = 0.25f),
                                            step.highlightColor.copy(alpha = 0.05f)
                                        )
                                    )
                                )
                        ) {
                            Box(
                                contentAlignment = Alignment.Center,
                                modifier = Modifier
                                    .size(76.dp)
                                    .clip(CircleShape)
                                    .background(step.highlightColor)
                            ) {
                                Icon(
                                    imageVector = step.icon,
                                    contentDescription = null,
                                    tint = Color.White,
                                    modifier = Modifier.size(40.dp)
                                )
                            }
                        }

                        Spacer(modifier = Modifier.height(20.dp))

                        // Feature Pill Badges
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(8.dp),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            step.badges.forEach { badge ->
                                AssistChip(
                                    onClick = {},
                                    label = { Text(badge, fontSize = 11.sp, fontWeight = FontWeight.SemiBold) },
                                    modifier = Modifier.padding(horizontal = 3.dp),
                                    shape = RoundedCornerShape(12.dp),
                                    colors = AssistChipDefaults.assistChipColors(
                                        containerColor = step.highlightColor.copy(alpha = 0.12f),
                                        labelColor = step.highlightColor
                                    ),
                                    border = null
                                )
                            }
                        }

                        Spacer(modifier = Modifier.height(20.dp))

                        // Titles & Copy
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(8.dp),
                            modifier = Modifier.padding(horizontal = 8.dp)
                        ) {
                            Text(
                                text = step.title,
                                style = MaterialTheme.typography.headlineMedium,
                                fontWeight = FontWeight.Black,
                                textAlign = TextAlign.Center,
                                color = MaterialTheme.colorScheme.onBackground
                            )

                            Text(
                                text = step.subtitle,
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.Bold,
                                color = step.highlightColor,
                                textAlign = TextAlign.Center
                            )

                            Spacer(modifier = Modifier.height(4.dp))

                            Text(
                                text = step.description,
                                style = MaterialTheme.typography.bodyMedium,
                                textAlign = TextAlign.Center,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                                lineHeight = 22.sp
                            )
                        }
                    }
                }

                // Left quick-move navigation arrow
                androidx.compose.animation.AnimatedVisibility(
                    visible = currentStep > 0,
                    enter = fadeIn(),
                    exit = fadeOut(),
                    modifier = Modifier.align(Alignment.CenterStart)
                ) {
                    IconButton(
                        onClick = {
                            coroutineScope.launch {
                                pagerState.animateScrollToPage(currentStep - 1)
                            }
                        },
                        modifier = Modifier
                            .size(36.dp)
                            .background(
                                color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.65f),
                                shape = CircleShape
                            )
                    ) {
                        Icon(
                            imageVector = Icons.AutoMirrored.Filled.ArrowBack,
                            contentDescription = "Previous Item",
                            modifier = Modifier.size(20.dp),
                            tint = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }

                // Right quick-move navigation arrow
                androidx.compose.animation.AnimatedVisibility(
                    visible = currentStep < steps.size - 1,
                    enter = fadeIn(),
                    exit = fadeOut(),
                    modifier = Modifier.align(Alignment.CenterEnd)
                ) {
                    IconButton(
                        onClick = {
                            coroutineScope.launch {
                                pagerState.animateScrollToPage(currentStep + 1)
                            }
                        },
                        modifier = Modifier
                            .size(36.dp)
                            .background(
                                color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.65f),
                                shape = CircleShape
                            )
                    ) {
                        Icon(
                            imageVector = Icons.AutoMirrored.Filled.ArrowForward,
                            contentDescription = "Next Item",
                            modifier = Modifier.size(20.dp),
                            tint = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
            }

            // Bottom Navigation Area: Easy-to-tap Dots & Action Buttons
            Column(
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(16.dp),
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 8.dp)
            ) {
                // Page Indicator Dots (Generous 44dp touch targets for effortless tapping to any item)
                Row(
                    horizontalArrangement = Arrangement.spacedBy(4.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    steps.indices.forEach { index ->
                        val isSelected = index == currentStep
                        val indicatorWidth by animateDpAsState(
                            targetValue = if (isSelected) 30.dp else 10.dp,
                            animationSpec = tween(durationMillis = 300),
                            label = "indicator_width"
                        )
                        val indicatorColor by animateColorAsState(
                            targetValue = if (isSelected) activeColor else MaterialTheme.colorScheme.outlineVariant,
                            animationSpec = tween(durationMillis = 300),
                            label = "indicator_color"
                        )

                        Box(
                            modifier = Modifier
                                .height(44.dp)
                                .clickable(
                                    interactionSource = remember { MutableInteractionSource() },
                                    indication = null
                                ) {
                                    coroutineScope.launch {
                                        pagerState.animateScrollToPage(index)
                                    }
                                }
                                .padding(horizontal = 4.dp),
                            contentAlignment = Alignment.Center
                        ) {
                            Box(
                                modifier = Modifier
                                    .height(8.dp)
                                    .width(indicatorWidth)
                                    .clip(RoundedCornerShape(4.dp))
                                    .background(indicatorColor)
                            )
                        }
                    }
                }

                // Next / Back / Get Started Buttons
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    if (currentStep > 0) {
                        OutlinedButton(
                            onClick = {
                                coroutineScope.launch {
                                    pagerState.animateScrollToPage(currentStep - 1)
                                }
                            },
                            modifier = Modifier
                                .weight(1f)
                                .height(52.dp),
                            shape = RoundedCornerShape(16.dp)
                        ) {
                            Text("Back", fontWeight = FontWeight.Bold)
                        }
                    }

                    Button(
                        onClick = {
                            if (currentStep < steps.size - 1) {
                                coroutineScope.launch {
                                    pagerState.animateScrollToPage(currentStep + 1)
                                }
                            } else {
                                completeOnboarding()
                            }
                        },
                        modifier = Modifier
                            .weight(if (currentStep > 0) 1.5f else 1f)
                            .height(52.dp),
                        shape = RoundedCornerShape(16.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = activeColor)
                    ) {
                        Text(
                            text = if (currentStep == steps.size - 1) "Begin journey" else "Next",
                            fontWeight = FontWeight.Bold,
                            fontSize = 15.sp,
                            color = Color.White
                        )
                    }
                }
            }
        }
    }
}

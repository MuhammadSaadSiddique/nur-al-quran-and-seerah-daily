package com.example.eternalechomobile.ui.onboarding

import android.content.Context
import androidx.compose.animation.*
import androidx.compose.animation.core.tween
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

data class OnboardingStep(
    val title: String,
    val subtitle: String,
    val description: String,
    val icon: ImageVector,
    val badges: List<String>,
    val highlightColor: Color
)

@OptIn(ExperimentalAnimationApi::class)
@Composable
fun OnboardingScreen(
    onFinished: () -> Unit,
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    val prefs = remember { com.example.eternalechomobile.security.SecurePreferences.getInstance(context) }

    var currentStep by remember { mutableStateOf(0) }

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
                highlightColor = Color(0xFFF59E0B) // Amber
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

    val completeOnboarding = {
        prefs.putBoolean("has_completed_onboarding", true)
        onFinished()
    }

    val step = steps[currentStep]

    Surface(
        modifier = modifier.fillMaxSize(),
        color = MaterialTheme.colorScheme.background
    ) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(24.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.SpaceBetween
        ) {
            // Top Bar: Step Indicator & Skip Button
            Row(
                modifier = Modifier.fillMaxWidth(),
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

            // Central Animated Content Card
            AnimatedContent(
                targetState = currentStep,
                transitionSpec = {
                    if (targetState > initialState) {
                        (slideInHorizontally(animationSpec = tween(300)) { width -> width } + fadeIn())
                            .togetherWith(slideOutHorizontally(animationSpec = tween(300)) { width -> -width } + fadeOut())
                    } else {
                        (slideInHorizontally(animationSpec = tween(300)) { width -> -width } + fadeIn())
                            .togetherWith(slideOutHorizontally(animationSpec = tween(300)) { width -> width } + fadeOut())
                    }
                },
                label = "onboarding_step"
            ) { targetIndex ->
                val current = steps[targetIndex]
                Column(
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.spacedBy(20.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    // Visual Glowing Icon Circle
                    Box(
                        contentAlignment = Alignment.Center,
                        modifier = Modifier
                            .size(120.dp)
                            .clip(CircleShape)
                            .background(
                                Brush.radialGradient(
                                    colors = listOf(
                                        current.highlightColor.copy(alpha = 0.25f),
                                        current.highlightColor.copy(alpha = 0.05f)
                                    )
                                )
                            )
                    ) {
                        Box(
                            contentAlignment = Alignment.Center,
                            modifier = Modifier
                                .size(76.dp)
                                .clip(CircleShape)
                                .background(current.highlightColor)
                        ) {
                            Icon(
                                imageVector = current.icon,
                                contentDescription = null,
                                tint = Color.White,
                                modifier = Modifier.size(40.dp)
                            )
                        }
                    }

                    // Feature Pill Badges
                    Column(
                        horizontalAlignment = Alignment.CenterHorizontally,
                        verticalArrangement = Arrangement.spacedBy(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        current.badges.forEach { badge ->
                            AssistChip(
                                onClick = {},
                                label = { Text(badge, fontSize = 11.sp, fontWeight = FontWeight.SemiBold) },
                                modifier = Modifier.padding(horizontal = 3.dp),
                                shape = RoundedCornerShape(12.dp),
                                colors = AssistChipDefaults.assistChipColors(
                                    containerColor = current.highlightColor.copy(alpha = 0.12f),
                                    labelColor = current.highlightColor
                                ),
                                border = null
                            )
                        }
                    }

                    // Titles & Copy
                    Column(
                        horizontalAlignment = Alignment.CenterHorizontally,
                        verticalArrangement = Arrangement.spacedBy(8.dp),
                        modifier = Modifier.padding(horizontal = 8.dp)
                    ) {
                        Text(
                            text = current.title,
                            style = MaterialTheme.typography.headlineMedium,
                            fontWeight = FontWeight.Black,
                            textAlign = TextAlign.Center,
                            color = MaterialTheme.colorScheme.onBackground
                        )

                        Text(
                            text = current.subtitle,
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold,
                            color = current.highlightColor,
                            textAlign = TextAlign.Center
                        )

                        Spacer(modifier = Modifier.height(4.dp))

                        Text(
                            text = current.description,
                            style = MaterialTheme.typography.bodyMedium,
                            textAlign = TextAlign.Center,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            lineHeight = 22.sp
                        )
                    }
                }
            }

            // Bottom Navigation Area: Dots & Action Buttons
            Column(
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(20.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                // Page Indicator Dots
                Row(
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    steps.indices.forEach { index ->
                        val isSelected = index == currentStep
                        Box(
                            modifier = Modifier
                                .height(8.dp)
                                .width(if (isSelected) 28.dp else 8.dp)
                                .clip(RoundedCornerShape(4.dp))
                                .background(
                                    if (isSelected) step.highlightColor else MaterialTheme.colorScheme.outlineVariant
                                )
                        )
                    }
                }

                // Next / Get Started Buttons
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    if (currentStep > 0) {
                        OutlinedButton(
                            onClick = { currentStep-- },
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
                                currentStep++
                            } else {
                                completeOnboarding()
                            }
                        },
                        modifier = Modifier
                            .weight(if (currentStep > 0) 1.5f else 1f)
                            .height(52.dp),
                        shape = RoundedCornerShape(16.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = step.highlightColor)
                    ) {
                        Text(
                            text = if (currentStep == steps.size - 1) "Begin Journey ✨" else "Next",
                            fontWeight = FontWeight.Black,
                            fontSize = 15.sp,
                            color = Color.White
                        )
                    }
                }
            }
        }
    }
}

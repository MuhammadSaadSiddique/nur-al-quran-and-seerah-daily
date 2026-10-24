package com.asloobulhayat.eternalecho.ui.insights

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.asloobulhayat.eternalecho.data.DailyEngagement
import com.asloobulhayat.eternalecho.data.DefaultDataRepository
import com.asloobulhayat.eternalecho.security.SecurePreferences
import kotlinx.coroutines.launch

/**
 * Full-screen knowledge check for a Seerah / Quran History daily insight.
 * Separated from the reflection list so the tab shows only today's insight.
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun InsightQuizScreen(
    markType: String,
    questionId: String,
    insightTitle: String,
    questionText: String,
    options: List<String>,
    onBackClick: () -> Unit,
    onAuthClick: () -> Unit = {},
    onEngagementUpdated: (DailyEngagement) -> Unit = {},
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    val prefs = remember { SecurePreferences.getInstance(context) }
    val scope = rememberCoroutineScope()
    val repository = remember { DefaultDataRepository() }

    var selectedOptionIndex by remember { mutableStateOf<Int?>(null) }
    var revealedCorrectIndex by remember { mutableIntStateOf(-1) }
    var revealedExplanation by remember { mutableStateOf("") }
    var completedQuizzes by remember { mutableIntStateOf(prefs.getInt("completed_quizzes", 0)) }
    var showLimitDialog by remember { mutableStateOf(false) }
    val hasAnswered = selectedOptionIndex != null

    if (showLimitDialog) {
        AlertDialog(
            onDismissRequest = { showLimitDialog = false },
            title = { Text("Login Required") },
            text = { Text("You have completed 1 free guest quiz. Please sign in to continue.") },
            confirmButton = {
                Button(onClick = {
                    showLimitDialog = false
                    onAuthClick()
                }) { Text("Sign In") }
            },
            dismissButton = {
                TextButton(onClick = { showLimitDialog = false }) { Text("Cancel") }
            }
        )
    }

    Scaffold(
        topBar = {
            com.asloobulhayat.eternalecho.ui.components.EchoTopBar(
                title = "Knowledge Check",
                onBackClick = onBackClick,
                navigationIcon = Icons.AutoMirrored.Filled.ArrowBack
            )
        }
    ) { padding ->
        Column(
            modifier = modifier
                .fillMaxSize()
                .padding(padding)
                .verticalScroll(rememberScrollState())
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text(
                text = insightTitle,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.primary
            )
            Text(
                text = questionText,
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold
            )

            options.forEachIndexed { index, option ->
                val isCorrectOption = revealedCorrectIndex >= 0 && index == revealedCorrectIndex
                val isSelectedOption = index == selectedOptionIndex
                val containerColor = when {
                    !hasAnswered -> MaterialTheme.colorScheme.surface
                    isCorrectOption -> MaterialTheme.colorScheme.primaryContainer
                    isSelectedOption && revealedCorrectIndex >= 0 -> MaterialTheme.colorScheme.errorContainer
                    isSelectedOption -> MaterialTheme.colorScheme.secondaryContainer
                    else -> MaterialTheme.colorScheme.surface
                }
                val contentColor = when {
                    !hasAnswered -> MaterialTheme.colorScheme.onSurface
                    isCorrectOption -> MaterialTheme.colorScheme.onPrimaryContainer
                    isSelectedOption && revealedCorrectIndex >= 0 -> MaterialTheme.colorScheme.onErrorContainer
                    isSelectedOption -> MaterialTheme.colorScheme.onSecondaryContainer
                    else -> MaterialTheme.colorScheme.onSurface
                }
                val borderColor = when {
                    !hasAnswered -> MaterialTheme.colorScheme.outline
                    isCorrectOption -> MaterialTheme.colorScheme.primary
                    isSelectedOption && revealedCorrectIndex >= 0 -> MaterialTheme.colorScheme.error
                    isSelectedOption -> MaterialTheme.colorScheme.secondary
                    else -> MaterialTheme.colorScheme.outlineVariant
                }

                OutlinedButton(
                    onClick = {
                        if (hasAnswered) return@OutlinedButton
                        val userId = prefs.getInt("user_id", -1)
                        val token = prefs.getString("api_token", null).orEmpty()
                        val loggedIn = userId > 0 && token.isNotBlank()
                        val guestDone = prefs.getInt("completed_quizzes", 0)
                        if (!loggedIn && guestDone >= 1) {
                            showLimitDialog = true
                            return@OutlinedButton
                        }
                        selectedOptionIndex = index
                        if (!loggedIn) {
                            prefs.putInt("completed_quizzes", guestDone + 1)
                            completedQuizzes = guestDone + 1
                            return@OutlinedButton
                        }
                        scope.launch {
                            try {
                                com.asloobulhayat.eternalecho.data.ApiClient.authToken = token
                                if (questionId.isNotBlank()) {
                                    repository.checkAnswer(questionId, index, options)?.let { reveal ->
                                        revealedCorrectIndex = reveal.correctAnswerIndex
                                        revealedExplanation = reveal.explanation
                                    }
                                }
                                repository.markInsight(userId, markType, questionId).onSuccess { engagement ->
                                    engagement?.let {
                                        onEngagementUpdated(it)
                                        com.asloobulhayat.eternalecho.data.EngagementBus.publish(it)
                                    } ?: com.asloobulhayat.eternalecho.data.EngagementBus.requestRefresh()
                                }
                            } catch (e: Exception) {
                                android.util.Log.e("InsightQuizScreen", "Failed to check/mark insight", e)
                            }
                        }
                    },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp),
                    colors = ButtonDefaults.outlinedButtonColors(
                        containerColor = containerColor,
                        contentColor = contentColor
                    ),
                    border = BorderStroke(1.dp, borderColor)
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 6.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text(
                            text = "${(65 + index).toChar()}. $option",
                            style = MaterialTheme.typography.bodyLarge,
                            fontWeight = if (isSelectedOption) FontWeight.Bold else FontWeight.Normal,
                            modifier = Modifier.weight(1f)
                        )
                        if (hasAnswered && revealedCorrectIndex >= 0) {
                            Text(
                                text = if (isCorrectOption) "✓" else if (isSelectedOption) "✗" else "",
                                fontWeight = FontWeight.Bold
                            )
                        }
                    }
                }
            }

            if (hasAnswered && revealedExplanation.isNotEmpty()) {
                Surface(
                    color = MaterialTheme.colorScheme.tertiaryContainer,
                    contentColor = MaterialTheme.colorScheme.onTertiaryContainer,
                    shape = RoundedCornerShape(12.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Text(
                            text = "Deep Insight",
                            style = MaterialTheme.typography.labelMedium,
                            fontWeight = FontWeight.Bold
                        )
                        Spacer(modifier = Modifier.height(6.dp))
                        Text(
                            text = revealedExplanation,
                            style = MaterialTheme.typography.bodyMedium
                        )
                    }
                }
            }

            if (hasAnswered) {
                Button(
                    onClick = onBackClick,
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text("Back to Today's Insight", fontWeight = FontWeight.Bold)
                }
            }
        }
    }
}

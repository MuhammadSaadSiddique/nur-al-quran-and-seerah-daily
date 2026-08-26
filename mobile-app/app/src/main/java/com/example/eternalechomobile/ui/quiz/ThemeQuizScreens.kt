package com.example.eternalechomobile.ui.quiz

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.ArrowForward
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.eternalechomobile.data.DataRepository
import com.example.eternalechomobile.data.QuizQuestion
import com.example.eternalechomobile.data.Theme
import com.example.eternalechomobile.data.DefaultDataRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import androidx.compose.foundation.BorderStroke
import androidx.compose.ui.platform.LocalContext
import androidx.lifecycle.compose.collectAsStateWithLifecycle

// --- VIEW MODELS ---

class ThemeSelectionViewModel(private val repository: DataRepository) : ViewModel() {
    private val _state = MutableStateFlow<ThemeSelectionUiState>(ThemeSelectionUiState.Loading)
    val state: StateFlow<ThemeSelectionUiState> = _state.asStateFlow()

    init {
        loadThemes()
    }

    fun loadThemes() {
        viewModelScope.launch {
            _state.value = ThemeSelectionUiState.Loading
            try {
                val themes = repository.getThemes()
                _state.value = ThemeSelectionUiState.Success(themes)
            } catch (t: Throwable) {
                _state.value = ThemeSelectionUiState.Error(t)
            }
        }
    }
}

sealed interface ThemeSelectionUiState {
    object Loading : ThemeSelectionUiState
    data class Success(val themes: List<Theme>) : ThemeSelectionUiState
    data class Error(val throwable: Throwable) : ThemeSelectionUiState
}

class PlayThemeQuizViewModel(
    private val themeId: Int,
    private val difficulty: String,
    private val repository: DataRepository
) : ViewModel() {
    private val _state = MutableStateFlow<PlayQuizUiState>(PlayQuizUiState.Loading)
    val state: StateFlow<PlayQuizUiState> = _state.asStateFlow()

    var currentQuestionIndex by mutableIntStateOf(0)
    var selectedOptionIndex by mutableStateOf<Int?>(null)
    var score by mutableIntStateOf(0)
    var quizFinished by mutableStateOf(false)
    val userAnswers = mutableListOf<Int?>()

    init {
        loadQuiz()
    }

    fun loadQuiz() {
        viewModelScope.launch {
            _state.value = PlayQuizUiState.Loading
            try {
                val rawQuestions = repository.getThemeQuiz(themeId, difficulty)
                // Shuffle options for each question so that Option A isn't always the correct answer
                val shuffledQuestions = rawQuestions.map { q ->
                    val correctText = q.options.getOrNull(q.correctAnswerIndex) ?: ""
                    val shuffled = q.options.shuffled()
                    val newCorrectIndex = shuffled.indexOf(correctText).coerceAtLeast(0)
                    q.copy(options = shuffled, correctAnswerIndex = newCorrectIndex)
                }
                _state.value = PlayQuizUiState.Success(shuffledQuestions)
            } catch (t: Throwable) {
                _state.value = PlayQuizUiState.Error(t)
            }
        }
    }

    fun submitAnswer(index: Int, correctAnswerIndex: Int) {
        if (selectedOptionIndex == null) {
            selectedOptionIndex = index
            userAnswers.add(index)
            if (index == correctAnswerIndex) {
                score++
            }
        }
    }

    fun nextQuestion(totalQuestions: Int) {
        if (selectedOptionIndex == null) {
            userAnswers.add(null)
        }
        selectedOptionIndex = null
        if (currentQuestionIndex + 1 < totalQuestions) {
            currentQuestionIndex++
        } else {
            quizFinished = true
        }
    }

    fun finishAndSubmit(userId: Int, themeName: String, questions: List<QuizQuestion>) {
        viewModelScope.launch {
            try {
                val questionsArray = org.json.JSONArray()
                questions.forEach { q ->
                    val obj = org.json.JSONObject()
                    obj.put("id", q.id)
                    obj.put("text", q.text)
                    obj.put("correctAnswerIndex", q.correctAnswerIndex)
                    obj.put("difficulty", q.difficulty)
                    val opts = org.json.JSONArray()
                    q.options.forEach { opts.put(it) }
                    obj.put("options", opts)
                    questionsArray.put(obj)
                }

                val answersArray = org.json.JSONArray()
                userAnswers.forEach { answersArray.put(it ?: -1) }

                repository.submitQuiz(
                    userId = userId,
                    type = "THEME",
                    title = themeName,
                    score = score,
                    totalQuestions = questions.size,
                    difficulty = difficulty,
                    questionsJson = questionsArray.toString(),
                    userAnswersJson = answersArray.toString()
                )
            } catch (e: Exception) {
                android.util.Log.e("PlayThemeQuizViewModel", "Failed to submit quiz score", e)
            }
        }
    }
}

sealed interface PlayQuizUiState {
    object Loading : PlayQuizUiState
    data class Success(val questions: List<QuizQuestion>) : PlayQuizUiState
    data class Error(val throwable: Throwable) : PlayQuizUiState
}

// --- COMPOSABLES ---

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ThemeSelectionScreen(
    onBackClick: () -> Unit,
    onThemeSelect: (Theme, String) -> Unit,
    onAuthClick: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: ThemeSelectionViewModel = viewModel { ThemeSelectionViewModel(DefaultDataRepository()) }
) {
    val state by viewModel.state.collectAsStateWithLifecycle()
    var selectedDifficulty by remember { mutableStateOf("Medium") }

    val context = LocalContext.current
    val prefs = remember { context.getSharedPreferences("app_prefs", android.content.Context.MODE_PRIVATE) }
    var completedQuizzes by remember { mutableIntStateOf(prefs.getInt("completed_quizzes", 0)) }
    var showLimitDialog by remember { mutableStateOf(false) }

    if (showLimitDialog) {
        AlertDialog(
            onDismissRequest = { showLimitDialog = false },
            title = { Text("Login Required") },
            text = { Text("You have completed 1 free guest quiz. Please sign in or create an account to unlock unlimited quizzes!") },
            confirmButton = {
                Button(onClick = {
                    showLimitDialog = false
                    onAuthClick()
                }) {
                    Text("Sign In")
                }
            },
            dismissButton = {
                TextButton(onClick = { showLimitDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Thematic Quizzes", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onBackClick) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primaryContainer,
                    titleContentColor = MaterialTheme.colorScheme.onPrimaryContainer
                )
            )
        }
    ) { paddingValues ->
        Column(
            modifier = modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(16.dp)
        ) {
            // Difficulty Selector
            Text(
                text = "Select Difficulty",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.padding(bottom = 8.dp)
            )
            Row(
                modifier = Modifier.fillMaxWidth().padding(bottom = 24.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                listOf("Easy", "Medium", "Hard").forEach { diff ->
                    val isSelected = selectedDifficulty == diff
                    Surface(
                        onClick = { selectedDifficulty = diff },
                        shape = RoundedCornerShape(16.dp),
                        color = if (isSelected) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surfaceVariant,
                        modifier = Modifier.weight(1f)
                    ) {
                        Text(
                            text = diff,
                            modifier = Modifier.padding(vertical = 10.dp),
                            textAlign = TextAlign.Center,
                            style = MaterialTheme.typography.labelMedium,
                            fontWeight = FontWeight.Bold,
                            color = if (isSelected) MaterialTheme.colorScheme.onPrimaryContainer else MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
            }

            Text(
                text = "Choose a Theme",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.padding(bottom = 12.dp)
            )

            when (val uiState = state) {
                is ThemeSelectionUiState.Loading -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator()
                    }
                }
                is ThemeSelectionUiState.Error -> {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text("Error loading Themes", color = MaterialTheme.colorScheme.error)
                            Spacer(modifier = Modifier.height(8.dp))
                            Button(onClick = { viewModel.loadThemes() }) {
                                Text("Retry")
                            }
                        }
                    }
                }
                is ThemeSelectionUiState.Success -> {
                    LazyColumn(
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        items(uiState.themes) { theme ->
                            Card(
                                onClick = {
                                    val currentIsLoggedIn = prefs.getInt("user_id", -1) != -1
                                    if (!currentIsLoggedIn && completedQuizzes >= 1) {
                                        showLimitDialog = true
                                    } else {
                                        onThemeSelect(theme, selectedDifficulty)
                                    }
                                },
                                modifier = Modifier.fillMaxWidth()
                            ) {
                                Column(modifier = Modifier.padding(16.dp)) {
                                    Row(
                                        modifier = Modifier.fillMaxWidth(),
                                        horizontalArrangement = Arrangement.SpaceBetween,
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Text(
                                            text = theme.name,
                                            style = MaterialTheme.typography.titleMedium,
                                            fontWeight = FontWeight.Bold,
                                            modifier = Modifier.weight(1f)
                                        )
                                        Badge(
                                            containerColor = if (theme.type == "PARA") {
                                                MaterialTheme.colorScheme.secondaryContainer
                                            } else {
                                                MaterialTheme.colorScheme.tertiaryContainer
                                            }
                                        ) {
                                            Text(
                                                text = if (theme.type == "PARA") "Quran" else "Seerah",
                                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                                                style = MaterialTheme.typography.labelSmall
                                            )
                                        }
                                    }
                                    if (!theme.description.isNullOrEmpty()) {
                                        Spacer(modifier = Modifier.height(6.dp))
                                        Text(
                                            text = theme.description,
                                            style = MaterialTheme.typography.bodyMedium,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PlayThemeQuizScreen(
    themeId: Int,
    themeName: String,
    difficulty: String,
    onBackClick: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: PlayThemeQuizViewModel = viewModel(key = "theme_${themeId}_${difficulty}") {
        PlayThemeQuizViewModel(themeId, difficulty, DefaultDataRepository())
    }

) {
    val state by viewModel.state.collectAsStateWithLifecycle()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(themeName, fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onBackClick) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primaryContainer,
                    titleContentColor = MaterialTheme.colorScheme.onPrimaryContainer
                )
            )
        }
    ) { paddingValues ->
        Box(
            modifier = modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(16.dp)
        ) {
            when (val uiState = state) {
                is PlayQuizUiState.Loading -> {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                }
                is PlayQuizUiState.Error -> {
                    Column(
                        modifier = Modifier.align(Alignment.Center),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Text("Error: ${uiState.throwable.message}", color = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(onClick = { viewModel.loadQuiz() }) {
                            Text("Retry")
                        }
                    }
                }
                is PlayQuizUiState.Success -> {
                    val questions = uiState.questions
                    if (questions.isEmpty()) {
                        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                            Text(
                                text = "No questions available for this theme/difficulty yet.",
                                textAlign = TextAlign.Center,
                                style = MaterialTheme.typography.bodyLarge
                            )
                        }
                    } else if (viewModel.quizFinished) {
                        // Increment completed quizzes in SharedPreferences on finish
                        val context = LocalContext.current
                        val prefs = remember { context.getSharedPreferences("app_prefs", android.content.Context.MODE_PRIVATE) }
                        LaunchedEffect(Unit) {
                            val userId = prefs.getInt("user_id", -1)
                            val isLoggedIn = userId != -1
                            if (!isLoggedIn) {
                                val current = prefs.getInt("completed_quizzes", 0)
                                prefs.edit().putInt("completed_quizzes", current + 1).apply()
                            } else {
                                viewModel.finishAndSubmit(userId, themeName, questions)
                            }
                        }

                        Card(
                            modifier = Modifier.align(Alignment.Center).fillMaxWidth(),
                            shape = RoundedCornerShape(24.dp)
                        ) {
                            Column(
                                modifier = Modifier.padding(24.dp),
                                horizontalAlignment = Alignment.CenterHorizontally
                            ) {
                                Text(
                                    text = "Quiz Completed!",
                                    style = MaterialTheme.typography.headlineMedium,
                                    fontWeight = FontWeight.Bold,
                                    color = MaterialTheme.colorScheme.primary
                                )
                                Spacer(modifier = Modifier.height(16.dp))
                                Text(
                                    text = "Your Score",
                                    style = MaterialTheme.typography.titleMedium
                                )
                                Text(
                                    text = "${viewModel.score} / ${questions.size}",
                                    style = MaterialTheme.typography.displayMedium,
                                    fontWeight = FontWeight.Black,
                                    color = MaterialTheme.colorScheme.secondary
                                )
                                Spacer(modifier = Modifier.height(24.dp))
                                Button(
                                    onClick = onBackClick,
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    Text("Back to Themes")
                                }
                            }
                        }
                    } else {
                        val currentQuestion = questions[viewModel.currentQuestionIndex]
                        val hasAnswered = viewModel.selectedOptionIndex != null

                        Column(modifier = Modifier.fillMaxSize()) {
                            // Progress bar
                            LinearProgressIndicator(
                                progress = { (viewModel.currentQuestionIndex + 1).toFloat() / questions.size.toFloat() },
                                modifier = Modifier.fillMaxWidth().padding(bottom = 8.dp)
                            )
                            Row(
                                modifier = Modifier.fillMaxWidth().padding(bottom = 16.dp),
                                horizontalArrangement = Arrangement.SpaceBetween
                            ) {
                                Text(
                                    text = "Question ${viewModel.currentQuestionIndex + 1} of ${questions.size}",
                                    style = MaterialTheme.typography.bodyMedium,
                                    fontWeight = FontWeight.Bold
                                )
                                Text(
                                    text = "Difficulty: ${currentQuestion.difficulty}",
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = MaterialTheme.colorScheme.secondary
                                )
                            }

                            Text(
                                text = currentQuestion.text,
                                style = MaterialTheme.typography.titleLarge,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier.padding(bottom = 24.dp)
                            )

                            LazyColumn(
                                modifier = Modifier.weight(1f),
                                verticalArrangement = Arrangement.spacedBy(10.dp)
                            ) {
                                items(currentQuestion.options.size) { index ->
                                    val option = currentQuestion.options[index]
                                    val isCorrect = index == currentQuestion.correctAnswerIndex
                                    val isSelected = index == viewModel.selectedOptionIndex

                                    val containerColor = when {
                                        !hasAnswered -> MaterialTheme.colorScheme.surface
                                        isCorrect -> MaterialTheme.colorScheme.primaryContainer
                                        isSelected -> MaterialTheme.colorScheme.errorContainer
                                        else -> MaterialTheme.colorScheme.surface
                                    }

                                    val contentColor = when {
                                        !hasAnswered -> MaterialTheme.colorScheme.onSurface
                                        isCorrect -> MaterialTheme.colorScheme.onPrimaryContainer
                                        isSelected -> MaterialTheme.colorScheme.onErrorContainer
                                        else -> MaterialTheme.colorScheme.onSurface
                                    }

                                    val borderColor = when {
                                        !hasAnswered -> MaterialTheme.colorScheme.outline
                                        isCorrect -> MaterialTheme.colorScheme.primary
                                        isSelected -> MaterialTheme.colorScheme.error
                                        else -> MaterialTheme.colorScheme.outlineVariant
                                    }

                                    OutlinedButton(
                                        onClick = { viewModel.submitAnswer(index, currentQuestion.correctAnswerIndex) },
                                        modifier = Modifier.fillMaxWidth(),
                                        shape = RoundedCornerShape(12.dp),
                                        colors = ButtonDefaults.outlinedButtonColors(
                                            containerColor = containerColor,
                                            contentColor = contentColor
                                        ),
                                        border = BorderStroke(1.dp, borderColor)
                                    ) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Text(
                                                text = "${(65 + index).toChar()}. $option",
                                                modifier = Modifier.weight(1f)
                                            )
                                            if (hasAnswered) {
                                                if (isCorrect) {
                                                    Text("✓", fontWeight = FontWeight.Bold)
                                                } else if (isSelected) {
                                                    Text("✗", fontWeight = FontWeight.Bold)
                                                }
                                            }
                                        }
                                    }
                                }

                                if (hasAnswered && !currentQuestion.explanation.isNullOrEmpty()) {
                                    item {
                                        Card(
                                            colors = CardDefaults.cardColors(
                                                containerColor = MaterialTheme.colorScheme.tertiaryContainer,
                                                contentColor = MaterialTheme.colorScheme.onTertiaryContainer
                                            ),
                                            shape = RoundedCornerShape(12.dp),
                                            modifier = Modifier.fillMaxWidth().padding(top = 16.dp)
                                        ) {
                                            Column(modifier = Modifier.padding(16.dp)) {
                                                Text(
                                                    text = "Explanation",
                                                    style = MaterialTheme.typography.titleSmall,
                                                    fontWeight = FontWeight.Bold
                                                )
                                                Spacer(modifier = Modifier.height(4.dp))
                                                Text(
                                                    text = currentQuestion.explanation,
                                                    style = MaterialTheme.typography.bodyMedium
                                                )
                                                if (!currentQuestion.reference.isNullOrEmpty()) {
                                                    Spacer(modifier = Modifier.height(8.dp))
                                                    Text(
                                                        text = "Ref: ${currentQuestion.reference}",
                                                        style = MaterialTheme.typography.labelSmall,
                                                        fontWeight = FontWeight.Bold,
                                                        color = MaterialTheme.colorScheme.tertiary
                                                    )
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            if (hasAnswered) {
                                Button(
                                    onClick = { viewModel.nextQuestion(questions.size) },
                                    modifier = Modifier.fillMaxWidth().padding(top = 16.dp)
                                ) {
                                    Row(
                                        horizontalArrangement = Arrangement.Center,
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Text(if (viewModel.currentQuestionIndex + 1 == questions.size) "Finish" else "Next")
                                        Spacer(modifier = Modifier.width(8.dp))
                                        Icon(Icons.Default.ArrowForward, contentDescription = "Next")
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

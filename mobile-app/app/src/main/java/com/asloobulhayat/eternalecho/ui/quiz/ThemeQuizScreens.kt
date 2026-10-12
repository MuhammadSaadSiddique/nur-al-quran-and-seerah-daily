package com.asloobulhayat.eternalecho.ui.quiz

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.ArrowForward
import androidx.compose.material.icons.filled.Share
import androidx.compose.material3.*
import com.asloobulhayat.eternalecho.util.ShareHelper
import androidx.compose.runtime.*
import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.foundation.Canvas
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Close
import androidx.compose.ui.draw.clip
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.text.style.TextOverflow
import kotlinx.coroutines.delay
import com.asloobulhayat.eternalecho.ui.adaptive.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import com.asloobulhayat.eternalecho.data.DataRepository
import com.asloobulhayat.eternalecho.data.QuizQuestion
import com.asloobulhayat.eternalecho.data.Theme
import com.asloobulhayat.eternalecho.data.DefaultDataRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import androidx.compose.foundation.BorderStroke
import androidx.compose.ui.platform.LocalContext
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.lifecycle.Lifecycle
import com.asloobulhayat.eternalecho.data.QuizSubmissionHelper
import com.asloobulhayat.eternalecho.security.SecurePreferences

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
    private val repository: DataRepository,
    private val quantity: Int = 20,
    private val quizType: String = "THEME"
) : ViewModel() {
    private val _state = MutableStateFlow<PlayQuizUiState>(PlayQuizUiState.Loading)
    val state: StateFlow<PlayQuizUiState> = _state.asStateFlow()

    var currentQuestionIndex by mutableIntStateOf(0)
    var selectedOptionIndex by mutableStateOf<Int?>(null)
    var isAnswerConfirmed by mutableStateOf(false)
    var score by mutableIntStateOf(0)
    var quizFinished by mutableStateOf(false)
    val userAnswers = mutableListOf<Int?>()
    var isScoreSubmitted by mutableStateOf(false)
    var isSubmittingScore by mutableStateOf(false)
    var submissionError by mutableStateOf<String?>(null)

    init {
        loadQuiz()
    }

    fun loadQuiz() {
        viewModelScope.launch {
            _state.value = PlayQuizUiState.Loading
            try {
                val isGrand = quizType.startsWith("GRAND")
                val rawQuestions = if (isGrand) {
                    val grandType = if (quizType.contains("SEERAH", ignoreCase = true)) "SEERAH" else "QURAN"
                    repository.getGrandQuiz(grandType, difficulty, quantity)
                } else {
                    repository.getThemeQuiz(themeId, difficulty, quantity)
                }
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

    fun selectOption(index: Int) {
        if (!isAnswerConfirmed) {
            selectedOptionIndex = index
        }
    }

    fun confirmAnswer(correctAnswerIndex: Int) {
        if (isAnswerConfirmed) return
        isAnswerConfirmed = true
        userAnswers.add(selectedOptionIndex)
        if (selectedOptionIndex == correctAnswerIndex) {
            score++
        }
    }

    fun submitAnswer(index: Int, correctAnswerIndex: Int) {
        selectOption(index)
        confirmAnswer(correctAnswerIndex)
    }

    fun nextQuestion(totalQuestions: Int) {
        if (!isAnswerConfirmed) {
            userAnswers.add(null)
        }
        selectedOptionIndex = null
        isAnswerConfirmed = false
        if (currentQuestionIndex + 1 < totalQuestions) {
            currentQuestionIndex++
        } else {
            quizFinished = true
        }
    }

    fun resetQuiz() {
        currentQuestionIndex = 0
        selectedOptionIndex = null
        isAnswerConfirmed = false
        score = 0
        quizFinished = false
        isScoreSubmitted = false
        isSubmittingScore = false
        submissionError = null
        userAnswers.clear()
        loadQuiz()
    }

    fun finishAndSubmit(
        userId: Int,
        themeName: String,
        questions: List<QuizQuestion>,
        onComplete: ((Boolean) -> Unit)? = null
    ) {
        if (isScoreSubmitted || isSubmittingScore || userId <= 0) return
        isSubmittingScore = true
        submissionError = null
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

                val submissionType = if (quizType.startsWith("GRAND")) "GRAND" else "THEME"
                val success = repository.submitQuiz(
                    userId = userId,
                    type = submissionType,
                    title = themeName,
                    score = score,
                    totalQuestions = questions.size,
                    difficulty = difficulty,
                    questionsJson = questionsArray.toString(),
                    userAnswersJson = answersArray.toString()
                )
                if (success) {
                    isScoreSubmitted = true
                    onComplete?.invoke(true)
                } else {
                    submissionError = "Could not record score. Please try again."
                    onComplete?.invoke(false)
                }
            } catch (e: Exception) {
                android.util.Log.e("PlayThemeQuizViewModel", "Failed to submit quiz score", e)
                submissionError = e.message ?: "Failed to submit quiz score"
                onComplete?.invoke(false)
            } finally {
                isSubmittingScore = false
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
    onThemeSelect: (Theme, String, Int) -> Unit,
    onGrandQuizLaunch: (String, String, String, Int) -> Unit = { _, _, _, _ -> },
    onAuthClick: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: ThemeSelectionViewModel = viewModel { ThemeSelectionViewModel(DefaultDataRepository()) }
) {
    val state by viewModel.state.collectAsStateWithLifecycle()
    var selectedDifficulty by remember { mutableStateOf("Medium") }
    var selectedQuantity by remember { mutableIntStateOf(20) }
    var selectedTab by remember { mutableIntStateOf(0) }

    val context = LocalContext.current
    val prefs = remember { SecurePreferences.getInstance(context) }
    var showLimitDialog by remember { mutableStateOf(false) }

    fun checkAndLaunch(action: () -> Unit) {
        val currentIsLoggedIn = prefs.getInt("user_id", -1) != -1
        val currentCompleted = prefs.getInt("completed_quizzes", 0)
        if (!currentIsLoggedIn && currentCompleted >= 1) {
            showLimitDialog = true
        } else {
            action()
        }
    }

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
                title = { Text("Thematic & Grand Quizzes", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onBackClick) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
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
               // .padding(paddingValues)
                .padding(horizontal = 16.dp, vertical = 8.dp)
        ) {
            // Quiz Quantity Selector (same as web: 20, 50, 100)
            Text(
                text = "Quiz Quantity",
                style = MaterialTheme.typography.titleSmall,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.padding(bottom = 6.dp)
            )
            Row(
                modifier = Modifier.fillMaxWidth().padding(bottom = 12.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                listOf(20, 50, 100).forEach { qty ->
                    val isSelected = selectedQuantity == qty
                    Surface(
                        onClick = { selectedQuantity = qty },
                        shape = RoundedCornerShape(12.dp),
                        color = if (isSelected) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surfaceVariant,
                        modifier = Modifier.weight(1f)
                    ) {
                        Text(
                            text = "$qty Qs",
                            modifier = Modifier.padding(vertical = 10.dp),
                            textAlign = TextAlign.Center,
                            style = MaterialTheme.typography.labelMedium,
                            fontWeight = FontWeight.Bold,
                            color = if (isSelected) MaterialTheme.colorScheme.onPrimaryContainer else MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
            }

            // Difficulty Selector (Easy, Medium, Hard)
            Text(
                text = "Select Difficulty",
                style = MaterialTheme.typography.titleSmall,
                fontWeight = FontWeight.Bold,
                modifier = Modifier.padding(bottom = 6.dp)
            )
            Row(
                modifier = Modifier.fillMaxWidth().padding(bottom = 14.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                listOf("Easy", "Medium", "Hard").forEach { diff ->
                    val isSelected = selectedDifficulty == diff
                    Surface(
                        onClick = { selectedDifficulty = diff },
                        shape = RoundedCornerShape(12.dp),
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

            // Tabs for Quran Themes, Seerah Themes, and Grand Quiz
            PrimaryTabRow(
                selectedTabIndex = selectedTab,
                modifier = Modifier.fillMaxWidth().padding(bottom = 12.dp)
            ) {
                Tab(
                    selected = selectedTab == 0,
                    onClick = { selectedTab = 0 },
                    text = {
                        val count = if (state is ThemeSelectionUiState.Success) {
                            (state as ThemeSelectionUiState.Success).themes.count { it.type.equals("PARA", ignoreCase = true) }
                        } else 0
                        Text(if (count > 0) "Quran ($count)" else "Quran Themes")
                    }
                )
                Tab(
                    selected = selectedTab == 1,
                    onClick = { selectedTab = 1 },
                    text = {
                        val count = if (state is ThemeSelectionUiState.Success) {
                            (state as ThemeSelectionUiState.Success).themes.count { it.type.equals("SEERAH", ignoreCase = true) }
                        } else 0
                        Text(if (count > 0) "Seerah ($count)" else "Seerah Themes")
                    }
                )
                Tab(
                    selected = selectedTab == 2,
                    onClick = { selectedTab = 2 },
                    text = { Text("Grand Quiz") }
                )
            }

            if (selectedTab == 2) {
                // Grand Quiz Tab (Exact match to web Grand Quiz section)
                LazyColumn(
                    verticalArrangement = Arrangement.spacedBy(16.dp),
                    modifier = Modifier.fillMaxSize()
                ) {
                    // Grand Quran Quiz Card
                    item {
                        Card(
                            onClick = {
                                checkAndLaunch {
                                    onGrandQuizLaunch("GRAND_QURAN", "Grand Quran Quiz", selectedDifficulty, selectedQuantity)
                                }
                            },
                            shape = RoundedCornerShape(18.dp),
                            colors = CardDefaults.cardColors(
                                containerColor = MaterialTheme.colorScheme.secondaryContainer,
                                contentColor = MaterialTheme.colorScheme.onSecondaryContainer
                            ),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Column(modifier = Modifier.padding(18.dp)) {
                                Text(
                                    text = "Grand Quran Quiz",
                                    style = MaterialTheme.typography.titleLarge,
                                    fontWeight = FontWeight.Bold
                                )
                                Spacer(modifier = Modifier.height(4.dp))
                                Text(
                                    text = "$selectedQuantity questions from all 30 Paras",
                                    style = MaterialTheme.typography.bodyMedium
                                )
                                Spacer(modifier = Modifier.height(14.dp))

                                Button(
                                    onClick = {
                                        checkAndLaunch {
                                            onGrandQuizLaunch("GRAND_QURAN", "Grand Quran Quiz", selectedDifficulty, selectedQuantity)
                                        }
                                    },
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(10.dp)
                                ) {
                                    Text("Start Grand Quran Quiz", fontWeight = FontWeight.Bold, fontSize = 13.sp)
                                }
                            }
                        }
                    }

                    // Grand Seerah Quiz Card
                    item {
                        Card(
                            onClick = {
                                checkAndLaunch {
                                    onGrandQuizLaunch("GRAND_SEERAH", "Grand Seerah Quiz", selectedDifficulty, selectedQuantity)
                                }
                            },
                            shape = RoundedCornerShape(18.dp),
                            colors = CardDefaults.cardColors(
                                containerColor = MaterialTheme.colorScheme.tertiaryContainer,
                                contentColor = MaterialTheme.colorScheme.onTertiaryContainer
                            ),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Column(modifier = Modifier.padding(18.dp)) {
                                Text(
                                    text = "Grand Seerah Quiz",
                                    style = MaterialTheme.typography.titleLarge,
                                    fontWeight = FontWeight.Bold
                                )
                                Spacer(modifier = Modifier.height(4.dp))
                                Text(
                                    text = "$selectedQuantity questions from all Seerah themes",
                                    style = MaterialTheme.typography.bodyMedium
                                )
                                Spacer(modifier = Modifier.height(14.dp))
                                Button(
                                    onClick = {
                                        checkAndLaunch {
                                            onGrandQuizLaunch("GRAND_SEERAH", "Grand Seerah Quiz", selectedDifficulty, selectedQuantity)
                                        }
                                    },
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(10.dp)
                                ) {
                                    Text("Start Grand Seerah Quiz", fontWeight = FontWeight.Bold, fontSize = 13.sp)
                                }
                            }
                        }
                    }
                }
            } else {
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
                        when (selectedTab) {
                            0 -> {
                                // Quran Themes (Clean list matching web)
                                val quranThemes = remember(uiState.themes) {
                                    uiState.themes.filter { it.type.equals("PARA", ignoreCase = true) }
                                }
                                LazyColumn(
                                    verticalArrangement = Arrangement.spacedBy(10.dp),
                                    modifier = Modifier.fillMaxSize()
                                ) {
                                    items(quranThemes) { theme ->
                                        Card(
                                            onClick = {
                                                checkAndLaunch {
                                                    onThemeSelect(theme, selectedDifficulty, selectedQuantity)
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
                                                    Icon(
                                                        Icons.AutoMirrored.Filled.ArrowForward,
                                                        contentDescription = "Start Quiz",
                                                        tint = MaterialTheme.colorScheme.primary,
                                                        modifier = Modifier.size(20.dp)
                                                    )
                                                }
                                                if (!theme.description.isNullOrEmpty()) {
                                                    Spacer(modifier = Modifier.height(4.dp))
                                                    Text(
                                                        text = theme.description,
                                                        style = MaterialTheme.typography.bodyMedium,
                                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                                    )
                                                }
                                                Spacer(modifier = Modifier.height(8.dp))
                                                Text(
                                                    text = "$selectedQuantity Questions • $selectedDifficulty",
                                                    style = MaterialTheme.typography.labelSmall,
                                                    color = MaterialTheme.colorScheme.primary,
                                                    fontWeight = FontWeight.SemiBold
                                                )
                                            }
                                        }
                                    }
                                }
                            }
                            1 -> {
                                // Seerah Themes (Clean list matching web, with General Seerah at the top)
                                val seerahThemes = remember(uiState.themes) {
                                    uiState.themes.filter { it.type.equals("SEERAH", ignoreCase = true) }
                                }
                                LazyColumn(
                                    verticalArrangement = Arrangement.spacedBy(10.dp),
                                    modifier = Modifier.fillMaxSize()
                                ) {
                                    // General (All Topics) option - exact match to web's General Seerah
                                    item {
                                        Card(
                                            onClick = {
                                                checkAndLaunch {
                                                    onGrandQuizLaunch("GRAND_SEERAH", "General Seerah", selectedDifficulty, selectedQuantity)
                                                }
                                            },
                                            colors = CardDefaults.cardColors(
                                                containerColor = MaterialTheme.colorScheme.secondaryContainer,
                                                contentColor = MaterialTheme.colorScheme.onSecondaryContainer
                                            ),
                                            shape = RoundedCornerShape(14.dp),
                                            modifier = Modifier.fillMaxWidth()
                                        ) {
                                            Column(modifier = Modifier.padding(16.dp)) {
                                                Row(
                                                    modifier = Modifier.fillMaxWidth(),
                                                    horizontalArrangement = Arrangement.SpaceBetween,
                                                    verticalAlignment = Alignment.CenterVertically
                                                ) {
                                                    Text(
                                                        text = "General (All Topics)",
                                                        style = MaterialTheme.typography.titleMedium,
                                                        fontWeight = FontWeight.Bold,
                                                        modifier = Modifier.weight(1f)
                                                    )
                                                    Icon(
                                                        Icons.AutoMirrored.Filled.ArrowForward,
                                                        contentDescription = "Start Quiz",
                                                        tint = MaterialTheme.colorScheme.onSecondaryContainer,
                                                        modifier = Modifier.size(20.dp)
                                                    )
                                                }
                                                Spacer(modifier = Modifier.height(4.dp))
                                                Text(
                                                    text = "Comprehensive life journey across all Seerah topics",
                                                    style = MaterialTheme.typography.bodyMedium
                                                )
                                                Spacer(modifier = Modifier.height(8.dp))
                                                Text(
                                                    text = "$selectedQuantity Questions • $selectedDifficulty",
                                                    style = MaterialTheme.typography.labelSmall,
                                                    fontWeight = FontWeight.SemiBold
                                                )
                                            }
                                        }
                                    }

                                    items(seerahThemes) { theme ->
                                        Card(
                                            onClick = {
                                                checkAndLaunch {
                                                    onThemeSelect(theme, selectedDifficulty, selectedQuantity)
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
                                                    Icon(
                                                        Icons.AutoMirrored.Filled.ArrowForward,
                                                        contentDescription = "Start Quiz",
                                                        tint = MaterialTheme.colorScheme.primary,
                                                        modifier = Modifier.size(20.dp)
                                                    )
                                                }
                                                if (!theme.description.isNullOrEmpty()) {
                                                    Spacer(modifier = Modifier.height(4.dp))
                                                    Text(
                                                        text = theme.description,
                                                        style = MaterialTheme.typography.bodyMedium,
                                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                                    )
                                                }
                                                Spacer(modifier = Modifier.height(8.dp))
                                                Text(
                                                    text = "$selectedQuantity Questions • $selectedDifficulty",
                                                    style = MaterialTheme.typography.labelSmall,
                                                    color = MaterialTheme.colorScheme.primary,
                                                    fontWeight = FontWeight.SemiBold
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
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PlayThemeQuizScreen(
    themeId: Int,
    themeName: String,
    difficulty: String,
    quantity: Int = 20,
    quizType: String = "THEME",
    sessionId: Long = 0L,
    onBackClick: () -> Unit,
    onAuthClick: () -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: PlayThemeQuizViewModel = viewModel(key = "quiz_${quizType}_${themeId}_${difficulty}_${quantity}_$sessionId") {
        PlayThemeQuizViewModel(themeId, difficulty, DefaultDataRepository(), quantity, quizType)
    },
    adaptiveInfo: WindowAdaptiveInfo = rememberWindowAdaptiveInfo()
) {
    val state by viewModel.state.collectAsStateWithLifecycle()
    val context = LocalContext.current
    val prefs = remember { SecurePreferences.getInstance(context) }

    var resumeTrigger by remember { mutableIntStateOf(0) }
    val lifecycleOwner = androidx.lifecycle.compose.LocalLifecycleOwner.current
    DisposableEffect(lifecycleOwner) {
        val observer = LifecycleEventObserver { _, event ->
            if (event == Lifecycle.Event.ON_RESUME) {
                resumeTrigger++
            }
        }
        lifecycleOwner.lifecycle.addObserver(observer)
        onDispose {
            lifecycleOwner.lifecycle.removeObserver(observer)
        }
    }

    val userId = remember(resumeTrigger) { prefs.getInt("user_id", -1) }
    val userName = remember(resumeTrigger) { prefs.getString("user_name", "") ?: "" }
    val isLoggedIn = userId != -1
    var showLimitDialog by remember { mutableStateOf(false) }

    if (showLimitDialog) {
        AlertDialog(
            onDismissRequest = { showLimitDialog = false },
            title = { Text("Quiz Limit Reached") },
            text = { Text("You have completed 1 free guest quiz. Sign up or log in now to unlock unlimited quizzes and keep track of your scores!") },
            confirmButton = {
                Button(onClick = {
                    showLimitDialog = false
                    onAuthClick()
                }) {
                    Text("Sign Up / Sign In")
                }
            },
            dismissButton = {
                TextButton(onClick = { showLimitDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }

    var showExitDialog by remember { mutableStateOf(false) }

    // Countdown Timer logic: 20 seconds per question (matches 18s in mockup)
    val maxQuestionTime = 20
    var timeLeft by remember(viewModel.currentQuestionIndex) { mutableIntStateOf(maxQuestionTime) }

    LaunchedEffect(viewModel.currentQuestionIndex, viewModel.isAnswerConfirmed, state) {
        if (!viewModel.isAnswerConfirmed) {
            timeLeft = maxQuestionTime
            while (timeLeft > 0 && !viewModel.isAnswerConfirmed) {
                delay(1000L)
                timeLeft--
            }
            if (timeLeft == 0 && !viewModel.isAnswerConfirmed) {
                val currentQ = (state as? PlayQuizUiState.Success)?.questions?.getOrNull(viewModel.currentQuestionIndex)
                if (currentQ != null) {
                    viewModel.confirmAnswer(currentQ.correctAnswerIndex)
                }
            }
        }
    }

    if (showExitDialog) {
        AlertDialog(
            onDismissRequest = { showExitDialog = false },
            title = { Text("Exit Quiz?", fontWeight = FontWeight.Bold) },
            text = { Text("Are you sure you want to exit? Your progress for this quiz session will be lost.") },
            confirmButton = {
                Button(
                    onClick = {
                        showExitDialog = false
                        onBackClick()
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                ) {
                    Text("Exit")
                }
            },
            dismissButton = {
                TextButton(onClick = { showExitDialog = false }) {
                    Text("Stay")
                }
            }
        )
    }

    // Outer Edge-to-Edge Islamic Emerald Background
    Box(
        modifier = modifier
            .fillMaxSize()
            .background(
                Brush.verticalGradient(
                    colors = listOf(
                        Color(0xFF025A3D),
                        Color(0xFF023F2A),
                        Color(0xFF012217)
                    )
                )
            )
    ) {
        // Decorative Islamic Rosette Watermark in Top-Right Background
        IslamicGeometricRosette(
            modifier = Modifier
                .size(280.dp)
                .align(Alignment.TopEnd)
                .offset(x = 90.dp, y = (-40).dp),
            strokeColor = Color(0xFFA7F3D0).copy(alpha = 0.12f),
            strokeWidth = 1.2.dp
        )

        when (val uiState = state) {
            is PlayQuizUiState.Loading -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        CircularProgressIndicator(color = Color(0xFF10B981))
                        Spacer(modifier = Modifier.height(16.dp))
                        Text(
                            text = "Loading Quiz Questions...",
                            color = Color(0xFFD1FAE5),
                            style = MaterialTheme.typography.bodyMedium
                        )
                    }
                }
            }
            is PlayQuizUiState.Error -> {
                Box(modifier = Modifier.fillMaxSize().padding(24.dp), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text(
                            text = "Error: ${uiState.throwable.message}",
                            color = Color(0xFFFCA5A5),
                            textAlign = TextAlign.Center
                        )
                        Spacer(modifier = Modifier.height(16.dp))
                        Button(
                            onClick = { viewModel.loadQuiz() },
                            colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF10B981))
                        ) {
                            Text("Retry")
                        }
                    }
                }
            }
            is PlayQuizUiState.Success -> {
                val questions = uiState.questions
                if (questions.isEmpty()) {
                    Box(modifier = Modifier.fillMaxSize().padding(24.dp), contentAlignment = Alignment.Center) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text(
                                text = if (quizType.startsWith("GRAND")) "No questions available for this Grand Quiz yet." else "No questions available for this theme/difficulty yet.",
                                textAlign = TextAlign.Center,
                                color = Color.White,
                                style = MaterialTheme.typography.bodyLarge
                            )
                            Spacer(modifier = Modifier.height(16.dp))
                            Button(
                                onClick = onBackClick,
                                colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF10B981))
                            ) {
                                Text("Go Back")
                            }
                        }
                    }
                } else if (viewModel.quizFinished) {
                    // Results View
                    var guestCountRecorded by remember { mutableStateOf(false) }

                    LaunchedEffect(viewModel.quizFinished, resumeTrigger, userId) {
                        if (viewModel.quizFinished) {
                            if (!isLoggedIn) {
                                if (!guestCountRecorded) {
                                    guestCountRecorded = true
                                    val current = prefs.getInt("completed_quizzes", 0)
                                    prefs.putInt("completed_quizzes", current + 1)
                                }
                                QuizSubmissionHelper.savePendingQuiz(
                                    prefs = prefs,
                                    type = if (quizType.startsWith("GRAND")) "GRAND" else "THEME",
                                    title = themeName,
                                    score = viewModel.score,
                                    totalQuestions = questions.size,
                                    difficulty = difficulty,
                                    questions = questions,
                                    userAnswers = viewModel.userAnswers
                                )
                            } else {
                                if (!viewModel.isScoreSubmitted && !viewModel.isSubmittingScore) {
                                    viewModel.finishAndSubmit(userId, themeName, questions) { success ->
                                        if (success) {
                                            QuizSubmissionHelper.clearPendingQuiz(prefs)
                                        }
                                    }
                                }
                            }
                        }
                    }

                    Box(
                        modifier = Modifier
                            .fillMaxSize()
                            .statusBarsPadding()
                            .navigationBarsPadding()
                            .padding(20.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        Card(
                            modifier = Modifier
                                .fillMaxWidth()
                                .widthIn(max = 500.dp),
                            shape = RoundedCornerShape(26.dp),
                            colors = CardDefaults.cardColors(containerColor = Color(0xFF033E29)),
                            border = BorderStroke(1.dp, Color(0xFF10B981).copy(alpha = 0.5f)),
                            elevation = CardDefaults.cardElevation(defaultElevation = 10.dp)
                        ) {
                            Column(
                                modifier = Modifier
                                    .padding(24.dp)
                                    .verticalScroll(rememberScrollState()),
                                horizontalAlignment = Alignment.CenterHorizontally
                            ) {
                                Text(
                                    text = "🏆",
                                    fontSize = 42.sp
                                )
                                Spacer(modifier = Modifier.height(8.dp))
                                Text(
                                    text = "Quiz Completed!",
                                    style = MaterialTheme.typography.headlineMedium,
                                    fontWeight = FontWeight.Bold,
                                    color = Color.White
                                )
                                Spacer(modifier = Modifier.height(16.dp))

                                Surface(
                                    shape = RoundedCornerShape(16.dp),
                                    color = Color(0xFF022B1D),
                                    border = BorderStroke(1.dp, Color(0xFFD97706)),
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    Column(
                                        modifier = Modifier.padding(16.dp),
                                        horizontalAlignment = Alignment.CenterHorizontally
                                    ) {
                                        Text(
                                            text = "Your Score",
                                            style = MaterialTheme.typography.titleSmall,
                                            color = Color(0xFFFDE68A)
                                        )
                                        Text(
                                            text = "${viewModel.score} / ${questions.size}",
                                            style = MaterialTheme.typography.displayMedium,
                                            fontWeight = FontWeight.Black,
                                            color = Color.White
                                        )
                                        Text(
                                            text = "+${viewModel.score * 30} Points Earned",
                                            style = MaterialTheme.typography.labelMedium,
                                            fontWeight = FontWeight.Bold,
                                            color = Color(0xFF34D399)
                                        )
                                    }
                                }

                                val percentage = if (questions.isNotEmpty()) (viewModel.score * 100) / questions.size else 0
                                Spacer(modifier = Modifier.height(12.dp))
                                Text(
                                    text = when {
                                        percentage >= 80 -> "🌟 Outstanding performance!"
                                        percentage >= 50 -> "👍 Good effort, keep learning!"
                                        else -> "📖 Review the themes and try again!"
                                    },
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = Color(0xFFD1FAE5),
                                    textAlign = TextAlign.Center
                                )

                                Spacer(modifier = Modifier.height(18.dp))

                                if (isLoggedIn) {
                                    if (viewModel.isSubmittingScore) {
                                        Row(
                                            verticalAlignment = Alignment.CenterVertically,
                                            horizontalArrangement = Arrangement.Center,
                                            modifier = Modifier.fillMaxWidth().padding(vertical = 8.dp)
                                        ) {
                                            CircularProgressIndicator(
                                                modifier = Modifier.size(18.dp),
                                                strokeWidth = 2.dp,
                                                color = Color(0xFF10B981)
                                            )
                                            Spacer(modifier = Modifier.width(10.dp))
                                            Text(
                                                text = "Saving score to your account...",
                                                style = MaterialTheme.typography.bodyMedium,
                                                color = Color(0xFFD1FAE5)
                                            )
                                        }
                                    } else if (viewModel.isScoreSubmitted) {
                                        Surface(
                                            color = Color(0xFF065F46).copy(alpha = 0.5f),
                                            shape = RoundedCornerShape(12.dp),
                                            border = BorderStroke(1.dp, Color(0xFF10B981)),
                                            modifier = Modifier.fillMaxWidth()
                                        ) {
                                            Row(
                                                modifier = Modifier.padding(12.dp),
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Text("✓", fontWeight = FontWeight.Black, fontSize = 20.sp, color = Color(0xFF34D399))
                                                Spacer(modifier = Modifier.width(10.dp))
                                                Column {
                                                    Text(
                                                        text = "Score Saved to Account!",
                                                        style = MaterialTheme.typography.titleSmall,
                                                        fontWeight = FontWeight.Bold,
                                                        color = Color.White
                                                    )
                                                    if (userName.isNotEmpty()) {
                                                        Text(
                                                            text = "Recorded for $userName",
                                                            style = MaterialTheme.typography.bodySmall,
                                                            color = Color(0xFFA7F3D0)
                                                        )
                                                    }
                                                }
                                            }
                                        }
                                    } else if (viewModel.submissionError != null) {
                                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                            Text(
                                                text = viewModel.submissionError ?: "Failed to save score",
                                                color = Color(0xFFFCA5A5),
                                                style = MaterialTheme.typography.bodySmall
                                            )
                                            TextButton(onClick = {
                                                viewModel.finishAndSubmit(userId, themeName, questions)
                                            }) {
                                                Text("Retry Saving Score", color = Color(0xFF34D399))
                                            }
                                        }
                                    }
                                } else {
                                    Surface(
                                        color = Color(0xFF3B2E15),
                                        shape = RoundedCornerShape(16.dp),
                                        border = BorderStroke(1.dp, Color(0xFFD97706)),
                                        modifier = Modifier.fillMaxWidth()
                                    ) {
                                        Column(
                                            modifier = Modifier.padding(16.dp),
                                            horizontalAlignment = Alignment.CenterHorizontally
                                        ) {
                                            Text(
                                                text = "Save Score to Leaderboard",
                                                style = MaterialTheme.typography.titleMedium,
                                                fontWeight = FontWeight.Bold,
                                                color = Color(0xFFFDE68A),
                                                textAlign = TextAlign.Center
                                            )
                                            Spacer(modifier = Modifier.height(6.dp))
                                            Text(
                                                text = "Create an account or sign in to save your score of ${viewModel.score}/${questions.size} to your profile and unlock unlimited quizzes!",
                                                style = MaterialTheme.typography.bodySmall,
                                                color = Color(0xFFFEF3C7),
                                                textAlign = TextAlign.Center
                                            )
                                            Spacer(modifier = Modifier.height(12.dp))
                                            Button(
                                                onClick = onAuthClick,
                                                modifier = Modifier.fillMaxWidth(),
                                                colors = ButtonDefaults.buttonColors(
                                                    containerColor = Color(0xFF10B981),
                                                    contentColor = Color.White
                                                ),
                                                shape = RoundedCornerShape(10.dp)
                                            ) {
                                                Text("Sign Up to Save Score", fontWeight = FontWeight.Bold)
                                            }
                                        }
                                    }
                                }

                                Spacer(modifier = Modifier.height(18.dp))

                                OutlinedButton(
                                    onClick = {
                                        ShareHelper.shareQuizResult(
                                            context = context,
                                            quizTitle = themeName,
                                            score = viewModel.score,
                                            total = questions.size
                                        )
                                    },
                                    border = BorderStroke(1.dp, Color(0xFF34D399)),
                                    colors = ButtonDefaults.outlinedButtonColors(contentColor = Color(0xFF34D399)),
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(24.dp)
                                ) {
                                    Icon(
                                        imageVector = Icons.Default.Share,
                                        contentDescription = null,
                                        modifier = Modifier.size(18.dp)
                                    )
                                    Spacer(modifier = Modifier.width(8.dp))
                                    Text("Share Your Score", fontWeight = FontWeight.Bold)
                                }

                                Spacer(modifier = Modifier.height(10.dp))

                                Button(
                                    onClick = {
                                        val currentUserId = prefs.getInt("user_id", -1)
                                        val currentCompleted = prefs.getInt("completed_quizzes", 0)
                                        if (currentUserId == -1 && currentCompleted >= 1) {
                                            showLimitDialog = true
                                        } else {
                                            viewModel.resetQuiz()
                                        }
                                    },
                                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF10B981)),
                                    modifier = Modifier.fillMaxWidth(),
                                    shape = RoundedCornerShape(24.dp)
                                ) {
                                    Text("Play Again", fontWeight = FontWeight.Bold)
                                }

                                Spacer(modifier = Modifier.height(8.dp))

                                TextButton(
                                    onClick = onBackClick,
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    Text(
                                        text = if (quizType.startsWith("GRAND")) "Back to Quizzes" else "Back to Themes",
                                        color = Color(0xFFA7F3D0)
                                    )
                                }
                            }
                        }
                    }
                } else {
                    // Active Quiz Screen matching User Design Mockup
                    val currentQuestion = questions[viewModel.currentQuestionIndex]

                    if (adaptiveInfo.isTableTop) {
                        // TableTop Mode (Foldables)
                        AdaptiveTwoPane(
                            adaptiveInfo = adaptiveInfo,
                            firstPane = {
                                Column(
                                    modifier = Modifier
                                        .fillMaxSize()
                                        .statusBarsPadding()
                                        .padding(16.dp)
                                ) {
                                    QuizHeaderSection(
                                        themeName = themeName,
                                        onBackClick = { showExitDialog = true },
                                        currentQuestionIndex = viewModel.currentQuestionIndex,
                                        totalQuestions = questions.size,
                                        timeLeft = timeLeft,
                                        maxTime = maxQuestionTime,
                                        score = viewModel.score
                                    )
                                    Spacer(modifier = Modifier.height(16.dp))
                                    QuizQuestionCard(questionText = currentQuestion.text)
                                }
                            },
                            secondPane = {
                                Column(
                                    modifier = Modifier
                                        .fillMaxSize()
                                        .navigationBarsPadding()
                                        .padding(16.dp)
                                ) {
                                    QuizOptionsAndActionButton(
                                        currentQuestion = currentQuestion,
                                        selectedOptionIndex = viewModel.selectedOptionIndex,
                                        isConfirmed = viewModel.isAnswerConfirmed,
                                        isLastQuestion = viewModel.currentQuestionIndex + 1 == questions.size,
                                        onSelectOption = { viewModel.selectOption(it) },
                                        onConfirmAnswer = { viewModel.confirmAnswer(currentQuestion.correctAnswerIndex) },
                                        onNextQuestion = { viewModel.nextQuestion(questions.size) },
                                        modifier = Modifier.weight(1f)
                                    )
                                }
                            }
                        )
                    } else {
                        // Standard Layout with Ergonomic Centering
                        Box(
                            modifier = Modifier
                                .fillMaxSize()
                                .statusBarsPadding()
                                .navigationBarsPadding(),
                            contentAlignment = Alignment.TopCenter
                        ) {
                            Column(
                                modifier = Modifier
                                    .fillMaxSize()
                                    .widthIn(max = 680.dp)
                                    .padding(horizontal = 20.dp, vertical = 10.dp)
                            ) {
                                QuizHeaderSection(
                                    themeName = themeName,
                                    onBackClick = { showExitDialog = true },
                                    currentQuestionIndex = viewModel.currentQuestionIndex,
                                    totalQuestions = questions.size,
                                    timeLeft = timeLeft,
                                    maxTime = maxQuestionTime,
                                    score = viewModel.score
                                )

                                Spacer(modifier = Modifier.height(18.dp))

                                // Scrollable body containing Question Card and Options
                                Column(
                                    modifier = Modifier
                                        .weight(1f)
                                        .verticalScroll(rememberScrollState()),
                                    verticalArrangement = Arrangement.spacedBy(16.dp)
                                ) {
                                    QuizQuestionCard(questionText = currentQuestion.text)

                                    QuizOptionsList(
                                        options = currentQuestion.options,
                                        correctAnswerIndex = currentQuestion.correctAnswerIndex,
                                        selectedOptionIndex = viewModel.selectedOptionIndex,
                                        isConfirmed = viewModel.isAnswerConfirmed,
                                        onSelectOption = { viewModel.selectOption(it) }
                                    )

                                    if (viewModel.isAnswerConfirmed && !currentQuestion.explanation.isNullOrEmpty()) {
                                        QuizExplanationCard(
                                            explanation = currentQuestion.explanation,
                                            reference = currentQuestion.reference
                                        )
                                    }

                                    Spacer(modifier = Modifier.height(12.dp))
                                }

                                Spacer(modifier = Modifier.height(12.dp))

                                // Bottom Pill Action Button: "Confirm Answer" / "Next Question"
                                val isLastQuestion = viewModel.currentQuestionIndex + 1 == questions.size
                                Button(
                                    onClick = {
                                        if (!viewModel.isAnswerConfirmed) {
                                            viewModel.confirmAnswer(currentQuestion.correctAnswerIndex)
                                        } else {
                                            viewModel.nextQuestion(questions.size)
                                        }
                                    },
                                    enabled = viewModel.isAnswerConfirmed || viewModel.selectedOptionIndex != null,
                                    shape = RoundedCornerShape(32.dp),
                                    colors = ButtonDefaults.buttonColors(
                                        containerColor = Color(0xFF10B981),
                                        contentColor = Color.White,
                                        disabledContainerColor = Color(0xFF10B981).copy(alpha = 0.35f),
                                        disabledContentColor = Color.White.copy(alpha = 0.6f)
                                    ),
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .height(56.dp)
                                ) {
                                    Text(
                                        text = if (!viewModel.isAnswerConfirmed) "Confirm Answer" else if (isLastQuestion) "View Results" else "Next Question",
                                        style = MaterialTheme.typography.titleMedium.copy(
                                            fontWeight = FontWeight.Bold,
                                            fontSize = 16.5.sp
                                        )
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

// --- SUBCOMPONENTS FOR QUIZ SCREEN DESIGN ---

@Composable
fun QuizHeaderSection(
    themeName: String,
    onBackClick: () -> Unit,
    currentQuestionIndex: Int,
    totalQuestions: Int,
    timeLeft: Int,
    maxTime: Int,
    score: Int,
    modifier: Modifier = Modifier
) {
    Column(modifier = modifier.fillMaxWidth()) {
        // Top Bar: Back button and centered Title
        Row(
            modifier = Modifier.fillMaxWidth(),
            verticalAlignment = Alignment.CenterVertically
        ) {
            IconButton(
                onClick = onBackClick,
                modifier = Modifier.size(38.dp)
            ) {
                Icon(
                    imageVector = Icons.AutoMirrored.Filled.ArrowBack,
                    contentDescription = "Exit Quiz",
                    tint = Color.White
                )
            }

            Text(
                text = themeName,
                color = Color.White,
                style = MaterialTheme.typography.titleMedium.copy(
                    fontWeight = FontWeight.Bold,
                    fontSize = 17.sp
                ),
                textAlign = TextAlign.Center,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
                modifier = Modifier
                    .weight(1f)
                    .padding(horizontal = 8.dp)
            )

            // Balancer spacer for true centering
            Spacer(modifier = Modifier.size(38.dp))
        }

        Spacer(modifier = Modifier.height(10.dp))

        // Full-width Rounded Progress Bar
        val progressVal = if (totalQuestions > 0) (currentQuestionIndex + 1).toFloat() / totalQuestions.toFloat() else 0f
        val animatedProgress by animateFloatAsState(targetValue = progressVal, label = "quizProgress")

        Box(
            modifier = Modifier
                .fillMaxWidth()
                .height(7.dp)
                .clip(RoundedCornerShape(4.dp))
                .background(Color(0xFF073824))
        ) {
            Box(
                modifier = Modifier
                    .fillMaxWidth(animatedProgress)
                    .fillMaxHeight()
                    .clip(RoundedCornerShape(4.dp))
                    .background(Color(0xFF10B981))
            )
        }

        Spacer(modifier = Modifier.height(14.dp))

        // Sub-Row: Question count (left) | Circular Timer (center) | Score Badge (right)
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Left: Question Count with mini progress bar
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = "Question ${currentQuestionIndex + 1} of $totalQuestions",
                    color = Color(0xFFD1FAE5),
                    style = MaterialTheme.typography.bodyMedium.copy(
                        fontWeight = FontWeight.SemiBold,
                        fontSize = 13.sp
                    )
                )
                Spacer(modifier = Modifier.height(4.dp))
                Box(
                    modifier = Modifier
                        .width(76.dp)
                        .height(4.dp)
                        .clip(RoundedCornerShape(2.dp))
                        .background(Color(0xFF073824))
                ) {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth(animatedProgress)
                            .fillMaxHeight()
                            .clip(RoundedCornerShape(2.dp))
                            .background(Color(0xFF10B981))
                    )
                }
            }

            // Center: Circular Countdown Timer
            val timerProgress = (timeLeft.toFloat() / maxTime.toFloat()).coerceIn(0f, 1f)
            Box(
                modifier = Modifier.size(54.dp),
                contentAlignment = Alignment.Center
            ) {
                Canvas(modifier = Modifier.size(54.dp)) {
                    val stroke = 3.5.dp.toPx()
                    // Background circle ring
                    drawCircle(
                        color = Color(0xFF07432D),
                        radius = size.minDimension / 2f - stroke / 2f,
                        style = Stroke(width = stroke)
                    )
                    // Sweep countdown arc
                    drawArc(
                        color = if (timeLeft <= 5) Color(0xFFEF4444) else Color(0xFF2DD4BF),
                        startAngle = -90f,
                        sweepAngle = timerProgress * 360f,
                        useCenter = false,
                        style = Stroke(width = stroke, cap = StrokeCap.Round)
                    )
                }
                Text(
                    text = "${timeLeft}s",
                    color = Color.White,
                    style = MaterialTheme.typography.labelLarge.copy(
                        fontWeight = FontWeight.Bold,
                        fontSize = 15.sp
                    )
                )
            }

            // Right: Trophy Score Badge
            Box(
                modifier = Modifier.weight(1f),
                contentAlignment = Alignment.CenterEnd
            ) {
                Surface(
                    shape = RoundedCornerShape(12.dp),
                    color = Color(0xFF2E220D),
                    border = BorderStroke(1.dp, Color(0xFFD97706))
                ) {
                    Row(
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text(text = "🏆", fontSize = 13.sp)
                        Spacer(modifier = Modifier.width(6.dp))
                        Text(
                            text = "Score: ${score * 30} pts",
                            color = Color(0xFFFDE68A),
                            style = MaterialTheme.typography.labelMedium.copy(
                                fontWeight = FontWeight.Bold,
                                fontSize = 12.sp
                            )
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun QuizQuestionCard(
    questionText: String,
    modifier: Modifier = Modifier
) {
    Card(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(22.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 8.dp)
    ) {
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .padding(22.dp),
            contentAlignment = Alignment.Center
        ) {
            // Delicate Islamic Rosette Watermarks in the corners
            IslamicGeometricRosette(
                modifier = Modifier
                    .size(80.dp)
                    .align(Alignment.TopStart)
                    .offset(x = (-24).dp, y = (-24).dp),
                strokeColor = Color(0xFF065F46).copy(alpha = 0.08f),
                strokeWidth = 1.dp
            )
            IslamicGeometricRosette(
                modifier = Modifier
                    .size(80.dp)
                    .align(Alignment.BottomEnd)
                    .offset(x = 24.dp, y = 24.dp),
                strokeColor = Color(0xFF065F46).copy(alpha = 0.08f),
                strokeWidth = 1.dp
            )

            Text(
                text = questionText,
                color = Color(0xFF0F172A),
                style = MaterialTheme.typography.titleMedium.copy(
                    fontWeight = FontWeight.Bold,
                    fontSize = 18.5.sp,
                    lineHeight = 26.sp,
                    textAlign = TextAlign.Center
                ),
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 8.dp, vertical = 10.dp)
            )
        }
    }
}

@Composable
fun QuizOptionsList(
    options: List<String>,
    correctAnswerIndex: Int,
    selectedOptionIndex: Int?,
    isConfirmed: Boolean,
    onSelectOption: (Int) -> Unit,
    modifier: Modifier = Modifier
) {
    Column(
        modifier = modifier.fillMaxWidth(),
        verticalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        options.forEachIndexed { index, option ->
            val letter = ('A'.code + index).toChar()
            val isSelected = selectedOptionIndex == index
            val isCorrect = index == correctAnswerIndex

            // Color scheme matching mockup: unselected is clean light pill, selected is emerald with checkmark
            val containerColor = when {
                !isConfirmed -> if (isSelected) Color(0xFF07452E) else Color.White
                isCorrect -> Color(0xFF07452E)
                isSelected && !isCorrect -> Color(0xFF7F1D1D)
                else -> Color(0xFFF8FAFC).copy(alpha = 0.8f)
            }

            val textColor = when {
                !isConfirmed -> if (isSelected) Color.White else Color(0xFF0F172A)
                isCorrect -> Color.White
                isSelected && !isCorrect -> Color.White
                else -> Color(0xFF64748B)
            }

            val borderStroke = when {
                !isConfirmed -> if (isSelected) BorderStroke(2.dp, Color(0xFF10B981)) else BorderStroke(1.dp, Color(0xFFE2E8F0))
                isCorrect -> BorderStroke(2.dp, Color(0xFF10B981))
                isSelected && !isCorrect -> BorderStroke(2.dp, Color(0xFFEF4444))
                else -> BorderStroke(1.dp, Color(0xFFE2E8F0))
            }

            Surface(
                onClick = {
                    if (!isConfirmed) {
                        onSelectOption(index)
                    }
                },
                shape = RoundedCornerShape(32.dp),
                color = containerColor,
                border = borderStroke,
                shadowElevation = if (isSelected || !isConfirmed) 2.dp else 0.dp,
                modifier = Modifier.fillMaxWidth()
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 20.dp, vertical = 15.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text(
                        text = "$letter)  $option",
                        color = textColor,
                        style = MaterialTheme.typography.bodyLarge.copy(
                            fontWeight = FontWeight.SemiBold,
                            fontSize = 15.5.sp
                        ),
                        modifier = Modifier.weight(1f)
                    )

                    // Checkmark or Cross badge
                    if (!isConfirmed && isSelected) {
                        // Green circular checkmark badge from mockup!
                        Box(
                            modifier = Modifier
                                .size(24.dp)
                                .clip(CircleShape)
                                .border(1.5.dp, Color(0xFF10B981), CircleShape),
                            contentAlignment = Alignment.Center
                        ) {
                            Icon(
                                imageVector = Icons.Default.Check,
                                contentDescription = "Selected",
                                tint = Color(0xFF10B981),
                                modifier = Modifier.size(15.dp)
                            )
                        }
                    } else if (isConfirmed) {
                        if (isCorrect) {
                            Box(
                                modifier = Modifier
                                    .size(24.dp)
                                    .clip(CircleShape)
                                    .background(Color(0xFF10B981)),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(
                                    imageVector = Icons.Default.Check,
                                    contentDescription = "Correct",
                                    tint = Color.White,
                                    modifier = Modifier.size(15.dp)
                                )
                            }
                        } else if (isSelected && !isCorrect) {
                            Box(
                                modifier = Modifier
                                    .size(24.dp)
                                    .clip(CircleShape)
                                    .background(Color(0xFFEF4444)),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(
                                    imageVector = Icons.Default.Close,
                                    contentDescription = "Incorrect",
                                    tint = Color.White,
                                    modifier = Modifier.size(15.dp)
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun QuizExplanationCard(
    explanation: String,
    reference: String?,
    modifier: Modifier = Modifier
) {
    Card(
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = Color(0xFF043523).copy(alpha = 0.95f)),
        border = BorderStroke(1.dp, Color(0xFF10B981).copy(alpha = 0.4f)),
        modifier = modifier.fillMaxWidth()
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text("💡", fontSize = 16.sp)
                Spacer(modifier = Modifier.width(6.dp))
                Text(
                    text = "Explanation",
                    color = Color(0xFF6EE7B7),
                    style = MaterialTheme.typography.titleSmall.copy(fontWeight = FontWeight.Bold)
                )
            }
            Spacer(modifier = Modifier.height(6.dp))
            Text(
                text = explanation,
                color = Color(0xFFE2E8F0),
                style = MaterialTheme.typography.bodyMedium.copy(fontSize = 14.sp, lineHeight = 20.sp)
            )
            if (!reference.isNullOrEmpty()) {
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    text = "Ref: $reference",
                    color = Color(0xFFFDE68A),
                    style = MaterialTheme.typography.labelSmall.copy(fontWeight = FontWeight.SemiBold)
                )
            }
        }
    }
}

@Composable
fun QuizOptionsAndActionButton(
    currentQuestion: QuizQuestion,
    selectedOptionIndex: Int?,
    isConfirmed: Boolean,
    isLastQuestion: Boolean,
    onSelectOption: (Int) -> Unit,
    onConfirmAnswer: () -> Unit,
    onNextQuestion: () -> Unit,
    modifier: Modifier = Modifier
) {
    Column(
        modifier = modifier.fillMaxWidth(),
        verticalArrangement = Arrangement.SpaceBetween
    ) {
        Column(
            modifier = Modifier
                .weight(1f)
                .verticalScroll(rememberScrollState()),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            QuizOptionsList(
                options = currentQuestion.options,
                correctAnswerIndex = currentQuestion.correctAnswerIndex,
                selectedOptionIndex = selectedOptionIndex,
                isConfirmed = isConfirmed,
                onSelectOption = onSelectOption
            )

            if (isConfirmed && !currentQuestion.explanation.isNullOrEmpty()) {
                QuizExplanationCard(
                    explanation = currentQuestion.explanation,
                    reference = currentQuestion.reference
                )
            }
        }

        Spacer(modifier = Modifier.height(12.dp))

        Button(
            onClick = {
                if (!isConfirmed) {
                    onConfirmAnswer()
                } else {
                    onNextQuestion()
                }
            },
            enabled = isConfirmed || selectedOptionIndex != null,
            shape = RoundedCornerShape(32.dp),
            colors = ButtonDefaults.buttonColors(
                containerColor = Color(0xFF10B981),
                contentColor = Color.White,
                disabledContainerColor = Color(0xFF10B981).copy(alpha = 0.35f),
                disabledContentColor = Color.White.copy(alpha = 0.6f)
            ),
            modifier = Modifier
                .fillMaxWidth()
                .height(56.dp)
        ) {
            Text(
                text = if (!isConfirmed) "Confirm Answer" else if (isLastQuestion) "View Results" else "Next Question",
                style = MaterialTheme.typography.titleMedium.copy(
                    fontWeight = FontWeight.Bold,
                    fontSize = 16.5.sp
                )
            )
        }
    }
}

/**
 * Authentic 8-pointed Islamic Star Rosette geometric pattern drawn via Canvas
 */
@Composable
fun IslamicGeometricRosette(
    modifier: Modifier = Modifier,
    strokeColor: Color = Color.White.copy(alpha = 0.12f),
    strokeWidth: androidx.compose.ui.unit.Dp = 1.dp
) {
    Canvas(modifier = modifier) {
        val cx = size.width / 2f
        val cy = size.height / 2f
        val radius = minOf(size.width, size.height) / 2f
        val sw = strokeWidth.toPx()

        // Outer concentric circle
        drawCircle(
            color = strokeColor,
            radius = radius * 0.95f,
            center = Offset(cx, cy),
            style = Stroke(width = sw)
        )
        // Mid concentric circle
        drawCircle(
            color = strokeColor,
            radius = radius * 0.70f,
            center = Offset(cx, cy),
            style = Stroke(width = sw)
        )
        // Inner concentric circle
        drawCircle(
            color = strokeColor,
            radius = radius * 0.38f,
            center = Offset(cx, cy),
            style = Stroke(width = sw)
        )

        // 8-point geometric star outer
        val points = 8
        val path = Path()
        val outerR = radius * 0.90f
        val innerR = radius * 0.55f
        for (i in 0 until (points * 2)) {
            val angle = (i * Math.PI / points).toFloat() - (Math.PI / 2).toFloat()
            val r = if (i % 2 == 0) outerR else innerR
            val x = cx + r * kotlin.math.cos(angle)
            val y = cy + r * kotlin.math.sin(angle)
            if (i == 0) path.moveTo(x, y) else path.lineTo(x, y)
        }
        path.close()
        drawPath(
            path = path,
            color = strokeColor,
            style = Stroke(width = sw)
        )

        // 8 radiating lines
        for (i in 0 until 8) {
            val angle = (i * Math.PI / 4).toFloat()
            val x2 = cx + radius * kotlin.math.cos(angle)
            val y2 = cy + radius * kotlin.math.sin(angle)
            drawLine(
                color = strokeColor,
                start = Offset(cx, cy),
                end = Offset(x2, y2),
                strokeWidth = sw
            )
        }

        // Inner 8-point star rotated
        val innerPath = Path()
        val inOuterR = radius * 0.48f
        val inInnerR = radius * 0.26f
        for (i in 0 until (points * 2)) {
            val angle = (i * Math.PI / points).toFloat() - (Math.PI / 2).toFloat() + (Math.PI / points).toFloat()
            val r = if (i % 2 == 0) inOuterR else inInnerR
            val x = cx + r * kotlin.math.cos(angle)
            val y = cy + r * kotlin.math.sin(angle)
            if (i == 0) innerPath.moveTo(x, y) else innerPath.lineTo(x, y)
        }
        innerPath.close()
        drawPath(
            path = innerPath,
            color = strokeColor,
            style = Stroke(width = sw)
        )
    }
}


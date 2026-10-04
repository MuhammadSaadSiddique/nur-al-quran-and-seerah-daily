package com.asloobulhayat.eternalecho.ui.quiz

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.ArrowForward
import androidx.compose.material3.*
import androidx.compose.runtime.*
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
                val rawQuestions = if (quizType == "GRAND_QURAN" || quizType == "GRAND_SEERAH") {
                    val grandType = if (quizType == "GRAND_QURAN") "QURAN" else "SEERAH"
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

    fun resetQuiz() {
        currentQuestionIndex = 0
        selectedOptionIndex = null
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
                .padding(paddingValues)
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
                        2 -> {
                            // Grand Quiz Tab (Exact match to web Grand Quiz section)
                            LazyColumn(
                                verticalArrangement = Arrangement.spacedBy(16.dp),
                                modifier = Modifier.fillMaxSize()
                            ) {
                                // Grand Quran Quiz Card
                                item {
                                    Card(
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
                                            Row(
                                                modifier = Modifier.fillMaxWidth(),
                                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                                            ) {
                                                listOf("Easy", "Medium", "Hard").forEach { diff ->
                                                    Button(
                                                        onClick = {
                                                            checkAndLaunch {
                                                                onGrandQuizLaunch("GRAND_QURAN", "Grand Quran Quiz", diff, selectedQuantity)
                                                            }
                                                        },
                                                        modifier = Modifier.weight(1f),
                                                        shape = RoundedCornerShape(10.dp)
                                                    ) {
                                                        Text(diff, fontWeight = FontWeight.Bold, fontSize = 13.sp)
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                // Grand Seerah Quiz Card
                                item {
                                    Card(
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
                                            Row(
                                                modifier = Modifier.fillMaxWidth(),
                                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                                            ) {
                                                listOf("Easy", "Medium", "Hard").forEach { diff ->
                                                    Button(
                                                        onClick = {
                                                            checkAndLaunch {
                                                                onGrandQuizLaunch("GRAND_SEERAH", "Grand Seerah Quiz", diff, selectedQuantity)
                                                            }
                                                        },
                                                        modifier = Modifier.weight(1f),
                                                        shape = RoundedCornerShape(10.dp)
                                                    ) {
                                                        Text(diff, fontWeight = FontWeight.Bold, fontSize = 13.sp)
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

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(themeName, fontWeight = FontWeight.Bold) },
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
        Box(
            modifier = modifier
                .fillMaxSize()
                .padding(paddingValues)
                //.padding(16.dp)
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

                        Card(
                            modifier = Modifier.align(Alignment.Center).fillMaxWidth().widthIn(max = 500.dp),
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
                                val percentage = if (questions.isNotEmpty()) (viewModel.score * 100) / questions.size else 0
                                Text(
                                    text = when {
                                        percentage >= 80 -> "🌟 Outstanding performance!"
                                        percentage >= 50 -> "👍 Good effort, keep learning!"
                                        else -> "📖 Review the themes and try again!"
                                    },
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )

                                Spacer(modifier = Modifier.height(20.dp))

                                if (isLoggedIn) {
                                    if (viewModel.isSubmittingScore) {
                                        Row(
                                            verticalAlignment = Alignment.CenterVertically,
                                            horizontalArrangement = Arrangement.Center,
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .padding(vertical = 8.dp)
                                        ) {
                                            CircularProgressIndicator(modifier = Modifier.size(20.dp), strokeWidth = 2.dp)
                                            Spacer(modifier = Modifier.width(10.dp))
                                            Text(
                                                text = "Saving score to your account...",
                                                style = MaterialTheme.typography.bodyMedium
                                            )
                                        }
                                    } else if (viewModel.isScoreSubmitted) {
                                        Card(
                                            colors = CardDefaults.cardColors(
                                                containerColor = MaterialTheme.colorScheme.primaryContainer,
                                                contentColor = MaterialTheme.colorScheme.onPrimaryContainer
                                            ),
                                            shape = RoundedCornerShape(12.dp),
                                            modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)
                                        ) {
                                            Row(
                                                modifier = Modifier.padding(12.dp),
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Text("✓", fontWeight = FontWeight.Black, fontSize = 20.sp, color = MaterialTheme.colorScheme.primary)
                                                Spacer(modifier = Modifier.width(10.dp))
                                                Column {
                                                    Text(
                                                        text = "Score Saved to Account!",
                                                        style = MaterialTheme.typography.titleSmall,
                                                        fontWeight = FontWeight.Bold
                                                    )
                                                    if (userName.isNotEmpty()) {
                                                        Text(
                                                            text = "Recorded for $userName",
                                                            style = MaterialTheme.typography.bodySmall
                                                        )
                                                    }
                                                }
                                            }
                                        }
                                    } else if (viewModel.submissionError != null) {
                                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                            Text(
                                                text = viewModel.submissionError ?: "Failed to save score",
                                                color = MaterialTheme.colorScheme.error,
                                                style = MaterialTheme.typography.bodySmall
                                            )
                                            TextButton(onClick = {
                                                viewModel.finishAndSubmit(userId, themeName, questions)
                                            }) {
                                                Text("Retry Saving Score")
                                            }
                                        }
                                    }
                                } else {
                                    Card(
                                        colors = CardDefaults.cardColors(
                                            containerColor = MaterialTheme.colorScheme.tertiaryContainer,
                                            contentColor = MaterialTheme.colorScheme.onTertiaryContainer
                                        ),
                                        shape = RoundedCornerShape(16.dp),
                                        modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)
                                    ) {
                                        Column(
                                            modifier = Modifier.padding(16.dp),
                                            horizontalAlignment = Alignment.CenterHorizontally
                                        ) {
                                            Text(
                                                text = "Save Score to Leaderboard",
                                                style = MaterialTheme.typography.titleMedium,
                                                fontWeight = FontWeight.Bold,
                                                textAlign = TextAlign.Center
                                            )
                                            Spacer(modifier = Modifier.height(6.dp))
                                            Text(
                                                text = "Create an account or sign in to save your score of ${viewModel.score}/${questions.size} to your profile and unlock unlimited quizzes!",
                                                style = MaterialTheme.typography.bodySmall,
                                                textAlign = TextAlign.Center
                                            )
                                            Spacer(modifier = Modifier.height(12.dp))
                                            Button(
                                                onClick = onAuthClick,
                                                modifier = Modifier.fillMaxWidth(),
                                                colors = ButtonDefaults.buttonColors(
                                                    containerColor = MaterialTheme.colorScheme.primary,
                                                    contentColor = MaterialTheme.colorScheme.onPrimary
                                                ),
                                                shape = RoundedCornerShape(10.dp)
                                            ) {
                                                Text("Sign Up to Save Score", fontWeight = FontWeight.Bold)
                                            }
                                        }
                                    }
                                }

                                Spacer(modifier = Modifier.height(20.dp))

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
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    Text("Play Again")
                                }
                                Spacer(modifier = Modifier.height(8.dp))
                                OutlinedButton(
                                    onClick = onBackClick,
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    Text(if (quizType.startsWith("GRAND")) "Back to Quizzes" else "Back to Themes")
                                }
                            }
                        }
                    } else {
                        val currentQuestion = questions[viewModel.currentQuestionIndex]
                        val hasAnswered = viewModel.selectedOptionIndex != null

                        if (adaptiveInfo.isTableTop) {
                            // Flip phone TableTop (Flex) mode
                            AdaptiveTwoPane(
                                adaptiveInfo = adaptiveInfo,
                                firstPane = {
                                    Column(
                                        modifier = Modifier
                                            .fillMaxSize()
                                            .padding(12.dp)
                                    ) {
                                        LinearProgressIndicator(
                                            progress = { (viewModel.currentQuestionIndex + 1).toFloat() / questions.size.toFloat() },
                                            modifier = Modifier.fillMaxWidth().padding(bottom = 8.dp)
                                        )
                                        Row(
                                            modifier = Modifier.fillMaxWidth().padding(bottom = 8.dp),
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
                                            fontWeight = FontWeight.Bold
                                        )
                                    }
                                },
                                secondPane = {
                                    Box(
                                        modifier = Modifier
                                            .fillMaxSize()
                                            .padding(12.dp)
                                    ) {
                                        QuizOptionsSection(
                                            currentQuestion = currentQuestion,
                                            hasAnswered = hasAnswered,
                                            selectedOptionIndex = viewModel.selectedOptionIndex,
                                            onSelectOption = { index ->
                                                viewModel.submitAnswer(index, currentQuestion.correctAnswerIndex)
                                            },
                                            onNextQuestion = { viewModel.nextQuestion(questions.size) },
                                            isLastQuestion = viewModel.currentQuestionIndex + 1 == questions.size
                                        )
                                    }
                                }
                            )
                        } else {
                            // Standard layout with ergonomic wide-screen centering
                            Box(
                                modifier = Modifier.fillMaxSize(),
                                contentAlignment = Alignment.TopCenter
                            ) {
                                Column(
                                    modifier = Modifier
                                        .fillMaxSize()
                                        .widthIn(max = 760.dp)
                                ) {
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
                                        modifier = Modifier.padding(bottom = 20.dp)
                                    )

                                    QuizOptionsSection(
                                        currentQuestion = currentQuestion,
                                        hasAnswered = hasAnswered,
                                        selectedOptionIndex = viewModel.selectedOptionIndex,
                                        onSelectOption = { index ->
                                            viewModel.submitAnswer(index, currentQuestion.correctAnswerIndex)
                                        },
                                        onNextQuestion = { viewModel.nextQuestion(questions.size) },
                                        isLastQuestion = viewModel.currentQuestionIndex + 1 == questions.size,
                                        modifier = Modifier.weight(1f)
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

@Composable
fun QuizOptionsSection(
    currentQuestion: QuizQuestion,
    hasAnswered: Boolean,
    selectedOptionIndex: Int?,
    onSelectOption: (Int) -> Unit,
    onNextQuestion: () -> Unit,
    isLastQuestion: Boolean,
    modifier: Modifier = Modifier
) {
    Column(modifier = modifier.fillMaxWidth()) {
        LazyColumn(
            modifier = Modifier.weight(1f),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            items(currentQuestion.options.size) { index ->
                val option = currentQuestion.options[index]
                val isCorrect = index == currentQuestion.correctAnswerIndex
                val isSelected = index == selectedOptionIndex

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
                    onClick = { onSelectOption(index) },
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
                onClick = onNextQuestion,
                modifier = Modifier.fillMaxWidth().padding(top = 16.dp)
            ) {
                Row(
                    horizontalArrangement = Arrangement.Center,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(if (isLastQuestion) "Finish" else "Next")
                    Spacer(modifier = Modifier.width(8.dp))
                    Icon(Icons.AutoMirrored.Filled.ArrowForward, contentDescription = "Next")
                }
            }
        }
    }
}


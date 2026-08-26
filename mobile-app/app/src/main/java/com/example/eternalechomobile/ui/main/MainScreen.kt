package com.example.eternalechomobile.ui.main

import androidx.compose.foundation.clickable
import androidx.compose.foundation.BorderStroke
import androidx.compose.ui.platform.LocalContext
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.List
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.ExitToApp
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation3.runtime.NavKey
import com.example.eternalechomobile.ThemeQuizSelection
import com.example.eternalechomobile.AuthRoute
import com.example.eternalechomobile.data.*
import androidx.lifecycle.LifecycleEventObserver
import androidx.lifecycle.Lifecycle
import kotlinx.coroutines.launch
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation

@Composable
fun MainScreen(
    onItemClick: (NavKey) -> Unit,
    modifier: Modifier = Modifier,
    viewModel: MainScreenViewModel = viewModel { MainScreenViewModel(DefaultDataRepository()) }
) {
    var selectedTab by remember { mutableStateOf(0) }

    val context = LocalContext.current
    val prefs = remember { context.getSharedPreferences("app_prefs", android.content.Context.MODE_PRIVATE) }

    var loginStateRefresh by remember { mutableStateOf(0) }
    val lifecycleOwner = androidx.lifecycle.compose.LocalLifecycleOwner.current
    DisposableEffect(lifecycleOwner) {
        val observer = LifecycleEventObserver { _, event ->
            if (event == Lifecycle.Event.ON_RESUME) {
                loginStateRefresh++
            }
        }
        lifecycleOwner.lifecycle.addObserver(observer)
        onDispose {
            lifecycleOwner.lifecycle.removeObserver(observer)
        }
    }

    val userId = remember(loginStateRefresh) { prefs.getInt("user_id", -1) }
    val userName = remember(loginStateRefresh) { prefs.getString("user_name", "") ?: "" }
    val isLoggedIn = userId != -1

    val repository = remember { DefaultDataRepository() }
    val scope = rememberCoroutineScope()
    
    var showPasswordDialog by remember { mutableStateOf(false) }
    var passwordText by remember { mutableStateOf("") }
    var passwordSaving by remember { mutableStateOf(false) }
    var passwordMessage by remember { mutableStateOf("") }

    if (showPasswordDialog) {
        AlertDialog(
            onDismissRequest = { 
                if (!passwordSaving) {
                    showPasswordDialog = false 
                    passwordText = ""
                    passwordMessage = ""
                }
            },
            title = { Text("Change Password", fontWeight = FontWeight.Bold) },
            text = {
                Column(
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text(
                        text = "Enter your new password (minimum 6 characters):",
                        style = MaterialTheme.typography.bodyMedium
                    )
                    OutlinedTextField(
                        value = passwordText,
                        onValueChange = { passwordText = it },
                        label = { Text("New Password") },
                        visualTransformation = PasswordVisualTransformation(),
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp),
                        enabled = !passwordSaving
                    )
                    if (passwordMessage.isNotEmpty()) {
                        Text(
                            text = passwordMessage,
                            color = if (passwordMessage.startsWith("Success")) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.error,
                            style = MaterialTheme.typography.bodySmall
                        )
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        if (passwordText.length < 6) {
                            passwordMessage = "Password must be at least 6 characters."
                            return@Button
                        }
                        passwordSaving = true
                        passwordMessage = ""
                        scope.launch {
                            try {
                                val success = repository.changePassword(userId, passwordText)
                                if (success) {
                                    passwordMessage = "Success! Password updated."
                                    kotlinx.coroutines.delay(1000)
                                    showPasswordDialog = false
                                    passwordText = ""
                                    passwordMessage = ""
                                } else {
                                    passwordMessage = "Failed to update password."
                                }
                            } catch (t: Throwable) {
                                passwordMessage = t.message ?: "Failed to update password."
                            } finally {
                                passwordSaving = false
                            }
                        }
                    },
                    enabled = !passwordSaving && passwordText.isNotEmpty()
                ) {
                    if (passwordSaving) {
                        CircularProgressIndicator(
                            color = MaterialTheme.colorScheme.onPrimary,
                            modifier = Modifier.size(16.dp)
                        )
                    } else {
                        Text("Update")
                    }
                }
            },
            dismissButton = {
                TextButton(
                    onClick = { 
                        showPasswordDialog = false 
                        passwordText = ""
                        passwordMessage = ""
                    },
                    enabled = !passwordSaving
                ) {
                    Text("Cancel")
                }
            }
        )
    }

    Scaffold(
        topBar = {
            @OptIn(ExperimentalMaterial3Api::class)
            TopAppBar(
                title = { Text("Eternal Echo", fontWeight = FontWeight.Bold) },
                actions = {
                    if (isLoggedIn) {
                        TextButton(
                            onClick = { showPasswordDialog = true },
                            modifier = Modifier.padding(end = 8.dp)
                        ) {
                            Text(
                                text = userName,
                                style = MaterialTheme.typography.bodyMedium,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onPrimaryContainer
                            )
                        }
                        IconButton(onClick = {
                            prefs.edit().remove("user_id").remove("user_name").remove("user_email").apply()
                            loginStateRefresh++
                        }) {
                            Icon(Icons.Default.ExitToApp, contentDescription = "Log Out")
                        }
                    } else {
                        Button(
                            onClick = { onItemClick(AuthRoute) },
                            shape = RoundedCornerShape(8.dp)
                        ) {
                            Text("Sign In", fontSize = 12.sp)
                        }
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primaryContainer,
                    titleContentColor = MaterialTheme.colorScheme.onPrimaryContainer
                )
            )
        },
        bottomBar = {
            NavigationBar(
                containerColor = MaterialTheme.colorScheme.primaryContainer
            ) {
                NavigationBarItem(
                    selected = selectedTab == 0,
                    onClick = { selectedTab = 0 },
                    icon = { Icon(Icons.Default.List, contentDescription = "Surahs") },
                    label = { Text("Surahs") }
                )
                NavigationBarItem(
                    selected = selectedTab == 1,
                    onClick = { selectedTab = 1 },
                    icon = { Icon(Icons.Default.Star, contentDescription = "Leaderboard") },
                    label = { Text("Leaderboard") }
                )
                NavigationBarItem(
                    selected = selectedTab == 2,
                    onClick = { selectedTab = 2 },
                    icon = { Icon(Icons.Default.Person, contentDescription = "Seerah") },
                    label = { Text("Seerah") }
                )
                NavigationBarItem(
                    selected = selectedTab == 3,
                    onClick = { selectedTab = 3 },
                    icon = { Icon(Icons.Default.Info, contentDescription = "History") },
                    label = { Text("History") }
                )
            }
        }
    ) { paddingValues ->
        Box(
            modifier = modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            when (selectedTab) {
                0 -> SurahsTab(
                    viewModel = viewModel,
                    onSurahClick = { onItemClick(SurahNavKey(it.number)) },
                    onThemeQuizClick = { onItemClick(ThemeQuizSelection) }
                )
                1 -> LeaderboardTab(viewModel = viewModel)
                2 -> SeerahTab(viewModel = viewModel, onAuthClick = { onItemClick(AuthRoute) })
                3 -> HistoryTab(viewModel = viewModel, onAuthClick = { onItemClick(AuthRoute) })
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SurahsTab(
    viewModel: MainScreenViewModel,
    onSurahClick: (Surah) -> Unit,
    onThemeQuizClick: () -> Unit
) {
    val state by viewModel.surahsState.collectAsStateWithLifecycle()
    var searchQuery by remember { mutableStateOf("") }

    Column(modifier = Modifier.fillMaxSize()) {
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 16.dp),
            colors = CardDefaults.cardColors(
                containerColor = MaterialTheme.colorScheme.secondaryContainer,
                contentColor = MaterialTheme.colorScheme.onSecondaryContainer
            ),
            shape = RoundedCornerShape(16.dp)
        ) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = "Thematic Quizzes",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold
                    )
                    Spacer(modifier = Modifier.height(2.dp))
                    Text(
                        text = "Practice quizzes by Quranic and Seerah themes.",
                        style = MaterialTheme.typography.bodySmall
                    )
                }
                Button(
                    onClick = onThemeQuizClick,
                    shape = RoundedCornerShape(8.dp)
                ) {
                    Text("Explore", fontSize = 12.sp)
                }
            }
        }

        OutlinedTextField(
            value = searchQuery,
            onValueChange = { searchQuery = it },
            placeholder = { Text("Search Surah...") },
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 16.dp),
            leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search") },
            shape = RoundedCornerShape(12.dp)
        )

        when (val uiState = state) {
            is SurahsUiState.Loading -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            }
            is SurahsUiState.Error -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("Error loading Surahs", color = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(onClick = { viewModel.loadSurahs() }) {
                            Text("Retry")
                        }
                    }
                }
            }
            is SurahsUiState.Success -> {
                val filteredSurahs = uiState.data.filter {
                    it.nameSimple.contains(searchQuery, ignoreCase = true) ||
                            it.nameTranslated.contains(searchQuery, ignoreCase = true) ||
                            it.number.toString() == searchQuery
                }

                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    items(filteredSurahs) { surah ->
                        Card(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable { onSurahClick(surah) },
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(
                                containerColor = MaterialTheme.colorScheme.surfaceVariant
                            )
                        ) {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(16.dp),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Box(
                                        modifier = Modifier
                                            .size(40.dp),
                                        contentAlignment = Alignment.Center
                                    ) {
                                        Badge(containerColor = MaterialTheme.colorScheme.primary) {
                                            Text(
                                                text = surah.number.toString(),
                                                color = MaterialTheme.colorScheme.onPrimary,
                                                fontWeight = FontWeight.Bold
                                            )
                                        }
                                    }
                                    Spacer(modifier = Modifier.width(12.dp))
                                    Column {
                                        Text(
                                            text = surah.nameSimple,
                                            style = MaterialTheme.typography.bodyLarge,
                                            fontWeight = FontWeight.Bold
                                        )
                                        Text(
                                            text = surah.nameTranslated,
                                            style = MaterialTheme.typography.bodySmall,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }
                                }
                                Column(horizontalAlignment = Alignment.End) {
                                    Text(
                                        text = surah.nameArabic,
                                        fontSize = 20.sp,
                                        fontWeight = FontWeight.Bold,
                                        color = MaterialTheme.colorScheme.primary
                                    )
                                    Text(
                                        text = "${surah.versesCount} Verses",
                                        style = MaterialTheme.typography.labelSmall,
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

@Composable
fun LeaderboardTab(viewModel: MainScreenViewModel) {
    val state by viewModel.leaderboardState.collectAsStateWithLifecycle()

    Column(modifier = Modifier.fillMaxSize()) {
        Text(
            text = "Leaderboard Challenges",
            style = MaterialTheme.typography.headlineMedium,
            fontWeight = FontWeight.Bold,
            modifier = Modifier.padding(bottom = 16.dp)
        )

        when (val uiState = state) {
            is LeaderboardUiState.Loading -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            }
            is LeaderboardUiState.Error -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("Error loading Leaderboard", color = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(onClick = { viewModel.loadLeaderboard() }) {
                            Text("Retry")
                        }
                    }
                }
            }
            is LeaderboardUiState.Success -> {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    items(uiState.data.withIndex().toList()) { (index, user) ->
                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(
                                containerColor = if (index < 3) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surfaceVariant
                            )
                        ) {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(16.dp),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Text(
                                        text = "#${index + 1}",
                                        style = MaterialTheme.typography.titleMedium,
                                        fontWeight = FontWeight.Bold,
                                        modifier = Modifier.width(36.dp),
                                        color = if (index < 3) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                    Column {
                                        Text(
                                            text = user.displayName.ifEmpty { user.name },
                                            style = MaterialTheme.typography.bodyLarge,
                                            fontWeight = FontWeight.Bold
                                        )
                                        Text(
                                            text = "Seerah read: ${user.seerahReadCount}",
                                            style = MaterialTheme.typography.labelSmall,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }
                                }
                                Column(horizontalAlignment = Alignment.End) {
                                    Text(
                                        text = "${user.totalScore} pts",
                                        style = MaterialTheme.typography.bodyLarge,
                                        fontWeight = FontWeight.Bold,
                                        color = MaterialTheme.colorScheme.primary
                                    )
                                    Text(
                                        text = "${user.totalQuestions} Questions",
                                        style = MaterialTheme.typography.labelSmall,
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

@Composable
fun SeerahItem(event: SeerahEvent, onAuthClick: () -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(
                text = event.title,
                style = MaterialTheme.typography.bodyLarge,
                fontWeight = FontWeight.Bold
            )

            if (event.category.isNotEmpty()) {
                Spacer(modifier = Modifier.height(8.dp))
                Badge(containerColor = MaterialTheme.colorScheme.secondaryContainer) {
                    Text(
                        text = event.category,
                        modifier = Modifier.padding(horizontal = 4.dp, vertical = 1.dp),
                        style = MaterialTheme.typography.labelSmall
                    )
                }
            }
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = event.description,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )

            if (event.questionText.isNotEmpty() && event.options.isNotEmpty()) {
                Spacer(modifier = Modifier.height(16.dp))
                HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
                Spacer(modifier = Modifier.height(12.dp))

                val context = LocalContext.current
                val prefs = remember { context.getSharedPreferences("app_prefs", android.content.Context.MODE_PRIVATE) }
                var completedQuizzes by remember { mutableIntStateOf(prefs.getInt("completed_quizzes", 0)) }
                var showLimitDialog by remember { mutableStateOf(false) }
                val scope = rememberCoroutineScope()

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

                var selectedOptionIndex by remember { mutableStateOf<Int?>(null) }
                val hasAnswered = selectedOptionIndex != null

                Text(
                    text = "Knowledge Check",
                    style = MaterialTheme.typography.labelSmall,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.padding(bottom = 4.dp)
                )

                Text(
                    text = event.questionText,
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.padding(bottom = 12.dp)
                )

                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    for (index in event.options.indices) {
                        val option = event.options[index]
                        val isCorrectOption = index == event.correctAnswerIndex
                        val isSelectedOption = index == selectedOptionIndex

                        val containerColor = when {
                            !hasAnswered -> MaterialTheme.colorScheme.surface
                            isCorrectOption -> MaterialTheme.colorScheme.primaryContainer
                            isSelectedOption -> MaterialTheme.colorScheme.errorContainer
                            else -> MaterialTheme.colorScheme.surface
                        }

                        val contentColor = when {
                            !hasAnswered -> MaterialTheme.colorScheme.onSurface
                            isCorrectOption -> MaterialTheme.colorScheme.onPrimaryContainer
                            isSelectedOption -> MaterialTheme.colorScheme.onErrorContainer
                            else -> MaterialTheme.colorScheme.onSurface
                        }

                        val borderColor = when {
                            !hasAnswered -> MaterialTheme.colorScheme.outline
                            isCorrectOption -> MaterialTheme.colorScheme.primary
                            isSelectedOption -> MaterialTheme.colorScheme.error
                            else -> MaterialTheme.colorScheme.outlineVariant
                        }

                        OutlinedButton(
                            onClick = {
                                if (!hasAnswered) {
                                    val userId = prefs.getInt("user_id", -1)
                                    val currentIsLoggedIn = userId != -1
                                    if (!currentIsLoggedIn && completedQuizzes >= 1) {
                                        showLimitDialog = true
                                    } else {
                                        selectedOptionIndex = index
                                        if (!currentIsLoggedIn) {
                                            prefs.edit().putInt("completed_quizzes", completedQuizzes + 1).apply()
                                            completedQuizzes++
                                        } else {
                                            val isCorrect = index == event.correctAnswerIndex
                                            val scoreValue = if (isCorrect) 1 else 0
                                            scope.launch {
                                                try {
                                                    val questionsArray = org.json.JSONArray()
                                                    val qObj = org.json.JSONObject()
                                                    qObj.put("id", event.id)
                                                    qObj.put("text", event.questionText)
                                                    qObj.put("correctAnswerIndex", event.correctAnswerIndex)
                                                    val opts = org.json.JSONArray()
                                                    event.options.forEach { opts.put(it) }
                                                    qObj.put("options", opts)
                                                    questionsArray.put(qObj)

                                                    val answersArray = org.json.JSONArray()
                                                    answersArray.put(index)

                                                    DefaultDataRepository().submitQuiz(
                                                        userId = userId,
                                                        type = "SEERAH",
                                                        title = event.title,
                                                        score = scoreValue,
                                                        totalQuestions = 1,
                                                        difficulty = "Medium",
                                                        questionsJson = questionsArray.toString(),
                                                        userAnswersJson = answersArray.toString()
                                                    )
                                                } catch (e: Exception) {
                                                    android.util.Log.e("SeerahItem", "Failed to submit quiz score", e)
                                                }
                                            }
                                        }
                                    }
                                }
                            },
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(8.dp),
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
                                    style = MaterialTheme.typography.bodyMedium,
                                    fontWeight = if (isSelectedOption) FontWeight.Bold else FontWeight.Normal
                                )
                                if (hasAnswered) {
                                    if (isCorrectOption) {
                                        Text("✓", fontWeight = FontWeight.Bold)
                                    } else if (isSelectedOption) {
                                        Text("✗", fontWeight = FontWeight.Bold)
                                    }
                                }
                            }
                        }
                    }
                }

                if (hasAnswered && event.explanation.isNotEmpty()) {
                    Spacer(modifier = Modifier.height(12.dp))
                    Surface(
                        color = MaterialTheme.colorScheme.tertiaryContainer,
                        contentColor = MaterialTheme.colorScheme.onTertiaryContainer,
                        shape = RoundedCornerShape(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(modifier = Modifier.padding(12.dp)) {
                            Text(
                                text = "Deep Insight",
                                style = MaterialTheme.typography.labelSmall,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.tertiary
                            )
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = event.explanation,
                                style = MaterialTheme.typography.bodySmall
                            )
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun HistoryItem(event: HistoryEvent, onAuthClick: () -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Text(
                text = event.title,
                style = MaterialTheme.typography.bodyLarge,
                fontWeight = FontWeight.Bold
            )

            if (event.category.isNotEmpty()) {
                Spacer(modifier = Modifier.height(8.dp))
                Badge(containerColor = MaterialTheme.colorScheme.secondaryContainer) {
                    Text(
                        text = event.category,
                        modifier = Modifier.padding(horizontal = 4.dp, vertical = 1.dp),
                        style = MaterialTheme.typography.labelSmall
                    )
                }
            }
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = event.description,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )

            if (event.questionText.isNotEmpty() && event.options.isNotEmpty()) {
                Spacer(modifier = Modifier.height(16.dp))
                HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
                Spacer(modifier = Modifier.height(12.dp))

                val context = LocalContext.current
                val prefs = remember { context.getSharedPreferences("app_prefs", android.content.Context.MODE_PRIVATE) }
                var completedQuizzes by remember { mutableIntStateOf(prefs.getInt("completed_quizzes", 0)) }
                var showLimitDialog by remember { mutableStateOf(false) }
                val scope = rememberCoroutineScope()

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

                var selectedOptionIndex by remember { mutableStateOf<Int?>(null) }
                val hasAnswered = selectedOptionIndex != null

                Text(
                    text = "Knowledge Check",
                    style = MaterialTheme.typography.labelSmall,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.padding(bottom = 4.dp)
                )

                Text(
                    text = event.questionText,
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.padding(bottom = 12.dp)
                )

                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    for (index in event.options.indices) {
                        val option = event.options[index]
                        val isCorrectOption = index == event.correctAnswerIndex
                        val isSelectedOption = index == selectedOptionIndex

                        val containerColor = when {
                            !hasAnswered -> MaterialTheme.colorScheme.surface
                            isCorrectOption -> MaterialTheme.colorScheme.primaryContainer
                            isSelectedOption -> MaterialTheme.colorScheme.errorContainer
                            else -> MaterialTheme.colorScheme.surface
                        }

                        val contentColor = when {
                            !hasAnswered -> MaterialTheme.colorScheme.onSurface
                            isCorrectOption -> MaterialTheme.colorScheme.onPrimaryContainer
                            isSelectedOption -> MaterialTheme.colorScheme.onErrorContainer
                            else -> MaterialTheme.colorScheme.onSurface
                        }

                        val borderColor = when {
                            !hasAnswered -> MaterialTheme.colorScheme.outline
                            isCorrectOption -> MaterialTheme.colorScheme.primary
                            isSelectedOption -> MaterialTheme.colorScheme.error
                            else -> MaterialTheme.colorScheme.outlineVariant
                        }

                        OutlinedButton(
                            onClick = {
                                if (!hasAnswered) {
                                    val userId = prefs.getInt("user_id", -1)
                                    val currentIsLoggedIn = userId != -1
                                    if (!currentIsLoggedIn && completedQuizzes >= 1) {
                                        showLimitDialog = true
                                    } else {
                                        selectedOptionIndex = index
                                        if (!currentIsLoggedIn) {
                                            prefs.edit().putInt("completed_quizzes", completedQuizzes + 1).apply()
                                            completedQuizzes++
                                        } else {
                                            val isCorrect = index == event.correctAnswerIndex
                                            val scoreValue = if (isCorrect) 1 else 0
                                            scope.launch {
                                                try {
                                                    val questionsArray = org.json.JSONArray()
                                                    val qObj = org.json.JSONObject()
                                                    qObj.put("id", event.id)
                                                    qObj.put("text", event.questionText)
                                                    qObj.put("correctAnswerIndex", event.correctAnswerIndex)
                                                    val opts = org.json.JSONArray()
                                                    event.options.forEach { opts.put(it) }
                                                    qObj.put("options", opts)
                                                    questionsArray.put(qObj)

                                                    val answersArray = org.json.JSONArray()
                                                    answersArray.put(index)

                                                    DefaultDataRepository().submitQuiz(
                                                        userId = userId,
                                                        type = "HISTORY",
                                                        title = event.title,
                                                        score = scoreValue,
                                                        totalQuestions = 1,
                                                        difficulty = "Medium",
                                                        questionsJson = questionsArray.toString(),
                                                        userAnswersJson = answersArray.toString()
                                                    )
                                                } catch (e: Exception) {
                                                    android.util.Log.e("HistoryItem", "Failed to submit quiz score", e)
                                                }
                                            }
                                        }
                                    }
                                }
                            },
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(8.dp),
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
                                    style = MaterialTheme.typography.bodyMedium,
                                    fontWeight = if (isSelectedOption) FontWeight.Bold else FontWeight.Normal
                                )
                                if (hasAnswered) {
                                    if (isCorrectOption) {
                                        Text("✓", fontWeight = FontWeight.Bold)
                                    } else if (isSelectedOption) {
                                        Text("✗", fontWeight = FontWeight.Bold)
                                    }
                                }
                            }
                        }
                    }
                }

                if (hasAnswered && event.explanation.isNotEmpty()) {
                    Spacer(modifier = Modifier.height(12.dp))
                    Surface(
                        color = MaterialTheme.colorScheme.tertiaryContainer,
                        contentColor = MaterialTheme.colorScheme.onTertiaryContainer,
                        shape = RoundedCornerShape(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(modifier = Modifier.padding(12.dp)) {
                            Text(
                                text = "Deep Insight",
                                style = MaterialTheme.typography.labelSmall,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.tertiary
                            )
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = event.explanation,
                                style = MaterialTheme.typography.bodySmall
                            )
                        }
                    }
                }
            }
        }
    }
}
@Composable
fun SeerahTab(viewModel: MainScreenViewModel, onAuthClick: () -> Unit) {
    val state by viewModel.insightsState.collectAsStateWithLifecycle()

    Column(modifier = Modifier.fillMaxSize()) {
        Text(
            text = "Daily Seerah & Historical Insights",
            style = MaterialTheme.typography.headlineMedium,
            fontWeight = FontWeight.Bold,
            modifier = Modifier.padding(bottom = 16.dp)
        )

        when (val uiState = state) {
            is InsightsUiState.Loading -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            }
            is InsightsUiState.Error -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("Error loading Seerah", color = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(onClick = { viewModel.loadInsights() }) {
                            Text("Retry")
                        }
                    }
                }
            }
            is InsightsUiState.Success -> {
                val categories = listOf("All") + uiState.data.seerahCategories

                LazyRow(
                    modifier = Modifier.fillMaxWidth().padding(bottom = 12.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    items(categories) { category ->
                        val isSelected = (category == "All" && viewModel.currentSeerahCategory == "") ||
                                         (category == viewModel.currentSeerahCategory)
                        Surface(
                            onClick = {
                                val filterVal = if (category == "All") "" else category
                                viewModel.filterSeerahByCategory(filterVal)
                            },
                            shape = RoundedCornerShape(16.dp),
                            color = if (isSelected) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surfaceVariant,
                            modifier = Modifier.padding(vertical = 4.dp)
                        ) {
                            Text(
                                text = category,
                                modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
                                style = MaterialTheme.typography.labelMedium,
                                fontWeight = FontWeight.Bold,
                                color = if (isSelected) MaterialTheme.colorScheme.onPrimaryContainer else MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                }

                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    if (uiState.data.seerahEvents.isEmpty()) {
                        item {
                            Box(
                                modifier = Modifier.fillMaxWidth().padding(32.dp),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(
                                    text = "No events found for this category.",
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                        }
                    } else {
                        items(uiState.data.seerahEvents) { event ->
                            SeerahItem(event, onAuthClick)
                        }
                    }

                    item {
                        Row(
                            modifier = Modifier.fillMaxWidth().padding(vertical = 8.dp),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Button(
                                onClick = { viewModel.prevPageSeerah() },
                                enabled = uiState.data.seerahPage > 1
                            ) {
                                Text("Previous")
                            }
                            Text(
                                text = "Page ${uiState.data.seerahPage} of ${uiState.data.seerahTotalPages}",
                                style = MaterialTheme.typography.bodyMedium,
                                fontWeight = FontWeight.Bold
                            )
                            Button(
                                onClick = { viewModel.nextPageSeerah() },
                                enabled = uiState.data.seerahPage < uiState.data.seerahTotalPages
                            ) {
                                Text("Next")
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun HistoryTab(viewModel: MainScreenViewModel, onAuthClick: () -> Unit) {
    val state by viewModel.insightsState.collectAsStateWithLifecycle()

    Column(modifier = Modifier.fillMaxSize().padding(16.dp)) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(bottom = 8.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                text = "General History",
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold
            )
        }

        when (val uiState = state) {
            is InsightsUiState.Loading -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            }
            is InsightsUiState.Error -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("Error loading History", color = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(onClick = { viewModel.loadInsights() }) {
                            Text("Retry")
                        }
                    }
                }
            }
            is InsightsUiState.Success -> {
                val categories = listOf("All") + uiState.data.historyCategories

                LazyRow(
                    modifier = Modifier.fillMaxWidth().padding(bottom = 12.dp),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    items(categories) { category ->
                        val isSelected = (category == "All" && viewModel.currentHistoryCategory == "") ||
                                         (category == viewModel.currentHistoryCategory)
                        Surface(
                            onClick = {
                                val filterVal = if (category == "All") "" else category
                                viewModel.filterHistoryByCategory(filterVal)
                            },
                            shape = RoundedCornerShape(16.dp),
                            color = if (isSelected) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surfaceVariant,
                            modifier = Modifier.padding(vertical = 4.dp)
                        ) {
                            Text(
                                text = category,
                                modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
                                style = MaterialTheme.typography.labelSmall,
                                fontWeight = FontWeight.Bold,
                                color = if (isSelected) MaterialTheme.colorScheme.onPrimaryContainer else MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                }

                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    if (uiState.data.historyEvents.isEmpty()) {
                        item {
                            Box(
                                modifier = Modifier.fillMaxWidth().padding(32.dp),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(
                                    text = "No events found for this category.",
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                        }
                    } else {
                        items(uiState.data.historyEvents) { event ->
                            HistoryItem(event, onAuthClick)
                        }
                    }

                    item {
                        Row(
                            modifier = Modifier.fillMaxWidth().padding(vertical = 8.dp),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Button(
                                onClick = { viewModel.prevPageHistory() },
                                enabled = uiState.data.historyPage > 1
                            ) {
                                Text("Previous")
                            }
                            Text(
                                text = "Page ${uiState.data.historyPage} of ${uiState.data.historyTotalPages}",
                                style = MaterialTheme.typography.bodyMedium,
                                fontWeight = FontWeight.Bold
                            )
                            Button(
                                onClick = { viewModel.nextPageHistory() },
                                enabled = uiState.data.historyPage < uiState.data.historyTotalPages
                            ) {
                                Text("Next")
                            }
                        }
                    }
                }
            }
        }
    }
}

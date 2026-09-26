package com.example.eternalechomobile.ui.duas

import androidx.compose.animation.*
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.example.eternalechomobile.data.DataRepository
import com.example.eternalechomobile.data.DefaultDataRepository
import com.example.eternalechomobile.data.Dua
import com.example.eternalechomobile.ui.adaptive.*
import androidx.compose.runtime.saveable.rememberSaveable
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DuasScreen(
    onBackClick: () -> Unit,
    modifier: Modifier = Modifier,
    repository: DataRepository = remember { DefaultDataRepository() }
) {
    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Sacred Duas", fontWeight = FontWeight.Bold) },
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
        DuasContent(
            repository = repository,
            modifier = modifier
                .fillMaxSize()
                .padding(paddingValues)
        )
    }
}

@Composable
fun DuasContent(
    repository: DataRepository = remember { DefaultDataRepository() },
    modifier: Modifier = Modifier,
    adaptiveInfo: WindowAdaptiveInfo = rememberWindowAdaptiveInfo()
) {
    val scope = rememberCoroutineScope()

    var searchQuery by remember { mutableStateOf("") }
    var selectedCategory by remember { mutableStateOf("all") }
    var selectedSourceType by remember { mutableStateOf("all") }
    var language by remember { mutableStateOf("en") }

    var isLoading by remember { mutableStateOf(true) }
    var duasList by remember { mutableStateOf<List<Dua>>(emptyList()) }
    var errorMessage by remember { mutableStateOf<String?>(null) }
    var selectedDuaId by rememberSaveable { mutableIntStateOf(-1) }

    val categories = listOf(
        "all" to "All Categories",
        "Comprehensive & Success" to "Comprehensive",
        "Forgiveness & Tawbah" to "Forgiveness",
        "Distress & Hardship" to "Distress & Relief",
        "Guidance & Knowledge" to "Knowledge",
        "Morning & Evening" to "Morning/Evening",
        "Protection & Health" to "Protection",
        "Parents & Family" to "Parents & Family",
        "Praise & Gratitude" to "Gratitude",
        "Daily Living" to "Daily Living"
    )

    fun loadDuas() {
        scope.launch {
            isLoading = true
            errorMessage = null
            try {
                duasList = repository.getDuas(
                    category = if (selectedCategory == "all") "" else selectedCategory,
                    search = searchQuery,
                    sourceType = if (selectedSourceType == "all") "" else selectedSourceType
                )
            } catch (t: Throwable) {
                errorMessage = t.message ?: "Failed to load Duas"
            } finally {
                isLoading = false
            }
        }
    }

    LaunchedEffect(selectedCategory, selectedSourceType) {
        loadDuas()
    }

    val selectedDua = remember(duasList, selectedDuaId) {
        if (duasList.isEmpty()) null
        else duasList.find { it.id == selectedDuaId } ?: duasList.first()
    }

    if (adaptiveInfo.isDualPane && duasList.isNotEmpty() && selectedDua != null) {
        AdaptiveTwoPane(
            adaptiveInfo = adaptiveInfo,
            firstPane = {
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(12.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    DuasFilterHeader(
                        searchQuery = searchQuery,
                        onSearchQueryChange = {
                            searchQuery = it
                            loadDuas()
                        },
                        selectedSourceType = selectedSourceType,
                        onSourceTypeChange = { selectedSourceType = it },
                        language = language,
                        onLanguageChange = { language = it },
                        categories = categories,
                        selectedCategory = selectedCategory,
                        onCategoryChange = { selectedCategory = it }
                    )

                    LazyColumn(
                        verticalArrangement = Arrangement.spacedBy(8.dp),
                        modifier = Modifier.fillMaxSize()
                    ) {
                        items(duasList, key = { it.id }) { dua ->
                            val isSelected = dua.id == selectedDua.id
                            Card(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .clickable { selectedDuaId = dua.id },
                                shape = RoundedCornerShape(12.dp),
                                colors = CardDefaults.cardColors(
                                    containerColor = if (isSelected) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surfaceVariant
                                ),
                                border = if (isSelected) BorderStroke(1.5.dp, MaterialTheme.colorScheme.primary) else null
                            ) {
                                Row(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(12.dp),
                                    horizontalArrangement = Arrangement.SpaceBetween,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Column(modifier = Modifier.weight(1f)) {
                                        Text(
                                            text = dua.title,
                                            style = MaterialTheme.typography.titleSmall,
                                            fontWeight = FontWeight.Bold,
                                            color = if (isSelected) MaterialTheme.colorScheme.onPrimaryContainer else MaterialTheme.colorScheme.onSurface
                                        )
                                        Spacer(modifier = Modifier.height(2.dp))
                                        Text(
                                            text = dua.category,
                                            style = MaterialTheme.typography.labelSmall,
                                            color = MaterialTheme.colorScheme.primary
                                        )
                                    }
                                    if (dua.sourceType.isNotEmpty()) {
                                        Surface(
                                            color = MaterialTheme.colorScheme.secondaryContainer,
                                            shape = RoundedCornerShape(6.dp)
                                        ) {
                                            Text(
                                                text = dua.sourceType.replaceFirstChar { it.uppercase() },
                                                style = MaterialTheme.typography.labelSmall,
                                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                                            )
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            },
            secondPane = {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(12.dp)
                ) {
                    LazyColumn(
                        modifier = Modifier.fillMaxSize(),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        item {
                            DuaCard(dua = selectedDua, language = language)
                        }
                    }
                }
            },
            modifier = modifier
        )
    } else {
        // Standard Single-Pane Layout for compact portrait screens
        Column(
            modifier = modifier
                .fillMaxSize()
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(14.dp)
        ) {
            DuasFilterHeader(
                searchQuery = searchQuery,
                onSearchQueryChange = {
                    searchQuery = it
                    loadDuas()
                },
                selectedSourceType = selectedSourceType,
                onSourceTypeChange = { selectedSourceType = it },
                language = language,
                onLanguageChange = { language = it },
                categories = categories,
                selectedCategory = selectedCategory,
                onCategoryChange = { selectedCategory = it }
            )

            // Duas Content List
            if (isLoading) {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .weight(1f),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator()
                }
            } else if (errorMessage != null) {
                Card(
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Text("Error loading Duas: $errorMessage", color = MaterialTheme.colorScheme.onErrorContainer)
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(onClick = { loadDuas() }) { Text("Retry") }
                    }
                }
            } else if (duasList.isEmpty()) {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .weight(1f),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = "No Duas found matching your filter.",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            } else {
                LazyColumn(
                    verticalArrangement = Arrangement.spacedBy(16.dp),
                    modifier = Modifier
                        .fillMaxWidth()
                        .weight(1f)
                ) {
                    items(duasList, key = { it.id }) { dua ->
                        DuaCard(dua = dua, language = language)
                    }
                }
            }
        }
    }
}

@Composable
fun DuasFilterHeader(
    searchQuery: String,
    onSearchQueryChange: (String) -> Unit,
    selectedSourceType: String,
    onSourceTypeChange: (String) -> Unit,
    language: String,
    onLanguageChange: (String) -> Unit,
    categories: List<Pair<String, String>>,
    selectedCategory: String,
    onCategoryChange: (String) -> Unit
) {
    Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
        // Search Bar
        OutlinedTextField(
            value = searchQuery,
            onValueChange = onSearchQueryChange,
            placeholder = { Text("Search by meaning, Arabic, or reference...", fontSize = 13.sp) },
            leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
            trailingIcon = {
                if (searchQuery.isNotEmpty()) {
                    IconButton(onClick = { onSearchQueryChange("") }) {
                        Icon(Icons.Default.Close, contentDescription = "Clear search")
                    }
                }
            },
            shape = RoundedCornerShape(16.dp),
            modifier = Modifier.fillMaxWidth(),
            singleLine = true
        )

        // Source Filters & Language Toggle
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Row(horizontalArrangement = Arrangement.spacedBy(4.dp)) {
                listOf("all" to "All", "quran" to "Quran", "hadith" to "Hadith").forEach { (type, label) ->
                    val isSelected = selectedSourceType == type
                    FilterChip(
                        selected = isSelected,
                        onClick = { onSourceTypeChange(type) },
                        label = { Text(label, fontSize = 11.sp, fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal) },
                        shape = RoundedCornerShape(12.dp)
                    )
                }
            }

            Row(
                modifier = Modifier
                    .clip(RoundedCornerShape(12.dp))
                    .background(MaterialTheme.colorScheme.surfaceVariant)
                    .padding(2.dp)
            ) {
                Box(
                    modifier = Modifier
                        .clip(RoundedCornerShape(10.dp))
                        .background(if (language == "en") MaterialTheme.colorScheme.primary else Color.Transparent)
                        .clickable { onLanguageChange("en") }
                        .padding(horizontal = 8.dp, vertical = 4.dp)
                ) {
                    Text(
                        text = "EN",
                        fontSize = 11.sp,
                        fontWeight = FontWeight.Bold,
                        color = if (language == "en") MaterialTheme.colorScheme.onPrimary else MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
                Box(
                    modifier = Modifier
                        .clip(RoundedCornerShape(10.dp))
                        .background(if (language == "ur") MaterialTheme.colorScheme.primary else Color.Transparent)
                        .clickable { onLanguageChange("ur") }
                        .padding(horizontal = 8.dp, vertical = 4.dp)
                ) {
                    Text(
                        text = "اردو",
                        fontSize = 11.sp,
                        fontWeight = FontWeight.Bold,
                        color = if (language == "ur") MaterialTheme.colorScheme.onPrimary else MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }
        }

        // Category Scrollable Row
        LazyRow(
            horizontalArrangement = Arrangement.spacedBy(8.dp),
            modifier = Modifier.fillMaxWidth()
        ) {
            items(categories) { (key, label) ->
                val isSelected = selectedCategory == key
                SuggestionChip(
                    onClick = { onCategoryChange(key) },
                    label = {
                        Text(
                            text = label,
                            fontSize = 12.sp,
                            fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal
                        )
                    },
                    shape = RoundedCornerShape(12.dp),
                    colors = SuggestionChipDefaults.suggestionChipColors(
                        containerColor = if (isSelected) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surface,
                        labelColor = if (isSelected) MaterialTheme.colorScheme.onPrimaryContainer else MaterialTheme.colorScheme.onSurface
                    ),
                    border = BorderStroke(
                        1.dp,
                        if (isSelected) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.outlineVariant
                    )
                )
            }
        }
    }
}


@Composable
fun DuaCard(
    dua: Dua,
    language: String
) {
    var isWordByWordExpanded by remember { mutableStateOf(false) }
    var isCommentaryExpanded by remember { mutableStateOf(false) }
    var tasbihCount by remember { mutableStateOf(0) }

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant)
    ) {
        Column(
            modifier = Modifier.padding(18.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            // Header: Category, Type & Grading
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                    Surface(
                        color = MaterialTheme.colorScheme.primaryContainer,
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Text(
                            text = dua.category,
                            style = MaterialTheme.typography.labelSmall,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onPrimaryContainer,
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                        )
                    }

                    if (dua.sourceType == "quran" || dua.sourceType == "both") {
                        Surface(
                            color = Color(0xFFE6F4EA),
                            shape = RoundedCornerShape(8.dp)
                        ) {
                            Text(
                                text = "Quran",
                                style = MaterialTheme.typography.labelSmall,
                                fontWeight = FontWeight.Bold,
                                color = Color(0xFF137333),
                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 4.dp)
                            )
                        }
                    }

                    if (dua.sourceType == "hadith" || dua.sourceType == "both") {
                        Surface(
                            color = Color(0xFFFEF7E0),
                            shape = RoundedCornerShape(8.dp)
                        ) {
                            Text(
                                text = "Hadith",
                                style = MaterialTheme.typography.labelSmall,
                                fontWeight = FontWeight.Bold,
                                color = Color(0xFFB06000),
                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 4.dp)
                            )
                        }
                    }
                }

                if (dua.hadithGrading != null) {
                    Text(
                        text = dua.hadithGrading,
                        style = MaterialTheme.typography.labelSmall,
                        color = MaterialTheme.colorScheme.outline,
                        fontWeight = FontWeight.SemiBold
                    )
                }
            }

            // Title & Description
            Text(
                text = dua.title,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface
            )

            if (!dua.description.isNullOrBlank()) {
                Text(
                    text = dua.description,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }

            // Arabic Text (Large & Legible)
            Surface(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(16.dp),
                color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Text(
                        text = dua.arabicText,
                        fontSize = 24.sp,
                        fontWeight = FontWeight.Bold,
                        textAlign = TextAlign.End,
                        lineHeight = 42.sp,
                        modifier = Modifier.fillMaxWidth(),
                        color = MaterialTheme.colorScheme.onSurface
                    )

                    if (!dua.transliteration.isNullOrBlank()) {
                        Spacer(modifier = Modifier.height(10.dp))
                        HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))
                        Spacer(modifier = Modifier.height(8.dp))
                        Text(
                            text = dua.transliteration,
                            style = MaterialTheme.typography.bodySmall,
                            fontStyle = FontStyle.Italic,
                            color = MaterialTheme.colorScheme.primary
                        )
                    }
                }
            }

            // Translation Box
            Surface(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(12.dp),
                color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.25f)
            ) {
                Column(modifier = Modifier.padding(12.dp)) {
                    Text(
                        text = if (language == "ur" && !dua.translationUr.isNullOrBlank()) {
                            dua.translationUr
                        } else {
                            dua.translationEn
                        },
                        style = MaterialTheme.typography.bodyMedium,
                        fontWeight = FontWeight.Medium,
                        textAlign = if (language == "ur") TextAlign.End else TextAlign.Start,
                        lineHeight = 22.sp,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                }
            }

            // Word-by-Word Toggle Button & Matrix
            if (!dua.wordByWord.isNullOrEmpty()) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = "Word-by-Word Breakdown",
                        style = MaterialTheme.typography.labelMedium,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.primary
                    )
                    TextButton(onClick = { isWordByWordExpanded = !isWordByWordExpanded }) {
                        Text(
                            text = if (isWordByWordExpanded) "Hide Words ▲" else "View Words ▼",
                            fontSize = 11.sp,
                            fontWeight = FontWeight.Bold
                        )
                    }
                }

                AnimatedVisibility(visible = isWordByWordExpanded) {
                    Column(
                        verticalArrangement = Arrangement.spacedBy(8.dp),
                        modifier = Modifier
                            .fillMaxWidth()
                            .clip(RoundedCornerShape(12.dp))
                            .background(MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.35f))
                            .padding(10.dp)
                    ) {
                        dua.wordByWord.chunked(3).forEach { chunk ->
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                chunk.forEach { word ->
                                    Card(
                                        modifier = Modifier.weight(1f),
                                        shape = RoundedCornerShape(10.dp),
                                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                                        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant)
                                    ) {
                                        Column(
                                            modifier = Modifier.padding(6.dp),
                                            horizontalAlignment = Alignment.CenterHorizontally
                                        ) {
                                            Text(
                                                text = word.arabic,
                                                fontSize = 16.sp,
                                                fontWeight = FontWeight.Bold,
                                                textAlign = TextAlign.Center
                                            )
                                            Text(
                                                text = word.transliteration,
                                                fontSize = 9.sp,
                                                color = MaterialTheme.colorScheme.outline,
                                                fontStyle = FontStyle.Italic
                                            )
                                            Spacer(modifier = Modifier.height(2.dp))
                                            Text(
                                                text = if (language == "ur" && word.meaningUr != null) word.meaningUr else word.meaningEn,
                                                fontSize = 10.sp,
                                                fontWeight = FontWeight.SemiBold,
                                                color = MaterialTheme.colorScheme.primary,
                                                textAlign = TextAlign.Center
                                            )
                                        }
                                    }
                                }
                                // Fill missing slots if row has fewer than 3 words
                                repeat(3 - chunk.size) {
                                    Spacer(modifier = Modifier.weight(1f))
                                }
                            }
                        }
                    }
                }
            }

            // Expandable Spiritual Commentary
            if (!dua.meaningExplanation.isNullOrBlank() || !dua.benefitsAndVirtues.isNullOrBlank()) {
                Surface(
                    shape = RoundedCornerShape(12.dp),
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f),
                    modifier = Modifier.clickable { isCommentaryExpanded = !isCommentaryExpanded }
                ) {
                    Column(modifier = Modifier.padding(12.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(
                                text = "💡 Deep Meaning & Virtues",
                                style = MaterialTheme.typography.labelMedium,
                                fontWeight = FontWeight.Bold
                            )
                            Icon(
                                imageVector = if (isCommentaryExpanded) Icons.Default.KeyboardArrowUp else Icons.Default.KeyboardArrowDown,
                                contentDescription = null,
                                modifier = Modifier.size(18.dp)
                            )
                        }

                        AnimatedVisibility(visible = isCommentaryExpanded) {
                            Column(
                                modifier = Modifier.padding(top = 8.dp),
                                verticalArrangement = Arrangement.spacedBy(6.dp)
                            ) {
                                if (!dua.meaningExplanation.isNullOrBlank()) {
                                    Text(
                                        text = dua.meaningExplanation,
                                        style = MaterialTheme.typography.bodySmall,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                }
                                if (!dua.benefitsAndVirtues.isNullOrBlank()) {
                                    Text(
                                        text = "Virtues: ${dua.benefitsAndVirtues}",
                                        style = MaterialTheme.typography.bodySmall,
                                        fontWeight = FontWeight.SemiBold,
                                        color = MaterialTheme.colorScheme.primary
                                    )
                                }
                            }
                        }
                    }
                }
            }

            // References & Sunnah Tasbih Counter Bar
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 4.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                // Citation
                Column {
                    if (!dua.quranReference.isNullOrBlank()) {
                        Text(
                            text = "📖 ${dua.quranReference}",
                            style = MaterialTheme.typography.labelSmall,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.primary
                        )
                    }
                    if (!dua.hadithReference.isNullOrBlank()) {
                        Text(
                            text = "📜 ${dua.hadithReference}",
                            style = MaterialTheme.typography.labelSmall,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFFB06000)
                        )
                    }
                }

                // Sunnah Tasbih Counter Button
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    FilledTonalButton(
                        onClick = {
                            if (tasbihCount < dua.repeatCount) {
                                tasbihCount++
                            }
                        },
                        shape = RoundedCornerShape(12.dp),
                        contentPadding = PaddingValues(horizontal = 10.dp, vertical = 6.dp)
                    ) {
                        Text(
                            text = "📿 $tasbihCount / ${dua.repeatCount}",
                            style = MaterialTheme.typography.labelMedium,
                            fontWeight = FontWeight.Bold
                        )
                    }

                    if (tasbihCount > 0) {
                        IconButton(
                            onClick = { tasbihCount = 0 },
                            modifier = Modifier.size(28.dp)
                        ) {
                            Icon(Icons.Default.Refresh, contentDescription = "Reset", modifier = Modifier.size(16.dp))
                        }
                    }
                }
            }
        }
    }
}

package com.asloobulhayat.eternalecho.ui.duas

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
import androidx.compose.ui.platform.LocalContext
import com.asloobulhayat.eternalecho.util.ShareHelper
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.asloobulhayat.eternalecho.data.DataRepository
import com.asloobulhayat.eternalecho.data.DefaultDataRepository
import com.asloobulhayat.eternalecho.data.Dua
import com.asloobulhayat.eternalecho.ui.adaptive.*
import androidx.compose.runtime.saveable.rememberSaveable
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DuasScreen(
    onBackClick: () -> Unit,
    onDuaClick: (Dua) -> Unit = {},
    modifier: Modifier = Modifier,
    repository: DataRepository = remember { DefaultDataRepository() }
) {
    var showContributeSheet by remember { mutableStateOf(false) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Sacred Duas", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onBackClick) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(onClick = { showContributeSheet = true }) {
                        Icon(Icons.Default.Add, contentDescription = "Contribute Dua")
                    }
                    val context = LocalContext.current
                    IconButton(onClick = {
                        ShareHelper.shareDuas(context)
                    }) {
                        Icon(Icons.Default.Share, contentDescription = "Share Duas")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primaryContainer,
                    titleContentColor = MaterialTheme.colorScheme.onPrimaryContainer
                )
            )
        },
        floatingActionButton = {
            ExtendedFloatingActionButton(
                onClick = { showContributeSheet = true },
                icon = { Icon(Icons.Default.Add, contentDescription = null) },
                text = { Text("Contribute Dua") },
                containerColor = MaterialTheme.colorScheme.primary,
                contentColor = MaterialTheme.colorScheme.onPrimary
            )
        }
    ) { paddingValues ->
        DuasContent(
            repository = repository,
            onDuaClick = onDuaClick,
            modifier = modifier
                .fillMaxSize()
                .padding(paddingValues)
        )

        if (showContributeSheet) {
            ContributeDuaBottomSheet(
                repository = repository,
                onDismiss = { showContributeSheet = false }
            )
        }
    }
}

@Composable
fun DuasContent(
    repository: DataRepository = remember { DefaultDataRepository() },
    onDuaClick: (Dua) -> Unit = {},
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

                                        if (dua.sourceType.isNotEmpty()) {
                                            Surface(
                                                color = MaterialTheme.colorScheme.secondaryContainer,
                                                shape = RoundedCornerShape(6.dp)
                                            ) {
                                                Text(
                                                    text = dua.sourceType.replaceFirstChar { it.uppercase() },
                                                    style = MaterialTheme.typography.labelSmall,
                                                    modifier = Modifier.padding(
                                                        horizontal = 6.dp,
                                                        vertical = 2.dp
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
                            DuaCard(
                                dua = selectedDua,
                                language = language,
                                onViewDetailClick = { onDuaClick(selectedDua) }
                            )
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
                        DuaCard(
                            dua = dua,
                            language = language,
                            onViewDetailClick = { onDuaClick(dua) }
                        )
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

        // Category Multi-row Chips
//        FlowRow(
        LazyRow(
            horizontalArrangement = Arrangement.spacedBy(8.dp),
            //verticalArrangement = Arrangement.spacedBy(8.dp),
            modifier = Modifier.fillMaxWidth()
        ) {
//            categories.forEach { (key, label) ->
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
    language: String,
    onViewDetailClick: (() -> Unit)? = null
) {
    var isWordByWordExpanded by remember { mutableStateOf(false) }
    var isCommentaryExpanded by remember { mutableStateOf(false) }
    var tasbihCount by remember { mutableStateOf(0) }
    /*if (onViewDetailClick != null) {
        OutlinedButton(
            onClick = onViewDetailClick,
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(12.dp)
        ) {
            Text("View Detailed Dua & Meanings →", fontWeight = FontWeight.Bold, fontSize = 13.sp)
        }
    }*/
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .then(
                if (onViewDetailClick != null) Modifier.clickable { onViewDetailClick() } else Modifier
            ),
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant),
        onClick = {
            onViewDetailClick?.invoke()
        }
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

                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    if (dua.hadithGrading != null) {
                        Text(
                            text = dua.hadithGrading,
                            style = MaterialTheme.typography.labelSmall,
                            color = MaterialTheme.colorScheme.outline,
                            fontWeight = FontWeight.SemiBold
                        )
                    }
                    val context = LocalContext.current
                    IconButton(
                        onClick = {
                            val trans = if (language == "ur" && !dua.translationUr.isNullOrBlank()) {
                                dua.translationUr
                            } else {
                                dua.translationEn
                            }
                            val ref = dua.quranReference ?: dua.hadithReference
                            ShareHelper.shareDua(
                                context = context,
                                title = dua.title,
                                arabicText = dua.arabicText,
                                translation = trans,
                                reference = ref
                            )
                        },
                        modifier = Modifier.size(28.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.Share,
                            contentDescription = "Share Dua",
                            modifier = Modifier.size(16.dp),
                            tint = MaterialTheme.colorScheme.primary
                        )
                    }
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

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ContributeDuaBottomSheet(
    repository: DataRepository,
    onDismiss: () -> Unit
) {
    val scope = rememberCoroutineScope()
    val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)

    var title by remember { mutableStateOf("") }
    var category by remember { mutableStateOf("Forgiveness & Tawbah") }
    var sourceType by remember { mutableStateOf("hadith") }
    var reference by remember { mutableStateOf("") }
    var arabicText by remember { mutableStateOf("") }
    var transliteration by remember { mutableStateOf("") }
    var translationEn by remember { mutableStateOf("") }
    var translationUr by remember { mutableStateOf("") }
    var contributorName by remember { mutableStateOf("") }

    var isSubmitting by remember { mutableStateOf(false) }
    var errorMessage by remember { mutableStateOf<String?>(null) }
    var showSuccessDialog by remember { mutableStateOf(false) }

    val categories = listOf(
        "Forgiveness & Tawbah",
        "Protection & Safety",
        "Guidance & Knowledge",
        "Morning & Evening",
        "Distress & Hardship",
        "Health & Healing",
        "Comprehensive & Success"
    )

    if (showSuccessDialog) {
        AlertDialog(
            onDismissRequest = {
                showSuccessDialog = false
                onDismiss()
            },
            title = { Text("Alhamdulillah! ✨", fontWeight = FontWeight.Bold) },
            text = {
                Text(
                    "Your Dua contribution has been submitted for review.\n\n" +
                    "To maintain authenticity and precision, our administrative team will verify the citations before publishing it to the public library. JazakAllah Khair!"
                )
            },
            confirmButton = {
                Button(
                    onClick = {
                        showSuccessDialog = false
                        onDismiss()
                    }
                ) {
                    Text("Done")
                }
            }
        )
    }

    ModalBottomSheet(
        onDismissRequest = onDismiss,
        sheetState = sheetState,
        dragHandle = { BottomSheetDefaults.DragHandle() },
        containerColor = MaterialTheme.colorScheme.surface
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 20.dp, vertical = 8.dp)
                .verticalScroll(rememberScrollState())
                .imePadding(),
            verticalArrangement = Arrangement.spacedBy(14.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column {
                    Text(
                        text = "Contribute a Dua",
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.Bold
                    )
                    Text(
                        text = "Subject to admin review & verification",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
                IconButton(onClick = onDismiss) {
                    Icon(Icons.Default.Close, contentDescription = "Close")
                }
            }

            if (errorMessage != null) {
                Surface(
                    color = MaterialTheme.colorScheme.errorContainer,
                    shape = RoundedCornerShape(12.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text(
                        text = errorMessage ?: "",
                        color = MaterialTheme.colorScheme.onErrorContainer,
                        style = MaterialTheme.typography.bodySmall,
                        modifier = Modifier.padding(12.dp)
                    )
                }
            }

            // Title
            OutlinedTextField(
                value = title,
                onValueChange = { title = it },
                label = { Text("Dua Title *") },
                placeholder = { Text("e.g. Dua for Guidance & Faith") },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true,
                shape = RoundedCornerShape(12.dp)
            )

            // Category Chips
            Text("Category", style = MaterialTheme.typography.labelMedium, fontWeight = FontWeight.SemiBold)
            LazyRow(
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                items(categories) { cat ->
                    FilterChip(
                        selected = category == cat,
                        onClick = { category = cat },
                        label = { Text(cat, fontSize = 12.sp) }
                    )
                }
            }

            // Source Type
            Text("Source Type *", style = MaterialTheme.typography.labelMedium, fontWeight = FontWeight.SemiBold)
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                FilterChip(
                    selected = sourceType == "hadith",
                    onClick = { sourceType = "hadith" },
                    label = { Text("Hadith") }
                )
                FilterChip(
                    selected = sourceType == "quran",
                    onClick = { sourceType = "quran" },
                    label = { Text("Quran") }
                )
                FilterChip(
                    selected = sourceType == "both",
                    onClick = { sourceType = "both" },
                    label = { Text("Both") }
                )
            }

            // Reference Citation
            OutlinedTextField(
                value = reference,
                onValueChange = { reference = it },
                label = { Text("Reference / Citation") },
                placeholder = { Text(if (sourceType == "quran") "e.g. Surah Al-Baqarah 2:201" else "e.g. Sahih Muslim 2722") },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true,
                shape = RoundedCornerShape(12.dp)
            )

            // Arabic Text
            OutlinedTextField(
                value = arabicText,
                onValueChange = { arabicText = it },
                label = { Text("Arabic Text * (النص العربي)") },
                placeholder = { Text("رَبَّنَا آتِنَا فِي الدُّنْيَا حَسَنَةً...") },
                modifier = Modifier.fillMaxWidth(),
                minLines = 3,
                maxLines = 5,
                shape = RoundedCornerShape(12.dp)
            )

            // Transliteration
            OutlinedTextField(
                value = transliteration,
                onValueChange = { transliteration = it },
                label = { Text("Transliteration (Optional)") },
                placeholder = { Text("Rabbanā ātinā fid-dunyā...") },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true,
                shape = RoundedCornerShape(12.dp)
            )

            // English Translation
            OutlinedTextField(
                value = translationEn,
                onValueChange = { translationEn = it },
                label = { Text("English Translation *") },
                placeholder = { Text("Our Lord! Give us in this world that which is good...") },
                modifier = Modifier.fillMaxWidth(),
                minLines = 3,
                maxLines = 5,
                shape = RoundedCornerShape(12.dp)
            )

            // Urdu Translation
            OutlinedTextField(
                value = translationUr,
                onValueChange = { translationUr = it },
                label = { Text("Urdu Translation (اردو ترجمہ - Optional)") },
                placeholder = { Text("اے ہمارے رب! ہمیں دنیا میں بھلائی عطا فرما...") },
                modifier = Modifier.fillMaxWidth(),
                minLines = 2,
                maxLines = 4,
                shape = RoundedCornerShape(12.dp)
            )

            // Contributor Name
            OutlinedTextField(
                value = contributorName,
                onValueChange = { contributorName = it },
                label = { Text("Your Name (Optional)") },
                placeholder = { Text("e.g. Brother Ahmad") },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true,
                shape = RoundedCornerShape(12.dp)
            )

            // Notice
            Surface(
                color = MaterialTheme.colorScheme.tertiaryContainer.copy(alpha = 0.5f),
                shape = RoundedCornerShape(12.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                Text(
                    text = "⏳ To protect the sanctity of authentic Duas, all submissions are reviewed by an administrator before appearing in the app.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onTertiaryContainer,
                    modifier = Modifier.padding(12.dp)
                )
            }

            // Submit Button
            Button(
                onClick = {
                    if (title.isBlank() || arabicText.isBlank() || translationEn.isBlank()) {
                        errorMessage = "Please fill in Title, Arabic text, and English translation."
                        return@Button
                    }
                    errorMessage = null
                    isSubmitting = true
                    scope.launch {
                        val result = repository.submitDua(
                            title = title.trim(),
                            category = category.trim(),
                            arabicText = arabicText.trim(),
                            translationEn = translationEn.trim(),
                            sourceType = sourceType,
                            reference = reference.trim(),
                            transliteration = transliteration.trim(),
                            translationUr = translationUr.trim(),
                            submitterName = contributorName.trim()
                        )
                        isSubmitting = false
                        if (result.isSuccess) {
                            showSuccessDialog = true
                        } else {
                            errorMessage = result.exceptionOrNull()?.message ?: "Submission failed. Please check network."
                        }
                    }
                },
                enabled = !isSubmitting,
                modifier = Modifier
                    .fillMaxWidth()
                    .height(50.dp),
                shape = RoundedCornerShape(12.dp)
            ) {
                if (isSubmitting) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(20.dp),
                        color = MaterialTheme.colorScheme.onPrimary,
                        strokeWidth = 2.dp
                    )
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("Submitting...")
                } else {
                    Text("Submit for Review ✨", fontWeight = FontWeight.Bold)
                }
            }

            Spacer(modifier = Modifier.height(24.dp))
        }
    }
}

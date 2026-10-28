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
        containerColor = Color.Transparent,
        topBar = {
            com.asloobulhayat.eternalecho.ui.components.EchoTopBar(
                title = "Sacred Duas",
                onBackClick = onBackClick,
                navigationIcon = Icons.AutoMirrored.Filled.ArrowBack,
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
                }
            )
        },
        floatingActionButton = {
            ExtendedFloatingActionButton(
                onClick = { showContributeSheet = true },
                icon = { Icon(Icons.Default.Add, contentDescription = null) },
                text = { Text("Contribute") },
                containerColor = MaterialTheme.colorScheme.primary,
                contentColor = MaterialTheme.colorScheme.onPrimary,
                shape = RoundedCornerShape(16.dp)
            )
        }
    ) { paddingValues ->
        com.asloobulhayat.eternalecho.ui.components.EchoScreenBackground {
            DuasContent(
                repository = repository,
                onDuaClick = onDuaClick,
                modifier = modifier
                    .fillMaxSize()
                    .padding(paddingValues)
            )
        }

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
                        .weight(1f)
                        .padding(24.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Column(
                        horizontalAlignment = Alignment.CenterHorizontally,
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Text(
                            text = "No duas found",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold,
                            textAlign = TextAlign.Center
                        )
                        Text(
                            text = "Try another category, source, or search term.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            textAlign = TextAlign.Center
                        )
                    }
                }
            } else {
                LazyColumn(
                    verticalArrangement = Arrangement.spacedBy(12.dp),
                    contentPadding = PaddingValues(bottom = 88.dp),
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
            placeholder = { Text("Search duas…", fontSize = 13.sp) },
            leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
            trailingIcon = {
                if (searchQuery.isNotEmpty()) {
                    IconButton(onClick = { onSearchQueryChange("") }) {
                        Icon(Icons.Default.Close, contentDescription = "Clear search")
                    }
                }
            },
            shape = RoundedCornerShape(14.dp),
            modifier = Modifier.fillMaxWidth(),
            singleLine = true
        )

        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                listOf("all" to "All", "quran" to "Quran", "hadith" to "Hadith").forEach { (type, label) ->
                    val isSelected = selectedSourceType == type
                    FilterChip(
                        selected = isSelected,
                        onClick = { onSourceTypeChange(type) },
                        label = {
                            Text(
                                label,
                                fontSize = 12.sp,
                                fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium
                            )
                        },
                        shape = RoundedCornerShape(20.dp)
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

        LazyRow(
            horizontalArrangement = Arrangement.spacedBy(8.dp),
            modifier = Modifier.fillMaxWidth()
        ) {
            items(categories) { (key, label) ->
                val isSelected = selectedCategory == key
                FilterChip(
                    selected = isSelected,
                    onClick = { onCategoryChange(key) },
                    label = {
                        Text(
                            text = label,
                            fontSize = 12.sp,
                            fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium
                        )
                    },
                    shape = RoundedCornerShape(20.dp)
                )
            }
        }
    }
}


@Composable
fun DuaSourceBadge(sourceType: String) {
    Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
        if (sourceType == "quran" || sourceType == "both") {
            Surface(
                color = MaterialTheme.colorScheme.primary.copy(alpha = 0.15f),
                shape = RoundedCornerShape(8.dp)
            ) {
                Text(
                    text = "Quran",
                    style = MaterialTheme.typography.labelSmall,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                )
            }
        }
        if (sourceType == "hadith" || sourceType == "both") {
            Surface(
                color = MaterialTheme.colorScheme.tertiary.copy(alpha = 0.18f),
                shape = RoundedCornerShape(8.dp)
            ) {
                Text(
                    text = "Hadith",
                    style = MaterialTheme.typography.labelSmall,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.tertiary,
                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                )
            }
        }
    }
}

/**
 * Compact list card — opens detail for word-by-word, commentary, and tasbih.
 */
@Composable
fun DuaCard(
    dua: Dua,
    language: String,
    onViewDetailClick: (() -> Unit)? = null
) {
    val context = LocalContext.current
    val translation = if (language == "ur" && !dua.translationUr.isNullOrBlank()) {
        dua.translationUr
    } else {
        dua.translationEn
    }
    val arabicPreview = if (dua.arabicText.length > 90) {
        dua.arabicText.take(90).trimEnd() + "…"
    } else {
        dua.arabicText
    }

    Card(
        onClick = { onViewDetailClick?.invoke() },
        modifier = Modifier.fillMaxWidth(),
        enabled = onViewDetailClick != null,
        shape = RoundedCornerShape(18.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surface.copy(alpha = 0.94f)
        ),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.6f))
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(
                    horizontalArrangement = Arrangement.spacedBy(6.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    modifier = Modifier.weight(1f)
                ) {
                    Surface(
                        color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.75f),
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Text(
                            text = dua.category,
                            style = MaterialTheme.typography.labelSmall,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onPrimaryContainer,
                            maxLines = 1,
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                        )
                    }
                    DuaSourceBadge(sourceType = dua.sourceType)
                }

                IconButton(
                    onClick = {
                        ShareHelper.shareDua(
                            context = context,
                            title = dua.title,
                            arabicText = dua.arabicText,
                            translation = translation,
                            reference = dua.quranReference ?: dua.hadithReference,
                            idOrSlug = dua.slug.ifBlank { dua.id.toString() }
                        )
                    },
                    modifier = Modifier.size(32.dp)
                ) {
                    Icon(
                        imageVector = Icons.Default.Share,
                        contentDescription = "Share Dua",
                        modifier = Modifier.size(18.dp),
                        tint = MaterialTheme.colorScheme.primary
                    )
                }
            }

            Text(
                text = dua.title,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface,
                maxLines = 2
            )

            Text(
                text = arabicPreview,
                fontSize = 20.sp,
                fontWeight = FontWeight.Medium,
                textAlign = TextAlign.End,
                lineHeight = 34.sp,
                modifier = Modifier.fillMaxWidth(),
                color = MaterialTheme.colorScheme.onSurface,
                fontFamily = androidx.compose.ui.text.font.FontFamily.Serif
            )

            Text(
                text = translation,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                maxLines = 3,
                textAlign = if (language == "ur") TextAlign.End else TextAlign.Start,
                lineHeight = 20.sp
            )

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                val ref = dua.quranReference ?: dua.hadithReference
                Text(
                    text = ref?.takeIf { it.isNotBlank() } ?: if (dua.repeatCount > 1) "Repeat ${dua.repeatCount}×" else "Open details",
                    style = MaterialTheme.typography.labelSmall,
                    fontWeight = FontWeight.SemiBold,
                    color = MaterialTheme.colorScheme.primary,
                    maxLines = 1,
                    modifier = Modifier.weight(1f)
                )
                if (onViewDetailClick != null) {
                    Text(
                        text = "Open →",
                        style = MaterialTheme.typography.labelMedium,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.primary
                    )
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

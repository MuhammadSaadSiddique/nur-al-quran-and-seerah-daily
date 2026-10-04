package com.asloobulhayat.eternalecho.ui.surah

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.ArrowForward
import androidx.compose.material.icons.filled.Clear
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewmodel.compose.viewModel
import com.asloobulhayat.eternalecho.data.Connection
import com.asloobulhayat.eternalecho.data.ConnectionsData
import com.asloobulhayat.eternalecho.data.DefaultDataRepository
import com.asloobulhayat.eternalecho.data.Verse
import com.asloobulhayat.eternalecho.theme.EternalEchoMobileTheme

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SurahScreen(
    surahNumber: Int,
    onBackClick: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: SurahScreenViewModel = viewModel(key = "surah_$surahNumber") { SurahScreenViewModel(surahNumber, DefaultDataRepository()) }
) {
    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Surah #$surahNumber", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onBackClick) {
                        Icon(imageVector = Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.primaryContainer,
                    titleContentColor = MaterialTheme.colorScheme.onPrimaryContainer
                )
            )
        }
    ) { paddingValues ->
        SurahReaderContent(
            surahNumber = surahNumber,
            modifier = modifier
                .fillMaxSize()
                .padding(paddingValues),
            viewModel = viewModel
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SurahReaderContent(
    surahNumber: Int,
    modifier: Modifier = Modifier,
    viewModel: SurahScreenViewModel = viewModel(key = "surah_$surahNumber") { SurahScreenViewModel(surahNumber, DefaultDataRepository()) }
) {
    val versesState by viewModel.versesState.collectAsStateWithLifecycle()
    val connectionsState by viewModel.connectionsState.collectAsStateWithLifecycle()

    var activeVerseNumber by remember { mutableStateOf<Int?>(null) }
    var initialCategory by remember { mutableStateOf("all") }

    Box(modifier = modifier.fillMaxSize()) {
        when (val state = versesState) {
            is VersesUiState.Loading -> {
                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            }
            is VersesUiState.Error -> {
                Column(
                    modifier = Modifier.align(Alignment.Center),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Text("Error: ${state.throwable.message}", color = MaterialTheme.colorScheme.error)
                    Spacer(modifier = Modifier.height(8.dp))
                    Button(onClick = { viewModel.loadVerses() }) {
                        Text("Retry")
                    }
                }
            }
            is VersesUiState.Success -> {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(14.dp)
                ) {
                    items(state.data, key = { it.verseNumber }) { verse ->
                        VerseItem(
                            verse = verse,
                            onOpenCategory = { category ->
                                activeVerseNumber = verse.verseNumber
                                initialCategory = category
                                viewModel.loadConnections(verse.verseNumber)
                            }
                        )
                    }
                }

                if (activeVerseNumber != null) {
                    val currentVerse = state.data.find { it.verseNumber == activeVerseNumber }
                    val totalVerses = state.data.maxOfOrNull { it.verseNumber } ?: state.data.size

                    QuranicLinkageBottomSheet(
                        surahNumber = surahNumber,
                        verseNumber = activeVerseNumber!!,
                        verse = currentVerse,
                        totalVerses = totalVerses,
                        connectionsState = connectionsState,
                        initialCategory = initialCategory,
                        onDismiss = {
                            activeVerseNumber = null
                            viewModel.clearConnections()
                        },
                        onNavigateVerse = { targetVerse ->
                            activeVerseNumber = targetVerse
                            viewModel.loadConnections(targetVerse)
                        },
                        onRetry = {
                            viewModel.loadConnections(activeVerseNumber!!)
                        }
                    )
                }
            }
        }
    }
}

@Composable
fun VerseItem(
    verse: Verse,
    onOpenCategory: (category: String) -> Unit,
    modifier: Modifier = Modifier
) {
    Card(
        modifier = modifier
            .fillMaxWidth()
            .clickable { onOpenCategory("all") },
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surfaceVariant
        ),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Column(
            modifier = Modifier.padding(16.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Badge(
                    containerColor = MaterialTheme.colorScheme.primary,
                    contentColor = MaterialTheme.colorScheme.onPrimary
                ) {
                    Text(
                        text = "Verse ${verse.verseNumber}",
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp),
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.Bold
                    )
                }
                Surface(
                    color = MaterialTheme.colorScheme.surface,
                    shape = RoundedCornerShape(8.dp),
                    border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))
                ) {
                    Text(
                        text = "Juz ${verse.juzNumber}",
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp),
                        style = MaterialTheme.typography.labelSmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }

            Spacer(modifier = Modifier.height(14.dp))

            Text(
                text = verse.textArabic,
                fontFamily = FontFamily.Serif,
                fontSize = 24.sp,
                lineHeight = 40.sp,
                textAlign = TextAlign.Right,
                modifier = Modifier.fillMaxWidth(),
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface
            )

            Spacer(modifier = Modifier.height(10.dp))

            Text(
                text = verse.textTransliteration,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                lineHeight = 20.sp
            )

            Spacer(modifier = Modifier.height(14.dp))
            HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.35f))
            Spacer(modifier = Modifier.height(10.dp))

            // Interactive category pills row
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Surface(
                    modifier = Modifier.clickable { onOpenCategory("all") },
                    shape = RoundedCornerShape(20.dp),
                    color = MaterialTheme.colorScheme.primary.copy(alpha = 0.12f),
                    border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.3f))
                ) {
                    Row(
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(4.dp)
                    ) {
                        Text(
                            text = "🔗 Lens & Linkages",
                            style = MaterialTheme.typography.labelSmall,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.primary
                        )
                    }
                }

                Surface(
                    modifier = Modifier.clickable { onOpenCategory("science") },
                    shape = RoundedCornerShape(20.dp),
                    color = Color(0xFF7C3AED).copy(alpha = 0.10f),
                    border = BorderStroke(1.dp, Color(0xFF7C3AED).copy(alpha = 0.25f))
                ) {
                    Text(
                        text = "🔬 Science",
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.SemiBold,
                        color = Color(0xFF6D28D9)
                    )
                }

                Surface(
                    modifier = Modifier.clickable { onOpenCategory("seerah") },
                    shape = RoundedCornerShape(20.dp),
                    color = Color(0xFF2563EB).copy(alpha = 0.10f),
                    border = BorderStroke(1.dp, Color(0xFF2563EB).copy(alpha = 0.25f))
                ) {
                    Text(
                        text = "🕌 Seerah",
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.SemiBold,
                        color = Color(0xFF1D4ED8)
                    )
                }

                Surface(
                    modifier = Modifier.clickable { onOpenCategory("hadith") },
                    shape = RoundedCornerShape(20.dp),
                    color = Color(0xFF059669).copy(alpha = 0.10f),
                    border = BorderStroke(1.dp, Color(0xFF059669).copy(alpha = 0.25f))
                ) {
                    Text(
                        text = "📚 Hadith",
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.SemiBold,
                        color = Color(0xFF047857)
                    )
                }
            }
        }
    }
}

data class CategoryFilterItem(
    val id: String,
    val label: String,
    val count: Int,
    val emoji: String
)

data class TypedConnection(
    val type: String,
    val connection: Connection
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun QuranicLinkageBottomSheet(
    surahNumber: Int,
    verseNumber: Int,
    verse: Verse?,
    totalVerses: Int,
    connectionsState: ConnectionsUiState,
    initialCategory: String,
    onDismiss: () -> Unit,
    onNavigateVerse: (Int) -> Unit,
    onRetry: () -> Unit,
    modifier: Modifier = Modifier
) {
    var selectedCategory by remember(initialCategory) { mutableStateOf(initialCategory) }
    var searchQuery by remember { mutableStateOf("") }

    ModalBottomSheet(
        onDismissRequest = onDismiss,
        sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true),
        containerColor = MaterialTheme.colorScheme.surface,
        dragHandle = { BottomSheetDefaults.DragHandle() },
        modifier = modifier
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .fillMaxHeight(0.90f)
                .padding(horizontal = 20.dp)
        ) {
            // Header: Title + Navigation Controls + Close
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 12.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column {
                    Text(
                        text = "Quranic Lens & Linkages",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(6.dp)
                    ) {
                        Text(
                            text = "Surah $surahNumber : Ayah $verseNumber",
                            style = MaterialTheme.typography.labelMedium,
                            fontWeight = FontWeight.SemiBold,
                            color = MaterialTheme.colorScheme.primary
                        )
                        if (verse != null) {
                            Text(
                                text = "• Juz ${verse.juzNumber}",
                                style = MaterialTheme.typography.labelSmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                }

                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(4.dp)
                ) {
                    IconButton(
                        onClick = { if (verseNumber > 1) onNavigateVerse(verseNumber - 1) },
                        enabled = verseNumber > 1
                    ) {
                        Icon(
                            imageVector = Icons.AutoMirrored.Filled.ArrowBack,
                            contentDescription = "Previous Verse",
                            tint = if (verseNumber > 1) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.38f)
                        )
                    }

                    IconButton(
                        onClick = { if (verseNumber < totalVerses) onNavigateVerse(verseNumber + 1) },
                        enabled = verseNumber < totalVerses
                    ) {
                        Icon(
                            imageVector = Icons.AutoMirrored.Filled.ArrowForward,
                            contentDescription = "Next Verse",
                            tint = if (verseNumber < totalVerses) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.38f)
                        )
                    }

                    IconButton(onClick = onDismiss) {
                        Icon(Icons.Default.Close, contentDescription = "Close")
                    }
                }
            }

            // Verse Context Snippet Card
            if (verse != null) {
                Surface(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 12.dp),
                    shape = RoundedCornerShape(12.dp),
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.6f),
                    border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.35f))
                ) {
                    Column(modifier = Modifier.padding(12.dp)) {
                        Text(
                            text = verse.textArabic,
                            fontFamily = FontFamily.Serif,
                            fontSize = 18.sp,
                            lineHeight = 30.sp,
                            textAlign = TextAlign.Right,
                            modifier = Modifier.fillMaxWidth(),
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onSurface
                        )
                        if (verse.textTransliteration.isNotBlank()) {
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = verse.textTransliteration,
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                                maxLines = 2,
                                overflow = TextOverflow.Ellipsis
                            )
                        }
                    }
                }
            }

            HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.35f))

            // Body Content based on connectionsState
            when (connectionsState) {
                is ConnectionsUiState.Loading -> {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .weight(1f),
                        contentAlignment = Alignment.Center
                    ) {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            CircularProgressIndicator(color = MaterialTheme.colorScheme.primary)
                            Text(
                                text = "Loading linkages for Verse $verseNumber...",
                                style = MaterialTheme.typography.bodyMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                }

                is ConnectionsUiState.Error -> {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .weight(1f),
                        contentAlignment = Alignment.Center
                    ) {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            Text(
                                text = "Failed to load linkages",
                                style = MaterialTheme.typography.titleSmall,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.error
                            )
                            Text(
                                text = connectionsState.throwable.message ?: "Unknown error occurred",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                                textAlign = TextAlign.Center
                            )
                            Button(onClick = onRetry) {
                                Text("Retry")
                            }
                        }
                    }
                }

                is ConnectionsUiState.Success -> {
                    val data = connectionsState.data
                    val totalCount = data.science.size + data.seerah.size + data.hadith.size + data.history.size + data.scripture.size

                    val filterCategories = listOf(
                        CategoryFilterItem("all", "All", totalCount, "🌐"),
                        CategoryFilterItem("science", "Science", data.science.size, "🔬"),
                        CategoryFilterItem("seerah", "Seerah", data.seerah.size, "🕌"),
                        CategoryFilterItem("hadith", "Hadith", data.hadith.size, "📚"),
                        CategoryFilterItem("history", "History", data.history.size, "🏛️"),
                        CategoryFilterItem("scripture", "Scripture", data.scripture.size, "📖")
                    )

                    Spacer(modifier = Modifier.height(10.dp))

                    // Category Filter Scrollable Tabs
                    FlowRow(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                        verticalArrangement = Arrangement.spacedBy(6.dp)
                    ) {
                        filterCategories.forEach { filterItem ->
                            val isSelected = selectedCategory == filterItem.id
                            Surface(
                                modifier = Modifier.clickable { selectedCategory = filterItem.id },
                                shape = RoundedCornerShape(20.dp),
                                color = if (isSelected) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                                border = BorderStroke(
                                    1.dp,
                                    if (isSelected) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.4f)
                                )
                            ) {
                                Row(
                                    modifier = Modifier.padding(horizontal = 12.dp, vertical = 7.dp),
                                    verticalAlignment = Alignment.CenterVertically,
                                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                                ) {
                                    Text(
                                        text = "${filterItem.emoji} ${filterItem.label}",
                                        style = MaterialTheme.typography.labelMedium,
                                        fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium,
                                        color = if (isSelected) MaterialTheme.colorScheme.onPrimaryContainer else MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                    Surface(
                                        shape = CircleShape,
                                        color = if (isSelected) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.6f)
                                    ) {
                                        Text(
                                            text = "${filterItem.count}",
                                            modifier = Modifier.padding(horizontal = 6.dp, vertical = 1.dp),
                                            style = MaterialTheme.typography.labelSmall,
                                            fontWeight = FontWeight.Bold,
                                            color = if (isSelected) MaterialTheme.colorScheme.onPrimary else MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }
                                }
                            }
                        }
                    }

                    Spacer(modifier = Modifier.height(10.dp))

                    // Search input
                    OutlinedTextField(
                        value = searchQuery,
                        onValueChange = { searchQuery = it },
                        modifier = Modifier.fillMaxWidth(),
                        placeholder = { Text("Search connections...", style = MaterialTheme.typography.bodySmall) },
                        leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search", modifier = Modifier.size(18.dp)) },
                        trailingIcon = {
                            if (searchQuery.isNotEmpty()) {
                                IconButton(onClick = { searchQuery = "" }) {
                                    Icon(Icons.Default.Clear, contentDescription = "Clear", modifier = Modifier.size(16.dp))
                                }
                            }
                        },
                        singleLine = true,
                        shape = RoundedCornerShape(12.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedBorderColor = MaterialTheme.colorScheme.primary,
                            unfocusedBorderColor = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f)
                        )
                    )

                    Spacer(modifier = Modifier.height(10.dp))

                    // Prepare items based on category and query
                    val allItems = remember(data) {
                        val list = mutableListOf<TypedConnection>()
                        data.science.forEach { list.add(TypedConnection("science", it)) }
                        data.seerah.forEach { list.add(TypedConnection("seerah", it)) }
                        data.hadith.forEach { list.add(TypedConnection("hadith", it)) }
                        data.history.forEach { list.add(TypedConnection("history", it)) }
                        data.scripture.forEach { list.add(TypedConnection("scripture", it)) }
                        list
                    }

                    val filteredItems = remember(selectedCategory, searchQuery, allItems) {
                        val categoryFiltered = if (selectedCategory == "all") {
                            allItems
                        } else {
                            allItems.filter { it.type == selectedCategory }
                        }

                        if (searchQuery.isBlank()) {
                            categoryFiltered
                        } else {
                            val q = searchQuery.trim().lowercase()
                            categoryFiltered.filter { item ->
                                item.connection.title.lowercase().contains(q) ||
                                item.connection.description.lowercase().contains(q) ||
                                item.connection.relevanceDescription.lowercase().contains(q) ||
                                item.connection.sourceName.lowercase().contains(q) ||
                                item.connection.location.lowercase().contains(q) ||
                                item.connection.category.lowercase().contains(q) ||
                                item.connection.collectionName.lowercase().contains(q) ||
                                item.connection.narratorChain.lowercase().contains(q)
                            }
                        }
                    }

                    if (filteredItems.isEmpty()) {
                        Box(
                            modifier = Modifier
                                .fillMaxWidth()
                                .weight(1f),
                            contentAlignment = Alignment.Center
                        ) {
                            Column(
                                horizontalAlignment = Alignment.CenterHorizontally,
                                verticalArrangement = Arrangement.spacedBy(8.dp),
                                modifier = Modifier.padding(24.dp)
                            ) {
                                Text(
                                    text = if (totalCount == 0) "📖" else "🔍",
                                    fontSize = 36.sp
                                )
                                Text(
                                    text = if (totalCount == 0) {
                                        "No approved connections mapped for Verse $verseNumber yet."
                                    } else {
                                        "No matching linkages found for this filter."
                                    },
                                    style = MaterialTheme.typography.bodyMedium,
                                    fontWeight = FontWeight.Medium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                    textAlign = TextAlign.Center
                                )
                            }
                        }
                    } else {
                        LazyColumn(
                            modifier = Modifier
                                .fillMaxWidth()
                                .weight(1f),
                            contentPadding = PaddingValues(vertical = 8.dp),
                            verticalArrangement = Arrangement.spacedBy(14.dp)
                        ) {
                            items(filteredItems) { item ->
                                ConnectionCard(connection = item.connection, type = item.type)
                            }
                        }
                    }
                }

                is ConnectionsUiState.Idle -> {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .weight(1f),
                        contentAlignment = Alignment.Center
                    ) {
                        CircularProgressIndicator(color = MaterialTheme.colorScheme.primary)
                    }
                }
            }
        }
    }
}

@Composable
fun ConnectionCard(
    connection: Connection,
    type: String,
    modifier: Modifier = Modifier
) {
    // Distinct styling per category matching web theme
    val accentColor = when (type.lowercase()) {
        "science" -> Color(0xFF7C3AED)
        "seerah" -> Color(0xFF2563EB)
        "hadith" -> Color(0xFF059669)
        "history" -> Color(0xFFD97706)
        "scripture" -> Color(0xFFE11D48)
        else -> MaterialTheme.colorScheme.primary
    }

    val typeBadgeTitle = when (type.lowercase()) {
        "science" -> "🔬 Science & Nature"
        "seerah" -> "🕌 Prophetic Seerah"
        "hadith" -> "📚 Prophetic Hadith"
        "history" -> "🏛️ Historical Context"
        "scripture" -> "📖 Scriptural Parallel"
        else -> type.replaceFirstChar { it.uppercase() }
    }

    Card(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.45f)
        ),
        border = BorderStroke(1.dp, accentColor.copy(alpha = 0.25f))
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            // Category Badge & Title Header
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Surface(
                    shape = RoundedCornerShape(8.dp),
                    color = accentColor.copy(alpha = 0.12f),
                    border = BorderStroke(1.dp, accentColor.copy(alpha = 0.3f))
                ) {
                    Text(
                        text = typeBadgeTitle,
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp),
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.Bold,
                        color = accentColor
                    )
                }

                // Header secondary badge (e.g. grading, date, field)
                when (type.lowercase()) {
                    "hadith" -> {
                        if (connection.grading.isNotBlank()) {
                            val isSahih = connection.grading.contains("sahih", ignoreCase = true)
                            val gradColor = if (isSahih) Color(0xFF059669) else Color(0xFFD97706)
                            Surface(
                                shape = RoundedCornerShape(8.dp),
                                color = gradColor.copy(alpha = 0.15f)
                            ) {
                                Text(
                                    text = connection.grading,
                                    modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                                    style = MaterialTheme.typography.labelSmall,
                                    fontWeight = FontWeight.Bold,
                                    color = gradColor
                                )
                            }
                        }
                    }
                    "seerah", "history" -> {
                        if (connection.dateInfo.isNotBlank()) {
                            Surface(
                                shape = RoundedCornerShape(8.dp),
                                color = MaterialTheme.colorScheme.surface,
                                border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.4f))
                            ) {
                                Text(
                                    text = connection.dateInfo,
                                    modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                                    style = MaterialTheme.typography.labelSmall,
                                    fontWeight = FontWeight.SemiBold,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                        }
                    }
                    "science" -> {
                        val field = connection.category.ifBlank { connection.extraInfo }
                        if (field.isNotBlank()) {
                            Surface(
                                shape = RoundedCornerShape(8.dp),
                                color = accentColor.copy(alpha = 0.10f)
                            ) {
                                Text(
                                    text = field,
                                    modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                                    style = MaterialTheme.typography.labelSmall,
                                    fontWeight = FontWeight.SemiBold,
                                    color = accentColor
                                )
                            }
                        }
                    }
                    "scripture" -> {
                        if (connection.scriptureType.isNotBlank()) {
                            Surface(
                                shape = RoundedCornerShape(8.dp),
                                color = accentColor.copy(alpha = 0.10f)
                            ) {
                                Text(
                                    text = connection.scriptureType,
                                    modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                                    style = MaterialTheme.typography.labelSmall,
                                    fontWeight = FontWeight.SemiBold,
                                    color = accentColor
                                )
                            }
                        }
                    }
                }
            }

            // Title
            Text(
                text = connection.title,
                style = MaterialTheme.typography.titleSmall,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface
            )

            // Narrator chain for Hadith
            if (connection.narratorChain.isNotBlank()) {
                Text(
                    text = "Narrated by: ${connection.narratorChain}",
                    style = MaterialTheme.typography.bodySmall,
                    fontStyle = FontStyle.Italic,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }

            // Arabic text for Hadith
            if (connection.arabicText.isNotBlank()) {
                Surface(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(10.dp),
                    color = MaterialTheme.colorScheme.surface,
                    border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.3f))
                ) {
                    Text(
                        text = connection.arabicText,
                        fontFamily = FontFamily.Serif,
                        fontSize = 17.sp,
                        lineHeight = 28.sp,
                        textAlign = TextAlign.Right,
                        fontWeight = FontWeight.Bold,
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(12.dp),
                        color = MaterialTheme.colorScheme.onSurface
                    )
                }
            }

            // Main Description / Text
            if (connection.description.isNotBlank()) {
                Text(
                    text = connection.description,
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    lineHeight = 22.sp
                )
            }

            // Context Metadata (Location, Category, Source, Credibility)
            val metaElements = mutableListOf<String>()
            if (connection.location.isNotBlank()) metaElements.add("📍 Location: ${connection.location}")
            if (connection.category.isNotBlank() && type.lowercase() != "science") metaElements.add("🏷️ Category: ${connection.category}")
            if (connection.sourceName.isNotBlank()) {
                val label = if (type.lowercase() == "science") "🔬 Study Source:" else "📚 Source:"
                metaElements.add("$label ${connection.sourceName}")
            }
            if (connection.credibilityScore.isNotBlank()) {
                metaElements.add("Credibility: ${connection.credibilityScore}")
            }
            if (connection.relationshipType.isNotBlank()) {
                metaElements.add("Parallel Type: ${connection.relationshipType}")
            }

            if (metaElements.isNotEmpty()) {
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(top = 2.dp),
                    verticalArrangement = Arrangement.spacedBy(4.dp)
                ) {
                    metaElements.forEach { meta ->
                        Text(
                            text = meta,
                            style = MaterialTheme.typography.labelSmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            fontWeight = FontWeight.Medium
                        )
                    }
                }
            }

            // Highlighted Relevance Context Box
            if (connection.relevanceDescription.isNotBlank()) {
                Surface(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(10.dp),
                    color = MaterialTheme.colorScheme.surface,
                    border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))
                ) {
                    Column(
                        modifier = Modifier.padding(10.dp),
                        verticalArrangement = Arrangement.spacedBy(4.dp)
                    ) {
                        Text(
                            text = "💡 RELEVANCE CONTEXT",
                            style = MaterialTheme.typography.labelSmall,
                            fontWeight = FontWeight.Black,
                            fontSize = 10.sp,
                            color = MaterialTheme.colorScheme.primary
                        )
                        Text(
                            text = connection.relevanceDescription,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurface,
                            lineHeight = 18.sp
                        )
                    }
                }
            }
        }
    }
}

@Preview
@Composable
fun PreviewConnectionCard() {
    EternalEchoMobileTheme {
        ConnectionCard(
            connection = Connection(
                title = "Origin of Life in Water",
                description = "Cytoplasm consists of up to 80% water. Modern biology confirms all life began in aqueous environments.",
                relevanceDescription = "Corresponds directly to the Quranic assertion: 'And We made from water every living thing' (21:30).",
                category = "Biology & Cytology",
                sourceName = "Nature (2020), Campbell Biology",
                credibilityScore = "9.5/10"
            ),
            type = "science"
        )
    }
}

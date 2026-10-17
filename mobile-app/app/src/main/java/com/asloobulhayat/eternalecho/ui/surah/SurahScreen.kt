package com.asloobulhayat.eternalecho.ui.surah

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
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
import androidx.compose.material.icons.filled.Share
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import com.asloobulhayat.eternalecho.util.ShareHelper
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
import android.content.Intent
import android.net.Uri
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewmodel.compose.viewModel
import com.asloobulhayat.eternalecho.data.Connection
import com.asloobulhayat.eternalecho.data.ConnectionsData
import com.asloobulhayat.eternalecho.data.DefaultDataRepository
import com.asloobulhayat.eternalecho.data.ResearchContribution
import com.asloobulhayat.eternalecho.data.Verse
import com.asloobulhayat.eternalecho.data.VerseTranslation
import com.asloobulhayat.eternalecho.security.SecurePreferences
import com.asloobulhayat.eternalecho.theme.EternalEchoMobileTheme
import com.asloobulhayat.eternalecho.ui.research.ContributeResearchBottomSheet
import com.asloobulhayat.eternalecho.ui.research.ContributionCard
import com.asloobulhayat.eternalecho.ui.research.VerificationDialog
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SurahScreen(
    surahNumber: Int,
    initialVerseNumber: Int? = null,
    onBackClick: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: SurahScreenViewModel = viewModel(key = "surah_$surahNumber") { SurahScreenViewModel(surahNumber, DefaultDataRepository()) }
) {
    Scaffold(
        containerColor = Color.Transparent,
        topBar = {
            com.asloobulhayat.eternalecho.ui.components.EchoTopBar(
                title = "Surah $surahNumber",
                onBackClick = onBackClick,
                navigationIcon = Icons.AutoMirrored.Filled.ArrowBack,
                actions = {
                    val context = LocalContext.current
                    IconButton(onClick = {
                        ShareHelper.shareSurah(context, surahNumber)
                    }) {
                        Icon(imageVector = Icons.Default.Share, contentDescription = "Share Surah")
                    }
                }
            )
        }
    ) { paddingValues ->
        com.asloobulhayat.eternalecho.ui.components.EchoScreenBackground {
            SurahReaderContent(
                surahNumber = surahNumber,
                initialVerseNumber = initialVerseNumber,
                modifier = modifier
                    .fillMaxSize()
                    .padding(paddingValues),
                viewModel = viewModel
            )
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SurahReaderContent(
    surahNumber: Int,
    initialVerseNumber: Int? = null,
    modifier: Modifier = Modifier,
    viewModel: SurahScreenViewModel = viewModel(key = "surah_$surahNumber") { SurahScreenViewModel(surahNumber, DefaultDataRepository()) }
) {
    val versesState by viewModel.versesState.collectAsStateWithLifecycle()
    val connectionsState by viewModel.connectionsState.collectAsStateWithLifecycle()
    val translationsLoading by viewModel.translationsLoading.collectAsStateWithLifecycle()
    val context = LocalContext.current
    val prefs = remember { SecurePreferences.getInstance(context) }
    var selectedEditionId by remember {
        mutableStateOf(prefs.getString("quran_translation_id", "en.sahih") ?: "en.sahih")
    }

    var activeVerseNumber by remember { mutableStateOf<Int?>(initialVerseNumber) }
    var initialCategory by remember { mutableStateOf("all") }
    val listState = androidx.compose.foundation.lazy.rememberLazyListState()

    LaunchedEffect(initialVerseNumber) {
        if (initialVerseNumber != null) {
            viewModel.loadConnections(initialVerseNumber)
        }
    }

    LaunchedEffect(versesState, initialVerseNumber) {
        if (initialVerseNumber != null && versesState is VersesUiState.Success) {
            val list = (versesState as VersesUiState.Success).data
            val targetIndex = list.indexOfFirst { it.verseNumber == initialVerseNumber }
            if (targetIndex >= 0) {
                listState.animateScrollToItem(targetIndex)
            }
        }
    }

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
                val editions = state.data.firstNotNullOfOrNull { verse ->
                    verse.translations.takeIf { it.isNotEmpty() }
                }.orEmpty()

                Column(modifier = Modifier.fillMaxSize()) {
                    if (translationsLoading && editions.isEmpty()) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(start = 16.dp, end = 16.dp, top = 12.dp),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            CircularProgressIndicator(
                                modifier = Modifier.size(16.dp),
                                strokeWidth = 2.dp
                            )
                            Text(
                                text = "Loading translations…",
                                style = MaterialTheme.typography.labelMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                    TranslationEditionBar(
                        editions = editions,
                        selectedId = selectedEditionId,
                        onSelect = { editionId ->
                            selectedEditionId = editionId
                            prefs.putString("quran_translation_id", editionId)
                        }
                    )
                    LazyColumn(
                        state = listState,
                        modifier = Modifier.weight(1f),
                        contentPadding = PaddingValues(16.dp),
                        verticalArrangement = Arrangement.spacedBy(14.dp)
                    ) {
                        items(state.data, key = { it.verseNumber }) { verse ->
                            VerseItem(
                                verse = verse,
                                selectedEditionId = selectedEditionId,
                                onOpenCategory = { category ->
                                    activeVerseNumber = verse.verseNumber
                                    initialCategory = category
                                    viewModel.loadConnections(verse.verseNumber)
                                }
                            )
                        }
                    }
                }

                if (activeVerseNumber != null) {
                    val currentVerse = state.data.find { it.verseNumber == activeVerseNumber }
                    val totalVerses = state.data.maxOfOrNull { it.verseNumber } ?: state.data.size

                    QuranicLinkageBottomSheet(
                        surahNumber = surahNumber,
                        verseNumber = activeVerseNumber!!,
                        verse = currentVerse,
                        selectedEditionId = selectedEditionId,
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

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun TranslationEditionBar(
    editions: List<VerseTranslation>,
    selectedId: String,
    onSelect: (String) -> Unit
) {
    if (editions.isEmpty()) return

    Column(
        modifier = Modifier
            .fillMaxWidth()
            .padding(start = 16.dp, end = 16.dp, top = 12.dp)
    ) {
        Text(
            text = "Translation",
            style = MaterialTheme.typography.labelMedium,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        Spacer(modifier = Modifier.height(8.dp))
        LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            items(editions, key = { it.id }) { edition ->
                FilterChip(
                    selected = edition.id == selectedId,
                    onClick = { onSelect(edition.id) },
                    label = {
                        Text(
                            text = edition.name,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis
                        )
                    }
                )
            }
        }
    }
}

@Composable
fun VerseItem(
    verse: Verse,
    onOpenCategory: (category: String) -> Unit,
    modifier: Modifier = Modifier,
    selectedEditionId: String = "en.sahih"
) {
    val chosen = verse.translationFor(selectedEditionId)
    val translationText = chosen?.text?.takeIf { it.isNotBlank() } ?: verse.textTransliteration
    val isRtl = chosen?.direction == "rtl"
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
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
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
                    val context = LocalContext.current
                    IconButton(
                        onClick = {
                            ShareHelper.shareVerse(
                                context = context,
                                verseKey = verse.verseKey,
                                arabicText = verse.textArabic,
                                translation = translationText
                            )
                        },
                        modifier = Modifier.size(28.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.Share,
                            contentDescription = "Share Verse",
                            modifier = Modifier.size(16.dp),
                            tint = MaterialTheme.colorScheme.primary
                        )
                    }
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

            if (!chosen?.name.isNullOrBlank()) {
                Text(
                    text = chosen.name.uppercase(),
                    style = MaterialTheme.typography.labelSmall,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary
                )
                Spacer(modifier = Modifier.height(4.dp))
            }

            Text(
                text = translationText,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurface,
                lineHeight = if (isRtl) 28.sp else 22.sp,
                textAlign = if (isRtl) TextAlign.Right else TextAlign.Start,
                modifier = Modifier.fillMaxWidth()
            )

            if (chosen != null && verse.textTransliteration.isNotBlank()) {
                Spacer(modifier = Modifier.height(6.dp))
                Text(
                    text = verse.textTransliteration,
                    style = MaterialTheme.typography.bodySmall,
                    fontStyle = FontStyle.Italic,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    lineHeight = 18.sp
                )
            }

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
                    color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.4f),
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
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }

                Surface(
                    modifier = Modifier.clickable { onOpenCategory("science") },
                    shape = RoundedCornerShape(20.dp),
                    color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.4f),
                    border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.3f))
                ) {
                    Text(
                        text = "🔬 Science",
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.SemiBold,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }

                Surface(
                    modifier = Modifier.clickable { onOpenCategory("seerah") },
                    shape = RoundedCornerShape(20.dp),
                    color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.4f),
                    border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.3f))
                ) {
                    Text(
                        text = "🕌 Seerah",
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.SemiBold,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }

                Surface(
                    modifier = Modifier.clickable { onOpenCategory("hadith") },
                    shape = RoundedCornerShape(20.dp),
                    color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.4f),
                    border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.3f))
                ) {
                    Text(
                        text = "📚 Hadith",
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.SemiBold,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
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
    selectedEditionId: String = "en.sahih",
    connectionsState: ConnectionsUiState,
    initialCategory: String,
    onDismiss: () -> Unit,
    onNavigateVerse: (Int) -> Unit,
    onRetry: () -> Unit,
    modifier: Modifier = Modifier
) {
    var selectedCategory by remember(initialCategory) { mutableStateOf(initialCategory) }
    var searchQuery by remember { mutableStateOf("") }

    val sheetContext = LocalContext.current
    val prefs = remember { SecurePreferences.getInstance(sheetContext) }
    val userId = remember { prefs.getInt("user_id", -1) }
    val userName = remember { prefs.getString("user_name", "") ?: "" }
    val userEmail = remember { prefs.getString("user_email", "") ?: "" }
    val isLoggedIn = userId > 0
    val chosenTranslation = verse?.translationFor(selectedEditionId)
    val translationText = chosenTranslation?.text?.takeIf { it.isNotBlank() }
        ?: verse?.textTransliteration.orEmpty()
    val repository = remember { DefaultDataRepository() }
    val scope = rememberCoroutineScope()

    var showContributeSheet by remember { mutableStateOf(false) }
    var selectedItemForVerify by remember { mutableStateOf<ResearchContribution?>(null) }
    var surahContributions by remember { mutableStateOf<List<ResearchContribution>>(emptyList()) }
    var surahPendingContributions by remember { mutableStateOf<List<ResearchContribution>>(emptyList()) }

    fun loadSurahResearch() {
        scope.launch {
            try {
                surahContributions = repository.getResearchContributions(
                    tab = "published",
                    category = "all",
                    surahNumber = surahNumber,
                    search = searchQuery,
                    page = 1,
                    userId = if (isLoggedIn) userId else null
                )
                if (isLoggedIn) {
                    surahPendingContributions = repository.getResearchContributions(
                        tab = "pending",
                        category = "all",
                        surahNumber = surahNumber,
                        search = searchQuery,
                        page = 1,
                        userId = userId
                    )
                }
            } catch (_: Exception) {}
        }
    }

    LaunchedEffect(surahNumber, verseNumber, isLoggedIn) {
        loadSurahResearch()
    }

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
                        text = "Quranic Lens & Research",
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
                    FilledTonalButton(
                        onClick = { showContributeSheet = true },
                        contentPadding = PaddingValues(horizontal = 8.dp, vertical = 2.dp),
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Text("➕ Add Video", fontSize = 10.sp, fontWeight = FontWeight.Bold)
                    }

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

                    val context = LocalContext.current
                    IconButton(
                        onClick = {
                            if (verse != null) {
                                ShareHelper.shareVerse(
                                    context = context,
                                    verseKey = verse.verseKey,
                                    arabicText = verse.textArabic,
                                    translation = translationText
                                )
                            } else {
                                ShareHelper.shareApp(context, "Explore Surah $surahNumber : Ayah $verseNumber linkages on Eternal Echo!")
                            }
                        }
                    ) {
                        Icon(
                            imageVector = Icons.Default.Share,
                            contentDescription = "Share Verse",
                            tint = MaterialTheme.colorScheme.primary
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
                        if (translationText.isNotBlank()) {
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = translationText,
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                                textAlign = if (chosenTranslation?.direction == "rtl") TextAlign.Right else TextAlign.Start,
                                modifier = Modifier.fillMaxWidth(),
                                maxLines = 3,
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
                    val videosForSurah = surahContributions.filter { it.category == "video" }
                    val totalCount = data.science.size + data.seerah.size + data.hadith.size + data.history.size + data.scripture.size + videosForSurah.size

                    val filterCategories = buildList {
                        add(CategoryFilterItem("all", "All", totalCount, "🌐"))
                        add(CategoryFilterItem("video", "Videos", videosForSurah.size, "🎬"))
                        add(CategoryFilterItem("science", "Science", data.science.size, "🔬"))
                        add(CategoryFilterItem("seerah", "Seerah", data.seerah.size, "🕌"))
                        add(CategoryFilterItem("hadith", "Hadith", data.hadith.size, "📚"))
                        add(CategoryFilterItem("history", "History", data.history.size, "🏛️"))
                        add(CategoryFilterItem("scripture", "Scripture", data.scripture.size, "📖"))
                        if (isLoggedIn) {
                            add(CategoryFilterItem("pending", "Pending", surahPendingContributions.size, "⏳"))
                        }
                    }

                    Spacer(modifier = Modifier.height(10.dp))

                    // Category Filter Scrollable Tabs
                    LazyRow(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                    ) {
                        items(filterCategories) { filterItem ->
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
                        placeholder = { Text("Search connections & videos...", style = MaterialTheme.typography.bodySmall) },
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

                    if (selectedCategory == "video" || selectedCategory == "pending") {
                        val listToShow = if (selectedCategory == "pending") surahPendingContributions else videosForSurah
                        if (listToShow.isEmpty()) {
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
                                    Text("🎬", fontSize = 36.sp)
                                    Text(
                                        text = if (selectedCategory == "pending") "No pending items for Surah $surahNumber." else "No videos published for Surah $surahNumber yet.",
                                        style = MaterialTheme.typography.bodyMedium,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                                        textAlign = TextAlign.Center
                                    )
                                    Button(onClick = { showContributeSheet = true }) {
                                        Text("➕ Add Video / Research")
                                    }
                                }
                            }
                        } else {
                            LazyColumn(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .weight(1f),
                                contentPadding = PaddingValues(vertical = 8.dp),
                                verticalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                items(listToShow, key = { it.id }) { item ->
                                    ContributionCard(
                                        item = item,
                                        isLoggedIn = isLoggedIn,
                                        onWatchVideo = { url ->
                                            try {
                                                val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url))
                                                sheetContext.startActivity(intent)
                                            } catch (_: Exception) {}
                                        },
                                        onVerifyClick = {
                                            if (isLoggedIn) {
                                                selectedItemForVerify = item
                                            }
                                        }
                                    )
                                }
                            }
                        }
                    } else {
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
                                    Button(onClick = { showContributeSheet = true }) {
                                        Text("➕ Add Video / Research")
                                    }
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

        if (showContributeSheet) {
            ContributeResearchBottomSheet(
                repository = repository,
                userId = if (isLoggedIn) userId else null,
                userName = userName,
                userEmail = userEmail,
                initialSurahNumber = surahNumber,
                initialVerseNumber = verseNumber,
                onDismiss = {
                    showContributeSheet = false
                    loadSurahResearch()
                }
            )
        }

        selectedItemForVerify?.let { item ->
            VerificationDialog(
                item = item,
                onDismiss = { selectedItemForVerify = null },
                onSubmitVerdict = { vote, feedback ->
                    scope.launch {
                        repository.reviewResearchContribution(
                            contributionId = item.id,
                            userId = userId,
                            vote = vote,
                            feedback = feedback
                        )
                        selectedItemForVerify = null
                        loadSurahResearch()
                    }
                }
            )
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

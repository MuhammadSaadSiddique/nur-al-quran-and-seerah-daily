package com.asloobulhayat.eternalecho.ui.search

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyListState
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Clear
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Share
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.focus.FocusRequester
import androidx.compose.ui.focus.focusRequester
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalSoftwareKeyboardController
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.SpanStyle
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.buildAnnotatedString
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardCapitalization
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.text.withStyle
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.asloobulhayat.eternalecho.data.DataRepository
import com.asloobulhayat.eternalecho.data.DefaultDataRepository
import com.asloobulhayat.eternalecho.data.Dua
import com.asloobulhayat.eternalecho.data.GlobalConnectionItem
import com.asloobulhayat.eternalecho.data.QuranSearchHit
import com.asloobulhayat.eternalecho.data.Surah
import com.asloobulhayat.eternalecho.data.TextHighlight
import com.asloobulhayat.eternalecho.theme.ArabicVerseFont
import com.asloobulhayat.eternalecho.util.ShareHelper
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.async
import kotlinx.coroutines.coroutineScope
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch

private const val VERSE_PAGE_SIZE = 20
private const val SEARCH_DEBOUNCE_MS = 500L

private enum class SearchFilter(val label: String) {
    ALL("All"),
    VERSES("Ayahs"),
    SURAHS("Surahs"),
    CONNECTIONS("Insights"),
    DUAS("Duas")
}

/** Like runCatching, but lets coroutine cancellation propagate. */
private inline fun <T> attempt(block: () -> T): Result<T> = try {
    Result.success(block())
} catch (e: CancellationException) {
    throw e
} catch (e: Exception) {
    Result.failure(e)
}

/** Case-insensitive occurrences of [query] in [text]. */
internal fun findMatches(text: String, query: String): List<TextHighlight> {
    if (query.isBlank() || text.isEmpty()) return emptyList()
    return Regex(Regex.escape(query), RegexOption.IGNORE_CASE)
        .findAll(text)
        .map { TextHighlight(it.range.first, it.range.last + 1) }
        .toList()
}

private fun highlight(text: String, ranges: List<TextHighlight>, style: SpanStyle): AnnotatedString =
    buildAnnotatedString {
        var cursor = 0
        ranges.sortedBy { it.start }.forEach { range ->
            val start = range.start.coerceIn(cursor, text.length)
            val end = range.end.coerceIn(start, text.length)
            if (start == end) return@forEach
            append(text.substring(cursor, start))
            withStyle(style) { append(text.substring(start, end)) }
            cursor = end
        }
        append(text.substring(cursor))
    }

// ─────────────────────────────────────────────────────────────────────────────
// Word Search results screen
// ─────────────────────────────────────────────────────────────────────────────

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun WordSearchScreen(
    initialQuery: String = "",
    onBackClick: () -> Unit,
    onVerseClick: (surahNumber: Int, verseNumber: Int?) -> Unit = { _, _ -> },
    onDuaClick: (duaId: Int, duaSlug: String) -> Unit = { _, _ -> },
    modifier: Modifier = Modifier,
    repository: DataRepository = remember { DefaultDataRepository() }
) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val keyboardController = LocalSoftwareKeyboardController.current
    val focusRequester = remember { FocusRequester() }

    var query by rememberSaveable { mutableStateOf(initialQuery) }
    var activeQuery by rememberSaveable { mutableStateOf(initialQuery.trim()) }
    var selectedFilter by rememberSaveable { mutableStateOf(SearchFilter.ALL) }
    var retryTick by remember { mutableIntStateOf(0) }

    var allSurahs by remember { mutableStateOf<List<Surah>>(emptyList()) }
    var isLoading by remember { mutableStateOf(false) }
    var verseHits by remember { mutableStateOf<List<QuranSearchHit>>(emptyList()) }
    var verseTotal by remember { mutableIntStateOf(0) }
    var versePage by remember { mutableIntStateOf(0) }
    var verseTotalPages by remember { mutableIntStateOf(0) }
    var verseError by remember { mutableStateOf("") }
    var isLoadingMore by remember { mutableStateOf(false) }
    var connectionResults by remember { mutableStateOf<List<GlobalConnectionItem>>(emptyList()) }
    var duaResults by remember { mutableStateOf<List<Dua>>(emptyList()) }

    LaunchedEffect(Unit) {
        allSurahs = attempt { repository.getSurahs() }.getOrDefault(emptyList())
    }

    // Only pop the keyboard when the user arrives without a query.
    LaunchedEffect(Unit) {
        if (initialQuery.isBlank()) {
            delay(100)
            try { focusRequester.requestFocus() } catch (_: Exception) {}
        }
    }

    // Debounce typing; clearing the field resets immediately.
    LaunchedEffect(query) {
        val trimmed = query.trim()
        if (trimmed == activeQuery) return@LaunchedEffect
        if (trimmed.isNotEmpty()) delay(SEARCH_DEBOUNCE_MS)
        activeQuery = trimmed
    }

    // A new query cancels the previous search, so stale responses never land.
    LaunchedEffect(activeQuery, retryTick) {
        val q = activeQuery
        verseHits = emptyList()
        verseTotal = 0
        versePage = 0
        verseTotalPages = 0
        verseError = ""
        isLoadingMore = false
        connectionResults = emptyList()
        duaResults = emptyList()
        if (q.isBlank()) {
            isLoading = false
            return@LaunchedEffect
        }
        isLoading = true
        coroutineScope {
            val verses = async { attempt { repository.searchQuran(q, 1, VERSE_PAGE_SIZE) } }
            val connections = async {
                attempt { repository.getAllConnections(category = "all", search = q, page = 1, limit = 30).data }
            }
            val duas = async { attempt { repository.getDuas(search = q) } }

            verses.await()
                .onSuccess { page ->
                    verseHits = page.results.distinctBy { it.verseKey }
                    verseTotal = page.totalResults
                    versePage = page.currentPage
                    verseTotalPages = page.totalPages
                }
                .onFailure { verseError = it.message ?: "Unable to search the Quran right now." }
            connectionResults = connections.await().getOrDefault(emptyList()).distinctBy { it.category to it.id }
            duaResults = duas.await().getOrDefault(emptyList()).distinctBy { it.id to it.slug }
        }
        isLoading = false
    }

    fun loadMoreVerses() {
        if (isLoadingMore || versePage >= verseTotalPages) return
        val q = activeQuery
        val nextPage = versePage + 1
        isLoadingMore = true
        scope.launch {
            val result = attempt { repository.searchQuran(q, nextPage, VERSE_PAGE_SIZE) }
            if (q != activeQuery) return@launch
            result
                .onSuccess { page ->
                    verseHits = (verseHits + page.results).distinctBy { it.verseKey }
                    versePage = page.currentPage
                    verseTotalPages = page.totalPages
                }
                .onFailure { verseError = it.message ?: "Unable to load more ayahs." }
            isLoadingMore = false
        }
    }

    val surahResults = remember(allSurahs, activeQuery) {
        if (activeQuery.isBlank()) emptyList() else allSurahs.filter {
            it.number.toString() == activeQuery ||
                it.nameSimple.contains(activeQuery, ignoreCase = true) ||
                it.nameTransliteration.contains(activeQuery, ignoreCase = true) ||
                it.nameTranslated.contains(activeQuery, ignoreCase = true) ||
                it.nameArabic.contains(activeQuery)
        }
    }
    val surahNames = remember(allSurahs) { allSurahs.associate { it.number to it.nameSimple } }

    fun shows(filter: SearchFilter) = selectedFilter == SearchFilter.ALL || selectedFilter == filter
    val shownVerses = if (shows(SearchFilter.VERSES)) verseHits else emptyList()
    val shownSurahs = if (shows(SearchFilter.SURAHS)) surahResults else emptyList()
    val shownConnections = if (shows(SearchFilter.CONNECTIONS)) connectionResults else emptyList()
    val shownDuas = if (shows(SearchFilter.DUAS)) duaResults else emptyList()
    val shownCount = shownVerses.size + shownSurahs.size + shownConnections.size + shownDuas.size
    val anyResults = verseHits.isNotEmpty() || surahResults.isNotEmpty() ||
        connectionResults.isNotEmpty() || duaResults.isNotEmpty()

    fun shareAllShown() {
        val text = ShareHelper.buildSearchResultsShareText(
            query = activeQuery,
            verses = shownVerses,
            totalVerseMatches = if (shownVerses.isEmpty()) 0 else maxOf(verseTotal, shownVerses.size),
            surahNames = surahNames,
            surahs = shownSurahs,
            connections = shownConnections,
            duas = shownDuas
        )
        ShareHelper.shareSearchResults(context, activeQuery, text)
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    OutlinedTextField(
                        value = query,
                        onValueChange = { query = it },
                        placeholder = { Text("Search a word in the Quran…") },
                        modifier = Modifier
                            .fillMaxWidth()
                            .focusRequester(focusRequester),
                        leadingIcon = {
                            Icon(
                                Icons.Default.Search,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        },
                        trailingIcon = {
                            AnimatedVisibility(visible = query.isNotEmpty(), enter = fadeIn(), exit = fadeOut()) {
                                IconButton(onClick = { query = "" }) {
                                    Icon(Icons.Default.Clear, contentDescription = "Clear search")
                                }
                            }
                        },
                        keyboardOptions = KeyboardOptions(
                            imeAction = ImeAction.Search,
                            capitalization = KeyboardCapitalization.None
                        ),
                        keyboardActions = KeyboardActions(
                            onSearch = {
                                keyboardController?.hide()
                                activeQuery = query.trim()
                            }
                        ),
                        singleLine = true,
                        shape = RoundedCornerShape(28.dp),
                        colors = OutlinedTextFieldDefaults.colors(
                            unfocusedBorderColor = MaterialTheme.colorScheme.outline.copy(alpha = 0.4f),
                            focusedBorderColor = MaterialTheme.colorScheme.primary
                        )
                    )
                },
                navigationIcon = {
                    IconButton(onClick = onBackClick) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    AnimatedVisibility(visible = shownCount > 0 && !isLoading) {
                        IconButton(onClick = { shareAllShown() }) {
                            Icon(Icons.Default.Share, contentDescription = "Share search results")
                        }
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface
                )
            )
        },
        modifier = modifier
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            if (activeQuery.isNotBlank() && anyResults) {
                LazyRow(
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    items(SearchFilter.entries) { filter ->
                        val count = when (filter) {
                            SearchFilter.ALL -> null
                            SearchFilter.VERSES -> verseTotal
                            SearchFilter.SURAHS -> surahResults.size
                            SearchFilter.CONNECTIONS -> connectionResults.size
                            SearchFilter.DUAS -> duaResults.size
                        }
                        FilterChip(
                            selected = selectedFilter == filter,
                            onClick = { selectedFilter = filter },
                            label = {
                                Text(if (count != null && count > 0) "${filter.label} ($count)" else filter.label)
                            }
                        )
                    }
                }
            }

            when {
                activeQuery.isBlank() -> EmptySearchPrompt(
                    onSuggestionClick = { suggestion ->
                        keyboardController?.hide()
                        query = suggestion
                        activeQuery = suggestion
                    }
                )

                isLoading -> Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center
                ) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        CircularProgressIndicator()
                        Spacer(modifier = Modifier.height(12.dp))
                        Text(
                            "Searching…",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }

                !anyResults && verseError.isNotBlank() -> SearchErrorState(
                    message = verseError,
                    onRetry = { retryTick++ }
                )

                !anyResults -> NoResultsState(query = activeQuery)

                else -> {
                    val listState = remember(activeQuery, selectedFilter) { LazyListState() }
                    LazyColumn(
                        state = listState,
                        contentPadding = PaddingValues(bottom = 24.dp),
                        modifier = Modifier.fillMaxSize()
                    ) {
                        item(key = "summary") {
                            ResultsSummary(
                                query = activeQuery,
                                verseTotal = verseTotal,
                                otherCount = surahResults.size + connectionResults.size + duaResults.size
                            )
                        }

                        if (shownSurahs.isNotEmpty()) {
                            item(key = "surah_header") {
                                SearchSectionHeader(icon = "📚", title = "Surahs", count = shownSurahs.size)
                            }
                            items(shownSurahs, key = { "surah_${it.number}" }) { surah ->
                                SurahSearchCard(
                                    surah = surah,
                                    query = activeQuery,
                                    onClick = { onVerseClick(surah.number, null) },
                                    onShare = { ShareHelper.shareSurah(context, surah.number, surah.nameSimple) }
                                )
                                ResultDivider()
                            }
                        }

                        if (shows(SearchFilter.VERSES) && (shownVerses.isNotEmpty() || verseError.isNotBlank())) {
                            item(key = "verse_header") {
                                SearchSectionHeader(icon = "📖", title = "Ayahs", count = verseTotal)
                            }
                            items(shownVerses, key = { "verse_${it.verseKey}" }) { hit ->
                                VerseSearchCard(
                                    hit = hit,
                                    surahName = surahNames[hit.surahNumber],
                                    onClick = { onVerseClick(hit.surahNumber, hit.verseNumber) },
                                    onShare = {
                                        ShareHelper.shareVerse(context, hit.verseKey, hit.arabicText, hit.translationText)
                                    }
                                )
                                ResultDivider()
                            }
                            item(key = "verse_footer") {
                                VerseListFooter(
                                    canLoadMore = versePage < verseTotalPages,
                                    isLoadingMore = isLoadingMore,
                                    error = verseError,
                                    onLoadMore = {
                                        verseError = ""
                                        if (verseHits.isEmpty()) retryTick++ else loadMoreVerses()
                                    }
                                )
                            }
                        }

                        if (shownConnections.isNotEmpty()) {
                            item(key = "conn_header") {
                                SearchSectionHeader(
                                    icon = "🔗",
                                    title = "Quranic insights",
                                    count = shownConnections.size
                                )
                            }
                            items(shownConnections, key = { "conn_${it.category}_${it.id}" }) { conn ->
                                ConnectionSearchCard(
                                    item = conn,
                                    query = activeQuery,
                                    onClick = { onVerseClick(conn.surahNumber, conn.verseNumber) },
                                    onShare = {
                                        ShareHelper.shareConnection(context, conn.title, conn.description, conn.category)
                                    }
                                )
                                ResultDivider()
                            }
                        }

                        if (shownDuas.isNotEmpty()) {
                            item(key = "dua_header") {
                                SearchSectionHeader(icon = "🤲", title = "Duas", count = shownDuas.size)
                            }
                            items(shownDuas, key = { "dua_${it.id}_${it.slug}" }) { dua ->
                                DuaSearchCard(
                                    dua = dua,
                                    query = activeQuery,
                                    onClick = { onDuaClick(dua.id, dua.slug) },
                                    onShare = {
                                        ShareHelper.shareDua(
                                            context,
                                            dua.title,
                                            dua.arabicText,
                                            dua.translationEn,
                                            dua.referenceLine(),
                                            dua.slug.ifBlank { dua.id.toString() }
                                        )
                                    }
                                )
                                ResultDivider()
                            }
                        }

                        if (shownCount > 0) {
                            item(key = "share_all") {
                                Box(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(horizontal = 16.dp, vertical = 20.dp),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Button(onClick = { shareAllShown() }, shape = RoundedCornerShape(24.dp)) {
                                        Icon(Icons.Default.Share, contentDescription = null, modifier = Modifier.size(16.dp))
                                        Spacer(modifier = Modifier.width(8.dp))
                                        Text("Share these results")
                                    }
                                }
                            }
                        } else {
                            item(key = "filter_empty") {
                                Text(
                                    "No ${selectedFilter.label.lowercase()} match \"$activeQuery\".",
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                    textAlign = TextAlign.Center,
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(32.dp)
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}

private fun Dua.referenceLine(): String? =
    listOfNotNull(hadithBook, hadithNumber, hadithReference, quranReference).firstOrNull { it.isNotBlank() }

// ─────────────────────────────────────────────────────────────────────────────
// Sub-composables
// ─────────────────────────────────────────────────────────────────────────────

@Composable
private fun ResultDivider() {
    HorizontalDivider(
        modifier = Modifier.padding(horizontal = 16.dp),
        color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.4f)
    )
}

@Composable
private fun EmptySearchPrompt(onSuggestionClick: (String) -> Unit) {
    Box(
        modifier = Modifier.fillMaxSize().padding(32.dp),
        contentAlignment = Alignment.Center
    ) {
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            Text("🔍", fontSize = 48.sp)
            Spacer(modifier = Modifier.height(16.dp))
            Text(
                "Search the Quran",
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold
            )
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                "Type any word in English or Arabic to find every ayah that mentions it, plus matching Surahs, insights and duas.",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = TextAlign.Center
            )
            Spacer(modifier = Modifier.height(24.dp))
            Text(
                "Try searching for:",
                style = MaterialTheme.typography.labelMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            Spacer(modifier = Modifier.height(4.dp))
            LazyRow(
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                contentPadding = PaddingValues(horizontal = 4.dp)
            ) {
                items(WORD_SEARCH_SUGGESTIONS) { s ->
                    SuggestionChip(onClick = { onSuggestionClick(s) }, label = { Text(s) })
                }
            }
        }
    }
}

/** Shared with the Home screen search bar. */
val WORD_SEARCH_SUGGESTIONS = listOf("Mercy", "Patience", "Paradise", "Musa", "رحمة", "Yusuf")

@Composable
private fun SearchErrorState(message: String, onRetry: () -> Unit) {
    Box(
        modifier = Modifier.fillMaxSize().padding(32.dp),
        contentAlignment = Alignment.Center
    ) {
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            Text("⚠️", fontSize = 36.sp)
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                message,
                style = MaterialTheme.typography.bodyMedium,
                textAlign = TextAlign.Center,
                color = MaterialTheme.colorScheme.error
            )
            Spacer(modifier = Modifier.height(16.dp))
            OutlinedButton(onClick = onRetry) { Text("Retry") }
        }
    }
}

@Composable
private fun NoResultsState(query: String) {
    Box(
        modifier = Modifier.fillMaxSize().padding(32.dp),
        contentAlignment = Alignment.Center
    ) {
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            Text("🔎", fontSize = 48.sp)
            Spacer(modifier = Modifier.height(16.dp))
            Text(
                "No results for \"$query\"",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
                textAlign = TextAlign.Center
            )
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                "Try a different spelling, a single word, or a Surah name like \"Yusuf\".",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = TextAlign.Center
            )
        }
    }
}

@Composable
private fun ResultsSummary(query: String, verseTotal: Int, otherCount: Int) {
    val parts = buildList {
        if (verseTotal > 0) add("%,d ayah%s".format(verseTotal, if (verseTotal == 1) "" else "s"))
        if (otherCount > 0) add("$otherCount other match${if (otherCount == 1) "" else "es"}")
    }
    Text(
        text = "${parts.joinToString(" · ")} for \"$query\"",
        style = MaterialTheme.typography.bodySmall,
        color = MaterialTheme.colorScheme.onSurfaceVariant,
        modifier = Modifier.padding(start = 16.dp, end = 16.dp, top = 4.dp)
    )
}

@Composable
private fun SearchSectionHeader(icon: String, title: String, count: Int) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 12.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        Text(icon, fontSize = 16.sp)
        Text(
            title,
            style = MaterialTheme.typography.labelLarge,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.primary
        )
        if (count > 0) {
            Surface(shape = CircleShape, color = MaterialTheme.colorScheme.primaryContainer) {
                Text(
                    "%,d".format(count),
                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp),
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onPrimaryContainer,
                    fontWeight = FontWeight.Bold
                )
            }
        }
    }
}

/** Renders [text] with [highlights] in bold primary colour. */
@Composable
private fun HighlightedText(
    text: String,
    highlights: List<TextHighlight>,
    modifier: Modifier = Modifier,
    style: TextStyle = MaterialTheme.typography.bodyMedium,
    color: Color = Color.Unspecified,
    textAlign: TextAlign? = null,
    maxLines: Int = Int.MAX_VALUE
) {
    val highlightStyle = SpanStyle(
        color = MaterialTheme.colorScheme.primary,
        fontWeight = FontWeight.Bold,
        background = MaterialTheme.colorScheme.primary.copy(alpha = 0.10f)
    )
    val annotated = remember(text, highlights, highlightStyle) { highlight(text, highlights, highlightStyle) }
    Text(
        annotated,
        modifier = modifier,
        style = style,
        color = color,
        textAlign = textAlign,
        maxLines = maxLines,
        overflow = TextOverflow.Ellipsis
    )
}

@Composable
private fun HighlightedText(
    text: String,
    query: String,
    style: TextStyle = MaterialTheme.typography.bodyMedium,
    maxLines: Int = Int.MAX_VALUE
) {
    val matches = remember(text, query) { findMatches(text, query) }
    HighlightedText(text = text, highlights = matches, style = style, maxLines = maxLines)
}

@Composable
private fun ShareIconButton(contentDescription: String, onClick: () -> Unit) {
    IconButton(onClick = onClick, modifier = Modifier.size(32.dp)) {
        Icon(
            Icons.Default.Share,
            contentDescription = contentDescription,
            tint = MaterialTheme.colorScheme.onSurfaceVariant,
            modifier = Modifier.size(18.dp)
        )
    }
}

@Composable
private fun VerseSearchCard(
    hit: QuranSearchHit,
    surahName: String?,
    onClick: () -> Unit,
    onShare: () -> Unit
) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .padding(horizontal = 16.dp, vertical = 12.dp),
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Surface(shape = RoundedCornerShape(6.dp), color = MaterialTheme.colorScheme.primaryContainer) {
                Text(
                    hit.verseKey,
                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp),
                    style = MaterialTheme.typography.labelMedium,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onPrimaryContainer
                )
            }
            Spacer(modifier = Modifier.width(8.dp))
            Text(
                text = surahName?.let { "$it · Ayah ${hit.verseNumber}" } ?: "Surah ${hit.surahNumber} · Ayah ${hit.verseNumber}",
                style = MaterialTheme.typography.labelMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                modifier = Modifier.weight(1f),
                maxLines = 1,
                overflow = TextOverflow.Ellipsis
            )
            ShareIconButton(contentDescription = "Share ayah ${hit.verseKey}", onClick = onShare)
        }
        if (hit.arabicText.isNotBlank()) {
            HighlightedText(
                text = hit.arabicText,
                highlights = hit.arabicHighlights,
                modifier = Modifier.fillMaxWidth(),
                style = TextStyle(fontFamily = ArabicVerseFont, fontSize = 20.sp, lineHeight = 34.sp),
                color = MaterialTheme.colorScheme.onSurface,
                textAlign = TextAlign.Right
            )
        }
        if (hit.translationText.isNotBlank()) {
            HighlightedText(
                text = hit.translationText,
                highlights = hit.translationHighlights,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurface,
                maxLines = 6
            )
            if (hit.translationName.isNotBlank()) {
                Text(
                    "— ${hit.translationName}",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        }
    }
}

@Composable
private fun VerseListFooter(
    canLoadMore: Boolean,
    isLoadingMore: Boolean,
    error: String,
    onLoadMore: () -> Unit
) {
    if (!canLoadMore && error.isBlank() && !isLoadingMore) return
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 12.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(8.dp)
    ) {
        if (error.isNotBlank()) {
            Text(
                error,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.error,
                textAlign = TextAlign.Center
            )
        }
        when {
            isLoadingMore -> CircularProgressIndicator(modifier = Modifier.size(24.dp), strokeWidth = 2.dp)
            error.isNotBlank() -> OutlinedButton(onClick = onLoadMore) { Text("Retry") }
            canLoadMore -> OutlinedButton(onClick = onLoadMore) { Text("Load more ayahs") }
        }
    }
}

@Composable
private fun SurahSearchCard(
    surah: Surah,
    query: String,
    onClick: () -> Unit,
    onShare: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .padding(horizontal = 16.dp, vertical = 12.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(12.dp)
    ) {
        Surface(
            shape = RoundedCornerShape(8.dp),
            color = MaterialTheme.colorScheme.primaryContainer,
            modifier = Modifier.size(40.dp)
        ) {
            Box(contentAlignment = Alignment.Center, modifier = Modifier.fillMaxSize()) {
                Text(
                    surah.number.toString(),
                    style = MaterialTheme.typography.labelMedium,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onPrimaryContainer
                )
            }
        }
        Column(modifier = Modifier.weight(1f)) {
            HighlightedText(
                text = surah.nameSimple,
                query = query,
                style = MaterialTheme.typography.titleSmall.copy(fontWeight = FontWeight.SemiBold)
            )
            HighlightedText(
                text = "${surah.nameTranslated} · ${surah.versesCount} verses · ${surah.revelationPlace}",
                query = query,
                style = MaterialTheme.typography.bodySmall,
                maxLines = 1
            )
        }
        Text(
            surah.nameArabic,
            style = MaterialTheme.typography.titleMedium,
            fontFamily = ArabicVerseFont,
            color = MaterialTheme.colorScheme.onSurface
        )
        ShareIconButton(contentDescription = "Share Surah", onClick = onShare)
    }
}

@Composable
private fun ConnectionSearchCard(
    item: GlobalConnectionItem,
    query: String,
    onClick: () -> Unit,
    onShare: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .padding(horizontal = 16.dp, vertical = 12.dp),
        verticalAlignment = Alignment.Top
    ) {
        Column(modifier = Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(2.dp)) {
            Row(
                horizontalArrangement = Arrangement.spacedBy(6.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                Surface(shape = RoundedCornerShape(4.dp), color = MaterialTheme.colorScheme.secondaryContainer) {
                    Text(
                        item.category.uppercase(),
                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                        style = MaterialTheme.typography.labelSmall,
                        color = MaterialTheme.colorScheme.onSecondaryContainer,
                        fontWeight = FontWeight.Bold
                    )
                }
                Text(
                    "Ayah ${item.surahNumber}:${item.verseNumber}",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            HighlightedText(
                text = item.title,
                query = query,
                style = MaterialTheme.typography.titleSmall.copy(fontWeight = FontWeight.SemiBold)
            )
            HighlightedText(
                text = item.description,
                query = query,
                style = MaterialTheme.typography.bodySmall,
                maxLines = 3
            )
            if (item.verseArabic.isNotBlank()) {
                Text(
                    item.verseArabic,
                    style = MaterialTheme.typography.bodySmall,
                    fontStyle = FontStyle.Italic,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis
                )
            }
        }
        ShareIconButton(contentDescription = "Share insight", onClick = onShare)
    }
}

@Composable
private fun DuaSearchCard(
    dua: Dua,
    query: String,
    onClick: () -> Unit,
    onShare: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .padding(horizontal = 16.dp, vertical = 12.dp),
        verticalAlignment = Alignment.Top
    ) {
        Column(modifier = Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(4.dp)) {
            if (dua.category.isNotBlank()) {
                Surface(shape = RoundedCornerShape(4.dp), color = MaterialTheme.colorScheme.tertiaryContainer) {
                    Text(
                        dua.category,
                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                        style = MaterialTheme.typography.labelSmall,
                        color = MaterialTheme.colorScheme.onTertiaryContainer,
                        fontWeight = FontWeight.Bold
                    )
                }
            }
            HighlightedText(
                text = dua.title,
                query = query,
                style = MaterialTheme.typography.titleSmall.copy(fontWeight = FontWeight.SemiBold)
            )
            if (dua.arabicText.isNotBlank()) {
                Text(
                    dua.arabicText,
                    style = MaterialTheme.typography.bodyMedium,
                    fontFamily = ArabicVerseFont,
                    textAlign = TextAlign.Right,
                    modifier = Modifier.fillMaxWidth(),
                    maxLines = 3,
                    overflow = TextOverflow.Ellipsis
                )
            }
            if (dua.translationEn.isNotBlank()) {
                HighlightedText(
                    text = dua.translationEn,
                    query = query,
                    style = MaterialTheme.typography.bodySmall,
                    maxLines = 3
                )
            }
            dua.referenceLine()?.let { ref ->
                Text(
                    "📚 $ref",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis
                )
            }
        }
        ShareIconButton(contentDescription = "Share Dua", onClick = onShare)
    }
}

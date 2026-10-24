package com.asloobulhayat.eternalecho.ui.patterns

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.FlowRow
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.SpanStyle
import androidx.compose.ui.text.buildAnnotatedString
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.withStyle
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.asloobulhayat.eternalecho.data.DataRepository
import com.asloobulhayat.eternalecho.data.DefaultDataRepository
import com.asloobulhayat.eternalecho.data.QuranPatternCard
import com.asloobulhayat.eternalecho.data.QuranPatternHit
import com.asloobulhayat.eternalecho.ui.components.EchoScreenBackground
import com.asloobulhayat.eternalecho.ui.components.EchoTopBar
import com.asloobulhayat.eternalecho.ui.home.HomeThemePalette
import com.asloobulhayat.eternalecho.ui.home.rememberHomeThemePalette
import kotlinx.coroutines.launch

@OptIn(ExperimentalLayoutApi::class)
@Composable
fun PatternsScreen(
    onBackClick: () -> Unit,
    onOpenVerse: (chapter: Int, verse: Int) -> Unit,
    modifier: Modifier = Modifier,
    repository: DataRepository = remember { DefaultDataRepository() }
) {
    val palette = rememberHomeThemePalette()
    val scope = rememberCoroutineScope()
    var query by remember { mutableStateOf("") }
    var hits by remember { mutableStateOf<List<QuranPatternHit>>(emptyList()) }
    var card by remember { mutableStateOf<QuranPatternCard?>(null) }
    var message by remember { mutableStateOf<String?>(null) }
    var loading by remember { mutableStateOf(false) }
    var details by remember { mutableStateOf(false) }

    fun loadCard(chapter: Int, verse: Int, focus: String) {
        scope.launch {
            loading = true
            message = null
            details = false
            try {
                card = repository.loadQuranPatternCard(chapter, verse, focus)
                if (card == null) {
                    message = "That verse was not found."
                }
            } catch (t: Throwable) {
                card = null
                message = t.message ?: "Could not load the pattern card."
            } finally {
                loading = false
            }
        }
    }

    fun search() {
        val text = query.trim()
        if (text.isEmpty()) return
        scope.launch {
            loading = true
            message = null
            card = null
            details = false
            try {
                val result = repository.searchQuranPatterns(text)
                hits = result.hits
                message = result.message.takeIf { it.isNotBlank() }
                if (result.hits.size == 1) {
                    val hit = result.hits.first()
                    val focus = if (text.matches(Regex("""\d{1,3}\s*[:.]\s*\d{1,3}"""))) "" else text
                    card = repository.loadQuranPatternCard(hit.chapter, hit.verse, focus)
                    if (card == null) {
                        message = "That verse was not found."
                    }
                }
            } catch (t: Throwable) {
                hits = emptyList()
                message = t.message ?: "Search failed."
            } finally {
                loading = false
            }
        }
    }

    Scaffold(
        containerColor = Color.Transparent,
        topBar = {
            EchoTopBar(
                title = "Quran Patterns",
                onBackClick = onBackClick,
                navigationIcon = Icons.AutoMirrored.Filled.ArrowBack
            )
        }
    ) { padding ->
        EchoScreenBackground {
            Column(
                modifier = modifier
                    .fillMaxSize()
                    .padding(padding)
                    .verticalScroll(rememberScrollState())
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                Text(
                    text = "Search a word or verse.",
                    color = palette.textMuted,
                    style = MaterialTheme.typography.bodyMedium
                )
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    OutlinedTextField(
                        value = query,
                        onValueChange = { query = it },
                        modifier = Modifier.weight(1f),
                        singleLine = true,
                        placeholder = { Text("86:1") },
                        keyboardOptions = KeyboardOptions(imeAction = ImeAction.Search),
                        keyboardActions = KeyboardActions(onSearch = { search() })
                    )
                    Button(onClick = { search() }, enabled = !loading) {
                        Text("Search")
                    }
                }

                if (loading) {
                    CircularProgressIndicator(color = palette.primaryAccent)
                }

                message?.let {
                    Text(text = it, color = palette.textSecondary)
                }

                if (hits.size > 1) {
                    FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        hits.forEach { hit ->
                            TextButton(onClick = {
                                loadCard(hit.chapter, hit.verse, query.trim())
                            }) {
                                Text("${hit.verseKey} ${hit.surahName}")
                            }
                        }
                    }
                }

                card?.let { pattern ->
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(20.dp),
                        colors = CardDefaults.cardColors(containerColor = palette.cardBg),
                        border = BorderStroke(1.dp, palette.cardBorder)
                    ) {
                        Column(
                            modifier = Modifier.padding(18.dp),
                            verticalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            Text(text = pattern.gloss, color = palette.textSecondary)
                            Text(
                                text = "Verse ${pattern.verseKey}",
                                color = palette.textPrimary,
                                fontWeight = FontWeight.Black,
                                fontSize = 28.sp
                            )
                            Text(
                                text = pattern.arabicTerm,
                                color = palette.goldAccent,
                                fontSize = 32.sp,
                                textAlign = TextAlign.Center,
                                modifier = Modifier.fillMaxWidth()
                            )
                            PatternLine("Ring", pattern.ringNote, palette)
                            pattern.ringParts.forEach { part ->
                                Text(
                                    text = if (part.center) {
                                        "${part.number}  ${part.text}  · Center"
                                    } else {
                                        "${part.number}" + (part.pair?.let { " · $it" } ?: "") + "  ${part.text}"
                                    },
                                    color = if (part.center) palette.goldAccent else palette.textSecondary,
                                    fontWeight = if (part.center) FontWeight.Bold else FontWeight.Normal
                                )
                            }
                            PatternLine("Math", pattern.math.summary, palette)
                            PatternLine("Linguistic", pattern.linguisticNote, palette)
                            if (pattern.topics.isNotEmpty()) {
                                FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                    pattern.topics.forEach { topic ->
                                        Surface(
                                            shape = RoundedCornerShape(50),
                                            color = palette.primaryButtonBg
                                        ) {
                                            Text(
                                                text = topic,
                                                color = palette.primaryButtonContent,
                                                modifier = Modifier.padding(horizontal = 12.dp, vertical = 6.dp),
                                                fontWeight = FontWeight.Bold
                                            )
                                        }
                                    }
                                }
                            }
                            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                TextButton(onClick = { details = !details }) { Text("Details") }
                                TextButton(onClick = { onOpenVerse(pattern.chapter, pattern.verse) }) {
                                    Text("Open verse")
                                }
                            }
                            if (details) {
                                if (pattern.translation.isNotBlank()) {
                                    Text(text = pattern.translation, color = palette.textSecondary)
                                }
                                Text(text = pattern.arabicText, color = palette.textPrimary)
                                Text(
                                    text = "Letters in this verse: ${pattern.math.lettersInVerse}.",
                                    color = palette.textMuted
                                )
                                pattern.math.focusCountInSurah?.let { count ->
                                    Text(
                                        text = "This focus word appears $count time(s) in the stored text of the surah.",
                                        color = palette.textMuted
                                    )
                                }
                                pattern.science.forEach { link ->
                                    Text(
                                        text = "${link.title} — ${link.description}",
                                        color = palette.textSecondary
                                    )
                                }
                            }
                        }
                    }
                    Text(
                        text = pattern.disclaimer,
                        color = palette.textMuted,
                        style = MaterialTheme.typography.bodySmall,
                        modifier = Modifier.fillMaxWidth(),
                        textAlign = TextAlign.Center
                    )
                }
                Spacer(modifier = Modifier.height(12.dp))
            }
        }
    }
}

@Composable
private fun PatternLine(label: String, body: String, palette: HomeThemePalette) {
    Text(
        text = buildAnnotatedString {
            withStyle(SpanStyle(color = palette.primaryAccent, fontWeight = FontWeight.Bold)) {
                append(label)
            }
            withStyle(SpanStyle(color = palette.textPrimary)) {
                append(" — $body")
            }
        }
    )
}

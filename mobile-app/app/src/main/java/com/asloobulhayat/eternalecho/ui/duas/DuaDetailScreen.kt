package com.asloobulhayat.eternalecho.ui.duas

import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.speech.tts.TextToSpeech
import android.widget.Toast
import androidx.compose.animation.*
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.ArrowForward
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.hapticfeedback.HapticFeedbackType
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalHapticFeedback
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.asloobulhayat.eternalecho.data.DataRepository
import com.asloobulhayat.eternalecho.data.DefaultDataRepository
import com.asloobulhayat.eternalecho.data.Dua
import com.asloobulhayat.eternalecho.util.ShareHelper
import kotlinx.coroutines.launch
import java.util.Locale

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DuaDetailScreen(
    duaId: Int = 0,
    duaSlug: String = "",
    onBackClick: () -> Unit,
    onNavigateToDua: (Int, String) -> Unit = { _, _ -> },
    onSurahClick: (Int, Int?) -> Unit = { _, _ -> },
    modifier: Modifier = Modifier,
    repository: DataRepository = remember { DefaultDataRepository() }
) {
    val context = LocalContext.current
    val haptic = LocalHapticFeedback.current
    val scope = rememberCoroutineScope()

    var currentDua by remember { mutableStateOf<Dua?>(null) }
    var allDuas by remember { mutableStateOf<List<Dua>>(emptyList()) }
    var isLoading by remember { mutableStateOf(true) }
    var errorMessage by remember { mutableStateOf<String?>(null) }
    var language by remember { mutableStateOf("en") } // "en" or "ur"
    var tasbihCount by remember { mutableIntStateOf(0) }

    // TTS Setup
    var tts by remember { mutableStateOf<TextToSpeech?>(null) }
    var isTtsPlaying by remember { mutableStateOf(false) }

    DisposableEffect(context) {
        var speechEngine: TextToSpeech? = null
        speechEngine = TextToSpeech(context) { status ->
            if (status == TextToSpeech.SUCCESS) {
                // Try Arabic locale first, default to available voice
                val arLocale = Locale.forLanguageTag("ar")
                val langResult = speechEngine?.setLanguage(arLocale)
                if (langResult == TextToSpeech.LANG_MISSING_DATA || langResult == TextToSpeech.LANG_NOT_SUPPORTED) {
                    speechEngine?.setLanguage(Locale.getDefault())
                }
            }
        }
        tts = speechEngine
        onDispose {
            speechEngine.stop()
            speechEngine.shutdown()
        }
    }

    fun playArabicSpeech(text: String) {
        tts?.let { engine ->
            if (isTtsPlaying) {
                engine.stop()
                isTtsPlaying = false
            } else {
                isTtsPlaying = true
                engine.speak(text, TextToSpeech.QUEUE_FLUSH, null, "DuaSpeech")
            }
        }
    }

    fun loadData() {
        scope.launch {
            isLoading = true
            errorMessage = null
            try {
                // Load all duas for prev/next paging
                val list = repository.getDuas()
                allDuas = list

                // Find the requested dua
                val matched = if (duaId > 0) {
                    repository.getDua(id = duaId) ?: list.find { it.id == duaId }
                } else if (duaSlug.isNotBlank()) {
                    repository.getDua(slug = duaSlug) ?: list.find { it.slug == duaSlug }
                } else {
                    list.firstOrNull()
                }

                currentDua = matched ?: list.firstOrNull()
                tasbihCount = 0
            } catch (t: Throwable) {
                errorMessage = t.message ?: "Failed to load Dua details."
            } finally {
                isLoading = false
            }
        }
    }

    LaunchedEffect(duaId, duaSlug) {
        loadData()
    }

    val prevDua = remember(currentDua, allDuas) {
        currentDua?.let { curr ->
            val idx = allDuas.indexOfFirst { it.id == curr.id }
            if (idx > 0) allDuas[idx - 1] else null
        }
    }

    val nextDua = remember(currentDua, allDuas) {
        currentDua?.let { curr ->
            val idx = allDuas.indexOfFirst { it.id == curr.id }
            if (idx in 0 until allDuas.lastIndex) allDuas[idx + 1] else null
        }
    }

    fun copyDuaToClipboard(dua: Dua) {
        val trans = if (language == "ur" && !dua.translationUr.isNullOrBlank()) {
            dua.translationUr
        } else {
            dua.translationEn
        }
        val ref = dua.quranReference ?: dua.hadithReference ?: ""
        val textToCopy = buildString {
            appendLine(dua.title)
            appendLine()
            appendLine(dua.arabicText)
            if (!dua.transliteration.isNullOrBlank()) {
                appendLine()
                appendLine(dua.transliteration)
            }
            appendLine()
            appendLine(trans)
            if (ref.isNotBlank()) {
                appendLine()
                appendLine("Reference: $ref")
            }
        }

        val clipboard = context.getSystemService(Context.CLIPBOARD_SERVICE) as ClipboardManager
        val clip = ClipData.newPlainText("Dua", textToCopy)
        clipboard.setPrimaryClip(clip)
        Toast.makeText(context, "Dua copied to clipboard", Toast.LENGTH_SHORT).show()
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = currentDua?.title ?: "Dua Detail",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        maxLines = 1
                    )
                },
                navigationIcon = {
                    IconButton(onClick = onBackClick) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    currentDua?.let { dua ->
                        IconButton(onClick = { playArabicSpeech(dua.arabicText) }) {
                            Icon(
                                imageVector = if (isTtsPlaying) Icons.Default.Close else Icons.Default.PlayArrow,
                                contentDescription = "Listen Recitation",
                                tint = MaterialTheme.colorScheme.primary
                            )
                        }
                        IconButton(onClick = { copyDuaToClipboard(dua) }) {
                            Icon(Icons.Default.ContentCopy, contentDescription = "Copy Dua")
                        }
                        IconButton(onClick = {
                            val trans = if (language == "ur" && !dua.translationUr.isNullOrBlank()) {
                                dua.translationUr
                            } else {
                                dua.translationEn
                            }
                            ShareHelper.shareDua(
                                context = context,
                                title = dua.title,
                                arabicText = dua.arabicText,
                                translation = trans,
                                reference = dua.quranReference ?: dua.hadithReference
                            )
                        }) {
                            Icon(Icons.Default.Share, contentDescription = "Share Dua")
                        }
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
        ) {
            if (isLoading) {
                Box(
                    modifier = Modifier.fillMaxSize(),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator()
                }
            } else if (errorMessage != null || currentDua == null) {
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(20.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.errorContainer)
                ) {
                    Column(
                        modifier = Modifier.padding(20.dp),
                        verticalArrangement = Arrangement.spacedBy(10.dp),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Text(
                            text = errorMessage ?: "Dua not found.",
                            color = MaterialTheme.colorScheme.onErrorContainer,
                            textAlign = TextAlign.Center
                        )
                        Button(onClick = { loadData() }) {
                            Text("Retry")
                        }
                    }
                }
            } else {
                val dua = currentDua!!
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .verticalScroll(rememberScrollState())
                        .padding(horizontal = 16.dp, vertical = 12.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    // Header Badges & Language Switcher
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Row(
                            horizontalArrangement = Arrangement.spacedBy(6.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
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
                                        text = "📖 Quran",
                                        style = MaterialTheme.typography.labelSmall,
                                        fontWeight = FontWeight.Bold,
                                        color = Color(0xFF137333),
                                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                                    )
                                }
                            }

                            if (dua.sourceType == "hadith" || dua.sourceType == "both") {
                                Surface(
                                    color = Color(0xFFFEF7E0),
                                    shape = RoundedCornerShape(8.dp)
                                ) {
                                    Text(
                                        text = "📜 Hadith",
                                        style = MaterialTheme.typography.labelSmall,
                                        fontWeight = FontWeight.Bold,
                                        color = Color(0xFFB06000),
                                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                                    )
                                }
                            }

                            if (!dua.hadithGrading.isNullOrBlank()) {
                                Surface(
                                    color = MaterialTheme.colorScheme.surfaceVariant,
                                    shape = RoundedCornerShape(8.dp)
                                ) {
                                    Text(
                                        text = dua.hadithGrading,
                                        style = MaterialTheme.typography.labelSmall,
                                        fontWeight = FontWeight.Medium,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 4.dp)
                                    )
                                }
                            }
                        }

                        // Language Toggle: English / Urdu
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
                                    .clickable { language = "en" }
                                    .padding(horizontal = 10.dp, vertical = 4.dp)
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
                                    .clickable { language = "ur" }
                                    .padding(horizontal = 10.dp, vertical = 4.dp)
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

                    // Title & Description Card
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.45f))
                    ) {
                        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                            Text(
                                text = dua.title,
                                style = MaterialTheme.typography.titleLarge,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onSurface
                            )
                            if (!dua.description.isNullOrBlank()) {
                                Text(
                                    text = dua.description,
                                    style = MaterialTheme.typography.bodyMedium,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                    lineHeight = 20.sp
                                )
                            }
                        }
                    }

                    // Sacred Arabic Calligraphy Card
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(20.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp),
                        border = BorderStroke(1.5.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.35f))
                    ) {
                        Column(
                            modifier = Modifier.padding(20.dp),
                            verticalArrangement = Arrangement.spacedBy(14.dp)
                        ) {
                            Text(
                                text = dua.arabicText,
                                fontSize = 28.sp,
                                fontWeight = FontWeight.Bold,
                                textAlign = TextAlign.End,
                                lineHeight = 48.sp,
                                modifier = Modifier.fillMaxWidth(),
                                color = MaterialTheme.colorScheme.onSurface
                            )

                            if (!dua.transliteration.isNullOrBlank()) {
                                HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))
                                Column(verticalArrangement = Arrangement.spacedBy(4.dp)) {
                                    Text(
                                        text = "Transliteration",
                                        style = MaterialTheme.typography.labelSmall,
                                        fontWeight = FontWeight.Bold,
                                        color = MaterialTheme.colorScheme.primary
                                    )
                                    Text(
                                        text = dua.transliteration,
                                        style = MaterialTheme.typography.bodyMedium,
                                        fontStyle = FontStyle.Italic,
                                        lineHeight = 22.sp,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                }
                            }
                        }
                    }

                    // Translation Card
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.25f)),
                        border = BorderStroke(1.dp, MaterialTheme.colorScheme.primaryContainer)
                    ) {
                        Column(
                            modifier = Modifier.padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(6.dp)
                        ) {
                            Text(
                                text = if (language == "ur") "ترجمہ" else "Translation",
                                style = MaterialTheme.typography.labelMedium,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.primary
                            )
                            Text(
                                text = if (language == "ur" && !dua.translationUr.isNullOrBlank()) {
                                    dua.translationUr
                                } else {
                                    dua.translationEn
                                },
                                style = MaterialTheme.typography.bodyLarge,
                                fontWeight = FontWeight.Medium,
                                textAlign = if (language == "ur") TextAlign.End else TextAlign.Start,
                                lineHeight = 26.sp,
                                color = MaterialTheme.colorScheme.onSurface
                            )
                        }
                    }

                    // Word-by-Word Lexical Analysis
                    if (!dua.wordByWord.isNullOrEmpty()) {
                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(16.dp),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                            border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant)
                        ) {
                            Column(
                                modifier = Modifier.padding(16.dp),
                                verticalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                Row(
                                    modifier = Modifier.fillMaxWidth(),
                                    horizontalArrangement = Arrangement.SpaceBetween,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Text(
                                        text = "🔍 Word-by-Word Breakdown",
                                        style = MaterialTheme.typography.titleSmall,
                                        fontWeight = FontWeight.Bold,
                                        color = MaterialTheme.colorScheme.primary
                                    )
                                    Text(
                                        text = "${dua.wordByWord.size} Words",
                                        style = MaterialTheme.typography.labelSmall,
                                        color = MaterialTheme.colorScheme.outline
                                    )
                                }

                                dua.wordByWord.chunked(3).forEach { chunk ->
                                    Row(
                                        modifier = Modifier.fillMaxWidth(),
                                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                                    ) {
                                        chunk.forEach { word ->
                                            Card(
                                                modifier = Modifier.weight(1f),
                                                shape = RoundedCornerShape(10.dp),
                                                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)),
                                                border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant)
                                            ) {
                                                Column(
                                                    modifier = Modifier.padding(8.dp),
                                                    horizontalAlignment = Alignment.CenterHorizontally,
                                                    verticalArrangement = Arrangement.spacedBy(3.dp)
                                                ) {
                                                    Text(
                                                        text = word.arabic,
                                                        fontSize = 17.sp,
                                                        fontWeight = FontWeight.Bold,
                                                        textAlign = TextAlign.Center
                                                    )
                                                    Text(
                                                        text = word.transliteration,
                                                        fontSize = 9.sp,
                                                        color = MaterialTheme.colorScheme.outline,
                                                        fontStyle = FontStyle.Italic,
                                                        textAlign = TextAlign.Center
                                                    )
                                                    Text(
                                                        text = if (language == "ur" && !word.meaningUr.isNullOrBlank()) word.meaningUr else word.meaningEn,
                                                        fontSize = 10.sp,
                                                        fontWeight = FontWeight.SemiBold,
                                                        color = MaterialTheme.colorScheme.primary,
                                                        textAlign = TextAlign.Center,
                                                        lineHeight = 13.sp
                                                    )
                                                }
                                            }
                                        }
                                        repeat(3 - chunk.size) {
                                            Spacer(modifier = Modifier.weight(1f))
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Spiritual Insights & Theological Wisdom
                    if (!dua.meaningExplanation.isNullOrBlank() || !dua.benefitsAndVirtues.isNullOrBlank() || !dua.whenToRecite.isNullOrBlank()) {
                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(16.dp),
                            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f)),
                            border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant)
                        ) {
                            Column(
                                modifier = Modifier.padding(16.dp),
                                verticalArrangement = Arrangement.spacedBy(12.dp)
                            ) {
                                Text(
                                    text = "💡 Spiritual Insights & Divine Wisdom",
                                    style = MaterialTheme.typography.titleSmall,
                                    fontWeight = FontWeight.Bold,
                                    color = MaterialTheme.colorScheme.onSurface
                                )

                                if (!dua.meaningExplanation.isNullOrBlank()) {
                                    Column(verticalArrangement = Arrangement.spacedBy(2.dp)) {
                                        Text(
                                            text = "Deep Meaning & Context",
                                            style = MaterialTheme.typography.labelSmall,
                                            fontWeight = FontWeight.Bold,
                                            color = MaterialTheme.colorScheme.primary
                                        )
                                        Text(
                                            text = dua.meaningExplanation,
                                            style = MaterialTheme.typography.bodySmall,
                                            lineHeight = 19.sp,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }
                                }

                                if (!dua.benefitsAndVirtues.isNullOrBlank()) {
                                    Column(verticalArrangement = Arrangement.spacedBy(2.dp)) {
                                        Text(
                                            text = "Virtues & Divine Blessings",
                                            style = MaterialTheme.typography.labelSmall,
                                            fontWeight = FontWeight.Bold,
                                            color = MaterialTheme.colorScheme.primary
                                        )
                                        Text(
                                            text = dua.benefitsAndVirtues,
                                            style = MaterialTheme.typography.bodySmall,
                                            lineHeight = 19.sp,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }
                                }

                                if (!dua.whenToRecite.isNullOrBlank()) {
                                    Column(verticalArrangement = Arrangement.spacedBy(2.dp)) {
                                        Text(
                                            text = "Recommended Timings & Occasions",
                                            style = MaterialTheme.typography.labelSmall,
                                            fontWeight = FontWeight.Bold,
                                            color = MaterialTheme.colorScheme.primary
                                        )
                                        Text(
                                            text = dua.whenToRecite,
                                            style = MaterialTheme.typography.bodySmall,
                                            lineHeight = 19.sp,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }
                                }
                            }
                        }
                    }

                    // Interactive Sunnah Tasbih Counter
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.35f)),
                        border = BorderStroke(1.dp, MaterialTheme.colorScheme.primary.copy(alpha = 0.4f))
                    ) {
                        Column(
                            modifier = Modifier.padding(16.dp),
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text(
                                    text = "📿 Sunnah Tasbih Counter",
                                    style = MaterialTheme.typography.titleSmall,
                                    fontWeight = FontWeight.Bold,
                                    color = MaterialTheme.colorScheme.primary
                                )
                                Text(
                                    text = "Target: ${dua.repeatCount}x",
                                    style = MaterialTheme.typography.labelMedium,
                                    fontWeight = FontWeight.SemiBold,
                                    color = MaterialTheme.colorScheme.outline
                                )
                            }

                            // Counter button
                            Row(
                                horizontalArrangement = Arrangement.spacedBy(16.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Button(
                                    onClick = {
                                        haptic.performHapticFeedback(HapticFeedbackType.LongPress)
                                        if (tasbihCount < dua.repeatCount) {
                                            tasbihCount++
                                        }
                                    },
                                    shape = RoundedCornerShape(16.dp),
                                    colors = ButtonDefaults.buttonColors(
                                        containerColor = if (tasbihCount >= dua.repeatCount) Color(0xFF137333) else MaterialTheme.colorScheme.primary
                                    ),
                                    contentPadding = PaddingValues(horizontal = 24.dp, vertical = 12.dp)
                                ) {
                                    Icon(Icons.Default.TouchApp, contentDescription = null, modifier = Modifier.size(20.dp))
                                    Spacer(modifier = Modifier.width(8.dp))
                                    Text(
                                        text = "$tasbihCount / ${dua.repeatCount}",
                                        fontSize = 18.sp,
                                        fontWeight = FontWeight.Bold
                                    )
                                }

                                if (tasbihCount > 0) {
                                    OutlinedButton(
                                        onClick = {
                                            haptic.performHapticFeedback(HapticFeedbackType.TextHandleMove)
                                            tasbihCount = 0
                                        },
                                        shape = CircleShape,
                                        contentPadding = PaddingValues(0.dp),
                                        modifier = Modifier.size(44.dp)
                                    ) {
                                        Icon(Icons.Default.Refresh, contentDescription = "Reset", modifier = Modifier.size(18.dp))
                                    }
                                }
                            }

                            // Progress Bar
                            val progress = if (dua.repeatCount > 0) (tasbihCount.toFloat() / dua.repeatCount.toFloat()).coerceIn(0f, 1f) else 0f
                            LinearProgressIndicator(
                                progress = { progress },
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .height(6.dp)
                                    .clip(RoundedCornerShape(3.dp)),
                                color = if (tasbihCount >= dua.repeatCount) Color(0xFF137333) else MaterialTheme.colorScheme.primary,
                                trackColor = MaterialTheme.colorScheme.surfaceVariant
                            )

                            if (tasbihCount >= dua.repeatCount) {
                                Text(
                                    text = "✨ MashaAllah! Supplication goal completed.",
                                    style = MaterialTheme.typography.labelMedium,
                                    fontWeight = FontWeight.Bold,
                                    color = Color(0xFF137333)
                                )
                            }
                        }
                    }

                    // Scriptural Reference & Verification Card
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant)
                    ) {
                        Column(
                            modifier = Modifier.padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            Text(
                                text = "📜 Scriptural Citations & Authority",
                                style = MaterialTheme.typography.labelLarge,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onSurface
                            )

                            if (!dua.quranReference.isNullOrBlank()) {
                                Row(
                                    modifier = Modifier.fillMaxWidth(),
                                    horizontalArrangement = Arrangement.SpaceBetween,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Text(
                                        text = "📖 ${dua.quranReference}",
                                        style = MaterialTheme.typography.bodyMedium,
                                        fontWeight = FontWeight.SemiBold,
                                        color = MaterialTheme.colorScheme.primary
                                    )
                                    if (dua.surahNumber != null && dua.surahNumber > 0) {
                                        TextButton(onClick = {
                                            onSurahClick(dua.surahNumber, dua.verseNumber)
                                        }) {
                                            Text("Read Ayah →", fontSize = 11.sp, fontWeight = FontWeight.Bold)
                                        }
                                    }
                                }
                            }

                            if (!dua.hadithReference.isNullOrBlank()) {
                                Text(
                                    text = "📜 ${dua.hadithReference}",
                                    style = MaterialTheme.typography.bodyMedium,
                                    fontWeight = FontWeight.SemiBold,
                                    color = Color(0xFFB06000)
                                )
                            }
                        }
                    }

                    // Navigation Controls: Previous / Next Dua
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 8.dp),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        if (prevDua != null) {
                            OutlinedButton(
                                onClick = { onNavigateToDua(prevDua.id, prevDua.slug) },
                                shape = RoundedCornerShape(12.dp)
                            ) {
                                Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = null, modifier = Modifier.size(16.dp))
                                Spacer(modifier = Modifier.width(6.dp))
                                Text("Previous", fontSize = 12.sp)
                            }
                        } else {
                            Spacer(modifier = Modifier.width(8.dp))
                        }

                        if (nextDua != null) {
                            Button(
                                onClick = { onNavigateToDua(nextDua.id, nextDua.slug) },
                                shape = RoundedCornerShape(12.dp)
                            ) {
                                Text("Next Dua", fontSize = 12.sp)
                                Spacer(modifier = Modifier.width(6.dp))
                                Icon(Icons.AutoMirrored.Filled.ArrowForward, contentDescription = null, modifier = Modifier.size(16.dp))
                            }
                        }
                    }
                }
            }
        }
    }
}

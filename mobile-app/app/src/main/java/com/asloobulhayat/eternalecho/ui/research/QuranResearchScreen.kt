package com.asloobulhayat.eternalecho.ui.research

import android.content.Intent
import android.net.Uri
import androidx.compose.animation.*
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.asloobulhayat.eternalecho.data.DataRepository
import com.asloobulhayat.eternalecho.data.DefaultDataRepository
import com.asloobulhayat.eternalecho.data.ResearchContribution
import com.asloobulhayat.eternalecho.security.SecurePreferences
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun QuranResearchScreen(
    onBackClick: () -> Unit,
    onAuthClick: () -> Unit = {},
    modifier: Modifier = Modifier,
    repository: DataRepository = remember { DefaultDataRepository() }
) {
    val context = LocalContext.current
    val prefs = remember { SecurePreferences.getInstance(context) }
    val userId = remember { prefs.getInt("user_id", -1) }
    val isLoggedIn = userId > 0

    var showContributeSheet by remember { mutableStateOf(false) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Quran Research & Videos", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onBackClick) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(onClick = { showContributeSheet = true }) {
                        Icon(Icons.Default.Add, contentDescription = "Contribute")
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
                text = { Text("Add Video / Research") },
                containerColor = MaterialTheme.colorScheme.primary,
                contentColor = MaterialTheme.colorScheme.onPrimary
            )
        }
    ) { paddingValues ->
        QuranResearchContent(
            repository = repository,
            isLoggedIn = isLoggedIn,
            userId = if (isLoggedIn) userId else null,
            onAuthClick = onAuthClick,
            modifier = modifier
                .fillMaxSize()
                .padding(paddingValues)
        )

        if (showContributeSheet) {
            ContributeResearchBottomSheet(
                repository = repository,
                userId = if (isLoggedIn) userId else null,
                userName = prefs.getString("user_name", "") ?: "",
                userEmail = prefs.getString("user_email", "") ?: "",
                onDismiss = { showContributeSheet = false }
            )
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun QuranResearchContent(
    repository: DataRepository,
    isLoggedIn: Boolean,
    userId: Int?,
    onAuthClick: () -> Unit = {},
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()

    var selectedTab by remember { mutableStateOf("published") } // "published" vs "pending"
    var selectedCategory by remember { mutableStateOf("all") }
    var searchQuery by remember { mutableStateOf("") }

    var isLoading by remember { mutableStateOf(true) }
    var itemsList by remember { mutableStateOf<List<ResearchContribution>>(emptyList()) }
    var errorMessage by remember { mutableStateOf<String?>(null) }

    var selectedItemForVerify by remember { mutableStateOf<ResearchContribution?>(null) }
    var snackbarMessage by remember { mutableStateOf<String?>(null) }

    fun loadData() {
        scope.launch {
            isLoading = true
            errorMessage = null
            try {
                val data = repository.getResearchContributions(
                    tab = selectedTab,
                    category = selectedCategory,
                    search = searchQuery,
                    page = 1,
                    userId = userId
                )
                itemsList = data
            } catch (e: Exception) {
                errorMessage = e.message ?: "Failed to load contributions."
            } finally {
                isLoading = false
            }
        }
    }

    LaunchedEffect(selectedTab, selectedCategory) {
        loadData()
    }

    Column(modifier = modifier.fillMaxSize()) {
        // Top Switch: Published vs Pending
        Surface(
            color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
            modifier = Modifier.fillMaxWidth()
        ) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                FilterChip(
                    selected = selectedTab == "published",
                    onClick = { selectedTab = "published" },
                    label = { Text("🌟 Published") },
                    colors = FilterChipDefaults.filterChipColors(
                        selectedContainerColor = MaterialTheme.colorScheme.primary,
                        selectedLabelColor = MaterialTheme.colorScheme.onPrimary
                    ),
                    modifier = Modifier.weight(1f)
                )

                FilterChip(
                    selected = selectedTab == "pending",
                    onClick = {
                        if (!isLoggedIn) {
                            onAuthClick()
                        } else {
                            selectedTab = "pending"
                        }
                    },
                    label = {
                        Text(if (isLoggedIn) "⏳ Pending Review" else "🔒 Pending (Login)")
                    },
                    colors = FilterChipDefaults.filterChipColors(
                        selectedContainerColor = MaterialTheme.colorScheme.tertiary,
                        selectedLabelColor = MaterialTheme.colorScheme.onTertiary
                    ),
                    modifier = Modifier.weight(1f)
                )
            }
        }

        // Category Filter Chips (Including "video" category)
        val categories = listOf(
            "all" to "🌐 All",
            "video" to "🎬 Videos",
            "science" to "🔬 Science",
            "seerah" to "🕌 Seerah",
            "hadith" to "📚 Hadith",
            "history" to "🏛️ History"
        )

        LazyRow(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 8.dp),
            horizontalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            items(categories) { (catKey, catLabel) ->
                FilterChip(
                    selected = selectedCategory == catKey,
                    onClick = { selectedCategory = catKey },
                    label = { Text(catLabel, fontSize = 12.sp) }
                )
            }
        }

        // Search bar
        OutlinedTextField(
            value = searchQuery,
            onValueChange = { searchQuery = it },
            placeholder = { Text("Search title, lecture, notes...", fontSize = 13.sp) },
            leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search") },
            trailingIcon = {
                if (searchQuery.isNotBlank()) {
                    IconButton(onClick = {
                        searchQuery = ""
                        loadData()
                    }) {
                        Icon(Icons.Default.Clear, contentDescription = "Clear")
                    }
                } else {
                    IconButton(onClick = { loadData() }) {
                        Icon(Icons.Default.ArrowForward, contentDescription = "Execute Search")
                    }
                }
            },
            singleLine = true,
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 4.dp),
            shape = RoundedCornerShape(12.dp)
        )

        // Pending banner explaining 5 approvals rule
        if (selectedTab == "pending") {
            Card(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 6.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.tertiaryContainer.copy(alpha = 0.5f))
            ) {
                Row(
                    modifier = Modifier.padding(12.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    Text("⚖️", fontSize = 20.sp)
                    Text(
                        text = "5 community approvals will publish an item. Downvotes return contested items to pending for revision.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onTertiaryContainer
                    )
                }
            }
        }

        // Content State
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .weight(1f)
        ) {
            if (isLoading) {
                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
            } else if (errorMessage != null) {
                Column(
                    modifier = Modifier
                        .align(Alignment.Center)
                        .padding(16.dp),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Text("Error: $errorMessage", color = MaterialTheme.colorScheme.error, textAlign = TextAlign.Center)
                    Spacer(modifier = Modifier.height(8.dp))
                    Button(onClick = { loadData() }) {
                        Text("Retry")
                    }
                }
            } else if (itemsList.isEmpty()) {
                Column(
                    modifier = Modifier
                        .align(Alignment.Center)
                        .padding(24.dp),
                    horizontalAlignment = Alignment.CenterHorizontally
                ) {
                    Text("🎬", fontSize = 42.sp)
                    Spacer(modifier = Modifier.height(8.dp))
                    Text(
                        text = if (selectedTab == "pending") "No pending items found." else "No research contributions found.",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = "Be the first to contribute an authentic video or research connection!",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        textAlign = TextAlign.Center
                    )
                }
            } else {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(14.dp)
                ) {
                    items(itemsList, key = { it.id }) { item ->
                        ContributionCard(
                            item = item,
                            isLoggedIn = isLoggedIn,
                            onWatchVideo = { url ->
                                try {
                                    val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url))
                                    context.startActivity(intent)
                                } catch (e: Exception) {
                                    snackbarMessage = "Unable to open video link."
                                }
                            },
                            onVerifyClick = {
                                if (isLoggedIn) {
                                    selectedItemForVerify = item
                                } else {
                                    onAuthClick()
                                }
                            }
                        )
                    }
                }
            }
        }
    }

    // Verification Dialog
    selectedItemForVerify?.let { item ->
        VerificationDialog(
            item = item,
            onDismiss = { selectedItemForVerify = null },
            onSubmitVerdict = { vote, feedback ->
                scope.launch {
                    val res = repository.reviewResearchContribution(
                        contributionId = item.id,
                        userId = userId ?: 0,
                        vote = vote,
                        feedback = feedback
                    )
                    selectedItemForVerify = null
                    loadData()
                }
            }
        )
    }
}

@Composable
fun ContributionCard(
    item: ResearchContribution,
    isLoggedIn: Boolean,
    onWatchVideo: (String) -> Unit,
    onVerifyClick: () -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.6f)),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.4f))
    ) {
        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            // Header Row: Surah coordinate / Scope + Platform + Status
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(horizontalArrangement = Arrangement.spacedBy(6.dp), verticalAlignment = Alignment.CenterVertically) {
                    val computedLabel = item.scopeLabel.ifBlank {
                        when {
                            !item.surahNumbers.isNullOrEmpty() && item.surahNumbers.size > 1 ->
                                "Surahs ${item.surahNumbers.joinToString(", ")}"
                            item.endSurahNumber != null && item.endSurahNumber > item.surahNumber ->
                                "Surahs ${item.surahNumber}–${item.endSurahNumber}"
                            item.isFullSurah ->
                                "Surah ${item.surahNumber} (Full Surah)"
                            item.endVerseNumber != null && item.endVerseNumber > item.verseNumber ->
                                "Surah ${item.surahNumber}:${item.verseNumber}–${item.endVerseNumber}"
                            else ->
                                "Surah ${item.surahNumber}:${item.verseNumber}"
                        }
                    }
                    Surface(
                        color = MaterialTheme.colorScheme.primaryContainer,
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Text(
                            text = computedLabel,
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 3.dp),
                            style = MaterialTheme.typography.labelSmall,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onPrimaryContainer
                        )
                    }

                    if (!item.videoPlatform.isNullOrBlank()) {
                        Surface(
                            color = MaterialTheme.colorScheme.secondaryContainer,
                            shape = RoundedCornerShape(8.dp)
                        ) {
                            Text(
                                text = "🎬 ${item.videoPlatform.uppercase()}",
                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 3.dp),
                                style = MaterialTheme.typography.labelSmall,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.onSecondaryContainer
                            )
                        }
                    }
                }

                if (item.status == "published") {
                    Surface(
                        color = Color(0xFFD1FAE5),
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Text(
                            text = "✅ Published",
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp),
                            style = MaterialTheme.typography.labelSmall,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFF065F46)
                        )
                    }
                } else {
                    Surface(
                        color = Color(0xFFFEF3C7),
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Text(
                            text = "⏳ Pending (${item.approvalsCount}/5)",
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 2.dp),
                            style = MaterialTheme.typography.labelSmall,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFF92400E)
                        )
                    }
                }
            }

            // Title
            Text(
                text = item.title,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface
            )

            // Description
            if (!item.description.isNullOrBlank()) {
                Text(
                    text = item.description,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 3,
                    overflow = TextOverflow.Ellipsis
                )
            }

            // Submitter & Approval Counts
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "By: ${item.submitterName ?: "Contributor"}",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.outline
                )

                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text(
                        text = "👍 ${item.approvalsCount}",
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFF059669)
                    )
                    Text(
                        text = "👎 ${item.downvotesCount}",
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFFE11D48)
                    )
                }
            }

            // Actions row: Watch Video + Verify / Vote
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 4.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                if (!item.videoUrl.isNullOrBlank()) {
                    Button(
                        onClick = { onWatchVideo(item.videoUrl) },
                        modifier = Modifier.weight(1f),
                        colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.primary)
                    ) {
                        Icon(Icons.Default.PlayArrow, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(
                            text = if (!item.videoPlatform.isNullOrBlank()) "Watch (${item.videoPlatform.replaceFirstChar { it.uppercase() }})" else "Watch Video",
                            fontSize = 12.sp
                        )
                    }
                }

                OutlinedButton(
                    onClick = onVerifyClick,
                    modifier = Modifier.weight(1f)
                ) {
                    Text(if (isLoggedIn) "⚖️ Verify / Vote" else "Login to Verify", fontSize = 12.sp)
                }
            }

            // User's previous review if available
            if (item.myVote != null) {
                Surface(
                    color = MaterialTheme.colorScheme.surface,
                    shape = RoundedCornerShape(8.dp),
                    border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f)),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text(
                        text = "Your vote: ${if (item.myVote == "approve") "Approved 👍" else "Downvoted 👎"}" +
                                if (!item.myFeedback.isNullOrBlank()) " — \"${item.myFeedback}\"" else "",
                        modifier = Modifier.padding(8.dp),
                        style = MaterialTheme.typography.labelSmall,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                }
            }
        }
    }
}

@Composable
fun VerificationDialog(
    item: ResearchContribution,
    onDismiss: () -> Unit,
    onSubmitVerdict: (vote: String, feedback: String) -> Unit
) {
    var vote by remember { mutableStateOf("approve") }
    var feedback by remember { mutableStateOf("") }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Peer Verification") },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                Text(
                    text = "Evaluate: \"${item.title}\"",
                    style = MaterialTheme.typography.bodySmall,
                    fontWeight = FontWeight.Bold
                )

                Text("Your verdict:", style = MaterialTheme.typography.labelMedium)

                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    FilterChip(
                        selected = vote == "approve",
                        onClick = { vote = "approve" },
                        label = { Text("Approve 👍") },
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = Color(0xFFD1FAE5),
                            selectedLabelColor = Color(0xFF065F46)
                        )
                    )
                    FilterChip(
                        selected = vote == "downvote",
                        onClick = { vote = "downvote" },
                        label = { Text("Downvote 👎") },
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = Color(0xFFFFE4E6),
                            selectedLabelColor = Color(0xFF9F1239)
                        )
                    )
                }

                OutlinedTextField(
                    value = feedback,
                    onValueChange = { feedback = it },
                    label = { Text("Feedback / Scholarly remarks (Optional)") },
                    modifier = Modifier.fillMaxWidth(),
                    maxLines = 3
                )
            }
        },
        confirmButton = {
            Button(onClick = { onSubmitVerdict(vote, feedback) }) {
                Text("Submit Verdict")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Cancel")
            }
        }
    )
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ContributeResearchBottomSheet(
    repository: DataRepository,
    userId: Int?,
    userName: String,
    userEmail: String,
    initialSurahNumber: Int = 1,
    initialVerseNumber: Int = 1,
    onDismiss: () -> Unit
) {
    var title by remember { mutableStateOf("") }
    var scopeMode by remember { mutableStateOf("single") } // "single", "verse_range", "full_surah", "surah_range", "multiple_surahs"
    var surahNumberText by remember(initialSurahNumber) { mutableStateOf(initialSurahNumber.toString()) }
    var endSurahNumberText by remember(initialSurahNumber) { mutableStateOf(initialSurahNumber.toString()) }
    var multipleSurahsText by remember { mutableStateOf("") }
    var verseNumberText by remember(initialVerseNumber) { mutableStateOf(initialVerseNumber.toString()) }
    var endVerseNumberText by remember { mutableStateOf("") }
    var category by remember { mutableStateOf("video") }
    var videoUrl by remember { mutableStateOf("") }
    var description by remember { mutableStateOf("") }
    var submitterName by remember { mutableStateOf(userName.ifBlank { "Mobile Contributor" }) }
    var submitterEmail by remember { mutableStateOf(userEmail) }

    var isSubmitting by remember { mutableStateOf(false) }
    var errorMessage by remember { mutableStateOf<String?>(null) }
    var successMessage by remember { mutableStateOf<String?>(null) }

    val scope = rememberCoroutineScope()

    ModalBottomSheet(
        onDismissRequest = onDismiss,
        sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 20.dp, vertical = 10.dp)
                .verticalScroll(rememberScrollState()),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text(
                text = "Contribute Quran Research / Video",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold
            )
            Text(
                text = "Add a single verse, verse range, full Surah (start to end), or multiple Surahs, and link videos from any platform.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )

            if (errorMessage != null) {
                Text(errorMessage!!, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodySmall)
            }

            if (successMessage != null) {
                Text(successMessage!!, color = Color(0xFF059669), style = MaterialTheme.typography.bodySmall, fontWeight = FontWeight.Bold)
            }

            // Category choice
            val categories = listOf("video" to "🎬 Video", "science" to "🔬 Science", "seerah" to "🕌 Seerah", "hadith" to "📚 Hadith", "history" to "🏛️ History")
            LazyRow(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                items(categories) { (key, label) ->
                    FilterChip(
                        selected = category == key,
                        onClick = { category = key },
                        label = { Text(label, fontSize = 12.sp) }
                    )
                }
            }

            // Scope Mode selector
            val scopeModes = listOf(
                "single" to "📍 Single Ayah",
                "verse_range" to "📏 Ayah Range",
                "full_surah" to "📖 Full Surah (Start–End)",
                "surah_range" to "📚 Surah Range",
                "multiple_surahs" to "🗂️ Multiple Surahs"
            )
            Text("Surah / Verse Scope", style = MaterialTheme.typography.labelSmall, fontWeight = FontWeight.Bold)
            LazyRow(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                items(scopeModes) { (modeKey, modeLabel) ->
                    FilterChip(
                        selected = scopeMode == modeKey,
                        onClick = { scopeMode = modeKey },
                        label = { Text(modeLabel, fontSize = 11.sp) }
                    )
                }
            }

            // Surah & Verse inputs depending on Scope Mode
            when (scopeMode) {
                "single" -> {
                    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        OutlinedTextField(
                            value = surahNumberText,
                            onValueChange = { surahNumberText = it },
                            label = { Text("Surah # (1-114)") },
                            modifier = Modifier.weight(1f),
                            singleLine = true
                        )
                        OutlinedTextField(
                            value = verseNumberText,
                            onValueChange = { verseNumberText = it },
                            label = { Text("Ayah #") },
                            modifier = Modifier.weight(1f),
                            singleLine = true
                        )
                    }
                }
                "verse_range" -> {
                    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        OutlinedTextField(
                            value = surahNumberText,
                            onValueChange = { surahNumberText = it },
                            label = { Text("Surah #") },
                            modifier = Modifier.weight(1f),
                            singleLine = true
                        )
                        OutlinedTextField(
                            value = verseNumberText,
                            onValueChange = { verseNumberText = it },
                            label = { Text("Start Ayah") },
                            modifier = Modifier.weight(1f),
                            singleLine = true
                        )
                        OutlinedTextField(
                            value = endVerseNumberText,
                            onValueChange = { endVerseNumberText = it },
                            label = { Text("End Ayah") },
                            modifier = Modifier.weight(1f),
                            singleLine = true
                        )
                    }
                }
                "full_surah" -> {
                    OutlinedTextField(
                        value = surahNumberText,
                        onValueChange = { surahNumberText = it },
                        label = { Text("Surah # (Full Surah Start to End)") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true
                    )
                }
                "surah_range" -> {
                    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        OutlinedTextField(
                            value = surahNumberText,
                            onValueChange = { surahNumberText = it },
                            label = { Text("Start Surah #") },
                            modifier = Modifier.weight(1f),
                            singleLine = true
                        )
                        OutlinedTextField(
                            value = endSurahNumberText,
                            onValueChange = { endSurahNumberText = it },
                            label = { Text("End Surah #") },
                            modifier = Modifier.weight(1f),
                            singleLine = true
                        )
                    }
                }
                "multiple_surahs" -> {
                    OutlinedTextField(
                        value = multipleSurahsText,
                        onValueChange = { multipleSurahsText = it },
                        label = { Text("Multiple Surahs (e.g. 1, 18, 36 or 112-114)") },
                        placeholder = { Text("1, 18, 36") },
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true
                    )
                }
            }

            // Title
            OutlinedTextField(
                value = title,
                onValueChange = { title = it },
                label = { Text("Title *") },
                placeholder = { Text("e.g. Complete Tafsir / Scientific Miracle Analysis") },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true
            )

            // Video URL (Every Platform)
            OutlinedTextField(
                value = videoUrl,
                onValueChange = { videoUrl = it },
                label = { Text("Video URL (Any Platform: YouTube, Vimeo, TikTok, FB, IG, X, MP4...)") },
                placeholder = { Text("https://www.youtube.com/watch?v=... or any video link") },
                modifier = Modifier.fillMaxWidth(),
                singleLine = true
            )

            // Description
            OutlinedTextField(
                value = description,
                onValueChange = { description = it },
                label = { Text("Description / Research Notes") },
                modifier = Modifier.fillMaxWidth(),
                maxLines = 4
            )

            Spacer(modifier = Modifier.height(8.dp))

            Button(
                onClick = {
                    if (title.isBlank()) {
                        errorMessage = "Title is required."
                        return@Button
                    }
                    if (category == "video" && videoUrl.isBlank()) {
                        errorMessage = "Video URL is required for video category."
                        return@Button
                    }
                    val surahNum = surahNumberText.toIntOrNull() ?: 1
                    val verseNum = if (scopeMode == "full_surah" || scopeMode == "surah_range" || scopeMode == "multiple_surahs") 1 else (verseNumberText.toIntOrNull() ?: 1)
                    val endVerseNum = if (scopeMode == "verse_range") endVerseNumberText.toIntOrNull() else null
                    val endSurahNum = if (scopeMode == "surah_range") endSurahNumberText.toIntOrNull() else null
                    val surahNumsStr = if (scopeMode == "multiple_surahs") multipleSurahsText.trim() else ""
                    val fullSurahFlag = scopeMode == "full_surah" || scopeMode == "surah_range" || scopeMode == "multiple_surahs"

                    scope.launch {
                        isSubmitting = true
                        errorMessage = null
                        val res = repository.submitResearchContribution(
                            title = title,
                            surahNumber = surahNum,
                            verseNumber = verseNum,
                            category = category,
                            videoUrl = videoUrl,
                            description = description,
                            submitterName = submitterName,
                            submitterEmail = submitterEmail,
                            userId = userId,
                            endSurahNumber = endSurahNum,
                            surahNumbers = surahNumsStr,
                            endVerseNumber = endVerseNum,
                            isFullSurah = fullSurahFlag
                        )
                        isSubmitting = false
                        if (res.isSuccess) {
                            successMessage = "JazakAllah Khair! Contribution submitted for peer review."
                            title = ""
                            videoUrl = ""
                            description = ""
                        } else {
                            errorMessage = res.exceptionOrNull()?.message ?: "Submission failed."
                        }
                    }
                },
                modifier = Modifier.fillMaxWidth(),
                enabled = !isSubmitting
            ) {
                if (isSubmitting) {
                    CircularProgressIndicator(modifier = Modifier.size(18.dp), color = MaterialTheme.colorScheme.onPrimary)
                } else {
                    Text("Submit Contribution")
                }
            }

            Spacer(modifier = Modifier.height(16.dp))
        }
    }
}

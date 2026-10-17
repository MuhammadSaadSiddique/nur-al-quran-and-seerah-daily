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
        containerColor = androidx.compose.ui.graphics.Color.Transparent,
        topBar = {
            com.asloobulhayat.eternalecho.ui.components.EchoTopBar(
                title = "Quran Research",
                onBackClick = onBackClick,
                navigationIcon = Icons.AutoMirrored.Filled.ArrowBack,
                actions = {
                    IconButton(onClick = { showContributeSheet = true }) {
                        Icon(Icons.Default.Add, contentDescription = "Contribute")
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
                shape = androidx.compose.foundation.shape.RoundedCornerShape(16.dp)
            )
        }
    ) { paddingValues ->
        com.asloobulhayat.eternalecho.ui.components.EchoScreenBackground {
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
                    label = { Text("Published", fontWeight = FontWeight.SemiBold) },
                    colors = FilterChipDefaults.filterChipColors(
                        selectedContainerColor = MaterialTheme.colorScheme.primary,
                        selectedLabelColor = MaterialTheme.colorScheme.onPrimary
                    ),
                    modifier = Modifier.weight(1f),
                    shape = RoundedCornerShape(20.dp)
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
                        Text(
                            if (isLoggedIn) "Pending review" else "Pending · Login",
                            fontWeight = FontWeight.SemiBold
                        )
                    },
                    colors = FilterChipDefaults.filterChipColors(
                        selectedContainerColor = MaterialTheme.colorScheme.tertiary,
                        selectedLabelColor = MaterialTheme.colorScheme.onTertiary
                    ),
                    modifier = Modifier.weight(1f),
                    shape = RoundedCornerShape(20.dp)
                )
            }
        }

        val categories = listOf(
            "all" to "All",
            "video" to "Videos",
            "science" to "Science",
            "seerah" to "Seerah",
            "hadith" to "Hadith",
            "history" to "History"
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
    val isPublished = item.status == "published"
    val scopeLabel = item.scopeLabel.ifBlank {
        when {
            !item.surahNumbers.isNullOrEmpty() && item.surahNumbers.size > 1 ->
                "Surahs ${item.surahNumbers.joinToString(", ")}"
            item.endSurahNumber != null && item.endSurahNumber > item.surahNumber ->
                "Surahs ${item.surahNumber}–${item.endSurahNumber}"
            item.isFullSurah ->
                "Surah ${item.surahNumber} · Full"
            item.endVerseNumber != null && item.endVerseNumber > item.verseNumber ->
                "${item.surahNumber}:${item.verseNumber}–${item.endVerseNumber}"
            else ->
                "${item.surahNumber}:${item.verseNumber}"
        }
    }

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(18.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surface.copy(alpha = 0.94f)
        ),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.55f)),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
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
                        color = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.7f),
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Text(
                            text = scopeLabel,
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                            style = MaterialTheme.typography.labelSmall,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onPrimaryContainer,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis
                        )
                    }
                    if (!item.videoPlatform.isNullOrBlank()) {
                        Surface(
                            color = MaterialTheme.colorScheme.secondaryContainer.copy(alpha = 0.7f),
                            shape = RoundedCornerShape(8.dp)
                        ) {
                            Text(
                                text = item.videoPlatform.replaceFirstChar { it.uppercase() },
                                modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                                style = MaterialTheme.typography.labelSmall,
                                fontWeight = FontWeight.SemiBold,
                                color = MaterialTheme.colorScheme.onSecondaryContainer
                            )
                        }
                    }
                }

                Surface(
                    color = if (isPublished)
                        MaterialTheme.colorScheme.primary.copy(alpha = 0.15f)
                    else
                        MaterialTheme.colorScheme.tertiaryContainer.copy(alpha = 0.55f),
                    shape = RoundedCornerShape(8.dp)
                ) {
                    Text(
                        text = if (isPublished) "Published" else "${item.approvalsCount}/5 pending",
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.Bold,
                        color = if (isPublished)
                            MaterialTheme.colorScheme.primary
                        else
                            MaterialTheme.colorScheme.onTertiaryContainer
                    )
                }
            }

            Text(
                text = item.title,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface
            )

            if (!item.description.isNullOrBlank()) {
                Text(
                    text = item.description,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 3,
                    overflow = TextOverflow.Ellipsis,
                    lineHeight = 18.sp
                )
            }

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = item.submitterName ?: "Contributor",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                Text(
                    text = "${item.approvalsCount} approve · ${item.downvotesCount} down",
                    style = MaterialTheme.typography.labelSmall,
                    fontWeight = FontWeight.SemiBold,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }

            // Mobile-friendly progress for pending
            if (!isPublished) {
                LinearProgressIndicator(
                    progress = { (item.approvalsCount.coerceIn(0, 5) / 5f) },
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(6.dp)
                        .clip(RoundedCornerShape(3.dp)),
                    color = MaterialTheme.colorScheme.primary,
                    trackColor = MaterialTheme.colorScheme.surfaceVariant
                )
            }

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                if (!item.videoUrl.isNullOrBlank()) {
                    Button(
                        onClick = { onWatchVideo(item.videoUrl) },
                        modifier = Modifier.weight(1f),
                        shape = RoundedCornerShape(12.dp)
                    ) {
                        Icon(Icons.Default.PlayArrow, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Watch", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                    }
                }

                OutlinedButton(
                    onClick = onVerifyClick,
                    modifier = Modifier.weight(1f),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Text(
                        text = if (isLoggedIn) "Review" else "Login to review",
                        fontSize = 13.sp,
                        fontWeight = FontWeight.SemiBold
                    )
                }
            }

            if (item.myVote != null) {
                val voteLabel = if (item.myVote == "approve") "Approved" else "Downvoted"
                val feedbackSuffix = if (!item.myFeedback.isNullOrBlank()) " — ${item.myFeedback}" else ""
                Surface(
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.45f),
                    shape = RoundedCornerShape(10.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text(
                        text = "Your review: $voteLabel$feedbackSuffix",
                        modifier = Modifier.padding(10.dp),
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
        title = {
            Text("Peer review", fontWeight = FontWeight.Bold)
        },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                Text(
                    text = item.title,
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.SemiBold
                )
                Text(
                    text = "Help the community decide whether this should be published.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    FilterChip(
                        selected = vote == "approve",
                        onClick = { vote = "approve" },
                        label = { Text("Approve") },
                        modifier = Modifier.weight(1f),
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = MaterialTheme.colorScheme.primaryContainer,
                            selectedLabelColor = MaterialTheme.colorScheme.onPrimaryContainer
                        )
                    )
                    FilterChip(
                        selected = vote == "downvote",
                        onClick = { vote = "downvote" },
                        label = { Text("Downvote") },
                        modifier = Modifier.weight(1f),
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = MaterialTheme.colorScheme.errorContainer,
                            selectedLabelColor = MaterialTheme.colorScheme.onErrorContainer
                        )
                    )
                }

                OutlinedTextField(
                    value = feedback,
                    onValueChange = { feedback = it },
                    label = { Text("Optional notes") },
                    placeholder = { Text("Why approve or downvote?") },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp),
                    maxLines = 3
                )
            }
        },
        confirmButton = {
            Button(
                onClick = { onSubmitVerdict(vote, feedback) },
                shape = RoundedCornerShape(12.dp)
            ) {
                Text("Submit review")
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
                text = "Contribute research",
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold
            )
            Text(
                text = "Link a video or note to an ayah, range, or Surah. Peer review publishes at 5 approvals.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                lineHeight = 18.sp
            )

            if (errorMessage != null) {
                Text(errorMessage!!, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodySmall)
            }

            if (successMessage != null) {
                Text(
                    successMessage!!,
                    color = MaterialTheme.colorScheme.primary,
                    style = MaterialTheme.typography.bodySmall,
                    fontWeight = FontWeight.Bold
                )
            }

            Text("Category", style = MaterialTheme.typography.labelMedium, fontWeight = FontWeight.Bold)
            val categories = listOf(
                "video" to "Video",
                "science" to "Science",
                "seerah" to "Seerah",
                "hadith" to "Hadith",
                "history" to "History"
            )
            LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                items(categories) { (key, label) ->
                    FilterChip(
                        selected = category == key,
                        onClick = { category = key },
                        label = { Text(label, fontSize = 12.sp, fontWeight = FontWeight.SemiBold) },
                        shape = RoundedCornerShape(20.dp)
                    )
                }
            }

            Text("Scope", style = MaterialTheme.typography.labelMedium, fontWeight = FontWeight.Bold)
            val scopeModes = listOf(
                "single" to "Single ayah",
                "verse_range" to "Ayah range",
                "full_surah" to "Full Surah",
                "surah_range" to "Surah range",
                "multiple_surahs" to "Multiple Surahs"
            )
            LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                items(scopeModes) { (modeKey, modeLabel) ->
                    FilterChip(
                        selected = scopeMode == modeKey,
                        onClick = { scopeMode = modeKey },
                        label = { Text(modeLabel, fontSize = 12.sp, fontWeight = FontWeight.SemiBold) },
                        shape = RoundedCornerShape(20.dp)
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

            OutlinedTextField(
                value = title,
                onValueChange = { title = it },
                label = { Text("Title") },
                placeholder = { Text("Short clear title") },
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(12.dp),
                singleLine = true
            )

            OutlinedTextField(
                value = videoUrl,
                onValueChange = { videoUrl = it },
                label = { Text(if (category == "video") "Video URL" else "Optional video URL") },
                placeholder = { Text("YouTube, Vimeo, or any video link") },
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(12.dp),
                singleLine = true
            )

            OutlinedTextField(
                value = description,
                onValueChange = { description = it },
                label = { Text("Notes") },
                placeholder = { Text("What does this research show?") },
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(12.dp),
                maxLines = 4
            )

            Spacer(modifier = Modifier.height(4.dp))

            Button(
                onClick = {
                    if (title.isBlank()) {
                        errorMessage = "Title is required."
                        return@Button
                    }
                    if (category == "video" && videoUrl.isBlank()) {
                        errorMessage = "Video URL is required for the video category."
                        return@Button
                    }
                    val surahNum = surahNumberText.toIntOrNull() ?: 1
                    val verseNum = if (scopeMode == "full_surah" || scopeMode == "surah_range" || scopeMode == "multiple_surahs") {
                        1
                    } else {
                        verseNumberText.toIntOrNull() ?: 1
                    }
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
                            successMessage = "JazakAllah Khair — submitted for peer review."
                            title = ""
                            videoUrl = ""
                            description = ""
                        } else {
                            errorMessage = res.exceptionOrNull()?.message ?: "Submission failed."
                        }
                    }
                },
                modifier = Modifier
                    .fillMaxWidth()
                    .height(52.dp),
                enabled = !isSubmitting,
                shape = RoundedCornerShape(14.dp)
            ) {
                if (isSubmitting) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(18.dp),
                        color = MaterialTheme.colorScheme.onPrimary,
                        strokeWidth = 2.dp
                    )
                } else {
                    Text("Submit for review", fontWeight = FontWeight.SemiBold)
                }
            }

            Spacer(modifier = Modifier.height(20.dp))
        }
    }
}

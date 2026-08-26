package com.example.eternalechomobile.ui.surah

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.eternalechomobile.data.Connection
import com.example.eternalechomobile.data.DefaultDataRepository
import com.example.eternalechomobile.data.Verse
import com.example.eternalechomobile.theme.EternalEchoMobileTheme

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SurahScreen(
    surahNumber: Int,
    onBackClick: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: SurahScreenViewModel = viewModel { SurahScreenViewModel(surahNumber, DefaultDataRepository()) }
) {
    val versesState by viewModel.versesState.collectAsStateWithLifecycle()
    val connectionsState by viewModel.connectionsState.collectAsStateWithLifecycle()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Surah #$surahNumber", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onBackClick) {
                        Icon(imageVector = Icons.Default.ArrowBack, contentDescription = "Back")
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
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        items(state.data) { verse ->
                            VerseItem(
                                verse = verse,
                                onClick = { viewModel.loadConnections(verse.verseNumber) }
                            )
                        }
                    }
                }
            }

            // Connection Dialog
            when (val state = connectionsState) {
                is ConnectionsUiState.Loading -> {
                    AlertDialog(
                        onDismissRequest = { viewModel.clearConnections() },
                        confirmButton = {},
                        title = { Text("Loading Connections...") },
                        text = {
                            Box(modifier = Modifier.fillMaxWidth().height(100.dp)) {
                                CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                            }
                        }
                    )
                }
                is ConnectionsUiState.Error -> {
                    AlertDialog(
                        onDismissRequest = { viewModel.clearConnections() },
                        confirmButton = {
                            TextButton(onClick = { viewModel.clearConnections() }) {
                                Text("Dismiss")
                            }
                        },
                        title = { Text("Error") },
                        text = { Text(state.throwable.message ?: "Failed to load connection details.") }
                    )
                }
                is ConnectionsUiState.Success -> {
                    AlertDialog(
                        onDismissRequest = { viewModel.clearConnections() },
                        confirmButton = {
                            TextButton(onClick = { viewModel.clearConnections() }) {
                                Text("Close")
                            }
                        },
                        title = { Text("Research Connections: Verse ${surahNumber}:${state.verseNumber}") },
                        text = {
                            LazyColumn(
                                modifier = Modifier.fillMaxWidth().heightIn(max = 400.dp),
                                verticalArrangement = Arrangement.spacedBy(16.dp)
                            ) {
                                if (state.data.science.isNotEmpty()) {
                                    item { CategoryHeader("🔬 Science & Nature") }
                                    items(state.data.science) { ConnectionCard(it, "Science") }
                                }
                                if (state.data.seerah.isNotEmpty()) {
                                    item { CategoryHeader("🕌 Seerah / History") }
                                    items(state.data.seerah) { ConnectionCard(it, "Seerah") }
                                }
                                if (state.data.hadith.isNotEmpty()) {
                                    item { CategoryHeader("📚 Prophetic Hadith") }
                                    items(state.data.hadith) { ConnectionCard(it, "Hadith") }
                                }
                                if (state.data.history.isNotEmpty()) {
                                    item { CategoryHeader("🏛️ History Context") }
                                    items(state.data.history) { ConnectionCard(it, "History") }
                                }
                                if (state.data.scripture.isNotEmpty()) {
                                    item { CategoryHeader("📖 Biblical Scripture") }
                                    items(state.data.scripture) { ConnectionCard(it, "Scripture") }
                                }
                                if (state.data.science.isEmpty() && state.data.seerah.isEmpty() &&
                                    state.data.hadith.isEmpty() && state.data.history.isEmpty() &&
                                    state.data.scripture.isEmpty()) {
                                    item {
                                        Text(
                                            "No approved connections mapped for this verse yet.",
                                            style = MaterialTheme.typography.bodyMedium,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }
                                }
                            }
                        }
                    )
                }
                else -> { /* Idle */ }
            }
        }
    }
}

@Composable
fun VerseItem(verse: Verse, onClick: () -> Unit) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surfaceVariant
        )
    ) {
        Column(
            modifier = Modifier.padding(16.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Badge(containerColor = MaterialTheme.colorScheme.primary) {
                    Text(
                        text = "Verse ${verse.verseNumber}",
                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                        color = MaterialTheme.colorScheme.onPrimary
                    )
                }
                Text(
                    text = "Juz " + verse.juzNumber,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
            Spacer(modifier = Modifier.height(12.dp))
            Text(
                text = verse.textArabic,
                fontFamily = FontFamily.Serif,
                fontSize = 24.sp,
                lineHeight = 36.sp,
                textAlign = TextAlign.Right,
                modifier = Modifier.fillMaxWidth(),
                fontWeight = FontWeight.Bold
            )
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = verse.textTransliteration,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = "Tap to view research connections",
                style = MaterialTheme.typography.labelSmall,
                color = MaterialTheme.colorScheme.primary,
                fontWeight = FontWeight.Bold
            )
        }
    }
}

@Composable
fun CategoryHeader(title: String) {
    Text(
        text = title,
        style = MaterialTheme.typography.titleMedium,
        fontWeight = FontWeight.Bold,
        modifier = Modifier.padding(vertical = 4.dp),
        color = MaterialTheme.colorScheme.primary
    )
}

@Composable
fun ConnectionCard(connection: Connection, type: String) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.secondaryContainer
        )
    ) {
        Column(modifier = Modifier.padding(12.dp)) {

            Text(
                text = connection.title,
                style = MaterialTheme.typography.bodyLarge,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSecondaryContainer
            )
            Spacer(modifier = Modifier.height(4.dp))
            if (connection.extraInfo.isNotEmpty()) {
                Badge(containerColor = MaterialTheme.colorScheme.tertiaryContainer) {
                    Text(
                        text = connection.extraInfo,
                        modifier = Modifier.padding(horizontal = 4.dp, vertical = 1.dp),
                        style = MaterialTheme.typography.labelSmall,
                        color = MaterialTheme.colorScheme.onTertiaryContainer
                    )
                }
            }

            Spacer(modifier = Modifier.height(4.dp))
            Text(
                text = connection.description,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}
@Preview
@Composable
fun Prview(){
    EternalEchoMobileTheme{
        ConnectionCard(Connection("test","Discribe","extra"),"type")

    }
}

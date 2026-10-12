package com.asloobulhayat.eternalecho

import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.clickable
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.asloobulhayat.eternalecho.data.ApiClient
import com.asloobulhayat.eternalecho.security.AppSecurity
import com.asloobulhayat.eternalecho.security.SecurePreferences
import com.asloobulhayat.eternalecho.theme.EternalEchoMobileTheme
import com.asloobulhayat.eternalecho.theme.ThemeManager
import com.asloobulhayat.eternalecho.theme.ThemeMode
import com.asloobulhayat.eternalecho.theme.ThemeSettingDialog
import com.asloobulhayat.eternalecho.ui.home.HomeScreen
import com.asloobulhayat.eternalecho.util.DeepLinkHelper
import kotlinx.coroutines.launch

/**
 * AppActivity displaying the Home Dashboard and linking with other activities & screens.
 */
class AppActivity : ComponentActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // Disable screen security if requested (consistent with MainActivity)
        AppSecurity.disableScreenSecurity(this)

        enableEdgeToEdge()

        setContent {
            EternalEchoMobileTheme {
                val context = this@AppActivity
                val prefs = remember { SecurePreferences.getInstance(context) }
                var loginStateRefresh by remember { mutableIntStateOf(0) }

                val userId = remember(loginStateRefresh) { prefs.getInt("user_id", -1) }
                val userName = remember(loginStateRefresh) {
                    val saved = prefs.getString("user_name", "") ?: ""
                    saved.ifBlank { "Wisdom Seeker" }
                }
                val userEmail = remember(loginStateRefresh) { prefs.getString("user_email", "") ?: "" }
                val isLoggedIn = userId != -1
                val repository = remember { com.asloobulhayat.eternalecho.data.DefaultDataRepository() }
                val scope = rememberCoroutineScope()

                var streakDays by remember { mutableIntStateOf(prefs.getInt("current_streak", 0)) }
                var longestStreak by remember { mutableIntStateOf(prefs.getInt("longest_streak", 0)) }
                var isStreakActiveToday by remember { mutableStateOf(prefs.getBoolean("is_streak_active_today", false)) }

                LaunchedEffect(userId, loginStateRefresh) {
                    if (userId > 0) {
                        val res = repository.getStreakStatus(userId)
                        res.getOrNull()?.let { streak ->
                            streakDays = streak.currentStreak
                            longestStreak = streak.longestStreak
                            isStreakActiveToday = streak.isActiveToday
                            prefs.putInt("current_streak", streak.currentStreak)
                            prefs.putInt("longest_streak", streak.longestStreak)
                            prefs.putBoolean("is_streak_active_today", streak.isActiveToday)
                        }
                    }
                }

                var showProfileDialog by remember { mutableStateOf(false) }
                var showThemeDialog by remember { mutableStateOf(false) }

                if (showThemeDialog) {
                    ThemeSettingDialog(
                        onDismiss = { showThemeDialog = false }
                    )
                }

                if (showProfileDialog) {
                    AppProfileDialog(
                        isLoggedIn = isLoggedIn,
                        userName = userName,
                        userEmail = userEmail,
                        onDismiss = { showProfileDialog = false },
                        onOpenThemeSettings = {
                            showProfileDialog = false
                            showThemeDialog = true
                        },
                        onSignInClick = {
                            showProfileDialog = false
                            launchMainActivityDestination("eternalecho://home")
                        },
                        onLogOutClick = {
                            prefs.remove("user_id")
                            prefs.remove("user_name")
                            prefs.remove("user_email")
                            loginStateRefresh++
                            showProfileDialog = false
                        }
                    )
                }

                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background
                ) {
                    HomeScreen(
                        userName = userName,
                        streakDays = streakDays,
                        longestStreak = longestStreak,
                        isStreakActiveToday = isStreakActiveToday,
                        currentJuz = 2,
                        readPages = 5,
                        readingProgressPercent = 70,
                        onClaimStreakClick = {
                            if (userId > 0) {
                                scope.launch {
                                    val res = repository.checkInDailyStreak(userId)
                                    res.getOrNull()?.let { streak ->
                                        streakDays = streak.currentStreak
                                        longestStreak = streak.longestStreak
                                        isStreakActiveToday = true
                                        prefs.putInt("current_streak", streak.currentStreak)
                                        prefs.putInt("longest_streak", streak.longestStreak)
                                        prefs.putBoolean("is_streak_active_today", true)
                                    }
                                }
                            } else {
                                streakDays += 1
                                if (streakDays > longestStreak) longestStreak = streakDays
                                isStreakActiveToday = true
                                prefs.putInt("current_streak", streakDays)
                                prefs.putInt("longest_streak", longestStreak)
                                prefs.putBoolean("is_streak_active_today", true)
                            }
                        },
                        onStartQuizClick = {
                            launchMainActivityDestination("eternalecho://quiz")
                        },
                        onExploreHistoryClick = {
                            launchMainActivityDestination("eternalecho://seerah")
                        },
                        onDeepDiveClick = {
                            launchMainActivityDestination("eternalecho://lens/1")
                        },
                        onReadDuasClick = {
                            launchMainActivityDestination("eternalecho://duas")
                        },
                        onReflectClick = {
                            launchMainActivityDestination("eternalecho://lens/2/286")
                        },
                        onReadingProgressClick = {
                            launchMainActivityDestination("eternalecho://lens/2/142")
                        },
                        selectedBottomNavIndex = 0,
                        onBottomNavSelected = { index ->
                            when (index) {
                                0 -> { /* Already on Home */ }
                                1 -> launchMainActivityDestination("eternalecho://lens/1")
                                2 -> launchMainActivityDestination("eternalecho://quiz")
                                3 -> showProfileDialog = true
                            }
                        },
                        showBottomBar = true
                    )
                }
            }
        }
    }

    /**
     * Navigates to MainActivity with the specified deep link URI destination.
     */
    private fun launchMainActivityDestination(uriString: String) {
        val intent = Intent(this, MainActivity::class.java).apply {
            data = Uri.parse(uriString)
            flags = Intent.FLAG_ACTIVITY_CLEAR_TOP or Intent.FLAG_ACTIVITY_SINGLE_TOP
        }
        startActivity(intent)
    }

    companion object {
        fun createIntent(context: Context): Intent {
            return Intent(context, AppActivity::class.java)
        }
    }
}

@Composable
private fun AppProfileDialog(
    isLoggedIn: Boolean,
    userName: String,
    userEmail: String,
    onDismiss: () -> Unit,
    onOpenThemeSettings: () -> Unit,
    onSignInClick: () -> Unit,
    onLogOutClick: () -> Unit
) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                Icon(
                    Icons.Default.Person,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.primary
                )
                Text("User Profile", fontWeight = FontWeight.Bold)
            }
        },
        text = {
            Column(
                verticalArrangement = Arrangement.spacedBy(12.dp),
                modifier = Modifier.fillMaxWidth()
            ) {
                // Theme Appearance Card
                Card(
                    colors = CardDefaults.cardColors(
                        containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                    ),
                    shape = RoundedCornerShape(12.dp),
                    modifier = Modifier
                        .fillMaxWidth()
                        .clickable { onOpenThemeSettings() }
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            Icon(
                                Icons.Default.Settings,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.primary,
                                modifier = Modifier.size(20.dp)
                            )
                            Column {
                                Text("App Theme", fontWeight = FontWeight.SemiBold, style = MaterialTheme.typography.bodyMedium)
                                val currentThemeMode by ThemeManager.themeMode.collectAsState()
                                Text(
                                    text = currentThemeMode.title,
                                    style = MaterialTheme.typography.bodySmall,
                                    color = MaterialTheme.colorScheme.primary
                                )
                            }
                        }
                        Text("Change", style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.primary)
                    }
                }

                if (isLoggedIn) {
                    Text(
                        text = "Signed in as $userName",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold
                    )
                    if (userEmail.isNotEmpty()) {
                        Text(
                            text = userEmail,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                    Text(
                        text = "🔥 7 Day Streak • 70% Juz 2 Completed",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.primary
                    )
                    Spacer(modifier = Modifier.height(4.dp))
                    Button(
                        onClick = onLogOutClick,
                        colors = ButtonDefaults.buttonColors(
                            containerColor = MaterialTheme.colorScheme.error
                        ),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text("Log Out")
                    }
                } else {
                    Text(
                        text = "Welcome, $userName!",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold
                    )
                    Text(
                        text = "Sign in to sync your quiz scores, test submissions, and streaks across devices.",
                        style = MaterialTheme.typography.bodySmall
                    )
                    Button(
                        onClick = onSignInClick,
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text("Sign In / Register")
                    }
                }
            }
        },
        confirmButton = {},
        dismissButton = {
            TextButton(onClick = onDismiss) {
                Text("Close")
            }
        }
    )
}

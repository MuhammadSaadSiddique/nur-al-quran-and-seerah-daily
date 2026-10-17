package com.asloobulhayat.eternalecho.ui.main

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.itemsIndexed
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ExitToApp
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Clear
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.ExitToApp
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Menu
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.PlayArrow
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.Share
import androidx.compose.material3.*
import com.asloobulhayat.eternalecho.ui.profile.ProfileDrawerContent
import androidx.compose.runtime.*
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.testTag
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation3.runtime.NavKey
import com.asloobulhayat.eternalecho.AuthRoute
import com.asloobulhayat.eternalecho.DuasRoute
import com.asloobulhayat.eternalecho.OnboardingRoute
import com.asloobulhayat.eternalecho.ThemeQuizSelection
import com.asloobulhayat.eternalecho.config.AppConfig
import com.asloobulhayat.eternalecho.data.*
import com.asloobulhayat.eternalecho.notifications.NotificationRepository
import com.asloobulhayat.eternalecho.security.SecurePreferences
import com.asloobulhayat.eternalecho.theme.ThemeManager
import com.asloobulhayat.eternalecho.theme.ThemeSettingDialog
import com.asloobulhayat.eternalecho.ui.adaptive.*
import com.asloobulhayat.eternalecho.ui.duas.DuasContent
import com.asloobulhayat.eternalecho.ui.home.HomeScreen
import com.asloobulhayat.eternalecho.ui.notifications.NotificationsBottomSheet
import com.asloobulhayat.eternalecho.ui.research.ContributeResearchBottomSheet
import com.asloobulhayat.eternalecho.ui.research.ContributionCard
import com.asloobulhayat.eternalecho.ui.research.VerificationDialog
import com.asloobulhayat.eternalecho.ui.surah.SurahReaderContent
import com.asloobulhayat.eternalecho.util.ShareHelper
import kotlinx.coroutines.launch


@Composable
fun MainScreen(
    onItemClick: (NavKey) -> Unit,
    modifier: Modifier = Modifier,
    initialTab: Int = 0,
    onTabSelected: (Int) -> Unit = {},
    viewModel: MainScreenViewModel = viewModel { MainScreenViewModel(DefaultDataRepository()) }
) {
    var selectedTab by remember(initialTab) { mutableIntStateOf(initialTab) }
    val adaptiveInfo = rememberWindowAdaptiveInfo()

    val context = LocalContext.current
    val prefs = remember { SecurePreferences.getInstance(context) }
    val scope = rememberCoroutineScope()

    val notificationRepo = remember { NotificationRepository.getInstance(context) }
    val notifications by notificationRepo.notifications.collectAsState()
    val unreadCount by notificationRepo.unreadCount.collectAsState()
    var showNotificationsSheet by remember { mutableStateOf(false) }


    LaunchedEffect(Unit) {
        notificationRepo.syncNotifications(showAlerts = true)
    }

    var loginStateRefresh by remember { mutableStateOf(0) }
    val lifecycleOwner = androidx.lifecycle.compose.LocalLifecycleOwner.current
    DisposableEffect(lifecycleOwner) {
        val observer = LifecycleEventObserver { _, event ->
            if (event == Lifecycle.Event.ON_RESUME) {
                loginStateRefresh++
                scope.launch {
                    notificationRepo.syncNotifications(showAlerts = true)
                }
            }
        }
        lifecycleOwner.lifecycle.addObserver(observer)
        onDispose {
            lifecycleOwner.lifecycle.removeObserver(observer)
        }
    }


    var authVersion by remember { mutableIntStateOf(0) }
    val authKey = loginStateRefresh + authVersion
    val userId = remember(authKey) { prefs.getInt("user_id", -1) }
    val userName = remember(authKey) { prefs.getString("user_name", "") ?: "" }
    val userEmail = remember(authKey) { prefs.getString("user_email", "") ?: "" }
    val apiToken = remember(authKey) { prefs.getString("api_token", null).orEmpty() }
    // Token is required for authenticated API calls after the mobile auth hardening.
    val isLoggedIn = userId > 0 && apiToken.isNotBlank()
    var userHasPassword by remember(authKey) { mutableStateOf(prefs.getBoolean("has_password", false)) }

    fun clearLocalSession() {
        prefs.remove("user_id")
        prefs.remove("user_name")
        prefs.remove("user_email")
        prefs.remove("api_token")
        prefs.remove("has_password")
        ApiClient.authToken = null
        userHasPassword = false
    }

    LaunchedEffect(loginStateRefresh) {
        ApiClient.authToken = prefs.getString("api_token", null)
        // Drop stale pre-token sessions that look logged-in but always 401.
        if (prefs.getInt("user_id", -1) > 0 && prefs.getString("api_token", null).isNullOrBlank()) {
            clearLocalSession()
            authVersion++
        }
    }

    val repository = remember { DefaultDataRepository() }

    var streakDays by remember { mutableIntStateOf(prefs.getInt("current_streak", 0)) }
    var longestStreak by remember { mutableIntStateOf(prefs.getInt("longest_streak", 0)) }
    var isStreakActiveToday by remember { mutableStateOf(prefs.getBoolean("is_streak_active_today", false)) }
    var dailyEngagement by remember { mutableStateOf<com.asloobulhayat.eternalecho.data.DailyEngagement?>(null) }

    LaunchedEffect(userId, loginStateRefresh) {
        if (userId > 0) {
            QuizSubmissionHelper.submitPendingQuizIfAny(
                repository,
                prefs,
                userId
            )
            val engagementRes = repository.getDailyEngagement(userId)
            engagementRes.getOrNull()?.let { engagement ->
                dailyEngagement = engagement
                streakDays = engagement.streak.currentStreak
                longestStreak = engagement.streak.longestStreak
                isStreakActiveToday = engagement.streak.isActiveToday
                prefs.putInt("current_streak", engagement.streak.currentStreak)
                prefs.putInt("longest_streak", engagement.streak.longestStreak)
                prefs.putBoolean("is_streak_active_today", engagement.streak.isActiveToday)
            } ?: run {
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
        } else {
            dailyEngagement = null
        }
    }

    var showAccountDialog by remember { mutableStateOf(false) }
    var showThemeSettingsDialog by remember { mutableStateOf(false) }
    var showFeedbackSheet by remember { mutableStateOf(false) }
    var showDeleteAccountConfirmDialog by remember { mutableStateOf(false) }
    var isDeletingAccount by remember { mutableStateOf(false) }
    var deleteAccountError by remember { mutableStateOf("") }

    var showPasswordDialog by remember { mutableStateOf(false) }
    var passwordText by remember { mutableStateOf("") }
    var currentPasswordText by remember { mutableStateOf("") }
    var passwordSaving by remember { mutableStateOf(false) }
    var passwordMessage by remember { mutableStateOf("") }

    if (showPasswordDialog) {
        val isSetFlow = !userHasPassword
        AlertDialog(
            onDismissRequest = {
                if (!passwordSaving) {
                    showPasswordDialog = false
                    passwordText = ""
                    currentPasswordText = ""
                    passwordMessage = ""
                }
            },
            title = {
                Text(
                    if (isSetFlow) "Set Password" else "Change Password",
                    fontWeight = FontWeight.Bold
                )
            },
            text = {
                Column(
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text(
                        text = if (isSetFlow) {
                            "Choose a password (minimum 6 characters). You can use it next time you sign in."
                        } else {
                            "Enter your current password and a new password (minimum 6 characters):"
                        },
                        style = MaterialTheme.typography.bodyMedium
                    )
                    if (!isSetFlow) {
                        OutlinedTextField(
                            value = currentPasswordText,
                            onValueChange = { currentPasswordText = it },
                            label = { Text("Current Password") },
                            visualTransformation = PasswordVisualTransformation(),
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp),
                            enabled = !passwordSaving
                        )
                    }
                    OutlinedTextField(
                        value = passwordText,
                        onValueChange = { passwordText = it },
                        label = { Text(if (isSetFlow) "Password" else "New Password") },
                        visualTransformation = PasswordVisualTransformation(),
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp),
                        enabled = !passwordSaving
                    )
                    if (passwordMessage.isNotEmpty()) {
                        Text(
                            text = passwordMessage,
                            color = if (passwordMessage.startsWith("Success")) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.error,
                            style = MaterialTheme.typography.bodySmall
                        )
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        if (passwordText.length < 6) {
                            passwordMessage = "Password must be at least 6 characters."
                            return@Button
                        }
                        passwordSaving = true
                        passwordMessage = ""
                        scope.launch {
                            try {
                                val success = if (isSetFlow) {
                                    repository.setPassword(userId, passwordText)
                                } else {
                                    repository.changePassword(userId, passwordText, currentPasswordText)
                                }
                                if (success) {
                                    passwordMessage = if (isSetFlow) "Success! Password set." else "Success! Password updated."
                                    userHasPassword = true
                                    prefs.putBoolean("has_password", true)
                                    kotlinx.coroutines.delay(1000)
                                    showPasswordDialog = false
                                    passwordText = ""
                                    currentPasswordText = ""
                                    passwordMessage = ""
                                } else {
                                    passwordMessage = if (isSetFlow) "Failed to set password." else "Failed to update password."
                                }
                            } catch (t: Throwable) {
                                passwordMessage = t.message ?: if (isSetFlow) "Failed to set password." else "Failed to update password."
                            } finally {
                                passwordSaving = false
                            }
                        }
                    },
                    enabled = !passwordSaving && passwordText.isNotEmpty() && (isSetFlow || currentPasswordText.isNotEmpty())
                ) {
                    if (passwordSaving) {
                        CircularProgressIndicator(
                            color = MaterialTheme.colorScheme.onPrimary,
                            modifier = Modifier.size(16.dp)
                        )
                    } else {
                        Text(if (isSetFlow) "Save" else "Update")
                    }
                }
            },
            dismissButton = {
                TextButton(
                    onClick = {
                        showPasswordDialog = false
                        passwordText = ""
                        currentPasswordText = ""
                        passwordMessage = ""
                    },
                    enabled = !passwordSaving
                ) {
                    Text("Cancel")
                }
            }
        )
    }

    if (showFeedbackSheet) {
        val feedbackPalette = com.asloobulhayat.eternalecho.ui.home.rememberHomeThemePalette()
        com.asloobulhayat.eternalecho.ui.home.AppFeedbackBottomSheet(
            palette = feedbackPalette,
            onDismiss = {
                showFeedbackSheet = false
                prefs.putLong("feedback_dismissed_timestamp", System.currentTimeMillis())
            },
            onSubmitFeedback = { rating, category, message, onSuccess, onError ->
                scope.launch {
                    val result = repository.submitAppFeedback(
                        rating = rating,
                        category = category,
                        message = message,
                        userId = if (userId > 0) userId else null,
                        userName = userName.ifEmpty { null },
                        userEmail = userEmail.ifEmpty { null },
                        deviceInfo = "Android " + android.os.Build.VERSION.RELEASE
                    )
                    if (result.isSuccess) {
                        prefs.putBoolean("feedback_submitted", true)
                        onSuccess()
                    } else {
                        onError(result.exceptionOrNull()?.message ?: "Unable to submit feedback.")
                    }
                }
            }
        )
    }

    if (showAccountDialog) {
        AlertDialog(
            onDismissRequest = { showAccountDialog = false },
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
                    Text("Account Settings", fontWeight = FontWeight.Bold)
                }
            },
            text = {
                Column(
                    verticalArrangement = Arrangement.spacedBy(14.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Card(
                        colors = CardDefaults.cardColors(
                            containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                        ),
                        shape = RoundedCornerShape(12.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(modifier = Modifier.padding(14.dp)) {
                            Text(
                                text = userName.ifEmpty { "Registered User" },
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
                        }
                    }

                    Card(
                        colors = CardDefaults.cardColors(
                            containerColor = MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.4f)
                        ),
                        shape = RoundedCornerShape(12.dp),
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable {
                                try {
                                    val intent = Intent(Intent.ACTION_VIEW, Uri.parse(ApiClient.DONATION_URL))
                                    context.startActivity(intent)
                                } catch (_: Exception) {}
                            }
                    ) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(14.dp),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Row(
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.spacedBy(10.dp)
                            ) {
                                Text("🤲", fontSize = 20.sp)
                                Column {
                                    Text("Support Project", fontWeight = FontWeight.Bold)
                                    Text(
                                        text = "Sadaqah Jariyah & Donate",
                                        style = MaterialTheme.typography.bodySmall,
                                        color = MaterialTheme.colorScheme.primary
                                    )
                                }
                            }
                            Text(
                                text = "Donate",
                                style = MaterialTheme.typography.labelMedium,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.primary
                            )
                        }
                    }

                    Card(
                        colors = CardDefaults.cardColors(
                            containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                        ),
                        shape = RoundedCornerShape(12.dp),
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable {
                                showAccountDialog = false
                                showThemeSettingsDialog = true
                            }
                    ) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(14.dp),
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
                                    Text("App Theme", fontWeight = FontWeight.SemiBold)
                                    val currentThemeMode by ThemeManager.themeMode.collectAsState()
                                    Text(
                                        text = currentThemeMode.title,
                                        style = MaterialTheme.typography.bodySmall,
                                        color = MaterialTheme.colorScheme.primary
                                    )
                                }
                            }
                            Text(
                                text = "Change",
                                style = MaterialTheme.typography.labelMedium,
                                color = MaterialTheme.colorScheme.primary
                            )
                        }
                    }

                    Card(
                        colors = CardDefaults.cardColors(
                            containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                        ),
                        shape = RoundedCornerShape(12.dp),
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable {
                                showAccountDialog = false
                                showFeedbackSheet = true
                            }
                    ) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(14.dp),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Row(
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.spacedBy(10.dp)
                            ) {
                                Text("⭐", fontSize = 18.sp)
                                Column {
                                    Text("Send Feedback", fontWeight = FontWeight.SemiBold)
                                    Text(
                                        text = "Share suggestions or report issues",
                                        style = MaterialTheme.typography.bodySmall,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                }
                            }
                            Text(
                                text = "Rate",
                                style = MaterialTheme.typography.labelMedium,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.primary
                            )
                        }
                    }

                    OutlinedButton(
                        onClick = {
                            scope.launch {
                                try { repository.logout() } catch (_: Throwable) {}
                                clearLocalSession()
                                loginStateRefresh++
                            }
                        },
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp)
                    ) {
                        Icon(
                            Icons.AutoMirrored.Filled.ExitToApp,
                            contentDescription = null,
                            modifier = Modifier.size(18.dp)
                        )
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Logout")
                    }
                    OutlinedButton(
                        onClick = {
                            showAccountDialog = false
                            showPasswordDialog = true
                        },
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp)
                    ) {
                        Icon(
                            Icons.Default.Lock,
                            contentDescription = null,
                            modifier = Modifier.size(18.dp)
                        )
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Change Password")
                    }

                    OutlinedButton(
                        onClick = {
                            try {
                                val intent = Intent(Intent.ACTION_VIEW, Uri.parse(ApiClient.ACCOUNT_DELETE_URL))
                                context.startActivity(intent)
                            } catch (_: Exception) {}
                        },
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp)
                    ) {
                        Text("🌐 Open Web Deletion URL", fontSize = 13.sp)
                    }

                    HorizontalDivider()

                    Button(
                        onClick = {
                            deleteAccountError = ""
                            showDeleteAccountConfirmDialog = true
                        },
                        colors = ButtonDefaults.buttonColors(
                            containerColor = MaterialTheme.colorScheme.error
                        ),
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp)
                    ) {
                        Icon(
                            Icons.Default.Delete,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.onError,
                            modifier = Modifier.size(18.dp)
                        )
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Delete Account Permanently", color = MaterialTheme.colorScheme.onError)
                    }
                }
            },
            confirmButton = {},
            dismissButton = {
                TextButton(onClick = { showAccountDialog = false }) {
                    Text("Close")
                }
            }
        )
    }

    if (showDeleteAccountConfirmDialog) {
        AlertDialog(
            onDismissRequest = {
                if (!isDeletingAccount) {
                    showDeleteAccountConfirmDialog = false
                    deleteAccountError = ""
                }
            },
            title = {
                Text(
                    text = "Confirm Account Deletion",
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.error
                )
            },
            text = {
                Column(
                    verticalArrangement = Arrangement.spacedBy(8.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text(
                        text = "Are you sure you want to permanently delete your account?\n\n" +
                            "This action cannot be undone. All your quiz history, test submissions, bookmarks, and account data will be permanently wiped.\n\n" +
                            "If you set a password, enter it below to confirm.",
                        style = MaterialTheme.typography.bodyMedium
                    )
                    OutlinedTextField(
                        value = currentPasswordText,
                        onValueChange = { currentPasswordText = it },
                        label = { Text("Current password (if set)") },
                        singleLine = true,
                        visualTransformation = androidx.compose.ui.text.input.PasswordVisualTransformation(),
                        modifier = Modifier.fillMaxWidth()
                    )
                    if (deleteAccountError.isNotEmpty()) {
                        Text(
                            text = deleteAccountError,
                            color = MaterialTheme.colorScheme.error,
                            style = MaterialTheme.typography.bodySmall
                        )
                    }
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        isDeletingAccount = true
                        deleteAccountError = ""
                        scope.launch {
                            try {
                                val success = repository.deleteAccount(
                                    userId = userId,
                                    currentPassword = currentPasswordText,
                                    confirmDeletion = currentPasswordText.isBlank()
                                )
                                if (success) {
                                    clearLocalSession()
                                    prefs.remove("completed_quizzes")
                                    QuizSubmissionHelper.clearPendingQuiz(prefs)
                                    loginStateRefresh++
                                    showDeleteAccountConfirmDialog = false
                                    showAccountDialog = false
                                    android.widget.Toast.makeText(
                                        context,
                                        "Your account and data have been permanently deleted.",
                                        android.widget.Toast.LENGTH_LONG
                                    ).show()
                                } else {
                                    deleteAccountError = "Failed to delete account. Please try again."
                                }
                            } catch (t: Throwable) {
                                deleteAccountError = t.message ?: "Failed to delete account."
                            } finally {
                                isDeletingAccount = false
                            }
                        }
                    },
                    colors = ButtonDefaults.buttonColors(
                        containerColor = MaterialTheme.colorScheme.error
                    ),
                    enabled = !isDeletingAccount
                ) {
                    if (isDeletingAccount) {
                        CircularProgressIndicator(
                            color = MaterialTheme.colorScheme.onError,
                            modifier = Modifier.size(16.dp)
                        )
                    } else {
                        Text("Delete Forever", color = MaterialTheme.colorScheme.onError)
                    }
                }
            },
            dismissButton = {
                TextButton(
                    onClick = {
                        showDeleteAccountConfirmDialog = false
                        deleteAccountError = ""
                    },
                    enabled = !isDeletingAccount
                ) {
                    Text("Cancel")
                }
            }
        )
    }

    if (showNotificationsSheet) {
        NotificationsBottomSheet(
            notifications = notifications,
            onDismiss = { showNotificationsSheet = false },
            onMarkAsRead = { notificationRepo.markAsRead(it) },
            onMarkAllAsRead = { notificationRepo.markAllAsRead() }
        )
    }

    if (showThemeSettingsDialog) {
        ThemeSettingDialog(
            onDismiss = { showThemeSettingsDialog = false }
        )
    }

    val drawerState = rememberDrawerState(initialValue = DrawerValue.Closed)

    ModalNavigationDrawer(
        drawerState = drawerState,
        drawerContent = {
            ProfileDrawerContent(
                isLoggedIn = isLoggedIn,
                userId = userId,
                userName = userName,
                userEmail = userEmail,
                hasPassword = userHasPassword,
                onHasPasswordLoaded = { hasPw ->
                    userHasPassword = hasPw
                    prefs.putBoolean("has_password", hasPw)
                },
                onClose = { scope.launch { drawerState.close() } },
                onSignInClick = { onItemClick(AuthRoute) },
                onChangeThemeClick = { showThemeSettingsDialog = true },
                onChangePasswordClick = { showPasswordDialog = true },
                onLogoutClick = {
                    scope.launch {
                        try { repository.logout() } catch (_: Throwable) {}
                        clearLocalSession()
                        loginStateRefresh++
                    }
                },
                onDeleteAccountClick = {
                    deleteAccountError = ""
                    showDeleteAccountConfirmDialog = true
                },
                onSendFeedbackClick = { showFeedbackSheet = true },
                onSupportProjectClick = {
                    try {
                        val intent = Intent(Intent.ACTION_VIEW, Uri.parse(ApiClient.DONATION_URL))
                        context.startActivity(intent)
                    } catch (_: Exception) {}
                },
                onStreakUpdated = { updatedStreak ->
                    streakDays = updatedStreak.currentStreak
                    longestStreak = updatedStreak.longestStreak
                    isStreakActiveToday = updatedStreak.isActiveToday
                    prefs.putInt("current_streak", updatedStreak.currentStreak)
                    prefs.putInt("longest_streak", updatedStreak.longestStreak)
                    prefs.putBoolean("is_streak_active_today", updatedStreak.isActiveToday)
                }
            )
        }
    ) {
        AdaptiveNavigationScaffold(
            adaptiveInfo = adaptiveInfo,
            selectedTab = selectedTab,
            onTabSelected = {
                selectedTab = it
                onTabSelected(it)
            },
            topBar = {
                @OptIn(ExperimentalMaterial3Api::class)
                TopAppBar(
                    title = { Text("Eternal Echo", fontWeight = FontWeight.Bold) },
                    navigationIcon = {
                        IconButton(onClick = { scope.launch { drawerState.open() } }) {
                            Icon(Icons.Default.Menu, contentDescription = "Profile & Side Menu")
                        }
                    },
                    actions = {
                        IconButton(onClick = { showNotificationsSheet = true }) {
                            BadgedBox(
                                badge = {
                                    if (unreadCount > 0) {
                                        Badge(
                                            containerColor = MaterialTheme.colorScheme.error,
                                            contentColor = MaterialTheme.colorScheme.onError
                                        ) {
                                            Text(if (unreadCount > 9) "9+" else unreadCount.toString())
                                        }
                                    }
                                }
                            ) {
                                Icon(Icons.Default.Notifications, contentDescription = "Announcements")
                            }
                        }
                        IconButton(onClick = { onItemClick(OnboardingRoute) }) {
                            Icon(Icons.Default.Info, contentDescription = "App Guide")
                        }
                        if (isLoggedIn) {
                            IconButton(
                                onClick = { scope.launch { drawerState.open() } },
                                modifier = Modifier.padding(end = 8.dp)
                            ) {
                                Icon(
                                    Icons.Default.Person,
                                    contentDescription = "User Profile & Stats",
                                    modifier = Modifier.padding(end = 4.dp)
                                )
                            }
                        } else {
                            Button(
                                onClick = { onItemClick(AuthRoute) },
                                shape = RoundedCornerShape(8.dp)
                            ) {
                                Text("Sign In", fontSize = 12.sp)
                            }
                        }
                    },
                    colors = TopAppBarDefaults.topAppBarColors(
                        containerColor = MaterialTheme.colorScheme.surface.copy(alpha = 0.92f),
                        titleContentColor = MaterialTheme.colorScheme.onSurface,
                        navigationIconContentColor = MaterialTheme.colorScheme.onSurface,
                        actionIconContentColor = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                )
            }
        ) { paddingValues ->
        Box(
            modifier = modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            when (selectedTab) {
                0 -> HomeScreen(
                    userName = userName.ifBlank { "Wisdom Seeker" },
                    streakDays = streakDays,
                    longestStreak = longestStreak,
                    isStreakActiveToday = isStreakActiveToday,
                    currentJuz = 2,
                    readPages = 5,
                    readingProgressPercent = dailyEngagement?.progressPercent ?: 0,
                    dailyEngagement = dailyEngagement,
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
                    onEngagementActivityClick = { activityKey ->
                        when (activityKey) {
                            "quiz" -> onItemClick(ThemeQuizSelection)
                            "read" -> selectedTab = 5
                            "insight" -> selectedTab = 2
                            "dua" -> {
                                onItemClick(DuasRoute)
                                if (userId > 0) {
                                    scope.launch {
                                        repository.markEngagement(userId, "dua").getOrNull()?.let { engagement ->
                                            dailyEngagement = engagement
                                            streakDays = engagement.streak.currentStreak
                                            longestStreak = engagement.streak.longestStreak
                                            isStreakActiveToday = engagement.streak.isActiveToday
                                            prefs.putInt("current_streak", engagement.streak.currentStreak)
                                            prefs.putInt("longest_streak", engagement.streak.longestStreak)
                                            prefs.putBoolean("is_streak_active_today", engagement.streak.isActiveToday)
                                        }
                                    }
                                }
                            }
                        }
                    },
                    onStartQuizClick = { onItemClick(ThemeQuizSelection) },
                    onExploreHistoryClick = { selectedTab = 2 },
                    onDeepDiveClick = { selectedTab = 5 },
                    onReadDuasClick = {
                        onItemClick(DuasRoute)
                        if (userId > 0) {
                            scope.launch {
                                repository.markEngagement(userId, "dua").getOrNull()?.let { engagement ->
                                    dailyEngagement = engagement
                                    streakDays = engagement.streak.currentStreak
                                    longestStreak = engagement.streak.longestStreak
                                    isStreakActiveToday = engagement.streak.isActiveToday
                                }
                            }
                        }
                    },
                    onReflectClick = { onItemClick(SurahNavKey(2, 286)) },
                    onReadingProgressClick = { onItemClick(SurahNavKey(2, 142)) },
                    onThemeSettingsClick = { showThemeSettingsDialog = true },
                    onSubmitFeedback = { rating, category, message ->
                        repository.submitAppFeedback(
                            rating = rating,
                            category = category,
                            message = message,
                            userId = if (userId > 0) userId else null,
                            userName = userName.ifBlank { null },
                            userEmail = userEmail.ifBlank { null }
                        )
                    },
                    selectedBottomNavIndex = 0,
                    onBottomNavSelected = { navIdx ->
                        when (navIdx) {
                            0 -> selectedTab = 0
                            1 -> selectedTab = 10
                            2 -> onItemClick(ThemeQuizSelection)
                            3 -> if (isLoggedIn) scope.launch { drawerState.open() } else onItemClick(AuthRoute)
                        }
                    },
                    showBottomBar = false
                )
                10 -> SurahsTab(
                    viewModel = viewModel,
                    onSurahClick = { onItemClick(SurahNavKey(it.number)) },
                    onThemeQuizClick = { onItemClick(ThemeQuizSelection) },
                    onExploreLensClick = { category ->
                        if (AppConfig.IS_LENS_FEATURE_ENABLED) {
                            viewModel.loadAllConnections(category = category, search = "", page = 1)
                            selectedTab = 5
                        }
                    },
                    adaptiveInfo = adaptiveInfo
                )
                1 -> LeaderboardTab(
                    viewModel = viewModel,
                    adaptiveInfo = adaptiveInfo,
                    onThemeQuizClick = { onItemClick(ThemeQuizSelection) },
                )
                2 -> SeerahTab(
                    viewModel = viewModel,
                    onAuthClick = { onItemClick(AuthRoute) },
                    adaptiveInfo = adaptiveInfo
                )
                3 -> HistoryTab(
                    viewModel = viewModel,
                    onAuthClick = { onItemClick(AuthRoute) },
                    adaptiveInfo = adaptiveInfo
                )
                4 -> if (AppConfig.IS_DUAS_FEATURE_ENABLED) {
                    DuasContent(
                        repository = repository,
                        adaptiveInfo = adaptiveInfo,
                        onDuaClick = { dua -> onItemClick(com.asloobulhayat.eternalecho.DuaDetailRoute(dua.id, dua.slug)) }
                    )
                }
                5 -> if (AppConfig.IS_LENS_FEATURE_ENABLED) {
                    QuranicLensTab(
                        viewModel = viewModel,
                        onSurahClick = { surahNumber -> onItemClick(SurahNavKey(surahNumber)) },
                        repository = repository,
                        isLoggedIn = isLoggedIn,
                        userId = if (isLoggedIn) userId else null,
                        userName = userName,
                        userEmail = userEmail,
                        onAuthClick = { onItemClick(AuthRoute) },
                        adaptiveInfo = adaptiveInfo
                    )
                }
            }
        }
    }
}
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SurahsTab(
    viewModel: MainScreenViewModel,
    onSurahClick: (Surah) -> Unit,
    onThemeQuizClick: () -> Unit,
    onExploreLensClick: (category: String) -> Unit = {},
    adaptiveInfo: WindowAdaptiveInfo = rememberWindowAdaptiveInfo()
) {
    val state by viewModel.surahsState.collectAsStateWithLifecycle()
    var searchQuery by remember { mutableStateOf("") }
    var selectedSurahNumber by rememberSaveable { mutableIntStateOf(1) }

    when (val uiState = state) {
        is SurahsUiState.Loading -> {
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }
        }
        is SurahsUiState.Error -> {
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text("Error loading Surahs", color = MaterialTheme.colorScheme.error)
                    Spacer(modifier = Modifier.height(8.dp))
                    Button(onClick = { viewModel.loadSurahs() }) {
                        Text("Retry")
                    }
                }
            }
        }
        is SurahsUiState.Success -> {
            val filteredSurahs = uiState.data.filter {
                it.nameSimple.contains(searchQuery, ignoreCase = true) ||
                        it.nameTranslated.contains(searchQuery, ignoreCase = true) ||
                        it.number.toString() == searchQuery
            }

            val currentSelectedSurah = filteredSurahs.find { it.number == selectedSurahNumber }
                ?: filteredSurahs.firstOrNull() ?: uiState.data.firstOrNull()

            if (adaptiveInfo.isDualPane && currentSelectedSurah != null) {
                AdaptiveTwoPane(
                    adaptiveInfo = adaptiveInfo,
                    firstPane = {
                        Column(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(12.dp)
                        ) {
                            OutlinedTextField(
                                value = searchQuery,
                                onValueChange = { searchQuery = it },
                                placeholder = { Text("Search Surah...") },
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .testTag("search")
                                    .padding(bottom = 12.dp),
                                leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search") },
                                shape = RoundedCornerShape(12.dp),
                                singleLine = true
                            )
                            LazyColumn(
                                modifier = Modifier.fillMaxSize(),
                                verticalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                items(filteredSurahs, key = { it.number }) { surah ->
                                    val isSelected = surah.number == currentSelectedSurah.number
                                    SurahListItemCard(
                                        surah = surah,
                                        isSelected = isSelected,
                                        onClick = { selectedSurahNumber = surah.number },
                                        onOpenFullScreen = { onSurahClick(surah) }
                                    )
                                }
                            }
                        }
                    },
                    secondPane = {
                        Column(
                            modifier = Modifier
                                .fillMaxSize()
                                .padding(12.dp)
                        ) {
                            Card(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(bottom = 8.dp),
                                colors = CardDefaults.cardColors(
                                    containerColor = MaterialTheme.colorScheme.secondaryContainer
                                ),
                                shape = RoundedCornerShape(12.dp)
                            ) {
                                Row(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(horizontal = 14.dp, vertical = 8.dp),
                                    horizontalArrangement = Arrangement.SpaceBetween,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Column {
                                        Text(
                                            text = "Surah ${currentSelectedSurah.nameSimple} (${currentSelectedSurah.nameArabic})",
                                            style = MaterialTheme.typography.titleMedium,
                                            fontWeight = FontWeight.Bold,
                                            color = MaterialTheme.colorScheme.onSecondaryContainer
                                        )
                                        Text(
                                            text = "${currentSelectedSurah.versesCount} Verses • ${currentSelectedSurah.nameTranslated}",
                                            style = MaterialTheme.typography.labelSmall,
                                            color = MaterialTheme.colorScheme.onSecondaryContainer.copy(alpha = 0.8f)
                                        )
                                    }
                                    Button(
                                        onClick = { onSurahClick(currentSelectedSurah) },
                                        shape = RoundedCornerShape(8.dp),
                                        contentPadding = PaddingValues(horizontal = 10.dp, vertical = 4.dp)
                                    ) {
                                        Text("Full Screen", fontSize = 11.sp)
                                    }
                                }
                            }

                            SurahReaderContent(
                                surahNumber = currentSelectedSurah.number,
                                modifier = Modifier.fillMaxSize()
                            )
                        }
                    }
                )
            } else {
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(16.dp)
                ) {
                    OutlinedTextField(
                        value = searchQuery,
                        onValueChange = { searchQuery = it },
                        placeholder = { Text("Search Surah...") },
                        modifier = Modifier
                            .fillMaxWidth()
                            .testTag("search")
                            .padding(bottom = 16.dp),
                        leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search") },
                        shape = RoundedCornerShape(12.dp),
                        singleLine = true
                    )
                    LazyColumn(
                        modifier = Modifier.fillMaxSize(),
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        items(filteredSurahs, key = { it.number }) { surah ->
                            SurahListItemCard(
                                surah = surah,
                                isSelected = false,
                                onClick = { onSurahClick(surah) }
                            )
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun SurahThematicQuizCard(onThemeQuizClick: () -> Unit) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(bottom = 14.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.secondaryContainer,
            contentColor = MaterialTheme.colorScheme.onSecondaryContainer
        ),
        shape = RoundedCornerShape(16.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = "Thematic & Grand Quizzes",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold
                )
                Spacer(modifier = Modifier.height(2.dp))
                Text(
                    text = "Practice Quran & Seerah themes or take the 20, 50, 100 questions Grand Quiz.",
                    style = MaterialTheme.typography.bodySmall
                )
            }
            Button(
                onClick = onThemeQuizClick,
                shape = RoundedCornerShape(8.dp)
            ) {
                Text("Explore", fontSize = 12.sp)
            }
        }
    }
}

@Composable
fun SurahListItemCard(
    surah: Surah,
    isSelected: Boolean,
    onClick: () -> Unit,
    onOpenFullScreen: (() -> Unit)? = null
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { onClick() },
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(
            containerColor = if (isSelected) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surfaceVariant
        ),
        border = if (isSelected) BorderStroke(1.5.dp, MaterialTheme.colorScheme.primary) else null
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(14.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.weight(1f)) {
                Box(
                    modifier = Modifier.size(38.dp),
                    contentAlignment = Alignment.Center
                ) {
                    Badge(containerColor = MaterialTheme.colorScheme.primary) {
                        Text(
                            text = surah.number.toString(),
                            color = MaterialTheme.colorScheme.onPrimary,
                            fontWeight = FontWeight.Bold
                        )
                    }
                }
                Spacer(modifier = Modifier.width(12.dp))
                Column {
                    Text(
                        text = surah.nameSimple,
                        style = MaterialTheme.typography.bodyLarge,
                        fontWeight = FontWeight.Bold
                    )
                    Text(
                        text = surah.nameTranslated,
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }
            Column(horizontalAlignment = Alignment.End) {
                Text(
                    text = surah.nameArabic,
                    fontSize = 20.sp,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary
                )
                Text(
                    text = "${surah.versesCount} Verses",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        }
    }
}

@Composable
fun LeaderboardTab(
    viewModel: MainScreenViewModel,
    adaptiveInfo: WindowAdaptiveInfo = rememberWindowAdaptiveInfo(),
    onThemeQuizClick: () -> Unit
) {
    val state by viewModel.leaderboardState.collectAsStateWithLifecycle()

    Box(
        modifier = Modifier.fillMaxSize(),
        contentAlignment = Alignment.TopCenter
    ) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .widthIn(max = 760.dp)
                .padding(horizontal = if (adaptiveInfo.widthClass != WindowWidthClass.Compact) 16.dp else 0.dp)
        ) {
            SurahThematicQuizCard(onThemeQuizClick)
        when (val uiState = state) {
            is LeaderboardUiState.Loading -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            }
            is LeaderboardUiState.Error -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("Error loading Leaderboard", color = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(onClick = { viewModel.loadLeaderboard() }) {
                            Text("Retry")
                        }
                    }
                }
            }
            is LeaderboardUiState.Success -> {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    items(uiState.data.withIndex().toList()) { (index, user) ->
                        if (user.totalScore == 0) return@items
                        Card(
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp),
                            colors = CardDefaults.cardColors(
                                containerColor = if (index < 3) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surfaceVariant
                            )
                        ) {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(16.dp),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Text(
                                        text = "#${index + 1}",
                                        style = MaterialTheme.typography.titleMedium,
                                        fontWeight = FontWeight.Bold,
                                        modifier = Modifier.width(36.dp),
                                        color = if (index < 3) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                    Column {
                                        Text(
                                            text = if (user.displayName.isNotEmpty() && user.displayName != "null") user.displayName else if (user.name.isNotEmpty() && user.name != "null") user.name else "User",
                                            style = MaterialTheme.typography.bodyLarge,
                                            fontWeight = FontWeight.Bold
                                        )
                                        Text(
                                            text = "${user.totalQuestions} Questions",
                                            style = MaterialTheme.typography.labelSmall,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant
                                        )
                                    }
                                }
                                Column(horizontalAlignment = Alignment.End) {
                                    Text(
                                        text = "${user.totalScore} pts",
                                        style = MaterialTheme.typography.bodyLarge,
                                        fontWeight = FontWeight.Bold,
                                        color = MaterialTheme.colorScheme.primary
                                    )

                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
}

@Composable
fun SeerahItem(event: SeerahEvent, onAuthClick: () -> Unit) {
    val context = LocalContext.current
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Top
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = event.title,
                        style = MaterialTheme.typography.bodyLarge,
                        fontWeight = FontWeight.Bold
                    )

                    if (event.category.isNotEmpty()) {
                        Spacer(modifier = Modifier.height(8.dp))
                        Badge(containerColor = MaterialTheme.colorScheme.secondaryContainer) {
                            Text(
                                text = event.category,
                                modifier = Modifier.padding(horizontal = 4.dp, vertical = 1.dp),
                                style = MaterialTheme.typography.labelSmall
                            )
                        }
                    }
                }
                IconButton(
                    onClick = {
                        ShareHelper.shareSeerahEvent(
                            context = context,
                            title = event.title,
                            description = event.description,
                            category = event.category
                        )
                    },
                    modifier = Modifier.size(32.dp)
                ) {
                    Icon(
                        imageVector = Icons.Default.Share,
                        contentDescription = "Share Seerah Story",
                        modifier = Modifier.size(18.dp),
                        tint = MaterialTheme.colorScheme.primary
                    )
                }
            }
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = event.description,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )

            if (event.questionText.isNotEmpty() && event.options.isNotEmpty()) {
                Spacer(modifier = Modifier.height(16.dp))
                HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
                Spacer(modifier = Modifier.height(12.dp))

                val context = LocalContext.current
                val prefs = remember { SecurePreferences.getInstance(context) }
                var completedQuizzes by remember { mutableIntStateOf(prefs.getInt("completed_quizzes", 0)) }
                var showLimitDialog by remember { mutableStateOf(false) }
                val scope = rememberCoroutineScope()

                if (showLimitDialog) {
                    AlertDialog(
                        onDismissRequest = { showLimitDialog = false },
                        title = { Text("Login Required") },
                        text = { Text("You have completed 1 free guest quiz. Please sign in or create an account to unlock unlimited quizzes!") },
                        confirmButton = {
                            Button(onClick = {
                                showLimitDialog = false
                                onAuthClick()
                            }) {
                                Text("Sign In")
                            }
                        },
                        dismissButton = {
                            TextButton(onClick = { showLimitDialog = false }) {
                                Text("Cancel")
                            }
                        }
                    )
                }

                var selectedOptionIndex by remember { mutableStateOf<Int?>(null) }
                var revealedCorrectIndex by remember { mutableIntStateOf(-1) }
                var revealedExplanation by remember { mutableStateOf("") }
                val hasAnswered = selectedOptionIndex != null
                val repository = remember { DefaultDataRepository() }

                Text(
                    text = "Knowledge Check",
                    style = MaterialTheme.typography.labelSmall,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.padding(bottom = 4.dp)
                )

                Text(
                    text = event.questionText,
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.padding(bottom = 12.dp)
                )

                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    for (index in event.options.indices) {
                        val option = event.options[index]
                        val isCorrectOption = revealedCorrectIndex >= 0 && index == revealedCorrectIndex
                        val isSelectedOption = index == selectedOptionIndex

                        val containerColor = when {
                            !hasAnswered -> MaterialTheme.colorScheme.surface
                            isCorrectOption -> MaterialTheme.colorScheme.primaryContainer
                            isSelectedOption && revealedCorrectIndex >= 0 -> MaterialTheme.colorScheme.errorContainer
                            isSelectedOption -> MaterialTheme.colorScheme.secondaryContainer
                            else -> MaterialTheme.colorScheme.surface
                        }

                        val contentColor = when {
                            !hasAnswered -> MaterialTheme.colorScheme.onSurface
                            isCorrectOption -> MaterialTheme.colorScheme.onPrimaryContainer
                            isSelectedOption && revealedCorrectIndex >= 0 -> MaterialTheme.colorScheme.onErrorContainer
                            isSelectedOption -> MaterialTheme.colorScheme.onSecondaryContainer
                            else -> MaterialTheme.colorScheme.onSurface
                        }

                        val borderColor = when {
                            !hasAnswered -> MaterialTheme.colorScheme.outline
                            isCorrectOption -> MaterialTheme.colorScheme.primary
                            isSelectedOption && revealedCorrectIndex >= 0 -> MaterialTheme.colorScheme.error
                            isSelectedOption -> MaterialTheme.colorScheme.secondary
                            else -> MaterialTheme.colorScheme.outlineVariant
                        }

                        OutlinedButton(
                            onClick = {
                                if (!hasAnswered) {
                                    val userId = prefs.getInt("user_id", -1)
                                    val token = prefs.getString("api_token", null).orEmpty()
                                    val currentIsLoggedIn = userId > 0 && token.isNotBlank()
                                    val currentCompleted = prefs.getInt("completed_quizzes", 0)
                                    if (!currentIsLoggedIn && currentCompleted >= 1) {
                                        showLimitDialog = true
                                    } else {
                                        selectedOptionIndex = index
                                        if (!currentIsLoggedIn) {
                                            prefs.putInt("completed_quizzes", currentCompleted + 1)
                                            completedQuizzes = currentCompleted + 1
                                        } else {
                                            scope.launch {
                                                try {
                                                    val qid = event.questionId.ifBlank { event.id.toString() }
                                                    if (event.questionId.isNotBlank()) {
                                                        repository.checkAnswer(
                                                            event.questionId,
                                                            index,
                                                            event.options
                                                        )?.let { reveal ->
                                                            revealedCorrectIndex = reveal.correctAnswerIndex
                                                            revealedExplanation = reveal.explanation
                                                        }
                                                    }
                                                    repository.markInsight(userId, "SEERAH", qid)
                                                } catch (e: Exception) {
                                                    android.util.Log.e("SeerahItem", "Failed to reveal/mark insight", e)
                                                }
                                            }
                                        }
                                    }
                                }
                            },
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(8.dp),
                            colors = ButtonDefaults.outlinedButtonColors(
                                containerColor = containerColor,
                                contentColor = contentColor
                            ),
                            border = BorderStroke(1.dp, borderColor)
                        ) {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text(
                                    text = "${(65 + index).toChar()}. $option",
                                    style = MaterialTheme.typography.bodyMedium,
                                    fontWeight = if (isSelectedOption) FontWeight.Bold else FontWeight.Normal
                                )
                                if (hasAnswered && revealedCorrectIndex >= 0) {
                                    if (isCorrectOption) {
                                        Text("✓", fontWeight = FontWeight.Bold)
                                    } else if (isSelectedOption) {
                                        Text("✗", fontWeight = FontWeight.Bold)
                                    }
                                }
                            }
                        }
                    }
                }

                if (hasAnswered && revealedExplanation.isNotEmpty()) {
                    Spacer(modifier = Modifier.height(12.dp))
                    Surface(
                        color = MaterialTheme.colorScheme.tertiaryContainer,
                        contentColor = MaterialTheme.colorScheme.onTertiaryContainer,
                        shape = RoundedCornerShape(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(modifier = Modifier.padding(12.dp)) {
                            Text(
                                text = "Deep Insight",
                                style = MaterialTheme.typography.labelSmall,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.tertiary
                            )
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = revealedExplanation,
                                style = MaterialTheme.typography.bodySmall
                            )
                        }
                    }
                }

                if (hasAnswered && prefs.getInt("user_id", -1) == -1) {
                    Spacer(modifier = Modifier.height(10.dp))
                    Button(
                        onClick = {
                            onAuthClick()
                        },
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Text("Sign Up to Save Score & Join Leaderboard")
                    }
                }
            }
        }
    }
}

@Composable
fun HistoryItem(event: HistoryEvent, onAuthClick: () -> Unit) {
    val context = LocalContext.current
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Top
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = event.title,
                        style = MaterialTheme.typography.bodyLarge,
                        fontWeight = FontWeight.Bold
                    )

                    if (event.category.isNotEmpty()) {
                        Spacer(modifier = Modifier.height(8.dp))
                        Badge(containerColor = MaterialTheme.colorScheme.secondaryContainer) {
                            Text(
                                text = event.category,
                                modifier = Modifier.padding(horizontal = 4.dp, vertical = 1.dp),
                                style = MaterialTheme.typography.labelSmall
                            )
                        }
                    }
                }
                IconButton(
                    onClick = {
                        ShareHelper.shareHistoryEvent(
                            context = context,
                            title = event.title,
                            description = event.description,
                            category = event.category
                        )
                    },
                    modifier = Modifier.size(32.dp)
                ) {
                    Icon(
                        imageVector = Icons.Default.Share,
                        contentDescription = "Share History Milestone",
                        modifier = Modifier.size(18.dp),
                        tint = MaterialTheme.colorScheme.primary
                    )
                }
            }
            Spacer(modifier = Modifier.height(8.dp))
            Text(
                text = event.description,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )

            if (event.questionText.isNotEmpty() && event.options.isNotEmpty()) {
                Spacer(modifier = Modifier.height(16.dp))
                HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant)
                Spacer(modifier = Modifier.height(12.dp))

                val context = LocalContext.current
                val prefs = remember { SecurePreferences.getInstance(context) }
                var completedQuizzes by remember { mutableIntStateOf(prefs.getInt("completed_quizzes", 0)) }
                var showLimitDialog by remember { mutableStateOf(false) }
                val scope = rememberCoroutineScope()

                if (showLimitDialog) {
                    AlertDialog(
                        onDismissRequest = { showLimitDialog = false },
                        title = { Text("Login Required") },
                        text = { Text("You have completed 1 free guest quiz. Please sign in or create an account to unlock unlimited quizzes!") },
                        confirmButton = {
                            Button(onClick = {
                                showLimitDialog = false
                                onAuthClick()
                            }) {
                                Text("Sign In")
                            }
                        },
                        dismissButton = {
                            TextButton(onClick = { showLimitDialog = false }) {
                                Text("Cancel")
                            }
                        }
                    )
                }

                var selectedOptionIndex by remember { mutableStateOf<Int?>(null) }
                var revealedCorrectIndex by remember { mutableIntStateOf(-1) }
                var revealedExplanation by remember { mutableStateOf("") }
                val hasAnswered = selectedOptionIndex != null
                val repository = remember { DefaultDataRepository() }

                Text(
                    text = "Knowledge Check",
                    style = MaterialTheme.typography.labelSmall,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.padding(bottom = 4.dp)
                )

                Text(
                    text = event.questionText,
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.padding(bottom = 12.dp)
                )

                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    for (index in event.options.indices) {
                        val option = event.options[index]
                        val isCorrectOption = revealedCorrectIndex >= 0 && index == revealedCorrectIndex
                        val isSelectedOption = index == selectedOptionIndex

                        val containerColor = when {
                            !hasAnswered -> MaterialTheme.colorScheme.surface
                            isCorrectOption -> MaterialTheme.colorScheme.primaryContainer
                            isSelectedOption && revealedCorrectIndex >= 0 -> MaterialTheme.colorScheme.errorContainer
                            isSelectedOption -> MaterialTheme.colorScheme.secondaryContainer
                            else -> MaterialTheme.colorScheme.surface
                        }

                        val contentColor = when {
                            !hasAnswered -> MaterialTheme.colorScheme.onSurface
                            isCorrectOption -> MaterialTheme.colorScheme.onPrimaryContainer
                            isSelectedOption && revealedCorrectIndex >= 0 -> MaterialTheme.colorScheme.onErrorContainer
                            isSelectedOption -> MaterialTheme.colorScheme.onSecondaryContainer
                            else -> MaterialTheme.colorScheme.onSurface
                        }

                        val borderColor = when {
                            !hasAnswered -> MaterialTheme.colorScheme.outline
                            isCorrectOption -> MaterialTheme.colorScheme.primary
                            isSelectedOption && revealedCorrectIndex >= 0 -> MaterialTheme.colorScheme.error
                            isSelectedOption -> MaterialTheme.colorScheme.secondary
                            else -> MaterialTheme.colorScheme.outlineVariant
                        }

                        OutlinedButton(
                            onClick = {
                                if (!hasAnswered) {
                                    val userId = prefs.getInt("user_id", -1)
                                    val token = prefs.getString("api_token", null).orEmpty()
                                    val currentIsLoggedIn = userId > 0 && token.isNotBlank()
                                    val currentCompleted = prefs.getInt("completed_quizzes", 0)
                                    if (!currentIsLoggedIn && currentCompleted >= 1) {
                                        showLimitDialog = true
                                    } else {
                                        selectedOptionIndex = index
                                        if (!currentIsLoggedIn) {
                                            prefs.putInt("completed_quizzes", currentCompleted + 1)
                                            completedQuizzes = currentCompleted + 1
                                        } else {
                                            scope.launch {
                                                try {
                                                    val qid = event.questionId.ifBlank { event.id.toString() }
                                                    if (event.questionId.isNotBlank()) {
                                                        repository.checkAnswer(
                                                            event.questionId,
                                                            index,
                                                            event.options
                                                        )?.let { reveal ->
                                                            revealedCorrectIndex = reveal.correctAnswerIndex
                                                            revealedExplanation = reveal.explanation
                                                        }
                                                    }
                                                    repository.markInsight(userId, "QURAN_HISTORY", qid)
                                                } catch (e: Exception) {
                                                    android.util.Log.e("HistoryItem", "Failed to reveal/mark insight", e)
                                                }
                                            }
                                        }
                                    }
                                }
                            },
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(8.dp),
                            colors = ButtonDefaults.outlinedButtonColors(
                                containerColor = containerColor,
                                contentColor = contentColor
                            ),
                            border = BorderStroke(1.dp, borderColor)
                        ) {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text(
                                    text = "${(65 + index).toChar()}. $option",
                                    style = MaterialTheme.typography.bodyMedium,
                                    fontWeight = if (isSelectedOption) FontWeight.Bold else FontWeight.Normal
                                )
                                if (hasAnswered && revealedCorrectIndex >= 0) {
                                    if (isCorrectOption) {
                                        Text("✓", fontWeight = FontWeight.Bold)
                                    } else if (isSelectedOption) {
                                        Text("✗", fontWeight = FontWeight.Bold)
                                    }
                                }
                            }
                        }
                    }
                }

                if (hasAnswered && revealedExplanation.isNotEmpty()) {
                    Spacer(modifier = Modifier.height(12.dp))
                    Surface(
                        color = MaterialTheme.colorScheme.tertiaryContainer,
                        contentColor = MaterialTheme.colorScheme.onTertiaryContainer,
                        shape = RoundedCornerShape(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(modifier = Modifier.padding(12.dp)) {
                            Text(
                                text = "Deep Insight",
                                style = MaterialTheme.typography.labelSmall,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.tertiary
                            )
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = revealedExplanation,
                                style = MaterialTheme.typography.bodySmall
                            )
                        }
                    }
                }

                if (hasAnswered && prefs.getInt("user_id", -1) == -1) {
                    Spacer(modifier = Modifier.height(10.dp))
                    Button(
                        onClick = { onAuthClick() },
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Text("Sign Up to Save Score & Join Leaderboard")
                    }
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CategoryDropdownSelector(
    label: String = "Category",
    categories: List<String>,
    selectedCategory: String,
    onCategorySelected: (String) -> Unit,
    modifier: Modifier = Modifier
) {
    var expanded by remember { mutableStateOf(false) }
    val displayLabel = if (selectedCategory.isEmpty()) "All" else selectedCategory

    ExposedDropdownMenuBox(
        expanded = expanded,
        onExpandedChange = { expanded = !expanded },
        modifier = modifier
            .fillMaxWidth()
            .padding(bottom = 12.dp)
    ) {
        OutlinedTextField(
            value = displayLabel,
            onValueChange = {},
            readOnly = true,
            label = { Text(label) },
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
            colors = ExposedDropdownMenuDefaults.outlinedTextFieldColors(),
            shape = RoundedCornerShape(12.dp),
            modifier = Modifier
                .menuAnchor(MenuAnchorType.PrimaryNotEditable)
                .fillMaxWidth()
        )

        ExposedDropdownMenu(
            expanded = expanded,
            onDismissRequest = { expanded = false }
        ) {
            categories.forEach { category ->
                val isSelected = (category == "All" && selectedCategory.isEmpty()) ||
                        (category == selectedCategory)

                DropdownMenuItem(
                    text = {
                        Text(
                            text = category,
                            fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal,
                            color = if (isSelected) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurface
                        )
                    },
                    onClick = {
                        val filterVal = if (category == "All") "" else category
                        onCategorySelected(filterVal)
                        expanded = false
                    }
                )
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SeerahTab(
    viewModel: MainScreenViewModel,
    onAuthClick: () -> Unit,
    adaptiveInfo: WindowAdaptiveInfo = rememberWindowAdaptiveInfo()
) {
    val state by viewModel.insightsState.collectAsStateWithLifecycle()
    var selectedEventId by rememberSaveable { mutableIntStateOf(-1) }

    Column(modifier = Modifier.fillMaxSize()) {
        Text(
            text = "Seerah & Historical Insights",
            style = MaterialTheme.typography.headlineMedium,
            fontWeight = FontWeight.Bold,
            modifier = Modifier.padding(bottom = 16.dp)
        )

        when (val uiState = state) {
            is InsightsUiState.Loading -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            }
            is InsightsUiState.Error -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("Error loading Seerah", color = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(onClick = { viewModel.loadInsights() }) {
                            Text("Retry")
                        }
                    }
                }
            }
            is InsightsUiState.Success -> {
                val categories = listOf("All") + uiState.data.seerahCategories
                val selectedEvent = uiState.data.seerahEvents.find { it.id == selectedEventId }
                    ?: uiState.data.seerahEvents.firstOrNull()

                if (adaptiveInfo.isDualPane && selectedEvent != null) {
                    AdaptiveTwoPane(
                        adaptiveInfo = adaptiveInfo,
                        firstPane = {
                            Column(modifier = Modifier
                                .fillMaxSize()
                                .padding(12.dp)) {
                                CategoryDropdownSelector(
                                    label = "Category",
                                    categories = categories,
                                    selectedCategory = viewModel.currentSeerahCategory,
                                    onCategorySelected = { viewModel.filterSeerahByCategory(it) }
                                )

                                LazyColumn(
                                    modifier = Modifier.fillMaxSize(),
                                    verticalArrangement = Arrangement.spacedBy(10.dp)
                                ) {
                                    if (uiState.data.seerahEvents.isEmpty()) {
                                        item {
                                            Box(
                                                modifier = Modifier
                                                    .fillMaxWidth()
                                                    .padding(24.dp),
                                                contentAlignment = Alignment.Center
                                            ) {
                                                Text(
                                                    text = "No events found for this category.",
                                                    style = MaterialTheme.typography.bodyMedium,
                                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                                )
                                            }
                                        }
                                    } else {
                                        items(uiState.data.seerahEvents, key = { it.id }) { event ->
                                            val isSelected = event.id == selectedEvent.id
                                            Card(
                                                modifier = Modifier
                                                    .fillMaxWidth()
                                                    .clickable { selectedEventId = event.id },
                                                shape = RoundedCornerShape(12.dp),
                                                colors = CardDefaults.cardColors(
                                                    containerColor = if (isSelected) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surfaceVariant
                                                ),
                                                border = if (isSelected) BorderStroke(1.5.dp, MaterialTheme.colorScheme.primary) else null
                                            ) {
                                                Column(modifier = Modifier.padding(12.dp)) {
                                                    Text(
                                                        text = event.title,
                                                        style = MaterialTheme.typography.titleSmall,
                                                        fontWeight = FontWeight.Bold,
                                                        color = if (isSelected) MaterialTheme.colorScheme.onPrimaryContainer else MaterialTheme.colorScheme.onSurface
                                                    )
                                                    if (event.category.isNotEmpty()) {
                                                        Spacer(modifier = Modifier.height(4.dp))
                                                        Badge(containerColor = MaterialTheme.colorScheme.secondaryContainer) {
                                                            Text(
                                                                text = event.category,
                                                                modifier = Modifier.padding(horizontal = 4.dp, vertical = 1.dp),
                                                                style = MaterialTheme.typography.labelSmall
                                                            )
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    item {
                                        Row(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .padding(vertical = 8.dp),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Button(
                                                onClick = { viewModel.prevPageSeerah() },
                                                enabled = uiState.data.seerahPage > 1
                                            ) {
                                                Text("Previous")
                                            }
                                            Text(
                                                text = "Page ${uiState.data.seerahPage} of ${uiState.data.seerahTotalPages}",
                                                style = MaterialTheme.typography.bodySmall,
                                                fontWeight = FontWeight.Bold
                                            )
                                            Button(
                                                onClick = { viewModel.nextPageSeerah() },
                                                enabled = uiState.data.seerahPage < uiState.data.seerahTotalPages
                                            ) {
                                                Text("Next")
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
                                        SeerahItem(selectedEvent, onAuthClick)
                                    }
                                }
                            }
                        }
                    )
                } else {
                    CategoryDropdownSelector(
                        label = "Category",
                        categories = categories,
                        selectedCategory = viewModel.currentSeerahCategory,
                        onCategorySelected = { viewModel.filterSeerahByCategory(it) }
                    )

                    LazyColumn(
                        modifier = Modifier.fillMaxSize(),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        if (uiState.data.seerahEvents.isEmpty()) {
                            item {
                                Box(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(32.dp),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Text(
                                        text = "No events found for this category.",
                                        style = MaterialTheme.typography.bodyMedium,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                }
                            }
                        } else {
                            items(uiState.data.seerahEvents) { event ->
                                SeerahItem(event, onAuthClick)
                            }
                        }

                        item {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(vertical = 8.dp),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Button(
                                    onClick = { viewModel.prevPageSeerah() },
                                    enabled = uiState.data.seerahPage > 1
                                ) {
                                    Text("Previous")
                                }
                                Text(
                                    text = "Page ${uiState.data.seerahPage} of ${uiState.data.seerahTotalPages}",
                                    style = MaterialTheme.typography.bodyMedium,
                                    fontWeight = FontWeight.Bold
                                )
                                Button(
                                    onClick = { viewModel.nextPageSeerah() },
                                    enabled = uiState.data.seerahPage < uiState.data.seerahTotalPages
                                ) {
                                    Text("Next")
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HistoryTab(
    viewModel: MainScreenViewModel,
    onAuthClick: () -> Unit,
    adaptiveInfo: WindowAdaptiveInfo = rememberWindowAdaptiveInfo()
) {
    val state by viewModel.insightsState.collectAsStateWithLifecycle()
    var selectedEventId by rememberSaveable { mutableIntStateOf(-1) }

    Column(modifier = Modifier
        .fillMaxSize()
        .padding(horizontal = 4.dp)) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 8.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Text(
                text = "History Insights",
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold
            )
        }

        when (val uiState = state) {
            is InsightsUiState.Loading -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
            }
            is InsightsUiState.Error -> {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        Text("Error loading History", color = MaterialTheme.colorScheme.error)
                        Spacer(modifier = Modifier.height(8.dp))
                        Button(onClick = { viewModel.loadInsights() }) {
                            Text("Retry")
                        }
                    }
                }
            }
            is InsightsUiState.Success -> {
                val categories = listOf("All") + uiState.data.historyCategories
                val selectedEvent = uiState.data.historyEvents.find { it.id == selectedEventId }
                    ?: uiState.data.historyEvents.firstOrNull()

                if (adaptiveInfo.isDualPane && selectedEvent != null) {
                    AdaptiveTwoPane(
                        adaptiveInfo = adaptiveInfo,
                        firstPane = {
                            Column(modifier = Modifier
                                .fillMaxSize()
                                .padding(12.dp)) {
                                CategoryDropdownSelector(
                                    label = "Category",
                                    categories = categories,
                                    selectedCategory = viewModel.currentHistoryCategory,
                                    onCategorySelected = { viewModel.filterHistoryByCategory(it) }
                                )

                                LazyColumn(
                                    modifier = Modifier.fillMaxSize(),
                                    verticalArrangement = Arrangement.spacedBy(10.dp)
                                ) {
                                    if (uiState.data.historyEvents.isEmpty()) {
                                        item {
                                            Box(
                                                modifier = Modifier
                                                    .fillMaxWidth()
                                                    .padding(24.dp),
                                                contentAlignment = Alignment.Center
                                            ) {
                                                Text(
                                                    text = "No events found for this category.",
                                                    style = MaterialTheme.typography.bodyMedium,
                                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                                )
                                            }
                                        }
                                    } else {
                                        items(uiState.data.historyEvents, key = { it.id }) { event ->
                                            val isSelected = event.id == selectedEvent.id
                                            Card(
                                                modifier = Modifier
                                                    .fillMaxWidth()
                                                    .clickable { selectedEventId = event.id },
                                                shape = RoundedCornerShape(12.dp),
                                                colors = CardDefaults.cardColors(
                                                    containerColor = if (isSelected) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surfaceVariant
                                                ),
                                                border = if (isSelected) BorderStroke(1.5.dp, MaterialTheme.colorScheme.primary) else null
                                            ) {
                                                Column(modifier = Modifier.padding(12.dp)) {
                                                    Text(
                                                        text = event.title,
                                                        style = MaterialTheme.typography.titleSmall,
                                                        fontWeight = FontWeight.Bold,
                                                        color = if (isSelected) MaterialTheme.colorScheme.onPrimaryContainer else MaterialTheme.colorScheme.onSurface
                                                    )
                                                    if (event.category.isNotEmpty()) {
                                                        Spacer(modifier = Modifier.height(4.dp))
                                                        Badge(containerColor = MaterialTheme.colorScheme.secondaryContainer) {
                                                            Text(
                                                                text = event.category,
                                                                modifier = Modifier.padding(horizontal = 4.dp, vertical = 1.dp),
                                                                style = MaterialTheme.typography.labelSmall
                                                            )
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    item {
                                        Row(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .padding(vertical = 8.dp),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Button(
                                                onClick = { viewModel.prevPageHistory() },
                                                enabled = uiState.data.historyPage > 1
                                            ) {
                                                Text("Previous")
                                            }
                                            Text(
                                                text = "Page ${uiState.data.historyPage} of ${uiState.data.historyTotalPages}",
                                                style = MaterialTheme.typography.bodySmall,
                                                fontWeight = FontWeight.Bold
                                            )
                                            Button(
                                                onClick = { viewModel.nextPageHistory() },
                                                enabled = uiState.data.historyPage < uiState.data.historyTotalPages
                                            ) {
                                                Text("Next")
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
                                        HistoryItem(selectedEvent, onAuthClick)
                                    }
                                }
                            }
                        }
                    )
                } else {
                    CategoryDropdownSelector(
                        label = "Category",
                        categories = categories,
                        selectedCategory = viewModel.currentHistoryCategory,
                        onCategorySelected = { viewModel.filterHistoryByCategory(it) }
                    )

                    LazyColumn(
                        modifier = Modifier.fillMaxSize(),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        if (uiState.data.historyEvents.isEmpty()) {
                            item {
                                Box(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(32.dp),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Text(
                                        text = "No events found for this category.",
                                        style = MaterialTheme.typography.bodyMedium,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                }
                            }
                        } else {
                            items(uiState.data.historyEvents) { event ->
                                HistoryItem(event, onAuthClick)
                            }
                        }

                        item {
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(vertical = 8.dp),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Button(
                                    onClick = { viewModel.prevPageHistory() },
                                    enabled = uiState.data.historyPage > 1
                                ) {
                                    Text("Previous")
                                }
                                Text(
                                    text = "Page ${uiState.data.historyPage} of ${uiState.data.historyTotalPages}",
                                    style = MaterialTheme.typography.bodyMedium,
                                    fontWeight = FontWeight.Bold
                                )
                                Button(
                                    onClick = { viewModel.nextPageHistory() },
                                    enabled = uiState.data.historyPage < uiState.data.historyTotalPages
                                ) {
                                    Text("Next")
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun QuranicLensBannerCard(
    onExploreLensClick: (category: String) -> Unit,
    stats: ConnectionStats?,
    modifier: Modifier = Modifier
) {
    Card(
        modifier = modifier
            .fillMaxWidth()
            .padding(bottom = 12.dp)
            .clickable { onExploreLensClick("all") },
        shape = RoundedCornerShape(18.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surface.copy(alpha = 0.94f)
        ),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.55f)),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            Text(
                text = "Quranic Lens",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface
            )
            Text(
                text = "Cross-links between the Quran and science, Seerah, Hadith, history, and scripture.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                lineHeight = 18.sp
            )
            FlowRow(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(6.dp),
                verticalArrangement = Arrangement.spacedBy(6.dp)
            ) {
                StatsChip(count = "${stats?.surahsCount ?: 114}", label = "Surahs")
                StatsChip(count = "${stats?.scienceCount ?: 3954}", label = "Science")
                StatsChip(count = "${stats?.seerahCount ?: 477}", label = "Seerah")
                StatsChip(count = "${stats?.historyCount ?: 1916}", label = "History")
            }
            Text(
                text = "Open Lens",
                style = MaterialTheme.typography.labelLarge,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.primary
            )
        }
    }
}

@Composable
fun StatsChip(
    count: String,
    label: String,
    modifier: Modifier = Modifier
) {
    Surface(
        modifier = modifier,
        shape = RoundedCornerShape(8.dp),
        color = MaterialTheme.colorScheme.surface.copy(alpha = 0.9f),
        border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))
    ) {
        Row(
            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(4.dp)
        ) {
            Text(
                text = count,
                style = MaterialTheme.typography.labelSmall,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.primary
            )
            Text(
                text = label,
                style = MaterialTheme.typography.labelSmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun QuranicLensTab(
    viewModel: MainScreenViewModel,
    onSurahClick: (Int) -> Unit,
    repository: DataRepository = remember { DefaultDataRepository() },
    isLoggedIn: Boolean = false,
    userId: Int? = null,
    userName: String = "",
    userEmail: String = "",
    onAuthClick: () -> Unit = {},
    adaptiveInfo: WindowAdaptiveInfo = rememberWindowAdaptiveInfo()
) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val allConnState by viewModel.allConnectionsState.collectAsStateWithLifecycle()
    var searchQuery by remember { mutableStateOf(viewModel.currentConnSearch) }
    var selectedCategory by remember { mutableStateOf(viewModel.currentConnCategory) }
    var selectedItemForDetails by remember { mutableStateOf<GlobalConnectionItem?>(null) }

    // Inline Research & Video Contribution states
    var showContributeSheet by remember { mutableStateOf(false) }
    var selectedItemForVerify by remember { mutableStateOf<ResearchContribution?>(null) }
    var isContribLoading by remember { mutableStateOf(false) }
    var contribError by remember { mutableStateOf<String?>(null) }
    var contributionsList by remember { mutableStateOf<List<ResearchContribution>>(emptyList()) }

    fun loadInlineContributions() {
        scope.launch {
            isContribLoading = true
            contribError = null
            try {
                val tabMode = if (selectedCategory == "pending") "pending" else "published"
                val catFilter = if (selectedCategory == "pending") "all" else if (selectedCategory == "video") "video" else selectedCategory
                contributionsList = repository.getResearchContributions(
                    tab = tabMode,
                    category = catFilter,
                    search = searchQuery,
                    page = 1,
                    userId = userId
                )
            } catch (e: Exception) {
                contribError = e.message ?: "Failed to load contributions"
            } finally {
                isContribLoading = false
            }
        }
    }

    LaunchedEffect(selectedCategory, isLoggedIn) {
        if (selectedCategory == "video" || selectedCategory == "pending") {
            loadInlineContributions()
        }
    }

    val categories = listOf(
        "all" to "All",
        "video" to "Videos",
        "science" to "Science",
        "seerah" to "Seerah",
        "hadith" to "Hadith",
        "history" to "History",
        "scripture" to "Scripture",
        "pending" to if (isLoggedIn) "Pending" else "Pending · Login"
    )

    fun refreshSearch(query: String) {
        if (selectedCategory == "video" || selectedCategory == "pending") {
            loadInlineContributions()
        } else {
            viewModel.loadAllConnections(
                category = selectedCategory,
                search = query,
                page = 1
            )
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(horizontal = 14.dp, vertical = 10.dp)
    ) {
        // Compact mobile hero
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 12.dp),
            shape = RoundedCornerShape(18.dp),
            colors = CardDefaults.cardColors(
                containerColor = MaterialTheme.colorScheme.surface.copy(alpha = 0.92f)
            ),
            border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.65f)),
            elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
        ) {
            Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                Text(
                    text = "Quranic Lens",
                    style = MaterialTheme.typography.titleLarge,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurface
                )
                Text(
                    text = "Explore ayah connections, peer-reviewed videos, and community research.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    lineHeight = 18.sp
                )

                val stats = (allConnState as? AllConnectionsUiState.Success)?.response?.stats
                if (stats != null) {
                    FlowRow(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(6.dp),
                        verticalArrangement = Arrangement.spacedBy(6.dp)
                    ) {
                        StatsChip(count = "${stats.surahsCount}", label = "Surahs")
                        StatsChip(count = "${stats.scienceCount}", label = "Science")
                        StatsChip(count = "${stats.seerahCount}", label = "Seerah")
                        StatsChip(count = "${stats.hadithCount}", label = "Hadith")
                        StatsChip(count = "${stats.historyCount}", label = "History")
                    }
                }

                Button(
                    onClick = { showContributeSheet = true },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Icon(Icons.Default.Add, contentDescription = null, modifier = Modifier.size(18.dp))
                    Spacer(modifier = Modifier.width(6.dp))
                    Text("Contribute research", fontWeight = FontWeight.SemiBold)
                }
            }
        }

        OutlinedTextField(
            value = searchQuery,
            onValueChange = {
                searchQuery = it
                refreshSearch(it)
            },
            placeholder = { Text("Search connections or videos") },
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 10.dp),
            leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search") },
            trailingIcon = {
                if (searchQuery.isNotEmpty()) {
                    IconButton(onClick = {
                        searchQuery = ""
                        refreshSearch("")
                    }) {
                        Icon(Icons.Default.Clear, contentDescription = "Clear")
                    }
                }
            },
            shape = RoundedCornerShape(14.dp),
            singleLine = true
        )

        LazyRow(
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 12.dp),
            horizontalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            items(categories) { (catKey, catLabel) ->
                val selected = selectedCategory == catKey
                Surface(
                    onClick = {
                        if (catKey == "pending" && !isLoggedIn) {
                            onAuthClick()
                        } else {
                            selectedCategory = catKey
                            if (catKey == "video" || catKey == "pending") {
                                loadInlineContributions()
                            } else {
                                viewModel.loadAllConnections(
                                    category = catKey,
                                    search = searchQuery,
                                    page = 1
                                )
                            }
                        }
                    },
                    shape = RoundedCornerShape(20.dp),
                    color = if (selected) MaterialTheme.colorScheme.primary
                    else MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.55f),
                    border = BorderStroke(
                        1.dp,
                        if (selected) MaterialTheme.colorScheme.primary
                        else MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.6f)
                    )
                ) {
                    Text(
                        text = catLabel,
                        fontSize = 12.sp,
                        fontWeight = if (selected) FontWeight.Bold else FontWeight.Medium,
                        color = if (selected) MaterialTheme.colorScheme.onPrimary
                        else MaterialTheme.colorScheme.onSurfaceVariant,
                        modifier = Modifier.padding(horizontal = 14.dp, vertical = 8.dp)
                    )
                }
            }
        }

        if (selectedCategory == "video" || selectedCategory == "pending") {
            if (selectedCategory == "pending") {
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(bottom = 10.dp),
                    shape = RoundedCornerShape(14.dp),
                    colors = CardDefaults.cardColors(
                        containerColor = MaterialTheme.colorScheme.tertiaryContainer.copy(alpha = 0.4f)
                    ),
                    border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))
                ) {
                    Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                        Text(
                            text = "Peer review",
                            style = MaterialTheme.typography.labelLarge,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.onTertiaryContainer
                        )
                        Text(
                            text = "5 approvals publish a contribution. Downvotes keep contested items pending.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onTertiaryContainer.copy(alpha = 0.9f)
                        )
                    }
                }
            }

            Box(
                modifier = Modifier
                    .weight(1f)
                    .fillMaxWidth()
            ) {
                if (isContribLoading) {
                    CircularProgressIndicator(modifier = Modifier.align(Alignment.Center))
                } else if (contribError != null) {
                    Column(
                        modifier = Modifier
                            .align(Alignment.Center)
                            .padding(24.dp),
                        horizontalAlignment = Alignment.CenterHorizontally,
                        verticalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        Text(
                            text = contribError ?: "Something went wrong",
                            color = MaterialTheme.colorScheme.error,
                            textAlign = TextAlign.Center,
                            style = MaterialTheme.typography.bodyMedium
                        )
                        Button(onClick = { loadInlineContributions() }, shape = RoundedCornerShape(12.dp)) {
                            Text("Retry")
                        }
                    }
                } else if (contributionsList.isEmpty()) {
                    Column(
                        modifier = Modifier
                            .align(Alignment.Center)
                            .padding(28.dp),
                        horizontalAlignment = Alignment.CenterHorizontally,
                        verticalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        Text(
                            text = if (selectedCategory == "pending")
                                "Nothing waiting for review"
                            else
                                "No videos published yet",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold,
                            textAlign = TextAlign.Center
                        )
                        Text(
                            text = "Share a video or research note linked to an ayah or Surah.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            textAlign = TextAlign.Center
                        )
                        Button(
                            onClick = { showContributeSheet = true },
                            shape = RoundedCornerShape(12.dp)
                        ) {
                            Icon(Icons.Default.Add, contentDescription = null, modifier = Modifier.size(18.dp))
                            Spacer(modifier = Modifier.width(6.dp))
                            Text("Contribute")
                        }
                    }
                } else {
                    LazyColumn(
                        modifier = Modifier.fillMaxSize(),
                        verticalArrangement = Arrangement.spacedBy(12.dp),
                        contentPadding = PaddingValues(bottom = 16.dp)
                    ) {
                        items(contributionsList, key = { it.id }) { item ->
                            ContributionCard(
                                item = item,
                                isLoggedIn = isLoggedIn,
                                onWatchVideo = { url ->
                                    try {
                                        val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url))
                                        context.startActivity(intent)
                                    } catch (_: Exception) {}
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
        } else {
            // Standard Connection State rendering
            when (val state = allConnState) {
                is AllConnectionsUiState.Loading -> {
                    Box(
                        modifier = Modifier.fillMaxSize(),
                        contentAlignment = Alignment.Center
                    ) {
                        CircularProgressIndicator()
                    }
                }
                is AllConnectionsUiState.Error -> {
                    Box(
                        modifier = Modifier.fillMaxSize(),
                        contentAlignment = Alignment.Center
                    ) {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            Text(
                                text = "Failed to load connections: ${state.throwable.message ?: "Unknown error"}",
                                color = MaterialTheme.colorScheme.error,
                                textAlign = TextAlign.Center
                            )
                            Button(onClick = {
                                viewModel.loadAllConnections(
                                    category = selectedCategory,
                                    search = searchQuery,
                                    page = viewModel.currentConnPage
                                )
                            }) {
                                Text("Retry")
                            }
                        }
                    }
                }
                is AllConnectionsUiState.Success -> {
                    val resp = state.response
                    if (resp.data.isEmpty()) {
                        Box(
                            modifier = Modifier.fillMaxSize(),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                text = "No connections found matching your filter.",
                                style = MaterialTheme.typography.bodyMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    } else {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(bottom = 6.dp),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(
                                text = "${resp.totalItems} connections found",
                                style = MaterialTheme.typography.labelMedium,
                                fontWeight = FontWeight.Bold,
                                color = MaterialTheme.colorScheme.primary
                            )
                            Text(
                                text = "Page ${resp.page} of ${resp.totalPages}",
                                style = MaterialTheme.typography.labelSmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }

                        LazyColumn(
                            modifier = Modifier
                                .weight(1f)
                                .fillMaxWidth(),
                            verticalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            items(
                                items = resp.data,
                                key = { "${it.category}_${it.id}" }
                            ) { item ->
                                GlobalConnectionCard(
                                    item = item,
                                    onSurahClick = { onSurahClick(item.surahNumber) },
                                    onShowDetails = { selectedItemForDetails = item }
                                )
                            }

                            item {
                                // Pagination Bar
                                Row(
                                    modifier = Modifier
                                        .fillMaxWidth()
                                        .padding(vertical = 12.dp),
                                    horizontalArrangement = Arrangement.SpaceBetween,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Button(
                                        onClick = {
                                            viewModel.loadAllConnections(
                                                category = selectedCategory,
                                                search = searchQuery,
                                                page = resp.page - 1
                                            )
                                        },
                                        enabled = resp.page > 1
                                    ) {
                                        Text("Previous")
                                    }

                                    Text(
                                        text = "Page ${resp.page} of ${resp.totalPages}",
                                        style = MaterialTheme.typography.bodyMedium,
                                        fontWeight = FontWeight.Bold
                                    )

                                    Button(
                                        onClick = {
                                            viewModel.loadAllConnections(
                                                category = selectedCategory,
                                                search = searchQuery,
                                                page = resp.page + 1
                                            )
                                        },
                                        enabled = resp.page < resp.totalPages
                                    ) {
                                        Text("Next")
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // Inline Add Video / Research Bottom Sheet
    if (showContributeSheet) {
        ContributeResearchBottomSheet(
            repository = repository,
            userId = userId,
            userName = userName,
            userEmail = userEmail,
            onDismiss = {
                showContributeSheet = false
                if (selectedCategory == "video" || selectedCategory == "pending") {
                    loadInlineContributions()
                }
            }
        )
    }

    // Inline Verification Dialog
    selectedItemForVerify?.let { item ->
        VerificationDialog(
            item = item,
            onDismiss = { selectedItemForVerify = null },
            onSubmitVerdict = { vote, feedback ->
                scope.launch {
                    repository.reviewResearchContribution(
                        contributionId = item.id,
                        userId = userId ?: 0,
                        vote = vote,
                        feedback = feedback
                    )
                    selectedItemForVerify = null
                    loadInlineContributions()
                }
            }
        )
    }

    // Detail Bottom Sheet
    selectedItemForDetails?.let { item ->
        ConnectionDetailBottomSheet(
            item = item,
            onDismiss = { selectedItemForDetails = null },
            onSurahClick = {
                selectedItemForDetails = null
                onSurahClick(item.surahNumber)
            }
        )
    }
}

@Composable
fun GlobalConnectionCard(
    item: GlobalConnectionItem,
    onSurahClick: () -> Unit,
    onShowDetails: () -> Unit,
    modifier: Modifier = Modifier
) {
    val categoryColor = when (item.category.lowercase()) {
        "science" -> Color(0xFF1E88E5)
        "seerah" -> Color(0xFF2E7D32)
        "hadith" -> Color(0xFFE65100)
        "history" -> Color(0xFF6A1B9A)
        "scripture" -> Color(0xFFC2185B)
        else -> MaterialTheme.colorScheme.primary
    }

    Card(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.surface.copy(alpha = 0.92f)
        ),
        border = BorderStroke(1.dp, categoryColor.copy(alpha = 0.28f)),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(14.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Surface(
                    shape = RoundedCornerShape(8.dp),
                    color = categoryColor.copy(alpha = 0.14f)
                ) {
                    Text(
                        text = item.category.replaceFirstChar { it.uppercase() },
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.Bold,
                        color = categoryColor,
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                    )
                }

                Surface(
                    shape = RoundedCornerShape(8.dp),
                    color = MaterialTheme.colorScheme.primary.copy(alpha = 0.12f)
                ) {
                    Text(
                        text = "${item.surahNumber}:${item.verseNumber}",
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.SemiBold,
                        color = MaterialTheme.colorScheme.primary,
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                    )
                }
            }

            Spacer(modifier = Modifier.height(6.dp))

            // Surah Name
            Text(
                text = "${item.surahName} (Juz ${item.juzNumber})",
                style = MaterialTheme.typography.labelMedium,
                fontWeight = FontWeight.Medium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )

            // Arabic Verse Text if present
            if (item.verseArabic.isNotBlank()) {
                Spacer(modifier = Modifier.height(4.dp))
                Text(
                    text = item.verseArabic,
                    style = MaterialTheme.typography.bodyLarge,
                    fontFamily = FontFamily.Serif,
                    textAlign = TextAlign.End,
                    modifier = Modifier.fillMaxWidth(),
                    color = MaterialTheme.colorScheme.onSurface
                )
            }

            // Verse Transliteration if present
            if (item.verseTransliteration.isNotBlank()) {
                Spacer(modifier = Modifier.height(4.dp))
                Text(
                    text = "\"${item.verseTransliteration}\"",
                    style = MaterialTheme.typography.bodySmall,
                    fontStyle = FontStyle.Italic,
                    color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.8f)
                )
            }

            Spacer(modifier = Modifier.height(8.dp))

            // Title
            Text(
                text = item.title,
                style = MaterialTheme.typography.titleSmall,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface
            )

            Spacer(modifier = Modifier.height(4.dp))

            // Description
            Text(
                text = item.description,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                maxLines = 4
            )

            val contextText = item.relevanceDescription.ifBlank { item.extraInfo }
            if (contextText.isNotBlank()) {
                Spacer(modifier = Modifier.height(8.dp))
                Surface(
                    shape = RoundedCornerShape(10.dp),
                    color = categoryColor.copy(alpha = 0.08f),
                    border = BorderStroke(1.dp, categoryColor.copy(alpha = 0.2f)),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(10.dp)) {
                        Text(
                            text = "Relevance",
                            style = MaterialTheme.typography.labelSmall,
                            fontWeight = FontWeight.Bold,
                            color = categoryColor
                        )
                        Spacer(modifier = Modifier.height(2.dp))
                        Text(
                            text = contextText,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurface,
                            fontSize = 12.sp
                        )
                    }
                }
            }

            Spacer(modifier = Modifier.height(12.dp))

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                OutlinedButton(
                    onClick = onShowDetails,
                    modifier = Modifier.weight(1f),
                    shape = RoundedCornerShape(12.dp),
                    contentPadding = PaddingValues(horizontal = 8.dp, vertical = 8.dp)
                ) {
                    Text("Details", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                }

                Button(
                    onClick = onSurahClick,
                    modifier = Modifier.weight(1f),
                    shape = RoundedCornerShape(12.dp),
                    contentPadding = PaddingValues(horizontal = 8.dp, vertical = 8.dp)
                ) {
                    Text("Read ayah", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ConnectionDetailBottomSheet(
    item: GlobalConnectionItem,
    onDismiss: () -> Unit,
    onSurahClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    val categoryColor = when (item.category.lowercase()) {
        "science" -> Color(0xFF1E88E5)
        "seerah" -> Color(0xFF2E7D32)
        "hadith" -> Color(0xFFE65100)
        "history" -> Color(0xFF6A1B9A)
        "scripture" -> Color(0xFFC2185B)
        else -> MaterialTheme.colorScheme.primary
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
                .padding(horizontal = 20.dp, vertical = 10.dp)
                .verticalScroll(rememberScrollState())
        ) {
            // Badges row
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Surface(
                    shape = RoundedCornerShape(6.dp),
                    color = categoryColor.copy(alpha = 0.15f)
                ) {
                    Text(
                        text = item.category.uppercase(),
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.Bold,
                        color = categoryColor,
                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                    )
                }

                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    Surface(
                        shape = RoundedCornerShape(6.dp),
                        color = MaterialTheme.colorScheme.primary.copy(alpha = 0.12f)
                    ) {
                        Text(
                            text = "Surah ${item.surahName} (${item.surahNumber}:${item.verseNumber})",
                            style = MaterialTheme.typography.labelSmall,
                            fontWeight = FontWeight.Bold,
                            color = MaterialTheme.colorScheme.primary,
                            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp)
                        )
                    }
                    val context = LocalContext.current
                    IconButton(
                        onClick = {
                            ShareHelper.shareConnection(
                                context = context,
                                title = item.title,
                                description = item.description,
                                category = item.category
                            )
                        },
                        modifier = Modifier.size(32.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.Share,
                            contentDescription = "Share Linkage",
                            modifier = Modifier.size(18.dp),
                            tint = MaterialTheme.colorScheme.primary
                        )
                    }
                }
            }

            Spacer(modifier = Modifier.height(14.dp))

            // Arabic text
            if (item.verseArabic.isNotBlank()) {
                Text(
                    text = item.verseArabic,
                    style = MaterialTheme.typography.headlineSmall,
                    fontFamily = FontFamily.Serif,
                    textAlign = TextAlign.End,
                    modifier = Modifier.fillMaxWidth(),
                    color = MaterialTheme.colorScheme.onSurface
                )
                Spacer(modifier = Modifier.height(6.dp))
            }

            // Translation / Transliteration
            if (item.verseTransliteration.isNotBlank()) {
                Text(
                    text = "\"${item.verseTransliteration}\"",
                    style = MaterialTheme.typography.bodyMedium,
                    fontStyle = FontStyle.Italic,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                Spacer(modifier = Modifier.height(12.dp))
            }

            HorizontalDivider()

            Spacer(modifier = Modifier.height(12.dp))

            // Title
            Text(
                text = item.title,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface
            )

            Spacer(modifier = Modifier.height(6.dp))

            // Full Description
            Text(
                text = item.description,
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                lineHeight = 20.sp
            )

            // Relevance context
            val contextText = item.relevanceDescription.ifBlank { item.extraInfo }
            if (contextText.isNotBlank()) {
                Spacer(modifier = Modifier.height(12.dp))
                Surface(
                    shape = RoundedCornerShape(10.dp),
                    color = categoryColor.copy(alpha = 0.08f),
                    border = BorderStroke(1.dp, categoryColor.copy(alpha = 0.2f)),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Column(modifier = Modifier.padding(12.dp)) {
                        Text(
                            text = "💡 RELEVANCE CONTEXT",
                            style = MaterialTheme.typography.labelMedium,
                            fontWeight = FontWeight.ExtraBold,
                            color = categoryColor
                        )
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            text = contextText,
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurface
                        )
                    }
                }
            }

            // Extra Metadata
            val extraMeta = mutableListOf<Pair<String, String>>()
            if (item.field.isNotBlank()) extraMeta.add("Field" to item.field)
            if (item.sourceName.isNotBlank()) extraMeta.add("Source" to item.sourceName)
            if (item.location.isNotBlank()) extraMeta.add("Location" to item.location)
            if (item.dateInfo.isNotBlank()) extraMeta.add("Date / Era" to item.dateInfo)
            if (item.credibilityScore.isNotBlank()) extraMeta.add("Credibility" to item.credibilityScore)
            if (item.collectionName.isNotBlank()) extraMeta.add("Collection" to item.collectionName)
            if (item.hadithNumber.isNotBlank()) extraMeta.add("Hadith #" to item.hadithNumber)
            if (item.grading.isNotBlank()) extraMeta.add("Grading" to item.grading)
            if (item.scriptureType.isNotBlank()) extraMeta.add("Scripture Type" to item.scriptureType)
            if (item.relationshipType.isNotBlank()) extraMeta.add("Relationship" to item.relationshipType)

            if (extraMeta.isNotEmpty()) {
                Spacer(modifier = Modifier.height(12.dp))
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(8.dp),
                    colors = CardDefaults.cardColors(
                        containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f)
                    )
                ) {
                    Column(modifier = Modifier.padding(10.dp)) {
                        extraMeta.forEach { (label, value) ->
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(vertical = 2.dp),
                                horizontalArrangement = Arrangement.SpaceBetween
                            ) {
                                Text(
                                    text = label,
                                    style = MaterialTheme.typography.labelSmall,
                                    fontWeight = FontWeight.Bold,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                                Text(
                                    text = value,
                                    style = MaterialTheme.typography.bodySmall,
                                    color = MaterialTheme.colorScheme.onSurface
                                )
                            }
                        }
                    }
                }
            }

            Spacer(modifier = Modifier.height(16.dp))

            Button(
                onClick = onSurahClick,
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(10.dp)
            ) {
                Text("📖 Read Surah ${item.surahName} (${item.surahNumber})")
            }

            Spacer(modifier = Modifier.height(12.dp))
        }
    }
}



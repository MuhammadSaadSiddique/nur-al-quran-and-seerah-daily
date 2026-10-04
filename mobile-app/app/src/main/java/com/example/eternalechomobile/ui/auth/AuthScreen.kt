package com.asloobulhayat.eternalecho.ui.auth

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import com.asloobulhayat.eternalecho.data.DataRepository
import com.asloobulhayat.eternalecho.data.DefaultDataRepository
import com.asloobulhayat.eternalecho.data.UserSession
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

import com.asloobulhayat.eternalecho.data.UserSessionOtpResponse

class AuthViewModel(private val repository: DataRepository) : ViewModel() {
    private val _uiState = MutableStateFlow<AuthUiState>(AuthUiState.Idle)
    val uiState: StateFlow<AuthUiState> = _uiState.asStateFlow()

    private fun sanitizeErrorMessage(rawMessage: String?, defaultMessage: String): String {
        if (rawMessage.isNullOrBlank()) return defaultMessage
        val trimmed = rawMessage.trim()
        if (trimmed.contains("http://", ignoreCase = true) ||
            trimmed.contains("https://", ignoreCase = true) ||
            trimmed.contains("HTTP Error", ignoreCase = true) ||
            trimmed.contains("Exception", ignoreCase = true) ||
            trimmed.contains(".php", ignoreCase = true) ||
            trimmed.contains("failed to connect", ignoreCase = true) ||
            trimmed.contains("timeout", ignoreCase = true)
        ) {
            if (trimmed.contains("timeout", ignoreCase = true) || trimmed.contains("failed to connect", ignoreCase = true)) {
                return "Unable to connect to the server. Please check your internet connection."
            }
            return defaultMessage
        }
        return trimmed
    }

    fun setError(message: String) {
        _uiState.value = AuthUiState.Error(message)
    }

    fun requestOtp(email: String, onSuccess: () -> Unit) {
        val cleanEmail = email.trim()
        if (!cleanEmail.contains("@") || cleanEmail.length < 5) {
            _uiState.value = AuthUiState.Error("Please enter a valid email address.")
            return
        }
        viewModelScope.launch {
            _uiState.value = AuthUiState.Loading
            try {
                val success = repository.requestOtp(cleanEmail)
                if (success) {
                    _uiState.value = AuthUiState.Idle
                    onSuccess()
                } else {
                    _uiState.value = AuthUiState.Error("Failed to send verification code. Please try again.")
                }
            } catch (t: Throwable) {
                _uiState.value = AuthUiState.Error(sanitizeErrorMessage(t.message, "Failed to send verification code. Please try again."))
            }
        }
    }

    fun verifyOtp(email: String, otp: String, onSuccess: (UserSessionOtpResponse) -> Unit) {
        val cleanEmail = email.trim()
        val cleanOtp = otp.trim()
        if (cleanEmail.isEmpty()) {
            _uiState.value = AuthUiState.Error("Email address is required.")
            return
        }
        if (cleanOtp.isEmpty()) {
            _uiState.value = AuthUiState.Error("Please enter the verification code.")
            return
        }
        viewModelScope.launch {
            _uiState.value = AuthUiState.Loading
            try {
                val response = repository.verifyOtp(cleanEmail, cleanOtp)
                _uiState.value = AuthUiState.Success(response.session)
                onSuccess(response)
            } catch (t: Throwable) {
                _uiState.value = AuthUiState.Error(sanitizeErrorMessage(t.message, "Verification failed. Please check the code and try again."))
            }
        }
    }

    fun setPassword(userId: Int, password: String, onSuccess: () -> Unit) {
        if (password.length < 6) {
            _uiState.value = AuthUiState.Error("Password must be at least 6 characters.")
            return
        }
        viewModelScope.launch {
            _uiState.value = AuthUiState.Loading
            try {
                val success = repository.setPassword(userId, password)
                if (success) {
                    _uiState.value = AuthUiState.Idle
                    onSuccess()
                } else {
                    _uiState.value = AuthUiState.Error("Failed to set password. Please try again.")
                }
            } catch (t: Throwable) {
                _uiState.value = AuthUiState.Error(sanitizeErrorMessage(t.message, "Failed to set password. Please try again."))
            }
        }
    }

    fun login(email: String, password: String, onSuccess: (UserSession) -> Unit) {
        val cleanEmail = email.trim()
        if (!cleanEmail.contains("@")) {
            _uiState.value = AuthUiState.Error("Please enter a valid email address.")
            return
        }
        if (password.isEmpty()) {
            _uiState.value = AuthUiState.Error("Please enter your password.")
            return
        }
        viewModelScope.launch {
            _uiState.value = AuthUiState.Loading
            try {
                val session = repository.login(cleanEmail, password)
                _uiState.value = AuthUiState.Success(session)
                onSuccess(session)
            } catch (t: Throwable) {
                _uiState.value = AuthUiState.Error(sanitizeErrorMessage(t.message, "Authentication failed. Please check your credentials."))
            }
        }
    }

    fun clearState() {
        _uiState.value = AuthUiState.Idle
    }
}

sealed interface AuthUiState {
    object Idle : AuthUiState
    object Loading : AuthUiState
    data class Success(val session: UserSession) : AuthUiState
    data class Error(val message: String) : AuthUiState
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AuthScreen(
    onBackClick: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: AuthViewModel = viewModel { AuthViewModel(DefaultDataRepository()) }
) {
    val uiState by viewModel.uiState.collectAsState()

    var method by remember { mutableStateOf("otp") } // "otp" or "password"
    var phase by remember { mutableStateOf("email") } // "email", "otp", "set_password"

    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var otp by remember { mutableStateOf("") }
    var sessionForSetPassword by remember { mutableStateOf<UserSession?>(null) }

    val context = LocalContext.current
    val prefs = remember { com.asloobulhayat.eternalecho.security.SecurePreferences.getInstance(context) }
    val scope = rememberCoroutineScope()

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = when {
                            method == "otp" && phase == "otp" -> "Verify Code"
                            method == "otp" && phase == "set_password" -> "Set Password"
                            method == "otp" -> "OTP Sign Up / In"
                            else -> "Password Sign In"
                        },
                        fontWeight = FontWeight.Bold
                    )
                },
                navigationIcon = {
                    IconButton(onClick = onBackClick) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
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
                .padding(24.dp)
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .align(Alignment.Center),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(16.dp)
            ) {
                Text(
                    text = "Welcome to Eternal Echo",
                    style = MaterialTheme.typography.headlineMedium,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary,
                    textAlign = TextAlign.Center
                )

                Text(
                    text = "Unlock unlimited quizzes and contribute inn research by signing up or logging in.",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    textAlign = TextAlign.Center,
                    modifier = Modifier.padding(bottom = 16.dp)
                )

                // Method Selection Tab (only in email phase)
                if (phase == "email") {
                    TabRow(selectedTabIndex = if (method == "otp") 0 else 1) {
                        Tab(
                            selected = method == "otp",
                            onClick = {
                                method = "otp"
                                viewModel.clearState()
                            },
                            text = { Text("🔑 Sign UP") }
                        )
                        Tab(
                            selected = method == "password",
                            onClick = {
                                method = "password"
                                viewModel.clearState()
                            },
                            text = { Text("🔒 Password") }
                        )
                    }
                }

                // Phase forms
                when {
                    method == "otp" && phase == "email" -> {
                        OutlinedTextField(
                            value = email, maxLines = 1,
                            onValueChange = { email = it },
                            label = { Text("Email Address") },
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp)
                        )
                    }
                    method == "otp" && phase == "otp" -> {
                        Text(
                            text = "A verification code has been sent to $email",
                            style = MaterialTheme.typography.bodySmall,
                            textAlign = TextAlign.Center,
                            color = MaterialTheme.colorScheme.secondary
                        )
                        OutlinedTextField(
                            value = otp,
                            onValueChange = { otp = it },maxLines = 1,
                            label = { Text("Verification Code") },
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp)
                        )
                    }
                    method == "otp" && phase == "set_password" -> {
                        Text(
                            text = "Success! Secure your account by setting a password now, or skip and set it later in your profile.",
                            style = MaterialTheme.typography.bodySmall,
                            textAlign = TextAlign.Center,
                            color = MaterialTheme.colorScheme.secondary
                        )
                        OutlinedTextField(
                            value = password,
                            onValueChange = { password = it },maxLines = 1,
                            label = { Text("Set Password") },
                            visualTransformation = PasswordVisualTransformation(),
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp)
                        )
                    }
                    method == "password" -> {
                        OutlinedTextField(
                            value = email,
                            onValueChange = { email = it },
                            label = { Text("Email Address") },
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp)
                        )
                        OutlinedTextField(
                            value = password,
                            onValueChange = { password = it },maxLines = 1,
                            label = { Text("Password") },
                            visualTransformation = PasswordVisualTransformation(),
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp)
                        )
                    }
                }

                if (uiState is AuthUiState.Error) {
                    Text(
                        text = (uiState as AuthUiState.Error).message,
                        color = MaterialTheme.colorScheme.error,
                        style = MaterialTheme.typography.bodySmall,
                        modifier = Modifier.fillMaxWidth(),
                        textAlign = TextAlign.Start
                    )
                }

                Spacer(modifier = Modifier.height(8.dp))

                // Submit Button
                Button(
                    onClick = {
                        val saveSessionToPrefs: (UserSession) -> Unit = { session ->
                            prefs.putInt("user_id", session.userId)
                            prefs.putString("user_name", session.name)
                            prefs.putString("user_email", session.email)
                            scope.launch {
                                com.asloobulhayat.eternalecho.data.QuizSubmissionHelper.submitPendingQuizIfAny(
                                    com.asloobulhayat.eternalecho.data.DefaultDataRepository(),
                                    prefs,
                                    session.userId
                                )
                            }
                        }

                        when {
                            method == "otp" && phase == "email" -> {
                                val cleanEmail = email.trim()
                                if (cleanEmail.contains("@")) {
                                    viewModel.requestOtp(cleanEmail) {
                                        phase = "otp"
                                    }
                                } else {
                                    viewModel.setError("Please enter a valid email address.")
                                }
                            }
                            method == "otp" && phase == "otp" -> {
                                val cleanEmail = email.trim()
                                val cleanOtp = otp.trim()
                                if (cleanOtp.isNotEmpty()) {
                                    viewModel.verifyOtp(cleanEmail, cleanOtp) { response ->
                                        saveSessionToPrefs(response.session)
                                        if (response.hasPassword) {
                                            onBackClick()
                                        } else {
                                            sessionForSetPassword = response.session
                                            phase = "set_password"
                                        }
                                    }
                                } else {
                                    viewModel.setError("Please enter the verification code.")
                                }
                            }
                            method == "otp" && phase == "set_password" -> {
                                if (password.length >= 6) {
                                    val uId = sessionForSetPassword?.userId ?: -1
                                    viewModel.setPassword(uId, password) {
                                        onBackClick()
                                    }
                                } else {
                                    viewModel.setError("Password must be at least 6 characters.")
                                }
                            }
                            method == "password" -> {
                                val cleanEmail = email.trim()
                                if (!cleanEmail.contains("@")) {
                                    viewModel.setError("Please enter a valid email address.")
                                } else if (password.isEmpty()) {
                                    viewModel.setError("Please enter your password.")
                                } else {
                                    viewModel.login(cleanEmail, password) { session ->
                                        saveSessionToPrefs(session)
                                        onBackClick()
                                    }
                                }
                            }
                        }
                    },
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp),
                    enabled = uiState !is AuthUiState.Loading
                ) {
                    if (uiState is AuthUiState.Loading) {
                        CircularProgressIndicator(
                            color = MaterialTheme.colorScheme.onPrimary,
                            modifier = Modifier.size(24.dp)
                        )
                    } else {
                        Text(
                            text = when {
                                method == "otp" && phase == "email" -> "Send Verification Code"
                                method == "otp" && phase == "otp" -> "Verify & Enter"
                                method == "otp" && phase == "set_password" -> "Save Password"
                                else -> "Sign In"
                            }
                        )
                    }
                }

                // Cancel/Back/Skip buttons
                if (method == "otp" && phase == "set_password") {
                    TextButton(
                        onClick = onBackClick,
                        enabled = uiState !is AuthUiState.Loading
                    ) {
                        Text("Skip & Enter Dashboard")
                    }
                } else if (method == "otp" && phase == "otp") {
                    TextButton(
                        onClick = {
                            phase = "email"
                            viewModel.clearState()
                        },
                        enabled = uiState !is AuthUiState.Loading
                    ) {
                        Text("Back to Email Form")
                    }
                }

                Spacer(modifier = Modifier.height(8.dp))
                TextButton(
                    onClick = {
                        try {
                            val intent = android.content.Intent(
                                android.content.Intent.ACTION_VIEW,
                                android.net.Uri.parse(com.asloobulhayat.eternalecho.data.ApiClient.ACCOUNT_DELETE_URL)
                            )
                            context.startActivity(intent)
                        } catch (_: Exception) {}
                    }
                ) {
                    Text(
                        text = "Account Deletion & Data Policy",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.primary,
                        textAlign = TextAlign.Center
                    )
                }
            }
        }
    }
}

package com.example.eternalechomobile.ui.auth

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
import com.example.eternalechomobile.data.DataRepository
import com.example.eternalechomobile.data.DefaultDataRepository
import com.example.eternalechomobile.data.UserSession
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

import com.example.eternalechomobile.data.UserSessionOtpResponse

class AuthViewModel(private val repository: DataRepository) : ViewModel() {
    private val _uiState = MutableStateFlow<AuthUiState>(AuthUiState.Idle)
    val uiState: StateFlow<AuthUiState> = _uiState.asStateFlow()

    fun requestOtp(email: String, onSuccess: () -> Unit) {
        viewModelScope.launch {
            _uiState.value = AuthUiState.Loading
            try {
                val success = repository.requestOtp(email)
                if (success) {
                    _uiState.value = AuthUiState.Idle
                    onSuccess()
                } else {
                    _uiState.value = AuthUiState.Error("Failed to send verification code.")
                }
            } catch (t: Throwable) {
                _uiState.value = AuthUiState.Error(t.message ?: "Failed to send verification code.")
            }
        }
    }

    fun verifyOtp(email: String, otp: String, onSuccess: (UserSessionOtpResponse) -> Unit) {
        viewModelScope.launch {
            _uiState.value = AuthUiState.Loading
            try {
                val response = repository.verifyOtp(email, otp)
                _uiState.value = AuthUiState.Success(response.session)
                onSuccess(response)
            } catch (t: Throwable) {
                _uiState.value = AuthUiState.Error(t.message ?: "Verification failed.")
            }
        }
    }

    fun setPassword(userId: Int, password: String, onSuccess: () -> Unit) {
        viewModelScope.launch {
            _uiState.value = AuthUiState.Loading
            try {
                val success = repository.setPassword(userId, password)
                if (success) {
                    _uiState.value = AuthUiState.Idle
                    onSuccess()
                } else {
                    _uiState.value = AuthUiState.Error("Failed to set password.")
                }
            } catch (t: Throwable) {
                _uiState.value = AuthUiState.Error(t.message ?: "Failed to set password.")
            }
        }
    }

    fun login(email: String, password: String, onSuccess: (UserSession) -> Unit) {
        viewModelScope.launch {
            _uiState.value = AuthUiState.Loading
            try {
                val session = repository.login(email, password)
                _uiState.value = AuthUiState.Success(session)
                onSuccess(session)
            } catch (t: Throwable) {
                _uiState.value = AuthUiState.Error(t.message ?: "Authentication failed.")
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
    val prefs = remember { context.getSharedPreferences("app_prefs", android.content.Context.MODE_PRIVATE) }

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
                    text = "Unlock unlimited quizzes and save your daily insights",
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
                            text = { Text("🔑 OTP") }
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
                            value = email,
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
                            onValueChange = { otp = it },
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
                            onValueChange = { password = it },
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
                            onValueChange = { password = it },
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
                            prefs.edit()
                                .putInt("user_id", session.userId)
                                .putString("user_name", session.name)
                                .putString("user_email", session.email)
                                .apply()
                        }

                        when {
                            method == "otp" && phase == "email" -> {
                                if (email.contains("@")) {
                                    viewModel.requestOtp(email) {
                                        phase = "otp"
                                    }
                                } else {
                                    viewModel.clearState()
                                }
                            }
                            method == "otp" && phase == "otp" -> {
                                if (otp.isNotEmpty()) {
                                    viewModel.verifyOtp(email, otp) { response ->
                                        saveSessionToPrefs(response.session)
                                        if (response.hasPassword) {
                                            onBackClick()
                                        } else {
                                            sessionForSetPassword = response.session
                                            phase = "set_password"
                                        }
                                    }
                                }
                            }
                            method == "otp" && phase == "set_password" -> {
                                if (password.length >= 6) {
                                    val uId = sessionForSetPassword?.userId ?: -1
                                    viewModel.setPassword(uId, password) {
                                        onBackClick()
                                    }
                                }
                            }
                            method == "password" -> {
                                if (email.contains("@") && password.isNotEmpty()) {
                                    viewModel.login(email, password) { session ->
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
            }
        }
    }
}

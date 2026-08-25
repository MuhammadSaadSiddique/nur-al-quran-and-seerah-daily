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

class AuthViewModel(private val repository: DataRepository) : ViewModel() {
    private val _uiState = MutableStateFlow<AuthUiState>(AuthUiState.Idle)
    val uiState: StateFlow<AuthUiState> = _uiState.asStateFlow()

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

    fun register(name: String, email: String, password: String, onSuccess: (UserSession) -> Unit) {
        viewModelScope.launch {
            _uiState.value = AuthUiState.Loading
            try {
                val session = repository.register(name, email, password)
                _uiState.value = AuthUiState.Success(session)
                onSuccess(session)
            } catch (t: Throwable) {
                _uiState.value = AuthUiState.Error(t.message ?: "Registration failed.")
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
    var isSignUpMode by remember { mutableStateOf(false) }

    var name by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }

    val context = LocalContext.current
    val prefs = remember { context.getSharedPreferences("app_prefs", android.content.Context.MODE_PRIVATE) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text(if (isSignUpMode) "Create Account" else "Sign In", fontWeight = FontWeight.Bold) },
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

                if (isSignUpMode) {
                    OutlinedTextField(
                        value = name,
                        onValueChange = { name = it },
                        label = { Text("Name") },
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp)
                    )
                }

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

                Button(
                    onClick = {
                        val onAuthSuccess: (UserSession) -> Unit = { session ->
                            prefs.edit()
                                .putInt("user_id", session.userId)
                                .putString("user_name", session.name)
                                .putString("user_email", session.email)
                                .apply()
                            onBackClick()
                        }

                        if (isSignUpMode) {
                            viewModel.register(name, email, password, onAuthSuccess)
                        } else {
                            viewModel.login(email, password, onAuthSuccess)
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
                        Text(if (isSignUpMode) "Sign Up" else "Sign In")
                    }
                }

                TextButton(
                    onClick = {
                        isSignUpMode = !isSignUpMode
                        viewModel.clearState()
                    },
                    enabled = uiState !is AuthUiState.Loading
                ) {
                    Text(
                        if (isSignUpMode) "Already have an account? Sign In"
                        else "Don't have an account? Sign Up"
                    )
                }
            }
        }
    }
}

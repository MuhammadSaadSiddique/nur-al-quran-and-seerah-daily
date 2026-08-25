package com.example.eternalechomobile.ui.surah

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.eternalechomobile.data.DataRepository
import com.example.eternalechomobile.data.Verse
import com.example.eternalechomobile.data.ConnectionsData
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

class SurahScreenViewModel(
    private val surahNumber: Int,
    private val dataRepository: DataRepository
) : ViewModel() {
    private val _versesState = MutableStateFlow<VersesUiState>(VersesUiState.Loading)
    val versesState: StateFlow<VersesUiState> = _versesState.asStateFlow()

    private val _connectionsState = MutableStateFlow<ConnectionsUiState>(ConnectionsUiState.Idle)
    val connectionsState: StateFlow<ConnectionsUiState> = _connectionsState.asStateFlow()

    init {
        loadVerses()
    }

    fun loadVerses() {
        viewModelScope.launch {
            _versesState.value = VersesUiState.Loading
            try {
                val data = dataRepository.getVerses(surahNumber)
                _versesState.value = VersesUiState.Success(data)
            } catch (t: Throwable) {
                _versesState.value = VersesUiState.Error(t)
            }
        }
    }

    fun loadConnections(verseNumber: Int) {
        viewModelScope.launch {
            _connectionsState.value = ConnectionsUiState.Loading
            try {
                val data = dataRepository.getConnections(surahNumber, verseNumber)
                _connectionsState.value = ConnectionsUiState.Success(verseNumber, data)
            } catch (t: Throwable) {
                _connectionsState.value = ConnectionsUiState.Error(t)
            }
        }
    }

    fun clearConnections() {
        _connectionsState.value = ConnectionsUiState.Idle
    }
}

sealed interface VersesUiState {
    object Loading : VersesUiState
    data class Error(val throwable: Throwable) : VersesUiState
    data class Success(val data: List<Verse>) : VersesUiState
}

sealed interface ConnectionsUiState {
    object Idle : ConnectionsUiState
    object Loading : ConnectionsUiState
    data class Error(val throwable: Throwable) : ConnectionsUiState
    data class Success(val verseNumber: Int, val data: ConnectionsData) : ConnectionsUiState
}

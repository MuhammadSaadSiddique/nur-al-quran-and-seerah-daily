package com.example.eternalechomobile.ui.main

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.eternalechomobile.data.DataRepository
import com.example.eternalechomobile.data.Surah
import com.example.eternalechomobile.data.LeaderboardUser
import com.example.eternalechomobile.data.InsightsData
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

class MainScreenViewModel(private val dataRepository: DataRepository) : ViewModel() {
    private val _surahsState = MutableStateFlow<SurahsUiState>(SurahsUiState.Loading)
    val surahsState: StateFlow<SurahsUiState> = _surahsState.asStateFlow()

    private val _leaderboardState = MutableStateFlow<LeaderboardUiState>(LeaderboardUiState.Loading)
    val leaderboardState: StateFlow<LeaderboardUiState> = _leaderboardState.asStateFlow()

    private val _insightsState = MutableStateFlow<InsightsUiState>(InsightsUiState.Loading)
    val insightsState: StateFlow<InsightsUiState> = _insightsState.asStateFlow()

    var currentSeerahPage = 1
        private set
    var currentHistoryPage = 1
        private set
    var currentSeerahCategory = ""
        private set
    var currentHistoryCategory = ""
        private set

    init {
        loadSurahs()
        loadLeaderboard()
        loadInsights()
    }

    fun loadSurahs() {
        viewModelScope.launch {
            _surahsState.value = SurahsUiState.Loading
            try {
                val data = dataRepository.getSurahs()
                _surahsState.value = SurahsUiState.Success(data)
            } catch (t: Throwable) {
                _surahsState.value = SurahsUiState.Error(t)
            }
        }
    }

    fun loadLeaderboard() {
        viewModelScope.launch {
            _leaderboardState.value = LeaderboardUiState.Loading
            try {
                val data = dataRepository.getLeaderboard()
                _leaderboardState.value = LeaderboardUiState.Success(data)
            } catch (t: Throwable) {
                _leaderboardState.value = LeaderboardUiState.Error(t)
            }
        }
    }

    fun loadInsights(
        seerahPage: Int = currentSeerahPage,
        historyPage: Int = currentHistoryPage,
        seerahCategory: String = currentSeerahCategory,
        historyCategory: String = currentHistoryCategory
    ) {
        viewModelScope.launch {
            _insightsState.value = InsightsUiState.Loading
            try {
                val data = dataRepository.getInsights(seerahPage, historyPage, seerahCategory, historyCategory)
                currentSeerahPage = data.seerahPage
                currentHistoryPage = data.historyPage
                currentSeerahCategory = seerahCategory
                currentHistoryCategory = historyCategory
                _insightsState.value = InsightsUiState.Success(data)
            } catch (t: Throwable) {
                _insightsState.value = InsightsUiState.Error(t)
            }
        }
    }

    fun nextPageSeerah() {
        loadInsights(seerahPage = currentSeerahPage + 1)
    }

    fun prevPageSeerah() {
        if (currentSeerahPage > 1) {
            loadInsights(seerahPage = currentSeerahPage - 1)
        }
    }

    fun nextPageHistory() {
        loadInsights(historyPage = currentHistoryPage + 1)
    }

    fun prevPageHistory() {
        if (currentHistoryPage > 1) {
            loadInsights(historyPage = currentHistoryPage - 1)
        }
    }

    fun filterSeerahByCategory(category: String) {
        loadInsights(seerahPage = 1, seerahCategory = category)
    }

    fun filterHistoryByCategory(category: String) {
        loadInsights(historyPage = 1, historyCategory = category)
    }
}

sealed interface SurahsUiState {
    object Loading : SurahsUiState
    data class Error(val throwable: Throwable) : SurahsUiState
    data class Success(val data: List<Surah>) : SurahsUiState
}

sealed interface LeaderboardUiState {
    object Loading : LeaderboardUiState
    data class Error(val throwable: Throwable) : LeaderboardUiState
    data class Success(val data: List<LeaderboardUser>) : LeaderboardUiState
}

sealed interface InsightsUiState {
    object Loading : InsightsUiState
    data class Error(val throwable: Throwable) : InsightsUiState
    data class Success(val data: InsightsData) : InsightsUiState
}

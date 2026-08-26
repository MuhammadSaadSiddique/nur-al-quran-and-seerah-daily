package com.example.eternalechomobile.ui.main

import com.example.eternalechomobile.data.*
import junit.framework.TestCase.assertEquals
import kotlinx.coroutines.test.runTest
import org.junit.Test

class MainScreenViewModelTest {
  @Test
  fun uiState_initiallyLoading() = runTest {
    val viewModel = MainScreenViewModel(FakeMyModelRepository())
    assertEquals(viewModel.surahsState.value, SurahsUiState.Loading)
    assertEquals(viewModel.leaderboardState.value, LeaderboardUiState.Loading)
    assertEquals(viewModel.insightsState.value, InsightsUiState.Loading)
  }
}

private class FakeMyModelRepository : DataRepository {
  override suspend fun getSurahs(): List<Surah> = emptyList()
  override suspend fun getVerses(surahNumber: Int): List<Verse> = emptyList()
  override suspend fun getConnections(surahNumber: Int, verseNumber: Int): ConnectionsData = 
    ConnectionsData(emptyList(), emptyList(), emptyList(), emptyList(), emptyList())
  override suspend fun getLeaderboard(): List<LeaderboardUser> = emptyList()
  override suspend fun getInsights(seerahPage: Int, historyPage: Int, seerahCategory: String, historyCategory: String): InsightsData =
    InsightsData(emptyList(), emptyList(), seerahPage, 1, historyPage, 1, emptyList(), emptyList())
  override suspend fun getThemes(): List<Theme> = emptyList()
  override suspend fun getThemeQuiz(themeId: Int, difficulty: String): List<QuizQuestion> = emptyList()
  override suspend fun login(email: String, password: String): UserSession = UserSession(0, "", "", 0)
  override suspend fun register(name: String, email: String, password: String): UserSession = UserSession(0, "", "", 0)
  override suspend fun submitQuiz(
      userId: Int,
      type: String,
      title: String,
      score: Int,
      totalQuestions: Int,
      difficulty: String,
      questionsJson: String,
      userAnswersJson: String
  ): Boolean = true
}

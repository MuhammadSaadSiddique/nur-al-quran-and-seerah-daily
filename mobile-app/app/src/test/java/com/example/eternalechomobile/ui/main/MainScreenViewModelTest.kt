package com.asloobulhayat.eternalecho.ui.main

import com.asloobulhayat.eternalecho.data.*
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
    assertEquals(viewModel.allConnectionsState.value, AllConnectionsUiState.Loading)
  }
}

private class FakeMyModelRepository : DataRepository {
  override suspend fun getSurahs(): List<Surah> {
    kotlinx.coroutines.delay(1000)
    return emptyList()
  }
  override suspend fun getVerses(surahNumber: Int): List<Verse> = emptyList()
  override suspend fun getConnections(surahNumber: Int, verseNumber: Int): ConnectionsData =
    ConnectionsData(emptyList(), emptyList(), emptyList(), emptyList(), emptyList())
  override suspend fun getAllConnections(
      category: String,
      search: String,
      page: Int,
      limit: Int,
      surahNumber: Int?
  ): AllConnectionsResponse {
    kotlinx.coroutines.delay(1000)
    return AllConnectionsResponse(
        stats = ConnectionStats(114, 3954, 477, 1, 1916, 18, 6366),
        category = category,
        page = page,
        totalPages = 1,
        totalItems = 0,
        data = emptyList()
    )
  }
  override suspend fun getLeaderboard(): List<LeaderboardUser> {
    kotlinx.coroutines.delay(1000)
    return emptyList()
  }
  override suspend fun getInsights(seerahPage: Int, historyPage: Int, seerahCategory: String, historyCategory: String): InsightsData {
    kotlinx.coroutines.delay(1000)
    return InsightsData(emptyList(), emptyList(), seerahPage, 1, historyPage, 1, emptyList(), emptyList())
  }
  override suspend fun getThemes(): List<Theme> = emptyList()
  override suspend fun getThemeQuiz(themeId: Int, difficulty: String, quantity: Int): List<QuizQuestion> = emptyList()
  override suspend fun getGrandQuiz(quizType: String, difficulty: String, quantity: Int): List<QuizQuestion> = emptyList()
  override suspend fun login(email: String, password: String): UserSession = UserSession(0, "", "", 0)
  override suspend fun register(name: String, email: String, password: String): UserSession = UserSession(0, "", "", 0)
  override suspend fun requestOtp(email: String): Boolean = true
  override suspend fun verifyOtp(email: String, otp: String): UserSessionOtpResponse =
    UserSessionOtpResponse(UserSession(0, "", "", 0), true)
  override suspend fun setPassword(userId: Int, password: String): Boolean = true
  override suspend fun changePassword(userId: Int, password: String): Boolean = true
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
  override suspend fun getDuas(category: String, search: String, sourceType: String): List<Dua> = emptyList()
}


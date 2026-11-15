package com.asloobulhayat.eternalecho

import com.asloobulhayat.eternalecho.data.QuizSession
import com.asloobulhayat.eternalecho.data.*
import junit.framework.TestCase.*
import kotlinx.coroutines.test.runTest
import org.junit.Test

class AppFeedbackTest {

    private class TestFeedbackRepository : DataRepository {
        var lastRating: Int? = null
        var lastCategory: String? = null
        var lastMessage: String? = null

        override suspend fun getSurahs(): List<Surah> = emptyList()
        override suspend fun getVerses(surahNumber: Int): List<Verse> = emptyList()
        override suspend fun getConnections(surahNumber: Int, verseNumber: Int): ConnectionsData =
            ConnectionsData(emptyList(), emptyList(), emptyList(), emptyList(), emptyList())
        override suspend fun getAllConnections(category: String, search: String, page: Int, limit: Int, surahNumber: Int?): AllConnectionsResponse =
            AllConnectionsResponse(ConnectionStats(0, 0, 0, 0, 0, 0, 0), category, page, 1, 0, emptyList())
        override suspend fun getLeaderboard(): List<LeaderboardUser> = emptyList()
        override suspend fun getInsights(seerahPage: Int, historyPage: Int, seerahCategory: String, historyCategory: String): InsightsData =
            InsightsData(emptyList(), emptyList(), 1, 1, 1, 1, emptyList(), emptyList())
        override suspend fun getThemes(): List<Theme> = emptyList()
        override suspend fun getThemeQuiz(themeId: Int, difficulty: String, quantity: Int): QuizSession = QuizSession(emptyList())
        override suspend fun getGrandQuiz(quizType: String, difficulty: String, quantity: Int): QuizSession = QuizSession(emptyList())
        override suspend fun login(email: String, password: String): UserSession = UserSession(0, "", "", 0)
        override suspend fun register(name: String, email: String, password: String): UserSession = UserSession(0, "", "", 0)
        override suspend fun requestOtp(email: String): Boolean = true
        override suspend fun verifyOtp(email: String, otp: String): UserSessionOtpResponse =
            UserSessionOtpResponse(UserSession(0, "", "", 0), true)
        override suspend fun setPassword(userId: Int, password: String): Boolean = true
        override suspend fun changePassword(userId: Int, password: String, currentPassword: String): Boolean = true
        override suspend fun logout(): Boolean = true
        override suspend fun deleteAccount(userId: Int, currentPassword: String, confirmDeletion: Boolean): Boolean = true
        override suspend fun submitQuiz(userId: Int, type: String, title: String, score: Int, totalQuestions: Int, difficulty: String, questionsJson: String, userAnswersJson: String, attemptToken: String): Boolean = true
        override suspend fun getDuas(category: String, search: String, sourceType: String): List<Dua> = emptyList()

        override suspend fun submitAppFeedback(
            rating: Int,
            category: String,
            message: String,
            userId: Int?,
            userName: String?,
            userEmail: String?,
            deviceInfo: String?
        ): Result<String> {
            lastRating = rating
            lastCategory = category
            lastMessage = message
            return Result.success("JazakAllahu Khairan! Thank you for sharing your feedback.")
        }
    }

    @Test
    fun testSubmitAppFeedbackRepositoryDelegation() = runTest {
        val repo = TestFeedbackRepository()

        val res = repo.submitAppFeedback(
            rating = 5,
            category = "app_experience",
            message = "Alhamdulillah! Amazing app.",
            userId = 1,
            userName = "Talha",
            userEmail = "talha@example.com"
        )

        assertTrue(res.isSuccess)
        assertEquals(5, repo.lastRating)
        assertEquals("app_experience", repo.lastCategory)
        assertEquals("Alhamdulillah! Amazing app.", repo.lastMessage)
    }

    @Test
    fun testDefaultDataRepositoryInstantiation() {
        val defaultRepo = DefaultDataRepository()
        assertNotNull(defaultRepo)
    }
}

package com.asloobulhayat.eternalecho.ui.quiz

import com.asloobulhayat.eternalecho.data.*
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.*
import org.junit.After
import org.junit.Assert.*
import org.junit.Before
import org.junit.Test

@OptIn(ExperimentalCoroutinesApi::class)
class QuizSubmissionTest {

    private val testDispatcher = StandardTestDispatcher()

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun testQuizWorkflowAndGuestCompletion() = runTest {
        val questions = listOf(
            QuizQuestion(
                id = 1,
                questionId = "q1",
                text = "Question 1",
                options = listOf("A", "B", "C", "D"),
                correctAnswerIndex = 0,
                explanation = "Exp 1",
                difficulty = "Easy",
                reference = "Ref 1"
            ),
            QuizQuestion(
                id = 2,
                questionId = "q2",
                text = "Question 2",
                options = listOf("A", "B", "C", "D"),
                correctAnswerIndex = 1,
                explanation = "Exp 2",
                difficulty = "Easy",
                reference = "Ref 2"
            )
        )
        val fakeRepo = FakeQuizRepository(questions)
        val viewModel = PlayThemeQuizViewModel(1, "Easy", fakeRepo)

        testScheduler.advanceUntilIdle()

        assertFalse(viewModel.quizFinished)
        assertEquals(0, viewModel.score)
        assertFalse(viewModel.isScoreSubmitted)

        // Answer question 1 correctly
        val q1 = (viewModel.state.value as PlayQuizUiState.Success).questions[0]
        viewModel.submitAnswer(q1.correctAnswerIndex, q1.correctAnswerIndex)
        assertEquals(1, viewModel.score)

        viewModel.nextQuestion(2)
        assertEquals(1, viewModel.currentQuestionIndex)
        assertFalse(viewModel.quizFinished)

        // Answer question 2 incorrectly
        val q2 = (viewModel.state.value as PlayQuizUiState.Success).questions[1]
        val wrongIndex = if (q2.correctAnswerIndex == 0) 1 else 0
        viewModel.submitAnswer(wrongIndex, q2.correctAnswerIndex)
        assertEquals(1, viewModel.score)

        // Finish quiz as guest
        viewModel.nextQuestion(2)
        assertTrue(viewModel.quizFinished)
        assertFalse(viewModel.isScoreSubmitted)

        // Guest signs up/in, triggering finishAndSubmit with their new userId
        viewModel.finishAndSubmit(
            userId = 42,
            themeName = "Theme 1",
            questions = (viewModel.state.value as PlayQuizUiState.Success).questions
        )
        testScheduler.advanceUntilIdle()

        assertTrue("Expected score submitted, but error: ${viewModel.submissionError}, submitting: ${viewModel.isSubmittingScore}", viewModel.isScoreSubmitted)
        assertEquals(42, fakeRepo.submittedUserId)
        assertEquals("Theme 1", fakeRepo.submittedTitle)
        assertEquals(1, fakeRepo.submittedScore)

        // Play again resets state
        viewModel.resetQuiz()
        testScheduler.advanceUntilIdle()

        assertFalse(viewModel.quizFinished)
        assertEquals(0, viewModel.score)
        assertFalse(viewModel.isScoreSubmitted)
        assertEquals(0, viewModel.currentQuestionIndex)
    }

    @Test
    fun testFinishAndSubmitRequiresValidUserId() = runTest {
        val questions = listOf(
            QuizQuestion(
                id = 1,
                questionId = "q1",
                text = "Question 1",
                options = listOf("A", "B"),
                correctAnswerIndex = 0,
                explanation = "",
                difficulty = "Easy",
                reference = ""
            )
        )
        val fakeRepo = FakeQuizRepository(questions)
        val viewModel = PlayThemeQuizViewModel(1, "Easy", fakeRepo)
        testScheduler.advanceUntilIdle()

        // Attempt submit with guest/invalid userId <= 0 should do nothing
        viewModel.finishAndSubmit(0, "Theme 1", questions)
        testScheduler.advanceUntilIdle()

        assertFalse(viewModel.isScoreSubmitted)
        assertNull(fakeRepo.submittedUserId)

        viewModel.finishAndSubmit(-1, "Theme 1", questions)
        testScheduler.advanceUntilIdle()

        assertFalse(viewModel.isScoreSubmitted)
        assertNull(fakeRepo.submittedUserId)
    }
    @Test
    fun testGrandQuranQuizWorkflowWithQuantity() = runTest {
        val questions = listOf(
            QuizQuestion(id = 1, text = "Q1", options = listOf("A", "B"), correctAnswerIndex = 0),
            QuizQuestion(id = 2, text = "Q2", options = listOf("A", "B"), correctAnswerIndex = 1)
        )
        val fakeRepo = FakeQuizRepository(questions)
        val viewModel = PlayThemeQuizViewModel(
            themeId = 0,
            difficulty = "Hard",
            repository = fakeRepo,
            quantity = 50,
            quizType = "GRAND_QURAN"
        )
        testScheduler.advanceUntilIdle()

        assertEquals("QURAN", fakeRepo.requestedGrandType)
        assertEquals("Hard", fakeRepo.requestedGrandDifficulty)
        assertEquals(50, fakeRepo.requestedGrandQuantity)
        assertTrue(viewModel.state.value is PlayQuizUiState.Success)

        viewModel.finishAndSubmit(
            userId = 10,
            themeName = "Grand Quran Quiz",
            questions = (viewModel.state.value as PlayQuizUiState.Success).questions
        )
        testScheduler.advanceUntilIdle()

        assertTrue(viewModel.isScoreSubmitted)
        assertEquals(10, fakeRepo.submittedUserId)
        assertEquals("GRAND", fakeRepo.submittedType)
        assertEquals("Grand Quran Quiz", fakeRepo.submittedTitle)
    }

    @Test
    fun testGrandSeerahQuizWorkflowWithQuantity() = runTest {
        val questions = listOf(
            QuizQuestion(id = 101, text = "SQ1", options = listOf("A", "B"), correctAnswerIndex = 0)
        )
        val fakeRepo = FakeQuizRepository(questions)
        val viewModel = PlayThemeQuizViewModel(
            themeId = 0,
            difficulty = "Easy",
            repository = fakeRepo,
            quantity = 100,
            quizType = "GRAND_SEERAH"
        )
        testScheduler.advanceUntilIdle()

        assertEquals("SEERAH", fakeRepo.requestedGrandType)
        assertEquals("Easy", fakeRepo.requestedGrandDifficulty)
        assertEquals(100, fakeRepo.requestedGrandQuantity)

        viewModel.finishAndSubmit(
            userId = 25,
            themeName = "Grand Seerah Quiz",
            questions = (viewModel.state.value as PlayQuizUiState.Success).questions
        )
        testScheduler.advanceUntilIdle()

        assertTrue(viewModel.isScoreSubmitted)
        assertEquals("GRAND", fakeRepo.submittedType)
        assertEquals("Grand Seerah Quiz", fakeRepo.submittedTitle)
    }

    @Test
    fun testThemeQuizWithCustomQuantity() = runTest {
        val questions = listOf(
            QuizQuestion(id = 55, text = "TQ1", options = listOf("A", "B"), correctAnswerIndex = 0)
        )
        val fakeRepo = FakeQuizRepository(questions)
        val viewModel = PlayThemeQuizViewModel(
            themeId = 12,
            difficulty = "Medium",
            repository = fakeRepo,
            quantity = 50,
            quizType = "THEME"
        )
        testScheduler.advanceUntilIdle()

        assertEquals(12, fakeRepo.requestedThemeId)
        assertEquals("Medium", fakeRepo.requestedThemeDifficulty)
        assertEquals(50, fakeRepo.requestedThemeQuantity)

        viewModel.finishAndSubmit(
            userId = 99,
            themeName = "Patience and Gratitude",
            questions = (viewModel.state.value as PlayQuizUiState.Success).questions
        )
        testScheduler.advanceUntilIdle()

        assertTrue(viewModel.isScoreSubmitted)
        assertEquals("THEME", fakeRepo.submittedType)
        assertEquals("Patience and Gratitude", fakeRepo.submittedTitle)
    }

    @Test
    fun testThemesCategorizationQuranAndSeerah() {
        val mixedThemes = listOf(
            Theme(id = 1, name = "Prophets in Quran", type = "PARA", description = "Quran theme"),
            Theme(id = 2, name = "Battle of Badr", type = "SEERAH", description = "Seerah theme"),
            Theme(id = 3, name = "Tawheed & Faith", type = "PARA", description = "Quran theme"),
            Theme(id = 4, name = "Migration to Madinah", type = "SEERAH", description = "Seerah theme")
        )

        val quranThemes = mixedThemes.filter { it.type.equals("PARA", ignoreCase = true) }
        val seerahThemes = mixedThemes.filter { it.type.equals("SEERAH", ignoreCase = true) }

        assertEquals(2, quranThemes.size)
        assertEquals(listOf("Prophets in Quran", "Tawheed & Faith"), quranThemes.map { it.name })

        assertEquals(2, seerahThemes.size)
        assertEquals(listOf("Battle of Badr", "Migration to Madinah"), seerahThemes.map { it.name })
    }
}

private class FakeQuizRepository(private val mockQuestions: List<QuizQuestion>) : DataRepository {
    var submittedUserId: Int? = null
    var submittedTitle: String? = null
    var submittedScore: Int? = null
    var submittedType: String? = null

    var requestedThemeId: Int? = null
    var requestedThemeDifficulty: String? = null
    var requestedThemeQuantity: Int? = null

    var requestedGrandType: String? = null
    var requestedGrandDifficulty: String? = null
    var requestedGrandQuantity: Int? = null

    override suspend fun getSurahs(): List<Surah> = emptyList()
    override suspend fun getVerses(surahNumber: Int): List<Verse> = emptyList()
    override suspend fun getConnections(surahNumber: Int, verseNumber: Int): ConnectionsData =
        ConnectionsData(emptyList(), emptyList(), emptyList(), emptyList(), emptyList())
    override suspend fun getAllConnections(
        category: String,
        search: String,
        page: Int,
        limit: Int,
        surahNumber: Int?
    ): AllConnectionsResponse = AllConnectionsResponse(
        stats = ConnectionStats(114, 3954, 477, 1, 1916, 18, 6366),
        category = category,
        page = page,
        totalPages = 1,
        totalItems = 0,
        data = emptyList()
    )
    override suspend fun getLeaderboard(): List<LeaderboardUser> = emptyList()
    override suspend fun getInsights(seerahPage: Int, historyPage: Int, seerahCategory: String, historyCategory: String): InsightsData =
        InsightsData(emptyList(), emptyList(), seerahPage, 1, historyPage, 1, emptyList(), emptyList())
    override suspend fun getThemes(): List<Theme> = emptyList()
    override suspend fun getThemeQuiz(themeId: Int, difficulty: String, quantity: Int): List<QuizQuestion> {
        requestedThemeId = themeId
        requestedThemeDifficulty = difficulty
        requestedThemeQuantity = quantity
        return mockQuestions
    }
    override suspend fun getGrandQuiz(quizType: String, difficulty: String, quantity: Int): List<QuizQuestion> {
        requestedGrandType = quizType
        requestedGrandDifficulty = difficulty
        requestedGrandQuantity = quantity
        return mockQuestions
    }
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
    ): Boolean {
        submittedUserId = userId
        submittedType = type
        submittedTitle = title
        submittedScore = score
        return true
    }
    override suspend fun getDuas(category: String, search: String, sourceType: String): List<Dua> = emptyList()
}

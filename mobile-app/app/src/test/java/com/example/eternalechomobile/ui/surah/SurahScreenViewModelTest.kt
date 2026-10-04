package com.asloobulhayat.eternalecho.ui.surah

import com.asloobulhayat.eternalecho.data.*
import junit.framework.TestCase.assertEquals
import junit.framework.TestCase.assertTrue
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.*
import org.junit.After
import org.junit.Before
import org.junit.Test

@OptIn(ExperimentalCoroutinesApi::class)
class SurahScreenViewModelTest {
    private val testDispatcher = StandardTestDispatcher()

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    private val fakeRepo = object : DataRepository {
        override suspend fun getSurahs(): List<Surah> = emptyList()

        override suspend fun getVerses(surahNumber: Int): List<Verse> = listOf(
            Verse(
                id = 1,
                verseNumber = 1,
                verseKey = "96:1",
                juzNumber = 30,
                textArabic = "اقْرَأْ بِاسْمِ رَبِّكَ الَّذِي خَلَقَ",
                textTransliteration = "Iqra bi-ismi rabbika allathee khalaq"
            )
        )

        override suspend fun getConnections(surahNumber: Int, verseNumber: Int): ConnectionsData = ConnectionsData(
            science = listOf(
                Connection(
                    title = "Origin of Life in Water",
                    description = "All life forms depend on aqueous environments.",
                    relevanceDescription = "Matches biological origin",
                    category = "Biology",
                    sourceName = "Nature 2021",
                    credibilityScore = "9.5/10"
                )
            ),
            seerah = listOf(
                Connection(
                    title = "First Revelation at Cave Hira",
                    description = "The Prophet received the first revelation through Gabriel.",
                    relevanceDescription = "Historical context of revelation",
                    category = "Prophethood",
                    dateInfo = "13 BH / 610 CE",
                    location = "Cave Hira, Makkah",
                    sourceName = "Sahih Bukhari"
                )
            ),
            hadith = listOf(
                Connection(
                    title = "Sahih al-Bukhari (#3)",
                    description = "The start of divine inspiration to the Messenger of Allah...",
                    relevanceDescription = "Direct hadith context of Iqra",
                    arabicText = "أَوَّلُ مَا بُدِئَ بِهِ رَسُولُ اللَّهِ...",
                    narratorChain = "Aisha (RA)",
                    grading = "Sahih",
                    collectionName = "Sahih al-Bukhari",
                    hadithNumber = "3"
                )
            ),
            history = listOf(
                Connection(
                    title = "Pre-Islamic Arabian Society",
                    description = "Society before the dawn of Islam.",
                    relevanceDescription = "Societal state prior to revelation",
                    dateInfo = "6th Century CE",
                    location = "Hejaz",
                    scriptureType = "Arabian Peninsula"
                )
            ),
            scripture = emptyList()
        )

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
            InsightsData(emptyList(), emptyList(), 1, 1, 1, 1, emptyList(), emptyList())
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

    @Test
    fun testVersesLoadingAndSuccess() = runTest(testDispatcher) {
        val viewModel = SurahScreenViewModel(96, fakeRepo)
        advanceUntilIdle()

        val state = viewModel.versesState.value
        assertTrue(state is VersesUiState.Success)
        val verses = (state as VersesUiState.Success).data
        assertEquals(1, verses.size)
        assertEquals(1, verses[0].verseNumber)
        assertEquals("96:1", verses[0].verseKey)
    }

    @Test
    fun testConnectionsLoadingAndFields() = runTest(testDispatcher) {
        val viewModel = SurahScreenViewModel(96, fakeRepo)
        advanceUntilIdle()
        assertEquals(ConnectionsUiState.Idle, viewModel.connectionsState.value)

        viewModel.loadConnections(1)
        advanceUntilIdle()

        val connState = viewModel.connectionsState.value
        assertTrue(connState is ConnectionsUiState.Success)

        val success = connState as ConnectionsUiState.Success
        assertEquals(1, success.verseNumber)

        // Science verification
        assertEquals(1, success.data.science.size)
        val sci = success.data.science[0]
        assertEquals("Origin of Life in Water", sci.title)
        assertEquals("9.5/10", sci.credibilityScore)
        assertEquals("Matches biological origin", sci.relevanceDescription)

        // Seerah verification
        assertEquals(1, success.data.seerah.size)
        val seer = success.data.seerah[0]
        assertEquals("First Revelation at Cave Hira", seer.title)
        assertEquals("13 BH / 610 CE", seer.dateInfo)
        assertEquals("Cave Hira, Makkah", seer.location)

        // Hadith verification
        assertEquals(1, success.data.hadith.size)
        val had = success.data.hadith[0]
        assertEquals("Sahih al-Bukhari (#3)", had.title)
        assertEquals("Sahih", had.grading)
        assertEquals("Aisha (RA)", had.narratorChain)
        assertTrue(had.arabicText.contains("أَوَّلُ"))

        // History verification
        assertEquals(1, success.data.history.size)
        val hist = success.data.history[0]
        assertEquals("Pre-Islamic Arabian Society", hist.title)
        assertEquals("6th Century CE", hist.dateInfo)

        // Clear connections
        viewModel.clearConnections()
        assertEquals(ConnectionsUiState.Idle, viewModel.connectionsState.value)
    }
}

package com.asloobulhayat.eternalecho.data

import kotlinx.serialization.Serializable
import androidx.navigation3.runtime.NavKey

@Serializable
data class Surah(
    val id: Int,
    val number: Int,
    val nameArabic: String,
    val nameSimple: String,
    val nameTransliteration: String,
    val nameTranslated: String,
    val revelationPlace: String,
    val versesCount: Int
)

@Serializable
data class VerseTranslation(
    val id: String,
    val name: String,
    val language: String,
    val text: String,
    val direction: String = "ltr"
)

@Serializable
data class Verse(
    val id: Int,
    val verseNumber: Int,
    val verseKey: String,
    val juzNumber: Int,
    val textArabic: String,
    val textTransliteration: String,
    val translations: List<VerseTranslation> = emptyList()
) {
    fun translationFor(editionId: String): VerseTranslation? {
        if (translations.isEmpty()) return null
        return translations.firstOrNull { it.id == editionId } ?: translations.first()
    }
}

@Serializable
data class Connection(
    val title: String,
    val description: String,
    val extraInfo: String = "",
    val relevanceDescription: String = "",
    val category: String = "",
    val dateInfo: String = "",
    val location: String = "",
    val sourceName: String = "",
    val credibilityScore: String = "",
    val arabicText: String = "",
    val narratorChain: String = "",
    val grading: String = "",
    val collectionName: String = "",
    val hadithNumber: String = "",
    val scriptureType: String = "",
    val relationshipType: String = ""
)

@Serializable
data class ConnectionsData(
    val science: List<Connection>,
    val seerah: List<Connection>,
    val hadith: List<Connection>,
    val history: List<Connection>,
    val scripture: List<Connection>
)

@Serializable
data class ConnectionStats(
    val surahsCount: Int = 114,
    val scienceCount: Int = 0,
    val seerahCount: Int = 0,
    val hadithCount: Int = 0,
    val historyCount: Int = 0,
    val scriptureCount: Int = 0,
    val totalCount: Int = 0
)

@Serializable
data class GlobalConnectionItem(
    val id: Int,
    val category: String,
    val surahNumber: Int,
    val surahName: String,
    val verseNumber: Int,
    val juzNumber: Int,
    val verseArabic: String,
    val verseTransliteration: String,
    val title: String,
    val description: String,
    val extraInfo: String = "",
    val relevanceDescription: String = "",
    val field: String = "",
    val sourceName: String = "",
    val credibilityScore: String = "",
    val dateInfo: String = "",
    val location: String = "",
    val arabicText: String = "",
    val narratorChain: String = "",
    val grading: String = "",
    val collectionName: String = "",
    val hadithNumber: String = "",
    val scriptureType: String = "",
    val relationshipType: String = ""
) {
    fun toConnection(): Connection = Connection(
        title = title,
        description = description,
        extraInfo = extraInfo,
        relevanceDescription = relevanceDescription,
        category = category,
        dateInfo = dateInfo,
        location = location,
        sourceName = sourceName,
        credibilityScore = credibilityScore,
        arabicText = arabicText,
        narratorChain = narratorChain,
        grading = grading,
        collectionName = collectionName,
        hadithNumber = hadithNumber,
        scriptureType = scriptureType,
        relationshipType = relationshipType
    )
}

@Serializable
data class AllConnectionsResponse(
    val stats: ConnectionStats,
    val category: String,
    val page: Int,
    val totalPages: Int,
    val totalItems: Int,
    val data: List<GlobalConnectionItem>
)

@Serializable
data class LeaderboardUser(
    val id: Int,
    val name: String,
    val displayName: String,
    val email: String,
    val totalScore: Int,
    val totalQuestions: Int,
    val seerahReadCount: Int
)

@Serializable
data class SeerahEvent(
    val id: Int,
    val title: String,
    val description: String,
    val category: String,
    val questionText: String = "",
    val options: List<String> = emptyList(),
    val correctAnswerIndex: Int = 0,
    val explanation: String = ""
)

@Serializable
data class HistoryEvent(
    val id: Int,
    val title: String,
    val description: String,
    val category: String = "",
    val questionText: String = "",
    val options: List<String> = emptyList(),
    val correctAnswerIndex: Int = 0,
    val explanation: String = ""
)

@Serializable
data class InsightsData(
    val seerahEvents: List<SeerahEvent>,
    val historyEvents: List<HistoryEvent>,
    val seerahPage: Int = 1,
    val seerahTotalPages: Int = 1,
    val historyPage: Int = 1,
    val historyTotalPages: Int = 1,
    val seerahCategories: List<String> = emptyList(),
    val historyCategories: List<String> = emptyList()
)

@Serializable
data class SurahNavKey(val surahNumber: Int, val verseNumber: Int? = null) : NavKey

@Serializable
data class Theme(
    val id: Int,
    val name: String,
    val type: String,
    val description: String? = null
)

@Serializable
data class QuizQuestion(
    val id: Int,
    val questionId: String = "",
    val text: String,
    val options: List<String>,
    val correctAnswerIndex: Int = -1,
    val explanation: String? = "",
    val difficulty: String = "",
    val reference: String? = "",
    val sourceInfo: String? = "",
    /** Original server option order — used to map shuffled selections back for scoring. */
    val originalOptions: List<String> = emptyList()
) {
    fun scoringId(): String = questionId.ifBlank { id.toString() }

    fun toOriginalAnswerIndex(shuffledIndex: Int?): Int {
        if (shuffledIndex == null || shuffledIndex < 0) return -1
        val selectedText = options.getOrNull(shuffledIndex) ?: return shuffledIndex
        val originals = originalOptions.ifEmpty { options }
        val mapped = originals.indexOf(selectedText)
        return if (mapped >= 0) mapped else shuffledIndex
    }
}

data class AnswerReveal(
    val correct: Boolean,
    val correctAnswerIndex: Int,
    val explanation: String = "",
    val questionId: String = ""
)

@Serializable
data class UserSession(
    val userId: Int,
    val name: String,
    val email: String,
    val totalScore: Int,
    val token: String = ""
)

@Serializable
data class UserSessionOtpResponse(
    val session: UserSession,
    val hasPassword: Boolean
)

@Serializable
data class DuaWord(
    val arabic: String,
    val transliteration: String,
    val meaningEn: String,
    val meaningUr: String? = null
)

@Serializable
data class Dua(
    val id: Int,
    val title: String,
    val slug: String,
    val category: String,
    val description: String? = null,
    val arabicText: String,
    val transliteration: String? = null,
    val translationEn: String,
    val translationUr: String? = null,
    val wordByWord: List<DuaWord>? = null,
    val meaningExplanation: String? = null,
    val benefitsAndVirtues: String? = null,
    val whenToRecite: String? = null,
    val repeatCount: Int = 1,
    val sourceType: String = "hadith",
    val quranReference: String? = null,
    val surahNumber: Int? = null,
    val verseNumber: Int? = null,
    val hadithReference: String? = null,
    val hadithBook: String? = null,
    val hadithNumber: String? = null,
    val hadithGrading: String? = null
)

@Serializable
data class NotificationItem(
    val id: Long,
    val title: String,
    val message: String,
    val type: String = "announcement",
    val actionUrl: String? = null,
    val createdAt: String? = null,
    val isRead: Boolean = false
)

@Serializable
data class StreakData(
    val currentStreak: Int = 0,
    val longestStreak: Int = 0,
    val isActiveToday: Boolean = false,
    val lastStreakDate: String? = null,
    val streakHistory: List<String> = emptyList()
)

@Serializable
data class EngagementActivity(
    val key: String,
    val done: Boolean = false,
    val label: String = "",
    val href: String = ""
)

@Serializable
data class DailyEngagement(
    val date: String = "",
    val activities: List<EngagementActivity> = emptyList(),
    val completedCount: Int = 0,
    val total: Int = 4,
    val progressPercent: Int = 0,
    val streak: StreakData = StreakData()
)

@Serializable
data class ResearchContribution(
    val id: Int,
    val userId: Int? = null,
    val submitterName: String? = null,
    val surahNumber: Int = 1,
    val endSurahNumber: Int? = null,
    val surahNumbers: List<Int>? = null,
    val verseNumber: Int = 1,
    val endVerseNumber: Int? = null,
    val isFullSurah: Boolean = false,
    val scopeLabel: String = "",
    val surahName: String? = null,
    val category: String = "video",
    val title: String,
    val description: String? = null,
    val videoUrl: String? = null,
    val videoPlatform: String? = null,
    val videoId: String? = null,
    val status: String = "pending",
    val approvalsCount: Int = 0,
    val downvotesCount: Int = 0,
    val myVote: String? = null,
    val myFeedback: String? = null,
    val createdAt: String? = null
)

@Serializable
data class DifficultyStat(
    val total: Int = 0,
    val correct: Int = 0,
    val accuracy: Int = 0
)

@Serializable
data class DifficultyStatsGroup(
    val easy: DifficultyStat = DifficultyStat(),
    val medium: DifficultyStat = DifficultyStat(),
    val hard: DifficultyStat = DifficultyStat()
)

@Serializable
data class UserProfileStats(
    val userId: Int,
    val name: String,
    val email: String,
    val spiritualLevel: String = "Novice",
    val totalScore: Int = 0,
    val maxPossibleScore: Int = 0,
    val totalQuestions: Int = 0,
    val accuracy: Int = 0,
    val surahsCompletedCount: Int = 0,
    val parasCompletedCount: Int = 0,
    val seerahReadCount: Int = 0,
    val quranHistoryReadCount: Int = 0,
    val bookmarksCount: Int = 0,
    val streak: StreakData = StreakData(),
    val engagement: DailyEngagement? = null,
    val difficulty: DifficultyStatsGroup = DifficultyStatsGroup(),
    val hasPassword: Boolean = false
)

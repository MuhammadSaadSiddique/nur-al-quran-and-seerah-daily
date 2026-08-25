package com.example.eternalechomobile.data

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
data class Verse(
    val id: Int,
    val verseNumber: Int,
    val verseKey: String,
    val juzNumber: Int,
    val textArabic: String,
    val textTransliteration: String
)

@Serializable
data class Connection(
    val title: String,
    val description: String,
    val extraInfo: String
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
data class SurahNavKey(val surahNumber: Int) : NavKey

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
    val correctAnswerIndex: Int,
    val explanation: String? = "",
    val difficulty: String = "",
    val reference: String? = "",
    val sourceInfo: String? = ""
)

@Serializable
data class UserSession(
    val userId: Int,
    val name: String,
    val email: String,
    val totalScore: Int
)

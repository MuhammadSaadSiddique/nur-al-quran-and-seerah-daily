package com.example.eternalechomobile.data

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow

interface DataRepository {
    suspend fun getSurahs(): List<Surah>
    suspend fun getVerses(surahNumber: Int): List<Verse>
    suspend fun getConnections(surahNumber: Int, verseNumber: Int): ConnectionsData
    suspend fun getLeaderboard(): List<LeaderboardUser>
    suspend fun getInsights(
        seerahPage: Int = 1,
        historyPage: Int = 1,
        seerahCategory: String = "",
        historyCategory: String = ""
    ): InsightsData
    suspend fun getThemes(): List<Theme>
    suspend fun getThemeQuiz(themeId: Int, difficulty: String): List<QuizQuestion>
    suspend fun login(email: String, password: String): UserSession
    suspend fun register(name: String, email: String, password: String): UserSession
    suspend fun submitQuiz(
        userId: Int,
        type: String,
        title: String,
        score: Int,
        totalQuestions: Int,
        difficulty: String,
        questionsJson: String,
        userAnswersJson: String
    ): Boolean
}

class DefaultDataRepository : DataRepository {
    override suspend fun getSurahs(): List<Surah> {
        return ApiClient.fetchSurahs()
    }

    override suspend fun getVerses(surahNumber: Int): List<Verse> {
        return ApiClient.fetchVerses(surahNumber)
    }

    override suspend fun getConnections(surahNumber: Int, verseNumber: Int): ConnectionsData {
        return ApiClient.fetchConnections(surahNumber, verseNumber)
    }

    override suspend fun getLeaderboard(): List<LeaderboardUser> {
        return ApiClient.fetchLeaderboard()
    }

    override suspend fun getInsights(
        seerahPage: Int,
        historyPage: Int,
        seerahCategory: String,
        historyCategory: String
    ): InsightsData {
        return ApiClient.fetchInsights(seerahPage, historyPage, seerahCategory, historyCategory)
    }

    override suspend fun getThemes(): List<Theme> {
        return ApiClient.fetchThemes()
    }

    override suspend fun getThemeQuiz(themeId: Int, difficulty: String): List<QuizQuestion> {
        return ApiClient.fetchThemeQuiz(themeId, difficulty)
    }

    override suspend fun login(email: String, password: String): UserSession {
        return ApiClient.login(email, password)
    }

    override suspend fun register(name: String, email: String, password: String): UserSession {
        return ApiClient.register(name, email, password)
    }

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
        return ApiClient.submitQuiz(userId, type, title, score, totalQuestions, difficulty, questionsJson, userAnswersJson)
    }
}

package com.asloobulhayat.eternalecho.data

import com.asloobulhayat.eternalecho.security.SecurePreferences
import org.json.JSONArray
import org.json.JSONObject

object QuizSubmissionHelper {
    const val KEY_HAS_PENDING = "has_pending_quiz"
    const val KEY_PENDING_TYPE = "pending_quiz_type"
    const val KEY_PENDING_TITLE = "pending_quiz_title"
    const val KEY_PENDING_SCORE = "pending_quiz_score"
    const val KEY_PENDING_TOTAL = "pending_quiz_total"
    const val KEY_PENDING_DIFFICULTY = "pending_quiz_difficulty"
    const val KEY_PENDING_QUESTIONS = "pending_quiz_questions"
    const val KEY_PENDING_ANSWERS = "pending_quiz_answers"

    fun savePendingQuiz(
        prefs: SecurePreferences,
        type: String,
        title: String,
        score: Int,
        totalQuestions: Int,
        difficulty: String,
        questions: List<QuizQuestion>,
        userAnswers: List<Int?>
    ) {
        val questionsArray = JSONArray()
        questions.forEach { q ->
            val obj = JSONObject()
            obj.put("id", q.id)
            obj.put("text", q.text)
            obj.put("correctAnswerIndex", q.correctAnswerIndex)
            obj.put("difficulty", q.difficulty)
            val opts = JSONArray()
            q.options.forEach { opts.put(it) }
            obj.put("options", opts)
            questionsArray.put(obj)
        }

        val answersArray = JSONArray()
        userAnswers.forEach { answersArray.put(it ?: -1) }

        savePendingRawQuiz(
            prefs = prefs,
            type = type,
            title = title,
            score = score,
            totalQuestions = totalQuestions,
            difficulty = difficulty,
            questionsJson = questionsArray.toString(),
            userAnswersJson = answersArray.toString()
        )
    }

    fun savePendingRawQuiz(
        prefs: SecurePreferences,
        type: String,
        title: String,
        score: Int,
        totalQuestions: Int,
        difficulty: String,
        questionsJson: String,
        userAnswersJson: String
    ) {
        prefs.putBoolean(KEY_HAS_PENDING, true)
        prefs.putString(KEY_PENDING_TYPE, type)
        prefs.putString(KEY_PENDING_TITLE, title)
        prefs.putInt(KEY_PENDING_SCORE, score)
        prefs.putInt(KEY_PENDING_TOTAL, totalQuestions)
        prefs.putString(KEY_PENDING_DIFFICULTY, difficulty)
        prefs.putString(KEY_PENDING_QUESTIONS, questionsJson)
        prefs.putString(KEY_PENDING_ANSWERS, userAnswersJson)
    }

    fun hasPendingQuiz(prefs: SecurePreferences): Boolean {
        return prefs.getBoolean(KEY_HAS_PENDING, false)
    }

    fun getPendingScore(prefs: SecurePreferences): Pair<Int, Int> {
        val score = prefs.getInt(KEY_PENDING_SCORE, 0)
        val total = prefs.getInt(KEY_PENDING_TOTAL, 0)
        return Pair(score, total)
    }

    fun clearPendingQuiz(prefs: SecurePreferences) {
        prefs.putBoolean(KEY_HAS_PENDING, false)
        prefs.remove(KEY_PENDING_TYPE)
        prefs.remove(KEY_PENDING_TITLE)
        prefs.remove(KEY_PENDING_SCORE)
        prefs.remove(KEY_PENDING_TOTAL)
        prefs.remove(KEY_PENDING_DIFFICULTY)
        prefs.remove(KEY_PENDING_QUESTIONS)
        prefs.remove(KEY_PENDING_ANSWERS)
    }

    suspend fun submitPendingQuizIfAny(
        repository: DataRepository,
        prefs: SecurePreferences,
        userId: Int
    ): Boolean {
        if (userId <= 0 || !hasPendingQuiz(prefs)) return false
        val type = prefs.getString(KEY_PENDING_TYPE, "THEME") ?: "THEME"
        val title = prefs.getString(KEY_PENDING_TITLE, "") ?: ""
        val score = prefs.getInt(KEY_PENDING_SCORE, 0)
        val total = prefs.getInt(KEY_PENDING_TOTAL, 0)
        val difficulty = prefs.getString(KEY_PENDING_DIFFICULTY, "Medium") ?: "Medium"
        val questionsJson = prefs.getString(KEY_PENDING_QUESTIONS, "[]") ?: "[]"
        val answersJson = prefs.getString(KEY_PENDING_ANSWERS, "[]") ?: "[]"

        return try {
            val success = repository.submitQuiz(
                userId = userId,
                type = type,
                title = title,
                score = score,
                totalQuestions = total,
                difficulty = difficulty,
                questionsJson = questionsJson,
                userAnswersJson = answersJson
            )
            if (success) {
                clearPendingQuiz(prefs)
            }
            success
        } catch (e: Exception) {
            android.util.Log.e("QuizSubmissionHelper", "Failed to submit pending quiz", e)
            false
        }
    }
}

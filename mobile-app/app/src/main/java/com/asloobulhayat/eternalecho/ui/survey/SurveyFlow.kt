package com.asloobulhayat.eternalecho.ui.survey

import com.asloobulhayat.eternalecho.data.SurveyAnswers
import com.asloobulhayat.eternalecho.data.SurveyConfig
import com.asloobulhayat.eternalecho.data.SurveyFeature
import com.asloobulhayat.eternalecho.data.SurveyQuestion
import com.asloobulhayat.eternalecho.data.SurveyReaction

/** One screen of the survey. [id] is stable across launches, so a saved draft can resume on it. */
sealed interface SurveyStep {
    val id: String

    data object Welcome : SurveyStep { override val id = "welcome" }
    data class Choice(val question: SurveyQuestion) : SurveyStep { override val id get() = question.key }
    data object Features : SurveyStep { override val id = "features" }
    data object Nps : SurveyStep { override val id = "nps" }
    data object Wish : SurveyStep { override val id = "wish" }
    data object Contact : SurveyStep { override val id = "contact" }
    data object Done : SurveyStep { override val id = "done" }
}

/** Pure survey rules (no Android types), shared by the ViewModel and unit tests. */
object SurveyFlow {

    /** How many choice questions come before the feature deck: get to know them, then show the app. */
    private const val QUESTIONS_BEFORE_DECK = 3

    private val GOAL_TO_FEATURE = mapOf(
        "quran_understanding" to "quranic_lens",
        "seerah" to "seerah",
        "quizzes" to "para_quizzes",
        "duas" to "duas",
        "research" to "research_community",
        "salah" to "salah",
        "family" to "para_quizzes",
        "habit" to "streaks",
    )

    private val EMAIL = Regex("^[^\\s@]+@[^\\s@]+\\.[^\\s@]{2,}$")

    fun steps(config: SurveyConfig): List<SurveyStep> {
        val choices = config.questions.map { SurveyStep.Choice(it) }
        val split = minOf(QUESTIONS_BEFORE_DECK, choices.size)
        return buildList {
            add(SurveyStep.Welcome)
            addAll(choices.take(split))
            if (config.features.isNotEmpty()) add(SurveyStep.Features)
            addAll(choices.drop(split))
            add(SurveyStep.Nps)
            add(SurveyStep.Wish)
            add(SurveyStep.Contact)
            add(SurveyStep.Done)
        }
    }

    sealed interface ChoiceResult {
        data class Updated(val answers: SurveyAnswers, val selected: Boolean) : ChoiceResult
        /** A multi-pick question is already at its limit. */
        data object Full : ChoiceResult
    }

    fun choose(answers: SurveyAnswers, question: SurveyQuestion, value: String): ChoiceResult {
        if (question.options.none { it.value == value }) return ChoiceResult.Updated(answers, false)
        val current = answers.picked(question.key)
        val next = when {
            !question.isMulti -> listOf(value)
            value in current -> current - value
            current.size >= question.max -> return ChoiceResult.Full
            else -> current + value
        }
        return ChoiceResult.Updated(answers.copy(choices = answers.choices + (question.key to next)), value in next)
    }

    fun isEmailValid(email: String): Boolean = email.isBlank() || EMAIL.matches(email.trim())

    fun canContinue(step: SurveyStep, answers: SurveyAnswers, deckDone: Boolean): Boolean = when (step) {
        is SurveyStep.Choice -> answers.picked(step.question.key).isNotEmpty()
        SurveyStep.Features -> deckDone
        SurveyStep.Nps -> answers.nps != null
        SurveyStep.Wish -> true
        SurveyStep.Contact -> isEmailValid(answers.email)
        SurveyStep.Welcome, SurveyStep.Done -> false
    }

    /**
     * Up to three features to suggest at the end: ones they wanted to try first, then ones matching
     * their goals, then ones they loved. Never one they said was not for them.
     */
    fun picks(config: SurveyConfig, answers: SurveyAnswers, goalsKey: String = "goals"): List<SurveyFeature> {
        val reactions = answers.reactions
        val order = buildList {
            config.features.filter { reactions[it.key] == SurveyReaction.TRY }.forEach { add(it.key) }
            answers.picked(goalsKey).mapNotNull { GOAL_TO_FEATURE[it] }.forEach { add(it) }
            config.features.filter { reactions[it.key] == SurveyReaction.LOVE }.forEach { add(it.key) }
        }
        return order.distinct()
            .filter { reactions[it] != SurveyReaction.MEH }
            .mapNotNull { key -> config.features.firstOrNull { it.key == key } }
            .take(3)
    }

    fun npsFace(score: Int?): String = when {
        score == null -> "🤔"
        score <= 2 -> "😞"
        score <= 4 -> "😕"
        score <= 6 -> "😐"
        score <= 8 -> "🙂"
        score == 9 -> "😊"
        else -> "😍"
    }

    fun npsLabel(score: Int?): String = score?.let {
        listOf(
            "Not at all", "Very unlikely", "Unlikely", "Probably not", "Maybe not", "Not sure",
            "Maybe", "Likely", "Very likely", "Definitely", "Already have! 💚"
        )[it.coerceIn(0, 10)]
    } ?: "Tap a number"
}

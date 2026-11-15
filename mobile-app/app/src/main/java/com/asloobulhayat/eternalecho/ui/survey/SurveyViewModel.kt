package com.asloobulhayat.eternalecho.ui.survey

import android.app.Application
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import com.asloobulhayat.eternalecho.data.SurveyAnswers
import com.asloobulhayat.eternalecho.data.SurveyApi
import com.asloobulhayat.eternalecho.data.SurveyConfig
import com.asloobulhayat.eternalecho.data.SurveyJson
import com.asloobulhayat.eternalecho.data.SurveyQuestion
import com.asloobulhayat.eternalecho.data.SurveyReaction
import com.asloobulhayat.eternalecho.data.SurveyStore
import com.asloobulhayat.eternalecho.security.SecurePreferences
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import org.json.JSONObject

data class SurveyToast(val id: Long, val text: String, val xp: Int = 0)

data class SurveyUiState(
    val loading: Boolean = true,
    val loadError: String? = null,
    val config: SurveyConfig? = null,
    val steps: List<SurveyStep> = emptyList(),
    val index: Int = 0,
    val forward: Boolean = true,
    val answers: SurveyAnswers = SurveyAnswers(),
    val deckIndex: Int = 0,
    val xp: Int = 0,
    val awarded: Set<String> = emptySet(),
    val toast: SurveyToast? = null,
    /** "questionKey:value" of a tile to shake (multi-pick already full), with a nonce so repeats re-shake. */
    val shake: Pair<String, Long>? = null,
    val submitting: Boolean = false,
    val submitError: String? = null,
    /** Step to offer "continue where you left off" on, from a saved draft. */
    val resumeIndex: Int? = null,
    val alreadyAnswered: Boolean = false,
    val signedIn: Boolean = false,
    val startedAt: Long = 0L
) {
    val step: SurveyStep get() = steps.getOrNull(index) ?: SurveyStep.Welcome
    /** Questions shown in the progress bar: everything between the welcome and done screens. */
    val totalQuestions: Int get() = (steps.size - 2).coerceAtLeast(1)
    val questionNumber: Int get() = index.coerceIn(0, totalQuestions)
    val inQuestions: Boolean get() = step != SurveyStep.Welcome && step != SurveyStep.Done
    val deckDone: Boolean get() = deckIndex >= (config?.features?.size ?: 0)
    val canContinue: Boolean get() = SurveyFlow.canContinue(step, answers, deckDone)
}

class SurveyViewModel(application: Application) : AndroidViewModel(application) {

    private val context get() = getApplication<Application>()
    private val prefs = SecurePreferences.getInstance(application)

    private val _state = MutableStateFlow(SurveyUiState())
    val state: StateFlow<SurveyUiState> = _state.asStateFlow()

    private var autoAdvance: Job? = null
    private var toastJob: Job? = null
    private var cheer = 0

    init {
        SurveyStore.markOpened(application)
        load()
    }

    fun load() {
        _state.update { it.copy(loading = true, loadError = null) }
        viewModelScope.launch {
            SurveyApi.config()
                .onSuccess { config -> applyConfig(config) }
                .onFailure { e ->
                    _state.update { it.copy(loading = false, loadError = e.message ?: "Couldn't load the survey.") }
                }
        }
    }

    private fun applyConfig(config: SurveyConfig) {
        val steps = SurveyFlow.steps(config)
        val signedIn = !prefs.getString("api_token", null).isNullOrBlank()
        var next = SurveyUiState(
            loading = false,
            config = config,
            steps = steps,
            answers = withSignedInContact(SurveyAnswers()),
            alreadyAnswered = config.previous != null || SurveyStore.isDone(context, config.key),
            signedIn = signedIn
        )

        SurveyStore.loadDraft(context)?.takeIf { it.optString("round") == config.key }?.let { draft ->
            val resumeAt = steps.indexOfFirst { it.id == draft.optString("step") }
            if (resumeAt > 0 && steps[resumeAt] != SurveyStep.Done) {
                val answers = draft.optJSONObject("answers")
                    ?.let { SurveyJson.answersFrom(it, config.questions.map { q -> q.key }) }
                    ?: SurveyAnswers()
                next = next.copy(
                    answers = withSignedInContact(answers),
                    deckIndex = draft.optInt("deckIndex").coerceIn(0, config.features.size),
                    xp = draft.optInt("xp"),
                    awarded = draft.optJSONArray("awarded")?.let { a -> (0 until a.length()).map { a.optString(it) }.toSet() }.orEmpty(),
                    startedAt = draft.optLong("startedAt", System.currentTimeMillis()),
                    resumeIndex = resumeAt
                )
            }
        }
        _state.value = next
    }

    private fun withSignedInContact(answers: SurveyAnswers): SurveyAnswers = answers.copy(
        name = answers.name.ifBlank { prefs.getString("user_name", null).orEmpty() },
        email = answers.email.ifBlank { prefs.getString("user_email", null).orEmpty() }
    )

    // ---------- navigation ----------

    fun start() {
        val config = _state.value.config ?: return
        // Updating earlier answers? Start from them rather than a blank form.
        val base = config.previous ?: SurveyAnswers()
        _state.update {
            it.copy(
                answers = withSignedInContact(base),
                xp = 0, awarded = emptySet(), deckIndex = 0,
                startedAt = System.currentTimeMillis(), resumeIndex = null
            )
        }
        goTo(1, forward = true)
    }

    fun resume() {
        val target = _state.value.resumeIndex ?: return
        _state.update { it.copy(resumeIndex = null) }
        goTo(target, forward = true)
    }

    fun restart() {
        SurveyStore.clearDraft(context)
        start()
    }

    fun next() {
        val s = _state.value
        if (!s.canContinue || s.index >= s.steps.lastIndex) return
        award(s.step)
        goTo(s.index + 1, forward = true)
    }

    /** Returns false when there is no earlier question, so the caller can leave the screen. */
    fun back(): Boolean {
        val s = _state.value
        if (s.submitting || !s.inQuestions || s.index <= 1) return false
        goTo(s.index - 1, forward = false)
        return true
    }

    private fun goTo(index: Int, forward: Boolean) {
        autoAdvance?.cancel()
        _state.update { it.copy(index = index.coerceIn(0, it.steps.lastIndex), forward = forward, submitError = null) }
        saveDraft()
    }

    // ---------- answers ----------

    fun choose(question: SurveyQuestion, value: String) {
        when (val result = SurveyFlow.choose(_state.value.answers, question, value)) {
            SurveyFlow.ChoiceResult.Full -> {
                _state.update { it.copy(shake = "${question.key}:$value" to System.nanoTime()) }
                say("Pick up to ${question.max}. Tap one to swap it out")
            }
            is SurveyFlow.ChoiceResult.Updated -> {
                _state.update { it.copy(answers = result.answers) }
                saveDraft()
                if (!question.isMulti && result.selected) {
                    scheduleAdvance(question.key, 420)
                }
            }
        }
    }

    fun react(reaction: SurveyReaction) {
        val s = _state.value
        val features = s.config?.features ?: return
        val card = features.getOrNull(s.deckIndex) ?: return
        val firstTime = "card:${card.key}" !in s.awarded
        _state.update {
            it.copy(
                answers = it.answers.copy(reactions = it.answers.reactions + (card.key to reaction)),
                deckIndex = it.deckIndex + 1,
                awarded = it.awarded + "card:${card.key}",
                xp = it.xp + if (firstTime) 5 else 0
            )
        }
        if (_state.value.deckDone) say(deckSummary(), 0)
        saveDraft()
    }

    fun undoCard() {
        val s = _state.value
        val features = s.config?.features ?: return
        if (s.deckIndex == 0) return
        val previous = features[s.deckIndex - 1]
        _state.update {
            it.copy(deckIndex = it.deckIndex - 1, answers = it.answers.copy(reactions = it.answers.reactions - previous.key))
        }
        saveDraft()
    }

    fun skipDeck() {
        _state.update { it.copy(deckIndex = it.config?.features?.size ?: 0) }
        saveDraft()
    }

    fun replayDeck() {
        _state.update { it.copy(deckIndex = 0) }
        saveDraft()
    }

    fun deckSummary(): String {
        val loved = _state.value.answers.reactions.values.count { it == SurveyReaction.LOVE }
        return when (loved) {
            0 -> "Thanks, that helps us focus 🙏"
            1 -> "You loved 1 feature ✨"
            else -> "You loved $loved features ✨"
        }
    }

    fun setNps(score: Int) {
        _state.update { it.copy(answers = it.answers.copy(nps = score.coerceIn(0, 10))) }
        saveDraft()
        scheduleAdvance(SurveyStep.Nps.id, 750)
    }

    fun setWish(text: String) = updateAnswers { it.copy(wish = text.take(1000)) }

    fun addIdea(idea: String) = updateAnswers {
        val current = it.wish.trim()
        if (current.contains(idea)) it
        else it.copy(wish = (if (current.isEmpty()) idea else current + (if (current.last() in ".!?") " " else ". ") + idea).take(1000))
    }

    fun setName(text: String) = updateAnswers { it.copy(name = text.take(100)) }
    fun setEmail(text: String) = updateAnswers { it.copy(email = text.take(150)) }
    fun toggleNotify() = updateAnswers { it.copy(notify = !it.notify) }

    private fun updateAnswers(change: (SurveyAnswers) -> SurveyAnswers) {
        _state.update { it.copy(answers = change(it.answers)) }
        saveDraft()
    }

    private fun scheduleAdvance(stepId: String, delayMs: Long) {
        autoAdvance?.cancel()
        autoAdvance = viewModelScope.launch {
            delay(delayMs)
            if (_state.value.step.id == stepId) next()
        }
    }

    // ---------- submit ----------

    fun submit() {
        val s = _state.value
        val config = s.config ?: return
        if (s.submitting || !s.canContinue) return
        _state.update { it.copy(submitting = true, submitError = null) }
        val duration = if (s.startedAt > 0) (System.currentTimeMillis() - s.startedAt) / 1000 else null
        val payload = SurveyJson.payload(s.answers, config.questions, duration)

        viewModelScope.launch {
            SurveyApi.submit(payload)
                .onSuccess {
                    award(SurveyStep.Contact)
                    SurveyStore.markDone(context, config.key)
                    _state.update {
                        it.copy(submitting = false, alreadyAnswered = true, index = it.steps.lastIndex, forward = true)
                    }
                }
                .onFailure { e ->
                    _state.update {
                        it.copy(submitting = false, submitError = e.message ?: "We couldn't save your answers. Please try again.")
                    }
                }
        }
    }

    // ---------- rewards ----------

    private fun award(step: SurveyStep) {
        val s = _state.value
        if (step.id in s.awarded || step == SurveyStep.Features || step == SurveyStep.Welcome) return
        val points = when (step) {
            SurveyStep.Wish -> if (s.answers.wish.isBlank()) return else 20
            else -> 10
        }
        _state.update { it.copy(xp = it.xp + points, awarded = it.awarded + step.id) }
        val n = s.questionNumber
        val text = when {
            step == SurveyStep.Contact -> "Sent! 🎉"
            n == (s.totalQuestions + 1) / 2 -> "Halfway there! 🎉"
            n == s.totalQuestions - 2 -> "Almost done 🙌"
            step == SurveyStep.Wish -> "What a wish! 💫"
            else -> CHEERS[cheer++ % CHEERS.size]
        }
        say(text, points)
    }

    private fun say(text: String, xp: Int = 0) {
        toastJob?.cancel()
        _state.update { it.copy(toast = SurveyToast(System.nanoTime(), text, xp)) }
        toastJob = viewModelScope.launch {
            delay(1800)
            _state.update { it.copy(toast = null) }
        }
    }

    // ---------- draft ----------

    private fun saveDraft() {
        val s = _state.value
        val config = s.config ?: return
        if (s.step == SurveyStep.Welcome || s.step == SurveyStep.Done) return
        val draft = JSONObject().apply {
            put("round", config.key)
            put("step", s.step.id)
            put("deckIndex", s.deckIndex)
            put("xp", s.xp)
            put("startedAt", s.startedAt)
            put("awarded", org.json.JSONArray(s.awarded.toList()))
            put("answers", SurveyJson.payload(s.answers, config.questions, null).apply {
                // Keep the notify toggle as the person set it, not the email-gated value sent to the server.
                put("notify", s.answers.notify)
            })
        }
        SurveyStore.saveDraft(context, draft)
    }

    private companion object {
        val CHEERS = listOf("MashaAllah! ✨", "Great pick 🌟", "Love that 💚", "Noted! 📝", "Barakallahu feek 🤲", "Nice one 🙌")
    }
}

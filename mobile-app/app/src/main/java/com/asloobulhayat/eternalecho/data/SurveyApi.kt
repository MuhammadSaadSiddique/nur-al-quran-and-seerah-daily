package com.asloobulhayat.eternalecho.data

import android.content.Context
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONArray
import org.json.JSONObject

/*
 * "Help shape Eternal Echo" survey. Questions, feature cards and ideas all come from the server
 * (RequirementSurvey.php via mobile.php ?action=survey_config), so the app and the website
 * always ask the same thing and edits never need an app release.
 */

data class SurveyOption(val value: String, val emoji: String, val label: String)

data class SurveyQuestion(
    val key: String,
    val isMulti: Boolean,
    val max: Int,
    val eyebrow: String,
    val title: String,
    val hint: String,
    val options: List<SurveyOption>
)

/** A feature already in the app. [app] names the in-app screen to open; [url] is the web fallback. */
data class SurveyFeature(
    val key: String,
    val emoji: String,
    val title: String,
    val body: String,
    val tag: String,
    val url: String,
    val app: String?
)

enum class SurveyReaction(val key: String, val emoji: String, val label: String) {
    LOVE("love", "😍", "Love it"),
    TRY("try", "👀", "Want to try"),
    MEH("meh", "🙅", "Not for me");

    companion object {
        fun fromKey(key: String?): SurveyReaction? = entries.firstOrNull { it.key == key }
    }
}

data class SurveyAnswers(
    /** Question key -> picked option values (one value for single-choice questions). */
    val choices: Map<String, List<String>> = emptyMap(),
    /** Feature key -> reaction. Cards the person skipped are simply absent. */
    val reactions: Map<String, SurveyReaction> = emptyMap(),
    val nps: Int? = null,
    val wish: String = "",
    val name: String = "",
    val email: String = "",
    val whatsapp: String = "",
    val notify: Boolean = true
) {
    fun picked(questionKey: String): List<String> = choices[questionKey].orEmpty()
}

data class SurveyConfig(
    val key: String,
    val questions: List<SurveyQuestion>,
    val features: List<SurveyFeature>,
    val wishIdeas: List<String>,
    /** The signed-in person's saved answers for this round, if they answered before. */
    val previous: SurveyAnswers?
)

object SurveyJson {

    fun parseConfig(data: JSONObject): SurveyConfig {
        val questionsObj = data.optJSONObject("questions") ?: JSONObject()
        val order = data.optJSONArray("order")?.strings() ?: questionsObj.keys().asSequence().toList()
        val questions = order.mapNotNull { key ->
            val q = questionsObj.optJSONObject(key) ?: return@mapNotNull null
            val options = q.optJSONArray("options")?.objects()?.map {
                SurveyOption(it.optString("value"), it.optString("emoji"), it.optString("label"))
            }.orEmpty()
            val isMulti = q.optString("type") == "multi"
            SurveyQuestion(
                key = key,
                isMulti = isMulti,
                max = if (isMulti) q.optInt("max", options.size).coerceAtLeast(1) else 1,
                eyebrow = q.optString("eyebrow"),
                title = q.optString("title"),
                hint = q.optString("hint"),
                options = options
            )
        }.filter { it.options.isNotEmpty() }

        val features = data.optJSONArray("features")?.objects()?.map {
            SurveyFeature(
                key = it.optString("key"),
                emoji = it.optString("emoji"),
                title = it.optString("title"),
                body = it.optString("body"),
                tag = it.optString("tag"),
                url = it.optString("url"),
                app = it.optString("app").takeIf { app -> app.isNotBlank() && app != "null" }
            )
        }.orEmpty()

        val previous = data.optJSONObject("previous")?.let { answersFrom(it, questions.map { q -> q.key }) }

        return SurveyConfig(
            key = data.optString("key"),
            questions = questions,
            features = features,
            wishIdeas = data.optJSONArray("wishIdeas")?.strings().orEmpty(),
            previous = previous
        )
    }

    /** Reads answers in the server's shape (also used for the on-device draft). */
    fun answersFrom(obj: JSONObject, questionKeys: List<String>): SurveyAnswers {
        val choices = questionKeys.associateWith { key ->
            when (val v = obj.opt(key)) {
                is JSONArray -> v.strings()
                is String -> listOf(v).filter { it.isNotBlank() && it != "null" }
                else -> emptyList()
            }
        }.filterValues { it.isNotEmpty() }

        val reactions = buildMap {
            obj.optJSONObject("features")?.let { f ->
                f.keys().forEach { k -> SurveyReaction.fromKey(f.optString(k))?.let { put(k, it) } }
            }
        }

        return SurveyAnswers(
            choices = choices,
            reactions = reactions,
            nps = if (obj.has("nps") && !obj.isNull("nps")) obj.optInt("nps").coerceIn(0, 10) else null,
            wish = obj.text("wish"),
            name = obj.text("name"),
            email = obj.text("email"),
            whatsapp = obj.text("whatsapp"),
            notify = obj.optBoolean("notify", true)
        )
    }

    /** The submit payload in the shape RequirementSurvey::rules() validates. */
    fun payload(
        answers: SurveyAnswers,
        questions: List<SurveyQuestion>,
        durationSeconds: Long?,
        source: String = "android"
    ): JSONObject = JSONObject().apply {
        questions.forEach { q ->
            val picked = answers.picked(q.key)
            if (q.isMulti) put(q.key, JSONArray(picked)) else put(q.key, picked.firstOrNull() ?: JSONObject.NULL)
        }
        put("features", JSONObject().apply { answers.reactions.forEach { (k, r) -> put(k, r.key) } })
        put("nps", answers.nps ?: JSONObject.NULL)
        put("wish", answers.wish.trim().ifBlank { null } ?: JSONObject.NULL)
        put("name", answers.name.trim().ifBlank { null } ?: JSONObject.NULL)
        val email = answers.email.trim()
        put("email", email.ifBlank { null } ?: JSONObject.NULL)
        put("whatsapp", answers.whatsapp.trim().ifBlank { null } ?: JSONObject.NULL)
        put("notify", answers.notify && email.isNotEmpty())
        put("source", source)
        if (durationSeconds != null) put("duration_seconds", durationSeconds.coerceIn(0, 86_400))
    }

    private fun JSONArray.strings(): List<String> = (0 until length()).map { optString(it) }.filter { it.isNotBlank() }
    private fun JSONArray.objects(): List<JSONObject> = (0 until length()).mapNotNull { optJSONObject(it) }
    private fun JSONObject.text(key: String): String = if (!has(key) || isNull(key)) "" else optString(key).trim()
}

object SurveyApi {
    private val base: String get() =ApiClient.BASE_URL

    suspend fun config(): Result<SurveyConfig> = call {
        val json = JSONObject(ApiClient.makeGetRequest("$base?action=survey_config"))
        if (json.optString("status") != "success") throw Exception(json.optString("message", "Couldn't load the survey."))
        SurveyJson.parseConfig(json.getJSONObject("data"))
    }

    suspend fun submit(payload: JSONObject): Result<String> = call {
        val json = JSONObject(ApiClient.makePostRequest("$base?action=submit_survey", mapOf("payload" to payload.toString())))
        if (json.optString("status") != "success") {
            throw Exception(json.optString("message").ifBlank { "We couldn't save your answers. Please try again." })
        }
        json.optString("message", "JazakAllahu Khairan!")
    }

    private suspend fun <T> call(block: suspend () -> T): Result<T> = withContext(Dispatchers.IO) {
        try {
            Result.success(block())
        } catch (e: Exception) {
            android.util.Log.e("SurveyApi", "Request failed: ${e.message}", e)
            Result.failure(e)
        }
    }
}

/**
 * On-device survey state: the home card's dismiss/opened flags, the "already answered" flag and an
 * in-progress draft so people can leave mid-survey and pick up where they stopped.
 */
object SurveyStore {
    /** Must match RequirementSurvey::KEY on the server; change both to promote a new round. */
    const val CURRENT_ROUND = "requirements-2026"
    private const val PREFS = "survey_prompt"

    private fun prefs(context: Context) = context.applicationContext.getSharedPreferences(PREFS, Context.MODE_PRIVATE)

    fun isDismissed(context: Context, round: String = CURRENT_ROUND) = prefs(context).getBoolean("dismissed_$round", false)
    fun dismiss(context: Context, round: String = CURRENT_ROUND) = prefs(context).edit().putBoolean("dismissed_$round", true).apply()
    fun isOpened(context: Context, round: String = CURRENT_ROUND) = prefs(context).getBoolean("opened_$round", false)
    fun markOpened(context: Context, round: String = CURRENT_ROUND) = prefs(context).edit().putBoolean("opened_$round", true).apply()
    fun isDone(context: Context, round: String = CURRENT_ROUND) = prefs(context).getBoolean("done_$round", false)

    fun markDone(context: Context, round: String) =
        prefs(context).edit().putBoolean("done_$round", true).remove("draft").apply()

    fun saveDraft(context: Context, draft: JSONObject) = prefs(context).edit().putString("draft", draft.toString()).apply()
    fun clearDraft(context: Context) = prefs(context).edit().remove("draft").apply()

    fun loadDraft(context: Context): JSONObject? =
        prefs(context).getString("draft", null)?.let { runCatching { JSONObject(it) }.getOrNull() }
}

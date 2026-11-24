package com.example.eternalechomobile

import com.asloobulhayat.eternalecho.data.SurveyAnswers
import com.asloobulhayat.eternalecho.data.SurveyJson
import com.asloobulhayat.eternalecho.data.SurveyReaction
import com.asloobulhayat.eternalecho.ui.survey.SurveyFlow
import com.asloobulhayat.eternalecho.ui.survey.SurveyStep
import org.json.JSONObject
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test

class SurveyFlowTest {

    // Trimmed copy of what mobile.php ?action=survey_config returns. Questions are deliberately
    // listed out of order: the app must follow "order", not JSON key order.
    private val configJson = """
        {
          "key": "requirements-2026",
          "order": ["goals", "role", "daily_time", "wishlist"],
          "questions": {
            "wishlist": {"type": "multi", "max": 2, "eyebrow": "Build next", "title": "Which should we build?", "hint": "Pick 2",
              "options": [{"value": "audio", "emoji": "🎧", "label": "Audio"}, {"value": "hifz", "emoji": "🧩", "label": "Hifz"}, {"value": "offline", "emoji": "📴", "label": "Offline"}]},
            "goals": {"type": "multi", "max": 3, "eyebrow": "Your intention", "title": "What brings you?", "hint": "Pick up to 3",
              "options": [{"value": "seerah", "emoji": "🌙", "label": "Seerah"}, {"value": "habit", "emoji": "🔥", "label": "Habit"}]},
            "role": {"type": "single", "eyebrow": "About you", "title": "Who are you?", "hint": "",
              "options": [{"value": "student", "emoji": "🎓", "label": "Student"}, {"value": "parent", "emoji": "🏡", "label": "Parent"}]},
            "daily_time": {"type": "single", "eyebrow": "Rhythm", "title": "How long?", "hint": "",
              "options": [{"value": "2min", "emoji": "⚡", "label": "2 min"}]}
          },
          "features": [
            {"key": "seerah", "emoji": "⏳", "title": "Seerah", "body": "b", "tag": "t", "url": "https://x/seerah", "app": "seerah"},
            {"key": "streaks", "emoji": "🔥", "title": "Streaks", "body": "b", "tag": "t", "url": "https://x/dashboard", "app": "home"},
            {"key": "salah", "emoji": "🕌", "title": "Salah", "body": "b", "tag": "t", "url": "https://play", "app": "salah"},
            {"key": "research_community", "emoji": "🧑‍🔬", "title": "Research", "body": "b", "tag": "t", "url": "https://x/r", "app": null}
          ],
          "wishIdeas": ["Dark mode"],
          "previous": null
        }
    """.trimIndent()

    private val config = SurveyJson.parseConfig(JSONObject(configJson))

    @Test
    fun parses_config_in_server_order_with_app_targets() {
        assertEquals(listOf("goals", "role", "daily_time", "wishlist"), config.questions.map { it.key })
        assertEquals(2, config.questions.last().max)
        assertFalse(config.questions[1].isMulti)
        assertEquals("seerah", config.features[0].app)
        assertNull(config.features[3].app)
        assertNull(config.previous)
    }

    @Test
    fun deck_comes_after_three_questions() {
        val ids = SurveyFlow.steps(config).map { it.id }
        assertEquals(
            listOf("welcome", "goals", "role", "daily_time", "features", "wishlist", "nps", "wish", "contact", "done"),
            ids
        )
    }

    @Test
    fun multi_pick_stops_at_its_limit_and_toggles_off() {
        val wishlist = config.questions.first { it.key == "wishlist" }
        var answers = SurveyAnswers()
        answers = (SurveyFlow.choose(answers, wishlist, "audio") as SurveyFlow.ChoiceResult.Updated).answers
        answers = (SurveyFlow.choose(answers, wishlist, "hifz") as SurveyFlow.ChoiceResult.Updated).answers
        assertEquals(SurveyFlow.ChoiceResult.Full, SurveyFlow.choose(answers, wishlist, "offline"))

        val removed = SurveyFlow.choose(answers, wishlist, "audio") as SurveyFlow.ChoiceResult.Updated
        assertEquals(listOf("hifz"), removed.answers.picked("wishlist"))
        assertFalse(removed.selected)
    }

    @Test
    fun single_pick_replaces_and_unknown_values_are_ignored() {
        val role = config.questions.first { it.key == "role" }
        val first = (SurveyFlow.choose(SurveyAnswers(), role, "student") as SurveyFlow.ChoiceResult.Updated).answers
        val second = (SurveyFlow.choose(first, role, "parent") as SurveyFlow.ChoiceResult.Updated).answers
        assertEquals(listOf("parent"), second.picked("role"))

        val ignored = SurveyFlow.choose(second, role, "astronaut") as SurveyFlow.ChoiceResult.Updated
        assertEquals(listOf("parent"), ignored.answers.picked("role"))
    }

    @Test
    fun continue_rules_per_step() {
        val goals = SurveyStep.Choice(config.questions.first())
        assertFalse(SurveyFlow.canContinue(goals, SurveyAnswers(), deckDone = false))
        assertTrue(SurveyFlow.canContinue(goals, SurveyAnswers(choices = mapOf("goals" to listOf("seerah"))), deckDone = false))
        assertFalse(SurveyFlow.canContinue(SurveyStep.Features, SurveyAnswers(), deckDone = false))
        assertTrue(SurveyFlow.canContinue(SurveyStep.Wish, SurveyAnswers(), deckDone = false))
        assertFalse(SurveyFlow.canContinue(SurveyStep.Contact, SurveyAnswers(name = "Aisha", email = "learner@"), deckDone = true))
        assertFalse("email is required", SurveyFlow.canContinue(SurveyStep.Contact, SurveyAnswers(name = "Aisha", email = ""), deckDone = true))
        assertFalse("name is required", SurveyFlow.canContinue(SurveyStep.Contact, SurveyAnswers(email = "a@b.com"), deckDone = true))
        assertTrue(SurveyFlow.canContinue(SurveyStep.Contact, SurveyAnswers(name = "Aisha", email = "a@b.com"), deckDone = true))
        assertTrue(SurveyFlow.canContinue(SurveyStep.Contact, SurveyAnswers(name = "Aisha", email = "a@b.com", whatsapp = "+92 300 1234567"), deckDone = true))
        assertFalse(SurveyFlow.canContinue(SurveyStep.Contact, SurveyAnswers(name = "Aisha", email = "a@b.com", whatsapp = "call me"), deckDone = true))
    }

    @Test
    fun picks_put_try_first_then_goals_and_never_not_for_me() {
        val answers = SurveyAnswers(
            choices = mapOf("goals" to listOf("habit", "seerah")),
            reactions = mapOf(
                "salah" to SurveyReaction.TRY,
                "seerah" to SurveyReaction.MEH,
                "research_community" to SurveyReaction.LOVE
            )
        )
        assertEquals(listOf("salah", "streaks", "research_community"), SurveyFlow.picks(config, answers).map { it.key })
    }

    @Test
    fun payload_matches_server_shape_and_round_trips() {
        val answers = SurveyAnswers(
            choices = mapOf("goals" to listOf("seerah"), "role" to listOf("student"), "daily_time" to listOf("2min"), "wishlist" to listOf("audio")),
            reactions = mapOf("seerah" to SurveyReaction.LOVE),
            nps = 9,
            wish = "  Dark mode  ",
            name = "Aisha",
            email = "",
            notify = true
        )
        val payload = SurveyJson.payload(answers, config.questions, durationSeconds = 95)

        assertEquals("student", payload.getString("role"))
        assertEquals(1, payload.getJSONArray("goals").length())
        assertEquals("love", payload.getJSONObject("features").getString("seerah"))
        assertEquals("Dark mode", payload.getString("wish"))
        assertTrue(payload.isNull("email"))
        assertFalse("no email, so no update emails", payload.getBoolean("notify"))
        assertEquals("android", payload.getString("source"))
        assertEquals(95, payload.getInt("duration_seconds"))

        val back = SurveyJson.answersFrom(payload, config.questions.map { it.key })
        assertEquals(answers.choices, back.choices)
        assertEquals(answers.reactions, back.reactions)
        assertEquals(9, back.nps)
        assertTrue(payload.isNull("whatsapp"))

        val withWhatsapp = SurveyJson.payload(answers.copy(whatsapp = " +92 300 1234567 "), config.questions, durationSeconds = null)
        assertEquals("+92 300 1234567", withWhatsapp.getString("whatsapp"))
        assertEquals("+92 300 1234567", SurveyJson.answersFrom(withWhatsapp, config.questions.map { it.key }).whatsapp)
    }
}

package com.example.eternalechomobile

import com.asloobulhayat.eternalecho.data.QuranPatternJson
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Test

class QuranPatternJsonTest {
    @Test
    fun parseCardReadsThePatternFields() {
        val json = """
            {
              "status": "success",
              "data": {
                "chapter": 86,
                "verse": 1,
                "verse_key": "86:1",
                "surah_name": "At-Tariq",
                "arabic_text": "والسماء والطارق",
                "translation": "By the sky and the night comer",
                "gloss": "The night comer",
                "arabic_term": "والطارق",
                "ring_note": "Local symmetry",
                "linguistic_note": "والطارق is the knocking door",
                "topics": ["Cosmology"],
                "math": {
                  "ayat_in_surah": 17,
                  "words_in_verse": 3,
                  "letters_in_verse": 14,
                  "focus_position": 2,
                  "focus_count_in_surah": 1,
                  "summary": "17 ayat, 3 words, focus word is second"
                },
                "resources": {
                  "verse_url": "https://example.test/lens/86/1",
                  "science": [
                    {"title": "Night sky", "description": "A note", "field": "Cosmology"}
                  ]
                },
                "disclaimer": "Counts come from the stored text. Notes are study observations."
              }
            }
        """.trimIndent()

        val card = QuranPatternJson.parseCard(json)
        assertNotNull(card)
        assertEquals(86, card!!.chapter)
        assertEquals("86:1", card.verseKey)
        assertEquals("The night comer", card.gloss)
        assertEquals("والطارق", card.arabicTerm)
        assertEquals(listOf("Cosmology"), card.topics)
        assertEquals(17, card.math.ayatInSurah)
        assertEquals(3, card.math.wordsInVerse)
        assertEquals(2, card.math.focusPosition)
        assertEquals("17 ayat, 3 words, focus word is second", card.math.summary)
        assertEquals("https://example.test/lens/86/1", card.verseUrl)
        assertEquals("Night sky", card.science.first().title)
        assertEquals("Counts come from the stored text. Notes are study observations.", card.disclaimer)
    }

    @Test
    fun parseSearchDropsNothingTheServerAlreadyVerified() {
        val json = """
            {"status":"success","suggested":true,"direct":false,"message":"","hits":[
              {"chapter":36,"verse":40,"verse_key":"36:40","surah_name":"Ya-Sin","arabic":"كل","translation":"Each"}
            ]}
        """.trimIndent()

        val result = QuranPatternJson.parseSearch(json)
        assertEquals(true, result.suggested)
        assertEquals(1, result.hits.size)
        assertEquals("36:40", result.hits.first().verseKey)
    }

    @Test
    fun parseCardReturnsNullWhenTheVerseIsMissing() {
        val card = QuranPatternJson.parseCard("""{"status":"error","message":"That verse was not found."}""")
        assertNull(card)
    }
}

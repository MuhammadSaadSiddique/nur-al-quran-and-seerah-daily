package com.asloobulhayat.eternalecho.data

import org.json.JSONObject

data class QuranPatternHit(
    val chapter: Int,
    val verse: Int,
    val verseKey: String,
    val surahName: String,
    val arabic: String,
    val translation: String
)

data class QuranPatternSearchResult(
    val hits: List<QuranPatternHit> = emptyList(),
    val suggested: Boolean = false,
    val direct: Boolean = false,
    val message: String = ""
)

data class QuranPatternMath(
    val ayatInSurah: Int = 0,
    val wordsInVerse: Int = 0,
    val lettersInVerse: Int = 0,
    val focusPosition: Int? = null,
    val focusCountInSurah: Int? = null,
    val summary: String = ""
)

data class QuranPatternResource(
    val title: String,
    val description: String,
    val field: String
)

data class QuranPatternRingPart(
    val number: Int,
    val text: String,
    val pair: Int? = null,
    val center: Boolean = false
)

data class QuranPatternCard(
    val chapter: Int,
    val verse: Int,
    val verseKey: String,
    val surahName: String,
    val arabicText: String,
    val translation: String,
    val gloss: String,
    val arabicTerm: String,
    val ringNote: String,
    val linguisticNote: String,
    val topics: List<String>,
    val math: QuranPatternMath,
    val verseUrl: String,
    val science: List<QuranPatternResource>,
    val disclaimer: String,
    val ringParts: List<QuranPatternRingPart> = emptyList()
)

object QuranPatternJson {
    fun parseSearch(json: String): QuranPatternSearchResult {
        val root = JSONObject(json)
        if (root.optString("status") == "error") {
            return QuranPatternSearchResult(message = root.optString("message"))
        }
        val hitsJson = root.optJSONArray("hits")
        val hits = mutableListOf<QuranPatternHit>()
        if (hitsJson != null) {
            for (i in 0 until hitsJson.length()) {
                val item = hitsJson.optJSONObject(i) ?: continue
                hits.add(
                    QuranPatternHit(
                        chapter = item.optInt("chapter"),
                        verse = item.optInt("verse"),
                        verseKey = item.optString("verse_key"),
                        surahName = item.optString("surah_name"),
                        arabic = item.optString("arabic"),
                        translation = item.optString("translation")
                    )
                )
            }
        }
        return QuranPatternSearchResult(
            hits = hits,
            suggested = root.optBoolean("suggested"),
            direct = root.optBoolean("direct"),
            message = root.optString("message")
        )
    }

    fun parseCard(json: String): QuranPatternCard? {
        val root = JSONObject(json)
        if (root.optString("status") != "success") return null
        val data = root.optJSONObject("data") ?: return null
        val mathJson = data.optJSONObject("math") ?: JSONObject()
        val resources = data.optJSONObject("resources") ?: JSONObject()
        val scienceJson = resources.optJSONArray("science")
        val science = mutableListOf<QuranPatternResource>()
        if (scienceJson != null) {
            for (i in 0 until scienceJson.length()) {
                val item = scienceJson.optJSONObject(i) ?: continue
                science.add(
                    QuranPatternResource(
                        title = item.optString("title"),
                        description = item.optString("description"),
                        field = item.optString("field")
                    )
                )
            }
        }
        val ringJson = data.optJSONArray("ring_parts")
        val ringParts = mutableListOf<QuranPatternRingPart>()
        if (ringJson != null) {
            for (i in 0 until ringJson.length()) {
                val part = ringJson.optJSONObject(i) ?: continue
                val pair = if (part.has("pair") && !part.isNull("pair")) part.optInt("pair") else null
                ringParts.add(
                    QuranPatternRingPart(
                        number = part.optInt("n"),
                        text = part.optString("text"),
                        pair = pair,
                        center = part.optBoolean("center")
                    )
                )
            }
        }
        val topicsJson = data.optJSONArray("topics")
        val topics = mutableListOf<String>()
        if (topicsJson != null) {
            for (i in 0 until topicsJson.length()) {
                val topic = topicsJson.optString(i)
                if (topic.isNotBlank()) topics.add(topic)
            }
        }
        val focusPosition = if (mathJson.has("focus_position") && !mathJson.isNull("focus_position")) {
            mathJson.optInt("focus_position")
        } else {
            null
        }
        val focusCount = if (mathJson.has("focus_count_in_surah") && !mathJson.isNull("focus_count_in_surah")) {
            mathJson.optInt("focus_count_in_surah")
        } else {
            null
        }
        return QuranPatternCard(
            chapter = data.optInt("chapter"),
            verse = data.optInt("verse"),
            verseKey = data.optString("verse_key"),
            surahName = data.optString("surah_name"),
            arabicText = data.optString("arabic_text"),
            translation = data.optString("translation"),
            gloss = data.optString("gloss"),
            arabicTerm = data.optString("arabic_term"),
            ringNote = data.optString("ring_note"),
            linguisticNote = data.optString("linguistic_note"),
            topics = topics,
            math = QuranPatternMath(
                ayatInSurah = mathJson.optInt("ayat_in_surah"),
                wordsInVerse = mathJson.optInt("words_in_verse"),
                lettersInVerse = mathJson.optInt("letters_in_verse"),
                focusPosition = focusPosition,
                focusCountInSurah = focusCount,
                summary = mathJson.optString("summary")
            ),
            verseUrl = resources.optString("verse_url"),
            science = science,
            disclaimer = data.optString("disclaimer"),
            ringParts = ringParts
        )
    }
}

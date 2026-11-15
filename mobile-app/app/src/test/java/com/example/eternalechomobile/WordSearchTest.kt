package com.asloobulhayat.eternalecho

import com.asloobulhayat.eternalecho.data.ApiClient
import com.asloobulhayat.eternalecho.data.Dua
import com.asloobulhayat.eternalecho.data.QuranSearchHit
import com.asloobulhayat.eternalecho.data.Surah
import com.asloobulhayat.eternalecho.data.TextHighlight
import com.asloobulhayat.eternalecho.ui.search.findMatches
import com.asloobulhayat.eternalecho.util.ShareHelper
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class WordSearchTest {

    @Test
    fun parsesHighlightedTranslationSnippet() {
        val (text, highlights) = ApiClient.parseHighlightedText(
            "He said: My Lord! Have <em>mercy</em> on me &amp; my brother<sup foot_note=\"1\">1</sup>."
        )
        assertEquals("He said: My Lord! Have mercy on me & my brother .", text)
        assertEquals(listOf(TextHighlight(23, 28)), highlights)
        assertEquals("mercy", text.substring(23, 28))
    }

    @Test
    fun parsesMultipleAndArabicHighlights() {
        val (text, highlights) = ApiClient.parseHighlightedText(
            "فَفِى <em>رَحْمَةِ</em> ٱللَّهِ"
        )
        assertEquals("فَفِى رَحْمَةِ ٱللَّهِ", text)
        assertEquals(1, highlights.size)
        assertEquals("رَحْمَةِ", text.substring(highlights[0].start, highlights[0].end))

        val (multi, multiHighlights) = ApiClient.parseHighlightedText("<em>Mercy</em> and <em>mercy</em>")
        assertEquals("Mercy and mercy", multi)
        assertEquals(listOf(TextHighlight(0, 5), TextHighlight(10, 15)), multiHighlights)
    }

    @Test
    fun plainTextHasNoHighlights() {
        val (text, highlights) = ApiClient.parseHighlightedText("In the name of Allah")
        assertEquals("In the name of Allah", text)
        assertTrue(highlights.isEmpty())
    }

    @Test
    fun findMatchesIsCaseInsensitiveAndTreatsQueryLiterally() {
        assertEquals(
            listOf(TextHighlight(0, 5), TextHighlight(10, 15)),
            findMatches("Mercy and mercy", "MERCY")
        )
        assertEquals(listOf(TextHighlight(4, 7)), findMatches("1 + (2) = 3", "(2)"))
        assertTrue(findMatches("anything", " ").isEmpty())
    }

    @Test
    fun searchShareTextListsResultsWithLinks() {
        val text = ShareHelper.buildSearchResultsShareText(
            query = "mercy",
            verses = listOf(
                QuranSearchHit(
                    verseKey = "7:151",
                    surahNumber = 7,
                    verseNumber = 151,
                    arabicText = "قَالَ رَبِّ ٱغْفِرْ لِى",
                    translationText = "My Lord, forgive me and my brother and enter us into Your mercy."
                )
            ),
            totalVerseMatches = 2156,
            surahNames = mapOf(7 to "Al-A'raf"),
            surahs = listOf(Surah(55, 55, "الرحمن", "Ar-Rahman", "Ar-Rahman", "The Beneficent", "Medinan", 78)),
            duas = listOf(
                Dua(id = 3, title = "Dua for mercy", slug = "dua-for-mercy", category = "Forgiveness", arabicText = "", translationEn = "")
            )
        )

        assertTrue(text.contains("\"mercy\""))
        assertTrue(text.contains("Ayahs (2156 found)"))
        assertTrue(text.contains("• Al-A'raf 7:151"))
        assertTrue(text.contains("Your mercy."))
        assertTrue(text.contains("https://theeternalecho.asloobulhayat.com/lens/7/151"))
        assertTrue(text.contains("… and 2155 more"))
        assertTrue(text.contains("Surah 55: Ar-Rahman"))
        assertTrue(text.contains("https://theeternalecho.asloobulhayat.com/duas/dua-for-mercy"))
        assertTrue(text.contains(ShareHelper.APP_NAME))
        assertFalse(text.contains("Quranic insights"))
    }

    @Test
    fun searchShareTextFallsBackToArabicWhenNoTranslation() {
        val text = ShareHelper.buildSearchResultsShareText(
            query = "رحمة",
            verses = listOf(
                QuranSearchHit("3:107", 3, 107, arabicText = "فَفِى رَحْمَةِ ٱللَّهِ")
            )
        )
        assertTrue(text.contains("• Ayah 3:107"))
        assertTrue(text.contains("فَفِى رَحْمَةِ ٱللَّهِ"))
        assertFalse(text.contains("more"))
    }
}

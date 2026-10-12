package com.asloobulhayat.eternalecho

import android.content.Context
import android.content.Intent
import com.asloobulhayat.eternalecho.util.ShareHelper
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertTrue
import org.junit.Test

class ShareHelperTest {

    private class TestContext : android.content.ContextWrapper(null) {
        var startedIntent: Intent? = null

        override fun startActivity(intent: Intent?) {
            startedIntent = intent
        }
    }

    @Test
    fun testShareHelperConstants() {
        assertEquals("Eternal Echo - Daily Quran & Seerah", ShareHelper.APP_NAME)
        assertEquals("https://theeternalecho.asloobulhayat.com", ShareHelper.APP_URL)
    }

    @Test
    fun testShareAppTextBuilding() {
        val text = ShareHelper.buildAppShareText()
        assertTrue(text.contains(ShareHelper.APP_NAME))
        assertTrue(text.contains(ShareHelper.APP_URL))
        assertTrue(text.contains("Explore the Holy Quran"))

        val customText = ShareHelper.buildAppShareText("Check this out!")
        assertTrue(customText.startsWith("Check this out!"))
    }

    @Test
    fun testShareVerseFormatting() {
        val text = ShareHelper.buildVerseShareText(
            verseKey = "1:1",
            arabicText = "بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ",
            translation = "In the name of Allah, the Entirely Merciful, the Especially Merciful."
        )

        assertTrue(text.contains("Ayah 1:1"))
        assertTrue(text.contains("بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ"))
        assertTrue(text.contains("In the name of Allah"))
        assertTrue(text.contains(ShareHelper.APP_URL))
        assertTrue(text.contains("https://theeternalecho.asloobulhayat.com/lens/1/1"))
    }

    @Test
    fun testShareDuaFormatting() {
        val text = ShareHelper.buildDuaShareText(
            title = "Morning Protection",
            arabicText = "اللَّهُمَّ بِكَ أَصْبَحْنَا",
            translation = "O Allah, by You we enter the morning",
            reference = "Abu Dawood 5068"
        )

        assertTrue(text.contains("Morning Protection"))
        assertTrue(text.contains("اللَّهُمَّ بِكَ أَصْبَحْنَا"))
        assertTrue(text.contains("Abu Dawood 5068"))
        assertTrue(text.contains(ShareHelper.APP_URL))
        assertTrue(text.contains("https://theeternalecho.asloobulhayat.com/duas"))
    }

    @Test
    fun testShareSeerahFormatting() {
        val text = ShareHelper.buildSeerahShareText(
            title = "The Treaty of Hudaybiyyah",
            description = "A pivotal event in the history of Islam demonstrating wisdom and peace.",
            category = "Pivotal Moments"
        )

        assertTrue(text.contains("The Treaty of Hudaybiyyah"))
        assertTrue(text.contains("Pivotal Moments"))
        assertTrue(text.contains(ShareHelper.APP_URL))
        assertTrue(text.contains("https://theeternalecho.asloobulhayat.com/seerah"))
    }

    @Test
    fun testShareHistoryFormatting() {
        val text = ShareHelper.buildHistoryShareText(
            title = "House of Wisdom in Baghdad",
            description = "A major intellectual center during the Islamic Golden Age.",
            category = "Islamic Golden Age"
        )

        assertTrue(text.contains("House of Wisdom in Baghdad"))
        assertTrue(text.contains("Islamic Golden Age"))
        assertTrue(text.contains(ShareHelper.APP_URL))
        assertTrue(text.contains("https://theeternalecho.asloobulhayat.com/quran-history"))
    }

    @Test
    fun testShareQuizResultFormatting() {
        val text = ShareHelper.buildQuizResultShareText(
            quizTitle = "Surah Al-Baqarah Themes",
            score = 9,
            total = 10
        )

        assertTrue(text.contains("Surah Al-Baqarah Themes"))
        assertTrue(text.contains("9 / 10 (90%)"))
        assertTrue(text.contains(ShareHelper.APP_NAME))
        assertTrue(text.contains("https://theeternalecho.asloobulhayat.com/quiz-learning"))
    }

    @Test
    fun testShareConnectionFormatting() {
        val text = ShareHelper.buildConnectionShareText(
            title = "Cosmology and the Expanding Universe",
            description = "Quranic reference to expanding celestial heavens aligns with astrophysics.",
            category = "Science"
        )

        assertTrue(text.contains("Cosmology and the Expanding Universe"))
        assertTrue(text.contains("Science"))
        assertTrue(text.contains(ShareHelper.APP_URL))
    }

    @Test
    fun testStartActivityCalledSafely() {
        val context = TestContext()
        ShareHelper.shareApp(context)
        assertNotNull("startActivity must be invoked on context", context.startedIntent)

        val surahContext = TestContext()
        ShareHelper.shareSurah(surahContext, 2, "Al-Baqarah")
        assertNotNull(surahContext.startedIntent)

        val duasContext = TestContext()
        ShareHelper.shareDuas(duasContext)
        assertNotNull(duasContext.startedIntent)
    }
}

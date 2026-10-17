package com.asloobulhayat.eternalecho

import com.asloobulhayat.eternalecho.util.DeepLinkDestination
import com.asloobulhayat.eternalecho.util.DeepLinkHelper
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

class DeepLinkHelperTest {

    @Test
    fun testGenerators() {
        assertEquals("https://theeternalecho.asloobulhayat.com/", DeepLinkHelper.getHomeUrl())
        assertEquals("https://theeternalecho.asloobulhayat.com/lens/2/255", DeepLinkHelper.getVerseUrl(2, 255))
        assertEquals("https://theeternalecho.asloobulhayat.com/lens/112", DeepLinkHelper.getVerseUrl(112))
        assertEquals("https://theeternalecho.asloobulhayat.com/duas", DeepLinkHelper.getDuasUrl())
        assertEquals("https://theeternalecho.asloobulhayat.com/seerah", DeepLinkHelper.getSeerahUrl())
        assertEquals("https://theeternalecho.asloobulhayat.com/quran-history", DeepLinkHelper.getHistoryUrl())
        assertEquals("https://theeternalecho.asloobulhayat.com/quiz-learning", DeepLinkHelper.getQuizUrl())
        assertEquals("https://theeternalecho.asloobulhayat.com/leaderboard", DeepLinkHelper.getLeaderboardUrl())
        assertEquals("eternalecho://lens/2/255", DeepLinkHelper.getAppSchemeUrl("lens/2/255"))
    }

    @Test
    fun testWebVerseDeepLinkWithAyah() {
        val dest = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/lens/2/255")
        assertEquals(DeepLinkDestination.Surah(2, 255), dest)
    }

    @Test
    fun testWebSurahDeepLinkWithoutAyah() {
        val dest = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/lens/112")
        assertEquals(DeepLinkDestination.Surah(112, null), dest)

        val destSurahRoute = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/surah/1")
        assertEquals(DeepLinkDestination.Surah(1, null), destSurahRoute)
    }

    @Test
    fun testWebDuasDeepLinks() {
        val dest1 = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/duas")
        assertEquals(DeepLinkDestination.Duas, dest1)

        val dest2 = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/daily-dua")
        assertEquals(DeepLinkDestination.Duas, dest2)
    }

    @Test
    fun testWebDuaDetailDeepLinks() {
        val dest1 = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/duas/sayyid-al-istighfar")
        assertEquals(DeepLinkDestination.DuaDetail("sayyid-al-istighfar"), dest1)

        val dest2 = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/duas/1")
        assertEquals(DeepLinkDestination.DuaDetail("1"), dest2)

        val dest3 = DeepLinkHelper.parse("eternalecho://duas/sayyid-al-istighfar")
        assertEquals(DeepLinkDestination.DuaDetail("sayyid-al-istighfar"), dest3)

        assertEquals("https://theeternalecho.asloobulhayat.com/duas/sayyid-al-istighfar", DeepLinkHelper.getDuaDetailUrl("sayyid-al-istighfar"))
    }

    @Test
    fun testWebSeerahDeepLinks() {
        val dest1 = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/seerah")
        assertEquals(DeepLinkDestination.Seerah, dest1)

        val dest2 = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/seerah-daily")
        assertEquals(DeepLinkDestination.Seerah, dest2)
    }

    @Test
    fun testWebHistoryDeepLinks() {
        val dest1 = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/quran-history")
        assertEquals(DeepLinkDestination.History, dest1)

        val dest2 = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/history")
        assertEquals(DeepLinkDestination.History, dest2)
    }

    @Test
    fun testWebQuizDeepLinks() {
        val dest1 = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/quiz-learning")
        assertEquals(DeepLinkDestination.Quiz, dest1)

        val dest2 = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/themes")
        assertEquals(DeepLinkDestination.Quiz, dest2)

        val dest3 = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/quiz")
        assertEquals(DeepLinkDestination.Quiz, dest3)
    }

    @Test
    fun testWebLeaderboardDeepLink() {
        val dest = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/leaderboard")
        assertEquals(DeepLinkDestination.Leaderboard, dest)
    }

    @Test
    fun testWebHomeDeepLinks() {
        val dest1 = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com")
        assertEquals(DeepLinkDestination.Home, dest1)

        val dest2 = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/")
        assertEquals(DeepLinkDestination.Home, dest2)

        val dest3 = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/home")
        assertEquals(DeepLinkDestination.Home, dest3)
    }

    @Test
    fun testCustomSchemeLinks() {
        assertEquals(
            DeepLinkDestination.Surah(2, 255),
            DeepLinkHelper.parse("eternalecho://lens/2/255")
        )
        assertEquals(
            DeepLinkDestination.Duas,
            DeepLinkHelper.parse("eternalecho://duas")
        )
        assertEquals(
            DeepLinkDestination.Seerah,
            DeepLinkHelper.parse("eternalecho://seerah")
        )
        assertEquals(
            DeepLinkDestination.History,
            DeepLinkHelper.parse("eternalecho://history")
        )
        assertEquals(
            DeepLinkDestination.Quiz,
            DeepLinkHelper.parse("eternalecho://quiz")
        )
        assertEquals(
            DeepLinkDestination.Leaderboard,
            DeepLinkHelper.parse("eternalecho://leaderboard")
        )
        assertEquals(
            DeepLinkDestination.Home,
            DeepLinkHelper.parse("eternalecho://home")
        )
        assertEquals(
            DeepLinkDestination.Home,
            DeepLinkHelper.parse("eternalecho://app")
        )
    }

    @Test
    fun testQueryParametersAndFragmentsStripping() {
        val dest = DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/lens/2/255?source=whatsapp&utm=share#ayah255")
        assertEquals(DeepLinkDestination.Surah(2, 255), dest)
    }

    @Test
    fun testRelativeAndHostOnlyPaths() {
        assertEquals(
            DeepLinkDestination.DuaDetail("ayat-al-kursi"),
            DeepLinkHelper.parse("/duas/ayat-al-kursi")
        )
        assertEquals(
            DeepLinkDestination.Surah(2, 255),
            DeepLinkHelper.parse("lens/2/255")
        )
        assertEquals(
            DeepLinkDestination.Seerah,
            DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/seerah")
        )
        assertEquals(
            "https://theeternalecho.asloobulhayat.com/duas/ayat-al-kursi",
            DeepLinkHelper.toAbsoluteUrl("/duas/ayat-al-kursi")
        )
    }

    @Test
    fun testInvalidOrUnsupportedUrls() {
        assertNull(DeepLinkHelper.parse(null as String?))
        assertNull(DeepLinkHelper.parse(null as android.net.Uri?))
        assertNull(DeepLinkHelper.parseUri(null))
        assertNull(DeepLinkHelper.parse(""))
        assertNull(DeepLinkHelper.parse("   "))
        assertNull(DeepLinkHelper.parse("https://google.com/lens/2/255"))
        assertNull(DeepLinkHelper.parse("https://theeternalecho.asloobulhayat.com/invalid_page_here"))
    }
}

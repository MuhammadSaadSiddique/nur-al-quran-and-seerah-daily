package com.asloobulhayat.eternalecho

import com.asloobulhayat.eternalecho.config.AppConfig
import com.asloobulhayat.eternalecho.data.DefaultDataRepository
import junit.framework.TestCase.*
import kotlinx.coroutines.test.runTest
import org.junit.Test

class DuasAndOnboardingTest {

    @Test
    fun testDuasFeatureFlagReflectedInNavigation() {
        val destinations = com.asloobulhayat.eternalecho.ui.adaptive.defaultNavigationDestinations
        val hasDuasDestination = destinations.any { it.index == 4 }
        assertEquals(
            "Duas destination in defaultNavigationDestinations must match IS_DUAS_FEATURE_ENABLED flag",
            AppConfig.IS_DUAS_FEATURE_ENABLED,
            hasDuasDestination
        )
    }

    @Test
    fun testDataRepositoryProvidesFallbackDuas() = runTest {
        val repository = DefaultDataRepository()
        val duas = repository.getDuas()
        assertTrue("Fallback repository must supply authentic Duas", duas.isNotEmpty())

        val firstDua = duas.first()
        assertTrue("First Dua has valid Arabic text", firstDua.arabicText.isNotBlank())
        assertTrue("First Dua has valid English translation", firstDua.translationEn.isNotBlank())
        assertTrue("First Dua has word-by-word data", !firstDua.wordByWord.isNullOrEmpty())
    }

    @Test
    fun testDuasFiltering() = runTest {
        val repository = DefaultDataRepository()
        val forgivenessDuas = repository.getDuas(category = "Forgiveness & Tawbah")
        assertTrue(forgivenessDuas.any { it.slug == "sayyid-al-istighfar" })

        val quranDuas = repository.getDuas(sourceType = "quran")
        assertTrue(quranDuas.all { it.sourceType == "quran" || it.sourceType == "both" })
    }

    @Test
    fun testLensFeatureFlagGuard() {
        val destinations = com.asloobulhayat.eternalecho.ui.adaptive.defaultNavigationDestinations
        val hasLensDestination = destinations.any { it.index == 5 }
        assertEquals(
            "Lens destination in defaultNavigationDestinations must match IS_LENS_FEATURE_ENABLED flag",
            AppConfig.IS_LENS_FEATURE_ENABLED,
            hasLensDestination
        )
    }
}

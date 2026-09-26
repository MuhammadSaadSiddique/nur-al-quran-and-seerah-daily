package com.example.eternalechomobile

import com.example.eternalechomobile.config.AppConfig
import com.example.eternalechomobile.data.DefaultDataRepository
import junit.framework.TestCase.*
import kotlinx.coroutines.test.runTest
import org.junit.Test

class DuasAndOnboardingTest {

    @Test
    fun testDuasFeatureIsDisabledByDefaultUntilApiDeployed() {
        // As requested by user: "create feature in mobile app but not enable it until I deploy API"
        assertFalse(
            "Duas feature must remain disabled by default until API is deployed",
            AppConfig.IS_DUAS_FEATURE_ENABLED
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
}

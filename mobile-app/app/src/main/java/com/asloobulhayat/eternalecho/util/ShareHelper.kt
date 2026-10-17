package com.asloobulhayat.eternalecho.util

import android.content.Context
import android.content.Intent

/**
 * Utility helper to handle sharing content (verses, duas, seerah stories,
 * history events, quiz scores, and app invitations) via the Android Sharesheet.
 */
object ShareHelper {

    const val APP_NAME = "Eternal Echo - Daily Quran & Seerah"
    const val APP_URL = "https://theeternalecho.asloobulhayat.com"

    /**
     * Builds the Intent used to share plain text via standard Android chooser.
     */
    fun createShareIntent(text: String, subject: String = APP_NAME): Intent {
        val sendIntent = Intent().apply {
            action = Intent.ACTION_SEND
            putExtra(Intent.EXTRA_SUBJECT, subject)
            putExtra(Intent.EXTRA_TEXT, text)
            type = "text/plain"
        }
        val chooser = try {
            Intent.createChooser(sendIntent, "Share via")
        } catch (_: Throwable) {
            null
        }
        val finalIntent = chooser ?: sendIntent
        finalIntent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        return finalIntent
    }

    /**
     * Generic share helper that opens the standard Android share sheet.
     */
    fun shareText(context: Context, text: String, subject: String = APP_NAME) {
        val intent = createShareIntent(text, subject)
        context.startActivity(intent)
    }

    fun buildAppShareText(customMessage: String? = null): String = buildString {
        if (!customMessage.isNullOrBlank()) {
            appendLine(customMessage)
            appendLine()
        }
        appendLine("🌙 $APP_NAME")
        appendLine("Explore the Holy Quran with translations, Seerah stories, Islamic history, and interactive quizzes.")
        appendLine()
        appendLine("Download or visit: $APP_URL")
    }

    /**
     * Share the app link and description with friends & family.
     */
    fun shareApp(context: Context, customMessage: String? = null) {
        shareText(context, buildAppShareText(customMessage), subject = "Join me on $APP_NAME")
    }

    fun parseVerseKey(verseKey: String): Pair<Int?, Int?> {
        val parts = verseKey.split(":")
        val surah = parts.getOrNull(0)?.trim()?.toIntOrNull()
        val verse = parts.getOrNull(1)?.trim()?.toIntOrNull()
        return Pair(surah, verse)
    }

    fun buildVerseShareText(
        verseKey: String,
        arabicText: String,
        translation: String
    ): String = buildString {
        val (surah, verse) = parseVerseKey(verseKey)
        val link = if (surah != null) DeepLinkHelper.getVerseUrl(surah, verse) else APP_URL
        appendLine("📖 Holy Quran — Ayah $verseKey")
        appendLine()
        appendLine(arabicText)
        appendLine()
        if (translation.isNotBlank()) {
            appendLine(translation)
            appendLine()
        }
        appendLine("— Shared via $APP_NAME ($link)")
    }

    /**
     * Share a Quranic verse with its Arabic text, transliteration, and reference.
     */
    fun shareVerse(
        context: Context,
        verseKey: String,
        arabicText: String,
        translation: String
    ) {
        shareText(context, buildVerseShareText(verseKey, arabicText, translation), subject = "Holy Quran: Ayah $verseKey")
    }

    /**
     * Share a whole Surah with deep link.
     */
    fun shareSurah(
        context: Context,
        surahNumber: Int,
        surahName: String? = null
    ) {
        val link = DeepLinkHelper.getVerseUrl(surahNumber)
        val title = if (!surahName.isNullOrBlank()) "Surah #$surahNumber ($surahName)" else "Surah #$surahNumber"
        val text = buildString {
            appendLine("📖 Holy Quran — $title")
            appendLine("Explore verses, translations, and Islamic connections:")
            appendLine(link)
        }
        shareText(context, text, subject = "Holy Quran: $title")
    }

    fun buildDuaShareText(
        title: String,
        arabicText: String,
        translation: String,
        reference: String? = null,
        idOrSlug: String? = null
    ): String = buildString {
        val link = if (!idOrSlug.isNullOrBlank()) {
            DeepLinkHelper.getDuaDetailUrl(idOrSlug)
        } else {
            DeepLinkHelper.getDuasUrl()
        }
        appendLine("🤲 $title")
        appendLine()
        appendLine(arabicText)
        appendLine()
        if (translation.isNotBlank()) {
            appendLine(translation)
            appendLine()
        }
        if (!reference.isNullOrBlank()) {
            appendLine("Reference: $reference")
            appendLine()
        }
        appendLine("— Shared via $APP_NAME ($link)")
    }

    /**
     * Share a Sacred Dua with its Arabic, transliteration/translation, and source reference.
     */
    fun shareDua(
        context: Context,
        title: String,
        arabicText: String,
        translation: String,
        reference: String? = null,
        idOrSlug: String? = null
    ) {
        shareText(
            context,
            buildDuaShareText(title, arabicText, translation, reference, idOrSlug),
            subject = "Dua: $title"
        )
    }

    /**
     * Share Sacred Duas main page with deep link.
     */
    fun shareDuas(context: Context) {
        val link = DeepLinkHelper.getDuasUrl()
        val text = buildString {
            appendLine("🤲 Sacred Duas & Supplications")
            appendLine("Explore authentic supplications from the Quran and Sunnah:")
            appendLine(link)
        }
        shareText(context, text, subject = "Sacred Duas on $APP_NAME")
    }

    fun buildSeerahShareText(
        title: String,
        description: String,
        category: String? = null
    ): String = buildString {
        val link = DeepLinkHelper.getSeerahUrl()
        appendLine("🕌 Seerah Daily: $title")
        if (!category.isNullOrBlank()) {
            appendLine("Category: $category")
        }
        appendLine()
        appendLine(description)
        appendLine()
        appendLine("— Shared via $APP_NAME ($link)")
    }

    /**
     * Share a Seerah daily event and story.
     */
    fun shareSeerahEvent(
        context: Context,
        title: String,
        description: String,
        category: String? = null
    ) {
        shareText(context, buildSeerahShareText(title, description, category), subject = "Seerah Daily: $title")
    }

    fun buildHistoryShareText(
        title: String,
        description: String,
        category: String? = null
    ): String = buildString {
        val link = DeepLinkHelper.getHistoryUrl()
        appendLine("📜 Islamic History: $title")
        if (!category.isNullOrBlank()) {
            appendLine("Category: $category")
        }
        appendLine()
        appendLine(description)
        appendLine()
        appendLine("— Shared via $APP_NAME ($link)")
    }

    /**
     * Share an Islamic History event.
     */
    fun shareHistoryEvent(
        context: Context,
        title: String,
        description: String,
        category: String? = null
    ) {
        shareText(context, buildHistoryShareText(title, description, category), subject = "Islamic History: $title")
    }

    fun buildQuizResultShareText(
        quizTitle: String,
        score: Int,
        total: Int
    ): String {
        val percentage = if (total > 0) (score * 100) / total else 0
        val link = DeepLinkHelper.getQuizUrl()
        return buildString {
            appendLine("🏆 Eternal Echo Quiz Result")
            appendLine("Quiz: $quizTitle")
            appendLine("Score: $score / $total ($percentage%)")
            appendLine()
            appendLine("Test your Islamic knowledge on $APP_NAME!")
            appendLine(link)
        }
    }

    /**
     * Share a quiz score and challenge others.
     */
    fun shareQuizResult(
        context: Context,
        quizTitle: String,
        score: Int,
        total: Int
    ) {
        shareText(context, buildQuizResultShareText(quizTitle, score, total), subject = "My Quiz Score on $APP_NAME")
    }

    fun buildConnectionShareText(
        title: String,
        description: String,
        category: String? = null
    ): String = buildString {
        val link = DeepLinkHelper.getHomeUrl()
        appendLine("🔗 Quranic Linkage: $title")
        if (!category.isNullOrBlank()) {
            appendLine("Category: $category")
        }
        appendLine()
        appendLine(description)
        appendLine()
        appendLine("— Shared via $APP_NAME ($link)")
    }

    /**
     * Share a Quranic linkage or connection.
     */
    fun shareConnection(
        context: Context,
        title: String,
        description: String,
        category: String? = null
    ) {
        shareText(context, buildConnectionShareText(title, description, category), subject = "Quranic Linkage: $title")
    }
}

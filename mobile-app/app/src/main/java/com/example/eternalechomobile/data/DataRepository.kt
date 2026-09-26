package com.example.eternalechomobile.data

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow

interface DataRepository {
    suspend fun getSurahs(): List<Surah>
    suspend fun getVerses(surahNumber: Int): List<Verse>
    suspend fun getConnections(surahNumber: Int, verseNumber: Int): ConnectionsData
    suspend fun getLeaderboard(): List<LeaderboardUser>
    suspend fun getInsights(
        seerahPage: Int = 1,
        historyPage: Int = 1,
        seerahCategory: String = "",
        historyCategory: String = ""
    ): InsightsData
    suspend fun getThemes(): List<Theme>
    suspend fun getThemeQuiz(themeId: Int, difficulty: String): List<QuizQuestion>
    suspend fun login(email: String, password: String): UserSession
    suspend fun register(name: String, email: String, password: String): UserSession
    suspend fun requestOtp(email: String): Boolean
    suspend fun verifyOtp(email: String, otp: String): UserSessionOtpResponse
    suspend fun setPassword(userId: Int, password: String): Boolean
    suspend fun changePassword(userId: Int, password: String): Boolean
    suspend fun submitQuiz(
        userId: Int,
        type: String,
        title: String,
        score: Int,
        totalQuestions: Int,
        difficulty: String,
        questionsJson: String,
        userAnswersJson: String
    ): Boolean
    suspend fun getDuas(category: String = "", search: String = "", sourceType: String = ""): List<Dua>
}

class DefaultDataRepository : DataRepository {
    override suspend fun getSurahs(): List<Surah> {
        return ApiClient.fetchSurahs()
    }

    override suspend fun getVerses(surahNumber: Int): List<Verse> {
        return ApiClient.fetchVerses(surahNumber)
    }

    override suspend fun getConnections(surahNumber: Int, verseNumber: Int): ConnectionsData {
        return ApiClient.fetchConnections(surahNumber, verseNumber)
    }

    override suspend fun getLeaderboard(): List<LeaderboardUser> {
        return ApiClient.fetchLeaderboard()
    }

    override suspend fun getInsights(
        seerahPage: Int,
        historyPage: Int,
        seerahCategory: String,
        historyCategory: String
    ): InsightsData {
        return ApiClient.fetchInsights(seerahPage, historyPage, seerahCategory, historyCategory)
    }

    override suspend fun getThemes(): List<Theme> {
        return ApiClient.fetchThemes()
    }

    override suspend fun getThemeQuiz(themeId: Int, difficulty: String): List<QuizQuestion> {
        return ApiClient.fetchThemeQuiz(themeId, difficulty)
    }

    override suspend fun login(email: String, password: String): UserSession {
        return ApiClient.login(email, password)
    }

    override suspend fun register(name: String, email: String, password: String): UserSession {
        return ApiClient.register(name, email, password)
    }

    override suspend fun requestOtp(email: String): Boolean {
        return ApiClient.requestOtp(email)
    }

    override suspend fun verifyOtp(email: String, otp: String): UserSessionOtpResponse {
        return ApiClient.verifyOtp(email, otp)
    }

    override suspend fun setPassword(userId: Int, password: String): Boolean {
        return ApiClient.setPassword(userId, password)
    }

    override suspend fun changePassword(userId: Int, password: String): Boolean {
        return ApiClient.changePassword(userId, password)
    }

    override suspend fun submitQuiz(
        userId: Int,
        type: String,
        title: String,
        score: Int,
        totalQuestions: Int,
        difficulty: String,
        questionsJson: String,
        userAnswersJson: String
    ): Boolean {
        return ApiClient.submitQuiz(userId, type, title, score, totalQuestions, difficulty, questionsJson, userAnswersJson)
    }

    override suspend fun getDuas(category: String, search: String, sourceType: String): List<Dua> {
        return try {
            ApiClient.fetchDuas(category, search, sourceType)
        } catch (_: Exception) {
            getFallbackDuas(category, search, sourceType)
        }
    }

    private fun getFallbackDuas(category: String, search: String, sourceType: String): List<Dua> {
        val all = listOf(
            Dua(
                id = 1,
                title = "Rabbana Atina fid-Dunya Hasanah",
                slug = "rabbana-atina-fid-dunya-hasanah",
                category = "Comprehensive & Success",
                description = "The most frequently recited prayer of Prophet Muhammad ﷺ asking for total good in this world and the hereafter.",
                arabicText = "رَبَّنَا آتِنَا فِي الدُّنْيَا حَسَنَةً وَفِي الْآخِرَةِ حَسَنَةً وَقِنَا عَذَابَ النَّارِ",
                transliteration = "Rabbanā ātinā fī l-dunyā ḥasanatan wa-fī l-ākhirati ḥasanatan wa-qinā ‘adhāba l-nār",
                translationEn = "Our Lord, give us in this world that which is good and in the Hereafter that which is good, and protect us from the punishment of the Fire.",
                translationUr = "اے ہمارے رب! ہمیں دنیا میں بھی بھلائی عطا فرما اور آخرت میں بھی بھلائی عطا فرما، اور ہمیں آگ کے عذاب سے بچا۔",
                wordByWord = listOf(
                    DuaWord("رَبَّنَا", "Rabbanā", "Our Lord", "اے ہمارے رب"),
                    DuaWord("آتِنَا", "ātinā", "Grant us", "ہمیں عطا فرما"),
                    DuaWord("فِي", "fī", "in", "میں"),
                    DuaWord("الدُّنْيَا", "l-dunyā", "the worldly life", "دنیا"),
                    DuaWord("حَسَنَةً", "ḥasanatan", "good / excellence", "بھلائی"),
                    DuaWord("وَفِي", "wa-fī", "and in", "اور میں"),
                    DuaWord("الْآخِرَةِ", "l-ākhirati", "the Hereafter", "آخرت"),
                    DuaWord("حَسَنَةً", "ḥasanatan", "good / paradise", "بھلائی"),
                    DuaWord("وَقِنَا", "wa-qinā", "and protect us", "اور ہمیں بچا"),
                    DuaWord("عَذَابَ", "‘adhāba", "from torment of", "عذاب سے"),
                    DuaWord("النَّارِ", "l-nār", "the Fire", "آگ کے")
                ),
                meaningExplanation = "Hasanah in the worldly context means lawful sustenance, a righteous family, and tranquility. In the hereafter, it means paradise and divine forgiveness.",
                benefitsAndVirtues = "Reported by Anas (RA) as the most frequent supplication of the Prophet ﷺ (Sahih al-Bukhari 4522).",
                whenToRecite = "During Tawaf, after prayers, and in daily remembrance",
                repeatCount = 3,
                sourceType = "both",
                quranReference = "Surah Al-Baqarah (2:201)",
                surahNumber = 2,
                verseNumber = 201,
                hadithReference = "Sahih al-Bukhari 4522",
                hadithBook = "Sahih al-Bukhari",
                hadithNumber = "4522",
                hadithGrading = "Sahih"
            ),
            Dua(
                id = 2,
                title = "Sayyid al-Istighfar (Master Supplication for Forgiveness)",
                slug = "sayyid-al-istighfar",
                category = "Forgiveness & Tawbah",
                description = "The supreme pinnacle of asking for forgiveness taught by the Prophet ﷺ.",
                arabicText = "اللَّهُمَّ أَنْتَ رَبِّي لَا إِلَهَ إِلَّا أَنْتَ، خَلَقْتَنِي وَأَنَا عَبْدُكَ، وَأَنَا عَلَى عَهْدِكَ وَوَعْدِكَ مَا اسْتَطَعْتُ، أَعُوذُ بِكَ مِنْ شَرِّ مَا صَنَعْتُ، أَبُوءُ لَكَ بِنِعْمَتِكَ عَلَيَّ، وَأَبُوءُ لَكَ بِذَنْبِي فَاغْفِرْ لِي فَإِنَّهُ لَا يَغْفِرُ الذُّنُوبَ إِلَّا أَنْتَ",
                transliteration = "Allāhumma anta Rabbī lā ilāha illā anta, khalaqtanī wa-anā ‘abduka, wa-anā ‘alā ‘ahdika wa-wa‘dika ma-staṭa‘tu, a‘ūdhu bika min sharri mā ṣana‘tu, abū’u laka bi-ni‘matika ‘alayya, wa-abū’u laka bi-dhanbī fa-ghfir lī fa-innahu lā yaghfiru l-dhunūba illā anta",
                translationEn = "O Allah, You are my Lord; there is no deity except You. You created me and I am Your servant, and I uphold Your covenant as much as I am able. Forgive me, for none forgives sins except You.",
                translationUr = "اے اللہ! تو ہی میرا رب ہے، تیرے سوا کوئی معبود نہیں۔ تو نے مجھے پیدا کیا اور میں تیرا بندہ ہوں۔ مجھے بخش دے، تیرے سوا کوئی گناہ معاف نہیں کر سکتا۔",
                meaningExplanation = "Combines monotheism, servantship, confession of blessings, and admission of sin.",
                benefitsAndVirtues = "Whoever recites it in the day/night with faith and passes away will be from the people of Paradise (Bukhari 6306).",
                whenToRecite = "Morning and Evening",
                repeatCount = 1,
                sourceType = "hadith",
                hadithReference = "Sahih al-Bukhari 6306",
                hadithBook = "Sahih al-Bukhari",
                hadithNumber = "6306",
                hadithGrading = "Sahih"
            ),
            Dua(
                id = 3,
                title = "Ayat e Karima — Dua of Prophet Yunus (AS)",
                slug = "ayat-e-karima-prophet-yunus",
                category = "Distress & Hardship",
                description = "The cry of Prophet Yunus AS from the depths of distress, guaranteed to be answered by Allah.",
                arabicText = "لَّا إِلَٰهَ إِلَّا أَنتَ سُبْحَانَكَ إِنِّي كُنتُ مِنَ الظَّالِمِينَ",
                transliteration = "Lā ilāha illā anta subḥānaka innī kuntu mina l-ẓālimīn",
                translationEn = "There is no deity except You; exalted are You. Indeed, I have been of the wrongdoers.",
                translationUr = "تیرے سوا کوئی معبود نہیں، تو پاک ہے، بے شک میں ہی قصورواروں میں سے تھا۔",
                meaningExplanation = "Purifies Allah from all injustice and brings immediate relief from distress.",
                benefitsAndVirtues = "No Muslim supplicates with this prayer for anything except that Allah answers him (Tirmidhi 3505).",
                whenToRecite = "During anxiety, hardship, and grief",
                repeatCount = 40,
                sourceType = "both",
                quranReference = "Surah Al-Anbiya (21:87)",
                surahNumber = 21,
                verseNumber = 87,
                hadithReference = "Jami‘ at-Tirmidhi 3505",
                hadithBook = "Jami‘ at-Tirmidhi",
                hadithNumber = "3505",
                hadithGrading = "Sahih"
            )
        )

        return all.filter { d ->
            val matchCat = category.isBlank() || category == "all" || d.category.equals(category, ignoreCase = true)
            val matchSource = sourceType.isBlank() || sourceType == "all" || d.sourceType.equals(sourceType, ignoreCase = true) || d.sourceType == "both"
            val matchSearch = search.isBlank() || d.title.contains(search, ignoreCase = true) ||
                    d.arabicText.contains(search) ||
                    d.translationEn.contains(search, ignoreCase = true) ||
                    (d.quranReference?.contains(search, ignoreCase = true) == true) ||
                    (d.hadithReference?.contains(search, ignoreCase = true) == true)
            matchCat && matchSource && matchSearch
        }
    }
}


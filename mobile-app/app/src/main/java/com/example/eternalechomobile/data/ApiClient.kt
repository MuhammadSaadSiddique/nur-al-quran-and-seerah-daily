package com.example.eternalechomobile.data

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONArray
import org.json.JSONObject
import java.io.BufferedReader
import java.io.InputStreamReader
import java.net.HttpURLConnection
import java.net.URL
import javax.net.ssl.HttpsURLConnection
import com.example.eternalechomobile.security.AppSecurity

object ApiClient {
    private val OBFUSCATED_BASE_URL = byteArrayOf(
        50.toByte(), 46.toByte(), 46.toByte(), 42.toByte(), 41.toByte(), 96.toByte(), 117.toByte(), 117.toByte(),
        46.toByte(), 50.toByte(), 63.toByte(), 63.toByte(), 46.toByte(), 63.toByte(), 40.toByte(), 52.toByte(),
        59.toByte(), 54.toByte(), 63.toByte(), 57.toByte(), 50.toByte(), 53.toByte(), 116.toByte(), 59.toByte(),
        41.toByte(), 54.toByte(), 53.toByte(), 53.toByte(), 56.toByte(), 47.toByte(), 54.toByte(), 50.toByte(),
        59.toByte(), 35.toByte(), 59.toByte(), 46.toByte(), 116.toByte(), 57.toByte(), 53.toByte(), 55.toByte(),
        117.toByte(), 59.toByte(), 42.toByte(), 51.toByte(), 117.toByte(), 44.toByte(), 107.toByte(), 117.toByte(),
        55.toByte(), 53.toByte(), 56.toByte(), 51.toByte(), 54.toByte(), 63.toByte(), 116.toByte(), 42.toByte(),
        50.toByte(), 42.toByte()
    )
    private const val OBFUSCATION_KEY: Byte = 0x5A

    val BASE_URL: String
        get() = AppSecurity.deobfuscate(OBFUSCATED_BASE_URL, OBFUSCATION_KEY)

    private suspend fun makeGetRequest(urlStr: String): String = withContext(Dispatchers.IO) {
        if (!urlStr.startsWith("https://", ignoreCase = true)) {
            throw SecurityException("Insecure HTTP connections are strictly prohibited.")
        }
        val url = URL(urlStr)
        try {
            val conn = url.openConnection() as HttpURLConnection
            conn.requestMethod = "GET"
            conn.connectTimeout = 10000
            conn.readTimeout = 10000

            val responseCode = conn.responseCode
            if (responseCode == HttpURLConnection.HTTP_OK) {
                val reader = BufferedReader(InputStreamReader(conn.inputStream))
                val response = StringBuilder()
                var line: String?
                while (reader.readLine().also { line = it } != null) {
                    response.append(line)
                }
                reader.close()
                response.toString()
            } else {
                val errorMsg = "HTTP Error Code: $responseCode for URL: $urlStr"
                android.util.Log.e("ApiClient", errorMsg)
                throw Exception(errorMsg)
            }
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Network request failed for URL: $urlStr. Error: ${e.message}", e)
            throw e
        }
    }

    suspend fun fetchSurahs(): List<Surah> {
        val jsonStr = makeGetRequest("$BASE_URL?action=surahs")
        val jsonObj = JSONObject(jsonStr)
        val dataArray = jsonObj.getJSONArray("data")
        val list = mutableListOf<Surah>()
        for (i in 0 until dataArray.length()) {
            val item = dataArray.getJSONObject(i)
            list.add(
                Surah(
                    id = item.optInt("id"),
                    number = item.optInt("number"),
                    nameArabic = item.optString("name_arabic"),
                    nameSimple = item.optString("name_simple", item.optString("name_transliteration")),
                    nameTransliteration = item.optString("name_transliteration"),
                    nameTranslated = item.optString("name_translated", item.optString("name_english")),
                    revelationPlace = item.optString("revelation_place", item.optString("revelation_type")),
                    versesCount = item.optInt("verses_count", item.optInt("verse_count"))
                )
            )
        }
        return list
    }

    suspend fun fetchVerses(surahNumber: Int): List<Verse> {
        val jsonStr = makeGetRequest("$BASE_URL?action=verses&surah_number=$surahNumber")
        val jsonObj = JSONObject(jsonStr)
        val dataArray = jsonObj.getJSONArray("data")
        val list = mutableListOf<Verse>()
        for (i in 0 until dataArray.length()) {
            val item = dataArray.getJSONObject(i)
            list.add(
                Verse(
                    id = item.optInt("id"),
                    verseNumber = item.optInt("verse_number"),
                    verseKey = item.optString("verse_key"),
                    juzNumber = item.optInt("juz_number"),
                    textArabic = item.optString("text_arabic"),
                    textTransliteration = item.optString("text_transliteration")
                )
            )
        }
        return list
    }

    suspend fun fetchConnections(surahNumber: Int, verseNumber: Int): ConnectionsData {
        val jsonStr = makeGetRequest("$BASE_URL?action=connections&surah_number=$surahNumber&verse_number=$verseNumber")
        val jsonObj = JSONObject(jsonStr)
        val dataObj = jsonObj.getJSONObject("data")

        fun parseList(key: String): List<Connection> {
            val arr = dataObj.optJSONArray(key) ?: return emptyList()
            val list = mutableListOf<Connection>()
            for (i in 0 until arr.length()) {
                val item = arr.getJSONObject(i)
                list.add(
                    Connection(
                        title = item.optString("title", "Hadith #" + item.optString("hadith_number")),
                        description = item.optString("description", item.optString("relevance_description", item.optString("text", item.optString("link_description")))),
                        extraInfo = item.optString("field", item.optString("category", item.optString("collection_name", item.optString("extra_info", ""))))
                    )
                )
            }
            return list
        }

        return ConnectionsData(
            science = parseList("science"),
            seerah = parseList("seerah"),
            hadith = parseList("hadith"),
            history = parseList("history"),
            scripture = parseList("scripture")
        )
    }

    suspend fun fetchLeaderboard(): List<LeaderboardUser> {
        val jsonStr = makeGetRequest("$BASE_URL?action=leaderboard")
        val jsonObj = JSONObject(jsonStr)
        val dataArray = jsonObj.getJSONArray("data")
        val list = mutableListOf<LeaderboardUser>()
        for (i in 0 until dataArray.length()) {
            val item = dataArray.getJSONObject(i)
            list.add(
                LeaderboardUser(
                    id = item.optInt("id"),
                    name = item.optString("name"),
                    displayName = item.optString("display_name"),
                    email = item.optString("email"),
                    totalScore = item.optInt("total_score"),
                    totalQuestions = item.optInt("total_questions"),
                    seerahReadCount = item.optInt("seerah_read_count")
                )
            )
        }
        return list
    }

    suspend fun fetchInsights(
        seerahPage: Int = 1,
        historyPage: Int = 1,
        seerahCategory: String = "",
        historyCategory: String = ""
    ): InsightsData {
        val url = "$BASE_URL?action=insights&seerah_page=$seerahPage&history_page=$historyPage&seerah_category=$seerahCategory&history_category=$historyCategory"
        val jsonStr = makeGetRequest(url)
        val jsonObj = JSONObject(jsonStr)
        val dataObj = jsonObj.getJSONObject("data")

        val seerahArr = dataObj.getJSONArray("seerah_events")
        val seerahList = mutableListOf<SeerahEvent>()
        for (i in 0 until seerahArr.length()) {
            val item = seerahArr.getJSONObject(i)
            seerahList.add(
                SeerahEvent(
                    id = item.optInt("id"),
                    title = item.optString("title"),
                    description = item.optString("description"),
                    category = item.optString("category")
                )
            )
        }

        val historyArr = dataObj.getJSONArray("history_events")
        val historyList = mutableListOf<HistoryEvent>()
        for (i in 0 until historyArr.length()) {
            val item = historyArr.getJSONObject(i)
            historyList.add(
                HistoryEvent(
                    id = item.optInt("id"),
                    title = item.optString("title"),
                    description = item.optString("description"),
                    category = item.optString("category")
                )
            )
        }

        val seerahCatsArr = dataObj.optJSONArray("seerah_categories")
        val seerahCats = mutableListOf<String>()
        if (seerahCatsArr != null) {
            for (j in 0 until seerahCatsArr.length()) {
                seerahCats.add(seerahCatsArr.getString(j))
            }
        }

        val historyCatsArr = dataObj.optJSONArray("history_categories")
        val historyCats = mutableListOf<String>()
        if (historyCatsArr != null) {
            for (j in 0 until historyCatsArr.length()) {
                historyCats.add(historyCatsArr.getString(j))
            }
        }

        return InsightsData(
            seerahEvents = seerahList,
            historyEvents = historyList,
            seerahPage = dataObj.optInt("seerah_page", 1),
            seerahTotalPages = dataObj.optInt("seerah_total_pages", 1),
            historyPage = dataObj.optInt("history_page", 1),
            historyTotalPages = dataObj.optInt("history_total_pages", 1),
            seerahCategories = seerahCats,
            historyCategories = historyCats
        )
    }

    suspend fun fetchThemes(): List<Theme> {
        val jsonStr = makeGetRequest("$BASE_URL?action=themes")
        val jsonObj = JSONObject(jsonStr)
        val dataArr = jsonObj.getJSONArray("data")
        val themes = mutableListOf<Theme>()
        for (i in 0 until dataArr.length()) {
            val item = dataArr.getJSONObject(i)
            themes.add(
                Theme(
                    id = item.optInt("id"),
                    name = item.optString("name"),
                    type = item.optString("type"),
                    description = item.optString("description").takeIf { !item.isNull("description") }
                )
            )
        }
        return themes
    }

    suspend fun fetchThemeQuiz(themeId: Int, difficulty: String): List<QuizQuestion> {
        val jsonStr = makeGetRequest("$BASE_URL?action=theme_quiz&theme_id=$themeId&difficulty=$difficulty")
        val jsonObj = JSONObject(jsonStr)
        val dataArr = jsonObj.getJSONArray("data")
        val questions = mutableListOf<QuizQuestion>()
        for (i in 0 until dataArr.length()) {
            val item = dataArr.getJSONObject(i)
            val optionsArr = item.optJSONArray("options")
            val optionsList = mutableListOf<String>()
            if (optionsArr != null) {
                for (j in 0 until optionsArr.length()) {
                    optionsList.add(optionsArr.getString(j))
                }
            }
            questions.add(
                QuizQuestion(
                    id = item.optInt("id"),
                    questionId = item.optString("question_id"),
                    text = item.optString("text"),
                    options = optionsList,
                    correctAnswerIndex = item.optInt("correct_answer_index"),
                    explanation = item.optString("explanation", ""),
                    difficulty = item.optString("difficulty"),
                    reference = item.optString("reference", ""),
                    sourceInfo = item.optString("source_info", "")
                )
            )
        }
        return questions
    }

    suspend fun fetchDuas(category: String = "", search: String = "", sourceType: String = ""): List<Dua> {
        val queryParams = mutableListOf<String>()
        if (category.isNotBlank() && category != "all") queryParams.add("category=" + java.net.URLEncoder.encode(category, "UTF-8"))
        if (search.isNotBlank()) queryParams.add("search=" + java.net.URLEncoder.encode(search, "UTF-8"))
        if (sourceType.isNotBlank() && sourceType != "all") queryParams.add("source_type=" + java.net.URLEncoder.encode(sourceType, "UTF-8"))

        val queryString = if (queryParams.isNotEmpty()) "&" + queryParams.joinToString("&") else ""
        val jsonStr = makeGetRequest("$BASE_URL?action=duas$queryString")
        val jsonObj = JSONObject(jsonStr)
        val dataArr = jsonObj.optJSONArray("data") ?: return emptyList()

        val duas = mutableListOf<Dua>()
        for (i in 0 until dataArr.length()) {
            val item = dataArr.getJSONObject(i)
            val wbwArr = item.optJSONArray("word_by_word")
            val wbwList = mutableListOf<DuaWord>()
            if (wbwArr != null) {
                for (j in 0 until wbwArr.length()) {
                    val wObj = wbwArr.getJSONObject(j)
                    wbwList.add(
                        DuaWord(
                            arabic = wObj.optString("arabic", ""),
                            transliteration = wObj.optString("transliteration", ""),
                            meaningEn = wObj.optString("meaning_en", ""),
                            meaningUr = wObj.optString("meaning_ur").takeIf { it.isNotBlank() }
                        )
                    )
                }
            }

            duas.add(
                Dua(
                    id = item.optInt("id"),
                    title = item.optString("title", ""),
                    slug = item.optString("slug", ""),
                    category = item.optString("category", ""),
                    description = item.optString("description", "").takeIf { it.isNotBlank() },
                    arabicText = item.optString("arabic_text", ""),
                    transliteration = item.optString("transliteration", "").takeIf { it.isNotBlank() },
                    translationEn = item.optString("translation_en", ""),
                    translationUr = item.optString("translation_ur", "").takeIf { it.isNotBlank() },
                    wordByWord = if (wbwList.isNotEmpty()) wbwList else null,
                    meaningExplanation = item.optString("meaning_explanation", "").takeIf { it.isNotBlank() },
                    benefitsAndVirtues = item.optString("benefits_and_virtues", "").takeIf { it.isNotBlank() },
                    whenToRecite = item.optString("when_to_recite", "").takeIf { it.isNotBlank() },
                    repeatCount = item.optInt("repeat_count", 1),
                    sourceType = item.optString("source_type", "hadith"),
                    quranReference = item.optString("quran_reference", "").takeIf { it.isNotBlank() },
                    surahNumber = item.optInt("surah_number", 0).takeIf { it > 0 },
                    verseNumber = item.optInt("verse_number", 0).takeIf { it > 0 },
                    hadithReference = item.optString("hadith_reference", "").takeIf { it.isNotBlank() },
                    hadithBook = item.optString("hadith_book", "").takeIf { it.isNotBlank() },
                    hadithNumber = item.optString("hadith_number", "").takeIf { it.isNotBlank() },
                    hadithGrading = item.optString("hadith_grading", "").takeIf { it.isNotBlank() }
                )
            )
        }
        return duas
    }

    private suspend fun makePostRequest(urlStr: String, params: Map<String, String>): String = withContext(Dispatchers.IO) {
        if (!urlStr.startsWith("https://", ignoreCase = true)) {
            throw SecurityException("Insecure HTTP connections are strictly prohibited.")
        }
        val url = URL(urlStr)
        try {
            val conn = url.openConnection() as HttpURLConnection
            conn.requestMethod = "POST"
            conn.doOutput = true
            conn.connectTimeout = 10000
            conn.readTimeout = 10000
            conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded")

            val postData = params.map { (k, v) ->
                java.net.URLEncoder.encode(k, "UTF-8") + "=" + java.net.URLEncoder.encode(v, "UTF-8")
            }.joinToString("&")

            conn.outputStream.use { os ->
                os.write(postData.toByteArray(charset("UTF-8")))
            }

            val responseCode = conn.responseCode
            if (responseCode == HttpURLConnection.HTTP_OK) {
                val reader = BufferedReader(InputStreamReader(conn.inputStream))
                val response = StringBuilder()
                var line: String?
                while (reader.readLine().also { line = it } != null) {
                    response.append(line)
                }
                reader.close()
                response.toString()
            } else {
                val errorMsg = "HTTP Error Code: $responseCode for URL: $urlStr"
                android.util.Log.e("ApiClient", errorMsg)
                throw Exception(errorMsg)
            }
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Network request failed for URL: $urlStr. Error: ${e.message}", e)
            throw e
        }
    }

    suspend fun login(email: String, password: String): UserSession {
        val params = mapOf("email" to email, "password" to password)
        val jsonStr = makePostRequest("$BASE_URL?action=login", params)
        val jsonObj = JSONObject(jsonStr)
        val data = jsonObj.getJSONObject("data")
        return UserSession(
            userId = data.getInt("user_id"),
            name = data.getString("name"),
            email = data.getString("email"),
            totalScore = data.getInt("total_score")
        )
    }

    suspend fun register(name: String, email: String, password: String): UserSession {
        val params = mapOf("name" to name, "email" to email, "password" to password)
        val jsonStr = makePostRequest("$BASE_URL?action=register", params)
        val jsonObj = JSONObject(jsonStr)
        val data = jsonObj.getJSONObject("data")
        return UserSession(
            userId = data.getInt("user_id"),
            name = data.getString("name"),
            email = data.getString("email"),
            totalScore = data.getInt("total_score")
        )
    }

    suspend fun requestOtp(email: String): Boolean {
        val params = mapOf("email" to email)
        val jsonStr = makePostRequest("$BASE_URL?action=request_otp", params)
        val jsonObj = JSONObject(jsonStr)
        return jsonObj.getString("status") == "success"
    }

    suspend fun verifyOtp(email: String, otp: String): UserSessionOtpResponse {
        val params = mapOf("email" to email, "otp" to otp)
        val jsonStr = makePostRequest("$BASE_URL?action=verify_otp", params)
        val jsonObj = JSONObject(jsonStr)
        val data = jsonObj.getJSONObject("data")
        val session = UserSession(
            userId = data.getInt("user_id"),
            name = data.getString("name"),
            email = data.getString("email"),
            totalScore = data.getInt("total_score")
        )
        return UserSessionOtpResponse(
            session = session,
            hasPassword = data.getBoolean("has_password")
        )
    }

    suspend fun setPassword(userId: Int, password: String): Boolean {
        val params = mapOf("user_id" to userId.toString(), "password" to password)
        val jsonStr = makePostRequest("$BASE_URL?action=set_password", params)
        val jsonObj = JSONObject(jsonStr)
        return jsonObj.getString("status") == "success"
    }

    suspend fun changePassword(userId: Int, password: String): Boolean {
        val params = mapOf("user_id" to userId.toString(), "password" to password)
        val jsonStr = makePostRequest("$BASE_URL?action=change_password", params)
        val jsonObj = JSONObject(jsonStr)
        return jsonObj.getString("status") == "success"
    }

    suspend fun submitQuiz(
        userId: Int,
        type: String,
        title: String,
        score: Int,
        totalQuestions: Int,
        difficulty: String,
        questionsJson: String,
        userAnswersJson: String
    ): Boolean {
        val params = mapOf(
            "user_id" to userId.toString(),
            "type" to type,
            "title" to title,
            "score" to score.toString(),
            "total_questions" to totalQuestions.toString(),
            "difficulty" to difficulty,
            "questions" to questionsJson,
            "user_answers" to userAnswersJson
        )
        return try {
            val jsonStr = makePostRequest("$BASE_URL?action=submit_quiz", params)
            val jsonObj = JSONObject(jsonStr)
            jsonObj.getString("status") == "success"
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Failed to submit quiz score", e)
            false
        }
    }
}

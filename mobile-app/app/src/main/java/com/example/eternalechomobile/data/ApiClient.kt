package com.asloobulhayat.eternalecho.data

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONArray
import org.json.JSONObject
import java.io.BufferedReader
import java.io.InputStreamReader
import java.net.HttpURLConnection
import java.net.URL
import java.net.URLEncoder
import javax.net.ssl.HttpsURLConnection
import com.asloobulhayat.eternalecho.security.AppSecurity

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

    const val ACCOUNT_DELETE_URL: String = "https://theeternalecho.asloobulhayat.com/delete-account"

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
            conn.setRequestProperty("Accept", "application/json")

            val responseCode = conn.responseCode
            val stream = if (responseCode in 200..299) {
                conn.inputStream
            } else {
                conn.errorStream
            }

            val response = if (stream != null) {
                BufferedReader(InputStreamReader(stream)).use { reader ->
                    val sb = StringBuilder()
                    var line: String?
                    while (reader.readLine().also { line = it } != null) {
                        sb.append(line)
                    }
                    sb.toString()
                }
            } else {
                ""
            }

            if (responseCode in 200..299) {
                response
            } else {
                android.util.Log.e("ApiClient", "Server response code: $responseCode")
                val serverMsg = try {
                    val jsonObj = JSONObject(response)
                    jsonObj.optString("message").takeIf { it.isNotBlank() }
                } catch (ignored: Exception) {
                    null
                }
                throw Exception(serverMsg ?: "Unable to complete request. Please try again later.")
            }
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Network request failed: ${e.message}", e)
            val friendlyMsg = when {
                e is java.net.UnknownHostException || e is java.net.ConnectException ->
                    "Unable to connect to the server. Please check your internet connection."
                e is java.net.SocketTimeoutException ->
                    "Connection timed out. Please try again."
                !e.message.isNullOrBlank() && !e.message!!.contains("http", ignoreCase = true) ->
                    e.message!!
                else ->
                    "Unable to communicate with the server. Please try again."
            }
            throw Exception(friendlyMsg)
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
        val dataObj = jsonObj.optJSONObject("data") ?: return ConnectionsData(emptyList(), emptyList(), emptyList(), emptyList(), emptyList())

        fun parseScience(): List<Connection> {
            val arr = dataObj.optJSONArray("science") ?: return emptyList()
            val list = mutableListOf<Connection>()
            for (i in 0 until arr.length()) {
                val item = arr.getJSONObject(i)
                val field = item.optString("field", "")
                val cred = item.optString("credibility_score", "")
                list.add(
                    Connection(
                        title = item.optString("title", "Scientific Insight"),
                        description = item.optString("description", ""),
                        extraInfo = field,
                        relevanceDescription = item.optString("relevance_description", ""),
                        category = field,
                        sourceName = item.optString("source_name", ""),
                        credibilityScore = if (cred.isNotBlank() && cred != "0" && cred != "null") "$cred/10" else ""
                    )
                )
            }
            return list
        }

        fun parseSeerah(): List<Connection> {
            val arr = dataObj.optJSONArray("seerah") ?: return emptyList()
            val list = mutableListOf<Connection>()
            for (i in 0 until arr.length()) {
                val item = arr.getJSONObject(i)
                val hijri = item.optString("date_hijri", "").trim()
                val ce = item.optString("date_ce", "").trim()
                val dateStr = when {
                    hijri.isNotBlank() && ce.isNotBlank() -> "$hijri AH / $ce CE"
                    hijri.isNotBlank() -> "$hijri AH"
                    ce.isNotBlank() -> "$ce CE"
                    else -> ""
                }
                val srcBook = item.optString("source_book", "").trim()
                val srcRef = item.optString("source_reference", "").trim()
                val sourceCombined = if (srcBook.isNotBlank()) "$srcBook $srcRef".trim() else ""

                list.add(
                    Connection(
                        title = item.optString("title", "Seerah Event"),
                        description = item.optString("description", ""),
                        extraInfo = item.optString("category", ""),
                        relevanceDescription = item.optString("link_description", ""),
                        category = item.optString("category", ""),
                        dateInfo = dateStr,
                        location = item.optString("location", ""),
                        sourceName = sourceCombined
                    )
                )
            }
            return list
        }

        fun parseHadith(): List<Connection> {
            val arr = dataObj.optJSONArray("hadith") ?: return emptyList()
            val list = mutableListOf<Connection>()
            for (i in 0 until arr.length()) {
                val item = arr.getJSONObject(i)
                val coll = item.optString("collection_name", "Hadith")
                val num = item.optString("hadith_number", "").trim()
                val displayTitle = if (num.isNotBlank()) "$coll (#$num)" else coll

                list.add(
                    Connection(
                        title = displayTitle,
                        description = item.optString("text", ""),
                        extraInfo = coll,
                        relevanceDescription = item.optString("link_description", ""),
                        arabicText = item.optString("text_arabic", ""),
                        narratorChain = item.optString("narrator_chain", ""),
                        grading = item.optString("grading", ""),
                        collectionName = coll,
                        hadithNumber = num
                    )
                )
            }
            return list
        }

        fun parseHistory(): List<Connection> {
            val arr = dataObj.optJSONArray("history") ?: return emptyList()
            val list = mutableListOf<Connection>()
            for (i in 0 until arr.length()) {
                val item = arr.getJSONObject(i)
                val civ = item.optString("civilization", "").trim()
                val reg = item.optString("region", "").trim()
                val dateRange = item.optString("date_range", "").trim()
                list.add(
                    Connection(
                        title = item.optString("title", "Historical Event"),
                        description = item.optString("description", ""),
                        extraInfo = if (civ.isNotBlank()) civ else item.optString("extra_info", ""),
                        relevanceDescription = item.optString("link_description", ""),
                        dateInfo = dateRange,
                        location = reg,
                        scriptureType = civ
                    )
                )
            }
            return list
        }

        fun parseScripture(): List<Connection> {
            val arr = dataObj.optJSONArray("scripture") ?: return emptyList()
            val list = mutableListOf<Connection>()
            for (i in 0 until arr.length()) {
                val item = arr.getJSONObject(i)
                list.add(
                    Connection(
                        title = item.optString("title", "Scripture Reference"),
                        description = item.optString("text", ""),
                        extraInfo = item.optString("scripture_type", "Scripture"),
                        relevanceDescription = item.optString("link_description", ""),
                        scriptureType = item.optString("scripture_type", "Scripture"),
                        relationshipType = item.optString("relationship_type", "")
                    )
                )
            }
            return list
        }

        return ConnectionsData(
            science = parseScience(),
            seerah = parseSeerah(),
            hadith = parseHadith(),
            history = parseHistory(),
            scripture = parseScripture()
        )
    }

    suspend fun fetchAllConnections(
        category: String = "all",
        search: String = "",
        page: Int = 1,
        limit: Int = 20,
        surahNumber: Int? = null
    ): AllConnectionsResponse {
        val encodedCat = URLEncoder.encode(category, "UTF-8")
        val encodedSearch = URLEncoder.encode(search, "UTF-8")
        val surahParam = if (surahNumber != null && surahNumber > 0) "&surah_number=$surahNumber" else ""
        val url = "$BASE_URL?action=all_connections&category=$encodedCat&search=$encodedSearch&page=$page&limit=$limit$surahParam"

        val jsonStr = makeGetRequest(url)
        val jsonObj = JSONObject(jsonStr)

        val statsObj = jsonObj.optJSONObject("stats") ?: JSONObject()
        val stats = ConnectionStats(
            surahsCount = statsObj.optInt("surahs_count", 114),
            scienceCount = statsObj.optInt("science_count", 0),
            seerahCount = statsObj.optInt("seerah_count", 0),
            hadithCount = statsObj.optInt("hadith_count", 0),
            historyCount = statsObj.optInt("history_count", 0),
            scriptureCount = statsObj.optInt("scripture_count", 0),
            totalCount = statsObj.optInt("total_count", 0)
        )

        val cat = jsonObj.optString("category", category)
        val currPage = jsonObj.optInt("page", page)
        val totalPages = jsonObj.optInt("total_pages", 1)
        val totalItems = jsonObj.optInt("total_items", 0)

        val dataArr = jsonObj.optJSONArray("data") ?: JSONArray()
        val list = mutableListOf<GlobalConnectionItem>()

        for (i in 0 until dataArr.length()) {
            val item = dataArr.getJSONObject(i)
            list.add(
                GlobalConnectionItem(
                    id = item.optInt("id", i + 1),
                    category = item.optString("category", "science"),
                    surahNumber = item.optInt("surah_number", 1),
                    surahName = item.optString("surah_name", ""),
                    verseNumber = item.optInt("verse_number", 1),
                    juzNumber = item.optInt("juz_number", 1),
                    verseArabic = item.optString("verse_arabic", ""),
                    verseTransliteration = item.optString("verse_transliteration", ""),
                    title = item.optString("title", ""),
                    description = item.optString("description", ""),
                    extraInfo = item.optString("extra_info", ""),
                    relevanceDescription = item.optString("relevance_description", ""),
                    field = item.optString("field", ""),
                    sourceName = item.optString("source_name", ""),
                    credibilityScore = item.optString("credibility_score", ""),
                    dateInfo = item.optString("date_info", ""),
                    location = item.optString("location", ""),
                    arabicText = item.optString("arabic_text", ""),
                    narratorChain = item.optString("narrator_chain", ""),
                    grading = item.optString("grading", ""),
                    collectionName = item.optString("collection_name", ""),
                    hadithNumber = item.optString("hadith_number", ""),
                    scriptureType = item.optString("scripture_type", ""),
                    relationshipType = item.optString("relationship_type", "")
                )
            )
        }

        return AllConnectionsResponse(
            stats = stats,
            category = cat,
            page = currPage,
            totalPages = totalPages,
            totalItems = totalItems,
            data = list
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

    suspend fun fetchThemeQuiz(themeId: Int, difficulty: String, quantity: Int = 20): List<QuizQuestion> {
        val jsonStr = makeGetRequest("$BASE_URL?action=theme_quiz&theme_id=$themeId&difficulty=$difficulty&quantity=$quantity")
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

    suspend fun fetchGrandQuiz(quizType: String, difficulty: String, quantity: Int = 20): List<QuizQuestion> {
        val jsonStr = makeGetRequest("$BASE_URL?action=grand_quiz&quiz_type=$quizType&difficulty=$difficulty&quantity=$quantity")
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
            conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8")
            conn.setRequestProperty("Accept", "application/json")

            val postData = params.map { (k, v) ->
                java.net.URLEncoder.encode(k.trim(), "UTF-8") + "=" + java.net.URLEncoder.encode(v.trim(), "UTF-8")
            }.joinToString("&")

            val postBytes = postData.toByteArray(charset("UTF-8"))
            conn.setFixedLengthStreamingMode(postBytes.size)

            conn.outputStream.use { os ->
                os.write(postBytes)
                os.flush()
            }

            val responseCode = conn.responseCode
            val stream = if (responseCode in 200..299) {
                conn.inputStream
            } else {
                conn.errorStream
            }

            val response = if (stream != null) {
                BufferedReader(InputStreamReader(stream)).use { reader ->
                    val sb = StringBuilder()
                    var line: String?
                    while (reader.readLine().also { line = it } != null) {
                        sb.append(line)
                    }
                    sb.toString()
                }
            } else {
                ""
            }

            if (responseCode in 200..299) {
                response
            } else {
                android.util.Log.e("ApiClient", "Server response code: $responseCode")
                val serverMsg = try {
                    val jsonObj = JSONObject(response)
                    jsonObj.optString("message").takeIf { it.isNotBlank() }
                } catch (ignored: Exception) {
                    null
                }
                throw Exception(serverMsg ?: "Unable to complete request. Please try again later.")
            }
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Network request failed: ${e.message}", e)
            val friendlyMsg = when {
                e is java.net.UnknownHostException || e is java.net.ConnectException ->
                    "Unable to connect to the server. Please check your internet connection."
                e is java.net.SocketTimeoutException ->
                    "Connection timed out. Please try again."
                !e.message.isNullOrBlank() && !e.message!!.contains("http", ignoreCase = true) ->
                    e.message!!
                else ->
                    "Unable to communicate with the server. Please try again."
            }
            throw Exception(friendlyMsg)
        }
    }

    suspend fun login(email: String, password: String): UserSession {
        val cleanEmail = email.trim()
        val params = mapOf("email" to cleanEmail, "password" to password)
        val encodedEmail = java.net.URLEncoder.encode(cleanEmail, "UTF-8")
        val jsonStr = makePostRequest("$BASE_URL?action=login&email=$encodedEmail", params)
        val jsonObj = JSONObject(jsonStr)
        if (jsonObj.optString("status") != "success") {
            val msg = jsonObj.optString("message", "Invalid email or password.")
            throw Exception(msg)
        }
        val data = jsonObj.getJSONObject("data")
        return UserSession(
            userId = data.getInt("user_id"),
            name = data.getString("name"),
            email = data.getString("email"),
            totalScore = data.getInt("total_score")
        )
    }

    suspend fun register(name: String, email: String, password: String): UserSession {
        val cleanEmail = email.trim()
        val cleanName = name.trim()
        val params = mapOf("name" to cleanName, "email" to cleanEmail, "password" to password)
        val jsonStr = makePostRequest("$BASE_URL?action=register", params)
        val jsonObj = JSONObject(jsonStr)
        if (jsonObj.optString("status") != "success") {
            val msg = jsonObj.optString("message", "Registration failed.")
            throw Exception(msg)
        }
        val data = jsonObj.getJSONObject("data")
        return UserSession(
            userId = data.getInt("user_id"),
            name = data.getString("name"),
            email = data.getString("email"),
            totalScore = data.getInt("total_score")
        )
    }

    suspend fun requestOtp(email: String): Boolean {
        val cleanEmail = email.trim()
        val params = mapOf("email" to cleanEmail)
        val encodedEmail = java.net.URLEncoder.encode(cleanEmail, "UTF-8")
        val jsonStr = makePostRequest("$BASE_URL?action=request_otp&email=$encodedEmail", params)
        val jsonObj = JSONObject(jsonStr)
        if (jsonObj.optString("status") != "success") {
            val msg = jsonObj.optString("message", "Failed to send verification code.")
            throw Exception(msg)
        }
        return true
    }

    suspend fun verifyOtp(email: String, otp: String): UserSessionOtpResponse {
        val cleanEmail = email.trim()
        val cleanOtp = otp.trim()
        val params = mapOf("email" to cleanEmail, "otp" to cleanOtp)
        val encodedEmail = java.net.URLEncoder.encode(cleanEmail, "UTF-8")
        val encodedOtp = java.net.URLEncoder.encode(cleanOtp, "UTF-8")
        val jsonStr = makePostRequest("$BASE_URL?action=verify_otp&email=$encodedEmail&otp=$encodedOtp", params)
        val jsonObj = JSONObject(jsonStr)
        if (jsonObj.optString("status") != "success") {
            val msg = jsonObj.optString("message", "Verification failed. Please try again.")
            throw Exception(msg)
        }
        val data = jsonObj.getJSONObject("data")
        val session = UserSession(
            userId = data.getInt("user_id"),
            name = data.getString("name"),
            email = data.getString("email"),
            totalScore = data.getInt("total_score")
        )
        return UserSessionOtpResponse(
            session = session,
            hasPassword = data.optBoolean("has_password", false)
        )
    }

    suspend fun setPassword(userId: Int, password: String): Boolean {
        val params = mapOf("user_id" to userId.toString(), "password" to password)
        val jsonStr = makePostRequest("$BASE_URL?action=set_password&user_id=$userId", params)
        val jsonObj = JSONObject(jsonStr)
        if (jsonObj.optString("status") != "success") {
            val msg = jsonObj.optString("message", "Failed to set password.")
            throw Exception(msg)
        }
        return true
    }

    suspend fun changePassword(userId: Int, password: String): Boolean {
        val params = mapOf("user_id" to userId.toString(), "password" to password)
        val jsonStr = makePostRequest("$BASE_URL?action=change_password&user_id=$userId", params)
        val jsonObj = JSONObject(jsonStr)
        if (jsonObj.optString("status") != "success") {
            val msg = jsonObj.optString("message", "Failed to change password.")
            throw Exception(msg)
        }
        return true
    }

    suspend fun deleteAccount(userId: Int): Boolean {
        val params = mapOf("user_id" to userId.toString())
        val jsonStr = makePostRequest("$BASE_URL?action=delete_account&user_id=$userId", params)
        val jsonObj = JSONObject(jsonStr)
        if (jsonObj.optString("status") != "success") {
            val msg = jsonObj.optString("message", "Failed to delete account.")
            throw Exception(msg)
        }
        return true
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

    suspend fun fetchNotifications(sinceId: Long = 0, limit: Int = 20): List<NotificationItem> {
        return try {
            val url = if (sinceId > 0) {
                "$BASE_URL?action=notifications&since_id=$sinceId&limit=$limit"
            } else {
                "$BASE_URL?action=notifications&limit=$limit"
            }
            val jsonStr = makeGetRequest(url)
            val jsonObj = JSONObject(jsonStr)
            val dataArr = jsonObj.optJSONArray("data") ?: JSONArray()
            val list = mutableListOf<NotificationItem>()
            for (i in 0 until dataArr.length()) {
                val item = dataArr.getJSONObject(i)
                list.add(
                    NotificationItem(
                        id = item.optLong("id"),
                        title = item.optString("title", "Announcement"),
                        message = item.optString("message", ""),
                        type = item.optString("type", "announcement"),
                        actionUrl = item.optString("action_url").takeIf { it.isNotEmpty() && it != "null" },
                        createdAt = item.optString("created_at").takeIf { it.isNotEmpty() && it != "null" },
                        isRead = false
                    )
                )
            }
            list
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Failed to fetch notifications: ${e.message}", e)
            emptyList()
        }
    }

    suspend fun registerDeviceToken(token: String, deviceName: String = "Android Device", appVersion: String = "1.0", userId: Int? = null): Boolean {
        return try {
            val params = mutableMapOf(
                "token" to token,
                "platform" to "android",
                "device_name" to deviceName,
                "app_version" to appVersion
            )
            if (userId != null) {
                params["user_id"] = userId.toString()
            }
            val jsonStr = makePostRequest("$BASE_URL?action=register_device_token", params)
            val jsonObj = JSONObject(jsonStr)
            jsonObj.optString("status") == "success"
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Failed to register device token: ${e.message}", e)
            false
        }
    }
}


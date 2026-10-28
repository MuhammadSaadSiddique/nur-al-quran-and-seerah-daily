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
    @Volatile
    var authToken: String? = null

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
    const val DONATION_URL: String = "https://theeternalecho.asloobulhayat.com/donate"

    private val webTranslationCache = java.util.concurrent.ConcurrentHashMap<Int, Map<Int, List<VerseTranslation>>>()

    private data class TranslationEdition(
        val id: String,
        val name: String,
        val language: String,
        val direction: String,
        val resourceId: Int
    )

    private val translationEditions = listOf(
        TranslationEdition("en.sahih", "Saheeh International", "en", "ltr", 20),
        TranslationEdition("en.pickthall", "Pickthall", "en", "ltr", 19),
        TranslationEdition("en.yusufali", "Yusuf Ali", "en", "ltr", 22),
        TranslationEdition("en.hilali", "Hilali & Khan", "en", "ltr", 203),
        TranslationEdition("ur.jalandhry", "Jalandhry (Urdu)", "ur", "rtl", 234),
        TranslationEdition("ur.maududi", "Maududi (Urdu)", "ur", "rtl", 97)
    )

    private suspend fun makeGetRequest(
        urlStr: String,
        timeoutMs: Int = 10000,
        sendAuth: Boolean = true
    ): String = withContext(Dispatchers.IO) {
        if (!urlStr.startsWith("https://", ignoreCase = true)) {
            throw SecurityException("Insecure HTTP connections are strictly prohibited.")
        }
        val url = URL(urlStr)
        try {
            val conn = url.openConnection() as HttpURLConnection
            conn.requestMethod = "GET"
            conn.connectTimeout = timeoutMs
            conn.readTimeout = timeoutMs
            conn.setRequestProperty("Accept", "application/json")
            if (sendAuth) {
                applyAuthHeader(conn)
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

    private fun applyAuthHeader(conn: HttpURLConnection) {
        val token = authToken?.trim().orEmpty()
        if (token.isNotEmpty()) {
            conn.setRequestProperty("Authorization", "Bearer $token")
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
            val translations = mutableListOf<VerseTranslation>()
            val translationArray = item.optJSONArray("translations")
            if (translationArray != null) {
                for (j in 0 until translationArray.length()) {
                    val translation = translationArray.optJSONObject(j) ?: continue
                    val text = translation.optString("text")
                    val id = translation.optString("id")
                    if (text.isBlank() || id.isBlank()) continue
                    translations.add(
                        VerseTranslation(
                            id = id,
                            name = translation.optString("name", id),
                            language = translation.optString("language", "en"),
                            text = text,
                            direction = translation.optString("direction", "ltr").ifBlank { "ltr" }
                        )
                    )
                }
            }
            list.add(
                Verse(
                    id = item.optInt("id"),
                    verseNumber = item.optInt("verse_number"),
                    verseKey = item.optString("verse_key"),
                    juzNumber = item.optInt("juz_number"),
                    textArabic = item.optString("text_arabic"),
                    textTransliteration = item.optString("text_transliteration"),
                    translations = translations
                )
            )
        }
        return list
    }

    /**
     * Published translations from Quran.com. The app's own API only returns
     * Arabic and transliteration, so the reader loads these directly.
     */
    suspend fun fetchWebTranslations(surahNumber: Int): Map<Int, List<VerseTranslation>> {
        if (surahNumber !in 1..114) return emptyMap()
        webTranslationCache[surahNumber]?.let { return it }

        val resourceIds = translationEditions.joinToString(",") { it.resourceId.toString() }
        val jsonStr = try {
            makeGetRequest(
                "https://api.quran.com/api/v4/verses/by_chapter/$surahNumber?translations=$resourceIds&per_page=286&fields=verse_number",
                timeoutMs = 25000,
                sendAuth = false
            )
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Translation fetch failed: ${e.message}")
            return emptyMap()
        }

        val root = try {
            JSONObject(jsonStr)
        } catch (e: Exception) {
            return emptyMap()
        }

        val verses = root.optJSONArray("verses") ?: return emptyMap()
        val catalog = translationEditions.associateBy { it.resourceId }
        val byVerse = mutableMapOf<Int, MutableList<VerseTranslation>>()
        for (i in 0 until verses.length()) {
            val verse = verses.optJSONObject(i) ?: continue
            val number = verse.optInt("verse_number")
            if (number < 1) continue
            val translations = verse.optJSONArray("translations") ?: continue
            for (j in 0 until translations.length()) {
                val translation = translations.optJSONObject(j) ?: continue
                val meta = catalog[translation.optInt("resource_id")] ?: continue
                val text = cleanTranslationText(translation.optString("text"))
                if (text.isBlank()) continue
                byVerse.getOrPut(number) { mutableListOf() }.add(
                    VerseTranslation(
                        id = meta.id,
                        name = meta.name,
                        language = meta.language,
                        text = text,
                        direction = meta.direction
                    )
                )
            }
        }

        val order = translationEditions.mapIndexed { index, edition -> edition.id to index }.toMap()
        val sorted = byVerse.mapValues { (_, items) ->
            items.sortedBy { order[it.id] ?: 99 }
        }
        if (sorted.isNotEmpty()) {
            webTranslationCache[surahNumber] = sorted
        }
        return sorted
    }

    private fun cleanTranslationText(raw: String): String {
        return raw
            .replace(Regex("<sup\\b[^>]*>.*?</sup>", RegexOption.IGNORE_CASE), " ")
            .replace(Regex("<[^>]*>"), " ")
            .replace("&amp;", "&")
            .replace("&quot;", "\"")
            .replace("&#39;", "'")
            .replace("&apos;", "'")
            .replace("&lt;", "<")
            .replace("&gt;", ">")
            .replace(Regex("\\s+"), " ")
            .trim()
    }

    /**
     * Same translation set as the website ayah page: stored translations plus
     * the published Quran.com editions.
     */
    suspend fun fetchVerseTranslations(surahNumber: Int, verseNumber: Int): List<VerseTranslation> {
        if (surahNumber !in 1..114 || verseNumber < 1) return emptyList()
        val jsonStr = makeGetRequest(
            "$BASE_URL?action=verse_translations&surah_number=$surahNumber&verse_number=$verseNumber",
            timeoutMs = 20000
        )
        val jsonObj = JSONObject(jsonStr)
        val dataArray = jsonObj.optJSONArray("data") ?: return emptyList()
        val list = mutableListOf<VerseTranslation>()
        for (i in 0 until dataArray.length()) {
            val item = dataArray.optJSONObject(i) ?: continue
            val text = cleanTranslationText(item.optString("text"))
            val id = item.optString("id")
            if (text.isBlank() || id.isBlank()) continue
            list.add(
                VerseTranslation(
                    id = id,
                    name = item.optString("name", id),
                    language = item.optString("language", "en"),
                    text = text,
                    direction = item.optString("direction", "ltr").ifBlank { "ltr" }
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
        seerahPage: Int = 0,
        historyPage: Int = 0,
        seerahCategory: String = "",
        historyCategory: String = ""
    ): InsightsData {
        // page 0 / negative => "daily" (server picks today's reflection)
        val seerahPageParam = if (seerahPage <= 0) "daily" else seerahPage.toString()
        val historyPageParam = if (historyPage <= 0) "daily" else historyPage.toString()
        val encodedSeerahCat = URLEncoder.encode(seerahCategory, "UTF-8")
        val encodedHistoryCat = URLEncoder.encode(historyCategory, "UTF-8")
        val url = "$BASE_URL?action=insights&limit=1&seerah_page=$seerahPageParam&history_page=$historyPageParam&seerah_category=$encodedSeerahCat&history_category=$encodedHistoryCat"
        val jsonStr = makeGetRequest(url)
        val jsonObj = JSONObject(jsonStr)
        val dataObj = jsonObj.getJSONObject("data")

        fun parseOptions(item: JSONObject): List<String> {
            val optionsArr = item.optJSONArray("options") ?: return emptyList()
            val list = mutableListOf<String>()
            for (j in 0 until optionsArr.length()) {
                list.add(optionsArr.optString(j))
            }
            return list
        }

        val seerahArr = dataObj.getJSONArray("seerah_events")
        val seerahList = mutableListOf<SeerahEvent>()
        for (i in 0 until seerahArr.length()) {
            val item = seerahArr.getJSONObject(i)
            seerahList.add(
                SeerahEvent(
                    id = item.optInt("id"),
                    title = item.optString("title"),
                    description = item.optString("description"),
                    category = item.optString("category"),
                    questionId = item.optString("question_id", ""),
                    questionText = item.optString("question_text", ""),
                    options = parseOptions(item),
                    // Server must send -1; never trust a leaked key if present.
                    correctAnswerIndex = -1,
                    explanation = "",
                    isRead = item.optBoolean("is_read", false)
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
                    category = item.optString("category"),
                    questionId = item.optString("question_id", ""),
                    questionText = item.optString("question_text", ""),
                    options = parseOptions(item),
                    correctAnswerIndex = -1,
                    explanation = "",
                    isRead = item.optBoolean("is_read", false)
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

    private fun parseQuizQuestions(dataArr: JSONArray): List<QuizQuestion> {
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
                    correctAnswerIndex = -1,
                    explanation = "",
                    difficulty = item.optString("difficulty"),
                    reference = item.optString("reference", ""),
                    sourceInfo = item.optString("source_info", "")
                )
            )
        }
        return questions
    }

    suspend fun fetchThemeQuiz(themeId: Int, difficulty: String, quantity: Int = 20): QuizSession {
        val jsonStr = makeGetRequest("$BASE_URL?action=theme_quiz&theme_id=$themeId&difficulty=$difficulty&quantity=$quantity")
        val jsonObj = JSONObject(jsonStr)
        if (jsonObj.optString("status") == "error") {
            throw Exception(jsonObj.optString("message", "Failed to load Theme Quiz questions."))
        }
        val dataArr = jsonObj.optJSONArray("data") ?: return QuizSession(emptyList())
        return QuizSession(
            questions = parseQuizQuestions(dataArr),
            attemptToken = jsonObj.optString("attempt_token", "")
        )
    }

    suspend fun fetchGrandQuiz(quizType: String, difficulty: String, quantity: Int = 20): QuizSession {
        val jsonStr = makeGetRequest("$BASE_URL?action=grand_quiz&quiz_type=$quizType&difficulty=$difficulty&quantity=$quantity")
        val jsonObj = JSONObject(jsonStr)
        if (jsonObj.optString("status") == "error") {
            throw Exception(jsonObj.optString("message", "Failed to load Grand Quiz questions."))
        }
        val dataArr = jsonObj.optJSONArray("data") ?: return QuizSession(emptyList())
        return QuizSession(
            questions = parseQuizQuestions(dataArr),
            attemptToken = jsonObj.optString("attempt_token", "")
        )
    }

    fun parseDuaJson(item: JSONObject): Dua {
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

        return Dua(
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
            duas.add(parseDuaJson(dataArr.getJSONObject(i)))
        }
        return duas
    }

    suspend fun fetchDua(id: Int = 0, slug: String = ""): Dua? {
        val param = if (id > 0) {
            "id=$id"
        } else if (slug.isNotBlank()) {
            "slug=" + java.net.URLEncoder.encode(slug, "UTF-8")
        } else {
            return null
        }
        return try {
            val jsonStr = makeGetRequest("$BASE_URL?action=dua&$param")
            val jsonObj = JSONObject(jsonStr)
            if (jsonObj.optString("status") != "success") return null
            val item = jsonObj.optJSONObject("data") ?: return null
            parseDuaJson(item)
        } catch (_: Exception) {
            null
        }
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
            applyAuthHeader(conn)

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
        val session = UserSession(
            userId = data.getInt("user_id"),
            name = data.getString("name"),
            email = data.getString("email"),
            totalScore = data.getInt("total_score"),
            token = data.optString("token", "")
        )
        if (session.token.isNotBlank()) {
            authToken = session.token
        }
        return session
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
        val session = UserSession(
            userId = data.getInt("user_id"),
            name = data.getString("name"),
            email = data.getString("email"),
            totalScore = data.getInt("total_score"),
            token = data.optString("token", "")
        )
        if (session.token.isNotBlank()) {
            authToken = session.token
        }
        return session
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
            totalScore = data.getInt("total_score"),
            token = data.optString("token", "")
        )
        if (session.token.isNotBlank()) {
            authToken = session.token
        }
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

    suspend fun changePassword(userId: Int, password: String, currentPassword: String = ""): Boolean {
        val params = mutableMapOf(
            "user_id" to userId.toString(),
            "password" to password
        )
        if (currentPassword.isNotBlank()) {
            params["current_password"] = currentPassword
        }
        val jsonStr = makePostRequest("$BASE_URL?action=change_password&user_id=$userId", params)
        val jsonObj = JSONObject(jsonStr)
        if (jsonObj.optString("status") != "success") {
            val msg = jsonObj.optString("message", "Failed to change password.")
            throw Exception(msg)
        }
        return true
    }

    suspend fun logout(): Boolean {
        return try {
            val jsonStr = makePostRequest("$BASE_URL?action=logout", emptyMap())
            val jsonObj = JSONObject(jsonStr)
            authToken = null
            jsonObj.optString("status") == "success"
        } catch (_: Exception) {
            authToken = null
            false
        }
    }

    suspend fun deleteAccount(
        userId: Int,
        currentPassword: String = "",
        confirmDeletion: Boolean = false
    ): Boolean {
        val params = mutableMapOf("user_id" to userId.toString())
        if (currentPassword.isNotBlank()) {
            params["current_password"] = currentPassword
        }
        if (confirmDeletion) {
            params["confirm_deletion"] = "1"
        }
        val jsonStr = makePostRequest("$BASE_URL?action=delete_account&user_id=$userId", params)
        val jsonObj = JSONObject(jsonStr)
        if (jsonObj.optString("status") != "success") {
            val msg = jsonObj.optString("message", "Failed to delete account.")
            throw Exception(msg)
        }
        authToken = null
        return true
    }

    suspend fun checkAnswer(
        questionId: String,
        selectedIndex: Int,
        options: List<String> = emptyList()
    ): AnswerReveal? {
        val params = mutableMapOf(
            "question_id" to questionId,
            "selected_index" to selectedIndex.toString()
        )
        if (options.isNotEmpty()) {
            params["options"] = JSONArray(options).toString()
        }
        return try {
            val jsonStr = makePostRequest("$BASE_URL?action=check_answer", params)
            val jsonObj = JSONObject(jsonStr)
            if (jsonObj.optString("status") != "success") return null
            val data = jsonObj.getJSONObject("data")
            AnswerReveal(
                correct = data.optBoolean("correct", false),
                correctAnswerIndex = data.optInt("correct_answer_index", -1),
                explanation = data.optString("explanation", ""),
                questionId = data.optString("question_id", questionId)
            )
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Failed to check answer", e)
            null
        }
    }

    suspend fun submitQuiz(
        userId: Int,
        type: String,
        title: String,
        score: Int,
        totalQuestions: Int,
        difficulty: String,
        questionsJson: String,
        userAnswersJson: String,
        attemptToken: String = ""
    ): Boolean {
        val params = mutableMapOf(
            "user_id" to userId.toString(),
            "type" to type,
            "title" to title,
            "score" to score.toString(),
            "total_questions" to totalQuestions.toString(),
            "difficulty" to difficulty,
            "questions" to questionsJson,
            "user_answers" to userAnswersJson
        )
        if (attemptToken.isNotBlank()) {
            params["attempt_token"] = attemptToken
        }
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

    suspend fun submitDua(
        title: String,
        category: String,
        arabicText: String,
        translationEn: String,
        sourceType: String = "hadith",
        reference: String = "",
        transliteration: String = "",
        translationUr: String = "",
        submitterName: String = "",
        submitterEmail: String = "",
        userId: Int? = null
    ): Result<String> = withContext(Dispatchers.IO) {
        try {
            val params = mutableMapOf(
                "title" to title,
                "category" to category,
                "arabic_text" to arabicText,
                "translation_en" to translationEn,
                "source_type" to sourceType,
                "transliteration" to transliteration,
                "translation_ur" to translationUr,
                "submitter_name" to submitterName,
                "submitter_email" to submitterEmail
            )
            if (sourceType.equals("quran", ignoreCase = true)) {
                params["quran_reference"] = reference
            } else {
                params["hadith_reference"] = reference
            }
            if (userId != null) {
                params["user_id"] = userId.toString()
            }

            val jsonStr = makePostRequest("$BASE_URL?action=submit_dua", params)
            val jsonObj = JSONObject(jsonStr)
            if (jsonObj.optString("status") == "success") {
                val msg = jsonObj.optString("message", "Dua submitted successfully for review.")
                Result.success(msg)
            } else {
                val msg = jsonObj.optString("message", "Unable to submit Dua. Please try again.")
                Result.failure(Exception(msg))
            }
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Failed to submit Dua: ${e.message}", e)
            Result.failure(e)
        }
    }

    private fun parseDailyEngagement(data: JSONObject): DailyEngagement {
        val streakObj = data.optJSONObject("streak")
        val streakHistoryArray = streakObj?.optJSONArray("streak_history")
        val history = mutableListOf<String>()
        if (streakHistoryArray != null) {
            for (i in 0 until streakHistoryArray.length()) {
                history.add(streakHistoryArray.getString(i))
            }
        }
        val streak = StreakData(
            currentStreak = streakObj?.optInt("current_streak", 0) ?: 0,
            longestStreak = streakObj?.optInt("longest_streak", 0) ?: 0,
            isActiveToday = streakObj?.optBoolean("is_active_today", false) ?: false,
            lastStreakDate = streakObj?.optString("last_streak_date")?.takeIf { it.isNotBlank() },
            streakHistory = history
        )

        val activitiesObj = data.optJSONObject("activities")
        val activityList = mutableListOf<EngagementActivity>()
        val keys = listOf("quiz", "read", "insight", "dua")
        if (activitiesObj != null) {
            for (key in keys) {
                val act = activitiesObj.optJSONObject(key) ?: continue
                activityList.add(
                    EngagementActivity(
                        key = key,
                        done = act.optBoolean("done", false),
                        label = act.optString("label", key.replaceFirstChar { it.uppercase() }),
                        href = act.optString("href", "")
                    )
                )
            }
        }

        return DailyEngagement(
            date = data.optString("date", ""),
            activities = activityList,
            completedCount = data.optInt("completed_count", activityList.count { it.done }),
            total = data.optInt("total", 4),
            progressPercent = data.optInt("progress_percent", 0),
            streak = streak
        )
    }

    suspend fun getDailyEngagement(userId: Int): Result<DailyEngagement> = withContext(Dispatchers.IO) {
        try {
            val jsonStr = makeGetRequest("$BASE_URL?action=daily_engagement&user_id=$userId")
            val jsonObj = JSONObject(jsonStr)
            if (jsonObj.optString("status") == "success") {
                Result.success(parseDailyEngagement(jsonObj.getJSONObject("data")))
            } else {
                Result.failure(Exception(jsonObj.optString("message", "Unable to load daily engagement.")))
            }
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Failed to fetch daily engagement: ${e.message}", e)
            Result.failure(e)
        }
    }

    suspend fun markEngagement(userId: Int, activity: String): Result<DailyEngagement> = withContext(Dispatchers.IO) {
        try {
            val params = mapOf(
                "user_id" to userId.toString(),
                "activity" to activity
            )
            val jsonStr = makePostRequest("$BASE_URL?action=mark_engagement", params)
            val jsonObj = JSONObject(jsonStr)
            if (jsonObj.optString("status") == "success") {
                Result.success(parseDailyEngagement(jsonObj.getJSONObject("data")))
            } else {
                Result.failure(Exception(jsonObj.optString("message", "Unable to record engagement.")))
            }
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Failed to mark engagement: ${e.message}", e)
            Result.failure(e)
        }
    }

    suspend fun markInsight(userId: Int, type: String, insightId: String): Result<DailyEngagement?> = withContext(Dispatchers.IO) {
        try {
            val params = mapOf(
                "user_id" to userId.toString(),
                "type" to type,
                "insight_id" to insightId
            )
            val jsonStr = makePostRequest("$BASE_URL?action=mark_insight", params)
            val jsonObj = JSONObject(jsonStr)
            if (jsonObj.optString("status") == "success") {
                val data = jsonObj.optJSONObject("data")
                val engagementObj = data?.optJSONObject("engagement")
                Result.success(engagementObj?.let { parseDailyEngagement(it) })
            } else {
                Result.failure(Exception(jsonObj.optString("message", "Unable to mark insight.")))
            }
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Failed to mark insight: ${e.message}", e)
            Result.failure(e)
        }
    }

    data class SurahReadResult(
        val isRead: Boolean,
        val engagement: DailyEngagement?
    )

    suspend fun toggleSurahRead(userId: Int, surah: Int): Result<SurahReadResult> = withContext(Dispatchers.IO) {
        try {
            val params = mapOf(
                "user_id" to userId.toString(),
                "surah" to surah.toString()
            )
            val jsonStr = makePostRequest("$BASE_URL?action=toggle_surah", params)
            val jsonObj = JSONObject(jsonStr)
            if (jsonObj.optString("status") == "success") {
                val data = jsonObj.optJSONObject("data")
                val engagementObj = data?.optJSONObject("engagement")
                Result.success(
                    SurahReadResult(
                        isRead = data?.optBoolean("is_read", false) ?: false,
                        engagement = engagementObj?.let { parseDailyEngagement(it) }
                    )
                )
            } else {
                Result.failure(Exception(jsonObj.optString("message", "Unable to toggle surah.")))
            }
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Failed to toggle surah: ${e.message}", e)
            Result.failure(e)
        }
    }

    suspend fun getStreakStatus(userId: Int): Result<StreakData> = withContext(Dispatchers.IO) {
        try {
            val jsonStr = makeGetRequest("$BASE_URL?action=streak_status&user_id=$userId")
            val jsonObj = JSONObject(jsonStr)
            if (jsonObj.optString("status") == "success") {
                val data = jsonObj.getJSONObject("data")
                val streakHistoryArray = data.optJSONArray("streak_history")
                val history = mutableListOf<String>()
                if (streakHistoryArray != null) {
                    for (i in 0 until streakHistoryArray.length()) {
                        history.add(streakHistoryArray.getString(i))
                    }
                }
                val streak = StreakData(
                    currentStreak = data.optInt("current_streak", 0),
                    longestStreak = data.optInt("longest_streak", 0),
                    isActiveToday = data.optBoolean("is_active_today", false),
                    lastStreakDate = data.optString("last_streak_date").takeIf { it.isNotBlank() },
                    streakHistory = history
                )
                Result.success(streak)
            } else {
                Result.failure(Exception(jsonObj.optString("message", "Unable to load streak data.")))
            }
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Failed to fetch streak status: ${e.message}", e)
            Result.failure(e)
        }
    }

    suspend fun checkInDailyStreak(userId: Int): Result<StreakData> = withContext(Dispatchers.IO) {
        try {
            val params = mapOf("user_id" to userId.toString())
            val jsonStr = makePostRequest("$BASE_URL?action=daily_streak", params)
            val jsonObj = JSONObject(jsonStr)
            if (jsonObj.optString("status") == "success") {
                val data = jsonObj.getJSONObject("data")
                val streakHistoryArray = data.optJSONArray("streak_history")
                val history = mutableListOf<String>()
                if (streakHistoryArray != null) {
                    for (i in 0 until streakHistoryArray.length()) {
                        history.add(streakHistoryArray.getString(i))
                    }
                }
                val streak = StreakData(
                    currentStreak = data.optInt("current_streak", 0),
                    longestStreak = data.optInt("longest_streak", 0),
                    isActiveToday = data.optBoolean("is_active_today", true),
                    lastStreakDate = data.optString("last_streak_date").takeIf { it.isNotBlank() },
                    streakHistory = history
                )
                Result.success(streak)
            } else {
                Result.failure(Exception(jsonObj.optString("message", "Unable to record daily streak.")))
            }
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Failed to check in daily streak: ${e.message}", e)
            Result.failure(e)
        }
    }

    suspend fun fetchUserStats(userId: Int): Result<UserProfileStats> = withContext(Dispatchers.IO) {
        try {
            val jsonStr = makeGetRequest("$BASE_URL?action=user_stats&user_id=$userId")
            val jsonObj = JSONObject(jsonStr)
            if (jsonObj.optString("status") == "success") {
                val data = jsonObj.getJSONObject("data")
                val streakObj = data.optJSONObject("streak")
                val streakHistoryArray = streakObj?.optJSONArray("streak_history")
                val history = mutableListOf<String>()
                if (streakHistoryArray != null) {
                    for (i in 0 until streakHistoryArray.length()) {
                        history.add(streakHistoryArray.getString(i))
                    }
                }
                val streak = StreakData(
                    currentStreak = streakObj?.optInt("current_streak", 0) ?: 0,
                    longestStreak = streakObj?.optInt("longest_streak", 0) ?: 0,
                    isActiveToday = streakObj?.optBoolean("is_active_today", false) ?: false,
                    lastStreakDate = streakObj?.optString("last_streak_date")?.takeIf { it.isNotBlank() },
                    streakHistory = history
                )

                val diffObj = data.optJSONObject("difficulty")
                val easyObj = diffObj?.optJSONObject("easy")
                val medObj = diffObj?.optJSONObject("medium")
                val hardObj = diffObj?.optJSONObject("hard")

                val difficultyGroup = DifficultyStatsGroup(
                    easy = DifficultyStat(
                        total = easyObj?.optInt("total", 0) ?: 0,
                        correct = easyObj?.optInt("correct", 0) ?: 0,
                        accuracy = easyObj?.optInt("accuracy", 0) ?: 0
                    ),
                    medium = DifficultyStat(
                        total = medObj?.optInt("total", 0) ?: 0,
                        correct = medObj?.optInt("correct", 0) ?: 0,
                        accuracy = medObj?.optInt("accuracy", 0) ?: 0
                    ),
                    hard = DifficultyStat(
                        total = hardObj?.optInt("total", 0) ?: 0,
                        correct = hardObj?.optInt("correct", 0) ?: 0,
                        accuracy = hardObj?.optInt("accuracy", 0) ?: 0
                    )
                )

                val stats = UserProfileStats(
                    userId = data.optInt("user_id", userId),
                    name = data.optString("name", "User"),
                    email = data.optString("email", ""),
                    spiritualLevel = data.optString("spiritual_level", "Novice"),
                    totalScore = data.optInt("total_score", 0),
                    maxPossibleScore = data.optInt("max_possible_score", 0),
                    totalQuestions = data.optInt("total_questions", 0),
                    accuracy = data.optInt("accuracy", 0),
                    surahsCompletedCount = data.optInt("surahs_completed_count", 0),
                    parasCompletedCount = data.optInt("paras_completed_count", 0),
                    seerahReadCount = data.optInt("seerah_read_count", 0),
                    quranHistoryReadCount = data.optInt("quran_history_read_count", 0),
                    bookmarksCount = data.optInt("bookmarks_count", 0),
                    streak = streak,
                    engagement = data.optJSONObject("engagement")?.let { parseDailyEngagement(it) },
                    difficulty = difficultyGroup,
                    hasPassword = data.optBoolean("has_password", false)
                )
                Result.success(stats)
            } else {
                Result.failure(Exception(jsonObj.optString("message", "Unable to load user profile statistics.")))
            }
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Failed to fetch user stats: ${e.message}", e)
            Result.failure(e)
        }
    }

    suspend fun fetchResearchContributions(
        tab: String = "published",
        category: String = "all",
        surahNumber: Int? = null,
        search: String = "",
        page: Int = 1,
        userId: Int? = null
    ): List<ResearchContribution> = withContext(Dispatchers.IO) {
        val qParams = mutableListOf<String>()
        qParams.add("action=research_contributions")
        qParams.add("tab=${URLEncoder.encode(tab, "UTF-8")}")
        if (category != "all" && category.isNotBlank()) {
            qParams.add("category=${URLEncoder.encode(category, "UTF-8")}")
        }
        if (surahNumber != null) {
            qParams.add("surah_number=$surahNumber")
        }
        if (search.isNotBlank()) {
            qParams.add("search=${URLEncoder.encode(search, "UTF-8")}")
        }
        qParams.add("page=$page")
        if (userId != null && userId > 0) {
            qParams.add("user_id=$userId")
        }

        val url = "$BASE_URL?" + qParams.joinToString("&")
        val jsonStr = makeGetRequest(url)
        val jsonObj = JSONObject(jsonStr)
        val dataArray = jsonObj.optJSONArray("data") ?: JSONArray()
        val list = mutableListOf<ResearchContribution>()

        for (i in 0 until dataArray.length()) {
            val item = dataArray.getJSONObject(i)
            val sNumsArr = item.optJSONArray("surah_numbers")
            val parsedSurahNumbers = if (sNumsArr != null) {
                val tmp = mutableListOf<Int>()
                for (j in 0 until sNumsArr.length()) {
                    val v = sNumsArr.optInt(j, 0)
                    if (v in 1..114) tmp.add(v)
                }
                tmp.takeIf { it.isNotEmpty() }
            } else null

            list.add(
                ResearchContribution(
                    id = item.optInt("id"),
                    userId = if (item.has("user_id") && !item.isNull("user_id")) item.optInt("user_id") else null,
                    submitterName = item.optString("author_name").ifBlank { item.optString("submitter_name") },
                    surahNumber = item.optInt("surah_number", 1),
                    endSurahNumber = if (item.has("end_surah_number") && !item.isNull("end_surah_number")) item.optInt("end_surah_number") else null,
                    surahNumbers = parsedSurahNumbers,
                    verseNumber = item.optInt("verse_number", 1),
                    endVerseNumber = if (item.has("end_verse_number") && !item.isNull("end_verse_number")) item.optInt("end_verse_number") else null,
                    isFullSurah = item.optBoolean("is_full_surah", false) || item.optInt("is_full_surah", 0) == 1,
                    scopeLabel = item.optString("scope_label", ""),
                    surahName = item.optString("surah_name"),
                    category = item.optString("category", "video"),
                    title = item.optString("title"),
                    description = item.optString("description"),
                    videoUrl = item.optString("video_url"),
                    videoPlatform = item.optString("video_platform"),
                    videoId = item.optString("video_id"),
                    status = item.optString("status", "pending"),
                    approvalsCount = item.optInt("approvals_count", 0),
                    downvotesCount = item.optInt("downvotes_count", 0),
                    myVote = if (item.has("my_vote") && !item.isNull("my_vote")) item.optString("my_vote") else null,
                    myFeedback = if (item.has("my_feedback") && !item.isNull("my_feedback")) item.optString("my_feedback") else null,
                    createdAt = item.optString("created_at")
                )
            )
        }
        list
    }

    suspend fun submitResearchContribution(
        title: String,
        surahNumber: Int,
        verseNumber: Int,
        category: String,
        videoUrl: String,
        description: String,
        submitterName: String = "",
        submitterEmail: String = "",
        userId: Int? = null,
        endSurahNumber: Int? = null,
        surahNumbers: String = "",
        endVerseNumber: Int? = null,
        isFullSurah: Boolean = false
    ): Result<String> = withContext(Dispatchers.IO) {
        try {
            val params = mutableMapOf(
                "title" to title,
                "surah_number" to surahNumber.toString(),
                "verse_number" to verseNumber.toString(),
                "category" to category,
                "video_url" to videoUrl,
                "description" to description,
                "submitter_name" to submitterName,
                "submitter_email" to submitterEmail,
                "is_full_surah" to (if (isFullSurah) "1" else "0")
            )
            if (endSurahNumber != null && endSurahNumber > 0) {
                params["end_surah_number"] = endSurahNumber.toString()
            }
            if (surahNumbers.isNotBlank()) {
                params["surah_numbers"] = surahNumbers
            }
            if (endVerseNumber != null && endVerseNumber > 0) {
                params["end_verse_number"] = endVerseNumber.toString()
            }
            if (userId != null && userId > 0) {
                params["user_id"] = userId.toString()
            }

            val jsonStr = makePostRequest("$BASE_URL?action=submit_research_contribution", params)
            val jsonObj = JSONObject(jsonStr)
            if (jsonObj.optString("status") == "success") {
                val msg = jsonObj.optString("message", "Contribution submitted successfully.")
                Result.success(msg)
            } else {
                val msg = jsonObj.optString("message", "Unable to submit contribution.")
                Result.failure(Exception(msg))
            }
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Failed to submit research contribution: ${e.message}", e)
            Result.failure(e)
        }
    }

    suspend fun reviewResearchContribution(
        contributionId: Int,
        userId: Int,
        vote: String,
        feedback: String = ""
    ): Result<String> = withContext(Dispatchers.IO) {
        try {
            val params = mapOf(
                "contribution_id" to contributionId.toString(),
                "user_id" to userId.toString(),
                "vote" to vote,
                "feedback" to feedback
            )

            val jsonStr = makePostRequest("$BASE_URL?action=review_research_contribution", params)
            val jsonObj = JSONObject(jsonStr)
            if (jsonObj.optString("status") == "success") {
                val msg = jsonObj.optString("message", "Review saved.")
                Result.success(msg)
            } else {
                val msg = jsonObj.optString("message", "Unable to submit review.")
                Result.failure(Exception(msg))
            }
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Failed to review research contribution: ${e.message}", e)
            Result.failure(e)
        }
    }

    suspend fun submitAppFeedback(
        rating: Int,
        category: String = "app_experience",
        message: String = "",
        userId: Int? = null,
        userName: String? = null,
        userEmail: String? = null,
        deviceInfo: String? = null
    ): Result<String> {
        return try {
            val params = mutableMapOf(
                "rating" to rating.toString(),
                "category" to category,
                "message" to message,
                "platform" to "android"
            )
            if (userId != null && userId > 0) {
                params["user_id"] = userId.toString()
            }
            if (!userName.isNullOrBlank()) {
                params["user_name"] = userName
            }
            if (!userEmail.isNullOrBlank()) {
                params["user_email"] = userEmail
            }
            if (!deviceInfo.isNullOrBlank()) {
                params["device_info"] = deviceInfo
            }

            val jsonStr = makePostRequest("$BASE_URL?action=submit_app_feedback", params)
            val jsonObj = JSONObject(jsonStr)
            if (jsonObj.optString("status") == "success") {
                val msg = jsonObj.optString("message", "JazakAllahu Khairan! Thank you for sharing your feedback.")
                Result.success(msg)
            } else {
                val msg = jsonObj.optString("message", "Unable to submit feedback.")
                Result.failure(Exception(msg))
            }
        } catch (e: Exception) {
            android.util.Log.e("ApiClient", "Failed to submit app feedback: ${e.message}", e)
            Result.failure(e)
        }
    }
}

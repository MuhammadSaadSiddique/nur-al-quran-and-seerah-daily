package com.asloobulhayat.eternalecho.data

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONArray
import org.json.JSONObject
import java.net.URLEncoder

/**
 * Mobile API calls for the community mosque directory (mobile.php actions
 * backed by the Laravel MosqueService).
 */
object MosqueApi {
    private val base: String get() = ApiClient.BASE_URL

    suspend fun nearby(
        latitude: Double?,
        longitude: Double?,
        radiusKm: Double,
        search: String = "",
        filters: Set<MosqueFilter> = emptySet(),
        sect: MosqueSect? = null
    ): Result<List<Mosque>> = call {
        val query = buildQuery(
            "action" to "nearby_mosques",
            "latitude" to latitude?.toString(),
            "longitude" to longitude?.toString(),
            "radius_km" to radiusKm.toString(),
            "search" to search.trim().ifBlank { null },
            "filters" to filters.joinToString(",") { it.key }.ifBlank { null },
            "sect" to sect?.key,
            "limit" to "40"
        )
        parseMosqueArray(JSONObject(ApiClient.makeGetRequest("$base?$query")).optJSONArray("data"))
    }

    suspend fun byIds(ids: Collection<Int>, latitude: Double?, longitude: Double?): Result<List<Mosque>> = call {
        if (ids.isEmpty()) return@call emptyList()
        val query = buildQuery(
            "action" to "mosques_by_ids",
            "ids" to ids.distinct().joinToString(","),
            "latitude" to latitude?.toString(),
            "longitude" to longitude?.toString()
        )
        parseMosqueArray(JSONObject(ApiClient.makeGetRequest("$base?$query")).optJSONArray("data"))
    }

    suspend fun add(
        name: String,
        locality: String,
        address: String,
        city: String,
        country: String,
        latitude: Double,
        longitude: Double,
        times: MosqueTimesInput,
        details: MosqueDetailsInput,
        note: String = ""
    ): Result<AddMosqueResult> = call {
        val params = mutableMapOf(
            "name" to name,
            "locality" to locality,
            "address" to address,
            "city" to city,
            "country" to country,
            "latitude" to latitude.toString(),
            "longitude" to longitude.toString(),
            "note" to note
        )
        params.putAll(timeParams(times))
        params.putAll(detailParams(details))
        val json = postOrThrow("add_mosque", params)
        AddMosqueResult(
            mosque = parseMosque(json.getJSONObject("data")),
            duplicate = json.optBoolean("duplicate", false),
            message = json.str("message")
        )
    }

    suspend fun updateTimes(mosqueId: Int, times: MosqueTimesInput, note: String = ""): Result<Pair<Mosque, String>> = call {
        val params = mutableMapOf("mosque_id" to mosqueId.toString(), "note" to note)
        params.putAll(timeParams(times))
        val json = postOrThrow("update_mosque_times", params)
        parseMosque(json.getJSONObject("data")) to json.str("message")
    }

    /** Facilities, sect, Jumu'ah and Ramadan details. A sect change waits for review. */
    suspend fun updateDetails(mosqueId: Int, details: MosqueDetailsInput): Result<Pair<Mosque, String>> = call {
        val params = mutableMapOf("mosque_id" to mosqueId.toString())
        params.putAll(detailParams(details))
        val json = postOrThrow("update_mosque_details", params)
        parseMosque(json.getJSONObject("data")) to json.str("message")
    }

    suspend fun confirmTimes(mosqueId: Int): Result<Pair<Mosque, String>> = call {
        val json = postOrThrow("confirm_mosque_times", mapOf("mosque_id" to mosqueId.toString()))
        parseMosque(json.getJSONObject("data")) to json.str("message")
    }

    suspend fun report(mosqueId: Int, reason: String, note: String): Result<String> = call {
        val json = postOrThrow(
            "report_mosque",
            mapOf("mosque_id" to mosqueId.toString(), "reason" to reason, "note" to note)
        )
        json.str("message")
    }

    suspend fun favorites(latitude: Double?, longitude: Double?): Result<Map<SalahPrayer, Mosque>> = call {
        val query = buildQuery(
            "action" to "salah_favorites",
            "latitude" to latitude?.toString(),
            "longitude" to longitude?.toString()
        )
        parseFavorites(JSONObject(ApiClient.makeGetRequest("$base?$query")).optJSONObject("data"))
    }

    suspend fun setFavorite(prayer: SalahPrayer, mosqueId: Int?): Result<Map<SalahPrayer, Mosque>> = call {
        val json = postOrThrow(
            "set_salah_favorite",
            mapOf("prayer" to prayer.key, "mosque_id" to (mosqueId?.toString() ?: ""))
        )
        parseFavorites(json.optJSONObject("data"))
    }

    suspend fun contributionSummary(): Result<MosqueContributionSummary> = call {
        val data = JSONObject(ApiClient.makeGetRequest("$base?action=my_mosque_contributions"))
            .optJSONObject("data") ?: JSONObject()
        MosqueContributionSummary(
            mosquesAdded = data.optInt("mosques_added"),
            timesUpdated = data.optInt("times_updated"),
            confirmations = data.optInt("confirmations"),
            mosquesHelped = data.optInt("mosques_helped"),
            worshippersBenefiting = data.optInt("worshippers_benefiting")
        )
    }

    internal fun parseMosque(item: JSONObject): Mosque {
        val timesObj = item.optJSONObject("times")
        val times = buildMap {
            SalahPrayer.entries.forEach { prayer ->
                val value = timesObj?.str(prayer.key).orEmpty()
                if (parseClockTime(value) != null) put(prayer, value)
            }
        }
        val facilitiesObj = item.optJSONObject("facilities")
        val facilities = buildMap {
            MosqueFacility.entries.forEach { facility ->
                if (facilitiesObj != null && facilitiesObj.has(facility.key) && !facilitiesObj.isNull(facility.key)) {
                    put(facility, facilitiesObj.optBoolean(facility.key))
                }
            }
        }
        val languages = item.optJSONArray("khutbah_languages")?.let { arr ->
            (0 until arr.length()).map { arr.optString(it) }.filter { code -> KHUTBAH_LANGUAGES.any { it.first == code } }
        }.orEmpty()
        val taraweeh = item.optJSONObject("taraweeh")
        val eid = item.optJSONObject("eid_times")

        return Mosque(
            id = item.optInt("id"),
            name = item.str("name"),
            address = item.str("address"),
            locality = item.str("locality"),
            city = item.str("city"),
            country = item.str("country"),
            latitude = item.optDouble("latitude", 0.0),
            longitude = item.optDouble("longitude", 0.0),
            placeType = PlaceType.fromKey(item.str("place_type")),
            sect = MosqueSect.fromKey(item.str("sect")),
            sectChangePending = item.optBoolean("sect_change_pending", false),
            asrMethod = AsrMethod.fromKey(item.str("asr_method")),
            times = times,
            jumuahSecondTime = item.str("jumuah_second_time").takeIf { parseClockTime(it) != null },
            maghribOffsetMinutes = item.intOrNull("maghrib_offset_minutes"),
            hasWomenArea = if (item.isNull("has_women_area")) null else item.optBoolean("has_women_area"),
            facilities = facilities,
            khutbahLanguages = languages,
            taraweehRakats = taraweeh?.intOrNull("rakats"),
            taraweehKhatm = taraweeh?.let { if (!it.has("khatm") || it.isNull("khatm")) null else it.optBoolean("khatm") },
            eidFitr = eid?.optJSONObject("fitr")?.let(::parseEid),
            eidAdha = eid?.optJSONObject("adha")?.let(::parseEid),
            isVerified = item.optBoolean("is_verified", false),
            confirmationsCount = item.optInt("confirmations_count"),
            regularsCount = item.optInt("regulars_count"),
            timesUpdatedAt = item.str("times_updated_at").ifBlank { null },
            daysSinceUpdate = item.intOrNull("days_since_update"),
            isStale = item.optBoolean("is_stale", true),
            distanceKm = if (item.isNull("distance_km") || !item.has("distance_km")) null else item.optDouble("distance_km"),
            attribution = item.str("attribution").ifBlank { null },
            osmUrl = item.str("osm_url").takeIf { it.startsWith("https://www.openstreetmap.org/") }
        )
    }

    /** Same shape the server sends, so cached favourites round-trip through [parseMosque]. */
    internal fun mosqueToJson(m: Mosque): JSONObject = JSONObject().apply {
        put("id", m.id)
        put("name", m.name)
        put("address", m.address)
        put("locality", m.locality)
        put("city", m.city)
        put("country", m.country)
        put("latitude", m.latitude)
        put("longitude", m.longitude)
        put("place_type", m.placeType.key)
        put("sect", m.sect?.key ?: JSONObject.NULL)
        put("sect_change_pending", m.sectChangePending)
        put("asr_method", m.asrMethod?.key ?: JSONObject.NULL)
        put("times", JSONObject().apply { m.times.forEach { (p, t) -> put(p.key, t) } })
        put("jumuah_second_time", m.jumuahSecondTime ?: JSONObject.NULL)
        put("maghrib_offset_minutes", m.maghribOffsetMinutes ?: JSONObject.NULL)
        put("has_women_area", m.hasWomenArea ?: JSONObject.NULL)
        put("facilities", JSONObject().apply { m.facilities.forEach { (f, v) -> put(f.key, v) } })
        put("khutbah_languages", JSONArray(m.khutbahLanguages))
        put("taraweeh", JSONObject().apply {
            put("rakats", m.taraweehRakats ?: JSONObject.NULL)
            put("khatm", m.taraweehKhatm ?: JSONObject.NULL)
        })
        put("eid_times", JSONObject().apply {
            m.eidFitr?.let { put("fitr", eidToJson(it)) }
            m.eidAdha?.let { put("adha", eidToJson(it)) }
        })
        put("is_verified", m.isVerified)
        put("confirmations_count", m.confirmationsCount)
        put("regulars_count", m.regularsCount)
        put("times_updated_at", m.timesUpdatedAt ?: JSONObject.NULL)
        put("days_since_update", m.daysSinceUpdate ?: JSONObject.NULL)
        put("is_stale", m.isStale)
        put("distance_km", m.distanceKm ?: JSONObject.NULL)
        put("attribution", m.attribution ?: JSONObject.NULL)
        put("osm_url", m.osmUrl ?: JSONObject.NULL)
    }

    internal fun parseFavorites(data: JSONObject?): Map<SalahPrayer, Mosque> {
        if (data == null) return emptyMap()
        return buildMap {
            SalahPrayer.entries.forEach { prayer ->
                data.optJSONObject(prayer.key)?.let { put(prayer, parseMosque(it)) }
            }
        }
    }

    private fun parseMosqueArray(arr: JSONArray?): List<Mosque> {
        if (arr == null) return emptyList()
        return (0 until arr.length()).mapNotNull { i -> arr.optJSONObject(i)?.let(::parseMosque) }
    }

    private fun parseEid(obj: JSONObject): EidTimes? {
        val arr = obj.optJSONArray("times") ?: return null
        val times = (0 until arr.length()).map { arr.optString(it) }.filter { parseClockTime(it) != null }
        val year = obj.optInt("year", 0)
        return if (times.isEmpty() || year == 0) null else EidTimes(year, times)
    }

    private fun eidToJson(eid: EidTimes): JSONObject =
        JSONObject().put("year", eid.year).put("times", JSONArray(eid.times))

    private fun timeParams(times: MosqueTimesInput): Map<String, String> = buildMap {
        SalahPrayer.entries.forEach { prayer ->
            put("${prayer.key}_time", times.times[prayer]?.trim().orEmpty())
        }
        put("jumuah_time_2", times.jumuahSecondTime?.trim().orEmpty())
        put("maghrib_offset_minutes", times.maghribOffsetMinutes?.toString() ?: "")
    }

    /** Form-encoded the way PHP parses it: facilities[key]=1/0/"" and comma lists. */
    internal fun detailParams(d: MosqueDetailsInput): Map<String, String> = buildMap {
        put("place_type", d.placeType.key)
        put("sect", d.sect?.key ?: "")
        put("asr_method", d.asrMethod?.key ?: "")
        put("has_women_area", triState(d.hasWomenArea))
        MosqueFacility.entries.forEach { f -> put("facilities[${f.key}]", triState(d.facilities[f])) }
        put("khutbah_languages", d.khutbahLanguages.joinToString(","))
        put("taraweeh_rakats", d.taraweehRakats?.toString() ?: "")
        put("taraweeh_khatm", triState(d.taraweehKhatm))
        put("eid_year", d.eidYear.toString())
        put("eid_fitr_times", d.eidFitrTimes.joinToString(","))
        put("eid_adha_times", d.eidAdhaTimes.joinToString(","))
    }

    private fun triState(value: Boolean?): String = when (value) {
        true -> "1"
        false -> "0"
        null -> ""
    }

    private suspend fun postOrThrow(action: String, params: Map<String, String>): JSONObject {
        val json = JSONObject(ApiClient.makePostRequest("$base?action=$action", params))
        if (json.optString("status") != "success") {
            throw Exception(json.str("message").ifBlank { "Something went wrong. Please try again." })
        }
        return json
    }

    private fun buildQuery(vararg pairs: Pair<String, String?>): String =
        pairs.filter { it.second != null }
            .joinToString("&") { (k, v) -> k + "=" + URLEncoder.encode(v, "UTF-8") }

    private suspend fun <T> call(block: suspend () -> T): Result<T> = withContext(Dispatchers.IO) {
        try {
            Result.success(block())
        } catch (e: Exception) {
            android.util.Log.e("MosqueApi", "Request failed: ${e.message}", e)
            Result.failure(e)
        }
    }
}

/** org.json returns the text "null" for JSON nulls; treat those as empty. */
private fun JSONObject.str(key: String): String =
    if (!has(key) || isNull(key)) "" else optString(key, "").trim()

private fun JSONObject.intOrNull(key: String): Int? =
    if (!has(key) || isNull(key)) null else optInt(key)

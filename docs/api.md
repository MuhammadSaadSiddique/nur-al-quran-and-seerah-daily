# Nur Al-Quran and Seerah Daily - API Documentation

This document describes the API endpoints available for retrieving and submitting research content (Quranic Lens Analyses) and performing other API actions.

---

## Table of Contents
1. [Base URL & General Headers](#base-url--general-headers)
2. [Authentication](#authentication)
3. [Endpoints](#endpoints)
   - [Get Auth Token (`POST /api/token`)](#1-get-auth-token-post-apitoken)
   - [Retrieve Research Content (`GET /api/research`)](#2-retrieve-research-content-getapiresearch)
   - [Submit Research Content (`POST /api/research`)](#3-submit-research-content-postapiresearch)
   - [Bulk Question Upload (`POST /api/upload-questions`)](#4-bulk-question-upload-postapiupload-questions)

---

## Base URL & General Headers

The default API base URL is:
```
http://localhost:8000
```
(or the deployed domain of the application).

### General Headers
All request payloads must be sent as JSON. Ensure the following headers are included for all requests:
```http
Accept: application/json
Content-Type: application/json
```

---

## Authentication

The API supports two authentication mechanisms:
1. **User Authentication (Sanctum Tokens)**:
   - Used for submitting research and viewing restricted statuses.
   - Header format: `Authorization: Bearer <your-personal-access-token>`
2. **System Admin Authentication (Admin Token)**:
   - Used for bulk uploads.
   - Header format: `X-Admin-Token: <admin-secret-token>`

---

## Endpoints

### 1. Get Auth Token (`POST /api/token`)

Authenticate a user with their registered email and password to receive a Sanctum Bearer token for subsequent API calls.

* **Method**: `POST`
* **Path**: `/api/token`
* **Authentication**: None

#### Request Body
| Field | Type | Required | Description |
|---|---|---|---|
| `email` | string | Yes | The registered user's email address. |
| `password` | string | Yes | The user's password. |
| `token_name` | string | No | A custom label for the token (defaults to `api-token`). |

*Example Request Body:*
```json
{
  "email": "researcher@example.com",
  "password": "correctpassword",
  "token_name": "mobile-app-session"
}
```

#### Responses
* **`200 OK`**: Successfully authenticated. Returns the bearer token.
  ```json
  {
    "token": "1|xYzAbCdEfGhIjKlMnOpQrStUvWxYz...",
    "user": {
      "id": 2,
      "name": "Researcher User",
      "email": "researcher@example.com",
      "is_researcher": true,
      "is_admin": false
    }
  }
  ```
* **`401 Unauthorized`**: Invalid email or password.
  ```json
  {
    "error": "Invalid email or password."
  }
  ```
* **`400 Bad Request`**: Account has no password set (registered via OTP only).
  ```json
  {
    "error": "You registered via OTP and do not have a password set. Please log in via OTP first and set a password in your Profile."
  }
  ```

*Example cURL:*
```bash
curl -X POST http://localhost:8000/api/token \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email": "researcher@example.com", "password": "correctpassword"}'
```

---

### 2. Retrieve Research Content (`GET /api/research`)

Retrieve a paginated list of Quranic Lens Analyses (research entries).

* **Method**: `GET`
* **Path**: `/api/research`
* **Authentication**: Optional. If authenticated as an administrator or researcher, you can view pending or rejected content using the `status` filter.

#### Query Parameters
| Parameter | Type | Required | Description |
|---|---|---|---|
| `lens_type` | string | No | Filter by lens category (e.g. `science`, `history`, `tafsir`, `hadith`, `seerat`, `biology`, `maths`, `bible`, `torah`, `psychology`). |
| `chapter_number` | integer | No | Filter by Quranic Surah number (1-114). |
| `verse_number` | integer | No | Filter by verse number (Ayah). |
| `theme_id` | integer | No | Filter by specific thematic ID. |
| `status` | string | No | Filter by status (`approved`, `pending`, `rejected`). **Note**: Only accessible to authenticated researchers/admins. Non-moderator requests default to `approved` and ignore other statuses. |
| `per_page` | integer | No | Number of records per page (default: 15, max: 100). |
| `page` | integer | No | The page number to retrieve. |

#### Response (`200 OK`)
Returns a standard Laravel paginated response containing an array of research objects.

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 12,
      "user_id": 2,
      "chapter_number": 2,
      "verse_number": 164,
      "lens_type": "science",
      "title": "Cosmological Wonders in Verse 164",
      "content": "Detailed commentary about wind circulation, clouds, and cosmic balance as depicted in this verse.",
      "status": "approved",
      "theme_id": null,
      "created_at": "2026-08-23T11:31:06.000000Z",
      "updated_at": "2026-08-23T11:31:06.000000Z",
      "user": {
        "id": 2,
        "name": "Researcher User",
        "email": "researcher@example.com"
      },
      "theme": null
    }
  ],
  "first_page_url": "http://localhost:8000/api/research?page=1",
  "from": 1,
  "last_page": 1,
  "last_page_url": "http://localhost:8000/api/research?page=1",
  "next_page_url": null,
  "path": "http://localhost:8000/api/research",
  "per_page": 15,
  "prev_page_url": null,
  "to": 1,
  "total": 1
}
```

*Example cURL:*
```bash
curl -X GET "http://localhost:8000/api/research?lens_type=science&chapter_number=2" \
  -H "Accept: application/json"
```

---

### 3. Submit Research Content (`POST /api/research`)

Submit a new Quranic Lens Analysis (research content).

* **Method**: `POST`
* **Path**: `/api/research`
* **Authentication**: Required (Bearer Token)

#### Request Body
| Field | Type | Required | Description |
|---|---|---|---|
| `chapter_number` | integer | Yes | Surah number (1-114). |
| `verse_number` | integer | Yes | Verse (Ayah) number (1 or greater). |
| `lens_type` | string | Yes | Category of the research. Must be one of: `tafsir`, `hadith`, `seerat`, `science`, `biology`, `maths`, `history`, `bible`, `torah`, `psychology`. |
| `title` | string | Yes | A concise title for the research (max 255 chars). |
| `content` | string | Yes | The research analysis content body (min 10 chars). |
| `theme_id` | integer | No | Optional ID of a theme this research links to. |

*Example Request Body:*
```json
{
  "chapter_number": 30,
  "verse_number": 48,
  "lens_type": "science",
  "title": "Atmospheric Science & Rain Formation",
  "content": "This verse describes how clouds are spread across the sky and then fragment to form raindrops, which corresponds with modern meteorology."
}
```

#### Responses
* **`201 Created`**: Successfully submitted.
  * For regular users, `status` will be `pending` and require a researcher/admin approval.
  * For researcher/admin users, `status` will automatically be `approved`.
  ```json
  {
    "success": true,
    "message": "Your research analysis has been submitted and is currently pending review by a researcher.",
    "data": {
      "id": 13,
      "user_id": 3,
      "chapter_number": 30,
      "verse_number": 48,
      "lens_type": "science",
      "title": "Atmospheric Science & Rain Formation",
      "content": "This verse describes how clouds are spread across the sky and then fragment to form raindrops, which corresponds with modern meteorology.",
      "theme_id": null,
      "status": "pending",
      "created_at": "2026-08-23T11:33:00.000000Z",
      "updated_at": "2026-08-23T11:33:00.000000Z",
      "user": {
        "id": 3,
        "name": "Regular User",
        "email": "user@example.com"
      },
      "theme": null
    }
  }
  ```
* **`422 Unprocessable Content`**: Validation failed.
  ```json
  {
    "errors": {
      "chapter_number": ["The chapter number must be between 1 and 114."],
      "content": ["The content field must be at least 10 characters."]
    }
  }
  ```
* **`401 Unauthorized`**: Missing or invalid bearer token.
  ```json
  {
    "message": "Unauthenticated."
  }
  ```

*Example cURL:*
```bash
curl -X POST http://localhost:8000/api/research \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer 1|xYzAbCdEf..." \
  -d '{"chapter_number": 30, "verse_number": 48, "lens_type": "science", "title": "Atmospheric Science", "content": "Analysis of atmospheric processes."}'
```

---

### 4. Bulk Question Upload (`POST /api/upload-questions`)

Upload generated questions in bulk.

* **Method**: `POST`
* **Path**: `/api/upload-questions`
* **Authentication**: Admin Token (`X-Admin-Token` header)

#### Request Body
A JSON array containing question objects:
```json
[
  {
    "type": "PARA",
    "difficulty": "Easy",
    "theme": "Quranic Wisdom",
    "text": "What is the first chapter of the Quran?",
    "options": ["Al-Baqarah", "Al-Fatihah", "Al-Imran", "An-Nisa"],
    "correct_answer_index": 1,
    "explanation": "Surah Al-Fatihah is the opening chapter of the Quran.",
    "reference": "1:1",
    "source_info": "System Gen"
  }
]
```

#### Responses
* **`200 OK`**: Bulk upload completed.
  ```json
  {
    "success": true,
    "message": "Bulk import complete. 1 questions inserted. 0 skipped.",
    "successful_count": 1,
    "skipped_count": 0,
    "failed_count": 0,
    "errors": []
  }
  ```
* **`401 Unauthorized`**: Missing or invalid `X-Admin-Token` header.
  ```json
  {
    "error": "Unauthorized. Invalid or missing X-Admin-Token header."
  }
  ```

*Example cURL:*
```bash
curl -X POST http://localhost:8000/api/upload-questions \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-Admin-Token: your_bulk_api_token_here" \
  -d '[{"type": "PARA", "difficulty": "Easy", "text": "Question Text...", "options": ["A", "B"], "correct_answer_index": 0, "explanation": "Explanation..."}]'
```

---

## Mobile API: Salah Times & Mosque Directory

Served by `public/api/v1/mobile.php?action=...` and backed by `App\Services\MosqueService`.
Authenticated actions need the mobile Sanctum token (`Authorization: Bearer <token>`).
Validation errors return HTTP `422` with `{"status":"error","message":"...","errors":{...}}`.

Times are local 24-hour `HH:MM` strings. Maghrib can instead be stored as
`maghrib_offset_minutes` (minutes after the adhan), which the app resolves against
the day's calculated sunset. Adhan (start) times are calculated on the device.

| Action | Method | Auth | Parameters |
|---|---|---|---|
| `nearby_mosques` | GET | – | `latitude`, `longitude`, `radius_km` (0.5–50, default 10), `search` (name/area; ignores radius), `limit` (≤50), `filters` (comma list of `wheelchair`, `chairs`, `women`, `jumuah`, `parking`), `sect` (one sect key; omit for all) |
| `mosque` | GET | – | `id`, optional `latitude`/`longitude` for distance |
| `mosques_by_ids` | GET | – | `ids` (comma separated, ≤20), optional `latitude`/`longitude` |
| `add_mosque` | POST | ✓ | `name`, `latitude`, `longitude`, optional `locality`, `address`, `city`, `country`, `note`, every time field, and every detail field (below) |
| `update_mosque_times` | POST | ✓ | `mosque_id`, `fajr_time` … `jumuah_time`, `jumuah_time_2`, `maghrib_offset_minutes`, `note` (replaces all times; unchanged times count as a confirmation) |
| `update_mosque_details` | POST | ✓ | `mosque_id` plus any detail fields; only fields sent are changed. A `sect` change from a non-admin is held for review (`"sect_pending": true`) |
| `confirm_mosque_times` | POST | ✓ | `mosque_id` (once per worshipper per 7 days) |
| `report_mosque` | POST | ✓ | `mosque_id`, `reason` (`closed`, `duplicate`, `wrong_location`, `wrong_times`, `inappropriate`, `other`), `note` |
| `salah_favorites` | GET | ✓ | optional `latitude`/`longitude`. Returns `{ "fajr": {mosque}, "dhuhr": {mosque}, ... }` |
| `set_salah_favorite` | POST | ✓ | `prayer` (`fajr`, `dhuhr`, `asr`, `maghrib`, `isha`, `jumuah`), `mosque_id` (blank clears) |
| `my_mosque_contributions` | GET | ✓ | – Returns counts plus `worshippers_benefiting` (others who chose mosques you added or updated) |

**Detail fields** (form-encoded; blank means "not known"):

| Field | Values |
|---|---|
| `place_type` | `masjid` (default), `musalla` |
| `sect` | `deobandi`, `barelvi`, `ahl_e_hadith`, `sunni_other`, `shia_ithna_ashari`, `ismaili`, `bohra`, `shia_other`, or blank for not specified |
| `asr_method` | `hanafi`, `standard`, blank |
| `has_women_area` | `1` / `0` / blank |
| `facilities[<key>]` | `1` / `0` / blank for `wheelchair_entrance`, `accessible_wudu`, `prayer_chairs`, `women_wudu`, `wudu_area`, `toilets`, `car_parking`, `bike_parking`, `air_conditioning` (other keys ignored) |
| `khutbah_languages` | comma list, up to 4 of `ar`, `ur`, `en`, `bn`, `pa`, `ps`, `sd`, `fa`, `tr`, `ms`, `id`, `fr`, `so` |
| `taraweeh_rakats` | `8`, `20`, blank |
| `taraweeh_khatm` | `1` / `0` / blank (whole Quran completed) |
| `eid_year` | e.g. `2027` (defaults to the current year) |
| `eid_fitr_times`, `eid_adha_times` | comma list of up to 4 `HH:MM` jama'ah times; blank removes them |

Mosque objects in responses include `place_type`, `sect`, `sect_change_pending`, `asr_method`,
`jumuah_second_time`, `facilities` (every key, `true`/`false`/`null`), `khutbah_languages`,
`taraweeh` (`{rakats, khatm}`) and `eid_times` (`{"fitr": {"year": 2027, "times": [...]}}`).

`add_mosque` returns `"duplicate": true` with the existing mosque when one with a similar
name is within 100 m (or any mosque within 20 m), so the app can offer to update it instead.
Unverified mosques are hidden for admin review after 5 distinct reports; admins moderate at
`/admin/mosques`.

### Mosques from OpenStreetMap

Mosque locations can be imported from OpenStreetMap, the same data falah.io's Mosque Finder
shows (Overpass query `amenity=place_of_worship` + `religion=muslim`, plus `building=mosque`).

```bash
php artisan mosques:import-osm --city="Karachi" --country="Pakistan" --dry-run   # preview
php artisan mosques:import-osm --city="Karachi" --country="Pakistan"             # import
php artisan mosques:import-osm --lat=24.92 --lng=67.09 --radius=10
php artisan mosques:import-osm --bbox="31.4,74.2,31.7,74.5"
php artisan mosques:import-osm --country="Pakistan"   # large: split into 1° tiles, 1 s apart
php artisan mosques:import-osm --refresh              # re-sync everything already imported
```

Options: `--include-unnamed` (import places OSM has no name for), `--verified`.
Admins can also preview and import a city or a point (≤ 25 km) from **Admin → Mosques → Import from OpenStreetMap**.

Re-running is safe: mosques are matched by OSM id; rows nobody has edited are refreshed from OSM;
rows edited in the app or admin only get empty fields filled; mosques an admin deletes are recorded
in `mosque_import_exclusions` and never re-imported. Jama'ah times never come from OSM.

Mosque objects include `source` (`community` or `osm`), `osm_url`, and `attribution`
(`"© OpenStreetMap contributors"` for any entry using OSM data). OSM data is licensed under the
[Open Database License](https://www.openstreetmap.org/copyright): show the attribution wherever these
entries appear. Set `OSM_USER_AGENT` in `.env` to identify your server to the OSM services, as their
usage policies ask.

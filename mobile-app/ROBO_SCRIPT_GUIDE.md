# Firebase & Google Play Pre-Launch Robo Scripts Guide

This guide provides everything needed to run automated **Robo Tests** on **The Eternal Echo** (`com.asloobulhayat.eternalecho`) using Google Play Console and Firebase Test Lab, and how to resolve the **Predefined text input (`#predefined-text`)** error.

---

## 🔍 Understanding the Predefined Text Error

### Why Does Google Play Console Show This Error?
> *"Robo test searches for EditText fields with an Android resource name that matches a supplied regular expression. If Robo can't find a matching field, it doesn't input your text, but otherwise continues its crawl as usual."*

1. **The Root Cause:**
   Traditional Android apps use XML layouts where input fields have Android resource IDs (e.g., `@+id/email` &rarr; `com.asloobulhayat.eternalecho:id/email`).
   **The Eternal Echo is built with Jetpack Compose** (`OutlinedTextField`), which by default lives in Compose's internal accessibility tree without exposing traditional Android View `resource-id`s to UI Automator / the Robo crawler.
   When Google Play Console's Robo crawler uses its regex matcher (e.g., `.*email.*`), it searches for native Android view resource names and finds none.

2. **The Permanent Code Fix We Applied:**
   - Enabled `testTagsAsResourceId = true` on the root `Surface` semantics in [`MainActivity.kt`](file:///d:/nur-al-quran-and-seerah-daily/mobile-app/app/src/main/java/com/example/eternalechomobile/MainActivity.kt):
     ```kotlin
     Surface(
         modifier = Modifier
             .fillMaxSize()
             .semantics {
                 @OptIn(ExperimentalComposeUiApi::class)
                 testTagsAsResourceId = true
             },
         color = MaterialTheme.colorScheme.background
     )
     ```
   - Assigned explicit Compose `Modifier.testTag(...)` to input fields in [`AuthScreen.kt`](file:///d:/nur-al-quran-and-seerah-daily/mobile-app/app/src/main/java/com/example/eternalechomobile/ui/auth/AuthScreen.kt):
     - Email field: `.testTag("email")` &rarr; exposed as `com.asloobulhayat.eternalecho:id/email`
     - Password field: `.testTag("password")` &rarr; exposed as `com.asloobulhayat.eternalecho:id/password`
     - OTP field: `.testTag("otp")` &rarr; exposed as `com.asloobulhayat.eternalecho:id/otp`
     - Search field: `.testTag("search")` &rarr; exposed as `com.asloobulhayat.eternalecho:id/search`

---

## ⚙️ How to Configure Predefined Text in Google Play Console

In the Google Play Console:
1. Navigate to **Release** > **Testing** > **Pre-launch report** > **Settings**.
2. Scroll to **Test credentials** or **Predefined text**.
3. Set the entries as follows:

| Target Field | Android Resource Name (Regex) | Predefined Input Value |
|---|---|---|
| **Email Address** | `.*email.*` *(or `com.asloobulhayat.eternalecho:id/email`)* | `tester@example.com` |
| **Password** | `.*password.*` *(or `com.asloobulhayat.eternalecho:id/password`)* | `TestPassword123!` |
| **Search Bar** | `.*search.*` *(or `com.asloobulhayat.eternalecho:id/search`)* | `Al-Baqarah` |

Now the Robo crawler's regex engine will find the `resource-id` and populate the fields automatically.

---

## 📁 Available Robo Scripts

In addition to Predefined Text, **Robo Scripts** allow you to script the exact sequence of taps, gestures, and delays across the entire user journey:

| Script Name | Purpose | Recommended Use Case |
|---|---|---|
| [`robo_script.json`](file:///d:/nur-al-quran-and-seerah-daily/mobile-app/robo_script.json) | **Complete Walkthrough**: Steps through all 4 onboarding pages, enters Dashboard, navigates every tab (`Surahs`, `Lens`, `Seerah`, `History`, `Duas`, `Rank`), opens Surah reader, tests announcements, and explores auth. | **Default Pre-Launch Report** on Google Play Console for maximum code coverage. |
| [`robo_script_quick_onboarding.json`](file:///d:/nur-al-quran-and-seerah-daily/mobile-app/play_store_assets/robo_script_quick_onboarding.json) | **Fast Onboarding Skip**: Directly clicks "Skip" and rapidly navigates through all primary bottom navigation tabs. | Rapid smoke testing / fast pre-launch crawler validation. |
| [`robo_script_auth.json`](file:///d:/nur-al-quran-and-seerah-daily/mobile-app/play_store_assets/robo_script_auth.json) | **Authentication Flow**: Bypasses onboarding, navigates to "Sign In", toggles to Password sign-in tab, inputs test credentials, and tests back navigation. | Testing input field stability and auth screen rendering. |

---

## 🚀 Uploading to Google Play Console (Pre-Launch Report)

1. Open the [Google Play Console](https://play.google.com/console).
2. Select your app (**The Eternal Echo**).
3. In the left navigation menu, go to:
   **Release** > **Testing** > **Pre-launch report** > **Settings**.
4. In the **Robo test** settings section:
   - Click **Add Robo script** (or **Upload Robo script**).
   - Select and upload [`robo_script.json`](file:///d:/nur-al-quran-and-seerah-daily/mobile-app/play_store_assets/robo_script.json).
5. Click **Save**.
6. The next time you upload an app bundle (`app-release.aab`), the Pre-launch report crawler will execute your script and properly fill any predefined text fields!

---

## 🧪 Running via Firebase Test Lab CLI (`gcloud`)

```bash
gcloud firebase test android run \
  --type robo \
  --app mobile-app/play_store_assets/app-release.aab \
  --robo-script mobile-app/play_store_assets/robo_script.json \
  --device model=Pixel7,version=33,locale=en,orientation=portrait \
  --timeout 5m
```

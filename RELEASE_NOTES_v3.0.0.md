# Release Notes - The Eternal Echo (v3.0.0)

**Release Version:** `v3.0.0`  
**Android Version Name:** `3.0`  
**Android Version Code:** `8`  
**Release Date:** November 16, 2026  
**Target SDK:** 37 (Android 16) | **Min SDK:** 24 (Android 7.0 Nougat)  
**Binary Artifact:** `mobile-app/app/release/app-release.aab`  

---

## 🌟 Executive Summary

Version 3.0.0 represents a major milestone for **The Eternal Echo** ecosystem. This update brings essential daily Islamic lifestyle tools directly into the palm of your hand: **Astronomical Salah Prayer Times**, **Exact Adhan Reminders**, a **Global Mosque & Jamat Directory** powered by OpenStreetMap, **Quranic Root Word Search**, interactive **Community Feature Surveys**, and streamlined **Sadaqah Jariyah support**.

---

## 🚀 What's New

### 1. 🕌 Salah Times & Accurate Prayer Calculation
* **Astronomical Precision:** Calculates exact daily prayer timings (Fajr, Sunrise, Dhuhr, Asr, Maghrib, Isha) based on your device's geographical coordinates (GPS / network).
* **Global Calculation Conventions:** Full support for major Islamic calculation authorities:
  * University of Islamic Sciences, Karachi
  * Muslim World League (MWL)
  * Islamic Society of North America (ISNA)
  * Umm al-Qura University, Makkah
  * Egyptian General Authority of Survey
  * Institute of Geophysics, University of Tehran
  * Shia Ithna-Ashari (Jafari)
  * Gulf Region
* **Juristic Asr Calculation:** Choose between Hanafi (double shadow length) and Standard (Shafi'i, Maliki, Hanbali).
* **Next Prayer Countdown:** Dynamic badge showing upcoming prayer time, live progress, and 12-hour / 24-hour clock formatting.

### 2. ⏰ Adhan Reminders & Exact Alarm Scheduling
* **Reliable Notifications:** Built using Android's exact alarm scheduling (`SCHEDULE_EXACT_ALARM`) to ensure timely prayer alerts even during Doze mode.
* **Per-Prayer Customization:** Toggle alerts on or off for individual prayers.
* **Resilient Engine:** Automatically reschedules alerts across device reboots (`RECEIVE_BOOT_COMPLETED`), app updates, manual time adjustments, and timezone changes.

### 3. 🗺️ Mosque Directory & Jamat Times
* **OpenStreetMap Integration:** Search and discover nearby masajid within a customizable radius with walking / driving distance.
* **Community Contributions:** Users can add new masajid, submit jamat schedules, update facilities (Wudu area, women's prayer area, parking, wheelchair access), and verify existing times.
* **Favorite Mosque per Prayer:** Pin your preferred local mosque for Fajr, Dhuhr, Asr, Maghrib, Isha, or Jummu'ah.

### 4. 🔍 Quran Root Word Search
* **High-Speed Word Lookup:** Search across Arabic root words and terms throughout the Holy Quran.
* **Ayah & Context Linking:** Inspect occurrence counts, explore contextual verses, and jump directly into the Quranic Lens reader.

### 5. 🗳️ Community Requirement Survey
* **"Help Shape The Eternal Echo":** Built-in interactive survey for both web and Android app users.
* **Direct Feedback Loop:** Vote on planned features, report daily pain points, and suggest enhancements to guide ongoing development.

### 6. 🔗 Deep Linking (`eternalecho://`)
* Native deep links for instant routing:
  * `eternalecho://salah` or `eternalecho://prayer-times` -> Opens Salah Dashboard
  * `eternalecho://lens/{surah}/{verse}` -> Direct verse analysis
  * `eternalecho://quiz` -> Daily knowledge quiz
  * `eternalecho://leaderboard` -> Global engagement leaderboard

### 7. 💳 Streamlined Sadaqah Jariyah Support
* **Whop Integration:** Instant, secure global checkout for credit/debit card support.
* **Sustaining the Mission:** Direct contribution pipeline to help fund server operations, API maintenance, and feature development.

### 8. 🛡️ System & Architecture Improvements
* **Modern Android Stack:** Upgraded compilation and target SDK to Android 37.
* **Database Integrity:** Enforced InnoDB storage engine defaults across database configurations.
* **Comprehensive Test Suite:** Added end-to-end feature tests for mosque import, analytics tracking, prayer reminder scheduling, and donation workflows.

---

## 📦 Release Assets & Artifacts
* **Android App Bundle (AAB):** `mobile-app/app/release/app-release.aab`
* **Package Name:** `com.asloobulhayat.eternalecho`
* **Git Tag:** `v3.0.0`

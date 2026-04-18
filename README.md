<div align="center">
<img width="1200" height="475" alt="GHBanner" src="https://github.com/user-attachments/assets/0aa67016-6eaf-458a-adb2-6e31a0763ed6" />

# The Eternal Echo

**An AI-powered daily quiz app for deepening knowledge of the 30 Paras of the Holy Quran and the Seerah (life) of Prophet Muhammad (SAWW).**

[![React](https://img.shields.io/badge/React-19.2-61DAFB?logo=react)](https://react.dev/)
[![Vite](https://img.shields.io/badge/Vite-6.2-646CFF?logo=vite)](https://vitejs.dev/)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.8-3178C6?logo=typescript)](https://www.typescriptlang.org/)
[![Gemini AI](https://img.shields.io/badge/Gemini_AI-Powered-4285F4?logo=google)](https://ai.google.dev/)
[![Express](https://img.shields.io/badge/Express-4.19-000000?logo=express)](https://expressjs.com/)
[![SQLite](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite)](https://www.sqlite.org/)
[![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-CDN-06B6D4?logo=tailwindcss)](https://tailwindcss.com/)

</div>

---

## 📖 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Architecture](#-architecture)
- [Tech Stack](#-tech-stack)
- [Project Structure](#-project-structure)
- [Getting Started](#-getting-started)
- [Environment Variables](#-environment-variables)
- [API Endpoints](#-api-endpoints)
- [Data Models](#-data-models)
- [AI Integration](#-ai-integration)
- [Screens & Navigation](#-screens--navigation)
- [Database Schema](#-database-schema)
- [License](#-license)

---

## 🌟 Overview

**The Eternal Echo** is an interactive educational web application that leverages AI to generate dynamic, never-repeating quiz questions about the Quran and the life of Prophet Muhammad (SAWW). Users authenticate via email OTP verification, take quizzes across three difficulty levels, receive AI-generated insights, and track their spiritual learning journey through detailed analytics.

---

## ✨ Features

### 🕌 Quran Para Quizzes
- Take quizzes on any of the **30 Paras** of the Holy Quran
- **Three difficulty levels**: Easy, Medium, Hard
- **Theme filtering**: Belief in Allah, Stories of Prophets, Guidance for Daily Life, Hereafter
- **10 AI-generated questions** per quiz session with Quranic verse references (Surah & Ayat)

### 📿 Seerah Journey
- **Seerah Quizzes**: Structured quizzes about the Prophet's life (SAWW)
- **Quick Daily Insights**: AI-generated short reflections with an accompanying quiz question
- **Seerah themes**: Early Life, The Revelation, Persecution in Makkah, The Hijrah, Life in Madinah

### 📜 Quranic History
- Educational insights about the revelation, preservation, and compilation of the Quran
- Difficulty-leveled historical content with interactive questions

### 🔐 Authentication
- **Email-based OTP verification** with 5-minute expiry
- Real email delivery via **Nodemailer** (Gmail SMTP), with mock fallback for development
- Session persistence via `localStorage`

### 📊 Analytics & Progress Tracking
- **Accuracy percentage** and total score tracking
- **Pie chart** visualization of correct answers by difficulty level (via Recharts)
- **Spiritual Level** system: Novice → Aspirant → Knowledge Seeker
- **Quiz History** with per-question review (correct vs. user answers)
- **Completed Paras** counter out of 30

### 🔖 Bookmarks
- Save any question for later review
- View saved questions with correct answers and explanations
- Remove bookmarks at any time

### 📤 Share & Social
- Share individual questions via Web Share API or clipboard copy
- AI-generated **spiritual welcome message** on login

### 👤 Profile & Settings
- Customizable display name
- Database connection status indicator
- Growth summary dashboard with all key metrics
- Sign out functionality

---

## 🏗 Architecture

```
┌─────────────────────────────────────────────────────────┐
│                      Browser (Client)                    │
│                                                          │
│  ┌──────────┐  ┌────────────┐  ┌───────────────────┐   │
│  │ App.tsx  │──│ Components │  │ Services           │   │
│  │ (Router) │  │ Layout.tsx │  │ ├─ geminiService   │   │
│  │          │  │ QuizCard   │  │ │  (Gemini AI API) │   │
│  │ 9 Views  │  │            │  │ └─ databaseService │   │
│  └──────────┘  └────────────┘  │    (REST client)   │   │
│                                 └─────────┬─────────┘   │
└─────────────────────────────────────────────────────────┘
                                            │ HTTP
                                            ▼
┌─────────────────────────────────────────────────────────┐
│                   Backend Server (Express)                │
│                                                          │
│  ┌──────────────┐  ┌────────────┐  ┌──────────────┐    │
│  │ Auth Routes  │  │ User Sync  │  │ Quiz Save    │    │
│  │ OTP Request  │  │ Upsert     │  │              │    │
│  │ OTP Verify   │  │ Load       │  │              │    │
│  └──────┬───────┘  └──────┬─────┘  └──────┬───────┘    │
│         │                 │               │              │
│         ▼                 ▼               ▼              │
│  ┌──────────────┐  ┌─────────────────────────────┐      │
│  │  Nodemailer  │  │  SQLite (database.sqlite)   │      │
│  │  (Gmail)     │  │  Tables: users, quizzes     │      │
│  └──────────────┘  └─────────────────────────────┘      │
└─────────────────────────────────────────────────────────┘
```

---

## 🛠 Tech Stack

| Layer        | Technology                         | Purpose                              |
|:-------------|:-----------------------------------|:-------------------------------------|
| **Frontend** | React 19.2 + TypeScript 5.8        | UI framework & type safety           |
| **Bundler**  | Vite 6.2                           | Dev server & production build        |
| **Styling**  | Tailwind CSS (CDN) + Custom CSS    | Utility-first styling & animations   |
| **Charts**   | Recharts 3.7                       | Pie chart & bar chart analytics      |
| **AI**       | Google Gemini (`gemini-3-flash-preview`) | Dynamic quiz & content generation |
| **Backend**  | Express 4.19 + Node.js             | REST API server                      |
| **Database** | SQLite 3 (via `sqlite3` npm)       | Persistent user & quiz storage       |
| **Email**    | Nodemailer (Gmail SMTP)            | OTP delivery                         |
| **Fonts**    | Inter + Amiri (Google Fonts)       | UI + Arabic typography               |

---

## 📁 Project Structure

```
the-eternal-echo/
├── index.html              # Entry HTML with Tailwind CDN, import maps, custom CSS
├── index.tsx               # React DOM root mount point
├── App.tsx                 # Main application component (all views, state, logic)
├── types.ts                # TypeScript interfaces & enums
├── vite.config.ts          # Vite configuration (port 3000, env, aliases)
├── tsconfig.json           # TypeScript compiler options (ES2022, React JSX)
├── package.json            # Frontend dependencies & scripts
├── .env.local              # Environment variables (GEMINI_API_KEY)
├── .gitignore              # Git ignore rules
├── metadata.json           # App metadata (name, description)
├── database.sqlite         # SQLite database file (auto-created by server)
│
├── components/
│   ├── Layout.tsx           # App shell: header, footer, desktop & mobile navigation
│   └── QuizCard.tsx         # Reusable quiz question card with share/bookmark
│
├── services/
│   ├── geminiService.ts     # Google Gemini AI API integration (5 functions)
│   └── databaseService.ts   # REST API client for backend communication
│
└── server/
    ├── index.js             # Express server (auth, user sync, quiz save)
    ├── package.json         # Server dependencies
    └── node_modules/        # Server dependencies
```

---

## 🚀 Getting Started

### Prerequisites

- **Node.js** (v18 or later recommended)
- **Google Gemini API Key** ([Get one here](https://aistudio.google.com/apikey))
- *(Optional)* Gmail App Password for real OTP emails

### 1. Clone the Repository

```bash
git clone https://github.com/your-username/nur-al-quran-and-seerah-daily.git
cd nur-al-quran-and-seerah-daily
```

### 2. Install Frontend Dependencies

```bash
npm install
```

### 3. Install Backend Dependencies

```bash
cd server
npm install
cd ..
```

### 4. Configure Environment Variables

Create a `.env.local` file in the project root:

```env
GEMINI_API_KEY=your_gemini_api_key_here
```

Create a `.env` file inside the `server/` directory (optional, for real emails):

```env
PORT=5000
EMAIL_USER=your_gmail_address@gmail.com
EMAIL_PASS=your_gmail_app_password
```

> **Note:** If `EMAIL_USER` and `EMAIL_PASS` are not set, the server will log OTPs to the console (mock mode) instead of sending real emails.

### 5. Start the Backend Server

```bash
cd server
npm start
```

The server will start on `http://localhost:5000`.

### 6. Start the Frontend Dev Server

In a separate terminal:

```bash
npm run dev
```

The app will be available at `http://localhost:3000`.

---

## 🔑 Environment Variables

| Variable         | Location      | Required | Description                          |
|:-----------------|:--------------|:---------|:-------------------------------------|
| `GEMINI_API_KEY` | `.env.local`  | ✅ Yes    | Google Gemini API key for AI content |
| `PORT`           | `server/.env` | ❌ No     | Server port (default: `5000`)        |
| `EMAIL_USER`     | `server/.env` | ❌ No     | Gmail address for sending OTPs       |
| `EMAIL_PASS`     | `server/.env` | ❌ No     | Gmail App Password for SMTP          |

---

## 🔌 API Endpoints

All endpoints are served from `http://localhost:5000/api`.

### Authentication

| Method | Endpoint               | Body                          | Description                          |
|:-------|:-----------------------|:------------------------------|:-------------------------------------|
| POST   | `/auth/otp/request`    | `{ email }`                   | Generates a 6-digit OTP, sends via email (or logs to console). OTP expires in 5 minutes. |
| POST   | `/auth/otp/verify`     | `{ email, otp }`              | Verifies the OTP. Returns `{ message: "Verified", token: "mock-jwt-token" }`. |

### User Data

| Method | Endpoint               | Body / Params                 | Description                          |
|:-------|:-----------------------|:------------------------------|:-------------------------------------|
| POST   | `/user/sync`           | `{ userEmail, stats }`        | Upserts user stats (creates new or updates existing). Stats are stored as JSON. |
| GET    | `/user/:email`         | URL param: `email`            | Loads user stats. Returns `404` if not found. |

### Quiz Data

| Method | Endpoint               | Body                                                      | Description                          |
|:-------|:-----------------------|:----------------------------------------------------------|:-------------------------------------|
| POST   | `/quiz/save`           | `{ id, userEmail, type, score, totalQuestions, difficulty, timestamp, details }` | Saves a completed quiz session to the database. |

---

## 📦 Data Models

Defined in [`types.ts`](types.ts):

### Enums

```typescript
enum AppState {
  LOGIN, HOME, QUIZ, SEERAH, SEERAH_QUIZ_CONFIG,
  STATS, PROFILE, QURAN_HISTORY, BOOKMARKS, HISTORY
}

enum DifficultyLevel {
  EASY = 'Easy',
  MEDIUM = 'Medium',
  HARD = 'Hard'
}
```

### Core Interfaces

| Interface            | Key Fields                                                                                      |
|:---------------------|:-----------------------------------------------------------------------------------------------|
| `Question`           | `id`, `text`, `options[]`, `correctAnswerIndex`, `explanation`, `difficulty?`, `theme?`, `source?` |
| `QuizSession`        | `type` (PARA/SEERAH), `paraNumber?`, `difficulty`, `questions[]`, `currentQuestionIndex`, `score`, `answers[]` |
| `SavedQuiz`          | `id`, `timestamp`, `type`, `title`, `difficulty`, `score`, `totalQuestions`, `questions[]`, `userAnswers[]` |
| `SeerahHistoryItem`  | `id`, `title`, `content`, `question`, `userAnswerIndex`, `timestamp`                          |
| `BookmarkedQuestion` | `id`, `question`, `sourceInfo`, `timestamp`                                                    |
| `UserStats`          | `userEmail`, `displayName`, `completedParas[]`, `paraMastery`, `totalScore`, `totalQuestions`, `seerahReadCount`, `quranHistoryReadCount`, `seerahQuizCount`, `seerahQuizBestScore`, `seerahHistory[]`, `bookmarkedQuestions[]`, `quizHistory[]`, `difficultyStats`, `dbConfig` |

---

## 🤖 AI Integration

Located in [`services/geminiService.ts`](services/geminiService.ts). Uses the **Google Gemini API** (`@google/genai` SDK) with structured JSON output schemas.

| Function                      | Model                     | Description                                              |
|:------------------------------|:--------------------------|:---------------------------------------------------------|
| `generateSpiritualWelcome()`  | `gemini-3-flash-preview`  | One-sentence spiritual welcome message for login         |
| `generateParaQuestions()`     | `gemini-3-flash-preview`  | 20 MCQs about a specific Quran Para with verse refs      |
| `generateSeerahQuizQuestions()` | `gemini-3-flash-preview` | 20 MCQs about the Seerah with historical event refs      |
| `generateSeerahInsight()`     | `gemini-3-flash-preview`  | Single insight + 1 MCQ from the Seerah                   |
| `generateQuranHistoryInsight()` | `gemini-3-flash-preview` | Single insight + 1 MCQ about Quranic history             |

All quiz-generating functions return **structured JSON** via Gemini's `responseSchema` feature, ensuring type-safe output with randomized correct answer indices.

---

## 📱 Screens & Navigation

The app uses a **state-based routing** system (via `AppState` enum) with a responsive layout:
- **Desktop**: Top navigation bar with 5 tabs
- **Mobile**: Fixed bottom tab bar navigation

| Screen            | State Enum       | Description                                           |
|:------------------|:-----------------|:------------------------------------------------------|
| **Login**         | `LOGIN`          | Email input → OTP verification → AI welcome message  |
| **Home**          | `HOME`           | Para grid (1–30), Seerah quiz launchers, Quranic History section |
| **Quiz**          | `QUIZ`           | Active quiz session with progress bar, question cards  |
| **Seerah**        | `SEERAH`         | Daily Seerah insight with reflection question          |
| **Quranic History** | `QURAN_HISTORY` | Historical insight with quiz question                 |
| **Stats**         | `STATS`          | Pie charts, accuracy %, paras completed, insights count |
| **History**       | `HISTORY`        | Past quiz sessions with per-question review cards      |
| **Bookmarks**     | `BOOKMARKS`      | Saved questions grid with explanations                 |
| **Profile**       | `PROFILE`        | User settings, growth summary, DB status, spiritual level |

---

## 🗄 Database Schema

SQLite database (`database.sqlite`) with two tables:

### `users` Table

| Column         | Type | Description                        |
|:---------------|:-----|:-----------------------------------|
| `email`        | TEXT | Primary key                        |
| `display_name` | TEXT | User's display name                |
| `stats`        | TEXT | JSON-stringified `UserStats` object |

### `quizzes` Table

| Column           | Type    | Description                        |
|:-----------------|:--------|:-----------------------------------|
| `id`             | TEXT    | Primary key (generated client-side) |
| `user_email`     | TEXT    | Foreign reference to user          |
| `type`           | TEXT    | `PARA` or `SEERAH`                |
| `score`          | INTEGER | Number of correct answers          |
| `total_questions` | INTEGER | Total number of questions          |
| `difficulty`     | TEXT    | Easy, Medium, or Hard              |
| `timestamp`      | INTEGER | Unix timestamp (ms)                |
| `details`        | TEXT    | JSON-stringified full quiz data    |

---

## 📜 License

© 2024 The Eternal Echo. All rights reserved.

# LifeGPT 🧠

### AI-Powered Collective Wisdom Platform

> **“Your life has answers someone else needs.”**
> *Powered by people. Organized by AI.*

LifeGPT is an experimental **FiftyIsNifty** platform that uses AI to interview people about their life experiences, lessons, decisions, achievements, regrets, and advice. Approved and anonymized experiences are organized into a knowledge base for the future **Ask LifeGPT** RAG experience.

## ✨ Features

* 🤖 AI-powered conversational interviews
* 🎭 5 interviewer personalities
* 🎤 Voice input with browser speech capabilities
* ⌨️ Text input with voice fallback
* 👤 Anonymous and registered-user interviews
* 💾 Save, pause, resume, edit, and delete interviews
* 📝 AI-generated summaries, themes, lessons, and quotes
* 🔐 Granular consent and attribution controls
* 👨‍💼 Admin moderation and knowledge approval
* 🔎 Ask LifeGPT — limited RAG prototype

## 🛠️ Tech Stack

* **Backend:** PHP 8.x
* **Database:** MySQL
* **Frontend:** HTML, CSS, JavaScript
* **AI:** OpenAI API
* **Voice:** Browser Speech Recognition & Speech Synthesis
* **Environment:** XAMPP / Apache

## 📁 Project Structure

```text
lifegpt/
├── interview/      # Interview experience
├── ask/            # Ask LifeGPT
├── account/        # Authentication & profile
├── dashboard/      # User dashboard
├── admin/          # Admin & moderation
├── api/             # Backend API endpoints
├── includes/        # Core PHP services
├── assets/          # CSS & JavaScript
└── cron/            # Background tasks
```

## 🚀 Local Setup

### 1. Clone the project

```bash
git clone <repository-url>
cd lifegpt
```

### 2. XAMPP

Place the project in:

```text
C:\xampp\htdocs\lifegpt
```

Start **Apache** and **MySQL** from XAMPP.

### 3. Database

Create the MySQL database and import the project's SQL migrations/seed data.

### 4. Configuration

Configure your database credentials and OpenAI API key in the environment/configuration file.

> **Never commit API keys, passwords, or other secrets to Git.**

### 5. Run

Open:

```text
http://localhost/lifegpt/
```

## 🔐 Privacy & Security

LifeGPT is designed with contributor control and privacy in mind:

* Consent is granular, versioned, and withdrawable.
* Only contributor-approved transcripts are stored.
* Raw audio is not stored in the MVP.
* Admin approval is required before content enters RAG.
* Deleted or withdrawn content must be excluded from retrieval.
* PDO prepared statements and CSRF protection are required.

## 🎯 Core Principle

**The wisdom comes from people. AI helps ask better questions, organize the answers, preserve contributor control, and make relevant experiences easier to discover.**

## ⚠️ Disclaimer

LifeGPT is an experimental platform. Its responses represent individual lived experiences and should not be treated as professional medical, legal, financial, or mental-health advice.
LifeGPT is an experimental platform. Its responses represent individual lived experiences and **should not be treated as professional medical, legal, financial, or mental-health advice**.

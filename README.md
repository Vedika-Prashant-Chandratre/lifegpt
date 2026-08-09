# LifeGPT - AI-Powered Collective Wisdom Platform

LifeGPT is a web application designed to interview individuals (primarily adults aged 50+) about their life lessons, turning points, achievements, and funny moments. The wisdom collected is summarized by AI and prepared for an anonymized search experience.

This project is built using native **PHP 8.x**, **MySQL**, **Vanilla JavaScript**, and browser speech APIs, running locally on **XAMPP**.

---

## Features Implemented (Starting Part)

1. **Database & Configuration Setup**:
   - Environment loader using `.env` files.
   - PDO database connection manager supporting transactions, parameters, and friendly error handlers.
   - Initial database migrations (`lg_` prefix) and seed files for personas and topics.
   - Automatic database setup script (`database/setup_db.php`) and administrator seeder (`database/seeds/seed_admin.php`).
2. **Accessible Styling & Layout**:
   - Styling (`assets/css/lifegpt.css`) matching high-fidelity Figma specs.
   - Optimizations for older adults: Inter/Outfit fonts, high contrast, visible focus outlines, and touch-target padding (minimum `48px` height).
   - Responsive design covering desktop, tablet, and mobile views.
   - Shared responsive header navigation and footer components.
3. **Static & Information Pages**:
   - Conversational Homepage (`index.php`).
   - Detailed guide page (`how-it-works.php`).
   - Policies (`privacy.php`, `terms.php`).
4. **Granular Privacy & Consent**:
   - Versioned consent choices (`interview/consent.php`) mapping storage, search/RAG, and quotes.
   - Multiple attribution choices (Anonymous, First Name, Nickname, Full Name, Private).
5. **Robust Authentication**:
   - Optional registration (`account/register.php`), log in (`login.php`), and logout (`logout.php`).
   - Mock verification flows for email verification (`verify-email.php`) and password resets (`forgot-password.php`, `reset-password.php`).
   - User settings profile panel with password update and account deletion (`account/profile.php`).
6. **Interviewer Setup Flow**:
   - Welcoming start page (`interview/start.php`).
   - Persona selection grid (`interview/choose-persona.php`) displaying the 5 seeded personalities.
   - Topic and length selectors (`interview/choose-topic.php`).
7. **Interactive Conversation Screen**:
   - Speech UI layout (`interview/conversation.php`).
   - Client JS Speech Recognition (`assets/js/speech-recognition.js`) for browser transcribing.
   - Client JS Speech Synthesis (`assets/js/speech-synthesis.js`) for voice output.
   - Main controller (`assets/js/interview.js`) handling progress bars, dialog, and voice review prompts ("Use, Edit, Try Again").
8. **Interviewer & Summary Engine**:
   - Server-side context builder and OpenAI API client (`includes/openai.php`, `includes/interview-engine.php`).
   - **Local Fallback Mode**: If no OpenAI API key is present in `.env`, the system automatically activates a local question-bank engine that serves logical follow-up questions tailored to your chosen topic, alongside a dynamic summary compiler. This lets you test the entire conversation flow offline.
9. **Form API Endpoints**:
   - `api/interview-next-question.php`, `api/interview-save-answer.php`, `api/interview-complete.php`, and `api/interview-summary.php` are fully implemented with authorization checks and CSRF validation.

---

## Local Setup & Installation

Follow these steps to run LifeGPT on XAMPP:

### 1. Project Location
Copy the project folder into your Apache document root:
```
C:\xampp\htdocs\lifegpt\
```

### 2. Startup XAMPP Services
Open the **XAMPP Control Panel** and start:
1. **Apache**
2. **MySQL**

### 3. Initialize Database
You can set up the database and import migrations/seeds in one step. Open your browser and navigate to:
```
http://localhost/lifegpt/database/setup_db.php
```
*Alternatively, you can run this script via your terminal command:*
```bash
C:\xampp\php\php.exe C:\xampp\htdocs\lifegpt\database\setup_db.php
```

### 4. Create Default Administrator Account
Run the administrator user seeder script by navigating to:
```
http://localhost/lifegpt/database/seeds/seed_admin.php
```
*Or run it via command line:*
```bash
C:\xampp\php\php.exe C:\xampp\htdocs\lifegpt\database\seeds\seed_admin.php
```
- **Admin Email**: `admin@lifegpt.local`
- **Admin Password**: `adminpassword123`

### 5. Access the Platform
Open your browser and visit:
```
http://localhost/lifegpt/
```

---

## Configuration (`.env`)

You can edit database credentials and set up your OpenAI API key in:
`C:\xampp\htdocs\lifegpt\.env`

```ini
APP_ENV=local
APP_URL=http://localhost/lifegpt

# Database settings (Default XAMPP)
DB_HOST=localhost
DB_PORT=3306
DB_NAME=lifegpt
DB_USER=root
DB_PASS=

# OpenAI API (Optional for offline mock mode)
OPENAI_API_KEY=your_openai_api_key_here
```

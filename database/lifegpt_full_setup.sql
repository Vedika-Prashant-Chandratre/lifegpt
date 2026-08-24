-- ============================================================
-- LifeGPT Full Database Setup Script
-- Run this ONCE on your cloud MySQL database (Aiven, Clever Cloud, etc.)
-- Includes: all 16 tables + required seed data
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ============================================================
-- TABLE 1: lg_users
-- ============================================================
DROP TABLE IF EXISTS lg_users;
CREATE TABLE lg_users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    uuid          CHAR(36)     UNIQUE NOT NULL,
    email         VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    username      VARCHAR(50)  NULL,
    display_name  VARCHAR(100) NOT NULL,
    age           INT          NULL,
    country       VARCHAR(100) NULL,
    profession    VARCHAR(100) NULL,
    gender        VARCHAR(30)  NULL,
    about_me      TEXT         NULL,
    role          ENUM('member', 'admin') DEFAULT 'member',
    status        ENUM('pending', 'active', 'suspended') DEFAULT 'active',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_uuid  (uuid),
    INDEX idx_user_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 2: lg_password_resets
-- ============================================================
DROP TABLE IF EXISTS lg_password_resets;
CREATE TABLE lg_password_resets (
    reset_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT        NOT NULL,
    token_hash CHAR(64)   UNIQUE NOT NULL,
    expires_at DATETIME   NOT NULL,
    used_at    DATETIME   NULL,
    FOREIGN KEY (user_id) REFERENCES lg_users(user_id) ON DELETE CASCADE,
    INDEX idx_reset_token (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 3: lg_email_verifications
-- ============================================================
DROP TABLE IF EXISTS lg_email_verifications;
CREATE TABLE lg_email_verifications (
    verification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT      NOT NULL,
    token_hash      CHAR(64) UNIQUE NOT NULL,
    expires_at      DATETIME NOT NULL,
    verified_at     DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES lg_users(user_id) ON DELETE CASCADE,
    INDEX idx_verification_token (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 4: lg_interviewer_personas
-- ============================================================
DROP TABLE IF EXISTS lg_interviewer_personas;
CREATE TABLE lg_interviewer_personas (
    persona_id    INT AUTO_INCREMENT PRIMARY KEY,
    persona_key   VARCHAR(50)  UNIQUE NOT NULL,
    name          VARCHAR(100) NOT NULL,
    avatar        VARCHAR(255) NOT NULL,
    greeting      TEXT         NOT NULL,
    system_prompt TEXT         NOT NULL,
    voice_settings JSON        NULL,
    active        TINYINT(1)   DEFAULT 1,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_persona_key (persona_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 5: lg_interview_topics
-- ============================================================
DROP TABLE IF EXISTS lg_interview_topics;
CREATE TABLE lg_interview_topics (
    topic_id       INT AUTO_INCREMENT PRIMARY KEY,
    topic_key      VARCHAR(50)  UNIQUE NOT NULL,
    name           VARCHAR(100) NOT NULL,
    starter_prompt TEXT         NOT NULL,
    active         TINYINT(1)   DEFAULT 1,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_topic_key (topic_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 6: lg_interviews
-- ============================================================
DROP TABLE IF EXISTS lg_interviews;
CREATE TABLE lg_interviews (
    interview_id  INT AUTO_INCREMENT PRIMARY KEY,
    uuid          CHAR(36)     UNIQUE NOT NULL,
    user_id       INT          NULL,
    persona_id    INT          NOT NULL,
    topic_id      INT          NOT NULL,
    status        ENUM('in_progress', 'completed', 'deleted') DEFAULT 'in_progress',
    language      VARCHAR(10)  DEFAULT 'en',
    input_mode    ENUM('voice', 'typing', 'mixed') DEFAULT 'mixed',
    duration_type ENUM('quick', 'standard', 'deep') DEFAULT 'standard',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES lg_users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (persona_id) REFERENCES lg_interviewer_personas(persona_id),
    FOREIGN KEY (topic_id)   REFERENCES lg_interview_topics(topic_id),
    INDEX idx_interview_uuid (uuid),
    INDEX idx_interview_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 7: lg_interview_messages
-- ============================================================
DROP TABLE IF EXISTS lg_interview_messages;
CREATE TABLE lg_interview_messages (
    message_id    INT AUTO_INCREMENT PRIMARY KEY,
    interview_id  INT  NOT NULL,
    sequence      INT  NOT NULL,
    role          ENUM('interviewer', 'contributor') NOT NULL,
    text          TEXT NOT NULL,
    input_method  ENUM('voice', 'typing') NULL,
    question_type VARCHAR(50) NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (interview_id) REFERENCES lg_interviews(interview_id) ON DELETE CASCADE,
    INDEX idx_message_interview (interview_id, sequence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 8: lg_interview_summaries
-- ============================================================
DROP TABLE IF EXISTS lg_interview_summaries;
CREATE TABLE lg_interview_summaries (
    summary_id           INT AUTO_INCREMENT PRIMARY KEY,
    interview_id         INT  NOT NULL,
    story_summary        TEXT NOT NULL,
    main_lesson          TEXT NOT NULL,
    turning_point        TEXT NOT NULL,
    outcome              TEXT NOT NULL,
    advice               TEXT NOT NULL,
    funny_moment         TEXT NULL,
    representative_quote TEXT NOT NULL,
    approved_summary     TINYINT(1) DEFAULT 0,
    approved_quote       TINYINT(1) DEFAULT 0,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (interview_id) REFERENCES lg_interviews(interview_id) ON DELETE CASCADE,
    INDEX idx_summary_interview (interview_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 9: lg_themes
-- ============================================================
DROP TABLE IF EXISTS lg_themes;
CREATE TABLE lg_themes (
    theme_id        INT AUTO_INCREMENT PRIMARY KEY,
    theme_name      VARCHAR(100) UNIQUE NOT NULL,
    parent_theme_id INT NULL,
    FOREIGN KEY (parent_theme_id) REFERENCES lg_themes(theme_id) ON DELETE SET NULL,
    INDEX idx_theme_name (theme_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 10: lg_interview_themes
-- ============================================================
DROP TABLE IF EXISTS lg_interview_themes;
CREATE TABLE lg_interview_themes (
    interview_id      INT          NOT NULL,
    theme_id          INT          NOT NULL,
    confidence        DECIMAL(5,2) DEFAULT 0.00,
    user_confirmation TINYINT(1)   DEFAULT 0,
    PRIMARY KEY (interview_id, theme_id),
    FOREIGN KEY (interview_id) REFERENCES lg_interviews(interview_id) ON DELETE CASCADE,
    FOREIGN KEY (theme_id)     REFERENCES lg_themes(theme_id)         ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 11: lg_consents
-- ============================================================
DROP TABLE IF EXISTS lg_consents;
CREATE TABLE lg_consents (
    consent_id          INT AUTO_INCREMENT PRIMARY KEY,
    interview_id        INT  NOT NULL,
    storage_consent     TINYINT(1) DEFAULT 0,
    rag_consent         TINYINT(1) DEFAULT 0,
    quotes_consent      TINYINT(1) DEFAULT 0,
    research_consent    TINYINT(1) DEFAULT 0,
    publication_consent TINYINT(1) DEFAULT 0,
    attribution_type    ENUM('private','anonymous','first_name','full_name','nickname') DEFAULT 'anonymous',
    attribution_value   VARCHAR(100) NULL,
    withdrawn           TINYINT(1) DEFAULT 0,
    withdrawn_at        DATETIME NULL,
    version             INT DEFAULT 1,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (interview_id) REFERENCES lg_interviews(interview_id) ON DELETE CASCADE,
    INDEX idx_consent_interview (interview_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 12: lg_knowledge_chunks  (RAG data store)
-- ============================================================
DROP TABLE IF EXISTS lg_knowledge_chunks;
CREATE TABLE lg_knowledge_chunks (
    chunk_id                 INT AUTO_INCREMENT PRIMARY KEY,
    interview_id             INT  NOT NULL,
    content_type             VARCHAR(50) NOT NULL,
    text                     TEXT NOT NULL,
    anonymized_text          TEXT NOT NULL,
    embedding_reference      TEXT NULL,
    approved_for_rag         TINYINT(1) DEFAULT 0,
    approved_for_publication TINYINT(1) DEFAULT 0,
    status                   ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at               TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at               TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (interview_id) REFERENCES lg_interviews(interview_id) ON DELETE CASCADE,
    INDEX idx_chunk_interview (interview_id),
    FULLTEXT INDEX idx_chunk_fulltext (anonymized_text)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 13: lg_guest_access_tokens
-- ============================================================
DROP TABLE IF EXISTS lg_guest_access_tokens;
CREATE TABLE lg_guest_access_tokens (
    token_id          INT AUTO_INCREMENT PRIMARY KEY,
    interview_id      INT      NOT NULL,
    token_hash        CHAR(64) UNIQUE NOT NULL,
    expiry            DATETIME NOT NULL,
    revocation_status TINYINT(1) DEFAULT 0,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (interview_id) REFERENCES lg_interviews(interview_id) ON DELETE CASCADE,
    INDEX idx_guest_token (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 14: lg_audit_log
-- ============================================================
DROP TABLE IF EXISTS lg_audit_log;
CREATE TABLE lg_audit_log (
    audit_id        INT AUTO_INCREMENT PRIMARY KEY,
    actor           VARCHAR(100) NOT NULL,
    action          VARCHAR(50)  NOT NULL,
    entity          VARCHAR(50)  NOT NULL,
    entity_id       INT          NULL,
    before_metadata TEXT         NULL,
    after_metadata  TEXT         NULL,
    timestamp       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 15: lg_ai_usage
-- ============================================================
DROP TABLE IF EXISTS lg_ai_usage;
CREATE TABLE lg_ai_usage (
    usage_id      INT AUTO_INCREMENT PRIMARY KEY,
    interview_id  INT          NULL,
    request_type  VARCHAR(50)  NOT NULL,
    model         VARCHAR(50)  NOT NULL,
    tokens        INT          DEFAULT 0,
    latency       INT          DEFAULT 0,
    status        VARCHAR(20)  NOT NULL,
    cost_estimate DECIMAL(10,5) DEFAULT 0.00000,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (interview_id) REFERENCES lg_interviews(interview_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 16: lg_feedback
-- ============================================================
DROP TABLE IF EXISTS lg_feedback;
CREATE TABLE lg_feedback (
    feedback_id         INT AUTO_INCREMENT PRIMARY KEY,
    interview_id        INT NULL,
    question_answer_ref VARCHAR(100) NULL,
    rating              TINYINT(1) NOT NULL,
    comment             TEXT NULL,
    timestamp           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (interview_id) REFERENCES lg_interviews(interview_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
-- SEED DATA
-- ============================================================

-- 5 Interviewer Personas
INSERT INTO lg_interviewer_personas (persona_key, name, avatar, greeting, system_prompt, voice_settings, active) VALUES
('grandchild', 'Curious Grandchild', 'grandchild',
 'Hi! I''m so glad we''re talking. What is something life taught you that you wish you had known earlier?',
 'You are the Curious Grandchild. Your tone is warm, personal, gentle, curious, and encouraging. Ask questions as a loving grandchild would, focusing on warm reflections and stories. Keep questions concise and ask only one question at a time. Do not give advice or therapy.',
 '{"rate": 1.0, "pitch": 1.1, "lang": "en-US"}', 1),
('journalist', 'Journalist', 'journalist',
 'Hello. Thank you for sharing your time. Tell me about a decision that changed the direction of your life.',
 'You are the Journalist. Your tone is clear, structured, objective, and neutral. Focus on facts, decisions, actions, and outcomes. Ask structured questions. Keep questions concise and ask only one question at a time.',
 '{"rate": 1.0, "pitch": 1.0, "lang": "en-US"}', 1),
('coach', 'Life Coach', 'coach',
 'Welcome. Let''s talk about your journey. What challenge taught you the most, and how did it change you?',
 'You are the Life Coach. Your tone is supportive, growth-oriented, and action-oriented. Focus on personal growth, overcoming challenges, and practical advice. Keep questions concise and ask only one question at a time.',
 '{"rate": 1.05, "pitch": 1.0, "lang": "en-US"}', 1),
('comedian', 'Comedian', 'comedian',
 'Hey! Thanks for stopping by. What is something about getting older that you can finally laugh about?',
 'You are the Comedian. Your tone is playful, respectful, witty, and warm. Focus on humorous mishaps and lighthearted life lessons. Keep questions concise and ask only one question at a time. Never mock or be mean.',
 '{"rate": 1.1, "pitch": 0.95, "lang": "en-US"}', 1),
('historian', 'Historian', 'historian',
 'Hello. Let''s document your perspective. What everyday part of life today would have seemed impossible when you were younger?',
 'You are the Historian. Your tone is reflective, contextual, and analytical. Focus on technological advancements and cultural shifts. Keep questions concise and ask only one question at a time.',
 '{"rate": 0.95, "pitch": 1.0, "lang": "en-US"}', 1)
ON DUPLICATE KEY UPDATE name=VALUES(name), greeting=VALUES(greeting), system_prompt=VALUES(system_prompt);


-- 12 Interview Topics
INSERT INTO lg_interview_topics (topic_key, name, starter_prompt, active) VALUES
('lesson',        'A lesson I learned',              'Tell me about a specific lesson that life has taught you.', 1),
('differently',   'Something I would do differently', 'If you could go back to a major turning point, what would you do differently and why?', 1),
('advice',        'Advice for younger people',        'What advice would you give to a 20-year-old starting out today?', 1),
('funny',         'A funny life experience',          'Tell me about a funny memory or mishap that still brings a smile to your face.', 1),
('career',        'Career and work',                  'What are some of the most meaningful lessons you learned from your career or working years?', 1),
('relationships', 'Family and relationships',         'What has life taught you about love, family, and maintaining relationships over time?', 1),
('money',         'Money and retirement',             'What wisdom have you gathered about managing money, saving, or transitioning into retirement?', 1),
('health',        'Health and aging',                 'How has your perspective on health, aging, and wellness evolved over the years?', 1),
('technology',    'Technology and how life changed',  'How has technology changed the way we connect, and what do you miss from the old days?', 1),
('turning_point', 'A major turning point',            'What was a major event or decision that defined who you are today?', 1),
('pride',         'Something I am proud of',          'What is one of the achievements or quiet moments in your life that you are most proud of?', 1),
('surprise',      'Surprise me',                      'Ask me anything about my life and experiences.', 1)
ON DUPLICATE KEY UPDATE name=VALUES(name), starter_prompt=VALUES(starter_prompt);


-- 8 Base Themes
INSERT INTO lg_themes (theme_name, parent_theme_id) VALUES
('Career & Work', NULL),
('Love & Family', NULL),
('Humor & Mishaps', NULL),
('Financial Wisdom', NULL),
('Health & Aging', NULL),
('Life Transitions', NULL),
('Regrets & Reflections', NULL),
('Technology & Society', NULL)
ON DUPLICATE KEY UPDATE theme_name=theme_name;

-- ============================================================
-- SETUP COMPLETE. Your LifeGPT database is ready.
-- ============================================================

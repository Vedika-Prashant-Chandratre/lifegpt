-- LifeGPT Initial Database Schema Migration
-- Table Prefix: lg_

-- Create Database if not exists (in case running directly)
-- CREATE DATABASE IF NOT EXISTS lifegpt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE lifegpt;

-- Disable foreign key checks during migration
SET FOREIGN_KEY_CHECKS = 0;

-- 1. lg_users
DROP TABLE IF EXISTS lg_users;
CREATE TABLE lg_users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    username VARCHAR(50) NULL,
    display_name VARCHAR(100) NOT NULL,
    age INT NULL,
    country VARCHAR(100) NULL,
    profession VARCHAR(100) NULL,
    gender VARCHAR(30) NULL,
    about_me TEXT NULL,
    role ENUM('member', 'admin') DEFAULT 'member',
    status ENUM('pending', 'active', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_uuid (uuid),
    INDEX idx_user_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2. lg_password_resets
DROP TABLE IF EXISTS lg_password_resets;
CREATE TABLE lg_password_resets (
    reset_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES lg_users(user_id) ON DELETE CASCADE,
    INDEX idx_reset_token (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. lg_email_verifications
DROP TABLE IF EXISTS lg_email_verifications;
CREATE TABLE lg_email_verifications (
    verification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    verified_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES lg_users(user_id) ON DELETE CASCADE,
    INDEX idx_verification_token (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. lg_interviewer_personas
DROP TABLE IF EXISTS lg_interviewer_personas;
CREATE TABLE lg_interviewer_personas (
    persona_id INT AUTO_INCREMENT PRIMARY KEY,
    persona_key VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    avatar VARCHAR(255) NOT NULL,
    greeting TEXT NOT NULL,
    system_prompt TEXT NOT NULL,
    voice_settings JSON NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_persona_key (persona_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. lg_interview_topics
DROP TABLE IF EXISTS lg_interview_topics;
CREATE TABLE lg_interview_topics (
    topic_id INT AUTO_INCREMENT PRIMARY KEY,
    topic_key VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    starter_prompt TEXT NOT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_topic_key (topic_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. lg_interviews
DROP TABLE IF EXISTS lg_interviews;
CREATE TABLE lg_interviews (
    interview_id INT AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    user_id INT NULL,
    persona_id INT NOT NULL,
    topic_id INT NOT NULL,
    status ENUM('in_progress', 'completed', 'deleted') DEFAULT 'in_progress',
    language VARCHAR(10) DEFAULT 'en',
    input_mode ENUM('voice', 'typing', 'mixed') DEFAULT 'mixed',
    duration_type ENUM('quick', 'standard', 'deep') DEFAULT 'standard',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES lg_users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (persona_id) REFERENCES lg_interviewer_personas(persona_id),
    FOREIGN KEY (topic_id) REFERENCES lg_interview_topics(topic_id),
    INDEX idx_interview_uuid (uuid),
    INDEX idx_interview_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. lg_interview_messages
DROP TABLE IF EXISTS lg_interview_messages;
CREATE TABLE lg_interview_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    interview_id INT NOT NULL,
    sequence INT NOT NULL,
    role ENUM('interviewer', 'contributor') NOT NULL,
    text TEXT NOT NULL,
    input_method ENUM('voice', 'typing') NULL,
    question_type VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (interview_id) REFERENCES lg_interviews(interview_id) ON DELETE CASCADE,
    INDEX idx_message_interview (interview_id, sequence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. lg_interview_summaries
DROP TABLE IF EXISTS lg_interview_summaries;
CREATE TABLE lg_interview_summaries (
    summary_id INT AUTO_INCREMENT PRIMARY KEY,
    interview_id INT NOT NULL,
    story_summary TEXT NOT NULL,
    main_lesson TEXT NOT NULL,
    turning_point TEXT NOT NULL,
    outcome TEXT NOT NULL,
    advice TEXT NOT NULL,
    funny_moment TEXT NULL,
    representative_quote TEXT NOT NULL,
    approved_summary TINYINT(1) DEFAULT 0,
    approved_quote TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (interview_id) REFERENCES lg_interviews(interview_id) ON DELETE CASCADE,
    INDEX idx_summary_interview (interview_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. lg_themes
DROP TABLE IF EXISTS lg_themes;
CREATE TABLE lg_themes (
    theme_id INT AUTO_INCREMENT PRIMARY KEY,
    theme_name VARCHAR(100) UNIQUE NOT NULL,
    parent_theme_id INT NULL,
    FOREIGN KEY (parent_theme_id) REFERENCES lg_themes(theme_id) ON DELETE SET NULL,
    INDEX idx_theme_name (theme_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. lg_interview_themes
DROP TABLE IF EXISTS lg_interview_themes;
CREATE TABLE lg_interview_themes (
    interview_id INT NOT NULL,
    theme_id INT NOT NULL,
    confidence DECIMAL(5,2) DEFAULT 0.00,
    user_confirmation TINYINT(1) DEFAULT 0,
    PRIMARY KEY (interview_id, theme_id),
    FOREIGN KEY (interview_id) REFERENCES lg_interviews(interview_id) ON DELETE CASCADE,
    FOREIGN KEY (theme_id) REFERENCES lg_themes(theme_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. lg_consents
DROP TABLE IF EXISTS lg_consents;
CREATE TABLE lg_consents (
    consent_id INT AUTO_INCREMENT PRIMARY KEY,
    interview_id INT NOT NULL,
    storage_consent TINYINT(1) DEFAULT 0,
    rag_consent TINYINT(1) DEFAULT 0,
    quotes_consent TINYINT(1) DEFAULT 0,
    research_consent TINYINT(1) DEFAULT 0,
    publication_consent TINYINT(1) DEFAULT 0,
    attribution_type ENUM('private', 'anonymous', 'first_name', 'full_name', 'nickname') DEFAULT 'anonymous',
    attribution_value VARCHAR(100) NULL,
    withdrawn TINYINT(1) DEFAULT 0,
    withdrawn_at DATETIME NULL,
    version INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (interview_id) REFERENCES lg_interviews(interview_id) ON DELETE CASCADE,
    INDEX idx_consent_interview (interview_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. lg_knowledge_chunks
DROP TABLE IF EXISTS lg_knowledge_chunks;
CREATE TABLE lg_knowledge_chunks (
    chunk_id INT AUTO_INCREMENT PRIMARY KEY,
    interview_id INT NOT NULL,
    content_type VARCHAR(50) NOT NULL,
    text TEXT NOT NULL,
    anonymized_text TEXT NOT NULL,
    embedding_reference TEXT NULL,
    approved_for_rag TINYINT(1) DEFAULT 0,
    approved_for_publication TINYINT(1) DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (interview_id) REFERENCES lg_interviews(interview_id) ON DELETE CASCADE,
    INDEX idx_chunk_interview (interview_id),
    FULLTEXT INDEX idx_chunk_fulltext (anonymized_text)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Note: MyISAM engine supports Fulltext in older MySQL versions, but InnoDB supports it in modern ones.
-- We specify InnoDB for lg_knowledge_chunks if MySQL is 5.6+, but in general InnoDB fulltext is great.
-- Let's change the engine of lg_knowledge_chunks to InnoDB so that Foreign Keys are fully supported!
ALTER TABLE lg_knowledge_chunks ENGINE = InnoDB;

-- 13. lg_guest_access_tokens
DROP TABLE IF EXISTS lg_guest_access_tokens;
CREATE TABLE lg_guest_access_tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    interview_id INT NOT NULL,
    token_hash CHAR(64) UNIQUE NOT NULL,
    expiry DATETIME NOT NULL,
    revocation_status TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (interview_id) REFERENCES lg_interviews(interview_id) ON DELETE CASCADE,
    INDEX idx_guest_token (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. lg_audit_log
DROP TABLE IF EXISTS lg_audit_log;
CREATE TABLE lg_audit_log (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    actor VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL,
    entity VARCHAR(50) NOT NULL,
    entity_id INT NULL,
    before_metadata TEXT NULL, -- Stored as JSON string
    after_metadata TEXT NULL,  -- Stored as JSON string
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. lg_ai_usage
DROP TABLE IF EXISTS lg_ai_usage;
CREATE TABLE lg_ai_usage (
    usage_id INT AUTO_INCREMENT PRIMARY KEY,
    interview_id INT NULL,
    request_type VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    tokens INT DEFAULT 0,
    latency INT DEFAULT 0,
    status VARCHAR(20) NOT NULL,
    cost_estimate DECIMAL(10,5) DEFAULT 0.00000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (interview_id) REFERENCES lg_interviews(interview_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. lg_feedback
DROP TABLE IF EXISTS lg_feedback;
CREATE TABLE lg_feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    interview_id INT NULL,
    question_answer_ref VARCHAR(100) NULL,
    rating TINYINT(1) NOT NULL,
    comment TEXT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (interview_id) REFERENCES lg_interviews(interview_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enable foreign key checks back
SET FOREIGN_KEY_CHECKS = 1;

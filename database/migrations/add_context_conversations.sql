-- LifeGPT Context-Aware Conversations Database Schema Migration
-- Creates persistent tables for Ask LifeGPT multi-turn conversations and message logs

CREATE TABLE IF NOT EXISTS `lg_ask_conversations` (
  `conversation_id` VARCHAR(64) NOT NULL,
  `user_id` INT NULL DEFAULT NULL,
  `summary` TEXT NULL DEFAULT NULL,
  `topic_label` VARCHAR(255) NULL DEFAULT NULL,
  `message_count` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`conversation_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lg_ask_messages` (
  `message_id` INT NOT NULL AUTO_INCREMENT,
  `conversation_id` VARCHAR(64) NOT NULL,
  `role` ENUM('user', 'assistant') NOT NULL,
  `content` LONGTEXT NOT NULL,
  `contextual_query` TEXT NULL DEFAULT NULL,
  `intent_type` VARCHAR(32) NULL DEFAULT 'STANDALONE',
  `grounding_score` INT NULL DEFAULT NULL,
  `debug_info` LONGTEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  KEY `idx_conversation_id` (`conversation_id`),
  KEY `idx_role` (`role`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_ask_messages_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `lg_ask_conversations` (`conversation_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

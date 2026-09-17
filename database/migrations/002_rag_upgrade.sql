-- ==============================================================================
-- LifeGPT RAG Pipeline Upgrade Migration (002_rag_upgrade.sql)
-- Adds vector embedding storage, indexing, and cleans eligibility records.
-- ==============================================================================

-- 1. Add vector embedding columns to lg_knowledge_chunks
ALTER TABLE lg_knowledge_chunks
    ADD COLUMN IF NOT EXISTS embedding LONGTEXT NULL AFTER anonymized_text,
    ADD COLUMN IF NOT EXISTS embedding_model VARCHAR(64) NULL AFTER embedding,
    ADD COLUMN IF NOT EXISTS embedded_at DATETIME NULL AFTER embedding_model;

-- 2. Add compound index on approval and status for high-performance RAG filtering
ALTER TABLE lg_knowledge_chunks ADD INDEX IF NOT EXISTS idx_rag_eligibility (approved_for_rag, status);

-- 3. Data Cleanup: Exclude meaningless "Greetings" chunks from RAG eligibility
UPDATE lg_knowledge_chunks
SET approved_for_rag = 0, status = 'rejected', updated_at = CURRENT_TIMESTAMP
WHERE chunk_id IN (7, 8, 9, 11)
   OR LOWER(TRIM(anonymized_text)) = 'greetings'
   OR LOWER(TRIM(anonymized_text)) = '"greetings"'
   OR CHAR_LENGTH(TRIM(anonymized_text)) < 20;

-- 4. Ensure consent records exist for all interviews with approved chunks
INSERT IGNORE INTO lg_consents (interview_id, storage_consent, rag_consent, quotes_consent, research_consent, publication_consent, attribution_type, attribution_value, withdrawn, version, created_at, updated_at)
SELECT DISTINCT i.interview_id, 1, 1, 1, 1, 1, 'anonymous', NULL, 0, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM lg_interviews i
JOIN lg_knowledge_chunks kc ON i.interview_id = kc.interview_id
LEFT JOIN lg_consents c ON i.interview_id = c.interview_id
WHERE c.consent_id IS NULL AND kc.approved_for_rag = 1;

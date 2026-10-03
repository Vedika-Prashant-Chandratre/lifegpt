<?php
/**
 * Export all approved RAG knowledge chunks as INSERT SQL statements.
 * Output: database/exports/rag_knowledge_chunks_export.sql
 * Run via: php scripts/export_rag_sql.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$outDir  = __DIR__ . '/../database/exports';
$outFile = $outDir . '/rag_knowledge_chunks_export.sql';

if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$interviews = DB::fetchAll("
    SELECT i.interview_id, i.uuid, i.topic_id, i.persona_id, i.status,
           t.name AS topic_name, p.name AS persona_name
    FROM lg_interviews i
    LEFT JOIN lg_interview_topics t ON i.topic_id = t.topic_id
    LEFT JOIN lg_interviewer_personas p ON i.persona_id = p.persona_id
    WHERE i.status = 'completed'
    ORDER BY i.interview_id ASC
");

$chunks = DB::fetchAll("
    SELECT kc.*
    FROM lg_knowledge_chunks kc
    JOIN lg_interviews i ON kc.interview_id = i.interview_id
    WHERE kc.status = 'approved' AND kc.approved_for_rag = 1
    ORDER BY kc.interview_id ASC, kc.chunk_id ASC
");

$consents = DB::fetchAll("
    SELECT c.* FROM lg_consents c
    JOIN lg_interviews i ON c.interview_id = i.interview_id
    WHERE i.status = 'completed'
    ORDER BY c.interview_id ASC
");

function sqlEscape(string $val): string {
    return addslashes($val);
}

$lines = [];
$lines[] = "-- LifeGPT RAG Knowledge Chunks Export";
$lines[] = "-- Generated: " . date('Y-m-d H:i:s');
$lines[] = "-- Total interviews: " . count($interviews);
$lines[] = "-- Total chunks: " . count($chunks);
$lines[] = "";
$lines[] = "SET FOREIGN_KEY_CHECKS=0;";
$lines[] = "";

// --- Interviews ---
$lines[] = "-- ============================================================";
$lines[] = "-- INTERVIEWS";
$lines[] = "-- ============================================================";
foreach ($interviews as $row) {
    $uuid      = sqlEscape($row['uuid']);
    $topicId   = (int)$row['topic_id'];
    $personaId = (int)$row['persona_id'];
    $status    = sqlEscape($row['status']);
    $lines[]   = "INSERT IGNORE INTO lg_interviews (uuid, topic_id, persona_id, status, created_at, updated_at) VALUES ('{$uuid}', {$topicId}, {$personaId}, '{$status}', NOW(), NOW());";
}

$lines[] = "";
$lines[] = "-- ============================================================";
$lines[] = "-- CONSENTS";
$lines[] = "-- ============================================================";
foreach ($consents as $row) {
    $iid       = "(SELECT interview_id FROM lg_interviews WHERE uuid = (SELECT uuid FROM lg_interviews WHERE interview_id = " . (int)$row['interview_id'] . " LIMIT 1))";
    $lines[]   = "INSERT IGNORE INTO lg_consents (interview_id, rag_consent, storage_consent, attribution_type, withdrawn, created_at) VALUES ({$iid}, {$row['rag_consent']}, {$row['storage_consent']}, '" . sqlEscape($row['attribution_type']) . "', {$row['withdrawn']}, NOW());";
}

$lines[] = "";
$lines[] = "-- ============================================================";
$lines[] = "-- KNOWLEDGE CHUNKS (approved, RAG-ready)";
$lines[] = "-- ============================================================";
foreach ($chunks as $row) {
    $iid       = "(SELECT interview_id FROM lg_interviews WHERE uuid = (SELECT uuid FROM lg_interviews WHERE interview_id = " . (int)$row['interview_id'] . " LIMIT 1))";
    $type      = sqlEscape($row['content_type']);
    $text      = sqlEscape($row['anonymized_text'] ?? '');
    $model     = sqlEscape($row['embedding_model'] ?? 'local-semantic-tfidf-v1');
    $lines[]   = "INSERT IGNORE INTO lg_knowledge_chunks (interview_id, content_type, anonymized_text, embedding_model, status, approved_for_rag, created_at, updated_at) VALUES ({$iid}, '{$type}', '{$text}', '{$model}', 'approved', 1, NOW(), NOW());";
}

$lines[] = "";
$lines[] = "SET FOREIGN_KEY_CHECKS=1;";
$lines[] = "";
$lines[] = "-- After import, regenerate embeddings:";
$lines[] = "-- php scripts/generate_embeddings.php";

file_put_contents($outFile, implode("\n", $lines));

$size = round(filesize($outFile) / 1024, 1);
echo "Exported to: {$outFile}\n";
echo "File size: {$size} KB\n";
echo "Interviews: " . count($interviews) . "\n";
echo "Chunks: " . count($chunks) . "\n";
echo "Consents: " . count($consents) . "\n";

<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/services/RagPipeline.php';

echo "=== RAG DATABASE TOPICS & CHUNKS SUMMARY ===\n\n";

try {
    $totalChunks = DB::fetch("SELECT COUNT(*) as cnt FROM lg_knowledge_chunks WHERE status = 'approved' AND approved_for_rag = 1");
    echo "Total Approved RAG Chunks: " . ($totalChunks['cnt'] ?? 0) . "\n\n";

    $topics = DB::fetchAll("
        SELECT t.topic_id, t.name, t.slug, COUNT(k.chunk_id) as chunk_count 
        FROM lg_interview_topics t 
        LEFT JOIN lg_interviews i ON t.topic_id = i.topic_id 
        LEFT JOIN lg_knowledge_chunks k ON i.interview_id = k.interview_id AND k.status = 'approved' AND k.approved_for_rag = 1
        GROUP BY t.topic_id, t.name, t.slug
        ORDER BY chunk_count DESC
    ");
    
    foreach ($topics as $t) {
        echo sprintf("Topic [%d] %-30s (slug: %-20s): %d chunks\n", $t['topic_id'], $t['name'], $t['slug'], $t['chunk_count']);
    }

    echo "\n=== SAMPLE CHUNKS BY TOPIC ===\n";
    $sampleChunks = DB::fetchAll("
        SELECT t.name as topic_name, k.content_type, SUBSTRING(k.anonymized_text, 1, 100) as snippet
        FROM lg_knowledge_chunks k
        JOIN lg_interviews i ON k.interview_id = i.interview_id
        JOIN lg_interview_topics t ON i.topic_id = t.topic_id
        WHERE k.status = 'approved' AND k.approved_for_rag = 1
        GROUP BY t.name, k.content_type
        ORDER BY t.name, k.content_type
        LIMIT 40
    ");

    foreach ($sampleChunks as $sc) {
        echo sprintf("[%-25s | %-20s]: %s...\n", $sc['topic_name'], $sc['content_type'], str_replace(["\n", "\r"], ' ', $sc['snippet']));
    }

} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}

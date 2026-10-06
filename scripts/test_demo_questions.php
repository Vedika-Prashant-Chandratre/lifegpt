<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/services/RagPipeline.php';

echo "=== RAG DATABASE CHUNKS BREAKDOWN BY TOPIC ===\n\n";

$topics = DB::fetchAll("
    SELECT t.topic_id, t.topic_key, t.name, COUNT(k.chunk_id) as chunk_count 
    FROM lg_interview_topics t 
    LEFT JOIN lg_interviews i ON t.topic_id = i.topic_id 
    LEFT JOIN lg_knowledge_chunks k ON i.interview_id = k.interview_id AND k.status = 'approved' AND k.approved_for_rag = 1
    GROUP BY t.topic_id, t.topic_key, t.name
    ORDER BY chunk_count DESC
");

foreach ($topics as $t) {
    echo sprintf("Topic [%2d] %-35s (key: %-15s): %4d chunks\n", $t['topic_id'], $t['name'], $t['topic_key'], $t['chunk_count']);
}

echo "\n=== TESTING SAMPLE QUERIES ===\n\n";

$testQueries = [
    'Career & Work' => 'What career lessons and work advice do experienced people share?',
    'Family & Relationships' => 'What have people learned about marriage, raising children, and family relationships?',
    'Money & Retirement' => 'What financial lessons, money regrets, and retirement advice do people share?',
    'Health & Wellness' => 'What wisdom do older adults share about staying healthy, active, and coping with illness?',
    'Turning Points & Resilience' => 'How do people navigate major life turning points, career changes, and bounce back from failure?',
    'Humor & Life Mishaps' => 'What funny mishaps, embarrassing moments, and humorous memories do people laugh about later in life?',
    'Starting a Business' => 'What advice do experienced entrepreneurs give about starting a business and taking risks?',
    'Advice for 20s' => 'What is the most important life advice experienced people would give to someone in their twenties?',
    'Grief & Loss' => 'How do people cope with grief, loss of loved ones, and finding strength through hardship?',
    'Life Lessons' => 'What are the biggest lessons people wish they had learned earlier in life?'
];

foreach ($testQueries as $label => $q) {
    echo "--- Testing: {$label} ---\n";
    echo "Query: {$q}\n";
    $res = RagPipeline::ask($q, []);
    echo "Grounding Score: {$res['grounding_score']}%\n";
    echo "Confidence: {$res['confidence_label']}\n";
    echo "Sources Count: " . count($res['sources']) . "\n";
    echo "Retrieval Status: {$res['retrieval_status']}\n";
    echo "Answer Preview: " . substr(strip_tags($res['answer']), 0, 150) . "...\n\n";
}

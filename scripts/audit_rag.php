<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$total = DB::fetch('SELECT COUNT(*) as cnt FROM lg_knowledge_chunks WHERE status="approved" AND approved_for_rag=1');
echo 'Total approved RAG chunks: ' . $total['cnt'] . PHP_EOL;

$topics = DB::fetchAll('SELECT t.name, COUNT(kc.chunk_id) as cnt FROM lg_knowledge_chunks kc JOIN lg_interviews i ON kc.interview_id=i.interview_id LEFT JOIN lg_interview_topics t ON i.topic_id=t.topic_id WHERE kc.status="approved" AND kc.approved_for_rag=1 GROUP BY t.topic_id, t.name ORDER BY cnt DESC');
echo PHP_EOL . 'Chunks per topic:' . PHP_EOL;
foreach ($topics as $t) {
    echo '  ' . ($t['name'] ?? 'Unknown') . ': ' . $t['cnt'] . PHP_EOL;
}

$interviews = DB::fetch('SELECT COUNT(*) as cnt FROM lg_interviews WHERE status="completed"');
echo PHP_EOL . 'Total completed interviews: ' . $interviews['cnt'] . PHP_EOL;

$topicList = DB::fetchAll('SELECT topic_id, name FROM lg_interview_topics WHERE active=1 ORDER BY name');
echo PHP_EOL . 'Available topics (ID => Name):' . PHP_EOL;
foreach ($topicList as $t) {
    echo '  [' . $t['topic_id'] . '] ' . $t['name'] . PHP_EOL;
}

$personaList = DB::fetchAll('SELECT persona_id, name FROM lg_interviewer_personas ORDER BY persona_id');
echo PHP_EOL . 'Available personas (ID => Name):' . PHP_EOL;
foreach ($personaList as $p) {
    echo '  [' . $p['persona_id'] . '] ' . $p['name'] . PHP_EOL;
}

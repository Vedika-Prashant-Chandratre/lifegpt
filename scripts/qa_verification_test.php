<?php
/**
 * LifeGPT Comprehensive QA Test Runner (TP-01 through TP-07)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

echo "=======================================================\n";
echo "       LifeGPT QA INTEGRATION VERIFICATION RUNNER       \n";
echo "=======================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $testId, string $desc, bool $condition, string $detail = '') {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "✅ [PASS] {$testId}: {$desc}\n";
        if ($detail) echo "         → {$detail}\n";
    } else {
        $failCount++;
        echo "❌ [FAIL] {$testId}: {$desc}\n";
        if ($detail) echo "         → ERROR: {$detail}\n";
    }
}

// -----------------------------------------------------------------------------
// TP-01: Unified API - Ask LifeGPT (action=ask)
// -----------------------------------------------------------------------------
echo "\n--- Running TP-01: Ask LifeGPT RAG Query ---\n";
$url = 'http://localhost/lifegpt/api/index.php?action=ask';
$postData = json_encode(['query' => 'How do people deal with career burnout and finding purpose?']);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Requested-With: XMLHttpRequest'
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($resp, true);
$tp1Pass = ($httpCode === 200 && is_array($data) && !empty($data['success']) && !empty($data['answer']));
$score = $data['grounding_score'] ?? 0;
$srcCount = $data['sources_count'] ?? 0;
assertTest('TP-01', 'Unified API ask action returns valid RAG answer and grounding score', $tp1Pass, "HTTP {$httpCode}, Score: {$score}%, Sources: {$srcCount}");

// -----------------------------------------------------------------------------
// TP-02: Privacy & Persona Name Leakage Check
// -----------------------------------------------------------------------------
echo "\n--- Running TP-02: Persona Name Privacy Leakage Audit ---\n";
$answerText = strtolower($data['answer'] ?? '');
$leakDetected = (strpos($answerText, 'linda') !== false || strpos($answerText, 'as an ai interviewer') !== false);
assertTest('TP-02', 'Generated RAG answer contains zero persona name or internal prompt leakage', !$leakDetected, "Answer length: " . strlen($data['answer'] ?? '') . " chars, zero 'Linda' occurrences");

// -----------------------------------------------------------------------------
// TP-03: Story Funnel - Guest Setup & Token Generation
// -----------------------------------------------------------------------------
echo "\n--- Running TP-03: Anonymous Guest Story Setup ---\n";
$uuid = Auth::generateUUID();
$guestToken = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $guestToken);
$expiry = date('Y-m-d H:i:s', strtotime('+30 days'));

$interviewId = DB::insert(
    "INSERT INTO lg_interviews (uuid, user_id, persona_id, topic_id, status, language, input_mode, duration_type) 
     VALUES (:uuid, NULL, 1, 1, 'in_progress', 'en', 'mixed', 'standard')",
    ['uuid' => $uuid]
);

DB::insert(
    "INSERT INTO lg_consents (interview_id, storage_consent, rag_consent, quotes_consent, research_consent, publication_consent, attribution_type, attribution_value) 
     VALUES (:id, 1, 1, 1, 1, 1, 'anonymous', NULL)",
    ['id' => $interviewId]
);

DB::insert(
    "INSERT INTO lg_guest_access_tokens (interview_id, token_hash, expiry) 
     VALUES (:id, :hash, :expiry)",
    ['id' => $interviewId, 'hash' => $tokenHash, 'expiry' => $expiry]
);

$interviewRow = DB::fetch("SELECT * FROM lg_interviews WHERE interview_id = :id", ['id' => $interviewId]);
$tokenRow = DB::fetch("SELECT * FROM lg_guest_access_tokens WHERE interview_id = :id", ['id' => $interviewId]);
$tp3Pass = ($interviewRow && $tokenRow && $interviewRow['status'] === 'in_progress');
assertTest('TP-03', 'Guest story session initialized with hashed token and anonymous consent', $tp3Pass, "Interview ID: {$interviewId}, UUID: {$uuid}");

// -----------------------------------------------------------------------------
// TP-04: Story Funnel - First Question with Dropped Session (Token Auth)
// -----------------------------------------------------------------------------
echo "\n--- Running TP-04: Next Question with Guest Token (Session Self-Healing) ---\n";
$url = 'http://localhost/lifegpt/api/index.php?action=next_question';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'interview_uuid' => $uuid,
    'guest_token'    => $guestToken
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Requested-With: XMLHttpRequest'
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($resp, true);
$tp4Pass = ($httpCode === 200 && is_array($data) && !empty($data['success']) && !empty($data['next_question']));
assertTest('TP-04', 'Next question loads successfully via guest token without session cookie', $tp4Pass, "HTTP {$httpCode}, Question: " . substr($data['next_question'] ?? '', 0, 50) . "...");

// -----------------------------------------------------------------------------
// TP-05: Story Funnel - Save Answer & Progress to Question 2
// -----------------------------------------------------------------------------
echo "\n--- Running TP-05: Save Answer and Progression ---\n";
$url = 'http://localhost/lifegpt/api/index.php?action=save_answer';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'interview_uuid' => $uuid,
    'guest_token'    => $guestToken,
    'answer'         => 'When I turned 48, I left my corporate banking job to teach woodworking to high schoolers.',
    'input_method'   => 'typing'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Requested-With: XMLHttpRequest'
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$saveData = json_decode($resp, true);
$tp5SavePass = ($httpCode === 200 && is_array($saveData) && !empty($saveData['success']));

// Verify question 2 retrieves next
$url = 'http://localhost/lifegpt/api/index.php?action=next_question';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'interview_uuid' => $uuid,
    'guest_token'    => $guestToken
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Requested-With: XMLHttpRequest'
]);
$resp = curl_exec($ch);
$q2Data = json_decode($resp, true);
curl_close($ch);

$tp5ProgPass = ($tp5SavePass && !empty($q2Data['success']) && !empty($q2Data['next_question']));
assertTest('TP-05', 'Contributor answer saved to database and question sequence progresses', $tp5ProgPass, "Seq: " . ($q2Data['question_sequence'] ?? 'unknown'));

// -----------------------------------------------------------------------------
// TP-06: Complete Story & Extract Knowledge Summary
// -----------------------------------------------------------------------------
echo "\n--- Running TP-06: Interview Completion & Knowledge Extraction ---\n";
$url = 'http://localhost/lifegpt/api/index.php?action=complete';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'interview_uuid' => $uuid,
    'guest_token'    => $guestToken
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Requested-With: XMLHttpRequest'
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$compData = json_decode($resp, true);

// Verify DB status
$updatedInterview = DB::fetch("SELECT status FROM lg_interviews WHERE interview_id = :id", ['id' => $interviewId]);
$chunks = DB::fetchAll("SELECT content_type, anonymized_text FROM lg_knowledge_chunks WHERE interview_id = :id", ['id' => $interviewId]);

$tp6Pass = ($httpCode === 200 && !empty($compData['success']) && $updatedInterview['status'] === 'completed' && count($chunks) > 0);
assertTest('TP-06', 'Interview marked completed and summary knowledge chunks synthesized in DB', $tp6Pass, "Status: {$updatedInterview['status']}, Chunks: " . count($chunks));

// -----------------------------------------------------------------------------
// TP-07: Backward Compatibility of Legacy Endpoints
// -----------------------------------------------------------------------------
echo "\n--- Running TP-07: Backward Compatibility of api/interview-next-question.php ---\n";
$url = 'http://localhost/lifegpt/api/interview-next-question.php';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'interview_uuid' => $uuid,
    'guest_token'    => $guestToken
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Requested-With: XMLHttpRequest'
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$legacyData = json_decode($resp, true);
$tp7Pass = ($httpCode === 200 && is_array($legacyData) && isset($legacyData['success']));
assertTest('TP-07', 'Legacy endpoint api/interview-next-question.php forwards seamlessly to router', $tp7Pass, "HTTP {$httpCode}");

echo "\n=======================================================\n";
echo "QA VERIFICATION SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "=======================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);

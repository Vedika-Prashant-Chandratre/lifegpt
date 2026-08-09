<?php
/**
 * LifeGPT API - Complete Interview Session
 * POST JSON request containing interview_uuid
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/interview-engine.php';

// Validate CSRF token in HTTP headers
CSRF::validateRequest();

// Parse JSON request body
$rawBody = file_get_contents('php://input');
$requestData = json_decode($rawBody, true);

$uuid = $requestData['interview_uuid'] ?? '';

if (empty($uuid)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing interview UUID.']);
    exit;
}

try {
    // 1. Fetch Interview details
    $interview = DB::fetch(
        "SELECT i.*, p.system_prompt, p.name as persona_name, t.name as topic_name
         FROM lg_interviews i
         JOIN lg_interviewer_personas p ON i.persona_id = p.persona_id
         JOIN lg_interview_topics t ON i.topic_id = t.topic_id
         WHERE i.uuid = :uuid",
        ['uuid' => $uuid]
    );

    if (!$interview) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Interview session not found.']);
        exit;
    }

    // 2. Validate Ownership
    if ($interview['user_id'] !== null) {
        if (!Auth::isLoggedIn() || (int)$_SESSION['user_id'] !== (int)$interview['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
            exit;
        }
    } else {
        $sessionActiveId = $_SESSION['active_interview_id'] ?? null;
        if ((int)$sessionActiveId !== (int)$interview['interview_id']) {
            // Check guest token
            $guestToken = $_SESSION['guest_return_token'] ?? '';
            if (empty($guestToken)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Unauthorized. Guest token missing.']);
                exit;
            }
            $tokenHash = hash('sha256', $guestToken);
            $tokenRow = DB::fetch(
                "SELECT token_id FROM lg_guest_access_tokens WHERE interview_id = :id AND token_hash = :hash AND revocation_status = 0 AND expiry > NOW()",
                ['id' => $interview['interview_id'], 'hash' => $tokenHash]
            );
            if (!$tokenRow) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Unauthorized. Guest token invalid.']);
                exit;
            }
        }
    }

    // 3. Update status to completed
    DB::query(
        "UPDATE lg_interviews SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE interview_id = :id",
        ['id' => $interview['interview_id']]
    );

    // 4. Generate structured summary
    InterviewEngine::generateSummary($interview);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("api/interview-complete.php failure: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'A system error occurred while generating your story summary.']);
}

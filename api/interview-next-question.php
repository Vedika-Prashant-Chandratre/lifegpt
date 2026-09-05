<?php
/**
 * LifeGPT API - Get Next Question
 * POST JSON request containing interview_uuid
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/interview-engine.php';

// Validate as a JSON AJAX request (serverless-safe, no session dependency)
CSRF::validateAjax();

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
        "SELECT i.*, p.greeting, p.system_prompt, p.name as persona_name, p.avatar as persona_avatar, p.persona_key, t.name as topic_name, t.topic_key
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

    // 2. Validate Ownership (Authorization check)
    // If it's a registered user's interview, they must be logged in and match
    if ($interview['user_id'] !== null) {
        if (!Auth::isLoggedIn() || (int)$_SESSION['user_id'] !== (int)$interview['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized access to this interview.']);
            exit;
        }
    } 
    // If it's a guest interview, check if they hold the active session or guest return token
    else {
        $sessionActiveId = $_SESSION['active_interview_id'] ?? null;
        if ((int)$sessionActiveId !== (int)$interview['interview_id']) {
            // Check guest return token
            $guestToken = $_SESSION['guest_return_token'] ?? '';
            if (empty($guestToken)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Unauthorized access. Guest token missing.']);
                exit;
            }
            
            $tokenHash = hash('sha256', $guestToken);
            $tokenRow = DB::fetch(
                "SELECT token_id FROM lg_guest_access_tokens WHERE interview_id = :id AND token_hash = :hash AND revocation_status = 0 AND expiry > NOW()",
                ['id' => $interview['interview_id'], 'hash' => $tokenHash]
            );
            
            if (!$tokenRow) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Unauthorized access. Guest token invalid or expired.']);
                exit;
            }
        }
    }

    // 3. Retrieve next question from InterviewEngine
    $result = InterviewEngine::getNextQuestion($interview);
    
    echo json_encode($result);

} catch (Exception $e) {
    error_log("api/interview-next-question.php failure: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'A system error occurred while generating the next question.']);
}

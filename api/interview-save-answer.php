<?php
/**
 * LifeGPT API - Save Contributor Answer
 * POST JSON request containing interview_uuid, answer, and input_method
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Validate as a JSON AJAX request (serverless-safe, no session dependency)
CSRF::validateAjax();

// Parse JSON request body
$rawBody = file_get_contents('php://input');
$requestData = json_decode($rawBody, true);

$uuid = $requestData['interview_uuid'] ?? '';
$answer = trim($requestData['answer'] ?? '');
$inputMethod = $requestData['input_method'] ?? 'typing';

if (empty($uuid) || empty($answer)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing interview UUID or response content.']);
    exit;
}

// Enforce valid input methods
if (!in_array($inputMethod, ['voice', 'typing'])) {
    $inputMethod = 'typing';
}

try {
    // 1. Fetch Interview details
    $interview = DB::fetch("SELECT * FROM lg_interviews WHERE uuid = :uuid", ['uuid' => $uuid]);

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

    // 3. Count messages to determine next sequence number
    $countRow = DB::fetch(
        "SELECT COUNT(*) as cnt FROM lg_interview_messages WHERE interview_id = :id",
        ['id' => $interview['interview_id']]
    );
    $nextSequence = (int)$countRow['cnt'] + 1;

    // 4. Save response message
    DB::insert(
        "INSERT INTO lg_interview_messages (interview_id, sequence, role, text, input_method) 
         VALUES (:interview_id, :sequence, 'contributor', :text, :input_method)",
        [
            'interview_id' => $interview['interview_id'],
            'sequence' => $nextSequence,
            'text' => $answer,
            'input_method' => $inputMethod
        ]
    );

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("api/interview-save-answer.php failure: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'A system error occurred while saving your response.']);
}

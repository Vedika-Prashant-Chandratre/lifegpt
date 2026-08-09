<?php
/**
 * LifeGPT API - Retrieve, Update, or Approve Interview Summary
 * GET / POST
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Check if JSON request (AJAX) or traditional Form Post
$isJson = (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) || 
          (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

try {
    // 1. Authenticate Request & Determine Interview UUID
    $uuid = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        CSRF::validateRequest();
        
        if ($isJson) {
            $rawBody = file_get_contents('php://input');
            $requestData = json_decode($rawBody, true);
            $uuid = $requestData['interview_uuid'] ?? '';
        } else {
            $uuid = $_POST['interview_uuid'] ?? '';
        }
    } else {
        // GET Request
        $uuid = $_GET['uuid'] ?? '';
    }

    if (empty($uuid)) {
        // Fallback to active session if none provided
        $uuid = $_SESSION['active_interview_uuid'] ?? '';
    }

    if (empty($uuid)) {
        http_response_code(400);
        respond(['success' => false, 'error' => 'Missing interview reference UUID.'], $isJson);
    }

    // 2. Fetch Interview
    $interview = DB::fetch("SELECT * FROM lg_interviews WHERE uuid = :uuid", ['uuid' => $uuid]);
    if (!$interview) {
        http_response_code(404);
        respond(['success' => false, 'error' => 'Interview session not found.'], $isJson);
    }

    // 3. Authorization Check
    $isAuthorized = false;
    if ($interview['user_id'] !== null) {
        if (Auth::isLoggedIn() && (int)$_SESSION['user_id'] === (int)$interview['user_id']) {
            $isAuthorized = true;
        }
    } else {
        // Guest checks
        $sessionActiveId = $_SESSION['active_interview_id'] ?? null;
        if ((int)$sessionActiveId === (int)$interview['interview_id']) {
            $isAuthorized = true;
        } else {
            // Check GET or POST token
            $guestToken = $_GET['token'] ?? $_POST['token'] ?? $_SESSION['guest_return_token'] ?? '';
            if (!empty($guestToken)) {
                $tokenHash = hash('sha256', $guestToken);
                $tokenRow = DB::fetch(
                    "SELECT token_id FROM lg_guest_access_tokens WHERE interview_id = :id AND token_hash = :hash AND revocation_status = 0 AND expiry > NOW()",
                    ['id' => $interview['interview_id'], 'hash' => $tokenHash]
                );
                if ($tokenRow) {
                    $isAuthorized = true;
                    // Establish active session
                    $_SESSION['active_interview_id'] = $interview['interview_id'];
                    $_SESSION['active_interview_uuid'] = $interview['uuid'];
                    $_SESSION['guest_return_token'] = $guestToken;
                }
            }
        }
    }

    if (!$isAuthorized) {
        http_response_code(403);
        respond(['success' => false, 'error' => 'Access Denied. You do not own this interview.'], $isJson);
    }

    $interviewId = (int)$interview['interview_id'];

    // 4. Handle GET Request: Return Summary JSON details
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $summary = DB::fetch("SELECT * FROM lg_interview_summaries WHERE interview_id = :id", ['id' => $interviewId]);
        $consent = DB::fetch("SELECT * FROM lg_consents WHERE interview_id = :id", ['id' => $interviewId]);
        
        respond([
            'success' => true,
            'interview' => $interview,
            'summary' => $summary,
            'consent' => $consent
        ], $isJson);
    }

    // 5. Handle POST Request: Save edits and finalize approvals
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Collect edits
        $storySummary = trim($_POST['story_summary'] ?? $requestData['story_summary'] ?? '');
        $mainLesson = trim($_POST['main_lesson'] ?? $requestData['main_lesson'] ?? '');
        $turningPoint = trim($_POST['turning_point'] ?? $requestData['turning_point'] ?? '');
        $outcome = trim($_POST['outcome'] ?? $requestData['outcome'] ?? '');
        $advice = trim($_POST['advice'] ?? $requestData['advice'] ?? '');
        $funnyMoment = trim($_POST['funny_moment'] ?? $requestData['funny_moment'] ?? '');
        $representativeQuote = trim($_POST['representative_quote'] ?? $requestData['representative_quote'] ?? '');
        
        // Collect consents
        $rag = isset($_POST['rag_consent']) || !empty($requestData['rag_consent']) ? 1 : 0;
        $quotes = isset($_POST['quotes_consent']) || !empty($requestData['quotes_consent']) ? 1 : 0;
        $research = isset($_POST['research_consent']) || !empty($requestData['research_consent']) ? 1 : 0;
        $publication = isset($_POST['publication_consent']) || !empty($requestData['publication_consent']) ? 1 : 0;
        
        $attributionType = $_POST['attribution_type'] ?? $requestData['attribution_type'] ?? 'anonymous';
        $attributionValue = trim($_POST['attribution_value'] ?? $requestData['attribution_value'] ?? '');

        if (empty($storySummary) || empty($mainLesson) || empty($representativeQuote)) {
            respond(['success' => false, 'error' => 'Summary, main lesson, and representative quote are required fields.'], $isJson);
        }

        try {
            DB::beginTransaction();

            // A. Update Summary Table
            DB::query(
                "UPDATE lg_interview_summaries 
                 SET story_summary = :story_summary, main_lesson = :main_lesson, turning_point = :turning_point,
                     outcome = :outcome, advice = :advice, funny_moment = :funny_moment, representative_quote = :representative_quote,
                     approved_summary = 1, approved_quote = 1, updated_at = CURRENT_TIMESTAMP
                 WHERE interview_id = :id",
                [
                    'story_summary' => $storySummary,
                    'main_lesson' => $mainLesson,
                    'turning_point' => $turningPoint,
                    'outcome' => $outcome,
                    'advice' => $advice,
                    'funny_moment' => !empty($funnyMoment) ? $funnyMoment : null,
                    'representative_quote' => $representativeQuote,
                    'id' => $interviewId
                ]
            );

            // B. Update Consents Table
            DB::query(
                "UPDATE lg_consents 
                 SET rag_consent = :rag, quotes_consent = :quotes, research_consent = :research, publication_consent = :publication,
                     attribution_type = :attribution_type, attribution_value = :attribution_value, withdrawn = 0, updated_at = CURRENT_TIMESTAMP
                 WHERE interview_id = :id",
                [
                    'rag' => $rag,
                    'quotes' => $quotes,
                    'research' => $research,
                    'publication' => $publication,
                    'attribution_type' => $attributionType,
                    'attribution_value' => !empty($attributionValue) ? $attributionValue : null,
                    'id' => $interviewId
                ]
            );

            // C. Populate lg_knowledge_chunks as PENDING (Admin review required)
            // Clean out old pending chunks if they resubmit edits
            DB::query("DELETE FROM lg_knowledge_chunks WHERE interview_id = :id AND status = 'pending'", ['id' => $interviewId]);
            
            $chunks = [
                ['content_type' => 'summary', 'text' => $storySummary],
                ['content_type' => 'lesson', 'text' => $mainLesson],
                ['content_type' => 'turning_point', 'text' => $turningPoint],
                ['content_type' => 'outcome', 'text' => $outcome],
                ['content_type' => 'advice', 'text' => $advice],
                ['content_type' => 'quote', 'text' => $representativeQuote]
            ];
            
            if (!empty($funnyMoment)) {
                $chunks[] = ['content_type' => 'funny_moment', 'text' => $funnyMoment];
            }
            
            foreach ($chunks as $c) {
                if (empty($c['text'])) continue;
                
                // Set default anonymized text (same as text initially, admin can redact)
                DB::insert(
                    "INSERT INTO lg_knowledge_chunks (interview_id, content_type, text, anonymized_text, approved_for_rag, approved_for_publication, status) 
                     VALUES (:interview_id, :type, :text, :anon, 0, 0, 'pending')",
                    [
                        'interview_id' => $interviewId,
                        'type' => $c['content_type'],
                        'text' => $c['text'],
                        'anon' => $c['text']
                    ]
                );
            }

            DB::commit();

            // Clear active interview states from session
            unset($_SESSION['active_interview_id']);
            unset($_SESSION['active_interview_uuid']);

            // D. Redirect or respond
            if ($isJson) {
                respond(['success' => true], $isJson);
            } else {
                $_SESSION['flash_success'] = "Thank you! Your story has been saved and submitted for admin review.";
                if (Auth::isLoggedIn()) {
                    header("Location: " . APP_URL . "/dashboard/");
                } else {
                    // Guests go to a thank you screen
                    header("Location: " . APP_URL . "/interview/summary.php?uuid=" . $uuid . "&saved=1");
                }
                exit;
            }

        } catch (Exception $e) {
            DB::rollBack();
            error_log("Failed finalizing interview summary: " . $e->getMessage());
            respond(['success' => false, 'error' => 'A database error occurred. Summary not saved.'], $isJson);
        }
    }

} catch (Exception $e) {
    error_log("api/interview-summary.php global error: " . $e->getMessage());
    http_response_code(500);
    respond(['success' => false, 'error' => 'System error processing request.'], $isJson);
}

/**
 * Handle response formats dynamically
 */
function respond(array $data, bool $isJson): void {
    if ($isJson) {
        header('Content-Type: application/json');
        echo json_encode($data);
    } else {
        // Fallback for form error rendering
        $_SESSION['flash_error'] = $data['error'] ?? 'An error occurred.';
        header("Location: " . $_SERVER['HTTP_REFERER']);
    }
    exit;
}

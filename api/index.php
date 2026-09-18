<?php
/**
 * LifeGPT - Unified API Controller & Router
 *
 * Supported Actions:
 *   - ask:           RAG-powered conversational wisdom query
 *   - next_question: Progression for interview storytelling
 *   - save_answer:   Store contributor voice/typed answers
 *   - complete:      Finalize interview session & trigger summary extraction
 *   - summary:       Retrieve or update story summary
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/openai.php';
require_once __DIR__ . '/../includes/interview-engine.php';
require_once __DIR__ . '/../includes/services/RagPipeline.php';

// Parse incoming request (supporting JSON body, POST, and GET)
$rawBody = file_get_contents('php://input');
$requestData = [];
if (!empty($rawBody)) {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $requestData = $decoded;
    }
}

$action = $_GET['action'] ?? $requestData['action'] ?? $_POST['action'] ?? '';

// Fallback to route detection if called directly
if (empty($action)) {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, 'interview-next-question') !== false) {
        $action = 'next_question';
    } elseif (strpos($uri, 'interview-save-answer') !== false) {
        $action = 'save_answer';
    } elseif (strpos($uri, 'interview-complete') !== false) {
        $action = 'complete';
    } elseif (strpos($uri, 'interview-summary') !== false) {
        $action = 'summary';
    } elseif (isset($_POST['query']) || isset($requestData['query'])) {
        $action = 'ask';
    }
}

try {
    switch ($action) {

        // =====================================================================
        // Action: Ask LifeGPT (RAG Wisdom Search)
        // =====================================================================
        case 'ask':
            CSRF::validateAjax();

            $query = trim($requestData['query'] ?? $_POST['query'] ?? $_GET['q'] ?? '');
            if (empty($query)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Query string cannot be empty.']);
                exit;
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $chatHistory = $_SESSION['ask_history'] ?? [];
            $result = RagPipeline::ask($query, $chatHistory);

            // Record user and assistant exchanges in session
            $chatHistory[] = [
                'role'    => 'user',
                'content' => $query,
                'time'    => date('g:i A')
            ];

            $chatHistory[] = [
                'role'             => 'assistant',
                'content'          => $result['answer'],
                'grounding_score'  => $result['grounding_score'],
                'confidence_label' => $result['confidence_label'],
                'confidence_color' => $result['confidence_color'],
                'confidence_badge' => $result['confidence_badge'],
                'sources_count'    => $result['sources_count'],
                'sources'          => $result['sources'],
                'retrieval_status' => $result['retrieval_status'],
                'disclaimer'       => $result['disclaimer'],
                'time'             => date('g:i A')
            ];

            $_SESSION['ask_history'] = $chatHistory;

            echo json_encode([
                'success'          => true,
                'answer'           => $result['answer'],
                'grounding_score'  => $result['grounding_score'],
                'confidence_label' => $result['confidence_label'],
                'confidence_color' => $result['confidence_color'],
                'confidence_badge' => $result['confidence_badge'],
                'sources_count'    => $result['sources_count'],
                'sources'          => $result['sources'],
                'retrieval_status' => $result['retrieval_status'],
                'disclaimer'       => $result['disclaimer'],
                'time'             => date('g:i A')
            ]);
            break;

        // =====================================================================
        // Action: Next Question (Story Interview Funnel)
        // =====================================================================
        case 'next_question':
            CSRF::validateAjax();

            $uuid = $requestData['interview_uuid'] ?? $_POST['interview_uuid'] ?? '';
            $guestToken = $requestData['guest_token'] ?? $_POST['guest_token'] ?? null;

            if (empty($uuid)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing interview UUID parameter.']);
                exit;
            }

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

            if (!Auth::validateInterviewAccess($interview, $guestToken)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Unauthorized access. Guest token missing or expired.']);
                exit;
            }

            $result = InterviewEngine::getNextQuestion($interview);
            echo json_encode($result);
            break;

        // =====================================================================
        // Action: Save Answer (Story Interview Funnel)
        // =====================================================================
        case 'save_answer':
            CSRF::validateAjax();

            $uuid = $requestData['interview_uuid'] ?? $_POST['interview_uuid'] ?? '';
            $answer = trim($requestData['answer'] ?? $_POST['answer'] ?? '');
            $inputMethod = $requestData['input_method'] ?? $_POST['input_method'] ?? 'typing';
            $guestToken = $requestData['guest_token'] ?? $_POST['guest_token'] ?? null;

            if (empty($uuid) || empty($answer)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing interview UUID or response text.']);
                exit;
            }

            if (!in_array($inputMethod, ['voice', 'typing'])) {
                $inputMethod = 'typing';
            }

            $interview = DB::fetch("SELECT * FROM lg_interviews WHERE uuid = :uuid", ['uuid' => $uuid]);
            if (!$interview) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Interview session not found.']);
                exit;
            }

            if (!Auth::validateInterviewAccess($interview, $guestToken)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Unauthorized access. Guest token missing or invalid.']);
                exit;
            }

            $countRow = DB::fetch(
                "SELECT COUNT(*) as cnt FROM lg_interview_messages WHERE interview_id = :id",
                ['id' => $interview['interview_id']]
            );
            $nextSequence = (int)$countRow['cnt'] + 1;

            DB::insert(
                "INSERT INTO lg_interview_messages (interview_id, sequence, role, text, input_method) 
                 VALUES (:interview_id, :sequence, 'contributor', :text, :input_method)",
                [
                    'interview_id' => $interview['interview_id'],
                    'sequence'     => $nextSequence,
                    'text'         => $answer,
                    'input_method' => $inputMethod
                ]
            );

            echo json_encode(['success' => true, 'sequence' => $nextSequence]);
            break;

        // =====================================================================
        // Action: Complete Interview & Extract Knowledge
        // =====================================================================
        case 'complete':
            CSRF::validateAjax();

            $uuid = $requestData['interview_uuid'] ?? $_POST['interview_uuid'] ?? '';
            $guestToken = $requestData['guest_token'] ?? $_POST['guest_token'] ?? null;

            if (empty($uuid)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing interview UUID parameter.']);
                exit;
            }

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

            if (!Auth::validateInterviewAccess($interview, $guestToken)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Unauthorized access. Guest token missing or invalid.']);
                exit;
            }

            $summaryData = InterviewEngine::finalizeStory((int)$interview['interview_id']);

            echo json_encode([
                'success' => true,
                'summary' => $summaryData['story_summary'] ?? '',
                'title'   => $summaryData['title'] ?? ($interview['topic_name'] ?? 'Life Story'),
                'advice'  => $summaryData['advice'] ?? ''
            ]);
            break;

        // =====================================================================
        // Action: Summary Management
        // =====================================================================
        case 'summary':
            $uuid = $requestData['interview_uuid'] ?? $_POST['interview_uuid'] ?? $_GET['uuid'] ?? '';
            $guestToken = $requestData['guest_token'] ?? $_POST['guest_token'] ?? $_GET['guest_token'] ?? null;

            if (empty($uuid)) {
                $uuid = $_SESSION['active_interview_uuid'] ?? '';
            }

            if (empty($uuid)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing interview UUID parameter.']);
                exit;
            }

            $interview = DB::fetch("SELECT * FROM lg_interviews WHERE uuid = :uuid", ['uuid' => $uuid]);
            if (!$interview) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Interview session not found.']);
                exit;
            }

            if (!Auth::validateInterviewAccess($interview, $guestToken)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Unauthorized access to interview summary.']);
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $chunks = DB::fetchAll(
                    "SELECT chunk_id, content_type, anonymized_text, status 
                     FROM lg_knowledge_chunks 
                     WHERE interview_id = :id 
                     ORDER BY content_type ASC",
                    ['id' => $interview['interview_id']]
                );

                $summaryText = '';
                $adviceText = '';
                $quoteText = '';
                foreach ($chunks as $chunk) {
                    if ($chunk['content_type'] === 'story_summary') $summaryText = $chunk['anonymized_text'];
                    if ($chunk['content_type'] === 'advice') $adviceText = $chunk['anonymized_text'];
                    if ($chunk['content_type'] === 'representative_quote') $quoteText = $chunk['anonymized_text'];
                }

                echo json_encode([
                    'success'  => true,
                    'summary'  => $summaryText,
                    'advice'   => $adviceText,
                    'quote'    => $quoteText,
                    'status'   => $interview['status'],
                    'chunks'   => $chunks
                ]);
            } else {
                CSRF::validateAjax();
                $storySummary = trim($requestData['story_summary'] ?? $_POST['story_summary'] ?? '');
                $advice = trim($requestData['advice'] ?? $_POST['advice'] ?? '');
                $quote = trim($requestData['representative_quote'] ?? $_POST['representative_quote'] ?? '');

                if (!empty($storySummary)) {
                    DB::query(
                        "UPDATE lg_knowledge_chunks SET anonymized_text = :text WHERE interview_id = :id AND content_type = 'story_summary'",
                        ['text' => $storySummary, 'id' => $interview['interview_id']]
                    );
                }
                if (!empty($advice)) {
                    DB::query(
                        "UPDATE lg_knowledge_chunks SET anonymized_text = :text WHERE interview_id = :id AND content_type = 'advice'",
                        ['text' => $advice, 'id' => $interview['interview_id']]
                    );
                }
                if (!empty($quote)) {
                    DB::query(
                        "UPDATE lg_knowledge_chunks SET anonymized_text = :text WHERE interview_id = :id AND content_type = 'representative_quote'",
                        ['text' => $quote, 'id' => $interview['interview_id']]
                    );
                }

                echo json_encode(['success' => true, 'message' => 'Story summary updated successfully.']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error'   => 'Unrecognized API action. Valid actions: ask, next_question, save_answer, complete, summary.'
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("Unified API Controller Error [action={$action}]: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Internal server error: ' . $e->getMessage()
    ]);
}

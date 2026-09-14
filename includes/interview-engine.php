<?php
/**
 * LifeGPT - Interview Engine Service
 * Handles conversation state management, next question generation, and summary extraction.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/openai.php';

class InterviewEngine {

    /**
     * Determine target question limit based on duration type
     */
    public static function getTargetLimit(string $durationType): int {
        switch ($durationType) {
            case 'quick':
                return 4;
            case 'deep':
                return 8;
            case 'standard':
            default:
                return 5;
        }
    }

    /**
     * Retrieve or generate the next interviewer question
     */
    public static function getNextQuestion(array $interview): array {
        $interviewId = (int)$interview['interview_id'];
        $targetLimit = self::getTargetLimit($interview['duration_type']);
        
        // 1. Fetch existing messages for this interview
        $messages = DB::fetchAll(
            "SELECT sequence, role, text, question_type, created_at 
             FROM lg_interview_messages 
             WHERE interview_id = :id 
             ORDER BY sequence ASC",
            ['id' => $interviewId]
        );
        
        $totalMessages = count($messages);
        
        // 2. Count questions asked by interviewer so far
        $interviewerQuestionsCount = 0;
        foreach ($messages as $msg) {
            if ($msg['role'] === 'interviewer') {
                $interviewerQuestionsCount++;
            }
        }
        
        // 3. First question case (Initial greeting + starter question)
        if ($interviewerQuestionsCount === 0) {
            $greeting = $interview['greeting'] ?? "Hello! Thank you for sharing your story today.";
            $starterPrompt = $interview['starter_prompt'] ?? "What is a key experience or turning point you would like to share?";
            
            $initialQuestion = $greeting . " " . $starterPrompt;
            
            DB::insert(
                "INSERT INTO lg_interview_messages (interview_id, sequence, role, text, question_type) 
                 VALUES (:id, 1, 'interviewer', :text, 'starter')",
                [
                    'id' => $interviewId,
                    'text' => $initialQuestion
                ]
            );
            
            return [
                'success' => true,
                'next_question' => $initialQuestion,
                'question_sequence' => 1,
                'target_limit' => $targetLimit,
                'interview_complete' => false
            ];
        }

        // 4. Check if the last message in DB is from interviewer (awaiting user response)
        $lastMessage = end($messages);
        if ($lastMessage && $lastMessage['role'] === 'interviewer') {
            return [
                'success' => true,
                'next_question' => $lastMessage['text'],
                'question_sequence' => $interviewerQuestionsCount,
                'target_limit' => $targetLimit,
                'interview_complete' => false
            ];
        }
        
        // 5. Check if target limit is reached
        if ($interviewerQuestionsCount >= $targetLimit) {
            DB::query(
                "UPDATE lg_interviews SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE interview_id = :id",
                ['id' => $interviewId]
            );
            self::generateSummary($interview);

            return [
                'success' => true,
                'next_question' => '',
                'question_sequence' => $interviewerQuestionsCount,
                'target_limit' => $targetLimit,
                'interview_complete' => true
            ];
        }
        
        $nextSequence = $totalMessages + 1;
        $nextQuestionNum = $interviewerQuestionsCount + 1;
        
        // 6. Attempt OpenAI API chat completion
        try {
            $apiMessages = self::buildApiMessages($interview, $messages);
            $schema = self::getResponseSchema();
            
            $aiResponse = OpenAIClient::chatCompletion($apiMessages, $schema);
            
            $nextQuestion = $aiResponse['next_question'] ?? '';
            $qType = $aiResponse['question_type'] ?? 'follow_up';
            $isComplete = $aiResponse['interview_complete'] ?? false;
            $themes = $aiResponse['themes'] ?? [];
            
            if ($isComplete || empty($nextQuestion)) {
                DB::query(
                    "UPDATE lg_interviews SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE interview_id = :id",
                    ['id' => $interviewId]
                );
                self::generateSummary($interview);

                return [
                    'success' => true,
                    'next_question' => '',
                    'question_sequence' => $interviewerQuestionsCount,
                    'target_limit' => $targetLimit,
                    'interview_complete' => true
                ];
            }
            
            DB::insert(
                "INSERT INTO lg_interview_messages (interview_id, sequence, role, text, question_type) 
                 VALUES (:id, :seq, 'interviewer', :text, :qtype)",
                [
                    'id' => $interviewId,
                    'seq' => $nextSequence,
                    'text' => $nextQuestion,
                    'qtype' => $qType
                ]
            );
            
            self::saveThemes($interviewId, $themes);
            
            return [
                'success' => true,
                'next_question' => $nextQuestion,
                'question_sequence' => $nextQuestionNum,
                'target_limit' => $targetLimit,
                'interview_complete' => false
            ];
            
        } catch (Exception $e) {
            error_log("OpenAI Engine bypassed or error occurred (" . $e->getMessage() . "). Activating local fallback...");
            
            $fallbackQuestion = self::generateLocalQuestion($interview['topic_key'], $nextQuestionNum, $interview['persona_key']);
            
            if (empty($fallbackQuestion)) {
                DB::query(
                    "UPDATE lg_interviews SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE interview_id = :id",
                    ['id' => $interviewId]
                );
                self::generateSummary($interview);

                return [
                    'success' => true,
                    'next_question' => '',
                    'question_sequence' => $interviewerQuestionsCount,
                    'target_limit' => $targetLimit,
                    'interview_complete' => true
                ];
            }
            
            DB::insert(
                "INSERT INTO lg_interview_messages (interview_id, sequence, role, text, question_type) 
                 VALUES (:id, :seq, 'interviewer', :text, 'local_fallback')",
                [
                    'id' => $interviewId,
                    'seq' => $nextSequence,
                    'text' => $fallbackQuestion
                ]
            );
            
            return [
                'success' => true,
                'next_question' => $fallbackQuestion,
                'question_sequence' => $nextQuestionNum,
                'target_limit' => $targetLimit,
                'interview_complete' => false
            ];
        }
    }

    /**
     * Build prompt history for OpenAI API call
     */
    private static function buildApiMessages(array $interview, array $dbMessages): array {
        $personaPrompt = $interview['system_prompt'] ?? "You are a warm, empathetic listener.";
        $topicName = $interview['topic_name'] ?? "Life Experience";
        $targetLimit = self::getTargetLimit($interview['duration_type']);
        
        $language = $interview['language'] ?? ($_SESSION['ui_lang'] ?? 'en');
        $langInstruction = match($language) {
            'hi' => "IMPORTANT: You MUST ask all your questions in Hindi language (Devanagari script). Respond only in Hindi.",
            'mr' => "IMPORTANT: You MUST ask all your questions in Marathi language (Devanagari script). Respond only in Marathi.",
            default => "Ask questions in English."
        };

        // Build a list of previously asked questions to prevent repetition
        $askedQuestions = array_filter(array_map(function($m) {
            return ($m['role'] === 'interviewer') ? $m['text'] : null;
        }, $dbMessages));
        $askedList = !empty($askedQuestions)
            ? "\n\nPreviously asked questions (DO NOT repeat or rephrase these):\n- " . implode("\n- ", array_values($askedQuestions))
            : "";

        $systemText = $personaPrompt . "\n\n" .
            "You are conducting a structured life story recording session on the topic: \"{$topicName}\".\n" .
            "Your goal is to guide the contributor to share meaningful life lessons, turning points, and advice.\n" .
            "Target number of questions: {$targetLimit}.\n" .
            "Be empathetic, natural, and asking only ONE question at a time.\n" . "Do not use or mention personal names (such as Linda, etc.) in your questions.\n" .
            "Do not ask multiple questions in a single response. Avoid using any markdown formatting (like asterisks or hashtags) in your questions.\n" .
            "Each question MUST be genuinely different from anything already asked — explore a new aspect of the story.\n" .
            $langInstruction .
            $askedList;

        $apiMessages = [
            ['role' => 'system', 'content' => $systemText]
        ];
        
        foreach ($dbMessages as $msg) {
            $role = ($msg['role'] === 'interviewer') ? 'assistant' : 'user';
            $apiMessages[] = [
                'role' => $role,
                'content' => $msg['text']
            ];
        }
        
        return $apiMessages;
    }

    private static function getResponseSchema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'next_question' => ['type' => 'string'],
                'question_type' => ['type' => 'string'],
                'interview_complete' => ['type' => 'boolean'],
                'completion_reason' => ['type' => ['string', 'null']],
                'themes' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'safety_flag' => ['type' => 'boolean'],
                'safety_category' => ['type' => ['string', 'null']]
            ],
            'required' => [
                'next_question', 'question_type', 'interview_complete', 'completion_reason', 'themes', 'safety_flag', 'safety_category'
            ],
            'additionalProperties' => false
        ];
    }

    private static function saveThemes(int $interviewId, array $themesList): void {
        foreach ($themesList as $themeName) {
            $themeName = trim(ucwords(strtolower($themeName)));
            if (empty($themeName)) continue;
            
            try {
                $theme = DB::fetch("SELECT theme_id FROM lg_themes WHERE theme_name = :name", ['name' => $themeName]);
                if ($theme) {
                    $themeId = $theme['theme_id'];
                } else {
                    $themeId = DB::insert("INSERT INTO lg_themes (theme_name) VALUES (:name)", ['name' => $themeName]);
                }
                
                DB::query(
                    "INSERT INTO lg_interview_themes (interview_id, theme_id, confidence, user_confirmation) 
                     VALUES (:interview_id, :theme_id, 0.85, 0)
                     ON DUPLICATE KEY UPDATE confidence = 0.85",
                    ['interview_id' => $interviewId, 'theme_id' => $themeId]
                );
            } catch (Exception $e) {
                error_log("Failed mapping theme '$themeName': " . $e->getMessage());
            }
        }
    }

    private static function generateLocalQuestion(string $topicKey, int $questionNum, string $personaKey): string {
        $banks = [
            'lesson' => [
                2 => "What were the immediate events that led up to you learning this lesson?",
                3 => "Were there other people involved who influenced what happened?",
                4 => "Looking back, what choice did you make that you think mattered most?",
                5 => "What was the outcome of that choice, and how did it change your perspective?",
                6 => "If you could tell someone younger one thing to prevent them from making the same mistake, what would it be?",
                7 => "Is there a specific moment in this experience that you can look back on and laugh about now?",
                8 => "Thank you for sharing this. Is there any final thought you want to add before we finish?"
            ]
        ];
        
        $defaultBank = [
            2 => "Can you describe the turning point or details of this experience in more depth?",
            3 => "Who else was involved, and how did they affect the outcome?",
            4 => "What was the hardest choice you had to make during this period?",
            5 => "What was the outcome, and how did it affect the direction of your life?",
            6 => "Looking back, what did this teach you about yourself or society in general?",
            7 => "What is the most practical piece of advice you would offer to someone else?",
            8 => "Thank you so much. Is there any last thought you'd like to record before we summarize?"
        ];
        
        $bank = $banks[$topicKey] ?? $defaultBank;
        $question = $bank[$questionNum] ?? '';
        
        if (!empty($question)) {
            switch ($personaKey) {
                case 'grandchild':
                    $question = "Oh, wow. " . lcfirst($question);
                    break;
                case 'comedian':
                    $question = "That's fascinating! Tell me, " . lcfirst($question);
                    break;
                case 'coach':
                    $question = "Got it. Reflecting on that, " . lcfirst($question);
                    break;
                case 'historian':
                    $question = "Understood. For the record, " . lcfirst($question);
                    break;
            }
        }
        
        return $question;
    }

    /**
     * Generate structured summary from interview conversation
     */
    public static function generateSummary(array $interview): array {
        $interviewId = (int)$interview['interview_id'];
        
        // Fetch all contributor responses
        $dbAnswers = DB::fetchAll(
            "SELECT text FROM lg_interview_messages WHERE interview_id = :id AND role = 'contributor' ORDER BY sequence ASC",
            ['id' => $interviewId]
        );
        
        $answers = array_column($dbAnswers, 'text');
        
        // Filter out skipped placeholder notes for clean summaries
        $validAnswers = array_values(array_filter($answers, function($a) {
            return !empty($a) && stripos($a, 'skip') === false;
        }));

        // Attempt OpenAI summary extraction
        try {
            $dbMessages = DB::fetchAll(
                "SELECT role, text FROM lg_interview_messages WHERE interview_id = :id ORDER BY sequence ASC",
                ['id' => $interviewId]
            );
            
            $apiMessages = [];
            $systemText = "You are the summary extraction engine of LifeGPT. Analyze the provided interview and extract the following structured details:\n" .
                          "1. story_summary: A 2-3 sentence overview of the contributor's story.\n" .
                          "2. main_lesson: The core lesson the contributor learned.\n" .
                          "3. turning_point: The main decision or turning point.\n" .
                          "4. outcome: What resulted from this decision.\n" .
                          "5. advice: Direct advice the contributor offers to younger generations.\n" .
                          "6. funny_moment: A funny or lighthearted detail, or null if not applicable.\n" .
                          "7. representative_quote: A powerful, first-person quote summarizing their wisdom.\n" .
                          "Ensure all outputs are clear, respectful, and capture the authentic voice of the contributor. Do not use markdown syntax (like asterisks or hashtags) inside the extracted text.";
            
            $apiMessages[] = ['role' => 'system', 'content' => $systemText];
            
            foreach ($dbMessages as $msg) {
                $role = ($msg['role'] === 'interviewer') ? 'assistant' : 'user';
                $apiMessages[] = ['role' => $role, 'content' => $msg['text']];
            }
            
            $schema = [
                'type' => 'object',
                'properties' => [
                    'story_summary' => ['type' => 'string'],
                    'main_lesson' => ['type' => 'string'],
                    'turning_point' => ['type' => 'string'],
                    'outcome' => ['type' => 'string'],
                    'advice' => ['type' => 'string'],
                    'funny_moment' => ['type' => ['string', 'null']],
                    'representative_quote' => ['type' => 'string']
                ],
                'required' => ['story_summary', 'main_lesson', 'turning_point', 'outcome', 'advice', 'funny_moment', 'representative_quote'],
                'additionalProperties' => false
            ];
            
            $summaryData = OpenAIClient::chatCompletion($apiMessages, $schema);
            
        } catch (Exception $e) {
            error_log("OpenAI summary bypassed or failed (" . $e->getMessage() . "). Generating dynamic local summary...");
            
            $topicName = $interview['topic_name'] ?? 'Life Reflections';
            $firstAnswer = $validAnswers[0] ?? 'Sharing life experiences and reflections';
            $secondAnswer = $validAnswers[1] ?? 'Making key choices during life turning points';
            $thirdAnswer = $validAnswers[2] ?? 'Growth and learning from experience';
            $fourthAnswer = $validAnswers[3] ?? 'Staying resilient and valuing family and relationships';
            $fifthAnswer = $validAnswers[4] ?? 'Always trust your resilience and cherish your loved ones.';
            
            $summaryData = [
                'story_summary' => "A personal reflection on " . strtolower($topicName) . ". Key insight: " . $firstAnswer,
                'main_lesson' => $fourthAnswer,
                'turning_point' => $secondAnswer,
                'outcome' => $thirdAnswer,
                'advice' => $fifthAnswer,
                'funny_moment' => "A lighter moment occurred when reflecting on life's unexpected turns.",
                'representative_quote' => '"' . $firstAnswer . '"'
            ];
        }
        
        // Save summary to database (insert or update)
        $existing = DB::fetch("SELECT summary_id FROM lg_interview_summaries WHERE interview_id = :id", ['id' => $interviewId]);
        
        if ($existing) {
            DB::query(
                "UPDATE lg_interview_summaries 
                 SET story_summary = :story_summary, main_lesson = :main_lesson, turning_point = :turning_point, 
                     outcome = :outcome, advice = :advice, funny_moment = :funny_moment, representative_quote = :representative_quote,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE interview_id = :id",
                array_merge($summaryData, ['id' => $interviewId])
            );
        } else {
            DB::insert(
                "INSERT INTO lg_interview_summaries (interview_id, story_summary, main_lesson, turning_point, outcome, advice, funny_moment, representative_quote) 
                 VALUES (:id, :story_summary, :main_lesson, :turning_point, :outcome, :advice, :funny_moment, :representative_quote)",
                array_merge($summaryData, ['id' => $interviewId])
            );
        }

        // Also update interview status to completed
        DB::query(
            "UPDATE lg_interviews SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE interview_id = :id",
            ['id' => $interviewId]
        );
        
        return $summaryData;
    }

    /**
     * Finalize the story by generating/updating the summary,
     * populating knowledge chunks, updating consents, and marking the interview as completed.
     */
    public static function finalizeStory(int $interviewId, array $customData = []): array {
        // 1. Fetch the interview
        $interview = DB::fetch("SELECT * FROM lg_interviews WHERE interview_id = :id", ['id' => $interviewId]);
        if (!$interview) {
            throw new Exception("Interview not found.");
        }

        // 2. Ensure summary exists or is generated
        $summary = DB::fetch("SELECT * FROM lg_interview_summaries WHERE interview_id = :id", ['id' => $interviewId]);
        if (!$summary) {
            $summary = self::generateSummary($interview);
        }

        // 3. Merge custom data if provided, prioritizing the custom inputs
        $storySummary = isset($customData['story_summary']) && $customData['story_summary'] !== '' ? $customData['story_summary'] : $summary['story_summary'];
        $mainLesson = isset($customData['main_lesson']) && $customData['main_lesson'] !== '' ? $customData['main_lesson'] : $summary['main_lesson'];
        $turningPoint = isset($customData['turning_point']) && $customData['turning_point'] !== '' ? $customData['turning_point'] : $summary['turning_point'];
        $outcome = isset($customData['outcome']) && $customData['outcome'] !== '' ? $customData['outcome'] : $summary['outcome'];
        $advice = isset($customData['advice']) && $customData['advice'] !== '' ? $customData['advice'] : $summary['advice'];
        $funnyMoment = isset($customData['funny_moment']) ? $customData['funny_moment'] : $summary['funny_moment'];
        $representativeQuote = isset($customData['representative_quote']) && $customData['representative_quote'] !== '' ? $customData['representative_quote'] : $summary['representative_quote'];

        // 4. Update summary table
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

        // 5. Update consents if provided
        $consent = DB::fetch("SELECT * FROM lg_consents WHERE interview_id = :id", ['id' => $interviewId]);
        if ($consent) {
            DB::query(
                "UPDATE lg_consents 
                 SET rag_consent = :rag, quotes_consent = :quotes, research_consent = :research, publication_consent = :publication,
                     attribution_type = :attribution_type, attribution_value = :attribution_value, withdrawn = 0, updated_at = CURRENT_TIMESTAMP
                 WHERE interview_id = :id",
                [
                    'rag' => $customData['rag_consent'] ?? $consent['rag_consent'],
                    'quotes' => $customData['quotes_consent'] ?? $consent['quotes_consent'],
                    'research' => $customData['research_consent'] ?? $consent['research_consent'],
                    'publication' => $customData['publication_consent'] ?? $consent['publication_consent'],
                    'attribution_type' => $customData['attribution_type'] ?? $consent['attribution_type'],
                    'attribution_value' => !empty($customData['attribution_value']) ? $customData['attribution_value'] : $consent['attribution_value'],
                    'id' => $interviewId
                ]
            );
        }

        // 6. Populate lg_knowledge_chunks as PENDING (Admin review required)
        // Clean out old pending chunks first to prevent duplicates on resubmissions
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

        // 7. Mark interview as completed
        DB::query(
            "UPDATE lg_interviews SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE interview_id = :id",
            ['id' => $interviewId]
        );

        return [
            'story_summary' => $storySummary,
            'main_lesson' => $mainLesson,
            'turning_point' => $turningPoint,
            'outcome' => $outcome,
            'advice' => $advice,
            'funny_moment' => $funnyMoment,
            'representative_quote' => $representativeQuote
        ];
    }
}


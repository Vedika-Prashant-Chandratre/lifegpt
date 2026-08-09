<?php
/**
 * LifeGPT - OpenAI-powered Interview Engine
 * Builds conversation context, calls OpenAI for structured JSON questions,
 * and maintains a robust local fallback bank for offline/development testing.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/openai.php';

class InterviewEngine {
    /**
     * Determine next question or complete state for an interview session
     */
    public static function getNextQuestion(array $interview): array {
        $interviewId = (int)$interview['interview_id'];
        $uuid = $interview['uuid'];
        
        // 1. Fetch current conversation messages in order
        $messages = DB::fetchAll(
            "SELECT * FROM lg_interview_messages WHERE interview_id = :id ORDER BY sequence ASC",
            ['id' => $interviewId]
        );
        
        $totalMessages = count($messages);
        
        // 2. Determine limits based on duration settings
        $targetLimit = 8; // standard
        if ($interview['duration_type'] === 'quick') {
            $targetLimit = 4;
        } elseif ($interview['duration_type'] === 'deep') {
            $targetLimit = 12;
        }
        
        // 3. Case A: Brand new conversation. First question is the persona's greeting.
        if ($totalMessages === 0) {
            $greeting = $interview['greeting'];
            
            DB::insert(
                "INSERT INTO lg_interview_messages (interview_id, sequence, role, text, question_type) 
                 VALUES (:id, 1, 'interviewer', :text, 'greeting')",
                ['id' => $interviewId, 'text' => $greeting]
            );
            
            return [
                'success' => true,
                'next_question' => $greeting,
                'question_sequence' => 1,
                'target_limit' => $targetLimit,
                'interview_complete' => false
            ];
        }
        
        $lastMessage = $messages[$totalMessages - 1];
        
        // 4. Case B: Page reload or consecutive requests without user answering yet.
        // If the last role was 'interviewer', return that same question.
        if ($lastMessage['role'] === 'interviewer') {
            // Count interviewer questions asked
            $questionSeq = 0;
            foreach ($messages as $msg) {
                if ($msg['role'] === 'interviewer') $questionSeq++;
            }
            
            return [
                'success' => true,
                'next_question' => $lastMessage['text'],
                'question_sequence' => $questionSeq,
                'target_limit' => $targetLimit,
                'interview_complete' => false
            ];
        }
        
        // 5. Case C: Contributor has answered. We need to generate the NEXT interviewer question.
        // Count interviewer questions asked so far
        $interviewerQuestionsCount = 0;
        foreach ($messages as $msg) {
            if ($msg['role'] === 'interviewer') {
                $interviewerQuestionsCount++;
            }
        }
        
        // Check if we hit the limit
        if ($interviewerQuestionsCount >= $targetLimit) {
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
            
            // If OpenAI decides it's complete or next question is empty, end early
            if ($isComplete || empty($nextQuestion)) {
                return [
                    'success' => true,
                    'next_question' => '',
                    'question_sequence' => $interviewerQuestionsCount,
                    'target_limit' => $targetLimit,
                    'interview_complete' => true
                ];
            }
            
            // Save AI message to database
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
            
            // Parse and save themes to mapping
            self::saveThemes($interviewId, $themes);
            
            return [
                'success' => true,
                'next_question' => $nextQuestion,
                'question_sequence' => $nextQuestionNum,
                'target_limit' => $targetLimit,
                'interview_complete' => false
            ];
            
        } catch (Exception $e) {
            // Log fallback indicator
            error_log("OpenAI Engine bypassed or error occurred (" . $e->getMessage() . "). Activating local fallback...");
            
            // 7. Local Fallback Question Generator
            $fallbackQuestion = self::generateLocalQuestion($interview['topic_key'], $nextQuestionNum, $interview['persona_key']);
            
            // If fallback question returns empty, it means we chose to wrap up
            if (empty($fallbackQuestion)) {
                return [
                    'success' => true,
                    'next_question' => '',
                    'question_sequence' => $interviewerQuestionsCount,
                    'target_limit' => $targetLimit,
                    'interview_complete' => true
                ];
            }
            
            // Save local message to database
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
     * Build the structured message history array for the OpenAI API
     */
    private static function buildApiMessages(array $interview, array $dbMessages): array {
        $apiMessages = [];
        
        // System Prompt
        $systemText = $interview['system_prompt'] . "\n" .
                      "You are interviewing a contributor on the topic: \"" . $interview['topic_name'] . "\".\n" .
                      "The contributor's chosen length is: " . $interview['duration_type'] . ".\n" .
                      "Remember your rules: Ask exactly ONE concise question. Utilize previous answers for relevant follow-up. Do not request identifying information.";
        
        $apiMessages[] = ['role' => 'system', 'content' => $systemText];
        
        // Map database messages to OpenAI schema
        foreach ($dbMessages as $msg) {
            $role = ($msg['role'] === 'interviewer') ? 'assistant' : 'user';
            $apiMessages[] = ['role' => $role, 'content' => $msg['text']];
        }
        
        return $apiMessages;
    }

    /**
     * Define the JSON response schema for strict validation
     */
    private static function getResponseSchema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'next_question' => [
                    'type' => 'string',
                    'description' => 'The next follow up question'
                ],
                'question_type' => [
                    'type' => 'string',
                    'description' => 'One word category of the question, e.g. turning_point, advice, reflection'
                ],
                'interview_complete' => [
                    'type' => 'boolean',
                    'description' => 'Set true if target questions are met or content has wrapped up'
                ],
                'completion_reason' => [
                    'type' => ['string', 'null'],
                    'description' => 'Reason for completing the interview, or null'
                ],
                'themes' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'List of 2-3 high-level themes extracted from the conversation so far'
                ],
                'safety_flag' => [
                    'type' => 'boolean',
                    'description' => 'True if input triggers suicide/crisis/sensitive rules'
                ],
                'safety_category' => [
                    'type' => ['string', 'null'],
                    'description' => 'Category of safety issue, or null'
                ]
            ],
            'required' => [
                'next_question',
                'question_type',
                'interview_complete',
                'completion_reason',
                'themes',
                'safety_flag',
                'safety_category'
            ],
            'additionalProperties' => false
        ];
    }

    /**
     * Map extracted themes to lg_themes and map to this interview
     */
    private static function saveThemes(int $interviewId, array $themesList): void {
        foreach ($themesList as $themeName) {
            $themeName = trim(ucwords(strtolower($themeName)));
            if (empty($themeName)) continue;
            
            try {
                // 1. Get or insert theme
                $theme = DB::fetch("SELECT theme_id FROM lg_themes WHERE theme_name = :name", ['name' => $themeName]);
                if ($theme) {
                    $themeId = $theme['theme_id'];
                } else {
                    $themeId = DB::insert("INSERT INTO lg_themes (theme_name) VALUES (:name)", ['name' => $themeName]);
                }
                
                // 2. Map theme to interview
                DB::query(
                    "INSERT INTO lg_interview_themes (interview_id, theme_id, confidence, user_confirmation) 
                     VALUES (:interview_id, :theme_id, 0.85, 0)
                     ON DUPLICATE KEY UPDATE confidence = 0.85",
                    ['interview_id' => $interviewId, 'theme_id' => $themeId]
                );
            } catch (Exception $e) {
                // Log and ignore to prevent blocking
                error_log("Failed mapping theme '$themeName': " . $e->getMessage());
            }
        }
    }

    /**
     * Generate logical fallback questions when OpenAI API is disabled or fails
     */
    private static function generateLocalQuestion(string $topicKey, int $questionNum, string $personaKey): string {
        // Predefined question sequences for each topic
        $banks = [
            'lesson' => [
                2 => "What were the immediate events that led up to you learning this lesson?",
                3 => "Were there other people involved who influenced what happened?",
                4 => "Looking back, what choice did you make that you think mattered most?",
                5 => "What was the outcome of that choice, and how did it change your perspective?",
                6 => "If you could tell someone younger one thing to prevent them from making the same mistake, what would it be?",
                7 => "Is there a specific moment in this experience that you can look back on and laugh about now?",
                8 => "Thank you for sharing this. Is there any final thought you want to add before we finish?"
            ],
            'differently' => [
                2 => "What options did you have at the time, and what made you choose the path you did?",
                3 => "How did that decision end up affecting your career, family, or personal path?",
                4 => "If you had gone down the other path, what do you think would have been different?",
                5 => "How long did it take you to reconcile with the path you actually took?",
                6 => "What is the single most important lesson that taking that path taught you about yourself?",
                7 => "What advice would you give to someone who is at a similar crossroad today?",
                8 => "Thank you. Is there any concluding thought you'd like to share?"
            ],
            'advice' => [
                2 => "What are the common mistakes you see younger people making in this area today?",
                3 => "How did you learn this? Was there a specific mentor or personal mistake that taught you?",
                4 => "How has society's view on this topic changed since you were in your twenties?",
                5 => "What is a practical first step a young person could take to apply your advice?",
                6 => "Is there a common piece of advice that young people get today that you actually disagree with?",
                7 => "If you could go back and tell your 20-year-old self this advice, how do you think they would react?",
                8 => "Thank you. Is there any final wisdom you'd like to record?"
            ],
            'funny' => [
                2 => "How did the situation start? Was it a misunderstanding or just bad luck?",
                3 => "What was going through your mind when you realized things were going wrong?",
                4 => "How did the people around you react to the mishap at the time?",
                5 => "How long did it take for this experience to go from embarrassing to hilarious?",
                6 => "What does this funny memory teach you about taking life too seriously?",
                7 => "Do you still share this story at family dinners or gatherings?",
                8 => "That's a wonderful memory. Any final detail you'd like to include before we wrap up?"
            ]
        ];
        
        // General default bank for other topics (career, relationships, money, health, turning_point, pride, etc.)
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
        
        // Modify the tone slightly based on the persona
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
        
        // Attempt OpenAI summary extraction
        try {
            // Fetch all messages
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
                          "Ensure all outputs are clear, respectful, and capture the authentic voice of the contributor.";
            
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
            error_log("OpenAI summary failed or bypassed: " . $e->getMessage() . ". Generating dynamic local fallback summary...");
            
            // Dynamic local fallback using user's actual answers
            $firstAnswer = $answers[0] ?? 'Sharing life experiences';
            $secondAnswer = $answers[1] ?? 'Making key decisions';
            $thirdAnswer = $answers[2] ?? 'Reaping the outcomes';
            $fourthAnswer = $answers[3] ?? 'Sharing lessons learned';
            $fifthAnswer = $answers[4] ?? 'Giving advice';
            
            $summaryData = [
                'story_summary' => "A personal reflection on " . strtolower($interview['topic_name']) . ". The contributor shared key details: " . substr($firstAnswer, 0, 100) . "...",
                'main_lesson' => !empty($fourthAnswer) ? $fourthAnswer : "The core lesson was that decisions are shaping points, and staying resilient matters.",
                'turning_point' => !empty($secondAnswer) ? $secondAnswer : "The turning point was making a key choice regarding " . strtolower($interview['topic_name']) . ".",
                'outcome' => !empty($thirdAnswer) ? $thirdAnswer : "The decision led to growth, learning, and new perspectives.",
                'advice' => !empty($fifthAnswer) ? $fifthAnswer : "Advice to younger people: take risks, value relationships, and don't worry about tiny details.",
                'funny_moment' => "A lighter moment occurred when reflecting on life's unexpected turns.",
                'representative_quote' => !empty($firstAnswer) ? '"' . substr($firstAnswer, 0, 150) . '"' : '"Your life has answers someone else needs."'
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
        
        return $summaryData;
    }
}

<?php
/**
 * LifeGPT - Master RAG Pipeline Coordinator
 * Orchestrates query processing, hybrid semantic retrieval, story context reconstruction,
 * prompt generation, Groq LLM synthesis, and programmatic grounding evaluation.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../openai.php';
require_once __DIR__ . '/RetrievalService.php';
require_once __DIR__ . '/GroundingService.php';

class RagPipeline {
    /**
     * Execute full RAG pipeline for a user question
     * 
     * @param string $userQuery
     * @param array $chatHistory
     * @param string|null $languageCode 'en' | 'hi' | 'mr'
     * @return array Structured response for UI and API consumers
     */
    public static function ask(string $userQuery, array $chatHistory = [], ?string $languageCode = null): array {
        $activeLang = $languageCode ?: ($_SESSION['ui_lang'] ?? $_COOKIE['ui_lang'] ?? 'en');

        // 1. Run hybrid semantic retrieval
        $retrieval = RetrievalService::retrieve($userQuery);

        // 2. Build structured anonymized sources for UI display
        $sources = [];
        foreach ($retrieval['stories'] as $i => $st) {
            $topic = $st['topic_name'] ?? 'Life Experience';
            $lesson = $st['structured_excerpts']['lesson'] 
                   ?? $st['structured_excerpts']['advice'] 
                   ?? $st['structured_excerpts']['summary'] 
                   ?? 'Shared life perspective';

            $sources[] = [
                'experience_num'  => $i + 1,
                'interview_id'    => $st['interview_id'],
                'topic'           => $topic,
                'relevance_score' => $st['story_relevance'],
                'key_insight'     => mb_substr($lesson, 0, 160) . '...',
                'author'          => 'Anonymous Contributor',
            ];
        }

        // 3. Check relevance threshold & handle insufficient evidence
        if ($retrieval['status'] === 'insufficient_evidence' || empty($retrieval['stories'])) {
            $answer = self::buildLowEvidenceResponse($userQuery, $activeLang);
            $grounding = GroundingService::evaluate($retrieval, $userQuery, $answer);

            return [
                'answer'            => $answer,
                'grounding_score'   => $grounding['score'],
                'confidence_label'  => $grounding['label'],
                'confidence_color'  => $grounding['color'],
                'confidence_badge'  => $grounding['badge_bg'],
                'sources_count'     => 0,
                'sources'           => [],
                'retrieval_status'  => 'insufficient_evidence',
                'disclaimer'        => $grounding['disclaimer'],
                'metrics'           => $retrieval['metrics'],
            ];
        }

        // 4. Construct story-level context
        $contextText = "";
        foreach ($retrieval['stories'] as $i => $st) {
            $topic = $st['topic_name'] ?? 'Life Experience';
            $excerpts = $st['structured_excerpts'] ?? [];
            
            $contextText .= "--- STORY " . ($i + 1) . " (Topic: {$topic}) ---\n";
            if (!empty($excerpts['summary'])) {
                $contextText .= "Summary: " . $excerpts['summary'] . "\n";
            }
            if (!empty($excerpts['turning_point'])) {
                $contextText .= "Turning Point: " . $excerpts['turning_point'] . "\n";
            }
            if (!empty($excerpts['lesson'])) {
                $contextText .= "Key Lesson: " . $excerpts['lesson'] . "\n";
            }
            if (!empty($excerpts['advice'])) {
                $contextText .= "Advice: " . $excerpts['advice'] . "\n";
            }
            if (!empty($excerpts['quote'])) {
                $contextText .= "Quote: " . $excerpts['quote'] . "\n";
            }
            $contextText .= "\n";
        }

        // 5. Anti-repetition context from session history
        $historyContext = '';
        if (!empty($chatHistory)) {
            $prevPairs = array_filter($chatHistory, fn($m) => ($m['role'] ?? '') === 'assistant');
            if (!empty($prevPairs)) {
                $historyContext = "\n\nPrevious answers already given in this session (DO NOT repeat the same phrases):\n";
                foreach (array_values($prevPairs) as $idx => $prev) {
                    $historyContext .= ($idx + 1) . ". " . mb_substr($prev['content'] ?? '', 0, 180) . "...\n";
                }
            }
        }

        // 6. Language instruction
        $langInstruction = match($activeLang) {
            'hi' => "CRITICAL LANGUAGE INSTRUCTION: You MUST formulate your entire response in natural, fluent Hindi (हिंदी) in Devanagari script. Speak with warmth, depth, and empathy.",
            'mr' => "CRITICAL LANGUAGE INSTRUCTION: You MUST formulate your entire response in natural, fluent Marathi (मराठी) in Devanagari script. Speak with warmth, depth, and empathy.",
            default => "CRITICAL LANGUAGE INSTRUCTION: Answer in warm, natural, human conversation style in English with depth and care."
        };

        // 7. System prompt enforcing depth, strict evidence grounding & privacy
        $systemPrompt = "You are Ask LifeGPT, an AI assistant trained on a growing collection of real human life experiences, advice, and wisdom.\n" .
            "Answer the user's question in a warm, natural, human conversation style based SPECIFICALLY on the provided LifeGPT story experiences.\n\n" .
            "RESPONSE LENGTH AND DEPTH GUIDELINES:\n" .
            "- Provide a comprehensive, in-depth, and well-developed response (typically 3 to 4 substantial paragraphs). Do NOT give a brief or superficial 1-paragraph summary.\n" .
            "- Paragraph 1: Directly address the user's dilemma with empathy and practical framing, clarifying the core tension or challenge.\n" .
            "- Paragraphs 2 & 3: Deeply synthesize the specific life turning points, real setbacks, and hard-won wisdom from the retrieved contributor experiences. Discuss specific decisions that worked, common mistakes or emotional pitfalls to avoid, and how contributors navigated the journey.\n" .
            "- Paragraph 4: Conclude with thoughtful, actionable takeaways and grounded perspective for someone facing this situation today.\n\n" .
            "STRICT INTEGRITY & PRIVACY RULES:\n" .
            "1. Answer the user's actual question directly and thoroughly.\n" .
            "2. Use the retrieved LifeGPT stories as your primary evidence and context. Synthesize multiple stories together.\n" .
            "3. NEVER invent an experience, never fabricate a story, and never invent a quote.\n" .
            "4. NEVER mention, invent, or attribute any personal names or persona names (such as Linda, Maria, Helen, John, David, Robert, etc.). Refer to contributors anonymously (e.g., 'A contributor reflected that...', 'People who went through this shared that...').\n" .
            "5. Avoid using markdown formatting (like asterisks **, hashtags #, or bullet characters); format your answer in clean, readable paragraphs suitable for a chat bubble.\n" .
            $langInstruction . "\n" .
            $historyContext . "\n" .
            "You MUST return a JSON object with an \"answer\" key containing your complete response.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Retrieved LifeGPT Experience Stories:\n{$contextText}\n\nUser Question: {$userQuery}"]
        ];

        // 8. Generate synthesis using Groq / OpenAI client
        try {
            $apiRes = OpenAIClient::chatCompletion($messages, [
                'type' => 'object',
                'properties' => [
                    'answer' => ['type' => 'string']
                ],
                'required' => ['answer']
            ]);
            $rawAnswer = $apiRes['answer'] ?? '';
            $answer = self::sanitizeAnswer($rawAnswer);
        } catch (Exception $e) {
            $answer = self::buildFallbackSynthesizedAnswer($retrieval['stories'], $activeLang);
        }

        // 9. Programmatic Grounding Evaluation
        $grounding = GroundingService::evaluate($retrieval, $userQuery, $answer);

        return [
            'answer'            => $answer,
            'grounding_score'   => $grounding['score'],
            'confidence_label'  => $grounding['label'],
            'confidence_color'  => $grounding['color'],
            'confidence_badge'  => $grounding['badge_bg'],
            'sources_count'     => count($sources),
            'sources'           => $sources,
            'retrieval_status'  => 'grounded',
            'disclaimer'        => $grounding['disclaimer'],
            'metrics'           => $retrieval['metrics'],
        ];
    }

    /**
     * Clean up markdown and strip accidental persona names
     */
    private static function sanitizeAnswer(string $text): string {
        // Strip markdown
        $text = preg_replace('/\*\*(.+?)\*\*/s', '$1', $text);
        $text = preg_replace('/__(.+?)__/s', '$1', $text);
        $text = preg_replace('/\*(.+?)\*/s', '$1', $text);
        $text = preg_replace('/_(.+?)_/s', '$1', $text);
        $text = preg_replace('/^#{1,6}\s*/m', '', $text);
        $text = preg_replace('/^[\-\*]\s+/m', '', $text);
        $text = preg_replace('/^\d+\.\s+/m', '', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // Strip persona names like Linda
        $text = preg_replace('/^(?:Linda|Maria|Helen|John|David|Robert|Contributor)\s*:\s*/iu', '', $text);
        $text = preg_replace('/\b(?:Linda|Maria|Helen|John|David|Robert)\b/iu', 'a contributor', $text);

        return trim($text);
    }

    /**
     * Grounded low-evidence response when query has no matching archive data
     */
    private static function buildLowEvidenceResponse(string $query, string $lang): string {
        return match($lang) {
            'hi' => "मुझे LifeGPT अभिलेखागार में इस विशिष्ट प्रश्न के लिए पर्याप्त प्रासंगिक वास्तविक जीवन अनुभव नहीं मिले। LifeGPT वास्तविक लोगों द्वारा साझा किए गए अनुभवों पर आधारित है, और वर्तमान में इस विषय पर पर्याप्त प्रलेखित अनुभव उपलब्ध नहीं हैं।",
            'mr' => "मला LifeGPT संग्रहामध्ये या विशिष्ट प्रश्नासाठी पुरेसे संबंधित प्रत्यक्ष जीवन अनुभव आढळले नाहीत. LifeGPT वास्तविक लोकांनी सामायिक केलेल्या अनुभवांवर आधारित आहे आणि सध्या या विषयावर पुरेसे प्रलेखित अनुभव उपलब्ध नाहीत.",
            default => "I couldn't find enough closely relevant experiences in the LifeGPT archive to give a well-grounded answer. LifeGPT is powered by real life turning points shared by contributors, and our archive does not yet have enough documented experiences addressing this specific question."
        };
    }

    private static function buildFallbackSynthesizedAnswer(array $stories, string $lang): string {
        $lessons = [];
        foreach ($stories as $s) {
            if (!empty($s['structured_excerpts']['lesson'])) {
                $lessons[] = $s['structured_excerpts']['lesson'];
            }
        }
        $combined = implode(' ', array_slice($lessons, 0, 2));

        return match($lang) {
            'hi' => "हमारे संग्रह में दर्ज वास्तविक जीवन अनुभवों के आधार पर: " . $combined,
            'mr' => "आमच्या संग्रहातील वास्तविक जीवन अनुभवांवर आधारित: " . $combined,
            default => "Based on the real life experiences recorded in our archive: " . $combined
        };
    }
}

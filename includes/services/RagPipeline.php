<?php
/**
 * LifeGPT - Master RAG Pipeline Coordinator
 * Orchestrates query processing, hybrid semantic retrieval, story context reconstruction,
 * prompt generation, Groq LLM synthesis, and programmatic grounding evaluation.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../openai.php';
require_once __DIR__ . '/ContextService.php';
require_once __DIR__ . '/RetrievalService.php';
require_once __DIR__ . '/GroundingService.php';

class RagPipeline {

    /**
     * Execute full RAG pipeline for a user question
     *
     * @param string      $userQuery       Raw user question
     * @param array       $chatHistory     Legacy in-session history (used only if no conversation_id)
     * @param string|null $languageCode    'en' | 'hi' | 'mr'
     * @param string|null $conversationId  Persistent conversation UUID (auto-generated if null)
     * @param bool        $debugMode       Return debug info in response when true
     * @return array Structured response for UI and API consumers
     */
    public static function ask(
        string  $userQuery,
        array   $chatHistory = [],
        ?string $languageCode = null,
        ?string $conversationId = null,
        bool    $debugMode = false
    ): array {
        $activeLang = $languageCode ?: ($_SESSION['ui_lang'] ?? $_COOKIE['ui_lang'] ?? 'en');

        $cfg           = require __DIR__ . '/../../config/rag.php';
        $ctxCfg        = $cfg['context'] ?? ['max_history_turns' => 6, 'summary_threshold_turns' => 8, 'persist_to_db' => true];
        $maxTurns      = (int)($ctxCfg['max_history_turns'] ?? 6);
        $summaryTurns  = (int)($ctxCfg['summary_threshold_turns'] ?? 8);
        $persistDb     = (bool)($ctxCfg['persist_to_db'] ?? true);

        // ---- STEP 1: Resolve conversation ID & load persistent history ----
        if (empty($conversationId)) {
            $conversationId = ContextService::generateUUID();
        }

        $dbHistory      = [];
        $conversationRow = null;
        $existingSummary = null;

        if ($persistDb) {
            $conversationRow = ContextService::getOrCreateConversation($conversationId);
            $dbHistory       = ContextService::getHistory($conversationId, $maxTurns);
            $existingSummary = $conversationRow['summary'] ?? null;
        } else {
            // Fall back to session-level history if DB persistence is disabled
            $dbHistory = $chatHistory;
        }

        // ---- STEP 2: Intent Classification ----
        $intentResult   = ContextService::detectIntent($userQuery, $dbHistory);
        $intentType     = $intentResult['intent_type'];  // 'FOLLOW_UP' | 'STANDALONE'

        // ---- STEP 3: Contextual Query Rewriting ----
        $contextualQuery = ContextService::generateContextualQuery(
            $userQuery,
            $dbHistory,
            $existingSummary,
            $intentResult
        );

        // ---- STEP 4: Hybrid Retrieval with Context Data ----
        $contextData = [
            'intent_type'      => $intentType,
            'context_concepts' => $intentResult['context_concepts'] ?? [],
            'contextual_query' => $contextualQuery,
        ];
        $retrieval = RetrievalService::retrieve($userQuery, $contextData);

        // ---- STEP 5: Build structured sources ----
        $sources = [];
        foreach ($retrieval['stories'] as $i => $st) {
            $topic  = $st['topic_name'] ?? 'Life Experience';
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

        // ---- STEP 6: Handle insufficient evidence ----
        if ($retrieval['status'] === 'insufficient_evidence' || empty($retrieval['stories'])) {
            $answer   = self::buildLowEvidenceResponse($userQuery, $activeLang);
            $grounding = GroundingService::evaluate($retrieval, $userQuery, $answer);

            if ($persistDb) {
                ContextService::saveMessage($conversationId, 'user',      $userQuery, $contextualQuery, $intentType, null);
                ContextService::saveMessage($conversationId, 'assistant', $answer,    null,             $intentType, $grounding['score']);
            }

            $resp = [
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
                'conversation_id'   => $conversationId,
            ];

            if ($debugMode) {
                $resp['debug'] = [
                    'intent_type'      => $intentType,
                    'intent_confidence'=> $intentResult['confidence'],
                    'intent_reason'    => $intentResult['reason'],
                    'contextual_query' => $contextualQuery,
                    'context_concepts' => $intentResult['context_concepts'],
                    'retrieval_metrics'=> $retrieval['metrics'],
                ];
            }
            return $resp;
        }

        // ---- STEP 7: Construct story-level context text ----
        $contextText = '';
        foreach ($retrieval['stories'] as $i => $st) {
            $topic    = $st['topic_name'] ?? 'Life Experience';
            $excerpts = $st['structured_excerpts'] ?? [];

            $contextText .= '--- STORY ' . ($i + 1) . " (Topic: {$topic}) ---\n";
            if (!empty($excerpts['summary'])) {
                $contextText .= 'Summary: '        . $excerpts['summary']        . "\n";
            }
            if (!empty($excerpts['turning_point'])) {
                $contextText .= 'Turning Point: '  . $excerpts['turning_point']  . "\n";
            }
            if (!empty($excerpts['lesson'])) {
                $contextText .= 'Key Lesson: '     . $excerpts['lesson']         . "\n";
            }
            if (!empty($excerpts['advice'])) {
                $contextText .= 'Advice: '         . $excerpts['advice']         . "\n";
            }
            if (!empty($excerpts['quote'])) {
                $contextText .= 'Quote: '          . $excerpts['quote']          . "\n";
            }
            $contextText .= "\n";
        }

        // ---- STEP 8: Build conversation context block for LLM ----
        $conversationContextBlock = '';
        if ($intentType === 'FOLLOW_UP' && !empty($dbHistory)) {
            // Extract the very first user question to anchor the original topic
            $firstUserMsg = '';
            foreach ($dbHistory as $m) {
                if (($m['role'] ?? '') === 'user') {
                    $firstUserMsg = mb_substr($m['content'], 0, 200);
                    break;
                }
            }

            // Inject recent turns — limit to last 3 pairs (user+assistant) to keep prompt lean
            $recentTurns = array_slice($dbHistory, -6);
            $conversationContextBlock = "\n\n===CONVERSATION HISTORY (this is a follow-up — use this to understand context)===\n";
            if (!empty($firstUserMsg)) {
                $conversationContextBlock .= "Original topic: {$firstUserMsg}\n\n";
            }
            foreach ($recentTurns as $m) {
                $role    = ucfirst($m['role']);
                $snippet = mb_substr($m['content'] ?? '', 0, 250);
                $conversationContextBlock .= "{$role}: {$snippet}\n";
            }
            $conversationContextBlock .= "\n===END CONVERSATION HISTORY===\n";

            // Strong anti-repetition — pull exact phrases from last 2 assistant answers
            $prevAnswers = array_values(array_filter($dbHistory, fn($m) => $m['role'] === 'assistant'));
            if (!empty($prevAnswers)) {
                $lastTwo = array_slice($prevAnswers, -2);
                $conversationContextBlock .= "\nDO NOT REPEAT these themes or phrases already given:\n";
                foreach ($lastTwo as $i => $prev) {
                    $conversationContextBlock .= ($i + 1) . '. ' . mb_substr($prev['content'], 0, 300) . "...\n";
                }
            }
        } elseif (!empty($dbHistory)) {
            $prevAnswers = array_values(array_filter($dbHistory, fn($m) => $m['role'] === 'assistant'));
            if (!empty($prevAnswers)) {
                $lastTwo = array_slice($prevAnswers, -2);
                $conversationContextBlock = "\nThis is a fresh question. Do NOT repeat the following already-given advice:\n";
                foreach ($lastTwo as $i => $prev) {
                    $conversationContextBlock .= ($i + 1) . '. ' . mb_substr($prev['content'], 0, 250) . "...\n";
                }
            }
        }

        // ---- STEP 9: Language instruction ----
        $langInstruction = match($activeLang) {
            'hi' => 'LANGUAGE: Respond entirely in natural, warm, conversational Hindi (हिंदी) in Devanagari script.',
            'mr' => 'LANGUAGE: Respond entirely in natural, warm, conversational Marathi (मराठी) in Devanagari script.',
            default => '',
        };

        // ---- STEP 10: System prompt — conversational, natural, ChatGPT-style ----
        $followUpNote = $intentType === 'FOLLOW_UP'
            ? "This is a FOLLOW-UP question in an ongoing conversation. Refer to the CONVERSATION HISTORY section below to understand what the user is asking about. Stay tightly on the same topic — do not wander into unrelated areas."
            : '';

        $systemPrompt = <<<PROMPT
You are Ask LifeGPT — an AI powered by a real archive of human life stories, wisdom, and lived experience.

Your job is to answer the user's question like a thoughtful, well-read friend who has heard thousands of real human stories — NOT like a research assistant reading out case summaries.

HOW TO SOUND:
- Speak naturally and directly. Not every answer needs to start with a long framing paragraph.
- Vary your response length to match the question. A sharp focused question gets a direct answer. A big open-ended question gets more depth. Never pad to fill space.
- DO NOT open with "Career growth is..." or "It can feel like..." style generic scene-setting every single time. Get to the point.
- DO NOT close every answer with a bullet-list of "practical steps" or "putting these insights together..." — only add a list when it genuinely helps.
- Do NOT end with the same closing pattern every time. Each answer should feel fresh and unique.

HOW TO USE THE STORIES:
- The retrieved experiences are your source material. Synthesize them into your own voice — do NOT quote them like a report.
- NEVER say "A contributor shared...", "People who went through this reported...", "Several stories highlighted...", "One contributor reflected..." — this sounds clinical and repetitive.
- Instead, speak the insight directly: "What actually works, from what we've seen, is...", "The honest pattern is...", "Something that comes up again and again is...", "Here's the thing most people discover..."
- Draw on the specific situations, decisions, and turning points from the stories — but blend them naturally, not as numbered examples.

FOR FOLLOW-UP QUESTIONS:
- Stay tightly on the topic from the conversation history. Do not retrieve or introduce themes from unrelated life domains.
- Answer only what was asked. Do not repeat advice already given.
- Build on what was said before, not re-explain it.

STRICT RULES:
1. Answer ONLY based on what the retrieved stories actually support. Do not invent experiences.
2. NEVER use real names. Refer to people as "someone who went through this", "people in this situation", etc.
3. No markdown formatting (no **, ##, bullet points with dashes). Write in clean readable paragraphs.
4. Return a JSON object with a single key: {"answer": "...your full response here..."}
{$followUpNote}
{$langInstruction}
{$conversationContextBlock}
PROMPT;

        // User message content — for FOLLOW_UP queries, include the rewritten query label so LLM understands
        $userMsgContent = $intentType === 'FOLLOW_UP' && $contextualQuery !== $userQuery
            ? "Retrieved LifeGPT Experience Stories:\n{$contextText}\n\nUser's Follow-Up Question: {$userQuery}\n(Rewritten for clarity: {$contextualQuery})"
            : "Retrieved LifeGPT Experience Stories:\n{$contextText}\n\nUser Question: {$userQuery}";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userMsgContent],
        ];

        // ---- STEP 11: LLM synthesis ----
        try {
            $apiRes    = OpenAIClient::chatCompletion($messages, [
                'type'       => 'object',
                'properties' => ['answer' => ['type' => 'string']],
                'required'   => ['answer'],
            ]);
            $rawAnswer = $apiRes['answer'] ?? '';
            $answer    = self::sanitizeAnswer($rawAnswer);
        } catch (Exception $e) {
            $answer = self::buildFallbackSynthesizedAnswer($retrieval['stories'], $activeLang);
        }

        // ---- STEP 12: Grounding Evaluation ----
        $grounding = GroundingService::evaluate($retrieval, $userQuery, $answer);

        // ---- STEP 13: Persist messages to DB ----
        if ($persistDb) {
            $debugInfo = $debugMode ? [
                'intent_type'      => $intentType,
                'intent_confidence'=> $intentResult['confidence'],
                'intent_reason'    => $intentResult['reason'],
                'contextual_query' => $contextualQuery,
                'context_concepts' => $intentResult['context_concepts'],
                'retrieval_metrics'=> $retrieval['metrics'],
            ] : null;

            ContextService::saveMessage(
                $conversationId, 'user', $userQuery,
                $contextualQuery, $intentType, null, $debugInfo
            );
            ContextService::saveMessage(
                $conversationId, 'assistant', $answer,
                null, $intentType, $grounding['score'], null
            );

            // Regenerate summary if conversation is long
            $msgCount = (int)($conversationRow['message_count'] ?? 0) + 2;
            if ($msgCount >= $summaryTurns * 2) {
                $allHistory = ContextService::getHistory($conversationId, $summaryTurns);
                $newSummary = ContextService::updateSummary($allHistory);
                if (!empty($newSummary)) {
                    ContextService::updateConversationSummary($conversationId, $newSummary);
                }
            }
        }

        // ---- STEP 14: Build response ----
        $resp = [
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
            'conversation_id'   => $conversationId,
            'intent_type'       => $intentType,
        ];

        if ($debugMode) {
            $resp['debug'] = [
                'intent_type'       => $intentType,
                'intent_confidence' => $intentResult['confidence'],
                'intent_reason'     => $intentResult['reason'],
                'contextual_query'  => $contextualQuery,
                'context_concepts'  => $intentResult['context_concepts'],
                'retrieval_metrics' => $retrieval['metrics'],
            ];
        }

        return $resp;
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

<?php
/**
 * LifeGPT - Context Service
 * Handles multi-turn conversation context management:
 *   - Intent classification: FOLLOW_UP vs STANDALONE
 *   - Contextual query rewriting before RAG retrieval
 *   - Conversation summary generation for long threads (>8 turns)
 *   - Persistent DB storage in lg_ask_conversations / lg_ask_messages
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../openai.php';

class ContextService {

    // ---------------------------------------------------------------------------
    // Follow-up detection signals
    // ---------------------------------------------------------------------------

    /** Strong pronoun/reference words that almost certainly reference prior context
     *  Note: Avoid common English words like 'we', 'our', 'he', 'she' which appear
     *  in everyday queries and cause false-positive FOLLOW_UP classifications.
     */
    private static array $STRONG_FOLLOW_UP_TOKENS = [
        'that', 'this', 'it', 'they', 'them', 'their', 'those', 'these',
        'my decision', 'the same', 'the decision',
        'the issue', 'the situation', 'the problem', 'the choice'
    ];

    /** Interrogative opening patterns that are almost always follow-ups in context */
    private static array $FOLLOW_UP_STARTERS = [
        'what if', 'what about', 'how about', 'but what',
        'why did', 'why would', 'why not', 'why?',
        'how so', 'how does', 'could you', 'can you elaborate',
        'tell me more', 'give me more', 'what else',
        'and if', 'and what', 'and how', 'also', 'additionally',
        'in that case', 'in this case', 'if so', 'if not',
        'what happens then', 'then what', 'so then',
    ];

    /** Single or very short queries likely to be follow-ups */
    private const VERY_SHORT_QUERY_WORDS = 5;

    /** Topic-switch indicators — if both appear the query is a new topic */
    private static array $TOPIC_DOMAINS = [
        'career'        => ['job', 'career', 'work', 'profession', 'salary', 'quit', 'resign', 'retire', 'boss', 'promotion'],
        'family'        => ['parent', 'family', 'marriage', 'divorce', 'child', 'kids', 'mother', 'father', 'husband', 'wife'],
        'health'        => ['health', 'illness', 'hospital', 'doctor', 'surgery', 'weight', 'exercise', 'pain', 'disease'],
        'money'         => ['money', 'debt', 'savings', 'invest', 'loan', 'bankrupt', 'rich', 'poor', 'financial'],
        'grief'         => ['grief', 'death', 'died', 'mourn', 'loss', 'funeral', 'passed away'],
        'mental_health' => ['anxiety', 'depression', 'stress', 'mental', 'therapy', 'burnout', 'overwhelm'],
        'business'      => ['business', 'startup', 'entrepreneur', 'founder', 'company', 'sales', 'client'],
        'education'     => ['study', 'college', 'university', 'degree', 'course', 'school', 'scholarship'],
        'purpose'       => ['purpose', 'meaning', 'passion', 'direction', 'fulfillment', 'existential', 'identity'],
        'relationship'  => ['friend', 'relationship', 'breakup', 'partner', 'love', 'dating', 'trust', 'communication'],
    ];

    // ---------------------------------------------------------------------------
    // PUBLIC API
    // ---------------------------------------------------------------------------

    /**
     * Classify a query as FOLLOW_UP or STANDALONE given recent conversation history.
     *
     * Returns:
     *   [
     *     'intent_type'        => 'FOLLOW_UP' | 'STANDALONE',
     *     'confidence'         => 0.0-1.0,
     *     'reason'             => string,
     *     'is_topic_switch'    => bool,
     *     'context_concepts'   => string[]   // keywords extracted from prior context
     *   ]
     */
    public static function detectIntent(string $currentQuery, array $recentHistory): array {
        $query = mb_strtolower(trim($currentQuery));

        // No history — always standalone
        if (empty($recentHistory)) {
            return self::buildIntentResult('STANDALONE', 0.95, 'No conversation history', false, []);
        }

        // Extract previous assistant messages as context
        $prevAssistant = array_values(array_filter($recentHistory, fn($m) => ($m['role'] ?? '') === 'assistant'));
        $prevUser      = array_values(array_filter($recentHistory, fn($m) => ($m['role'] ?? '') === 'user'));

        if (empty($prevAssistant)) {
            return self::buildIntentResult('STANDALONE', 0.90, 'No prior assistant turn', false, []);
        }

        // --- Signal 1: Strong pronoun / reference token match ---
        // Use word-boundary matching for short tokens to prevent substring false-positives
        // (e.g. 'it' inside 'weight', 'this' inside 'something', 'that' inside 'what')
        foreach (self::$STRONG_FOLLOW_UP_TOKENS as $token) {
            if (mb_strlen($token) <= 4) {
                // Word-boundary match: token must appear as a whole word
                if (preg_match('/\b' . preg_quote($token, '/') . '\b/iu', $query)) {
                    $concepts = self::extractContextConcepts($prevAssistant, $prevUser);
                    return self::buildIntentResult('FOLLOW_UP', 0.90, "Reference token detected: '{$token}'", false, $concepts);
                }
            } else {
                // Longer multi-word tokens are safe with simple substring match
                if (mb_strpos($query, $token) !== false) {
                    $concepts = self::extractContextConcepts($prevAssistant, $prevUser);
                    return self::buildIntentResult('FOLLOW_UP', 0.90, "Reference token detected: '{$token}'", false, $concepts);
                }
            }
        }

        // --- Signal 2: Follow-up starter phrases ---
        foreach (self::$FOLLOW_UP_STARTERS as $starter) {
            if (mb_strpos($query, $starter) !== false) {
                $concepts = self::extractContextConcepts($prevAssistant, $prevUser);
                return self::buildIntentResult('FOLLOW_UP', 0.85, "Follow-up starter phrase: '{$starter}'", false, $concepts);
            }
        }

        // --- Signal 3: Very short query (≤5 words) — likely continuation ---
        $wordCount = count(preg_split('/\s+/u', trim($query), -1, PREG_SPLIT_NO_EMPTY));
        if ($wordCount <= self::VERY_SHORT_QUERY_WORDS && count($recentHistory) >= 2) {
            $concepts = self::extractContextConcepts($prevAssistant, $prevUser);
            return self::buildIntentResult('FOLLOW_UP', 0.75, "Query is very short ({$wordCount} words) in ongoing conversation", false, $concepts);
        }

        // --- Signal 4: Topic domain switch detection ---
        $prevDomains    = self::detectTopicDomains(self::getHistoryText($prevUser));
        $currentDomains = self::detectTopicDomains($query);

        if (!empty($prevDomains) && !empty($currentDomains)) {
            $overlap = array_intersect($prevDomains, $currentDomains);
            if (empty($overlap)) {
                // New topic — classify as STANDALONE
                return self::buildIntentResult('STANDALONE', 0.80, 'Topic domain switched: ' . implode(',', $currentDomains), true, []);
            }
            // Same domain — FOLLOW_UP
            $concepts = self::extractContextConcepts($prevAssistant, $prevUser);
            return self::buildIntentResult('FOLLOW_UP', 0.70, 'Same topic domain: ' . implode(',', $overlap), false, $concepts);
        }

        // --- Signal 5: Query has substantial unique content — standalone ---
        if ($wordCount > 12) {
            return self::buildIntentResult('STANDALONE', 0.65, 'Query is long and self-contained', false, []);
        }

        // Default: treat as follow-up in ongoing conversation
        $concepts = self::extractContextConcepts($prevAssistant, $prevUser);
        return self::buildIntentResult('FOLLOW_UP', 0.60, 'Default follow-up in ongoing session', false, $concepts);
    }

    /**
     * Rewrite an ambiguous follow-up question into an explicit, self-contained query
     * suitable for accurate RAG semantic retrieval.
     *
     * If the query is STANDALONE, returns the original unchanged.
     * Uses the Groq LLM for rewriting (falls back to heuristic if API unavailable).
     *
     * @param string      $currentQuery  The raw user query
     * @param array       $recentHistory Recent [role, content] messages
     * @param string|null $summary       Conversation summary for long threads
     * @param array       $intentResult  Output from detectIntent()
     */
    public static function generateContextualQuery(
        string $currentQuery,
        array  $recentHistory,
        ?string $summary,
        array  $intentResult
    ): string {
        // No rewriting needed for standalone queries
        if ($intentResult['intent_type'] !== 'FOLLOW_UP') {
            return $currentQuery;
        }

        // Build a compact history snippet to send with LLM rewrite request
        $historySnippet = self::buildHistorySnippet($recentHistory, 4);
        $summaryPart    = $summary ? "Conversation Summary:\n{$summary}\n\n" : '';

        $systemMsg = <<<EOT
You are a query rewriting assistant for a life advice system called LifeGPT.
Your task is to take a follow-up question that references prior conversation context and rewrite it as a fully self-contained, explicit question that can be understood without any conversation history.

Rules:
- Resolve ALL pronouns and references (they, that, it, my decision, the situation, etc.) using context
- Keep the rewritten query concise (1-2 sentences)
- Preserve the user's original intent
- Do NOT add assumptions not present in the conversation
- Return JSON: {"contextual_query": "..."}
EOT;

        $userMsg = <<<EOT
{$summaryPart}Recent conversation:
{$historySnippet}

Follow-up question to rewrite: "{$currentQuery}"
EOT;

        try {
            $res = OpenAIClient::chatCompletion([
                ['role' => 'system', 'content' => $systemMsg],
                ['role' => 'user',   'content' => $userMsg],
            ], [
                'type'       => 'object',
                'properties' => ['contextual_query' => ['type' => 'string']],
                'required'   => ['contextual_query'],
            ]);

            $rewritten = trim($res['contextual_query'] ?? '');
            if (!empty($rewritten) && mb_strlen($rewritten) >= 10) {
                return $rewritten;
            }
        } catch (Exception $e) {
            // LLM unavailable — use heuristic fallback
        }

        // Heuristic fallback: prepend context concepts to the query
        $concepts = $intentResult['context_concepts'] ?? [];
        if (!empty($concepts)) {
            $conceptStr = implode(', ', array_slice($concepts, 0, 5));
            return "Regarding {$conceptStr}: {$currentQuery}";
        }

        return $currentQuery;
    }

    /**
     * Generate a compact conversation summary for threads exceeding the summary threshold.
     * Called by RagPipeline when message_count > summary_threshold_turns.
     */
    public static function updateSummary(array $conversationHistory): string {
        if (empty($conversationHistory)) {
            return '';
        }

        $transcript = self::buildHistorySnippet($conversationHistory, 20);

        $systemMsg = 'You are a conversation summarizer for a life advice AI. Produce a concise 3-5 sentence summary of the key topics discussed, decisions explored, and advice given. Return JSON: {"summary": "..."}';

        try {
            $res = OpenAIClient::chatCompletion([
                ['role' => 'system', 'content' => $systemMsg],
                ['role' => 'user',   'content' => "Conversation transcript:\n{$transcript}"],
            ], [
                'type'       => 'object',
                'properties' => ['summary' => ['type' => 'string']],
                'required'   => ['summary'],
            ]);
            return trim($res['summary'] ?? '');
        } catch (Exception $e) {
            // Fallback: extract last few messages
            $last = array_slice($conversationHistory, -4);
            return implode(' | ', array_map(fn($m) => mb_substr($m['content'] ?? '', 0, 100), $last));
        }
    }

    // ---------------------------------------------------------------------------
    // DB PERSISTENCE HELPERS
    // ---------------------------------------------------------------------------

    /**
     * Generate a UUID v4 string
     */
    public static function generateUUID(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Get or create a conversation record, returning the conversation row.
     */
    public static function getOrCreateConversation(string $conversationId, ?int $userId = null): array {
        $existing = DB::fetch(
            'SELECT * FROM lg_ask_conversations WHERE conversation_id = :cid',
            ['cid' => $conversationId]
        );

        if ($existing) {
            return $existing;
        }

        DB::insert(
            'INSERT INTO lg_ask_conversations (conversation_id, user_id, message_count, created_at, updated_at)
             VALUES (:cid, :uid, 0, NOW(), NOW())',
            ['cid' => $conversationId, 'uid' => $userId]
        );

        return [
            'conversation_id' => $conversationId,
            'user_id'         => $userId,
            'summary'         => null,
            'topic_label'     => null,
            'message_count'   => 0,
        ];
    }

    /**
     * Retrieve recent messages for a conversation_id, ordered oldest-first.
     * Limits to max_history_turns * 2 messages (each turn = user + assistant).
     */
    public static function getHistory(string $conversationId, int $maxTurns = 6): array {
        $limit = $maxTurns * 2;
        $rows  = DB::fetchAll(
            'SELECT role, content, intent_type, contextual_query, grounding_score, created_at
             FROM lg_ask_messages
             WHERE conversation_id = :cid
             ORDER BY message_id DESC
             LIMIT :lim',
            ['cid' => $conversationId, 'lim' => $limit]
        );
        // Reverse so oldest is first
        return array_reverse($rows);
    }

    /**
     * Persist a single message (user or assistant) to lg_ask_messages.
     * Also increments the conversation message_count.
     */
    public static function saveMessage(
        string  $conversationId,
        string  $role,
        string  $content,
        ?string $contextualQuery = null,
        ?string $intentType = null,
        ?int    $groundingScore = null,
        ?array  $debugInfo = null
    ): void {
        DB::query(
            'INSERT INTO lg_ask_messages
                (conversation_id, role, content, contextual_query, intent_type, grounding_score, debug_info, created_at)
             VALUES
                (:cid, :role, :content, :cq, :it, :gs, :di, NOW())',
            [
                'cid'     => $conversationId,
                'role'    => $role,
                'content' => $content,
                'cq'      => $contextualQuery,
                'it'      => $intentType,
                'gs'      => $groundingScore,
                'di'      => $debugInfo ? json_encode($debugInfo) : null,
            ]
        );

        DB::query(
            'UPDATE lg_ask_conversations
             SET message_count = message_count + 1, updated_at = NOW()
             WHERE conversation_id = :cid',
            ['cid' => $conversationId]
        );
    }

    /**
     * Update the conversation summary text (called after summary regeneration).
     */
    public static function updateConversationSummary(string $conversationId, string $summary): void {
        DB::query(
            'UPDATE lg_ask_conversations SET summary = :s, updated_at = NOW() WHERE conversation_id = :cid',
            ['s' => $summary, 'cid' => $conversationId]
        );
    }

    // ---------------------------------------------------------------------------
    // PRIVATE HELPERS
    // ---------------------------------------------------------------------------

    private static function buildIntentResult(
        string $intentType,
        float  $confidence,
        string $reason,
        bool   $isTopicSwitch,
        array  $contextConcepts
    ): array {
        return [
            'intent_type'      => $intentType,
            'confidence'       => $confidence,
            'reason'           => $reason,
            'is_topic_switch'  => $isTopicSwitch,
            'context_concepts' => $contextConcepts,
        ];
    }

    /**
     * Extract key concept words from recent assistant answers and user questions.
     */
    private static function extractContextConcepts(array $assistantMessages, array $userMessages): array {
        $allText = '';
        foreach (array_slice($assistantMessages, -2) as $m) {
            $allText .= ' ' . ($m['content'] ?? '');
        }
        foreach (array_slice($userMessages, -2) as $m) {
            $allText .= ' ' . ($m['content'] ?? '');
        }

        $stopWords = [
            'the','and','are','for','how','what','you','your','that','with','have','this',
            'from','they','been','does','did','can','but','not','all','was','who','why',
            'when','will','should','would','could','its','our','their','has','had','about',
            'tell','some','give','best','into','than','then','also','more','i','me','my',
            'a','an','is','in','of','to','it','be','at','by','on','as','we','or','do',
            'if','so','no','up','go','he','she','his','her','him','them','just','there',
            'here','any','each','now','may','very','well','like','back','life','time',
            'people','contributor','experience','contributors','shared','said','one',
        ];

        $words = preg_split('/[\s,\.;:!\?\(\)]+/u', mb_strtolower($allText), -1, PREG_SPLIT_NO_EMPTY);
        $concepts = array_values(array_unique(array_diff($words, $stopWords)));

        // Filter out very short or numeric words
        $concepts = array_filter($concepts, fn($w) => mb_strlen($w) >= 4 && !is_numeric($w));

        // Sort by frequency
        $freq = array_count_values($concepts);
        arsort($freq);

        return array_slice(array_keys($freq), 0, 10);
    }

    /**
     * Detect topic domain(s) from text using keyword matching.
     */
    private static function detectTopicDomains(string $text): array {
        $text   = mb_strtolower($text);
        $found  = [];
        foreach (self::$TOPIC_DOMAINS as $domain => $keywords) {
            foreach ($keywords as $kw) {
                if (mb_strpos($text, $kw) !== false) {
                    $found[] = $domain;
                    break;
                }
            }
        }
        return array_unique($found);
    }

    /**
     * Build a plain-text snippet of conversation history for LLM prompts.
     */
    private static function buildHistorySnippet(array $history, int $maxMessages = 6): string {
        $recent = array_slice($history, -$maxMessages);
        $lines  = [];
        foreach ($recent as $msg) {
            $role    = ucfirst($msg['role'] ?? 'user');
            $content = mb_substr($msg['content'] ?? '', 0, 300);
            $lines[] = "{$role}: {$content}";
        }
        return implode("\n", $lines);
    }

    /**
     * Get concatenated text from a message array.
     */
    private static function getHistoryText(array $messages): string {
        return implode(' ', array_map(fn($m) => $m['content'] ?? '', $messages));
    }
}

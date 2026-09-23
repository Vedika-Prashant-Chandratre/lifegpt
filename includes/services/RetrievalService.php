<?php
/**
 * LifeGPT - Retrieval Service
 * Implements hybrid semantic + keyword retrieval, query intent expansion,
 * story-level grouping, diversity re-ranking, and relevance thresholding.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../openai.php';
require_once __DIR__ . '/EmbeddingService.php';

class RetrievalService {
    private static ?array $config = null;

    private static function getConfig(): array {
        if (self::$config === null) {
            $configPath = __DIR__ . '/../../config/rag.php';
            self::$config = file_exists($configPath) ? require $configPath : [];
        }
        return self::$config;
    }

    /**
     * Main retrieval method: given a user query, returns ranked, deduplicated, story-level context
     *
     * @param string $userQuery     Raw or rewritten user question
     * @param array  $contextData   Optional context from ContextService:
     *                               - contextual_query string   (LLM-rewritten query for retrieval)
     *                               - intent_type      string   ('FOLLOW_UP' | 'STANDALONE')
     *                               - context_concepts string[] (keywords from prior conversation)
     * @return array{
     *    status: string,               // 'grounded' | 'insufficient_evidence'
     *    stories: array,               // Grouped and reconstructed stories
     *    chunks: array,                // Top ranked chunks
     *    metrics: array,               // Semantic, keyword, and hybrid metrics
     *    intent: array                 // Extracted intent & matched themes
     * }
     */
    public static function retrieve(string $userQuery, array $contextData = []): array {
        $cfg = self::getConfig();
        $cfgWeights = $cfg['hybrid_weights'] ?? [
            'semantic'          => 0.70,
            'keyword'           => 0.20,
            'theme'             => 0.10,
            'context_relevance' => 0.00,
        ];
        $thresholds = $cfg['thresholds'] ?? ['min_relevance_threshold' => 0.45, 'min_story_count' => 2, 'max_story_count' => 8];

        // Determine whether context-aware or standalone weights apply
        $isFollowUp      = ($contextData['intent_type'] ?? 'STANDALONE') === 'FOLLOW_UP';
        $contextConcepts = $contextData['context_concepts'] ?? [];
        $contextualQuery = $contextData['contextual_query'] ?? '';

        if ($isFollowUp && !empty($contextConcepts)) {
            $hybridWeights = [
                'semantic'          => $cfgWeights['semantic']          ?? 0.60,
                'keyword'           => $cfgWeights['keyword']           ?? 0.15,
                'theme'             => $cfgWeights['theme']             ?? 0.10,
                'context_relevance' => $cfgWeights['context_relevance'] ?? 0.15,
            ];
        } else {
            // No context boost — use original 3-component weights, padded to 1.0
            $hybridWeights = [
                'semantic'          => 0.70,
                'keyword'           => 0.20,
                'theme'             => 0.10,
                'context_relevance' => 0.00,
            ];
        }

        // 1. Clean & normalize query
        // Use contextual (rewritten) query for retrieval when available
        $cleanQuery = !empty($contextualQuery) ? trim($contextualQuery) : trim($userQuery);
        if (empty($cleanQuery)) {
            return self::emptyResult('insufficient_evidence', 'Empty query');
        }

        // 1b. If query contains Devanagari script (Hindi/Marathi), translate for English archive retrieval
        if (preg_match('/[\x{0900}-\x{097F}]/u', $cleanQuery)) {
            try {
                $transRes = OpenAIClient::chatCompletion([
                    ['role' => 'system', 'content' => 'Translate this Hindi or Marathi question into a concise English search query for life advice retrieval. Return JSON: {"english_query": "..."}'],
                    ['role' => 'user', 'content' => $cleanQuery]
                ], [
                    'type' => 'object',
                    'properties' => ['english_query' => ['type' => 'string']],
                    'required' => ['english_query']
                ]);
                if (!empty($transRes['english_query'])) {
                    $cleanQuery = trim($transRes['english_query']);
                }
            } catch (Exception $e) {
                // Continue with original query if offline
            }
        }

        // 2. Query expansion & intent detection
        $intent = self::analyzeIntent($cleanQuery);

        // 3. Generate query embedding
        $queryVector = EmbeddingService::embedText($cleanQuery);

        // 4. Load all eligible RAG chunks with strict consent and approval filters
        // Exclusion rules: status='approved' AND approved_for_rag=1 AND rag_consent=1 AND withdrawn=0
        $eligibleChunks = self::loadEligibleChunks();
        if (empty($eligibleChunks)) {
            return self::emptyResult('insufficient_evidence', 'No eligible RAG chunks in archive');
        }

        // 5. Compute Semantic Similarity, Keyword, Theme & Context Relevance Scores
        $scoredCandidates = [];
        $queryKeywords = $intent['keywords'];

        foreach ($eligibleChunks as $chunk) {
            // A. Semantic score via Cosine Similarity
            $chunkVector = !empty($chunk['embedding']) ? json_decode($chunk['embedding'], true) : null;
            if (is_array($chunkVector)) {
                $semanticSim = EmbeddingService::cosineSimilarity($queryVector, $chunkVector);
                // Rescale cosine similarity from [-1, 1] to [0, 1]
                $semanticScore = (float)max(0.0, min(1.0, ($semanticSim + 1.0) / 2.0));
            } else {
                $semanticScore = 0.0;
            }

            // B. Keyword & N-Gram match score
            $chunkText = mb_strtolower($chunk['anonymized_text']);
            $kwMatches = 0;
            foreach ($queryKeywords as $kw) {
                if (mb_stripos($chunkText, $kw) !== false) {
                    $kwMatches++;
                }
            }
            $keywordScore = count($queryKeywords) > 0 ? min(1.0, $kwMatches / count($queryKeywords)) : 0.0;

            // C. Theme / Intent match score
            $themeScore = 0.0;
            if (!empty($intent['matched_topic_ids']) && in_array($chunk['topic_id'], $intent['matched_topic_ids'])) {
                $themeScore = 1.0;
            } elseif (!empty($intent['matched_theme_names'])) {
                foreach ($intent['matched_theme_names'] as $tName) {
                    if (mb_stripos($chunk['topic_name'] ?? '', $tName) !== false) {
                        $themeScore = 0.8;
                        break;
                    }
                }
            }

            // D. Context Relevance Score — measures chunk overlap with prior conversation concepts
            $contextScore = 0.0;
            if ($isFollowUp && !empty($contextConcepts)) {
                $conceptMatches = 0;
                foreach ($contextConcepts as $concept) {
                    if (mb_strlen($concept) >= 3 && mb_stripos($chunkText, $concept) !== false) {
                        $conceptMatches++;
                    }
                }
                $contextScore = min(1.0, $conceptMatches / max(1, count($contextConcepts)));
            }

            // E. Compute normalized hybrid score (all 4 components)
            $rawHybrid = (
                ($hybridWeights['semantic']          * $semanticScore) +
                ($hybridWeights['keyword']           * $keywordScore) +
                ($hybridWeights['theme']             * $themeScore) +
                ($hybridWeights['context_relevance'] * $contextScore)
            );

            // Penalize if no query keyword and no theme overlap (off-topic guard)
            if ($keywordScore <= 0.0 && $themeScore <= 0.0 && $contextScore <= 0.0) {
                $rawHybrid *= 0.65;
            }

            $hybridScore = $rawHybrid;

            $chunk['semantic_score']  = round($semanticScore, 4);
            $chunk['keyword_score']   = round($keywordScore, 4);
            $chunk['theme_score']     = round($themeScore, 4);
            $chunk['context_score']   = round($contextScore, 4);
            $chunk['hybrid_score']    = round($hybridScore, 4);

            $scoredCandidates[] = $chunk;
        }

        // Sort candidates by hybrid score descending
        usort($scoredCandidates, fn($a, $b) => $b['hybrid_score'] <=> $a['hybrid_score']);

        // Keep top candidate pool
        $poolLimit = $thresholds['candidate_chunk_pool'] ?? 30;
        $topPool = array_slice($scoredCandidates, 0, $poolLimit);

        // 6. Story-Level Aggregation by interview_id
        $stories = self::groupByStory($topPool);

        // 7. Diversity Re-ranking
        // Re-rank stories by overall story relevance and penalize duplicate interviews
        usort($stories, function ($a, $b) {
            return $b['story_relevance'] <=> $a['story_relevance'];
        });

        // 8. Apply Relevance Threshold Gate
        $minThreshold = (float)($thresholds['min_relevance_threshold'] ?? 0.45);
        $filteredStories = [];

        foreach ($stories as $story) {
            if ($story['story_relevance'] >= $minThreshold) {
                $filteredStories[] = $story;
            }
        }

        // Limit selected stories between min_story_count and max_story_count
        $maxStories = $thresholds['max_story_count'] ?? 6;
        $finalStories = array_slice($filteredStories, 0, $maxStories);

        $retrievalStatus = (!empty($finalStories)) ? 'grounded' : 'insufficient_evidence';

        // Collect representative chunks from the final stories
        $finalChunks = [];
        foreach ($finalStories as $st) {
            foreach ($st['chunks'] as $ch) {
                $finalChunks[] = $ch;
            }
        }

        // Summary metrics
        $bestSemantic = !empty($finalChunks) ? max(array_column($finalChunks, 'semantic_score')) : 0.0;
        $bestKeyword  = !empty($finalChunks) ? max(array_column($finalChunks, 'keyword_score'))  : 0.0;
        $bestContext  = !empty($finalChunks) ? max(array_column($finalChunks, 'context_score'))  : 0.0;
        $bestHybrid   = !empty($finalStories) ? $finalStories[0]['story_relevance'] : 0.0;

        return [
            'status'  => $retrievalStatus,
            'stories' => $finalStories,
            'chunks'  => $finalChunks,
            'metrics' => [
                'best_semantic_score'  => $bestSemantic,
                'best_keyword_score'   => $bestKeyword,
                'best_context_score'   => $bestContext,
                'best_hybrid_score'    => $bestHybrid,
                'total_candidates'     => count($topPool),
                'selected_stories'     => count($finalStories),
                'threshold'            => $minThreshold,
                'context_mode'         => $isFollowUp ? 'follow_up' : 'standalone',
                'contextual_query'     => !empty($contextualQuery) ? $contextualQuery : null,
            ],
            'intent'  => $intent,
        ];
    }

    /**
     * Load eligible knowledge chunks using strict consent and approval checks
     */
    private static function loadEligibleChunks(): array {
        $sql = "
            SELECT 
                kc.chunk_id, 
                kc.interview_id, 
                kc.content_type, 
                kc.anonymized_text, 
                kc.embedding, 
                kc.embedding_model,
                i.topic_id,
                t.name as topic_name,
                c.attribution_type
            FROM lg_knowledge_chunks kc
            JOIN lg_interviews i ON kc.interview_id = i.interview_id
            LEFT JOIN lg_interview_topics t ON i.topic_id = t.topic_id
            JOIN lg_consents c ON i.interview_id = c.interview_id
            WHERE kc.status = 'approved'
              AND kc.approved_for_rag = 1
              AND c.rag_consent = 1
              AND c.withdrawn = 0
              AND CHAR_LENGTH(TRIM(kc.anonymized_text)) >= 25
              AND LOWER(TRIM(kc.anonymized_text)) NOT LIKE 'greeting%'
        ";
        return DB::fetchAll($sql);
    }

    /**
     * Analyze user query to extract intent, concepts, and matching topic IDs
     */
    private static function analyzeIntent(string $query): array {
        $clean = mb_strtolower(trim($query));
        $cleanTokens = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $clean);
        $words = preg_split('/\s+/u', $cleanTokens, -1, PREG_SPLIT_NO_EMPTY);

        $stopWords = [
            'the','and','are','for','how','what','you','your','that','with','have','this',
            'from','they','been','does','did','can','but','not','all','was','who','why',
            'when','will','should','would','could','its','our','their','has','had','about',
            'tell','some','give','best','into','than','then','also','more','i','me','my'
        ];

        $keywords = array_values(array_diff($words, $stopWords));

        // Concept mappings for intent expansion
        $intentMap = [
            'career' => ['job', 'work', 'career', 'promotion', 'switch', 'profession', 'boss', 'interview', 'quit', 'retire', 'corporate'],
            'family' => ['parent', 'parents', 'mother', 'father', 'mom', 'dad', 'family', 'children', 'child', 'kid', 'kids', 'marriage', 'wife', 'husband', 'partner'],
            'money'  => ['money', 'finance', 'financial', 'invest', 'saving', 'savings', 'debt', 'salary', 'wealth', 'pension', 'index fund'],
            'health' => ['health', 'body', 'injury', 'aging', 'pain', 'exercise', 'doctor', 'hospital', 'illness', 'fitness'],
            'grief'  => ['grief', 'loss', 'died', 'death', 'mourn', 'passed away', 'lost a loved one', 'funeral', 'heartbreak'],
            'failure'=> ['fail', 'failure', 'mistake', 'bounce back', 'bankrupt', 'regret', 'wrong choice', 'lost everything'],
            'purpose'=> ['purpose', 'meaning', 'lost', 'direction', 'fulfillment', 'volunteer', 'passion', 'existential'],
            'business'=> ['business', 'entrepreneur', 'startup', 'client', 'sales', 'company', 'founder', 'venture'],
        ];

        $matchedThemes = [];
        foreach ($intentMap as $theme => $tokens) {
            foreach ($tokens as $tok) {
                if (mb_stripos($clean, $tok) !== false) {
                    $matchedThemes[] = $theme;
                    break;
                }
            }
        }

        // Match against database topic IDs
        $topics = DB::fetchAll("SELECT topic_id, name FROM lg_interview_topics WHERE active = 1");
        $matchedTopicIds = [];
        foreach ($topics as $tp) {
            $tName = mb_strtolower($tp['name']);
            foreach ($matchedThemes as $mTheme) {
                if (mb_stripos($tName, $mTheme) !== false) {
                    $matchedTopicIds[] = (int)$tp['topic_id'];
                }
            }
        }

        return [
            'keywords'            => $keywords,
            'matched_themes'      => array_unique($matchedThemes),
            'matched_topic_ids'   => array_unique($matchedTopicIds),
            'matched_theme_names' => array_unique($matchedThemes),
        ];
    }

    /**
     * Group candidate chunks by interview_id and reconstruct story context
     */
    private static function groupByStory(array $candidateChunks): array {
        $storyGroups = [];

        foreach ($candidateChunks as $chunk) {
            $intId = $chunk['interview_id'];
            if (!isset($storyGroups[$intId])) {
                $storyGroups[$intId] = [
                    'interview_id' => $intId,
                    'topic_name'   => $chunk['topic_name'] ?? 'Life Experience',
                    'chunks'       => [],
                    'scores'       => [],
                ];
            }
            $storyGroups[$intId]['chunks'][] = $chunk;
            $storyGroups[$intId]['scores'][] = $chunk['hybrid_score'];
        }

        $reconstructedStories = [];
        foreach ($storyGroups as $intId => $data) {
            $scores = $data['scores'];
            $maxScore = !empty($scores) ? max($scores) : 0.0;
            $avgScore = !empty($scores) ? (array_sum($scores) / count($scores)) : 0.0;

            // Story relevance combines the peak matching chunk with the depth of relevant chunks
            $storyRelevance = round(($maxScore * 0.70) + ($avgScore * 0.30), 4);

            // Structure story excerpts by content type
            $structuredExcerpts = [];
            foreach ($data['chunks'] as $c) {
                $type = $c['content_type'];
                $structuredExcerpts[$type] = $c['anonymized_text'];
            }

            $reconstructedStories[] = [
                'interview_id'      => $intId,
                'topic_name'        => $data['topic_name'],
                'story_relevance'   => $storyRelevance,
                'chunks_count'      => count($data['chunks']),
                'structured_excerpts' => $structuredExcerpts,
                'chunks'            => $data['chunks'],
            ];
        }

        return $reconstructedStories;
    }

    private static function emptyResult(string $status, string $reason): array {
        return [
            'status'  => $status,
            'stories' => [],
            'chunks'  => [],
            'metrics' => [
                'best_semantic_score' => 0.0,
                'best_keyword_score'  => 0.0,
                'best_hybrid_score'   => 0.0,
                'total_candidates'    => 0,
                'selected_stories'    => 0,
                'threshold'           => 0.45,
                'reason'              => $reason,
            ],
            'intent' => ['keywords' => []],
        ];
    }
}

<?php
/**
 * LifeGPT - RAG (Retrieval-Augmented Generation) Pipeline Configuration
 * Centralized settings for embedding generation, hybrid retrieval, story re-ranking,
 * relevance thresholds, and programmatic grounding score calculation.
 */

return [
    // --- 1. Embedding Provider Settings ---
    // Uses the local high-dimensional semantic vectorizer by default (100% free, zero paid API).
    // If an OpenAI API key (sk-...) is configured, can optionally use OpenAI text-embedding-3-small.
    'embedding' => [
        'provider' => 'local_semantic', // 'local_semantic' or 'openai'
        'model' => 'local-semantic-tfidf-v1',
        'dimension' => 256,
        'batch_size' => 25,
        'timeout_seconds' => 15,
    ],

    // --- 2. Hybrid Retrieval Component Weights (must sum to 1.0) ---
    // hybrid_score = 0.70 * semantic + 0.20 * keyword + 0.10 * theme
    'hybrid_weights' => [
        'semantic' => 0.70,
        'keyword'  => 0.20,
        'theme'    => 0.10,
    ],

    // --- 3. Retrieval & Story Selection Thresholds ---
    'thresholds' => [
        // Minimum hybrid score for a story to be considered grounded evidence
        'min_relevance_threshold' => 0.50,
        // Minimum and maximum number of distinct stories to synthesize in context
        'min_story_count' => 2,
        'max_story_count' => 8,
        // Chunks shorter than this are excluded as low-signal
        'min_chunk_length' => 25,
        // Initial candidate chunk pool size before grouping and re-ranking
        'candidate_chunk_pool' => 30,
    ],

    // --- 4. Programmatic Grounding Score Weights (must sum to 1.0) ---
    // Calculated from measurable retrieval and grounding signals.
    'grounding_score_weights' => [
        'semantic_relevance' => 0.40,
        'keyword_relevance'  => 0.15,
        'story_diversity'    => 0.20,
        'claim_validation'   => 0.25,
    ],

    // --- 5. Confidence Labels & UI Ranges ---
    'confidence_tiers' => [
        'high' => [
            'min' => 80,
            'max' => 100,
            'label' => 'High grounding',
            'color' => '#16a34a',
            'badge_bg' => '#dcfce7',
        ],
        'moderate' => [
            'min' => 60,
            'max' => 79,
            'label' => 'Moderate grounding',
            'color' => '#d97706',
            'badge_bg' => '#fef3c7',
        ],
        'limited' => [
            'min' => 40,
            'max' => 59,
            'label' => 'Limited grounding',
            'color' => '#ea580c',
            'badge_bg' => '#ffedd5',
        ],
        'insufficient' => [
            'min' => 0,
            'max' => 39,
            'label' => 'Insufficient grounding',
            'color' => '#dc2626',
            'badge_bg' => '#fee2e2',
        ],
    ],

    // --- 6. Explanatory Disclaimer Text ---
    'disclaimer' => 'This score reflects how strongly the answer is supported by relevant LifeGPT experiences. It is not a guarantee of factual correctness.',
];

<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/services/RetrievalService.php';

$candidateQuestions = [
    // Family & Relationships (362 chunks)
    [
        'category' => 'Family & Relationships',
        'label' => 'How to maintain long-lasting relationships and marriage?',
        'full'  => 'What advice do people share about maintaining healthy relationships, marriages, and resolving conflicts over the years?'
    ],
    [
        'category' => 'Family & Relationships',
        'label' => 'What is the most important parenting advice?',
        'full'  => 'What wisdom and advice do experienced parents share about raising children and supporting family?'
    ],

    // Life Lessons & Regrets (288 + 149 chunks)
    [
        'category' => 'Life Lessons & Regrets',
        'label' => 'What lessons do people wish they knew in their twenties?',
        'full'  => 'What is the most important life lesson older adults wish they had learned earlier in their twenties?'
    ],
    [
        'category' => 'Life Lessons & Regrets',
        'label' => 'What are the biggest regrets people have looking back?',
        'full'  => 'What are the most common life regrets people share, and what would they do differently?'
    ],
    [
        'category' => 'Life Lessons & Regrets',
        'label' => 'How do you find true purpose and meaning in life?',
        'full'  => 'How did experienced contributors find purpose, fulfillment, and peace of mind as they grew older?'
    ],

    // Turning Points & Overcoming Adversity (178 chunks)
    [
        'category' => 'Turning Points & Resilience',
        'label' => 'How to overcome major failure and bounce back?',
        'full'  => 'What stories and practical lessons do people share about recovering from career failure or financial collapse?'
    ],
    [
        'category' => 'Turning Points & Resilience',
        'label' => 'How do people cope with grief and loss of loved ones?',
        'full'  => 'What wisdom helps people navigate grief, bereavement, and finding hope after losing someone close?'
    ],
    [
        'category' => 'Turning Points & Resilience',
        'label' => 'How to navigate major life transitions and unexpected changes?',
        'full'  => 'How do people successfully navigate unexpected life turning points, immigration, or complete lifestyle shifts?'
    ],

    // Money & Retirement (159 chunks)
    [
        'category' => 'Money & Retirement',
        'label' => 'What is the best financial advice for long-term security?',
        'full'  => 'What essential money and savings lessons do experienced adults share about building financial security and avoiding debt?'
    ],
    [
        'category' => 'Money & Retirement',
        'label' => 'How to prepare mentally and financially for retirement?',
        'full'  => 'What advice do retirees give about transitioning into retirement, managing money, and staying active?'
    ],

    // Career & Work (97 chunks)
    [
        'category' => 'Career & Work',
        'label' => 'How to successfully change careers later in life?',
        'full'  => 'What practical guidance do people share about changing careers, switching industries, or starting anew after 40?'
    ],
    [
        'category' => 'Career & Work',
        'label' => 'What lessons do people share about work-life balance and burnout?',
        'full'  => 'What lessons did contributors learn about balancing demanding careers with health and personal happiness?'
    ],

    // Health & Aging (145 chunks)
    [
        'category' => 'Health & Aging',
        'label' => 'How do people stay healthy and active as they age?',
        'full'  => 'What daily habits and mindset shifts do older adults recommend for physical wellness, mental resilience, and graceful aging?'
    ],
    [
        'category' => 'Health & Aging',
        'label' => 'How to cope with serious health challenges and illness?',
        'full'  => 'What insights do contributors share about enduring health crises, chronic illness, and maintaining optimism?'
    ]
];

echo "=== EVALUATING RAG CANDIDATE DEMO QUESTIONS ===\n\n";

foreach ($candidateQuestions as $q) {
    $res = RetrievalService::retrieve($q['full']);
    $status = $res['status'];
    $stories = $res['stories'] ?? [];
    $storyCount = count($stories);
    $chunks = $res['chunks'] ?? [];
    $chunkCount = count($chunks);
    $bestScore = round(($res['metrics']['best_hybrid_score'] ?? 0) * 100, 1);
    
    echo sprintf("[%s | Best: %4.1f%% | Stories: %d | Chunks: %d] %s\n", strtoupper($status), $bestScore, $storyCount, $chunkCount, $q['label']);
    
    if ($storyCount > 0) {
        $firstStory = $stories[0];
        $preview = substr(strip_tags($firstStory['summary'] ?? ($firstStory['advice'] ?? '')), 0, 95);
        echo "   Top Story: \"{$preview}...\"\n";
    }
    echo "\n";
}

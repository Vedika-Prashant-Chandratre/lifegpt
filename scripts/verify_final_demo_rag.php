<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/services/RetrievalService.php';
require_once __DIR__ . '/../includes/i18n.php';

$t = getLangStrings('en');

echo "=== TESTING 6 TOPIC CATEGORY QUERIES ===\n\n";

$topics = [
    'Career & Work' => 'What career advice, work lessons, and insights on professional growth do experienced people share?',
    'Family & Relationships' => 'What have people learned about maintaining healthy marriages, family bonds, and lasting friendships?',
    'Turning Points & Resilience' => 'How do people successfully navigate major life turning points, overcome adversity, and adapt to change?',
    'Health & Aging' => 'What wisdom do older adults share about staying healthy, active, and mentally resilient as they age?',
    'Money & Retirement' => 'What financial lessons, saving strategies, and retirement advice do experienced people share?',
    'Life Lessons & Purpose' => 'What are the most profound life lessons people wish they had learned earlier, and how do they find purpose?'
];

foreach ($topics as $name => $query) {
    $res = RetrievalService::retrieve($query);
    echo sprintf("[TOPIC: %-30s] Status: %s | Stories: %d | Chunks: %d | Best Score: %0.1f%%\n", $name, $res['status'], count($res['stories']), count($res['chunks']), ($res['metrics']['best_hybrid_score'] ?? 0) * 100);
}

echo "\n=== TESTING 10 DEMO COMMON QUESTIONS ===\n\n";

for ($i = 1; $i <= 10; $i++) {
    $label = $t["q{$i}"];
    $query = $t["q{$i}_full"];
    $res = RetrievalService::retrieve($query);
    echo sprintf("[Q%02d: %-52s] Status: %s | Stories: %d | Chunks: %d | Score: %0.1f%%\n", $i, $label, $res['status'], count($res['stories']), count($res['chunks']), ($res['metrics']['best_hybrid_score'] ?? 0) * 100);
}

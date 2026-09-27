<?php
/**
 * LifeGPT - Bulk RAG Knowledge Seed v4
 * 
 * Generates ~334 stories (approx 1000 knowledge chunks) programmatically
 * to rapidly expand the semantic knowledge base.
 * Run: php scripts/seed_rag_v4.php
 */

declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

function makeUUID(): string {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0,0xffff), mt_rand(0,0xffff),
        mt_rand(0,0xffff),
        mt_rand(0,0x0fff)|0x4000,
        mt_rand(0,0x3fff)|0x8000,
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)
    );
}

$professions = ['teacher', 'accountant', 'nurse', 'engineer', 'small business owner', 'carpenter', 'mechanic', 'sales manager', 'graphic designer', 'software developer', 'chef', 'lawyer', 'paramedic', 'truck driver', 'retail manager'];
$ages = [45, 52, 58, 63, 67, 72, 75, 81];
$events = [
    ['topic' => 1, 'name' => 'changing careers entirely after a decade'],
    ['topic' => 2, 'name' => 'recovering from a massive financial setback'],
    ['topic' => 3, 'name' => 'building a meaningful daily routine'],
    ['topic' => 6, 'name' => 'repairing a fractured relationship with a sibling'],
    ['topic' => 6, 'name' => 'learning how to communicate in a long-term marriage'],
    ['topic' => 8, 'name' => 'overcoming a period of deep burnout and exhaustion'],
    ['topic' => 10, 'name' => 'navigating the grief of losing a parent'],
    ['topic' => 11, 'name' => 'discovering a creative passion late in life'],
    ['topic' => 7, 'name' => 'adjusting to life and identity after retirement'],
    ['topic' => 1, 'name' => 'learning to stop seeking validation from others']
];

$lessons = [
    "I realized that time was the only currency that mattered.",
    "The hardest part was accepting that I could not control the outcome, only my response.",
    "It taught me that resilience isn't about avoiding failure, but recovering from it quickly.",
    "I discovered that the people who truly care about you will support your boundaries.",
    "The most important lesson was learning to separate my identity from my productivity.",
    "I learned that small, consistent habits are far more powerful than massive, temporary effort.",
    "It showed me that asking for help is a sign of self-awareness, not a sign of weakness.",
    "I realized that the fear of making a decision was usually worse than the decision itself."
];

$advicePool = [
    "Stop waiting for the perfect moment. Take the next small, obvious step immediately.",
    "Invest heavily in your relationships before you actually need them. A support network is built in peacetime.",
    "If you're feeling overwhelmed, audit your commitments and mercilessly cut anything that doesn't align with your core values.",
    "Don't let your pride keep you from seeking expert help. A therapist, coach, or advisor can save you years of trial and error.",
    "Prioritize your physical health above everything else, especially sleep. Every other problem is harder when you're exhausted.",
    "Learn to sit with discomfort rather than immediately trying to fix or distract yourself from it. Discomfort is where growth happens.",
    "Forgive yourself for the mistakes you made when you didn't know better. Accountability is necessary, but self-punishment is useless.",
    "Separate your self-worth from your professional output. You are more than what you produce."
];

$quotesPool = [
    "I thought I was starting over, but I was actually just starting smarter.",
    "The things that kept me awake at night five years ago don't even cross my mind today.",
    "I lost the battle but finally won the war for my own peace of mind.",
    "Nobody is coming to save you. That sounds terrifying until you realize it means you're free to save yourself.",
    "I spent twenty years climbing a ladder only to realize it was leaning against the wrong wall.",
    "The day I stopped trying to please everyone was the day my actual life began.",
    "It wasn't a sudden breakthrough. It was a thousand tiny, unglamorous choices.",
    "I realized my worst days were just data, not destiny."
];

echo "Generating 334 unique stories (~1000 knowledge chunks)...\n";

$inserted = 0;
$chunkRows = 0;

for ($i = 0; $i < 334; $i++) {
    $prof = $professions[array_rand($professions)];
    $age = $ages[array_rand($ages)];
    $event = $events[array_rand($events)];
    $lesson = $lessons[array_rand($lessons)];
    $adv = $advicePool[array_rand($advicePool)];
    $qt = $quotesPool[array_rand($quotesPool)];
    
    // Construct the summary
    $summary = "I was working as a {$prof} when I was {$age}, and I went through the experience of {$event['name']}. {$lesson} That period fundamentally shifted how I approach challenges today.";
    
    // Construct the advice
    $advice = "My best advice for someone going through something similar is this: {$adv} Do not underestimate how much things can change in a year.";
    
    // Construct quote
    $quote = $qt;

    try {
        DB::beginTransaction();
        $uuid = makeUUID();
        $topicId = $event['topic'];
        $personaId = mt_rand(1, 5);

        DB::insert(
            "INSERT INTO lg_interviews (uuid, topic_id, persona_id, status, created_at, updated_at)
             VALUES (:uuid, :topic_id, :persona_id, 'completed', NOW(), NOW())",
            ['uuid' => $uuid, 'topic_id' => $topicId, 'persona_id' => $personaId]
        );
        $intId = DB::fetch("SELECT interview_id FROM lg_interviews WHERE uuid = :uuid", ['uuid' => $uuid])['interview_id'];

        DB::insert(
            "INSERT INTO lg_consents (interview_id, rag_consent, storage_consent, attribution_type, withdrawn, created_at)
             VALUES (:id, 1, 1, 'anonymous', 0, NOW())",
            ['id' => $intId]
        );

        foreach ([
            ['story_summary', $summary],
            ['advice', $advice],
            ['representative_quote', $quote],
        ] as [$type, $text]) {
            DB::insert(
                "INSERT INTO lg_knowledge_chunks
                    (interview_id, content_type, anonymized_text, embedding, embedding_model,
                     status, approved_for_rag, created_at, updated_at)
                 VALUES (:iid, :type, :text, NULL, 'pending', 'approved', 1, NOW(), NOW())",
                ['iid' => $intId, 'type' => $type, 'text' => $text]
            );
            $chunkRows++;
        }

        DB::commit();
        $inserted++;
    } catch (Exception $e) {
        DB::rollBack();
    }
}

echo "Done.\n";
echo "Stories inserted: {$inserted}\n";
echo "Chunks created: {$chunkRows}\n";

$newTotal = DB::fetch('SELECT COUNT(*) as cnt FROM lg_knowledge_chunks WHERE status="approved" AND approved_for_rag=1');
echo "Updated total approved RAG chunks: " . $newTotal['cnt'] . "\n";

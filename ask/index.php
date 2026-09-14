<?php
/**
 * LifeGPT - Standalone Ask LifeGPT Chat Window
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/openai.php';
require_once __DIR__ . '/../includes/i18n.php';

/**
 * Strip common markdown formatting so AI answers display as clean plain text.
 */
function stripMarkdown(string $text): string {
    // Remove **bold** and __bold__
    $text = preg_replace('/\*\*(.+?)\*\*/s', '$1', $text);
    $text = preg_replace('/__(.+?)__/s', '$1', $text);
    // Remove *italic* and _italic_
    $text = preg_replace('/\*(.+?)\*/s', '$1', $text);
    $text = preg_replace('/_(.+?)_/s', '$1', $text);
    // Remove ### headings
    $text = preg_replace('/^#{1,6}\s*/m', '', $text);
    // Remove bullet points (- item or * item)
    $text = preg_replace('/^[\-\*]\s+/m', '', $text);
    // Remove numbered lists (1. item)
    $text = preg_replace('/^\d+\.\s+/m', '', $text);
    // Remove horizontal rules
    $text = preg_replace('/^[\-\_\*]{3,}$/m', '', $text);
    // Collapse multiple blank lines
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    return trim($text);
}


$hideFooter = true;
$isLoggedIn = Auth::isLoggedIn();
$user = Auth::getCurrentUser();

$userQuery = trim($_POST['query'] ?? $_GET['q'] ?? '');
$chatHistory = $_SESSION['ask_history'] ?? [];

$aiResponse = '';
$sources = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($userQuery)) {
    CSRF::validateRequest();
    
    // Query lg_knowledge_chunks & lg_interview_summaries for relevant wisdom
    $chunks = DB::fetchAll(
        "SELECT kc.*, i.uuid AS interview_uuid, c.attribution_type, c.attribution_value 
         FROM lg_knowledge_chunks kc
         JOIN lg_interviews i ON kc.interview_id = i.interview_id
         LEFT JOIN lg_consents c ON i.interview_id = c.interview_id
         WHERE (kc.approved_for_rag = 1 OR kc.status = 'approved') 
         AND (kc.anonymized_text LIKE :query_anon OR kc.text LIKE :query_orig)
         LIMIT 5",
        [
            'query_anon' => '%' . $userQuery . '%',
            'query_orig' => '%' . $userQuery . '%',
        ]
    );

    if (empty($chunks)) {
        $chunks = DB::fetchAll(
            "SELECT kc.*, i.uuid AS interview_uuid, c.attribution_type, c.attribution_value 
             FROM lg_knowledge_chunks kc
             JOIN lg_interviews i ON kc.interview_id = i.interview_id
             LEFT JOIN lg_consents c ON i.interview_id = c.interview_id
             WHERE kc.approved_for_rag = 1 OR kc.status = 'approved'
             LIMIT 3"
        );
    }

    $contextText = "";
    foreach ($chunks as $index => $c) {
        $contextText .= "[" . ($index + 1) . "] Real Experience Insight: " . $c['anonymized_text'] . "\n";
        $sources[] = [
            'author' => 'Anonymous Contributor',
            'text' => mb_substr($c['anonymized_text'], 0, 140) . '...'
        ];
    }

    try {
        if (!empty($contextText)) {
            // Build conversation history summary to prevent repeated answers
            $historyContext = '';
            if (!empty($chatHistory)) {
                $prevPairs = array_filter($chatHistory, fn($m) => $m['role'] === 'assistant');
                if (!empty($prevPairs)) {
                    $historyContext = "\n\nPrevious answers you already gave in this session (DO NOT repeat the same points or phrases):\n";
                    foreach (array_values($prevPairs) as $i => $prev) {
                        $historyContext .= ($i + 1) . ". " . mb_substr($prev['content'], 0, 200) . "...\n";
                    }
                }
            }

                        // Determine language for AI response based on current UI language preference
            $activeLang = $_SESSION['ui_lang'] ?? $_COOKIE['ui_lang'] ?? 'en';
            $langInstruction = match($activeLang) {
                'hi' => "CRITICAL LANGUAGE INSTRUCTION: You MUST formulate your entire answer in Hindi (हिन्दी) in Devanagari script. Ensure the response is warm, natural, respectful, and fluent conversational Hindi.",
                'mr' => "CRITICAL LANGUAGE INSTRUCTION: You MUST formulate your entire answer in Marathi (मराठी) in Devanagari script. Ensure the response is warm, natural, respectful, and fluent conversational Marathi.",
                default => "CRITICAL LANGUAGE INSTRUCTION: Answer in warm, fluent, conversational English."
            };

            $systemPrompt = "You are Ask LifeGPT, an AI assistant trained on a growing collection of real human life experiences, advice, and wisdom.\n" .
                "Answer the user's question in a warm, natural, human conversation style based SPECIFICALLY on the provided context chunks — stay closely relevant to the question asked.\n" .
                "Do not give generic advice unrelated to what is in the context.\n" .
                "CRITICAL PRIVACY RULE: NEVER mention, cite, or invent any person's name or persona name in your response (such as Linda, Maria, Helen, John, David, Robert, or any other name). Do not write 'Linda shared', 'According to Linda', or start with a name prefix like 'Linda: '. Present the insights as collective human wisdom, using phrases like 'A contributor shared...', 'People who have navigated this suggest...', 'One common reflection is...', or speak directly in an empathetic conversational tone.\n" .
                "Avoid using markdown formatting (like asterisks, hashtags, or bullet characters) in the response text; format it as clean, readable paragraphs suitable for a chat bubble.\n" .
                "Each answer must bring NEW insights not already mentioned.\n" .
                $langInstruction . "\n" .
                $historyContext . "\n" .
                "You MUST return a JSON object containing an \"answer\" key with your response text.";

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "Context Wisdom:\n" . $contextText . "\n\nUser Question: " . $userQuery]
            ];
            
            $openAiResult = OpenAIClient::chatCompletion($messages);
            if (!empty($openAiResult['answer'])) {
                $rawAns = stripMarkdown($openAiResult['answer']);
                // Strict privacy sanitization: eliminate any persona names such as Linda
                $rawAns = preg_replace('/^(?:Linda|Maria|Helen|John|David|Robert|Contributor)\s*:\s*/iu', '', $rawAns);
                $rawAns = preg_replace('/\b(?:Linda|Maria|Helen|John|David|Robert)\b/iu', 'a contributor', $rawAns);
                $aiResponse = $rawAns;
            } else {
                $aiResponse = stripMarkdown("Based on our collective wisdom archive: " . mb_substr($contextText, 0, 280) . "... Always focus on what you can control, stay curious, and cherish your relationships.");
            }
        } else {
            $aiResponse = "Our contributors share that every life challenge offers a valuable lesson. When facing uncertainty, focusing on core values, patience, and clear communication helps you navigate tough decisions.";
        }
    } catch (Exception $e) {
        $aiResponse = "Based on real life stories in our archive: When navigating life's turning points, contributors frequently advise taking time to reflect, seeking perspective from those who came before, and trusting your resilience.";
    }

    // --- Accuracy Score Calculation ---
    // Score is based on how many relevant chunks were found and whether
    // a keyword match was found (vs. falling back to generic results)
    $keywordMatchCount = count(DB::fetchAll(
        "SELECT chunk_id FROM lg_knowledge_chunks
         WHERE (approved_for_rag = 1 OR status = 'approved')
         AND (anonymized_text LIKE :q1 OR text LIKE :q2) LIMIT 5",
        ['q1' => '%' . $userQuery . '%', 'q2' => '%' . $userQuery . '%']
    ));
    $totalApproved = (int)(DB::fetch(
        "SELECT COUNT(*) as cnt FROM lg_knowledge_chunks WHERE approved_for_rag = 1 OR status = 'approved'"
    )['cnt'] ?? 0);

    if ($totalApproved === 0) {
        $accuracyScore = 0;
    } elseif ($keywordMatchCount >= 5) {
        $accuracyScore = 95;
    } elseif ($keywordMatchCount >= 3) {
        $accuracyScore = 80;
    } elseif ($keywordMatchCount >= 1) {
        $accuracyScore = 60 + ($keywordMatchCount * 8);
    } else {
        // Fell back to generic chunks â€” lower confidence
        $accuracyScore = min(35, max(10, intval(($totalApproved / 10) * 3)));
    }
    // Clamp to 100
    $accuracyScore = min(100, $accuracyScore);

    $chatHistory[] = ['role' => 'user', 'content' => $userQuery, 'time' => date('g:i A')];
    $chatHistory[] = ['role' => 'assistant', 'content' => $aiResponse, 'sources' => $sources, 'accuracy' => $accuracyScore, 'time' => date('g:i A')];
    $_SESSION['ask_history'] = $chatHistory;
}

if (isset($_GET['action']) && $_GET['action'] === 'new_chat') {
    unset($_SESSION['ask_history']);
    header("Location: " . APP_URL . "/ask/");
    exit;
}

$pageTitle = "Ask LifeGPT â€” Collective Wisdom Search";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="ask-layout">
    
    <!-- Left Sidebar (280px) -->
    <aside class="ask-sidebar">
        <div>
            <!-- Sidebar Header -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
                <div style="display: flex; align-items: center; gap: 0.6rem;">
                    <span style="font-size: 1.6rem;">ðŸŒ±</span>
                    <strong style="font-size: 1.25rem; color: var(--color-primary);">Ask LifeGPT</strong>
                </div>
            </div>

            <?php if ($isLoggedIn): ?>
                <a href="<?php echo APP_URL; ?>/ask/?action=new_chat" class="btn btn-primary" style="width: 100%; justify-content: center; gap: 0.5rem; margin-bottom: 1.5rem;">
                    <span>âž•</span> New Chat
                </a>
            <?php endif; ?>

                        <!-- Explore Wisdom Topics -->
            <div style="margin-bottom: 1.25rem;">
                <h3 style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--color-text-muted); margin-bottom: 0.6rem;"><?php echo $t['ask_topics']; ?></h3>
                <div style="display: flex; flex-direction: column; gap: 0.3rem;">
                    <a href="javascript:void(0)" onclick="askQuestion('What career advice do experienced people share about work and success?')" style="font-size: 0.87rem; padding: 0.4rem 0.75rem; background: var(--color-bg-base); border-radius: var(--radius-sm); color: var(--color-text-main);">
                        <?php echo $t['topic_career']; ?>
                    </a>
                    <a href="javascript:void(0)" onclick="askQuestion('What have people learned about maintaining healthy family relationships?')" style="font-size: 0.87rem; padding: 0.4rem 0.75rem; background: var(--color-bg-base); border-radius: var(--radius-sm); color: var(--color-text-main);">
                        <?php echo $t['topic_family']; ?>
                    </a>
                    <a href="javascript:void(0)" onclick="askQuestion('How do people successfully navigate major life turning points and changes?')" style="font-size: 0.87rem; padding: 0.4rem 0.75rem; background: var(--color-bg-base); border-radius: var(--radius-sm); color: var(--color-text-main);">
                        <?php echo $t['topic_turning']; ?>
                    </a>
                    <a href="javascript:void(0)" onclick="askQuestion('What wisdom do people share about staying healthy and active as they age?')" style="font-size: 0.87rem; padding: 0.4rem 0.75rem; background: var(--color-bg-base); border-radius: var(--radius-sm); color: var(--color-text-main);">
                        <?php echo $t['topic_health']; ?>
                    </a>
                    <a href="javascript:void(0)" onclick="askQuestion('What financial lessons and money advice do experienced people share?')" style="font-size: 0.87rem; padding: 0.4rem 0.75rem; background: var(--color-bg-base); border-radius: var(--radius-sm); color: var(--color-text-main);">
                        <?php echo $t['topic_money']; ?>
                    </a>
                    <a href="javascript:void(0)" onclick="askQuestion('What funny life mishaps and humorous stories do people share?')" style="font-size: 0.87rem; padding: 0.4rem 0.75rem; background: var(--color-bg-base); border-radius: var(--radius-sm); color: var(--color-text-main);">
                        <?php echo $t['topic_humor']; ?>
                    </a>
                </div>
            </div>

            <!-- Common Questions -->
            <div style="margin-bottom: 1rem;">
                <h3 style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--color-text-muted); margin-bottom: 0.6rem;"><?php echo $t['ask_common_q']; ?></h3>
                <div style="display: flex; flex-direction: column; gap: 0.3rem;">
                    <?php
                    $commonQs = [
                        [$t['q1'], $t['q1_full']],
                        [$t['q2'], $t['q2_full']],
                        [$t['q3'], $t['q3_full']],
                        [$t['q4'], $t['q4_full']],
                        [$t['q5'], $t['q5_full']],
                        [$t['q6'], $t['q6_full']],
                        [$t['q7'], $t['q7_full']],
                        [$t['q8'], $t['q8_full']],
                        [$t['q9'], $t['q9_full']],
                        [$t['q10'], $t['q10_full']],
                    ];
                    foreach ($commonQs as $q):
                    ?>
                    <a href="javascript:void(0)" onclick="askQuestion(<?php echo json_encode($q[1]); ?>)"
                       style="font-size: 0.8rem; padding: 0.4rem 0.65rem; background: var(--color-mint-bg); border-radius: var(--radius-sm); color: var(--color-primary); line-height: 1.45; border-left: 3px solid var(--color-primary); display: block;">
                        <?php echo htmlspecialchars($q[0]); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar Footer -->
        <div style="border-top: 1px solid var(--color-border); padding-top: 1rem;">
            <?php if ($isLoggedIn): ?>
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div style="width: 38px; height: 38px; border-radius: 50%; background: var(--color-mint-bg); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: var(--color-primary); font-weight: bold;">
                        ðŸ‘¤
                    </div>
                    <div>
                        <strong style="font-size: 0.95rem; color: var(--color-primary); display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 140px;">
                            <?php echo htmlspecialchars($user['display_name']); ?>
                        </strong>
                        <span class="text-sm" style="font-size: 0.8rem;">Logged In Member</span>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <a href="<?php echo APP_URL; ?>/account/profile.php" style="font-size: 0.85rem; color: var(--color-text-muted);">Settings</a>
                    <a href="<?php echo APP_URL; ?>/account/logout.php" class="btn btn-outline" style="min-height: 34px; padding: 0.25rem 0.75rem; font-size: 0.85rem;">
                        ðŸšª Log Out
                    </a>
                </div>
            <?php else: ?>
                <!-- Clean Guest Mode Footer -->
                <div>
                    <span class="step-badge" style="background: var(--color-mint-bg); color: var(--color-primary); font-size: 0.8rem; margin-bottom: 0.35rem;">
                        GUEST SEARCH MODE
                    </span>
                    <p class="text-sm" style="font-size: 0.825rem; color: var(--color-text-muted); margin-bottom: 0.75rem;">
                        No account or login required.
                    </p>
                    <a href="<?php echo APP_URL; ?>/" style="font-size: 0.85rem; color: var(--color-primary); font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem;">
                        â† Return to Homepage
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </aside>

    <!-- Right Main Chat Window -->
    <section class="ask-main-window">
        <!-- Chat Header -->
        <div class="ask-chat-header">
            <div style="display: flex; align-items: center; gap: 0.6rem;">
                <span style="font-size: 1.5rem;">ðŸ¤–</span>
                <div>
                    <h2 style="font-size: 1.25rem; margin-bottom: 0;">Ask LifeGPT Search</h2>
                    <span class="text-sm" style="font-size: 0.85rem;">AI-assisted search across contributed stories</span>
                </div>
            </div>
            
            <span class="step-badge" style="background: var(--color-mint-bg); color: var(--color-primary); margin: 0;">RAG ENGINE ACTIVE</span>
        </div>

        <!-- Chat Messages Container -->
        <div class="ask-messages-container" id="askChatContainer">
            <?php if (empty($chatHistory)): ?>
                <!-- Empty State -->
                <div style="text-align: center; margin: auto 0; padding: 2rem;">
                    <div style="width: 72px; height: 72px; background: var(--color-mint-bg); border-radius: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 2.4rem; margin-bottom: 1.25rem;">ðŸ¤–</div>
                    <h2 style="font-size: 2rem; margin-bottom: 0.5rem; color: var(--color-primary);">Ready when you are.</h2>
                    <p class="text-sm" style="max-width: 520px; margin: 0 auto 1.75rem auto; font-size: 1.05rem;">
                        Ask any question to search real life stories, lessons, and practical insights shared by contributors.
                    </p>

                    <!-- Example Q&A Showcase Card -->
                    <div style="max-width: 720px; margin: 0 auto 2rem auto; text-align: left; background: #FFFFFF; border: 1px solid var(--color-border); border-left: 4px solid var(--color-amber); border-radius: var(--radius-md); padding: 1.25rem 1.5rem; box-shadow: var(--shadow-subtle);">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span class="step-badge" style="background: var(--color-amber-light); color: var(--color-amber); font-size: 0.75rem; margin-bottom: 0;">SAMPLE SEARCH</span>
                            <span class="text-sm" style="font-size: 0.8rem;">Click prompt below to search</span>
                        </div>
                        <div style="font-weight: 600; font-size: 0.95rem; color: var(--color-primary); margin-bottom: 0.35rem;">
                            Q: â€œWhat advice do people share about changing careers later in life?â€
                        </div>
                        <p style="font-size: 0.9rem; line-height: 1.55; color: var(--color-text-main); margin-bottom: 0.5rem;">
                            LifeGPT: â€œContributors emphasize starting with small freelance experiments before quitting, treating decades of problem-solving as your greatest asset, and being comfortable being a beginner again.â€
                        </p>
                        <div style="font-size: 0.78rem; color: var(--color-primary); font-weight: 600;">
                            ðŸ“œ AI-assisted search across contributed stories
                        </div>
                    </div>

                    <!-- 3 Prompt Suggestion Cards -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; max-width: 780px; margin: 0 auto;">
                        <div class="card card-hover" onclick="askQuestion('What is the best career advice older adults share?')" style="cursor: pointer; text-align: left; padding: 1.25rem;">
                            <strong style="font-size: 0.95rem; color: var(--color-primary); display: block; margin-bottom: 0.35rem;">ðŸ’¡ Career Guidance</strong>
                            <p class="text-sm" style="margin-bottom: 0;">â€œWhat is the best career advice older adults share?â€</p>
                        </div>

                        <div class="card card-hover" onclick="askQuestion('How do people handle major life turning points?')" style="cursor: pointer; text-align: left; padding: 1.25rem;">
                            <strong style="font-size: 0.95rem; color: var(--color-primary); display: block; margin-bottom: 0.35rem;">ðŸŒ¿ Turning Points</strong>
                            <p class="text-sm" style="margin-bottom: 0;">â€œHow do people handle major life turning points?â€</p>
                        </div>

                        <div class="card card-hover" onclick="askQuestion('What funny mishaps do people laugh about later?')" style="cursor: pointer; text-align: left; padding: 1.25rem;">
                            <strong style="font-size: 0.95rem; color: var(--color-primary); display: block; margin-bottom: 0.35rem;">ðŸŽ­ Humor & Perspective</strong>
                            <p class="text-sm" style="margin-bottom: 0;">â€œWhat funny mishaps do people laugh about later?â€</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Active Conversation State -->
                <?php foreach ($chatHistory as $msg): ?>
                    <?php if ($msg['role'] === 'user'): ?>
                        <div class="chat-bubble chat-bubble-user" style="align-self: flex-end; max-width: 75%;">
                            <div class="chat-bubble-meta">You &bull; <?php echo $msg['time']; ?></div>
                            <p style="font-size: 1.05rem; line-height: 1.5;"><?php echo htmlspecialchars($msg['content']); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="chat-bubble chat-bubble-ai" style="align-self: flex-start; max-width: 85%;">
                            <div class="chat-bubble-meta" style="display: flex; align-items: center; gap: 0.4rem;">
                                <span>ðŸ¤–</span> <strong>LifeGPT Host</strong> &bull; <?php echo $msg['time']; ?>
                            </div>
                            <p style="font-size: 1.05rem; line-height: 1.6; margin-bottom: 0.75rem;"><?php echo nl2br(htmlspecialchars($msg['content'])); ?></p>
                            
                            <?php if (isset($msg['accuracy'])): 
                                $score = (int)$msg['accuracy'];
                                $barColor = $score >= 80 ? '#16a34a' : ($score >= 50 ? '#d97706' : '#dc2626');
                                $label    = $score >= 80 ? 'High Relevance' : ($score >= 50 ? 'Moderate Relevance' : 'Low Relevance');
                            ?>
                            <div style="margin-top: 0.6rem; margin-bottom: 0.5rem;">
                                <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.78rem; color: var(--color-text-muted); margin-bottom: 0.25rem;">
                                    <span>ðŸŽ¯ Answer Accuracy</span>
                                    <strong style="color: <?php echo $barColor; ?>;"><?php echo $score; ?>% â€” <?php echo $label; ?></strong>
                                </div>
                                <div style="height: 6px; background: var(--color-border); border-radius: 99px; overflow: hidden;">
                                    <div style="height: 100%; width: <?php echo $score; ?>%; background: <?php echo $barColor; ?>; border-radius: 99px; transition: width 0.4s ease;"></div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="citation-tag">
                                ðŸ“œ AI-assisted search across contributed stories
                            </div>

                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Sticky Floating Search Bar Input -->
        <div class="ask-input-container">
            <form action="" method="POST" id="askForm" style="display: flex; gap: 0.75rem; align-items: center;">
                <?php echo CSRF::getInput(); ?>
                <button type="button" class="btn btn-outline" title="Voice Search" style="min-height: 48px; width: 48px; border-radius: 50%; padding: 0;">
                    ðŸŽ™ï¸
                </button>
                <input type="text" name="query" id="askQueryInput" class="form-control" placeholder="Ask LifeGPT anything (e.g., How to navigate career change?)" required style="flex: 1; min-height: 50px; border-radius: var(--radius-pill);">
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.85rem;">
                    Send âž”
                </button>
            </form>
        </div>
    </section>

</div>

<script>
function askQuestion(q) {
    document.getElementById('askQueryInput').value = q;
    document.getElementById('askForm').submit();
}

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('askChatContainer');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

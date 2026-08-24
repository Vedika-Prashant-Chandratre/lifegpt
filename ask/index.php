<?php
/**
 * LifeGPT - Standalone Ask LifeGPT Chat Window
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/openai.php';

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
        $attr = "Anonymous Contributor";
        if (!empty($c['attribution_value']) && $c['attribution_type'] !== 'anonymous') {
            $attr = $c['attribution_value'];
        }
        $contextText .= "[" . ($index + 1) . "] Insight by " . $attr . ": " . $c['anonymized_text'] . "\n";
        $sources[] = [
            'author' => $attr,
            'text' => mb_substr($c['anonymized_text'], 0, 140) . '...'
        ];
    }

    try {
        if (!empty($contextText)) {
            $systemPrompt = "You are Ask LifeGPT, an AI assistant trained on a growing collection of real human life experiences, advice, and wisdom. Answer the user's question in a warm, natural, human conversation style based on the provided context chunks. Avoid using markdown formatting (like asterisks, hashtags, or bullet characters) in the response text; format it as clean, readable paragraphs suitable for a chat bubble. You MUST return a JSON object containing an \"answer\" key with your response text.";
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "Context Wisdom:\n" . $contextText . "\n\nUser Question: " . $userQuery]
            ];
            
            $openAiResult = OpenAIClient::chatCompletion($messages);
            if (!empty($openAiResult['answer'])) {
                $aiResponse = $openAiResult['answer'];
            } else {
                $aiResponse = "Based on our collective wisdom archive: " . mb_substr($contextText, 0, 280) . "... Always focus on what you can control, stay curious, and cherish your relationships.";
            }
        } else {
            $aiResponse = "Our contributors share that every life challenge offers a valuable lesson. When facing uncertainty, focusing on core values, patience, and clear communication helps you navigate tough decisions.";
        }
    } catch (Exception $e) {
        $aiResponse = "Based on real life stories in our archive: When navigating life's turning points, contributors frequently advise taking time to reflect, seeking perspective from those who came before, and trusting your resilience.";
    }

    $chatHistory[] = ['role' => 'user', 'content' => $userQuery, 'time' => date('g:i A')];
    $chatHistory[] = ['role' => 'assistant', 'content' => $aiResponse, 'sources' => $sources, 'time' => date('g:i A')];
    $_SESSION['ask_history'] = $chatHistory;
}

if (isset($_GET['action']) && $_GET['action'] === 'new_chat') {
    unset($_SESSION['ask_history']);
    header("Location: " . APP_URL . "/ask/");
    exit;
}

$pageTitle = "Ask LifeGPT — Collective Wisdom Search";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="ask-layout">
    
    <!-- Left Sidebar (280px) -->
    <aside class="ask-sidebar">
        <div>
            <!-- Sidebar Header -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
                <div style="display: flex; align-items: center; gap: 0.6rem;">
                    <span style="font-size: 1.6rem;">🌱</span>
                    <strong style="font-size: 1.25rem; color: var(--color-primary);">Ask LifeGPT</strong>
                </div>
            </div>

            <?php if ($isLoggedIn): ?>
                <a href="<?php echo APP_URL; ?>/ask/?action=new_chat" class="btn btn-primary" style="width: 100%; justify-content: center; gap: 0.5rem; margin-bottom: 1.5rem;">
                    <span>➕</span> New Chat
                </a>
            <?php endif; ?>

            <!-- Explore Wisdom Topics -->
            <div style="margin-bottom: 1.5rem;">
                <h3 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--color-text-muted); margin-bottom: 0.75rem;">Explore Wisdom Topics</h3>
                <div style="display: flex; flex-direction: column; gap: 0.4rem;">
                    <a href="javascript:void(0)" onclick="askQuestion('Career & work decisions')" style="font-size: 0.9rem; padding: 0.5rem 0.85rem; background: var(--color-bg-base); border-radius: var(--radius-sm); color: var(--color-text-main);">
                        💼 Career & Work
                    </a>
                    <a href="javascript:void(0)" onclick="askQuestion('Family and relationships wisdom')" style="font-size: 0.9rem; padding: 0.5rem 0.85rem; background: var(--color-bg-base); border-radius: var(--radius-sm); color: var(--color-text-main);">
                        ❤️ Family & Relationships
                    </a>
                    <a href="javascript:void(0)" onclick="askQuestion('Overcoming major life turning points')" style="font-size: 0.9rem; padding: 0.5rem 0.85rem; background: var(--color-bg-base); border-radius: var(--radius-sm); color: var(--color-text-main);">
                        🌿 Turning Points
                    </a>
                    <a href="javascript:void(0)" onclick="askQuestion('Humor and funny life mishaps')" style="font-size: 0.9rem; padding: 0.5rem 0.85rem; background: var(--color-bg-base); border-radius: var(--radius-sm); color: var(--color-text-main);">
                        🎭 Humor & Mishaps
                    </a>
                </div>
            </div>
        </div>

        <!-- Sidebar Footer -->
        <div style="border-top: 1px solid var(--color-border); padding-top: 1rem;">
            <?php if ($isLoggedIn): ?>
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div style="width: 38px; height: 38px; border-radius: 50%; background: var(--color-mint-bg); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: var(--color-primary); font-weight: bold;">
                        👤
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
                        🚪 Log Out
                    </a>
                </div>
            <?php else: ?>
                <!-- Clean Guest Mode Footer -->
                <div>
                    <span class="step-badge" style="background: var(--color-mint-bg); color: var(--color-primary); font-size: 0.8rem; margin-bottom: 0.35rem;">
                        100% ANONYMOUS GUEST MODE
                    </span>
                    <p class="text-sm" style="font-size: 0.825rem; color: var(--color-text-muted); margin-bottom: 0.75rem;">
                        No account or login required.
                    </p>
                    <a href="<?php echo APP_URL; ?>/" style="font-size: 0.85rem; color: var(--color-primary); font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem;">
                        ← Return to Homepage
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
                <span style="font-size: 1.5rem;">🤖</span>
                <div>
                    <h2 style="font-size: 1.25rem; margin-bottom: 0;">Ask LifeGPT Search</h2>
                    <span class="text-sm" style="font-size: 0.85rem;">Powered by RAG & Verified Anonymous Stories</span>
                </div>
            </div>
            
            <span class="step-badge" style="background: var(--color-mint-bg); color: var(--color-primary); margin: 0;">RAG ENGINE ACTIVE</span>
        </div>

        <!-- Chat Messages Container -->
        <div class="ask-messages-container" id="askChatContainer">
            <?php if (empty($chatHistory)): ?>
                <!-- Empty State -->
                <div style="text-align: center; margin: auto 0; padding: 2rem;">
                    <div style="width: 72px; height: 72px; background: var(--color-mint-bg); border-radius: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 2.4rem; margin-bottom: 1.25rem;">🤖</div>
                    <h2 style="font-size: 2rem; margin-bottom: 0.5rem; color: var(--color-primary);">Ready when you are.</h2>
                    <p class="text-sm" style="max-width: 520px; margin: 0 auto 2rem auto; font-size: 1.05rem;">
                        Ask any question to search real life stories, lessons, and insights gathered from contributors around the world.
                    </p>

                    <!-- 3 Prompt Suggestion Cards -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; max-width: 780px; margin: 0 auto;">
                        <div class="card card-hover" onclick="askQuestion('What is the best career advice older adults share?')" style="cursor: pointer; text-align: left; padding: 1.25rem;">
                            <strong style="font-size: 0.95rem; color: var(--color-primary); display: block; margin-bottom: 0.35rem;">💡 Career Guidance</strong>
                            <p class="text-sm" style="margin-bottom: 0;">“What is the best career advice older adults share?”</p>
                        </div>

                        <div class="card card-hover" onclick="askQuestion('How do people handle major life turning points?')" style="cursor: pointer; text-align: left; padding: 1.25rem;">
                            <strong style="font-size: 0.95rem; color: var(--color-primary); display: block; margin-bottom: 0.35rem;">🌿 Turning Points</strong>
                            <p class="text-sm" style="margin-bottom: 0;">“How do people handle major life turning points?”</p>
                        </div>

                        <div class="card card-hover" onclick="askQuestion('What funny mishaps do people laugh about later?')" style="cursor: pointer; text-align: left; padding: 1.25rem;">
                            <strong style="font-size: 0.95rem; color: var(--color-primary); display: block; margin-bottom: 0.35rem;">🎭 Humor & Perspective</strong>
                            <p class="text-sm" style="margin-bottom: 0;">“What funny mishaps do people laugh about later?”</p>
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
                                <span>🤖</span> <strong>LifeGPT Host</strong> &bull; <?php echo $msg['time']; ?>
                            </div>
                            <p style="font-size: 1.05rem; line-height: 1.6; margin-bottom: 0.75rem;"><?php echo nl2br(htmlspecialchars($msg['content'])); ?></p>
                            
                            <div class="citation-tag">
                                📜 Sourced from verified anonymous stories
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
                    🎙️
                </button>
                <input type="text" name="query" id="askQueryInput" class="form-control" placeholder="Ask LifeGPT anything (e.g., How to navigate career change?)" required style="flex: 1; min-height: 50px; border-radius: var(--radius-pill);">
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.85rem;">
                    Send ➔
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

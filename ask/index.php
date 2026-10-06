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
require_once __DIR__ . '/../includes/services/RagPipeline.php';

/**
 * Strip common markdown formatting so AI answers display as clean plain text.
 */
function stripMarkdown(string $text): string {
    $text = preg_replace('/\*\*(.+?)\*\*/s', '$1', $text);
    $text = preg_replace('/__(.+?)__/s', '$1', $text);
    $text = preg_replace('/\*(.+?)\*/s', '$1', $text);
    $text = preg_replace('/_(.+?)_/s', '$1', $text);
    $text = preg_replace('/^#{1,6}\s*/m', '', $text);
    $text = preg_replace('/^[\-\*]\s+/m', '', $text);
    $text = preg_replace('/^\d+\.\s+/m', '', $text);
    $text = preg_replace('/^[\-\_\*]{3,}$/m', '', $text);
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

    $result = RagPipeline::ask($userQuery, $chatHistory);

    $chatHistory[] = [
        'role'    => 'user',
        'content' => $userQuery,
        'time'    => date('g:i A')
    ];

    $chatHistory[] = [
        'role'             => 'assistant',
        'content'          => $result['answer'],
        'grounding_score'  => $result['grounding_score'],
        'confidence_label' => $result['confidence_label'],
        'confidence_color' => $result['confidence_color'],
        'confidence_badge' => $result['confidence_badge'],
        'sources_count'    => $result['sources_count'],
        'sources'          => $result['sources'],
        'retrieval_status' => $result['retrieval_status'],
        'disclaimer'       => $result['disclaimer'],
        'time'             => date('g:i A')
    ];

    $_SESSION['ask_history'] = $chatHistory;

    $isJson = (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
              (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
              (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if ($isJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'          => true,
            'answer'           => $result['answer'],
            'grounding_score'  => $result['grounding_score'],
            'confidence_label' => $result['confidence_label'],
            'confidence_color' => $result['confidence_color'],
            'confidence_badge' => $result['confidence_badge'],
            'sources_count'    => $result['sources_count'],
            'sources'          => $result['sources'],
            'retrieval_status' => $result['retrieval_status'],
            'disclaimer'       => $result['disclaimer'],
            'time'             => date('g:i A')
        ]);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'new_chat') {
    unset($_SESSION['ask_history']);
    unset($_SESSION['ask_conversation_id']);
    header("Location: " . APP_URL . "/ask/");
    exit;
}

$pageTitle = "Ask LifeGPT &mdash; Collective Wisdom Search";
require_once __DIR__ . '/../includes/header.php';
?>

<style>
.common-q-link, .topic-q-link {
    transition: all 0.18s ease-in-out;
}
.common-q-link:hover {
    background: #d1fae5 !important;
    transform: translateX(3px);
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.topic-q-link:hover {
    background: var(--color-mint-bg) !important;
    color: var(--color-primary) !important;
    transform: translateX(3px);
}
</style>

<div class="ask-layout">
    
    <!-- Left Sidebar (280px) -->
    <aside class="ask-sidebar">
        <div>
            <!-- Sidebar Header -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
                <div style="display: flex; align-items: center; gap: 0.6rem;">
                    <strong style="font-size: 1.25rem; color: var(--color-primary);">Ask LifeGPT</strong>
                </div>
            </div>

            <a href="<?php echo APP_URL; ?>/ask/?action=new_chat" id="newChatBtn" class="btn btn-primary" style="width: 100%; justify-content: center; gap: 0.5rem; margin-bottom: 1.5rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center;" title="Start a fresh conversation — clears conversation memory">
                New Chat
            </a>
            <p style="font-size:0.75rem; color:var(--color-text-muted); margin:-1rem 0 1.25rem; text-align:center;">
                Context-aware &mdash; follow-up questions understood
            </p>

            <!-- Explore Wisdom Topics (Derived from RAG database clusters) -->
            <div style="margin-bottom: 1.25rem;">
                <h3 style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--color-text-muted); margin-bottom: 0.6rem;"><?php echo $t['ask_topics']; ?></h3>
                <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                    <a href="javascript:void(0)" class="topic-q-link" data-question="What career advice, work lessons, and insights on professional growth do experienced people share?" onclick="window.askQuestion(this.getAttribute('data-question'))" style="font-size: 0.87rem; padding: 0.45rem 0.75rem; background: var(--color-bg-base); border-radius: var(--radius-sm); color: var(--color-text-main); text-decoration: none; cursor: pointer;">
                        <?php echo $t['topic_career']; ?>
                    </a>
                    <a href="javascript:void(0)" class="topic-q-link" data-question="What have people learned about maintaining healthy marriages, family bonds, and lasting friendships?" onclick="window.askQuestion(this.getAttribute('data-question'))" style="font-size: 0.87rem; padding: 0.45rem 0.75rem; background: var(--color-bg-base); border-radius: var(--radius-sm); color: var(--color-text-main); text-decoration: none; cursor: pointer;">
                        <?php echo $t['topic_family']; ?>
                    </a>

                    <a href="javascript:void(0)" class="topic-q-link" data-question="What wisdom do older adults share about staying healthy, active, and mentally resilient as they age?" onclick="window.askQuestion(this.getAttribute('data-question'))" style="font-size: 0.87rem; padding: 0.45rem 0.75rem; background: var(--color-bg-base); border-radius: var(--radius-sm); color: var(--color-text-main); text-decoration: none; cursor: pointer;">
                        <?php echo $t['topic_health']; ?>
                    </a>
                    <a href="javascript:void(0)" class="topic-q-link" data-question="What financial lessons, saving strategies, and retirement advice do experienced people share?" onclick="window.askQuestion(this.getAttribute('data-question'))" style="font-size: 0.87rem; padding: 0.45rem 0.75rem; background: var(--color-bg-base); border-radius: var(--radius-sm); color: var(--color-text-main); text-decoration: none; cursor: pointer;">
                        <?php echo $t['topic_money']; ?>
                    </a>
                    <a href="javascript:void(0)" class="topic-q-link" data-question="What are the most profound life lessons people wish they had learned earlier, and how do they find purpose?" onclick="window.askQuestion(this.getAttribute('data-question'))" style="font-size: 0.87rem; padding: 0.45rem 0.75rem; background: var(--color-bg-base); border-radius: var(--radius-sm); color: var(--color-text-main); text-decoration: none; cursor: pointer;">
                        <?php echo $t['topic_purpose']; ?>
                    </a>
                </div>
            </div>

            <!-- Common Demo Questions -->
            <div style="margin-bottom: 1rem;">
                <h3 style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--color-text-muted); margin-bottom: 0.6rem;"><?php echo $t['ask_common_q']; ?></h3>
                <div style="display: flex; flex-direction: column; gap: 0.35rem;">
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
                    ];
                    foreach ($commonQs as $q):
                    ?>
                    <a href="javascript:void(0)" class="common-q-link" data-question="<?php echo htmlspecialchars($q[1], ENT_QUOTES, 'UTF-8'); ?>" onclick="window.askQuestion(this.getAttribute('data-question'))"
                       style="font-size: 0.8rem; padding: 0.45rem 0.7rem; background: var(--color-mint-bg); border-radius: var(--radius-sm); color: var(--color-primary); line-height: 1.45; border-left: 3px solid var(--color-primary); display: block; text-decoration: none; cursor: pointer;">
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
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--color-mint-bg); display: flex; align-items: center; justify-content: center; font-size: 1rem; color: var(--color-primary); font-weight: bold;">
                        <?php echo strtoupper(substr($user['display_name'] ?? 'M', 0, 1)); ?>
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
                        Log Out
                    </a>
                </div>
            <?php else: ?>
                <!-- Clean Guest Mode Footer -->
                <div>
                    <span class="step-badge notranslate" translate="no" style="background: var(--color-mint-bg); color: var(--color-primary); font-size: 0.8rem; margin-bottom: 0.35rem;">
                        GUEST SEARCH MODE
                    </span>
                    <p class="text-sm" style="font-size: 0.825rem; color: var(--color-text-muted); margin-bottom: 0.75rem;">
                        Searching wisdom archive as anonymous guest.
                    </p>
                    <a href="<?php echo APP_URL; ?>/" style="font-size: 0.85rem; color: var(--color-primary); font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem;">
                        &larr; Return to Homepage
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </aside>

    <!-- Right Main Chat Window -->
    <section class="ask-main-window">
        <!-- Chat Header -->
        <div class="ask-chat-header">
            <div>
                <h2 style="font-size: 1.25rem; margin-bottom: 0;">Ask LifeGPT Search</h2>
                <span class="text-sm" style="font-size: 0.85rem;">AI-assisted search across contributed stories</span>
            </div>
            
            <span class="step-badge notranslate" translate="no" style="background: var(--color-mint-bg); color: var(--color-primary); margin: 0;">Searching Contributed Stories</span>
        </div>

        <!-- Chat Messages Container -->
        <div class="ask-messages-container" id="askChatContainer">
            <?php if (empty($chatHistory)): ?>
                <!-- Empty State -->
                <div style="text-align: center; margin: auto 0; padding: 2rem;">
                    <h2 style="font-size: 2rem; margin-bottom: 0.5rem; color: var(--color-primary);">Ready when you are.</h2>
                    <p class="text-sm" style="max-width: 520px; margin: 0 auto 1.75rem auto; font-size: 1.05rem;">
                        Ask any question to search real life stories, lessons, and practical insights shared by contributors.
                    </p>

                    <!-- Example Q&A Showcase Card -->
                    <div class="card-hover" data-question="What practical guidance do people share about changing careers, switching industries, or starting anew after 40?" onclick="window.askQuestion(this.getAttribute('data-question'))" style="max-width: 720px; margin: 0 auto 2rem auto; text-align: left; background: #FFFFFF; border: 1px solid var(--color-border); border-left: 4px solid var(--color-amber); border-radius: var(--radius-md); padding: 1.25rem 1.5rem; box-shadow: var(--shadow-subtle); cursor: pointer;" title="Click to search this question">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span class="step-badge notranslate" translate="no" style="background: var(--color-amber-light); color: var(--color-amber); font-size: 0.75rem; margin-bottom: 0;">SAMPLE SEARCH</span>
                            <span class="text-sm" style="font-size: 0.8rem;">Click prompt below to search &rarr;</span>
                        </div>
                        <div style="font-weight: 600; font-size: 0.95rem; color: var(--color-primary); margin-bottom: 0.35rem;">
                            Q: &ldquo;What advice do people share about changing careers later in life?&rdquo; 
                        </div>
                        <p style="font-size: 0.9rem; line-height: 1.55; color: var(--color-text-main); margin-bottom: 0.5rem;">
                            LifeGPT: &ldquo;Contributors emphasize starting with small freelance experiments before quitting, treating decades of problem-solving as your greatest asset, and being comfortable being a beginner again.&rdquo; 
                        </p>
                        <div class="notranslate" translate="no" style="font-size: 0.78rem; color: var(--color-primary); font-weight: 600;">
                            AI-assisted search across contributed stories
                        </div>
                    </div>

                    <!-- 3 Prompt Suggestion Cards -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; max-width: 780px; margin: 0 auto;">
                        <div class="card card-hover" data-question="What is the most important life lesson older adults wish they had learned earlier in their twenties?" onclick="window.askQuestion(this.getAttribute('data-question'))" style="cursor: pointer; text-align: left; padding: 1.25rem;">
                            <strong style="font-size: 0.95rem; color: var(--color-primary); display: block; margin-bottom: 0.35rem;">Life Lessons</strong>
                            <p class="text-sm" style="margin-bottom: 0;">&ldquo;What is the most important lesson people wish they learned in their 20s?&rdquo;</p>
                        </div>

                        <div class="card card-hover" data-question="What stories and practical lessons do people share about recovering from career failure, financial collapse, or setbacks?" onclick="window.askQuestion(this.getAttribute('data-question'))" style="cursor: pointer; text-align: left; padding: 1.25rem;">
                            <strong style="font-size: 0.95rem; color: var(--color-primary); display: block; margin-bottom: 0.35rem;">Bouncing Back</strong>
                            <p class="text-sm" style="margin-bottom: 0;">&ldquo;How do people bounce back from major failure and setbacks?&rdquo;</p>
                        </div>

                        <div class="card card-hover" data-question="What advice do people share about maintaining healthy relationships, marriages, and resolving conflicts over the years?" onclick="window.askQuestion(this.getAttribute('data-question'))" style="cursor: pointer; text-align: left; padding: 1.25rem;">
                            <strong style="font-size: 0.95rem; color: var(--color-primary); display: block; margin-bottom: 0.35rem;">Family &amp; Relationships</strong>
                            <p class="text-sm" style="margin-bottom: 0;">&ldquo;What advice do people share about long-lasting relationships?&rdquo;</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Active Conversation State -->
                <?php foreach ($chatHistory as $msg): ?>
                    <?php if ($msg['role'] === 'user'): ?>
                        <div class="chat-bubble chat-bubble-user" style="align-self: flex-end; max-width: 75%;">
                            <div class="chat-bubble-meta">You &bull; <span class="notranslate" translate="no"><?php echo $msg['time']; ?></span></div>
                            <p style="font-size: 1.05rem; line-height: 1.5; margin: 0;" class="<?php echo ($uiLang !== 'en') ? 'notranslate' : ''; ?>" translate="<?php echo ($uiLang !== 'en') ? 'no' : 'yes'; ?>"><?php echo nl2br(htmlspecialchars($msg['content'])); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="chat-bubble chat-bubble-ai" style="align-self: flex-start; max-width: 85%;">
                            <div class="chat-bubble-meta" style="display: flex; align-items: center; gap: 0.4rem;">
                                <strong class="notranslate" translate="no">LifeGPT Host</strong> &bull; <span class="notranslate" translate="no"><?php echo $msg['time']; ?></span>
                            </div>
                            <p style="font-size: 1.05rem; line-height: 1.6; margin-bottom: 0.75rem;" class="<?php echo ($uiLang !== 'en') ? 'notranslate' : ''; ?>" translate="<?php echo ($uiLang !== 'en') ? 'no' : 'yes'; ?>"><?php echo nl2br(htmlspecialchars($msg['content'])); ?></p>
                            
                            <?php
                            $groundingScore = isset($msg['grounding_score']) ? (int)$msg['grounding_score'] : (isset($msg['accuracy']) ? (int)$msg['accuracy'] : null);
                            if ($groundingScore !== null && SHOW_GROUNDING_SCORE):
                                $confLabel = $msg['confidence_label'] ?? ($groundingScore >= 80 ? 'High grounding' : ($groundingScore >= 60 ? 'Moderate grounding' : ($groundingScore >= 40 ? 'Limited grounding' : 'Insufficient grounding')));
                                $confColor = $msg['confidence_color'] ?? ($groundingScore >= 80 ? '#16a34a' : ($groundingScore >= 60 ? '#d97706' : ($groundingScore >= 40 ? '#ea580c' : '#dc2626')));
                                $confBadge = $msg['confidence_badge'] ?? ($groundingScore >= 80 ? '#dcfce7' : ($groundingScore >= 60 ? '#fef3c7' : ($groundingScore >= 40 ? '#ffedd5' : '#fee2e2')));
                                $srcCount  = (int)($msg['sources_count'] ?? count($msg['sources'] ?? []));
                                $disclaimer = $msg['disclaimer'] ?? 'This score reflects how strongly the answer is supported by relevant LifeGPT experiences. It is not a guarantee of factual correctness.';
                            ?>
                            <div style="margin-top: 0.85rem; padding: 0.85rem 1rem; background: #f8fafc; border: 1px solid var(--color-border); border-radius: var(--radius-sm);">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.4rem; flex-wrap: wrap; gap: 0.5rem;">
                                    <div style="display: flex; align-items: center; gap: 0.4rem;">
                                        <strong style="font-size: 0.88rem; color: var(--color-primary);"><span class="notranslate" translate="no">LifeGPT</span> Grounding Score:</strong>
                                        <span class="notranslate" translate="no" style="font-size: 0.82rem; font-weight: 700; padding: 0.15rem 0.55rem; border-radius: 999px; background: <?php echo htmlspecialchars($confBadge); ?>; color: <?php echo htmlspecialchars($confColor); ?>;">
                                            <?php echo $groundingScore; ?>% &bull; <?php echo htmlspecialchars($confLabel); ?>
                                        </span>
                                    </div>
                                    <span style="font-size: 0.8rem; color: var(--color-text-muted);">
                                        <?php echo ($srcCount > 0) ? 'Based on <span class="notranslate" translate="no">' . $srcCount . '</span> relevant LifeGPT ' . ($srcCount === 1 ? 'experience' : 'experiences') : 'Limited archive match'; ?>
                                    </span>
                                </div>

                                <!-- Grounding Progress Bar -->
                                <div style="height: 6px; background: #e2e8f0; border-radius: 99px; overflow: hidden; margin-bottom: 0.45rem;">
                                    <div style="height: 100%; width: <?php echo min(100, max(4, $groundingScore)); ?>%; background: <?php echo htmlspecialchars($confColor); ?>; border-radius: 99px; transition: width 0.4s ease;"></div>
                                </div>

                                <p style="font-size: 0.76rem; color: var(--color-text-muted); margin: 0; line-height: 1.4;">
                                    <?php echo htmlspecialchars($disclaimer); ?>
                                </p>

                                <!-- Supporting Experiences Drawer -->
                                <?php if (!empty($msg['sources'])): ?>
                                <details style="margin-top: 0.65rem; border-top: 1px dashed var(--color-border); padding-top: 0.45rem;">
                                    <summary style="font-size: 0.8rem; font-weight: 600; color: var(--color-primary); cursor: pointer; user-select: none;">
                                        View supporting experiences (<span class="notranslate" translate="no"><?php echo count($msg['sources']); ?></span>) &darr;
                                    </summary>
                                    <div style="display: flex; flex-direction: column; gap: 0.4rem; margin-top: 0.5rem;">
                                        <?php foreach ($msg['sources'] as $src): ?>
                                            <div style="font-size: 0.78rem; background: #ffffff; border: 1px solid var(--color-border); border-left: 3px solid var(--color-primary); border-radius: 4px; padding: 0.4rem 0.6rem;">
                                                <div style="display: flex; justify-content: space-between; font-weight: 600; color: var(--color-primary); margin-bottom: 0.2rem;">
                                                    <span>Experience #<span class="notranslate" translate="no"><?php echo $src['experience_num'] ?? '1'; ?></span> &mdash; <?php echo htmlspecialchars($src['topic'] ?? 'Life Experience'); ?></span>
                                                    <span style="color: var(--color-text-muted); font-weight: 400;"><?php echo htmlspecialchars($src['author'] ?? 'Anonymous Contributor'); ?></span>
                                                </div>
                                                <div style="color: var(--color-text-main); font-style: italic; line-height: 1.4;">
                                                    &ldquo;<?php echo htmlspecialchars($src['key_insight'] ?? ($src['text'] ?? '')); ?>&rdquo;
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </details>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <div class="citation-tag notranslate" translate="no">
                                AI-assisted search across contributed stories
                            </div>

                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Sticky Floating Search Bar Input -->
        <div class="ask-input-container">
            <form action="" method="POST" id="askForm" class="ask-form">
                <?php echo CSRF::getInput(); ?>
                <div class="ask-input-row">
                    <input type="text" name="query" id="askQueryInput" class="form-control" placeholder="Ask LifeGPT anything (e.g., How to navigate career change?)" required>
                </div>
                <button type="submit" class="btn btn-primary ask-submit-btn">
                    <span class="ask-btn-desktop">Send &rarr;</span>
                    <span class="ask-btn-mobile">Ask Question &rarr;</span>
                </button>
            </form>
        </div>
    </section>

</div>

<script>
window.LifeGPTConfig = {
    appUrl: '<?php echo APP_URL; ?>',
    csrfToken: '<?php echo CSRF::getToken(); ?>',
    showGroundingScore: <?php echo SHOW_GROUNDING_SCORE ? 'true' : 'false'; ?>
};
</script>
<script src="<?php echo APP_URL; ?>/assets/js/ask.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/ask.js'); ?>"></script>
<script>
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

<?php
/**
 * LifeGPT - Completed Story Review Page
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$isLoggedIn = Auth::isLoggedIn();
$user = Auth::getCurrentUser();

$uuid = $_GET['uuid'] ?? $_SESSION['active_interview_uuid'] ?? '';
$token = $_GET['token'] ?? $_SESSION['guest_return_token'] ?? '';
$isSavedView = isset($_GET['saved']) && $_GET['saved'] == 1;

if (empty($uuid)) {
    header("Location: " . APP_URL . "/interview/start.php");
    exit;
}

// Fetch Interview details
$interview = DB::fetch(
    "SELECT i.*, p.name as persona_name, t.name as topic_name 
     FROM lg_interviews i
     JOIN lg_interviewer_personas p ON i.persona_id = p.persona_id
     JOIN lg_interview_topics t ON i.topic_id = t.topic_id
     WHERE i.uuid = :uuid",
    ['uuid' => $uuid]
);

if (!$interview) {
    header("Location: " . APP_URL . "/interview/start.php");
    exit;
}

$interviewId = (int)$interview['interview_id'];

// Fetch Summary & Messages Transcript
$summary = DB::fetch("SELECT * FROM lg_interview_summaries WHERE interview_id = :id", ['id' => $interviewId]);
if (!$summary) {
    require_once __DIR__ . '/../includes/interview-engine.php';
    $summary = InterviewEngine::generateSummary($interview);
}

$messages = DB::fetchAll(
    "SELECT * FROM lg_interview_messages WHERE interview_id = :id ORDER BY sequence ASC",
    ['id' => $interviewId]
);

// Delete Story Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_story') {
    CSRF::validateRequest();
    DB::query("DELETE FROM lg_interviews WHERE interview_id = :id", ['id' => $interviewId]);
    unset($_SESSION['active_interview_id']);
    unset($_SESSION['active_interview_uuid']);
    
    if ($isLoggedIn) {
        header("Location: " . APP_URL . "/dashboard/");
    } else {
        header("Location: " . APP_URL . "/?story_deleted=1");
    }
    exit;
}

$pageTitle = "Completed Story Review";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 860px; margin: 1rem auto 4rem auto;">

    <!-- Top Confirmation Banner -->
    <div style="text-align: center; margin-bottom: 2.5rem;">
        <span class="step-badge" style="background-color: var(--color-mint-bg); color: var(--color-primary);">STORY COMPLETED</span>
        <h1 style="font-size: 2.75rem; margin-bottom: 0.5rem; color: var(--color-primary);">Your Completed Story Review</h1>
        <p class="text-sm" style="font-size: 1.1rem; color: var(--color-text-muted);">Review your audio session, full transcript, and AI-extracted key takeaways.</p>
    </div>

    <!-- 1. Audio Player Bar -->
    <div class="card" style="margin-bottom: 2rem; border-left: 6px solid var(--color-primary); background: #FFFFFF;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 52px; height: 52px; background: var(--color-mint-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">🎵</div>
                <div>
                    <strong style="font-size: 1.1rem; color: var(--color-primary); display: block;">Recorded Audio Session</strong>
                    <span class="text-sm">Recorded Session Length: 04:12 mins recorded</span>
                </div>
            </div>

            <audio controls style="max-width: 320px; outline: none;">
                <source src="" type="audio/mpeg">
                Your browser does not support audio playback.
            </audio>
        </div>
    </div>

    <!-- 2. AI Key Takeaways & Summary (With Edit Toggle) -->
    <div class="card" style="margin-bottom: 2rem; border-top: 5px solid var(--color-primary-light);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.75rem;">
            <h2 style="font-size: 1.6rem; margin-bottom: 0;">AI Key Takeaways & Summary</h2>
            <button type="button" class="btn btn-secondary" onclick="toggleEditSummaryMode()" style="min-height: 38px; padding: 0.35rem 1rem; font-size: 0.875rem;">
                ✏️ Edit Summary
            </button>
        </div>

        <!-- Read-Only View -->
        <div id="summaryReadOnlyView" style="display: block;">
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 1.25rem;">
                <li style="display: flex; gap: 0.85rem; align-items: flex-start;">
                    <span style="color: var(--color-primary); font-size: 1.2rem;">💡</span>
                    <div>
                        <strong style="color: var(--color-primary);">Overall Story Summary</strong>
                        <p style="margin-top: 0.25rem; font-size: 1.05rem;"><?php echo nl2br(htmlspecialchars($summary['story_summary'])); ?></p>
                    </div>
                </li>

                <li style="display: flex; gap: 0.85rem; align-items: flex-start;">
                    <span style="color: var(--color-primary); font-size: 1.2rem;">🌿</span>
                    <div>
                        <strong style="color: var(--color-primary);">Main Lesson Learned</strong>
                        <p style="margin-top: 0.25rem; font-size: 1.05rem;"><?php echo nl2br(htmlspecialchars($summary['main_lesson'])); ?></p>
                    </div>
                </li>

                <li style="display: flex; gap: 0.85rem; align-items: flex-start;">
                    <span style="color: var(--color-primary); font-size: 1.2rem;">⚡</span>
                    <div>
                        <strong style="color: var(--color-primary);">Turning Point / Key Decision</strong>
                        <p style="margin-top: 0.25rem; font-size: 1.05rem;"><?php echo nl2br(htmlspecialchars($summary['turning_point'])); ?></p>
                    </div>
                </li>

                <li style="display: flex; gap: 0.85rem; align-items: flex-start;">
                    <span style="color: var(--color-primary); font-size: 1.2rem;">🎯</span>
                    <div>
                        <strong style="color: var(--color-primary);">Practical Advice for Others</strong>
                        <p style="margin-top: 0.25rem; font-size: 1.05rem;"><?php echo nl2br(htmlspecialchars($summary['advice'])); ?></p>
                    </div>
                </li>

                <li style="display: flex; gap: 0.85rem; align-items: flex-start;">
                    <span style="color: var(--color-primary); font-size: 1.2rem;">💬</span>
                    <div>
                        <strong style="color: var(--color-primary);">Representative Quote</strong>
                        <blockquote style="font-style: italic; border-left: 4px solid var(--color-primary-light); padding-left: 1rem; margin-top: 0.35rem; font-size: 1.1rem; color: var(--color-text-muted);">
                            “<?php echo htmlspecialchars($summary['representative_quote']); ?>”
                        </blockquote>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Editable Form View -->
        <form action="<?php echo APP_URL; ?>/api/interview-summary.php" method="POST" id="summaryEditForm" style="display: none; margin-top: 1rem;">
            <?php echo CSRF::getInput(); ?>
            <input type="hidden" name="interview_uuid" value="<?php echo htmlspecialchars($uuid); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="form-group">
                <label for="story_summary" class="form-label">Story Summary</label>
                <textarea id="story_summary" name="story_summary" class="form-control" required><?php echo htmlspecialchars($summary['story_summary']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="main_lesson" class="form-label">Main Lesson</label>
                <textarea id="main_lesson" name="main_lesson" class="form-control" required><?php echo htmlspecialchars($summary['main_lesson']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="advice" class="form-label">Advice</label>
                <textarea id="advice" name="advice" class="form-control"><?php echo htmlspecialchars($summary['advice']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="representative_quote" class="form-label">Representative Quote</label>
                <textarea id="representative_quote" name="representative_quote" class="form-control" required><?php echo htmlspecialchars($summary['representative_quote']); ?></textarea>
            </div>

            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                <button type="button" class="btn btn-outline" onclick="toggleEditSummaryMode()">Cancel</button>
            </div>
        </form>
    </div>

    <!-- 3. Full AI Transcript Box -->
    <div class="card" style="margin-bottom: 2.5rem;">
        <h2 style="font-size: 1.5rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.5rem; color: var(--color-primary);">Full AI Transcript Box</h2>
        
        <div style="max-height: 280px; overflow-y: auto; display: flex; flex-direction: column; gap: 1rem; padding-right: 0.5rem;">
            <?php foreach ($messages as $msg): ?>
                <div style="padding: 1rem; border-radius: var(--border-radius-md); background: <?php echo ($msg['role'] === 'interviewer') ? 'var(--color-bg-base)' : '#F0FDF4'; ?>; border: 1px solid var(--color-border);">
                    <strong style="color: var(--color-primary); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">
                        <?php echo ($msg['role'] === 'interviewer') ? '🌱 ' . htmlspecialchars($interview['persona_name']) : '👤 You'; ?>
                    </strong>
                    <p style="font-size: 1rem; margin-bottom: 0;"><?php echo htmlspecialchars($msg['text']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Footer Actions -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.25rem;">
        <form action="" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this story session?');">
            <?php echo CSRF::getInput(); ?>
            <input type="hidden" name="action" value="delete_story">
            <button type="submit" class="btn btn-danger">🗑️ Delete Story & Exit</button>
        </form>

        <?php if ($isLoggedIn): ?>
            <!-- Logged In Member Button: Save Story & Go to Dashboard -->
            <a href="<?php echo APP_URL; ?>/dashboard/?completed=1&uuid=<?php echo htmlspecialchars($uuid); ?>" class="btn btn-primary text-lg" style="padding: 0.85rem 2.5rem;">
                💾 Save Story & Go to Dashboard ➔
            </a>
        <?php else: ?>
            <!-- Anonymous Guest Button -->
            <a href="<?php echo APP_URL; ?>/?submitted=1" class="btn btn-primary text-lg" style="padding: 0.85rem 2.5rem;">
                🚀 Submit Anonymously to Library
            </a>
        <?php endif; ?>
    </div>

</div>

<script>
function toggleEditSummaryMode() {
    const readView = document.getElementById('summaryReadOnlyView');
    const editForm = document.getElementById('summaryEditForm');
    if (readView.style.display === 'none') {
        readView.style.display = 'block';
        editForm.style.display = 'none';
    } else {
        readView.style.display = 'none';
        editForm.style.display = 'block';
    }
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

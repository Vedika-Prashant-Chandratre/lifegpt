<?php
/**
 * LifeGPT - Member User Dashboard
 * (A FiftyIsNifty research initiative)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

Auth::requireLogin();
$user = Auth::getCurrentUser();

// 1. Auto-heal: Ensure any interview that has a summary is marked as 'completed'
try {
    DB::query(
        "UPDATE lg_interviews i 
         JOIN lg_interview_summaries s ON i.interview_id = s.interview_id 
         SET i.status = 'completed', i.updated_at = CURRENT_TIMESTAMP 
         WHERE i.user_id = :user_id AND i.status = 'in_progress'",
        ['user_id' => $user['user_id']]
    );
} catch (Exception $e) {
    error_log("Auto-heal query warning: " . $e->getMessage());
}

// 2. Fetch member stories from database
$interviews = DB::fetchAll(
    "SELECT i.*, p.name AS persona_name, p.avatar AS persona_avatar, t.name AS topic_name, 
            s.story_summary, s.main_lesson, s.turning_point, s.advice, s.representative_quote, s.approved_summary
     FROM lg_interviews i
     LEFT JOIN lg_interviewer_personas p ON i.persona_id = p.persona_id
     LEFT JOIN lg_interview_topics t ON i.topic_id = t.topic_id
     LEFT JOIN lg_interview_summaries s ON i.interview_id = s.interview_id
     WHERE i.user_id = :user_id AND i.status != 'deleted'
     ORDER BY i.created_at DESC",
    ['user_id' => $user['user_id']]
);

// Check if just completed a story
$justCompletedUuid = $_GET['uuid'] ?? '';
$isCompletedSuccess = isset($_GET['completed']) && $_GET['completed'] == 1;

// Calculate stats
$totalStories = count($interviews);
$completedStories = 0;
$inProgressStories = 0;
$approvedQuotes = 0;

$targetStoryToEdit = null;

foreach ($interviews as $item) {
    if ($item['status'] === 'completed') {
        $completedStories++;
    } elseif ($item['status'] === 'in_progress') {
        $inProgressStories++;
    }
    if (!empty($item['approved_summary'])) {
        $approvedQuotes++;
    }

    if (!empty($justCompletedUuid) && $item['uuid'] === $justCompletedUuid) {
        $targetStoryToEdit = $item;
    }
}

// Fallback target if just completed
if ($isCompletedSuccess && !$targetStoryToEdit && !empty($interviews)) {
    foreach ($interviews as $item) {
        if ($item['status'] === 'completed') {
            $targetStoryToEdit = $item;
            break;
        }
    }
}

$pageTitle = "My Story Dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 1140px; margin: 1rem auto 4rem auto;">
    
    <!-- Completed Success Banner -->
    <?php if ($isCompletedSuccess): ?>
        <div class="alert alert-success" style="margin-bottom: 2rem; border-radius: var(--radius-md); padding: 1.25rem 1.5rem; background: #F0FDF4; border: 2px solid #BBF7D0; color: #166534; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div>
                <strong style="font-size: 1.1rem; display: block;">🎉 Story Completed & Saved to Your Private Archive!</strong>
                <span>Your AI host has organized your key takeaways below. You can review and edit your story summary anytime.</span>
            </div>
            <button type="button" class="btn btn-primary" onclick="openSummaryModal('<?php echo htmlspecialchars($targetStoryToEdit['uuid'] ?? ''); ?>')" style="min-height: 40px; font-size: 0.9rem;">
                📜 View Story Summary
            </button>
        </div>
    <?php endif; ?>

    <!-- Dashboard Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 2.5rem;">
        <div>
            <span class="step-badge" style="background-color: var(--color-mint-bg); color: var(--color-primary);">MEMBER DASHBOARD</span>
            <h1 style="font-size: 2.6rem; margin-bottom: 0.25rem;">
                Welcome back, <?php echo htmlspecialchars($user['display_name']); ?>
            </h1>
            <p class="text-sm">Manage your saved stories, active drafts, and approved wisdom takeaways.</p>
        </div>

        <div style="display: flex; gap: 0.85rem; flex-wrap: wrap;">
            <a href="<?php echo APP_URL; ?>/interview/start.php" class="btn btn-primary">➕ Start New Story</a>
            <a href="<?php echo APP_URL; ?>/ask/" class="btn btn-secondary">💬 Open AskGPT</a>
        </div>
    </div>

    <!-- Stats Counters -->
    <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
        <div class="metric-card">
            <div class="metric-value" style="color: var(--color-success);"><?php echo $completedStories; ?></div>
            <div class="metric-label">Completed Stories</div>
        </div>

        <div class="metric-card">
            <div class="metric-value" style="color: var(--color-amber);"><?php echo $inProgressStories; ?></div>
            <div class="metric-label">In Progress Drafts</div>
        </div>

        <div class="metric-card">
            <div class="metric-value" style="color: var(--color-primary);"><?php echo $approvedQuotes; ?></div>
            <div class="metric-label">Approved Quotes & Takeaways</div>
        </div>
    </div>

    <!-- Recent Activity Table / Stories List -->
    <div class="card" style="padding: 2rem; margin-top: 2.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.6rem; margin-bottom: 0;">My Saved Story Archive</h2>
            <a href="<?php echo APP_URL; ?>/interview/start.php" class="btn btn-primary" style="min-height: 40px; padding: 0.4rem 1.25rem; font-size: 0.9rem;">+ Start New Story</a>
        </div>

        <?php if (empty($interviews)): ?>
            <div style="text-align: center; padding: 3.5rem 1.5rem; background: var(--color-bg-base); border-radius: var(--radius-md); border: 2px dashed var(--color-border);">
                <div style="font-size: 3.2rem; margin-bottom: 1rem;">🌱</div>
                <h3 style="font-size: 1.4rem; margin-bottom: 0.5rem;">No stories recorded yet</h3>
                <p class="text-sm" style="max-width: 480px; margin: 0 auto 1.5rem auto;">
                    Share your first life story using our AI-guided storytelling host.
                </p>
                <a href="<?php echo APP_URL; ?>/interview/start.php" class="btn btn-primary">
                    Start Your First Member Story →
                </a>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--color-border); background: var(--color-bg-base);">
                            <th style="padding: 0.95rem;">Topic Theme</th>
                            <th style="padding: 0.95rem;">AI Host</th>
                            <th style="padding: 0.95rem;">Date Created</th>
                            <th style="padding: 0.95rem;">Status</th>
                            <th style="padding: 0.95rem; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($interviews as $row): ?>
                            <tr style="border-bottom: 1px solid var(--color-border);">
                                <td style="padding: 1.15rem 0.95rem;">
                                    <strong style="color: var(--color-primary); font-size: 1.05rem; display: block; margin-bottom: 0.2rem;">
                                        <?php echo htmlspecialchars($row['topic_name'] ?? 'Life Reflections'); ?>
                                    </strong>
                                    <?php if (!empty($row['story_summary'])): ?>
                                        <span class="text-sm" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; max-width: 420px; line-height: 1.4;">
                                            “<?php echo htmlspecialchars($row['story_summary']); ?>”
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1.15rem 0.95rem; font-weight: 600; color: var(--color-text-main);"><?php echo htmlspecialchars($row['persona_name'] ?? 'AI Host'); ?></td>
                                <td style="padding: 1.15rem 0.95rem; color: var(--color-text-muted);"><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                                <td style="padding: 1.15rem 0.95rem;">
                                    <?php if ($row['status'] === 'completed'): ?>
                                        <span class="step-badge" style="background: #F0FDF4; color: #166534; font-size: 0.75rem; margin: 0;">COMPLETED</span>
                                    <?php else: ?>
                                        <span class="step-badge" style="background: #FEF3C7; color: #92400E; font-size: 0.75rem; margin: 0;">IN PROGRESS</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1.15rem 0.95rem; text-align: right;">
                                    <?php if ($row['status'] === 'completed'): ?>
                                        <button type="button" class="btn btn-secondary" onclick="openSummaryModal('<?php echo $row['uuid']; ?>')" style="min-height: 38px; padding: 0.35rem 0.95rem; font-size: 0.85rem;">
                                            📜 View & Edit Summary
                                        </button>
                                    <?php else: ?>
                                        <a href="<?php echo APP_URL; ?>/interview/conversation.php" class="btn btn-primary" style="min-height: 38px; padding: 0.35rem 0.95rem; font-size: 0.85rem;">Resume Session →</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Summary Popup Modal for Members (Read-only view by default + Edit Mode toggle) -->
<div id="dashboardSummaryModal" class="modal-overlay" aria-hidden="true">
    <div class="modal-card" style="max-width: 760px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header">
            <div>
                <span class="step-badge" style="margin-bottom: 0.25rem;">STORY SUMMARY & TAKEAWAYS</span>
                <h2 style="font-size: 1.6rem; margin-bottom: 0;" id="modalTitleHeading">AI Key Takeaways & Summary</h2>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeSummaryModal()" aria-label="Close modal">✕</button>
        </div>

        <div style="background: var(--color-bg-base); padding: 1rem 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size: 1.5rem;">🎵</span>
                <div>
                    <strong style="font-size: 0.95rem; color: var(--color-primary); display: block;" id="modalTopicHostInfo">Recorded Audio Session</strong>
                    <span class="text-sm" style="font-size: 0.85rem;">04:12 mins recorded • AI Audio Transcript Saved</span>
                </div>
            </div>
            <button type="button" class="btn btn-secondary" id="btnToggleEdit" onclick="toggleDashboardEditMode()" style="min-height: 36px; padding: 0.3rem 0.85rem; font-size: 0.85rem;">
                ✏️ Edit Summary
            </button>
        </div>

        <!-- Read-Only View of AI Generated Takeaways -->
        <div id="dashboardSummaryReadView" style="display: block;">
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 1.25rem;">
                <li style="display: flex; gap: 0.85rem; align-items: flex-start;">
                    <span style="color: var(--color-primary); font-size: 1.2rem;">💡</span>
                    <div>
                        <strong style="color: var(--color-primary);">Overall Story Summary</strong>
                        <p style="margin-top: 0.25rem; font-size: 1.05rem;" id="read_story_summary"></p>
                    </div>
                </li>

                <li style="display: flex; gap: 0.85rem; align-items: flex-start;">
                    <span style="color: var(--color-primary); font-size: 1.2rem;">🌿</span>
                    <div>
                        <strong style="color: var(--color-primary);">Main Lesson Learned</strong>
                        <p style="margin-top: 0.25rem; font-size: 1.05rem;" id="read_main_lesson"></p>
                    </div>
                </li>

                <li style="display: flex; gap: 0.85rem; align-items: flex-start;">
                    <span style="color: var(--color-primary); font-size: 1.2rem;">⚡</span>
                    <div>
                        <strong style="color: var(--color-primary);">Turning Point / Key Decision</strong>
                        <p style="margin-top: 0.25rem; font-size: 1.05rem;" id="read_turning_point"></p>
                    </div>
                </li>

                <li style="display: flex; gap: 0.85rem; align-items: flex-start;">
                    <span style="color: var(--color-primary); font-size: 1.2rem;">🎯</span>
                    <div>
                        <strong style="color: var(--color-primary);">Practical Advice for Others</strong>
                        <p style="margin-top: 0.25rem; font-size: 1.05rem;" id="read_advice"></p>
                    </div>
                </li>

                <li style="display: flex; gap: 0.85rem; align-items: flex-start;">
                    <span style="color: var(--color-primary); font-size: 1.2rem;">💬</span>
                    <div>
                        <strong style="color: var(--color-primary);">Representative Quote</strong>
                        <blockquote style="font-style: italic; border-left: 4px solid var(--color-primary-light); padding-left: 1rem; margin-top: 0.35rem; font-size: 1.05rem; color: var(--color-text-muted);" id="read_representative_quote">
                        </blockquote>
                    </div>
                </li>
            </ul>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--color-border);">
                <button type="button" class="btn btn-outline" onclick="closeSummaryModal()">Close</button>
                <button type="button" class="btn btn-primary" onclick="toggleDashboardEditMode()">✏️ Edit Summary Text</button>
            </div>
        </div>

        <!-- Editable Form Mode -->
        <form action="<?php echo APP_URL; ?>/api/interview-summary.php" method="POST" id="modalSummaryForm" style="display: none;">
            <?php echo CSRF::getInput(); ?>
            <input type="hidden" name="interview_uuid" id="modalInterviewUuid" value="">

            <div class="form-group">
                <label for="modal_story_summary" class="form-label">Overall Story Summary</label>
                <textarea id="modal_story_summary" name="story_summary" class="form-control" required style="min-height: 95px;"></textarea>
            </div>

            <div class="form-group">
                <label for="modal_main_lesson" class="form-label">Main Lesson Learned</label>
                <textarea id="modal_main_lesson" name="main_lesson" class="form-control" required style="min-height: 85px;"></textarea>
            </div>

            <div class="form-group">
                <label for="modal_turning_point" class="form-label">Turning Point / Key Decision</label>
                <textarea id="modal_turning_point" name="turning_point" class="form-control" style="min-height: 85px;"></textarea>
            </div>

            <div class="form-group">
                <label for="modal_advice" class="form-label">Practical Advice for Others</label>
                <textarea id="modal_advice" name="advice" class="form-control" style="min-height: 85px;"></textarea>
            </div>

            <div class="form-group">
                <label for="modal_representative_quote" class="form-label">Representative Quote</label>
                <textarea id="modal_representative_quote" name="representative_quote" class="form-control" required style="min-height: 75px;"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--color-border);">
                <button type="button" class="btn btn-outline" onclick="toggleDashboardEditMode()">Cancel Edit</button>
                <button type="submit" class="btn btn-primary">💾 Save Summary Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
const storyMap = <?php echo json_encode($interviews); ?>;

function openSummaryModal(uuid) {
    let target = null;
    for (let s of storyMap) {
        if (s.uuid === uuid) {
            target = s;
            break;
        }
    }

    if (!target) return;

    document.getElementById('modalInterviewUuid').value = target.uuid;
    
    // Set Header info
    document.getElementById('modalTopicHostInfo').innerHTML = (target.topic_name || 'Life Reflections') + ' • Host: ' + (target.persona_name || 'AI Host');

    // Populate Read View
    document.getElementById('read_story_summary').innerText = target.story_summary || 'A personal reflection on ' + (target.topic_name || 'life experiences') + '.';
    document.getElementById('read_main_lesson').innerText = target.main_lesson || 'Resilience and key choices define life lessons.';
    document.getElementById('read_turning_point').innerText = target.turning_point || 'Making an important choice during a life turning point.';
    document.getElementById('read_advice').innerText = target.advice || 'Value relationships, stay curious, and trust your personal path.';
    document.getElementById('read_representative_quote').innerText = '“' + (target.representative_quote || target.story_summary || 'Your life has answers someone else needs.') + '”';

    // Populate Form Inputs
    document.getElementById('modal_story_summary').value = target.story_summary || '';
    document.getElementById('modal_main_lesson').value = target.main_lesson || '';
    document.getElementById('modal_turning_point').value = target.turning_point || '';
    document.getElementById('modal_advice').value = target.advice || '';
    document.getElementById('modal_representative_quote').value = target.representative_quote || '';

    // Show Read View initially
    document.getElementById('dashboardSummaryReadView').style.display = 'block';
    document.getElementById('modalSummaryForm').style.display = 'none';
    document.getElementById('btnToggleEdit').innerHTML = '✏️ Edit Summary';

    const modal = document.getElementById('dashboardSummaryModal');
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
}

function toggleDashboardEditMode() {
    const readView = document.getElementById('dashboardSummaryReadView');
    const editForm = document.getElementById('modalSummaryForm');
    const btn = document.getElementById('btnToggleEdit');

    if (readView.style.display === 'none') {
        readView.style.display = 'block';
        editForm.style.display = 'none';
        btn.innerHTML = '✏️ Edit Summary';
    } else {
        readView.style.display = 'none';
        editForm.style.display = 'block';
        btn.innerHTML = '👁️ View Summary';
    }
}

function closeSummaryModal() {
    const modal = document.getElementById('dashboardSummaryModal');
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
}

<?php if ($isCompletedSuccess && !empty($targetStoryToEdit)): ?>
document.addEventListener('DOMContentLoaded', function() {
    openSummaryModal('<?php echo htmlspecialchars($targetStoryToEdit['uuid']); ?>');
});
<?php endif; ?>
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

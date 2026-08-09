<?php
/**
 * LifeGPT - Review and Edit AI-generated Summary
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$uuid = $_GET['uuid'] ?? $_SESSION['active_interview_uuid'] ?? '';
$token = $_GET['token'] ?? $_SESSION['guest_return_token'] ?? '';
$isSavedView = isset($_GET['saved']) && $_GET['saved'] == 1;

if (empty($uuid)) {
    header("Location: " . APP_URL . "/interview/choose-persona.php");
    exit;
}

// 1. Fetch Interview details
$interview = DB::fetch(
    "SELECT i.*, p.name as persona_name, t.name as topic_name 
     FROM lg_interviews i
     JOIN lg_interviewer_personas p ON i.persona_id = p.persona_id
     JOIN lg_interview_topics t ON i.topic_id = t.topic_id
     WHERE i.uuid = :uuid",
    ['uuid' => $uuid]
);

if (!$interview) {
    header("Location: " . APP_URL . "/interview/choose-persona.php");
    exit;
}

$interviewId = (int)$interview['interview_id'];

// 2. Validate Ownership
$isAuthorized = false;
if ($interview['user_id'] !== null) {
    if (Auth::isLoggedIn() && (int)$_SESSION['user_id'] === (int)$interview['user_id']) {
        $isAuthorized = true;
    }
} else {
    // Guest validation
    $sessionActiveId = $_SESSION['active_interview_id'] ?? null;
    if ((int)$sessionActiveId === $interviewId) {
        $isAuthorized = true;
    } elseif (!empty($token)) {
        $tokenHash = hash('sha256', $token);
        $tokenRow = DB::fetch(
            "SELECT token_id FROM lg_guest_access_tokens WHERE interview_id = :id AND token_hash = :hash AND revocation_status = 0 AND expiry > NOW()",
            ['id' => $interviewId, 'hash' => $tokenHash]
        );
        if ($tokenRow) {
            $isAuthorized = true;
            // Restore guest session variables
            $_SESSION['active_interview_id'] = $interviewId;
            $_SESSION['active_interview_uuid'] = $interview['uuid'];
            $_SESSION['guest_return_token'] = $token;
        }
    }
}

if (!$isAuthorized) {
    http_response_code(403);
    echo "Access Denied: You do not have permissions to view this summary.";
    exit;
}

// 3. Fetch Summary & Consents (Create summary if missing)
$summary = DB::fetch("SELECT * FROM lg_interview_summaries WHERE interview_id = :id", ['id' => $interviewId]);
if (!$summary) {
    require_once __DIR__ . '/../includes/interview-engine.php';
    $summary = InterviewEngine::generateSummary($interview);
}

$consent = DB::fetch("SELECT * FROM lg_consents WHERE interview_id = :id", ['id' => $interviewId]);

// Render Flash Errors/Success if set
$flashError = $_SESSION['flash_error'] ?? '';
$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error']);
unset($_SESSION['flash_success']);

$pageTitle = "Review Your Summary";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <?php if ($isSavedView): ?>
        <!-- Guest Thank You / Confirmation Page -->
        <div class="card" style="text-align: center; margin-bottom: 2.5rem; border-color: var(--color-success); background-color: #f0fdf4;">
            <div style="font-size: 4rem; color: var(--color-success); margin-bottom: 1rem;">✔</div>
            <h1 style="color: var(--color-success); font-size: 2rem;">Story Submitted!</h1>
            <p class="text-lg" style="color: #166534; margin-bottom: 1.5rem;">
                Thank you! Your story has been saved and submitted to our administrators for review.
            </p>
            
            <?php if (!empty($token)): ?>
                <div style="padding: 1.25rem; background: #ffffff; border-radius: 8px; border: 1px solid #bbf7d0; text-align: left; margin: 0 auto 1rem auto; max-width: 600px;">
                    <strong style="color: #15803d; font-size: 0.95rem; display: block; margin-bottom: 0.5rem;">🔑 Your Private Access Link:</strong>
                    <p class="text-sm" style="margin-bottom: 0.75rem;">Bookmark this secure URL to return and view, edit, or delete this story in the future:</p>
                    <a href="<?php echo APP_URL; ?>/interview/summary.php?uuid=<?php echo $uuid; ?>&token=<?php echo $token; ?>" style="word-break: break-all; text-decoration: underline; color: #15803d; font-weight: 600;">
                        <?php echo APP_URL; ?>/interview/summary.php?uuid=<?php echo $uuid; ?>&token=<?php echo $token; ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="card" style="margin-bottom: 2.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--color-border); padding-bottom: 0.75rem; margin-bottom: 1.5rem;">
                <h2>Story Highlights</h2>
                <span class="persona-style-tag" style="background-color: #fef3c7; color: #d97706;">Pending Moderation</span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div>
                    <strong style="display: block; color: var(--color-primary);">Story Summary</strong>
                    <p style="margin-top: 0.25rem;"><?php echo nl2br(htmlspecialchars($summary['story_summary'])); ?></p>
                </div>
                <div>
                    <strong style="display: block; color: var(--color-primary);">Main Lesson</strong>
                    <p style="margin-top: 0.25rem;"><?php echo nl2br(htmlspecialchars($summary['main_lesson'])); ?></p>
                </div>
                <div>
                    <strong style="display: block; color: var(--color-primary);">Turning Point</strong>
                    <p style="margin-top: 0.25rem;"><?php echo nl2br(htmlspecialchars($summary['turning_point'])); ?></p>
                </div>
                <div>
                    <strong style="display: block; color: var(--color-primary);">Outcome</strong>
                    <p style="margin-top: 0.25rem;"><?php echo nl2br(htmlspecialchars($summary['outcome'])); ?></p>
                </div>
                <div>
                    <strong style="display: block; color: var(--color-primary);">Advice</strong>
                    <p style="margin-top: 0.25rem;"><?php echo nl2br(htmlspecialchars($summary['advice'])); ?></p>
                </div>
                <?php if (!empty($summary['funny_moment'])): ?>
                    <div>
                        <strong style="display: block; color: var(--color-primary);">Funny Moment</strong>
                        <p style="margin-top: 0.25rem;"><?php echo nl2br(htmlspecialchars($summary['funny_moment'])); ?></p>
                    </div>
                <?php endif; ?>
                <div>
                    <strong style="display: block; color: var(--color-primary);">Representative Quote</strong>
                    <blockquote style="font-style: italic; border-left: 4px solid var(--color-primary-light); padding-left: 1rem; margin-top: 0.5rem; font-size: 1.15rem; color: var(--color-text-muted);">
                        <?php echo htmlspecialchars($summary['representative_quote']); ?>
                    </blockquote>
                </div>
            </div>
            
            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <a href="<?php echo APP_URL; ?>/interview/summary.php?uuid=<?php echo $uuid; ?>&token=<?php echo $token; ?>" class="btn btn-secondary">Edit Settings / Story</a>
                <a href="<?php echo APP_URL; ?>/" class="btn btn-primary">Return to Homepage</a>
            </div>
        </div>

    <?php else: ?>
        <!-- Editing / Setup Approval Form -->
        <div style="text-align: center; margin-bottom: 2.5rem;">
            <span class="hero-subtitle">Final Step 4 of 4</span>
            <h1 style="font-size: 2.25rem; margin-top: 0.5rem;">Review & Edit Your Wisdom</h1>
            <p class="text-sm" style="font-size: 1.1rem; color: var(--color-text-muted);">Read through the summaries extracted by the AI below. You can freely edit any section to reflect your voice perfectly.</p>
        </div>

        <?php if (!empty($flashError)): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo htmlspecialchars($flashError); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($flashSuccess)): ?>
            <div class="alert alert-success">
                <strong>Success:</strong> <?php echo htmlspecialchars($flashSuccess); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo APP_URL; ?>/api/interview-summary.php" method="POST">
            <?php echo CSRF::getInput(); ?>
            <input type="hidden" name="interview_uuid" value="<?php echo htmlspecialchars($uuid); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div style="display: flex; flex-direction: column; gap: 2rem;">
                <!-- 1. Content Edits -->
                <div class="card">
                    <h2 style="border-bottom: 1px solid var(--color-border); padding-bottom: 0.5rem; margin-bottom: 1.5rem; color: var(--color-primary);">1. Story Summaries</h2>
                    
                    <div class="form-group">
                        <label for="story_summary" class="form-label">Story Summary (Overview)</label>
                        <textarea id="story_summary" name="story_summary" class="form-control" required><?php echo htmlspecialchars($summary['story_summary']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="main_lesson" class="form-label">Main Lesson Learned</label>
                        <textarea id="main_lesson" name="main_lesson" class="form-control" required><?php echo htmlspecialchars($summary['main_lesson']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="turning_point" class="form-label">Turning Point / Key Decision</label>
                        <textarea id="turning_point" name="turning_point" class="form-control" required><?php echo htmlspecialchars($summary['turning_point']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="outcome" class="form-label">Outcome of Decision</label>
                        <textarea id="outcome" name="outcome" class="form-control" required><?php echo htmlspecialchars($summary['outcome']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="advice" class="form-label">Advice for Younger Generations</label>
                        <textarea id="advice" name="advice" class="form-control" required><?php echo htmlspecialchars($summary['advice']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="funny_moment" class="form-label">Funny Moment / Mishap (Optional)</label>
                        <textarea id="funny_moment" name="funny_moment" class="form-control"><?php echo htmlspecialchars($summary['funny_moment'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="representative_quote" class="form-label">Representative Quote (Highlights your story in first-person)</label>
                        <textarea id="representative_quote" name="representative_quote" class="form-control" style="font-style: italic; min-height: 80px;" required><?php echo htmlspecialchars($summary['representative_quote']); ?></textarea>
                    </div>
                </div>

                <!-- 2. Consent Permissions Review -->
                <div class="card">
                    <h2 style="border-bottom: 1px solid var(--color-border); padding-bottom: 0.5rem; margin-bottom: 1.5rem; color: var(--color-primary);">2. Review Consent & Privacy Permissions</h2>
                    
                    <div class="form-check" style="margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--color-border);">
                        <input type="checkbox" id="rag_consent" name="rag_consent" class="form-check-input" <?php echo ($consent['rag_consent'] ?? 1) ? 'checked' : ''; ?>>
                        <label for="rag_consent" class="form-check-label">
                            <strong>Allow Ask LifeGPT Retrieval</strong><br>
                            <span class="text-sm">Allow this anonymized story summary to answer visitor searches.</span>
                        </label>
                    </div>

                    <div class="form-check" style="margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--color-border);">
                        <input type="checkbox" id="quotes_consent" name="quotes_consent" class="form-check-input" <?php echo ($consent['quotes_consent'] ?? 1) ? 'checked' : ''; ?>>
                        <label for="quotes_consent" class="form-check-label">
                            <strong>Allow Quote Display</strong><br>
                            <span class="text-sm">Allow displaying your representative quote publicly under attribution.</span>
                        </label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" id="research_consent" name="research_consent" class="form-check-input" <?php echo ($consent['research_consent'] ?? 1) ? 'checked' : ''; ?>>
                        <label for="research_consent" class="form-check-label">
                            <strong>Allow Academic Research Use</strong><br>
                            <span class="text-sm">Allow FiftyIsNifty researchers to include this in social history archives.</span>
                        </label>
                    </div>
                </div>

                <!-- 3. Attribution Options -->
                <div class="card">
                    <h2 style="border-bottom: 1px solid var(--color-border); padding-bottom: 0.5rem; margin-bottom: 1.5rem; color: var(--color-primary);">3. Select Attribution</h2>
                    
                    <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem;">
                        <?php $attrType = $consent['attribution_type'] ?? 'anonymous'; ?>
                        <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                            <input type="radio" name="attribution_type" value="anonymous" class="form-check-input" <?php echo ($attrType === 'anonymous') ? 'checked' : ''; ?>>
                            <span>Anonymous</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                            <input type="radio" name="attribution_type" value="first_name" class="form-check-input" <?php echo ($attrType === 'first_name') ? 'checked' : ''; ?>>
                            <span>First Name</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                            <input type="radio" name="attribution_type" value="nickname" class="form-check-input" <?php echo ($attrType === 'nickname') ? 'checked' : ''; ?>>
                            <span>Nickname / Initials</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                            <input type="radio" name="attribution_type" value="full_name" class="form-check-input" <?php echo ($attrType === 'full_name') ? 'checked' : ''; ?>>
                            <span>Full Name</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                            <input type="radio" name="attribution_type" value="private" class="form-check-input" <?php echo ($attrType === 'private') ? 'checked' : ''; ?>>
                            <span>Private Only (Locked to private dashboard only)</span>
                        </label>
                    </div>

                    <div class="form-group" id="attributionValueGroup" style="display: <?php echo in_array($attrType, ['first_name', 'nickname', 'full_name']) ? 'block' : 'none'; ?>;">
                        <label for="attribution_value" class="form-label">Enter display name:</label>
                        <input type="text" id="attribution_value" name="attribution_value" class="form-control" value="<?php echo htmlspecialchars($consent['attribution_value'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 3rem; gap: 1.5rem;">
                <button type="submit" class="btn btn-primary text-lg" style="padding: 1rem 3rem;">Save & Finalize Story</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const radioButtons = document.querySelectorAll('input[name="attribution_type"]');
    const valueGroup = document.getElementById('attributionValueGroup');
    const valueInput = document.getElementById('attribution_value');
    
    function toggleAttributionInput() {
        const selectedValue = document.querySelector('input[name="attribution_type"]:checked').value;
        if (selectedValue === 'first_name' || selectedValue === 'nickname' || selectedValue === 'full_name') {
            valueGroup.style.display = 'block';
            valueInput.required = true;
        } else {
            valueGroup.style.display = 'none';
            valueInput.required = false;
        }
    }
    
    radioButtons.forEach(btn => {
        btn.addEventListener('change', toggleAttributionInput);
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

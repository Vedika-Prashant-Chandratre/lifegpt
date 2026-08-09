<?php
/**
 * LifeGPT - Interview Completed Confirmation
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect if there is no active session
if (!isset($_SESSION['active_interview_id']) || !isset($_SESSION['active_interview_uuid'])) {
    header("Location: " . APP_URL . "/interview/choose-persona.php");
    exit;
}

$interviewId = (int)$_SESSION['active_interview_id'];
$interviewUuid = $_SESSION['active_interview_uuid'];

// Fetch details
$interview = DB::fetch(
    "SELECT i.*, p.name as persona_name 
     FROM lg_interviews i
     JOIN lg_interviewer_personas p ON i.persona_id = p.persona_id
     WHERE i.interview_id = :id AND i.uuid = :uuid",
    ['id' => $interviewId, 'uuid' => $interviewUuid]
);

if (!$interview) {
    header("Location: " . APP_URL . "/interview/choose-persona.php");
    exit;
}

// Generate return link for guest
$returnLink = '';
if ($interview['user_id'] === null && isset($_SESSION['guest_return_token'])) {
    $returnLink = APP_URL . "/interview/summary.php?uuid=" . $interviewUuid . "&token=" . $_SESSION['guest_return_token'];
}

$pageTitle = "Interview Complete!";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 650px; margin: 3rem auto; text-align: center;">
    <div class="card">
        <div style="font-size: 5rem; color: var(--color-success); margin-bottom: 1.5rem;">🎉</div>
        <h1 style="font-size: 2.25rem; margin-bottom: 1rem;">Congratulations!</h1>
        <h2 style="font-size: 1.35rem; color: var(--color-text-muted); font-weight: 500; margin-bottom: 2rem;">
            You have successfully completed your interview with <?php echo htmlspecialchars($interview['persona_name']); ?>.
        </h2>

        <p class="text-lg" style="margin-bottom: 2rem; color: var(--color-text-muted); line-height: 1.6;">
            Your answers have been securely autosaved. The AI is organizing your responses into a story summary, identifying key lessons, turning points, and representative quotes.
        </p>

        <?php if (!empty($returnLink)): ?>
            <div class="alert alert-info" style="text-align: left; flex-direction: column; margin-bottom: 2rem;">
                <strong style="font-size: 1.05rem; display: block; margin-bottom: 0.5rem; color: #1e40af;">🔑 Save Your Guest Access Link</strong>
                <p style="font-size: 0.95rem; margin-bottom: 1rem;">Since you participated as a guest, please copy and bookmark this secure link. It is the only way to return to view or delete your story later:</p>
                <div style="padding: 0.75rem; background: #ffffff; border-radius: 6px; border: 1px solid #bfdbfe; font-family: monospace; word-break: break-all; font-size: 0.9rem;">
                    <?php echo $returnLink; ?>
                </div>
            </div>
        <?php endif; ?>

        <div style="display: flex; flex-direction: column; gap: 1rem; align-items: center; margin-top: 2rem;">
            <a href="<?php echo APP_URL; ?>/interview/summary.php" class="btn btn-primary text-lg" style="width: 100%; padding: 1rem;">
                Review & Edit Summary
            </a>
            
            <?php if (!Auth::isLoggedIn()): ?>
                <p style="font-size: 0.95rem; color: var(--color-text-muted); margin-top: 1rem;">
                    Want to save multiple interviews and see how they impact community wisdom? <a href="<?php echo APP_URL; ?>/account/register.php">Create a free account</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

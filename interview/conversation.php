<?php
/**
 * LifeGPT - Story Question Session (Screen 2B)
 * Professional 2-column storytelling interface
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if (!isset($_SESSION['active_interview_id']) || !isset($_SESSION['active_interview_uuid'])) {
    $resumeUuid = $_GET['uuid'] ?? '';
    $resumeToken = $_GET['guest_token'] ?? '';
    if (!empty($resumeUuid)) {
        $candidate = DB::fetch("SELECT * FROM lg_interviews WHERE uuid = :uuid", ['uuid' => $resumeUuid]);
        if ($candidate && Auth::validateInterviewAccess($candidate, $resumeToken)) {
            $_SESSION['active_interview_id'] = (int)$candidate['interview_id'];
            $_SESSION['active_interview_uuid'] = $candidate['uuid'];
        }
    }
}

if (!isset($_SESSION['active_interview_id']) || !isset($_SESSION['active_interview_uuid'])) {
    header("Location: " . APP_URL . "/interview/start.php");
    exit;
}

$interviewId = (int)$_SESSION['active_interview_id'];
$interviewUuid = $_SESSION['active_interview_uuid'];
$isLoggedIn = Auth::isLoggedIn();

$interview = DB::fetch(
    "SELECT i.*, p.name as persona_name, p.avatar as persona_avatar, p.voice_settings, t.name as topic_name 
     FROM lg_interviews i
     JOIN lg_interviewer_personas p ON i.persona_id = p.persona_id
     JOIN lg_interview_topics t ON i.topic_id = t.topic_id
     WHERE i.interview_id = :id AND i.uuid = :uuid",
    ['id' => $interviewId, 'uuid' => $interviewUuid]
);

if (!$interview) {
    unset($_SESSION['active_interview_id']);
    unset($_SESSION['active_interview_uuid']);
    header("Location: " . APP_URL . "/interview/start.php");
    exit;
}

$pageTitle = "Story Session with " . htmlspecialchars($interview['persona_name']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="interview-layout">
    
    <!-- Left Sidebar: AI Host & Session Details -->
    <aside class="interview-sidebar">
        <div style="text-align: center; margin-bottom: 1.75rem;">
            <div style="width: 84px; height: 84px; font-size: 2.5rem; margin: 0 auto 0.85rem auto; background: var(--color-mint-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid #FFFFFF; box-shadow: 0 4px 14px rgba(27,67,50,0.08);">
                <?php 
                $emoji = '🧒';
                switch($interview['persona_avatar']) {
                    case 'grandchild': $emoji = '🧒'; break;
                    case 'journalist': $emoji = '🎤'; break;
                    case 'coach': $emoji = '🏃'; break;
                    case 'comedian': $emoji = '🎭'; break;
                    case 'historian': $emoji = '📜'; break;
                }
                echo $emoji;
                ?>
            </div>
            <h2 style="font-size: 1.4rem; margin-bottom: 0.2rem; color: var(--color-primary);"><?php echo htmlspecialchars($interview['persona_name']); ?></h2>
            <span class="text-sm" style="font-weight: 600;">AI Story Host</span>
        </div>
        
        <div style="border-top: 1px solid var(--color-border); padding-top: 1.25rem; margin-top: 1rem; display: flex; flex-direction: column; gap: 1rem;">
            <div>
                <span class="step-badge" style="margin-bottom: 0.35rem;">STORY THEME</span>
                <strong style="display: block; font-size: 1.05rem; color: var(--color-primary);"><?php echo htmlspecialchars($interview['topic_name']); ?></strong>
            </div>

            <div>
                <span class="step-badge" style="background: var(--color-amber-light); color: var(--color-amber); margin-bottom: 0.35rem;">PRIVACY LEVEL</span>
                <strong style="display: block; font-size: 1.05rem; color: var(--color-text-main);">
                    <?php echo $isLoggedIn ? 'Registered Member Story' : 'Guest Contributor'; ?>
                </strong>
            </div>
        </div>

        <div style="border-top: 1px solid var(--color-border); padding-top: 1.25rem; margin-top: 1.75rem;">
            <button id="btnPause" class="btn btn-outline" style="width: 100%; justify-content: center; min-height: 44px; font-size: 0.9rem;" aria-label="Pause Story">
                ⏸ Pause & Save Progress
            </button>
        </div>
    </aside>

    <!-- Right Main Session Window -->
    <section class="interview-main">
        <!-- Top Bar: Progress & Audio Replay -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.75rem;">
            <div style="display: flex; align-items: center; gap: 0.6rem;">
                <span class="step-badge" style="margin-bottom: 0;">PROGRESS</span>
                <span id="questionProgress" style="font-weight: 700; color: var(--color-primary); font-size: 1.05rem;">Question 1 of 5</span>
            </div>
            
            <button id="btnReplay" class="btn btn-outline" style="min-height: 38px; padding: 0.35rem 1rem; font-size: 0.85rem; border-radius: var(--radius-pill);" aria-label="Read Question Aloud">
                🔊 Read Question
            </button>
        </div>
        
        <div class="progress-bar-container">
            <div class="progress-bar-fill" id="progressBar" style="width: 20%;"></div>
        </div>

        <!-- Chat Bubble Thread Output -->
        <div class="chat-container" id="chatContainer">
            <!-- Active Question Bubble renders here via JS -->
        </div>

        <!-- Microphone Recording Active Alert -->
        <div class="speech-state-indicator" id="recordingIndicator" style="display: none;">
            🔴 Microphone Active — Listening to your story response...
        </div>

        <!-- Speech Transcript Review Box -->
        <div class="transcript-review-box" id="transcriptReviewBox" style="display: none;">
            <div class="transcript-review-header">📻 Speech Transcript Captured:</div>
            <p id="transcriptReviewText" style="font-style: italic; margin-bottom: 1rem; color: var(--color-text-main); font-size: 1.1rem; line-height: 1.5;"></p>
            
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <button type="button" id="btnAcceptTranscript" class="btn btn-primary" style="min-height: 40px; padding: 0.4rem 1.25rem; font-size: 0.9rem;">Use This Text</button>
                <button type="button" id="btnEditTranscript" class="btn btn-secondary" style="min-height: 40px; padding: 0.4rem 1.25rem; font-size: 0.9rem;">Edit Text</button>
                <button type="button" id="btnRetryTranscript" class="btn btn-outline" style="min-height: 40px; padding: 0.4rem 1.25rem; font-size: 0.9rem;">Try Again</button>
            </div>
        </div>

        <!-- Profanity Moderation Alert Banner -->
        <div class="profanity-alert-banner" id="profanityAlertBanner">
            ⚠️ Inappropriate language detected. Please edit your response to proceed.
        </div>

        <!-- User Answer Input Form -->
        <form id="answerForm" autocomplete="off" style="margin-top: auto; padding-top: 1rem;">
            <?php echo CSRF::getInput(); ?>
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label for="answerText" class="form-label" id="inputLabel" style="font-size: 1.05rem; font-weight: 700;">Type your response or speak aloud:</label>
                <textarea id="answerText" class="form-control" placeholder="Share your experience here... Take your time." style="font-size: 1.1rem; line-height: 1.6; min-height: 140px; border-radius: 16px;"></textarea>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-top: 1.25rem;">
                <div class="btn-group">
                    <?php if ($interview['input_mode'] !== 'typing'): ?>
                        <button type="button" id="btnRecord" class="btn btn-amber" aria-label="Start Voice Recording" style="padding: 0.75rem 1.75rem;">
                            🎙️ <span id="recordBtnText">Speak Answer</span>
                        </button>
                        <button type="button" id="btnStopRecord" class="btn btn-danger" aria-label="Stop Voice Recording" style="display: none; padding: 0.75rem 1.5rem;">
                            ⏹ Stop Recording
                        </button>
                    <?php endif; ?>
                    
                    <button type="submit" id="btnSubmit" class="btn btn-primary" style="padding: 0.75rem 2.25rem;">
                        Send Answer ➔
                    </button>
                </div>
                
                <div class="btn-group">
                    <button type="button" id="btnSkip" class="btn btn-outline" style="padding: 0.75rem 1.35rem;">
                        Skip
                    </button>
                    <button type="button" id="btnEndEarly" class="btn btn-secondary" style="padding: 0.75rem 1.5rem;">
                        🏁 Finish Story & View Summary
                    </button>
                </div>
            </div>
        </form>
    </section>

</div>

<script>
if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
    window.speechSynthesis.cancel();
}
window.LifeGPTConfig = {
    appUrl: '<?php echo APP_URL; ?>',
    interviewUuid: '<?php echo $interviewUuid; ?>',
    inputMode: '<?php echo $interview['input_mode']; ?>',
    isLoggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?>,
    guestToken: '<?php echo htmlspecialchars($_SESSION['guest_return_token'] ?? $_GET['guest_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>',
    csrfToken: '<?php echo CSRF::getToken(); ?>',
    voiceSettings: <?php echo !empty($interview['voice_settings']) ? $interview['voice_settings'] : 'null'; ?>
};
</script>

<script src="<?php echo APP_URL; ?>/assets/js/speech-recognition.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/speech-recognition.js'); ?>"></script>
<script src="<?php echo APP_URL; ?>/assets/js/speech-synthesis.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/speech-synthesis.js'); ?>"></script>
<script src="<?php echo APP_URL; ?>/assets/js/interview.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/interview.js'); ?>"></script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

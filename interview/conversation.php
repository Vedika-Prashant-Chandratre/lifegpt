<?php
/**
 * LifeGPT - Conversation/Interview Screen
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Redirect if there is no active interview session
if (!isset($_SESSION['active_interview_id']) || !isset($_SESSION['active_interview_uuid'])) {
    header("Location: " . APP_URL . "/interview/choose-persona.php");
    exit;
}

$interviewId = (int)$_SESSION['active_interview_id'];
$interviewUuid = $_SESSION['active_interview_uuid'];

// Fetch active interview details
$interview = DB::fetch(
    "SELECT i.*, p.name as persona_name, p.avatar as persona_avatar, p.voice_settings, t.name as topic_name 
     FROM lg_interviews i
     JOIN lg_interviewer_personas p ON i.persona_id = p.persona_id
     JOIN lg_interview_topics t ON i.topic_id = t.topic_id
     WHERE i.interview_id = :id AND i.uuid = :uuid",
    ['id' => $interviewId, 'uuid' => $interviewUuid]
);

if (!$interview) {
    // Clear session and redirect
    unset($_SESSION['active_interview_id']);
    unset($_SESSION['active_interview_uuid']);
    header("Location: " . APP_URL . "/interview/choose-persona.php");
    exit;
}

$pageTitle = "Interview with " . htmlspecialchars($interview['persona_name']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="interview-layout">
    <!-- Sidebar Meta Details -->
    <aside class="interview-sidebar">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <div class="persona-avatar-wrapper" style="width: 80px; height: 80px; font-size: 2rem; margin-bottom: 0.75rem;">
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
            <h2 style="font-size: 1.25rem; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($interview['persona_name']); ?></h2>
            <span class="text-sm">Your AI Interviewer</span>
        </div>
        
        <div style="border-top: 1px solid var(--color-border); padding-top: 1rem; margin-top: 1rem;">
            <p style="font-size: 0.95rem; margin-bottom: 0.5rem;"><strong style="color: var(--color-primary);">Topic:</strong> <?php echo htmlspecialchars($interview['topic_name']); ?></p>
            <p style="font-size: 0.95rem; margin-bottom: 0.5rem;"><strong style="color: var(--color-primary);">Mode:</strong> <?php echo ucfirst(htmlspecialchars($interview['input_mode'])); ?></p>
            <p style="font-size: 0.95rem; margin-bottom: 0.5rem;"><strong style="color: var(--color-primary);">Length:</strong> <?php echo ucfirst(htmlspecialchars($interview['duration_type'])); ?></p>
        </div>

        <div style="border-top: 1px solid var(--color-border); padding-top: 1rem; margin-top: 1.5rem; text-align: center;">
            <button id="btnPause" class="btn btn-outline" style="width: 100%; min-height: 40px; font-size: 1rem; padding: 0.5rem;" aria-label="Pause Interview">
                ⏸ Pause & Save Later
            </button>
        </div>
    </aside>

    <!-- Main Chat / Response Area -->
    <section class="interview-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
            <span id="questionProgress" style="font-weight: 600; color: var(--color-text-muted);">Preparing...</span>
            <button id="btnReplay" class="btn btn-outline" style="min-height: 36px; padding: 0.25rem 0.75rem; font-size: 0.9rem;" aria-label="Replay Question Voice">
                🔊 Replay Question
            </button>
        </div>
        
        <div class="progress-bar-container">
            <div class="progress-bar-fill" id="progressBar"></div>
        </div>

        <!-- Chat Output Messages -->
        <div class="chat-container" id="chatContainer">
            <!-- Active Question will render here dynamically -->
        </div>

        <!-- Microphone Recording State Alert -->
        <div class="speech-state-indicator" id="recordingIndicator" style="display: none;">
            🔴 Microphone Active - Listening to your story...
        </div>

        <!-- Voice Transcript Review State (Here is what I heard) -->
        <div class="transcript-review-box" id="transcriptReviewBox" style="display: none;">
            <div class="transcript-review-header">📻 Here is what I heard:</div>
            <p id="transcriptReviewText" style="font-style: italic; margin-bottom: 1rem; color: var(--color-text-main); font-size: 1.15rem; line-height: 1.5;"></p>
            
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <button type="button" id="btnAcceptTranscript" class="btn btn-primary" style="min-height: 40px; padding: 0.5rem 1.25rem; font-size: 1rem;">Use This</button>
                <button type="button" id="btnEditTranscript" class="btn btn-secondary" style="min-height: 40px; padding: 0.5rem 1.25rem; font-size: 1rem;">Edit Text</button>
                <button type="button" id="btnRetryTranscript" class="btn btn-outline" style="min-height: 40px; padding: 0.5rem 1.25rem; font-size: 1rem;">Try Again</button>
            </div>
        </div>

        <!-- User Input Control Form -->
        <form id="answerForm" autocomplete="off" style="margin-top: auto;">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="answerText" class="form-label" id="inputLabel">Type your response below:</label>
                <textarea id="answerText" class="form-control" placeholder="Share your experience here... Feel free to take your time." style="font-size: 1.15rem; line-height: 1.6; min-height: 150px;"></textarea>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-top: 1.5rem;">
                <div class="btn-group">
                    <?php if ($interview['input_mode'] !== 'typing'): ?>
                        <button type="button" id="btnRecord" class="btn btn-warning text-lg" aria-label="Start Voice Recording" style="padding: 0.75rem 2rem; gap: 0.5rem;">
                            🎙 <span id="recordBtnText">Speak Answer</span>
                        </button>
                        <button type="button" id="btnStopRecord" class="btn btn-danger" aria-label="Stop Voice Recording" style="display: none; padding: 0.75rem 1.5rem;">
                            ⏹ Stop
                        </button>
                    <?php endif; ?>
                    
                    <button type="submit" id="btnSubmit" class="btn btn-primary text-lg" style="padding: 0.75rem 2.5rem;">
                        Save & Next Question
                    </button>
                </div>
                
                <div class="btn-group">
                    <button type="button" id="btnSkip" class="btn btn-outline" aria-label="Skip Question" style="padding: 0.75rem 1.25rem;">
                        Skip
                    </button>
                    <button type="button" id="btnEndEarly" class="btn btn-danger" aria-label="End Interview and View Summary" style="padding: 0.75rem 1.25rem;">
                        Finish & Summarize
                    </button>
                </div>
            </div>
        </form>
    </section>
</div>

<!-- Configuration parameters passed to JavaScript safely -->
<script>
window.LifeGPTConfig = {
    appUrl: '<?php echo APP_URL; ?>',
    interviewUuid: '<?php echo $interviewUuid; ?>',
    inputMode: '<?php echo $interview['input_mode']; ?>',
    voiceSettings: <?php echo !empty($interview['voice_settings']) ? $interview['voice_settings'] : 'null'; ?>
};
</script>

<script src="<?php echo APP_URL; ?>/assets/js/speech-recognition.js"></script>
<script src="<?php echo APP_URL; ?>/assets/js/speech-synthesis.js"></script>
<script src="<?php echo APP_URL; ?>/assets/js/interview.js"></script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

<?php
/**
 * LifeGPT - Select Topic, Duration & Mode
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

// Redirect if persona is not chosen yet
if (!isset($_SESSION['setup_persona_id'])) {
    header("Location: " . APP_URL . "/interview/choose-persona.php");
    exit;
}

// Fetch active topics
$topics = DB::fetchAll("SELECT * FROM lg_interview_topics WHERE active = 1");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();
    
    $selectedTopicId = $_POST['topic_id'] ?? '';
    $duration = $_POST['duration'] ?? 'standard';
    $inputMode = $_POST['input_mode'] ?? 'mixed';
    
    if (empty($selectedTopicId)) {
        $error = 'Please select a topic for your interview.';
    } else {
        // Validate topic ID
        $isValidTopic = false;
        foreach ($topics as $t) {
            if ((int)$t['topic_id'] === (int)$selectedTopicId) {
                $isValidTopic = true;
                $_SESSION['setup_topic_id'] = (int)$t['topic_id'];
                $_SESSION['setup_topic_name'] = $t['name'];
                $_SESSION['setup_topic_key'] = $t['topic_key'];
                break;
            }
        }
        
        // Validate duration & mode
        $validDurations = ['quick', 'standard', 'deep'];
        $validModes = ['voice', 'typing', 'mixed'];
        
        if ($isValidTopic && in_array($duration, $validDurations) && in_array($inputMode, $validModes)) {
            $_SESSION['setup_duration'] = $duration;
            $_SESSION['setup_input_mode'] = $inputMode;
            
            header("Location: " . APP_URL . "/interview/consent.php");
            exit;
        } else {
            $error = 'Invalid configuration choice. Please verify your selections.';
        }
    }
}

// Get defaults from session
$currentTopicId = $_SESSION['setup_topic_id'] ?? '';
$currentDuration = $_SESSION['setup_duration'] ?? 'standard';
$currentInputMode = $_SESSION['setup_input_mode'] ?? 'mixed';

$pageTitle = "Choose Your Topic & Settings";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 2.5rem;">
        <span class="hero-subtitle">Step 2 of 4</span>
        <h1 style="font-size: 2.25rem; margin-top: 0.5rem;">Select Topic & Settings</h1>
        <p class="text-sm" style="font-size: 1.1rem; color: var(--color-text-muted);">What stories would you like to share today? Configure your preferences below.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" id="configForm">
        <?php echo CSRF::getInput(); ?>
        <input type="hidden" name="topic_id" id="selectedTopicId" value="<?php echo htmlspecialchars($currentTopicId); ?>">

        <!-- Section 1: Topics -->
        <h2 style="font-size: 1.5rem; margin-bottom: 1rem; border-bottom: 2px solid var(--color-border); padding-bottom: 0.5rem; color: var(--color-primary);">1. Choose a Topic</h2>
        <div class="topic-grid">
            <?php foreach ($topics as $t): 
                $isSelected = ((int)$t['topic_id'] === (int)$currentTopicId);
            ?>
                <div class="topic-card <?php echo $isSelected ? 'selected' : ''; ?>" 
                     data-id="<?php echo $t['topic_id']; ?>" 
                     tabindex="0"
                     role="radio"
                     aria-checked="<?php echo $isSelected ? 'true' : 'false'; ?>">
                    <h3 style="font-size: 1.15rem; margin-bottom: 0; color: inherit;"><?php echo htmlspecialchars($t['name']); ?></h3>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 3rem;">
            <!-- Section 2: Duration -->
            <div>
                <h2 style="font-size: 1.5rem; margin-bottom: 1rem; border-bottom: 2px solid var(--color-border); padding-bottom: 0.5rem; color: var(--color-primary);">2. Interview Length</h2>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <label class="card" style="display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; cursor: pointer; border-width: 2px;">
                        <input type="radio" name="duration" value="quick" class="form-check-input" style="width: 20px; height: 20px;" <?php echo ($currentDuration === 'quick') ? 'checked' : ''; ?>>
                        <div>
                            <strong style="font-size: 1.1rem; display: block;">Quick (3–5 minutes)</strong>
                            <span class="text-sm">Ideal for a single concise story. (Approx. 4 questions)</span>
                        </div>
                    </label>
                    <label class="card" style="display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; cursor: pointer; border-width: 2px;">
                        <input type="radio" name="duration" value="standard" class="form-check-input" style="width: 20px; height: 20px;" <?php echo ($currentDuration === 'standard') ? 'checked' : ''; ?>>
                        <div>
                            <strong style="font-size: 1.1rem; display: block;">Standard (7–10 minutes)</strong>
                            <span class="text-sm">Explores the event and its lessons. (Approx. 6–8 questions)</span>
                        </div>
                    </label>
                    <label class="card" style="display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; cursor: pointer; border-width: 2px;">
                        <input type="radio" name="duration" value="deep" class="form-check-input" style="width: 20px; height: 20px;" <?php echo ($currentDuration === 'deep') ? 'checked' : ''; ?>>
                        <div>
                            <strong style="font-size: 1.1rem; display: block;">Deep (Approx. 15 minutes)</strong>
                            <span class="text-sm">Comprehensive narrative details. (Approx. 8–12 questions)</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Section 3: Input Mode -->
            <div>
                <h2 style="font-size: 1.5rem; margin-bottom: 1rem; border-bottom: 2px solid var(--color-border); padding-bottom: 0.5rem; color: var(--color-primary);">3. How to Answer</h2>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <label class="card" style="display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; cursor: pointer; border-width: 2px;">
                        <input type="radio" name="input_mode" value="mixed" class="form-check-input" style="width: 20px; height: 20px;" <?php echo ($currentInputMode === 'mixed') ? 'checked' : ''; ?>>
                        <div>
                            <strong style="font-size: 1.1rem; display: block;">Mixed Mode (Recommended)</strong>
                            <span class="text-sm">Speak or type interchangeably as you prefer.</span>
                        </div>
                    </label>
                    <label class="card" style="display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; cursor: pointer; border-width: 2px;">
                        <input type="radio" name="input_mode" value="voice" class="form-check-input" style="width: 20px; height: 20px;" <?php echo ($currentInputMode === 'voice') ? 'checked' : ''; ?>>
                        <div>
                            <strong style="font-size: 1.1rem; display: block;">Voice Mode</strong>
                            <span class="text-sm">Use browser microphone voice transcripts to answer.</span>
                        </div>
                    </label>
                    <label class="card" style="display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; cursor: pointer; border-width: 2px;">
                        <input type="radio" name="input_mode" value="typing" class="form-check-input" style="width: 20px; height: 20px;" <?php echo ($currentInputMode === 'typing') ? 'checked' : ''; ?>>
                        <div>
                            <strong style="font-size: 1.1rem; display: block;">Typing Mode</strong>
                            <span class="text-sm">Standard keyboard text boxes. Great fallback.</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 3rem; border-top: 1px solid var(--color-border); padding-top: 2rem;">
            <a href="<?php echo APP_URL; ?>/interview/choose-persona.php" class="btn btn-outline">Back</a>
            <button type="submit" class="btn btn-primary text-lg" style="padding: 0.75rem 2.5rem;">Continue to Consent</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.topic-card');
    const input = document.getElementById('selectedTopicId');
    
    function selectTopic(card) {
        // Deselect all
        cards.forEach(c => {
            c.classList.remove('selected');
            c.setAttribute('aria-checked', 'false');
        });
        
        // Select this one
        card.classList.add('selected');
        card.setAttribute('aria-checked', 'true');
        
        // Set input
        const id = card.getAttribute('data-id');
        input.value = id;
    }
    
    cards.forEach(card => {
        card.addEventListener('click', function() {
            selectTopic(this);
        });
        
        card.addEventListener('keydown', function(e) {
            if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                selectTopic(this);
            }
        });
    });
});
</script>

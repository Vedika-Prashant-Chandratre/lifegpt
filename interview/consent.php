<?php
/**
 * LifeGPT - Privacy Consent Selection
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Redirect if setup sessions are missing
if (!isset($_SESSION['setup_persona_id']) || !isset($_SESSION['setup_topic_id'])) {
    header("Location: " . APP_URL . "/interview/choose-persona.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();
    
    $storage = isset($_POST['storage_consent']) ? 1 : 0;
    $rag = isset($_POST['rag_consent']) ? 1 : 0;
    $quotes = isset($_POST['quotes_consent']) ? 1 : 0;
    $research = isset($_POST['research_consent']) ? 1 : 0;
    $publication = isset($_POST['publication_consent']) ? 1 : 0;
    
    $attributionType = $_POST['attribution_type'] ?? 'anonymous';
    $attributionValue = trim($_POST['attribution_value'] ?? '');
    
    if (!$storage) {
        $error = 'You must grant storage consent so we can save and process your interview answers.';
    } else {
        // Save consent settings to session for database insertion
        $_SESSION['consent_storage'] = $storage;
        $_SESSION['consent_rag'] = $rag;
        $_SESSION['consent_quotes'] = $quotes;
        $_SESSION['consent_research'] = $research;
        $_SESSION['consent_publication'] = $publication;
        $_SESSION['consent_attribution_type'] = $attributionType;
        $_SESSION['consent_attribution_value'] = $attributionValue;
        
        try {
            DB::beginTransaction();
            
            // 1. Create Interview Row
            $uuid = Auth::generateUUID();
            $userId = Auth::isLoggedIn() ? (int)$_SESSION['user_id'] : null;
            $personaId = (int)$_SESSION['setup_persona_id'];
            $topicId = (int)$_SESSION['setup_topic_id'];
            $inputMode = $_SESSION['setup_input_mode'] ?? 'mixed';
            $durationType = $_SESSION['setup_duration'] ?? 'standard';
            
            $interviewId = DB::insert(
                "INSERT INTO lg_interviews (uuid, user_id, persona_id, topic_id, status, language, input_mode, duration_type) 
                 VALUES (:uuid, :user_id, :persona_id, :topic_id, 'in_progress', 'en', :input_mode, :duration_type)",
                [
                    'uuid' => $uuid,
                    'user_id' => $userId,
                    'persona_id' => $personaId,
                    'topic_id' => $topicId,
                    'input_mode' => $inputMode,
                    'duration_type' => $durationType
                ]
            );
            
            // 2. Create Consent Row
            DB::insert(
                "INSERT INTO lg_consents (interview_id, storage_consent, rag_consent, quotes_consent, research_consent, publication_consent, attribution_type, attribution_value, withdrawn, version) 
                 VALUES (:interview_id, :storage, :rag, :quotes, :research, :publication, :attribution_type, :attribution_value, 0, 1)",
                [
                    'interview_id' => $interviewId,
                    'storage' => $storage,
                    'rag' => $rag,
                    'quotes' => $quotes,
                    'research' => $research,
                    'publication' => $publication,
                    'attribution_type' => $attributionType,
                    'attribution_value' => !empty($attributionValue) ? $attributionValue : null
                ]
            );
            
            // 3. For Guest User, generate access token
            if (!$userId) {
                $guestToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $guestToken);
                $expiry = date('Y-m-d H:i:s', strtotime('+30 days')); // Guest can return within 30 days
                
                DB::insert(
                    "INSERT INTO lg_guest_access_tokens (interview_id, token_hash, expiry) 
                     VALUES (:interview_id, :token_hash, :expiry)",
                    [
                        'interview_id' => $interviewId,
                        'token_hash' => $tokenHash,
                        'expiry' => $expiry
                    ]
                );
                
                $_SESSION['guest_return_token'] = $guestToken;
            }
            
            // 4. Save active interview details in session
            $_SESSION['active_interview_id'] = $interviewId;
            $_SESSION['active_interview_uuid'] = $uuid;
            
            DB::commit();
            
            // Clean setup session vars
            unset($_SESSION['setup_persona_id']);
            unset($_SESSION['setup_topic_id']);
            
            // Redirect to conversation page!
            header("Location: " . APP_URL . "/interview/conversation.php");
            exit;
            
        } catch (Exception $e) {
            DB::rollBack();
            error_log("Failed to create interview record: " . $e->getMessage());
            $error = 'A database error occurred. Please try again.';
        }
    }
}

$pageTitle = "Privacy & Consent Settings";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 2.5rem;">
        <span class="hero-subtitle">Step 3 of 4</span>
        <h1 style="font-size: 2.25rem; margin-top: 0.5rem;">Privacy & Consent Settings</h1>
        <p class="text-sm" style="font-size: 1.1rem; color: var(--color-text-muted);">You retain full ownership of your stories. Please review how your words will be handled.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" id="consentForm">
        <?php echo CSRF::getInput(); ?>

        <div style="display: flex; flex-direction: column; gap: 2rem;">
            <!-- Granular Permissions -->
            <div class="card">
                <h2>1. Consent Permissions</h2>
                
                <div class="form-check" style="margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--color-border);">
                    <input type="checkbox" id="storage_consent" name="storage_consent" class="form-check-input" checked required>
                    <label for="storage_consent" class="form-check-label">
                        <strong>Allow Storage (Required)</strong><br>
                        <span class="text-sm">Allows us to save your interview answers, transcribe your speech, and organize your responses. If deselected, we cannot save your stories.</span>
                    </label>
                </div>
                
                <div class="form-check" style="margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--color-border);">
                    <input type="checkbox" id="rag_consent" name="rag_consent" class="form-check-input" checked>
                    <label for="rag_consent" class="form-check-label">
                        <strong>Allow AI Search Retrieval (Ask LifeGPT)</strong><br>
                        <span class="text-sm">Allows your anonymized stories and advice to be searched by visitors asking questions on the platform. Your name will never be exposed in search results.</span>
                    </label>
                </div>

                <div class="form-check" style="margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--color-border);">
                    <input type="checkbox" id="quotes_consent" name="quotes_consent" class="form-check-input" checked>
                    <label for="quotes_consent" class="form-check-label">
                        <strong>Allow Representative Quotes</strong><br>
                        <span class="text-sm">Allows the platform to highlight short excerpts or quotes from your interview in public feeds or research reports, credited under your chosen attribution below.</span>
                    </label>
                </div>

                <div class="form-check">
                    <input type="checkbox" id="research_consent" name="research_consent" class="form-check-input" checked>
                    <label for="research_consent" class="form-check-label">
                        <strong>Allow Academic Research Use</strong><br>
                        <span class="text-sm">Allows anonymized data to be reviewed by FiftyIsNifty educational program researchers studying community trends and social historical memories.</span>
                    </label>
                </div>
            </div>

            <!-- Attribution Selection -->
            <div class="card">
                <h2>2. Choose Your Attribution</h2>
                <p class="text-sm" style="margin-bottom: 1.5rem;">How would you like to be credited when your approved stories or quotes are read?</p>
                
                <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="radio" name="attribution_type" value="anonymous" class="form-check-input" checked>
                        <span><strong>Anonymous</strong> (Credited as "Anonymous")</span>
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="radio" name="attribution_type" value="first_name" class="form-check-input">
                        <span><strong>First Name</strong> (e.g. "Helen")</span>
                    </label>

                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="radio" name="attribution_type" value="nickname" class="form-check-input">
                        <span><strong>Nickname / Initials</strong> (e.g. "Grammy H" or "H.S.")</span>
                    </label>

                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="radio" name="attribution_type" value="full_name" class="form-check-input">
                        <span><strong>Full Name</strong> (e.g. "Helen Smith")</span>
                    </label>

                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                        <input type="radio" name="attribution_type" value="private" class="form-check-input">
                        <span><strong>Private Only</strong> (Stories are fully locked, visible only to you on your private dashboard)</span>
                    </label>
                </div>

                <div class="form-group" id="attributionValueGroup" style="margin-top: 2rem; display: none;">
                    <label for="attribution_value" class="form-label">Enter the name or nickname to display:</label>
                    <input type="text" id="attribution_value" name="attribution_value" class="form-control" placeholder="e.g. Grammy H">
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 3rem; border-top: 1px solid var(--color-border); padding-top: 2rem;">
            <a href="<?php echo APP_URL; ?>/interview/choose-topic.php" class="btn btn-outline">Back</a>
            <button type="submit" class="btn btn-primary text-lg" style="padding: 0.75rem 2.5rem;">Accept & Start My Interview</button>
        </div>
    </form>
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
            valueInput.value = '';
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

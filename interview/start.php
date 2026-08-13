<?php
/**
 * LifeGPT - Story Setup (Screen 2A)
 * Supports both Logged-In Member and Anonymous Guest storytelling setup.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$isLoggedIn = Auth::isLoggedIn();
$user = Auth::getCurrentUser();

// Fetch active personas & topics
$personas = DB::fetchAll("SELECT * FROM lg_interviewer_personas WHERE active = 1");
$topics = DB::fetchAll("SELECT * FROM lg_interview_topics WHERE active = 1");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();

    $personaId = (int)($_POST['persona_id'] ?? 1);
    $topicId = (int)($_POST['topic_id'] ?? 1);
    $durationType = $_POST['duration_type'] ?? 'standard';
    $displayName = trim($_POST['display_name'] ?? ($isLoggedIn ? $user['display_name'] : ''));
    $isAnonymous = isset($_POST['is_anonymous']) ? 1 : 0;

    try {
        DB::beginTransaction();

        $uuid = Auth::generateUUID();
        $userId = $isLoggedIn ? (int)$_SESSION['user_id'] : null;

        $interviewId = DB::insert(
            "INSERT INTO lg_interviews (uuid, user_id, persona_id, topic_id, status, language, input_mode, duration_type) 
             VALUES (:uuid, :user_id, :persona_id, :topic_id, 'in_progress', 'en', 'mixed', :duration_type)",
            [
                'uuid' => $uuid,
                'user_id' => $userId,
                'persona_id' => $personaId,
                'topic_id' => $topicId,
                'duration_type' => $durationType
            ]
        );

        // Insert Consent
        DB::insert(
            "INSERT INTO lg_consents (interview_id, storage_consent, rag_consent, quotes_consent, research_consent, publication_consent, attribution_type, attribution_value, withdrawn, version) 
             VALUES (:interview_id, 1, 1, 1, 1, 1, :attribution_type, :attribution_value, 0, 1)",
            [
                'interview_id' => $interviewId,
                'attribution_type' => ($isAnonymous || empty($displayName)) ? 'anonymous' : 'first_name',
                'attribution_value' => !empty($displayName) ? $displayName : null
            ]
        );

        if (!$userId) {
            $guestToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $guestToken);
            $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
            DB::insert(
                "INSERT INTO lg_guest_access_tokens (interview_id, token_hash, expiry) 
                 VALUES (:interview_id, :token_hash, :expiry)",
                ['interview_id' => $interviewId, 'token_hash' => $tokenHash, 'expiry' => $expiry]
            );
            $_SESSION['guest_return_token'] = $guestToken;
        }

        $_SESSION['active_interview_id'] = $interviewId;
        $_SESSION['active_interview_uuid'] = $uuid;

        DB::commit();

        header("Location: " . APP_URL . "/interview/conversation.php");
        exit;
    } catch (Exception $e) {
        DB::rollBack();
        error_log("Setup failed: " . $e->getMessage());
        $error = 'System error occurred while starting your story. Please try again.';
    }
}

$pageTitle = $isLoggedIn ? "Member Story Setup" : "Setup Your Story";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 920px; margin: 1rem auto 4rem auto;">
    
    <!-- Setup Header -->
    <div style="text-align: center; margin-bottom: 3rem;">
        <?php if ($isLoggedIn): ?>
            <span class="step-badge" style="background-color: var(--color-mint-bg); color: var(--color-primary);">MEMBER STORY SETUP (STEP 1 OF 2)</span>
            <h1 style="font-size: 2.75rem; margin-bottom: 0.5rem; color: var(--color-primary);">Customize Your Member Story Session</h1>
            <p class="text-sm" style="font-size: 1.1rem; color: var(--color-text-muted);">
                Welcome back, <strong><?php echo htmlspecialchars($user['display_name']); ?></strong>! Choose your host, story theme, and options before you begin.
            </p>
        <?php else: ?>
            <span class="step-badge" style="background-color: var(--color-mint-bg); color: var(--color-primary);">GUEST STORY SETUP (STEP 1 OF 2)</span>
            <h1 style="font-size: 2.75rem; margin-bottom: 0.5rem; color: var(--color-primary);">Customize Your Anonymous Story Session</h1>
            <p class="text-sm" style="font-size: 1.1rem; color: var(--color-text-muted);">Choose your host, story theme, and privacy preferences before you begin.</p>
        <?php endif; ?>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" id="storySetupForm">
        <?php echo CSRF::getInput(); ?>
        <input type="hidden" name="persona_id" id="personaIdInput" value="<?php echo $personas[0]['persona_id'] ?? 1; ?>">
        <input type="hidden" name="topic_id" id="topicIdInput" value="<?php echo $topics[0]['topic_id'] ?? 1; ?>">

        <!-- Section 1: Choose Persona -->
        <div class="card" style="margin-bottom: 2rem; border-top: 5px solid var(--color-primary);">
            <h2 style="font-size: 1.5rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>🎭</span> Section 1: Choose Your AI Host Persona
            </h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
                <?php foreach ($personas as $idx => $p): 
                    $emoji = '🧒';
                    switch($p['persona_key']) {
                        case 'grandchild': $emoji = '🧒'; break;
                        case 'journalist': $emoji = '🎤'; break;
                        case 'coach': $emoji = '🏃'; break;
                        case 'comedian': $emoji = '🎭'; break;
                        case 'historian': $emoji = '📜'; break;
                    }
                ?>
                    <div class="card persona-card <?php echo ($idx === 0) ? 'selected' : ''; ?>" 
                         data-id="<?php echo $p['persona_id']; ?>" 
                         onclick="selectPersona(this)" 
                         style="cursor: pointer; text-align: center; padding: 1.5rem; border: 2px solid var(--color-border); border-radius: var(--radius-md);">
                        <div style="font-size: 2.2rem; margin-bottom: 0.5rem;"><?php echo $emoji; ?></div>
                        <h3 style="font-size: 1.15rem; margin-bottom: 0.25rem; color: var(--color-primary);"><?php echo htmlspecialchars($p['name']); ?></h3>
                        <p class="text-sm" style="font-size: 0.85rem; font-style: italic;">“<?php echo htmlspecialchars(mb_substr($p['greeting'], 0, 60)); ?>...”</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Section 2: Select Topic Pills -->
        <div class="card" style="margin-bottom: 2rem; border-top: 5px solid var(--color-primary-light);">
            <h2 style="font-size: 1.5rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>📝</span> Section 2: Select Topic Theme
            </h2>

            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                <?php foreach ($topics as $idx => $t): ?>
                    <button type="button" class="btn <?php echo ($idx === 0) ? 'btn-primary' : 'btn-outline'; ?> topic-pill-btn" 
                            data-id="<?php echo $t['topic_id']; ?>" 
                            onclick="selectTopicPill(this)"
                            style="border-radius: var(--radius-pill); font-size: 0.95rem; padding: 0.6rem 1.35rem;">
                        <?php echo htmlspecialchars($t['name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Section 3: Story Length -->
        <div class="card" style="margin-bottom: 2rem; border-top: 5px solid var(--color-amber);">
            <h2 style="font-size: 1.5rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>⏱️</span> Section 3: Story Length
            </h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                <label style="border: 2px solid var(--color-border); border-radius: var(--radius-md); padding: 1.25rem; cursor: pointer; display: flex; gap: 0.75rem; align-items: flex-start;">
                    <input type="radio" name="duration_type" value="quick" class="form-check-input">
                    <div>
                        <strong style="font-size: 1.05rem; display: block;">Quick (3–5 mins)</strong>
                        <span class="text-sm">Single concise story (4 questions).</span>
                    </div>
                </label>

                <label style="border: 2px solid var(--color-primary); background: var(--color-mint-bg); border-radius: var(--radius-md); padding: 1.25rem; cursor: pointer; display: flex; gap: 0.75rem; align-items: flex-start;">
                    <input type="radio" name="duration_type" value="standard" class="form-check-input" checked>
                    <div>
                        <strong style="font-size: 1.05rem; display: block; color: var(--color-primary);">Standard (7–10 mins)</strong>
                        <span class="text-sm">Explores narrative & lessons (5 questions).</span>
                    </div>
                </label>

                <label style="border: 2px solid var(--color-border); border-radius: var(--radius-md); padding: 1.25rem; cursor: pointer; display: flex; gap: 0.75rem; align-items: flex-start;">
                    <input type="radio" name="duration_type" value="deep" class="form-check-input">
                    <div>
                        <strong style="font-size: 1.05rem; display: block;">Deep Dive (~15 mins)</strong>
                        <span class="text-sm">Comprehensive narrative details.</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Section 4: Contributor Identity & Privacy -->
        <div class="card" style="margin-bottom: 2.5rem; border-top: 5px solid var(--color-primary);">
            <h2 style="font-size: 1.5rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>👤</span> Section 4: Contributor Identity & Archive Settings
            </h2>

            <?php if ($isLoggedIn): ?>
                <div class="form-group">
                    <label for="display_name" class="form-label">Member Display Name</label>
                    <input type="text" id="display_name" name="display_name" class="form-control" value="<?php echo htmlspecialchars($user['display_name']); ?>" required>
                    <span class="text-sm" style="margin-top: 0.25rem; display: block;">This story will be saved directly to your account archive: <strong><?php echo htmlspecialchars($user['email']); ?></strong></span>
                </div>

                <div class="form-check">
                    <input type="checkbox" id="is_anonymous" name="is_anonymous" class="form-check-input" value="1">
                    <label for="is_anonymous" class="form-check-label">
                        <strong>Publish Anonymously to Public Wisdom Archive</strong><br>
                        <span class="text-sm">Keep this story in your private profile, but hide your name when shared in public AskGPT results.</span>
                    </label>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label for="display_name" class="form-label">Display Name / Contributor Attribution (Optional)</label>
                    <input type="text" id="display_name" name="display_name" class="form-control" placeholder="e.g. Grandma Helen, J.S., or leave blank for Anonymous">
                </div>

                <div class="form-check">
                    <input type="checkbox" id="is_anonymous" name="is_anonymous" class="form-check-input" checked value="1">
                    <label for="is_anonymous" class="form-check-label">
                        <strong>Participate 100% Anonymously</strong><br>
                        <span class="text-sm">Your real identity will never be exposed or stored in search results.</span>
                    </label>
                </div>
            <?php endif; ?>
        </div>

        <!-- Action Button -->
        <div style="text-align: center;">
            <button type="submit" class="btn btn-primary text-lg" style="padding: 1rem 3.5rem; font-size: 1.2rem;">
                <?php echo $isLoggedIn ? 'Begin Member Story ➔' : 'Begin Guest Story ➔'; ?>
            </button>
        </div>
    </form>
</div>

<script>
function selectPersona(card) {
    document.querySelectorAll('.persona-card').forEach(c => {
        c.style.borderColor = 'var(--color-border)';
        c.style.backgroundColor = '#FFFFFF';
    });
    card.style.borderColor = 'var(--color-primary)';
    card.style.backgroundColor = 'var(--color-mint-bg)';
    document.getElementById('personaIdInput').value = card.getAttribute('data-id');
}

function selectTopicPill(btn) {
    document.querySelectorAll('.topic-pill-btn').forEach(b => {
        b.classList.remove('btn-primary');
        b.classList.add('btn-outline');
    });
    btn.classList.remove('btn-outline');
    btn.classList.add('btn-primary');
    document.getElementById('topicIdInput').value = btn.getAttribute('data-id');
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

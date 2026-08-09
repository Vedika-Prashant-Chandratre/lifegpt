<?php
/**
 * LifeGPT - Interview Start/Intro
 */
$pageTitle = "Start Your Interview";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 700px; margin: 0 auto; text-align: center;">
    <span class="hero-subtitle">Begin Your Journey</span>
    <h1 style="font-size: 2.25rem; margin-top: 0.5rem; margin-bottom: 1.5rem;">Share a Piece of Your Wisdom</h1>
    
    <p class="text-lg" style="color: var(--color-text-muted); margin-bottom: 3rem; line-height: 1.6;">
        Sharing your life experiences is simple. You will choose an interviewer persona, select a topic, and participate in a guided chat. We will summarize your stories and help you save them securely.
    </p>

    <div style="display: flex; flex-direction: column; gap: 1.5rem; text-align: left; margin-bottom: 3rem;">
        <div class="card" style="display: flex; gap: 1.5rem; align-items: flex-start; padding: 1.5rem;">
            <div style="font-size: 2.25rem; line-height: 1; margin-top: 0.25rem;">🎭</div>
            <div>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.25rem;">1. Choose Your Interviewer</h3>
                <p class="text-sm">Pick a persona that fits your story—from a playful grandchild to a focused historical archivist.</p>
            </div>
        </div>

        <div class="card" style="display: flex; gap: 1.5rem; align-items: flex-start; padding: 1.5rem;">
            <div style="font-size: 2.25rem; line-height: 1; margin-top: 0.25rem;">📝</div>
            <div>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.25rem;">2. Pick a Life Topic</h3>
                <p class="text-sm">Select from a range of topics, such as career decisions, relationships, funny mishaps, or a general life lesson.</p>
            </div>
        </div>

        <div class="card" style="display: flex; gap: 1.5rem; align-items: flex-start; padding: 1.5rem;">
            <div style="font-size: 2.25rem; line-height: 1; margin-top: 0.25rem;">🔒</div>
            <div>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.25rem;">3. Review Privacy & Consent</h3>
                <p class="text-sm">You decide exactly what happens to your words. Choose private-only, anonymous, or public attribution.</p>
            </div>
        </div>
    </div>

    <div class="btn-group" style="justify-content: center;">
        <a href="<?php echo APP_URL; ?>/interview/choose-persona.php" class="btn btn-primary text-lg" style="padding: 1rem 3rem;">Proceed to Persona Selection</a>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

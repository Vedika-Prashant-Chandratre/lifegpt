<?php
/**
 * LifeGPT - Homepage
 */
$pageTitle = "Your life has answers someone else needs";
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="hero-text">
        <span class="hero-subtitle">A FiftyIsNifty Research Initiative</span>
        <h1 class="hero-title">Your life has answers someone else needs.</h1>
        <p class="hero-copy">
            Talk with an AI interviewer about something you learned, something you would do differently, or something that still makes you laugh. Participate anonymously or create an account to save your conversations.
        </p>
        
        <div class="btn-group">
            <a href="<?php echo APP_URL; ?>/interview/start.php" class="btn btn-primary text-lg" style="padding: 1rem 2.5rem;">Start My Interview</a>
            <a href="<?php echo APP_URL; ?>/ask/" class="btn btn-secondary text-lg" style="padding: 1rem 2.5rem;">Ask LifeGPT</a>
        </div>
        
        <p class="hero-concept">
            Powered by people. Organized by AI.
        </p>
    </div>
    
    <div class="hero-graphic" aria-hidden="true">
        <div class="hero-graphic-circle">
            💡
        </div>
    </div>
</section>

<section style="margin: 4rem 0;">
    <h2 style="text-align: center; margin-bottom: 3rem;">How LifeGPT Works</h2>
    
    <div class="step-list">
        <div class="card step-card">
            <div class="step-number">1</div>
            <h3>Choose a Persona & Topic</h3>
            <p class="text-sm">Select an interviewer style that suits you—whether a Curious Grandchild or a neutral Journalist—and choose a life theme to share.</p>
        </div>
        
        <div class="card step-card">
            <div class="step-number">2</div>
            <h3>Share Your Story</h3>
            <p class="text-sm">Answer questions at your own pace using voice recognition or typing. All recognized transcripts can be edited before submitting.</p>
        </div>
        
        <div class="card step-card">
            <div class="step-number">3</div>
            <h3>Review & Save</h3>
            <p class="text-sm">Read the AI-extracted summary and lessons, specify what parts can be used for community wisdom, and save it securely.</p>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 2rem;">
        <a href="<?php echo APP_URL; ?>/how-it-works.php" class="btn btn-outline">Read the Complete Guide</a>
    </div>
</section>

<section style="background-color: var(--color-bg-alternate); border-radius: var(--border-radius-lg); padding: 3rem; margin: 4rem 0; text-align: center;">
    <h2 style="margin-bottom: 1rem;">Respecting Your Privacy & Consent</h2>
    <p style="max-width: 800px; margin: 0 auto 2rem auto; color: var(--color-text-muted);">
        We believe that your lived experiences are extremely valuable, but you retain full ownership. Your stories will only be stored with your explicit, granular permission, and you can withdraw consent or delete your interviews at any time.
    </p>
    <div style="display: flex; justify-content: center; gap: 1.5rem; flex-wrap: wrap;">
        <span class="font-semibold" style="display: flex; align-items: center; gap: 0.5rem; color: var(--color-success);">✔ Granular Consent</span>
        <span class="font-semibold" style="display: flex; align-items: center; gap: 0.5rem; color: var(--color-success);">✔ Full Anonymity Option</span>
        <span class="font-semibold" style="display: flex; align-items: center; gap: 0.5rem; color: var(--color-success);">✔ Withdrawal Anytime</span>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

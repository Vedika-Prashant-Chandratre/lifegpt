<?php
/**
 * LifeGPT - Homepage (Entry Hub)
 * A FiftyIsNifty research initiative
 */
$pageTitle = "A FiftyIsNifty research initiative";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section style="margin: 1.5rem 0 3.5rem 0;">
    <div style="text-align: center; max-width: 820px; margin: 0 auto 3rem auto;">
        <span class="step-badge" style="background-color: var(--color-mint-bg); color: var(--color-primary); font-size: 0.85rem; letter-spacing: 0.1em; margin-bottom: 1rem;">
            POWERED BY PEOPLE. ORGANIZED BY AI.
        </span>
        <h1 style="font-size: 3.25rem; margin-bottom: 1rem; color: var(--color-primary); line-height: 1.15;">
            Your life has answers someone else needs.
        </h1>
        <p class="text-lg" style="color: var(--color-text-muted); max-width: 680px; margin: 0 auto;">
            Talk with an AI host about something you learned, something you would do differently, or something that still makes you laugh. Participate anonymously or create an account to save your stories.
        </p>
    </div>

    <!-- Dual-Access Module: Card A (Guest Access) & Card B (Member Access) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 2.25rem; max-width: 1120px; margin: 0 auto;">
        
        <!-- CARD A (GUEST ACCESS) -->
        <div class="card card-hover" style="border-top: 6px solid var(--color-primary-light); display: flex; flex-direction: column; justify-content: space-between; padding: 2.5rem;">
            <div>
                <span class="step-badge" style="background-color: var(--color-mint-bg); color: var(--color-primary); margin-bottom: 1rem;">
                    100% ANONYMOUS GUEST ACCESS
                </span>
                <h2 style="font-size: 1.8rem; margin-bottom: 0.75rem;">Guest Story & Search</h2>
                <p class="text-sm" style="font-size: 1rem; line-height: 1.6; margin-bottom: 1.5rem;">
                    Share your wisdom anonymously or search real human stories without registering an account.
                </p>
                
                <ul style="list-style: none; margin-bottom: 2rem;">
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.6rem; font-size: 0.95rem;">
                        <span style="color: var(--color-success); font-weight: bold;">✔</span> No tracking, cookies, or account required
                    </li>
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.6rem; font-size: 0.95rem;">
                        <span style="color: var(--color-success); font-weight: bold;">✔</span> 5-minute dynamic AI story session
                    </li>
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.6rem; font-size: 0.95rem;">
                        <span style="color: var(--color-success); font-weight: bold;">✔</span> Instant AI key takeaways & summary
                    </li>
                </ul>
            </div>
            
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="<?php echo APP_URL; ?>/interview/start.php" class="btn btn-primary" style="flex: 1;">🎙️ Share My Story</a>
                <a href="<?php echo APP_URL; ?>/ask/" class="btn btn-secondary" style="flex: 1;">💬 Ask LifeGPT</a>
            </div>
        </div>

        <!-- CARD B (MEMBER ACCESS) -->
        <div class="card card-hover" style="border-top: 6px solid var(--color-amber); display: flex; flex-direction: column; justify-content: space-between; padding: 2.5rem;">
            <div>
                <span class="step-badge" style="background-color: var(--color-amber-light); color: var(--color-amber); margin-bottom: 1rem;">
                    REGISTERED MEMBER ACCESS
                </span>
                <h2 style="font-size: 1.8rem; margin-bottom: 0.75rem;">Member Archive & Portal</h2>
                <p class="text-sm" style="font-size: 1rem; line-height: 1.6; margin-bottom: 1.5rem;">
                    Log in to save audio recordings, organize your private story archive, and manage your contributions.
                </p>
                
                <ul style="list-style: none; margin-bottom: 2rem;">
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.6rem; font-size: 0.95rem;">
                        <span style="color: var(--color-amber); font-weight: bold;">👤</span> Personal story archive & dashboard
                    </li>
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.6rem; font-size: 0.95rem;">
                        <span style="color: var(--color-amber); font-weight: bold;">👤</span> Audio recordings & AI transcripts
                    </li>
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.6rem; font-size: 0.95rem;">
                        <span style="color: var(--color-amber); font-weight: bold;">👤</span> Full privacy & withdrawal controls
                    </li>
                </ul>
            </div>
            
            <div>
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo APP_URL; ?>/account/choice.php" class="btn btn-amber" style="width: 100%;">✨ Open Member Selection</a>
                <?php else: ?>
                    <a href="<?php echo APP_URL; ?>/account/login.php" class="btn btn-amber" style="width: 100%;">🔑 Sign In / Register</a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>

<!-- Section: Privacy & Process -->
<section style="margin: 4rem 0;">
    <div style="text-align: center; margin-bottom: 2.5rem;">
        <h2 style="font-size: 2.25rem; color: var(--color-primary);">Built with your comfort and privacy in mind</h2>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; align-items: center;">
        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div style="display: flex; gap: 1rem; align-items: flex-start;">
                <div style="width: 48px; height: 48px; border-radius: 14px; background: var(--color-mint-bg); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">🔒</div>
                <div>
                    <h3 style="font-size: 1.2rem; margin-bottom: 0.2rem;">100% Anonymous option</h3>
                    <p class="text-sm">You choose whether to attach your name or remain completely anonymous.</p>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; align-items: flex-start;">
                <div style="width: 48px; height: 48px; border-radius: 14px; background: var(--color-mint-bg); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">🛡️</div>
                <div>
                    <h3 style="font-size: 1.2rem; margin-bottom: 0.2rem;">Review & Edit options</h3>
                    <p class="text-sm">Full transparency. Inspect and edit all AI-generated transcripts before submitting.</p>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; align-items: flex-start;">
                <div style="width: 48px; height: 48px; border-radius: 14px; background: var(--color-mint-bg); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">⚡</div>
                <div>
                    <h3 style="font-size: 1.2rem; margin-bottom: 0.2rem;">Fast & Easy setup</h3>
                    <p class="text-sm">Speak naturally or type your responses. Answer 3 to 5 questions in under 10 minutes.</p>
                </div>
            </div>
        </div>

        <!-- Right Emerald Highlight Box -->
        <div style="background-color: var(--color-primary); color: #FFFFFF; padding: 2.5rem; border-radius: 24px; box-shadow: var(--shadow-md);">
            <span style="display: inline-block; padding: 0.3rem 0.75rem; background: rgba(255,255,255,0.15); border-radius: 9999px; font-size: 0.8rem; font-weight: 700; color: #A7F3D0; margin-bottom: 1rem;">WISDOM ARCHIVE</span>
            <h3 style="color: #FFFFFF; font-size: 1.8rem; margin-bottom: 1rem; line-height: 1.3;">Every story contains a lesson someone is searching for today.</h3>
            <p style="color: #D1FAE5; font-size: 1.05rem; line-height: 1.6; margin-bottom: 1.5rem;">
                LifeGPT transforms personal reflections into structured, searchable wisdom. Your insights help guide students, career switchers, and seekers around the globe.
            </p>
            <button type="button" class="btn btn-secondary" onclick="openHowItWorksModal()" style="border-color: #FFFFFF; color: var(--color-primary);">Learn How It Works →</button>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

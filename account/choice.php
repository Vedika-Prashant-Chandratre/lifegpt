<?php
/**
 * LifeGPT - Member Selection Page ("WELCOME BACK!")
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::requireLogin();
$user = Auth::getCurrentUser();

$pageTitle = "Welcome Back — Member Selection";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 960px; margin: 2rem auto 4rem auto;">
    
    <!-- Top Welcome Badge & Heading -->
    <div style="text-align: center; margin-bottom: 3rem;">
        <span class="step-badge" style="background-color: var(--color-mint-bg); color: var(--color-primary); font-size: 0.9rem;">
            ✨ Welcome back!
        </span>
        <h1 style="font-size: 3rem; margin-bottom: 0.75rem; color: var(--color-primary);">
            Hello, <?php echo htmlspecialchars($user['display_name']); ?>
        </h1>
        <p class="text-lg" style="color: var(--color-text-muted); max-width: 640px; margin: 0 auto;">
            Choose how you'd like to continue your LifeGPT experience today.
        </p>
    </div>

    <!-- Dual Selection Module -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 2.25rem;">
        
        <!-- CARD 1: Share My Story -->
        <div class="card card-hover" style="border-top: 6px solid var(--color-primary); padding: 2.5rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="width: 64px; height: 64px; background-color: var(--color-mint-bg); border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1.25rem;">🚀</div>
                <h2 style="font-size: 1.85rem; margin-bottom: 0.75rem;">Share My Story</h2>
                <p class="text-sm" style="font-size: 1.05rem; line-height: 1.6; margin-bottom: 1.5rem;">
                    Start a new AI-guided storytelling session or manage your saved drafts. Select your persona and topic to preserve your personal life story.
                </p>
                
                <ul style="list-style: none; margin-bottom: 2rem;">
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.6rem; font-size: 0.95rem;">
                        <span style="color: var(--color-success); font-weight: bold;">✔</span> Access personal story dashboard & archive
                    </li>
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.6rem; font-size: 0.95rem;">
                        <span style="color: var(--color-success); font-weight: bold;">✔</span> Choose from 5 AI host personas
                    </li>
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.6rem; font-size: 0.95rem;">
                        <span style="color: var(--color-success); font-weight: bold;">✔</span> Save transcripts & audio to private profile
                    </li>
                </ul>
            </div>

            <a href="<?php echo APP_URL; ?>/dashboard/" class="btn btn-primary" style="width: 100%; padding: 0.9rem 1.5rem; font-size: 1.1rem;">
                🖋️ Share My Story →
            </a>
        </div>

        <!-- CARD 2: AskGPT -->
        <div class="card card-hover" style="border-top: 6px solid var(--color-amber); padding: 2.5rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="width: 64px; height: 64px; background-color: var(--color-amber-light); border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1.25rem;">💬</div>
                <h2 style="font-size: 1.85rem; margin-bottom: 0.75rem;">AskGPT</h2>
                <p class="text-sm" style="font-size: 1.05rem; line-height: 1.6; margin-bottom: 1.5rem;">
                    Search the collective wisdom archive. Explore life advice, career insights, and personal lessons shared by contributors around the world.
                </p>
                
                <ul style="list-style: none; margin-bottom: 2rem;">
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.6rem; font-size: 0.95rem;">
                        <span style="color: var(--color-amber); font-weight: bold;">💬</span> Standalone ChatGPT-style search interface
                    </li>
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.6rem; font-size: 0.95rem;">
                        <span style="color: var(--color-amber); font-weight: bold;">💬</span> Explore wisdom topics & recent searches
                    </li>
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.6rem; font-size: 0.95rem;">
                        <span style="color: var(--color-amber); font-weight: bold;">💬</span> Citations sourced from verified anonymous stories
                    </li>
                </ul>
            </div>

            <a href="<?php echo APP_URL; ?>/ask/" class="btn btn-amber" style="width: 100%; padding: 0.9rem 1.5rem; font-size: 1.1rem;">
                💬 AskGPT →
            </a>
        </div>

    </div>

</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

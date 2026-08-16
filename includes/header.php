<?php
/**
 * LifeGPT - Common Header Template (A FiftyIsNifty research initiative)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$isLoggedIn = Auth::isLoggedIn();
$user = Auth::getCurrentUser();
$isAdmin = Auth::isAdmin();

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | LifeGPT' : 'LifeGPT — A FiftyIsNifty research initiative'; ?></title>
    <meta name="description" content="Share your life stories, lessons, and turning points with LifeGPT. Powered by people. Organized by AI. A FiftyIsNifty research initiative.">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/lifegpt.css?v=<?php echo file_exists(dirname(__DIR__) . '/assets/css/lifegpt.css') ? filemtime(dirname(__DIR__) . '/assets/css/lifegpt.css') : time(); ?>">
    <?php echo isset($extraHead) ? $extraHead : ''; ?>
</head>
<body>
    <header>
        <div class="header-container">
            <!-- Left Logo with Live Indicator Dot -->
            <a href="<?php echo APP_URL; ?>/" class="logo-link" aria-label="LifeGPT Homepage">
                <span class="logo-live-dot" title="Live System Active"></span>
                LifeGPT 
                <span class="logo-sub">A FiftyIsNifty research initiative</span>
            </a>
            
            <!-- Center is clean and empty per specification -->
            <div></div>

            <!-- Right Header Action: Admin Portal Pill Button & User Avatar -->
            <div style="display: flex; align-items: center; gap: 1rem;">
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo APP_URL; ?>/account/choice.php" class="btn btn-secondary" style="min-height: 42px; padding: 0.35rem 1.1rem; font-size: 0.9rem;">
                        👋 <?php echo htmlspecialchars($user['display_name']); ?>
                    </a>
                <?php endif; ?>

                <a href="<?php echo APP_URL; ?>/admin/login.php" class="btn btn-primary" style="background-color: var(--color-primary); color: #FFFFFF; font-size: 0.9rem; min-height: 42px; padding: 0.35rem 1.25rem;">
                    🔐 Admin Portal
                </a>
            </div>
        </div>
    </header>

    <!-- Bottom-Right Floating Avatar Widget (🤖) -->
    <div class="floating-avatar-widget" onclick="openHowItWorksModal()" title="How LifeGPT Works (Onboarding Guide)">
        🤖
    </div>

    <!-- 4-Step Centered Onboarding Modal Overlay ("How LifeGPT Works") -->
    <div id="howItWorksModal" class="modal-overlay" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-header">
                <h2>How LifeGPT Works</h2>
                <button type="button" class="modal-close-btn" onclick="closeHowItWorksModal()" aria-label="Close modal">✕</button>
            </div>

            <!-- Step 1: Welcome & Mission -->
            <div id="modalStep1" class="modal-step-body">
                <span class="step-badge">STEP 1 OF 4 — WELCOME</span>
                <div style="width: 60px; height: 60px; border-radius: 16px; background-color: var(--color-mint-bg); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1.25rem;">🌱</div>
                <h3 style="font-size: 1.45rem; margin-bottom: 0.5rem;">Your Life Experience Matters</h3>
                <p class="text-sm">Every lesson you've learned, obstacle you've overcome, or story that makes you laugh holds immense value. LifeGPT collects real human wisdom to help others navigating similar paths.</p>
            </div>

            <!-- Step 2: Talk or Type -->
            <div id="modalStep2" class="modal-step-body" style="display: none;">
                <span class="step-badge">STEP 2 OF 4 — STORYTELLING</span>
                <div style="width: 60px; height: 60px; border-radius: 16px; background-color: var(--color-mint-bg); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1.25rem;">🎙️</div>
                <h3 style="font-size: 1.45rem; margin-bottom: 0.5rem;">Talk or Type at Your Own Pace</h3>
                <p class="text-sm">Choose from customized AI host personas like a Curious Grandchild or Journalist. Speak your answers aloud using voice recognition or type them in. Review and edit your responses anytime.</p>
            </div>

            <!-- Step 3: Privacy & Control -->
            <div id="modalStep3" class="modal-step-body" style="display: none;">
                <span class="step-badge">STEP 3 OF 4 — PRIVACY</span>
                <div style="width: 60px; height: 60px; border-radius: 16px; background-color: var(--color-mint-bg); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1.25rem;">🔒</div>
                <h3 style="font-size: 1.45rem; margin-bottom: 0.5rem;">You Are in Complete Control</h3>
                <p class="text-sm">Participate 100% anonymously without an account, or sign in to save audio recordings and AI-generated transcripts to your private archive. Review and edit everything before sharing.</p>
            </div>

            <!-- Step 4: Ready to Inspire -->
            <div id="modalStep4" class="modal-step-body" style="display: none;">
                <span class="step-badge">STEP 4 OF 4 — GET STARTED</span>
                <div style="width: 60px; height: 60px; border-radius: 16px; background-color: var(--color-mint-bg); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1.25rem;">🎉</div>
                <h3 style="font-size: 1.45rem; margin-bottom: 0.5rem;">Ready to Inspire Someone?</h3>
                <p class="text-sm">Your anonymized insights join a growing library of real-life stories that guide students, career switchers, and seekers around the world.</p>
            </div>

            <!-- Dots Indicator -->
            <div class="stepper-dots">
                <span class="stepper-dot active" id="dot1"></span>
                <span class="stepper-dot" id="dot2"></span>
                <span class="stepper-dot" id="dot3"></span>
                <span class="stepper-dot" id="dot4"></span>
            </div>

            <!-- Footer Buttons -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="modalBackBtn" onclick="changeModalStep(-1)" disabled>← Back</button>
                <button type="button" class="btn btn-primary" id="modalNextBtn" onclick="changeModalStep(1)">Next Step →</button>
            </div>
        </div>
    </div>

    <script>
        let currentModalStep = 1;

        function openHowItWorksModal() {
            currentModalStep = 1;
            updateModalUI();
            const modal = document.getElementById('howItWorksModal');
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeHowItWorksModal() {
            const modal = document.getElementById('howItWorksModal');
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
        }

        function changeModalStep(delta) {
            currentModalStep += delta;
            if (currentModalStep > 4) {
                closeHowItWorksModal();
                window.location.href = "<?php echo APP_URL; ?>/interview/start.php";
                return;
            }
            if (currentModalStep < 1) currentModalStep = 1;
            updateModalUI();
        }

        function updateModalUI() {
            for (let i = 1; i <= 4; i++) {
                document.getElementById('modalStep' + i).style.display = (i === currentModalStep) ? 'block' : 'none';
                const dot = document.getElementById('dot' + i);
                if (i === currentModalStep) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            }

            const backBtn = document.getElementById('modalBackBtn');
            const nextBtn = document.getElementById('modalNextBtn');

            backBtn.disabled = (currentModalStep === 1);

            if (currentModalStep === 4) {
                nextBtn.innerHTML = '🚀 Start My Story';
            } else {
                nextBtn.innerHTML = 'Next Step →';
            }
        }
    </script>
    <main class="main-content">

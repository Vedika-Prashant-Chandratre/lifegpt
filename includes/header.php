<?php
/**
 * LifeGPT - Common Header Template (A FiftyIsNifty research initiative)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/i18n.php';

$isLoggedIn = Auth::isLoggedIn();
$user = Auth::getCurrentUser();
$isAdmin = Auth::isAdmin();

// Language detection & persistence (defaults to 'en')
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'hi', 'mr'])) {
    $_SESSION['ui_lang'] = $_GET['lang'];
    setcookie('ui_lang', $_GET['lang'], time() + (86400 * 30), '/');
} elseif (isset($_COOKIE['ui_lang']) && in_array($_COOKIE['ui_lang'], ['en', 'hi', 'mr'])) {
    $_SESSION['ui_lang'] = $_COOKIE['ui_lang'];
}
$uiLang = $_SESSION['ui_lang'] ?? 'en';
$t = getLangStrings($uiLang);

$currentPage = basename($_SERVER['PHP_SELF']);
$rootIndexPath = realpath(dirname(__DIR__) . '/index.php');
$isHomePage = false;
if (!empty($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === $rootIndexPath) {
    $isHomePage = true;
} elseif ($currentPage === 'index.php') {
    $phpSelf = $_SERVER['PHP_SELF'] ?? '';
    if (strpos($phpSelf, '/ask/') === false && strpos($phpSelf, '/dashboard/') === false && strpos($phpSelf, '/admin/') === false) {
        $isHomePage = true;
    }
}
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

    <style>
        /* Hide Google Translate top banner and popups for clean UI */
        .goog-te-banner-frame.skiptranslate, .goog-te-banner-frame { display: none !important; }
        body { top: 0px !important; }
        #goog-gt-tt, .goog-te-balloon-frame { display: none !important; }
        .goog-text-highlight { background: none !important; box-shadow: none !important; }
        .skiptranslate > iframe { display: none !important; }
        #google_translate_element { display: none !important; }

        /* Modern Language Switcher Pill in Header */
        .lang-switcher-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            border: 1.5px solid var(--color-border);
            border-radius: var(--radius-pill);
            padding: 0.2rem 0.45rem;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin: 0 0.5rem;
        }
        .lang-btn {
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--color-text-muted);
            text-decoration: none;
            border: none;
            background: transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        .lang-btn:hover {
            color: var(--color-primary);
            background: var(--color-mint-bg);
        }
        .lang-btn.active {
            color: var(--color-primary);
            background: var(--color-mint-bg);
            font-weight: 700;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <!-- Left Logo with Live Indicator Dot -->
            <a href="<?php echo APP_URL; ?>/" class="logo-link" aria-label="LifeGPT Homepage">
                <span class="logo-live-dot" title="Live System Active"></span>
                <span class="notranslate" translate="no">LifeGPT</span> 
                <span class="logo-sub notranslate" translate="no">A FiftyIsNifty research initiative</span>
            </a>
            
            <!-- Mobile Menu Toggle Button -->
            <button class="nav-toggle notranslate" translate="no" id="navToggle" aria-label="Toggle navigation menu" aria-expanded="false">
                &#9776;
            </button>

            <!-- Main Visitor Navigation Links -->
            <nav class="nav-menu" id="navMenu">
                <a href="<?php echo APP_URL; ?>/" class="nav-link <?php echo $isHomePage ? 'active' : ''; ?>"><?php echo $t['nav_home'] ?? 'Home'; ?></a>
                <a href="<?php echo APP_URL; ?>/how-it-works.php" class="nav-link <?php echo ($currentPage === 'how-it-works.php') ? 'active' : ''; ?>"><?php echo $t['nav_how'] ?? 'How It Works'; ?></a>
                <a href="<?php echo APP_URL; ?>/interview/start.php" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/interview/') !== false) ? 'active' : ''; ?>"><?php echo $t['nav_share'] ?? 'Share a Story'; ?></a>
                <a href="<?php echo APP_URL; ?>/ask/" class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/ask/') !== false) ? 'active' : ''; ?>"><?php echo $t['nav_ask'] ?? 'Ask LifeGPT'; ?></a>

                <!-- Top Right Language Switcher -->
                <div class="lang-switcher-pill notranslate" translate="no" title="Choose Language / भाषा चुनें / भाषा निवडा">
                    <span class="notranslate" translate="no" style="font-size: 0.85rem; margin-right: 0.15rem;">&#127760;</span>
                    <button type="button" class="lang-btn <?php echo $uiLang === 'en' ? 'active' : ''; ?>" onclick="switchLanguage('en')">English</button>
                    <button type="button" class="lang-btn <?php echo $uiLang === 'hi' ? 'active' : ''; ?>" onclick="switchLanguage('hi')">हिन्दी</button>
                    <button type="button" class="lang-btn <?php echo $uiLang === 'mr' ? 'active' : ''; ?>" onclick="switchLanguage('mr')">मराठी</button>
                </div>

                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo APP_URL; ?>/dashboard/" class="btn btn-secondary" style="min-height: 40px; padding: 0.35rem 1.25rem; font-size: 0.9rem;">
                        <?php echo $t['nav_dashboard'] ?? 'Dashboard'; ?>
                    </a>
                <?php else: ?>
                    <a href="<?php echo APP_URL; ?>/account/login.php" class="btn btn-secondary" style="min-height: 40px; padding: 0.35rem 1.25rem; font-size: 0.9rem;">
                        <?php echo $t['nav_signin'] ?? 'Sign In'; ?>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Hidden Google Translate Element -->
    <div id="google_translate_element" style="display:none;"></div>

    <!-- Bottom-Right Floating Avatar Widget -->
    <div class="floating-avatar-widget notranslate" translate="no" onclick="openHowItWorksModal()" title="How LifeGPT Works (Onboarding Guide)">&#129302;</div>

    <!-- 4-Step Centered Onboarding Modal Overlay ("How LifeGPT Works") -->
    <div id="howItWorksModal" class="modal-overlay" aria-hidden="true">
        <div class="modal-card">
            <div class="modal-header">
                <h2>How LifeGPT Works</h2>
                <button type="button" class="modal-close-btn" onclick="closeHowItWorksModal()" aria-label="Close modal">&#10005;</button>
            </div>

            <!-- Step 1: Welcome & Mission -->
            <div id="modalStep1" class="modal-step-body">
                <span class="step-badge notranslate" translate="no">STEP 1 OF 4 &mdash; WELCOME</span>
                <div class="notranslate" translate="no" style="width: 60px; height: 60px; border-radius: 16px; background-color: var(--color-mint-bg); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1.25rem;">&#127793;</div>
                <h3 style="font-size: 1.45rem; margin-bottom: 0.5rem;">Your Life Experience Matters</h3>
                <p class="text-sm">Every lesson you've learned, obstacle you've overcome, or story that makes you laugh holds immense value. LifeGPT collects real human wisdom to help others navigating similar paths.</p>
            </div>

            <!-- Step 2: Talk or Type -->
            <div id="modalStep2" class="modal-step-body" style="display: none;">
                <span class="step-badge notranslate" translate="no">STEP 2 OF 4 &mdash; STORYTELLING</span>
                <div class="notranslate" translate="no" style="width: 60px; height: 60px; border-radius: 16px; background-color: var(--color-mint-bg); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1.25rem;">&#127897;&#65039;</div>
                <h3 style="font-size: 1.45rem; margin-bottom: 0.5rem;">Talk or Type at Your Own Pace</h3>
                <p class="text-sm">Choose from customized AI host personas like a Curious Grandchild or Journalist. Speak your answers aloud using voice recognition or type them in. Review and edit your responses anytime.</p>
                <div style="background: var(--color-bg-base); border-left: 3px solid var(--color-primary); padding: 0.6rem 0.85rem; border-radius: 6px; margin-top: 0.75rem; font-size: 0.85rem; color: var(--color-text-main);">
                    <strong>Example:</strong> Maria shared how she changed careers at 52. LifeGPT asked five follow-up questions, created a summary, and extracted three lessons for others considering a career change.
                </div>
            </div>

            <!-- Step 3: Privacy & Control -->
            <div id="modalStep3" class="modal-step-body" style="display: none;">
                <span class="step-badge notranslate" translate="no">STEP 3 OF 4 &mdash; PRIVACY</span>
                <div class="notranslate" translate="no" style="width: 60px; height: 60px; border-radius: 16px; background-color: var(--color-mint-bg); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1.25rem;">&#128274;</div>
                <h3 style="font-size: 1.45rem; margin-bottom: 0.5rem;">You Are in Complete Control</h3>
                <p class="text-sm">No account is required. You may participate without displaying your name. Please avoid sharing information that could identify you or someone else. Review and edit everything before sharing, or sign in to save your private archive.</p>
            </div>

            <!-- Step 4: Ready to Inspire -->
            <div id="modalStep4" class="modal-step-body" style="display: none;">
                <span class="step-badge notranslate" translate="no">STEP 4 OF 4 &mdash; GET STARTED</span>
                <div class="notranslate" translate="no" style="width: 60px; height: 60px; border-radius: 16px; background-color: var(--color-mint-bg); display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1.25rem;">&#127881;</div>
                <h3 style="font-size: 1.45rem; margin-bottom: 0.5rem;">Ready to Inspire Someone?</h3>
                <p class="text-sm">Your contributed insights join a growing library of real-life stories that guide students, career switchers, and seekers.</p>
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
                <button type="button" class="btn btn-secondary" id="modalBackBtn" onclick="changeModalStep(-1)" disabled><span class="notranslate" translate="no">&larr;</span> Back</button>
                <button type="button" class="btn btn-primary" id="modalNextBtn" onclick="changeModalStep(1)">Next Step <span class="notranslate" translate="no">&rarr;</span></button>
            </div>
        </div>
    </div>

    <!-- Google Translate Script & switchLanguage Handler -->
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                includedLanguages: 'en,hi,mr',
                autoDisplay: false
            }, 'google_translate_element');
        }

        function switchLanguage(lang) {
            // 1. Set backend preference cookie
            document.cookie = "ui_lang=" + lang + "; path=/; max-age=" + (86400 * 30);

            // 2. Set browser Google Translate cookie
            if (lang === 'en') {
                // Clear translate cookie to restore original English
                document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=" + window.location.hostname;
                document.cookie = "googtrans=/en/en; path=/;";
                document.cookie = "googtrans=/en/en; path=/; domain=" + window.location.hostname;
            } else {
                document.cookie = "googtrans=/en/" + lang + "; path=/;";
                document.cookie = "googtrans=/en/" + lang + "; path=/; domain=" + window.location.hostname;
            }

            // 3. Try to trigger Google Translate dropdown directly if present
            var combo = document.querySelector('.goog-te-combo');
            if (combo) {
                combo.value = lang;
                combo.dispatchEvent(new Event('change'));
            }

            // 4. Reload with ?lang= parameter to sync backend session and browser translation
            var currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('lang', lang);
            window.location.href = currentUrl.toString();
        }

        // Auto-check on page load if googtrans cookie is set but combo hasn't fired
        document.addEventListener('DOMContentLoaded', function() {
            var currentLang = '<?php echo $uiLang; ?>';
            if (currentLang && currentLang !== 'en') {
                var checkInterval = setInterval(function() {
                    var combo = document.querySelector('.goog-te-combo');
                    if (combo) {
                        if (combo.value !== currentLang) {
                            combo.value = currentLang;
                            combo.dispatchEvent(new Event('change'));
                        }
                        clearInterval(checkInterval);
                    }
                }, 300);
                setTimeout(function() { clearInterval(checkInterval); }, 5000);
            }
        });
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

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
                nextBtn.innerHTML = '<span class="notranslate" translate="no">&#128640;</span> Start My Story';
            } else {
                nextBtn.innerHTML = 'Next Step <span class="notranslate" translate="no">&rarr;</span>';
            }
        }

        // Mobile Nav Toggle
        const navToggle = document.getElementById('navToggle');
        const navMenu = document.getElementById('navMenu');
        if (navToggle && navMenu) {
            navToggle.addEventListener('click', function() {
                const expanded = this.getAttribute('aria-expanded') === 'true' || false;
                navMenu.classList.toggle('show');
                this.setAttribute('aria-expanded', !expanded);
                this.innerHTML = expanded ? '☰' : '✕';
            });
        }
    </script>
    <main class="main-content">
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
    <div style="text-align: center; max-width: 820px; margin: 0 auto 2.5rem auto;">
        <span class="step-badge" style="background-color: var(--color-mint-bg); color: var(--color-primary); font-size: 0.85rem; letter-spacing: 0.1em; margin-bottom: 1rem;">
            POWERED BY PEOPLE. ORGANIZED BY AI.
        </span>
                <!-- Prominent Home Page Language Selector -->
        <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: #ffffff; border: 1.5px solid var(--color-border); border-radius: var(--radius-pill); padding: 0.4rem 1.1rem; margin-bottom: 1.5rem; box-shadow: var(--shadow-subtle);">
            <span style="font-size: 0.95rem; color: var(--color-text-main);">🌐 <strong>Language / भाषा / भाषा:</strong></span>
            <button type="button" class="lang-btn <?php echo ($uiLang ?? 'en') === 'en' ? 'active' : ''; ?>" onclick="switchLanguage('en')" style="font-size: 0.92rem; font-weight: 600; padding: 0.3rem 0.75rem;">🇮🇳 English</button>
            <button type="button" class="lang-btn <?php echo ($uiLang ?? 'en') === 'hi' ? 'active' : ''; ?>" onclick="switchLanguage('hi')" style="font-size: 0.92rem; font-weight: 600; padding: 0.3rem 0.75rem;">🇮🇳 हिन्दी (Hindi)</button>
            <button type="button" class="lang-btn <?php echo ($uiLang ?? 'en') === 'mr' ? 'active' : ''; ?>" onclick="switchLanguage('mr')" style="font-size: 0.92rem; font-weight: 600; padding: 0.3rem 0.75rem;">🇮🇳 मराठी (Marathi)</button>
        </div>

        <h1 style="font-size: 3.25rem; margin-bottom: 1rem; color: var(--color-primary); line-height: 1.15;">
            Your life has answers someone else needs.
        </h1>
        <p class="text-lg" style="color: var(--color-text-muted); max-width: 680px; margin: 0 auto 2rem auto;">
            Talk with an AI host about something you learned, something you would do differently, or something that still makes you laugh. Participate without displaying your name, or sign in to save your stories.
        </p>

        <!-- Preferred Action Hierarchy: Primary / Secondary / Small Link -->
        <div style="display: flex; gap: 1.25rem; justify-content: center; align-items: center; flex-wrap: wrap; margin-bottom: 1rem;">
            <a href="<?php echo APP_URL; ?>/interview/start.php" class="btn btn-primary text-lg" style="padding: 0.9rem 2.25rem; font-size: 1.15rem; box-shadow: var(--shadow-md);">
                🎙️ Share Your Story
            </a>
            <a href="<?php echo APP_URL; ?>/ask/" class="btn btn-secondary text-lg" style="padding: 0.9rem 2rem; font-size: 1.15rem;">
                💬 Ask LifeGPT
            </a>
        </div>

        <div style="margin-top: 0.75rem;">
            <span style="color: var(--color-text-muted); font-size: 0.95rem;">Already contributed? </span>
            <?php if ($isLoggedIn): ?>
                <a href="<?php echo APP_URL; ?>/dashboard/" style="font-size: 0.95rem; font-weight: 600; text-decoration: underline; color: var(--color-primary);">
                    Open your saved stories dashboard &rarr;
                </a>
            <?php else: ?>
                <a href="<?php echo APP_URL; ?>/account/login.php" style="font-size: 0.95rem; font-weight: 600; text-decoration: underline; color: var(--color-primary);">
                    Sign in to view your saved stories &rarr;
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 3 Distinct Offerings: Share a Story / Explore Life Wisdom / View My Stories -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; max-width: 1200px; margin: 3rem auto 0 auto;">
        
        <!-- CARD 1: SHARE A STORY -->
        <div class="card card-hover" style="border-top: 6px solid var(--color-primary); display: flex; flex-direction: column; justify-content: space-between; padding: 2.25rem;">
            <div>
                <span class="step-badge" style="background-color: var(--color-mint-bg); color: var(--color-primary); margin-bottom: 0.75rem;">
                    STORY INTERVIEW
                </span>
                <h2 style="font-size: 1.7rem; margin-bottom: 0.75rem;">Share a Story</h2>
                <p class="text-sm" style="font-size: 0.98rem; line-height: 1.6; margin-bottom: 1.25rem;">
                    Reflect on turning points, career changes, and life lessons with a thoughtful AI host. Speak naturally or type at your own pace.
                </p>
                
                <ul style="list-style: none; margin-bottom: 2rem;">
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: flex-start; gap: 0.6rem; font-size: 0.92rem;">
                        <span style="color: var(--color-success); font-weight: bold; line-height: 1.4;">✔</span>
                        <span>No account required. Participate without displaying your name.</span>
                    </li>
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: flex-start; gap: 0.6rem; font-size: 0.92rem;">
                        <span style="color: var(--color-success); font-weight: bold; line-height: 1.4;">✔</span>
                        <span>5-minute dynamic AI interview with gentle follow-up questions.</span>
                    </li>
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: flex-start; gap: 0.6rem; font-size: 0.92rem;">
                        <span style="color: var(--color-success); font-weight: bold; line-height: 1.4;">✔</span>
                        <span>Instant AI key takeaways and editable summary before saving.</span>
                    </li>
                </ul>
            </div>
            
            <div>
                <a href="<?php echo APP_URL; ?>/interview/start.php" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    🎙️ Share a Story
                </a>
            </div>
        </div>

        <!-- CARD 2: EXPLORE LIFE WISDOM -->
        <div class="card card-hover" style="border-top: 6px solid var(--color-primary-light); display: flex; flex-direction: column; justify-content: space-between; padding: 2.25rem;">
            <div>
                <span class="step-badge" style="background-color: var(--color-mint-bg); color: var(--color-primary-light); margin-bottom: 0.75rem;">
                    AI SEARCH & DISCOVERY
                </span>
                <h2 style="font-size: 1.7rem; margin-bottom: 0.75rem;">Explore Life Wisdom</h2>
                <p class="text-sm" style="font-size: 0.98rem; line-height: 1.6; margin-bottom: 1.25rem;">
                    Ask any question to search real lived experiences, practical advice, and perspectives across contributed life stories.
                </p>
                
                <ul style="list-style: none; margin-bottom: 2rem;">
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: flex-start; gap: 0.6rem; font-size: 0.92rem;">
                        <span style="color: var(--color-primary-light); font-weight: bold; line-height: 1.4;">✔</span>
                        <span>AI-assisted search across contributed stories.</span>
                    </li>
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: flex-start; gap: 0.6rem; font-size: 0.92rem;">
                        <span style="color: var(--color-primary-light); font-weight: bold; line-height: 1.4;">✔</span>
                        <span>Real answers to career shifts, relationships, and aging questions.</span>
                    </li>
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: flex-start; gap: 0.6rem; font-size: 0.92rem;">
                        <span style="color: var(--color-primary-light); font-weight: bold; line-height: 1.4;">✔</span>
                        <span>Searchable wisdom with relevant story citations and context.</span>
                    </li>
                </ul>
            </div>
            
            <div>
                <a href="<?php echo APP_URL; ?>/ask/" class="btn btn-secondary" style="width: 100%; justify-content: center;">
                    💬 Explore Life Wisdom
                </a>
            </div>
        </div>

        <!-- CARD 3: VIEW MY STORIES -->
        <div class="card card-hover" style="border-top: 6px solid var(--color-amber); display: flex; flex-direction: column; justify-content: space-between; padding: 2.25rem;">
            <div>
                <span class="step-badge" style="background-color: var(--color-amber-light); color: var(--color-amber); margin-bottom: 0.75rem;">
                    SAVED STORIES ARCHIVE
                </span>
                <h2 style="font-size: 1.7rem; margin-bottom: 0.75rem;">View My Stories</h2>
                <p class="text-sm" style="font-size: 0.98rem; line-height: 1.6; margin-bottom: 1.25rem;">
                    Access your personal dashboard to review saved transcripts, organize your private archive, and manage consent settings.
                </p>
                
                <ul style="list-style: none; margin-bottom: 2rem;">
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: flex-start; gap: 0.6rem; font-size: 0.92rem;">
                        <span style="color: var(--color-amber); font-weight: bold; line-height: 1.4;">👤</span>
                        <span>Personal story archive and contributor dashboard.</span>
                    </li>
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: flex-start; gap: 0.6rem; font-size: 0.92rem;">
                        <span style="color: var(--color-amber); font-weight: bold; line-height: 1.4;">👤</span>
                        <span>Saved transcripts and AI-generated summaries.</span>
                    </li>
                    <li style="margin-bottom: 0.6rem; display: flex; align-items: flex-start; gap: 0.6rem; font-size: 0.92rem;">
                        <span style="color: var(--color-amber); font-weight: bold; line-height: 1.4;">👤</span>
                        <span>Privacy settings and withdrawal controls anytime.</span>
                    </li>
                </ul>
            </div>
            
            <div>
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo APP_URL; ?>/dashboard/" class="btn btn-amber" style="width: 100%; justify-content: center;">
                        ✨ Open My Story Dashboard
                    </a>
                <?php else: ?>
                    <a href="<?php echo APP_URL; ?>/account/login.php" class="btn btn-amber" style="width: 100%; justify-content: center;">
                        🔑 Sign In to View Stories
                    </a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>

<!-- Concrete Examples Section: How LifeGPT Works & Sample Ask LifeGPT Q&A -->
<section style="margin: 4.5rem 0;">
    <div style="text-align: center; margin-bottom: 2.5rem;">
        <span class="step-badge" style="background-color: var(--color-mint-bg); color: var(--color-primary); font-size: 0.85rem; letter-spacing: 0.08em;">
            SEE IT IN PRACTICE
        </span>
        <h2 style="font-size: 2.25rem; color: var(--color-primary);">How LifeGPT Works in the Real World</h2>
        <p class="text-sm" style="max-width: 640px; margin: 0.5rem auto 0 auto; font-size: 1.05rem;">
            From raw reflection to shared life wisdom, here is how stories are told and how answers are discovered.
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 2rem; max-width: 1120px; margin: 0 auto;">
        
        <!-- Example 1: Story Interview in Action (Maria Example) -->
        <div class="card" style="background: linear-gradient(135deg, #FFFFFF 0%, var(--color-mint-bg) 100%); border-left: 6px solid var(--color-primary); padding: 2.25rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <span class="step-badge" style="background-color: var(--color-primary); color: #FFFFFF; margin-bottom: 0;">
                    STORYTELLING EXAMPLE
                </span>
                <span style="font-size: 1.5rem;">🌱</span>
            </div>
            <h3 style="font-size: 1.35rem; margin-bottom: 0.75rem; color: var(--color-primary);">How an Interview Becomes Wisdom</h3>
            <p style="font-size: 1.05rem; line-height: 1.65; color: var(--color-text-main); margin-bottom: 1.5rem;">
                “Maria shared how she changed careers at 52. LifeGPT asked five follow-up questions, created a summary, and extracted three lessons for others considering a career change.”
            </p>
            <div style="background: rgba(255, 255, 255, 0.8); border: 1px solid var(--color-mint-border); border-radius: var(--radius-md); padding: 1rem 1.25rem;">
                <div style="font-weight: 700; font-size: 0.85rem; color: var(--color-primary); margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.05em;">Three Key Lessons Extracted:</div>
                <div style="font-size: 0.92rem; color: var(--color-text-muted); line-height: 1.5;">
                    1. Test new fields with small freelance projects first.<br>
                    2. Decades of general problem-solving transfer across roles.<br>
                    3. Embrace being a beginner again with patience.
                </div>
            </div>
        </div>

        <!-- Example 2: Ask LifeGPT Sample Q&A -->
        <div class="card" style="background: linear-gradient(135deg, #FFFFFF 0%, var(--color-amber-light) 100%); border-left: 6px solid var(--color-amber); padding: 2.25rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <span class="step-badge" style="background-color: var(--color-amber); color: #FFFFFF; margin-bottom: 0;">
                    ASK LIFEGPT EXAMPLE
                </span>
                <span style="font-size: 1.5rem;">💬</span>
            </div>
            <h3 style="font-size: 1.35rem; margin-bottom: 0.75rem; color: var(--color-primary);">Sample Question & Answer</h3>
            
            <div style="background: rgba(255, 255, 255, 0.9); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1rem 1.25rem; margin-bottom: 1rem;">
                <div style="font-size: 0.82rem; font-weight: 700; color: var(--color-text-muted); margin-bottom: 0.25rem;">VISITOR ASKS:</div>
                <div style="font-weight: 600; font-size: 1rem; color: var(--color-text-main);">
                    “What advice do people share about changing careers later in life?”
                </div>
            </div>

            <div style="background: rgba(255, 255, 255, 0.9); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1rem 1.25rem;">
                <div style="font-size: 0.82rem; font-weight: 700; color: var(--color-amber); margin-bottom: 0.25rem;">LIFEGPT RESPONDS:</div>
                <p style="font-size: 0.95rem; line-height: 1.55; color: var(--color-text-main); margin-bottom: 0.75rem;">
                    “Contributors emphasize starting with small freelance experiments before quitting, treating decades of problem-solving as your greatest asset, and being comfortable being a beginner again.”
                </p>
                <div class="step-badge" style="background: var(--color-mint-bg); color: var(--color-primary); font-size: 0.75rem; margin-bottom: 0;">
                    📜 AI-assisted search across contributed stories
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Section: Privacy & Process -->
<section style="margin: 4.5rem 0;">
    <div style="text-align: center; margin-bottom: 2.5rem;">
        <h2 style="font-size: 2.25rem; color: var(--color-primary);">Built with your comfort and privacy in mind</h2>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; align-items: center;">
        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div style="display: flex; gap: 1rem; align-items: flex-start;">
                <div style="width: 48px; height: 48px; border-radius: 14px; background: var(--color-mint-bg); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">🔒</div>
                <div>
                    <h3 style="font-size: 1.2rem; margin-bottom: 0.2rem;">Comfort & Name Privacy</h3>
                    <p class="text-sm">No account is required. You may participate without displaying your name. Please avoid sharing information that could identify you or someone else.</p>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; align-items: flex-start;">
                <div style="width: 48px; height: 48px; border-radius: 14px; background: var(--color-mint-bg); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">🛡️</div>
                <div>
                    <h3 style="font-size: 1.2rem; margin-bottom: 0.2rem;">Review & Edit Options</h3>
                    <p class="text-sm">Inspect and edit all AI-generated transcripts and key takeaways before submitting anything to the archive.</p>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; align-items: flex-start;">
                <div style="width: 48px; height: 48px; border-radius: 14px; background: var(--color-mint-bg); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">⚡</div>
                <div>
                    <h3 style="font-size: 1.2rem; margin-bottom: 0.2rem;">Fast & Accessible Experience</h3>
                    <p class="text-sm">Speak naturally or type your responses. Answer 3 to 5 questions at your own comfortable pace.</p>
                </div>
            </div>
        </div>

        <!-- Right Emerald Highlight Box -->
        <div style="background-color: var(--color-primary); color: #FFFFFF; padding: 2.5rem; border-radius: 24px; box-shadow: var(--shadow-md);">
            <span style="display: inline-block; padding: 0.3rem 0.75rem; background: rgba(255,255,255,0.15); border-radius: 9999px; font-size: 0.8rem; font-weight: 700; color: #A7F3D0; margin-bottom: 1rem;">WISDOM ARCHIVE</span>
            <h3 style="color: #FFFFFF; font-size: 1.8rem; margin-bottom: 1rem; line-height: 1.3;">Every story contains a lesson someone is searching for today.</h3>
            <p style="color: #D1FAE5; font-size: 1.05rem; line-height: 1.6; margin-bottom: 1.5rem;">
                LifeGPT transforms personal reflections into structured, searchable wisdom. Your insights help guide students, career switchers, and life seekers.
            </p>
            <button type="button" class="btn btn-secondary" onclick="openHowItWorksModal()" style="border-color: #FFFFFF; color: var(--color-primary);">Learn How It Works →</button>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
